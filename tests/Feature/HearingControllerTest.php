<?php

namespace Tests\Feature;

use App\Models\CourtHearing;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HearingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-03-10 09:00:00');
    }

    public function test_a_future_hearing_becomes_the_cases_next_hearing(): void
    {
        $this->actingAs(User::factory()->create());
        $case = $this->makeCase();

        $this->get(route('hearings.create', ['case' => $case->id]))->assertOk()->assertSee('Asha vs State');

        $this->post(route('hearings.store'), ['case_id' => $case->id, 'hearing_date' => '2026-03-18', 'hearing_time' => '10:30', 'hearing_stage' => 'Arguments'])
            ->assertRedirect(route('cases.calendar', ['view' => 'day', 'date' => '2026-03-18']));

        $hearing = CourtHearing::firstOrFail();
        $this->assertSame('2026-03-18', $hearing->next_hearing_date->toDateString());
        $this->assertSame('manual', $hearing->source_type);
        $this->get(route('cases.show', $case))->assertSee('18 Mar 2026');
    }

    public function test_a_past_hearing_keeps_its_own_next_date_and_can_return_to_the_case(): void
    {
        $this->actingAs(User::factory()->create());
        $case = $this->makeCase();

        $this->post(route('hearings.store'), [
            'case_id' => $case->id,
            'hearing_date' => '2026-03-02',
            'proceedings' => 'Reply filed',
            'next_hearing_date' => '2026-04-06',
            'return' => 'case',
        ])->assertRedirect(route('cases.show', $case));

        $hearing = CourtHearing::firstOrFail();
        $this->assertSame('2026-03-02', $hearing->hearing_date->toDateString());
        $this->assertSame('2026-04-06', $hearing->next_hearing_date->toDateString());
    }

    public function test_a_past_hearing_without_a_next_date_has_no_next_hearing(): void
    {
        $this->actingAs(User::factory()->create());
        $case = $this->makeCase();

        $this->post(route('hearings.store'), ['case_id' => $case->id, 'hearing_date' => '2026-03-02'])->assertSessionHasNoErrors();

        $this->assertNull(CourtHearing::firstOrFail()->next_hearing_date);
    }

    public function test_rejects_a_next_date_before_the_hearing(): void
    {
        $this->actingAs(User::factory()->create());
        $case = $this->makeCase();

        $this->post(route('hearings.store'), ['case_id' => $case->id, 'hearing_date' => '2026-03-18', 'next_hearing_date' => '2026-03-01'])
            ->assertSessionHasErrors(['next_hearing_date' => 'The next date cannot be before the hearing date.']);

        $this->assertDatabaseCount('court_hearings', 0);
    }

    public function test_cannot_add_a_hearing_to_another_users_case(): void
    {
        $this->actingAs(User::factory()->create());
        $otherCase = $this->makeCase();

        $this->actingAs(User::factory()->create());

        $this->post(route('hearings.store'), ['case_id' => $otherCase->id, 'hearing_date' => '2026-03-18'])
            ->assertSessionHasErrors(['case_id' => 'Choose one of your cases.']);

        $this->get(route('hearings.create'))->assertOk()->assertDontSee('Asha vs State')->assertSee('Add a case first');
    }

    public function test_editing_a_hearing_moves_its_next_date_with_it(): void
    {
        $this->actingAs(User::factory()->create());
        $case = $this->makeCase();
        $this->post(route('hearings.store'), ['case_id' => $case->id, 'hearing_date' => '2026-03-18']);
        $hearing = CourtHearing::firstOrFail();

        $this->get(route('cases.show', $case))->assertSee(route('hearings.edit', $hearing), false);
        $this->get(route('hearings.edit', $hearing))->assertOk()->assertSee('Save changes')->assertSee('Delete hearing');

        $this->put(route('hearings.update', $hearing), ['case_id' => $case->id, 'hearing_date' => '2026-03-25', 'hearing_stage' => 'Evidence', 'return' => 'case'])
            ->assertRedirect(route('cases.show', $case));

        $hearing->refresh();
        $this->assertSame('2026-03-25', $hearing->next_hearing_date->toDateString());
        $this->assertSame('Evidence', $hearing->hearing_stage);
    }

    public function test_a_hearing_you_added_can_be_deleted(): void
    {
        $this->actingAs(User::factory()->create());
        $case = $this->makeCase();
        $hearing = CourtHearing::create(['case_id' => $case->id, 'hearing_date' => '2026-03-18', 'source_type' => 'manual']);

        $this->delete(route('hearings.destroy', $hearing))->assertRedirect(route('cases.show', $case));

        $this->assertModelMissing($hearing);
    }

    public function test_imported_court_records_cannot_be_changed(): void
    {
        $this->actingAs(User::factory()->create());
        $case = $this->makeCase();
        $imported = CourtHearing::create(['case_id' => $case->id, 'hearing_date' => '2026-03-18', 'source_type' => 'official_portal_assisted_import']);

        $this->get(route('hearings.edit', $imported))->assertForbidden();
        $this->put(route('hearings.update', $imported), ['case_id' => $case->id, 'hearing_date' => '2026-03-25'])->assertForbidden();
        $this->delete(route('hearings.destroy', $imported))->assertForbidden();
        $this->get(route('cases.show', $case))->assertSee('Imported')->assertDontSee(route('hearings.edit', $imported), false);
    }

    public function test_cannot_edit_or_delete_another_users_hearing(): void
    {
        $this->actingAs(User::factory()->create());
        $hearing = CourtHearing::create(['case_id' => $this->makeCase()->id, 'hearing_date' => '2026-03-18', 'source_type' => 'manual']);

        $this->actingAs(User::factory()->create());

        $this->get(route('hearings.edit', $hearing))->assertNotFound();
        $this->delete(route('hearings.destroy', $hearing))->assertNotFound();
        $this->assertModelExists($hearing);
    }

    public function test_guests_are_sent_to_log_in(): void
    {
        $this->get(route('hearings.create'))->assertRedirect(route('login'));
        $this->post(route('hearings.store'), [])->assertRedirect(route('login'));
    }

    private function makeCase(): LegalCase
    {
        return LegalCase::create(['case_title' => 'Asha vs State', 'court_name' => 'District Court Lucknow', 'case_status' => 'pending']);
    }
}
