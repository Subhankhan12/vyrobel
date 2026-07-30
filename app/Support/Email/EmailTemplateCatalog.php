<?php

declare(strict_types=1);

namespace App\Support\Email;

/**
 * The catalog of the 5 customizable guest emails (Operator Email Templates exact-match #86) — the code
 * DEFAULTS ({@see EmailTemplateResolver} overrides them from the per-org store) + each template's real
 * trigger, Mailable class, preview key, mergeable variables, and toggleable content blocks.
 *
 * ⚠️ The default subject/heading/message/button are the current #83 strings (with `{merge}` placeholders in
 * place of the code's inline interpolation) — so an un-customized template renders faithfully. Editing the
 * store genuinely changes the real email; a block toggled off is genuinely absent.
 */
final class EmailTemplateCatalog
{
    /**
     * @return array<string, array{
     *   label: string, trigger: string, channel: string, preview: string,
     *   subject: string, heading: string, message: string, button: string,
     *   vars: list<string>, blocks: array<string, array{label: string, default: bool}>
     * }>
     */
    public static function all(): array
    {
        return [
            'pre-arrival' => [
                'label' => 'Pre-arrival',
                'trigger' => 'Sends 48 hours before check-in',
                'channel' => 'Email',
                'preview' => 'pre-arrival',
                'subject' => 'Your stay is ready · {reference}',
                'heading' => 'Your stay is ready',
                'message' => 'Everything’s set for your arrival at {unit}. Here’s your self check-in — no need to stop by a desk. Save this email; you’ll want it on the day.',
                'button' => 'Pay {balance} now',
                'vars' => ['first_name', 'unit', 'nights', 'reference', 'balance'],
                'blocks' => [
                    'door_code' => ['label' => 'Door-code hero', 'default' => true],
                    'directions' => ['label' => 'Directions', 'default' => true],
                    'wifi' => ['label' => 'WiFi details', 'default' => true],
                    'checkin' => ['label' => 'Check-in & out times', 'default' => true],
                    'balance' => ['label' => 'Balance-due pay button', 'default' => true],
                ],
            ],
            'payment-reminder' => [
                'label' => 'Payment reminder',
                'trigger' => 'Sends 3 days before arrival when a balance is due',
                'channel' => 'Email',
                'preview' => 'payment-reminder',
                'subject' => 'A balance is due before your stay · {reference}',
                'heading' => 'Just one thing before you arrive',
                'message' => 'We’re looking forward to welcoming you to {unit}. There’s a small balance left on your booking — settling it now means you skip the desk and head straight in.',
                'button' => 'Pay {balance} now · skip the desk',
                'vars' => ['first_name', 'unit', 'reference', 'balance'],
                'blocks' => [
                    'totals' => ['label' => 'Total / Paid / Balance table', 'default' => true],
                    'booking_detail' => ['label' => 'Arriving / Unit / Reference', 'default' => true],
                ],
            ],
            'refund-confirmation' => [
                'label' => 'Refund confirmation',
                'trigger' => 'Sends when a refund is processed',
                'channel' => 'Email',
                'preview' => 'refund-confirmation',
                'subject' => 'Your refund has been processed · {reference}',
                'heading' => 'Your refund is on the way',
                'message' => 'Hi {first_name}, we’ve processed your refund for booking {reference}. It’s being returned to your original payment method.',
                'button' => 'View your reservation',
                'vars' => ['first_name', 'reference'],
                'blocks' => [
                    'status_timeline' => ['label' => 'Refund-status timeline', 'default' => true],
                    'detail_rows' => ['label' => 'Reason / Reference / ETA', 'default' => true],
                ],
            ],
            'review-request' => [
                'label' => 'Review request',
                'trigger' => 'Sends the morning after checkout',
                'channel' => 'Email',
                'preview' => 'review-request',
                'subject' => 'How was your stay? · {reference}',
                'heading' => 'Thanks for staying with us, {first_name}',
                'message' => 'It was a real pleasure hosting you at {unit}. A small team like ours lives and dies by word of mouth — if you have a minute, a quick rating would mean the world.',
                'button' => 'Leave a review · 1 minute',
                'vars' => ['first_name', 'unit', 'nights', 'reference'],
                'blocks' => [
                    'tap_a_star' => ['label' => 'Tap-a-star block', 'default' => true],
                    'stay_recap' => ['label' => 'Stay recap row', 'default' => true],
                    'signature' => ['label' => 'Team signature', 'default' => true],
                ],
            ],
            'verify-email' => [
                'label' => 'Verify email',
                'trigger' => 'Sends when a guest claims their account',
                'channel' => 'Email',
                'preview' => 'verify-email',
                'subject' => 'Confirm your email address',
                'heading' => 'Confirm your email, {first_name}',
                'message' => 'Tap below to verify your email and activate your account. It only takes a second.',
                'button' => 'Verify my email',
                'vars' => ['first_name'],
                'blocks' => [
                    'copy_link' => ['label' => 'Copy-paste link fallback', 'default' => true],
                ],
            ],
        ];
    }

    /** The catalog entry for one key, or null. */
    public static function get(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }

    /** @return list<string> the valid template keys */
    public static function keys(): array
    {
        return array_keys(self::all());
    }
}
