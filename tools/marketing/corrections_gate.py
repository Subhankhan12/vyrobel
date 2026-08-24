"""⚠️ THE CORRECTIONS-IMPLEMENTED GATE — what is OWED vs what is DONE.

⚠️⚠️ WHY IT EXISTS: THERE ARE TWO REGISTERS AND ONLY ONE WAS CONSULTED.
`claims.json` tracks UNSOURCED claims; the Gate-5 audit tracks FALSE ones. `pageIsCleared()` reads only
the first — so it is a SOURCING gate, not a TRUTH gate, and a page can read CLEARED while carrying
outright falsehoods. `guide-security-doc` did exactly that: cleared before AND after its false security
claims were corrected, because those claims were never in the ledger at all.

⚠️⚠️ OWED COMES FROM THE AUDIT. DONE COMES FROM THE CONVERTER. THE GATE COMPARES THEM.
The direction matters more than anything else here. ⚠️ A map assembled by walking the converter's
transforms and labelling them CAN NEVER FAIL — it would report that everything implemented is
implemented, which is the source-checked-against-itself shape this project has already shipped twice
(the /pricing comparison guard; the seeder-derived money pin). So the OWED side is parsed from
`FRONT-GATE5-CLAIMS-APPENDIX-2026-08-22.md`, an authority written before any of these transforms existed
and never edited by them.

⚠️ DONE IS EVIDENCE, NOT REGISTRATION. A registered needle is not an implemented correction — a needle
that matches nothing is the silent no-op the zero-match check exists for. So DONE asks a different
question: IS THE FALSE CLAIM'S TEXT STILL IN THE CONVERTED OUTPUT? Gone means something removed it.
⚠️ It deliberately does NOT infer "the build was green, so every needle fired" — that coupling is true
today and false the moment somebody relaxes the zero-match check, at which point this gate would quietly
become an assertion with nothing announcing it.

⚠️ AN UNKNOWN IS NOT A PASS. A page the audit covers but the converter cannot produce counts as
OUTSTANDING, not as corrected (§N.18.4 — fail closed).

Run:  python3 corrections_gate.py           (report)
      python3 corrections_gate.py --write    (also emit resources/marketing/corrections-status.json)
"""
import re, io, os, sys, json

_HERE = os.path.dirname(os.path.abspath(__file__))
_ROOT = os.path.dirname(os.path.dirname(_HERE))
sys.path.insert(0, _HERE)
import convert as C
import reconcile as R

APPENDIX = os.path.join(_ROOT, 'docs/audits/FRONT-GATE5-CLAIMS-APPENDIX-2026-08-22.md')

# ⚠️ THE AUDIT ADDRESSES PAGES BY URL; THE CONVERTER BY KEY. This map is the only place the two meet, and
# it is written by hand FROM the audit's own section list — never derived from the converter's registry,
# which would let a page the converter does not know silently vanish from the OWED side.
# ⚠️⚠️ THESE TWO WERE SWAPPED FROM THE DAY THIS MAP WAS HAND-WRITTEN, AND THE GATE SAID NOTHING.
# `guide-checkin` is the page titled "Check-in, check-out and the folio"; `guide-arrival` is "Online
# check-in and arrival information". Crossed, the gate compared each page against the OTHER page's claims
# and reported all 35 of their claims as UNMATCHED — which looks exactly like the audit paraphrasing, and
# is why it survived three passes. ⚠️ A HAND-WRITTEN JOIN IS A CLAIM LIKE ANY OTHER: it was never checked
# against the pages' own titles until batch 3 read them.
URL_TO_KEY = {
    '/docs': 'docs', '/faq': 'faq', '/glossary': 'glossary', '/resources': 'resources',
    '/docs/invites-and-access': 'guide-access', '/docs/online-check-in': 'guide-arrival',
    '/docs/connecting-channels': 'guide-channels', '/docs/check-in-and-folio': 'guide-checkin',
    '/docs/gdpr-and-data': 'guide-gdpr', '/docs/housekeeping-board': 'guide-housekeeping',
    '/docs/payout-runs': 'guide-payouts', '/docs/rate-plans': 'guide-rates',
    '/docs/refunds-and-changes': 'guide-refunds', '/docs/reports-and-exports': 'guide-reports',
    '/docs/taking-a-reservation': 'guide-reservation', '/docs/account-security': 'guide-security-doc',
    '/docs/settings': 'guide-settings', '/docs/first-property': 'guide-setup',
    '/docs/owner-statements': 'guide-statements', '/docs/storefront': 'guide-storefront',
    '/resources/growing-direct-bookings': 'article-direct',
    '/resources/housekeeping-at-forty-units': 'article-housekeeping',
    '/resources/out-of-service': 'article-oos', '/resources/winning-owners': 'article-owners',
    '/resources/pricing-levers': 'article-pricing', '/resources/owner-statement': 'article-statement',
    '/resources/booking-direct-trust': 'article-trust',
}


def owed():
    """⚠️ THE OWED SIDE, PARSED FROM THE AUDIT — every claim the audit judged FALSE, per page."""
    s = io.open(APPENDIX, encoding='utf-8').read()
    # ⚠️ THE VERDICT PATTERN IS DELIBERATELY PERMISSIVE. A first version matched `\*\*([A-Z]+)\*\*` and
    # silently dropped FIVE pages whose verdict is the multi-word "PUBLISHABLE WITH DOC FIX" — and those
    # five were precisely the pages under active work. ⚠️ A map that loses a page is vacuous exactly where
    # it matters most, so the completeness check below counts sections independently of this parse.
    parts = re.split(r'\n## `([^`]+)`\s*·\s*\*\*([A-Z][A-Z ]*)\*\*', s)
    out = {}
    for i in range(1, len(parts), 3):
        url, verdict, body = parts[i], parts[i + 1].strip(), parts[i + 2]
        # ⚠️⚠️ THE VERDICT CELL CARRIES A SEVERITY MARKER ON THE WORST ROWS: `| **FALSE** ⚠️ |`.
        #    A first version anchored on `\| \*\*FALSE\*\* \|` and silently dropped ALL 47 of them —
        #    ⚠️ leaving the gate blind to precisely the claims the auditor flagged hardest, and making
        #    OWED read 173 when the audit's own headline is 220. ⚠️ THIS IS THE SECOND TIME THIS EXACT
        #    SHAPE HAS BITTEN IN THIS FILE (the verdict regex `[A-Z]+` dropped the five multi-word
        #    "PUBLISHABLE WITH DOC FIX" pages). ⚠️ A REGEX THAT ASSUMES AN EXACT TERMINATOR DROPS
        #    EVERYTHING DECORATED — so match the cell, not the cell-plus-its-closing-pipe.
        claims = [m.group(1).strip() for m in
                  re.finditer(r'^\| (.+?) \| \*\*FALSE\*\*[^|]*\|', body, re.M)]
        out[url] = {'verdict': verdict, 'false_claims': claims}
    return out


def _quoted(claim):
    """The audit quotes a claim; the page contains the sentence. Pull the longest quoted run to match on."""
    runs = re.findall(r'"([^"]{25,})"', claim)
    return max(runs, key=len) if runs else (claim if len(claim) >= 25 else None)


def status():
    o = owed()

    # ⚠️ COMPLETENESS, CHECKED INDEPENDENTLY OF THE PARSE ABOVE. If the audit has a section the parse did
    # not yield, the map is short and the gate must say so rather than report a tidy total.
    section_count = len(re.findall(r'^## `[^`]+`', io.open(APPENDIX, encoding='utf-8').read(), re.M))

    rows = []
    for url, d in sorted(o.items()):
        key = URL_TO_KEY.get(url)
        if key is None:
            rows.append({'url': url, 'key': None, 'owed': len(d['false_claims']),
                         'done': 0, 'outstanding': ['(page not mapped to a converter key)'],
                         'unmatched': [], 'verdict': d['verdict']})
            continue
        try:
            body = C.convert(key)[1]
        except Exception as e:
            # ⚠️ FAIL CLOSED: unproducible is OUTSTANDING, never corrected.
            rows.append({'url': url, 'key': key, 'owed': len(d['false_claims']), 'done': 0,
                         'outstanding': [f'(page did not convert: {type(e).__name__})'],
                         'unmatched': [], 'verdict': d['verdict']})
            continue
        # ⚠️⚠️ "ABSENT FROM THE OUTPUT" IS NOT EVIDENCE OF A CORRECTION, AND THE FIRST VERSION OF THIS
        #    GATE TREATED IT AS IF IT WERE. It reported /faq, /glossary and /docs/online-check-in as
        #    fully corrected — pages nothing has ever transformed. The cause: the audit QUOTES a claim,
        #    and when that quote does not align with the page's own wording it is absent from the output
        #    for a reason that has nothing to do with a correction.
        #    ⚠️ SO THE COMPARISON IS AGAINST THE MASTER. A claim counts as DONE only if it was PRESENT in
        #    the untransformed source and is ABSENT after conversion — that difference is the only thing
        #    that evidences a transform having removed it. A claim absent from BOTH was never matched by
        #    anything, and is reported as UNMATCHED rather than silently banked as progress.
        try:
            master = io.open(os.path.join(_HERE, 'core', key + '.html'), encoding='utf-8').read()
        except Exception:
            master = None

        # ⚠️ RECONCILED MATCHING (tools/marketing/reconcile.py). Every rule is NAMED there and every
        #    rejection carries the rule that rejected it — so the matcher can be ARGUED WITH rather than
        #    inferred from its total. ⚠️ Ambiguity and paraphrase STAY unmatched: a wrong match reports
        #    DONE for a claim nobody corrected, and nothing downstream would ever announce it.
        mtext = R.text_of(master) if master is not None else ''
        btext = R.text_of(body)
        still, unmatched, done, matches = [], [], 0, []
        for c in d['false_claims']:
            f = R.fragment(c)
            if f is None:
                unmatched.append((c[:90], 'reject: cell carries no matchable text')); continue
            rule, sent = R.locate(f, mtext)
            if sent is None:
                unmatched.append((c[:90], rule)); continue
            if R.locate(f, btext)[1] is not None:
                still.append((c[:90], rule))          # present in the master AND still in the output
            else:
                done += 1                             # present in the master, gone after conversion
                matches.append({'url': url, 'rule': rule, 'fragment': R.norm(f), 'sentence': sent})
        rows.append({'url': url, 'key': key, 'owed': len(d['false_claims']),
                     'done': done, 'outstanding': still, 'unmatched': unmatched,
                     'matches': matches, 'verdict': d['verdict']})
    return rows, section_count, len(o)


if __name__ == '__main__':
    rows, sections, parsed = status()
    print(f"appendix sections: {sections} · parsed into the map: {parsed}")
    if sections != parsed:
        print(f"  ⚠️⚠️ THE MAP IS SHORT BY {sections - parsed} PAGE(S) — it cannot report what it cannot see.")
    unmapped = [r for r in rows if r['key'] is None]
    if unmapped:
        print(f"  ⚠️ {len(unmapped)} audited page(s) have no converter key.")
    print()
    print(f"{'page':<40} {'OWED':>5} {'DONE':>5} {'OUT':>5} {'UNMATCHED':>10}")
    tot_o = tot_d = tot_u = 0
    for r in sorted(rows, key=lambda x: (-x['done'], x['url'])):
        tot_o += r['owed']; tot_d += r['done']; tot_u += len(r.get('unmatched', []))
        flag = '  ✅ corrections landed' if r['done'] and not r['outstanding'] else ''
        print(f"  {r['url']:<38} {r['owed']:>5} {r['done']:>5} {len(r['outstanding']):>5} "
              f"{len(r.get('unmatched', [])):>10}{flag}")
    print(f"\n  TOTAL owed {tot_o} · DONE (evidenced) {tot_d} · outstanding "
          f"{sum(len(r['outstanding']) for r in rows)} · ⚠️ unmatched {tot_u}")
    print("  ⚠️ UNMATCHED = the audit's quote does not align with the page text. NOT a correction, and")
    print("     not counted as one — it is a gap between the two registers that someone must reconcile.")
    if '--write' in sys.argv:
        p = os.path.join(_ROOT, 'resources/marketing/corrections-status.json')
        io.open(p, 'w', encoding='utf-8').write(json.dumps(
            {r['key']: {'owed': r['owed'], 'done': r['done'], 'outstanding': len(r['outstanding'])}
             for r in rows if r['key']}, indent=2) + "\n")
        print(f"  wrote {p}")
