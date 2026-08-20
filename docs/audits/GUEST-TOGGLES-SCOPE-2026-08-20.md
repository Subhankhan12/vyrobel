# Guest preference toggles — SCOPE + PROPOSE (read-only; nothing built)

**2026-08-20.** Read-only. No code, no migration, no `allows()` change, no controller, no UI, no store. Money
as seeded (`unit_rate_overrides` = 0 — note `rate_calendar.override_reason` does not exist). Baseline
`origin/main` `2ed497a`.

---

## ⚠️ 0 — THE HEADLINE: THE WIREFRAME IS A DIFFERENT FEATURE FROM THE ONE WE SHIPPED

`Guest Email Preferences.html` decoded. Its component state is:

```js
state = { prearrival, payment, offers, reviews, winback,   // six EMAIL categories
          push,                                            // ONE global push switch
          quiet, quietStart:'22:00', quietEnd:'08:00', quietExcept,
          unsubOpen, unsubDone, resubOpen, resubDone }      // unsubscribe / resubscribe
```

and its six rows:

| key | title | note |
|---|---|---|
| `txn` | Booking confirmations & receipts | ⚠️ **`locked: true, toggleable: false`** |
| `prearrival` | Pre-arrival & check-in | |
| `payment` | Payment reminders | |
| `offers` | Offers & news | marketing |
| `reviews` | Review requests | |
| `winback` | We-miss-you offers | marketing |

⚠️⚠️ **There is NO in-app channel anywhere in it.** Zero occurrences of "in-app". The screen governs
**EMAIL** (six grouped categories) plus **push**, **quiet hours** and **unsubscribe**.

> **The brief framed this as "toggles for the notifications we built". It is not. We shipped the IN-APP
> (database) channel; the wireframe draws the EMAIL programme. They overlap in subject matter and share
> nothing in mechanism.**

⚠️ **And the prototype already made the mandatory-transactional call itself** — `txn` is `locked` in the
design, not just in our reasoning.

---

## §1 — CONFIRM-FIRST: the preference system as it really is

| Fact | Source |
|---|---|
| `notification_preferences` is `(user_id FK→users, event_key, channel enum, enabled)`, unique on the triple, storing **only deviations** from default | `2026_07_20_160000_create_notification_preferences_table.php:21-27` |
| A hard `foreignUlid('user_id')->constrained('users')` — **a Guest cannot hold a row without a schema change** | same |
| `CHANNELS = ['in_app', 'email']` — ⚠️ **there is no `push` channel, and no push provider anywhere in `app/`** | `NotificationPreferences.php:29` |
| The fall-through a guest toggle would change: `if (! $notifiable instanceof User) return $this->defaultFor($entry, $channel);` | `allows()` |
| `always_on` short-circuits **before** the fall-through — so G8 is unreachable by any preference | `allows()` |
| The operator/owner controller hard-asserts `in_array($audience, ['operator','owner'], true)` → 404 | `NotificationPreferenceController:22` |
| The guest surface writes only `marketing_consent` + `language` today | `GuestAccountController::updatePreferences()` |
| Marketing unsubscribe already exists end-to-end | `marketing.unsubscribe` + `.confirm` routes |

### ⚠️⚠️ §1.1 THE ARCHITECTURAL FACT THAT SPLITS THIS FEATURE IN TWO

**The guest transactional emails never consult `allows()`.** `SendGuestTransactionalEmails::sendToGuest()`
calls `Mail::to($email)->send($mail)` directly; the two scheduled commands do the same. Their only gate is
`EmailTemplateResolver::enabled()` — which is the **operator's** per-org #86 switch, not a guest preference.

> **So "a guest email toggle" is NOT an `allows()` change. `allows()` governs the in-app rows only. An email
> toggle is a NEW gate at each Mailable send site. The two halves of this feature share a screen and nothing
> else.**

This is the single most important thing to settle before any build.

---

## §2 — WHAT THE TOGGLES SHOULD CONTROL

⚠️ **Grounded in the wireframe + the catalog, not assumed.** The wireframe governs email; the shipped feature
is in-app. Both are legitimate, and they are different work. Mapping the six wireframe rows to what actually
exists:

| Wireframe row | Real sender | Verdict |
|---|---|---|
| `txn` (confirmations, receipts, cancellations) | `BookingConfirmationMail` · `RefundConfirmationMail` · `CancellationMail` | ✅ real — **LOCKED** in the prototype and correct: these are transactional records |
| `prearrival` | `PreArrivalMail` (live daily command) | ✅ real — legitimately optional |
| `payment` | `PaymentReminderMail` (live daily command) | ✅ real — legitimately optional |
| `reviews` | `ReviewRequestMail` (live on `reservation.checked_out`) | ✅ real — legitimately optional |
| `offers` (Offers & news) | ⚠️ **NONE — zero mailables exist** | ⛔ a toggle over nothing |
| `winback` (We-miss-you) | ⚠️ `WinbackMail` exists but is **preview-only** — its sole reference outside `app/Mail/` is `EmailPreviewController` | ⛔ a toggle over nothing |
| `push` (global) | ⚠️ **no channel, no provider** | ⛔ a toggle over nothing (#39) |
| `quiet hours` | `QuietHours` exists but is **`User`-typed**, reading `users.quiet_hours_start/end` | needs the same widening the feed got, plus two guest columns |
| unsubscribe / resubscribe | `marketing.unsubscribe` + `marketing_consent` | ✅ **already built** — do not duplicate |

⚠️ **Three of the wireframe's controls govern capabilities that do not exist** (`offers`, `winback`, `push`).
Shipping them as live switches would be exactly the fabrication #39 forbids: a control that appears to do
something and does nothing.

### The proposal

**Scope the toggles to EMAIL, per the wireframe — and only over the three optional emails that really send:**
`prearrival`, `payment`, `reviews`. `txn` renders **locked** (as drawn). `offers`/`winback`/`push` are
**omitted or shown honestly deferred**, never as working switches.

**In-app toggles are a SEPARATE, smaller question** and the wireframe does not ask for them. My read: the
in-app feed already has **dismiss** and is self-curating, so per-type in-app suppression is low value — but
it is the one thing `allows()` can do cheaply, so it is a genuine founder call, not a foregone one.

---

## §3 — STORAGE: the recommendation

Both live mechanisms read. Restating the three options from the notifications scope report §3.5:

| | Option | Verdict |
|---|---|---|
| (a) | **Defaults-only** | the shipped baseline |
| (b) | **Extend `NotificationPreference` polymorphically** | unified across three realms — but it changes the table **both sealed realms** depend on, needs a full isolation proof (operator 16 / owner 9 byte-identical), a migration on a live FK, and it opens the `in_array` boundary. ⚠️ **And it buys nothing for the wireframe's feature**, because the emails never read that table (§1.1) |
| (c) | **A guest-specific store on `guest.account.preferences`** | isolated, matches the wireframe (a guest-account screen), reuses the surface that already writes `marketing_consent` + `language` |

### ⚠️ RECOMMENDED: (c) — the guest-specific store

The reason is not risk-aversion, it is that **(b) solves the wrong problem.** Extending
`NotificationPreference` would let `allows()` read a guest row — but `allows()` does not gate any guest
email, so the wireframe's screen would still need its own gate at the send sites. You would take the
sealed-table risk **and** still build the parallel path.

Concretely: a small `guest_email_preferences`-shaped store (or typed columns on `guests`, founder's taste),
read by ONE shared helper called at each Mailable send site — the `EmailTemplateResolver::enabled()` pattern,
which is already how those sends are gated.

**The honest trade-off:** (c) is a parallel store, so "preferences" then live in two places across the
product (users' table + guests' store). That is real duplication of *concept*. It is accepted here because
the two are read by different pipelines anyway, and because §N.8f's rule is one definition **per concept in
one pipeline** — not one table for two unrelated gates. ⚠️ If the founder later wants **in-app** guest
toggles too, revisit (b) at that point: that is the case where the unified table genuinely pays.

---

## §4 — THE ALWAYS-ON LIST + the `allows()` change

### Always-on (never toggleable)

1. ⚠️ **`guest_account_security` (G8, GDPR)** — `always_on => true`; `allows()` short-circuits before any
   preference is read, so it is unreachable by design. **Omit from the screen, or render locked.** A guest
   cannot switch off notice that their account is scheduled for erasure.
2. **`txn` — booking confirmation, refund/receipt, cancellation.** Transactional records of a contract, not
   messages. The prototype already locks this row.

Everything else optional: `prearrival`, `payment`, `reviews`.

### The `allows()` change — ⚠️ probably NONE

If the toggles are email-only (§2), **`allows()` does not change at all**, because it does not gate those
emails. The new gate lives at the send sites.

`allows()` changes **only if** the founder also wants in-app toggles. In that case the minimal change is to
replace the single fall-through line with a guest branch that reads the guest's stored preference and falls
back to the catalog when unset — **leaving the `User` path byte-identical** so operator/owner behaviour
cannot move.

---

## §5 — THE GUARDS (for the build pass to prove, not assert)

- **#28 self-scoped** — a guest reads/writes only their own preferences, resolved through `ResolvesGuest`
  from the guard, never a request id (the method the notifications gates used).
- **The `in_array` boundary stays closed.** Guest toggles live on the **guest** surface
  (`guest.account.preferences`), not by adding a `'guest'` audience to the operator/owner controller. If the
  founder prefers the unified path, opening that assertion is a **deliberate, tested edit**.
- **Operator/owner untouched** — prefs screens 16 / 9, `allows()`'s `User` path byte-identical. Under option
  (b) this needs the full feed-widening isolation proof; under (c) it is untouched by construction.
- ⚠️ **§N.29 — guard the protection RUNNING.** The guard must prove **the email does not send** when the
  toggle is off (assert the Mailable was not dispatched, mutation-proven by flipping the stored preference),
  **never** that the toggle renders, that a column exists, or that a route is declared. A rendered switch
  that suppresses nothing is precisely the label-not-protection failure §N.29 catalogues.
- **No fabricated controls (#39)** — `offers`, `winback`, `push` have no engine behind them.

---

## §6 — OPEN PRODUCT QUESTIONS

1. ⚠️ **Email, in-app, or both?** The wireframe says email. The shipped feature is in-app. My read:
   **email-only**, per the wireframe — the in-app feed already self-curates via dismiss.
2. ⚠️ **`offers`, `winback`, `push` — omit, or show as honestly deferred?** None has an engine. My read:
   **render them disabled with an honest label** rather than omit, since the wireframe draws them and a
   silently missing row reads as an oversight. Never as working switches.
3. ⚠️ **Quiet hours for guests?** The wireframe draws it (22:00–08:00 + an "except" escape). `QuietHours`
   exists but is `User`-typed and reads `users` columns. Real work: two guest columns + widening the helper.
   In or out of scope?
4. **Storage shape for (c)** — a `guest_email_preferences` table, or typed boolean columns on `guests`?
   (Sparse-row table matches the existing convention; columns are simpler for three flags.)
5. **Unsubscribe-from-all** — the wireframe draws a full unsub/resub flow. `marketing.unsubscribe` covers
   *marketing*. Should the screen's unsubscribe also stop the three optional transactional emails, or is it
   marketing-only as today?
6. **Does turning off `prearrival` risk the guest missing their door code?** #33 gates the code behind a
   window and the Arrival Info page still shows it — but the email is the push. Product call.

---

## Status

| | |
|---|---|
| **Built** | ⚠️ **Nothing.** No migration, store, `allows()` change, controller, or UI. |
| **Repo** | Read-only. No writes; money as seeded (`unit_rate_overrides` 0). Sealed realms, the notifications feature and the guest surface untouched. |
| **Headline** | ⚠️ **The wireframe governs EMAIL, not the in-app feature we shipped** — and ⚠️ **the guest emails never consult `allows()`**, so an email toggle is a new gate at the send sites, not a preference-service change. ⚠️ **Three of the six drawn controls have no engine** (`offers`, `winback`, `push`). |
| **Recommended** | Email-only, over the three optional emails that really send; a **guest-specific store** on the existing guest-account surface (option **c**), because (b) takes the sealed-table risk and still needs the parallel gate. |
| **Next** | ⛔ The founder rules on §6. The build is a separate pass. |
