@extends('layouts.app')

@section('title', 'Admin log in')
@section('page-heading', 'Admin log in')

@section('content')
<div class="mx-auto mt-10 max-w-md rounded-xl border border-slate-200 bg-white p-6 shadow-sm">

    <div class="mb-4"><img src="{{ asset('images/logo.svg') }}" alt="VakilNiti" class="h-12 w-auto max-w-[220px] object-contain"></div>
        <h1 class="text-xl font-bold text-slate-800">Administrator Login</h1>

    <p class="mt-2 text-sm text-slate-600">
        Sign in using an authorised VakilNiti administrator account.
    </p>

    @if($errors->any())
        <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('admin.login.submit') }}" class="mt-6 space-y-4">
        @csrf
        @php $setup = request()->boolean('setup') || old('setup') === '1'; @endphp
        @if($setup)<input type="hidden" name="setup" value="1">@endif

        <div>
            <label for="email" class="mb-1 block text-sm font-medium text-slate-700">
                Email address
            </label>
            <input id="email" name="email" type="email"
                   value="{{ old('email') }}" required autofocus
                   autocomplete="username"
                   class="w-full rounded-lg border-slate-300 text-sm">
        </div>

        @unless($setup)
        <div>
            <label for="password" class="mb-1 block text-sm font-medium text-slate-700">
                Password
            </label>
            <input id="password" name="password" type="password" required
                   autocomplete="current-password"
                   class="w-full rounded-lg border-slate-300 text-sm">
        </div>

        @endunless
        <button type="submit"
                class="w-full rounded-lg bg-[#16776e] px-4 py-2.5 text-sm font-medium text-white hover:bg-[#125f58]">
            {{ $setup ? 'Send setup code' : 'Sign in' }}
        </button>
    </form>
    <a href="{{ route('admin.login', $setup ? [] : ['setup' => 1]) }}" class="mt-4 inline-flex min-h-11 items-center text-sm font-semibold underline">{{ $setup ? 'Back to administrator sign-in' : 'Set up a newly granted administrator account' }}</a>
</div>
@endsection
