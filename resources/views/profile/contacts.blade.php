@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Contact Information</h1>
            <p class="mt-1 text-sm text-slate-500">
                View existing profile contacts and maintain additional office contact channels.
            </p>
        </div>

        <a href="{{ route('profile.index') }}"
           class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
            Back to Profile
        </a>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-slate-800">Firm Contact Details</h2>

            <div class="mt-4 space-y-3 text-sm">
                <div>
                    <p class="text-slate-500">Firm Email</p>
                    <p class="font-medium text-slate-800">{{ $firm->email ?: 'Not provided' }}</p>
                </div>

                <div>
                    <p class="text-slate-500">Firm Phone</p>
                    <p class="font-medium text-slate-800">{{ $firm->phone ?: 'Not provided' }}</p>
                </div>

                <div>
                    <p class="text-slate-500">Office Address</p>
                    <p class="font-medium text-slate-800">
                        {{ $firm->address ?: 'Not provided' }}
                        {{ $firm->city ? ', '.$firm->city : '' }}
                        {{ $firm->district ? ', '.$firm->district : '' }}
                        {{ $firm->state ? ', '.$firm->state : '' }}
                        {{ $firm->pincode ? ' - '.$firm->pincode : '' }}
                    </p>
                </div>
            </div>

            <a href="{{ route('firm-profile.index') }}"
               class="mt-5 inline-flex text-sm font-medium text-indigo-600 hover:underline">
                Edit Firm Profile
            </a>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-slate-800">Personal Contact Details</h2>

            @if($personal)
                <div class="mt-4 space-y-3 text-sm">
                    <div>
                        <p class="text-slate-500">Name</p>
                        <p class="font-medium text-slate-800">{{ $personal->full_name }}</p>
                    </div>

                    <div>
                        <p class="text-slate-500">Email</p>
                        <p class="font-medium text-slate-800">{{ $personal->email ?: 'Not provided' }}</p>
                    </div>

                    <div>
                        <p class="text-slate-500">Mobile</p>
                        <p class="font-medium text-slate-800">{{ $personal->phone ?: 'Not provided' }}</p>
                    </div>
                </div>
            @else
                <p class="mt-4 text-sm text-slate-500">Personal Profile has not been completed.</p>
            @endif

            <a href="{{ route('profile.personal.index') }}"
               class="mt-5 inline-flex text-sm font-medium text-indigo-600 hover:underline">
                Edit Personal Profile
            </a>
        </div>

    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="font-semibold text-slate-800">Add Additional Contact Information</h2>

        <form method="POST" action="{{ route('profile.contacts.store') }}"
              class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
            @csrf

            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Contact Type *</label>
                <select name="contact_type" required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">Select contact type</option>
                    <option value="email">Email</option>
                    <option value="phone">Phone</option>
                    <option value="whatsapp">WhatsApp</option>
                    <option value="website">Website</option>
                    <option value="other">Other</option>
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Label</label>
                <input type="text" name="label" maxlength="100"
                       placeholder="e.g. Office, Reception, Billing"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>

            <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium text-slate-700">Contact Value *</label>
                <input type="text" name="contact_value" required maxlength="255"
                       placeholder="Enter email, phone number or website"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>

            <div class="md:col-span-2 flex justify-end">
                <button type="submit"
                        class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                    Add Contact
                </button>
            </div>
        </form>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="font-semibold text-slate-800">Additional Contact Channels</h2>

        @if($contacts->count())
            <div class="mt-4 divide-y divide-slate-100">
                @foreach($contacts as $contact)
                    <div class="flex flex-wrap items-center justify-between gap-3 py-4">
                        <div>
                            <p class="font-medium text-slate-800">
                                {{ ucfirst($contact->contact_type) }}
                                @if($contact->label)
                                    <span class="font-normal text-slate-500">— {{ $contact->label }}</span>
                                @endif
                            </p>
                            <p class="mt-1 text-sm text-slate-600">{{ $contact->contact_value }}</p>
                        </div>

                        <form method="POST"
                              action="{{ route('profile.contacts.destroy', $contact->id) }}"
                              onsubmit="return confirm('Remove this contact from the active list?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="rounded-lg border border-red-200 px-3 py-2 text-sm text-red-600 hover:bg-red-50">
                                Remove
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        @else
            <p class="mt-3 text-sm text-slate-500">No additional contact channels have been added.</p>
        @endif
    </div>

</div>
@endsection