@extends('layouts.app')

@section('title', $case->case_title ?: 'Case Details')
@section('page-heading', 'Case Details')

@section('content')
<div class="space-y-6">

        @php
            $upcomingHearing = $case->hearings
                ->filter(fn ($hearing) => $hearing->next_hearing_date && \Carbon\Carbon::parse($hearing->next_hearing_date)->startOfDay()->gte(today()))
                ->sortBy('next_hearing_date')
                ->first();
            $shareText = collect([
                'Case: '.($case->case_title ?: 'Untitled case'),
                $case->case_number ? 'Case No: '.$case->case_number : null,
                $case->appearingForLabel() ? 'Appearing for: '.$case->appearingForLabel() : null,
                $case->court_name ? 'Court: '.$case->court_name : null,
                ($upcomingHearing?->court_room ?: $case->court_room) ? 'Court room: '.($upcomingHearing?->court_room ?: $case->court_room) : null,
                'Status: '.ucfirst($case->case_status ?: 'unknown').($case->stage ? ' ('.$case->stage.')' : ''),
                $upcomingHearing ? 'Next hearing: '.\Carbon\Carbon::parse($upcomingHearing->next_hearing_date)->format('d M Y') : null,
                ($upcomingHearing?->vc_link ?: $case->vc_link) ? 'VC link: '.($upcomingHearing?->vc_link ?: $case->vc_link) : null,
            ])->filter()->implode("\n");
            $linkedClients = $case->parties->filter(fn ($party) => $party->is_client && $party->client);
            $advocateName = auth()->user()?->name;
            $courtRoom = $upcomingHearing?->court_room ?: $case->court_room;
            // A link sent for the next hearing beats the case's usual link.
            $vcLink = $upcomingHearing?->vc_link ?: $case->vc_link;
            $tabCounts = [
                'case-history' => $case->hearings->count(),
                'case-clients' => $linkedClients->count(),
                'case-notes' => $case->notes->count(),
                'case-appointments' => $case->appointments->count(),
                'case-documents' => $case->documents->count(),
                'case-expenses' => $case->expenses->count(),
                'case-time' => $case->timeEntries->count(),
            ];
            $statusClass = match ($case->case_status) {
                'pending' => 'bg-amber-100 text-amber-800',
                'disposed', 'closed' => 'bg-rose-100 text-rose-700',
                default => 'bg-slate-100 text-slate-700',
            };
        @endphp

        <nav aria-label="Breadcrumb" class="text-sm text-slate-500 print:hidden">
            <a href="{{ route('cases.index') }}" class="hover:underline">My cases</a>
            <span aria-hidden="true">›</span>
            <span class="text-slate-700">{{ \Illuminate\Support\Str::limit($case->case_title ?: 'Untitled case', 60) }}</span>
        </nav>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-2xl font-bold text-slate-900">{{ $case->case_title ?: 'Untitled case' }}</h2>
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">{{ ucfirst($case->case_status ?: 'unknown') }}</span>
                        <x-case-side :side="$case->appearing_for" :parties="$case->appearing_for_parties" size="lg" />
                    </div>
                    <p class="mt-1 text-sm text-slate-600">
                        {{ collect([$case->case_number, $case->cnr_number ? 'CNR '.$case->cnr_number : null, $case->stage ? 'Stage: '.$case->stage : null])->filter()->implode(' · ') ?: 'No case number yet' }}
                    </p>
                </div>

                <div class="flex flex-wrap gap-2 print:hidden">
                    <a href="https://wa.me/?text={{ rawurlencode($shareText) }}" target="_blank" rel="noopener noreferrer" data-direct-share
                       data-share-text="{{ $shareText }}" data-share-title="{{ $case->case_title ?: 'Case' }}"
                       class="inline-flex items-center gap-1.5 rounded-xl bg-[#25d366] px-4 py-2 text-sm font-semibold text-white hover:bg-[#1ebe5b]">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2Zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.2-.4.7-1.3.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.8 11.8 0 0 0 4.5 4c1.7.7 2.3.8 3.2.6.5-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.2-1.2-.1-.1-.2-.2-.5-.3Z"/></svg>
                        Share case
                    </a>
                    <a href="{{ route('cases.edit', $case) }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Edit</a>
                    <button type="button" data-print class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Print</button>
                    @if($case->isClosed())
                        <form method="POST" action="{{ route('cases.reopen', $case) }}">
                            @csrf
                            <button type="submit" class="rounded-xl border border-[#8fc9c0] bg-[#e8f4f2] px-4 py-2 text-sm font-semibold text-[#16776e] hover:bg-[#d9efeb]">Reopen case</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('cases.close', $case) }}" data-confirm="Close this case? You can reopen it later.">
                            @csrf
                            <button type="submit" class="rounded-xl bg-[#142235] px-4 py-2 text-sm font-semibold text-white hover:bg-[#20334a]">Close case</button>
                        </form>
                    @endif
                </div>
            </div>

            @include('cases.partials.key-facts')

            <div class="mt-4 flex flex-wrap items-center gap-x-6 gap-y-3 border-t border-slate-100 pt-4 print:hidden">
                <form method="POST" action="{{ route('cases.file-number', $case) }}" class="flex items-center gap-2">
                    @csrf
                    @method('PATCH')
                    <label for="case-file-number" class="text-sm font-semibold text-slate-700">File No.</label>
                    <input id="case-file-number" name="file_number" value="{{ $case->file_number }}" maxlength="100" placeholder="Add"
                           class="w-36 rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm">
                    <button type="submit" class="text-sm font-semibold text-[#16776e] hover:underline">Save</button>
                </form>

                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-sm font-semibold text-slate-700">Tags</span>
                    @foreach($case->tags as $caseTag)
                        <form method="POST" action="{{ route('cases.tags.detach', [$case, $caseTag]) }}" class="inline-flex">
                            @csrf
                            @method('DELETE')
                            <button type="submit" title="Remove tag" class="rounded-full bg-[#e8f4f2] px-2.5 py-0.5 text-xs font-medium text-[#16776e] hover:bg-[#d9efeb]">{{ $caseTag->name }} ×</button>
                        </form>
                    @endforeach
                    <form method="POST" action="{{ route('cases.tags.attach', $case) }}" class="flex items-center gap-1">
                        @csrf
                        <input name="name" list="case-tag-options" maxlength="50" required placeholder="New tag"
                               class="w-28 rounded-lg border border-slate-300 px-2 py-1 text-xs">
                        <datalist id="case-tag-options">
                            @foreach($tags as $tagOption)<option value="{{ $tagOption->name }}"></option>@endforeach
                        </datalist>
                        <button type="submit" class="text-xs font-semibold text-[#16776e] hover:underline">Add</button>
                    </form>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div role="status" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <x-tabs label="Case workspace" :tabs="[
            'case-overview' => 'Overview',
            'case-history' => 'Case history',
            'case-clients' => 'Notify clients',
            'case-notes' => 'Notes',
            'case-appointments' => 'Appointments',
            'case-documents' => 'Documents',
            'case-expenses' => 'Expenses',
            'case-time' => 'Time entries',
        ]" :counts="$tabCounts" />

        <div id="case-overview" data-tab-panel role="tabpanel" aria-labelledby="tab-case-overview" class="space-y-6">
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
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    @if($nextHearing)
                        <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-800">
                            Upcoming
                        </span>
                    @endif
                    <a href="{{ route('hearings.create', ['case' => $case->id, 'return' => 'case']) }}" class="rounded-lg bg-[#16776e] px-3 py-1.5 text-xs font-semibold text-white hover:bg-[#125f58]">+ Add hearing</a>
                </div>
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
                            {{ $nextHearing->court_room ?: ($case->court_room ?: 'Not available') }}
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

        @include('cases.partials.journey')

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
                    <p class="text-xs font-medium uppercase text-slate-500">We appear for</p>
                    <p class="mt-1 font-medium">{{ $case->appearingForLabel() ?: 'Not set' }}</p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase text-slate-500">Court room</p>
                    <p class="mt-1 font-medium">{{ $case->court_room ?: 'Not available' }}</p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase text-slate-500">VC link</p>
                    @if($case->vc_link)
                        <a href="{{ $case->vc_link }}" target="_blank" rel="noopener noreferrer" data-direct-share class="mt-1 block truncate font-medium text-[#16776e] hover:underline">{{ $case->vc_link }}</a>
                    @else
                        <p class="mt-1 font-medium">Not available</p>
                    @endif
                </div>

                <div>
                    <p class="text-xs font-medium uppercase text-slate-500">Stage</p>
                    <p class="mt-1 font-medium">{{ $case->stage ?: 'Not available' }}</p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase text-slate-500">Disposed on</p>
                    <p class="mt-1 font-medium">{{ $case->disposed_on?->format('d M Y') ?: 'Not disposed' }}</p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase text-slate-500">Petitioner's advocate</p>
                    <p class="mt-1 font-medium">{{ $case->petitioner_advocate ?: 'Not available' }}</p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase text-slate-500">Respondent's advocate</p>
                    <p class="mt-1 font-medium">{{ $case->respondent_advocate ?: 'Not available' }}</p>
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

        <!-- Orders and Documents -->
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Official Orders and Court Documents</h3>

            <p class="mt-2 text-sm text-slate-500">Orders and judgments appear here once added to this case.</p>
        </section>
        </div>

        <section id="case-clients" data-tab-panel role="tabpanel" aria-labelledby="tab-case-clients" class="scroll-mt-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Notify clients</h3>

            @if($errors->has('client_id') || $errors->has('name') || $errors->has('mobile') || $errors->has('email'))
                <div role="alert" class="mt-3 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                    {{ $errors->first('name') ?: ($errors->first('mobile') ?: ($errors->first('email') ?: $errors->first('client_id'))) }}
                </div>
            @endif

            <div class="mt-4 divide-y divide-slate-100">
                @forelse($linkedClients as $party)
                    @php
                        $client = $party->client;
                        $message = $case->clientUpdateMessage($client->displayName(), $advocateName);
                        $whatsapp = $client->whatsappNumber();
                    @endphp
                    <div class="flex flex-wrap items-center justify-between gap-3 py-3">
                        <div class="min-w-0">
                            <a href="{{ route('clients.show', $client) }}" class="font-medium text-slate-900 hover:text-[#16776e]">{{ $client->displayName() }}</a>
                            <p class="mt-0.5 text-xs text-slate-500">{{ collect([$client->mobile, $client->email])->filter()->implode(' · ') ?: 'No mobile or email saved' }}</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 print:hidden">
                            @if($whatsapp)
                                <a href="https://wa.me/{{ $whatsapp }}?text={{ rawurlencode($message) }}" target="_blank" rel="noopener noreferrer" data-direct-share
                                   class="rounded-lg bg-[#25d366] px-3 py-1.5 text-sm font-semibold text-white hover:bg-[#1ebe5b]">WhatsApp</a>
                            @endif
                            @if($client->email)
                                <a href="mailto:{{ $client->email }}?subject={{ rawurlencode('Case update: '.($case->case_title ?: 'your matter')) }}&body={{ rawurlencode($message) }}" data-direct-share
                                   class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Email</a>
                            @endif
                            <form method="POST" action="{{ route('cases.clients.destroy', [$case, $party]) }}" data-confirm="Remove this client from the case?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm text-red-700 hover:underline">Remove</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="py-3 text-sm text-slate-500">No clients linked to this case yet.</p>
                @endforelse
            </div>

            <div class="mt-4 grid gap-4 border-t border-slate-100 pt-4 lg:grid-cols-2 print:hidden">
                <form method="POST" action="{{ route('cases.clients.store', $case) }}" class="flex flex-wrap items-end gap-2">
                    @csrf
                    <label class="min-w-0 flex-1 text-sm font-medium text-slate-700">
                        Link an existing client
                        <select name="client_id" required class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2">
                            <option value="">Select client</option>
                            @foreach($clients as $clientOption)
                                <option value="{{ $clientOption->id }}">{{ $clientOption->displayName() }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button type="submit" class="rounded-lg bg-[#238b80] px-4 py-2 text-sm font-semibold text-white hover:bg-[#16776e]">Link</button>
                </form>

                <form method="POST" action="{{ route('cases.clients.store', $case) }}" class="grid gap-2 sm:grid-cols-[1fr_1fr_1fr_auto] sm:items-end">
                    @csrf
                    <label class="text-sm font-medium text-slate-700">
                        New contact
                        <input name="name" required maxlength="150" placeholder="Name" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    </label>
                    <input name="mobile" maxlength="20" inputmode="tel" placeholder="Mobile" aria-label="Mobile" class="rounded-lg border border-slate-300 px-3 py-2">
                    <input name="email" type="email" maxlength="255" placeholder="Email" aria-label="Email" class="rounded-lg border border-slate-300 px-3 py-2">
                    <button type="submit" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Add</button>
                </form>
            </div>
        </section>

        <!-- Case Timeline / Hearing History -->
        <section id="case-history" data-tab-panel role="tabpanel" aria-labelledby="tab-case-history" class="scroll-mt-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Case Timeline & Hearing History</h3>
                </div>
                <a href="{{ route('hearings.create', ['case' => $case->id, 'return' => 'case']) }}" class="text-sm font-semibold text-[#16776e] hover:underline">+ Add hearing</a>
            </div>

            <div class="mt-5 space-y-4">
                @forelse($case->hearings as $hearing)
                    <div class="border-l-2 border-blue-300 pl-4">
                        <div class="rounded-lg border border-slate-200 p-4">

                            <div class="flex flex-wrap justify-between gap-2">
                                <p class="font-semibold text-slate-900">
                                    {{ $hearing->hearing_date?->format('d M Y') ?: 'Hearing date not available' }}
                                </p>
                                <span class="flex items-center gap-3 text-xs text-slate-500">
                                    {{ $hearing->isManual() ? 'Added by you' : 'Imported court record' }}
                                    @if($hearing->isManual())
                                        <a href="{{ route('hearings.edit', $hearing) }}" class="font-semibold text-[#16776e] hover:underline">Edit</a>
                                    @endif
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

                                @if($hearing->vc_link)
                                    <div>
                                        <a href="{{ $hearing->vc_link }}" target="_blank" rel="noopener noreferrer" data-direct-share class="font-semibold text-[#16776e] hover:underline">Join VC for this hearing</a>
                                    </div>
                                @endif

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

        <section id="case-notes" data-tab-panel role="tabpanel" aria-labelledby="tab-case-notes" class="scroll-mt-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Case Notes</h3>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $case->notes->count() }} notes</span>
            </div>

            <form method="POST" action="{{ route('cases.notes.store', $case) }}" class="mt-4 grid gap-3">
                @csrf
                <label class="text-sm font-medium text-slate-700">
                    Title <span class="font-normal text-slate-400">(optional)</span>
                    <input name="note_title" value="{{ old('note_title') }}" maxlength="160" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('note_title') <span data-field-error class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm font-medium text-slate-700">
                    Note
                    <textarea name="note_body" rows="3" required maxlength="10000" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('note_body') }}</textarea>
                    @error('note_body') <span data-field-error class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
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

        <section id="case-appointments" data-tab-panel role="tabpanel" aria-labelledby="tab-case-appointments" class="scroll-mt-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Appointments</h3>

            <form method="POST" action="{{ route('cases.appointments.store', $case) }}" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                @csrf
                <label class="text-sm font-medium text-slate-700 sm:col-span-2">
                    Appointment title
                    <input name="appointment_title" value="{{ old('appointment_title') }}" required maxlength="160" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('appointment_title') <span data-field-error class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm font-medium text-slate-700">
                    Date and time
                    <input type="datetime-local" name="appointment_starts_at" value="{{ old('appointment_starts_at') }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('appointment_starts_at') <span data-field-error class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm font-medium text-slate-700">
                    Location
                    <input name="appointment_location" value="{{ old('appointment_location') }}" maxlength="255" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('appointment_location') <span data-field-error class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm font-medium text-slate-700 sm:col-span-2">
                    Notes <span class="font-normal text-slate-400">(optional)</span>
                    <textarea name="appointment_notes" rows="2" maxlength="5000" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('appointment_notes') }}</textarea>
                    @error('appointment_notes') <span data-field-error class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <div class="sm:col-span-2"><button class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Schedule appointment</button></div>
            </form>

            <div class="mt-5 divide-y divide-slate-100">
                @forelse($case->appointments as $appointment)
                    <article class="flex flex-wrap justify-between gap-3 py-3">
                        <div>
                            <h4 class="font-semibold text-slate-900">{{ $appointment->title }}</h4>
                            <p class="mt-1 text-sm text-slate-600">{{ $appointment->starts_at->format('d M Y, h:i A') }}@if($appointment->location) · {{ $appointment->location }}@endif</p>
                            @if($appointment->notes)<p class="mt-1 whitespace-pre-line text-sm text-slate-500">{{ $appointment->notes }}</p>@endif
                        </div>
                    </article>
                @empty
                    <p class="py-4 text-sm text-slate-500">No appointments are scheduled for this case.</p>
                @endforelse
            </div>
        </section>

        <section id="case-documents" data-tab-panel role="tabpanel" aria-labelledby="tab-case-documents" class="scroll-mt-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Case Documents</h3>
                    <p class="mt-1 text-sm text-slate-500">PDF, Office, JPG or PNG · up to 20 MB each · stored privately.</p>
                </div>

                <div class="flex flex-wrap gap-2 print:hidden">
                    <form method="POST" action="{{ route('cases.folders.store', $case) }}" class="flex items-center gap-1">
                        @csrf
                        <input type="hidden" name="parent_id" value="{{ $currentFolder?->id }}">
                        <input name="name" required maxlength="120" placeholder="New folder name" aria-label="New folder name"
                               class="w-40 rounded-lg border border-slate-300 px-2.5 py-2 text-sm">
                        <button type="submit" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Create folder</button>
                    </form>

                    <form method="POST" action="{{ route('cases.documents.store', $case) }}" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="folder_id" value="{{ $currentFolder?->id }}">
                        <label class="inline-flex cursor-pointer items-center rounded-lg bg-[#238b80] px-3 py-2 text-sm font-semibold text-white hover:bg-[#16776e]">
                            Upload files
                            <input type="file" name="files[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" data-upload-files class="sr-only">
                        </label>
                    </form>

                    <form method="POST" action="{{ route('cases.documents.store', $case) }}" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="folder_id" value="{{ $currentFolder?->id }}">
                        <label class="inline-flex cursor-pointer items-center rounded-lg border border-[#8fc9c0] bg-[#e8f4f2] px-3 py-2 text-sm font-semibold text-[#16776e] hover:bg-[#d9efeb]">
                            Upload folder
                            <input type="file" name="files[]" multiple webkitdirectory data-upload-folder class="sr-only">
                        </label>
                    </form>
                </div>
            </div>

            @if($errors->has('files') || $errors->has('files.*') || $errors->has('name'))
                <div role="alert" class="mt-3 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                    {{ $errors->first('files') ?: ($errors->first('files.*') ?: $errors->first('name')) }}
                </div>
            @endif

            <nav aria-label="Folder" class="mt-4 flex flex-wrap items-center gap-1 text-sm">
                <a href="{{ route('cases.show', $case) }}#case-documents" class="font-medium text-[#16776e] hover:underline">Documents</a>
                @foreach($currentFolder?->trail() ?? [] as $trailFolder)
                    <span class="text-slate-400" aria-hidden="true">›</span>
                    @if($loop->last)
                        <span class="font-medium text-slate-700">{{ $trailFolder->name }}</span>
                    @else
                        <a href="{{ route('cases.show', [$case, 'folder' => $trailFolder->id]) }}#case-documents" class="font-medium text-[#16776e] hover:underline">{{ $trailFolder->name }}</a>
                    @endif
                @endforeach
            </nav>

            <div class="mt-3 divide-y divide-slate-100">
                @foreach($folders as $folder)
                    <div class="flex items-center justify-between gap-3 py-3">
                        <a href="{{ route('cases.show', [$case, 'folder' => $folder->id]) }}#case-documents" class="flex min-w-0 items-center gap-2 font-medium text-slate-900 hover:text-[#16776e]">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="#f6c453" aria-hidden="true"><path d="M3 6.5A1.5 1.5 0 0 1 4.5 5h5l2 2h8A1.5 1.5 0 0 1 21 8.5v9a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 17.5Z"/></svg>
                            <span class="truncate">{{ $folder->name }}</span>
                        </a>
                        <form method="POST" action="{{ route('cases.folders.destroy', [$case, $folder]) }}" data-confirm="Delete this folder? Only empty folders can be deleted." class="print:hidden">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm text-red-700 hover:underline">Delete</button>
                        </form>
                    </div>
                @endforeach

                @foreach($folderDocuments as $document)
                    <div class="flex flex-wrap items-center justify-between gap-3 py-3">
                        <div class="min-w-0">
                            <p class="truncate font-medium text-slate-900">{{ $document->original_name }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ number_format($document->size / 1024, 0) }} KB · {{ $document->created_at->format('d M Y') }}</p>
                        </div>
                        <div class="flex items-center gap-3 print:hidden">
                            <a href="{{ route('cases.documents.download', [$case, $document]) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-[#16776e] hover:bg-slate-50">Download</a>
                            <form method="POST" action="{{ route('cases.documents.destroy', [$case, $document]) }}" data-confirm="Delete this document permanently?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm text-red-700 hover:underline">Delete</button>
                            </form>
                        </div>
                    </div>
                @endforeach

                @if($folders->isEmpty() && $folderDocuments->isEmpty())
                    <p class="py-4 text-sm text-slate-500">{{ $currentFolder ? 'This folder is empty.' : 'No files have been uploaded to this case.' }}</p>
                @endif
            </div>
        </section>

        <section id="case-expenses" data-tab-panel role="tabpanel" aria-labelledby="tab-case-expenses" class="scroll-mt-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Case Expenses</h3>
                </div>
                <p class="rounded-full bg-amber-50 px-3 py-1 text-sm font-semibold text-amber-800">
                    Total: @money($case->expenses->sum('amount'))
                </p>
            </div>

            <form method="POST" action="{{ route('cases.expenses.store', $case) }}" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                @csrf
                <label class="text-sm font-medium text-slate-700 sm:col-span-3">
                    Expense description
                    <input name="expense_description" value="{{ old('expense_description') }}" required maxlength="255" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('expense_description') <span data-field-error class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm font-medium text-slate-700">
                    Amount (@currency)
                    <input type="number" name="expense_amount" value="{{ old('expense_amount') }}" min="0.01" step="0.01" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('expense_amount') <span data-field-error class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm font-medium text-slate-700">
                    Date
                    <input type="date" name="expense_date" value="{{ old('expense_date', now()->toDateString()) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('expense_date') <span data-field-error class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <div class="flex items-end"><button class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Add expense</button></div>
            </form>

            <div class="mt-5 divide-y divide-slate-100">
                @forelse($case->expenses as $expense)
                    <div class="flex flex-wrap justify-between gap-3 py-3">
                        <div><p class="font-medium text-slate-900">{{ $expense->description }}</p><p class="mt-1 text-xs text-slate-500">{{ $expense->expense_date->format('d M Y') }}</p></div>
                        <p class="font-semibold text-slate-800">@money((float) $expense->amount)</p>
                    </div>
                @empty
                    <p class="py-4 text-sm text-slate-500">No expenses have been recorded for this case.</p>
                @endforelse
            </div>
        </section>

        <section id="case-time" data-tab-panel role="tabpanel" aria-labelledby="tab-case-time" class="scroll-mt-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Time Entries</h3>
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
                    <input name="time_description" value="{{ old('time_description') }}" required maxlength="255" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('time_description') <span data-field-error class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm font-medium text-slate-700">
                    Date
                    <input type="date" name="entry_date" value="{{ old('entry_date', now()->toDateString()) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('entry_date') <span data-field-error class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm font-medium text-slate-700">
                    Hours
                    <input type="number" name="time_hours" value="{{ old('time_hours', 0) }}" min="0" max="23" step="1" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('time_hours') <span data-field-error class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm font-medium text-slate-700">
                    Minutes
                    <input type="number" name="time_minutes" value="{{ old('time_minutes', 0) }}" min="0" max="59" step="1" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('time_minutes') <span data-field-error class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm font-medium text-slate-700">
                    Hourly rate (@currency, optional)
                    <input type="number" name="hourly_rate" value="{{ old('hourly_rate') }}" min="0" step="0.01" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('hourly_rate') <span data-field-error class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <div class="sm:col-span-2 lg:col-span-4"><button class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Add time entry</button></div>
            </form>

            <div class="mt-5 divide-y divide-slate-100">
                @forelse($case->timeEntries as $entry)
                    <div class="flex flex-wrap justify-between gap-3 py-3">
                        <div><p class="font-medium text-slate-900">{{ $entry->description }}</p><p class="mt-1 text-xs text-slate-500">{{ $entry->entry_date->format('d M Y') }}</p></div>
                        <p class="font-semibold text-slate-800">
                            {{ $entry->hours }}h {{ $entry->minutes }}m
                            @if($entry->hourly_rate !== null)<span class="ml-2 text-xs font-normal text-slate-500">@money((float) $entry->hourly_rate)/hr</span>@endif
                        </p>
                    </div>
                @empty
                    <p class="py-4 text-sm text-slate-500">No time has been recorded for this case.</p>
                @endforelse
            </div>
        </section>


        <p class="text-xs text-slate-400">Verify case status and hearings with the court portal.</p>
</div>
@endsection
