<?php

declare(strict_types=1);

use App\Http\Middleware\SetVisitorLocale;
use App\Models\Guest;
use App\Models\PlatformAdmin;
use App\Models\User;
use App\Support\I18n\Locales;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

// ops/i18n/render-all.php — I18N-01: render every in-scope GET page as every role, in one language, and keep its VISIBLE
// text (text nodes + the attributes people read: placeholder, title, aria-label, alt, button values, meta description).
//
//   php -d memory_limit=2G ops/i18n/render-all.php <out-dir> [locale=en] [only-realm-substring]
//
// Two runs diffed (ops/i18n/render-diff.php) are the proof Phase A needs: English unchanged, page by page, role by role.
// The same run in another locale is Phase D's render check (status, raw keys, raw Blade, English left).
//
// ⚠️ SAFE BY CONSTRUCTION — it REFUSES to run unless the database is the scratch copy `vyrobel_i18n` (a fresh
//    `migrate:fresh --seed` at VY_SEED_DATE=2026-09-01 — never the dev database, never the tests' database, never
//    production). Every request runs inside a transaction that is ROLLED BACK; mail, notifications and the queue are
//    faked; sessions and cache live in memory. The clock is frozen, so two runs of the same code print the same page.
//    Pages are rendered as the demo seeder's own role accounts IN THAT COPY; nothing is ever saved.

$root = dirname(__DIR__, 2);
$outDir = rtrim($argv[1] ?? '', '/');
$locale = $argv[2] ?? 'en';
$only = $argv[3] ?? '';
if ($outDir === '') {
    fwrite(STDERR, "usage: php ops/i18n/render-all.php <out-dir> [locale] [realm-filter]\n");
    exit(2);
}

foreach (['DB_DATABASE' => 'vyrobel_i18n', 'SESSION_DRIVER' => 'array', 'CACHE_STORE' => 'array', 'MAIL_MAILER' => 'array',
    'QUEUE_CONNECTION' => 'null', 'BROADCAST_CONNECTION' => 'null', 'LOG_CHANNEL' => 'null', 'APP_DEBUG' => 'false'] as $k => $v) {
    putenv("$k=$v");
    $_ENV[$k] = $v;
    $_SERVER[$k] = $v;
}

require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (DB::getDatabaseName() !== 'vyrobel_i18n') {
    fwrite(STDERR, 'REFUSED: the database is '.DB::getDatabaseName().", not the scratch copy vyrobel_i18n\n");
    exit(3);
}
if ($locale !== 'en') {
    config(['app.available_locales' => array_values(array_unique([...Locales::finished(), $locale]))]);
}

Mail::fake();
Notification::fake();
Queue::fake();
CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-15 09:30:00', 'UTC'));
Carbon::setTestNow(Carbon::parse('2026-09-15 09:30:00', 'UTC'));

require __DIR__.'/scope.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$base = rtrim((string) config('app.url'), '/');

// ── actors ───────────────────────────────────────────────────────────────────────────────────────────────────────────
$demoOrg = DB::table('organizations')->where('slug', 'vyrobel-demo')->value('id');
$userBy = fn (string $email) => User::query()->where('email', $email)->first();
$actors = [];
foreach (['billing@', 'demo-admin@', 'demo-groupadmin@', 'demo-staff@', 'demo-revenue@', 'demo-frontdesk@', 'demo-agent@', 'demo-housekeeper@',
    'demo-maintenance@', 'demo-accountant@', 'demo-marketing@', 'demo-support@'] as $prefix) {
    $u = $userBy($prefix.'vyrobel-demo.test');
    if ($u === null) {
        continue;
    }
    $role = (string) DB::table('model_has_roles')->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
        ->where('model_has_roles.model_id', $u->getKey())->where('model_has_roles.organization_id', $demoOrg)->value('roles.name');
    if ($role !== '' && in_array($role, config('authorization.back_office_roles'), true) && ! isset($actors['op:'.$role])) {
        $actors['op:'.$role] = ['realm' => 'Operator back office', 'guard' => 'web', 'user' => $u];
    }
}
foreach (['demo-owner@' => 'owner:primary', 'demo-owner-accountant@' => 'owner:accountant', 'demo-owner-viewer@' => 'owner:view-only'] as $e => $label) {
    if ($u = $userBy($e.'vyrobel-demo.test')) {
        $actors[$label] = ['realm' => 'Owner portal', 'guard' => 'web', 'user' => $u];
    }
}
if ($g = Guest::withoutGlobalScopes()->where('email_hash', Guest::emailHash('demo.traveller@vyrobel-demo.test'))->first()) {
    $actors['guest'] = ['realm' => 'Guest portal', 'guard' => 'guest', 'user' => $g];
}
if ($a = PlatformAdmin::query()->where('email', 'admin@vyrobel-demo.test')->first()) {
    $actors['admin'] = ['realm' => 'Platform admin console', 'guard' => 'admin', 'user' => $a];
}
$actors['anon'] = ['realm' => '*public*', 'guard' => null, 'user' => null];

// ── parameters, read from the copy (raw queries, the demo organisation) ──────────────────────────────────────────────
$first = fn (string $table, array $where = [], string $col = 'id') => Schema::hasTable($table)
    ? DB::table($table)->where($where)->when(Schema::hasColumn($table, 'organization_id'), fn ($q) => $q->where('organization_id', $demoOrg))->orderBy($col)->value($col)
    : null;
$guestId = isset($actors['guest']) ? (string) $actors['guest']['user']->getKey() : null;
$ownerId = $first('owners');
$propertySlug = DB::table('properties')->where('organization_id', $demoOrg)->orderBy('created_at')->value('slug');
$params = [
    'reservation' => $guestId ? DB::table('reservations')->where('lead_guest_id', $guestId)->orderBy('check_in')->value('id') : $first('reservations'),
    'guest' => $first('guests'),
    'thread' => $first('message_threads'),
    'ticket' => $first('maintenance_tickets'),
    'owner' => $ownerId,
    'unit' => DB::table('units')->where('owner_id', $ownerId)->orderBy('code')->value('id') ?? $first('units'),
    'statement' => $first('owner_statements', ['owner_id' => $ownerId]),
    'payout' => $first('payouts', ['owner_id' => $ownerId]),
    'booking' => $first('reservations'),
    'year' => '2026',
    'key' => 'booking-confirmation',
    'property' => $propertySlug,
    'organization' => DB::table('organizations')->where('id', $demoOrg)->value('slug'),
    'roomType' => DB::table('room_types')->whereIn('property_id', DB::table('properties')->where('organization_id', $demoOrg)->pluck('id'))->orderBy('created_at')->value('id'),
    'plan' => Schema::hasTable('plans') ? DB::table('plans')->orderBy('id')->value('id') : null,
    'channel' => 'channex',
];
$realmParams = [
    'Guest portal' => ['thread' => $guestId ? DB::table('message_threads')->where('guest_id', $guestId)->value('id') : null],
    'Owner portal' => ['thread' => DB::table('message_threads')->where('owner_id', $ownerId)->value('id'),
        'booking' => DB::table('reservation_units')->whereIn('unit_id', DB::table('units')->where('owner_id', $ownerId)->pluck('id'))->value('reservation_id')],
    'Platform admin console' => ['organization' => $demoOrg],
];

// ── visible text ─────────────────────────────────────────────────────────────────────────────────────────────────────
function visibleText(string $html): string
{
    $html = preg_replace('#<(script|style|template)\b[^>]*>.*?</\1>#si', ' ', $html) ?? $html;
    $html = preg_replace('#<!--.*?-->#s', ' ', $html) ?? $html;
    $lines = [];
    // the attributes people read
    preg_match_all('#<[a-z][^>]*>#si', $html, $tags);
    foreach ($tags[0] as $tag) {
        preg_match_all('/\s(placeholder|title|aria-label|alt|aria-description|data-confirm|wire:confirm|label)\s*=\s*("([^"]*)"|\'([^\']*)\')/i', $tag, $am, PREG_SET_ORDER);
        foreach ($am as $a) {
            $v = trim(html_entity_decode($a[3] !== '' ? $a[3] : ($a[4] ?? ''), ENT_QUOTES | ENT_HTML5));
            if ($v !== '') {
                $lines[] = '@'.strtolower($a[1]).': '.$v;
            }
        }
        if (preg_match('/^<(input|button)\b/i', $tag) && preg_match('/\stype\s*=\s*["\']?(submit|button|reset)/i', $tag) && preg_match('/\svalue\s*=\s*"([^"]*)"/i', $tag, $vm)) {
            $lines[] = '@value: '.trim(html_entity_decode($vm[1], ENT_QUOTES | ENT_HTML5));
        }
        if (preg_match('/^<meta\b/i', $tag) && preg_match('/name\s*=\s*"description"/i', $tag) && preg_match('/content\s*=\s*"([^"]*)"/i', $tag, $cm)) {
            $lines[] = '@meta: '.trim(html_entity_decode($cm[1], ENT_QUOTES | ENT_HTML5));
        }
    }
    $text = preg_replace('#<(br|/p|/div|/li|/tr|/h[1-6]|/td|/th|/option|/button|/a|/label|/span|/section|/header|/footer|/title)\b[^>]*>#i', "\n", $html) ?? $html;
    $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5);
    foreach (preg_split('/\n/', $text) ?: [] as $l) {
        $l = trim(preg_replace('/\s+/u', ' ', $l) ?? $l);
        if ($l !== '') {
            $lines[] = $l;
        }
    }
    $out = implode("\n", $lines);
    // volatile values that are not language: ids, tokens, the real clock where a page reads it directly
    $out = preg_replace('/\b[0-9A-HJKMNP-TV-Z]{26}\b/', '<ULID>', $out) ?? $out;
    $out = preg_replace('/\b[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\b/i', '<UUID>', $out) ?? $out;
    $out = preg_replace('/\b[0-9a-zA-Z]{40,}\b/', '<TOKEN>', $out) ?? $out;

    return $out."\n";
}

// ── render ───────────────────────────────────────────────────────────────────────────────────────────────────────────
@mkdir($outDir, 0755, true);
$index = [];
$problems = [];
foreach ($actors as $label => $actor) {
    foreach (vyI18nScopeRoutes() as $r) {
        $realm = $r['realm'];
        $isPublic = in_array($realm, ['Public booking engine', 'Auth, signup and billing'], true) || in_array($r['name'], vyI18nGuestPublicNames(), true);
        if ($actor['realm'] === '*public*' ? ! $isPublic : ($realm !== $actor['realm'] || $isPublic)) {
            continue;
        }
        if ($only !== '' && ! str_contains($realm, $only)) {
            continue;
        }
        $missing = [];
        $uri = preg_replace_callback('/\{(\w+)\??\}/', function ($m) use ($params, $realmParams, $realm, &$missing) {
            $v = $realmParams[$realm][$m[1]] ?? $params[$m[1]] ?? null;
            if ($v === null || $v === '') {
                $missing[] = $m[1];
            }

            return (string) $v;
        }, $r['uri']) ?? $r['uri'];
        $slug = trim(str_replace(['/', '{', '}', '?'], ['_', '', '', ''], $r['uri']), '_') ?: 'home';
        if ($missing !== []) {
            $index[] = "$label\t/{$r['uri']}\tSKIP\t-\tno value for ".implode(',', $missing);

            continue;
        }

        // a FRESH session per request: an in-memory session carried between requests keeps the previous account's
        // password hash, and Laravel's session guard then signs the next account out (measured: each actor's first page
        // redirected to /login)
        app('session')->forgetDrivers();
        app('auth')->forgetGuards();
        if ($actor['guard'] !== null) {
            app('auth')->guard($actor['guard'])->setUser($actor['user']);
        }
        $request = Request::create($base.'/'.ltrim($uri, '/'), 'GET');
        if ($actor['guard'] === null && $locale !== 'en') {
            // the browser holds the cookie ENCRYPTED (EncryptCookies) — a plain value is dropped on the way in, and the page
            // would render in English (measured 2026-10-29: every signed-out page of the first German run was English)
            $request->cookies->set(SetVisitorLocale::COOKIE, app('encrypter')->encrypt(CookieValuePrefix::create(SetVisitorLocale::COOKIE, app('encrypter')->getKey()).$locale, false));
        }
        app()->instance('request', $request);
        DB::beginTransaction();
        try {
            if ($actor['guard'] === 'admin' && ! $actor['user']->hasTwoFactorEnabled()) {
                // the console sends an admin without 2FA to set-up; the copy's admin gets a confirmed one, rolled back
                $actor['user']->forceFill(['two_factor_secret' => 'I18NRENDERONLYXXXXXXXXXXXXXXXX', 'two_factor_confirmed_at' => now()])->saveQuietly();
            }
            if ($actor['guard'] !== null && $locale !== 'en') {
                if ($actor['guard'] === 'guest') {
                    $actor['user']->forceFill(['language' => $locale])->saveQuietly();
                } elseif ($actor['guard'] === 'web' || $actor['guard'] === 'admin') {
                    $actor['user']->forceFill(['locale' => $locale])->saveQuietly();
                }
            }
            $response = $kernel->handle($request);
            $status = $response->getStatusCode();
            $type = (string) $response->headers->get('Content-Type');
            $body = method_exists($response, 'getContent') ? (string) $response->getContent() : '';
            $loc = $status >= 300 && $status < 400 ? ' → '.preg_replace('#^https?://[^/]+#', '', (string) $response->headers->get('Location')) : '';
            if (str_contains($type, 'text/html') && $body !== '') {
                file_put_contents("$outDir/".Str::slug($label)."__$slug.txt", visibleText($body));
                if (preg_match_all('/(?<![\w.])(?:[a-z_]+\/)*[a-z_]+\.[a-z0-9_]+(?:\.[a-z0-9_]+)+(?![\w(])/', strip_tags(visibleText($body)), $rawKeys)) {
                    foreach (array_unique($rawKeys[0]) as $k) {
                        if (Lang::has($k, 'en', false)) {
                            $problems[] = "$label /$uri RAW-KEY $k";
                        }
                    }
                }
                if (preg_match('/@(if|foreach|lang|endif|csrf|auth|can)\b|\{\{\s*\$/', visibleText($body))) {
                    $problems[] = "$label /$uri RAW-BLADE";
                }
            } elseif (str_contains($type, 'pdf') && $body !== '') {
                // Phase D: every PDF in this language, kept to be turned into images and looked at (glyphs, joined Arabic)
                @mkdir("$outDir/pdf");
                file_put_contents("$outDir/pdf/".Str::slug($label)."__$slug.pdf", $body);
            } elseif (str_contains($type, 'csv')) {
                file_put_contents("$outDir/".Str::slug($label)."__$slug.csv.txt", strtok($body, "\n")."\n");
            }
            if ($status >= 500) {
                $problems[] = "$label /$uri HTTP $status";
            }
            $index[] = "$label\t/{$r['uri']}\t$status\t".strlen($body)."\t".strtok($type, ';').$loc;
            $kernel->terminate($request, $response);
        } catch (Throwable $e) {
            $problems[] = "$label /$uri EXCEPTION ".get_class($e).': '.mb_strimwidth($e->getMessage(), 0, 160, '…');
            $index[] = "$label\t/{$r['uri']}\tEXC\t-\t".get_class($e);
        } finally {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        }
    }
}
file_put_contents("$outDir/_index.tsv", implode("\n", $index)."\n");
file_put_contents("$outDir/_problems.txt", implode("\n", $problems)."\n");
$counts = array_count_values(array_map(fn ($l) => explode("\t", $l)[2] ?? '?', $index));
ksort($counts);
echo 'rendered: '.count($index).' · '.json_encode($counts).' · problems: '.count($problems)."\n";
