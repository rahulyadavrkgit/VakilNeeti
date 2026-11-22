@extends('layouts.app')

@section('content')
<div class="mx-auto mt-10 max-w-md rounded-xl border border-slate-200 bg-white p-6 shadow-sm">

    <div class="mb-4"><img src="{{ asset('images/VakilNiti-logo.png') }}" alt="VakilNiti" class="h-12 w-auto max-w-[220px] rounded bg-white px-2 py-1 object-contain"></div>
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

        <div>
            <label for="email" class="mb-1 block text-sm font-medium text-slate-700">
                Email address
            </label>
            <input id="email" name="email" type="email"
                   value="{{ old('email') }}" required autofocus
                   autocomplete="username"
                   class="w-full rounded-lg border-slate-300 text-sm">
        </div>

        <div>
            <label for="password" class="mb-1 block text-sm font-medium text-slate-700">
                Password
            </label>
            <input id="password" name="password" type="password" required
                   autocomplete="current-password"
                   class="w-full rounded-lg border-slate-300 text-sm">
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="remember" value="1"
                   class="rounded border-slate-300">
            Remember me
        </label>

        <button type="submit"
                class="w-full rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-800">
            Sign in
        </button>
    </form>
</div>
@endsection
