<?php

namespace App\Console\Commands;

use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('cases:add-samples {workspace : Owner ID of the local workspace}')]
#[Description('Add clearly labelled fictional hearings to a local workspace')]
class AddSampleCourtCases extends Command
{
    public function handle(): int
    {
        if (! app()->environment('local') || config('database.default') !== 'sqlite') {
            $this->error('Sample cases can only be added to a local SQLite workspace.');

            return self::FAILURE;
        }

        $owner = User::query()->find($this->argument('workspace'));
        if (! $owner || $owner->isTeamMember()) {
            $this->error('Choose the ID of a local workspace owner.');

            return self::FAILURE;
        }

        $samples = [
            ['SC', 'Sample Foundation vs Example Council', 'Supreme Court of India', 'supreme', null, null, '3', '12', 54, 'Admission'],
            ['DHC', 'Example Traders vs Sample Authority', 'High Court of Delhi', 'high', 'DL', null, '18', '64', 112, 'Final arguments'],
            ['AHC', 'Sample Association vs Example Board', 'Allahabad High Court', 'high', 'UP', null, '90', '97', 114, 'Directions'],
            ['DC', 'Example Builder vs Sample Homeowner', 'District and Sessions Court, New Delhi', 'district', 'DL', 'New Delhi', '512', '17', 63, 'Evidence'],
            ['NCLT', 'Sample Manufacturing vs Example Finance', 'National Company Law Tribunal, New Delhi', 'tribunal', 'DL', null, '2', '8', 42, 'Admission'],
            ['CAT', 'Example Officer vs Sample Department', 'Central Administrative Tribunal, Principal Bench', 'tribunal', 'DL', null, '5', '11', 36, 'Reply'],
            ['NGT', 'Sample Residents vs Example Industries', 'National Green Tribunal, Principal Bench', 'tribunal', 'DL', null, '1', '6', 28, 'Compliance'],
            ['CON', 'Example Consumer vs Sample Retailer', 'Delhi State Consumer Disputes Redressal Commission', 'tribunal', 'DL', null, '4', '9', 31, 'Arguments'],
            ['WAIT', 'Sample Applicant vs Example Society', 'Civil Court, Lucknow', 'district', 'UP', 'Lucknow', null, null, null, 'Directions'],
        ];

        DB::transaction(function () use ($owner, $samples): void {
            foreach ($samples as $offset => [$code, $title, $court, $level, $state, $district, $room, $item, $total, $stage]) {
                $reference = 'VN-SAMPLE-'.$code;
                $sample = LegalCase::query()->withTrashed()->where('user_id', $owner->id)->where('source_type', 'sample')
                    ->firstOrNew(['source_reference' => $reference]);

                if ($sample->trashed()) {
                    continue;
                }

                if (! $sample->exists) {
                    $sample->forceFill([
                        'user_id' => $owner->id,
                        'case_title' => '[Sample] '.$title,
                        'case_number' => $reference,
                        'file_number' => $reference,
                        'court_name' => $court,
                        'court_level' => $level,
                        'state_code' => $state,
                        'district' => $district,
                        'court_room' => $room,
                        'judge_name' => $room ? 'Sample bench' : null,
                        'case_status' => 'pending',
                        'stage' => $stage,
                        'appearing_for' => 'applicant',
                        'source_type' => 'sample',
                        'source_reference' => $reference,
                        'case_description' => 'Fictional matter for previewing hearing dates, listing details and sharing.',
                    ])->save();
                }

                $hearing = $sample->hearings()->whereDate('hearing_date', '>=', today())->firstOrNew(['source_reference' => $reference]);
                if (! $hearing->exists) {
                    $hearing->forceFill([
                        'user_id' => $owner->id,
                        'hearing_date' => today()->addWeekdays(1 + intdiv($offset, 3)),
                        'hearing_time' => '10:30',
                        'court_room' => $room,
                        'judge_name' => $room ? 'Sample bench' : null,
                        'item_number' => $item,
                        'list_total' => $total,
                        'hearing_stage' => $stage,
                        'source_type' => 'manual',
                        'source_reference' => $reference,
                    ])->save();
                }
                $this->line($reference.' /cases/'.$sample->id);
            }
        }, 3);

        return self::SUCCESS;
    }
}
