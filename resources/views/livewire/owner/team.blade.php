{{-- Owner Team & access (exact-match pass · invariant #65) — rebuilt to the decoded "Owner Team" wireframe on the
     REAL owner-team feature (Phase 7c), REUSED not rebuilt: members ({@see OwnerUser}), role change, remove, invite
     ({@see OwnerTeamInviteService}), pending revoke + a NEW real resend — every mutation a controller POST behind
     EnsurePrimaryOwner (so /livewire/update can't bypass the gate, #20). The "what each role can see" matrix is REAL
     from OwnerTeamRole::CAPABILITIES (anti-drift with the route gates). Recent activity is TRUTHFUL (real sign-ins +
     invites, never a fabricated event log). Wave B · GATE 5: the "watermarked exports" claim is now TRUE — every
     financial export is stamped with the downloader + recorded in the real download log (ExportDownload). (Old note:
     implemented, #39). Shell from <x-owner.shell active="team">.
     RAW-BLADE RULE: every `@` follows a space, newline or tag (unregistered @click passes through to Alpine). --}}
<x-owner.shell active="team" title="Team & access" subtitle="Give a co-owner or your accountant their own sign-in — scoped to what they need." :viewer="$viewer">
    <style>
        .vy-tm-row { display:flex; flex-wrap:wrap; gap:20px; align-items:flex-start; }
        .vy-tm-left { flex:999 1 420px; min-width:0; display:flex; flex-direction:column; gap:20px; }
        .vy-tm-right { flex:1 1 290px; min-width:270px; display:flex; flex-direction:column; gap:20px; }

        .vy-tm-member { display:flex; align-items:center; gap:13px; padding:13px 15px; border-radius:15px; background:rgba(255,255,255,.45); border:1px solid var(--vy-glass-border); }
        .vy-tm-av { width:42px; height:42px; border-radius:50%; color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-family:var(--vy-display); font-weight:600; font-size:14px; background:var(--vy-grad); }
        .vy-tm-rolepill { display:inline-flex; padding:2px 9px; border-radius:30px; font-size:10.5px; font-weight:600; }
        .vy-tm-rolepill--co { background:rgba(217,102,46,.12); color:#B4400F; }
        .vy-tm-rolepill--acc { background:rgba(79,122,70,.13); color:var(--vy-green-text); }
        .vy-tm-rolepill--view { background:rgba(120,70,40,.1); color:var(--vy-on-grad); }
        .vy-tm-rolepill--owner { background:var(--vy-wash,#F6EBE2); color:var(--vy-muted); }

        .vy-tm-pick { text-align:left; padding:14px 15px; border-radius:14px; cursor:pointer; font-family:var(--vy-sans); transition:all .14s; background:rgba(255,255,255,.5); border:1.5px solid var(--vy-glass-border); }
        .vy-tm-pick.is-on { border-color:var(--vy-accent); background:rgba(217,102,46,.06); }
        .vy-tm-pick .dot { width:16px; height:16px; border-radius:50%; border:5px solid rgba(120,70,40,.25); background:#fff; box-sizing:border-box; flex-shrink:0; }
        .vy-tm-pick.is-on .dot { border-color:var(--vy-accent); }

        .vy-tm-pending { display:flex; align-items:center; gap:12px; padding:11px 14px; border-radius:13px; background:rgba(224,146,30,.08); border:1px dashed rgba(214,133,26,.35); }
        .vy-tm-pending-ic { width:34px; height:34px; border-radius:50%; background:rgba(224,146,30,.16); color:var(--vy-amber-text); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .vy-tm-mini { padding:5px 12px; border-radius:30px; font-family:var(--vy-sans); font-size:11.5px; font-weight:500; cursor:pointer; background:rgba(255,255,255,.7); border:1px solid rgba(214,133,26,.3); color:var(--vy-amber-text); }
        .vy-tm-mini--danger { background:transparent; border-color:rgba(190,32,28,.3); color:#B01E1E; }
        .vy-tm-mini--danger:hover { background:rgba(190,32,28,.08); }

        .vy-tm-matrix { display:grid; grid-template-columns:1.6fr repeat(3,1fr); gap:8px 10px; align-items:center; }
        .vy-tm-matrix .head { font-size:10.5px; letter-spacing:.05em; text-transform:uppercase; color:var(--vy-faint); text-align:center; }
        .vy-tm-matrix .area { font-size:12.5px; font-weight:500; color:var(--vy-ink); padding:7px 0; border-top:1px solid rgba(200,180,165,.25); }
        .vy-tm-matrix .cell { text-align:center; padding:7px 0; border-top:1px solid rgba(200,180,165,.25); font-size:13px; font-weight:600; }
        .vy-tm-matrix .cell.yes { color:var(--vy-green-text); }
        .vy-tm-matrix .cell.no { color:var(--vy-faint); }

        .vy-tm-act { display:flex; align-items:flex-start; gap:11px; padding:10px 0; border-bottom:1px solid rgba(200,180,165,.2); }
        .vy-tm-act-av { width:30px; height:30px; border-radius:50%; background:var(--vy-grad); color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-family:var(--vy-display); font-weight:600; font-size:11px; }
        .vy-tm-sec { background:var(--vy-grad-dark); border-radius:20px; box-shadow:var(--vy-shadow-dark); padding:22px; color:#F3E7DD; }
        .vy-tm-sec-ic { width:40px; height:40px; border-radius:12px; background:rgba(255,255,255,.14); display:flex; align-items:center; justify-content:center; margin-bottom:13px; }
    </style>

    @if (session('team_status'))
        <div class="vy-card vy-fade" style="margin-bottom:14px;border-left:3px solid var(--vy-accent);padding:12px 16px;font-size:13.5px;color:var(--vy-ink)">{{ session('team_status') }}</div>
    @endif
    @if (session('team_error'))
        <div class="vy-card vy-fade" style="margin-bottom:14px;border-left:3px solid #C2453A;padding:12px 16px;font-size:13.5px;color:#8A2F27">{{ session('team_error') }}</div>
    @endif
    @error('email')
        <div class="vy-card vy-fade" style="margin-bottom:14px;border-left:3px solid #C2453A;padding:12px 16px;font-size:13.5px;color:#8A2F27">{{ $message }}</div>
    @enderror

    <div class="vy-tm-row">
        {{-- ── LEFT: people · invite (+ pending) · role matrix ── --}}
        <div class="vy-tm-left">
            {{-- People with access — REAL members (you + sub-users), truthful last-active, real Remove --}}
            <section class="vy-card vy-fade">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px">
                    <h2 class="vy-card-title" style="font-size:17px">People with access</h2>
                    <span style="display:inline-flex;padding:4px 11px;border-radius:30px;background:rgba(120,70,40,.09);color:var(--vy-on-grad);font-size:12px;font-weight:600">{{ count($members) + 1 }}</span>
                </div>
                <div style="display:flex;flex-direction:column;gap:10px">
                    {{-- You — the account owner (implicit co-owner, not removable) --}}
                    <div class="vy-tm-member">
                        <span class="vy-tm-av">{{ $viewer['initials'] }}</span>
                        <div style="flex:1;min-width:0">
                            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                                <span style="font-size:14px;font-weight:600;color:var(--vy-ink)">{{ $you['name'] }}</span>
                                <span class="vy-tm-rolepill vy-tm-rolepill--owner">Account owner</span>
                                <span style="font-size:11px;color:var(--vy-faint)">that’s you</span>
                            </div>
                            <div style="font-size:12px;color:var(--vy-muted);margin-top:3px">Full access to everything, including this page.</div>
                        </div>
                        <div style="text-align:right;flex-shrink:0"><div style="font-size:11.5px;color:var(--vy-faint)">Active now</div></div>
                    </div>

                    @forelse ($members as $member)
                        @php $pillMod = ['co_owner' => 'co', 'accountant' => 'acc', 'view_only' => 'view'][$member['role']] ?? 'view'; @endphp
                        <div class="vy-tm-member">
                            <span class="vy-tm-av" style="background:linear-gradient(140deg,#6FA764,#4F7A46)">{{ \Illuminate\Support\Str::of($member['name'])->explode(' ')->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}</span>
                            <div style="flex:1;min-width:0">
                                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                                    <span style="font-size:14px;font-weight:600;color:var(--vy-ink)">{{ $member['name'] }}</span>
                                    <span class="vy-tm-rolepill vy-tm-rolepill--{{ $pillMod }}">{{ $member['role_label'] }}</span>
                                </div>
                                <div style="font-size:12px;color:var(--vy-muted);margin-top:3px">{{ $member['role_summary'] }}</div>
                            </div>
                            <div style="text-align:right;flex-shrink:0;display:flex;flex-direction:column;gap:6px;align-items:flex-end">
                                <div style="font-size:11.5px;color:var(--vy-faint)">{{ $member['last_active'] }}</div>
                                {{-- Role change (real, retained over the wireframe's display-only pill) + Remove --}}
                                <form method="POST" action="{{ route('owner.team.members.role', $member['id']) }}" style="display:flex;gap:6px;align-items:center;margin:0" x-data="{ ask: false }">
                                    @csrf
                                    @method('PATCH')
                                    <select name="role" class="vy-input" style="padding:4px 7px;font-size:11.5px" aria-label="Role for {{ $member['name'] }}" @change="ask = true">
                                        @foreach ($roles as $role)
                                            <option value="{{ $role['value'] }}" @selected($member['role'] === $role['value'])>{{ $role['label'] }}</option>
                                        @endforeach
                                    </select>
                                    <input x-show="ask" x-cloak type="password" name="current_password" class="vy-input"
                                           style="padding:4px 7px;font-size:11.5px;max-width:150px" placeholder="Your password"
                                           autocomplete="current-password" aria-label="Confirm with your password">
                                    <button type="submit" class="vy-tm-mini">Save</button>
                                </form>
                                <form method="POST" action="{{ route('owner.team.members.remove', $member['id']) }}" style="display:flex;gap:6px;align-items:center;margin:0" onsubmit="return confirm('Remove {{ $member['name'] }}? They can no longer sign in.')">
                                    @csrf
                                    @method('DELETE')
                                    <input type="password" name="current_password" class="vy-input"
                                           style="padding:4px 7px;font-size:11.5px;max-width:150px" placeholder="Your password"
                                           autocomplete="current-password" required aria-label="Confirm with your password">
                                    <button type="submit" class="vy-tm-mini vy-tm-mini--danger">Remove</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="vy-empty">Just you so far — invite a co-owner or accountant below.</div>
                    @endforelse
                </div>
            </section>

            {{-- Invite someone — REAL invite (role picker + email → owner.team.invite) + pending (real resend/revoke) --}}
            <section class="vy-card vy-fade" style="animation-delay:.04s" x-data="{ role: 'accountant' }">
                <h2 class="vy-card-title" style="font-size:17px;margin-bottom:4px">Invite someone</h2>
                <p class="vy-line-sub" style="font-size:12.5px;margin:0 0 18px">They get their own sign-in — you can remove access anytime.</p>

                <form method="POST" action="{{ route('owner.team.invite') }}">
                    @csrf
                    <input type="hidden" name="role" :value="role">
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:10px;margin-bottom:16px">
                        @foreach ($roles as $role)
                            <button type="button" class="vy-tm-pick" :class="role === @js($role['value']) ? 'is-on' : ''" @click="role = @js($role['value'])">
                                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                                    <span class="dot"></span>
                                    <span style="font-size:13.5px;font-weight:600;color:var(--vy-ink)">{{ $role['label'] }}</span>
                                </div>
                                <div style="font-size:11.5px;color:var(--vy-muted);line-height:1.45">{{ $role['summary'] }}</div>
                            </button>
                        @endforeach
                    </div>
                    <div style="display:flex;gap:10px;flex-wrap:wrap">
                        <input type="email" name="email" aria-label="Email address to invite" value="{{ old('email') }}" placeholder="name@email.com" class="vy-input" style="flex:1;min-width:200px;border-radius:30px;padding:12px 14px" autocomplete="off" required>
                            {{-- ⚠️ RE-AUTH: an invite mints a NEW authenticated principal, which outlives every
                                 later password reset and session revocation. --}}
                            <input type="password" name="current_password" aria-label="Confirm with your password" placeholder="Your password" class="vy-input" style="flex:1;min-width:170px;border-radius:30px;padding:12px 14px" autocomplete="current-password" required>
                        <button type="submit" class="vy-btn vy-btn--accent" style="padding:12px 22px;border-radius:30px">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2 11 13M22 2 15 22l-4-9-9-4z"/></svg>
                            Send invite
                        </button>
                    </div>
                </form>

                @if (count($invites) > 0)
                    <div style="margin-top:18px;padding-top:16px;border-top:1px solid rgba(200,180,165,.3)">
                        <div style="font-size:12px;font-weight:500;letter-spacing:.04em;text-transform:uppercase;color:var(--vy-faint);margin-bottom:10px">Pending invites</div>
                        <div style="display:flex;flex-direction:column;gap:8px">
                            @foreach ($invites as $invite)
                                <div class="vy-tm-pending">
                                    <span class="vy-tm-pending-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="m3 7 9 6 9-6"/></svg></span>
                                    <div style="flex:1;min-width:0">
                                        <div style="font-size:13px;font-weight:600;color:var(--vy-ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $invite['email'] }}</div>
                                        <div style="font-size:11.5px;color:var(--vy-amber-text);margin-top:2px">{{ $invite['role_label'] }} · {{ $invite['expires'] }}</div>
                                    </div>
                                    <form method="POST" action="{{ route('owner.team.invites.resend', $invite['id']) }}" style="margin:0">
                                        @csrf
                                        <button type="submit" class="vy-tm-mini">Resend</button>
                                    </form>
                                    <form method="POST" action="{{ route('owner.team.invites.revoke', $invite['id']) }}" style="margin:0">
                                        @csrf
                                        <button type="submit" class="vy-tm-mini vy-tm-mini--danger">Revoke</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>

            {{-- What each role can see — the REAL capability matrix (OwnerTeamRole::CAPABILITIES) --}}
            <section class="vy-card vy-fade" style="animation-delay:.08s">
                <h2 class="vy-card-title" style="font-size:16px;margin-bottom:14px">What each role can see</h2>
                <div class="vy-tm-matrix">
                    <div></div>
                    <div class="head">Co-owner</div>
                    <div class="head">Accountant</div>
                    <div class="head">View only</div>
                    @foreach ($matrix as $row)
                        <div class="area">{{ $row['label'] }}</div>
                        <div class="cell {{ $row['co_owner'] ? 'yes' : 'no' }}">{{ $row['co_owner'] ? '✓' : '—' }}</div>
                        <div class="cell {{ $row['accountant'] ? 'yes' : 'no' }}">{{ $row['accountant'] ? '✓' : '—' }}</div>
                        <div class="cell {{ $row['view_only'] ? 'yes' : 'no' }}">{{ $row['view_only'] ? '✓' : '—' }}</div>
                    @endforeach
                </div>
                <p class="vy-line-sub" style="font-size:11.5px;margin-top:14px">Only you, the account owner, can manage the team and the payout account.</p>
            </section>
        </div>

        {{-- ── RIGHT: recent activity (truthful) · security note (honest) ── --}}
        <div class="vy-tm-right">
            <section class="vy-card vy-fade" style="animation-delay:.04s">
                <h2 class="vy-card-title" style="font-size:16px;margin-bottom:14px">Recent activity</h2>
                @forelse ($activity as $a)
                    <div class="vy-tm-act">
                        <span class="vy-tm-act-av">{{ $a['initials'] }}</span>
                        <div style="flex:1;min-width:0">
                            <div style="font-size:12.5px;color:var(--vy-ink);line-height:1.4">{{ $a['what'] }}</div>
                            <div style="font-size:11px;color:var(--vy-faint);margin-top:2px">{{ $a['when'] }}</div>
                        </div>
                    </div>
                @empty
                    <div class="vy-empty" style="padding:22px 10px">Nothing yet — invites and sign-ins will show up here.</div>
                @endforelse
            </section>

            {{-- Download log (Wave B · GATE 5) — the REAL financial-export audit: who downloaded what, when. The
                 SAME identity is watermarked onto every exported PDF/CSV, so the claim below is now TRUE. --}}
            <section class="vy-card vy-fade" style="animation-delay:.06s">
                <h2 class="vy-card-title">Export download log</h2>
                <p class="vy-line-sub" style="font-size:12px;margin:2px 0 12px">Every statement, booking, payout or tax file your team downloads is stamped with who downloaded it — and recorded here.</p>
                @forelse ($downloads as $d)
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:9px 0;border-top:1px solid var(--vy-hairline)">
                        <div style="min-width:0">
                            <div style="font-size:13px;font-weight:500;color:var(--vy-ink)">{{ $d['what'] }}</div>
                            <div style="font-size:11px;color:var(--vy-faint)">{{ $d['who'] }}</div>
                        </div>
                        <span style="font-size:11.5px;color:var(--vy-muted);white-space:nowrap">{{ $d['when'] }}</span>
                    </div>
                @empty
                    <div class="vy-empty" style="padding:18px 10px">No exports downloaded yet — they’ll appear here, stamped with who downloaded them.</div>
                @endforelse
            </section>

            {{-- Security note — the "watermarked exports" claim is now TRUE (Wave B · GATE 5). --}}
            <section class="vy-tm-sec vy-fade" style="animation-delay:.08s">
                <span class="vy-tm-sec-ic" aria-hidden="true"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="#F7D9C4" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg></span>
                <h2 class="vy-card-title" style="font-size:15px;color:#fff">Everyone gets their own key</h2>
                <p style="font-size:12.5px;line-height:1.6;color:#D6C4B8;margin-top:8px">No shared passwords — each person signs in with their own account, and access is scoped to their role. Financial exports are watermarked with who downloaded them and recorded in the log above. For your own account, turn on 2-step verification.</p>
                <a href="{{ route('owner.security') }}" style="display:inline-flex;align-items:center;gap:7px;margin-top:15px;font-size:12.5px;font-weight:500;color:#F7B98E;text-decoration:none">Security settings<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
            </section>
        </div>
    </div>
</x-owner.shell>
