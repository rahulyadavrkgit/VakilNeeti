<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_finds_your_own_cases_and_clients_by_cnr_number_or_name(): void
    {
        $this->actingAs(User::factory()->create());
        LegalCase::create(['case_title' => 'Theirs vs State', 'cnr_number' => 'DLHC010000012026', 'court_name' => 'Delhi High Court', 'case_status' => 'pending']);

        $this->actingAs(User::factory()->create());
        LegalCase::create(['case_title' => 'Asha vs Ravi', 'case_number' => 'CS/12/2024', 'cnr_number' => 'DLHC010215262023', 'court_name' => 'Delhi High Court', 'case_status' => 'pending']);
        Client::create(['client_code' => 1, 'client_type' => 'individual', 'first_name' => 'Asha', 'mobile' => '9876543210', 'status' => 'active']);

        $this->get(route('search', ['q' => 'DLHC0102']))->assertOk()->assertSee('Asha vs Ravi')->assertDontSee('Theirs vs State');
        $this->get(route('search', ['q' => '98765']))->assertOk()->assertSee('Asha');
        $this->get(route('search', ['q' => 'Delhi High']))->assertOk()->assertSee('Asha vs Ravi')->assertDontSee('Theirs vs State');
    }

    public function test_short_queries_and_wildcards_do_not_list_everything(): void
    {
        $this->actingAs(User::factory()->create());
        LegalCase::create(['case_title' => 'Asha vs Ravi', 'court_name' => 'X', 'case_status' => 'pending']);

        $this->get(route('search', ['q' => 'a']))->assertOk()->assertSee('Type at least 2 letters')->assertDontSee('Asha vs Ravi');
        $this->get(route('search', ['q' => '%%']))->assertOk()->assertDontSee('Asha vs Ravi');
    }

    public function test_search_needs_a_login(): void
    {
        $this->get(route('search', ['q' => 'abc']))->assertRedirect(route('login'));
    }
}
