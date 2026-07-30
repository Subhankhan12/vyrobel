# Invite Owner (form + email) — exact-match audit (PHASE 1, read-only)

**Date:** 2026-07-30 · **Surfaces:** the operator "Invite owner" modal (`/app/owners`, `OperatorOwners` + `owners.blade`) vs `Vyrobel Invite Owner.html`; the invite EMAIL (`emails/owner-invite.blade`, `OwnerInviteMail`) vs `Owner Invite Email.html`.
**Method:** decoded both dc-runtime bundles (JSON.parse the `__bundler/template` → `<x-dc>` body + the `Component` logic) + read the real invite flow (`InviteOwnerController` → `OwnerInviteService` → `OwnerInvite` + `OwnerInviteMail` + `AcceptInviteController`). **Nothing changed.**

---

## 0. The shape of the gap — this is almost entirely REAL already

The operator→owner invite is a **complete, certified, real flow**, and the form + email already carry every field the wireframes show. This is a **presentation match + a few real additions**, not a build.

- `POST /app/owners/invite` → `InviteOwnerController` → `OwnerInviteService::invite()` already accepts **name · email · role(owner/manager/accountant) · commission_pct · unit_ids** — exactly the wireframe's fields. Gated **`can:users.manage`** (the privilege-escalation gate — operator-admin only) + `throttle:10,1`, CSRF, tenant-scoped (#20).
- The service creates a **real pending invite** (`owner_invites`: `role`, `assigned_unit_ids`, `commission_pct`, sha256 **token_hash**, 7-day `expires_at`, `status=pending`), a pending `Owner` (owner role) or a `Membership` (staff role), and **sends `OwnerInviteMail`** with the real tokened `/invite/{token}` link. `AcceptInviteController` redeems it: set password → **activate** the login + owner → **assign units + create the `ManagementAgreement` at the chosen commission** → assign the role → **spend the single-use token** → auto-login → `/owner`. Revoke + a 7-day prune exist.
- The **role mapping is real and confirmed** (the wireframe's guess "owner-team roles" is not how this flow works — that's the SEPARATE owner→team flow): **owner → `External Owner`** (owner portal + units + commission + agreement); **manager → `Hotel Manager`** (operator STAFF, `/app`, org-wide operations); **accountant → `Accountant`** (operator STAFF, `/app`, financials). Owner-only fields (units, commission) are dropped server-side for staff.
- The **email is real + email-safe + role-aware** (`OwnerInviteMail` + `emails/owner-invite.blade`): the real `acceptUrl` token link, the real `expires_at`, a bulletproof button, the paste-able link, and role-appropriate headline/intro (owner portal vs Hotel Manager vs Accountant).

So: the **commission-at-invite is REAL** (the chosen 12/15/18 becomes the real `ManagementAgreement.commission_pct`; "later" = no agreement — NEVER a fabricated rate); the **units are REAL** (tenant-scoped, assigned on activation); the **email sends + the link activates a real account** (no account until the password is set). Nothing here is faked.

---

## 1. PIECE A — the FORM (element-by-element)

| # | Element (wireframe) | Real backing | Verdict |
|---|---|---|---|
| 1 | Header — `roleTitle` ("Invite an owner/property manager/accountant") + "They'll get an email to set their password…" + close | The modal already retitles by role. | ✅ **REAL → restyle** to the exact warm-glass modal (460px, header icon, exact type). |
| 2 | Name + Email | Real inputs (`name`/`email`, validated). | ✅ **REAL → restyle.** |
| 3 | Role picker Owner / Manager / Accountant + `roleHelper` | Real (`role` → owner/Hotel-Manager/Accountant). | ✅ **REAL → restyle** to the exact segmented control. |
| 4 | Assign units (Villa Perla / Apt 204 / Casa Verde) + count | Real tenant-scoped `Unit`s (owner-only — assigned on activation). | ✅ **REAL → restyle** (exact chips + checkmark). 📐 **Divergence:** the wireframe shows units for manager/accountant too; the real staff roles are **org-wide** (units don't apply + are dropped server-side), so units show for the **owner role only** — never a no-op control (#39). |
| 5 | Management agreement 12 / 15 / 18 / "Set up later" + `commHelper` (owner-only) | Real — the chosen % becomes `ManagementAgreement.commission_pct` on activation; "later" defers. | ✅ **REAL → restyle.** Default preset = **18%** (the real system/demo default via `OwnerCommissionRate`), not the wireframe mock's 15% — a legitimate selection, never fabricated. |
| 6 | Access level — Manager "Full operations…" / Accountant "Read & export…" | The real role capabilities (Hotel Manager operations / Accountant financials). | 🔨 **BUILD (presentation) — the exact access-level card** for manager/accountant (currently a help-text line). |
| 7 | Cancel / Send invite → the **"Invite sent"** confirmation (emailed X · Pending · expires 7 days · resend from the owner's row) | The real POST + PRG; the real pending/expiry/resend. | 🔨 **BUILD (presentation) — the in-modal "sent" view.** Today it's a PRG flash on the owners page; add the exact confirmation view, rendered on the successful redirect (a session flag carrying the invited email + expiry). |
| 8 | "Preview the invite email" | The real Mailable template. | 🔨 **BUILD — a real preview route** (operator-admin) that renders the real `OwnerInviteMail` template. |

**Backend:** no service/controller change needed for GATE 1 (the flow is complete). The only wiring: pass the invited email + expiry to the redirect so the "sent" view can render the real confirmation.

---

## 2. PIECE B — the EMAIL (element-by-element)

| # | Element (wireframe) | Real backing | Verdict |
|---|---|---|---|
| 1 | Brand + eyebrow "Owner portal invitation" / "Accountant access invitation" (role-aware) + **"Invited by {operator}"** | `OwnerInviteMail` (role-aware copy); `orgName` resolved. | ✅ **REAL → match** + add the "Invited by {operator}" line + the role-aware eyebrow. |
| 2 | "Hi {owner}," + `bodyLine` (role-aware) | Real `ownerName` + `intro`. | ✅ **REAL → match** the exact body copy. |
| 3 | **"In your portal"** (owner: Monthly statements / Payouts & schedule / Unit performance) OR **"What you can access"** (accountant: Statements & ledgers / Tax documents / Payout reconciliation) | Role-appropriate feature lists (static, honest descriptions of what the portal/role gives). | 🔨 **BUILD (presentation) — the role-aware feature section** (owner → "In your portal"; accountant → "What you can access"; manager → an operations list). |
| 4 | "Set your password" button (`url`) + paste-able link + "This invite expires in {expiryStr}" + "no account is created until you set a password." | Real tokened `acceptUrl`, real `expires_at`; the "no account until password" fact is TRUE (activation only on accept). | ✅ **REAL → match** (already present; align copy/expiry-format). |
| 5 | Footer — "You're receiving this because {operator} {roleReason} · Sent automatically when an operator invites an owner · Edit in Email templates" + Get help / Sign in | The invited-by is real; "Edit in Email templates" is an operator-facing note. | 🔨 **BUILD (presentation) — the exact footer** (invited-by reason + the automatic-send note; "Edit in Email templates" rendered as honest copy, not a live link unless a templates screen exists → plain text). |

**Mailable:** extend `OwnerInviteMail::content()` to pass `operator` (invited-by), the role-aware feature items, and `roleReason` — no new send path (reuse the real Postmark/MailSafetyGuard infra). Add a **preview route** rendering the same blade with the real invite's data.

---

## 3. Reuse map (anti-drift)
- **`OwnerInviteService`** — the ONE invite write path (record + token + pending + role + units + commission + agreement + mail + revoke + prune). Not rebuilt.
- **`InviteOwnerController`** (`can:users.manage`, throttle, CSRF, PRG) — the real gated write (#20).
- **`OwnerInviteMail` + `emails/owner-invite.blade`** — the real role-aware, email-safe Mailable (Postmark infra + `MailSafetyGuard`). Matched, not rebuilt.
- **`AcceptInviteController` + `OwnerInvite::isRedeemable()`** — the real tokened set-password → activation.
- **`OperatorOwners`** — the real owners list + pending invites + units (drives the modal).
- **`OwnerCommissionRate`** — the real rate the picker defaults to (18%).

## 4. Founder decisions
**None blocking.** The role model + commission-at-invite are real and confirmed (no extension needed). Two minor divergences are documented above and resolved honestly (units for the owner role only, since staff roles are org-wide; the commission picker defaults to the real 18%). If the founder later wants unit-scoped managers/accountants, that is a NEW owner-team concept (the separate `OwnerTeamInviteService`), out of scope here.

## 5. Build plan (two gates)
- **⛔ GATE 1 — the FORM.** Restyle the invite modal to the exact wireframe (warm-glass 460px modal, header icon, segmented role + agreement pickers, unit chips, the access-level cards for manager/accountant) + add the in-modal **"Invite sent"** confirmation view (rendered on the real PRG via a session flag: email + Pending + "expires in 7 days · resend from the owner's row" + "Preview the invite email" + "Back to owners") + the **preview** link. Reuse the real flow (no service change; pass the email+expiry to the redirect). New/updated tests (the invite still creates the real records + sends; the sent view renders; operator-admin gating). Browser-verify (send → real pending invite + queued email). Commit.
- **⛔ GATE 2 — the EMAIL + consolidate.** Match `emails/owner-invite.blade` to the exact wireframe (eyebrow, invited-by, the role-aware feature section, button+link, expiry, footer — email-safe inline styles); extend `OwnerInviteMail::content()` (operator, feature items, roleReason). Add the operator-admin **preview route**. Consolidate docs (this audit → resolved, `DECISIONS.md` #80, `DIVERGENCE-REGISTER.md` invite form+email → exact-match, `PROJECT-STATE.md` v25). New tests (the email renders role-aware with the real token link; the preview route is gated). Browser-verify (the email renders; the set-password link activates a real account). Commit + push.

## ✅ RESOLUTION (built 2026-07-30 · invariant #80 · two gates)

Both pieces matched to their wireframes EXACTLY on the REAL, certified invite flow — no backend rebuild.

**GATE 1 — the FORM (`0853092`).** The invite modal in `owners.blade` restyled to the exact "Vyrobel Invite Owner" wireframe: warm-glass 460px scrim + `vyModalIn`, header icon + role-aware title, segmented Role (Owner/Manager/Accountant), the real tenant-scoped unit chips, the management-agreement segmented picker (12/15/18/Set up later) defaulting to the REAL **18%**; the exact **access-level cards** (Accountant "Read & export…" / Manager "Full operations…"); and the in-modal **"Invite sent"** view rendered on the real PRG via a structured flash (`owner_invite_sent` = the real email + real 7-day expiry) — check icon, "Pending until they activate", "expires on {date} · resend from the owner's row", "Preview the invite email", "Back to owners". Added `InviteOwnerPreviewController` — renders the REAL `OwnerInviteMail` via `->render()` (transient invite + placeholder token; never sends/writes), gated `can:users.manage`. Backend untouched (the controller only gained the structured flash). Honest divergences: units for the OWNER role only (staff are org-wide); the picker defaults to the real 18%. Tests: `InviteOwnerFormTest` (3).

**GATE 2 — the EMAIL.** `emails/owner-invite.blade` matched to the exact "Owner Invite Email" wireframe (email-safe, table-based, inline CSS): the role-aware **eyebrow** ("Owner portal invitation" / "Accountant access invitation" / "Property manager invitation"), the **"Invited by {operator}"** line, the headline + role body, the role-aware **feature section** ("In your portal" [Monthly statements / Payouts & schedule / Unit performance] · "What you can access" [Statements & ledgers / Tax documents / Payout reconciliation] · "In the operator app" for manager), the bulletproof **Set your password** button + the paste-able **real tokened link**, the real **expiry** + "no account is created until you set a password", and the exact **footer** ("You're receiving this because {operator} {roleReason} · Sent automatically…"). `OwnerInviteMail::copyFor()` extended (eyebrow, section title, feature items, role reason, operator) — same real Postmark/`MailSafetyGuard` send path. Both variants browser-verified via the preview route.

Suite **1271/1271**; PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean. The whole certified invite chain (invite → token → email → set-password → activation → role/units/commission) stays real + green.

## 6. Guardrails held throughout
🔒 **#20** the invite is a controller POST, **operator-admin gated (`can:users.manage`)** + throttled + audited via the flash/PRG; the preview route is operator-admin-gated too · **#39** no fabrication — the commission is the real chosen rate (→ `ManagementAgreement`), the invite/token/expiry/resend are real, the email genuinely sends (Postmark), the set-password link activates a real account (no account until the password is set); units shown only where they really apply (owner) · **anti-drift** — reuse `OwnerInviteService` + `OwnerInviteMail`, never a parallel invite path.
