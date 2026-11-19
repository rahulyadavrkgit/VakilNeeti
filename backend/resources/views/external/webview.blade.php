@extends('layouts.app')

@section('title', 'External Website')
@section('page-heading', 'External Website')

@section('content')
<div class="mx-auto max-w-[1600px] space-y-4">
    <section class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="min-w-0">
            <p class="text-xs font-bold uppercase tracking-wider text-blue-700">VakilNeeti Webview</p>
            <p class="mt-1 max-w-3xl truncate text-sm font-medium text-slate-700" title="{{ $url }}">
                {{ $host }}
            </p>
        </div>
        <div class="flex gap-2">
            <button type="button" onclick="vnCloseWebview()"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Close
            </button>
            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer"
               class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">
                Open in browser
            </a>
        </div>
    </section>

    <section class="overflow-hidden rounded-xl border border-slate-300 bg-white shadow-sm">
        <iframe
            id="vn-external-frame"
            src="{{ $url }}"
            title="External website: {{ $host }}"
            class="h-[calc(100vh-220px)] min-h-[500px] w-full"
            referrerpolicy="strict-origin-when-cross-origin"
            sandbox="allow-same-origin allow-scripts allow-forms allow-popups allow-popups-to-escape-sandbox"
        ></iframe>
    </section>

    <p class="text-xs text-slate-500">
        The website controls whether it can be displayed inside a webview. If it does not load, use “Open in browser”.
    </p>
</div>

@push('scripts')
<script>
    function vnCloseWebview() {
        if (window.history.length > 1) {
            window.history.back();
        } else {
            window.location.assign(@json(route('dashboard')));
        }
    }
</script>
@endpush
@endsection
