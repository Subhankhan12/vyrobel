#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────────────────────────
# Vyrobel — DEPLOY §7.1 · THE RESTORE DRILL. This is the deliverable, not ops/backup.sh.
#
# ⚠️ AN UNRESTORED BACKUP IS A HYPOTHESIS. A script that produces a file proves only that a file was
# produced. This restores the archive into a SCRATCH database and then asserts the restored data
# matches what was backed up: row counts for EVERY table, a known-record spot-check, money totals,
# referential integrity and every file. It is the "open the real bytes" discipline applied to disaster recovery.
#
# ⚠️ IT NEVER TOUCHES THE SOURCE. The scratch schema is created, restored into, compared, and DROPPED.
# The script refuses outright if the scratch name collides with the source database.
#
# TWO WAYS TO COMPARE (VY-12, 2026-11-25):
#   · RESTORE_AGAINST=source (the default) — against the LIVE source database on this machine, as before. Only possible where
#     the backup was made.
#   · RESTORE_AGAINST=facts — against FACTS.txt and FILES.sha256 INSIDE the archive (written by ops/backup.sh at dump time).
#     ⚠️ This is the one that proves an OFF-SERVER copy: it needs no access to the server at all — a laptop with a database
#     server the drill may create a scratch schema on is enough (production's own account cannot create one).
#     A figure marked `moved` (it changed while the dump ran) is reported and not compared.
#
# Usage:  ops/restore-drill.sh [archive.tar.gz.enc]           (no argument → the newest archive in storage/backups)
#   BACKUP_PASSPHRASE   the archive's passphrase. ⚠️ Not set and run at a terminal → asked for, hidden, never echoed or stored.
#   RESTORE_AGAINST     source | facts
#   RESTORE_DB_HOST / RESTORE_DB_PORT / RESTORE_DB_USER / RESTORE_DB_PASS   the database server to restore into (default: .env's)
#   RESTORE_MYSQL_BIN   the client to use (e.g. MariaDB's own `mariadb` for a MariaDB dump: its first line is a MariaDB-only command)
#   RESTORE_SCRATCH_DB  the scratch schema's name (default vyrobel_restore_drill)
# ─────────────────────────────────────────────────────────────────────────────────────────────────
set -euo pipefail

APP_DIR="${APP_DIR:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
env_get() { grep -E "^${1}=" "${APP_DIR}/.env" 2>/dev/null | head -1 | cut -d= -f2- | tr -d '"'"'"'' ; }
AGAINST="${RESTORE_AGAINST:-source}"
case "${AGAINST}" in source|facts) ;; *) echo "RESTORE_AGAINST must be source or facts" >&2; exit 2 ;; esac
DB_HOST="${RESTORE_DB_HOST:-$(env_get DB_HOST)}"; DB_PORT="${RESTORE_DB_PORT:-$(env_get DB_PORT)}"
DB_USER="${RESTORE_DB_USER:-$(env_get DB_USERNAME)}"; DB_PASS="${RESTORE_DB_PASS-$(env_get DB_PASSWORD)}"
SRC_DB="$(env_get DB_DATABASE)"
MYSQL_BIN="${RESTORE_MYSQL_BIN:-mysql}"
SCRATCH="${RESTORE_SCRATCH_DB:-vyrobel_restore_drill}"

# ⚠️ The passphrase: from the environment, or typed at a hidden prompt — never an argument, never printed, never written.
if [ -z "${BACKUP_PASSPHRASE:-}" ]; then
  if [ -t 0 ]; then
    read -r -s -p "Backup passphrase (hidden): " BACKUP_PASSPHRASE; echo
    export BACKUP_PASSPHRASE
  fi
fi
: "${BACKUP_PASSPHRASE:?BACKUP_PASSPHRASE required to decrypt the archive}"
# ⚠️ THE GUARD THAT MATTERS: a drill that restored over the live schema would destroy the thing it
# claims to protect. Refuse before doing anything else.
if [ "${SCRATCH}" = "${SRC_DB}" ]; then
  echo "REFUSING: scratch database equals the source database (${SRC_DB})." >&2; exit 2
fi

ARCHIVE="${1:-$(ls -t "${APP_DIR}"/storage/backups/vyrobel-*.tar.gz.enc 2>/dev/null | head -1)}"
[ -n "${ARCHIVE}" ] && [ -f "${ARCHIVE}" ] || { echo "No archive found." >&2; exit 2; }

my() { "${MYSQL_BIN}" ${DB_HOST:+-h "${DB_HOST}"} ${DB_PORT:+-P "${DB_PORT}"} -u "${DB_USER:-root}" ${DB_PASS:+-p"${DB_PASS}"} "$@"; }

WORK="$(mktemp -d)"
cleanup() { my -e "DROP DATABASE IF EXISTS \`${SCRATCH}\`;" >/dev/null 2>&1 || true; rm -rf "${WORK}"; }
trap cleanup EXIT

echo "→ archive : $(basename "${ARCHIVE}")"
if [ "${AGAINST}" = "facts" ]; then
  echo "→ scratch : ${SCRATCH}   (compared with the facts INSIDE the archive — no source database is read)"
else
  echo "→ scratch : ${SCRATCH}   (source ${SRC_DB} is READ-ONLY here)"
fi

echo "→ decrypting + unpacking …"
openssl enc -d -aes-256-cbc -pbkdf2 -pass env:BACKUP_PASSPHRASE -in "${ARCHIVE}" | tar -xzf - -C "${WORK}"
[ -s "${WORK}/database.sql" ] || { echo "FAIL: no database.sql in the archive" >&2; exit 1; }
if [ "${AGAINST}" = "facts" ]; then
  [ -s "${WORK}/FACTS.txt" ] && [ -f "${WORK}/FILES.sha256" ] \
    || { echo "FAIL: this archive carries no FACTS.txt / FILES.sha256 (made before 2026-11-25) — drill it where it was made, with RESTORE_AGAINST=source" >&2; exit 1; }
fi

echo "→ restoring into ${SCRATCH} …"
my -e "DROP DATABASE IF EXISTS \`${SCRATCH}\`; CREATE DATABASE \`${SCRATCH}\` CHARACTER SET utf8mb4;"
my "${SCRATCH}" < "${WORK}/database.sql"

FAIL=0
# the spot-check pairs and money sums — the SAME lists as ops/backup.sh's facts (a test keeps them in step)
SPOT_PAIRS="reservations:id,reference,status,check_in,check_out,currency payouts:id,amount_minor,currency,status,owner_id owner_statements:id,net_payable_minor,gross_minor,status"
SUM_COLS="payouts.amount_minor owner_statements.net_payable_minor folio_items.amount_minor"
fact() { awk -F'\t' -v t="$1" -v n="$2" '$1 == t && $2 == n { print $3; exit }' "${WORK}/FACTS.txt"; }

# ── 1. ROW COUNTS, EVERY TABLE ───────────────────────────────────────────────────────────────────
# ⚠️ Compared per table, not as a total: two tables off by +1/−1 would cancel in a sum.
echo "→ comparing row counts across every table …"
COUNT_TABLES=0; MISMATCH=0; MOVED=0; ROWS=0
if [ "${AGAINST}" = "facts" ]; then
  while IFS=$'\t' read -r KIND T A; do
    [ "${KIND}" = "table" ] || continue
    COUNT_TABLES=$((COUNT_TABLES+1))
    B=$(my -sN -e "SELECT COUNT(*) FROM \`${SCRATCH}\`.\`${T}\`;" 2>/dev/null || echo "MISSING")
    if [ "${A}" = "moved" ]; then MOVED=$((MOVED+1)); continue; fi
    if [ "${A}" != "${B}" ]; then echo "   ✗ ${T}: backed up=${A} restored=${B}"; MISMATCH=$((MISMATCH+1)); else ROWS=$((ROWS+B)); fi
  done < "${WORK}/FACTS.txt"
  # and nothing restored that the facts do not name
  RESTORED_N=$(my -sN -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${SCRATCH}' AND table_type='BASE TABLE';")
  if [ "${RESTORED_N}" != "${COUNT_TABLES}" ]; then echo "   ✗ tables: the facts name ${COUNT_TABLES}, the restore has ${RESTORED_N}"; FAIL=1; fi
else
  TABLES=$(my -sN -e "SELECT table_name FROM information_schema.tables WHERE table_schema='${SRC_DB}' AND table_type='BASE TABLE' ORDER BY table_name;")
  for T in ${TABLES}; do
    A=$(my -sN -e "SELECT COUNT(*) FROM \`${SRC_DB}\`.\`${T}\`;")
    B=$(my -sN -e "SELECT COUNT(*) FROM \`${SCRATCH}\`.\`${T}\`;" 2>/dev/null || echo "MISSING")
    COUNT_TABLES=$((COUNT_TABLES+1))
    if [ "${A}" != "${B}" ]; then echo "   ✗ ${T}: source=${A} restored=${B}"; MISMATCH=$((MISMATCH+1)); else ROWS=$((ROWS+B)); fi
  done
fi
if [ "${MISMATCH}" -eq 0 ]; then
  echo "   ✓ ${COUNT_TABLES} tables, every row count matches$([ "${MOVED}" -gt 0 ] && echo " (${MOVED} moved during the dump, not compared)")"
else FAIL=1; fi

# ⚠️ NON-VACUITY: an EMPTY database restores "successfully" and matches itself perfectly. A drill over no
# rows proves nothing — so it fails rather than reporting a green tick.
if [ "${AGAINST}" = "facts" ]; then
  RES_COUNT=$(my -sN -e "SELECT COUNT(*) FROM \`${SCRATCH}\`.reservations;" 2>/dev/null || echo 0)
  if [ "${ROWS}" -lt 1 ]; then
    echo "   ✗ the archive holds no rows — this drill would pass vacuously"; FAIL=1
  else
    echo "   ✓ the archive is a real dataset (${ROWS} rows compared, ${RES_COUNT} reservations), so the comparison means something"
  fi
else
  POPULATED=$(my -sN -e "SELECT COUNT(*) FROM \`${SRC_DB}\`.reservations;")
  if [ "${POPULATED}" -lt 1 ]; then
    echo "   ✗ the source database has no reservations — this drill would pass vacuously"; FAIL=1
  else
    echo "   ✓ source is a real dataset (${POPULATED} reservations), so the comparison means something"
  fi
fi

# ── 2. KNOWN-RECORD SPOT-CHECK — money, field by field ───────────────────────────────────────────
# ⚠️ Row counts alone would pass on a dump that restored the right NUMBER of wrong rows.
echo "→ spot-checking a known reservation + payout, field by field …"
for PAIR in ${SPOT_PAIRS}; do
  T="${PAIR%%:*}"; COLS="${PAIR#*:}"
  if [ "${AGAINST}" = "facts" ]; then
    A="$(fact spot "${T}")"
    [ -n "${A}" ] || continue
    B=$(my -sN -e "SELECT CONCAT_WS('|',${COLS}) FROM \`${SCRATCH}\`.\`${T}\` ORDER BY id LIMIT 1;")
    if [ "${A}" = "moved" ]; then echo "   · ${T}: first record moved during the dump, not compared"; continue; fi
    if [ "${A}" = "${B}" ]; then echo "   ✓ ${T} first record → ${A}"; else echo "   ✗ ${T} first record: backed up='${A}' restored='${B}'"; FAIL=1; fi
  else
    KEY=$(my -sN -e "SELECT id FROM \`${SRC_DB}\`.\`${T}\` ORDER BY id LIMIT 1;")
    [ -n "${KEY}" ] || continue
    A=$(my -sN -e "SELECT CONCAT_WS('|',${COLS}) FROM \`${SRC_DB}\`.\`${T}\` WHERE id='${KEY}';")
    B=$(my -sN -e "SELECT CONCAT_WS('|',${COLS}) FROM \`${SCRATCH}\`.\`${T}\` WHERE id='${KEY}';")
    if [ "${A}" = "${B}" ] && [ -n "${A}" ]; then echo "   ✓ ${T}[${KEY}] → ${A}"; else echo "   ✗ ${T}[${KEY}] source='${A}' restored='${B}'"; FAIL=1; fi
  fi
done

# ── 3. MONEY TOTALS — the sums a business would notice ───────────────────────────────────────────
echo "→ comparing money totals …"
for COL in ${SUM_COLS}; do
  TAB="${COL%%.*}"; Q="SELECT COALESCE(SUM(${COL#*.}),0) FROM \`${TAB}\`"
  if [ "${AGAINST}" = "facts" ]; then
    A="$(fact sum "${COL}")"; [ -n "${A}" ] || continue
    if [ "${A}" = "moved" ]; then echo "   · ${COL}: moved during the dump, not compared"; continue; fi
  else
    A=$(my -sN -e "USE \`${SRC_DB}\`; ${Q};")
  fi
  B=$(my -sN -e "USE \`${SCRATCH}\`; ${Q};")
  if [ "${A}" = "${B}" ]; then echo "   ✓ SUM(${COL}) = ${A}"; else echo "   ✗ SUM(${COL}): backed up=${A} restored=${B}"; FAIL=1; fi
done

# ── 4. REFERENTIAL INTEGRITY — the constraints came back, and nothing dangles ─────────────────────
echo "→ checking referential integrity in the restored schema …"
FKS=$(my -sN -e "SELECT COUNT(*) FROM information_schema.referential_constraints WHERE constraint_schema='${SCRATCH}';")
if [ "${AGAINST}" = "facts" ]; then FKS_SRC="$(fact fks all)"; else
  FKS_SRC=$(my -sN -e "SELECT COUNT(*) FROM information_schema.referential_constraints WHERE constraint_schema='${SRC_DB}';"); fi
if [ "${FKS}" = "${FKS_SRC}" ] && [ "${FKS}" -gt 0 ]; then echo "   ✓ ${FKS} foreign keys restored (backed up: ${FKS_SRC})"; else echo "   ✗ FK count: backed up=${FKS_SRC} restored=${FKS}"; FAIL=1; fi
# ⚠️ Constraints EXISTING is not the same as data SATISFYING them — a dump restored out of order can
# leave orphans behind while the constraint definition looks fine. Ask the engine to re-check.
ORPHANS=$(my -sN "${SCRATCH}" -e "SELECT COUNT(*) FROM reservations r LEFT JOIN properties p ON p.id=r.property_id WHERE p.id IS NULL;")
if [ "${ORPHANS}" = "0" ]; then echo "   ✓ no orphaned reservations (every property_id resolves)"; else echo "   ✗ ${ORPHANS} orphaned reservations"; FAIL=1; fi

# ── 5. THE FILE SET — restored and byte-identical ────────────────────────────────────────────────
echo "→ verifying the restored file set …"
mkdir -p "${WORK}/files" && tar -xf "${WORK}/storage-app.tar" -C "${WORK}/files"
RES_N=$(find "${WORK}/files/app" -type f | wc -l | tr -d ' ')
if [ "${AGAINST}" = "facts" ]; then
  # ⚠️ EVERY file, by its checksum at backup time — not a count and one sample
  if command -v sha256sum >/dev/null 2>&1; then SHA=(sha256sum); else SHA=(shasum -a 256); fi
  LISTED=$(wc -l < "${WORK}/FILES.sha256" | tr -d ' ')
  if [ "${LISTED}" -eq 0 ]; then
    echo "   · the backup listed no files (storage/app was empty); ${RES_N} restored"
  elif ( cd "${WORK}/files" && "${SHA[@]}" -c "${WORK}/FILES.sha256" ) >/dev/null 2>&1; then
    echo "   ✓ ${LISTED} files restored, every one byte-identical to its checksum at backup time (${RES_N} in the archive)"
  else
    BAD=$( ( cd "${WORK}/files" && "${SHA[@]}" -c "${WORK}/FILES.sha256" 2>/dev/null ) | grep -vc ': OK$' || true)
    echo "   ✗ ${BAD} of ${LISTED} files missing or different from their checksum at backup time"; FAIL=1
  fi
else
  SRC_N=$(find "${APP_DIR}/storage/app" -type f | wc -l | tr -d ' ')
  if [ "${SRC_N}" = "${RES_N}" ]; then echo "   ✓ ${RES_N} files restored"; else echo "   ✗ files: source=${SRC_N} restored=${RES_N}"; FAIL=1; fi
  SAMPLE=$(find "${APP_DIR}/storage/app" -type f ! -name '.gitignore' | head -1)
  if [ -n "${SAMPLE}" ]; then
    REL="${SAMPLE#${APP_DIR}/storage/}"
    H1=$(shasum -a 256 "${SAMPLE}" | cut -d' ' -f1); H2=$(shasum -a 256 "${WORK}/files/${REL}" 2>/dev/null | cut -d' ' -f1 || echo none)
    if [ "${H1}" = "${H2}" ]; then echo "   ✓ checksum matches for ${REL##*/} (${H1:0:16}…)"; else echo "   ✗ checksum differs for ${REL}"; FAIL=1; fi
  fi
fi

echo
if [ "${FAIL}" -eq 0 ]; then echo "✅ RESTORE DRILL PASSED — the archive reconstructs the data."; else echo "❌ RESTORE DRILL FAILED."; fi
exit "${FAIL}"
