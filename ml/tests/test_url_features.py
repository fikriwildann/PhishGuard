"""Uji extractor fitur URL. Nilai harapan dihitung manual dari definisi di url_features.py."""
import ast
import math
from pathlib import Path

import pytest

import url_features as uf


def test_jumlah_dan_nama_fitur():
    assert len(uf.FEATURE_NAMES) == 49
    assert len(set(uf.FEATURE_NAMES)) == 49


def test_urutan_dict_sama_dengan_vektor():
    for url in ["https://www.example.com/path?a=1&b=2", "http://192.168.0.1:8080/login.php", "http://bit.ly/abc"]:
        features = uf.extract_url_features(url)
        assert list(features) == uf.FEATURE_NAMES
        assert uf.extract_feature_vector(url) == [float(features[n]) for n in uf.FEATURE_NAMES]


def test_url_biasa():
    f = uf.extract_url_features("https://www.example.com/path?a=1&b=2")
    expected = {
        "length_url": 36, "length_hostname": 15, "nb_dots": 2, "nb_qm": 1, "nb_and": 1, "nb_eq": 2,
        "nb_slash": 3, "nb_www": 1, "nb_com": 1, "nb_dslash": 1, "is_https": 1,
        "nb_subdomains": 1, "ip": 0, "has_port": 0,
    }
    for key, value in expected.items():
        assert f[key] == value, key


def test_host_ip_dengan_port():
    f = uf.extract_url_features("http://192.168.0.1:8080/login.php")
    assert (f["ip"], f["has_port"], f["is_https"], f["nb_colon"]) == (1, 1, 0, 2)


def test_pola_phishing_klasik():
    f = uf.extract_url_features("http://paypal.com.secure-login.xyz/x.exe")
    assert f["tld_in_subdomain"] == 1 and f["prefix_suffix"] == 1 and f["path_extension"] == 1
    assert f["nb_subdomains"] == 2 and f["phish_hints"] == 3


def test_pemendek_subdomain_abnormal_tld_path_punycode():
    assert uf.extract_url_features("http://bit.ly/abc")["shortening_service"] == 1
    assert uf.extract_url_features("http://www.bit.ly/abc")["shortening_service"] == 1
    assert uf.extract_url_features("http://notbit.ly.example.com/abc")["shortening_service"] == 0
    assert uf.extract_url_features("http://ww2.evil.com/a/b.com/x")["abnormal_subdomain"] == 1
    assert uf.extract_url_features("http://ww2.evil.com/a/b.com/x")["tld_in_path"] == 1
    assert uf.extract_url_features("http://xn--80ak6aa92e.com/")["punycode"] == 1


@pytest.mark.parametrize("aneh", ["", "   ", "not a url", "http://[::1", "http://", "a" * 5000, None,
                                  "http://exa mple.com/%zz", "javascript:alert(1)", "http://@@@", "http://a..b/"])
def test_input_aneh_tidak_melempar_error(aneh):
    vector = uf.extract_feature_vector(aneh)
    assert len(vector) == len(uf.FEATURE_NAMES)
    assert all(math.isfinite(x) for x in vector)


def test_deterministik():
    url = "https://login.secure-update.example.co.id/a/b/c?x=1"
    assert uf.extract_url_features(url) == uf.extract_url_features(url)


def test_extractor_hanya_memakai_pustaka_standar():
    source = Path(uf.__file__).read_text(encoding="utf-8")
    imported = set()
    for node in ast.walk(ast.parse(source)):
        if isinstance(node, ast.Import):
            imported |= {n.name.split(".")[0] for n in node.names}
        elif isinstance(node, ast.ImportFrom):
            imported.add(node.module.split(".")[0])
    assert imported <= {"__future__", "ipaddress", "math", "re", "collections", "itertools", "urllib"}