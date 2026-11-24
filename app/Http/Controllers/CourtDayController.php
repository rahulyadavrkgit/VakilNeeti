<?php

namespace App\Http\Controllers;

use App\Models\CaseParty;
use App\Models\CauseListAppearance;
use App\Models\LegalCase;
use App\Services\CalendarEventService;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

/**
 * "Court day": today's and tomorrow's hearings on one light page (court, room, item, VC, client's phone).
 * The phone keeps a copy (see public/sw.js), so it still opens in a court corridor with no signal.
 */
class CourtDayController extends Controller
{
    public function __invoke(CalendarEventService $calendar): Response
    {
        $hearings = $calendar->between(today(), today()->addDay(), ['groups' => ['hearing']])
            ->sortBy(fn (array $event): string => $event['date'].($event['time'] ?? '99:99'))
            // A matter shows once a day (a hearing and an earlier "next date" for the same day are one visit).
            ->unique(fn (array $event): string => ($event['case_id'] ?? 'h'.$event['id']).'|'.$event['date'])
            ->values();

        // Matters printed in the court's cause list count too, even when no hearing was saved for that day.
        $listings = CauseListAppearance::upcomingForCases(LegalCase::query()->select('id'), today()->addDay());
        $hearingKeys = $hearings->map(fn (array $event): string => $event['case_id'].'|'.$event['date'])->flip();

        foreach ($listings as $listing) {
            $date = $listing->courtDocument->listing_date->toDateString();
            $case = $listing->legalCase;

            if ($case && ! $hearingKeys->has($case->id.'|'.$date)) {
                $hearingKeys->put($case->id.'|'.$date, true);
                $hearings->push([
                    'id' => 'list-'.$listing->id, 'case_id' => $case->id, 'date' => $date, 'time' => null,
                    'title' => $case->case_title ?: $listing->case_reference, 'case_number' => $case->case_number ?: $listing->case_reference,
                    'court' => $case->court_name, 'court_complex' => null, 'court_room' => null, 'judge_name' => null,
                    'vc_link' => null, 'details' => null, 'url' => route('cases.show', $case),
                ]);
            }
        }

        $caseIds = $hearings->pluck('case_id')->filter()->unique()->values();

        $clients = CaseParty::query()
            ->with('client:id,display_name,first_name,last_name,mobile,phone')
            ->whereIn('case_id', $caseIds)
            ->whereNotNull('client_id')
            ->get()
            ->groupBy('case_id');

        $listings = $listings->groupBy(fn (CauseListAppearance $listing): string => $listing->legal_case_id.'|'.$listing->courtDocument->listing_date->toDateString());

        $days = collect([today(), today()->addDay()])->map(fn (Carbon $day): array => [
            'date' => $day,
            'hearings' => $hearings->sortBy(fn (array $event): string => $event['time'] ?? '99:99')->where('date', $day->toDateString())->map(fn (array $event): array => $event + [
                'clients' => ($clients[$event['case_id']] ?? collect())->pluck('client')->filter()->unique('id')->values(),
                'listing' => ($listings[$event['case_id'].'|'.$day->toDateString()] ?? collect())->first(),
            ])->values(),
        ]);

        // Never stored by shared caches; the phone's own copy is kept by the service worker.
        return response()->view('court-day', ['days' => $days, 'savedAt' => now()])
            ->header('Cache-Control', 'private, no-store');
    }
}
