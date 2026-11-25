#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────────────────────────
# ops/secrets.sh — VY-10 / VY-12 (2026-11-25): HIS STEPS, ONE COMMAND EACH. Run by HIM, in his own Terminal.
#
#   ops/secrets.sh mail                 F1 + switch mail on: after he changed the contact@ password in hPanel
#   ops/secrets.sh backup-passphrase    VY-12: the backup passphrase, the first backup, the off-server copy
#   ops/secrets.sh heartbeat            F4 job 3: the Healthchecks ping address
#   ops/secrets.sh app-key              F2: APP_KEY onto the clipboard, for his password manager — never printed
#
# ⚠️⚠️ HARD RULES 1 AND 2: HE TYPES EVERY SECRET. This script asks for it at a HIDDEN prompt (nothing echoed, nothing in the shell
#    history, nothing in a file on this Mac), sends it over SSH on STANDARD INPUT (never an argument — arguments show in `ps`), and
#    prints only the setting's NAME. It refuses to run without a terminal, so no session — and no pipe a session builds — can feed it.
# ⚠️ EVERY CHANGE GOES LIVE THE ONE WAY (rule 8): ops/host/env-set.php writes the .env (the old file kept, the write read back),
#    then `ops/release.sh recache` + `swap`, then the public checks — and a FAIL rolls back to the release that was live before.
# ⚠️ Only one deploy at a time: do not run this while a session is deploying.
# ─────────────────────────────────────────────────────────────────────────────────────────────────
set -euo pipefail

REPO="$(cd "$(dirname "$0")/.." && pwd)"
eval "$(sed -n -e '/^HOST=/p' -e '/^ROOT=/p' "${REPO}/ops/release.sh")"   # HOST PORT KEY ROOT PHP — one definition
: "${HOST:?}" "${PORT:?}" "${KEY:?}" "${ROOT:?}" "${PHP:?}"

ssh_() { ssh -i "${KEY}" -p "${PORT}" -o ConnectTimeout=60 -o BatchMode=yes "${HOST}" "$@"; }
die() { echo "  ✗ $*" >&2; exit 1; }
say() { echo "  $*"; }
step() { echo; echo "── $* ──"; }

CMD="${1:-}"
case "${CMD}" in mail|backup-passphrase|heartbeat|app-key) ;; *)
  sed -n '4,9p' "$0"; exit 2 ;;
esac

# ⚠️ A TERMINAL, OR NOTHING: the secret must come from his keyboard.
[ -t 0 ] && [ -t 1 ] || die "run this yourself, in Terminal — it asks for a secret at a hidden prompt (a session cannot run it)"

ENV_ARGS="--env=\$HOME/${ROOT}/public_html/.env --backups=\$HOME/${ROOT}/env-backups"
TOOL="\$HOME/${ROOT}/public_html/current/ops/host"
ssh_ "[ -f ${TOOL}/env-set.php ] && [ -f ${TOOL}/ops-check.php ]" \
  || die "the live release has no ops/host/env-set.php yet — the release that brings it must be deployed first"

# ask for a secret twice, hidden; it stays in this process's memory only
ask_secret() { # $1 prompt  $2 minimum length  → SECRET
  local again
  read -r -s -p "  $1 (hidden): " SECRET; echo
  [ -n "${SECRET}" ] || die "nothing typed — nothing changed"
  [ "${#SECRET}" -ge "$2" ] || { SECRET=; die "shorter than $2 characters — nothing changed"; }
  read -r -s -p "  Once more, to be sure (hidden): " again; echo
  [ "${SECRET}" = "${again}" ] || { SECRET=; again=; die "the two did not match — nothing changed"; }
  again=
}
confirm() { local a; read -r -p "  $1 [y/N] " a; [ "${a}" = "y" ] || [ "${a}" = "Y" ]; }

# send the secret on STANDARD INPUT (printf is a shell builtin: the value never appears in a process list)
put_secret() { # $1 mode (secret|secret-file)  $2 KEY  [$3 path]
  printf '%s\n' "${SECRET}" | ssh_ "${PHP} ${TOOL}/env-set.php ${ENV_ARGS} $1 $2 ${3:-}"
}
put_plain() { ssh_ "${PHP} ${TOOL}/env-set.php ${ENV_ARGS} set $1 '$2'"; }

next_release() { # rel_CV → rel_CW (… rel_CZ → rel_DA), the first name not taken
  local name="$1" s i c carry out
  while :; do
    s="${name#rel_}"; carry=1; out=""
    for ((i=${#s}-1; i>=0; i--)); do
      c="${s:i:1}"
      if [ "${carry}" -eq 1 ]; then
        if [ "${c}" = "Z" ]; then c="A"; else c="$(printf "\\$(printf '%03o' $(( $(printf '%d' "'${c}") + 1 )))")"; carry=0; fi
      fi
      out="${c}${out}"
    done
    [ "${carry}" -eq 1 ] && out="A${out}"
    name="rel_${out}"
    ssh_ "[ -e ~/${ROOT}/releases/${name} ]" || { echo "${name}"; return; }
  done
}

# recache + swap + public checks; a FAIL rolls back first (rule 8)
go_live() {
  local live next
  live="$(ssh_ "readlink ~/${ROOT}/public_html/current" | sed 's#.*/##')"
  next="$(next_release "${live}")"
  step "making it live: ${live} → ${next} (the same code, the config rebuilt from the .env)"
  "${REPO}/ops/release.sh" recache "${next}"
  "${REPO}/ops/release.sh" swap "${next}"
  step "public checks (a few minutes)"
  set +e; "${REPO}/ops/public-checks.sh"; local rc=$?; set -e
  if [ "${rc}" -eq 1 ]; then
    step "a public check FAILED — rolling back to ${live} first (rule 8)"
    "${REPO}/ops/release.sh" rollback "${live}"
    die "rolled back to ${live}. The .env change is still in the file (and the old .env is kept in env-backups); tell the session."
  fi
  ROLLBACK_TO="${live}"; LIVE_NOW="${next}"
  say "live: ${next} (rollback target ${live})"
}

case "${CMD}" in
# ── VY-10 / F1 + F13 (first half): mail on ─────────────────────────────────────────────────────
mail)
  step "mail — checks first (RULES: never against an unverified domain, never with unreviewed mail waiting)"
  # ⚠️ Read 2026-11-25 from this Mac AND from the server: vyrobel.com had NO MX, SPF, DKIM or DMARC record at all (they existed on
  #    2026-09-25). hPanel's email set-up for the domain adds them; this refuses until all four answer.
  MX="$(dig +short MX vyrobel.com | grep -ci 'hostinger' || true)"
  SPF="$(dig +short TXT vyrobel.com | tr -d '"' | grep -c 'v=spf1.*hostinger' || true)"
  DKIM=0; for SEL in hostingermail-a hostingermail-b hostingermail-c hostingermail; do
    [ "$(dig +short TXT "${SEL}._domainkey.vyrobel.com" | tr -d '"' | grep -c 'p=[A-Za-z0-9]' || true)" -ge 1 ] && DKIM=1; done
  DMARC="$(dig +short TXT _dmarc.vyrobel.com | tr -d '"' | grep -c 'v=DMARC1' || true)"
  yn() { [ "$1" -ge 1 ] && echo yes || echo NO; }
  say "DNS: MX $(yn "${MX}") · SPF $(yn "${SPF}") · DKIM $(yn "${DKIM}") · DMARC $(yn "${DMARC}")"
  [ "${MX}" -ge 1 ] && [ "${SPF}" -ge 1 ] && [ "${DKIM}" -ge 1 ] && [ "${DMARC}" -ge 1 ] \
    || die "the domain's mail records are not all there (hPanel → Emails → vyrobel.com sets them up) — nothing changed"
  PRE="$(ssh_ "${PHP} ${TOOL}/ops-check.php mail-preflight")"; echo "${PRE}" | grep -v '^PREFLIGHT'
  PENDING="$(echo "${PRE}" | sed -n 's/^PREFLIGHT pending=\([0-9]*\).*/\1/p')"
  [ "${PENDING:-x}" = "0" ] || die "${PENDING:-?} mail job(s) are waiting in the queue — they would all go out at once. A session reviews them first; nothing changed."
  confirm "Have you ALREADY changed the contact@vyrobel.com password in hPanel (the old one was exposed — F1)?" \
    || die "change it in hPanel first (Emails → contact@vyrobel.com → Change password), then run this again — nothing changed"

  step "the NEW contact@vyrobel.com password"
  ask_secret "New contact@vyrobel.com password" 8
  put_secret secret MAIL_PASSWORD; SECRET=
  put_plain MAIL_MAILER smtp
  go_live

  step "one test message"
  read -r -p "  Send ONE test message to which address of yours? " TO
  if ssh_ "${PHP} ${TOOL}/ops-check.php mail-send '${TO//\'/}'"; then
    say "✓ mail is ON. Check that inbox (and spam). Tell the session: it records F1 and VY-10 as done and reads the health board."
  else
    step "the test failed — switching mail back OFF (log) so nothing fails silently"
    put_plain MAIL_MAILER log
    "${REPO}/ops/release.sh" rollback "${ROLLBACK_TO}"
    die "rolled back to ${ROLLBACK_TO} (mail off). Usually the password was mistyped: run 'ops/secrets.sh mail' again."
  fi
  ;;

# ── VY-12: the backup passphrase, the first backup, the off-server copy ──────────────────────────
backup-passphrase)
  step "the backup passphrase"
  say "Choose a LONG passphrase (20 characters or more). ⚠️ Without it NO backup can ever be opened —"
  say "put a second copy in your password manager now, before you go on."
  ask_secret "Backup passphrase" 20
  put_secret secret-file OPS_BACKUP_PASSPHRASE_FILE "\$HOME/.vyrobel-secrets/backup-passphrase"; SECRET=
  if confirm "Should THIS Mac keep the off-server copy (it pulls each backup over SSH — ops/backup-pull.sh)?"; then
    put_plain OPS_BACKUP_OFFSITE_PULL "founder laptop"; PULL=1
  else PULL=0; fi
  go_live

  step "the first backup, now (a minute or two)"
  ssh_ "cd ~/${ROOT}/public_html/current && ${PHP} artisan backup:run" || die "backup:run failed — read its last line above; tell the session"
  ssh_ "${PHP} ${TOOL}/ops-check.php backup-status"
  if [ "${PULL}" -eq 1 ]; then
    step "the off-server copy, onto this Mac"
    "${REPO}/ops/backup-pull.sh"
    if confirm "Prove it restores HERE now (asks for the passphrase again; a few minutes)?"; then "${REPO}/ops/backup-pull.sh" --drill; fi
    ssh_ "${PHP} ${TOOL}/ops-check.php backup-status"
  fi
  confirm "Is the second copy of the passphrase in your password manager?" || say "⚠️ do it now — the archives are useless without it."
  ;;

# ── F4 job 3: the heartbeat alarm ────────────────────────────────────────────────────────────────
heartbeat)
  step "the heartbeat address"
  say "In Healthchecks.io: a check with period 1 minute and grace 5 minutes (the scheduler pings every minute; a failed job"
  say "stops the ping on purpose). Alerts to YOUR email or phone — never through vyrobel.com's mail. Copy its ping URL."
  ask_secret "Ping URL (https://hc-ping.com/…)" 20
  put_secret secret OPS_HEARTBEAT_PING_URL; SECRET=
  go_live
  say "✓ within two minutes Healthchecks should show pings arriving. Use its 'Send test notification' to prove the alert reaches you."
  ;;

# ── F2: APP_KEY into his password manager ────────────────────────────────────────────────────────
app-key)
  command -v pbcopy >/dev/null 2>&1 || die "pbcopy not found — this step is for his Mac"
  # ⚠️ straight from the server's .env into the clipboard: never printed, never in a variable that is echoed, never in a file
  ssh_ "grep -E '^APP_KEY=' ~/${ROOT}/public_html/.env | tail -1 | cut -d= -f2- | tr -d '\"\\n'" | pbcopy
  N="$(pbpaste | wc -c | tr -d ' ')"
  [ "${N}" -gt 20 ] || { pbcopy < /dev/null; die "APP_KEY did not arrive — nothing copied"; }
  say "APP_KEY is on your clipboard (${N} characters, starting base64:). Paste it into your password manager NOW."
  say "The clipboard is cleared in 60 seconds."
  sleep 60; pbcopy < /dev/null
  say "clipboard cleared."
  ;;
esac
