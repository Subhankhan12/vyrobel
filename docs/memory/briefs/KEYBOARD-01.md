# VYROBEL — KEYBOARD 01 — ⚠️ FIVE JOBS WHERE HE ONLY SIGNS IN

**Run after TONIGHT 01 has closed. ⚠️ Job 4 also needs ADMIN 06 live (see its first gate). ⚠️ He is at the keyboard for this whole pass, about half an hour.**
**⚠️⚠️ THE PATTERN IS THE ONE THAT WORKED FOR THE GOOGLE AND X CONSOLES: ⚠️ **you open each site in the visible Playwright browser and drive it; he signs in, types every password and code, and copies every secret himself.** ⚠️ You never see a secret.**

---

## ⚠️⚠️ THE RULES FOR EVERY JOB BELOW

- ⚠️⚠️ **HE SIGNS IN EVERYWHERE. YOU NEVER TYPE A PASSWORD OR 2FA CODE.**
- ⚠️⚠️ **STOP BEFORE ANY SCREEN THAT REVEALS A SECRET.** He copies it, he pastes it into `.env` on the server, then you resume. ⚠️ Never screenshot, scrape or download a secret. ⚠️ **If one lands in your output, say so and tell him to regenerate it.**
- ⚠️⚠️ **EVERY `.env` CHANGE: config cache rebuilt (L14) and proven by behaviour — never by reading the file.**
- ⚠️ **Identifiers are read, never written.** ⚠️ **Any failing probe gets a control run before it's believed.**
- ⚠️ **If a page doesn't respond after two or three tries, stop and ask him — don't click around a live account.**
- ⚠️⚠️ **NOTHING THAT MOVES MONEY. NO LIVE CHARGE. NO LIVE TRANSFER. EVER, IN THIS PASS.**

---

## ⚠️ JOB 1 — ⚠️⚠️ REPLACE THE GOOGLE CLIENT SECRET, AND PROVE THE NEW ONE WITH A REAL SIGN-UP

- ⚠️ **Google Cloud → Clients → the Vyrobel web client → add a new secret.** ⚠️ **Stop before it's shown. He copies it and replaces the value in `.env`.** Cache rebuilt.
- ⚠️⚠️ **THE TRAP: `/auth/google/redirect` returning a 302 proves the client ID, NOT the secret.** ⚠️ The secret is only used when Google sends the user back and the app exchanges the code. ⚠️ **So the only proof the new secret works is a completed sign-in.**
- ⚠️ **Therefore: he signs up with his own Google account in a private window, all the way to `/app`.** ⚠️ This also closes "nobody has ever walked a Google signup." ⚠️ **Read the `user_identities` row back** — it has been at zero since the feature shipped.
- ⚠️ **Only after that succeeds: delete the old secret in Google Cloud.** ⚠️ Then prove sign-in still works — ⚠️ **a rotation that leaves the old secret working isn't a rotation.**
- ⚠️ **His new organisation is his own, not a customer: mark it `is_internal` by ID read from the database.** ⚠️ Ask him once whether to keep it or archive it.

## ⚠️ JOB 2 — ⚠️ THE FOUR ADMIN CHECKS THAT NEED HIS PASSWORD

- ⚠️ **He signs into `/admin` in the Playwright window.** ⚠️ Then exactly ADMIN 03B #3, on a fresh clearly named probe — **he types at every prompt:**
  - ⚠️ suspend → the probe's user is refused at `/app` → reactivate → access is back with the same status as before;
  - ⚠️ create an admin invite → cancel it → ⚠️ **never open the link;**
  - ⚠️ remove the last Owner → **refused;**
  - ⚠️ a break-glass support session → red banner → **one write attempt refused** → end it → the probe's staff see the in-app notice.
- ⚠️ **Audit row read back for every one, reason included.** ⚠️ Archive the probe after.

## ⚠️ JOB 3 — ⚠️ SWITCH ON THE HEARTBEAT ALARM

- ⚠️ **First read the code: how often does the scheduler send the heartbeat, and what suppresses it?** ⚠️ That decides the check's period and grace.
- ⚠️ **He signs into Healthchecks (or creates an account).** ⚠️ Create one check with that period and a sensible grace. ⚠️ **Alerts go to him directly — through Healthchecks' own email or an app, never through vyrobel.com's mail**, or the alarm shares a failure with the thing it watches.
- ⚠️ **He copies the ping URL into `OPS_HEARTBEAT_PING_URL`.** Cache rebuilt.
- ⚠️ **Prove it: Healthchecks shows real pings arriving and the check goes green.** ⚠️ **Use Healthchecks' "send test notification" to prove the alert actually reaches him.**
- ⚠️⚠️ **DO NOT break production to watch it fire.** ⚠️ Record "alarm firing on a real outage" as covered by the suite's suppression tests, unmeasured live.

## ⚠️ JOB 4 — ⚠️⚠️ STRIPE LIVE — ⚠️ SET UP, BUT DON'T FLIP THE APP UNTIL IT'S SAFE

- ⚠️⚠️ **FIRST GATE — ADMIN 06 MUST BE LIVE.** Permanent delete (ADMIN 05) must refuse while a Checkout page is open (B19), while any Stripe object is active or pending, and for any organisation with live-mode money records. ⚠️ **The top of `docs/STRIPE-PRODUCTION.md` lists those refusals with their test names — confirm the line is there and those tests pass on the live release.** ⚠️ **If it isn't there, no live key goes in, whatever else is ready. Do the dashboard setup below, stop before `.env`, and say so.**
- ⚠️ **Read `docs/STRIPE-PRODUCTION.md` first and follow it.** ⚠️ Where it disagrees with this brief, the doc wins and you say so.
- ⚠️ **He signs into Stripe and switches to live mode.** ⚠️ In the dashboard: create the live webhook endpoints the doc names (at least `/webhooks/stripe` and `/webhooks/stripe/connect`) with exactly the events it lists · review the Connect settings. ⚠️ **His business verification (identity, bank) is his to fill in — guide, never type it.**
- ⚠️⚠️ **BEFORE ANY LIVE KEY GOES INTO `.env`, ANSWER TWO QUESTIONS AND SHOW HIM:**
  1. ⚠️ **Could anything be charged the moment live keys are in?** ⚠️ Every organisation on production is on a free trial — **what happens when a trial ends under live keys?** ⚠️ Prove no internal organisation can ever be charged.
  2. ⚠️ **Do the plans point at Stripe price IDs?** ⚠️ Test-mode price IDs don't exist in live mode, and **live prices need his decision on what to charge — which hasn't been made.**
- ⚠️⚠️ **IF EITHER ANSWER IS UNSAFE OR INCOMPLETE: leave the app on test keys, keep the webhook secrets ready, and stop here.** ⚠️ Report exactly what's needed to finish — **most likely his price decision.**
- ⚠️ **IF BOTH ARE SAFE:** he copies the live keys and webhook signing secrets into `.env` · cache rebuilt · ⚠️ **send a test event from Stripe's dashboard to each live endpoint and prove the app accepts it with a valid signature** · prove an unsigned request is still rejected with 400 · front door from outside.
- ⚠️ **The revenue screen will keep saying "No live billing yet" until a real invoice is paid.** ⚠️ That's correct — don't make it say anything else.

## ⚠️ JOB 5 — ⚠️ CLOSE

- ⚠️ **Nothing left behind:** no probe suspended, no invite pending, no support session open, no announcement, no `/status` incident, every kill switch on.
- ⚠️ **If any code changed: full suite · PHPStan · Pint · money gate FULL · mutations all land and are caught · deploy via L12k · front door from outside · `.release` read, never typed.**
- ⚠️ **Sub-processor register updated for Healthchecks** (it receives pings from the server, no personal data — confirm that).
- ⚠️ **Three sentences, plain words:** is Google sign-up proven end to end with the new secret · is the alarm armed and proven to reach him · is Stripe live, or exactly what's still needed.

---

## ⚠️ WHAT STAYS HIS AFTER THIS

- ⚠️ **Prices and tax rates — and with them, the last step of Stripe live if Job 4 stopped.**
- ⚠️ **The lawyer — processing agreements with Hostinger, Stripe, Google and the rest.**
- ⚠️ **The remaining decisions on the list.**
