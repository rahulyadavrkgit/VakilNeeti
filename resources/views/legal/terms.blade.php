@extends('layouts.app')

@section('title', 'Terms of use')
@section('page-heading', 'Terms of use')
@section('meta_description', 'The terms for using VakilNiti, the case diary and practice tools for advocates in India.')

@section('content')
<article class="mx-auto max-w-3xl space-y-5 rounded-2xl border border-slate-200 bg-white p-5 text-[15px] leading-relaxed text-slate-700 shadow-sm sm:p-8">
    <header>
        <h1 class="text-2xl font-semibold text-slate-900">Terms of use</h1>
        <p class="mt-1 text-sm text-slate-500">Last updated: 24 November 2026</p>
    </header>

    <section class="space-y-2">
        <h2 class="text-lg font-semibold text-slate-900">The service</h2>
        <p>VakilNiti gives advocates tools to run their practice: a case diary, hearings and reminders, clients, invoices, and links to court information. It is not legal advice. Court lists, judges, rooms and links come from the courts' own websites and can change at any time; always confirm them with the court before relying on them.</p>
    </section>

    <section class="space-y-2">
        <h2 class="text-lg font-semibold text-slate-900">Your account</h2>
        <p>You sign in with your email address and a code sent to it. Keep your email account secure. You are responsible for what is done in your account, including by juniors and clerks you invite, and for removing people who should no longer have access.</p>
    </section>

    <section class="space-y-2">
        <h2 class="text-lg font-semibold text-slate-900">Your content</h2>
        <p>What you enter stays yours. You let VakilNiti store and process it only to provide the service to you. You are responsible for having the right to store your clients' details and for keeping them confidential, as your professional duties require. You can export your cases and billing records at any time.</p>
    </section>

    <section class="space-y-2">
        <h2 class="text-lg font-semibold text-slate-900">Fair use</h2>
        <p>Do not use VakilNiti for anything unlawful, try to reach another account's data, overload or copy the service by automated means, or upload harmful files. We may suspend an account that does.</p>
    </section>

    <section class="space-y-2">
        <h2 class="text-lg font-semibold text-slate-900">Reminders and availability</h2>
        <p>Reminders, live court boards and calendar links help you, but they can be late or stop working, for example when a court website is down. Keep your own diary of important dates. We work to keep VakilNiti available and your data safe, but we cannot promise it will always be free of interruptions or errors.</p>
    </section>

    <section class="space-y-2">
        <h2 class="text-lg font-semibold text-slate-900">Liability</h2>
        <p>As far as the law allows, VakilNiti is not responsible for a missed hearing, a lost matter or other loss arising from use of the service or of court information shown in it.</p>
    </section>

    <section class="space-y-2">
        <h2 class="text-lg font-semibold text-slate-900">Ending your use</h2>
        <p>You can stop using VakilNiti at any time and ask us to delete your account at <a href="mailto:support@vakilniti.com" class="font-semibold text-[#16776e]">support@vakilniti.com</a>. If these terms change, the new version is posted here with a new date.</p>
    </section>

    <section class="space-y-2">
        <h2 class="text-lg font-semibold text-slate-900">Law</h2>
        <p>These terms are governed by the laws of India, and the courts at Delhi have jurisdiction.</p>
    </section>

    <p class="border-t border-slate-100 pt-4 text-sm"><a href="{{ route('legal.privacy') }}" class="font-semibold text-[#16776e]">Privacy policy &rarr;</a></p>
</article>
@endsection
