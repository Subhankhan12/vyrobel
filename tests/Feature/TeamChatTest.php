<?php

declare(strict_types=1);

use App\Models\BreakGlassSession;
use App\Models\Media;
use App\Models\Membership;
use App\Models\TeamConversation;
use App\Models\TeamMessage;
use App\Notifications\Operator\TeamMessageNotification;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use PragmaRX\Google2FA\Google2FA;
use Symfony\Component\Uid\Ulid;

uses(DatabaseTransactions::class);

// ⚠️⚠️ TEAM CHAT, 2026-10-06 (the founder: "messages must be owner and the employees as well … toggle for client and employees
// … attachment … picture … voice … notification icon … notification sound … Ajax based runtime"). `docs/TEAM-CHAT-DESIGN.md`.
// 📖 Read first: no staff-to-staff messaging existed; the Inbox is `guests.view`, which Housekeeping, Maintenance, Accountant and
// Revenue Manager do not hold; nothing polled; the microphone was off on every page. Each test failed first against the code
// as it stood.

/** A workspace with three staff: the account owner (Max), a housekeeper (Anna) and a front-desk agent (Ben). */
function tcWorld(): array
{
    $s = seedScenario(1);
    $s['max'] = operatorUser($s, ['name' => 'Max Brown'], 'Property Owner');
    $s['anna'] = operatorUser($s, ['name' => 'Anna Lee'], 'Housekeeping');
    $s['ben'] = operatorUser($s, ['name' => 'Ben Ortiz'], 'Front Desk');

    return $s;
}

/** Start a conversation through the real endpoint, as $from, with these teammates. */
function tcStart(array $s, string $from, array $with, ?string $title = null): string
{
    $members = array_map(fn (string $k): string => (string) $s[$k]->id, $with);
    $response = test()->actingAs($s[$from])->postJson(route('app.inbox.team.start'), array_filter(['members' => $members, 'title' => $title]))
        ->assertSuccessful();

    return (string) $response->json('conversation.id');
}

/** Send through the real endpoint. */
function tcSend(array $s, string $from, string $conversationId, array $data): TestResponse
{
    return test()->actingAs($s[$from])->post(route('app.inbox.team.send', $conversationId), $data, ['Accept' => 'application/json']);
}

/**
 * A REAL upload, judged by its bytes as on a server. ⚠️ `UploadedFile::fake()` takes its type from the NAME, so a fake
 * `.vbs` is refused before the code under test runs — a test of "the bytes decide" needs a real file.
 */
function tcRealFile(string $name, string $bytes): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'vy-tc');
    file_put_contents($path, $bytes);

    return new UploadedFile($path, $name, null, null, true);
}

/** A tiny WebM header (EBML + DocType "webm") — what Chrome's MediaRecorder writes; PHP's finfo reads it as video/webm. */
function tcWebm(): string
{
    return "\x1A\x45\xDF\xA3\x9F\x42\x86\x81\x01\x42\xF7\x81\x01\x42\xF2\x81\x04\x42\xF3\x81\x08\x42\x82\x84webm\x42\x87\x81\x04\x42\x85\x81\x02"
        .str_repeat("\x00", 2048);
}

// ── who can use it ────────────────────────────────────────────────────────────────────────────────────────────────

it('⚠️⚠️ opens Team for every back-office role — a housekeeper included, who still cannot open Guests', function () {
    $s = tcWorld();

    $this->actingAs($s['anna'])->get(route('app.inbox.team'))->assertOk()
        ->assertSee('id="vy-team-chat"', false)
        ->assertSee('Max Brown')->assertSee('Ben Ortiz');
    $this->actingAs($s['anna'])->get(route('app.inbox'))->assertForbidden();   // #30 — the Guests gate is unchanged
});

it('⚠️ shows the Guests | Team toggle to someone who holds guests.view, on both pages — Team alone to someone who does not', function () {
    $s = tcWorld();

    foreach ([route('app.inbox'), route('app.inbox.team')] as $page) {
        $html = (string) $this->actingAs($s['ben'])->get($page)->assertOk()->getContent();
        expect(preg_match('~<nav class="vy-msg-toggle"[^>]*>(.*?)</nav>~s', $html, $m))->toBe(1)
            ->and($m[1])->toContain('href="'.route('app.inbox').'"')->toContain('href="'.route('app.inbox.team').'"');
    }

    $housekeeper = (string) $this->actingAs($s['anna'])->get(route('app.inbox.team'))->getContent();
    expect($housekeeper)->not->toContain('class="vy-msg-toggle"');
});

it('⚠️ leads a housekeeper\'s sidebar Messages to Team — it was hidden from four of the eleven back-office roles', function () {
    $s = tcWorld();

    $html = (string) $this->actingAs($s['anna'])->get(route('app.dashboard'))->getContent();
    expect($html)->toMatch('~<a [^>]*href="'.preg_quote(route('app.inbox.team'), '~').'"[^>]*>.*?Messages~s');
});

// ── starting a conversation ──────────────────────────────────────────────────────────────────────────────────────

it('⚠️⚠️ keeps one direct conversation per pair — starting it again opens the same one, from either side', function () {
    $s = tcWorld();

    $first = tcStart($s, 'max', ['anna']);
    $again = tcStart($s, 'max', ['anna']);
    $fromAnna = tcStart($s, 'anna', ['max']);

    expect($again)->toBe($first)->and($fromAnna)->toBe($first)
        ->and(DB::table('team_conversations')->where('id', $first)->value('kind'))->toBe('direct')
        ->and(DB::table('team_conversation_members')->where('conversation_id', $first)->count())->toBe(2);
});

it('starts a group with two or more teammates and keeps its name', function () {
    $s = tcWorld();

    $id = tcStart($s, 'max', ['anna', 'ben'], 'Morning shift');

    expect(DB::table('team_conversations')->where('id', $id)->first(['kind', 'title']))
        ->kind->toBe('group')->title->toBe('Morning shift')
        ->and(DB::table('team_conversation_members')->where('conversation_id', $id)->count())->toBe(3);
});

it('⚠️⚠️ refuses a teammate who is not on this workspace\'s back-office staff — another workspace, or a unit owner', function () {
    $s = tcWorld();
    $owner = ownerRoleUser($s);
    $other = seedScenario(1);
    $stranger = operatorUser($other, ['name' => 'Stranger'], 'Hotel Manager');
    app(TenantContext::class)->set($s['org']->id);

    foreach ([$stranger, $owner] as $who) {
        $this->actingAs($s['max'])->postJson(route('app.inbox.team.start'), ['members' => [(string) $who->id]])
            ->assertStatus(422)->assertJsonPath('message', 'Choose people who work in this workspace.');
    }
    $this->actingAs($s['max'])->postJson(route('app.inbox.team.start'), ['members' => [(string) $s['max']->id]])->assertStatus(422);
    expect(DB::table('team_conversations')->where('organization_id', $s['org']->id)->count())->toBe(0);
});

// ── sending and receiving ────────────────────────────────────────────────────────────────────────────────────────

it('⚠️⚠️ delivers a message to the other member — their poll shows it unread, then the text itself', function () {
    $s = tcWorld();
    $id = tcStart($s, 'max', ['anna']);

    tcSend($s, 'max', $id, ['body' => "Room 12 needs towels.\nThanks!"])->assertCreated()
        ->assertJsonPath('message.body', "Room 12 needs towels.\nThanks!")
        ->assertJsonPath('message.mine', true);

    $list = $this->actingAs($s['anna'])->getJson(route('app.inbox.team.poll'))->assertOk();
    expect($list->json('unread_total'))->toBe(1)
        ->and(collect($list->json('conversations'))->firstWhere('id', $id))
        ->name->toBe('Max Brown')->unread->toBe(1);

    $open = $this->actingAs($s['anna'])->getJson(route('app.inbox.team.poll', ['c' => $id]))->assertOk();
    expect($open->json('messages.0.body'))->toBe("Room 12 needs towels.\nThanks!")
        ->and($open->json('messages.0.mine'))->toBeFalse()
        ->and($open->json('messages.0.sender.name'))->toBe('Max Brown');
});

it('⚠️⚠️ returns what came after the cursor, and looks 30 seconds back — a message saved a moment late is never skipped', function () {
    // ⚠️ 2026-10-06 (the independent review): this test said "never repeats" and the poll was a strict `id > cursor`. A
    // message's id is made when it is written, its row is seen when its transaction ends — a message with files can be
    // seen AFTER a newer one, behind the cursor, and was never shown. The page now de-duplicates by id.
    $s = tcWorld();
    $id = tcStart($s, 'max', ['anna']);
    $cursor = (string) tcSend($s, 'max', $id, ['body' => 'Seen already'])->json('message.id');
    tcSend($s, 'max', $id, ['body' => 'After the cursor']);

    app(TenantContext::class)->set($s['org']->id);
    $at = Ulid::fromString($cursor)->getDateTime();
    foreach (['Saved a moment late' => 5, 'Long ago' => 120] as $body => $secondsBefore) {
        (new TeamMessage)->forceFill([
            'id' => strtolower((string) Str::ulid($at->modify('-'.$secondsBefore.' seconds'))),
            'organization_id' => $s['org']->id, 'conversation_id' => $id, 'user_id' => $s['max']->id, 'body' => $body,
        ])->save();
    }

    $rows = collect($this->actingAs($s['anna'])->getJson(route('app.inbox.team.poll', ['c' => $id, 'after' => $cursor]))->assertOk()->json('messages'));
    expect($rows->pluck('body')->all())->toContain('After the cursor')->toContain('Saved a moment late')->not->toContain('Long ago')
        ->and($rows->pluck('id')->all())->toBe($rows->pluck('id')->sort()->values()->all());   // oldest first, by id
});

it('⚠️⚠️ hides a conversation from everyone who is not in it — same workspace or another — as not found', function () {
    $s = tcWorld();
    $id = tcStart($s, 'max', ['anna']);
    tcSend($s, 'max', $id, ['body' => 'Private']);
    $other = seedScenario(1);
    $stranger = operatorUser($other, [], 'Hotel Manager');
    app(TenantContext::class)->set($s['org']->id);

    // Ben works here but is not in the conversation
    tcSend($s, 'ben', $id, ['body' => 'Hi'])->assertNotFound();
    $this->actingAs($s['ben'])->postJson(route('app.inbox.team.read', $id))->assertNotFound();
    $ben = $this->actingAs($s['ben'])->getJson(route('app.inbox.team.poll', ['c' => $id]))->assertOk();
    expect($ben->json('messages'))->toBe([])->and(collect($ben->json('conversations'))->pluck('id')->all())->not->toContain($id);
    expect((string) $this->actingAs($s['ben'])->get(route('app.inbox.team', ['c' => $id]))->getContent())->not->toContain('Private');

    // another workspace
    $this->actingAs($stranger)->postJson(route('app.inbox.team.read', $id))->assertNotFound();
    expect($this->actingAs($stranger)->getJson(route('app.inbox.team.poll', ['c' => $id]))->json('messages'))->toBe([]);
});

it('refuses an empty message and one over 5,000 characters, in words', function () {
    $s = tcWorld();
    $id = tcStart($s, 'max', ['anna']);

    tcSend($s, 'max', $id, ['body' => '   '])->assertStatus(422)->assertJsonPath('message', 'Write a message, or add a file or a voice note.');
    tcSend($s, 'max', $id, ['body' => str_repeat('a', 5001)])->assertStatus(422)->assertJsonPath('message', 'A message can be up to 5,000 characters.');
    expect(DB::table('team_messages')->where('conversation_id', $id)->count())->toBe(0);
});

// ── files, pictures, voice notes ─────────────────────────────────────────────────────────────────────────────────

it('⚠️⚠️ keeps pictures and files private — on the private disk, opened only by the conversation\'s members', function () {
    Storage::fake('local');
    $s = tcWorld();
    $id = tcStart($s, 'max', ['anna']);
    $other = seedScenario(1);
    $stranger = operatorUser($other, [], 'Hotel Manager');
    app(TenantContext::class)->set($s['org']->id);

    $sent = tcSend($s, 'max', $id, ['body' => 'See attached', 'files' => [
        UploadedFile::fake()->image('stain.png', 40, 30),
        UploadedFile::fake()->createWithContent('Checklist.pdf', "%PDF-1.4\n%âãÏÓ\n1 0 obj << /Type /Catalog >> endobj\ntrailer << >>\n%%EOF\n"),
    ]])->assertCreated();

    $files = collect($sent->json('message.files'));
    expect($files->pluck('kind')->all())->toBe(['image', 'file'])
        ->and($files->pluck('name')->all())->toBe(['stain.png', 'Checklist.pdf']);
    $media = Media::query()->whereIn('id', $files->pluck('id'))->get();
    expect($media->pluck('disk')->unique()->all())->toBe(['local']);
    foreach ($media as $m) {
        Storage::disk('local')->assertExists($m->path);
        expect($m->path)->toStartWith('team-chat/');
    }

    $image = $files->firstWhere('kind', 'image');
    $pdf = $files->firstWhere('kind', 'file');
    $this->actingAs($s['anna'])->get($image['url'])->assertOk()->assertHeader('Content-Type', 'image/png')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
    $download = $this->actingAs($s['anna'])->get($pdf['url'])->assertOk()->assertHeader('Content-Type', 'application/octet-stream');
    expect((string) $download->headers->get('Content-Disposition'))->toContain('attachment')->toContain('Checklist.pdf')
        ->and((string) $download->headers->get('Cache-Control'))->toContain('no-store');

    $this->actingAs($s['ben'])->get($image['url'])->assertNotFound();      // same workspace, not in the conversation
    $this->actingAs($stranger)->get($image['url'])->assertNotFound();      // another workspace
    auth()->logout();
    $this->get($image['url'])->assertRedirect(route('login'));
});

it('refuses a file type that is not a picture or a document, and more than five files', function () {
    Storage::fake('local');
    $s = tcWorld();
    $id = tcStart($s, 'max', ['anna']);

    tcSend($s, 'max', $id, ['files' => [UploadedFile::fake()->createWithContent('page.html', '<html><script>alert(1)</script></html>')]])
        ->assertStatus(422)->assertJsonPath('message', 'Send pictures (JPG, PNG, WebP, GIF) or documents (PDF, Word, Excel, CSV, text).');
    tcSend($s, 'max', $id, ['files' => array_map(fn (int $i) => UploadedFile::fake()->image("p{$i}.png"), range(1, 6))])
        ->assertStatus(422)->assertJsonPath('message', 'Send up to 5 files at a time.');
    expect(DB::table('team_messages')->where('conversation_id', $id)->count())->toBe(0);
});

it('⚠️⚠️ keeps a voice note with its length and plays it as audio — in parts, as Safari asks', function () {
    Storage::fake('local');
    $s = tcWorld();
    $id = tcStart($s, 'max', ['anna']);

    $sent = tcSend($s, 'max', $id, ['voice' => UploadedFile::fake()->createWithContent('voice.webm', tcWebm()), 'voice_seconds' => 12])
        ->assertCreated();
    $voice = $sent->json('message.files.0');
    expect($voice['kind'])->toBe('voice')->and($voice['seconds'])->toBe(12)
        ->and(Media::query()->find($voice['id'])->collection)->toBe('voice');

    $this->actingAs($s['anna'])->get($voice['url'])->assertOk()->assertHeader('Content-Type', 'audio/webm');
    $part = $this->actingAs($s['anna'])->get($voice['url'], ['Range' => 'bytes=0-9']);
    $part->assertStatus(206);
    expect((string) $part->headers->get('Content-Range'))->toStartWith('bytes 0-9/');

    // a voice note is never a way to post any other file
    tcSend($s, 'max', $id, ['voice' => UploadedFile::fake()->image('not-audio.png')])
        ->assertStatus(422)->assertJsonPath('message', 'That voice note could not be read — record it again.');
    // its length is kept within the 3-minute cap
    $long = tcSend($s, 'max', $id, ['voice' => UploadedFile::fake()->createWithContent('v.webm', tcWebm()), 'voice_seconds' => 999]);
    expect($long->json('message.files.0.seconds'))->toBe(180);
});

// ── read receipts and unread ─────────────────────────────────────────────────────────────────────────────────────

it('⚠️ clears your unread when you read, and tells the sender it was seen', function () {
    $s = tcWorld();
    $id = tcStart($s, 'max', ['anna']);
    tcSend($s, 'max', $id, ['body' => 'Are you on today?']);

    expect($this->actingAs($s['max'])->getJson(route('app.inbox.team.poll'))->json('unread_total'))->toBe(0);   // never your own
    $this->actingAs($s['anna'])->postJson(route('app.inbox.team.read', $id))->assertOk();
    expect($this->actingAs($s['anna'])->getJson(route('app.inbox.team.poll'))->json('unread_total'))->toBe(0);

    $reads = collect($this->actingAs($s['max'])->getJson(route('app.inbox.team.poll', ['c' => $id]))->json('reads'));
    expect($reads->firstWhere('user_id', (string) $s['anna']->id))->name->toBe('Anna Lee')->at->not->toBeNull();
});

// ── notifications ────────────────────────────────────────────────────────────────────────────────────────────────

it('⚠️⚠️ rings the bell once per conversation while unread — in-app only, never emailed, never the message text', function () {
    Mail::fake();
    // ⚠️ the mail fake cannot see a NOTIFICATION's mail channel (it ignores view-based sends) — TC-M8 stayed green against
    // it on the first run. Every channel passes the NotificationSending event: that is what is asserted.
    Event::fake([NotificationSending::class]);
    $s = tcWorld();
    $id = tcStart($s, 'max', ['anna']);

    tcSend($s, 'max', $id, ['body' => 'The safe code is 4471']);
    tcSend($s, 'max', $id, ['body' => 'Second note']);

    $rows = $s['anna']->unreadNotifications()->where('type', TeamMessageNotification::class)->get();
    expect($rows)->toHaveCount(1)
        ->and($rows[0]->data['title'])->toBe('Max Brown sent you 2 messages')
        ->and($rows[0]->data['category'])->toBe('messages')
        ->and($rows[0]->data['url'])->toBe(route('app.inbox.team', ['c' => $id], false))
        ->and(json_encode($rows[0]->data))->not->toContain('4471')->not->toContain('Second note');
    expect($s['max']->unreadNotifications()->count())->toBe(0);   // the sender is never notified of their own message
    Mail::assertNothingOutgoing();
    Event::assertDispatched(NotificationSending::class, fn (NotificationSending $e): bool => $e->channel === 'database');
    Event::assertNotDispatched(NotificationSending::class, fn (NotificationSending $e): bool => $e->channel === 'mail');

    $this->actingAs($s['anna'])->postJson(route('app.inbox.team.read', $id))->assertOk();
    expect($s['anna']->fresh()->unreadNotifications()->where('type', TeamMessageNotification::class)->count())->toBe(0);
});

it('names the group when a teammate writes in one', function () {
    $s = tcWorld();
    $id = tcStart($s, 'max', ['anna', 'ben'], 'Morning shift');
    tcSend($s, 'anna', $id, ['body' => 'Done with 3rd floor']);

    expect($s['ben']->unreadNotifications()->first()->data['title'])->toBe('Anna Lee wrote in Morning shift')
        ->and($s['max']->unreadNotifications()->first()->data['title'])->toBe('Anna Lee wrote in Morning shift');
});

// ── the pulse: the bell, the badges and the chime on every page ──────────────────────────────────────────────────

it('⚠️ gives every back-office page its live counts — guests only to those who may read them', function () {
    $s = tcWorld();
    $id = tcStart($s, 'max', ['anna', 'ben'], 'Shift');
    tcSend($s, 'max', $id, ['body' => 'Briefing at 9']);

    $this->actingAs($s['anna'])->getJson(route('app.pulse'))->assertOk()
        ->assertJsonPath('team', 1)->assertJsonPath('notifications', 1)->assertJsonPath('guests', null);
    $this->actingAs($s['ben'])->getJson(route('app.pulse'))->assertOk()
        ->assertJsonPath('team', 1)->assertJsonPath('guests', 0);
});

// ── privacy and the page's own permissions ───────────────────────────────────────────────────────────────────────

it('⚠️⚠️ keeps team chat closed to a Vyrobel support session — it borrows a staff member\'s view, not their conversations', function () {
    config(['platform.break_glass.enabled' => true]);
    $s = tcWorld();
    $id = tcStart($s, 'max', ['anna']);
    tcSend($s, 'max', $id, ['body' => 'Between us']);
    // a support session refuses to open in a browser where a workspace user is signed in (PlatformBreakGlassTest) — sign out
    auth('web')->logout();
    $this->flushSession();
    $admin = platformAdmin();

    $this->actingAs($admin, 'admin')->post(route('admin.break-glass.start'), [
        'organization_id' => $s['org']->id, 'code' => (new Google2FA)->getCurrentOtp((string) $admin->two_factor_secret),
        'reason' => 'Ticket 7 — a booking is missing from the list.',
    ]);
    app('auth')->shouldUse('web');
    expect(BreakGlassSession::query()->where('target_organization_id', $s['org']->id)->exists())->toBeTrue();

    $page = (string) $this->get(route('app.inbox.team', ['c' => $id]))->assertOk()->getContent();
    expect($page)->toContain('Team messages are private to the people in them')->not->toContain('Between us');
    $this->getJson(route('app.inbox.team.poll', ['c' => $id]))->assertNotFound();
});

it('⚠️ allows the microphone on the two Messages pages only, and lets a recording be heard before it is sent', function () {
    $s = tcWorld();

    foreach (['app.inbox.team', 'app.dashboard'] as $name) {
        $policy = (string) $this->actingAs($s['ben'])->get(route($name))->headers->get('Permissions-Policy');
        expect($policy)->toContain($name === 'app.inbox.team' ? 'microphone=(self)' : 'microphone=()');
    }
    expect((string) $this->actingAs($s['ben'])->get(route('app.inbox'))->headers->get('Permissions-Policy'))->toContain('microphone=(self)')
        ->and((string) $this->actingAs($s['ben'])->get(route('app.inbox.team'))->headers->get('Content-Security-Policy'))->toContain("media-src 'self' blob:");
});

it('⚠️ keeps a paused workspace\'s chats readable but sends nothing — like the Inbox', function () {
    $s = tcWorld();
    $id = tcStart($s, 'max', ['anna']);
    tcSend($s, 'max', $id, ['body' => 'Before the pause']);
    $s['org']->forceFill(['paused_until' => now()->addDays(10)])->save();

    tcSend($s, 'anna', $id, ['body' => 'After'])->assertStatus(422)
        ->assertJsonPath('message', 'This workspace is paused — messages can be read, not sent.');
    $this->actingAs($s['anna'])->postJson(route('app.inbox.team.start'), ['members' => [(string) $s['ben']->id]])
        ->assertStatus(422)->assertJsonPath('message', 'This workspace is paused — messages can be read, not sent.');
    $this->actingAs($s['anna'])->postJson(route('app.inbox.team.read', $id))->assertOk();      // reading is not a write
    expect($this->actingAs($s['anna'])->getJson(route('app.inbox.team.poll', ['c' => $id]))->json('messages.0.body'))->toBe('Before the pause');
    expect((string) $this->actingAs($s['anna'])->get(route('app.inbox.team', ['c' => $id]))->getContent())
        ->toContain('"readOnly":true');
});

it('says so when nobody else works here yet', function () {
    $s = seedScenario(1);
    $solo = operatorUser($s, ['name' => 'Solo Owner'], 'Property Owner');

    $this->actingAs($solo)->get(route('app.inbox.team'))->assertOk()->assertSee('No one else works here yet');
});

it('keeps the data in tables of its own — never among the guest conversations', function () {
    $s = tcWorld();
    $id = tcStart($s, 'max', ['anna']);
    tcSend($s, 'max', $id, ['body' => 'Internal only']);

    expect(DB::table('message_threads')->where('organization_id', $s['org']->id)->count())->toBe(0)
        ->and(DB::table('messages')->where('organization_id', $s['org']->id)->count())->toBe(0)
        ->and(TeamMessage::query()->where('conversation_id', $id)->count())->toBe(1)
        ->and(TeamConversation::query()->whereKey($id)->exists())->toBeTrue();
    expect((string) $this->actingAs($s['ben'])->get(route('app.inbox'))->getContent())->not->toContain('Internal only');
});

// ── the independent review's findings (2026-10-06) — each failed first ──────────────────────────────────────────────

it('⚠️⚠️ never skips a colleague\'s message because you sent one — and never marks it read for you (review 1)', function () {
    $s = tcWorld();
    $id = tcStart($s, 'max', ['anna']);
    $first = (string) tcSend($s, 'max', $id, ['body' => 'Seen this?'])->json('message.id');   // Anna has not polled yet
    $mine = (string) tcSend($s, 'anna', $id, ['body' => 'Writing at the same moment'])->json('message.id');

    // Anna's page holds her own message as the newest; her poll after it must still bring Max's earlier one
    $poll = $this->actingAs($s['anna'])->getJson(route('app.inbox.team.poll', ['c' => $id, 'after' => $mine]))->assertOk();
    expect(collect($poll->json('messages'))->pluck('id')->all())->toContain($first)
        // and sending did not mark it read: it is still unread to her
        ->and($poll->json('unread_total'))->toBe(1);
    expect(DB::table('team_conversation_members')->where('conversation_id', $id)->where('user_id', $s['anna']->id)->value('last_read_message_id'))->toBeNull();
});

it('⚠️⚠️ keeps a Vyrobel support session out of team chat\'s bell entries too — names and counts included (review 4)', function () {
    config(['platform.break_glass.enabled' => true]);
    $s = tcWorld();
    // two people write to a group of three, so whoever the session borrows holds a team-chat bell entry
    $id = tcStart($s, 'anna', ['max', 'ben'], 'Night shift');
    tcSend($s, 'anna', $id, ['body' => 'About the warning']);
    tcSend($s, 'max', $id, ['body' => 'Noted']);
    auth('web')->logout();
    $this->flushSession();
    // the support session borrows the first verified back-office user's view — make that Max
    $admin = platformAdmin();
    $this->actingAs($admin, 'admin')->post(route('admin.break-glass.start'), [
        'organization_id' => $s['org']->id, 'code' => (new Google2FA)->getCurrentOtp((string) $admin->two_factor_secret),
        'reason' => 'Ticket 9 — checking the support view.',
    ]);
    app('auth')->shouldUse('web');
    $viewing = BreakGlassSession::query()->where('target_organization_id', $s['org']->id)->value('viewing_user_id');
    // ⚠️ NON-VACUITY: the borrowed view is one of the three members, so it really holds a team-chat bell entry
    expect([(string) $s['max']->id, (string) $s['anna']->id, (string) $s['ben']->id])->toContain((string) $viewing)
        ->and(DatabaseNotification::query()->where('notifiable_id', $viewing)->where('type', TeamMessageNotification::class)->whereNull('read_at')->count())->toBe(1);

    $dashboard = (string) $this->get(route('app.dashboard'))->assertOk()->getContent();
    $feed = (string) $this->get(route('app.notifications'))->assertOk()->getContent();
    expect($dashboard)->not->toContain('Night shift')->not->toContain('wrote in')
        ->and($feed)->not->toContain('Night shift')->not->toContain('wrote in');
    $pulse = $this->getJson(route('app.pulse', ['bell' => 1]))->assertOk();
    expect($pulse->json('team'))->toBe(0)->and((string) $pulse->json('bell'))->not->toContain('Night shift');
});

it('⚠️ stops ringing a person who no longer works here (review 6)', function () {
    $s = tcWorld();
    $id = tcStart($s, 'max', ['anna', 'ben'], 'Shift');
    Membership::query()->where('organization_id', $s['org']->id)->where('user_id', $s['ben']->id)->update(['status' => 'disabled']);

    tcSend($s, 'max', $id, ['body' => 'Rota change']);
    expect($s['ben']->unreadNotifications()->where('type', TeamMessageNotification::class)->count())->toBe(0)
        ->and($s['anna']->unreadNotifications()->where('type', TeamMessageNotification::class)->count())->toBe(1);
});

it('⚠️ keeps ONE bell entry per conversation in the feed — a read one is replaced, not piled up (review 7)', function () {
    $s = tcWorld();
    $id = tcStart($s, 'max', ['anna']);
    tcSend($s, 'max', $id, ['body' => 'One']);
    $this->actingAs($s['anna'])->postJson(route('app.inbox.team.read', $id))->assertOk();
    tcSend($s, 'max', $id, ['body' => 'Two']);

    expect($s['anna']->notifications()->where('type', TeamMessageNotification::class)->count())->toBe(1)
        ->and($s['anna']->unreadNotifications()->where('type', TeamMessageNotification::class)->first()->data['title'])->toBe('Max Brown sent you a message');
});

it('⚠️ downloads a file under its own name and the type its bytes say — never a hidden direction mark or a script\'s extension (review 9)', function () {
    Storage::fake('local');
    $s = tcWorld();
    $id = tcStart($s, 'max', ['anna']);
    $sent = tcSend($s, 'max', $id, ['files' => [tcRealFile("invoice\u{202E}fdp.vbs", "MsgBox \"hello\"\n")]])->assertCreated();
    $file = $sent->json('message.files.0');

    expect($file['name'])->not->toContain("\u{202E}");
    $download = $this->actingAs($s['anna'])->get($file['url'])->assertOk();
    $disposition = (string) $download->headers->get('Content-Disposition');
    expect($disposition)->toContain('invoicefdp.txt')->not->toContain('.vbs')->not->toContain("\u{202E}");

    // a file whose name and bytes agree keeps its own name — a CSV stays a CSV
    $csv = tcSend($s, 'max', $id, ['files' => [tcRealFile('rates.csv', "date,rate\n2026-10-06,120\n")]])->assertCreated();
    expect((string) $this->actingAs($s['anna'])->get($csv->json('message.files.0.url'))->assertOk()->headers->get('Content-Disposition'))->toContain('rates.csv');
});
