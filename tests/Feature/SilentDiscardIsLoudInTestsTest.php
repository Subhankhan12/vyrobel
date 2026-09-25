<?php

declare(strict_types=1);

use App\Models\Owner;
use Illuminate\Database\Eloquent\MassAssignmentException;

// ⚠️ FINISH 01 · Part C — Eloquent drops a key a model does not accept from create()/update()/fill() SILENTLY in
// production (Laravel's default, kept by ruling). In the TEST environment it throws (tests/TestCase.php), so every write
// the suite makes is checked — the class a static sweep cannot see (220 runtime-built write sites in app/). Switched on
// 2026-09-25: it found 30 sites (22 test fixtures, the demo seeder run guarded, and one fixture that was silently not
// linking refunds the way the product does). This test fails if the switch is ever removed.

it('⚠️ a key a model does not accept THROWS in the test environment — it can never again vanish unseen', function () {
    expect(fn () => (new Owner)->fill(['legal_name' => 'Real column', 'no_such_column' => 'silently dropped']))
        ->toThrow(MassAssignmentException::class);
});
