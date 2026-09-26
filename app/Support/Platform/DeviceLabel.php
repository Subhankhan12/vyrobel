<?php

declare(strict_types=1);

namespace App\Support\Platform;

/**
 * ADMIN 04 (2026-09-26) — a stored user-agent string, in words: "Chrome on macOS", "Safari on iPhone".
 *
 * ⚠️ A LABEL, NOT A FINGERPRINT. It names the browser family and the operating system and nothing else — no version,
 * no model. An agent it does not recognise is shown as the agent's own first characters rather than guessed at, so
 * a script or a probe reads as what it is. The ORDER of the checks is the whole trick: Edge and Opera also say
 * "Chrome", Chrome also says "Safari", and an iPhone also says "Mac OS X".
 */
final class DeviceLabel
{
    public static function from(?string $userAgent): string
    {
        $ua = trim((string) $userAgent);
        if ($ua === '') {
            return 'Unknown device';
        }

        $browser = match (true) {
            str_contains($ua, 'Edg/') || str_contains($ua, 'EdgiOS/') => 'Edge',
            str_contains($ua, 'OPR/') || str_contains($ua, 'Opera') => 'Opera',
            str_contains($ua, 'Firefox/') || str_contains($ua, 'FxiOS/') => 'Firefox',
            str_contains($ua, 'Chrome/') || str_contains($ua, 'CriOS/') => 'Chrome',
            str_contains($ua, 'Safari/') => 'Safari',
            default => null,
        };

        $system = match (true) {
            str_contains($ua, 'iPhone') => 'iPhone',
            str_contains($ua, 'iPad') => 'iPad',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'CrOS') => 'ChromeOS',
            str_contains($ua, 'Macintosh') || str_contains($ua, 'Mac OS X') => 'macOS',
            str_contains($ua, 'Linux') => 'Linux',
            default => null,
        };

        return match (true) {
            $browser !== null && $system !== null => $browser.' on '.$system,
            $browser !== null => $browser,
            $system !== null => $system,
            default => mb_strimwidth($ua, 0, 40, '…'),
        };
    }
}
