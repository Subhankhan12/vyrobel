#!/usr/bin/env python3
"""Reachability search. ⚠️ Its output is a claim of ABSENCE, so it searches MANY call shapes and is
validated against a known-reachable entity before any negative is trusted."""
import re, os, sys, io

# ⚠️ PORTABILITY ONLY (2026-08-23) — was this machine's absolute repo path. Derived from __file__
# (tools/audit/reach.py -> repo root); override with VY_ROOT.
import os as _os
ROOT = _os.environ.get('VY_ROOT', _os.path.dirname(_os.path.dirname(_os.path.dirname(_os.path.abspath(__file__)))))
# Where a REAL user's request can reach. Excludes seeders, tests, factories, migrations.
APP_ROOTS = ['app', 'routes']
SEED_ROOTS = ['database/seeders', 'database/factories', 'tests']

def files(roots, exts=('.php',)):
    out = []
    for r in roots:
        for dirpath, _, names in os.walk(os.path.join(ROOT, r)):
            for n in names:
                if n.endswith(exts):
                    out.append(os.path.join(dirpath, n))
    return out

def strip_comments(src):
    """⚠️ BLANK comments out IN PLACE — never delete them. Deleting shifts every subsequent line number,
    which the first version did: it reported Property::create at TenantProvisioningService:74 when the real
    line is 110. An instrument that mis-cites is worse than none, because the citation looks checkable."""
    def blank(m):
        return re.sub(r'[^\n]', ' ', m.group(0))
    src = re.sub(r'/\*.*?\*/', blank, src, flags=re.S)
    return re.sub(r'//[^\n]*', blank, src)

def shapes(model, table, relations):
    """Every call shape that could CREATE one. rel = the relation names on a parent."""
    rels = '|'.join(relations) if relations else r'\0NOMATCH\0'
    return [
        ('A static create',        rf'\b{model}::create\s*\('),
        # ⚠️ firstOrNew / newModelInstance CREATE ON ->save(). The first version omitted them and would have
        # reported OwnerStatement as having no creation code at all — while
        # OwnerStatementService::generateDraft() builds one with exactly that shape. An unsearched shape is
        # an unproven absence, and this is what that looks like when it bites.
        ('B static make/firstOrNew/firstOrCreate/updateOrCreate/upsert', rf'\b{model}::(?:make|firstOrNew|newModelInstance|firstOrCreate|updateOrCreate|upsert|forceCreate)\s*\('),
        ('C query()->create/insert/firstOrNew', rf'\b{model}::query\(\)[^;]{{0,400}}?->(?:create|insert|firstOrNew|firstOrCreate|updateOrCreate|upsert|forceCreate|make)\s*\('),
        # ⚠️ 'new Model;' has NO parentheses — the first version required them and missed
        # OwnerTeamInviteService.php:97 (`?? new OwnerTeamInvite;`). Third shape miss in this audit.
        ('D new Model + save',     rf'new\s+{model}\s*[(;,)\]]'),
        ('E relation create',      rf'->(?:{rels})\(\)\s*->\s*(?:create|createMany|save|saveMany|make|firstOrNew|firstOrCreate|updateOrCreate)\s*\('),
        ('F DB table insert',      rf"DB::table\(\s*['\"]{table}['\"]\s*\)[^;]{{0,200}}?->(?:insert|insertGetId|upsert|updateOrInsert)"),
        ('G factory',              rf'\b{model}::factory\s*\('),
        ('H model class ref',      rf'\b{model}::class'),
    ]

def scan(model, table, relations):
    res = {}
    for label, pat in shapes(model, table, relations):
        rx = re.compile(pat)
        hits_app, hits_seed = [], []
        for group, roots in (('app', APP_ROOTS), ('seed', SEED_ROOTS)):
            for f in files(roots):
                src = strip_comments(io.open(f, encoding='utf-8', errors='ignore').read())
                for m in rx.finditer(src):
                    line = src[:m.start()].count('\n') + 1
                    rel = os.path.relpath(f, ROOT)
                    (hits_app if group == 'app' else hits_seed).append(f'{rel}:{line}')
        res[label] = (hits_app, hits_seed)
    return res

def report(model, table, relations):
    print(f'══ {model} (table {table}) ' + '═' * (52 - len(model)))
    res = scan(model, table, relations)
    total_app = 0
    for label, (a, s) in res.items():
        if label == 'H model class ref':
            continue   # reference, not creation — reported separately as a hint
        total_app += len(a)
        if a or s:
            print(f'  {label:52} app={len(a):2} seed/test={len(s):2}')
            for h in a[:4]:
                print(f'      APP  {h}')
    hints = res['H model class ref'][0]
    print(f'  → CREATE-SHAPE HITS IN app/+routes/: {total_app}    (class references in app/: {len(hints)})')
    return total_app

if __name__ == '__main__':
    TARGETS = {
        # positive controls (known reachable)
        'Property':    ('properties', ['properties']),
        'Reservation': ('reservations', ['reservations']),
        'RateCalendar': ('rate_calendar', ['rateCalendar', 'rates']),
        # every entity the demo seeder creates
        'AddOn': ('add_ons', ['addOns']),
        'AgreementAmendment': ('agreement_amendments', ['amendments']),
        'Amenity': ('amenities', ['amenities']),
        'Building': ('buildings', ['buildings']),
        'Channel': ('channels', ['channels']),
        'ChannelMapping': ('channel_mappings', ['mappings', 'channelMappings']),
        'Floor': ('floors', ['floors']),
        'OwnerMonthlyTarget': ('owner_monthly_targets', ['monthlyTargets', 'targets']),
        'OwnerTeamInvite': ('owner_team_invites', ['teamInvites', 'invites']),
        'OwnerUser': ('owner_users', ['ownerUsers', 'users']),
        'PromoCode': ('promo_codes', ['promoCodes']),
        'PropertyPolicy': ('property_policies', ['policies', 'policy']),
        'RatePlan':    ('rate_plans', ['ratePlans', 'ratePlan']),
        'RoomType':    ('room_types', ['roomTypes', 'roomType']),
        'Tag': ('tags', ['tags']),
        'Tax': ('taxes', ['taxes']),
        'Unit':        ('units', ['units']),
        'Owner': ('owners', ['owners']),
        'ManagementAgreement': ('management_agreements', ['agreements', 'managementAgreements']),
        'PayoutMethod': ('payout_methods', ['payoutMethods']),
        'Fee': ('fees', ['fees']),
        'Media': ('media', ['media']),
        'OwnerStatement': ('owner_statements', ['statements', 'ownerStatements']),
    }
    for m in (sys.argv[1:] or TARGETS):
        t, r = TARGETS[m]
        report(m, t, r)
        print()
