<?php

// app/Http/Controllers/Operator/GuestActionController.php — the guest profile's writes (edit, add, notes, tags, erase).

return [
    'anonymized_cannot_edit' => 'That guest has been anonymized — their details cannot be edited.',
    'profile_updated' => 'Profile updated.',
    'guest_added' => 'Guest added.',
    'anonymized_no_conversation' => 'That guest has been anonymized — no new conversation can be started.',
    'note_added' => 'Note added.',
    'tag_added' => 'Tag added.',
    'tag_removed' => 'Tag removed.',
    'already_anonymized' => 'That guest has already been anonymized.',
    'confirm_required' => 'Type ERASE to confirm this permanent erasure.',
    'confirm_exact' => 'Type ERASE exactly to confirm this permanent erasure.',
    'guest_erased' => 'Guest erased. Their personal data is gone; the financial history remains.',
    'workspace_paused' => 'Your workspace is paused (read-only) — changes are off until you resume. Resume from Billing.',
];
