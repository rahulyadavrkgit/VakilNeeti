<?php

namespace App\Http\Controllers;

use App\Models\ApiSyncRun;
use App\Models\CourtRecordImport;
use App\Models\LegalCase;
use App\Models\User;
use App\Notifications\ReminderNotification;
use App\Services\AdminCaseReview;
use App\Services\RecentSiteErrors;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminReviewController extends Controller
{
    public function overview(AdminCaseReview $review, RecentSiteErrors $errors): View
    {
        $feedback = DB::table('admin_review_feedback')->selectRaw('finding, status, count(*) as total')
            ->groupBy('finding', 'status')->get()->groupBy('finding');
        $findings = collect(AdminCaseReview::FINDINGS)->map(function (array $info, string $finding) use ($review, $feedback): array {
            $ratings = $feedback->get($finding, collect())->pluck('total', 'status');
            $count = $review->pending($finding)->count();
            $confirmed = (int) $ratings->get('confirmed', 0);
            $dismissed = (int) $ratings->get('false-positive', 0);

            return ['label' => $info[0], 'source' => $info[1], 'count' => $count,
                'confirmed' => $confirmed, 'dismissed' => $dismissed, 'priority' => $count ? $count * 2 + $confirmed - $dismissed : PHP_INT_MIN];
        })->sortByDesc('priority');

        return view('auth.admin-overview', [
            'findings' => $findings,
            'imports' => $this->failedImports()->count(),
            'syncs' => $this->failedSyncs()->count(),
            'reports' => DB::table('case_support_reports')->whereNull('resolved_at')->count(),
            'errorCount' => count($errors->since(now()->subDay())),
        ]);
    }

    public function queue(Request $request, string $finding, AdminCaseReview $review): View
    {
        $showReviewed = $request->boolean('reviewed');
        $query = $showReviewed ? $review->cases($finding) : $review->pending($finding);
        $queueFilter = $request->validate(['queue' => ['nullable', Rule::in(['mine', 'recurring'])]])['queue'] ?? null;
        if ($queueFilter) {
            $query->whereExists(fn ($feedback) => $feedback->from('admin_review_feedback')
                ->whereColumn('case_id', 'legal_cases.id')->where('finding', $finding)
                ->when($queueFilter === 'mine', fn ($mine) => $mine->where('assigned_to', $request->user()->id))
                ->when($queueFilter === 'recurring', fn ($repeated) => $repeated->where('occurrences', '>', 1)));
        }
        $cases = $query->orderByDesc('updated_at')->orderByDesc('id')->paginate(20)->withQueryString();
        $decisions = DB::table('admin_review_feedback')->where('finding', $finding)
            ->whereIn('case_id', $cases->pluck('id'))->get()->keyBy('case_id');
        $reviewers = User::query()->where('is_admin', true)->whereNull('suspended_at')->orderBy('name')->get(['id', 'name']);

        return view('auth.admin-queue', [
            'finding' => $finding,
            'label' => AdminCaseReview::FINDINGS[$finding][0],
            'source' => AdminCaseReview::FINDINGS[$finding][1],
            'cases' => $cases,
            'showReviewed' => $showReviewed,
            'decisions' => $decisions,
            'reviewers' => $reviewers,
            'queueFilter' => $queueFilter,
        ]);
    }

    public function feedback(Request $request, string $finding, int $caseId, AdminCaseReview $review): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(AdminCaseReview::STATUSES))],
            'reason' => ['nullable', 'string', 'max:500'],
            'assigned_to' => ['nullable', Rule::exists('users', 'id')->where('is_admin', true)->whereNull('suspended_at')],
        ]);
        $case = $review->cases($finding)->findOrFail($caseId);

        DB::transaction(function () use ($case, $finding, $data, $request): void {
            $previous = DB::table('admin_review_feedback')->where('case_id', $case->id)->where('finding', $finding)->lockForUpdate()->first();
            $recurs = $previous && $case->updated_at?->gt($previous->updated_at) && in_array($previous->status, ['resolved', 'false-positive'], true);
            DB::table('admin_review_feedback')->upsert([
                'case_id' => $case->id, 'finding' => $finding, 'status' => $data['status'],
                'assigned_to' => array_key_exists('assigned_to', $data) ? $data['assigned_to'] : $previous?->assigned_to,
                'note' => $data['reason'] ?? $previous?->note,
                'occurrences' => ($previous?->occurrences ?? 1) + (int) $recurs,
                'updated_at' => now(),
            ], ['case_id', 'finding'], ['status', 'assigned_to', 'note', 'occurrences', 'updated_at']);
            $this->record($request->user()->id, $case->user_id, "review {$finding}: ".$data['status'], $data['reason'] ?? null, $case->id);
        });

        return back()->with('success', 'Review decision recorded.');
    }

    public function notifyDateConflict(Request $request, int $caseId, AdminCaseReview $review): RedirectResponse
    {
        $case = $review->cases('listing-conflict')->findOrFail($caseId);
        $owner = User::query()->findOrFail($case->user_id);
        $saved = collect([$case->hearing_day, $case->fixed_day])->filter()->sort()->first();
        $key = "date-conflict:{$case->id}:{$saved}:{$case->listed_date}";
        $this->notifyOnce($owner, $key, new ReminderNotification('alert', 'Check conflicting hearing dates',
            "Saved date {$saved}; matched cause list {$case->listed_date}. Check the latest court listing before changing your diary.",
            route('cases.show', $case, false), $key), $request->user()->id, 'sent hearing date discrepancy', $case->id);

        return back()->with('success', 'The workspace owner has a review notification.');
    }

    public function retryImport(Request $request, int $importId): RedirectResponse
    {
        $import = $this->failedImports()->findOrFail($importId);
        $method = $import->retryMethod();
        abort_unless($method, 404);
        $owner = User::query()->findOrFail($import->user_id);
        $key = 'import-retry:'.$import->id;
        $this->notifyOnce($owner, $key, new ReminderNotification('alert', 'Retry requested for a case import',
            'Correct the original export and upload it again. Review the preview before saving.',
            route('cases.find', ['method' => $method, 'retry' => $import->id], false), $key),
            $request->user()->id, 'requested import retry #'.$import->id);

        return back()->with('success', 'A retry request was sent to the workspace owner.');
    }

    private function notifyOnce(User $owner, string $key, ReminderNotification $notification, int $actor, string $action, ?int $caseId = null): void
    {
        DB::transaction(function () use ($owner, $key, $notification, $actor, $action, $caseId): void {
            User::query()->whereKey($owner->id)->lockForUpdate()->firstOrFail();
            if (! $owner->notifications()->where('data->key', $key)->exists()) {
                $owner->notify($notification);
                $this->record($actor, $owner->id, $action, null, $caseId);
            }
        }, 3);
    }

    public function reports(): View
    {
        $reports = DB::table('case_support_reports as report')->join('legal_cases as cases', 'cases.id', '=', 'report.case_id')
            ->join('users as reporter', 'reporter.id', '=', 'report.reporter_id')
            ->whereNull('cases.deleted_at')->whereNull('report.resolved_at')
            ->select('report.*', 'cases.user_id', 'cases.case_title', 'reporter.name')
            ->latest('report.id')->paginate(25);

        return view('auth.admin-reports', compact('reports'));
    }

    public function resolveReport(Request $request, int $reportId): RedirectResponse
    {
        DB::transaction(function () use ($request, $reportId): void {
            $report = DB::table('case_support_reports')->where('id', $reportId)->whereNull('resolved_at')->lockForUpdate()->first();
            abort_unless($report, 404);
            $case = LegalCase::query()->withoutGlobalScope('user_ownership')->findOrFail($report->case_id);
            DB::table('case_support_reports')->where('id', $reportId)->update(['resolved_at' => now(), 'updated_at' => now()]);
            $this->record($request->user()->id, $case->user_id, 'resolved problem report #'.$reportId, null, $case->id);
        });

        return back()->with('success', 'Problem report resolved.');
    }

    public function access(Request $request, User $account): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['suspend', 'restore', 'grant-admin', 'remove-admin', 'role-junior', 'role-clerk'])],
            'reason' => ['required', 'string', 'min:8', 'max:500'],
        ]);
        $actorId = $request->user()->id;
        abort_if($actorId === $account->id, 403, 'Use another administrator to change your own access.');

        DB::transaction(function () use ($actorId, $account, $data): void {
            $users = User::query()->whereIn('id', [$actorId, $account->id])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $actor = $users[$actorId];
            $target = $users[$account->id];
            abort_unless($actor->is_admin && ! $actor->suspended_at, 403);

            $changes = match ($data['action']) {
                'suspend' => $target->suspended_at ? [] : ['suspended_at' => now(), 'remember_token' => Str::random(60)],
                'restore' => $target->suspended_at ? ['suspended_at' => null] : [],
                'grant-admin' => $target->is_admin || $target->isTeamMember() || $target->suspended_at ? [] : ['is_admin' => true, 'admin_password_setup' => true],
                'remove-admin' => $target->is_admin ? ['is_admin' => false, 'admin_password_setup' => false] : [],
                'role-junior', 'role-clerk' => $target->isTeamMember() && $target->team_role !== substr($data['action'], 5)
                    ? ['team_role' => substr($data['action'], 5)] : [],
            };

            if ($changes === []) {
                return;
            }

            $target->forceFill($changes + ['admin_access_version' => (int) $target->admin_access_version + 1])->save();
            if (config('session.driver') === 'database') {
                DB::connection(config('session.connection'))->table(config('session.table'))->where('user_id', $target->id)->delete();
            }

            $this->record($actorId, $target->id, $data['action'], $data['reason']);
        }, 3);

        return back()->with('success', 'Account access reviewed.');
    }

    public function audit(): View
    {
        $entries = DB::table('admin_audit_entries as audit')
            ->leftJoin('users as actor', 'actor.id', '=', 'audit.actor_id')
            ->leftJoin('users as account', 'account.id', '=', 'audit.account_id')
            ->select('audit.*', 'actor.name as actor_name', 'account.name as account_name')
            ->latest('audit.id')->paginate(30);

        return view('auth.admin-audit', compact('entries'));
    }

    public function operations(string $kind, RecentSiteErrors $errors): View
    {
        abort_unless(in_array($kind, ['imports', 'syncs', 'errors'], true), 404);
        $rows = match ($kind) {
            'imports' => $this->failedImports()->latest('id')->paginate(25, ['id', 'user_id', 'source_name', 'import_status', 'created_at', 'parsed_data']),
            'syncs' => $this->failedSyncs()->latest('id')->paginate(25, ['id', 'provider_key', 'entity_type', 'status', 'started_at']),
            'errors' => collect($errors->since(now()->subDay()))->countBy()->sortDesc()->take(30),
        };

        $retries = $kind === 'imports' ? CourtRecordImport::query()->withoutGlobalScope('user_ownership')
            ->whereIn('parsed_data->retry_of', $rows->pluck('id'))->latest('id')
            ->get(['id', 'import_status', 'parsed_data', 'created_at'])
            ->groupBy(fn (CourtRecordImport $import) => $import->parsed_data['retry_of']) : collect();

        return view('auth.admin-operations', compact('kind', 'rows', 'retries'));
    }

    private function failedImports(): Builder
    {
        return CourtRecordImport::query()->withoutGlobalScope('user_ownership')
            ->whereIn('import_status', ['failed', 'error', 'partial'])->where('created_at', '>=', now()->subDay());
    }

    private function failedSyncs(): Builder
    {
        return ApiSyncRun::query()->whereIn('status', ['failed', 'completed_with_errors'])->where('started_at', '>=', now()->subDay());
    }

    private function record(int $actorId, int $accountId, string $action, ?string $reason, ?int $caseId = null): void
    {
        DB::table('admin_audit_entries')->insert([
            'actor_id' => $actorId, 'account_id' => $accountId, 'case_id' => $caseId,
            'action' => $action, 'reason' => $reason, 'created_at' => now(),
        ]);
    }
}
