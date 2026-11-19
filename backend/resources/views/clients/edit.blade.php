<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VakilNeeti | Edit Client</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">

<div class="mx-auto max-w-5xl p-8">

    <a href="{{ route('clients.index') }}" class="text-blue-600 hover:underline">
        ← Back to Clients
    </a>

    <h1 class="mb-2 mt-5 text-3xl font-bold">Edit Client</h1>
    <p class="mb-8 text-slate-600">Client ID: {{ $client->id }}</p>

    @if ($errors->any())
        <div class="mb-6 rounded-lg bg-red-100 p-4 text-red-700">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form action="{{ route('clients.update', $client) }}" method="POST"
          class="space-y-6 rounded-xl bg-white p-8 shadow-sm">

        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

            <div>
                <label class="mb-2 block font-medium">Client Type *</label>
                <select name="client_type" required class="w-full rounded-lg border p-3">
                    <option value="individual" {{ old('client_type', $client->client_type) == 'individual' ? 'selected' : '' }}>Individual</option>
                    <option value="company" {{ old('client_type', $client->client_type) == 'company' ? 'selected' : '' }}>Company</option>
                    <option value="organisation" {{ old('client_type', $client->client_type) == 'organisation' ? 'selected' : '' }}>Organisation</option>
                </select>
            </div>

            @foreach (['first_name' => 'First Name', 'middle_name' => 'Middle Name', 'last_name' => 'Last Name', 'display_name' => 'Display Name', 'organisation_name' => 'Organisation Name', 'mobile' => 'Mobile', 'alternate_mobile' => 'Alternate Mobile', 'email' => 'Email', 'city' => 'City', 'district' => 'District', 'state' => 'State', 'pin_code' => 'PIN Code', 'country' => 'Country', 'occupation' => 'Occupation', 'referred_by' => 'Referred By'] as $field => $label)
                <div>
                    <label class="mb-2 block font-medium">{{ $label }}</label>
                    <input
                        type="{{ $field === 'email' ? 'email' : 'text' }}"
                        name="{{ $field }}"
                        value="{{ old($field, $client->$field) }}"
                        class="w-full rounded-lg border p-3"
                        @if($field === 'first_name') required @endif
                    >
                </div>
            @endforeach

            <div class="md:col-span-2">
                <label class="mb-2 block font-medium">Address</label>
                <textarea name="address" rows="3" class="w-full rounded-lg border p-3">{{ old('address', $client->address) }}</textarea>
            </div>

            <div class="md:col-span-2">
                <label class="mb-2 block font-medium">Notes</label>
                <textarea name="notes" rows="4" class="w-full rounded-lg border p-3">{{ old('notes', $client->notes) }}</textarea>
            </div>

            <div>
                <label class="mb-2 block font-medium">Status</label>
                <select name="status" class="w-full rounded-lg border p-3">
                    <option value="active" {{ old('status', $client->status) == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status', $client->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

        </div>

        <div class="flex gap-3 border-t pt-6">
            <button type="submit" class="rounded-lg bg-blue-600 px-6 py-3 font-semibold text-white">
                Update Client
            </button>

            <a href="{{ route('clients.index') }}" class="rounded-lg border px-6 py-3">
                Cancel
            </a>
        </div>

    </form>
</div>

</body>
</html>