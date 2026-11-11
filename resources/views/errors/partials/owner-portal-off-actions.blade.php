{{-- DEC-o — what a person refused because an operator switched their owner portal OFF can actually do: sign out. Never a link back
     into the portal that refused them (it would loop), and no contact address of ours — the message names who to ask. --}}
<div class="actions">
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn">{{ __('common.sign_out') }}</button>
    </form>
</div>
