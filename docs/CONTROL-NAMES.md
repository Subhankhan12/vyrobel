# Accessible names for form controls

**2026-09-04.** The census moved, the split the brief described did not exist, and I shipped a defect
mid-pass and caught it.

---

## 1 · The re-derived census

| | |
|---|---|
| form controls in views | **832** |
| **excluded** — genuinely need no name | **253** |
| **named** already | **493** |
| ⚠️ **unnamed** | **86** (the brief said 79) |

**Exclusions, each with its reason** — 251 `type=hidden` · 1 `aria-hidden` password-manager decoy · 1
honeypot. ⚠️ **That the exclusion set is 253 is itself the finding**: a naive scan of 832 controls would
report a catastrophe where the truth is 86.

⚠️⚠️ **155 controls are named by a WRAPPING `<label>`** — an association with neither `for` nor
`aria-label`. **A scan missing those would have claimed 241 unnamed.** That is how the original count was
wrong, and it is why the census recognises all four means: `for`/`id` (245) · `aria-label` (93) ·
`aria-labelledby` (resolving) · wrapping (155). I sampled five wrapping detections and confirmed each is
genuinely wrapped rather than trusting the parser.

### ⚠️ The brief's split does not exist

It described **30 with an adjacent visible `<div>` label** (free) and 49 placeholder-only. Re-derived:

| shape | count |
|---|---|
| placeholder-only | **57** |
| **no name text at all** | **29** |
| of the 86, having an adjacent label-ish element | ⚠️ **7** — and several of those were false positives |

**So this pass was not mostly free.** The design-neutral `<div>` → `<label for>` promotion the brief expected
to cover thirty controls covered almost none: the label text simply is not there.

---

## 2 · What was done, and the rule that decided it

An applier took label text **only from an authored placeholder** and **refused otherwise** — the discipline
the dialog pass established when it refused to name four dialogs rather than invent an id.

**Visible or hidden was decided by a detectable rule, not by convenience:** a control whose *sibling* fields
already carry a visible house label (`vy-field-label`) gets a **visible** label, because one demonstrably
fits that container; a control in a toolbar, filter row or inline context gets `.vy-sr-only`.

| | |
|---|---|
| labelled | **51** |
| of which **visible** | 11 |
| of which **visually hidden** | 40 |
| **refused and reported** | **35** |

**Refusal reasons:** 29 have **no authored text** to promote · the rest could not be anchored uniquely, or
sit inside a `@foreach` where minting a fixed id would **duplicate it N times** — the exact defect the dialog
pass had to solve with a caller-supplied id.

⚠️ **The visible half is not solved and I am not claiming it is.** 40 controls got a hidden label, which
names them for assistive tech and leaves the sighted problem in place: a placeholder still vanishes on the
first keystroke. Those 40 are where a visible label would change 40 layouts I could not verify in this pass.
**Reported as remaining work rather than described as done.**

---

## 3 · ⚠️⚠️ A defect I shipped mid-pass and caught

The applier promoted placeholders to labels. Nine of those placeholders were **format hints, not names** —
and **three rendered as VISIBLE labels**, including one reading **"0.00"** on the maintenance parts/labour
row. A visible label reading "0.00" is worse than no label.

**All nine were removed.** Two were re-wired to a **real** adjacent label (`"Labor"`, and one other); seven
were left unnamed and reported, because a hint is not a name.

⚠️ **And one of those two re-wires was itself wrong.** `unit-price-form`'s "What to do" labels a mode
selector, and the script pointed it at the price input. **Caught by reading the result rather than trusting
that the rewire happened**, and undone — that field is now in the reported remainder.

⚠️ **`.vy-sr-only` was undefined in two shells.** It is defined in the operator, owner and guest shells but
not in **admin** or **marketing** — and I had placed hidden labels in both. **An undefined class hides
nothing: those labels would have rendered as stray text on the page.** The rule is now defined in both,
copied verbatim, with a comment saying why.

---

## 4 · The deliberate controls, untouched

The `aria-hidden` password-manager decoy, the honeypot and the visually-hidden chip radio are **excluded with
their reasons in the guard**, not silently skipped. ⚠️ **How deliberate was distinguished from defective:** a
control is deliberate only where the markup itself carries the intent — `aria-hidden="true"`, a
`tabindex="-1"` honeypot name, a 1px focusable control driving `:checked +`. Anything I could not explain was
**reported, not changed**.

---

## 5 · ⚠️ NO POSTED KEY MOVED

**A label is presentation; a `name` is behaviour, and one of these fields writes a real owner-ledger charge.**
The applier only ever inserts a `<label>` element and an `id` attribute — **an `id` is never submitted, which
is the whole reason this is safe**. Verified against the diff: **zero `name="…"` values removed**. (Seven
appear on added lines; those belong to a seasonal-pricing form from an earlier uncommitted pass, not to this
one — `git diff` is against HEAD, not this pass's start.)

---

## 6 · The guard

`tests/Support/label_helpers.php`, beside `heading_helpers` and `dialog_helpers` — **discovering, not
listing**, as the dialog pass proved necessary when two dialogs that "looked done" were incomplete.

- a **ceiling** on unnamed controls, so the number cannot grow — explicitly **not** a blessing of the
  remainder;
- **no dangling `aria-labelledby`**;
- ⚠️ a **wrapping-label floor**: if that detection ever breaks, the first assertion would suddenly "find" 155
  offenders and someone would go and fix correct markup;
- **exclusion reasons asserted non-empty**, and the decoy and honeypot named.

⚠️ **The ceiling moved from 41 to 42 and the delta was checked, not bumped.** It is the `unit-price-form`
amount field, whose minted id was removed when the mis-wire above was undone. **A number raised to green a
gate is the thing this project refuses**; re-deriving confirmed the two independent censuses then agreed.

⚠️ **What the guard cannot prove:** that a name is a *good* one. This pass demonstrated that the hard way.
