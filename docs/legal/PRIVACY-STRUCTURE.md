# Privacy Policy — STRUCTURE AND FACTS, FOR COUNSEL TO DRAFT FROM

**front-website Gate 7 · 2026-08-22 · ⚠️ NOT A POLICY. NOT PUBLISHED. NOT LEGAL ADVICE.**

---

## ⚠️ WHY THIS IS A DOCUMENT AND NOT A PAGE

The brief asked for the page's structure with placeholders, and for the page not to be published. This is
that structure — deliberately as a **document rather than an unrouted Blade view**, because an unrouted view
is dead code one route away from being live, and the thing it must never do is get published by accident.
The audience is a lawyer, and a lawyer does not need Blade.

⚠️ **NO POLICY TEXT IS DRAFTED HERE.** Jurisdiction-specific legal drafting is not a build step, and a
plausible-looking privacy policy is worse than none: the founder would rely on it, and so would their
customers' auditors. What this gives counsel instead is the thing only the codebase can produce — **what the
software actually does**, cited to `path:line`, section by section, in the order the design's own page runs.

⚠️ **THE DESIGN'S OWN DRAFT WAS ALREADY HONEST ABOUT THIS**, and deserves credit: it is headed *"Draft — not
yet reviewed by counsel"*, states *"not legal advice and must not be published as-is"*, and is scattered with
`TBC` markers. The correction needed is not to its framing but to its **facts** — several of its factual
assertions do not match the code, and they are listed under each section below.

---

## The design's section order

| # | Section | What counsel needs | Where the facts are |
|---|---|---|---|
| 1 | Who we are | The legal entity, its address, and the controller/processor split | ⚠️ **No company identity exists anywhere in the repo** — no registered office, company number or VAT number. Founder must supply. |
| 2 | What we collect | The categories, exhaustively | `DATA-INVENTORY.md` Domains 1–3 — 91 cited entries |
| 3 | Lawful basis, by category | A basis per category + a balancing test wherever legitimate interests is claimed | Inventory "legal basis" column — **the code's implication only** |
| 4 | How we use it | Purposes | Inventory "why" column |
| 5 | Where it lives, and for how long | Hosting, region, retention | ⚠️ **Hosting is not chosen.** Retention: see the gap list — 96 of 142 entries have no rule |
| 6 | Sub-processors | Named entity, location, DPA, transfer mechanism | Inventory Domain 4 — 27 entries, 3 real |
| 7 | Your rights | Which rights are self-service, per realm | Inventory Domain 5 |
| 8 | Cookies | Every cookie, its purpose and lifetime | `App\Support\Privacy\CookieConsent` — the live catalogue, CI-guarded |
| 9 | Contact | A privacy address and a response process | ⚠️ Founder must supply |

---

## ⚠️ SECTION-BY-SECTION: WHAT THE DESIGN'S DRAFT ASSERTS, AND WHAT THE CODE SAYS

### §1 Who we are
The draft says Vyrobel is the **processor** and the operator is the **controller** for guest data. That is the
conventional allocation for a hosted PMS and nothing in the code contradicts it. ⚠️ Two things counsel must
settle: **Vyrobel is the CONTROLLER for its own customers' account data** (Domain 2 — 37 entries), and the
processor relationship is contingent on an **Article 28 agreement that does not exist**.

**PLACEHOLDER — pending legal drafting:** legal entity, registered address, company number, representative.

### §2 What we collect
⚠️ The draft's five bullets are broadly right but materially incomplete against the inventory. Missing from
them: **staff and roster data including leave reasons** (possible Article 9), **IP addresses and user agents
in permanent audit stores**, **verbatim third-party webhook payloads**, **abandoned booking quotes from people
who never booked**, and **browser localStorage**.

### §3 Lawful basis, by category
⚠️ The draft's table is a good skeleton with honest `TBC`s. Replace its guessed rows with the inventory's
cited ones. ⚠️ Two of its rows are wrong as written:
- *"Marketing contact — Consent, separately captured at check-in"*: guests have a real `marketing_consent`
  and a separate unsubscribe flow, so this is right for guests — but the **new public contact form**
  (Gate 7) collects a prospective customer's name, email and free text on a **different** basis entirely.
- *"Payment references (tokens) — held by the payment provider, not by us"*: correct, and worth keeping.
  **No card data is stored, ever** — hosted Stripe Checkout, SAQ A.

### §4 How we use it
No factual conflict found.

### §5 Where it lives, and for how long
⚠️ **THE DRAFT'S TWO ASSERTIONS ARE BOTH UNSUPPORTED TODAY.**
- *"Data is stored on European infrastructure"* — ⚠️ **the product is not deployed anywhere.** There is no
  provider, no region and no residency commitment in the repository. This is a decision, not a fact.
- *"encrypted in transit and at rest"* — encryption at rest is **application-level and column-selective**,
  not database or disk level. The encrypted columns are listed in the inventory; guest **names**, dates of
  birth, nationality, city and every reservation field are **not** among them.
- *"Identity documents are held only as long as the operator's local law requires"* — ⚠️ the purge is real
  and runs daily, but the window is a **fixed 30-day product default**, not a per-jurisdiction legal period.

### §6 Sub-processors
⚠️ **The list does not exist.** The draft's table is entirely `TBC`. The inventory names the real ones:
**Postmark** (email — the only one live today), **Stripe** (payments, and Connect KYC which transmits owner
identity documents), **Anthropic** (the help-assistant seam, inert), plus **Redis** and any object store.
⚠️ **Channex is NOT a sub-processor today** — it is not integrated and no code path can transmit guest data
to a channel. It becomes one on connection.
⚠️ ⚠️ **KEEP the draft's line that each entry "needs a named entity, its processing location, a signed data
processing agreement, and a transfer mechanism where processing happens outside the EEA."** It is the most
useful sentence on the page. It states a requirement; it does not claim a DPA exists.

### §7 Your rights
⚠️ Rights are **realm-dependent**, and the draft implies they are uniform.
- **Correction** — self-service in all three realms.
- **Erasure** — self-service for **guests only** (14-day reversible window). Owners and operators must ask.
- **Access / portability** — self-service for owners and operators (own account and activity). ⚠️ **A guest
  cannot export their own data** — the guest portal has no export route at all.
- **Objection / restriction** — no mechanism in any realm.

### §8 Cookies
⚠️ This section can be written from the code today with confidence, and it is the strongest section on the
page. `App\Support\Privacy\CookieConsent` holds the live catalogue and a CI gate keeps it true: **four
cookies, all strictly necessary, no analytics, no marketing, no third-party cookie**, and the site makes **no
third-party request of any kind**. ⚠️ **One addition is required: `localStorage`.** The app stores a
reservation draft and a recent-search list in the browser, and under ePrivacy terminal-equipment storage is
in scope regardless of whether it is a cookie.

### §9 Contact
**PLACEHOLDER — pending founder decision:** the draft uses `privacy@vyrobel.com`, which **does not exist
anywhere in the repository**. The only address the codebase knows is `sales@vyrobel.com`.

---

## ⚠️ THE THREE THINGS THAT BLOCK PUBLICATION

1. **The Article 28 DPA does not exist**, and Vyrobel cannot lawfully process a customer's guest data as a
   processor without one. Already a recorded go-live blocker.
2. **The sub-processor list does not exist.**
3. **Retention periods do not exist** for 96 of 142 categories. A policy cannot state a period the software
   does not implement — the periods must be chosen *and then built*, in that order.
