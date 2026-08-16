{{-- Operator ROLES & PERMISSIONS (task #102) — matched to `Operator Roles.html`. The permission MATRIX is
     EDITABLE (roles.manage): a toggle genuinely grants/revokes a real capability (spatie DB) via
     RolePermissionService, bounded (editable-role/subset/last-admin) + audited. New-role/delete-role are honestly
     deferred (the back-office role catalogue is config-anchored, #26). Assignment stays users.manage.

     RAW-BLADE RULE: every `@` follows a space, newline or tag. Alpine uses x-on:/x-model, never `@click`. --}}
<div>
    <x-operator.shell active="roles" :title="'Roles & permissions'" :subtitle="'What each role can see and do'" :viewer="$viewer">

        @if (session('roles_notice'))
            <div class="vy-card vy-fade" style="border-left: 3px solid var(--vy-accent); margin-bottom: 16px;">
                <div style="font-size: 13.5px; color: var(--vy-ink);">{{ session('roles_notice') }}</div>
            </div>
        @endif

        @if ($readOnly)
            <div class="vy-card vy-fade" style="border-left: 3px solid var(--vy-amber); margin-bottom: 16px;">
                <div style="font-size: 13px; color: var(--vy-ink);">Your workspace is read-only right now — role changes are paused.</div>
            </div>
        @endif

        @if (count($matrix['roles']) === 0)
            <div class="vy-card vy-fade" style="text-align: center; padding: 40px 24px;">
                <strong style="display: block; font-size: 15px; color: var(--vy-ink); margin-bottom: 6px;">No editable roles</strong>
                <span style="font-size: 13px; color: var(--vy-muted);">Your organization's back-office roles will appear here.</span>
            </div>
        @else
            <div x-data="rolesMatrix({
                roles: @js($matrix['roles']),
                sections: @js($matrix['sections']),
                domainOrder: @js(\App\Services\RolePermissionService::DOMAIN_ORDER),
                lockable: @js($matrix['lockable']),
                canEdit: {{ $canEdit ? 'true' : 'false' }},
                readOnly: {{ $readOnly ? 'true' : 'false' }},
            })">
                {{-- ── D23-3 — the roles as a CARD GRID, above the two columns ──────────────────────────
                     ⚠️ LAYOUT ONLY. This is the SAME `roles` array, the SAME `select(i)`, rendered wider: no
                     role is filtered, sorted away or sliced, and nothing here is an input. The permission
                     form below (and its `permissions[]` checkboxes) is a SEPARATE <form> and is untouched —
                     which is what keeps a re-layout from silently revoking a grant on the next save.
                     ⚠️ THE ICON IS KEYED TO THE ROLE'S REAL DERIVED `level` (Admin / Manager / Supervisor /
                     Team — computed from its ACTUAL grants by RolePermissionService::level()), never to an
                     invented per-role decoration. A role cannot show an authority glyph its permissions do
                     not support, and the glyph moves the moment its grants do. --}}
                <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 12px;">
                    <h2 class="vy-card-title" style="margin: 0;">Roles</h2>
                    <button type="button" class="vy-btn" style="padding: 6px 12px; font-size: 12px;" x-on:click="newRoleOpen = true">New role</button>
                </div>
                <div class="vyr-rolegrid">
                    <template x-for="(r, i) in roles" x-bind:key="r.name">
                        <button type="button" class="vyr-rolecard" x-bind:class="{ 'is-on': i === selected }" x-on:click="select(i)">
                            <div class="vyr-rolecard-top">
                                <span class="vyr-rolecard-icon" x-bind:class="'is-' + levelKey(r.level)">
                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path x-bind:d="levelIcon(r.level)"></path></svg>
                                </span>
                                <span class="vyr-rolecard-tick" x-show="i === selected" x-cloak>
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 12.5 9.5 17.5 19.5 7"></path></svg>
                                </span>
                            </div>
                            <div class="vyr-rolecard-name" x-text="r.name"></div>
                            <div class="vyr-rolecard-desc" x-text="r.desc"></div>
                            <div class="vyr-rolecard-meta">
                                <span class="vyr-rolecard-level" x-bind:class="'is-' + levelKey(r.level)" x-text="r.level"></span>
                                <span x-text="r.member_count + (r.member_count === 1 ? ' member' : ' members')"></span>
                            </div>
                        </button>
                    </template>
                </div>

                <div class="vyr-cols">
                    {{-- ── LEFT: the editable permission matrix ────────────────────────────────────────── --}}
                    <form class="vy-card vy-fade" method="POST" action="{{ route('app.roles.permissions') }}" style="align-self: start;">
                        @csrf
                        <input type="hidden" name="role" x-bind:value="roles[selected].name">
                        <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 4px;">
                            <h2 class="vy-card-title" style="margin: 0;"><span x-text="roles[selected].name"></span> · permissions</h2>
                            <span style="font-size: 12px; color: var(--vy-muted);"><span x-text="grantCount()"></span> of <span x-text="roles[selected].total_count"></span> on</span>
                        </div>
                        <p style="font-size: 12px; color: var(--vy-muted); margin: 0 0 14px;">Toggle what this role can access. Changes apply to everyone assigned to it.</p>

                        {{-- D23-2 — a coarser DOMAIN tier above the resource headings. 49 permissions sat
                             under 30 resource headings, 20 of them holding one row: grouped in name, flat to
                             the eye. This groups those SAME sections; it adds no permission and removes none,
                             and every row keeps its exact 1:1 key-derived label (the design's friendlier
                             phrasing was refused because "Manage team & roles" conflates `users.manage` with
                             `roles.manage` — two permissions this product separates, on the screen that
                             grants them). --}}
                        <template x-for="domain in domains" x-bind:key="domain.domain">
                        <div>
                            <div class="vyr-domain" x-text="domain.domain"></div>
                        <template x-for="section in domain.sections" x-bind:key="section.section">
                            <div style="border-top: 1px solid var(--vy-hairline); padding: 10px 0;">
                                <div class="vyr-section" x-text="section.section"></div>
                                <div class="vyr-perms-grid">
                                    <template x-for="perm in section.permissions" x-bind:key="perm.key">
                                        <label class="vyr-toggle" x-bind:class="{ 'is-locked': !canToggle(perm.key) }">
                                            <input type="checkbox" name="permissions[]" x-bind:value="perm.key"
                                                   x-model="granted[perm.key]" x-bind:disabled="!canEdit || readOnly || !canToggle(perm.key)">
                                            <span x-text="perm.label"></span>
                                            {{-- Preserve a locked-but-granted permission on save (the actor can't toggle it, but must not drop it). --}}
                                            <template x-if="!canToggle(perm.key) && granted[perm.key]"><input type="hidden" name="permissions[]" x-bind:value="perm.key"></template>
                                        </label>
                                    </template>
                                </div>
                            </div>
                        </template>
                        </div>
                        </template>

                        @if ($canEdit && ! $readOnly)
                            <div style="margin-top: 16px; display: flex; justify-content: flex-end;">
                                <button type="submit" class="vy-btn vy-btn--accent" style="padding: 11px 20px;">Save changes</button>
                            </div>
                        @else
                            <div style="margin-top: 14px; font-size: 11.5px; color: var(--vy-faint);">
                                Editing a role's permissions needs the organization-admin permission (roles.manage).
                            </div>
                        @endif
                    </form>

                    {{-- ── RIGHT: the selected role card — DARK (D23-3). Same real figures, new treatment. --}}
                    <div class="vyr-selected vy-fade" style="align-self: start;">
                        <span class="vy-eyebrow" style="font-size: 10px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase;">Selected role</span>
                        <div class="vyr-sel-name" style="font-size: 20px; font-weight: 700; letter-spacing: -.01em; margin: 6px 0 2px;" x-text="roles[selected].name"></div>
                        <div class="vyr-sel-sub" style="font-size: 12.5px;"><span x-text="roles[selected].level"></span> · <span x-text="roles[selected].member_count + (roles[selected].member_count === 1 ? ' member' : ' members')"></span></div>
                        <div class="vyr-sel-sub" style="font-size: 12px; margin-top: 6px;"><span x-text="grantCount()"></span> permissions granted</div>

                        <div class="vyr-sel-rule" style="height: 1px; margin: 14px 0 12px;"></div>
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <span style="font-size: 12.5px; font-weight: 600; color: var(--vy-ink);">Members</span>
                                @if ($canAssign && ! $readOnly)
                                    <a href="#assign" class="vy-btn" style="padding: 5px 11px; font-size: 11.5px;">Add</a>
                                @endif
                            </div>
                            <template x-for="m in roles[selected].members" x-bind:key="m.id">
                                <div style="display: flex; align-items: center; gap: 10px; padding: 7px 0;">
                                    <span style="width: 30px; height: 30px; border-radius: 50%; background: var(--vy-grad); color: #fff; font-size: 11px; font-weight: 600; display: grid; place-items: center;" x-text="m.initials"></span>
                                    <div style="min-width: 0;">
                                        <div style="font-size: 13px; color: var(--vy-ink);" x-text="m.name"></div>
                                        <div style="font-size: 11.5px; color: var(--vy-muted); overflow: hidden; text-overflow: ellipsis;" x-text="m.email"></div>
                                    </div>
                                </div>
                            </template>
                            <div x-show="roles[selected].members.length === 0" style="font-size: 12px; color: var(--vy-faint);">No one holds this role yet.</div>
                        </div>

                        {{-- Delete-this-role — honestly deferred (the catalogue is config-anchored). --}}
                        <div style="margin-top: 14px; border-top: 1px solid var(--vy-hairline); padding-top: 12px;">
                            <button type="button" class="vy-btn" style="padding: 7px 14px; font-size: 12px; color: rgb(150,50,42); border-color: rgba(180,68,58,.3);" x-on:click="deleteOpen = true">Delete this role</button>
                        </div>
                    </div>
                </div>

                {{-- New-role modal — honest (the back-office catalogue is fixed) --}}
                <div x-show="newRoleOpen" x-cloak style="position: fixed; inset: 0; background: rgba(40,25,15,.45); z-index: 60; display: flex; align-items: center; justify-content: center; padding: 20px;" x-on:click.self="newRoleOpen = false">
                    <div class="vy-card" style="max-width: 440px; width: 100%;">
                        <h3 style="font-size: 16px; font-weight: 700; color: var(--vy-ink); margin: 0 0 8px;">The role catalogue is fixed</h3>
                        <p style="font-size: 13px; color: var(--vy-muted); line-height: 1.6; margin: 0 0 14px;">Back-office roles are anchored to how the app grants access, so new roles aren't created here. Instead, <strong>edit an existing role's permissions</strong> above to shape exactly what its members can do — the toggles take effect immediately.</p>
                        <div style="display: flex; justify-content: flex-end;"><button type="button" class="vy-btn vy-btn--accent" style="padding: 9px 16px;" x-on:click="newRoleOpen = false">Got it</button></div>
                    </div>
                </div>

                {{-- Delete-role modal — honest --}}
                <div x-show="deleteOpen" x-cloak style="position: fixed; inset: 0; background: rgba(40,25,15,.45); z-index: 60; display: flex; align-items: center; justify-content: center; padding: 20px;" x-on:click.self="deleteOpen = false">
                    <div class="vy-card" style="max-width: 440px; width: 100%;">
                        <h3 style="font-size: 16px; font-weight: 700; color: var(--vy-ink); margin: 0 0 8px;">Roles can't be deleted here</h3>
                        <p style="font-size: 13px; color: var(--vy-muted); line-height: 1.6; margin: 0 0 14px;">The back-office role catalogue is fixed. To take access away from a role, un-toggle its permissions above; to move a person off a role, revoke it from them in the assignment section — they keep their other roles.</p>
                        <div style="display: flex; justify-content: flex-end;"><button type="button" class="vy-btn vy-btn--accent" style="padding: 9px 16px;" x-on:click="deleteOpen = false">Got it</button></div>
                    </div>
                </div>
            </div>

            {{-- ── Assign a role to a member (users.manage) — kept below the matrix ────────────────────── --}}
            @if ($canAssign)
                <form id="assign" method="POST" action="{{ route('app.roles.assign') }}" class="vy-card vy-fade" style="margin-top: 20px;">
                    @csrf
                    <h2 class="vy-card-title" style="margin-bottom: 12px;">Assign a role</h2>
                    <div class="vyr-assign">
                        <div class="vy-field" style="margin: 0;">
                            <label class="vy-field-label" for="au">Team member</label>
                            <select id="au" name="user_id" class="vy-input" required {{ $readOnly ? 'disabled' : '' }}>
                                <option value="">Choose a member…</option>
                                @foreach ($members as $member)
                                    <option value="{{ $member['id'] }}">{{ $member['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="vy-field" style="margin: 0;">
                            <label class="vy-field-label" for="ar">Role</label>
                            <select id="ar" name="role" class="vy-input" required {{ $readOnly ? 'disabled' : '' }}>
                                <option value="">Choose a role…</option>
                                @foreach ($assignableRoles as $role)
                                    <option value="{{ $role }}">{{ $role }}</option>
                                @endforeach
                            </select>
                        </div>
                        @unless ($readOnly)
                            <button type="submit" class="vy-btn vy-btn--accent" style="padding: 11px 18px;">Assign role</button>
                        @endunless
                    </div>
                    <div style="font-size: 11.5px; color: var(--vy-faint); margin-top: 9px;">Only roles whose access you already hold are listed — you can't grant more than your own.</div>
                </form>
            @endif
        @endif

    </x-operator.shell>

    <script>
        function rolesMatrix(config) {
            return {
                roles: config.roles,

                /* D23-3 — the card's glyph + tone, derived from the role's REAL `level` (which
                   RolePermissionService computes from its ACTUAL grants). Presentation only: this reads a
                   value the payload already carried and grants nothing. An unrecognised level falls back to
                   the neutral 'team' tone rather than guessing an authority tier. */
                levelKey: function (level) {
                    var k = String(level || '').toLowerCase();
                    return ['admin', 'manager', 'supervisor'].indexOf(k) !== -1 ? k : 'team';
                },
                levelIcon: function (level) {
                    switch (this.levelKey(level)) {
                        case 'admin': return 'M12 3l7 4v5c0 4.2-2.9 7.6-7 9-4.1-1.4-7-4.8-7-9V7l7-4z';
                        case 'manager': return 'M16 20v-2a4 4 0 0 0-8 0v2M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8';
                        case 'supervisor': return 'M2 12s3.6-6.5 10-6.5S22 12 22 12s-3.6 6.5-10 6.5S2 12 2 12zm10 2.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z';
                        default: return 'M17 20v-2a3 3 0 0 0-3-3H7a3 3 0 0 0-3 3v2M10.5 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7M20 20v-2a3 3 0 0 0-2.3-2.9';
                    }
                },
                sections: config.sections,
                // D23-2 — the domain tier is a VIEW TRANSFORM over `sections`: same objects, regrouped.
                // Building it here (rather than shipping a second list) means there is one source of truth
                // and no way for the two to disagree about which permissions exist.
                domains: config.domainOrder
                    .map(name => ({ domain: name, sections: config.sections.filter(s => s.domain === name) }))
                    .filter(d => d.sections.length > 0),
                domainOrder: config.domainOrder,
                lockable: config.lockable,
                canEdit: config.canEdit,
                readOnly: config.readOnly,
                selected: 0,
                granted: {},
                newRoleOpen: false,
                deleteOpen: false,
                init() { this.loadGranted(); },
                select(i) { this.selected = i; this.loadGranted(); },
                loadGranted() {
                    const set = {};
                    (this.roles[this.selected].granted || []).forEach(function (p) { set[p] = true; });
                    this.granted = set;
                },
                canToggle(key) { return this.lockable.includes(key); },
                isGranted(key) { return !!this.granted[key]; },
                grantCount() { return Object.keys(this.granted).filter((k) => this.granted[k]).length; },
            };
        }
    </script>

    <style>
        /* D23-3 — the roles moved out of a 240px column into a full-width card grid, so the page is now
           two columns (matrix + rail) rather than three. Layout only. */
        .vyr-cols { display: grid; grid-template-columns: minmax(0, 1fr) 300px; gap: 18px; align-items: start; }
        .vyr-rolegrid { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 14px; margin-bottom: 22px; }
        .vyr-rolecard { text-align: left; background: rgba(255,255,255,0.62); border: 1px solid var(--vy-hairline); border-radius: 18px; padding: 16px 18px; cursor: pointer; transition: transform .14s, box-shadow .14s, border-color .14s; }
        .vyr-rolecard:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(60,30,10,.10); }
        .vyr-rolecard.is-on { border-color: var(--vy-accent); box-shadow: 0 8px 22px rgba(194,65,12,.16); }
        .vyr-rolecard-top { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 10px; }
        .vyr-rolecard-icon { width: 34px; height: 34px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        /* The tile's tone follows the role's REAL derived level — not a per-role decoration. */
        .vyr-rolecard-icon.is-admin { background: rgba(194,65,12,.12); color: #C2410C; }
        .vyr-rolecard-icon.is-manager { background: rgba(180,83,9,.11); color: #B45309; }
        .vyr-rolecard-icon.is-supervisor { background: rgba(21,94,117,.10); color: #155E75; }
        .vyr-rolecard-icon.is-team { background: rgba(87,83,78,.10); color: #57534E; }
        .vyr-rolecard-tick { width: 20px; height: 20px; border-radius: 50%; background: linear-gradient(140deg, #E8703A, #C2410C); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .vyr-rolecard-name { font-size: 14px; font-weight: 600; color: var(--vy-ink); }
        .vyr-rolecard-desc { font-size: 11.5px; color: var(--vy-muted); margin-top: 2px; line-height: 1.35; min-height: 30px; }
        .vyr-rolecard-meta { display: flex; align-items: center; gap: 7px; margin-top: 10px; font-size: 11px; color: var(--vy-faint); }
        .vyr-rolecard-level { display: inline-flex; align-items: center; padding: 3px 9px; border-radius: 30px; font-size: 10px; font-weight: 600; }
        .vyr-rolecard-level.is-admin { background: rgba(194,65,12,.12); color: #C2410C; }
        .vyr-rolecard-level.is-manager { background: rgba(180,83,9,.11); color: #B45309; }
        .vyr-rolecard-level.is-supervisor { background: rgba(21,94,117,.10); color: #155E75; }
        .vyr-rolecard-level.is-team { background: rgba(87,83,78,.10); color: #57534E; }
        /* The rail's selected-role card goes DARK (the design's hero treatment). */
        .vyr-selected { position: relative; background: linear-gradient(160deg, #3A2E27 0%, #251C17 100%); border-radius: 20px; box-shadow: 0 10px 30px rgba(60,30,10,.28); padding: 20px 22px; }
        .vyr-selected .vy-eyebrow, .vyr-selected .vyr-sel-sub { color: #B9ABA0 !important; }
        .vyr-selected .vyr-sel-name { color: #FFFFFF; }
        .vyr-selected .vyr-sel-rule { background: rgba(255,255,255,.10); }
        .vyr-section { font-size: 10px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-faint); margin-bottom: 8px; }
        /* D23-2 — the domain tier: the same uppercase idiom as .vyr-section, one step louder so it reads as
           the outer grouping. --vy-muted is the AA-corrected token. */
        .vyr-domain { font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-muted); margin: 18px 0 2px; padding-bottom: 6px; border-bottom: 2px solid var(--vy-accent-border, rgba(194,65,12,.18)); }
        .vyr-perms-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 6px 12px; }
        /* The design renders each permission as a SWITCH rather than a bare checkbox. The input stays a real
           `input[type=checkbox]` — only its appearance changes — so the form payload, the x-model binding and
           every disabled/locked rule are untouched.
           ⚠️ RBAC-SAFE BY CONSTRUCTION: the permission's LABEL always sits beside the switch, so granted vs
           denied is never carried by colour or position alone (WCAG 1.4.1), and a LOCKED row keeps a visibly
           distinct treatment (dimmed + not-allowed) so an actor can tell "off" from "you may not change this"
           — a distinction that matters more on a permissions matrix than anywhere else in the product. */
        .vyr-toggle { display: flex; align-items: center; gap: 9px; font-size: 12.5px; color: var(--vy-ink); cursor: pointer; }
        .vyr-toggle input[type="checkbox"] { appearance: none; -webkit-appearance: none; flex: none; position: relative; width: 32px; height: 18px; border-radius: 999px; background: rgba(120, 70, 40, 0.16); border: 1px solid var(--vy-hairline); cursor: pointer; transition: background .16s, border-color .16s; margin: 0; }
        .vyr-toggle input[type="checkbox"]::after { content: ''; position: absolute; top: 1px; left: 1px; width: 14px; height: 14px; border-radius: 50%; background: #fff; box-shadow: 0 1px 3px rgba(60, 40, 24, 0.28); transition: transform .16s; }
        .vyr-toggle input[type="checkbox"]:checked { background: var(--vy-grad); border-color: transparent; }
        .vyr-toggle input[type="checkbox"]:checked::after { transform: translateX(14px); }
        .vyr-toggle input[type="checkbox"]:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
        .vyr-toggle input[type="checkbox"]:disabled { cursor: not-allowed; opacity: .55; }
        .vyr-toggle.is-locked { color: var(--vy-faint); cursor: not-allowed; }
        .vyr-assign { display: grid; grid-template-columns: 1fr 1fr auto; gap: 12px; align-items: end; }
        @media (max-width: 980px) { .vyr-cols { grid-template-columns: 1fr; } .vyr-assign { grid-template-columns: 1fr; } }
    </style>
</div>
