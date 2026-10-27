<?php

declare(strict_types=1);

// Mutation set security-five — the operator Security page in the five-page pass (2026-10-05, the founder: "this page design is
// not good. Fix it."). Rebuilt as one header and two named groups of equal cards on the shared kit; beneath the look, a device
// can be signed out from its row (by a one-way token, never its session id), "Sign out of all other devices" comes back here, an
// error shows once in the form it came from, removing the allowlist rule that admits you is refused, X is called X, and a
// passkey keeps an honest name. Run with `php tests/Mutations/run.php --set=security-five`. ⚠️ The look is what a browser draws:
// these prove the markup, words, rules and the server's behaviour; the 🖥 walk proved the look at 1,280 / 1,024 / 390, the
// per-device sign-out, the lockout refusal and every dialog.

$test = 'tests/Feature/SecurityPassFiveTest.php';
$view = 'resources/views/livewire/operator/operator-security.blade.php';
$css = 'resources/views/components/security/styles.blade.php';   // 2026-10-24: the page's CSS moved to the shared Security kit
$ctl = 'app/Http/Controllers/Operator/OperatorSecurityController.php';
$sessions = 'app/Support/Security/ActiveSessions.php';

return [
    [
        'id' => 'SC-M1',
        'name' => 'the cards go back to a 560 px column beside full-width ones (his report)',
        'file' => $css,
        'old' => '    .vy-sx-cols { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(320px, 100%), 1fr)); gap: 16px; align-items: start; }',
        'new' => '    .vy-sx-cols { display: block; max-width: 560px; }',
        'tests' => [['file' => $test, 'filter' => 'lays the page out in three columns']],
    ],
    [
        'id' => 'SC-M2',
        'name' => 'the page falls back to one stacked column (2026-10-24: three columns, his design)',
        'file' => $view,
        'old' => '        <div class="vy-sx-cols">',
        'new' => '        <div class="vy-sx-stack">',
        'tests' => [['file' => $test, 'filter' => 'lays the page out in three columns']],
    ],
    [
        'id' => 'SC-M3',
        'name' => '"Current password" is labelled twice again',
        'file' => $view,
        'old' => "                        @if (\$hasPassword)\n                            <div class=\"vy-sx-field\">\n                                <label class=\"vy-sx-label\" for=\"vy-sec-cur\">{{ __('livewire/operator/operator-security.current_password') }}</label>",
        'new' => "                        <label class=\"vy-sx-label\" for=\"vy-sec-cur\">{{ __('livewire/operator/operator-security.current_password') }}</label>\n                        @if (\$hasPassword)\n                            <div class=\"vy-sx-field\">\n                                <label class=\"vy-sx-label\" for=\"vy-sec-cur\">{{ __('livewire/operator/operator-security.current_password') }}</label>",
        'tests' => [['file' => $test, 'filter' => 'labels Current password once']],
    ],
    [
        'id' => 'SC-M4',
        'name' => 'a dialog goes back to the undefined scrim with inline positioning',
        'file' => $view,
        'old' => '<div class="vy-sx-scrim" x-cloak x-show="exportOpen" x-transition.opacity',
        'new' => '<div class="vyp-scrim" x-cloak x-show="exportOpen" style="position:fixed;inset:0;display:flex;" x-transition.opacity',
        'tests' => [['file' => $test, 'filter' => 'opens every dialog centred on the Security kit']],
    ],
    [
        'id' => 'SC-M5',
        'name' => 'a flash goes back to hand-mixed colours under AA',
        'file' => $view,
        'old' => "            <div class=\"vy-flash is-ok vy-fade\" role=\"status\">{{ session('security_notice') }}</div>",
        'new' => "            <div class=\"vy-flash\" role=\"status\" style=\"background:rgba(26,127,75,.10);color:#1a7f4b;\">{{ session('security_notice') }}</div>",
        'tests' => [['file' => $test, 'filter' => 'shows flashes in the shared AA classes']],
    ],
    // ── sessions ──────────────────────────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'SC-M6',
        'name' => 'the page prints each session’s id — a key to that sign-in',
        'file' => $view,
        'old' => '<input type="hidden" name="session" value="{{ $sess[\'key\'] }}">',
        'new' => '<input type="hidden" name="session" value="{{ $sess[\'key\'] }}"><input type="hidden" name="raw" value="{{ \\Illuminate\\Support\\Facades\\DB::table(\'sessions\')->where(\'user_id\', auth()->id())->where(\'ip_address\', $sess[\'ip\'])->value(\'id\') }}">',
        'tests' => [['file' => $test, 'filter' => 'signs one other device out from its row']],
    ],
    [
        'id' => 'SC-M7',
        'name' => 'a session is looked for among everyone’s, not the person’s own',
        'file' => $sessions,
        'old' => "        foreach (DB::table('sessions')->where('user_id', (string) \$user->getKey())->get(['id', 'user_agent']) as \$row) {",
        'new' => "        foreach (DB::table('sessions')->get(['id', 'user_agent']) as \$row) {",
        'tests' => [['file' => $test, 'filter' => 'signs one other device out from its row']],
    ],
    [
        'id' => 'SC-M8',
        'name' => 'signing a device out leaves no audit row',
        'file' => $ctl,
        'old' => "        \$audit->record('user.session_signed_out', \$user, null, ['device' => \$device]);\n",
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'signs one other device out from its row']],
    ],
    [
        'id' => 'SC-M9',
        'name' => '"Sign out of all other devices" lands on the Profile page again',
        'file' => 'app/Http/Controllers/Operator/OperatorProfileController.php',
        'old' => "        return \$request->input('return') === 'security'",
        'new' => "        return \$request->input('return') === 'never'",
        'tests' => [['file' => $test, 'filter' => 'brings “Sign out of all other devices” back to this page']],
    ],
    [
        'id' => 'SC-M10',
        'name' => 'its password error no longer reopens the dialog',
        'file' => 'app/Livewire/Operator/OperatorSecurity.php',
        'old' => "        \$errored = session()->has('errors') ? old('_form') : null;",
        'new' => '        $errored = null;',
        'tests' => [['file' => $test, 'filter' => 'brings “Sign out of all other devices” back to this page']],
    ],
    // ── the IP allowlist ──────────────────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'SC-M11',
        'name' => 'removing the one rule that admits you is allowed — the next page locks you out',
        'file' => $ctl,
        'old' => "        if (\$remaining !== [] && \$current !== '' && IpUtils::checkIp(\$current, [\$ip]) && ! IpUtils::checkIp(\$current, \$remaining)) {",
        'new' => '        if (false) {',
        'tests' => [['file' => $test, 'filter' => 'refuses to remove the one allowlist rule that lets you in']],
    ],
    [
        'id' => 'SC-M12',
        'name' => 'removing the last rule is refused too — the allowlist can never be switched off',
        'file' => $ctl,
        'old' => "        if (\$remaining !== [] && \$current !== '' && IpUtils::checkIp(\$current, [\$ip]) && ! IpUtils::checkIp(\$current, \$remaining)) {",
        'new' => "        if (\$current !== '' && IpUtils::checkIp(\$current, [\$ip]) && ! IpUtils::checkIp(\$current, \$remaining)) {",
        'tests' => [['file' => $test, 'filter' => 'refuses to remove the one allowlist rule that lets you in']],
    ],
    [
        'id' => 'SC-M13',
        'name' => 'removing a rule goes back to the browser’s confirm()',
        'file' => $view,
        'old' => '<button type="button" class="vy-sx-btn vy-sx-btn--soft vy-sx-btn--sm" @click="ipRemove = @js([\'id\' => $r[\'id\'], \'ip\' => $r[\'ip\']])">{{ __(\'common.remove\') }}</button>',
        'new' => '<form method="POST" action="{{ route(\'app.security.ip-rules.remove\', $r[\'id\']) }}" onsubmit="return confirm(\'Remove?\')">@csrf @method(\'DELETE\')<button type="submit" class="vy-btn vy-btn--ghost vy-sec-small">{{ __(\'common.remove\') }}</button></form>',
        'tests' => [['file' => $test, 'filter' => 'asks before removing an allowlist rule']],
    ],
    [
        'id' => 'SC-M14',
        'name' => 'the page says "admin panel" again',
        'file' => 'lang/en/livewire/operator/operator-security.php',
        'old' => '\'Anyone with a valid sign-in can reach the back office from any network.\'',
        'new' => '\'Anyone with a valid sign-in can reach the admin panel from any network.\'',
        'tests' => [['file' => $test, 'filter' => 'says "back office", never "admin panel"']],
    ],
    // ── honest words ──────────────────────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'SC-M15',
        'name' => 'the trusted-devices list promises a code that two-factor-off never asks for',
        'file' => $view,
        'old' => "{{ \$twoFactorOn ? __('livewire/operator/operator-security.no_trusted_2fa_on') : __('livewire/operator/operator-security.no_trusted_2fa_off') }}",
        'new' => "{{ __('livewire/operator/operator-security.no_trusted_2fa_on') }}",
        'tests' => [['file' => $test, 'filter' => 'says what the trusted-devices list means']],
    ],
    [
        'id' => 'SC-M16',
        'name' => 'disconnecting X says "Google disconnected." again',
        'file' => 'app/Http/Controllers/Auth/SocialAuthController.php',
        'old' => "            ->with('connections_status', __('php/http/controllers/auth/social_auth_controller.disconnected', ['provider' => SocialProviders::label((string) \$row->provider)]));",
        'new' => "            ->with('connections_status', __('php/http/controllers/auth/social_auth_controller.disconnected', ['provider' => 'Google']));",
        'tests' => [['file' => $test, 'filter' => 'names the provider it connected or disconnected']],
    ],
    [
        'id' => 'SC-M17',
        'name' => 'a passkey added from Safari is called "This device" for ever',
        'file' => 'resources/views/components/auth/passkeys.blade.php',
        'old' => "'X-Passkey-Label': this.label() },",
        'new' => "'X-Passkey-Label': (navigator.userAgentData?.platform || 'This device') },",
        'tests' => [['file' => $test, 'filter' => 'keeps the passkey card honest']],
    ],
    [
        'id' => 'SC-M18',
        'name' => 'the two-factor panel shows every password error on the page, whichever form it came from',
        'file' => 'resources/views/livewire/partials/two-factor-off-dialog.blade.php',
        'old' => "                    @if (old('_form') === '2fa-off')\n                        @error('current_password')",
        'new' => "                @if (true)\n                    @error('current_password')",
        'tests' => [['file' => $test, 'filter' => 'shows each error inside the form that caused it']],
    ],
    [
        'id' => 'SC-M19',
        'name' => 'the export goes back to native dropdowns',
        'file' => $view,
        'old' => "<x-operator.choices name=\"format\" :label=\"__('livewire/operator/operator-security.export_format')\" :options=\"[['value' => 'json', 'label' => 'JSON'], ['value' => 'csv', 'label' => 'CSV']]\" model=\"format\" variant=\"segmented\" />",
        'new' => '<select name="format" x-model="format" class="vy-input"><option value="json">JSON</option><option value="csv">CSV</option></select>',
        'tests' => [['file' => $test, 'filter' => 'offers the export as choice tiles']],
    ],
    [
        'id' => 'SC-M20',
        'name' => 'a wrong two-factor code shows twice — at the top and under the field (the review)',
        'file' => 'resources/views/livewire/partials/two-factor-panel.blade.php',
        'old' => "            <input type=\"hidden\" name=\"_form\" value=\"2fa-confirm\">\n",
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'shows a wrong two-factor code once']],
    ],
    [
        'id' => 'SC-M21',
        'name' => 'Change password loses its mark — its error falls to the top of the page',
        'file' => $view,
        'old' => "                    <input type=\"hidden\" name=\"_form\" value=\"password\">\n",
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'shows each error inside the form that caused it']],
    ],
    [
        'id' => 'SC-M22',
        'name' => 'Sign out of all other devices loses its mark — its dialog no longer reopens with the error',
        'file' => $view,
        'old' => "                    <input type=\"hidden\" name=\"_form\" value=\"signout\">\n",
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'brings “Sign out of all other devices” back to this page']],
    ],
];
