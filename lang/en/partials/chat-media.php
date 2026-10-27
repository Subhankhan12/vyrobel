<?php

// resources/views/partials/chat-media.blade.php — the shared voice-note recorder and player (its messages and spoken position).

return [
    'unsupported' => 'This browser cannot record voice notes.',
    'mic_not_allowed' => 'Allow the microphone for this site to record a voice note.',
    'mic_not_found' => 'No microphone was found.',
    'mic_failed' => 'The microphone could not be started.',
    // what a screen reader says for the player's slider, e.g. "0:12 of 0:40"
    'spoken_position' => ':at of :length',
];
