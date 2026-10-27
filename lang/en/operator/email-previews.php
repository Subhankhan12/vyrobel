<?php

// resources/views/operator/email-previews.blade.php — the tabbed gallery of the automated guest emails.

return [
    'title' => 'Email previews',
    'subtitle' => 'Switch between the automated guest emails',
    // shown to someone who may send a test (properties.manage)
    'intro_can_send' => 'Previews render this org’s latest booking. A test sends the real email to <strong style="color:var(--vy-ink);">your own inbox</strong> — never a guest.',
    // shown to someone who may only look
    'intro_view_only' => 'Previews render this org’s latest booking. Nothing is sent from here.',
    'test_recipient_label' => 'Test recipient (you)',
    'send_test' => 'Send test',
    'preview_frame_title' => 'Email preview',
];
