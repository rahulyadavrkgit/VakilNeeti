@extends('layouts.app')

@section('title', 'Client Profile')
@section('page-heading', 'Client Profile')

@section('content')
<div class="space-y-6">

    @if(session('success'))
        <div class="rounded-lg bg-green-50 p-4 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-lg bg-red-50 p-4 text-red-800">
            {{ session('error') }}
        </div>
    @endif

    {{-- CLIENT HEADER --}}
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-5">

            <div>

                <h2 class="mt-3 text-2xl font-bold text-slate-900">
                    {{ $client->displayName() }}
                </h2>
                @if($client->company)
                    <p class="mt-1 text-sm text-slate-600">Works at <a href="{{ route('clients.show', $client->company) }}" class="font-semibold text-[#16776e] hover:underline">{{ $client->company->displayName() }}</a></p>
                @endif

                <p class="mt-1 text-sm text-slate-500">
                    Client ID: {{ $client->client_code ?? $client->id }}
                    &nbsp;|&nbsp;
                    {{ ucfirst($client->client_type) }}
                </p>

                <span class="mt-3 inline-block rounded-full px-3 py-1 text-xs font-semibold
                    {{ $client->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600' }}">
                    {{ ucfirst($client->status) }}
                </span>

                <div class="mt-4 space-y-1 text-sm text-slate-600">
                    @if($client->mobile)
                        <p>Mobile: {{ $client->mobile }}</p>
                    @endif
                    @if($client->email)
                        <p>Email: {{ $client->email }}</p>
                    @endif
                    @if($client->city || $client->state)
                        <p>Location: {{ collect([$client->city, $client->state])->filter()->implode(', ') }}</p>
                    @endif
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('invoices.create', ['client_id' => $client->id]) }}"
                   class="rounded-lg bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-700">
                    + Create Invoice
                </a>

                <a href="{{ route('payments.create', ['client_id' => $client->id]) }}"
                   class="rounded-lg bg-emerald-600 px-4 py-3 text-sm font-semibold text-white hover:bg-emerald-700">
                    + Record Payment
                </a>

                <a href="{{ route('clients.activities.create', $client) }}"
                   class="rounded-lg bg-blue-600 px-4 py-3 text-sm font-semibold text-white hover:bg-blue-700">
                    + Add Activity
                </a>

                <a href="{{ route('clients.edit', $client) }}"
                   class="rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Edit Client
                </a>
            </div>
        </div>
    </div>

    {{-- PROFILE NAVIGATION --}}
    <div class="flex flex-wrap gap-2 border-b border-slate-200 pb-3 text-sm">
        @foreach([
            'overview' => 'Overview',
            'cases' => 'Cases',
            'hearings' => 'Hearings',
            'tasks' => 'Tasks',
            'invoices' => 'Invoices',
            'payments' => 'Payments',
            'activities' => 'Activities'
        ] as $id => $label)
            <a href="#{{ $id }}"
               class="rounded-lg bg-white px-4 py-2 font-medium text-slate-700 border border-slate-200 hover:bg-slate-100">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- OVERVIEW --}}
    <section id="overview" class="space-y-4">
        <h3 class="text-lg font-bold text-slate-900">Client Overview</h3>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

            <div class="rounded-xl border bg-white p-5">
                <p class="text-sm text-slate-500">Connected Cases</p>
                <p class="mt-2 text-2xl font-bold">{{ $cases->count() }}</p>
            </div>

            <div class="rounded-xl border bg-white p-5">
                <p class="text-sm text-slate-500">Tasks</p>
                <p class="mt-2 text-2xl font-bold">{{ $tasks->whereNotIn('status', ['completed', 'cancelled'])->count() }}</p>
            </div>

            <div class="rounded-xl border bg-white p-5">
                <p class="text-sm text-slate-500">Total Invoiced</p>
                <p class="mt-2 text-2xl font-bold">@money($totalInvoiced)</p>
            </div>

            <div class="rounded-xl border bg-white p-5">
                <p class="text-sm text-slate-500">Outstanding</p>
                <p class="mt-2 text-2xl font-bold text-red-600">@money($totalOutstanding)</p>
            </div>

        </div>

        <div class="rounded-xl border bg-white p-5">
            <h4 class="font-semibold">Client Information</h4>
            <div class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                <p><span class="text-slate-500">Client type:</span> {{ $client->isCompany() ? ucfirst($client->client_type) : 'Person' }}</p>
                <p><span class="text-slate-500">Mobile:</span> {{ $client->mobile ?: 'Not provided' }}</p>
                <p><span class="text-slate-500">Email:</span> {{ $client->email ?: 'Not provided' }}</p>
                @if($client->phone)<p><span class="text-slate-500">Phone:</span> {{ $client->phone }}</p>@endif
                @if($client->website)<p><span class="text-slate-500">Website:</span> <a href="{{ $client->website }}" target="_blank" rel="noopener noreferrer" class="text-[#16776e] hover:underline">{{ $client->website }}</a></p>@endif
                @unless($client->isCompany())<p><span class="text-slate-500">Occupation:</span> {{ $client->occupation ?: 'Not provided' }}</p>@endunless
                <p class="sm:col-span-2"><span class="text-slate-500">Address:</span> {{ collect([$client->address, $client->locality, $client->city, $client->district, $client->state, $client->pin_code])->filter()->implode(', ') ?: 'Not provided' }}</p>
                <p><span class="text-slate-500">Referred by:</span> {{ $client->referred_by ?: 'Not provided' }}</p>
            </div>

            @if($client->isCompany())
                <div class="mt-5 border-t border-slate-100 pt-4">
                    <div class="flex items-center justify-between gap-2">
                        <h5 class="text-sm font-semibold text-slate-800">People at {{ $client->displayName() }}</h5>
                        <a href="{{ route('clients.create') }}" class="text-xs font-semibold text-[#16776e] hover:underline">+ Add person</a>
                    </div>
                    <ul class="mt-2 divide-y divide-slate-100 text-sm">
                        @forelse($client->people as $person)
                            <li class="flex items-center justify-between gap-2 py-2">
                                <a href="{{ route('clients.show', $person) }}" class="font-medium text-slate-900 hover:text-[#16776e]">{{ $person->displayName() }}</a>
                                <span class="text-xs text-slate-500">{{ collect([$person->mobile, $person->email])->filter()->implode(' · ') }}</span>
                            </li>
                        @empty
                            <li class="py-2 text-slate-500">No people linked yet. Choose this company under "Works at" when adding a person.</li>
                        @endforelse
                    </ul>
                </div>
            @endif
        </div>
    </section>

    {{-- CASES --}}
    <section id="cases" class="rounded-xl border bg-white p-5">
        <div class="flex items-center justify-between gap-3">
            <h3 class="text-lg font-bold">Connected Cases</h3>
            <a href="{{ route('cases.index') }}" class="text-sm text-blue-600 hover:underline">All Cases</a>
        </div>

        <div class="mt-4 space-y-3">
            @forelse($cases as $case)
                <div class="rounded-lg border border-slate-200 p-4">
                    <a href="{{ route('cases.show', $case) }}"
                       class="font-semibold text-blue-700 hover:underline">
                        {{ $case->case_title }}
                    </a>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $case->case_number ?: 'Case number unavailable' }}
                        @if($case->cnr_number) | CNR: {{ $case->cnr_number }} @endif
                    </p>
                    <p class="mt-1 text-sm text-slate-600">
                        {{ $case->court_name ?: 'Court not specified' }}
                        | Status: {{ ucfirst(str_replace('_', ' ', $case->case_status ?? 'Unknown')) }}
                    </p>
                </div>
            @empty
                <p class="text-sm text-slate-500">No cases are connected to this client yet.</p>
            @endforelse
        </div>
    </section>

    {{-- HEARINGS --}}
    <section id="hearings" class="rounded-xl border bg-white p-5">
        <div class="flex items-center justify-between gap-3">
            <h3 class="text-lg font-bold">Hearing History & Dates</h3>
            <a href="{{ route('cases.calendar') }}" class="text-sm text-blue-600 hover:underline">Open Calendar</a>
        </div>

        <div class="mt-4 space-y-3">
            @forelse($hearingItems as $item)
                <div class="rounded-lg border p-4">
                    <p class="font-semibold">{{ $item->case->case_title }}</p>
                    <p class="mt-1 text-sm text-slate-600">
                        Hearing: {{ $item->hearing->hearing_date?->format('d-m-Y') ?? 'Not recorded' }}
                        @if($item->hearing->hearing_time)
                            | {{ $item->hearing->hearing_time }}
                        @endif
                    </p>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $item->hearing->hearing_stage ?: 'Stage not specified' }}
                    </p>
                    @if($item->hearing->proceedings)
                        <p class="mt-2 text-sm text-slate-600">{{ $item->hearing->proceedings }}</p>
                    @endif
                    @if($item->hearing->next_hearing_date)
                        <p class="mt-2 text-sm font-medium text-blue-700">
                            Next hearing: {{ $item->hearing->next_hearing_date->format('d-m-Y') }}
                        </p>
                    @endif
                </div>
            @empty
                <p class="text-sm text-slate-500">No hearing records are available for this client's connected cases.</p>
            @endforelse
        </div>
    </section>

    {{-- TASKS --}}
    <section id="tasks" class="rounded-xl border bg-white p-5">
        <div class="flex items-center justify-between gap-3">
            <h3 class="text-lg font-bold">Client Tasks</h3>
            <a href="{{ route('tasks.create', ['client_id' => $client->id]) }}"
               class="text-sm font-semibold text-blue-600 hover:underline">
                + Create Task
            </a>
        </div>

        <div class="mt-4 space-y-3">
            @forelse($tasks as $task)
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border p-4">
                    <div>
                        <a href="{{ route('tasks.show', $task) }}" class="font-semibold text-blue-700 hover:underline">
                            {{ $task->title }}
                        </a>
                        <p class="mt-1 text-sm text-slate-500">
                            Due: {{ $task->due_at?->format('d-m-Y H:i') ?? 'No due date' }}
                        </p>
                        @if($task->legalCase)
                            <p class="mt-1 text-sm text-blue-700">
                                Case: {{ $task->legalCase->case_title }}
                                @if($task->legalCase->case_number)
                                    | {{ $task->legalCase->case_number }}
                                @endif
                            </p>
                        @endif
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold">
                        {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                    </span>
                </div>
            @empty
                <p class="text-sm text-slate-500">No tasks are linked to this client.</p>
            @endforelse
        </div>
    </section>

    {{-- INVOICES --}}
    <section id="invoices" class="rounded-xl border bg-white p-5">
        <div class="flex items-center justify-between gap-3">
            <h3 class="text-lg font-bold">Client Invoices</h3>
            <a href="{{ route('invoices.create', ['client_id' => $client->id]) }}"
               class="text-sm font-semibold text-blue-600 hover:underline">
                + Create Invoice
            </a>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-100">
                    <tr>
                        <th class="p-3">Invoice</th>
                        <th class="p-3">Date</th>
                        <th class="p-3">Amount</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                        <tr class="border-t">
                            <td class="p-3 font-medium">{{ $invoice->invoice_number }}</td>
                            <td class="p-3">{{ $invoice->invoice_date?->format('d-m-Y') }}</td>
                            <td class="p-3">@money($invoice->total_amount)</td>
                            <td class="p-3">{{ ucfirst(str_replace('_', ' ', $invoice->status)) }}</td>
                            <td class="p-3">
                                <a href="{{ route('invoices.show', $invoice) }}" class="text-blue-600 hover:underline">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-4 text-center text-slate-500">No invoices found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- PAYMENTS --}}
    <section id="payments" class="rounded-xl border bg-white p-5">
        <div class="flex items-center justify-between gap-3">
            <h3 class="text-lg font-bold">Client Payments</h3>
            <a href="{{ route('payments.create', ['client_id' => $client->id]) }}"
               class="text-sm font-semibold text-blue-600 hover:underline">
                + Record Payment
            </a>
        </div>

        <p class="mt-2 text-sm text-slate-600">
            Received: <strong>@money($totalReceived)</strong>
            &nbsp;|&nbsp;
            Outstanding: <strong>@money($totalOutstanding)</strong>
        </p>

        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-100">
                    <tr>
                        <th class="p-3">Receipt</th>
                        <th class="p-3">Invoice</th>
                        <th class="p-3">Date</th>
                        <th class="p-3">Method</th>
                        <th class="p-3">Amount</th>
                        <th class="p-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr class="border-t">
                            <td class="p-3 font-medium">{{ $payment->receipt_number }}</td>
                            <td class="p-3">{{ $payment->invoice?->invoice_number ?? '—' }}</td>
                            <td class="p-3">{{ $payment->payment_date?->format('d-m-Y') }}</td>
                            <td class="p-3">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                            <td class="p-3">@money($payment->amount)</td>
                            <td class="p-3">
                                <a href="{{ route('payments.show', $payment) }}" class="text-blue-600 hover:underline">Receipt</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-4 text-center text-slate-500">No payments recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- ACTIVITIES --}}
    <section id="activities" class="rounded-xl border bg-white p-5">
        <div class="flex items-center justify-between gap-3">
            <h3 class="text-lg font-bold">Client Activities</h3>
            <a href="{{ route('clients.activities.create', $client) }}"
               class="text-sm font-semibold text-blue-600 hover:underline">
                + Add Activity
            </a>
        </div>

        <div class="mt-4 space-y-3">
            @forelse($activities as $activity)
                <div class="rounded-lg border p-4">
                    <p class="font-semibold">{{ $activity->subject }}</p>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $activity->activity_type }}
                        | {{ $activity->activity_at?->format('d-m-Y H:i') }}
                    </p>
                    @if($activity->description)
                        <p class="mt-2 text-sm text-slate-600">{{ $activity->description }}</p>
                    @endif
                </div>
            @empty
                <p class="text-sm text-slate-500">No activities recorded.</p>
            @endforelse
        </div>

        <div class="mt-4">{{ $activities->links() }}</div>
    </section>

</div>
@endsection