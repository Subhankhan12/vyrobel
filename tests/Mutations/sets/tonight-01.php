<?php

declare(strict_types=1);

// Mutation set tonight-01 — TONIGHT 01: the shipped data inventory the retention meter reads, and the FIRST
// verification link a sign-up is sent. Each guard removed one at a time.
// Run with `php tests/Mutations/run.php --set=tonight-01`.

return [
    [
        'id' => 'T01-M1',
        'name' => 'the meter reads the doc, which is never deployed',
        'file' => 'app/Support/Privacy/RetentionCoverage.php',
        'old' => '    public const DATA_FILE = \'resources/data/data-inventory.json\';',
        'new' => '    public const DATA_FILE = \'docs/legal/DATA-INVENTORY.md\';',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformFunnelAndPrivacyTest.php', 'filter' => 'one the release script SHIPS']],
    ],
    [
        'id' => 'T01-M2',
        'name' => 'the generator stops recording the tables a category names',
        'file' => 'app/Support/Privacy/RetentionCoverage.php',
        'old' => '            return [\'category\' => trim($name[1] ?? \'\'), \'tables\' => $named];',
        'new' => '            return [\'category\' => trim($name[1] ?? \'\'), \'tables\' => []];',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformFunnelAndPrivacyTest.php', 'filter' => 'the two cannot drift']],
    ],
    [
        'id' => 'T01-M3',
        'name' => 'an unreadable data file is counted anyway',
        'file' => 'app/Support/Privacy/RetentionCoverage.php',
        'old' => '        if (! is_array($data) || ! is_array($data[\'categories\'] ?? null)) {',
        'new' => '        if (false) {',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformFunnelAndPrivacyTest.php', 'filter' => 'unreadable inventory data file']],
    ],
    [
        'id' => 'T01-M4',
        'name' => 'sign-up sends no first verification link',
        'file' => 'app/Http/Controllers/Auth/OnboardingController.php',
        'old' => '                $result[\'user\']->sendEmailVerificationNotification();',
        'new' => '                // mutated away',
        'tests' => [['file' => 'tests/Feature/VerifiedEmailGateTest.php', 'filter' => 'SENT its first verification link']],
    ],
    [
        'id' => 'T01-M5',
        'name' => 'an already-verified social founder is sent a link too',
        'file' => 'app/Http/Controllers/Auth/OnboardingController.php',
        'old' => '        if (! $result[\'user\']->hasVerifiedEmail()) {',
        'new' => '        if (true) {',
        'tests' => [['file' => 'tests/Feature/GoogleSignInTest.php', 'filter' => 'sent NO verification link']],
    ],
    [
        'id' => 'T01-M6',
        'name' => 'a mail server that refuses fails the sign-up',
        'file' => 'app/Http/Controllers/Auth/OnboardingController.php',
        'old' => '            } catch (Throwable $e) {
                Log::warning(\'The first verification link could not be sent at sign-up.\'',
        'new' => '            } catch (\LogicException $e) {
                Log::warning(\'The first verification link could not be sent at sign-up.\'',
        'tests' => [['file' => 'tests/Feature/VerifiedEmailGateTest.php', 'filter' => 'a mail server that refuses']],
    ],
    [
        'id' => 'T01-M7',
        'name' => 'the console hands a verification email for a never-deliverable address to the mailer',
        'file' => 'app/Http/Controllers/Admin/SupportController.php',
        'old' => '        if (! $discards && ($refused = $this->refusedUndeliverable($account, \'the verification email\')) !== null) {',
        'new' => '        if (false) {',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformSupportToolsTest.php', 'filter' => 'can NEVER receive mail is REFUSED']],
    ],
    [
        'id' => 'T01-M8',
        'name' => 'the console hands a reset link for a never-deliverable address to the mailer',
        'file' => 'app/Http/Controllers/Admin/SupportController.php',
        'old' => '        if (! $discards && ($refused = $this->refusedUndeliverable($account, \'the reset link\')) !== null) {',
        'new' => '        if (false) {',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformSupportToolsTest.php', 'filter' => 'can NEVER receive mail is REFUSED']],
    ],
];
