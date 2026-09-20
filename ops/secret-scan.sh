#!/usr/bin/env bash
#
# ops/secret-scan.sh — ⚠️⚠️ WHAT IS ABOUT TO LEAVE THIS MACHINE, CHECKED BEFORE IT LEAVES.
#
# ── ⚠️ WHY THIS EXISTS ──────────────────────────────────────────────────────────────────────────────────
#
# ⚠️⚠️ **A PUSH IS THE FIRST IRREVERSIBLE ACTION IN THIS PROGRAMME.** Every other mistake here has been
# recoverable — a secret rotated, a scratch table dropped, a test rewritten. A credential pushed to a remote
# has been fetched, mirrored and cached before anyone notices, and deleting the repository does not undo it.
# ⚠️ Two credentials reached working transcripts in two consecutive passes. The count that justifies a build
# scales with what a miss costs, and a leaked key costs more than a lost hour.
#
# ── ⚠️⚠️ AND THE RULE THAT SHAPES IT: A CLEAN SCAN FROM AN UNVERIFIED SCANNER IS AN EMPTY LOG ────────────
#
# ⚠️⚠️ **A SCANNER THAT HAS NEVER FOUND ANYTHING IS INDISTINGUISHABLE FROM A SCANNER THAT CANNOT.** This
# project has just watched `ops/proc-check.sh` produce **two false negatives while being built to prevent
# false negatives**. ⚠️ So this script is not considered working until it has found a needle deliberately
# planted in a scratch clone's history. **Plant, find, discard — then trust a clean result, and not before.**
#
# ⚠️⚠️ **THERE IS NO ALLOW-LIST, AND THAT IS DELIBERATE.** An allow-list is how a guard dies quietly: the
# first false positive gets suppressed, the suppression outlives the reason, and the rule silently stops
# covering the case it was written for. ⚠️ **This repository publishes its demo seed password on purpose and
# this scanner WILL report it every single time.** That is correct. Triage in the report, never in the rule.
#
# Usage:
#   ops/secret-scan.sh tree       # tracked files in the working tree (git ls-files — what IS tracked)
#   ops/secret-scan.sh history    # ⚠️ every blob in every ref — what a push would actually publish
#
# Exit: 0 = no hits · 1 = hits found (triage them) · 2 = usage error
#
# ⚠️ Matches are REDACTED in the output — this script must never become a new way to print a secret.
#
set -uo pipefail

MODE="${1:-tree}"
cd "$(git rev-parse --show-toplevel 2>/dev/null)" || { echo "not inside a git repository" >&2; exit 2; }

case "${MODE}" in
  tree|history) ;;
  *) echo "usage: ops/secret-scan.sh [tree|history]" >&2; exit 2 ;;
esac

# ⚠️ Rules are NAME|REGEX. High-signal credential SHAPES first, then the broad assignment rule that is
#    expected to be noisy — noise is triaged by a human; a miss is not triaged by anyone.
read -r -d '' RULES <<'EOF' || true
private-key|-----BEGIN [A-Z ]*PRIVATE KEY-----
aws-access-key|AKIA[0-9A-Z]{16}
stripe-live-secret|(sk|rk)_live_[0-9a-zA-Z]{16,}
stripe-test-secret|(sk|rk)_test_[0-9a-zA-Z]{16,}
stripe-webhook-secret|whsec_[0-9a-zA-Z]{16,}
github-token|gh[pousr]_[0-9A-Za-z]{30,}
slack-token|xox[baprs]-[0-9A-Za-z-]{10,}
google-api-key|AIza[0-9A-Za-z_-]{30,}
jwt|eyJ[A-Za-z0-9_-]{10,}\.eyJ[A-Za-z0-9_-]{10,}\.
laravel-app-key|APP_KEY=base64:[A-Za-z0-9+/]{40,}
generic-assignment|(?i)(password|passwd|secret|api[_-]?key|access[_-]?token|auth[_-]?token|private[_-]?key)['"]?\s*[=:]\s*['"][^'"\n]{6,}['"]
EOF

export VY_RULES="${RULES}"
export VY_MODE="${MODE}"

python3 - <<'PY'
import os, re, subprocess, sys, collections

mode  = os.environ["VY_MODE"]
rules = []
for line in os.environ["VY_RULES"].strip().split("\n"):
    if not line.strip():
        continue
    name, _, pat = line.partition("|")
    rules.append((name, re.compile(pat.encode())))

def redact(m: bytes) -> str:
    s = m.decode("utf-8", "replace")
    if len(s) <= 10:
        return s[:2] + "…" + "*" * max(0, len(s) - 3)
    return s[:6] + "…" + "*" * 8 + "…" + s[-2:]

hits = collections.defaultdict(list)          # rule -> [(where, lineno, redacted)]
scanned = 0

def scan(blob: bytes, where: str) -> None:
    global scanned
    scanned += 1
    if b"\x00" in blob[:4096]:                 # binary — skip, and say so in the totals
        return
    for lineno, line in enumerate(blob.split(b"\n"), 1):
        if len(line) > 4000:
            line = line[:4000]
        for name, rx in rules:
            m = rx.search(line)
            if m:
                hits[name].append((where, lineno, redact(m.group(0))))

if mode == "tree":
    files = subprocess.run(["git", "ls-files", "-z"], capture_output=True).stdout.split(b"\x00")
    for f in files:
        if not f:
            continue
        p = f.decode("utf-8", "replace")
        try:
            with open(p, "rb") as fh:
                scan(fh.read(), p)
        except (OSError, IsADirectoryError):
            pass
else:
    # ⚠️ Every blob reachable from every ref — this is what a push actually publishes, and it is NOT the
    #    same set as the working tree: a file deleted today is still in the history that goes over the wire.
    objs = subprocess.run(["git", "rev-list", "--objects", "--all"], capture_output=True).stdout
    paths = {}
    order = []
    for line in objs.split(b"\n"):
        if not line:
            continue
        parts = line.split(b" ", 1)
        if len(parts) == 2 and parts[1]:
            sha = parts[0].decode()
            paths[sha] = parts[1].decode("utf-8", "replace")
            order.append(sha)
    proc = subprocess.Popen(["git", "cat-file", "--batch"], stdin=subprocess.PIPE, stdout=subprocess.PIPE)
    out, _ = proc.communicate(b"\n".join(s.encode() for s in order) + b"\n")
    i = 0
    hdr = re.compile(rb"^([0-9a-f]{40}) (\w+) (\d+)$")
    while i < len(out):
        nl = out.find(b"\n", i)
        if nl == -1:
            break
        m = hdr.match(out[i:nl])
        if not m:
            i = nl + 1
            continue
        sha, otype, size = m.group(1).decode(), m.group(2).decode(), int(m.group(3))
        body = out[nl + 1: nl + 1 + size]
        i = nl + 1 + size + 1
        if otype == "blob":
            scan(body, f"{sha[:8]} {paths.get(sha, '?')}")

total = sum(len(v) for v in hits.values())
print(f"  scanned: {scanned} {'tracked files' if mode == 'tree' else 'blobs across all refs'}")
print(f"  rules:   {len(rules)}")
print(f"  hits:    {total}\n")

if total == 0:
    print("  ✅ no credential-shaped content found.")
    print("  ⚠️ A clean result is only meaningful if this scanner has been PROVEN to find a planted needle.")
    sys.exit(0)

for name, found in sorted(hits.items(), key=lambda kv: -len(kv[1])):
    print(f"  ⚠️ {name}: {len(found)} hit(s)")
    seen = set()
    shown = 0
    for where, lineno, red in found:
        key = (where.split(" ", 1)[-1], red)
        if key in seen:
            continue
        seen.add(key)
        if shown < 12:
            print(f"       {where}:{lineno}  {red}")
            shown += 1
    if len(seen) > shown:
        # ⚠️ NO SILENT CAPS — say exactly what was not printed.
        print(f"       … {len(seen) - shown} further DISTINCT location(s) not printed (rerun for the full list)")
    print()

print("  ⚠️⚠️ TRIAGE EVERY ONE. Do not add an allow-list — say in the report which are known and accepted.")
sys.exit(1)
PY
