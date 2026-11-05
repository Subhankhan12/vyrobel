{{-- Owner Add a Unit — the 4-step WIZARD (My Units · GATE 3), rebuilt to the wireframe. An owner REQUESTS a unit;
     they never create billable inventory. The write is ONE ordinary form POST → OwnerUnitRequestController (never
     /livewire/update — the owner-scope bypass boundary, #20); the steps are a client-side (Alpine) convenience and
     EVERY field is re-validated server-side. Everything shown is REAL (#39): the "Managed by … X% commission" panel
     reads the owner's OWN active agreement; the amenity chips are the real catalog; the live preview reflects the
     owner's OWN entered values, honestly badged "Pending review". RAW-BLADE RULE: every `@` follows a space/tag. --}}
@php
    // Amenity slug → inline SVG (13px chips). Decorative only — the DATA (names/ids) is the real catalog. A slug
    // with no mapping falls back to a check, never a fabricated label.
    $amIcons = [
        'wifi' => '<path d="M5 12.5a10 10 0 0 1 14 0"/><path d="M8 15.5a6 6 0 0 1 8 0"/><path d="M12 19h.01"/>',
        'pool' => '<path d="M3 17c2 1.4 4 1.4 6 0s4-1.4 6 0 4 1.4 6 0"/><path d="M3 13c2 1.4 4 1.4 6 0s4-1.4 6 0 4 1.4 6 0"/><path d="M9 12V6a2 2 0 0 1 4 0"/>',
        'kitchen' => '<path d="M8 3v6a2 2 0 0 0 4 0V3"/><path d="M10 9v12"/><path d="M16 3c-1.4 0-2 1.9-2 4s.6 4 2 4 2-1.9 2-4-.6-4-2-4z"/><path d="M16 11v10"/>',
        'ac' => '<rect x="3" y="6" width="18" height="6" rx="2"/><path d="M7 16v.01"/><path d="M12 16v2"/><path d="M17 16v.01"/>',
        'heating' => '<path d="M8 3c1 2-1 3 0 5M12 3c1 2-1 3 0 5M16 3c1 2-1 3 0 5"/><path d="M5 11h14v8a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2z"/>',
        'parking' => '<path d="M7 20V4h5a4 4 0 0 1 0 8H7"/>',
        'washer' => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M4 8h16"/><circle cx="12" cy="14" r="4"/>',
        'tv' => '<rect x="2" y="7" width="20" height="12" rx="2"/><path d="M8 3l4 4 4-4"/>',
        'balcony' => '<path d="M5 21V10h14v11"/><path d="M5 14h14"/><path d="M9 14v7M15 14v7"/><path d="M8 10V6a4 4 0 0 1 8 0v4"/>',
        'pets' => '<circle cx="5" cy="9" r="2"/><circle cx="19" cy="9" r="2"/><circle cx="9" cy="6" r="2"/><circle cx="15" cy="6" r="2"/><path d="M12 22c4 0 5-3 5-5a5 5 0 0 0-10 0c0 2 1 5 5 5z"/>',
    ];
    $amFallback = '<path d="M4.5 12.5 9.5 17.5 19.5 7"/>';
@endphp
<x-owner.shell active="add-unit" :title="__('livewire/owner/add-unit.page_title')" :viewer="$viewer">

    {{-- Server validation only — the wizard never invents a client-side "looks good". Every message the server
         actually returned is shown (the old markup dropped all but the first), announced, and led by a word so
         the failure is not carried by the accent rule alone. --}}
    @if ($errors->any())
        <div class="vy-card vy-fade" role="alert" style="border-left:3px solid var(--vy-accent);margin-bottom:16px">
            <div style="font-size:13px;font-weight:600;color:var(--vy-accent-text);margin-bottom:2px">{{ __('livewire/owner/add-unit.error_heading') }}</div>
            @foreach ($errors->all() as $error)
                <div style="font-size:13px;color:var(--vy-ink)">{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @unless ($linked)
        <a class="vy-au-back" href="{{ route('owner.units') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
            {{ __('livewire/owner/add-unit.my_units') }}
        </a>
        <div class="vy-card vy-fade"><div class="vy-empty">{{ __('livewire/owner/add-unit.not_linked') }}</div></div>
    @elseif ($done)
        {{-- DONE — server-rendered after the PRG redirect from a REAL submission --}}
        <a class="vy-au-back" href="{{ route('owner.units') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
            {{ __('livewire/owner/add-unit.my_units') }}
        </a>
        <div class="vy-au-done vy-fade">
            <div class="vy-au-done-card">
                <div class="vy-au-done-badge">
                    <div class="vy-au-done-badge-glow"></div>
                    <div class="vy-au-done-badge-disc">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    </div>
                </div>
                <div class="vy-au-done-title">{{ __('livewire/owner/add-unit.done_title') }}</div>
                <div class="vy-au-done-sub">{{ __('livewire/owner/add-unit.done_sub', ['first_name' => $firstName, 'operator' => $operatorName]) }}</div>

                <div class="vy-au-checklist">
                    <div class="vy-au-check">
                        <span class="vy-au-check-ico vy-au-check-ico--done"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 12.5 9.5 17.5 19.5 7"/></svg></span>
                        <span class="vy-au-check-txt">@if ($done['location'] !== ''){!! __('livewire/owner/add-unit.done_submitted_in', ['name' => e($done['name']), 'location' => e($done['location'])]) !!}@else{!! __('livewire/owner/add-unit.done_submitted', ['name' => e($done['name'])]) !!}@endif</span>
                    </div>
                    <div class="vy-au-check">
                        <span class="vy-au-check-ico vy-au-check-ico--wait"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/></svg></span>
                        <span class="vy-au-check-txt">{{ __('livewire/owner/add-unit.done_team_verifies') }}</span>
                    </div>
                    <div class="vy-au-check">
                        <span class="vy-au-check-ico vy-au-check-ico--home"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/></svg></span>
                        <span class="vy-au-check-txt">{!! __('livewire/owner/add-unit.done_once_live') !!}</span>
                    </div>
                </div>

                <a class="vy-au-cta" href="{{ route('owner.units') }}">{{ __('livewire/owner/add-unit.back_to_my_units') }}</a>
                <div class="vy-au-done-actions">
                    <a class="vy-au-cta--ghost" href="{{ route('owner.add-unit') }}">{{ __('livewire/owner/add-unit.add_another') }}</a>
                    <a class="vy-au-cta--ghost" href="{{ route('owner.messages') }}">{{ __('livewire/owner/add-unit.message_the_team') }}</a>
                </div>
            </div>
        </div>
    @else
        {{-- DO9-3 — the withdrawal outcome. The REFUSAL is its own notice and says WHICH state won the race
             ("already approved" / "already reviewed"), because "couldn't withdraw" alone would leave the owner
             unsure whether their request is still live. --}}
        @if (session('unit_request_withdrawn'))
            <div class="vy-flash is-ok" style="margin-bottom:18px">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                {{ session('unit_request_withdrawn') }}
            </div>
        @endif
        @if (session('unit_request_withdraw_error'))
            <div class="vy-flash" style="margin-bottom:18px">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>
                {{ session('unit_request_withdraw_error') }}
            </div>
        @endif

        {{-- DO9-2 — the draft-saved confirmation. Says plainly that nothing has been sent, because "saved" on a
             request screen could otherwise be read as "submitted". --}}
        @if (session('unit_request_draft_saved'))
            <div class="vy-flash is-ok" style="margin-bottom:18px">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                {{ session('unit_request_draft_saved') }}
            </div>
        @endif

        {{-- THE WIZARD --}}
        {{-- DO9-2 — every initial value comes from the resumed DRAFT when one is being continued, and falls back
             to the wizard's own defaults otherwise. Pre-filling here (rather than with a second "load" step)
             keeps resume a plain GET: the page IS the draft. --}}
        <div x-data="{
            step: 1,
            name: @js($draft['requested_name'] ?? ''), kind: @js($draft['requested_kind'] ?? array_key_first($kinds)),
            town: @js($draft['location'] ?? ''), address: @js($draft['street_address'] ?? ''),
            beds: @js($draft['bedrooms'] ?? 1), baths: @js($draft['bathrooms'] ?? 1),
            sleeps: @js($draft['sleeps'] ?? 2), minNights: @js($draft['min_nights'] ?? 2),
            amenities: @js($draft['amenities'] ?? []), nightly: @js($draft['nightly_rate'] ?? ''),
            cleaning: @js($draft['cleaning_fee'] ?? ''), coverUrl: '', photo2Url: '', notes: @js($draft['notes'] ?? ''),
            pct: @js($commissionPct), sym: @js($currencySymbol), lim: @js($limits),
            kindLabels: @js($kinds), amNames: @js(collect($amenities)->pluck('name', 'id')),
            get canNext1() { return this.name.trim().length > 0 },
            get stepLabel() { return @js(__('livewire/owner/add-unit.step_of', ['step' => ':step'])).replace(':step', this.step) },
            get kindLabel() { return this.kindLabels[this.kind] || '' },
            get previewName() { return this.name.trim() || @js(__('livewire/owner/add-unit.your_new_unit')) },
            get previewSub() { return this.kindLabel + (this.town.trim() ? ' · ' + this.town.trim() : '') },
            get previewRate() { return this.nightly.trim() ? this.sym + this.nightly.trim() : this.sym + '—' },
            get hasAmenities() { return this.amenities.length > 0 },
            get previewAmenities() { return this.amenities.slice(0, 4).map(id => this.amNames[id]).filter(Boolean) },
            get netNight() {
                if (this.pct === null) return null;
                const n = parseFloat(String(this.nightly).replace(/[^0-9.]/g, ''));
                if (!n || n <= 0) return null;
                return this.sym + Math.round(n * (1 - this.pct / 100));
            },
            isAm(id) { return this.amenities.includes(id) },
            toggleAm(id) { const i = this.amenities.indexOf(id); i >= 0 ? this.amenities.splice(i, 1) : this.amenities.push(id) },
            inc(k, max) { if (this[k] < max) this[k]++ },
            dec(k, min) { if (this[k] > min) this[k]-- },
            onCover(e) { const f = e.target.files[0]; this.coverUrl = f ? URL.createObjectURL(f) : '' },
            onPhoto2(e) { const f = e.target.files[0]; this.photo2Url = f ? URL.createObjectURL(f) : '' },
            go(s) { this.step = s; window.scrollTo({ top: 0, behavior: 'smooth' }) }
        }">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:2px">
                <a class="vy-au-back" href="{{ route('owner.units') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
                    {{ __('livewire/owner/add-unit.my_units') }}
                </a>
                <span class="vy-au-steplabel" x-text="stepLabel">{{ __('livewire/owner/add-unit.step_of', ['step' => 1]) }}</span>
            </div>

            {{-- Enter inside any text field triggers IMPLICIT submission against the step-4 submit button, even
                 while that step is hidden — from step 1 that files a REAL request on the operator's desk with
                 nothing but a name and the default kind (the server requires only those two). A wizard must not
                 be submittable from a step that isn't the last one, so Enter only submits on step 4. --}}
            <form method="POST" action="{{ route('owner.add-unit.submit') }}" enctype="multipart/form-data"
                  @keydown.enter="if (step !== 4) $event.preventDefault()">
                @csrf
                {{-- Stepper / chip values that have no visible named input post through these mirrors --}}
                {{-- DO9-2 — which draft is being continued. Absent on a fresh wizard; only ever a hint, since
                     the server re-resolves it owner-scoped + draft-only. --}}
                @if ($draft)<input type="hidden" name="draft_id" value="{{ $draft['id'] }}">@endif
                <input type="hidden" name="requested_kind" :value="kind">
                <input type="hidden" name="bedrooms" :value="beds">
                <input type="hidden" name="bathrooms" :value="baths">
                <input type="hidden" name="sleeps" :value="sleeps">
                <input type="hidden" name="min_nights" :value="minNights">
                <template x-for="id in amenities" :key="id"><input type="hidden" name="amenities[]" :value="id"></template>

                <div class="vy-au-wrap">
                    {{-- LEFT — the steps --}}
                    <div class="vy-au-left">
                        {{-- Decorative: "Step N of 4" beside the title carries the same information in words. --}}
                        <div class="vy-au-rail" aria-hidden="true">
                            <span class="vy-au-rail-seg" :class="step >= 1 && 'is-on'"></span>
                            <span class="vy-au-rail-seg" :class="step >= 2 && 'is-on'"></span>
                            <span class="vy-au-rail-seg" :class="step >= 3 && 'is-on'"></span>
                            <span class="vy-au-rail-seg" :class="step >= 4 && 'is-on'"></span>
                        </div>

                        <div class="vy-au-card">
                            {{-- STEP 1 — the basics --}}
                            <div x-show="step === 1">
                                <div class="vy-au-steptitle">{{ __('livewire/owner/add-unit.step1_title') }}</div>
                                <div class="vy-au-stepsub">{{ __('livewire/owner/add-unit.step1_sub') }}</div>

                                <div class="vy-au-field">
                                    <label class="vy-au-flabel" for="au-name">{{ __('livewire/owner/add-unit.unit_name') }}</label>
                                    <input id="au-name" name="requested_name" x-model="name" placeholder="{{ __('livewire/owner/add-unit.unit_name_placeholder') }}" class="vy-au-input" maxlength="120" autocomplete="off">
                                </div>

                                <div class="vy-au-field">
                                    <div class="vy-au-flabel" id="au-kind-label">{{ __('livewire/owner/add-unit.property_type') }}</div>
                                    <div class="vy-au-chips" role="group" aria-labelledby="au-kind-label">
                                        @foreach ($kinds as $value => $label)
                                            <button type="button" @click="kind = '{{ $value }}'" :aria-pressed="kind === '{{ $value }}' ? 'true' : 'false'" :class="kind === '{{ $value }}' ? 'vy-au-chip is-sel' : 'vy-au-chip'">{{ $label }}</button>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="vy-au-field">
                                    <label class="vy-au-flabel" for="au-town">{{ __('livewire/owner/add-unit.town_city') }}</label>
                                    <input id="au-town" name="location" x-model="town" placeholder="{{ __('livewire/owner/add-unit.town_placeholder') }}" class="vy-au-input" maxlength="120" autocomplete="off">
                                </div>

                                <div class="vy-au-field" style="margin-bottom:22px">
                                    <label class="vy-au-flabel" for="au-address">{{ __('livewire/owner/add-unit.street_address') }}</label>
                                    <input id="au-address" name="street_address" x-model="address" placeholder="{{ __('livewire/owner/add-unit.street_address_placeholder') }}" class="vy-au-input" maxlength="200" autocomplete="off">
                                </div>

                                <button type="button" @click="canNext1 && go(2)" :disabled="!canNext1" :class="canNext1 ? 'vy-au-cta' : 'vy-au-cta vy-au-cta--wait'">
                                    <span x-text="canNext1 ? @js(__('common.continue')) : @js(__('livewire/owner/add-unit.name_to_continue'))">{{ __('common.continue') }}</span>
                                </button>
                            </div>

                            {{-- STEP 2 — space & capacity --}}
                            <div x-show="step === 2" class="vy-au-step" style="display:none">
                                <div class="vy-au-steptitle">{{ __('livewire/owner/add-unit.step2_title') }}</div>
                                <div class="vy-au-stepsub">{{ __('livewire/owner/add-unit.step2_sub') }}</div>

                                <div class="vy-au-group">
                                    <div class="vy-au-row">
                                        <span class="vy-au-row-name" id="au-beds-label">{{ __('livewire/owner/add-unit.bedrooms') }}</span>
                                        <span class="vy-au-stepper">
                                            <button type="button" class="vy-au-pm" aria-label="{{ __('livewire/owner/add-unit.one_fewer_bedroom') }}" @click="dec('beds', 0)" :disabled="beds <= 0"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M5 12h14"/></svg></button>
                                            <span class="vy-au-num" role="status" aria-labelledby="au-beds-label" x-text="beds">1</span>
                                            <button type="button" class="vy-au-pm" aria-label="{{ __('livewire/owner/add-unit.one_more_bedroom') }}" @click="inc('beds', lim.bedrooms)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14"/><path d="M5 12h14"/></svg></button>
                                        </span>
                                    </div>
                                    <div class="vy-au-row">
                                        <span class="vy-au-row-name" id="au-baths-label">{{ __('livewire/owner/add-unit.bathrooms') }}</span>
                                        <span class="vy-au-stepper">
                                            <button type="button" class="vy-au-pm" aria-label="{{ __('livewire/owner/add-unit.one_fewer_bathroom') }}" @click="dec('baths', 1)" :disabled="baths <= 1"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M5 12h14"/></svg></button>
                                            <span class="vy-au-num" role="status" aria-labelledby="au-baths-label" x-text="baths">1</span>
                                            <button type="button" class="vy-au-pm" aria-label="{{ __('livewire/owner/add-unit.one_more_bathroom') }}" @click="inc('baths', lim.bathrooms)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14"/><path d="M5 12h14"/></svg></button>
                                        </span>
                                    </div>
                                    <div class="vy-au-row">
                                        <span><span class="vy-au-row-name" id="au-sleeps-label">{{ __('livewire/owner/add-unit.sleeps') }}</span><span class="vy-au-row-sub">{{ __('livewire/owner/add-unit.max_guests') }}</span></span>
                                        <span class="vy-au-stepper">
                                            <button type="button" class="vy-au-pm" aria-label="{{ __('livewire/owner/add-unit.one_fewer_guest') }}" @click="dec('sleeps', 1)" :disabled="sleeps <= 1"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M5 12h14"/></svg></button>
                                            <span class="vy-au-num" role="status" aria-labelledby="au-sleeps-label" x-text="sleeps">2</span>
                                            <button type="button" class="vy-au-pm" aria-label="{{ __('livewire/owner/add-unit.one_more_guest') }}" @click="inc('sleeps', lim.sleeps)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14"/><path d="M5 12h14"/></svg></button>
                                        </span>
                                    </div>
                                </div>

                                <div class="vy-au-flabel" style="margin-bottom:10px" id="au-amenities-label">{{ __('livewire/owner/add-unit.amenities') }}</div>
                                @if (count($amenities) > 0)
                                    <div class="vy-au-chips" style="margin-bottom:22px" role="group" aria-labelledby="au-amenities-label">
                                        @foreach ($amenities as $a)
                                            <button type="button" @click="toggleAm('{{ $a['id'] }}')" :aria-pressed="isAm('{{ $a['id'] }}') ? 'true' : 'false'" :class="isAm('{{ $a['id'] }}') ? 'vy-au-chip is-sel' : 'vy-au-chip'">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $amIcons[$a['icon']] ?? $amFallback !!}</svg>
                                                {{ $a['name'] }}
                                            </button>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="vy-au-stepsub" style="margin-bottom:22px">{{ __('livewire/owner/add-unit.amenities_later') }}</div>
                                @endif

                                <button type="button" class="vy-au-cta" @click="go(3)">{{ __('common.continue') }}</button>
                                <button type="button" class="vy-au-cta--ghost" @click="go(1)">{{ __('common.back') }}</button>
                            </div>

                            {{-- STEP 3 — photos --}}
                            <div x-show="step === 3" class="vy-au-step" style="display:none">
                                <div class="vy-au-steptitle">{{ __('livewire/owner/add-unit.step3_title') }}</div>
                                <div class="vy-au-stepsub">{!! __('livewire/owner/add-unit.step3_sub') !!}</div>

                                <div class="vy-au-drops">
                                    <label class="vy-au-drop">
                                        {{-- The visible label is swapped for the chosen image, so the NAME lives on the input itself. --}}
                                        <input type="file" name="photos[]" aria-label="{{ __('livewire/owner/add-unit.cover_photo') }}" accept="image/jpeg,image/png,image/webp" @change="onCover">
                                        <template x-if="coverUrl"><img class="vy-au-drop-img" :src="coverUrl" alt=""></template>
                                        <template x-if="!coverUrl">
                                            <span style="display:flex;flex-direction:column;align-items:center;gap:6px">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="15" rx="2"/><circle cx="9" cy="10" r="2"/><path d="m21 16-4-4-9 7"/></svg>
                                                <span class="vy-au-drop-label">{{ __('livewire/owner/add-unit.cover_photo') }}</span>
                                            </span>
                                        </template>
                                    </label>
                                    <label class="vy-au-drop">
                                        <input type="file" name="photos[]" aria-label="{{ __('livewire/owner/add-unit.another_photo') }}" accept="image/jpeg,image/png,image/webp" @change="onPhoto2">
                                        <template x-if="photo2Url"><img class="vy-au-drop-img" :src="photo2Url" alt=""></template>
                                        <template x-if="!photo2Url">
                                            <span style="display:flex;flex-direction:column;align-items:center;gap:6px">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                                                <span class="vy-au-drop-label">{{ __('livewire/owner/add-unit.another_photo') }}</span>
                                            </span>
                                        </template>
                                    </label>
                                </div>

                                <div class="vy-au-note">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="#B4400F" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                                    <span>{{ __('livewire/owner/add-unit.photos_note', ['operator' => $operatorName]) }}</span>
                                </div>

                                <button type="button" class="vy-au-cta" @click="go(4)">{{ __('common.continue') }}</button>
                                <button type="button" class="vy-au-cta--ghost" @click="go(2)">{{ __('common.back') }}</button>
                            </div>

                            {{-- STEP 4 — rate & terms --}}
                            <div x-show="step === 4" class="vy-au-step" style="display:none">
                                <div class="vy-au-steptitle">{{ __('livewire/owner/add-unit.step4_title') }}</div>
                                <div class="vy-au-stepsub">{{ __('livewire/owner/add-unit.step4_sub') }}</div>

                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px">
                                    <div>
                                        <label class="vy-au-flabel" for="au-nightly">{{ __('livewire/owner/add-unit.base_rate_night') }}</label>
                                        <div class="vy-au-money">
                                            <span class="vy-au-money-cur" aria-hidden="true">{{ $currencySymbol }}</span>
                                            <input id="au-nightly" name="nightly_rate" x-model="nightly" inputmode="decimal" placeholder="0" maxlength="12">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="vy-au-flabel" for="au-cleaning">{{ __('livewire/owner/add-unit.cleaning_fee') }}</label>
                                        <div class="vy-au-money">
                                            <span class="vy-au-money-cur" aria-hidden="true">{{ $currencySymbol }}</span>
                                            <input id="au-cleaning" name="cleaning_fee" x-model="cleaning" inputmode="decimal" placeholder="0" maxlength="12">
                                        </div>
                                    </div>
                                </div>

                                <div class="vy-au-group">
                                    <div class="vy-au-row">
                                        <span><span class="vy-au-row-name" id="au-min-label">{{ __('livewire/owner/add-unit.min_nights') }}</span><span class="vy-au-row-sub">{{ __('livewire/owner/add-unit.min_nights_sub') }}</span></span>
                                        <span class="vy-au-stepper">
                                            <button type="button" class="vy-au-pm" aria-label="{{ __('livewire/owner/add-unit.one_fewer_min_night') }}" @click="dec('minNights', 1)" :disabled="minNights <= 1"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M5 12h14"/></svg></button>
                                            <span class="vy-au-num" role="status" aria-labelledby="au-min-label" x-text="minNights">2</span>
                                            <button type="button" class="vy-au-pm" aria-label="{{ __('livewire/owner/add-unit.one_more_min_night') }}" @click="inc('minNights', lim.min_nights)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14"/><path d="M5 12h14"/></svg></button>
                                        </span>
                                    </div>
                                </div>

                                {{-- DO9-1 — "anything else we should know", the last word before submitting.
                                     ⚠️ THE BACKEND WAS ALREADY READY: OwnerUnitRequestController validates
                                     ['nullable','string','max:2000'] and OwnerUnitRequestService persists it. The
                                     column was simply unreachable — the wizard had no input. Nothing about the
                                     validation or the persistence changed here; this only reaches them.
                                     ⚠️ OPTIONAL, because the rule is `nullable`. Submitting with the box empty
                                     must still file the request — the field is a chance to add context, never a
                                     new obstacle in front of one.
                                     `maxlength` mirrors the server's max:2000 so the limit is felt in the field
                                     rather than discovered as a rejection; the server rule remains authoritative.
                                     Reuses .vy-au-input / .vy-au-flabel — the wizard's own field idiom (§N.8f). --}}
                                <div class="vy-au-field" style="margin-bottom:16px">
                                    <label class="vy-au-flabel" for="au-notes">{!! __('livewire/owner/add-unit.notes_label') !!}</label>
                                    <textarea id="au-notes" name="notes" x-model="notes" class="vy-au-input" rows="3" maxlength="2000"
                                              placeholder="{{ __('livewire/owner/add-unit.notes_placeholder') }}"
                                              style="resize:vertical;min-height:76px;font-family:inherit"></textarea>
                                </div>

                                {{-- Managed-by panel — REAL commission from the owner's agreement, or an honest line when none --}}
                                <div class="vy-au-commission">
                                    <span class="vy-au-commission-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v5c0 4.4-3 8-7 10-4-2-7-5.6-7-10V6z"/><path d="M9 12l2 2 4-4"/></svg></span>
                                    <span class="vy-au-commission-main">
                                        @if ($commissionPct !== null)
                                            <span class="vy-au-commission-l1">{!! __('livewire/owner/add-unit.managed_by_pct', ['operator' => e($operatorName), 'pct' => e(\App\Support\I18n\LocalNumber::decimal(rtrim(rtrim(number_format($commissionPct, 2), '0'), '.')))]) !!}</span>
                                            <span class="vy-au-commission-l2" x-show="netNight">{!! __('livewire/owner/add-unit.you_keep', ['amount' => '<span class="vy-au-commission-net" x-text="netNight"></span>']) !!}</span>
                                            <span class="vy-au-commission-l2" x-show="!netNight">{{ __('livewire/owner/add-unit.enter_rate') }}</span>
                                        @else
                                            <span class="vy-au-commission-l1">{{ __('livewire/owner/add-unit.managed_by', ['operator' => $operatorName]) }}</span>
                                            <span class="vy-au-commission-l2">{{ __('livewire/owner/add-unit.commission_later') }}</span>
                                        @endif
                                    </span>
                                </div>

                                <button type="submit" class="vy-au-cta">{{ __('livewire/owner/add-unit.submit_for_review') }}</button>
                                <button type="button" class="vy-au-cta--ghost" @click="go(3)">{{ __('common.back') }}</button>
                            </div>

                            {{-- DO9-2 — SAVE AS DRAFT, available from every step (that is the point: the wizard
                                 was submit-or-lose). Reuses the wizard's own ghost-button idiom rather than a
                                 new style.
                                 ⚠️ IT SITS AFTER "Submit for review" IN DOM ORDER ON PURPOSE. Implicit
                                 submission (Enter) targets the FIRST submit button in the form, so placing this
                                 earlier would have made Enter on step 4 silently save a draft instead of
                                 submitting — quietly breaking the guard the comment on the <form> describes.
                                 `formaction` sends it to the draft route; one form, two destinations. --}}
                            <div class="vy-au-draftbar">
                                <button type="submit" formaction="{{ route('owner.add-unit.draft') }}" formnovalidate
                                        class="vy-au-cta--ghost" style="width:100%;margin:0">
                                    {{ __('livewire/owner/add-unit.save_as_draft') }}
                                </button>
                                <p class="vy-au-draftnote">
                                    @if ($draft)
                                        @if ($draft['saved_at'])
                                            {{ __('livewire/owner/add-unit.draft_saved_at', ['saved_at' => $draft['saved_at'], 'operator' => $operatorName]) }}
                                        @else
                                            {{ __('livewire/owner/add-unit.draft_saved', ['operator' => $operatorName]) }}
                                        @endif
                                        @if ($draft['photo_count'] > 0)
                                            {{ trans_choice('livewire/owner/add-unit.draft_photos', $draft['photo_count'], ['count' => $draft['photo_count']]) }}
                                        @endif
                                    @else
                                        {{ __('livewire/owner/add-unit.draft_keeps', ['operator' => $operatorName]) }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- RIGHT — live preview --}}
                    <div class="vy-au-preview">
                        <div class="vy-au-preview-label">{{ __('livewire/owner/add-unit.live_preview') }}</div>
                        <div class="vy-au-preview-card">
                            <div class="vy-au-preview-cover">
                                <template x-if="coverUrl"><img :src="coverUrl" alt=""></template>
                                <template x-if="!coverUrl"><span class="vy-au-preview-cover-ph">{{ __('livewire/owner/add-unit.add_cover_photo') }}</span></template>
                                <div class="vy-au-badge"><span></span>{{ __('livewire/owner/add-unit.pending_review') }}</div>
                            </div>
                            <div class="vy-au-preview-body">
                                <div class="vy-au-preview-name" x-text="previewName">{{ __('livewire/owner/add-unit.your_new_unit') }}</div>
                                <div class="vy-au-preview-sub" x-text="previewSub"></div>

                                <div class="vy-au-preview-meta">
                                    <span><svg viewBox="0 0 24 24" fill="none" stroke="#B4400F" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3"/><path d="M2.5 20c.9-3.4 3.6-5.5 6.5-5.5s5.6 2.1 6.5 5.5"/></svg><span x-text="sleeps"></span></span>
                                    <span><svg viewBox="0 0 24 24" fill="none" stroke="#B4400F" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10V7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v3"/><path d="M2 14h20"/><path d="M4 14v5"/><path d="M20 14v5"/></svg><span x-text="@js(__('livewire/owner/add-unit.beds_short', ['beds' => ':beds'])).replace(':beds', beds)"></span></span>
                                    <span><svg viewBox="0 0 24 24" fill="none" stroke="#B4400F" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12V6a2 2 0 0 1 3.5-1.3"/><path d="M4 12h16v2a4 4 0 0 1-4 4H8a4 4 0 0 1-4-4z"/></svg><span x-text="@js(__('livewire/owner/add-unit.baths_short', ['baths' => ':baths'])).replace(':baths', baths)"></span></span>
                                </div>

                                <div class="vy-au-preview-am" x-show="hasAmenities">
                                    <template x-for="nm in previewAmenities" :key="nm"><span class="vy-au-preview-am-chip" x-text="nm"></span></template>
                                </div>

                                <div class="vy-au-preview-rate">
                                    <b x-text="previewRate">{{ $currencySymbol }}—</b><i>{{ __('livewire/owner/add-unit.per_night') }}</i>
                                </div>
                            </div>
                        </div>
                        <div class="vy-au-preview-cap">{!! __('livewire/owner/add-unit.preview_caption', ['operator' => e($operatorName)]) !!}</div>
                    </div>
                </div>
            </form>
        </div>

        {{-- Your requests — REAL, owner-scoped: the pending/decided requests you've already sent (kept from the
             live page; the wireframe omits it, but hiding real pending requests would be a regression). --}}
        @if (count($requests) > 0)
            <section class="vy-card vy-fade" style="margin-top:22px">
                <h2 class="vy-card-title" style="margin-bottom:4px">{{ __('livewire/owner/add-unit.your_requests') }}</h2>
                <p class="vy-line-sub" style="font-size:12px;margin:0 0 12px">{{ __('livewire/owner/add-unit.your_requests_sub') }}</p>
                @foreach ($requests as $r)
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;padding:11px 0;border-bottom:1px solid var(--vy-hairline)">
                        <div>
                            <div style="font-size:13.5px;font-weight:600;color:var(--vy-ink)">{{ $r['name'] }}</div>
                            <div class="vy-line-sub" style="font-size:12px">{{ $r['kind'] }}@if ($r['when']) · {{ $r['when'] }}@endif</div>
                        </div>
                        <div style="display:flex;align-items:center;gap:10px">
                            {{-- DO9-2 — only a draft is resumable; a filed request has no continue link. --}}
                            @if ($r['resume_url'])
                                <a href="{{ $r['resume_url'] }}" class="vy-au-resume">{{ __('common.continue') }}</a>
                            @endif
                            {{-- DO9-3 — retracting a filed request is deliberate, so it confirms first (the
                                 realm's existing onsubmit-confirm idiom, as on Team and Payout method). The
                                 server re-decides under a lock: this control is convenience, not the gate. --}}
                            @if ($r['withdraw_url'])
                                <form method="POST" action="{{ $r['withdraw_url'] }}" style="margin:0" data-guard-submit
                                      onsubmit="return confirm(@js(__('livewire/owner/add-unit.withdraw_confirm')))">
                                    @csrf
                                    <button type="submit" class="vy-au-withdraw">{{ __('livewire/owner/add-unit.withdraw') }}</button>
                                </form>
                            @endif
                            <span class="vy-status vy-status--{{ $r['status_meta']['tone'] }}"><span class="vy-dot"></span>{{ $r['status_meta']['label'] }}</span>
                        </div>
                    </div>
                @endforeach
            </section>
        @endif
    @endunless
</x-owner.shell>
