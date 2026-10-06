<?php

declare(strict_types=1);

use App\Models\Guest;
use App\Models\Media;
use App\Models\Message;
use App\Models\Owner;
use App\Services\InboxService;
use App\Support\Messaging\ChatFiles;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

uses(DatabaseTransactions::class);

// ⚠️⚠️ FILES, PICTURES AND VOICE NOTES IN A REPLY TO A GUEST OR AN OWNER (2026-10-06 — his "attachment … picture … voice …
// all of them to should be working fine"; `docs/TEAM-CHAT-DESIGN.md` §6). 📖 Read first: the operator composer could not
// attach anything; the guest portal showed no file at all; nothing anywhere played a voice note. Each test failed first.

/** A WebM header, as Chrome records a voice note (finfo reads it as video/webm). */
function iaWebm(): string
{
    return "\x1A\x45\xDF\xA3\x9F\x42\x86\x81\x01\x42\xF7\x81\x01\x42\xF2\x81\x04\x42\xF3\x81\x08\x42\x82\x84webm\x42\x87\x81\x04\x42\x85\x81\x02"
        .str_repeat("\x00", 2048);
}

/** A workspace, a guest who can sign in to their account, their Direct conversation, and a front-desk agent. */
function iaWorld(): array
{
    $s = seedScenario(1);
    $s['desk'] = operatorUser($s, ['name' => 'Dan Desk'], 'Front Desk');
    $guest = Guest::withoutGlobalScopes()->whereKey($s['guest']->id)->first();
    $guest->forceFill(['email' => 'ines@example.test', 'first_name' => 'Ines', 'last_name' => 'Ibarra', 'password' => 'guest-password-1234', 'email_verified_at' => now()])->save();
    $s['guest'] = $guest->fresh();
    $s['thread'] = app(InboxService::class)->startThread(['channel' => 'manual', 'guest_id' => (string) $guest->id]);

    return $s;
}

function iaReply(array $s, string $threadId, array $data, bool $json = true): TestResponse
{
    return test()->actingAs($s['desk'])->post(route('app.inbox.reply', $threadId), $data, $json ? ['Accept' => 'application/json'] : []);
}

it('⚠️⚠️ sends pictures, a document and a voice note to a guest — and the guest sees and hears them in their account', function () {
    Storage::fake('local');
    $s = iaWorld();

    iaReply($s, (string) $s['thread']->id, [
        'body' => 'Here is your invoice and the map.',
        'attachments' => [UploadedFile::fake()->image('map.png', 60, 40), UploadedFile::fake()->createWithContent('Invoice.pdf', "%PDF-1.4\n%%EOF\n")],
        'voice' => UploadedFile::fake()->createWithContent('voice.webm', iaWebm()), 'voice_seconds' => 9,
    ])->assertCreated()->assertJsonPath('ok', true);

    $message = Message::query()->where('thread_id', $s['thread']->id)->where('direction', 'outbound')->sole();
    expect($message->media()->pluck('collection')->sort()->values()->all())->toBe(['attachments', 'attachments', 'voice'])
        ->and($message->media()->pluck('disk')->unique()->all())->toBe(['local']);

    auth()->logout();
    $page = (string) $this->actingAs($s['guest'], 'guest')->get(route('guest.messages.show', $s['thread']->id))->assertOk()->getContent();
    $media = $message->media()->get()->keyBy('collection');
    $voice = $message->media()->where('collection', 'voice')->sole();
    $map = $message->media()->where('alt', 'map.png')->sole();
    expect($page)->toContain('Here is your invoice and the map.')
        ->toContain('<img src="'.route('guest.messages.attachment', (string) $map->id).'"')
        ->toContain('Invoice.pdf')
        ->toContain('<audio controls preload="none" src="'.route('guest.messages.attachment', (string) $voice->id).'"');

    $this->actingAs($s['guest'], 'guest')->get(route('guest.messages.attachment', (string) $map->id))->assertOk()->assertHeader('Content-Type', 'image/png');
    $this->actingAs($s['guest'], 'guest')->get(route('guest.messages.attachment', (string) $voice->id))->assertOk()->assertHeader('Content-Type', 'audio/webm');
    $part = $this->actingAs($s['guest'], 'guest')->get(route('guest.messages.attachment', (string) $voice->id), ['Range' => 'bytes=0-9']);
    $part->assertStatus(206);
});

it('⚠️⚠️ opens a guest\'s file for that guest only — another guest, a signed-out visitor and an owner\'s file all refused', function () {
    Storage::fake('local');
    $s = iaWorld();
    iaReply($s, (string) $s['thread']->id, ['attachments' => [UploadedFile::fake()->image('room.png')]])->assertCreated();
    $file = Media::query()->where('alt', 'room.png')->sole();

    $other = Guest::create(['first_name' => 'Otto', 'last_name' => 'Other', 'email' => 'otto@example.test']);
    $other->forceFill(['password' => 'guest-password-1234', 'email_verified_at' => now()])->save();
    auth()->logout();
    $this->actingAs($other->fresh(), 'guest')->get(route('guest.messages.attachment', (string) $file->id))->assertNotFound();

    // an owner's file never opens through the guest door
    $ownerUser = ownerRoleUser($s);
    $owner = Owner::query()->where('user_id', $ownerUser->id)->sole();
    $ownerThread = app(InboxService::class)->startThread(['channel' => 'manual', 'owner_id' => (string) $owner->id]);
    iaReply($s, (string) $ownerThread->id, ['attachments' => [UploadedFile::fake()->image('statement.png')]])->assertCreated();
    $ownerFile = Media::query()->where('alt', 'statement.png')->sole();
    auth()->logout();
    $this->actingAs($s['guest'], 'guest')->get(route('guest.messages.attachment', (string) $ownerFile->id))->assertNotFound();
    // ⚠️ PER LAYER (IA-M5 stayed green without this): an owner's conversation never names a guest, so the `guest_id` check
    // alone refused the file above and the door's own "never an owner's conversation" was never exercised. A conversation
    // that names BOTH is the one shape only that check refuses.
    $ownerThread->forceFill(['guest_id' => $s['guest']->id])->save();
    $this->actingAs($s['guest'], 'guest')->get(route('guest.messages.attachment', (string) $ownerFile->id))->assertNotFound();

    auth('guest')->logout();
    $this->get(route('guest.messages.attachment', (string) $file->id))->assertRedirect();
});

it('⚠️ plays an operator\'s voice note in the owner\'s portal', function () {
    Storage::fake('local');
    $s = iaWorld();
    $ownerUser = ownerRoleUser($s);
    $owner = Owner::query()->where('user_id', $ownerUser->id)->sole();
    $thread = app(InboxService::class)->startThread(['channel' => 'manual', 'owner_id' => (string) $owner->id]);

    iaReply($s, (string) $thread->id, ['voice' => UploadedFile::fake()->createWithContent('v.webm', iaWebm()), 'voice_seconds' => 4])->assertCreated();
    $voice = Media::query()->where('collection', 'voice')->sole();

    auth()->logout();
    $page = (string) $this->actingAs($ownerUser)->get(route('owner.messages', ['c' => $thread->id]))->assertOk()->getContent();
    expect($page)->toContain('<audio controls preload="none" src="'.route('owner.messages.attachment', (string) $voice->id).'"');
    $this->actingAs($ownerUser)->get(route('owner.messages.attachment', (string) $voice->id))->assertOk()->assertHeader('Content-Type', 'audio/webm');
});

it('refuses files on a channel that cannot carry them, and a reply with nothing in it, in words', function () {
    Storage::fake('local');
    $s = iaWorld();
    $email = app(InboxService::class)->startThread(['channel' => 'email', 'guest_id' => (string) $s['guest']->id, 'external_thread_ref' => 'em-1']);

    iaReply($s, (string) $email->id, ['body' => 'See attached', 'attachments' => [UploadedFile::fake()->image('a.png')]])
        ->assertStatus(422)->assertJsonPath('message', 'Files and voice notes can be sent to owners and in Direct conversations only.');
    iaReply($s, (string) $s['thread']->id, ['body' => ''])
        ->assertStatus(422)->assertJsonPath('message', 'Write a reply, or add a file or a voice note.');
    expect(Message::query()->where('direction', 'outbound')->count())->toBe(0);
});

it('⚠️ still works as a plain form without JavaScript — and answers in JSON when the page sends it live', function () {
    $s = iaWorld();

    iaReply($s, (string) $s['thread']->id, ['body' => 'Plain form'], json: false)
        ->assertRedirect(route('app.inbox', ['c' => $s['thread']->id]));
    iaReply($s, (string) $s['thread']->id, ['body' => 'Live'])->assertCreated()->assertJsonPath('ok', true);
    expect(Message::query()->where('thread_id', $s['thread']->id)->where('direction', 'outbound')->pluck('body')->all())->toBe(['Plain form', 'Live']);
});

it('⚠️⚠️ erases the files in a guest\'s conversations with the guest (GDPR) — not only the words', function () {
    Storage::fake('local');
    $s = iaWorld();
    iaReply($s, (string) $s['thread']->id, ['body' => 'Your passport scan', 'attachments' => [UploadedFile::fake()->image('passport.png')]])->assertCreated();
    $file = Media::query()->where('alt', 'passport.png')->sole();
    Storage::disk('local')->assertExists($file->path);

    app(TenantContext::class)->set($s['org']->id);
    Guest::query()->whereKey($s['guest']->id)->sole()->anonymize();

    expect(Media::query()->whereKey($file->id)->exists())->toBeFalse();
    Storage::disk('local')->assertMissing($file->path);
});

// ── the independent review's findings (2026-10-06) ──────────────────────────────────────────────────────────────────

it('⚠️⚠️ keeps a reply\'s files first — when the disk refuses one, nothing is sent and everything is kept (review 13)', function () {
    $s = iaWorld();
    // a private disk that refuses the write — a FILE stands where the attachments' folder must go, so no folder can be made
    // there, whoever runs the test (⚠️ not a read-only folder: root ignores permissions, and CI's container runs as root)
    $root = sys_get_temp_dir().'/vy-ia-refuses-'.bin2hex(random_bytes(4));
    mkdir($root);
    file_put_contents($root.'/message-attachments', '');
    Storage::set('local', Storage::build(['driver' => 'local', 'root' => $root]));

    iaReply($s, (string) $s['thread']->id, ['body' => 'The map', 'attachments' => [UploadedFile::fake()->image('map.png')]])
        ->assertStatus(422)->assertJsonPath('ok', false)->assertJsonPath('message', 'A file could not be saved — nothing was sent. Try again.');
    expect(Message::query()->where('thread_id', $s['thread']->id)->where('direction', 'outbound')->count())->toBe(0);
    unlink($root.'/message-attachments');
    rmdir($root);
});

it('⚠️ says so when the words went but the files could not be recorded — and leaves no file behind (review 13)', function () {
    Storage::fake('local');
    $s = iaWorld();
    Media::creating(fn () => throw new RuntimeException('The database refused.'));   // the record, after the words went

    iaReply($s, (string) $s['thread']->id, ['body' => 'The map', 'attachments' => [UploadedFile::fake()->image('map.png')]])
        ->assertCreated()->assertJsonPath('ok', true)->assertJsonPath('files_kept', true)
        ->assertJsonPath('message', 'Your message was sent, but its files could not be saved — send them again.');
    $message = Message::query()->where('thread_id', $s['thread']->id)->where('direction', 'outbound')->sole();
    expect($message->body)->toBe('The map')
        ->and(Media::query()->where('mediable_id', $message->id)->count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([]);   // the kept file went too
});

it('⚠️ downloads a file under its own words and the type its bytes earned — on the guest\'s side too (review 9)', function () {
    Storage::fake('local');
    $s = iaWorld();
    $path = tempnam(sys_get_temp_dir(), 'vy-ia');
    file_put_contents($path, "Plain words, not a script.\n");
    iaReply($s, (string) $s['thread']->id, ['body' => 'Notes', 'attachments' => [new UploadedFile($path, "notes\u{202E}txt.bat", null, null, true)]])->assertCreated();
    $file = Media::query()->where('collection', 'attachments')->latest('id')->first();

    auth()->logout();
    $page = (string) $this->actingAs($s['guest'], 'guest')->get(route('guest.messages.show', $s['thread']->id))->assertOk()->getContent();
    $download = $this->actingAs($s['guest'], 'guest')->get(route('guest.messages.attachment', (string) $file->id))->assertOk();
    expect($page)->toContain('notestxt.txt')->not->toContain("\u{202E}")->not->toContain('.bat')
        ->and((string) $download->headers->get('Content-Disposition'))->toContain('notestxt.txt')->not->toContain('.bat');
});

it('tells the size this server takes — ours, or PHP\'s own ceiling when lower — in the refusal and on the page', function () {
    // ⚠️ 🔬 this Mac's PHP takes 2 MB a file and 8 MB a request (PHP's defaults): "up to 10 MB" was not true here
    $mb = ChatFiles::limits()['mb'];
    $upload = (int) ini_get('upload_max_filesize');   // "2M" → 2 (M is the unit the Mac and the host use)
    expect($mb)->toBe($upload > 0 ? min(10, $upload) : 10);

    $s = iaWorld();
    expect((string) $this->actingAs($s['desk'])->get(route('app.inbox.team'))->assertOk()->getContent())->toContain('"mb":'.$mb.',"post":');
    iaReply($s, (string) $s['thread']->id, ['attachments' => [UploadedFile::fake()->create('big.pdf', ($mb * 1024) + 1, 'application/pdf')]])
        ->assertStatus(422)->assertJsonPath('message', 'Each file can be up to '.$mb.' MB.');
});
