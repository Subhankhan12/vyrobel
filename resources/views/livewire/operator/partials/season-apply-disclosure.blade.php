{{-- THE SEASON APPLY DISCLOSURE — what applying a season writes, what it cannot undo, and what it replaces that somebody
     negotiated (R-RATE: it tells; it does not ask). ⚠️ ONE copy, read by every page that offers Apply: the Rates page's dialog
     and, since 2026-10-04, the New reservation page's (whose first Apply posted straight away, with none of this said — the
     independent review's H2). It reads Alpine's `confirmSeason` — the object built from SeasonalRateService::preview(). --}}
{{-- I18N-01: each sentence is ONE key; the values Alpine fills in are passed as static <span x-text> markup (Js::from for the
     words inside them), so the wording and what it says are unchanged and a language can order it as its grammar needs. --}}
@php
    // The three counted phrases are built on the server in the page language's real plural for the season's own counts
    // (SeasonApplyFacts — units_text, agreed_carry_text, agreed_owners_text; I18N-01 rule 12), never chosen here by `=== 1`.
    $__sadUnitsHtml = '<span x-text="confirmSeason?.units_text"></span>';
    $__sadCarryHtml = '<span x-text="confirmSeason?.agreed_carry_text"></span>';
    $__sadOwnersHtml = '<span x-text="confirmSeason?.agreed_owners_text"></span>';
@endphp
<p>
    {!! __('livewire/operator/partials/season-apply-disclosure.will_overwrite', [
        'season' => '<span x-text="confirmSeason?.name"></span>',
        'changing' => '<span x-text="confirmSeason?.changing"></span>',
        'nights' => '<span x-text="confirmSeason?.nights"></span>',
        'units' => $__sadUnitsHtml,
        'from' => '<span x-text="confirmSeason?.from_display ?? \'—\'"></span>',
        'to' => '<strong x-text="confirmSeason?.to_display"></strong>',
    ]) !!}
</p>
{{-- ⚠️⚠️ TRUE, AND VERIFIED BEFORE IT WAS WRITTEN: `setRate()` records an audit row whose `before` is
     null, so the prices it replaces are held nowhere. There is no undo to offer. --}}
<p>{!! __('livewire/operator/partials/season-apply-disclosure.cannot_undo') !!}</p>
{{-- ⚠️⚠️ WHAT IT WOULD REPLACE THAT SOMEBODY NEGOTIATED. This TELLS; it does not ask (R-RATE). --}}
<template x-if="confirmSeason?.agreed_nights > 0">
    <p class="vyr-callout">
        {!! __('livewire/operator/partials/season-apply-disclosure.agreed_callout', [
            'agreed' => '<strong><span x-text="confirmSeason?.agreed_nights"></span></strong>',
            'carry' => $__sadCarryHtml,
            'owners' => $__sadOwnersHtml,
            'from' => '<span x-text="confirmSeason?.agreed_from"></span>',
            'to' => '<span x-text="confirmSeason?.agreed_to"></span>',
        ]) !!}
    </p>
</template>
<template x-if="confirmSeason?.losing_nights > 0">
    <p>{!! __('livewire/operator/partials/season-apply-disclosure.losing_nights', ['nights' => '<span x-text="confirmSeason?.losing_nights"></span>', 'season' => '<span x-text="confirmSeason?.losing_to"></span>']) !!}</p>
</template>
<template x-if="confirmSeason?.rewritten_nights > 0">
    <p>{!! __('livewire/operator/partials/season-apply-disclosure.rewritten_nights', ['nights' => '<span x-text="confirmSeason?.rewritten_nights"></span>']) !!}</p>
</template>
{{-- ⚠️⚠️ F-21 — THE DISCLOSURE STATES ITS OWN SCOPE, UNCONDITIONALLY (hidden at zero, "0 nights carry a
     negotiated rate" would read as "nothing is at risk"). --}}
<p class="vyr-muted">{{ __('livewire/operator/partials/season-apply-disclosure.count_scope') }}</p>
<p class="vyr-muted">{{ __('livewire/operator/partials/season-apply-disclosure.booked_keep_price') }}</p>
