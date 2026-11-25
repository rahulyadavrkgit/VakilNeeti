<?php

namespace App\Http\Controllers;

use App\Models\CaseDocument;
use App\Models\CaseDocumentFolder;
use App\Models\LegalCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CaseWorkspaceController extends Controller
{
    public function storeNote(Request $request, LegalCase $case)
    {
        $validated = $request->validate([
            'note_title' => ['nullable', 'string', 'max:160'],
            'note_body' => ['required', 'string', 'max:10000'],
        ]);

        $case->notes()->create([
            'title' => $validated['note_title'] ?? null,
            'body' => $validated['note_body'],
        ]);

        return $this->backToCase($case, 'Case note added.', 'case-notes');
    }

    public function storeAppointment(Request $request, LegalCase $case)
    {
        $validated = $request->validate([
            'appointment_title' => ['required', 'string', 'max:160'],
            'appointment_starts_at' => ['required', 'date'],
            'appointment_location' => ['nullable', 'string', 'max:255'],
            'appointment_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $case->appointments()->create([
            'title' => $validated['appointment_title'],
            'starts_at' => $validated['appointment_starts_at'],
            'location' => $validated['appointment_location'] ?? null,
            'notes' => $validated['appointment_notes'] ?? null,
        ]);

        return $this->backToCase($case, 'Appointment added.', 'case-appointments');
    }

    public function storeExpense(Request $request, LegalCase $case)
    {
        $validated = $request->validate([
            'expense_description' => ['required', 'string', 'max:255'],
            'expense_amount' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'expense_date' => ['required', 'date'],
        ]);

        $case->expenses()->create([
            'description' => $validated['expense_description'],
            'amount' => $validated['expense_amount'],
            'expense_date' => $validated['expense_date'],
        ]);

        return $this->backToCase($case, 'Expense recorded.', 'case-expenses');
    }

    public function storeTimeEntry(Request $request, LegalCase $case)
    {
        $validated = $request->validate([
            'time_description' => ['required', 'string', 'max:255'],
            'entry_date' => ['required', 'date'],
            'time_hours' => ['required', 'integer', 'between:0,23'],
            'time_minutes' => ['required', 'integer', 'between:0,59'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
        ]);

        if (((int) $validated['time_hours'] * 60) + (int) $validated['time_minutes'] === 0) {
            throw ValidationException::withMessages([
                'time_hours' => 'Enter a duration of at least one minute.',
            ]);
        }

        $case->timeEntries()->create([
            'description' => $validated['time_description'],
            'entry_date' => $validated['entry_date'],
            'hours' => $validated['time_hours'],
            'minutes' => $validated['time_minutes'],
            'hourly_rate' => $validated['hourly_rate'] ?? null,
        ]);

        return $this->backToCase($case, 'Time entry recorded.', 'case-time');
    }

    /**
     * Upload one or more files into the case (or into a folder of it). For a folder upload the
     * browser sends each file's relative path, and the sub-folders are recreated.
     */
    public function storeDocument(Request $request, LegalCase $case)
    {
        $validated = $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:50'],
            'files.*' => ['required', 'file', 'max:20480', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png'],
            'paths' => ['sometimes', 'array'],
            'paths.*' => ['nullable', 'string', 'max:500'],
            'folder_id' => ['nullable', 'integer'],
        ], [
            'files.*.mimes' => 'Only PDF, Word, Excel, JPG and PNG files can be uploaded.',
            'files.*.max' => 'Each file must be 20 MB or smaller.',
        ]);

        $baseFolder = isset($validated['folder_id'])
            ? $case->documentFolders()->findOrFail($validated['folder_id'])
            : null;

        foreach ($validated['files'] as $index => $file) {
            $folder = $this->folderForPath($case, $baseFolder, $validated['paths'][$index] ?? null);
            $path = $file->store('case-documents/'.$case->id, 'local');

            if (! $path) {
                throw new \RuntimeException('The case document could not be saved.');
            }

            $case->documents()->create([
                'folder_id' => $folder?->id,
                'original_name' => Str::limit($file->getClientOriginalName(), 255, ''),
                'storage_path' => $path,
                'mime_type' => $file->getMimeType() ?: $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        $count = count($validated['files']);

        return $this->backToFolder($case, $baseFolder, $count === 1 ? 'Document uploaded.' : "{$count} documents uploaded.");
    }

    public function storeFolder(Request $request, LegalCase $case)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120', 'not_regex:/[\\\\\/]/'],
            'parent_id' => ['nullable', 'integer'],
        ], [
            'name.not_regex' => 'A folder name cannot contain / or \\.',
        ]);

        $parent = isset($validated['parent_id'])
            ? $case->documentFolders()->findOrFail($validated['parent_id'])
            : null;

        $name = $this->cleanFolderName($validated['name']);

        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'Enter a folder name.']);
        }

        $case->documentFolders()->firstOrCreate(['parent_id' => $parent?->id, 'name' => $name]);

        return $this->backToFolder($case, $parent, "Folder “{$name}” created.");
    }

    public function destroyFolder(LegalCase $case, CaseDocumentFolder $folder)
    {
        abort_unless($folder->case_id === $case->id, 404);

        if ($folder->children()->exists() || $folder->documents()->exists()) {
            return $this->backToFolder($case, $folder, 'Only empty folders can be deleted.');
        }

        $parent = $folder->parent;
        $folder->delete();

        return $this->backToFolder($case, $parent, 'Folder deleted.');
    }

    public function destroyDocument(LegalCase $case, CaseDocument $document)
    {
        abort_unless($document->case_id === $case->id, 404);

        $folder = $document->folder_id ? $case->documentFolders()->find($document->folder_id) : null;
        Storage::disk('local')->delete($document->storage_path);
        $document->delete();

        return $this->backToFolder($case, $folder, 'Document deleted.');
    }

    /**
     * The folder a file belongs in, creating sub-folders from its relative path ("A/B/file.pdf").
     */
    private function folderForPath(LegalCase $case, ?CaseDocumentFolder $base, ?string $relativePath): ?CaseDocumentFolder
    {
        if ($relativePath === null || ! str_contains($relativePath, '/')) {
            return $base;
        }

        $segments = array_slice(explode('/', str_replace('\\', '/', $relativePath)), 0, -1);
        $folder = $base;

        foreach (array_slice($segments, 0, 10) as $segment) {
            $name = $this->cleanFolderName($segment);

            if ($name === '' || $name === '.' || $name === '..') {
                continue;
            }

            $folder = $case->documentFolders()->firstOrCreate(['parent_id' => $folder?->id, 'name' => $name]);
        }

        return $folder;
    }

    private function cleanFolderName(string $name): string
    {
        $name = preg_replace('/[\x00-\x1F\x7F\\\\\/]+/u', ' ', $name) ?? '';

        return Str::limit(trim(preg_replace('/\s+/u', ' ', $name) ?? ''), 120, '');
    }

    private function backToFolder(LegalCase $case, ?CaseDocumentFolder $folder, string $message)
    {
        $parameters = $folder ? [$case, 'folder' => $folder->id] : [$case];

        return redirect()
            ->to(route('cases.show', $parameters).'#case-documents')
            ->with('success', $message);
    }

    public function downloadDocument(Request $request, LegalCase $case, CaseDocument $document): StreamedResponse
    {
        abort_unless($document->case_id === $case->id, 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($document->storage_path), 404);

        if ($request->boolean('preview') && $document->mime_type === 'application/pdf') {
            return $disk->response($document->storage_path, $document->original_name, ['Content-Type' => 'application/pdf']);
        }

        return $disk->download(
            $document->storage_path,
            $document->original_name,
            ['Content-Type' => $document->mime_type]
        );
    }

    private function backToCase(LegalCase $case, string $message, string $section)
    {
        return redirect()
            ->to(route('cases.show', $case).'#'.$section)
            ->with('success', $message);
    }
}
