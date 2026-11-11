<?php

// resources/views/livewire/operator/team-chat.blade.php — the operator Team chat (/app/inbox/team).

return [
    'title' => 'Messages',
    'subtitle' => 'Conversations with your colleagues',
    // a Vyrobel support session sees that team chat is private, and nothing of it
    'private_heading' => 'Team messages are private to the people in them',
    'private_body' => 'A Vyrobel support session can look at the back office, but not at the conversations between colleagues.',

    // the conversations
    'rail_aria' => 'Team conversations',
    'rail_title' => 'Team',
    'new_conversation' => 'New conversation',
    'new_button' => 'New',
    'search_label' => 'Search people and conversations',
    'search_placeholder' => 'Search people and chats',
    'preview_waiting' => 'Not sent — your message is waiting here',
    // :text is the last message's own words
    'preview_you' => 'You: :text',
    'preview_none' => 'No messages yet',
    'unread_aria' => '{1} :count unread|[0,*] :count unread',
    'start_a_conversation' => 'Start a conversation',
    'no_one_heading' => 'No one else works here yet',
    'no_one_body' => 'When colleagues join this workspace, you can message them here.',
    'invite_team' => 'Invite your team',
    'no_conversations_heading' => 'No conversations yet',
    'no_conversations_body' => 'Message one colleague, or a few at once.',
    // :term is what the person typed in the search
    'nobody_matches' => 'Nobody matches “:term”.',

    // the open conversation
    'pane_aria' => 'Conversation',
    'blank_heading' => 'Talk to your team',
    'blank_body' => 'Pick a conversation, or start one with a colleague — pictures, documents and voice notes included.',
    'back_aria' => 'Back to conversations',
    'sound_on_aria' => 'Sound on — turn off',
    'sound_off_aria' => 'Sound off — turn on',
    'sound_on' => 'Sound on',
    'sound_off' => 'Sound off',
    'reload' => 'Reload',
    'dismiss' => 'Dismiss',
    'stream_aria' => 'Messages',
    'load_earlier' => 'Load earlier messages',
    'say_hello_to' => 'Say hello to :name',
    'say_hello_group' => 'Say hello to the group',
    'hello_body' => 'Messages here are seen only by the people in this conversation.',
    'open_file_aria' => 'Open :name',
    'download_file_aria' => 'Download :name',
    'pause_voice_aria' => 'Pause voice message',
    'play_voice_aria' => 'Play voice message',
    'position_aria' => 'Position',
    'new_messages_jump' => '{1} :count new message ↓|[0,*] :count new messages ↓',

    // the composer
    'paused' => 'This workspace is paused — messages can be read, not sent.',
    'remove_file_aria' => 'Remove :name',
    'starting_mic' => 'Starting the microphone…',
    'cancel_recording_aria' => 'Cancel recording',
    // :time is minutes:seconds, e.g. 0:42
    'recording' => 'Recording :time',
    'recording_cap' => 'up to :time',
    'stop_recording_aria' => 'Stop recording',
    'discard_voice_aria' => 'Discard voice note',
    'pause' => 'Pause',
    'listen_before_sending' => 'Listen before sending',
    'send_voice_aria' => 'Send voice note',
    'attach_documents' => 'Attach documents',
    'add_pictures' => 'Add pictures',
    'message_label' => 'Message',
    // :name is the colleague's first name
    'message_placeholder_direct' => 'Message :name',
    'message_placeholder_group' => 'Message the group',
    'send_aria' => 'Send',
    'record_voice' => 'Record a voice note',
    // :count is the most files one message may carry, :mb the largest file; both are wrapped in markup on the page
    'composer_hint' => '{1} Enter to send · Shift + Enter for a new line · up to :count files of :mb MB|[0,*] Enter to send · Shift + Enter for a new line · up to :count files of :mb MB',
    'drop_to_attach' => 'Drop to attach',

    // the New conversation dialog
    'new_dialog_sub' => 'Pick one colleague, or several for a group. Only the people you add can read it.',
    'find_colleague' => 'Find a colleague',
    'colleagues_legend' => 'Colleagues',
    'group_name_label' => 'Group name · optional',
    'group_name_placeholder' => 'For example: Morning shift',
    'starting' => 'Starting…',
    'start_group' => 'Start group',
    'start_conversation' => 'Start conversation',

    // the words the page's script shows
    // :names is the other members' first names, comma separated
    'members_line' => '{1} :count people · :names|[0,*] :count people · :names',
    'former_teammate' => 'Former teammate',
    'receipt_sent' => '· Sent',
    'receipt_seen' => '· Seen',
    'receipt_seen_by_everyone' => '· Seen by everyone',
    'receipt_seen_by' => '· Seen by :names',
    'yesterday' => 'Yesterday',
    'not_sent' => 'Not sent',
    'sending' => 'Sending…',
    'signed_out_banner' => 'You have been signed out, or this workspace needs attention. Reload to continue.',
    'start_failed' => 'That conversation could not be started. Try again.',
    'too_many_files' => '{1} Send up to :count files at a time.|[0,*] Send up to :count files at a time.',
    'wrong_file_type' => 'Send pictures (JPG, PNG, WebP, GIF) or documents (PDF, Word, Excel, CSV, text).',
    'file_too_big' => ':name is over :mb MB.',
    'message_too_long' => 'A message can be up to 5,000 characters.',
    'files_too_big_together' => 'Together these files are over :mb MB — send fewer at a time.',
    'voice_message' => 'Voice message',
    'photo' => 'Photo',
    'file' => 'File',
    'files_count' => '{1} :count file|[0,*] :count files',
    'not_sent_notice_to' => 'Your message to :name was not sent — it is waiting in that conversation.',
    'not_sent_notice' => 'Your message was not sent — it is waiting in that conversation.',
    'reason_signed_out' => 'You have been signed out — reload to continue. Your message was kept.',
    'reason_no_connection' => 'No connection — your message was kept. Try again.',
    'reason_too_large' => 'Together these files are too large to send at once — send fewer at a time.',
    'reason_too_many' => 'Too many messages at once — wait a moment, then try again. Your message was kept.',
    'reason_failed' => 'Your message was not sent — something went wrong. It was kept; try again.',
    'nothing_recorded' => 'Nothing was recorded — try again.',

    // EDIT, DELETE AND THE PRIVACY NOTE (2026-11-11)
    'message_deleted' => 'This message was deleted',
    'edited' => 'edited',
    // shown under a message whose text had a guest's name, email or number removed when that guest was erased
    'redacted_note' => 'Part of this message was removed to protect someone\'s privacy.',
    'tools_aria' => 'Message actions',
    'options_aria' => 'Message options',
    'edit' => 'Edit',
    'delete' => 'Delete',
    'edit_aria' => 'Edit your message',
    'edit_save' => 'Save',
    'edit_hint' => 'Enter to save · Esc to cancel',
    'delete_confirm' => 'Delete this message for everyone?',
    'edit_empty' => 'A message cannot be left empty — delete it instead.',
    'edit_failed' => 'The message could not be changed — try again.',
    'delete_failed' => 'The message could not be deleted — try again.',

    // REACTIONS
    'react_aria' => 'Add a reaction',
    'picker_aria' => 'Choose a reaction',
    // :emoji is the reaction, :names who gave it
    'reaction_chip_aria' => ':emoji — :names',
    'reaction_failed' => 'The reaction could not be saved — try again.',

    // SEARCH IN A CONVERSATION
    'search_chat_open' => 'Search this conversation',
    'search_chat_close' => 'Close search',
    'search_chat_label' => 'Search messages in this conversation',
    'search_chat_placeholder' => 'Search messages',
    'search_go' => 'Go to message',
    'search_you' => 'You',
    'search_failed' => 'The search did not work — try again.',
    'search_too_far' => 'That message is too far back to jump to. Scroll up to load earlier messages.',
    'search_hint' => 'Type at least two characters.',

    // TYPING AND ONLINE
    // :name is a first name
    'typing_one' => ':name is typing…',
    // :a and :b are first names
    'typing_two' => ':a and :b are typing…',
    'typing_many' => 'Several people are typing…',
    'online_now' => 'Online now',
    'online_count' => '{1} :count online|[0,*] :count online',

    // DESKTOP NOTIFICATIONS (the switch next to the sound; the words a notification says are in partials/live-pulse)
    'desktop_on_aria' => 'Desktop notifications on — turn off',
    'desktop_off_aria' => 'Desktop notifications off — turn on',
    'desktop_on' => 'Desktop notifications on',
    'desktop_off' => 'Desktop notifications off',
    'desktop_help' => 'Desktop notifications appear only while a Vyrobel tab is open in this browser, and only when you are looking at another window. They say how many messages are waiting — never who wrote or what. They do not work in Safari on an iPhone.',
];
