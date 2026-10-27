<?php

// app/Http/Controllers/Operator/OperatorRateProposalController.php — the operator's rate proposals to an owner.

return [
    'rate_required' => 'Enter the nightly rate you propose.',
    'weekend_uplift_range' => 'The weekend uplift is a percentage between 0 and 200.',
    'min_stay_range' => 'The minimum stay is a whole number of nights, 1 to 365.',
    'from_date_required' => 'Choose the first night.',
    'to_date_required' => 'Choose the last night.',
    'to_date_after_from' => 'The last night has to be on or after the first.',
    'note_max' => 'A note to the owner can be up to 500 characters.',
    'rate_invalid' => 'Enter a valid nightly rate, e.g. 175 or 175.50.',
    'proposal_sent' => 'Rate proposal sent to the owner for approval.',
    'already_decided' => 'That proposal was already decided.',
    'proposal_withdrawn' => 'Proposal withdrawn.',
    'counter_not_open' => 'That counter is no longer open.',
    'counter_accepted' => 'Counter accepted — the owner’s rate is now live.',
];
