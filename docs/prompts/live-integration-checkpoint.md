# Vyrobel — Live-Integration Checkpoint · Claude Code Prompt

**What this is:** the credentialed session that closes Phase 2's two outstanding live checkpoints — validating the config-gated **real `ChannexClient`** (Channex staging) and **real `StripeClient`** (Stripe test-mode) against actual accounts, then reconciling any contract differences back into the fakes so the CI suite reflects reality. Validation + reconciliation only — no new features.

**Before you paste — have these ready (put all secrets in `.env`, which is gitignored):**
1. **Channex:** a staging/sandbox account + API key + webhook signing secret, and at least one mapped test property/room-type. (If Channex onboarding isn't done yet, you can still run the Stripe half — the prompt handles that.)
2. **Stripe:** account in **TEST mode** + test secret key + webhook signing secret. Install the **Stripe CLI** and run `stripe login` (needed to forward real test webhooks to localhost).
3. **For Channex inbound webhooks:** a tunnel (e.g. `ngrok http 8000` or `cloudflared`) pointing at your local app, with the tunnel URL registered as the Channex webhook endpoint. (Outbound ARI push needs no tunnel.)
4. Have the app running (`php artisan serve`) and **Horizon running** (`php artisan horizon`) for the live legs.

Paste everything below the line.

═══════════════════ PASTE BELOW THIS LINE ═══════════════════

# ROLE & MISSION
This is the **live-integration checkpoint** for Vyrobel Phase 2. Validate the config-gated **real `ChannexClient`** against Channex staging and the **real `StripeClient`** against Stripe test-mode, using real accounts, then **reconcile every difference between the real contract and the in-memory fakes** so the CI suite reflects reality. This is validation + reconciliation, **not** new features or tables. Read `docs/PROJECT-STATE.md` and `docs/DECISIONS.md` (the two outstanding live checkpoints) first. Work in order, **STOP at every gate**.

# SECRETS — CRITICAL
- All credentials live in `.env` ONLY (Channex API key + webhook secret; Stripe test secret key + webhook signing secret). Confirm `.env` is gitignored before doing anything.
- **NEVER commit secrets. NEVER print full secret values** in any report — mask them (`sk_test_…a1b2`). If a secret would ever land in code, a fixture, a test, or a committed file, STOP.
- Do not enter credentials into any third-party web field on my behalf — they go in `.env`, which I control.

# HONESTY RULES (apply with extra force here)
- **Never mark a checkpoint closed unless it was actually exercised against the real system.** "Proven against fakes" is not "validated live."
- If an account/tunnel/credential wall blocks a leg, **STOP and report exactly what's needed** — mark that leg still-outstanding. Partial-but-honest beats fake-complete.
- **Live calls stay OUT of the CI suite.** Real-account checks are a separate tagged group (e.g. `@group live` / a `Live` suite) or one-off artisan commands, **excluded from the default `php artisan test`** — which must remain green using fakes with no network. Never make CI depend on network or credentials.
- **Reconcile, don't paper over:** when the real contract differs from a fake, fix the **real client** to match reality AND update the **fake** (payload shape / signature / endpoint) so the suite reflects the true contract. Document every difference found.

# RULES OF ENGAGEMENT
- Audit first; STOP at every ⛔ gate; per-gate commit (no secrets) at each close-out; update `DECISIONS.md` + `PROJECT-STATE.md`.
- Do not touch the Phase 1/2 guarantee logic (AssignmentService locking, ledger, single write path) — this checkpoint validates the external edges, not the core.

## ⛔ STEP 0 — Audit & plan (GATE)
1. Report, for each external client, the **config flag** that switches fake↔real, and where the real client lives (`app/Services/Channex/`, `app/Payments/`).
2. Report what the **fakes currently ASSUME** (this is the "documented guess" surface being validated): Channex ARI endpoint + payload shape + auth header; Channex webhook payload shape + HMAC scheme; Stripe intent/authorize/capture/refund calls + webhook signature scheme.
3. Confirm `.env` is gitignored and **list exactly which env vars must be set** (names only, never values). Report which are currently present vs missing.
4. Report what's **reachable now vs blocked**: Channex staging key present? Stripe test keys present? Stripe CLI installed + logged in? A tunnel available + Channex webhook URL registered?
5. If Channex staging is **not** provisioned, note it and plan to run Steps 3 (Stripe) independently, leaving Channex outstanding. Propose the plan and flag every wall. **STOP.**

## ⛔ STEP 1 — Channex OUTBOUND (ARI push) live validation (GATE)
- Switch config to the **real `ChannexClient`** (staging), creds from `.env`.
- Exercise a **real ARI push** for a mapped room_type + date range through the existing `AriPushJob` path (via Horizon/Redis). Capture the real request + response. Confirm Channex accepts it (expected 2xx / response body).
- **Reconcile vs `FakeChannexClient`:** document EVERY difference (endpoint, field names, auth header, payload structure, response handling). Fix the real client to match reality; update the fake to mirror the true shape.
- Re-run the fake-based CI suite → must stay green against the corrected contract. **STOP** with the list of differences found + fixed (secrets masked).

## ⛔ STEP 2 — Channex INBOUND (webhook) live validation (GATE)
- Needs a public URL. If a tunnel is up and the Channex webhook endpoint is registered to it, proceed. If NOT, **STOP** and tell me exactly what to set up (tunnel + register URL in Channex), and mark inbound **still-outstanding** — do not fake it.
- Trigger a **real** test booking / modification / cancellation from Channex (or their sandbox tool). Capture the **real webhook payload + signature header**.
- Validate against `ChannexWebhookSignature` (real HMAC) and the import path: does the real payload verify, parse, and import correctly **through `AssignmentService`** (idempotent, mapped, overbooking policy intact)? Document differences; fix the real parser/signature; update the fake payloads + signature to match reality.
- Re-run the fake suite → green. **STOP** with differences + fixes.

## ⛔ STEP 3 — Stripe test-mode live validation (GATE)  *(can run independently of Channex)*
- Switch to the **real `StripeClient`**, test keys from `.env`. Start real webhook delivery with the Stripe CLI: `stripe listen --forward-to <local-url>/webhooks/stripe`. If the Stripe CLI isn't installed/logged in, **STOP** and tell me to set it up.
- Exercise against **real Stripe test-mode**:
  - (a) Payment intent + confirm with a normal test card (`4242 4242 4242 4242`) → succeeds; ledger records the payment via `FolioService`.
  - (b) A **3DS-required** test card (e.g. `4000 0027 6000 3184`) → returns `requires_action`/pending with `client_secret`, **NOT** a false success; a subsequent `payment_intent.succeeded` webhook settles it → paid.
  - (c) Deposit hold **authorize → capture** via `payment_authorizations` (hold ≠ paid until capture).
  - (d) **Refund** → Stripe processes it; our ledger records a **new offsetting entry**, original payment row untouched (append-only preserved).
  - (e) Real **webhooks** (`payment_intent.succeeded`, `charge.refunded`) delivered via Stripe CLI → signature-verified with the real scheme → idempotent under redelivery.
- **Reconcile vs `FakeStripeClient`:** document differences, fix the real client, update the fake to match. Confirm **token-only** (no PAN anywhere). Re-run the fake suite → green. **STOP.**

## ⛔ STEP 4 — Reconcile, certify & document (GATE)
- Confirm the full CI suite (`php artisan test`, fakes, no network) is green and now reflects the **validated real contracts**. State the final test count.
- Update `DECISIONS.md` + `PROJECT-STATE.md`: move whichever checkpoints were **actually exercised live** from "outstanding" to "validated against live" (with the date and exactly what was tested); keep anything blocked as **still-outstanding** with the precise blocker. Record every contract difference found + how it was reconciled.
- Per-gate commit (no secrets; confirm `git status` shows no `.env` or key material staged). **Report:** what was validated live vs still-blocked, differences reconciled, final suite count, and the honest checkpoint status. **STOP** — do not begin the design pass or Phase 3.

═══════════════════ PASTE ABOVE THIS LINE ═══════════════════

### After this checkpoint
Once whichever legs you can reach are validated and the fakes reflect reality, Phase 2 moves from "certified against fakes" to "certified against reality" for those legs. Then the design-vs-Phase-3 fork is a clean decision with no live-contract landmine underneath it — send me the checkpoint report and we'll take that fork deliberately.
