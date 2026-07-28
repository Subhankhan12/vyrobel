# Owner Team & access + Unit Detail — wireframe-EXACT-match audit

**Date:** 2026-07-28 · **Author:** build session · **Status:** ✅ **BUILT — invariant #65** (GATE 1 Team `e5d1a1f` · GATE 2 Unit Detail). Both pages match their wireframes on REAL owner-scoped data, honest where not modelled. Team: real matrix (from `OwnerTeamRole::CAPABILITIES`) + truthful activity + a NEW real resend + honest security note (no watermark claim). Unit Detail: the Wave-1 STALE refusals are now REAL — tap-to-block (`OwnerBlockService`, mutation-proven in the browser), nightly-rate + min-stay edit (`OwnerRateService`, browser-proven €180→€185 + returns to the unit), reviews (`ReviewRating`/`ReviewList`), and the name-free owner↔operator relay; weekend-uplift / cleaning / OTA-sync / snooze / per-unit-payout / "Direct saved you" stay honest. Suite green; PHPStan L5 (0); Pint clean; browser-verified. *(The PHASE-1 analysis below is preserved as the audit of record.)*
**Pages:** `/owner/team` (`OwnerTeam` + `team.blade.php`) · `/owner/units/{unit}` (`OwnerUnitDetail` + `unit-detail.blade.php`).
**Wireframes:** `docs/design/raw/Owner Team.html` · `docs/design/raw/Owner Unit Detail.html` (decoded → `/tmp/owner_team_decoded.txt`, `/tmp/owner_unitdetail_decoded.txt`).

---

## 0. Headline

- **TEAM is almost entirely REAL** — the live page already wires the owner-team feature (members, role change, remove, invite, pending, revoke, the role legend). The delta is **presentation** + three additions: the **role matrix** (buildable REAL from the actual capability map), an honest **recent-activity** treatment, and a **security note** whose "watermarked exports" claim must be **softened** (not implemented).
- **UNIT DETAIL is a large aggregation.** The live page already has ~11 REAL sections. Its Wave-1 docblock omitted many sections "because no backend" — but several of those refusals are **STALE**: `OwnerBlockService` (blocking), `OwnerRateService` (rate + min-stay), the reviews feature, and the owner↔operator message relay were all built *after*. Re-verified: **day-blocking, nightly-rate + minimum-stay edit, reviews, and a name-free message relay are now buildable REAL.** Weekend-uplift, cleaning, OTA-sync, per-unit payout timeline, and the snooze toggle stay **honest** (genuinely not modelled).

---

# PAGE A — TEAM & ACCESS (`/owner/team`)

## A1. Section classification + reuse map

| # | Wireframe section | Verdict | Backend (REUSE) |
|---|---|---|---|
| 1 | Shared chrome ("Team & access" active) | ✅ REAL | `<x-owner.shell active="team">` |
| 2 | Header + subtitle | ✅ REAL | match the fuller subtitle. |
| 3 | **People with access** (count + rows: initials/name/role/"that's you"/scope/last-active/Remove) | ✅ REAL — REUSE | `OwnerTeam` reads `OwnerUser` (members) + the primary owner; role change = PATCH `owner.team.members.role`, remove = DELETE `owner.team.members.remove` (both `EnsurePrimaryOwner`-gated, #20). Last-active is TRUTHFUL (real `last_login_at`, never fabricated). |
| 4 | **Invite someone** (role picker + Send) | ✅ REAL — REUSE | POST `owner.team.invite` (throttled, primary-owner-gated) → `OwnerTeamInviteService` (a real `OwnerTeamInvite` + `OwnerTeamInviteMail`). |
| 5 | **Pending invites** (email/role/sent + Resend/Revoke) | 🟡 mostly REAL | **Revoke** = REAL (POST `owner.team.invites.revoke`). **Resend** — ⚠️ NO route exists, and the wireframe's Resend is a **no-op toast** (no click handler). → see §A2. |
| 6 | **What each role can see** (matrix: area × Co-owner/Accountant/View-only) | ✅ **REAL-BUILDABLE** | derive verbatim from `OwnerTeamRole::CAPABILITIES` — the REAL capability grid the route gates (`EnsureOwnerCapability`) + the UI (`@ownercan`) share. 6 rows (Earnings/Approvals/Documents/Calendar/Exports/Payout) × 3 roles. **Never a fabricated matrix** — it reflects exactly what each role grants. |
| 7 | **Recent activity** (who/what/when) | 🔴 **HONEST** (no event log) | team actions are **NOT audited** (no record calls in `OwnerTeamController`/`OwnerTeamInviteService`) → no real activity feed exists. The real signals are per-member **last-active** (already shown) + invite timestamps. → see §A2. |
| 8 | **Security note** ("no shared passwords · exports **watermarked** with who downloaded · turn on 2-step") + Security link | ⚠️ **SOFTEN** | ⚠️ **the watermark claim is FALSE** (grep-confirmed: no export is watermarked, and downloads are NOT audit-logged either). → §A2. The 2-step + separate-sign-in + Security link (`owner.security`) are REAL. |

## A2. ⚠️ Team investigate resolutions

1. **The "watermarked exports" claim → SOFTEN (never claim a watermark that isn't applied).** Exports carry no downloader watermark and downloads aren't audit-logged. The honest, TRUE claim: *"No shared passwords — each person signs in with their own account, and access is scoped to their role. For your own account, turn on 2-step verification."* (Drop "watermarked" **and** "audit-logged who downloaded".) #39.
2. **Recent activity → HONEST.** No audited team-event log exists. **Recommend: omit the fabricated event feed** — the per-member "Active today / Last active X / Hasn't signed in yet" (already real) conveys activity truthfully; a rich who-did-what-when feed would be fabricated. *(Alternative: a minimal REAL "activity" derived from real last-sign-ins + invite timestamps — thinner but truthful. Recommend omit for honesty, or the minimal-real if the founder prefers the section filled.)* **← light founder call.**
3. **Resend → recommend a small REAL resend (or omit).** No resend route exists and the proto's Resend does nothing. Since `OwnerTeamInviteService` already builds + mails invites, a real **resend** (re-dispatch the mail + extend `expires_at`, primary-owner-gated, throttled) is a small faithful addition. *(Alternative: honest-omit Resend, keep Revoke.)* **← light founder call.** Recommend: add the real resend so the design's button works.

## A3. Team build plan — **⛔ GATE 1**
Match the page exactly (people list, invite, pending, **role matrix**, recent-activity treatment, security note — every token/animation) on the shared chrome. REUSE the owner-team feature (members/roles/invite/remove/revoke — all real, gated, controller POSTs #20). Build the **role matrix REAL** from `OwnerTeamRole::CAPABILITIES`. Security note **softened** (true claim, no watermark). Recent activity **honest** (omit or minimal-real). Resend **real** (or omit) per the founder call. Tests: the matrix matches the real caps; the security note makes no watermark claim; (if added) resend re-dispatches + is primary-owner-gated. Browser-verify invite/remove/revoke + the matrix. Per-gate commit.

---

# PAGE B — UNIT DETAIL (`/owner/units/{unit}`)

The live page already renders ~11 REAL sections (Wave 1 + Batch B). Its docblock omitted several sections "no backend" — **re-verified against the now-existing services below.**

## B1. Section classification + reuse map (15 sections)

| # | Wireframe section | Verdict | Backend / resolution |
|---|---|---|---|
| 1 | Header — name + **Active/Snoozed** + Edit-pricing + sync/share | 🟡 REAL + HONEST | name/status REAL (`is_active` → "Active"); **Snooze toggle** = an `is_active` write with no owner path → **honest read-only status** (no toggle). Edit-pricing → the real rate editor (§4). **sync/share** → §B2. |
| 2 | KPIs — Occupancy (+Δ) / Nights sold / Revenue | ✅ REAL (built) | `OwnerOccupancy` (unit-scoped) + `OwnerLedgerAttribution` net. The "+Δ" vs last period → honest-omit unless real (no prior-period delta stored). |
| 3 | **Availability calendar** (booked/open/**blocked**, tap-to-block) | ✅ **REAL-BUILDABLE (stale refusal)** | the live calendar is read-only booked/no-booking. **`OwnerBlockService` + `owner.calendar.block`/`release` now exist** (#60) → wire real booked/**blocked**/open + **tap-to-block** (the SAME service as Block Calendar/My Units; `$capActions` #20, non-overlap via `AssignmentService`). Guest data name-only #28. |
| 4 | **Pricing & availability** — status · Nightly rate · **Weekend uplift** · **Minimum stay** · Edit | 🟡 **REAL (nightly + min-stay) + HONEST (weekend uplift)** | **`OwnerRateService::apply($roomType, from, to, priceMinor, minStay, stopSell)` now exists** (#55) → **Nightly rate + Minimum stay = REAL** (per-room-type, exclusive-owned only, honest-disabled for a shared room type — the SAME as My Units; a real controller write #20). ⚠️ **Weekend uplift is NOT modelled** (no column anywhere) → **honest-omit** the weekend-uplift input (never a fake save that changes nothing). |
| 5 | Revenue · 6 months chart | ✅ REAL (built) | `revenueTrend()` — Σ signedMinor per month (net to you). |
| 6 | Occupancy calendar (12 months) | ✅ REAL (built) | `occupancyTrend()` — shared `OwnerOccupancy`, real avg/peak. |
| 7 | Upcoming / all bookings | ✅ REAL (built) | `bookings()` — name-only #28 (REAL refs/dates/source/status; no guest PII). Align to `OwnerBookingsReader` labels for anti-drift. |
| 8 | **Cleaning schedule** — cleaner/rating/Message/Schedule | 🔴 **HONEST** (not owner-facing) | no owner-facing cleaning model; `units.housekeeping_status` is an operator-ops signal, and the wireframe's cleaner (Ana Ruiz/SparkleCare/4.9) is fabricated. → **honest state: "Cleaning is coordinated by your management team"** (optionally a relay link to Messages) — NEVER a fake cleaner/schedule/"message sent". #39. → §B2. |
| 9 | **Reviews & rating** — score/trend + category bars + reviews | ✅ **REAL-BUILDABLE (stale refusal) + HONEST (categories)** | **`ReviewRating::forUnit` + `ReviewList::forUnits` now exist** (Phase 5) → **overall rating + count + the real review list = REAL** (published/public only; the reviewer's public display name is fine — the SAME as Owner Reviews). ⚠️ **category scores (Cleanliness/Accuracy/…) are NOT modelled** → honest-omit the category bars. Honest-empty ("No reviews yet") when none. |
| 10 | This-month summary + **rank badge** | ✅ REAL (built) | `bestBadge()` — real cross-unit ranking (only when this unit leads & owner has >1 unit). |
| 11 | Unit details (k/v) | ✅ REAL (built) | `unitFacts()` — real columns. |
| 12 | **Deductions · Sep** + Full statement | ✅ REAL (built, reconciles #17) | `OwnerLedgerAttribution::breakdown` — the same lines the statement claims. |
| 13 | **Channel mix · Sep** + "Direct saved you €X" | ✅ REAL (built) / 🚫 refuse the "saved you" | `channelMix()` — real `reservations.source`. The **"Direct saved you €X"** = a fabricated ×0.15 → **refuse** (#39). |
| 14 | **Payout timeline** (cadence + rows) | 🔴 **HONEST** (per-statement, not per-unit) | payouts are per-STATEMENT/per-OWNER — a per-unit payout timeline is a fabricated attribution. → the real **Payouts** link (as today); real states only, masked IBAN #28, no invented "scheduled" date (the payout ENGINE is deferred). |
| 15 | **Guest messages** — threads + reply | ✅ **REAL-BUILDABLE (stale refusal), reframed HONEST** | **the owner↔operator relay `owner.messages.start` now exists** (#55) — but guest **threads + owner→guest reply would be guest PII / owner→guest, forbidden (#28)**. → an **honest owner↔operator relay** entry (name-free, "message your management team about this unit") or a link to the real owner Messages — NEVER guest threads. |

## B2. ⚠️ Unit Detail investigate resolutions

- **Weekend uplift → HONEST-OMIT** (no column; nightly + min-stay only). Never a fake save.
- **Minimum stay → REAL** (`OwnerRateService::apply` `minStay`). Nightly rate → REAL (same). Both per-room-type, exclusive-owned, honest-disabled for a shared room type.
- **Day-blocking → REAL** (`OwnerBlockService` via `owner.calendar.block`; `$capActions` #20; non-overlap enforced). *(Note: the block controller redirects to the Calendar page on success — the block is real and visible there; acceptable reuse.)*
- **Cleaning schedule → HONEST** ("coordinated by your management team" note ± a Messages relay link) — never a fake cleaner/schedule.
- **Sync/share → HONEST/REAL split.** OTA **sync** is account-gated (Channex) → honest "not connected / managed by your team"; **Share** = a real public storefront listing link IF one exists for the unit's property, else honest-omit.
- **Snooze toggle → HONEST read-only status** (no owner `is_active` write path).
- **Per-unit payout timeline → HONEST** (link the real Payouts; payouts aren't per-unit).
- **"Direct saved you €X" → REFUSE** (fabricated).

## B3. Unit Detail build plan — **⛔ GATE 2**
Rebuild `unit-detail.blade.php` to the wireframe (page-scoped `.vy-ud-*` + shared chrome; all 15 sections, every token/animation). Wire each section to its REAL backend (REUSE): KPIs/occupancy/revenue/bookings/attribution/channel-mix/best-badge/unit-facts (already built — restyle to the wireframe); **availability calendar + tap-to-block** (`OwnerBlockService`); **nightly-rate + min-stay edit** (`OwnerRateService`, exclusive-owned, honest-disabled); **reviews** (`ReviewRating`/`ReviewList`, honest-empty, no category bars); **guest-messages → owner↔operator relay** (name-free #28). HONEST: weekend-uplift omitted, cleaning "managed by your team", OTA-sync honest, snooze read-only, per-unit payout timeline → Payouts link, "Direct saved you" refused. Guards: #28 name-only + masked IBAN · #17 deductions/attribution reconcile · #20 controller writes (block/rate) + audited · #42 exports-gated · #39 nothing fabricated. Tests: block a real date (mutation-proven, the SAME service); edit the real rate (nightly + min-stay persist; shared room type honest-disabled); reviews real-or-empty; no fabricated cleaner/weekend-uplift/OTA-sync. Browser-verify (block a date + edit a rate + all sections render). Consolidate docs. Per-gate commit + push.

---

## Guards (both gates)
🔒 #28 owner-scoped + guest **name-only** (+ masked IBAN CAP_PAYOUT-only) · #17 reconcile (deductions/attribution/channel-mix) · #20 controller-route writes + audited (team, block, rate) · #42 exports CAP_EXPORTS-gated · #39 **nothing fabricated** (no watermark claim, no fake cleaner/schedule, no fake weekend-uplift save, no OTA-sync, no scheduled payout, no "Direct saved you"). REUSE the owner-team feature + `OwnerBlockService` + `OwnerRateService` + `OwnerBookingsReader` + `ReviewRating`/`ReviewList` + the message relay (anti-drift). Additive-only where a small write is added (resend) — DISTURB NOTHING existing (the green suite is the proof).
