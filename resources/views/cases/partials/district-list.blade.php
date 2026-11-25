<details class="rounded-lg border border-slate-200 px-3 py-2" @if($errors->districtList->any()) open @endif>
    <summary class="cursor-pointer text-sm font-semibold">Import a district cause list</summary>
    <form method="POST" enctype="multipart/form-data" action="{{ route('cases.district-list.store', $case) }}" class="mt-2 grid gap-2 sm:grid-cols-2">
        @csrf
        <p class="text-xs text-slate-600 sm:col-span-2">Use one full courtroom list for one hearing date. The CSV header is <code>item,cnr</code>. Keep rows with no CNR and leave their CNR cells blank so they count towards the list total.</p>
        <label class="text-sm font-medium">Hearing date<input required name="hearing_date" type="date" value="{{ old('hearing_date', $panelNext ? $panelNext['date']->toDateString() : '') }}" class="mt-1 block min-h-11 w-full rounded-lg border-slate-300"></label>
        <label class="text-sm font-medium">Room and list name<input required name="room_label" maxlength="60" placeholder="512 Criminal" value="{{ old('room_label') }}" class="mt-1 block min-h-11 w-full rounded-lg border-slate-300"></label>
        <label class="text-sm font-medium">Official source<input required name="source" type="url" value="{{ old('source') }}" class="mt-1 block min-h-11 w-full rounded-lg border-slate-300"></label>
        <label class="text-sm font-medium">Cause-list CSV<input required name="list_file" type="file" accept=".csv,text/csv" class="mt-1 block w-full"></label>
        @foreach($errors->districtList->all() as $error)<p role="alert" class="text-sm text-red-700 sm:col-span-2">{{ $error }}</p>@endforeach
        <button class="min-h-11 justify-self-start rounded-lg bg-teal-700 px-4 text-sm font-semibold text-white">Import list</button>
    </form>
</details>
