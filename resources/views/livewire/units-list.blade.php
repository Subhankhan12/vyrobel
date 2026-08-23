{{-- Operator Units — the org's physical units (design pass · final operator stub). Styled on the
     shared operator design system via <x-operator.shell>. All data is REAL and tenant-scoped; the
     status tags are keyed to the real unit enums. Read-only. --}}
{{-- ⚠️ `unitPriceFor` LIVES ON THE ROOT, and that placement is the fix for a real defect: it was first
     declared on the dialog wrapper at the foot of the page, which is a SIBLING of the table — so the row
     buttons referenced a variable no ancestor owned and clicking them did nothing at all. Six green tests
     missed it because they assert the markup is present, not that the interaction works; only opening the
     page in a browser showed 0 dialogs on click (the item-21 blank-rows lesson, in a new costume). --}}
<div x-data="{ unitPriceFor: null, arrivalFor: null }">
    <x-operator.shell active="units" title="Units" subtitle="Inventory" :viewer="$viewer">

        @if (session('units_notice'))
            <div class="vy-flash is-ok vy-fade" role="status">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                {{ session('units_notice') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="vy-flash is-err vy-fade" role="alert">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v5M12 16h.01M10.3 3.9L2 18a2 2 0 0 0 1.7 3h16.6A2 2 0 0 0 22 18L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>
                {{ $errors->first() }}
            </div>
        @endif

        {{-- DERIVED summary + honest NO-BACKEND affordances --}}
        <div class="vy-rack-toolbar vy-fade">
            <div class="vy-left vy-summary" style="margin:0;">
                <div class="vy-summary-chip"><span class="vy-sc-num">{{ $summary['total'] }}</span><span class="vy-sc-label">Units</span></div>
                <div class="vy-summary-chip"><span class="vy-sc-num">{{ $summary['active'] }}</span><span class="vy-sc-label">Active</span></div>
                <div class="vy-summary-chip @if ($summary['to_clean'] > 0) is-due @endif"><span class="vy-sc-num">{{ $summary['to_clean'] }}</span><span class="vy-sc-label">To clean</span></div>
                <div class="vy-summary-chip @if ($summary['out_of_service'] > 0) is-due @endif"><span class="vy-sc-num">{{ $summary['out_of_service'] }}</span><span class="vy-sc-label">Out of service</span></div>
            </div>
            <div class="vy-right">
                <span class="vy-pill is-soon" aria-disabled="true">Filters <span class="vy-soon-tag">Soon</span></span>
                <span class="vy-pill is-soon" aria-disabled="true">Export <span class="vy-soon-tag">Soon</span></span>
                {{-- ⚠️ P-6 · Gate 1 — WAS "Add unit · Soon". It is real now, and it had to become real:
                     RoomType and RatePlan had NO creation path in the whole application, so a /signup
                     tenant reached a property they could never make sellable while the app told them to
                     "add units, rates & channels to go live". ⚠️ Gated on the SAME permission as the route
                     (#30), so a viewer never sees a form they cannot submit. --}}
                @can('units.manage')
                    <a class="vy-btn" href="#vy-add-inventory">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                        Add inventory
                    </a>
                @endcan
            </div>
        </div>

        @if (count($units) === 0)
            {{-- ⚠️ WAS: "add a property and room types to see its units" — an instruction the tenant could
                 not follow, because room types could not be created at all. It now names the two real steps
                 in order, and the forms are directly below. --}}
            <div class="vy-card vy-fade"><div class="vy-empty">
                No units yet.
                @can('units.manage')
                    Add a <strong>room type</strong> first (what you sell — “Double Room”), then a
                    <strong>unit</strong> in it (the physical room — “101”). Both are below.
                @else
                    Ask an administrator to add a room type and a unit.
                @endcan
            </div></div>
        @else
            <div class="vy-card vy-card--flush vy-fade">
                <div class="vy-utable-wrap">
                    <table class="vy-utable">
                        <thead>
                            <tr>
                                <th>Unit</th>
                                <th>Room type</th>
                                <th>Kind</th>
                                <th>Sleeps</th>
                                <th>Housekeeping</th>
                                <th>Condition</th>
                                <th>Owner</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($units as $unit)
                                <tr>
                                    <td>
                                        <div class="vy-u-cell">
                                            @if ($unit['cover'])
                                                <img src="{{ $unit['cover'] }}" alt="" class="vy-u-thumb" loading="lazy">
                                            @else
                                                <span class="vy-u-thumb vy-u-thumb--empty" aria-hidden="true"></span>
                                            @endif
                                            <div>
                                                <div class="vy-u-code">{{ $unit['code'] }}
                                                    @unless ($unit['active'])<span class="vy-u-inactive">Inactive</span>@endunless
                                                </div>
                                                @if ($unit['name'])<div class="vy-u-sub">{{ $unit['name'] }}</div>@endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $unit['room_type'] }}
                                        {{-- D18-5 · SINGLE-SET — the N=1 entry to the ONE per-unit pricing form.
                                             ⚠️ It opens the SAME <x-rates.unit-price-form> the rates grid opens,
                                             pre-selecting this unit, and posts to the SAME preview→apply routes,
                                             so this surface adds no second writer and no second price rule (D4-1). --}}
                                        @if ($canManageRates ?? false)
                                            <button type="button" class="vy-u-priceind"
                                                    style="display:block;margin-top:4px;padding:0;border:0;background:none;font-size:11.5px;color:var(--vy-accent);cursor:pointer;text-decoration:underline;"
                                                    x-on:click="unitPriceFor = '{{ $unit['id'] }}'">Price individually</button>
                                        @endif
                                        {{-- ⚠️ CLUSTER E — the arrival-info editor. The badge reports only
                                             WHETHER a code is set; the value itself is never in this page. --}}
                                        @if ($canManageUnits ?? false)
                                            <button type="button"
                                                    style="display:block;margin-top:4px;padding:0;border:0;background:none;font-size:11.5px;color:var(--vy-accent);cursor:pointer;text-decoration:underline;"
                                                    x-on:click="arrivalFor = '{{ $unit['id'] }}'">Arrival info<span style="color: var(--vy-muted); text-decoration: none;">{{ $unit['arrival']['has_code'] ? ' · code set' : '' }}</span></button>
                                        @endif
                                    </td>
                                    <td>{{ $unit['kind'] }}</td>
                                    <td>{{ $unit['occupancy'] }}</td>
                                    <td><span class="vy-tag tone-{{ $unit['hk_tone'] }}"><span class="vy-dot"></span>{{ $unit['hk_label'] }}</span></td>
                                    <td><span class="vy-tag tone-{{ $unit['condition_tone'] }}"><span class="vy-dot"></span>{{ $unit['condition_label'] }}</span></td>
                                    <td>
                                        @if ($unit['owner'])
                                            <span class="vy-u-owner"><span class="vy-mini-avatar">{{ strtoupper(mb_substr($unit['owner'], 0, 1)) }}</span>{{ $unit['owner'] }}</span>
                                        @else
                                            {{-- No owner_id set — operator-owned inventory (e.g. a hotel room). Real, not blank-faked. --}}
                                            <span class="vy-u-operator">Operator-owned</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ROOM-TYPE PHOTOS — real uploads (secure: images only, size-limited, generated filenames).
                 Photos attach to the room-type (what the storefront sells); unit cards derive their cover.
                 ⚠️ OPERATOR FIX 1 (audit Finding 6) — every control in this card (Add photo · set cover ·
                 reorder · remove) posts to `room-types.photos.*`, which require `units.manage`. The card
                 carried NO guard, so all 11 back-office roles were offered writes that 403. Gated on the
                 permission the routes themselves require (#30 — gate the CONTROL too).
                 ⚠️ NOT `$canManageRates`: that flag is `rates.manage` and correctly guards the unrelated
                 "Price individually" button above — swapping it would have broken a correct gate. --}}
            @if ($canManageUnits ?? false)
            <div class="vy-card vy-fade" style="margin-top:16px;">
                <div class="vy-detail-head" style="margin-bottom:6px;">
                    <h2 class="vy-card-title">Room-type photos</h2>
                    <span class="vy-eyebrow">Shown on the booking storefront + unit cards</span>
                </div>
                @foreach ($roomTypes as $rt)
                    <div class="vy-pm-room">
                        <div class="vy-pm-head">
                            <span class="vy-pm-name">{{ $rt['name'] }}</span>
                            <form method="POST" action="{{ route('app.room-types.photos.store', $rt['id']) }}" enctype="multipart/form-data">
                                @csrf
                                <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" required
                                       id="pm-{{ $rt['id'] }}" class="vy-pm-file" onchange="this.form.submit()">
                                <label for="pm-{{ $rt['id'] }}" class="vy-btn vy-btn--ghost vy-pm-add">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                                    Add photo
                                </label>
                            </form>
                        </div>
                        @if (count($rt['photos']) > 0)
                            <div class="vy-pm-thumbs">
                                @foreach ($rt['photos'] as $i => $photo)
                                    <div class="vy-pm-thumb @if ($photo['is_cover']) is-cover @endif">
                                        <img src="{{ $photo['url'] }}" alt="{{ $photo['alt'] }}" loading="lazy">
                                        @if ($photo['is_cover'])<span class="vy-pm-cover-badge">Cover</span>@endif
                                        <div class="vy-pm-ctrls">
                                            @unless ($photo['is_cover'])
                                                <form method="POST" action="{{ route('app.room-types.photos.cover', [$rt['id'], $photo['id']]) }}">@csrf<button class="vy-pm-btn" title="Set as cover" aria-label="Set as cover">★</button></form>
                                            @endunless
                                            <form method="POST" action="{{ route('app.room-types.photos.move', [$rt['id'], $photo['id']]) }}">@csrf<input type="hidden" name="direction" value="up"><button class="vy-pm-btn" title="Move earlier" aria-label="Move earlier" @if ($i === 0) disabled @endif>‹</button></form>
                                            <form method="POST" action="{{ route('app.room-types.photos.move', [$rt['id'], $photo['id']]) }}">@csrf<input type="hidden" name="direction" value="down"><button class="vy-pm-btn" title="Move later" aria-label="Move later" @if ($i === count($rt['photos']) - 1) disabled @endif>›</button></form>
                                            <form method="POST" action="{{ route('app.room-types.photos.destroy', [$rt['id'], $photo['id']]) }}" onsubmit="return confirm('Remove this photo?')">@csrf @method('DELETE')<button class="vy-pm-btn vy-pm-btn--danger" title="Remove" aria-label="Remove">✕</button></form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="vy-pm-empty">No photos yet — the storefront shows a gradient placeholder until you add one.</div>
                        @endif
                    </div>
                @endforeach
            </div>
            @endif

        @endif


        {{-- ══ P-6 · Gate 1 — INVENTORY CREATION ═══════════════════════════════════════════════════════
             ⚠️ WHY THIS IS PLAIN HTML AND NOT A LIVEWIRE ACTION: it reuses the operator realm's locked
             mutation pattern (POST → controller → PRG) exactly as the Portfolio's Add-property does, so
             there is ONE pattern for this kind of work (§N.8f). Nothing new is invented here.
             ⚠️ NO WIREFRAME EXISTS for an operator room-type/unit/rate-plan screen — the only unit
             wireframes in docs/design/raw are the OWNER ones. Built to the realm's existing design system
             instead, and registered as a divergence rather than passed over silently. --}}
        @can('units.manage')
            <div class="vy-card vy-fade" id="vy-add-inventory" style="margin-top:18px">
                <h2 class="vy-card-title">Add inventory</h2>
                <p class="vy-line-sub" style="margin-bottom:14px">
                    A <strong>room type</strong> is what a guest books (“Double Room”). A <strong>unit</strong>
                    is the physical room inside it (“101”). You need at least one of each, plus a rate plan
                    with prices, before the storefront will offer anything.
                </p>

                <form method="POST" action="{{ route('app.inventory.room-types.store') }}" class="vy-form-row" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;align-items:end;margin-bottom:18px">
                    @csrf
                    <label class="vy-field"><span class="vy-label">Room type name</span>
                        <input class="vy-input" name="name" required maxlength="255" placeholder="Double Room"></label>
                    <label class="vy-field"><span class="vy-label">Kind</span>
                        <select class="vy-input" name="kind">
                            @foreach (['room' => 'Room', 'apartment' => 'Apartment', 'villa' => 'Villa', 'dorm' => 'Dorm'] as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select></label>
                    <label class="vy-field"><span class="vy-label">Sleeps (adults)</span>
                        <input class="vy-input" type="number" name="max_adults" value="2" min="1" max="30" required></label>
                    <label class="vy-field"><span class="vy-label">Children</span>
                        <input class="vy-input" type="number" name="max_children" value="0" min="0" max="30" required></label>
                    <button type="submit" class="vy-btn">Add room type</button>
                </form>

                @if (count($roomTypes) === 0)
                    <p class="vy-line-sub">Add a room type above, and the unit form appears here.</p>
                @else
                    <form method="POST" action="{{ route('app.inventory.units.store') }}" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;align-items:end">
                        @csrf
                        <label class="vy-field"><span class="vy-label">Room type</span>
                            <select class="vy-input" name="room_type_id" required>
                                @foreach ($roomTypes as $rt)
                                    <option value="{{ $rt['id'] }}">{{ $rt['name'] }}</option>
                                @endforeach
                            </select></label>
                        <label class="vy-field"><span class="vy-label">Unit code</span>
                            <input class="vy-input" name="code" required maxlength="60" placeholder="101"></label>
                        <label class="vy-field"><span class="vy-label">Name (optional)</span>
                            <input class="vy-input" name="name" maxlength="255" placeholder="Room 101"></label>
                        <button type="submit" class="vy-btn">Add unit</button>
                    </form>
                @endif
            </div>
        @endcan

    </x-operator.shell>

{{-- D18-5 · the single-set dialog. ⚠️ One <x-rates.unit-price-form> per row with `preselect`, which is the
     N=1 call of the very same component and routes the rates grid uses — the money path is the proven
     UnitRateOverrideService in both cases, never a direct write. --}}
@if ($canManageRates ?? false)
    <div x-cloak>
        @foreach ($units as $vyU)
            <div x-show="unitPriceFor === '{{ $vyU['id'] }}'" x-cloak
                 style="position: fixed; inset: 0; background: rgba(40,25,15,.45); z-index: 60; display: flex; align-items: center; justify-content: center; padding: 20px;"
                 x-on:click.self="unitPriceFor = null">
                <div class="vy-card" style="max-width: 520px; width: 100%; max-height: 90vh; overflow: auto;"
                     x-data="{ unitMode: 'set', unitRt: '{{ $vyU['room_type_id'] }}' }">
                    <x-rates.unit-price-form
                        :room-types="$unitPriceRoomTypes"
                        :units="$unitPriceUnits"
                        :preselect="$vyU['id']"
                        :from="now()->toDateString()"
                        :to="now()->addDays(6)->toDateString()"
                        heading="Price this unit individually"
                        subheading="a price for this unit only — the rest of its room type is unchanged">
                        <x-slot:actions>
                            <button type="button" class="vy-btn" x-on:click="unitPriceFor = null" style="padding: 9px 16px;">Cancel</button>
                        </x-slot:actions>
                    </x-rates.unit-price-form>
                </div>
            </div>
        @endforeach
    </div>
@endif

{{-- ⚠️⚠️ CLUSTER E — THE ARRIVAL-INFO EDITOR. These columns had no writer outside the demo seeder, so
     invariant #33 was gating the release of data no operator could enter. Reuses the per-unit modal shape
     the D18-5 pricing form established (§N.8f) rather than introducing a second form pattern.

     ⚠️ THE TWO SECRETS ARE WRITE-ONLY HERE, AND THAT IS DELIBERATE. A door code and a wifi password are
     credentials to a physical space; rendering the stored value back into this page would put it in every
     response, browser cache and screenshot of the units screen for everyone holding `units.manage`. The
     field shows only whether one is SET. Reading one is a separate, audited action.

     ⚠️ A BLANK FIELD LEAVES THE STORED VALUE ALONE — it is not an erase, because the form cannot show you
     what is there and a blank input therefore carries no intent. Clearing is explicit. --}}
@if ($canManageUnits ?? false)
    <div x-cloak>
        @foreach ($units as $vyA)
            <div x-show="arrivalFor === '{{ $vyA['id'] }}'" x-cloak
                 style="position: fixed; inset: 0; background: rgba(40,25,15,.45); z-index: 60; display: flex; align-items: center; justify-content: center; padding: 20px;"
                 x-on:click.self="arrivalFor = null">
                <div class="vy-card" style="max-width: 520px; width: 100%; max-height: 90vh; overflow: auto;">
                    <h3 class="vy-card-title" style="margin: 0 0 4px;">Arrival info — {{ $vyA['name'] ?? $vyA['code'] }}</h3>
                    <p style="font-size: 12px; color: var(--vy-muted); margin: 0 0 14px;">Guests see this only from {{ \App\Support\Guest\ArrivalPack::LEAD_DAYS }} days before check-in.</p>

                    <form method="POST" action="{{ route('app.inventory.units.arrival-info', $vyA['id']) }}" style="display: grid; gap: 10px;">
                        @csrf
                        <label style="font-size: 12px; color: var(--vy-muted);">Door code
                            <input type="text" name="door_code" autocomplete="off"
                                   placeholder="{{ $vyA['arrival']['has_code'] ? 'A code is set — leave blank to keep it' : 'Not set' }}"
                                   style="width: 100%; margin-top: 4px;">
                        </label>
                        <label style="font-size: 12px; color: var(--vy-muted);">Wifi network
                            <input type="text" name="wifi_ssid" value="{{ $vyA['arrival']['ssid'] }}" style="width: 100%; margin-top: 4px;">
                        </label>
                        <label style="font-size: 12px; color: var(--vy-muted);">Wifi password
                            <input type="text" name="wifi_password" autocomplete="off"
                                   placeholder="{{ $vyA['arrival']['has_wifi'] ? 'A password is set — leave blank to keep it' : 'Not set' }}"
                                   style="width: 100%; margin-top: 4px;">
                        </label>
                        <label style="font-size: 12px; color: var(--vy-muted);">Arrival notes
                            <textarea name="arrival_notes" rows="3" placeholder="{{ $vyA['arrival']['has_notes'] ? 'Notes are set — leave blank to keep them' : 'Directions, parking, key collection…' }}" style="width: 100%; margin-top: 4px;"></textarea>
                        </label>
                        <p style="font-size: 11.5px; color: var(--vy-muted); margin: 0;">A blank field keeps what is stored. Type <code>{{ \App\Services\ArrivalInfoService::CLEAR }}</code> to remove one.</p>
                        <div style="display: flex; gap: 8px; justify-content: flex-end;">
                            <button type="button" class="vy-btn" x-on:click="arrivalFor = null" style="padding: 9px 16px;">Cancel</button>
                            <button type="submit" class="vy-btn vy-btn--accent" style="padding: 9px 16px;">Save</button>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('app.inventory.units.arrival-info.reveal', $vyA['id']) }}" style="margin-top: 10px; border-top: 1px solid var(--vy-hairline); padding-top: 10px;">
                        @csrf
                        <button type="submit" class="vy-btn" style="padding: 7px 13px; font-size: 12px;">Reveal stored values</button>
                        <span style="font-size: 11.5px; color: var(--vy-muted); margin-left: 8px;">Recorded in the audit log.</span>
                    </form>

                    @if (($reveal = session('arrival_reveal')) && $reveal['unit'] === $vyA['id'])
                        <div style="margin-top: 10px; padding: 10px 12px; border-radius: 10px; background: rgba(194,65,12,.08); font-size: 12.5px; color: var(--vy-ink);">
                            <div>Door code: <strong>{{ $reveal['door_code'] ?? '—' }}</strong></div>
                            <div>Wifi: <strong>{{ $reveal['wifi_ssid'] ?? '—' }}</strong> / <strong>{{ $reveal['wifi_password'] ?? '—' }}</strong></div>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif
</div>
