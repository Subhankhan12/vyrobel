<?php

// resources/views/livewire/operator/operator-email-editor.blade.php — the editor for one customisable guest email.

return [
    'back_to_templates' => '← Templates',
    'status_active' => 'Active',
    'status_off' => 'Off',
    'sub_line' => ':trigger · via :channel',
    'sub_line_edited' => ':trigger · via :channel · :last_edited',
    'send_test' => 'Send me a test',
    'save_template' => 'Save template',
    'subject_line' => 'Subject line',
    'insert_variable' => 'Insert a variable',
    // {first_name} is a merge variable and stays exactly as written
    'variables_help' => 'These fill in from the real booking — <span style="color: var(--vy-muted);">{first_name}</span> becomes the guest’s name, and so on.',
    'heading' => 'Heading',
    'message' => 'Message',
    'button_label' => 'Button label',
    'sections' => 'Sections',
    'sections_help' => 'Turn a section off and it won’t appear in the sent email.',
    'test_send_help' => '“Send me a test” delivers the SAVED version to your own address — save your changes first.',
    'live_preview' => 'Live preview',
    'preview_note' => 'Real layout · sample data',
    'preview_subject' => 'Subject:',
    'preview_frame_title' => 'Email preview',
];
