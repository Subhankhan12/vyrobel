# VYROBEL — TONIGHT 01 — ⚠️ SIX THINGS, THREE BEFORE THE PASSWORD AND THREE AFTER

**Live: `6afbfeb0` + rel_Y (mail code). Suite 3936/3936 · 155 mutations caught · money gate unmoved · 0 customers.**
**⚠️ MAIL 02 is mid-way: Hostinger mail wired, skip-list in place, queue empty — waiting on him to type the `contact@` mailbox password into `.env`.**
**⚠️⚠️ PART 1 needs nothing from him — start it now. ⚠️ PART 2 starts the moment he says the password is in. ⚠️ Don't ask him anything else.**

---

## ⚠️ PART 1 — NOW, WITHOUT HIM

### 1 — ⚠️ Move the three older settings backups out of the web folder

- ⚠️ **He has said yes.** Move them to the same private folder, outside the web root, where you put your own two.
- ⚠️⚠️ **NEVER PRINT, `cat`, `head` OR PREFIX THEM — they hold every secret the site has. ⚠️ Names, sizes, modes and digests only.**
- ⚠️ **After the move:** mode `600` on each · none left anywhere under the web root (search for every `.env*` copy, not just the three you know about) · nothing in the app or the deploy scripts references the old paths · the site still works.
- ⚠️ **Report the before and after locations by name.**

### 2 — ⚠️ Lock down `.env`

- ⚠️ **First establish how `.env` is laid out: one shared file symlinked into each release, or a copy per release?** ⚠️ Say which.
- ⚠️⚠️ **Confirm the web process runs as the file's owner (`u545270279`) before changing anything** — ⚠️ `600` under the wrong owner produces an instant site-wide 500 that looks like a bug. Same prerequisite as L13.
- ⚠️ **Set `600` on every `.env` the site can read.** ⚠️ **Then prove by result:** front door from outside — Google and X redirects, both buttons on `/login`, `/signup` accepts a signup, anonymous `/admin/*` → login, 5xx sweep, `SERVED=0`.
- ⚠️⚠️ **AND CHECK THE NEXT DEPLOY DOESN'T UNDO IT.** ⚠️ If a deploy creates or copies `.env` at `777`, fix the deploy script so it sets `600` — ⚠️ **a fix a deploy undoes is not a fix.** ⚠️ Prove it on the next deploy in this pass.

### 3 — ⚠️ Put the data inventory on the live site

- ⚠️ **The retention meter reads an inventory that only lives in `docs/`, and `docs/` isn't deployed** — so on production the meter says it can't count.
- ⚠️ **Ship only the inventory**, as a data file the app reads from inside the release (not all of `docs/`). ⚠️ One source of truth: the doc and the shipped file must not be able to drift — generate one from the other, or make the doc point at the data file.
- ⚠️ **Confirm it holds no personal data** — it's a list of data categories, not data.
- ⚠️ **The meter still shows both candidate readings and still chooses neither** — that's his ruling with his lawyer. ⚠️ **Prove on the live console that it now counts instead of saying it can't.** ⚠️ If he isn't signed into `/admin`, prove it from the server with the same code path the page uses, and say so.
- ⚠️ **Gates:** suite · PHPStan · Pint · money gate FULL · mutations all land and are caught · deploy via L12k · front door · `.release` read, never typed.

---

## ⚠️ PART 2 — THE MOMENT HE SAYS THE PASSWORD IS IN

### 4 — ⚠️ Finish MAIL 02 exactly as you laid it out

- ⚠️ **Test the Hostinger login before switching on → switch on → config cache rebuilt (L14) → prove at runtime that mail is on.**
- ⚠️ **One real email through the queue to `contact@`.** ⚠️ It arrives, not in spam; ⚠️ check SPF, DKIM and DMARC results in the received headers; ⚠️ compare with hPanel's Email Logs.
- ⚠️⚠️ **PLUS-ADDRESSES: test ONE `contact+test@vyrobel.com` before relying on them.** ⚠️ Hostinger has no aliases or catch-all — ⚠️ **if a plus-address doesn't arrive, stop using them immediately** (each failed send may bounce and hurt `contact@`'s reputation) ⚠️ and ask him once for a second real inbox to use for the flows that need two addresses.
- ⚠️ **The four flows on a clearly named throwaway organisation, each proven by the email arriving AND its link working:** signup verification · password reset (with a second signed-in device signed out) · staff invite · **the booking confirmation that closes the day-one chain.**
- ⚠️ **One email Hostinger must reject → lands in `failed_jobs` → the mail card goes amber.** ⚠️ Then clear it.
- ⚠️⚠️ **BUDGET: Hostinger allows 100 emails a day. Keep this whole pass under 20, count every send, and report the count.**

### 5 — ⚠️ The support buttons actually deliver

- ⚠️ **"Resend verification" and "send password reset" from the admin console have only ever been seen saying "not delivered".**
- ⚠️ **Use a user on the throwaway organisation, at an address that you proved in #4 receives mail.** ⚠️ Never a probe address — they're on the skip list, and it should refuse them; **confirm that it does.**
- ⚠️ **If he's signed into `/admin` in the Playwright window, press the real buttons.** ⚠️ Otherwise trigger the same actions from the server through the same code path, and say which you did. ⚠️ Either way: email arrives, link works, audit row read back.

### 6 — ⚠️ The mail card tells the truth

- ⚠️ **Green now, with real sent and failed counts that match hPanel's Email Logs.** ⚠️ **A card that disagrees with the provider is lying — reconcile it before calling it done.**
- ⚠️ **It still says plainly that bounces aren't visible to the app.** ⚠️ Don't let that line disappear just because the card turned green.

### Close

- ⚠️ **Archive the throwaway organisation. Nothing left behind:** no queued test mail, no pending invite, no announcement, no `/status` incident, every kill switch on.
- ⚠️ **Sub-processor register and data inventory updated for Hostinger mail** — agreement still "None on file" until one exists.
- ⚠️ **Three sentences, plain words:** did an email from vyrobel.com arrive in a real inbox, not spam · does every email flow now work end to end · how many emails were sent today, out of 100.

---

## ⚠️ DON'T-OVER-REACH

- ⚠️⚠️ **NEVER SEE, PRINT OR STORE THE MAILBOX PASSWORD OR ANY BACKUP'S CONTENTS.**
- ⚠️ **Never email a probe, test-domain or no-mailbox address. Never email anyone he didn't give you.**
- ⚠️ **Never type his admin password or 2FA.**
- ⚠️ **Nothing that moves money. No changes to the social sign-in rules, the verify gate, `RefuseEmailTaken`, or `throttle:5,1`.**
- ⚠️ **Any public check fails after a deploy → roll back first, diagnose second.**
- ⚠️ **One suite at a time · no edits while a suite runs · `ops/ps-check.sh` with the php84 discriminator.**
