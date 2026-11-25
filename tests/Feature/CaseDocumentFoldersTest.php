<?php

namespace Tests\Feature;

use App\Models\CaseDocument;
use App\Models\CaseDocumentFolder;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class CaseDocumentFoldersTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_a_folder_and_a_sub_folder(): void
    {
        $case = $this->actingAsOwnerOfCase();

        $this->post(route('cases.folders.store', $case), ['name' => 'Pleadings'])->assertSessionHasNoErrors();
        $parent = CaseDocumentFolder::where('name', 'Pleadings')->firstOrFail();

        $this->post(route('cases.folders.store', $case), ['name' => '2026', 'parent_id' => $parent->id])
            ->assertRedirect(route('cases.show', [$case, 'folder' => $parent->id]).'#case-documents');

        $this->assertDatabaseHas('case_document_folders', ['case_id' => $case->id, 'parent_id' => $parent->id, 'name' => '2026']);
    }

    public function test_rejects_folder_names_with_slashes(): void
    {
        $case = $this->actingAsOwnerOfCase();

        $this->post(route('cases.folders.store', $case), ['name' => '../secret'])
            ->assertSessionHasErrors(['name' => 'A folder name cannot contain / or \\.']);
    }

    public function test_uploads_several_files_into_the_current_folder(): void
    {
        Storage::fake('local');
        $case = $this->actingAsOwnerOfCase();
        $folder = $case->documentFolders()->create(['name' => 'Orders']);

        $this->post(route('cases.documents.store', $case), [
            'folder_id' => $folder->id,
            'files' => [$this->pdf('order-1.pdf'), $this->pdf('order-2.pdf')],
        ])->assertSessionHasNoErrors();

        $this->assertSame(2, CaseDocument::where('folder_id', $folder->id)->count());
        Storage::disk('local')->assertExists(CaseDocument::firstOrFail()->storage_path);
    }

    public function test_folder_upload_recreates_sub_folders_from_relative_paths(): void
    {
        Storage::fake('local');
        $case = $this->actingAsOwnerOfCase();

        $this->post(route('cases.documents.store', $case), [
            'files' => [$this->pdf('plaint.pdf'), $this->pdf('reply.pdf')],
            'paths' => ['Matter/Pleadings/plaint.pdf', 'Matter/Pleadings/Replies/reply.pdf'],
        ])->assertSessionHasNoErrors();

        $pleadings = CaseDocumentFolder::where('name', 'Pleadings')->firstOrFail();
        $replies = CaseDocumentFolder::where('name', 'Replies')->firstOrFail();

        $this->assertSame('Matter', $pleadings->parent->name);
        $this->assertSame($pleadings->id, $replies->parent_id);
        $this->assertDatabaseHas('case_documents', ['original_name' => 'plaint.pdf', 'folder_id' => $pleadings->id]);
        $this->assertDatabaseHas('case_documents', ['original_name' => 'reply.pdf', 'folder_id' => $replies->id]);
    }

    public function test_relative_paths_cannot_climb_out_with_dot_dot(): void
    {
        Storage::fake('local');
        $case = $this->actingAsOwnerOfCase();

        $this->post(route('cases.documents.store', $case), [
            'files' => [$this->pdf('a.pdf')],
            'paths' => ['../../etc/a.pdf'],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('case_document_folders', ['name' => '..']);
        $this->assertDatabaseHas('case_document_folders', ['name' => 'etc', 'parent_id' => null]);
    }

    public function test_rejects_unsupported_file_types_with_a_clear_message(): void
    {
        $case = $this->actingAsOwnerOfCase();

        $this->post(route('cases.documents.store', $case), [
            'files' => [UploadedFile::fake()->create('script.exe', 5, 'application/octet-stream')],
        ])->assertSessionHasErrors(['files.0' => 'Only PDF, Word, Excel, JPG and PNG files can be uploaded.']);
    }

    public function test_only_empty_folders_can_be_deleted(): void
    {
        Storage::fake('local');
        $case = $this->actingAsOwnerOfCase();
        $folder = $case->documentFolders()->create(['name' => 'Evidence']);
        $this->post(route('cases.documents.store', $case), ['folder_id' => $folder->id, 'files' => [$this->pdf('photo.pdf')]]);

        $this->delete(route('cases.folders.destroy', [$case, $folder]))->assertSessionHas('success', 'Only empty folders can be deleted.');
        $this->assertModelExists($folder);

        $this->delete(route('cases.documents.destroy', [$case, CaseDocument::firstOrFail()]));
        $this->delete(route('cases.folders.destroy', [$case, $folder]));
        $this->assertModelMissing($folder);
    }

    public function test_deleting_a_document_removes_the_stored_file(): void
    {
        Storage::fake('local');
        $case = $this->actingAsOwnerOfCase();
        $this->post(route('cases.documents.store', $case), ['files' => [$this->pdf('notice.pdf')]]);
        $document = CaseDocument::firstOrFail();

        $this->delete(route('cases.documents.destroy', [$case, $document]));

        Storage::disk('local')->assertMissing($document->storage_path);
        $this->assertModelMissing($document);
    }

    public function test_cannot_open_a_folder_that_belongs_to_another_case(): void
    {
        $case = $this->actingAsOwnerOfCase();
        $otherCase = LegalCase::create(['case_title' => 'Other', 'court_name' => 'Court', 'case_status' => 'pending']);
        $otherFolder = $otherCase->documentFolders()->create(['name' => 'Private']);

        $this->get(route('cases.show', [$case, 'folder' => $otherFolder->id]))->assertNotFound();
        $this->post(route('cases.documents.store', $case), ['folder_id' => $otherFolder->id, 'files' => [$this->pdf('x.pdf')]])->assertNotFound();
    }

    public function test_case_page_lists_the_folders_and_files_of_the_current_folder(): void
    {
        Storage::fake('local');
        $case = $this->actingAsOwnerOfCase();
        $folder = $case->documentFolders()->create(['name' => 'Orders']);
        $this->post(route('cases.documents.store', $case), ['files' => [$this->pdf('top-level.pdf')]]);
        $this->post(route('cases.documents.store', $case), ['folder_id' => $folder->id, 'files' => [$this->pdf('inside.pdf')]]);

        // The Documents tab shows one folder at a time (the Timeline lists every file by date).
        $documentsPanel = fn ($response): string => Str::of($response->assertOk()->getContent())->after('id="case-documents"')->before('id="case-expenses"')->toString();

        $topLevel = $documentsPanel($this->get(route('cases.show', $case)));
        $this->assertStringContainsString('Orders', $topLevel);
        $this->assertStringContainsString('top-level.pdf', $topLevel);
        $this->assertStringNotContainsString('inside.pdf', $topLevel);

        $inFolder = $documentsPanel($this->get(route('cases.show', [$case, 'folder' => $folder->id])));
        $this->assertStringContainsString('inside.pdf', $inFolder);
        $this->assertStringNotContainsString('top-level.pdf', $inFolder);
    }

    private function actingAsOwnerOfCase(): LegalCase
    {
        $this->actingAs(User::factory()->create());

        return LegalCase::create(['case_title' => 'Asha vs State', 'court_name' => 'District Court', 'case_status' => 'pending']);
    }

    private function pdf(string $name): UploadedFile
    {
        return UploadedFile::fake()->create($name, 20, 'application/pdf');
    }
}
