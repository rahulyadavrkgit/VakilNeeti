@extends('layouts.app')

@section('title', 'Official Court Search')
@section('page-heading', 'Official Court Search')

@section('content')
<div class="space-y-6">

    <div class="bg-white border rounded-xl p-5">
        <h2 class="text-xl font-bold text-gray-900">Search Official Court Records</h2>
        <p class="text-sm text-gray-600 mt-2">
            Select the appropriate court on the official government portal,
            search for the case, and then add the retrieved record to your VakilNeeti case diary.
        </p>

        <div class="flex flex-wrap gap-3 mt-5">
            <a href="https://services.ecourts.gov.in/ecourtindia_v6/"
               target="_blank" rel="noopener noreferrer"
               class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-700 text-white text-sm font-medium hover:bg-blue-800">
                Open Official District Courts Portal
            </a>

            <a href="{{ route('court-directory.index') }}"
               class="inline-flex items-center px-4 py-2 rounded-lg border text-sm font-medium hover:bg-gray-50">
                Open Court Directory
            </a>
        </div>

        <p class="text-xs text-gray-500 mt-3">
            Official portal searches, including CAPTCHA verification, are completed on the government website.
            VakilNeeti does not claim live access to court records where an authorised interface is unavailable.
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        @php
            $searchMethods = [
                [
                    'title' => '1. CNR Number',
                    'description' => 'Search using the 16-character Case Number Record.',
                    'details' => ['Complete CNR number', 'CAPTCHA verification, if requested']
                ],
                [
                    'title' => '2. Case Number',
                    'description' => 'Search using the registered case number and year.',
                    'details' => ['State and district', 'Court complex or establishment', 'Case type and case number', 'Registration year']
                ],
                [
                    'title' => '3. Party Name',
                    'description' => 'Find cases by petitioner, plaintiff, respondent or defendant.',
                    'details' => ['Party name', 'State and district', 'Registration year, if known']
                ],
                [
                    'title' => '4. Advocate Name',
                    'description' => 'Search cases associated with an advocate where supported by the official portal.',
                    'details' => ['Advocate registered name', 'Bar registration number, if required', 'State and district']
                ],
                [
                    'title' => '5. Filing Number',
                    'description' => 'Locate a case using its filing or diary reference where available.',
                    'details' => ['Filing number', 'Filing year', 'Court establishment']
                ],
                [
                    'title' => '6. FIR Number',
                    'description' => 'Search criminal cases using FIR details where supported.',
                    'details' => ['FIR number and year', 'Police station', 'State and district']
                ],
                [
                    'title' => '7. Act and Case Type',
                    'description' => 'Search using the relevant Act and case classification where available.',
                    'details' => ['Act name', 'Section, if known', 'Case type and court establishment']
                ],
            ];
        @endphp

        @foreach ($searchMethods as $method)
            <div class="bg-white border rounded-xl p-5">
                <h3 class="font-semibold text-gray-900">{{ $method['title'] }}</h3>
                <p class="text-sm text-gray-600 mt-2">{{ $method['description'] }}</p>

                <p class="text-sm font-medium mt-3">Keep ready:</p>
                <ul class="text-sm text-gray-600 list-disc pl-5 mt-1 space-y-1">
                    @foreach ($method['details'] as $detail)
                        <li>{{ $detail }}</li>
                    @endforeach
                </ul>
            </div>
        @endforeach

    </div>

    <div class="bg-white border rounded-xl p-5">
        <h2 class="text-lg font-semibold text-gray-900">After finding the case</h2>

        <ol class="list-decimal pl-5 mt-3 space-y-2 text-sm text-gray-700">
            <li>Open the case details on the official court portal.</li>
            <li>Copy the available case information and retain its official source URL.</li>
            <li>Return to VakilNeeti and open the assisted import page.</li>
            <li>Review the parsed information and correct any extraction errors.</li>
            <li>Save the verified record to your case diary.</li>
        </ol>

        <div class="flex flex-wrap gap-3 mt-5">
            <a href="{{ route('cases.import') }}"
               class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-700 text-white text-sm font-medium hover:bg-blue-800">
                Import Official Case Record
            </a>

            <a href="{{ route('cases.import.history') }}"
               class="inline-flex items-center px-4 py-2 rounded-lg border text-sm font-medium hover:bg-gray-50">
                View Import History
            </a>
        </div>
    </div>

    <div class="bg-amber-50 border border-amber-200 rounded-xl p-5">
        <h3 class="font-semibold text-amber-900">Integration status</h3>
        <p class="text-sm text-amber-900 mt-2">
            This workflow currently uses official-portal-assisted search and import.
            Live API retrieval and automatic synchronisation are not enabled or verified.
            CAPTCHA and other official access controls must be completed on the government portal.
        </p>
    </div>

    <p class="text-xs text-gray-500">
        Source: eCourts Services, Government of India. Availability of search fields and case information
        depends on the selected court and the services provided by its official portal.
    </p>

</div>
@endsection