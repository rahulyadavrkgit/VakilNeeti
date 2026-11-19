@extends('layouts.app')

@section('content')
<div class="max-w-lg mx-auto p-6">
    <h1 class="text-2xl font-bold mb-2">User sign in</h1>
    <p class="text-sm text-gray-600 mb-6">Sign in using your verified email address or mobile number.</p>

    @if (session('status'))
        <div class="mb-4 rounded bg-green-50 p-3 text-green-800">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded border border-red-300 bg-red-50 p-3 text-red-700">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('login.submit') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block font-medium mb-1">Email or mobile number</label>
            <input name="identifier" value="{{ old('identifier') }}" required
                class="w-full rounded border p-3" autocomplete="username">
        </div>

        <div>
            <label class="block font-medium mb-1">Password</label>
            <input type="password" name="password" required
                class="w-full rounded border p-3" autocomplete="current-password">
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="remember" value="1"> Remember me
        </label>

        <button class="w-full rounded bg-blue-700 px-4 py-3 font-semibold text-white">
            Sign in
        </button>
    </form>

    <p class="mt-5 text-sm">New to VakilNeeti?
        <a href="{{ route('register') }}" class="text-blue-700 underline">Create an account</a>
    </p>
    <p class="mt-3 text-sm">
        <a href="{{ route('admin.login') }}" class="text-gray-600 underline">Administrator sign in</a>
    </p>
</div>
@endsection