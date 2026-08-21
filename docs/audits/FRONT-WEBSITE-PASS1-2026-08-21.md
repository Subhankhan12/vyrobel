# Front website — PASS 1: ANALYSE the design + AUDIT the public realm

**2026-08-21. READ-ONLY.** Nothing built, no route changed, no code written, nothing moved or renamed in the
design directory. Money as seeded, verified read-only: **929 / 10,912,000 / 0 `unit_rate_overrides` / 41 /
16 / 0**. Baseline `main` `60a1e78`.

---

## ⚠️ THE FOUR HEADLINES

**1. There is almost no collision.** The design wants 79 URLs. Vyrobel occupies **one** of them — `/` — and
what sits there today is the **stock Laravel welcome splash** (`resources/views/welcome.blade.php`,
`<title>{{ config('app.name', 'Laravel') }}</title>`). ⚠️ **The sealed storefront never had `/`.** It lives
on `/book/{property}/…` and `/stay/{organization}/…`, neither of which the design claims. There is no
`Route::fallback` and no top-level wildcard. **The "who owns `/`" question the brief anticipated does not
arise** — nothing of value is there to displace.

**2. SEO: we are in World (ii), unambiguously — Vyrobel has essentially none, and the design brings a
complete kit.** No sitemap, no JSON-LD, no canonical, no hreflang, no OG/Twitter, no meta description, no
SEO package anywhere in the app. `public/robots.txt` is **two lines**. The design ships a 79-URL sitemap
with 155 hreflang alternates, per-page canonical + OG + Twitter + description, and two JSON-LD blocks.

**3. ⚠️⚠️ THE BLOCKING RISK IS NOT TECHNICAL — IT IS FABRICATED CONTENT.** The design carries its own
honesty ledger, `data/vyrobel-figures.json`, and it says: **`"status": "ILLUSTRATIVE — replace before
launch"`**, with `"real": null` on **every** figure, customer and testimonial, and **`"consent": false`** on
every named person. Publishing it as-is means publishing invented metrics, three invented customers and
three named people who do not exist. ⚠️ **Under this project's own #39 that is a refusal, not a caveat** —
and unlike #39 elsewhere, this one has legal exposure (advertising standards, and named "quotes" without
consent).

**4. The pages are React, client-rendered.** Each `.dc.html` is a dc-runtime bundle whose `<body>` contains
only a loader; everything renders from a JS bundle against `react@18.3.1` + `react-dom@18.3.1` from unpkg.
⚠️ **They are not directly usable as Blade.** The good news is the underlying template is clean semantic
HTML, extractable — see §A.7.

---

# PART A — THE DESIGN (`docs/design/front/`)

⚠️ **Note the path.** At audit time it was `docs/design/**Front**/` (capital F), resolving as lowercase only
because macOS is case-insensitive — a Linux host would not forgive that. ✅ **Gate 0 (2026-08-21) renamed it
to the canonical lowercase `docs/design/front/`** (matching its siblings `raw/`, `decoded/`) and updated the
references here. ⚠️ **The directory remains UNTRACKED by design** — 102 MB; Gate 0 measured the tracking
options and surfaced the choice to the founder (see `DECISIONS.md`), and added a protective `.gitignore`
entry so a stray `git add -A` cannot sweep it in silently. The honesty ledger itself IS tracked:
`resources/marketing/content.json`.

## A.1 Scale

| | |
|---|---|
| Pages | **84** `.dc.html` |
| Total size | **102 MB** (~1.6 MB per page — fonts and components inlined per page) |
| Extras | `index.html` (a launcher listing all 84) · `sitemap.xml` · `robots.txt` · `og-cover.png` (854 KB) · `favicon.svg` · `favicon-32.png` · `apple-touch-icon.png` · `site.webmanifest` · `data/vyrobel-figures.json` |

## A.2 Page inventory — 84 pages in 8 groups

| Group | N | Pages |
|---|---|---|
| **Core** | 7 | Home · Product · Features · Modules · How It Works · Pricing · Try Booking |
| **Sales** | 6 | Alternatives · Compare · Switching · For Hotels · For Apartments · For Villas |
| **Customer stories** | 4 | Case Studies · Story Cyclades · Story Lisbon · Story Rotterdam |
| **Resources** | 8 | Resources + 7 articles (Direct · Housekeeping · OOS · Owners · Pricing · Statement · Trust) |
| **Documentation** | 19 | Docs · FAQ · Glossary + 16 guides (Setup · Reservation · Checkin · Arrival · Access · Channels · Housekeeping · Rates · Refunds · Reports · Payouts · Statements · Storefront · Settings · Security · GDPR) |
| **Company, trust & system** | 10 | About · Careers · Changelog · Contact · Demo Confirmed · Integrations · Privacy · Security · Terms · **404** |
| **Internal** | 3 | ⚠️ Launch Checklist · Legal Packet · Photo Brief — **working documents, not public pages** |
| **Translations** | 27 | 9 pages × EL/ES/NL (Home · Product · Features · Modules · How It Works · Pricing · Switching · Alternatives · Contact) |

⚠️ **The three "Internal" pages must not be published.** They are the founder's own working docs. Note they
are correctly **absent from the sitemap** (79 URLs, not 84 — 84 minus 3 internal minus … see A.3).

## A.3 IA — the URL map the design intends

Authoritative, from its own `sitemap.xml` (79 `<loc>`):

```
/                          /product        /features      /modules      /how-it-works
/pricing                   /switching      /alternatives  /compare      /try-booking
/for-hotels                /for-apartments /for-villas
/case-studies              /case-studies/{cyclades|lisbon|rotterdam}
/resources                 /resources/{7 slugs}
/docs                      /docs/{16 slugs}     /faq      /glossary
/about /careers /changelog /contact /integrations /privacy /security /terms
/{el|es|nl}/               /{el|es|nl}/{product|features|modules|how-it-works|pricing|switching|alternatives|contact}
```

⚠️ **Slugs are NOT filename-derived** — e.g. `Vyrobel Guide Setup` → `/docs/first-property`, `Vyrobel
Article Direct` → `/resources/growing-direct-bookings`. A build pass must read the sitemap, not guess.
⚠️ **Not in the sitemap:** the 3 internal pages, `Demo Confirmed` (a post-submit state), `404`, and — see
B.4 — **anything of Vyrobel's own**, including the storefront.

## A.4 Design system — ⚠️ it IS Vyrobel's system

This is the pass's best news. Measured against `resources/views/components/design/tokens.blade.php` (the
ONE canonical 31-token palette, DO-TOKEN-1 / §N.8f):

| Token | App | Design | |
|---|---|---|---|
| Background gradient | `#F6C89B → #F1B896 → #EBD9CF → #D9D6D2` | **same four stops** (+ a 5th `#E7DCD4`; stop %s differ 0/20/46/70 vs 0/32/62/100) | ✅ |
| Ink | `rgb(43,35,32)` | `#2B2320` | ✅ **identical** |
| Accent | `rgb(194,65,12)` | `#C2410C` | ✅ **identical** |
| Display / body font | Sora / Inter | Sora / Inter | ✅ **identical** |
| Glass card | `rgba(255,252,248,.58)` + blur/saturate | `rgba(255,252,248,.6)` + `blur(20px) saturate(1.15)` | ✅ effectively same |
| Muted text | `--vy-muted rgb(95,88,83)` | `#4A403A` (108 uses) | ⚠️ **different** |
| Link colour | `--vy-accent-text rgb(147,42,7)` | `#A8380B` (51 uses) | ⚠️ **different** |

**Verdict: MATCHES.** Two secondary tones diverge and are a founder call — adopt the app's tokens (one
definition, §N.8f) or ratify the design's two as marketing-only variants.

⚠️ **But the values are inline hex, not `var(--vy-*)`.** A faithful Blade port should bind them to
`<x-design.tokens />` rather than copy hex — otherwise the marketing site becomes a second palette free to
drift from the product's, which is precisely what §N.8f exists to prevent.

## A.5 Assets

- ⚠️⚠️ **There are no photographs.** The Home page uses **six `<image-slot>` placeholders** —
  *"Drop your hero property photo"*, *"A room or interior"*, *"A pool, terrace or view"*, *"Breakfast,
  detail or staff"*, *"The neighbourhood"*, *"Drop a wide property photo"*. `Vyrobel Photo Brief.dc.html`
  is the brief for photography **that does not exist yet**. **Every page will need real images sourced or
  licensed before launch.**
- Icons are **inline SVG** (76 `<svg>`, 93 `<path>` on Home alone) — no icon font, no sprite. Ports cleanly.
- `og-cover.png` is **854 KB** for a 1200×630 card — wants compressing.
- ⚠️ **Fonts conflict.** The design loads **Google Fonts CDN**
  (`fonts.googleapis.com/css2?family=Sora…&family=Inter…`). Vyrobel deliberately **self-hosts**
  (`public/fonts/fonts.css`, *"no third-party font request"*, audit F6-6). The port must use the
  self-hosted path — this is a privacy/GDPR posture the project already decided.

## A.6 Forms and interactivity

| Surface | What it is | What it needs server-side |
|---|---|---|
| **Contact** — contact form | name · email · company · units band (1–10 / 11–60 / 60+) · topic (demo / pricing / migration / other) · message | POST + validation + throttle + a mailable + a store; today the submit is a **JS handler with no `action`** |
| **Contact** — demo booker | day picker → slot picker → name + email → confirm | Real availability, a booking store, a confirmation mail — **or** honestly reduce to a request form. ⚠️ **A slot picker that books nothing is exactly the #39 shape.** |
| **Demo Confirmed** | the post-submit state | a real redirect target |
| **Home hero** | a **units slider** (`<input type="range" min=4 max=200 value=24>`) driving a live ROI figure | pure client-side; portable as Alpine — ⚠️ but the *numbers it produces* are the illustrative figures of §3 |
| **ROI Calculator** | a shared component | same |
| **Support Widget** | a shared component, `hint-size="0,0"` | ⚠️ unknown backend — likely a third-party chat; **needs a decision** |

Shared partials (via `<dc-import>`): **Site Nav · Site Footer · ROI Calculator · Support Widget** — four
components that map naturally onto Blade components.

## A.7 ⚠️ What cannot be reproduced as-is — the obstacles, named now

1. ⚠️⚠️ **React 18 client-rendering.** `ext_resources` pulls `react@18.3.1` and `react-dom@18.3.1` UMD from
   **unpkg.com**. The `<body>` ships only `<div id="__bundler_loading">Unpacking...</div>`.
   **A crawler with JS disabled sees nothing** — which would defeat the entire SEO purpose.
   ✅ **Mitigation: the content is recoverable.** The `__bundler/template` block is a JSON-encoded string of
   **clean semantic HTML** — 379 `<div>`, 369 `<span>`, 35 `<p>`, 17 `<h2>`, 13 `<section>`, 24
   `<blockquote>`, plus `<helmet>`, `<dc-import>` and `<image-slot>` custom elements. Blade reproduces this
   directly; only the interactive bits (slider, calculator, tabs) need re-implementing in **Alpine**, which
   the operator/owner shells already use.
2. **CDN font load** → must become the self-hosted `/fonts/fonts.css` (A.5).
3. **CDN React** → drop entirely; server-render in Blade.
4. **`{{ … }}` handlebars in the template** (`sc-camel-on-click="{{ submit }}"`) will **collide with Blade's
   own `{{ }}`** if copied verbatim. A mechanical hazard, easily handled — but it will bite a careless port.
5. **Templates are per-page inlined**, so the four shared components appear 84 times. Extract once.
6. **No build step and no external service** beyond the two CDNs — otherwise self-contained. ✅
7. **Font licence:** Sora and Inter are both **SIL OFL** — no licence obstacle, and both are already
   self-hosted in the app.

---

# PART B — VYROBEL'S EXISTING PUBLIC REALM

## B.1 The public route table (474 routes total; these are the unauthenticated GETs)

| Path | Served by | Money-carrying? |
|---|---|---|
| **`/`** | **a closure → `view('welcome')`** — [routes/web.php:211](../../routes/web.php#L211) | ❌ **stock Laravel splash** |
| `/book/{property}` | `BookingEngineController@browse` | ⚠️ **STOREFRONT** |
| `/book/{property}/search` · `/rooms/{roomType}` | `BookingEngineController` | ⚠️ **STOREFRONT** |
| **`/book/{property}/checkout`** | `BookingEngineController@checkout` → **`booking.reserve`** | ⚠️⚠️ **THE MONEY PATH (#17)** |
| `/book/{property}/confirmation/{quote}` · `/reservation/{quote}` | `BookingEngineController` | ⚠️ **STOREFRONT** |
| `/stay/{organization}` · `/stay/{organization}/search` | `StorefrontLandingController` | ⚠️ **STOREFRONT (org landing)** |
| `/login` · `/signup` · `/forgot-password` · `/reset-password/{token}` · `/two-factor-challenge` · `/login-approval*` | Auth | no |
| `/invite/{token}` · `/team-invite/{token}` · `/unsubscribe/{guest}` | tokened, public by necessity | no |
| `/admin/*` | guarded `Authenticate:admin` + 2FA + **`NoIndex`** | no |
| `/horizon/*` | Horizon's own gate | no |

⚠️ **A correction to my own first probe (§N.29, form (a)).** My initial filter reported `/admin` and
`/owner` as *public* because it grepped lowercase `auth` and the real middleware string is
`Illuminate\Auth\Middleware\Authenticate`. **Both are properly guarded.** A filter that names the wrong
thing reports a clean-looking falsehood — recorded rather than silently fixed.

## B.2 ⚠️ Collisions — the complete list

| Design URL | Owned today by | Collision |
|---|---|---|
| **`/`** | `view('welcome')` — the Laravel splash | ⚠️ **the only one**, and it displaces nothing of value |
| all 78 others | **nothing** | ✅ none |

**Structural safety confirmed:** no `Route::fallback`, no top-level single-segment wildcard
(`/{slug}`) — so `/about`, `/pricing`, `/docs/...` cannot be swallowed by an existing route, and adding them
cannot shadow `/book/*` or `/stay/*`, which are distinct prefixes.

⚠️ **Two adjacent, non-URL risks that ARE real:**

- **`/try-booking`** — the design's own demo booking page. It **overlaps in purpose** with the live
  storefront (`/book/seaside-demo`). Not a route clash; a **product-meaning clash**. Whether "Try booking"
  links to the real demo storefront or renders a fake one is a founder call — ⚠️ a *fake* one would be #39.
- **`robots.txt`** — the design's version is richer than the app's and would replace it. See B.4.

## B.3 The public shell

⚠️ **A second correction to my own framing:** shells are not in `components/layouts/` (that holds only
`app`/`operator`/`owner`). They live at `components/<realm>/shell.blade.php`, and there are **five**:
`public` · `guest` · `owner` · `operator` · `admin`.

`components/public/shell.blade.php` (240 lines) is the storefront chrome: property header, centered
content, footer, no nav, no logged-in user, **no Livewire** (server-rendered, rate-limit-friendly). It
loads `<x-design.tokens />` first, then its own overrides.

**Its `<head>` has a `<title>` and nothing else** — no description, no canonical, no OG.

**Recommendation (a founder call, §C.5):** the marketing site should get its **own sixth shell**, not reuse
the public one. They differ in chrome (marketing needs a full nav + a large footer; the storefront
deliberately has neither) and the storefront shell is part of a **sealed, money-verified** surface. ⚠️
Editing it to serve marketing pages would modify a sealed surface for a cosmetic reason.

## B.4 ⚠️ SEO — **World (ii): Vyrobel has essentially none**

Stated without hedging, each line verified:

| Artifact | Vyrobel | The design |
|---|---|---|
| `robots.txt` | ⚠️ **2 lines** — `User-agent: *` / `Disallow: /admin`. **Does NOT disallow `/app/`, `/owner/`, `/guest/`** | 11 lines — disallows `/app/ /owner/ /guest/ /login /signup /forgot-password /set-password`, declares the sitemap |
| sitemap | ❌ **none anywhere** | ✅ **79 URLs, 155 hreflang alternates**, changefreq + priority |
| JSON-LD | ❌ **none** (`grep application/ld+json` → 0 in `resources/` and `app/`) | ✅ **`Organization` + `SoftwareApplication`** |
| canonical | ❌ none | ✅ per page |
| hreflang | ❌ none | ✅ en/nl/es/el + `x-default` |
| OG / Twitter | ❌ none | ✅ full set + `og:image` 1200×630 |
| meta description | ❌ none | ✅ per page |
| `theme-color`, webmanifest, favicons | ❌ none | ✅ present |
| SEO package | ❌ none in `composer.json` | — |
| `NoIndex` middleware | ✅ exists — `X-Robots-Tag: noindex, nofollow, noarchive`, applied **only** to `/admin` ([routes/web.php:1375](../../routes/web.php#L1375)) | — |

⚠️ **Two live gaps this audit surfaces independently of the marketing site:**

1. **The private portals are not disallowed.** `/app/`, `/owner/`, `/guest/` are absent from
   `public/robots.txt`. They redirect to login, so little leaks — but they are crawl-budget and
   surface-area noise, and the design's robots.txt already fixes it.
2. ⚠️ **The storefront has NO SEO at all, and the design's sitemap does not cover it.** Property pages
   carry a bare `<title>` — no description, no canonical, no `LocalBusiness`/`Hotel`/`Offer` JSON-LD, and
   they appear in **no sitemap**. **For a product whose core promise is commission-free direct
   bookings, the direct-booking pages are currently invisible to search.** That is arguably a bigger SEO
   win than the marketing site, and it is **out of scope here** — flagged, not actioned.

## B.5 Auth — the portal entry map, and the feasibility of one login

**Guards (three realms, deliberately separate):**

| Realm | Login route | Guard |
|---|---|---|
| Operator **+** Owner | **`/login`** — `Auth\LoginController@create` | `web` (User) |
| Guest | **`/guest/login`** — `Guest\GuestLoginController` | **`guest`** (Guest model) |
| Platform admin | **`/admin/login`** — `Admin\Auth\AdminLoginController` | **`admin`** (`platform_admins`) + `EnsurePlatformAdminTwoFactor` |

⚠️ **The role dispatch the founder wants ALREADY EXISTS — for two of the four.**
`Auth\Controllers\Concerns\RedirectsByRole::homeFor(User $user)`:

```php
$isOwner = …Owner::query()->where('user_id', $user->getKey())->exists();
if ($isOwner)                                          return route('owner.dashboard');
if ($user->memberships()->where('status','active')->exists()) return route('app.dashboard');
return null;                                            // authenticated but placed nowhere → refused
```

It is reused by `LoginController`, `PasskeyLoginController`, `TwoFactorChallengeController` and
`LoginApprovalController` — one definition, four callers (§N.8f).

**Feasibility verdict — three honest tiers:**

| | What | Feasible? |
|---|---|---|
| **Tier 1** | One marketing "Log in" button → `/login`, which already dispatches **operator vs owner** | ✅ **Today. Zero auth change.** |
| **Tier 2** | Add guest to the same form — one email box, three guards tried in turn | ⚠️ **Possible but security-significant.** It would need a resolution order across two user tables, and would make **cross-realm account enumeration** newly possible (one form revealing whether an address is a guest or a staff user). The realms are separate **by construction** — a guest is refused from `/app` and `/owner` and that gate is #26, a real closed hole. |
| **Tier 3** | Include **platform admin** | ⛔ **Recommend against.** `/admin` is `NoIndex`, separately guarded, 2FA-forced, on its own table. Putting it behind a public marketing login widens the platform's most sensitive surface for a cosmetic gain. |

⚠️ **The lowest-risk shape that meets the goal as a user experiences it:** one prominent **Log in** →
`/login` (operator + owner, already role-dispatching), plus a small **"Booked a stay? Guest sign-in"** link
→ `/guest/login`. The user sees one entry point; the realm separation stays exactly as sealed.

---

# PART C — PROPOSED INTEGRATION PLAN

⚠️ **Proposed, not decided.** Everything in §D is the founder's.

## C.1 Route mapping — no collision

A dedicated `routes/marketing.php`, included by prefix-free top-level registration **after** the storefront
group so ordering is explicit and reviewable:

```
/                       → MarketingController@home        (replaces view('welcome'))
/{product|features|modules|how-it-works|pricing|switching|alternatives|compare|try-booking}
/{for-hotels|for-apartments|for-villas}
/case-studies  /case-studies/{story}         (whitelisted slugs — never a free wildcard)
/resources     /resources/{article}          (whitelisted)
/docs          /docs/{guide}                 (whitelisted)
/faq /glossary /about /careers /changelog /contact /integrations /privacy /security /terms
/{locale}/{page}        where locale ∈ {el,es,nl}   (constrained, so it cannot swallow /book or /stay)
```

⚠️ **The locale prefix is the one genuine route hazard.** `/{locale}/...` must be `->whereIn('locale',
['el','es','nl'])`. Unconstrained it would match `/book/...` and shadow the storefront — **the single
change in this whole project capable of breaking the money path.** It must be constrained *and*
mutation-proven (a test asserting `/book/seaside-demo` still resolves to `booking.browse`).

## C.2 Blade reproduction

- **A sixth shell** `components/marketing/shell.blade.php` — nav, footer, SEO slots. Not the storefront's.
- **Components:** `<x-marketing.nav>`, `<x-marketing.footer>`, `<x-marketing.roi-calculator>`,
  `<x-marketing.support-widget>` — the design's own four `<dc-import>`s, extracted once.
- **Tokens:** bind to `<x-design.tokens />`; do not copy hex (§N.8f).
- **Interactivity → Alpine** (already in operator/owner). No React, no CDN.
- **Content:** extracted from each bundle's `__bundler/template`, which is clean semantic HTML.
  ⚠️ Escape the design's `{{ }}` handlebars before they meet Blade's.
- **Fonts:** the self-hosted `/fonts/fonts.css`. Never the Google CDN.

## C.3 SEO — World (ii), so the front site brings it

Carry the design's kit over wholesale, plus:
- a **generated** sitemap (a route, not a static file) so it cannot go stale;
- `robots.txt` replaced with the design's — ⚠️ **adding `/book/` to the *sitemap*, not the disallow list**;
- an `<x-marketing.seo>` component taking title/description/canonical/hreflang/OG per page;
- ⚠️ **`APP_URL` must be right in production** — every canonical and hreflang depends on it.

## C.4 The login entry

Tier 1 (§B.5): a **Log in** button → `/login`, plus a guest sign-in link → `/guest/login`. No auth change.

## C.5 Divergence register

One entry per page, in the existing discipline: ✅ KIND-1 layout → fix · 🚫 KIND-2 fabricated content →
**refuse** · 📐 KIND-3 beyond the boundary → document. ⚠️ **The figures/testimonials of §3 are the largest
KIND-2 finding in this project's history** and every one needs an explicit ruling.

## C.6 Suggested gates

| Gate | Covers |
|---|---|
| **0** | ⛔ **The content ruling** (§D.1) — no page can be built until the fabricated figures are resolved. **And settle the design directory** (casing + a measured tracking decision) before anything depends on 102 MB of untracked files. *(Done in Gate 0 — renamed to `front/`, tracking options measured and surfaced.)* |
| **1** | Foundation: the marketing shell, nav, footer, tokens, SEO component, the constrained locale route + **the storefront-still-resolves proof** |
| **2** | Core 7 (Home · Product · Features · Modules · How It Works · Pricing · Try Booking) |
| **3** | Sales 6 + Customer stories 4 (⚠️ gated on Gate 0) |
| **4** | Docs 19 + Resources 8 — the SEO long tail |
| **5** | Company 9 + 404 + the **Contact/demo backend** (real POST, validation, throttle, mail) |
| **6** | The 27 translations + hreflang wiring |
| **7** | SEO finalisation: generated sitemap, robots, JSON-LD, OG assets, and a render-verify pass |

---

# ⚠️ PART D — FOUNDER DECISIONS (surfaced, not picked)

| # | Decision | Why it is yours |
|---|---|---|
| **D1** | ⚠️⚠️ **The fabricated figures, customers and testimonials.** Options: (a) replace with real, consented ones; (b) remove those sections and ship honest; (c) rewrite as clearly-labelled illustrations. ⚠️ **(d) ship as-is is not on the table** — invented named people without consent. | Business, legal, and #39 |
| **D2** | **Does `/` become the marketing home?** My read: yes — it currently holds the Laravel splash. But it is your call, and it is the only URL with an existing occupant. | Product |
| **D3** | **`/try-booking`** — link to the real demo storefront, or build a separate demo? ⚠️ A simulated booking flow would be #39. | Product |
| **D4** | **The Support Widget** — a third-party chat (which one, what does it cost, GDPR?), your own inbox, or drop it? | Business |
| **D5** | **The demo booker** — real scheduling (needs a calendar backend) or reduce to a request form? | Product |
| **D6** | **The two divergent tones** (`#4A403A`, `#A8380B`) — adopt the app's tokens, or ratify these as marketing variants? | Design |
| **D7** | **The 27 translations** — ship all four locales at launch, or English first? They are the largest single chunk of work. | Business |
| **D8** | **Photography** — 6+ slots per page with nothing in them. Source, shoot, or licence? **This gates the visual quality of every page.** | Business |
| **D9** | **One login (Tier 2/3)** — accept the enumeration trade-off to add guest to `/login`, or keep the realms separate with two links? My read: keep them separate. | Security |
| **D10** | **Storefront SEO** (§B.4) — a separate pass? It may be worth more than the marketing site. | Product |

---

# ⚠️ PART E — RISKS TO THE SEALED PRODUCT

| # | Risk | Severity | Mitigation |
|---|---|---|---|
| **E1** | ⚠️⚠️ **An unconstrained `/{locale}/...` route shadows `/book/...`** and breaks the money path | **HIGH — the only one that can break money** | `->whereIn('locale', […])` **plus** a test asserting `/book/seaside-demo` still resolves to `booking.browse` |
| **E2** | **Taking `/` changes the app's entry** for anyone with it bookmarked | LOW | it is the Laravel splash |
| **E3** | **Editing the sealed `public/shell`** to serve marketing | MEDIUM | a separate sixth shell; do not touch the storefront's |
| **E4** | **A second palette** copied as hex, drifting from `--vy-*` | MEDIUM | bind to `<x-design.tokens />` (§N.8f) |
| **E5** | **robots.txt replaced carelessly** — disallowing `/book/` would de-index the money pages | **HIGH** | ⚠️ the storefront must be **allowed and sitemapped**, never disallowed |
| **E6** | ⚠️ **Publishing fabricated figures / named testimonials** | **HIGH — legal + reputational** | D1, before any build |
| **E7** | **84 pages × ~600 lines of Blade** balloons the repo and the suite | MEDIUM | shared components; assert-render tests, not per-page snapshots |
| **E8** | ⚠️ **`docs/design/front/` is untracked** — 102 MB living on one machine *(casing settled in Gate 0: renamed `Front`→`front`; tracking measured + surfaced as the founder's call; `.gitignore`-guarded against an accidental sweep)* | MEDIUM | founder picks a tracking option before Gate 1 leans on the files |
| **E9** | **CDN React/fonts** left in a port | MEDIUM | server-render; self-hosted fonts (the F6-6 decision) |

---

## Status

| | |
|---|---|
| **Built / changed** | ⚠️ **Nothing.** Read-only: no route, no code, no Blade file; nothing moved or renamed in the design directory |
| **Money** | **929 / 10,912,000 / 0 `unit_rate_overrides` / 41 / 16 / 0** — re-verified, unchanged |
| **Design** | **84 pages**, 102 MB, React-client-rendered, content recoverable as clean semantic HTML |
| **Collisions** | ⚠️ **exactly one — `/`** — and it holds the stock Laravel splash. No fallback, no top-level wildcard |
| **SEO world** | **(ii) — Vyrobel has essentially none; the design brings a complete kit** |
| **Auth** | role dispatch **already exists** for operator+owner (`RedirectsByRole::homeFor`); guest and admin are separate guards by design |
| **Blocking item** | ⚠️⚠️ **D1 — the fabricated figures, customers and testimonials.** The design's own ledger says *"ILLUSTRATIVE — replace before launch"*, `consent: false` |
| **Next** | ⛔ **The founder rules on D1–D10.** Gate 0 (the content ruling + tracking the design directory) precedes any build |
