# `.captures/` — verification screenshots

⚠️ **Browser-verification captures go HERE, and this whole directory is gitignored.**

A `browser_take_screenshot` with a bare relative filename writes to the **repo root**, so every
verification pass used to leave loose PNGs behind under a fresh prefix (`d1-1-tasks-*`, `d8-2-ready-by`,
`do9-*`, `restore*`, …). The old defence was a `.gitignore` line per prefix, which is whack-a-mole: the
next pass invents a prefix nobody has written a rule for yet, and it either litters `git status` or —
worse — gets swept into a commit by a `git add -A`. **That is not hypothetical: commit `3104918` did
exactly that to 18 of them.**

## The convention

Pass the directory in the filename:

    browser_take_screenshot(filename: ".captures/whatever-you-are-proving.png")

Anything under `.captures/` is ignored by a single rule, whatever it is called. `/*.png` at the repo root
is ignored too, as a backstop for the times the convention is forgotten.

⚠️ **A `.gitignore` rule does NOT untrack an already-tracked file.** The ~80 root capture PNGs committed
before this convention existed remain tracked and versioned exactly as they were — the root rule only
stops NEW ones being added. If you ever genuinely want a new root-level image in the repo, `git add -f`
it deliberately.
