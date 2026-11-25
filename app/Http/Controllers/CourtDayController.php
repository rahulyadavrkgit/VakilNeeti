<?php

namespace App\Http\Controllers;

use App\Models\CaseParty;
use App\Services\CalendarEventService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class CourtDayController extends Controller
{
    public function __invoke(Request $request, CalendarEventService $calendar): Response
    {
        $data = $request->validate(['date' => ['nullable', 'date_format:Y-m-d']]);
        $selectedDate = isset($data['date']) ? Carbon::parse($data['date'])->startOfDay() : null;
        $dates = collect($selectedDate ? [$selectedDate] : [today(), today()->addDay()]);

        $hearings = $calendar->between($dates->first(), $dates->last(), ['groups' => ['hearing']])
            ->sortBy(fn (array $event): string => $event['date'].($event['time'] ?? '99:99'))
            ->unique(fn (array $event): string => ($event['case_id'] ?? 'h'.$event['id']).'|'.$event['date']);

        $clients = CaseParty::query()
            ->with('client:id,display_name,first_name,last_name,mobile,phone')
            ->whereIn('case_id', $hearings->pluck('case_id')->filter()->unique())
            ->whereNotNull('client_id')->get()->groupBy('case_id');

        $days = $dates->map(fn (Carbon $day): array => [
            'date' => $day,
            'hearings' => $hearings->where('date', $day->toDateString())->map(fn (array $event): array => $event + [
                'clients' => ($clients[$event['case_id']] ?? collect())->pluck('client')->filter()->unique('id')->values(),
            ])->values(),
        ]);

        return response()->view('court-day', ['days' => $days, 'selectedDate' => $selectedDate, 'savedAt' => now()])
            ->header('Cache-Control', 'private, no-store');
    }
}
