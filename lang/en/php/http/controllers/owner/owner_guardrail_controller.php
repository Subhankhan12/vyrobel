<?php

// app/Http/Controllers/Owner/OwnerGuardrailController.php — the owner's rate guardrails (floor and ceiling).

return [
    'floor_above_ceiling' => 'The floor can’t be higher than the ceiling.',
    'saved' => 'Guardrails saved — your manager prices within these limits.',
    'invalid_floor' => 'Enter a floor above zero, e.g. 120 or 120.50 — or leave it blank for no limit.',
    'invalid_ceiling' => 'Enter a ceiling above zero, e.g. 120 or 120.50 — or leave it blank for no limit.',
    // fallback for any other bound field (none today)
    'invalid_bound' => 'Enter a value above zero, e.g. 120 or 120.50 — or leave it blank for no limit.',
];
