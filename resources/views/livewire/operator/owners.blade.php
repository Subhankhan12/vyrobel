{{-- Operator Owners (Auth Phase B · Step 4) — the org's owners + the "Invite an owner" modal, styled
     on the shared operator design system. The list is REAL + tenant-scoped (owners, their unit counts,
     pending invites). The invite is a CSRF-protected POST → InviteOwnerController → OwnerInviteService
     → PRG (never a Livewire wire-action for a scoped write). Alpine (bundled with Livewire) drives ONLY
     the modal's presentation (show/hide + selection state); the server re-validates everything.

     Wave 1 · Sub-gate 3 — each owner row now navigates to the operator Owner Detail
     (`app.owners.show`), a REAL built route: the operator's tenant-scoped lens on that one owner's
     units / agreement / statements / payouts / balance. It is a plain GET link (never a wire-action),
     so the scope middleware runs on the way in. Nothing else on this screen changed.

     Roles: Owner (external party → portal, with units + commission) plus the two STAFF roles Manager
     (→ "Hotel Manager") and Accountant (→ "Accountant") — real spatie catalog roles assigned on
     activation. When a staff role is selected the owner-only fields (units, commission) hide; the server
     re-validates the role set and drops owner-only fields for staff. Commission presets map to a real
     management-agreement %; "Set up later" sends no value. --}}
<div>
    <x-operator.shell active="owners" title="Owners"
        subtitle="Owners & management agreements" :viewer="$viewer">
        <div x-data="{
            open: {{ $errors->any() || session('owners_error') ? 'true' : 'false' }},
            role: @js(old('role', 'owner')),
            commission: @js(old('commission_pct') !== null ? (string) old('commission_pct') : '15'),
            units: @js(array_values((array) old('unit_ids', []))),
        }">

        {{-- Flash: success / graceful error --}}
        @if (session('owners_notice'))
            <div class="vy-flash is-ok vy-fade" role="status">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                {{ session('owners_notice') }}
            </div>
        @endif
        @if (session('owners_error'))
            <div class="vy-flash is-err vy-fade" role="alert">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v5M12 16h.01M10.3 3.9L2 18a2 2 0 0 0 1.7 3h16.6A2 2 0 0 0 22 18L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>
                {{ session('owners_error') }}
            </div>
        @endif

        {{-- Header: count + invite trigger --}}
        <div class="vy-rack-toolbar vy-fade">
            <div class="vy-left">
                <span class="vy-range">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M8 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM2 20c0-3 3-5 6-5s6 2 6 5M16 11a3 3 0 1 0 0-6M22 20c0-3-3-5-6-5"/></svg>
                    {{ count($owners) }} {{ \Illuminate\Support\Str::plural('owner', count($owners)) }}
                </span>
            </div>
            <div class="vy-right">
                <button type="button" class="vy-btn vy-btn--accent" @click="open = true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM8 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM2 20c0-3.3 2.7-5 6-5s6 1.7 6 5M18 14v6M21 17h-6"/></svg>
                    Invite an owner
                </button>
            </div>
        </div>

        <div class="vy-dash-cols">
            {{-- Owners list (REAL, tenant-scoped) --}}
            <div class="vy-card vy-fade">
                <h2 class="vy-card-title" style="margin-bottom: 4px;">Owners</h2>
                <div style="margin: 0 -20px;">
                    @forelse ($owners as $o)
                        @php
                            $parts = preg_split('/\s+/', trim($o['name'])) ?: [];
                            $initials = strtoupper(mb_substr($parts[0] ?? 'O', 0, 1) . (count($parts) > 1 ? mb_substr(end($parts), 0, 1) : ''));
                        @endphp
                        {{-- Plain GET link to the REAL owner-detail route (tenant-scoped there too). --}}
                        <a class="vy-owner-row" href="{{ route('app.owners.show', $o['id']) }}"
                           style="color: inherit; text-decoration: none;">
                            <span class="vy-owner-avatar">{{ $initials }}</span>
                            <div class="vy-owner-main">
                                <div class="vy-owner-name">{{ $o['name'] }}</div>
                                <div class="vy-owner-sub">{{ $o['units'] }} {{ \Illuminate\Support\Str::plural('unit', $o['units']) }}</div>
                            </div>
                            @unless ($o['active'])
                                <span class="vy-pending-badge">Invited</span>
                            @endunless
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="flex: none; color: var(--vy-faint);"><path d="M9 6l6 6-6 6"/></svg>
                        </a>
                    @empty
                        <div class="vy-empty" style="padding: 30px 20px;">No owners yet — invite your first owner to give them portal access.</div>
                    @endforelse
                </div>
            </div>

            {{-- Pending invites (REAL) --}}
            <div class="vy-card vy-fade">
                <div class="vy-detail-head" style="margin-bottom: 4px;">
                    <h2 class="vy-card-title">Pending invites</h2>
                    <span class="vy-eyebrow">{{ count($pending) }}</span>
                </div>
                <div style="margin: 0 -20px;">
                    @forelse ($pending as $p)
                        <div class="vy-owner-row">
                            <span class="vy-owner-avatar" style="background: rgba(224,146,30,0.16); color: var(--vy-amber);">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16v12H4zM4 7l8 6 8-6"/></svg>
                            </span>
                            <div class="vy-owner-main">
                                <div class="vy-owner-name" style="font-weight: 500;">{{ $p['email'] }}</div>
                                <div class="vy-owner-sub">@if ($p['expires']) Expires {{ $p['expires'] }} @else Awaiting acceptance @endif</div>
                            </div>
                            <span class="vy-pending-badge">Pending</span>
                        </div>
                    @empty
                        <div class="vy-empty" style="padding: 30px 20px;">No pending invites.</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ============================ INVITE MODAL ============================ --}}
        <div class="vy-modal-backdrop" x-cloak x-show="open" x-transition.opacity
             @keydown.escape.window="open = false" @click.self="open = false">
            <div class="vy-modal" x-show="open" x-transition
                 role="dialog" aria-modal="true" aria-labelledby="vy-invite-title">
                <form method="POST" action="{{ route('app.owners.invite') }}">
                    @csrf

                    <div class="vy-modal-head">
                        <span class="vy-modal-ico">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M16 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM8 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM2 20c0-3.3 2.7-5 6-5s6 1.7 6 5M18 14v6M21 17h-6"/></svg>
                        </span>
                        <div style="flex: 1;">
                            <h3 class="vy-modal-title" id="vy-invite-title"
                                x-text="role === 'owner' ? 'Invite an owner' : 'Invite a team member'">Invite an owner</h3>
                            <p class="vy-modal-sub"
                               x-text="role === 'owner'
                                   ? 'They’ll get an email to set their password and access their owner portal.'
                                   : 'They’ll get an email to set their password and join the operator app.'">They’ll get an email to set their password and access their owner portal.</p>
                        </div>
                        <button type="button" class="vy-modal-x" @click="open = false" aria-label="Close">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="vy-modal-body">
                        {{-- Name --}}
                        <div class="vy-field">
                            <label class="vy-field-label" for="vy-owner-name">Full name</label>
                            <div class="vy-inwrap">
                                <span class="vy-in-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM4 21c0-4 4-6 8-6s8 2 8 6"/></svg></span>
                                <input type="text" id="vy-owner-name" name="name" class="vy-input" placeholder="e.g. Marisol Vaz"
                                       value="{{ old('name') }}" maxlength="120" required>
                            </div>
                            @error('name') <div class="vy-help" style="color: var(--vy-accent);">{{ $message }}</div> @enderror
                        </div>

                        {{-- Email --}}
                        <div class="vy-field">
                            <label class="vy-field-label" for="vy-owner-email">Email</label>
                            <div class="vy-inwrap">
                                <span class="vy-in-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16v12H4zM4 7l8 6 8-6"/></svg></span>
                                <input type="email" id="vy-owner-email" name="email" class="vy-input" placeholder="name@example.com"
                                       value="{{ old('email') }}" maxlength="190" required>
                            </div>
                            @error('email') <div class="vy-help" style="color: var(--vy-accent);">{{ $message }}</div> @enderror
                        </div>

                        {{-- Role — Owner (portal) + the two STAFF roles wired to real spatie catalog roles.
                             "Manager" → Hotel Manager · "Accountant" → Accountant. Owner-only fields below
                             hide when a staff role is chosen (server re-validates + drops them too). --}}
                        <div class="vy-field">
                            <label class="vy-field-label">Role</label>
                            <input type="hidden" name="role" :value="role">
                            <div class="vy-seg">
                                <label class="vy-seg-opt" :class="{ 'is-checked': role === 'owner' }" @click="role = 'owner'">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l9-8 9 8M5 10v10h14V10"/></svg>
                                    Owner
                                </label>
                                <label class="vy-seg-opt" :class="{ 'is-checked': role === 'manager' }" @click="role = 'manager'">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16v13H4zM8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                    Manager
                                </label>
                                <label class="vy-seg-opt" :class="{ 'is-checked': role === 'accountant' }" @click="role = 'accountant'">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M5 3h14v18H5zM9 8h6M9 12h6M9 16h4"/></svg>
                                    Accountant
                                </label>
                            </div>
                            <div class="vy-help" x-show="role === 'owner'">Owns units; sees statements, payouts and unit performance.</div>
                            <div class="vy-help" x-show="role === 'manager'" x-cloak>Operator staff — <strong>Hotel Manager</strong> role: day-to-day operations (calendar, front desk, housekeeping, reservations, folios).</div>
                            <div class="vy-help" x-show="role === 'accountant'" x-cloak>Operator staff — <strong>Accountant</strong> role: folios, payments, invoices, taxes and reports.</div>
                        </div>

                        {{-- Owner-only fields — hidden when a STAFF role is selected (staff get no units/commission) --}}
                        {{-- Assign units — REAL, tenant-scoped inventory --}}
                        @if (count($units) > 0)
                            <div class="vy-field" x-show="role === 'owner'">
                                <label class="vy-field-label">
                                    Assign units <span class="vy-fl-note">· optional</span>
                                    <span class="vy-fl-right" x-text="units.length + ' ' + (units.length === 1 ? 'unit' : 'units')"></span>
                                </label>
                                <div class="vy-units">
                                    @foreach ($units as $u)
                                        <label class="vy-unitpill" :class="{ 'is-checked': units.includes(@js($u['id'])) }">
                                            <input type="checkbox" name="unit_ids[]" value="{{ $u['id'] }}" x-model="units">
                                            <span class="vy-ubox">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                                            </span>
                                            {{ $u['name'] ?? $u['code'] }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Management agreement — presets map to a real commission %; "Set up later" sends none --}}
                        <div class="vy-field" style="margin-bottom: 4px;" x-show="role === 'owner'">
                            <label class="vy-field-label">Management agreement</label>
                            <input type="hidden" name="commission_pct" :value="commission === 'later' ? '' : commission">
                            <div class="vy-seg">
                                @foreach (['12', '15', '18'] as $preset)
                                    <label class="vy-seg-opt" :class="{ 'is-checked': commission === @js($preset) }" @click="commission = @js($preset)">{{ $preset }}%</label>
                                @endforeach
                                <label class="vy-seg-opt" :class="{ 'is-checked': commission === 'later' }" @click="commission = 'later'">Set up later</label>
                            </div>
                            <div class="vy-help">
                                <span x-show="commission !== 'later'"><span x-text="commission"></span>% of gross booking value · applied to this owner’s statements. Change it any time.</span>
                                <span x-show="commission === 'later'">No agreement created now — add one from the owner’s record later.</span>
                            </div>
                            @error('commission_pct') <div class="vy-help" style="color: var(--vy-accent);">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="vy-modal-foot">
                        <button type="button" class="vy-btn vy-btn--ghost" @click="open = false">Cancel</button>
                        <button type="submit" class="vy-btn vy-btn--accent">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4z"/></svg>
                            Send invite
                        </button>
                    </div>
                </form>
            </div>
        </div>

        </div>
    </x-operator.shell>
</div>
