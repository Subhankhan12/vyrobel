<?php

declare(strict_types=1);

namespace App\Support\Email;

use App\Models\EmailTemplate;
use App\Models\Scopes\OrganizationScope;
use App\Support\I18n\Locales;

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

    /**
     * I18N-01 (PLAN 5.3) — the language this email goes out in. An organisation that has EDITED any of its words wrote
     * them in one language, so the whole email follows the organisation's default language (a guest never gets half one
     * language, half another); an email left as Vyrobel wrote it goes in the recipient's own language.
     */
    public static function localeFor(?string $organizationId, string $key, ?string $recipientLocale): string
    {
        $row = self::row($organizationId, $key);
        $edited = $row !== null && collect([$row->subject, $row->heading, $row->message, $row->button_label])
            ->contains(fn ($v): bool => is_string($v) && trim($v) !== '');

        return $edited ? Locales::resolve(Locales::organizationDefault($organizationId)) : Locales::resolve($recipientLocale);
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
