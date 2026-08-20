# Guest notifications — SCOPE + PROPOSE (read-only; nothing built)

**Pass date: 2026-08-20.** Read-only. No code, no migration, no route, no notification type, no dispatch,
no bell wiring. Money as seeded — no writes of any kind. The deliverable is this proposal; the build is a
separate pass, after the founder rules on the types.

> ⚠️ **Filename note.** The prompt named this file `…-2026-08-18.md` and it is written to that path as
> instructed, but the pass actually ran on **2026-08-20**. Worth renaming so the audit trail stays honest.

---

## ⚠️ 0 — THREE CORRECTIONS TO THE BRIEF'S PREMISE (confirm-first, cutting both ways)

The brief was right that the feature is unbuilt and right about the shape of the answer. Three of its
mechanical premises do not survive contact with the code, and one of them makes the job *smaller*.

| # | The brief said | What the code says |
|---|---|---|
| **1** | "the Notification model + the `audience` field" — implying `audience` is a column on the notification | ⚠️ **There is no `audience` column, and no `Notification` model.** Notifications are Laravel's native `DatabaseNotification` rows on the polymorphic `notifications` table. `audience` is a key inside `NotificationPreferences::CATALOG` (`app/Services/NotificationPreferences.php:41+`) and does exactly one job: decide **which portal's preferences screen lists a toggle**. It never routes a notification. |
| **2** | "audience is a SINGLE STRING — a type serves ONE portal — ADD, never flip" | **The rule holds, the reason differs.** The single-string constraint is real and is documented in `OwnerNewBookingNotification`'s own docblock — it is why the owner got `owner_new_booking` instead of reusing the operator's `new_booking`. So: still ADD, never flip. But what actually decides who receives a notification is the **`notifiable` morph**, not `audience`. |
| **3** | (implied) guests need new plumbing to receive notifications | ⚠️ **Smaller than assumed.** `Guest` already `use`s `Notifiable` (`app/Models/Guest.php:54`) and the table is `ulidMorphs('notifiable')` — and `Guest` is a ULID model. **A guest notification row needs ZERO schema change today.** The trait is present and entirely unused: nothing in `app/` calls `notify()` on a Guest. |

---

## §1 — CONFIRM-FIRST: the system, the wireframe, the owner pattern

### 1.1 ⚠️ There is NO guest notifications wireframe. None.

This is the headline for the product decision.

```
docs/design/raw/Operator Notifications.html     ← exists
docs/design/raw/Owner Notifications.html        ← exists
docs/design/raw/Guest Notifications.html        ← DOES NOT EXIST
```

All 32 guest prototypes were listed; none is a notifications feed or panel. **19 of them import
`<dc-import name="Guest Notif Bell">`** — a bell in the chrome — but the surface that bell opens was never
drawn. The nearest thing is `Guest Email Preferences.html`, which is a *preferences* screen, not a feed.

⚠️ **Consequence: for the guest realm, the notification TYPES are a pure product decision.** There is no
wireframe to read them off. That is precisely why this pass stops here rather than building — and why every
type below is anchored on a **real existing event or a real existing guest email**, never on a guess about
what a guest "would probably want".

(Precedent: the operator/owner bell panel had no wireframe either — `bell.blade.php`'s docblock records that
honestly and builds in the feed page's language. The *page* had one; the *panel* did not. For guests neither
exists.)

### 1.2 The mechanism, as it really works

- **`FeedNotification`** (`app/Notifications/FeedNotification.php`) — the base. Already **realm-agnostic**:
  `via(object $notifiable)`, `data(object $notifiable)`. It already special-cases `$notifiable instanceof User`
  for quiet hours, i.e. it anticipates non-User notifiables.
- **`NotificationPreferences::allows()`** (`:83`) — the channel gate. ⚠️ **`if (! $notifiable instanceof User)
  return $this->defaultFor($entry, $channel);`** — a Guest notifiable falls through to the **catalog default**
  and cannot hold a stored preference, because `NotificationPreference` keys on **`user_id`** (FK to `users`).
- **`NotificationFeed`** (`app/Support/Notifications/NotificationFeed.php`) — the ONE shared feed builder
  (`forUser(User)`, `recentForUser(User)`), reading strictly through `$user->notifications()`. ⚠️ **Typed to
  `User`** — the single genuine code obstacle to reuse.
- **`NotificationController`** — mark-read / read-all / dismiss. Uses **`$request->user()`**, i.e. the
  **default `web` guard**. A guest on the `guest` guard resolves to `null` here.
- **`NotificationRecipients`** — resolves *operator* audiences (org users, permission-filtered). Not relevant
  to guests: a guest notification has exactly one recipient, the guest.
- **`DispatchNotificationsForDomainEvent`** — domain event → notification, 11 events mapped.
- **`bell.blade.php`** — appearance fixed 2026-08-20; calls `NotificationFeed::recentForUser(auth()->user())`.

### 1.3 The owner pattern to mirror (§N.8f — reuse the shape)

`OwnerNewBookingNotification` is the template: extend `FeedNotification`, declare `eventKey()` (a catalog key),
`category()`, and `data()` returning `title / context / url / category`. Dispatch is a small private method on
the listener. **`OwnerMessageReceivedNotification` is the closest mirror of all** — its dispatcher reads:

```php
$ownerId = $thread->owner_id;
if (! is_string($ownerId) || $ownerId === '') {
    return;   // an operator↔guest thread — not the owner's conversation
}
```

⚠️ **The guest branch is the `else` that was never written.** The brief's claim is confirmed at source:
`message_threads` is **`guest_id` XOR `owner_id`** (the migration docblock states it in those words), so a
message to a guest is from the **property/operator**, never from an owner.

---

## §2 — THE PROPOSED TYPES (every one anchored; trigger status stated)

⚠️ **The anchor that makes this not-invented-product-content:** the app **already sends guests eight
transactional emails** on real triggers. Each type below gives an existing message an in-app surface. It does
not invent a new thing to tell a guest about.

Two live sources: `SendGuestTransactionalEmails` (4 domain events) and two scheduled commands in
`routes/console.php` that run daily and email guests.

### 2.1 Ready — trigger exists and fires today

| # | Proposed key | Trigger (exists today) | Anchor | Proposed `url` |
|---|---|---|---|---|
| **G1** | `guest_booking_confirmed` | `reservation.confirmed` → `SendGuestTransactionalEmails::bookingConfirmation()` | `BookingConfirmationMail`. Same event `owner_new_booking` fires on — addressed to the guest instead | `guest.reservation` |
| **G2** | `guest_checkin_ready` | `emails:send-pre-arrival`, `dailyAt('09:00')` | `PreArrivalMail`, whose description is *"the day the arrival window opens"* — this **is** #33, already scheduled | `guest.arrival` |
| **G3** | `guest_message_received` | `inbox.message_sent` — documented as **the OPERATOR's direction** | The unwritten `else` in `ownerMessage()`. Property→guest, per `guest_id` XOR `owner_id` | `guest.messages.show` |
| **G4** | `guest_payment_due` | `emails:send-payment-reminders`, `dailyAt('09:00')` | `PaymentReminderMail` — outstanding balance, arriving within N days | `guest.payments` |
| **G5** | `guest_booking_cancelled` | `reservation.cancelled` → `SendGuestTransactionalEmails::cancellation()` | `CancellationMail` | `guest.trips` ⚠️ *not* the reservation — see §4.2 |
| **G6** | `guest_refund_processed` | `payment.refunded` → `SendGuestTransactionalEmails::refundConfirmation()` | `RefundConfirmationMail` | `guest.payments` |
| **G7** | `guest_review_request` | `reservation.checked_out` → `SendGuestTransactionalEmails::reviewRequest()` | `ReviewRequestMail`; the guest review surface is tied to a real completed stay | `guest.review` |
| **G8** | `guest_account_security` | `GuestDeletionService` (deletion scheduled / cancelled) | `AccountDeletionScheduledMail`; GDPR 14-day erasure. Mirrors owner `account_security`, **always-on** | `guest.account` |

⚠️ **G3 nuance.** The event fires today and the owner half is wired; only the guest branch is missing. It is
the *cheapest* of the eight and arguably the most valuable — a guest currently has no signal that the property
replied. Note the bulk path (`OperatorGuestMessageMail` via `GuestBulkActionController`) is a **separate**
send that does not go through the inbox thread; whether bulk messages should also notify is a product call.

### 2.2 ⚠️ FLAGGED — NOT proposed as ready (the trigger does not exist)

Per the brief: a type whose trigger doesn't exist is a **new backend flow**, not a notification. Named, not
proposed.

| Candidate | Why it is not ready |
|---|---|
| `guest_waitlist_available` | ⚠️ **`WaitlistNotificationMail` has NO send site.** Its only reference outside `app/Mail/` is `EmailPreviewController` — it is **preview-only**. There is no availability→waitlist matcher. This is a feature, not a notification. (The guest audit already recorded the waitlist "#N in line" as an honest deferral.) |
| `guest_review_responded` | `review.responded` **exists** as an event, but no guest-facing message exists and it is unconfirmed whether a response is even surfaced to the guest. Needs a product call before it needs code. |
| `guest_checked_in` / `guest_checked_out` | Events exist, but there is no guest-facing communication for them today, and telling someone they checked in is likely noise. |
| `guest_charge_posted` | `folio.charge_posted` exists and fires often (every add-on, fee, tax line). Would be **noisy**; G4/G6 already cover the money moments a guest cares about. |
| Winback / marketing | `WinbackMail` is marketing, gated by `marketing_consent`. ⚠️ **Marketing must not enter a transactional feed** — different consent basis entirely. |

### 2.3 One stale docblock noticed in passing (not a defect)

`SendGuestTransactionalEmails`'s docblock says *"Only these three events are mapped"* and lists pre-arrival and
review among the deferred — but `HANDLED` now contains **four** events including `reservation.checked_out`
(review), and pre-arrival has a live scheduled command. The **code is correct**; the comment describes an
earlier state. Flagged only because this project's canon treats a stale comment as a real hazard. No action
taken (read-only).

---

## §3 — THE FEED / ROUTES DESIGN (self-scoped; mirroring the owner feed)

### 3.1 Storage — nothing new

Guest rows land in the existing polymorphic `notifications` table via `Guest`'s existing `Notifiable`.
**No migration.** `read_at` and `dismissed_at` already exist and the feed already filters on them.

### 3.2 The one real code obstacle — widen the shared builder, don't fork it

`NotificationFeed::forUser(User $user)` / `recentForUser(User $user)` are `User`-typed. §N.8f says reuse the
proven path, so the proposal is to **widen the two signatures** to accept `User|Guest` (both are Eloquent +
`Notifiable`; the body only ever calls `->notifications()`), **not** to write a parallel `GuestNotificationFeed`.
A second builder is exactly how two surfaces drift.

⚠️ Widening touches a file the operator and owner realms both depend on, so the build pass must re-run the
operator + owner notification suites, not just a new guest one.

### 3.3 Routes — mirroring `owner.notifications`

| Proposed | Mirrors | Note |
|---|---|---|
| `GET /guest/notifications` → `guest.notifications` | `owner.notifications` | The page the bell's "Open all" reaches |
| Bell panel in the guest shell | `<x-notifications.bell>` | Reuse the just-fixed component |
| Mark-read / read-all / dismiss | `notifications.*` | ⚠️ **See below — these are `web`-guard bound** |

⚠️ **The mark-read routes cannot simply be reused as-is.** `NotificationController` resolves the actor via
`$request->user()` — the **default `web` guard**. An authenticated guest resolves to `null` there. So the build
must either resolve the notifiable realm-aware, or add guest-guard routes that reuse the same controller logic
against the resolved guest. **This is a design decision the build pass must make deliberately, not discover.**

### 3.4 The bell's placement is an open design question

The guest shell (`resources/views/components/guest/shell.blade.php`) is a **light mobile-first header** —
brand · nav links · sign out. It has no `.vy-topbar-actions` cluster like the operator/owner shells, and no
`.vy-bell` rule. The 19 guest prototypes show a bell in the chrome, but with no guest notifications wireframe
there is no drawn answer for where it sits at mobile width. Flagged for the founder / a design pass.

### 3.5 Preferences — an honest gap, not a blocker

⚠️ `NotificationPreference` keys on **`user_id`**; a Guest has no row there. So guest notifications would run on
**catalog defaults with no per-guest toggle** unless preferences are extended. Three options, founder's call:

1. **Ship on defaults first** (smallest, honest — a guest gets the eight transactional signals, no toggles).
2. **Extend `NotificationPreference`** to a polymorphic owner (touches a table both live realms use — the
   riskiest, and it would need its own isolation proof).
3. **Reuse the guest's own preference surface** — `guest.account.preferences` already exists but today writes
   only `marketing_consent` and `language`. `Guest Email Preferences.html` is a drawn wireframe that may
   already answer this.

⚠️ Note `NotificationPreferenceController` hard-asserts `in_array($audience, ['operator','owner'])` — adding a
guest audience there is an explicit, deliberate edit, not an accident.

---

## §4 — THE GUARDS (to be stated and proven in the build pass)

### 4.1 #28 — self-scoped, no cross-guest leak
Reads go **only** through `$guest->notifications()`, exactly as the operator/owner feed goes only through
`$user->notifications()`. A guest can never name another guest's row. This mirrors the self-scoping the guest
audit already proved for reservations, receipts and threads (`GuestMessagesController` resolves threads by
`where('guest_id', $this->guest()->getKey())`, never by route-model binding).
⚠️ Payloads stay **the guest's own data only** — a guest notification must never carry another guest's name.

### 4.2 ⚠️ The `url` must be openable by that guest — no dead affordance
A notification is a pointer; pointing it at a page the guest 403s/404s is worse than no notification.
**Concretely: G5 (cancelled) must NOT point at `guest.reservation`** — a cancelled booking may no longer
resolve for the guest, which would send them to a dead link at the worst possible moment. It points at
`guest.trips`. Every proposed `url` above is a **real named guest route**, verified present in `route:list`.

### 4.3 audience — ADD, never flip
Any new catalog entries are **new keys** (`guest_*`). The 16 operator keys and 9 owner keys are untouched.
The `OwnerNewBookingNotification` docblock records what flipping costs: it would strand every user relying on
the existing key.

### 4.4 A guest "message" is from the PROPERTY, not an owner
`guest_id` XOR `owner_id`. G3 fires on the operator direction (`inbox.message_sent`) for threads **with a
`guest_id`**. Owner↔guest messaging does not exist and this must not accidentally create it.

### 4.5 Don't break the two live realms
Widening `NotificationFeed` (§3.2) and touching `NotificationPreferenceController` (§3.5) both reach code the
operator and owner realms depend on. Both are sealed. The build pass re-proves them.

---

## §5 — OPEN PRODUCT QUESTIONS (the founder's ruling)

1. ⚠️ **Which of the eight?** G1–G8 all have live triggers. All eight, or a subset? My read: **G1, G2, G3, G4
   are the core** (booking confirmed · check-in ready · property replied · balance due) — the four a guest
   would actually miss. G5–G8 are lower value in-app because the email already lands at exactly that moment.
2. ⚠️ **Is in-app duplication of an email wanted at all?** Every one of these already emails the guest. The
   value of the feed is a *durable* record the guest can return to; the cost is telling them twice. This is the
   central product question, and it is yours.
3. ⚠️ **Preferences: which of the three options in §3.5?** (Defaults-only is the smallest and most honest.)
4. **Bell placement in the light guest shell** (§3.4) — no wireframe exists.
5. **Should the bulk operator message (`OperatorGuestMessageMail`) also notify**, or only real inbox threads?
6. **`guest_review_responded`** — is a property's response to a guest review surfaced to that guest at all?
   Product question before it is a code question.
7. **Copy.** With no wireframe, every title/context string is an authoring decision. Proposal: derive them from
   the **existing Mailable subject lines**, so the in-app row and the email say the same thing and cannot drift.
8. **Do you want the flagged §2.2 items built?** Each is a new backend flow, not a notification —
   `guest_waitlist_available` in particular is a whole availability-matching feature.

---

## Status

| | |
|---|---|
| **Built** | ⚠️ **Nothing.** No migration, type, route, dispatch, bell wiring, or preference change. |
| **Written** | This report only. |
| **Repo state** | Read-only. No writes, no seeds, money as seeded. Bell appearance fix + owner/operator types + all three sealed realms untouched. |
| **Guest seal** | Not re-opened. Guest notifications are **net-new scope**, absent from the audited surface — not a gap in it. |
| **Headline** | ⚠️ **No guest notifications wireframe exists** → the types are a pure product decision. ⚠️ But **eight real guest-addressed emails with live triggers** already exist, so the feature can be grounded entirely in what the app already tells guests. ⚠️ And `Guest` **already** has `Notifiable` on a polymorphic table → **no schema change**. |
| **Next** | ⛔ The founder rules on §5. The build is a separate pass. |
