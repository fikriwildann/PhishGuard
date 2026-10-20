import json, threading, time
from http.server import BaseHTTPRequestHandler, HTTPServer

import pytest
from fastapi.testclient import TestClient

from api import internal
from api.internal import FailedAuthLimiter, fetch_active_model, LaravelUnavailable, token_problem
from api.main import create_app
from api.model_manager import ModelManager
from tests.bundle_factory import write_bundle

TOKEN = "a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4"
H = {"X-Internal-Token": TOKEN}


@pytest.fixture()
def registry(tmp_path):
    write_bundle(tmp_path, "xgboost_v1_0", kind="xgb", version="1.0")
    write_bundle(tmp_path, "xgboost_v1_1", kind="xgb", version="1.1")
    write_bundle(tmp_path, "randomforest_v1_0", kind="rf", version="1.0")
    write_bundle(tmp_path, "bad_v1_0", features=["a", "b", "c"])
    return tmp_path


def make_client(registry, **kwargs):
    kwargs.setdefault("internal_token", TOKEN)
    app = create_app(ModelManager(registry), **kwargs)
    return TestClient(app, raise_server_exceptions=False), app


# ------------------------------------------------------------------ token
@pytest.mark.parametrize("token,expected", [
    ("", True), (None, True), ("pendek", True), ("a" * 40, True), ("ganti_dengan_token_acak_yang_panjang_sekali_123", True),
    ("token_yang_sudah_Anda_buat_ABCDEFGHIJKLMNOP", True), (TOKEN, False),
])
def test_token_problem(token, expected):
    assert (token_problem(token) is not None) is expected


def test_internal_nonaktif_jika_token_lemah(registry):
    client, _ = make_client(registry, internal_token="pendek")
    with client as c:
        r = c.post("/internal/reload-model", json={"bundle_name": "xgboost_v1_0"}, headers={"X-Internal-Token": "pendek"})
        assert r.status_code == 503 and "belum dikonfigurasi" in r.json()["detail"]
        assert c.get("/health").status_code == 200   # layanan publik tetap hidup


def test_tanpa_token_atau_salah_ditolak_dan_model_tidak_berubah(registry):
    client, app = make_client(registry, active_bundle="xgboost_v1_0")
    with client as c:
        for headers in ({}, {"X-Internal-Token": "salah"}, {"X-Internal-Token": TOKEN[:-1]}, {"X-Internal-Token": TOKEN + "x"}):
            r = c.post("/internal/reload-model", json={"bundle_name": "xgboost_v1_1"}, headers=headers)
            assert r.status_code == 401 and r.json() == {"detail": "Token internal tidak valid."}
        assert app.state.manager.current.version == "1.0"
        # autentikasi diperiksa sebelum isi permintaan
        assert c.post("/internal/reload-model", json={"bukan": "valid"}).status_code == 401


def test_pembatasan_percobaan_token_salah(registry):
    client, app = make_client(registry)
    app.state.internal.limiter = FailedAuthLimiter(max_failures=3, window_seconds=60)
    with client as c:
        for _ in range(3):
            assert c.post("/internal/validate-model", json={"bundle_name": "xgboost_v1_0"}, headers={"X-Internal-Token": "x"}).status_code == 401
        r = c.post("/internal/validate-model", json={"bundle_name": "xgboost_v1_0"}, headers=H)
        assert r.status_code == 429 and "Retry-After" in r.headers


def test_limiter_jendela_waktu():
    lim = FailedAuthLimiter(max_failures=2, window_seconds=0.2)
    lim.record("a"); lim.record("a")
    assert lim.blocked("a") and not lim.blocked("b")
    time.sleep(0.25)
    assert not lim.blocked("a")
    lim.record("a"); lim.reset("a")
    assert not lim.blocked("a")


def test_endpoint_internal_tidak_ada_di_dokumentasi(registry):
    client, _ = make_client(registry)
    with client as c:
        assert "/internal" not in json.dumps(c.get("/openapi.json").json())


# ------------------------------------------------------------------ validate-model
def test_validate_model_sah_tidak_mengubah_model_aktif(registry):
    client, app = make_client(registry, active_bundle="xgboost_v1_0")
    with client as c:
        r = c.post("/internal/validate-model", json={"bundle_name": "randomforest_v1_0", "model_id": 7}, headers=H)
        assert r.status_code == 200
        body = r.json()
        assert body["valid"] is True and body["message"] == "Model kompatibel dengan sistem." and body["errors"] == []
        m = body["model"]
        assert m["version"] == "1.0" and m["feature_count"] == 49 and len(m["feature_names"]) == 49
        assert set(m["metrics"]) >= {"accuracy", "precision", "recall", "f1_score", "roc_auc"} and "dataset_name" in m
        assert len(m["model_sha256"]) == 64
        assert app.state.manager.current.bundle_name == "xgboost_v1_0"   # tidak berubah


def test_validate_model_tidak_sah(registry):
    client, app = make_client(registry, active_bundle="xgboost_v1_0")
    with client as c:
        body = c.post("/internal/validate-model", json={"bundle_name": "bad_v1_0"}, headers=H).json()
        assert body["valid"] is False and body["model"] is None
        assert body["message"] == "Model tidak kompatibel dengan sistem."
        assert "Model membutuhkan 3 fitur, sedangkan feature extractor saat ini menghasilkan 49 fitur." in body["errors"]
        body = c.post("/internal/validate-model", json={"bundle_name": "tidak_ada"}, headers=H).json()
        assert body["valid"] is False
        assert app.state.manager.current.bundle_name == "xgboost_v1_0"


@pytest.mark.parametrize("nama", ["../xgboost_v1_0", "a/b", "a\\b", "", ".x", "x" * 101, "C:\\x"])
def test_nama_bundle_berbahaya_ditolak_validasi(registry, nama):
    client, _ = make_client(registry)
    with client as c:
        r = c.post("/internal/reload-model", json={"bundle_name": nama}, headers=H)
        assert r.status_code == 422 and r.json() == {"detail": "Permintaan tidak valid."}


# ------------------------------------------------------------------ reload-model
def test_reload_berhasil_dan_prediksi_memakai_model_baru(registry):
    client, app = make_client(registry, active_bundle="xgboost_v1_0")
    with client as c:
        before = c.post("/predict", json={"url": "https://www.example.com/a"}).json()
        assert before["model_version"] == "1.0" and before["model_id"] is None
        r = c.post("/internal/reload-model", json={"bundle_name": "xgboost_v1_1", "model_id": 5}, headers=H)
        assert r.status_code == 200
        body = r.json()
        assert body["model"]["version"] == "1.1" and body["model"]["model_id"] == 5
        assert body["previous"]["version"] == "1.0"
        after = c.post("/predict", json={"url": "https://www.example.com/a"}).json()
        assert after["model_version"] == "1.1" and after["model_id"] == 5
        assert c.get("/model").json()["bundle_name"] == "xgboost_v1_1"
        assert c.get("/health").json()["model_version"] == "1.1"


def test_reload_gagal_model_lama_bertahan(registry):
    client, app = make_client(registry, active_bundle="xgboost_v1_0")
    with client as c:
        r = c.post("/internal/reload-model", json={"bundle_name": "bad_v1_0", "model_id": 9}, headers=H)
        assert r.status_code == 422
        body = r.json()
        assert body["valid"] is False and body["message"] == "Model tidak kompatibel dengan sistem."
        assert body["active_model"]["version"] == "1.0"
        after = c.post("/predict", json={"url": "https://example.com/a"}).json()
        assert after["model_version"] == "1.0"


def test_reload_ketika_belum_ada_model(registry):
    client, app = make_client(registry)
    with client as c:
        assert c.get("/health").json()["status"] == "degraded"
        r = c.post("/internal/reload-model", json={"bundle_name": "randomforest_v1_0", "model_id": 2}, headers=H)
        assert r.status_code == 200 and r.json()["previous"] is None
        assert c.get("/health").json()["status"] == "ok"


def test_reload_rollback_ke_versi_sebelumnya(registry):
    client, _ = make_client(registry, active_bundle="xgboost_v1_1")
    with client as c:
        assert c.post("/internal/reload-model", json={"bundle_name": "xgboost_v1_0", "model_id": 1}, headers=H).status_code == 200
        assert c.post("/predict", json={"url": "https://example.com/x"}).json()["model_version"] == "1.0"


def test_operasi_model_serentak_dikunci(registry, monkeypatch):
    monkeypatch.setattr(internal, "MODEL_OPERATION_TIMEOUT", 0.2)
    client, app = make_client(registry)
    with client as c:
        app.state.model_op_lock.acquire()
        try:
            r = c.post("/internal/reload-model", json={"bundle_name": "xgboost_v1_0"}, headers=H)
            assert r.status_code == 409
        finally:
            app.state.model_op_lock.release()


# ------------------------------------------------------------------ tarik dari Laravel (server palsu sungguhan)
class FakeLaravel:
    def __init__(self):
        self.mode = "ok"; self.payload = {"bundle_name": "xgboost_v1_1", "model_id": 3}; self.hits = 0; self.seen_tokens = []
        outer = self

        class Handler(BaseHTTPRequestHandler):
            def log_message(self, *a): pass
            def do_GET(self):
                outer.hits += 1; outer.seen_tokens.append(self.headers.get("X-Internal-Token"))
                if self.path != "/api/internal/active-model": self.send_response(404); self.end_headers(); return
                if outer.mode == "500": self.send_response(500); self.end_headers(); return
                if outer.mode == "redirect": self.send_response(302); self.send_header("Location", "http://127.0.0.1:1/"); self.end_headers(); return
                if outer.mode == "html": body = b"<html>bukan json</html>"
                else: body = json.dumps(outer.payload).encode()
                self.send_response(200); self.send_header("Content-Type", "application/json"); self.send_header("Content-Length", str(len(body)))
                self.end_headers(); self.wfile.write(body)

        self.server = HTTPServer(("127.0.0.1", 0), Handler)
        self.url = f"http://127.0.0.1:{self.server.server_port}"
        threading.Thread(target=self.server.serve_forever, daemon=True).start()

    def close(self): self.server.shutdown(); self.server.server_close()


@pytest.fixture()
def laravel():
    fake = FakeLaravel(); yield fake; fake.close()


def test_fetch_active_model_kontrak(laravel):
    assert fetch_active_model(laravel.url, TOKEN) == {"bundle_name": "xgboost_v1_1", "model_id": 3}
    assert laravel.seen_tokens[-1] == TOKEN
    laravel.payload = {"bundle_name": None, "model_id": None}
    assert fetch_active_model(laravel.url, TOKEN) == {"bundle_name": None, "model_id": None}
    for bad in ({"bundle_name": "../x", "model_id": 1}, {"bundle_name": "ok_v1", "model_id": "3"}, {"bundle_name": "ok_v1", "model_id": 0},
                {"bundle_name": 5}, [1, 2], "teks"):
        laravel.payload = bad
        with pytest.raises(LaravelUnavailable):
            fetch_active_model(laravel.url, TOKEN)
    for mode in ("500", "redirect", "html"):
        laravel.mode = mode
        with pytest.raises(LaravelUnavailable):
            fetch_active_model(laravel.url, TOKEN)
    with pytest.raises(LaravelUnavailable):
        fetch_active_model("http://127.0.0.1:1", TOKEN, timeout=0.5)


def test_startup_memakai_model_dari_laravel(registry, laravel):
    client, app = make_client(registry, laravel_url=laravel.url, active_bundle="randomforest_v1_0")
    with client as c:
        h = c.get("/health").json()
        assert h["status"] == "ok" and h["model_version"] == "1.1"       # dari Laravel, bukan bootstrap
        assert c.post("/predict", json={"url": "https://example.com/a"}).json()["model_id"] == 3


def test_laravel_menyebut_model_rusak_tidak_ada_pengganti(registry, laravel):
    laravel.payload = {"bundle_name": "bad_v1_0", "model_id": 4}
    client, _ = make_client(registry, laravel_url=laravel.url, active_bundle="randomforest_v1_0")
    with client as c:
        assert c.get("/health").json()["status"] == "degraded"
        assert c.post("/predict", json={"url": "https://example.com/a"}).status_code == 503


def test_laravel_mati_memakai_bootstrap(registry):
    client, _ = make_client(registry, laravel_url="http://127.0.0.1:1", active_bundle="xgboost_v1_0", retry_seconds=60)
    with client as c:
        assert c.get("/health").json()["model_version"] == "1.0"


def test_laravel_tanpa_model_aktif_memakai_bootstrap(registry, laravel):
    laravel.payload = {"bundle_name": None, "model_id": None}
    client, _ = make_client(registry, laravel_url=laravel.url, active_bundle="xgboost_v1_0")
    with client as c:
        assert c.get("/health").json()["model_version"] == "1.0"


def test_menunggu_lalu_laravel_hidup(registry, laravel):
    laravel.mode = "500"
    client, app = make_client(registry, laravel_url=laravel.url, retry_seconds=0.1)
    with client as c:
        assert c.get("/health").json()["status"] == "degraded"
        laravel.mode = "ok"
        for _ in range(40):
            if c.get("/health").json()["status"] == "ok": break
            time.sleep(0.1)
        assert c.get("/health").json()["status"] == "ok" and c.get("/model").json()["model_id"] == 3


def test_tanpa_laravel_tanpa_bootstrap_menunggu(registry):
    client, _ = make_client(registry)
    with client as c:
        assert c.get("/health").json()["status"] == "degraded"
        assert c.post("/predict", json={"url": "https://example.com/a"}).status_code == 503