<?php

// app/Exceptions/Owner/PayoutsPausedException.php — the Payout Run prints this after "Couldn’t pay — " / "Couldn’t create the payout — ".

return [
    'paused' => 'payouts to :owner are paused — resume them on the owner’s page first.',
];
