<?php

declare(strict_types=1);

// Mutation set decisions-nov13 — the founder's rulings DEC-1, 3, 4, 5, 6, 7 (2026-11-13), one mutation per guard, each aimed at the
// test that watches it. Run with `php tests/Mutations/run.php --set=decisions-nov13`.

$oauth = 'tests/Feature/OAuthRevokeOnPasswordChangeTest.php';
$dec7 = 'tests/Feature/Admin/TrialExtensionAndComplimentaryTest.php';
$dec5 = 'tests/Feature/Admin/AbandonedSignupBucketTest.php';
$dec6 = 'tests/Feature/AccountDeletionRequestTest.php';
$dec4 = 'tests/Feature/Admin/RetentionBucketsTest.php';
$dec1 = 'tests/Feature/MailboxRotationTest.php';

return [
    // ── DEC-3 — a password change or reset cuts off every connected app ──────────────────────────────────────────
    [
        'id' => 'D13-M1',
        'name' => 'the shared password path stops cutting off connected apps',
        'file' => 'app/Models/User.php',
        'old' => '$appsDisconnected = app(OAuthConnectionService::class)->revokeAllFor($this);',
        'new' => '$appsDisconnected = 0;',
        'tests' => [['file' => $oauth]],
    ],
    [
        'id' => 'D13-M2',
        'name' => 'the cut-off reaches EVERY person\'s apps, not just this one',
        'file' => 'app/Services/OAuthConnectionService.php',
        'old' => '$allIds = Token::query()->where(\'user_id\', $user->getKey())->pluck(\'id\')->all();',
        'new' => '$allIds = Token::query()->pluck(\'id\')->all();',
        'tests' => [['file' => $oauth, 'filter' => 'another person']],
    ],
    [
        'id' => 'D13-M3',
        'name' => 'the refresh tokens are left alive, so an app can mint a new access token',
        'file' => 'app/Services/OAuthConnectionService.php',
        'old' => '        RefreshToken::query()->whereIn(\'access_token_id\', $allIds)->update([\'revoked\' => true]);
        Token::query()->whereIn(\'id\', $allIds)->update([\'revoked\' => true]);

        return $live->',
        'new' => '        Token::query()->whereIn(\'id\', $allIds)->update([\'revoked\' => true]);

        return $live->',
        'tests' => [['file' => $oauth, 'filter' => 'revokes every connected app']],
    ],
    // ── DEC-7 — extend a trial / complimentary plan ──────────────────────────────────────────────────────────────
    [
        'id' => 'D13-M4',
        'name' => 'an EXPIRED trial is extended from the day it ended, not from today',
        'file' => 'app/Http/Controllers/Admin/CustomerController.php',
        'old' => '$from = $subscription->trial_ends_at !== null && $subscription->trial_ends_at->isFuture() ? $subscription->trial_ends_at->copy() : now();',
        'new' => '$from = $subscription->trial_ends_at?->copy() ?? now();',
        'tests' => [['file' => $dec7, 'filter' => 'EXPIRED trial']],
    ],
    [
        'id' => 'D13-M5',
        'name' => 'the 90-day ceiling on a trial is gone',
        'file' => 'app/Http/Controllers/Admin/CustomerController.php',
        'old' => 'if ($to->greaterThan(now()->addDays(self::TRIAL_CEILING_DAYS))) {',
        'new' => 'if (false) {',
        'tests' => [['file' => $dec7, 'filter' => 'caps one extension']],
    ],
    [
        'id' => 'D13-M6',
        'name' => 'extending a trial no longer asks for the admin\'s password',
        'file' => 'app/Http/Controllers/Admin/CustomerController.php',
        'old' => "            'days' => ['required', 'integer', 'min:1', 'max:60'],\n            'current_password' => ['required', 'current_password:admin'],",
        'new' => "            'days' => ['required', 'integer', 'min:1', 'max:60'],",
        'tests' => [['file' => $dec7, 'filter' => 'without the admin']],
    ],
    [
        'id' => 'D13-M7',
        'name' => 'a BILLED (not trialing) organisation can have its trial extended',
        'file' => 'app/Http/Controllers/Admin/CustomerController.php',
        'old' => "if (\$subscription === null || \$subscription->status !== 'trialing') {",
        'new' => 'if ($subscription === null) {',
        'tests' => [['file' => $dec7, 'filter' => 'billed, cancelled or already complimentary']],
    ],
    [
        'id' => 'D13-M8',
        'name' => 'a complimentary organisation can have its trial extended',
        'file' => 'app/Http/Controllers/Admin/CustomerController.php',
        'old' => 'if ($subscription->complimentary_until !== null) {',
        'new' => 'if (false) {',
        'tests' => [['file' => $dec7, 'filter' => 'billed, cancelled or already complimentary']],
    ],
    [
        'id' => 'D13-M9',
        'name' => 'a BILLED organisation can be made complimentary — a paying customer hidden behind a free label',
        'file' => 'app/Http/Controllers/Admin/CustomerController.php',
        'old' => "if (in_array(\$subscription->status, ['active', 'past_due'], true)) {",
        'new' => 'if (false) {',
        'tests' => [['file' => $dec7, 'filter' => 'BILLED organisation complimentary']],
    ],
    [
        'id' => 'D13-M10',
        'name' => 'a complimentary plan can be open-ended or in the past',
        'file' => 'app/Http/Controllers/Admin/CustomerController.php',
        'old' => "'until' => ['required', 'date', 'after:today', 'before_or_equal:'.now()->addDays(self::COMPLIMENTARY_MAX_DAYS)->toDateString()],",
        'new' => "'until' => ['nullable', 'date'],",
        'tests' => [['file' => $dec7, 'filter' => 'end date within a year']],
    ],
    [
        'id' => 'D13-M11',
        'name' => 'a protected organisation can be made complimentary',
        'file' => 'app/Http/Controllers/Admin/CustomerController.php',
        'old' => "if (\$refused = \$this->refusedByLock(\$request, \$org, 'grant_complimentary', \$reason, lifecycle: false)) {",
        'new' => 'if (false) {',
        'tests' => [['file' => $dec7, 'filter' => 'protected']],
    ],
    [
        'id' => 'D13-M12',
        'name' => 'a real Stripe activation leaves the complimentary mark on a paying customer',
        'file' => 'app/Models/Subscription.php',
        'old' => "if (\$subscription->status !== 'trialing' && \$subscription->complimentary_until !== null) {",
        'new' => 'if (false) {',
        'tests' => [['file' => $dec7, 'filter' => 'Stripe activation']],
    ],
    [
        'id' => 'D13-M13',
        'name' => 'the platform "on trial" figure counts a complimentary workspace',
        'file' => 'app/Support/Platform/PlatformMetrics.php',
        'old' => "->where('status', 'trialing')->whereNull('complimentary_until')->count(),",
        'new' => "->where('status', 'trialing')->count(),",
        'tests' => [['file' => $dec7, 'filter' => 'figures leave a complimentary']],
    ],
    [
        'id' => 'D13-M14',
        'name' => 'the revenue page counts a complimentary workspace as a trial waiting to convert',
        'file' => 'app/Support/Platform/Revenue/PlatformRevenue.php',
        'old' => "->whereNull('stripe_subscription_id')->whereNull('complimentary_until')->count(),",
        'new' => "->whereNull('stripe_subscription_id')->count(),",
        'tests' => [['file' => $dec7, 'filter' => 'figures leave a complimentary']],
    ],
    [
        'id' => 'D13-M15',
        'name' => 'the directory\'s trial filter lists complimentary workspaces',
        'file' => 'app/Support/Platform/PlatformCustomers.php',
        'old' => "->where('subscriptions.status', 'trialing')->whereNull('subscriptions.complimentary_until')   // DEC-7: complimentary is not a trial\n",
        'new' => "->where('subscriptions.status', 'trialing')\n",
        'tests' => [['file' => $dec7, 'filter' => 'free trial” filter']],
    ],
    [
        'id' => 'D13-M16',
        'name' => 'ending a complimentary plan leaves the organisation with full access',
        'file' => 'app/Http/Controllers/Admin/CustomerController.php',
        'old' => "['complimentary_until' => null, 'trial_ends_at' => now()->subSecond()]",
        'new' => "['complimentary_until' => null]",
        'tests' => [['file' => $dec7, 'filter' => 'ending a complimentary plan sends']],
    ],
    [
        'id' => 'D13-M17',
        'name' => 'the organisation\'s Billing page counts a complimentary workspace down like a trial',
        'file' => 'resources/views/livewire/operator/billing.blade.php',
        'old' => "@if (\$status['complimentary'] && \$status['on_trial'])",
        'new' => '@if (false)',
        'tests' => [['file' => $dec7, 'filter' => 'tells a complimentary workspace']],
    ],
    [
        'id' => 'D13-M18',
        'name' => 'a complimentary workspace is labelled as an ordinary trial in the console',
        'file' => 'app/Support/Billing/SubscriptionStatus.php',
        'old' => '            $this->isComplimentary() && $this->onTrial() => __(',
        'new' => '            false => __(',
        'tests' => [['file' => $dec7, 'filter' => 'grants a complimentary plan with an end date']],
    ],
    // ── DEC-5 — the abandoned-signups bucket (surfaces only) ─────────────────────────────────────────────────────
    [
        'id' => 'D13-M19',
        'name' => 'a signup counts as abandoned the day it is created, not after 30 days',
        'file' => 'app/Support/Platform/PlatformCustomers.php',
        'old' => "->where('organizations.created_at', '<', CarbonImmutable::now()->subDays(self::ABANDONED_AFTER_DAYS))",
        'new' => "->where('organizations.created_at', '<', CarbonImmutable::now())",
        'tests' => [['file' => $dec5, 'filter' => 'inside the 30 days']],
    ],
    [
        'id' => 'D13-M20',
        'name' => 'a signup where somebody confirmed an email still counts as abandoned',
        'file' => 'app/Support/Platform/PlatformCustomers.php',
        'old' => "->where('organizations.created_at', '<', CarbonImmutable::now()->subDays(self::ABANDONED_AFTER_DAYS))\n                ->whereNotExists(\$this->verifiedMember())\n",
        'new' => "->where('organizations.created_at', '<', CarbonImmutable::now()->subDays(self::ABANDONED_AFTER_DAYS))\n",
        'tests' => [['file' => $dec5, 'filter' => 'confirmed an email']],
    ],
    [
        'id' => 'D13-M21',
        'name' => 'a signup where somebody has signed in still counts as abandoned',
        'file' => 'app/Support/Platform/PlatformCustomers.php',
        'old' => "                ->whereNotExists(\$this->verifiedMember())\n                ->whereNotExists(\$this->activeMembers()->whereNotNull('users.last_login_at')),",
        'new' => '                ->whereNotExists($this->verifiedMember()),',
        'tests' => [['file' => $dec5, 'filter' => 'ever signed in']],
    ],
    // ── DEC-6 — staff and owners REQUEST deletion (a request a person answers; nothing is erased) ────────────────
    [
        'id' => 'D13-M22',
        'name' => 'a second request is filed while one is already open',
        'file' => 'app/Support/Privacy/AccountDeletionRequests.php',
        'old' => "        \$open = self::openFor(\$user);\n        if (\$open !== null) {",
        'new' => "        \$open = null;\n        if (\$open !== null) {",
        'tests' => [['file' => $dec6, 'filter' => 'second request']],
    ],
    [
        'id' => 'D13-M23',
        'name' => 'the request no longer asks for the password',
        'file' => 'app/Http/Controllers/Auth/AccountDeletionRequestController.php',
        'old' => "'current_password' => \$user->hasPassword() ? ['required', 'current_password'] : ['prohibited'],",
        'new' => "'current_password' => ['nullable'],",
        'tests' => [['file' => $dec6, 'filter' => 'wrong password']],
    ],
    [
        'id' => 'D13-M24',
        'name' => 'the request is filed for whichever user id the form names, not the signed-in person',
        'file' => 'app/Http/Controllers/Auth/AccountDeletionRequestController.php',
        'old' => "        \$user = \$request->user();\n        abort_if(\$user === null, 403);",
        'new' => "        \$user = \\App\\Models\\User::query()->find(\$request->input('user_id') ?? \$request->user()?->getKey());\n        abort_if(\$user === null, 403);",
        'tests' => [['file' => $dec6, 'filter' => 'posted user id is ignored']],
    ],
    [
        'id' => 'D13-M25',
        'name' => 'filing the request also erases the person (renames the account)',
        'file' => 'app/Support/Privacy/AccountDeletionRequests.php',
        'old' => '        return [$request, true];',
        'new' => "        \$user->forceFill(['name' => 'Erased'])->save();\n\n        return [\$request, true];",
        'tests' => [['file' => $dec6, 'filter' => 'erases nothing']],
    ],
    [
        'id' => 'D13-M26',
        'name' => 'the Security page keeps the request form once a request is already open',
        'file' => 'resources/views/livewire/operator/operator-security.blade.php',
        'old' => "        @if (\$deletionRequest === null)\n            <x-security.deletion-dialog :action=\"route('app.security.deletion-request')\"",
        'new' => "        @if (true)\n            <x-security.deletion-dialog :action=\"route('app.security.deletion-request')\"",
        'tests' => [['file' => $dec6, 'filter' => 'card on both Security pages']],
    ],
    // ── DEC-4 — the retention card's three buckets ────────────────────────────────────────────────────────────────
    [
        'id' => 'D13-M27',
        'name' => '"pruned by a timer" counts a category where only SOME of its stores are pruned',
        'file' => 'app/Support/Privacy/RetentionCoverage.php',
        'old' => '$strict += count($pruned) === count($named) ? 1 : 0;',
        'new' => '$strict += $pruned !== [] ? 1 : 0;',
        'tests' => [['file' => $dec4, 'filter' => 'EVERY store it names is pruned']],
    ],
    [
        'id' => 'D13-M28',
        'name' => '"kept on purpose" counts a category when only ONE of its stores has a decision',
        'file' => 'app/Support/Privacy/RetentionCoverage.php',
        'old' => 'if (array_all($unpruned, fn (string $t): bool => RetentionDecisions::isComplete($decisions[$t] ?? null))) {',
        'new' => 'if (array_any($unpruned, fn (string $t): bool => RetentionDecisions::isComplete($decisions[$t] ?? null))) {',
        'tests' => [['file' => $dec4, 'filter' => 'COMPLETE decision on record']],
    ],
    [
        'id' => 'D13-M29',
        'name' => 'a half-filled decision (no reason, period or date) counts as a decision',
        'file' => 'app/Support/Retention/RetentionDecisions.php',
        'old' => "if (trim((string) (\$decision[\$field] ?? '')) === '') {",
        'new' => 'if (false) {',
        'tests' => [['file' => $dec4, 'filter' => 'half-filled decision']],
    ],
    [
        'id' => 'D13-M30',
        'name' => 'a category whose data is not in a table drops out of the buckets, so they no longer add up',
        'file' => 'app/Support/Privacy/RetentionCoverage.php',
        'old' => "                \$none++;   // its data is not in a table, and nothing on record says how long it is kept\n\n                continue;",
        'new' => '                continue;',
        'tests' => [['file' => $dec4, 'filter' => 'add up to every category']],
    ],
    // D13-M31 ('the shipped registry is pre-filled with a retention period nobody decided') RETIRED 2026-11-14: the founder decided the money
    // records' period himself, without a lawyer, so the registry is no longer empty by ruling. Its successors are in `retention-decided`.
    // ── DEC-1 — send from two mailboxes, keeping Hostinger's daily limit ─────────────────────────────────────────
    [
        'id' => 'D13-M32',
        'name' => 'the daily limit is never checked — the first mailbox is used past its limit',
        'file' => 'app/Mail/Transport/RotatingMailboxTransport.php',
        'old' => "if (MailboxRotation::usedInLastDay(\$mailbox['label']) >= \$mailbox['limit']) {",
        'new' => 'if (false) {',
        'tests' => [['file' => $dec1, 'filter' => 'FIRST mailbox until its daily limit']],
    ],
    [
        'id' => 'D13-M33',
        'name' => 'the From address is not rewritten — the second mailbox sends as the first (the host rejects it)',
        'file' => 'app/Mail/Transport/RotatingMailboxTransport.php',
        'old' => 'if ($from === null || strcasecmp($from->getAddress(), $mailbox[\'address\']) !== 0) {',
        'new' => 'if (false) {',
        'tests' => [['file' => $dec1, 'filter' => 'From address follows the login']],
    ],
    [
        'id' => 'D13-M34',
        'name' => 'the envelope sender is not the mailbox that signs in',
        'file' => 'app/Mail/Transport/RotatingMailboxTransport.php',
        'old' => "return \$mailbox['transport']->send(\$email, new Envelope(new Address(\$mailbox['address']), \$recipients));",
        'new' => "return \$mailbox['transport']->send(\$email, \$envelope ?? Envelope::create(\$email));",
        'tests' => [['file' => $dec1, 'filter' => 'falls through to the second mailbox when the first REFUSES']],
    ],
    [
        'id' => 'D13-M35',
        'name' => 'a suppressed message uses up the daily limit (outcome no longer filtered)',
        'file' => 'app/Support/Mail/MailboxRotation.php',
        'old' => "            ->where('outcome', MailSendLog::ACCEPTED)\n            ->where('created_at', '>=', now()->subDay())",
        'new' => "            ->where('created_at', '>=', now()->subDay())",
        'tests' => [['file' => $dec1, 'filter' => 'counts only what was ACCEPTED']],
    ],
    [
        'id' => 'D13-M36',
        'name' => 'the limit never frees — rows older than 24 hours still count, so it never shuffles back',
        'file' => 'app/Support/Mail/MailboxRotation.php',
        'old' => "            ->where('outcome', MailSendLog::ACCEPTED)\n            ->where('created_at', '>=', now()->subDay())",
        'new' => "            ->where('outcome', MailSendLog::ACCEPTED)",
        'tests' => [['file' => $dec1, 'filter' => 'goes BACK to the first mailbox']],
    ],
    [
        'id' => 'D13-M37',
        'name' => 'when every mailbox refuses, the error is swallowed into a generic one',
        'file' => 'app/Mail/Transport/RotatingMailboxTransport.php',
        'old' => '$lastError = $e;   // this mailbox\'s server refused — try the next one',
        'new' => '$lastError = null;',
        'tests' => [['file' => $dec1, 'filter' => 'the second\'s error is raised']],
    ],
    [
        'id' => 'D13-M38',
        'name' => 'the accepted-mail log no longer records which mailbox a message left from',
        'file' => 'app/Listeners/Mail/RecordAcceptedMail.php',
        'old' => 'MailSendLog::accepted(MailSendLog::typeOf($event->data), $event->sent->getMessageId(), MailboxRotation::labelFor($sender));',
        'new' => 'MailSendLog::accepted(MailSendLog::typeOf($event->data), $event->sent->getMessageId());',
        'tests' => [['file' => $dec1, 'filter' => 'records which mailbox']],
    ],
    [
        'id' => 'D13-M39',
        'name' => 'the mail safety guard no longer reads the rotating mailer through its mailboxes',
        'file' => 'app/Support/Mail/MailSafetyGuard.php',
        'old' => "\$transport === 'rotating') {   // DEC-1: the rotating mailer is judged by its mailboxes",
        'new' => 'false) {',
        'tests' => [['file' => $dec1, 'filter' => 'safety guard reads the rotating mailer']],
    ],
    [
        'id' => 'D13-M40',
        'name' => 'the health card stops showing how much of the limit each mailbox has used',
        'file' => 'app/Support/Platform/Health/HealthBoard.php',
        'old' => "'used' => MailboxRotation::usedInLastDay(\$mailbox['label']), 'limit' => \$limit]);",
        'new' => "'used' => 0, 'limit' => \$limit]);",
        'tests' => [['file' => $dec1, 'filter' => 'Mail health card shows how much']],
    ],
    [
        'id' => 'D13-M41',
        'name' => 'a second mailbox with no login is kept in the rotation',
        'file' => 'app/Support/Mail/MailboxRotation.php',
        'old' => "            if (\$address === '') {\n                continue;\n            }\n",
        'new' => '',
        'tests' => [['file' => $dec1, 'filter' => 'only ONE mailbox configured']],
    ],
];
