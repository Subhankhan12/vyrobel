<?php

declare(strict_types=1);

use App\Livewire\Operator\GuestsList;
use App\Models\Guest;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

// ⚠️ GUESTS — THE PAGE REBUILT (2026-09-29, the founder's report; design approved the same day).
//   · "The Add guest dialog is not in the centre" — nor was any dialog on the page, nor the profile's Edit: each
//     centred itself with an INLINE display that x-show erases (see XShowInlineDisplayContractTest).
//   · The table sorts by Guest, Stays and Last stay, pages 25 / 50 / 100 with a real footer, and every link keeps the
//     other settings. Search says honestly what it can find.
//   · Every row has actions — open, edit (guests.manage), erase personal data (guests.erase) — and from the list both
//     come back to the list, where you were.

uses(DatabaseTransactions::class);

beforeEach(function () {
    // Stages and "Last stay" are date-dependent; book() stays 1→3 Sep, so freeze mid-stay (as OperatorGuestsListTest).
    Carbon::setTestNow(Carbon::parse('2026-09-02 09:00:00'));
});

function glrGuest(string $first, string $last, array $extra = []): Guest
{
    return Guest::create(array_merge(['first_name' => $first, 'last_name' => $last], $extra));
}

/** A stay for $guest on its OWN unit — book() always stays 1→3 Sep, so a shared unit trips the no-double-booking guard. */
function glrStay(array $s, int $unit, Guest $guest, string $status, string $in, string $out): void
{
    $reservation = book($s, ['unit_id' => $s['units'][$unit]->id]);

    Reservation::query()->whereKey($reservation->getKey())->update([
        'lead_guest_id' => $guest->id, 'status' => $status, 'check_in' => $in, 'check_out' => $out,
    ]);
}

/**
 * The cast of the sorting tests. ⚠️ Chosen so that sorting "Last stay" by the last stay's CHECK-IN would give a
 * different order from the dates the column SHOWS: Bea's long stay began first and ended last (1→20 Aug), Dee's short
 * one began later and ended sooner (10→12 Aug). The column shows departures, so Bea is the more recent of the two.
 */
function glrCast(array $s): array
{
    $ada = glrGuest('Ada', 'Adams');     // 3 stays; the latest is UPCOMING → "Arrives 5 Oct 2026"
    glrStay($s, 0, $ada, 'confirmed', '2026-10-05', '2026-10-08');
    glrStay($s, 1, $ada, 'checked_out', '2026-06-01', '2026-06-03');
    glrStay($s, 2, $ada, 'checked_out', '2026-05-01', '2026-05-04');

    $bea = glrGuest('Bea', 'Brown');     // 1 stay → "20 Aug 2026"
    glrStay($s, 4, $bea, 'checked_out', '2026-08-01', '2026-08-20');

    $cy = glrGuest('Cy', 'Clark');       // never booked → "—", last in BOTH directions

    // 2 stays + THREE bookings that are not stays (later than both) → "12 Aug 2026". ⚠️ Counted wrongly, Dee has 5 and
    // outranks Ada's 3 — so a Stays sort that counted cancellations cannot hide behind a tie.
    $dee = glrGuest('Dee', 'Diaz');
    glrStay($s, 5, $dee, 'checked_out', '2026-08-10', '2026-08-12');
    glrStay($s, 6, $dee, 'checked_out', '2026-07-01', '2026-07-05');
    glrStay($s, 7, $dee, 'cancelled', '2026-09-20', '2026-09-22');
    glrStay($s, 8, $dee, 'no_show', '2026-08-25', '2026-08-26');
    glrStay($s, 3, $dee, 'cancelled', '2026-11-01', '2026-11-03');

    $eve = glrGuest('Eve', 'Evans');     // checked in now → "In house", which sits at today
    glrStay($s, 9, $eve, 'checked_in', '2026-09-01', '2026-09-04');

    return compact('ada', 'bea', 'cy', 'dee', 'eve');
}

/** @return list<array<string, mixed>> the rows of the page the query asks for */
function glrRows(array $s, array $params, string $role = 'Hotel Manager'): array
{
    $rows = Livewire::actingAs(operatorUser($s, [], $role))->withQueryParams($params)->test(GuestsList::class)->viewData('rows');

    return collect($rows->items())->all();
}

/** @return list<string> */
function glrNames(array $s, array $params): array
{
    return array_column(glrRows($s, $params), 'name');
}

// ── The dialogs ─────────────────────────────────────────────────────────────────────────────────

it('⚠️⚠️ centres every dialog on the page, and the profile\'s Edit, by the shared class', function () {
    $dialogs = array_values(array_filter(vyDialogCensus(), fn (array $d): bool => in_array($d['file'], [
        'livewire/operator/guests-list.blade.php', 'livewire/operator/guest-profile.blade.php',
    ], true)));

    $byFile = array_count_values(array_column($dialogs, 'file'));
    // Message · Add tag · Erase (bulk) · Add guest · Edit · Erase (one) — and the profile's Edit
    expect($byFile['livewire/operator/guests-list.blade.php'] ?? 0)->toBe(6)
        ->and($byFile['livewire/operator/guest-profile.blade.php'] ?? 0)->toBe(1);

    foreach ($dialogs as $d) {
        expect(str_contains($d['backdrop'], 'vy-modal-backdrop'))->toBeTrue($d['file'].' x-show="'.$d['show'].'" is not centred by the class')
            ->and(str_contains($d['panel'], 'vy-modal'))->toBeTrue($d['file'].' x-show="'.$d['show'].'" does not use the shared panel');
    }
});

// ── Sorting ─────────────────────────────────────────────────────────────────────────────────────

it('⚠️⚠️ sorts by guest, stays or last stay — last stay by the date the column SHOWS, never-stayed guests last both ways', function () {
    $s = seedScenario(10);
    glrCast($s);
    // The scenario's own guest ("G One") never stays either — every booking above is handed to the cast.

    expect(glrNames($s, []))->toBe(['Ada Adams', 'Bea Brown', 'Cy Clark', 'Dee Diaz', 'Eve Evans', 'G One'])
        ->and(glrNames($s, ['sort' => 'name', 'dir' => 'desc']))->toBe(['G One', 'Eve Evans', 'Dee Diaz', 'Cy Clark', 'Bea Brown', 'Ada Adams']);

    // Stays: most first by default, a cancelled or no-show booking is not a stay, ties go A–Z by surname.
    expect(glrNames($s, ['sort' => 'stays']))->toBe(['Ada Adams', 'Dee Diaz', 'Bea Brown', 'Eve Evans', 'Cy Clark', 'G One'])
        ->and(glrNames($s, ['sort' => 'stays', 'dir' => 'asc']))->toBe(['Cy Clark', 'G One', 'Bea Brown', 'Eve Evans', 'Dee Diaz', 'Ada Adams'])
        ->and(array_column(glrRows($s, ['sort' => 'stays']), 'stays'))->toBe([3, 2, 1, 1, 0, 0]);

    // Last stay: most recent first by default. Bea (left 20 Aug) before Dee (left 12 Aug) — by check-in it would be the
    // other way round. "In house" sits at today, between the arrival ahead and the departures behind.
    expect(glrNames($s, ['sort' => 'last_stay']))->toBe(['Ada Adams', 'Eve Evans', 'Bea Brown', 'Dee Diaz', 'Cy Clark', 'G One'])
        ->and(array_column(glrRows($s, ['sort' => 'last_stay']), 'last_stay'))
        ->toBe(['Arrives 5 Oct 2026', 'In house', 'Left 20 Aug 2026', 'Left 12 Aug 2026', null, null])   // "Left …" since 2026-10-05 (the column is "Stay")
        ->and(glrNames($s, ['sort' => 'last_stay', 'dir' => 'asc']))->toBe(['Dee Diaz', 'Bea Brown', 'Eve Evans', 'Ada Adams', 'Cy Clark', 'G One']);
})->group('security');

it('⚠️ refuses a sort or a direction it does not offer — never an arbitrary column, never an error', function () {
    $s = seedScenario(10);
    glrCast($s);
    $az = ['Ada Adams', 'Bea Brown', 'Cy Clark', 'Dee Diaz', 'Eve Evans', 'G One'];

    // ⚠️ `email` matters most: ordering by an encrypted column would leak nothing useful and cost a full scan, and the
    //    list must never become a way to order people by a field it does not show.
    foreach (['email', 'phone', 'id; drop table guests', 'created_at'] as $sort) {
        expect(glrNames($s, ['sort' => $sort]))->toBe($az);
    }

    expect(glrNames($s, ['sort' => 'stays', 'dir' => 'sideways']))->toBe(glrNames($s, ['sort' => 'stays']));

    $this->actingAs(operatorUser($s))->get('/app/guests?sort[]=x&dir[]=y&per[]=z')->assertOk();
})->group('security');

// ── Paging ──────────────────────────────────────────────────────────────────────────────────────

it('⚠️ pages 25, 50 or 100 at a time — any other size falls back to 25', function () {
    $s = seedScenario(1);
    Guest::factory()->count(60)->create(['organization_id' => $s['org']->id]);

    $size = fn (array $params): int => Livewire::actingAs(operatorUser($s))->withQueryParams($params)->test(GuestsList::class)->viewData('rows')->perPage();

    expect($size([]))->toBe(GuestsList::PER_PAGE)
        ->and($size(['per' => '50']))->toBe(50)
        ->and($size(['per' => '100']))->toBe(100)
        ->and($size(['per' => '37']))->toBe(25)
        ->and($size(['per' => '100000']))->toBe(25)
        ->and($size(['per' => '-1']))->toBe(25);
});

it('⚠️ keeps every setting on every link — filter, sort, page size and search travel together', function () {
    $s = seedScenario(1);
    Guest::factory()->count(60)->create(['organization_id' => $s['org']->id, 'last_name' => 'Pageable', 'is_vip' => true]);

    $html = (string) $this->actingAs(operatorUser($s))
        ->get('/app/guests?filter=vip&sort=stays&dir=asc&per=50&q=Pageable')->assertOk()->getContent();
    $links = glrLinks($html);

    expect(array_keys($links))->toContain('chip-all', 'chip-vip', 'sort-name', 'sort-stays', 'sort-last_stay', 'per-25', 'per-100', 'page-next');

    // the "All" filter drops the filter and keeps the rest
    expect($links['chip-all'])->not->toContain('filter=');
    expect($links['chip-all'])->toContain('sort=stays', 'dir=asc', 'per=50', 'q=Pageable');

    // sorting by guest (the default, so no sort in the URL) keeps the filter, the size and the search — from page 1
    expect($links['sort-name'])->toContain('filter=vip', 'per=50', 'q=Pageable');
    expect($links['sort-name'])->not->toContain('sort=stays');
    expect($links['sort-name'])->not->toContain('page=');

    // the sorted column's own link turns it round (desc is the Stays default, so the URL just says stays)
    expect($links['sort-stays'])->toContain('sort=stays', 'filter=vip');
    expect($links['sort-stays'])->not->toContain('dir=asc');

    // a page size keeps everything else, from page 1
    expect($links['per-100'])->toContain('per=100', 'filter=vip', 'sort=stays', 'dir=asc', 'q=Pageable');
    expect($links['per-100'])->not->toContain('page=');

    // the next page keeps everything
    expect($links['page-next'])->toContain('page=2', 'filter=vip', 'sort=stays', 'dir=asc', 'per=50', 'q=Pageable');
});

/** @return array<string, string> each keyed control link on the page (`data-gl-key`) → its decoded href */
function glrLinks(string $html): array
{
    $links = [];
    preg_match_all('/<a\b[^>]*>/', $html, $tags);
    foreach ($tags[0] as $tag) {
        if (preg_match('/data-gl-key="([^"]+)"/', $tag, $key) === 1 && preg_match('/href="([^"]*)"/', $tag, $href) === 1) {
            $links[$key[1]] = html_entity_decode($href[1]);
        }
    }

    return $links;
}

it('⚠️ shows where you are in a real footer — outside the table, never inside one of its rows', function () {
    $s = seedScenario(1);
    Guest::factory()->count(30)->create(['organization_id' => $s['org']->id]);

    $page = $this->actingAs(operatorUser($s))->get('/app/guests')->assertOk();
    $html = (string) $page->getContent();

    // 30 made here + the scenario's own guest
    $page->assertSeeText('1–25 of 31 guests');

    preg_match('/<tbody\b.*?<\/tbody>/s', $html, $tbody);
    expect($tbody)->not->toBeEmpty();
    expect(str_contains($tbody[0], '<nav'))->toBeFalse('the pager used to render INSIDE <tbody> — invalid markup')
        ->and(str_contains($tbody[0], 'data-gl-foot'))->toBeFalse();
    expect($html)->toContain('data-gl-foot');

    // the sorted column says so, for screen readers too
    expect($html)->toContain('aria-sort="ascending"');
    assertNoRawBlade($page);
});

// ── Search, honestly ────────────────────────────────────────────────────────────────────────────

it('⚠️ says honestly what search can find — and no filter is described as another', function () {
    $s = seedScenario(1);

    $page = $this->actingAs(operatorUser($s))->get('/app/guests')->assertOk();
    $page->assertSee('placeholder="Search name, city or exact email"', false)
        ->assertDontSee('Search by name, email or phone', false);

    // The old footer called EVERY filter but VIP "flagged at risk" — In-house and Upcoming included.
    $this->actingAs(operatorUser($s))->get('/app/guests?filter=in_house')->assertOk()->assertDontSeeText('flagged at risk');
    $this->actingAs(operatorUser($s))->get('/app/guests?filter=upcoming')->assertOk()->assertDontSeeText('flagged at risk');
});

// ── Row actions: who sees what ──────────────────────────────────────────────────────────────────

it('⚠️⚠️ offers Edit only with guests.manage and Erase only with guests.erase', function () {
    $s = seedScenario(1);
    glrGuest('Ivy', 'Irwin', ['email' => 'ivy.irwin@example.test']);

    // Property Owner — manage + erase
    $this->actingAs(operatorUser($s, [], 'Property Owner'))->get('/app/guests')->assertOk()
        ->assertSee('id="vy-gl-edit-t"', false)
        ->assertSee('id="vy-gl-erase-t"', false);

    // Hotel Manager — manage, not erase
    $this->actingAs(operatorUser($s, [], 'Hotel Manager'))->get('/app/guests')->assertOk()
        ->assertSee('id="vy-gl-edit-t"', false)
        ->assertDontSee('id="vy-gl-erase-t"', false);

    // Marketing Manager — view only: no Edit, no Erase, no Add guest
    $this->actingAs(operatorUser($s, [], 'Marketing Manager'))->get('/app/guests')->assertOk()
        ->assertDontSee('id="vy-gl-edit-t"', false)
        ->assertDontSee('id="vy-gl-erase-t"', false)
        ->assertDontSee('id="vy-gl-add-t"', false)
        ->assertSee('Ivy Irwin');
})->group('security');

it('⚠️ the list\'s Edit form tells the server to keep the stay preferences it does not show', function () {
    $s = seedScenario(1);

    $this->actingAs(operatorUser($s))->get('/app/guests')->assertOk()
        ->assertSee('name="keep_preferences" value="1"', false)
        ->assertSee('name="return" value="list"', false);
});

// ── Row actions: the writes ─────────────────────────────────────────────────────────────────────

it('⚠️⚠️ edits a guest from the list and comes back to the list, where you were', function () {
    $s = seedScenario(1);
    $guest = glrGuest('Ivy', 'Irwin');

    $this->actingAs(operatorUser($s))->from('/app/guests?filter=vip&sort=stays&page=2')
        ->post(route('app.guests.update', $guest->id), [
            'first_name' => 'Ivy', 'last_name' => 'Irving', 'email' => 'ivy@example.test', 'nationality' => 'IE',
            'keep_preferences' => '1', 'return' => 'list', 'return_query' => 'filter=vip&sort=stays&page=2&evil=1&q[]=x',
        ])
        ->assertRedirect(route('app.guests').'?filter=vip&sort=stays&page=2')
        ->assertSessionHas('guest_notice');

    $fresh = $guest->fresh();
    expect($fresh->last_name)->toBe('Irving')->and($fresh->email)->toBe('ivy@example.test')->and($fresh->nationality)->toBe('IE');
})->group('security');

it('⚠️⚠️ keeps the guest\'s stay preferences when the list edits them — the list form does not carry them', function () {
    $s = seedScenario(1);
    $guest = glrGuest('Pia', 'Prefs', ['preferences' => ['quiet_room', 'late_checkout'], 'risk_flag' => true]);

    // From the list: the preferences are not in the form, and the marker says so — they stay as they were.
    $this->actingAs(operatorUser($s))->from('/app/guests')->post(route('app.guests.update', $guest->id), [
        'first_name' => 'Pia', 'last_name' => 'Prefs', 'risk_flag' => '1', 'keep_preferences' => '1', 'return' => 'list',
    ])->assertRedirect(route('app.guests'));
    expect($guest->fresh()->preferences)->toBe(['quiet_room', 'late_checkout']);   // untouched, as stored

    // From the profile (no marker): the form carries every preference checkbox, so an empty set means "none" — as before.
    $this->actingAs(operatorUser($s))->from(route('app.guests.show', $guest->id))->post(route('app.guests.update', $guest->id), [
        'first_name' => 'Pia', 'last_name' => 'Prefs', 'risk_flag' => '1',
    ])->assertRedirect(route('app.guests.show', $guest->id));
    expect($guest->fresh()->preferences)->toBe([]);
})->group('security');

it('⚠️⚠️ erases from the list only with ERASE typed, and comes back to the list', function () {
    $s = seedScenario(1);
    $owner = operatorUser($s, [], 'Property Owner');
    $guest = glrGuest('Zed', 'Zero', ['email' => 'zed.zero@example.test']);

    // no word, or the wrong word — nothing is erased, and you are back on the list with the reason
    foreach (['', 'erase', 'DELETE'] as $typed) {
        $this->actingAs($owner)->from('/app/guests?page=1')->post(route('app.guests.anonymize', $guest->id), [
            'confirm' => $typed, 'return' => 'list', 'return_query' => 'page=1',
        ])->assertRedirect('/app/guests?page=1')->assertSessionHasErrors('confirm');
        expect($guest->fresh()->isAnonymized())->toBeFalse();
    }

    $this->actingAs($owner)->from('/app/guests?sort=last_stay')->post(route('app.guests.anonymize', $guest->id), [
        'confirm' => 'ERASE', 'return' => 'list', 'return_query' => 'sort=last_stay',
    ])->assertRedirect(route('app.guests').'?sort=last_stay')->assertSessionHas('guest_notice');

    $fresh = $guest->fresh();
    expect($fresh->isAnonymized())->toBeTrue()->and($fresh->first_name)->toBeNull()->and($fresh->email)->toBeNull();

    // and a role without guests.erase cannot, whatever it types
    $other = glrGuest('Yan', 'Yes');
    $this->actingAs(operatorUser($s, [], 'Hotel Manager'))->post(route('app.guests.anonymize', $other->id), [
        'confirm' => 'ERASE', 'return' => 'list',
    ])->assertForbidden();
    expect($other->fresh()->isAnonymized())->toBeFalse();
})->group('security');

it('⚠️⚠️ never sends you anywhere but the Guests list on the way back — the return is rebuilt from an allow-list', function () {
    $s = seedScenario(1);
    $guest = glrGuest('Ola', 'Open');
    $list = route('app.guests');

    foreach (['//evil.example/x', 'https://evil.example/', '/app/billing', "filter=vip\r\nLocation: https://evil.example", 'q=%0d%0aSet-Cookie:x'] as $rq) {
        $response = $this->actingAs(operatorUser($s))->post(route('app.guests.update', $guest->id), [
            'first_name' => 'Ola', 'last_name' => 'Open', 'keep_preferences' => '1', 'return' => 'list', 'return_query' => $rq,
        ]);
        $target = (string) $response->headers->get('Location');
        // ⚠️ the PATH, exactly — the profile's own URL also STARTS with the list's, which is how a first draft of this
        //    check passed before the feature existed.
        expect(parse_url($target, PHP_URL_PATH))->toBe(parse_url($list, PHP_URL_PATH), "return_query={$rq} sent you to {$target}")
            ->and(parse_url($target, PHP_URL_HOST))->toBe(parse_url($list, PHP_URL_HOST))
            ->and($target)->not->toContain("\n")
            ->and($target)->not->toContain('evil');
    }

    // an unknown `return` is the profile's own behaviour — back to the profile
    $this->actingAs(operatorUser($s))->post(route('app.guests.update', $guest->id), [
        'first_name' => 'Ola', 'last_name' => 'Open', 'return' => 'https://evil.example',
    ])->assertRedirect(route('app.guests.show', $guest->id));
})->group('security');

it('⚠️ an edit the server refuses reopens with what was typed, says why inside the dialog, and saves nothing', function () {
    $s = seedScenario(1);
    $guest = glrGuest('Rae', 'Retry', ['email' => 'rae.retry@example.test', 'city' => 'Leeds']);
    $manager = operatorUser($s);

    // ⚠️ No assertSessionHasErrors() here: TestResponse::session() STARTS the session store, and the flashed input is then
    //    gone before the follow-up request below (🖥 a probe showed the block rendering without it and missing with it).
    //    The refusal is proven by what the next page renders, which is the stronger check anyway.
    $this->actingAs($manager)->from('/app/guests?filter=vip')->post(route('app.guests.update', $guest->id), [
        'first_name' => 'Rae', 'last_name' => 'Retry', 'email' => 'not-an-email', 'city' => 'Manchester',
        'keep_preferences' => '1', 'return' => 'list', '_guest' => $guest->id,
    ])->assertRedirect('/app/guests?filter=vip');

    $html = (string) $this->actingAs($manager)->get('/app/guests?filter=vip')->assertOk()->getContent();
    // (Livewire wraps a loop in <!--[if BLOCK]--> markers, so the block is read by position, not by its closing tags.)
    $at = strpos($html, '<div class="vy-gl-errors" role="alert">');
    expect($at)->not->toBeFalse('the reason is inside the Edit dialog, not only in the flash behind it');
    expect(substr($html, (int) $at, 400))->toContain('valid email address');
    expect(strpos($html, 'id="vy-gl-edit-t"'))->toBeLessThan($at);   // and it sits in the Edit dialog
    // the dialog reopens on what was typed — the boot data carries it back
    expect($html)->toContain('Manchester')->toContain('not-an-email');

    $fresh = $guest->fresh();
    expect($fresh->email)->toBe('rae.retry@example.test')->and($fresh->city)->toBe('Leeds');
});

it('⚠️ a refused edit from the list comes back to the list with its reason — not to the profile', function () {
    $s = seedScenario(1);
    $erased = glrGuest('Old', 'Name');
    $erased->anonymize();

    $this->actingAs(operatorUser($s))->post(route('app.guests.update', $erased->id), [
        'first_name' => 'New', 'last_name' => 'Name', 'keep_preferences' => '1', 'return' => 'list', 'return_query' => 'filter=vip',
    ])->assertRedirect(route('app.guests').'?filter=vip')->assertSessionHas('guest_error');

    expect(DB::table('guests')->where('id', $erased->id)->value('first_name'))->toBeNull();
})->group('security');
