<?php

namespace Tests\Feature;

use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PracticeStateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_independent_practitioner_without_a_firm_sees_the_state_list(): void
    {
        $this->actingAs($this->independentPractitioner())
            ->get(route('profile.practice-states.index'))
            ->assertOk()
            ->assertDontSee('Firm Profile required')
            ->assertSee('value="Uttar Pradesh"', false);
    }

    public function test_court_catalogue_coverage_is_shown_to_admins_only(): void
    {
        $user = $this->independentPractitioner();
        $admin = User::factory()->create(['is_admin' => true]);

        foreach ([$user, $admin] as $account) {
            DB::table('firm_practice_states')->insert([
                'user_id' => $account->id,
                'state_name' => 'Delhi',
                'is_active' => true,
                'district_selection_mode' => 'none',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->actingAs($user)->get(route('profile.practice-states.index'))
            ->assertOk()
            ->assertDontSee('Court Catalogue Coverage');

        $this->actingAs($admin)->get(route('profile.practice-states.index'))
            ->assertOk()
            ->assertSee('Court Catalogue Coverage');

        $this->actingAs($user)->get(route('court-management.index'))
            ->assertOk()
            ->assertDontSee('Courts in Catalogue');

        $this->actingAs($admin)->get(route('court-management.index'))
            ->assertOk()
            ->assertSee('Courts in Catalogue');
    }

    public function test_independent_practitioner_without_a_firm_can_save_practice_states(): void
    {
        State::create(['name' => 'Uttar Pradesh', 'code' => 'IN-UP']);
        $user = $this->independentPractitioner();

        $this->actingAs($user)
            ->post(route('profile.practice-states.store'), ['states' => ['Uttar Pradesh']])
            ->assertRedirect(route('profile.practice-states.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('firm_practice_states', [
            'user_id' => $user->id,
            'firm_profile_id' => null,
            'state_name' => 'Uttar Pradesh',
            'is_active' => true,
        ]);
    }

    public function test_does_not_show_practice_states_saved_by_another_user(): void
    {
        $otherUser = $this->independentPractitioner();
        DB::table('firm_practice_states')->insert([
            'user_id' => $otherUser->id,
            'state_name' => 'Kerala',
            'is_active' => true,
            'district_selection_mode' => 'none',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->independentPractitioner())
            ->get(route('profile.practice-states.index'))
            ->assertOk()
            ->assertViewHas('selectedStates', []);
    }

    public function test_sidebar_lists_only_the_selected_districts_of_district_practice_states(): void
    {
        $user = $this->independentPractitioner();
        $uttarPradesh = State::create(['name' => 'Uttar Pradesh', 'code' => 'IN-UP']);
        $kerala = State::create(['name' => 'Kerala', 'code' => 'IN-KL']);
        $lucknow = $this->insertDistrict($uttarPradesh->id, 'Lucknow');
        $this->insertDistrict($uttarPradesh->id, 'Agra');
        $this->insertDistrict($kerala->id, 'Ernakulam');

        $upPractice = $this->insertPracticeState($user->id, 'Uttar Pradesh', 'selected');
        $this->insertPracticeState($user->id, 'Kerala', 'none');
        DB::table('firm_practice_districts')->insert([
            'user_id' => $user->id,
            'firm_practice_state_id' => $upPractice,
            'judicial_district_id' => $lucknow,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('profile.practice-states.index'))
            ->assertOk()
            ->assertSee(route('court-directory.districts.show', $lucknow), false)
            ->assertDontSee('>Agra<', false)
            ->assertDontSee('Ernakulam');
    }

    private function insertDistrict(int $stateId, string $name): int
    {
        return DB::table('judicial_districts')->insertGetId([
            'state_id' => $stateId,
            'district_name' => $name,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertPracticeState(int $userId, string $stateName, string $mode): int
    {
        return DB::table('firm_practice_states')->insertGetId([
            'user_id' => $userId,
            'state_name' => $stateName,
            'is_active' => true,
            'district_selection_mode' => $mode,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function independentPractitioner(): User
    {
        return User::factory()->create(['account_type' => 'individual', 'account_role' => 'independent_practitioner']);
    }
}
