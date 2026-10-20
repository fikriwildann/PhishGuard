"""PhishGuard API (FastAPI).

PHASE 13: GET /, GET /health, GET /model, POST /predict.
PHASE 14: POST /internal/validate-model, POST /internal/reload-model (lihat api/internal.py)
          dan penarikan model aktif dari Laravel saat startup.
"""
from __future__ import annotations

import asyncio
import logging
import os
import re
import threading
from contextlib import asynccontextmanager, suppress
from pathlib import Path
from typing import Literal
from urllib.parse import urlsplit

# Impor pertama ini mengatur sys.path ke ml/src, tempat url_features.py berada.
from api.model_manager import ModelManager, ModelUnavailableError, describe_model

from dotenv import load_dotenv
from fastapi import FastAPI, Request
from fastapi.exceptions import RequestValidationError
from fastapi.responses import JSONResponse
from pydantic import BaseModel, Field, field_validator

from api.internal import InternalConfig, retry_until_model, router as internal_router, sync_active_model

ML_ROOT = Path(__file__).resolve().parents[1]
load_dotenv(ML_ROOT / ".env")
logging.basicConfig(level=os.getenv("LOG_LEVEL", "INFO"), format="%(asctime)s %(levelname)s %(name)s: %(message)s")
logger = logging.getLogger("phishguard.api")

API_VERSION = "1.1.0"
URL_PATTERN = re.compile(r"^https?://\S+$", re.IGNORECASE)


# ----------------------------------------------------------------------------- skema
class PredictRequest(BaseModel):
    url: str = Field(..., max_length=2048, description="URL lengkap dengan skema http:// atau https://")

    @field_validator("url")
    @classmethod
    def validasi_url(cls, value: str) -> str:
        value = value.strip()
        if not URL_PATTERN.fullmatch(value) or any(ord(ch) < 32 or ord(ch) == 127 for ch in value):
            raise ValueError("URL tidak valid.")
        try:
            host = urlsplit(value).hostname
        except ValueError as exc:
            raise ValueError("URL tidak valid.") from exc
        if not host:
            raise ValueError("URL tidak valid.")
        return value  # tidak dinormalisasi: fitur dihitung dari string persis seperti saat training


class PredictResponse(BaseModel):
    url: str
    prediction: Literal["Legitimate", "Phishing"]
    probability: float = Field(description="Keyakinan model pada kelas yang diprediksi")
    risk_score: int = Field(description="Probabilitas phishing x 100, dibulatkan (0-100)")
    risk_level: Literal["Low", "Medium", "High"]
    model_id: int | None
    model_name: str
    model_version: str
    message: str
    indicators: list[str] = Field(description="Indikator heuristik dari struktur URL, bukan penjelasan langsung model")


class HealthResponse(BaseModel):
    status: Literal["ok", "degraded"]
    model_loaded: bool
    model_name: str | None = None
    model_version: str | None = None


class ModelInfoResponse(BaseModel):
    model_id: int | None
    bundle_name: str
    name: str
    version: str
    algorithm: str
    feature_count: int
    extractor_version: str
    model_sha256: str
    loaded_at: str
    metrics: dict[str, float]
    risk_thresholds: dict[str, float]
    warnings: list[str]


# ----------------------------------------------------------------------------- konfigurasi
def _env_path(name: str, default: str) -> Path:
    value = Path(os.getenv(name, default))
    return (value if value.is_absolute() else ML_ROOT / value).resolve()


def _env_int(name: str) -> int | None:
    raw = os.getenv(name, "").strip()
    return int(raw) if raw.isdigit() else None


def _env_float(name: str, default: float) -> float:
    try:
        return float(os.getenv(name, str(default)))
    except ValueError:
        return default


# ----------------------------------------------------------------------------- aplikasi
def create_app(
    manager: ModelManager | None = None,
    active_bundle: str | None = None,
    internal_token: str | None = None,
    laravel_url: str | None = None,
    retry_seconds: float | None = None,
) -> FastAPI:
    """Pabrik aplikasi. Jika `manager` tidak diberikan (produksi), semua nilai dibaca dari .env."""
    enable_docs = os.getenv("ENABLE_DOCS", "true").lower() == "true"
    bootstrap_model_id = None
    if manager is None:
        manager = ModelManager(_env_path("MODEL_REGISTRY_DIR", "models"))
        active_bundle = active_bundle or os.getenv("ACTIVE_MODEL_DIR", "").strip() or None
        bootstrap_model_id = _env_int("ACTIVE_MODEL_ID")
        if internal_token is None:
            internal_token = os.getenv("ML_INTERNAL_TOKEN", "").strip()
        if laravel_url is None:
            laravel_url = os.getenv("LARAVEL_BASE_URL", "").strip() or None
    config = InternalConfig(
        token=internal_token or "",
        laravel_url=laravel_url,
        bootstrap_bundle=active_bundle,
        bootstrap_model_id=bootstrap_model_id,
        retry_seconds=retry_seconds if retry_seconds is not None else _env_float("STARTUP_PULL_RETRY_SECONDS", 15.0),
    )

    @asynccontextmanager
    async def lifespan(app: FastAPI):
        if config.problem:
            logger.warning("Endpoint internal dinonaktifkan: %s", config.problem)
        status = await asyncio.to_thread(sync_active_model, app)
        if status in ("laravel", "bootstrap", "aktif"):
            logger.info("Model aktif diperoleh (%s).", status)
        elif status == "menunggu":
            logger.warning("Belum ada model aktif; /predict menjawab 503 sampai model aktif tersedia.")
        retry_task = None
        if status == "menunggu" and config.laravel_url and not config.problem:
            retry_task = asyncio.create_task(retry_until_model(app))
        try:
            yield
        finally:
            if retry_task is not None:
                retry_task.cancel()
                with suppress(asyncio.CancelledError):
                    await retry_task

    app = FastAPI(
        title="PhishGuard API",
        version=API_VERSION,
        description="Layanan klasifikasi URL phishing berbasis Machine Learning.",
        lifespan=lifespan,
        docs_url="/docs" if enable_docs else None,
        redoc_url=None,
        openapi_url="/openapi.json" if enable_docs else None,
    )
    app.state.manager = manager
    app.state.internal = config
    app.state.model_op_lock = threading.Lock()

    @app.exception_handler(RequestValidationError)
    async def handle_validation(request: Request, exc: RequestValidationError):
        detail = "URL tidak valid." if request.url.path == "/predict" else "Permintaan tidak valid."
        return JSONResponse(status_code=422, content={"detail": detail})

    @app.exception_handler(ModelUnavailableError)
    async def handle_model_unavailable(request: Request, exc: ModelUnavailableError):
        return JSONResponse(status_code=503, content={"detail": "Model deteksi tidak dapat digunakan."})

    @app.exception_handler(Exception)
    async def handle_unexpected(request: Request, exc: Exception):
        logger.exception("Kesalahan tak terduga pada %s", request.url.path)
        return JSONResponse(status_code=500, content={"detail": "Terjadi kesalahan pada layanan deteksi."})

    @app.get("/", tags=["Info"])
    def root():
        return {"service": "PhishGuard API", "version": API_VERSION, "status": "running"}

    @app.get("/health", response_model=HealthResponse, tags=["Info"])
    def health(request: Request):
        current = request.app.state.manager.current
        return HealthResponse(
            status="ok" if current else "degraded",
            model_loaded=current is not None,
            model_name=current.name if current else None,
            model_version=current.version if current else None,
        )

    @app.get("/model", response_model=ModelInfoResponse, tags=["Model"])
    def model_info(request: Request):
        current = request.app.state.manager.current
        if current is None:
            raise ModelUnavailableError("Belum ada model aktif.")
        return ModelInfoResponse(**describe_model(current))

    @app.post("/predict", response_model=PredictResponse, tags=["Prediksi"])
    def predict(payload: PredictRequest, request: Request):
        return request.app.state.manager.predict(payload.url)

    app.include_router(internal_router)
    return app


app = create_app()