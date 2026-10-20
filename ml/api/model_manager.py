"""Pemuatan, validasi, dan prediksi model aktif PhishGuard (PHASE 13).

Prinsip:
- File model dimuat dengan unpickler terbatas (allowlist objek), bukan joblib.load biasa.
- Sebuah bundle hanya menjadi model aktif jika SEMUA pemeriksaan lulus. Jika gagal,
  model yang sedang aktif tetap dipakai.
- Nama bundle divalidasi agar tidak bisa keluar dari folder registry.
"""
from __future__ import annotations

import hashlib
import json
import logging
import math
import re
import sys
import threading
import warnings
from dataclasses import dataclass, field
from datetime import datetime, timezone
from importlib import metadata as importlib_metadata
from pathlib import Path
from typing import Any

import joblib
import numpy as np
from joblib import numpy_pickle as _numpy_pickle

# Extractor fitur ada di ml/src dan dipakai bersama dengan training.
_SRC_DIR = Path(__file__).resolve().parents[1] / "src"
if str(_SRC_DIR) not in sys.path:
    sys.path.insert(0, str(_SRC_DIR))

import url_features as uf  # noqa: E402  (harus setelah sys.path diatur)

logger = logging.getLogger("phishguard.model")

REQUIRED_FILES = ("model.pkl", "metadata.json", "feature_names.json")
MAX_JSON_BYTES = 2 * 1024 * 1024
BUNDLE_NAME_PATTERN = re.compile(r"^[A-Za-z0-9][A-Za-z0-9_.-]{0,99}$")
DEFAULT_RISK_THRESHOLDS = (0.30, 0.70)

# Hanya objek berikut yang boleh muncul di dalam file model. Objek lain (mis. os.system,
# builtins.eval, subprocess.Popen) ditolak SEBELUM dieksekusi. Untuk mengizinkan algoritma
# baru, tambahkan kelasnya di sini secara sadar setelah ditinjau.
ALLOWED_GLOBALS = frozenset({
    ("builtins", "bytearray"), ("builtins", "set"), ("builtins", "frozenset"),
    ("collections", "OrderedDict"),
    ("joblib.numpy_pickle", "NumpyArrayWrapper"),
    ("numpy", "dtype"), ("numpy", "ndarray"),
    ("numpy.core.multiarray", "scalar"), ("numpy._core.multiarray", "scalar"),
    ("sklearn.pipeline", "Pipeline"),
    ("sklearn.preprocessing._data", "StandardScaler"),
    ("sklearn.linear_model._logistic", "LogisticRegression"),
    ("sklearn.ensemble._forest", "RandomForestClassifier"),
    ("sklearn.tree._classes", "DecisionTreeClassifier"),
    ("sklearn.tree._tree", "Tree"),
    ("xgboost.sklearn", "XGBClassifier"),
    ("xgboost.core", "Booster"),
})

_LOAD_LOCK = threading.Lock()
_IDENTIFIER = re.compile(r"[A-Za-z_][A-Za-z0-9_.]{0,79}")


class UnsafeModelFileError(Exception):
    """File model berisi objek yang tidak ada di allowlist."""


class ModelUnavailableError(Exception):
    """Tidak ada model aktif yang dapat dipakai untuk prediksi."""


def safe_joblib_load(path: Path) -> tuple[Any, list[str]]:
    """Muat file joblib dengan menolak objek di luar ALLOWED_GLOBALS.

    Mengembalikan (objek, daftar peringatan library). Penggantian find_class bersifat
    sementara dan dilindungi lock.
    """
    cls = _numpy_pickle.NumpyUnpickler
    has_own = "find_class" in vars(cls)
    original = cls.find_class

    def restricted(self, module, name):
        if (module, name) not in ALLOWED_GLOBALS:
            # Teks dari file yang tidak tepercaya hanya ditampilkan jika berbentuk nama Python yang wajar.
            if isinstance(module, str) and isinstance(name, str) and _IDENTIFIER.fullmatch(module) and _IDENTIFIER.fullmatch(name):
                raise UnsafeModelFileError(f"Objek tidak diizinkan di dalam file model: {module}.{name}")
            raise UnsafeModelFileError("File model rusak atau tidak dapat dibaca.")
        return original(self, module, name)

    with _LOAD_LOCK:
        cls.find_class = restricted
        try:
            with warnings.catch_warnings(record=True) as caught:
                warnings.simplefilter("always")
                obj = joblib.load(path)
        finally:
            if has_own:
                cls.find_class = original
            else:
                del cls.find_class
    return obj, [str(w.message) for w in caught]


def _sha256_stream(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as handle:
        for chunk in iter(lambda: handle.read(1024 * 1024), b""):
            digest.update(chunk)
    return digest.hexdigest()


def extractor_hashes() -> set[str]:
    """Hash url_features.py saat ini, juga versi dengan akhir baris LF (Windows vs Linux)."""
    raw = Path(uf.__file__).read_bytes()
    return {hashlib.sha256(raw).hexdigest(), hashlib.sha256(raw.replace(b"\r\n", b"\n")).hexdigest()}


@dataclass
class ValidationReport:
    checks: list[dict] = field(default_factory=list)
    warnings: list[str] = field(default_factory=list)

    def add(self, name: str, ok: bool, detail: str = "") -> bool:
        self.checks.append({"name": name, "ok": bool(ok), "detail": detail})
        return bool(ok)

    @property
    def ok(self) -> bool:
        return bool(self.checks) and all(c["ok"] for c in self.checks)

    @property
    def errors(self) -> list[str]:
        return [c["detail"] or c["name"] for c in self.checks if not c["ok"]]

    def to_dict(self) -> dict:
        return {
            "valid": self.ok,
            "message": "Model kompatibel dengan sistem." if self.ok else "Model tidak kompatibel dengan sistem.",
            "errors": self.errors,
            "warnings": self.warnings,
            "checks": self.checks,
        }


@dataclass(frozen=True)
class LoadedModel:
    model: Any
    bundle_name: str
    name: str
    version: str
    algorithm: str
    features: tuple[str, ...]
    metadata: dict
    model_sha256: str
    extractor_version: str
    risk_thresholds: tuple[float, float]
    loaded_at: str
    model_id: int | None = None
    warnings: tuple[str, ...] = ()


def _is_number(value: Any) -> bool:
    return isinstance(value, (int, float)) and not isinstance(value, bool) and math.isfinite(value)


def _check_metadata(metadata: Any, names: Any) -> list[str]:
    """Kembalikan daftar masalah skema metadata (kosong jika valid)."""
    if not isinstance(metadata, dict):
        return ["metadata.json harus berupa objek JSON."]
    problems: list[str] = []
    for key in ("name", "version", "algorithm"):
        value = metadata.get(key)
        if not isinstance(value, str) or not value.strip() or len(value) > 100:
            problems.append(f"Field '{key}' wajib berupa teks tidak kosong (maksimal 100 karakter).")
    features = metadata.get("features")
    if not isinstance(features, list) or not features or not all(isinstance(f, str) for f in features):
        problems.append("Field 'features' wajib berupa daftar nama fitur.")
    count = metadata.get("feature_count")
    if not isinstance(count, int) or isinstance(count, bool):
        problems.append("Field 'feature_count' wajib berupa bilangan bulat.")
    sha = metadata.get("model_sha256")
    if not isinstance(sha, str) or not re.fullmatch(r"[0-9a-f]{64}", sha):
        problems.append("Field 'model_sha256' wajib berupa SHA-256 heksadesimal 64 karakter.")
    extractor = metadata.get("extractor")
    if not (isinstance(extractor, dict) and isinstance(extractor.get("version"), str)
            and isinstance(extractor.get("sha256"), str)):
        problems.append("Field 'extractor' wajib memuat 'version' dan 'sha256'.")
    metrics = metadata.get("metrics")
    needed = ("accuracy", "precision", "recall", "f1_score", "roc_auc")
    if not (isinstance(metrics, dict) and all(_is_number(metrics.get(k)) and 0 <= metrics[k] <= 1 for k in needed)):
        problems.append("Field 'metrics' wajib memuat accuracy, precision, recall, f1_score, roc_auc (0 sampai 1).")
    if not isinstance(names, list) or not all(isinstance(n, str) for n in names):
        problems.append("feature_names.json wajib berupa daftar nama fitur.")
    return problems


def _risk_thresholds(metadata: dict) -> tuple[float, float]:
    raw = metadata.get("risk_thresholds")
    if isinstance(raw, dict) and _is_number(raw.get("low")) and _is_number(raw.get("high")):
        low, high = float(raw["low"]), float(raw["high"])
        if 0 < low < high < 1:
            return low, high
    return DEFAULT_RISK_THRESHOLDS

def _library_differences(metadata: dict) -> list[str]:
    """Daftar library yang versinya berbeda dari saat training (untuk peringatan dan diagnosis)."""
    notes: list[str] = []
    for package in ("scikit-learn", "xgboost", "numpy", "joblib"):
        trained = (metadata.get("libraries") or {}).get(package)
        try:
            installed = importlib_metadata.version(package)
        except importlib_metadata.PackageNotFoundError:
            continue
        if trained and trained != installed:
            notes.append(f"{package}: training {trained}, terpasang {installed}")
    return notes

def build_indicators(features: dict[str, float], url: str) -> list[str]:
    """Indikator heuristik dari struktur URL. Bukan penjelasan langsung dari model."""
    found: list[str] = []
    if features["ip"]:
        found.append("Alamat host berupa IP, bukan nama domain.")
    if not features["is_https"]:
        found.append("URL tidak memakai HTTPS.")
    if features["nb_at"]:
        found.append("URL mengandung karakter '@'.")
    if features["punycode"]:
        found.append("Hostname memakai punycode (xn--), bisa dipakai untuk menyamarkan domain.")
    if features["shortening_service"]:
        found.append("URL memakai layanan pemendek tautan.")
    if features["tld_in_subdomain"]:
        found.append("Subdomain memuat ekstensi domain umum (mis. .com), pola penyamaran domain.")
    if features["abnormal_subdomain"]:
        found.append("Subdomain berbentuk tidak lazim (mis. ww2).")
    if features["nb_subdomains"] >= 3:
        found.append(f"Jumlah subdomain banyak ({int(features['nb_subdomains'])}).")
    if features["has_port"]:
        found.append("URL menyebut nomor port secara eksplisit.")
    if features["path_extension"]:
        found.append("Path berakhir dengan ekstensi berkas (.exe, .js, atau .txt).")
    if features["length_url"] > 75:
        found.append(f"URL sangat panjang ({int(features['length_url'])} karakter).")
    if features["ratio_digits_url"] >= 0.2:
        found.append("Proporsi angka pada URL tinggi.")
    lower = url.lower()
    keywords = [word for word in uf.PHISH_HINTS if word in lower]
    if keywords:
        found.append("Mengandung kata kunci yang sering dipakai phishing: " + ", ".join(keywords) + ".")
    if not found:
        found.append("Tidak ditemukan indikator mencurigakan pada struktur URL.")
    return found

def describe_model(loaded: LoadedModel, detail: bool = False) -> dict:
    """Ringkasan model untuk respons API. detail=True menambah data yang dibutuhkan tabel models di Laravel."""
    low, high = loaded.risk_thresholds
    info = {
        "model_id": loaded.model_id,
        "bundle_name": loaded.bundle_name,
        "name": loaded.name,
        "version": loaded.version,
        "algorithm": loaded.algorithm,
        "feature_count": len(loaded.features),
        "extractor_version": loaded.extractor_version,
        "model_sha256": loaded.model_sha256,
        "loaded_at": loaded.loaded_at,
        "metrics": {k: float(v) for k, v in loaded.metadata["metrics"].items()},
        "risk_thresholds": {"low": low, "high": high},
        "warnings": list(loaded.warnings),
    }
    if detail:
        dataset = loaded.metadata.get("dataset")
        dataset_name = dataset.get("name") if isinstance(dataset, dict) else dataset
        info["dataset_name"] = dataset_name if isinstance(dataset_name, str) else None
        info["description"] = loaded.metadata.get("description") if isinstance(loaded.metadata.get("description"), str) else None
        info["feature_names"] = list(loaded.features)
    return info

class ModelManager:
    """Memuat, memvalidasi, dan menyimpan satu model aktif untuk prediksi."""

    def __init__(self, registry_dir: Path, max_model_bytes: int = 100 * 1024 * 1024):
        self.registry_dir = Path(registry_dir).resolve()
        self.max_model_bytes = max_model_bytes
        self._current: LoadedModel | None = None
        self._swap_lock = threading.Lock()
        self._predict_lock = threading.Lock()  # prediksi diserialkan agar aman untuk library apa pun

    @property
    def current(self) -> LoadedModel | None:
        return self._current

    # ------------------------------------------------------------------ validasi
    def _resolve_bundle(self, bundle_name: str, report: ValidationReport) -> Path | None:
        if not isinstance(bundle_name, str) or not BUNDLE_NAME_PATTERN.fullmatch(bundle_name):
            report.add("Nama bundle valid", False, "Nama bundle tidak valid.")
            return None
        path = (self.registry_dir / bundle_name).resolve()
        if path.parent != self.registry_dir:
            report.add("Bundle berada di dalam registry", False, "Bundle berada di luar folder registry.")
            return None
        if not path.is_dir():
            report.add("Folder bundle ditemukan", False, f"Folder bundle '{bundle_name}' tidak ditemukan di registry.")
            return None
        report.add("Folder bundle ditemukan", True)
        return path

    def validate_bundle(self, bundle_name: str, model_id: int | None = None) -> tuple[LoadedModel | None, ValidationReport]:
        """Validasi lengkap. Tidak mengubah model aktif. Tidak melempar error untuk bundle yang buruk."""
        report = ValidationReport()
        bundle_dir = self._resolve_bundle(bundle_name, report)
        if bundle_dir is None:
            return None, report
        try:
            return self._validate(bundle_dir, bundle_name, model_id, report), report
        except Exception:  # noqa: BLE001 - detail hanya ke log server, bukan ke pengguna
            logger.exception("Validasi bundle '%s' gagal secara tak terduga", bundle_name)
            report.add("Validasi selesai tanpa kesalahan internal", False,
                       "Terjadi kesalahan internal saat memvalidasi model.")
            return None, report

    def _validate(self, bundle_dir: Path, bundle_name: str, model_id: int | None,
                  report: ValidationReport) -> LoadedModel | None:
        # 1. Berkas lengkap dan ukuran wajar
        missing = [f for f in REQUIRED_FILES if not (bundle_dir / f).is_file()]
        if not report.add("Berkas bundle lengkap", not missing,
                          f"Berkas tidak ditemukan: {', '.join(missing)}." if missing else ""):
            return None
        model_path = bundle_dir / "model.pkl"
        too_big = model_path.stat().st_size > self.max_model_bytes or any(
            (bundle_dir / f).stat().st_size > MAX_JSON_BYTES for f in ("metadata.json", "feature_names.json"))
        if not report.add("Ukuran berkas wajar", not too_big, "Ukuran berkas melebihi batas yang diizinkan."):
            return None

        # 2. Metadata dapat dibaca dan skemanya benar
        try:
            metadata = json.loads((bundle_dir / "metadata.json").read_text(encoding="utf-8"))
            names = json.loads((bundle_dir / "feature_names.json").read_text(encoding="utf-8"))
        except (OSError, ValueError):
            report.add("Metadata dapat dibaca", False, "metadata.json atau feature_names.json bukan JSON yang valid.")
            return None
        problems = _check_metadata(metadata, names)
        if not report.add("Metadata valid", not problems, " ".join(problems)):
            return None

        # 3. Kompatibilitas fitur (paling penting)
        model_features, current_features = list(names), list(uf.FEATURE_NAMES)
        count_ok = len(model_features) == len(current_features)
        report.add("Jumlah fitur sesuai", count_ok,
                   "" if count_ok else f"Model membutuhkan {len(model_features)} fitur, "
                                       f"sedangkan feature extractor saat ini menghasilkan {len(current_features)} fitur.")
        if count_ok:
            names_ok = model_features == current_features
            diff = next((i for i, (a, b) in enumerate(zip(model_features, current_features)) if a != b), None)
            report.add("Nama dan urutan fitur sesuai", names_ok,
                       "" if names_ok else f"Nama atau urutan fitur tidak sama dengan feature extractor saat ini "
                                           f"(posisi {diff + 1}: model '{model_features[diff]}', extractor '{current_features[diff]}').")
        consistent = metadata["features"] == model_features and metadata["feature_count"] == len(model_features)
        report.add("metadata.json konsisten dengan feature_names.json", consistent,
                   "" if consistent else "Daftar fitur atau feature_count di metadata.json tidak sama dengan feature_names.json.")
        extractor = metadata["extractor"]
        version_ok = extractor["version"] == uf.EXTRACTOR_VERSION
        report.add("Versi feature extractor sesuai", version_ok,
                   "" if version_ok else f"Model dilatih dengan extractor versi {extractor['version']}, "
                                         f"sedangkan sistem memakai versi {uf.EXTRACTOR_VERSION}.")
        sha_ok = extractor["sha256"] in extractor_hashes()
        report.add("Hash feature extractor sesuai", sha_ok,
                   "" if sha_ok else "url_features.py berbeda dari yang dipakai saat training (hash tidak sama).")
        if not report.ok:
            return None

        # 4. Integritas file model
        actual_sha = _sha256_stream(model_path)
        if not report.add("Hash file model cocok", actual_sha == metadata["model_sha256"],
                          "SHA-256 model.pkl tidak sama dengan yang tercatat di metadata.json."):
            return None

        # 5. Pemuatan aman (perbedaan versi library dicatat dulu agar terlihat walau pemuatan gagal)
        version_notes = _library_differences(metadata)
        report.warnings.extend(f"Versi library berbeda: {note}" for note in version_notes)
        try:
            model, load_warnings = safe_joblib_load(model_path)
        except UnsafeModelFileError as exc:
            report.add("File model aman dimuat", False, str(exc))
            return None
        except Exception:  # noqa: BLE001
            logger.exception("Gagal memuat %s", model_path)
            detail = "File model rusak atau tidak dapat dibaca."
            if version_notes:
                detail += " Kemungkinan penyebab: versi library berbeda dari saat training (" + "; ".join(version_notes) + ")."
            report.add("File model dapat dimuat", False, detail)
            return None

        # 6. Antarmuka model
        has_proba = callable(getattr(model, "predict_proba", None))
        classes = getattr(model, "classes_", None)
        classes_ok = classes is not None and [int(c) for c in classes] == [0, 1]
        n_in = getattr(model, "n_features_in_", len(model_features))
        report.add("Model punya predict_proba", has_proba, "Model tidak memiliki method predict_proba.")
        report.add("Kelas model [0, 1]", classes_ok, "Kelas model harus [0, 1] dengan 1 = phishing.")
        report.add("Jumlah fitur model sesuai", n_in == len(model_features),
                   f"Model dilatih dengan {n_in} fitur, sedangkan daftar fitur berisi {len(model_features)}.")
        if not report.ok:
            return None

        # 7. Dummy prediction dan prediksi URL contoh
        try:
            samples = [np.zeros((1, len(model_features))),
                       np.array([uf.extract_feature_vector("https://www.example.com/path?a=1")])]
            for sample in samples:
                proba = np.asarray(model.predict_proba(sample), dtype=float)
                valid = proba.shape == (1, 2) and np.isfinite(proba).all() and ((proba >= 0) & (proba <= 1)).all()
                if not valid:
                    raise ValueError("keluaran predict_proba tidak valid")
        except Exception:  # noqa: BLE001
            logger.exception("Dummy prediction gagal untuk %s", bundle_dir)
            report.add("Dummy prediction berhasil", False, "Model gagal melakukan prediksi uji.")
            return None
        report.add("Dummy prediction berhasil", True)

        return LoadedModel(
            model=model, bundle_name=bundle_name, name=metadata["name"], version=metadata["version"],
            algorithm=metadata["algorithm"], features=tuple(model_features), metadata=metadata,
            model_sha256=actual_sha, extractor_version=extractor["version"],
            risk_thresholds=_risk_thresholds(metadata),
            loaded_at=datetime.now(timezone.utc).isoformat(timespec="seconds"),
            model_id=model_id, warnings=tuple(report.warnings),
        )

    # ------------------------------------------------------------------ aktivasi
    def activate_bundle(self, bundle_name: str, model_id: int | None = None) -> ValidationReport:
        """Validasi lalu jadikan model aktif. Jika gagal, model lama tetap dipakai."""
        loaded, report = self.validate_bundle(bundle_name, model_id)
        if loaded is None or not report.ok:
            logger.warning("Bundle '%s' ditolak: %s", bundle_name, report.errors)
            return report
        with self._swap_lock:
            self._current = loaded
        logger.info("Model aktif: %s v%s (%s)", loaded.name, loaded.version, loaded.bundle_name)
        return report

    # ------------------------------------------------------------------ prediksi
    def predict(self, url: str) -> dict:
        loaded = self._current  # satu rujukan, aman terhadap pergantian model di tengah permintaan
        if loaded is None:
            raise ModelUnavailableError("Belum ada model aktif.")
        try:
            features = uf.extract_url_features(url)
            vector = np.array([[features[name] for name in loaded.features]], dtype=float)
            with self._predict_lock:
                p_phishing = float(loaded.model.predict_proba(vector)[0][1])
            if not math.isfinite(p_phishing) or not 0.0 <= p_phishing <= 1.0:
                raise ValueError("probabilitas tidak valid")
        except Exception as exc:  # noqa: BLE001
            logger.exception("Prediksi gagal")
            raise ModelUnavailableError("Prediksi gagal.") from exc

        is_phishing = p_phishing >= 0.5
        low, high = loaded.risk_thresholds
        prediction = "Phishing" if is_phishing else "Legitimate"
        return {
            "url": url,
            "prediction": prediction,
            "probability": round(p_phishing if is_phishing else 1.0 - p_phishing, 4),
            "risk_score": int(round(p_phishing * 100)),
            "risk_level": "High" if p_phishing >= high else "Medium" if p_phishing >= low else "Low",
            "model_id": loaded.model_id,
            "model_name": loaded.name,
            "model_version": loaded.version,
            "message": f"URL terdeteksi sebagai {prediction} berdasarkan model.",
            "indicators": build_indicators(features, url),
        }