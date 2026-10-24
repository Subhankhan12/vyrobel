<?php

declare(strict_types=1);

namespace App\Support\Security;

/**
 * THE ACCOUNT SECURITY SCORE — one rule for both Security pages, the back office's and the owner portal's (2026-10-24).
 *
 * The score is a deterministic function of real settings (#39): each protection that is ON earns its share of 100, and
 * nothing else moves it — no streaks, no rewards, no points that are not a setting. Before this, the owner page scored
 * three protections (40 + 40 + 20) and the back office had no score at all, so the same account read differently in the
 * two places it can be managed.
 *
 * ⚠️ A protection the account cannot turn on here is not OFFERED, and an unoffered protection never counts against
 * anyone: the offered ones share the 100 in proportion to their weights. Login approval, for example, is offered only
 * while email really goes out — its approval link is an email, and a link nobody receives would hold the sign-in for ever.
 *
 * ⚠️ The shares are rounded so they ADD UP: largest remainder, ties broken by the order below. The ring, "points
 * available" and every "+N pts" are then the same arithmetic — the ring never says 70 beside rows that add to 31.
 */
final class SecurityScore
{
    /** The weights, in the order the caption looks for the next step. Sum: 100. */
    public const WEIGHTS = [
        'password' => 25,
        'two_factor' => 30,
        'recovery_codes' => 15,
        'passkey' => 15,
        'login_approval' => 10,
        'login_alerts' => 5,
    ];

    /** @var array<string, array{label: string, desc: string, button: string, target: string, caption: string}> */
    private const COPY = [
        'password' => ['label' => 'Set a password', 'desc' => 'Sign in with your email and a password.', 'button' => 'Set', 'target' => '#sx-password', 'caption' => 'Set a password so you can always sign in.'],
        'two_factor' => ['label' => 'Turn on two-factor', 'desc' => 'A code from your phone at each sign-in.', 'button' => 'Enable', 'target' => '#sx-two-factor', 'caption' => 'Turn on two-factor to strengthen your account.'],
        'recovery_codes' => ['label' => 'Save recovery codes', 'desc' => 'Back-up codes for a lost phone.', 'button' => 'Enable', 'target' => '#sx-two-factor', 'caption' => 'Save your recovery codes so a lost phone can’t lock you out.'],
        'passkey' => ['label' => 'Add a passkey', 'desc' => 'Face or fingerprint instead of a password.', 'button' => 'Enable', 'target' => '#sx-passkeys', 'caption' => 'Add a passkey for a faster, safer sign-in.'],
        'login_approval' => ['label' => 'Approve new devices', 'desc' => 'A new device waits for your yes.', 'button' => 'Enable', 'target' => '#sx-approval', 'caption' => 'Approve new devices before they can sign in.'],
        'login_alerts' => ['label' => 'Login alerts', 'desc' => 'Hear about sign-ins from new devices.', 'button' => 'Enable', 'target' => '#sx-alerts', 'caption' => 'Turn on login alerts to hear about new sign-ins.'],
    ];

    /**
     * @param  array<string, array{done: bool, offered: bool}>  $state  one entry per key of WEIGHTS
     * @return array{num: int, tier: string, caption: string, on: int, total: int, available: int,
     *               protections: list<array{key: string, label: string, desc: string, button: string, action: string, target: string, done: bool, pts: int}>}
     */
    public static function from(array $state): array
    {
        $offered = [];
        foreach (self::WEIGHTS as $key => $weight) {
            if (($state[$key]['offered'] ?? false) === true) {
                $offered[$key] = $weight;
            }
        }

        $points = self::shares($offered);
        $protections = [];
        $num = 0;
        $on = 0;
        $next = null;

        foreach ($offered as $key => $weight) {
            $done = ($state[$key]['done'] ?? false) === true;
            $copy = self::COPY[$key];
            $protections[] = [
                'key' => $key,
                'label' => $copy['label'],
                'desc' => $key === 'recovery_codes' && ! ($state['two_factor']['done'] ?? false)
                    ? 'They come with two-factor — turn it on first.'
                    : $copy['desc'],
                'button' => $copy['button'],
                'action' => $copy['label'],
                'target' => $copy['target'],
                'done' => $done,
                'pts' => $points[$key],
            ];
            if ($done) {
                $num += $points[$key];
                $on++;
            } elseif ($next === null) {
                $next = $key;
            }
        }

        return [
            'num' => $num,
            'tier' => $num >= 85 ? 'Strong' : ($num >= 55 ? 'Good' : 'Basic'),
            'caption' => $next === null ? 'All protections are on — your account is fully secured.' : self::COPY[$next]['caption'],
            'on' => $on,
            'total' => count($offered),
            'available' => 100 - $num,
            'protections' => $protections,
        ];
    }

    /**
     * Each offered weight's share of 100, rounded by largest remainder so the shares sum to exactly 100.
     *
     * @param  array<string, int>  $weights
     * @return array<string, int>
     */
    private static function shares(array $weights): array
    {
        $total = array_sum($weights);
        if ($total === 0) {
            return [];
        }

        $floor = [];
        $rest = [];
        foreach ($weights as $key => $weight) {
            $exact = $weight * 100 / $total;
            $floor[$key] = (int) floor($exact);
            $rest[$key] = $exact - $floor[$key];
        }

        $left = 100 - array_sum($floor);
        $order = array_keys($rest);
        // largest remainder first; equal remainders keep the WEIGHTS order (a stable sort on the remainder only)
        usort($order, fn (string $a, string $b): int => $rest[$b] <=> $rest[$a] ?: array_search($a, array_keys($weights), true) <=> array_search($b, array_keys($weights), true));
        foreach (array_slice($order, 0, $left) as $key) {
            $floor[$key]++;
        }

        return $floor;
    }
}
