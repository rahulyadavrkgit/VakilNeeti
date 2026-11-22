<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\CourtHearing;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CaseClientControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_links_an_existing_client_once(): void
    {
        $case = $this->actingAsOwnerOfCase();
        $client = $this->makeClient('Asha Sharma', '9876543210');

        $this->post(route('cases.clients.store', $case), ['client_id' => $client->id])->assertSessionHas('success', 'Client linked to the case.');
        $this->post(route('cases.clients.store', $case), ['client_id' => $client->id])->assertSessionHas('success', 'This client is already linked to the case.');

        $this->assertSame(1, $case->parties()->where('client_id', $client->id)->where('is_client', true)->count());
    }

    public function test_adds_a_new_contact_and_links_it(): void
    {
        $case = $this->actingAsOwnerOfCase();

        $this->post(route('cases.clients.store', $case), ['name' => 'Ravi Kumar', 'mobile' => '98765 43210'])->assertSessionHasNoErrors();

        $client = Client::where('display_name', 'Ravi Kumar')->firstOrFail();
        $this->assertSame('98765 43210', $client->mobile);
        $this->assertDatabaseHas('case_parties', ['case_id' => $case->id, 'client_id' => $client->id, 'is_client' => true]);
    }

    public function test_new_contact_needs_a_mobile_or_an_email(): void
    {
        $case = $this->actingAsOwnerOfCase();

        $this->post(route('cases.clients.store', $case), ['name' => 'No Contact'])
            ->assertSessionHasErrors(['mobile' => 'Enter a mobile number or an email address.']);

        $this->assertDatabaseMissing('clients', ['display_name' => 'No Contact']);
    }

    public function test_cannot_link_another_users_client(): void
    {
        $this->actingAs(User::factory()->create());
        $otherClient = $this->makeClient('Someone Else', '9000000000');

        $case = $this->actingAsOwnerOfCase();

        $this->post(route('cases.clients.store', $case), ['client_id' => $otherClient->id])->assertNotFound();
    }

    public function test_removing_a_notify_only_contact_deletes_the_link(): void
    {
        $case = $this->actingAsOwnerOfCase();
        $this->post(route('cases.clients.store', $case), ['name' => 'Ravi Kumar', 'email' => 'ravi@example.test']);
        $party = $case->parties()->firstOrFail();

        $this->delete(route('cases.clients.destroy', [$case, $party]))->assertSessionHas('success', 'Client removed from this case.');

        $this->assertModelMissing($party);
    }

    public function test_case_page_shows_whatsapp_and_email_buttons_with_the_update_message(): void
    {
        $case = $this->actingAsOwnerOfCase();
        CourtHearing::create(['case_id' => $case->id, 'next_hearing_date' => now()->addDays(3)->toDateString()]);
        $client = $this->makeClient('Asha Sharma', '9876543210', 'asha@example.test');
        $this->post(route('cases.clients.store', $case), ['client_id' => $client->id]);

        $expectedMessage = $case->fresh()->load('hearings')->clientUpdateMessage('Asha Sharma', auth()->user()->name);

        $this->get(route('cases.show', $case))
            ->assertOk()
            ->assertSee('https://wa.me/919876543210?text='.rawurlencode($expectedMessage), false)
            ->assertSee('mailto:asha@example.test', false);

        $this->assertStringContainsString('Next hearing: '.now()->addDays(3)->format('d M Y'), $expectedMessage);
    }

    /**
     * @return array<string, array{string|null, string|null}>
     */
    public static function mobileNumbers(): array
    {
        return [
            'ten digits are Indian' => ['98765 43210', '919876543210'],
            'leading zero is dropped' => ['09876543210', '919876543210'],
            'international number kept' => ['+44 20 7946 0958', '442079460958'],
            'too short is unusable' => ['12345', null],
            'missing number' => [null, null],
        ];
    }

    #[DataProvider('mobileNumbers')]
    public function test_whatsapp_number_formats_mobile_numbers(?string $mobile, ?string $expected): void
    {
        $this->assertSame($expected, (new Client(['mobile' => $mobile]))->whatsappNumber());
    }

    private function actingAsOwnerOfCase(): LegalCase
    {
        $this->actingAs(User::factory()->create());

        return LegalCase::create(['case_title' => 'Asha vs State', 'case_number' => 'HMA/1/2026', 'court_name' => 'District Court', 'case_status' => 'pending']);
    }

    private function makeClient(string $name, ?string $mobile, ?string $email = null): Client
    {
        return Client::create([
            'client_type' => 'individual',
            'first_name' => $name,
            'display_name' => $name,
            'mobile' => $mobile,
            'email' => $email,
            'country' => 'India',
            'status' => 'active',
        ]);
    }
}
