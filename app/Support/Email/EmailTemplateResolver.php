<?php

declare(strict_types=1);

namespace App\Support\Email;

use App\Models\EmailTemplate;
use App\Models\Scopes\OrganizationScope;

/**
 * Resolves the effective customization of a guest email for an org (Operator Email Templates exact-match #86).
 * The single read the #83 Mailables (and the editor's live preview) use — the store override merged over the
 * catalog default, so editing genuinely changes the real email. Fail-open: no org / no row → the code default.
 */
final class EmailTemplateResolver
{
    /**
     * @param  array<string, scalar>  $vars  merge values (first_name, unit, nights, reference, …)
     */
    public static function for(?string $organizationId, string $key, array $vars = []): ResolvedEmailTemplate
    {
        return new ResolvedEmailTemplate(EmailTemplateCatalog::get($key), self::row($organizationId, $key), $vars);
    }

    /** Whether the email is enabled for this org — a missing row = ON (the default). OFF stops the send. */
    public static function enabled(?string $organizationId, string $key): bool
    {
        $row = self::row($organizationId, $key);

        return $row === null ? true : (bool) $row->enabled;
    }

    private static function row(?string $organizationId, string $key): ?EmailTemplate
    {
        if ($organizationId === null) {
            return null;
        }

        // Read OUTSIDE the tenant scope (a Mailable renders when queued, with no tenant context) — the
        // explicit organization_id predicate is the boundary, so this never leaks another org's template.
        return EmailTemplate::query()
            ->withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $organizationId)
            ->where('template_key', $key)
            ->first();
    }
}
