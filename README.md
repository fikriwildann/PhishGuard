<div align="center">

# PhishGuard

### Sistem Deteksi URL Phishing Berbasis Machine Learning

[![PHP](https://img.shields.io/badge/PHP-8.2+-8892BF?logo=php&logoColor=white)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![FastAPI](https://img.shields.io/badge/FastAPI-0.115+-009688?logo=fastapi&logoColor=white)](https://fastapi.tiangolo.com)
[![Python](https://img.shields.io/badge/Python-3.12+-3776AB?logo=python&logoColor=white)](https://python.org)
[![XGBoost](https://img.shields.io/badge/XGBoost-3.4.1-189FAD?logo=xgboost&logoColor=white)](https://xgboost.readthedocs.io)
[![MySQL](https://img.shields.io/badge/MySQL-8.x-4479A1?logo=mysql&logoColor=white)](https://mysql.com)
[![License](https://img.shields.io/badge/License-Academic-blueviolet)](LICENSE)

PhishGuard adalah sistem web terintegrasi untuk mendeteksi URL phishing secara real-time menggunakan model XGBoost terlatih. Sistem terdiri dari dua komponen utama: **portal web** berbasis Laravel 12 (antarmuka pengguna & admin) dan **mesin inferensi AI** berbasis FastAPI (layanan machine learning).

</div>

---

## 📋 Daftar Isi

- [Fitur Unggulan](#-fitur-unggulan)
- [Arsitektur Sistem](#-arsitektur-sistem)
- [Teknologi yang Digunakan](#-teknologi-yang-digunakan)
- [Struktur Direktori](#-struktur-direktori)
- [Prasyarat](#-prasyarat)
- [Panduan Instalasi Lengkap](#-panduan-instalasi-lengkap)
  - [1. Persiapan Database](#1-persiapan-database-mysql)
  - [2. Setup Laravel](#2-setup-laravel-web-portal)
  - [3. Setup FastAPI](#3-setup-fastapi-ml-engine)
  - [4. Menjalankan Sistem](#4-menjalankan-sistem)
- [Kredensial Default](#-kredensial-default)
- [Dokumentasi API](#-dokumentasi-api)
- [Pipeline ML & Fitur URL](#-pipeline-ml--fitur-url)
- [Manajemen Model ML](#-manajemen-model-ml)
- [Pengujian Otomatis](#-pengujian-otomatis)
- [Panduan Penggunaan](#-panduan-penggunaan)
- [Keamanan](#-keamanan)

---

## ✨ Fitur Unggulan

### Portal Pengguna
| Fitur | Keterangan |
|---|---|
| 🔐 **Autentikasi Multi-Peran** | Registrasi, login, logout; role `user` dan `admin` dipisahkan secara ketat |
| 🔍 **Pemindai URL Real-Time** | Analisis URL phishing instan menggunakan model XGBoost melalui FastAPI |
| 📊 **Dashboard Statistik** | Ringkasan total scan, persentase phishing, dan riwayat terbaru |
| 📜 **Riwayat Pemindaian** | Filter, pencarian, dan detail setiap hasil scan dengan isolasi data per pengguna |
| 👤 **Manajemen Profil** | Ubah nama, email, dan kata sandi secara mandiri |

### Panel Administrator
| Fitur | Keterangan |
|---|---|
| 📊 **Dashboard Admin** | Grafik tren scan, distribusi hasil, dan pertumbuhan pengguna (Chart.js) |
| 👥 **Manajemen Pengguna** | CRUD pengguna, ubah role, reset password; proteksi self-deletion |
| 🗂️ **Manajemen Scan Global** | Monitoring seluruh scan sistem, filter multi-dimensi |
| 🤖 **Model Lifecycle Registry** | Upload bundle ZIP, validasi 10 pengujian ketat, aktivasi runtime, rollback instan |
| 📋 **Audit Trail** | Log seluruh aktivitas sistem (login, scan, upload model, aktivasi, rollback) |

### Mesin ML (FastAPI)
| Fitur | Keterangan |
|---|---|
| ⚡ **Inferensi Cepat** | Ekstraksi 49 fitur leksikal URL + prediksi XGBoost dalam satu HTTP call |
| 🔄 **Hot-Reload Model** | Ganti model aktif tanpa restart server; model baru langsung berlaku |
| ✅ **Validasi Bundle Ketat** | 10 pengujian: integritas file, konsistensi fitur, format metadata, akurasi minimum |
| 🏥 **Health Check** | Endpoint `/health` untuk monitoring status mesin inferensi |
| 📖 **Dokumentasi Interaktif** | Swagger UI & ReDoc tersedia otomatis (`/docs`, `/redoc`) |

---

## 🏗️ Arsitektur Sistem

```
┌─────────────────────────────────────────────────────────────────┐
│                        PENGGUNA / ADMIN                         │
│                      Browser (HTTP :8001)                       │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                   LARAVEL 12 (Port: 8001)                       │
│  ┌──────────────┐  ┌──────────────┐  ┌────────────────────────┐ │
│  │  Auth Layer  │  │ User Portal  │  │    Admin Panel         │ │
│  │  (Login/Reg) │  │ (Scanner,    │  │    (Dashboard, Users,  │ │
│  │              │  │  History,    │  │     Scans, Models,     │ │
│  │              │  │  Profile)    │  │     Activity Logs)     │ │
│  └──────────────┘  └──────────────┘  └────────────────────────┘ │
│                          │                       │               │
│       ┌──────────────────┘                       │               │
│       │  PhishGuardService (HTTP Client)         │               │
│       │                                          │               │
│  ┌────▼──────┐    ┌──────────────────────────────▼──────────┐   │
│  │ /predict  │    │  /internal/validate-model               │   │
│  │ /health   │    │  /internal/reload-model                 │   │
│  └────┬──────┘    └──────────────────────────────┬──────────┘   │
└───────┼──────────────────────────────────────────┼──────────────┘
        │            HTTP (Token-Protected)         │
        ▼                                           ▼
┌─────────────────────────────────────────────────────────────────┐
│                    FASTAPI ML ENGINE (Port: 8000)               │
│  ┌───────────────┐  ┌──────────────────┐  ┌─────────────────┐  │
│  │  model_manager│  │  url_features.py │  │   internal.py   │  │
│  │  (ModelManager│  │  (49 fitur URL   │  │  (validate &    │  │
│  │   Hot-Reload) │  │   leksikal)      │  │   reload model) │  │
│  └───────┬───────┘  └──────────────────┘  └─────────────────┘  │
│          │                                                        │
│    ┌─────▼─────────────────────────────┐                         │
│    │      XGBoost Model (model.pkl)    │                         │
│    │      Model Registry (ml/models/)  │                         │
│    └───────────────────────────────────┘                         │
└─────────────────────────────────────────────────────────────────┘
        │
        ▼
┌─────────────────────────────────────────────────────────────────┐
│                    MySQL (Port: 3306)                           │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌───────────────────┐  │
│  │  users   │ │  scans   │ │  models  │ │  activity_logs    │  │
│  └──────────┘ └──────────┘ └──────────┘ └───────────────────┘  │
│              ┌───────────────────────────────┐                   │
│              │  model_activation_logs        │                   │
│              └───────────────────────────────┘                   │
└─────────────────────────────────────────────────────────────────┘
```

### Alur Pemindaian URL

```
Pengguna Input URL
       │
       ▼
ScanUrlRequest (Validasi Laravel)
       │
       ▼
PhishGuardService::predict()
       │
       ├──► POST http://127.0.0.1:8000/predict  {"url": "..."}
       │           │
       │           ▼
       │    url_features.py: Ekstraksi 49 Fitur Leksikal
       │           │
       │           ▼
       │    XGBoost Model: Inferensi Probabilitas
       │           │
       │           ▼
       │    PredictResponse: prediction, probability, risk_score,
       │                     risk_level, indicators, model_id
       │
       ▼
ScannerController: Simpan hasil → tabel `scans`
       │
       ▼
ActivityLog::record(SCAN_URL, ...)
       │
       ▼
Tampilkan Hasil + Indikator ke Pengguna
```

---

## 🛠️ Teknologi yang Digunakan

### Backend & Web (Laravel)
| Komponen | Versi | Peran |
|---|---|---|
| PHP | ≥ 8.2 | Runtime backend |
| Laravel Framework | 12.x | Web framework, ORM, routing |
| MySQL / MariaDB | ≥ 8.0 / ≥ 10.4 | Database relasional |
| Tailwind CSS (CDN) | 3.x | Framework CSS responsif |
| Chart.js (CDN) | 4.x | Visualisasi grafik admin |

### ML Engine (FastAPI)
| Komponen | Versi | Peran |
|---|---|---|
| Python | ≥ 3.12 | Runtime ML engine |
| FastAPI | ≥ 0.115 | REST API framework |
| Uvicorn | latest | ASGI server |
| XGBoost | 3.4.1 | Model klasifikasi URL |
| Scikit-learn | 1.6.1 | Pipeline ML & preprocessing |
| NumPy | 2.1.3 | Komputasi numerik |
| Pandas | 2.2.3 | Manipulasi data |
| Pydantic | v2 | Validasi skema request/response |

---

## 📁 Struktur Direktori

```
PhishGuard/
├── README.md                        ← Dokumentasi ini
│
├── laravel/                         ← Portal Web & Backend API
│   ├── app/
│   │   ├── Exceptions/
│   │   │   └── MlServiceException.php   # Exception ML dengan pesan aman pengguna
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── Admin/
│   │   │   │   │   ├── ActivityLogController.php
│   │   │   │   │   ├── DashboardController.php
│   │   │   │   │   ├── ModelManagementController.php   # Upload/Validasi/Aktivasi/Rollback
│   │   │   │   │   ├── ScanController.php
│   │   │   │   │   └── UserController.php
│   │   │   │   ├── Auth/
│   │   │   │   │   └── AuthController.php
│   │   │   │   └── User/
│   │   │   │       ├── DashboardController.php
│   │   │   │       ├── HistoryController.php
│   │   │   │       ├── ProfileController.php
│   │   │   │       └── ScannerController.php
│   │   │   ├── Middleware/
│   │   │   │   ├── AdminMiddleware.php         # Proteksi rute admin
│   │   │   │   ├── SecurityHeadersMiddleware.php  # HTTP security headers
│   │   │   │   └── UserMiddleware.php
│   │   │   └── Requests/
│   │   │       ├── Admin/                      # Form Request Validation admin
│   │   │       └── ScanUrlRequest.php          # Validasi input URL scanner
│   │   ├── Models/
│   │   │   ├── ActivityLog.php
│   │   │   ├── MlModel.php
│   │   │   ├── ModelActivationLog.php
│   │   │   ├── Scan.php
│   │   │   └── User.php
│   │   └── Services/
│   │       ├── PhishGuardService.php   # HTTP client ke FastAPI
│   │       └── PredictionResult.php   # Value Object hasil prediksi + validasi kontrak
│   ├── config/
│   │   └── phishguard.php             # Konfigurasi ML API (URL, timeout, token)
│   ├── database/
│   │   ├── factories/
│   │   │   └── UserFactory.php
│   │   ├── migrations/                # 8 migrasi database
│   │   └── seeders/
│   │       └── DatabaseSeeder.php     # Akun default + model awal
│   ├── public/
│   │   ├── images/
│   │   │   └── logo.png               # Logo PhishGuard
│   │   └── favicon.png
│   ├── resources/views/
│   │   ├── admin/                     # Views panel admin
│   │   ├── auth/                      # Login & register
│   │   ├── errors/
│   │   │   └── 403.blade.php
│   │   ├── layouts/
│   │   │   ├── admin.blade.php
│   │   │   ├── app.blade.php
│   │   │   └── auth.blade.php
│   │   ├── user/                      # Views portal pengguna
│   │   └── welcome.blade.php          # Landing page
│   ├── routes/
│   │   └── web.php                    # Seluruh definisi rute
│   ├── tests/
│   │   ├── Feature/                   # 5 test file (66 test cases)
│   │   └── Unit/                      # Unit test PredictionResult
│   ├── .env.example
│   └── phpunit.xml
│
└── ml/                              ← Mesin Inferensi ML
    ├── api/
    │   ├── main.py          # FastAPI app, endpoint /predict, /health, /model
    │   ├── model_manager.py # ModelManager: load, hot-reload, thread-safe
    │   └── internal.py      # /internal/validate-model, /internal/reload-model
    ├── src/
    │   └── url_features.py  # Ekstraksi 49 fitur leksikal URL
    ├── models/
    │   └── xgboost_v1_1/    # Bundle model aktif (model.pkl, metadata.json, feature_names.json)
    ├── tests/               # 101 test cases pytest
    │   ├── test_api.py
    │   ├── test_internal.py
    │   ├── test_url_features.py
    │   └── test_bundle_asli.py
    ├── .env
    └── requirements.txt
```

---

## ⚙️ Prasyarat

Pastikan perangkat lunak berikut telah terpasang sebelum memulai:

| Perangkat Lunak | Versi Minimum | Catatan |
|---|---|---|
| PHP | 8.2 | Ekstensi wajib: `pdo_mysql`, `zip`, `mbstring`, `fileinfo`, `xml`, `curl` |
| Composer | 2.x | Package manager PHP |
| MySQL / MariaDB | 8.0 / 10.4 | Atau gunakan XAMPP/Laragon |
| Python | 3.12 | Untuk ML engine |
| pip | 24.x | Package manager Python |

**Verifikasi instalasi:**
```bash
php --version          # PHP 8.2.x atau lebih baru
composer --version     # Composer 2.x
mysql --version        # MySQL 8.x atau MariaDB 10.4.x
python --version       # Python 3.12.x atau lebih baru
pip --version
```

---

## 🚀 Panduan Instalasi Lengkap

### 1. Persiapan Database (MySQL)

Buat database `phishguard` terlebih dahulu:

```sql
-- Masuk ke MySQL console
mysql -u root -p

-- Buat database
CREATE DATABASE phishguard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Verifikasi
SHOW DATABASES;
EXIT;
```

---

### 2. Setup Laravel (Web Portal)

#### 2a. Instal Dependensi

```bash
cd PhishGuard/laravel
composer install
```

#### 2b. Konfigurasi Environment

Salin file konfigurasi contoh dan sesuaikan:

```bash
copy .env.example .env
```

Edit file `.env` dengan nilai berikut:

```dotenv
APP_NAME=PhishGuard
APP_ENV=local
APP_KEY=                        # Akan digenerate otomatis
APP_DEBUG=true
APP_URL=http://127.0.0.1:8001

APP_LOCALE=id
APP_FALLBACK_LOCALE=id

# Database MySQL
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=phishguard
DB_USERNAME=root
DB_PASSWORD=                    # Isi password MySQL jika ada

# Integrasi FastAPI
ML_API_URL=http://127.0.0.1:8000
ML_API_TIMEOUT=10
ML_API_CONNECT_TIMEOUT=3
ML_INTERNAL_TOKEN=your-secret-token-here   # Harus sama dengan ml/.env

# Session & Cache
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

#### 2c. Generate Application Key

```bash
php artisan key:generate
```

#### 2d. Jalankan Migrasi & Seeder

```bash
# Buat seluruh tabel database
php artisan migrate

# Isi data awal (akun admin, user, model ML pertama)
php artisan db:seed
```

#### 2e. Buat Symlink Storage (Opsional)

```bash
php artisan storage:link
```

---

### 3. Setup FastAPI (ML Engine)

#### 3a. Buat & Aktifkan Virtual Environment

```bash
cd PhishGuard/ml

# Windows
python -m venv venv
venv\Scripts\activate

# Linux / macOS
python3 -m venv venv
source venv/bin/activate
```

#### 3b. Instal Dependensi Python

```bash
pip install -r requirements.txt
```

#### 3c. Konfigurasi Environment ML

Buat atau edit file `ml/.env`:

```dotenv
APP_ENV=local

# Token rahasia (HARUS SAMA dengan ML_INTERNAL_TOKEN di laravel/.env)
ML_INTERNAL_TOKEN=your-secret-token-here

# URL Laravel untuk sinkronisasi model aktif saat startup
LARAVEL_BASE_URL=http://127.0.0.1:8001

# Direktori registry model (relatif terhadap folder ml/)
MODEL_REGISTRY_DIR=models

# Model yang aktif saat ini (nama folder bundle)
ACTIVE_MODEL_DIR=xgboost_v1_1

# Aktifkan Swagger UI & ReDoc
ENABLE_DOCS=true
LOG_LEVEL=INFO

# Interval retry sinkronisasi model aktif dari Laravel (detik)
STARTUP_PULL_RETRY_SECONDS=15
```

> **Penting:** Nilai `ML_INTERNAL_TOKEN` di `ml/.env` **harus persis sama** dengan nilai di `laravel/.env`. Token ini melindungi endpoint internal FastAPI dari akses tidak sah.

#### 3d. Verifikasi Bundle Model

Pastikan bundle model awal tersedia:

```bash
# Periksa struktur bundle
dir ml\models\xgboost_v1_1\
```

Bundle harus mengandung tiga berkas wajib:
```
ml/models/xgboost_v1_1/
├── model.pkl            # Model XGBoost terserialisasi
├── metadata.json        # Metadata: metrics, algorithm, version
└── feature_names.json   # Daftar 49 nama fitur (harus urut sama dengan training)
```

---

### 4. Menjalankan Sistem

Sistem memerlukan **dua proses** yang berjalan secara bersamaan di terminal terpisah:

#### Terminal 1 — Laravel Web Portal

```bash
cd PhishGuard/laravel
php artisan serve --host=127.0.0.1 --port=8001
```

✅ Akses di: **http://127.0.0.1:8001**

#### Terminal 2 — FastAPI ML Engine

```bash
cd PhishGuard/ml
venv\Scripts\activate           # Windows
# atau: source venv/bin/activate  (Linux/macOS)

uvicorn api.main:app --host 127.0.0.1 --port 8000 --reload
```

✅ Akses API di: **http://127.0.0.1:8000**
✅ Swagger UI di: **http://127.0.0.1:8000/docs**

---

## 🔑 Kredensial Default

Setelah menjalankan `php artisan db:seed`, akun berikut tersedia:

| Role | Email | Password |
|---|---|---|
| **Administrator** | `admin@phishguard.test` | `password123` |
| **Pengguna Biasa** | `user@phishguard.test` | `password123` |

> ⚠️ **Segera ganti password default di lingkungan produksi.**

---

## 📖 Dokumentasi API

### FastAPI Public Endpoints

| Method | Endpoint | Keterangan |
|---|---|---|
| `GET` | `/` | Info versi API & status |
| `GET` | `/health` | Status mesin ML (model loaded, nama, versi) |
| `GET` | `/model` | Detail teknis model aktif |
| `POST` | `/predict` | Prediksi URL phishing |

#### POST `/predict` — Prediksi URL

**Request Body:**
```json
{
  "url": "https://contoh.com/halaman?param=nilai"
}
```

**Response 200 (Sukses):**
```json
{
  "url": "https://contoh.com/halaman?param=nilai",
  "prediction": "Legitimate",
  "probability": 0.9823,
  "risk_score": 2,
  "risk_level": "Low",
  "model_id": 1,
  "model_name": "PhishGuard XGBoost",
  "model_version": "1.2",
  "message": "URL ini terdeteksi aman berdasarkan analisis 49 fitur struktural.",
  "indicators": [
    "Menggunakan protokol HTTPS yang aman",
    "Panjang URL dalam batas normal"
  ]
}
```

| Field | Tipe | Keterangan |
|---|---|---|
| `prediction` | `"Legitimate"` \| `"Phishing"` | Hasil klasifikasi |
| `probability` | `float` [0.5–1.0] | Keyakinan model pada kelas yang diprediksi |
| `risk_score` | `int` [0–100] | Skor risiko phishing (0 = aman, 100 = berbahaya) |
| `risk_level` | `"Low"` \| `"Medium"` \| `"High"` | Tingkat risiko berdasarkan skor |

**Kode Status Respons:**
| Status | Keterangan |
|---|---|
| `200` | Prediksi berhasil |
| `422` | URL tidak valid (format, skema, atau karakter ilegal) |
| `503` | Model belum dimuat / mesin tidak siap |

---

### FastAPI Internal Endpoints (Token-Protected)

> Memerlukan header: `X-Internal-Token: <ML_INTERNAL_TOKEN>`

| Method | Endpoint | Keterangan |
|---|---|---|
| `POST` | `/internal/validate-model` | Validasi bundle ZIP model baru (10 pengujian) |
| `POST` | `/internal/reload-model` | Aktivasi hot-reload model baru sebagai model aktif |

---

### Laravel Internal Endpoint (Token-Protected)

| Method | Endpoint | Keterangan |
|---|---|---|
| `GET` | `/api/internal/active-model` | Info bundle model aktif untuk sinkronisasi FastAPI saat startup |

---

## 🤖 Pipeline ML & Fitur URL

### Dataset Training

Model dilatih menggunakan **Web page phishing detection dataset (Hannousse & Yahiouche) — Dataset B** yang tersedia secara publik di Kaggle. Dataset terdiri dari 87.177 sampel URL (legitimate dan phishing) yang seimbang.

### 49 Fitur Leksikal URL

Sistem mengekstraksi fitur dari **struktur teks URL** saja — tanpa mengakses konten website atau melakukan request jaringan ke server target:

| Kelompok | Fitur |
|---|---|
| **Panjang** | `length_url`, `length_hostname` |
| **Karakter Khusus** | `nb_dots`, `nb_hyphens`, `nb_at`, `nb_qm`, `nb_and`, `nb_or`, `nb_eq`, `nb_underscore`, `nb_tilde`, `nb_percent`, `nb_slash`, `nb_star`, `nb_colon`, `nb_comma`, `nb_semicolon`, `nb_dollar`, `nb_space` |
| **Pola URL** | `nb_www`, `nb_com`, `nb_http`, `nb_dslash`, `is_https`, `has_port`, `ip` |
| **Rasio** | `ratio_digits_url`, `ratio_digits_host` |
| **Domain** | `punycode`, `tld_in_path`, `tld_in_subdomain`, `abnormal_subdomain`, `nb_subdomains`, `prefix_suffix`, `shortening_service` |
| **Path** | `path_extension` |
| **Statistik Kata** | `length_words_raw`, `char_repeat`, `shortest_words_raw`, `shortest_word_host`, `shortest_word_path`, `longest_words_raw`, `longest_word_host`, `longest_word_path`, `avg_words_raw`, `avg_word_host`, `avg_word_path` |
| **Semantik** | `phish_hints`, `entropy_host` |

### Metrik Model Baseline (XGBoost v1.2)

| Metrik | Nilai |
|---|---|
| **Accuracy** | 87.58% |
| **Precision** | 87.51% |
| **Recall** | 87.34% |
| **F1-Score** | 87.41% |
| **ROC-AUC** | 95.03% |

---

## 🔄 Manajemen Model ML

PhishGuard mendukung **multi-versi model** dengan alur lifecycle yang terstruktur:

```
                    ┌─────────────────────────────┐
                    │      Admin Upload Bundle     │
                    │   (ZIP: model.pkl +          │
                    │    metadata.json +           │
                    │    feature_names.json)       │
                    └─────────────┬───────────────┘
                                  │
                                  ▼
                    ┌─────────────────────────────┐
                    │    STATUS: INACTIVE          │
                    │    (Tersimpan di database)   │
                    └─────────────┬───────────────┘
                                  │
                          Klik "Validasi"
                                  │
                                  ▼
                    ┌─────────────────────────────┐
                    │   Validasi 10 Pengujian:     │
                    │   ✓ File integrity           │
                    │   ✓ Feature consistency      │
                    │   ✓ Metadata format          │
                    │   ✓ Prediction correctness   │
                    │   ✓ Accuracy minimum         │
                    └─────────────┬───────────────┘
                                  │ Lulus semua
                          Klik "Aktivasi"
                                  │
                                  ▼
                    ┌─────────────────────────────┐
                    │    STATUS: ACTIVE            │
                    │    Hot-Reload via FastAPI    │
                    │    (tanpa restart server)    │
                    └─────────────┬───────────────┘
                                  │
              Jika ditemukan masalah → Klik "Rollback"
                                  │
                                  ▼
                    ┌─────────────────────────────┐
                    │    STATUS: ARCHIVED          │
                    │    (Model sebelumnya         │
                    │     diaktifkan kembali)      │
                    └─────────────────────────────┘
```

### Format Bundle ZIP Model

```
bundle_model_v2.0.zip
├── model.pkl              # Model XGBoost terserialisasi (joblib)
├── metadata.json          # Wajib berisi:
│   ├── name
│   ├── version
│   ├── algorithm
│   ├── metrics: {accuracy, precision, recall, f1_score, roc_auc}
│   └── description (opsional)
└── feature_names.json     # Array 49 string nama fitur (urut harus sama persis dengan training)
```

---

## 🧪 Pengujian Otomatis

### Laravel (PHPUnit)

```bash
cd PhishGuard/laravel

# Jalankan seluruh test suite
php artisan test

# Jalankan hanya unit tests
php artisan test --testsuite=Unit

# Jalankan hanya feature tests
php artisan test --testsuite=Feature
```

**Cakupan Pengujian (66 Test Cases):**

| Test Class | Deskripsi |
|---|---|
| `PredictionResultTest` | Validasi kontrak respons FastAPI (30 kasus) |
| `PhishGuardServiceTest` | Integrasi HTTP client ke FastAPI dengan mock (24 kasus) |
| `AuthenticationTest` | Login, register, logout, validasi kredensial (5 kasus) |
| `RoleAccessControlTest` | Proteksi middleware admin, isolasi role (4 kasus) |
| `ScanHistoryIsolationTest` | Isolasi data per pengguna (IDOR protection) (1 kasus) |

### FastAPI (Pytest)

```bash
cd PhishGuard/ml
venv\Scripts\activate     # Windows
# source venv/bin/activate  (Linux/macOS)

# Jalankan seluruh test
python -m pytest

# Jalankan dengan output verbose
python -m pytest -v

# Jalankan test tertentu
python -m pytest tests/test_api.py -v
```

**Cakupan Pengujian (101 Test Cases):**

| Test File | Deskripsi |
|---|---|
| `test_api.py` | Seluruh endpoint FastAPI (predict, health, model) |
| `test_internal.py` | Validasi & reload model internal |
| `test_url_features.py` | Ekstraksi 49 fitur dari berbagai URL |
| `test_bundle_asli.py` | Integrasi dengan bundle model asli |

---

## 📘 Panduan Penggunaan

### Sebagai Pengguna Biasa

1. **Registrasi** → Akses `http://127.0.0.1:8001/register` dan buat akun baru
2. **Login** → Masuk dengan email dan password
3. **Scanner** → Klik menu **Scanner**, masukkan URL lengkap (wajib diawali `http://` atau `https://`), klik **Pindai**
4. **Lihat Hasil** → Sistem menampilkan: label prediksi, tingkat risiko, skor kepercayaan (0–100%), dan indikator struktural
5. **Riwayat** → Klik menu **History** untuk melihat seluruh pemindaian sebelumnya dengan filter dan pencarian
6. **Profil** → Klik menu **Profil** untuk mengubah nama, email, atau kata sandi

### Sebagai Administrator

1. **Login** dengan akun admin → Secara otomatis diarahkan ke **Panel Admin**
2. **Dashboard** → Pantau metrik sistem: total pengguna, scan, tren, distribusi hasil
3. **Manajemen Pengguna** → Kelola semua akun, ubah role, reset password
4. **Model ML** → Upload bundle model baru, validasi, dan aktifkan tanpa restart server
5. **Activity Logs** → Audit seluruh aktivitas sistem secara kronologis

---

## 🔒 Keamanan

| Fitur | Implementasi |
|---|---|
| **CSRF Protection** | Token CSRF pada seluruh form POST/PUT/DELETE (Laravel default) |
| **Password Hashing** | Bcrypt dengan 12 salt rounds (`password` cast di model `User`) |
| **Role-Based Access Control** | `AdminMiddleware` memblokir akses `/admin/*` untuk role non-admin |
| **Data Isolation** | Pengguna hanya dapat mengakses data scan miliknya sendiri (cek `user_id` eksplisit) |
| **Timing-Safe Token** | `hash_equals()` untuk validasi `ML_INTERNAL_TOKEN` (mencegah timing attack) |
| **Zip Slip Protection** | Inspeksi seluruh entri ZIP sebelum ekstraksi bundle model |
| **Security Headers** | `X-Content-Type-Options`, `X-Frame-Options`, `X-XSS-Protection`, `Referrer-Policy`, `Permissions-Policy` via `SecurityHeadersMiddleware` |
| **Input Validation** | Form Request Validation Laravel + Pydantic validation di FastAPI |
| **Error Sanitization** | `MlServiceException` memastikan detail teknis tidak bocor ke antarmuka pengguna |
| **Audit Trail** | Seluruh tindakan sensitif dicatat di tabel `activity_logs` |

---

## 📄 Lisensi

Sistem ini dikembangkan untuk keperluan **akademik** dalam rangka penyelesaian Tugas Akhir / Skripsi.

---

<div align="center">

**PhishGuard** — *Melindungi pengguna dari ancaman tautan berbahaya menggunakan kecerdasan buatan.*

© 2026 PhishGuard — AI-Powered Phishing URL Detection System

</div>
