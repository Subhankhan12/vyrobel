<?php

declare(strict_types=1);

namespace App\Support\Marketing;

use RuntimeException;

/**
 * THE ONE MARKETING CONTENT SOURCE — and the honesty guard over it (front-website Gate 0, ruling D1).
 *
 * ⚠️ WHY THIS EXISTS. The marketing design ships with an honesty ledger the designer wrote into the data
 * itself: `"status": "ILLUSTRATIVE — replace before launch"`, `"real": null` on every figure, and
 * `"consent": false` on every named customer and testimonial author. The founder ruled (D1) to KEEP that
 * illustrative content during the build and replace it with real, consented content before launch — and to
 * ENFORCE the replacement by CI, because a note in a JSON file has no failure mode (the same silent-failure
 * family as `MAIL_MAILER=log` and an unregistered Stripe webhook: nothing errors, the harm just ships).
 *
 * ⚠️ THE RULE, IN ONE SENTENCE: an entry that is not real (`real` must be exactly `true` or an explicit
 * JSON number) or not consented (`consent` !== true where consent applies) must never be published in a
 * production-like context — enforced at BUILD time by tests/Feature/MarketingContentHonestyTest.php +
 * CI Gate 5 (the ledger cannot be declared production-ready while such entries exist), and at RUN time by
 * {@see self::figures()} / {@see self::customers()} / {@see self::testimonials()}, which FILTER those
 * entries out everywhere except an explicit dev environment (an honest empty section, never a fabricated
 * one — the #39 pattern).
 *
 * ⚠️ ONE SOURCE (§N.8f). Every marketing page (Gate 1+) MUST render figures/customers/testimonials through
 * this class from `resources/marketing/content.json` — never a second copy, never a literal typed into a
 * Blade page. The data was carried over from the design byte-identical, flags intact; nothing was invented
 * or "fixed".
 *
 * ⚠️ THE HONEST LIMIT OF THIS GUARD: it protects the FLAGGED content in the one tracked ledger. It cannot
 * know about a fabrication a future page introduces OUTSIDE the ledger (a number typed straight into Blade
 * carries no flags to check). That discipline — pages render marketing claims only through this source — is
 * a Gate 1+ review rule, stated here so the gate's scope is never overclaimed.
 *
 * ⚠️ FAIL-CLOSED AS AN ALLOW-LIST (§N.18.4 — hardened after adversarial review caught the first version's
 * reject-list letting `real: "no"` / `""` / `"TBD"` through both layers): `real` is accepted ONLY as
 * exactly `true` or an explicit int/float (the true value itself); every other shape — null, false, a
 * string of any kind, an array, absence — is refused. A missing `status` counts as production-declared
 * (strict); an illustrative declaration counts ONLY when the status BEGINS with the word "illustrative"
 * (a status that merely mentions it — "Production ready, illustrative content replaced" — is
 * production-declared). The silent error here is shipping fabricated people, so every ambiguity resolves
 * toward "do not publish".
 */
final class MarketingContent
{
    /** The environments allowed to render the illustrative content UNFILTERED (D1: kept for the build). */
    private const UNFILTERED_ENVIRONMENTS = ['local', 'development', 'testing'];

    /** The only top-level keys this guard knows. An UNKNOWN section in the ledger fails the honesty gate. */
    public const SECTIONS = ['updated', 'status', 'figures', 'customers', 'testimonials'];

    /** The one tracked ledger the marketing pages render from. */
    public static function path(): string
    {
        return resource_path('marketing/content.json');
    }

    /**
     * The raw ledger, flags intact. Throws on a missing or unparseable file — a marketing build without its
     * content source is a broken build, not an empty one.
     *
     * @return array<string, mixed>
     */
    public static function ledger(): array
    {
        $raw = @file_get_contents(self::path());

        if ($raw === false) {
            throw new RuntimeException('Marketing content ledger missing: '.self::path());
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Marketing content ledger is not valid JSON: '.self::path());
        }

        return $decoded;
    }

    /**
     * Has the ledger been DECLARED production-ready? The design's own convention: the status BEGINS with
     * "ILLUSTRATIVE" until the founder replaces the content. ⚠️ Only a status that OPENS with that word is
     * illustrative — one that merely CONTAINS it ("Production ready — illustrative content replaced") is
     * production-declared, and a missing/empty/non-string status is production-declared too. Fail closed,
     * never fail quiet.
     *
     * @param  array<string, mixed>|null  $ledger
     */
    public static function declaredProduction(?array $ledger = null): bool
    {
        $ledger ??= self::ledger();
        $status = $ledger['status'] ?? null;

        if (! is_string($status) || trim($status) === '') {
            return true;   // no declaration → strict
        }

        return preg_match('/^\s*illustrative\b/iu', $status) !== 1;
    }

    /**
     * May this single entry be PUBLISHED (rendered in a production-like context)?
     *
     * ⚠️ ALLOW-LIST, not a reject-list: `real` must be exactly `true` or an explicit JSON number (int or
     * float — the true value itself; an explicit 0 is a value someone deliberately typed, not an
     * ambiguity). Everything else — null, false, "", "no", "TBD", a numeric STRING, an array, an absent
     * key — is REFUSED. Where consent applies (named people), `consent` must be strictly true; any other
     * shape, including absence, is a refusal.
     *
     * @param  array<string, mixed>  $entry
     */
    public static function isPublishable(array $entry, bool $requiresConsent = false): bool
    {
        $real = $entry['real'] ?? null;

        if ($real !== true && ! is_int($real) && ! is_float($real)) {
            return false;
        }

        if ($requiresConsent && ($entry['consent'] ?? false) !== true) {
            return false;
        }

        return true;
    }

    /**
     * Does content rendered in this environment get the honesty FILTER? ⚠️ An inverted allow-list: only
     * the explicit dev environments render unfiltered — production, staging, an empty name, a typo'd
     * "prod", and every environment this code has never heard of ALL filter. An unknown environment
     * failing OPEN would be the silent-error direction (§N.18.4) — the first version keyed on
     * environment === 'production' exactly, and a `staging` host would have rendered every fabricated
     * entry unfiltered.
     */
    public static function filtersFor(string $environment): bool
    {
        return ! in_array(strtolower(trim($environment)), self::UNFILTERED_ENVIRONMENTS, true);
    }

    /**
     * The stats-band figures. Under the filter (any non-dev environment, or $filtered forced true)
     * unpublishable figures are REMOVED — the band renders honestly smaller, never an invented number.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function figures(?bool $filtered = null): array
    {
        $figures = self::ledger()['figures'] ?? [];
        $figures = is_array($figures) ? array_filter($figures, 'is_array') : [];

        if (! self::filtered($filtered)) {
            return $figures;
        }

        return array_filter($figures, fn (array $f): bool => self::isPublishable($f));
    }

    /**
     * Named customers (case-study subjects). Consent REQUIRED — these are named businesses.
     *
     * @return list<array<string, mixed>>
     */
    public static function customers(?bool $filtered = null): array
    {
        return self::people('customers', $filtered);
    }

    /**
     * Named testimonial authors. Consent REQUIRED — these are named people with quoted words.
     *
     * @return list<array<string, mixed>>
     */
    public static function testimonials(?bool $filtered = null): array
    {
        return self::people('testimonials', $filtered);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function people(string $section, ?bool $filtered): array
    {
        $entries = self::ledger()[$section] ?? [];
        $entries = is_array($entries) ? array_values(array_filter($entries, 'is_array')) : [];

        if (! self::filtered($filtered)) {
            return $entries;
        }

        return array_values(array_filter(
            $entries,
            fn (array $e): bool => self::isPublishable($e, requiresConsent: true),
        ));
    }

    private static function filtered(?bool $filtered): bool
    {
        return $filtered ?? self::filtersFor(app()->environment());
    }
}
