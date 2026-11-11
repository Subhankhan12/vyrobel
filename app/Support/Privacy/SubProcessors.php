<?php

declare(strict_types=1);

namespace App\Support\Privacy;

use App\Support\Auth\SocialProviders;
use App\Support\Platform\MailDelivery;

/**
 * ⚠️ ADMIN 01 · PHASE 4 — THE SUB-PROCESSOR REGISTER: every third party that receives, or will receive, personal
 * data because of Vyrobel — who, why, what is shared, whether it is active today, and whether an agreement exists.
 *
 * ⚠️ AGREEMENT STATUS IS "NONE ON FILE" FOR EVERY ONE. The data inventory (Domain 4) records that no sub-processor
 * agreement and no Article 28 DPA exists; the console must not imply otherwise. When one is signed, this changes in
 * code review like any other statement the product makes about itself.
 * ⚠️ "ACTIVE TODAY" IS READ FROM CONFIG — which mailer, whether a provider is switched on, whether a fake client is
 * bound — and reports SET / not set only, never a value.
 * ⚠️ It makes the lawyer's job shorter; it does not replace it.
 */
final class SubProcessors
{
    /** The lang group every sentence of the register is said from (I18N-01 — the console reads it in the founder's language). */
    private const LANG = 'php/support/privacy/sub_processors';

    /**
     * @return list<array{key: string, name: string, purpose: string, data: string, active: bool, state: string, agreement: string}>
     */
    public static function register(): array
    {
        $social = SocialProviders::enabled();
        $mail = MailDelivery::mailer();
        $stripeFake = (bool) config('services.stripe.fake', true);
        $llmOff = (bool) config('services.llm.fake', true) || blank(config('services.llm.api_key'));
        $channexOff = (bool) config('services.channex.fake', true) || blank(config('services.channex.api_key'));

        return [
            self::row('hostinger_hosting', true, self::say('hostinger_hosting.state')),
            self::row('hostinger_cdn', true, self::say('hostinger_cdn.state')),
            self::row('google', isset($social['google']), self::say(isset($social['google']) ? 'state_switched_on' : 'state_not_active')),
            self::row('x', isset($social['twitter']), self::say(isset($social['twitter']) ? 'state_switched_on' : 'state_not_active')),
            // MAIL 02 (2026-09-25) — the founder chose Hostinger's own mail over Postmark, so email goes to the SAME company
            //    as hosting, through a separate service; Postmark receives nothing and is no longer listed.
            self::row('hostinger_email', $mail === 'smtp', $mail === 'smtp' ? self::say('hostinger_email.state_active') : self::say('hostinger_email.state_inactive', ['mailer' => $mail])),
            self::row('stripe', ! $stripeFake, self::say($stripeFake ? 'stripe.state_fake' : 'state_configured')),
            self::row('anthropic', ! $llmOff, self::say($llmOff ? 'anthropic.state_off' : 'state_configured')),
            self::row('channex', ! $channexOff, self::say($channexOff ? 'channex.state_off' : 'state_configured')),
        ];
    }

    /**
     * One register row. `key` is the row's stable identity (a page that picks a row picks it by THIS, never by its name, which is said in the
     * reader's language); the name, purpose and what they get are read from the language files by that key.
     *
     * @return array{key: string, name: string, purpose: string, data: string, active: bool, state: string, agreement: string}
     */
    private static function row(string $key, bool $active, string $state): array
    {
        return [
            'key' => $key,
            'name' => self::say($key.'.name'),
            'purpose' => self::say($key.'.purpose'),
            'data' => self::say($key.'.data'),
            'active' => $active,
            'state' => $state,
            'agreement' => self::say('agreement_none'),
        ];
    }

    /** @param  array<string, string>  $replace */
    private static function say(string $key, array $replace = []): string
    {
        return (string) __(self::LANG.'.'.$key, $replace);
    }
}
