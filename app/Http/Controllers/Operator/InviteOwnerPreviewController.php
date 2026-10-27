<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Mail\OwnerInviteMail;
use App\Models\OwnerInvite;
use App\Services\OwnerInviteService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * "Preview the invite email" (the invite modal's link) — renders the REAL {@see OwnerInviteMail} template so
 * the operator sees exactly what the invitee receives. Operator-admin gated (`can:users.manage`, the same gate
 * as the invite write). PREVIEW ONLY: it builds a TRANSIENT (unsaved) invite for the current org + a
 * placeholder token → renders the Mailable via `->render()` (reusing the exact template + copy, so it can never
 * drift from the real email) — it NEVER writes a record, sends mail, or mints a redeemable token.
 */
class InviteOwnerPreviewController extends Controller
{
    public function show(Request $request): SymfonyResponse
    {
        $role = in_array($request->query('role'), ['owner', 'manager', 'accountant'], true)
            ? (string) $request->query('role')
            : 'owner';

        $invite = new OwnerInvite([
            'organization_id' => app(TenantContext::class)->id(),
            'email' => 'invitee@example.com',
            'role' => $role,
            'expires_at' => now()->addDays(OwnerInviteService::EXPIRY_DAYS),
        ]);

        // The REAL Mailable, rendered to HTML — a preview never sends and never redeems (placeholder token).
        $html = (new OwnerInviteMail($invite, 'preview-not-a-real-token', __('php/http/controllers/operator/invite_owner_preview_controller.sample_recipient')))->render();

        return Response::make($html);
    }
}
