<?php

declare(strict_types=1);

// Mutation set open-list-two — the second open-list pass (2026-11-11): each guard of D-INBOX-REACTION-COLLATION, D-RTL-TEXT-ARROWS, D-DAY-ELSEWHERE,
// D-TC-TYPING-SWEEP, D-UPLOAD-LIMITS (browser), D-EDGE-CHECKS, D-HOLD-STUCK (the release button — money-adjacent), A8 and D-MODAL-PAD removed one at a time.
// Run with `php tests/Mutations/run.php --set=open-list-two`.

return [
    // ── D-INBOX-REACTION-COLLATION ──
    [
        'id' => 'OL2-M1',
        'name' => 'a reaction is stored as the emoji itself again (the unicode_ci column folds two different emoji into one)',
        'file' => 'app/Services/InboxService.php',
        'old' => "            'emoji' => self::REACTION_KEYS[\$emoji],",
        'new' => "            'emoji' => \$emoji,",
        'tests' => [['file' => 'tests/Feature/InboxReactionCollationTest.php']],
    ],
    [
        'id' => 'OL2-M2',
        'name' => 'a toggle finds the person\'s first reaction whatever the emoji (the bug as it was: the second emoji removes the first)',
        'file' => 'app/Services/InboxService.php',
        'old' => '            ->first(fn (MessageReaction $r): bool => self::reactionEmoji((string) $r->emoji) === $emoji);',
        'new' => '            ->first(fn (MessageReaction $r): bool => true);',
        'tests' => [['file' => 'tests/Feature/InboxReactionCollationTest.php']],
    ],
    // ── D-RTL-TEXT-ARROWS ──
    [
        'id' => 'OL2-M3',
        'name' => 'the text arrow ignores the page\'s direction (Arabic and Persian get → again)',
        'file' => 'app/Support/I18n/Locales.php',
        'old' => "        return self::isRtl(\$code) ? '←' : '→';",
        'new' => "        return '→';",
        'tests' => [['file' => 'tests/Feature/RtlTextArrowsTest.php']],
    ],
    // ── D-DAY-ELSEWHERE ──
    [
        'id' => 'OL2-M4',
        'name' => 'a property\'s "today" is the app\'s day again (its timezone is ignored)',
        'file' => 'app/Support/Property/PropertyDay.php',
        'old' => "            \$out[(string) \$id] = BoardDate::today(is_string(\$zone) && \$zone !== '' ? \$zone : null)->toDateString();",
        'new' => '            $out[(string) $id] = BoardDate::today(null)->toDateString();',
        'tests' => [['file' => 'tests/Feature/PropertyDayElsewhereTest.php']],
    ],
    [
        'id' => 'OL2-M5',
        'name' => 'the shell\'s date pill falls back to the app\'s day on a Livewire update (the session is not asked)',
        'file' => 'app/Support/Property/PropertyDay.php',
        'old' => '            $id = $context->forProperty($user->accessiblePropertyIds(), is_string($selection) ? $selection : null, fn (): ?string => $context->id());',
        'new' => '            $id = null;',
        'tests' => [['file' => 'tests/Feature/PropertyDayElsewhereTest.php', 'filter' => 'Livewire UPDATE']],
    ],
    [
        'id' => 'OL2-M6',
        'name' => '"Assign all" stops putting a waiting guest\'s room first (the first sort key compares nothing)',
        'file' => 'app/Http/Controllers/HousekeepingActionController.php',
        'old' => '                fn (HousekeepingTask $a, HousekeepingTask $b): int => ($waiting($a) ? 0 : 1) <=> ($waiting($b) ? 0 : 1),',
        'new' => '                fn (HousekeepingTask $a, HousekeepingTask $b): int => 0,',
        'tests' => [['file' => 'tests/Feature/PropertyDayElsewhereTest.php', 'filter' => 'Assign all']],
    ],
    // ── D-TC-TYPING-SWEEP ──
    [
        'id' => 'OL2-M7',
        'name' => 'the typing sweep deletes the LIVE rows and keeps the dead ones (the cutoff is inverted)',
        'file' => 'app/Console/Commands/PruneTeamTyping.php',
        'old' => "->where('typed_at', '<', now()->subMinutes(self::KEEP_MINUTES))->delete();",
        'new' => "->where('typed_at', '>', now()->subMinutes(self::KEEP_MINUTES))->delete();",
        'tests' => [['file' => 'tests/Feature/PruneTeamTypingTest.php']],
    ],
    // ── D-UPLOAD-LIMITS (browser) ──
    [
        'id' => 'OL2-M8',
        'name' => 'the owner reply composer\'s file input is no longer checked in the browser',
        'file' => 'resources/views/livewire/owner/messages.blade.php',
        'old' => 'x-ref="file" style="display:none" @change="fileError = vyOwnerFileCheck($refs.file, cfg); if (fileError) $refs.file.value = \'\'; files = $refs.file.files.length">',
        'new' => 'x-ref="file" style="display:none" @change="files = $refs.file.files.length">',
        'tests' => [['file' => 'tests/Feature/OwnerComposerPrecheckTest.php']],
    ],
    // ── D-EDGE-CHECKS ──
    [
        'id' => 'OL2-M9',
        'name' => 'a probe the server proves fine is still a FAIL (the second look never downgrades the edge)',
        'file' => 'ops/public-checks.sh',
        'old' => '    if "$pred" "$sst" "$sloc" "$sbody"; then verdict CNM "$name" "this Mac: $detail · the server, through ${BASE#*://}: HTTP $sst — the EDGE, not the site"',
        'new' => '    if "$pred" "$sst" "$sloc" "$sbody"; then verdict FAIL "$name" "this Mac: $detail · the server, through ${BASE#*://}: HTTP $sst — the EDGE, not the site"',
        'tests' => [['file' => 'tests/Feature/PublicChecksSecondLookTest.php']],
    ],
    [
        'id' => 'OL2-M10',
        'name' => 'a probe the server COULD NOT be asked about is downgraded to COULD NOT MEASURE (a FAIL must stand without evidence)',
        'file' => 'ops/public-checks.sh',
        'old' => '    if [ "$r" = UNAVAILABLE ]; then verdict FAIL "$name" "$detail — second look unavailable (the server could not be asked)"; return; fi',
        'new' => '    if [ "$r" = UNAVAILABLE ]; then verdict CNM "$name" "$detail — second look unavailable (the server could not be asked)"; return; fi',
        'tests' => [['file' => 'tests/Feature/PublicChecksSecondLookTest.php']],
    ],
    // ── D-HOLD-STUCK (the release button) ──
    [
        'id' => 'OL2-M11',
        'name' => 'a hold that is not open any more is released anyway (the status guard under the lock is gone)',
        'file' => 'app/Services/FolioService.php',
        'old' => "            if ((string) \$locked->status !== 'authorized') {\n                throw new RuntimeException(__('php/services/folio_service.hold_not_open'));",
        'new' => "            if (false) {\n                throw new RuntimeException(__('php/services/folio_service.hold_not_open'));",
        'tests' => [['file' => 'tests/Feature/CardHoldReleaseTest.php']],
    ],
    [
        'id' => 'OL2-M12',
        'name' => 'a processor that refuses to release is treated as released (the hold is marked released while the card is still held)',
        'file' => 'app/Services/FolioService.php',
        'old' => '            if (! $result->successful) {',
        'new' => '            if (false) {',
        'tests' => [['file' => 'tests/Feature/CardHoldReleaseTest.php', 'filter' => 'the processor is asked to release it']],
    ],
    [
        'id' => 'OL2-M13',
        'name' => 'a hold of ANOTHER reservation (or organisation) can be released through this one',
        'file' => 'app/Http/Controllers/ReservationFolioActionController.php',
        'old' => "            ->whereHas('payment', fn (\$q) => \$q->where('reservation_id', \$model->id)->where('type', 'deposit_hold'))\n",
        'new' => '',
        'tests' => [['file' => 'tests/Feature/CardHoldReleaseTest.php']],
    ],
    [
        'id' => 'OL2-M14',
        'name' => 'the release route is open to anyone signed in (the payments.refund gate is gone)',
        'file' => 'routes/web.php',
        'old' => "        ->middleware('can:payments.refund')->whereUlid(['reservation', 'authorization'])->name('reservations.hold-release');",
        'new' => "        ->whereUlid(['reservation', 'authorization'])->name('reservations.hold-release');",
        'tests' => [['file' => 'tests/Feature/CardHoldReleaseTest.php', 'filter' => 'gated like a refund']],
    ],
    [
        'id' => 'OL2-M15',
        'name' => 'the folio keeps offering "Release hold" to a role that may not refund (the view stops checking the permission)',
        'file' => 'resources/views/livewire/reservation-detail.blade.php',
        'old' => "@if ((\$line['release_authorization_id'] ?? null) && auth()->user()?->can('payments.refund'))",
        'new' => "@if ((\$line['release_authorization_id'] ?? null))",
        'tests' => [['file' => 'tests/Feature/CardHoldReleaseTest.php', 'filter' => 'gated like a refund']],
    ],
    // ── A8 ──
    [
        'id' => 'OL2-M16',
        'name' => 'a localhost address reaches structured data',
        'file' => 'app/Support/Marketing/StructuredData.php',
        'old' => " || \$host === 'localhost' ||",
        'new' => ' ||',
        'tests' => [['file' => 'tests/Feature/MarketingStructuredDataTest.php']],
    ],
    [
        'id' => 'OL2-M17',
        'name' => 'an http (not https) address is accepted as the site',
        'file' => 'app/Support/Marketing/StructuredData.php',
        'old' => "if ((\$parts['scheme'] ?? '') !== 'https' || ",
        'new' => 'if (',
        'tests' => [['file' => 'tests/Feature/MarketingStructuredDataTest.php']],
    ],
    // ── D-MODAL-PAD · the class collision found in the browser ──
    [
        'id' => 'OL2-M18',
        'name' => 'the Block Calendar\'s "remove?" span takes the gradient button\'s class again',
        'file' => 'resources/views/livewire/owner/block-calendar.blade.php',
        'old' => '        .vy-cal-relconfirm { display:inline-flex; gap:6px; }',
        'new' => '        .vy-cal-confirm { display:inline-flex; gap:6px; }',
        'tests' => [['file' => 'tests/Feature/ClassDefinedTwiceWithDisplayTest.php']],
    ],
    [
        'id' => 'OL2-M19',
        'name' => 'sectioned dialogs stack their own padding on the 26px again',
        'file' => 'resources/views/components/layouts/operator.blade.php',
        'old' => '        .vy-modal:has(.vy-modal-head) { padding: 0; }',
        'new' => '',
        'tests' => [['file' => 'tests/Feature/ModalPaddingTest.php']],
    ],
];
