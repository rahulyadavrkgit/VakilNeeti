@extends('layouts.app')

@section('title', 'Import court record')
@section('page-heading', 'Import court record')

@section('content')
<div class="max-w-5xl mx-auto py-8 px-4">

    <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Import Court Record</h1>
            <p class="text-slate-500 mt-2">Bring information from a court record into VakilNiti.</p>
        </div>
        <a href="{{ route('cases.import.history') }}" class="text-[#16776e] hover:underline">
            View Import History
        </a>
    </div>

    <p class="mb-5 rounded-lg border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-900">
        Adding many cases at once? Use <a href="{{ route('cases.find') }}" class="font-semibold underline">Find my cases</a> to bring in your whole eCourts list by bar code.
    </p>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4 mb-5">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6">
        <p class="text-sm text-slate-600 mb-5">Find the case on the court portal, copy its details and paste them below. You’ll see a preview before anything is saved.</p>

        <form method="POST" action="{{ route('cases.import.preview') }}">
            @csrf

            <div class="mb-5">
                <label class="block text-sm font-medium text-slate-700 mb-2">Court Source</label>
                <select name="court_source" required class="w-full rounded-lg border-slate-300">
                    <option value="">Select court</option>
                    <option value="Supreme Court of India">Supreme Court of India</option>
                    <option value="Delhi High Court">Delhi High Court</option>
                    <option value="Allahabad High Court">Allahabad High Court</option>
                    <option value="District / Subordinate Court">District / Subordinate Court</option>
                </select>
            </div>

            <div class="mb-5">
                <label class="block text-sm font-medium text-slate-700 mb-2">Copied Case Record</label>
                <textarea name="official_record" rows="14" required maxlength="100000"
                    placeholder="Paste the case information copied from the court website..."
                    class="w-full rounded-lg border-slate-300 focus:border-[#16776e] focus:ring-[#16776e]">{{ old('official_record') }}</textarea>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-700 mb-2">Record URL (optional)</label>
                <input type="url" name="source_url" value="{{ old('source_url') }}"
                    placeholder="https://..."
                    class="w-full rounded-lg border-slate-300">
            </div>

            <button type="submit" class="px-5 py-3 rounded-lg bg-[#16776e] text-white hover:bg-[#125f58]">
                Extract and Review Case Information
            </button>
        </form>
    </div>
</div>
@endsection