<?php

namespace App\Services;

use App\Models\LegalCase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

class AdminCaseReview
{
    public const FINDINGS = [
        'date' => ['Open cases without an upcoming date', 'Saved hearing dates and matched cause lists'],
        'court' => ['Incomplete court details', 'Saved court level, name, state and district'],
        'duplicate' => ['Possible duplicate cases', 'Matching CNR, or matching case number and court where a CNR is missing'],
        'court-conflict' => ['Conflicting CNR and court information', 'CNR state prefix and stored state'],
        'listing-conflict' => ['Saved dates and cause-list dates differ', 'Earliest upcoming saved date compared with the earliest matched cause-list date'],
    ];

    public const STATUSES = [
        'open' => 'Reopen',
        'confirmed' => 'Confirm finding',
        'resolved' => 'Mark resolved',
        'false-positive' => 'Mark false positive',
    ];

    public function cases(string $finding): Builder
    {
        abort_unless(array_key_exists($finding, self::FINDINGS), 404);
        $cases = LegalCase::query()->withoutGlobalScope('user_ownership');
        $today = today()->toDateString();
        $saved = DB::table('court_hearings')->whereColumn('court_hearings.case_id', 'legal_cases.id')
            ->whereColumn('court_hearings.user_id', 'legal_cases.user_id');
        $listed = DB::table('cause_list_appearances')->join('court_documents', 'court_documents.id', '=', 'cause_list_appearances.court_document_id')
            ->whereColumn('cause_list_appearances.legal_case_id', 'legal_cases.id')->where('listing_date', '>=', $today);
        $recorded = (clone $saved)->where('hearing_date', '>=', $today);
        $fixed = (clone $saved)->where('next_hearing_date', '>=', $today);
        $least = DB::connection()->getDriverName() === 'sqlite' ? 'min' : 'least';

        return match ($finding) {
            'date' => $cases->whereIn('case_status', ['pending', 'other'])
                ->whereNotExists($recorded)->whereNotExists($fixed)->whereNotExists($listed),
            'court' => $cases->where(fn (Builder $query) => $query->whereNull('court_level')->orWhereNull('court_name')->orWhere('court_name', '')
                ->orWhere(fn (Builder $district) => $district->where('court_level', 'district')
                    ->where(fn (Builder $fields) => $fields->whereNull('state_code')->orWhereNull('district')))),
            'duplicate' => $cases->whereExists(fn (QueryBuilder $duplicates) => $duplicates->from('legal_cases as other')
                ->whereColumn('other.user_id', 'legal_cases.user_id')->whereColumn('other.id', '<>', 'legal_cases.id')->whereNull('other.deleted_at')
                ->where(fn (QueryBuilder $matches) => $matches
                    ->where(fn (QueryBuilder $cnr) => $cnr->where('legal_cases.cnr_number', '<>', '')
                        ->whereColumn('other.cnr_number', 'legal_cases.cnr_number'))
                    ->orWhere(fn (QueryBuilder $number) => $number->where('legal_cases.case_number', '<>', '')
                        ->whereColumn('other.case_number', 'legal_cases.case_number')->whereColumn('other.court_name', 'legal_cases.court_name')
                        ->where(fn (QueryBuilder $missing) => $missing->whereNull('legal_cases.cnr_number')->orWhere('legal_cases.cnr_number', '')
                            ->orWhereNull('other.cnr_number')->orWhere('other.cnr_number', ''))))),
            'court-conflict' => $cases->whereNotNull('state_code')->whereRaw('length(cnr_number) = 16')
                ->whereRaw('upper(substr(cnr_number, 1, 2)) <> state_code'),
            'listing-conflict' => $cases->addSelect([
                'listed_date' => (clone $listed)->selectRaw('min(date(listing_date))'),
                'hearing_day' => (clone $recorded)->selectRaw('min(date(hearing_date))'),
                'fixed_day' => (clone $fixed)->selectRaw('min(date(next_hearing_date))'),
            ])->whereExists($listed)->where(fn (Builder $query) => $query->whereExists($recorded)->orWhereExists($fixed))
                ->whereRaw("(select min(date(listing_date)) from cause_list_appearances join court_documents on court_documents.id = cause_list_appearances.court_document_id where legal_case_id = legal_cases.id and listing_date >= ?)
                    <> {$least}(
                        coalesce((select min(date(hearing_date)) from court_hearings where case_id = legal_cases.id and user_id = legal_cases.user_id and hearing_date >= ?), ?),
                        coalesce((select min(date(next_hearing_date)) from court_hearings where case_id = legal_cases.id and user_id = legal_cases.user_id and next_hearing_date >= ?), ?)
                    )", [$today, $today, '9999-12-31', $today, '9999-12-31']),
        };
    }

    public function pending(string $finding): Builder
    {
        return $this->cases($finding)->whereNotExists(fn (QueryBuilder $feedback) => $feedback->from('admin_review_feedback')
            ->whereColumn('case_id', 'legal_cases.id')->where('finding', $finding)
            ->whereColumn('admin_review_feedback.updated_at', '>=', 'legal_cases.updated_at')
            ->whereIn('status', ['resolved', 'false-positive']));
    }
}
