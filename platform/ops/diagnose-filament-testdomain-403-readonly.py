#!/usr/bin/env python3
"""HW24 failed TEST Filament cutover: read-only 403 diagnosis.

Only inspect metadata, access verdicts, and categorized *historical* web logs.
Never print log lines, URLs with query tokens, IPs, passwords, or .env contents.
Does not alter Plesk, files, ACL, database, Git, or the document root.
"""
from __future__ import annotations

from collections import Counter
from pathlib import Path
import os
import pwd
import re
import stat
import subprocess
import sys

SITE = "test-artikelmanager.hairworld24.de"
VHOST = Path("/var/www/vhosts/hairworld24.de")
LEGACY = VHOST / "test-artikelmanager.HAIRWORLD24.de"
APP = VHOST / "hw24-console-private/repo/platform"
PUBLIC = APP / "public"
LOGDIRS = (
    Path("/var/www/vhosts/system") / SITE / "logs",
    VHOST / "logs" / SITE,
)
LOGNAMES = {
    "proxy_error_log", "error_log", "access_log", "access_ssl_log",
    "proxy_access_log", "proxy_access_ssl_log",
}
# Failure occurred 2026-11-22 19:32:55 UTC; system log may use local CET.
# Do not reveal raw log lines, which may include client IPs or request parameters.
TIME_RE = re.compile(r"\b(?:19|20):(2[5-9]|3[0-9]|4[0-6]):[0-5][0-9]\b")
DAY_RE = re.compile(r"(?i)(?:2026[-/]11[-/]22|nov\s+22)")
SIGNS = (
    ("htaccess_unreadable", re.compile(r"pcfg_openfile|unable to check htaccess|\.htaccess.{0,90}permission denied", re.I)),
    ("directory_traverse_denied", re.compile(r"AH00035|search permissions are missing|permission denied.*(?:directory|path|open\()", re.I)),
    ("index_or_script_denied", re.compile(r"index\.php.{0,160}(?:permission denied|denied|forbidden)|primary script unknown", re.I)),
    ("directory_index_forbidden", re.compile(r"AH01276|no matching directoryindex|directory index .*forbidden|directory index is forbidden", re.I)),
    ("access_rule_denied", re.compile(r"AH01797|client denied by server configuration|access forbidden by rule|access denied by rule", re.I)),
    ("nginx_forbidden", re.compile(r"403 forbidden|access forbidden|forbidden by nginx", re.I)),
    ("php_open_basedir", re.compile(r"open_basedir restriction|open_basedir", re.I)),
    ("php_other_error", re.compile(r"php (?:fatal error|warning|notice)|AH01071", re.I)),
    ("permission_denied_other", re.compile(r"permission denied|\(13: Permission denied\)", re.I)),
)


def run(args: list[str]) -> subprocess.CompletedProcess[str]:
    return subprocess.run(args, capture_output=True, text=True, check=False, timeout=10)


def verdict(user: str, flag: str, path: Path) -> str:
    if not path.exists():
        return "FEHLT"
    result = run(["runuser", "-u", user, "--", "test", flag, str(path)])
    return "JA" if result.returncode == 0 else "NEIN"


def permission_probe() -> None:
    print("2. Zugriffsrechte auf Veroeffentlichungspfad (ohne Dateiinhalte)")
    targets = [
        ("private-parent", VHOST / "hw24-console-private", "-x"),
        ("private-repo", APP.parent, "-x"),
        ("app-dir", APP, "-x"),
        ("public-dir", PUBLIC, "-x"),
        ("public/.htaccess", PUBLIC / ".htaccess", "-r"),
        ("public/index.php", PUBLIC / "index.php", "-r"),
        ("filament-css", PUBLIC / "css/filament/filament/app.css", "-r"),
        ("filament-js", PUBLIC / "js/filament/filament/app.js", "-r"),
    ]
    for label, path, flag in targets:
        try:
            meta = path.stat()
            mode = oct(stat.S_IMODE(meta.st_mode))
            owner = pwd.getpwuid(meta.st_uid).pw_name
        except (FileNotFoundError, PermissionError, KeyError):
            mode, owner = "?", "?"
        checks = " / ".join(
            f"{user}={verdict(user, flag, path)}"
            for user in ("nicovoss", "nginx", "www-data")
        )
        print(f"{label}: {mode}, Besitzer={owner}, {checks}")
    for private in (APP / ".env", APP / "database/database.sqlite"):
        result = " / ".join(
            f"{user}={verdict(user, '-r', private)}"
            for user in ("nginx", "www-data")
        )
        print(f"Private Datei {private.name} lesbar: {result}")
        if any(verdict(u, "-r", private) == "JA" for u in ("nginx", "www-data")):
            print("WARNUNG: Private Datei ist fuer einen Webserver-Benutzer lesbar!")

    if verdict("www-data", "-r", PUBLIC / ".htaccess") == "NEIN":
        print("VERDACHT: Apache-Benutzer kann public/.htaccess nicht lesen.")
    if verdict("www-data", "-r", PUBLIC / "index.php") == "NEIN":
        print("VERDACHT: Apache-Benutzer kann public/index.php nicht lesen.")
    print("Hinweis: Apache muss .htaccess ggf. selbst lesen; PHP-FPM-Rechte allein reichen nicht.")


def check_logs() -> None:
    print("3. Fehlerprotokolle zum Cutover-Zeitfenster (nur kategorisierte Treffer)")
    available = 0
    for directory in LOGDIRS:
        if not directory.is_dir():
            continue
        for filename in sorted(LOGNAMES):
            path = directory / filename
            if not path.is_file() or path.is_symlink():
                continue
            available += 1
            try:
                # Read last 4 MiB only. Never output unredacted lines.
                with path.open("rb") as stream:
                    stream.seek(0, os.SEEK_END)
                    size = stream.tell()
                    stream.seek(max(0, size - 4 * 1024 * 1024))
                    lines = stream.read().decode("utf-8", errors="replace").splitlines()
            except OSError:
                print(f"{filename}: nicht lesbar")
                continue
            matches = [
                line for line in lines
                if DAY_RE.search(line) and TIME_RE.search(line)
                and ("admin/login" in line or "403" in line
                     or "permission denied" in line.lower()
                     or "denied" in line.lower()
                     or "AH0" in line or "open_basedir" in line.lower())
            ]
            counts: Counter[str] = Counter()
            for line in matches:
                for category, pattern in SIGNS:
                    if pattern.search(line):
                        counts[category] += 1
                if re.search(r'(?<!\d)403(?!\d)', line):
                    counts["http_403_mentions"] += 1
                if "admin/login" in line:
                    counts["admin_login_mentions"] += 1
            print(f"{filename}: Zeilen im passenden Zeitfenster={len(matches)}")
            for name, number in sorted(counts.items()):
                print(f"  {name}={number}")
    if not available:
        print("HINWEIS: Keine Logs am erwarteten Pfad gefunden. Plesk > Protokolle kontrollieren.")
    print("Es wurden keine Serverlog-Zeilen oder IP-Adressen ausgegeben.")


def main() -> None:
    if os.geteuid() != 0:
        raise SystemExit("STOP: Nur root-PuTTY kann Dateirechte und Logs pruefen.")
    print("=== HW24 TEST-FILAMENT 403: AUSSCHLIESSLICH LESEN ===")
    root = run([
        "plesk", "db", "-Ne",
        "SELECT h.www_root FROM domains d JOIN hosting h ON d.id=h.dom_id "
        "WHERE LOWER(d.name)='test-artikelmanager.hairworld24.de'",
    ])
    if root.returncode:
        raise SystemExit("STOP: Plesk-DocumentRoot ist nicht lesbar.")
    print("1. Aktueller Test-DocumentRoot:")
    if root.stdout.strip() == str(LEGACY):
        print("OK: Automatischer Rollback auf bisherigen TEST-Root bestaetigt.")
    else:
        print("WARNUNG: Test-DocumentRoot nicht wie erwartet. Keine neue Umschaltung!")
    permission_probe()
    check_logs()
    print("=== READ-ONLY-DIAGNOSE BEENDET, KEINE AENDERUNG ===")
    print("Erst nach konkreter Fehlerursache die minimalen Rechte separat korrigieren.")


if __name__ == "__main__":
    main()
