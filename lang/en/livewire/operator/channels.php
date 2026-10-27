<?php

// resources/views/livewire/operator/channels.blade.php — the channel manager (OTA connections; sync is account-gated).

return [
    'page_title' => 'Channels',
    'heading' => 'Channels',
    'connect_channel_button' => 'Connect channel',
    'sync_setup_eyebrow' => 'Channel sync — setup required',
    // the Channex / Direct names stay inside the sentence so each language places them where its grammar needs
    'sync_setup_body' => 'Live two-way sync with the OTAs runs through <strong>Channex</strong>, our channel-manager partner. Once your Channex account is connected, Vyrobel maps your listings, pushes your rates & availability, and imports OTA bookings automatically — two-way sync every few minutes. Until then, your <strong>Direct</strong> channel (your own storefront) is live and taking bookings.',
    // stats row
    'ota_bookings_month' => 'OTA bookings · :month',
    'of_total' => 'of :total total',
    'direct_share' => 'Direct share',
    'direct_this_month' => ':bookings direct this month',
    'sync_status' => 'Sync status',
    'n_disconnected' => ':channels disconnected',
    'all_connected' => 'all connected',
    // connected channels
    'connected_channels' => 'Connected channels',
    'direct_name' => 'Direct',
    'live' => 'Live',
    'direct_sub' => 'Your own storefront & booking engine · no commission',
    'bookings_month' => 'bookings · :month',
    'status_connected' => 'Connected',
    'status_action_needed' => 'Action needed',
    'last_sync' => 'Last sync :time',
    'mapped_ready' => 'Mapped & ready — live sync activates with your Channex account',
    'connection_needs_attention' => 'Connection needs attention',
    'error_reconnect' => ':error · reconnect via your Channex account',
    'not_connected' => 'Not connected — connect via your Channex account to sync',
    'bookings' => 'bookings',
    'bookings_commission' => 'bookings · :pct comm.',
    'no_ota_channels' => 'No OTA channels connected yet — connect one below to reach the marketplaces.',
    // connect a channel
    'connect_a_channel' => 'Connect a channel',
    'connect_intro' => 'Pick a marketplace to add. Vyrobel maps your listings, pushes rates & availability, and imports bookings — two-way sync every few minutes, all through Channex.',
    'commission_short' => ':pct comm.',
    'setup_required' => 'Setup required',
    'setup_required_body' => 'Connecting a marketplace needs your Channex partner account + API credentials. Add them in Channex, then each channel maps and syncs here — Vyrobel never fabricates a sync it can’t run.',
    // right rail
    'booking_mix_month' => 'Booking mix · :month',
    'no_bookings_month' => 'No bookings yet this month.',
    'booking_mix_note' => 'Every direct booking keeps the OTA commission in house. OTA figures populate once channel sync is live.',
    'sync_activity' => 'Sync activity',
    'channels_configured' => 'Channels configured',
    'sync_activity_note' => 'Live sync events (rate pushes, booking imports) appear here once your Channex connection is active.',
    'no_sync_activity' => 'No sync activity yet — connect a channel to begin.',
];
