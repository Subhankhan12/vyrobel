# -*- coding: utf-8 -*-
"""SLICE 2 — cluster F (Security) and cluster G (GDPR). Every sentence verified BEFORE it was written.

⚠️⚠️ CLUSTER F'S RULE: A SECURITY CLAIM IS BACKED BY CODE OR IT IS FALSE. IT IS NEVER SOFTENED. A hedged
security property is worse than a plainly wrong one — the reader keeps believing they have the protection,
and nothing can now disprove it.

⚠️ CLUSTER G'S RULE: DESCRIBE WHAT EXISTS. The page narrated a retention system that was never built. It
must also NOT promise the cluster-G build — describing an unbuilt system in the present tense is the same
defect pointed the other way.
"""

# ══ CLUSTER F · SECURITY ═════════════════════════════════════════════════════════════════════════════
SECURITY = {
 'F1-sms': {
   'original': ('An authenticator app is the default and the one we recommend. SMS is offered because some '
                'teams need it, and is meaningfully weaker — use it only where an app is not practical.'),
   'replacement': ('Two-factor authentication uses an authenticator app, with single-use recovery codes for '
                   'the day you lose the device. We do not offer SMS at all — and that is the stronger '
                   'position rather than a missing feature: a code sent by text can be taken by a SIM swap, '
                   'and an authenticator app cannot.'),
   'code_fact': ('⚠️ NO SMS PROVIDER EXISTS — nothing in config/services.php, no SMS service or '
                 'notification channel anywhere in app/. What DOES exist: TOTP plus '
                 '`two_factor_recovery_codes` (an encrypted:array cast on User).'),
   'note': '⚠️ THE CORRECTION IS STRONGER, NOT A CONCESSION. The shipped mechanism beats the one the page claimed.',
 },
 'F2-account-wide': {
   'original': ('Admins can require 2FA account-wide. Existing users are prompted at their next login rather '
                'than locked out mid-shift.'),
   'replacement': ('Each person turns two-factor authentication on for their own account. There is no '
                   'account-wide requirement an administrator can switch on — today that is a policy you '
                   'enforce with your team, not a setting we can enforce for you.'),
   'code_fact': ('⚠️ NO TENANT-LEVEL ENFORCEMENT. The only enforcement middleware is '
                 '`EnsurePlatformAdminTwoFactor` — the PLATFORM-ADMIN realm, not a tenant. No '
                 '`two_factor_required` column or equivalent exists on any tenant model.'),
 },
 'F3-location': {
   'original': ('The sessions list shows every signed-in device with its approximate location and last '
                'activity. Ending a session signs that device out immediately.'),
   'replacement': ('The sessions list shows every signed-in device with its browser, IP address and last '
                   'activity. Ending a session signs that device out immediately.'),
   'code_fact': ('⚠️ NO GEOLOCATION EXISTS ANYWHERE IN THE APPLICATION. `ActiveSessions::forUser()` returns '
                 'exactly: `current`, `device` (a browser name derived from the user-agent string), `ip`, '
                 '`last_active`. There is no geo lookup, no country, no city.'),
   'note': '⚠️ The second sentence was already true and is left exactly as it was.',
 },
 'F4-write-scope': {
   'original': ('Any third-party application you have authorised appears here with the specific permissions '
                'it holds — read reservations, write availability, and so on. Open one before you connect it '
                'and read what it is asking for.'),
   'replacement': ('Any third-party application you have authorised appears here with the specific '
                   'permissions it holds. Today every one of them is read-only — your bookings, your '
                   'statements, your units — and nothing can be granted write access, because no write '
                   'permission exists to grant.'),
   'code_fact': ('⚠️ `OAuthScopes` DEFINES EXACTLY THREE SCOPES, ALL READ: read:bookings, read:statements, '
                 'read:units. There is no write scope, so the authorisation server cannot issue one. '
                 '⚠️ The page was promising a grant the server REFUSES TO MAKE.'),
 },
}

# ══ CLUSTER G · GDPR ═════════════════════════════════════════════════════════════════════════════════
#
# ⚠️ `{{ … }}` IN A REPLACEMENT IS A BLADE ECHO, ON PURPOSE. The grace period is a real constant, and
#    typing "14" here would put a second copy of it in the docs, free to drift from the code the day it
#    changes. The LEAD_DAYS precedent: interpolate the constant, never type the number.
GDPR = {
 'G1-scheduled': {
   'original': ('Retention and anonymisation run on a schedule once configured; you do not trigger them. '
                'What you need to know is where the settings are and what you are still responsible for.'),
   'replacement': ('Two jobs run on a schedule and you do not trigger either: a guest who asks for their '
                   'account to be deleted is anonymised once the grace period elapses, and identity '
                   'documents are hard-deleted a fixed number of days after checkout. Neither is '
                   'configurable per property, and neither is a general retention policy — what you are '
                   'responsible for is everything they do not cover.'),
   'code_fact': ('`guests:process-deletions` daily 03:00 → cancels upcoming reservations per policy and '
                 'calls Guest::anonymize(). `guest-documents:purge-expired` daily 03:30 → hard-deletes ID '
                 'documents (a fixed `--days` default, not a per-property setting). ⚠️ There is no '
                 'configurable retention period anywhere in app/ or the migrations.'),
 },
 'G2-settings-screen': {
   'original': 'Settings, Data and privacy. Set once per property.',
   'replacement': 'There is no data-and-privacy settings screen today, and nothing here is set per property.',
   'code_fact': '⚠️ NO SUCH SCREEN — "Data and privacy" appears in no operator Livewire component or view.',
 },
 'G3-statutory-period': {
   'original': ('Guest registration data is kept for the period your jurisdiction requires and then '
                'anonymised automatically. Set the actual legal figure for where you operate; the default '
                'is not legal advice.'),
   'replacement': ('There is no statutory-retention setting, and nothing anonymises guest registration data '
                   'on a legal timetable. What exists is narrower and guest-driven: a guest can ask for '
                   'their account to be deleted, and {{ \\App\\Services\\GuestDeletionService::GRACE_DAYS }} '
                   'days later their personal fields are anonymised. Meeting a retention obligation for '
                   'your jurisdiction is yours to do, and we would not have you believe otherwise.'),
   'code_fact': ('⚠️ THE SWEEP DOES NOT EXIST. `GuestDeletionService::GRACE_DAYS = 14`, and the deletion is '
                 'GUEST-REQUESTED and undoable — a different thing from a statutory sweep. No retention '
                 'column, no setting, no scheduled statutory anonymisation.'),
   'note': '⚠️ It also must not promise the cluster-G BUILD, which is ruled but unstarted.',
 },
 'G4-id-retention': {
   'original': ('We keep this setting separate from the registration period precisely because the two '
                'rarely want the same value. Images are deleted automatically at the end of it. Keeping them '
                'indefinitely is a liability rather than an asset.'),
   'replacement': ('Identity documents are hard-deleted a fixed number of days after checkout by a '
                   'scheduled job — file and row together, not anonymised. It is not a per-property '
                   'setting. Keeping them indefinitely is a liability rather than an asset.'),
   'code_fact': ('`PurgeExpiredGuestDocuments` hard-deletes the encrypted file AND the rows a fixed number '
                 'of days after checkout. ⚠️ NOT configurable per property — the window is the command\'s '
                 'own default. ⚠️ The number is deliberately not typed here: it lives in a command '
                 'signature, so a literal in the docs would be a second copy free to drift.'),
 },
 'G5-erase-refusal': {
   'original': ('You cannot erase what you are legally required to retain, and the product will tell you so '
                'rather than silently doing nothing.'),
   'replacement': ('The product does not know what you are legally required to retain, and it will not stop '
                   'you erasing something you needed to keep. Judging that is yours.'),
   'code_fact': ('⚠️ NO SUCH REFUSAL EXISTS. Nothing in GuestDeletionService or Guest::anonymize() checks a '
                 'statutory obligation or blocks an erasure on one. ⚠️ The original told a reader a '
                 'safety-net was watching for them; it is not.'),
 },
}

# ⚠️ REPORTED, NOT FIXED — the phantom retention purge (Guest.php:350).
# `Guest::anonymize()` clears booking-quote PII only for quotes that converted into this guest's
# reservations, and defers the rest with: "those are cleared by the scheduled retention purge, not from
# here." ⚠️ THERE IS NO SUCH PURGE for abandoned quotes. An abandoned quote keeps the guest's encrypted
# email, phone and snapshot indefinitely, and a GDPR erase does not reach it.
# ⚠️ THE CODE FIX IS CLUSTER G'S BUILD, NOT THIS PASS. What this pass owes it is that the DOC must not
# describe the purge as real — and G1/G3 above no longer do.
PHANTOM_PURGE = 'app/Models/Guest.php:350'
