@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6 sm:px-6 lg:px-8">

    <!-- Back Navigation -->
    <div class="mb-4">
        <a href="{{ route('police-administration.index', ['state_id' => $station->state_id]) }}"
           class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800">
            &larr; Back to Police Directory
        </a>
    </div>

    <!-- Administrative Hierarchy Breadcrumb -->
    <nav class="mb-6 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-xs text-gray-600">
        <span class="font-semibold text-gray-800">Administrative Hierarchy:</span>
        <span>{{ $station->state->name ?? 'India' }}</span>

        @if($station->is_specialist_unit)
            <span class="mx-1.5 text-gray-400">&rarr;</span>
            <span class="font-medium text-purple-700">
                Specialist Unit ({{ $station->specialist_unit_category ?? 'Non-Territorial' }})
            </span>
        @else
            @if($station->policeDistrict && $station->policeDistrict->policeRange)
                <span class="mx-1.5 text-gray-400">&rarr;</span>
                <span>{{ $station->policeDistrict->policeRange->range_name }}</span>
            @elseif($station->policeDistrict && $station->policeDistrict->range_name)
                <span class="mx-1.5 text-gray-400">&rarr;</span>
                <span>{{ $station->policeDistrict->range_name }}</span>
            @endif

            @if($station->policeDistrict)
                <span class="mx-1.5 text-gray-400">&rarr;</span>
                <span>{{ $station->policeDistrict->district_name }} Police District</span>
            @endif

            @if($station->subDivision)
                <span class="mx-1.5 text-gray-400">&rarr;</span>
                <span>{{ $station->subDivision->sub_division_name }} Sub-Division</span>
            @endif
        @endif

        <span class="mx-1.5 text-gray-400">&rarr;</span>
        <span class="font-semibold text-gray-900">{{ $station->station_name }}</span>
    </nav>

    <!-- Main Station Card -->
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 bg-white px-6 py-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $station->station_name }}</h1>
                @if($station->station_code)
                    <p class="mt-1 text-sm text-gray-500">Station / CCTNS Code: <span class="font-mono font-medium text-gray-700">{{ $station->station_code }}</span></p>
                @endif
            </div>
            <div class="flex flex-wrap gap-2">
                @if($station->is_specialist_unit)
                    <span class="inline-flex items-center rounded-full bg-purple-100 px-3 py-1 text-xs font-semibold text-purple-800">
                        {{ $station->specialist_unit_category ?? 'Specialist Unit' }}
                    </span>
                @else
                    <span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-800">
                        {{ $station->station_type ?? 'Territorial Police Station' }}
                    </span>
                @endif
            </div>
        </div>

        <div class="p-6 grid grid-cols-1 gap-6 md:grid-cols-2">

            <!-- Location & Address -->
            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500">Location & Address</h2>
                <dl class="mt-3 space-y-2 text-sm text-gray-700">
                    <div>
                        <dt class="font-medium text-gray-900">Address:</dt>
                        <dd class="mt-0.5 text-gray-600">{{ $station->address ?: 'Not officially published yet.' }}</dd>
                    </div>
                    @if($station->locality || $station->city)
                        <div>
                            <dt class="font-medium text-gray-900">City / Locality:</dt>
                            <dd class="mt-0.5 text-gray-600">
                                {{ collect([$station->locality, $station->city])->filter()->implode(', ') }}
                            </dd>
                        </div>
                    @endif
                    @if($station->pincode)
                        <div>
                            <dt class="font-medium text-gray-900">PIN Code:</dt>
                            <dd class="mt-0.5 text-gray-600">{{ $station->pincode }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            <!-- Official Contact & Officer Details -->
            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500">Official Contacts & Leadership</h2>
                <dl class="mt-3 space-y-2 text-sm text-gray-700">
                    <div>
                        <dt class="font-medium text-gray-900">Station Phone:</dt>
                        <dd class="mt-0.5">
                            @if($station->phone)
                                <a href="tel:{{ $station->phone }}" class="text-indigo-600 hover:underline">{{ $station->phone }}</a>
                            @else
                                <span class="text-gray-500">Not published</span>
                            @endif
                        </dd>
                    </div>
                    @if($station->control_room_phone)
                        <div>
                            <dt class="font-medium text-gray-900">Control Room / Emergency:</dt>
                            <dd class="mt-0.5 text-gray-600">{{ $station->control_room_phone }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="font-medium text-gray-900">Official Email:</dt>
                        <dd class="mt-0.5">
                            @if($station->email)
                                <a href="mailto:{{ $station->email }}" class="text-indigo-600 hover:underline">{{ $station->email }}</a>
                            @else
                                <span class="text-gray-500">Not published</span>
                            @endif
                        </dd>
                    </div>
                    @if($station->sho_name || $station->sho_designation)
                        <div>
                            <dt class="font-medium text-gray-900">Officer In-Charge (SHO):</dt>
                            <dd class="mt-0.5 text-gray-600">
                                {{ $station->sho_name ?? 'Published' }}
                                @if($station->sho_designation)
                                    ({{ $station->sho_designation }})
                                @endif
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>

            <!-- Jurisdiction Area -->
            @if($station->jurisdiction_area)
                <div class="md:col-span-2 border-t border-gray-100 pt-4">
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500">Jurisdiction Coverage</h2>
                    <p class="mt-2 text-sm text-gray-700 whitespace-pre-line">{{ $station->jurisdiction_area }}</p>
                </div>
            @endif

            <!-- Judicial & Remand Jurisdiction Cross-Link Panel -->
            <div class="md:col-span-2 border-t border-indigo-100 bg-indigo-50/50 rounded-xl p-5 mt-2">
                <h2 class="text-sm font-bold uppercase tracking-wider text-indigo-900 flex items-center gap-2">
                    <span>⚖️ Judicial & Remand Jurisdiction Cross-Link</span>
                </h2>
                <p class="mt-1 text-xs text-indigo-700">Official court complex exercising remand and trial jurisdiction over this police station's FIRs.</p>

                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="font-semibold text-gray-900">Trial / Remand Court Complex:</span>
                        @if($station->courtComplex)
                            <div class="mt-1">
                                <a href="{{ route('court-directory.index') }}?q={{ urlencode($station->courtComplex->complex_name) }}" class="font-bold text-indigo-600 hover:underline">
                                    {{ $station->courtComplex->complex_name }} &nearr;
                                </a>
                                <p class="text-xs text-gray-600 mt-0.5">{{ $station->courtComplex->address }}</p>
                                @if($station->courtComplex->highCourt)
                                    <p class="text-xs text-indigo-800 mt-1">
                                        <strong>Controlling High Court:</strong> {{ $station->courtComplex->highCourt->court_name }}
                                    </p>
                                @endif
                            </div>
                        @else
                            <p class="mt-1 text-gray-500">Not mapped yet.</p>
                        @endif
                    </div>

                    <div>
                        <span class="font-semibold text-gray-900">Affiliated Bar Association(s):</span>
                        @if($station->courtComplex && $station->courtComplex->barAssociations->count() > 0)
                            <ul class="mt-1 space-y-1">
                                @foreach($station->courtComplex->barAssociations as $assoc)
                                    <li class="text-xs text-gray-700 bg-white p-2 rounded border border-indigo-100 shadow-sm">
                                        <span class="font-semibold text-gray-900">{{ $assoc->association_name }}</span>
                                        @if($assoc->contact_phone)
                                            <span class="block text-gray-500">Phone: <a href="tel:{{ $assoc->contact_phone }}" class="text-indigo-600 hover:underline">{{ $assoc->contact_phone }}</a></span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="mt-1 text-gray-500">No bar association linked.</p>
                        @endif
                    </div>
                </div>

                @if($station->remand_court_notes)
                    <div class="mt-3 text-xs text-gray-700 bg-white p-3 rounded border border-indigo-100">
                        <strong>Jurisdiction Notes:</strong> {{ $station->remand_court_notes }}
                    </div>
                @endif
            </div>

            <!-- Portals & Source Verification -->
            <div class="md:col-span-2 border-t border-gray-100 pt-4">
                <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500">Official Portals & Source Verification</h2>
                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 text-sm">
                    @if($station->official_url)
                        <div>
                            <span class="font-medium text-gray-900">Official Website:</span>
                            <a href="{{ $station->official_url }}" target="_blank" rel="noopener noreferrer" class="ml-1 text-indigo-600 hover:underline">
                                Visit Official Page &nearr;
                            </a>
                        </div>
                    @endif
                    @if($station->citizen_portal_url)
                        <div>
                            <span class="font-medium text-gray-900">Citizen Service Portal:</span>
                            <a href="{{ $station->citizen_portal_url }}" target="_blank" rel="noopener noreferrer" class="ml-1 text-indigo-600 hover:underline">
                                Open Citizen Portal &nearr;
                            </a>
                        </div>
                    @endif
                    <div>
                        <span class="font-medium text-gray-900">Source:</span>
                        <span class="text-gray-600">{{ $station->source_name ?? 'Official Police Portal' }}</span>
                        @if($station->source_url)
                            (<a href="{{ $station->source_url }}" target="_blank" rel="noopener noreferrer" class="text-indigo-600 hover:underline">Reference</a>)
                        @endif
                    </div>
                    <div>
                        <span class="font-medium text-gray-900">Verification Status:</span>
                        <span class="inline-flex items-center rounded bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-800">
                            {{ ucfirst(str_replace('_', ' ', $station->source_status)) }}
                        </span>
                        @if($station->last_verified_at)
                            <span class="ml-1 text-xs text-gray-500">(Verified: {{ $station->last_verified_at->format('d M Y') }})</span>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection