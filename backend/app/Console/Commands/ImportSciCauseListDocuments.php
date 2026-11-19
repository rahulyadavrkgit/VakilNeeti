<?php

namespace App\Console\Commands;

use App\Models\ApiSyncError;
use App\Models\ApiSyncRun;
use App\Models\CourtDataSource;
use App\Models\CourtDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportSciCauseListDocuments extends Command
{
    protected $signature = 'sci:import-documents';

    protected $description = 'Import Supreme Court cause-list document metadata into Laravel';

    public function handle(): int
    {
        $providerKey = 'supreme_court_india';

        $jsonPath = storage_path('app/sci-cause-list-parsed.json');

        if (!file_exists($jsonPath)) {
            $this->error("Source JSON not found: {$jsonPath}");
            return self::FAILURE;
        }

        $source = CourtDataSource::where(
            'provider_key',
            $providerKey
        )->first();

        if (!$source) {
            $this->error('Supreme Court data source is not registered.');
            return self::FAILURE;
        }

        $data = json_decode(
            file_get_contents($jsonPath),
            true
        );

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid JSON: ' . json_last_error_msg());
            return self::FAILURE;
        }

        $documents = $data['documents'] ?? null;

        if (!is_array($documents)) {
            $this->error(
                'Expected a documents array in sci-cause-list-parsed.json.'
            );
            return self::FAILURE;
        }

        $run = ApiSyncRun::create([
            'provider_key' => $providerKey,
            'entity_type' => 'court_documents',
            'sync_mode' => 'incremental',
            'status' => 'running',
            'started_at' => now(),
            'metadata' => [
                'source_file' => 'sci-cause-list-parsed.json',
            ],
        ]);

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($documents as $document) {
            try {
                $url = trim($document['document_url'] ?? '');

                if ($url === '') {
                    $skipped++;
                    continue;
                }

                $listingDate = $document['listing_date'] ?? null;

                $documentKey = hash('sha256', implode('|', [
                    $listingDate ?? '',
                    $document['court_scope'] ?? '',
                    $document['category'] ?? '',
                    $document['list_type'] ?? '',
                    $document['sub_type'] ?? '',
                    $url,
                ]));

                $existing = CourtDocument::where(
                    'provider_key',
                    $providerKey
                )->where(
                    'document_key',
                    $documentKey
                )->first();

                $record = [
                    'provider_key' => $providerKey,
                    'court_id' => null,
                    'document_key' => $documentKey,
                    'listing_date' => $listingDate,
                    'court_scope' => $document['court_scope'] ?? null,
                    'category' => $document['category'] ?? null,
                    'list_type' => $document['list_type'] ?? null,
                    'sub_type' => $document['sub_type'] ?? null,
                    'document_label' => $document['document_label'] ?? null,
                    'source_url' => $url,
                    'metadata' => $document,
                ];

                CourtDocument::updateOrCreate(
                    [
                        'provider_key' => $providerKey,
                        'document_key' => $documentKey,
                    ],
                    $record
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
                    'external_id' => $document['document_url'] ?? null,
                    'error_code' => 'DOCUMENT_IMPORT_ERROR',
                    'message' => $exception->getMessage(),
                    'context' => $document,
                ]);
            }
        }

        $run->update([
            'status' => 'completed',
            'finished_at' => now(),
            'records_received' => count($documents),
            'records_created' => $created,
            'records_updated' => $updated,
            'records_skipped' => $skipped,
        ]);

        $source->update([
            'last_synced_at' => now(),
        ]);

        $this->info('Supreme Court document import completed.');
        $this->line("Received: {$run->records_received}");
        $this->line("Created: {$created}");
        $this->line("Updated: {$updated}");
        $this->line("Skipped: {$skipped}");

        return self::SUCCESS;
    }
}
