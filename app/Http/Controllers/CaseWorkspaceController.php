<?php

namespace App\Http\Controllers;

use App\Models\CaseAppointment;
use App\Models\CaseDocument;
use App\Models\CaseExpense;
use App\Models\CaseNote;
use App\Models\CaseTimeEntry;
use App\Models\LegalCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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

    public function storeDocument(Request $request, LegalCase $case)
    {
        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'max:20480',
                'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png',
            ],
        ]);

        $file = $validated['file'];
        $path = $file->store('case-documents/'.$case->id, 'local');

        if (!$path) {
            throw new \RuntimeException('The case document could not be saved.');
        }

        $case->documents()->create([
            'original_name' => Str::limit($file->getClientOriginalName(), 255, ''),
            'storage_path' => $path,
            'mime_type' => $file->getMimeType() ?: $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);

        return $this->backToCase($case, 'Document uploaded.', 'case-documents');
    }

    public function downloadDocument(LegalCase $case, CaseDocument $document)
    {
        abort_unless($document->case_id === $case->id, 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($document->storage_path), 404);

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
