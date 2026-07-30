# Operator Email Templates + Editor — exact-match audit (PHASE 1, read-only)

**Pages:** `/app/email-templates` (list — a static catalog today) + a NEW single-template **Editor**.
**Wireframes:** `Operator Email Templates.html` · `Operator Email Editor.html` (dc-runtime bundles — decoded)
**Date:** 2026-07-30 · **Status:** PHASE 1 complete; awaiting the stat + test-send decisions.

---

## 0 · The core finding — the #83 emails are HARDCODED; the mandate is a real customization store

The 5 guest Mailables (#83 — pre-arrival · payment-reminder · refund · review-request · verify) have their
**subject/heading/message/button hardcoded** in the Mailable (`envelope()`/`content()`) + the blade. The
existing `/app/email-templates` page is a **static catalog** (`EmailTemplateController::TEMPLATES` const) with
an honest "Editing template content is coming · Soon" — no store, no editor.

**So the mandate is a genuine BUILD:** a per-org, per-template **customization store** (subject/heading/
message/button + block toggles + enabled) that the **real Mailables consume at send**, so editing + saving
**genuinely changes the real email** — mutation-proven. The defaults MUST equal the current hardcoded strings
so an un-customized template renders byte-identically (the #83 tests stay green — anti-drift).

---

## 1 · The architecture (how the store threads into the real Mailables)

1. **Store** — `email_templates` table: `organization_id`, `template_key`, `enabled`, `subject`, `heading`,
   `message`, `button_label`, `blocks` (json toggle map), `updated_by`, timestamps; unique(org, key). Model
   `EmailTemplate` (BelongsToOrganization; morph-map alias).
2. **Catalog of defaults** — `App\Support\Email\EmailTemplateCatalog`: per key → label · trigger-desc ·
   channel · Mailable class · **default subject/heading/message/button** (= the current hardcoded strings) ·
   the **real toggleable blocks** for that template (each: key, label, default-on) · the mergeable variables.
3. **Resolver** — `EmailTemplateResolver::for($orgId, $key)` → a value object with the EFFECTIVE
   subject/heading/message/button (stored ?? default) + blocks + enabled. A `merge($text, $vars)` replaces
   the real chips (`{first_name}`/`{unit}`/`{nights}`/`{reference}`) from the reservation/guest.
4. **Thread into the 5 Mailables (additive)** — each `content()` resolves the template for the RESERVATION's
   org, merges its real vars, and passes `heading`/`message`/`button`/`blocks` (+ the existing data) to the
   blade; `envelope()` uses the effective (merged) subject. Each blade renders `{{ $heading }}`/`{{ $message }}`/
   `{{ $button }}` and wraps its toggleable blocks in `@if ($blocks['…'])`. **Default = current string → identical
   output un-customized.**
5. **Enabled toggle** — the triggers (`SendGuestTransactionalEmails` + `SendPreArrivalEmails` +
   `SendPaymentReminders`) check `EmailTemplateResolver::enabled($orgId, $key)` before dispatching → OFF
   genuinely stops that email.
6. **Editor (Livewire, live preview)** — holds the draft (subject/heading/message/button/blocks) as
   `wire:model.live` props; `render()` renders the preview by rendering the REAL `emails.{key}` blade with the
   draft + sample merge data → **preview == send (the same blade, can't diverge)**. SAVE = a real controller
   POST (#20, capability-gated, audited) persisting the store; "last edited by X" from `updated_by`.

---

## 2 · Templates list — per-element classification (`/app/email-templates`)

| Element | Class | Plan |
|---|---|---|
| Settings / Guest emails header · "sent from {sender}" | ✅ REAL | the real org sender/brand (org `name`/`branding`) |
| Per-template row: name · trigger · channel | ✅ REAL | the catalog (real #83 triggers: after-checkout / 48h-before / on-refund / 3-days-before / on-claim); channel = Email |
| **status (Active / off)** | 🔨 BUILD | the real per-template `enabled` toggle — OFF genuinely stops the send |
| Preview | ✅ REAL | the existing `EmailPreviewController` render |
| **stat (send-count / open-rate)** | 🚫 HONEST (**Decision A**) | no send-tracking exists → NO fabricated open-rate; show the real status/trigger (or a real send-count only if a counter is added) |
| New template | 🚫 HONEST | the 5 are the fixed transactional set — "New template" honest-omitted/disabled (no arbitrary new automated email) |

## 3 · Editor — per-element classification (NEW)

| Element | Class | Plan |
|---|---|---|
| Header: name · Active · "Sends {trigger}" · last edited by {X}, {date} | ✅ REAL / 🔨 | catalog trigger + the store's `updated_by`/`updated_at` |
| **Subject line** + Insert chips `{first_name}`/`{unit}`/`{nights}` | 🔨 BUILD | editable; the chips are **REAL merge vars** resolving from the reservation/guest at send |
| **Heading · Message · Button label** | 🔨 BUILD | editable; drive the real email (default = current strings) |
| **Block toggles** (per-template real blocks) | 🔨 BUILD | genuinely show/hide the block in the sent email (`@if ($blocks[…])`). review-request: tap-a-star · stay-recap · signature(honest) ; pre-arrival: door-code · directions · wifi · balance ; payment-reminder: totals · pay-button ; refund: status-timeline ; verify: copy-link |
| **Live preview** (+ personalization toggle) | 🔨 BUILD | renders the REAL `emails.{key}` blade with the draft + sample data — preview == send |
| **Save template** | 🔨 BUILD | a real controller POST (#20), capability-gated (`properties.manage`), audited |
| **Test send** | 🔨 BUILD (**Decision B**) | a real test send (the customized Mailable → the operator's own email via the real mail path) or honest |

⚠️ **Honesty:** the wireframe's "Sofia Duarte · Property manager" named signature stays REFUSED as a
fabricated person (#39) — the signature block, if on, is the **honest** property/team signature (org name),
never an invented individual.

---

## 4 · Reuse map
The 5 #83 Mailables + `emails/layout.blade.php` (live preview reuses it → no drift) + the real triggers
(`SendGuestTransactionalEmails`/`SendPreArrivalEmails`/`SendPaymentReminders`) + the real merge data
(reservation/guest already in each Mailable) + `EmailPreviewController` + `<x-operator.shell>` + the
`AuditEventRecorder` + `properties.manage`.

## 5 · Guards
🔒 #20 SAVE is a controller POST, `properties.manage`-gated, audited; #39 no fabrication (the editor
genuinely persists + drives the real email — no mockup; no fake open-rate; real merge; honest signature);
the customization genuinely drives the #83 sends (**mutation-proven** — an edited heading appears in the real
rendered email, a disabled block is absent, an OFF template doesn't send); the **#83 sends undisturbed**
(defaults == current strings → identical un-customized; the #83 email tests stay green); tenant-scoped.

## 6 · Build plan (single gate)
1. Migration + `EmailTemplate` model (+ morph alias). 2. `EmailTemplateCatalog` (defaults = current strings +
per-template blocks + vars). 3. `EmailTemplateResolver` (effective values + merge + enabled). 4. Thread into
the 5 Mailables + wrap the 5 blades' headings/messages/buttons/blocks (defaults identical). 5. Enabled check
in the 3 triggers. 6. The list (`OperatorEmailTemplates` — real rows + the Active/off toggle POST). 7. The
Editor (Livewire live preview + a Save POST controller, audited). 8. Tests (the store drives the Mailable;
the block toggle; the enabled toggle stops the send; the merge resolves; #83 undisturbed). 9. Consolidate
docs; bump HEAD.

---

## FOUNDER DECISIONS
- **A — the list "stat":** **honest** (no send-tracking exists → show the real status/trigger, NO fabricated
  open-rate; recommended) vs add a real send-count (a counter incremented at send).
- **B — Test send:** **real** (send the customized Mailable to the operator's own email via the real mail
  path + `MailSafetyGuard`; recommended) vs honest-gated.
- **(stated, not choices)** the editor genuinely persists + drives the real Mailables (mutation-proven); the
  merge vars are real; a disabled block/template genuinely omits/stops; the signature is honest (no
  fabricated named person); the #83 sends are undisturbed.

## RESOLUTION _(2026-07-30 · #86 · single gate)_

**BUILT — a real per-org customization store that GENUINELY drives the #83 guest Mailables** (mutation-proven +
browser-proven end-to-end). Both founder decisions taken as recommended: **A** stat = honest (status/trigger,
no fabricated open-rate); **B** Test send = a real send to the operator's own email via `MailSafetyGuard`.

**The store + threading**
- `email_templates` (org + `template_key` + `enabled` + subject/heading/message/button + `blocks` json +
  `updated_by`; unique(org, key)) · `EmailTemplate` model (morph alias `email_template`).
- `EmailTemplateCatalog` — the 5 templates' DEFAULTS (= the #83 strings, `{merge}` placeholders) + real
  trigger/channel/blocks/vars. `EmailTemplateResolver` (effective = stored ?? default, `enabled()`,
  scope-safe read for queued Mailables) → `ResolvedEmailTemplate` (merged subject/heading/message/button +
  `block()`). `EmailTemplateService` (whitelisted upsert + audited `email_template.updated`/`enabled`/`disabled`).
- Threaded into ALL 5 Mailables (`envelope()` = resolved subject; `content()` passes `heading`/`intro`/`button`/
  `blocks` — **`intro`, NOT `message`: the latter collides with Blade's reserved `$message`**) + all 5 blades
  render `{{ $heading }}`/`{{ $intro }}`/`{{ $button }}` and wrap each toggleable block in `@if ($blocks[…])`.
  Defaults render byte-faithfully → **GuestEmailTest 15/15 unchanged**.
- The 3 real triggers (`SendGuestTransactionalEmails` · `SendPreArrivalEmails` · `SendPaymentReminders`) skip a
  send when `EmailTemplateResolver::enabled()` is false → **OFF genuinely stops the email**.

**The surfaces**
- LIST (`/app/email-templates`) — the 5 real rows (status/name/trigger/channel), a real Active/off toggle POST,
  Preview, Edit. Honest footer: no open/click tracking → no engagement stats.
- EDITOR (`OperatorEmailEditor`) — live-preview via `wire:model.live` rendering the REAL `emails.{key}` blade +
  layout (`EmailPreviewSample` → real sample data, draft overlaid → preview == send, cannot drift) + insert
  chips + block toggles + SAVE (native form POST, `can:properties.manage`, audited) + a real Test-send.

**Guards** — every write is a controller POST gated `can:properties.manage` (#30, same tier as Settings — a
Hotel Manager correctly sees a view-only list, exactly like Settings; the control is hidden so no 403 invite)
→ `EmailTemplateService` (audited #20); tenant-scoped via `TenantContext` (never a request id); the key is
whitelisted against the catalog (foreign key 404s). Honesty (#39): no fabricated open-rate; the signature block
is the honest property/team line, never an invented named person; verify-email honours its org too.

**Proof** — `OperatorEmailTemplatesTest` (9): saved heading replaces the default in the REAL render · merge var
resolves in the real subject · a disabled block is absent from the real render · OFF does not send on the real
checkout trigger (ON does) · guarded+audited controller save · Front-Desk save 403 · toggle flips `enabled` ·
list+editor render · unknown key 404. **Browser-verified** (as a Property Owner): live preview updated on edit,
tap-a-star block vanished on toggle-off, Save persisted ("last edited by …"), and the **independent** email
preview (the real Mailable) rendered the saved heading + omitted the block. Suite green · PHPStan L5 (0) · Pint
clean · `migrate` clean; the verification's temp user + demo template row were removed (demo back to defaults).
