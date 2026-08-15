<?php

declare(strict_types=1);

namespace App\Support\Auth;

use App\Providers\AppServiceProvider;
use Illuminate\Validation\Rules\Password;

/**
 * THE password composition policy — the rule AND the sentence that describes it, in ONE place (DO13-4).
 *
 * ⚠️ WHY BOTH HALVES LIVE HERE. The defect this closes was not only that the rule was weak; it was that NINE
 * controllers each carried their own copy of it and EIGHT screens each carried their own copy of the sentence
 * about it — so the code said `min(8)` while the Guest Signup screen promised "a number and symbol", and
 * nothing structurally stopped them disagreeing. Splitting the rule from its description would leave exactly
 * that gap open: a future policy change would tighten the rule and leave eight screens quietly lying.
 * `rule()` and `hint()` are defined together so they cannot drift apart (§N.8f).
 *
 * ⚠️ THE COMPOSITION MATCHES THE STRONGEST PROMISE THE PRODUCT MAKES. The prototypes do not agree with each
 * other — "At least 8 characters" (Set Password) · "at least 8 characters with a number." (Owner Security) ·
 * "8+ characters with a number and symbol" (Guest Signup) — so there was no single promise to read off. A
 * per-realm password policy would be a security smell, so the strongest becomes the floor for every realm and
 * the weaker screens' copy was corrected to state what is actually enforced.
 *
 * ⚠️ NOT INCLUDED, deliberately:
 *   • `mixedCase()` — no screen promises it. Enforcing an unstated rule is the same defect reversed.
 *   • `uncompromised()` (Have-I-Been-Pwned) — a founder call, not a code call: an outbound API dependency on
 *     every password set, and it rejects passwords users consider fine. Recorded in DECISIONS, not bundled.
 *
 * ⚠️ `CreatePlatformAdminCommand` deliberately does NOT use this: it requires min(12) + mixedCase + numbers +
 * symbols. Pointing it here would WEAKEN it. A higher bar for the platform super-admin is a real difference,
 * not drift — confirmed before collapsing, the same judgement the payout-status maps needed.
 */
final class PasswordPolicy
{
    /** The minimum length every realm enforces. */
    public const MIN_LENGTH = 8;

    /**
     * The composition rule. Registered as `Password::defaults()` in {@see AppServiceProvider},
     * so the nine set-password controllers ask for `Password::defaults()` and a policy change is ONE edit.
     */
    public static function rule(): Password
    {
        return Password::min(self::MIN_LENGTH)->letters()->numbers()->symbols();
    }

    /**
     * The user-facing sentence, stated on every set-password screen.
     *
     * ⚠️ It must describe {@see rule()} exactly. A rule the UI does not state produces rejections the user
     * cannot act on — which is how this defect read from the outside before it was a security issue at all.
     */
    public static function hint(): string
    {
        return 'At least '.self::MIN_LENGTH.' characters, including a number and a symbol.';
    }
}
