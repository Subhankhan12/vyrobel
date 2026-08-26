<?php

declare(strict_types=1);

namespace App\Support\Log;

use Throwable;

/**
 * ⚠️ The ONE place an exception message is made safe to log (residency trace 2026-08-26 · §N.8f).
 *
 * The codebase already holds the rule — every deliberate log context passes IDs and class names, and two
 * sites carry the comment "never the address (no PII in logs)". The rule was true of the context ARRAY and
 * silently untrue of the one key beside it: `'error' => $e->getMessage()`.
 *
 * ⚠️ TWO CONFIRMED WAYS AN ADDRESS ENTERS A MESSAGE NOBODY WROTE:
 *   ① `QueryException` interpolates the BINDINGS into its own message —
 *      `Illuminate\Database\QueryException::formatMessage()` builds it with
 *      `Str::replaceArray('?', $bindings, $sql)` (verified in vendor, Laravel 13). A failing INSERT whose
 *      bindings include a login identifier therefore reports that identifier, in the clear, with no
 *      logging statement naming it.
 *   ② A mail transport's message is written by the PROVIDER and commonly quotes the rejected recipient
 *      ("invalid To address: …"). Nothing in this codebase controls that text.
 *
 * ⚠️ DELIBERATELY NARROW — this redacts the EMAIL ADDRESS and nothing else. The SQLSTATE, the provider's
 * error code, the class and the rest of the sentence all survive, because a log stripped of everything
 * useful is its own failure: the address is the one token with no diagnostic value that the surrounding
 * context (reservation id, organisation id) does not already carry better.
 *
 * ⚠️ NOT A GUARANTEE, AND NOT SOLD AS ONE. An exception message is free-form text from a third party. This
 * removes the identifier that is both the most likely to appear and the most identifying; it cannot prove a
 * provider never embeds something else. An IP or user-agent bound into the same statement still survives —
 * recorded as a known limit rather than fixed, because broad redaction on a hunch is the failure this rule
 * exists to prevent.
 */
final class LogSafe
{
    /**
     * Email addresses, unicode-aware. Kept intentionally simple: it must over-match a little rather than
     * miss a real address, and it can only ever affect text on its way into a log file.
     */
    private const EMAIL = '/[\p{L}\p{N}._%+\-]+@[\p{L}\p{N}.\-]+\.[\p{L}]{2,}/u';

    public const PLACEHOLDER = '[email redacted]';

    /** The message of $e with any email address replaced — safe to put in a log context. */
    public static function message(Throwable $e): string
    {
        return self::scrub($e->getMessage());
    }

    /** The same scrub for text that is already a string. */
    public static function scrub(string $text): string
    {
        return (string) preg_replace(self::EMAIL, self::PLACEHOLDER, $text);
    }
}
