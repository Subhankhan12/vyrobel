<?php

// Run on the host, from inside a release:   php mark-internal.php initial <prefix,prefix,...> "<reason>"
//                                          php mark-internal.php by-name "<exact org name>" "<reason>"
//
// ⚠️ IDENTIFIERS ARE READ, NEVER TYPED. `initial` refuses unless the organisations that exist are EXACTLY the set
//    measured in Phase 0 (by id prefix, read from the survey) — any extra org, e.g. a real customer who signed up
//    since, stops it before it writes. `by-name` refuses unless exactly ONE org has that name.
// ⚠️ Every mark writes an append-only ledger row with the reason (actor: CLI), and the result is READ BACK.

declare(strict_types=1);

use App\Models\Organization;
use App\Support\Platform\PlatformAuditLogger;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$base = getcwd();
require $base.'/vendor/autoload.php';
$app = require $base.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

[$script, $mode, $arg, $reason] = array_pad($argv, 4, '');
if (trim($reason) === '') {
    fwrite(STDERR, "  ✗ a reason is required\n");
    exit(2);
}

$orgs = match ($mode) {
    'initial' => (function () use ($arg) {
        $expected = array_filter(explode(',', $arg));
        $all = Organization::query()->withTrashed()->orderBy('created_at')->get();
        $prefixes = $all->map(fn ($o) => substr((string) $o->id, 0, 8))->sort()->values()->all();
        sort($expected);
        if ($prefixes !== $expected) {
            fwrite(STDERR, '  ✗ REFUSED: the organisations present are ['.implode(',', $prefixes).'], not the measured ['.implode(',', $expected)."] — something changed since Phase 0; nothing written\n");
            exit(1);
        }

        return $all;
    })(),
    'by-name' => (function () use ($arg) {
        $hits = Organization::query()->where('name', $arg)->get();
        if ($hits->count() !== 1) {
            fwrite(STDERR, "  ✗ REFUSED: {$hits->count()} organisations are named [{$arg}] — expected exactly one; nothing written\n");
            exit(1);
        }

        return $hits;
    })(),
    default => (function () {
        fwrite(STDERR, "  ✗ mode must be initial|by-name\n");
        exit(2);
    })(),
};

foreach ($orgs as $org) {
    $was = $org->isInternal();
    if (! $was) {
        $org->forceFill(['is_internal' => true])->save();
        app(PlatformAuditLogger::class)->record('platform.customer.mark_internal', ['via' => 'cli'], 'organization', (string) $org->id, reason: $reason);
    }
    $fresh = DB::table('organizations')->where('id', $org->id)->first(['is_internal', 'name']);
    printf("  %s…  %-46s is_internal: %s → %s   users=%d props=%d subs=%d\n",
        substr((string) $org->id, 0, 8), mb_strimwidth((string) $fresh->name, 0, 46, '…'), $was ? 'yes' : 'no', $fresh->is_internal ? 'YES' : 'no (!!)',
        DB::table('organization_user')->where('organization_id', $org->id)->count(),
        DB::table('properties')->where('organization_id', $org->id)->count(),
        DB::table('subscriptions')->where('organization_id', $org->id)->count());
}
printf("  customers (not internal) now on the platform: %d\n", Organization::query()->customers()->count());
