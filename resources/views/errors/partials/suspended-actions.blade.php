{{-- ADMIN 01 · PHASE 2 — what a person refused by a suspended workspace can actually do: move to another workspace
     they belong to (if any), sign out, or contact Vyrobel. Never a link back into the page that refused them. --}}
<div class="actions">
    @foreach ($otherWorkspaces ?? [] as $id => $name)
        <form method="POST" action="{{ route('app.workspace.switch') }}">
            @csrf
            <input type="hidden" name="organization_id" value="{{ $id }}">
            <button type="submit" class="btn">Switch to {{ $name }}</button>
        </form>
    @endforeach
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn {{ ($otherWorkspaces ?? []) !== [] ? 'btn--ghost' : '' }}">Sign out</button>
    </form>
    <a href="{{ route('marketing.page.contact') }}" style="font-size: 13px; color: inherit;">Contact Vyrobel</a>
</div>
