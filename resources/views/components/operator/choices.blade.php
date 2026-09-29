@props(['name', 'label', 'options' => [], 'selected' => null, 'model' => null, 'variant' => 'tiles'])
{{-- ⚠️ 2026-09-29 — CHOICE TILES: a short list shown whole, one click to choose, instead of a dropdown that hides it.
     Plain radio buttons underneath (the posted field is unchanged; each is named by its wrapping label). `model` binds
     them to an Alpine value (the Edit dialog); `variant="segmented"` is the two-way Active | Inactive toggle. --}}
<fieldset class="vy-choices vy-choices--{{ $variant }}">
    <legend class="vy-field-label">{{ $label }}</legend>
    <div class="vy-choice-row">
        @foreach ($options as $o)
            <label class="vy-choice">
                <input type="radio" name="{{ $name }}" value="{{ $o['value'] }}" @checked($o['value'] === $selected) @if ($model) x-model="{{ $model }}" @endif>
                <span class="vy-choice-face">
                    @if (! empty($o['icon']))
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $o['icon'] !!}</svg>
                    @endif
                    <span class="vy-choice-text">{{ $o['label'] }}</span>
                </span>
            </label>
        @endforeach
    </div>
</fieldset>
