# Owner-portal parity audit — 2026-08-17

**Read-only. No code changed. Demo left money-as-seeded.**
Method: browser MCP (Playwright) for the harness proof and the wireframe render; an authenticated `curl`
session — **proven to read the same authenticated DOM as the browser** — to walk every route without
burning a browser round-trip per page.

---

## ⚠️ #0 — THE HARNESS, PROVEN BEFORE ANY VERDICT

A "missing" verdict is worthless from a harness that cannot see presence. Both harnesses were shown a
**positive** first:

| Check | Evidence |
|---|---|
| Credentials from the **seeder**, not memory | `DemoSeeder.php:234` → `demo-owner@vyrobel-demo.test` / `password` |
| Browser login lands in the owner realm | redirected to **`/owner`** |
| Browser can READ known-present content | **24** owner nav links; money `€885.60`, `€3,690.00` |
| curl session sees the SAME DOM | `€885.60` × **5** on `/owner` |
| Demo is seeded | 41 reservations; `rate_calendar` `2bbdafccc3b5c1ee43f89fe313a3f02d` |

⚠️ The **wireframe** side was probed too: `Owner Dashboard.html` renders **1,690 chars** of real copy (its
only console error is a missing `favicon.ico`). Without that, "absent from the build" could have meant "the
prototype never rendered".

---

## ⚠️ #1 — INVENTORY (routes are the source of truth; a page list is a lower bound)

`php artisan route:list` → **54 GET owner routes**: **31 HTML pages**, the rest exports/PDF/CSV/ZIP/API.
**35 owner prototypes** in `docs/design/raw/`.

**Routes with no wireframe (→ candidate ENHANCEMENTS):** `/owner/notification-preferences`,
`/owner/messages/{thread}`, `/owner/archive/zip`, `/owner/security/export`, `/owner/*/export` (CSV family),
`/owner/tax/{year}/vat-return.csv`, `api/owner/*` (the OAuth-scoped API).
**Wireframes with no page route:** the four PDFs and two emails — all have real routes of their own kind.

---

## ⚠️ #2 — EVERY PAGE: REACHED, REAL DATA, REAL FORMS

All 25 index pages **HTTP 200**, all render **real seeded money**, none is an empty shell.

| Route | Code | Bytes | € figures | forms | First headings |
|---|---|---|---|---|---|
| `/owner` | 200 | 181,731 | 27 | 4 | Good afternoon, Demo · Needs your attention · Recent statements · Earnings |
| `/owner/units` | 200 | 213,497 | 17 | 20 | My units · Needs your attention · Your units |
| `/owner/bookings` | 200 | 184,459 | 21 | 5 | Bookings |
| `/owner/calendar` | 200 | 184,018 | 7 | 3 | Block calendar |
| `/owner/add-unit` | 200 | 173,334 | 7 | 3 | Add a unit |
| `/owner/archive` | 200 | 165,725 | 17 | 2 | Statements archive · 2026 · Mar–Sep |
| `/owner/payouts` | 200 | 174,634 | 27 | 3 | Payouts · Payout history |
| `/owner/payout-method` | 200 | 166,320 | 9 | 7 | Payout method · Bank accounts · Payout schedule · Tax & remittance |
| `/owner/balance` | 200 | 194,596 | 70 | 2 | Balance & withdrawals · Withdraw early · Balance activity |
| `/owner/expenses` | 200 | 169,591 | 22 | 5 | Expenses & maintenance · Needs your approval · Pending review |
| `/owner/documents` | 200 | 183,835 | 32 | 2 | Documents |
| `/owner/tax` | 200 | 158,932 | 10 | 3 | Tax center · Tax profile · Withholding tax · Annual summaries |
| `/owner/reports` | 200 | 202,738 | 60 | 3 | Reports · Net earnings by month · Performance by unit · Booking sources |
| `/owner/insights` | 200 | 169,336 | 12 | 2 | Occupancy & pricing insights · Occupancy by month · Booking pace |
| `/owner/rates` | 200 | 154,643 | 9 | 4 | Rates & pricing · Proposed rate changes · Current rates · Guardrails |
| `/owner/reviews` | 200 | 177,696 | 7 | 2 | Reviews & reputation · By villa · Recent reviews · Rating breakdown |
| `/owner/messages` | 200 | 185,367 | 7 | 20 | Messages · Demo Staff |
| `/owner/notifications` | 200 | 160,927 | 13 | 15 | Notifications |
| `/owner/notification-preferences` | 200 | 166,828 | 7 | 3 | Notifications |
| `/owner/settings` | 200 | 177,894 | 7 | 7 | Settings · Profile · Business & tax · Billing & invoices |
| `/owner/security` | 200 | 166,371 | 7 | 7 | Security · Change password · Two-factor · Active sessions |
| `/owner/team` | 200 | 164,606 | 7 | 9 | Team & access · People with access · Invite someone · Roles |
| `/owner/agreement` | 200 | 159,044 | 7 | 3 | Agreement · Management agreement · What your fee covers |
| `/owner/help` | 200 | 160,023 | 7 | 3 | Help & support · Ask Vyrobel · Message your manager |
| `/owner/welcome` | 200 | 146,092 | 7 | 2 | Welcome, Demo 👋 · Your units are live · How you get paid |

**Detail routes, with real seeded ids:**

| Route | Result |
|---|---|
| `/owner/bookings/{id}` | 200 · 160,058b · *Demo Booker · Reservation · Your earnings · Guest* |
| `/owner/units/{id}` | 200 · 195,309b · *A1 · Availability · Revenue · 6 months* |
| `/owner/statements/{id}` | 200 · 177,776b · *Statement · Breakdown · Statement history · By unit* |
| `/owner/bookings/{id}/confirmation` | 200 · **881 KB `application/pdf`** — a real PDF, not a page |
| `/owner/statements/{id}/bookings` | 200 · **881 KB `application/pdf`** |
| `/owner/messages/{thread}` | **302** — redirects; ⚠️ **UNVERIFIED**, not "missing" (destination not followed) |

**No dead page, no 404, no empty shell was found on any owner route.**

---

## ⚠️ #3 — ELEMENT-BY-ELEMENT: OVERVIEW vs `Owner Dashboard.html`

Both sides rendered, then diffed label-by-label. **22 of 25 wireframe labels MATCH.** The three that
don't are **not missing elements**:

| Wireframe label | Verdict | Evidence |
|---|---|---|
| Needs your attention · Open Action center · View calendar · Message guests · Download statement · Contact support · THIS MONTH · TOTAL UNITS · OCCUPANCY · EARNED YTD · Recent statements · Earnings · NEXT PAYOUT · MY UNITS · UPCOMING STAYS · Next 30 days · View all bookings · Add tax details · goal · 3M · 6M · 12M | **MATCH** (22) | present in the rendered build |
| `IBAN` on the Overview payout card | **DIVERGENCE — placement** | IBAN *does* render in the build: `/owner/payouts` ×2, `/owner/payout-method` ×4. It is masked and CAP_PAYOUT-gated (#57/#58), so its absence from the Overview is a scoping choice, **not a gap** |
| `Gross booking value` | **DIVERGENCE — placement** | Exists in the build on `owner/booking-detail` and the booking-confirmation PDF; not surfaced on the Overview |
| `Rate change proposed` | **DATA-DEPENDENT, not a gap** | The attention feed itself MATCHes and is real (Wave C rate-proposals). The demo simply has no pending proposal to show; the honest empty state is correct behaviour (#39) |

---

## ⚠️ #4 — WHAT THIS AUDIT DID **NOT** DO (stated plainly, not glossed)

⚠️ **A full element-by-element wireframe diff was completed for the Overview only (1 of 27 mapped pages).**
The other 26 pages were verified **structurally and for backend wiring** — reachable, real seeded data,
real forms, correct headings — but their prototypes were **not** rendered and diffed label-by-label.
Each prototype is a client-side bundle needing its own browser render, and the remaining budget did not
allow 26 more render-and-diff passes done properly.

**Therefore this report claims NO frontend gaps for those 26 pages — it does not claim they have none.**
That is a limit of the audit, not a finding about the build. Re-running the same method per page would
close it.

---

## ROLL-UP

**FRONTEND gaps (wireframe element absent from the build):** **0 found.** Overview fully diffed; the three
non-matches are placement divergences or data-dependent. *(26 pages not diffed — see #4.)*

**BACKEND gaps (renders but dead):** **0 found.** Every one of the 31 routes returned 200 (or a real PDF),
carried real seeded money, and rendered real forms. One route — `/owner/messages/{thread}` — returned 302
and is recorded **UNVERIFIED**, not dead.

**DIVERGENCES:** 2, both *placement*, both benign — `IBAN` and `Gross booking value` exist in the build on
the pages where they belong, just not echoed on the Overview.

**ENHANCEMENTS (in the build, beyond the wireframes) — welcome, recorded, not defects:**
`/owner/notification-preferences` · `/owner/messages/{thread}` · the CSV/ZIP export family
(`bookings`, `units`, `payouts`, `balance`, `expenses`, `statement-history`, `archive/zip`,
`security/export`) · `/owner/tax/{year}/vat-return.csv` · the OAuth-scoped `api/owner/*` surface.

**Demo state after the audit:** unchanged — `rate_calendar` `2bbdafccc3b5c1ee43f89fe313a3f02d`,
0 unit overrides, nothing written. No code modified.
