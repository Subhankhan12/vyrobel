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
}
