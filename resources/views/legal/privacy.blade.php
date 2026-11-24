@extends('layouts.app')

@section('title', 'Privacy policy')
@section('page-heading', 'Privacy policy')
@section('meta_description', 'How VakilNiti collects, uses and protects personal data of advocates and their clients, and your rights under the Digital Personal Data Protection Act, 2023.')

@section('content')
<article class="mx-auto max-w-3xl space-y-5 rounded-2xl border border-slate-200 bg-white p-5 text-[15px] leading-relaxed text-slate-700 shadow-sm sm:p-8">
    <header>
        <h1 class="text-2xl font-semibold text-slate-900">Privacy policy</h1>
        <p class="mt-1 text-sm text-slate-500">Last updated: 24 November 2026</p>
    </header>

    <section class="space-y-2">
        <h2 class="text-lg font-semibold text-slate-900">Who we are</h2>
        <p>VakilNiti is a practice-management service for advocates in India: case diary, hearings and reminders, clients and invoices, and court information. For any question about your data, or to use your rights below, write to <a href="mailto:support@vakilniti.com" class="font-semibold text-[#16776e]">support@vakilniti.com</a>. This address is also our grievance contact under the Digital Personal Data Protection Act, 2023.</p>
    </section>

    <section class="space-y-2">
        <h2 class="text-lg font-semibold text-slate-900">What we collect</h2>
        <ul class="list-disc space-y-1 pl-5">
            <li><strong>Your account:</strong> name, mobile number, email, Bar Council enrolment details and office details you give us.</li>
            <li><strong>What you enter:</strong> cases, hearings, tasks, notes, documents you upload, and your clients' names and contact details.</li>
            <li><strong>Technical records:</strong> sign-in records, the browser and device used, and error logs, to keep the service secure and working.</li>
        </ul>
    </section>

    <section class="space-y-2">
        <h2 class="text-lg font-semibold text-slate-900">Why we use it</h2>
        <p>Only to run VakilNiti for you: show and remind you of your hearings, send sign-in codes by email, prepare invoices, keep the service secure and answer your support requests. We do not sell personal data, and we do not show advertising.</p>
    </section>

    <section class="space-y-2">
        <h2 class="text-lg font-semibold text-slate-900">Your clients' data</h2>
        <p>When you store a client's details, you decide what is stored and why; VakilNiti processes it only to provide the service to you. Please store only what your practice needs, and keep it confidential as your professional duties require.</p>
    </section>

    <section class="space-y-2">
        <h2 class="text-lg font-semibold text-slate-900">Who else handles it</h2>
        <ul class="list-disc space-y-1 pl-5">
            <li><strong>Hosting:</strong> our web host stores the service and its data; its servers may be outside India.</li>
            <li><strong>Email:</strong> MSG91, the service that delivers your sign-in codes, receives your email address, your name and the code.</li>
            <li><strong>Your team:</strong> juniors and clerks you invite see your workspace as their role allows.</li>
            <li><strong>The law:</strong> we share data when a law or a court order requires it.</li>
        </ul>
        <p>Court information on VakilNiti comes from the courts' own public websites. We do not send your data to any court.</p>
    </section>

    <section class="space-y-2">
        <h2 class="text-lg font-semibold text-slate-900">How we protect it and how long we keep it</h2>
        <p>Every page uses an encrypted connection. Each account sees only its own workspace. A copy of the database is made every night and kept for 14 days. We keep your data while your account is active; when you ask us to delete your account, we delete it within 30 days, except what a law requires us to keep. Backups older than 14 days are removed by themselves.</p>
    </section>

    <section class="space-y-2">
        <h2 class="text-lg font-semibold text-slate-900">Your rights</h2>
        <p>Under the Digital Personal Data Protection Act, 2023, you can ask to see the personal data we hold about you, correct or complete it, have it erased, withdraw your consent, nominate someone to act for you, and raise a grievance. Write to <a href="mailto:support@vakilniti.com" class="font-semibold text-[#16776e]">support@vakilniti.com</a>; we reply within 30 days. If you are not satisfied, you may approach the Data Protection Board of India.</p>
    </section>

    <section class="space-y-2">
        <h2 class="text-lg font-semibold text-slate-900">Cookies</h2>
        <p>We use only the cookies the service needs: to keep you signed in and remember your settings. There are no advertising or tracking cookies.</p>
    </section>

    <section class="space-y-2">
        <h2 class="text-lg font-semibold text-slate-900">Changes</h2>
        <p>If this policy changes, the new version is posted here with a new date. VakilNiti is meant for people aged 18 and over.</p>
    </section>

    <p class="border-t border-slate-100 pt-4 text-sm"><a href="{{ route('legal.terms') }}" class="font-semibold text-[#16776e]">Terms of use &rarr;</a></p>
</article>
@endsection
