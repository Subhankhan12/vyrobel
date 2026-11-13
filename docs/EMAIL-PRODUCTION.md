# Production email — Hostinger mail (SMTP) · ⚠️ Postmark superseded 2026-09-25

## ⚠️ READ THIS FIRST — MAIL 02 (2026-09-25): THE PROVIDER IS HOSTINGER'S OWN MAIL, NOT POSTMARK

The founder chose Hostinger's own mail over Postmark: he already has live mail on `vyrobel.com` there (MX →
`mx1/mx2.hostinger.com`), so there is no new vendor and no new DNS. Everything below the line is the Postmark history,
kept as a record — **do not follow it.**

**The production settings** (`.env` on the host, shared by every release; the config cache is rebuilt by staging a
release — L14 — so a `.env` edit alone does nothing):

```
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_SCHEME=smtps
MAIL_USERNAME=contact@vyrobel.com
MAIL_PASSWORD=<the mailbox password — typed by the founder, never seen by anyone else>
MAIL_FROM_ADDRESS=contact@vyrobel.com     # MUST equal MAIL_USERNAME — Hostinger rejects mail sent as anyone else
MAIL_FROM_NAME="Vyrobel"
MAIL_SUPPRESS_ADDRESSES=                  # optional: exact addresses measured to have no mailbox
```

**DNS — already in place, measured 2026-09-25, nothing added or changed:** SPF `v=spf1 include:_spf.mail.hostinger.com
~all` (one record) · DKIM `hostingermail-a._domainkey` (a live RSA key; `-b`/`-c` are empty, rotated out) · DMARC
`v=DMARC1; p=none`. MX untouched.

**What protects it (code, tested and mutation-proved — `tests/Feature/MailHostingerSmtpTest.php`, set `mail-02`):**
- `MailSafetyGuard` — `smtp` to a REAL host is real sending, refused outside production; `smtp` to this machine
  (Mailpit) is left for development.
- `SuppressUndeliverableRecipients` — before every send, addresses that can never receive mail (RFC-reserved `.invalid`,
  `.test`, `.example`, `.localhost`, example.com/.net/.org, plus `MAIL_SUPPRESS_ADDRESSES`) are removed; a message with
  nobody real left is cancelled. The probe accounts live on `.invalid` — without this they would bounce against the
  founder's own mailbox.
- The health board's mail card: red for SMTP pointed at this machine, an incomplete login, or a From that is not the
  login; otherwise green with real counts — accepted by the server (24h / 7 days), suppressed, failed on the queue.
  ⚠️ **Bounces are not visible to the app:** with SMTP a bounce comes back as an email to `contact@vyrobel.com`, and
  guests' replies land there too. "Accepted" is not "delivered".

### ⚠️ DEC-1 (his ruling, 2026-11-13) — SEND FROM TWO MAILBOXES (`MAIL_MAILER=rotating`). BUILT; NOT SWITCHED ON.

He ruled: keep Hostinger's own daily limit, and send from **two mailboxes** — `contact@` and `sales@` — so that when the first has
reached its limit the second takes over, and the first is used again when its 24 hours free. (The free-plan end date, 2 Apr 2027,
he will extend himself — Founder said.) Code: `App\Mail\Transport\RotatingMailboxTransport` · `App\Support\Mail\MailboxRotation` ·
tests `tests/Feature/MailboxRotationTest.php` · mutation set `decisions-nov13` (D13-M32…M41).

**How it works.** For every message the first mailbox in `mail.mailers.rotating.mailers` (`smtp`, then `smtp_second`) whose count of
ACCEPTED messages in the last 24 hours (`mail_send_log`, by its `mailbox` label) is below `MAIL_DAILY_LIMIT` is used. Each pick starts again
from the first, so "back to the first" needs no timer. The message is sent AS the chosen mailbox — the From **address** and the envelope
sender are rewritten to its login (Hostinger rejects anything else); the display name, Reply-To and recipients are untouched. If a
mailbox's server REFUSES the message (its limit reached by a person sending by hand, a login problem), the next is tried. If every
mailbox is at its limit or refuses, the send **fails loudly** — nothing is dropped silently. The health board's Mail card shows
"Mailbox 1 — N of LIMIT sent in the last 24 hours" for each (counts only; never an address).

**To switch it on (his, with his own hands — a session never types a secret):**
1. In hPanel, create a real **mailbox** `sales@vyrobel.com` (⚠️ an ALIAS is not enough — an alias has no login and cannot send; the alias
   created 2026-09-26 forwards mail but is not this).
2. On the host, in the shared `.env`, add: `MAIL_SECOND_USERNAME=sales@vyrobel.com` and `MAIL_SECOND_PASSWORD=<that mailbox's password>`
   (the host, port and scheme default to the first mailbox's; `MAIL_SECOND_HOST` / `_PORT` / `_SCHEME` override them), and optionally
   `MAIL_DAILY_LIMIT=100` (Hostinger's cap **per mailbox** — read it in hPanel; it was Reported as 100, and Hostinger's own help pages disagree,
   100 vs 300), then set `MAIL_MAILER=rotating`.
3. `ops/release.sh recache <live release>` (the L14 path — the config cache must be rebuilt for an `.env` change to take effect).
4. Read the health board's Mail card: both mailboxes listed, both at 0 of the limit. ⚠️ **Never switch mail on against an unverified domain, or
   with unreviewed mail in the queue** (`RULES.md` Part 1) — this does not change that; F1 (rotate the exposed `contact@` password) still comes first.

⚠️ **Limits, stated:** the count sees only what the APP sent — a person writing from `contact@` by hand also uses that mailbox's Hostinger
limit and is invisible to it (the refusal fall-through covers it, but the first mailbox can run out earlier than the count says). Bounces
are still invisible to the app. With ONE mailbox configured the rotation uses just that one, with the limit still applied.

---

## Production email (Phase 8) — Postmark · ⚠️ HISTORY, superseded 2026-09-25

Phase 3 built the **entire mail layer** (9 guest transactional Mailables + owner/operator delivery, the
per-channel preference gate, the "a mail failure never rolls back the domain transaction" boundary) on
**Mailpit** for dev. Phase 8 wires **Postmark** as the real **production** transport — a config/ENV swap, **not**
a Mailable rewrite. This file is the runbook: what is code (done) and what is **Subhan's** to do (account + DNS).

---

## 1. The environment matrix — STRICTLY separated (the #1 guard)

| Environment | `MAIL_MAILER` | Delivers to a real person? |
|---|---|---|
| **local / dev** | `log` (default) or `smtp` → Mailpit (127.0.0.1:1025) | **No** — logs / a local catch inbox |
| **test / CI** | `array` (set in `phpunit.xml`) | **No** — captured in memory, never sent |
| **production** | `postmark` | **Yes** — the real provider |

**Enforced in code, not by convention** — [`MailSafetyGuard`](../app/Support/Mail/MailSafetyGuard.php), applied
at boot in `AppServiceProvider`: a real-sending transport (`postmark`/`ses`/`resend`/`mailgun`/`sendmail`) is
**refused outside `production`** and downgraded to `log`. So even `MAIL_MAILER=postmark` in a stray local/CI
`.env` **cannot** email a real person unless `APP_ENV=production`. Mailpit `smtp`, `log`, and `array` are left
untouched (they never reach a real inbox). Proven in `tests/Feature/MailTransportSafetyTest.php`.

---

## 2. Secrets — ENV only (never committed)

Set in the **production** environment only (a secrets manager / the host's env, never the repo):

```
MAIL_MAILER=postmark
POSTMARK_TOKEN=<Postmark server API token>        # Server → API Tokens
POSTMARK_MESSAGE_STREAM_ID=outbound               # the transactional stream
MAIL_FROM_ADDRESS="no-reply@<your-sending-domain>"  # MUST be on the DKIM-verified domain
MAIL_FROM_NAME="Vyrobel"
```

`.env.example` documents the keys with **no values**. `config/services.php` reads `services.postmark.token`;
`config/mail.php` reads the message stream. Nothing else changes — the Mailables are untouched.

---

## 3. Domain authentication — DNS records (⚠️ SUBHAN's to add)

Unauthenticated mail lands in **spam** (or is rejected). Postmark requires two things on the sending domain, added
to your DNS **and verified in the Postmark dashboard** (Sender Signatures → Domains → your domain shows the exact
values — copy them verbatim; the DKIM value below is a shape, the real one is unique to your account):

| Purpose | Type | Host (example for `vyrobel.com`) | Value |
|---|---|---|---|
| **DKIM** (signs the mail) | `TXT` | `<selector>._domainkey.vyrobel.com` | the long `p=…` public key Postmark shows |
| **Return-Path** (custom bounce domain — sets SPF alignment) | `CNAME` | `pm-bounces.vyrobel.com` | `pm.mtasv.net` |
| **DMARC** (policy + reporting) | `TXT` | `_dmarc.vyrobel.com` | `v=DMARC1; p=none; rua=mailto:dmarc@vyrobel.com` |

Notes:
- **SPF**: Postmark's custom Return-Path (the `pm-bounces` CNAME) provides SPF alignment — you do **not** normally
  add `spf.mtasv.net` to your apex SPF. If you send from the apex without the custom Return-Path, add
  `include:spf.mtasv.net` to your existing `v=spf1 … ~all` TXT (keep ONE SPF record).
- **DMARC**: start at `p=none` (monitor via the `rua` reports), then tighten to `p=quarantine` and eventually
  `p=reject` once DKIM + SPF pass consistently. Don't start at `reject`.
- Verify each record shows **green/verified** in Postmark before the first production send.

**What's code vs DNS:** the transport, the token wiring, the safety guard, the from-identity plumbing are **code
(done)**. The DKIM/Return-Path/DMARC records are **DNS on your domain (Subhan)** — Vyrobel can't add them.

---

## 4. The one controlled real send (the live-rail proof) — run when the account + DNS are ready

There is **no automated real send** in dev/test (the #1 guard forbids it). To prove the live rail end-to-end,
send **one** real email to **your own address** (never a customer), from a shell where the production ENV is
loaded (`APP_ENV=production`, `MAIL_MAILER=postmark`, `POSTMARK_TOKEN` set):

```php
// php artisan tinker  (in the production-env shell)
Mail::raw('Vyrobel production email — live-rail test.', function ($m) {
    $m->to('YOUR-OWN@address')->subject('Vyrobel · Postmark live test');
});
```

Or send a real Mailable to yourself (e.g. a review-request) the same way. If DKIM/DMARC haven't propagated yet,
it may land in **spam** until they do — that's expected, not a bug. **Only ever your own address** for this test.

---

## 5. Delivery hardening (Phase 8 · SG2)

- **The Phase-3 boundary holds with the real transport (re-proven).** A Postmark send failure (network / 5xx /
  rate-limit) is caught + logged (never the address — no PII) in `SendGuestTransactionalEmails` and **never rolls
  back** the booking/notification — the email is a best-effort side effect. Proven against a transport that
  always throws in `tests/Feature/MailDeliveryBoundaryTest.php` (a booking AND a cancellation both survive a
  provider outage).
- **Fail fast:** the Postmark mailer has an 8s client timeout (`config/mail.php`), so a provider hang can't hold
  a request open — it throws, is caught, and the domain op proceeds.
- **Retry / queueing — deferred deliberately (not faked).** v1 uses the certified Phase-3 SYNCHRONOUS best-effort
  send. Queued retry via Horizon (which is live) is a real future enhancement but is **not enabled now**: making
  the transactional Mailables `ShouldQueue` flips `Mail::assertSent` → `Mail::assertQueued` across the certified
  Phase-3 email suite (`GuestEmailTest`), i.e. it would rebuild the certified layer — out of scope for "wire a
  provider into the existing seam". The boundary already guarantees a failure never breaks the business op; a
  transient failure logs and the operator can re-trigger the transactional email through the app flow.

## 6. Bounce / complaint handling — Postmark-native for v1 (honest deferral)

**The deliverability concern — "don't keep emailing a dead address" — is handled AT THE PROVIDER.** Postmark
automatically **suppresses hard bounces and spam complaints** on its side: it will not re-deliver to a
hard-bounced address and honours complaints, and the **Postmark dashboard** (Activity → Suppressions / Bounces)
surfaces them. For v1 this is the bounce/complaint handling — real, at the provider, not faked.

**App-side handling is DEFERRED deliberately** (stated, not stubbed) for two honest reasons: (a) Postmark's
native suppression already protects deliverability, and (b) an app-side suppression store must **blind-index**
the address — the guest email is encrypted PII (invariant #28), so a plaintext bounce list would undo that — a
real subsystem to build deliberately, not rush.

**What a real app-side webhook would add (the future build — NOT built now):** a `POST /webhooks/postmark` route
(CSRF-exempt, like the other webhooks), **authenticated by HTTP Basic Auth** (Postmark secures webhooks with a
username:password set in the Postmark webhook config — checked on the endpoint; Postmark does **not** HMAC-sign,
so Basic Auth over TLS is the verification), parsing the `Bounce` / `SpamComplaint` payload, recording a
suppression keyed by a **blind-index hash** of the address, with the guest-send path skipping a suppressed hash.
This is documented so it can be built when app-side suppression is wanted — it is not simulated today.
