{{-- Operator GUESTS LIST — the CRM (#87 exact-match), styled on the shared operator design system via
     <x-operator.shell>. Real guest profiles (operator-scoped, FULL contact — the CRM is the ONE PII surface,
     locked decision 4; the name-only rule is owner-only). Bulk select → Message · Add tag · Export · Delete
     (GDPR erasure — anonymize-not-destroy). Add guest. See App\Livewire\Operator\GuestsList for the REAL /
     DERIVED map.

     ⚠️ PII SURFACE, the ONLY one. Email/phone are encrypted casts decrypted here deliberately. The component
     holds NO public props, so none of this round-trips over /livewire/update. Bulk selection is CLIENT-SIDE
     (Alpine) keyed on ULID guest ids (not PII); each bulk form posts the selected ids to a real controller.

     RAW-BLADE RULE: never write `word@directive` — every `@` below follows a space, newline or tag. Guest
     EMAILS contain a literal `@` but are echoed through {{ }} as data, never written into the template. --}}
<div>
    <x-operator.shell active="guests" title="Guests" :subtitle="$subtitle" :viewer="$viewer">

        @if (session('guest_notice'))
            <div class="vy-flash is-ok vy-fade">{{ session('guest_notice') }}</div>
        @endif
        @if (session('guest_error'))
            <div class="vy-flash is-err vy-fade">{{ session('guest_error') }}</div>
        @endif
        @if ($errors->any())
            <div class="vy-flash is-err vy-fade">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        {{-- One Alpine root drives select-mode, the selection, and the three overlays. Selection is by ULID id. --}}
        <div x-data="{
                selectMode: false,
                sel: {},
                msgOpen: false, marketing: false,
                tagOpen: false, delOpen: false, addOpen: false,
                ids() { return Object.keys(this.sel).filter(k => this.sel[k]); },
                count() { return this.ids().length; },
                clear() { this.sel = {}; },
                toggleMode() { this.selectMode = !this.selectMode; if (! this.selectMode) this.clear(); },
             }">

            {{-- ── Stat strip — DERIVED, recomputed from real rows (no design constant ported) ─────────── --}}
            <div class="vy-dash-kpis vy-dash-kpis--3 vy-fade">
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">Guest profiles</div>
                    <div class="vy-dk-val">{{ $stats['profiles'] }}</div>
                    <div class="vy-dk-sub">in this workspace @if ($stats['anonymized'] > 0) · {{ $stats['anonymized'] }} anonymized @endif</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">In-house tonight</div>
                    <div class="vy-dk-val">{{ $stats['in_house'] }}</div>
                    <div class="vy-dk-sub">guests checked in · across every property</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">Repeat guests</div>
                    @if ($stats['repeat_pct'] === null)
                        <div class="vy-dk-val">—</div>
                        <div class="vy-dk-sub">nobody has stayed yet</div>
                    @else
                        <div class="vy-dk-val">{{ $stats['repeat_pct'] }}<span class="vy-dk-unit">%</span></div>
                        <div class="vy-dk-sub">{{ $stats['repeat'] }} of {{ $stats['with_stays'] }} {{ \Illuminate\Support\Str::plural('guest', $stats['with_stays']) }} who have stayed booked again</div>
                    @endif
                </div>
            </div>

            {{-- ── Control row — GET search (a scoped read must re-run the route middleware, #20) ───────── --}}
            <div class="vy-rack-toolbar vy-fade" style="margin-top: 22px;">
                <div class="vy-left" style="flex: 1; min-width: 260px;">
                    <form method="GET" action="{{ route('app.guests') }}" class="vy-input-group" style="width: 100%; max-width: 420px;">
                        <input class="vy-input" type="search" name="q" value="{{ $query }}"
                               placeholder="Search by name, email or phone…" aria-label="Search guests">
                        <button type="submit" class="vy-btn vy-btn--ghost">Search</button>
                        @if ($query !== '')
                            <a class="vy-pill" href="{{ route('app.guests') }}">Clear</a>
                        @endif
                    </form>
                </div>
                <div class="vy-right" style="display: flex; gap: 8px;">
                    <button type="button" class="vy-btn vy-btn--ghost" :class="selectMode && 'vy-btn--accent'" @click="toggleMode()">
                        <span x-text="selectMode ? 'Done' : 'Select'">Select</span>
                    </button>
                    <button type="button" class="vy-btn vy-btn--accent" @click="addOpen = true">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                        Add guest
                    </button>
                </div>
            </div>

            {{-- ── Filter tabs — All · In-house · Upcoming · VIP (real stay-state + the real VIP column) ── --}}
            <div class="vy-seg vy-fade" style="margin-bottom: 18px; flex-wrap: wrap;">
                @foreach ($chips as $chip)
                    <a class="vy-seg-opt @if ($chip['active']) is-checked @endif" href="{{ $chip['href'] }}"
                       @if ($chip['active']) aria-current="page" @endif>
                        {{ $chip['label'] }} <span class="vy-muted">{{ $chip['count'] }}</span>
                    </a>
                @endforeach
            </div>

            @if ($total === 0)
                <div class="vy-card vy-fade">
                    <div class="vy-empty">
                        <strong style="display: block; font-size: 15px; color: var(--vy-ink); margin-bottom: 6px;">No guests yet</strong>
                        A guest profile is created with their first booking — or add one now.
                    </div>
                </div>
            @elseif (count($rows) === 0)
                <div class="vy-card vy-fade">
                    <div class="vy-empty">
                        <strong style="display: block; font-size: 15px; color: var(--vy-ink); margin-bottom: 6px;">
                            @if ($query !== '') No guests match “{{ $query }}” @else No guests in this view @endif
                        </strong>
                        <span style="display: block; margin-bottom: 14px;">
                            Search matches name, city and an exact email address — phone and partial email are encrypted at rest, so they cannot be searched.
                            {{ $total }} {{ \Illuminate\Support\Str::plural('guest', $total) }} in total.
                        </span>
                        <a class="vy-btn vy-btn--ghost" href="{{ route('app.guests') }}">Clear search and filters</a>
                    </div>
                </div>
            @else
                <div class="vy-card vy-card--flush vy-fade">
                    <div class="vy-utable-wrap">
                        <table class="vy-utable">
                            <thead>
                                <tr>
                                    <th x-show="selectMode" style="width: 28px;"></th>
                                    <th>Guest</th>
                                    <th>Nationality</th>
                                    <th style="text-align: right;">Stays</th>
                                    <th>Last stay</th>
                                    <th style="text-align: right;">Lifetime</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $row)
                                    <tr :class="sel['{{ $row['id'] }}'] && 'is-selected'">
                                        <td x-show="selectMode" style="width: 28px;">
                                            <input type="checkbox" x-model="sel['{{ $row['id'] }}']" aria-label="Select {{ $row['name'] }}">
                                        </td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                <span class="vy-avatar">{{ $row['initials'] }}</span>
                                                <div style="min-width: 0;">
                                                    <a class="vy-u-code vy-arr-link" href="{{ route('app.guests.show', $row['id']) }}">{{ $row['name'] }}</a>
                                                    @if ($row['is_vip'] || $row['risk_flag'] || $row['tags'] !== [])
                                                        <div style="display: flex; align-items: center; gap: 5px; flex-wrap: wrap; margin-top: 3px;">
                                                            @if ($row['is_vip']) <span class="vy-tag tone-vip"><span class="vy-dot"></span>VIP</span> @endif
                                                            @if ($row['risk_flag']) <span class="vy-tag tone-risk"><span class="vy-dot"></span>At risk</span> @endif
                                                            @foreach ($row['tags'] as $tag) <span class="vy-tag tone-tag"><span class="vy-dot"></span>{{ $tag }}</span> @endforeach
                                                        </div>
                                                    @endif
                                                    @if ($row['email'] !== null)
                                                        <div class="vy-u-sub" style="margin-top: 3px;"><a href="mailto:{{ $row['email'] }}">{{ $row['email'] }}</a>@if ($row['phone'] !== null) <span class="vy-muted">· {{ $row['phone'] }}</span>@endif</div>
                                                    @elseif ($row['phone'] !== null)
                                                        <div class="vy-u-sub" style="margin-top: 3px;">{{ $row['phone'] }}</div>
                                                    @elseif ($row['anonymized'])
                                                        <div class="vy-u-sub vy-muted" style="margin-top: 3px;">Erased</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>@if ($row['nationality'] !== null) {{ $row['nationality'] }} @else <span class="vy-muted">—</span> @endif</td>
                                        <td style="text-align: right;"><span class="vy-u-code">{{ $row['stays'] }}</span></td>
                                        <td>@if ($row['last_stay'] !== null) <span class="vy-u-sub">{{ $row['last_stay'] }}</span> @else <span class="vy-muted">—</span> @endif</td>
                                        <td style="text-align: right;">
                                            @forelse ($row['billed'] as $line)
                                                <div class="vy-u-code">{{ $line }}</div>
                                            @empty
                                                <span class="vy-muted">—</span>
                                            @endforelse
                                            @foreach ($row['balance'] as $line)
                                                <div class="vy-balance is-due" style="font-size: 11px;">{{ $line }} due</div>
                                            @endforeach
                                        </td>
                                        <td>
                                            @if ($row['stage_meta'] !== null)
                                                <span class="vy-tag tone-{{ $row['stage_meta']['tone'] }}"><span class="vy-dot"></span>{{ $row['stage_meta']['label'] }}</span>
                                            @else
                                                <span class="vy-muted">No bookings</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="vy-fl-note" style="padding: 13px 16px; border-top: 1px solid var(--vy-hairline);">{{ $footer }}</div>
                </div>
            @endif

            {{-- ── Floating BULK ACTION BAR — shows in select mode with ≥1 selected ────────────────────── --}}
            <div x-show="selectMode && count() > 0" x-cloak x-transition
                 style="position: fixed; bottom: 26px; left: 50%; transform: translateX(-50%); z-index: 40;
                        background: rgba(43,35,32,.97); color: #fff; border-radius: 14px; padding: 12px 16px;
                        display: flex; align-items: center; gap: 14px; box-shadow: 0 18px 50px rgba(0,0,0,.35);">
                <span style="font-size: 13px;"><strong x-text="count()"></strong> selected</span>
                <button type="button" @click="clear()" style="background: none; border: none; color: #F0A57A; font-size: 12.5px; cursor: pointer;">Clear</button>
                <span style="width: 1px; height: 20px; background: rgba(255,255,255,.18);"></span>

                <button type="button" @click="msgOpen = true; marketing = false" class="vy-bulk-act">Message</button>
                <button type="button" @click="tagOpen = true" class="vy-bulk-act">Add tag</button>

                {{-- Export — a real audited CSV download --}}
                <form method="POST" action="{{ route('app.guests.bulk.export') }}">
                    @csrf
                    <template x-for="id in ids()" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
                    <button type="submit" class="vy-bulk-act">Export</button>
                </form>

                <button type="button" @click="delOpen = true" class="vy-bulk-act" style="color: #F6C6B4;">Delete</button>
            </div>

            {{-- ── MESSAGE modal ──────────────────────────────────────────────────────────────────────── --}}
            <div x-show="msgOpen" x-cloak @keydown.escape.window="msgOpen = false"
                 style="position: fixed; inset: 0; z-index: 50; background: rgba(43,35,32,.4); backdrop-filter: blur(3px); display: flex; align-items: center; justify-content: center; padding: 20px;">
                <div @click.outside="msgOpen = false" class="vy-card vy-fade" style="width: 100%; max-width: 460px;">
                    <h2 class="vy-card-title" style="margin-bottom: 4px;">Message <span x-text="count()"></span> <span x-text="count() === 1 ? 'guest' : 'guests'"></span></h2>
                    <p style="font-size: 12.5px; color: var(--vy-muted); margin: 0 0 14px;">Sent by email · replies land in your inbox.</p>
                    <form method="POST" action="{{ route('app.guests.bulk.message') }}">
                        @csrf
                        <template x-for="id in ids()" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
                        <textarea class="vy-input" name="body" rows="5" maxlength="2000" required
                                  placeholder="Write your message…" style="width: 100%; resize: vertical;"></textarea>
                        <label class="vy-field-label" style="display: flex; align-items: center; gap: 8px; text-transform: none; letter-spacing: 0; margin-top: 12px;">
                            <input type="checkbox" name="marketing" value="1" x-model="marketing">
                            This is a marketing message
                        </label>
                        <p style="font-size: 11px; color: var(--vy-faint); margin: 4px 0 0;" x-show="marketing">Only guests who opted in to marketing will receive it — the rest are skipped.</p>
                        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 16px;">
                            <button type="button" class="vy-btn vy-btn--ghost" @click="msgOpen = false">Cancel</button>
                            <button type="submit" class="vy-btn vy-btn--accent">Send to <span x-text="count()"></span> <span x-text="count() === 1 ? 'guest' : 'guests'"></span></button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- ── ADD TAG modal — reuses the real per-guest tag write ────────────────────────────────── --}}
            <div x-show="tagOpen" x-cloak @keydown.escape.window="tagOpen = false"
                 style="position: fixed; inset: 0; z-index: 50; background: rgba(43,35,32,.4); backdrop-filter: blur(3px); display: flex; align-items: center; justify-content: center; padding: 20px;">
                <div @click.outside="tagOpen = false" class="vy-card vy-fade" style="width: 100%; max-width: 420px;">
                    <h2 class="vy-card-title" style="margin-bottom: 12px;">Tag <span x-text="count()"></span> <span x-text="count() === 1 ? 'guest' : 'guests'"></span></h2>
                    <form method="POST" action="{{ route('app.guests.bulk.tag') }}">
                        @csrf
                        <template x-for="id in ids()" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
                        <input class="vy-input" type="text" name="name" maxlength="255" required placeholder="Tag name…" style="width: 100%;">
                        <p style="font-size: 11px; color: var(--vy-faint); margin: 6px 0 0;">Tags are shared across this workspace — a new name is added to the catalogue.</p>
                        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 16px;">
                            <button type="button" class="vy-btn vy-btn--ghost" @click="tagOpen = false">Cancel</button>
                            <button type="submit" class="vy-btn vy-btn--accent">Add tag</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- ── DELETE (GDPR erasure) confirm — HONEST copy: PII erased, reservations retained ───────── --}}
            <div x-show="delOpen" x-cloak @keydown.escape.window="delOpen = false"
                 style="position: fixed; inset: 0; z-index: 50; background: rgba(43,35,32,.4); backdrop-filter: blur(3px); display: flex; align-items: center; justify-content: center; padding: 20px;">
                <div @click.outside="delOpen = false" class="vy-card vy-fade" style="width: 100%; max-width: 440px;">
                    <h2 class="vy-card-title" style="margin-bottom: 4px; color: #8F3110;">Erase <span x-text="count()"></span> <span x-text="count() === 1 ? 'guest' : 'guests'"></span>?</h2>
                    <p style="font-size: 12.5px; color: var(--vy-muted); margin: 0 0 6px;">This can’t be undone.</p>
                    <p style="font-size: 13px; color: var(--vy-ink); margin: 0 0 14px;">
                        The selected guests’ personal data — name, contact, ID documents, notes and message history —
                        is <strong>permanently erased</strong>. Their reservations, folios and financial records are
                        <strong>kept</strong> (anonymized, no longer linked to a named person) so the books still balance.
                    </p>
                    @can('guests.erase')
                        <form method="POST" action="{{ route('app.guests.bulk.anonymize') }}"
                              onsubmit="return confirm('Permanently erase the selected guests? Their personal data cannot be recovered.');">
                            @csrf
                            <template x-for="id in ids()" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
                            <label class="vy-field-label" for="bulk_confirm">Type ERASE to confirm</label>
                            <input class="vy-input" type="text" id="bulk_confirm" name="confirm" autocomplete="off" placeholder="ERASE" style="width: 100%; margin-bottom: 14px;">
                            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                                <button type="button" class="vy-btn vy-btn--ghost" @click="delOpen = false">Keep them</button>
                                <button type="submit" class="vy-btn" style="background: #8F3110; color: #fff;">Erase <span x-text="count()"></span></button>
                            </div>
                        </form>
                    @else
                        <div class="vy-recon" style="margin-top: 0;">Erasing a guest requires the erase permission (Property Owner / Group Admin).</div>
                        <div style="display: flex; justify-content: flex-end; margin-top: 12px;"><button type="button" class="vy-btn vy-btn--ghost" @click="delOpen = false">Close</button></div>
                    @endcan
                </div>
            </div>

            {{-- ── ADD GUEST modal — a real create ────────────────────────────────────────────────────── --}}
            <div x-show="addOpen" x-cloak @keydown.escape.window="addOpen = false"
                 style="position: fixed; inset: 0; z-index: 50; background: rgba(43,35,32,.4); backdrop-filter: blur(3px); display: flex; align-items: center; justify-content: center; padding: 20px;">
                <div @click.outside="addOpen = false" class="vy-card vy-fade" style="width: 100%; max-width: 480px; max-height: 90vh; overflow: auto;">
                    <h2 class="vy-card-title" style="margin-bottom: 12px;">Add guest</h2>
                    <form method="POST" action="{{ route('app.guests.store') }}">
                        @csrf
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div><label class="vy-field-label" for="a_first">First name</label><input class="vy-input" id="a_first" type="text" name="first_name" maxlength="255" style="width: 100%;"></div>
                            <div><label class="vy-field-label" for="a_last">Last name</label><input class="vy-input" id="a_last" type="text" name="last_name" maxlength="255" required style="width: 100%;"></div>
                            <div><label class="vy-field-label" for="a_email">Email</label><input class="vy-input" id="a_email" type="email" name="email" maxlength="255" style="width: 100%;"></div>
                            <div><label class="vy-field-label" for="a_phone">Phone</label><input class="vy-input" id="a_phone" type="text" name="phone" maxlength="64" style="width: 100%;"></div>
                            <div><label class="vy-field-label" for="a_city">City / country</label><input class="vy-input" id="a_city" type="text" name="city" maxlength="255" style="width: 100%;"></div>
                            <div><label class="vy-field-label" for="a_nat">Nationality (2-letter)</label><input class="vy-input" id="a_nat" type="text" name="nationality" maxlength="2" style="width: 100%; text-transform: uppercase;"></div>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 12px;">
                            <label class="vy-field-label" style="display: flex; align-items: center; gap: 8px; text-transform: none; letter-spacing: 0;"><input type="checkbox" name="is_vip" value="1"> VIP guest</label>
                            <label class="vy-field-label" style="display: flex; align-items: center; gap: 8px; text-transform: none; letter-spacing: 0;"><input type="checkbox" name="prefers_email" value="1" checked> Prefers email contact</label>
                            <label class="vy-field-label" style="display: flex; align-items: center; gap: 8px; text-transform: none; letter-spacing: 0;"><input type="checkbox" name="marketing_consent" value="1"> Marketing consent</label>
                        </div>
                        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 16px;">
                            <button type="button" class="vy-btn vy-btn--ghost" @click="addOpen = false">Cancel</button>
                            <button type="submit" class="vy-btn vy-btn--accent">Add guest</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </x-operator.shell>
</div>

@once
    <style>
        [x-cloak] { display: none !important; }
        .vy-utable tr.is-selected { background: rgba(217,102,46,.10); }
        .vy-bulk-act { background: none; border: none; color: #fff; font-size: 12.5px; font-weight: 600; cursor: pointer; padding: 4px 8px; border-radius: 7px; }
        .vy-bulk-act:hover { background: rgba(255,255,255,.10); }
    </style>
@endonce
