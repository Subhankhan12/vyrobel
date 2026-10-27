<?php

declare(strict_types=1);

/**
 * REGRESSION — wireframe-parity sweep WAVE 3 (minor), Insights unit labels.
 *
 * Both surfaces printed a bare number where the figure is a percentage:
 *  · the operator weekday band rendered "12" for 12% occupancy — a tile that reads as a COUNT of nights;
 *  · the owner occupancy delta rendered "▲3" beside percentages, where the value is percentage POINTS.
 *
 * Neither is a money figure and no number changed — only the unit the reader is owed.
 */
it('prints the operator weekday occupancy tiles as percentages', function () {
    $blade = i18nEnglishSource(resource_path('views/livewire/operator/insights.blade.php'));

    expect($blade)->toContain("{{ \$w['pct'] }}%")
        ->and($blade)->not->toContain("font-weight: 700;\">{{ \$w['pct'] }}</div>");
});

it('labels the owner occupancy delta in percentage points', function () {
    $blade = i18nEnglishSource(resource_path('views/livewire/owner/insights.blade.php'));

    // (I18N-01: the delta prints through delta_up / delta_down with `:pts` = abs(delta_pts); "pts" is in the words)
    expect($blade)->toContain("['pts' => e(abs(\$m['delta_pts']))]")
        ->and(__('livewire/owner/insights.delta_up', [], 'en'))->toEndWith(':pts pts')
        ->and(__('livewire/owner/insights.delta_down', [], 'en'))->toEndWith(':pts pts')
        ->and($blade)->not->toContain("{{ abs(\$m['delta_pts']) }}</span>");
});
