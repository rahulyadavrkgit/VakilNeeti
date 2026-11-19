<?php

namespace App\Http\Controllers;

use App\Models\CourtHearing;
use App\Models\LegalCase;
use App\Models\Task;

class DashboardController extends Controller
{
    public function index()
    {
        $today = today();
        $sevenDaysLater = today()->copy()->addDays(7);

        // Case statistics
        $totalCases = LegalCase::count();

        $pendingCases = LegalCase::where('case_status', 'pending')
            ->count();

        $recentCases = LegalCase::latest('updated_at')
            ->limit(8)
            ->get();

        // Hearings scheduled for today
        $todayHearings = CourtHearing::with('legalCase')
            ->whereDate('next_hearing_date', $today)
            ->whereHas('legalCase')
            ->orderBy('hearing_time')
            ->get();

        // Hearings scheduled during the next seven days
        $upcomingHearings = CourtHearing::with('legalCase')
            ->whereDate('next_hearing_date', '>', $today)
            ->whereDate('next_hearing_date', '<=', $sevenDaysLater)
            ->whereHas('legalCase')
            ->orderBy('next_hearing_date')
            ->orderBy('hearing_time')
            ->limit(15)
            ->get();

        // Past hearing dates requiring review
        $pastDueHearings = CourtHearing::with('legalCase')
            ->whereDate('next_hearing_date', '<', $today)
            ->whereHas('legalCase')
            ->orderByDesc('next_hearing_date')
            ->limit(10)
            ->get();

        // Recent hearing history
        $recentHearings = CourtHearing::with('legalCase')
            ->whereNotNull('hearing_date')
            ->whereHas('legalCase')
            ->orderByDesc('hearing_date')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        // Tasks and deadlines
        $overdueTasks = Task::with('client')
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->whereIn('status', ['pending', 'in_progress'])
            ->orderBy('due_at')
            ->limit(10)
            ->get();

        $todayTasks = Task::with('client')
            ->whereDate('due_at', $today)
            ->whereIn('status', ['pending', 'in_progress'])
            ->orderBy('due_at')
            ->get();

        $upcomingTasks = Task::with('client')
            ->where('due_at', '>', now()->endOfDay())
            ->whereIn('status', ['pending', 'in_progress'])
            ->orderBy('due_at')
            ->limit(10)
            ->get();

        $pendingTasksCount = Task::whereIn('status', [
            'pending',
            'in_progress',
        ])->count();

        $completedTasksCount = Task::where('status', 'completed')
            ->count();

        return view('dashboard', compact(
            'today',
            'totalCases',
            'pendingCases',
            'recentCases',
            'todayHearings',
            'upcomingHearings',
            'pastDueHearings',
            'recentHearings',
            'overdueTasks',
            'todayTasks',
            'upcomingTasks',
            'pendingTasksCount',
            'completedTasksCount'
        ));
    }
}