<?php

declare(strict_types=1);

use App\Support\I18n\LocalNumber;
use App\Support\Money\MoneyInput;
use Illuminate\Contracts\Console\Kernel;

// ops/i18n/number-parity.php — D-MONEY-COMMA: the PHP reader (App\Support\I18n\LocalNumber) and its script copy
// (resources/views/partials/local-number-script.blade.php) must read EVERY typed amount the same way, or a page would show one
// amount and save another. Renders the script under each page language, runs it in node against the same inputs as PHP —
// random strings from the characters people type plus fixed edge cases — and fails on the first difference.
//
//   php ops/i18n/number-parity.php [count=20000]

$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$n = (int) ($argv[1] ?? 20000);
mt_srand(20261028);
$alphabet = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '5', '.', ',', '.', ',', ' ', "\u{00A0}", "\u{202F}", '’', "'", '€', '$', '۵', '٫', '٬', '٣', 'a', '-'];
$inputs = ['150.50', '150,50', '1,234', '1.234', '1,234.50', '1.234,50', '1 234,50', '150.', '.5', ',5', '0,5', '1.2345', '1,2,3',
    '1.234.567,89', '12,34,567.00', '۱۵۰٫۵۰', '€ 99', '', ' ', '00,10', '0', '0.00', '1,234,5', '12.345.6', '1’234.50'];
for ($i = 0; $i < $n; $i++) {
    $len = mt_rand(1, 12);
    $s = '';
    for ($j = 0; $j < $len; $j++) {
        $s .= $alphabet[mt_rand(0, count($alphabet) - 1)];
    }
    $inputs[] = $s;
}

$tmp = sys_get_temp_dir().'/vy-number-parity';
@mkdir($tmp);
$failed = 0;
foreach (['en', 'de', 'fa', 'fr'] as $locale) {
    app()->setLocale($locale);
    $script = view('partials.local-number-script')->render();
    $js = preg_replace('~^.*?<script>|</script>.*$~s', '', $script);
    file_put_contents("$tmp/in.json", json_encode($inputs, JSON_UNESCAPED_UNICODE));
    file_put_contents("$tmp/run.js", "var window = {};\n".$js."\nconst ins = require('$tmp/in.json');\n"
        ."process.stdout.write(JSON.stringify(ins.map(s => [window.vyLocalNumber.normalize(s, 2), window.vyLocalNumber.normalize(s, 1), window.vyLocalNumber.toMinor(s)])));\n");
    $out = shell_exec('node '.escapeshellarg("$tmp/run.js"));
    $jsResults = json_decode((string) $out, true);
    if (! is_array($jsResults)) {
        fwrite(STDERR, "node did not run for $locale\n");
        exit(2);
    }
    $diffs = 0;
    foreach ($inputs as $i => $s) {
        $php = [LocalNumber::normalize($s, 2, $locale), LocalNumber::normalize($s, 1, $locale), MoneyInput::toMinor($s)];
        if ($php !== $jsResults[$i]) {
            $diffs++;
            if ($diffs <= 5) {
                echo "DIFF $locale ".json_encode($s, JSON_UNESCAPED_UNICODE).' php='.json_encode($php).' js='.json_encode($jsResults[$i])."\n";
            }
        }
    }
    $accepted = count(array_filter($inputs, fn ($s) => LocalNumber::normalize($s, 2, $locale) !== null));
    echo str_pad($locale, 3).' inputs '.count($inputs).' · read by both '.$accepted.' · differences '.$diffs."\n";
    $failed += $diffs;
}
echo $failed === 0 ? "OK — the page and the server read every input the same way\n" : "FAILED — $failed difference(s)\n";
exit($failed === 0 ? 0 : 1);
