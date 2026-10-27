<?php

// app/Services/OwnerUnitRequestService.php — the requested property types, and the refusals the owner and operator see.

return [
    // the requestable property types, keyed by units.unit_kind
    'kind_apartment' => 'Apartment',
    'kind_villa' => 'Villa',
    'kind_room' => 'Room',
    'kind_dorm_bed' => 'Dorm bed',
    // the operator's refusals (Unit requests screen)
    'owner_removed' => 'This request’s owner has been removed — it can only be declined.',
    'only_pending_approve' => 'Only a pending request can be approved.',
    'only_pending_decline' => 'Only a pending request can be declined.',
    // the owner's refusals when withdrawing, keyed by the request's real status
    'withdraw_approved' => 'Your manager already approved this request, so it can no longer be withdrawn.',
    'withdraw_declined' => 'Your manager already reviewed this request, so there is nothing to withdraw.',
    'withdraw_withdrawn' => 'This request has already been withdrawn.',
    'withdraw_draft' => 'This is still a draft — it hasn’t been sent, so there is nothing to withdraw.',
    'withdraw_default' => 'This request can no longer be withdrawn.',
];
