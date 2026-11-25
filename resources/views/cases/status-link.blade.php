{{-- Client status link: what a client sees when the advocate sends them this case's link. Read-only, nothing private. --}}
@php
    $nextDate = $next['date'] ?? null;
    $nextHearing = $next['hearing'] ?? null;
    $room = $nextHearing?->court_room ?: $case->court_room;
@endphp
<!DOCTYPE html>
<html lang="en-IN" data-theme="system">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Case update · {{ $case->case_title }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-mark.svg') }}">
    @include('layouts.partials.theme-script')
    @vite(['resources/css/app.css'])
    <style nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
        body { background: #f6f5f1; color: #172536; font-family: var(--font-sans), sans-serif; }
        html.dark body { background: #0d1524; color: #e3e9f1; }
    </style>
</head>
<body class="min-h-screen">
<main class="mx-auto max-w-lg space-y-4 p-4 sm:p-6">
    <header class="flex items-center gap-2 pt-2">
        <x-brand :size="30" />
    </header>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Case update</p>
        <h1 class="mt-1 text-xl font-semibold leading-snug text-slate-900">{{ $case->case_title }}</h1>
        <p class="mt-1 text-sm text-slate-600">{{ collect([$case->case_number, $case->court_name])->filter()->implode(' · ') }}</p>
    </section>

    <section class="rounded-2xl border p-5 shadow-sm {{ $nextDate?->isToday() ? 'border-red-200 bg-red-50' : 'border-slate-200 bg-white' }}">
        <p class="text-xs font-bold uppercase tracking-wide {{ $nextDate?->isToday() ? 'text-red-700' : 'text-slate-500' }}">Next date</p>
        @if($nextDate)
            <p class="mt-1 text-2xl font-bold text-slate-900">
                {{ $nextDate->isToday() ? 'Today' : ($nextDate->isTomorrow() ? 'Tomorrow' : $nextDate->format('D, d M Y')) }}
            </p>
            <p class="mt-1 text-sm text-slate-700">
                {{ collect([$nextHearing?->hearing_time ? substr((string) $nextHearing->hearing_time, 0, 5) : null, $nextHearing?->hearing_stage, $room ? 'Room '.$room : null])->filter()->implode(' · ') }}
            </p>
        @else
            <p class="mt-1 text-lg font-semibold text-slate-900">Not fixed yet</p>
            <p class="mt-1 text-sm text-slate-600">Your advocate will update this when the court gives the next date.</p>
        @endif
    </section>

    @if($last)
        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Last hearing</p>
            <p class="mt-1 font-semibold text-slate-900">{{ $last['date']->format('d M Y') }}</p>
            @if($last['hearing']->hearing_stage)
                <p class="text-sm text-slate-600">{{ $last['hearing']->hearing_stage }}</p>
            @endif
        </section>
    @endif

    <p class="px-1 text-xs leading-relaxed text-slate-500">
        Shared by {{ $advocate?->name ?: 'your advocate' }} through VakilNiti · updated {{ now()->format('d M Y, H:i') }}.
        Dates can change in court; please confirm with your advocate.
    </p>
</main>
</body>
</html>
