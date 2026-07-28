<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Livewire\Concerns\PreparesTwoFactor;
use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\Passkey;
use App\Support\Auth\TrustedDeviceService;
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
 * HONESTY: the "account protection" list + the security SCORE are DERIVED FROM TRUE STATE (a password is set;
 * whether 2FA is confirmed-active; whether unused recovery codes exist) — the score is a deterministic function
 * of those real protections (password 40 + 2FA 40 + recovery codes 20 = /100), NOT a fabricated number, and the
 * "Boost your score" items are the REAL remaining protections, each linking to its REAL control (never a fake
 * point economy). Active sessions are the REAL rows in the `sessions` table. "Last sign-in" is the real
 * `last_login_at`. Everything the wireframe draws over no backend — passkeys, login-approval / trusted-locations,
 * connected apps, full sign-in history, login-alerts, session-timeout — is an honest "Soon" card, never a fake toggle.
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

        // The 3 REAL protections + their weights → a deterministic derived score (not a fabricated number, #39).
        $protections = [
            ['label' => 'Password set', 'done' => true, 'pts' => 40, 'hint' => 'Change it below any time.', 'boost_desc' => 'A strong password is your baseline.'],
            ['label' => 'Two-factor authentication', 'done' => $twoFactorOn, 'pts' => 40, 'hint' => $twoFactorOn ? 'On — a code is required at sign-in.' : 'Add a second layer beyond your password.', 'boost_desc' => 'Require a code from your phone at sign-in.'],
            ['label' => 'Recovery codes saved', 'done' => $twoFactorOn && $unusedCodes > 0, 'pts' => 20, 'hint' => $twoFactorOn ? $unusedCodes.' unused code(s) — keep them somewhere safe.' : 'Available once two-factor is on.', 'boost_desc' => 'Back-up codes to get in if you lose your device.'],
        ];
        $score = array_sum(array_map(fn (array $p): int => $p['done'] ? $p['pts'] : 0, $protections));
        $onCount = count(array_filter($protections, fn (array $p): bool => $p['done']));
        // The remaining REAL protections to turn on (each links to its real control below) — never invented.
        $boost = array_values(array_map(
            fn (array $p): array => ['label' => $p['label'], 'desc' => $p['boost_desc'], 'pts' => '+'.$p['pts']],
            array_filter($protections, fn (array $p): bool => ! $p['done']),
        ));
        $sessions = $this->activeSessions((string) $user->getKey());

        return view('livewire.owner.owner-security', [
            'viewer' => $this->viewer(),
            'twoFactor' => $this->twoFactorPanel('owner.security.2fa'),
            'isPrimaryOwner' => app(OwnerContext::class)->isPrimaryOwner(),
            'checklist' => array_map(fn (array $p): array => ['label' => $p['label'], 'done' => $p['done'], 'hint' => $p['hint']], $protections),
            'score' => [
                'num' => $score,
                'tier' => $score >= 100 ? 'Strong' : ($score >= 60 ? 'Good' : 'Basic'),
                'caption' => $score >= 100
                    ? 'All protections are on — your account is fully secured.'
                    : ($twoFactorOn ? 'Save your recovery codes to reach full protection.' : 'Turn on two-factor to strengthen your account.'),
                'on' => $onCount,
                'total' => count($protections),
                'to_full' => 100 - $score,
                'sessions' => count($sessions),
                'two_factor' => $twoFactorOn,
                'boost' => $boost,
            ],
            'lastLogin' => $user->last_login_at instanceof Carbon ? $user->last_login_at : null,
            'sessions' => $sessions,
            'loginApprovalOn' => $user->requiresLoginApproval(),
            // Passkeys (Wave B · GATE 4) — the user's registered WebAuthn credentials, self-only.
            'passkeys' => Passkey::query()
                ->where('user_id', (string) $user->getKey())
                ->orderByDesc('last_used_at')
                ->get()
                ->map(fn (Passkey $p): array => [
                    'id' => (string) $p->id,
                    'label' => (string) ($p->label ?? 'Passkey'),
                    'last_used' => $p->last_used_at instanceof Carbon ? $p->last_used_at->diffForHumans() : 'not used yet',
                ])->all(),
            // Trusted devices (Wave B · GATE 4) — the REAL live trusts for this user (skip-2FA), self-only.
            'trustedDevices' => app(TrustedDeviceService::class)->forUser($user, request())
                ->map(fn ($d): array => [
                    'id' => (string) $d->id,
                    'label' => (string) ($d->label ?? 'Trusted device'),
                    'ip' => (string) ($d->ip ?? ''),
                    'last_used' => $d->last_used_at instanceof Carbon ? $d->last_used_at->diffForHumans() : '—',
                    'expires' => $d->expires_at instanceof Carbon ? $d->expires_at->diffForHumans() : '—',
                    'current' => (bool) $d->getAttribute('is_current'),
                ])->all(),
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
