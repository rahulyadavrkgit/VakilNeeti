<div class="border-b border-slate-100 p-4 last:border-0">
    <div class="flex flex-wrap justify-between gap-3">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-1.5">
                <a href="{{ route('cases.show', $hearing->case_id) }}"
                   class="font-semibold text-blue-700 hover:underline">
                    {{ $hearing->case_title ?: 'Case title unavailable' }}
                </a>
                <x-case-side :side="$hearing->appearing_for ?? null" :parties="$hearing->appearing_for_parties ?? null" />
            </div>

            <p class="mt-1 text-sm text-slate-600">
                {{ $hearing->case_number ?: 'Case number unavailable' }}
                @if($hearing->cnr_number)
                    · CNR: {{ $hearing->cnr_number }}
                @endif
            </p>

            <x-court-info class="mt-1.5" :court="$hearing->court_name ?: 'Court unavailable'" :room="($hearing->court_room ?? null) ?: ($hearing->case_court_room ?? null)" :judge="($hearing->judge_name ?? null) ?: ($hearing->case_judge_name ?? null)" :vc-link="($hearing->hearing_vc_link ?? null) ?: ($hearing->vc_link ?? null)" />
        </div>

        <div class="text-sm text-slate-700">
            <p class="font-semibold">
                {{ \Carbon\Carbon::parse($hearing->next_hearing_date)->format('d M Y') }}
            </p>
            <p class="text-slate-500">
                {{ $hearing->hearing_time ?: 'Time not available' }}
            </p>
        </div>
    </div>

    <div class="mt-3 flex flex-wrap gap-x-5 gap-y-1 text-xs text-slate-500">
        @if($hearing->hearing_stage)
            <span>Stage: {{ $hearing->hearing_stage }}</span>
        @endif
        @if($hearing->next_action)
            <span>Next Action: {{ $hearing->next_action }}</span>
        @endif
    </div>
</div>