@extends('layouts.app')

@section('title', 'Public profile & CV')
@section('page-heading', 'Public profile & CV')
@section('back-url', route('profile.index'))

@section('content')
@php
    $chosenUsername = old('username', $username);
    $about = old('about', $settings['about'] ?? '');
    $showEmail = (bool) old('show_email', $settings['show_email']);
    $showPhone = (bool) old('show_phone', $settings['show_phone']);
    $publish = (bool) old('publish', $isPublished);
    $linkPrefix = preg_replace('#^https?://#', '', url('/cv')).'/';
@endphp
<div class="mx-auto max-w-6xl space-y-5">
    <x-page-header title="Public profile & CV" subtitle="A one-page profile made from your Advocate profile." />

    @if(session('cv_saved'))
        <p role="status" class="rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm font-semibold text-teal-900">{{ session('cv_saved') }}</p>
    @endif

    <div class="grid items-start gap-5 lg:grid-cols-[minmax(0,26rem)_minmax(0,1fr)]">
        {{-- Settings --}}
        <form method="POST" action="{{ route('profile.cv.update') }}" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-5">
            @csrf
            @method('PUT')

            {{-- Status and link --}}
            <div class="flex items-center justify-between gap-3">
                <span @class(['inline-flex items-center gap-2 rounded-full px-3 py-1 text-sm font-semibold', 'bg-teal-50 text-teal-900' => $isPublished, 'bg-slate-100 text-slate-700' => ! $isPublished])>
                    <span aria-hidden="true" @class(['h-2 w-2 rounded-full', 'bg-[#16776e]' => $isPublished, 'bg-slate-400' => ! $isPublished])></span>
                    {{ $isPublished ? 'Live' : 'Not public' }}
                </span>
                @if($publicUrl)
                    <button type="button" data-copy="{{ $publicUrl }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 px-3.5 text-sm font-semibold text-slate-800 hover:bg-slate-50"><span data-copy-label>Copy link</span></button>
                @endif
            </div>

            <div>
                <label for="cv-username" class="block text-sm font-semibold text-slate-900">Your link</label>
                <div @class(['mt-1.5 flex min-h-11 overflow-hidden rounded-xl border bg-white focus-within:ring-2 focus-within:ring-[#16776e]', 'border-red-400' => $errors->has('username'), 'border-slate-300' => ! $errors->has('username')])>
                    <span class="flex shrink-0 items-center bg-slate-50 pl-3 pr-1 text-sm text-slate-500">{{ $linkPrefix }}</span>
                    <input id="cv-username" name="username" value="{{ is_string($chosenUsername) ? $chosenUsername : '' }}" minlength="3" maxlength="30"
                           pattern="[a-zA-Z0-9](?:[a-zA-Z0-9\-]*[a-zA-Z0-9])?" autocapitalize="none" autocomplete="off" spellcheck="false" placeholder="your-name"
                           aria-describedby="cv-username-note" class="min-w-0 flex-1 border-0 px-1 text-sm focus:ring-0">
                </div>
                @error('username')
                    <p id="cv-username-note" role="alert" class="mt-1.5 text-sm text-red-700">{{ $message }}</p>
                @else
                    <p id="cv-username-note" class="mt-1.5 text-xs text-slate-500">Letters, numbers and hyphens.</p>
                @enderror
            </div>

            <div>
                <label for="cv-about" class="block text-sm font-semibold text-slate-900">About <span class="font-normal text-slate-500">(optional)</span></label>
                <textarea id="cv-about" name="about" rows="4" maxlength="1500" placeholder="A few lines about your practice."
                          class="mt-1.5 w-full rounded-xl border-slate-300 text-sm">{{ is_string($about) ? $about : '' }}</textarea>
                @error('about')<p role="alert" class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>

            <fieldset>
                <legend class="text-sm font-semibold text-slate-900">Show on page</legend>
                <div class="mt-1 flex flex-wrap gap-x-5">
                    <input type="hidden" name="show_email" value="0">
                    <label class="inline-flex min-h-11 items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="show_email" value="1" @checked($showEmail) class="rounded border-slate-300 text-[#16776e]"> Email
                    </label>
                    <input type="hidden" name="show_phone" value="0">
                    <label class="inline-flex min-h-11 items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="show_phone" value="1" @checked($showPhone) class="rounded border-slate-300 text-[#16776e]"> Phone
                    </label>
                </div>
            </fieldset>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-4">
                <input type="hidden" name="publish" value="0">
                <label class="inline-flex min-h-11 cursor-pointer items-center gap-3 text-sm font-semibold text-slate-900">
                    <input type="checkbox" name="publish" value="1" @checked($publish) class="peer sr-only">
                    <span aria-hidden="true" class="relative h-6 w-11 rounded-full bg-slate-300 transition peer-checked:bg-[#16776e] peer-focus-visible:ring-2 peer-focus-visible:ring-[#16776e] peer-focus-visible:ring-offset-2 after:absolute after:top-0.5 after:left-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow after:transition peer-checked:after:translate-x-5"></span>
                    Public page
                </label>
                <button class="inline-flex min-h-11 items-center rounded-xl bg-[#16776e] px-5 text-sm font-semibold text-white shadow-sm hover:bg-[#125f58]">Save</button>
            </div>
        </form>

        {{-- Preview --}}
        <section aria-label="Preview" class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 px-4 py-2">
                <h2 class="text-sm font-semibold text-slate-900">Preview</h2>
                <div class="flex flex-wrap gap-1">
                    <a href="{{ $publicUrl ?? route('profile.cv.preview') }}" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center rounded-lg px-3 text-sm font-semibold text-[#16776e] hover:bg-slate-50">Open</a>
                    <a href="{{ route('profile.cv.download') }}" class="inline-flex min-h-11 items-center rounded-lg px-3 text-sm font-semibold text-[#16776e] hover:bg-slate-50">Download PDF</a>
                </div>
            </div>
            <iframe src="{{ route('profile.cv.preview', ['embed' => 1]) }}" title="Preview of your public page" loading="lazy" class="block h-[34rem] w-full bg-[#f6f5f1] lg:h-[40rem]"></iframe>
            <p class="border-t border-slate-200 px-4 py-3 text-xs text-slate-600">
                @if($missing)
                    Not shown yet: {{ implode(', ', $missing) }}.
                @else
                    Details come from your profile.
                @endif
                <a href="{{ route('profile.advocate.index') }}" class="font-semibold text-[#16776e] underline underline-offset-2">Edit Advocate profile</a>
            </p>
        </section>
    </div>
</div>
@endsection
