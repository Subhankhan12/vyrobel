<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureOwnerApiScope;
use App\Http\Middleware\ResolveApiOwnerContext;
use App\Models\OwnerStatement;
use App\Models\Unit;
use App\Services\OwnerPortalService;
use App\Support\Owner\OwnerBookingsReader;
use Illuminate\Http\JsonResponse;

/**
 * The scoped, owner + tenant-isolated READ API (Wave D · OAuth · Phase 3). Every action runs behind
 * `auth:api` + {@see ResolveApiOwnerContext} + {@see EnsureOwnerApiScope}
 * — so the OwnerScope + OrganizationScope are already active for THIS token's owner + org, and the token has been
 * proven to carry the endpoint's scope AND a capability the owner's role holds. Each action returns ONLY the safe,
 * owner-scoped fields (guest NAME-only #28; never guest contact, door codes or Wi-Fi) — the same shapes the portal
 * shows, read through the same certified services (anti-drift). The isolation is the scopes' job; this class just
 * shapes the JSON.
 */
class OwnerApiController extends Controller
{
    /** GET /api/owner/bookings — the owner's bookings (name-only #28). Scope: read:bookings. */
    public function bookings(OwnerBookingsReader $reader): JsonResponse
    {
        $data = array_map(fn (array $r): array => [
            'id' => $r['id'],
            'reference' => $r['reference'],
            'guest' => $r['guest'],          // NAME only — never email/phone (#28)
            'unit' => $r['unit_label'],
            'check_in' => $r['check_in_sort'],
            'check_out' => $r['check_out_sort'],
            'nights' => $r['nights'],
            'source' => $r['source'],
            'status' => $r['status'],
            'net_minor' => $r['net_minor'],  // owner's own attributed net, per currency
        ], $reader->rows());

        return response()->json(['data' => $data]);
    }

    /** GET /api/owner/statements — the owner's statements. Scope: read:statements. */
    public function statements(OwnerPortalService $portal): JsonResponse
    {
        $data = $portal->statements()->map(fn (OwnerStatement $s): array => [
            'id' => (string) $s->id,
            'period_start' => $s->period_start->toDateString(),
            'period_end' => $s->period_end->toDateString(),
            'status' => (string) $s->status,
            'currency' => (string) $s->currency,
            'net_payable_minor' => (int) $s->net_payable_minor,
        ])->values()->all();

        return response()->json(['data' => $data]);
    }

    /** GET /api/owner/units — the owner's units (physical facts only — never door codes / Wi-Fi #33). Scope: read:units. */
    public function units(OwnerPortalService $portal): JsonResponse
    {
        $data = $portal->units()->map(fn (Unit $u): array => [
            'id' => (string) $u->id,
            'code' => (string) $u->code,
            'name' => $u->name,
            'kind' => $u->unit_kind,
            'room_type' => $u->roomType?->name,
            'max_occupancy' => $u->max_occupancy,
            'is_active' => (bool) $u->is_active,
        ])->values()->all();

        return response()->json(['data' => $data]);
    }
}
