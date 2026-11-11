<?php

declare(strict_types=1);

// Mutation set dec-r-guest-match — DEC-r (2026-11-11): the existing guest is OFFERED for a typed email. Each guard removed one at a time.
// Run with `php tests/Mutations/run.php --set=dec-r-guest-match`.

$match = 'app/Support/Guest/GuestEmailMatch.php';
$test = 'tests/Feature/GuestEmailMatchTest.php';

return [
    [
        'id' => 'GM-M1',
        'name' => 'the lookup compares the plain email instead of the blind index (it finds nobody)',
        'file' => $match,
        'old' => "            ->where('email_hash', Guest::emailHash(\$email))",
        'new' => "            ->where('email_hash', \$email)",
        'tests' => [['file' => $test, 'filter' => 'NAME and stay history']],
    ],
    [
        'id' => 'GM-M2',
        'name' => 'an ERASED guest is offered',
        'file' => $match,
        'old' => "            ->whereNull('anonymized_at')\n",
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'another organisation']],
    ],
    [
        'id' => 'GM-M3',
        'name' => 'a guest whose erasure is scheduled is offered',
        'file' => $match,
        'old' => "            ->whereNull('scheduled_deletion_at')\n",
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'another organisation']],
    ],
    [
        'id' => 'GM-M4',
        'name' => 'the lookup returns more than names (the email rides along)',
        'file' => $match,
        'old' => "                    'id' => (string) \$guest->getKey(),\n",
        'new' => "                    'id' => (string) \$guest->getKey(),\n                    'email' => (string) \$guest->email,\n",
        'tests' => [['file' => $test, 'filter' => 'NAME and stay history']],
    ],
    [
        'id' => 'GM-M5',
        'name' => 'the server net is off: a matching email silently creates a second guest again',
        'file' => 'app/Http/Controllers/Operator/ReservationCreateController.php',
        'old' => "        if (\$email !== '' && ! \$request->boolean('guest_create_anyway')) {",
        'new' => '        if (false) {',
        'tests' => [['file' => $test, 'filter' => 'server\'s own net']],
    ],
    [
        'id' => 'GM-M6',
        'name' => '"Create a new one" is ignored: the net refuses even after the operator chose it',
        'file' => 'app/Http/Controllers/Operator/ReservationCreateController.php',
        'old' => "        if (\$email !== '' && ! \$request->boolean('guest_create_anyway')) {",
        'new' => "        if (\$email !== '') {",
        'tests' => [['file' => $test, 'filter' => 'server\'s own net']],
    ],
    [
        'id' => 'GM-M7',
        'name' => 'a booking can be made onto an erased guest through existing_guest_id',
        'file' => 'app/Http/Controllers/Operator/ReservationCreateController.php',
        'old' => "            \$guest = Guest::query()->whereNull('anonymized_at')->whereNull('scheduled_deletion_at')->find(\$existingId);",
        'new' => '            $guest = Guest::query()->find($existingId);',
        'tests' => [['file' => $test, 'filter' => 'Use this guest']],
    ],
    [
        'id' => 'GM-M8',
        'name' => 'the lookup route is open to any signed-in role (the reservations.create gate is gone)',
        'file' => 'routes/web.php',
        'old' => "->middleware(['can:reservations.create', 'throttle:60,1'])->name('reservations.guest-match');",
        'new' => "->middleware(['throttle:60,1'])->name('reservations.guest-match');",
        'tests' => [['file' => $test, 'filter' => 'gated like the booking itself']],
    ],
    [
        'id' => 'GM-M9',
        'name' => 'the lookup is not throttled',
        'file' => 'routes/web.php',
        'old' => "->middleware(['can:reservations.create', 'throttle:60,1'])->name('reservations.guest-match');",
        'new' => "->middleware(['can:reservations.create'])->name('reservations.guest-match');",
        'tests' => [['file' => $test, 'filter' => 'gated like the booking itself']],
    ],
];
