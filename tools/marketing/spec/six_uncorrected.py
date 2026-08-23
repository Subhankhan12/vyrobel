"""⚠️ THE SIX UNCORRECTED FALSE CLAIMS — the ones two passes reported as corrected and never touched.

⚠️ WHY THEY WERE MISSED, and it is the finding that matters more than the corrections:
`article-housekeeping` was re-argued against `claims.json`'s UNSOURCED-claim ids (S1-071, S1-104, …) while
the Gate-5 audit's FALSE claims on THAT SAME PAGE are a DIFFERENT SET. Two registers, two lists, one page —
and the page was reported corrected on the strength of the wrong list.

⚠️ EVERY SENTENCE BELOW WAS CHECKED AGAINST THE CODE BEFORE IT WAS WRITTEN, and the checks are cited.
⚠️ NOTHING IS HEDGED. A hedged security claim is worse than a plainly wrong one: the reader keeps believing
they have the protection, and the hedge makes the claim unfalsifiable.
"""

# ─────────────────────────────────────────────────────────────────────────────────────────────────
# guide-security-doc
# ─────────────────────────────────────────────────────────────────────────────────────────────────

SECURITY_SIX = {
    # ⚠️ ONE SENTENCE CARRYING TWO AUDIT CLAIMS (the strength meter AND the generator) — the S1-044
    #    shape. Replacing the sentence corrects BOTH, and the gate evidences both.
    #
    # VERIFIED: (1) there is NO strength meter on this page — operator-security.blade.php:76 renders a
    # static sentence, `PasswordPolicy::hint()`, and nothing else. (2) Where a meter DOES exist
    # (reset-password, set-password, onboarding) the code says verbatim "Presentational strength hint
    # only — the server enforces the real minimum" and "a VISUAL AID, not a gate". (3) NO password
    # generator exists anywhere: grep for generatePassword / password-generator / randomPassword /
    # Str::password across app/, resources/ and routes/ returns nothing.
    #
    # ⚠️ CONFIRM-FIRST CUT BOTH WAYS HERE: the SERVER genuinely does enforce a minimum
    # (PasswordPolicy::rule() → Password::min(MIN_LENGTH)->letters()->numbers()->symbols()). The false
    # part is attributing that enforcement to a meter this page does not have, and promising a
    # generator that does not exist. ⚠️ So the requirement is stated from the CONSTANT, never typed.
    'meter-and-generator': {
        'before': 'The strength meter is not decorative — it enforces a minimum, and the generator '
                  'produces something you can store in a manager rather than remember.',
        'after': 'The requirement is enforced when you save, not suggested: '
                 '{{ \\App\\Support\\Auth\\PasswordPolicy::hint() }} '
                 'Use your password manager to generate and store it.',
    },

    # VERIFIED: the OAuth scope catalog (app/Support/OAuth/OAuthScopes.php:22-42) has exactly three
    # entries and all three are READ: read:bookings, read:statements, read:units. No write scope exists,
    # so no connected app can push or stop pushing availability. A channel manager is NOT an OAuth
    # connected app and cannot be disconnected here — channel connections are a separate operator
    # surface (ChannelConnectionService).
    'channel-manager': {
        'before': 'Disconnecting is immediate and asks you to confirm, because a disconnected channel '
                  'manager stops updating availability and that is a problem you would rather cause '
                  'deliberately.',
        'after': 'Disconnecting is immediate and asks you to confirm. Every scope on this list is '
                 'read-only, so disconnecting stops an application reading your data — it cannot '
                 'change what your channels are doing. Channel connections are managed separately.',
    },
}

# ─────────────────────────────────────────────────────────────────────────────────────────────────
# article-housekeeping
# ─────────────────────────────────────────────────────────────────────────────────────────────────

HOUSEKEEPING_SIX = {
    # VERIFIED, independently of the audit: `config/authorization.php:216-220` gives the Housekeeping
    # role units.view + housekeeping.view + housekeeping.manage + schedule.view — and NOT
    # maintenance.manage, which only Maintenance and the manager-level roles hold. And the housekeeping
    # board view carries ZERO maintenance/ticket references, so there is no raise-a-ticket affordance
    # for anyone on that screen.
    #
    # ⚠️ CONFIRM-FIRST CUT BOTH WAYS: the PHOTOGRAPH half is real — the board view has 11 proof/photo
    # references and proof photos are a built feature. Only the raise-a-ticket half is false, so only
    # that half is removed. ⚠️ The sentence is the article's argument for why damage should be captured
    # where it is found; removing the whole sentence would gut the paragraph's point, and the accurate
    # version still makes it.
    'cleaner-raises-ticket': {
        'before': 'A cleaner should be able to photograph the cracked screen and raise it in fifteen '
                  'seconds from the room.',
        'after': 'A cleaner should be able to photograph the cracked screen from the room, in seconds, '
                 'and have it reach whoever can act on it.',
    },
}
