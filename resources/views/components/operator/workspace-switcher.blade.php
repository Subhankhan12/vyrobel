{{-- The WORKSPACE (organization) switcher — auth/isolation pass, item 2.

     `active_organization_id` was READ by SetTenantForWebRequest and NEVER WRITTEN, so a user belonging to
     two organizations was silently pinned to whichever membership came back first, with no way to reach the
     other. This is the missing write, shaped exactly like the property switcher above it (POST → session →
     PRG), so the two working-context switchers behave identically.

     Honesty: only the user's OWN active memberships are offered, and the controller re-validates the target
     against that same set (a crafted org id is refused, never stored — #38). A user in ONE workspace has
     nothing to switch between, so NOTHING renders — no dropdown, no decorative single-option control.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@php
    $__wsUser = auth()->user();
    // ⚠️ Offer ONLY workspaces the user can actually ENTER. A Membership is NOT operator access — the org
    // must also grant one of config('authorization.back_office_roles') for that team, or /app answers 403
    // (verified in the browser: switching to a membership-without-a-role landed on the 403 page). Listing it
    // would invite a 403, which #30 forbids ("gate the CONTROL too").
    //
    // Read from the role pivot directly rather than looping setPermissionsTeamId() + hasAnyRole(), which
    // would mutate spatie's global team state mid-render.
    // spatie's teams column is CONFIGURABLE (here: organization_id) — read it from config rather than
    // assuming the package default, which does not exist in this schema.
    $__wsTeamKey = (string) config('permission.column_names.team_foreign_key', 'team_id');

    $__wsMemberOrgIds = $__wsUser === null
        ? collect()
        : $__wsUser->memberships()->where('status', 'active')->pluck('organization_id');

    $__wsAllowed = $__wsMemberOrgIds->isEmpty() ? collect() : \Illuminate\Support\Facades\DB::table('model_has_roles')
        ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
        ->where('model_has_roles.model_id', $__wsUser->getKey())
        ->whereIn('model_has_roles.'.$__wsTeamKey, $__wsMemberOrgIds)
        ->whereIn('roles.name', config('authorization.back_office_roles', []))
        ->pluck('model_has_roles.'.$__wsTeamKey)
        ->unique();

    $__wsOrgs = $__wsAllowed->isEmpty() ? collect() : \App\Models\Organization::query()
        ->whereIn('id', $__wsAllowed)
        ->orderBy('name')
        ->get(['id', 'name']);
    $__wsActive = (string) (app(\App\Support\Tenancy\TenantContext::class)->id() ?? '');
@endphp

@if ($__wsOrgs->count() > 1)
    <form method="POST" action="{{ route('app.workspace.switch') }}" class="vy-propsw">
        @csrf
        <svg class="vy-propsw-ico" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        <select name="organization_id" class="vy-propsw-select" onchange="this.form.submit()" aria-label="Workspace">
            @foreach ($__wsOrgs as $o)
                <option value="{{ $o->id }}" @selected($__wsActive === (string) $o->id)>{{ $o->name }}</option>
            @endforeach
        </select>
    </form>
@endif
