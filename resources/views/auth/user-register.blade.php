@extends('layouts.app')

@section('title', 'Create account')
@section('page-heading', 'Create account')

@section('content')
<div class="mx-auto max-w-2xl rounded-xl border border-slate-200 bg-white p-8 text-center shadow-sm">
    <div class="mb-4 flex flex-col items-center gap-3"><img src="{{ asset('images/logo.svg') }}" alt="VakilNiti" class="h-12 w-auto max-w-[220px] object-contain"><h1 class="text-xl font-bold text-slate-900">Create your account</h1></div>
    <p class="mt-2 text-sm text-slate-600">
        Use the account dialog to create your private workspace.
    </p>
</div>
@endsection

