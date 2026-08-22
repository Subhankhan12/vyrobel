"""GATE 7 — turn convert()'s output into a Blade page, with its provenance docblock.

⚠️ THIS FILE EXISTS BECAUSE THE FIRST VERSION OF IT WAS AN INLINE HEREDOC. The per-page docblocks — which
carry WHY each claim was corrected and are the only record of the audit inside the codebase — lived nowhere,
so the next regeneration would have dropped every one of them without a word. That is the converter's own
"a post-generation patch is undone by the next generation" lesson, one level up: the GENERATOR was the
unversioned thing.

Run:  python3 g7build.py                 (regenerates all four pages)
"""
import sys, io, html, os

# ⚠️ PORTABILITY ONLY (2026-08-23) — was an absolute scratchpad path. Derived from __file__.
_HERE = os.path.dirname(os.path.abspath(__file__))
_ROOT = os.path.dirname(os.path.dirname(_HERE))
sys.path.insert(0, _HERE)
import convert as C

VIEWS = os.path.join(_ROOT, "resources", "views", "marketing", "%s.blade.php")

HEADERS = {'security': '{{-- SECURITY — front-website Gate 7.\n\n     ⚠️ REPRODUCED FROM `docs/design/front/Vyrobel Security.dc.html`, and then CORRECTED against the code.\n     The Gate 7 doc-truth audit rated 29 claims on this page: 9 TRUE, 11 PARTIAL, 7 FALSE, 2 UNVERIFIABLE.\n     Every FALSE and PARTIAL one is rewritten in the converter (transform 7), never here — a post-generation\n     patch is undone by the next generation, silently, which this project has now proven three times.\n\n     ⚠️ CORRECTED, NEVER SOFTENED. Replacing a false specific with "we take security seriously" would leave a\n     reader knowing less than before; a security page that says less is not safer, it is just vaguer. Where\n     the truth is a limitation — no 2FA enforcement, no hosting yet, no account deletion, no retention timer\n     — the limitation is what the page prints.\n\n     ⚠️ THE PAGE\'S RESTRAINT IS KEPT: it makes no claim about certifications, penetration testing, uptime, an\n     SLA or backups, and every one of those would have been false. An absent claim is a correct claim.\n\n     ⚠️ ONE FINDING HERE IS A PRODUCT DEFECT, NOT A COPY DEFECT, and is reported to the founder rather than\n     papered over: `/app/dashboard` carries no permission gate, so its "Revenue today" tile renders for every\n     back-office role including Housekeeping. The sentence "a housekeeper sees rooms, not revenue" is cut\n     back to the two clauses that ARE enforced. --}}', 'integrations': '{{-- INTEGRATIONS — front-website Gate 7.\n\n     ⚠️ REPRODUCED FROM `docs/design/front/Vyrobel Integrations.dc.html`, then corrected in the converter.\n     The audit classified every listed integration: Stripe (built, test-mode until the founder\'s live keys),\n     accounting exports (real, CSV/PDF), connected apps (REAL — a full OAuth2 server with scoped, revocable,\n     audited access), and the direct storefront\'s 0% commission (verified against the ledger).\n\n     ⚠️ THE THREE OTA CHANNELS ARE NOT CONNECTABLE, so the page says so. `Channel::create` exists exactly once\n     outside factories — in the demo seeder — so no operator can connect Booking.com, Airbnb or Expedia at\n     all; Vrbo is not modelled. The channel-manager pipeline IS built and tested against a simulated client,\n     and the page distinguishes that from an operating state rather than blurring the two.\n\n     ⚠️ THE THREE "Synced" PILLS ARE NOW "Not connected" — which is what the product\'s own channels screen\n     shows. `sync_jobs` and `webhook_events` are empty and `last_synced_at` is null: nothing here has ever\n     synced, and OperatorChannels.php refuses the word for exactly that reason. --}}', 'about': '{{-- ABOUT — front-website Gate 7.\n\n     ⚠️ REPRODUCED FROM `docs/design/front/Vyrobel About.dc.html`, MINUS ITS TEAM. Vyrobel is one person: 863\n     of 863 commits by a single author. The design presented four named staff — and two of them, "Elena\n     Kastellis" and "Joris van Dijk", are already in the honesty ledger as invented, unconsented CUSTOMER\n     testimonial authors, while a third, "Amara Reyes", is a recombination of two more. The block is DELETED\n     in the converter, not renamed: renaming an invented co-founder does not remove one, it invents a\n     different one. The D13 rename transform is explicitly scoped away from this page for that reason.\n\n     ⚠️ "The problem we kept watching" named D1\'s three invented customers — the Cyclades manager, the\n     Rotterdam family, the Lisbon hotel — as businesses observed first-hand. Those are the same three\n     unconsented fictions whose case-study pages were refused in Gate 3 and rebuilt as labelled illustrative\n     scenarios. De-specified to the pattern, which is the part that was ever true.\n\n     ⚠️ WHAT SURVIVES IS THE STRONGEST PART OF THE PAGE and is genuinely true: why the product exists, what\n     it is built to do, and four principles — two of which ("owners see performance, never a guest\'s personal\n     details"; "guest data is minimised and anonymised") are checkable product properties the audit\n     confirmed. --}}', 'contact': '{{-- CONTACT — front-website Gate 7. ⚠️ THE FORM IS REAL.\n\n     ⚠️ REPRODUCED FROM `docs/design/front/Vyrobel Contact.dc.html`, whose submit was an `sc-camel-on-click` handler binding\n     — a React handler that flipped a local flag. No <form>, no action, no endpoint. Shipping it would have\n     been a control that promises and does nothing (#39), and `key="contact"` appears 21 times across nine\n     PUBLISHED pages, so it is the destination of nearly every call to action the site makes.\n\n     ⚠️ IT NOW POSTS to `marketing.contact.store`: validated, throttled per IP, honeypotted, and written to\n     `marketing_contact_messages` INSIDE a transaction BEFORE any mail is attempted. The row is the system of\n     record and the email is a notification, because MailSafetyGuard downgrades every sending transport to\n     `log` outside production — a mail-only form would silently destroy every message sent before go-live.\n\n     ⚠️ THE DEMO BOOKER IS REMOVED (D5, still open). Its day picker, time slots and "a calendar invite is on\n     its way to your inbox" would have booked nothing: there is no scheduling package, no meeting provider\n     and no invite generator in the product. A picker that names a time and books nothing is worse than a\n     form that does not send. The right rail\'s "Book a demo instead" card went with it — removing a feature\n     and leaving its advertisement is how a dead control survives a cleanup.\n\n     ⚠️ NO SUPPORT WIDGET (D4, open): it would be the first third-party request on a site that deliberately\n     makes none. --}}'}

for key in ("security", "integrations", "about", "contact"):
    meta, page, left = C.convert(key)
    title = html.unescape(meta["title"]).replace('"', "&quot;")
    desc = html.unescape(meta["description"]).replace('"', "&quot;")
    out = (HEADERS[key] + "\n@php\n    $testimonialQuotes = [];\n@endphp\n"
           + '<x-marketing.shell\n    title="' + title + '"\n    description="' + desc + '"\n'
           + '    canonical="' + meta["canonical"] + '">\n' + page + "\n</x-marketing.shell>\n")
    io.open(VIEWS % key, "w", encoding="utf-8").write(out)
    print("%-14s %6d chars" % (key, len(out)))

missed = [(k, tag) for k, tag, ok in C._NEEDLE_LOG if not ok]
print("\nhonesty needles: %d attempted, %d matched nothing" % (len(C._NEEDLE_LOG), len(missed)))
for k, tag in missed:
    print("  \u26a0\ufe0f SILENT NO-OP: %s / %s" % (k, tag))
if missed:
    sys.exit(4)
