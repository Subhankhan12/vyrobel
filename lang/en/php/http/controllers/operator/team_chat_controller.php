<?php

// app/Http/Controllers/Operator/TeamChatController.php — the team chat's refusals (shown by the page's script).

return [
    'choose_members' => 'Choose who to message.',
    'group_max_people' => 'A group can have up to :limit people.',
    'group_name_max' => 'A group name can be up to 80 characters.',
    'files_max' => 'Send up to :limit files at a time.',
    'file_too_big' => 'Each file can be up to :size MB.',
    'file_types' => 'Send pictures (JPG, PNG, WebP, GIF) or documents (PDF, Word, Excel, CSV, text).',
    'file_unreadable' => 'That file could not be read — files can be up to :size MB. Choose it again.',
    'voice_too_big' => 'A voice note can be up to :size MB.',
    'voice_unreadable' => 'That voice note could not be read — record it again.',
    'conversation_not_found' => 'That conversation was not found.',
    'message_not_found' => 'That message was not found.',
    'choose_reaction' => 'Choose a reaction.',
    // :min is the fewest characters a search is run with
    'search_too_short' => 'Type at least :min characters to search.',
    'search_too_long' => 'Search for up to 100 characters.',
    // the line above the results: how many messages matched
    'search_summary' => '{0} No messages found|{1} :count message found|[2,*] :count messages found',
    // more matched than are listed: :shown are, :count in all
    'search_summary_capped' => '[0,*] Showing the newest :shown of :count messages',
];
