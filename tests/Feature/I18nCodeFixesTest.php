<?php

declare(strict_types=1);

use App\Support\I18n\CarbonFixes;
use App\Support\I18n\ListJoin;
use App\Support\I18n\LocalDate;
use App\Support\I18n\Locales;
use App\Support\I18n\LocalNumber;
use App\Support\I18n\LocalText;
use App\Support\I18n\Ordinal;
use App\Support\Reservations\CancellationPolicy;
use Carbon\CarbonImmutable;

// I18N-01 · the code-fix pass (2026-11-05) — month names, 24-hour clocks, Persian calendar, Georgian case, decimal marks, list separators.
// English must stay byte for byte what every page printed before.

afterEach(fn () => app()->setLocale('en'));

function cfDate(): CarbonImmutable
{
    return CarbonImmutable::parse('2026-11-05 15:05:00');
}

it('writes the free-cancel date in the reader\'s language, and English exactly as before', function () {
    app()->setLocale('en');
    expect(CancellationPolicy::label(cfDate()))->toBe('5 Nov')->and(CancellationPolicy::label(null))->toBeNull();

    app()->setLocale('de');
    expect(CancellationPolicy::label(cfDate()))->toStartWith('5.')->toContain('Nov');
    app()->setLocale('ru');
    expect(CancellationPolicy::label(cfDate()))->not->toContain('Nov');   // a Russian month, not the English one
});

it('keeps a 24-hour clock 24-hour in every language whose own habit is 12-hour (Greek, Hindi, Arabic, Albanian, Korean, zh_TW)', function () {
    foreach (['el', 'hi', 'ar', 'sq', 'ko', 'zh_TW', 'de', 'ja', 'fa'] as $l) {
        $out = cfDate()->locale(Locales::carbon($l))->translatedFormat(LocalDate::pattern('H:i', $l));
        expect($out)->toMatch('/15:05/', $l)->and($out)->not->toMatch('/[AaPp]\.?[Mm]|ΜΜ|ΠΜ|म\.|ص|م$/u', $l);
    }
    expect(LocalDate::pattern('H:i', 'en'))->toBe('H:i')
        ->and(LocalDate::pattern('j M Y, H:i T', 'en'))->toBe('j M Y, H:i T');
    // a 12-hour source stays free to follow the language
    expect(LocalDate::pattern('g:i A', 'en'))->toBe('g:i A');
});

it('prints a Persian date in Gregorian order, not the Solar Hijri one', function () {
    $out = cfDate()->locale('fa')->translatedFormat(LocalDate::pattern('l j F Y', 'fa'));
    expect($out)->toMatch('/^\S+ 5 \S+ 2026$/u')->and($out)->not->toContain(',');
});

it('gives the browser a Gregorian, Latin-digit tag for Persian only', function () {
    expect(Locales::dateTag('fa'))->toBe('fa-u-ca-gregory-nu-latn')
        ->and(Locales::dateTag('de'))->toBe('de')
        ->and(Locales::dateTag('en'))->toBe('en')
        ->and(Locales::dateTag('sr'))->toBe('sr-Cyrl');
});

it('does not capitalise Georgian, and capitalises everything else exactly as mb_strtoupper does', function () {
    expect(LocalText::upper('ნოემბერი'))->toBe('ნოემბერი')
        ->and(LocalText::upper('abc ნოე'))->toBe('ABC ნოე')
        ->and(LocalText::upper('Straße ç ж α'))->toBe(mb_strtoupper('Straße ç ж α'))
        ->and(LocalText::upper('日本語 ไทย हिन्दी العربية'))->toBe('日本語 ไทย हिन्दी العربية')
        ->and(LocalText::initial('ნინო'))->toBe('ნ')
        ->and(LocalText::initial('ana'))->toBe('A');
    app()->setLocale('en');
    expect(LocalText::caselessCss())->toBe('');
    expect(LocalText::caselessCss('ka'))->toContain('text-transform:none');
});

it('writes Georgian months and weekdays in the nominative a date line wants', function () {
    CarbonFixes::apply('ka');
    $d = cfDate()->locale('ka');
    expect($d->translatedFormat('F Y'))->toBe('ნოემბერი 2026')
        ->and($d->translatedFormat('l'))->toBe('ხუთშაბათი')
        ->and($d->translatedFormat('l, j F Y'))->toBe('ხუთშაბათი, 5 ნოემბერი 2026');
    expect(cfDate()->locale('de')->translatedFormat('l F'))->toBe('Donnerstag November');   // no other language moved
});

it('picks the ordinal form: English rule unchanged, Georgian rule for ka', function () {
    foreach ([1 => 'st', 2 => 'nd', 3 => 'rd', 4 => 'th', 11 => 'th', 12 => 'th', 13 => 'th', 21 => 'st', 22 => 'nd', 23 => 'rd', 101 => 'st', 111 => 'th'] as $n => $form) {
        expect(Ordinal::form($n, 'en'))->toBe($form, "en $n");
    }
    expect(Ordinal::form(1, 'ka'))->toBe('st')   // 1-ლი
        ->and(Ordinal::form(5, 'ka'))->toBe('nd')   // მე-5
        ->and(Ordinal::form(20, 'ka'))->toBe('nd')   // მე-20
        ->and(Ordinal::form(21, 'ka'))->toBe('th')   // 21-ე
        ->and(Ordinal::form(100, 'ka'))->toBe('nd')   // მე-100
        ->and(Ordinal::form(31, 'ka'))->toBe('th');
});

it('writes a printed decimal with the language\'s own mark', function () {
    expect(LocalNumber::decimal('1.5', 'en'))->toBe('1.5')
        ->and(LocalNumber::decimal('1.5', 'de'))->toBe('1,5')
        ->and(LocalNumber::decimal('2', 'de'))->toBe('2')
        ->and(LocalNumber::decimal('18.25', 'ru'))->toBe('18,25');
    app()->setLocale('de');
    expect(LocalNumber::decimal('0.5'))->toBe('0,5');
});

it('joins a list of words with the language\'s own separator, English exactly as implode(", ")', function () {
    $items = ['A1', 'B2', 'C3'];
    expect(ListJoin::join($items, 'en'))->toBe(implode(', ', $items))
        ->and(ListJoin::join($items, 'de'))->toBe('A1, B2, C3')
        ->and(ListJoin::join($items, 'ar'))->toBe('A1، B2، C3')
        ->and(ListJoin::join($items, 'fa'))->toBe('A1، B2، C3')
        ->and(ListJoin::join($items, 'zh_TW'))->toBe('A1、B2、C3')
        ->and(ListJoin::join(collect($items), 'ja'))->toBe('A1、B2、C3')
        ->and(ListJoin::join($items, 'th'))->toBe('A1 B2 C3')
        ->and(ListJoin::join([], 'en'))->toBe('')
        ->and(ListJoin::join([7, 8], 'en'))->toBe('7, 8');
    app()->setLocale('ar');
    expect(ListJoin::join(['x', 'y']))->toBe('x، y');
});

it('prints a Georgian month and year without the comma ICU puts between them, and leaves every other pattern as ICU gave it (D-I18N-KA-MONTH-COMMA)', function () {
    CarbonFixes::apply('ka');
    expect(LocalDate::pattern('F Y', 'ka'))->toBe('F Y')
        ->and(cfDate()->locale('ka')->translatedFormat(LocalDate::pattern('F Y', 'ka')))->toBe('ნოემბერი 2026')
        ->and(LocalDate::pattern('F Y', 'en'))->toBe('F Y')
        ->and(LocalDate::pattern('j F Y', 'ka'))->toBe('j F, Y');   // a day-month-year line keeps ICU's own comma
    // another language's month-and-year order is untouched by the Georgian rule
    expect(LocalDate::pattern('F Y', 'de'))->not->toBe('F, Y');
});
