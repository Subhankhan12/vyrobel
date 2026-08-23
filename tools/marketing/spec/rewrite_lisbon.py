import io, sys

P = "/Applications/XAMPP/xamppfiles/htdocs/vyrobel/resources/views/marketing/story-lisbon.blade.php"
s = io.open(P, encoding="utf-8").read()
orig = s

LABEL = '<p style="margin: 14px 0 0; padding: 11px 15px; border-radius: 13px; background: rgba(217,102,46,.12); border: 1px solid rgba(217,102,46,.26); font-size: 13.5px; line-height: 1.55; color: var(--vy-ink); max-width: 640px;" data-scenario-label>⚠️ <strong>An illustrative scenario, not a customer.</strong> Vyrobel is new and has no published customer stories yet. This describes how the product is used by an operation of this shape, composed from onboarding conversations. Nothing here is a claim about a real business, and no figure is a measured result.</p>'

reps = [
  # --- condition 6 + 1: shell title / meta description ---
  ('    title="Hotel Alfama Reyes — 96 keys in Lisbon | Vyrobel customer story"',
   '    title="96 keys, one city hotel — an example scenario | Vyrobel"'),

  ('    description="A legacy PMS the front desk tolerated and nobody else could open. How a 96-key Lisbon hotel got housekeeping, maintenance and the front desk onto one system."',
   '    description="A legacy PMS the front desk tolerated and nobody else could open. An example scenario: how a 96-key city hotel gets housekeeping, maintenance and the front desk onto one system."'),

  # --- condition 6 + 1: print letterhead ---
  ('>Customer story · 96 keys · Lisbon</span>',
   '>Example scenario · 96 keys · city hotel</span>'),

  ('>Hotel Alfama Reyes · Illustrative story, composed from onboarding conversations · vyrobel.com/case-studies</div>',
   '>Illustrative scenario, composed from onboarding conversations · vyrobel.com</div>'),

  # --- condition 6: breadcrumb collection label (component + key untouched) ---
  ('font-weight: 500;">Customer stories</x-marketing.nav-link>',
   'font-weight: 500;">Example scenarios</x-marketing.nav-link>'),

  # --- condition 1: breadcrumb current page identity ---
  ('<span aria-current="page" style="color: var(--vy-ink); font-weight: 500;">Hotel Alfama Reyes</span>',
   '<span aria-current="page" style="color: var(--vy-ink); font-weight: 500;">A 96-key city hotel</span>'),

  # --- condition 1: eyebrow city pin ---
  ('>Hotel · Lisbon</span>',
   '>Hotel · city centre</span>'),

  # --- condition 5: prominent label immediately after <h1> ---
  ('The floor stopped phoning reception</h1>\n          <p style="margin: 20px 0 0;',
   'The floor stopped phoning reception</h1>\n          ' + LABEL + '\n          <p style="margin: 20px 0 0;'),

  # --- condition 1: previous-story card identity + city pin ---
  ('line-height: 1.3;">Van Dijk Stays</span>',
   'line-height: 1.3;">An 18-apartment operator</span>'),

  ('margin-top: 3px;">18 apartments · Rotterdam</span>',
   'margin-top: 3px;">Apartments · example scenario</span>'),

  # --- condition 6: bottom note register ---
  ('>Illustrative story</div>',
   '>Illustrative scenario</div>'),
]

for old, new in reps:
    n = s.count(old)
    if n != 1:
        sys.exit("NEEDLE COUNT %d (expected 1): %r" % (n, old[:80]))
    s = s.replace(old, new)

# --- condition 2: delete the blockquote element outright (whole line + newline) ---
start = s.index('        <blockquote data-pull=""')
end = s.index('</blockquote>\n', start) + len('</blockquote>\n')
block = s[start:end]
if s.count('<blockquote') != 1 or '<figure' in s or '<figcaption' in s:
    sys.exit("blockquote/figure assumption violated")
s = s[:start] + s[end:]

io.open(P, "w", encoding="utf-8").write(s)
print("BLOCKQUOTE REMOVED:", block.strip()[:120], "...")
print("replacements applied:", len(reps))
print("bytes %d -> %d" % (len(orig), len(s)))
