@extends('layouts.app')

@section('title', $guide ? 'Court fee: '.$guide['title'] : 'Court fee by State and court')
@section('meta_description', 'Court fee for the Supreme Court, every High Court and District Courts in each State: the governing law, online payment and an estimator.')
@section('page-heading', 'Court fee')

@php
    $chip = 'inline-flex min-h-11 items-center justify-center rounded-xl px-4 text-sm font-semibold transition';
    $field = 'w-full rounded-lg border-slate-300 text-sm';
@endphp

@section('content')
<div class="mx-auto max-w-4xl space-y-4">
    <h1 class="text-2xl font-bold text-slate-900">Court fee</h1>

    {{-- 1. Court --}}
    <nav aria-label="Court" class="grid grid-cols-3 gap-2">
        @foreach(\App\Services\CourtFeeGuide::COURTS as $courtKey => $courtLabel)
            <a href="{{ route('court-fee.index', array_filter(['court' => $courtKey, 'state' => $courtKey === 'supreme' ? null : $state])) }}"
               aria-current="{{ $court === $courtKey ? 'page' : 'false' }}"
               class="{{ $chip }} {{ $court === $courtKey ? 'bg-[#16776e] text-white shadow-sm' : 'border border-slate-200 bg-white text-slate-700 hover:border-[#16776e]' }}">{{ $courtLabel }}</a>
        @endforeach
    </nav>

    {{-- 2. State (High Court and District Court) --}}
    @if($court !== 'supreme')
        <form method="GET" action="{{ route('court-fee.index') }}" class="flex gap-2">
            <input type="hidden" name="court" value="{{ $court }}">
            <label class="flex-1">
                <span class="sr-only">State or Union Territory</span>
                <select name="state" data-autosubmit class="min-h-11 w-full rounded-xl border-slate-300 bg-white text-sm">
                    <option value="">Choose State / UT</option>
                    @foreach($states as $stateOption)
                        <option value="{{ $stateOption }}" @selected($state === $stateOption)>{{ $stateOption }}</option>
                    @endforeach
                </select>
            </label>
            <noscript><button type="submit" class="min-h-11 rounded-xl bg-[#16776e] px-4 text-sm font-semibold text-white">Show</button></noscript>
        </form>
    @endif

    {{-- 3. Where the fee comes from and where to pay --}}
    @if($guide)
        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5" aria-labelledby="fee-court">
            <h2 id="fee-court" class="text-lg font-semibold text-slate-900">{{ $guide['title'] }}</h2>
            <p class="mt-1 text-sm text-slate-600">Fee set by: <strong class="text-slate-900">{{ $guide['law'] }}</strong></p>
            <div class="mt-4 grid gap-2 sm:grid-cols-2">
                @foreach($guide['links'] as $officialLink)
                    <a href="{{ $officialLink['url'] }}" target="_blank" rel="noopener noreferrer" data-direct-share
                       class="flex min-h-14 items-center justify-between gap-3 rounded-xl border border-slate-200 px-4 py-2.5 hover:border-[#16776e] hover:bg-teal-50/50">
                        <span>
                            <span class="block text-sm font-semibold text-slate-900">{{ $officialLink['label'] }}</span>
                            <span class="block text-xs text-slate-500">{{ $officialLink['hint'] }}</span>
                        </span>
                        <x-icon name="external" :size="16" class="text-slate-400" />
                    </a>
                @endforeach
            </div>
        </section>
    @else
        <p class="rounded-2xl border border-dashed border-slate-300 bg-white p-5 text-center text-sm text-slate-500">Choose a State to see its court fee law and payment links.</p>
    @endif

    {{-- 4. Estimate an ad valorem fee from the rate in the schedule --}}
    <section id="calculator" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <h2 class="text-lg font-semibold text-slate-900">Estimate</h2>
        <form id="court-fee-estimator" class="mt-3 grid items-end gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <label class="text-sm font-medium text-slate-700">Claim value (Rs.)
                <input id="claim-value" type="number" min="0" step="0.01" required inputmode="decimal" class="mt-1 {{ $field }}" placeholder="100000">
            </label>
            <label class="text-sm font-medium text-slate-700">Rate from schedule (%)
                <input id="fee-rate" type="number" min="0" step="0.0001" required inputmode="decimal" class="mt-1 {{ $field }}" placeholder="e.g. 1">
            </label>
            <label class="text-sm font-medium text-slate-700">Minimum (Rs.)
                <input id="minimum-fee" type="number" min="0" step="0.01" inputmode="decimal" class="mt-1 {{ $field }}" placeholder="Optional">
            </label>
            <label class="text-sm font-medium text-slate-700">Maximum (Rs.)
                <input id="maximum-fee" type="number" min="0" step="0.01" inputmode="decimal" class="mt-1 {{ $field }}" placeholder="Optional">
            </label>
            <div class="flex gap-2 sm:col-span-2 lg:col-span-4">
                <button type="submit" class="min-h-11 rounded-xl bg-[#16776e] px-5 text-sm font-semibold text-white hover:bg-[#125f58]">Estimate</button>
                <button type="reset" class="min-h-11 rounded-xl border border-slate-300 px-5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Clear</button>
            </div>
        </form>
        <p id="fee-estimate" role="status" aria-live="polite" hidden class="mt-3 rounded-xl bg-teal-50 p-4 text-base font-bold text-[#12645d]"></p>
    </section>

    <p class="text-xs text-slate-500">Fees, slabs and exemptions change by State notification. Check the official law before paying.</p>
</div>
@endsection

@push('scripts')
<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    const feeEstimator = document.getElementById('court-fee-estimator');
    const feeEstimate = document.getElementById('fee-estimate');

    feeEstimator.addEventListener('submit', function (event) {
        event.preventDefault();

        const claimValue = Number(document.getElementById('claim-value').value);
        const rate = Number(document.getElementById('fee-rate').value);
        const minimumInput = document.getElementById('minimum-fee').value;
        const maximumInput = document.getElementById('maximum-fee').value;
        const minimum = minimumInput === '' ? 0 : Number(minimumInput);
        const maximum = maximumInput === '' ? null : Number(maximumInput);

        if (![claimValue, rate, minimum].every(Number.isFinite) ||
            (maximum !== null && !Number.isFinite(maximum)) ||
            claimValue < 0 || rate < 0 || minimum < 0 ||
            (maximum !== null && (maximum < 0 || maximum < minimum))) {
            feeEstimate.textContent = 'Check the amounts: none can be negative and the maximum cannot be below the minimum.';
            feeEstimate.hidden = false;
            return;
        }

        let estimate = Math.max(claimValue * rate / 100, minimum);
        if (maximum !== null) {
            estimate = Math.min(estimate, maximum);
        }

        feeEstimate.textContent = 'About Rs. ' + new Intl.NumberFormat('en-IN', { maximumFractionDigits: 2 }).format(estimate);
        feeEstimate.hidden = false;
    });

    feeEstimator.addEventListener('reset', function () {
        feeEstimate.textContent = '';
        feeEstimate.hidden = true;
    });
</script>
@endpush
