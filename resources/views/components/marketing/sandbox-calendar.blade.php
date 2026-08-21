{{-- THE SANDBOX DATE PICKER (front-website Gate 4b — the founder's ruling to restore interactivity).

     ⚠️ EVERY NIGHT'S PRICE IS THE ENGINE'S. Each cell carries what BookingEngineService::priceOver()
     returns for that one night, and each cell's availability is the same spine the storefront sells
     against — so a sold-out night is really sold out and an unpriced night really has no rate.

     ⚠️ IT IS ALL LINKS, ON PURPOSE. There is no JavaScript on this page: picking a date is a GET back to
     /try-booking, and the server re-prices the stay through priceOver(). A client-side picker would have
     had to SUM nightly prices in JavaScript — a second implementation of the engine's own rule, which is
     precisely how two surfaces come to disagree about money (§N.8f). It also means the picker works with
     scripting off, and that the confirmation email below can never show a different stay from the one
     selected above it.

     ⚠️ AND THERE IS NO PROPERTY PARAMETER. The only inputs are two dates and a month cursor; the property
     is a constant in TryBookingSandbox. An endpoint that took a property id would price every customer's
     rates for anyone who asked. --}}
@props(['sandbox'])
@php
    $calendar = $sandbox['calendar'];
    $dayNames = ['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'];
@endphp

<div data-sandbox-calendar style="padding: 22px 24px 24px; border-radius: 22px; background: var(--vy-glass); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid var(--vy-glass-border); box-shadow: var(--vy-shadow-sm);">

    <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px;">
        <div style="font-size: 10.5px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: var(--vy-faint);">Pick your dates</div>
        <div style="display: inline-flex; gap: 6px;">
            @if ($calendar['prevUrl'] !== null)
                <a href="{{ $calendar['prevUrl'] }}" rel="nofollow" aria-label="Earlier months" style="display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 10px; border: 1px solid var(--vy-hairline); background: rgba(255,255,255,.55); color: var(--vy-ink); text-decoration: none;">&lsaquo;</a>
            @else
                <span aria-hidden="true" style="display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 10px; border: 1px solid var(--vy-hairline); color: var(--vy-faint); opacity: .45;">&lsaquo;</span>
            @endif
            @if ($calendar['nextUrl'] !== null)
                <a href="{{ $calendar['nextUrl'] }}" rel="nofollow" aria-label="Later months" style="display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 10px; border: 1px solid var(--vy-hairline); background: rgba(255,255,255,.55); color: var(--vy-ink); text-decoration: none;">&rsaquo;</a>
            @else
                <span aria-hidden="true" style="display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 10px; border: 1px solid var(--vy-hairline); color: var(--vy-faint); opacity: .45;">&rsaquo;</span>
            @endif
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(290px, 1fr)); gap: 28px;">
        @foreach ($calendar['months'] as $month)
            <div>
                <div style="font-family: var(--vy-display); font-weight: 600; font-size: 15px; letter-spacing: -.01em; color: var(--vy-ink); margin-bottom: 10px;">{{ $month['label'] }}</div>
                <div role="grid" aria-label="{{ $month['label'] }}" style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 3px;">
                    @foreach ($dayNames as $dayName)
                        <div role="columnheader" style="text-align: center; font-size: 10px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-faint); padding-bottom: 4px;">{{ $dayName }}</div>
                    @endforeach

                    @foreach ($month['cells'] as $cell)
                        @if ($cell === null)
                            <span aria-hidden="true"></span>
                        @elseif ($cell['selectable'])
                            @php
                                $tone = $cell['isCheckIn'] || $cell['isCheckOut']
                                    ? 'background: var(--vy-grad); color: #fff; border-color: transparent; box-shadow: var(--vy-shadow-accent);'
                                    : ($cell['inStay']
                                        ? 'background: rgba(217,102,46,.16); color: var(--vy-ink); border-color: rgba(217,102,46,.3);'
                                        : 'background: rgba(255,255,255,.5); color: var(--vy-ink); border-color: var(--vy-hairline);');
                            @endphp
                            {{-- ⚠️ THE PRICE IS EMITTED AS THE ENGINE'S OWN MINOR-UNIT INTEGER, not only as a
                                 formatted string, so the guard can compare every visible night against a
                                 fresh priceOver() call rather than against a re-parsed display value. --}}
                            <a href="{{ $cell['href'] }}" rel="nofollow"
                               data-night="{{ $cell['date'] }}" data-night-price-minor="{{ $cell['priceMinor'] }}" data-night-available="{{ $cell['available'] }}"
                               aria-label="{{ $cell['day'] }} {{ $month['label'] }} — {{ $cell['price'] }} a night, {{ $cell['available'] }} {{ \Illuminate\Support\Str::plural('room', $cell['available']) }} free"
                               style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 40px; border-radius: 10px; border: 1px solid; text-decoration: none; font-size: 13px; font-weight: 600; line-height: 1.1; {{ $tone }}">
                                {{ $cell['day'] }}
                                <span style="font-size: 9px; font-weight: 500; opacity: .72; font-variant-numeric: tabular-nums;">{{ $cell['price'] }}</span>
                            </a>
                        @else
                            {{-- ⚠️ NOT SELECTABLE, AND THE REASON IS REAL: the night is in the past, has no
                                 published rate, or has no room free. Never a decorative disabled state. --}}
                            <span aria-disabled="true"
                                  data-night="{{ $cell['date'] }}" data-night-price-minor="{{ $cell['priceMinor'] }}" data-night-available="{{ $cell['available'] }}"
                                  @if (! $cell['past'])
                                      title="{{ $cell['priceMinor'] === null ? 'No published rate for this night' : 'No room free on this night' }}"
                                  @endif
                                  style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 40px; border-radius: 10px; border: 1px dashed var(--vy-hairline); font-size: 13px; font-weight: 500; line-height: 1.1; color: var(--vy-faint); opacity: {{ $cell['past'] ? '.35' : '.7' }};">
                                {{ $cell['day'] }}
                                @if (! $cell['past'] && $cell['priceMinor'] !== null && $cell['available'] < 1)
                                    <span style="font-size: 8.5px; font-weight: 500; letter-spacing: .04em; text-transform: uppercase;">Booked</span>
                                @endif
                            </span>
                        @endif
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 14px; margin-top: 16px; padding-top: 14px; border-top: 1px solid var(--vy-hairline); font-size: 11.5px; color: var(--vy-muted);">
        <span style="display: inline-flex; align-items: center; gap: 6px;"><span aria-hidden="true" style="width: 12px; height: 12px; border-radius: 4px; background: var(--vy-grad);"></span>Your stay</span>
        <span style="display: inline-flex; align-items: center; gap: 6px;"><span aria-hidden="true" style="width: 12px; height: 12px; border-radius: 4px; border: 1px dashed var(--vy-hairline);"></span>No room free, or no rate published</span>
        <span style="color: var(--vy-faint);">Up to {{ $sandbox['maxNights'] }} nights &middot; rates published to {{ $sandbox['lastNight']->format('j M Y') }}</span>
        @if ($sandbox['picked'] || $sandbox['status'] === 'partial')
            <a href="{{ $sandbox['clearUrl'] }}" rel="nofollow" style="margin-left: auto; color: var(--vy-accent-text); font-weight: 600; text-decoration: none;">Clear dates</a>
        @endif
    </div>
</div>
