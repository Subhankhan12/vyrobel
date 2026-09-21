#!/usr/bin/env bash
#
# ops/proc-check.sh — ⚠️⚠️ NEVER TRUST A NEGATIVE FROM A PROCESS CHECK YOU HAVE NOT PROVEN CAN SEE ANYTHING.
#
# ── ⚠️ WHY THIS EXISTS, AND WHY IT IS A SCRIPT RATHER THAN A RULE ────────────────────────────────────────
#
# ⚠️⚠️ The rule was already written down. Twice. *"A failed probe can be your own error wearing a denial's
# clothes"* and *"verify a check CAN pass"* are both in the method notes — and on 2026-09-20 the same trap
# fired **five times in one day**, the last time expensively:
#
#   🔬 `ps -eo pid,cmd | grep pest` printed **PIDs only** — macOS `ps` has no `cmd` keyword, it is a Linux
#      spelling — so the grep matched nothing. That empty result was read as *"the suite died"*, a second
#      suite was started, and **two runs overlapped on the shared test database** — breaking the one hard
#      rule this project has about test runs (`CLAUDE.md`: RUN THE SUITE ALONE).
#
# ⚠️⚠️ **A RULE THAT IS WRITTEN DOWN AND STILL FIRES IS NOT A RULE, IT IS A NOTE.** Five occurrences is the
# count that earns a mechanism. The discipline belongs in the tool, not in the remembering.
#
# ── ⚠️ WHAT IT DOES ──────────────────────────────────────────────────────────────────────────────────────
#
# Before reporting ANY result, it spawns a marker process with an unmistakable command line and requires the
# listing to SEE it. ⚠️ If the positive control fails, it **REFUSES (exit 3)** rather than reporting zero —
# because at that moment "nothing is running" and "I cannot see anything" are indistinguishable, and only one
# of them is safe to act on.
#
# ── ⚠️⚠️ AND THE SECOND VERB, ADDED AFTER THE SAME TRAP FIRED ON IT ─────────────────────────────────────
#
# ⚠️⚠️ **A MECHANISM SCOPED TO ONE VERB DOES NOT COVER THE OTHER.** This script guarded *checking* while
# `pkill -f "sleep 720"` was still being typed by hand — and 🔬 it matched its own shell's command line (which
# contained the pattern) and killed the session mid-command, printing nothing. ⚠️ The same self-match family,
# one verb over, and the existing guard could not help because nobody had extended it there.
#
# ⚠️ `--kill` therefore refuses a pattern that matches **this script or its ancestors**, kills by **PID**
# rather than by pattern, and **confirms death** rather than trusting the signal.
#
# Usage:
#   ops/proc-check.sh 'pestphp/pest'          # exit 0 = matches found (printed) · 1 = a TRUSTED zero · 3 = blind
#   ops/proc-check.sh -q 'pestphp/pest'       # quiet: exit status only
#   ops/proc-check.sh --kill 'pestphp/pest'   # exit 0 = all killed and confirmed dead · 1 = nothing matched
#                                             #      · 3 = blind · 4 = REFUSED (the pattern matches us)
#
# ⚠️ The pattern is made self-match-proof automatically (first character is bracketed), so the grep cannot
# match its own command line — the other half of this same family of false positives.
#
set -uo pipefail

# ⚠️ `command` is the portable keyword; `cmd` is Linux-only and is exactly what caused the incident above.
PS_CMD=${VY_PS_CMD:-"ps -eo pid,command"}

QUIET=0
KILL=0
while [ $# -gt 0 ]; do
  case "${1:-}" in
    -q)     QUIET=1; shift ;;
    --kill) KILL=1; shift ;;
    *)      break ;;
  esac
done

if [ $# -lt 1 ]; then
  echo "usage: ops/proc-check.sh [-q] [--kill] <extended-regex>" >&2
  exit 2
fi
PATTERN="$1"

say() { [ "$QUIET" -eq 1 ] || echo "$@"; }

# ── 1. ⚠️⚠️ THE POSITIVE CONTROL — a process we KNOW is running, findable by name ───────────────────────
#
# ⚠️⚠️ THE TRAILING `; :` IS LOad-BEARING AND WAS FOUND BY MEASUREMENT, NOT BY READING.
#    🔬 `sh -c 'sleep 25' MARKER` is **exec-optimised**: a shell given a single simple command replaces
#    itself with it, so the process shows as plain `sleep 25` and **the marker is gone**. The first version
#    of this script did exactly that and therefore refused EVERY time — failing safe, but useless.
#    🔬 `bash -c 'sleep 25; :' MARKER` is two commands, so the shell must stay resident and `ps` shows
#    `bash -c sleep 25; : MARKER`. Verified on this platform before being relied on.
MARKER="vyproccheck$$"
bash -c 'sleep 25; :' "${MARKER}" &
CONTROL_PID=$!

# ⚠️ Cleanup is a trap, so it runs on every exit path including the refusal below — the mistake that left a
#    scratch table in production for three passes was a cleanup sharing a line with something that failed.
trap 'kill '"${CONTROL_PID}"' 2>/dev/null; wait '"${CONTROL_PID}"' 2>/dev/null' EXIT

# ⚠️⚠️ NEVER `grep -q` IN A PIPELINE UNDER `pipefail` — AND THIS SCRIPT IS THE PROOF.
#    🔬 The first working version used `if ${PS_CMD} | grep -qE "$MARKER"`. `grep -q` exits the instant it
#    matches, which closes the pipe; `ps` then dies of SIGPIPE with a non-zero status; and `set -o pipefail`
#    promotes THAT to the pipeline's status. So **a successful match was reported as "not found"** — the
#    exact false negative this whole script exists to refuse, produced by the script itself, ten times in a
#    row, while an identical `grep -c` one line later returned 1.
#    ⚠️ `grep -c` consumes all input, so `ps` finishes normally and the status is honest. Use counts, not -q.
control_visible() {
  local n
  n="$(${PS_CMD} 2>/dev/null | grep -cE "[${MARKER:0:1}]${MARKER:1}" || true)"
  [ "${n:-0}" -gt 0 ]
}

# Give the kernel a moment to make it visible.
for _ in 1 2 3 4 5 6 7 8 9 10; do
  if control_visible; then break; fi
  sleep 0.1
done

if ! control_visible; then
  echo "⚠️⚠️ PROCESS CHECK REFUSED — the listing cannot see a process this script KNOWS is running." >&2
  echo "   The control marker '${MARKER}' was spawned and is absent from: ${PS_CMD}" >&2
  echo "   ⚠️ So a zero from this mechanism would mean 'I am blind', NOT 'nothing is running', and those" >&2
  echo "      are indistinguishable at exactly the moment the difference matters." >&2
  echo "   ⚠️ Likely cause: a ps keyword this platform does not support (macOS has no 'cmd' — use" >&2
  echo "      'command'). Override with VY_PS_CMD if your platform needs a different spelling." >&2
  exit 3
fi

say "  ✅ control: the listing can see a known-present process — a zero below is trustworthy"

# ── 2. THE ACTUAL QUESTION, asked self-match-proof ──────────────────────────────────────────────────────
#
# ⚠️ Bracketing the first character stops the grep matching its OWN command line — the classic form.
# ⚠️⚠️ BUT THERE IS A SECOND FORM THAT BRACKETING DOES NOT CATCH, AND IT WAS MEASURED HERE: when this script
#    is invoked from a wrapper shell, **the wrapper's command line contains the pattern verbatim** (it
#    contains the whole invocation), so it matches and reports a process that is really just the question
#    being asked. 🔬 Observed on this machine: a `/bin/zsh -c … proc-check.sh 'pestphp/pest' …` line matched
#    a search for `pestphp/pest`. So lines naming this script, and this script's own process tree, are
#    excluded — a false POSITIVE here is as damaging as the false negative the control guards against.
# ⚠️⚠️ AND A THIRD FORM, MEASURED 2026-09-21: BRACKETING THE FIRST CHARACTER OF THE *PATTERN* PROTECTS ONLY
#    THE FIRST ALTERNATIVE. 🔬 `proc-check.sh 'phpunit|pest|artisan test'` became `[p]hpunit|pest|artisan
#    test` — and the grep's own command line contains the literal `pest`, so it matched ITSELF and reported
#    a test run in flight when there was none. ⚠️ A false POSITIVE on a pre-flight is not harmless: it blocks
#    a legitimate run, and the human fix is to stop believing the tool. ⚠️ So every `|`-separated alternative
#    is bracketed, not just the first. Already-bracketed alternatives are left alone (idempotent).
SAFE=""
_ifs_saved="$IFS"; IFS='|'
for _alt in ${PATTERN}; do
  case "${_alt}" in
    "")      _safe_alt="" ;;                      # an empty alternative: leave it (a degenerate pattern)
    \[*)     _safe_alt="${_alt}" ;;               # already bracketed by the caller — do not double-wrap
    *)       _safe_alt="[${_alt:0:1}]${_alt:1}" ;;
  esac
  SAFE="${SAFE:+${SAFE}|}${_safe_alt}"
done
IFS="$_ifs_saved"
MATCHES="$(${PS_CMD} 2>/dev/null \
  | grep -E "${SAFE}" \
  | grep -v "${MARKER}" \
  | grep -v 'proc-check\.sh' \
  | awk -v me="$$" -v parent="${PPID:-0}" '$1 != me && $1 != parent' \
  || true)"
COUNT="$(printf '%s' "${MATCHES}" | grep -c . || true)"

if [ "${KILL}" -eq 0 ]; then
  if [ "${COUNT}" -gt 0 ]; then
    say "  ⚠️ ${COUNT} process(es) match /${PATTERN}/:"
    say "${MATCHES}" | sed 's/^/     /'
    exit 0
  fi

  say "  ✅ 0 processes match /${PATTERN}/ — and this zero was proven visible, not assumed"
  exit 1
fi

# ── 3. ⚠️⚠️ THE KILL PATH ───────────────────────────────────────────────────────────────────────────────
#
# ⚠️⚠️ THE RULE IS "NEVER KILL AN ANCESTOR", NOT "NEVER KILL WHEN AN ANCESTOR MATCHES".
#    🔬 The first version of this path refused *every* legitimate kill, because the shell you type the
#    pattern into necessarily has the pattern in its own command line and is necessarily an ancestor. Safe,
#    and useless — the same shape as the control that refused every time. ⚠️ So ancestors are **excluded from
#    the kill set** (they are the act of asking, not a target), and the refusal fires only when there is
#    nothing left once they are removed.
ANCESTORS=" $$ "
_pid="${PPID:-0}"
for _ in 1 2 3 4 5 6 7 8; do
  [ -z "${_pid}" ] || [ "${_pid}" = "0" ] || [ "${_pid}" = "1" ] && break
  ANCESTORS="${ANCESTORS}${_pid} "
  _pid="$(ps -p "${_pid}" -o ppid= 2>/dev/null | tr -d ' ' || true)"
done

TARGETS=""
ANCESTOR_HITS=0
for pid in $(printf '%s\n' "${MATCHES}" | awk 'NF {print $1}'); do
  case "${ANCESTORS}" in
    *" ${pid} "*) ANCESTOR_HITS=$((ANCESTOR_HITS + 1)) ;;
    *)            TARGETS="${TARGETS} ${pid}" ;;
  esac
done

if [ "${ANCESTOR_HITS}" -gt 0 ]; then
  say "  ⚠️ ${ANCESTOR_HITS} matching process(es) are THIS script or its ancestors — excluded, never killed."
fi

N="$(printf '%s' "${TARGETS}" | wc -w | tr -d ' ')"

if [ "${N}" -eq 0 ]; then
  if [ "${ANCESTOR_HITS}" -gt 0 ]; then
    echo "⚠️⚠️ KILL REFUSED — /${PATTERN}/ matched NOTHING BUT this script and its ancestors." >&2
    echo "   Acting on it would have terminated the session running the command, mid-command and" >&2
    echo "   silently — which is exactly what a hand-typed \`pkill -f\` did to this project." >&2
    echo "   ⚠️ Narrow the pattern, or kill by PID." >&2
    exit 4
  fi
  say "  ✅ nothing matches /${PATTERN}/ — nothing to kill, and the zero was proven visible"
  exit 1
fi

say "  ⚠️ killing ${N} process(es) matching /${PATTERN}/ BY PID:"
for pid in ${TARGETS}; do
  kill "${pid}" 2>/dev/null && say "     signalled ${pid}" || say "     ${pid} already gone"
done

# ⚠️ CONFIRM DEATH. A signal that was delivered is not a process that exited — wait, escalate, then VERIFY.
#    "kill returned 0" is not evidence, in the same way "the command ran" is not "the command worked".
sleep 1
for pid in ${TARGETS}; do
  ps -p "${pid}" -o pid= >/dev/null 2>&1 && kill -9 "${pid}" 2>/dev/null
done
sleep 1

REMAIN=""
for pid in ${TARGETS}; do
  ps -p "${pid}" -o pid= >/dev/null 2>&1 && REMAIN="${REMAIN} ${pid}"
done

if [ -n "${REMAIN}" ]; then
  echo "⚠️⚠️ NOT ALL DEAD — still running:${REMAIN}" >&2
  exit 3
fi

say "  ✅ all ${N} confirmed dead — verified by re-querying each PID, not by the kill's exit code"
exit 0
