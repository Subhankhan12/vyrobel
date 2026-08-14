{{-- Operator GUEST PROFILE — the CRM detail (#87 exact-match), on the shared operator design system. EVERY
     control writes: each form is a real CSRF POST → GuestActionController → GuestService → PRG. Full contact is
     shown + editable (the operator is the manager; the #28 name-only rule is owner-only). See
     App\Livewire\Operator\GuestProfile for the REAL / DERIVED map.

     RAW-BLADE RULE: every `@` follows a space, newline or tag. The guest's EMAIL renders here (a literal `@`)
     but is echoed through {{ }} as data, never written into the template. --}}
<div class="vy-gst">
    <x-operator.shell active="guests" :title="$guest['name']" :subtitle="$history['stat'] ?? 'No stays yet'" :viewer="$viewer">

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
                <div class="vy-left"><a class="vy-convo-back" href="{{ route('app.guests') }}">‹ Guests</a></div>
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
                                @if ($guest['since_year'] !== null) <span class="vy-sep">·</span><span>guest since {{ $guest['since_year'] }}</span> @endif
                            </div>
                            <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap; margin-top: 8px;">
                                @if ($guest['stage_meta'] !== null)
                                    <span class="vy-tag tone-{{ $guest['stage_meta']['tone'] }}"><span class="vy-dot"></span>{{ $guest['stage_meta']['label'] }}</span>
                                @endif
                                @if ($guest['is_vip']) <span class="vy-tag tone-vip"><span class="vy-dot"></span>VIP</span> @endif
                                @if ($guest['risk_flag']) <span class="vy-tag tone-risk"><span class="vy-dot"></span>At risk</span> @endif
                                @if ($guest['repeat']) <span class="vy-tag tone-tag"><span class="vy-dot"></span>Repeat guest</span> @endif
                            </div>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        @unless ($guest['anonymized'])
                            <form method="POST" action="{{ route('app.guests.message', $guest['id']) }}" style="display: inline;">
                                @csrf
                                <button type="submit" class="vy-act vy-act--ghost">Message</button>
                            </form>
                            <button type="button" class="vy-act vy-act--ghost" @click="editOpen = true">Edit profile</button>
                        @endunless
                        @if ($guest['current_stay'] !== null)
                            <a class="vy-act vy-act--accent" href="{{ route('app.reservations.show', $guest['current_stay']) }}">Current stay</a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ── Stat strip — Stays · Nights · Avg/night (+ source split) ─────────────────────────────── --}}
            <div class="vy-dash-kpis vy-dash-kpis--3 vy-fade" style="margin-top: 18px;">
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">Stays</div>
                    <div class="vy-dk-val">{{ $history['stays'] }}</div>
                    <div class="vy-dk-sub">cancellations excluded</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">Nights</div>
                    <div class="vy-dk-val">{{ $history['nights'] > 0 ? $history['nights'] : '—' }}</div>
                    <div class="vy-dk-sub">across every stay</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">Avg / night</div>
                    <div class="vy-dk-val">{{ $spend['avg_per_night'] ?? '—' }}</div>
                    <div class="vy-dk-sub">{{ $guest['source_mix'] ?? 'no stays yet' }}</div>
                </div>
            </div>

            <div class="vy-detail-cols" style="margin-top: 18px;">
                <div class="vy-stack">

                    {{-- ── Stay history — with the real per-stay refund line ─────────────────────────── --}}
                    <div class="vy-card">
                        <div class="vy-detail-head" style="margin-bottom: 10px;">
                            <h2 class="vy-card-title">Stay history</h2>
                            <span class="vy-eyebrow">{{ $history['stays'] }} {{ \Illuminate\Support\Str::plural('stay', $history['stays']) }}</span>
                        </div>
                        @forelse ($stays as $stay)
                            <div class="vy-unit-row">
                                <div style="min-width: 0;">
                                    <div class="vy-um-name">
                                        <a class="vy-arr-link" href="{{ route('app.reservations.show', $stay['id']) }}">{{ $stay['check_in'] }} – {{ $stay['check_out'] }}</a>
                                        @if ($stay['in_house']) <span class="vy-tag tone-in-house" style="margin-left: 6px;"><span class="vy-dot"></span>Here now</span> @endif
                                    </div>
                                    <div class="vy-um-sub">
                                        {{ $stay['reference'] }}
                                        <span class="vy-sep">·</span>{{ $stay['nights'] }} {{ \Illuminate\Support\Str::plural('night', $stay['nights']) }}
                                        @if ($stay['unit'] !== null) <span class="vy-sep">·</span>{{ $stay['unit'] }} @endif
                                        <span class="vy-sep">·</span>{{ $stay['source'] }}
                                    </div>
                                </div>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="text-align: right;">
                                        <span class="vy-ledger-amt tone-charge">{{ $stay['total'] }}</span>
                                        @if ($stay['refund'] !== null)
                                            <div class="vy-balance is-due" style="font-size: 11px;">− {{ $stay['refund'] }} refund</div>
                                        @endif
                                    </div>
                                    <span class="vy-tag tone-{{ $stay['stage_meta']['tone'] }}"><span class="vy-dot"></span>{{ $stay['stage_meta']['label'] }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="vy-empty">No stays yet — this profile has never been booked.</div>
                        @endforelse
                    </div>

                    {{-- ── Latest review — real (#76); honest empty state, never a fabricated 5.0 ─────── --}}
                    <div class="vy-card">
                        <div class="vy-detail-head" style="margin-bottom: 10px;">
                            <h2 class="vy-card-title">Latest review</h2>
                            <a class="vy-eyebrow vy-arr-link" href="{{ route('app.reviews') }}">All reviews</a>
                        </div>
                        @if ($review !== null)
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span role="img" aria-label="{{ number_format($review['rating'], 1) }} out of 5 stars" style="font-size: 18px; color: #8F5A06; letter-spacing: 2px;"><span aria-hidden="true">{{ $review['stars'] }}</span></span>
                                <span style="font-weight: 700; color: var(--vy-ink);">{{ number_format($review['rating'], 1) }}</span>
                                @if ($review['status'] !== 'published')
                                    <span class="vy-tag tone-tag" style="margin-left: auto;"><span class="vy-dot"></span>{{ ucfirst($review['status']) }}</span>
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
                                <div class="vy-recon" style="margin-top: 8px;">Your response: {{ $review['response'] }}</div>
                            @endif
                        @else
                            <div class="vy-empty">No reviews yet.</div>
                        @endif
                    </div>

                    {{-- ── Notes — the shared polymorphic table; this page its first writer AND reader ── --}}
                    <div class="vy-card">
                        <div class="vy-detail-head" style="margin-bottom: 10px;">
                            <h2 class="vy-card-title">Notes</h2>
                            <span class="vy-eyebrow">internal only</span>
                        </div>
                        @unless ($guest['anonymized'])
                            <form method="POST" action="{{ route('app.guests.notes.store', $guest['id']) }}" class="vy-input-group" style="margin-bottom: 14px;">
                                @csrf
                                <input class="vy-input" type="text" name="body" maxlength="2000" placeholder="Add an internal note…" aria-label="Add an internal note" required>
                                <button type="submit" class="vy-btn vy-btn--accent">Add note</button>
                            </form>
                        @endunless
                        @forelse ($notes as $note)
                            <div class="vy-unit-row">
                                <div style="min-width: 0;">
                                    @if ($note['redacted'])
                                        <div class="vy-um-name vy-muted">Redacted — this guest has been anonymized</div>
                                    @else
                                        <div class="vy-um-name" style="white-space: normal;">{{ $note['body'] }}</div>
                                    @endif
                                    <div class="vy-um-sub">{{ $note['author'] }} <span class="vy-sep">·</span>{{ $note['created'] }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="vy-empty">No notes yet.</div>
                        @endforelse
                    </div>
                </div>

                <div class="vy-stack">

                    {{-- ── Lifetime value — the dark hero (billed + open folio), reconciling to the ledger ── --}}
                    <div class="vy-tonight" style="padding: 20px;">
                        <div class="vy-dk-label" style="color: rgba(255,255,255,.7);">Lifetime value</div>
                        @forelse ($spend['billed'] as $line)
                            <div style="font-family: 'Sora', sans-serif; font-weight: 700; color: #fff; @if ($loop->first) font-size: 30px; @else font-size: 19px; @endif">{{ $line }}</div>
                        @empty
                            <div style="font-family: 'Sora', sans-serif; font-weight: 700; color: #fff; font-size: 30px;">—</div>
                        @endforelse
                        <div style="font-size: 12.5px; color: rgba(255,255,255,.7); margin-top: 6px;">
                            {{ $history['stays'] }} {{ \Illuminate\Support\Str::plural('stay', $history['stays']) }} · {{ $history['nights'] }} {{ \Illuminate\Support\Str::plural('night', $history['nights']) }}
                            {{ $spend['avg_per_night'] !== null ? '· avg '.$spend['avg_per_night'].' a night' : '' }}
                        </div>
                        @if ($spend['balance'] !== [])
                            <div style="border-top: 1px solid rgba(255,255,255,.14); margin-top: 14px; padding-top: 12px;">
                                @foreach ($spend['balance'] as $line)
                                    <div style="color: #F6C6B4; font-weight: 600;">{{ $line }} open folio</div>
                                @endforeach
                                <div style="font-size: 11.5px; color: rgba(255,255,255,.6); margin-top: 2px;">settles at checkout</div>
                            </div>
                        @endif
                    </div>

                    {{-- ── Contact — email/phone/city + prefers-email + marketing-consent ─────────────── --}}
                    <div class="vy-card">
                        <h2 class="vy-card-title" style="margin-bottom: 10px;">Contact</h2>
                        <div class="vy-facts">
                            <div class="vy-fact-label">Email</div>
                            <div class="vy-fact-val">@if ($guest['email'] !== null) <a href="mailto:{{ $guest['email'] }}">{{ $guest['email'] }}</a> @else <span class="vy-muted">—</span> @endif</div>
                            <div class="vy-fact-label">Phone</div>
                            <div class="vy-fact-val">{{ $guest['phone'] ?? '—' }}</div>
                            <div class="vy-fact-label">City</div>
                            <div class="vy-fact-val">{{ $guest['city'] ?? '—' }}</div>
                        </div>
                        <div class="vy-recon" style="margin-top: 10px;">
                            {{ $guest['prefers_email'] ? 'Prefers email' : 'No channel preference set' }} ·
                            {{ $guest['marketing_consent'] ? 'marketing consent on file' : 'no marketing consent' }}
                        </div>
                    </div>

                    {{-- ── Preferences & notes — real structured prefs, shown to HK & front desk ──────── --}}
                    <div class="vy-card">
                        <h2 class="vy-card-title" style="margin-bottom: 4px;">Preferences</h2>
                        <p style="font-size: 12px; color: var(--vy-muted); margin: 0 0 12px;">Shown to housekeeping &amp; the front desk.</p>
                        @if ($guest['preferences'] !== [])
                            <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                @foreach ($guest['preferences'] as $key)
                                    <span class="vy-tag tone-tag"><span class="vy-dot"></span>{{ $prefCatalog[$key] ?? $key }}</span>
                                @endforeach
                            </div>
                        @else
                            <div class="vy-empty" style="padding: 12px;">No preferences set — add them from Edit profile.</div>
                        @endif
                    </div>

                    {{-- ── Tags — the tenant's REAL catalog, created on first use ──────────────────────── --}}
                    <div class="vy-card">
                        <h2 class="vy-card-title" style="margin-bottom: 10px;">Tags</h2>
                        @if ($guest['tags'] !== [])
                            <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap; margin-bottom: 12px;">
                                @foreach ($guest['tags'] as $tag)
                                    <form method="POST" action="{{ route('app.guests.tags.destroy', $guest['id']) }}" style="display: inline;">
                                        @csrf @method('DELETE')
                                        <input type="hidden" name="tag" value="{{ $tag['id'] }}">
                                        <button type="submit" class="vy-tag tone-tag" style="border: none; cursor: pointer;" title="Remove this tag">
                                            <span class="vy-dot"></span>{{ $tag['name'] }} ×
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        @endif
                        @unless ($guest['anonymized'])
                            <form method="POST" action="{{ route('app.guests.tags.store', $guest['id']) }}" class="vy-input-group">
                                @csrf
                                <input class="vy-input" type="text" name="name" maxlength="255" placeholder="Add a tag…" aria-label="Add a tag" required>
                                <button type="submit" class="vy-btn vy-btn--ghost">Add</button>
                            </form>
                            <div class="vy-recon">Tags are shared across this workspace — a new name is added to the catalogue.</div>
                        @endunless
                    </div>

                    @if ($guest['anonymized'])
                        <div class="vy-card">
                            <div class="vy-empty" style="padding: 20px;">This guest has been anonymized. Their details cannot be edited — the profile is kept only so their financial history stays intact.</div>
                        </div>
                    @endif

                    {{-- ── GDPR ERASURE — gated, server-confirmed ────────────────────────────────────── --}}
                    @can('guests.erase')
                        @unless ($guest['anonymized'])
                            <div class="vy-card">
                                <h2 class="vy-card-title" style="margin-bottom: 6px;">Erase this guest</h2>
                                <div class="vy-recon" style="margin-top: 0;">
                                    Right to erasure. This <strong>permanently</strong> destroys their name, contact details, ID
                                    documents, notes and message history. It <strong>cannot be undone</strong>. Their stays and
                                    financial records are kept — without their identity — so the books still balance.
                                </div>
                                <form method="POST" action="{{ route('app.guests.anonymize', $guest['id']) }}" style="margin-top: 12px;"
                                      onsubmit="return confirm('Permanently erase this guest? Their personal data cannot be recovered.');">
                                    @csrf
                                    <label class="vy-field-label" for="confirm">Type ERASE to confirm</label>
                                    <div class="vy-input-group">
                                        <input class="vy-input" type="text" id="confirm" name="confirm" autocomplete="off" placeholder="ERASE">
                                        <button type="submit" class="vy-btn vy-btn--ghost">Erase</button>
                                    </div>
                                </form>
                            </div>
                        @endunless
                    @endcan
                </div>
            </div>

            {{-- ── EDIT PROFILE modal — the real write (name/email/phone/city/prefs/toggles) ──────────── --}}
            @unless ($guest['anonymized'])
                <div x-show="editOpen" x-cloak @keydown.escape.window="editOpen = false"
                     style="position: fixed; inset: 0; z-index: 50; background: rgba(43,35,32,.4); backdrop-filter: blur(3px); display: flex; align-items: center; justify-content: center; padding: 20px;">
                    <div @click.outside="editOpen = false" class="vy-card vy-modal-card vy-fade" style="width: 100%; max-width: 480px; max-height: 90vh; overflow: auto;">
                        <h2 class="vy-card-title" style="margin-bottom: 12px;">Edit profile</h2>
                        <form method="POST" action="{{ route('app.guests.update', $guest['id']) }}">
                            @csrf
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <div><label class="vy-field-label" for="first_name">First name</label><input class="vy-input" id="first_name" type="text" name="first_name" maxlength="255" value="{{ old('first_name', $guest['first_name']) }}" style="width: 100%;"></div>
                                <div><label class="vy-field-label" for="last_name">Last name</label><input class="vy-input" id="last_name" type="text" name="last_name" maxlength="255" value="{{ old('last_name', $guest['last_name']) }}" style="width: 100%;"></div>
                                <div><label class="vy-field-label" for="email">Email</label><input class="vy-input" id="email" type="email" name="email" maxlength="255" value="{{ old('email', $guest['email']) }}" style="width: 100%;"></div>
                                <div><label class="vy-field-label" for="phone">Phone</label><input class="vy-input" id="phone" type="text" name="phone" maxlength="64" value="{{ old('phone', $guest['phone']) }}" style="width: 100%;"></div>
                                <div><label class="vy-field-label" for="city">City / country</label><input class="vy-input" id="city" type="text" name="city" maxlength="255" value="{{ old('city', $guest['city']) }}" style="width: 100%;"></div>
                                <div><label class="vy-field-label" for="nat">Nationality</label><input class="vy-input" id="nat" type="text" name="nationality" maxlength="2" value="{{ old('nationality', $guest['nationality_code']) }}" style="width: 100%; text-transform: uppercase;"></div>
                            </div>

                            {{-- Preferences — the real structured store; shown to HK & front desk --}}
                            <div style="margin-top: 14px;">
                                <label class="vy-field-label" style="display: flex; justify-content: space-between;"><span>Preferences</span><span style="text-transform: none; font-weight: 500; color: var(--vy-faint);">Shown to housekeeping &amp; front desk</span></label>
                                <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-top: 6px;">
                                    @foreach ($prefCatalog as $key => $label)
                                        <label class="vy-tag" style="cursor: pointer; border: 1px solid var(--vy-hairline);">
                                            <input type="checkbox" name="preferences[]" value="{{ $key }}" @checked(in_array($key, $guest['preferences'], true)) style="margin-right: 5px;">
                                            {{ $label }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 14px;">
                                <label class="vy-field-label" style="display: flex; align-items: center; gap: 8px; text-transform: none; letter-spacing: 0;"><input type="checkbox" name="is_vip" value="1" @checked(old('is_vip', $guest['is_vip']))> VIP guest <span style="color: var(--vy-faint); font-weight: 500;">· flags arrivals &amp; the front desk</span></label>
                                <label class="vy-field-label" style="display: flex; align-items: center; gap: 8px; text-transform: none; letter-spacing: 0;"><input type="checkbox" name="risk_flag" value="1" @checked(old('risk_flag', $guest['risk_flag']))> Flagged at risk</label>
                                <label class="vy-field-label" style="display: flex; align-items: center; gap: 8px; text-transform: none; letter-spacing: 0;"><input type="checkbox" name="prefers_email" value="1" @checked(old('prefers_email', $guest['prefers_email']))> Prefers email contact</label>
                                <label class="vy-field-label" style="display: flex; align-items: center; gap: 8px; text-transform: none; letter-spacing: 0;"><input type="checkbox" name="marketing_consent" value="1" @checked(old('marketing_consent', $guest['marketing_consent']))> Marketing consent <span style="color: var(--vy-faint); font-weight: 500;">· governs marketing email</span></label>
                            </div>
                            <p class="vy-recon" style="margin-top: 8px;">Contact changes apply to future confirmations, not emails already sent.</p>

                            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 16px;">
                                <button type="button" class="vy-btn vy-btn--ghost" @click="editOpen = false">Cancel</button>
                                <button type="submit" class="vy-btn vy-btn--accent">Save changes</button>
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
