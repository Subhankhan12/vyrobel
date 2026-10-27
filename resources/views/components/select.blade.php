{{-- The Vyrobel SELECT, rich field — a real select, opened and drawn by the one engine (components/vy-selects.blade.php), with
     its hint and live clock drawn over the field's right side (2026-10-09, approved on /app/profile; 2026-10-10, rebuilt on the
     engine for the app-wide rollout, so the profile's select and every other drop-down are ONE definition).

     ⚠️ A REAL <select> carries the value: it posts with the form, `x-model` and any other attribute passed to this component
        land on it, and it works before (and without) a script. Groups are <optgroup>s; an option may carry a hint (shown muted
        beside its label) and a clock (an IANA zone: the live time there, in the list and on the field when it is chosen).

     Props: name · id · options (list of [value, label, hint?, group?, clock?, keywords?], or [value => label]) · value ·
     labelledby (the id of the field's visible label — it names the open list; the select itself is named by label[for]) · searchable (null = automatic above 8 options) · placeholder (shown
     when nothing is chosen) · searchPlaceholder · emptyText · deviceTimezone (offer this device's zone when it differs) ·
     minWidth (the panel's least width in px). Other attributes (x-model, required, …) go to the select.

     RAW-BLADE RULE: every directive follows a space, a newline or a tag. --}}
@props([
    'name',
    'id',
    'options' => [],
    'value' => null,
    'labelledby' => null,
    'searchable' => null,
    'placeholder' => __('components/select.placeholder'),
    'searchPlaceholder' => __('components/select.search_placeholder'),
    'emptyText' => __('components/select.empty_text'),
    'deviceTimezone' => false,
    'minWidth' => 0,
])
@php
    $items = [];
    foreach ($options as $key => $option) {
        $option = is_array($option) ? $option : ['value' => (string) $key, 'label' => (string) $option];
        $label = (string) $option['label'];
        $hint = (string) ($option['hint'] ?? '');
        $items[] = [
            'value' => (string) $option['value'],
            'label' => $label,
            'hint' => $hint,
            'group' => (string) ($option['group'] ?? ''),
            'clock' => (string) ($option['clock'] ?? ''),
            'keywords' => mb_strtolower((string) ($option['keywords'] ?? ($label.' '.$option['value'].' '.$hint))),
        ];
    }
    $current = (string) ($value ?? '');
    $chosen = collect($items)->firstWhere('value', $current);
    $groups = collect($items)->groupBy('group', true);
@endphp
<div class="vys-rich">
    {{-- Named by the page's own label[for] (a real select needs nothing more); the label's id names the open list. --}}
    <select name="{{ $name }}" id="{{ $id }}"
            @if ($labelledby) data-labelledby="{{ $labelledby }}" @endif
            @if ($searchable === true || ($searchable === null && count($items) > 8)) data-search @elseif ($searchable === false) data-search="off" @endif
            data-search-placeholder="{{ $searchPlaceholder }}" data-empty="{{ $emptyText }}"
            @if ($deviceTimezone) data-suggest-device-zone @endif
            @if ($minWidth) data-panel-width="{{ (int) $minWidth }}" @endif
            {{ $attributes }}>
        @if ($chosen === null)
            <option value="" disabled selected hidden>{{ $placeholder }}</option>
        @endif
        @foreach ($groups as $group => $members)
            @if ($group !== '')<optgroup label="{{ $group }}">@endif
            @foreach ($members as $item)
                <option value="{{ $item['value'] }}" data-keywords="{{ $item['keywords'] }}" @if ($item['hint'] !== '') data-hint="{{ $item['hint'] }}" @endif @if ($item['clock'] !== '') data-clock="{{ $item['clock'] }}" @endif @selected($item['value'] === $current)>{{ $item['label'] }}</option>
            @endforeach
            @if ($group !== '')</optgroup>@endif
        @endforeach
    </select>
    <span class="vys-deco" aria-hidden="true"><span class="vys-deco-hint">{{ $chosen['hint'] ?? '' }}</span><span class="vys-deco-clock" data-clock="{{ $chosen['clock'] ?? '' }}"></span></span>
</div>
