#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────────────────────────
# ops/backup-pull.sh — VY-12 (2026-11-25): THE OFF-SERVER COPY, PULLED. Run on HIS MAC (or this session's machine).
#
#   ops/backup-pull.sh            copy the newest encrypted archive off the server, prove it, mark it
#   ops/backup-pull.sh --drill    the same, then restore it here and prove it (ops/restore-drill.sh, facts mode) —
#                                 asks for the backup passphrase at a hidden prompt
#
# ⚠️ WHY A PULL: the audit's step was "a disk on another provider" — that needs his account there and one new package
#    (his yes). A pull needs neither: the server already lets this machine in over SSH (the deploy key), and an archive is
#    useless without the passphrase, so a laptop holding the ciphertext is safe. When he picks a provider, OPS_BACKUP_OFFSITE_DISK
#    still works; the two can run side by side.
# ⚠️ A COPY COUNTS ONLY ONCE IT IS PROVEN TO BE THAT FILE: the checksum written beside the archive at backup time, the server's
#    own fresh checksum, and this machine's checksum of what arrived must all agree — only then is the server's off-server
#    marker written (the console's Backups card reads it). A failed or partial copy writes nothing, and the card stays amber.
# ⚠️ NOTHING SECRET IS READ OR WRITTEN HERE: archives are already encrypted; the passphrase is only ever typed into the drill.
# ⚠️ IT ONLY READS on the server, except that one marker file.
# ─────────────────────────────────────────────────────────────────────────────────────────────────
set -euo pipefail

REPO="$(cd "$(dirname "$0")/.." && pwd)"
# the host, port, key, root and PHP binary — read from ops/release.sh, ONE definition
eval "$(sed -n -e '/^HOST=/p' -e '/^ROOT=/p' "${REPO}/ops/release.sh")"
: "${HOST:?}" "${PORT:?}" "${KEY:?}" "${ROOT:?}"
COPIES="${VY_BACKUP_COPY_DIR:-${HOME}/vyrobel-local/backups}"
KEEP="${VY_BACKUP_COPY_KEEP:-14}"
DRILL=0; [ "${1:-}" = "--drill" ] && DRILL=1

if [ -n "${VY_PULL_FAKE_SERVER:-}" ]; then
  # ⚠️ TESTS ONLY (tests/Feature/Vy12BackupPullTest.php): the "server" is a local folder standing for the account's home —
  #    nothing leaves this machine. Every other line runs exactly as it does against production.
  ssh_() { HOME="${VY_PULL_FAKE_SERVER}" bash -c "$*"; }
  fetch() { cp "$1" "$2"; }
else
  ssh_() { ssh -i "${KEY}" -p "${PORT}" -o ConnectTimeout=60 -o BatchMode=yes "${HOST}" "$@"; }
  fetch() { scp -q -i "${KEY}" -P "${PORT}" -o BatchMode=yes "${HOST}:$1" "$2"; }
fi
die() { echo "  ✗ $*" >&2; exit 1; }
say() { echo "  $*"; }
if command -v sha256sum >/dev/null 2>&1; then SHA=(sha256sum); else SHA=(shasum -a 256); fi

# ── 1. the newest archive on the server, and its two checksums ───────────────────────────────────
# the backup folder: OPS_BACKUP_DEST when the .env sets it, else the shared storage/backups (what BackupStatus::dest() reads)
REMOTE="$(ssh_ "set -e
  cd ~/${ROOT}/public_html
  D=\$(grep -E '^OPS_BACKUP_DEST=' .env | tail -1 | cut -d= -f2- | tr -d '\"' || true)
  [ -n \"\$D\" ] || D=\$(cd current/storage && pwd -P)/backups
  [ -d \"\$D\" ] || { echo \"NODIR \$D\"; exit 0; }
  A=\$(ls -t \"\$D\"/vyrobel-*.tar.gz.enc 2>/dev/null | head -1 || true)
  [ -n \"\$A\" ] || { echo \"NONE \$D\"; exit 0; }
  W=\$(cut -d' ' -f1 \"\$A.sha256\" 2>/dev/null || echo missing)
  if command -v sha256sum >/dev/null 2>&1; then N=\$(sha256sum \"\$A\" | cut -d' ' -f1); else N=\$(shasum -a 256 \"\$A\" | cut -d' ' -f1); fi
  echo \"OK \$D \$(basename \"\$A\") \$(wc -c < \"\$A\" | tr -d ' ') \$W \$N\"")"
set -- ${REMOTE}
case "${1:-}" in
  NODIR) die "no backup folder on the server yet ($2) — the backup has not run: his step, ops/secrets.sh backup-passphrase" ;;
  NONE)  die "no archive in $2 yet — the backup has not run: his step, ops/secrets.sh backup-passphrase" ;;
  OK)    ;;
  *)     die "the server gave an answer this script does not understand" ;;
esac
DIR="$2"; NAME="$3"; BYTES="$4"; WRITTEN="$5"; NOW="$6"
[[ "${NAME}" =~ ^vyrobel-[0-9]{8}T[0-9]{6}Z\.tar\.gz\.enc$ ]] || die "unexpected archive name: ${NAME}"
[ "${WRITTEN}" = "${NOW}" ] || die "${NAME}: the checksum written at backup time (${WRITTEN:0:16}…) is not the file's checksum now (${NOW:0:16}…) — not copied"
say "server  : ${NAME}  (${BYTES} bytes, sha256 ${NOW:0:16}…)"

# ── 2. copy it here, and prove it is the same file ───────────────────────────────────────────────
mkdir -p "${COPIES}"; chmod 700 "${COPIES}"
if [ -f "${COPIES}/${NAME}" ] && [ "$("${SHA[@]}" "${COPIES}/${NAME}" | cut -d' ' -f1)" = "${NOW}" ]; then
  say "here    : already copied and proven (${COPIES}/${NAME})"
else
  PART="${COPIES}/.${NAME}.part"
  rm -f "${PART}"
  fetch "${DIR}/${NAME}" "${PART}" || { rm -f "${PART}"; die "the copy failed — nothing marked"; }
  GOT="$("${SHA[@]}" "${PART}" | cut -d' ' -f1)"
  [ "${GOT}" = "${NOW}" ] || { rm -f "${PART}"; die "the copy's checksum (${GOT:0:16}…) is not the server's (${NOW:0:16}…) — discarded, nothing marked"; }
  chmod 600 "${PART}"; mv "${PART}" "${COPIES}/${NAME}"
  printf '%s  %s\n' "${NOW}" "${NAME}" > "${COPIES}/${NAME}.sha256"
  say "here    : ${COPIES}/${NAME}  — proven: the same sha256 on both sides"
fi

# ── 3. only now, the server's off-server marker ──────────────────────────────────────────────────
STAMP="$(date -u +%Y-%m-%dT%H:%M:%S+00:00)"
ssh_ "set -e; cd '${DIR}' && printf '%s' '${STAMP}' > .offsite-last.tmp && mv .offsite-last.tmp .offsite-last"
say "marked  : the server's Backups card now reads an off-server copy made ${STAMP}"

# ── 4. keep the newest ${KEEP} copies here ─────────────────────────────────────────────────────
ls -t "${COPIES}"/vyrobel-*.tar.gz.enc 2>/dev/null | tail -n "+$((KEEP+1))" | while read -r OLD; do rm -f "${OLD}" "${OLD}.sha256"; done
say "kept    : $(ls "${COPIES}"/vyrobel-*.tar.gz.enc 2>/dev/null | wc -l | tr -d ' ') copies in ${COPIES} (newest ${KEEP} kept)"

# ── 5. --drill: restore it HERE and prove it ─────────────────────────────────────────────────────
if [ "${DRILL}" -eq 1 ]; then
  # production runs MariaDB 11.8; its dump starts with a MariaDB-only command, so it is restored into MariaDB 11.8 here too —
  # this Mac's own copy, on a port of its own (3319: 3306 and 3307 belong to MySQL servers, this project's and another's)
  # ⚠️ its OWN data folder, made fresh and DELETED afterwards: production's rows never sit in a shared datadir's free pages (the
  #    project's engine gate uses /opt/homebrew/var/mariadb118 — never this) — review 2026-11-25, finding 8
  MDB="${VY_DRILL_MARIADB:-/opt/homebrew/opt/mariadb@11.8}"; MDATA="${HOME}/vyrobel-local/drill-mariadb-$$"
  MPORT="${VY_DRILL_PORT:-3319}"; MSOCK="/tmp/vyrobel-drill-${MPORT}.sock"
  [ -x "${MDB}/bin/mariadbd-safe" ] || die "no MariaDB 11.8 at ${MDB} — set VY_DRILL_MARIADB"
  STARTED=0
  if "${MDB}/bin/mariadb" --skip-ssl-verify-server-cert -h 127.0.0.1 -P "${MPORT}" -u root -e 'SELECT 1' >/dev/null 2>&1; then
    die "something already answers on 127.0.0.1:${MPORT} — the drill uses a fresh server of its own there; free the port (VY_DRILL_PORT)"
  fi
  if true; then
    "${MDB}/bin/mariadb-install-db" --no-defaults --basedir="${MDB}" --datadir="${MDATA}" --auth-root-authentication-method=normal \
      >/dev/null 2>&1 || die "could not make the drill's own data folder (${MDATA})"
    say "drill   : starting a fresh MariaDB 11.8 on 127.0.0.1:${MPORT} (its own data folder, deleted afterwards)"
    "${MDB}/bin/mariadbd-safe" --no-defaults --basedir="${MDB}" --datadir="${MDATA}" --port="${MPORT}" \
      --bind-address=127.0.0.1 --socket="${MSOCK}" >/dev/null 2>&1 &
    STARTED=1
    for _ in $(seq 1 30); do "${MDB}/bin/mariadb" --skip-ssl-verify-server-cert -h 127.0.0.1 -P "${MPORT}" -u root -e 'SELECT 1' >/dev/null 2>&1 && break; sleep 1; done
  fi
  stop_mariadb() {
    [ "${STARTED}" -eq 1 ] && "${MDB}/bin/mariadb-admin" -h 127.0.0.1 -P "${MPORT}" -u root shutdown >/dev/null 2>&1 || true
    for _ in $(seq 1 20); do [ -e "${MSOCK}" ] || break; sleep 1; done
    case "${MDATA}" in "${HOME}/vyrobel-local/drill-mariadb-"*) rm -rf "${MDATA}" ;; esac
  }
  trap stop_mariadb EXIT
  V="$("${MDB}/bin/mariadb" --skip-ssl-verify-server-cert -h 127.0.0.1 -P "${MPORT}" -u root -sN -e 'SELECT VERSION()')" || die "MariaDB did not answer on ${MPORT}"
  case "${V}" in *MariaDB*) say "drill   : restoring into ${V} on 127.0.0.1:${MPORT}" ;; *) die "port ${MPORT} answered ${V}, not MariaDB — not drilling there" ;; esac
  RESTORE_AGAINST=facts RESTORE_DB_HOST=127.0.0.1 RESTORE_DB_PORT="${MPORT}" RESTORE_DB_USER=root RESTORE_DB_PASS= \
    RESTORE_MYSQL_BIN="${MDB}/bin/mariadb" RESTORE_MYSQL_OPTS="--skip-ssl-verify-server-cert" "${REPO}/ops/restore-drill.sh" "${COPIES}/${NAME}"
fi
