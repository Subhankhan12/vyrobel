<?php

declare(strict_types=1);

namespace App\Support\Marketing;

use App\Models\Plan;
use App\Support\Money\MoneyFormatter;

/**
 * THE ONE PRICE THE MARKETING SITE MAY SHOW (front-website B12, §N.8f).
 *
 * ⚠️ WHY THIS EXISTS. `/pricing` and the home page each hard-coded their own prices, and they disagreed —
 * with each other AND with the billing system: the page advertised **Starter €6 / Pro €4 per unit**, home
 * said **€6 / €5 per unit**, and the engine charges **flat €49 / €99**. Three independent representations
 * of one number, drifting exactly as §N.8f predicts. A marketing page that advertises a price the system
 * does not charge is the money-surface version of the false-claim shape (§N.8d).
 *
 * ⚠️ THE DIRECTION IS ONE-WAY. This reads `plans` (what the engine actually charges, via the same rows
 * `Plan::chargeFor()` bills from) and falls back to `config/billing.php`'s tier catalogue. Marketing
 * conforms to billing; billing NEVER conforms to marketing. Nothing here writes, and `config/billing.php`
 * is not touched — it is what charges real customers.
 *
 * ⚠️ WHAT THE ENGINE CHARGES TODAY — confirmed, not assumed: every seeded plan is `pricing_type = 'flat'`
 * with `unit_rate_minor` NULL, so `chargeFor()` returns €49.00 (Essential) and €99.00 (Pro) at quantity 1.
 * The per-unit shape is fully supported by the engine and used by NO plan. ⚠️ That is why the design's
 * "€X / unit / mo" framing cannot simply be re-numbered: it describes a model the system does not run.
 * See `docs/DECISIONS.md` (Gate 3b) — the framing copy is a founder decision, surfaced rather than invented.
 *
 * ⚠️ PLACEHOLDER, AND SAYS SO. The tier prices are flagged PLACEHOLDER in `config/billing.php` pending the
 * founder's real numbers. {@see self::isPlaceholder()} lets a page say so rather than present a placeholder
 * as a firm price.
 */
final class MarketingPricing
{
    /** Flat: one price regardless of unit count. Per-unit: rate × the org's real active-unit count. */
    public const SHAPE_FLAT = 'flat';

    public const SHAPE_PER_UNIT = 'per_unit';

    /**
     * The publishable tiers, in catalogue order — read from the rows the engine bills from.
     *
     * @return list<array{code: string, name: string, shape: string, amount_minor: int, formatted: string, interval: string, unit_rate_minor: ?int}>
     */
    public static function tiers(string $interval = 'monthly'): array
    {
        $plans = Plan::withoutGlobalScopes()
            ->where('interval', $interval)
            ->where('is_active', true)
            ->orderBy('price_minor')
            ->get();

        if ($plans->isEmpty()) {
            return self::fromConfig($interval);
        }

        return $plans->map(fn (Plan $p): array => [
            'code' => (string) $p->code,
            'name' => (string) $p->name,
            'shape' => (string) ($p->pricing_type ?? self::SHAPE_FLAT),
            'amount_minor' => (int) ($p->pricing_type === self::SHAPE_PER_UNIT
                ? ($p->unit_rate_minor ?? 0)
                : ($p->price_minor ?? 0)),
            'formatted' => MoneyFormatter::display(
                (int) ($p->pricing_type === self::SHAPE_PER_UNIT ? ($p->unit_rate_minor ?? 0) : ($p->price_minor ?? 0)),
                (string) ($p->currency ?? 'EUR'),
            ),
            'interval' => (string) ($p->interval ?? $interval),
            'unit_rate_minor' => $p->unit_rate_minor === null ? null : (int) $p->unit_rate_minor,
        ])->values()->all();
    }

    /**
     * ⚠️ THE MODEL THE ENGINE ACTUALLY RUNS — `flat` or `per_unit`. A page must not describe one while the
     * engine charges the other; the suffix below follows from this, so the two can never disagree.
     */
    public static function shape(string $interval = 'monthly'): string
    {
        foreach (self::tiers($interval) as $tier) {
            if ($tier['shape'] === self::SHAPE_PER_UNIT) {
                return self::SHAPE_PER_UNIT;
            }
        }

        return self::SHAPE_FLAT;
    }

    /**
     * The price suffix, DERIVED from the shape — "/ mo" on a flat plan, "/ unit / mo" on a per-unit one.
     *
     * ⚠️ This is the whole B12 fix in one method: the "per unit" qualifier can no longer be typed into a
     * page independently of what is charged. Flip the seeded plans to `per_unit` and every marketing
     * surface says "per unit" the same day — and not one day earlier.
     */
    public static function suffix(string $interval = 'monthly'): string
    {
        $period = $interval === 'yearly' ? 'yr' : 'mo';

        return self::shape($interval) === self::SHAPE_PER_UNIT
            ? '/ unit / '.$period
            : '/ '.$period;
    }

    /**
     * The tiers shaped for the marketing cards — price, suffix, cycle note and the REAL feature gates.
     *
     * ⚠️ THE FEATURES ARE THE ENGINE'S OWN GATES, not marketing copy. The design's card lists asserted
     * things the system does not enforce ("Up to 3 team members" where the config allows 5; a four-tier
     * ladder where two plans exist). What a plan actually grants is `config('billing.tiers.*.features')`
     * — the same definitions the paywall and the `planAllows` feature-gate read — so a card cannot
     * promise a capability the org will not get.
     *
     * @return list<array<string, mixed>>
     */
    public static function tiersForDisplay(string $interval = 'monthly'): array
    {
        $catalogue = (array) config('billing.tiers', []);

        return array_map(function (array $tier) use ($catalogue, $interval): array {
            // ⚠️ The plan CODE carries its interval (`essential_monthly`); the config catalogue is keyed by
            // tier alone (`essential`). Derive the tier key rather than assume they match — they do not.
            $tierKey = (string) str($tier['code'])->before('_');
            $features = (array) ($catalogue[$tierKey]['features'] ?? []);

            $lines = [];
            foreach ($features as $feature) {
                if (($feature['enabled'] ?? false) !== true) {
                    continue;   // a gate that is OFF is not a selling point — it is simply absent
                }
                $label = (string) ($feature['label'] ?? '');
                $limit = $feature['limit'] ?? null;
                $lines[] = $limit === null ? $label : $label.' (up to '.$limit.')';
            }

            return $tier + [
                'sub' => self::shape($interval) === self::SHAPE_PER_UNIT
                    ? 'Per active unit'
                    : 'One price, any number of units',
                'cycle' => $interval === 'yearly' ? 'Billed yearly' : 'Billed monthly',
                'features' => $lines,
            ];
        }, self::tiers($interval));
    }

    /**
     * The page headline, DERIVED from the model the engine runs.
     *
     * ⚠️ PLACEHOLDER COPY, DELIBERATELY PLAIN — and it is the one thing here the founder should replace.
     * The design's headline was *"One price per unit. The direct channel pays for it."*, which asserts a
     * per-unit model; the engine bills FLAT, so that sentence was simply false and could not be left on a
     * live page. Writing marketing copy is not this build's business, so this states the true model in the
     * fewest possible words and no more. ⚠️ It follows the shape, so it can never contradict billing again.
     */
    public static function headline(string $interval = 'monthly'): string
    {
        return self::shape($interval) === self::SHAPE_PER_UNIT
            ? 'One price per unit. The direct channel pays for it.'
            : 'One price per property account. The direct channel pays for it.';
    }

    /** The small-print note beside the prices — likewise derived, and honest about the placeholder status. */
    public static function priceNote(string $interval = 'monthly'): string
    {
        $model = self::shape($interval) === self::SHAPE_PER_UNIT
            ? 'per active unit, per month'
            : 'per account, per month, whatever your unit count';

        return 'Prices are '.$model.(self::isPlaceholder() ? ', indicative and' : ',').' exclusive of VAT.';
    }

    /**
     * The FAQ's "who pays" line — derived, because it too asserted the billing model ("You pay per unit").
     * ⚠️ A pricing claim in prose is still a pricing claim; a sweep found this one after the headline.
     */
    public static function whoPays(string $interval = 'monthly'): string
    {
        return self::shape($interval) === self::SHAPE_PER_UNIT
            ? 'You pay per unit; nobody else is billed.'
            : 'You pay one subscription; nobody else is billed.';
    }

    /** Are the published prices still the config's flagged PLACEHOLDERS? */
    public static function isPlaceholder(): bool
    {
        $config = (array) config('billing.tiers', []);
        $configured = collect($config)->pluck('prices_minor.monthly')->filter()->sort()->values()->all();
        $live = collect(self::tiers('monthly'))->pluck('amount_minor')->sort()->values()->all();

        // Identical to the shipped placeholder catalogue ⇒ nobody has set real prices yet.
        return $configured === $live;
    }

    /**
     * @return list<array{code: string, name: string, shape: string, amount_minor: int, formatted: string, interval: string, unit_rate_minor: ?int}>
     */
    private static function fromConfig(string $interval): array
    {
        $out = [];
        foreach ((array) config('billing.tiers', []) as $code => $tier) {
            $minor = (int) ($tier['prices_minor'][$interval] ?? 0);
            $out[] = [
                'code' => (string) $code,
                'name' => (string) ($tier['name'] ?? $code),
                'shape' => self::SHAPE_FLAT,
                'amount_minor' => $minor,
                'formatted' => MoneyFormatter::display($minor, (string) config('billing.default_currency', 'EUR')),
                'interval' => $interval,
                'unit_rate_minor' => null,
            ];
        }

        return $out;
    }
}
