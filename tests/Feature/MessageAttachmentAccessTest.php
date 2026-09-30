<?php

declare(strict_types=1);

use App\Models\Media;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\Owner;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(DatabaseTransactions::class);

// ⚠️⚠️ OWNER MESSAGE ATTACHMENTS — ONLY THE PEOPLE IN THAT CONVERSATION, SIGNED IN (2026-09-30, his ruling on DEC-m: "Fix
// this issue as well … let only people in that conversation open them, after signing in").
//
// 🔬 Before: the files sat on the PUBLIC disk and were linked by their public address — an address production refuses
// (`/storage`, KNOWN-LIMITS L12p), so they never opened; and one that, had it worked, anyone holding the link could have
// opened. The operator inbox — the side the owner sends them TO — never showed them at all. Now they are stored on the
// private disk and open only through two signed-in routes: the owner portal's (the owner scope decides whose conversation
// it is) and the inbox's (`guests.view`, this organisation).

/**
 * An owner who sent a PDF and a photo into their conversation with the operator.
 *
 * @return array{s: array<string, mixed>, a: array{user: User, owner: Owner}, thread: MessageThread, pdf: Media, image: Media}
 */
function attachmentCast(object $tc): array
{
    Storage::fake('local');
    Storage::fake('public');
    $s = seedScenario(2);
    $a = inboxOwner($s, 'Amara Okafor', 0);
    $thread = ownerThread($a['owner']);

    $tc->actingAs($a['user'])->post('/owner/messages/'.$thread->id, [
        'body' => 'The invoice, and a photo of the leak.',
        'attachments' => [
            // real bytes: `create()` only REPORTS a size — its file is empty, and a check against '' proves nothing
            UploadedFile::fake()->createWithContent('Invoice 42%.pdf', "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\ntrailer << /Root 1 0 R >>\n%%EOF\n"),
            UploadedFile::fake()->image('leak.png', 20, 20),
        ],
    ])->assertRedirect();

    $message = Message::query()->where('thread_id', $thread->id)->where('body', 'The invoice, and a photo of the leak.')->firstOrFail();
    $media = Media::query()->where('mediable_id', $message->id)->where('collection', 'attachments')->orderBy('sort_order')->get();

    return ['s' => $s, 'a' => $a, 'thread' => $thread, 'pdf' => $media[0], 'image' => $media[1]];
}

it('⚠️⚠️ keeps an owner’s attachments on the private disk and opens them for that owner through a signed-in link', function () {
    $c = attachmentCast($this);

    foreach ([$c['pdf'], $c['image']] as $m) {
        expect((string) $m->disk)->toBe('local');
        Storage::disk('local')->assertExists((string) $m->path);
        Storage::disk('public')->assertMissing((string) $m->path);
    }

    // the owner's conversation links to the signed-in route — never to a public address
    $imageUrl = route('owner.messages.attachment', $c['image']->id);
    $pdfUrl = route('owner.messages.attachment', $c['pdf']->id);
    $page = (string) $this->actingAs($c['a']['user'])->get('/owner/messages?c='.$c['thread']->id)->assertOk()->getContent();
    expect($page)->toContain('href="'.e($pdfUrl).'"')->toContain('src="'.e($imageUrl).'"')
        ->not->toContain('/storage/message-attachments')->not->toContain('/media/message-attachments');

    // a photo opens in place; nothing is kept by a shared cache or the browser
    $image = $this->actingAs($c['a']['user'])->get($imageUrl);
    $image->assertOk()->assertHeader('Content-Type', 'image/png')->assertHeader('X-Content-Type-Options', 'nosniff');
    expect((string) $image->headers->get('Content-Disposition'))->toStartWith('inline')
        ->and((string) $image->headers->get('Cache-Control'))->toContain('private')->toContain('no-store');

    // a document is a download under its own name — never rendered in the page's origin (a `%` in the name is fine)
    $pdf = $this->actingAs($c['a']['user'])->get($pdfUrl);
    $pdf->assertOk();
    expect((string) $pdf->headers->get('Content-Disposition'))->toStartWith('attachment')->toContain('Invoice 42')
        ->and($pdf->streamedContent())->toStartWith('%PDF-1.4')->toBe(Storage::disk('local')->get((string) $c['pdf']->path));
})->group('security');

it('⚠️⚠️ refuses everyone outside that conversation on the owner side — another owner, another organisation, a visitor', function () {
    $c = attachmentCast($this);
    $url = route('owner.messages.attachment', $c['pdf']->id);

    // someone the owner added to their team sees the owner's messages — they are in the conversation
    $mate = ownerSubUser($c['s'], (string) $c['a']['owner']->id, 'view_only');
    $this->actingAs($mate)->get($url)->assertOk();

    // another owner of the same organisation is not — not found, not merely forbidden
    $b = inboxOwner($c['s'], 'Bem Adeyemi', 1);
    $this->actingAs($b['user'])->get($url)->assertNotFound();

    // nor an owner of another organisation
    $far = seedScenario(1);
    $z = inboxOwner($far, 'Zara Obi', 0);
    $this->actingAs($z['user'])->get($url)->assertNotFound();

    // a visitor who is not signed in is sent to sign in, and gets no file
    $guest = $this->actingAsGuest()->get($url);
    $guest->assertRedirect();
    expect((string) $guest->getContent())->not->toContain(Storage::disk('local')->get((string) $c['pdf']->path));
})->group('security');

it('⚠️⚠️ shows the attachments in the operator inbox and opens them for inbox staff of that organisation only', function () {
    $c = attachmentCast($this);
    $pdfUrl = route('app.inbox.attachment', $c['pdf']->id);
    $imageUrl = route('app.inbox.attachment', $c['image']->id);

    // the side the owner sent them TO can now see them, under the message, and open them
    $desk = operatorUser($c['s'], [], 'Front Desk');
    $page = (string) $this->actingAs($desk)->get(route('app.inbox', ['c' => $c['thread']->id]))->assertOk()->getContent();
    expect($page)->toContain('href="'.e($pdfUrl).'"')->toContain('src="'.e($imageUrl).'"')->toContain('Invoice 42%.pdf');
    $this->actingAs($desk)->get($pdfUrl)->assertOk();
    $this->actingAs($desk)->get($imageUrl)->assertOk()->assertHeader('Content-Type', 'image/png');

    // a role that cannot open the inbox cannot open its files either
    $this->actingAs(operatorUser($c['s'], [], 'Housekeeping'))->get($pdfUrl)->assertForbidden();

    // inbox staff of another organisation
    $far = seedScenario(1);
    $this->actingAs(operatorUser($far, [], 'Front Desk'))->get($pdfUrl)->assertNotFound();

    // each realm's door refuses the other realm's people
    expect($this->actingAs($c['a']['user'])->get($pdfUrl)->isOk())->toBeFalse()
        ->and($this->actingAs($desk)->get(route('owner.messages.attachment', $c['pdf']->id))->isOk())->toBeFalse();
})->group('security');

it('opens nothing but a message attachment — another file’s id through the same door is not found', function () {
    $c = attachmentCast($this);
    $photo = Media::create([
        'mediable_type' => $c['s']['roomType']->getMorphClass(), 'mediable_id' => $c['s']['roomType']->id,
        'collection' => 'photos', 'disk' => 'public', 'path' => 'room-photos/'.$c['s']['roomType']->id.'/X.jpg',
        'mime' => 'image/jpeg', 'size' => 10, 'sort_order' => 0, 'is_cover' => true, 'alt' => 'Std photo',
    ]);

    $this->actingAs($c['a']['user'])->get(route('owner.messages.attachment', $photo->id))->assertNotFound();
    $this->actingAs(operatorUser($c['s'], [], 'Front Desk'))->get(route('app.inbox.attachment', $photo->id))->assertNotFound();
    $this->actingAs($c['a']['user'])->get(route('owner.messages.attachment', '01ZZZZZZZZZZZZZZZZZZZZZZZZ'))->assertNotFound();
})->group('security');
