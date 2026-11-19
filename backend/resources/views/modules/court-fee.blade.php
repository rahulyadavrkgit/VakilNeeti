@extends('layouts.app')

@section('title', 'Court Fee')
@section('page-heading', 'Court Fee')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <section id="overview" class="rounded-2xl bg-slate-900 p-6 text-white shadow-sm">
        <p class="text-xs font-bold uppercase tracking-widest text-blue-300">VakilNeeti Legal Resources</p>
        <h1 class="mt-2 text-2xl font-bold">Court Fee Resources &amp; Estimator</h1>
        <p class="mt-2 max-w-3xl text-sm text-slate-300">
            Review filing considerations and make a transparent, user-rate-based estimate before checking the applicable official schedule.
        </p>
    </section>

    <div role="note" class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
        Court fees depend on the State or Union Territory, court, proceeding, relief, valuation and current amendments.
        This page does not contain official fee tables and its estimator is illustrative only. Confirm the amount with the
        current applicable law, court registry or official e-filing service before filing.
    </div>

    <section id="calculator" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-5">
            <h2 class="text-lg font-semibold text-slate-900">Illustrative flat-rate estimator</h2>
            <p class="mt-1 text-sm text-slate-600">
                Enter a rate from a verified schedule. This simple calculation does not model slabs, fixed fees, exemptions or other statutory rules.
            </p>
        </div>

        <form id="court-fee-estimator" class="grid items-end gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div>
                <label for="claim-value" class="mb-1 block text-sm font-medium text-slate-700">Claim value (INR)</label>
                <input id="claim-value" type="number" min="0" step="0.01" required
                       class="w-full rounded-lg border-slate-300 text-sm" placeholder="e.g. 100000">
            </div>
            <div>
                <label for="fee-rate" class="mb-1 block text-sm font-medium text-slate-700">Verified rate (%)</label>
                <input id="fee-rate" type="number" min="0" step="0.0001" required
                       class="w-full rounded-lg border-slate-300 text-sm" placeholder="Enter the rate">
            </div>
            <div>
                <label for="minimum-fee" class="mb-1 block text-sm font-medium text-slate-700">Minimum fee (INR, optional)</label>
                <input id="minimum-fee" type="number" min="0" step="0.01"
                       class="w-full rounded-lg border-slate-300 text-sm" placeholder="No minimum">
            </div>
            <div>
                <label for="maximum-fee" class="mb-1 block text-sm font-medium text-slate-700">Maximum fee (INR, optional)</label>
                <input id="maximum-fee" type="number" min="0" step="0.01"
                       class="w-full rounded-lg border-slate-300 text-sm" placeholder="No maximum">
            </div>
            <div class="md:col-span-2 xl:col-span-4">
                <button type="submit" class="rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">
                    Estimate
                </button>
                <button type="reset" class="ml-2 rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Clear
                </button>
            </div>
        </form>

        <p id="fee-estimate" role="status" aria-live="polite" hidden
           class="mt-4 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm font-semibold text-blue-900"></p>
    </section>

    <section id="fee-schedules" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">Check the applicable schedule</h2>
        <p class="mt-1 text-sm text-slate-600">
            Verify each of these details against the current official source before relying on a fee amount.
        </p>
        <ul class="mt-4 grid gap-3 text-sm text-slate-700 md:grid-cols-2">
            <li class="rounded-lg bg-slate-50 p-4">State or Union Territory and the court where filing will occur.</li>
            <li class="rounded-lg bg-slate-50 p-4">Proceeding type, relief claimed and the applicable valuation method.</li>
            <li class="rounded-lg bg-slate-50 p-4">Current State amendments, rules, notifications and court-specific directions.</li>
            <li class="rounded-lg bg-slate-50 p-4">Any minimums, caps, exemptions, fixed components or rounding rules.</li>
        </ul>
    </section>

    <section id="rules" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">Official references</h2>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <a href="https://www.indiacode.nic.in/" target="_blank" rel="noopener noreferrer"
               class="rounded-lg border border-slate-200 p-4 hover:border-blue-300 hover:bg-blue-50">
                <span class="font-semibold text-blue-800">India Code</span>
                <span class="mt-1 block text-sm text-slate-600">Search official Central Acts and check for applicable State amendments.</span>
            </a>
            <a href="https://efiling.ecourts.gov.in/" target="_blank" rel="noopener noreferrer"
               class="rounded-lg border border-slate-200 p-4 hover:border-blue-300 hover:bg-blue-50">
                <span class="font-semibold text-blue-800">eCourts e-Filing</span>
                <span class="mt-1 block text-sm text-slate-600">Check the official filing workflow and fee details for supported courts.</span>
            </a>
        </div>
        <p class="mt-4 text-xs text-slate-500">
            This estimator is a planning aid, not legal advice or an official court-fee determination.
        </p>
    </section>
</div>
@endsection

@push('scripts')
<script>
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
            feeEstimate.textContent = 'Enter valid non-negative amounts and ensure the maximum is not less than the minimum.';
            feeEstimate.hidden = false;
            return;
        }

        let estimate = Math.max(claimValue * rate / 100, minimum);
        if (maximum !== null) {
            estimate = Math.min(estimate, maximum);
        }

        const formattedEstimate = new Intl.NumberFormat('en-IN', {
            style: 'currency',
            currency: 'INR',
            maximumFractionDigits: 2
        }).format(estimate);

        feeEstimate.textContent = 'Illustrative flat-rate estimate: ' + formattedEstimate + '. Verify against the current official schedule.';
        feeEstimate.hidden = false;
    });

    feeEstimator.addEventListener('reset', function () {
        feeEstimate.textContent = '';
        feeEstimate.hidden = true;
    });
</script>
@endpush
