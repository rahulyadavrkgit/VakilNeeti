<?php

namespace Tests\Feature;

use App\Models\CaseTag;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalCaseControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_adding_a_case_manually_saves_the_new_practice_fields(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->post(route('cases.store'), $this->casePayload());

        $case = LegalCase::firstOrFail();
        $response->assertRedirect(route('cases.show', $case));
        $this->assertSame('F-101', $case->file_number);
        $this->assertSame('Evidence', $case->stage);
        $this->assertSame('A. Mehta', $case->petitioner_advocate);
        $this->assertSame('manual', $case->source_type);
    }

    public function test_rejects_a_status_outside_the_list(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('cases.store'), $this->casePayload(['case_status' => 'sleeping']))
            ->assertSessionHasErrors('case_status');
    }

    public function test_edit_page_renders_and_update_saves_changes(): void
    {
        $this->actingAs(User::factory()->create());
        $case = $this->makeCase();

        $this->get(route('cases.edit', $case))->assertOk()->assertSee('Save changes');

        $this->put(route('cases.update', $case), $this->casePayload(['stage' => 'Final arguments']))
            ->assertRedirect(route('cases.show', $case));

        $this->assertSame('Final arguments', $case->fresh()->stage);
    }

    public function test_close_and_reopen_change_the_status(): void
    {
        $this->actingAs(User::factory()->create());
        $case = $this->makeCase();

        $this->post(route('cases.close', $case));
        $this->assertNotNull($case->fresh()->closed_at);
        $this->assertSame('closed', $case->fresh()->case_status);

        $this->post(route('cases.reopen', $case));
        $this->assertNull($case->fresh()->closed_at);
        $this->assertSame('pending', $case->fresh()->case_status);
    }

    public function test_file_number_can_be_saved_from_the_case_page(): void
    {
        $this->actingAs(User::factory()->create());
        $case = $this->makeCase();

        $this->patch(route('cases.file-number', $case), ['file_number' => 'OFF/77']);

        $this->assertSame('OFF/77', $case->fresh()->file_number);
    }

    public function test_tags_can_be_added_reused_and_removed(): void
    {
        $this->actingAs(User::factory()->create());
        $first = $this->makeCase();
        $second = $this->makeCase(['case_title' => 'Second case']);

        $this->post(route('cases.tags.attach', $first), ['name' => 'Urgent']);
        $this->post(route('cases.tags.attach', $second), ['name' => 'Urgent']);

        $this->assertDatabaseCount('case_tags', 1);
        $tag = CaseTag::firstOrFail();
        $this->assertCount(2, $tag->cases);

        $this->delete(route('cases.tags.detach', [$first, $tag]));
        $this->assertCount(0, $first->fresh()->tags);
    }

    public function test_list_filters_by_tag_and_finds_cases_by_file_number(): void
    {
        $this->actingAs(User::factory()->create());
        $tagged = $this->makeCase(['case_title' => 'Tagged matter', 'file_number' => 'F-555']);
        $this->makeCase(['case_title' => 'Untagged matter']);
        $this->post(route('cases.tags.attach', $tagged), ['name' => 'Bail']);

        $this->get(route('cases.index', ['tag' => CaseTag::firstOrFail()->id]))
            ->assertOk()
            ->assertSee('Tagged matter')
            ->assertDontSee('Untagged matter');

        $this->get(route('cases.index', ['search' => 'F-555']))
            ->assertOk()
            ->assertSee('Tagged matter')
            ->assertDontSee('Untagged matter');
    }

    public function test_export_downloads_the_filtered_cases_as_csv(): void
    {
        $this->actingAs(User::factory()->create());
        $this->makeCase(['case_title' => 'Pending matter', 'case_status' => 'pending']);
        $this->makeCase(['case_title' => 'Disposed matter', 'case_status' => 'disposed']);

        $response = $this->get(route('cases.export', ['case_status' => 'pending']))->assertOk();
        $csv = $response->streamedContent();

        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Case title,"Case number","File No."', str_replace(['"Case title"'], ['Case title'], $csv));
        $this->assertStringContainsString('Pending matter', $csv);
        $this->assertStringNotContainsString('Disposed matter', $csv);
    }

    public function test_case_page_offers_whatsapp_sharing_with_the_case_summary(): void
    {
        $this->actingAs(User::factory()->create());
        $case = $this->makeCase(['case_title' => 'Sharma vs State', 'case_number' => 'HMA/1/2026']);

        $this->get(route('cases.show', $case))
            ->assertOk()
            ->assertSee('https://wa.me/?text='.rawurlencode("*Sharma vs State*\nCase no: HMA/1/2026"), false)
            ->assertSee('data-direct-share', false);
    }

    public function test_delete_removes_the_case_from_the_list(): void
    {
        $this->actingAs(User::factory()->create());
        $case = $this->makeCase();

        $this->delete(route('cases.destroy', $case))->assertRedirect(route('cases.index'));

        $this->assertSoftDeleted('legal_cases', ['id' => $case->id]);
    }

    public function test_cannot_open_or_delete_another_users_case(): void
    {
        $this->actingAs(User::factory()->create());
        $case = $this->makeCase();

        $this->actingAs(User::factory()->create());
        $this->get(route('cases.show', $case))->assertNotFound();
        $this->delete(route('cases.destroy', $case))->assertNotFound();

        $this->assertNotSoftDeleted('legal_cases', ['id' => $case->id]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeCase(array $overrides = []): LegalCase
    {
        return LegalCase::create(array_merge([
            'case_title' => 'Asha vs State',
            'court_name' => 'District Court Lucknow',
            'case_status' => 'pending',
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function casePayload(array $overrides = []): array
    {
        return array_merge([
            'case_title' => 'Asha vs State',
            'court_name' => 'District Court Lucknow',
            'case_status' => 'pending',
            'file_number' => 'F-101',
            'stage' => 'Evidence',
            'petitioner_advocate' => 'A. Mehta',
        ], $overrides);
    }
}
