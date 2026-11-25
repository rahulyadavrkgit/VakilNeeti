<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Deletes an account for good. A practice owner's whole workspace goes with it (cases, hearings, clients, invoices,
 * documents and their files, profiles) together with the logins of its juniors and clerks. A junior's or clerk's own
 * account holds no data of its own (their work belongs to the owner's workspace), so only their login is removed.
 * The administrator audit log is kept.
 */
class AccountDeletion
{
    /**
     * Workspace tables keyed by user_id, children before the tables they point to.
     *
     * @var list<string>
     */
    private const WORKSPACE_TABLES = [
        'client_activities', 'case_parties', 'cause_list_appearances', 'court_hearings', 'case_notes', 'case_appointments',
        'case_documents', 'case_expenses', 'case_time_entries', 'case_links', 'tasks', 'tds_follow_ups', 'tds_deductions',
        'professional_tax_records', 'payments', 'invoices', 'case_tags', 'legal_cases', 'clients', 'custom_dashboards',
        'court_record_imports', 'court_stand_sides', 'firm_practice_districts', 'firm_practice_states', 'profile_contacts',
        'personal_profiles', 'professional_profiles', 'invoice_settings', 'firm_profiles', 'advocate_profiles',
    ];

    /**
     * @return array{cases: int, team: int} what was removed, for the audit note
     */
    public function delete(User $account): array
    {
        $files = ['local' => [], 'public' => []];
        $summary = ['cases' => 0, 'team' => 0];

        DB::transaction(function () use ($account, &$files, &$summary): void {
            $logins = [$account->id];

            if (! $account->isTeamMember()) {
                $workspace = $account->id;
                $caseIds = DB::table('legal_cases')->where('user_id', $workspace)->pluck('id');
                $invoiceIds = DB::table('invoices')->where('user_id', $workspace)->pluck('id');
                $summary['cases'] = $caseIds->count();

                $files['local'] = DB::table('case_documents')->where('user_id', $workspace)->pluck('storage_path')
                    ->concat(DB::table('case_expenses')->where('user_id', $workspace)->pluck('receipt_path'))
                    ->filter()->all();
                $files['public'] = DB::table('advocate_profiles')->where('user_id', $workspace)->pluck('photo_path')
                    ->concat(DB::table('firm_profiles')->where('user_id', $workspace)->pluck('logo_path'))
                    ->filter()->all();

                // Rows tied to the workspace through a case or an invoice rather than a user_id.
                DB::table('case_tag')->whereIn('legal_case_id', $caseIds)->delete();
                DB::table('invoice_items')->whereIn('invoice_id', $invoiceIds)->delete();
                DB::table('case_support_reports')->whereIn('case_id', $caseIds)->delete();
                DB::table('admin_review_feedback')->whereIn('case_id', $caseIds)->delete();

                // Nested folders: the deepest (newest) first, so no folder is removed before what it holds.
                DB::table('case_document_folders')->where('user_id', $workspace)->orderByDesc('id')->pluck('id')
                    ->each(fn (int $folderId) => DB::table('case_document_folders')->where('id', $folderId)->delete());

                foreach (self::WORKSPACE_TABLES as $table) {
                    DB::table($table)->where('user_id', $workspace)->delete();
                }

                $team = User::query()->where('team_owner_id', $workspace)->pluck('id');
                $summary['team'] = $team->count();
                $logins = [...$logins, ...$team->all()];
            }

            DB::table('notifications')->where('notifiable_type', User::class)->whereIn('notifiable_id', $logins)->delete();
            DB::table('user_registration_otps')->whereIn('user_id', $logins)->delete();
            DB::table('sessions')->whereIn('user_id', $logins)->delete();
            DB::table('users')->whereIn('id', array_diff($logins, [$account->id]))->delete();
            DB::table('users')->where('id', $account->id)->delete();
        }, 3);

        // Files go only once the records are gone for sure.
        foreach ($files as $disk => $paths) {
            if ($paths !== []) {
                Storage::disk($disk)->delete($paths);
            }
        }

        return $summary;
    }
}
