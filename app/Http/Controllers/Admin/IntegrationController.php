<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformIntegration;
use App\Support\Platform\AdminReason;
use App\Support\Platform\ConsoleFields;
use App\Support\Platform\PlatformAuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * SUPER ADMIN · BUILD 2 · GATE 2 — platform channel-integration management. The founder's MASTER credentials
 * per booking channel (Channex / OTAs), stored ENCRYPTED at rest (see {@see PlatformIntegration}).
 *
 * 🔒 The secret is NEVER echoed after saving — the edit screen shows only whether a credential is configured
 * (masked), and an input to enter/ROTATE a new one (write-only). Managing credentials is an AUDITED admin
 * action; the audit records WHICH channel, NEVER the value. 🔒 The real channel SYNC is account-gated — the
 * test-connection is an honest STUB ("connects when the account is live"); the customer-self-connect model is
 * documented, built later.
 *
 * These are GLOBAL platform rows (not tenant-scoped) → no cross-tenant bypass; the /admin realm + 2FA gate it.
 */
class IntegrationController extends Controller
{
    public function __construct(private readonly PlatformAuditLogger $audit) {}

    /** List the channel catalog with each channel's configured/enabled status. */
    public function index(): View
    {
        $integrations = PlatformIntegration::query()->get()->keyBy('channel');

        return view('admin.integrations.index', ['integrations' => $integrations]);
    }

    /** Manage one channel's master credential (masked; never shows the stored secret). */
    public function edit(string $channel): View
    {
        abort_unless(array_key_exists($channel, PlatformIntegration::CHANNELS), 404);
        $integration = PlatformIntegration::query()->where('channel', $channel)->first();

        return view('admin.integrations.edit', [
            'channel' => $channel,
            'label' => PlatformIntegration::CHANNELS[$channel],
            'integration' => $integration,
        ]);
    }

    /** Save / ROTATE the master credential (encrypted, write-only) + enabled flag. Audited (never the value). */
    public function update(Request $request, string $channel): RedirectResponse
    {
        abort_unless(array_key_exists($channel, PlatformIntegration::CHANNELS), 404);

        $validated = $request->validate([
            'api_key' => ['nullable', 'string', 'max:500'],
            'api_secret' => ['nullable', 'string', 'max:500'],
            'is_enabled' => ['nullable', 'boolean'],
        ], [], ConsoleFields::NAMES);
        $reason = AdminReason::from($request);   // ⚠️ before any write — ADMIN 01 · Phase 1

        $integration = PlatformIntegration::query()->firstOrNew(['channel' => $channel]);

        // Write-only: only overwrite a credential field when a NEW value is submitted (blank = keep existing).
        $credentials = $integration->credentials ?? [];
        foreach (['api_key', 'api_secret'] as $field) {
            $value = trim((string) ($validated[$field] ?? ''));
            if ($value !== '') {
                $credentials[$field] = $value;
            }
        }

        $integration->fill([
            'credentials' => $credentials === [] ? null : $credentials,
            'is_enabled' => $request->boolean('is_enabled'),
            'status' => $credentials === [] ? 'not_configured' : 'configured',
        ])->save();

        // 🔒 Audit records WHICH channel + WHETHER configured — NEVER the credential value (#28 discipline).
        $this->audit->record('platform.integration.updated', ['channel' => $channel, 'configured' => $integration->isConfigured()], 'platform_integration', $integration->id, reason: $reason);

        return redirect()->route('admin.integrations.index')->with('admin_notice', PlatformIntegration::CHANNELS[$channel].' credentials saved (encrypted).');
    }

    /**
     * Test-connection STUB. The real channel APIs are account-gated, so this validates that a credential is
     * present + marks the integration tested; it honestly does NOT reach the live channel yet.
     */
    public function test(Request $request, string $channel): RedirectResponse
    {
        abort_unless(array_key_exists($channel, PlatformIntegration::CHANNELS), 404);
        // ⚠️ The test WRITES (status + last_tested_at), so it carries a reason like every other admin write.
        $reason = AdminReason::from($request);
        $integration = PlatformIntegration::query()->where('channel', $channel)->first();

        if ($integration === null || ! $integration->isConfigured()) {
            return redirect()->route('admin.integrations.edit', $channel)->with('admin_notice', 'Add a credential before testing.');
        }

        $integration->update(['status' => 'tested', 'last_tested_at' => now()]);
        $this->audit->record('platform.integration.tested', ['channel' => $channel], 'platform_integration', $integration->id, reason: $reason);

        return redirect()->route('admin.integrations.index')
            ->with('admin_notice', PlatformIntegration::CHANNELS[$channel].': credential present. A live connection test runs once the '.PlatformIntegration::CHANNELS[$channel].' account is connected (account-gated).');
    }
}
