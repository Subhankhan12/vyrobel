@props(['message', 'outcome' => 'ok'])

{{--
    ⚠️⚠️ THE OWNER REALM'S HALF OF ONE CONTRACT — the twin of <x-operator.flash-notice>.

    ⚠️ WHY A SECOND FILE AND NOT THE OPERATOR COMPONENT (§N.8f asks, so this answers).
    The two realms are DIFFERENT DESIGN SYSTEMS, not two opinions about truth: the operator shell styles
    `.vy-toast` (components/layouts/operator.blade.php), the owner shell styles `.vy-flash` with `.is-ok` /
    `.is-err` (components/layouts/owner.blade.php:701-703). Rendering the operator component inside
    <x-owner.shell> would emit `vy-toast` markup that NOTHING IN THIS SHELL STYLES — an unstyled block, not
    a shared fix. ⚠️ This repo has already been bitten by exactly that: a bell partial emitted `.vy-sr-only`
    that was styled in one shell only, and an undefined `.vy-sr-only` hides nothing at all.
    ⚠️ WHAT IS SHARED IS THE SEMANTIC CONTRACT, and it is TESTED rather than trusted:
    `PayoutRetryOutcomeTest` asserts both components answer a refusal the same way — alert triangle,
    `role="alert"`, a visually-hidden prefix, focus taken. That test is what stops the two skins drifting.

    ⚠️⚠️ THREE OUTCOMES, NOT TWO — AND THAT IS THE POINT OF THIS PASS.
    The brief called four of five payout-retry messages "refusals". Read against the controller, they are
    not. Painting all four red would be a NEW honesty defect: telling an owner whose money is already on
    its way that something failed.
      · `ok`      — it happened. "We've queued this payout for another attempt."
      · `neutral` — nothing to do, and nothing lost: the payout left the failed state, or a replacement is
                    ALREADY ON ITS WAY. No tick (it did not succeed) and no alarm (nothing is wrong).
      · `refused` — ⚠️ THE OWNER MUST ACT OR NO MONEY ARRIVES. Today that is exactly one message:
                    "Please finish setting up your payout account first." An INSTRUCTION rendered as a
                    COMPLETION is the worst of the three, because a green tick tells the reader to do
                    nothing — and the thing they must do is the only thing that releases their money.

    ⚠️ NOT COLOUR ALONE: the glyph, the accessible prefix and the ARIA role all change with the outcome.
    ⚠️ `role="status"` PRESENT AT PAGE LOAD NEVER ANNOUNCES — a live region speaks only when its content
    changes while observed, and a server-rendered flash is already there. So `refused` is `role="alert"`
    AND takes focus; that is what actually reaches a screen-reader user. Neutral and ok do not steal focus.
--}}

@if ($outcome === 'refused')
    <div class="vy-flash is-err" style="margin-bottom:18px" role="alert" tabindex="-1"
         x-data x-init="$nextTick(() => $el.focus())">
        {{-- Alert triangle — a different SHAPE from the tick, not a recoloured one. --}}
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
            <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/>
            <path d="M12 9v4"/><path d="M12 17h.01"/>
        </svg>
        <span class="vy-sr-only">Action needed: </span>{{ $message }}
    </div>
@elseif ($outcome === 'neutral')
    {{-- No modifier: the shell's plain `.vy-flash`, already used elsewhere in this realm. No icon at all —
         a tick would claim a success that did not happen, and a warning would invent a problem. --}}
    <div class="vy-flash" style="margin-bottom:18px" role="status">
        <span class="vy-sr-only">No change: </span>{{ $message }}
    </div>
@else
    <div class="vy-flash is-ok" style="margin-bottom:18px" role="status">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
            <path d="M20 6 9 17l-5-5"/>
        </svg>
        {{ $message }}
    </div>
@endif
