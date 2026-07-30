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
            open: {{ $errors->any() || session('owners_error') || session('owner_invite_sent') ? 'true' : 'false' }},
            sent: {{ session('owner_invite_sent') ? 'true' : 'false' }},
            sentEmail: @js(session('owner_invite_sent.email')),
            sentExpires: @js(session('owner_invite_sent.expires')),
            role: @js(old('role', 'owner')),
            commission: @js(old('commission_pct') !== null ? (string) old('commission_pct') : '18'),
            units: @js(array_values((array) old('unit_ids', []))),
            roleTitle() { return this.role === 'owner' ? 'Invite an owner' : (this.role === 'manager' ? 'Invite a property manager' : 'Invite an accountant'); },
            nameLabel() { return this.role === 'owner' ? 'Owner name' : (this.role === 'manager' ? 'Manager name' : 'Accountant name'); },
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
                {{-- users.manage (2026-07-17): the invite ASSIGNS A REAL ROLE, so it is the privilege-
                     escalation gate — held by Property Owner / Group Admin only. Hidden rather than shown
                     dead, so the UI never invites the 403. --}}
                @can('users.manage')
                <button type="button" class="vy-btn vy-btn--accent" @click="open = true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM8 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM2 20c0-3.3 2.7-5 6-5s6 1.7 6 5M18 14v6M21 17h-6"/></svg>
                    Invite an owner
                </button>
                @endcan
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

        {{-- ============================ INVITE MODAL (exact-match #80) ============================ --}}
        {{-- The WHOLE modal is behind users.manage, not just the button that opens it: a form an unpermitted
             user could never submit has no business in their DOM. The route is gated too — this is the UI half,
             so the control is never offered (never invite a 403). Matched to "Vyrobel Invite Owner.html":
             a warm-glass 460px modal with a FORM view + a "sent" confirmation view (the latter renders on the
             real PRG via session('owner_invite_sent')). The backend is the REAL, unchanged invite flow.
             RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
        @can('users.manage')
        <style>
            @keyframes vyModalIn { from { opacity: 0; transform: translateY(14px) scale(.98); } to { opacity: 1; transform: translateY(0) scale(1); } }
            .vy-inv-scrim { position: fixed; inset: 0; z-index: 60; background: rgba(43,35,32,.38); backdrop-filter: blur(7px); display: flex; align-items: center; justify-content: center; padding: 24px; box-sizing: border-box; }
            .vy-inv-modal { width: 460px; max-width: 100%; max-height: 92vh; overflow-y: auto; background: rgba(255,252,248,.94); backdrop-filter: blur(30px) saturate(1.15); border: 1px solid rgba(255,255,255,.85); border-radius: 20px; box-shadow: 0 30px 80px rgba(60,30,10,.35), inset 0 1px 0 rgba(255,255,255,.75); padding: 24px; box-sizing: border-box; animation: vyModalIn .4s cubic-bezier(.22,.61,.36,1) both; }
            .vy-inv-ico { width: 40px; height: 40px; border-radius: 12px; background: linear-gradient(140deg,#E8703A,#C2410C); display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 5px 14px rgba(194,65,12,.32); }
            .vy-inv-x { width: 32px; height: 32px; border-radius: 50%; background: rgba(120,70,40,.07); border: none; color: #8A8079; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0; text-decoration: none; }
            .vy-inv-x:hover { background: rgba(120,70,40,.13); }
            .vy-inv-lbl { font-size: 10.5px; font-weight: 500; letter-spacing: .08em; text-transform: uppercase; color: #9C9089; margin-bottom: 7px; }
            .vy-inv-inwrap { display: flex; align-items: center; gap: 8px; border: 1px solid rgba(120,70,40,.14); background: rgba(255,255,255,.75); border-radius: 14px; padding: 11px 14px; }
            .vy-inv-inwrap input { flex: 1; min-width: 0; border: none; outline: none; background: transparent; font-family: inherit; font-size: 13.5px; color: #2B2320; padding: 0; }
            .vy-inv-seg { display: flex; gap: 6px; padding: 4px; border-radius: 14px; background: rgba(120,70,40,.06); border: 1px solid rgba(120,70,40,.10); }
            .vy-inv-segopt { flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 7px; padding: 10px 0; border-radius: 11px; border: none; background: transparent; color: #8A8079; font-family: inherit; font-size: 12.5px; font-weight: 500; cursor: pointer; transition: background .14s; }
            .vy-inv-segopt.on { background: #fff; color: #C2410C; box-shadow: 0 3px 10px rgba(150,90,50,.14); }
            .vy-inv-help { font-size: 11px; color: #A69A92; margin-top: 7px; }
            .vy-inv-chip { display: inline-flex; align-items: center; gap: 8px; padding: 9px 13px; border-radius: 30px; background: rgba(255,255,255,.6); border: 1px solid rgba(120,70,40,.14); cursor: pointer; font-family: inherit; font-size: 12.5px; font-weight: 500; color: #8A8079; transition: transform .14s, background .14s; }
            .vy-inv-chip:hover { transform: translateY(-1px); }
            .vy-inv-chip.on { background: rgba(217,102,46,.12); border-color: rgba(232,112,58,.5); color: #B4400F; }
            .vy-inv-chipbox { width: 16px; height: 16px; border-radius: 5px; border: 1.5px solid rgba(120,70,40,.28); background: transparent; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
            .vy-inv-chip.on .vy-inv-chipbox { border: none; background: linear-gradient(140deg,#E8703A,#C2410C); }
            .vy-inv-access { display: flex; align-items: center; gap: 11px; padding: 12px 14px; border-radius: 14px; background: rgba(120,70,40,.06); border: 1px solid rgba(120,70,40,.10); }
            .vy-inv-access-ico { width: 32px; height: 32px; border-radius: 10px; background: rgba(217,102,46,.13); color: #B4400F; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
            .vy-inv-cancel { flex: 1; padding: 12px 0; border-radius: 30px; background: rgba(255,255,255,.65); border: 1px solid rgba(120,70,40,.16); color: #8A8079; font-family: inherit; font-size: 13.5px; font-weight: 500; cursor: pointer; text-align: center; }
            .vy-inv-cancel:hover { background: rgba(120,70,40,.06); }
            .vy-inv-send { flex: 2; padding: 12px 0; border-radius: 30px; background: linear-gradient(140deg,#E8703A,#C2410C); border: none; color: #fff; font-family: inherit; font-size: 13.5px; font-weight: 500; cursor: pointer; box-shadow: 0 8px 22px rgba(194,65,12,.40); display: inline-flex; align-items: center; justify-content: center; gap: 8px; transition: transform .14s, box-shadow .14s; }
            .vy-inv-send:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(194,65,12,.48); }
        </style>
        <div class="vy-inv-scrim" x-cloak x-show="open" x-transition.opacity
             @keydown.escape.window="open = false" @click.self="open = false">
            <div class="vy-inv-modal" role="dialog" aria-modal="true" aria-labelledby="vy-invite-title">

                {{-- ══ FORM VIEW ══ --}}
                <form method="POST" action="{{ route('app.owners.invite') }}" x-show="! sent">
                    @csrf

                    {{-- header --}}
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:18px">
                        <div style="display:flex;align-items:center;gap:12px;min-width:0">
                            <span class="vy-inv-ico">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.4"/><path d="M2.5 20c.8-3.2 3.4-5 6.5-5c1 0 2 .2 2.8.5"/><path d="M17 8.5v6"/><path d="M14 11.5h6"/></svg>
                            </span>
                            <div style="min-width:0">
                                <div id="vy-invite-title" style="font-family:var(--vy-display,'Sora'),sans-serif;font-weight:600;font-size:19px;letter-spacing:-.01em" x-text="roleTitle()">Invite an owner</div>
                                <div style="font-size:12px;color:#A69A92;margin-top:2px;line-height:1.4">They’ll get an email to set their password and access their portal.</div>
                            </div>
                        </div>
                        <button type="button" class="vy-inv-x" @click="open = false" aria-label="Close">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12"/><path d="M18 6 6 18"/></svg>
                        </button>
                    </div>

                    {{-- name --}}
                    <div style="margin-bottom:12px">
                        <div class="vy-inv-lbl" x-text="nameLabel()">Owner name</div>
                        <div class="vy-inv-inwrap">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#A69A92" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><circle cx="12" cy="8" r="3.5"/><path d="M5 20c.9-3.4 3.7-5.5 7-5.5s6.1 2.1 7 5.5"/></svg>
                            <input type="text" name="name" value="{{ old('name') }}" maxlength="120" required placeholder="Person or company">
                        </div>
                        @error('name')<div class="vy-inv-help" style="color:var(--vy-accent)">{{ $message }}</div>@enderror
                    </div>

                    {{-- email --}}
                    <div style="margin-bottom:16px">
                        <div class="vy-inv-lbl">Email</div>
                        <div class="vy-inv-inwrap">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#A69A92" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="M3 8l9 6 9-6"/></svg>
                            <input type="email" name="email" value="{{ old('email') }}" maxlength="190" required placeholder="Where the invite is sent">
                        </div>
                        @error('email')<div class="vy-inv-help" style="color:var(--vy-accent)">{{ $message }}</div>@enderror
                    </div>

                    {{-- role --}}
                    <div style="margin-bottom:16px">
                        <div class="vy-inv-lbl">Role</div>
                        <input type="hidden" name="role" :value="role">
                        <div class="vy-inv-seg">
                            <button type="button" class="vy-inv-segopt" :class="{ on: role === 'owner' }" @click="role = 'owner'">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9.5 21v-5h5v5"/></svg>
                                Owner
                            </button>
                            <button type="button" class="vy-inv-segopt" :class="{ on: role === 'manager' }" @click="role = 'manager'">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5.5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2V7"/></svg>
                                Manager
                            </button>
                            <button type="button" class="vy-inv-segopt" :class="{ on: role === 'accountant' }" @click="role = 'accountant'">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 3v18l2-1 2 1 2-1 2 1 2-1 2 1 2-1V3l-2 1-2-1-2 1-2-1-2 1-2-1z"/><path d="M8 8h8"/><path d="M8 12h5"/></svg>
                                Accountant
                            </button>
                        </div>
                        <div class="vy-inv-help" x-show="role === 'owner'">Owns units; sees statements, payouts and unit performance.</div>
                        <div class="vy-inv-help" x-show="role === 'manager'" x-cloak>Operator staff — manages bookings, calendar, guests and maintenance.</div>
                        <div class="vy-inv-help" x-show="role === 'accountant'" x-cloak>Read &amp; export access to statements, tax documents and payouts.</div>
                    </div>

                    {{-- assign units — OWNER only (the real manager/accountant are org-wide staff; units don't
                         apply + are dropped server-side, so we never show a no-op control, #39) --}}
                    @if (count($units) > 0)
                        <div style="margin-bottom:16px" x-show="role === 'owner'">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
                                <div class="vy-inv-lbl" style="margin-bottom:0">Assign units <span style="text-transform:none;letter-spacing:0;color:#B7ADA4;font-weight:400">· optional</span></div>
                                <span style="font-size:11px;color:#A69A92" x-text="units.length === 0 ? 'None selected' : units.length"></span>
                            </div>
                            <div style="display:flex;flex-wrap:wrap;gap:8px">
                                @foreach ($units as $u)
                                    <label class="vy-inv-chip" :class="{ on: units.includes(@js((string) $u['id'])) }">
                                        <input type="checkbox" name="unit_ids[]" value="{{ $u['id'] }}" x-model="units" style="position:absolute;opacity:0;width:0;height:0">
                                        <span class="vy-inv-chipbox"><svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round" :style="units.includes(@js((string) $u['id'])) ? 'opacity:1' : 'opacity:0'"><path d="M4.5 12.5 9.5 17.5 19.5 7"/></svg></span>
                                        {{ $u['name'] ?? $u['code'] }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- management agreement — OWNER only; the chosen % is the REAL agreement rate --}}
                    <div style="margin-bottom:20px" x-show="role === 'owner'">
                        <div class="vy-inv-lbl">Management agreement</div>
                        <input type="hidden" name="commission_pct" :value="commission === 'later' ? '' : commission">
                        <div class="vy-inv-seg">
                            @foreach (['12', '15', '18'] as $preset)
                                <button type="button" class="vy-inv-segopt" :class="{ on: commission === @js($preset) }" @click="commission = @js($preset)">{{ $preset }}%</button>
                            @endforeach
                            <button type="button" class="vy-inv-segopt" style="flex:1.5" :class="{ on: commission === 'later' }" @click="commission = 'later'">Set up later</button>
                        </div>
                        <div class="vy-inv-help">
                            <span x-show="commission !== 'later'"><span x-text="commission"></span>% of gross booking value · applied to this owner’s statements. Change it any time.</span>
                            <span x-show="commission === 'later'" x-cloak>You can set the commission when their agreement is ready.</span>
                        </div>
                        @error('commission_pct')<div class="vy-inv-help" style="color:var(--vy-accent)">{{ $message }}</div>@enderror
                    </div>

                    {{-- access level — ACCOUNTANT (read & export) --}}
                    <div style="margin-bottom:20px" x-show="role === 'accountant'" x-cloak>
                        <div class="vy-inv-lbl">Access level</div>
                        <div class="vy-inv-access">
                            <span class="vy-inv-access-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5c-5 0-8.5 4.2-9.5 7c1 2.8 4.5 7 9.5 7s8.5-4.2 9.5-7c-1-2.8-4.5-7-9.5-7z"/><circle cx="12" cy="12" r="2.6"/></svg></span>
                            <span style="min-width:0">
                                <span style="display:block;font-size:13px;font-weight:500">Read &amp; export</span>
                                <span style="display:block;font-size:11.5px;color:#8A8079;margin-top:1px;line-height:1.45">Statements, tax documents and payouts. No commission applies.</span>
                            </span>
                        </div>
                    </div>

                    {{-- access level — MANAGER (full operations) --}}
                    <div style="margin-bottom:20px" x-show="role === 'manager'" x-cloak>
                        <div class="vy-inv-lbl">Access level</div>
                        <div class="vy-inv-access">
                            <span class="vy-inv-access-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5.5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2V7"/><path d="M3 13h18"/></svg></span>
                            <span style="min-width:0">
                                <span style="display:block;font-size:13px;font-weight:500">Full operations</span>
                                <span style="display:block;font-size:11.5px;color:#8A8079;margin-top:1px;line-height:1.45">Bookings, calendar, guest messaging and maintenance. No owner financials.</span>
                            </span>
                        </div>
                    </div>

                    {{-- actions --}}
                    <div style="display:flex;gap:10px">
                        <button type="button" class="vy-inv-cancel" @click="open = false">Cancel</button>
                        <button type="submit" class="vy-inv-send">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4z"/></svg>
                            Send invite
                        </button>
                    </div>
                </form>

                {{-- ══ SENT VIEW (rendered on the real PRG via session('owner_invite_sent')) ══ --}}
                <div x-show="sent" x-cloak>
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:4px">
                        <div></div>
                        <button type="button" class="vy-inv-x" @click="open = false; sent = false" aria-label="Close">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12"/><path d="M18 6 6 18"/></svg>
                        </button>
                    </div>
                    <div style="display:flex;flex-direction:column;align-items:center;padding:0 0 18px">
                        <div style="position:relative;width:72px;height:72px;margin-bottom:14px">
                            <div style="position:absolute;inset:-12px;border-radius:50%;background:radial-gradient(circle,rgba(232,112,58,.28),transparent 70%);filter:blur(9px)"></div>
                            <div style="position:relative;width:72px;height:72px;border-radius:50%;background:linear-gradient(140deg,#E8703A,#C2410C);box-shadow:0 12px 30px rgba(194,65,12,.40);display:flex;align-items:center;justify-content:center">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4z"/></svg>
                            </div>
                        </div>
                        <div style="font-family:var(--vy-display,'Sora'),sans-serif;font-weight:600;font-size:20px;letter-spacing:-.02em;text-align:center">Invite sent</div>
                        <div style="font-size:12.5px;color:#8A8079;margin-top:6px;text-align:center;line-height:1.55">We emailed <span style="font-weight:500;color:#2B2320" x-text="sentEmail"></span> a link to set their password.<br>They’ll appear as <span style="font-weight:500;color:#2B2320">Pending</span> until they activate.</div>
                    </div>
                    <div style="display:flex;align-items:center;gap:12px;padding:12px 16px;border-radius:14px;background:rgba(120,70,40,.06);border:1px solid rgba(120,70,40,.10);margin-bottom:18px">
                        <span style="width:30px;height:30px;border-radius:50%;background:rgba(217,102,46,.13);color:#B4400F;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/></svg>
                        </span>
                        <span style="min-width:0;flex:1;font-size:12px;color:#8A8079;line-height:1.5">The link expires <template x-if="sentExpires"><span>on <span style="font-weight:500;color:#4A4038" x-text="sentExpires"></span></span></template><template x-if="! sentExpires"><span>in 7 days</span></template>. You can resend it any time from the owner’s row.</span>
                    </div>
                    <a href="{{ route('app.owners.invite.preview') }}" target="_blank" rel="noopener" style="display:flex;align-items:center;justify-content:center;gap:8px;width:100%;box-sizing:border-box;padding:12px 0;border-radius:30px;background:rgba(255,255,255,.65);border:1px solid rgba(120,70,40,.14);color:#2B2320;font-family:inherit;font-size:13px;font-weight:500;text-decoration:none">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#C2410C" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2.5"/><path d="M22 7 12 13 2 7"/></svg>
                        Preview the invite email
                    </a>
                    <button type="button" @click="open = false; sent = false" style="display:block;width:100%;box-sizing:border-box;margin-top:10px;padding:12px 0;border-radius:30px;background:linear-gradient(140deg,#E8703A,#C2410C);border:none;color:#fff;font-family:inherit;font-size:13.5px;font-weight:500;cursor:pointer;box-shadow:0 8px 22px rgba(194,65,12,.40);text-align:center">Back to owners</button>
                </div>
            </div>
        </div>
        @endcan

        </div>
    </x-operator.shell>
</div>
