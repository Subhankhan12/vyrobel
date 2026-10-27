<?php

// resources/views/emails/maintenance-cost-report.blade.php — the maintenance costs report email.

return [
    'title' => 'Your maintenance costs report',
    'preheader' => 'Your maintenance costs report is attached as a PDF.',
    'heading' => 'Maintenance costs',
    'body' => 'Your maintenance costs report is attached as a PDF — total spend, spend by category, and the billable work orders (labour + parts).',
    'generated_from' => 'Generated from your real resolved work orders.',
];
