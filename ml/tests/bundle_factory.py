"""Pembuat bundle model sintetis untuk pengujian (tidak memakai dataset asli)."""
import hashlib, json, random, string
from pathlib import Path

import joblib
import numpy as np
from sklearn.ensemble import RandomForestClassifier
from sklearn.linear_model import LogisticRegression
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import StandardScaler
from xgboost import XGBClassifier

import url_features as uf


def _training_data(n=500, seed=1):
    rng = random.Random(seed)
    urls, y = [], []
    for i in range(n):
        ph = i % 2
        host = "".join(rng.choices(string.ascii_lowercase, k=8)) + (".xyz" if ph else ".com")
        path = "/" + "".join(rng.choices(string.ascii_lowercase, k=6)) + ("/login" if ph else "")
        urls.append(("http" if ph else "https") + "://" + host + path)
        y.append(ph)
    return np.array([uf.extract_feature_vector(u) for u in urls]), np.array(y)


def make_model(kind="xgb"):
    X, y = _training_data()
    models = {
        "xgb": XGBClassifier(n_estimators=30, eval_metric="logloss", tree_method="hist", random_state=0),
        "rf": RandomForestClassifier(n_estimators=20, random_state=0),
        "logreg": Pipeline([("scaler", StandardScaler()), ("clf", LogisticRegression(max_iter=2000))]),
    }
    return models[kind].fit(X, y)


def sha256_file(path):
    return hashlib.sha256(Path(path).read_bytes()).hexdigest()


def write_bundle(registry, name, model=None, kind="xgb", features=None, metadata_overrides=None, version="1.0"):
    registry = Path(registry)
    bundle = registry / name
    bundle.mkdir(parents=True, exist_ok=True)
    model = model if model is not None else make_model(kind)
    joblib.dump(model, bundle / "model.pkl")
    features = list(features) if features is not None else list(uf.FEATURE_NAMES)
    metadata = {
        "name": "PhishGuard Test", "version": version, "algorithm": kind,
        "features": features, "feature_count": len(features),
        "extractor": {"version": uf.EXTRACTOR_VERSION, "sha256": hashlib.sha256(Path(uf.__file__).read_bytes()).hexdigest()},
        "metrics": {"accuracy": 0.9, "precision": 0.9, "recall": 0.9, "f1_score": 0.9, "roc_auc": 0.95, "brier_score": 0.07},
        "model_sha256": sha256_file(bundle / "model.pkl"),
        "libraries": {},
    }
    metadata.update(metadata_overrides or {})
    (bundle / "metadata.json").write_text(json.dumps(metadata), encoding="utf-8")
    (bundle / "feature_names.json").write_text(json.dumps(features), encoding="utf-8")
    return bundle