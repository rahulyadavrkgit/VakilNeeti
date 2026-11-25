@extends('layouts.app')

@section('title', 'New Criminal Laws: BNS, BNSS and BSA')
@section('page-heading', 'New Criminal Laws: BNS, BNSS and BSA')
@section('meta_description', 'Bharatiya Nyaya Sanhita, Bharatiya Nagarik Suraksha Sanhita and Bharatiya Sakshya Adhiniyam: overview and section-by-section comparison with the IPC, CrPC and Evidence Act.')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">

    <x-page-header title="New Criminal Laws" subtitle="BNS, BNSS and BSA: compare the old and new sections." />

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">

        <a href="{{ route('new-criminal-laws.compare', ['pair' => 'bns-ipc']) }}"
           class="rounded-xl border border-slate-200 bg-white p-5 transition hover:border-indigo-400 hover:shadow-sm">
            <h2 class="text-xl font-bold text-slate-900">BNS 2023</h2>
            <p class="mt-1 text-sm text-slate-600">Compared with IPC 1860</p>
            <p class="mt-5 text-sm font-semibold text-indigo-700">
                Compare Sections →
            </p>
        </a>

        <a href="{{ route('new-criminal-laws.compare', ['pair' => 'bnss-crpc']) }}"
           class="rounded-xl border border-slate-200 bg-white p-5 transition hover:border-indigo-400 hover:shadow-sm">
            <h2 class="text-xl font-bold text-slate-900">BNSS 2023</h2>
            <p class="mt-1 text-sm text-slate-600">Compared with CrPC 1973</p>
            <p class="mt-5 text-sm font-semibold text-indigo-700">
                Compare Sections →
            </p>
        </a>

        <a href="{{ route('new-criminal-laws.compare', ['pair' => 'bsa-iea']) }}"
           class="rounded-xl border border-slate-200 bg-white p-5 transition hover:border-indigo-400 hover:shadow-sm">
            <h2 class="text-xl font-bold text-slate-900">BSA 2023</h2>
            <p class="mt-1 text-sm text-slate-600">Compared with Evidence Act 1872</p>
            <p class="mt-5 text-sm font-semibold text-indigo-700">
                Compare Sections →
            </p>
        </a>

    </div>

</div>
@endsection