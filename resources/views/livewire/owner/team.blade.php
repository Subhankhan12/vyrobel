{{-- Owner Team (Phase 7c · SG3) — the PRIMARY OWNER manages their team: invite a sub-user, change a role,
     remove a member, revoke a pending invite. READ-ONLY component; every form POSTs to a controller route
     behind EnsurePrimaryOwner (so /livewire/update can't bypass the gate). Shell from <x-owner.shell>.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-owner.shell active="team" title="Team & access" subtitle="Invite people to help manage your properties — at the access level you choose." :viewer="$viewer">

    @if (session('team_status'))
        <div class="vy-card" style="margin-bottom:14px;border-left:3px solid var(--vy-accent);padding:12px 16px;font-size:13.5px;color:var(--vy-ink)">{{ session('team_status') }}</div>
    @endif
    @if (session('team_error'))
        <div class="vy-card" style="margin-bottom:14px;border-left:3px solid #C2453A;padding:12px 16px;font-size:13.5px;color:#8A2F27">{{ session('team_error') }}</div>
    @endif
    @error('email')
        <div class="vy-card" style="margin-bottom:14px;border-left:3px solid #C2453A;padding:12px 16px;font-size:13.5px;color:#8A2F27">{{ $message }}</div>
    @enderror

    <div style="display:grid;grid-template-columns:1.4fr 1fr;gap:18px;align-items:start">

        {{-- LEFT — the team roster (you + members + pending invites) --}}
        <div class="vy-stack" style="display:flex;flex-direction:column;gap:14px">
            <section class="vy-card">
                <h2 class="vy-card-title" style="margin-bottom:12px">Your team</h2>

                {{-- You — the account owner (implicit co-owner, not removable) --}}
                <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--vy-hairline)">
                    <div>
                        <div style="font-size:13.5px;font-weight:600;color:var(--vy-ink)">{{ $you['name'] }} <span style="font-size:11.5px;color:var(--vy-faint);font-weight:500">· you</span></div>
                        <div class="vy-line-sub" style="font-size:12px">{{ $you['email'] }}</div>
                    </div>
                    <span style="display:inline-block;padding:4px 11px;border-radius:14px;background:var(--vy-wash,#F6EBE2);font-size:11.5px;color:var(--vy-muted);white-space:nowrap">Account owner</span>
                </div>

                @forelse ($members as $member)
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;padding:12px 0;border-bottom:1px solid var(--vy-hairline)">
                        <div style="min-width:0">
                            <div style="font-size:13.5px;font-weight:600;color:var(--vy-ink)">{{ $member['name'] }}</div>
                            <div class="vy-line-sub" style="font-size:12px;overflow:hidden;text-overflow:ellipsis">{{ $member['email'] }}</div>
                            <div class="vy-line-sub" style="font-size:11.5px;color:var(--vy-faint);margin-top:2px">{{ $member['role_summary'] }} · {{ $member['last_active'] }}</div>
                        </div>
                        <div style="display:flex;flex-direction:column;gap:6px;align-items:flex-end;flex-shrink:0">
                            {{-- Change role — PATCH to the primary-owner-gated route --}}
                            <form method="POST" action="{{ route('owner.team.members.role', $member['id']) }}" style="display:flex;gap:6px;align-items:center;margin:0">
                                @csrf
                                @method('PATCH')
                                <select name="role" class="vy-input" style="padding:5px 8px;font-size:12px" aria-label="Role for {{ $member['name'] }}">
                                    @foreach ($roles as $role)
                                        <option value="{{ $role['value'] }}" @selected($member['role'] === $role['value'])>{{ $role['label'] }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="vy-btn vy-btn--ghost" style="padding:5px 10px;font-size:12px">Save</button>
                            </form>
                            {{-- Remove — DELETE; revokes access (an orphaned login is disabled) --}}
                            <form method="POST" action="{{ route('owner.team.members.remove', $member['id']) }}" style="margin:0" onsubmit="return confirm('Remove {{ $member['name'] }} from your team? They will lose access immediately.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="vy-btn vy-btn--ghost" style="padding:5px 10px;font-size:12px;color:#8A2F27">Remove</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="vy-empty" style="margin-top:10px">No team members yet — invite someone on the right.</div>
                @endforelse
            </section>

            @if (count($invites) > 0)
                <section class="vy-card">
                    <h2 class="vy-card-title" style="margin-bottom:10px">Pending invites</h2>
                    @foreach ($invites as $invite)
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--vy-hairline)">
                            <div style="min-width:0">
                                <div style="font-size:13px;font-weight:600;color:var(--vy-ink);overflow:hidden;text-overflow:ellipsis">{{ $invite['email'] }}</div>
                                <div class="vy-line-sub" style="font-size:11.5px">{{ $invite['role_label'] }} · {{ $invite['expires'] }}</div>
                            </div>
                            <form method="POST" action="{{ route('owner.team.invites.revoke', $invite['id']) }}" style="margin:0">
                                @csrf
                                <button type="submit" class="vy-btn vy-btn--ghost" style="padding:5px 10px;font-size:12px">Revoke</button>
                            </form>
                        </div>
                    @endforeach
                </section>
            @endif
        </div>

        {{-- RIGHT — invite someone + the role legend --}}
        <div class="vy-stack" style="display:flex;flex-direction:column;gap:14px">
            <section class="vy-card">
                <h2 class="vy-card-title" style="margin-bottom:4px">Invite someone</h2>
                <p class="vy-line-sub" style="font-size:12px;margin:0 0 12px">They’ll get an email to set a password and join your team.</p>

                <form method="POST" action="{{ route('owner.team.invite') }}">
                    @csrf
                    <div class="vy-field" style="margin-bottom:10px">
                        <label class="vy-field-label" for="invite_name">Name <span style="color:var(--vy-faint)">(optional)</span></label>
                        <input id="invite_name" type="text" name="name" value="{{ old('name') }}" class="vy-input" autocomplete="off" placeholder="Their name">
                    </div>
                    <div class="vy-field" style="margin-bottom:10px">
                        <label class="vy-field-label" for="invite_email">Email</label>
                        <input id="invite_email" type="email" name="email" value="{{ old('email') }}" class="vy-input" autocomplete="off" placeholder="name@email.com" required>
                    </div>
                    <div class="vy-field" style="margin-bottom:14px">
                        <label class="vy-field-label" for="invite_role">Access level</label>
                        <select id="invite_role" name="role" class="vy-input" required>
                            @foreach ($roles as $role)
                                <option value="{{ $role['value'] }}" @selected(old('role') === $role['value'])>{{ $role['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="vy-btn vy-btn--accent" style="width:100%;justify-content:center">Send invite</button>
                </form>
            </section>

            <section class="vy-card">
                <h2 class="vy-card-title" style="margin-bottom:10px">Access levels</h2>
                @foreach ($roles as $role)
                    <div style="padding:8px 0;border-bottom:1px solid var(--vy-hairline)">
                        <div style="font-size:13px;font-weight:600;color:var(--vy-ink)">{{ $role['label'] }}</div>
                        <div class="vy-line-sub" style="font-size:12px;margin-top:1px">{{ $role['summary'] }}</div>
                    </div>
                @endforeach
                <p class="vy-line-sub" style="font-size:11.5px;margin-top:10px">Only you, the account owner, can manage the team and the payout account.</p>
            </section>
        </div>
    </div>
</x-owner.shell>
