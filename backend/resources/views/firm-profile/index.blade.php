@extends('layouts.app')

@section('title', 'Firm Profile')
@section('page-heading', 'Firm Profile')

@section('content')

<div class="max-w-5xl mx-auto space-y-6">

    <div>
        <h2 class="text-2xl font-bold text-slate-800">Firm Information</h2>
        <p class="mt-1 text-sm text-slate-500">
            Manage the business details displayed on your invoices.
        </p>
    </div>

    @if(session('success'))
        <div class="rounded-lg bg-green-100 border border-green-200 px-4 py-3 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-lg bg-red-100 border border-red-200 px-4 py-3 text-red-800">
            <p class="font-semibold">Please correct the following:</p>
            <ul class="list-disc pl-5 mt-2 text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('firm-profile.update') }}"
          class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-8">

        @csrf

        <section>
            <h3 class="text-lg font-semibold text-slate-800 border-b pb-3 mb-5">
                Firm Identity
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                <div>
                    <label class="block text-sm font-medium mb-2">Firm Name *</label>
                    <input type="text" name="firm_name"
                           value="{{ old('firm_name', $firm->firm_name ?? '') }}"
                           required
                           class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Proprietor / Owner Name</label>
                    <input type="text" name="proprietor_name"
                           value="{{ old('proprietor_name', $firm->proprietor_name ?? '') }}"
                           class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                </div>

            </div>
        </section>

        <section>
            <h3 class="text-lg font-semibold text-slate-800 border-b pb-3 mb-5">
                Business Address
            </h3>

            <div class="space-y-5">

                <div>
                    <label class="block text-sm font-medium mb-2">Address</label>
                    <textarea name="address" rows="3"
                              class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">{{ old('address', $firm->address ?? '') }}</textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                    <div>
                        <label class="block text-sm font-medium mb-2">City</label>
                        <input type="text" name="city"
                               value="{{ old('city', $firm->city ?? '') }}"
                               class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">District</label>
                        <input type="text" name="district"
                               value="{{ old('district', $firm->district ?? '') }}"
                               class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">State</label>
                        <input type="text" name="state"
                               value="{{ old('state', $firm->state ?? '') }}"
                               class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">GST State Code</label>
                        <input type="text" name="state_code" maxlength="2"
                               value="{{ old('state_code', $firm->state_code ?? '') }}"
                               placeholder="e.g. 09"
                               class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">PIN Code</label>
                        <input type="text" name="pincode" maxlength="10"
                               value="{{ old('pincode', $firm->pincode ?? '') }}"
                               class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                    </div>

                </div>
            </div>
        </section>

        <section>
            <h3 class="text-lg font-semibold text-slate-800 border-b pb-3 mb-5">
                GST and Contact Details
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                <div>
                    <label class="block text-sm font-medium mb-2">GSTIN</label>
                    <input type="text" name="gstin" maxlength="15"
                           value="{{ old('gstin', $firm->gstin ?? '') }}"
                           placeholder="15-character GSTIN"
                           class="w-full rounded-lg border-slate-300 uppercase focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Email</label>
                    <input type="email" name="email"
                           value="{{ old('email', $firm->email ?? '') }}"
                           class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Phone</label>
                    <input type="text" name="phone"
                           value="{{ old('phone', $firm->phone ?? '') }}"
                           class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                </div>

            </div>

            <p class="mt-4 text-xs text-slate-500">
                Enter the GSTIN and registered business details exactly as recorded
                in your GST registration certificate.
            </p>
        </section>

        <div class="flex justify-end border-t pt-5">
            <button type="submit"
                    class="rounded-lg bg-blue-700 px-6 py-3 text-white font-medium hover:bg-blue-800">
                Save Firm Profile
            </button>
        </div>

    </form>

</div>

@endsection