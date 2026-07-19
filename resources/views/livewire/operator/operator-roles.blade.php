{{-- Operator ROLES (Phase 1 · Sub-gate 3) — the security-first redesign. VIEW the fixed catalog roles + their
     READ-ONLY permissions + members, and ASSIGN an existing role to a member. The whole page is gated on
     users.manage (route). The role→permission catalog is CODE-DEFINED and shown read-only — never edited here
     (that was the design's escalation weapon). Assignment is bounded by the privilege-subset rule in the service.

     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<div>
    <x-operator.shell active="roles" :title="'Roles & access'" :subtitle="'Who can do what'" :viewer="$viewer">

        @if (session('roles_notice'))
            <div class="vy-card vy-fade" style="border-left: 3px solid var(--vy-accent); margin-bottom: 16px;">
                <div style="font-size: 13.5px; color: var(--vy-ink);">{{ session('roles_notice') }}</div>
            </div>
        @endif

        {{-- The refusal, stated honestly: the catalog is code-defined and not editable here. --}}
        <div class="vy-card vy-fade" style="margin-bottom: 16px;">
            <div style="font-size: 12.5px; color: var(--vy-muted); line-height: 1.6;">
                Roles and the permissions they grant are defined in Vyrobel’s code — the fixed catalog. They can’t be
                created, edited or deleted here (so no one can quietly widen a role’s access). What you <strong>can</strong> do is
                assign an existing role to a team member — and only a role whose access you already hold yourself.
            </div>
        </div>

        {{-- ── Assign a role (gated on users.manage — the page already is, but gate the control too) ──── --}}
        @can('users.manage')
            <form method="POST" action="{{ route('app.roles.assign') }}" class="vy-card vy-fade" style="margin-bottom: 20px;">
                @csrf
                <h2 class="vy-card-title" style="margin-bottom: 12px;">Assign a role</h2>
                <div class="vyr-assign">
                    <div class="vy-field" style="margin: 0;">
                        <label class="vy-field-label" for="au">Team member</label>
                        <select id="au" name="user_id" class="vy-input" required>
                            <option value="">Choose a member…</option>
                            @foreach ($members as $member)
                                <option value="{{ $member['id'] }}">{{ $member['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="vy-field" style="margin: 0;">
                        <label class="vy-field-label" for="ar">Role</label>
                        <select id="ar" name="role" class="vy-input" required>
                            <option value="">Choose a role…</option>
                            @foreach ($assignableRoles as $role)
                                <option value="{{ $role }}">{{ $role }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="vy-btn vy-btn--accent" style="padding: 11px 18px;">Assign role</button>
                </div>
                <div style="font-size: 11.5px; color: var(--vy-faint); margin-top: 9px;">
                    Only roles whose access you already hold are listed — you can’t grant more than your own.
                </div>
            </form>
        @endcan

        {{-- ── The roles: read-only permissions + members ─────────────────────────────────────────────── --}}
        @foreach ($roles as $role)
            <div class="vy-card vy-fade" style="margin-bottom: 14px;">
                <div class="vyr-head">
                    <div>
                        <span class="vyr-name">{{ $role['name'] }}</span>
                        <span class="vyr-count">{{ $role['permission_count'] }} {{ $role['permission_count'] === 1 ? 'permission' : 'permissions' }} · {{ $role['member_count'] }} {{ $role['member_count'] === 1 ? 'member' : 'members' }}</span>
                    </div>
                </div>

                {{-- Members --}}
                @if ($role['members'] !== [])
                    <div class="vyr-members">
                        @foreach ($role['members'] as $member)
                            <span class="vyr-member">
                                <span class="vyr-av">{{ $member['initials'] }}</span>
                                {{ $member['name'] }}
                                @can('users.manage')
                                    @if (in_array($role['name'], $assignableRoles, true))
                                        <form method="POST" action="{{ route('app.roles.revoke') }}" style="display: inline;">
                                            @csrf
                                            <input type="hidden" name="user_id" value="{{ $member['id'] }}">
                                            <input type="hidden" name="role" value="{{ $role['name'] }}">
                                            <button type="submit" class="vyr-x" title="Remove this role">&times;</button>
                                        </form>
                                    @endif
                                @endcan
                            </span>
                        @endforeach
                    </div>
                @else
                    <div class="vyr-nomembers">No one holds this role yet.</div>
                @endif

                {{-- Read-only permission grants (from the fixed catalog) --}}
                <div class="vyr-perms">
                    <span class="vyr-perms-label">Grants</span>
                    <div class="vyr-perms-groups">
                        @foreach ($role['permissions'] as $resource => $actions)
                            <span class="vyr-perm">
                                <strong>{{ $resource }}</strong>
                                <span class="vyr-perm-actions">{{ implode(' · ', array_filter($actions)) }}</span>
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach

    </x-operator.shell>

    <style>
        .vyr-assign { display: grid; grid-template-columns: 1fr 1fr auto; gap: 12px; align-items: end; }
        .vyr-head { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 12px; }
        .vyr-name { font-size: 15px; font-weight: 700; color: var(--vy-ink); font-family: var(--vy-display, inherit); }
        .vyr-count { font-size: 11.5px; color: var(--vy-muted); margin-left: 10px; }
        .vyr-members { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
        .vyr-member { display: inline-flex; align-items: center; gap: 7px; font-size: 12.5px; color: var(--vy-ink); background: rgba(255,255,255,0.7); border: 1px solid var(--vy-hairline); border-radius: 20px; padding: 4px 10px 4px 4px; }
        .vyr-av { width: 24px; height: 24px; border-radius: 50%; background: var(--vy-grad); color: #fff; font-size: 9.5px; font-weight: 600; display: grid; place-items: center; }
        .vyr-x { border: none; background: none; color: var(--vy-faint); font-size: 16px; line-height: 1; cursor: pointer; padding: 0 2px; }
        .vyr-x:hover { color: var(--vy-accent); }
        .vyr-nomembers { font-size: 12px; color: var(--vy-faint); margin-bottom: 14px; }
        .vyr-perms { border-top: 1px solid var(--vy-hairline); padding-top: 12px; }
        .vyr-perms-label { font-size: 10px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--vy-faint); }
        .vyr-perms-groups { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; }
        .vyr-perm { font-size: 11.5px; color: var(--vy-muted); background: rgba(120,70,40,0.05); border-radius: 7px; padding: 4px 9px; }
        .vyr-perm strong { color: var(--vy-ink); font-weight: 600; }
        .vyr-perm-actions { margin-left: 5px; }
        @media (max-width: 720px) { .vyr-assign { grid-template-columns: 1fr; } }
    </style>
</div>
