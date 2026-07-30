# Operator Messages + New Reservation + Notification Preferences — exact-match audit (#90, PHASE 1, read-only)

**Pages:** `/app/inbox` (`InboxList` + `InboxThread`) · `/app/reservations/create` (`NewReservation`) · `/app/notification-preferences` (`OperatorNotificationPreferences`) — all exist; being matched to their wireframes EXACTLY on REAL data, building what's missing.
**Wireframes:** `Operator Messages.html` · `Operator New Reservation.html` · `Operator Notification Preferences.html` (dc-runtime bundles — decoded; the readable copy + data arrays are in `scratchpad/wf-inventory.md`).
**Date:** 2026-07-31 · **Status:** PHASE 1 complete; awaiting the relay/reservation/Push decisions + the extras-tax / credit / low-rating / draft founder decisions.

**All three OVERLAP existing work — REUSE/EXTEND, never rebuild** (the green suite is the proof; disturb nothing — esp. #77 owner Messages, #84 Calendar create, #79 owner Notifications sharing these engines).

---

## 1 · MESSAGES — per-element (`Operator Messages.html`)

**Reuse:** the REAL unified inbox — `MessageThread` + `Message` (one pair of models for owner↔operator, operator↔guest, guest↔portal, discriminated by `guest_id` XOR `owner_id`), single write path `InboxService`, operator surfaces `InboxList` (`app.inbox`) + `InboxThread` (`app.inbox.show`), writes via `InboxActionController` (`reply`/`assign`/`close`/`reopen`/`snooze`/`link-reservation`). **operator↔GUEST carries FULL guest data** (`guest_id`+`reservation_id`+encrypted `participant_identity`; `InboxThread::guestCard()`/`reservationCard()`) — DISTINCT from owner↔operator name-only (#77). The owner `OwnerMessages` is the **3-pane exact-match reference** to mirror with the operator design system.

| Element | Verdict | Backend / what to extend |
|---|---|---|
| Conversation list (guest name/time/preview/channel/context) | ✅ REAL-NOW | `InboxList::render()` maps name/shortTime/90-char preview/channel/unread/reservationTag |
| Search (within list) | ✅ REAL-NOW (client) | per-row lowercase search blob + Alpine filter; a server guest-search for the composer is a thin BUILD (reuse `GuestsList`/`OperatorSearch`) |
| Thread + reply | ✅ REAL-NOW | `InboxThread` transcript + `InboxActionController::reply` → `InboxService::sendOutbound()` (deferred channels leave an honest `failed`, never a fake send #24) |
| **Assign conversation** (hand to staff, internal, "guests never see this") | ✅ REAL / 🔨 notify | assign REAL (`InboxService::assign()`, `assigned_to`, audited; picker `orgStaff()` or richer `StaffRoster` for role/department labels). "Internal / guests never see" is inherent (a thread column, never sent). **BUILD:** the in-app notify to the assignee (no listener on `inbox.thread_assigned` today) |
| **New message** → pick a guest (current/upcoming stay) | 🔨 BUILD (backend exists) | no compose-in-inbox UI; backend present — `InboxService::startThread(['channel'=>'manual','guest_id'=>…])` + guest/reservation enumeration (cf. `GuestActionController::startThread`) |
| **Start from template** (pre-arrival / payment / review) | 🔨 BUILD (data exists, not wired) | `EmailTemplateCatalog` (keys `pre-arrival`/`payment-reminder`/`review-request`) + `EmailTemplateResolver` + `EmailPreviewSample` exist but are NOT wired into the composer — BUILD a bridge: resolve for org, merge guest/reservation vars, pre-fill the body |
| Suggested replies | 🚫 NOT-BUILDABLE | no inbox reply-suggestion engine anywhere → **honest** (omit, or a small static canned set clearly not AI). #39 |
| Read-only respected | 🔨 BUILD | ⚠️ GAP — `InboxActionController` does NOT call `OperatorWorkspace::isReadOnly()` (the guest-thread START paths do). Add the guard for consistency |

---

## 2 · NEW RESERVATION — per-element (`Operator New Reservation.html`, the FULL wizard extending #84)

**Reuse:** the certified `AssignmentService::createBooking()` (the SAME engine as #84 — Level-A count + Level-B `FOR UPDATE` non-overlap, throws `InsufficientAvailabilityException`/`UnitUnavailableException`), the existing `NewReservation` (a 3-step single-page form) + `ReservationCreateController::store()` + blade — **EXTEND to the 7-step wizard**. Availability read = `UnitAvailabilityService::availableRoomTypes()`. Folio = `FolioService` (append-only, `recompute()` derives `total_minor = SUM(folio_items.amount_minor)` — #17 reconciles by construction).

| Step | Verdict | Backend / what to extend |
|---|---|---|
| 1 · When & unit | ✅ REAL-NOW | `UnitAvailabilityService::availableRoomTypes()` (priced+free units only); write via `createBooking()` |
| 2 · Who's staying (existing/new, VIP, consent) | ✅ REAL / 🔨 UI | `NewReservation::searchGuests()` + `ReservationCreateController::resolveGuest()`; `Guest.is_vip`/`marketing_consent`(#86)/`preferences` columns EXIST — wire the UI |
| 3 · Rate & extras | ✅ services / 🔨 wire | room rate REAL (`priceOverStay()`); extras = `AddOn` + `FolioService::postAddOn`, taxes = `Tax` + `applyTax` — **REAL but only wired POST-creation** (`ReservationFolioActionController::addCharge`); extend the CREATE flow to post add-on + tax lines beside the room charge |
| 4 · Arrival & transfer | 🔨 BUILD | transfer = an `AddOn` folio line (real, reconciles); arrival window → a reservation note or `Guest` preference; "make own way → door code in confirmation" = the #33 pre-arrival (`ArrivalPack`, guest-only) |
| 5 · House rules | ✅ partial / 🔨 display | `Property.check_in_time`/`check_out_time` + `PropertyPolicy.cancellation`/`min_stay`/`max_stay`/`deposit` REAL; quiet-hours + max-occupancy have no Property column (occupancy at RoomType/Unit) — derive/display honestly |
| 6 · Review & confirm | ✅ REAL-NOW | derived folio totals (`recompute()`); `BookingConfirmationMail` fires on `reservation.confirmed` |
| 7 · Payment | ✅ manual / 🔨 card honest | manual cash/bank REAL (`FolioService::recordPayment()`, `payments.record`); **card = honest "Soon"** (`StripeSafetyGuard` test-mode; hosted-checkout seam `recordCheckoutIntent()` exists but unwired; **no PAN field** — SAQ A). No real charge until go-live (#45) |
| Returning-guest credit | ✅ REAL | `GuestWalletService::applyToBooking()` + `GuestCreditEntry` + `FolioService::applyWalletCredit()` (append-only, overdraw-guarded) — apply the guest's REAL spendable balance, NOT a hardcoded €50 |
| Draft-restore | 🚫 no server / ✅ client | no drafts table → but the wireframe itself uses **`localStorage`** ('vyrobel:new-reservation:draft') — a client-side draft MATCHES the wireframe and is honest (no server-persistence claim) |

**Reconcile (#17):** accommodation (room) + extras (add_on) + transfer (add_on) + city-tax/VAT all post as `folio_items`; `recompute()` derives the total from the append-only ledger → the summary reconciles by construction. **Must wire extras/transfer/tax into the CREATE controller** (today only the `room` line posts at creation).

---

## 3 · NOTIFICATION PREFERENCES — per-element (`Operator Notification Preferences.html`, the operator side of #79)

**Route reality:** `/app/notification-preferences` (name `app.notification-preferences[.update]`) — NOT `/app/settings/notifications`. **Reuse verbatim:** the shared `NotificationPreferences` engine (per-USER rows `(user_id,event_key,channel)`, channels `in_app`+`email`), `NotificationPreferenceController@update` (audience `operator`), `QuietHours`. Email genuinely gates delivery via `FeedNotification::via()` (mutation-proven). The owner page is the honest-SMS 3-column reference.

| Element | Verdict | Backend |
|---|---|---|
| Channel card — Email (status) | ✅ REAL-NOW | Email gates delivery (`FeedNotification::via()`); show the auth user's email |
| Channel card — **Push** (status) | 🚫 HONEST-GATED | **Push is definitively NOT real** — no VAPID/web-push/service-worker/device-token/FCM anywhere. Display-only/disabled, never a submitted field (#39) |
| Channel card — SMS (status) | 🚫 HONEST-GATED | no provider (owner precedent) — display-only/disabled, never a fake send |
| Notify-me grid — **Email** column | ✅ REAL (8) / 🔨 (3) / 🚫 (1) | 8 events wired+firing; 3 pref-real/delivery-deferred (`arrivals_today`, `housekeeping_behind`, `new_device_login`); 1 has NO engine key (`Low rating <4★`) |
| Notify-me grid — Push / SMS columns | 🚫 HONEST-GATED | no engine channel — never submit `prefs[…][push]`/`[sms]`; preserve real `in_app` via hidden inputs (owner precedent) |
| Quiet hours (+ urgent bypass) | ✅ REAL-NOW | `QuietHours` + `users.quiet_hours_start/end`, holds EMAIL only (in-app unaffected); `always_on` bypass exists but NO operator event is always_on (owner-only `account_security`) |
| Daily digest (Off/08:00/18:00) | 🔨 honest | `daily_summary` pref stores; **no real digest job** → honest (store the pref, no fabricated send) |

**Event map (wireframe → real key):** New booking→`new_booking`✅ · Arrivals due today→`arrivals_today`🔨 · Balance due→`balance_due`✅ · Housekeeping behind→`housekeeping_behind`🔨 · Urgent work order→`urgent_work_order`✅ · Unit out of order→`unit_out_of_order`✅ · Owner payout sent→`owner_payout_sent`✅ · Statement ready→`statement_finalized`✅ · New review→`new_review`✅ · **Low rating <4★→(none)🚫** · Channel sync error→`channel_sync_error`✅ · Login from new device→`new_device_login`🔨.

---

## 4 · The three KEY decisions (present inline)

1. **operator↔guest relay** → **REUSE the same `MessageThread`/`Message`/`InboxService`; the operator side carries FULL guest data** (already does via `InboxThread::guestCard`), distinct from owner↔operator name-only #77. Recommend collapsing operator list+thread into the **3-pane exact-match** (mirror `OwnerMessages` with the operator design system). *No new isolation mechanism.*
2. **full-reservation-extends-#84** → **EXTEND `NewReservation`/`ReservationCreateController` to the 7-step wizard on the SAME `AssignmentService::createBooking`** (non-overlap mutation-proven — the SAME as #84); wire extras/transfer/tax/credit into the create flow (all real services); payment test-mode (manual real, card honest-Soon, no PAN); draft = client-side localStorage (matches the wireframe).
3. **Push** → **HONEST-GATED** (definitive — zero infra; identical to SMS). Not a preference — reality forces it.

## 5 · Founder sub-decisions (recommendations stated)

- **A · Extras + taxes: real `AddOn`/`Tax` records vs hardcoded wireframe values.** The wireframe hardcodes €18 breakfast / €65 transfer / €40 late / €15 cot / €2.50 city tax / VAT 10%. **Recommend:** wire to REAL `AddOn` + `Tax` records (post real folio lines that reconcile #17); seed clearly-flagged demo add-ons/taxes so the demo shows the wireframe shape; where a property has none configured, **honest-omit** that extra rather than fabricate a price. *Alt: keep the wireframe constants as honest fixed defaults.*
- **B · Returning-guest credit: real wallet vs honest.** **Recommend REAL** — apply the guest's real `GuestWalletService::spendableMinor()` (0 → the row hides), not a fabricated €50.
- **C · "Low rating <4★" notification event.** No engine key exists. **Recommend:** honest-omit (render the 11 real events; document the one refusal) — *Alt: build a real `low_rating` catalog key + a dispatcher branch on `review.submitted` rating<4 (a genuine small build).*
- **D · Draft-restore.** **Recommend client-side localStorage** (matches the wireframe exactly; honest — no server-persistence claim). *Alt: a real `reservation_drafts` table (heavier).*
- **E · Assign-to-staff notify.** **Recommend BUILD the real in-app notification** to the assignee (a listener on `inbox.thread_assigned` → a `FeedNotification`), so "notified on their device" is true in-app (no fabricated push).
- **F · Suggested replies.** **Recommend honest** — omit (no reply-suggestion engine); do NOT fake AI. *Alt: a small static canned-phrase set, clearly not AI.*

## 5b · Decisions CONFIRMED (founder, 2026-07-31)
- **A → Real `AddOn`/`Tax` + honest-omit** (real reconciling folio lines; flagged demo seed; omit where unconfigured).
- **B → Real wallet credit** (`GuestWalletService::spendableMinor()`; hides at 0).
- **C → Build the real `low_rating` key** (catalog key + `review.submitted` rating<4 dispatcher branch → a real notification; the grid matches the wireframe honestly).
- **D → Client-side localStorage draft** (matches the wireframe; no server-persistence claim).
- **E → Build the real in-app assign-notify** (listener on `inbox.thread_assigned` → `FeedNotification`; "notified" true in-app, no fake push).
- **F → Suggested replies honest-omit** (no engine — never fake AI).

## 6 · Guards (all gates)
🔒 operator-scoped (Messages = FULL guest data — the manager, distinct from owner name-only #77); the certified `AssignmentService` reused (non-overlap **mutation-proven** — the SAME as #84); #17 the folio reconciles (accommodation+extras+transfer+tax=total, derived from the append-only ledger); payment **test-mode** (no real charge, no PAN — #45); the #79 prefs engine reused (Email gates delivery, **mutation-proven**); #33 door code guest-only in the confirmation; #20 reply/assign/create controller-routed + audited; #39 no fabrication (no fake assignment/charge/stored-card/SMS/push/suggested-reply/low-rating). Read-only (`OperatorWorkspace`) respected on every write (add the missing inbox guard). Anti-drift — reuse `InboxService`/`MessageThread` (#77) · `AssignmentService`/`FolioService`/`GuestWalletService` (#84) · `NotificationPreferences`/`QuietHours` (#79) · `EmailTemplateCatalog` (#83) · `StaffRoster` · `StripeSafetyGuard`.

## 7 · Build plan
**GATE 1 — Messages:** the 3-pane exact-match operator inbox (list+thread+compose) on `InboxService`; assign-to-staff (real + the new in-app notify); new-message + start-from-template (bridge `EmailTemplateCatalog`); suggested-replies honest; add the read-only guard → tests (reply/assign audited; template pre-fill; read-only refuses) → browser → commit.
**GATE 2 — New Reservation:** extend to the 7-step wizard on `createBooking` (non-overlap mutation-proven); wire extras/transfer/tax/credit into the create flow (reconciling #17); house-rules display; payment test-mode (manual real, card honest); localStorage draft → tests (full reservation via the certified engine; the folio reconciles; non-overlap throws) → browser → commit.
**GATE 3 — Notification Preferences:** port the owner honest-3-column UI onto the operator catalog (Email real + honored; Push+SMS honest-gated; quiet-hours real; digest honest); in_app preserved via hidden inputs → tests (Email pref gates delivery mutation-proven; Push/SMS never submitted) → CONSOLIDATE docs → browser → commit + PUSH.

## RESOLUTION
_(appended at GATE close)_
