@props(['caseId', 'page'])
<details class="mt-3">
    <summary class="flex min-h-11 cursor-pointer items-center text-sm font-semibold text-teal-800 underline">Report a problem</summary>
    <form method="POST" action="{{ route('cases.report', $caseId) }}" class="space-y-2">
        @csrf
        <input type="hidden" name="page" value="{{ $page }}">
        <label class="block text-sm font-medium">What went wrong?
            <textarea required name="message" maxlength="500" rows="2" class="mt-1 block w-full rounded-lg border-slate-300"></textarea>
        </label>
        <button class="min-h-11 rounded-lg bg-teal-700 px-4 text-sm font-semibold text-white">Send report</button>
    </form>
</details>
