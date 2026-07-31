<?php

use App\Models\Reservation;
use App\Models\Review;
use App\Services\ReviewService;
use App\Support\Reviews\ReviewRating;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// #104 GATE 2b — the operator Reviews summary dashboard (overall rating + 5-star distribution + reply rate) is a
// REAL aggregate over the org's reviews via the shared ReviewRating; the status filter is driven by the real column.

function completedStay(array $s, string $in, string $out): Reservation
{
    return Reservation::create([
        'property_id' => $s['property']->id,
        'reference' => 'VYR-'.strtoupper(substr(md5($in.$out), 0, 5)),
        'status' => 'checked_out',
        'source' => 'direct',
        'lead_guest_id' => $s['guest']->id,
        'check_in' => $in,
        'check_out' => $out,
        'currency' => 'EUR',
    ]);
}

it('computes a real reputation summary (rating, distribution, reply rate, pending)', function () {
    $s = seedScenario(1);
    $svc = app(ReviewService::class);

    $r5 = $svc->submit($s['guest'], completedStay($s, '2026-08-01', '2026-08-03'), ['rating' => 5, 'body' => 'Great']);
    $r3 = $svc->submit($s['guest'], completedStay($s, '2026-08-05', '2026-08-07'), ['rating' => 3, 'body' => 'Ok']);
    $svc->submit($s['guest'], completedStay($s, '2026-08-09', '2026-08-11'), ['rating' => 4, 'body' => 'Pending one']);

    // Publish two; answer one.
    $r5->update(['status' => Review::STATUS_PUBLISHED, 'visibility' => Review::VISIBILITY_PUBLIC, 'responded_at' => now(), 'operator_response' => 'Thanks!']);
    $r3->update(['status' => Review::STATUS_PUBLISHED, 'visibility' => Review::VISIBILITY_PUBLIC]);
    // the 4★ stays pending

    $summary = ReviewRating::operatorSummary();
    expect($summary['count'])->toBe(2)
        ->and($summary['average'])->toBe(4.0)          // (5+3)/2
        ->and($summary['distribution'][5])->toBe(1)
        ->and($summary['distribution'][3])->toBe(1)
        ->and($summary['distribution'][4])->toBe(0)    // the 4★ is pending, not counted in published rating
        ->and($summary['responded'])->toBe(1)
        ->and($summary['reply_rate'])->toBe(50)
        ->and($summary['pending'])->toBe(1);
});

it('renders the summary strip and filters the list by status', function () {
    $s = seedScenario(1);
    $svc = app(ReviewService::class);
    $pub = $svc->submit($s['guest'], completedStay($s, '2026-08-01', '2026-08-03'), ['rating' => 5, 'body' => 'Published review body']);
    $pub->update(['status' => Review::STATUS_PUBLISHED, 'visibility' => Review::VISIBILITY_PUBLIC]);
    $svc->submit($s['guest'], completedStay($s, '2026-08-05', '2026-08-07'), ['rating' => 2, 'body' => 'Pending review body']);

    $manager = operatorUser($s, [], 'Hotel Manager');

    $this->actingAs($manager)->get('/app/reviews')
        ->assertOk()
        ->assertSee('Overall rating')
        ->assertSee('Reply rate')
        ->assertSee('Published review body')
        ->assertSee('Pending review body');

    // filter=published → only the published one.
    $this->actingAs($manager)->get('/app/reviews?filter=published')
        ->assertOk()
        ->assertSee('Published review body')
        ->assertDontSee('Pending review body');
});
