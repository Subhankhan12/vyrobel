<?php

declare(strict_types=1);

namespace App\Support\Platform;

/**
 * ⚠️ ADMIN 03 — the words a console validation message uses for a field, ONCE. Laravel names a field by its input name,
 * so the console said "The organization id field is required" (the internal name, in American spelling),
 * "The subject kind field…", "The starts at field…" — none of which is the label the admin was looking at. Every
 * `validate()` in the console passes this map (ConsoleFieldNamesAreUsedTest fails if one does not).
 *
 * Only names whose default reads wrongly are here. ⚠️ `code` is deliberately absent: it is the PLAN code on the plan
 * form and the 2FA code on sign-in and break-glass — one word cannot be both, and "code" reads right in each.
 */
final class ConsoleFields
{
    public const NAMES = [
        'current_password' => 'your password',
        'organization_id' => 'organisation',
        'organization_ids' => 'chosen organisations',
        'organization_ids.*' => 'organisation',
        'plan_id' => 'plan',
        // DEC-7 — extend a trial, grant a complimentary plan
        'days' => 'days',
        'until' => 'end date',
        // Announcements
        'body' => 'message',
        'starts_at' => 'show from',
        'ends_at' => 'until',
        // Privacy desk
        'subject_kind' => 'about',
        'channel' => 'received by',
        // Integrations and plans
        'api_key' => 'API key',
        'api_secret' => 'API secret',
        'is_enabled' => 'enabled',
        'is_active' => 'active',
        'pricing_type' => 'pricing',
        'vat_pct' => 'VAT %',
        'stripe_price_id' => 'Stripe price ID',
        // ADMIN 04 — update and create an organisation
        'country' => 'country',
        'default_locale' => 'language',
        'billing_email' => 'billing email',
        'billing_company' => 'billing name',
        'billing_vat_id' => 'VAT ID',
        'billing_city' => 'billing city',
        'org_name' => 'organisation name',
        'currency' => 'currency',
        'property_name' => 'first property',
        'property_type' => 'property type',
        'location' => 'location',
        'founder_name' => "founder's name",
        'founder_email' => "founder's email",
        // ADMIN 05 — delete permanently
        'acknowledge_delete' => 'the acknowledgement',
        'features.*.enabled' => 'feature switch',
        'features.*.limit' => 'feature limit',
    ];

    /**
     * ⚠️ ADMIN 03B — a conditional rule's default message prints the RAW VALUE of the other field: live, the privacy
     * desk said "The organisation field is required when about is relayed_guest". Every `required_if` in the console
     * gets a sentence here instead (ConsoleFieldNamesAreUsedTest fails if one does not).
     */
    public const MESSAGES = [
        'email.required_if' => 'An account holder’s request needs their email address — it finds the account and is not stored.',
        'organization_id.required_if' => 'A guest’s request needs the organisation that relayed it.',
        'organization_ids.required_if' => 'Choose at least one organisation — or send it to every organisation.',
        'price.required_if' => 'A flat-priced plan needs a price.',
        'unit_rate.required_if' => 'A per-unit plan needs a rate per unit.',
    ];

    /**
     * I18N-01 — {@see NAMES} in the request's language. The constant stays the English record of WHICH fields are
     * named (and the fallback); this translates each by its field code at render time (a constant cannot call __()).
     *
     * @return array<string, string>
     */
    public static function names(): array
    {
        $out = [];
        foreach (self::NAMES as $field => $english) {
            $out[$field] = self::name($field) ?? $english;
        }

        return $out;
    }

    /**
     * I18N-01 — {@see MESSAGES} in the request's language, keyed by the same rule codes.
     *
     * @return array<string, string>
     */
    public static function messages(): array
    {
        $out = [];
        foreach (self::MESSAGES as $rule => $english) {
            $out[$rule] = self::message($rule) ?? $english;
        }

        return $out;
    }

    /** One conditional rule's sentence in the request's language — null for a rule this class does not word. */
    public static function message(string $rule): ?string
    {
        return match ($rule) {
            'email.required_if' => __('php/support/platform/console_fields.email_required_if'),
            'organization_id.required_if' => __('php/support/platform/console_fields.organization_id_required_if'),
            'organization_ids.required_if' => __('php/support/platform/console_fields.organization_ids_required_if'),
            'price.required_if' => __('php/support/platform/console_fields.price_required_if'),
            'unit_rate.required_if' => __('php/support/platform/console_fields.unit_rate_required_if'),
            default => null,
        };
    }

    /** One field's word in the request's language — null for a field this class does not name. */
    public static function name(string $field): ?string
    {
        return match ($field) {
            'current_password' => __('php/support/platform/console_fields.current_password'),
            'organization_id' => __('php/support/platform/console_fields.organization_id'),
            'organization_ids' => __('php/support/platform/console_fields.organization_ids'),
            'organization_ids.*' => __('php/support/platform/console_fields.organization_ids_each'),
            'plan_id' => __('php/support/platform/console_fields.plan_id'),
            'days' => __('php/support/platform/console_fields.days'),
            'until' => __('php/support/platform/console_fields.until'),
            'body' => __('php/support/platform/console_fields.body'),
            'starts_at' => __('php/support/platform/console_fields.starts_at'),
            'ends_at' => __('php/support/platform/console_fields.ends_at'),
            'subject_kind' => __('php/support/platform/console_fields.subject_kind'),
            'channel' => __('php/support/platform/console_fields.channel'),
            'api_key' => __('php/support/platform/console_fields.api_key'),
            'api_secret' => __('php/support/platform/console_fields.api_secret'),
            'is_enabled' => __('php/support/platform/console_fields.is_enabled'),
            'is_active' => __('php/support/platform/console_fields.is_active'),
            'pricing_type' => __('php/support/platform/console_fields.pricing_type'),
            'vat_pct' => __('php/support/platform/console_fields.vat_pct'),
            'stripe_price_id' => __('php/support/platform/console_fields.stripe_price_id'),
            'country' => __('php/support/platform/console_fields.country'),
            'default_locale' => __('php/support/platform/console_fields.default_locale'),
            'billing_email' => __('php/support/platform/console_fields.billing_email'),
            'billing_company' => __('php/support/platform/console_fields.billing_company'),
            'billing_vat_id' => __('php/support/platform/console_fields.billing_vat_id'),
            'billing_city' => __('php/support/platform/console_fields.billing_city'),
            'org_name' => __('php/support/platform/console_fields.org_name'),
            'currency' => __('php/support/platform/console_fields.currency'),
            'property_name' => __('php/support/platform/console_fields.property_name'),
            'property_type' => __('php/support/platform/console_fields.property_type'),
            'location' => __('php/support/platform/console_fields.location'),
            'founder_name' => __('php/support/platform/console_fields.founder_name'),
            'founder_email' => __('php/support/platform/console_fields.founder_email'),
            'acknowledge_delete' => __('php/support/platform/console_fields.acknowledge_delete'),
            'features.*.enabled' => __('php/support/platform/console_fields.feature_enabled'),
            'features.*.limit' => __('php/support/platform/console_fields.feature_limit'),
            default => null,
        };
    }
}
