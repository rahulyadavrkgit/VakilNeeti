@extends('layouts.app')

@section('title', 'Add Client Activity')
@section('page-heading', 'Add Client Activity')

@section('content')
<div class="space-y-6">

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
                        class="rounded-lg bg-[#16776e] px-6 py-3 font-semibold text-white hover:bg-[#125f58]">
                    Save Activity
                </button>

                <a href="{{ route('clients.activities.index', $client) }}"
                   class="rounded-lg border border-slate-300 px-6 py-3">
                    Cancel
                </a>
            </div>

        </form>
</div>
@endsection
