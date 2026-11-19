<div class="mb-6 flex flex-wrap gap-2">
    <a href="{{ route('tds.follow-up-dashboard') }}"
       class="rounded-lg px-4 py-2 text-sm font-medium {{ request()->routeIs('tds.follow-up-dashboard') ? 'bg-indigo-600 text-white' : 'border border-slate-300 text-slate-700 hover:bg-slate-50' }}">
        Follow-up Dashboard
    </a>

    <a href="{{ route('tds.follow-ups.due') }}"
       class="rounded-lg px-4 py-2 text-sm font-medium {{ request()->routeIs('tds.follow-ups.due') ? 'bg-indigo-600 text-white' : 'border border-slate-300 text-slate-700 hover:bg-slate-50' }}">
        Due Follow-ups
    </a>

    <a href="{{ route('tds.follow-ups.upcoming') }}"
       class="rounded-lg px-4 py-2 text-sm font-medium {{ request()->routeIs('tds.follow-ups.upcoming') ? 'bg-indigo-600 text-white' : 'border border-slate-300 text-slate-700 hover:bg-slate-50' }}">
        Upcoming Follow-ups
    </a>

    <a href="{{ route('tds.follow-ups.history') }}"
       class="rounded-lg px-4 py-2 text-sm font-medium {{ request()->routeIs('tds.follow-ups.history') ? 'bg-indigo-600 text-white' : 'border border-slate-300 text-slate-700 hover:bg-slate-50' }}">
        Follow-up History
    </a>

    <a href="{{ route('tds.index') }}"
       class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
        TDS Register
    </a>
</div>