<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $case->case_title ?: 'Case Details' }} | VakilNeeti</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-slate-100 text-slate-800">

<div class="min-h-screen md:flex">

    <aside class="w-full bg-slate-950 p-5 text-white md:min-h-screen md:w-64">
        <h1 class="mb-8 text-2xl font-bold">VakilNeeti</h1>

        <nav class="space-y-2">
            <a href="{{ route('dashboard') }}" class="block rounded-lg px-3 py-2 hover:bg-slate-800">Dashboard</a>
            <a href="{{ route('clients.index') }}" class="block rounded-lg px-3 py-2 hover:bg-slate-800">Clients</a>
            <a href="{{ route('cases.index') }}" class="block rounded-lg bg-slate-800 px-3 py-2">My Case Diary</a>
            <a href="{{ route('court-catalogue.index') }}" class="block rounded-lg px-3 py-2 hover:bg-slate-800">Court Catalogue</a>
            <a href="{{ route('invoices.index') }}" class="block rounded-lg px-3 py-2 hover:bg-slate-800">Invoices</a>
            <a href="{{ route('payments.index') }}" class="block rounded-lg px-3 py-2 hover:bg-slate-800">Payments</a>
            <a href="{{ route('tasks.index') }}" class="block rounded-lg px-3 py-2 hover:bg-slate-800">Tasks & Reminders</a>
        </nav>
    </aside>

    <main class="min-w-0 flex-1 space-y-6 p-5 md:p-8">

        <div>
            <a href="{{ route('cases.index') }}" class="text-sm font-medium text-blue-700 hover:underline">
                &larr; Back to My Case Diary
            </a>

            <div class="mt-4 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium uppercase tracking-wide text-slate-500">Case Details</p>
                    <h2 class="mt-1 text-2xl font-bold text-slate-900">
                        {{ $case->case_title ?: 'Untitled Case' }}
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $case->case_number ?: 'Case number not available' }}
                    </p>
                </div>

                <span class="rounded-full bg-blue-100 px-4 py-2 text-sm font-semibold text-blue-800">
                    {{ ucfirst($case->case_status ?: 'Status unavailable') }}
                </span>
            </div>
        </div>

        <!-- Next Hearing -->
        @php
            $nextHearing = $case->hearings
                ->filter(function ($hearing) {
                    return $hearing->next_hearing_date
                        && $hearing->next_hearing_date->greaterThanOrEqualTo(now()->startOfDay());
                })
                ->sortBy('next_hearing_date')
                ->first();
        @endphp

        <section class="rounded-xl border border-blue-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Next Hearing</h3>
                    <p class="mt-1 text-sm text-slate-500">Upcoming date recorded in your case diary</p>
                </div>

                @if($nextHearing)
                    <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-800">
                        Upcoming
                    </span>
                @endif
            </div>

            @if($nextHearing)
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <p class="text-xs font-medium uppercase text-slate-500">Date</p>
                        <p class="mt-1 text-2xl font-bold text-blue-800">
                            {{ $nextHearing->next_hearing_date->format('d M Y') }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase text-slate-500">Courtroom</p>
                        <p class="mt-1 font-semibold">
                            {{ $nextHearing->court_room ?: 'Not available' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase text-slate-500">Judge</p>
                        <p class="mt-1 font-semibold">
                            {{ $nextHearing->judge_name ?: 'Not available' }}
                        </p>
                    </div>
                </div>
            @else
                <p class="mt-4 rounded-lg bg-slate-50 p-4 text-sm text-slate-500">
                    No upcoming hearing date is currently recorded for this case.
                </p>
            @endif
        </section>

        <!-- Case Information -->
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Case Information</h3>

            <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">

                <div>
                    <p class="text-xs font-medium uppercase text-slate-500">CNR Number</p>
                    <p class="mt-1 font-medium">{{ $case->cnr_number ?: 'Not available' }}</p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase text-slate-500">Case Number</p>
                    <p class="mt-1 font-medium">{{ $case->case_number ?: 'Not available' }}</p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase text-slate-500">Case Type</p>
                    <p class="mt-1 font-medium">{{ $case->case_type ?: 'Not available' }}</p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase text-slate-500">Court</p>
                    <p class="mt-1 font-medium">{{ $case->court_name ?: 'Not available' }}</p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase text-slate-500">Court Complex / Establishment</p>
                    <p class="mt-1 font-medium">{{ $case->court_complex ?: 'Not available' }}</p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase text-slate-500">Judge</p>
                    <p class="mt-1 font-medium">{{ $case->judge_name ?: 'Not available' }}</p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase text-slate-500">Filing Number</p>
                    <p class="mt-1 font-medium">{{ $case->filing_number ?: 'Not available' }}</p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase text-slate-500">Filing Date</p>
                    <p class="mt-1 font-medium">
                        {{ $case->filing_date?->format('d M Y') ?: 'Not available' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase text-slate-500">Record Source</p>
                    <p class="mt-1 font-medium">{{ ucfirst($case->source_type ?: 'Not specified') }}</p>
                </div>

            </div>

            @if($case->case_description)
                <div class="mt-5 border-t border-slate-100 pt-4">
                    <p class="text-xs font-medium uppercase text-slate-500">Description</p>
                    <p class="mt-2 whitespace-pre-line text-sm">{{ $case->case_description }}</p>
                </div>
            @endif

            @if($case->source_reference)
                <div class="mt-4 border-t border-slate-100 pt-4">
                    <p class="text-xs font-medium uppercase text-slate-500">Source Reference</p>
                    @if(\Illuminate\Support\Str::startsWith($case->source_reference, ['https://', 'http://']))
                        <a href="{{ $case->source_reference }}" target="_blank" rel="noopener noreferrer"
                           class="mt-1 inline-block break-all text-sm text-blue-700 hover:underline">
                            Open source record
                        </a>
                    @else
                        <p class="mt-1 break-all text-sm">{{ $case->source_reference }}</p>
                    @endif
                </div>
            @endif
        </section>

        <!-- Parties -->
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Parties</h3>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="p-3">Party Name</th>
                            <th class="p-3">Party Type</th>
                            <th class="p-3">Client</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse($case->parties as $party)
                            <tr>
                                <td class="p-3 font-medium text-slate-900">
                                    {{ $party->party_name }}
                                </td>
                                <td class="p-3">
                                    {{ $party->party_type ?: 'Not specified' }}
                                </td>
                                <td class="p-3">
                                    {{ $party->is_client ? 'Yes' : 'No' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="p-6 text-center text-slate-500">
                                    No party records available.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Case Timeline / Hearing History -->
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Case Timeline & Hearing History</h3>
            <p class="mt-1 text-sm text-slate-500">
                Hearing events and proceedings currently recorded in VakilNeeti.
            </p>

            <div class="mt-5 space-y-4">
                @forelse($case->hearings as $hearing)
                    <div class="border-l-2 border-blue-300 pl-4">
                        <div class="rounded-lg border border-slate-200 p-4">

                            <div class="flex flex-wrap justify-between gap-2">
                                <p class="font-semibold text-slate-900">
                                    {{ $hearing->hearing_date?->format('d M Y') ?: 'Hearing date not available' }}
                                </p>
                                <span class="text-xs text-slate-500">
                                    {{ ucfirst($hearing->source_type ?: 'Manual') }} record
                                </span>
                            </div>

                            <div class="mt-3 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                                <div>
                                    <span class="text-slate-500">Time:</span>
                                    {{ $hearing->hearing_time
                                        ? \Carbon\Carbon::parse($hearing->hearing_time)->format('h:i A')
                                        : 'Not available' }}
                                </div>

                                <div>
                                    <span class="text-slate-500">Courtroom:</span>
                                    {{ $hearing->court_room ?: 'Not available' }}
                                </div>

                                <div>
                                    <span class="text-slate-500">Judge:</span>
                                    {{ $hearing->judge_name ?: 'Not available' }}
                                </div>

                                <div>
                                    <span class="text-slate-500">Stage:</span>
                                    {{ $hearing->hearing_stage ?: 'Not available' }}
                                </div>

                                <div>
                                    <span class="text-slate-500">Next Hearing:</span>
                                    {{ $hearing->next_hearing_date?->format('d M Y') ?: 'Not available' }}
                                </div>
                            </div>

                            @if($hearing->proceedings)
                                <div class="mt-4 border-t border-slate-100 pt-3">
                                    <p class="text-xs font-medium uppercase text-slate-500">Proceedings</p>
                                    <p class="mt-1 whitespace-pre-line text-sm">{{ $hearing->proceedings }}</p>
                                </div>
                            @endif

                            @if($hearing->next_action)
                                <div class="mt-3">
                                    <p class="text-xs font-medium uppercase text-slate-500">Next Action</p>
                                    <p class="mt-1 whitespace-pre-line text-sm">{{ $hearing->next_action }}</p>
                                </div>
                            @endif

                        </div>
                    </div>
                @empty
                    <p class="rounded-lg bg-slate-50 p-5 text-center text-sm text-slate-500">
                        No hearing history is currently available for this case.
                    </p>
                @endforelse
            </div>
        </section>

        <!-- Orders and Documents -->
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Orders and Court Documents</h3>

            <p class="mt-2 text-sm text-slate-500">
                Official orders, judgments and court documents will appear here
                when they are retrieved from an authorised court source or added to this case.
                No live document connection is currently represented by this section.
            </p>
        </section>

        <p class="text-xs text-slate-400">
            Case information displayed here is limited to records currently stored in VakilNeeti.
            Verify official case status and hearing information with the relevant court portal.
        </p>

    </main>
</div>

</body>
</html>