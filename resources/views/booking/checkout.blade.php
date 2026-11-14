{{-- Public checkout — review + guest details (design pass · Guest Checkout wireframe). PUBLIC,
     server-rendered. The price is REAL (certified search path) and reconciles to the folio to the
     cent (#17). NO inventory is written here — the booking happens on the CSRF-protected POST, which
     routes through AssignmentService + FolioService. Honesty (audit D4): NO raw PAN / on-page Apple
     Pay (SAQ A → the certified hosted Stripe redirect); NO "won't be charged until check-in". The
     wallet-credit toggle is REAL (D3) — a signed-in guest's spendable balance, capped at what's owed,
     genuinely deducted on submit; a tiny inline preview (guest realm has no Alpine) reconciles with it. --}}
@php
    $back = http_build_query(array_filter([
        'check_in' => $search['check_in'], 'check_out' => $search['check_out'],
        'adults' => $search['adults'], 'children' => $search['children'],
    ]));
    // The wired rail (founder decision D1): 'stripe' → test-mode hosted Checkout; else pay-on-arrival.
    $payByCard = (string) config('services.payments.gateway') === 'stripe';
    $fmtDate = fn (?string $d): string => $d ? \Illuminate\Support\Carbon::parse($d)->translatedFormat(\App\Support\I18n\LocalDate::pattern('D j M')) : '';
    $guests = (int) $search['adults'] + (int) $search['children'];
@endphp
<x-public.shell :property="$property" :title="__('booking/checkout.page_title')" :seo="$seo">
    <x-slot:head>
        <style>
            .vy-co-h1 { margin: 0 0 22px; font-family: var(--vy-display); font-weight: 600; font-size: 30px; letter-spacing: -0.02em; }
            .vy-co-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 18px; align-items: start; }
            .vy-co-col { display: flex; flex-direction: column; gap: 18px; min-width: 0; }
            .vy-co-card { background: rgba(255, 252, 248, 0.6); backdrop-filter: blur(22px) saturate(1.15); border: 1px solid var(--vy-glass-border); border-radius: 20px; box-shadow: 0 14px 40px rgba(150, 90, 50, 0.14), inset 0 1px 0 rgba(255, 255, 255, 0.65); padding: 22px; }
            .vy-co-h2 { margin: 0 0 14px; font-family: var(--vy-display); font-weight: 500; font-size: 16px; }
            .vy-co-fl { font-size: 10.5px; font-weight: 500; letter-spacing: 0.08em; text-transform: uppercase; color: #9c9089; margin-bottom: 6px; }
            /* ⚠️ Shown ONLY when a field was rejected; nothing renders in the normal case. */
            .vy-co-err { margin: 6px 0 0; font-size: 12px; line-height: 1.45; color: #8F2F09; }
            .vy-co-in { width: 100%; box-sizing: border-box; border: 1px solid rgba(120, 70, 40, 0.14); background: rgba(255, 255, 255, 0.75); border-radius: 13px; padding: 11px 14px; font-family: var(--vy-sans); font-size: 13.5px; color: var(--vy-ink); outline: none; }
            .vy-co-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
            .vy-co-secure { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; color: var(--vy-muted); font-weight: 500; }

            .vy-co-pay-brands { display: inline-flex; gap: 5px; }
            .vy-co-pay-brands span { width: 30px; height: 20px; border-radius: 4px; }
            .vy-co-note { font-size: 11.5px; color: var(--vy-muted); margin-top: 12px; display: flex; align-items: flex-start; gap: 6px; line-height: 1.45; }

            .vy-co-hero { display: flex; gap: 13px; align-items: center; padding-bottom: 16px; border-bottom: 1px solid rgba(120, 70, 40, 0.12); }
            .vy-co-hero-ic { width: 62px; height: 62px; border-radius: 14px; background: linear-gradient(140deg, #f3b98e, #e8875a); display: grid; place-items: center; color: rgba(255, 255, 255, 0.9); flex-shrink: 0; }
            .vy-co-hero-name { font-family: var(--vy-display); font-weight: 500; font-size: 16px; }
            .vy-co-hero-sub { font-size: 12px; color: var(--vy-faint); margin-top: 3px; }
            .vy-co-rows { display: flex; flex-direction: column; gap: 9px; padding: 16px 0; border-bottom: 1px solid rgba(120, 70, 40, 0.12); }
            .vy-co-row { display: flex; justify-content: space-between; font-size: 12.5px; }
            .vy-co-row .k { color: var(--vy-muted); }
            .vy-co-row .v { font-weight: 500; font-variant-numeric: tabular-nums; }
            .vy-co-lines { display: flex; flex-direction: column; gap: 9px; padding: 16px 0; }
            .vy-co-line { display: flex; justify-content: space-between; font-size: 12.5px; }
            .vy-co-line .k { color: var(--vy-muted); }
            .vy-co-line .v { font-variant-numeric: tabular-nums; }

            .vy-co-credit { width: 100%; box-sizing: border-box; display: flex; align-items: center; gap: 11px; text-align: left; padding: 11px 12px; border-radius: 14px; border: 1px solid rgba(120, 70, 40, 0.14); background: rgba(255, 255, 255, 0.5); cursor: pointer; margin: 6px 0; transition: background 0.16s ease, border-color 0.16s ease; }
            .vy-co-credit.on { border-color: rgba(232, 112, 58, 0.5); background: rgba(217, 102, 46, 0.09); }
            .vy-co-credit input { position: absolute; opacity: 0; pointer-events: none; }
            .vy-co-credit-ic { width: 32px; height: 32px; border-radius: 9px; background: rgba(217, 102, 46, 0.12); color: #b4400f; display: grid; place-items: center; flex-shrink: 0; }
            .vy-co-credit-t { flex: 1; min-width: 0; }
            .vy-co-credit-t b { display: block; font-size: 13px; font-weight: 500; color: var(--vy-ink); }
            .vy-co-credit-t span { display: block; font-size: 11.5px; color: var(--vy-muted); margin-top: 1px; }
            .vy-co-switch { position: relative; width: 38px; height: 22px; border-radius: 22px; background: rgba(120, 70, 40, 0.18); flex-shrink: 0; transition: background 0.18s ease; }
            .vy-co-switch::after { content: ""; position: absolute; top: 3px; left: 3px; width: 16px; height: 16px; border-radius: 50%; background: #fff; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.28); transition: left 0.18s ease; }
            .vy-co-credit.on .vy-co-switch { background: var(--vy-grad); }
            .vy-co-credit.on .vy-co-switch::after { left: 19px; }
            .vy-co-credit-line { display: none; justify-content: space-between; font-size: 12.5px; padding: 6px 0 2px; }
            .vy-co-credit.on ~ .vy-co-credit-line { display: flex; }
            .vy-co-credit-line .lbl { color: #3f6338; font-weight: 500; display: inline-flex; align-items: center; gap: 6px; }
            .vy-co-credit-line .amt { color: #3f6338; font-weight: 600; font-variant-numeric: tabular-nums; }

            /* F-GUEST-1 — the real payment-method chooser (shown only when the card rail is wired). */
            .vy-co-pm { display: flex; flex-direction: column; gap: 9px; }
            .vy-co-pm-opt { position: relative; display: flex; align-items: flex-start; gap: 11px; padding: 12px 13px; border-radius: 14px; border: 1px solid rgba(120, 70, 40, 0.14); background: rgba(255, 255, 255, 0.5); cursor: pointer; transition: background 0.16s ease, border-color 0.16s ease; }
            .vy-co-pm-opt.on { border-color: rgba(232, 112, 58, 0.5); background: rgba(217, 102, 46, 0.09); }
            .vy-co-pm-opt input { position: absolute; opacity: 0; pointer-events: none; }
            .vy-co-pm-t { flex: 1; min-width: 0; }
            .vy-co-pm-t b { display: block; font-size: 13px; font-weight: 500; color: var(--vy-ink); }
            .vy-co-pm-t span { display: block; font-size: 11.5px; color: var(--vy-muted); margin-top: 2px; line-height: 1.4; }
            .vy-co-pm-dot { width: 18px; height: 18px; border-radius: 50%; border: 2px solid rgba(120, 70, 40, 0.28); flex-shrink: 0; margin-top: 2px; position: relative; transition: border-color 0.16s ease; }
            .vy-co-pm-opt.on .vy-co-pm-dot { border-color: var(--vy-accent); }
            .vy-co-pm-opt.on .vy-co-pm-dot::after { content: ""; position: absolute; inset: 3px; border-radius: 50%; background: var(--vy-grad); }

            .vy-co-total { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; padding-top: 14px; border-top: 1px solid rgba(120, 70, 40, 0.16); }
            .vy-co-total .lbl { font-family: var(--vy-display); font-weight: 600; font-size: 15px; }
            .vy-co-total .val { font-family: var(--vy-display); font-weight: 600; font-size: 22px; color: var(--vy-accent); letter-spacing: -0.02em; font-variant-numeric: tabular-nums; }
            .vy-co-confirm { width: 100%; margin-top: 16px; padding: 14px 0; border-radius: var(--vy-r-pill); background: var(--vy-grad); border: none; color: #fff; font-family: var(--vy-sans); font-size: 14px; font-weight: 500; cursor: pointer; box-shadow: 0 8px 22px rgba(194, 65, 12, 0.4); }
            .vy-co-saved { display: none; text-align: center; font-size: 11.5px; color: #3f6338; font-weight: 500; margin-top: 11px; align-items: center; justify-content: center; gap: 6px; }
            .vy-co-freecancel { text-align: center; font-size: 11px; color: var(--vy-faint); margin-top: 12px; }

            @media (max-width: 700px) { .vy-co-h1 { font-size: 25px; } }
        </style>
    </x-slot:head>

    <x-slot:nav>
        <span class="vy-co-secure">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="11" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg>
            {{ __('booking/checkout.secure_checkout') }}
        </span>
    </x-slot:nav>

    <a class="vy-back vy-fade" href="{{ route('booking.browse', $property['slug']) }}?{{ $back }}">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
        {{ __('booking/checkout.back_to_rooms') }}
    </a>

    @if (session('booking_error'))
        <div class="vy-error vy-fade" role="alert">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>
            {{ session('booking_error') }}
        </div>
    @endif

    @if ($offer === null)
        <div class="vy-co-card vy-fade">
            <div class="vy-empty">
                {{-- ⚠️⚠️ THE BRANCH THE AUDIT ACTUALLY REACHED. The `@else` below carries the checkout's
                     `<h1>`, but THIS branch — no live offer in session, which is what a visitor gets from a
                     stale link or an expired quote — had no heading element at all. On the page that takes
                     money from a stranger. ⚠️ PROMOTED, not invented: this sentence already IS what the page
                     says. `font:inherit;margin:0` keeps it rendering exactly as the text node did. --}}
                <h1 style="font:inherit;margin:0">{{ __('booking/checkout.unavailable_heading') }}</h1>
                <div style="margin-top:14px;"><a class="vy-btn vy-btn--accent" href="{{ route('booking.browse', $property['slug']) }}?{{ $back }}">{{ __('booking/checkout.choose_another_room') }}</a></div>
            </div>
        </div>
    @else
        <h1 class="vy-co-h1 vy-fade">{{ __('booking/checkout.heading') }}</h1>

        {{-- ONE form spans both columns so the summary's Confirm button + the wallet toggle submit together. --}}
        <form method="POST" action="{{ route('booking.reserve', $property['slug']) }}" data-guard-submit>
            @csrf
            <input type="hidden" name="room_type_id" value="{{ $offer['room_type_id'] }}">
            <input type="hidden" name="rate_plan_id" value="{{ $offer['rate_plan_id'] }}">
            {{-- D18-5 — pick a specific unit. ⚠️ Every option here is genuinely free for these dates (the same
                 read the booking re-validates against), so a choice can never be refused at the lock. Leaving
                 it on "Any" books exactly as this page always did. --}}
            @if (count($unitChoices ?? []) > 1)
                <label class="vy-field" style="display:block;margin:0 0 12px;">
                    <span class="vy-field-label">{{ ($property['type_code'] ?? '') === 'hotel' ? __('booking/checkout.choose_your_room') : __('booking/checkout.choose_your_unit') }}</span>
                    <select name="unit_id" class="vy-input">
                        <option value="">{{ __('booking/checkout.any_available', ['total' => $offer['total']]) }}</option>
                        @foreach ($unitChoices as $vyUnit)
                            <option value="{{ $vyUnit['id'] }}">{{ $vyUnit['label'] }}@if ($vyUnit['price']) — {{ $vyUnit['price'] }}@endif</option>
                        @endforeach
                    </select>
                </label>
            @endif
            <input type="hidden" name="check_in" value="{{ $search['check_in'] }}">
            <input type="hidden" name="check_out" value="{{ $search['check_out'] }}">
            <input type="hidden" name="adults" value="{{ $search['adults'] }}">
            <input type="hidden" name="children" value="{{ $search['children'] }}">

            <div class="vy-co-grid vy-fade">
                {{-- LEFT — your details + payment --}}
                <div class="vy-co-col">
                    <div class="vy-co-card">
                        <h2 class="vy-co-h2">{{ __('booking/checkout.your_details') }}</h2>
                        <div style="display:flex; flex-direction:column; gap:12px;">
                            {{-- ⚠️⚠️ D3 — THE ONLY UNNAMED CONTROLS ON THE PAGE THAT TAKES MONEY FROM STRANGERS.
                                 The label TEXT was always here and always visible; it was carried by a `<div>`,
                                 which associates with nothing. So a sighted guest was fine — nothing vanished on
                                 typing, because these were never placeholders — while a screen-reader user reached
                                 three unnamed edit fields in a row and had to infer them from reading order.
                                 ⚠️ THE FIX IS THE HOUSE PATTERN, NOT A NEW ONE: `<label class="…" for="id">` with a
                                 VISIBLE label is what 131 `vy-field-label` sites and every other form here already
                                 do. The class is unchanged, so the page looks EXACTLY as before.
                                 ⚠️ NO POSTED KEY MOVED — `name=` is untouched on every input; only `id`/`for` were
                                 added, and an `id` is not submitted. --}}
                            <div class="vy-co-2">
                                <div>
                                    <label class="vy-co-fl" for="vy-co-first">{{ __('booking/checkout.first_name') }}</label>
                                    <input class="vy-co-in" type="text" name="first_name" id="vy-co-first"
                                           value="{{ old('first_name') }}" required autocomplete="given-name"
                                           @error('first_name') aria-invalid="true" aria-describedby="vy-co-first-err" @enderror>
                                    @error('first_name')<p class="vy-co-err" id="vy-co-first-err">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="vy-co-fl" for="vy-co-last">{{ __('booking/checkout.last_name') }}</label>
                                    <input class="vy-co-in" type="text" name="last_name" id="vy-co-last"
                                           value="{{ old('last_name') }}" required autocomplete="family-name"
                                           @error('last_name') aria-invalid="true" aria-describedby="vy-co-last-err" @enderror>
                                    @error('last_name')<p class="vy-co-err" id="vy-co-last-err">{{ $message }}</p>@enderror
                                </div>
                            </div>
                            {{-- ⚠️ AND THE ERRORS ARE NOW SHOWN AT ALL. `validate()` rejects a malformed email
                                 server-side and Laravel redirects BACK — but this view rendered only the
                                 domain-level `booking_error` flash, so a guest whose email failed got a SILENT
                                 BOUNCE: fields repopulated, no reason given anywhere on the page. `required`
                                 covers the empty case in the browser and nothing covered the rejected one.
                                 ⚠️ `aria-describedby` ties the message to its own field, so the answer to
                                 "what failed?" is not left to reading order. --}}
                            <div>
                                <label class="vy-co-fl" for="vy-co-email">{{ __('common.email') }}</label>
                                <input class="vy-co-in" type="email" name="email" id="vy-co-email"
                                       value="{{ old('email') }}" required autocomplete="email"
                                       @error('email') aria-invalid="true" aria-describedby="vy-co-email-err" @enderror>
                                @error('email')<p class="vy-co-err" id="vy-co-email-err">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="vy-co-card">
                        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px;">
                            <h2 class="vy-co-h2" style="margin:0;">{{ __('booking/checkout.payment') }}</h2>
                            <span class="vy-co-pay-brands"><span style="background: linear-gradient(140deg, #F0A57A, #D9662E);"></span><span style="background: rgba(120,70,40,.15);"></span></span>
                        </div>
                        {{-- D4 — honest payment (F-GUEST-1): the guest's choice is a REAL submitted field
                             (payment_option), never decorative. "Pay on arrival" leaves the balance DUE — we
                             NEVER record a payment the guest didn't make (#39). The card option is offered ONLY
                             when the certified TEST-MODE hosted Stripe rail is wired (no PAN on us, SAQ A; fulfils
                             on the webhook) — when it isn't, pay-on-arrival is the sole honest option. --}}
                        @if ($payByCard)
                            <div class="vy-co-pm" role="radiogroup" aria-label="{{ __('booking/checkout.how_to_pay') }}">
                                <label class="vy-co-pm-opt on">
                                    <input type="radio" name="payment_option" value="card" checked onchange="vyPayOpt(this)">
                                    <span class="vy-co-pm-t"><b>{{ __('booking/checkout.pay_now') }}</b><span>{!! __('booking/checkout.pay_now_detail') !!}</span></span>
                                    <span class="vy-co-pm-dot"></span>
                                </label>
                                <label class="vy-co-pm-opt">
                                    <input type="radio" name="payment_option" value="on_arrival" onchange="vyPayOpt(this)">
                                    <span class="vy-co-pm-t"><b>{{ __('booking/checkout.pay_on_arrival') }}</b><span>{{ __('booking/checkout.pay_on_arrival_detail') }}</span></span>
                                    <span class="vy-co-pm-dot"></span>
                                </label>
                            </div>
                        @else
                            <input type="hidden" name="payment_option" value="on_arrival">
                            <div class="vy-pay-note">
                                <span class="vy-soon-tag">{{ __('booking/checkout.pay_on_arrival') }}</span>
                                <span>{{ __('booking/checkout.pay_on_arrival_detail') }}</span>
                            </div>
                        @endif
                        <div class="vy-co-note">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" style="flex:none; margin-top:1px;"><rect x="4" y="10" width="16" height="11" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg>
                            {{ __('booking/checkout.secure_note') }}
                        </div>
                    </div>

                    {{-- BOOKING TERMS (his ruling 2026-11-14, option B): what the guest agrees to, built from the property's REAL settings by the
                         ONE App\Support\Booking\BookingTerms (the same builder the stored proof is made from). The VERSION shown travels in the
                         form: if the property changes its rules before the button is pressed, nothing is booked and this page is shown again. --}}
                    <div class="vy-co-card" id="vy-terms">
                        <h2 class="vy-co-h2">{{ __('booking/checkout.before_you_book') }}</h2>
                        <p style="margin: 0 0 12px; font-size: 13px; color: var(--vy-muted);">{{ __('booking/checkout.before_you_book_intro') }}</p>
                        <x-booking.terms :terms="$terms" />
                        <input type="hidden" name="terms_version" value="{{ $terms['version'] }}">
                        @php
                            // The sentence is ESCAPED first; only then are the two links put in — a translation can never inject markup.
                            $acceptLine = strtr(e(__('booking/checkout.accept_terms')), [
                                ':terms' => '<a href="'.e($termsUrl).'" target="_blank" rel="noopener">'.e(__('booking/checkout.terms_link')).'</a>',
                                ':privacy' => '<a href="'.e($privacyUrl).'" target="_blank" rel="noopener">'.e(__('booking/checkout.privacy_link')).'</a>',
                            ]);
                        @endphp
                        <label style="display: flex; align-items: flex-start; gap: 10px; margin-top: 16px; font-size: 13.5px; line-height: 1.45; color: var(--vy-ink); cursor: pointer;">
                            <input type="checkbox" name="terms_accepted" value="1" required style="margin-top: 3px; flex: none; width: 17px; height: 17px;"
                                   @error('terms_accepted') aria-invalid="true" aria-describedby="vy-co-terms-err" @enderror>
                            <span>{!! $acceptLine !!}</span>
                        </label>
                        @error('terms_accepted')<p class="vy-co-err" id="vy-co-terms-err" role="alert">{{ $message }}</p>@enderror
                    </div>
                </div>

                {{-- RIGHT — the REAL reconciling summary --}}
                <div class="vy-co-card" id="vy-summary" @isset($wallet) data-credit-minor="{{ $wallet['apply'] }}" @endisset>
                    <div class="vy-co-hero">
                        <div class="vy-co-hero-ic"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5 12 3l9 7.5"></path><path d="M5 9.5V21h14V9.5"></path><path d="M9.5 21v-6h5v6"></path></svg></div>
                        <div style="min-width:0;">
                            <div class="vy-co-hero-name">{{ $offer['name'] }}</div>
                            <div class="vy-co-hero-sub">{{ $offer['kind'] }} · {{ $property['name'] }}</div>
                        </div>
                    </div>

                    <div class="vy-co-rows">
                        <div class="vy-co-row"><span class="k">{{ __('booking/checkout.check_in') }}</span><span class="v">{{ $fmtDate($search['check_in']) }}</span></div>
                        <div class="vy-co-row"><span class="k">{{ __('booking/checkout.check_out') }}</span><span class="v">{{ $fmtDate($search['check_out']) }}</span></div>
                        <div class="vy-co-row"><span class="k">{{ __('booking/checkout.guests') }}</span><span class="v">{{ trans_choice('booking/checkout.guests_count', $guests, ['count' => $guests]) }}</span></div>
                    </div>

                    {{-- RECONCILING breakdown (room + real cleaning fee + charged taxes) via StayEstimator (#17). --}}
                    <div class="vy-co-lines">
                        @foreach ($offer['breakdown'] as $line)
                            <div class="vy-co-line">
                                <span class="k">{{ $line['label'] }}{{ $line['detail'] !== '' ? ' · '.$line['detail'] : '' }}</span>
                                <span class="v">{{ $line['informational'] ? __('booking/checkout.included') : $line['amount'] }}</span>
                            </div>
                        @endforeach
                    </div>

                    {{-- REAL wallet credit (D3): shown ONLY for a signed-in guest with a real spendable balance.
                         The checkbox submits apply_credit; the server caps + deducts for real. A no-Alpine inline
                         preview swaps the shown Total — it reconciles with the server (cap = min(spendable, owed)). --}}
                    @isset($wallet)
                        <label class="vy-co-credit" id="vy-credit-toggle">
                            <input type="checkbox" name="apply_credit" value="1" onchange="vyToggleCredit(this)">
                            <span class="vy-co-credit-ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="13" rx="2.5"></rect><path d="M2 10.5h20"></path><path d="M6 15h3"></path></svg></span>
                            <span class="vy-co-credit-t"><b>{{ __('booking/checkout.apply_wallet_credit') }}</b><span>{{ __('booking/checkout.wallet_available', ['amount' => $wallet['display']]) }}</span></span>
                            <span class="vy-co-switch"></span>
                        </label>
                        <div class="vy-co-credit-line">
                            <span class="lbl"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 12.5 9.5 17.5 19.5 7"></path></svg>{{ __('booking/checkout.wallet_credit') }}</span>
                            <span class="amt">−{{ $wallet['apply_display'] }}</span>
                        </div>
                    @endisset

                    <div class="vy-co-total">
                        <span class="lbl">{{ __('common.total') }}</span>
                        <span class="val" id="vy-total" @isset($wallet) data-full="{{ $offer['grand_total'] }}" data-net="{{ $wallet['net_display'] }}" @endisset>{{ $offer['grand_total'] }}</span>
                    </div>

                    <x-busy-btn class="vy-co-confirm" busy-label="{{ $payByCard ? __('booking/checkout.redirecting') : __('booking/checkout.reserving') }}"><span id="vy-confirm-label">{{ $payByCard ? __('booking/checkout.confirm_and_pay', ['amount' => $offer['grand_total']]) : __('booking/checkout.confirm_reservation') }}</span></x-busy-btn>

                    @isset($wallet)
                        <div class="vy-co-saved" id="vy-saved"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg>{{ __('booking/checkout.you_saved', ['amount' => $wallet['apply_display']]) }}</div>
                    @endisset

                    @if ($freeCancel !== null)
                        <div class="vy-co-freecancel">{{ __('booking/checkout.free_cancellation', ['date' => $freeCancel]) }}</div>
                    @endif
                </div>
            </div>
        </form>

        @if ($payByCard)
            <script>
                // F-GUEST-1 — reflect the guest's real payment choice in the confirm button (progressive
                // enhancement; the server is authoritative). "Pay now" shows the price; "Pay on arrival" doesn't.
                function vyPayOpt(radio) {
                    document.querySelectorAll('.vy-co-pm-opt').forEach(function (o) { o.classList.toggle('on', o.contains(radio)); });
                    var label = document.getElementById('vy-confirm-label');
                    var total = document.getElementById('vy-total');
                    var shown = total ? total.textContent : '';
                    if (label) label.textContent = radio.value === 'card' ? @js(__('booking/checkout.confirm_and_pay', ['amount' => ':amount'])).replace(':amount', function () { return shown; }) : @js(__('booking/checkout.confirm_reservation'));
                }
            </script>
        @endif

        @isset($wallet)
            <script>
                // No-Alpine wallet-credit preview (progressive enhancement). Presentational only — the server is
                // authoritative (caps at min(spendable, owed) and posts the real folio discount). The full/net
                // display strings are computed server-side so the preview can't drift from the charge (#17).
                function vyToggleCredit(cb) {
                    var toggle = document.getElementById('vy-credit-toggle');
                    var total = document.getElementById('vy-total');
                    var saved = document.getElementById('vy-saved');
                    var label = document.getElementById('vy-confirm-label');
                    var on = cb.checked;
                    if (toggle) toggle.classList.toggle('on', on);
                    if (total) total.textContent = on ? (total.dataset.net || total.textContent) : (total.dataset.full || total.textContent);
                    if (saved) saved.style.display = on ? 'flex' : 'none';
                    // Only the card path shows a price in the button ("Confirm & pay …"); keep it in sync. Asked of the SERVER's
                    // own flag, not of the button's English text — a search for 'pay' fails in every other language (I18N-01).
                    if (label && @js((bool) $payByCard)) {
                        label.textContent = @js(__('booking/checkout.confirm_and_pay', ['amount' => ':amount'])).replace(':amount', function () { return on ? total.dataset.net : total.dataset.full; });
                    }
                }
            </script>
        @endisset
    @endif
</x-public.shell>
