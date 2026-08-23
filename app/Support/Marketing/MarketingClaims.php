<?php

declare(strict_types=1);

namespace App\Support\Marketing;

use Carbon\CarbonImmutable;
use RuntimeException;

/**
 * THE EXTERNAL-CLAIMS LEDGER — and the freshness guard over it (front-website Gate 3b, audit B).
 *
 * ⚠️ WHY THIS EXISTS, AND WHY IT IS A SIBLING OF THE CONTENT GATE. `MarketingContent` guards what we say
 * about OURSELVES; nothing guarded what we say about ANYONE ELSE. Audit B found twelve assertions about
 * named competitors — including one about their PRICING — with no source, no date, and a disclaimer
 * reading "at the time of writing" that carries no date at all and therefore can never be shown stale.
 *
 * ⚠️ THAT LAST ONE IS THE WHOLE POINT. A competitor changes their pricing and tells nobody. No test fails,
 * no error is logged, no monitor fires — the page is simply wrong, for as long as nobody happens to look.
 * It is the `MAIL_MAILER=log` failure shape wearing a marketing hat, and the only defence is an expiry
 * date that something checks. {@see self::stale()} is that something.
 *
 * ⚠️ THE TWO RULES, IN ONE SENTENCE EACH:
 *   · UNSOURCED — a claim with no `source_url` (or no `verified_on`) may never be published.
 *   · STALE — a claim whose `review_by` has passed may never be published, even though it once was true.
 *
 * ⚠️ SOURCING IS NOT TESTING. A competitor's published price is SOURCEABLE from their own public page. A
 * claim about their product's QUALITY or EASE OF USE is not: it could only be established by running their
 * product, which we have not done and will not. Those are `kind: requires_testing` and their disposition is
 * `soften` or `remove` — never "held pending a test that will not happen".
 *
 * ⚠️ THE HONEST LIMIT OF THIS GUARD — the same one Gate 0 stated and D13 proved real: it governs LEDGERED
 * claims. An assertion typed straight into a Blade page carries no entry and no gate can see it. That is a
 * review discipline (external claims render only through this class), not a property of this code.
 */
final class MarketingClaims
{
    public const KIND_SOURCEABLE = 'sourceable';

    public const KIND_REQUIRES_TESTING = 'requires_testing';

    /**
     * ⚠️ A claim about OUR OWN PRODUCT, which no third-party URL can settle.
     *
     * ⚠️⚠️ ITS EVIDENCE IS A TEST, NOT A `file:line` POINTER. A pointer ROTS SILENTLY: the code moves, the
     * behaviour changes, the claim quietly becomes false, and the citation still points confidently at a
     * line that no longer says what it said. ⚠️ A citation that can decay is the exact failure this ledger
     * exists to prevent, so admitting one here would be the §N.29 label shape inside the gate built
     * against it. An internal source is a test that FAILS when the claim stops being true.
     *
     * ⚠️ AND IT IS NOT A LOOPHOLE: `internal` alone sources nothing. The claim must name a test in
     * `internal_test`, and `MarketingInternalClaimsTest` asserts every named test actually EXISTS — a
     * citation naming nothing reads as evidence and is not.
     */
    public const KIND_INTERNAL = 'internal';

    public static function path(): string
    {
        return resource_path('marketing/claims.json');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function all(): array
    {
        $raw = @file_get_contents(self::path());

        if ($raw === false) {
            throw new RuntimeException('Marketing claims ledger missing: '.self::path());
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded) || ! isset($decoded['claims']) || ! is_array($decoded['claims'])) {
            throw new RuntimeException('Marketing claims ledger is not valid JSON: '.self::path());
        }

        return array_values(array_filter($decoded['claims'], 'is_array'));
    }

    /**
     * Is this claim SOURCED? Both a source and the date it was actually read are required — a URL with no
     * date is the same unfalsifiable shape as "at the time of writing".
     *
     * @param  array<string, mixed>  $claim
     */
    public static function isSourced(array $claim): bool
    {
        // ⚠️ AN INTERNAL CLAIM IS SOURCED BY ITS TEST, and by nothing else — not by a URL it will never
        // have, and not by carrying the kind. No test named, no source.
        if (($claim['kind'] ?? '') === self::KIND_INTERNAL) {
            $test = $claim['internal_test'] ?? null;

            return is_string($test) && trim($test) !== '';
        }

        $url = $claim['source_url'] ?? null;
        $on = $claim['verified_on'] ?? null;

        return is_string($url) && trim($url) !== ''
            && is_string($on) && self::parse($on) !== null;
    }

    /**
     * Has this claim passed its review date? ⚠️ A MISSING `review_by` counts as STALE, not as "no expiry" —
     * fail closed (§N.18.4). An unsourced claim is reported as unsourced rather than stale, so the two
     * failures stay distinguishable in a build log.
     *
     * @param  array<string, mixed>  $claim
     */
    public static function stale(array $claim, ?CarbonImmutable $now = null): bool
    {
        if (! self::isSourced($claim)) {
            return false;   // unsourced is its own, louder failure — see unsourced()
        }

        // ⚠️ AN INTERNAL CLAIM DOES NOT EXPIRE, AND THAT IS NOT AN EXEMPTION — IT IS THE POINT.
        // `review_by` exists because an external citation can go stale without anyone noticing, so
        // somebody must re-read it. An internal claim's citation is a test that runs on every build: the
        // day the claim stops being true, the test REDS. It cannot rot unobserved, so there is nothing for
        // a review date to protect against. ⚠️ The protection it DOES need — that the named test is real —
        // is enforced by MarketingInternalClaimsTest, not by a date.
        if (($claim['kind'] ?? '') === self::KIND_INTERNAL) {
            return false;
        }

        $review = self::parse((string) ($claim['review_by'] ?? ''));

        if ($review === null) {
            return true;    // sourced but with no expiry ⇒ it can never be shown stale ⇒ treat as stale now
        }

        return $review->isBefore($now ?? CarbonImmutable::now()->startOfDay());
    }

    /**
     * May this claim be PUBLISHED? Sourced, in date, and not one whose disposition is to drop it.
     *
     * @param  array<string, mixed>  $claim
     */
    public static function isPublishable(array $claim, ?CarbonImmutable $now = null): bool
    {
        if (($claim['disposition'] ?? 'publish') === 'remove') {
            return false;
        }

        if (($claim['kind'] ?? self::KIND_SOURCEABLE) === self::KIND_REQUIRES_TESTING) {
            return false;   // cannot be sourced, only tested — soften or remove it, never publish it
        }

        return self::isSourced($claim) && ! self::stale($claim, $now);
    }

    /**
     * Every claim that cannot publish because nobody has sourced it.
     *
     * @return list<array<string, mixed>>
     */
    public static function unsourced(): array
    {
        return array_values(array_filter(
            self::all(),
            fn (array $c): bool => ($c['kind'] ?? '') !== self::KIND_REQUIRES_TESTING && ! self::isSourced($c),
        ));
    }

    /**
     * Every claim that is sourced but past (or missing) its review date.
     *
     * @return list<array<string, mixed>>
     */
    public static function staleClaims(?CarbonImmutable $now = null): array
    {
        return array_values(array_filter(self::all(), fn (array $c): bool => self::stale($c, $now)));
    }

    /**
     * Claims that can only be settled by testing another vendor's product — they must be softened or
     * removed, never published and never left pending.
     *
     * @return list<array<string, mixed>>
     */
    public static function requiringTesting(): array
    {
        return array_values(array_filter(
            self::all(),
            fn (array $c): bool => ($c['kind'] ?? '') === self::KIND_REQUIRES_TESTING,
        ));
    }

    /**
     * The claims a given page may actually render.
     *
     * @return list<array<string, mixed>>
     */
    public static function publishableFor(string $page, ?CarbonImmutable $now = null): array
    {
        return array_values(array_filter(
            self::all(),
            fn (array $c): bool => ($c['page'] ?? null) === $page && self::isPublishable($c, $now),
        ));
    }

    /** Is this page cleared to publish — every one of its claims sourced, in date and publishable? */
    public static function pageIsCleared(string $page, ?CarbonImmutable $now = null): bool
    {
        $onPage = array_filter(self::all(), fn (array $c): bool => ($c['page'] ?? null) === $page);

        if ($onPage === []) {
            return true;   // a page with no external claims needs no clearance
        }

        foreach ($onPage as $claim) {
            // A `remove`/`soften` disposition is a decision already taken, not a blocker — but anything
            // still marked `publish` must actually be publishable.
            if (($claim['disposition'] ?? 'publish') !== 'publish') {
                continue;
            }
            if (! self::isPublishable($claim, $now)) {
                return false;
            }
        }

        return true;
    }

    private static function parse(string $date): ?CarbonImmutable
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($date))) {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m-d', trim($date))->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
