<?php

// resources/views/livewire/operator/guest-profile.blade.php — the operator back office's Guest profile (Guests CRM detail).

return [
    // the page subtitle when the guest has no stay figure yet
    'no_stays_yet' => 'No stays yet',
    'back_to_guests' => '‹ Guests',
    'guest_since' => 'guest since :year',
    // header pills
    'vip' => 'VIP',
    'at_risk' => 'At risk',
    'repeat_guest' => 'Repeat guest',
    // header actions
    'message_button' => 'Message',
    // the header button AND the title of the dialog it opens
    'edit_profile' => 'Edit profile',
    'current_stay' => 'Current stay',
    // the stat strip
    'stays_label' => 'Stays',
    'cancellations_excluded' => 'cancellations excluded',
    'nights_label' => 'Nights',
    'across_every_stay' => 'across every stay',
    'avg_per_night_label' => 'Avg / night',
    // the source-mix line under Avg / night when there is none
    'no_stays_yet_lower' => 'no stays yet',
    // Stay history
    'stay_history' => 'Stay history',
    'stays_count' => '{1} :count stay|[0,*] :count stays',
    'stay_dates' => ':check_in – :check_out',
    'here_now' => 'Here now',
    'nights_count' => '{1} :count night|[0,*] :count nights',
    'refund_line' => '− :amount refund',
    'stays_empty' => 'No stays yet — this profile has never been booked.',
    // Latest review
    'latest_review' => 'Latest review',
    'all_reviews' => 'All reviews',
    'rating_stars_label' => ':rating out of 5 stars',
    'your_response' => 'Your response: :response',
    'no_reviews_yet' => 'No reviews yet.',
    // Notes
    'internal_only' => 'internal only',
    'note_placeholder' => 'Add an internal note…',
    'note_aria' => 'Add an internal note',
    'add_note' => 'Add note',
    'note_redacted' => 'Redacted — this guest has been anonymized',
    'no_notes_yet' => 'No notes yet.',
    // Lifetime value (the dark card)
    'lifetime_value' => 'Lifetime value',
    // follows ":count stays · :count nights"; the leading dot is the separator
    'avg_a_night' => '· avg :amount a night',
    'open_folio_line' => ':amount open folio',
    'settles_at_checkout' => 'settles at checkout',
    // Contact
    'contact' => 'Contact',
    'city' => 'City',
    // a statement about the guest, under the contact details
    'prefers_email' => 'Prefers email',
    'no_channel_preference' => 'No channel preference set',
    'marketing_consent_on_file' => 'marketing consent on file',
    'no_marketing_consent' => 'no marketing consent',
    // Preferences
    'preferences' => 'Preferences',
    'preferences_shown_to' => 'Shown to housekeeping & the front desk.',
    'no_preferences' => 'No preferences set — add them from Edit profile.',
    // Tags
    'tags' => 'Tags',
    'remove_tag_title' => 'Remove this tag',
    'tag_placeholder' => 'Add a tag…',
    'tag_aria' => 'Add a tag',
    'tags_shared_note' => 'Tags are shared across this workspace — a new name is added to the catalogue.',
    'anonymized_notice' => 'This guest has been anonymized. Their details cannot be edited — the profile is kept only so their financial history stays intact.',
    // GDPR erasure
    'erase_heading' => 'Erase this guest',
    'erase_explainer' => 'Right to erasure. This <strong>permanently</strong> destroys their name, contact details, ID documents, notes and message history. It <strong>cannot be undone</strong>. Their stays and financial records are kept — without their identity — so the books still balance.',
    // the browser's confirm() box before the erase is sent
    'erase_confirm_dialog' => 'Permanently erase this guest? Their personal data cannot be recovered.',
    // ERASE is the exact word the server checks — keep it in English, in capitals
    'type_erase_to_confirm' => 'Type ERASE to confirm',
    'erase_button' => 'Erase',
    // Edit profile dialog
    'edit_profile_sub' => 'Contact changes apply to future confirmations, not emails already sent.',
    'first_name' => 'First name',
    'last_name' => 'Last name',
    'city_country' => 'City / country',
    'nationality' => 'Nationality',
    'two_letters_note' => '· 2 letters',
    'nationality_title' => 'Two letters, e.g. IE',
    'stay_preferences' => 'Stay preferences',
    'stay_preferences_note' => '· shown to housekeeping and the front desk',
    // the four switches: a name and a one-line hint each
    'vip_guest' => 'VIP guest',
    'vip_guest_hint' => 'Flagged on arrivals and at the front desk',
    'at_risk_switch' => 'At risk',
    'at_risk_hint' => 'Flag this guest for the team',
    'prefers_email_switch' => 'Prefers email',
    'prefers_email_hint' => 'How they like to be contacted',
    'marketing_consent' => 'Marketing consent',
    'marketing_consent_hint' => 'Governs marketing email',
];
