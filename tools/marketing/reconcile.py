"""⚠️ THE RECONCILER — joins the audit's FALSE-claim fragments to the page's own sentences.

⚠️⚠️ THE UNMATCHED COUNT IS NOT A TARGET OF ZERO. A wrong match reports DONE for a claim nobody
corrected, silently and permanently; an unmatched row is a visible, honest gap. So every rule below is
CONSERVATIVE, is NAMED, and is reported with what it rejected — so the rule can be argued with rather
than inferred from a total.

THE RULES, in full:

  R1  TEXT, NOT MARKUP.  The audit quotes running prose; the master is HTML. Compare tag-stripped,
      entity-decoded, whitespace- and typography-normalised TEXT on both sides.
      ⚠️ This is not a loosening — it is comparing text with text instead of text with markup.
      (Measured: it recovers only 2 of the 94. Markup was never the main cause.)

  R2  TRUNCATION.  The audit elides with "..." ("End anything you do not recognise, and end all other
      sessions after a staff change..."). Match on the retained side only — a prefix or suffix is still
      containment, not a guess. At least 40 characters must remain, or the row stays UNMATCHED.

  R3  THE AUDITOR'S JOIN.  ⚠️ THE JUDGEMENT CALL, AND THE ONLY ONE. The audit renders a card as
      "Title — Blurb" where the page holds Title and Blurb as two ADJACENT elements. Accepted only when
      both halves are present, in order, separated in the page by whitespace ALONE, and each half is at
      least 15 characters. ⚠️ Reported separately as JOIN so it can be reviewed apart from the rest.

  R4  AMBIGUITY IS NOT A TIE TO BREAK.  If a fragment occurs MORE THAN ONCE in the page text it stays
      UNMATCHED. ⚠️ First-match-wins would pick one silently, and nothing downstream would ever say so.

  R5  NO PARAPHRASE, NO FUZZ.  There is no similarity scoring anywhere in this file. "The panel shows
      the room's housekeeping state live." is the auditor's own summary; no sentence on the page
      contains it. It stays UNMATCHED. ⚠️ "It is obviously the same claim" is a judgement, and a
      judgement that is not recorded is an assertion.

  R6  ONE SENTENCE MAY CARRY SEVERAL FRAGMENTS.  Matching is per fragment, so a transform replacing one
      sentence correctly evidences every fragment that sentence carried (S1-044 was exactly this shape).
"""
import io, os, re, html

_HERE = os.path.dirname(os.path.abspath(__file__))
MIN_TRUNC, MIN_HALF = 40, 15


def norm(s):
    s = html.unescape(s)
    for a, b in (('’', "'"), ('‘', "'"), ('“', '"'), ('”', '"'),
                 ('–', '—'), (' ', ' ')):
        s = s.replace(a, b)
    return re.sub(r'\s+', ' ', s).strip()


def text_of(h):
    """R1 — the page as a reader sees it, not as the markup spells it."""
    h = re.sub(r'(?is)<(script|style)\b.*?</\1>', ' ', h)
    h = re.sub(r'(?s)<[^>]+>', ' ', h)
    return norm(h)


def fragment(claim):
    """The text the audit is asserting. Longest quoted run, else the cell itself."""
    runs = re.findall(r'"([^"]{25,})"', claim)
    return max(runs, key=len) if runs else (claim if len(claim) >= 25 else None)


def locate(frag, page):
    """⚠️ Returns (rule, sentence) or (reason-it-was-rejected, None). NEVER a guess."""
    q = norm(frag)
    if q and page.count(q) == 1:
        return 'EXACT', _sentence(page, page.index(q), len(q))
    if page.count(q) > 1:
        return 'reject: R4 ambiguous — %d occurrences' % page.count(q), None

    if '...' in q or '…' in q:                                       # R2
        for part in re.split(r'\.\.\.|…', q):
            part = part.strip(' ,.')
            if len(part) >= MIN_TRUNC:
                n = page.count(part)
                if n == 1:
                    return 'TRUNC', _sentence(page, page.index(part), len(part))
                if n > 1:
                    return 'reject: R4 ambiguous after R2 — %d occurrences' % n, None
        return 'reject: R2 nothing >=%dch survived the elision' % MIN_TRUNC, None

    if '—' in q:                                                     # R3
        a, _, b = q.partition('—')
        a, b = a.strip(), b.strip()
        if len(a) >= MIN_HALF and len(b) >= MIN_HALF:
            m = re.search(re.escape(a) + r'\s+' + re.escape(b), page)
            if m and len(re.findall(re.escape(a) + r'\s+' + re.escape(b), page)) == 1:
                return 'JOIN', _sentence(page, m.start(), m.end() - m.start())
            if m:
                return 'reject: R4 ambiguous join', None
        return 'reject: R3 halves not adjacent on the page', None

    return 'reject: R5 absent — the auditor\'s paraphrase, not the page\'s words', None


def _sentence(page, i, n):
    """The page's own sentence around the hit — so a reader can disagree with the ROW."""
    s = page.rfind('. ', 0, i)
    s = 0 if s < 0 else s + 2
    e = page.find('. ', i + n)
    e = len(page) if e < 0 else e + 1
    return page[max(s, i - 240):min(e, i + n + 240)].strip()
