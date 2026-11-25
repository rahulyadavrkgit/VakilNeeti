<?php

namespace App\Console\Commands;

use App\Models\CaseLink;
use App\Models\LegalCase;
use App\Services\CourtIdentity;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Fills court level, state, court code, district and matter number on saved cases (all advocates).
 * Safe to run again: it only reads the CNR and court names, and keeps matter numbers already given.
 */
#[Signature('cases:court-identity {--apply : Save the proposed changes}')]
#[Description('Work out which court and state every saved case belongs to, and give each a matter number')]
class FillCourtIdentity extends Command
{
    public function handle(CourtIdentity $identity): int
    {
        if ($this->option('apply')) {
            return DB::transaction(fn () => $this->fillCases($identity), 3);
        }

        DB::beginTransaction();

        try {
            return DB::transaction(fn () => $this->fillCases($identity), 3);
        } finally {
            DB::rollBack();
        }
    }

    private function fillCases(CourtIdentity $identity): int
    {
        $apply = (bool) $this->option('apply');
        $cases = fn () => LegalCase::query()->withoutGlobalScopes()->withTrashed()->orderBy('id');
        $filled = 0;

        // Twice: the first pass learns what each CNR code means from cases whose court complex names the
        // district, so the second pass can name the district on cases of the same court that do not.
        foreach ([1, 2] as $pass) {
            $cases()->chunkById(200, function ($chunk) use ($identity, $pass, $apply, &$filled) {
                foreach ($chunk as $case) {
                    if ($pass === 2 && ($case->district || $case->court_level !== 'district')) {
                        continue;
                    }

                    // Without a CNR, values already there (perhaps chosen by the advocate) are kept; only gaps are filled.
                    $identity->fill($case);
                    $case->matter_id ??= LegalCase::nextMatterId((int) $case->user_id);

                    if ($pass === 1 && $case->court_level === 'district') {
                        $inferred = $case->state_code ? $identity->districtFromText($case->state_code, $case->court_complex.', '.$case->court_name) : null;

                        if (! $case->district || ($inferred && $inferred !== $case->district)) {
                            $this->warn("Review district for case #{$case->id} (".($case->court_code ?: 'no code').').');
                        }
                    }

                    if ($case->isDirty()) {
                        if (! $apply) {
                            $this->line("Case #{$case->id}: ".($case->courtLabel() ?: 'court unknown').' / '.$case->matter_id);
                        }

                        $case->timestamps = false;
                        $case->saveQuietly();
                        $filled++;
                    }
                }
            });
        }

        CaseLink::query()->withoutGlobalScope('user_ownership')->where('relation', 'arises_from')
            ->eachById(fn (CaseLink $link) => $link->syncMatter());

        $this->info(($apply ? 'Updated ' : 'Previewed ').$filled.' case updates.');

        return self::SUCCESS;
    }
}
