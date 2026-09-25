<?php

declare(strict_types=1);

namespace App\Support\Mail;

use App\Listeners\Mail\SuppressUndeliverableRecipients;

/**
 * ⚠️⚠️ MAIL 02 (2026-09-25) — THE ONE definition of "this address can never receive mail", read by
 * {@see SuppressUndeliverableRecipients} before every send.
 *
 * Why it exists: the moment mail goes live on Hostinger, every email aimed at a probe account bounces — and the probes
 * are real users on production (`…@vyrobel-probe.invalid`, 10 of them when measured). A bounce against the founder's
 * own Hostinger mailbox counts against HIS sending reputation, and a mailbox that bounces a lot gets throttled. So the
 * app never hands such an address to the provider at all.
 *
 * Two kinds of address, and nothing else:
 *   1. RESERVED NAMES (RFC 2606 / RFC 6761) — `.invalid`, `.test`, `.example`, `.localhost`, and example.com/.net/.org.
 *      By definition nobody can own them, so suppressing them can never stop a real person's mail.
 *   2. AN EXPLICIT LIST (`mail.suppress_addresses`, from `MAIL_SUPPRESS_ADDRESSES`) — addresses measured to have no
 *      mailbox behind them. Exact, case-insensitive; never a pattern.
 * ⚠️ It deliberately does NOT suppress "users of internal organisations": the disposable test organisations used to
 *    prove mail end to end are internal too, and they send to the founder's real test inbox.
 */
final class UndeliverableRecipients
{
    /** RFC 2606 §2 + RFC 6761 — top-level names reserved so that they can never resolve to a real mailbox. */
    public const RESERVED_TLDS = ['invalid', 'test', 'example', 'localhost'];

    /** RFC 2606 §3 — second-level names reserved for documentation. */
    public const RESERVED_DOMAINS = ['example.com', 'example.net', 'example.org'];

    /** Why an address can never receive mail, or null when nothing says it cannot. */
    public static function reason(string $address): ?string
    {
        $address = strtolower(trim($address));
        $at = strrpos($address, '@');
        $domain = $at === false ? '' : substr($address, $at + 1);

        if ($domain === '') {
            return 'no domain';
        }
        foreach (self::RESERVED_DOMAINS as $reserved) {
            if ($domain === $reserved || str_ends_with($domain, '.'.$reserved)) {
                return 'reserved domain '.$reserved;
            }
        }
        $dot = strrpos($domain, '.');
        $tld = $dot === false ? $domain : substr($domain, $dot + 1);
        if (in_array($tld, self::RESERVED_TLDS, true)) {
            return 'reserved top-level domain .'.$tld;
        }
        if (in_array($address, self::listed(), true)) {
            return 'on the suppression list';
        }

        return null;
    }

    /** @return list<string> */
    private static function listed(): array
    {
        return array_values(array_filter(array_map(
            static fn ($a): string => strtolower(trim((string) $a)),
            (array) config('mail.suppress_addresses', []),
        )));
    }
}
