#!/usr/bin/env python3
"""⚠️⚠️ THE MUTATION HARNESS — it REFUSES to report a result it cannot justify.

⚠️ WHY THIS EXISTS. Three times a mutation reported "NOT CAUGHT" when the mutation itself did nothing:

  1. an `Event::listen` removal that was INERT because Laravel auto-discovers listeners — the file DID
     change, and the mutation still tested nothing;
  2. and 3. two ANCHOR MISSES, where the replacement never applied and the run reported "passed".

⚠️ Each was caught by a person distrusting a suspiciously clean result. THAT IS LUCK, and it was happening
on money code. A harness whose failure mode is a false "everything is fine" is worse than no harness.

⚠️ SO THIS ENFORCES TWO THINGS, IN ORDER:

  · THE EDIT MUST HAVE HAPPENED — the anchor matches EXACTLY ONCE and the file content differs afterwards.
    ⚠️ An anchor miss is an ERROR. It is never, under any circumstances, reported as a pass.

  · THE EDIT MUST HAVE MATTERED — some test must FAIL under it. ⚠️ A file that changed is NOT proof that
    behaviour changed; that is precisely how the `Event::listen` case slipped through. If every test still
    passes, the result is **NO TEST COVERS THIS**, which is a FINDING, not a pass.

⚠️ Usage:  python3 tools/mutation/mutate.py <file> <anchor> <replacement> <pest-filter>
⚠️ The file is ALWAYS restored, and the restore is verified byte-for-byte before exiting.
"""
import hashlib
import io
import subprocess
import sys


def digest(path: str) -> str:
    with open(path, 'rb') as fh:
        return hashlib.sha256(fh.read()).hexdigest()


def main() -> int:
    if len(sys.argv) != 5:
        print(__doc__)
        return 2

    path, anchor, replacement, filt = sys.argv[1:5]
    original = io.open(path, encoding='utf-8').read()
    before = digest(path)

    # ⚠️ GUARD 1 — THE EDIT MUST HAVE HAPPENED, and unambiguously.
    hits = original.count(anchor)
    if hits == 0:
        print(f'  ⛔ ERROR — ANCHOR MISS in {path}. The mutation never applied.')
        print('     ⚠️ This is NOT a pass and must never be reported as one.')
        return 3
    if hits > 1:
        print(f'  ⛔ ERROR — the anchor matches {hits} times in {path}; the mutation is ambiguous.')
        return 3

    io.open(path, 'w', encoding='utf-8').write(original.replace(anchor, replacement, 1))

    if digest(path) == before:
        io.open(path, 'w', encoding='utf-8').write(original)
        print(f'  ⛔ ERROR — {path} is unchanged after the edit (the replacement equals the anchor).')
        return 3

    try:
        proc = subprocess.run(
            ['php', 'artisan', 'test', '--filter', filt],
            capture_output=True, text=True, timeout=1800,
        )
        out = proc.stdout + proc.stderr
        failed = '"result":"failed"' in out
    finally:
        io.open(path, 'w', encoding='utf-8').write(original)
        if digest(path) != before:
            print('  ⛔ ERROR — RESTORE FAILED. The working tree is dirty; fix before trusting anything.')
            return 4

    # ⚠️ GUARD 2 — THE EDIT MUST HAVE MATTERED. A changed file proves nothing on its own.
    if failed:
        print('  ✅ CAUGHT — the mutation applied and a test failed under it.')
        return 0

    print('  ⚠️ NO TEST COVERS THIS — the mutation applied and changed the file, but every test still')
    print('     passed. ⚠️ That is a FINDING (an uncovered guard), NOT a pass.')
    return 1


if __name__ == '__main__':
    sys.exit(main())
