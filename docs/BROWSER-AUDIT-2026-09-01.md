# Vyrobel — browser audit of the marketing site · 2026-09-01

**The first time any of these pages has rendered in a real browser.** Every prior claim about them was
"the markup is present". This pass loaded them, clicked them, read the console, and measured them.

**Instrument.** Playwright MCP driving a warmed `php artisan serve` on `http://localhost:8000` (the
`APP_URL` host — see FP-1 for why the port matters). Widths: **375 × 812**, **768**, **1280**. Nothing was
fixed; nothing was regenerated. `git diff` is empty.

---

## 0 · Coverage statement — read this before any finding

⚠️ **"Nothing found on page X" and "X was never opened" must not read the same.** They do not here.

**19 routed marketing pages**, taken from `route:list`, not the file system:
`/` · `/product` · `/features` · `/modules` · `/pricing` · `/security` · `/integrations` · `/switching` ·
`/how-it-works` · `/about` · `/contact` · `/try-booking` · `/for-hotels` · `/for-apartments` ·
`/for-villas` · `/example-scenarios` · `/example-scenarios/city-hotel` · `/…/serviced-apartments` ·
`/…/villa-portfolio`.

| Check | Pages covered | How |
|---|---|---|
| HTTP status | **19/19** | direct request |
| Title · description · h1 count · heading order · `lang` · viewport · og/twitter · canonical · JSON-LD · img alt · vague link text · internal hrefs | **19/19** | parsed from the served HTML |
| Dead-control census · no-JS hidden-pane census | **19/19** | parsed from the served HTML |
| Layout at **375px** — horizontal overflow, tap-target heights | **19/19** | rendered in a 375px same-origin frame |
| Real-viewport 375px overflow, offender identification | **1** (`/`) | top-level viewport resize |
| Console + network | **5** — `/`, `/pricing`, `/product`, `/modules`, `/example-scenarios/city-hotel` | live navigation |
| Interaction (click + keyboard) | **4 controls** — home lenses, product stepper, module chips, story print | live |
| Focus order + focus-ring visibility, every stop | **1** (`/example-scenarios/city-hotel`, 35 stops); 10-control sample on `/` | live |
| Border / focus-ring contrast | **1** (`/`), 15 bordered controls | computed styles |

### ⚠️ What was NOT checked — stated so it is not mistaken for "clean"
- **768px and 1280px were spot-checked only**, not measured page by page. The 375px numbers below are the
  measured ones; the wider widths are **not** evidenced here.
- **Console and network on 14 of 19 pages.** All pages share one shell and one script, and the five checked
  were clean — but that is an inference, not a measurement, for the other fourteen.
- **Focus order on 17 of 19 pages.**
- **No screenshots were compared.** Nothing here claims a page "looks right" — only what measured.
- **No operator or owner app** (out of scope; needs authentication).
- **Real devices.** A 375px viewport is not an iPhone; touch behaviour and browser chrome are unverified.

---

## 1 · ⚠️⚠️ Highest consequence first

### F-1 · Nine controls on the home page do nothing, and eight panels can never be read
**What a visitor experiences:** the home page shows a day-in-the-life timeline with hour buttons
**07:00 · 09:00 · 11:00 · 13:00 · 15:00 · 17:00 · 19:00 · 21:00 · 23:00**. They look like tabs, they carry
`role="tab"` and `aria-selected`, and **clicking any of them does nothing at all**.

**Measured.** Clicking `07:00`: the nine panel heights were `[0,0,0,0,178,0,0,0,0]` before and
`[0,0,0,0,178,0,0,0,0]` after; every `aria-selected` value unchanged. `marketing.js` binds exactly four
selectors — `data-lensbtn`, `data-stagebtn`, `data-modchip`, `data-printbtn` — and **`data-hourbtn` is not
among them.**

⚠️⚠️ **AND THIS CONTRADICTS A STANDING RULING — reporting it loudly, as instructed.** The visible-by-default
rule is that panes ship **unhidden** and JavaScript collapses them. These nine panels ship with **inline
`style="display: none"` in the served HTML**. Eight of the nine are therefore invisible **with or without
JavaScript, forever** — the content is served, costs bytes, and no visitor can reach it. The lens panes on
the same page do follow the rule correctly (they ship visible and JS adds `hidden`), which is what makes
this a genuine divergence rather than a misunderstanding of the design.

**Severity: highest.** It is simultaneously nine dead controls, eight unreachable content panels, and a
violated safety rule.

### F-2 · The pricing page's Monthly/Annual toggle is dead
**What a visitor experiences:** on `/pricing` — the page where money is decided — a visitor clicks
**"Annual"** expecting a different number. Nothing happens.

**Measured.** Prices before the click: `€49.00 / mo`, `€99.00 / mo`. After clicking "Annual": **identical**.
The button carries `data-cycle` and a `style` attribute only — no `aria-pressed`, no handler, and
`data-cycle` is not one of the four selectors `marketing.js` binds.

⚠️ **This is not the ruled item.** *Building* the toggle was refused twice; that ruling stands and this pass
does not reopen it. What is reported here is different and new: **the control is still on the page and still
clickable.** A refused feature that leaves a live-looking button is worse than no button.

### F-3 · "Download pricing as PDF" is a dead button
`/pricing` carries `<button>Download pricing as PDF</button>` with a `style` attribute and nothing else —
no form, no handler, no href. A visitor clicks it and receives nothing, with no message.

### F-4 · The cookie banner is rendered twice on every page
**Measured:** two `<h2>Cookies on this site</h2>` on `/`, `/pricing`, `/product`, `/contact` and
`/example-scenarios/city-hotel` — and correspondingly two "That's fine" and two "Decline non-essential"
buttons. A visitor sees a duplicated consent block; which of the two buttons governs consent is not
evidenced here and should be established before it ships.

### F-5 · Every page scrolls sideways on a phone
**Measured on the real 375px viewport:** `documentElement.scrollWidth` **388** against `clientWidth` **375**
— **13px of horizontal overflow**, and `scrollWidth > clientWidth` on **all 19 pages**.

**What a visitor experiences:** the page slides left-right under the thumb, and the right edge of the
layout is cut. ⚠️ The largest single offender is a **560px-wide cookie table** ("Every cookie this site
sets") extending 225px past the viewport; the hour-timeline buttons reach x=754px but sit inside a
horizontally-scrolling strip, so they are **not** the page-level cause. The page-level figure is 13px.

### F-6 · Two page titles render a literal `&amp;`
**Measured in the served HTML:**
- `/for-apartments` → `<title>Serviced apartment &amp;amp; aparthotel management software | Vyrobel</title>`
- `/for-villas` → `<title>Villa &amp;amp; vacation rental management software with owner portal | Vyrobel</title>`

The ampersand is **double-escaped**, so the browser tab and any search result show
**"Serviced apartment &amp; aparthotel…"** literally. Visible to every visitor and every crawler.

### F-7 · Heading structure defects on three pages
| Page | Measured | Consequence |
|---|---|---|
| `/contact` | **zero `<h1>`** — the top heading is `<h2> Tell us how your properties run` | a screen-reader user gets no page title in the heading tree; the page has no top-level heading for indexing |
| `/try-booking` | **two `<h1>`** — "See what a direct booking looks like" and "You're all set, Guest" | the second is a preview panel; the page claims two subjects |
| `/features` | heading order jumps **H1 → H3** with no H2 | a level is skipped, so heading navigation misreports the outline |

### F-8 · `/pricing` links to an anchor that does not exist
`/pricing` contains a link to **`/contact#form`**. `/contact` has **no element with `id="form"`** — the
visitor lands at the top of the page with nothing highlighted and no indication of where to go.
⚠️ Every other in-page anchor used across the site resolves: `#calculator` on `/`, `#owners`/`#guests`/
`#operators` on `/product`, `#owners`/`#guests` on `/features`, `#demo` on `/contact` — all present.

### F-9 · Control borders are effectively invisible — the existing probe's blind spot
`tools/contrast-*` samples only elements that paint their own text, so **it has never measured a border**.
Measured here on `/`, 15 bordered controls: **10 fall below the 3:1 non-text-contrast threshold.**

| Control | Border vs its background |
|---|---|
| Hour buttons `07:00`–`23:00` (×9) | **1.02 : 1** |
| "Daylight" | **1.00 : 1** |

At 1.0–1.02 the border is the same colour as what it sits on: the control's edge cannot be seen at all.
⚠️ This may be deliberate styling on a decorative timeline — it is reported as **measured**, and whether it
is acceptable is a design judgement, not something this pass rules on.

### F-10 · A dead "Join" control on all 19 pages
Every page footer carries a `<button>Join</button>` outside any form, with no handler. 19 pages, one dead
control each.

### F-11 · The skip link is the third tab stop
Measured on `/example-scenarios/city-hotel` (35 focusable stops): the order begins
**1. logo link → 2. an empty `<label>` → 3. "Skip to content"**. A keyboard user must tab twice before
reaching the control whose entire purpose is to be first. Also **3 of 35 stops have no visible focus ring**.

### F-12 · Tap targets below 44px throughout
Measured at 375px, count of focusable controls under 44px tall per page: `/` **51** · `/try-booking` **95** ·
`/contact` **41** (including an `<input>` measuring **1px** and one at 22px) · `/features` **42** ·
most other pages **29–34**. Footer links measure 16–18px.

---

## 2 · ⚠️ What the JavaScript actually does — it works, and this is the first proof

⚠️ **Four controls were clicked, tabbed to and driven by keyboard. All four work correctly**, with the full
ARIA tab pattern. This had never been demonstrated before today.

| Control | Click | Keyboard | Result |
|---|---|---|---|
| **Home audience lenses** (`/`, 3 tabs) | ✅ | ✅ `ArrowRight` advances, `Home` returns to first | `aria-selected` and roving `tabindex` both move; the correct pane shows (1010px → 980px → 883px) and focus follows |
| **Product stage stepper** (`/product`, 8 stages) | ✅ | ✅ `ArrowRight`, `End` | selection moved 1 → 4 → 5 → 8; exactly one panel visible at each step |
| **Module filter chips** (`/modules`, 4 chips) | ✅ | — | 18 modules → **11** on "operator" → back to **18** on "all"; `aria-pressed` moves correctly; a real empty-state element exists and stays hidden while results remain |
| **Story print** (3 story pages) | ✅ | — | `window.print()` genuinely invoked (verified by interception, not by reading the handler) |

### With JavaScript disabled
Measured from the **served HTML**, which is exactly the no-JS state:

- ✅ **The lens panes obey the rule** — they ship with **no** `hidden` attribute and no inline hiding. All
  three audience panes are readable with scripts blocked. Same for the product stages and the module list.
- ⚠️ **The hour panels do not** (F-1): 8 of 9 ship `display: none` inline and stay hidden.
- **Site-wide count of panes hidden in the served HTML:** `/` **8** · `/pricing` **2** · each story page
  **2** · `/modules` **1** · `/try-booking` **1** · all other pages **0**.

---

## 3 · Console and network

**Checked live on 5 pages** (`/`, `/pricing`, `/product`, `/modules`, `/example-scenarios/city-hotel`):

- ✅ **Zero console errors, zero warnings** on all five.
- ✅ **No 404s.** Every referenced asset resolves: `/fonts/fonts.css` (11,257 B) and
  `/js/marketing.js` (7,925 B), plus **9 self-hosted `.woff2` files, all HTTP 200**, totalling ~256 KB.
- ✅ **No external origins requested at all** — no CDN, no Google Fonts, no analytics, no trackers. Nothing
  leaves the origin, so there is no mixed content and no third-party privacy exposure.
- ✅ **No CSP violations** on the `APP_URL` origin.

**Measured HTML weight** (served bytes): `/` **229,754** · `/try-booking` **157,038** ·
`/features` **156,766** · `/modules` **120,409** · `/product` **117,753** · `/how-it-works` **105,420** ·
`/pricing` **99,506** · remainder **82,637 – 95,359**.
⚠️ The home page at **~230 KB of HTML** before fonts is worth noting for a phone on a slow connection.
**Load time was not measured** and is not claimed.

---

## 4 · Measurable SEO — and only that

⚠️ **No keyword analysis, no content-gap advice, no score.** Every line below is a rendered fact.

✅ **Correct on all 19 pages:** a unique `<title>` (45–75 characters) · a unique `<meta name="description">`
(103–249 characters) · `lang="en"` · a viewport meta · **6 `og:` tags and 3 `twitter:` tags** · zero images
(so no missing `alt` — the site is imageless by construction) · **zero vague link texts** ("click here",
"read more" and similar: none anywhere).

⚠️ **Measured absences and defects:**
- **JSON-LD structured data: zero on all 19 pages.** No `Organization`, `Product`, `FAQPage` or
  `BreadcrumbList`. This is a present-tense fact, not a recommendation.
- **Description length:** `/features` at **249** characters will be truncated in most result listings;
  `/about` at **103** is short. Reported as measured, not as a rule.
- Heading defects — F-7. Double-escaped titles — F-6. Broken anchor — F-8.

✅ **The navigation does NOT link to any unrouted page.** Every internal `href` across all 19 pages was
collected and every one resolves to a routed path. **`/faq`, `/glossary`, `/changelog` and `/docs` are
unrouted and are linked from nowhere** — the suspected broken promise does not exist. See FP-3.

---

## 5 · ⚠️ Cannot be settled until a domain exists — go-live items, not findings

These are **premature, not broken**, and are listed so they are not lost:

| Item | State today | What will be needed |
|---|---|---|
| `<link rel="canonical">` | **Present on all 19 pages**, pointing at `http://localhost:8000/…` | re-point at the real origin; it is wired, only the host is a placeholder |
| `og:url` | Present, same localhost host | same |
| `sitemap.xml` | **Absent** (`/sitemap.xml` → 404) | generate one covering the 19 routed pages; absence today is expected |
| `robots.txt` | **Present** (`/robots.txt` → 200) | review its contents against the real host before launch |
| Absolute asset URLs | `asset()` emits `APP_URL`-absolute URLs | see FP-1 — correct once `APP_URL` matches the deployed host |
| HTTPS / HSTS / mixed content | untestable over plain-HTTP localhost | re-check after TLS |

---

## 6 · False positives — what looked wrong and was not

**FP-1 · "The marketing JavaScript is blocked by CSP and never runs."** My first load was on port **8200**
while `APP_URL=http://localhost:8000`. `asset()` emits an **absolute** URL, so the script was cross-origin
and `script-src 'self'` blocked it — one console error, and every control dead. **On the `APP_URL` origin it
loads and runs correctly.** Reported as a **risk, not a defect**: any environment whose serving host differs
from `APP_URL` loses all marketing JS silently, with only a console error to show for it. Worth knowing at
deploy time; not broken today.

**FP-2 · "There are no tab groups on the home page."** My first probe searched for `data-tabgroup`. The real
selectors are `data-lensbtn` / `data-stagebtn` / `data-modchip` / `data-printbtn`. **My selector was wrong,
not the page.** The controls are present and work.

**FP-3 · "The nav links to unrouted pages."** Anticipated, and false: no link to `/faq`, `/glossary`,
`/changelog` or `/docs` exists anywhere in the 19 pages.

**FP-4 · "8 of 9 panels hidden means the visible-by-default rule is broken site-wide."** It is broken for
the hour timeline **only** (F-1). The lens, stage and module panes all ship visible and are collapsed by
JavaScript exactly as designed.

**FP-5 · "The home page is 754px wide on a phone."** Elements do extend to x=754, but inside a
horizontally-scrolling container. The page-level overflow is **13px** (F-5).

---

## 7 · Not re-reported (already ruled)

The 22 ⚠️ caveat markers (deliberate, ruled to stay) · placeholder prices `€49`/`€99` (a founder decision;
they render correctly from the database) · the unrouted pages · the ~90 stripped handlers as a general fact
— though **the specific controls a visitor can still click are named above**, which is the part that was
asked for.

---

## 8 · Status

**Nothing was fixed. Nothing was regenerated. No test was added. `git diff` is EMPTY** — this file is the
only artefact, and it is untracked. The suite was **not** run: the tree is unchanged, so an 11-minute run
would prove nothing. The dev server started for this audit has been stopped.
