<?php

declare(strict_types=1);

use App\Notifications\FeedNotification;
use App\Support\Notifications\NotificationFeed;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;

uses(DatabaseTransactions::class);

// ⚠️⚠️ I18N-01 — the bell speaks its READER's language (the founder's ruling: notifications in each account's own language). A row
// written since the translation work stores each text's key and parameters beside the sentence; older rows keep their sentence.

function i18nProbeNotification(): FeedNotification
{
    return new class extends FeedNotification
    {
        public function eventKey(): string
        {
            return 'new_booking';
        }

        public function category(): string
        {
            return 'front_desk';
        }

        public function via(object $notifiable): array
        {
            return ['database'];
        }

        public function data(object $notifiable): array
        {
            return ['title' => $this->t('title', 'i18n_probe/feed.title', ['unit' => 'A1']), 'context' => 'stays as written',
                'url' => '/app', 'category' => 'front_desk'];
        }
    };
}

it('⚠️⚠️ stores the key and parameters beside the sentence, and shows the row in the reader\'s CURRENT language', function () {
    config(['app.available_locales' => ['en', 'de']]);
    app('translator')->addLines(['i18n_probe/feed.title' => 'New booking on :unit'], 'en');
    app('translator')->addLines(['i18n_probe/feed.title' => 'Neue Buchung für :unit'], 'de');
    $s = seedScenario(1);
    $me = operatorUser($s, ['locale' => 'en'], 'Front Desk');

    $me->notify(i18nProbeNotification());
    $row = $me->notifications()->latest()->first();
    expect($row->data['title'])->toBe('New booking on A1')
        ->and($row->data['i18n']['title'])->toBe(['i18n_probe/feed.title', ['unit' => 'A1']]);

    app()->setLocale('de');   // the same reader, now reading in German
    $item = NotificationFeed::recentForUser($me->fresh())['items'][0];
    expect($item['title'])->toBe('Neue Buchung für A1')->and($item['context'])->toBe('stays as written');
});

it('⚠️ keeps an older row\'s stored sentence — nothing stored is rewritten', function () {
    $s = seedScenario(1);
    $me = operatorUser($s, [], 'Front Desk');
    $me->notifications()->create(['id' => (string) Str::uuid(), 'type' => 'legacy', 'data' => ['title' => 'Stored English title', 'context' => 'Old', 'url' => '/app', 'category' => 'finance']]);

    app()->setLocale('de');
    $item = NotificationFeed::recentForUser($me->fresh())['items'][0];
    expect($item['title'])->toBe('Stored English title')
        ->and(NotificationFeed::categoryLabel('front_desk'))->toBe('Front Desk')
        ->and(NotificationFeed::categoryLabel('a_new_code'))->toBe('A New Code');
});

it('⚠️ stores a plural row\'s count, so the reader\'s language picks the right form', function () {
    config(['app.available_locales' => ['en', 'de']]);
    app('translator')->addLines(['i18n_probe/feed.nights' => '{1} :count night|[0,*] :count nights'], 'en');
    app('translator')->addLines(['i18n_probe/feed.nights' => '{1} :count Nacht|[0,*] :count Nächte'], 'de');
    $s = seedScenario(1);
    $me = operatorUser($s, ['locale' => 'en'], 'Front Desk');
    $n = new class extends FeedNotification
    {
        public function eventKey(): string
        {
            return 'new_booking';
        }

        public function category(): string
        {
            return 'front_desk';
        }

        public function via(object $notifiable): array
        {
            return ['database'];
        }

        public function data(object $notifiable): array
        {
            return ['title' => $this->tc('title', 'i18n_probe/feed.nights', 3), 'context' => '', 'url' => '/app', 'category' => 'front_desk'];
        }
    };

    $me->notify($n);
    expect($me->notifications()->latest()->first()->data['title'])->toBe('3 nights');
    app()->setLocale('de');
    expect(NotificationFeed::recentForUser($me->fresh())['items'][0]['title'])->toBe('3 Nächte');
});
