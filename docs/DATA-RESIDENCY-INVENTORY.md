# Data residency and sub-processor inventory (2026-08-26)

⚠️ **Written to be handed to a lawyer directly.** It is the list an Article 28 data-processing agreement
asks for.

⚠️⚠️ **STATE OF PLAY, STATED FIRST: THERE IS NO DEPLOYMENT.** Vyrobel has never run outside development and
CI. **No hosting region is committed, and this document makes no claim about where data will live.** The
founder has expressed an intention to host in the EU; ⚠️ **that becomes a fact at deploy and not before, so
it is deliberately not written here as one.** Every "location" below is therefore *"wherever the founder
provisions it"* unless the processor fixes it.

## Vyrobel's role

Vyrobel is the **processor**; the operator (the hotel or property manager) is the **controller** for their
guests' data. Guests' personal data is processed on the operator's instruction.

## 1. Data Vyrobel itself holds

| store | what is in it | where it lives | ⚠️ notes |
|---|---|---|---|
| **MySQL database** | reservations, guests (name, **encrypted** email and phone), folios, payments, owner ledger, staff accounts, audit and domain events | ⚠️ **the host the founder provisions — UNDECIDED** | Guest `email`/`phone` are encrypted casts, keyed by `APP_KEY`. |
| ⚠️ **Guest identity documents** | passport / ID images captured at online check-in | ⚠️ **the application server's own local disk**: `storage/app/private` (`config/filesystems.php` `local`, root `storage_path('app/private')`) | ⚠️⚠️ **THE MOST SENSITIVE DATA IN THE SYSTEM.** Written **encrypted at rest** — `GuestIdDocumentService` encrypts the bytes before writing, so the blob on disk is ciphertext keyed by `APP_KEY`. **Never on the `public` disk and never URL-addressable.** Purged 30 days after check-out by `guest-documents:purge-expired`. ⚠️ **That purge only runs if the `schedule:run` cron is installed.** |
| **Backups** | database + media, encrypted with `BACKUP_PASSPHRASE` | ⚠️ **local to the host unless an S3 target is configured — UNDECIDED** | `ops/backup.sh`. If S3 is used, the bucket's region becomes a residency fact. |
| **Application logs** | request and error logs | ⚠️ **the host — UNDECIDED**, but **on the app server's own disk**: `storage/logs/laravel-YYYY-MM-DD.log` (`daily` driver, `LOG_STACK=daily`), level `warning` in production, **14 files retained** (`LOG_DAILY_DAYS`) | ✅ **AUDITED 2026-08-26 (was UNKNOWN).** 22 `Log::` sites across 16 files. Every deliberate context passes **identifiers and class names only** — no guest name, email, phone, IBAN or document value. The `audit_events` / `domain_events` claim was **re-verified, not restated**: across **202** audit write sites, no `before`/`after` payload names an email, phone, IBAN or document. ⚠️ **One real leak was found and FIXED:** a logged *exception message* carried an address that no logging statement named — `QueryException` interpolates its **bindings** into its own message (`Str::replaceArray('?', $bindings, $sql)`, verified in Laravel 13's vendor source), so a failing insert of a login-activity row reported the login **email** in the clear. Now scrubbed at the five affected sites by `App\Support\Log\LogSafe` (email addresses only; the SQLSTATE and provider error text are kept). ⚠️ **Known limit:** an **IP address** or user-agent bound into the same statement still survives — deliberately not redacted, since broad redaction would remove what a debugger needs. ⚠️ **`MAIL_MAILER=log` writes whole email bodies, including guest names and booking details, into this same file** — that is the dev/staging default; production uses Postmark. |
| **Redis** | queued jobs and cache | ⚠️ **the host — UNDECIDED** | ✅ **Checked 2026-08-26.** `QUEUE_CONNECTION=redis`. A live payload exists only until the worker runs it (normally seconds). ⚠️ **It is NOT purely in memory:** RDB snapshotting is **on** in the verified local configuration (`save 3600 1 300 100 60 10000`; `appendonly no`), so a payload in flight at snapshot time **is written to `dump.rdb` on the host's disk**. The retained-snapshot question therefore belongs to whoever provisions Redis in production — ⚠️ **UNDECIDED**. |
| ⚠️⚠️ **`failed_jobs` (MySQL)** | ⚠️ **the FULL serialised payload of every job that exhausted its retries**, plus the exception | ⚠️ **the host — UNDECIDED** (same database) | ⚠️⚠️ **NEWLY IDENTIFIED 2026-08-26 — this store was not previously listed, and NOTHING PRUNES IT.** Driver `database-uuids`. ✅ **Vyrobel's own queued jobs are clean**: every one holds **id strings only** (`organizationId`, `reservationId`, `syncJobId`) — no Eloquent model, so the "serialised model carrying decrypted attributes" risk does **not** arise here. ⚠️⚠️ **Queued MAILABLES are the exception, and were verified by serialising one:** a failed `OwnerReportMail` persists the recipient's **email address**, their **name**, and **the entire statement PDF** — including its financial contents — because the mailable holds `public readonly string $pdfBytes`. A failed `LoginApprovalMail` persists an email address, **IP**, device string and a **signed login-approval URL**. ⚠️ **Retention today is indefinite: there is no `queue:prune-failed` schedule.** A window is a **founder decision**, not a default — see §5. |

## 2. Sub-processors — third parties that receive data

| sub-processor | what reaches them | purpose | ⚠️ residency |
|---|---|---|---|
| **Stripe** | ⚠️ **cardholder data directly** (never through Vyrobel's servers — hosted Checkout, SAQ A), amounts, currency, booking reference, and the operator's own business/identity data during onboarding | taking card payments; operator account verification | Stripe's own infrastructure, per Stripe's DPA. ⚠️ **Each operator's connected account is established in a country that cannot be changed after creation.** |
| **Postmark** | guest and staff **email addresses** and the message bodies of transactional email (booking confirmations, invites, resets) | transactional email | Postmark's infrastructure. ⚠️ Region is a Postmark account setting — **UNDECIDED here.** |
| **Anthropic** (`ANTHROPIC_API_KEY`) | ✅ **TRACED FIELD BY FIELD 2026-08-26 (was UNKNOWN). Exactly three things leave, and nothing else:** ① a **static** system prompt holding no data; ② the owner's **typed question, verbatim** (`required|string|max:500`, only trimmed); ③ **1–3 of 17 hard-coded help articles** compiled into the source. ⚠️ **NO guest name, email, phone, identity document, booking reference or folio figure reaches Anthropic; no staff identity; no operator business data — and not even the owner's own id** (that is passed to a *local* token counter, never into the payload). | the "Ask Vyrobel" support assistant | ⚠️ **Anthropic's infrastructure — region per Anthropic's terms, NOT established here.** ✅ **Cross-tenant exposure is structurally impossible:** the payload contains **nothing tenant-derived at all** — the knowledge base is a constant array identical for every operator, and the whole code path (`AskVyrobelService`, `HelpRetriever`, `HelpKnowledgeBase`, `Prompts`) touches **no model, no database, no authenticated user and no request**. Reachable from **one** route only (`POST /owner/help/ask`, owner realm, throttled 15/min); guests and the public cannot reach it. ⚠️ **THE RESIDUAL RISK IS THE FREE-TEXT BOX:** an owner may type anything into those 500 characters — including a guest's name or their own bank details — and **nothing redacts it before it is sent.** ⚠️ **Anthropic's retention and model-training terms are NOT established here** — they are commercial terms, not visible in the code, and **must be read before the DPA is signed.** ⚠️ **Inactive by default:** `LLM_FAKE` defaults to true and `LlmSafetyGuard` forces the fake client outside `production`, so **no data leaves at all in development, CI or demo**; the flow becomes live only when a real key is set in production. |
| **Channex** | would receive availability, rates and reservations | channel sync | ⚠️ **NOT ENABLED.** No data flows today. |
| **AWS S3** | backups, if configured | offsite backup | ⚠️ **Not configured — UNDECIDED.** Bucket region would become a residency fact. |
| **Slack** | operational notifications, if configured | internal alerting | ⚠️ Configured optionally; **not verified as in use.** |

⚠️ **No error-tracking service (Sentry or similar) is configured.** Errors go to the application log only.

## 3. Retention

| data | retention | mechanism |
|---|---|---|
| ⚠️ **`failed_jobs` payloads** — ⚠️ **CONTAINS PERSONAL DATA**: a failed queued mailable is serialised whole, so a row can hold a **recipient email address** and, for `OwnerReportMail`, **an owner's entire statement PDF** | **7 days** | `retention:prune` — ⚠️ **a PRIVACY window, not a debugging one.** Seven days is what a debugger plausibly needs; the payload is why it is not longer. (Removing the PDF from the payload is tracked separately as audit M-7.) |
| Application log files | **14 rotations** (`LOG_DAILY_DAYS=14`, `daily` driver) | log rotation, on the host |
| Guest identity documents | **30 days after check-out**, then hard-deleted (file + row) | `guest-documents:purge-expired` |
| Guests marked for deletion | **14 days**, then **anonymised** (PII replaced, `anonymized_at` stamped) — ⚠️ **rows are kept**, so accounts and occupancy history survive | `guests:process-deletions` |
| Login / security activity | **90 days** | `platform:prune-activity` |
| In-app notifications | **90 days** | `retention:prune` |
| Sessions — ⚠️ contains **IP address and user agent** | **30 days** | `retention:prune` (the configured session lifetime is 120 **minutes**, so this can never end a live session) |
| Provider webhook payloads (`webhook_events`) — may contain customer identifiers | **90 days** | `retention:prune` — ⚠️ this table also **deduplicates replays**; 90 days deliberately exceeds Stripe's ~3-day retry window by a wide margin |
| Export download records | **90 days** | `retention:prune` |
| AI-assistant usage accounting (token counts; **no message bodies**) | **90 days** | `retention:prune` |

### ⚠️⚠️ KEPT INDEFINITELY — and a lawyer must be told, because it is not a deletion

| data | retention | why |
|---|---|---|
| `audit_events` — who changed what, when; includes **the actor's IP address and user agent on every row** | ⚠️ **INDEFINITE, by decision** | It is the **evidence** by which a disputed owner money figure is reconstructed, and a dispute surfaces late — often after the statement is paid and the period sealed. A window sized for "logs" would delete exactly the rows a dispute needs. Its size problem is solved by an index, not by deletion. |
| `domain_events` — the business-event outbox (event type, subject, payload) | ⚠️ **INDEFINITE, by decision** | Same reason. Payloads record **which** fields changed, not their values, and carry no contact details. |

⚠️ **OPEN, AND NOT DECIDED IN CODE:** `audit_events` stores an **IP address and user agent on every row**, and
this product already treats that data class as security-monitoring signal with a **90-day** limit elsewhere
(`login_activity`, pruned by `platform:prune-activity`). Keeping the audit **row** indefinitely is defensible;
keeping those two **columns** indefinitely is a separate question. Nulling them past a window would preserve
the evidence while dropping the personal data. **This needs a founder/lawyer decision — see §5.**

⚠️⚠️ **EVERY WINDOW ABOVE DEPENDS ON THE `schedule:run` CRON.** Without it they fail **silently** — no error,
no failed job. See `docs/GO-LIVE-REQUIREMENTS.md` B5.

⚠️ **These windows are fixed in code, not configurable per tenant.** Statutory guest-registration retention
differs by jurisdiction, so a controller in a country requiring a longer or shorter period cannot currently
be accommodated. **This is a known limit, not an oversight.**

## 4. Data-subject rights — the honest position

- **Erasure and export are performed by the OPERATOR** from the guest record in the back office.
- ⚠️⚠️ **CORRECTED 2026-08-26 — this line was half wrong, and it understated the product.** It previously
  read *"there is no guest-facing export or erasure route"*. The **erasure** half is FALSE: a guest **can**
  delete their own account without contacting the operator — `POST /guest/account/delete` →
  `GuestAccountController::destroy` → `GuestDeletionService`, with an **undo** and a grace period before
  anything is removed. ⚠️ The **export** half stands: there is **no** guest-facing data export. A guest can
  download their own booking's documents (receipt, VAT invoice, statement, arrival guide), which is not a
  subject-access export. ⚠️ **An inventory written for a lawyer that understates a capability is a defect in
  the same way one that overstates it is** — the error was found by checking a support page against this
  document and discovering they disagreed.

## 5. What is NOT decided, and must be before the DPA is signed

⚠️ **NEW (audit M-6, 2026-08-30) — the `audit_events` IP / user-agent columns.** Every audit row stores the
actor's IP address and user agent, and the audit table is kept **indefinitely** because it is the evidence for
reconstructing a disputed owner money figure. The same data class is pruned at **90 days** in `login_activity`
as a limited-retention security signal, so the product currently answers the same question two ways. Options:
ratify indefinite retention for the audit trail, or null those two columns past a window (which keeps the
evidence and drops the personal data). **Not decided in code; nothing was changed.**

1. ⚠️ **Hosting region** — nothing is provisioned; no claim is made here.
2. ⚠️ **Backup location** — local vs S3, and if S3, which region.
3. ⚠️ **Postmark region**.
4. ✅ **What the support assistant sends to Anthropic — SETTLED 2026-08-26**, traced field by field (§2). ⚠️ **What remains open is NOT what we send, but what Anthropic does with it:** their **retention and model-training terms are commercial terms, not readable from the code, and must be read before signing.**
5. ✅ **Whether any log line carries incidental guest PII — AUDITED 2026-08-26** (§1). One real leak found and fixed. ⚠️ **What remains open is a deliberate limit, not a gap in the audit:** a logged exception message can still contain an **IP address** or user-agent, which are personal data under GDPR. Redacting them was rejected as removing what a debugger needs — ⚠️ **that is a decision to ratify or reverse, not an oversight.**
6. ⚠️ **Per-jurisdiction retention** — currently one fixed set of windows.
7. ⚠️⚠️ **NEW — a retention window for `failed_jobs`.** It is unbounded today and can hold an email address and a full statement PDF (§1). ⚠️ **Deliberately NOT chosen here:** the other three windows are fixed in code and already recorded as a limitation; **a fourth is a decision for the founder**, and the right answer depends on how long a failed job must stay diagnosable.
8. ⚠️⚠️ **NEW — whether `OwnerReportMail` should keep carrying the PDF in its payload.** It holds the rendered statement as a constructor property, so the document itself is queued and, on failure, persisted. ⚠️ **Not changed in this pass:** re-rendering inside the worker means re-establishing owner and tenant context there, which is a design change on a document path, not a redaction.
9. ⚠️⚠️ **NEW — the free-text support box.** An owner can type any personal data into the 500-character question and it is sent to Anthropic verbatim (§2). ⚠️ **Whether to warn, redact or accept this is a product decision.**
10. ⚠️ **NEW — Redis snapshot retention on the production host** (§1): RDB snapshotting was on where verified, so queue payloads reach disk.
