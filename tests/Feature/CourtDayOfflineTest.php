<?php

namespace Tests\Feature;

use App\Models\CaseDocument;
use App\Models\CaseParty;
use App\Models\Client;
use App\Models\CourtHearing;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CourtDayOfflineTest extends TestCase
{
    use RefreshDatabase;

    public function test_court_day_lists_today_and_tomorrow_with_room_vc_and_client_phone(): void
    {
        $this->travelTo('2026-03-10 08:00:00');
        $user = User::factory()->create();
        $this->actingAs($user);

        $today = LegalCase::create(['case_title' => 'Asha vs State', 'case_number' => 'CS/12/2026', 'court_name' => 'Saket Courts', 'court_room' => '214', 'case_status' => 'pending']);
        $tomorrow = LegalCase::create(['case_title' => 'Ravi vs Union', 'case_number' => 'W.P.(C)/5/2026', 'court_name' => 'High Court of Delhi', 'case_status' => 'pending', 'vc_link' => 'https://example.webex.com/meet/court9']);
        $later = LegalCase::create(['case_title' => 'Next week matter', 'court_name' => 'Tis Hazari', 'case_status' => 'pending']);
        CourtHearing::create(['case_id' => $today->id, 'hearing_date' => '2026-03-10', 'hearing_time' => '10:30:00', 'hearing_stage' => 'Arguments']);
        CourtHearing::create(['case_id' => $tomorrow->id, 'hearing_date' => '2026-03-11']);
        CourtHearing::create(['case_id' => $later->id, 'hearing_date' => '2026-03-17']);
        $client = Client::create(['client_type' => 'individual', 'first_name' => 'Asha', 'display_name' => 'Asha Sharma', 'mobile' => '+91 98100 12345', 'country' => 'India', 'status' => 'active']);
        CaseParty::create(['case_id' => $today->id, 'client_id' => $client->id, 'party_name' => 'Asha Sharma', 'is_client' => true]);

        Storage::fake('local');
        Storage::disk('local')->put('case-documents/order.pdf', '%PDF-1.4');
        $order = CaseDocument::create(['case_id' => $today->id, 'original_name' => 'Court order.pdf',
            'storage_path' => 'case-documents/order.pdf', 'mime_type' => 'application/pdf', 'size' => 8]);

        $response = $this->get(route('court-day'))->assertOk()
            ->assertSee('Saved by you')->assertSee('Open order')->assertSee('Add next date')->assertSee('Report a problem')
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertSeeInOrder(['Today', 'Asha vs State', 'Room', '214', 'Purpose:', 'Arguments', 'Tomorrow', 'Ravi vs Union', 'Join VC'])
            ->assertSee('href="tel:+919810012345"', false)
            ->assertSee('data-offline-note', false)
            ->assertDontSee('Next week matter');

        $response->assertSee('data-signed-in="1"', false);
        $this->get(route('cases.documents.download', [$today, $order, 'preview' => 1]))->assertOk()
            ->assertHeader('Content-Disposition', 'inline; filename="Court order.pdf"');

        $this->actingAs(User::factory()->create())->get(route('court-day'))->assertOk()->assertDontSee('Asha vs State');
    }

    public function test_court_day_needs_sign_in_and_the_offline_worker_keeps_no_private_pages(): void
    {
        $this->get(route('court-day'))->assertRedirect();

        $worker = file_get_contents(public_path('sw.js'));
        $this->assertStringNotContainsString('vn-pages', $worker);
        $this->assertStringContainsString("request.method !== 'GET'", $worker);
        $this->assertStringNotContainsString('cache.put(url.pathname', $worker);

        $this->get(route('legal.privacy'))->assertSee('data-signed-in="0"', false);
    }
}
