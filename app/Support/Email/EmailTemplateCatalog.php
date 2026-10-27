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
     * ⚠️ I18N-01 — the DEFAULT texts are translation keys, translated at CALL time (never in a constant), so an
     * un-customized email reads in the recipient's language. `{merge}` chips stay literal inside every value.
     * An organisation's edited text is untouched: {@see ResolvedEmailTemplate} still prefers the stored column.
     *
     * @return array<string, array{
     *   label: string, trigger: string, channel: string, preview: string,
     *   subject: string, heading: string, message: string, button: string,
     *   vars: list<string>, blocks: array<string, array{label: string, default: bool}>
     * }>
     */
    public static function all(): array
    {
        return [
            'booking-confirmation' => [
                'label' => (string) __('php/support/email/email_template_catalog.booking_confirmation_label'),
                'trigger' => (string) __('php/support/email/email_template_catalog.booking_confirmation_trigger'),
                'channel' => (string) __('php/support/email/email_template_catalog.channel_email'),
                'preview' => 'booking-confirmation',
                // Subject byte-identical to the shipped envelope so an un-customized send is unchanged.
                'subject' => (string) __('php/support/email/email_template_catalog.booking_confirmation_subject'),
                'heading' => (string) __('php/support/email/email_template_catalog.booking_confirmation_heading'),
                'message' => (string) __('php/support/email/email_template_catalog.booking_confirmation_message'),
                'button' => (string) __('php/support/email/email_template_catalog.booking_confirmation_button'),
                'vars' => ['guest_name', 'first_name', 'unit', 'nights', 'reference'],
                'blocks' => [
                    'price_breakdown' => ['label' => (string) __('php/support/email/email_template_catalog.booking_confirmation_block_price_breakdown'), 'default' => true],
                    'before_you_arrive' => ['label' => (string) __('php/support/email/email_template_catalog.booking_confirmation_block_before_you_arrive'), 'default' => true],
                    'calendar_links' => ['label' => (string) __('php/support/email/email_template_catalog.booking_confirmation_block_calendar_links'), 'default' => true],
                ],
            ],
            'cancellation' => [
                'label' => (string) __('php/support/email/email_template_catalog.cancellation_label'),
                'trigger' => (string) __('php/support/email/email_template_catalog.cancellation_trigger'),
                'channel' => (string) __('php/support/email/email_template_catalog.channel_email'),
                'preview' => 'cancellation',
                'subject' => (string) __('php/support/email/email_template_catalog.cancellation_subject'),
                'heading' => (string) __('php/support/email/email_template_catalog.cancellation_heading'),
                'message' => (string) __('php/support/email/email_template_catalog.cancellation_message'),
                'button' => (string) __('php/support/email/email_template_catalog.cancellation_button'),
                'vars' => ['first_name', 'unit', 'reference'],
                'blocks' => [
                    'refund_summary' => ['label' => (string) __('php/support/email/email_template_catalog.cancellation_block_refund_summary'), 'default' => true],
                    'return_offer' => ['label' => (string) __('php/support/email/email_template_catalog.cancellation_block_return_offer'), 'default' => true],
                ],
            ],
            'pre-arrival' => [
                'label' => (string) __('php/support/email/email_template_catalog.pre_arrival_label'),
                'trigger' => (string) __('php/support/email/email_template_catalog.pre_arrival_trigger'),
                'channel' => (string) __('php/support/email/email_template_catalog.channel_email'),
                'preview' => 'pre-arrival',
                'subject' => (string) __('php/support/email/email_template_catalog.pre_arrival_subject'),
                'heading' => (string) __('php/support/email/email_template_catalog.pre_arrival_heading'),
                'message' => (string) __('php/support/email/email_template_catalog.pre_arrival_message'),
                'button' => (string) __('php/support/email/email_template_catalog.pre_arrival_button'),
                'vars' => ['first_name', 'unit', 'nights', 'reference', 'balance'],
                'blocks' => [
                    'door_code' => ['label' => (string) __('php/support/email/email_template_catalog.pre_arrival_block_door_code'), 'default' => true],
                    'directions' => ['label' => (string) __('php/support/email/email_template_catalog.pre_arrival_block_directions'), 'default' => true],
                    'wifi' => ['label' => (string) __('php/support/email/email_template_catalog.pre_arrival_block_wifi'), 'default' => true],
                    'checkin' => ['label' => (string) __('php/support/email/email_template_catalog.pre_arrival_block_checkin'), 'default' => true],
                    'balance' => ['label' => (string) __('php/support/email/email_template_catalog.pre_arrival_block_balance'), 'default' => true],
                ],
            ],
            'payment-reminder' => [
                'label' => (string) __('php/support/email/email_template_catalog.payment_reminder_label'),
                'trigger' => (string) __('php/support/email/email_template_catalog.payment_reminder_trigger'),
                'channel' => (string) __('php/support/email/email_template_catalog.channel_email'),
                'preview' => 'payment-reminder',
                'subject' => (string) __('php/support/email/email_template_catalog.payment_reminder_subject'),
                'heading' => (string) __('php/support/email/email_template_catalog.payment_reminder_heading'),
                'message' => (string) __('php/support/email/email_template_catalog.payment_reminder_message'),
                'button' => (string) __('php/support/email/email_template_catalog.payment_reminder_button'),
                'vars' => ['first_name', 'unit', 'reference', 'balance'],
                'blocks' => [
                    'totals' => ['label' => (string) __('php/support/email/email_template_catalog.payment_reminder_block_totals'), 'default' => true],
                    'booking_detail' => ['label' => (string) __('php/support/email/email_template_catalog.payment_reminder_block_booking_detail'), 'default' => true],
                ],
            ],
            'refund-confirmation' => [
                'label' => (string) __('php/support/email/email_template_catalog.refund_confirmation_label'),
                'trigger' => (string) __('php/support/email/email_template_catalog.refund_confirmation_trigger'),
                'channel' => (string) __('php/support/email/email_template_catalog.channel_email'),
                'preview' => 'refund-confirmation',
                'subject' => (string) __('php/support/email/email_template_catalog.refund_confirmation_subject'),
                'heading' => (string) __('php/support/email/email_template_catalog.refund_confirmation_heading'),
                'message' => (string) __('php/support/email/email_template_catalog.refund_confirmation_message'),
                'button' => (string) __('php/support/email/email_template_catalog.refund_confirmation_button'),
                'vars' => ['first_name', 'reference'],
                'blocks' => [
                    'status_timeline' => ['label' => (string) __('php/support/email/email_template_catalog.refund_confirmation_block_status_timeline'), 'default' => true],
                    'detail_rows' => ['label' => (string) __('php/support/email/email_template_catalog.refund_confirmation_block_detail_rows'), 'default' => true],
                ],
            ],
            'review-request' => [
                'label' => (string) __('php/support/email/email_template_catalog.review_request_label'),
                'trigger' => (string) __('php/support/email/email_template_catalog.review_request_trigger'),
                'channel' => (string) __('php/support/email/email_template_catalog.channel_email'),
                'preview' => 'review-request',
                'subject' => (string) __('php/support/email/email_template_catalog.review_request_subject'),
                'heading' => (string) __('php/support/email/email_template_catalog.review_request_heading'),
                'message' => (string) __('php/support/email/email_template_catalog.review_request_message'),
                'button' => (string) __('php/support/email/email_template_catalog.review_request_button'),
                'vars' => ['first_name', 'unit', 'nights', 'reference'],
                'blocks' => [
                    'tap_a_star' => ['label' => (string) __('php/support/email/email_template_catalog.review_request_block_tap_a_star'), 'default' => true],
                    'stay_recap' => ['label' => (string) __('php/support/email/email_template_catalog.review_request_block_stay_recap'), 'default' => true],
                    'signature' => ['label' => (string) __('php/support/email/email_template_catalog.review_request_block_signature'), 'default' => true],
                ],
            ],
            'verify-email' => [
                'label' => (string) __('php/support/email/email_template_catalog.verify_email_label'),
                'trigger' => (string) __('php/support/email/email_template_catalog.verify_email_trigger'),
                'channel' => (string) __('php/support/email/email_template_catalog.channel_email'),
                'preview' => 'verify-email',
                'subject' => (string) __('php/support/email/email_template_catalog.verify_email_subject'),
                'heading' => (string) __('php/support/email/email_template_catalog.verify_email_heading'),
                'message' => (string) __('php/support/email/email_template_catalog.verify_email_message'),
                'button' => (string) __('php/support/email/email_template_catalog.verify_email_button'),
                'vars' => ['first_name'],
                'blocks' => [
                    'copy_link' => ['label' => (string) __('php/support/email/email_template_catalog.verify_email_block_copy_link'), 'default' => true],
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
