<?php

namespace App\Services\CourtCatalogue;

class CatalogueSnapshotValidator
{
    public function validate(
        array $records,
        bool $fetchSucceeded,
        bool $completeSnapshot,
        bool $sourceVerified,
        int $minimumRecords = 1
    ): array {
        $errors = [];
        $identifiers = [];

        if (!$fetchSucceeded) {
            $errors[] = 'Official source fetch failed.';
        }

        if (!$sourceVerified) {
            $errors[] = 'Official source has not been verified for automated catalogue updates.';
        }

        if (count($records) < $minimumRecords) {
            $errors[] = 'Snapshot contains fewer records than the minimum threshold.';
        }

        foreach ($records as $index => $record) {
            if (!is_array($record) || empty($record['external_id'])) {
                $errors[] = "Record {$index} has no external identifier.";
                continue;
            }

            $identifier = (string) $record['external_id'];

            if (isset($identifiers[$identifier])) {
                $errors[] = "Duplicate external identifier: {$identifier}.";
                continue;
            }

            $identifiers[$identifier] = true;
        }

        $safeToReconcile = count($errors) === 0;

        return [
            'safe_to_reconcile' => $safeToReconcile,
            'safe_to_deactivate_missing_records' =>
                $safeToReconcile && $completeSnapshot,
            'complete_snapshot' => $completeSnapshot,
            'record_count' => count($records),
            'errors' => $errors,
        ];
    }
}