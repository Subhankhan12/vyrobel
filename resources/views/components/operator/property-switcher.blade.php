{{-- Phase 6a · multi-property — the topbar property switcher. Sets the operator's working property
     (or "all properties" for the roll-up screens) via a POST → session → PRG. Reads the current
     PropertyContext, which is active for the /app GET that renders this shell.

     Honesty: only properties the user can access (the property_user allow-list, or the whole org when
     they have no pivot rows) are offered. With a single accessible property there is nothing to switch
     between, so we render a static label — no dropdown, no meaningless "all". --}}
@php
    $ctx = app(\App\Support\Property\PropertyContext::class);
    $accessibleIds = $ctx->active() ? $ctx->accessibleIds() : [];
    $properties = $accessibleIds === []
        ? collect()
        : \App\Models\Property::query()->whereIn('id', $accessibleIds)->orderBy('name')->get(['id', 'name']);
    $selection = $ctx->selection();
    $currentName = optional($properties->firstWhere('id', $ctx->id()))->name;
@endphp

@if ($properties->count() > 1)
    <form method="POST" action="{{ route('app.property.switch') }}" class="vy-propsw">
        @csrf
        <svg class="vy-propsw-ico" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 9h.01M9 13h.01M9 17h.01M15 9h.01M15 13h.01M15 17h.01"/></svg>
        <select name="property_id" class="vy-propsw-select" onchange="this.form.submit()" title="{{ $currentName ?? __('components/operator/property-switcher.all_properties') }}" aria-label="{{ __('components/operator/property-switcher.working_property') }}">
            <option value="all" @selected($selection === \App\Support\Property\PropertyContext::ALL)>{{ __('components/operator/property-switcher.all_properties') }}</option>
            @foreach ($properties as $p)
                <option value="{{ $p->id }}" @selected($selection === (string) $p->id)>{{ $p->name }}</option>
            @endforeach
        </select>
    </form>
@elseif ($currentName)
    <span class="vy-propsw vy-propsw-static" title="{{ __('components/operator/property-switcher.your_property') }}">
        <svg class="vy-propsw-ico" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 9h.01M9 13h.01M9 17h.01M15 9h.01M15 13h.01M15 17h.01"/></svg>
        {{ $currentName }}
    </span>
@endif
