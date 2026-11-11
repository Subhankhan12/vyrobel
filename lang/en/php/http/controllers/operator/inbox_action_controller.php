<?php

// app/Http/Controllers/Operator/InboxActionController.php — the Inbox's reply, assign, close / snooze / reopen, retry, link and new message.

return [
    'body_max' => 'A reply can be up to 5,000 characters.',
    'attachments_max' => 'Send up to :max files at a time.',
    'attachment_too_big' => 'Each file can be up to :mb MB.',
    'attachment_type' => 'Send pictures (JPG, PNG, WebP, GIF) or documents (PDF, Word, Excel, CSV, text).',
    'attachment_unreadable' => 'That file could not be read — files can be up to :mb MB. Choose it again.',
    'voice_too_big' => 'A voice note can be up to :mb MB.',
    'voice_unreadable' => 'That voice note could not be read — record it again.',
    'reply_empty' => 'Write a reply, or add a file or a voice note.',
    'files_not_allowed' => 'Files and voice notes can be sent to owners and in Direct conversations only.',
    'file_not_saved' => 'A file could not be saved — nothing was sent. Try again.',
    // :reason is the channel's own explanation
    'could_not_send' => 'Couldn’t send — :reason',
    'files_not_saved_after_send' => 'Your message was sent, but its files could not be saved — send them again.',
    'reply_sent' => 'Reply sent.',
    'not_a_member' => 'That user is not a member of this organization.',
    'assignment_updated' => 'Conversation assignment updated.',
    'conversation_closed' => 'Conversation closed.',
    'conversation_reopened' => 'Conversation reopened.',
    'conversation_snoozed' => 'Conversation snoozed.',
    'still_could_not_send' => 'Still couldn’t send — :reason',
    'message_resent' => 'Message re-sent.',
    'reservation_linked' => 'Reservation linked.',
    'conversation_started' => 'Conversation started.',
    // :name is the owner — they cannot read a message until they have a sign-in to the owner portal
    'owner_no_portal' => 'No portal sign-in yet — invite :name to the owner portal first.',
    'workspace_paused' => 'Workspace paused — messaging is read-only until :date.',
    'workspace_paused_for_now' => 'Workspace paused — messaging is read-only for now.',
];
