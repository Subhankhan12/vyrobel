<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AuditController;
use App\Models\PlatformAdminAction;
use Illuminate\Foundation\Testing\DatabaseTransactions;

// ⚠️⚠️ ADMIN 03 · F1 — the audit log's default view is "Changes & sign-ins". 🔬 Walked live: nine page-view actions
// added in Phases 2–5 were missing from the hand-kept read list, so viewing the triage queue appeared as a CHANGE.
// This fails the moment a new read action is not listed — the list can no longer drift in silence.

uses(DatabaseTransactions::class);

/** Every action string the application records as a READ: each PlatformQuery::read() call, and each recorded view/page. */
function auditReadActionsInCode(): array
{
    $found = [];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path())) as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }
        $code = collect(token_get_all((string) file_get_contents($file->getPathname())))
            ->reject(fn ($t) => is_array($t) && in_array($t[0], [T_COMMENT, T_DOC_COMMENT], true))
            ->map(fn ($t) => is_array($t) ? $t[1] : $t)->implode('');
        preg_match_all("/->read\\(\\s*'(platform\\.[a-z0-9_.]+)'/", $code, $reads);
        preg_match_all("/->record\\(\\s*'(platform\\.[a-z0-9_.]+\\.(?:view|page))'/", $code, $views);
        $found = [...$found, ...$reads[1], ...$views[1]];
    }

    return array_values(array_unique($found));
}

it('⚠️⚠️ every read action the code records is classed as a READ — none leaks into "Changes & sign-ins"', function () {
    $inCode = auditReadActionsInCode();
    expect(count($inCode))->toBeGreaterThan(15);   // the instrument sees the reads, not nothing

    expect(array_values(array_diff($inCode, AuditController::READ_ACTIONS)))->toBe([]);
})->group('security');

it('the default view shows a change and hides a page view — from the ledger, as the page renders it', function () {
    $admin = platformAdmin();
    foreach (['platform.customers.triage', 'platform.revenue', 'platform.privacy.view'] as $read) {
        PlatformAdminAction::create(['platform_admin_id' => $admin->id, 'action' => $read, 'created_at' => now()]);
    }
    PlatformAdminAction::create(['platform_admin_id' => $admin->id, 'action' => 'platform.switch.off', 'reason' => 'Audit filter probe — a real change.', 'created_at' => now()]);

    // ⚠️ The table rows only — the "Any action" filter lists every action name, so a whole-page search proves nothing.
    $rows = function (array $query) use ($admin): string {
        $this->flushSession();
        $html = (string) $this->actingAs($admin, 'admin')->get(route('admin.audit.index', $query))->assertOk()->getContent();

        return preg_match('/<tbody>(.*?)<\/tbody>/s', $html, $m) ? $m[1] : '';
    };

    $changes = $rows(['admin' => $admin->id]);
    expect($changes)->toContain('platform.switch.off')->not->toContain('platform.customers.triage');
    expect($changes)->not->toContain('platform.revenue');
    $reads = $rows(['admin' => $admin->id, 'kind' => 'reads']);
    expect($reads)->toContain('platform.customers.triage')->not->toContain('platform.switch.off');
})->group('security');
