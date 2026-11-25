#!/usr/bin/env bash
# Root-owned wrapper installed outside checkout; runs exclusively as nicovoss.
set -Eeuo pipefail
umask 077
[[ $(id -un) == nicovoss ]]
state='/var/www/vhosts/hairworld24.de/hw24-console-private/deploy-state'
repo='/var/www/vhosts/hairworld24.de/hw24-console-private/repo'
runner='/usr/local/sbin/hw24-test-autodeploy'
[[ -d "$state" && ! -L "$state" && "$(stat -c '%U %a' "$state")" == 'nicovoss 700' ]]
exec 8>"$state/monitor.lock"
flock -n 8 || exit 0
stamp(){ date -u +%FT%TZ; }
headsha(){ git -C "$repo" rev-parse HEAD 2>/dev/null || printf unknown; }
notify(){
    local subject="$1" body="$2" addr
    [[ -f "$state/notify.email" && ! -L "$state/notify.email" ]] || return 1
    addr=$(cat "$state/notify.email")
    [[ "$addr" =~ ^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,63}$ && "$addr" != -* ]] || return 1
    [[ -x /usr/sbin/sendmail ]] || return 1
    printf 'To: %s\nSubject: %s\nContent-Type: text/plain; charset=UTF-8\n\n%s\n' "$addr" "$subject" "$body" | /usr/bin/timeout 20 /usr/sbin/sendmail -oi -- "$addr"
}
if [[ "${1:-}" == '--test-notification' ]]; then
    notify '[HW24 TEST] Benachrichtigungstest' "Test der Deployment-Ueberwachung: $(stamp). Kein Fehler und kein Rollback."
    echo 'Testnachricht vom lokalen Maildienst angenommen; Posteingang kontrollieren.'
    exit 0
fi
[[ $# == 0 ]]
before=$(headsha)
rc=0
/usr/bin/timeout -k 15 150 "$runner" > "$state/monitor-run.log" 2>&1 || rc=$?
raw=$(tail -n 1 "$state/status" 2>/dev/null || true)
after=$(headsha)
case "$raw" in
    *'FAILED: rollback to '*) kind=ROLLBACK ;;
    *'FAILED:'*) kind=FAILED ;;
    *'BLOCKED:'*) kind=BLOCKED ;;
    *'DEPLOYING '*) kind=FAILED ;;
    *'DEPLOYED '*) kind=DEPLOYED ;;
    *'CURRENT '*) kind=CURRENT ;;
    *) kind=FAILED ;;
esac
if [[ "$rc" == 124 || "$rc" == 137 ]]; then kind=FAILED; raw='Deployment timeout; Zustand manuell pruefen.'; fi
if [[ "$rc" != 0 && "$kind" == CURRENT || "$rc" != 0 && "$kind" == DEPLOYED ]]; then kind=FAILED; raw="Runner exit $rc; Status widerspruechlich."; fi
if [[ "$kind" == FAILED && "$raw" == *'DEPLOYING '* ]]; then raw="Deployment nicht abgeschlossen: $raw"; fi
# Strip line breaks and bound messages; never copy logs, credentials or environment into alerts.
raw=$(printf '%s' "$raw" | tr -cd '[:print:]' | cut -c1-360)
detail=$(printf '%s' "$raw" | sed -E 's/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9:]+Z //')
now=$(stamp)
printf '%s %s HEAD=%s %s\n' "$now" "$kind" "$after" "$detail" > "$state/monitor.status"
fingerprint=$(printf '%s\n' "$kind" "$after" "$detail" | sha256sum | cut -d' ' -f1)
last=$(cat "$state/monitor.last" 2>/dev/null || true)
if [[ "$fingerprint" != "$last" ]]; then
    line="$now $kind HEAD=$after $detail"
    printf '%s\n' "$line" >> "$state/deploy-history.log"
    printf '%s\n' "$fingerprint" > "$state/monitor.last"
    if [[ "$kind" == FAILED || "$kind" == ROLLBACK || "$kind" == BLOCKED ]]; then
        printf '%s\n' "$line" >> "$state/incidents.log"
        printf '%s\n' "$line" > "$state/last-incident"
        printf '%s\n' "$line" >> "$state/mail-pending"
    fi
fi
# Retry unsent alerts, preserving distinct incidents even if later releases succeed.
if [[ -s "$state/mail-pending" ]]; then
    if notify '[HW24 TEST] Deployment-Warnung / Rollback' "$(cat "$state/mail-pending")"; then
        printf '%s Maildienst hat Warnung angenommen\n' "$now" >> "$state/notification.log"
        : > "$state/mail-pending"
    else
        printf '%s Mailversand fehlgeschlagen oder nicht eingerichtet; Warnung bleibt offen\n' "$now" >> "$state/notification.log"
    fi
fi
exit "$rc"
