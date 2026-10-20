"""Ekstraksi fitur URL PhishGuard.

Aturan:
- Hanya memakai string URL. Tidak ada akses jaringan (DNS, WHOIS, HTTP) dan tidak ada
  pembacaan file eksternal.
- File ini dipakai IDENTIK oleh training (Colab) dan inferensi (FastAPI).
- Mengubah fitur, urutan fitur, atau definisi fitur WAJIB menaikkan EXTRACTOR_VERSION.
- URL tanpa skema diberi "http://" oleh normalize_url() sebelum fitur dihitung (v1.1).
"""
from __future__ import annotations

import ipaddress
import math
import re
from collections import Counter
from itertools import groupby
from urllib.parse import urlsplit

EXTRACTOR_VERSION = "1.2"

FEATURE_NAMES: list[str] = [
    # Panjang
    "length_url", "length_hostname",
    # Hostname berupa IP
    "ip",
    # Jumlah karakter khusus pada URL
    "nb_dots", "nb_hyphens", "nb_at", "nb_qm", "nb_and", "nb_or", "nb_eq",
    "nb_underscore", "nb_tilde", "nb_percent", "nb_slash", "nb_star", "nb_colon",
    "nb_comma", "nb_semicolon", "nb_dollar", "nb_space",
    # Istilah umum
    "nb_www", "nb_com", "nb_http", "nb_dslash",
    # Protokol, rasio digit, punycode, port
    "is_https", "ratio_digits_url", "ratio_digits_host", "punycode", "has_port",
    # Posisi TLD dan struktur subdomain
    "tld_in_path", "tld_in_subdomain", "abnormal_subdomain", "nb_subdomains", "prefix_suffix",
    # Layanan pemendek URL dan ekstensi path
    "shortening_service", "path_extension",
    # Statistik kata
    "length_words_raw", "char_repeat",
    "shortest_words_raw", "shortest_word_host", "shortest_word_path",
    "longest_words_raw", "longest_word_host", "longest_word_path",
    "avg_words_raw", "avg_word_host", "avg_word_path",
    # Kata kunci mencurigakan dan keacakan hostname
    "phish_hints", "entropy_host",
]

COMMON_TLDS = frozenset({
    "com", "net", "org", "edu", "gov", "info", "biz", "io", "xyz", "top", "site",
    "online", "shop", "app", "dev", "uk", "de", "ru", "cn", "jp", "fr", "br", "au", "us",
})

SHORTENERS = frozenset({
    "bit.ly", "goo.gl", "tinyurl.com", "t.co", "ow.ly", "is.gd", "buff.ly", "adf.ly",
    "bit.do", "cutt.ly", "rebrand.ly", "shorturl.at", "tiny.cc", "rb.gy", "lnkd.in",
    "t.ly", "v.gd", "tr.im", "x.co", "soo.gd",
})

PHISH_HINTS = (
    "login", "signin", "verify", "update", "secure", "account", "bank", "confirm",
    "password", "paypal", "webscr", "wallet", "billing", "support", "suspend",
    "alert", "authenticate",
)

_WORD_SPLIT = re.compile(r"[^A-Za-z0-9]+")
_ABNORMAL_SUBDOMAIN = re.compile(r"w[w]?[0-9]*")
_INTEGER_HOST = re.compile(r"0x[0-9a-f]+|\d{8,10}")
_TLD_IN_PATH = re.compile(r"\.(?:" + "|".join(sorted(COMMON_TLDS)) + r")(?![a-z0-9])")


_SCHEME = re.compile(r"^[A-Za-z][A-Za-z0-9+.\-]*://")


def normalize_url(url) -> str:
    """Bentuk kanonik URL untuk ekstraksi: buang spasi tepi dan pastikan ada skema.

    URL pada dataset training berskema (http:// atau https://). URL tanpa skema (mis. "example.com/a")
    diberi "http://" agar fitur yang bergantung pada skema (is_https, nb_slash, nb_dslash, nb_http,
    length_url) tidak keluar dari distribusi training. Skema yang sudah ada tidak diubah.
    """
    raw = "" if url is None else str(url).strip()
    if not raw:
        return ""
    if raw.startswith("//"):
        return "http:" + raw
    if not _SCHEME.match(raw):
        return "http://" + raw
    return raw


def _split_url(url: str) -> tuple[str, str, bool, str]:
    """Kembalikan (url_mentah, hostname, ada_port, path). Tidak pernah melempar error."""
    raw = url.strip()
    candidate = raw if _SCHEME.match(raw) else "http://" + raw
    try:
        parts = urlsplit(candidate)
        host = (parts.hostname or "").lower()
        try:
            has_port = parts.port is not None
        except ValueError:
            has_port = True  # ada penanda port tetapi nilainya tidak valid
        return raw, host, has_port, parts.path
    except ValueError:
        # Cadangan manual untuk URL yang tidak dapat di-parse (mis. IPv6 rusak)
        rest = raw.split("://", 1)[-1]
        netloc = re.split(r"[/?#]", rest, maxsplit=1)[0]
        hostport = netloc.rsplit("@", 1)[-1]
        host = hostport.split(":")[0].lower()
        path = re.split(r"[?#]", rest[len(netloc):], maxsplit=1)[0]
        return raw, host, ":" in hostport, path


def _is_ip(host: str) -> bool:
    if not host:
        return False
    try:
        ipaddress.ip_address(host)
        return True
    except ValueError:
        return bool(_INTEGER_HOST.fullmatch(host))


def _words(text: str) -> list[str]:
    return [w for w in _WORD_SPLIT.split(text) if w]


def _word_stats(words: list[str]) -> tuple[int, int, int, float]:
    if not words:
        return 0, 0, 0, 0.0
    lengths = [len(w) for w in words]
    return len(words), min(lengths), max(lengths), sum(lengths) / len(lengths)


def _digit_ratio(text: str) -> float:
    if not text:
        return 0.0
    return sum("0" <= ch <= "9" for ch in text) / len(text)


def _entropy(text: str) -> float:
    if not text:
        return 0.0
    total = len(text)
    return -sum((n / total) * math.log2(n / total) for n in Counter(text).values())


def extract_url_features(url: str) -> dict[str, float]:
    """Hitung fitur dari satu URL. Urutan kunci selalu sama dengan FEATURE_NAMES."""
    raw, host, has_port, path = _split_url(normalize_url(url))
    lower = raw.lower()
    path_lower = path.lower()

    is_ip = _is_ip(host)
    labels = host.split(".") if host else []
    sub_labels = labels[:-2] if (labels and not is_ip) else []

    url_words = _words(raw)
    host_words = _words(host)
    path_words = _words(path)
    n_url, short_url, long_url, avg_url = _word_stats(url_words)
    _, short_host, long_host, avg_host = _word_stats(host_words)
    _, short_path, long_path, avg_path = _word_stats(path_words)

    f = {
        "length_url": len(raw),
        "length_hostname": len(host),
        "ip": int(is_ip),
        "nb_dots": raw.count("."),
        "nb_hyphens": raw.count("-"),
        "nb_at": raw.count("@"),
        "nb_qm": raw.count("?"),
        "nb_and": raw.count("&"),
        "nb_or": raw.count("|"),
        "nb_eq": raw.count("="),
        "nb_underscore": raw.count("_"),
        "nb_tilde": raw.count("~"),
        "nb_percent": raw.count("%"),
        "nb_slash": raw.count("/"),
        "nb_star": raw.count("*"),
        "nb_colon": raw.count(":"),
        "nb_comma": raw.count(","),
        "nb_semicolon": raw.count(";"),
        "nb_dollar": raw.count("$"),
        "nb_space": raw.count(" ") + lower.count("%20"),
        "nb_www": lower.count("www"),
        "nb_com": lower.count(".com"),
        "nb_http": lower.count("http"),
        "nb_dslash": raw.count("//"),
        "is_https": int(lower.startswith("https://")),
        "ratio_digits_url": _digit_ratio(raw),
        "ratio_digits_host": _digit_ratio(host),
        "punycode": int("xn--" in lower),
        "has_port": int(has_port),
        "tld_in_path": int(bool(_TLD_IN_PATH.search(path_lower))),
        "tld_in_subdomain": int(any(label in COMMON_TLDS for label in sub_labels)),
        "abnormal_subdomain": int(any(_ABNORMAL_SUBDOMAIN.fullmatch(label) for label in sub_labels)),
        "nb_subdomains": len(sub_labels),
        "prefix_suffix": int("-" in host),
        "shortening_service": int(
            host in SHORTENERS or any(host.endswith("." + s) for s in SHORTENERS)
        ),
        "path_extension": int(path_lower.endswith((".txt", ".exe", ".js"))),
        "length_words_raw": n_url,
        "char_repeat": max((len(list(g)) for _, g in groupby(raw)), default=0),
        "shortest_words_raw": short_url,
        "shortest_word_host": short_host,
        "shortest_word_path": short_path,
        "longest_words_raw": long_url,
        "longest_word_host": long_host,
        "longest_word_path": long_path,
        "avg_words_raw": avg_url,
        "avg_word_host": avg_host,
        "avg_word_path": avg_path,
        "phish_hints": sum(lower.count(word) for word in PHISH_HINTS),
        "entropy_host": _entropy(host),
    }
    return {name: f[name] for name in FEATURE_NAMES}


def extract_feature_vector(url: str) -> list[float]:
    """Vektor fitur dengan urutan FEATURE_NAMES (dipakai FastAPI saat prediksi)."""
    features = extract_url_features(url)
    return [float(features[name]) for name in FEATURE_NAMES]
