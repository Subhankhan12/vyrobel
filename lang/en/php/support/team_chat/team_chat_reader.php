<?php

// App\Support\TeamChat\TeamChatReader — the team chat list: a conversation's name and the one-line preview of its last message.

return [
    // shown for someone who has left the workspace
    'former_teammate' => 'Former teammate',
    // a group with no name of its own: two first names (:names) and how many others are in it
    'group_name_others' => '{1} :names and :count others|[0,*] :names and :count others',
    // the preview of a message sent with no text, only files
    'preview_voice' => 'Voice message',
    'preview_photos' => '{1} Photo|[0,*] :count photos',
    'preview_files' => '{1} File|[0,*] :count files',
];
