<?php

namespace Database\Seeders;

use App\Models\CourtBench;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Court rooms, benches and VC links for the in-site court lists.
 *
 * Supreme Court: VC links as published for each court. Delhi High Court: benches, judges and
 * matters from the Roster of Sitting effective 07.10.2026 as amended on 09.10.2026 and
 * 19.11.2026; court numbers as listed with the roster. Delhi district rows: as supplied.
 * Re-running replaces the lists of these courts; later changes can also be loaded with
 * `php artisan courts:import-benches`.
 */
class CourtBenchSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [...$this->supremeCourt(), ...$this->delhiHighCourt(), ...$this->delhiDistricts()];

        DB::transaction(function () use ($rows) {
            CourtBench::query()->whereIn('court_key', array_unique(array_column($rows, 'court_key')))->delete();

            foreach ($rows as $order => $row) {
                CourtBench::create($row + ['sort_order' => $order]);
            }
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function supremeCourt(): array
    {
        $rows = [];

        foreach (range(1, 17) as $court) {
            $rows[] = [
                'court_key' => 'supreme-court',
                'section' => 'Courts',
                'court_room' => 'Court No. '.$court,
                'vc_link' => sprintf('https://sci-vc.webex.com/meet/court%02d', $court),
            ];
        }

        foreach ([1, 2] as $court) {
            $rows[] = [
                'court_key' => 'supreme-court',
                'section' => 'Registrar courts',
                'court_room' => sprintf('R-%02d', $court),
                'bench' => sprintf('Registrar Court %02d', $court),
                'vc_link' => sprintf('https://sci-vc.webex.com/meet/registrarcourt%02d', $court),
            ];
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function delhiHighCourt(): array
    {
        $commercialAppellate = 'Matters to be heard by Commercial Appellate Division.';
        $regular = 'Regular hearing matters of the above categories.';
        $regularAforesaid = 'Regular hearing matters of the aforesaid categories.';
        $regularCategory = 'Regular hearing matters of the above category.';
        $judge = fn (string $name, ?string $designation = null): array => ['name' => $name, 'designation' => $designation];
        $bench = fn (string $section, string $court, ?string $label, array $judges, array $roster, ?string $location = null): array => [
            'court_key' => 'delhi-high-court',
            'section' => $section,
            'court_room' => $court,
            'bench' => $label,
            'location' => $location,
            'judges' => $judges,
            'roster' => $roster,
        ];

        $division = 'Division Bench';
        $civil = 'Single Bench — Civil';
        $criminal = 'Single Bench — Criminal';
        $originalSide = 'Original Side';
        $ip = 'IP Division';
        $registrar = 'Joint Registrar';

        $bailCriminal = 'Bail Matters.';
        $sexualHarassment = 'Cases relating to sexual harassment.';

        return [
            // Division Benches
            $bench($division, '01', 'DB-I', [$judge("Hon'ble Mr. Justice Nitin Wasudeo Sambre", 'Acting Chief Justice'), $judge("Hon'ble Mr. Justice Sanjay Sharma")], [
                'All PIL and Suo Moto Writ Petitions/matters.',
                'Writ Petitions challenging the constitutional validity of any Act, Statutory Rule, Regulation or Notification other than tax of the years 2021, 2022, 2023, 2024, 2025 & 2026.',
                'Writ Petitions challenging Constitutional validity of any Act, Statutory Rule, Regulation or Notification impacting Criminal Investigations, Trials, Prosecutions etc.',
                'Writ Petitions (Tender).',
                'Letters Patent Appeals (other than service matters) of the years 2024, 2025 & 2026.',
                'Misc. Writ Petitions invoking constitutional provisions and Writ Petitions other than the specified categories.',
                $commercialAppellate, $regular,
            ]),
            $bench($division, '37', 'DB-II', [$judge("Hon'ble Mr. Justice Dinesh Mehta"), $judge("Hon'ble Dr. Justice Aditi Choudhary")], [
                'Appeals under the Chartered Accountants Act.',
                'Writ Petitions challenging constitutional validity of any Act, Statutory Rule, Regulation/Notification pertaining to Direct Tax.',
                'Income Tax References, Wealth Tax and Gift Tax cases.',
                'Writ Petition (Tax).',
                $commercialAppellate, $regular,
            ]),
            $bench($division, '38', 'DB-III', [$judge("Hon'ble Mr. Justice Vivek Chaudhary"), $judge("Hon'ble Ms. Justice Nisha Sahay Saxena")], [
                'Letters Patent Appeals (Service) of the years 2018 to 2026.',
                'Regular First Appeals (Original Side).',
                'Execution First Appeals (Original Side).',
                'First Appeals from Orders (Original Side).',
                'Regular First Appeals from orders of Copyright Board.',
                'RERA Appeals.',
                'IPR Appellate Division to hear all categories of matters relating to IPR.',
                'Writ Petitions relating to MTNL, MCD & NDMC.',
                $commercialAppellate, $regular,
            ]),
            $bench($division, '33', 'DB-IV', [$judge("Hon'ble Ms. Justice Prathiba M. Singh"), $judge("Hon'ble Mr. Justice Dinesh Bhatt")], [
                'Writ Petitions challenging constitutional validity of any Act, Statutory Rule, Regulation/Notification other than tax upto the year 2020.',
                'Writ Petitions (Land Acquisition).',
                'Petitions (Co-Operative Societies).',
                'Matters relating to street vendors/Tehbazari.',
                'Contempt Appeals.',
                'Criminal Appeals from the years 2012 to 2020 and of the years 2025 & 2026.',
                $commercialAppellate, $regular,
            ]),
            $bench($division, '34', 'DB-V', [$judge("Hon'ble Mr. Justice Navin Chawla"), $judge("Hon'ble Mr. Justice Arun Bhardwaj")], [
                'Writ Petitions challenging Constitutional validity of any Act, Statutory Rule, Regulation/Notification impacting Criminal Investigations, Trials & Prosecutions etc.',
                'Criminal Appeals upto the years 2011 and of the years 2021 to 2024.',
                'Death Sentence References.',
                'Criminal Leave Petitions.',
                'Criminal Contempt Petitions.',
                'Criminal Contempt References.',
                'Criminal Writ Petitions including those relating to Habeas Corpus and Preventive Detention.',
                'Misc. Appeals (PMLA).',
                'Misc. Appeals (FEMA).',
                $regular,
            ]),
            $bench($division, '41', 'DB-VI', [$judge("Hon'ble Mr. Justice C. Hari Shankar"), $judge("Hon'ble Ms. Justice Nivedita Anil Sharma")], [
                'Writ Petitions (Service) arising out of the orders of CAT.',
                'Writ petitions pertaining to Service Matters required to be listed before the Division Bench.',
                'Writ Petitions challenging Constitutional validity of any Act, Statutory Rule, Regulation/Notification pertaining to Service relating to Armed Forces from the year 2020 onwards.',
                'Letters Patent Appeals (Service) upto the year 2017 and (other than service) matters upto the year 2023.',
                $commercialAppellate, $regular,
            ]),
            $bench($division, '02', 'DB-VII', [$judge("Hon'ble Mr. Justice Avneesh Jhingan"), $judge("Hon'ble Ms. Justice Shail Jain")], [
                'Writ Petitions pertaining to Service Tax, Central Excise Act, Customs Act and Value Added Tax (VAT) cases.',
                'Service Tax cases, Central Excise Act cases, Customs Act cases and VAT Appeals.',
                'Writ Petitions challenging orders passed by the High Court on the Administrative Side.',
                'Writ Petitions challenging Constitutional validity of any Act, Statutory Rule, Regulation/Notification pertaining to Service.',
                $commercialAppellate, $regular,
            ]),
            $bench($division, '04', 'DB-VIII', [$judge("Hon'ble Mr. Justice Subramonium Prasad"), $judge("Hon'ble Ms. Justice Renu Bhatnagar")], [
                'Appeals against the orders of the Family Courts.',
                'Writ Petitions (AAIFR, BIFR & Lokayukta).',
                'Writ Petitions (DRT & DRAT – Misc. Petitions including petitions under Article 227 of the Constitution of India).',
                'Income Tax Appeals.',
                $commercialAppellate, $regular,
            ]),
            $bench($division, '03', 'DB-IX', [$judge("Hon'ble Ms. Justice Jyoti Singh"), $judge("Hon'ble Mr. Justice Gurvinder Pal Singh")], [
                'Writ Petitions (Service) relating to the Armed Forces.',
                'Writ Petition challenging Constitutional validity of any Act, Statutory Rule, Regulation/Notification pertaining to Service relating to Armed Forces upto the year 2019.',
                'Company Appeals.',
                $commercialAppellate, $regular,
            ]),
            $bench($division, '06', 'DB-X', [$judge("Hon'ble Mr. Justice Prateek Jalan"), $judge("Hon'ble Mr. Justice Bharat Parashar")], [
                'Writ Petitions challenging the Constitutional Validity of any Act, Statutory Rule, Regulation/Notification pertaining to Indirect Tax.',
                'Writ Petitions challenging Constitutional validity of any Act, Statutory Rule, Regulation/Notification pertaining to Municipal Tax.',
                'Sales Tax cases and GST cases + ST Ref. Cases.',
                'Writ Petitions pertaining to Sales Tax and GST.',
                $commercialAppellate, $regular,
            ]),

            // Single Benches (Civil Jurisdiction)
            $bench($civil, '08', null, [$judge("Hon'ble Mr. Justice Manoj Kumar Ohri")], [
                'Civil Writ Petitions relating to Land Reforms including matters relating to allotment of alternative land.',
                'Civil Writ Petitions (Service) of the years 2016 onwards.',
                $regular,
            ]),
            $bench($civil, '51', null, [$judge("Hon'ble Mr. Justice Tejas Karia")], [
                'Civil Writ Petitions (Labour) of the years 2018 to 2023.',
                'First Appeals from Orders (other than MV Act).',
                $regular,
            ]),
            $bench($civil, '10', null, [$judge("Hon'ble Mr. Justice Jasmeet Singh")], [
                'Civil Writ Petitions (DDA).',
                'Civil Writ Petitions relating to Nationalized Banks and Financial Institutions of the years 2024, 2025 & 2026.',
                'Civil Writ Petitions (Education).',
                $regular,
            ]),
            $bench($civil, '15', null, [$judge("Hon'ble Ms. Justice Neena Bansal Krishna")], [
                'Civil Writ Petitions Misc. including those involving statutory authorities, DTC, Urban Arts Commission, Airport Authority of India etc. upto the year 2020.',
                'Civil Writ Petitions (Labour) from the year 2024 onwards.',
                'Civil Contempt Petitions of the years 2022 onwards.',
                $regular,
            ]),
            $bench($civil, '14', null, [$judge("Hon'ble Mr. Justice Amit Mahajan")], [
                'Civil Writ Petitions Misc. including those involving statutory authorities, DTC, Urban Arts Commission, Airport Authority of India etc. from year 2021 onwards.',
                'Civil Writ Petitions (RTI).',
                'Civil Writ Petitions (S.H. at Work place).',
                'Civil Writ Petitions (Mines).',
                $regular,
            ]),
            $bench($civil, '13', null, [$judge("Hon'ble Mr. Justice Saurabh Banerjee")], [
                'Civil Writ Petitions (Railways, Cantonment Board, Electricity, DJB, and MTNL).',
                'Civil Writ Petitions (MCD).',
                'Civil Writ Petitions (NDMC).',
                'Civil Writ Petitions (STA).',
                'Civil Writ Petitions (Election).',
                'Regular First Appeals of the years 2015 to 2019.',
                $regular,
            ]),
            $bench($civil, '16', null, [$judge("Hon'ble Mr. Justice Anish Dayal")], [
                'Civil Revision petitions.',
                'Civil Misc. Main (MV Act).',
                'MACT Appeals.',
                'Civil Writ Petitions (Waqf Board).',
                'All Company Matters.',
                $regular,
            ]),
            $bench($civil, '21', null, [$judge("Hon'ble Mr. Justice Amit Sharma")], [
                'Civil Writ Petitions (Labour) upto the year 2017.',
                'Land Acquisition Appeals.',
                $regular,
            ]),
            $bench($civil, '24', null, [$judge("Hon'ble Mr. Justice Ajay Digpaul")], [
                'Civil Misc. Main (other than MV Act and PP Act).',
                'Civil Writ Petitions relating to Nationalized Banks and Financial Institutions upto the year 2023.',
                $regular,
            ]),
            $bench($civil, '22', null, [$judge("Hon'ble Mr. Justice Harish Vaidyanathan Shankar")], [
                'Rent Control Revisions.',
                'Transfer Petitions (Civil).',
                'Regular First Appeals upto the year 2014.',
                'Civil Contempt Petitions upto the year 2021.',
                'All categories of cases under the P.P. Act.',
                'First Appeals from Orders (MACT).',
                'All categories of Matrimonial Cases.',
                'C.M.I.',
                'Original Reference.',
                'Execution First Appeals.',
                'Execution Second Appeals.',
                $regular,
            ]),
            $bench($civil, '23', null, [$judge("Hon'ble Mr. Justice Rajneesh Kumar Gupta")], [
                'Regular First Appeals of 2020 to 2025 and 2026.',
                'Civil Writ Petitions (Service) upto the year 2015.',
                'Regular Second Appeals.',
                $regular,
            ]),

            // Single Benches (Criminal Jurisdiction), as amended w.e.f. 09.10.2026
            $bench($criminal, '35', null, [$judge("Hon'ble Mr. Justice Sanjeev Narula", 'Judge-in-Charge (Criminal)')], [
                $bailCriminal,
                'Writ Petitions (Crl.) of the years 2023 & 2026.',
                'Criminal Appeals of the year 2026.',
                'Criminal Misc. Main cases of the years 2016, 2023 & 2026.',
                'Criminal Revision Petition of the years 2020 & 2026.',
                'Criminal Leave Petitions (except u/s 138 of the Negotiable Instruments Act, 1881) of the years 2024 & 2026.',
                'Transfer Petitions (Criminal).',
                $sexualHarassment, $regular,
            ]),
            $bench($criminal, '29', null, [$judge("Hon'ble Mr. Justice Amit Bansal")], [
                $bailCriminal,
                'Criminal Appeal of the year 2026.',
                'Criminal Revision Petition upto the year 2011 & 2026.',
                'Criminal Leave Petitions (except u/s 138 of the Negotiable Instruments Act, 1881) of the years 2017, 2019, 2021 & 2026.',
                'Criminal Misc. Main cases of 2017, 2018, 2021 & 2026.',
                'Writ Petitions (Crl.) of 2016 to 2021, 2024, 2025 & 2026.',
                $sexualHarassment, $regular,
            ]),
            $bench($criminal, '30', null, [$judge("Hon'ble Ms. Justice Mini Pushkarna")], [
                $bailCriminal,
                'Criminal Appeals of the year 2026.',
                'Criminal Revision Petitions of the years 2012, 2013, 2017, 2018, 2019 & 2026.',
                'Criminal Leave Petitions (except u/s 138 Negotiable Instruments Act) upto the years 2014, 2016 & 2026.',
                'Criminal Misc. Main cases of the years 2022 & 2026.',
                'Writ Petitions (Crl.) upto the year 2015, 2022 and 2026.',
                $sexualHarassment, $regular,
            ]),
            $bench($criminal, '31', null, [$judge("Hon'ble Ms. Justice Manmeet Pritam Singh Arora")], [
                $bailCriminal,
                'Writ Petitions (Crl.) of the year 2026.',
                'Criminal Appeals of the year 2026.',
                'Criminal Misc. Main cases of the years 2011, 2012, 2014, 2015 and 2025 (excluding registered during January to April, 2025) & 2026.',
                'Criminal Revision Petitions of the years 2021, 2024 & 2026.',
                'Criminal Leave Petitions (except u/s 138 of the Negotiable Instruments Act, 1881) of the years 2023 & 2026.',
                $sexualHarassment, $regular,
            ]),
            $bench($criminal, '25', null, [$judge("Hon'ble Mr. Justice Sachin Datta")], [
                $bailCriminal,
                'Criminal Appeals of the year 2026.',
                'All Criminal Misc. cases registered during the months of March & April, 2025.',
                'Criminal Misc. Main cases of the year 2026.',
                'Criminal Revision Petitions of the years 2023, 2025 & 2026.',
                'Writ Petitions (Crl.) of the year 2026.',
                'Criminal Leave Petitions (except u/s 138 of the Negotiable Instruments Act, 1881) of the year 2026.',
                'Criminal cases relating to sitting/former MPs/MLAs.',
                $sexualHarassment, $regular,
            ]),
            $bench($criminal, '32', null, [$judge("Hon'ble Mr. Justice Girish Kathpalia")], [
                $bailCriminal,
                'Criminal Appeals of year 2026.',
                'Criminal Misc. Main cases upto the year 2010, 2013 and of the years 2019, 2020 & 2026.',
                'All Criminal Misc. cases registered during the months of January & February, 2025.',
                'Criminal Revision Petitions of the years 2014, 2015, 2016 & 2026.',
                'Writ Petitions (Crl.) of the year 2026.',
                'Criminal Leave Petitions (except u/s 138 of the Negotiable Instruments Act, 1881) of the years 2018, 2020, 2022, 2025 & 2026.',
                $sexualHarassment, $regular,
            ]),
            $bench($criminal, '28', null, [$judge("Hon'ble Mr. Justice Ravinder Dudeja")], [
                'All Criminal Appeals upto the year 2015 (excluding those filed u/s 138 of NI Act) and except Appeals relating to FERA of the years 2007 & 2008 and relating to Prevention of Corruption Act of the years 2000 to 2006.',
                'Criminal Appeals of the year 2022 (excluding those filed u/s 138 of NI Act).',
                $sexualHarassment, $regular,
            ]),
            $bench($criminal, '27', null, [$judge("Hon'ble Ms. Justice Madhu Jain")], [
                'All criminal appeals from the year 2016 upto 2021, 2023, 2024 & 2025.',
                'All the Crl. Appeals, including of the year 2026, filed u/s 138 of the Negotiable Instruments Act, 1881, and all the Crl. Leave Petitions filed u/s 138 of the Negotiable Instruments Act, 1881.',
                'Appeals relating to FERA of the years 2007 and 2008.',
                'Appeals relating to Prevention of Corruption Act of the years 2000 to 2006.',
                $sexualHarassment, $regular,
            ]),
            $bench($criminal, '26', null, [$judge("Hon'ble Mr. Justice Vimal Kumar Yadav")], [
                $bailCriminal,
                'Writ Petitions (Crl.) of the year 2026.',
                'Criminal Appeals of the year 2026.',
                'Criminal Revision Petitions of the years 2022 & 2026.',
                'Criminal Misc. Main cases of the years 2024 & 2026.',
                'Criminal Leave Petitions (except u/s 138 of the Negotiable Instruments Act, 1881) of the years 2015 & 2026.',
                $sexualHarassment, $regular,
            ]),

            // Original Jurisdiction (Civil)
            $bench($originalSide, '48', "'F' Court", [$judge("Hon'ble Mr. Justice Anup Jairam Bhambhani", 'Judge-in-Charge (Original Side)')], [
                'Matters to be heard by the Commercial Division.',
                'Original Side Matters (including Finals) of the years 2024, 2025 & 2026.',
                'Matters under the Arbitration Act, 1940 and Arbitration & Conciliation Act, 1996 (including Finals) of the years 2024, 2025 & 2026.',
                'Execution Petitions under Arbitration Act, 1940 and Arbitration & Conciliation Act, 1996 of the years 2024, 2025 & 2026.',
                'Execution Petitions arising from Original Side Matters.',
                $regularAforesaid,
            ], 'Suit & Arbitration Matters'),
            $bench($originalSide, '43', "'C' Court", [$judge("Hon'ble Dr. Justice Swarana Kanta Sharma")], [
                'Matters to be heard by the Commercial Division.',
                'Original Side Matters (including Finals) upto the year 2010 and of the years 2011, 2012, 2013, 2014, 2015, 2018, 2020, 2021 & 2026.',
                'Execution Petitions arising from Original Side Matters.',
                'Matters under Arbitration Act, 1940 and Arbitration & Conciliation Act, 1996 (including Finals) of the years 2018, 2024 & 2026.',
                'Execution Petitions arising under Arbitration Act, 1940 and Arbitration & Conciliation Act, 1996 upto the years 2018 & 2026.',
                $regularAforesaid,
            ], 'Suit & Arbitration Matters'),
            $bench($originalSide, '47', "'G' Court", [$judge("Hon'ble Mr. Justice Tushar Rao Gedela")], [
                'Matters to be heard by the Commercial Division.',
                'Original Side Matters (including Finals) of the years 2023, 2025 & 2026.',
                'Execution Petitions arising from Original Side Matters.',
                'Matters under Arbitration Act, 1940 and Arbitration & Conciliation Act, 1996 (including Finals) of the years 2025 & 2026.',
                'Execution Petitions arising under the Arbitration Act, 1940 and Arbitration & Conciliation Act, 1996 of the years 2025 & 2026.',
                $regularAforesaid,
            ], 'Suit & Arbitration Matters'),
            $bench($originalSide, '45', "'A' Court", [$judge("Hon'ble Mr. Justice Om Prakash Shukla")], [
                'Matters to be heard by the Commercial Division.',
                'Matters under the Arbitration Act, 1940 and Arbitration & Conciliation Act, 1996 (including Finals) upto the year 2017 and of the years 2020, 2021, 2022, 2024, 2025 & 2026.',
                'Execution Petitions under the Arbitration Act, 1940 and Arbitration & Conciliation Act, 1996 of the years 2019, 2020, 2021, 2022, 2024, 2025 & 2026.',
                $regularAforesaid,
            ], 'Arbitration Matters'),
            $bench($originalSide, '42', "'B' Court", [$judge("Hon'ble Mr. Justice Manoj Jain")], [
                'Matters to be heard by the Commercial Division.',
                'Original Side Matters (including Finals) of the years 2024, 2025 & 2026.',
                'Execution Petitions arising from Original Side Matters.',
                'Matters under Arbitration Act, 1940 and Arbitration & Conciliation Act, 1996 (including Finals) of the years 2025 & 2026.',
                'Execution Petitions arising under Arbitration Act, 1940 and Arbitration & Conciliation Act, 1996 of the years 2024, 2025 & 2026.',
                $regularAforesaid,
            ], 'Suit & Arbitration Matters'),
            $bench($originalSide, '46', "'D' Court", [$judge("Hon'ble Mr. Justice Vinod Kumar")], [
                'Matters to be heard by the Commercial Division.',
                'Original Side Matters (including Finals) of the years 2016, 2017, 2019, 2022, 2024 & 2026.',
                'Execution Petitions arising from Original Side Matters.',
                'Matters under Arbitration Act, 1940 and Arbitration & Conciliation Act, 1996 (including Finals) of the years 2019, 2023, 2024 & 2026.',
                'Execution Petitions arising under Arbitration Act, 1940 and Arbitration & Conciliation Act, 1996 of the years 2023, 2024 & 2026.',
                $regularAforesaid,
            ], 'Suit & Arbitration Matters'),

            // IP Division, as amended w.e.f. 19.11.2026
            $bench($ip, '49', "'E' Court", [$judge("Hon'ble Mr. Justice Purushaindra Kumar Kaurav")], [
                'Matters to be heard by the Commercial Division relating to IPR disputes.',
                'Matters relating to Intellectual Property Rights.',
                'IPR Suits of the years 2021, 2022, 2024 & 2026.',
                $regularCategory,
            ]),
            $bench($ip, '50', "'J' Court", [$judge("Hon'ble Mr. Justice Vikas Mahajan")], [
                'Matters to be heard by the Commercial Division relating to IPR disputes.',
                'Matters relating to Intellectual Property Rights.',
                'IPR Suits upto the years 2020, 2023, 2025 & 2026.',
                $regularCategory,
            ]),

            // Joint Registrars (Judicial)
            $bench($registrar, 'R-1', "'C' Court", [$judge('Mr. Arun Goel', 'Joint Registrar (Judicial)')], [], 'New Courts Block, Ground Floor (OS-C)'),
            $bench($registrar, 'R-2', "'I' Court", [$judge('Ms. Swati Katiyar', 'Joint Registrar (Judicial)')], [], 'New Courts Block, Ground Floor (OS-I)'),
            $bench($registrar, 'R-3', "'A' Court", [$judge('Ms. Kadambri Awasthi', 'Joint Registrar (Judicial)')], [], 'New Courts Block, Ground Floor (OS-A)'),
            $bench($registrar, 'R-4', "'E' Court", [$judge('Ms. Ekta Gauba Mann', 'Joint Registrar (Judicial)')], [], 'New Courts Block, Ground Floor (OS-E)'),
            $bench($registrar, 'R-5', null, [$judge('Mr. Sandeep Gupta', 'Joint Registrar (Judicial)')], [], 'New Courts Block, Ground Floor · sits after the Appellate Side list'),
            $bench($registrar, 'R-6', "'D' Court", [$judge('Mr. Amit Sahrawat', 'Joint Registrar (Judicial)')], [], 'New Courts Block, Ground Floor (OS-D)'),
            $bench($registrar, 'R-7', "'G' Court", [$judge('Mr. Ashok Kumar', 'Joint Registrar (Judicial)')], [], 'New Courts Block, Ground Floor (OS-G)'),
            $bench($registrar, 'R-8', "'F' Court", [$judge('Mr. Gopal Singh Chauhan', 'Joint Registrar (Judicial)')], [], 'New Courts Block, Ground Floor (OS-F)'),
            $bench($registrar, 'R-9', "'B' Court", [$judge('Mr. Kishor Kumar', 'Joint Registrar (Judicial)')], [], 'New Courts Block, Ground Floor (OS-B)'),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function delhiDistricts(): array
    {
        return [
            [
                'court_key' => 'delhi-district-west',
                'court_room' => 'Room 201',
                'judges' => [['name' => 'Sh. Pitamber Dutt', 'designation' => 'Principal District & Sessions Judge']],
                'vc_link' => 'https://districtcourtdelhi.webex.com/meet/readerdjwest',
                'vc_meeting_id' => '176 228 1360',
                'email' => 'pdsj-west@ddc.nic.in',
            ],
            [
                'court_key' => 'delhi-district-west',
                'court_room' => 'Room 201 Ex Bl',
                'judges' => [['name' => 'Ms. Manjusha Wadhwa', 'designation' => 'District Judge (Commercial Court)']],
                'vc_meeting_id' => '176 440 9487',
                'email' => 'djcomm01-west@ddc.nic.in',
            ],
        ];
    }
}
