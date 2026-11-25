{{--
    The second menu: a bar under the header with the pages of the current section (Cases, Billing, Settings),
    the same on computers and phones. The main menu links straight to each section's first page.
--}}
@php
    $user = auth()->user();
    $isOwner = $user && ! $user->isTeamMember();
    $is = fn (string ...$patterns): bool => request()->is(...$patterns);
    $named = fn (string ...$names): bool => request()->routeIs(...$names);

    // [section label, [[tab label, url, current?], …]]
    $section = match (true) {
        ! $user => null,
        $is('cases*', 'today', 'tasks*', 'case-calendar*', 'hearings*') => [__('Cases'), [
            [__('My cases'), route('cases.index'), $named('cases.index', 'cases.show', 'cases.create', 'cases.edit', 'cases.manual')],
            [__('Calendar'), route('cases.calendar'), $is('case-calendar*', 'hearings*')],
            [__('Court day'), route('court-day'), $is('today')],
            [__('Tasks'), route('tasks.index'), $is('tasks*')],
            [__('Find & import'), route('cases.find'), $is('cases/find*', 'cases/import*')],
        ]],
        $isOwner && $is('billing*', 'invoices*', 'payments*', 'tds*', 'professional-tax*') => [__('Billing'), [
            [__('Overview'), route('billing.index'), $named('billing.index')],
            [__('Invoices'), route('invoices.index'), $is('invoices*')],
            [__('Payments'), route('payments.index'), $is('payments*')],
            [__('Overdues'), route('billing.overdues'), $named('billing.overdues')],
            [__('Time sheet'), route('billing.time'), $is('billing/time*')],
            [__('Expenses'), route('billing.expenses'), $is('billing/expenses*')],
            [__('TDS'), route('tds.index'), $is('tds*')],
            [__('Professional tax'), route('professional-tax.index'), $is('professional-tax*')],
        ]],
        $is('profile', 'profile/*', 'firm-profile*') || ($user->is_admin && $is('admin', 'admin/*')) => [__('Settings'), array_values(array_filter([
            [__('Settings'), route('profile.index'), $named('profile.index')],
            [__('Advocate profile'), route('profile.advocate.index'), $is('profile/advocate*')],
            [__('Public CV'), route('profile.cv.edit'), $is('profile/cv*')],
            $isOwner ? [__('Office'), route('firm-profile.index'), $is('firm-profile*')] : null,
            $isOwner ? [__('Bank & UPI'), route('profile.invoice-settings.index'), $is('profile/invoice-settings*')] : null,
            [__('Practice states'), route('profile.practice-states.index'), $is('profile/practice-states*')],
            $user->is_admin ? [__('Account administration'), route('admin.overview'), $is('admin', 'admin/*')] : null,
        ]))],
        default => null,
    };
@endphp

@if($section)
    <nav aria-label="{{ $section[0] }}" class="vn-section-nav border-b border-slate-200 bg-white print:hidden">
        <div class="flex overflow-x-auto px-1.5 [scrollbar-width:none] sm:px-3 lg:px-4">
            @foreach($section[1] as [$label, $url, $current])
                <a href="{{ $url }}" @if($current) aria-current="page" @endif
                   class="relative inline-flex h-12 shrink-0 items-center whitespace-nowrap px-3.5 text-sm font-semibold transition sm:px-4 {{ $current ? 'text-slate-900 after:absolute after:inset-x-3 after:bottom-0 after:h-[3px] after:rounded-t-full after:bg-[#16776e]' : 'text-slate-500 hover:text-slate-900' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </nav>
@endif
