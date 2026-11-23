@extends('layouts.app')

@section('title', 'Set up your account')
@section('page-heading', 'Set up your account')

@section('content')
@php
    $accountRole = old('account_role');
    $companyRoles = ['law_firm', 'commercial_company'];
    $accountType = in_array($accountRole, $companyRoles, true) ? 'company' : 'individual';
    $initialStep = $errors->has('account_role') ? 1 : (
        $errors->has('onboarding_features') ||
        $errors->has('onboarding_features.*') ||
        $errors->has('onboarding_other') ? 2 : (
            $errors->any() ? 3 : 0
        )
    );
@endphp

<div class="fixed inset-0 z-[100] flex items-center justify-center overflow-hidden bg-transparent p-3 sm:p-5">
    <div role="dialog"
         aria-modal="true"
         aria-labelledby="account-setup-title"
         tabindex="-1"
         class="relative max-h-[85vh] w-full max-w-2xl overflow-y-auto overscroll-contain rounded-2xl bg-white px-4 pb-5 pt-12 shadow-2xl sm:max-h-[85vh] sm:p-6 lg:p-7">
    

    @if($errors->any())
        <div role="alert" class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <p class="mb-1 font-semibold">Please review the highlighted setup details.</p>
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="relative z-10 mx-auto mb-5 flex h-3 w-24 items-center justify-center gap-2"
     role="progressbar" style="display:flex !important; visibility:visible !important; opacity:1 !important;"
     aria-label="Setup progress"
     aria-valuemin="1"
     aria-valuemax="4"
     aria-valuenow="{{ $initialStep + 1 }}">
    @foreach(range(0, 3) as $step)
        <span data-progress-segment="{{ $step }}"
              class="block h-1.5 w-4 shrink-0 rounded-full {{ $step <= $initialStep ? 'bg-amber-500' : 'bg-slate-200' }}">
        </span>
    @endforeach
</div>

    <form method="POST" action="{{ route('register.profile.submit') }}" enctype="multipart/form-data"
          data-onboarding-form
          data-initial-step="{{ $initialStep }}" class="space-y-6">
        @csrf
        <button type="button" data-back data-top-back hidden
                aria-label="Go to previous step"
                class="absolute left-4 top-3 z-20 flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 shadow-sm hover:bg-slate-100 transition-all duration-200 ease-in-out focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M19 12H5m7 7-7-7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>

        <section data-onboarding-step="0" class="rounded-xl border border-slate-200 bg-white p-6 text-center shadow-sm sm:p-9">
            <div class="mb-3 flex justify-center"><img src="{{ asset('images/logo.svg') }}" alt="VakilNiti" class="h-12 w-auto max-w-[220px] object-contain"></div>
            <h2 tabindex="-1" class="mt-3 text-xl font-bold text-slate-900">Welcome to VakilNiti</h2>
            <p class="mx-auto mt-3 max-w-xl text-slate-600">
                Let's find out how we can help you with your cases.
            </p>
            <button type="button" data-next class="mt-6 rounded-lg bg-amber-400 px-8 py-3 font-semibold text-slate-950 hover:bg-amber-300 transition-all duration-200 ease-in-out focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 hover:bg-amber-500 hover:text-white hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0 active:shadow-sm">
                Get started
            </button>
        </section>

        <section data-onboarding-step="1" hidden class="space-y-5">
            <div class="text-center">
                <h2 tabindex="-1" class="text-2xl font-bold text-slate-900">Which role best describes you?</h2>
                <p class="mt-2 text-slate-600">Choose the closest match. You can update your practice details later.</p>
            </div>
            <fieldset>
                <legend class="sr-only">Choose your role</legend>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($accountRoles as $value => $label)
                        <label class="flex min-h-28 cursor-pointer items-center rounded-xl border border-slate-200 bg-white p-5 transition hover:border-amber-400 has-[:checked]:border-amber-500 has-[:checked]:bg-amber-50 has-[:checked]:ring-1 has-[:checked]:ring-amber-500">
                            <input type="radio" name="account_role" value="{{ $value }}" @checked($accountRole === $value)
                                   class="mr-3 border-slate-300 text-amber-600 focus:ring-amber-500">
                            <span>
                                <span class="block font-semibold text-slate-900">{{ $label }}</span>
                                <span class="mt-1 block text-sm text-slate-500">
                                    @if($value === 'law_firm' || $value === 'commercial_company')
                                        Set up a company workspace and business profile.
                                    @elseif($value === 'student_intern')
                                        Create a workspace for learning and legal work experience.
                                    @else
                                        Set up your individual advocate profile and practice.
                                    @endif
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>
                <p data-role-error role="alert" class="mt-3 text-sm text-red-600" hidden>Please choose the role that best describes you.</p>
                @error('account_role') <span class="mt-2 block text-sm text-red-600">{{ $message }}</span> @enderror
            </fieldset>
            <div class="flex justify-center">
                
                <button type="button" data-next class="rounded-lg bg-amber-400 px-6 py-2.5 font-semibold text-slate-950 hover:bg-amber-300 transition-all duration-200 ease-in-out focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 hover:bg-amber-500 hover:text-white hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0 active:shadow-sm">Continue</button>
            </div>
        </section>

        <section data-onboarding-step="2" hidden class="space-y-5">
            <div class="text-center">
                <h2 tabindex="-1" class="text-2xl font-bold text-slate-900">What would you like to use?</h2>
                <p class="mt-2 text-slate-600">Select every feature that is useful to your work.</p>
            </div>
            <fieldset>
                <legend class="sr-only">Choose the features you need</legend>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($onboardingFeatures as $value => $label)
                        <label class="flex min-h-28 cursor-pointer flex-col items-center justify-center rounded-xl border border-slate-200 bg-white p-5 text-center transition hover:border-amber-400 has-[:checked]:border-amber-500 has-[:checked]:bg-amber-50 has-[:checked]:ring-1 has-[:checked]:ring-amber-500">
                            <input type="checkbox" name="onboarding_features[]" value="{{ $value }}"
                                   @checked(in_array($value, (array) old('onboarding_features', []), true))
                                   class="mb-3 h-5 w-5 rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                            <span class="font-medium text-slate-800">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                <label data-other-feature-field class="mt-4 block text-sm font-medium text-slate-700" hidden>
                    What other feature do you need?
                    <textarea name="onboarding_other" rows="2" maxlength="500" disabled
                              class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5">{{ old('onboarding_other') }}</textarea>
                    @error('onboarding_other') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <p data-features-error role="alert" class="mt-3 text-sm text-red-600" hidden>Select at least one feature to continue.</p>
                @error('onboarding_features') <span class="mt-2 block text-sm text-red-600">{{ $message }}</span> @enderror
                @error('onboarding_features.*') <span class="mt-2 block text-sm text-red-600">{{ $message }}</span> @enderror
            </fieldset>
            <p class="text-center text-sm text-slate-500">You can adjust your workspace preferences later.</p>
            <div class="flex justify-center">
                
                <button type="button" data-next class="rounded-lg bg-amber-400 px-6 py-2.5 font-semibold text-slate-950 hover:bg-amber-300 transition-all duration-200 ease-in-out focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 hover:bg-amber-500 hover:text-white hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0 active:shadow-sm">Continue</button>
            </div>
        </section>

        <section data-onboarding-step="3" hidden class="space-y-5">
            <div>
                <h2 tabindex="-1" class="text-2xl font-bold text-slate-900">Account information</h2>
                <p class="mt-2 text-slate-600">Add your account details to set up your workspace.</p>
            </div>

            <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <h3 class="text-lg font-bold text-slate-900">Account holder</h3>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <label class="text-sm font-medium text-slate-700">
                        <span data-name-label data-individual-label="Full name (as per Vakalatnama)" data-company-label="Your full name">Full name (as per Vakalatnama)</span>
                        <span class="text-red-600">*</span>
                        <input name="full_name" value="{{ old('full_name') }}" required maxlength="150" autocomplete="name"
                               class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5">
                        @error('full_name') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="text-sm font-medium text-slate-700">
                        Verified phone number
                        <input value="{{ $pending['mobile'] }}" readonly class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-slate-600">
                    </label>
                    <label class="text-sm font-medium text-slate-700 sm:col-span-2">
                        Account email <span class="text-red-600">*</span>
                        <input type="email" name="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email"
                               class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5">
                        @error('email') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </label>
                </div>
            </section>

            <div data-registration-profile="individual" @if($accountType !== 'individual') hidden @endif class="space-y-6">
                <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    <h3 class="text-lg font-bold text-slate-900">Bar Council enrolment details</h3>
                    <p class="mt-1 text-sm text-slate-500">These details are used for advocate identification and case fetching.</p>

                    <div class="mt-4">
                        <x-bar-registration-fields :state-groups="$stateGroups" :student-optional="true" />
                    </div>
                </section>
            </div>
            <div data-registration-profile="company" @if($accountType !== 'company') hidden @endif class="space-y-6">
                <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    <h3 class="text-lg font-bold text-slate-900">Company / law firm details</h3>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2" data-location-picker data-districts-url="{{ url('/api/court-directory/states') }}" data-cities-url="{{ url('/api/locations/districts') }}">
                        <label class="text-sm font-medium text-slate-700 sm:col-span-2">
                            Company / firm name <span class="text-red-600">*</span>
                            <input name="firm_name" value="{{ old('firm_name') }}" required maxlength="255" autocomplete="organization"
                                   class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5">
                            @error('firm_name') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                        </label>
                        <label class="text-sm font-medium text-slate-700 sm:col-span-2">
                            Proprietor / owner name
                            <input name="proprietor_name" value="{{ old('proprietor_name', old('full_name')) }}" maxlength="255"
                                   class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5">
                            @error('proprietor_name') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                        </label>
                        <label class="text-sm font-medium text-slate-700 sm:col-span-2">
                            Business address
                            <textarea name="address" rows="2" maxlength="5000" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5">{{ old('address') }}</textarea>
                            @error('address') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                        </label>
                        <div class="sm:col-span-2">
                            <label for="registration-region" class="block text-sm font-medium text-slate-700">State / Union Territory <span class="text-red-600">*</span></label>
                            <select id="registration-region" name="region" required data-location-state
                                    class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5">
                                <x-state-options :groups="$stateGroups" :selected="old('region')" />
                            </select>
                            @error('region') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label for="registration-district" class="block text-sm font-medium text-slate-700">District <span class="text-red-600">*</span></label>
                            <select data-location-district-select aria-label="District" hidden
                                    class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5"></select>
                            <input id="registration-district" name="district" value="{{ old('district') }}" required maxlength="255"
                                   data-location-district-input placeholder="Type your district"
                                   class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5">
                            <span data-location-district-hint class="mt-1 block text-xs text-slate-500" hidden></span>
                            @error('district') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div class="text-sm font-medium text-slate-700">
                            <label for="registration-city">City / Town <span class="text-red-600">*</span></label>
                            <select data-location-city-select aria-label="City or town" hidden
                                    class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5"></select>
                            <input id="registration-city" name="city" value="{{ old('city') }}" required maxlength="255" autocomplete="address-level2"
                                   data-location-city-input placeholder="Type the city or town"
                                   class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5">
                            @error('city') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <label class="text-sm font-medium text-slate-700">
                            GST state code
                            <input value="{{ \App\Models\State::GST_STATE_CODES[old('region')] ?? '' }}" readonly data-location-gst-code
                                   class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-slate-600">
                            <span class="mt-1 block text-xs font-normal text-slate-500">Filled in automatically.</span>
                        </label>
                        <label class="text-sm font-medium text-slate-700">
                            PIN code <span class="text-red-600">*</span>
                            <input name="pincode" value="{{ old('pincode') }}" required inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                                   class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5">
                            @error('pincode') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                        </label>
                        <label class="text-sm font-medium text-slate-700">
                            GSTIN <span class="font-normal text-slate-400">(optional)</span>
                            <input name="gstin" value="{{ old('gstin') }}" maxlength="15" placeholder="15-character GSTIN"
                                   class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 uppercase">
                            @error('gstin') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                        </label>
                        <label class="text-sm font-medium text-slate-700">
                            Business email
                            <input type="email" name="firm_email" value="{{ old('firm_email') }}" maxlength="255" autocomplete="email"
                                   class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5">
                            @error('firm_email') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                        </label>
                        <label class="text-sm font-medium text-slate-700">
                            Business phone
                            <input name="firm_phone" value="{{ old('firm_phone', $pending['mobile']) }}" maxlength="20" inputmode="tel"
                                   class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5">
                            @error('firm_phone') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                        </label>
                    </div>
                </section>
            </div>

            <div class="flex justify-center">
                
                <button type="submit" class="rounded-lg bg-amber-400 px-7 py-3 font-semibold text-slate-950 hover:bg-amber-300 transition-all duration-200 ease-in-out focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 hover:bg-amber-500 hover:text-white hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0 active:shadow-sm">
                    Complete signup
                </button>
            </div>
        </section>
    </form>
    </div>
</div>

<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('form[data-onboarding-form]');
        if (!form) return;

        const steps = Array.from(form.querySelectorAll('[data-onboarding-step]'));
        const progressSegments = document.querySelectorAll('[data-progress-segment]');
        const progressBar = document.querySelector('[role="progressbar"]');
        let currentStep = Number(form.dataset.initialStep || 0);
        const topBackButton = form.querySelector('[data-top-back]');

        function selectedRole() {
            return form.querySelector('input[name="account_role"]:checked')?.value || '';
        }

        function updateAccountFields() {
            const role = selectedRole();
            const isCompany = ['law_firm', 'commercial_company'].includes(role);
            const isStudent = role === 'student_intern';

            form.querySelectorAll('[data-registration-profile]').forEach(function (section) {
                const active = role !== '' && section.dataset.registrationProfile === (isCompany ? 'company' : 'individual');
                section.hidden = !active;
                section.querySelectorAll('input, select, textarea').forEach(function (field) {
                    field.disabled = !active;
                });
            });

            const nameLabel = document.querySelector('[data-name-label]');
            nameLabel.textContent = isCompany ? nameLabel.dataset.companyLabel : nameLabel.dataset.individualLabel;

            form.querySelectorAll('[data-student-optional]').forEach(function (field) {
                field.required = !isStudent;
            });
            ['bar', 'year'].forEach(function (field) {
                document.querySelector('[data-student-' + field + '-required]')?.toggleAttribute('hidden', isStudent);
                document.querySelector('[data-student-' + field + '-optional]')?.toggleAttribute('hidden', !isStudent);
            });

            const otherSelected = Boolean(form.querySelector('input[name="onboarding_features[]"][value="other"]:checked'));
            const otherField = document.querySelector('[data-other-feature-field]');
            otherField.hidden = !otherSelected;
            const otherInput = otherField.querySelector('textarea');
            otherInput.disabled = !otherSelected;
            otherInput.required = otherSelected;
        }

        function showStep(index) {
            currentStep = Math.max(0, Math.min(index, steps.length - 1));
            if (topBackButton) topBackButton.hidden = currentStep === 0;
            steps.forEach(function (step, stepIndex) {
                step.hidden = stepIndex !== currentStep;
            });
            progressBar.setAttribute('aria-valuenow', String(currentStep + 1));
            progressSegments.forEach(function (segment, stepIndex) {
                segment.classList.toggle('bg-amber-500', stepIndex <= currentStep);
                segment.classList.toggle('bg-slate-200', stepIndex > currentStep);
            });
            steps[currentStep].querySelector('h2, h3, button')?.focus({ preventScroll: true });
        }

        form.querySelectorAll('[data-next]').forEach(function (button) {
            button.addEventListener('click', function () {
                if (currentStep === 1 && !selectedRole()) {
                    document.querySelector('[data-role-error]').hidden = false;
                    return;
                }

                if (currentStep === 2) {
                    const hasFeature = form.querySelector('input[name="onboarding_features[]"]:checked');
                    const featuresError = document.querySelector('[data-features-error]');
                    featuresError.hidden = Boolean(hasFeature);
                    if (!hasFeature) return;

                    const otherInput = form.querySelector('[name="onboarding_other"]');
                    if (!otherInput.disabled && !otherInput.value.trim()) {
                        otherInput.required = true;
                        otherInput.reportValidity();
                        return;
                    }
                }

                updateAccountFields();
                showStep(currentStep + 1);
            });
        });

        form.querySelectorAll('[data-back]').forEach(function (button) {
            button.addEventListener('click', function () {
                showStep(currentStep - 1);
            });
        });

        form.querySelectorAll('input[name="account_role"]').forEach(function (input) {
            input.addEventListener('change', function () {
                document.querySelector('[data-role-error]').hidden = true;
                updateAccountFields();
            });
        });

        form.querySelectorAll('input[name="onboarding_features[]"]').forEach(function (input) {
            input.addEventListener('change', updateAccountFields);
        });

        updateAccountFields();
        showStep(currentStep);
    });
</script>
@endsection




