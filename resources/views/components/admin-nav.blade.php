<nav aria-label="Administration" class="flex flex-wrap gap-2">
    @foreach(['admin.overview' => 'Overview', 'admin.accounts' => 'Users and teams', 'admin.audit' => 'Audit history'] as $route => $label)
        <a href="{{ route($route) }}" @if(request()->routeIs($route)) aria-current="page" @endif
           class="inline-flex min-h-11 items-center rounded-lg border border-slate-200 px-3 text-sm font-semibold aria-[current=page]:bg-teal-700 aria-[current=page]:text-white">{{ $label }}</a>
    @endforeach
</nav>
