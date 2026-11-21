<div class="mb-6 rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('cases.index') }}" class="rounded-lg px-4 py-2 text-sm font-medium {{ request()->routeIs('cases.index') || request()->routeIs('cases.show') || request()->routeIs('cases.create') || request()->routeIs('cases.edit') ? 'bg-blue-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
            My Cases
        </a>
        <a href="{{ route('cases.index', ['case_status' => 'pending']) }}" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">
            Pending Cases
        </a>
        <a href="{{ route('cases.calendar') }}" class="rounded-lg px-4 py-2 text-sm font-medium {{ request()->routeIs('cases.calendar') ? 'bg-blue-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
            Calendar & Hearings
        </a>
        <a href="{{ route('cases.hearing-history') }}" class="rounded-lg px-4 py-2 text-sm font-medium {{ request()->routeIs('cases.hearing-history') ? 'bg-blue-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
            Hearing History
        </a>
        <a href="{{ route('tasks.index') }}" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">
            Tasks & Reminders
        </a>
    </div>
</div>
