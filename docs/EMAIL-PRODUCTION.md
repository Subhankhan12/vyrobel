# Production email (Phase 8) — Postmark

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

## 5. Delivery hardening + bounces — see §6 (Phase 8 · SG2)

The Phase-3 boundary holds with Postmark: a send failure (network / 5xx / rate-limit) is caught + logged and
**never rolls back** the booking/notification. Bounce/complaint handling: see the SG2 section of
`docs/DECISIONS.md` (2026-07-22, Phase 8 · production email) for whether a signed bounce webhook was built or the
Postmark dashboard suffices for v1 (stated honestly, never faked).
