<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Privacy\RetentionCoverage;
use Illuminate\Console\Command;

/**
 * TONIGHT 01 (2026-09-25) — regenerate the SHIPPED inventory data file from the audited doc. The retention meter on
 * the platform console reads the data file ({@see RetentionCoverage::DATA_FILE}) because `docs/` is not deployed.
 *
 *   php artisan privacy:inventory-data           rewrite resources/data/data-inventory.json from the doc
 *   php artisan privacy:inventory-data --check   say whether the committed file is current; write nothing
 *
 * ⚠️ Run it in the repository (where the doc is), never on the host. Edit the doc, regenerate, commit both — the
 *    suite fails if the committed file is not exactly what this produces.
 */
class ExportDataInventory extends Command
{
    protected $signature = 'privacy:inventory-data {--check : Only report whether the committed file is current}';

    protected $description = 'Regenerate the shipped data-inventory file (read by the retention meter) from docs/legal/DATA-INVENTORY.md.';

    public function handle(): int
    {
        $source = RetentionCoverage::sourcePath();
        if (! is_readable($source)) {
            $this->error(RetentionCoverage::SOURCE_DOC.' is not here — run this in the repository, not on the host.');

            return self::FAILURE;
        }

        $categories = RetentionCoverage::categoriesFrom((string) file_get_contents($source), RetentionCoverage::liveTables());
        $bytes = RetentionCoverage::render($categories);
        $target = RetentionCoverage::inventoryPath();

        if ($this->option('check')) {
            $current = is_readable($target) && (string) file_get_contents($target) === $bytes;
            $current
                ? $this->info(RetentionCoverage::DATA_FILE.' is current ('.count($categories).' categories).')
                : $this->error(RetentionCoverage::DATA_FILE.' is STALE — run `php artisan privacy:inventory-data` and commit it.');

            return $current ? self::SUCCESS : self::FAILURE;
        }

        if (! is_dir(dirname($target))) {
            mkdir(dirname($target), 0755, true);
        }
        file_put_contents($target, $bytes);
        $this->info(count($categories).' categories written to '.RetentionCoverage::DATA_FILE.'.');

        return self::SUCCESS;
    }
}
