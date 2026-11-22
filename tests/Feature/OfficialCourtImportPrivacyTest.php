<?php

namespace Tests\Feature;

use App\Models\CourtRecordImport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfficialCourtImportPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_pasted_records_belong_to_the_person_who_pasted_them(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner)->post(route('cases.import.preview'), [
            'court_source' => 'Delhi High Court',
            'official_record' => "Case Title: Secret Client vs State\nCase Number: WP/1/2026",
        ])->assertOk();

        $import = CourtRecordImport::firstOrFail();
        $this->assertSame($owner->id, $import->user_id);

        $this->actingAs(User::factory()->create());

        $this->get(route('cases.import.history'))->assertOk()->assertDontSee('Secret Client');
        $this->post(route('cases.import.save', $import), ['case_title' => 'Stolen'])->assertNotFound();

        $this->actingAs($owner)->get(route('cases.import.history'))->assertOk()->assertSee('Secret Client');
    }

    public function test_older_records_without_an_owner_are_hidden_from_users(): void
    {
        CourtRecordImport::create(['source_name' => 'Delhi High Court', 'raw_content' => 'Case Title: Unowned vs State', 'parsed_data' => ['case_title' => 'Unowned vs State'], 'import_status' => 'preview']);

        $this->actingAs(User::factory()->create())
            ->get(route('cases.import.history'))
            ->assertOk()
            ->assertDontSee('Unowned vs State');
    }
}
