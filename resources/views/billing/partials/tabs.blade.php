{{-- Tab bar shared by every billing page. --}}
@php
    $billingTabs = [
        ['route' => 'billing.index', 'label' => 'Overview', 'active' => request()->routeIs('billing.index')],
        ['route' => 'invoices.index', 'label' => 'Invoices', 'active' => request()->routeIs('invoices.*')],
        ['route' => 'payments.index', 'label' => 'Payments', 'active' => request()->routeIs('payments.*')],
        ['route' => 'billing.overdues', 'label' => 'Overdues', 'active' => request()->routeIs('billing.overdues')],
        ['route' => 'billing.time', 'label' => 'Time sheet', 'active' => request()->routeIs('billing.time*')],
        ['route' => 'billing.expenses', 'label' => 'Expenses', 'active' => request()->routeIs('billing.expenses*')],
    ];
@endphp
<nav aria-label="Billing" class="-mx-1 flex gap-1 overflow-x-auto border-b border-slate-200 pb-px print:hidden">
    @foreach($billingTabs as $tab)
        <a href="{{ route($tab['route']) }}" @if($tab['active']) aria-current="page" @endif
           class="whitespace-nowrap border-b-2 px-4 py-2.5 text-sm font-semibold {{ $tab['active'] ? 'border-[#238b80] text-[#16776e]' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
            {{ $tab['label'] }}
        </a>
    @endforeach
</nav>
