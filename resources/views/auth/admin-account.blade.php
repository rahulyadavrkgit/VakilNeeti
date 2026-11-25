@extends('layouts.app')
@section('title', 'Account diagnostics')
@section('page-heading', 'Account diagnostics')

@section('content')
    <div class="mx-auto max-w-5xl space-y-4">
        <a href="{{ route('admin.accounts') }}" class="inline-flex min-h-11 items-center text-sm font-semibold text-teal-700 underline">All accounts</a>
        <x-page-header :title="$account->name" :subtitle="$account->email" class="break-all" />
        <p class="text-sm text-slate-600">
            {{ $account->is_admin ? 'Administrator' : (\App\Models\User::TEAM_ROLES[$account->team_role] ?? 'Workspace owner') }}
            &middot; Joined {{ $account->created_at->format('d M Y') }}
            @if($account->isTeamMember()) &middot; Shared workspace @endif
        </p>

        <x-panel>
            <dl class="grid gap-2 text-sm sm:grid-cols-2">
                <div><dt class="font-semibold">Email verification</dt><dd>{{ $account->email_verified_at ? 'Verified' : 'Pending' }}</dd></div>
                <div><dt class="font-semibold">Mobile</dt><dd>{{ $account->mobile ?: 'Not supplied' }} @if($account->mobile_verified_at)&middot; Verified @endif</dd></div>
                @if($owner)
                    <div><dt class="font-semibold">Workspace owner</dt><dd><a class="underline" href="{{ route('admin.accounts.show', $owner) }}">{{ $owner->name }}</a></dd></div>
                @endif
                <div><dt class="font-semibold">Team members</dt><dd>{{ $team->count() }}</dd></div>
            </dl>
            @if($team->isNotEmpty())
                <ul class="mt-2 flex flex-wrap gap-x-4 gap-y-2 text-sm">
                    @foreach($team as $member)
                        <li><a class="inline-flex min-h-11 items-center underline" href="{{ route('admin.accounts.show', $member) }}">{{ $member->name }} ({{ \App\Models\User::TEAM_ROLES[$member->team_role] ?? 'Member' }})</a></li>
                    @endforeach
                </ul>
            @endif
        </x-panel>

        @unless(auth()->id() === $account->id)
            <x-panel>
                <h2 class="font-bold">Account access</h2>
                <p class="mt-1 text-sm text-slate-600">{{ $account->suspended_at ? 'Suspended' : 'Active' }}</p>
                @if(session('success'))<p role="status" class="my-2 text-sm text-teal-700">{{ session('success') }}</p>@endif
                <form method="POST" action="{{ route('admin.accounts.access', $account) }}" class="mt-3 grid gap-3 sm:grid-cols-2">
                    @csrf
                    <label class="text-sm font-semibold">Action
                        <select name="action" required class="mt-1 block min-h-11 w-full rounded-lg border-slate-300">
                            <option value="">Choose an action</option>
                            @if($account->suspended_at)
                                <option value="restore">Restore account access</option>
                            @else
                                <option value="suspend">Suspend account</option>
                            @endif
                            @if($account->is_admin)
                                <option value="remove-admin">Remove administrator access</option>
                            @elseif(! $account->suspended_at && ! $account->isTeamMember())
                                <option value="grant-admin">Grant administrator access</option>
                            @endif
                            @if($account->isTeamMember())
                                @foreach(\App\Models\User::TEAM_ROLES as $role => $label)
                                    @if($account->team_role !== $role)<option value="role-{{ $role }}">Set team role to {{ $label }}</option>@endif
                                @endforeach
                            @endif
                        </select>
                    </label>
                    <label class="text-sm font-semibold">Reason
                        <input name="reason" value="{{ old('reason') }}" required minlength="8" maxlength="500" class="mt-1 block min-h-11 w-full rounded-lg border-slate-300">
                    </label>
                    @foreach($errors->all() as $error)<p role="alert" class="text-sm text-red-700 sm:col-span-2">{{ $error }}</p>@endforeach
                    <button class="min-h-11 justify-self-start rounded-lg bg-teal-700 px-4 text-sm font-semibold text-white">Apply access change</button>
                </form>
            </x-panel>
        @endunless

        <x-panel>
            <h2 class="font-bold">Workspace review</h2>
            <nav aria-label="Case diagnostics filters" class="mt-2 flex flex-wrap gap-2">
                @foreach(['' => ['All cases', 'cases'], 'court' => ['Missing court details', 'court'], 'date' => ['Open cases without an upcoming date', 'date']] as $key => [$label, $count])
                    <a href="{{ route('admin.accounts.show', ['account' => $account, 'review' => $key ?: null, 'search' => $search ?: null]) }}"
                       @if(($filter ?? '') === $key) aria-current="page" @endif
                       class="inline-flex min-h-11 items-center rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold aria-[current=page]:bg-teal-700 aria-[current=page]:text-white">
                        {{ $label }} ({{ $totals[$count] }})
                    </a>
                @endforeach
            </nav>
            @if($totals['date'] || $totals['court'])
                <p class="mt-3 text-sm text-slate-700">
                    <strong>Suggested first check</strong>
                    {{ $totals['date'] >= $totals['court'] ? 'Review upcoming dates. Check the latest court listing before adding a date or closing a case.' : 'Review court details. Check the CNR or select the correct court and district.' }}
                </p>
            @endif
        </x-panel>

        <form method="GET" role="search" class="flex flex-wrap items-end gap-2">
            @if($filter)<input type="hidden" name="review" value="{{ $filter }}">@endif
            <label class="min-w-0 flex-1 text-sm font-medium">Find a case
                <input type="search" name="search" value="{{ $search }}" maxlength="120" placeholder="Title, case number or CNR" class="mt-1 block min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3">
            </label>
            <button class="min-h-11 rounded-lg bg-teal-700 px-4 text-sm font-semibold text-white">Search</button>
        </form>
        <x-panel class="divide-y divide-slate-200">
            @forelse($cases as $case)
                @php $nextDate = collect([$case->next_recorded_hearing, $case->next_fixed_hearing, $case->next_listed_hearing])->filter()->sort()->first(); @endphp
                <article class="flex flex-wrap items-start justify-between gap-2 p-4">
                    <div class="min-w-0 break-words">
                        <h2 class="font-semibold"><a class="inline-flex min-h-11 items-center underline underline-offset-2" href="{{ route('admin.accounts.cases.show', [$account, $case]) }}">{{ $case->case_title }}</a></h2>
                        <p class="text-sm text-slate-700">{{ $case->case_number ?: 'No case number' }} &middot; {{ $case->court_name ?: 'Court missing' }}</p>
                        @if($case->cnr_number)<p class="text-sm text-slate-600">CNR {{ $case->cnr_number }}</p>@endif
                    </div>
                    <div class="text-sm text-slate-600">
                        <p>{{ ucfirst($case->case_status ?? 'Unknown') }} @if($case->stage)&middot; {{ $case->stage }} @endif &middot; {{ $case->hearings_count }} hearings &middot; {{ $case->documents_count }} documents</p>
                        <p>Next hearing {{ $nextDate ? \Illuminate\Support\Carbon::parse($nextDate)->format('d M Y') : 'not recorded' }}</p>
                        <p>Updated {{ $case->updated_at?->format('d M Y') ?? 'Unknown' }}</p>
                    </div>
                </article>
            @empty
                <p class="p-4 text-sm text-slate-600">No cases match this view.</p>
            @endforelse
        </x-panel>
        {{ $cases->links() }}
    </div>
@endsection
