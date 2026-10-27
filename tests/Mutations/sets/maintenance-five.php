<?php

declare(strict_types=1);

// Mutation set maintenance-five — Maintenance in the five-page pass (2026-10-05, the founder: "Text is not visible completely. And note
// progress part ordered buttons design is not cool"). 🔬 The board's 945 px table ran 213 px past its card at 1,024 px; the work
// order's Note / Progress / Part ordered were inline-styled 11.5 px pills chosen by colour alone, and "Part ordered" only wrote a note.
// Run with `php tests/Mutations/run.php --set=maintenance-five`. ⚠️ The look is what a browser draws: these prove the markup, words,
// rules and the server's behaviour; the 🖥 walk proved the look and the Part ordered round trip.

$test = 'tests/Feature/MaintenancePassFiveTest.php';

return [
    [
        'id' => 'MT-M1',
        'name' => 'the board card is not a size container — its right columns slide out of sight (his report)',
        'file' => 'resources/views/components/layouts/operator.blade.php',
        'old' => '        .vy-mnt .vy-gl-card { container: vy-mt-list / inline-size; }
',
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'fits the board inside its card at every width']],
    ],
    [
        'id' => 'MT-M2',
        'name' => 'the 940 px floor comes back',
        'file' => 'resources/views/components/layouts/operator.blade.php',
        'old' => '        .vy-mnt .vy-mt-table { min-width: 0; }
',
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'fits the board inside its card at every width']],
    ],
    [
        'id' => 'MT-M3',
        'name' => 'a stacked fact loses its name',
        'file' => 'resources/views/livewire/maintenance-list.blade.php',
        'old' => '<td class="vy-mt-c-due" data-label="{{ __(\'livewire/maintenance-list.time_to_target\') }}">',
        'new' => '<td class="vy-mt-c-due">',
        'tests' => [['file' => $test, 'filter' => 'fits the board inside its card at every width']],
    ],
    [
        'id' => 'MT-M4',
        'name' => 'every assignee called "Technician" again',
        'file' => 'resources/views/livewire/maintenance-list.blade.php',
        'old' => '<th class="vy-mt-c-who">{{ __(\'livewire/maintenance-list.assigned_to\') }}</th>',
        'new' => '<th>Technician</th>',
        'tests' => [['file' => $test, 'filter' => 'calls the column "Assigned to"']],
    ],
    [
        'id' => 'MT-M5',
        'name' => 'assigning promises a task list that does not exist',
        'file' => 'resources/views/livewire/maintenance-list.blade.php',
        'old' => '<p class="vy-gl-note">{{ __(\'livewire/maintenance-list.assign_note\') }}</p>',
        'new' => '<p class="vy-gl-note">The work order appears in their task list. Nothing is sent to their phone.</p>',
        'tests' => [['file' => $test, 'filter' => 'says what assigning does']],
    ],
    [
        'id' => 'MT-M6',
        'name' => '"Busy" / "Available" guessed from a count again',
        'file' => 'resources/views/livewire/maintenance-list.blade.php',
        'old' => '                                    <span class="vy-mt-panel-sub">@if ($tech[\'urgent\'] > 0) {{ __(\'livewire/maintenance-list.tech_open_urgent\', [\'open\' => $tech[\'load\'], \'urgent\' => $tech[\'urgent\']]) }} @else {{ __(\'livewire/maintenance-list.tech_open\', [\'open\' => $tech[\'load\']]) }} @endif</span>
                                </span></span>
',
        'new' => '                                    <span class="vy-mt-panel-sub">@if ($tech[\'urgent\'] > 0) {{ __(\'livewire/maintenance-list.tech_open_urgent\', [\'open\' => $tech[\'load\'], \'urgent\' => $tech[\'urgent\']]) }} @else {{ __(\'livewire/maintenance-list.tech_open\', [\'open\' => $tech[\'load\']]) }} @endif</span>
                                </span></span>
                                <span class="vy-tag tone-{{ $tech[\'status\'] === \'busy\' ? \'risk\' : \'in-house\' }}"><span class="vy-dot"></span>{{ ucfirst($tech[\'status\']) }}</span>
',
        'tests' => [['file' => $test, 'filter' => 'never calls someone "Busy" or "Available"']],
    ],
    [
        'id' => 'MT-M7',
        'name' => 'tonight\'s rate called "revenue on hold" again',
        'file' => 'resources/views/livewire/maintenance-list.blade.php',
        'old' => '<span>{{ __(\'livewire/maintenance-list.tonight_rate\') }}</span>',
        'new' => '<span>revenue on hold</span>',
        'tests' => [['file' => $test, 'filter' => 'says the out-of-order figure is tonight’s rate']],
    ],
    [
        'id' => 'MT-M8',
        'name' => 'the drawer\'s photo lands on the full record again',
        'file' => 'app/Http/Controllers/Operator/MaintenanceActionController.php',
        'old' => '        return $this->listReturn($request, redirect()->route(\'app.maintenance.show\', $model->id))->with(\'mt_notice\', __(\'php/http/controllers/operator/maintenance_action_controller.photo_added\'));',
        'new' => '        return redirect()->route(\'app.maintenance.show\', $model->id)->with(\'mt_notice\', __(\'php/http/controllers/operator/maintenance_action_controller.photo_added\'));',
        'tests' => [['file' => $test, 'filter' => 'takes the photo types the server takes']],
    ],
    [
        'id' => 'MT-M9',
        'name' => 'Note / Progress / Part ordered are loose buttons again (his report)',
        'file' => 'resources/views/livewire/maintenance-ticket-detail.blade.php',
        'old' => '<div class="vy-wo-kinds" role="radiogroup" aria-label="{{ __(\'livewire/maintenance-ticket-detail.kind_of_update\') }}">',
        'new' => '<div class="vy-wo-kinds">',
        'tests' => [['file' => $test, 'filter' => 'makes Note / Progress / Part ordered one real choice']],
    ],
    [
        'id' => 'MT-M10',
        'name' => 'the chosen kind is not announced',
        'file' => 'resources/views/livewire/maintenance-ticket-detail.blade.php',
        'old' => ':aria-checked="kind === \'{{ $k[\'key\'] }}\' ? \'true\' : \'false\'"',
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'makes Note / Progress / Part ordered one real choice']],
    ],
    [
        'id' => 'MT-M11',
        'name' => 'the kinds shrink to 11.5 px again',
        'file' => 'resources/views/components/layouts/operator.blade.php',
        'old' => 'font: inherit; font-size: 13px; font-weight: 500; line-height: 1; cursor: pointer; white-space: nowrap; transition: background .15s, color .15s, box-shadow .15s; }',
        'new' => 'font: inherit; font-size: 11.5px; font-weight: 500; line-height: 1; cursor: pointer; white-space: nowrap; transition: background .15s, color .15s, box-shadow .15s; }',
        'tests' => [['file' => $test, 'filter' => 'makes Note / Progress / Part ordered one real choice']],
    ],
    [
        'id' => 'MT-M12',
        'name' => 'no focus ring on a kind',
        'file' => 'resources/views/components/layouts/operator.blade.php',
        'old' => '        .vy-wo-kind:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
',
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'makes Note / Progress / Part ordered one real choice']],
    ],
    [
        'id' => 'MT-M13',
        'name' => 'Post works with nothing to post',
        'file' => 'resources/views/livewire/maintenance-ticket-detail.blade.php',
        'old' => '<button type="submit" class="vy-btn vy-btn--accent" :disabled="! body.trim()">',
        'new' => '<button type="submit" class="vy-btn vy-btn--accent">',
        'tests' => [['file' => $test, 'filter' => 'keeps Post off until there is something to post']],
    ],
    [
        'id' => 'MT-M14',
        'name' => '"Part ordered" writes a note and nothing else again',
        'file' => 'app/Http/Controllers/Operator/MaintenanceActionController.php',
        'old' => '        if ($kind === \'part\' && $model->isActive()) {',
        'new' => '        if (false) {',
        'tests' => [['file' => $test, 'filter' => 'makes "Part ordered" do what it says']],
    ],
    [
        'id' => 'MT-M15',
        'name' => '"Part ordered" asks for no part',
        'file' => 'resources/views/livewire/maintenance-ticket-detail.blade.php',
        'old' => '<input id="vy-wo-part" class="vy-input" type="text" name="part_name" maxlength="160" placeholder="{{ __(\'livewire/maintenance-ticket-detail.part_placeholder\') }}">',
        'new' => '<input class="vy-input" type="text" maxlength="160" placeholder="{{ __(\'livewire/maintenance-ticket-detail.part_placeholder\') }}">',
        'tests' => [['file' => $test, 'filter' => 'makes "Part ordered" do what it says']],
    ],
    [
        'id' => 'MT-M16',
        'name' => 'four fixed columns of details again',
        'file' => 'resources/views/livewire/maintenance-ticket-detail.blade.php',
        'old' => '<div class="vy-wo-facts">',
        'new' => '<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:18px 16px;">',
        'tests' => [['file' => $test, 'filter' => 'lets the details wrap on a narrow screen']],
    ],
    [
        'id' => 'MT-M17',
        'name' => 'the details grid never wraps',
        'file' => 'resources/views/components/layouts/operator.blade.php',
        'old' => '.vy-wo-facts { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 18px 16px; }',
        'new' => '.vy-wo-facts { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px 16px; }',
        'tests' => [['file' => $test, 'filter' => 'lets the details wrap on a narrow screen']],
    ],
    [
        'id' => 'MT-M18',
        'name' => 'an open work order cannot be resolved here — stricter than the server',
        'file' => 'app/Livewire/MaintenanceTicketDetail.php',
        'old' => '                \'can_resolve\' => $canManage && in_array($status, [\'open\', \'in_progress\'], true),',
        'new' => '                \'can_resolve\' => $canManage && $status === \'in_progress\',',
        'tests' => [['file' => $test, 'filter' => 'offers Mark resolved once, and for an open work order too']],
    ],
    [
        'id' => 'MT-M19',
        'name' => 'Mark resolved printed twice again',
        'file' => 'resources/views/livewire/maintenance-ticket-detail.blade.php',
        'old' => '                        <div style="font-size:12.5px;color:#B9ABA0;margin-top:6px;">@if ($ticket[\'is_oos\']){{ __(\'livewire/maintenance-ticket-detail.hero_line_oos\', [\'priority\' => $ticket[\'priority_label\'], \'unit\' => $ticket[\'unit\']]) }}@else{{ __(\'livewire/maintenance-ticket-detail.hero_line\', [\'priority\' => $ticket[\'priority_label\'], \'unit\' => $ticket[\'unit\']]) }}@endif</div>
',
        'new' => '                        <div style="font-size:12.5px;color:#B9ABA0;margin-top:6px;">@if ($ticket[\'is_oos\']){{ __(\'livewire/maintenance-ticket-detail.hero_line_oos\', [\'priority\' => $ticket[\'priority_label\'], \'unit\' => $ticket[\'unit\']]) }}@else{{ __(\'livewire/maintenance-ticket-detail.hero_line\', [\'priority\' => $ticket[\'priority_label\'], \'unit\' => $ticket[\'unit\']]) }}@endif</div>
                        <button type="button" @click="mr = true" class="vy-btn vy-btn--accent">Mark resolved</button>
',
        'tests' => [['file' => $test, 'filter' => 'offers Mark resolved once, and for an open work order too']],
    ],
    [
        'id' => 'MT-M20',
        'name' => 'the part field\'s name only as a placeholder again',
        'file' => 'resources/views/livewire/maintenance-ticket-detail.blade.php',
        'old' => '<label class="vy-field-label" for="vy-wo-manage-part">{{ __(\'livewire/maintenance-ticket-detail.part_needed\') }}</label>',
        'new' => '<label class="vy-sr-only" for="vy-wo-manage-part">{{ __(\'livewire/maintenance-ticket-detail.part_needed\') }}</label>',
        'tests' => [['file' => $test, 'filter' => 'labels the part fields where they can be seen']],
    ],
    [
        'id' => 'MT-M21',
        'name' => 'every assignee called "Technician" on the work order',
        'file' => 'resources/views/livewire/maintenance-ticket-detail.blade.php',
        'old' => '{{ $ticket[\'assignee\'] ? __(\'livewire/maintenance-ticket-detail.assignee\') : __(\'livewire/maintenance-ticket-detail.no_one_assigned\') }}',
        'new' => '{{ $ticket[\'assignee\'] ? \'Technician\' : __(\'livewire/maintenance-ticket-detail.no_one_assigned\') }}',
        'tests' => [['file' => $test, 'filter' => 'says "Assignee", and offers "Notify" only when someone reported it']],
    ],
    [
        'id' => 'MT-M22',
        'name' => '"Notify" offered with nobody to tell',
        'file' => 'resources/views/livewire/maintenance-ticket-detail.blade.php',
        'old' => '                        @if ($ticket[\'reporter\'])
                        <input type="hidden" name="notify"',
        'new' => '                        @if (true)
                        <input type="hidden" name="notify"',
        'tests' => [['file' => $test, 'filter' => 'says "Assignee", and offers "Notify" only when someone reported it']],
    ],
    [
        'id' => 'MT-M23',
        'name' => 'the Mark resolved dialog’s "What was done" is named by nothing again (the review)',
        'file' => 'resources/views/livewire/maintenance-ticket-detail.blade.php',
        'old' => '<label class="vy-eyebrow" style="margin-bottom:7px;" for="vy-lbl-maintenance-ticket-detail-resolution">{{ __(\'livewire/maintenance-ticket-detail.what_was_done\') }}</label>',
        'new' => '<div class="vy-eyebrow" style="margin-bottom:7px;">{{ __(\'livewire/maintenance-ticket-detail.what_was_done\') }}</div>',
        'tests' => [['file' => $test, 'filter' => 'labels "What was done" in the Mark resolved dialog']],
    ],
];
