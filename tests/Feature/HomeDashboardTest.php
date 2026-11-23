<?php

namespace Tests\Feature;

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
            ->assertSee('Upcoming hearings')
            ->assertSeeInOrder(['Asha vs State', 'Ravi vs Union'])
            ->assertDontSee('Too far ahead')
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
            ->assertSeeInOrder(['Prahlad vs Sanjay', 'Petitioner', 'Saket Courts', 'Room 304, Main court building', 'Ms. Niti Phutela'])
            ->assertSeeInOrder(['Room 505', 'Sh. Dheeraj Mor']);
    }

    public function test_next_hearing_card_skips_hearings_already_started_today_and_offers_join_vc(): void
    {
        $this->actingAs(User::factory()->create());
        $started = LegalCase::create(['case_title' => 'Started vs Earlier', 'court_name' => 'Saket Courts', 'case_status' => 'pending']);
        $next = LegalCase::create([
            'case_title' => 'Meera vs Union',
            'court_name' => 'Delhi High Court',
            'court_room' => '37',
            'judge_name' => 'Justice Dinesh Mehta',
            'appearing_for' => 'appellant',
            'vc_link' => 'https://dhc.webex.com/meet/court37',
            'case_status' => 'pending',
        ]);
        CourtHearing::create(['case_id' => $started->id, 'hearing_date' => '2026-03-10', 'hearing_time' => '08:00']);
        CourtHearing::create(['case_id' => $next->id, 'hearing_date' => '2026-03-11', 'hearing_time' => '10:30']);

        $html = $this->get(route('dashboard'))->assertOk()->getContent();
        $card = substr($html, (int) strpos($html, 'id="next-hearing-heading"'), 6000);

        $this->assertStringContainsString('Next hearing · Tomorrow · 10:30', $card);
        foreach (['Meera vs Union', 'Appellant', 'Room 37', 'Justice Dinesh Mehta', 'href="https://dhc.webex.com/meet/court37"'] as $detail) {
            $this->assertStringContainsString($detail, $card);
        }
        $this->assertStringNotContainsString('Started vs Earlier', $card);

        $this->get(route('dashboard', ['hearing_search' => 'Meera']))->assertOk()->assertDontSee('id="next-hearing-heading"', false);
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
