<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PlatformAnnouncement;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * ADMIN 01 · PHASE 5 — a tenant user dismisses an announcement FOR THEMSELVES. Self-only by construction: it records
 * (announcement, the signed-in user) and nothing else, and only for an announcement that actually reaches their
 * organisation — a crafted id for someone else's notice is a 404.
 */
class AnnouncementDismissController extends Controller
{
    public function __invoke(Request $request, string $announcement): RedirectResponse
    {
        $user = $request->user();
        $orgId = app(TenantContext::class)->id();
        $row = PlatformAnnouncement::query()->find($announcement);

        abort_if($user === null || $orgId === null || $row === null || ! $row->reaches($orgId), 404);

        DB::table('announcement_dismissals')->insertOrIgnore([
            'id' => (string) Str::ulid(), 'announcement_id' => $row->id, 'user_id' => $user->getAuthIdentifier(), 'dismissed_at' => now(),
        ]);

        return back();
    }
}
