# Owner Notifications — exact-match audit (PHASE 1, read-only)

**Date:** 2026-07-30 · **Page:** `/owner/notification-preferences` (`OwnerNotificationPreferences`) vs the decoded wireframe `docs/design/raw/Owner Notifications.html`.
**Method:** decoded the dc-runtime bundle (JSON.parse the `__bundler/template` script → the `<x-dc>` body + the `Component` logic) + read the real backend (`NotificationPreferences` service + store, `FeedNotification`, the owner Mailables, `QuietHours`). **Nothing changed.**

---

## 0. The shape of the gap

There is **already a real, well-built preference engine** — a typed `notification_preferences` store (per-user × per-event × per-channel), a `NotificationPreferences` service whose `allows()` **genuinely gates delivery** (called from `FeedNotification::via()` BEFORE send — mutation-proven), the exact 5 owner events the wireframe wants (`statement_ready`, `payout_sent`, `performance_digest`, `terms_changed`, and the always-on `account_security`), owner Mailables that extend `FeedNotification` (so an email-off **really stops the email**), and a real quiet-hours window. The existing owner page renders it as an **In-app + Email** grid.

The wireframe is the SAME engine restyled + two shifts: (1) the grid columns are **Email + SMS** (the two EXTERNAL channels) — the wireframe drops the In-app column, on the honest premise "**statements are always kept in your portal regardless**" (the portal feed always records); (2) a **"Where we reach you"** card (the owner's own email + phone with channel-master toggles) and a **save toast**. So this is a **presentation rebuild on the real engine**, with the **SMS column honestly gated** (no provider) and the **urgent-bypass confirmed real**.

⚠️ **The owner's OWN email/phone are shown — this is NOT a guest-#28 concern** (#28 is GUEST contact; this is the owner's own contact, which they set).

---

## 1. Section-by-section classification (REAL / HONEST-GATED / BUILD)

| # | Section (wireframe) | Real backing | Verdict |
|---|---|---|---|
| 1 | **Breadcrumb (Settings / Notifications) + header** ("Choose what we tell you and where. Statements are always kept in your portal regardless.") | The shell breadcrumb; the statements-in-portal note is TRUE (statements/documents live in the portal independent of notification prefs). | ✅ **REAL → restyle.** |
| 2 | **Where we reach you** — Email (owner email) + a master toggle · SMS (owner phone) + a master toggle | Email = the auth **User.email** (where notifications actually route); phone = the owner's **Owner.phone** (encrypted, their own — honest "no number yet" when unset, empty in the demo). | ✅ **REAL (email) + HONEST (phone/SMS).** The **Email master** is a real bulk gate over the email column (off → all email prefs save off = no emails); the **SMS master** is gated (see #3). |
| 3 | **What you hear about** — the 5 events × **Email + SMS** toggles | The 5 events ARE the real owner catalog. | **EMAIL = ✅ REAL** (stored + honored by the Mailables via `allows()`; an email-off genuinely stops that email). **SMS = ⚠️ HONEST-GATED** (no Twilio/provider — a Wave-D hard-defer): the column renders in the exact style but is clearly labelled **"not active until SMS is set up"** and never implies a send (#39). **DECISION** below: store the SMS pref (additive) vs display-only. **In-app** column dropped (the portal feed is always-on — "statements kept in your portal regardless"); the current In-app prefs are PRESERVED (never clobbered off), so the feed is unaffected. |
| 4 | **The guarantee note** — "We never send marketing without asking, and urgent account or payout alerts always come through even if a channel is off." | ✅ **ALREADY TRUE.** `account_security` (sign-ins + **bank/payout-method changes**) is `always_on`: `allows()` short-circuits to true on EVERY channel and `save()` forces it enabled — it genuinely bypasses a channel-off. There is **no marketing** event in the catalog (all operational/financial/security). | ✅ **REAL — render verbatim** (the urgent-bypass is enforced, not aspirational). No build needed. |
| 5 | **Save + toast** ("Preferences saved · Your notification preferences are live") | The real self-only POST → `NotificationPreferenceController` → `NotificationPreferences::save()`. | ✅ **REAL (#20).** The toast fires on a real save (PRG flash + a dismissible toast). |
| — | **Quiet hours** (NOT in the wireframe) | The real Wave-B GATE-6 held-email window. | 📐 **KEEP as a documented addition** — a shipped real feature; removing its only UI would disturb existing functionality. Styled to match, placed below the matrix. |

---

## 2. Reuse map (anti-drift)

- **`NotificationPreferences` service + `notification_preferences` store** — the ONE per-event × per-channel engine (`groupedFor` / `statesFor` / `save` / `allows`). Extended additively ONLY if SMS is stored (owner-scoped channel; the operator path untouched).
- **`FeedNotification::via()` + the owner Mailables** (`StatementReadyNotification`, `PayoutSentNotification`, …) — the real delivery gate; an email toggle is honored end-to-end.
- **`NotificationPreferenceController` (`owner` audience)** — the real self-only write (#20). In-app preserved via hidden inputs so the controller/service need no change (Option B) — or extended for the owner channel set (Option A).
- **`QuietHours`** — the real held-email window (kept).
- **`x-owner.shell` + `--vy-*` tokens** — the warm-glass frame, Sora/Inter, `vyFadeUp`, the drift/shimmer orbs, the toast.

---

## 3. ⚠️ DECISION (present for input)

**The SMS column — store the preference, or display-only?** SMS delivery is account-gated (no Twilio/provider — a Wave-D hard-defer), so **either way the column is clearly labelled "not active until SMS is set up" and NEVER implies a send (#39).** The question is only whether the toggle is captured now:

1. **Store the SMS preference (additive, owner-scoped)** — extend the channel enum with `sms`, persist the owner's SMS toggles (the operator flow untouched via an owner-only channel set), UI interactive but visibly gated. Pro: prefs ready the moment SMS is wired. Con: an additive schema + service change; a stored pref no delivery path reads yet.
2. **Display-only (disabled), not stored** — the SMS column renders in the exact wireframe style but disabled with the honest note; **no schema/service/controller change** (zero disturbance to the shared engine + the operator page). Con: SMS prefs are captured only when SMS is actually built.

(The Email column is REAL, the urgent-bypass is REAL, the SMS "no send" and the In-app-always-on-portal are non-negotiable — not decisions.)

---

## 4. Build plan (single gate)

**⛔ GATE 1 — the exact-design page + real Email prefs + honest SMS + the real urgent-bypass + consolidate.** Rebuild `notification-preferences.blade` to the wireframe EXACTLY (breadcrumb, header, the "Where we reach you" cards with the owner's real email/phone + master toggles, the Email+SMS event matrix, the guarantee note, the save row + the toast — every gradient/shadow/radius/spacing/animation) on the shared owner chrome + `--vy-*`. Wire the REAL parts: the owner email/phone; the REAL per-event **email** prefs (stored + honored, via the existing service/controller); the **SMS** column per the decision (honest-gated); the **urgent-bypass** guarantee (already enforced by `always_on`); the **save** POST + toast; **in-app preserved** (feed unaffected); **quiet hours kept**. New tests (email pref honored end-to-end [reuse the proven gate], the SMS column honestly gated [+ stored if Option A], the urgent-bypass, the save persists + toast). Consolidate docs (this audit → resolved, `DECISIONS.md` #79, `DIVERGENCE-REGISTER.md` Notifications → exact-design + SMS honest-gated, `PROJECT-STATE.md` v24). Suite green + PHPStan L5 + Pint clean; `migrate:fresh --seed` clean; browser-verify. Per-gate commit + push.

## ✅ RESOLUTION (built 2026-07-30 · invariant #79 · single gate)

`/owner/notification-preferences` rebuilt to the wireframe EXACTLY on the real `NotificationPreferences` engine — **no schema/service/controller change** (the founder chose the display-only SMS, so zero disturbance to the shared engine + the operator page).

| Section | Result |
|---|---|
| Breadcrumb + header | ✅ shell breadcrumb (Settings / Notifications) + the 30px title + the statements-in-portal sub. |
| Where we reach you | ✅ REAL — the owner's own **email** (the auth user's, where mail routes) + a working master toggle; the **SMS** card shows the owner's phone (honest "No number on file yet" when unset) with a **disabled** switch + "SMS delivery isn't active yet — we'll use these preferences once it's set up." |
| What you hear about | ✅ **Email = REAL** (the 5 real owner events; per-event email toggles stored + honored by `allows()`; defaults correct — Performance digest off). **SMS = HONEST-GATED** (display-only, disabled, off-appearance, **no `name` → never submitted, never stored**, #39). **In-app** dropped from the UI but **preserved** via hidden inputs (the feed is never turned off). `account_security` renders locked-on ("Always on"). |
| The guarantee | ✅ **REAL — rendered verbatim.** `account_security` is `always_on` (bank/payout changes + sign-ins) → `allows()` bypasses a channel-off; no marketing event exists. Not aspirational. |
| Save + toast | ✅ REAL — the existing self-only POST (#20); the wireframe's bottom-right toast fires on the real save (the PRG flash) + auto-dismisses. "Unsaved changes / All preferences saved" tracked client-side. |
| Quiet hours | 📐 KEPT (a documented addition — the wireframe omits it; a shipped real feature we don't drop). |

**Decision taken:** SMS = **display-only (not stored)** — zero disturbance; the SMS prefs get captured when SMS delivery is actually built. Proven by `OwnerNotificationsPageTest` (3): the exact sections + the real email + the honest SMS gate render; a per-event email toggle through the page POST is honored (`allows` flips) AND in-app is preserved AND no `sms` row is ever created; `account_security` email can't be turned off through the page (urgent-bypass real). Suite 1268/1268; PHPStan L5 (0); Pint clean; browser-verified (email toggle persisted, SMS gated, save toast).

## 5. Guardrails held throughout
🔒 **#20** the save is a self-only controller POST (audience from the route, never the request), audited via the flash + PRG · **#39** no fabrication — no SMS toggle that implies a send (clearly gated, no provider), no false urgent-guarantee (the bypass is REAL via `always_on`), no marketing event (none in the catalog) · the owner's OWN email/phone are shown (NOT guest #28) · **anti-drift** — the ONE `NotificationPreferences` engine + the owner Mailables, never a parallel prefs store · in-app preserved (the feed is not silently turned off).
