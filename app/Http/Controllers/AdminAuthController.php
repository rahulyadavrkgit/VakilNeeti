<?php

namespace App\Http\Controllers;

use App\Models\CaseDocument;
use App\Models\LegalCase;
use App\Models\User;
use App\Services\Msg91EmailService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.admin-login');
    }

    public function login(Request $request, Msg91EmailService $emailService): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:254'],
            'setup' => ['nullable', 'boolean'],
            'password' => [Rule::requiredIf(! $request->boolean('setup')), 'nullable', 'string', 'max:255'],
        ]);
        $request->session()->forget('admin_challenge');
        $user = User::query()->whereRaw('lower(email) = ?', [strtolower(trim($credentials['email']))])->first();

        $setup = $request->boolean('setup');
        if (! $user || ! $user->is_admin || $user->suspended_at || (bool) $user->admin_password_setup !== $setup
            || (! $setup && ! Hash::check($credentials['password'], $user->password))) {
            return back()->withErrors(['email' => 'Administrator sign-in failed.'])->onlyInput('email');
        }

        $code = (string) random_int(100000, 999999);
        if ($error = $emailService->sendOtp($user->email, $code, $user->name, 5)) {
            return back()->withErrors(['email' => $error])->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->session()->put('admin_challenge', [
            'id' => $user->id,
            'version' => (int) $user->admin_access_version,
            'setup' => $setup,
            'hash' => Hash::make($code),
            'expires' => now()->addMinutes(5)->timestamp,
            'attempts' => 0,
        ]);

        return redirect()->route('admin.verify');
    }

    public function showVerification(Request $request, Msg91EmailService $emailService): View|RedirectResponse
    {
        return $request->session()->has('admin_challenge')
            ? view('auth.admin-verify', [
                'setup' => (bool) $request->session()->get('admin_challenge.setup'),
                'localCode' => app()->environment('local') && ! $emailService->isConfigured(),
            ])
            : redirect()->route('admin.login');
    }

    public function verify(Request $request): RedirectResponse
    {
        $challenge = $request->session()->get('admin_challenge');
        $setup = (bool) ($challenge['setup'] ?? false);
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
            'password' => [Rule::requiredIf($setup), 'nullable', 'string', 'confirmed', 'min:12', 'max:72'],
        ]);

        if (! $challenge || $challenge['expires'] <= now()->timestamp || $challenge['attempts'] >= 5) {
            $request->session()->forget('admin_challenge');

            return redirect()->route('admin.login')->withErrors(['email' => 'That request expired. Sign in again for a new code.']);
        }

        if (! Hash::check((string) $validated['code'], $challenge['hash'])) {
            $request->session()->increment('admin_challenge.attempts');

            return back()->withErrors(['code' => 'That code did not match.']);
        }

        $request->session()->forget('admin_challenge');
        $user = User::query()->where('is_admin', true)->whereNull('suspended_at')->find($challenge['id']);

        if (! $user || (int) $user->admin_access_version !== ($challenge['version'] ?? null)
            || (bool) $user->admin_password_setup !== $setup) {
            return redirect()->route('admin.login');
        }

        if ($setup) {
            $updated = User::query()->whereKey($user->id)->where('is_admin', true)->whereNull('suspended_at')
                ->where('admin_access_version', $challenge['version'])->where('admin_password_setup', true)
                ->update(['password' => Hash::make($validated['password']), 'admin_password_setup' => false]);

            if ($updated !== 1) {
                return redirect()->route('admin.login');
            }

            DB::table('admin_audit_entries')->insert([
                'actor_id' => $user->id, 'account_id' => $user->id, 'action' => 'set administrator password', 'created_at' => now(),
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('admin_verified', [
            'id' => $user->id, 'version' => (int) $user->admin_access_version, 'expires' => now()->addHours(8)->timestamp,
        ]);
        DB::table('admin_audit_entries')->insert(['actor_id' => $user->id, 'account_id' => $user->id, 'action' => 'signed in', 'created_at' => now()]);

        return redirect()->route('admin.overview');
    }

    public function accounts(Request $request): View
    {
        $search = trim($request->validate(['search' => ['nullable', 'string', 'max:120']])['search'] ?? '');
        $caseCounts = LegalCase::query()->withoutGlobalScope('user_ownership')
            ->selectRaw('user_id, count(*) as total')->groupBy('user_id');
        $documentCounts = CaseDocument::query()->withoutGlobalScope('user_ownership')
            ->selectRaw('user_id, count(*) as total')->groupBy('user_id');
        $accounts = User::query()
            ->leftJoinSub($caseCounts, 'case_counts', 'case_counts.user_id', '=', 'users.id')
            ->leftJoinSub($documentCounts, 'document_counts', 'document_counts.user_id', '=', 'users.id')
            ->select('users.id', 'users.name', 'users.email', 'users.created_at', 'users.is_admin')
            ->selectRaw('coalesce(case_counts.total, 0) as cases_count, coalesce(document_counts.total, 0) as documents_count')
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $users) => $users
                ->whereLike('users.name', '%'.$search.'%')->orWhereLike('users.email', '%'.$search.'%')))
            ->latest('users.id')
            ->paginate(25)->withQueryString();

        return view('auth.admin-accounts', [
            'accounts' => $accounts,
            'search' => $search,
            'accountCount' => User::query()->count(),
            'caseCount' => LegalCase::query()->withoutGlobalScope('user_ownership')->count(),
            'documentCount' => CaseDocument::query()->withoutGlobalScope('user_ownership')->count(),
        ]);
    }

    public function account(Request $request, User $account): View
    {
        $filters = $request->validate([
            'review' => ['nullable', Rule::in(['court', 'date'])],
            'search' => ['nullable', 'string', 'max:120'],
        ]);
        $filter = $filters['review'] ?? null;
        $search = trim($filters['search'] ?? '');
        $team = User::query()->where('team_owner_id', $account->workspaceId())->orderBy('name')
            ->get(['id', 'name', 'team_role']);
        $owner = $account->isTeamMember() ? User::query()->find($account->workspaceId(), ['id', 'name']) : null;
        $workspaceCases = LegalCase::query()->withoutGlobalScope('user_ownership')
            ->where('user_id', $account->workspaceId());
        $unidentifiedCourt = fn (Builder $query) => $query->whereNull('court_level')
            ->orWhere(fn (Builder $district) => $district->where('court_level', 'district')->whereNull('district'));
        $workspaceRelation = fn (Builder $query) => $query->withoutGlobalScope('user_ownership')
            ->where('user_id', $account->workspaceId());
        $noUpcomingDate = fn (Builder $query) => $query->whereIn('case_status', ['pending', 'other'])
            ->whereDoesntHave('hearings', fn (Builder $hearings) => $workspaceRelation($hearings)
                ->where(fn (Builder $dates) => $dates->whereDate('hearing_date', '>=', today())
                    ->orWhereDate('next_hearing_date', '>=', today())))
            ->whereDoesntHave('causeListAppearances.courtDocument', fn (Builder $documents) => $documents
                ->whereDate('listing_date', '>=', today()));

        $totals = [
            'cases' => (clone $workspaceCases)->count(),
            'court' => (clone $workspaceCases)->where($unidentifiedCourt)->count(),
            'date' => (clone $workspaceCases)->where($noUpcomingDate)->count(),
        ];
        $cases = $workspaceCases
            ->when($search !== '', fn (Builder $query) => $query->search($search, 'all'))
            ->when($filter === 'court', fn (Builder $query) => $query->where($unidentifiedCourt))
            ->when($filter === 'date', fn (Builder $query) => $query->where($noUpcomingDate))
            ->select(['id', 'case_title', 'case_number', 'cnr_number', 'court_name', 'case_status', 'stage', 'updated_at'])
            ->withCount(['hearings' => $workspaceRelation, 'documents' => $workspaceRelation])
            ->withMin(['hearings as next_recorded_hearing' => fn (Builder $query) => $workspaceRelation($query)->whereDate('hearing_date', '>=', today())], 'hearing_date')
            ->withMin(['hearings as next_fixed_hearing' => fn (Builder $query) => $workspaceRelation($query)->whereDate('next_hearing_date', '>=', today())], 'next_hearing_date')
            ->withMin(['causeListAppearances as next_listed_hearing' => fn (Builder $query) => $query
                ->join('court_documents', 'court_documents.id', '=', 'cause_list_appearances.court_document_id')
                ->whereDate('court_documents.listing_date', '>=', today())], 'court_documents.listing_date')
            ->orderByDesc('updated_at')->orderByDesc('id')->paginate(25)->withQueryString();

        DB::table('admin_audit_entries')->insert(['actor_id' => $request->user()->id, 'account_id' => $account->id, 'action' => 'viewed account', 'created_at' => now()]);

        return view('auth.admin-account', compact('account', 'cases', 'totals', 'filter', 'search', 'team', 'owner'));
    }

    public function caseDetails(Request $request, User $account, int $caseId): View
    {
        $workspaceId = $account->workspaceId();
        $workspaceRows = fn (Builder $query) => $query->withoutGlobalScope('user_ownership')
            ->where('user_id', $workspaceId);
        $case = LegalCase::query()->withoutGlobalScope('user_ownership')
            ->where('user_id', $workspaceId)->findOrFail($caseId);
        $hearings = $case->hearings()->withoutGlobalScope('user_ownership')->where('user_id', $workspaceId)
            ->orderByDesc('hearing_date')->orderByDesc('id')->paginate(15, pageName: 'hearings');
        $documents = $workspaceRows($case->documents()->getQuery())->latest('id')
            ->paginate(15, ['id', 'original_name', 'mime_type', 'size', 'created_at'], 'documents');
        $listings = $case->causeListAppearances()->with('courtDocument:id,listing_date,document_label')
            ->latest('id')->paginate(15, pageName: 'listings');

        DB::table('admin_audit_entries')->insert(['actor_id' => $request->user()->id, 'account_id' => $account->id, 'case_id' => $case->id, 'action' => 'viewed case', 'created_at' => now()]);

        return view('auth.admin-case', compact('account', 'case', 'hearings', 'documents', 'listings'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
