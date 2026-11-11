<?php

declare(strict_types=1);

use App\Models\BreakGlassSession;
use App\Models\Guest;
use App\Models\Media;
use App\Models\Membership;
use App\Models\TeamConversation;
use App\Models\TeamMessage;
use App\Notifications\Operator\TeamMessageNotification;
use App\Services\InboxService;
use App\Services\TeamChatService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use PragmaRX\Google2FA\Google2FA;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
    // and it cannot search them either (D-TC-LATER): the same borrowed view, a GET that would otherwise answer
    $this->getJson(route('app.inbox.team.search', ['conversation' => $id, 'q' => 'Between']))->assertNotFound();
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

// ══════════════════════════════════════════════════════════════════════════════════════════════════════════════════
// D-TC-LATER (2026-11-11, his go) — EDIT AND DELETE YOUR OWN MESSAGE, and the ONE way a change reaches an open screen.
// 📖 Read first: delivery is polling only, and the page merged by id, ignoring any id it already held — so nothing could
// reach another open screen after the message was first sent. Each write below bumps `updated_at`, and the poll (given
// the server's own clock as `since`) returns what changed.
// ══════════════════════════════════════════════════════════════════════════════════════════════════════════════════════

/** The facts recorded for one action, as one lowercase string — the trail must say THAT, never WHAT was written. */
function tcTrail(string $action): string
{
    // the JSON columns come back as text with the database's own spacing — decode them so the check reads the facts, not the format
    $decode = fn (array $row): array => array_map(fn ($v) => is_string($v) && in_array($v[0] ?? '', ['{', '['], true) ? json_decode($v, true) : $v, $row);
    $audit = DB::table('audit_events')->where('action', $action)->get(['before', 'after', 'subject_id'])->map(fn ($r): array => $decode((array) $r))->all();
    $events = DB::table('domain_events')->where('event_type', $action)->get(['payload', 'subject_id'])->map(fn ($r): array => $decode((array) $r))->all();

    return strtolower((string) json_encode(['audit' => $audit, 'events' => $events]));
}

it('⚠️⚠️ lets the author change the words — "edited" on both screens, and only a count in the trail', function () {
    $s = tcWorld();
    $id = tcStart($s, 'max', ['anna']);
    $mid = (string) tcSend($s, 'max', $id, ['body' => 'Room 12 needs towles'])->json('message.id');

    $edit = $this->actingAs($s['max'])->patchJson(route('app.inbox.team.message.edit', $mid), ['body' => 'Room 12 needs fresh towels'])->assertOk();
    expect($edit->json('message.body'))->toBe('Room 12 needs fresh towels')->and($edit->json('message.edited'))->toBeTrue();

    $row = collect($this->actingAs($s['anna'])->getJson(route('app.inbox.team.poll', ['c' => $id]))->json('messages'))->firstWhere('id', $mid);
    expect($row['body'])->toBe('Room 12 needs fresh towels')->and($row['edited'])->toBeTrue()->and($row['mine'])->toBeFalse()
        ->and($row['editable_until'])->toBeNull();   // only the author is offered Edit

    // the trail says THAT it was edited and how long it now is — never the words (before or after)
    $trail = tcTrail('team_chat.message_edited');
    expect($trail)->toContain('"characters":26')->not->toContain('towels')->not->toContain('towles');
    expect(DB::table('audit_events')->where('action', 'team_chat.message_edited')->count())->toBe(1)
        ->and(DB::table('domain_events')->where('event_type', 'team_chat.message_edited')->count())->toBe(1);
    // an edit is not a new message: the bell is not rung again
    expect($s['anna']->unreadNotifications()->where('type', TeamMessageNotification::class)->count())->toBe(1);

    // saving the same words again changes nothing — no second event
    $this->actingAs($s['max'])->patchJson(route('app.inbox.team.message.edit', $mid), ['body' => 'Room 12 needs fresh towels'])->assertOk();
    expect(DB::table('audit_events')->where('action', 'team_chat.message_edited')->count())->toBe(1);
});

it('⚠️⚠️ lets only the author edit or delete — a teammate in the conversation is told so, someone outside it gets "not found"', function () {
    $s = tcWorld();
    $id = tcStart($s, 'max', ['anna']);
    $mid = (string) tcSend($s, 'max', $id, ['body' => 'Mine'])->json('message.id');
    $other = seedScenario(1);
    $stranger = operatorUser($other, [], 'Hotel Manager');
    app(TenantContext::class)->set($s['org']->id);

    // PERMITTED actor beside the refused ones: Max, the author, can (above); Anna is in the conversation but did not write it
    $this->actingAs($s['anna'])->patchJson(route('app.inbox.team.message.edit', $mid), ['body' => 'Hijacked'])
        ->assertStatus(422)->assertJsonPath('message', 'You can change or delete only messages you wrote.');
    $this->actingAs($s['anna'])->deleteJson(route('app.inbox.team.message.delete', $mid))
        ->assertStatus(422)->assertJsonPath('message', 'You can change or delete only messages you wrote.');
    // Ben works here, another workspace's manager does not — neither is in the conversation: not found, never a 403
    foreach ([$s['ben'], $stranger] as $who) {
        $this->actingAs($who)->patchJson(route('app.inbox.team.message.edit', $mid), ['body' => 'No'])->assertNotFound();
        $this->actingAs($who)->deleteJson(route('app.inbox.team.message.delete', $mid))->assertNotFound();
    }
    $this->actingAs($s['max'])->patchJson(route('app.inbox.team.message.edit', 'not-an-id'), ['body' => 'No'])->assertNotFound();

    expect(DB::table('team_messages')->where('id', $mid)->first(['body', 'edited_at', 'deleted_at']))
        ->body->toBe('Mine')->edited_at->toBeNull()->deleted_at->toBeNull();
    $this->actingAs($s['max'])->patchJson(route('app.inbox.team.message.edit', $mid), ['body' => 'Mine, changed'])->assertOk();   // the author still can
});

it('⚠️ closes editing 15 minutes after sending — and refuses an edit that would leave nothing', function () {
    Storage::fake('local');
    $s = tcWorld();
    $id = tcStart($s, 'max', ['anna']);
    $mid = (string) tcSend($s, 'max', $id, ['body' => 'Plain text'])->json('message.id');
    $withFile = (string) tcSend($s, 'max', $id, ['body' => 'With a picture', 'files' => [UploadedFile::fake()->image('a.png', 20, 20)]])->json('message.id');

    $this->actingAs($s['max'])->patchJson(route('app.inbox.team.message.edit', $mid), ['body' => '   '])
        ->assertStatus(422)->assertJsonPath('message', 'A message cannot be left empty — delete it instead.');
    $this->actingAs($s['max'])->patchJson(route('app.inbox.team.message.edit', $mid), ['body' => str_repeat('a', 5001)])
        ->assertStatus(422)->assertJsonPath('message', 'A message can be up to 5,000 characters.');
    // a message that carries a file may lose its words — the file is still something
    $this->actingAs($s['max'])->patchJson(route('app.inbox.team.message.edit', $withFile), ['body' => ''])->assertOk()->assertJsonPath('message.body', '');

    Carbon::setTestNow(now()->addMinutes(14)->addSeconds(50));
    $this->actingAs($s['max'])->patchJson(route('app.inbox.team.message.edit', $mid), ['body' => 'Just in time'])->assertOk();
    Carbon::setTestNow(now()->addMinutes(1));   // sixteen minutes after it was sent
    $this->actingAs($s['max'])->patchJson(route('app.inbox.team.message.edit', $mid), ['body' => 'Too late'])
        ->assertStatus(422)->assertJsonPath('message', 'A message can be edited for 15 minutes after it is sent. You can still delete it.');
    expect(DB::table('team_messages')->where('id', $mid)->value('body'))->toBe('Just in time');
    $this->actingAs($s['max'])->deleteJson(route('app.inbox.team.message.delete', $mid))->assertOk();   // delete is allowed at any time
});

it('⚠️⚠️ deletes as a tombstone — the row stays, the words, the files and the bytes go, and it is no longer unread', function () {
    Storage::fake('local');
    $s = tcWorld();
    $id = tcStart($s, 'max', ['anna']);
    tcSend($s, 'max', $id, ['body' => 'First']);
    $mid = (string) tcSend($s, 'max', $id, ['body' => 'Secret passcode 4411', 'files' => [UploadedFile::fake()->image('door.png', 30, 30)]])->json('message.id');
    $file = Media::query()->where('mediable_id', $mid)->sole();
    Storage::disk('local')->assertExists($file->path);
    $before = $this->actingAs($s['anna'])->getJson(route('app.inbox.team.poll'))->assertOk();
    expect($before->json('unread_total'))->toBe(2);

    $deleted = $this->actingAs($s['max'])->deleteJson(route('app.inbox.team.message.delete', $mid))->assertOk();
    expect($deleted->json('message.deleted'))->toBeTrue()->and($deleted->json('message.body'))->toBe('')->and($deleted->json('message.files'))->toBe([]);

    // the row is KEPT (so order, read positions and "Seen" stay true); its words and files are not
    expect(DB::table('team_messages')->where('id', $mid)->first(['body', 'deleted_at', 'conversation_id']))
        ->body->toBeNull()->deleted_at->not->toBeNull()->conversation_id->toBe($id);
    expect(Media::query()->whereKey($file->id)->exists())->toBeFalse();
    Storage::disk('local')->assertMissing($file->path);

    // it is no longer something to read; the list says so, and the open conversation shows the tombstone in its place
    $after = $this->actingAs($s['anna'])->getJson(route('app.inbox.team.poll', ['c' => $id]))->assertOk();
    expect($after->json('unread_total'))->toBe(1)
        ->and(collect($after->json('conversations'))->firstWhere('id', $id)['last']['text'])->toBe('This message was deleted');
    $row = collect($after->json('messages'))->firstWhere('id', $mid);
    expect($row['deleted'])->toBeTrue()->and($row['body'])->toBe('')->and($row['files'])->toBe([]);

    // the trail: that it happened, and how many files — never the words
    expect(tcTrail('team_chat.message_deleted'))->toContain('"files":1')->not->toContain('passcode')->not->toContain('4411');

    // deleting again changes nothing; a deleted message cannot be edited
    $this->actingAs($s['max'])->deleteJson(route('app.inbox.team.message.delete', $mid))->assertOk();
    expect(DB::table('audit_events')->where('action', 'team_chat.message_deleted')->count())->toBe(1);
    $this->actingAs($s['max'])->patchJson(route('app.inbox.team.message.edit', $mid), ['body' => 'Back again'])
        ->assertStatus(422)->assertJsonPath('message', 'This message was deleted.');
});

it('⚠️ keeps a paused workspace from editing or deleting — like sending', function () {
    $s = tcWorld();
    $id = tcStart($s, 'max', ['anna']);
    $mid = (string) tcSend($s, 'max', $id, ['body' => 'Before the pause'])->json('message.id');
    $s['org']->forceFill(['paused_until' => now()->addDays(10)])->save();

    $this->actingAs($s['max'])->patchJson(route('app.inbox.team.message.edit', $mid), ['body' => 'Changed'])
        ->assertStatus(422)->assertJsonPath('message', 'This workspace is paused — messages can be read, not sent.');
    $this->actingAs($s['max'])->deleteJson(route('app.inbox.team.message.delete', $mid))
        ->assertStatus(422)->assertJsonPath('message', 'This workspace is paused — messages can be read, not sent.');
    expect(DB::table('team_messages')->where('id', $mid)->first(['body', 'deleted_at']))->body->toBe('Before the pause')->deleted_at->toBeNull();
});

it('⚠️⚠️ brings a change to a screen that already holds the message — the poll returns what changed since, whatever its id', function () {
    $s = tcWorld();
    $id = tcStart($s, 'max', ['anna']);
    app(TenantContext::class)->set($s['org']->id);

    // two messages long ago (ids a few minutes older than the page's cursor), and a newer one the page has just been shown
    $cursor = (string) tcSend($s, 'max', $id, ['body' => 'Newest on the page'])->json('message.id');
    $at = Ulid::fromString($cursor)->getDateTime();
    $old = [];
    foreach (['Edited later' => 300, 'Left alone' => 400] as $body => $secondsBefore) {
        $old[$body] = strtolower((string) Str::ulid($at->modify('-'.$secondsBefore.' seconds')));
        (new TeamMessage)->forceFill([
            'id' => $old[$body], 'organization_id' => $s['org']->id, 'conversation_id' => $id, 'user_id' => $s['max']->id, 'body' => $body,
            'created_at' => now(), 'updated_at' => now()->subHour(),
        ])->save();
    }
    $first = $this->actingAs($s['anna'])->getJson(route('app.inbox.team.poll', ['c' => $id, 'after' => $cursor]))->assertOk();
    $since = (int) $first->json('server_time');
    expect($since)->toBe(now()->getTimestamp());
    // nothing changed since the cursor and the clock → the old messages are not repeated
    expect(collect($first->json('messages'))->pluck('body')->all())->not->toContain('Edited later')->not->toContain('Left alone');

    Carbon::setTestNow(now()->addMinutes(2));
    $this->actingAs($s['max'])->patchJson(route('app.inbox.team.message.edit', $old['Edited later']), ['body' => 'Edited now'])->assertOk();

    // WITH `since`: the edited one comes back — and only that one; without it, the poll would never have shown it
    $with = collect($this->actingAs($s['anna'])->getJson(route('app.inbox.team.poll', ['c' => $id, 'after' => $cursor, 'since' => $since]))->json('messages'));
    expect($with->pluck('body')->all())->toContain('Edited now')->not->toContain('Left alone')->not->toContain('Edited later');
    $without = collect($this->actingAs($s['anna'])->getJson(route('app.inbox.team.poll', ['c' => $id, 'after' => $cursor]))->json('messages'));
    expect($without->pluck('body')->all())->not->toContain('Edited now');
    // a `since` from the future (a wrong clock, a tampered link) is held to now — it cannot hide a change
    $future = collect($this->actingAs($s['anna'])->getJson(route('app.inbox.team.poll', ['c' => $id, 'after' => $cursor, 'since' => now()->getTimestamp() + 99999]))->json('messages'));
    expect($future->pluck('body')->all())->toContain('Edited now');
});

// ══════════════════════════════════════════════════════════════════════════════════════════════════════════════════
// D-TC-PII (2026-11-11, his go) — A GUEST'S ERASURE REACHES TEAM CHAT. Staff write ABOUT a guest ("room 12's guest, Ines
// Walk, wants a late check-out"); a team message is not linked to a guest, so erasure finds it by the guest's own words.
// ⚠️ Read first: `Guest::anonymize()` cleared eleven stores and none of them was a team message — the words stayed in
// plaintext after the erasure. Each test below failed first against that code.
// ══════════════════════════════════════════════════════════════════════════════════════════════════════════════════════

/** An organisation's guest, as staff would write about them. */
function tcGuest(array $s, array $attributes = []): Guest
{
    app(TenantContext::class)->set($s['org']->id);

    return Guest::create($attributes + [
        'first_name' => 'Ines', 'last_name' => 'Walk', 'email' => 'ines.walk@example.test', 'phone' => '+351 910 000 123',
    ]);
}

it('⚠️⚠️ scrubs a guest from team chat when they are erased — full name (any order, case, accent), email and phone — and nothing else', function () {
    $s = tcWorld();
    $id = tcStart($s, 'max', ['anna']);
    $guest = tcGuest($s);
    $said = [
        'name' => "Room 12's guest, Ines Walk, wants a late check-out.",
        'reversed' => 'The booking is under Walk, Ines — please check.',
        'shouting' => 'INÉS WALK called again',
        'email' => 'Send it to Ines.Walk@Example.test please',
        'phone' => 'Her number is +351 910 000 123 and the other is 00351-910-000-123',
        'local' => 'Or ring 0910 000 123 after six',
        'firstOnly' => 'Ines said the tap drips',
        'surnameOnly' => 'Walk to the lobby and wait',
        'someoneElse' => 'Joanna Walker needs a cot',
        'plain' => 'Towels for room 14, thanks',
    ];
    $ids = [];
    foreach ($said as $key => $text) {
        $ids[$key] = (string) tcSend($s, 'max', $id, ['body' => $text])->json('message.id');
    }
    app(TenantContext::class)->set($s['org']->id);

    $guest->anonymize();

    $body = fn (string $key): string => (string) DB::table('team_messages')->where('id', $ids[$key])->value('body');
    foreach (['name', 'reversed', 'shouting', 'email', 'phone', 'local'] as $key) {
        expect($body($key))->toContain('[…]')->and(DB::table('team_messages')->where('id', $ids[$key])->value('redacted_at'))->not->toBeNull();
    }
    // the rest of a message stays, so a colleague's conversation still reads
    expect($body('name'))->toBe("Room 12's guest, […], wants a late check-out.")
        ->and($body('reversed'))->toBe('The booking is under […] — please check.')
        ->and($body('email'))->toBe('Send it to […] please')
        ->and($body('phone'))->toBe('Her number is […] and the other is […]');
    // ⚠️ a first name alone, a surname alone, and someone else with a similar name are other people's words — untouched
    foreach (['firstOnly', 'surnameOnly', 'someoneElse', 'plain'] as $key) {
        expect($body($key))->toBe($said[$key])->and(DB::table('team_messages')->where('id', $ids[$key])->value('redacted_at'))->toBeNull();
    }

    // raw-DB: nothing in the conversation still spells the identity
    $blob = strtolower((string) json_encode(DB::table('team_messages')->where('conversation_id', $id)->pluck('body')->filter(fn ($b) => str_contains((string) $b, '[…]'))->all(), JSON_UNESCAPED_UNICODE));
    expect($blob)->not->toContain('ines walk')->not->toContain('example.test')->not->toContain('910')->not->toContain('walk, ines');
});

it('⚠️⚠️ records only how many messages were scrubbed — never the words — and scrubs inside the erase\'s own transaction', function () {
    $s = tcWorld();
    $id = tcStart($s, 'max', ['anna']);
    $guest = tcGuest($s);
    tcSend($s, 'max', $id, ['body' => 'Ines Walk is arriving']);
    tcSend($s, 'max', $id, ['body' => 'Also ines.walk@example.test']);
    tcSend($s, 'max', $id, ['body' => 'Unrelated']);
    app(TenantContext::class)->set($s['org']->id);

    $guest->anonymize();

    $after = json_decode((string) DB::table('audit_events')->where('action', 'guest.anonymized')->where('subject_id', $guest->id)->value('after'), true);
    expect($after['team_messages_scrubbed'])->toBe(2);
    $all = strtolower((string) json_encode(DB::table('audit_events')->where('subject_id', $guest->id)->get()->all()).json_encode(DB::table('domain_events')->where('subject_id', $guest->id)->get()->all()));
    expect($all)->not->toContain('ines walk')->not->toContain('example.test');
    // erasing again finds nothing left to look for, and changes nothing
    $again = Guest::query()->whereKey($guest->id)->sole();
    $again->anonymize();
    expect(json_decode((string) DB::table('audit_events')->where('action', 'guest.anonymized')->where('subject_id', $guest->id)->latest('created_at')->value('after'), true)['team_messages_scrubbed'])->toBe(0);
});

it('⚠️⚠️ leaves another workspace\'s messages alone — the same name there is another person\'s', function () {
    $s = tcWorld();
    $id = tcStart($s, 'max', ['anna']);
    $guest = tcGuest($s);
    $mine = (string) tcSend($s, 'max', $id, ['body' => 'Ines Walk, room 3'])->json('message.id');

    $other = seedScenario(1);
    $otherMax = operatorUser($other, ['name' => 'Other Max'], 'Property Owner');
    $otherAnna = operatorUser($other, ['name' => 'Other Anna'], 'Housekeeping');
    app(TenantContext::class)->set($other['org']->id);
    $otherId = (string) $this->actingAs($otherMax)->postJson(route('app.inbox.team.start'), ['members' => [(string) $otherAnna->id]])->json('conversation.id');
    $theirs = (string) $this->actingAs($otherMax)->post(route('app.inbox.team.send', $otherId), ['body' => 'Ines Walk, ines.walk@example.test, +351 910 000 123'], ['Accept' => 'application/json'])->json('message.id');
    expect($theirs)->not->toBe('');

    app(TenantContext::class)->set($s['org']->id);
    $guest->anonymize();

    expect(DB::table('team_messages')->where('id', $mine)->value('body'))->toBe('[…], room 3')
        ->and(DB::table('team_messages')->where('id', $theirs)->value('body'))->toBe('Ines Walk, ines.walk@example.test, +351 910 000 123')
        ->and(DB::table('team_messages')->where('id', $theirs)->value('redacted_at'))->toBeNull();
});

it('⚠️ scrubs a guest who has only one name by email and phone alone — a lone name is never matched', function () {
    $s = tcWorld();
    $id = tcStart($s, 'max', ['anna']);
    $guest = tcGuest($s, ['first_name' => 'Madonna', 'last_name' => null, 'email' => 'madonna@example.test', 'phone' => null]);
    $name = (string) tcSend($s, 'max', $id, ['body' => 'Madonna asked for a late check-out'])->json('message.id');
    $mail = (string) tcSend($s, 'max', $id, ['body' => 'Her address is madonna@example.test'])->json('message.id');
    app(TenantContext::class)->set($s['org']->id);

    $guest->anonymize();

    expect(DB::table('team_messages')->where('id', $name)->value('body'))->toBe('Madonna asked for a late check-out')
        ->and(DB::table('team_messages')->where('id', $mail)->value('body'))->toBe('Her address is […]');
});

it('⚠️ shows the open screen what was removed and says why — the poll delivers the cleaned message, never the old words', function () {
    $s = tcWorld();
    $id = tcStart($s, 'max', ['anna']);
    $guest = tcGuest($s);
    $cursor = (string) tcSend($s, 'max', $id, ['body' => 'A later message that the screen holds'])->json('message.id');
    app(TenantContext::class)->set($s['org']->id);
    $at = Ulid::fromString($cursor)->getDateTime();
    $oldId = strtolower((string) Str::ulid($at->modify('-600 seconds')));
    (new TeamMessage)->forceFill([
        'id' => $oldId, 'organization_id' => $s['org']->id, 'conversation_id' => $id, 'user_id' => $s['max']->id,
        'body' => 'Ines Walk wants a quiet room', 'created_at' => now()->subHour(), 'updated_at' => now()->subHour(),
    ])->save();
    $since = (int) $this->actingAs($s['anna'])->getJson(route('app.inbox.team.poll', ['c' => $id, 'after' => $cursor]))->json('server_time');

    Carbon::setTestNow(now()->addMinute());
    $guest->anonymize();

    $row = collect($this->actingAs($s['anna'])->getJson(route('app.inbox.team.poll', ['c' => $id, 'after' => $cursor, 'since' => $since]))->assertOk()->json('messages'))->firstWhere('id', $oldId);
    expect($row['body'])->toBe('[…] wants a quiet room')->and($row['redacted'])->toBeTrue();
    // and the page carries the words that explain it
    $page = (string) $this->actingAs($s['anna'])->get(route('app.inbox.team', ['c' => $id]))->assertOk()->getContent();
    expect($page)->toContain('Part of this message was removed to protect someone')->not->toContain('Ines Walk');
});

it('⚠️ names team chat in what an erasure removes — the screen says so before it is confirmed', function () {
    $s = tcWorld();
    $page = (string) $this->actingAs($s['max'])->get(route('app.guests'))->assertOk()->getContent();

    expect($page)->toContain('Their name, email and phone number where team chat mentions them');
});

// ══════════════════════════════════════════════════════════════════════════════════════════════════════════════════
// D-TC-LATER (2026-11-11) · REACTIONS, SEARCH, TYPING, ONLINE NOW
// ══════════════════════════════════════════════════════════════════════════════════════════════════════════════════════

it('⚠️⚠️ toggles a reaction — one per person and emoji, only the allow-list, and a screen already holding the message gets it by the poll', function () {
    $s = tcWorld();
    $id = tcStart($s, 'max', ['anna', 'ben']);
    app(TenantContext::class)->set($s['org']->id);
    $cursor = (string) tcSend($s, 'max', $id, ['body' => 'Newest on the page'])->json('message.id');
    // the message being reacted to is OLDER than the page's cursor by minutes — only "what changed since" can bring it back
    $first = strtolower((string) Str::ulid(Ulid::fromString($cursor)->getDateTime()->modify('-600 seconds')));
    (new TeamMessage)->forceFill([
        'id' => $first, 'organization_id' => $s['org']->id, 'conversation_id' => $id, 'user_id' => $s['max']->id, 'body' => 'Briefing at nine',
        'created_at' => now()->subHour(), 'updated_at' => now()->subHour(),
    ])->save();
    $since = (int) $this->actingAs($s['max'])->getJson(route('app.inbox.team.poll', ['c' => $id, 'after' => $cursor]))->json('server_time');
    expect(collect($this->actingAs($s['max'])->getJson(route('app.inbox.team.poll', ['c' => $id, 'after' => $cursor, 'since' => $since]))->json('messages'))->pluck('id')->all())->not->toContain($first);

    Carbon::setTestNow(now()->addMinute());
    $on = $this->actingAs($s['anna'])->postJson(route('app.inbox.team.message.react', $first), ['emoji' => '👍'])->assertOk();
    expect($on->json('message.reactions'))->toBe([['emoji' => '👍', 'count' => 1, 'mine' => true, 'names' => ['Anna Lee']]]);
    $this->actingAs($s['ben'])->postJson(route('app.inbox.team.message.react', $first), ['emoji' => '👍'])->assertOk();
    $this->actingAs($s['ben'])->postJson(route('app.inbox.team.message.react', $first), ['emoji' => '🎉'])->assertOk();

    // the author's screen already holds the message — the poll brings its reactions (counted, who, and "mine" per viewer)
    $rows = collect($this->actingAs($s['max'])->getJson(route('app.inbox.team.poll', ['c' => $id, 'after' => $cursor, 'since' => $since]))->assertOk()->json('messages'));
    $row = $rows->firstWhere('id', $first);
    expect($row['reactions'])->toBe([
        ['emoji' => '👍', 'count' => 2, 'mine' => false, 'names' => ['Anna Lee', 'Ben Ortiz']],
        ['emoji' => '🎉', 'count' => 1, 'mine' => false, 'names' => ['Ben Ortiz']],
    ]);

    // the same tap again removes it — one row per (message, person, emoji), never two
    $off = $this->actingAs($s['anna'])->postJson(route('app.inbox.team.message.react', $first), ['emoji' => '👍'])->assertOk();
    expect($off->json('message.reactions.0.count'))->toBe(1)->and($off->json('message.reactions.0.mine'))->toBeFalse();
    expect(DB::table('team_message_reactions')->where('message_id', $first)->count())->toBe(2);

    // an emoji outside the fixed list is refused in words and stores nothing
    $this->actingAs($s['anna'])->postJson(route('app.inbox.team.message.react', $first), ['emoji' => '<b>hi</b>'])
        ->assertStatus(422)->assertJsonPath('message', 'That reaction is not available.');
    $this->actingAs($s['anna'])->postJson(route('app.inbox.team.message.react', $first), [])->assertStatus(422);
    expect(DB::table('team_message_reactions')->where('message_id', $first)->count())->toBe(2);
    // the trail has an audit row per toggle and NO domain event (the flagged exception) — and nobody is rung
    expect(DB::table('audit_events')->whereIn('action', ['team_chat.reaction_added', 'team_chat.reaction_removed'])->count())->toBe(4)
        ->and(DB::table('domain_events')->where('event_type', 'like', 'team_chat.reaction%')->count())->toBe(0)
        ->and($s['max']->unreadNotifications()->where('type', TeamMessageNotification::class)->count())->toBe(0);
});

it('⚠️ keeps reactions to the conversation\'s members — not found for anyone else, refused on a deleted message and in a paused workspace', function () {
    $s = tcWorld();
    $id = tcStart($s, 'max', ['anna']);
    $mid = (string) tcSend($s, 'max', $id, ['body' => 'Hello'])->json('message.id');
    $other = seedScenario(1);
    $stranger = operatorUser($other, [], 'Hotel Manager');
    app(TenantContext::class)->set($s['org']->id);

    $this->actingAs($s['anna'])->postJson(route('app.inbox.team.message.react', $mid), ['emoji' => '❤️'])->assertOk();   // a member can
    foreach ([$s['ben'], $stranger] as $who) {
        $this->actingAs($who)->postJson(route('app.inbox.team.message.react', $mid), ['emoji' => '❤️'])->assertNotFound();
    }
    expect(DB::table('team_message_reactions')->where('message_id', $mid)->count())->toBe(1);

    $s['org']->forceFill(['paused_until' => now()->addDays(10)])->save();
    $this->actingAs($s['anna'])->postJson(route('app.inbox.team.message.react', $mid), ['emoji' => '👍'])
        ->assertStatus(422)->assertJsonPath('message', 'This workspace is paused — messages can be read, not sent.');
    $s['org']->forceFill(['paused_until' => null])->save();

    // deleting a message takes its reactions with it, and a deleted message cannot be reacted to
    $this->actingAs($s['max'])->deleteJson(route('app.inbox.team.message.delete', $mid))->assertOk();
    expect(DB::table('team_message_reactions')->where('message_id', $mid)->count())->toBe(0);
    $this->actingAs($s['anna'])->postJson(route('app.inbox.team.message.react', $mid), ['emoji' => '👍'])
        ->assertStatus(422)->assertJsonPath('message', 'This message was deleted.');
});

it('⚠️⚠️ searches one conversation — members only, at least two characters, the newest first, % and _ taken literally, deleted messages never found', function () {
    $s = tcWorld();
    $id = tcStart($s, 'max', ['anna']);
    $other = (string) tcStart($s, 'max', ['ben']);
    $texts = ['Towels for room 14', 'A 50% deposit was taken', 'The code is 500', 'Snake_case is not a word here', 'Snakecase either', 'Towels again, room 9'];
    foreach ($texts as $t) {
        tcSend($s, 'max', $id, ['body' => $t]);
    }
    tcSend($s, 'max', $other, ['body' => 'Towels in the other conversation']);
    $gone = (string) tcSend($s, 'max', $id, ['body' => 'Towels that were deleted'])->json('message.id');
    $this->actingAs($s['max'])->deleteJson(route('app.inbox.team.message.delete', $gone))->assertOk();

    $found = $this->actingAs($s['anna'])->getJson(route('app.inbox.team.search', ['conversation' => $id, 'q' => 'towels']))->assertOk();
    expect(collect($found->json('results'))->pluck('snippet')->all())->toBe(['Towels again, room 9', 'Towels for room 14'])   // newest first
        ->and($found->json('total'))->toBe(2)
        ->and($found->json('results.0.sender'))->toBe('Max Brown')->and($found->json('results.0.mine'))->toBeFalse();

    // ⚠️ the characters a LIKE would treat as patterns are literal here
    $pct = $this->actingAs($s['anna'])->getJson(route('app.inbox.team.search', ['conversation' => $id, 'q' => '50%']))->assertOk();
    expect(collect($pct->json('results'))->pluck('snippet')->all())->toBe(['A 50% deposit was taken']);
    $under = $this->actingAs($s['anna'])->getJson(route('app.inbox.team.search', ['conversation' => $id, 'q' => 'Snake_case']))->assertOk();
    expect(collect($under->json('results'))->pluck('snippet')->all())->toBe(['Snake_case is not a word here']);

    // too short, in words; a conversation you are not in — or that is not a conversation — is not found
    $this->actingAs($s['anna'])->getJson(route('app.inbox.team.search', ['conversation' => $id, 'q' => 'a']))
        ->assertStatus(422)->assertJsonPath('message', 'Type at least 2 characters to search.');
    $this->actingAs($s['ben'])->getJson(route('app.inbox.team.search', ['conversation' => $id, 'q' => 'towels']))->assertNotFound();
    $this->actingAs($s['anna'])->getJson(route('app.inbox.team.search', ['conversation' => 'nope', 'q' => 'towels']))->assertNotFound();
    // and it only ever looks inside the conversation it was asked about
    $in = $this->actingAs($s['max'])->getJson(route('app.inbox.team.search', ['conversation' => $other, 'q' => 'towels']))->assertOk();
    expect(collect($in->json('results'))->pluck('snippet')->all())->toBe(['Towels in the other conversation']);
});

it('⚠️ shows who is typing for six seconds, never yourself, and ends it when they send', function () {
    $s = tcWorld();
    $id = tcStart($s, 'max', ['anna', 'ben'], 'Shift');

    $this->actingAs($s['max'])->postJson(route('app.inbox.team.typing', $id))->assertOk();
    $typing = fn (string $who): array => $this->actingAs($s[$who])->getJson(route('app.inbox.team.poll', ['c' => $id]))->assertOk()->json('typing');

    expect($typing('anna'))->toBe([['id' => (string) $s['max']->id, 'name' => 'Max Brown']])
        ->and($typing('max'))->toBe([]);   // you are never told you are typing

    Carbon::setTestNow(now()->addSeconds(5));
    expect($typing('anna'))->toHaveCount(1);
    Carbon::setTestNow(now()->addSeconds(2));   // seven seconds since the last keystroke
    expect($typing('anna'))->toBe([]);

    // two people at once; and sending clears the sender's row at once
    $this->actingAs($s['max'])->postJson(route('app.inbox.team.typing', $id))->assertOk();
    $this->actingAs($s['ben'])->postJson(route('app.inbox.team.typing', $id))->assertOk();
    expect(collect($typing('anna'))->pluck('name')->sort()->values()->all())->toBe(['Ben Ortiz', 'Max Brown']);
    tcSend($s, 'max', $id, ['body' => 'Done typing']);
    expect(collect($typing('anna'))->pluck('name')->all())->toBe(['Ben Ortiz'])
        ->and(DB::table('team_typing')->where('conversation_id', $id)->count())->toBe(1);   // one row per person, kept — not a pile

    // a person outside the conversation can neither say they are typing nor see who is; a paused workspace takes no writes
    $this->actingAs($s['max'])->postJson(route('app.inbox.team.typing', 'nope'))->assertNotFound();
    $direct = tcStart($s, 'max', ['anna']);
    $this->actingAs($s['ben'])->postJson(route('app.inbox.team.typing', $direct))->assertNotFound();
    $s['org']->forceFill(['paused_until' => now()->addDays(10)])->save();
    $this->actingAs($s['ben'])->postJson(route('app.inbox.team.typing', $id))->assertStatus(422);
});

it('⚠️⚠️ shows who is online from the sessions table — a request within 90 seconds, only people you talk to, and nothing at all on another driver', function () {
    $s = tcWorld();
    tcStart($s, 'max', ['anna']);   // Max talks to Anna; Ben is in no conversation with Max
    $session = fn (object $user, int $agoSeconds) => DB::table('sessions')->insert([
        'id' => Str::random(40), 'user_id' => (string) $user->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'test', 'payload' => '',
        'last_activity' => now()->getTimestamp() - $agoSeconds,
    ]);
    $online = fn (): mixed => $this->actingAs($s['max'])->getJson(route('app.inbox.team.poll'))->assertOk()->json('online');

    // the test suite keeps sessions in memory; presence is read only from the database driver — and says so by being null
    expect($online())->toBeNull();

    config(['session.driver' => 'database']);
    expect($online())->toBe([]);
    $session($s['anna'], 89);
    $session($s['ben'], 5);          // online, but Max shares no conversation with Ben: never named to him
    expect($online())->toBe([(string) $s['anna']->id]);

    DB::table('sessions')->where('user_id', $s['anna']->id)->delete();
    $session($s['anna'], 91);        // just past the boundary
    expect($online())->toBe([]);
    $session($s['anna'], 2);         // a second tab, active — one person, once
    $session($s['anna'], 3);
    expect($online())->toBe([(string) $s['anna']->id]);
});

it('⚠️⚠️ refuses a person outside the conversation at the SERVICE too — the controller is not the only door (edit, delete, react, typing)', function () {
    // "MEMBERS ONLY, HERE AS WELL AS AT THE CONTROLLER": a job, a command or a future route calls the service with no controller
    // in front of it. The HTTP tests above cannot tell the two layers apart (either one answers "not found"), so this calls it directly.
    $s = tcWorld();
    $id = tcStart($s, 'max', ['anna']);
    $mid = (string) tcSend($s, 'max', $id, ['body' => 'Between the two of us'])->json('message.id');
    app(TenantContext::class)->set($s['org']->id);
    $service = app(TeamChatService::class);
    $message = TeamMessage::query()->findOrFail($mid);
    $conversation = TeamConversation::query()->findOrFail($id);

    // PERMITTED actors beside the refused one: the author edits and deletes, a member reacts and types
    expect($service->edit($message, $s['max'], 'Between us two')->body)->toBe('Between us two')
        ->and($service->react($message, $s['anna'], '👍'))->toBeTrue();
    $service->typing($conversation, $s['anna']);

    // Ben works here but is in no part of this conversation
    expect(fn () => $service->edit($message, $s['ben'], 'No'))->toThrow(NotFoundHttpException::class)
        ->and(fn () => $service->delete($message, $s['ben']))->toThrow(NotFoundHttpException::class)
        ->and(fn () => $service->react($message, $s['ben'], '👍'))->toThrow(NotFoundHttpException::class)
        ->and(fn () => $service->typing($conversation, $s['ben']))->toThrow(NotFoundHttpException::class);
    expect(DB::table('team_message_reactions')->where('user_id', $s['ben']->id)->count())->toBe(0)
        ->and(DB::table('team_typing')->where('user_id', $s['ben']->id)->count())->toBe(0);
});

// ── THE PAGE (2026-11-11): what the browser is given — a script cannot be run here, so its inputs and its words are pinned ──

it('⚠️ gives the page its new inputs — the message, reaction, typing and search routes, the allow-list, the server clock — and the words for each control', function () {
    $s = tcWorld();
    $id = tcStart($s, 'max', ['anna']);
    tcSend($s, 'max', $id, ['body' => 'Hello']);

    $page = (string) $this->actingAs($s['anna'])->get(route('app.inbox.team', ['c' => $id]))->assertOk()->getContent();
    expect(preg_match('~<script type="application/json" id="vy-team-chat-data">(.*?)</script>~s', $page, $m))->toBe(1);
    $boot = json_decode($m[1], true);

    expect($boot['urls']['message'])->toEndWith('/inbox/team/messages/__ID__')
        ->and($boot['urls']['react'])->toEndWith('/inbox/team/messages/__ID__/reactions')
        ->and($boot['urls']['typing'])->toEndWith('/inbox/team/__ID__/typing')
        ->and($boot['urls']['search'])->toEndWith('/inbox/team/__ID__/search')
        ->and($boot['reactions'])->toBe(InboxService::REACTIONS)
        ->and($boot['serverTime'])->toBeInt()->toBeGreaterThan(0)
        ->and($boot['online'])->toBeNull()                                   // sessions are not in the database here: no presence, not a guess
        ->and($boot['messages'][0])->toHaveKeys(['deleted', 'edited', 'redacted', 'editable_until', 'reactions']);

    // every control says what it does, in words (the script reads them from here)
    foreach (['Message options', 'Add a reaction', 'This message was deleted', 'Search this conversation', 'Desktop notifications off',
        'Delete this message for everyone?', 'Go to message', 'is typing…', 'Online now'] as $words) {
        expect($page)->toContain($words);
    }
});

it('⚠️ gives every back-office page the desktop notification — counts only, on a click, a newer one replacing the older', function () {
    $s = tcWorld();
    $page = (string) $this->actingAs($s['anna'])->get(route('app.dashboard'))->assertOk()->getContent();
    expect(preg_match('~const DESK = \{(.*?)\};~s', $page, $m))->toBe(1);
    $desk = $m[1];

    expect($desk)->toContain('unread message')->toContain('Notifications are blocked in this browser')->toContain('This browser cannot show desktop notifications.')
        // counts only: the words never carry a name, and the script builds the body from the two counts
        ->not->toContain('Max Brown')->not->toContain('Anna Lee');
    expect($page)->toContain("store.get(DESKTOP_KEY) === 'on'")->toContain("tag: 'vy-messages'")->toContain('window.Notification.requestPermission()')
        ->toContain('VyLive.desktopNotify()');
});
