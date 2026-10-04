{{-- THE SEASON APPLY DISCLOSURE — what applying a season writes, what it cannot undo, and what it replaces that somebody
     negotiated (R-RATE: it tells; it does not ask). ⚠️ ONE copy, read by every page that offers Apply: the Rates page's dialog
     and, since 2026-10-04, the New reservation page's (whose first Apply posted straight away, with none of this said — the
     independent review's H2). It reads Alpine's `confirmSeason` — the object built from SeasonalRateService::preview(). --}}
<p>
    <span x-text="confirmSeason?.name"></span> will overwrite
    <span x-text="confirmSeason?.changing"></span> of
    <span x-text="confirmSeason?.nights"></span> nights across
    <span x-text="confirmSeason?.units === 1 ? '1 unit' : confirmSeason?.units + ' units'"></span>, from
    <span x-text="confirmSeason?.from_display ?? '—'"></span> to
    <strong x-text="confirmSeason?.to_display"></strong> a night.
</p>
{{-- ⚠️⚠️ TRUE, AND VERIFIED BEFORE IT WAS WRITTEN: `setRate()` records an audit row whose `before` is
     null, so the prices it replaces are held nowhere. There is no undo to offer. --}}
<p>The prices it replaces are not stored anywhere. <strong>This cannot be undone.</strong></p>
{{-- ⚠️⚠️ WHAT IT WOULD REPLACE THAT SOMEBODY NEGOTIATED. This TELLS; it does not ask (R-RATE). --}}
<template x-if="confirmSeason?.agreed_nights > 0">
    <p class="vyr-callout">
        <strong><span x-text="confirmSeason?.agreed_nights"></span></strong>
        <span x-text="confirmSeason?.agreed_nights === 1 ? 'of those nights carries' : 'of those nights carry'"></span>
        a rate negotiated with
        <span x-text="confirmSeason?.agreed_owners === 1 ? 'an owner' : confirmSeason?.agreed_owners + ' owners'"></span>
        (<span x-text="confirmSeason?.agreed_from"></span> to <span x-text="confirmSeason?.agreed_to"></span>).
        Applying replaces those prices, and the owner is not told.
    </p>
</template>
<template x-if="confirmSeason?.losing_nights > 0">
    <p><span x-text="confirmSeason?.losing_nights"></span> of those nights are governed by <span x-text="confirmSeason?.losing_to"></span>, which wins under the rule and will be written last.</p>
</template>
<template x-if="confirmSeason?.rewritten_nights > 0">
    <p>Applying also re-writes <span x-text="confirmSeason?.rewritten_nights"></span> nights from overlapping seasons that are already applied.</p>
</template>
{{-- ⚠️⚠️ F-21 — THE DISCLOSURE STATES ITS OWN SCOPE, UNCONDITIONALLY (hidden at zero, "0 nights carry a
     negotiated rate" would read as "nothing is at risk"). --}}
<p class="vyr-muted">That count sees only rates agreed through a proposal, and only while the price still matches what was agreed. A rate an owner set themselves, or one that has since moved, is not counted here.</p>
<p class="vyr-muted">Stays already booked keep the price they were booked at — this changes what is offered from now on.</p>
