# Owner Messages — exact-match audit (PHASE 1, read-only)

**Date:** 2026-07-30 · **Page:** `/owner/messages` (+ `/owner/messages/{thread}`) vs the decoded wireframe `docs/design/raw/Owner Messages.html`.
**Method:** decoded the dc-runtime bundle (JSON.parse the `__bundler/template` script → the `<x-dc>` body + the `Component` logic) + read the real relay backend. **Nothing changed.**

---

## 0. The shape of the gap

The **core relay is real and solid** — `InboxService` (`startThread` / `postOwnerMessage` / `markReadByOwner` / `sendOutbound`), the `MessageThread` (owner↔operator, `owner_id` + `owner_last_read_at`) + `Message` (`direction` org-relative, `body`, `author_user_id`, `sender_label`, `delivery_status`) models, and the current owner page already render the owner's real threads with the real **staff correspondent** (`author_user_id` → real name + real back-office role), real bodies/timestamps, and a real **unread** state (derived from `owner_last_read_at`). The current page **honestly refuses** the wireframe's rich chat features (its docblock documents each refusal).

The wireframe is a **rich 3-pane chat** (sidebar · conversation rail · thread pane) with reply-to, attachments, reactions, read-receipts, starred, pinned, compose, search, presence + typing. Per the **build mandate**, the missing rich features are BUILD targets — except the two that genuinely need real-time infra (presence, typing), which stay honest-omitted.

⚠️ **This is the owner↔OPERATOR relay** — NEVER owner→guest. The correspondents are the org's **back-office staff** (real users with a back-office role); guest data is name-only (#28) and no guest thread is ever reachable (the `OwnerScope` on `owner_id` sees to that). The wireframe's "Vyrobel Support / Vyrobel Billing" desks are the SaaS **vendor**, not the operator — REFUSED (the real recipients are the real staff).

---

## 1. Feature-by-feature classification (REAL / BUILD / NOT-BUILDABLE)

| # | Feature (wireframe) | Real backing | Classification |
|---|---|---|---|
| 1 | **Header + compose** ("Messages · {convoSub}" + New-message) | `InboxService::startThread` (owner-initiated threads exist). | ✅ **REAL** (surface). |
| 2 | **Conversation list** (name/role/time/preview/unread) | The owner's real threads + staff correspondent + `owner_last_read_at`-derived unread + last-message preview. | ✅ **REAL** (already built — restructure into the rail). |
| 2a | **All / Unread filter + unread count** | Derived from the real unread state. | ✅ **REAL** (client-side + derived). |
| 2b | **Search** (name/role/message text) | Over the owner's own threads/messages. | ✅ **REAL** (client-side/server filter). |
| 2c | **online-dot (presence)** | ⚠️ needs real-time presence (websockets/heartbeat) — none exists. | 🔒 **NOT-BUILDABLE → honest-omit** the green dot (never a fake "online"). The header "usually replies within an hour" **promise** is also refused → honest copy. |
| 3 | **Compose → pick recipient** (name/role/badge) | The org **back-office staff** (real users + roles); `startThread` + `assigned_to`. | ✅ **REAL** — recipients are the real staff (NEVER a guest #28); the "Vyrobel Support/Billing" vendor desks REFUSED. *(founder decision: per-staff vs a single "Management team".)* |
| 4 | **Thread view** — day separators, messages, meta | Real messages + `created_at` (day separators derived). | ✅ **REAL**. |
| 4a | **REPLY-TO** (quote the replied-to message) | ⚠️ no `reply_to` on `messages`. | 🔨 **BUILD** — `reply_to_message_id` (nullable self-FK) + the composer reply-to + the quoted render. |
| 4b | **ATTACHMENTS** (image / file + name/size) | ⚠️ no attachment on messages. | 🔨 **BUILD** — reuse the **Add-Unit secure-upload pattern** (`storeAs` + the polymorphic `Media` on a `Message`; validated type/size; owner-scoped). Image + file. NEVER a fake attachment. |
| 4c | **REACTIONS** (emoji per message per user) | ⚠️ not backed (wireframe is localStorage). | 🔨 **BUILD** — a `message_reactions` model (message_id · user_id · emoji; unique). |
| 4d | **READ-STATUS** (Sent/Delivered/Read ticks) | Partial — `delivery_status` gives *delivered* (internal), but no counterparty-read timestamp (no `read` enum). | 🔨 **BUILD** — an `operator_last_read_at` on the thread (set when an operator opens it); the owner's outbound message is **Read** if `operator_last_read_at ≥ message.created_at`, else **Delivered** (**Sent** transient). A REAL read-receipt — NEVER a fabricated "read" tick. *(founder decision: build vs honest-omit read.)* |
| 4e | **COPY per message** | Client-side clipboard. | ✅ **REAL** (client-side, no backend). |
| 4f | **PIN per message** (per-conversation) | ⚠️ not backed (persists nowhere in the mock). | 🔨 **BUILD** — `pinned_message_id` on the thread (one pin per thread) + the pinned banner. |
| 5 | **STARRED panel** (per-user message star) | ⚠️ not backed (localStorage). | 🔨 **BUILD** — a `message_stars` pivot (user_id · message_id; unique) + the panel; honest-empty. |
| 6 | **PINNED banner** | (see 4f) | 🔨 **BUILD** (the render of `pinned_message_id`). |
| 7 | **Reply composer** — draft, reply-to context, quick-reply chips | The **send** is real (`postOwnerMessage`, a controller POST #20, audited); reply-to wires to 4a; quick-replies seed the draft. | ✅ **REAL** (+ the built reply-to). |
| 8 | **Empty states** (no-search-match / all-caught-up / empty-thread / no-starred) | Real. | ✅ **REAL** (honest). |
| 9 | **typing indicator** | ⚠️ needs real-time. | 🔒 **NOT-BUILDABLE → honest-omit**. |
| 10 | **Motion / warm-glass / Sora-Inter / `vyFadeUp`** | Shared owner chrome. | ✅ **REUSE**. |

---

## 2. Reuse map (anti-drift)

- **`x-owner.shell` + `--vy-*` tokens** — the warm-glass frame + `vyFadeUp`.
- **`InboxService`** — `startThread` (compose), `postOwnerMessage` (send — the ONE owner write path, audited #20), `markReadByOwner` (owner read), `markRead` (operator read — where `operator_last_read_at` gets set).
- **The Add-Unit secure-upload pattern** (`OwnerUnitRequestController::storePhotos` → `storeAs` + `Media` polymorphic) — reused for message attachments (validated, safe storage, owner-scoped).
- **`Media`** (polymorphic `mediable`) — attach to a `Message` (no parallel structure).
- **The staff correspondent** (`author_user_id` → real name + real back-office role) — the conversation identity + compose recipients (NEVER a guest, #28).

---

## 3. ⚠️ FOUNDER DECISIONS

1. **Compose recipients — per-staff (assign) or a single "Management team"?** The org has real back-office staff (name + role). Option A (**recommended, matches the wireframe**): list the real staff; starting a thread **assigns it** to the picked person (`assigned_to`) while staying team-visible. Option B: one "Message your management team" that starts a generic thread. Either way the recipients are **real staff, NEVER a guest**, and the fake "Vyrobel Support/Billing" vendor desks are refused.
2. **Read-status — build the real read-receipt or honest-omit?** *Recommended: **BUILD** `operator_last_read_at`* → real Sent/Delivered/Read ticks on the owner's messages. Alternative: show only Delivered (no read tick) — honest but less than the wireframe.
3. **Presence (online-dot) + typing — honest-omit (recommended)** — no real-time infra, so never a fake "online"/typing. (Optional: a real "last active" from `users.last_login_at` as text — but the green *presence* dot stays omitted.)

Everything else (reply-to · attachments · reactions · starred · pinned · compose · search · filter · copy) is **BUILD-real or already-real with no decision.**

---

## 4. Build plan (gated)

- **⛔ GATE 1 — the exact-design 3-pane page on the REAL relay.** Restructure `/owner/messages` into the wireframe's 3-pane (conversation rail + thread pane in one view; picking a conversation is a GET so the middleware runs) — shared chrome + `--vy-*`. Wire the REAL core: the conversation list (real threads · staff correspondent · unread · preview · time), the All/Unread filter + count, search, the thread view (messages · day separators · meta), **compose** (start a real thread with a real staff recipient — a controller POST #20), the reply composer **send** (`postOwnerMessage`, controller POST #20, audited), quick-reply chips (seed the draft), the honest empty states, and **honest presence** (online-dot + typing omitted; no fabricated promise). 🔒 #28 owner↔operator-only + name-only · #20 controller send/compose + audited · #39 no fabrication. Suite green + tests + browser-verify. Per-gate commit.
- **⛔ GATE 2 — BUILD the rich features + consolidate.** Each additive, controller-routed (#20, audited), owner-scoped (#28), mutation-proven (persists + reflects): **reply-to** (`reply_to_message_id`), **attachments** (Media on Message via the Add-Unit upload pattern — image + file), **reactions** (`message_reactions`), **read-status** (`operator_last_read_at` → real ticks), **starred** (`message_stars` per-user), **pinned** (`pinned_message_id` per-thread). Consolidate docs (this audit → resolved, `DECISIONS.md`, `DIVERGENCE-REGISTER.md` Messages → exact-match + rich features, `PROJECT-STATE.md`, invariant). Browser-verify each feature. Suite green; PHPStan L5 + Pint clean; `migrate:fresh --seed` clean. Per-gate commit + push.

---

## 5. Guardrails held throughout
🔒 **#28** owner↔OPERATOR only (the `OwnerScope` on `owner_id` makes a guest/cross-owner thread 404) + staff correspondents are named, guest data name-only, NEVER owner→guest · **#20** every write (compose · send · react · star · pin · attach · reply) is a controller route, audited (never a `/livewire/update` mutation) · **#39** no fabricated feature — no fake attachment/reaction/read-tick/star/pin/**presence**/typing; the read-receipt is a real `operator_last_read_at`; presence + typing are honestly omitted (real-time infra needed).
