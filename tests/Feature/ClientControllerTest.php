<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_codes_count_up_per_user(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('clients.store'), $this->person('Asha'));
        $this->post(route('clients.store'), $this->person('Ravi'));

        $this->assertSame([1, 2], Client::orderBy('id')->pluck('client_code')->map(fn ($code) => (int) $code)->all());
    }

    public function test_two_users_can_each_add_their_first_client(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('clients.store'), $this->person('Asha'))->assertSessionHasNoErrors();

        $this->actingAs(User::factory()->create());
        $this->post(route('clients.store'), $this->person('Ravi'))->assertRedirect(route('clients.index'));

        $this->assertSame(2, Client::withoutGlobalScopes()->where('client_code', 1)->count());
    }

    public function test_a_company_needs_a_company_name_and_is_listed_by_it(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('clients.store'), ['client_type' => 'company', 'country' => 'India'])
            ->assertSessionHasErrors(['organisation_name' => 'Enter the company name.']);

        $this->post(route('clients.store'), ['client_type' => 'company', 'organisation_name' => 'Sharma Traders', 'website' => 'https://sharma.example', 'country' => 'India'])
            ->assertSessionHasNoErrors();

        $company = Client::firstOrFail();
        $this->assertSame('Sharma Traders', $company->displayName());
        $this->assertSame('Sharma Traders', $company->first_name);
    }

    public function test_a_person_can_be_linked_to_their_own_company(): void
    {
        $this->actingAs(User::factory()->create());
        $company = $this->company('Sharma Traders');

        $this->post(route('clients.store'), $this->person('Asha', ['company_id' => $company->id, 'locality' => 'Gomti Nagar']))->assertSessionHasNoErrors();

        $person = Client::where('first_name', 'Asha')->firstOrFail();
        $this->assertSame($company->id, $person->company_id);
        $this->assertSame('Gomti Nagar', $person->locality);
        $this->assertSame(['Asha'], $company->people->pluck('first_name')->all());

        $this->get(route('clients.show', $company))->assertOk()->assertSee('People at Sharma Traders')->assertSee('Asha');
        $this->get(route('clients.show', $person))->assertOk()->assertSee('Works at');
    }

    public function test_cannot_link_a_person_to_another_users_company(): void
    {
        $this->actingAs(User::factory()->create());
        $otherCompany = $this->company('Other Co');

        $this->actingAs(User::factory()->create());

        $this->post(route('clients.store'), $this->person('Asha', ['company_id' => $otherCompany->id]))
            ->assertSessionHasErrors(['company_id' => 'Choose one of your companies.']);
    }

    public function test_rejects_a_website_without_http(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('clients.store'), ['client_type' => 'company', 'organisation_name' => 'Co', 'website' => 'javascript:alert(1)', 'country' => 'India'])
            ->assertSessionHasErrors(['website' => 'Enter a website starting with http:// or https://.']);
    }

    public function test_list_tabs_filter_people_and_companies_with_counts(): void
    {
        $this->actingAs(User::factory()->create());
        $this->company('Sharma Traders');
        $this->post(route('clients.store'), $this->person('Asha'));

        $this->get(route('clients.index', ['type' => 'companies']))->assertOk()->assertSee('Sharma Traders')->assertDontSee('>Asha<', false);
        $response = $this->get(route('clients.index', ['type' => 'people']))->assertOk()->assertSee('Asha')->assertDontSee('Sharma Traders</a>', false);
        $this->assertSame(['all' => 2, 'people' => 1, 'companies' => 1], $response->viewData('counts'));
        $this->get(route('clients.index', ['view' => 'grid']))->assertOk()->assertSee('Company');
    }

    public function test_bulk_delete_removes_only_your_own_selected_clients(): void
    {
        $this->actingAs(User::factory()->create());
        $otherClient = $this->company('Other Co');

        $this->actingAs(User::factory()->create());
        $mine = $this->company('Mine Co');
        $keep = $this->company('Keep Co');

        $this->post(route('clients.bulk-destroy'), ['ids' => [$mine->id, $otherClient->id]])
            ->assertSessionHas('success', '1 client deleted.');

        $this->assertSoftDeleted($mine);
        $this->assertNotSoftDeleted($keep);
        $this->assertNotSoftDeleted($otherClient);
    }

    public function test_edit_form_renders_for_people_and_companies(): void
    {
        $this->actingAs(User::factory()->create());
        $company = $this->company('Sharma Traders');

        $this->get(route('clients.edit', $company))->assertOk()->assertSee('Company name')->assertSee('Save changes');
        $this->get(route('clients.create'))->assertOk()->assertSee('Works at');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function person(string $name, array $overrides = []): array
    {
        return array_merge(['client_type' => 'individual', 'first_name' => $name, 'country' => 'India'], $overrides);
    }

    private function company(string $name): Client
    {
        return Client::create([
            'client_code' => Client::nextCode(),
            'client_type' => 'company',
            'first_name' => $name,
            'organisation_name' => $name,
            'country' => 'India',
            'status' => 'active',
        ]);
    }
}
