<?php

namespace App\Http\Controllers;

use App\Models\CauseListAppearance;
use App\Models\CourtDocument;
use App\Models\LegalCase;
use App\Models\User;
use App\Services\CauseLists\CauseListRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DistrictCauseListController extends Controller
{
    private const PROVIDER = 'district-court';

    public function store(Request $request, LegalCase $case, CauseListRecorder $recorder): RedirectResponse
    {
        abort_unless($case->court_level === 'district' && $case->cnr_number, 404);
        $data = $request->validateWithBag('districtList', [
            'hearing_date' => ['required', 'date_format:Y-m-d'],
            'room_label' => ['required', 'string', 'max:60'],
            'source' => ['required', 'url:https', 'max:2048'],
            'list_file' => ['required', 'file', 'extensions:csv', 'max:512'],
        ]);
        $host = strtolower((string) parse_url($data['source'], PHP_URL_HOST));
        if (! preg_match('/\A[a-z0-9.-]+\.(gov|nic)\.in\z/', $host)) {
            $this->invalid('source', 'Enter the HTTPS address of the official court listing.');
        }

        $entries = $this->entries($request->file('list_file')->getContent());
        $cnr = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $case->cnr_number));
        if (! in_array($cnr, array_column($entries, 'cnr'), true)) {
            $this->invalid('list_file', 'The selected case must appear in the CSV with its CNR.');
        }

        $recorder->ensureSource(self::PROVIDER, 'District court cause lists', 'https://services.ecourts.gov.in/');
        $date = $data['hearing_date'];
        $room = $data['room_label'];

        DB::transaction(function () use ($case, $data, $entries, $date, $room): void {
            User::query()->whereKey($case->user_id)->lockForUpdate()->firstOrFail();
            $cases = LegalCase::query()->where('user_id', $case->user_id)->where('court_level', 'district')
                ->where('state_code', $case->state_code)->where('district', $case->district)
                ->where(fn ($query) => $query->whereIn('cnr_number', array_filter(array_column($entries, 'cnr')))->orWhereKey($case->id))
                ->get()->groupBy(fn (LegalCase $matter): string => strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $matter->cnr_number)));
            $totals = collect($entries)->map(fn (array $entry): string => $entry['cnr'] ?: 'item:'.$entry['item'])->unique()->count();
            $document = CourtDocument::query()->updateOrCreate([
                'provider_key' => self::PROVIDER,
                'document_key' => sha1(implode('|', [$case->user_id, $case->state_code, $case->district, $date, strtolower($room)])),
            ], [
                'listing_date' => $date, 'court_scope' => 'District Court', 'list_type' => 'cause_list',
                'document_label' => $room, 'source_url' => $data['source'], 'fetched_at' => now(),
                'download_status' => 'done', 'extraction_status' => 'done',
                'metadata' => ['entries' => $totals],
            ]);
            $document->appearances()->delete();

            foreach ($entries as $entry) {
                foreach ($cases->get($entry['cnr'], []) as $matter) {
                    CauseListAppearance::query()->updateOrCreate([
                        'court_document_id' => $document->id,
                        'record_key' => sha1($matter->id.'|'.$entry['item']),
                    ], [
                        'legal_case_id' => $matter->id, 'item_number' => $entry['item'], 'court_room' => $room,
                        'case_reference' => $matter->case_number ?: $entry['cnr'],
                        'match_status' => 'matched', 'matched_at' => now(), 'metadata' => ['board_total' => $totals],
                    ]);
                }
            }
        }, 3);

        return back()->with('success', 'District list imported. Matching workspace cases now show the item and list total on that date.');
    }

    /** @return list<array{item: string, cnr: string}> */
    private function entries(string $contents): array
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $contents);
        rewind($stream);
        $entries = [];

        try {
            $header = fgetcsv($stream, escape: '');
            if (! is_array($header) || array_map(fn ($column) => strtolower(trim((string) $column, "\xEF\xBB\xBF \t")), $header) !== ['item', 'cnr']) {
                $this->invalid('list_file', 'Use a CSV whose first row is item,cnr.');
            }

            while (($row = fgetcsv($stream, escape: '')) !== false) {
                if ($row === [null]) {
                    continue;
                }
                [$item, $cnr] = array_map(fn ($value) => strtoupper(trim((string) $value)), array_pad($row, 2, ''));
                if (count($row) !== 2 || ! preg_match('/\A\d{1,4}(?:[.\/-]\d{1,3})?\z/', $item)
                    || ($cnr !== '' && ! preg_match('/\A[A-Z]{4}\d{12}\z/', $cnr)) || count($entries) >= 5000) {
                    $this->invalid('list_file', 'Each row needs an item number and, where known, a valid CNR. Include at most 5,000 rows.');
                }
                $entries[] = ['item' => $item, 'cnr' => $cnr];
            }
        } finally {
            fclose($stream);
        }

        return $entries;
    }

    private function invalid(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message])->errorBag('districtList');
    }
}
