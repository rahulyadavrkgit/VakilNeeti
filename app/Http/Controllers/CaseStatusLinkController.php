<?php

namespace App\Http\Controllers;

use App\Models\CourtHearing;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Client status link: a private, read-only page for one case (next date, purpose, court) that the
 * advocate sends to the client on WhatsApp, so clients stop calling to ask. It shows nothing else:
 * no notes, documents, fees or other parties' contact details. The advocate can switch it off.
 */
class CaseStatusLinkController extends Controller
{
    public function show(string $token): View
    {
        $case = LegalCase::withoutGlobalScopes()->where('status_token', $token)->whereNull('deleted_at')->firstOrFail();
        $hearings = CourtHearing::withoutGlobalScopes()->where('case_id', $case->id)->get();
        $today = today();

        $dates = $hearings->flatMap(fn (CourtHearing $hearing) => collect([$hearing->hearing_date, $hearing->next_hearing_date])
            ->filter()
            ->map(fn ($date) => ['date' => $date, 'hearing' => $hearing]));
        $next = $dates->filter(fn (array $item) => $item['date']->gte($today))->sortBy(fn (array $item) => $item['date']->toDateString())->first();
        $last = $dates->filter(fn (array $item) => $item['date']->lt($today))->sortByDesc(fn (array $item) => $item['date']->toDateString())->first();

        return view('cases.status-link', [
            'case' => $case,
            'next' => $next,
            'last' => $last,
            'advocate' => User::find($case->user_id),
        ]);
    }

    public function create(LegalCase $case): RedirectResponse
    {
        if (! $case->status_token) {
            $case->forceFill(['status_token' => Str::random(40)])->save();
        }

        return redirect()->to(route('cases.show', $case).'#client-link')->with('success', 'Client link ready. Copy it or send it on WhatsApp.');
    }

    public function destroy(LegalCase $case): RedirectResponse
    {
        $case->forceFill(['status_token' => null])->save();

        return redirect()->to(route('cases.show', $case).'#client-link')->with('success', 'Client link switched off. The old link no longer opens.');
    }
}
