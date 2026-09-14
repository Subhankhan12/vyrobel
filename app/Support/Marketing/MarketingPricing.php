<?php

declare(strict_types=1);

namespace App\Support\Marketing;

use App\Models\Plan;
use App\Models\PlanFeature;
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
     * ⚠️⚠️ THE CATALOGUE SELLS BOTH SHAPES — an answer the page-level question needs and did not have.
     *
     * `shape()` used to return `per_unit` the moment ANY plan was per-unit, which is a defensible answer to
     * "does this product sell per-unit at all" and a FALSE one to "what is this plan". Page-level prose
     * asks the first question; a card asks the second. With both shapes live, neither single-model sentence
     * is true, so there is a third state rather than a coin-flip between two falsehoods.
     */
    public const SHAPE_MIXED = 'mixed';

    /**
     * The billing cycles the catalogue can express — a CLOSED set, mirroring the `plans.interval` enum.
     *
     * ⚠️ WHY IT IS A CONSTANT AND NOT TWO STRINGS IN A VIEW. Both `/pricing` surfaces render every cycle;
     * a view that spells the list itself is a second definition of what cycles exist, and adding a third
     * interval to the enum would then light up one surface and not the other.
     */
    public const CYCLES = ['monthly', 'yearly'];

    /**
     * ⚠️⚠️ THE TIER'S OWN IDENTITY — the thing a monthly row and a yearly row of the SAME plan share.
     *
     * A plan CODE carries its interval (`essential_monthly`), so a code is unique per row and useless as a
     * cross-cycle identity; the `plans` table has no tier column at all. This strips the interval suffix,
     * which is the convention every seeded and admin-created code follows.
     *
     * ⚠️ IT REPLACES `str($code)->before('_')`, which was the previous derivation in two places. That form
     * agrees with this one on every code that exists today (`essential_monthly` → `essential`) and is wrong
     * on the first multi-word one: `pro_plus_monthly` → `pro`, silently COLLIDING with the Pro tier and
     * handing a card the wrong feature list. One definition, and the safer of the two (§N.8f).
     */
    public static function tierKey(string $code): string
    {
        foreach (self::CYCLES as $interval) {
            if (str_ends_with($code, '_'.$interval)) {
                return substr($code, 0, -strlen($interval) - 1);
            }
        }

        return $code;
    }

    /**
     * The publishable tiers, in catalogue order — read from the rows the engine bills from.
     *
     * @return list<array{code: string, name: string, shape: string, amount_minor: int, formatted: string, interval: string, unit_rate_minor: ?int}>
     */
    public static function tiers(string $interval = 'monthly'): array
    {
        // ⚠️⚠️ `query()`, NOT `withoutGlobalScopes()`. `Plan` is a GLOBAL catalogue and carries exactly ONE
        //    global scope — `SoftDeletingScope` — so the bare call escaped a tenant scope THAT DOES NOT
        //    EXIST on this model and removed the soft-delete filter as its only real effect. A plan an
        //    admin had deleted stayed advertised here, with a price and a Stripe id behind it, while
        //    `BillingService` (which uses `Plan::query()`) could no longer find it.
        $plans = Plan::query()
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
        $shapes = [];

        foreach (self::tiers($interval) as $tier) {
            $shapes[(string) $tier['shape']] = true;
        }

        if (count($shapes) > 1) {
            return self::SHAPE_MIXED;
        }

        return isset($shapes[self::SHAPE_PER_UNIT]) ? self::SHAPE_PER_UNIT : self::SHAPE_FLAT;
    }

    /**
     * The price suffix, DERIVED from the shape — "/ mo" on a flat plan, "/ unit / mo" on a per-unit one.
     *
     * ⚠️ This is the whole B12 fix in one method: the "per unit" qualifier can no longer be typed into a
     * page independently of what is charged. Flip the seeded plans to `per_unit` and every marketing
     * surface says "per unit" the same day — and not one day earlier.
     */
    public static function suffix(string $shape, string $interval = 'monthly'): string
    {
        $period = $interval === 'yearly' ? 'yr' : 'mo';

        return $shape === self::SHAPE_PER_UNIT
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
            $tierKey = self::tierKey((string) $tier['code']);
            $features = (array) ($catalogue[$tierKey]['features'] ?? []);

            // ⚠️⚠️ WHAT A PLAN GATES IS `plan_features`, NOT THE CONFIG. The platform admin edits
            //    `plan_features` (Admin\PlanController) and `Organization::planAllows()` reads it at
            //    runtime — so a card rendered from `config/billing.php` could advertise a capability the
            //    plan no longer grants, or hide one it does. Two sources for one concept is exactly the
            //    drift §N.8f exists to stop, and on this page it would be a FALSE CLAIM where money is
            //    decided.
            // ⚠️ THE LABEL still comes from the catalogue — it is the only place a human-readable name
            //    lives; `plan_features.key` is a slug. So: the catalogue says what a feature is CALLED,
            //    `plan_features` says whether this plan HAS it and up to what limit.
            // ⚠️ FALLS BACK TO THE CATALOGUE when a plan has no `plan_features` rows — the config-only
            //    path (`fromConfig`) must keep working, and an empty table must not blank every card.
            $gates = PlanFeature::query()
                ->whereHas('plan', fn ($q) => $q->where('code', $tier['code']))
                ->get()
                ->keyBy('key');

            $lines = [];
            foreach ($features as $key => $feature) {
                $gate = $gates->get(is_string($key) ? $key : ($feature['key'] ?? ''));

                $enabled = $gate !== null ? (bool) $gate->enabled : (($feature['enabled'] ?? false) === true);
                if (! $enabled) {
                    continue;   // a gate that is OFF is not a selling point — it is simply absent
                }

                $label = (string) ($feature['label'] ?? '');
                $limit = $gate !== null ? $gate->limit : ($feature['limit'] ?? null);
                $lines[] = $limit === null ? $label : $label.' (up to '.$limit.')';
            }

            return $tier + [
                // ⚠️ THE KEY TRAVELS WITH THE ROW so no caller re-derives it — see `tierKey()`.
                'key' => $tierKey,
                // ⚠️ THE ROW'S OWN SHAPE, not the catalogue's. `$tier['shape']` is `plans.pricing_type`
                //    carried straight off the row by `tiers()`, so a per-unit plan cannot describe a flat
                //    one — which is exactly what a catalogue-wide read did.
                'sub' => (string) $tier['shape'] === self::SHAPE_PER_UNIT
                    ? 'Per active unit'
                    : 'One price, any number of units',
                'cycle' => $interval === 'yearly' ? 'Billed yearly' : 'Billed monthly',
                'features' => $lines,
            ];
        }, self::tiers($interval));
    }

    /**
     * ⚠️⚠️ ONE ROW PER TIER, CARRYING EVERY CYCLE — the shape that makes a MISPAIRING IMPOSSIBLE.
     *
     * ⚠️ WHAT THIS REPLACES, AND WHY A BOUNDS CHECK WOULD NOT HAVE DONE. Both `/pricing` surfaces built two
     * INDEPENDENT lists — `tiersForDisplay('monthly')` and `tiersForDisplay('yearly')` — and then paired
     * them BY POSITION (`$yearly[$loop->parent->index]`). Two facts made that unsafe, and only the first
     * announces itself:
     *
     *   1. **DIFFERENT LENGTHS → a hard 500.** `tiers()` filters on `interval`, so a plan that exists in one
     *      interval only makes one list shorter. Reproduced: a third monthly-only plan gave
     *      `Undefined array key 2` and `/pricing` returned 500. ⚠️ And the admin panel creates ONE plan per
     *      interval, so a founder adding a third tier CANNOT AVOID passing through that state.
     *   2. **⚠️⚠️ EQUAL LENGTHS, DIFFERENT ORDER → NO ERROR AT ALL.** `tiers()` orders by `price_minor`
     *      INDEPENDENTLY PER INTERVAL. Any yearly pricing that does not preserve the monthly rank silently
     *      transposes the columns. Reproduced: `/pricing` returned **200** with the Essential card showing
     *      another tier's yearly figure. A wrong price under the right plan name is worse than a 500,
     *      because a 500 announces itself.
     *
     * ⚠️ HOW MISPAIRING IS NOW IMPOSSIBLE, not merely guarded. A row is filed under the group its OWN
     * `key` names, at the cycle its OWN `interval` names. No index is computed anywhere, so there is no
     * arithmetic to be wrong: the yearly figure under a card is that tier's own yearly row or it is
     * absent. Ordering, length and price rank cannot enter into it. ⚠️ Nothing needs to enforce that the
     * two lists agree, because there are no longer two lists.
     *
     * ⚠️ A MISSING CYCLE IS `null`, NOT A ZERO AND NOT A DERIVED FIGURE. An annual-only tier is a
     * legitimate product; an annual price computed from a monthly one is an invented price.
     * {@see self::cycleUnavailableNote()} is what a surface says instead.
     *
     * ⚠️ ORDER: cycles in `CYCLES` order, so today's monthly-price ordering is unchanged and a tier that
     * exists only in a later cycle appends rather than reshuffling the page.
     *
     * ⚠️ COLLISION: if two plans in one interval derive the same `key`, the FIRST is kept. That is a data
     * error, not a display case — see `docs/PRICING-PAIRING.md`; the rule belongs in `PlanIntegrity`, and
     * plan validation is a money path with its own decision.
     *
     * @return list<array<string, mixed>>
     */
    public static function tiersWithCycles(): array
    {
        $groups = [];

        foreach (self::CYCLES as $interval) {
            foreach (self::tiersForDisplay($interval) as $tier) {
                $key = (string) $tier['key'];

                $groups[$key] ??= [
                    'key' => $key,
                    'name' => (string) $tier['name'],
                    'code' => (string) $tier['code'],
                    'sub' => (string) $tier['sub'],
                    'features' => (array) $tier['features'],
                    'cycles' => array_fill_keys(self::CYCLES, null),
                ];

                // ⚠️ `??=` — the row is placed at ITS OWN interval, and a second row deriving the same key
                //    in the same cycle never overwrites the first.
                // ⚠️⚠️ THE RELABEL WAS HERE. This stamped a CATALOGUE-WIDE suffix onto every tier's row,
                //    so the day one per-unit plan went live every flat card read "/ unit / mo" — €49 shown
                //    as per-unit against a plan that charges €49 flat, on the page where money is decided.
                //    The suffix now comes from the ROW's own shape, which `tiers()` already carried.
                $groups[$key]['cycles'][$interval] ??= $tier + ['suffix' => self::suffix((string) $tier['shape'], $interval)];
            }
        }

        return array_values($groups);
    }

    /**
     * What a surface says where a tier has no row for a cycle — ⚠️ HONEST, and never a number.
     *
     * ⚠️ WHY A SENTENCE AND NOT AN OMITTED BLOCK. With scripts off both cycles are visible (the
     * visible-by-default rule), and the switcher collapses one; a tier that simply omitted its yearly
     * block would leave a HOLE where a price belongs the moment a visitor pressed "Annual" — which reads
     * as a broken page, not as an absent product. Stating the fact keeps the switch whole.
     *
     * ⚠️ WHY NOTHING IS DERIVED. The obvious alternative — showing 12 × the monthly figure — invents a
     * price the engine would not charge, on the page where money is decided. `isPlaceholder()` is TRUE
     * besides, so it would be arithmetic over numbers nobody has set.
     *
     * ⚠️ PRESENT TENSE, NO ROADMAP. It states what is true now and promises no future cycle.
     * ⚠️ It is COPY, and short enough for a table header as well as a card — the founder may reword it;
     * what must not change is that it carries no figure.
     */
    public static function cycleUnavailableNote(string $interval): string
    {
        return $interval === 'yearly'
            ? 'Annual billing not offered'
            : 'Monthly billing not offered';
    }

    /**
     * THE PLAN COMPARISON — every row derived from the engine's own feature gates (§N.8f).
     *
     * ⚠️ WHY THIS EXISTS, AND WHY IT IS THE WHOLE POINT OF THIS PASS. B12 rebuilt `/pricing`'s CARDS to read
     * from here and pinned them against `Plan::chargeFor()` — and the comparison table ONE SECTION BELOW,
     * on the same page, kept a hand-drawn ladder of FOUR invented tiers (Starter / Growth / Pro /
     * Enterprise) and TWELVE invented feature differences. Live, and about money.
     *
     * ⚠️ THE SHAPE OF THE LIE WAS STRUCTURAL, NOT NUMERIC. Re-numbering the columns would not have fixed it:
     * a four-column ladder asserts three upgrade steps, and the product has ONE tier boundary. Counted
     * against `config('billing.tiers')`, the real differences are exactly TWO — `channels` (Pro only) and
     * `team` (5 seats vs unlimited). `analytics` is enabled on both, so it is not a difference at all. The
     * drawn table manufactured ten more.
     *
     * ⚠️ SO THE ROWS ARE NOT COPY. They are the catalogue the paywall and `Organization::planAllows()` read,
     * which means a marketing table can no longer promise a capability the org will not get, and adding a
     * real gate to the config puts a real row here the same day — and not one day earlier.
     *
     * ⚠️ A row that the config does not gate CANNOT appear. That is the guarantee; it is also the limit —
     * see `differences` below, which is how a caller tells "two plans that genuinely differ" from "two
     * plans that do not", rather than assuming a table is worth drawing.
     *
     * @return array{
     *     tiers: list<array<string, mixed>>,
     *     rows: list<array{key: string, label: string, cells: list<array{has: bool, note: string}>, differs: bool}>,
     *     differences: int
     * }
     */
    public static function comparison(): array
    {
        $catalogue = (array) config('billing.tiers', []);

        // ⚠️⚠️ THE COLUMNS ARE THE GROUPED TIERS, NOT ONE INTERVAL'S LIST — and the parameter this method
        //    used to take is GONE rather than ignored. It only ever chose which interval's list became the
        //    columns, which meant a tier sold on annual billing ONLY had no column at all: it vanished
        //    from the comparison while appearing on the cards above it, on the same page.
        //    ⚠️ A parameter that no longer changes anything is worse than none — a caller passing 'yearly'
        //    would reasonably believe it had asked for something.
        $tiers = self::tiersWithCycles();

        // ⚠️ The union of feature keys, in the catalogue's own order — never a hand-written row list.
        $keys = [];
        foreach ($catalogue as $tier) {
            foreach ((array) ($tier['features'] ?? []) as $key => $_) {
                if (! in_array($key, $keys, true)) {
                    $keys[] = $key;
                }
            }
        }

        $rows = [];
        foreach ($keys as $key) {
            $label = '';
            $cells = [];

            foreach ($tiers as $tier) {
                // ⚠️ The tier's own key — `tierKey()`, the ONE derivation (§N.8f), carried on the row.
                $feature = (array) ($catalogue[(string) $tier['key']]['features'][$key] ?? []);
                $label = $label !== '' ? $label : (string) ($feature['label'] ?? $key);
                $has = ($feature['enabled'] ?? false) === true;
                // ⚠️ `array_key_exists`, NOT `?? null` — and the difference is visible on the page. A purely
                // boolean gate declares no `limit` at all, so `?? null` made "Insights & reports" render as
                // "Unlimited", which is a quantity nobody claimed about a feature that has none. Only a
                // feature that actually DECLARES a limit gets a quantity.
                $hasLimit = array_key_exists('limit', $feature);
                $limit = $feature['limit'] ?? null;

                $cells[] = [
                    'has' => $has,
                    // ⚠️ A LIMIT IS PART OF THE ANSWER, not a footnote. "Team members ✓ / ✓" would hide the
                    // only other real difference in the product — five seats against unlimited.
                    'note' => match (true) {
                        ! $has => '',
                        ! $hasLimit => '',
                        $limit === null => 'Unlimited',
                        default => 'Up to '.$limit,
                    },
                ];
            }

            $notes = array_column($cells, 'note');
            $flags = array_column($cells, 'has');

            $rows[] = [
                'key' => $key,
                'label' => $label,
                'cells' => $cells,
                // ⚠️ DERIVED, never asserted: a row differs when the tiers do not all answer it identically.
                'differs' => count(array_unique($flags, SORT_REGULAR)) > 1 || count(array_unique($notes)) > 1,
            ];
        }

        return [
            'tiers' => $tiers,
            'rows' => $rows,
            'differences' => count(array_filter($rows, fn (array $r): bool => $r['differs'])),
        ];
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
        // ⚠️ THREE ANSWERS, BECAUSE THERE ARE THREE STATES. With both shapes live neither single-model
        //    sentence is true, and picking one would put a false claim at the top of the pricing page.
        return match (self::shape($interval)) {
            self::SHAPE_PER_UNIT => 'One price per unit. The direct channel pays for it.',
            self::SHAPE_MIXED => 'Priced per account or per unit, depending on the plan. The direct channel pays for it.',
            default => 'One price per property account. The direct channel pays for it.',
        };
    }

    /** The small-print note beside the prices — likewise derived, and honest about the placeholder status. */
    public static function priceNote(string $interval = 'monthly'): string
    {
        // ⚠️⚠️ THE PERIOD FOLLOWS THE INTERVAL, AND IT DID NOT USED TO. This method has always TAKEN an
        //    `$interval` and then hardcoded "per month" in both branches — harmless while every surface
        //    rendered monthly, and a live falsehood the moment `/pricing` could show a yearly price:
        //    "€490.00 / yr" beside "Prices are per account, per month" is worse than showing no yearly
        //    price at all. ⚠️ A control that changes the number and leaves the period beside it unchanged
        //    is the failure this fix exists to prevent.
        $period = $interval === 'yearly' ? 'per year' : 'per month';

        // ⚠️ The mixed wording names both models rather than asserting one of them for every card —
        //    each card already carries its own suffix, and this line must not contradict them.
        $model = match (self::shape($interval)) {
            self::SHAPE_PER_UNIT => 'per active unit, '.$period,
            self::SHAPE_MIXED => 'per account or per active unit depending on the plan, '.$period,
            default => 'per account, '.$period.', whatever your unit count',
        };

        return 'Prices are '.$model.(self::isPlaceholder() ? ', indicative and' : ',').' exclusive of VAT.';
    }

    /**
     * The FAQ's "who pays" line — derived, because it too asserted the billing model ("You pay per unit").
     * ⚠️ A pricing claim in prose is still a pricing claim; a sweep found this one after the headline.
     */
    public static function whoPays(string $interval = 'monthly'): string
    {
        return match (self::shape($interval)) {
            self::SHAPE_PER_UNIT => 'You pay per unit; nobody else is billed.',
            self::SHAPE_MIXED => 'You pay for the plan you choose; nobody else is billed.',
            default => 'You pay one subscription; nobody else is billed.',
        };
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
