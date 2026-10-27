<?php

// resources/views/livewire/owner/documents.blade.php — the owner portal Documents library.

return [
    'title' => 'Documents',
    'subtitle' => 'Statements, remittances & tax summaries',
    'document_count' => '{1} :count document|[0,*] :count documents',
    'since' => 'since :date',
    'full_archive' => 'full archive ›',
    'empty_intro' => 'Every document your statements and payouts produce is collected here.',
    'all_years' => 'All years',
    'net' => 'Net',
    'pdf' => 'PDF',
    'no_documents' => 'No documents yet.',
    'no_documents_body' => 'Your statement, booking summary and remittance advice appear here as soon as a statement is finalized.',
    'annual_tax_heading' => ':year annual tax summary',
    'annual_tax_sub' => '{1} :count month (:span) · net :net · one PDF for your accountant|[0,*] :count months (:span) · net :net · one PDF for your accountant',
    'annual_tax_download' => 'Download the :year annual tax summary as a PDF',
];
