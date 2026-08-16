#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────────────────────────
# Vyrobel — DEPLOY §7.1 · THE RESTORE DRILL. This is the deliverable, not ops/backup.sh.
#
# ⚠️ AN UNRESTORED BACKUP IS A HYPOTHESIS. A script that produces a file proves only that a file was
# produced. This restores the archive into a SCRATCH database and then asserts the restored data
# matches the source: row counts for EVERY table, a known-record spot-check, and referential
# integrity. It is the "open the real bytes" discipline applied to disaster recovery.
#
# ⚠️ IT NEVER TOUCHES THE SOURCE. The scratch schema is created, restored into, compared, and DROPPED.
# The script refuses outright if the scratch name collides with the source database.
#
# Usage:  BACKUP_PASSPHRASE=… ops/restore-drill.sh [archive.tar.gz.enc]
#         (no argument → the newest archive in storage/backups)
# ─────────────────────────────────────────────────────────────────────────────────────────────────
set -euo pipefail

APP_DIR="${APP_DIR:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
env_get() { grep -E "^${1}=" "${APP_DIR}/.env" 2>/dev/null | head -1 | cut -d= -f2- | tr -d '"'"'"'' ; }
DB_HOST="$(env_get DB_HOST)"; DB_PORT="$(env_get DB_PORT)"; SRC_DB="$(env_get DB_DATABASE)"
DB_USER="$(env_get DB_USERNAME)"; DB_PASS="$(env_get DB_PASSWORD)"
SCRATCH="${RESTORE_SCRATCH_DB:-vyrobel_restore_drill}"

: "${BACKUP_PASSPHRASE:?BACKUP_PASSPHRASE required to decrypt the archive}"
# ⚠️ THE GUARD THAT MATTERS: a drill that restored over the live schema would destroy the thing it
# claims to protect. Refuse before doing anything else.
if [ "${SCRATCH}" = "${SRC_DB}" ]; then
  echo "REFUSING: scratch database equals the source database (${SRC_DB})." >&2; exit 2
fi

ARCHIVE="${1:-$(ls -t "${APP_DIR}"/storage/backups/vyrobel-*.tar.gz.enc 2>/dev/null | head -1)}"
[ -n "${ARCHIVE}" ] && [ -f "${ARCHIVE}" ] || { echo "No archive found." >&2; exit 2; }

my() { mysql ${DB_HOST:+-h "${DB_HOST}"} ${DB_PORT:+-P "${DB_PORT}"} -u "${DB_USER:-root}" ${DB_PASS:+-p"${DB_PASS}"} "$@"; }

WORK="$(mktemp -d)"
cleanup() { my -e "DROP DATABASE IF EXISTS \`${SCRATCH}\`;" >/dev/null 2>&1 || true; rm -rf "${WORK}"; }
trap cleanup EXIT

echo "→ archive : $(basename "${ARCHIVE}")"
echo "→ scratch : ${SCRATCH}   (source ${SRC_DB} is READ-ONLY here)"

echo "→ decrypting + unpacking …"
openssl enc -d -aes-256-cbc -pbkdf2 -pass env:BACKUP_PASSPHRASE -in "${ARCHIVE}" | tar -xzf - -C "${WORK}"
[ -s "${WORK}/database.sql" ] || { echo "FAIL: no database.sql in the archive" >&2; exit 1; }

echo "→ restoring into ${SCRATCH} …"
my -e "DROP DATABASE IF EXISTS \`${SCRATCH}\`; CREATE DATABASE \`${SCRATCH}\` CHARACTER SET utf8mb4;"
my "${SCRATCH}" < "${WORK}/database.sql"

FAIL=0

# ── 1. ROW COUNTS, EVERY TABLE ───────────────────────────────────────────────────────────────────
# ⚠️ Compared per table, not as a total: two tables off by +1/−1 would cancel in a sum.
echo "→ comparing row counts across every table …"
TABLES=$(my -sN -e "SELECT table_name FROM information_schema.tables WHERE table_schema='${SRC_DB}' AND table_type='BASE TABLE' ORDER BY table_name;")
COUNT_TABLES=0; MISMATCH=0
for T in ${TABLES}; do
  A=$(my -sN -e "SELECT COUNT(*) FROM \`${SRC_DB}\`.\`${T}\`;")
  B=$(my -sN -e "SELECT COUNT(*) FROM \`${SCRATCH}\`.\`${T}\`;" 2>/dev/null || echo "MISSING")
  COUNT_TABLES=$((COUNT_TABLES+1))
  if [ "${A}" != "${B}" ]; then echo "   ✗ ${T}: source=${A} restored=${B}"; MISMATCH=$((MISMATCH+1)); fi
done
if [ "${MISMATCH}" -eq 0 ]; then echo "   ✓ ${COUNT_TABLES} tables, every row count matches"; else FAIL=1; fi

# ⚠️ NON-VACUITY: an EMPTY database restores "successfully" and matches itself perfectly. If the
# source carries no rows, this drill proves nothing — so it fails rather than reporting a green tick.
POPULATED=$(my -sN -e "SELECT COUNT(*) FROM \`${SRC_DB}\`.reservations;")
if [ "${POPULATED}" -lt 1 ]; then
  echo "   ✗ the source database has no reservations — this drill would pass vacuously"; FAIL=1
else
  echo "   ✓ source is a real dataset (${POPULATED} reservations), so the comparison means something"
fi

# ── 2. KNOWN-RECORD SPOT-CHECK — money, field by field ───────────────────────────────────────────
# ⚠️ Row counts alone would pass on a dump that restored the right NUMBER of wrong rows.
echo "→ spot-checking a known reservation + payout, field by field …"
for PAIR in "reservations:id,reference,status,check_in,check_out,currency" "payouts:id,amount_minor,currency,status,owner_id" "owner_statements:id,net_payable_minor,gross_minor,status"; do
  T="${PAIR%%:*}"; COLS="${PAIR#*:}"
  KEY=$(my -sN -e "SELECT id FROM \`${SRC_DB}\`.\`${T}\` ORDER BY id LIMIT 1;")
  [ -n "${KEY}" ] || continue
  A=$(my -sN -e "SELECT CONCAT_WS('|',${COLS}) FROM \`${SRC_DB}\`.\`${T}\` WHERE id='${KEY}';")
  B=$(my -sN -e "SELECT CONCAT_WS('|',${COLS}) FROM \`${SCRATCH}\`.\`${T}\` WHERE id='${KEY}';")
  if [ "${A}" = "${B}" ] && [ -n "${A}" ]; then echo "   ✓ ${T}[${KEY}] → ${A}"; else echo "   ✗ ${T}[${KEY}] source='${A}' restored='${B}'"; FAIL=1; fi
done

# ── 3. MONEY TOTALS — the sums a business would notice ───────────────────────────────────────────
echo "→ comparing money totals …"
for Q in "SELECT COALESCE(SUM(amount_minor),0) FROM payouts" "SELECT COALESCE(SUM(net_payable_minor),0) FROM owner_statements" "SELECT COALESCE(SUM(amount_minor),0) FROM folio_items"; do
  A=$(my -sN -e "USE \`${SRC_DB}\`; ${Q};"); B=$(my -sN -e "USE \`${SCRATCH}\`; ${Q};")
  if [ "${A}" = "${B}" ]; then echo "   ✓ ${Q#SELECT COALESCE(SUM(} … = ${A}"; else echo "   ✗ ${Q}: source=${A} restored=${B}"; FAIL=1; fi
done

# ── 4. REFERENTIAL INTEGRITY — the constraints came back, and nothing dangles ─────────────────────
echo "→ checking referential integrity in the restored schema …"
FKS=$(my -sN -e "SELECT COUNT(*) FROM information_schema.referential_constraints WHERE constraint_schema='${SCRATCH}';")
FKS_SRC=$(my -sN -e "SELECT COUNT(*) FROM information_schema.referential_constraints WHERE constraint_schema='${SRC_DB}';")
if [ "${FKS}" = "${FKS_SRC}" ] && [ "${FKS}" -gt 0 ]; then echo "   ✓ ${FKS} foreign keys restored (source has ${FKS_SRC})"; else echo "   ✗ FK count: source=${FKS_SRC} restored=${FKS}"; FAIL=1; fi
# ⚠️ Constraints EXISTING is not the same as data SATISFYING them — a dump restored out of order can
# leave orphans behind while the constraint definition looks fine. Ask the engine to re-check.
ORPHANS=$(my -sN "${SCRATCH}" -e "SELECT COUNT(*) FROM reservations r LEFT JOIN properties p ON p.id=r.property_id WHERE p.id IS NULL;")
if [ "${ORPHANS}" = "0" ]; then echo "   ✓ no orphaned reservations (every property_id resolves)"; else echo "   ✗ ${ORPHANS} orphaned reservations"; FAIL=1; fi

# ── 5. THE FILE SET — restored and byte-identical ────────────────────────────────────────────────
echo "→ verifying the restored file set …"
mkdir -p "${WORK}/files" && tar -xf "${WORK}/storage-app.tar" -C "${WORK}/files"
SRC_N=$(find "${APP_DIR}/storage/app" -type f | wc -l | tr -d ' ')
RES_N=$(find "${WORK}/files/app" -type f | wc -l | tr -d ' ')
if [ "${SRC_N}" = "${RES_N}" ]; then echo "   ✓ ${RES_N} files restored"; else echo "   ✗ files: source=${SRC_N} restored=${RES_N}"; FAIL=1; fi
SAMPLE=$(find "${APP_DIR}/storage/app" -type f ! -name '.gitignore' | head -1)
if [ -n "${SAMPLE}" ]; then
  REL="${SAMPLE#${APP_DIR}/storage/}"
  H1=$(shasum -a 256 "${SAMPLE}" | cut -d' ' -f1); H2=$(shasum -a 256 "${WORK}/files/${REL}" 2>/dev/null | cut -d' ' -f1 || echo none)
  if [ "${H1}" = "${H2}" ]; then echo "   ✓ checksum matches for ${REL##*/} (${H1:0:16}…)"; else echo "   ✗ checksum differs for ${REL}"; FAIL=1; fi
fi

echo
if [ "${FAIL}" -eq 0 ]; then echo "✅ RESTORE DRILL PASSED — the archive reconstructs the data."; else echo "❌ RESTORE DRILL FAILED."; fi
exit "${FAIL}"
