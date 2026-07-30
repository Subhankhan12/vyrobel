<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Support\Email\EmailTemplateCatalog;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Response;

/**
 * Operator Email Templates — the customization MANAGER (#86, exact-match). The 5 real guest emails (#83), each
 * with its real trigger, channel, an Active/off toggle (real `enabled` — OFF genuinely stops the send), a
 * Preview, and an Edit link into the editor. NO fabricated open-rate/send-count (no send-tracking exists —
 * founder decision A): the honest stat is the live status + trigger.
 *
 * Behind the back-office gate (#26); tenant-scoped ({@see TenantContext}). The customization it manages is what
 * the real Mailables render at send — this is not a static catalog any more.
 */
class EmailTemplateController extends Controller
{
    public function index(): Response
    {
        $orgId = (string) app(TenantContext::class)->id();

        // The org's saved rows (enabled flag + updated_at), keyed by template_key. A missing row = default ON.
        $stored = EmailTemplate::query()
            ->where('organization_id', $orgId)
            ->get()
            ->keyBy('template_key');

        $templates = [];
        foreach (EmailTemplateCatalog::all() as $key => $entry) {
            $row = $stored->get($key);
            $templates[$key] = [
                'key' => $key,
                'label' => $entry['label'],
                'trigger' => $entry['trigger'],
                'channel' => $entry['channel'],
                'preview' => $entry['preview'],
                'enabled' => $row === null ? true : (bool) $row->enabled,
                'customized' => $row !== null && ($row->subject !== null || $row->heading !== null || $row->message !== null || $row->button_label !== null || $row->blocks !== null),
            ];
        }

        return response()->view('operator.email-templates', [
            'templates' => $templates,
            'canManage' => (bool) auth()->user()?->can('properties.manage'),
        ]);
    }
}
