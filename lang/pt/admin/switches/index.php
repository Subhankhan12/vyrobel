<?php

// I18N-01 — pt translation of lang/en/admin/switches/index.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'title' => 'Interruptores de emergência',
    'subtitle' => 'Suspenda os novos registos, ou o início de sessão através de um fornecedor, sem publicar uma nova versão. Cada alteração fica registada na auditoria com um motivo.',
    'explainer' => 'Todos os interruptores estão <strong>ligados</strong>, a menos que alguém os desligue aqui — incluindo logo após a publicação de uma nova versão. Nenhum deles afeta esta consola. Desligar o <strong>início de sessão</strong> com um fornecedor bloqueia quem só consegue entrar através desse fornecedor, por isso esses interruptores mostram o número de contas afetadas e pedem confirmação duas vezes.',
    'state_on' => 'Ligado',
    'state_off' => 'Desligado',
    'last_changed_by' => 'Última alteração: :time UTC, por :name.',
    'last_changed' => 'Última alteração: :time UTC.',
    'never_changed' => 'Nunca alterado — ligado por predefinição.',
    'no_account_relies' => 'Neste momento, nenhuma conta depende apenas deste fornecedor — todas têm também uma palavra-passe ou outra forma de entrar.',
    'would_lock_out' => '{1} Desligar isto bloquearia <strong>:count</strong> conta — sem palavra-passe, e este fornecedor é a sua única forma de entrar.|[0,*] Desligar isto bloquearia <strong>:count</strong> contas — sem palavra-passe, e este fornecedor é a sua única forma de entrar.',
    'reason' => 'Motivo',
    'why_turn_off' => 'Porque está a desligar isto?',
    'why_turn_on' => 'Porque está a ligar isto?',
    'confirm_lockout_zero' => 'Compreendo que quem inicia sessão apenas desta forma ficará bloqueado a partir de agora, até que volte a ser ligado.',
    'confirm_lockout' => '{1} Compreendo que isto bloqueia :count conta até voltar a ser ligado.|[0,*] Compreendo que isto bloqueia :count contas até voltar a ser ligado.',
    'turn_off' => 'Desligar',
    'turn_on' => 'Ligar',
];
