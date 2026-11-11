<?php

declare(strict_types=1);

use App\Models\MessageReaction;
use App\Services\InboxService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(DatabaseTransactions::class);

// ⚠️ D-INBOX-REACTION-COLLATION (found 2026-11-11 by the team-chat build, 🔬 measured on MySQL 8; fixed 2026-11-11, his go).
// `message_reactions.emoji` is utf8mb4_unicode_ci, under which every emoji outside the basic plane compares EQUAL to every other: a second,
// DIFFERENT reaction by the same person found the first one and took it off, and the unique index would have refused it. A schema change is
// what `ops/release.sh` refuses (additive migrations only), so the fix is in the VALUE: each reaction is stored as a plain ASCII key, and
// "the same emoji" is decided in PHP, never by a database `=`. Rows an older release wrote (the emoji itself) are still read and still toggled.

function irc_react(): array
{
    $s = seedScenario(2);
    $a = inboxOwner($s, 'Amara Okafor', 0);
    $marco = operatorUser($s, ['name' => 'Marco Reyes'], 'Hotel Manager');
    $msg = app(InboxService::class)->sendOutbound(ownerThread($a['owner']), 'All set.', $marco);

    return [$s, $a['user'], $marco, $msg, app(InboxService::class)];
}

it('⚠️⚠️ keeps two different reactions by one person — the second emoji no longer finds the first and removes it', function () {
    [, $owner, , $msg, $inbox] = irc_react();

    // two emojis OUTSIDE the basic plane — the ones a "unicode_ci" collation cannot tell apart
    expect($inbox->toggleReaction($msg, $owner, '👍'))->toBeTrue();
    expect($inbox->toggleReaction($msg, $owner, '🎉'))->toBeTrue();   // RED before the fix: found 👍 and removed it (false)

    $stored = MessageReaction::query()->where('message_id', $msg->id)->where('user_id', $owner->id)->pluck('emoji')->all();
    expect($stored)->toHaveCount(2)
        ->and(array_map(fn (string $v): string => InboxService::reactionEmoji($v), $stored))->toContain('👍')->toContain('🎉');

    // and each still toggles off on its own, leaving the other
    expect($inbox->toggleReaction($msg, $owner, '👍'))->toBeFalse();
    expect(array_map(fn (string $v): string => InboxService::reactionEmoji($v), MessageReaction::query()->where('message_id', $msg->id)->pluck('emoji')->all()))->toBe(['🎉']);
});

it('stores a plain ASCII key, never the emoji — so no collation, on any engine, can fold two reactions into one', function () {
    [, $owner, , $msg, $inbox] = irc_react();

    foreach (InboxService::REACTIONS as $emoji) {
        $inbox->toggleReaction($msg, $owner, $emoji);
    }

    $stored = MessageReaction::query()->where('message_id', $msg->id)->pluck('emoji')->all();
    expect($stored)->toHaveCount(count(InboxService::REACTIONS));
    foreach ($stored as $value) {
        expect($value)->toMatch('/^[a-z_]+$/');   // ASCII, lower case: distinct under a case-insensitive collation too
    }
    expect(array_unique(array_map('strtolower', $stored)))->toHaveCount(count(InboxService::REACTIONS))
        ->and(array_keys(InboxService::REACTION_KEYS))->toBe(InboxService::REACTIONS)   // every allowed emoji has a key, in order
        ->and(array_unique(array_values(InboxService::REACTION_KEYS)))->toHaveCount(count(InboxService::REACTIONS));
});

it('⚠️ a row an older release wrote (the emoji itself) is still read and still toggled — and does not swallow a different emoji', function () {
    [$s, $owner, , $msg, $inbox] = irc_react();

    // exactly what the previous code stored
    DB::table('message_reactions')->insert([
        'id' => (string) Str::ulid(), 'organization_id' => $s['org']->id, 'message_id' => $msg->id, 'user_id' => $owner->id,
        'emoji' => '👍', 'created_at' => now(), 'updated_at' => now(),
    ]);

    expect($inbox->toggleReaction($msg, $owner, '🎉'))->toBeTrue();    // a different emoji is NOT mistaken for the old 👍
    expect($inbox->toggleReaction($msg, $owner, '👍'))->toBeFalse();   // the old 👍 is found and taken off
    expect(array_map(fn (string $v): string => InboxService::reactionEmoji($v), MessageReaction::query()->where('message_id', $msg->id)->pluck('emoji')->all()))->toBe(['🎉']);

    // the reading side: a key and an older emoji both stand for the emoji they were
    expect(InboxService::reactionEmoji('party'))->toBe('🎉')->and(InboxService::reactionEmoji('🎉'))->toBe('🎉')->and(InboxService::reactionEmoji('anything else'))->toBe('anything else');
});

it('⚠️ the owner\'s page counts an older row and a new one as the SAME emoji — and a chip posts the EMOJI back, never the stored key', function () {
    [$s, $owner, , $msg, $inbox] = irc_react();
    $second = inboxOwner($s, 'Bem Adeyemi', 1);
    DB::table('message_reactions')->insert([
        'id' => (string) Str::ulid(), 'organization_id' => $s['org']->id, 'message_id' => $msg->id, 'user_id' => $second['user']->id,
        'emoji' => '👍', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $inbox->toggleReaction($msg, $owner, '👍');
    $thread = (string) $msg->thread_id;

    $html = test()->actingAs($owner)->get('/owner/messages?c='.$thread)->assertOk()->getContent();

    expect($html)->toContain('👍 2</button>')                       // both rows are one 👍 with a count of two
        ->and($html)->toContain('<input type="hidden" name="emoji" value="👍">')
        ->and($html)->not->toContain('value="thumbs_up"');          // the key stays inside the database

    // tapping that chip (the page posts the emoji) takes the viewer's own reaction off and leaves the other person's
    test()->actingAs($owner)->post('/owner/messages/'.$thread.'/react', ['message_id' => $msg->id, 'emoji' => '👍'])->assertRedirect();
    expect(MessageReaction::query()->where('message_id', $msg->id)->count())->toBe(1)
        ->and(MessageReaction::query()->where('message_id', $msg->id)->value('user_id'))->toBe((string) $second['user']->id);
});
