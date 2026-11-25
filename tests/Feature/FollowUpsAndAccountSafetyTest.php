<?php

namespace Tests\Feature;

use App\Models\LegalCase;
use App\Models\Task;
use App\Models\User;
use App\Services\LiveCourtBoard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class FollowUpsAndAccountSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_follow_up_is_assigned_within_the_workspace_and_only_the_assignee_or_owner_updates_it(): void
    {
        $owner = User::factory()->create(['name' => 'Adv. Meera']);
        $junior = User::factory()->create(['name' => 'Junior Ravi', 'team_owner_id' => $owner->id, 'team_role' => 'junior']);
        $clerk = User::factory()->create(['name' => 'Clerk Sunil', 'team_owner_id' => $owner->id, 'team_role' => 'clerk']);
        $outsider = User::factory()->create();
        $this->actingAs($owner);
        $case = LegalCase::create(['case_title' => 'Asha vs State', 'court_name' => 'Saket Courts', 'case_status' => 'pending']);

        // Someone outside the workspace cannot be given the work.
        $this->post(route('cases.follow-ups.store', $case), ['title' => 'File reply', 'assigned_to' => $outsider->id, 'due_at' => today()->addDay()->toDateString()])
            ->assertSessionHasErrors('assigned_to');

        $this->post(route('cases.follow-ups.store', $case), ['title' => 'File reply', 'assigned_to' => $junior->id, 'due_at' => today()->addDay()->toDateString()])
            ->assertRedirect(route('cases.show', $case).'#case-follow-ups');
        $task = Task::query()->sole();
        $this->assertSame([$junior->id, $owner->id, 'pending'], [$task->assigned_to, $task->user_id, $task->status]);
        $this->assertSame('New case follow-up', $junior->notifications()->first()->data['title']);

        // A clerk who is not the assignee can see it but cannot change it.
        $this->actingAs($clerk)->get(route('cases.show', $case))->assertOk()->assertSee('File reply');
        $this->patch(route('cases.follow-ups.status', [$case, $task]), ['status' => 'completed'])->assertForbidden();

        $this->actingAs($junior)->patch(route('cases.follow-ups.status', [$case, $task]), ['status' => 'completed'])->assertRedirect();
        $this->assertNotNull($task->fresh()->completed_at);

        // Another advocate cannot reach the case or its follow-ups at all.
        $this->actingAs($outsider)->patch(route('cases.follow-ups.status', [$case, $task]), ['status' => 'pending'])->assertNotFound();
    }

    public function test_a_suspended_account_gets_no_code_and_a_clear_message(): void
    {
        Http::fake();
        config(['services.msg91.key' => 'k', 'services.msg91.email_domain' => 'mail.example.test', 'services.msg91.email_from' => 'no-reply@mail.example.test', 'services.msg91.otp_template_id' => 't']);
        User::factory()->create(['email' => 'asha@example.test', 'mobile' => '+919876543210', 'mobile_verified_at' => now(), 'suspended_at' => now()]);

        $this->post(route('login.submit'), ['email' => 'asha@example.test'])
            ->assertSessionHasErrors(['email' => 'This account is suspended. Write to support@vakilniti.com if you think this is a mistake.'], null, 'login');
        $this->post(route('login.submit'), ['mobile' => '9876543210'])->assertSessionHasErrors('mobile', null, 'login');

        Http::assertNothingSent();
        $this->assertDatabaseCount('user_registration_otps', 0);
        $this->assertGuest();
    }

    public function test_promoting_an_existing_account_makes_it_set_its_own_admin_password(): void
    {
        $user = User::factory()->create(['email' => 'rahul@example.test']);

        $this->artisan('vakilneeti:make-admin', ['email' => 'Rahul@Example.test', '--yes' => true])
            ->expectsOutputToContain('Set up a newly granted administrator account')
            ->assertSuccessful();

        $user->refresh();
        $this->assertTrue((bool) $user->is_admin);
        $this->assertTrue((bool) $user->admin_password_setup);
        $this->assertSame(1, DB::table('admin_audit_entries')->where('account_id', $user->id)->count());

        $member = User::factory()->create(['email' => 'junior@example.test', 'team_owner_id' => $user->id, 'team_role' => 'junior']);
        $this->artisan('vakilneeti:make-admin', ['email' => $member->email, '--yes' => true])->assertFailed();
    }

    public function test_location_lists_may_be_kept_by_the_browser_but_signed_in_pages_may_not(): void
    {
        $this->actingAs(User::factory()->create());

        $this->getJson(route('api.locations.court-districts', 'UP'))->assertOk()->assertHeader('Cache-Control', 'max-age=86400, public');
        $this->get(route('court-day'))->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_an_unreachable_court_board_is_a_warning_not_a_site_error(): void
    {
        Http::fake(fn () => throw new ConnectionException('Connection timed out'));
        Log::spy();

        $board = app(LiveCourtBoard::class)->board('supreme-court');
        $this->assertFalse($board['ok']);
        $this->assertSame([], $board['rooms']);

        Log::shouldHaveReceived('warning')->withArgs(fn (string $message) => str_contains($message, 'supreme-court'));
        Log::shouldNotHaveReceived('error');
    }
}
