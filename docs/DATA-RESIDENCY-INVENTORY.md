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
| **Application logs** | request and error logs | ⚠️ **the host — UNDECIDED** | ⚠️ Guest contact values are deliberately kept out of `audit_events` and `domain_events` payloads (which record *which* fields changed, never their values). **I have not audited every log line for incidental PII — UNKNOWN.** |
| **Redis** | queued jobs and cache | ⚠️ **the host — UNDECIDED** | Job payloads can contain identifiers; queues are transient. |

## 2. Sub-processors — third parties that receive data

| sub-processor | what reaches them | purpose | ⚠️ residency |
|---|---|---|---|
| **Stripe** | ⚠️ **cardholder data directly** (never through Vyrobel's servers — hosted Checkout, SAQ A), amounts, currency, booking reference, and the operator's own business/identity data during onboarding | taking card payments; operator account verification | Stripe's own infrastructure, per Stripe's DPA. ⚠️ **Each operator's connected account is established in a country that cannot be changed after creation.** |
| **Postmark** | guest and staff **email addresses** and the message bodies of transactional email (booking confirmations, invites, resets) | transactional email | Postmark's infrastructure. ⚠️ Region is a Postmark account setting — **UNDECIDED here.** |
| **Anthropic** (`ANTHROPIC_API_KEY`) | ⚠️ whatever the grounded support assistant is given | the "Ask Vyrobel" support assistant | ⚠️ **I have not traced exactly what content is sent — UNKNOWN, and it must be established before the DPA is signed.** |
| **Channex** | would receive availability, rates and reservations | channel sync | ⚠️ **NOT ENABLED.** No data flows today. |
| **AWS S3** | backups, if configured | offsite backup | ⚠️ **Not configured — UNDECIDED.** Bucket region would become a residency fact. |
| **Slack** | operational notifications, if configured | internal alerting | ⚠️ Configured optionally; **not verified as in use.** |

⚠️ **No error-tracking service (Sentry or similar) is configured.** Errors go to the application log only.

## 3. Retention

| data | retention | mechanism |
|---|---|---|
| Guest identity documents | **30 days after check-out**, then hard-deleted (file + row) | `guest-documents:purge-expired` |
| Guests marked for deletion | **14 days**, then **anonymised** (PII replaced, `anonymized_at` stamped) — ⚠️ **rows are kept**, so accounts and occupancy history survive | `guests:process-deletions` |
| Login / security activity | **90 days** | `platform:prune-activity` |

⚠️⚠️ **ALL THREE DEPEND ON THE `schedule:run` CRON.** Without it they fail **silently** — no error, no failed
job. See `docs/GO-LIVE-REQUIREMENTS.md` B5.

⚠️ **These windows are fixed in code, not configurable per tenant.** Statutory guest-registration retention
differs by jurisdiction, so a controller in a country requiring a longer or shorter period cannot currently
be accommodated. **This is a known limit, not an oversight.**

## 4. Data-subject rights — the honest position

- **Erasure and export are performed by the OPERATOR** from the guest record in the back office.
- ⚠️ **A guest cannot self-serve either.** There is no guest-facing export or erasure route. **Recorded as a
  gap, not a capability.**

## 5. What is NOT decided, and must be before the DPA is signed

1. ⚠️ **Hosting region** — nothing is provisioned; no claim is made here.
2. ⚠️ **Backup location** — local vs S3, and if S3, which region.
3. ⚠️ **Postmark region**.
4. ⚠️ **What the support assistant sends to Anthropic** — UNKNOWN, must be traced.
5. ⚠️ **Whether any log line carries incidental guest PII** — UNKNOWN, not audited.
6. ⚠️ **Per-jurisdiction retention** — currently one fixed set of windows.
