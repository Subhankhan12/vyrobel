<?php

// app/Http/Controllers/Operator/EmailTemplateActionController.php — saving, switching on/off and test-sending an email template.

return [
    'template_saved' => 'Template saved — your guest emails now use it.',
    'template_active' => ':label is now active.',
    'template_off' => ':label is now off — it won’t send.',
    'no_sample_reservation' => 'No sample reservation exists yet — create a booking first.',
    'no_email_address' => 'Your account has no email address to send a test to.',
    'test_sent' => 'Test email sent to :email.',
];
