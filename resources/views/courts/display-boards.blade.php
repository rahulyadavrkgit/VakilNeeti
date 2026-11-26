@extends('layouts.app')

@section('title', 'Court display boards')
@section('page-heading', 'Display boards')
@section('meta_description', 'Live court display boards in one place: Supreme Court of India, Delhi High Court and Allahabad High Court. See which item is running in each courtroom.')

@section('content')
<div class="mx-auto max-w-6xl space-y-4">
    @include('courts.partials.breadcrumb', ['current' => 'Display boards'])

    <div>
        <h1 class="text-2xl font-semibold text-slate-900">Court display boards</h1>
        <p class="mt-1 text-sm text-slate-600">Pick a court from the list to see which item is running in each courtroom. Switch boards any time from the same list.</p>
    </div>

    @include('courts.partials.board-picker', ['pageMode' => true])

    <p class="text-xs leading-relaxed text-slate-500">
        The Supreme Court and Delhi High Court boards marked <strong>Live board in VakilNiti</strong> show right here, refreshed every 10 seconds
        from the data the courts publish for their own boards, with your courts today outlined. The courts' own board pages open in a window beside VakilNiti,
        because those courts do not let other websites show them. Use <strong>Manage</strong> to keep only the courts you follow in the list.
    </p>
</div>
@endsection
