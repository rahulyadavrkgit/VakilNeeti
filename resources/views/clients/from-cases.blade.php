@extends('layouts.app')

@section('title', 'Clients from your cases')
@section('page-heading', 'Clients from your cases')

@section('content')
<div class="mx-auto max-w-4xl space-y-4">
    <x-page-header title="Clients from your cases"
                   subtitle="Tick the people you act for; each becomes a client.">
        <a href="{{ route('clients.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Back to clients</a>
    </x-page-header>

    @if($errors->any())
        <div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    @if($suggestions->isEmpty())
        <p class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-600">
            Every open case already has a client linked, or the case titles do not name the parties.
        </p>
    @else
        <form method="POST" action="{{ route('clients.from-cases.store') }}" class="space-y-3">
            @csrf
            <p class="text-sm text-slate-600">
                Your side is ticked where the case records which side you appear for.
                Cases without a side show both names: tick your client.
            </p>
            <ul class="space-y-2.5">
                @foreach($suggestions as $suggestion)
                    @php $case = $suggestion['case']; @endphp
                    <li class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <a href="{{ route('cases.show', $case) }}" class="font-semibold text-slate-900 hover:text-[#16776e]">{{ $case->case_title }}</a>
                            <x-case-side :side="$case->appearing_for" :parties="$case->appearing_for_parties" :edit-url="route('cases.edit', $case)" />
                        </div>
                        @if($case->case_number)
                            <p class="text-xs text-slate-500">{{ $case->case_number }}</p>
                        @endif
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach($suggestion['names'] as $option)
                                <label class="inline-flex min-h-11 cursor-pointer items-center gap-2 rounded-xl border px-3 text-sm has-[:checked]:border-[#16776e] has-[:checked]:bg-teal-50 {{ $option['ours'] ? 'border-[#16776e]' : 'border-slate-200' }}">
                                    <input type="checkbox" name="pick[]" value="{{ $case->id }}|{{ $option['name'] }}" @checked($option['ours'])
                                           class="rounded border-slate-300 text-[#16776e] focus:ring-[#16776e]">
                                    <span class="font-semibold text-slate-900">{{ $option['name'] }}</span>
                                    <span class="text-xs text-slate-500">{{ $option['ours'] ? 'our side' : $option['label'] }}</span>
                                </label>
                            @endforeach
                        </div>
                    </li>
                @endforeach
            </ul>
            <div class="sticky bottom-20 flex justify-end md:bottom-4">
                <button type="submit" class="min-h-11 rounded-xl bg-[#16776e] px-5 text-sm font-semibold text-white shadow-lg hover:bg-[#125f58]">Add ticked people as clients</button>
            </div>
        </form>
    @endif
</div>
@endsection
