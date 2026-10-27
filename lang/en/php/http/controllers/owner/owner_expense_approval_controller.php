<?php

// app/Http/Controllers/Owner/OwnerExpenseApprovalController.php — approving or querying an expense. The query's subject and
// default message are saved to the conversation and stay English.

return [
    'already_decided' => 'That expense was already decided.',
    'approved' => 'Expense approved — it’ll be deducted from your payout.',
    'query_sent' => 'Query sent to your management team — the expense won’t be charged until it’s resolved.',
];
