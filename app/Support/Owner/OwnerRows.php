<?php

declare(strict_types=1);

namespace App\Support\Owner;

use App\Livewire\Operator\OperatorOwners;
use App\Models\ManagementAgreement;
use App\Models\Owner;
use App\Models\OwnerInvite;
use App\Models\OwnerPayoutPreference;
use App\Models\OwnerStatement;
use App\Models\Payout;
use App\Models\PayoutMethod;
use App\Services\PayoutRunService;
use App\Support\I18n\LocalDate;
use App\Support\I18n\LocalText;
use App\Support\Money\MoneyFormatter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * The Owners page's rows (2026-10-01) — ONE definition of an owner as the list shows it: who, how many units, the commission
 * in force, the latest closed statement and what it owes, and its PAY STATUS with the reason in the owner's terms. Read by
 * the page ({@see OperatorOwners}) and its export, so the two cannot disagree.
 *
 * ⚠️ THE STATUS FOLLOWS WHAT THE PAYOUT RUN WILL DO. A held payout, missing tax details or a missing payout account keep an
 * owner on hold; a statement with unverified lines needs review; payouts paused by the team are paused — ⚠️ until 2026-10-01
 * a paused owner read "Ready" and was counted in the money "ready to pay". Since DEC-n (2026-10-02) a pause stops EVERY
 * payout — the automatic run, Run payouts now, Create payouts and Pay ({@see PayoutRunService}, and the payout service
 * itself). Judged on THIS statement's payout only (`owner_statement_id`) — one historical
 * paid payout once made every later statement read "Paid" (OperatorOwnersPayableStateTest).
 * `paused` is its own field: the pay status says "paused" only while money is due, and the row menu and the "Payouts
 * paused" chip need the preference itself.
 *
 * TENANT-scoped: every read goes through the organisation's global scope (OwnerScope is inactive off the portal).
 *
 * @phpstan-type OwnerRow array{id: string, name: string, email: string, initials: string, active: bool, units: int,
 *     unit_codes: list<string>, unit_labels: list<string>, commission: string, period: ?string, net_minor: ?int, currency: string, net: string,
 *     pay_key: string, pay_label: string, reason: ?string, paused: bool, portal_off: bool, invite: ?array{id: string, status: string, expires: ?string}}
 */
final class OwnerRows
{
    /** The order "status" sorts by — what needs someone first. */
    private const STATUS_ORDER = ['hold' => 0, 'draft' => 1, 'paused' => 2, 'due' => 3, 'invited' => 4, 'inactive' => 5, 'none' => 6, 'zero' => 7, 'paid' => 8];

    /** @return list<OwnerRow> */
    public function load(): array
    {
        $owners = Owner::query()->with(['units:id,owner_id,code,name'])->orderBy('name')->get();
        $ids = $owners->pluck('id')->all();
        if ($ids === []) {
            return [];
        }

        // batch-loaded, so the list is a handful of queries however many owners there are
        $statements = OwnerStatement::query()->whereIn('owner_id', $ids)->where('status', '!=', 'draft')
            ->orderByDesc('period_start')->get()->groupBy('owner_id');
        $payouts = Payout::query()->whereIn('owner_id', $ids)->get()->groupBy('owner_id');
        $hasMethod = PayoutMethod::query()->whereIn('owner_id', $ids)->pluck('owner_id')->map(fn ($id): string => (string) $id)->unique()->flip();
        $paused = OwnerPayoutPreference::query()->whereIn('owner_id', $ids)->where('paused', true)->pluck('owner_id')
            ->map(fn ($id): string => (string) $id)->flip();
        $commission = ManagementAgreement::query()->whereIn('owner_id', $ids)->whereNull('unit_id')
            ->effectiveOn(CarbonImmutable::today())->get()->keyBy('owner_id');
        $invites = OwnerInvite::query()->whereIn('owner_id', $ids)->whereIn('status', ['pending', 'expired'])
            ->orderByDesc('created_at')->get()->groupBy('owner_id');

        return $owners->map(function (Owner $owner) use ($statements, $payouts, $hasMethod, $paused, $commission, $invites): array {
            $id = (string) $owner->id;
            /** @var Collection<int, OwnerStatement> $ownerStatements */
            $ownerStatements = $statements->get($id) ?? collect();
            $latest = $ownerStatements->first();
            // `owners.payout_currency` is char(3) NOT NULL — the currency this owner is paid in (no literal fallback, F-44)
            $currency = $latest !== null ? (string) $latest->currency : (string) $owner->payout_currency;
            $net = $latest !== null ? (int) $latest->net_payable_minor : null;
            $invite = ($invites->get($id) ?? collect())->first();
            $agreement = $commission->get($id);

            $isPaused = $paused->has($id);
            [$key, $label, $reason] = $this->status($owner, $latest, $payouts->get($id) ?? collect(), $hasMethod->has($id), $isPaused, $invite);
            // a pause with nothing due yet still has to be visible — its status says why nothing is paid, this says why nothing will be
            if ($isPaused && $reason === null && $owner->status === 'active') {
                $reason = __('php/support/owner/owner_rows.reason_paused_by_team');
            }

            return [
                'id' => $id,
                'name' => (string) $owner->name,
                'email' => (string) ($owner->email ?? ''),
                'initials' => self::initials((string) $owner->name),
                'active' => $owner->status === 'active',
                'units' => $owner->units->count(),
                'unit_codes' => $owner->units->map(fn ($u): string => (string) $u->code)->sort(SORT_NATURAL)->values()->all(),
                'unit_labels' => $owner->units->map(fn ($u): string => (string) ($u->name ?: $u->code))->sort(SORT_NATURAL)->values()->all(),
                'commission' => $agreement !== null ? rtrim(rtrim((string) $agreement->commission_pct, '0'), '.').'%' : '—',
                'period' => $latest !== null ? CarbonImmutable::parse($latest->period_start)->translatedFormat(LocalDate::pattern('M Y')) : null,
                'net_minor' => $net,
                'currency' => $currency,
                'net' => $net !== null ? MoneyFormatter::display($net, $currency) : '—',
                'pay_key' => $key,
                'pay_label' => $label,
                'reason' => $reason,
                'paused' => $isPaused,
                // DEC-o — an operator switched this owner's portal access off (their pay status is unaffected: payouts never read it)
                'portal_off' => $owner->portalIsOff(),
                'invite' => $invite !== null ? ['id' => (string) $invite->id, 'status' => (string) $invite->status, 'expires' => $invite->expires_at?->translatedFormat(LocalDate::pattern('j M Y'))] : null,
            ];
        })->values()->all();
    }

    /** Does a row belong in this view — the chip and the search together. @param OwnerRow $row */
    public function matches(array $row, ?string $filter, string $q): bool
    {
        $inFilter = match ($filter) {
            'ready' => $row['pay_key'] === 'due',
            'hold' => $row['pay_key'] === 'hold',
            'review' => $row['pay_key'] === 'draft',
            'paused' => $row['paused'],
            'invited' => in_array($row['pay_key'], ['invited', 'inactive'], true),
            default => true,
        };
        if (! $inFilter) {
            return false;
        }
        if ($q === '') {
            return true;
        }

        $needle = mb_strtolower($q);
        foreach (array_merge([$row['name'], $row['email']], $row['unit_codes'], $row['unit_labels']) as $hay) {
            if (str_contains(mb_strtolower($hay), $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<OwnerRow>  $rows
     * @return list<OwnerRow>
     */
    public function sort(array $rows, string $sort, string $dir): array
    {
        $sign = $dir === 'desc' ? -1 : 1;
        usort($rows, function (array $a, array $b) use ($sort, $sign): int {
            $byName = strnatcasecmp($a['name'], $b['name']);
            $cmp = match ($sort) {
                'units' => $a['units'] <=> $b['units'],
                // an owner with no statement sorts after every figure, in either direction
                'net' => $a['net_minor'] === null || $b['net_minor'] === null
                    ? ($a['net_minor'] === null) <=> ($b['net_minor'] === null)
                    : $a['net_minor'] <=> $b['net_minor'],
                'status' => (self::STATUS_ORDER[$a['pay_key']] ?? 99) <=> (self::STATUS_ORDER[$b['pay_key']] ?? 99),
                default => $byName,
            };
            if ($sort === 'net' && ($a['net_minor'] === null || $b['net_minor'] === null)) {
                return $cmp !== 0 ? $cmp : $byName;
            }

            return $cmp !== 0 ? $sign * $cmp : $byName;
        });

        return $rows;
    }

    /**
     * The owner's pay status for the latest closed statement, and why — in the owner's terms.
     *
     * @param  Collection<int, Payout>  $payouts
     * @return array{0: string, 1: string, 2: ?string}
     */
    private function status(Owner $owner, ?OwnerStatement $latest, Collection $payouts, bool $hasMethod, bool $paused, ?OwnerInvite $invite): array
    {
        if ($owner->status !== 'active') {
            return $invite !== null && $invite->status === 'pending'
                ? ['invited', __('php/support/owner/owner_rows.pay_invited'), $invite->expires_at !== null
                    ? __('php/support/owner/owner_rows.reason_invite_expires', ['date' => $invite->expires_at->translatedFormat(LocalDate::pattern('j M'))])
                    : __('php/support/owner/owner_rows.reason_invite_sent')]
                : ['inactive', __('php/support/owner/owner_rows.pay_not_activated'), $invite !== null ? __('php/support/owner/owner_rows.reason_invite_expired') : null];
        }
        if ($latest === null) {
            return ['none', __('php/support/owner/owner_rows.pay_no_statement'), null];
        }

        $statementPayouts = $payouts->where('owner_statement_id', $latest->getKey());
        if ((string) $latest->status === 'paid' || $statementPayouts->contains(fn (Payout $p): bool => (string) $p->status === 'paid')) {
            return ['paid', __('php/support/owner/owner_rows.pay_paid'), null];
        }
        if ($statementPayouts->contains(fn (Payout $p): bool => (string) $p->status === 'held')) {
            return ['hold', __('php/support/owner/owner_rows.pay_on_hold'), __('php/support/owner/owner_rows.reason_payout_held')];
        }

        $net = (int) $latest->net_payable_minor;
        if ($net > 0 && (! $owner->hasTaxDetails() || ! $hasMethod)) {
            $reason = match (true) {
                ! $owner->hasTaxDetails() && ! $hasMethod => __('php/support/owner/owner_rows.reason_no_tax_no_account'),
                ! $owner->hasTaxDetails() => __('php/support/owner/owner_rows.reason_no_tax'),
                default => __('php/support/owner/owner_rows.reason_no_account'),
            };

            return ['hold', __('php/support/owner/owner_rows.pay_on_hold'), $reason];
        }
        if ((bool) $latest->has_unverified) {
            return ['draft', __('php/support/owner/owner_rows.pay_needs_review'), __('php/support/owner/owner_rows.reason_unverified_lines')];
        }
        if ($net > 0 && $paused) {
            // DEC-n (2026-10-02) — a pause stops every payout, by the automatic run or by hand
            return ['paused', __('php/support/owner/owner_rows.pay_paused'), __('php/support/owner/owner_rows.reason_paused_until_resumed')];
        }
        if ($net > 0) {
            return ['due', __('php/support/owner/owner_rows.pay_ready'), null];
        }

        return ['zero', __('php/support/owner/owner_rows.pay_nothing_due'), null];
    }

    /** Up to two initials for a name — the rows and the invites waiting draw their avatars from this one rule. */
    public static function initials(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $letters = array_map(fn (string $p): string => LocalText::upper(mb_substr($p, 0, 1)), array_slice($parts, 0, 2));

        return implode('', $letters) ?: '?';
    }
}
