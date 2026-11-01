<?php

declare(strict_types=1);

use App\Models\OwnerLedgerEntry;
use App\Models\OwnerStatementLine;
use App\Support\I18n\LocalDate;
use App\Support\I18n\StoredEnglish;
use App\Support\Notifications\FeedParams;
use App\Support\Notifications\NotificationFeed;
use App\Support\Reports\OperatorRevenueBreakdown;
use App\Support\Reviews\ReviewTag;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Lang;

// I18N-01 Phase D (2026-10-29) — text the SYSTEM wrote into rows in English (older bell notifications, statement lines, owner
// ledger lines, review tag codes, agreement rule codes) is said in the READER's language, and an English reader sees exactly
// what was stored. Found by ops/i18n/english-left.php on the first German render: those rows printed English on German pages.

afterEach(fn () => app()->setLocale('en'));

/** A test language whose words are known: the real English key, with a marker in front. */
function sePretend(array $lines): void
{
    Lang::addLines($lines, 'xx');
    app()->setLocale('xx');
}

it('matches a stored sentence to its key and carries the filled-in parts over — English untouched', function () {
    sePretend(['php/models/owner_ledger_entry.commission' => 'XX Provision :pct%', 'php/models/owner_ledger_entry.reversal' => 'XX Storno — :original']);

    $line = new OwnerLedgerEntry(['description' => 'Management commission 18%']);
    expect($line->label())->toBe('XX Provision 18%');

    $reversal = new OwnerLedgerEntry(['description' => 'Reversal — Management commission 18%']);
    expect($reversal->label())->toBe('XX Storno — XX Provision 18%');   // the reversed line is said the same way

    sePretend(['php/models/owner_ledger_entry.management_fee' => 'XX Gebühr (:interval, anteilig)', 'php/livewire/owner/owner_agreement.interval_monthly' => 'XX monatlich']);
    $fee = new OwnerLedgerEntry(['description' => 'Management fee (monthly, pro-rated)']);
    expect($fee->label())->toBe('XX Gebühr (XX monatlich, anteilig)');   // the interval code said in words, never "monthly"

    $typed = new OwnerLedgerEntry(['description' => 'Pool repair, invoice 44']);
    expect($typed->label())->toBe('Pool repair, invoice 44');           // a sentence no key has stays as stored

    app()->setLocale('en');
    expect($line->label())->toBe('Management commission 18%')
        ->and(StoredEnglish::say('php/models/owner_ledger_entry', 'Management commission 18%'))->toBeNull();
});

it('⚠️ every ledger key is a sentence a writer really stores — a key that drifted from its writer would never match', function () {
    $writers = implode("\n", array_map('file_get_contents', [
        app_path('Services/OwnerAttributionService.php'), app_path('Services/OwnerExpenseService.php'),
        app_path('Services/OwnerPayoutService.php'), app_path('Services/WithholdingService.php'),
    ]));
    foreach ((array) Lang::get('php/models/owner_ledger_entry', [], 'en') as $item => $english) {
        // the fixed text before the first filled-in part must appear in a writer, quoted
        $head = explode(':', (string) $english)[0];
        expect(str_contains($writers, "'".rtrim($head === '' ? (string) $english : $head)))->toBeTrue("ledger key {$item}");
    }
});

it('says a statement line by its type — and an English reader sees the stored description byte for byte', function () {
    $line = new OwnerStatementLine(['line_type' => 'commission', 'description' => 'Management commission']);
    $odd = new OwnerStatementLine(['line_type' => 'commission', 'description' => 'Commission (hand-written)']);
    expect($line->label())->toBe('Management commission')->and($odd->label())->toBe('Commission (hand-written)');

    sePretend(['php/models/owner_statement_line.commission' => 'XX Provision']);
    expect($line->label())->toBe('XX Provision')
        ->and($odd->label())->toBe('Commission (hand-written)');   // not the standard wording → as stored
});

it('says an older bell notification (stored as English) in the reader\'s language', function () {
    $legacy = new ReflectionMethod(NotificationFeed::class, 'legacy');
    $type = 'App\\Notifications\\Operator\\NewReviewNotification';

    expect($legacy->invoke(null, $type, 'Awaiting moderation'))->toBeNull();   // English reader: the stored sentence shows

    sePretend(['php/notifications/operator/new_review_notification.context_awaiting' => 'XX wartet', 'php/notifications/operator/new_review_notification.title' => 'XX Bewertung · :stars']);
    expect($legacy->invoke(null, $type, 'Awaiting moderation'))->toBe('XX wartet')
        ->and($legacy->invoke(null, $type, 'New review · ★★★☆☆'))->toBe('XX Bewertung · ★★★☆☆')
        ->and($legacy->invoke(null, $type, 'Something a newer English says'))->toBeNull()
        ->and($legacy->invoke(null, 'Some\\Other\\Type', 'Awaiting moderation'))->toBeNull();
});

it('says an older PLURAL bell row in the reader\'s language — the count read from the row, or from the form that names one', function () {
    $legacy = new ReflectionMethod(NotificationFeed::class, 'legacy');
    $type = 'App\\Notifications\\Operator\\InboxMessageNotification';

    expect($legacy->invoke(null, $type, 'New message from Demo Owner'))->toBeNull();   // English reader: as stored

    app()->setLocale('de');
    expect($legacy->invoke(null, $type, 'New message from Demo Owner'))->toBe('Neue Nachricht von Demo Owner')
        ->and($legacy->invoke(null, $type, '3 new messages from Demo Owner'))->toBe('3 neue Nachrichten von Demo Owner')
        ->and($legacy->invoke(null, $type, 'New messages from Demo Owner'))->toBeNull();   // no form says it — shown as stored
});

it('says review tags in the reader\'s language on every list — English is the stored code', function () {
    expect(ReviewTag::labels(['Comfy beds', 'Noise', 'unknown tag']))->toBe(['Comfy beds', 'Noise', 'unknown tag']);

    sePretend(['php/http/controllers/guest/guest_review_controller.tag_comfy_beds' => 'XX Bequeme Betten']);
    expect(ReviewTag::labels(['Comfy beds', 'unknown tag']))->toBe(['XX Bequeme Betten', 'unknown tag']);

    // the operator list and the owner/storefront list read through it — no surface prints the raw code
    expect(file_get_contents(app_path('Http/Controllers/Operator/OperatorReviewsController.php')))->toContain('ReviewTag::labels(')
        ->and(file_get_contents(app_path('Support/Reviews/ReviewList.php')))->toContain('ReviewTag::labels(');
});

it('says an agreement\'s rule codes in words — English is exactly the headline it always printed', function () {
    foreach ((array) __('php/livewire/owner/owner_agreement.rules') as $code => $english) {
        expect($english)->toBe((string) preg_replace('/\bOta\b/', 'OTA', Str::headline($code)));
    }
    foreach ((array) __('php/livewire/owner/owner_agreement.rule_values') as $code => $english) {
        expect($english)->toBe(Str::headline($code));
    }
});

it('says a notification\'s dates and booking source in the READER\'s language — stored as a date and a code, not as English text', function () {
    $params = ['source' => FeedParams::source('walk_in'), 'check_in' => FeedParams::date('2026-10-26', 'j M'), 'reference' => 'R-1'];
    // stored as data, never as finished text
    expect(json_decode(json_encode($params), true)['check_in'])->toBe(['@date' => CarbonImmutable::parse('2026-10-26')->toIso8601String(), '@format' => 'j M']);

    $said = FeedParams::say($params);
    expect($said['check_in'])->toBe('26 Oct')->and($said['reference'])->toBe('R-1')
        ->and($said['source'])->toBe(OperatorRevenueBreakdown::sourceLabel('walk_in'));   // "Walk-in", never "Walk_in"

    app()->setLocale('de');
    Carbon\Carbon::setLocale('de');
    expect(FeedParams::say($params)['check_in'])->toBe(CarbonImmutable::parse('2026-10-26')->locale('de')->translatedFormat(LocalDate::pattern('j M', 'de')))
        ->not->toBe('26 Oct');
    Carbon\Carbon::setLocale('en');
});

it('re-reads an OLDER row\'s English date and source for a reader in another language — English untouched', function () {
    $legacy = new ReflectionMethod(NotificationFeed::class, 'legacy');
    $type = 'App\\Notifications\\Operator\\NewBookingNotification';
    expect($legacy->invoke(null, $type, 'Ota · 26 Oct – 28 Oct'))->toBeNull();   // English reader: as stored

    sePretend(['php/notifications/operator/new_booking_notification.context' => 'XX :source · :check_in – :check_out', 'php/support/reports/operator_revenue_breakdown.source_ota' => 'XX-OTA']);
    Carbon\Carbon::setLocale('de');
    $okt = fn (int $d): string => CarbonImmutable::create(2026, 10, $d)->locale('de')->translatedFormat('j M');
    expect($legacy->invoke(null, $type, 'Ota · 26 Oct – 28 Oct'))->toBe('XX XX-OTA · '.$okt(26).' – '.$okt(28));
    Carbon\Carbon::setLocale('en');
});

it('matches the MOST specific stored sentence — a shorter one never swallows the rest into its last part', function () {
    sePretend([
        'php/notifications/guest/guest_reservation_notification.context' => 'XX-SHORT :property · :check_in – :check_out',
        'php/notifications/guest/guest_reservation_notification.context_nights' => 'XX :property · :check_in – :check_out · Nächte: :nights',
    ]);
    $legacy = new ReflectionMethod(NotificationFeed::class, 'legacy');
    Carbon\Carbon::setLocale('de');
    $said = $legacy->invoke(null, 'App\\Notifications\\Guest\\GuestReservationNotification', 'Seaside · 5 Oct 2026 – 8 Oct 2026 · 3 nights');
    $d = fn (int $day): string => CarbonImmutable::create(2026, 10, $day)->locale('de')->translatedFormat('j M Y');
    expect($said)->toBe('XX Seaside · '.$d(5).' – '.$d(8).' · Nächte: 3');
    Carbon\Carbon::setLocale('en');
});

it('re-reads an older row of a SUBCLASS through its parent\'s texts — the guest stay notifications share one group', function () {
    sePretend(['php/notifications/guest/guest_reservation_notification.context_nights' => 'XX :property · :check_in – :check_out · :nights']);
    $legacy = new ReflectionMethod(NotificationFeed::class, 'legacy');
    expect($legacy->invoke(null, 'App\\Notifications\\Guest\\GuestBookingConfirmedNotification', 'Seaside · 5 Oct 2026 – 8 Oct 2026 · 3 nights'))
        ->toStartWith('XX Seaside · ');
});
