<div class="mb-4 rounded-xl border border-slate-200 bg-white p-2 shadow-sm">
    <div class="-mx-1 flex gap-1.5 overflow-x-auto px-1">
        <a href="{{ route('cases.index') }}" class="shrink-0 whitespace-nowrap rounded-lg px-4 py-2 text-sm font-medium {{ request()->routeIs('cases.index') || request()->routeIs('cases.show') || request()->routeIs('cases.create') || request()->routeIs('cases.edit') ? 'bg-[#16776e] text-white' : 'text-slate-600 hover:bg-slate-100' }}">
            My Cases
        </a>
        <a href="{{ route('cases.index', ['case_status' => 'pending']) }}" class="shrink-0 whitespace-nowrap rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">
            Pending Cases
        </a>
        <a href="{{ route('cases.calendar') }}" class="shrink-0 whitespace-nowrap rounded-lg px-4 py-2 text-sm font-medium {{ request()->routeIs('cases.calendar') ? 'bg-[#16776e] text-white' : 'text-slate-600 hover:bg-slate-100' }}">
            Calendar & Hearings
        </a>
        <a href="{{ route('cases.hearing-history') }}" class="shrink-0 whitespace-nowrap rounded-lg px-4 py-2 text-sm font-medium {{ request()->routeIs('cases.hearing-history') ? 'bg-[#16776e] text-white' : 'text-slate-600 hover:bg-slate-100' }}">
            Hearing History
        </a>
        <a href="{{ route('tasks.index') }}" class="shrink-0 whitespace-nowrap rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">
            Tasks & Reminders
        </a>
    </div>
</div>
