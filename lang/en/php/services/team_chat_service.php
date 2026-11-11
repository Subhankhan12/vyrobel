<?php

// app/Services/TeamChatService.php — the refusals a person sees in Team chat.

return [
    'choose_people' => 'Choose people who work in this workspace.',
    // :max is the largest number of people a group can have
    'group_too_large' => 'A group can have up to :max people.',
    'empty_message' => 'Write a message, or add a file or a voice note.',
    'message_too_long' => 'A message can be up to 5,000 characters.',
    'workspace_paused' => 'This workspace is paused — messages can be read, not sent.',
    'only_author' => 'You can change or delete only messages you wrote.',
    'already_deleted' => 'This message was deleted.',
    // :minutes is how long after sending the words can still be changed
    'edit_window_over' => 'A message can be edited for :minutes minutes after it is sent. You can still delete it.',
    'reaction_not_allowed' => 'That reaction is not available.',
    'edit_to_nothing' => 'A message cannot be left empty — delete it instead.',
];
