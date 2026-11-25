@extends('layouts.app')
@section('title', 'Verify administrator sign-in')
@section('page-heading', 'Verify administrator sign-in')

@section('content')
    <form method="POST" action="{{ route('admin.verify.submit') }}" class="mx-auto max-w-md space-y-4 rounded-xl border border-slate-200 bg-white p-5">
        @csrf
        <h1 class="text-xl font-bold">{{ $localCode ? 'Enter the development code' : 'Check your email' }}</h1>
        <p class="text-sm text-slate-600">
            @if($localCode)
                Email delivery is unconfigured. Find the six-digit code in the local Laravel log and enter it within five minutes.
            @else
                Enter the six-digit code from your email within five minutes.
            @endif
        </p>
        <label class="block text-sm font-medium">Verification code
            <input name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required autofocus class="mt-1 block min-h-11 w-full rounded-lg border-slate-300">
        </label>
        @if($setup)
            <label class="block text-sm font-medium">New administrator password
                <input type="password" name="password" autocomplete="new-password" minlength="12" maxlength="72" required class="mt-1 block min-h-11 w-full rounded-lg border-slate-300">
            </label>
            <label class="block text-sm font-medium">Confirm password
                <input type="password" name="password_confirmation" autocomplete="new-password" minlength="12" maxlength="72" required class="mt-1 block min-h-11 w-full rounded-lg border-slate-300">
            </label>
        @endif
        @foreach($errors->all() as $error)<p role="alert" class="text-sm text-red-700">{{ $error }}</p>@endforeach
        <button class="min-h-11 rounded-lg bg-teal-700 px-4 font-semibold text-white">Verify and sign in</button>
        <a href="{{ route('admin.login') }}" class="ml-2 text-sm underline">Start over</a>
    </form>
@endsection
