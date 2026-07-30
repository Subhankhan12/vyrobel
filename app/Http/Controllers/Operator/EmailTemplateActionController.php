<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Services\EmailTemplateService;
use App\Support\Email\EmailPreviewSample;
use App\Support\Email\EmailTemplateCatalog;
use App\Support\Mail\MailSafetyGuard;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * Operator Email Templates writes (#86) — the three guarded writers behind the customization manager, via the
 * locked operator-mutation pattern (POST → controller → {@see EmailTemplateService} → PRG). The saved content
 * is what the REAL #83 guest Mailables render at send, so an edit here genuinely changes the email.
 *
 * PERMISSION (#30): every route carries `can:properties.manage` (Property Owner / Group Admin) — the same gate
 * as Settings. TENANT: the org is the CURRENT tenant ({@see TenantContext}, never a request-supplied id), so a
 * write can only ever touch the operator's own org's template; the template key is whitelisted against the
 * catalog in the service (a foreign key 404s).
 */
class EmailTemplateActionController extends Controller
{
    /** Save a template's content + block toggles → the real Mailables read it at send. */
    public function save(Request $request, string $key, EmailTemplateService $service): RedirectResponse
    {
        abort_if(EmailTemplateCatalog::get($key) === null, 404);
        $orgId = (string) app(TenantContext::class)->id();

        $data = $request->validate([
            'subject' => ['nullable', 'string', 'max:255'],
            'heading' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
            'button_label' => ['nullable', 'string', 'max:120'],
            'blocks' => ['array'],
            'blocks.*' => ['nullable'],
        ]);

        $service->save($orgId, $key, [
            'subject' => $data['subject'] ?? null,
            'heading' => $data['heading'] ?? null,
            'message' => $data['message'] ?? null,
            'button_label' => $data['button_label'] ?? null,
            'blocks' => $this->normalizeBlocks($key, $request),
        ], $request->user());

        return redirect()->to(route('app.email-templates.edit', $key))->with('email_template_notice', 'Template saved — your guest emails now use it.');
    }

    /** Enable/disable an email — OFF genuinely stops the send (checked by the real triggers). */
    public function toggle(Request $request, string $key, EmailTemplateService $service): RedirectResponse
    {
        abort_if(EmailTemplateCatalog::get($key) === null, 404);
        $orgId = (string) app(TenantContext::class)->id();

        $enabled = $request->boolean('enabled');
        $service->setEnabled($orgId, $key, $enabled, $request->user());

        $label = EmailTemplateCatalog::get($key)['label'] ?? $key;

        return redirect()->to(route('app.email-templates'))
            ->with('email_template_notice', $enabled ? "{$label} is now active." : "{$label} is now off — it won’t send.");
    }

    /**
     * A REAL test send (founder decision B) — the CUSTOMIZED Mailable rendered with the tenant's real sample
     * reservation, sent to the OPERATOR'S OWN email. Never a fake toast: it goes through the real mail path,
     * so {@see MailSafetyGuard} downgrades to `log`/Mailpit outside production (never a real guest inbox).
     */
    public function testSend(Request $request, string $key): RedirectResponse
    {
        abort_if(EmailTemplateCatalog::get($key) === null, 404);

        $mailable = EmailPreviewSample::mailable($key);
        abort_if($mailable === null, 404, 'No sample reservation exists yet — create a booking first.');

        $email = (string) $request->user()->email;
        abort_if($email === '', 422, 'Your account has no email address to send a test to.');

        Mail::to($email)->send($mailable);

        return redirect()->to(route('app.email-templates.edit', $key))
            ->with('email_template_notice', "Test email sent to {$email}.");
    }

    /**
     * The block toggles the operator actually submitted — for EVERY block this template defines, an unchecked
     * box is absent from the POST, so we read presence as the boolean. Only real blocks are kept.
     *
     * @return array<string, bool>
     */
    private function normalizeBlocks(string $key, Request $request): array
    {
        $catalog = EmailTemplateCatalog::get($key);
        $submitted = (array) $request->input('blocks', []);
        $blocks = [];
        foreach (array_keys($catalog['blocks']) as $blockKey) {
            $blocks[$blockKey] = (bool) ($submitted[$blockKey] ?? false);
        }

        return $blocks;
    }
}
