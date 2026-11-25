<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

/**
 * Daily copy of the MySQL database in storage/app/backups (not reachable from the web), compressed,
 * keeping the last 14 days. The password reaches mysqldump through its environment, never the
 * command line, so it does not show in process lists or cron output.
 */
#[Signature('app:backup-database {--keep=14 : Days of backups to keep}')]
#[Description('Save the database and case documents, then expire old backups')]
class BackupDatabase extends Command
{
    public function handle(): int
    {
        $connection = config('database.connections.'.config('database.default'));

        if (($connection['driver'] ?? null) !== 'mysql') {
            $this->line('Backups are made for MySQL only; this site uses '.($connection['driver'] ?? 'no database').'.');

            return self::SUCCESS;
        }

        $directory = storage_path('app/backups');

        if (! is_dir($directory)) {
            mkdir($directory, 0700, true);
        }

        $stamp = now()->format('Y-m-d-His').'-'.bin2hex(random_bytes(4));
        $file = $directory.'/db-'.$stamp.'.sql.gz';
        $documents = $directory.'/documents-'.$stamp.'.tar.gz';
        $documentRoot = Storage::disk('local')->path('case-documents');
        $dump = new Process(
            ['mysqldump', '--single-transaction', '--quick', '--no-tablespaces', '--host='.$connection['host'], '--port='.$connection['port'], '--user='.$connection['username'], $connection['database']],
            null,
            ['MYSQL_PWD' => (string) $connection['password']],
            null,
            600,
        );

        $gzip = gzopen($file, 'wb6');
        $dump->run(function (string $type, string $output) use ($gzip): void {
            if ($type === Process::OUT) {
                gzwrite($gzip, $output);
            }
        });
        gzclose($gzip);

        if (! $dump->isSuccessful() || filesize($file) < 100) {
            @unlink($file);
            Log::error('Database backup failed', ['error' => trim($dump->getErrorOutput())]);
            $this->error('Backup failed: '.trim($dump->getErrorOutput()));

            return self::FAILURE;
        }

        chmod($file, 0600);

        if (is_dir($documentRoot)) {
            $archive = new Process(['tar', '-czf', $documents.'.part', '-C', dirname($documentRoot), basename($documentRoot)], timeout: 900);
            $archive->run();

            if (! $archive->isSuccessful()) {
                @unlink($documents.'.part');
                Log::error('Case document backup failed', ['error' => trim($archive->getErrorOutput())]);
                $this->error('Case document backup failed. The database backup was saved.');

                return self::FAILURE;
            }

            chmod($documents.'.part', 0600);
            rename($documents.'.part', $documents);
            $this->info('Saved '.basename($documents));
        }

        $cutoff = now()->subDays((int) $this->option('keep'))->getTimestamp();

        foreach (array_merge(glob($directory.'/db-*.sql.gz') ?: [], glob($directory.'/documents-*.tar.gz') ?: []) as $old) {
            if (filemtime($old) < $cutoff) {
                unlink($old);
            }
        }

        $this->info('Saved '.basename($file).' ('.round(filesize($file) / 1024).' KB).');

        return self::SUCCESS;
    }
}
