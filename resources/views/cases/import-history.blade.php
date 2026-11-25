@extends('layouts.app')

@section('title', 'Import history')
@section('page-heading', 'Import history')

@section('content')
<div class="max-w-6xl mx-auto py-8 px-4">

    <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Official Court Import History</h1>
            <p class="text-slate-500 mt-2">Original records and their import status.</p>
        </div>
        <a href="{{ route('cases.import') }}"
           class="px-5 py-2.5 rounded-lg bg-[#16776e] text-white hover:bg-[#125f58]">
            New Import
        </a>
    </div>

    @if (session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-4 mb-5">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4 mb-5">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="p-4">Court Source</th>
                        <th class="p-4">Case Title</th>
                        <th class="p-4">CNR / Case Number</th>
                        <th class="p-4">Imported On</th>
                        <th class="p-4">Status</th>
                        <th class="p-4">Case ID</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($imports as $import)
                        @php($data = $import->parsed_data ?? [])
                        <tr>
                            <td class="p-4">{{ $import->source_name }}<br><span class="text-xs text-slate-500">Attempt #{{ $import->id }} @if(isset($data['retry_of'])) &middot; Retry of #{{ $data['retry_of'] }} @endif</span></td>
                            <td class="p-4">{{ $data['case_title'] ?? 'Not identified' }}</td>
                            <td class="p-4">
                                {{ $data['cnr_number'] ?? $data['case_number'] ?? 'Not identified' }}
                            </td>
                            <td class="p-4">{{ $import->created_at?->format('d-m-Y h:i A') }}</td>
                            <td class="p-4">
                                @if ($import->import_status === 'imported')
                                    <span class="text-green-700 font-medium">Imported</span>
                                @elseif ($import->import_status === 'duplicate')
                                    <span class="text-amber-700 font-medium">Duplicate</span>
                                @elseif(in_array($import->import_status, ['failed', 'partial'], true))
                                    <span class="text-red-700 font-medium">{{ $import->import_status === 'partial' ? 'Partially imported' : 'Failed' }}</span>
                                    <p>{{ $data['reason'] ?? ($import->import_status === 'partial' ? 'Some selected entries could not be matched to a saved case.' : 'No failure detail recorded.') }}</p>
                                    @if($import->retryMethod())
                                        <a class="inline-flex min-h-11 items-center underline" href="{{ route('cases.find', ['method' => $import->retryMethod(), 'retry' => $import->id]) }}">Correct and retry</a>
                                    @endif
                                @else
                                    <span class="text-[#16776e] font-medium">{{ ucfirst($import->import_status) }}</span>
                                @endif
                            </td>
                            @if(isset($data['counts']))
                                <td class="p-4">
                                    @foreach($data['counts'] as $kind => $count)<p>{{ ucfirst($kind) }} {{ $count }}</p>@endforeach
                                    @if(!empty($data['entries']))<a class="inline-flex min-h-11 items-center font-semibold underline" href="{{ route('cases.import.results', $import) }}">View entry results</a>@endif
                                </td>
                            @else
                            <td class="p-4">{{ $import->legal_case_id ?? '—' }}</td>
                            @endif
                        </tr>
                        @if(filled($import->raw_content))
                        <tr>
                            <td colspan="6" class="px-4 pb-4">
                                <details>
                                    <summary class="cursor-pointer text-[#16776e]">View original court record</summary>
                                    <pre class="mt-3 whitespace-pre-wrap break-words bg-slate-50 border rounded-lg p-3 text-xs">{{ $import->raw_content }}</pre>
                                    @if ($import->source_url)
                                        <a href="{{ $import->source_url }}" target="_blank" rel="noopener noreferrer"
                                           class="inline-block mt-2 text-[#16776e] hover:underline">Open source URL</a>
                                    @endif
                                </details>
                            </td>
                        </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="6" class="p-6 text-center text-slate-500">
                                No court records have been imported yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $imports->links() }}</div>
</div>
@endsection