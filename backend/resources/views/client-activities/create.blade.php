<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Client Activity | VakilNeeti</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-100 text-slate-900">
<div class="flex min-h-screen">

    <aside class="w-64 shrink-0 bg-slate-950 px-5 py-7 text-white">
        <h1 class="mb-10 text-2xl font-bold">VakilNeeti</h1>

        <nav class="space-y-2">
            <a href="/" class="block rounded-lg px-4 py-3 hover:bg-slate-800">Dashboard</a>
            <a href="{{ route('clients.index') }}" class="block rounded-lg bg-blue-600 px-4 py-3 font-semibold">Clients</a>
            <a href="{{ route('invoices.index') }}" class="block rounded-lg px-4 py-3 hover:bg-slate-800">Invoices</a>
            <a href="{{ route('payments.index') }}" class="block rounded-lg px-4 py-3 hover:bg-slate-800">Payments</a>
        </nav>
    </aside>

    <main class="min-w-0 flex-1 p-8">

        <h2 class="text-3xl font-bold">Record Client Activity</h2>

        <p class="mb-8 mt-2 text-slate-500">
            Client: {{ $client->display_name ?: trim($client->first_name.' '.$client->last_name) }}
            | ID: {{ $client->id }}
        </p>

        <form method="POST"
              action="{{ route('clients.activities.store', $client) }}"
              class="max-w-3xl space-y-6 rounded-xl border border-slate-200 bg-white p-7 shadow-sm">

            @csrf

            <div>
                <label class="mb-2 block font-medium">Activity Type *</label>
                <select name="activity_type" required class="w-full rounded-lg border border-slate-300 p-3">
                    <option value="">Select activity</option>
                    <option value="call" @selected(old('activity_type') === 'call')>Phone Call</option>
                    <option value="meeting" @selected(old('activity_type') === 'meeting')>Meeting</option>
                    <option value="whatsapp" @selected(old('activity_type') === 'whatsapp')>WhatsApp</option>
                    <option value="email" @selected(old('activity_type') === 'email')>Email</option>
                    <option value="client_instruction" @selected(old('activity_type') === 'client_instruction')>Client Instruction</option>
                    <option value="follow_up" @selected(old('activity_type') === 'follow_up')>Follow-up</option>
                    <option value="note" @selected(old('activity_type') === 'note')>Internal Note</option>
                    <option value="other" @selected(old('activity_type') === 'other')>Other</option>
                </select>
                @error('activity_type')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-2 block font-medium">Subject *</label>
                <input type="text" name="subject" value="{{ old('subject') }}"
                       required maxlength="255"
                       class="w-full rounded-lg border border-slate-300 p-3"
                       placeholder="Enter activity subject">
                @error('subject')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-2 block font-medium">Details / Instructions</label>
                <textarea name="description" rows="5"
                          class="w-full rounded-lg border border-slate-300 p-3"
                          placeholder="Record discussion, instructions or notes">{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-2 block font-medium">Activity Date & Time *</label>
                <input type="datetime-local" name="activity_at"
                       value="{{ old('activity_at', now()->format('Y-m-d\TH:i')) }}"
                       required class="w-full rounded-lg border border-slate-300 p-3">
                @error('activity_at')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-2 block font-medium">Follow-up Date & Time</label>
                <input type="datetime-local" name="follow_up_at"
                       value="{{ old('follow_up_at') }}"
                       class="w-full rounded-lg border border-slate-300 p-3">
                @error('follow_up_at')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-2 block font-medium">Status *</label>
                <select name="status" required class="w-full rounded-lg border border-slate-300 p-3">
                    <option value="completed" @selected(old('status', 'completed') === 'completed')>Completed</option>
                    <option value="pending" @selected(old('status') === 'pending')>Pending</option>
                    <option value="cancelled" @selected(old('status') === 'cancelled')>Cancelled</option>
                </select>
            </div>

            <div class="flex gap-3">
                <button type="submit"
                        class="rounded-lg bg-blue-600 px-6 py-3 font-semibold text-white hover:bg-blue-700">
                    Save Activity
                </button>

                <a href="{{ route('clients.activities.index', $client) }}"
                   class="rounded-lg border border-slate-300 px-6 py-3">
                    Cancel
                </a>
            </div>

        </form>

    </main>
</div>
</body>
</html>