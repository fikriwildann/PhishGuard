"""Endpoint internal PhishGuard (PHASE 14): hanya untuk Laravel di mesin yang sama.

- /internal/validate-model : memeriksa bundle tanpa mengubah model aktif.
- /internal/reload-model   : memvalidasi lalu mengaktifkan bundle (model lama bertahan jika gagal).
- Saat startup, model aktif ditarik dari Laravel (GET /api/internal/active-model).
Semua endpoint /internal/* wajib header X-Internal-Token dan tidak muncul di /docs.
"""
from __future__ import annotations

import asyncio
import hmac
import logging
import threading
import time
from collections import defaultdict, deque
from contextlib import contextmanager
from dataclasses import dataclass, field

import httpx
from fastapi import APIRouter, Depends, HTTPException, Request
from fastapi.responses import JSONResponse
from pydantic import BaseModel, Field, field_validator

from api.model_manager import BUNDLE_NAME_PATTERN, ModelManager, describe_model

logger = logging.getLogger("phishguard.internal")

MIN_TOKEN_LENGTH = 32
PLACEHOLDER_MARKERS = ("ganti_dengan", "token_yang", "changeme", "contoh", "example", "secret")
MODEL_OPERATION_TIMEOUT = 30  # detik menunggu operasi model lain selesai


def token_problem(token: str | None) -> str | None:
    """Kembalikan alasan jika token tidak layak dipakai, atau None jika layak."""
    if not token:
        return "ML_INTERNAL_TOKEN belum diisi."
    if len(token) < MIN_TOKEN_LENGTH:
        return f"ML_INTERNAL_TOKEN terlalu pendek (minimal {MIN_TOKEN_LENGTH} karakter)."
    if len(set(token)) < 8 or any(marker in token.lower() for marker in PLACEHOLDER_MARKERS):
        return "ML_INTERNAL_TOKEN masih berupa teks contoh atau terlalu sederhana."
    return None


class FailedAuthLimiter:
    """Membatasi percobaan token yang salah per alamat klien (jendela waktu geser)."""

    def __init__(self, max_failures: int = 5, window_seconds: float = 60.0):
        self.max_failures = max_failures
        self.window = window_seconds
        self._failures: dict[str, deque] = defaultdict(deque)
        self._lock = threading.Lock()

    def _purge(self, key: str, now: float) -> deque:
        queue = self._failures[key]
        while queue and now - queue[0] > self.window:
            queue.popleft()
        return queue

    def blocked(self, key: str) -> bool:
        with self._lock:
            return len(self._purge(key, time.monotonic())) >= self.max_failures

    def record(self, key: str) -> None:
        with self._lock:
            now = time.monotonic()
            self._purge(key, now).append(now)

    def reset(self, key: str) -> None:
        with self._lock:
            self._failures.pop(key, None)


@dataclass
class InternalConfig:
    token: str = ""
    laravel_url: str | None = None
    bootstrap_bundle: str | None = None
    bootstrap_model_id: int | None = None
    retry_seconds: float = 15.0
    limiter: FailedAuthLimiter = field(default_factory=FailedAuthLimiter)
    pull_failures: int = 0

    @property
    def problem(self) -> str | None:
        return token_problem(self.token)


# ----------------------------------------------------------------------------- autentikasi
def require_internal_token(request: Request) -> None:
    config: InternalConfig = request.app.state.internal
    if config.problem:
        raise HTTPException(status_code=503, detail="Endpoint internal belum dikonfigurasi.")
    client = request.client.host if request.client else "tidak-diketahui"
    if config.limiter.blocked(client):
        raise HTTPException(status_code=429, detail="Terlalu banyak percobaan. Coba lagi nanti.",
                            headers={"Retry-After": str(int(config.limiter.window))})
    supplied = request.headers.get("X-Internal-Token", "")
    if not hmac.compare_digest(supplied.encode("utf-8"), config.token.encode("utf-8")):
        config.limiter.record(client)
        raise HTTPException(status_code=401, detail="Token internal tidak valid.")
    config.limiter.reset(client)


@contextmanager
def model_operation(app):
    """Hanya satu operasi model (muat/validasi/aktifkan) berjalan pada satu waktu."""
    lock: threading.Lock = app.state.model_op_lock
    if not lock.acquire(timeout=MODEL_OPERATION_TIMEOUT):
        raise HTTPException(status_code=409, detail="Operasi model lain sedang berjalan.")
    try:
        yield
    finally:
        lock.release()


# ----------------------------------------------------------------------------- endpoint
class BundleRequest(BaseModel):
    bundle_name: str = Field(..., max_length=100)
    model_id: int | None = Field(default=None, ge=1)

    @field_validator("bundle_name")
    @classmethod
    def validasi_nama(cls, value: str) -> str:
        if not BUNDLE_NAME_PATTERN.fullmatch(value):
            raise ValueError("Nama bundle tidak valid.")
        return value


router = APIRouter(prefix="/internal", dependencies=[Depends(require_internal_token)], include_in_schema=False)


@router.post("/validate-model")
def validate_model(payload: BundleRequest, request: Request):
    manager: ModelManager = request.app.state.manager
    with model_operation(request.app):
        loaded, report = manager.validate_bundle(payload.bundle_name, payload.model_id)
    body = report.to_dict()
    body["model"] = describe_model(loaded, detail=True) if (loaded is not None and report.ok) else None
    return JSONResponse(status_code=200, content=body)


@router.post("/reload-model")
def reload_model(payload: BundleRequest, request: Request):
    manager: ModelManager = request.app.state.manager
    with model_operation(request.app):
        before = manager.current
        report = manager.activate_bundle(payload.bundle_name, payload.model_id)
        current = manager.current
    if not report.ok:
        body = report.to_dict()
        body["active_model"] = describe_model(before) if before else None
        return JSONResponse(status_code=422, content=body)
    return {
        "detail": "Model aktif diperbarui.",
        "model": describe_model(current),
        "previous": describe_model(before) if before else None,
        "warnings": report.warnings,
    }


# ----------------------------------------------------------------------------- tarik dari Laravel
class LaravelUnavailable(Exception):
    """Laravel tidak dapat dihubungi atau jawabannya tidak sesuai kontrak."""


def fetch_active_model(base_url: str, token: str, timeout: float = 3.0) -> dict:
    """GET {base}/api/internal/active-model -> {"bundle_name": str | null, "model_id": int | null}."""
    url = base_url.rstrip("/") + "/api/internal/active-model"
    try:
        with httpx.Client(timeout=timeout, follow_redirects=False) as client:
            response = client.get(url, headers={"X-Internal-Token": token, "Accept": "application/json"})
        if response.status_code != 200:
            raise LaravelUnavailable(f"Laravel menjawab HTTP {response.status_code}.")
        data = response.json()
    except LaravelUnavailable:
        raise
    except (httpx.HTTPError, ValueError) as exc:
        raise LaravelUnavailable(f"Gagal menghubungi Laravel: {type(exc).__name__}.") from exc

    if not isinstance(data, dict) or "bundle_name" not in data:
        raise LaravelUnavailable("Jawaban Laravel tidak sesuai kontrak (bentuk JSON).")
    bundle = data.get("bundle_name")
    model_id = data.get("model_id")
    if bundle is not None and not (isinstance(bundle, str) and BUNDLE_NAME_PATTERN.fullmatch(bundle)):
        raise LaravelUnavailable("Jawaban Laravel tidak sesuai kontrak (bundle_name).")
    if model_id is not None and not (isinstance(model_id, int) and not isinstance(model_id, bool) and model_id >= 1):
        raise LaravelUnavailable("Jawaban Laravel tidak sesuai kontrak (model_id).")
    return {"bundle_name": bundle, "model_id": model_id}


def sync_active_model(app) -> str:
    """Tentukan model aktif saat startup. Hasil: 'laravel', 'bootstrap', 'aktif', 'gagal', atau 'menunggu'.

    Jika Laravel dapat dihubungi dan menyebut model aktif, Laravel dipercaya sepenuhnya: jika model itu
    gagal divalidasi, tidak ada model pengganti (agar scan tidak tercatat pada model yang salah).
    ACTIVE_MODEL_DIR hanya dipakai jika Laravel tidak dapat dihubungi atau belum punya model aktif.
    """
    config: InternalConfig = app.state.internal
    manager: ModelManager = app.state.manager
    with model_operation(app):
        if manager.current is not None:
            return "aktif"
        if config.laravel_url and config.problem is None:
            try:
                info = fetch_active_model(config.laravel_url, config.token)
            except LaravelUnavailable as exc:
                config.pull_failures += 1
                if config.pull_failures == 1 or config.pull_failures % 20 == 0:
                    logger.warning("Model aktif belum bisa ditarik dari Laravel (percobaan ke-%d): %s",
                                   config.pull_failures, exc)
            else:
                if info["bundle_name"]:
                    report = manager.activate_bundle(info["bundle_name"], info["model_id"])
                    if report.ok:
                        return "laravel"
                    logger.error("Model aktif dari Laravel '%s' gagal divalidasi: %s", info["bundle_name"], report.errors)
                    return "gagal"
        if config.bootstrap_bundle:
            report = manager.activate_bundle(config.bootstrap_bundle, config.bootstrap_model_id)
            if report.ok:
                return "bootstrap"
            logger.error("Model awal '%s' gagal dimuat: %s", config.bootstrap_bundle, report.errors)
            return "gagal"
    return "menunggu"


async def retry_until_model(app) -> None:
    """Ulangi penarikan model aktif sampai ada model atau terjadi kegagalan validasi."""
    interval = app.state.internal.retry_seconds
    attempt = 0
    while True:
        await asyncio.sleep(interval)
        attempt += 1
        status = await asyncio.to_thread(sync_active_model, app)
        if status != "menunggu":
            logger.info("Model aktif diperoleh setelah percobaan ke-%d (%s).", attempt, status)
            return
        if attempt % 20 == 0:
            logger.warning("Masih menunggu model aktif (percobaan ke-%d).", attempt)