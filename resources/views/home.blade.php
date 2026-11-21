@extends('layouts.app')

@section('title', 'All Sections')
@section('page-heading', 'All Sections')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <header class="rounded-2xl bg-slate-900 p-6 text-white shadow-sm">
        <div class="mb-3"><img src="{{ asset('images/vakilneeti-logo.png') }}" alt="VakilNeeti" class="h-12 w-auto max-w-[220px] rounded bg-white px-2 py-1 object-contain"></div>
        <h1 class="mt-2 text-2xl font-bold">Explore the legal workspace</h1>
        <p class="mt-2 max-w-3xl text-sm text-slate-300">
            Explore Indian court information and legal resources, build public-data dashboards,
            or manage your own cases and practice.
        </p>
    </header>

    @php
        $sections = [
            [
                'title' => 'India Law & Justice Data',
                'description' => 'Build knowledge dashboards from official public datasets. This is separate from your private case-management data.',
                'links' => [
                    ['label' => 'Create Law Data Dashboards', 'url' => route('dashboard-studio.index')],
                ],
            ],
            [
                'title' => 'Case Management System',
                'description' => 'Manage your own cases, hearings, history, deadlines and reminders.',
                'links' => [
                    ['label' => 'My Case Dashboard', 'url' => route('case-dashboard.index')],
                    ['label' => 'My Cases', 'url' => route('cases.index')],
                    ['label' => 'Calendar & Hearings', 'url' => route('cases.calendar')],
                    ['label' => 'Hearing History', 'url' => route('hearings.history')],
                    ['label' => 'Tasks & Reminders', 'url' => route('tasks.index')],
                ],
            ],
            [
                'title' => 'Courts',
                'description' => 'Browse court levels, jurisdictions, catalogues and official portals.',
                'links' => [
                    ['label' => 'Court Directory', 'url' => route('court-directory.index')],
                    ['label' => 'Supreme Court of India', 'url' => route('court-directory.index', ['level' => 'supreme'])],
                    ['label' => 'High Courts', 'url' => route('court-directory.index', ['level' => 'high-court'])],
                    ['label' => 'District & Subordinate Courts', 'url' => route('court-directory.index', ['level' => 'district'])],
                    ['label' => 'Appellate Courts & Tribunals', 'url' => route('court-catalogue.index')],
                    ['label' => 'Official Court Search', 'url' => route('court-integrations.ecourts.search')],
                ],
            ],
            [
                'title' => 'Clients & Billing',
                'description' => 'Organise client records, invoices and payments.',
                'links' => [
                    ['label' => 'All Clients', 'url' => route('clients.index')],
                    ['label' => 'Invoices & Payments', 'url' => route('billing.index')],
                ],
            ],
            [
                'title' => 'Police Administration',
                'description' => 'Browse police stations, departments, districts and official portals.',
                'links' => [
                    ['label' => 'Police Stations', 'url' => url('/police-administration#police-stations')],
                    ['label' => 'State Police Departments', 'url' => url('/police-administration#state-police')],
                    ['label' => 'Police Districts & Ranges', 'url' => url('/police-administration#police-districts')],
                    ['label' => 'Official Police Portals', 'url' => url('/police-administration#official-portals')],
                ],
            ],
            [
                'title' => 'Bar Associations',
                'description' => 'Find national, state, High Court and district bar associations.',
                'links' => [
                    ['label' => 'National Associations', 'url' => route('bar-associations.index') . '#national'],
                    ['label' => 'State & High Court Associations', 'url' => route('bar-associations.index') . '#state-high-court'],
                    ['label' => 'District Associations', 'url' => route('bar-associations.index') . '#district'],
                    ['label' => 'Bar Councils', 'url' => route('bar-associations.index') . '#bar-councils'],
                ],
            ],
            [
                'title' => 'Court Fee',
                'description' => 'Use the court-fee calculator and browse fee schedules and rules.',
                'links' => [
                    ['label' => 'Court Fee Calculator', 'url' => url('/court-fee#calculator')],
                    ['label' => 'Court Fee Schedules', 'url' => url('/court-fee#fee-schedules')],
                    ['label' => 'Rules & Exemptions', 'url' => url('/court-fee#rules')],
                ],
            ],
            [
                'title' => 'Bare Acts',
                'description' => 'Browse central acts, criminal law codes and official sources.',
                'links' => [
                    ['label' => 'Overview', 'url' => route('bare-acts.index') . '#overview'],
                    ['label' => 'Central Acts', 'url' => route('bare-acts.index') . '#central-acts'],
                    ['label' => 'Criminal Law Codes', 'url' => route('bare-acts.index') . '#criminal-laws'],
                    ['label' => 'Official Sources', 'url' => route('bare-acts.index') . '#sources'],
                ],
            ],
            [
                'title' => 'New Criminal Laws',
                'description' => 'Explore the updated criminal-law reference section.',
                'links' => [
                    ['label' => 'Open New Criminal Laws', 'url' => url('/new-criminal-laws')],
                ],
            ],
            [
                'title' => 'Miscellaneous Forms',
                'description' => 'Browse court, civil, criminal, affidavit and RTI forms.',
                'links' => [
                    ['label' => 'Court Forms', 'url' => route('miscellaneous-forms.index') . '#court-forms'],
                    ['label' => 'Civil & Criminal Forms', 'url' => route('miscellaneous-forms.index') . '#civil-criminal'],
                    ['label' => 'Affidavits & Applications', 'url' => route('miscellaneous-forms.index') . '#affidavits'],
                    ['label' => 'Police & RTI Forms', 'url' => route('miscellaneous-forms.index') . '#police-rti'],
                ],
            ],
            [
                'title' => 'PDF Annotator',
                'description' => 'Annotate documents locally in your browser.',
                'links' => [
                    ['label' => 'Open PDF Annotator', 'url' => url('/pdf-annotator')],
                ],
            ],
            [
                'title' => 'Profile Settings',
                'description' => 'Manage your profile, firm, practice jurisdictions and tax settings.',
                'links' => [
                    ['label' => 'Open Profile Settings', 'url' => route('profile.index')],
                    ['label' => 'Personal Profile', 'url' => route('profile.personal.index')],
                    ['label' => 'Professional Profile', 'url' => route('profile.professional.index')],
                    ['label' => 'Firm Profile', 'url' => route('firm-profile.index')],
                    ['label' => 'Contact Information', 'url' => route('profile.contacts.index')],
                    ['label' => 'Practice States & Jurisdictions', 'url' => route('profile.practice-states.index')],
                    ['label' => 'Invoice & GST Settings', 'url' => route('profile.invoice-settings.index')],
                    ['label' => 'Tax & Compliance', 'url' => route('profile.tax-compliance')],
                ],
            ],
        ];
    @endphp

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach($sections as $section)
            <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900">{{ $section['title'] }}</h2>
                <p class="mt-2 min-h-12 text-sm leading-6 text-slate-600">{{ $section['description'] }}</p>
                <ul class="mt-4 space-y-2 border-t border-slate-100 pt-4">
                    @foreach($section['links'] as $link)
                        <li>
                            <a href="{{ $link['url'] }}" class="text-sm font-semibold text-blue-700 hover:underline">
                                {{ $link['label'] }} &rarr;
                            </a>
                        </li>
                    @endforeach
                </ul>
            </article>
        @endforeach
    </section>
</div>
@endsection

