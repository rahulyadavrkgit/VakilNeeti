<?php

namespace Tests\Unit\Services;

use App\Services\EcourtsResultParser;
use PHPUnit\Framework\TestCase;

class EcourtsResultParserTest extends TestCase
{
    public function test_reads_a_tab_separated_results_table_with_court_headings(): void
    {
        $text = "Sr No\tCase Type/Case Number/Case Year\tPetitioner Name versus Respondent Name\n"
            ."Civil Judge Senior Division, Lucknow\n"
            ."1\tO.S./0123/2019\tRam Kumar Vs Shyam Lal\tView\n"
            ."2\tCRL. MISC. CASE/45/2021\tState of U.P. versus Mohan\tView\n"
            ."District and Sessions Court, Kanpur\n"
            ."1\tS.T./7/2020\tState v/s Ravi\tView\n";

        $cases = (new EcourtsResultParser)->parse($text, 'Fallback Court');

        $this->assertSame(['O.S./123/2019', 'CRL. MISC. CASE/45/2021', 'S.T./7/2020'], array_column($cases, 'full_number'));
        $this->assertSame('Ram Kumar vs Shyam Lal', $cases[0]['title']);
        $this->assertSame('O.S.', $cases[0]['case_type']);
        $this->assertSame('Civil Judge Senior Division, Lucknow', $cases[1]['court']);
        $this->assertSame('State of U.P.', $cases[1]['petitioner']);
        $this->assertSame('District and Sessions Court, Kanpur', $cases[2]['court']);
        $this->assertSame('Ravi', $cases[2]['respondent']);
    }

    public function test_reads_cells_pasted_one_per_line_and_picks_up_cnr_numbers(): void
    {
        $text = "1\nWP/1234/2022\nAsha Devi\nvs\nUnion of India\n2\nWP/99/2023 UPHC010012342023\nRavi vs State\n";

        $cases = (new EcourtsResultParser)->parse($text, 'Allahabad High Court');

        $this->assertCount(2, $cases);
        $this->assertSame('WP/1234/2022', $cases[0]['title'], 'parties split over three lines are not guessed');
        $this->assertSame('Allahabad High Court', $cases[0]['court']);
        $this->assertSame('UPHC010012342023', $cases[1]['cnr']);
        $this->assertSame('Ravi vs State', $cases[1]['title']);
    }

    public function test_ignores_text_without_case_numbers_and_removes_duplicates(): void
    {
        $parser = new EcourtsResultParser;

        $this->assertSame([], $parser->parse("Case Status\nEnter Captcha\nNo records found", null));

        $cases = $parser->parse("1\tCS/12/2019\tA vs B\n2\tCS/12/2019\tA vs B", 'Court');
        $this->assertCount(1, $cases);
    }

    public function test_does_not_mistake_a_serial_number_or_dates_for_a_case_number(): void
    {
        $cases = (new EcourtsResultParser)->parse('3 MACP/88/2018 Sita vs Oriental Insurance listed 12/03/2024', 'MACT Lucknow');

        $this->assertSame('MACP/88/2018', $cases[0]['full_number']);
        $this->assertCount(1, $cases);
    }
}
