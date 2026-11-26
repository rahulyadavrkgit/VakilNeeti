<?php

namespace App\Http\Controllers;

use App\Models\CaseParty;
use App\Models\CourtHearing;
use App\Models\CourtRecordImport;
use App\Models\LegalCase;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OfficialCourtImportController extends Controller
{
    private function extract(string $record, array $labels): ?string
    {
        foreach ($labels as $label) {
            $pattern = '/(?:^|\r?\n)\s*'.preg_quote($label, '/').
                '\s*[:\-]\s*([^\r\n]+)/i';

            if (preg_match($pattern, $record, $matches)) {
                $value = trim($matches[1]);

                return $value !== '' ? $value : null;
            }
        }

        return null;
    }

    private function normaliseDate(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $value = trim($value);

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y'] as $format) {
            try {
                $date = Carbon::createFromFormat('!'.$format, $value);

                if ($date && $date->format($format) === $value) {
                    return $date->format('Y-m-d');
                }
            } catch (\Throwable $e) {
                // Try the next supported format.
            }
        }

        return null;
    }

    public function preview(Request $request)
    {
        $validated = $request->validate([
            'court_source' => ['required', 'string', 'max:255'],
            'official_record' => ['required', 'string', 'max:100000'],
            'source_url' => ['nullable', 'url', 'max:2048'],
        ]);

        $record = $validated['official_record'];

        $extracted = [
            'case_title' => $this->extract($record, [
                'Case Title', 'Cause Title',
            ]),
            'case_number' => $this->extract($record, [
                'Case Number', 'Case No.', 'Case Number and Year',
                'Registration Number', 'Registration No.',
            ]),
            'cnr_number' => $this->extract($record, [
                'CNR Number', 'CNR No.', 'CNR',
            ]),
            'case_type' => $this->extract($record, [
                'Case Type', 'Nature of Case',
            ]),
            'court_name' => $this->extract($record, [
                'Court Name', 'Name of Court', 'Court',
            ]),
            'court_complex' => $this->extract($record, [
                'Court Complex', 'Court Establishment', 'Establishment',
            ]),
            'filing_number' => $this->extract($record, [
                'Filing Number', 'Filing No.',
            ]),
            'filing_date' => $this->normaliseDate($this->extract($record, [
                'Filing Date', 'Date of Filing',
            ])),
            'case_status' => $this->extract($record, [
                'Case Status', 'Status',
            ]),
            'judge_name' => $this->extract($record, [
                'Judge Name', 'Judge', 'Coram',
            ]),
            'petitioner' => $this->extract($record, [
                'Petitioner', 'Plaintiff', 'Applicant',
            ]),
            'respondent' => $this->extract($record, [
                'Respondent', 'Defendant', 'Opposite Party',
            ]),
            'advocate' => $this->extract($record, [
                'Advocate', 'Counsel', 'Advocate Name',
            ]),
            'hearing_date' => $this->normaliseDate($this->extract($record, [
                'Hearing Date', 'Last Hearing Date', 'Date of Hearing',
            ])),
            'hearing_time' => $this->extract($record, [
                'Hearing Time', 'Court Time',
            ]),
            'hearing_stage' => $this->extract($record, [
                'Hearing Stage', 'Stage',
            ]),
            'proceedings' => $this->extract($record, [
                'Proceedings', 'Order Summary', 'Last Order',
            ]),
            'next_hearing_date' => $this->normaliseDate($this->extract($record, [
                'Next Hearing Date', 'Next Date', 'Next Listing Date',
            ])),
            'court_room' => $this->extract($record, [
                'Court Room', 'Court Hall', 'Court No.',
            ]),
        ];

        $import = CourtRecordImport::create([
            'source_name' => $validated['court_source'],
            'source_url' => $validated['source_url'] ?? null,
            'raw_content' => $record,
            'parsed_data' => $extracted,
            'import_status' => 'preview',
            'retrieved_at' => now(),
        ]);

        return view('cases.import-preview', [
            'import' => $import,
            'courtSource' => $validated['court_source'],
            'sourceUrl' => $validated['source_url'] ?? null,
            'officialRecord' => $record,
            'extracted' => $extracted,
        ]);
    }

    public function history()
    {
        $imports = CourtRecordImport::latest()->paginate(20);

        return view('cases.import-history', compact('imports'));
    }

    public function store(Request $request, CourtRecordImport $import)
    {
        abort_if(isset($import->parsed_data['method']), 404);
        if ($import->import_status === 'imported') {
            return redirect()
                ->route('cases.import.history')
                ->with('success', 'This record has already been added to My Case Diary.');
        }

        $data = $request->validate([
            'case_title' => ['required', 'string', 'max:255'],
            'case_number' => ['nullable', 'string', 'max:255'],
            'cnr_number' => ['nullable', 'string', 'max:255'],
            'case_type' => ['nullable', 'string', 'max:255'],
            'court_name' => ['nullable', 'string', 'max:255'],
            'court_complex' => ['nullable', 'string', 'max:255'],
            'filing_number' => ['nullable', 'string', 'max:255'],
            'filing_date' => ['nullable', 'date_format:Y-m-d'],
            'case_status' => ['nullable', 'string', 'max:255'],
            'judge_name' => ['nullable', 'string', 'max:255'],
            'petitioner' => ['nullable', 'string', 'max:1000'],
            'respondent' => ['nullable', 'string', 'max:1000'],
            'advocate' => ['nullable', 'string', 'max:1000'],
            'hearing_date' => ['nullable', 'date_format:Y-m-d'],
            'hearing_time' => ['nullable', 'string', 'max:50'],
            'hearing_stage' => ['nullable', 'string', 'max:255'],
            'proceedings' => ['nullable', 'string', 'max:10000'],
            'next_hearing_date' => ['nullable', 'date_format:Y-m-d'],
            'court_room' => ['nullable', 'string', 'max:255'],
        ]);

        $parsed = $import->parsed_data ?? [];

        $value = function (string $key) use ($data, $parsed) {
            return array_key_exists($key, $data)
                ? $data[$key]
                : ($parsed[$key] ?? null);
        };

        $cnr = trim((string) ($data['cnr_number'] ?? ''));
        $caseNumber = trim((string) ($data['case_number'] ?? ''));
        $courtName = trim((string) ($data['court_name'] ?? ''));

        if ($courtName === '') {
            $courtName = $import->source_name;
        }

        $existing = null;

        if ($cnr !== '') {
            $existing = LegalCase::where('cnr_number', $cnr)->first();
        }

        if (! $existing && $caseNumber !== '') {
            $existing = LegalCase::where('case_number', $caseNumber)
                ->where('court_name', $courtName)
                ->first();
        }

        if ($existing) {
            $import->update([
                'import_status' => 'duplicate',
                'legal_case_id' => $existing->id,
            ]);

            return redirect()
                ->route('cases.import.history')
                ->with('error', 'A matching case already exists. No duplicate case was created.');
        }

        $hearingDate = $this->normaliseDate($value('hearing_date'));
        $nextHearingDate = $this->normaliseDate($value('next_hearing_date'));

        $savedCase = DB::transaction(function () use (
            $data,
            $import,
            $value,
            $hearingDate,
            $nextHearingDate,
            $parsed,
            $courtName
        ) {
            $sourceReference = $import->source_url
                ? substr($import->source_url, 0, 255)
                : null;

            $case = LegalCase::create([
                'case_title' => $data['case_title'],
                'case_number' => $data['case_number'] ?: null,
                'cnr_number' => $data['cnr_number'] ?: null,
                'case_type' => $data['case_type'] ?: null,
                'court_name' => $courtName,
                'court_complex' => $data['court_complex'] ?: null,
                'judge_name' => $data['judge_name'] ?: null,
                'filing_number' => $data['filing_number'] ?: null,
                'filing_date' => $data['filing_date'] ?: null,
                'case_status' => $data['case_status'] ?: 'pending',
                'source_type' => 'official_portal_assisted_import',
                'source_reference' => $sourceReference,
            ]);

            $petitioner = trim((string) $value('petitioner'));
            $respondent = trim((string) $value('respondent'));

            if ($petitioner !== '') {
                CaseParty::create([
                    'case_id' => $case->id,
                    'party_name' => $petitioner,
                    'party_type' => 'petitioner',
                    'is_client' => false,
                ]);
            }

            if ($respondent !== '') {
                CaseParty::create([
                    'case_id' => $case->id,
                    'party_name' => $respondent,
                    'party_type' => 'respondent',
                    'is_client' => false,
                ]);
            }

            // Save a hearing record if either date is available.
            // The previous hearing date is allowed to be null.
            if ($hearingDate || $nextHearingDate) {
                CourtHearing::create([
                    'case_id' => $case->id,
                    'hearing_date' => $hearingDate,
                    'hearing_time' => $value('hearing_time'),
                    'court_room' => $value('court_room'),
                    'judge_name' => $data['judge_name'] ?: null,
                    'hearing_stage' => $value('hearing_stage'),
                    'proceedings' => $value('proceedings'),
                    'next_hearing_date' => $nextHearingDate,
                    'source_type' => 'official_portal_assisted_import',
                    'source_reference' => $import->source_url
                        ? substr($import->source_url, 0, 255)
                        : null,
                ]);
            }

            $updatedParsedData = array_merge($parsed, $data, [
                'court_name' => $courtName,
                'petitioner' => $petitioner ?: null,
                'respondent' => $respondent ?: null,
                'advocate' => $value('advocate'),
                'hearing_date' => $hearingDate,
                'next_hearing_date' => $nextHearingDate,
                'hearing_saved' => (bool) ($hearingDate || $nextHearingDate),
            ]);

            $import->update([
                'parsed_data' => $updatedParsedData,
                'import_status' => 'imported',
                'legal_case_id' => $case->id,
            ]);

            return $case;
        });

        return redirect()
            ->route('cases.show', $savedCase)
            ->with('success', 'Court record added to My Case Diary.');
    }
}
