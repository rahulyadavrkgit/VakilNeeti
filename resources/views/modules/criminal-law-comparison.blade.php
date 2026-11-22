@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl space-y-6 px-4 py-5">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('new-criminal-laws.index') }}"
               class="text-sm text-indigo-700 hover:underline">
                &larr; New Criminal Laws
            </a>

            <h1 class="mt-2 text-2xl font-bold text-gray-900">
                {{ $comparison['title'] }}
            </h1>

            <p class="mt-1 text-sm text-gray-500">
                {{ $comparison['subject'] }}
            </p>
        </div>

        <a href="{{ $comparison['comparison_url'] }}"
           target="_blank"
           rel="noopener noreferrer"
           class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
            Official NCRB comparison
        </a>
    </div>

    <div class="flex flex-wrap gap-2">
        @foreach($allComparisons as $item)
            <a href="{{ route('new-criminal-laws.compare', ['pair' => $item['key']]) }}"
               class="rounded-lg border px-3 py-2 text-sm font-medium
               {{ $comparison['key'] === $item['key']
                    ? 'border-indigo-600 bg-indigo-50 text-indigo-700'
                    : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50' }}">
                {{ $item['new_short'] }} / {{ $item['old_short'] }}
            </a>
        @endforeach
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">

        <form method="GET"
              action="{{ route('new-criminal-laws.compare', ['pair' => $comparison['key']]) }}"
              class="space-y-4">

            <div class="grid grid-cols-1 gap-3 md:grid-cols-4">

                <div class="md:col-span-2">
                    <label for="comparison-search"
                           class="mb-1 block text-sm font-medium text-gray-700">
                        Search section or keyword
                    </label>

                    <input
                        id="comparison-search"
                        type="search"
                        name="q"
                        value="{{ $search }}"
                        placeholder="e.g. 302, 103, murder, arrest..."
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                    >
                </div>

                <div>
                    <label for="comparison-direction"
                           class="mb-1 block text-sm font-medium text-gray-700">
                        Search direction
                    </label>

                    <select
                        id="comparison-direction"
                        name="direction"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">

                        <option value="new_to_old" {{ $direction === 'new_to_old' ? 'selected' : '' }}>
                            {{ $comparison['new_short'] }} to {{ $comparison['old_short'] }}
                        </option>

                        <option value="old_to_new" {{ $direction === 'old_to_new' ? 'selected' : '' }}>
                            {{ $comparison['old_short'] }} to {{ $comparison['new_short'] }}
                        </option>
                    </select>
                </div>

                <div>
                    <label for="comparison-status"
                           class="mb-1 block text-sm font-medium text-gray-700">
                        Provision status
                    </label>

                    <select
                        id="comparison-status"
                        name="status"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">

                        <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All statuses</option>
                        <option value="corresponding" {{ $status === 'corresponding' ? 'selected' : '' }}>Corresponding</option>
                        <option value="changed" {{ $status === 'changed' ? 'selected' : '' }}>Changed</option>
                        <option value="new" {{ $status === 'new' ? 'selected' : '' }}>New</option>
                        <option value="deleted" {{ $status === 'deleted' ? 'selected' : '' }}>Deleted</option>
                    </select>
                </div>

            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="submit"
                        class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                    Search
                </button>

                <a href="{{ route('new-criminal-laws.compare', ['pair' => $comparison['key']]) }}"
                   class="rounded-lg border border-gray-300 px-5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Reset
                </a>

                <span class="text-sm text-gray-500">
                    {{ number_format($resultCount) }} matching entries
                </span>
            </div>

        </form>
    </div>

    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
        @php
            $statusLabels = [
                'corresponding' => 'Corresponding',
                'changed' => 'Changed',
                'new' => 'New',
                'deleted' => 'Deleted',
            ];
        @endphp

        @foreach($statusLabels as $key => $label)
            <div class="rounded-lg border border-gray-200 bg-white p-3">
                <div class="text-xs text-gray-500">{{ $label }}</div>
                <div class="mt-1 text-xl font-semibold text-gray-900">
                    {{ number_format((int) ($statusCounts[$key] ?? 0)) }}
                </div>
            </div>
        @endforeach
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

        <div class="border-b border-gray-200 px-4 py-3">
            <h2 class="font-semibold text-gray-900">Section comparison</h2>
            <p class="mt-1 text-xs text-gray-500">
                Imported NCRB comparison entries. Select an entry to inspect its original source text.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] divide-y divide-gray-200 text-sm">

                <thead class="bg-gray-50">
                    <tr>
                        <th class="w-1/2 px-4 py-3 text-left font-semibold text-gray-700">
                            {{ $direction === 'new_to_old' ? $comparison['new_short'] : $comparison['old_short'] }}
                        </th>

                        <th class="w-1/2 px-4 py-3 text-left font-semibold text-gray-700">
                            {{ $direction === 'new_to_old' ? $comparison['old_short'] : $comparison['new_short'] }}
                        </th>

                        <th class="px-4 py-3 text-left font-semibold text-gray-700">
                            Status
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">

                    @forelse($rows as $row)
                        @php
                            $leftText = (string) $row->left_text;
                            $rightText = (string) $row->right_text;

                            $statusText = [
                                'corresponding' => 'Corresponding',
                                'changed' => 'Changed',
                                'new' => 'New',
                                'deleted' => 'Deleted',
                            ][$row->status] ?? ucfirst($row->status);
                        @endphp

                        <tr class="align-top hover:bg-gray-50">

                            <td class="px-4 py-3 text-gray-800">
                                {{ $leftText !== '' ? $leftText : '—' }}
                            </td>

                            <td class="px-4 py-3 text-gray-800">
                                {{ $rightText !== '' ? $rightText : '—' }}
                            </td>

                            <td class="px-4 py-3">
                                <span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-medium
                                    {{ $row->status === 'changed' ? 'bg-amber-100 text-amber-800' : '' }}
                                    {{ $row->status === 'new' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $row->status === 'deleted' ? 'bg-red-100 text-red-800' : '' }}
                                    {{ $row->status === 'corresponding' ? 'bg-gray-100 text-gray-700' : '' }}">
                                    {{ $statusText }}
                                </span>
                            </td>

                        </tr>

                        <tr class="bg-gray-50/70">
                            <td colspan="3" class="px-4 py-2">
                                <details>
                                    <summary class="cursor-pointer text-xs font-medium text-indigo-700 hover:text-indigo-900">
                                        View original comparison entry
                                    </summary>

                                    <div class="mt-3 grid gap-3 rounded-lg border border-gray-200 bg-white p-3 md:grid-cols-2">

                                        <div>
                                            <div class="mb-1 text-xs font-semibold text-gray-500">
                                                {{ $direction === 'new_to_old' ? $comparison['new_name'] : $comparison['old_name'] }}
                                            </div>
                                            <div class="whitespace-pre-wrap break-words text-sm text-gray-800">
                                                {{ $leftText !== '' ? $leftText : 'No corresponding text supplied in this source row.' }}
                                            </div>
                                        </div>

                                        <div>
                                            <div class="mb-1 text-xs font-semibold text-gray-500">
                                                {{ $direction === 'new_to_old' ? $comparison['old_name'] : $comparison['new_name'] }}
                                            </div>
                                            <div class="whitespace-pre-wrap break-words text-sm text-gray-800">
                                                {{ $rightText !== '' ? $rightText : 'No corresponding text supplied in this source row.' }}
                                            </div>
                                        </div>

                                        <div class="md:col-span-2 border-t border-gray-100 pt-2 text-xs text-gray-500">
                                            Source row: {{ $row->row_order }}
                                            · Status imported: {{ $statusText }}
                                        </div>

                                    </div>
                                </details>
                            </td>
                        </tr>

                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-12 text-center text-sm text-gray-500">
                                No matching comparison entries found.
                            </td>
                        </tr>
                    @endforelse

                </tbody>
            </table>
        </div>

        @if($rows->hasPages())
            <div class="border-t border-gray-200 px-4 py-4">
                {{ $rows->links() }}
            </div>
        @endif

    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-4">
        <h2 class="font-semibold text-gray-900">Related Acts — PDF Documents</h2>

        <div class="mt-3 grid gap-3 md:grid-cols-2">

            <div class="rounded-lg border border-gray-200 p-3">
                <div class="text-sm font-semibold text-gray-800">
                    {{ $comparison['old_name'] }}
                </div>

                <div class="mt-3 flex flex-wrap gap-2">
                    <a href="{{ $comparison['old_pdf'] }}"
                       download
                       class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50">
                        Download PDF
                    </a>

                    <button type="button"
                            data-toggle-open="old-act-viewer"
                            class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-medium text-white hover:bg-indigo-700">
                        View PDF
                    </button>
                </div>

                <details id="old-act-viewer" class="mt-3">
                    <summary class="cursor-pointer text-xs text-indigo-700">
                        PDF viewer
                    </summary>
                    <iframe src="{{ $comparison['old_pdf'] }}"
                            title="{{ $comparison['old_name'] }}"
                            class="mt-3 h-[650px] w-full rounded-lg border border-gray-200"
                            loading="lazy"></iframe>
                </details>
            </div>

            <div class="rounded-lg border border-gray-200 p-3">
                <div class="text-sm font-semibold text-gray-800">
                    {{ $comparison['new_name'] }}
                </div>

                <div class="mt-3 flex flex-wrap gap-2">
                    <a href="{{ $comparison['new_pdf'] }}"
                       download
                       class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50">
                        Download PDF
                    </a>

                    <button type="button"
                            data-toggle-open="new-act-viewer"
                            class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-medium text-white hover:bg-indigo-700">
                        View PDF
                    </button>
                </div>

                <details id="new-act-viewer" class="mt-3">
                    <summary class="cursor-pointer text-xs text-indigo-700">
                        PDF viewer
                    </summary>
                    <iframe src="{{ $comparison['new_pdf'] }}"
                            title="{{ $comparison['new_name'] }}"
                            class="mt-3 h-[650px] w-full rounded-lg border border-gray-200"
                            loading="lazy"></iframe>
                </details>
            </div>

        </div>
    </div>

    <p class="text-xs text-gray-500">From the NCRB comparison table. Always read the Act itself.</p>

</div>
@endsection