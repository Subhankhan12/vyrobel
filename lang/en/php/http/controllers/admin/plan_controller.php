<?php

// app/Http/Controllers/Admin/PlanController.php — create, edit and activate subscription plans.
// :plan is the plan's name; :reason is the sentence the plan check gives for why it cannot go live.

return [
    'created_draft' => ':plan created as a draft — add its Stripe Price id, then activate it.',
    'saved_stays_draft' => 'Saved, but it stays a draft: :reason',
    'updated' => ':plan updated.',
    'cannot_activate' => ':plan cannot be activated: :reason',
    'activated' => ':plan activated.',
    'deactivated' => ':plan deactivated.',
];
