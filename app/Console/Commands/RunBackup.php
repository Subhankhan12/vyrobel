<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Ops\BackupStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * VY-12 (audit of 2026-11-24; his ruling: "schedule now, copy later") — THE DAILY ENCRYPTED BACKUP.
 *
 * `ops/backup.sh` made an encrypted archive (the database + storage/app) only when somebody ran it by hand. This command is what
 * the schedule runs: it reads the passphrase from the file named in `ops.backup.passphrase_file`, runs THE SAME SCRIPT the restore
 * drill proves (never a second dump written here), and — when `ops.backup.offsite_disk` names a disk — copies the archive there.
 *
 * ⚠️⚠️ THE PASSPHRASE NEVER LEAVES THE PROCESS ENVIRONMENT. It is read from a file outside the application, handed to the script as
 *    `BACKUP_PASSPHRASE` in its environment (never an argument — arguments show in `ps`), and is not printed, logged or returned.
 * ⚠️ NO PASSPHRASE, NO BACKUP: the command refuses (exit 2) and names the SETTING. It never writes an unencrypted archive — the
 *    archive holds guest personal data, and the script itself refuses too.
 * ⚠️ IT FAILS LOUDLY: a non-zero exit and one error line (the script's last words, which carry no secret). The health board reads
 *    the folder with its own eyes ({@see BackupStatus}) — a job cannot report that it did not run.
 * ⚠️ `APP_KEY` IS NOT IN THE ARCHIVE, by design (see ops/backup.sh): the key and the data it protects are kept apart.
 */
class RunBackup extends Command
{
    protected $signature = 'backup:run';

    protected $description = 'Make the encrypted backup (database + storage/app) with ops/backup.sh, and copy it to the off-server disk when one is set.';

    /** Seconds the script may take before it is stopped: a dump that hangs must not hold the scheduler all night. */
    private const TIMEOUT_SECONDS = 1800;

    public function handle(): int
    {
        $passphrase = BackupStatus::passphrase();
        if ($passphrase === null) {
            $this->error('backup:run — REFUSING: no backup passphrase. Put it in a file outside the application (mode 600) and set OPS_BACKUP_PASSPHRASE_FILE to that file\'s path, then rebuild the config cache.');

            return 2;
        }

        $dest = BackupStatus::dest();
        $process = new Process(['bash', (string) config('ops.backup.script')], base_path(), [
            'BACKUP_PASSPHRASE' => $passphrase,
            'BACKUP_DEST' => $dest,
            'BACKUP_RETAIN_DAYS' => (string) max(1, (int) config('ops.backup.retain_days', 14)),
            'APP_DIR' => base_path(),
        ], null, self::TIMEOUT_SECONDS);

        try {
            $process->run();
        } catch (Throwable $e) {
            return $this->failed('the script could not be run: '.$e->getMessage());
        }

        if (! $process->isSuccessful()) {
            return $this->failed('ops/backup.sh exited '.$process->getExitCode().': '.$this->lastWords($process->getErrorOutput().$process->getOutput()));
        }

        // the script prints the archive's path as its last line
        $lines = array_values(array_filter(array_map('trim', explode("\n", $process->getOutput()))));
        $archive = (string) end($lines);
        if ($archive === '' || ! is_file($archive) || (int) filesize($archive) === 0) {
            return $this->failed('the script reported success but no archive was found.');
        }

        $this->info(sprintf('Backup made: %s (%d bytes).', basename($archive), (int) filesize($archive)));

        $disk = BackupStatus::offsiteDisk();
        if ($disk === null) {
            $this->warn('No off-server disk is set (OPS_BACKUP_OFFSITE_DISK): this archive exists on this server only.');

            return self::SUCCESS;
        }

        try {
            $this->copyOffsite($disk, $archive);
        } catch (Throwable $e) {
            return $this->failed('the archive was made but NOT copied off the server (disk "'.$disk.'"): '.$e->getMessage());
        }

        $this->info('Copied to the off-server disk "'.$disk.'".');

        return self::SUCCESS;
    }

    /** Copy one archive to the off-server disk, then drop copies there older than the retention period. */
    private function copyOffsite(string $disk, string $archive): void
    {
        $remote = Storage::disk($disk);
        $path = BackupStatus::OFFSITE_FOLDER.'/'.basename($archive);

        $stream = fopen($archive, 'rb');
        if ($stream === false) {
            throw new \RuntimeException('the archive could not be opened for reading.');
        }
        try {
            if ($remote->writeStream($path, $stream) === false || ! $remote->exists($path)) {
                throw new \RuntimeException('the disk did not accept the file.');
            }
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        BackupStatus::markOffsiteCopied();

        // retention on the copy, by the UTC stamp in the name (a remote file's own time is not always readable)
        $cutoff = now()->utc()->subDays(max(1, (int) config('ops.backup.retain_days', 14)))->format('Ymd\THis\Z');
        foreach ($remote->files(BackupStatus::OFFSITE_FOLDER) as $file) {
            if (preg_match('/vyrobel-(\d{8}T\d{6}Z)/', basename($file), $m) === 1 && $m[1] < $cutoff && $file !== $path) {
                $remote->delete($file);
            }
        }
    }

    private function failed(string $why): int
    {
        $message = 'backup:run FAILED — '.$why;
        Log::error($message);
        $this->error($message);

        return self::FAILURE;
    }

    /** The end of the script's output, on one line — enough to say what broke, bounded so a log line stays a line. */
    private function lastWords(string $output): string
    {
        $text = trim((string) preg_replace('/\s+/', ' ', $output));

        return $text === '' ? '(no output)' : mb_substr($text, -400);
    }
}
