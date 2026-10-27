<?php

// app/Http/Controllers/Admin/AnnouncementController.php — publish and withdraw a platform announcement.

return [
    'audience_mismatch' => 'You ticked organisations but chose “Every organisation”. Choose “Chosen organisations” to send it only to them, or untick them.',
    'published_all_live' => 'Announcement published to every organisation — showing now.',
    'published_all_scheduled' => 'Announcement published to every organisation — it will show from its start time.',
    'published_chosen_live' => '{1} Announcement published to :count chosen organisation — showing now.|[0,*] Announcement published to :count chosen organisations — showing now.',
    'published_chosen_scheduled' => '{1} Announcement published to :count chosen organisation — it will show from its start time.|[0,*] Announcement published to :count chosen organisations — it will show from its start time.',
    'already_withdrawn' => 'Already withdrawn.',
    'withdrawn' => 'Withdrawn — it no longer shows to anyone.',
];
