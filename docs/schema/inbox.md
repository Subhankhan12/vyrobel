# Unified Inbox module — schema & interface design (Step 0 draft, for approval)

One place for operators to see and reply to guest messages across channels, with threads linked to the
real `guests` + `reservations`. Built with the **interface + fake + real** pattern proven on Channex/Stripe:
the **internal core + a manual/fake channel** works end-to-end and is demoable NOW; the **real external
adapters (email / WhatsApp / OTA)** are config-gated skeletons that **throw "not configured"** — deferred,
account-gated, never faked as working (exactly like `StripeConnectPayoutRail`).

## Audit — what already exists (reuse/respect, do NOT duplicate)

| Concern | Already exists | How the inbox reuses it |
|---|---|---|
| Adapter seam (interface + fake + real, config-gated) | `ChannelManagerClient` interface → `FakeChannexClient` / `ChannexClient`, bound in `AppServiceProvider` via `services.channex.fake`; `PayoutRail` → `ManualPayoutRail` / `StripeConnectPayoutRail` (throws) | `MessageChannel` interface → `ManualChannel` (real, works now) + `Email/WhatsApp/BookingCom/Airbnb` **skeletons that throw** "not configured". A `MessageChannelResolver` picks the adapter per thread's `channel` (config-gated per channel). |
| Reliability backbone | `RetryableSyncJob` (`$tries=5`, exp `backoff()`, `forOrganization`, atomic row-claim, idempotent no-op) + `SyncJobDispatcher::dispatch(type,direction,idempotencyKey,jobClass,payloadSummary,channelId)` | Inbound message processing is a `ProcessInboundMessageJob extends RetryableSyncJob` — queued, retried, backed-off, dead-lettered, idempotent. |
| Inbound idempotency (exactly-once over at-least-once) | `webhook_events unique(source, external_event_id)` + `createOrFirst` catch-the-race in `ChannexWebhookController` | `messages unique(organization_id, channel, external_message_id)` + `createOrFirst` — a duplicate inbound (same external id) collapses to one row, race-safe. |
| Guest identity matching | `BookingImportService::resolveGuest` — `Guest::where('external_refs->{channel_id}', $otaGuestId)->first()` else `Guest::create([... 'external_refs' => ...])` | Inbound resolves the guest by channel identity (email/phone/handle) via `external_refs` (else creates + links) — the SAME pattern, no parallel matcher. |
| Guest PII | `guests.email`/`phone` **encrypted casts**; `external_refs` (array); GDPR `anonymize()` | The guest's channel address (email/phone/handle) is stored **encrypted** on the thread (`participant_identity`), never plaintext. The operator surface shows the guest **name + conversation** (front-desk PII precedent) — never the encrypted contact. Message bodies are redacted on `anonymize()` (GDPR hook, below). |
| Reservation / guest context | `reservations` (`reference`, `lead_guest_id`, `guest()`); `guests` | A thread optionally links `reservation_id` + `guest_id` so the operator sees booking context beside the conversation. No new guest/reservation tables. |
| Domain events + one-directional consumers | `DomainEventRecorded` + audit; consumers filter by type and never re-emit | `InboxService` records `message.received` / `message.sent` / `thread.*` domain + audit events. **One-directional** — no consumer re-triggers messaging. |
| Tenant scoping | `BelongsToOrganization` (global scope + auto-fill + guard) | Both new tables carry `organization_id` via the trait — cross-org resolution impossible. |
| Enforced morph map | `Relation::enforceMorphMap` (unmapped morph → throws) | `message_thread` + `message` registered. |
| Operator design system | `<x-operator.shell>` + `FormatsOperatorData` + POST→controller→service→PRG | The (later, deferred) styled inbox uses it; Step 3 ships an **unstyled** contract stub whose reply box posts a **real** outbound via `InboxService`. |

**Naming guard (important):** the existing **`channels` table** is an *OTA distribution connection* (Channex/
Booking.com/Airbnb property links). The inbox's **"channel"** is a *communication medium* (manual/email/
whatsapp/…). These are DIFFERENT concepts — the inbox channel is an **enum string column**, NEVER a FK to the
`channels` table, and the `MessageChannel` PHP interface is unrelated to the `Channel` model. No clash, no reuse.

**Conclusion:** TWO new tables (`message_threads`, `messages`) + a `MessageChannel` adapter family. Everything
else — the backbone, idempotency discipline, guest matching, PII encryption, events, tenancy, design system —
is reuse.

## New table — `message_threads`

ULID PK, `BelongsToOrganization`, `HasUlids`, `SoftDeletes`. Morph `message_thread`.

| Column | Type | Notes |
|---|---|---|
| `id` | ulid PK | |
| `organization_id` | foreignUlid → organizations | tenant scope |
| `channel` | enum(`manual`,`email`,`whatsapp`,`booking_com`,`airbnb`,`sms`) | communication medium — **enum, not a FK**. `manual` works now; the rest are deferred adapters. |
| `external_thread_ref` | string, nullable | the channel's conversation id (email thread, WhatsApp conversation, OTA thread) — the threading key for inbound grouping; null for manual. |
| `subject` | string, nullable | e.g. email subject. |
| `guest_id` | foreignUlid → guests, nullable | matched/created guest (Step 2). |
| `reservation_id` | foreignUlid → reservations, nullable | optional booking context (Step 2). |
| `participant_identity` | text, **encrypted cast**, nullable | the guest's address on this channel (email/phone/OTA handle) used to route outbound. Encrypted at rest (guest-PII discipline) — never rendered on the operator surface. |
| `status` | enum(`open`,`snoozed`,`closed`) default `open` | guarded lifecycle. |
| `assigned_to` | foreignUlid → users, nullable | operator handling it. |
| `unread_count` | unsignedInteger default 0 | inbound since last operator read. |
| `last_message_at` | timestamp, nullable | sort key. |
| `last_inbound_at` | timestamp, nullable | |
| `snoozed_until` | timestamp, nullable | |
| `meta` | json, nullable | non-PII channel metadata (display name, provider flags). |
| timestamps + softDeletes | | |

Indexes: index `(organization_id, status, last_message_at)`; **`unique(organization_id, channel,
external_thread_ref)`** (MySQL allows multiple NULLs → manual threads don't collide) — the threading anchor so
inbound groups into the right thread instead of spawning a new one each time.

## New table — `messages`

ULID PK, `BelongsToOrganization`, `HasUlids`, `SoftDeletes`. Morph `message`.

| Column | Type | Notes |
|---|---|---|
| `id` | ulid PK | |
| `organization_id` | foreignUlid → organizations | tenant scope |
| `thread_id` | foreignUlid → message_threads (cascade) | |
| `direction` | enum(`inbound`,`outbound`) | |
| `channel` | enum(as above) | denormalized from the thread (the medium this message travelled). |
| `external_message_id` | string, nullable | the provider's message id — **INBOUND idempotency anchor**. |
| `body` | text | message content. Guest-authored content is PII-adjacent → redacted on guest `anonymize()` (hook below). |
| `author_user_id` | foreignUlid → users, nullable | staff author of an outbound reply; null for inbound. |
| `sender_label` | string, nullable | non-PII display label (guest **name** or "You") — NOT the encrypted contact. |
| `delivery_status` | enum(`received`,`queued`,`sent`,`delivered`,`failed`) | inbound = `received`; outbound lifecycle `queued → sent → delivered` / `failed`. |
| `delivery_error` | string, nullable | outbound failure reason (from the adapter). |
| `sent_at` / `received_at` / `failed_at` | timestamp, nullable | per direction/outcome. |
| timestamps + softDeletes | | |

**`unique(organization_id, channel, external_message_id)`** — inbound exactly-once (createOrFirst catch-the-
race). `external_message_id` is null for outbound/manual → multiple NULLs allowed.

## `MessageChannel` interface + adapters (the interface + fake + real seam)

Namespace `App\Messaging` (mirrors `App\Payouts`):

```php
// App\Messaging\Contracts\MessageChannel
interface MessageChannel
{
    public function name(): string;                              // 'manual'|'email'|'whatsapp'|'booking_com'|'airbnb'|'sms'
    public function send(Message $message, ?string $to): MessageDeliveryResult;   // outbound; returns external id + status, or throws
    public function normalizeInbound(array $payload): InboundMessage;             // raw provider payload → normalized DTO
}
```

- **`ManualChannel` (REAL — works now):** `send()` records the outbound as `sent`→`delivered` with a
  generated internal message id (no external dependency); `normalizeInbound()` accepts an injected inbound
  payload (used by the demo + a test-only "inject inbound" affordance). This is the fully-working channel.
- **`EmailChannel` / `WhatsAppChannel` / `BookingComChannel` / `AirbnbChannel` (DEFERRED skeletons):** both
  methods **throw `RuntimeException("… not configured — <what it needs>")`** (like `StripeConnectPayoutRail`).
  Config-gated (`services.inbox.channels.*`), never active in tests, never faked as working.
- **`MessageChannelResolver::resolve(string $channel): MessageChannel`** — factory keyed by the thread's
  `channel` enum; returns `ManualChannel` for `manual`, else the skeleton (which throws on use). DTOs:
  `MessageDeliveryResult` (ok + external id / failure reason) and `InboundMessage` (normalized inbound).

## `InboxService` (mirrors the existing service pattern)

- `startThread(array): MessageThread` — open a thread (channel + optional guest/reservation + subject).
- `postInbound(array): Message` — **idempotent** (createOrFirst on `external_message_id`): resolve/attach the
  thread (by `channel` + `external_thread_ref` / participant), resolve/link the guest (Step 2), create the
  inbound message, bump `unread_count` + `last_*_at`, reopen if closed. Records `message.received`.
- `sendOutbound(MessageThread, string $body, ?User): Message` — create the outbound (`queued`) →
  `resolver->resolve(channel)->send()` → update `delivery_status`; records `message.sent`. Guarded (can't
  send on a channel whose adapter throws — the skeleton surfaces "not configured" cleanly).
- `assign / close / snooze / reopen` — guarded thread lifecycle (illegal transitions throw, surfaced as clean
  flashes in the UI later).

**Inbound on the backbone:** `ProcessInboundMessageJob extends RetryableSyncJob` calls `postInbound` — queued,
retried, dead-lettered, idempotent (duplicate external id → single message, race-safe). The manual channel's
"inject inbound" rides this job so the backbone is exercised now; real webhooks (deferred) will record via
`webhook_events` (source enum extended) + `SyncJobDispatcher`, identical to the Channex leg.

## GDPR / PII handling (respect convention #5)

- The guest's channel address is stored **encrypted** (`message_threads.participant_identity`), never
  plaintext, and never rendered on the operator surface — the surface shows the guest **name** + the
  conversation only (front-desk precedent).
- `Guest::anonymize()` gains a hook to **redact this org's message bodies + participant identities** for the
  guest (strip content, keep the thread skeleton for audit) — NEVER soft-delete-and-keep-PII. (Wired in Step 2
  when linking lands; flagged here so it isn't forgotten.)

## Deferral ledger (honest — account-gated, NOT faked)

| Channel | Status | What unblocks it |
|---|---|---|
| `manual` | **REAL, works now** | — |
| `email` | deferred skeleton (throws) | an inbound-email provider (Postmark/Mailgun inbound parse) + a verified sending domain |
| `whatsapp` | deferred skeleton (throws) | WhatsApp Business API approval + a BSP (Twilio/Meta) number |
| `booking_com` / `airbnb` | deferred skeleton (throws) | OTA messaging API access (via Channex messaging or the OTA partner API) |
| **Styled inbox UI** | deferred | a design pass (Step 3 ships an unstyled contract stub, consistent with the original Phase-1 stubs) |

## Build order (gated)

- **Step 1** — the two tables + models + `MessageChannel` interface + `ManualChannel` + skeletons +
  `InboxService` + the inbound job on the backbone (idempotency + catch-the-race proven).
- **Step 2** — reservation/guest linking + threading logic + the GDPR redaction hook.
- **Step 3** — unstyled operator stub (list + thread view + a real reply POST) + full integrity suite.
- **Step 4** — demo seed (via InboxService) + docs consolidation + push.
