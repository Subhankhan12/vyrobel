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
<x-owner.shell active="add-unit" title="Add a unit" :viewer="$viewer">

    @if ($errors->any())
        <div class="vy-card vy-fade" style="border-left:3px solid var(--vy-accent);margin-bottom:16px">
            <div style="font-size:13px;color:var(--vy-ink)">{{ $errors->first() }}</div>
        </div>
    @endif

    @unless ($linked)
        <a class="vy-au-back" href="{{ route('owner.units') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
            My units
        </a>
        <div class="vy-card vy-fade"><div class="vy-empty">No owner account is linked to your login yet.</div></div>
    @elseif ($done)
        {{-- DONE — server-rendered after the PRG redirect from a REAL submission --}}
        <a class="vy-au-back" href="{{ route('owner.units') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
            My units
        </a>
        <div class="vy-au-done vy-fade">
            <div class="vy-au-done-card">
                <div class="vy-au-done-badge">
                    <div class="vy-au-done-badge-glow"></div>
                    <div class="vy-au-done-badge-disc">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    </div>
                </div>
                <div class="vy-au-done-title">Request received</div>
                <div class="vy-au-done-sub">Thanks, {{ $firstName }}. {{ $operatorName }} reviews new units and comes back to you within two business days.</div>

                <div class="vy-au-checklist">
                    <div class="vy-au-check">
                        <span class="vy-au-check-ico vy-au-check-ico--done"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 12.5 9.5 17.5 19.5 7"/></svg></span>
                        <span class="vy-au-check-txt">Submitted — <b>{{ $done['name'] }}</b>@if ($done['location'] !== '') in {{ $done['location'] }}@endif</span>
                    </div>
                    <div class="vy-au-check">
                        <span class="vy-au-check-ico vy-au-check-ico--wait"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/></svg></span>
                        <span class="vy-au-check-txt">Your team verifies the details, sets up channels &amp; pricing, and schedules photos if needed</span>
                    </div>
                    <div class="vy-au-check">
                        <span class="vy-au-check-ico vy-au-check-ico--home"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/></svg></span>
                        <span class="vy-au-check-txt">Once live, it joins <b>My units</b> and starts taking bookings</span>
                    </div>
                </div>

                <a class="vy-au-cta" href="{{ route('owner.units') }}">Back to My units</a>
                <div class="vy-au-done-actions">
                    <a class="vy-au-cta--ghost" href="{{ route('owner.add-unit') }}">Add another</a>
                    <a class="vy-au-cta--ghost" href="{{ route('owner.messages') }}">Message the team</a>
                </div>
            </div>
        </div>
    @else
        {{-- THE WIZARD --}}
        <div x-data="{
            step: 1,
            name: '', kind: @js(array_key_first($kinds)), town: '', address: '',
            beds: 1, baths: 1, sleeps: 2, minNights: 2,
            amenities: [], nightly: '', cleaning: '', coverUrl: '', photo2Url: '',
            pct: @js($commissionPct), sym: @js($currencySymbol), lim: @js($limits),
            kindLabels: @js($kinds), amNames: @js(collect($amenities)->pluck('name', 'id')),
            get canNext1() { return this.name.trim().length > 0 },
            get stepLabel() { return 'Step ' + this.step + ' of 4' },
            get kindLabel() { return this.kindLabels[this.kind] || '' },
            get previewName() { return this.name.trim() || 'Your new unit' },
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
                    My units
                </a>
                <span class="vy-au-steplabel" x-text="stepLabel">Step 1 of 4</span>
            </div>

            <form method="POST" action="{{ route('owner.add-unit.submit') }}" enctype="multipart/form-data">
                @csrf
                {{-- Stepper / chip values that have no visible named input post through these mirrors --}}
                <input type="hidden" name="requested_kind" :value="kind">
                <input type="hidden" name="bedrooms" :value="beds">
                <input type="hidden" name="bathrooms" :value="baths">
                <input type="hidden" name="sleeps" :value="sleeps">
                <input type="hidden" name="min_nights" :value="minNights">
                <template x-for="id in amenities" :key="id"><input type="hidden" name="amenities[]" :value="id"></template>

                <div class="vy-au-wrap">
                    {{-- LEFT — the steps --}}
                    <div class="vy-au-left">
                        <div class="vy-au-rail">
                            <span class="vy-au-rail-seg" :class="step >= 1 && 'is-on'"></span>
                            <span class="vy-au-rail-seg" :class="step >= 2 && 'is-on'"></span>
                            <span class="vy-au-rail-seg" :class="step >= 3 && 'is-on'"></span>
                            <span class="vy-au-rail-seg" :class="step >= 4 && 'is-on'"></span>
                        </div>

                        <div class="vy-au-card">
                            {{-- STEP 1 — the basics --}}
                            <div x-show="step === 1">
                                <div class="vy-au-steptitle">The basics</div>
                                <div class="vy-au-stepsub">Tell us what you’re listing and where it is.</div>

                                <div class="vy-au-field">
                                    <div class="vy-au-flabel">Unit name</div>
                                    <input name="requested_name" x-model="name" placeholder="e.g. Villa Marisol" class="vy-au-input" maxlength="120" autocomplete="off">
                                </div>

                                <div class="vy-au-field">
                                    <div class="vy-au-flabel">Property type</div>
                                    <div class="vy-au-chips">
                                        @foreach ($kinds as $value => $label)
                                            <button type="button" @click="kind = '{{ $value }}'" :class="kind === '{{ $value }}' ? 'vy-au-chip is-sel' : 'vy-au-chip'">{{ $label }}</button>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="vy-au-field">
                                    <div class="vy-au-flabel">Town / city</div>
                                    <input name="location" x-model="town" placeholder="e.g. Split" class="vy-au-input" maxlength="120" autocomplete="off">
                                </div>

                                <div class="vy-au-field" style="margin-bottom:22px">
                                    <div class="vy-au-flabel">Street address</div>
                                    <input name="street_address" x-model="address" placeholder="Street &amp; number" class="vy-au-input" maxlength="200" autocomplete="off">
                                </div>

                                <button type="button" @click="canNext1 && go(2)" :disabled="!canNext1" :class="canNext1 ? 'vy-au-cta' : 'vy-au-cta vy-au-cta--wait'">
                                    <span x-text="canNext1 ? 'Continue' : 'Name your unit to continue'">Continue</span>
                                </button>
                            </div>

                            {{-- STEP 2 — space & capacity --}}
                            <div x-show="step === 2" class="vy-au-step" style="display:none">
                                <div class="vy-au-steptitle">Space &amp; capacity</div>
                                <div class="vy-au-stepsub">How big is it, and what does it offer?</div>

                                <div class="vy-au-group">
                                    <div class="vy-au-row">
                                        <span class="vy-au-row-name">Bedrooms</span>
                                        <span class="vy-au-stepper">
                                            <button type="button" class="vy-au-pm" @click="dec('beds', 0)" :disabled="beds <= 0"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M5 12h14"/></svg></button>
                                            <span class="vy-au-num" x-text="beds">1</span>
                                            <button type="button" class="vy-au-pm" @click="inc('beds', lim.bedrooms)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg></button>
                                        </span>
                                    </div>
                                    <div class="vy-au-row">
                                        <span class="vy-au-row-name">Bathrooms</span>
                                        <span class="vy-au-stepper">
                                            <button type="button" class="vy-au-pm" @click="dec('baths', 1)" :disabled="baths <= 1"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M5 12h14"/></svg></button>
                                            <span class="vy-au-num" x-text="baths">1</span>
                                            <button type="button" class="vy-au-pm" @click="inc('baths', lim.bathrooms)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg></button>
                                        </span>
                                    </div>
                                    <div class="vy-au-row">
                                        <span><span class="vy-au-row-name">Sleeps</span><span class="vy-au-row-sub">Maximum guests</span></span>
                                        <span class="vy-au-stepper">
                                            <button type="button" class="vy-au-pm" @click="dec('sleeps', 1)" :disabled="sleeps <= 1"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M5 12h14"/></svg></button>
                                            <span class="vy-au-num" x-text="sleeps">2</span>
                                            <button type="button" class="vy-au-pm" @click="inc('sleeps', lim.sleeps)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg></button>
                                        </span>
                                    </div>
                                </div>

                                <div class="vy-au-flabel" style="margin-bottom:10px">Amenities</div>
                                @if (count($amenities) > 0)
                                    <div class="vy-au-chips" style="margin-bottom:22px">
                                        @foreach ($amenities as $a)
                                            <button type="button" @click="toggleAm('{{ $a['id'] }}')" :class="isAm('{{ $a['id'] }}') ? 'vy-au-chip is-sel' : 'vy-au-chip'">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $amIcons[$a['icon']] ?? $amFallback !!}</svg>
                                                {{ $a['name'] }}
                                            </button>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="vy-au-stepsub" style="margin-bottom:22px">Your team will confirm amenities when they set the unit up.</div>
                                @endif

                                <button type="button" class="vy-au-cta" @click="go(3)">Continue</button>
                                <button type="button" class="vy-au-cta--ghost" @click="go(1)">Back</button>
                            </div>

                            {{-- STEP 3 — photos --}}
                            <div x-show="step === 3" class="vy-au-step" style="display:none">
                                <div class="vy-au-steptitle">Photos</div>
                                <div class="vy-au-stepsub">Add a <b style="font-weight:500;color:var(--vy-ink)">cover photo</b> — it appears first — then one more if you like.</div>

                                <div class="vy-au-drops">
                                    <label class="vy-au-drop">
                                        <input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" @change="onCover">
                                        <template x-if="coverUrl"><img class="vy-au-drop-img" :src="coverUrl" alt=""></template>
                                        <template x-if="!coverUrl">
                                            <span style="display:flex;flex-direction:column;align-items:center;gap:6px">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="15" rx="2"/><circle cx="9" cy="10" r="2"/><path d="m21 16-4-4-9 7"/></svg>
                                                <span class="vy-au-drop-label">Cover photo</span>
                                            </span>
                                        </template>
                                    </label>
                                    <label class="vy-au-drop">
                                        <input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" @change="onPhoto2">
                                        <template x-if="photo2Url"><img class="vy-au-drop-img" :src="photo2Url" alt=""></template>
                                        <template x-if="!photo2Url">
                                            <span style="display:flex;flex-direction:column;align-items:center;gap:6px">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                                                <span class="vy-au-drop-label">Another photo</span>
                                            </span>
                                        </template>
                                    </label>
                                </div>

                                <div class="vy-au-note">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="#B4400F" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                                    <span>Bright, landscape photos convert best. {{ $operatorName }} can arrange a professional shoot after approval — just ask.</span>
                                </div>

                                <button type="button" class="vy-au-cta" @click="go(4)">Continue</button>
                                <button type="button" class="vy-au-cta--ghost" @click="go(2)">Back</button>
                            </div>

                            {{-- STEP 4 — rate & terms --}}
                            <div x-show="step === 4" class="vy-au-step" style="display:none">
                                <div class="vy-au-steptitle">Rate &amp; terms</div>
                                <div class="vy-au-stepsub">Set a starting rate — your revenue team fine-tunes it against demand.</div>

                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px">
                                    <div>
                                        <div class="vy-au-flabel">Base rate / night</div>
                                        <div class="vy-au-money">
                                            <span class="vy-au-money-cur">{{ $currencySymbol }}</span>
                                            <input name="nightly_rate" x-model="nightly" inputmode="decimal" placeholder="0" maxlength="12">
                                        </div>
                                    </div>
                                    <div>
                                        <div class="vy-au-flabel">Cleaning fee</div>
                                        <div class="vy-au-money">
                                            <span class="vy-au-money-cur">{{ $currencySymbol }}</span>
                                            <input name="cleaning_fee" x-model="cleaning" inputmode="decimal" placeholder="0" maxlength="12">
                                        </div>
                                    </div>
                                </div>

                                <div class="vy-au-group">
                                    <div class="vy-au-row">
                                        <span><span class="vy-au-row-name">Minimum nights</span><span class="vy-au-row-sub">Shortest stay you’ll accept</span></span>
                                        <span class="vy-au-stepper">
                                            <button type="button" class="vy-au-pm" @click="dec('minNights', 1)" :disabled="minNights <= 1"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M5 12h14"/></svg></button>
                                            <span class="vy-au-num" x-text="minNights">2</span>
                                            <button type="button" class="vy-au-pm" @click="inc('minNights', lim.min_nights)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg></button>
                                        </span>
                                    </div>
                                </div>

                                {{-- Managed-by panel — REAL commission from the owner's agreement, or an honest line when none --}}
                                <div class="vy-au-commission">
                                    <span class="vy-au-commission-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v5c0 4.4-3 8-7 10-4-2-7-5.6-7-10V6z"/><path d="M9 12l2 2 4-4"/></svg></span>
                                    <span class="vy-au-commission-main">
                                        @if ($commissionPct !== null)
                                            <span class="vy-au-commission-l1">Managed by {{ $operatorName }} · <span style="font-weight:600">{{ rtrim(rtrim(number_format($commissionPct, 2), '0'), '.') }}% commission</span></span>
                                            <span class="vy-au-commission-l2" x-show="netNight">You keep about <span class="vy-au-commission-net" x-text="netNight"></span> per night after commission</span>
                                            <span class="vy-au-commission-l2" x-show="!netNight">Enter a rate to see what you’d keep after commission</span>
                                        @else
                                            <span class="vy-au-commission-l1">Managed by {{ $operatorName }}</span>
                                            <span class="vy-au-commission-l2">Your team confirms the commission when they set up your unit.</span>
                                        @endif
                                    </span>
                                </div>

                                <button type="submit" class="vy-au-cta">Submit for review</button>
                                <button type="button" class="vy-au-cta--ghost" @click="go(3)">Back</button>
                            </div>
                        </div>
                    </div>

                    {{-- RIGHT — live preview --}}
                    <div class="vy-au-preview">
                        <div class="vy-au-preview-label">Live preview</div>
                        <div class="vy-au-preview-card">
                            <div class="vy-au-preview-cover">
                                <template x-if="coverUrl"><img :src="coverUrl" alt=""></template>
                                <template x-if="!coverUrl"><span class="vy-au-preview-cover-ph">Add a cover photo</span></template>
                                <div class="vy-au-badge"><span></span>Pending review</div>
                            </div>
                            <div class="vy-au-preview-body">
                                <div class="vy-au-preview-name" x-text="previewName">Your new unit</div>
                                <div class="vy-au-preview-sub" x-text="previewSub"></div>

                                <div class="vy-au-preview-meta">
                                    <span><svg viewBox="0 0 24 24" fill="none" stroke="#B4400F" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3"/><path d="M2.5 20c.9-3.4 3.6-5.5 6.5-5.5s5.6 2.1 6.5 5.5"/></svg><span x-text="sleeps"></span></span>
                                    <span><svg viewBox="0 0 24 24" fill="none" stroke="#B4400F" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10V7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v3"/><path d="M2 14h20"/><path d="M4 14v5"/><path d="M20 14v5"/></svg><span x-text="beds + ' bd'"></span></span>
                                    <span><svg viewBox="0 0 24 24" fill="none" stroke="#B4400F" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12V6a2 2 0 0 1 3.5-1.3"/><path d="M4 12h16v2a4 4 0 0 1-4 4H8a4 4 0 0 1-4-4z"/></svg><span x-text="baths + ' ba'"></span></span>
                                </div>

                                <div class="vy-au-preview-am" x-show="hasAmenities">
                                    <template x-for="nm in previewAmenities" :key="nm"><span class="vy-au-preview-am-chip" x-text="nm"></span></template>
                                </div>

                                <div class="vy-au-preview-rate">
                                    <b x-text="previewRate">{{ $currencySymbol }}—</b><i>/ night</i>
                                </div>
                            </div>
                        </div>
                        <div class="vy-au-preview-cap">This is how your unit will appear<br>once {{ $operatorName }} approves it.</div>
                    </div>
                </div>
            </form>
        </div>

        {{-- Your requests — REAL, owner-scoped: the pending/decided requests you've already sent (kept from the
             live page; the wireframe omits it, but hiding real pending requests would be a regression). --}}
        @if (count($requests) > 0)
            <section class="vy-card vy-fade" style="margin-top:22px">
                <h2 class="vy-card-title" style="margin-bottom:4px">Your requests</h2>
                <p class="vy-line-sub" style="font-size:12px;margin:0 0 12px">Requests you’ve sent and where they stand.</p>
                @foreach ($requests as $r)
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;padding:11px 0;border-bottom:1px solid var(--vy-hairline)">
                        <div>
                            <div style="font-size:13.5px;font-weight:600;color:var(--vy-ink)">{{ $r['name'] }}</div>
                            <div class="vy-line-sub" style="font-size:12px">{{ $r['kind'] }}@if ($r['when']) · {{ $r['when'] }}@endif</div>
                        </div>
                        <span class="vy-status vy-status--{{ $r['status_meta']['tone'] }}"><span class="vy-dot"></span>{{ $r['status_meta']['label'] }}</span>
                    </div>
                @endforeach
            </section>
        @endif
    @endunless
</x-owner.shell>
