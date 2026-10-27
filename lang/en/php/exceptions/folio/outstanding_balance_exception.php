<?php

// app/Exceptions/Folio/OutstandingBalanceException.php — the checkout refusal the front desk reads while money is owed.

return [
    'message' => ':amount is still owed on this folio. Take the payment, bill it to a company account if they settle later, or post an adjustment or void the charge if it should not stand — then check out.',
];
