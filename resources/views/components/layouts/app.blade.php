{{-- UNSTYLED contract-stub layout. No design system, no CSS — raw structure only. The real
     design pass replaces these views with the externally-provided HTML. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Vyrobel — Contract Stub' }}</title>
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
