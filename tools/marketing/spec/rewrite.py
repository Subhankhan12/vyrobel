# -*- coding: utf-8 -*-
import io, sys

p = "/Applications/XAMPP/xamppfiles/htdocs/vyrobel/resources/views/marketing/story-cyclades.blade.php"
s = io.open(p, encoding="utf-8").read()
orig = s

LABEL = ('<p style="margin: 14px 0 0; padding: 11px 15px; border-radius: 13px; '
         'background: rgba(217,102,46,.12); border: 1px solid rgba(217,102,46,.26); '
         'font-size: 13.5px; line-height: 1.55; color: var(--vy-ink); max-width: 640px;" '
         'data-scenario-label>⚠️ <strong>An illustrative scenario, not a customer.</strong> '
         'Vyrobel is new and has no published customer stories yet. This describes how the product is used '
         'by an operation of this shape, composed from onboarding conversations. Nothing here is a claim '
         'about a real business, and no figure is a measured result.</p>')

reps = [
 # --- condition 6 + 1: shell title / meta description ---
 (u'    title="Kastellis Villas — 42 villas in the Cyclades | Vyrobel customer story"',
  u'    title="A 42-villa portfolio across four islands | Vyrobel example scenario"', 1),
 (u'    description="How a 42-villa manager in the Cyclades replaced a channel manager,',
  u'    description="An illustrative scenario: how a 42-villa manager across four islands replaced a channel manager,', 1),

 # --- condition 6 + 1: print letterhead ---
 (u'>Customer story · 42 villas, 31 owners · Cyclades<',
  u'>Example scenario · 42 villas, 31 owners · four islands<', 1),
 (u'>Kastellis Villas · Illustrative story, composed from onboarding conversations · vyrobel.com/case-studies<',
  u'>Illustrative scenario, composed from onboarding conversations · vyrobel.com/case-studies<', 1),

 # --- condition 6: breadcrumb label ---
 (u'font-weight: 500;">Customer stories</x-marketing.nav-link>',
  u'font-weight: 500;">Example scenarios</x-marketing.nav-link>', 1),

 # --- condition 1: breadcrumb current page (identity) ---
 (u'<span aria-current="page" style="color: var(--vy-ink); font-weight: 500;">Kastellis Villas</span>',
  u'<span aria-current="page" style="color: var(--vy-ink); font-weight: 500;">Villa portfolio</span>', 1),

 # --- condition 1: eyebrow, de-pin the region ---
 (u'>Villas · Cyclades, Greece<', u'>Villas · four islands<', 1),

 # --- condition 5: the prominent label, immediately after the <h1> ---
 (u'one screen</h1>\n          <p style="margin: 20px 0 0;',
  u'one screen</h1>\n          ' + LABEL + u'\n          <p style="margin: 20px 0 0;', 1),

 # --- condition 1: standfirst identity ---
 (u'>Kastellis Villas manages holiday houses across four islands',
  u'>This operation manages holiday houses across four islands', 1),

 # --- condition 1: "next" card identity + city pinning ---
 (u'line-height: 1.3;">Van Dijk Stays</span>',
  u'line-height: 1.3;">Eighteen serviced apartments</span>', 1),
 (u'margin-top: 3px;">18 apartments · Rotterdam</span>',
  u'margin-top: 3px;">A two-person family business</span>', 1),

 # --- condition 6: bottom note into the scenario register ---
 (u'margin-bottom: 10px;">Illustrative story</div>',
  u'margin-bottom: 10px;">Illustrative scenario</div>', 1),
]

for old, new, n in reps:
    c = s.count(old)
    if c != n:
        sys.stderr.write("FAIL count=%d expected=%d for: %s\n" % (c, n, old[:70]))
        sys.exit(1)
    s = s.replace(old, new)

# --- condition 2: delete the blockquote element entirely (whole line) ---
lines = s.split("\n")
before = len(lines)
lines = [l for l in lines if "<blockquote" not in l]
removed = before - len(lines)
if removed != 1:
    sys.stderr.write("FAIL blockquote lines removed=%d\n" % removed)
    sys.exit(1)
s = "\n".join(lines)

io.open(p, "w", encoding="utf-8").write(s)
print("OK: rewritten, %d replacements, %d blockquote line removed" % (len(reps), removed))
