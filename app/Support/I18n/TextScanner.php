<?php

declare(strict_types=1);

namespace App\Support\I18n;

/**
 * The hardcoded-English scanner (I18N-01) — ONE definition, read by `lang:todo` (HARDCODED), ops/i18n/check-file.php and the
 * inventory. It estimates what a person reads: Blade comments dropped; <script>/<style>/@php bodies, echoes and directive
 * arguments scanned for STRING LITERALS only; text nodes and the visible attributes counted. PHP is read with token_get_all —
 * comments are tokens, so they are never read (a source-scanning guard must not read its own needle).
 */
final class TextScanner
{
    // ops/i18n/scan.php — I18N-01: the hardcoded-English scanner (Phase A's working instrument; lang:todo's HARDCODED check is
    // built on it). It estimates what a person reads; check-file.php and the suite's guard decide.
    // Blade: comments dropped; <script>/<style>/@php bodies, echoes and directive arguments scanned for STRING LITERALS only;
    // text nodes and the visible attributes counted. PHP: token_get_all — comments are tokens, so they are never read.

    private const VISIBLE_ATTRS = ['placeholder', 'title', 'aria-label', 'alt', 'aria-description', 'aria-roledescription', 'label',
        'data-confirm', 'wire:confirm', 'data-tooltip', 'data-tip', 'data-hint', 'data-empty', 'data-title', 'data-label', 'summary',
        'aria-valuetext', 'data-placeholder', 'data-loading-text', 'data-copied'];

    // x-component attributes that are never visible text
    private const NON_TEXT_ATTRS = ['class', 'id', 'name', 'type', 'href', 'route', 'for', 'size', 'variant', 'icon', 'tone', 'color', 'key',
        'method', 'action', 'value', 'src', 'width', 'height', 'style', 'role', 'tabindex', 'target', 'rel', 'as', 'tag', 'align',
        'position', 'kind', 'mode', 'state', 'status', 'realm', 'shell', 'page', 'active', 'current', 'model', 'field', 'format', 'step',
        'min', 'max', 'pattern', 'autocomplete', 'inputmode', 'lang', 'dir', 'slot', 'layout', 'theme', 'accent', 'glyph', 'svg', 'd',
        'viewbox', 'fill', 'stroke', 'xmlns', 'data-gl-region', 'property', 'unit', 'currency', 'locale', 'timezone', 'zone', 'url'];

    private const VISIBLE_PHP_KEYS = ['label', 'title', 'heading', 'message', 'subject', 'description', 'text', 'hint', 'empty', 'placeholder',
        'cta', 'button', 'caption', 'subtitle', 'body', 'note', 'tooltip', 'help', 'summary', 'context', 'error', 'success', 'status',
        'warning', 'info', 'flash', 'notice', 'reason', 'line', 'detail', 'headline', 'sub', 'lead', 'copy', 'name_label', 'action_label'];

    private const VISIBLE_PHP_CALLS = ['with', 'flash', 'adderror', 'subject', 'line', 'greeting', 'action', 'withmessages', 'abort', 'abort_if',
        'abort_unless', 'witherrors', 'markdown', 'salutation', 'title', 'heading', 'label', 'dispatch', 'error', 'success', 'notify',
        'session', 'put', 'setTitle', 'header'];

    private const SKIP_PHP_CALLS = ['log', 'info', 'warning', 'debug', 'critical', 'alert', 'emergency', 'notice', 'report', 'where', 'orwhere',
        'raw', 'selectraw', 'whereraw', 'orderbyraw', 'statement', 'select', 'config', 'env', 'route', 'view', 'redirect', 'url', 'asset',
        'json_encode', 'sprintf', 'preg_match', 'preg_replace', 'str_contains', 'in_array', 'format', 'translatedformat', 'isoformat',
        'createfromformat', 'mergeconfigfrom', 'loadviewsfrom', 'can', 'authorize', 'hasrole', 'assignrole', 'givepermissionto',
        'mock', 'logger', 'exception', 'runtimeexception', 'logicexception', 'invalidargumentexception', 'domainexception'];

    public static function looksEnglish(string $s): bool
    {
        $t = trim(html_entity_decode($s, ENT_QUOTES | ENT_HTML5));
        if ($t === '' || mb_strlen($t) < 2 || ! preg_match('/[A-Za-z]{2,}/', $t)) {
            return false;
        }
        if (preg_match('#^(https?:|mailto:|tel:|/|\.\.?/|\#|\[|\{|<|data:|javascript:)#', $t)) {
            return false;
        }
        if (preg_match('/^[a-z0-9_.\-]+(\.[a-z0-9_\-]+)+$/', $t)) {   // view / route / config names
            return false;
        }
        if (preg_match('/^[A-Za-z]\\\\|::|->|\$[a-z_]|=>|\bfunction\b|^\w+\(|\);?$/', $t)) {   // code
            return false;
        }
        if (preg_match('/^[a-z0-9_]+$/', $t)) {   // snake_case identifiers / single lowercase words (keys, enum values)
            return false;
        }
        $tokens = preg_split('/\s+/', $t);
        $classy = true;
        foreach ($tokens as $tok) {
            if (! preg_match('/^[a-z0-9_:\-\/\[\]\.#!%&>@()=,]+$/', $tok)) {
                $classy = false;
                break;
            }
        }
        if ($classy && preg_match('/[-_:\/\[]/', $t)) {   // a class list / CSS / selector
            return false;
        }
        if (preg_match('/^[\w\- ]*:\s*[\w#\-().% ]+;?$/', $t) && preg_match('/(display|color|width|height|margin|padding|font|border|background|flex|grid|gap|opacity|position|top|left|transform)\s*:/i', $t)) {
            return false;   // inline CSS
        }
        if (preg_match('/^[dDjlNSwzWFmMntLoYyaABgGhHisuveIOPpTZcrU\\\\ .,:\/\-]+$/', $t) && preg_match('/^([a-zA-Z][ .,:\/\-]*){1,8}$/', $t) && ! preg_match('/[a-z]{3}/', $t)) {
            return false;   // a date() format string
        }
        if (preg_match('/^(select|insert|update|delete|alter|create|drop)\s/i', $t) && preg_match('/\b(from|into|table|set|where)\b/i', $t)) {
            return false;   // SQL
        }
        if (count($tokens) === 1) {
            return preg_match('/^[A-Z][a-z]+(\'[a-z]+)?[.!?:…]?$|^[A-Z][a-z]+[A-Z]?[a-z]*$|^[A-Z][a-z]+(-[a-z]+)+[.!?]?$/', $t) === 1 && ! preg_match('/^(App|Illuminate|Carbon|Livewire|Vyrobel|Stripe|Google|Laravel|GET|POST|PUT|PATCH|DELETE|true|false|null|UTC|EUR|USD|GBP)$/', $t);
        }

        return true;
    }

    /** Translation calls in a chunk of code. */
    public static function countKeys(string $code): int
    {
        return preg_match_all('/(?<![\w>$:])(__|trans|trans_choice|Lang::get|Lang::choice)\s*\(|@lang\s*\(|@choice\s*\(/', $code);
    }

    /** English-looking string literals in a chunk of PHP/JS code (not a translation call's own key). */
    public static function stringLiterals(string $code, string $lang = 'php'): array
    {
        $out = [];
        $code = preg_replace('/(?<![\w>$:])(__|trans|trans_choice|@lang)\s*\(\s*([\'"]).*?(?<!\\\\)\2/s', '', $code) ?? $code;
        if ($lang === 'js') {
            $code = preg_replace('#/\*.*?\*/#s', '', $code) ?? $code;
            $code = preg_replace('#(?<![:"\'\\\\])//[^\n]*#', '', $code) ?? $code;
        }
        preg_match_all('/\'((?:[^\'\\\\]|\\\\.)*)\'|"((?:[^"\\\\]|\\\\.)*)"|`((?:[^`\\\\]|\\\\.)*)`/s', $code, $m, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
        foreach ($m as $mmo) {
            $end = $mmo[0][1] + strlen($mmo[0][0]);
            $before = rtrim(substr($code, 0, $mmo[0][1]));
            if ($lang === 'js' && preg_match('/\\G\\s*:(?!:)/', $code, $_x, 0, $end) && ($before === '' || in_array(substr($before, -1), ['{', ','], true))) {
                continue;   // an object-literal KEY ({'Accept': …}), never shown
            }
            $mm = array_map(fn (array $g): string => $g[0], $mmo);
            $s = $mm[1] !== '' ? $mm[1] : (($mm[2] ?? '') !== '' ? $mm[2] : ($mm[3] ?? ''));
            $s = stripslashes($s);
            if ($lang === 'js' && preg_match('/\$\{/', $s)) {
                $s = preg_replace('/\$\{[^}]*\}/', ' X ', $s);
            }
            if (self::looksEnglish($s)) {
                $out[] = trim($s);
            }
        }

        return $out;
    }

    /** @return array{keys:int, text:list<string>, attr:list<string>, js:list<string>, code:list<string>} */
    public static function scanBlade(string $src): array
    {
        $r = ['keys' => 0, 'text' => [], 'attr' => [], 'js' => [], 'code' => []];
        $src = preg_replace('/\{\{--.*?--\}\}/s', '', $src) ?? $src;
        $src = preg_replace('/<!--.*?-->/s', '', $src) ?? $src;
        // <script> bodies — JS strings + keys; JSON-LD counted too
        $src = preg_replace_callback('#<script\b[^>]*>(.*?)</script>#si', function ($m) use (&$r) {
            $r['keys'] += self::countKeys($m[1]);
            $body = preg_replace('/\{\{.*?\}\}|\{!!.*?!!\}|@js\(.*?\)/s', '0', $m[1]) ?? $m[1];
            array_push($r['js'], ...self::stringLiterals($body, 'js'));

            return "\x01";
        }, $src) ?? $src;
        $src = preg_replace('#<style\b[^>]*>.*?</style>#si', "\x01", $src) ?? $src;
        // @php … @endphp
        $src = preg_replace_callback('/@php\b(?!\s*\()(.*?)@endphp/s', function ($m) use (&$r) {
            $r['keys'] += self::countKeys($m[1]);
            array_push($r['code'], ...self::visiblePhpStrings('<?php '.$m[1]));

            return "\x01";
        }, $src) ?? $src;
        // echoes
        $src = preg_replace_callback('/\{\{(?!--)(.*?)\}\}|\{!!(.*?)!!\}/s', function ($m) use (&$r) {
            $code = $m[1] !== '' ? $m[1] : ($m[2] ?? '');
            $r['keys'] += self::countKeys($code);
            array_push($r['code'], ...self::stringLiterals($code));

            return "\x01";
        }, $src) ?? $src;
        // directives with balanced arguments
        $out = '';
        $len = strlen($src);
        for ($i = 0; $i < $len; $i++) {
            $c = $src[$i];
            if ($c === '@' && ($i === 0 || ! preg_match('/\w/', $src[$i - 1])) && preg_match('/\G@([a-zA-Z]+)/', $src, $dm, 0, $i)) {
                $name = $dm[1];
                $j = $i + strlen($dm[0]);
                $k = $j;
                while ($k < $len && ($src[$k] === ' ' || $src[$k] === "\t")) {
                    $k++;
                }
                if ($k < $len && $src[$k] === '(') {
                    $depth = 0;
                    $q = null;
                    for ($p = $k; $p < $len; $p++) {
                        $ch = $src[$p];
                        if ($q !== null) {
                            if ($ch === '\\') {
                                $p++;
                            } elseif ($ch === $q) {
                                $q = null;
                            }

                            continue;
                        }
                        if ($ch === '\'' || $ch === '"') {
                            $q = $ch;
                        } elseif ($ch === '(') {
                            $depth++;
                        } elseif ($ch === ')') {
                            $depth--;
                            if ($depth === 0) {
                                break;
                            }
                        }
                    }
                    $args = substr($src, $k + 1, $p - $k - 1);
                    $r['keys'] += self::countKeys($args) + ($name === 'lang' || $name === 'choice' ? 1 : 0);
                    if (in_array($name, ['include', 'includeIf', 'includeWhen', 'includeFirst', 'component', 'livewire', 'each', 'php', 'js', 'json', 'props', 'aware', 'section', 'push', 'error', 'class'], true)) {
                        // strings passed as data to a partial — visible when keyed like a label
                        array_push($r['code'], ...self::visiblePhpStrings('<?php ['.$args.'];', true));
                    }
                    $out .= "\x02";
                    $i = $p;

                    continue;
                }
                $out .= "\x02";
                $i = $j - 1;

                continue;
            }
            $out .= $c;
        }
        $src = $out;
        // tags and text
        $len = strlen($src);
        $buf = '';
        $flushText = function () use (&$buf, &$r) {
            foreach (explode("\x02", $buf) as $piece) {
                $t = trim(preg_replace('/\s+/', ' ', str_replace("\x01", ' ', $piece)) ?? '');
                if ($t !== '' && self::looksEnglishText($t)) {
                    $r['text'][] = $t;
                }
            }
            $buf = '';
        };
        $inSvgText = 0;
        for ($i = 0; $i < $len; $i++) {
            $c = $src[$i];
            if ($c === '<' && $i + 1 < $len && preg_match('/[A-Za-z\/!]/', $src[$i + 1])) {
                $flushText();
                $q = null;
                for ($p = $i + 1; $p < $len; $p++) {
                    $ch = $src[$p];
                    if ($q !== null) {
                        if ($ch === $q) {
                            $q = null;
                        }

                        continue;
                    }
                    if ($ch === '"' || $ch === '\'') {
                        $q = $ch;
                    } elseif ($ch === '>') {
                        break;
                    }
                }
                $tag = substr($src, $i, $p - $i + 1);
                self::scanTag($tag, $r);
                $i = $p;

                continue;
            }
            $buf .= $c;
        }
        $flushText();

        return $r;
    }

    public static function looksEnglishText(string $t): bool
    {
        $d = trim(html_entity_decode($t, ENT_QUOTES | ENT_HTML5));
        if (! preg_match('/[A-Za-z]{2,}/', $d)) {
            return false;
        }
        if (preg_match('/^(Vyrobel|VYROBEL|vyrobel\.com|ADR|RevPAR|OTA|VAT|IBAN|PDF|CSV|ID|2FA|TOTP|Stripe|Google|X|Booking\.com|Airbnb|Expedia|Channex|UTC|EUR|USD|GBP|€|\$|£|AM|PM|N\/A|[A-Z]{1,3})$/', $d)) {
            return false;   // brand / acronym / currency on its own
        }
        if (preg_match('/^[\w\-]+\s*[:=]\s*[\'"]?[\w\-]+/', $d) && preg_match('/(=>|;|\$)/', $d)) {
            return false;   // stray code
        }

        return true;
    }

    public static function scanTag(string $tag, array &$r): void
    {
        if (preg_match('/^<\/|^<!/', $tag)) {
            return;
        }
        if (! preg_match('/^<([A-Za-z][\w\-.:]*)/', $tag, $tm)) {
            return;
        }
        $name = strtolower($tm[1]);
        $isComponent = str_starts_with($name, 'x-') || str_starts_with($name, 'livewire:');
        $type = '';
        if (preg_match('/\stype\s*=\s*["\']([^"\']*)/', $tag, $ty)) {
            $type = strtolower($ty[1]);
        }
        preg_match_all('/\s([:@]{0,2}[A-Za-z_][\w\-.:@]*)(?:\s*=\s*("([^"]*)"|\'([^\']*)\'))?/s', substr($tag, strlen($tm[0])), $am, PREG_SET_ORDER);
        foreach ($am as $a) {
            $attr = $a[1];
            $val = isset($a[3]) && $a[3] !== '' ? $a[3] : ($a[4] ?? '');
            $lattr = strtolower($attr);
            if ($val === '') {
                continue;
            }
            $r['keys'] += self::countKeys($val);
            // Alpine / Livewire bound attributes hold JS
            if (preg_match('/^(x-|:|@|wire:)/', $lattr) && $lattr !== 'wire:confirm') {
                $js = preg_replace('/\x01/', '0', $val) ?? $val;
                array_push($r['js'], ...self::stringLiterals($js, 'js'));

                continue;
            }
            $plain = trim(str_replace(["\x01", "\x02"], ' ', $val));
            if (preg_match('/^[\w.$]+\s*(=|\+\+|--|\(|\?)|;\s*$|&&|\|\||===?/', $plain)) {
                continue;   // JS / an expression in a plain attribute
            }
            if ($plain === '' || ! preg_match('/[A-Za-z]{2,}/', $plain)) {
                continue;
            }
            if (in_array($lattr, self::VISIBLE_ATTRS, true)
                || ($lattr === 'value' && in_array($type, ['submit', 'button', 'reset'], true))
                || ($lattr === 'content' && preg_match('/\sname\s*=\s*["\'](description|og:|twitter:)/', $tag))
                || ($isComponent && ! in_array($lattr, self::NON_TEXT_ATTRS, true) && ! str_starts_with($lattr, 'data-') && self::looksEnglish($plain))) {
                if (self::looksEnglishText($plain)) {
                    $r['attr'][] = $plain;
                }
            }
        }
    }

    /** English-looking strings in PHP source that are probably SHOWN (by context), via token_get_all — comments are tokens. */
    public static function visiblePhpStrings(string $src, bool $anyContext = false, bool $maybeToo = false): array
    {
        $out = [];
        try {
            $toks = token_get_all($src);
        } catch (\Throwable) {
            return [];
        }
        $sig = [];   // significant tokens so far
        $fn = '';
        $prevSig = null;
        foreach ($toks as $idx => $t) {
            $id = is_array($t) ? $t[0] : null;
            if ($id === T_STRING && $prevSig === T_FUNCTION) {
                $fn = strtolower($t[1]);
            }
            if ($id !== T_WHITESPACE && $id !== T_COMMENT && $id !== T_DOC_COMMENT) {
                $prevSig = $id;
            }
            if ($id === T_WHITESPACE || $id === T_COMMENT || $id === T_DOC_COMMENT) {
                continue;
            }
            if ($id === T_CONSTANT_ENCAPSED_STRING || $id === T_ENCAPSED_AND_WHITESPACE) {
                $raw = $t[1];
                $s = $id === T_CONSTANT_ENCAPSED_STRING ? stripcslashes(substr($raw, 1, -1)) : $raw;
                // the next significant token: '=>' means this string is an array KEY
                $next = null;
                for ($n = $idx + 1; $n < count($toks); $n++) {
                    $nt = $toks[$n];
                    if (is_array($nt) && in_array($nt[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                        continue;
                    }
                    $next = is_array($nt) ? $nt[1] : $nt;
                    break;
                }
                if ($next !== '=>' && self::looksEnglish($s)) {
                    $ctx = self::contextOf($sig);
                    if ($ctx === 'unknown' && preg_match('/label|title|summary|description|heading|text|message|caption|hint|subject|copy|placeholder|empty|tooltip|headline|explain|sentence|phrase|word|noun|verb|greeting|reason|advice|blurb|tagline|intro|line/', $fn)) {
                        $ctx = 'visible';
                    }
                    if ($ctx === 'skip') {
                        // logs, queries, exceptions, translation keys
                    } elseif ($ctx === 'visible' || $anyContext) {
                        $out[] = trim($s);
                    } elseif ($maybeToo) {
                        $out[] = '~'.trim($s);
                    }
                }
            }
            $sig[] = is_array($t) ? [$t[0], $t[1]] : [null, $t];
            if (count($sig) > 40) {
                array_shift($sig);
            }
        }

        return $out;
    }

    /** Walk back from a string to decide: inside a skipped call, a visible key/call, or unknown. */
    public static function contextOf(array $sig): string
    {
        $depth = 0;
        $sawArrow = null;
        for ($i = count($sig) - 1; $i >= 0; $i--) {
            [$id, $txt] = $sig[$i];
            if ($txt === ')' || $txt === ']') {
                $depth++;

                continue;
            }
            if ($txt === '(' || $txt === '[') {
                if ($depth > 0) {
                    $depth--;

                    continue;
                }
                // the call that opened this argument list
                for ($k = $i - 1; $k >= 0; $k--) {
                    [$kid, $ktxt] = $sig[$k];
                    if ($kid === T_STRING || $kid === T_NAME_QUALIFIED || $kid === T_NAME_FULLY_QUALIFIED || $kid === T_LIST || $kid === T_ISSET || $kid === T_EXIT) {
                        $fn = strtolower(ltrim((string) preg_replace('/^.*\\\\/', '', $ktxt), '\\'));
                        if (in_array($fn, ['__', 'trans', 'trans_choice'], true)) {
                            return 'skip';
                        }
                        if (in_array($fn, self::SKIP_PHP_CALLS, true) || str_ends_with($fn, 'exception') || str_ends_with($fn, 'error') && $fn !== 'adderror' && $fn !== 'error') {
                            return 'skip';
                        }
                        if (in_array($fn, self::VISIBLE_PHP_CALLS, true)) {
                            return 'visible';
                        }
                        break;
                    }
                    if ($kid === T_NEW) {
                        break;
                    }
                    break;
                }
                if ($sawArrow !== null) {
                    return $sawArrow;
                }

                continue;
            }
            if ($depth === 0 && $txt === '=>' && $sawArrow === null) {
                // the key of this array element
                for ($k = $i - 1; $k >= 0; $k--) {
                    [$kid, $ktxt] = $sig[$k];
                    if ($kid === T_CONSTANT_ENCAPSED_STRING) {
                        $key = strtolower(trim($ktxt, '\'"'));
                        foreach (self::VISIBLE_PHP_KEYS as $vk) {
                            if ($key === $vk || str_ends_with($key, '_'.$vk) || str_starts_with($key, $vk.'_')) {
                                return 'visible';
                            }
                        }
                    }
                    break;
                }
                $sawArrow = 'unknown';
            }
            if ($depth === 0 && ($txt === ';' || $txt === '{' || $txt === '}')) {
                break;
            }
            if ($depth === 0 && $id === T_RETURN) {
                return 'unknown';
            }
            if ($depth === 0 && $id === T_DOUBLE_ARROW) {
                continue;
            }
        }

        return 'unknown';
    }

    public static function scanPhp(string $src): array
    {
        return ['keys' => self::countKeys($src), 'visible' => self::visiblePhpStrings($src), 'maybe' => array_values(array_filter(self::visiblePhpStrings($src, false, true), fn ($s) => str_starts_with($s, '~')))];
    }
}
