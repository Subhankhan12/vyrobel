<?php

// I18N-01 — bg translation of lang/en/php/http/controllers/admin/admin_user_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'only_pending_reissued' => 'Само чакаща покана може да бъде издадена повторно.',
    'last_owner_role' => 'Това е последният активен администратор с роля „Собственик“. Първо дайте тази роля на друг човек.',
    'role_changed' => ':name вече е с роля „:role“.',
    'cannot_revoke_self' => 'Не можете да отнемете собствения си достъп. Помолете друг администратор с роля „Собственик“.',
    'last_owner_revoke' => 'Това е последният активен администратор с роля „Собственик“ и достъпът му не може да бъде отнет.',
    'access_revoked' => '{1}Достъпът на :name е отнет; прекратена е :count сесия.|[0,*]Достъпът на :name е отнет; прекратени са :count сесии.',
    'only_revoked_restored' => 'Само администратор с отнет достъп може да бъде възстановен.',
    'access_restored' => 'Достъпът на :name е възстановен.',
    'ended_own_other_sessions' => '{1}Прекратена е :count от другите ви сесии – тази остава активна.|[0,*]Прекратени са :count от другите ви сесии – тази остава активна.',
    'ended_sessions_for' => '{1}Прекратена е :count сесия на :name.|[0,*]Прекратени са :count сесии на :name.',
];
