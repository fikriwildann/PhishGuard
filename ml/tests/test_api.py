import json, os
from pathlib import Path

import joblib
import pytest
from fastapi.testclient import TestClient

from api.main import create_app
from api.model_manager import ModelManager, safe_joblib_load, UnsafeModelFileError
from tests.bundle_factory import make_model, write_bundle


@pytest.fixture(scope="module")
def registry(tmp_path_factory):
    reg = tmp_path_factory.mktemp("registry")
    write_bundle(reg, "xgboost_v1_0", kind="xgb")
    write_bundle(reg, "randomforest_v1_0", kind="rf", version="1.0")
    write_bundle(reg, "logreg_v1_0", kind="logreg")
    return reg


@pytest.fixture()
def client(registry):
    app = create_app(ModelManager(registry), active_bundle="xgboost_v1_0")
    with TestClient(app, raise_server_exceptions=False) as c:
        yield c


def test_root_health_model(client):
    assert client.get("/").json()["status"] == "running"
    h = client.get("/health").json()
    assert h == {"status": "ok", "model_loaded": True, "model_name": "PhishGuard Test", "model_version": "1.0"}
    m = client.get("/model").json()
    assert m["feature_count"] == 49 and m["bundle_name"] == "xgboost_v1_0" and "file_path" not in m
    assert set(m["metrics"]) >= {"accuracy", "f1_score", "roc_auc"}


def test_predict_shape_and_consistency(client):
    for url in ["https://www.example.com/path", "http://login-secure.xyz/login"]:
        r = client.post("/predict", json={"url": url})
        assert r.status_code == 200, r.text
        d = r.json()
        for key in ("url", "prediction", "probability", "risk_score", "risk_level", "model_name", "model_version", "message", "indicators"):
            assert key in d
        assert d["prediction"] in ("Legitimate", "Phishing") and 0.5 <= d["probability"] <= 1
        assert 0 <= d["risk_score"] <= 100 and d["risk_level"] in ("Low", "Medium", "High")
        assert d["message"] == f"URL terdeteksi sebagai {d['prediction']} berdasarkan model."
        assert d["url"] == url
    d1 = client.post("/predict", json={"url": "http://login-secure.xyz/login"}).json()
    d2 = client.post("/predict", json={"url": "http://login-secure.xyz/login"}).json()
    assert d1 == d2   # deterministik


def test_predict_url_tidak_dinormalisasi(client):
    url = "https://Example.COM:443/A b".replace(" ", "%20")
    assert client.post("/predict", json={"url": url}).json()["url"] == url


@pytest.mark.parametrize("payload", [
    {}, {"url": ""}, {"url": "   "}, {"url": "example.com"}, {"url": "ftp://example.com"},
    {"url": "http://"}, {"url": "http://exa mple.com"}, {"url": "http://a.com/\x00"}, {"url": 123},
    {"url": "http://" + "a" * 2100}, {"url": None},
])
def test_predict_url_tidak_valid(client, payload):
    r = client.post("/predict", json=payload)
    assert r.status_code == 422 and r.json() == {"detail": "URL tidak valid."}


def test_tanpa_model_503(registry):
    app = create_app(ModelManager(registry), active_bundle=None)
    with TestClient(app, raise_server_exceptions=False) as c:
        assert c.get("/health").json()["status"] == "degraded"
        r = c.post("/predict", json={"url": "https://example.com"})
        assert r.status_code == 503 and r.json() == {"detail": "Model deteksi tidak dapat digunakan."}
        assert c.get("/model").status_code == 503


def test_bundle_awal_rusak_api_tetap_hidup(registry):
    app = create_app(ModelManager(registry), active_bundle="tidak_ada")
    with TestClient(app, raise_server_exceptions=False) as c:
        assert c.get("/health").status_code == 200
        assert c.post("/predict", json={"url": "https://example.com"}).status_code == 503


def test_error_tak_terduga_tanpa_stack_trace(registry, monkeypatch):
    mgr = ModelManager(registry)
    app = create_app(mgr, active_bundle="xgboost_v1_0")
    with TestClient(app, raise_server_exceptions=False) as c:
        monkeypatch.setattr(mgr, "predict", lambda url: (_ for _ in ()).throw(RuntimeError("rahasia internal")))
        r = c.post("/predict", json={"url": "https://example.com"})
        assert r.status_code == 500 and "rahasia" not in r.text and "Traceback" not in r.text


def test_semua_jenis_model_valid(registry):
    mgr = ModelManager(registry)
    for name in ("xgboost_v1_0", "randomforest_v1_0", "logreg_v1_0"):
        report = mgr.activate_bundle(name)
        assert report.ok, report.errors
        assert mgr.predict("https://example.com/a")["model_version"] == "1.0"


def test_pickle_berbahaya_ditolak_dan_tidak_dieksekusi(tmp_path):
    marker = tmp_path / "pwned.txt"

    class Evil:
        def __reduce__(self):
            return (os.system, (f"echo hacked > {marker}",))

    bundle = write_bundle(tmp_path, "evil_v1_0", model=make_model("xgb"))
    joblib.dump(Evil(), bundle / "model.pkl")
    meta = json.loads((bundle / "metadata.json").read_text())
    from tests.bundle_factory import sha256_file
    meta["model_sha256"] = sha256_file(bundle / "model.pkl")      # hash sengaja dibuat cocok
    (bundle / "metadata.json").write_text(json.dumps(meta))
    mgr = ModelManager(tmp_path)
    report = mgr.activate_bundle("evil_v1_0")
    assert not report.ok and any("tidak diizinkan" in e for e in report.errors)
    assert not marker.exists() and mgr.current is None
    with pytest.raises(UnsafeModelFileError):
        safe_joblib_load(bundle / "model.pkl")
    # setelah penolakan, loader normal tetap bersih (patch dipulihkan) dan model sah tetap bisa dimuat
    from joblib import numpy_pickle
    assert "find_class" not in vars(numpy_pickle.NumpyUnpickler)


def test_gadget_eval_ditolak(tmp_path):
    class Evil:
        def __reduce__(self):
            return (eval, ("1+1",))
    joblib.dump(Evil(), tmp_path / "x.pkl")
    with pytest.raises(UnsafeModelFileError):
        safe_joblib_load(tmp_path / "x.pkl")


def test_hash_model_tidak_cocok(tmp_path):
    bundle = write_bundle(tmp_path, "m_v1_0")
    with open(bundle / "model.pkl", "ab") as f:
        f.write(b"x")
    mgr = ModelManager(tmp_path)
    r = mgr.activate_bundle("m_v1_0")
    assert not r.ok and any("SHA-256" in e for e in r.errors)


def test_jumlah_fitur_berbeda_pesan_sesuai_spesifikasi(tmp_path):
    write_bundle(tmp_path, "m_v1_0", features=[f"f{i}" for i in range(111)])
    r = ModelManager(tmp_path).activate_bundle("m_v1_0")
    assert not r.ok
    assert "Model membutuhkan 111 fitur, sedangkan feature extractor saat ini menghasilkan 49 fitur." in r.errors
    assert r.to_dict()["message"] == "Model tidak kompatibel dengan sistem."


def test_nama_fitur_berbeda(tmp_path):
    import url_features as uf
    names = list(uf.FEATURE_NAMES); names[2], names[3] = names[3], names[2]
    write_bundle(tmp_path, "m_v1_0", features=names)
    r = ModelManager(tmp_path).activate_bundle("m_v1_0")
    assert not r.ok and any("urutan fitur" in e for e in r.errors)


def test_versi_extractor_berbeda(tmp_path):
    import url_features as uf
    write_bundle(tmp_path, "m_v1_0", metadata_overrides={"extractor": {"version": "9.9", "sha256": "0" * 64}})
    r = ModelManager(tmp_path).activate_bundle("m_v1_0")
    assert not r.ok and any("extractor versi 9.9" in e for e in r.errors) and any("hash" in e for e in r.errors)


def test_berkas_hilang_dan_json_rusak(tmp_path):
    b = write_bundle(tmp_path, "a_v1_0"); (b / "feature_names.json").unlink()
    assert any("feature_names.json" in e for e in ModelManager(tmp_path).activate_bundle("a_v1_0").errors)
    b = write_bundle(tmp_path, "b_v1_0"); (b / "metadata.json").write_text("{bukan json")
    assert not ModelManager(tmp_path).activate_bundle("b_v1_0").ok
    b = write_bundle(tmp_path, "c_v1_0", metadata_overrides={"metrics": {"accuracy": 2}})
    assert any("metrics" in e for e in ModelManager(tmp_path).activate_bundle("c_v1_0").errors)


def test_file_model_rusak_atau_bukan_model(tmp_path):
    b = write_bundle(tmp_path, "r_v1_0")
    (b / "model.pkl").write_bytes(b"ini bukan model")
    meta = json.loads((b / "metadata.json").read_text())
    from tests.bundle_factory import sha256_file
    meta["model_sha256"] = sha256_file(b / "model.pkl"); (b / "metadata.json").write_text(json.dumps(meta))
    r = ModelManager(tmp_path).activate_bundle("r_v1_0")
    assert not r.ok and "File model rusak atau tidak dapat dibaca." in r.errors


def test_model_kelas_salah(tmp_path):
    from sklearn.linear_model import LogisticRegression
    import numpy as np
    X = np.random.RandomState(0).rand(60, 49); y = np.array([1, 2] * 30)
    # LogisticRegression ada di allowlist; kelas [1, 2] harus ditolak
    write_bundle(tmp_path, "k_v1_0", model=LogisticRegression().fit(X, y))
    r = ModelManager(tmp_path).activate_bundle("k_v1_0")
    assert not r.ok and any("Kelas model" in e for e in r.errors)


@pytest.mark.parametrize("nama", ["../xgboost_v1_0", "..", "a/b", "a\\b", "", ".hidden", "x" * 200, "C:\\Windows", None, 5])
def test_nama_bundle_berbahaya(registry, nama):
    r = ModelManager(registry).activate_bundle(nama)
    assert not r.ok


def test_model_lama_bertahan_jika_aktivasi_gagal(registry, tmp_path):
    import shutil
    reg = tmp_path / "reg"; shutil.copytree(registry, reg)
    mgr = ModelManager(reg)
    assert mgr.activate_bundle("xgboost_v1_0").ok
    before = mgr.current
    write_bundle(reg, "bad_v1_0", features=["a", "b"])
    assert not mgr.activate_bundle("bad_v1_0").ok
    assert mgr.current is before
    assert mgr.activate_bundle("randomforest_v1_0").ok and mgr.current.bundle_name == "randomforest_v1_0"


def test_ambang_risiko_dari_metadata(tmp_path):
    write_bundle(tmp_path, "t_v1_0", metadata_overrides={"risk_thresholds": {"low": 0.2, "high": 0.6}})
    write_bundle(tmp_path, "u_v1_0", metadata_overrides={"risk_thresholds": {"low": 0.9, "high": 0.1}})
    m = ModelManager(tmp_path)
    assert m.activate_bundle("t_v1_0").ok and m.current.risk_thresholds == (0.2, 0.6)
    assert m.activate_bundle("u_v1_0").ok and m.current.risk_thresholds == (0.30, 0.70)   # tidak valid -> default


def test_indikator():
    from api.model_manager import build_indicators
    import url_features as uf
    url = "http://192.168.0.1:8080/login.exe"
    ind = build_indicators(uf.extract_url_features(url), url)
    assert any("IP" in i for i in ind) and any("HTTPS" in i for i in ind) and any("port" in i for i in ind) and any("login" in i for i in ind)
    url = "https://www.example.com/"
    assert build_indicators(uf.extract_url_features(url), url) == ["Tidak ditemukan indikator mencurigakan pada struktur URL."]


def test_gagal_muat_menyebut_perbedaan_versi(tmp_path, monkeypatch):
    import api.model_manager as mm
    write_bundle(tmp_path, "v_v1_0", metadata_overrides={"libraries": {"xgboost": "0.0.1", "numpy": "2.1.3"}})
    monkeypatch.setattr(mm, "safe_joblib_load", lambda path: (_ for _ in ()).throw(RuntimeError("input stream corrupted")))
    r = ModelManager(tmp_path).activate_bundle("v_v1_0")
    assert not r.ok
    assert any("File model rusak" in e and "xgboost: training 0.0.1, terpasang" in e for e in r.errors)
    assert any("Versi library berbeda: xgboost" in w for w in r.warnings)


def test_peringatan_versi_tetap_muncul_saat_berhasil(tmp_path):
    write_bundle(tmp_path, "w_v1_0", metadata_overrides={"libraries": {"scikit-learn": "0.0.1"}})
    r = ModelManager(tmp_path).activate_bundle("w_v1_0")
    assert r.ok and any("scikit-learn: training 0.0.1" in w for w in r.warnings)


def test_model_xgboost_portabel(tmp_path):
    import numpy as np
    PortableXGBClassifier = pytest.importorskip("xgb_portable").PortableXGBClassifier
    from tests.bundle_factory import _training_data
    asli = make_model("xgb")
    portabel = PortableXGBClassifier.from_classifier(asli)
    X, _ = _training_data(n=100, seed=7)
    assert np.abs(portabel.predict_proba(X)[:, 1] - asli.predict_proba(X)[:, 1]).max() < 1e-9
    write_bundle(tmp_path, "portabel_v1_0", model=portabel)
    mgr = ModelManager(tmp_path)
    report = mgr.activate_bundle("portabel_v1_0")
    assert report.ok, report.errors
    assert mgr.predict("https://example.com/a")["prediction"] in ("Legitimate", "Phishing")