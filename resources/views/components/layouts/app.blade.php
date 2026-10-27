{{-- UNSTYLED contract-stub layout. No design system, no CSS — raw structure only. The real
     design pass replaces these views with the externally-provided HTML. --}}
<!DOCTYPE html>
<html {!! \App\Support\I18n\Locales::htmlAttributes() !!}>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? __('components/layouts/app.default_title') }}</title>
    @livewireStyles
    <x-vy-scrollbar />
    <x-vy-selects />
</head>
<body>
    <main>
        {{ $slot }}
    </main>
    @livewireScripts
</body>
</html>
