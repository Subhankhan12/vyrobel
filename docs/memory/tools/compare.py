#!/usr/bin/env python3
"""Judge a BEFORE and an AFTER proof of one organisation's deletion. Prints every difference; exits 1 on any unexplained one.
usage: compare.py before.json after.json"""
import json, sys

b, a = (json.load(open(p)) for p in sys.argv[1:3])
org = b['organization']
problems, notes = [], []

# the tables that move with ordinary activity (console reads are audited, people browse, caches and queues turn over)
VOLATILE = {'platform_admin_actions', 'sessions', 'cache', 'cache_locks', 'jobs', 'failed_jobs', 'job_batches',
            'platform_admin_sessions', 'login_activity', 'mail_send_log', 'webhook_events', 'rate_limits'}

# 0 · premises
for k in ('database', 'server', 'version', 'tables', 'organisation_tables', 'foreign_key_columns'):
    if b['premises'][k] != a['premises'][k]:
        problems.append(f'premise changed: {k} {b["premises"][k]} → {a["premises"][k]}')

# 1 · the organisation's own rows: all gone
gone_rows, gone_tables = 0, 0
for t, orgs in b['per_org'].items():
    if org in orgs:
        gone_rows += orgs[org][0]
        gone_tables += 1
        if org in a['per_org'].get(t, {}):
            problems.append(f'{t}: {a["per_org"][t][org][0]} row(s) of the organisation SURVIVED')
notes.append(f'its rows in organisation tables before: {gone_rows} in {gone_tables} tables — after: '
             + str(sum(v[org][0] for v in a['per_org'].values() if org in v)))

# 2 · every OTHER organisation: identical rows AND content, table by table
others_checked, other_rows = 0, 0
for t, orgs in b['per_org'].items():
    for o, (n, f) in orgs.items():
        if o in (org, '(whole table)'):
            continue
        others_checked += 1
        other_rows += n
        an, af = a['per_org'].get(t, {}).get(o, [0, None])
        if (an, af) != (n, f):
            problems.append(f'{t} · organisation {o[:10]}…: {n} rows → {an}' + ('' if an != n else ' (same count, CONTENT CHANGED)'))
    for o in a['per_org'].get(t, {}):
        if o not in orgs and o != '(whole table)':
            problems.append(f'{t} · organisation {o[:10]}… appeared after: {a["per_org"][t][o][0]} row(s)')
notes.append(f'other organisations: {others_checked} (table, organisation) groups, {other_rows} rows — compared by count AND content fingerprint')

# 3 · tables without an organisation column: only the expected changes
role_perms = b['footprint_fk'].get('role_has_permissions.role_id → roles.id', 0)
expected_delta = {'role_has_permissions': -role_perms, 'organizations': -1, 'users': -b['sole']}
for t, bt in b['totals'].items():
    if t not in b['per_org']:
        # an organisation table that was EMPTY before: any row in it after is new, and no deletion creates rows
        if a['per_org'].get(t):
            problems.append(f'{t}: empty before, {sum(v[0] for v in a["per_org"][t].values())} row(s) after')
        continue
    if '(whole table)' not in b['per_org'][t]:
        continue  # organisation tables: judged above, group by group
    at = a['totals'].get(t)
    d = (at or 0) - bt
    bf = b['per_org'][t]['(whole table)'][1]
    af = a['per_org'].get(t, {}).get('(whole table)', [None, None])[1]
    if t in expected_delta:
        (notes if d == expected_delta[t] else problems).append(f'{t}: {bt} → {at} ({d:+d}, expected {expected_delta[t]:+d})')
    elif t in VOLATILE:
        if d or bf != af:
            notes.append(f'{t}: {bt} → {at} ({d:+d}) — moves with ordinary activity; judged by the searches below')
    elif d or bf != af:
        problems.append(f'{t}: {bt} → {at} ({d:+d})' + ('' if d else ' (same count, CONTENT CHANGED)'))

# 4 · orphans: none may increase
for k, n in b['orphans'].items():
    if a['orphans'].get(k, 0) > n:
        problems.append(f'orphans INCREASED — {k}: {n} → {a["orphans"][k]}')
notes.append(f'orphan checks: {len(b["orphans"])} needle-free checks, none may increase')

# 5 · the searches for anything still naming it
if a['footprint_fk']:
    problems.append('foreign-key references to its rows remain: ' + json.dumps(a['footprint_fk']))
kept_ledger = b['footprint_text'].get('platform_admin_actions.target_id', 0)
for col, n in a['footprint_text'].items():
    if col == 'platform_admin_actions.target_id':
        (notes if n == kept_ledger + 2 else problems).append(
            f'console ledger rows naming it (kept by design): {kept_ledger} → {n} (expected +2: the request and the result)')
    elif col in ('cache.key', 'cache.value', 'cache_locks.key', 'cache_locks.owner'):
        notes.append(f'{col}: {n} cache entry/entries keyed by its id (e.g. an expired job-uniqueness lock) — temporary, holds no data, expires')
    elif col == 'sessions.payload':
        notes.append(f'sessions.payload: {n} live session(s) still carry its page address in their history — transient, not its data')
    else:
        problems.append(f'STILL NAMES IT: {col} — {n} row(s)')
if a['undecodable_sessions']:
    problems.append(f'{a["undecodable_sessions"]} session payload(s) could not be decoded — the session search is blind to them')

# 6 · its people, by the saved ids
for u, counts in a['people'].items():
    left = {k: v for k, v in counts.items() if v}
    (problems if left else notes).append(f'person {u[:10]}…: ' + (f'STILL HAS {left}' if left else 'account, sessions, Google/X sign-ins and every linked row: 0'))
for k, n in a['address_rows'].items():
    if n:
        problems.append(f'{k}: {n}')
if a.get('cache_files_naming_it'):
    notes.append(f'file cache: {a["cache_files_naming_it"]} file(s) still name it (temporary; they expire)')

print('BEFORE', b['taken_at_utc'], '→ AFTER', a['taken_at_utc'])
for n in notes:
    print('  ·', n)
print(f'\n{"❌ " + str(len(problems)) + " UNEXPLAINED DIFFERENCE(S)" if problems else "✅ NO UNEXPLAINED DIFFERENCE"}')
for p in problems:
    print('  ✗', p)
sys.exit(1 if problems else 0)
