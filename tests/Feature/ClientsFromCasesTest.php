<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientsFromCasesTest extends TestCase
{
    use RefreshDatabase;

    public function test_case_title_splits_into_the_two_sides(): void
    {
        $case = new LegalCase(['case_title' => 'Prahlad Yadav vs Sanjay Kumar Agarwal & Ors.']);
        $this->assertSame(['moving' => 'Prahlad Yadav', 'answering' => 'Sanjay Kumar Agarwal'], $case->titleSides());

        $this->assertSame(['moving' => 'FIROZ', 'answering' => 'STATE N.C.T. OF DELHI'], (new LegalCase(['case_title' => 'FIROZ v. STATE N.C.T. OF DELHI']))->titleSides());
        $this->assertSame(['moving' => null, 'answering' => null], (new LegalCase(['case_title' => 'In re: Estate of A']))->titleSides());
    }

    public function test_our_side_is_ticked_and_unknown_sides_offer_both_names(): void
    {
        $this->actingAs(User::factory()->create());
        LegalCase::create(['case_title' => 'Prahlad Yadav vs Sanjay Kumar', 'court_name' => 'Saket', 'appearing_for' => 'respondent', 'case_status' => 'pending']);
        LegalCase::create(['case_title' => 'Meera vs Union of India', 'court_name' => 'Delhi HC', 'case_status' => 'pending']);

        $html = $this->get(route('clients.from-cases'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/value="\d+\|Sanjay Kumar"\s+checked/', $html);
        $this->assertDoesNotMatchRegularExpression('/value="\d+\|Prahlad Yadav"\s+checked/', $html);
        $this->assertStringContainsString('|Meera"', $html);
        $this->assertStringContainsString('|Union of India"', $html);
        $this->assertStringContainsString('Set our side', $html);
    }

    public function test_ticked_names_become_clients_linked_to_their_cases_without_duplicates(): void
    {
        $this->actingAs(User::factory()->create());
        $first = LegalCase::create(['case_title' => 'Sanjay Kumar vs State', 'court_name' => 'Saket', 'appearing_for' => 'petitioner', 'case_status' => 'pending']);
        $second = LegalCase::create(['case_title' => 'State vs Sanjay Kumar', 'court_name' => 'Saket', 'appearing_for' => 'accused', 'case_status' => 'pending']);

        $this->post(route('clients.from-cases.store'), ['pick' => [$first->id.'|Sanjay Kumar', $second->id.'|Sanjay Kumar']])
            ->assertRedirect(route('clients.index'))
            ->assertSessionHas('success', fn (string $message) => str_contains($message, '1 new client added, 2 case links made'));

        $this->assertSame(1, Client::count());
        $client = Client::first();
        $this->assertTrue($first->parties()->where('client_id', $client->id)->where('is_client', true)->exists());
        $this->assertTrue($second->parties()->where('client_id', $client->id)->where('is_client', true)->exists());

        // Linked cases are no longer offered.
        $this->get(route('clients.from-cases'))->assertOk()->assertDontSee('Sanjay Kumar vs State');
        $this->get(route('clients.index'))->assertOk()->assertSee('href="'.route('clients.from-cases').'"', false);
    }
}
