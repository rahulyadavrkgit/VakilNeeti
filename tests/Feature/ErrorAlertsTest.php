<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorAlertsTest extends TestCase
{
    use RefreshDatabase;

    private string $log;

    private ?string $originalLog = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->log = storage_path('logs/laravel.log');
        $this->originalLog = is_file($this->log) ? file_get_contents($this->log) : null;
    }

    protected function tearDown(): void
    {
        $this->originalLog === null ? @unlink($this->log) : file_put_contents($this->log, $this->originalLog);

        parent::tearDown();
    }

    public function test_recent_errors_reach_admins_and_listed_mobiles_once_a_day(): void
    {
        config(['services.alerts.mobiles' => ['98100 00001']]);
        $admin = User::factory()->create(['is_admin' => true]);
        $listed = User::factory()->create(['mobile' => '+919810000001']);
        $other = User::factory()->create(['mobile' => '+919810000002']);

        $old = now()->subDays(3)->format('Y-m-d H:i:s');
        $recent = now()->subHour()->format('Y-m-d H:i:s');
        file_put_contents($this->log, implode("\n", [
            "[{$old}] production.ERROR: Old problem {\"exception\":\"x\"}",
            "[{$recent}] production.ERROR: Undefined variable \$case (View: /home/x/show.blade.php) {\"exception\":\"x\"}",
            '#0 /home/x/vendor/file.php(12): stack line',
            "[{$recent}] production.ERROR: Undefined variable \$case (View: /home/x/show.blade.php) {\"exception\":\"x\"}",
            "[{$recent}] production.CRITICAL: Database went away",
            "[{$recent}] production.INFO: Not an error",
            "[{$recent}] testing.ERROR: Written by a test run on this machine",
        ])."\n");

        $this->artisan('app:error-alerts')->expectsOutputToContain('3 errors; told 2 people')->assertSuccessful();
        $this->artisan('app:error-alerts')->assertSuccessful();

        $this->assertSame(1, $admin->notifications()->count());
        $this->assertSame(1, $listed->notifications()->count());
        $this->assertSame(0, $other->notifications()->count());

        $alert = $admin->notifications()->first()->data;
        $this->assertSame('alert', $alert['kind']);
        $this->assertSame('3 site errors in the last 24 hours', $alert['title']);
        $this->assertStringStartsWith('2× Undefined variable $case', $alert['body']);
        $this->assertStringContainsString('1× Database went away', $alert['body']);
        $this->assertStringNotContainsString('Old problem', $alert['body']);
        $this->assertStringNotContainsString('test run', $alert['body']);
    }

    public function test_a_quiet_day_sends_nothing_and_the_check_runs_every_morning(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        file_put_contents($this->log, '['.now()->format('Y-m-d H:i:s')."] production.INFO: All fine\n");

        $this->artisan('app:error-alerts')->expectsOutputToContain('No errors logged')->assertSuccessful();
        $this->assertSame(0, $admin->notifications()->count());

        $expression = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains((string) $event->command, 'app:error-alerts'))?->expression;
        $this->assertSame('0 9 * * *', $expression);
    }
}
