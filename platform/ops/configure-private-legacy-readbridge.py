#!/usr/bin/env python3
"""Safely configure ONLY the private Laravel read-only LIVE Basic-auth bridge.

Interactively asks for the existing nvoss Basic password. Never prints, logs,
commits, passes it in argv, or sends it outside the pinned HTTPS LIVE host.
No database writes, site deployment, user creation, or Laravel migrations.
"""
from __future__ import annotations

import base64
import getpass
import json
import os
from pathlib import Path
import pwd
import re
import shutil
import ssl
import stat
import subprocess
import sys
import tempfile
from datetime import datetime, timezone
from urllib.error import HTTPError, URLError
from urllib.request import HTTPRedirectHandler, Request, build_opener, HTTPSHandler

APP = Path("/var/www/vhosts/hairworld24.de/hw24-console-private/repo/platform")
REPO = APP.parent
ENV = APP / ".env"
CACHE = APP / "bootstrap/cache/config.php"
BACKUPS = Path("/root/hw24-backups")
PHP = "/opt/plesk/php/8.5/bin/php"
OWNER = "nicovoss"
EXPECTED_HEAD = "a26a64d09597e41523e224c557bb1d4e39bdf052"
BASE_URL = "https://artikelmanager.hairworld24.de"
USER = "nvoss"
EAN = "3474630337398"
ENV_KEYS = {
    "HW24_LEGACY_URL",
    "HW24_LEGACY_BASIC_USER",
    "HW24_LEGACY_BASIC_PASSWORD",
}


class Stop(Exception):
    pass


class NoRedirects(HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


def git(args: list[str]) -> str:
    r = subprocess.run(
        ["runuser", "-u", OWNER, "--", "git", "-C", str(REPO), *args],
        text=True, capture_output=True, timeout=20, check=False,
    )
    if r.returncode:
        raise Stop("Privater Git-Stand nicht lesbar. Keine Änderung.")
    return r.stdout.strip()


def env_quote(value: str) -> str:
    if not value or any(ord(c) < 32 or ord(c) == 127 for c in value):
        raise Stop("Zugangswert ist leer oder enthält Steuerzeichen.")
    if len(value) > 512:
        raise Stop("Zugangswert ist unerwartet lang.")
    # Dotenv double-quoted value. Validate Laravel's actual decoded value
    # before accepting the update; on mismatch the backup is restored.
    return json.dumps(value, ensure_ascii=False)


def render_env(original: str, updates: dict[str, str]) -> str:
    if "\r" in original:
        raise Stop("Ungewöhnliche CRLF-Zeilenumbrüche; .env wird nicht verändert.")
    lines = original.splitlines(keepends=True)
    found: set[str] = set()
    output: list[str] = []
    for line in lines:
        m = re.match(r"^\s*(HW24_LEGACY_URL|HW24_LEGACY_BASIC_USER|HW24_LEGACY_BASIC_PASSWORD)\s*=", line)
        if m:
            key = m.group(1)
            if key in found:
                raise Stop("Doppelter Laravel-Bridge-Konfigurationsschlüssel. Keine Änderung.")
            found.add(key)
            output.append(key + "=" + env_quote(updates[key]) + "\n")
        else:
            output.append(line)
    missing = ENV_KEYS - found
    if missing:
        if output and not output[-1].endswith("\n"):
            output.append("\n")
        for key in sorted(missing):
            output.append(key + "=" + env_quote(updates[key]) + "\n")
    result = "".join(output)
    if "APP_KEY=" not in result or "DB_CONNECTION=" not in result:
        raise Stop("Erwartete bestehende Laravel-Konfiguration fehlt. Keine Änderung.")
    return result


def atomic_env_write(content: bytes, original_stat: os.stat_result) -> None:
    fd, tmp = tempfile.mkstemp(prefix=".hw24-env-update-", dir=str(APP))
    try:
        os.fchmod(fd, stat.S_IMODE(original_stat.st_mode))
        os.fchown(fd, original_stat.st_uid, original_stat.st_gid)
        with os.fdopen(fd, "wb") as f:
            f.write(content)
            f.flush()
            os.fsync(f.fileno())
        os.replace(tmp, ENV)
    finally:
        if os.path.exists(tmp):
            os.unlink(tmp)


def root_backup(path: Path, source: bytes, suffix: str) -> Path:
    BACKUPS.mkdir(mode=0o700, parents=True, exist_ok=True)
    if stat.S_IMODE(BACKUPS.stat().st_mode) != 0o700:
        raise Stop("Root-Backupverzeichnis nicht auf 0700 geschützt.")
    when = datetime.now(timezone.utc).strftime("%Y%m%dT%H%M%SZ")
    fd, location = tempfile.mkstemp(
        prefix="hw24-private-bridge-before-" + when + "-",
        suffix=suffix, dir=str(BACKUPS),
    )
    with os.fdopen(fd, "wb") as f:
        os.fchmod(f.fileno(), 0o600)
        f.write(source)
        f.flush()
        os.fsync(f.fileno())
    return Path(location)


def verified_live_basic(password: str) -> None:
    url = BASE_URL + "/api/jtl.php?ean=" + EAN
    encoded = base64.b64encode((USER + ":" + password).encode("utf-8")).decode("ascii")
    request = Request(
        url,
        headers={"Authorization": "Basic " + encoded, "Accept": "application/json"},
        method="GET",
    )
    # Intentionally disallow redirects so Basic credentials are never forwarded.
    opener = build_opener(HTTPSHandler(context=ssl.create_default_context()), NoRedirects)
    try:
        with opener.open(request, timeout=20) as response:
            if response.status != 200:
                raise Stop("LIVE-Authentifizierung: unerwarteter HTTP-Status.")
            raw = response.read(2 * 1024 * 1024 + 1)
            if len(raw) > 2 * 1024 * 1024:
                raise Stop("LIVE-API-Antwort unerwartet groß.")
        payload = json.loads(raw)
        if not isinstance(payload, dict) or payload.get("ok") is not True:
            raise Stop("LIVE-API konnte nicht verifiziert werden.")
    except HTTPError as e:
        raise Stop("LIVE-Authentifizierung fehlgeschlagen: HTTP " + str(e.code) + ".")
    except (URLError, OSError, TimeoutError, ValueError):
        raise Stop("LIVE-HTTPS-Verbindung nicht verifizierbar. Keine Konfiguration geändert.")
    print("OK: Existierender LIVE-Basic-Zugang wurde über geschützten JTL-GET bestätigt.")


PHP_CHECK = r"""
chdir($argv[1]);
require "vendor/autoload.php";
$app=require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$expected=json_decode(stream_get_contents(STDIN),true,512,JSON_THROW_ON_ERROR);
$cfg=config("hw24_legacy");
if(!is_array($cfg) ||
   !hash_equals($expected["url"],(string)($cfg["base_url"]??"")) ||
   !hash_equals($expected["user"],(string)($cfg["user"]??"")) ||
   !hash_equals($expected["password"],(string)($cfg["password"]??"")))exit(4);
if(config("database.default")!=="sqlite" ||
   Illuminate\Support\Facades\DB::connection()->getDriverName()!=="sqlite" ||
   realpath((string)Illuminate\Support\Facades\DB::connection()->getDatabaseName())!==
   realpath($argv[2]))exit(5);
echo "OK: Laravel lädt alle drei Bridge-Konfigurationswerte exakt und verwendet SQLite.\n";
"""


def check_php_config(password: str) -> None:
    r = subprocess.run(
        ["runuser", "-u", OWNER, "--", PHP, "-r", PHP_CHECK,
         str(APP), str(APP / "database/database.sqlite")],
        input=json.dumps({"url": BASE_URL, "user": USER, "password": password},
                         ensure_ascii=False),
        text=True, capture_output=True, timeout=30, check=False,
    )
    if r.returncode:
        # Do NOT print PHP stderr: user config/errors could contain secrets.
        raise Stop("Laravel übernimmt die Werte nicht exakt oder SQLite-Prüfung fehlgeschlagen.")
    print("OK: Laravel lädt URL, Benutzer und Passwort korrekt, weiterhin private SQLite.")


def run_config_clear() -> None:
    r = subprocess.run(
        ["runuser", "-u", OWNER, "--", PHP, "artisan", "config:clear", "--no-interaction"],
        cwd=str(APP), text=True, capture_output=True, timeout=30, check=False,
    )
    if r.returncode:
        raise Stop("Laravel-Konfigurationscache konnte nicht geleert werden.")


def main() -> None:
    if os.geteuid() != 0:
        raise Stop("Bitte als root in PuTTY ausführen.")
    if not sys.stdin.isatty() or not sys.stderr.isatty():
        raise Stop("Interaktive PuTTY-Sitzung erforderlich; Passwort nie als Argument übergeben.")
    if not os.access(PHP, os.X_OK):
        raise Stop("PHP-8.5-Binary nicht vorhanden.")
    owner = pwd.getpwnam(OWNER)
    if git(["rev-parse", "HEAD"]) != EXPECTED_HEAD:
        raise Stop("Privater Git-HEAD unerwartet. Keine Änderung.")
    if git(["status", "--porcelain", "--untracked-files=no"]) != "":
        raise Stop("Getrackte lokale Framework-Änderungen. Keine Änderung.")
    if not ENV.is_file() or ENV.is_symlink() or CACHE.is_symlink():
        raise Stop("Private .env fehlt oder Symlink festgestellt.")
    original_stat = ENV.stat()
    if original_stat.st_uid != owner.pw_uid or stat.S_IMODE(original_stat.st_mode) != 0o600:
        raise Stop("Die .env ist nicht 0600 und Eigentum von nicovoss. Keine Änderung.")
    if not (APP / "database/database.sqlite").is_file():
        raise Stop("Private Plattform-SQLite fehlt.")
    if not (APP / "vendor/autoload.php").is_file():
        raise Stop("Laravel-Abhängigkeiten fehlen.")

    original = ENV.read_bytes()
    if b"\x00" in original:
        raise Stop("Unerwartete binäre .env. Keine Änderung.")
    old_text = original.decode("utf-8")
    # Do not display the current configuration or existing passwords.
    print("=== HW24 LARAVEL: PRIVATE LIVE-LESEBRÜCKE EINRICHTEN ===")
    print("Geprüfter Git-Stand und private .env-Berechtigungen: OK")
    print("Es wird nur die bestehende HTTP-Basic-Anmeldung nvoss verwendet.")
    password = getpass.getpass("LIVE-Passwort für nvoss verdeckt eingeben: ")
    if not password:
        raise Stop("Kein Passwort eingegeben. Keine Änderung.")
    # Verify real credentials before any disk write.
    verified_live_basic(password)

    updates = {
        "HW24_LEGACY_URL": BASE_URL,
        "HW24_LEGACY_BASIC_USER": USER,
        "HW24_LEGACY_BASIC_PASSWORD": password,
    }
    new_text = render_env(old_text, updates)
    existing_cache = CACHE.read_bytes() if CACHE.is_file() else None
    if existing_cache is not None and not os.access(CACHE, os.R_OK):
        raise Stop("Laravel-Config-Cache ist nicht lesbar.")
    backup = root_backup(ENV, original, ".env")
    cache_backup = None
    if existing_cache is not None:
        cache_backup = root_backup(CACHE, existing_cache, ".config.php")

    print("Private Konfigurationssicherung erstellt:", str(backup))
    replaced = False
    cache_cleared = False
    try:
        atomic_env_write(new_text.encode("utf-8"), original_stat)
        replaced = True
        if existing_cache is not None:
            run_config_clear()
            cache_cleared = True
        check_php_config(password)
        print("=== LARAVEL-LESEBRÜCKE SICHER KONFIGURIERT ===")
        print("Alle anderen .env-Einträge bleiben erhalten; keine DB-/Website-Änderung.")
    except Exception:
        if replaced:
            atomic_env_write(original, original_stat)
        if existing_cache is not None:
            # Restore the original cache without exposing its private values.
            cache_stat = CACHE.stat() if CACHE.exists() else None
            fd, tmp = tempfile.mkstemp(prefix=".hw24-cache-restore-", dir=str(CACHE.parent))
            try:
                os.fchmod(fd, 0o600 if cache_stat is None else stat.S_IMODE(cache_stat.st_mode))
                os.fchown(fd, original_stat.st_uid, original_stat.st_gid)
                with os.fdopen(fd, "wb") as f:
                    f.write(existing_cache)
                    f.flush()
                    os.fsync(f.fileno())
                os.replace(tmp, CACHE)
            finally:
                if os.path.exists(tmp):
                    os.unlink(tmp)
        print("RÜCKNAHME: Ursprüngliche .env und ggf. Config-Cache wiederhergestellt.")
        raise


def self_test() -> None:
    sample = "APP_KEY=base64:example\nDB_CONNECTION=sqlite\nEXTRA=untouched\n"
    rendered = render_env(sample, {
        "HW24_LEGACY_URL": BASE_URL,
        "HW24_LEGACY_BASIC_USER": USER,
        "HW24_LEGACY_BASIC_PASSWORD": "example:$#@!/?",
    })
    assert "EXTRA=untouched\n" in rendered
    assert rendered.count("HW24_LEGACY_URL=") == 1
    assert rendered.count("HW24_LEGACY_BASIC_PASSWORD=") == 1
    twice = render_env(rendered, {
        "HW24_LEGACY_URL": BASE_URL,
        "HW24_LEGACY_BASIC_USER": USER,
        "HW24_LEGACY_BASIC_PASSWORD": "second-password",
    })
    assert twice.count("HW24_LEGACY_BASIC_PASSWORD=") == 1
    assert "EXTRA=untouched\n" in twice
    try:
        render_env(rendered + "HW24_LEGACY_URL=x\n", {
            "HW24_LEGACY_URL": BASE_URL,
            "HW24_LEGACY_BASIC_USER": USER,
            "HW24_LEGACY_BASIC_PASSWORD": "abc",
        })
    except Stop:
        pass
    else:
        raise AssertionError("Duplicate env keys must fail closed")
    print("HW24 private .env render safeguards: PASS")


if __name__ == "__main__":
    try:
        if sys.argv[1:] == ["--self-test"]:
            self_test()
        elif len(sys.argv) == 1:
            main()
        else:
            raise Stop("Keine zusätzlichen Argumente erlaubt, niemals Passwörter anhängen.")
    except (Stop, UnicodeError) as exc:
        print("STOP:", str(exc), file=sys.stderr)
        sys.exit(2)
    except Exception as exc:
        # Never show exception messages that might contain credentials or file content.
        print("STOP: Sicherer Konfigurationsvorgang abgebrochen (" +
              type(exc).__name__ + ").", file=sys.stderr)
        sys.exit(2)
