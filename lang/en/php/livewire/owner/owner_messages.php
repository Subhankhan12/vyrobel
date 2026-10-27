<?php

// app/Livewire/Owner/OwnerMessages.php — the owner Messages inbox: sender names, receipts, day labels, the page subtitle.

return [
    // quick-reply chips: clicking one fills the message box with its text, which the owner then sends
    'quick_reply_payout' => 'When is my next payout?',
    'quick_reply_block_dates' => 'Can I block dates for personal use?',
    'quick_reply_tax_summary' => 'Send me the tax summary',
    // the owner themself, as the author of a message
    'you' => 'You',
    // the sender name when a message came from the management company and no staff member is named
    'management_team' => 'Your management team',
    // a message's small line under the bubble: who sent it and when
    'meta' => ':author · :time',
    // a short sender name when the message has no named staff author
    'team_author' => 'Team',
    // the read receipt on the owner's own message
    'receipt_read' => 'Read',
    'receipt_delivered' => 'Delivered',
    // the day separator in a thread, and the time stamp in the conversation list
    'today' => 'Today',
    'yesterday' => 'Yesterday',
    // the conversation list preview when the owner sent the last message
    'preview_mine' => 'You: :text',
    // the role line under a staff member's name when no role is known
    'awaiting_reply' => 'Awaiting a reply',
    'no_reply_yet' => 'No one has replied yet',
    'management_team_role' => 'Your management team',
    'team_role' => 'Team',
    // the page subtitle
    'subtitle_conversations' => '{1} :count conversation|[0,*] :count conversations',
    'subtitle_with_unread' => ':conversations · :number unread',
];
