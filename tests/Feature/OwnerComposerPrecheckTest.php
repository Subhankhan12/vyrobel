<?php

declare(strict_types=1);

use App\Services\InboxService;
use App\Support\Messaging\ChatFiles;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

// ⚠️ D-UPLOAD-LIMITS · the browser's half (2026-11-11, his go). The Inbox and the team chat already check a file against this server's ceilings
// before sending; the owner portal's composer did not, so a file past PHP's request ceiling arrived as nothing and only a 419 could answer.
// Both of its forms (start a conversation, reply) now carry the ceilings and the check. (The behaviour itself is a browser one — proven
// in a browser; this pins that the page carries the REAL numbers and wires every file input to the check.)

it('⚠️ both owner composers check a chosen file in the browser, against the server\'s real ceilings', function () {
    $s = seedScenario(2);
    $a = inboxOwner($s, 'Amara Okafor', 0);
    $marco = operatorUser($s, ['name' => 'Marco Reyes'], 'Hotel Manager');
    $thread = ownerThread($a['owner']);
    app(InboxService::class)->sendOutbound($thread, 'All set.', $marco);

    $limits = ChatFiles::limits();
    $reply = $this->actingAs($a['user'])->get('/owner/messages?c='.$thread->id)->assertOk()->getContent();

    // the check exists once, and every file input in the composer calls it
    expect($reply)->toContain('window.vyOwnerFileCheck');
    preg_match_all('/<input type="file" name="attachments\[\]"[^>]*>/', $reply, $inputs);
    expect($inputs[0])->not->toBeEmpty();
    foreach ($inputs[0] as $input) {
        expect($input)->toContain('vyOwnerFileCheck($refs.file, cfg)');
    }

    // the numbers are this server's own — never a literal typed in the page
    // (`@js` writes the quotes of its JSON as \u0022 so it is safe inside an attribute)
    expect($reply)->toContain('cfg:')->and($reply)->toContain('\u0022max\u0022:'.ChatFiles::MAX_FILES)
        ->and($reply)->toContain('\u0022mb\u0022:'.$limits['mb'])
        ->and($reply)->toContain('\u0022post\u0022:'.$limits['post_bytes']);
    expect($reply)->toContain('x-text="fileError"');

    // and the start-a-conversation composer carries it too
    $start = $this->actingAs($a['user'])->get('/owner/messages?compose='.$marco->id)->assertOk()->getContent();
    expect($start)->toContain('vyOwnerFileCheck($refs.file, cfg)')->and($start)->toContain('x-text="fileError"');
});

it('CONTROL: the page\'s refusal sentences are the Inbox\'s own, with the real megabytes', function () {
    $s = seedScenario(2);
    $a = inboxOwner($s, 'Amara Okafor', 0);
    $thread = ownerThread($a['owner']);

    $html = $this->actingAs($a['user'])->get('/owner/messages?c='.$thread->id)->assertOk()->getContent();

    expect($html)->toContain(':name is over '.ChatFiles::limits()['mb'].' MB.')
        ->and($html)->toContain('Send up to '.ChatFiles::MAX_FILES.' files at a time.');
});
