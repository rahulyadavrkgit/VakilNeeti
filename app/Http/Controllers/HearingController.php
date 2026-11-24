<?php

namespace App\Http\Controllers;

use App\Models\CourtHearing;
use App\Models\LegalCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HearingController extends Controller
{
    public function create(Request $request): View
    {
        $date = $request->query('date');

        return $this->form(new CourtHearing([
            'case_id' => (int) $request->query('case') ?: null,
            'hearing_date' => is_string($date) && strtotime($date) ? Carbon::parse($date) : today(),
        ]), $request->query('return') === 'case' ? 'case' : 'calendar');
    }

    /**
     * Record a hearing for one of the signed-in user's cases. A hearing on a future date
     * also becomes the case's next hearing, so it shows on the case page, case list and home.
     */
    public function store(Request $request): RedirectResponse
    {
        $hearing = CourtHearing::create([
            ...$this->validated($request),
            'source_type' => 'manual',
        ]);

        return $this->redirectAfterSave($request, $hearing, 'Hearing added.');
    }

    public function edit(CourtHearing $hearing): View
    {
        abort_unless($hearing->isManual(), 403, 'Imported court records cannot be edited.');

        return $this->form($hearing, 'case');
    }

    public function update(Request $request, CourtHearing $hearing): RedirectResponse
    {
        abort_unless($hearing->isManual(), 403, 'Imported court records cannot be edited.');

        $hearing->update($this->validated($request));

        return $this->redirectAfterSave($request, $hearing, 'Hearing updated.');
    }

    public function destroy(CourtHearing $hearing): RedirectResponse
    {
        abort_unless($hearing->isManual(), 403, 'Imported court records cannot be deleted.');

        $caseId = $hearing->case_id;
        $hearing->delete();

        return redirect()->route('cases.show', $caseId)->with('success', 'Hearing deleted.');
    }

    private function form(CourtHearing $hearing, string $returnTo): View
    {
        return view('hearings.form', [
            'hearing' => $hearing,
            'cases' => LegalCase::query()
                ->where(fn ($query) => $query->whereNull('closed_at')->when($hearing->case_id, fn ($query) => $query->orWhere('id', $hearing->case_id)))
                ->orderBy('case_title')
                ->get(['id', 'case_title', 'case_number', 'court_name']),
            'returnTo' => $returnTo,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'case_id' => ['required', 'integer', Rule::exists('legal_cases', 'id')->where('user_id', auth()->user()->workspaceId())->whereNull('deleted_at')],
            'hearing_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:1950-01-01', 'before_or_equal:2100-12-31'],
            'hearing_time' => ['nullable', 'date_format:H:i'],
            'hearing_stage' => ['nullable', 'string', 'max:255'],
            'court_room' => ['nullable', 'string', 'max:100'],
            'vc_link' => ['nullable', 'url:http,https', 'max:500'],
            'judge_name' => ['nullable', 'string', 'max:255'],
            'proceedings' => ['nullable', 'string', 'max:5000'],
            'next_hearing_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:hearing_date', 'before_or_equal:2100-12-31'],
            'next_action' => ['nullable', 'string', 'max:255'],
        ], [
            'case_id.required' => 'Choose a case.',
            'case_id.exists' => 'Choose one of your cases.',
            'next_hearing_date.after_or_equal' => 'The next date cannot be before the hearing date.',
        ]);

        if (empty($validated['next_hearing_date']) && Carbon::parse($validated['hearing_date'])->gte(today())) {
            $validated['next_hearing_date'] = $validated['hearing_date'];
        }

        return $validated;
    }

    private function redirectAfterSave(Request $request, CourtHearing $hearing, string $message): RedirectResponse
    {
        if ($request->input('return') === 'case') {
            return redirect()->route('cases.show', $hearing->case_id)->with('success', $message);
        }

        // Quick "Add next date" forms on lists go back to the same list.
        if ($request->input('return') === 'back') {
            return redirect()->back()->with('success', $message);
        }

        return redirect()->route('cases.calendar', ['view' => 'day', 'date' => ($hearing->next_hearing_date ?? $hearing->hearing_date)->toDateString()])
            ->with('success', $message);
    }
}
