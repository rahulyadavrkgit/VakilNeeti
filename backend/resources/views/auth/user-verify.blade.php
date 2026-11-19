@extends('layouts.app')

@section('content')
<div class="max-w-lg mx-auto p-6">
    <h1 class="text-2xl font-bold mb-2">Verify your account</h1>

    @if (session('status'))
        <div class="mb-4 rounded bg-green-50 p-3 text-green-800">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded border border-red-300 bg-red-50 p-3 text-red-700">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    <p class="mb-5 text-gray-600">Enter the six-digit OTP sent to your selected contact method.</p>

    <form method="POST" action="{{ route('register.verify.submit') }}" class="space-y-4">
        @csrf
        <label class="block font-medium">Six-digit OTP</label>
        <input name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
            required autocomplete="one-time-code" class="w-full rounded border p-3 text-center text-xl tracking-widest">
        <button class="w-full rounded bg-blue-700 px-4 py-3 font-semibold text-white">
            Verify and create account
        </button>
    </form>

    <form method="POST" action="{{ route('register.resend-otp') }}" class="mt-4">
        @csrf
        <button class="text-blue-700 underline">Resend OTP</button>
    </form>

    <p class="mt-5 text-sm"><a href="{{ route('register') }}" class="text-blue-700 underline">Start again</a></p>
</div>
@endsection