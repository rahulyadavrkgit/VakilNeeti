<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Hostinger pulls new code from GitHub on every push, but cannot run commands afterwards. Until the
 * saved routes and settings are rebuilt, pages can fail (for example "Route [tribunals.index] not
 * defined"). Run every minute by the scheduler: when the checked-out commit has changed, run the new
 * database migrations and rebuild the caches, then remember the commit.
 */
#[Signature('app:refresh-after-deploy {--force : Refresh even if the commit has not changed}')]
#[Description('After an automatic deploy, run migrations and rebuild the caches once')]
class RefreshAfterDeploy extends Command
{
    public function handle(): int
    {
        $commit = $this->currentCommit();

        if ($commit === null) {
            $this->line('Not a git checkout; nothing to do.');

            return self::SUCCESS;
        }

        $marker = storage_path('app/deployed-commit.txt');
        $previous = is_file($marker) ? trim((string) file_get_contents($marker)) : null;

        if ($previous === $commit && ! $this->option('force')) {
            $this->line('Already refreshed for '.substr($commit, 0, 7).'.');

            return self::SUCCESS;
        }

        $migrated = $this->call('migrate', ['--force' => true]);
        $optimized = $this->call('optimize');

        if ($migrated !== self::SUCCESS || $optimized !== self::SUCCESS) {
            Log::error('Refresh after deploy failed', ['commit' => $commit, 'migrate' => $migrated, 'optimize' => $optimized]);

            return self::FAILURE;
        }

        file_put_contents($marker, $commit);
        Log::info('Refreshed after deploy', ['from' => $previous, 'to' => $commit]);
        $this->info('Refreshed for '.substr($commit, 0, 7).'.');

        return self::SUCCESS;
    }

    /**
     * The checked-out commit, read from .git without running git: HEAD holds either the commit itself
     * (a detached checkout, as Hostinger makes) or the branch whose ref file or packed ref holds it.
     */
    private function currentCommit(): ?string
    {
        $git = base_path('.git');
        $head = is_file($git.'/HEAD') ? trim((string) file_get_contents($git.'/HEAD')) : '';

        if (preg_match('/^[0-9a-f]{40}$/', $head)) {
            return $head;
        }

        if (! str_starts_with($head, 'ref: ')) {
            return null;
        }

        $ref = substr($head, 5);

        if (is_file($git.'/'.$ref)) {
            return trim((string) file_get_contents($git.'/'.$ref)) ?: null;
        }

        if (is_file($git.'/packed-refs') && preg_match('/^([0-9a-f]{40}) '.preg_quote($ref, '/').'$/m', (string) file_get_contents($git.'/packed-refs'), $match)) {
            return $match[1];
        }

        return null;
    }
}
