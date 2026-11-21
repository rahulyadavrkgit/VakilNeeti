@extends('layouts.app')

@section('title', 'Create account')
@section('page-heading', 'Create account')

@section('content')
<div class="mx-auto max-w-2xl rounded-xl border border-slate-200 bg-white p-8 text-center shadow-sm">
    <div class="mb-4 flex flex-col items-center gap-3"><img src="{{ asset('images/vakilneeti-logo.png') }}" alt="VakilNeeti" class="h-12 w-auto max-w-[220px] rounded bg-white px-2 py-1 object-contain"><h1 class="text-xl font-bold text-slate-900">Create your account</h1></div>
    <p class="mt-2 text-sm text-slate-600">
        Use the account dialog to create your private workspace.
    </p>
</div>
@endsection

