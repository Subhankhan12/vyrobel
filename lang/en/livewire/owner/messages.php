<?php

// resources/views/livewire/owner/messages.blade.php — the owner's Messages inbox (conversation rail + thread pane).

return [
    'title' => 'Messages',
    // the count above the conversation rail
    'conversation_count' => '{1} :count conversation|[0,*] :count conversations',
    'new_message' => 'New message',
    'no_contacts' => 'No management contacts yet.',
    'search_placeholder' => 'Search messages',
    'tab_unread' => 'Unread',
    // screen-reader text beside the Unread tab badge
    'unread_sr' => ':number unread',
    'no_messages_yet' => 'No messages yet',
    // screen-reader text beside a conversation's unread dot
    'unread_state' => 'Unread',
    'no_match' => 'No messages match “:query”',
    'no_match_hint' => 'Try another name or keyword',
    'caught_up' => 'You\'re all caught up',
    'no_unread' => 'No unread messages',
    'no_conversations' => 'No conversations yet',
    'no_conversations_rail_hint' => 'Use New message to reach your management team',
    'start_with' => 'Start your conversation with :name',
    'start_with_hint' => 'Send a message below — they\'ll reply in your inbox.',
    'attach_file' => 'Attach a file',
    'message_placeholder' => 'Message :name…',
    'message_aria' => 'Message',
    // the count of files chosen in the composer; the span is filled in by the page's script
    'files_selected' => '<span x-text="files"></span> file(s)',
    'send' => 'Send',
    'starred_messages' => 'Starred messages',
    'starred' => 'Starred',
    'no_starred' => 'No starred messages yet. Star a message to keep it here.',
    'pinned_by' => 'Pinned · :author —',
    'unpin' => 'Unpin',
    'pin' => 'Pin',
    'voice_message' => 'Voice message',
    'not_delivered' => '· Not delivered',
    'reply' => 'Reply',
    // the quoted snippet of a message that has only an attachment
    'attachment' => 'Attachment',
    'react' => 'React',
    'copy_text' => 'Copy text',
    'copied' => 'Copied',
    'unstar' => 'Unstar',
    'star' => 'Star',
    'star_message' => 'Star message',
    'pin_message' => 'Pin message',
    'start_of_conversation' => 'Start of your conversation with :name',
    'start_of_conversation_hint' => 'Send a message below to start the conversation.',
    // the banner above the composer while replying; the two parts are filled in by the page's script
    'replying_to' => 'Replying to <b x-text="replyTo?.author"></b> · <span x-text="replyTo?.snippet"></span>',
    'cancel_reply' => 'Cancel reply',
    'no_conversations_hint' => 'Use <strong>New message</strong> to reach your management team.',
];
