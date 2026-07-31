<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnforceIpAllowlist;
use App\Models\AuditEvent;
use App\Models\IpAllowRule;
use App\Support\Auth\TrustedDeviceService;
use App\Support\Events\AuditEventRecorder;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * #104b GATE 1 — the operator Security writes: the REAL IP-allowlist (add/remove — genuinely enforced by
 * {@see EnforceIpAllowlist}), the trusted-device revoke (reuse of the shared #66
 * TrustedDeviceService), and a self-scoped account+activity data export. IP writes are `can:settings.manage`
 * (org-wide security control) + audited (#20); revoke + export are self-only (re-auth / throttle). None are
 * read-only-gated — account security must stay usable on a paused workspace.
 */
class OperatorSecurityController extends Controller
{
    public function addIpRule(Request $request, AuditEventRecorder $audit): RedirectResponse
    {
        $data = $request->validate([
            'ip_address' => ['required', 'string', 'max:64', function (string $attr, mixed $value, \Closure $fail): void {
                if (! $this->isValidIpOrCidr((string) $value)) {
                    $fail('Enter a valid IP address or CIDR block (e.g. 203.0.113.4 or 203.0.113.0/24).');
                }
            }],
            'label' => ['nullable', 'string', 'max:80'],
        ]);

        $isFirst = IpAllowRule::query()->count() === 0;
        $ip = trim((string) $data['ip_address']);

        // Re-adding the same address would hit the unique(organization_id, ip_address) index and 500. Treat a
        // duplicate as a no-op with a friendly notice (the rule is already enforcing).
        if (IpAllowRule::query()->where('ip_address', $ip)->exists()) {
            return $this->back('notice', $ip.' is already on the allowlist.');
        }

        $rule = IpAllowRule::create(['ip_address' => $ip, 'label' => $data['label'] ?? null, 'created_by' => $request->user()?->getKey()]);
        $audit->record('app.ip_allow_rule.added', $rule, null, ['ip_address' => $ip]);

        // Lockout mitigation — turning ON the allowlist (the FIRST rule) with a rule that doesn't cover the
        // admin's OWN current IP would lock them out on the next request. Auto-add the current IP so the person
        // enabling it keeps access; a clear label says so.
        $current = (string) ($request->ip() ?? '');
        if ($isFirst && $current !== '' && ! IpUtils::checkIp($current, [$ip])
            && ! IpAllowRule::query()->where('ip_address', $current)->exists()) {
            $auto = IpAllowRule::create(['ip_address' => $current, 'label' => 'This device (auto-added)', 'created_by' => $request->user()?->getKey()]);
            $audit->record('app.ip_allow_rule.added', $auto, null, ['ip_address' => $current, 'auto' => true]);

            return $this->back('notice', 'IP allowlist enabled. We also added your current address ('.$current.') so you don’t lock yourself out.');
        }

        return $this->back('notice', 'Added '.$ip.' to the allowlist.');
    }

    public function removeIpRule(Request $request, string $rule, AuditEventRecorder $audit): RedirectResponse
    {
        $model = IpAllowRule::query()->findOrFail($rule);   // tenant-scoped → cross-org 404
        $ip = (string) $model->ip_address;
        $model->delete();
        $audit->record('app.ip_allow_rule.removed', null, ['ip_address' => $ip], null);

        return $this->back('notice', 'Removed '.$ip.' from the allowlist.');
    }

    public function revokeTrustedDevice(Request $request, TrustedDeviceService $devices, AuditEventRecorder $audit): RedirectResponse
    {
        $data = $request->validate([
            'device_id' => ['required', 'string'],
            'current_password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        $wasCurrent = $devices->revoke($user, (string) $data['device_id'], $request);
        $audit->record('app.trusted_device_revoked', $user, null, ['device_id' => (string) $data['device_id']]);

        $response = $this->back('notice', 'Trusted device revoked.');

        return $wasCurrent !== null ? $response->withCookie($devices->forgetCookie()) : $response;
    }

    /**
     * "Request export" — a self-scoped account + own-activity export (#28: NOT org reservations / guest PII; an
     * operator's personal export is their account + the audit_events THEY caused, name-only). JSON or CSV; a real
     * date range (all-time / this-year / custom) filters the activity. Throttled at the route.
     */
    public function exportData(Request $request): StreamedResponse
    {
        $user = $request->user();
        $format = $request->query('format') === 'csv' ? 'csv' : 'json';
        [$from, $to, $rangeLabel] = $this->resolveRange($request);

        $activity = AuditEvent::query()
            ->where('actor_id', (string) $user->getKey())
            ->when($from !== null, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to !== null, fn ($q) => $q->where('created_at', '<', $to))
            ->orderByDesc('created_at')
            ->limit(5000)
            ->get(['action', 'created_at']);

        $roles = $user->getRoleNames()->all();
        $filename = 'my-vyrobel-data-'.CarbonImmutable::now()->format('Y-m-d').'.'.$format;

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($user, $roles, $activity, $rangeLabel): void {
                $out = fopen('php://output', 'w');
                fputcsv($out, [$this->csvSafe('Vyrobel account export'), $this->csvSafe($rangeLabel)]);
                fputcsv($out, ['Name', 'Email', 'Locale', 'Roles']);
                fputcsv($out, [$this->csvSafe((string) $user->name), $this->csvSafe((string) $user->email), $this->csvSafe((string) ($user->locale ?? '')), $this->csvSafe(implode(', ', $roles))]);
                fputcsv($out, []);
                fputcsv($out, [$this->csvSafe('Activity'), '']);
                fputcsv($out, ['When', 'Action']);
                foreach ($activity as $e) {
                    fputcsv($out, [CarbonImmutable::parse($e->created_at)->toIso8601String(), $this->csvSafe((string) $e->action)]);
                }
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv']);
        }

        $data = [
            'exported_for' => ['name' => (string) $user->name, 'email' => (string) $user->email, 'locale' => (string) ($user->locale ?? ''), 'roles' => $roles],
            'range' => $rangeLabel,
            'activity' => $activity->map(fn ($e): array => ['at' => CarbonImmutable::parse($e->created_at)->toIso8601String(), 'action' => (string) $e->action])->all(),
            'exported_at' => CarbonImmutable::now()->toIso8601String(),
        ];

        return response()->streamDownload(function () use ($data): void {
            echo (string) json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }, $filename, ['Content-Type' => 'application/json']);
    }

    /** @return array{0: ?CarbonImmutable, 1: ?CarbonImmutable, 2: string} */
    private function resolveRange(Request $request): array
    {
        $range = (string) $request->query('range', 'all');
        $today = CarbonImmutable::today();

        return match ($range) {
            'year' => [$today->startOfYear(), $today->addDay(), 'This year'],
            'custom' => [
                ($f = $request->query('from')) ? CarbonImmutable::parse((string) $f)->startOfDay() : null,
                ($t = $request->query('to')) ? CarbonImmutable::parse((string) $t)->addDay()->startOfDay() : null,
                'Custom range',
            ],
            default => [null, null, 'All time'],
        };
    }

    private function isValidIpOrCidr(string $value): bool
    {
        if (str_contains($value, '/')) {
            [$ip, $prefix] = explode('/', $value, 2);

            return filter_var($ip, FILTER_VALIDATE_IP) !== false && ctype_digit($prefix) && (int) $prefix >= 0 && (int) $prefix <= 128;
        }

        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    private function csvSafe(string $value): string
    {
        $value = str_replace(["\r", "\n"], ' ', $value);

        return preg_match('/^[=+\-@\t]/', $value) === 1 ? "'".$value : $value;
    }

    private function back(string $key, string $message): RedirectResponse
    {
        return redirect()->to(route('app.security'))->with('security_'.$key, $message);
    }
}
