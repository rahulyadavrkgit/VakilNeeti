@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-2">Create your VakilNeeti account</h1>
    <p class="text-sm text-gray-600 mb-6">Register using either your email address or mobile number.</p>

    @if ($errors->any())
        <div class="mb-4 rounded border border-red-300 bg-red-50 p-3 text-red-700">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('register.send-otp') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block font-medium mb-1">Full name</label>
            <input name="name" value="{{ old('name') }}" required maxlength="150"
                class="w-full rounded border p-3" autocomplete="name">
        </div>

        <div>
            <label class="block font-medium mb-1">Register using</label>
            <select name="channel" id="registration-channel" class="w-full rounded border p-3" required>
                <option value="email" {{ old('channel', 'email') === 'email' ? 'selected' : '' }}>Email address</option>
                <option value="mobile" {{ old('channel') === 'mobile' ? 'selected' : '' }}>Mobile number</option>
            </select>
        </div>

        <div id="email-field">
            <label class="block font-medium mb-1">Email address</label>
            <input type="email" name="email" value="{{ old('email') }}"
                class="w-full rounded border p-3" autocomplete="email">
        </div>

        <div id="mobile-field" class="hidden">
            <label class="block font-medium mb-1">Mobile number</label>
            <input type="tel" name="mobile" value="{{ old('mobile') }}"
                placeholder="10-digit Indian mobile number or +91..." class="w-full rounded border p-3"
                autocomplete="tel">
        </div>

        <div>
            <label class="block font-medium mb-1">Password</label>
            <input type="password" name="password" required minlength="8"
                class="w-full rounded border p-3" autocomplete="new-password">
        </div>

        <div>
            <label class="block font-medium mb-1">Confirm password</label>
            <input type="password" name="password_confirmation" required minlength="8"
                class="w-full rounded border p-3" autocomplete="new-password">
        </div>

        <button class="w-full rounded bg-blue-700 px-4 py-3 font-semibold text-white">
            Send verification OTP
        </button>
    </form>

    <p class="mt-5 text-sm">Already registered?
        <a href="{{ route('login') }}" class="text-blue-700 underline">Sign in</a>
    </p>
</div>

<script>
(function () {
    const channel = document.getElementById('registration-channel');
    const email = document.getElementById('email-field');
    const mobile = document.getElementById('mobile-field');
    const emailInput = email.querySelector('input');
    const mobileInput = mobile.querySelector('input');

    function updateFields() {
        const isEmail = channel.value === 'email';
        email.classList.toggle('hidden', !isEmail);
        mobile.classList.toggle('hidden', isEmail);
        emailInput.required = isEmail;
        mobileInput.required = !isEmail;
    }

    channel.addEventListener('change', updateFields);
    updateFields();
})();
</script>
@endsection