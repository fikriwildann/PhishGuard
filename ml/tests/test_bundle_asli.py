"""Uji bundle model ASLI Anda (hasil notebook). Dilewati otomatis jika bundle belum ada di ml/models.

Pilih bundle lain dengan variabel lingkungan REAL_BUNDLE, mis. (PowerShell):  $env:REAL_BUNDLE='xgboost_v1_1'
"""
import os
from pathlib import Path

import pytest
from dotenv import load_dotenv

import url_features as uf
from api.model_manager import ModelManager

ML_ROOT = Path(__file__).resolve().parents[1]
load_dotenv(ML_ROOT / ".env")
REGISTRY = ML_ROOT / "models"
BUNDLE = os.getenv("REAL_BUNDLE") or os.getenv("ACTIVE_MODEL_DIR") or "xgboost_v1_1"

pytestmark = pytest.mark.skipif(not (REGISTRY / BUNDLE).is_dir(), reason=f"bundle '{BUNDLE}' belum ada di ml/models")


@pytest.fixture(scope="module")
def manager():
    mgr = ModelManager(REGISTRY)
    report = mgr.activate_bundle(BUNDLE)
    assert report.ok, "Bundle ditolak:\n  - " + "\n  - ".join(report.errors)
    return mgr


def test_metadata_sesuai_extractor(manager):
    current = manager.current
    assert list(current.features) == uf.FEATURE_NAMES
    assert current.extractor_version == uf.EXTRACTOR_VERSION
    metrics = current.metadata["metrics"]
    for key in ("accuracy", "precision", "recall", "f1_score", "roc_auc"):
        assert 0 < metrics[key] <= 1


@pytest.mark.parametrize("url", [
    "https://www.wikipedia.org/",
    "https://www.google.com/search?q=cuaca",
    "http://paypal.com.secure-login.xyz/verify/account.exe",
    "http://192.168.0.1:8080/login.php",
    "https://bit.ly/3abcDEF",
])
def test_prediksi_lengkap_dan_konsisten(manager, url):
    result = manager.predict(url)
    assert result["url"] == url
    assert result["prediction"] in ("Legitimate", "Phishing")
    assert 0.5 <= result["probability"] <= 1
    assert 0 <= result["risk_score"] <= 100 and result["risk_level"] in ("Low", "Medium", "High")
    assert result["model_name"] == manager.current.name and result["model_version"] == manager.current.version
    assert result["indicators"]
    if result["prediction"] == "Phishing":
        assert result["risk_score"] >= 50
    else:
        assert result["risk_score"] <= 50
    assert manager.predict(url) == result      # deterministik