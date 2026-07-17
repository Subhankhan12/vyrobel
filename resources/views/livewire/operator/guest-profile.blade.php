{{-- Operator GUEST PROFILE — the CRM detail (Wave 2 · Sub-gate 5), styled on the shared operator design
     system via <x-operator.shell>. See App\Livewire\Operator\GuestProfile for the authoritative REAL /
     DERIVED / OMITTED map and the four decisions (two name inputs · tags created on first use · consent as a
     record not a promise · the app's vocabulary).

     EVERY CONTROL HERE WRITES. The design's "Save changes" fired a toast and persisted nothing; each form
     below is a real CSRF-protected POST → GuestActionController → GuestService → PRG, and survives a
     refresh. Nothing on this page is a "Soon".

     ⚠️ PII SURFACE (locked decision 4) — consistent with the Guests list, and still the only pair. Email and
     phone are decrypted here and EDITABLE; the cast does the encrypting on save. The component holds only a
     string id, so nothing round-trips over /livewire/update.

     OMITTED, and named so nobody re-adds them: the whole "Latest review" panel (no reviews table exists at
     all), the paused-billing banner, the localStorage refund netting + its non-reconciling "€978 open
     folio", the "Dublin, Ireland" row (no city column — nationality is not residence), "Prefers email" (no
     preferred_channel column), the 8 hard-coded preference chips (a designer's guess — the real tag catalog
     replaces them), and the "Shown to housekeeping & front desk" / "Flags arrivals" claims (verified false:
     neither surface reads tags or is_vip).

     RAW-BLADE RULE: never write `word@directive` — Blade guards with \B, so a directive whose `@` follows a
     word character ships the RAW SOURCE to the page as ordinary text, invisible to assertSee. Every `@`
     below follows a space, a newline or a tag. ⚠️ The guest's EMAIL renders here and contains a literal `@`
     — it is echoed through {{ }} as data, never written into the template, so it cannot trip the guard. --}}
<div>
    <x-operator.shell active="guests" :title="$guest['name']"
        :subtitle="$history['stat'] ?? 'No stays yet'" :viewer="$viewer">

        {{-- Flash — the two keys this view renders. GuestActionController validates with validate() BECAUSE
             this view renders the $errors bag below; a screen that showed only a flash would turn a
             validation failure into a silent no-op. --}}
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

        <div class="vy-rack-toolbar vy-fade" style="margin-bottom: 16px;">
            <div class="vy-left">
                <a class="vy-convo-back" href="{{ route('app.guests') }}">‹ Guests</a>
            </div>
            <div class="vy-right">
                {{-- Link-if-exists: starting a conversation is the inbox's job, and every external channel is
                     account-gated. A composer we cannot send from would be the design's toast in a new
                     costume. --}}
                @if ($threadId !== null)
                    <a class="vy-act vy-act--ghost" href="{{ route('app.inbox.show', $threadId) }}">View conversation</a>
                @endif
                @if ($guest['current_stay'] !== null)
                    <a class="vy-act vy-act--accent" href="{{ route('app.reservations.show', $guest['current_stay']) }}">Current stay</a>
                @endif
            </div>
        </div>

        {{-- ── Header ─────────────────────────────────────────────────────────────────────────────── --}}
        <div class="vy-card vy-fade">
            <div class="vy-detail-head">
                <div style="display: flex; align-items: center; gap: 12px; min-width: 0;">
                    <span class="vy-avatar" style="width: 44px; height: 44px; font-size: 15px;">{{ $guest['initials'] }}</span>
                    <div style="min-width: 0;">
                        <div class="vy-detail-ref">{{ $guest['name'] }}</div>
                        <div class="vy-u-sub" style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap; margin-top: 5px;">
                            @if ($guest['nationality'] !== null)
                                {{-- A NATIONALITY — guests.nationality is char(2). Not a residence: no such
                                     column exists, and the design's editable "Dublin, Ireland" conflates them. --}}
                                <span>{{ $guest['nationality'] }} national</span>
                            @endif
                            @if ($guest['since_year'] !== null)
                                <span class="vy-sep">·</span><span>guest since {{ $guest['since_year'] }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                    {{-- Real boolean columns. --}}
                    @if ($guest['is_vip'])
                        <span class="vy-tag tone-vip"><span class="vy-dot"></span>VIP</span>
                    @endif
                    @if ($guest['risk_flag'])
                        <span class="vy-tag tone-risk"><span class="vy-dot"></span>At risk</span>
                    @endif
                    {{-- DERIVED — more than one real stay. Not a column; the design implied one. --}}
                    @if ($guest['repeat'])
                        <span class="vy-tag tone-tag"><span class="vy-dot"></span>Repeat guest</span>
                    @endif
                    @if ($guest['stage_meta'] !== null)
                        <span class="vy-tag tone-{{ $guest['stage_meta']['tone'] }}"><span class="vy-dot"></span>{{ $guest['stage_meta']['label'] }}</span>
                    @else
                        <span class="vy-muted">No bookings</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="vy-detail-cols" style="margin-top: 18px;">
            <div class="vy-stack">

                {{-- ── Stay history — every row links (the design linked only the first) ───────────── --}}
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
                                    @if ($stay['in_house'])
                                        <span class="vy-tag tone-in-house" style="margin-left: 6px;"><span class="vy-dot"></span>Here now</span>
                                    @endif
                                </div>
                                <div class="vy-um-sub">
                                    {{ $stay['reference'] }}
                                    <span class="vy-sep">·</span>{{ $stay['nights'] }} {{ \Illuminate\Support\Str::plural('night', $stay['nights']) }}
                                    @if ($stay['unit'] !== null)
                                        <span class="vy-sep">·</span>{{ $stay['unit'] }}
                                    @endif
                                    {{-- The real source enum. The OTA brand is not modelled anywhere, so an
                                         OTA booking reads "OTA" — never a brand name. --}}
                                    <span class="vy-sep">·</span>{{ $stay['source'] }}
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                {{-- The app's vocabulary via the shared resolver — the design's "Completed"
                                     is not in the status enum. --}}
                                <span class="vy-tag tone-{{ $stay['stage_meta']['tone'] }}"><span class="vy-dot"></span>{{ $stay['stage_meta']['label'] }}</span>
                                <span class="vy-ledger-amt tone-charge">{{ $stay['total'] }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="vy-empty">No stays yet — this profile has never been booked.</div>
                    @endforelse
                </div>

                {{-- ── Notes — the shared polymorphic table; this page is its first writer AND reader ── --}}
                <div class="vy-card">
                    <div class="vy-detail-head" style="margin-bottom: 10px;">
                        <h2 class="vy-card-title">Notes</h2>
                        <span class="vy-eyebrow">internal only</span>
                    </div>

                    {{-- REAL: the design's "Add note" button had no handler at all. --}}
                    <form method="POST" action="{{ route('app.guests.notes.store', $guest['id']) }}" class="vy-input-group" style="margin-bottom: 14px;">
                        @csrf
                        <input class="vy-input" type="text" name="body" maxlength="2000"
                               placeholder="Add an internal note…" aria-label="Add an internal note" required>
                        <button type="submit" class="vy-btn vy-btn--accent">Add note</button>
                    </form>

                    @forelse ($notes as $note)
                        <div class="vy-unit-row">
                            <div style="min-width: 0;">
                                @if ($note['redacted'])
                                    {{-- anonymize() redacts note bodies (a note about an identified person is
                                         their PII). An explicit state, not a blank card that reads like a bug. --}}
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

                {{-- ── Money — the SHARED GuestSpend, identical to the list's figures ──────────────── --}}
                <div class="vy-dash-kpi is-feature">
                    {{-- BILLED — what we invoiced (locked decision 1). The design's hero netted a refund off
                         (money collected) while including unpaid charges (money billed); it cannot be both. --}}
                    <div class="vy-dk-label">Total billed</div>
                    @forelse ($spend['billed'] as $line)
                        <div class="vy-dk-val" @if (! $loop->first) style="font-size: 20px;" @endif>{{ $line }}</div>
                    @empty
                        {{-- No billing history → no currency to render a zero IN. --}}
                        <div class="vy-dk-val">—</div>
                    @endforelse
                    <div class="vy-dk-sub">
                        invoiced across {{ $history['stays'] }} {{ \Illuminate\Support\Str::plural('stay', $history['stays']) }}
                        {{ $spend['multi_currency'] ? '· shown per currency (never summed across)' : '' }}
                    </div>
                </div>

                <div class="vy-card">
                    <h2 class="vy-card-title" style="margin-bottom: 10px;">Account</h2>
                    <div class="vy-facts">
                        <div class="vy-fact-label">Open balance</div>
                        <div class="vy-fact-val">
                            {{-- BESIDE the billed figure, never folded into it. --}}
                            @forelse ($spend['balance'] as $line)
                                <span class="vy-balance is-due">{{ $line }}</span>
                            @empty
                                <span class="vy-muted">—</span>
                            @endforelse
                        </div>

                        <div class="vy-fact-label">Nights</div>
                        <div class="vy-fact-val">{{ $history['nights'] > 0 ? $history['nights'] : '—' }}</div>

                        <div class="vy-fact-label">Avg / night</div>
                        <div class="vy-fact-val">
                            {{-- The design's formula was sound; its inputs were fabricated. Only for the
                                 primary currency, and only with nights to divide by. --}}
                            {{ $spend['avg_per_night'] ?? '—' }}
                        </div>

                        @if ($guest['source_mix'] !== null)
                            <div class="vy-fact-label">Booked via</div>
                            <div class="vy-fact-val">{{ $guest['source_mix'] }}</div>
                        @endif
                    </div>
                    <div class="vy-recon">Billed and balance come from the folio ledger — the same figures as the guests list.</div>
                </div>

                {{-- ── Tags — the tenant's REAL catalog, created on first use ──────────────────────── --}}
                <div class="vy-card">
                    <h2 class="vy-card-title" style="margin-bottom: 10px;">Tags</h2>

                    @if ($guest['tags'] !== [])
                        <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap; margin-bottom: 12px;">
                            @foreach ($guest['tags'] as $tag)
                                <form method="POST" action="{{ route('app.guests.tags.destroy', $guest['id']) }}" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="tag" value="{{ $tag['id'] }}">
                                    <button type="submit" class="vy-tag tone-tag" style="border: none; cursor: pointer;"
                                            title="Remove this tag" aria-label="Remove the tag {{ $tag['name'] }}">
                                        <span class="vy-dot"></span>{{ $tag['name'] }} ×
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('app.guests.tags.store', $guest['id']) }}" class="vy-input-group">
                        @csrf
                        <input class="vy-input" type="text" name="name" maxlength="255"
                               placeholder="Add a tag…" aria-label="Add a tag" required>
                        <button type="submit" class="vy-btn vy-btn--ghost">Add</button>
                    </form>
                    {{-- The design's eight chips (Sea view / Crib / …) were hard-coded in its own script. The
                         real catalog grows from what operators type — and nothing had ever written a tag, so
                         a pick-list would have been empty forever. --}}
                    <div class="vy-recon">Tags are shared across this workspace — a new name is added to the catalogue.</div>
                </div>

                {{-- ── Edit — the REAL write. The design's Save fired a toast and persisted nothing. ── --}}
                <div class="vy-card vy-card--accent">
                    <h2 class="vy-card-title" style="margin-bottom: 10px;">Edit profile</h2>

                    @if ($guest['anonymized'])
                        {{-- Erased means erased: re-populating PII would quietly un-anonymize them. The row
                             is kept only so the financial history referencing it stays intact. --}}
                        <div class="vy-empty" style="padding: 20px;">
                            This guest has been anonymized. Their details cannot be edited — the profile is kept
                            only so their financial history stays intact.
                        </div>
                    @else
                        <form method="POST" action="{{ route('app.guests.update', $guest['id']) }}">
                            @csrf
                            {{-- TWO inputs, not the design's one: first_name + last_name are two columns, and
                                 splitting on the last space is lossy ("van Dijk", "de la Cruz"). --}}
                            <label class="vy-field-label" for="first_name">First name</label>
                            <input class="vy-input" id="first_name" type="text" name="first_name" maxlength="255"
                                   value="{{ old('first_name', $guest['first_name']) }}" style="width: 100%; margin-bottom: 10px;">

                            <label class="vy-field-label" for="last_name">Last name</label>
                            <input class="vy-input" id="last_name" type="text" name="last_name" maxlength="255"
                                   value="{{ old('last_name', $guest['last_name']) }}" style="width: 100%; margin-bottom: 10px;">

                            {{-- ⚠️ PII — encrypted at rest; the cast encrypts on save. --}}
                            <label class="vy-field-label" for="email">Email</label>
                            <input class="vy-input" id="email" type="email" name="email" maxlength="255"
                                   value="{{ old('email', $guest['email']) }}" style="width: 100%; margin-bottom: 10px;">

                            <label class="vy-field-label" for="phone">Phone</label>
                            <input class="vy-input" id="phone" type="text" name="phone" maxlength="64"
                                   value="{{ old('phone', $guest['phone']) }}" style="width: 100%; margin-bottom: 14px;">

                            {{-- Real boolean columns. NO promise is printed about what they trigger: the
                                 design's "Flags arrivals & the front desk for extra care" is verified false
                                 — nothing outside the model reads is_vip. --}}
                            <label class="vy-field-label" style="display: flex; align-items: center; gap: 8px; text-transform: none; letter-spacing: 0;">
                                <input type="checkbox" name="is_vip" value="1" @checked(old('is_vip', $guest['is_vip']))>
                                VIP guest
                            </label>
                            <label class="vy-field-label" style="display: flex; align-items: center; gap: 8px; text-transform: none; letter-spacing: 0;">
                                <input type="checkbox" name="risk_flag" value="1" @checked(old('risk_flag', $guest['risk_flag']))>
                                Flagged at risk
                            </label>
                            <label class="vy-field-label" style="display: flex; align-items: center; gap: 8px; text-transform: none; letter-spacing: 0;">
                                <input type="checkbox" name="marketing_consent" value="1" @checked(old('marketing_consent', $guest['marketing_consent']))>
                                Marketing consent
                            </label>
                            {{-- A record, not a promise: the column is real and this page is its first reader
                                 anywhere, but there is no sending mechanism — so the design's "Seasonal offers
                                 & the occasional newsletter" is not printed. --}}
                            <div class="vy-recon" style="margin-top: 4px;">
                                Consent is recorded here for your own compliance — Vyrobel sends no marketing.
                            </div>

                            <button type="submit" class="vy-btn vy-btn--accent" style="margin-top: 14px; width: 100%;">Save changes</button>
                        </form>
                    @endif
                </div>

                {{-- ── GDPR ERASURE — the trigger anonymize() never had ────────────────────────────
                     Gated on `guests.erase` (Property Owner / Group Admin only): running the house does not
                     include erasing a person. Hidden, not shown-and-403'd. Absent once already erased —
                     there is nothing left to destroy. --}}
                @can('guests.erase')
                    @unless ($guest['anonymized'])
                        <div class="vy-card">
                            <h2 class="vy-card-title" style="margin-bottom: 6px;">Erase this guest</h2>
                            <div class="vy-recon" style="margin-top: 0;">
                                Right to erasure. This <strong>permanently</strong> destroys their name, contact
                                details, ID documents, notes and message history. It <strong>cannot be undone</strong>.
                                Their stays and financial records are kept — without their identity — so the books
                                still balance.
                            </div>

                            {{-- NOT one-click. The typed word is checked by the SERVER (a dialog alone is
                                 bypassable by anyone who can POST); the confirm() is a second, softer layer. --}}
                            <form method="POST" action="{{ route('app.guests.anonymize', $guest['id']) }}"
                                  style="margin-top: 12px;"
                                  onsubmit="return confirm('Permanently erase this guest? Their personal data cannot be recovered.');">
                                @csrf
                                <label class="vy-field-label" for="confirm">Type ERASE to confirm</label>
                                <div class="vy-input-group">
                                    <input class="vy-input" type="text" id="confirm" name="confirm"
                                           autocomplete="off" placeholder="ERASE" aria-describedby="erase-help">
                                    <button type="submit" class="vy-btn vy-btn--ghost">Erase</button>
                                </div>
                                <div id="erase-help" class="vy-recon" style="margin-top: 6px;">
                                    Erasing is permanent and immediate.
                                </div>
                            </form>
                        </div>
                    @endunless
                @endcan
            </div>
        </div>

    </x-operator.shell>
</div>
