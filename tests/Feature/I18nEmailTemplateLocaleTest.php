<?php

declare(strict_types=1);

use App\Mail\ReviewRequestMail;
use App\Models\EmailTemplate;
use App\Models\Organization;
use App\Services\EmailTemplateService;
use App\Support\Email\EmailTemplateCatalog;
use App\Support\Email\EmailTemplateResolver;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

// ⚠️⚠️ I18N-01 (PLAN 5.3) — a guest email the organisation has REWRITTEN goes out in the organisation's language, whole; one
// left as Vyrobel wrote it goes in the guest's own. And saving the editor without changing a field stores nothing for it,
// so an untouched email stays translatable (it used to store the default's words, freezing the email in the editor's
// language). These tests pretend German is finished; the organisation writes in English, its guest reads German.

function i18nTplScenario(): array
{
    config(['app.available_locales' => ['en', 'de']]);
    $s = seedScenario(1);
    app(TenantContext::class)->runAsSuperAdmin(fn () => Organization::query()->whereKey($s['org']->id)->update(['default_locale' => 'en']));

    return $s;
}

it('⚠️⚠️ an email left as written follows the GUEST — an edited one follows the ORGANISATION, every word of it', function () {
    $s = i18nTplScenario();
    $org = (string) $s['org']->id;

    expect(EmailTemplateResolver::localeFor($org, 'review-request', 'de'))->toBe('de');

    app(EmailTemplateService::class)->save($org, 'review-request', ['heading' => 'We loved having you'], null);

    expect(EmailTemplateResolver::localeFor($org, 'review-request', 'de'))->toBe('en')
        ->and(EmailTemplateResolver::localeFor($org, 'cancellation', 'de'))->toBe('de');   // only the email that was edited
});

it('⚠️ an unfinished guest language and an unfinished organisation language both fall back to English, never a raw code', function () {
    $s = i18nTplScenario();
    $org = (string) $s['org']->id;
    app(TenantContext::class)->runAsSuperAdmin(fn () => Organization::query()->whereKey($org)->update(['default_locale' => 'ja']));

    expect(EmailTemplateResolver::localeFor($org, 'review-request', 'ja'))->toBe('en');
    app(EmailTemplateService::class)->save($org, 'review-request', ['heading' => 'We loved having you'], null);
    expect(EmailTemplateResolver::localeFor($org, 'review-request', 'de'))->toBe('en');
});

it('⚠️⚠️ saving the editor unchanged stores NO words — the default stays translatable, and the email reads as before', function () {
    $s = i18nTplScenario();
    $org = (string) $s['org']->id;
    $default = EmailTemplateCatalog::get('review-request');

    app(EmailTemplateService::class)->save($org, 'review-request', [
        'subject' => $default['subject'], 'heading' => '  '.$default['heading'].' ', 'message' => $default['message'], 'button_label' => $default['button'],
    ], null);

    $row = EmailTemplate::query()->withoutGlobalScopes()->where('organization_id', $org)->where('template_key', 'review-request')->firstOrFail();
    expect([$row->subject, $row->heading, $row->message, $row->button_label])->toBe([null, null, null, null])
        ->and(EmailTemplateResolver::localeFor($org, 'review-request', 'de'))->toBe('de')
        ->and((new ReviewRequestMail(book($s, ['unit_id' => $s['units'][0]->id])))->render())->toContain('Thanks for staying with us');   // the default, as before

    // a real edit is still kept, word for word
    app(EmailTemplateService::class)->save($org, 'review-request', ['heading' => 'We loved having you'], null);
    expect($row->fresh()->heading)->toBe('We loved having you');
});
