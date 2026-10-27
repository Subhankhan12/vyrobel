<?php

// app/Http/Controllers/Operator/SeasonActionController.php — the Rates page's seasons (save, edit, delete, apply).

return [
    'choose_rate_plan' => 'Choose a rate plan.',
    'name_required' => 'Give the season a name.',
    'name_too_long' => 'Keep the season name to 80 characters.',
    'choose_first_night' => 'Choose the first night.',
    'choose_last_night' => 'Choose the last night.',
    'last_before_first' => 'The last night can’t be before the first night.',
    'price_required' => 'Enter a nightly price.',
    'read_only' => 'Your workspace is read-only right now — rate changes are paused.',
    'price_out_of_range' => 'Enter a nightly price greater than zero and no more than 1,000,000.',
    'season_saved' => 'Season saved. Review the blast radius, then apply it to write those nights.',
    'already_applied' => 'This season has already been applied, so it cannot be edited — the nightly rates it wrote have replaced whatever was there, and nothing recorded the previous prices. Author a new season over those nights instead.',
    'season_updated' => 'Season updated. Review the blast radius, then apply it to write those nights.',
    'deleted_after_apply' => 'Season ":season" deleted. ⚠️ The nightly rates it already applied are unchanged — deleting a season does not restore the prices it replaced. Set those nights yourself if they are wrong.',
    'deleted_not_applied' => 'Season ":season" deleted. It had not been applied, so no rates changed.',
    'applied' => ':season applied — :nights rewritten across :units.',
    'nights_count' => '{1} :count night|[0,*] :count nights',
    'units_count' => '{1} :count unit|[0,*] :count units',
    'overlapping_reapplied' => '{1} :count overlapping season was re-applied in precedence order.|[0,*] :count overlapping seasons were re-applied in precedence order.',
    'pick_unit_to_continue' => 'Pick a unit to continue.',
    'stay_nights_unpriced' => '{1} :count night of the stay still has no price.|[0,*] :count nights of the stay still have no price.',
];
