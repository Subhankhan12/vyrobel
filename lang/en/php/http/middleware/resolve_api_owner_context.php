<?php

// app/Http/Middleware/ResolveApiOwnerContext.php — the JSON refusal for a token whose owner's portal access is switched off (DEC-o).

return [
    'portal_off' => 'Portal access for this owner account is switched off, so this token can’t read anything right now. It works again as soon as the team that manages the properties switches access back on.',
];
