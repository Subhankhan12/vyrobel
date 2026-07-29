<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\OwnerAttentionState;
use App\Support\Events\AuditEventRecorder;
use App\Support\Owner\OwnerAttention;
use App\Support\Owner\OwnerContext;
use App\Support\Owner\OwnerTeamRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Owner ATTENTION-state writes (Wave A · GATE 1) — snooze / dismiss / mark-read / undo an item on the shared
 * "Needs your attention" feed. Controller POSTs only (never a Livewire action), CAP_ACTIONS-gated on the route
 * (co-owner — a view-only sub-user may SEE the feed but not act on it), and OWNER-SCOPED: the posted key is
 * resolved back to a REAL current signal via {@see OwnerAttention::signalFor()} (so you can only ever act on a
 * genuine item, and the state row is written for that signal's own owner_id). Every write is audited.
 */
class OwnerAttentionController extends Controller
{
    public function __construct(private readonly AuditEventRecorder $audit) {}

    /** Snooze an item until tomorrow / next week / a chosen date — it hides on both surfaces until then. */
    public function snooze(Request $request, OwnerContext $context): RedirectResponse
    {
        $data = $request->validate([
            'attention_key' => ['required', 'string', 'max:120'],
            'duration' => ['required', Rule::in(['tomorrow', 'week', 'custom'])],
            'until' => ['nullable', 'date', 'after:today'],
        ]);

        $signal = OwnerAttention::signalFor($context, (string) $data['attention_key']);
        if ($signal === null) {
            return back()->with('owner_notice', 'That item is no longer active.');
        }

        $until = match ($data['duration']) {
            'tomorrow' => Carbon::tomorrow(),
            'week' => Carbon::now()->addWeek(),
            default => isset($data['until']) ? Carbon::parse((string) $data['until'])->endOfDay() : Carbon::now()->addWeek(),
        };

        $this->apply($signal, ['snoozed_until' => $until, 'dismissed_at' => null], 'owner.attention_snoozed');

        return back()->with('owner_notice', 'Snoozed — back '.$until->diffForHumans().'.');
    }

    /** Dismiss an item — it stays hidden until the owner restores it (Undo). */
    public function dismiss(Request $request, OwnerContext $context): RedirectResponse
    {
        $signal = $this->resolve($request, $context);
        if ($signal === null) {
            return back()->with('owner_notice', 'That item is no longer active.');
        }

        $this->apply($signal, ['dismissed_at' => Carbon::now(), 'snoozed_until' => null], 'owner.attention_dismissed');

        return back()->with('owner_notice', 'Dismissed.');
    }

    /** Mark an item read (it stays visible) — a light acknowledgement. */
    public function read(Request $request, OwnerContext $context): RedirectResponse
    {
        $signal = $this->resolve($request, $context);
        if ($signal !== null) {
            $this->apply($signal, ['read_at' => Carbon::now()], 'owner.attention_read');
        }

        return back();
    }

    /** UNDO — restore a snoozed/dismissed item to the visible feed. */
    public function restore(Request $request, OwnerContext $context): RedirectResponse
    {
        $signal = $this->resolve($request, $context);
        if ($signal !== null) {
            $this->apply($signal, ['snoozed_until' => null, 'dismissed_at' => null], 'owner.attention_restored');
        }

        return back()->with('owner_notice', 'Restored to your attention list.');
    }

    /** BULK — mark EVERY currently-raised item read (Wave B · 💡). Only ever touches genuine current signals. */
    public function readAll(OwnerContext $context): RedirectResponse
    {
        $signals = $this->actionableSignals($context);
        foreach ($signals as $signal) {
            $this->apply($signal, ['read_at' => Carbon::now()], 'owner.attention_read');
        }

        return back()->with('owner_notice', count($signals) === 0 ? 'Nothing to mark read.' : 'Marked all read.');
    }

    /** BULK — snooze EVERY currently-raised item for a week (Wave B · 💡). Genuine current signals only. */
    public function snoozeAll(OwnerContext $context): RedirectResponse
    {
        $until = Carbon::now()->addWeek();
        $signals = $this->actionableSignals($context);
        foreach ($signals as $signal) {
            $this->apply($signal, ['snoozed_until' => $until, 'dismissed_at' => null], 'owner.attention_snoozed');
        }

        return back()->with('owner_notice', count($signals) === 0 ? 'Nothing to snooze.' : 'Snoozed everything — back in a week.');
    }

    /**
     * 🔒 P1-1: the current signals whose owner the acting user has CAP_ACTIONS on — bulk actions silently skip an
     * owner the user is only a lesser role on (per-owner, never the union). The single-item paths 403 instead.
     *
     * @return list<array{owner_id: string, key: string}>
     */
    private function actionableSignals(OwnerContext $context): array
    {
        return array_values(array_filter(
            OwnerAttention::currentSignals($context),
            fn (array $signal): bool => $context->canOn((string) $signal['owner_id'], OwnerTeamRole::CAP_ACTIONS),
        ));
    }

    /**
     * @return array{owner_id: string, key: string}|null
     */
    private function resolve(Request $request, OwnerContext $context): ?array
    {
        $key = (string) $request->validate(['attention_key' => ['required', 'string', 'max:120']])['attention_key'];

        return OwnerAttention::signalFor($context, $key);
    }

    /**
     * Upsert the state row for the resolved signal (owner-scoped by its own owner_id) + audit. organization_id
     * is auto-filled by BelongsToOrganization on create.
     *
     * @param  array{owner_id: string, key: string}  $signal
     * @param  array<string, mixed>  $attrs
     */
    private function apply(array $signal, array $attrs, string $event): void
    {
        // 🔒 P1-1: acting on an attention item is CAP_ACTIONS on THAT signal's owner (per-owner, not the union) —
        // a co-owner of A + view-only of B may not snooze/dismiss/read B's items.
        abort_unless(app(OwnerContext::class)->canOn((string) $signal['owner_id'], OwnerTeamRole::CAP_ACTIONS), 403);

        $state = OwnerAttentionState::updateOrCreate(
            ['owner_id' => $signal['owner_id'], 'attention_key' => $signal['key']],
            $attrs,
        );

        $this->audit->record($event, $state, null, ['attention_key' => $signal['key']]);
    }
}
