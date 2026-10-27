{{-- Operator GUEST PROFILE — the CRM detail (#87 exact-match), on the shared operator design system. EVERY
     control writes: each form is a real CSRF POST → GuestActionController → GuestService → PRG. Full contact is
     shown + editable (the operator is the manager; the #28 name-only rule is owner-only). See
     App\Livewire\Operator\GuestProfile for the REAL / DERIVED map.

     RAW-BLADE RULE: every `@` follows a space, newline or tag. The guest's EMAIL renders here (a literal `@`)
     but is echoed through {{ }} as data, never written into the template. --}}
<div class="vy-gst">
    <x-operator.shell active="guests" :title="$guest['name']" :subtitle="$history['stat'] ?? __('livewire/operator/guest-profile.no_stays_yet')" :viewer="$viewer">

        @if (session('guest_notice'))
            <div class="vy-flash is-ok vy-fade">{{ session('guest_notice') }}</div>
        @endif
        @if (session('guest_error'))
            <div class="vy-flash is-err vy-fade">{{ session('guest_error') }}</div>
        @endif
        @if ($errors->any())
            <div class="vy-flash is-err vy-fade">
                @foreach ($errors->all() as $error) <div>{{ $error }}</div> @endforeach
            </div>
        @endif

        <div x-data="{ editOpen: false }">

            {{-- Breadcrumb --}}
            <div class="vy-rack-toolbar vy-fade" style="margin-bottom: 16px;">
                <div class="vy-left"><a class="vy-convo-back" href="{{ route('app.guests') }}">{{ __('livewire/operator/guest-profile.back_to_guests') }}</a></div>
            </div>

            {{-- ── Header ─────────────────────────────────────────────────────────────────────────────── --}}
            <div class="vy-card vy-fade">
                <div class="vy-detail-head">
                    <div style="display: flex; align-items: center; gap: 12px; min-width: 0;">
                        <span class="vy-avatar" style="width: 46px; height: 46px; font-size: 15px;">{{ $guest['initials'] }}</span>
                        <div style="min-width: 0;">
                            <div class="vy-detail-ref">{{ $guest['name'] }}</div>
                            <div class="vy-u-sub" style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap; margin-top: 5px;">
                                @if ($guest['email'] !== null) <span>{{ $guest['email'] }}</span> @endif
                                @if ($guest['nationality'] !== null) <span class="vy-sep">·</span><span>{{ $guest['nationality'] }}</span> @endif
                                @if ($guest['since_year'] !== null) <span class="vy-sep">·</span><span>{{ __('livewire/operator/guest-profile.guest_since', ['year' => $guest['since_year']]) }}</span> @endif
                            </div>
                            <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap; margin-top: 8px;">
                                @if ($guest['stage_meta'] !== null)
                                    <span class="vy-tag tone-{{ $guest['stage_meta']['tone'] }}"><span class="vy-dot"></span>{{ $guest['stage_meta']['label'] }}</span>
                                @endif
                                @if ($guest['is_vip']) <span class="vy-tag tone-vip"><span class="vy-dot"></span>{{ __('livewire/operator/guest-profile.vip') }}</span> @endif
                                @if ($guest['risk_flag']) <span class="vy-tag tone-risk"><span class="vy-dot"></span>{{ __('livewire/operator/guest-profile.at_risk') }}</span> @endif
                                @if ($guest['repeat']) <span class="vy-tag tone-tag"><span class="vy-dot"></span>{{ __('livewire/operator/guest-profile.repeat_guest') }}</span> @endif
                            </div>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        @unless ($guest['anonymized'])
                            <form method="POST" action="{{ route('app.guests.message', $guest['id']) }}" style="display: inline;">
                                @csrf
                                <button type="submit" class="vy-act vy-act--ghost">{{ __('livewire/operator/guest-profile.message_button') }}</button>
                            </form>
                            <button type="button" class="vy-act vy-act--ghost" @click="editOpen = true">{{ __('livewire/operator/guest-profile.edit_profile') }}</button>
                        @endunless
                        @if ($guest['current_stay'] !== null)
                            <a class="vy-act vy-act--accent" href="{{ route('app.reservations.show', $guest['current_stay']) }}">{{ __('livewire/operator/guest-profile.current_stay') }}</a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ── Stat strip — Stays · Nights · Avg/night (+ source split) ─────────────────────────────── --}}
            <div class="vy-dash-kpis vy-dash-kpis--3 vy-fade" style="margin-top: 18px;">
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">{{ __('livewire/operator/guest-profile.stays_label') }}</div>
                    <div class="vy-dk-val">{{ $history['stays'] }}</div>
                    <div class="vy-dk-sub">{{ __('livewire/operator/guest-profile.cancellations_excluded') }}</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">{{ __('livewire/operator/guest-profile.nights_label') }}</div>
                    <div class="vy-dk-val">{{ $history['nights'] > 0 ? $history['nights'] : '—' }}</div>
                    <div class="vy-dk-sub">{{ __('livewire/operator/guest-profile.across_every_stay') }}</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">{{ __('livewire/operator/guest-profile.avg_per_night_label') }}</div>
                    <div class="vy-dk-val">{{ $spend['avg_per_night'] ?? '—' }}</div>
                    <div class="vy-dk-sub">{{ $guest['source_mix'] ?? __('livewire/operator/guest-profile.no_stays_yet_lower') }}</div>
                </div>
            </div>

            <div class="vy-detail-cols" style="margin-top: 18px;">
                <div class="vy-stack">

                    {{-- ── Stay history — with the real per-stay refund line ─────────────────────────── --}}
                    <div class="vy-card">
                        <div class="vy-detail-head" style="margin-bottom: 10px;">
                            <h2 class="vy-card-title">{{ __('livewire/operator/guest-profile.stay_history') }}</h2>
                            <span class="vy-eyebrow">{{ trans_choice('livewire/operator/guest-profile.stays_count', $history['stays'], ['count' => $history['stays']]) }}</span>
                        </div>
                        @forelse ($stays as $stay)
                            <div class="vy-unit-row">
                                <div style="min-width: 0;">
                                    <div class="vy-um-name">
                                        <a class="vy-arr-link" href="{{ route('app.reservations.show', $stay['id']) }}">{{ __('livewire/operator/guest-profile.stay_dates', ['check_in' => $stay['check_in'], 'check_out' => $stay['check_out']]) }}</a>
                                        @if ($stay['in_house']) <span class="vy-tag tone-in-house" style="margin-left: 6px;"><span class="vy-dot"></span>{{ __('livewire/operator/guest-profile.here_now') }}</span> @endif
                                    </div>
                                    <div class="vy-um-sub">
                                        {{ $stay['reference'] }}
                                        <span class="vy-sep">·</span>{{ trans_choice('livewire/operator/guest-profile.nights_count', $stay['nights'], ['count' => $stay['nights']]) }}
                                        @if ($stay['unit'] !== null) <span class="vy-sep">·</span>{{ $stay['unit'] }} @endif
                                        <span class="vy-sep">·</span>{{ $stay['source'] }}
                                    </div>
                                </div>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="text-align: right;">
                                        <span class="vy-ledger-amt tone-charge">{{ $stay['total'] }}</span>
                                        @if ($stay['refund'] !== null)
                                            <div class="vy-balance is-due" style="font-size: 11px;">{{ __('livewire/operator/guest-profile.refund_line', ['amount' => $stay['refund']]) }}</div>
                                        @endif
                                    </div>
                                    <span class="vy-tag tone-{{ $stay['stage_meta']['tone'] }}"><span class="vy-dot"></span>{{ $stay['stage_meta']['label'] }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="vy-empty">{{ __('livewire/operator/guest-profile.stays_empty') }}</div>
                        @endforelse
                    </div>

                    {{-- ── Latest review — real (#76); honest empty state, never a fabricated 5.0 ─────── --}}
                    <div class="vy-card">
                        <div class="vy-detail-head" style="margin-bottom: 10px;">
                            <h2 class="vy-card-title">{{ __('livewire/operator/guest-profile.latest_review') }}</h2>
                            <a class="vy-eyebrow vy-arr-link" href="{{ route('app.reviews') }}">{{ __('livewire/operator/guest-profile.all_reviews') }}</a>
                        </div>
                        @if ($review !== null)
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span role="img" aria-label="{{ __('livewire/operator/guest-profile.rating_stars_label', ['rating' => number_format($review['rating'], 1)]) }}" style="font-size: 18px; color: var(--vy-star); letter-spacing: 2px;"><span aria-hidden="true">{{ $review['stars'] }}</span></span>
                                <span style="font-weight: 700; color: var(--vy-ink);">{{ number_format($review['rating'], 1) }}</span>
                                @if ($review['status'] !== 'published')
                                    <span class="vy-tag tone-tag" style="margin-left: auto;"><span class="vy-dot"></span>{{ $review['status_label'] }}</span>
                                @endif
                            </div>
                            @if ($review['body'] !== '')
                                <p style="margin: 10px 0 0; font-size: 14px; line-height: 1.55; color: var(--vy-ink);">“{{ $review['body'] }}”</p>
                            @endif
                            <div class="vy-um-sub" style="margin-top: 8px;">
                                @if ($review['unit'] !== null) {{ $review['unit'] }} <span class="vy-sep">·</span> @endif
                                @if ($review['when'] !== null) {{ $review['when'] }} @endif
                            </div>
                            @if ($review['response'] !== null)
                                <div class="vy-recon" style="margin-top: 8px;">{{ __('livewire/operator/guest-profile.your_response', ['response' => $review['response']]) }}</div>
                            @endif
                        @else
                            <div class="vy-empty">{{ __('livewire/operator/guest-profile.no_reviews_yet') }}</div>
                        @endif
                    </div>

                    {{-- ── Notes — the shared polymorphic table; this page its first writer AND reader ── --}}
                    <div class="vy-card">
                        <div class="vy-detail-head" style="margin-bottom: 10px;">
                            <h2 class="vy-card-title">{{ __('common.notes') }}</h2>
                            <span class="vy-eyebrow">{{ __('livewire/operator/guest-profile.internal_only') }}</span>
                        </div>
                        @unless ($guest['anonymized'])
                            <form method="POST" action="{{ route('app.guests.notes.store', $guest['id']) }}" class="vy-input-group" style="margin-bottom: 14px;">
                                @csrf
                                <input class="vy-input" type="text" name="body" maxlength="2000" placeholder="{{ __('livewire/operator/guest-profile.note_placeholder') }}" aria-label="{{ __('livewire/operator/guest-profile.note_aria') }}" required>
                                <button type="submit" class="vy-btn vy-btn--accent">{{ __('livewire/operator/guest-profile.add_note') }}</button>
                            </form>
                        @endunless
                        @forelse ($notes as $note)
                            <div class="vy-unit-row">
                                <div style="min-width: 0;">
                                    @if ($note['redacted'])
                                        <div class="vy-um-name vy-muted">{{ __('livewire/operator/guest-profile.note_redacted') }}</div>
                                    @else
                                        <div class="vy-um-name" style="white-space: normal;">{{ $note['body'] }}</div>
                                    @endif
                                    <div class="vy-um-sub">{{ $note['author'] }} <span class="vy-sep">·</span>{{ $note['created'] }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="vy-empty">{{ __('livewire/operator/guest-profile.no_notes_yet') }}</div>
                        @endforelse
                    </div>
                </div>

                <div class="vy-stack">

                    {{-- ── Lifetime value — the dark hero (billed + open folio), reconciling to the ledger ── --}}
                    <div class="vy-tonight" style="padding: 20px;">
                        <div class="vy-dk-label" style="color: rgba(255,255,255,.7);">{{ __('livewire/operator/guest-profile.lifetime_value') }}</div>
                        @forelse ($spend['billed'] as $line)
                            <div style="font-family: 'Sora', sans-serif; font-weight: 700; color: #fff; @if ($loop->first) font-size: 30px; @else font-size: 19px; @endif">{{ $line }}</div>
                        @empty
                            <div style="font-family: 'Sora', sans-serif; font-weight: 700; color: #fff; font-size: 30px;">—</div>
                        @endforelse
                        <div style="font-size: 12.5px; color: rgba(255,255,255,.7); margin-top: 6px;">
                            {{ trans_choice('livewire/operator/guest-profile.stays_count', $history['stays'], ['count' => $history['stays']]) }} · {{ trans_choice('livewire/operator/guest-profile.nights_count', $history['nights'], ['count' => $history['nights']]) }}
                            {{ $spend['avg_per_night'] !== null ? __('livewire/operator/guest-profile.avg_a_night', ['amount' => $spend['avg_per_night']]) : '' }}
                        </div>
                        @if ($spend['balance'] !== [])
                            <div style="border-top: 1px solid rgba(255,255,255,.14); margin-top: 14px; padding-top: 12px;">
                                @foreach ($spend['balance'] as $line)
                                    <div style="color: #F6C6B4; font-weight: 600;">{{ __('livewire/operator/guest-profile.open_folio_line', ['amount' => $line]) }}</div>
                                @endforeach
                                <div style="font-size: 11.5px; color: rgba(255,255,255,.6); margin-top: 2px;">{{ __('livewire/operator/guest-profile.settles_at_checkout') }}</div>
                            </div>
                        @endif
                    </div>

                    {{-- ── Contact — email/phone/city + prefers-email + marketing-consent ─────────────── --}}
                    <div class="vy-card">
                        <h2 class="vy-card-title" style="margin-bottom: 10px;">{{ __('livewire/operator/guest-profile.contact') }}</h2>
                        <div class="vy-facts">
                            <div class="vy-fact-label">{{ __('common.email') }}</div>
                            <div class="vy-fact-val">@if ($guest['email'] !== null) <a href="mailto:{{ $guest['email'] }}">{{ $guest['email'] }}</a> @else <span class="vy-muted">—</span> @endif</div>
                            <div class="vy-fact-label">{{ __('common.phone') }}</div>
                            <div class="vy-fact-val">{{ $guest['phone'] ?? '—' }}</div>
                            <div class="vy-fact-label">{{ __('livewire/operator/guest-profile.city') }}</div>
                            <div class="vy-fact-val">{{ $guest['city'] ?? '—' }}</div>
                        </div>
                        <div class="vy-recon" style="margin-top: 10px;">
                            {{ $guest['prefers_email'] ? __('livewire/operator/guest-profile.prefers_email') : __('livewire/operator/guest-profile.no_channel_preference') }} ·
                            {{ $guest['marketing_consent'] ? __('livewire/operator/guest-profile.marketing_consent_on_file') : __('livewire/operator/guest-profile.no_marketing_consent') }}
                        </div>
                    </div>

                    {{-- ── Preferences & notes — real structured prefs, shown to HK & front desk ──────── --}}
                    <div class="vy-card">
                        <h2 class="vy-card-title" style="margin-bottom: 4px;">{{ __('livewire/operator/guest-profile.preferences') }}</h2>
                        <p style="font-size: 12px; color: var(--vy-muted); margin: 0 0 12px;">{{ __('livewire/operator/guest-profile.preferences_shown_to') }}</p>
                        @if ($guest['preferences'] !== [])
                            <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                @foreach ($guest['preferences'] as $key)
                                    <span class="vy-tag tone-tag"><span class="vy-dot"></span>{{ $prefCatalog[$key] ?? $key }}</span>
                                @endforeach
                            </div>
                        @else
                            <div class="vy-empty" style="padding: 12px;">{{ __('livewire/operator/guest-profile.no_preferences') }}</div>
                        @endif
                    </div>

                    {{-- ── Tags — the tenant's REAL catalog, created on first use ──────────────────────── --}}
                    <div class="vy-card">
                        <h2 class="vy-card-title" style="margin-bottom: 10px;">{{ __('livewire/operator/guest-profile.tags') }}</h2>
                        @if ($guest['tags'] !== [])
                            <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap; margin-bottom: 12px;">
                                @foreach ($guest['tags'] as $tag)
                                    <form method="POST" action="{{ route('app.guests.tags.destroy', $guest['id']) }}" style="display: inline;">
                                        @csrf @method('DELETE')
                                        <input type="hidden" name="tag" value="{{ $tag['id'] }}">
                                        <button type="submit" class="vy-tag tone-tag" style="border: none; cursor: pointer;" title="{{ __('livewire/operator/guest-profile.remove_tag_title') }}">
                                            <span class="vy-dot"></span>{{ $tag['name'] }} ×
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        @endif
                        @unless ($guest['anonymized'])
                            <form method="POST" action="{{ route('app.guests.tags.store', $guest['id']) }}" class="vy-input-group">
                                @csrf
                                <input class="vy-input" type="text" name="name" maxlength="255" placeholder="{{ __('livewire/operator/guest-profile.tag_placeholder') }}" aria-label="{{ __('livewire/operator/guest-profile.tag_aria') }}" required>
                                <button type="submit" class="vy-btn vy-btn--ghost">{{ __('common.add') }}</button>
                            </form>
                            <div class="vy-recon">{{ __('livewire/operator/guest-profile.tags_shared_note') }}</div>
                        @endunless
                    </div>

                    @if ($guest['anonymized'])
                        <div class="vy-card">
                            <div class="vy-empty" style="padding: 20px;">{{ __('livewire/operator/guest-profile.anonymized_notice') }}</div>
                        </div>
                    @endif

                    {{-- ── GDPR ERASURE — gated, server-confirmed ────────────────────────────────────── --}}
                    @can('guests.erase')
                        @unless ($guest['anonymized'])
                            <div class="vy-card">
                                <h2 class="vy-card-title" style="margin-bottom: 6px;">{{ __('livewire/operator/guest-profile.erase_heading') }}</h2>
                                <div class="vy-recon" style="margin-top: 0;">
                                    {!! __('livewire/operator/guest-profile.erase_explainer') !!}
                                </div>
                                <form method="POST" action="{{ route('app.guests.anonymize', $guest['id']) }}" style="margin-top: 12px;"
                                      onsubmit="return confirm(@js(__('livewire/operator/guest-profile.erase_confirm_dialog')));">
                                    @csrf
                                    <label class="vy-field-label" for="confirm">{{ __('livewire/operator/guest-profile.type_erase_to_confirm') }}</label>
                                    <div class="vy-input-group">
                                        <input class="vy-input" type="text" id="confirm" name="confirm" autocomplete="off" placeholder="ERASE">
                                        <button type="submit" class="vy-btn vy-btn--ghost">{{ __('livewire/operator/guest-profile.erase_button') }}</button>
                                    </div>
                                </form>
                            </div>
                        @endunless
                    @endcan
                </div>
            </div>

            {{-- ── EDIT PROFILE modal — the real write (name/email/phone/city/prefs/toggles) ──────────────────────
                 2026-09-29 — centred by `.vy-modal-backdrop`: the inline `display: flex` it had was erased by x-show the first
                 time the dialog closed, so it opened top-left (XShowInlineDisplayContractTest). Same chrome as the Guests
                 list's Edit; every field it had is still here, the stay preferences included. --}}
            @unless ($guest['anonymized'])
                <div class="vy-modal-backdrop" x-cloak x-show="editOpen" x-transition.opacity @keydown.escape.window="editOpen = false" @click.self="editOpen = false">
                    <div class="vy-modal vy-modal-card vy-modal--wide" x-show="editOpen" x-transition role="dialog" aria-modal="true" tabindex="-1" x-trap.noscroll="editOpen" aria-labelledby="vy-dlg-guest-profile-editopen-1">
                        <form method="POST" action="{{ route('app.guests.update', $guest['id']) }}" data-guard-submit>
                            @csrf
                            <div class="vy-modal-head">
                                <span class="vy-modal-ico">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                                </span>
                                <div class="vy-gl-head-text">
                                    <h3 class="vy-modal-title" id="vy-dlg-guest-profile-editopen-1">{{ __('livewire/operator/guest-profile.edit_profile') }}</h3>
                                    <p class="vy-modal-sub">{{ __('livewire/operator/guest-profile.edit_profile_sub') }}</p>
                                </div>
                                <button type="button" class="vy-modal-x" @click="editOpen = false" aria-label="{{ __('common.close') }}">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <div class="vy-modal-body">
                                <div class="vy-gl-fields">
                                    <div class="vy-field"><label class="vy-field-label" for="first_name">{{ __('livewire/operator/guest-profile.first_name') }}</label><input class="vy-input" id="first_name" type="text" name="first_name" maxlength="255" value="{{ old('first_name', $guest['first_name']) }}" autocomplete="off"></div>
                                    <div class="vy-field"><label class="vy-field-label" for="last_name">{{ __('livewire/operator/guest-profile.last_name') }}</label><input class="vy-input" id="last_name" type="text" name="last_name" maxlength="255" value="{{ old('last_name', $guest['last_name']) }}" autocomplete="off"></div>
                                    <div class="vy-field"><label class="vy-field-label" for="email">{{ __('common.email') }}</label><input class="vy-input" id="email" type="email" name="email" maxlength="255" value="{{ old('email', $guest['email']) }}" autocomplete="off"></div>
                                    <div class="vy-field"><label class="vy-field-label" for="phone">{{ __('common.phone') }}</label><input class="vy-input" id="phone" type="tel" name="phone" maxlength="64" value="{{ old('phone', $guest['phone']) }}" autocomplete="off"></div>
                                    <div class="vy-field"><label class="vy-field-label" for="city">{{ __('livewire/operator/guest-profile.city_country') }}</label><input class="vy-input" id="city" type="text" name="city" maxlength="255" value="{{ old('city', $guest['city']) }}" autocomplete="off"></div>
                                    <div class="vy-field"><label class="vy-field-label" for="nat">{{ __('livewire/operator/guest-profile.nationality') }} <span class="vy-fl-note">{{ __('livewire/operator/guest-profile.two_letters_note') }}</span></label><input class="vy-input vy-gl-code" id="nat" type="text" name="nationality" maxlength="2" pattern="[A-Za-z]{2}" title="{{ __('livewire/operator/guest-profile.nationality_title') }}" value="{{ old('nationality', $guest['nationality_code']) }}" autocomplete="off"></div>
                                </div>

                                {{-- Preferences — the real structured store; shown to housekeeping and the front desk --}}
                                <fieldset class="vy-gl-fieldset">
                                    <legend class="vy-field-label">{{ __('livewire/operator/guest-profile.stay_preferences') }} <span class="vy-fl-note">{{ __('livewire/operator/guest-profile.stay_preferences_note') }}</span></legend>
                                    <div class="vy-gl-prefs">
                                        @foreach ($prefCatalog as $key => $label)
                                            <label class="vy-gl-pref"><input type="checkbox" name="preferences[]" value="{{ $key }}" @checked(in_array($key, $guest['preferences'], true))><span>{{ $label }}</span></label>
                                        @endforeach
                                    </div>
                                </fieldset>

                                <div class="vy-gl-switches">
                                    <label class="vy-gl-switch"><input type="checkbox" name="is_vip" value="1" @checked(old('is_vip', $guest['is_vip']))><span class="vy-gl-track" aria-hidden="true"></span><span class="vy-gl-switch-text"><strong>{{ __('livewire/operator/guest-profile.vip_guest') }}</strong><span>{{ __('livewire/operator/guest-profile.vip_guest_hint') }}</span></span></label>
                                    <label class="vy-gl-switch"><input type="checkbox" name="risk_flag" value="1" @checked(old('risk_flag', $guest['risk_flag']))><span class="vy-gl-track" aria-hidden="true"></span><span class="vy-gl-switch-text"><strong>{{ __('livewire/operator/guest-profile.at_risk_switch') }}</strong><span>{{ __('livewire/operator/guest-profile.at_risk_hint') }}</span></span></label>
                                    <label class="vy-gl-switch"><input type="checkbox" name="prefers_email" value="1" @checked(old('prefers_email', $guest['prefers_email']))><span class="vy-gl-track" aria-hidden="true"></span><span class="vy-gl-switch-text"><strong>{{ __('livewire/operator/guest-profile.prefers_email_switch') }}</strong><span>{{ __('livewire/operator/guest-profile.prefers_email_hint') }}</span></span></label>
                                    <label class="vy-gl-switch"><input type="checkbox" name="marketing_consent" value="1" @checked(old('marketing_consent', $guest['marketing_consent']))><span class="vy-gl-track" aria-hidden="true"></span><span class="vy-gl-switch-text"><strong>{{ __('livewire/operator/guest-profile.marketing_consent') }}</strong><span>{{ __('livewire/operator/guest-profile.marketing_consent_hint') }}</span></span></label>
                                </div>
                            </div>
                            <div class="vy-modal-foot">
                                <button type="button" class="vy-btn vy-btn--ghost" @click="editOpen = false">{{ __('common.cancel') }}</button>
                                <button type="submit" class="vy-btn vy-btn--accent">{{ __('common.save_changes') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endunless

        </div>
    </x-operator.shell>
</div>

@once
    <style>[x-cloak] { display: none !important; }</style>
@endonce
