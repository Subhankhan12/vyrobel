"""⚠️ BATCH 2 OF THE ~120 — ACCESS & PRIVACY. Ordered by consequence: a false access claim is a security
statement, and a false GDPR claim is legal exposure.

⚠️ `guide-gdpr` WAS NOT UNTOUCHED. Slice 2's cluster-G corrections already rewrote five of its sentences on
the LEDGER's list (G1-scheduled, G2-settings-screen, G3-statutory-period, G4-id-retention, G5-erase-refusal),
and five of the AUDIT's ten FALSE claims are already evidenced as done by that overlap. These transforms take
only what is still standing, and none of them reaches into a sentence slice 2 already owns.

SUBSYSTEMS VERIFIED ONCE (§N.8f):
  · per-property staff scoping — `property_user` DOES have a write path now (`PropertyScopeService`), reached
    by the operator route `app.settings.team-scope` from Settings. ⚠️ The Gate-5 audit predates it.
  · permissions — granted to ROLES, never to people: `RolePermissionService` writes spatie's
    `role_has_permissions`. Toggling one changes it for every holder of that role.
  · Housekeeping — `config/authorization.php:216-220` gives it units.view + housekeeping.view +
    housekeeping.manage + schedule.view, and NOT `maintenance.manage`.
  · staff deactivation — the only deactivate in the product is the OWNER self-deactivation
    (`OwnerManageController`). No operator surface deactivates a staff user, and nothing in the request
    pipeline re-reads `users.status`.
  · guest data export — no route in the guest realm exports a guest's data.
  · statutory retention — nothing in the product computes, stores or enforces a statutory registration
    period; erasure is not gated on stay dates.
"""

ACCESS = {
    # ⚠️ CONFIRM-FIRST CUT BOTH WAYS — AND THE AUDIT WAS STALE HERE. It says "no such control exists"; one
    # does now (PropertyScopeService via app.settings.team-scope), built in this same arc. ⚠️ So only the
    # FALSE half goes: the choice is real, but it is made on Settings afterwards, not as a step of the
    # invite the surrounding numbered list describes.
    'scope-step': {
        'before': 'On a multi-property account, choose which properties they work in. A housekeeper at one '
                  'building has no reason to see another’s arrivals.',
        'after': 'On a multi-property account you can limit which properties someone works in, from '
                 'Settings once their account exists. A housekeeper at one building has no reason to see '
                 'another’s arrivals. Until you set it, a new account can reach every property.',
    },
    # ⚠️ The Housekeeping role does not hold `maintenance.manage`, so it cannot raise a ticket — the New
    # Work Order control is behind that permission. The rest of the sentence is TRUE and stays.
    'housekeeping-ticket': {
        'before': ' — the board for their properties, room detail, and the ability to raise a maintenance '
                  'ticket. No guest contact details, no money.',
        'after': ' — the board for their properties and room detail. No guest contact details, no money, '
                 'and no raising maintenance tickets: that is the Maintenance role.',
    },
    # ⚠️ Permissions are attached to ROLES. Editing one on /app/roles changes it for EVERY holder of that
    # role — the opposite of "per person". ⚠️ An access-control claim is backed by code or it is FALSE, so
    # this is corrected rather than hedged.
    'per-person-permissions': {
        'before': 'Roles are a starting point rather than a cage: individual permissions can be adjusted '
                  'per person where your operation does not fit the standard shape.',
        'after': 'Roles are a starting point rather than a cage: you can change which permissions a role '
                 'carries where your operation does not fit the standard shape. A change applies to '
                 'everyone holding that role, not to one person.',
    },
    # ⚠️ THERE IS NO STAFF DEACTIVATION AT ALL. The only deactivate in the product is an owner deactivating
    # themselves; no operator surface offers it, and nothing re-reads `users.status` mid-session. ⚠️ The
    # section cannot be corrected into a true instruction, so it states what you can actually do instead.
    # ⚠️ THIS LEAVES "When someone leaves" THINNER — it now describes removing a role rather than a
    # deactivation workflow, because the workflow does not exist.
    'deactivate': {
        'before': 'Deactivate rather than delete. Deactivating ends their sessions immediately and blocks '
                  'any future login, while keeping their name attached to the work they did — which rooms '
                  'they cleaned, which refunds they approved.',
        'after': 'Take their role away rather than deleting the account. Their name stays attached to the '
                 'work they did — which rooms they cleaned, which refunds they approved — and without a '
                 'back-office role they cannot reach the app. Sign them out of their other sessions from '
                 'Security if the departure is abrupt.',
    },
}

GDPR = {
    # ⚠️ HALF TRUE. Guest-REQUESTED deletion is real (a request, then a 14-day grace). A guest data EXPORT
    # is not — there is no such route in the guest realm. Only the export half goes.
    'guest-export': {
        'before': 'A guest with an account can export their own data or request deletion from their '
                  'account, without contacting you.',
        'after': 'A guest with an account can request deletion from their account, without contacting you.',
    },
    # ⚠️ NOTHING COMPUTES, STORES OR ENFORCES A STATUTORY REGISTRATION PERIOD, and erasure is not gated on
    # stay dates. ⚠️ An operator relying on this sentence would tell a data subject that the system is
    # holding their record when it is not — which is the legal exposure pointed the wrong way.
    # ⚠️ It is REMOVED rather than restated: describing a retention rule the product does not apply would be
    # the same defect in the other direction.
    'statutory-wait': {
        'before': ' A guest whose stay falls inside a statutory registration period has to wait for it to '
                  'expire, and that is a defensible answer to give them.',
        'after': ' Where your jurisdiction requires you to keep a guest record, that is your judgement to '
                 'apply before you action the request — the product does not hold the record back for '
                 'you.',
    },
}
