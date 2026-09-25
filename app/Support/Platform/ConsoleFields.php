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
        'features.*.enabled' => 'feature switch',
        'features.*.limit' => 'feature limit',
    ];
}
