<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Livewire\Concerns\PreparesTwoFactor;
use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Support\Owner\OwnerContext;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Owner "SECURITY" (owner account area · Gate 2) — the acting login's OWN security, on the owner design
 * system. READ view; every write is a guarded controller POST (never a Livewire action).
 *
 * Per-USER (auth-resolved), so accountant/view-only SUB-USERS manage their OWN password/2FA/sessions here.
 * The ONE primary-owner-only action is DEACTIVATE (route-gated by EnsurePrimaryOwner, #42).
 *
 * HONESTY: the "account protection" list is DERIVED FROM TRUE STATE (a password is set; whether 2FA is
 * confirmed-active; whether unused recovery codes exist) — NEVER a fabricated 0–100 score. Active sessions
 * are the REAL rows in the `sessions` table (database driver). "Last sign-in" is the real `last_login_at`.
 * Everything the wireframe draws over no backend — passkeys, login-approval / trusted-locations, connected
 * apps, full success/failed login history, login-alerts, session-timeout / password-expiry — is an honest
 * "Soon" card, never a fake toggle.
 */
#[Layout('components.layouts.owner')]
class OwnerSecurity extends Component
{
    use FormatsOwnerData, PreparesTwoFactor;

    public function render(): View
    {
        $user = auth()->user();
        $twoFactorOn = $user->hasTwoFactorEnabled();

        // Unused recovery codes (the cast decrypts to [{code, used_at}]) — real, never faked.
        $codes = $user->two_factor_recovery_codes ?? [];
        $unusedCodes = collect($codes)->filter(fn ($c) => ($c['used_at'] ?? null) === null)->count();

        return view('livewire.owner.owner-security', [
            'viewer' => $this->viewer(),
            'twoFactor' => $this->twoFactorPanel('owner.security.2fa'),
            'isPrimaryOwner' => app(OwnerContext::class)->isPrimaryOwner(),
            'checklist' => [
                ['label' => 'Password set', 'done' => true, 'hint' => 'Change it below any time.'],
                ['label' => 'Two-factor authentication', 'done' => $twoFactorOn, 'hint' => $twoFactorOn ? 'On — a code is required at sign-in.' : 'Add a second layer beyond your password.'],
                ['label' => 'Recovery codes saved', 'done' => $twoFactorOn && $unusedCodes > 0, 'hint' => $twoFactorOn ? $unusedCodes.' unused code(s) — keep them somewhere safe.' : 'Available once two-factor is on.'],
            ],
            'lastLogin' => $user->last_login_at instanceof Carbon ? $user->last_login_at : null,
            'sessions' => $this->activeSessions((string) $user->getKey()),
        ]);
    }

    /**
     * The user's REAL active sessions from the `sessions` table (database driver), current one flagged.
     *
     * @return list<array{current: bool, device: string, ip: string, last_active: Carbon}>
     */
    private function activeSessions(string $userId): array
    {
        $currentId = session()->getId();

        return DB::table('sessions')
            ->where('user_id', $userId)
            ->orderByDesc('last_activity')
            ->get()
            ->map(fn ($row): array => [
                'current' => $row->id === $currentId,
                'device' => $this->describeUserAgent((string) ($row->user_agent ?? '')),
                'ip' => (string) ($row->ip_address ?? '—'),
                'last_active' => Carbon::createFromTimestamp((int) $row->last_activity),
            ])
            ->values()
            ->all();
    }

    /** A plain, honest device string from a user-agent — no fabrication; "Unknown device" when unclear. */
    private function describeUserAgent(string $ua): string
    {
        $browser = match (true) {
            str_contains($ua, 'Edg') => 'Edge',
            str_contains($ua, 'Chrome') => 'Chrome',
            str_contains($ua, 'Firefox') => 'Firefox',
            str_contains($ua, 'Safari') => 'Safari',
            default => 'Browser',
        };
        $os = match (true) {
            str_contains($ua, 'iPhone') => 'iPhone',
            str_contains($ua, 'iPad') => 'iPad',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'Mac OS') => 'Mac',
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Linux') => 'Linux',
            default => '',
        };

        return $ua === '' ? 'Unknown device' : trim($browser.($os !== '' ? ' · '.$os : ''));
    }
}
