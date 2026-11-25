<?php

namespace App\Http\Controllers;

use App\Models\LegalCase;
use App\Models\Task;
use App\Models\User;
use App\Notifications\ReminderNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CaseFollowUpController extends Controller
{
    public static function team(int $workspaceId): Builder
    {
        return User::query()->whereNull('suspended_at')
            ->where(fn (Builder $query) => $query->whereKey($workspaceId)->orWhere('team_owner_id', $workspaceId));
    }

    public function store(Request $request, LegalCase $case): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'assigned_to' => ['required', 'integer'],
            'due_at' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
        ]);

        $assignee = self::team($case->user_id)->find($data['assigned_to']);
        if (! $assignee) {
            throw ValidationException::withMessages(['assigned_to' => 'Choose an active member of this workspace.']);
        }

        DB::transaction(function () use ($case, $data, $assignee, $request): void {
            $task = Task::create($data + ['case_id' => $case->id, 'status' => 'pending', 'priority' => 'medium']);
            if ($assignee->id !== $request->user()->id) {
                $assignee->notify(new ReminderNotification('task', 'New case follow-up', $task->title, route('cases.show', $case, false).'#case-follow-ups', 'case-task:'.$task->id));
            }
        });

        return redirect(route('cases.show', $case).'#case-follow-ups')->with('success', 'Follow-up assigned.');
    }

    public function status(Request $request, LegalCase $case, Task $task): RedirectResponse
    {
        abort_unless($task->case_id === $case->id && $task->assigned_to, 404);
        $data = $request->validate(['status' => ['required', Rule::in(['pending', 'in_progress', 'completed'])]]);
        abort_unless($request->user()->id === $task->assigned_to || $request->user()->id === $case->user_id, 403);
        $task->update(['status' => $data['status'], 'completed_at' => $data['status'] === 'completed' ? now() : null]);

        return redirect(route('cases.show', $case).'#case-follow-ups')->with('success', 'Follow-up updated.');
    }

    public function report(Request $request, LegalCase $case): RedirectResponse
    {
        $data = $request->validate([
            'page' => ['required', Rule::in(['court-day', 'case-details'])],
            'message' => ['required', 'string', 'max:500'],
        ]);

        DB::table('case_support_reports')->insert([
            'case_id' => $case->id, 'reporter_id' => $request->user()->id,
            'page' => $data['page'], 'message' => $data['message'], 'created_at' => now(), 'updated_at' => now(),
        ]);

        return back()->with('success', 'Case problem reported.');
    }
}
