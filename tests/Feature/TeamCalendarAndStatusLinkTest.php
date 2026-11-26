<?php

namespace Tests\Feature;

use App\Models\CourtHearing;
use App\Models\LegalCase;
use App\Models\TeamInvite;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamCalendarAndStatusLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-03-10 09:00:00');
    }

    /**
     * Invite by email or by mobile number, then let that person sign in for the first time.
     */
    private function joinTeam(User $owner, string $contact, string $role): User
    {
        $this->actingAs($owner)->post(route('profile.team.invite'), ['contact' => $contact, 'role' => $role])->assertSessionHasNoErrors();
        $member = User::factory()->create(str_contains($contact, '@') ? ['email' => strtolower($contact)] : ['mobile' => '+91'.$contact]);
        event(new Login('web', $member, false));

        return $member->fresh();
    }

    public function test_an_invited_junior_works_in_the_owners_cases_but_not_billing(): void
    {
        $owner = User::factory()->create(['mobile' => '+919811111111']);
        $this->actingAs($owner);
        $case = LegalCase::create(['case_title' => 'Owner vs State', 'court_name' => 'Saket', 'case_status' => 'pending']);

        $junior = $this->joinTeam($owner, 'Junior@Example.test', 'junior');

        $this->assertSame($owner->id, $junior->team_owner_id);
        $this->assertSame('junior@example.test', TeamInvite::first()->email);
        $this->assertSame($owner->id, $junior->workspaceId());
        $this->assertNotNull(TeamInvite::first()->accepted_at);

        $this->actingAs($junior)->get(route('cases.index'))->assertOk()->assertSee('Owner vs State')->assertDontSee('>Billing<', false);
        $this->get(route('cases.show', $case))->assertOk();

        // What the junior adds belongs to the owner's workspace.
        $this->post(route('cases.store'), ['case_title' => 'Junior adds', 'court_name' => 'Saket', 'case_status' => 'pending'])->assertRedirect();
        $this->assertSame($owner->id, LegalCase::withoutGlobalScopes()->where('case_title', 'Junior adds')->value('user_id'));

        $this->get(route('billing.index'))->assertForbidden();
        $this->get(route('profile.invoice-settings.index'))->assertForbidden();
    }

    public function test_a_clerk_only_adds_dates_and_tasks(): void
    {
        $owner = User::factory()->create(['mobile' => '+919811111111']);
        $this->actingAs($owner);
        $case = LegalCase::create(['case_title' => 'Owner vs State', 'court_name' => 'Saket', 'case_status' => 'pending']);

        $clerk = $this->joinTeam($owner, '9833333333', 'clerk');
        $this->actingAs($clerk);

        $this->get(route('cases.show', $case))->assertOk();
        $this->post(route('hearings.store'), ['case_id' => $case->id, 'hearing_date' => '2026-03-20', 'return' => 'back'])->assertRedirect();
        $this->assertSame(1, CourtHearing::withoutGlobalScopes()->where('case_id', $case->id)->count());

        $this->post(route('cases.store'), ['case_title' => 'Clerk adds', 'court_name' => 'Saket', 'case_status' => 'pending'])->assertForbidden();
        $this->delete(route('cases.destroy', $case))->assertForbidden();
    }

    public function test_the_owner_can_remove_a_member_and_a_member_can_leave(): void
    {
        $owner = User::factory()->create(['mobile' => '+919811111111']);
        $junior = $this->joinTeam($owner, '9822222222', 'junior');

        $this->actingAs($owner)->delete(route('profile.team.remove', $junior))->assertRedirect();
        $this->assertNull($junior->fresh()->team_owner_id);

        $clerk = $this->joinTeam($owner, '9833333333', 'clerk');
        $this->actingAs($clerk)->post(route('profile.team.leave'))->assertRedirect(route('dashboard'));
        $this->assertNull($clerk->fresh()->team_owner_id);

        // Your own email cannot be invited, and something that is neither an email nor a number is refused.
        $this->actingAs($owner)->post(route('profile.team.invite'), ['contact' => $owner->email, 'role' => 'junior'])->assertSessionHasErrors('contact', null, 'team');
        $this->post(route('profile.team.invite'), ['contact' => 'asha', 'role' => 'junior'])->assertSessionHasErrors('contact', null, 'team');

        // Another advocate cannot remove someone else's member.
        $member = $this->joinTeam($owner, '9844444444', 'junior');
        $this->actingAs(User::factory()->create())->delete(route('profile.team.remove', $member))->assertNotFound();
    }

    public function test_calendar_sync_link_lists_hearings_and_can_be_replaced(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $case = LegalCase::create(['case_title' => 'Asha vs State', 'case_number' => 'CS/12/2026', 'court_name' => 'Delhi High Court', 'court_room' => '33', 'case_status' => 'pending']);
        CourtHearing::create(['case_id' => $case->id, 'hearing_date' => '2026-03-11', 'hearing_time' => '10:30:00', 'hearing_stage' => 'Arguments']);
        $this->actingAs(User::factory()->create());
        $other = LegalCase::create(['case_title' => 'Someone else', 'court_name' => 'Saket', 'case_status' => 'pending']);
        CourtHearing::create(['case_id' => $other->id, 'hearing_date' => '2026-03-11']);

        $this->actingAs($user)->post(route('profile.calendar-link'))->assertRedirect();
        $token = $user->fresh()->calendar_token;
        $this->assertSame(40, strlen($token));
        auth()->logout();

        $ics = $this->get(route('calendar.feed', $token))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/calendar; charset=utf-8')
            ->getContent();

        $this->assertStringContainsString('BEGIN:VCALENDAR', $ics);
        $this->assertStringContainsString('SUMMARY:Asha vs State', $ics);
        $this->assertStringContainsString('DTSTART;TZID=Asia/Kolkata:20260311T103000', $ics);
        $this->assertStringContainsString('LOCATION:Delhi High Court\, Room 33', $ics);
        $this->assertStringNotContainsString('Someone else', $ics);

        $this->actingAs($user)->post(route('profile.calendar-link'));
        $this->get(route('calendar.feed', $token))->assertNotFound();
    }

    public function test_client_status_link_shows_only_the_next_date_and_can_be_switched_off(): void
    {
        $user = User::factory()->create(['name' => 'Adv. Meera Rao']);
        $this->actingAs($user);
        $case = LegalCase::create(['case_title' => 'Asha vs State', 'case_number' => 'CS/12/2026', 'court_name' => 'Saket Courts', 'court_room' => '214', 'case_status' => 'pending', 'case_description' => 'Private strategy note']);
        CourtHearing::create(['case_id' => $case->id, 'hearing_date' => '2026-03-01', 'hearing_stage' => 'Evidence']);
        CourtHearing::create(['case_id' => $case->id, 'hearing_date' => '2026-03-11', 'hearing_time' => '10:30:00', 'hearing_stage' => 'Arguments']);

        $this->post(route('cases.status-link.create', $case))->assertRedirect();
        $token = $case->fresh()->status_token;
        $this->get(route('cases.show', $case))->assertSee(route('case-status.show', $token), false);
        auth()->logout();

        $this->get(route('case-status.show', $token))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertSeeInOrder(['Asha vs State', 'Next date', 'Tomorrow', '10:30', 'Arguments', 'Room 214', 'Last hearing', '01 Mar 2026', 'Adv. Meera Rao'])
            ->assertDontSee('Private strategy note');

        $this->actingAs($user)->delete(route('cases.status-link.destroy', $case))->assertRedirect();
        auth()->logout();
        $this->get(route('case-status.show', $token))->assertNotFound();
    }

    public function test_client_link_expires_after_seven_days_and_replacement_has_a_new_url(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $case = LegalCase::create(['case_title' => 'Asha vs State', 'court_name' => 'Saket Courts', 'case_status' => 'pending']);

        $this->post(route('cases.status-link.create', $case))->assertRedirect();
        $case->refresh();
        $token = $case->status_token;
        $this->assertTrue($case->status_link_expires_at->equalTo(now()->addWeek()));
        $statusUrl = route('case-status.show', $token);

        $this->travel(6)->days();
        $this->post(route('cases.status-link.create', $case))->assertRedirect();
        $this->assertSame($token, $case->fresh()->status_token);
        $this->get($statusUrl)->assertOk();

        $this->travel(1)->days();
        auth()->logout();
        $this->get($statusUrl)->assertNotFound();

        $this->actingAs($user)->get(route('cases.show', $case))
            ->assertSee('Link expired.')->assertDontSee($statusUrl, false);
        $this->post(route('cases.status-link.create', $case))->assertRedirect();
        $newToken = $case->fresh()->status_token;
        $this->assertNotSame($token, $newToken);
        auth()->logout();
        $this->get(route('case-status.show', $newToken))->assertOk();
        $this->get($statusUrl)->assertNotFound();
    }
}
