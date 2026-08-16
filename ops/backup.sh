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

# ⚠️ --single-transaction: a CONSISTENT snapshot on InnoDB without locking the site. Without it a dump
# taken mid-booking can contain a reservation whose folio rows are from a different instant.
# --routines/--triggers/--events so the schema comes back whole, not just the rows.
echo "→ dumping ${DB_NAME} …"
mysqldump --single-transaction --quick --routines --triggers --events \
  --default-character-set=utf8mb4 \
  ${DB_HOST:+-h "${DB_HOST}"} ${DB_PORT:+-P "${DB_PORT}"} \
  -u "${DB_USER:-root}" ${DB_PASS:+-p"${DB_PASS}"} "${DB_NAME}" > "${WORK}/database.sql"

echo "→ collecting storage/app …"
tar -cf "${WORK}/storage-app.tar" -C "${APP_DIR}/storage" app

# A manifest, so a restorer can tell WHAT they are holding without decrypting it first.
cat > "${WORK}/MANIFEST.txt" <<MAN
vyrobel-backup
created_utc = ${STAMP}
database    = ${DB_NAME}
db_bytes    = $(wc -c < "${WORK}/database.sql")
files_bytes = $(wc -c < "${WORK}/storage-app.tar")
⚠️ APP_KEY is NOT in this archive. Guest PII and ID documents cannot be read without it; store it separately.
MAN

ARCHIVE="${DEST}/vyrobel-${STAMP}.tar.gz.enc"
echo "→ encrypting → ${ARCHIVE}"
tar -czf - -C "${WORK}" database.sql storage-app.tar MANIFEST.txt \
  | openssl enc -aes-256-cbc -pbkdf2 -salt -pass env:BACKUP_PASSPHRASE -out "${ARCHIVE}"

# ⚠️ Retention runs LAST and only on this destination — a failed backup above aborts (set -e) before
# anything is deleted, so a broken run can never prune the last good archive.
find "${DEST}" -name 'vyrobel-*.tar.gz.enc' -type f -mtime "+${RETAIN_DAYS}" -delete 2>/dev/null || true

echo "✓ $(basename "${ARCHIVE}")  ($(wc -c < "${ARCHIVE}") bytes)"
echo "${ARCHIVE}"
