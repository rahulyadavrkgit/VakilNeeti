<?php

namespace Tests\Feature;

use App\Models\CourtHearing;
use App\Models\CourtRecordImport;
use App\Models\LegalCase;
use App\Models\Task;
use App\Models\User;
use App\Services\Msg91EmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CaseHearingFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-03-10 09:00:00');
        $this->actingAs(User::factory()->create());

        $case = fn (string $title) => LegalCase::create(['case_title' => $title, 'court_name' => 'Saket Courts', 'case_status' => 'pending']);

        // Added by hand for today: only the hearing's own date is set.
        CourtHearing::create(['case_id' => $case('Own Date Today')->id, 'hearing_date' => '2026-03-10', 'hearing_time' => '10:30']);
        // Fixed at the last hearing for tomorrow.
        CourtHearing::create(['case_id' => $case('Next Date Tomorrow')->id, 'hearing_date' => '2026-02-20', 'next_hearing_date' => '2026-03-11']);
        // The last day of the next 7 days (today + 6).
        CourtHearing::create(['case_id' => $case('Sixth Day')->id, 'hearing_date' => '2026-03-16']);
        // Beyond next week.
        CourtHearing::create(['case_id' => $case('Later This Month')->id, 'next_hearing_date' => '2026-03-25']);
        // Every date has passed.
        CourtHearing::create(['case_id' => $case('All Dates Passed')->id, 'hearing_date' => '2026-02-01', 'next_hearing_date' => '2026-03-01']);
        // Never had a date.
        $case('Never Listed');
    }

    public function test_each_chip_shows_every_case_with_a_hearing_on_its_days_whichever_date_holds_it(): void
    {
        $shows = fn (string $filter) => collect($this->get(route('cases.index', ['hearing_filter' => $filter]))->assertOk()->viewData('cases')->items())
            ->pluck('case_title')->sort()->values()->all();

        $this->assertSame(['Own Date Today'], $shows('today'));
        $this->assertSame(['Next Date Tomorrow'], $shows('tomorrow'));
        $this->assertSame(['Next Date Tomorrow', 'Own Date Today', 'Sixth Day'], $shows('next7'));
        $this->assertSame(['All Dates Passed'], $shows('past'));
        $this->assertSame(['Never Listed'], $shows('no_upcoming'));
    }

    public function test_chips_show_counts_including_the_next_30_days(): void
    {
        $response = $this->get(route('cases.index'))->assertOk();

        $this->assertSame(['all' => 6, 'today' => 1, 'tomorrow' => 1, 'next7' => 3, 'next30' => 4, 'past' => 1, 'no_upcoming' => 1], $response->viewData('hearingCounts'));
        $response->assertSee('Next 30 days')->assertSee('No date')->assertSee('title="Cases with a hearing from today to 16 Mar"', false);

        $this->get(route('cases.index', ['hearing_filter' => 'next30']))->assertOk()->assertSee('Later This Month');
    }

    public function test_administrators_can_review_accounts_but_case_diaries_stay_private(): void
    {
        $privateCase = LegalCase::query()->firstOrFail();
        $outsider = User::factory()->create();
        $this->actingAs($outsider)->get(route('cases.index'))->assertOk()->assertDontSee($privateCase->case_title);
        $this->get(route('cases.show', $privateCase))->assertNotFound();
        $this->get(route('admin.accounts'))->assertForbidden();
        $details = route('admin.accounts.show', $privateCase->user_id);
        $this->get($details)->assertForbidden();
        $caseDetails = route('admin.accounts.cases.show', [$privateCase->user_id, $privateCase]);
        $this->get($caseDetails)->assertForbidden();

        $admin = User::factory()->create(['is_admin' => true]);
        $this->signInAsAdmin($admin);
        $this->assertAuthenticatedAs($admin);
        $this->get(route('admin.accounts'))->assertOk()
            ->assertViewHas('caseCount', 6)
            ->assertSee($outsider->email)
            ->assertDontSee($privateCase->case_title);
        $this->get($details)->assertOk()
            ->assertViewHas('totals', ['cases' => 6, 'court' => 6, 'date' => 2])
            ->assertSee($privateCase->case_title)
            ->assertSee('1 hearings');
        $this->get($details.'?review=date')->assertOk()
            ->assertSee('All Dates Passed')->assertSee('Never Listed')
            ->assertDontSee('Own Date Today');
        $this->get(route('admin.accounts.show', $outsider))->assertOk()->assertDontSee($privateCase->case_title);
        $this->get(route('admin.accounts', ['search' => $outsider->email]))->assertOk()
            ->assertViewHas('accounts', fn ($accounts) => $accounts->pluck('id')->all() === [$outsider->id]);
        $this->get($details.'?search=Own+Date')->assertOk()
            ->assertSee('Own Date Today')->assertDontSee('Never Listed');
        $this->get($caseDetails)->assertOk()
            ->assertSee('Own Date Today')->assertSee('Hearing history')->assertSee('10:30')
            ->assertSee('Document inventory');
        $this->get(route('admin.accounts.cases.show', [$outsider, $privateCase]))->assertNotFound();
        $this->get(route('cases.show', $privateCase))->assertNotFound();
        $this->get(route('cases.index'))->assertOk()->assertDontSee($privateCase->case_title);
        $this->get(route('cases.index', ['list' => 'disposed']))->assertOk()->assertDontSee($privateCase->case_title);
    }

    public function test_admin_review_feedback_and_account_access_are_audited(): void
    {
        $owner = auth()->user();
        $case = LegalCase::query()->where('case_title', 'Never Listed')->firstOrFail();
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->get(route('admin.overview'))->assertRedirect(route('admin.login'));
        $this->signInAsAdmin($admin);

        $import = CourtRecordImport::forceCreate(['source_name' => 'eCourts app export', 'raw_content' => 'Private file content',
            'import_status' => 'failed', 'user_id' => $owner->id]);
        $this->get(route('admin.overview'))->assertOk()->assertSee('Case review queue');
        $this->get(route('admin.operations', 'imports'))->assertOk()->assertSee('eCourts app export')->assertDontSee('Private file content');
        $this->post(route('admin.operations.retry', $import))->assertRedirect();
        $this->post(route('admin.operations.retry', $import))->assertRedirect();
        $this->assertSame(1, $owner->notifications()->where('data->title', 'Retry requested for a case import')->count());
        $this->get(route('admin.operations', 'errors'))->assertOk();
        $this->get(route('admin.review', 'date'))->assertOk()->assertSee('Never Listed');
        $this->post(route('admin.review.feedback', ['date', $case]), ['status' => 'confirmed', 'assigned_to' => $admin->id, 'reason' => 'Check with the court'])->assertRedirect();
        $this->get(route('admin.review', ['finding' => 'date', 'queue' => 'mine']))->assertOk()
            ->assertSee('Never Listed')->assertSee('Check with the court');
        $this->post(route('admin.review.feedback', ['date', $case]), ['status' => 'false-positive', 'reason' => 'Listed separately'])->assertRedirect();
        $this->get(route('admin.review', 'date'))->assertOk()->assertDontSee('Never Listed');
        $this->get(route('admin.review', ['finding' => 'date', 'reviewed' => 1]))->assertOk()->assertSee('Never Listed');

        $this->post(route('admin.accounts.access', $owner), ['action' => 'suspend'])->assertSessionHasErrors('reason');
        $this->post(route('admin.accounts.access', $admin), ['action' => 'suspend', 'reason' => 'Support investigation'])->assertForbidden();
        $this->post(route('admin.accounts.access', $owner), ['action' => 'suspend', 'reason' => 'Support investigation'])->assertRedirect();
        $this->assertNotNull($owner->fresh()->suspended_at);
        $this->get(route('admin.audit'))->assertOk()->assertSee('Support investigation')->assertSee('Listed separately');

        $this->actingAs($owner->fresh())->get(route('cases.index'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_a_new_administrator_sets_their_own_password_once(): void
    {
        $candidate = auth()->user();
        $admin = User::factory()->create(['is_admin' => true]);
        $this->signInAsAdmin($admin);
        $this->post(route('admin.accounts.access', $candidate), ['action' => 'grant-admin', 'reason' => 'New support administrator'])->assertRedirect();
        $this->assertTrue((bool) $candidate->fresh()->admin_password_setup);
        $this->post(route('admin.logout'))->assertRedirect();

        $code = null;
        $this->mock(Msg91EmailService::class, function ($mock) use (&$code, $candidate): void {
            $mock->shouldReceive('sendOtp')->once()->withArgs(function (string $email, string $value) use (&$code, $candidate): bool {
                $code = $value;

                return $email === $candidate->email;
            })->andReturnNull();
        });

        $this->post(route('admin.login.submit'), ['email' => $candidate->email, 'setup' => true])->assertRedirect(route('admin.verify'));
        $this->get(route('admin.verify'))->assertOk()->assertSee('New administrator password');
        $this->post(route('admin.verify.submit'), ['code' => $code, 'password' => 'sample-password-1', 'password_confirmation' => 'sample-password-1'])
            ->assertRedirect(route('admin.overview'));
        $this->assertAuthenticatedAs($candidate);
        $this->assertFalse((bool) $candidate->fresh()->admin_password_setup);
        $this->post(route('admin.login.submit'), ['email' => $candidate->email, 'setup' => true])->assertSessionHasErrors('email');
    }

    public function test_case_follow_ups_and_problem_reports_stay_in_the_workspace(): void
    {
        $owner = auth()->user();
        $case = LegalCase::query()->firstOrFail();
        $member = User::factory()->create(['team_owner_id' => $owner->id, 'team_role' => 'junior']);
        $outside = User::factory()->create();
        $payload = ['title' => 'Review the order', 'due_at' => today()->addDay()->toDateString()];

        $this->post(route('cases.follow-ups.store', $case), $payload + ['assigned_to' => $outside->id])->assertSessionHasErrors('assigned_to');
        $this->post(route('cases.follow-ups.store', $case), $payload + ['assigned_to' => $member->id])->assertRedirect();
        $task = Task::query()->where('assigned_to', $member->id)->sole();
        $this->actingAs($member)->patch(route('cases.follow-ups.status', [$case, $task]), ['status' => 'completed'])->assertRedirect();
        $this->assertNotNull($task->fresh()->completed_at);
        $this->post(route('cases.report', $case), ['page' => 'court-day', 'message' => 'The hearing date looks wrong'])->assertRedirect();
        $this->actingAs($outside)->post(route('cases.report', $case), ['page' => 'court-day', 'message' => 'Outside'])->assertNotFound();

        $this->signInAsAdmin(User::factory()->create(['is_admin' => true]));
        $this->get(route('admin.reports'))->assertOk()->assertSee('The hearing date looks wrong');
        $report = DB::table('case_support_reports')->first();
        $this->post(route('admin.reports.resolve', $report->id))->assertRedirect();
        $this->get(route('admin.reports'))->assertOk()->assertDontSee('The hearing date looks wrong');
    }

    private function signInAsAdmin(User $admin): void
    {
        $this->post(route('admin.login.submit'), ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('admin.overview'));
        $this->assertAuthenticatedAs($admin);
        $this->assertNull(session('admin_challenge'));
    }

    public function test_court_identity_preview_preserves_saved_cases_until_applied(): void
    {
        $case = LegalCase::query()->firstOrFail();
        LegalCase::query()->whereKey($case->id)->update(['court_level' => null, 'matter_id' => null]);

        $this->artisan('cases:court-identity')->expectsOutputToContain('Previewed')->assertSuccessful();
        $this->assertNull($case->fresh()->matter_id);
        $this->assertNull($case->fresh()->court_level);

        $this->artisan('cases:court-identity', ['--apply' => true])->expectsOutputToContain('Updated')->assertSuccessful();
        $this->assertNotNull($case->fresh()->matter_id);
        $this->assertSame('district', $case->fresh()->court_level);
        $this->assertCount(6, LegalCase::query()->pluck('matter_id')->unique());
    }

    public function test_the_date_tile_uses_the_nearest_date_and_its_time(): void
    {
        $this->get(route('cases.index', ['hearing_filter' => 'today']))
            ->assertOk()
            ->assertSeeInOrder(['Today', '10', '10:30', 'Own Date Today']);
    }
}
