<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <title>Initial Administrator Setup | VakilNiti</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 px-4 py-10">
    <main class="mx-auto max-w-md rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-4"><img src="{{ asset('images/logo.svg') }}" alt="VakilNiti" class="h-12 w-auto max-w-[220px] object-contain"></div>
        <h1 class="text-xl font-bold text-slate-800">Initial Administrator Setup</h1>

        <p class="mt-2 text-sm text-slate-600">Create the first administrator. This page closes after that.</p>

        @if ($errors->any())
            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.initial-setup.store') }}" class="mt-6 space-y-4">
            @csrf

            <div>
                <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Administrator name</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required
                       autocomplete="name"
                       class="w-full rounded-lg border-slate-300 text-sm">
            </div>

            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email address</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required
                       autocomplete="username"
                       class="w-full rounded-lg border-slate-300 text-sm">
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm font-medium text-slate-700">Password</label>
                <input id="password" name="password" type="password" required minlength="8"
                       autocomplete="new-password"
                       class="w-full rounded-lg border-slate-300 text-sm">
            </div>

            <div>
                <label for="password_confirmation" class="mb-1 block text-sm font-medium text-slate-700">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8"
                       autocomplete="new-password"
                       class="w-full rounded-lg border-slate-300 text-sm">
            </div>

            <button type="submit"
                    class="w-full rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-800">
                Create administrator account
            </button>
        </form>
    </main>
</body>
</html>

