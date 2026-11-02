<?php

// I18N-01 — pt_BR translation of lang/en/admin/switches/index.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'title' => 'Interruptores de emergência',
    'subtitle' => 'Suspenda novos cadastros, ou o login por um provedor, sem publicar uma nova versão. Cada alteração é auditada com um motivo.',
    'explainer' => 'Todo interruptor fica <strong>ligado</strong>, a menos que alguém o desligue aqui — inclusive logo depois de publicar uma nova versão. Nenhum deles afeta este console. Desligar o <strong>login</strong> com um provedor bloqueia quem só entra por esse provedor, por isso esses interruptores mostram o número de contas afetadas e pedem confirmação duas vezes.',
    'state_on' => 'Ligado',
    'state_off' => 'Desligado',
    'last_changed_by' => 'Última alteração: :time UTC, por :name.',
    'last_changed' => 'Última alteração: :time UTC.',
    'never_changed' => 'Nunca alterado — ligado por padrão.',
    'no_account_relies' => 'No momento, nenhuma conta depende só deste provedor — todas têm também uma senha ou outra forma de entrar.',
    'would_lock_out' => '{1} Desligar isto bloquearia <strong>:count</strong> conta — sem senha, e este provedor é a única forma de entrar.|[0,*] Desligar isto bloquearia <strong>:count</strong> contas — sem senha, e este provedor é a única forma de entrar.',
    'reason' => 'Motivo',
    'why_turn_off' => 'Por que você está desligando isto?',
    'why_turn_on' => 'Por que você está ligando isto?',
    'confirm_lockout_zero' => 'Entendo que quem entra apenas desta forma ficará bloqueado a partir de agora, até que o interruptor seja religado.',
    'confirm_lockout' => '{1} Entendo que isto bloqueia :count conta até ser religado.|[0,*] Entendo que isto bloqueia :count contas até ser religado.',
    'turn_off' => 'Desligar',
    'turn_on' => 'Ligar',
];
