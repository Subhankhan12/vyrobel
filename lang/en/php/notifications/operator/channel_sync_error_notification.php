<?php

// app/Notifications/Operator/ChannelSyncErrorNotification.php — :provider is the channel's provider name, :channel its name.

return [
    'title' => 'Channel sync error · :provider',
    'context' => ':channel stopped syncing — reconnect needed',
];
