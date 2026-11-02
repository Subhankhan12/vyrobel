<?php

// I18N-01 — ru translation of lang/en/php/http/controllers/auth/account_security_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'login_approval_unavailable' => 'Разрешение входа пока нельзя включить: Vyrobel не отправляет письма, поэтому ссылка для разрешения до вас не дойдёт и новое устройство не сможет войти.',
    'login_approval_on' => 'Разрешение входа включено — новое устройство ждёт, пока вы его разрешите.',
    'login_approval_off' => 'Разрешение входа выключено.',
    'approval_no_longer_waiting' => 'Этот вход больше не ждёт решения — оно уже принято или срок истёк.',
    'approval_approved' => 'Разрешено — это устройство теперь может завершить вход.',
    'approval_denied' => 'Запрещено — этот вход не может продолжиться. Если это были не вы, смените пароль.',
    'login_alerts_on' => 'Оповещения о входе включены — вход с нового устройства появится в ваших уведомлениях.',
    'login_alerts_off' => 'Оповещения о входе выключены.',
    'session_timeout_off' => 'Тайм-аут сеанса выключен — как и раньше, при бездействии сеанс завершается через :duration.',
    'session_timeout_on' => 'При бездействии сеанс завершится через :duration.',
];
