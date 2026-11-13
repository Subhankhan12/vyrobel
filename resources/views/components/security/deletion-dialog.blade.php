{{-- DEC-6 — the dialog behind <x-security.deletion-card>. Re-asks for the password when the person has one (a person who signs in
     with Google or X has none — the route accepts that, and nothing is erased either way). A refused form (`_form` = deletion)
     reopens this dialog with its error inside it. Needs `deleteOpen` in the page's Alpine state. --}}
@props(['action', 'hasPassword' => true, 'errored' => null])
    <div class="vy-sx-scrim" x-cloak x-show="deleteOpen" x-transition.opacity @keydown.escape.window="deleteOpen = false" @click.self="deleteOpen = false">
        <div class="vy-sx-dialog" x-show="deleteOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-sx-del-dlg-t" tabindex="-1" x-trap.noscroll="deleteOpen">
            <form method="POST" action="{{ $action }}">
                @csrf
                <input type="hidden" name="_form" value="deletion">
                <input type="text" value="{{ auth()->user()?->email }}" autocomplete="username" readonly aria-hidden="true" tabindex="-1" hidden>
                <div class="vy-sx-dialog-head">
                    <span class="vy-sx-ico is-danger"><x-security.icon name="x" :size="18" /></span>
                    <div class="vy-sx-dialog-text">
                        <h3 class="vy-sx-dialog-title" id="vy-sx-del-dlg-t">{{ __('components/security/deletion-dialog.title') }}</h3>
                        <p class="vy-sx-dialog-sub">{{ __('components/security/deletion-dialog.sub') }}</p>
                    </div>
                    <button type="button" class="vy-sx-x" @click="deleteOpen = false" aria-label="{{ __('common.close') }}"><x-security.icon name="x" /></button>
                </div>
                <div class="vy-sx-dialog-body">
                    @if ($errored === 'deletion' && $errors->any())
                        <div class="vy-sx-errors" role="alert">{{ $errors->first() }}</div>
                    @endif
                    @if ($hasPassword)
                        <div class="vy-sx-field">
                            <label class="vy-sx-label" for="vy-sx-del-pw">{{ __('components/security/deletion-dialog.your_password') }}</label>
                            <x-password-field name="current_password" id="vy-sx-del-pw" autocomplete="current-password" :required="true" :label="__('components/security/deletion-dialog.field_password')" autofocus />
                        </div>
                    @endif
                </div>
                <div class="vy-sx-dialog-foot">
                    <button type="button" class="vy-sx-btn vy-sx-btn--soft" @click="deleteOpen = false">{{ __('components/security/deletion-dialog.keep') }}</button>
                    <button type="submit" class="vy-sx-btn vy-sx-btn--danger">{{ __('components/security/deletion-dialog.send') }}</button>
                </div>
            </form>
        </div>
    </div>
