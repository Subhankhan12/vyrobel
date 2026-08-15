<?php

/*
 * D34-1 — THE SCHEDULE PDF STATES THE REAL PUBLICATION STATE AND THE REAL OPEN SHIFTS (2026-08-15).
 *
 * ⚠️ THIS CLOSES THE LOOP DO-CORRECTNESS-1 OPENED. That pass removed two false sentences from this document
 * and left it SILENT on publication/open-shifts, because `RosterWeek` — the ONE builder the PDF and the
 * on-screen grid share — had no accessor for either, and a PDF template must never query models to work
 * around that. The accessors now exist, so the document can say what is true instead of saying nothing.
 *
 * ⚠️ THE ANTI-DRIFT RULE IS THE POINT, NOT A FORMALITY. The grid queries `SchedulePublication`/`OpenShift`
 * directly today. If the PDF had done the same, the two would disagree the first time either read changed —
 * a different week boundary, a different filter, a different ordering. `publicationState()`/`openShifts()`
 * read the models EXACTLY as `OperatorSchedule::publishedState()`/`openShiftsFor()` do, and the PARITY test
 * below asserts that equality directly rather than trusting the copy.
 *
 * ⚠️ AND EVERYTHING PRINTED MUST STILL BE VERIFIABLY TRUE — that is what DO-CORRECTNESS-1 was about, so this
 * pass must not undo it by adding a NEW unbacked claim. One design decision follows from that:
 * `publicationState()` returns a TIMESTAMP, not the grid's `diffForHumans()` string. "2 hours ago" is right
 * on a screen and false on paper the moment the sheet is carried out of the office.
 *
 * ⚠️ VERIFIED OFF THE DEMO PATH. Publication and open shifts are exactly the conditions the demo does not
 * carry, so "the demo PDF looks fine" proves nothing (the unrendered sibling branch, §N.13.1). Every state is
 * FORCED here: published AND draft, open-shifts-present AND none.
 */

use App\Livewire\Operator\OperatorSchedule;
use App\Models\OpenShift;
use App\Models\Property;
use App\Models\SchedulePublication;
use App\Support\Schedule\RosterWeek;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * The words a reader actually sees, decompressed from the PDF's own content streams — letters only, so
 * dompdf's per-glyph spacing and line wrapping cannot hide or fake a match.
 * ⚠️ Prefixed — a top-level function in a Pest file is GLOBAL and has collided in this suite before.
 */
function d341PdfText(string $bytes): string
{
    $text = '';
    if (preg_match_all('/stream\r?\n(.*?)endstream/s', $bytes, $streams)) {
        foreach ($streams[1] as $stream) {
            $plain = @gzuncompress($stream);
            if ($plain === false) {
                continue;
            }
            if (preg_match_all('/\((?:\\\\.|[^()\\\\])*\)/', $plain, $shown)) {
                foreach ($shown[0] as $chunk) {
                    $text .= str_replace(['\\(', '\\)'], ['(', ')'], substr($chunk, 1, -1));
                }
            }
        }
    }

    return strtolower((string) preg_replace('/[^A-Za-z]/', '', $text));
}

/** An org + property + a manager who can view the schedule. */
function d341Scenario(): array
{
    $org = newDemoOrg();
    $property = Property::create(['name' => 'Alpha House', 'type' => 'hotel', 'currency' => 'EUR', 'timezone' => 'UTC']);
    $s = ['org' => $org];
    $manager = operatorUser($s, ['name' => 'Rosa Manager'], 'Hotel Manager');

    return ['org' => $org, 'property' => $property, 'manager' => $manager];
}

/** The rendered PDF text for this week. */
function d341Render(array $ctx): string
{
    $response = test()->actingAs($ctx['manager'])
        ->withSession(['current_property_id' => $ctx['property']->id])
        ->get('/app/schedule/pdf')
        ->assertOk();

    return d341PdfText((string) $response->getContent());
}

/** The Monday `RosterWeek` uses for week 0, in the property's timezone. */
function d341WeekStart(Property $property): string
{
    return CarbonImmutable::now((string) ($property->timezone ?? 'UTC'))
        ->startOfWeek(CarbonImmutable::MONDAY)->toDateString();
}

it('⚠️ D34-1 — a DRAFT week renders as not-yet-published (the demo default branch)', function () {
    $ctx = d341Scenario();

    // ⚠️ NON-VACUITY: there really is no publication, so "draft" is the true state and not a render failure.
    expect(SchedulePublication::query()->count())->toBe(0);

    $rendered = d341Render($ctx);

    expect(strlen($rendered))->toBeGreaterThan(500)->and($rendered)->toContain('weeklyrota');   // anchors
    expect($rendered)->toContain('notyetpublished')
        ->and($rendered)->toContain('thisweekhasnotbeensenttotheteam')
        // ⚠️ DO-CORRECTNESS-1's removals must STAY removed — this pass adds truth, it does not restore claims.
        ->and($rendered)->not->toContain('thereisnodraftorpublishedstate')
        ->and($rendered)->not->toContain('statutoryrestperiods')
        ->and($rendered)->toContain('snapshotoftherosterasitstoodon');
});

it('⚠️ D34-1 — a PUBLISHED week renders when and by whom (the branch the demo NEVER shows)', function () {
    $ctx = d341Scenario();

    // ⚠️ FORCED THROUGH THE REAL WRITE PATH, not by hand-stamping a row: the same gated controller action the
    // Publish button posts to. That proves the accessor reads what the product actually writes.
    test()->actingAs($ctx['manager'])
        ->withSession(['current_property_id' => $ctx['property']->id])
        ->post(route('app.schedule.publish'), ['week' => 0, 'dept' => 'all'])
        ->assertRedirect();

    $publication = SchedulePublication::query()->first();
    expect($publication)->not->toBeNull()                                   // non-vacuity
        ->and($publication->published_by)->toBe($ctx['manager']->getKey());

    $rendered = d341Render($ctx);

    expect($rendered)->toContain('published')
        // The real publisher, from `published_by` — a name, never an invented "Unknown".
        ->and($rendered)->toContain('rosamanager')
        ->and($rendered)->not->toContain('notyetpublished');

    // ⚠️ AND THE DATE IS ABSOLUTE. A relative phrase would be false as soon as the sheet is printed.
    // ⚠️ The expected value is normalised the SAME way the haystack is — a first draft compared a spaced,
    // digit-bearing date against letters-only text and failed for its own reason, not the code's.
    $expectedDate = strtolower((string) preg_replace('/[^A-Za-z]/', '', CarbonImmutable::parse($publication->published_at)
        ->timezone((string) $ctx['property']->timezone)->format('D j M Y')));
    expect($expectedDate)->not->toBe('')                       // the normalisation itself is not vacuous
        ->and($rendered)->toContain($expectedDate);

    // ⚠️ NO RELATIVE PHRASE LEAKED ONTO PAPER. Asserted as the diffForHumans SHAPE the grid produces
    // ("… hours ago" / "… minutes ago"), not on the bare substring "ago" — in letters-only text that would
    // collide with ordinary words and fail for the wrong reason.
    foreach (['secondsago', 'minutesago', 'minuteago', 'hoursago', 'hourago', 'daysago', 'dayago'] as $relative) {
        expect($rendered)->not->toContain($relative);
    }
});

it('⚠️ D34-1 — open shifts render with their day, shift and department; the empty case is a positive statement', function () {
    $ctx = d341Scenario();
    $weekStart = d341WeekStart($ctx['property']);

    // ── EMPTY FIRST — the branch that is easy to ship broken, because "nothing here" and "this section
    //    failed to render" look identical on paper unless the document says so.
    expect(OpenShift::query()->count())->toBe(0);
    $empty = d341Render($ctx);
    expect($empty)->toContain('openshifts')
        ->and($empty)->toContain('everyshiftonthisrotaisassigned');

    // ── NOW POPULATED, through the real gated write path.
    test()->actingAs($ctx['manager'])
        ->withSession(['current_property_id' => $ctx['property']->id])
        ->post(route('app.schedule.open.create'), [
            'date' => CarbonImmutable::parse($weekStart)->addDay()->toDateString(),
            'shift_type' => 'late',
            'department' => 'housekeeping',
        ])->assertRedirect();

    expect(OpenShift::query()->count())->toBe(1);                            // non-vacuity

    $rendered = d341Render($ctx);
    expect($rendered)->toContain('openshifts')
        ->and($rendered)->toContain('late')                                  // the shift label
        ->and($rendered)->toContain('housekeeping')                          // the real department
        ->and($rendered)->not->toContain('everyshiftonthisrotaisassigned');  // the empty copy is gone
});

it('⚠️ D34-1 — PARITY: the builder returns exactly what the GRID reads (the anti-drift guarantee)', function () {
    $ctx = d341Scenario();
    $property = $ctx['property'];
    $weekStart = d341WeekStart($property);

    // Two open shifts on different days + a publication, so the comparison has real content on both sides.
    foreach (['early', 'late'] as $i => $type) {
        OpenShift::create([
            'organization_id' => $ctx['org']->id,
            'property_id' => $property->id,
            'date' => CarbonImmutable::parse($weekStart)->addDays($i + 1)->toDateString(),
            'shift_type' => $type,
            'department' => $i === 0 ? 'frontdesk' : null,
        ]);
    }
    SchedulePublication::create([
        'organization_id' => $ctx['org']->id,
        'property_id' => $property->id,
        'week_starts_on' => $weekStart,
        'published_at' => now(),
        'published_by' => $ctx['manager']->getKey(),
    ]);

    $roster = RosterWeek::build(Property::find($property->id), 0, 'all');

    /*
     * ⚠️ THE GRID'S OWN METHODS, INVOKED DIRECTLY. Both are `private`, so reflection is the only way to
     * compare them against the builder without refactoring the working screen (which this pass must not do).
     * Comparing to a re-implementation here would prove nothing — it would just be a third copy agreeing with
     * itself. This asserts the accessor against the CODE THAT ACTUALLY RENDERS THE SCREEN.
     */
    $component = new OperatorSchedule;
    $gridOpen = (function (Property $p, RosterWeek $r): array {
        return $this->openShiftsFor($p, $r);
    })->call($component, Property::find($property->id), $roster);

    $gridPublished = (function (Property $p, RosterWeek $r): ?string {
        return $this->publishedState($p, $r);
    })->call($component, Property::find($property->id), $roster);

    // ⚠️ NON-VACUITY: the grid really does see both open shifts, so an accidental [] === [] cannot pass.
    expect($gridOpen)->toHaveCount(2)
        ->and($gridPublished)->not->toBeNull();

    // OPEN SHIFTS — identical, field for field. Sorted by id because both sides order only by `date` and the
    // two fixtures share neither date nor tie-break; sorting keeps the comparison about CONTENT, not order.
    $byId = function (array $rows): array {
        usort($rows, fn (array $a, array $b): int => strcmp($a['id'], $b['id']));

        return $rows;
    };
    expect($byId($roster->openShifts()))->toBe($byId($gridOpen));

    // PUBLICATION — the grid renders a RELATIVE phrase and the PDF an ABSOLUTE one, so the strings differ by
    // design. What must agree is the FACT underneath: both see this week as published.
    expect($roster->publicationState()['published'])->toBeTrue()
        ->and($gridPublished)->toBeString();

    expect($roster->openShiftCount())->toBe(count($gridOpen));
});

it('⚠️ D34-1 — the accessors are property- and week-SCOPED (a neighbouring week/property must not bleed in)', function () {
    $ctx = d341Scenario();
    $property = $ctx['property'];
    $other = Property::create(['name' => 'Bravo House', 'type' => 'hotel', 'currency' => 'EUR', 'timezone' => 'UTC']);
    $weekStart = d341WeekStart($property);

    // An open shift NEXT week, and one at ANOTHER property this week — neither belongs to this rota.
    OpenShift::create(['organization_id' => $ctx['org']->id, 'property_id' => $property->id,
        'date' => CarbonImmutable::parse($weekStart)->addDays(9)->toDateString(), 'shift_type' => 'day', 'department' => null]);
    OpenShift::create(['organization_id' => $ctx['org']->id, 'property_id' => $other->id,
        'date' => CarbonImmutable::parse($weekStart)->addDay()->toDateString(), 'shift_type' => 'day', 'department' => null]);
    // …and a publication for the OTHER property's same week.
    SchedulePublication::create(['organization_id' => $ctx['org']->id, 'property_id' => $other->id,
        'week_starts_on' => $weekStart, 'published_at' => now(), 'published_by' => null]);

    // ⚠️ NON-VACUITY: the rows exist — this asserts scoping, not emptiness.
    expect(OpenShift::query()->count())->toBe(2)
        ->and(SchedulePublication::query()->count())->toBe(1);

    $roster = RosterWeek::build(Property::find($property->id), 0, 'all');

    expect($roster->openShifts())->toBe([])
        ->and($roster->publicationState()['published'])->toBeFalse();
});

it('⚠️ D34-1 — no fixture residue (verified by RAW query, independent of any scope)', function () {
    // §N.18.1 — a check must not run through the mechanism it is checking. RefreshDatabase rolls the
    // transaction back, but that is asserted here rather than assumed.
    expect((int) DB::table('open_shifts')->count())->toBe(0)
        ->and((int) DB::table('schedule_publications')->count())->toBe(0);
});
