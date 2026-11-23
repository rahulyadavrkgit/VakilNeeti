<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CaseDashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $tomorrow = $today->copy()->addDay();
        $dayTwo = $today->copy()->addDays(2);
        $daySeven = $today->copy()->addDays(7);
        $dayEight = $today->copy()->addDays(8);
        $dayThirty = $today->copy()->addDays(30);

        $totalCases = DB::table('legal_cases')
            ->when(! auth()->user()->is_admin, fn ($query) => $query->where('user_id', auth()->id()))
            ->count();

        $pendingCases = DB::table('legal_cases')
            ->when(! auth()->user()->is_admin, fn ($query) => $query->where('user_id', auth()->id()))
            ->whereNotIn('case_status', [
                'disposed', 'Disposed',
                'closed', 'Closed',
                'decided', 'Decided',
            ])
            ->count();

        $hearingBase = DB::table('court_hearings')
            ->join('legal_cases', function ($join) {
                $join->on('court_hearings.case_id', '=', 'legal_cases.id');
                if (! auth()->user()->is_admin) {
                    $join->where('legal_cases.user_id', auth()->id());
                }
            })
            ->when(! auth()->user()->is_admin, fn ($query) => $query->where('court_hearings.user_id', auth()->id()))
            ->whereNotNull('court_hearings.next_hearing_date');

        $hearingColumns = [
            'court_hearings.id',
            'court_hearings.case_id',
            'court_hearings.next_hearing_date',
            'court_hearings.hearing_time',
            'court_hearings.hearing_stage',
            'court_hearings.court_room',
            'court_hearings.vc_link as hearing_vc_link',
            'court_hearings.judge_name',
            'legal_cases.judge_name as case_judge_name',
            'legal_cases.case_title',
            'legal_cases.case_number',
            'legal_cases.cnr_number',
            'legal_cases.court_name',
            'legal_cases.appearing_for',
            'legal_cases.appearing_for_parties',
            'legal_cases.court_room as case_court_room',
            'legal_cases.vc_link as case_vc_link',
        ];

        // Four non-overlapping hearing counters.
        $hearingsToday = (clone $hearingBase)
            ->whereDate('court_hearings.next_hearing_date', $today)
            ->count();

        $hearingsTomorrow = (clone $hearingBase)
            ->whereDate('court_hearings.next_hearing_date', $tomorrow)
            ->count();

        $hearingsNext7Days = (clone $hearingBase)
            ->whereDate('court_hearings.next_hearing_date', '>=', $dayTwo)
            ->whereDate('court_hearings.next_hearing_date', '<=', $daySeven)
            ->count();

        $hearingsNext30Days = (clone $hearingBase)
            ->whereDate('court_hearings.next_hearing_date', '>=', $dayEight)
            ->whereDate('court_hearings.next_hearing_date', '<=', $dayThirty)
            ->count();

        // All upcoming hearings from tomorrow through the next 30 days.
        $upcomingHearings = (clone $hearingBase)
            ->whereDate('court_hearings.next_hearing_date', '>=', $tomorrow)
            ->whereDate('court_hearings.next_hearing_date', '<=', $dayThirty)
            ->select(
                'court_hearings.id',
                'court_hearings.case_id',
                'court_hearings.next_hearing_date',
                'court_hearings.hearing_time',
                'court_hearings.court_room',
                'court_hearings.judge_name',
                'court_hearings.hearing_stage',
                'legal_cases.case_title',
                'legal_cases.case_number',
                'legal_cases.cnr_number',
                'legal_cases.court_name',
                'legal_cases.appearing_for',
                'legal_cases.appearing_for_parties',
                'legal_cases.court_room as case_court_room',
                'legal_cases.vc_link as case_vc_link',
                'legal_cases.judge_name as case_judge_name',
                'court_hearings.vc_link as hearing_vc_link'
            )
            ->orderBy('court_hearings.next_hearing_date')
            ->orderBy('court_hearings.hearing_time')
            ->limit(50)
            ->get();

        $todayHearings = (clone $hearingBase)
            ->whereDate('court_hearings.next_hearing_date', $today)
            ->select($hearingColumns)
            ->orderBy('court_hearings.hearing_time')
            ->limit(20)
            ->get();

        $pastDueHearings = (clone $hearingBase)
            ->whereDate('court_hearings.next_hearing_date', '<', $today)
            ->select($hearingColumns)
            ->orderByDesc('court_hearings.next_hearing_date')
            ->limit(10)
            ->get();

        $recentHearings = DB::table('court_hearings')
            ->join('legal_cases', function ($join) {
                $join->on('court_hearings.case_id', '=', 'legal_cases.id');
                if (! auth()->user()->is_admin) {
                    $join->where('legal_cases.user_id', auth()->id());
                }
            })
            ->when(! auth()->user()->is_admin, fn ($query) => $query->where('court_hearings.user_id', auth()->id()))
            ->whereNotNull('court_hearings.hearing_date')
            ->orderByDesc('court_hearings.hearing_date')
            ->orderByDesc('court_hearings.id')
            ->limit(10)
            ->select(
                'court_hearings.id',
                'court_hearings.case_id',
                'court_hearings.hearing_date',
                'court_hearings.hearing_stage',
                'court_hearings.court_room',
                'court_hearings.judge_name',
                'court_hearings.vc_link as hearing_vc_link',
                'legal_cases.case_title',
                'legal_cases.case_number',
                'legal_cases.court_name',
                'legal_cases.appearing_for',
                'legal_cases.appearing_for_parties',
                'legal_cases.court_room as case_court_room',
                'legal_cases.judge_name as case_judge_name',
                'legal_cases.vc_link as case_vc_link'
            )
            ->get();

        $recentCases = DB::table('legal_cases')
            ->when(! auth()->user()->is_admin, fn ($query) => $query->where('user_id', auth()->id()))
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        return view('case-dashboard.index', compact(
            'totalCases',
            'pendingCases',
            'hearingsToday',
            'hearingsTomorrow',
            'hearingsNext7Days',
            'hearingsNext30Days',
            'upcomingHearings',
            'todayHearings',
            'pastDueHearings',
            'recentHearings',
            'recentCases',
        ));
    }
}
