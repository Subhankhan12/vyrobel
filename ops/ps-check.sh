#!/usr/bin/env bash
#
# ⚠️⚠️ THE BRACKET-SPLIT MECHANISM — because "remember to bracket-split" has now failed THREE TIMES.
#
# 🔬 The failures, each a real one: the Seaside detector reading its own needle · `ops/secret-scan.sh`
# matching the pattern in its own source · and 2026-09-23, `ps | grep "queue:work"` reporting **36 of 36
# sightings** of a worker that was not running — the sampling shell's own command line contained the string
# it was grepping for, and the conclusion ("a worker IS running") was the exact opposite of the truth until
# it was re-measured.
#
# ⚠️ A PROCESS OR SCANNER THAT MATCHES ON TEXT WILL MATCH THE TEXT OF THE THING DOING THE MATCHING.
# ⚠️⚠️ So this is a script, not a habit: the pattern is transformed HERE, where it cannot be forgotten.
#
# USAGE:  ops/ps-check.sh 'queue:work|horizon'      → prints matching processes, self-excluded
#         ops/ps-check.sh -c 'queue:work'           → prints only the count
#
# ⚠️ It bracket-splits the FIRST character of each alternative (`queue` → `[q]ueue`), which is invisible to
# the regex engine and fatal to self-matching, AND excludes this script's own PID and its parent.
set -u

COUNT_ONLY=0
[ "${1:-}" = "-c" ] && { COUNT_ONLY=1; shift; }
PATTERN="${1:-}"
[ -z "$PATTERN" ] && { echo "usage: ops/ps-check.sh [-c] '<extended-regex>'" >&2; exit 2; }

# ⚠️ TWO transformations, and BOTH were bought by a real false result:
#   1. BRACKET-SPLIT the first character (`queue` → `[q]ueue`) so the pattern cannot match a command line
#      that contains it verbatim — i.e. this tool's own invocation. 🔬 Bought by `ps | grep "queue:work"`
#      reporting 36/36 sightings of a worker that was not running.
#   2. ⚠️⚠️ WORD-ANCHOR each alternative, so a needle cannot match INSIDE a longer name. 🔬 Bought
#      2026-09-23 by the needle `rsync` matching macOS's **`postersyncd`** ("poste-rsync-d"). That one was
#      caught by eye — and ⚠️ **the danger is not the false positive, it is habituation: a check that cries
#      wolf gets skimmed, and the run it matters on is the one somebody skims.**
#   ⚠️ `[[:alnum:]_]` rather than `\b`, because `\b` is a GNU extension and this must also run on BSD grep.
SPLIT=$(printf '%s' "$PATTERN" | awk -F'|' '{
  out=""
  for (i = 1; i <= NF; i++) {
    a = $i
    if (length(a) > 0) {
      a = "[" substr(a,1,1) "]" substr(a,2)
      a = "(^|[^[:alnum:]_])" a "([^[:alnum:]_]|$)"
    }
    out = (i == 1 ? a : out "|" a)
  }
  print out
}')

SELF=$$
PARENT=$PPID

# ⚠️ EXCLUDE BY PID **AND** BY NAME. 🔬 PID alone was not enough: the command substitution below runs in a
#    subshell whose PID differs from `$$`, so this tool's own invocation survived its own filter — the very
#    self-matching this file exists to prevent, one layer deeper. Excluding the tool's own path closes it.
MATCHES=$(ps -ax -o pid=,args= 2>/dev/null \
  | grep -E "$SPLIT" \
  | grep -v 'ops/ps-check\.sh' \
  | awk -v s="$SELF" -v p="$PARENT" '$1 != s && $1 != p')

if [ "$COUNT_ONLY" = "1" ]; then
  printf '%s\n' "$(printf '%s' "$MATCHES" | grep -c . )"
  exit 0
fi

if [ -z "$MATCHES" ]; then
  echo "  ✅ 0 processes match /$PATTERN/ — self-matching excluded by construction (see this file's header)."
  exit 1
fi

printf '%s\n' "$MATCHES" | sed 's/^/  /'
exit 0
