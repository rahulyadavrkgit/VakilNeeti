<?php

namespace App\Console\Commands;

use DOMDocument;
use DOMXPath;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

class ImportCriminalLawComparisons extends Command
{
    protected $signature = 'criminal-laws:import-comparisons';

    protected $description = 'Import official NCRB criminal law section comparison tables';

    public function handle(): int
    {
        $sources = [
            'bns-ipc' => [
                'https://www.ncrb.mha.gov.in/uploads/SankalanPortal/SectionTableBNS.html',
                'Bharatiya Nyaya Sanhita, 2023',
                'Indian Penal Code, 1860',
            ],

            'bnss-crpc' => [
                'https://www.ncrb.mha.gov.in/uploads/SankalanPortal/SectionTableBNSS.html',
                'Bharatiya Nagarik Suraksha Sanhita, 2023',
                'Code of Criminal Procedure, 1973',
            ],

            'bsa-iea' => [
                'https://www.ncrb.mha.gov.in/uploads/SankalanPortal/SectionTableBSA.html',
                'Bharatiya Sakshya Adhiniyam, 2023',
                'Indian Evidence Act, 1872',
            ],
        ];

        foreach ($sources as $pair => $details) {
            $url = $details[0];

            $this->newLine();
            $this->info("Importing {$pair}");

            try {
                $response = Http::timeout(90)
                    ->withHeaders(['User-Agent' => 'VakilNiti Legal Research'])
                    ->get($url);

                if (! $response->successful()) {
                    $this->error("Source request failed: HTTP {$response->status()}");

                    continue;
                }

                $html = $response->body();

                if (stripos($html, '<html') === false) {
                    $this->error('The source did not return an HTML page.');

                    continue;
                }

                $dom = new DOMDocument;

                libxml_use_internal_errors(true);
                $loaded = $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
                libxml_clear_errors();

                if (! $loaded) {
                    $this->error('Could not parse the official HTML.');

                    continue;
                }

                $xpath = new DOMXPath($dom);
                $tables = $xpath->query('//table');

                if (! $tables || $tables->length === 0) {
                    $this->error('No HTML tables found. Import stopped for this pair.');

                    continue;
                }

                $imported = 0;

                foreach ($tables as $tableIndex => $table) {

                    $direction = $tableIndex === 0
                        ? 'new_to_old'
                        : 'old_to_new';

                    $rows = $xpath->query('.//tr', $table);
                    $rowOrder = 0;

                    foreach ($rows as $row) {
                        $cells = $xpath->query('./th|./td', $row);

                        if (! $cells || $cells->length < 2) {
                            continue;
                        }

                        $left = trim(preg_replace(
                            '/\s+/u',
                            ' ',
                            $cells->item(0)->textContent
                        ));

                        $right = trim(preg_replace(
                            '/\s+/u',
                            ' ',
                            $cells->item(1)->textContent
                        ));

                        if ($left === '' && $right === '') {
                            continue;
                        }

                        if (
                            stripos($left, $details[1]) !== false ||
                            stripos($right, $details[2]) !== false
                        ) {
                            continue;
                        }

                        if (
                            stripos($left, 'Search') !== false &&
                            stripos($right, 'Search') !== false
                        ) {
                            continue;
                        }

                        $combined = strtolower($left.' '.$right);

                        if (
                            str_contains($combined, 'deleted') ||
                            str_contains($combined, 'repealed')
                        ) {
                            $status = 'deleted';
                        } elseif (
                            str_contains($combined, 'new section') ||
                            str_contains($combined, 'new sub-section')
                        ) {
                            $status = 'new';
                        } elseif (
                            str_contains($combined, '(change)') ||
                            str_contains($combined, '(change')
                        ) {
                            $status = 'changed';
                        } else {
                            $status = 'corresponding';
                        }

                        $rowOrder++;

                        DB::table('law_section_comparisons')->updateOrInsert(
                            [
                                'pair' => $pair,
                                'direction' => $direction,
                                'row_order' => $rowOrder,
                            ],
                            [
                                'left_text' => $left,
                                'right_text' => $right,
                                'status' => $status,
                                'source_url' => $url,
                                'imported_at' => now(),
                                'updated_at' => now(),
                                'created_at' => now(),
                            ]
                        );

                        $imported++;
                    }
                }

                if ($imported === 0) {
                    $this->warn('No comparison rows imported. Check the source HTML structure.');
                } else {
                    $this->info("Imported or updated {$imported} rows.");
                }

            } catch (Throwable $exception) {
                $this->error($exception->getMessage());
            }
        }

        $this->newLine();
        $this->info('Import process finished.');

        return self::SUCCESS;
    }
}
