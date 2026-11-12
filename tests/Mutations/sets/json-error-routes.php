<?php

declare(strict_types=1);

// Mutation set json-error-routes — D-GM-EMPTY's sweep (2026-11-12): the JSON endpoints that live outside `/api` answer errors as JSON, and only those.
// Run with `php tests/Mutations/run.php --set=json-error-routes`.

$test = 'tests/Feature/NonApiJsonEndpointsAnswerJsonTest.php';

return [
    [
        'id' => 'JE-M1',
        'name' => 'the exception renderer forgets the listed routes (a bad booking call is a redirect again)',
        'file' => 'bootstrap/app.php',
        'old' => "fn (Request \$request) => \$request->is('api/*') || JsonErrorRoutes::matches(\$request),",
        'new' => "fn (Request \$request) => \$request->is('api/*'),",
        'tests' => [['file' => $test, 'filter' => 'with the field errors']],
    ],
    [
        'id' => 'JE-M2',
        'name' => 'the booking confirmation is left off the list (an unknown quote is an HTML 404 page for a machine client)',
        'file' => 'app/Support/Http/JsonErrorRoutes.php',
        'old' => "        'booking.confirmation',\n",
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'JSON 404']],
    ],
    [
        'id' => 'JE-M3',
        'name' => 'the booking search is left off the list (the scan finds a JSON endpoint that calls validate() and is not listed)',
        'file' => 'app/Support/Http/JsonErrorRoutes.php',
        'old' => "        'booking.search',\n",
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'new one cannot be added and forgotten']],
    ],
    [
        'id' => 'JE-M4',
        'name' => 'every route is treated as a JSON endpoint (the checkout page form would answer JSON instead of going back with its errors)',
        'file' => 'app/Support/Http/JsonErrorRoutes.php',
        'old' => 'return $request->routeIs(...self::NAMES);',
        'new' => 'return true;',
        'tests' => [['file' => $test, 'filter' => 'browser form']],
    ],
];
