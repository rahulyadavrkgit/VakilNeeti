<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CriminalLawComparisonController extends Controller
{
    public function show(Request $request, string $pair): View
    {
        $comparisons = [
            'bns-ipc' => [
                'key' => 'bns-ipc',
                'title' => 'BNS 2023 vs IPC 1860',
                'new_name' => 'Bharatiya Nyaya Sanhita, 2023',
                'old_name' => 'Indian Penal Code, 1860',
                'new_short' => 'BNS',
                'old_short' => 'IPC',
                'subject' => 'Offences and punishments',
                'comparison_url' => 'https://www.ncrb.mha.gov.in/uploads/SankalanPortal/SectionTableBNS.html',
                'old_pdf' => 'https://indiacode.gov.in/bitstream/123456789/11091/1/the_indian_penal_code%2C_1860.pdf',
                'new_pdf' => 'https://indiacode.gov.in/bitstream/123456789/20062/1/a202345.pdf',
            ],
            'bnss-crpc' => [
                'key' => 'bnss-crpc',
                'title' => 'BNSS 2023 vs CrPC 1973',
                'new_name' => 'Bharatiya Nagarik Suraksha Sanhita, 2023',
                'old_name' => 'Code of Criminal Procedure, 1973',
                'new_short' => 'BNSS',
                'old_short' => 'CrPC',
                'subject' => 'Criminal procedure',
                'comparison_url' => 'https://www.ncrb.mha.gov.in/uploads/SankalanPortal/SectionTableBNSS.html',
                'old_pdf' => 'https://indiacode.gov.in/bitstream/123456789/15272/1/the_code_of_criminal_procedure%2C_1973.pdf',
                'new_pdf' => 'https://indiacode.gov.in/bitstream/123456789/21920/1/the_bharatiya_nagarik_suraksha_sanhita%2C_2023.pdf',
            ],
            'bsa-iea' => [
                'key' => 'bsa-iea',
                'title' => 'BSA 2023 vs Indian Evidence Act 1872',
                'new_name' => 'Bharatiya Sakshya Adhiniyam, 2023',
                'old_name' => 'Indian Evidence Act, 1872',
                'new_short' => 'BSA',
                'old_short' => 'IEA',
                'subject' => 'Law of evidence',
                'comparison_url' => 'https://www.ncrb.mha.gov.in/uploads/SankalanPortal/SectionTableBSA.html',
                'old_pdf' => 'https://indiacode.gov.in/bitstream/123456789/6819/1/indian_evidence_act_1872.pdf',
                'new_pdf' => 'https://indiacode.gov.in/bitstream/123456789/20063/1/aa202347.pdf',
            ],
        ];

        abort_unless(isset($comparisons[$pair]), 404);

        $comparison = $comparisons[$pair];

        $direction = $request->query('direction', 'new_to_old');
        if (! in_array($direction, ['new_to_old', 'old_to_new'], true)) {
            $direction = 'new_to_old';
        }

        $status = $request->query('status', 'all');
        $allowedStatuses = ['all', 'corresponding', 'changed', 'new', 'deleted'];

        if (! in_array($status, $allowedStatuses, true)) {
            $status = 'all';
        }

        $search = trim((string) $request->query('q', ''));

        $query = DB::table('law_section_comparisons')
            ->where('pair', $pair)
            ->where('direction', $direction)
            ->orderBy('row_order');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('left_text', 'like', '%'.$search.'%')
                    ->orWhere('right_text', 'like', '%'.$search.'%');
            });
        }

        $allRows = $query->get();

        // Hide chapter and document-title headings from section results.
        // Original imported records remain untouched in the database.
        $allRows = $allRows->filter(function ($row) {
            $left = trim((string) $row->left_text);
            $right = trim((string) $row->right_text);

            $hasSectionNumber =
                preg_match('/\d/', $left) ||
                preg_match('/\d/', $right);

            if ($hasSectionNumber) {
                return true;
            }

            $heading = $left.' '.$right;

            return ! preg_match(
                '/^\s*(chapter\b|indian penal code\b|code of criminal procedure\b|indian evidence act\b|bharatiya nyaya sanhita\b|bharatiya nagarik suraksha sanhita\b|bharatiya sakshya adhiniyam\b)/i',
                $heading
            );
        })->values();

        $perPage = 25;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $total = $allRows->count();

        $rows = new LengthAwarePaginator(
            $allRows->forPage($currentPage, $perPage)->values(),
            $total,
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $statusCounts = DB::table('law_section_comparisons')
            ->where('pair', $pair)
            ->where('direction', $direction)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('modules.criminal-law-comparison', [
            'comparison' => $comparison,
            'allComparisons' => $comparisons,
            'rows' => $rows,
            'direction' => $direction,
            'status' => $status,
            'search' => $search,
            'statusCounts' => $statusCounts,
            'resultCount' => $total,
        ]);
    }
}
