<?php

namespace Tests\Unit\Services;

use App\Services\EcourtsAppExportParser;
use PHPUnit\Framework\TestCase;

class EcourtsAppExportParserTest extends TestCase
{
    /**
     * Builds a file the way the eCourts Services app writes it: a JSON string of a JSON list of JSON strings.
     *
     * @param  list<array<string, mixed>>  $records
     */
    public static function exportFile(array $records): string
    {
        return json_encode(json_encode(array_map(fn (array $record): string => json_encode($record), $records)));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function districtRecord(array $overrides = []): array
    {
        return array_merge([
            'cino' => 'DLWT010000332007',
            'type_name' => 'Civ DJ',
            'fil_type_name' => 'Civ DJ',
            'reg_year' => 2016,
            'reg_no' => 608651,
            'petparty_name' => 'ASHA DEVI',
            'resparty_name' => 'RAVI KUMAR',
            'fil_year' => 2007,
            'fil_no' => 43777,
            'establishment_name' => 'District and Sessions Judge, West, THC',
            'state_name' => 'Delhi',
            'district_name' => 'West',
            'date_next_list' => '2027-01-12',
            'date_of_decision' => null,
            'date_last_list' => '2026-10-06',
            'court_no_desg_name' => 'DISTRICT JUDGE - 04',
            'purpose_name' => 'Misc. Arguments',
            'ltype_name' => 'हिंदी',
        ], $overrides);
    }

    public function test_reads_a_district_court_case_from_the_triple_encoded_file(): void
    {
        $cases = (new EcourtsAppExportParser)->parse(self::exportFile([self::districtRecord()]));

        $this->assertCount(1, $cases);
        $case = $cases[0];
        $this->assertSame('DLWT010000332007', $case['cnr']);
        $this->assertFalse($case['is_high_court']);
        $this->assertSame('Civ DJ/608651/2016', $case['case_number']);
        $this->assertSame('ASHA DEVI vs RAVI KUMAR', $case['case_title']);
        $this->assertSame('DISTRICT JUDGE - 04', $case['court_name']);
        $this->assertSame('District and Sessions Judge, West, THC, Delhi', $case['court_complex']);
        $this->assertSame('43777/2007', $case['filing_number']);
        $this->assertSame('pending', $case['case_status']);
        $this->assertSame('Misc. Arguments', $case['stage']);
        $this->assertSame('2026-10-06', $case['last_date']);
        $this->assertSame('2027-01-12', $case['next_date']);
    }

    public function test_disposed_cases_get_a_status_and_decision_date_but_no_next_date(): void
    {
        $cases = (new EcourtsAppExportParser)->parse(self::exportFile([
            self::districtRecord(['cino' => 'DLCT120000552022', 'disp_name' => 'QUASHED', 'date_of_decision' => '2024-01-04', 'date_next_list' => '2024-01-04']),
            self::districtRecord(['cino' => 'UPJL040011602010', 'disp_name' => 'TRANSFER', 'date_of_decision' => '2015-07-06']),
            self::districtRecord(['cino' => 'DLCT030057392026', 'disp_name' => 'WITHDRAW', 'date_of_decision' => '2026-10-16']),
        ]));

        $this->assertSame(['disposed', 'transferred', 'withdrawn'], array_column($cases, 'case_status'));
        $this->assertSame('2024-01-04', $cases[0]['disposed_on']);
        $this->assertNull($cases[0]['next_date']);
        $this->assertSame('QUASHED', $cases[0]['disposal']);
    }

    public function test_high_court_rows_use_the_court_name_and_skip_placeholder_values(): void
    {
        $cases = (new EcourtsAppExportParser)->parse(self::exportFile([
            ['cino' => 'UPHC011965212016', 'type_name' => 'WRIC', 'reg_no' => 742, 'reg_year' => 2016, 'petparty_name' => 'A', 'resparty_name' => 'STATE OF U.P.',
                'establishment_name' => 'High Court cisdb_16012018', 'district_name' => 'Allahabad High Court', 'court_no_desg_name' => "HON'BLE JUSTICE",
                'date_next_list' => '2027-02-05', 'date_last_list' => '2027-02-05'],
            ['cino' => 'DLHC010170852024', 'type_name' => 'O.M.P.(I) (COMM.)', 'reg_no' => 99, 'reg_year' => 2024, 'petparty_name' => 'B', 'resparty_name' => 'C',
                'establishment_name' => 'Principal Bench at Delhi', 'district_name' => 'High Court of Delhi', 'court_no_desg_name' => 'CASE IS NOT ALLOCATED TO COURT.'],
        ]));

        $this->assertTrue($cases[0]['is_high_court']);
        $this->assertSame('Allahabad High Court', $cases[0]['court_name']);
        $this->assertNull($cases[0]['court_complex']);
        $this->assertNull($cases[0]['judge_name']);
        $this->assertSame('High Court of Delhi', $cases[1]['court_name']);
        $this->assertSame('Principal Bench at Delhi', $cases[1]['court_complex']);
        $this->assertSame('O.M.P.(I) (COMM.)/99/2024', $cases[1]['case_number']);
    }

    public function test_also_reads_a_plain_json_list_and_drops_duplicate_or_broken_rows(): void
    {
        $file = json_encode([json_encode(self::districtRecord()), json_encode(self::districtRecord()), json_encode(['cino' => 'bad']), 'not json']);

        $this->assertCount(1, (new EcourtsAppExportParser)->parse($file));
    }

    public function test_rejects_files_that_are_not_an_export(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new EcourtsAppExportParser)->parse('Hello, this is a note.');
    }
}
