<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <title>{{ $case->case_title ?: 'Case Details' }} | VakilNeeti</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-slate-100 text-slate-800">

<div class="min-h-screen md:flex">

    <aside class="w-full bg-slate-950 p-5 text-white md:min-h-screen md:w-64">
        <div class="mb-8"><img src="{{ asset('images/vakilneeti-logo.png') }}" alt="VakilNeeti" class="h-12 w-auto max-w-[220px] rounded bg-white px-2 py-1 object-contain"></div>

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

        @if(session('success'))
            <div role="status" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <nav aria-label="Case workspace" class="flex flex-wrap gap-2 rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
            <a href="#case-notes" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Notes</a>
            <a href="#case-appointments" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Appointments</a>
            <a href="#case-documents" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Documents</a>
            <a href="#case-expenses" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Expenses</a>
            <a href="#case-time" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Time entries</a>
        </nav>

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

        <section id="case-notes" class="scroll-mt-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Case Notes</h3>
                    <p class="mt-1 text-sm text-slate-500">Keep private working notes with this case.</p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $case->notes->count() }} notes</span>
            </div>

            <form method="POST" action="{{ route('cases.notes.store', $case) }}" class="mt-4 grid gap-3">
                @csrf
                <label class="text-sm font-medium text-slate-700">
                    Title <span class="font-normal text-slate-400">(optional)</span>
                    <input name="note_title" value="{{ old('note_title') }}" maxlength="160" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('note_title') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm font-medium text-slate-700">
                    Note
                    <textarea name="note_body" rows="3" required maxlength="10000" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('note_body') }}</textarea>
                    @error('note_body') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <div><button class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Add note</button></div>
            </form>

            <div class="mt-5 space-y-3">
                @forelse($case->notes as $note)
                    <article class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <div class="flex flex-wrap justify-between gap-2">
                            <h4 class="font-semibold text-slate-900">{{ $note->title ?: 'Case note' }}</h4>
                            <time class="text-xs text-slate-500">{{ $note->created_at->format('d M Y, h:i A') }}</time>
                        </div>
                        <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $note->body }}</p>
                    </article>
                @empty
                    <p class="rounded-lg bg-slate-50 p-4 text-sm text-slate-500">No notes have been added to this case yet.</p>
                @endforelse
            </div>
        </section>

        <section id="case-appointments" class="scroll-mt-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Appointments</h3>
            <p class="mt-1 text-sm text-slate-500">Schedule client meetings, calls, and other case-related appointments.</p>

            <form method="POST" action="{{ route('cases.appointments.store', $case) }}" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                @csrf
                <label class="text-sm font-medium text-slate-700 sm:col-span-2">
                    Appointment title
                    <input name="appointment_title" value="{{ old('appointment_title') }}" required maxlength="160" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('appointment_title') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm font-medium text-slate-700">
                    Date and time
                    <input type="datetime-local" name="appointment_starts_at" value="{{ old('appointment_starts_at') }}" required class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('appointment_starts_at') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm font-medium text-slate-700">
                    Location
                    <input name="appointment_location" value="{{ old('appointment_location') }}" maxlength="255" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('appointment_location') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm font-medium text-slate-700 sm:col-span-2">
                    Notes <span class="font-normal text-slate-400">(optional)</span>
                    <textarea name="appointment_notes" rows="2" maxlength="5000" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('appointment_notes') }}</textarea>
                    @error('appointment_notes') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <div class="sm:col-span-2"><button class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Schedule appointment</button></div>
            </form>

            <div class="mt-5 divide-y divide-slate-100">
                @forelse($case->appointments as $appointment)
                    <article class="flex flex-wrap justify-between gap-3 py-3">
                        <div>
                            <h4 class="font-semibold text-slate-900">{{ $appointment->title }}</h4>
                            <p class="mt-1 text-sm text-slate-600">{{ $appointment->starts_at->format('d M Y, h:i A') }}@if($appointment->location) Â· {{ $appointment->location }}@endif</p>
                            @if($appointment->notes)<p class="mt-1 whitespace-pre-line text-sm text-slate-500">{{ $appointment->notes }}</p>@endif
                        </div>
                    </article>
                @empty
                    <p class="py-4 text-sm text-slate-500">No appointments are scheduled for this case.</p>
                @endforelse
            </div>
        </section>

        <section id="case-documents" class="scroll-mt-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Case Documents</h3>
            <p class="mt-1 text-sm text-slate-500">Upload case files (PDF, Office documents, JPG, or PNG; up to 20 MB). Files are stored privately.</p>

            <form method="POST" action="{{ route('cases.documents.store', $case) }}" enctype="multipart/form-data" class="mt-4 flex flex-wrap items-end gap-3">
                @csrf
                <label class="min-w-0 flex-1 text-sm font-medium text-slate-700">
                    Choose a file
                    <input type="file" name="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" required class="mt-1 block w-full rounded-lg border border-slate-300 text-sm file:mr-3 file:border-0 file:bg-slate-100 file:px-3 file:py-2">
                    @error('file') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <button class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Upload document</button>
            </form>

            <div class="mt-5 divide-y divide-slate-100">
                @forelse($case->documents as $document)
                    <div class="flex flex-wrap items-center justify-between gap-3 py-3">
                        <div class="min-w-0">
                            <p class="truncate font-medium text-slate-900">{{ $document->original_name }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ number_format($document->size / 1024, 0) }} KB Â· {{ $document->created_at->format('d M Y') }}</p>
                        </div>
                        <a href="{{ route('cases.documents.download', [$case, $document]) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-blue-700 hover:bg-slate-50">Download</a>
                    </div>
                @empty
                    <p class="py-4 text-sm text-slate-500">No files have been uploaded to this case.</p>
                @endforelse
            </div>
        </section>

        <section id="case-expenses" class="scroll-mt-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Case Expenses</h3>
                    <p class="mt-1 text-sm text-slate-500">Track out-of-pocket costs against this case.</p>
                </div>
                <p class="rounded-full bg-amber-50 px-3 py-1 text-sm font-semibold text-amber-800">
                    Total: â‚¹{{ number_format($case->expenses->sum('amount'), 2) }}
                </p>
            </div>

            <form method="POST" action="{{ route('cases.expenses.store', $case) }}" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                @csrf
                <label class="text-sm font-medium text-slate-700 sm:col-span-3">
                    Expense description
                    <input name="expense_description" value="{{ old('expense_description') }}" required maxlength="255" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('expense_description') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm font-medium text-slate-700">
                    Amount (â‚¹)
                    <input type="number" name="expense_amount" value="{{ old('expense_amount') }}" min="0.01" step="0.01" required class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('expense_amount') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm font-medium text-slate-700">
                    Date
                    <input type="date" name="expense_date" value="{{ old('expense_date', now()->toDateString()) }}" required class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('expense_date') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <div class="flex items-end"><button class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Add expense</button></div>
            </form>

            <div class="mt-5 divide-y divide-slate-100">
                @forelse($case->expenses as $expense)
                    <div class="flex flex-wrap justify-between gap-3 py-3">
                        <div><p class="font-medium text-slate-900">{{ $expense->description }}</p><p class="mt-1 text-xs text-slate-500">{{ $expense->expense_date->format('d M Y') }}</p></div>
                        <p class="font-semibold text-slate-800">â‚¹{{ number_format((float) $expense->amount, 2) }}</p>
                    </div>
                @empty
                    <p class="py-4 text-sm text-slate-500">No expenses have been recorded for this case.</p>
                @endforelse
            </div>
        </section>

        <section id="case-time" class="scroll-mt-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Time Entries</h3>
                    <p class="mt-1 text-sm text-slate-500">Record work completed and optional hourly rates.</p>
                </div>
                <p class="rounded-full bg-indigo-50 px-3 py-1 text-sm font-semibold text-indigo-800">
                    {{ intdiv($case->timeEntries->sum(fn ($entry) => ($entry->hours * 60) + $entry->minutes), 60) }}h
                    {{ $case->timeEntries->sum(fn ($entry) => ($entry->hours * 60) + $entry->minutes) % 60 }}m tracked
                </p>
            </div>

            <form method="POST" action="{{ route('cases.time-entries.store', $case) }}" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @csrf
                <label class="text-sm font-medium text-slate-700 sm:col-span-2 lg:col-span-4">
                    Work description
                    <input name="time_description" value="{{ old('time_description') }}" required maxlength="255" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('time_description') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm font-medium text-slate-700">
                    Date
                    <input type="date" name="entry_date" value="{{ old('entry_date', now()->toDateString()) }}" required class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('entry_date') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm font-medium text-slate-700">
                    Hours
                    <input type="number" name="time_hours" value="{{ old('time_hours', 0) }}" min="0" max="23" step="1" required class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('time_hours') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm font-medium text-slate-700">
                    Minutes
                    <input type="number" name="time_minutes" value="{{ old('time_minutes', 0) }}" min="0" max="59" step="1" required class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('time_minutes') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm font-medium text-slate-700">
                    Hourly rate (â‚¹, optional)
                    <input type="number" name="hourly_rate" value="{{ old('hourly_rate') }}" min="0" step="0.01" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('hourly_rate') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <div class="sm:col-span-2 lg:col-span-4"><button class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Add time entry</button></div>
            </form>

            <div class="mt-5 divide-y divide-slate-100">
                @forelse($case->timeEntries as $entry)
                    <div class="flex flex-wrap justify-between gap-3 py-3">
                        <div><p class="font-medium text-slate-900">{{ $entry->description }}</p><p class="mt-1 text-xs text-slate-500">{{ $entry->entry_date->format('d M Y') }}</p></div>
                        <p class="font-semibold text-slate-800">
                            {{ $entry->hours }}h {{ $entry->minutes }}m
                            @if($entry->hourly_rate !== null)<span class="ml-2 text-xs font-normal text-slate-500">â‚¹{{ number_format((float) $entry->hourly_rate, 2) }}/hr</span>@endif
                        </p>
                    </div>
                @empty
                    <p class="py-4 text-sm text-slate-500">No time has been recorded for this case.</p>
                @endforelse
            </div>
        </section>

        <!-- Orders and Documents -->
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Official Orders and Court Documents</h3>

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

