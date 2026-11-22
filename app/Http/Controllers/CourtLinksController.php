<?php

namespace App\Http\Controllers;

use App\Services\CourtBoardEmbedCheck;
use Illuminate\View\View;

/**
 * Quick links to official court services: Supreme Court, Delhi High Court, Delhi District Courts,
 * eCourts and live display boards. Every link opens the court's own website; nothing is fetched
 * or copied into VakilNiti. Links were taken from the courts' own websites.
 */
class CourtLinksController extends Controller
{
    /**
     * @var list<array{title: string, description: string, url: string, icon: string}>
     */
    public const SUPREME_COURT = [
        ['title' => 'Court list', 'description' => 'Daily and advance cause lists: which matters are listed, in which court.', 'url' => 'https://www.sci.gov.in/cause-list/', 'icon' => 'list'],
        ['title' => 'Physical display board', 'description' => 'Live item numbers running in every courtroom.', 'url' => 'https://cdb.sci.gov.in/', 'icon' => 'board'],
        ['title' => 'Web display board', 'description' => 'The original all-courts display, item by item.', 'url' => 'https://wdb.sci.gov.in/display_original.php', 'icon' => 'screen'],
        ['title' => 'E-gate pass', 'description' => 'SuSwagatam: apply for and download your entry e-pass.', 'url' => 'https://suswagatam.sci.gov.in/', 'icon' => 'pass'],
        ['title' => 'Supreme Court calendar', 'description' => 'Sittings, non-sitting days, special benches and court notices.', 'url' => 'https://www.sci.gov.in/calendar/', 'icon' => 'calendar'],
    ];

    /**
     * @var list<array{title: string, description: string, url: string, icon: string}>
     */
    public const DELHI_HIGH_COURT = [
        ['title' => 'Physical display board', 'description' => 'Live item numbers for regular and registrar courts.', 'url' => 'https://delhihighcourt.nic.in/app/physical-display-board', 'icon' => 'board'],
        ['title' => 'Current roster', 'description' => "Judges' roster: which bench takes which matters.", 'url' => 'https://delhihighcourt.nic.in/web/judges-roster', 'icon' => 'people'],
        ['title' => 'Daily cause list', 'description' => 'Online and PDF cause lists, including advance and supplementary lists.', 'url' => 'https://delhihighcourt.nic.in/web/cause-lists', 'icon' => 'list'],
        ['title' => 'E-filing portal', 'description' => 'File cases and documents online.', 'url' => 'https://dhcefiling.nic.in/eFiling/', 'icon' => 'upload'],
        ['title' => 'E-gate pass', 'description' => 'Register for an e-Visitor pass to enter the court.', 'url' => 'https://delhihighcourt.nic.in/app/gatepass/register', 'icon' => 'pass'],
        ['title' => 'Case status', 'description' => 'Search by case type, number and year.', 'url' => 'https://delhihighcourt.nic.in/app/get-case-type-status', 'icon' => 'search'],
        ['title' => 'Case history', 'description' => 'Full listing history of a case.', 'url' => 'https://delhihighcourt.nic.in/app/get-case-wise', 'icon' => 'history'],
        ['title' => 'Orders', 'description' => 'Daily orders passed in a case.', 'url' => 'https://delhihighcourt.nic.in/app/', 'icon' => 'document'],
        ['title' => 'Judgements', 'description' => 'Search judgements by case number.', 'url' => 'https://delhihighcourt.nic.in/app/case-number', 'icon' => 'scale'],
        ['title' => 'Court calendar', 'description' => 'Working days and holidays of the High Court.', 'url' => 'https://delhihighcourt.nic.in/web/calender-list', 'icon' => 'calendar'],
    ];

    /**
     * Delhi's court complexes and the district courts that sit in each, with each district's official website.
     *
     * @var array<string, list<array{district: string, url: string}>>
     */
    public const DELHI_DISTRICT_COMPLEXES = [
        'Rohini' => [
            ['district' => 'North', 'url' => 'https://northdelhi.dcourts.gov.in/'],
            ['district' => 'North West', 'url' => 'https://rohini.dcourts.gov.in/'],
        ],
        'Karkardooma' => [
            ['district' => 'East', 'url' => 'https://eastdelhi.dcourts.gov.in/'],
            ['district' => 'North East', 'url' => 'https://northeast.dcourts.gov.in/'],
            ['district' => 'Shahdara', 'url' => 'https://shahdara.dcourts.gov.in/'],
        ],
        'Tis Hazari' => [
            ['district' => 'Central', 'url' => 'https://centraldelhi.dcourts.gov.in/'],
            ['district' => 'West', 'url' => 'https://westdelhi.dcourts.gov.in/'],
        ],
        'Dwarka' => [
            ['district' => 'South West', 'url' => 'https://southwestdelhi.dcourts.gov.in/'],
        ],
        'Saket' => [
            ['district' => 'South', 'url' => 'https://southdelhi.dcourts.gov.in/'],
            ['district' => 'South East', 'url' => 'https://southeastdelhi.dcourts.gov.in/'],
        ],
        'Patiala House' => [
            ['district' => 'New Delhi', 'url' => 'https://newdelhi.dcourts.gov.in/'],
        ],
        'Rouse Avenue' => [
            ['district' => 'Central (Rouse Avenue courts)', 'url' => 'https://centraldelhi.dcourts.gov.in/'],
        ],
    ];

    /**
     * Services shared by all Delhi district courts.
     *
     * @var array<string, string>
     */
    public const DELHI_DISTRICT_SERVICES = [
        'case_status' => 'https://delhidistrictcourts.nic.in/casestatus',
        'cause_list' => 'https://services.ecourts.gov.in/ecourtindia_v6/',
        'efiling' => 'https://filing.ecourts.gov.in/pdedev/',
        'virtual_court' => 'https://vcourts.gov.in/virtualcourt/index.php',
        'vc_links' => 'https://delhidistrictcourts.nic.in/lockdownfiling',
        'evening_court' => 'https://traffic.delhipolice.gov.in/evecourtddc/#/',
        'statistics' => 'https://njdg.ecourts.gov.in/njdgnew/?p=main/index&state_code=7~26',
        'website' => 'https://delhidistrictcourts.nic.in/',
    ];

    /**
     * National eCourts services for High Courts and District Courts.
     *
     * @var list<array{title: string, description: string, url: string, icon: string}>
     */
    public const ECOURTS_SERVICES = [
        ['title' => 'District court case status', 'description' => 'Case status, cause lists and orders of District and Taluka courts.', 'url' => 'https://services.ecourts.gov.in/ecourtindia_v6/', 'icon' => 'search'],
        ['title' => 'High Court case status', 'description' => 'Case status and cause lists of every High Court.', 'url' => 'https://hcservices.ecourts.gov.in/hcservices/main.php', 'icon' => 'building'],
        ['title' => 'Judgment search', 'description' => 'Free-text search of High Court judgments.', 'url' => 'https://judgments.ecourts.gov.in/', 'icon' => 'scale'],
        ['title' => 'ePay', 'description' => 'Pay court fees, fines and deposits online.', 'url' => 'https://pay.ecourts.gov.in/', 'icon' => 'wallet'],
        ['title' => 'eFiling', 'description' => 'File cases online in District courts and High Courts.', 'url' => 'https://efiling.ecourts.gov.in/', 'icon' => 'upload'],
        ['title' => 'NJDG', 'description' => 'National Judicial Data Grid: pending and disposed case statistics.', 'url' => 'https://njdg.ecourts.gov.in/', 'icon' => 'chart'],
        ['title' => 'Virtual courts', 'description' => 'Pay traffic challans and other virtual court matters.', 'url' => 'https://vcourts.gov.in/', 'icon' => 'screen'],
        ['title' => 'e-Committee', 'description' => 'The Supreme Court e-Committee: eCourts project news and guides.', 'url' => 'https://ecommitteesci.gov.in/', 'icon' => 'people'],
    ];

    /**
     * Official live display boards, by court. Only links published by the courts themselves.
     *
     * @var list<array{court: string, level: string, boards: list<array{title: string, url: string}>}>
     */
    public const DISPLAY_BOARDS = [
        [
            'court' => 'Supreme Court of India',
            'level' => 'Supreme Court',
            'boards' => [
                ['title' => 'Physical display board', 'url' => 'https://cdb.sci.gov.in/'],
                ['title' => 'Web display board', 'url' => 'https://wdb.sci.gov.in/display_original.php'],
            ],
        ],
        [
            'court' => 'High Court of Delhi',
            'level' => 'High Court',
            'boards' => [
                ['title' => 'Display board', 'url' => 'https://delhihighcourt.nic.in/app/display-board'],
                ['title' => 'Physical display board', 'url' => 'https://delhihighcourt.nic.in/app/physical-display-board'],
            ],
        ],
        [
            'court' => 'Allahabad High Court',
            'level' => 'High Court',
            'boards' => [
                ['title' => 'Court View display board', 'url' => 'https://courtview2.allahabadhighcourt.in/courtview/CourtViewAllahabad.do'],
            ],
        ],
    ];

    /**
     * Every display board as one flat list for the Home picker, with whether its court lets it be embedded.
     *
     * @return list<array{court: string, level: string, title: string, url: string, embeddable: bool}>
     */
    public static function boardOptions(): array
    {
        $check = app(CourtBoardEmbedCheck::class);
        $options = [];

        foreach (self::DISPLAY_BOARDS as $court) {
            foreach ($court['boards'] as $board) {
                $options[] = [
                    'court' => $court['court'],
                    'level' => $court['level'],
                    'title' => $board['title'],
                    'url' => $board['url'],
                    'embeddable' => $check->isEmbeddable($board['url']),
                ];
            }
        }

        return $options;
    }

    public function index(): View
    {
        return view('courts.index');
    }

    public function supremeCourt(): View
    {
        return view('courts.links', [
            'title' => 'Supreme Court of India',
            'heading' => 'Supreme Court',
            'intro' => 'Official Supreme Court services. Each opens the court\'s own website in a new tab.',
            'metaDescription' => 'Supreme Court of India quick links: cause list, live physical and web display boards, SuSwagatam e-gate pass and the court calendar.',
            'links' => self::SUPREME_COURT,
            'source' => 'sci.gov.in',
        ]);
    }

    public function delhiHighCourt(): View
    {
        return view('courts.links', [
            'title' => 'Delhi High Court',
            'heading' => 'Delhi High Court',
            'intro' => 'Official High Court of Delhi services. Each opens the court\'s own website in a new tab.',
            'metaDescription' => 'Delhi High Court quick links: physical display board, judges roster, daily cause list, e-filing, e-visitor pass, case status, orders, judgements and calendar.',
            'links' => self::DELHI_HIGH_COURT,
            'source' => 'delhihighcourt.nic.in',
        ]);
    }

    public function ecourtsServices(): View
    {
        return view('courts.links', [
            'title' => 'eCourts services',
            'heading' => 'eCourts services',
            'intro' => 'National eCourts services for High Courts and District Courts. Each opens the official website in a new tab.',
            'metaDescription' => 'eCourts services in one place: District and High Court case status, judgment search, ePay, eFiling, NJDG and virtual courts.',
            'links' => self::ECOURTS_SERVICES,
            'source' => 'ecourts.gov.in',
            'showCnrSearch' => true,
        ]);
    }

    public function delhiDistrictCourts(): View
    {
        return view('courts.delhi-district-courts', [
            'complexes' => self::DELHI_DISTRICT_COMPLEXES,
            'services' => self::DELHI_DISTRICT_SERVICES,
        ]);
    }

    public function displayBoards(): View
    {
        return view('courts.display-boards', ['courts' => self::DISPLAY_BOARDS]);
    }
}
