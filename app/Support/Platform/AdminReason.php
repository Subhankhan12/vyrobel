<?php

declare(strict_types=1);

namespace App\Support\Platform;

use Illuminate\Http\Request;

/**
 * ADMIN 01 · PHASE 1 — THE ONE DEFINITION of a valid written reason for an admin write.
 *
 * Every console action that changes something (a tenant, a plan, a switch, an admin, an integration) validates
 * its reason through here, so "every admin write carries a reason" is one rule rather than a dozen copies that
 * can drift. The reason is stored on the append-only audit row.
 *
 * ⚠️ It is the admin's own short explanation — deliberately bounded free text. The form tells them not to put
 *    a password, a guest's details, a card or an IBAN in it; the length cap keeps it a sentence, not a dump.
 * ⚠️ Self-service account actions (your own name, password, email, recovery codes) do NOT require one —
 *    they change only the actor's own identity, and asking "why are you changing your password?" protects
 *    nothing. That is the one deliberate exception, and it is tested.
 */
final class AdminReason
{
    public const MIN = 5;

    public const MAX = 500;

    /** @return array<string, list<string>> */
    public static function rules(): array
    {
        return ['reason' => ['required', 'string', 'min:'.self::MIN, 'max:'.self::MAX]];
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        return [
            'reason.required' => __('php/support/platform/admin_reason.reason_required'),
            'reason.min' => __('php/support/platform/admin_reason.reason_too_short'),
        ];
    }

    /** Validate and return the trimmed reason. */
    public static function from(Request $request): string
    {
        $request->validate(self::rules(), self::messages(), ConsoleFields::names());

        return trim((string) $request->string('reason'));
    }
}
