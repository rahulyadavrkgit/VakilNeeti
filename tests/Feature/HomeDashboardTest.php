<?php

namespace Tests\Feature;

use App\Models\CourtBench;
use App\Models\CourtHearing;
use App\Models\LegalCase;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HomeDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-03-10 09:00:00');
    }

    public function test_home_lists_upcoming_hearings_and_search_narrows_them(): void
    {
        $this->actingAs(User::factory()->create());
        $asha = LegalCase::create(['case_title' => 'Asha vs State', 'court_name' => 'District Court Lucknow', 'case_status' => 'pending']);
        $ravi = LegalCase::create(['case_title' => 'Ravi vs Union', 'court_name' => 'Delhi High Court', 'case_status' => 'pending']);
        CourtHearing::create(['case_id' => $asha->id, 'hearing_date' => '2026-03-12']);
        CourtHearing::create(['case_id' => $ravi->id, 'hearing_date' => '2026-03-15']);
        CourtHearing::create(['case_id' => $ravi->id, 'hearing_date' => '2026-05-15', 'hearing_stage' => 'Too far ahead']);
        Task::create(['title' => 'Old task', 'due_at' => '2026-03-01 10:00:00', 'priority' => 'low', 'status' => 'pending']);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['id="hearing-search"', 'id="hearing-day-2026-03-12"', 'Asha vs State', 'id="hearing-day-2026-03-15"', 'Ravi vs Union'], false)
            ->assertSee('Too far ahead')
            ->assertSee('1 overdue task');

        $this->get(route('dashboard', ['hearing_search' => 'Delhi']))
            ->assertOk()
            ->assertSee('Ravi vs Union')
            ->assertDontSee('Asha vs State');
    }

    public function test_upcoming_hearings_show_court_room_judge_and_our_side(): void
    {
        $this->actingAs(User::factory()->create());
        $case = LegalCase::create([
            'case_title' => 'Prahlad vs Sanjay',
            'court_name' => 'Saket Courts',
            'court_room' => '304, Main court building',
            'judge_name' => 'Ms. Niti Phutela',
            'appearing_for' => 'petitioner',
            'case_status' => 'pending',
        ]);
        CourtHearing::create(['case_id' => $case->id, 'hearing_date' => '2026-03-12']);
        CourtHearing::create(['case_id' => $case->id, 'hearing_date' => '2026-03-13', 'judge_name' => 'Sh. Dheeraj Mor', 'court_room' => '505']);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['Prahlad vs Sanjay', 'Saket Courts', 'Ms. Niti Phutela', 'Petitioner', '304, Main court building'])
            ->assertSeeInOrder(['Sh. Dheeraj Mor', '505'])
            // No VC link for this court: Join VC and Copy VC link stay in their place, greyed out.
            ->assertSeeInOrder(['Prahlad vs Sanjay', 'title="No VC link for this court yet"', 'Join VC', 'title="No VC link for this court yet"', 'Copy VC link'], false);
    }

    public function test_home_groups_hearings_by_date_and_keeps_todays_hearings_together(): void
    {
        $this->actingAs(User::factory()->create());
        $first = LegalCase::create(['case_title' => 'Started vs Earlier', 'court_name' => 'Saket Courts', 'case_status' => 'pending']);
        $second = LegalCase::create(['case_title' => 'Later vs Today', 'court_name' => 'Saket Courts', 'case_status' => 'pending']);
        $next = LegalCase::create([
            'case_title' => 'Meera vs Union',
            'case_number' => 'W.P.(C)/7/2026',
            'court_name' => 'Delhi High Court',
            'court_room' => '37',
            'judge_name' => 'Justice Dinesh Mehta',
            'appearing_for' => 'appellant',
            'vc_link' => 'https://dhc.webex.com/meet/court37',
            'case_status' => 'pending',
        ]);
        CourtHearing::create(['case_id' => $first->id, 'hearing_date' => '2026-03-10', 'hearing_time' => '08:00']);
        CourtHearing::create(['case_id' => $second->id, 'hearing_date' => '2026-03-10', 'hearing_time' => '14:00']);
        CourtHearing::create(['case_id' => $next->id, 'hearing_date' => '2026-03-11', 'hearing_time' => '10:30']);

        $html = $this->get(route('dashboard'))->assertOk()->getContent();
        $today = (int) strpos($html, 'id="hearing-day-2026-03-10"');
        $tomorrow = (int) strpos($html, 'id="hearing-day-2026-03-11"');
        $top = substr($html, $today, $tomorrow - $today);
        $this->assertStringContainsString('10 Mar 2026', $top);
        $this->assertStringContainsString('2 hearings', $top);
        $this->assertStringContainsString('Started vs Earlier', $top);
        $this->assertStringContainsString('Later vs Today', $top);
        $this->assertStringContainsString('title="Share all hearings of this day"', $top);
        $this->assertStringNotContainsString('Meera vs Union', $top);

        $this->travelTo('2026-03-10 23:00:00');
        CourtHearing::query()->whereIn('case_id', [$first->id, $second->id])->delete();
        $this->get(route('dashboard'))->assertOk()
            ->assertSeeInOrder(['id="hearing-day-2026-03-11"', 'Tomorrow', '11 Mar 2026', 'Meera vs Union', 'W.P.(C)/7/2026', 'Hearing · 10:30', 'Justice Dinesh Mehta', 'Appellant', '37', 'href="https://dhc.webex.com/meet/court37"', 'title="Share this hearing"'], false)
            ->assertDontSee('id="hearing-day-2026-03-10"', false);
    }

    public function test_the_hearing_list_goes_on_to_the_last_hearing_about_ten_at_a_time_without_splitting_a_day(): void
    {
        $this->actingAs(User::factory()->create());

        // Nine single days, then a day with three hearings, then two more days, the last one months away.
        $dates = ['2026-03-11', '2026-03-12', '2026-03-13', '2026-03-14', '2026-03-15', '2026-03-16', '2026-03-17', '2026-03-18', '2026-03-19',
            '2026-03-20', '2026-03-20', '2026-03-20', '2026-03-21', '2026-09-30'];
        foreach ($dates as $index => $date) {
            $case = LegalCase::create(['case_title' => 'Matter '.($index + 1).' vs State', 'court_name' => 'Saket Courts', 'case_status' => 'pending']);
            CourtHearing::create(['case_id' => $case->id, 'hearing_date' => $date]);
        }

        $first = $this->get(route('dashboard'))->assertOk();
        $first->assertSee('Matter 12 vs State')->assertDontSee('Matter 13 vs State');
        $more = route('home.hearings', ['after' => '2026-03-20']);
        $first->assertSee('data-load-more="'.e($more).'"', false);

        // The next load brings the rest, months ahead included, and says when the list has ended.
        $this->get($more, ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()
            ->assertSee('Matter 13 vs State')->assertSee('Matter 14 vs State')
            ->assertDontSee('Matter 12 vs State')->assertDontSee('data-load-more', false)
            ->assertSee('That is the last hearing in your diary.');

        // Opened as a page, the link goes to the calendar's list from the next day.
        $this->get($more)->assertRedirect(route('cases.calendar', ['view' => 'list', 'date' => '2026-03-21']));
    }

    public function test_a_high_court_hearing_names_the_judge_sitting_in_its_room(): void
    {
        $this->actingAs(User::factory()->create());
        CourtBench::create(['court_key' => 'delhi-high-court', 'court_room' => '23', 'judges' => [['name' => "Hon'ble Mr. Justice Rajneesh Kumar Gupta"]], 'sort_order' => 1]);
        CourtBench::create(['court_key' => 'delhi-high-court', 'court_room' => '3', 'judges' => [['name' => "Hon'ble Mr. Justice A"], ['name' => "Hon'ble Ms. Justice B"]], 'sort_order' => 2]);
        $single = LegalCase::create(['case_title' => 'Nawab vs Anjuman', 'court_name' => 'High Court of Delhi', 'court_room' => 'Court No. 23', 'case_status' => 'pending']);
        $division = LegalCase::create(['case_title' => 'Bench vs Two', 'court_name' => 'Delhi High Court', 'court_room' => '3', 'case_status' => 'pending']);
        CourtHearing::create(['case_id' => $single->id, 'hearing_date' => '2026-03-11']);
        CourtHearing::create(['case_id' => $division->id, 'hearing_date' => '2026-03-12']);

        $this->get(route('dashboard'))->assertOk()
            ->assertSeeInOrder(['Nawab vs Anjuman', "Hon'ble Mr. Justice Rajneesh Kumar Gupta", 'Bench vs Two', "Hon'ble Mr. Justice A & Hon'ble Ms. Justice B"]);
        $this->get(route('cases.show', $single))->assertOk()->assertSee("Hon'ble Mr. Justice Rajneesh Kumar Gupta");
    }

    public function test_profile_banner_shows_what_is_missing_and_can_be_hidden(): void
    {
        $user = User::factory()->create(['account_type' => 'individual']);
        DB::table('advocate_profiles')->insert(['user_id' => $user->id, 'bar_council_registration_number' => 'UP/123/2020', 'court' => 'District Court Lucknow']);
        $this->actingAs($user);

        $response = $this->get(route('dashboard'))->assertOk()->assertSee('Add your photo');
        $this->assertSame(29, $response->viewData('profile')['percent']);

        $this->post(route('home.profile-banner.hide'))->assertRedirect(route('dashboard'));
        $this->assertNull($this->get(route('dashboard'))->viewData('profile'));
    }

    public function test_guests_see_the_public_home_page(): void
    {
        $this->get(route('dashboard'))->assertOk()->assertDontSee('Upcoming hearings');
    }
}
