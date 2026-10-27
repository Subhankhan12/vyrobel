{{-- Guest Add-ons — "Enhance your stay" (Guest Portal · GATE 2). The REAL org+property-scoped AddOn
     catalogue (never the wireframe's invented 6), priced per ReservationExtras basis and shown VAT-inclusive
     so it === the true folio delta on confirmation (#17). WRITE MODEL (D4): "Add to booking" posts the chosen
     REAL AddOn ids to the certified add-on REQUEST path — the operator confirms and the folio posts; nothing
     settles until then. Guest-scoped. RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-guest.shell active="trips" :title="__('guest/extras.page_title', ['reference' => $reference])">
    <style>
        .ex-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 14px; }
        .ex-item { border: 1px solid var(--vy-glass-border); border-radius: var(--vy-r-lg); background: var(--vy-card); padding: 15px 16px; display: flex; flex-direction: column; gap: 4px; transition: border-color .15s, box-shadow .15s; }
        .ex-item.is-on { border-color: var(--vy-accent-3); box-shadow: var(--vy-shadow-accent); }
        .ex-name { font-family: var(--vy-display); font-weight: 500; font-size: 14.5px; }
        .ex-basis { font-size: 11.5px; color: var(--vy-faint); }
        .ex-price { font-family: var(--vy-display); font-weight: 600; font-size: 15px; color:var(--vy-accent-text); margin-top: 6px; }
        .ex-add { margin-top: 10px; display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 8px 0; border-radius: var(--vy-r-pill, 30px); border: 1px solid var(--vy-accent-3); background: var(--vy-accent); color: #fff; font-family: var(--vy-sans); font-size: 12.5px; font-weight: 500; cursor: pointer; }
        .ex-item.is-on .ex-add { background: rgba(217,102,46,.13); color: var(--vy-accent-text); }
        .ex-cbx { position: absolute; opacity: 0; pointer-events: none; }
        /* The checkbox is opacity:0 (correctly still in the tab order, unlike G4's display:none file input),
           but an invisible input shows an invisible focus ring — so the CARD carries it instead. Without
           this a keyboard guest can tab through every extra with no idea which one is focused. */
        .ex-item:focus-within { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
        .ex-summary { margin-top: 16px; border: 1px solid var(--vy-glass-border); border-radius: var(--vy-r-lg); background: var(--vy-card); padding: 16px 18px; }
        .ex-row { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; padding: 8px 0; font-size: 13px; border-top: 1px solid var(--vy-hairline); }
        .ex-total { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; margin-top: 8px; padding-top: 12px; border-top: 1.5px solid var(--vy-hairline); }
        .ex-total b { font-family: var(--vy-display); font-size: 20px; color:var(--vy-accent-text); }
    </style>

    <a class="vy-link" href="{{ route('guest.reservation', $reservationId) }}" style="font-size:12.5px;color:var(--vy-accent-text);text-decoration:none">{{ __('guest/extras.back_to_booking') }}</a>

    <div class="vy-eyebrow" style="margin-top:12px;color:var(--vy-green-text)">{{ __('guest/extras.nothing_to_pay_now') }}</div>
    <h1 class="vy-h1" style="margin-top:4px">{{ __('guest/extras.heading') }}</h1>
    <p class="vy-lead" style="margin-bottom:16px">{{ __('guest/extras.lead', ['property' => $property]) }}</p>

    @if (session('status'))
        <div class="vy-card" style="margin-bottom:14px;border-left:3px solid var(--vy-accent-3);font-size:13px;color:var(--vy-ink)">{{ session('status') }}</div>
    @endif

    {{-- Already requested / confirmed --}}
    @if (count($pending) > 0)
        <section class="vy-card" style="margin-bottom:14px">
            <div class="vy-eyebrow" style="margin-bottom:8px">{{ __('guest/extras.your_extras') }}</div>
            @foreach ($pending as $req)
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:8px 0;border-top:1px solid var(--vy-hairline);font-size:13px">
                    <span style="color:var(--vy-ink)">{{ implode(', ', $req['names']) ?: __('guest/extras.extras_fallback') }}</span>
                    <span style="flex:none;font-size:11px;font-weight:600;padding:2px 9px;border-radius:20px;{{ $req['status'] === 'confirmed' ? 'background:rgba(79,122,70,.13);color:#3F6338' : 'background:var(--vy-wash);color:var(--vy-amber-text)' }}">{{ $req['status'] === 'confirmed' ? __('guest/extras.added_to_booking') : __('guest/extras.awaiting_confirmation') }}</span>
                </div>
            @endforeach
        </section>
    @endif

    @if (count($catalog) === 0)
        <div class="vy-card"><div style="font-size:13px;color:var(--vy-muted);text-align:center;padding:20px">{{ __('guest/extras.empty_state') }}</div></div>
    @else
        <form method="POST" action="{{ route('guest.manage.add-ons', $reservationId) }}">
            @csrf
            <section class="vy-card">
                <div style="display:flex;align-items:baseline;justify-content:space-between;gap:12px;margin-bottom:12px">
                    <div class="vy-eyebrow">{{ __('guest/extras.available_extras') }}</div>
                    @if ($vatRate > 0)<div style="font-size:11.5px;color:var(--vy-faint)">{{ __('guest/extras.prices_include_vat') }}</div>@endif
                </div>
                <div class="ex-grid">
                    @foreach ($catalog as $item)
                        <label class="ex-item" data-minor="{{ $item['total_minor'] }}">
                            <input class="ex-cbx" type="checkbox" name="add_ons[]" value="{{ $item['id'] }}" onchange="exUpdate(this)">
                            <span class="ex-name">{{ $item['name'] }}</span>
                            {{-- WAVE 2 · #14 — the basis describes the UNIT price, so show the unit price next
                                 to it. This card printed the basis ("per guest · night") immediately above the
                                 STAY TOTAL, so a guest read €132.84 as a nightly per-person rate. The unit
                                 price was already computed by the controller and simply never rendered. --}}
                            <span class="ex-basis">{{ $item['unit_price'] }} {{ $item['basis'] }}</span>
                            <span class="ex-price">{{ $item['total'] }}</span>
                            <span class="ex-basis">{{ __('guest/extras.total_for_stay') }}</span>
                            <span class="ex-add">{{ __('guest/extras.add_to_stay') }}</span>
                        </label>
                    @endforeach
                </div>

                <div class="ex-summary">
                    <div style="font-family:var(--vy-display);font-weight:500;font-size:15px">{{ __('guest/extras.your_extras') }}</div>
                    <div class="ex-total">
                        <span><span style="font-family:var(--vy-display);font-weight:500">{{ __('common.total') }}</span> <span style="font-size:11px;color:var(--vy-faint)">{{ $vatRate > 0 ? __('guest/extras.incl_vat') : '' }}</span></span>
                        <b id="ex-total-val">{{ \App\Support\Money\MoneyFormatter::display(0, $currency) }}</b>
                    </div>
                    <button type="submit" class="vy-btn vy-btn--accent" id="ex-submit" style="width:100%;margin-top:14px;opacity:.5" disabled>{{ __('guest/extras.add_to_booking_button') }}</button>
                    {{-- ⚠️ "Free to cancel up to 24h before arrival" was removed here (#39). It came verbatim from the
                         prototype, but NOTHING implements it: there is no add-on cancellation window in AddOn,
                         ReservationExtras or config, and no removal path at all — the only extras route is a GET,
                         and an add-on can only be REQUESTED. The app was promising the guest a right it cannot
                         honour. Same family as the "48h" literal fixed at G2, except that one at least matched a
                         real constant; this one had no engine behind it. --}}
                    <div style="font-size:11px;color:var(--vy-faint);text-align:center;margin-top:10px;line-height:1.5">{{ __('guest/extras.settles_to', ['reference' => $reference]) }}</div>
                </div>
            </section>
        </form>

        <script>
            var exCur = @json($currency);
            function exFmt(minor) { return (exCur === 'EUR' ? '€' : exCur + ' ') + (minor / 100).toFixed(2); }
            function exUpdate(cb) {
                cb.closest('.ex-item').classList.toggle('is-on', cb.checked);
                var total = 0, any = false;
                document.querySelectorAll('.ex-item').forEach(function (el) {
                    var box = el.querySelector('input');
                    if (box.checked) { total += parseInt(el.getAttribute('data-minor'), 10) || 0; any = true; }
                });
                document.getElementById('ex-total-val').textContent = exFmt(total);
                var btn = document.getElementById('ex-submit');
                btn.disabled = !any;
                btn.style.opacity = any ? '1' : '.5';
                document.querySelectorAll('.ex-item .ex-add').forEach(function (a) {
                    a.textContent = a.closest('.ex-item').querySelector('input').checked ? @js(__('guest/extras.added')) : @js(__('guest/extras.add_to_stay'));
                });
            }
        </script>
    @endif
</x-guest.shell>
