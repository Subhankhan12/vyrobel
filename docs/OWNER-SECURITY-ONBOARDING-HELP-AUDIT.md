# Owner Security + Onboarding + Help — wireframe-EXACT-match audit

**Date:** 2026-07-28 · **Author:** build session · **Status:** 🔎 **PHASE 1 COMPLETE — read-only audit; PAUSED for approval (several account-gated bits + the Onboarding IBAN sensitivity).**
**Pages:** `/owner/security` (`OwnerSecurity` + `OwnerSecurityController`) · **Onboarding** (NET-NEW, no route) · `/owner/help` (`OwnerHelp`, static).
**Wireframes:** `docs/design/raw/Owner Security.html` · `Owner Onboarding.html` · `Owner Help.html` (decoded → `/tmp/owner_*_decoded.txt`).

---

## 0. Headline

- **SECURITY & HELP are already built HONESTLY** — the account rebuild (#46) + the static Help surface already made every
  investigate call correctly (no fabricated score, no fake locations/apps/approval, no fake AI/tickets/status). The delta
  is **presentation** (match the fuller wireframe design) + one optional real touch (a derived score number).
- **ONBOARDING is NET-NEW** and its centerpiece — an **IBAN capture + "two micro-deposits on the way"** — **cannot be built
  honestly** (no certified micro-deposit pipeline; and the compliance call is: do NOT stand up a fresh IBAN form). It must
  be honest-gated (link to the real Payout Method page) with the micro-deposit claim **refused**. ⚠️ **This gate needs
  founder sign-off** (build the honest thin flow vs. skip as net-new/not-launch-blocking) — see §2.

---

# PAGE A — SECURITY (`/owner/security`)

## A1. Section classification (verified in the repo + a live browser audit)

| Wireframe section | Verdict | Backend |
|---|---|---|
| **Security score** (n/100 + tier + points) | 🟡 **HONEST — REAL-derivable** | The live page renders a **derived "Account protection" checklist** (Password set · 2FA · Recovery codes), NOT a fabricated number. For the exact-match I can render the wireframe's **score card** with a **REAL derived score** (= on-protections ÷ total × 100) from true state — honest gamification — OR keep the checklist. **Never a fabricated 0–100.** ← minor call. |
| **Protections-on / Points-available / "Boost your score"** | 🟡 HONEST | maps to the REAL toggleable protection (2FA) only — "boost" = turn on 2FA. No fake point economy. |
| **Change password** | ✅ REAL — REUSE | `owner.security.password` → `ChangePasswordController::owner` (current-password verified, rules, throttled, audited, #20). |
| **Two-factor** (enable/confirm/recovery/disable) | ✅ REAL — REUSE | `owner.security.2fa.*` (`TwoFactorController`, `PreparesTwoFactor`). |
| **Active sessions** (device/ip/last-active + sign-out others) | ✅ REAL — REUSE | the real `sessions` table + `owner.security.sessions.signout` (re-auth, rotates remember-token, audited). |
| **Data & privacy — "Request my data"** (JSON/CSV) | ✅ **REAL — REUSE** | `owner.security.export` → a **real owner-scoped streamed JSON download** (profile + statements + payouts + units). **NOT a fake "emailed link".** (CSV is honest — the real export is JSON.) |
| **Deactivate account** | ✅ REAL — REUSE | `owner.security.deactivate` → reversible `users.status='disabled'` (primary-owner-only #42, re-auth, audited, financials kept). |
| **Trusted locations** (skip-2FA-here, add/remove) | 🔴 **HONEST — "Coming soon"** | no such store; a fabricated trusted-location is a security claim → honest-defer (already a "Coming soon" item live). |
| **Connected apps** (OAuth grants, revoke) | 🔴 **HONEST — "Coming soon"** | no OAuth authorization-server → honest-empty/defer, never a fake app row. |
| **Login approval** (approve new sign-ins) | 🔴 **HONEST — "Coming soon"** | no device-approval subsystem → honest-defer, never a fake approval. |

## A2. Security resolutions
- **Score → HONEST derived** (recommended: render the score card with a **real derived score** from the true protection
  state — honest gamification; or keep the checklist). Never fabricated.
- **Trusted locations / connected apps / login approval → HONEST "Coming soon"** (already the live call; no fakes).
- **Data export → REAL** (streamed JSON, owner-scoped). **Deactivate → REAL** (reversible, primary-owner-only). **Password
  / 2FA / sessions → REAL.** All reused.

## A3. Security build plan — **⛔ GATE 1**
Match the wireframe's design (the score card + the sectioned layout) on the shared chrome, REUSING every real control
(password/2FA/sessions/export/deactivate — all existing routes). Render the score card with a **real derived score** (or
the checklist), and the unmodelled sections as honest **"Coming soon"**. Guards #20 (controller writes + audited) / #39
(no fabricated score/location/app/approval). Extend `OwnerSecurityTest`. Browser-verify. Commit.

---

# PAGE B — ONBOARDING (net-new welcome wizard) ⚠️

The decoded flow (all data hardcoded — Amara Okafor, an IE IBAN, 15%): **Step 1 Welcome** (name + statement-email + "3
units at 15% commission") → **Step 2 Payout account** (Account holder + **IBAN** + "we send two micro-deposits to verify")
→ **Step 3 Preferences** (statement-email / payout-alert / digest toggles) → **Done** ("3 units connected", "**two
micro-deposits are on the way — verify in Payouts within 3 days**", "first statement on the 1st"). Standalone shell (no
sidebar).

## B1. Section classification

| Step / element | Verdict | Resolution |
|---|---|---|
| Standalone welcome shell | 🟢 build | a new minimal layout (no owner sidebar), shared `--vy-*` tokens. |
| Step 1 — name + statement-email | ✅ REAL | the owner's REAL name/email (confirm/edit → the existing profile store `OwnerAccountController`). |
| "3 units at **15%** commission" | ⚠️ **FIX** | the REAL units (count + names, owner-scoped) at the **REAL agreement rate 18%** (`OwnerCommissionRate`), never 15%. |
| Step 2 — **Account holder + IBAN** | 🔴 **HONEST-GATE (no capture)** | ⚠️ do NOT stand up a fresh IBAN form. The REAL banking path already exists (`owner.payout-method` — encrypted IBAN + mod-97 + Stripe Connect). → the step is an **honest hand-off: "Set up your payout account"** → a link to the REAL Payout Method page. No new credential capture here. |
| "two **micro-deposits** to verify" | 🔴 **REFUSE** | no micro-deposit pipeline exists (the Payouts audit #58 keeps verification honest-pending). → NEVER "on the way"; the honest copy is "your management team confirms your account". |
| Step 3 — notification toggles | 🟡 REAL-or-honest | reuse the real notification-preference store (in-app + email real; SMS/push honest-pending, per Settings #62) OR link to Settings. |
| Done — "3 units connected · first statement on the 1st" | ✅ REAL/honest | real unit count + email; honest next-steps (NO fabricated micro-deposit timeline). "Verify my payout account" → the real Payout Method page. |

## B2. ⚠️ Onboarding — OPEN QUESTIONS (founder decision)
1. **Build it, or skip?** Onboarding is **net-new**, and its headline feature (IBAN + micro-deposits) is un-buildable
   honestly, so the honest version is a **thin welcome flow that re-presents existing real surfaces** (profile confirm +
   REAL 18% units + a link to the real Payout Method + notification prefs + honest done-state). It is **not
   launch-blocking**. → **Build the honest thin flow, or defer Onboarding?**
2. **If built:** (a) **trigger** — a first-run auto-redirect (needs a "welcomed_at" flag), or just a reachable route
   (`/owner/welcome`)? (b) **the IBAN step** — the honest hand-off to Payout Method (recommended), or wire the real Stripe
   Connect onboarding start inline? Either way **NO fresh IBAN capture + NO micro-deposit claim**.

**Recommendation:** build the **honest thin flow** at a reachable route (`/owner/welcome`), IBAN step = the hand-off link
to the real Payout Method, no auto-redirect/flag in this pass (a separate product decision). ⚠️ Awaiting your call.

---

# PAGE C — HELP (`/owner/help`)

## C1. Section classification

| Wireframe section | Verdict | Backend |
|---|---|---|
| **Contact channels** (chat / call / email / WhatsApp) | ✅ REAL (static) | real, honest static contact info; "chat/message" → the real relay (below). |
| **"Message your manager"** | ✅ REAL — REUSE | the owner↔operator inbox relay (`owner.messages.start` / the existing Help relay). |
| **FAQ** (q/a + links to real screens) | ✅ REAL (static content) | real help content linking the real owner screens (already live: "Owner FAQs"). |
| **AI-ask** ("instant answer") | 🔴 **HONEST — REFUSE the fake AI** | no support AI → an honest "ask your manager / browse FAQs", never a fabricated AI answer. |
| **Support requests / tickets** (create/track/resolve) | 🔴 **HONEST-DEFER** | no owner support-ticket store (MaintenanceTicket is unrelated operator maintenance) → the real message relay is the channel; never a fake ticket that tracks nothing. |
| **System status** (per-service operational) | 🔴 **HONEST-STATIC** | no live health feed → an honest **static "All systems operational"** line (not a fabricated live health check), or omit. |
| Browse-by-topic / FAQ search / "was this helpful" | 🟡 static | real static help affordances (client-side filter over the real FAQ content); no backend claim. |

## C2. Help resolutions
Match the design; wire the **real** contact channels + FAQ + message relay; **AI-ask / tickets = honest** (link to the
relay / FAQs, never a fake answer or ticket); **system status = honest static "operational"** (not a live check). All
already the live call — GATE 3 is design-match + these honest states.

## C3. Help build plan — **⛔ GATE 3**
Match the wireframe (contact channels, FAQ, message relay, browse-by-topic) on the shared chrome; keep AI/tickets/status
honest (no fakes). Consolidate docs. Browser-verify. Commit + push.

---

## Guards (all gates)
🔒 #28 owner-scoped · #20 controller-route writes + audited (password / relay) · #39 **nothing fabricated** (no fake
security score/location/app/approval, no fake emailed-link, **NO IBAN capture**, no fake micro-deposit, no fake
AI-answer/ticket/live-status) · **NO IBAN collection** in Onboarding (the real Payout Method page owns banking). REUSE the
security controller + the message relay + the profile/notification/payout-method surfaces (anti-drift). DISTURB NOTHING
existing (the green suite is the proof).

## Build order & open items
- **GATE 1 Security** — design-match + real reuse + honest score/soon. *(No open question — proceed on approval.)*
- **GATE 2 Onboarding** — ⚠️ **founder call needed** (build the honest thin flow vs. defer; trigger; the IBAN hand-off).
- **GATE 3 Help** — design-match + real contact/FAQ/relay + honest AI/tickets/status. *(No open question.)*
