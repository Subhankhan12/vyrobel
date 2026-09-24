{{-- ADMIN 01 · PHASE 1 — THE "NOTHING IS SILENT" HEALTH BOARD: the first screen after sign-in.

     Four states per card, never three: GREEN · AMBER · RED · GREY ("could not measure" — never folded into green).
     ⚠️ Calibrated on known truths: on the day it shipped, mail went to `log` (red), no external heartbeat URL was
     set (amber), and the live release was readable from `.release`. A board that showed all green that day would
     have been broken. ⚠️ No card shows a secret or a config value — names only. ⚠️ Nothing here can act. --}}
@php
    $counts = collect($cards)->countBy(fn ($c) => $c->status);
@endphp
<x-admin.shell active="health" title="Health"
    subtitle="Is anything failing quietly? Every card is measured on this page load — grey means it could not look.">

    <div class="ad-health-summary">
        @foreach (['red' => 'problem', 'amber' => 'attention', 'grey' => 'could not measure', 'green' => 'ok'] as $status => $label)
            <span class="ad-health-count ad-health-count--{{ $status }}">
                <span class="ad-dot ad-dot--{{ $status }}" aria-hidden="true"></span>
                {{ $counts[$status] ?? 0 }} {{ $label }}
            </span>
        @endforeach
    </div>

    <div class="ad-health">
        @foreach ($cards as $card)
            <section class="ad-hcard ad-hcard--{{ $card->status }}" aria-labelledby="hc-{{ $card->key }}">
                <div class="ad-hcard-head">
                    <h2 class="ad-hcard-title" id="hc-{{ $card->key }}">{{ $card->title }}</h2>
                    <span class="ad-hpill ad-hpill--{{ $card->status }}">{{ $card->statusLabel() }}</span>
                </div>
                <div class="ad-hcard-headline">{{ $card->headline }}</div>
                @if ($card->lines !== [])
                    <ul class="ad-hcard-lines">
                        @foreach ($card->lines as $line)
                            <li>{{ $line }}</li>
                        @endforeach
                    </ul>
                @endif
                @if ($card->note)
                    <p class="ad-hcard-note">{{ $card->note }}</p>
                @endif
            </section>
        @endforeach
    </div>
</x-admin.shell>
