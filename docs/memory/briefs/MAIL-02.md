# VYROBEL — MAIL 02 — ⚠️⚠️ THE LAST LINK

**Live: `6afbfeb0`. Suite 3928/3928 · mutations 146/146 · money gate unmoved · 0 customers.**
**⚠️ Mail is still `log` — every email the product sends is discarded without a trace. ⚠️ The console's mail card is red.**
**⚠️⚠️ THE CHAIN FROM THE FIRST WEEK: ⚠️ **A GUEST BOOKS → A JOB IS QUEUED → A WORKER RUNS → POSTMARK SENDS → AN INBOX RECEIVES.** ⚠️ The first three links are proven. ⚠️ **THIS PASS PROVES THE LAST TWO, AND SWITCHES MAIL ON FOR REAL.**
**⚠️ HE IS PRESENT FOR THIS PASS — ⚠️ he signs into Postmark and his DNS provider, copies the token, and gives you one test inbox. ⚠️ Everything else is yours.**

---

## ⚠️⚠️ THE RULES THAT DON'T BEND

- ⚠️⚠️ **HE SIGNS IN EVERYWHERE. YOU NEVER TYPE A PASSWORD OR 2FA CODE.**
- ⚠️⚠️ **THE POSTMARK SERVER TOKEN IS HIS: stop before any screen reveals it, he copies it, he types it into `.env` on the server. ⚠️ You never see it, echo it, print it or store it. ⚠️ If it lands in your output, say so and tell him to regenerate it.**
- ⚠️ **Non-secret values — `MAIL_MAILER=postmark`, `MAIL_FROM_ADDRESS` — you may set, and you read them back.**
- ⚠️⚠️ **PRODUCTION CACHES CONFIG (L14): every `.env` change needs the config cache rebuilt, and is proven by behaviour — never by reading the file.**
- ⚠️⚠️ **NEVER TOUCH MX RECORDS. NEVER ADD A SECOND SPF RECORD. NEVER CHANGE DMARC. NEVER EDIT OR DELETE AN EXISTING DNS RECORD WITHOUT SHOWING HIM AND GETTING A YES.**
- ⚠️ **Identifiers are read, never written — DKIM values, message IDs, release hashes: copy them from their source, never retype them.**

---

## ⚠️ #1 — ⚠️⚠️ DNS AND POSTMARK — ⚠️ FOLLOW `Vyrobel-Dns01-TheRecordsThatMustBeExact.md` §0 TO §4 EXACTLY

- ⚠️ **§0** read what's already on the domain first — `dig NS / MX / TXT / TXT _dmarc` — **and show him the BEFORE state. ⚠️ It is the rollback.**
- ⚠️ **§1** he signs into Postmark (or creates the account) → add `vyrobel.com` → **capture DKIM and Return-Path from the page itself, never retyped.**
- ⚠️⚠️ **Also report the account's state. 📖 A new Postmark account may be pending approval, and until it's approved may only be allowed to send to addresses on its own verified domain. ⚠️ Verify that in the console — my knowledge of it is dated. ⚠️ If it's restricted, say so plainly: it decides where the test emails in #4 can go.**
- ⚠️ **§2** he signs into the DNS provider → paste, never retype → **watch for the provider appending the domain twice** → report exactly what was saved.
- ⚠️ **§3** read the records back from the **authoritative** nameserver and a **public** resolver → **three verdicts: matching · different · not yet visible.**
- ⚠️ **§4** verify at Postmark.
- ⚠️⚠️ **PROPAGATION:** re-check every few minutes for up to an hour. ⚠️ **If Postmark still hasn't verified by then, STOP CLEANLY** — mailer still `log`, nothing half-switched — ⚠️ write exactly where to resume, and end the pass there. ⚠️ **Do not switch the mailer on against an unverified domain.**

---

## ⚠️ #2 — ⚠️⚠️ BEFORE SWITCHING ON: WHAT WILL SEND THE MOMENT YOU DO

> ⚠️⚠️ **TURNING MAIL ON RELEASES EVERYTHING THAT WAS WAITING — AND EVERYTHING THAT WILL SEND NEXT. ⚠️ Look before you flip it.**

- ⚠️ **The queue: count every mail job in `jobs` and `failed_jobs`. ⚠️ Name each one — who it's to, what it is.** ⚠️ Anything stale or addressed to a probe: **remove it before switching** and say what you removed.
- ⚠️⚠️ **THE PROBE ADDRESSES CAN'T RECEIVE MAIL.** ⚠️ Probe users are on `@vyrobel-probe.invalid` and similar. ⚠️ **Every email sent to one will bounce — and a new Postmark account with a high bounce rate gets reviewed or suspended.** ⚠️ Find every scheduled or triggered email that could go to an internal org's user in the next few days (pre-arrival reminders, payment reminders, digests, trial-ending notices) and **make sure none will fire at an undeliverable address.** ⚠️ The cleanest way is an explicit suppression for internal/probe addresses — additive, tested, mutation-proved. ⚠️ **Say what you chose and why.**
- ⚠️ **Check the addresses on `Vyrobel Internal`'s six role accounts and `founder.test@vyrobel.com` too — ⚠️ say which can actually receive mail.**

---

## ⚠️ #3 — ⚠️⚠️ SWITCH ON

- ⚠️ **He types `POSTMARK_TOKEN` into `.env`.** ⚠️ You set `MAIL_MAILER=postmark` and `MAIL_FROM_ADDRESS` to **an address Postmark has actually verified** — read the verified sender from the console, never assume it.
- ⚠️ **Rebuild the config cache. ⚠️ Confirm the running app now reports the Postmark mailer** — through the health board's mail card or an equivalent runtime read — **not by reading `.env`.**
- ⚠️ **While he's in `.env`, offer once: he can paste his Healthchecks URL into `OPS_HEARTBEAT_PING_URL` too.** ⚠️ If he does, prove the ping arrives at Healthchecks. ⚠️ If not, leave it on his list.

---

## ⚠️ #4 — ⚠️⚠️ THE LAST TWO LINKS, PROVEN END TO END

- ⚠️⚠️ **ASK HIM ONCE FOR ONE REAL INBOX TO RECEIVE TEST MAIL** — his own address, ideally a plus-address he can filter (e.g. `name+vyrobel-test@…`). ⚠️ **Never guess or reuse an address from anywhere else.** ⚠️ If Postmark's account is restricted to its own domain (#1), use an address on the verified domain that he can read, and say so.
- ⚠️⚠️ **ONE REAL EMAIL FIRST.** Send a single test through the **real queue and the real worker** — not inline, not from the shell with a special mailer. ⚠️ Then:
  - ⚠️ **Postmark's activity feed shows it Delivered** — read the message ID from Postmark, never type it;
  - ⚠️ **he confirms it's in his inbox, not in spam.** ⚠️ **If it's in spam, that's a finding** — check it was signed with DKIM and aligned, and report.
- ⚠️⚠️ **THEN THE FLOWS THAT HAVE NEVER WORKED ON PRODUCTION**, each on a clearly named disposable test organisation, each to his test inbox, each proven by the email arriving **and the link inside it working:**
  1. ⚠️ **Email signup verification** — sign up with his test address → the verification email arrives → the link verifies → `/app` opens. ⚠️ **And confirm the "email delivery isn't switched on" sentence has disappeared from the confirm page.**
  2. ⚠️ **Password reset** — request it → email arrives → reset works → ⚠️ **a second session on the same account is signed out (the L16 fix, now proven through the real email path).**
  3. ⚠️ **Staff invite** — the owner invites a staff user at a second plus-address → invite arrives → accepted → the new user is verified and reaches `/app`.
  4. ⚠️⚠️ **THE ORIGINAL CHAIN — a booking confirmation.** On the test organisation, create a reservation for a guest at his test address → ⚠️ **the job is queued → the worker runs → Postmark shows Delivered → he sees it arrive.** ⚠️ **This is the chain that was the launch blocker on day one. Say so when it closes.**
- ⚠️ **Read the audit / notification rows back for each.**

---

## ⚠️ #5 — ⚠️⚠️ FAILURES MUST BE LOUD

- ⚠️ **Already proven in MAIL 01:** a bad token and an unreachable host both land in `failed_jobs`. ⚠️ Cite it; don't redo it on production.
- ⚠️⚠️ **THE ONE THAT COULDN'T BE TESTED WITHOUT A VALID TOKEN: A FROM-ADDRESS POSTMARK HASN'T VERIFIED.** ⚠️ Dispatch **one** email through the real queue with an overridden, unverified from-address, **using a one-off override — never by editing the real `.env`.** ⚠️ It must land in `failed_jobs` with Postmark's rejection in the reason, and the console's mail card must show it. ⚠️ **A silent success here is the worst possible result — it's exactly the failure the whole of MAIL 01 existed to prevent.**
- ⚠️ **Confirm the health board's mail card is now green, shows real sent / bounced / failed counts, and that its counts agree with Postmark's own activity feed.** ⚠️ **A board that disagrees with the provider is lying — reconcile it.**

---

## ⚠️ #6 — ⚠️ CLEAN UP AND CLOSE

- ⚠️ **Archive the test organisations this pass created** (they're disposable; archive is reversible). ⚠️ Leave `Vyrobel Internal` and `Vyrobel Test Workspace` alone.
- ⚠️ **Nothing left behind:** no queued test mail, no pending invite, no live announcement, no `/status` incident, every kill switch on.
- ⚠️ **If any code changed (suppression, board reconciliation): full suite · PHPStan · Pint · money gate FULL · mutations all land and are caught · deploy via L12k · front door from outside (Google, X, `/signup`, anonymous `/admin/*`, 5xx sweep, `SERVED=0`).**
- ⚠️ **Update the data inventory and the sub-processor register: Postmark now receives recipient addresses and message content.** ⚠️ Its register entry still says "no agreement on file" until one exists — **don't change that to make it look better.**

---

## ⚠️ DON'T-OVER-REACH

- ⚠️⚠️ **NEVER SEE THE TOKEN. NEVER TYPE A PASSWORD. NEVER TOUCH MX.**
- ⚠️⚠️ **NEVER SWITCH MAIL ON AGAINST AN UNVERIFIED DOMAIN, AND NEVER WITH UNREVIEWED MAIL SITTING IN THE QUEUE.**
- ⚠️ **Never send to an address he didn't give you. Never send to a probe address.**
- ⚠️ **No marketing or bulk mail — transactional stream only.**
- ⚠️ **Nothing that moves money. No changes to the social sign-in rules, the verify gate, `RefuseEmailTaken`, or `throttle:5,1`.**
- ⚠️ **If a page doesn't respond after two or three tries, stop and ask him — don't click around a live account.**

---

## THE FLOW

**#1** DNS 01 §0–§4 · account state reported · ⚠️ **stop cleanly if not verified within the hour**
**#2** review the queue · suppress mail to undeliverable internal addresses
**#3** he types the token · you set mailer + verified from-address · cache rebuilt · proven at runtime · offer the heartbeat URL
**#4** one real email: Delivered in Postmark + in his inbox → signup verification → password reset → staff invite → **the booking confirmation that closes the day-one chain**
**#5** unverified sender fails loudly · board green and agreeing with Postmark
**#6** archive test orgs · nothing left behind · deploy if code changed · inventory and register updated
**CLOSE** → ⚠️ **three sentences, plain words: has an email from vyrobel.com been seen arriving in a real inbox · does every flow that needs email now work end to end · is anything still waiting on him (Postmark approval, the heartbeat, the processing agreement).**
