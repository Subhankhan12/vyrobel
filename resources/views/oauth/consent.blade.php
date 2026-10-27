{{--
    OAuth CONSENT screen (Wave D · Passport · Phase 2). The owner is redirected here by a third-party app and
    sees EXACTLY which scopes are requested (the human descriptions from OAuthScopes) before authorizing. A
    standalone branded page (warm-glass, the owner design language) — never implies more access than requested.
    Data (from Passport): $client (the app), $scopes (Scope[] with ->id + ->description), $authToken.
--}}
<!doctype html>
<html {!! \App\Support\I18n\Locales::htmlAttributes() !!}>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('oauth/consent.page_title', ['app' => $client->name]) }}</title>
    <x-favicon />
    <style>
        :root {
            --vy-ink: #2b2320; --vy-muted: #5F5853; --vy-faint: #6C645F; --vy-accent: #c2410c;
            --vy-hairline: rgba(120, 90, 60, .16); --vy-grad: linear-gradient(135deg, #E8703A, #C2410C);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
            color: var(--vy-ink); display: grid; place-items: center; padding: 24px;
            background: radial-gradient(120% 90% at 20% 0%, #fdf1e7 0%, #fbe8dc 40%, #f6ddcd 100%);
        }
        .card {
            width: 100%; max-width: 460px; background: rgba(255, 252, 249, .82);
            border: 1px solid rgba(255, 255, 255, .7); border-radius: 22px; padding: 32px 30px;
            box-shadow: 0 24px 60px -20px rgba(120, 60, 20, .3); backdrop-filter: blur(14px);
        }
        .brand { font-size: 12px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: var(--vy-accent); }
        h1 { font-size: 21px; line-height: 1.3; margin: 10px 0 6px; letter-spacing: -.01em; }
        .sub { font-size: 13.5px; color: var(--vy-muted); line-height: 1.5; margin: 0 0 22px; }
        .app { font-weight: 700; color: var(--vy-ink); }
        .scopes { list-style: none; margin: 0 0 8px; padding: 0; border-top: 1px solid var(--vy-hairline); }
        .scope { display: flex; gap: 12px; align-items: flex-start; padding: 14px 2px; border-bottom: 1px solid var(--vy-hairline); }
        .scope-ic { flex: none; width: 26px; height: 26px; border-radius: 8px; background: rgba(194, 65, 12, .1); color: var(--vy-accent); display: grid; place-items: center; font-size: 14px; margin-top: 1px; }
        .scope-txt { font-size: 13px; line-height: 1.45; color: var(--vy-ink); }
        .scope-id { font-size: 11px; color: var(--vy-faint); font-family: ui-monospace, monospace; }
        .note { font-size: 12px; color: var(--vy-muted); background: rgba(255, 245, 235, .7); border: 1px solid var(--vy-hairline); border-radius: 12px; padding: 11px 13px; margin: 18px 0 22px; line-height: 1.5; }
        .actions { display: flex; gap: 12px; }
        form { margin: 0; flex: 1; }
        .btn { width: 100%; padding: 13px 18px; border-radius: 12px; font-size: 14px; font-weight: 600; cursor: pointer; border: 1px solid var(--vy-hairline); }
        .btn--deny { background: rgba(255, 255, 255, .7); color: var(--vy-muted); }
        .btn--allow { background: var(--vy-grad); color: #fff; border: none; box-shadow: 0 8px 20px -6px rgba(194, 65, 12, .5); }
        .who { font-size: 11.5px; color: var(--vy-faint); text-align: center; margin-top: 18px; }
    </style>
    <x-vy-scrollbar />
    <x-vy-selects />
</head>
<body>
    <div class="card">
        <div class="brand">Vyrobel</div>
        <h1>{!! __('oauth/consent.heading', ['app' => e($client->name)]) !!}</h1>
        <p class="sub">{!! __('oauth/consent.intro') !!}</p>

        <ul class="scopes">
            @foreach ($scopes as $scope)
                <li class="scope">
                    <span class="scope-ic" aria-hidden="true">✓</span>
                    <span class="scope-txt">{{ \App\Support\OAuth\OAuthScopes::description((string) $scope->id) ?? $scope->description }}<br><span class="scope-id">{{ $scope->id }}</span></span>
                </li>
            @endforeach
        </ul>

        <p class="note">{{ __('oauth/consent.note') }}</p>

        <div class="actions">
            {{-- Deny → DELETE /oauth/authorize (no token issued). --}}
            <form method="post" action="{{ route('passport.authorizations.deny') }}">
                @csrf
                @method('DELETE')
                <input type="hidden" name="auth_token" value="{{ $authToken }}">
                <button type="submit" class="btn btn--deny">{{ __('oauth/consent.deny') }}</button>
            </form>
            {{-- Authorize → POST /oauth/authorize (issues an auth code with ONLY these scopes). --}}
            <form method="post" action="{{ route('passport.authorizations.approve') }}">
                @csrf
                <input type="hidden" name="auth_token" value="{{ $authToken }}">
                <button type="submit" class="btn btn--allow">{{ __('oauth/consent.authorize') }}</button>
            </form>
        </div>

        <div class="who">{{ __('oauth/consent.signed_in_as', ['name' => $request->user()->name ?? $request->user()->email]) }}</div>
    </div>
</body>
</html>
