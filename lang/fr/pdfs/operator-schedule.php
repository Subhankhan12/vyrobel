<?php

// I18N-01 — fr translation of lang/en/pdfs/operator-schedule.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'eyebrow' => 'Planning d’équipe',
    'week_of' => 'Semaine du :week',
    'doc_title' => 'Planning hebdomadaire',
    'doc_sub' => ':property · semaine du :week · édition : :generated',
    'published' => 'Publication : :date',
    'published_by' => 'Publication : :date, par :name',
    'not_published' => 'Pas encore publié — cette semaine n’a pas été envoyée à l’équipe.',
    'team' => 'Équipe',
    'shifts' => 'Horaires',
    'hours' => 'Heures',
    'open' => 'À pourvoir',
    'conflicts' => 'Conflits',
    'no_staff' => 'Aucun membre du personnel au planning pour la semaine choisie.',
    'team_member' => 'Membre de l’équipe',
    'cell_conflict' => ':shift / Congé !',
    'cell_leave' => 'Congé',
    'legend' => '<strong>Lecture de ce planning :</strong> une case indique le nom de l’horaire et ses heures · <strong>Congé</strong> = absence approuvée · <strong>—</strong> = hors planning · <strong>« / Congé ! »</strong> = un horaire sur un jour de congé approuvé. Chaque état est écrit en toutes lettres : ce planning s’imprime donc correctement en noir et blanc.',
    'shift_hours' => ':early :early_hours · :day :day_hours · :late :late_hours · les heures de chaque horaire figurent dans chaque case.',
    'conflict_count' => '{1} <strong style="color:#B0322A">:count conflit</strong> — un horaire sur un jour de congé approuvé.|[0,*] <strong style="color:#B0322A">:count conflits</strong> — des horaires sur des jours de congé approuvé.',
    'open_shifts' => 'Horaires à pourvoir',
    'all_assigned' => 'Tous les horaires de ce planning sont attribués — aucun créneau à pourvoir cette semaine.',
    'any_department' => 'Tout service',
    'time_off' => 'Congés',
    'foot_generated' => ':property · édition : :generated',
    'foot_week' => 'Planning de la semaine · :week',
    'snapshot_note' => 'Ce planning est un instantané du planning d’équipe tel qu’il était au moment de son édition (:generated) — toute modification ultérieure n’apparaîtra pas sur cette impression. Les demandes d’échange d’horaire passent par l’application Vyrobel ou par la réception.',
];
