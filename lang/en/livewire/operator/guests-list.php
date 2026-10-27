<?php

// resources/views/livewire/operator/guests-list.blade.php — the operator Guests CRM list (/app/guests).

return [
    'page_title' => 'Guests',
    // the three tiles
    'kpi_profiles' => 'Guest profiles',
    'kpi_profiles_sub' => 'in this workspace',
    'kpi_profiles_sub_anonymized' => '{1} in this workspace · :count anonymized|[0,*] in this workspace · :count anonymized',
    'kpi_in_house' => 'In-house tonight',
    'kpi_in_house_sub' => 'guests checked in · across every property',
    'kpi_repeat' => 'Repeat guests',
    'kpi_repeat_none' => 'no bookings yet — cancellations and no-shows don’t count',
    'kpi_repeat_sub' => '{1} :repeat of :count guest with a booking booked again|[0,*] :repeat of :count guests with a booking booked again',
    // the toolbar
    'search_label' => 'Search guests',
    'search_placeholder' => 'Search name, city or exact email',
    'clear_search' => 'Clear search',
    // the button that turns select mode on ("Done" turns it off)
    'select' => 'Select',
    'add_guest' => 'Add guest',
    // names the row of filter chips for a screen reader
    'chips_label' => 'Show',
    // the empty states
    'empty_title' => 'No guests yet',
    'empty_text' => 'A guest profile is created with their first booking — or add one now.',
    'no_match' => 'No guests match “:query”',
    'none_in_view' => 'No guests in this view',
    'search_scope' => 'Search matches name, city and an exact email address — phone and partial email are encrypted at rest, so they cannot be searched.',
    'total_in_all' => '{1} :count guest in total.|[0,*] :count guests in total.',
    'clear_all' => 'Clear search and filters',
    // the table (:sorted is how it is sorted, e.g. "A–Z by surname")
    'caption' => 'Guests, :sorted',
    'select_page' => 'Select every guest on this page',
    'col_guest' => 'Guest',
    'col_nationality' => 'Nationality',
    'col_stays' => 'Stays',
    'col_stay' => 'Stay',
    'col_lifetime_hint' => 'Billed per currency — not sortable across currencies',
    'col_lifetime' => 'Lifetime',
    'select_guest' => 'Select :name',
    'tag_vip' => 'VIP',
    'tag_at_risk' => 'At risk',
    // the contact line of a guest whose personal data was erased
    'contact_erased' => 'Erased',
    // an open balance under the billed figure, e.g. "€120.00 due"
    'amount_due' => ':amount due',
    'no_bookings' => 'No bookings',
    'actions_for' => 'Actions for :name',
    // the footer
    'summary' => '{1} <strong>:first–:last</strong> of <strong>:count</strong> guest<span class="vy-gl-sorted"> · :sorted</span>|[0,*] <strong>:first–:last</strong> of <strong>:count</strong> guests<span class="vy-gl-sorted"> · :sorted</span>',
    'rows' => 'Rows',
    'pages' => 'Pages',
    'previous_page' => 'Previous page',
    'page_of' => 'Page :current of :last',
    'next_page' => 'Next page',
    // the row menu
    'open_profile' => 'Open profile',
    'edit_details' => 'Edit details',
    'erase_personal_data' => 'Erase personal data',
    'menu_erased_note' => 'Personal data erased — there is nothing to edit.',
    // the bulk bar
    'bulk_label' => 'Selected guests',
    'bulk_selected' => '{1} <strong>:count</strong> selected|[0,*] <strong>:count</strong> selected',
    'bulk_message' => 'Message',
    'add_tag' => 'Add tag',
    'bulk_erase' => 'Erase',
    // the Message dialog
    'message_title' => '{1} Message :count guest|[0,*] Message :count guests',
    'mail_off' => 'Email isn’t being sent from Vyrobel yet, so a message can’t reach anyone from here.',
    'sent_as_replies_to' => 'Sent by email as :sender. Replies go to :reply_to.',
    'sent_replies_to' => 'Sent by email. Replies go to :reply_to.',
    'sent_as_no_reply_settings' => 'Sent by email as :sender. Replies won’t reach you yet — add a reply-to address in Settings → Guest-facing brand.',
    'sent_no_reply_settings' => 'Sent by email. Replies won’t reach you yet — add a reply-to address in Settings → Guest-facing brand.',
    'sent_as_no_reply_ask' => 'Sent by email as :sender. Replies won’t reach you yet — ask someone who manages property settings to add a reply-to address.',
    'sent_no_reply_ask' => 'Sent by email. Replies won’t reach you yet — ask someone who manages property settings to add a reply-to address.',
    'message_label' => 'Message',
    'message_placeholder' => 'Write your message…',
    'marketing_title' => 'This is a marketing message',
    'marketing_hint' => 'Only guests who agreed to marketing get it — the rest are skipped.',
    'send_button' => '{1} Send to :count guest|[0,*] Send to :count guests',
    // the Add tag dialog
    'tag_title' => '{1} Tag :count guest|[0,*] Tag :count guests',
    'tag_sub' => 'Tags are shared across this workspace — a new name joins the list.',
    'tag_label' => 'Tag',
    'tag_placeholder' => 'e.g. Corporate',
    // the Erase dialogs (:word is the word to type, ERASE — the server checks that exact word, so it stays as it is)
    'erase_title' => '{1} Erase :count guest?|[0,*] Erase :count guests?',
    'erase_sub' => 'Their personal data is erased for good. This can’t be undone.',
    'bulk_erase_confirm' => 'Permanently erase the selected guests? Their personal data cannot be recovered.',
    'type_word_to_confirm' => 'Type :word to confirm',
    'keep_them' => 'Keep them',
    'erase_button' => '{1} Erase :count|[0,*] Erase :count',
    'erase_needs_permission' => 'Erasing a guest needs the erase permission (Property Owner or Group Admin).',
    // the Add guest dialog
    'add_sub' => 'Their profile is created now — bookings attach to it later.',
    'first_name' => 'First name',
    'last_name' => 'Last name',
    'city_country' => 'City / country',
    'not_given' => 'Not given',
    'vip_title' => 'VIP guest',
    'vip_hint' => 'Flagged on arrivals and at the front desk',
    'prefers_email_title' => 'Prefers email',
    'prefers_email_hint' => 'How they like to be contacted',
    'marketing_consent_title' => 'Marketing consent',
    'marketing_consent_hint' => 'They agreed to marketing email',
    // the Edit dialog (:profile_link is the link's script binding — keep the attribute exactly as it is)
    'edit_title' => 'Edit guest',
    'at_risk_hint' => 'Flag this guest for the team',
    'edit_note' => 'Stay preferences, notes and tags stay as they are — change them on the <a class="vy-gl-link" x-bind:href=":profile_link">guest’s profile</a>.',
    // the one-guest Erase dialog
    'erase_named' => 'Erase :name?',
    'ready_to_erase' => 'Ready to erase',
    'type_word_in_capitals' => 'Type :word in capitals',
];
