<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class DeployAndBackupCommandsTest extends TestCase
{
    public function test_refresh_after_deploy_and_backups_are_scheduled(): void
    {
        $events = collect(app(Schedule::class)->events())->mapWithKeys(fn ($event) => [$event->command => $event->expression]);

        $refresh = $events->first(fn ($expression, $command) => str_contains((string) $command, 'app:refresh-after-deploy'));
        $backup = $events->first(fn ($expression, $command) => str_contains((string) $command, 'app:backup-database'));

        $this->assertSame('* * * * *', $refresh);
        $this->assertSame('30 2 * * *', $backup);
    }

    public function test_backups_are_skipped_on_a_database_other_than_mysql(): void
    {
        $this->artisan('app:backup-database')
            ->expectsOutputToContain('Backups are made for MySQL only')
            ->assertSuccessful();
    }

    public function test_refresh_after_deploy_reads_the_checked_out_commit(): void
    {
        $marker = storage_path('app/deployed-commit.txt');
        $before = is_file($marker) ? file_get_contents($marker) : null;
        @unlink($marker);

        try {
            if (! is_dir(base_path('.git'))) {
                $this->artisan('app:refresh-after-deploy')->expectsOutputToContain('Not a git checkout')->assertSuccessful();

                return;
            }

            // A commit not refreshed for yet is refreshed once; the next run sees it is done.
            file_put_contents($marker, 'old-commit');
            $this->artisan('app:refresh-after-deploy')->expectsOutputToContain('Refreshed for')->assertSuccessful();
            $this->artisan('app:refresh-after-deploy')->expectsOutputToContain('Already refreshed for')->assertSuccessful();
        } finally {
            $before === null ? @unlink($marker) : file_put_contents($marker, $before);
            $this->artisan('optimize:clear');
        }
    }
}
