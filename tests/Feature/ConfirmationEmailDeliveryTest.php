<?php

use App\Exceptions\TenantContextMissingException;
use App\Jobs\SendBookingConfirmationEmail;
use App\Mail\BookingConfirmationMail;
use App\Models\Guest;
use App\Services\FolioService;
use App\Support\Money\Money;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

/*
 * THE BOOKING CONFIRMATION MUST SURVIVE THE QUEUE BOUNDARY (audit 2026-08-07 · H2 / F-2b-01 / P5-04).
 *
 * THE BUG: the confirmation was queued as `Mail::later(new BookingConfirmationMail($reservation))` — a Mailable
 * using SerializesModels, holding a tenant-scoped Reservation. On the worker the payload restores fine, but
 * RENDERING the mail touches `$reservation->leadGuest` and the property, both tenant-scoped, where there is no
 * ambient TenantContext. Every confirmation therefore threw TenantContextMissingException and failed
 * permanently (36 dead jobs in the live dev DB), with horizon `tries => 1` guaranteeing no retry.
 *
 * ⚠️ The audit reported this as a failure "on job deserialization". Verified here: it is not — the restore
 * succeeds and the RENDER is what dies, which is why the production exception named [App\Models\Guest] rather
 * than [App\Models\Reservation]. The distinction matters: wrapping only the deserialize would not have fixed it.
 *
 * WHY THE SUITE STAYED GREEN: every existing assertion was `Mail::fake()` + `assertQueued`, which proves a job
 * was ENQUEUED and never renders it. That is the exact blind spot this file closes: it crosses a REAL
 * serialize/deserialize boundary with the tenant context CLEARED, and RENDERS the mail through a real transport.
 */

/** A confirmed booking whose folio is already committed, so the rendered total is the real one. */
function confirmedBookingWithFolio(int $roomChargeMinor = 24500): array
{
    $s = seedScenario(1);
    $s['guest']->update(['email' => 'ada@example.test', 'first_name' => 'Ada', 'last_name' => 'Lovelace']);

    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);

    $folios = app(FolioService::class);
    $folio = $folios->openFolio($reservation);
    $folios->postCharge($folio, 'room', 'Room', new Money($roomChargeMinor, 'EUR'));

    return [$reservation->fresh(), $folio->fresh()];
}

it('deserializes and RENDERS the queued confirmation with NO ambient tenant context', function () {
    [$reservation] = confirmedBookingWithFolio(24500);

    // A REAL transport, not Mail::fake(): the message is genuinely rendered during send — inside the job's
    // tenant context, exactly as production does. Mail::fake() defers rendering, which is half of why this
    // bug survived a green suite.
    config(['mail.default' => 'array']);
    $transport = app('mailer')->getSymfonyTransport();
    $transport->flush();   // drop the confirmation the `sync` driver already delivered during book()

    $job = new SendBookingConfirmationEmail(
        (string) $reservation->organization_id,
        (string) $reservation->getKey(),
    );

    // Cross a REAL queue boundary: serialize, drop the tenant context exactly as a worker process would have
    // none, then unserialize. Against the pre-fix code the equivalent step threw TenantContextMissingException
    // HERE, before any job body could run — which is why nothing could wrap or retry it.
    $payload = serialize($job);
    app(TenantContext::class)->forget();
    expect(app(TenantContext::class)->hasContext())->toBeFalse();

    $restored = unserialize($payload);
    expect($restored)->toBeInstanceOf(SendBookingConfirmationEmail::class);

    // The body restores tenancy itself and loads the reservation inside it.
    $restored->handle(app(TenantContext::class));

    $messages = $transport->messages();
    expect($messages)->toHaveCount(1);

    $sent = $messages[0]->getOriginalMessage();
    $html = (string) $sent->getHtmlBody();

    expect($sent->getTo()[0]->getAddress())->toBe('ada@example.test');
    expect($html)->toContain((string) $reservation->reference);
    expect($html)->toContain('245.00');   // the REAL committed folio total (#17), never a "€0.00" email
});

it('pins THE BUG MECHANISM: a queued Mailable RENDERS outside tenant context and dies', function () {
    [$reservation] = confirmedBookingWithFolio();

    // This is EXACTLY what the pre-fix code queued: a Mailable using SerializesModels holding a Reservation.
    //
    // ⚠️ PRECISION MATTERS HERE. The audit described this as failing "on job deserialization". It does not:
    // the ModelIdentifier restores fine (asserted below), because Laravel's restoration query is not what
    // trips the scope. The failure is one step later — RENDERING touches `$reservation->leadGuest` and the
    // property, both tenant-scoped, on a worker with no ambient TenantContext. That is why the production
    // exception named [App\Models\Guest] and not [App\Models\Reservation].
    //
    // This pins the DESIGN rule, not just the symptom: if anyone ever queues a tenant-scoped Mailable again,
    // the render below still bites. SendBookingConfirmationEmail avoids it by carrying scalars and doing the
    // whole load-and-send inside forOrganization().
    $payload = serialize(new BookingConfirmationMail($reservation));
    app(TenantContext::class)->forget();

    $restored = unserialize($payload);           // restore SUCCEEDS …
    expect($restored)->toBeInstanceOf(BookingConfirmationMail::class);

    expect(fn () => $restored->render())         // … and the RENDER is what fails.
        ->toThrow(TenantContextMissingException::class);
});

it('keeps guest PII out of the queued payload', function () {
    [$reservation] = confirmedBookingWithFolio();

    $payload = serialize(new SendBookingConfirmationEmail(
        (string) $reservation->organization_id,
        (string) $reservation->getKey(),
    ));

    // A queue payload is stored in PLAINTEXT in `jobs`/`failed_jobs`. Serialising the address would copy an
    // encrypted column into a plaintext store and put it beyond the reach of Guest::anonymize() (audit F-2b-04).
    // The recipient is resolved inside the job, in tenant context, at send time.
    expect($payload)->not->toContain('ada@example.test');
    expect($payload)->toContain((string) $reservation->getKey());
});

it('sends nothing when the guest was anonymised between booking and send', function () {
    [$reservation] = confirmedBookingWithFolio();

    app(TenantContext::class)->forOrganization((string) $reservation->organization_id, function () use ($reservation): void {
        $guest = Guest::query()->findOrFail($reservation->lead_guest_id);
        $guest->anonymize();
    });

    // Fake AFTER the booking: the test queue driver is `sync`, so booking already delivered its own
    // confirmation. We are asserting about the send that happens AFTER erasure.
    Mail::fake();

    (new SendBookingConfirmationEmail(
        (string) $reservation->organization_id,
        (string) $reservation->getKey(),
    ))->handle(app(TenantContext::class));

    // Resolving the address at SEND time (not at dispatch) means an erased guest is never emailed.
    Mail::assertNotSent(BookingConfirmationMail::class);
});

it('retries a transient failure instead of discarding it', function () {
    $job = new SendBookingConfirmationEmail('org', 'res');

    // horizon `tries => 1` meant any SMTP 4xx / provider blip / deadlock permanently dead-lettered the send.
    expect($job->tries)->toBeGreaterThan(1);
    expect($job->backoff)->not->toBeEmpty();
});
