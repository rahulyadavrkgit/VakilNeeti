@extends('layouts.app')

@section('title', $case->case_title ?: 'Case Details')
@section('page-heading', 'Case Details')
@section('back-url', route('cases.index'))

@section('content')
<div class="mx-auto w-full max-w-5xl space-y-3 sm:space-y-4">

        @php
            $todayDate = today()->toDateString();
            $panelDates = $case->hearings->flatMap(fn ($hearing) => collect([
                $hearing->hearing_date ? ['date' => $hearing->hearing_date, 'time' => $hearing->hearing_time, 'hearing' => $hearing] : null,
                $hearing->next_hearing_date ? ['date' => $hearing->next_hearing_date, 'time' => null, 'hearing' => $hearing] : null,
            ])->filter());
            $panelNext = $panelDates->filter(fn ($entry) => $entry['date']->toDateString() >= $todayDate)
                ->sortBy(fn ($entry) => [$entry['date']->toDateString(), !$entry['hearing']->hearing_date?->isSameDay($entry['date']), $entry['time'] ?? '99'])->first();
            $firstListing = $causeListings->first();
            if ($firstListing && (!$panelNext || $firstListing->courtDocument->listing_date->lt($panelNext['date']))) {
                $panelNext = ['date' => $firstListing->courtDocument->listing_date, 'time' => null, 'hearing' => null];
            }
            $panelListing = $panelNext ? $causeListings->first(fn ($listing) => $listing->courtDocument->listing_date->isSameDay($panelNext['date'])) : null;
            $panelLast = $panelNext ? null : $panelDates->sortByDesc(fn ($entry) => $entry['date']->toDateString())->first();
            $nextHearing = $panelNext['hearing'] ?? null;
            $datedHearing = $panelNext && $nextHearing?->hearing_date?->isSameDay($panelNext['date']) ? $nextHearing : null;
            $panelTime = $panelNext && $panelNext['time'] ? substr((string) $panelNext['time'], 0, 5) : null;
            $listBench = app(\App\Services\CourtBenchMatcher::class)->find($case->court_name, $case->court_complex, $case->judge_name);
            $listJudge = $listBench?->judgeList()[0]['name'] ?? null;
            $listNote = '<span class="ml-1 text-xs font-normal text-slate-400">(court list)</span>';
            $linkedClients = $case->parties->filter(fn ($party) => $party->is_client && $party->client);
            $photoDistrict = $case->court_level === 'district' ? $case->district : null;
            $advocateName = auth()->user()?->name;
            $courtRoom = $panelListing?->court_room ?: ($nextHearing?->court_room ?: ($case->court_room ?: $listBench?->court_room));
            $vcLink = $panelListing?->vcLink() ?: ($nextHearing?->vc_link ?: ($case->vc_link ?: $listBench?->vc_link));
            $judge = $panelListing?->judge_name ?: ($nextHearing?->judge_name ?: ($case->judge_name ?: $listJudge));
            $judge = $judge ?: app(\App\Services\CourtBenchMatcher::class)->findRoom($case->court_name, $courtRoom)?->judgeNames();
            $causeList = $panelListing?->stripData();
            $purpose = $nextHearing?->hearing_stage ?: ($panelNext ? $case->stage : null);
            $item = $causeList['item'] ?? $datedHearing?->item_number;
            $listTotal = $causeList['total'] ?? $datedHearing?->list_total;
            $shareText = \App\Services\HearingShareText::single([
                'title' => $case->case_title, 'case_number' => $case->case_number,
                'side' => $case->appearing_for, 'parties' => $case->appearing_for_parties,
                'date' => $panelNext ? $panelNext['date']->toDateString() : null, 'time' => $panelTime,
                'court' => $case->court_name, 'room' => $courtRoom, 'judge' => $judge, 'vc_link' => $vcLink,
                'details' => $purpose,
                'item' => $item, 'list_total' => $listTotal,
            ])."\nStatus: ".ucfirst($case->case_status ?: 'unknown');
            $tabCounts = [
                'case-history' => $case->hearings->count(),
                'case-journey' => count($journey['lower']) + count($journey['higher']),
                'case-connected' => count($journey['connected']),
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
            $caseAction = 'inline-flex min-h-10 w-full items-center rounded-md px-2.5 text-left text-sm font-semibold hover:bg-slate-50';
            $positionUrl = route('sheets.court-position', $case);
            $activeClientLink = $case->hasActiveStatusLink();
        @endphp

        <nav aria-label="Breadcrumb" class="text-sm text-slate-500 print:hidden">
            <a href="{{ route('cases.index') }}" class="hover:underline">My cases</a>
            <span aria-hidden="true">›</span>
            <span class="text-slate-700">{{ \Illuminate\Support\Str::limit($case->case_title ?: 'Untitled case', 60) }}</span>
        </nav>

        @if(session('success'))
            <div role="status" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <section aria-label="Case summary" class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="px-3.5 pt-3.5 sm:px-5 sm:pt-4">
                <div class="flex flex-wrap items-center justify-between gap-x-2 gap-y-1">
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">{{ ucfirst($case->case_status ?: 'unknown') }}</span>
                    <div class="ml-auto flex min-w-0 items-center justify-end gap-2 print:hidden" aria-label="Case actions" role="group">
                        <a href="https://wa.me/?text={{ rawurlencode($shareText) }}" target="_blank" rel="noopener noreferrer" data-direct-share
                           aria-label="Share case details" data-share-text="{{ $shareText }}" data-share-title="{{ $case->case_title ?: 'Case' }}"
                           class="vn-primary-button inline-flex min-h-10 items-center gap-1 rounded-lg px-2.5 text-sm font-semibold">
                            <x-icon name="share" :size="15" /> Share
                        </a>
                        <details class="vn-profile-menu relative">
                            <summary class="flex min-h-10 cursor-pointer list-none items-center gap-1 rounded-lg border border-slate-300 px-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 [&::-webkit-details-marker]:hidden">
                                Actions <x-icon name="chevron" :size="14" />
                            </summary>
                            <div class="absolute right-0 z-30 mt-1 grid w-40 gap-0.5 rounded-lg border border-slate-200 bg-white p-1 shadow-lg">
                                <button type="button" data-print class="{{ $caseAction }} text-slate-700">Print</button>
                                <a href="{{ route('cases.edit', $case) }}" class="{{ $caseAction }}">Edit case</a>
                                <a href="{{ route('cases.download', $case) }}" class="{{ $caseAction }}">Download PDF</a>
                                @if($case->isClosed())
                                    <form method="POST" action="{{ route('cases.reopen', $case) }}">
                                        @csrf
                                        <button type="submit" class="{{ $caseAction }} text-slate-700">Reopen case</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('cases.close', $case) }}" data-confirm="Close this case? You can reopen it later.">
                                        @csrf
                                        <button type="submit" class="{{ $caseAction }} text-red-700">Close case</button>
                                    </form>
                                @endif
                            </div>
                        </details>
                    </div>
                </div>
                <h2 class="mt-2 wrap-anywhere text-xl font-bold leading-snug tracking-tight text-slate-900 sm:text-2xl">{{ $case->case_title ?: 'Untitled case' }}</h2>
                <div class="mt-1.5 flex flex-wrap items-baseline gap-x-2.5 gap-y-0.5 text-sm">
                    @if($case->case_number)
                        <span class="wrap-anywhere font-bold tabular-nums text-slate-900">{{ $case->case_number }}</span>
                    @endif
                    <span class="min-w-0 wrap-anywhere text-slate-600">{{ $case->court_name ?: $case->courtLevel() }}</span>
                </div>
                @if($case->cnr_number || $case->diary_number)
                    <dl class="mt-1 flex flex-wrap gap-x-3 gap-y-0.5 text-xs text-slate-600">
                        @if($case->cnr_number)
                            <div><dt class="inline font-semibold">CNR</dt> <dd class="inline break-all">{{ $case->cnr_number }}</dd></div>
                        @endif
                        @if($case->diary_number)
                            <div><dt class="inline font-semibold">Diary</dt> <dd class="inline break-all">{{ $case->diary_number }}</dd></div>
                        @endif
                    </dl>
                @endif
                @unless($panelNext)
                    <x-court-info class="mt-2" :live="false" :court="$case->court_name" :designation="$case->court_name" :complex="$case->court_complex" :room="$courtRoom" :judge="$judge" :vc-link="$vcLink" :copy-vc="true" :judge-photos="true" :photo-district="$photoDistrict" />
                @endunless
                <details class="max-w-full text-sm print:hidden">
                    <summary class="vn-link min-h-9 cursor-pointer py-2 font-semibold">Petitioner &amp; Respondent Advocates</summary>
                    <dl class="mt-1 grid gap-3 rounded-lg bg-slate-50 p-3 sm:grid-cols-2">
                        <div>
                            <dt class="font-semibold text-slate-900">Petitioner advocate</dt>
                            <dd class="mt-0.5 break-words text-slate-700">{{ $case->petitioner_advocate ?: 'Not recorded' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-slate-900">Respondent advocate</dt>
                            <dd class="mt-0.5 break-words text-slate-700">{{ $case->respondent_advocate ?: 'Not recorded' }}</dd>
                        </div>
                    </dl>
                </details>
            </div>

            <div class="flex flex-wrap items-center gap-2 border-t border-slate-200 px-2 py-2 sm:px-3">
                <div class="grid min-w-0 flex-1 basis-full grid-cols-2 divide-x divide-slate-200 text-sm sm:basis-auto" aria-label="Appearance and position" role="group">
                    <a href="{{ $positionUrl }}" data-sheet="{{ $positionUrl }}" class="min-w-0 px-2.5 py-1.5 hover:bg-slate-50">
                        <span class="block text-xs text-slate-600">Court position</span>
                        <span class="vn-link block break-words font-semibold">{{ $standSide ? $standSide['label'].' facing judge' : 'Set position' }}</span>
                    </a>
                    <a href="{{ $positionUrl }}" data-sheet="{{ $positionUrl }}" class="min-w-0 px-2.5 py-1.5 hover:bg-slate-50">
                        <span class="block text-xs text-slate-600">We appear for</span>
                        <span class="vn-link block break-words font-semibold">{{ $case->appearingForLabel() ?? 'Set appearance' }}</span>
                    </a>
                </div>
                <div class="ml-auto print:hidden">
                    <x-quick-next-date :case-id="$case->id" return="case" :open="true" :label="$panelDates->isNotEmpty() || $panelNext ? 'Add another date' : 'Add next date'" />
                </div>
            </div>
        </section>

        <x-tabs label="Case workspace" compact :tabs="[
            'case-history' => 'Timeline',
            'case-overview' => 'Details',
            'case-documents' => 'Documents',
            'case-clients' => 'Clients',
            'case-notes' => 'Notes',
            'case-follow-ups' => 'Follow-ups',
            'case-connected' => 'Connected matters',
            'case-journey' => 'Court journey',
            'case-appointments' => 'Appointments',
            'case-expenses' => 'Expenses',
            'case-time' => 'Time entries',
        ]" :counts="$tabCounts" />

        @include('cases.partials.timeline')
        @include('cases.partials.journey')
        @include('cases.partials.connected')
        @include('cases.partials.follow-ups')

        <div id="case-overview" data-tab-panel role="tabpanel" aria-labelledby="tab-case-overview" class="space-y-6">

        <!-- Case Information -->
        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <h3 class="text-lg font-semibold text-slate-900">Case Information</h3>

            <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-3 border-t border-slate-100 pt-3 print:hidden">
                <form method="POST" action="{{ route('cases.file-number', $case) }}" class="flex min-w-0 flex-wrap items-center gap-2">
                    @csrf
                    @method('PATCH')
                    <label for="case-file-number" class="text-sm font-semibold text-slate-700">File No.</label>
                    <input id="case-file-number" name="file_number" value="{{ $case->file_number }}" maxlength="100" placeholder="Add"
                           class="w-28 rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm sm:w-36">
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

            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">

                <div>
                    <p class="text-xs font-medium uppercase text-slate-500">CNR Number</p>
                    <p class="mt-1 font-medium">{{ $case->cnr_number ?: 'Not available' }}</p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase text-slate-500">Case Number</p>
                    <p class="mt-1 font-medium">{{ $case->case_number ?: 'Not available' }}</p>
                </div>

                @if($case->matter_id)
                    <div>
                        <p class="text-xs font-medium uppercase text-slate-500">Matter</p>
                        <p class="mt-1 font-medium">{{ $case->matter_id }}</p>
                    </div>
                @endif

                @if($case->diary_number)
                    <div>
                        <p class="text-xs font-medium uppercase text-slate-500">Diary Number</p>
                        <p class="mt-1 font-medium">{{ $case->diary_number }}</p>
                    </div>
                @endif

                <div>
                    <p class="text-xs font-medium uppercase text-slate-500">Case Type</p>
                    <p class="mt-1 font-medium">{{ $case->case_type ?: 'Not available' }}</p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase text-slate-500">Court</p>
                    <p class="mt-1 font-medium">{{ $case->court_name ?: 'Not available' }}</p>
                    @if($case->court_level)
                        <x-court-badge :level="$case->court_level" :state="$case->state_code" :district="$case->district" class="mt-1" />
                    @endif
                </div>

                <div>
                    <p class="text-xs font-medium uppercase text-slate-500">Court Complex / Establishment</p>
                    <p class="mt-1 font-medium">{{ $case->court_complex ?: 'Not available' }}</p>
                    @if($listBench)
                        <a href="{{ route('courts.delhi-district-courts.judges', \Illuminate\Support\Str::after($listBench->court_key, 'delhi-district-')) }}" class="mt-1 inline-block text-xs font-semibold text-[#16776e] hover:underline">Found in the court list &rarr;</a>
                    @endif
                </div>

                <div>
                    <p class="text-xs font-medium uppercase text-slate-500">Judge</p>
                    <p class="mt-1 font-medium">{{ $case->judge_name ?: ($listJudge ?: 'Not available') }}@if(! $case->judge_name && $listJudge){!! $listNote !!}@endif</p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase text-slate-500">We appear for</p>
                    <p class="mt-1 font-medium">{{ $case->appearingForLabel() ?: 'Not set' }}</p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase text-slate-500">Court room</p>
                    <p class="mt-1 font-medium">{{ $case->court_room ?: ($listBench?->court_room ?: 'Not available') }}@if(! $case->court_room && $listBench?->court_room){!! $listNote !!}@endif</p>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase text-slate-500">VC link</p>
                    @if($case->vc_link ?: $listBench?->vc_link)
                        <a href="{{ $case->vc_link ?: $listBench->vc_link }}" target="_blank" rel="noopener noreferrer" data-direct-share class="mt-1 block truncate font-medium text-[#16776e] hover:underline">{{ $case->vc_link ?: $listBench->vc_link }}</a>
                        @if(! $case->vc_link)<p class="text-xs text-slate-400">Court list</p>@endif
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
                           class="mt-1 inline-block break-all text-sm text-[#16776e] hover:underline">
                            Open source record
                        </a>
                    @else
                        <p class="mt-1 break-all text-sm">{{ $case->source_reference }}</p>
                    @endif
                </div>
            @endif
        </section>

        <!-- Parties -->
        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <h3 class="text-lg font-semibold text-slate-900">Parties</h3>

            <div class="mt-4 overflow-x-auto">
                <table class="vn-stack-table w-full text-left text-sm">
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
                                <td class="p-3 font-medium text-slate-900" data-stack="title">
                                    {{ $party->party_name }}
                                </td>
                                <td class="p-3" data-label="Party type">
                                    {{ $party->party_type ?: 'Not specified' }}
                                </td>
                                <td class="p-3" data-label="Our client">
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
        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <h3 class="text-lg font-semibold text-slate-900">Orders and Court Documents</h3>

            <p class="mt-2 text-sm text-slate-500">Orders and judgments appear here once added to this case.</p>
        </section>
        </div>

        <section id="case-clients" data-tab-panel role="tabpanel" aria-labelledby="tab-case-clients" class="scroll-mt-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <h3 class="text-lg font-semibold text-slate-900">Clients</h3>

            @unless(auth()->user()->isClerk())
                <div id="client-link" class="mt-3 scroll-mt-36 rounded-xl border border-teal-200 bg-teal-50/60 p-3 text-sm print:hidden">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h4 class="font-semibold text-slate-900">Client link</h4>
                            <p class="mt-0.5 text-xs text-slate-600">
                                @if($activeClientLink)
                                    Expires {{ $case->status_link_expires_at->format('d M Y, g:i a') }}
                                @elseif($case->status_token)
                                    Link expired. Create a new one to share updates.
                                @else
                                    Expires 7 days after creation.
                                @endif
                            </p>
                        </div>
                        @unless($activeClientLink)
                            <form method="POST" action="{{ route('cases.status-link.create', $case) }}">
                                @csrf
                                <button type="submit" class="inline-flex min-h-9 items-center gap-1.5 rounded-lg bg-[#16776e] px-3 font-semibold text-white hover:bg-[#125f58]">
                                    <x-icon name="share" :size="16" /> Create client link
                                </button>
                            </form>
                        @endunless
                    </div>
                    @if($activeClientLink)
                        @php
                            $statusUrl = route('case-status.show', $case->status_token);
                            $statusMessage = "Updates on your case {$case->case_title}: next date, purpose and court. This link is available until {$case->status_link_expires_at->format('d M Y, g:i a')}.\n{$statusUrl}";
                        @endphp
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <a href="{{ $statusUrl }}" class="vn-link min-w-0 grow basis-full truncate sm:basis-44" title="{{ $statusUrl }}">{{ $statusUrl }}</a>
                            <button type="button" data-copy="{{ $statusUrl }}" class="inline-flex min-h-9 items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 font-semibold text-slate-700"><x-icon name="copy" :size="14" /><span data-copy-label>Copy</span></button>
                            <a href="https://wa.me/?text={{ rawurlencode($statusMessage) }}" data-direct-share data-share-text="{{ $statusMessage }}" data-share-title="Case update link" class="inline-flex min-h-9 items-center gap-1 rounded-lg bg-[#16776e] px-3 font-semibold text-white"><x-icon name="share" :size="14" /> Send link</a>
                            <form method="POST" action="{{ route('cases.status-link.destroy', $case) }}" data-confirm="Switch off this client link? The old link will stop opening.">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="min-h-9 px-1 text-xs font-semibold text-red-700 hover:underline">Switch off</button>
                            </form>
                        </div>
                    @endif
                </div>
            @endunless

            <h4 class="mt-4 text-sm font-semibold text-slate-900">Notify clients</h4>

            @if($errors->has('client_id') || $errors->has('name') || $errors->has('mobile') || $errors->has('email'))
                <div role="alert" class="mt-3 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                    {{ $errors->first('name') ?: ($errors->first('mobile') ?: ($errors->first('email') ?: $errors->first('client_id'))) }}
                </div>
            @endif

            <div class="divide-y divide-slate-100">
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
                                <a href="tel:+{{ $whatsapp }}" data-direct-share class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Call</a>
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

            <div class="mt-4 space-y-4 border-t border-slate-100 pt-4 print:hidden">
                <form method="POST" action="{{ route('cases.clients.store', $case) }}" class="max-w-md">
                    @csrf
                    <label for="case-existing-client" class="text-sm font-medium text-slate-700">Link an existing client</label>
                    <div class="mt-1 flex items-center gap-2">
                        <select id="case-existing-client" name="client_id" required class="min-h-10 min-w-0 flex-1 rounded-lg border border-slate-300 bg-white px-2.5 text-sm">
                            <option value="">Select client</option>
                            @foreach($clients as $clientOption)
                                <option value="{{ $clientOption->id }}">{{ $clientOption->displayName() }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="vn-primary-button min-h-9 rounded-lg px-3 text-sm font-semibold">Link</button>
                    </div>
                </form>

                <form method="POST" action="{{ route('cases.clients.store', $case) }}">
                    @csrf
                    <fieldset>
                        <legend class="text-sm font-semibold text-slate-900">New contact</legend>
                        <div class="mt-1.5 grid items-end gap-2 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,1.25fr)_auto]">
                            <label class="min-w-0 text-xs font-medium text-slate-700">
                                Name
                                <input name="name" required maxlength="150" autocomplete="name" value="{{ old('name') }}" class="mt-1 min-h-10 w-full min-w-0 rounded-lg border border-slate-300 px-2.5 text-sm">
                            </label>
                            <label class="min-w-0 text-xs font-medium text-slate-700">
                                Mobile
                                <input name="mobile" maxlength="20" inputmode="tel" autocomplete="tel" value="{{ old('mobile') }}" class="mt-1 min-h-10 w-full min-w-0 rounded-lg border border-slate-300 px-2.5 text-sm">
                            </label>
                            <label class="min-w-0 text-xs font-medium text-slate-700">
                                Email
                                <input name="email" type="email" maxlength="255" autocomplete="email" value="{{ old('email') }}" class="mt-1 min-h-10 w-full min-w-0 rounded-lg border border-slate-300 px-2.5 text-sm">
                            </label>
                            <button type="submit" class="{{ $caseAction }} justify-self-start text-slate-700">Add contact</button>
                        </div>
                    </fieldset>
                </form>
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
                    <input name="note_title" value="{{ old('note_title') }}" maxlength="160" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#16776e] focus:ring-[#16776e]">
                    @error('note_title') <span data-field-error class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm font-medium text-slate-700">
                    Note
                    <textarea name="note_body" rows="3" required maxlength="10000" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#16776e] focus:ring-[#16776e]">{{ old('note_body') }}</textarea>
                    @error('note_body') <span data-field-error class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <div><button class="rounded-lg bg-[#16776e] px-4 py-2 text-sm font-semibold text-white hover:bg-[#125f58]">Add note</button></div>
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
                    <input name="appointment_title" value="{{ old('appointment_title') }}" required maxlength="160" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#16776e] focus:ring-[#16776e]">
                    @error('appointment_title') <span data-field-error class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm font-medium text-slate-700">
                    Date and time
                    <input type="datetime-local" name="appointment_starts_at" value="{{ old('appointment_starts_at') }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#16776e] focus:ring-[#16776e]">
                    @error('appointment_starts_at') <span data-field-error class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm font-medium text-slate-700">
                    Location
                    <input name="appointment_location" value="{{ old('appointment_location') }}" maxlength="255" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#16776e] focus:ring-[#16776e]">
                    @error('appointment_location') <span data-field-error class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm font-medium text-slate-700 sm:col-span-2">
                    Notes <span class="font-normal text-slate-400">(optional)</span>
                    <textarea name="appointment_notes" rows="2" maxlength="5000" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#16776e] focus:ring-[#16776e]">{{ old('appointment_notes') }}</textarea>
                    @error('appointment_notes') <span data-field-error class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <div class="sm:col-span-2"><button class="rounded-lg bg-[#16776e] px-4 py-2 text-sm font-semibold text-white hover:bg-[#125f58]">Schedule appointment</button></div>
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
                    <input name="expense_description" value="{{ old('expense_description') }}" required maxlength="255" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#16776e] focus:ring-[#16776e]">
                    @error('expense_description') <span data-field-error class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm font-medium text-slate-700">
                    Amount (@currency)
                    <input type="number" name="expense_amount" value="{{ old('expense_amount') }}" min="0.01" step="0.01" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#16776e] focus:ring-[#16776e]">
                    @error('expense_amount') <span data-field-error class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm font-medium text-slate-700">
                    Date
                    <input type="date" name="expense_date" value="{{ old('expense_date', now()->toDateString()) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#16776e] focus:ring-[#16776e]">
                    @error('expense_date') <span data-field-error class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <div class="flex items-end"><button class="rounded-lg bg-[#16776e] px-4 py-2 text-sm font-semibold text-white hover:bg-[#125f58]">Add expense</button></div>
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
                    <input name="time_description" value="{{ old('time_description') }}" required maxlength="255" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#16776e] focus:ring-[#16776e]">
                    @error('time_description') <span data-field-error class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm font-medium text-slate-700">
                    Date
                    <input type="date" name="entry_date" value="{{ old('entry_date', now()->toDateString()) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#16776e] focus:ring-[#16776e]">
                    @error('entry_date') <span data-field-error class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm font-medium text-slate-700">
                    Hours
                    <input type="number" name="time_hours" value="{{ old('time_hours', 0) }}" min="0" max="23" step="1" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#16776e] focus:ring-[#16776e]">
                    @error('time_hours') <span data-field-error class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm font-medium text-slate-700">
                    Minutes
                    <input type="number" name="time_minutes" value="{{ old('time_minutes', 0) }}" min="0" max="59" step="1" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#16776e] focus:ring-[#16776e]">
                    @error('time_minutes') <span data-field-error class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm font-medium text-slate-700">
                    Hourly rate (@currency, optional)
                    <input type="number" name="hourly_rate" value="{{ old('hourly_rate') }}" min="0" step="0.01" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#16776e] focus:ring-[#16776e]">
                    @error('hourly_rate') <span data-field-error class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <div class="sm:col-span-2 lg:col-span-4"><button class="rounded-lg bg-[#16776e] px-4 py-2 text-sm font-semibold text-white hover:bg-[#125f58]">Add time entry</button></div>
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


        <div class="print:hidden">
            <p class="text-xs text-slate-400">Verify case status and hearings with the court portal.</p>
            <x-case-report :case-id="$case->id" page="case-details" />
            @error('message')<p role="alert" class="text-sm text-red-700">{{ $message }}</p>@enderror
        </div>
</div>

@endsection
