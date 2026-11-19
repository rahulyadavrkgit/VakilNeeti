<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VakilNeeti | Add Client</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-100 text-slate-900">

<div class="flex min-h-screen">

    <aside class="w-64 shrink-0 bg-slate-950 px-5 py-7 text-white">
        <h1 class="mb-10 text-2xl font-bold">VakilNeeti</h1>
        <nav class="space-y-2">
            <a href="/" class="block rounded-lg px-4 py-3 hover:bg-slate-800">Dashboard</a>
            <a href="{{ route('clients.index') }}" class="block rounded-lg bg-blue-600 px-4 py-3 font-semibold">Clients</a>
        </nav>
    </aside>

    <main class="min-w-0 flex-1 p-8">

        <div class="mb-8">
            <a href="{{ route('clients.index') }}" class="text-blue-600 hover:underline">
                ← Back to Clients
            </a>
            <h2 class="mt-4 text-3xl font-bold">Add New Client</h2>
            <p class="mt-2 text-slate-600">Enter the client's basic information.</p>
        </div>

        @if ($errors->any())
            <div class="mb-6 rounded-lg bg-red-100 p-4 text-red-700">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('clients.store') }}" method="POST"
              class="space-y-8 rounded-xl bg-white p-8 shadow-sm">
            @csrf

            <section>
                <h3 class="mb-5 text-xl font-bold">Client Information</h3>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                    <div>
                        <label class="mb-2 block font-medium">Client Type *</label>
                        <select name="client_type" required class="w-full rounded-lg border border-slate-300 p-3">
                            <option value="individual">Individual</option>
                            <option value="company">Company</option>
                            <option value="organisation">Organisation</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block font-medium">First Name *</label>
                        <input type="text" name="first_name" value="{{ old('first_name') }}" required
                               class="w-full rounded-lg border border-slate-300 p-3">
                    </div>

                    <div>
                        <label class="mb-2 block font-medium">Middle Name</label>
                        <input type="text" name="middle_name" value="{{ old('middle_name') }}"
                               class="w-full rounded-lg border border-slate-300 p-3">
                    </div>

                    <div>
                        <label class="mb-2 block font-medium">Last Name</label>
                        <input type="text" name="last_name" value="{{ old('last_name') }}"
                               class="w-full rounded-lg border border-slate-300 p-3">
                    </div>

                    <div>
                        <label class="mb-2 block font-medium">Display Name</label>
                        <input type="text" name="display_name" value="{{ old('display_name') }}"
                               class="w-full rounded-lg border border-slate-300 p-3">
                    </div>

                    <div>
                        <label class="mb-2 block font-medium">Organisation Name</label>
                        <input type="text" name="organisation_name" value="{{ old('organisation_name') }}"
                               class="w-full rounded-lg border border-slate-300 p-3">
                    </div>

                </div>
            </section>

            <section>
                <h3 class="mb-5 text-xl font-bold">Contact Details</h3>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                    <div>
                        <label class="mb-2 block font-medium">Mobile Number</label>
                        <input type="text" name="mobile" value="{{ old('mobile') }}"
                               class="w-full rounded-lg border border-slate-300 p-3">
                    </div>

                    <div>
                        <label class="mb-2 block font-medium">Alternate Mobile</label>
                        <input type="text" name="alternate_mobile" value="{{ old('alternate_mobile') }}"
                               class="w-full rounded-lg border border-slate-300 p-3">
                    </div>

                    <div>
                        <label class="mb-2 block font-medium">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}"
                               class="w-full rounded-lg border border-slate-300 p-3">
                    </div>

                </div>
            </section>

            <section>
                <h3 class="mb-5 text-xl font-bold">Address Details</h3>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                    <div class="md:col-span-2">
                        <label class="mb-2 block font-medium">Full Address</label>
                        <textarea name="address" rows="3"
                                  class="w-full rounded-lg border border-slate-300 p-3">{{ old('address') }}</textarea>
                    </div>

                    <div>
                        <label class="mb-2 block font-medium">City</label>
                        <input type="text" name="city" value="{{ old('city') }}"
                               class="w-full rounded-lg border border-slate-300 p-3">
                    </div>

                    <div>
                        <label class="mb-2 block font-medium">District</label>
                        <input type="text" name="district" value="{{ old('district') }}"
                               class="w-full rounded-lg border border-slate-300 p-3">
                    </div>

                    <div>
                        <label class="mb-2 block font-medium">State</label>
                        <input type="text" name="state" value="{{ old('state') }}"
                               class="w-full rounded-lg border border-slate-300 p-3">
                    </div>

                    <div>
                        <label class="mb-2 block font-medium">PIN Code</label>
                        <input type="text" name="pin_code" value="{{ old('pin_code') }}"
                               class="w-full rounded-lg border border-slate-300 p-3">
                    </div>

                    <div>
                        <label class="mb-2 block font-medium">Country</label>
                        <input type="text" name="country" value="{{ old('country', 'India') }}"
                               class="w-full rounded-lg border border-slate-300 p-3">
                    </div>

                </div>
            </section>

            <section>
                <h3 class="mb-5 text-xl font-bold">Additional Information</h3>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                    <div>
                        <label class="mb-2 block font-medium">Occupation</label>
                        <input type="text" name="occupation" value="{{ old('occupation') }}"
                               class="w-full rounded-lg border border-slate-300 p-3">
                    </div>

                    <div>
                        <label class="mb-2 block font-medium">Referred By</label>
                        <input type="text" name="referred_by" value="{{ old('referred_by') }}"
                               class="w-full rounded-lg border border-slate-300 p-3">
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-2 block font-medium">Notes</label>
                        <textarea name="notes" rows="4"
                                  class="w-full rounded-lg border border-slate-300 p-3">{{ old('notes') }}</textarea>
                    </div>

                </div>
            </section>

            <div class="flex gap-4 border-t border-slate-200 pt-6">
                <button type="submit"
                        class="rounded-lg bg-blue-600 px-6 py-3 font-semibold text-white hover:bg-blue-700">
                    Save Client
                </button>

                <a href="{{ route('clients.index') }}"
                   class="rounded-lg border border-slate-300 px-6 py-3 font-semibold hover:bg-slate-100">
                    Cancel
                </a>
            </div>

        </form>

    </main>
</div>

</body>
</html>