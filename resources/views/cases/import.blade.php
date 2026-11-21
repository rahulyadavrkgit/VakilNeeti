@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto py-8 px-4">

    <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Import Official Court Record</h1>
            <p class="text-slate-500 mt-2">Bring information from an official court record into VakilNeeti.</p>
        </div>
        <a href="{{ route('cases.import.history') }}" class="text-blue-700 hover:underline">
            View Import History
        </a>
    </div>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4 mb-5">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6">
        <p class="text-sm text-slate-600 mb-5">
            Open the relevant official court portal, search for the case, and copy the
            displayed case information. Paste the copied record below. VakilNeeti will
            identify recognised fields and show you a preview before creating a case.
        </p>

        <form method="POST" action="{{ route('cases.import.preview') }}">
            @csrf

            <div class="mb-5">
                <label class="block text-sm font-medium text-slate-700 mb-2">Official Court Source</label>
                <select name="court_source" required class="w-full rounded-lg border-slate-300">
                    <option value="">Select court</option>
                    <option value="Supreme Court of India">Supreme Court of India</option>
                    <option value="Delhi High Court">Delhi High Court</option>
                    <option value="Allahabad High Court">Allahabad High Court</option>
                    <option value="District / Subordinate Court">District / Subordinate Court</option>
                </select>
            </div>

            <div class="mb-5">
                <label class="block text-sm font-medium text-slate-700 mb-2">Copied Official Case Record</label>
                <textarea name="official_record" rows="14" required maxlength="100000"
                    placeholder="Paste the case information copied from the official court website..."
                    class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">{{ old('official_record') }}</textarea>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-700 mb-2">Official Record URL (optional)</label>
                <input type="url" name="source_url" value="{{ old('source_url') }}"
                    placeholder="https://..."
                    class="w-full rounded-lg border-slate-300">
            </div>

            <button type="submit" class="px-5 py-3 rounded-lg bg-blue-700 text-white hover:bg-blue-800">
                Extract and Review Case Information
            </button>
        </form>
    </div>
</div>
@endsection