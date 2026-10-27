# I18N-01 — ⛔ G1: inventory and plan

The brief: `docs/memory/briefs/I18N-01.md` (pasted 2026-10-27 01:32 UTC). The coverage map: `docs/i18n/INVENTORY.md` (generated;
re-run `php ~/vyrobel-local/i18n/tools/inventory.php <repo> docs/i18n/INVENTORY.md`). The work log for a new session:
`~/vyrobel-local/i18n/STATE.md`. Labels: **V** = Verified (how, when) · **R** = Reported · **F** = Founder said · **E** = estimate
from the G1 draft scanner.

## 1. What exists today

- **Keys: 10 translation calls in 7 files; no `lang/` folder** (V 2026-10-27, grep — matches your read).
- **`SetLocale`** sits in the `web` group after `SetTenantForWebRequest` (`bootstrap/app.php`). It reads `users.locale`, then
  `guests.language`, and applies it only if listed in `config('app.available_locales')` = `en, pt, es, fr` (V). Livewire's
  update route runs the `web` group, so it should apply there too — to be proven by a test in Phase B, not assumed.
- **Production has no saved non-English choice** (V 2026-10-27, read-only, counts only): `users.locale` 17 × `en`, 3 empty;
  `guests.language` 18 of 18 empty; `organizations.default_locale` 15 × `en`. So narrowing `available_locales` to finished
  languages changes nobody's screen today.
- **The organisation default** `organizations.default_locale` exists (edited in Settings and the console) and nothing reads
  it to choose a language (V, grep). Settings accepts any string up to 10 characters.
- **The guest Account page** offers EN / DE / ES / FR (DE is not in `available_locales`), and the controller saves any value up
  to 10 characters — no allow-list (V, `GuestAccountController:94`). **The booking engine never sets a guest's language** (V).
- **Mail:** 22 Mailables, 2 queued; nothing implements `HasLocalePreference` (V).
- **The bell stores finished English sentences**: `NotificationFeed` reads `data['title']` / `data['context']` from the stored
  row (V). Audit timelines are labelled at render time from the action code (`ReservationDetail`), so nothing stored there
  needs rewriting (V).
- **PDFs:** 17 views, every one rendered through `PdfService` (no direct dompdf call elsewhere, V); each view sets DejaVu Sans
  in its own CSS (V).
- **Dates:** 395 `format()` calls print English month/day names; 0 use `translatedFormat`/`isoFormat`; 33 `diffForHumans`
  already follow the locale; 15 browser-side `toLocale…`/`Intl` calls (V, grep counts).
- **Uppercase:** 426 CSS `text-transform: uppercase|capitalize` rules in views (V, grep count).
- **Glued sentences and hand-made plurals (E, grep patterns):** ~930 English fragments joined with `.` in PHP, ~126 sentences in
  views split around a `{{ }}`, ~340 plurals built by hand (`Str::plural`, `=== 1 ? …`). These are the costly part of Phase A.
- **Five customisable guest emails** (`EmailTemplateCatalog`): defaults in code (translatable), an organisation's edits in the
  database (people's content) (V).
- **The AI assistant** is "Ask Vyrobel" on the owner Help page (`AskVyrobelService`, prompts in `App\Support\Llm\Prompts`),
  grounded on the owner Help articles in code (`HelpKnowledgeBase`, E: 78 strings, ~850 words) (V).

## 2. Scope and size (E — from `INVENTORY.md`)

| Realm | GET routes | real pages | hardcoded strings (own files) | words |
|---|---:|---:|---:|---:|
| Operator back office | 64 | 49 (+7 downloads, 3 JSON, 5 redirects) | ~4,080 + ~380 JS | ~16,900 |
| Owner portal | 48 | 37 (+8 downloads, 3 redirects) | ~1,960 + ~70 JS | ~8,050 |
| Guest portal | 30 | 29 | ~590 | ~2,660 |
| Public booking engine | 8 | 6 (+2 JSON the page's script draws) | ~160 | ~570 |
| Auth, signup, billing, OAuth consent | 14 | 13 | ~150 | ~800 |
| Platform admin console (last) | 29 | 28 | ~870 | ~4,040 |
| Shared shells and kit (38 files, counted once) | — | — | ~140 + ~40 JS | ~470 |

Around the screens (whole folders, overlapping the above): emails ~300 strings, notifications ~60, PDFs ~315, error pages 16,
label helpers in `app/Support` ~345 (+~730 unclassified), controller flashes ~460 (+~690 unclassified).
**Distinct English strings, each counted once: ~6,200 (~35,000 words)** — the per-language translation volume before keys
merge duplicates. For scale: hrmzy was ~13,500 keys per language (R: its memory, 379,344 translations ÷ 28).

**Who opens what** (route gates; pages also check actions inside): back office — Property Owner 64 routes, Group Admin 62,
Hotel Manager 57, Front Desk 39, Revenue Manager 37, Reservation Agent 36, Marketing Manager 35, Accountant 34,
Guest Support Agent 34, Maintenance 30, Housekeeping 25; owner team — primary 48, co-owner 47, accountant 39,
view-only 26. The per-screen table with gates and roles is in `INVENTORY.md`.

⚠️ **The scanner is a G1 draft** (`~/vyrobel-local/i18n/tools/scan.php`): checked by hand against the login page (12 of 12
strings, V) and the Security page's controller (12 of 12 flashes, V); it over-counts some attribute values and splits text
around Blade directives. The Phase A scanner replaces it, mutation-proven, in the repo.

## 3. Order of work

1. **Foundation (Phase B core, English only):** the locale table (`App\Support\I18n\Locales`: code, HTML tag, native name,
   direction, Carbon locale, PDF font group, script — one definition), `lang/en/` with the framework files published,
   `available_locales` = `['en']` (finished languages only; affects nobody, §1), the `SetLocale` precedence (§5.2) with a
   Livewire-update test, `HasLocalePreference` on `User` and `Guest`.
2. **The instruments first:** the repo scanner (HARDCODED, GLUED) and a first `lang:check` with `--self-test`, so Phase A is
   measured as it goes, not at the end.
3. **Phase A, realm by realm, English byte-for-byte unchanged:** shared shells and kit → auth/billing → booking engine →
   guest portal → owner portal → operator back office → emails, notifications (render-time keys for new rows, §5.4), PDFs,
   exports → admin console last. Each step: suite with no assertion edits + the render diff of every GET page of that realm,
   for every role, before/after.
4. **⛔ G2:** English unchanged, shown; release; your look.
5. **The pipeline** in `ops/i18n/` (export, check, consistency, consolidate, apply, render-all — adapted from hrmzy), the
   hospitality glossary, then **de + ar** → **⛔ G3** (screenshots first; D-MONEY-COMMA for your ruling before German ships).
6. The other 25 languages in your order, one release each.
7. **Phase E finished** (`lang:todo`, the release preflight, `DELTA.md`) → **⛔ G4** on real new strings.

## 4. Choices I made (settled as defaults; say if you want otherwise)

- **Keys are PHP group files with named keys**, not JSON English-as-key: `__('booking.checkout.pay_button')`. Reason: one key
  per meaning needs a name ("Book" the verb ≠ the noun; JSON would merge them), the realm reads from the key, and hrmzy's
  pipeline already works on group files. English lives in `lang/en/*.php`; the extractor writes the English text, so it stays
  byte-identical. Shared concepts get one key each in the helper that owns them (`RailLabel`, `ReservationStage`, …).
- **Locale codes, Laravel style:** `en ar tr de fr es pt it nl pt_BR ru pl ro id vi zh_CN ja ko zh_TW hi th fa et el sr bg ka
  sq`; the table maps each to its HTML tag (`pt-BR`, `zh-CN`, `zh-TW`, `sr-Cyrl`) and Carbon locale (`sr` → Cyrillic messages,
  as hrmzy does).
- **A changed MEANING gets a new key; a reworded same meaning keeps its key** (lang:todo shows it as CHANGED). This keeps a stale
  translation from saying the opposite of the new English.
- **Renders run as probe users in the disposable tenant three** (`harbourpoint-demo-walkthrough`), one per role, inside a
  rolled-back transaction — never in tenant one, never on northgate.
- **Tools:** reusable scripts in `ops/i18n/`, the gate and report as artisan commands, bulky work data in `~/vyrobel-local/i18n/`.

## 5. Proposals for your ruling

**5.1 The booking engine's language (no `/{locale}/` route).** A language picker in the storefront header links to the same URL
with `?lang=de`; a middleware on the booking routes only reads it, sets a first-party cookie, and uses it. Without a choice:
the cookie → the browser's `Accept-Language`, best match among finished languages → the organisation default → English.
HTML stays `no-cache, private` (checked cache-busted after the change) and gains `Vary: Accept-Language, Cookie`. **At booking,
the language in use is saved to `guests.language`** when the guest has none, or when they picked one in this visit — so the
confirmation and pre-arrival emails arrive in it. No route changes, so E1 cannot fire; the `/book/seaside-demo` pin stays.

**5.2 Whose language wins.** Signed-in staff and owners: their own choice → the organisation default → English. Guest portal:
`guests.language` → the organisation default → English. A choice for a language that is not finished yet is kept and falls
back the same way. Settings and the guest Account page offer only finished languages (an allow-list replaces "any 10
characters").

**5.3 Emails.** Each goes in the recipient's language (`HasLocalePreference`, kept for queued mail): guest mails → the guest's;
owner reports → the owner user's; security mails (alerts, approval, resets) → the account's; staff notices about a guest → the
staff member's. ⚠️ **The five customisable guest emails:** a field the hotel edited stays as written. Recommendation: if any
field of that email was edited, send the whole email in the organisation's default language (one language, no mix); if none
was edited, the guest's language. Per-language templates are an "ask me first" item — not proposed now.

**5.4 The bell.** New rows store a key and parameters beside the English sentence (additive, no migration — the `data` JSON) and
are drawn in the reader's language; rows already stored keep showing their English. Nothing stored is rewritten.

**5.5 PDFs and exports.** Guest documents (folio, statement, refund receipt, arrival pack) → the guest's language, whoever
downloads them. **VAT invoice and credit note → the organisation default language** (the issuer's legal language) until the
lawyer rules (§6). Owner documents (statement, remittance, booking summary and confirmation, statement history) → the owner's
language. Operator documents (reports, schedule, maintenance costs) → the viewer's. **English only:** the owner agreement and
its amendment, and the annual tax summary (your brief). CSV/XLSX headers → the viewer's language; the VAT-return CSV and the
security data export stay English (machine-read).

**5.6 RTL time.** The chrome mirrors (sidebar right, logical spacing). For the rack, calendar and schedule I recommend **time runs
right to left** in Arabic and Persian, as native calendar apps do, with Western digits. Nothing flips without your look: at G3 I
bring screenshots of both directions.

**5.7 What blocks and what warns (Phase E).** **Blocks a release (preflight) and the suite:** `lang:check` errors on finished
languages — placeholder or HTML differences, a straight quote that can break a script, collapsed labels, glued keys, a key that
was translated and is now missing, a lang file that does not parse, a raw key on a page. **Blocks the suite only:** a new
hardcoded English string beyond the accepted baseline (so new text must be a key). **Warns (printed in the preflight, release
continues):** NEW keys (English shows until translated), CHANGED keys (stale translation shows until re-translated), REMOVED keys.

**5.8 Commits.** Your rule is one commit per gate; G2 alone is several days of edits. Recommendation: local commits per realm
inside Phase A (each with the suite green), never pushed, so a multi-day tree is never uncommitted. Your call.

**5.9 Ask Vyrobel.** The Help articles are product copy: translated like the rest. Replies following the owner's language would
need a prompt line ("answer in the language of the question") — a prompt change, so yours to rule; not done.

## 6. Legal wording for the lawyer (R: general knowledge, not legal advice — the lawyer confirms per country)

1. **Owner agreement + amendment** (page and PDF) and the **annual tax summary**: stay English (your brief).
2. **VAT invoice and credit note:** the EU list of what an invoice must show (VAT Directive art. 226); whether a country may
   require its own language (art. 248a); the credit note's reference to the original invoice. ⚠️ Several markets need more
   than labels: **Portugal** — invoices from AT-certified software (ATCUD, QR code); **Italy** — e-invoices through SDI;
   **Greece** — myDATA; **Spain** — Verifactu; **France, Poland, Romania** — e-invoicing mandates. The question for the lawyer
   is whether the VAT invoice Vyrobel prints is a legal invoice in those countries at all, before which language it is in.
3. **Self-billing:** if owner statements or commission documents act as invoices issued for the owner, a self-billing mention may
   be required.
4. **Withholding and tax names** on the owner Tax Center and statement (national terms, e.g. Portugal's "retenção na fonte").
5. **Guest booking terms:** the checkout shows "Free cancellation until … · no fees" and **no terms, privacy notice or consent
   text at all** (V, grep of `resources/views/booking`) — pre-existing, outside this brief, recorded as `OPEN.md` D-BOOK-NO-TERMS.
6. **City/tourist tax labels** where a municipality prescribes the name.

## 7. Known limits, stated up front

Machine translation with independent AI review — no native speaker; never "100%" or "certified". Hindi PDFs print English wording
(dompdf cannot shape Devanagari). People's content (names, descriptions, messages, reviews, edited templates) stays as written.
Legal documents stay English. Numbers and money display exactly as today; D-MONEY-COMMA comes to you at G3.
