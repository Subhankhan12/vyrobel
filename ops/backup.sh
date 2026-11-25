#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────────────────────────
# Vyrobel — DEPLOY §7.1 · the backup half. The RESTORE half is ops/restore-drill.sh, and it is the
# one that proves this file works: an unrestored backup is a hypothesis, not a backup.
#
# ⚠️ WHAT IS UNRECOVERABLE IF LOST, and therefore what this captures:
#   1. THE DATABASE — every folio, ledger, payout and statement. 130 tables.
#   2. storage/app — the Media blobs, INCLUDING guest ID documents that are encrypted at rest
#      (GuestIdDocumentService). These exist only on the volume; nothing regenerates them.
# Deliberately NOT captured (regenerable, or ephemeral by design):
#   · Redis / Horizon queue state — in-flight jobs, not a system of record
#   · bootstrap/cache, storage/framework, vendor/, node_modules/ — all rebuilt by a deploy
#
# ⚠️⚠️ APP_KEY IS NOT IN HERE, AND THAT IS DELIBERATE — READ THIS BEFORE "FIXING" IT.
# Guest email/phone columns and the ID-document blobs are encrypted with APP_KEY. So:
#   · WITHOUT it, this backup restores rows whose PII is permanently unreadable.
#   · WITH it stored beside the backup, the encryption protecting that PII is worthless — one stolen
#     archive would carry both the ciphertext and its key.
# The key therefore belongs in a SEPARATE secret store the founder controls, and losing it is as
# fatal as losing the backup. §7.1 in docs/DEPLOYMENT.md states that as a founder step.
#
# ⚠️ THE ARCHIVE IS ENCRYPTED, AND THIS SCRIPT REFUSES TO RUN WITHOUT A PASSPHRASE. It contains guest
# personal data; writing it in the clear would be a GDPR breach committed by the backup itself.
# ─────────────────────────────────────────────────────────────────────────────────────────────────
set -euo pipefail

APP_DIR="${APP_DIR:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
DEST="${BACKUP_DEST:-${APP_DIR}/storage/backups}"
RETAIN_DAYS="${BACKUP_RETAIN_DAYS:-14}"
STAMP="$(date -u +%Y%m%dT%H%M%SZ)"

# Read DB credentials from .env rather than duplicating them (one definition).
env_get() { grep -E "^${1}=" "${APP_DIR}/.env" 2>/dev/null | head -1 | cut -d= -f2- | tr -d '"'"'"'' ; }
DB_HOST="$(env_get DB_HOST)"; DB_PORT="$(env_get DB_PORT)"; DB_NAME="$(env_get DB_DATABASE)"
DB_USER="$(env_get DB_USERNAME)"; DB_PASS="$(env_get DB_PASSWORD)"
: "${DB_NAME:?DB_DATABASE missing from .env}"

if [ -z "${BACKUP_PASSPHRASE:-}" ]; then
  echo "REFUSING: BACKUP_PASSPHRASE is not set." >&2
  echo "  This archive contains guest personal data (encrypted ID documents, PII columns)." >&2
  echo "  Writing it unencrypted would be a data breach performed by the backup itself." >&2
  exit 2
fi

mkdir -p "${DEST}"
WORK="$(mktemp -d)"; trap 'rm -rf "${WORK}"' EXIT

# ── THE FACTS A RESTORER CHECKS AGAINST (VY-12, 2026-11-25) ─────────────────────────────────────────
# ⚠️ ops/restore-drill.sh used to compare a restore with the LIVE source database — which only exists on the server, so the drill
#    could never run anywhere else, and the off-server copy could never be proven. These facts travel INSIDE the encrypted archive:
#    every table's row count, three money sums, the first record of three money tables field by field, and the foreign-key count.
# ⚠️ Taken BEFORE and AFTER the dump. The dump is one consistent snapshot; these queries are not part of it — so a figure that is
#    the same before and after is the figure the dump holds, and one that moved during the dump is written as `moved` and not
#    compared (a drill must never fail a good archive, or pass a bad one, on a row written mid-dump).
# ⚠️ The spot-check and sum lists are the SAME as the drill's own (ops/restore-drill.sh) — a test keeps them in step.
SPOT_PAIRS="reservations:id,reference,status,check_in,check_out,currency payouts:id,amount_minor,currency,status,owner_id owner_statements:id,net_payable_minor,gross_minor,status"
SUM_COLS="payouts.amount_minor owner_statements.net_payable_minor folio_items.amount_minor"
# ⚠️ the DB password reaches the clients through MYSQL_PWD (their environment — readable only by this account), never `-p…` on a
#    command line, which any process listing shows (review 2026-11-25, finding 5)
q() { MYSQL_PWD="${DB_PASS}" mysql -sN --default-character-set=utf8mb4 ${DB_HOST:+-h "${DB_HOST}"} ${DB_PORT:+-P "${DB_PORT}"} \
        -u "${DB_USER:-root}" "${DB_NAME}" -e "$1"; }
facts() {
  local tables sql="" t pair cols col tab
  tables=$(q "SELECT table_name FROM information_schema.tables WHERE table_schema=DATABASE() AND table_type='BASE TABLE' ORDER BY table_name;")
  for t in ${tables}; do
    [ -z "${sql}" ] || sql="${sql} UNION ALL "
    sql="${sql}SELECT 'table','${t}',COUNT(*) FROM \`${t}\`"
  done
  [ -z "${sql}" ] || q "${sql};"
  # ⚠️ a plain string test, NOT `printf | grep -q`: under pipefail an early-exiting grep can SIGPIPE the writer and the test reads
  #    as FALSE — the fact would be skipped in silence
  has_table() { [[ $'\n'"${tables}"$'\n' == *$'\n'"$1"$'\n'* ]]; }
  for col in ${SUM_COLS}; do
    tab="${col%%.*}"
    if has_table "${tab}"; then q "SELECT 'sum','${col}',COALESCE(SUM(${col#*.}),0) FROM \`${tab}\`;"; fi
  done
  for pair in ${SPOT_PAIRS}; do
    tab="${pair%%:*}"; cols="${pair#*:}"
    if has_table "${tab}"; then
      q "SELECT 'spot','${tab}',CONCAT_WS('|',${cols}) FROM \`${tab}\` ORDER BY id LIMIT 1;"
    fi
  done
  q "SELECT 'fks','all',COUNT(*) FROM information_schema.referential_constraints WHERE constraint_schema=DATABASE();"
}

echo "→ reading the facts (before the dump) …"
facts > "${WORK}/facts.before"

# ⚠️ --single-transaction: a CONSISTENT snapshot on InnoDB without locking the site. Without it a dump
# taken mid-booking can contain a reservation whose folio rows are from a different instant.
# --routines/--triggers/--events so the schema comes back whole, not just the rows.
echo "→ dumping ${DB_NAME} …"
MYSQL_PWD="${DB_PASS}" mysqldump --single-transaction --quick --routines --triggers --events \
  --default-character-set=utf8mb4 \
  ${DB_HOST:+-h "${DB_HOST}"} ${DB_PORT:+-P "${DB_PORT}"} \
  -u "${DB_USER:-root}" "${DB_NAME}" > "${WORK}/database.sql"

echo "→ reading the facts (after the dump) …"
facts > "${WORK}/facts.after"
# the same before and after → that figure; different, or only after → `moved` (not compared by the drill)
awk -F'\t' 'NR==FNR { before[$1 FS $2] = $3; next }
            { k = $1 FS $2; print k FS ((k in before && before[k] == $3) ? $3 : "moved") }' \
  "${WORK}/facts.before" "${WORK}/facts.after" > "${WORK}/FACTS.txt"
rm -f "${WORK}/facts.before" "${WORK}/facts.after"
MOVED=$(grep -c $'\tmoved$' "${WORK}/FACTS.txt" || true)

echo "→ collecting storage/app …"
# every file's checksum, so a restore anywhere can prove each file came back byte for byte
if command -v sha256sum >/dev/null 2>&1; then SHA=(sha256sum); else SHA=(shasum -a 256); fi
( cd "${APP_DIR}/storage" && find app -type f -exec "${SHA[@]}" {} + ) > "${WORK}/FILES.sha256"
tar -cf "${WORK}/storage-app.tar" -C "${APP_DIR}/storage" app

# A manifest, so a restorer can tell WHAT they are holding without decrypting it first.
cat > "${WORK}/MANIFEST.txt" <<MAN
vyrobel-backup
created_utc = ${STAMP}
database    = ${DB_NAME}
db_bytes    = $(wc -c < "${WORK}/database.sql")
files_bytes = $(wc -c < "${WORK}/storage-app.tar")
facts       = FACTS.txt — $(grep -c $'^table\t' "${WORK}/FACTS.txt" || true) tables (${MOVED} figure(s) moved during the dump, not compared)
files_list  = FILES.sha256 — $(wc -l < "${WORK}/FILES.sha256" | tr -d ' ') files
⚠️ APP_KEY is NOT in this archive. Guest PII and ID documents cannot be read without it; store it separately.
MAN

ARCHIVE="${DEST}/vyrobel-${STAMP}.tar.gz.enc"
echo "→ encrypting → ${ARCHIVE}"
tar -czf - -C "${WORK}" database.sql storage-app.tar MANIFEST.txt FACTS.txt FILES.sha256 \
  | openssl enc -aes-256-cbc -pbkdf2 -salt -pass env:BACKUP_PASSPHRASE -out "${ARCHIVE}"
# ⚠️ the archive's own checksum, beside it: an off-server copy is proven to be THIS file before it counts (ops/backup-pull.sh)
( cd "${DEST}" && "${SHA[@]}" "$(basename "${ARCHIVE}")" > "$(basename "${ARCHIVE}").sha256" )
find "${DEST}" -name 'vyrobel-*.tar.gz.enc.sha256' -type f -mtime "+${RETAIN_DAYS}" -delete 2>/dev/null || true

# ⚠️ Retention runs LAST and only on this destination — a failed backup above aborts (set -e) before
# anything is deleted, so a broken run can never prune the last good archive.
find "${DEST}" -name 'vyrobel-*.tar.gz.enc' -type f -mtime "+${RETAIN_DAYS}" -delete 2>/dev/null || true

echo "✓ $(basename "${ARCHIVE}")  ($(wc -c < "${ARCHIVE}") bytes)"
echo "${ARCHIVE}"
