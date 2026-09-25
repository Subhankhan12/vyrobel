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
    public const AGREEMENT_NONE = 'None on file';

    /**
     * @return list<array{name: string, purpose: string, data: string, active: bool, state: string, agreement: string}>
     */
    public static function register(): array
    {
        $social = SocialProviders::enabled();
        $mail = MailDelivery::mailer();
        $stripeFake = (bool) config('services.stripe.fake', true);
        $llmOff = (bool) config('services.llm.fake', true) || blank(config('services.llm.api_key'));
        $channexOff = (bool) config('services.channex.fake', true) || blank(config('services.channex.api_key'));

        return [
            self::row('Hostinger (hosting)', 'Runs the application and its database.', 'Everything the product stores — every account, hotel and guest record.', true, 'Active — the production host'),
            self::row('Hostinger CDN', 'Serves and caches the public site in front of the host.', 'Every request passing through it: IP addresses, URLs, cookies in transit.', true, 'Active — observed on every response'),
            self::row('Google (sign-in)', 'Sign in and sign up with a Google account.', 'The person’s Google identity: account id, email, name.', isset($social['google']), isset($social['google']) ? 'Active — switched on' : 'Not active'),
            self::row('X (sign-in)', 'Sign in and sign up with an X account.', 'The person’s X identity: account id, handle, name.', isset($social['twitter']), isset($social['twitter']) ? 'Active — switched on' : 'Not active'),
            // MAIL 02 (2026-09-25) — the founder chose Hostinger's own mail over Postmark, so email goes to the SAME company
            //    as hosting, through a separate service; Postmark receives nothing and is no longer listed.
            self::row('Hostinger (email)', 'Delivers every transactional email, from the vyrobel.com mailbox.', 'Recipient addresses and message contents — guest names, booking references, dates.', $mail === 'smtp', $mail === 'smtp' ? 'Active — the mailer' : 'Not active — mail is “'.$mail.'”'),
            self::row('Stripe (payments & billing)', 'Subscription billing, guest card payments, owner payouts (Connect).', 'Billing contacts, amounts, booking references; for owner payouts, identity for KYC.', ! $stripeFake, $stripeFake ? 'Not active — a fake client is bound' : 'Configured'),
            self::row('Anthropic (AI assistant)', 'Answers questions in the AI assistant.', 'The question asked and the context sent with it.', ! $llmOff, $llmOff ? 'Not active — fake client or no key' : 'Configured'),
            self::row('Channex (channel manager)', 'Syncs rates, availability and bookings with OTAs.', 'Booking and guest details exchanged with channels.', ! $channexOff, $channexOff ? 'Not connected' : 'Configured'),
        ];
    }

    /** @return array{name: string, purpose: string, data: string, active: bool, state: string, agreement: string} */
    private static function row(string $name, string $purpose, string $data, bool $active, string $state): array
    {
        return ['name' => $name, 'purpose' => $purpose, 'data' => $data, 'active' => $active, 'state' => $state, 'agreement' => self::AGREEMENT_NONE];
    }
}
