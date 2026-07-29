# Vyrobel — "Ask Vyrobel" Support AI (grounded, guard-railed, cost-controlled) · DESIGN

**Status:** PHASE 0 — investigation + design (read-only). Presented for approval before any build.
**Date:** 2026-07-29
**⚠️ HONESTY:** a support-AI must NEVER fabricate. It answers ONLY from real Vyrobel help content (grounded), escalates honestly to the real message relay when unsure, and never invents a policy, number, commitment, or account-specific data.

---

## 0. ⚠️ A load-bearing correction from the investigation (read this first)

**The prompt says "reuse the existing LlmManager (cost ledger, circuit breaker, versioned prompts, the Anthropic client)." That infrastructure does NOT exist.** A full sweep found **no `LlmManager`, no Anthropic/OpenAI client, no cost ledger, no circuit breaker, no embeddings/RAG, and no LLM SDK in `composer.json`.** The "AI-native" tagline is aspirational; the "Ask Vyrobel" AI was previously **REFUSED as fabrication** (`OwnerHelp.php:18` — *"NO fake AI"*, invariant #39/#66).

So this build **builds** that layer — but **honestly, the way the codebase already handles every account-gated external service** (Stripe, Channex): a **config-gated FAKE client** for dev/CI/demo + a **real client behind a safety guard**, test-mode-first, with the **live provider call deferred to the founder's go-live** (an Anthropic key). Everything *around* the LLM call — the grounding, the guardrails, the retrieval, the cost ledger, the circuit breaker, the Help surface, the escalate relay — is **REAL and fully behaviour-proven with the fake client**; only the actual Anthropic API call is account-gated (exactly like the Stripe live-key flip).

**This is not "fake AI."** The #39 refusal was about *fabricating* AI answers over no backend. Here the grounding + guardrails are real code; the fake client is a deterministic **test double** (like `FakeStripeClient`), and in dev/demo without a key the assistant **degrades honestly** to the retrieved help content + the relay — it never invents a conversation.

---

## 1. The core guarantee — grounding is ARCHITECTURAL, not a hope

The "never fabricate" guarantee does **not** rely on trusting the LLM to behave. It is enforced in **our** control flow:

```
owner asks a question
      │
      ▼
① RETRIEVE over the real Help knowledge base (deterministic token-overlap scoring)
      │
      ├─ no relevant content (score < threshold)  ─────────►  ⛔ ESCALATE (deterministic, NO LLM call)
      │     ← account-specific ("what's my payout?"), out-of-scope, or unknown all land here:
      │       the KB has no answer → we NEVER produce one. Directs to the real Payouts page / the relay.
      │
      ▼ relevant content found
② COST + RATE gate — per-owner/day token cap (the ledger) + a rate limit
      │     ├─ over cap / rate-limited  ─────────────────────►  ⛔ ESCALATE ("busy — message the team")
      ▼
③ LLM (via the config-gated client) with a STRICT grounded prompt:
      "Answer ONLY from the provided Vyrobel help content. If it doesn't answer, say you don't know and
       suggest messaging the team. NEVER invent Vyrobel policy, numbers, or account data."
      │     ├─ client throws / safety-guard blocks / no key  ─►  🔁 CIRCUIT BREAKER → return the retrieved
      │                                                            help content directly + the relay (honest fallback)
      ▼
④ ANSWER = the grounded reply + CITATIONS (the real FAQ/help items used, linking the real owner screens)
      │
      ▼  (always, on every path)
   an "AI assistant — verify important things; message the team for account help" note + a
   persistent ESCALATE-TO-TEAM button (the real owner↔operator relay, owner.messages).
```

**Because step ① gates the LLM, the assistant physically cannot answer without retrieved real content.** Account-specific questions have no generic-help match → they escalate; the assistant is never handed a number to repeat. This is the strongest honesty posture.

---

## 2. What already exists (REUSE — do not rebuild)

| Piece | Where | Role |
|---|---|---|
| The 7-item **FAQ** (keyword · question · answer · route · label) | `resources/views/livewire/owner/help.blade.php:26` | **The real knowledge base** — extracted to ONE shared source |
| The Help page + "Ask Vyrobel" FAQ-search hero | `OwnerHelp` + `help.blade.php` | The surface the real assistant replaces |
| The owner↔operator **message relay** (the escalate target) | `owner.messages` / `owner.messages.start` → `OwnerMessageActionController` → `InboxService::startThread`/`postOwnerMessage` | Where the assistant escalates; always-present |
| The **config-gated fake/real external-service pattern** (`stripe.fake` / `channex.fake` → a Fake vs real client bound in `AppServiceProvider`) | `config/services.php` · `AppServiceProvider` | The template for the LLM client seam |
| **`StripeSafetyGuard::forceFakeOutsideProduction`** (refuses a live key outside production → binds the fake) | `app/Payments/Stripe/StripeSafetyGuard.php` | Mirrored as `LlmSafetyGuard` |
| The outbound-HTTP pattern (`Http` facade, `ChannexClient`) | `app/Services/Channex/ChannexClient.php` | How the real `AnthropicLlmClient` calls the API |
| `RateLimiter` (throttle) | `AppServiceProvider` / route `throttle:` | The per-owner rate limit |

**The KB is the SAME content the FAQ shows** — extracting it to one `HelpKnowledgeBase` means the assistant grounds on exactly what the FAQ already tells owners (anti-drift; a curated, real, owner-facing corpus).

---

## 3. Grounding — the knowledge base + retrieval

- **`App\Support\Help\HelpKnowledgeBase`** — the ONE source: the 7 real FAQ items (id · keywords · question · answer · route · label), each a real help "article". `help.blade.php` renders the FAQ from it (so the page + the assistant can never disagree). Additional entries may be added from the owner screens' real honest copy — **real content only, never invented policy.**
- **`App\Support\Help\HelpRetriever::retrieve(question, k=3): array`** — a **deterministic** relevance score (token overlap / simple TF weighting over the KB's question+answer+keywords). Returns the top-k items **with scores**; below a threshold → **empty** (→ escalate). No embedding API is needed (the corpus is small + curated); this is honest, free, deterministic, and fully testable. (A future embeddings backend can slot behind the same interface if the KB grows.)

---

## 4. The LLM seam (config-gated, account-gated — mirrors Stripe/Channex)

- **`App\Support\Llm\LlmClientInterface::complete(string $system, string $userContent, int $maxTokens): LlmResponse`** — text + token usage. The ONE seam; nothing depends on a concrete provider.
- **`FakeLlmClient`** — deterministic, no network. For **tests** it returns configured responses (a grounded answer / an "I don't know" / a thrown failure) to prove each behaviour; for **dev/demo** it returns an answer **synthesised only from the passed-in retrieved content** (never open knowledge), so the demo is honest.
- **`AnthropicLlmClient`** — the real client (`Http` facade → Anthropic Messages API), account-gated. Versioned, named prompt templates (`App\Support\Llm\Prompts`).
- **`LlmSafetyGuard::forceFakeOutsideProduction($env, $configuredFake, $apiKey)`** — mirrors `StripeSafetyGuard`: a real key outside `production` → the fake is bound (loud log). Config `services.llm` = `{ provider, fake => env('LLM_FAKE', true), api_key => env('ANTHROPIC_API_KEY'), model, max_output_tokens, daily_token_cap }`. Bound in `AppServiceProvider` exactly like `StripeClientInterface`. **Default `LLM_FAKE=true`** → no dev/CI run ever calls a real LLM or spends a cent; the live flip is the founder's documented go-live step.

---

## 5. Guardrails, cost control, circuit breaker

- **No fabrication / grounded:** the LLM is only ever called with retrieved real content + a strict grounded system prompt; empty retrieval → deterministic escalate (§1). The answer always carries **citations** to the real help it used.
- **No account-data invention (#28):** the assistant reads **no owner data at all** — it's a help assistant over generic content. "What's my payout?" has no generic-help answer → it escalates + points to the real Payouts page. It therefore touches no PII (owner-scoped trivially satisfied); the only per-owner state is the usage counter + rate limit.
- **Escalate-when-unsure:** low retrieval score, cost cap, or LLM failure → an honest "I can't answer that confidently — here's how to reach your team" → the real relay. An escalate-to-team button is **always** present.
- **Cost ledger:** a small **`llm_usage`** table (owner_id · date · request_count · token_count) — checked **before** the call (per-owner/day token cap → escalate when exceeded) and recorded **after**. This is "the ledger caps spend."
- **Circuit breaker:** any client exception / safety-guard block / missing key → fall back to the FAQ retrieval result + the relay (the page never breaks; it degrades honestly). A short cooldown avoids hammering a down provider.
- **Rate limit:** `RateLimiter` per owner (e.g. a few asks/minute) on the ask route.
- **The honest note** on every answer: *"AI assistant — verify important things; message the team for account help."*

---

## 6. The Help surface (Phase 3)

The "Ask Vyrobel" hero becomes a real **ask box** → a controller POST (`owner.help.ask`, rate-limited + cost-capped; a read — no owner-data write) → `AskVyrobelService` → a grounded **answer + citations** (links to the real FAQ/owner screens it drew from) + the always-present **escalate-to-team** button (the relay) + the honest note. **The FAQ stays** below (the assistant complements it); suggested questions seed the box. Owner-scoped (the route is in the owner group); reads no PII.

---

## 7. Phase plan (gated; per-gate commit; suite green + PHPStan L5 + Pint throughout)

- **PHASE 1 — the knowledge base + retrieval (grounding).** Extract the FAQ into `HelpKnowledgeBase` (the ONE source; the Help FAQ blade reads it — anti-drift) + `HelpRetriever` (deterministic relevance, top-k with scores, threshold→empty). **Prove:** retrieval returns the relevant real item for an in-scope question; an out-of-scope/account question scores below threshold → empty; the KB is the real content (not invented). Suite green.
- **PHASE 2 — the grounded assistant (LLM seam + guardrails + cost/circuit).** `LlmClientInterface` + `FakeLlmClient` + `AnthropicLlmClient` + `LlmSafetyGuard` + the `services.llm` config (default fake); `llm_usage` cost ledger; the circuit breaker + rate limit; `AskVyrobelService` orchestrating retrieve→gate→LLM→cite→escalate. **Behaviour-prove:** in-scope → a grounded answer + citations (fake client); out-of-scope/account → honest escalate with **no LLM call**; cost cap exceeded → escalate; a failing client → FAQ+relay fallback (circuit breaker); the ledger records + caps. Suite green.
- **PHASE 3 — surface "Ask Vyrobel" + consolidate.** The real ask box + citations + the always-present escalate button + the honest note on the Help page (FAQ retained). Consolidate `SUPPORT-AI-DESIGN.md` (built) + `DECISIONS.md` + `DIVERGENCE-REGISTER.md` (Help "Ask Vyrobel" → real, grounded) + `PROJECT-STATE.md` + the invariant list; bump HEAD. Browser-verify (in-scope → grounded answer + citations; an account question → honest "message the team"; the escalate button → the real relay). Push all. **STOP** with a report.

---

## 8. Standing guarantees held throughout

⚠️ NEVER FABRICATE — grounded (answers ONLY from the real Help KB, never open knowledge; empty retrieval → deterministic escalate); guard-railed (escalate-when-unsure to the REAL relay, always present; never invents policy/numbers/commitments/account data; reads no PII, #28); cost-controlled (the `llm_usage` ledger caps per-owner/day spend) + circuit-breaker fallback to FAQ+relay on failure; rate-limited; a clear "AI assistant — verify important things; message the team for account help" note. **The LLM layer is NET-NEW** (no LlmManager existed) — built the codebase's way: a config-gated fake client + a real client behind `LlmSafetyGuard`, **default fake**, the live Anthropic call the founder's account-gated go-live (like the Stripe live-key flip). Additive — **disturb nothing** (the green suite is the proof); the demo shows the honest grounded/retrieval experience, never a fabricated AI conversation.
