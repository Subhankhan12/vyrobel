# The signed-in guest screens had no headings — and the cause was a false comment

**2026-09-03.** Measured from the **served page**, logged in as the demo guest.

---

## 1 · Why the signed-in screens differed — and it was one line, not one place

Four signed-out screens each had exactly one `<h1>`; the signed-in ones had none. The two groups use
**different layouts**:

| group | layout |
|---|---|
| signed-out (`login`, `claim`, `forgot-password`, `home`) | `@extends('guest.auth.layout')` |
| signed-in (everything else) | `<x-guest.shell active="…" title="…">` |

⚠️⚠️ **And the shell's own docblock named the intent — falsely:**

> `Props: active (nav key to highlight), title (optional page heading).`

**It feeds `<title>` and nothing else.** Every signed-in screen already passed a subject — `"Account"`,
`"Messages"`, `"Your trips"`, `"Saved"`, `"Wallet"` — into a prop documented as a *page heading* that no
code ever rendered as one. **That is very likely the whole cause**, and it is the same shape this project
has now caught six times: a comment describing what the code does not do. It has been corrected.

⚠️ **But the fix was NOT one place.** The shell cannot supply the heading, because each page's real subject
text lives in **its own hero** — and eleven of them were already displaying it. Making the shell emit an
`<h1>` from `title` would have produced a *second*, duplicate subject on every one of those screens.

---

## 2 · Per screen: promotion, move, or genuinely missing

⚠️ **Nothing was invented.** Eleven were the `/contact` shape exactly — a `<div>` styled as a heading:

| screen | element found | fix |
|---|---|---|
| `messages` · `notifications` · `payments` · `saved` | `<div style="…24-26px">Messages/…</div>` | ✅ **PROMOTION** |
| `manage` | `"Manage your booking"` | ✅ PROMOTION |
| `review` | `"How was your stay?"` | ✅ PROMOTION |
| `trips` | `"Welcome back, {{ $firstName }}"` | ✅ PROMOTION |
| `account` | `{{ $hero['name'] }}` | ✅ PROMOTION |
| `receipt` · `reservation` | `{{ $trip['property'] }}` | ✅ PROMOTION |
| `message-thread` | `{{ $subject }}` | ✅ PROMOTION |
| **`wallet`** | ⚠️ **nothing** — only an eyebrow (`"Available credit"`) labelling the balance | ⚠️ **WRITTEN — PROVISIONAL** |
| `booking/reservation` (confirmation) | `<div class="vy-confirm-title">Booking confirmed</div>` | ✅ PROMOTION |
| `booking/checkout` (empty branch) | the sentence `"That room isn't available for these dates."` | ✅ PROMOTION |

⚠️ **`wallet` is the only written heading**: `<h1>Wallet</h1>`, a plain accurate noun phrase matching the nav
item and the shell's own `title` prop — deliberately not branding, not a slogan. **The wording is the
founder's; only the element is not.** Marked provisional in the view itself.

⚠️ **No fresh skipped level behind the fix** — the second-order miss from the marketing pass. Every promoted
view had **zero** `h2`/`h3` beforehand, so each now has exactly one `h1` and nothing beneath it. Verified
from the served page, not reasoned.

⚠️ **`margin:0` on every promotion**: an `<h1>` carries default margins a `<div>` does not, so without it the
fix would have shifted eleven layouts. **The rendering is unchanged.**

---

## 3 · The storefront — and a correction to the brief

⚠️ **`checkout.blade.php` does have an `<h1>` in source.** It lives inside the `@else`, reached only with a
**live offer in session**. The `@if ($offer === null)` branch — a stale link, an expired quote — had **no
heading at all**, and that is exactly the state the audit reached, because it *could not* obtain a live
quote. **So the reported zero was real, and specific to the branch a stranger is most likely to land in.**
Both branches now carry one.

**The whole flow, not the one screen named:**

| view | before | after |
|---|---|---|
| `browse` | 1 | 1 ✅ |
| `room` | 1 | 1 ✅ |
| `landing` | 1 (+5 h2, 1 h3) | unchanged ✅ |
| **`checkout`** | 1 in the `@else`, **0 in the `@if`** | 1 in **both** ✅ |
| **`reservation`** (confirmation) | ⚠️ **0** — not named in the brief | 1 ✅ |

⚠️ **The D3 field-error fix is still live** — six `@error` directives remain in `checkout.blade.php`, and the
guard asserts it, because this pass edited that same view.

**How the checkout was reached:** with **no quote in session** — the same way the audit got there — so the
test exercises the branch that was actually broken.

---

## 4 · The full census

⚠️ **The brief said seven. Twelve views had zero.** The audit measured **routed** screens; the parameterised
ones (`receipt`, `reservation`, `manage`, `review`, `message-thread`) it never reached were in the same state.

⚠️ **One apparent defect was a false positive:** `marketing-unsubscribe` counts **two** `<h1>` in source, but
they are `@if ($done)` / `@else` — mutually exclusive, one renders. **A source count is not evidence**, which
is why the served page is the standard.

**Served-page result, logged in as the guest — 13/13 screens now have exactly one `<h1>`, no skipped level,
`lang="en"`, one `<main>`.**

### ⚠️ The owner realm — REPORTED, NOT FIXED

Measured the same way: **32 of 35 correct**. Three carry **two `<h1>`s**:
`/owner/agreement` · `/owner/agreement/pdf` · `/owner/welcome`. Left alone by instruction.

---

## 5 · A heading is not a title

Every signed-in screen already had a correct `<title>` — the shell renders `"{title} · Vyrobel"`. ⚠️ **The
heading text is NOT that string:** it would carry the product name and a separator, which is wrong in a
heading. Where a promotion existed, the heading is the page's **own displayed** subject (which sometimes
differs from the title on purpose — `trips` is titled "Your trips" but headed "Welcome back, …", because
that is what the page actually shows). Only `wallet` uses the title's word, because it had nothing else.

**Also checked while open, and all correct:** `lang="en"`, one `<main>` per page, viewport meta present.
Nothing else reported missing.

---

## 6 · The guard — extended, not duplicated

The marketing site already had this rule. It was **extracted into `tests/Support/heading_helpers.php`** and
the marketing guard now calls it, so there is **one definition across two realms** rather than a second copy
to drift (§N.8f). Marketing stayed green through the refactor (17/17).

⚠️ **Its limit is written into the helper's header, not implied:** it proves a heading **exists** at the
right level and that the outline is unbroken. **It cannot prove the heading says the right thing** — a page
headed "Untitled" passes. The per-screen reasoning above is the part a guard cannot hold.

---

## 7 · Visible, everywhere — no hidden headings

⚠️ **Eleven promotions changed nothing visually**: the text was already displayed at 20–26px; only its
element was wrong. So the "sighted half" was never the problem on those screens, and a visually-hidden
heading would have been strictly worse — a second, invisible copy of text already on screen.

**`wallet` gained one visible line**, styled exactly like its siblings' hero titles. That is a real but small
design change, and the alternative — a hidden heading — would have left wallet the only screen whose subject
a sighted user must infer from an eyebrow.

**No new D3 (unlabelled control) or D5 instance was created** — nothing but heading elements changed.

---

## 8 · Proof and mutations

⚠️ **WHO I WAS LOGGED IN AS, asserted** — the guest census landed on `/guest/trips` with a **"Sign out"**
control present and the page reading `"Demo Traveller"`; the owner census landed on `/owner`. A previous
census in this project silently ran as the wrong user, so the landing path is checked every time now.

**Mutations** — each applied, confirmed landed in its own block, run, reverted:

| mutation | |
|---|---|
| a heading **demoted back to a `<div>`** | **RED (1)** |
| a **second `<h1>`** added | **RED (1)** |
| a **level skipped** (h1 → h3) | **RED (1)** |
| the comment/style **strip removed** | ⚠️ **GREEN — see below** |

⚠️⚠️ **The strip mutation failed to land twice before it ran, and I nearly recorded its GREEN as a result.**
The first two attempts searched for `], $html);` when the line reads `], '', $html);`, so nothing was
replaced — the `landed:` check caught it both times. **A mutation that did not land is not a green.**

⚠️ **Once it did land, it came back GREEN, so the strip is precautionary rather than load-bearing — and the
reason is worth recording:** Blade removes its own `{{-- --}}` comments before the HTML is ever served, so
the comments this pass wrote (one of which contains the literal `<h1>`) can never reach a served-page scan.
The strip still protects a caller who passes raw Blade source, and the `<style>`/`<script>` half would matter
the day a page carries `<h1` inside a stylesheet — but claiming it was necessary today would be false.

⚠️ **And the same trap bit my own measurement**: an unstripped source count reported `checkout h1=3` when the
truth was 2, because my new comment contains `<h1>`. **The served page is the standard for exactly this reason.**
