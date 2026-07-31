# Guest emails — Booking Confirmation + Cancellation + Email Previews harness (exact-match audit)

**Wireframes:** `docs/design/raw/Booking Confirmation Email.html`, `Cancellation Email.html`, `Email Previews.html` (decoded → `scratchpad/wf-booking-confirmation.html`, `wf-cancellation.html`, `wf-email-previews.html`). PHASE 1, read-only.

## Headline — this is an EXACT-MATCH REBUILD of existing pieces (not net-new)

The task's premise ("two NEW Mailables, 5→7") is **half true**: `BookingConfirmationMail` + `CancellationMail` **already exist** (Phase 3 · Sub-gate 4) but are **minimal** (a few rows each) and **not threaded through the #86 editor**; a working `EmailPreviewController` + `operator.email-previews` page **already renders all 9 real Mailables**; and `RefundConfirmationMail` (the wireframe's 4th sibling) is **already done + threaded**. So the work is: (1) **rebuild** the two emails' blades to their wireframes; (2) **thread both through #86** (extend `EmailTemplateCatalog` 5→7, add a `template()` + `EmailPreviewSample` arm + the `enabled()` trigger-guard; add `BrandsGuestMail` to `CancellationMail`); (3) **rebuild the Previews page** to the tabbed gallery + real test-send. The #83 five + `GuestEmailTest` + the #86 editor stay undisturbed (catalog defaults === current strings, byte-faithful).

## The ⚠️ crux — reconciliation + the honesty refusals (founder decisions)

| # | Decision | Verdict | Why |
|---|---|---|---|
| A | **Masked card "Visa ···· 4242"** (both emails) | 🚫 **REFUSE → "your original payment method"** | `Payment` is **token-only** (`card_token`, `$hidden`); **no brand/last-4 stored anywhere** (SAQ A / #28/#45). `RefundConfirmationMail` already codifies this exact honest string + `GuestEmailTest` asserts the email does **not** contain `····`. A fabricated `····4242` is refused. |
| B | **Confirmation price breakdown** (rate×nights / cleaning / city tax / Total) | ✅ **REAL via folio line items, reconciling #17** | Render the **real `folio_items`** (description + `amount_minor`), Σ === `FolioTotals::for($folio)['charges_minor']`. The **Total === `reservation->total_minor`** (the test-locked, #17-reconciling figure). Room is stored as **one lump** for storefront bookings (no nightly split), cleaning/city-tax lines only if charged → **degrade honestly** to "Accommodation … Total"; never fabricate a split. Label **"Total"** (a confirmation can precede full payment), not "Total paid". |
| C | **Cancellation refund summary** (deposit / non-refundable / waived split + status timeline) | 🚫 **REFUSE the itemized split** → honest two cases | There is **no cancellation-policy / refund engine** — a cancel passes `refundMinor=null` **by design**; a real refund is a **separate** operator action firing its **own** `RefundConfirmationMail`. So the cancellation email shows: **Case A** (a real recorded `refundMinor` > 0) → "Refund of {amount} to your original payment method · arrives in 3–5 business days"; **Case B** (null/0) → "No refund due" + honest note. The deposit/non-refundable/waived math + the "Reviewed" timeline step are **fabrications, refused**. |
| D | **"Book direct next time with WELCOME10 · 10% off"** | 🚫 **REFUSE** → warm invitation, no code | No promo/% engine (`PromoCode` is a fixed-amount wallet credit, never auto-attached). `WinbackMail` precedent: "no fabricated discount, just a warm invitation back." A real storefront link, no fabricated code. |
| E | **OTA brand "Booked via Booking.com"** | ✅ real source / 🚫 no brand | `reservations.source` (direct/ota/…) is **real** → render the source label; the specific **OTA brand** is unmodelled (#85, same REFUSED precedent as owner-bookings) → never fabricate a brand. |
| F | **Calendar links (Google / Outlook / Apple .ics)** | ✅ **REAL — buildable from real dates** | Genuinely generatable from `reservation` dates + reference + property location (no fabrication). Build a small `App\Support\Email\CalendarLinks` helper (Google-render URL, Outlook deep-link, `.ics` data-URI). |
| G | **Confirmation door code** | ✅ **POINTER only (#33)** | The email fires on `reservation.confirmed` (months before the `ArrivalPack` 48h window); it shows a **promise** ("Door code & directions · arrive 48h before, by email and SMS"), **never the code** — `PreArrivalMail` is the real carrier. |
| H | **Previews send-test recipient** | ✅ **LOCK to the operator's own email** | The certified `testSend` sends **only** to `request->user()->email` (never an arbitrary/guest inbox). The wireframe's free-typed recipient is the fabrication to refuse → prefill + lock (or drop the input). |
| I | **~10-min reply SLA / "Reviewed" step / "sea-view studio"** | 🚫 drop/genericize | No response-time metric, no `reviewed_at`, no room descriptor modelled — genericize honestly. |

## Per-element — Booking Confirmation (`emails/booking-confirmation.blade.php` + Mailable)
| Section | Verdict | Reuse / plan |
|---|---|---|
| Brand header band (gradient · V logo · property · locality · "Confirmed" pill) | ✅ REAL / 🔨 | `BrandsGuestMail` + `PropertyBrand::for` (already used); add the band to `emails.layout` opt-in (don't regress the other 15 emails). |
| Hero image band + "{{villa}} · sea-view studio" | 🔨 / 🚫 | Real unit/property name; the "sea-view studio" descriptor is unmodelled → omit. |
| "You're all set, {{guestName}}" + intro | ✅ REAL-NOW | already present (`GuestName::display`); becomes `{{ $heading }}`/`{{ $intro }}` under #86. |
| Dates block (check-in "from 15:00" / check-out "by 11:00") | ✅ REAL-NOW | dates real; times from `property.check_in_time`/`check_out_time` (real columns). |
| Facts row (Guests / Nights / Reference) | ✅ REAL-NOW | `adults`+`children`, nights, `reference`. |
| **Price breakdown** | 🔨 BUILD (see B) | real `folio_items` + `FolioTotals`; Total === `total_minor` (#17, test-locked). |
| Paid-with (masked card · receipt · View receipt) | 🚫 card (see A) / ✅ receipt link | "your original payment method" + "View receipt ›" → the real guest folio-receipt/reservation route. |
| Buttons (View your booking / See arrival info) | ✅ REAL-NOW | real guest routes. |
| Before-you-arrive (check-in online · door code pointer) | ✅ REAL (see G) | `guest.checkin` link + the honest 48h pointer. |
| Host signature ("~10 min" · team · Message us) | 🔨 (drop SLA) | real team name (`PropertyBrand`) + `guest.messages`; drop the fabricated SLA. |
| Footer ("Booked direct · paid in full · free cancellation until {date}" · calendar links · policy · prefs/unsubscribe · address) | ✅ REAL / 🔨 | source (#85), "free cancellation until" from `PropertyPolicy.cancellation.free_until_days` (compute or omit); **calendar links REAL** (see F); prefs/unsubscribe already wired; address from `PropertyBrand`. |

## Per-element — Cancellation (`emails/cancellation.blade.php` + Mailable)
| Section | Verdict | Reuse / plan |
|---|---|---|
| Brand header + "Cancelled" pill | 🔨 BUILD | **add `BrandsGuestMail`** (currently a bare Envelope — the real gap) + the band. |
| "Your booking is cancelled" + intro | ✅ REAL-NOW | becomes `{{ $heading }}`/`{{ $intro }}`. |
| Cancelled-stay strip (villa · dates / nights · guests · ref) | ✅ REAL-NOW | real columns. |
| Reason chip(s) | 🔨 single | one real `cancellation_reason` (multi-chip refused). |
| **Refund summary** (deposit / non-refundable / waived / refund-to / timeline) | 🚫 split (see C) | honest two-case: real `refundMinor` or "No refund due"; card → "original payment method"; drop the fabricated split + "Reviewed" step. |
| "Booked via {{source}}" footnote | ✅ source / 🚫 brand (see E) | real source label; no OTA brand. |
| Return offer (WELCOME10) | 🚫 (see D) | warm invitation + real link, no code. |
| Primary button + host signature + footer | ✅ REAL / 🔨 | real routes; `{{ $button }}` under #86. |

## Per-element — Email Previews (`operator.email-previews` + `EmailPreviewController`)
| Element | Verdict | Reuse / plan |
|---|---|---|
| Header ("Email previews" / "Switch between the automated guest emails") | ✅ REAL-NOW | `x-operator.shell` title/subtitle (subtitle string diverges — trivial). |
| Tab switcher (9 pill tabs) | 🔨 BUILD | reuse `EmailPreviewController::CATALOG` (9 keys); an inline tab-switch (Alpine) over an `<iframe src=show/{key}>` for email-CSS isolation. |
| Preview panel (inline render of the real email) | ✅ REAL (reuse) | `app.email-previews.show/{key}` already returns the **real** `$mailable->render()` (can't drift); embed vs current `_blank`. |
| **Send-test** (recipient + button + toast) | 🔨 BUILD (see H) | reuse the certified `EmailTemplateActionController::testSend` (own-email only, `can:properties.manage`, `MailSafetyGuard`); recipient **locked to the operator**; real flash (no fake toast). Read-only: a test-send is a send, not a data write — gate on the capability; confirm behaviour under pause. |
| Honest per-email status labels | ✅ keep | keep the "Live · sends on…" captions (honest signal the WF drops). |

## Reuse map (anti-drift)
`BookingConfirmationMail`/`CancellationMail`/`RefundConfirmationMail` (the #83 patterns) · `BrandsGuestMail` + `PropertyBrand` (branding) · `EmailTemplateResolver`/`ResolvedEmailTemplate`/`EmailTemplateCatalog` (#86 — `template()` pattern per `RefundConfirmationMail`) · `EmailPreviewSample` (the ONE sample builder — preview + test-send + real send share it) · `EmailTemplateActionController::testSend` (own-email test-send) · `FolioTotals` + `folio_items` (#17) · `MoneyFormatter` · `GuestName::display` (#28 name-only) · `ArrivalPack` (#33 — pre-arrival only) · `SendGuestTransactionalEmails` (the real `reservation.confirmed`/`reservation.cancelled` triggers) · `emails.layout` (shared header/footer) · a new `App\Support\Email\CalendarLinks` (real ICS/Google/Outlook).

## Build plan (gated)
- **⛔ GATE 1 — the two Mailables exact-match + #86 threading.** Rebuild `emails/booking-confirmation.blade.php` (brand band, dates+times, facts, **real folio breakdown + Total===total_minor**, honest paid-with, before-you-arrive pointer, buttons, host block, footer + **real calendar links**) + `emails/cancellation.blade.php` (brand band via new `BrandsGuestMail`, stay strip, single reason, **honest two-case refund**, source, warm invitation, footer). Thread both through #86: extend `EmailTemplateCatalog` 5→7 (defaults === current strings), add `template()` to each Mailable, add `EmailPreviewSample` arms, blades read `$heading/$intro/$button/@if($blocks[...])`, add the `enabled()` guard to the two triggers. `App\Support\Email\CalendarLinks` helper. Tests: confirmation breakdown reconciles (Σ items / Total === total_minor #17) + door-code-pointer-not-code + no `····` card; cancellation two cases (real refund vs honest no-refund) + branded + source honest; both thread through #86 (customization renders; switched-off doesn't send). `GuestEmailTest` stays green (defaults byte-faithful). Render-verify. Commit.
- **⛔ GATE 2 — Email Previews harness + consolidate.** Rebuild `operator.email-previews` to the tabbed gallery (9 tabs) + inline `<iframe>` preview (reuse `show`) + the real **test-send** (own-email locked, `can:properties.manage`, `MailSafetyGuard`, honest flash). Keep the honest status captions. Consolidate docs (this file gaps→resolved · `DECISIONS.md` dated · `DIVERGENCE-REGISTER.md` Confirmation/Cancellation/Previews → exact-design · `PROJECT-STATE.md` + invariant bump). Full suite green + PHPStan L5 + Pint + `migrate:fresh --seed`. Render-verify (both emails render real + reconcile; the gallery renders all 9 + a real test-send). Commit + push.

---

## OUTCOME — gaps → resolved (2026-08-01, PHASE 2 delivered, gated; invariant #109)

**GATE 1 (`2364256`) — the two Mailables exact-match + threaded through #86.** `BookingConfirmationMail` +
`CancellationMail` (they existed but minimal) rebuilt to their wireframes, email-safe. **Confirmation:** the REAL
folio-item price breakdown with **Total === `total_minor`** (#17-reconciling; degrades honestly to Total-only when
the folio is empty — no fabricated nightly split), real dates + property check-in/out times, a real receipt link,
and **add-to-calendar** (Google/Outlook/Apple `.ics`) via a new `App\Support\Email\CalendarLinks` from real dates;
**HONEST (#28/#39):** no masked card → "your original payment method"; the door code is a **48h pointer only**
(#33); the source is the real category, never an OTA brand (#85); "free cancellation until {date}" from the real
`PropertyPolicy.cancellation.free_until_days`. **Cancellation:** now **branded** (added `BrandsGuestMail` — the
real gap) + the **two honest cases** (a real recorded refund, or "No refund due" — the deposit/non-refundable/
waived split is unmodelled and **refused**; a real refund fires its own `RefundConfirmationMail`); a warm come-back
invite, **no fabricated WELCOME10 promo** (#39). **#86 threading:** both added to `EmailTemplateCatalog` (defaults
byte-faithful so `GuestEmailTest` stays green), a `template()` → `EmailTemplateResolver` on each, `EmailPreviewSample`
arms, blades read `$heading/$intro/$button/@if($blocks[...])`, and the `enabled()` guard added to both triggers so
the on/off toggle is real. +5 `GuestEmailBookingCancellationTest`.

**GATE 2 (`73bc366`) — the Email Previews harness.** `operator.email-previews` rebuilt to the tabbed gallery: a
9-pill tab switcher over the automated emails, an inline **`<iframe>`** rendering the REAL Mailable per tab (via
`app.email-previews.show` — **can't drift** from the send), the honest per-email status caption, and a **real
Send-test** (`EmailPreviewController::testSend`, extracted the ONE shared Mailable builder) to the **operator's OWN
inbox only** (the certified rule — the wireframe's free-typed recipient **refused**, the field read-only-locked;
`MailSafetyGuard` downgrades outside production). Gated `can:properties.manage` (#30) + `@can`-gated control (never
a 403-ing button). +3 `OperatorEmailPreviewsTest`.

**Not in scope (already done):** the 4th sibling wireframe `Refund Confirmation Email.html` is already `RefundConfirmationMail`, threaded through #86 (catalog `refund-confirmation`).

Verification: full suite green (the #83 five + #86 editor undisturbed); PHPStan L5 (0); Pint clean;
`migrate:fresh --seed` clean. Both emails **browser-rendered** via the previews route; the gallery + tab-switch +
gated test-send **browser-verified**. Presentation boundary documented: the wireframes' frosted-glass/gradient
hero is not email-safe — the emails evoke the brand with table-based, inline-CSS, solid warm colours (the shipped
`emails.layout` discipline), which is the honest email-client-safe rendition.
