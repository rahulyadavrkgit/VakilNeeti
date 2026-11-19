<?php

namespace App\Console\Commands;

use App\Models\ApiSyncError;
use App\Models\ApiSyncRun;
use App\Models\CourtDocument;
use App\Models\CauseListAppearance;
use Illuminate\Console\Command;

class ImportSciCauseListAppearances extends Command
{
    protected $signature = 'sci:import-appearances';

    protected $description = 'Import extracted Supreme Court case appearances';

    public function handle(): int
    {
        $providerKey = 'supreme_court_india';

        $folder = storage_path(
            'app/sci-cause-list-party-data'
        );

        if (!is_dir($folder)) {
            $this->error('Party-data folder not found.');
            return self::FAILURE;
        }

        $files = glob($folder . DIRECTORY_SEPARATOR . '*.json');

        if (!$files) {
            $this->error('No extracted case JSON files found.');
            return self::FAILURE;
        }

        $run = ApiSyncRun::create([
            'provider_key' => $providerKey,
            'entity_type' => 'cause_list_appearances',
            'sync_mode' => 'incremental',
            'status' => 'running',
            'started_at' => now(),
            'metadata' => [
                'source_folder' => 'sci-cause-list-party-data',
                'files_found' => count($files),
            ],
        ]);

        $received = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($files as $file) {
            $data = json_decode(
                file_get_contents($file),
                true
            );

            if (
                json_last_error() !== JSON_ERROR_NONE ||
                !is_array($data) ||
                !isset($data['records']) ||
                !is_array($data['records'])
            ) {
                $skipped++;

                ApiSyncError::create([
                    'api_sync_run_id' => $run->id,
                    'external_id' => basename($file),
                    'error_code' => 'INVALID_CASE_JSON',
                    'message' => 'Invalid or unsupported case JSON file.',
                ]);

                continue;
            }

            $documentUrl = trim(
                $data['document_url'] ?? ''
            );

            $document = CourtDocument::where(
                'provider_key',
                $providerKey
            )->where(
                'source_url',
                $documentUrl
            )->first();

            if (!$document) {
                $skipped++;

                ApiSyncError::create([
                    'api_sync_run_id' => $run->id,
                    'external_id' => $documentUrl ?: basename($file),
                    'error_code' => 'DOCUMENT_NOT_FOUND',
                    'message' => 'Corresponding court document was not found.',
                    'context' => [
                        'file' => basename($file),
                    ],
                ]);

                continue;
            }

            foreach ($data['records'] as $record) {
                $received++;

                try {
                    $parties = $record['parties'] ?? [];

                    $rawText = $record['raw_text'] ?? '';

                    $recordKey = hash('sha256', implode('|', [
                        $document->document_key,
                        $record['item_number'] ?? '',
                        $record['source_line'] ?? '',
                        $record['case_reference_line'] ?? '',
                        $rawText,
                    ]));

                    $existing = CauseListAppearance::where(
                        'court_document_id',
                        $document->id
                    )->where(
                        'record_key',
                        $recordKey
                    )->first();

                    $caseReference = $record['case_reference_line'] ?? null;

                    $caseType = null;

                    if ($caseReference && preg_match(
                        '/^\s*((?:SLP|C\.A\.|Crl\.A\.|W\.P\.|T\.P\.|R\.P\.|M\.A\.|Diary)\s*(?:\([^)]*\))?)/i',
                        $caseReference,
                        $matches
                    )) {
                        $caseType = trim($matches[1]);
                    }

                    $attributes = [
                        'court_document_id' => $document->id,
                        'legal_case_id' => null,
                        'record_key' => $recordKey,
                        'item_number' => $record['item_number'] ?? null,
                        'case_reference' => $caseReference,
                        'case_type' => $caseType,
                        'classification' => $record['classification'] ?? null,

                        'case_title_raw' => $caseReference,

                        'petitioner_raw' =>
                            $parties['petitioner_line_raw'] ?? null,

                        'respondent_raw' =>
                            $parties['respondent_line_raw'] ?? null,

                        'advocates_raw' =>
                            $record['advocates'] ?? [],

                        'ia_details' =>
                            $record['ias'] ?? [],

                        'connected_matters' =>
                            $record['connected_matters'] ?? [],

                        'listing_notes' =>
                            $record['listing_notes'] ?? [],

                        'raw_text' => $rawText,

                        'source_line' =>
                            $record['source_line'] ?? null,

                        'match_status' => 'unmatched',

                        'metadata' => [
                            'source_file' => basename($file),
                            'party_extraction_status' =>
                                $parties['extraction_status'] ?? null,
                            'party_verification_status' =>
                                $parties['verification_status'] ?? null,
                            'extraction_verification' => 'unverified',
                        ],
                    ];

                    CauseListAppearance::updateOrCreate(
                        [
                            'court_document_id' => $document->id,
                            'record_key' => $recordKey,
                        ],
                        $attributes
                    );

                    if ($existing) {
                        $updated++;
                    } else {
                        $created++;
                    }
                } catch (\Throwable $exception) {
                    $skipped++;

                    ApiSyncError::create([
                        'api_sync_run_id' => $run->id,
                        'external_id' => $record['item_number'] ?? null,
                        'error_code' => 'APPEARANCE_IMPORT_ERROR',
                        'message' => $exception->getMessage(),
                        'context' => [
                            'file' => basename($file),
                            'record' => $record,
                        ],
                    ]);
                }
            }
        }

        $run->update([
            'status' => 'completed',
            'finished_at' => now(),
            'records_received' => $received,
            'records_created' => $created,
            'records_updated' => $updated,
            'records_skipped' => $skipped,
        ]);

        $this->info('Supreme Court appearance import completed.');

        $this->line("Received: {$received}");
        $this->line("Created: {$created}");
        $this->line("Updated: {$updated}");
        $this->line("Skipped: {$skipped}");

        return self::SUCCESS;
    }
}
