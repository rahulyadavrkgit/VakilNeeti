<?php

namespace App\Http\Controllers;

use App\Models\CourtHearing;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * Client status link: a private, read-only page for one case (next date, purpose, court) that the
 * advocate sends to the client on WhatsApp, so clients stop calling to ask. It shows nothing else:
 * no notes, documents, fees or other parties' contact details. The advocate can switch it off.
 */
class CaseStatusLinkController extends Controller
{
    public function show(string $token): Response
    {
        $case = LegalCase::withoutGlobalScopes()
            ->where('status_token', $token)
            ->where('status_link_expires_at', '>', now())
            ->whereNull('deleted_at')
            ->firstOrFail();
        $hearings = CourtHearing::withoutGlobalScopes()->where('case_id', $case->id)->get();
        $today = today();

        $dates = $hearings->flatMap(fn (CourtHearing $hearing) => collect([$hearing->hearing_date, $hearing->next_hearing_date])
            ->filter()
            ->map(fn ($date) => ['date' => $date, 'hearing' => $hearing]));
        $next = $dates->filter(fn (array $item) => $item['date']->gte($today))->sortBy(fn (array $item) => $item['date']->toDateString())->first();
        $last = $dates->filter(fn (array $item) => $item['date']->lt($today))->sortByDesc(fn (array $item) => $item['date']->toDateString())->first();

        return response()->view('cases.status-link', [
            'case' => $case,
            'next' => $next,
            'last' => $last,
            'advocate' => User::find($case->user_id),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function create(LegalCase $case): RedirectResponse
    {
        $now = now();

        $case->newQuery()->whereKey($case->getKey())
            ->where(fn ($query) => $query->whereNull('status_token')
                ->orWhereNull('status_link_expires_at')
                ->orWhere('status_link_expires_at', '<=', $now))
            ->update([
                'status_token' => Str::random(40),
                'status_link_expires_at' => $now->copy()->addWeek(),
            ]);

        return redirect()->to(route('cases.show', $case).'#client-link')->with('success', 'Client link ready. Copy it or send it on WhatsApp.');
    }

    public function destroy(LegalCase $case): RedirectResponse
    {
        $case->newQuery()->whereKey($case->getKey())->update([
            'status_token' => null,
            'status_link_expires_at' => null,
        ]);

        return redirect()->to(route('cases.show', $case).'#client-link')->with('success', 'Client link switched off. The old link no longer opens.');
    }
}
