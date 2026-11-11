<?php

// resources/views/livewire/operator/operator-inbox.blade.php — the operator Inbox (/app/inbox): rail, open conversation, composer and its dialogs.

return [
    // the New-message dialog's fallback when a picked guest has no name to show
    'this_guest' => 'This guest',
    'page_title' => 'Messages',
    // the title's subtitle (also kept live by the page's refresh)
    'unread_subtitle' => '{1} :count unread|[0,*] :count unread',
    // the stat strip
    'stat_unread' => 'Unread',
    'stat_open' => 'Open',
    'stat_waiting' => 'Waiting on guest',
    'stat_needs_sending' => 'Needs sending',
    // the status tabs (All is common.all)
    'tab_unread' => 'Unread',
    'tab_arriving' => 'Arriving today',
    'tab_mine' => 'Assigned to me',
    'tab_closed' => 'Closed',
    'new_message' => 'New message',
    'read_only' => 'Read-only',
    'clear_all' => 'Clear all',
    'sort_toggle_title' => 'Toggle sort order',
    'sort_oldest' => 'Oldest first',
    'sort_newest' => 'Newest first',
    // the rail
    'search_label' => 'Search guests and messages',
    'search_placeholder' => 'Search guests & messages',
    'assigned_title' => 'Assigned',
    'no_conversations_match' => 'No conversations match.',
    'no_conversations_yet' => 'No conversations yet.',
    // the open conversation's header
    'vip' => 'VIP',
    'assigned_to' => 'Assigned to :name',
    'assign' => 'Assign',
    'snooze' => 'Snooze',
    'reopen' => 'Reopen',
    'open_reservation_title' => 'Open reservation :reference',
    'change_link' => 'Change link',
    'link_reservation' => 'Link reservation',
    'view_guest_profile' => 'View guest profile',
    // the latest internal note, above the messages
    'internal_note_title' => 'Internal — the guest never sees this',
    'internal_note' => '<strong>Internal · :author</strong> · :time — :body',
    // attachments and voice notes in the conversation
    'open_attachment_title' => 'Open :name',
    'pause_voice' => 'Pause voice message',
    'play_voice' => 'Play voice message',
    'voice_position' => 'Position',
    'download_attachment_title' => 'Download :name',
    'retry' => 'Retry',
    'no_messages_yet' => 'No messages yet — start the conversation below.',
    // the composer
    'composer_read_only' => 'Workspace paused — replies are read-only.',
    'composer_closed' => 'This conversation is closed — reopen it to reply.',
    'composer_not_connected' => ':channel isn’t connected yet — you can’t send on this channel.',
    'reply_placeholder_direct' => 'Write a reply…',
    'reply_placeholder_channel' => 'Reply via :channel…',
    'remove_file' => 'Remove :name',
    'starting_microphone' => 'Starting the microphone…',
    'cancel_recording' => 'Cancel recording',
    // :time is the recording's length, e.g. "0:42"
    'recording_time' => 'Recording :time',
    'stop_recording' => 'Stop',
    'discard_voice_note' => 'Discard voice note',
    'pause' => 'Pause',
    'listen_before_sending' => 'Listen before sending',
    'voice_note_ready' => 'Voice note ready — it goes with your reply.',
    'attach_documents' => 'Attach documents',
    'add_pictures' => 'Add pictures',
    'record_voice_note' => 'Record a voice note',
    // :keys is the keycaps (⌘ or Ctrl, then Enter); the words after them stay in their own span
    'send_shortcut' => ':keys<span class="vy-composer-hint-t">to send</span>',
    // closes the CONVERSATION (not a dialog)
    'close_conversation' => 'Close',
    'sending' => 'Sending…',
    'reply' => 'Reply',
    // the line under the composer: who reads the reply, and where
    'hint_owner_thread' => 'Your owner replies in their portal · this thread is not visible to the guest.',
    'hint_direct_account' => ':name reads this in their guest account — it isn’t emailed.',
    'hint_direct_no_account' => ':name has no guest account, so this stays here as a record — it isn’t emailed.',
    'hint_booking_channel' => 'The guest replies wherever they booked · contact details stay private.',
    // no conversation open
    'pick_conversation' => 'Pick a conversation',
    'pick_conversation_sub' => 'Or start one with a guest who is staying, arriving or has just left.',
    // the Assign dialog
    'assign_heading' => 'Assign conversation',
    'assign_sub' => ':name · they get a notification in Vyrobel, and the conversation stays in this shared inbox.',
    'assign_to' => 'Assign to',
    'unassigned' => 'Unassigned',
    'no_one_in_particular' => 'No one in particular',
    'team_note_label' => 'Note for the team · optional',
    'latest_note' => 'Latest · :author: :body',
    'team_note_placeholder' => 'Why you’re handing this over',
    'team_note_hint' => 'Staff only — the guest never sees notes.',
    // the Link-a-reservation dialog
    'link_heading' => 'Link a reservation',
    'link_sub' => ':name · pick which booking this conversation is about.',
    'reservation_legend' => 'Reservation',
    'not_linked' => 'Not linked',
    'no_reservation' => 'No reservation',
    'save_link' => 'Save link',
    // the New-message dialog
    'compose_sub' => 'Guests staying now, arriving soon, or who left in the last 14 days.',
    'guest_legend' => 'Guest',
    'find_guest' => 'Find a guest',
    // START WITH A UNIT OWNER (2026-11-11): the New message modal's second side
    'compose_kind_legend' => 'Who is it for?',
    'compose_tab_guest' => 'Guest',
    'compose_tab_owner' => 'Owner',
    'owner_legend' => 'Owner',
    'find_owner' => 'Find an owner',
    'this_owner' => 'This owner',
    // shown under an owner who has no sign-in to the owner portal yet — nobody could read the message
    'owner_no_portal' => 'No portal sign-in yet',
    'compose_where_owner_unpicked' => 'Read in the owner portal — pick an owner. It isn’t emailed.',
    // :name is the owner
    'compose_where_owner' => 'This goes to :name’s owner portal. Nothing is emailed.',
    'template_legend' => 'Start from a template · optional',
    'message_label' => 'Message',
    'message_placeholder' => 'Write your message…',
    'compose_where_unpicked' => 'Kept in this inbox — pick a guest to see where they’ll read it.',
    // :name is the picked guest's name (filled in by the page)
    'compose_where_account' => ':name can read it in their guest account.',
    'compose_where_no_account' => ':name has no guest account, so it stays here as a record.',
    'not_emailed' => 'It isn’t emailed.',
    'start_conversation' => 'Start conversation',
    // the New-message dialog when no guest qualifies
    'compose_none_sub' => 'No guest to message from here yet.',
    'compose_none_window' => 'A guest shows up here while they’re staying, when they arrive soon, or for 14 days after they leave.',
    'compose_none_profile' => 'To write to anyone else, open them in Guests and use <strong>Message</strong> on their profile.',
    'go_to_guests' => 'Go to Guests',
    // the composer's script: the send shortcut (:key is ⌘ or Ctrl) and why a reply could not go
    'shortcut_hint' => ':key + Enter to send',
    'too_many_files' => '{1} Send up to :count file at a time.|[0,*] Send up to :count files at a time.',
    'file_too_large' => ':name is over :mb MB.',
    'nothing_recorded' => 'Nothing was recorded — try again.',
    'error_offline' => 'No connection — your reply was kept. Try again.',
    'error_signed_out' => 'You have been signed out — reload to continue. Your reply was kept.',
    'error_too_large' => 'Together these files are too large to send at once — send fewer at a time.',
    'error_too_many' => 'Too many replies at once — wait a moment, then try again. Your reply was kept.',
    'error_generic' => 'Your reply was not sent — something went wrong. It was kept; try again.',
    'stop_recording_first' => 'Stop the recording first.',
    'files_over_limit' => 'Together these files are over :mb MB — send fewer at a time.',
];
