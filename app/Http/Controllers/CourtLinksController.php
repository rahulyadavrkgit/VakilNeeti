<?php

namespace App\Http\Controllers;

use App\Models\CourtBench;
use App\Models\LegalCase;
use App\Services\CalendarEventService;
use App\Services\CourtBenchMatcher;
use App\Services\CourtBoardEmbedCheck;
use App\Services\LiveCourtBoard;
use App\Services\SupremeCourtBenches;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
     * @var array<string, list<array{district: string, url: string, vc_links: string}>>
     */
    public const DELHI_DISTRICT_COMPLEXES = [
        'Rohini' => [
            ['district' => 'North', 'url' => 'https://northdelhi.dcourts.gov.in/', 'vc_links' => 'https://northdelhi.dcourts.gov.in/document-category/vc-links/', 'bail_roster' => 'document-category/bail-roster-link-roster/'],
            ['district' => 'North West', 'url' => 'https://rohini.dcourts.gov.in/', 'vc_links' => 'https://rohini.dcourts.gov.in/document-category/vc-links/', 'bail_roster' => 'document-category/bail-roster-link-roster/'],
        ],
        'Karkardooma' => [
            ['district' => 'East', 'url' => 'https://eastdelhi.dcourts.gov.in/', 'vc_links' => 'https://eastdelhi.dcourts.gov.in/document-category/vc-links/'],
            ['district' => 'North East', 'url' => 'https://northeast.dcourts.gov.in/', 'vc_links' => 'https://northeast.dcourts.gov.in/document-category/vc-links/'],
            ['district' => 'Shahdara', 'url' => 'https://shahdara.dcourts.gov.in/', 'vc_links' => 'https://shahdara.dcourts.gov.in/document-category/vc-links/'],
        ],
        'Tis Hazari' => [
            ['district' => 'Central', 'url' => 'https://centraldelhi.dcourts.gov.in/', 'vc_links' => 'https://centraldelhi.dcourts.gov.in/document-category/central-district-vc-links/'],
            ['district' => 'West', 'url' => 'https://westdelhi.dcourts.gov.in/', 'vc_links' => 'https://westdelhi.dcourts.gov.in/document-category/vc-links/'],
        ],
        'Dwarka' => [
            ['district' => 'South West', 'url' => 'https://southwestdelhi.dcourts.gov.in/', 'vc_links' => 'https://southwestdelhi.dcourts.gov.in/document-category/vc-links/'],
        ],
        'Saket' => [
            ['district' => 'South', 'url' => 'https://southdelhi.dcourts.gov.in/', 'vc_links' => 'https://southdelhi.dcourts.gov.in/document-category/vc-links/'],
            ['district' => 'South East', 'url' => 'https://southeastdelhi.dcourts.gov.in/', 'vc_links' => 'https://southeastdelhi.dcourts.gov.in/document-category/vc-links/'],
        ],
        'Patiala House' => [
            ['district' => 'New Delhi', 'url' => 'https://newdelhi.dcourts.gov.in/', 'vc_links' => 'https://newdelhi.dcourts.gov.in/document-category/vc-links/'],
        ],
        'Rouse Avenue' => [
            ['district' => 'Central (Rouse Avenue courts)', 'url' => 'https://centraldelhi.dcourts.gov.in/', 'vc_links' => 'https://centraldelhi.dcourts.gov.in/document-category/rouse-avenue-district-court-vc-links/'],
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
     * `live` names the court's board in VakilNiti (App\Services\LiveCourtBoard), offered first.
     *
     * @var list<array{court: string, live?: string, level: string, boards: list<array{title: string, url: string}>}>
     */
    public const DISPLAY_BOARDS = [
        [
            'court' => 'Supreme Court of India',
            'live' => 'supreme-court',
            'level' => 'Supreme Court',
            'boards' => [
                ['title' => 'Physical display board', 'url' => 'https://cdb.sci.gov.in/'],
                ['title' => 'Web display board', 'url' => 'https://wdb.sci.gov.in/display_original.php'],
            ],
        ],
        [
            'court' => 'High Court of Delhi',
            'live' => 'delhi-high-court',
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
            // Our own board for this court shows inside VakilNiti, while the court's data can be reached.
            if (isset($court['live']) && app(LiveCourtBoard::class)->isAvailable($court['live'])) {
                $options[] = [
                    'court' => $court['court'],
                    'level' => $court['level'],
                    'title' => 'Live board in VakilNiti',
                    'url' => route('courts.live-board', $court['live']),
                    'embeddable' => true,
                ];
            }

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

    public function index(Request $request): View
    {
        return view('courts.index', [
            'myCourts' => $request->user() ? $this->myCourts() : collect(),
        ]);
    }

    /**
     * The courts the signed-in person's open cases are in, soonest hearing first: how many cases, how many
     * are listed today, the next date with its room, judge and VC link, and where to find the court's services.
     *
     * @return Collection<int, array{court: string, complex: ?string, cases: int, today: int, next_date: ?Carbon, next_count: int, room: ?string, judge: ?string, vc_link: ?string, cases_url: string, official_url: ?string}>
     */
    private function myCourts(): Collection
    {
        $today = today();

        $cases = LegalCase::query()
            ->whereNull('closed_at')
            ->where(fn ($status) => $status->whereNull('case_status')->orWhereNotIn('case_status', ['closed', 'disposed']))
            ->whereNotNull('court_name')
            ->where('court_name', '!=', '')
            ->with(['hearings' => fn ($hearings) => $hearings->where(fn ($dates) => $dates
                ->whereDate('hearing_date', '>=', $today)
                ->orWhereDate('next_hearing_date', '>=', $today))])
            ->get();

        return $cases
            ->groupBy(fn (LegalCase $case): string => Str::lower(trim($case->court_name)).'|'.Str::lower(trim((string) $case->court_complex)))
            ->map(function (Collection $courtCases) use ($today): array {
                // Each case's next date, with the hearing that set it (its room, judge and VC link come first).
                $upcoming = $courtCases->map(function (LegalCase $case) use ($today): ?array {
                    $next = $case->hearings
                        ->flatMap(fn ($hearing) => collect([$hearing->hearing_date, $hearing->next_hearing_date])
                            ->filter(fn (?Carbon $date): bool => $date !== null && $date->gte($today))
                            ->map(fn (Carbon $date): array => ['date' => $date, 'hearing' => $hearing]))
                        ->sortBy(fn (array $item): string => $item['date']->toDateString())
                        ->first();

                    return $next ? $next + ['case' => $case] : null;
                })->filter()->sortBy(fn (array $item): string => $item['date']->toDateString())->values();

                $first = $courtCases->first();
                $nearest = $upcoming->first();
                $nextDate = $nearest['date'] ?? null;
                $source = $nearest['case'] ?? $courtCases->first(fn (LegalCase $case): bool => (bool) ($case->court_room || $case->judge_name || $case->vc_link)) ?? $first;

                return [
                    'court' => trim($first->court_name),
                    'complex' => trim((string) $first->court_complex) ?: null,
                    'cases' => $courtCases->count(),
                    'today' => $upcoming->filter(fn (array $item): bool => $item['date']->isSameDay($today))->count(),
                    'next_date' => $nextDate,
                    'next_count' => $nextDate ? $upcoming->filter(fn (array $item): bool => $item['date']->isSameDay($nextDate))->count() : 0,
                    'room' => ($nearest['hearing'] ?? null)?->court_room ?: $source->court_room,
                    'judge' => ($nearest['hearing'] ?? null)?->judge_name ?: $source->judge_name,
                    'vc_link' => ($nearest['hearing'] ?? null)?->vc_link ?: $source->vc_link,
                    'cases_url' => route('cases.index', ['search' => trim($first->court_name), 'search_type' => 'court']),
                    'official_url' => $this->officialPageFor($first->court_name.' '.$first->court_complex),
                ];
            })
            ->sortBy(fn (array $court): string => ($court['next_date']?->toDateString() ?? '9999-12-31').sprintf('%06d', 999999 - $court['cases']))
            ->values();
    }

    /**
     * Our page of official services for a court, when we have one.
     */
    private function officialPageFor(string $court): ?string
    {
        $court = Str::lower($court);

        return match (true) {
            str_contains($court, 'supreme court') => route('courts.supreme-court'),
            str_contains($court, 'high court') && str_contains($court, 'delhi') => route('courts.delhi-high-court'),
            Str::contains($court, ['tis hazari', 'saket', 'karkardooma', 'rohini', 'dwarka', 'patiala house', 'rouse avenue']) => route('courts.delhi-district-courts'),
            default => null,
        };
    }

    public function supremeCourt(): View
    {
        return view('courts.links', [
            'title' => 'Supreme Court of India',
            'heading' => 'Supreme Court',
            'intro' => null,
            'metaDescription' => 'Supreme Court of India quick links: cause list, live physical and web display boards, SuSwagatam e-gate pass and the court calendar.',
            'links' => [
                ['title' => 'Live display board', 'description' => 'The item running in every court room now.', 'url' => route('courts.live-board', 'supreme-court'), 'icon' => 'board', 'internal' => true],
                ['title' => 'Court rooms & VC links', 'description' => 'Every court room with its VC link. Join in one tap.', 'url' => route('courts.supreme-court.court-list'), 'icon' => 'screen', 'internal' => true],
                ...self::SUPREME_COURT,
            ],
            'source' => 'sci.gov.in',
        ]);
    }

    public function delhiHighCourt(): View
    {
        return view('courts.links', [
            'title' => 'Delhi High Court',
            'heading' => 'Delhi High Court',
            'intro' => null,
            'metaDescription' => 'Delhi High Court quick links: physical display board, judges roster, daily cause list, e-filing, e-visitor pass, case status, orders, judgements and calendar.',
            'links' => [
                ['title' => 'Live display board', 'description' => 'The item running in every court room now.', 'url' => route('courts.live-board', 'delhi-high-court'), 'icon' => 'board', 'internal' => true],
                ['title' => 'Judges list & VC links', 'description' => 'Judges, court rooms, VC links and meeting IDs.', 'url' => route('courts.delhi-high-court.judges'), 'icon' => 'people', 'internal' => true],
                ...self::DELHI_HIGH_COURT,
            ],
            'source' => 'delhihighcourt.nic.in',
        ]);
    }

    /**
     * National tribunals, by the kind of matter they hear. Only the tribunals' own websites (checked 23.11.2026).
     *
     * @var array<string, list<array{name: string, short: string, hears: string, url: string, efiling?: string}>>
     */
    public const TRIBUNALS = [
        'Company, insolvency & debt' => [
            ['name' => 'National Company Law Tribunal', 'short' => 'NCLT', 'hears' => 'Insolvency (IBC), company law, oppression and mismanagement.', 'url' => 'https://nclt.gov.in/', 'efiling' => 'https://efiling.nclt.gov.in/'],
            ['name' => 'National Company Law Appellate Tribunal', 'short' => 'NCLAT', 'hears' => 'Appeals from NCLT, CCI and NFRA.', 'url' => 'https://nclat.nic.in/', 'efiling' => 'https://efiling.nclat.gov.in/'],
            ['name' => 'Debts Recovery Tribunals & Appellate Tribunals', 'short' => 'DRT / DRAT', 'hears' => 'Bank recovery and SARFAESI matters.', 'url' => 'https://drt.gov.in/'],
        ],
        'Tax' => [
            ['name' => 'Income Tax Appellate Tribunal', 'short' => 'ITAT', 'hears' => 'Income tax appeals.', 'url' => 'https://itat.gov.in/'],
            ['name' => 'Customs, Excise & Service Tax Appellate Tribunal', 'short' => 'CESTAT', 'hears' => 'Customs, central excise and service tax appeals.', 'url' => 'https://cestat.gov.in/'],
            ['name' => 'Goods and Services Tax Appellate Tribunal', 'short' => 'GSTAT', 'hears' => 'GST appeals.', 'url' => 'https://gstat.gov.in/'],
        ],
        'Service' => [
            ['name' => 'Central Administrative Tribunal', 'short' => 'CAT', 'hears' => 'Service matters of central government employees.', 'url' => 'https://cgat.gov.in/'],
            ['name' => 'Armed Forces Tribunal', 'short' => 'AFT', 'hears' => 'Service matters and court-martial appeals of the armed forces.', 'url' => 'https://aft.gov.in/'],
        ],
        'Regulatory' => [
            ['name' => 'National Green Tribunal', 'short' => 'NGT', 'hears' => 'Environment, forests and pollution.', 'url' => 'https://greentribunal.gov.in/'],
            ['name' => 'Appellate Tribunal for Electricity', 'short' => 'APTEL', 'hears' => 'Appeals against electricity regulators.', 'url' => 'https://aptel.gov.in/'],
            ['name' => 'Telecom Disputes Settlement & Appellate Tribunal', 'short' => 'TDSAT', 'hears' => 'Telecom, broadcasting and IT Act appeals.', 'url' => 'https://tdsat.gov.in/'],
        ],
        'Consumer & claims' => [
            ['name' => 'Consumer Commissions (National, State, District)', 'short' => 'NCDRC · e-Jagriti', 'hears' => 'Consumer complaints and appeals; e-Jagriti files in every commission.', 'url' => 'https://ncdrc.nic.in/', 'efiling' => 'https://e-jagriti.gov.in/'],
            ['name' => 'Railway Claims Tribunal', 'short' => 'RCT', 'hears' => 'Railway accident and goods claims.', 'url' => 'https://rct.indianrail.gov.in/'],
        ],
    ];

    public function tribunals(): View
    {
        return view('courts.tribunals', ['groups' => self::TRIBUNALS]);
    }

    /**
     * All 25 High Courts: principal seat, States and UTs covered, and the court's own website (checked 24.11.2026).
     *
     * @var list<array{name: string, seat: string, covers: string, url: string}>
     */
    public const HIGH_COURTS = [
        ['name' => 'Allahabad High Court', 'seat' => 'Prayagraj', 'covers' => 'Uttar Pradesh', 'url' => 'https://www.allahabadhighcourt.in/'],
        ['name' => 'High Court of Andhra Pradesh', 'seat' => 'Amaravati', 'covers' => 'Andhra Pradesh', 'url' => 'https://aphc.gov.in/'],
        ['name' => 'Bombay High Court', 'seat' => 'Mumbai', 'covers' => 'Maharashtra, Goa, Dadra & Nagar Haveli and Daman & Diu', 'url' => 'https://bombayhighcourt.gov.in/'],
        ['name' => 'Calcutta High Court', 'seat' => 'Kolkata', 'covers' => 'West Bengal, Andaman & Nicobar Islands', 'url' => 'https://www.calcuttahighcourt.gov.in/'],
        ['name' => 'High Court of Chhattisgarh', 'seat' => 'Bilaspur', 'covers' => 'Chhattisgarh', 'url' => 'https://highcourt.cg.gov.in/'],
        ['name' => 'High Court of Delhi', 'seat' => 'New Delhi', 'covers' => 'Delhi', 'url' => 'https://delhihighcourt.nic.in/'],
        ['name' => 'Gauhati High Court', 'seat' => 'Guwahati', 'covers' => 'Assam, Nagaland, Mizoram, Arunachal Pradesh', 'url' => 'https://ghconline.gov.in/'],
        ['name' => 'High Court of Gujarat', 'seat' => 'Ahmedabad', 'covers' => 'Gujarat', 'url' => 'https://gujarathighcourt.nic.in/'],
        ['name' => 'High Court of Himachal Pradesh', 'seat' => 'Shimla', 'covers' => 'Himachal Pradesh', 'url' => 'https://highcourt.hp.gov.in/'],
        ['name' => 'High Court of Jammu & Kashmir and Ladakh', 'seat' => 'Srinagar & Jammu', 'covers' => 'Jammu & Kashmir, Ladakh', 'url' => 'https://jkhighcourt.nic.in/'],
        ['name' => 'High Court of Jharkhand', 'seat' => 'Ranchi', 'covers' => 'Jharkhand', 'url' => 'https://jharkhandhighcourt.nic.in/'],
        ['name' => 'High Court of Karnataka', 'seat' => 'Bengaluru', 'covers' => 'Karnataka', 'url' => 'https://judiciary.karnataka.gov.in/'],
        ['name' => 'High Court of Kerala', 'seat' => 'Kochi', 'covers' => 'Kerala, Lakshadweep', 'url' => 'https://highcourt.kerala.gov.in/'],
        ['name' => 'High Court of Madhya Pradesh', 'seat' => 'Jabalpur', 'covers' => 'Madhya Pradesh', 'url' => 'https://mphc.gov.in/'],
        ['name' => 'Madras High Court', 'seat' => 'Chennai', 'covers' => 'Tamil Nadu, Puducherry', 'url' => 'https://www.mhc.tn.gov.in/judis/'],
        ['name' => 'High Court of Manipur', 'seat' => 'Imphal', 'covers' => 'Manipur', 'url' => 'https://hcmimphal.nic.in/'],
        ['name' => 'High Court of Meghalaya', 'seat' => 'Shillong', 'covers' => 'Meghalaya', 'url' => 'https://meghalayahighcourt.nic.in/'],
        ['name' => 'Orissa High Court', 'seat' => 'Cuttack', 'covers' => 'Odisha', 'url' => 'https://www.orissahighcourt.nic.in/'],
        ['name' => 'Patna High Court', 'seat' => 'Patna', 'covers' => 'Bihar', 'url' => 'https://patnahighcourt.gov.in/'],
        ['name' => 'High Court of Punjab & Haryana', 'seat' => 'Chandigarh', 'covers' => 'Punjab, Haryana, Chandigarh', 'url' => 'https://highcourtchd.gov.in/'],
        ['name' => 'Rajasthan High Court', 'seat' => 'Jodhpur', 'covers' => 'Rajasthan', 'url' => 'https://hcraj.nic.in/'],
        ['name' => 'High Court of Sikkim', 'seat' => 'Gangtok', 'covers' => 'Sikkim', 'url' => 'https://hcs.gov.in/'],
        ['name' => 'High Court for the State of Telangana', 'seat' => 'Hyderabad', 'covers' => 'Telangana', 'url' => 'https://tshc.gov.in/'],
        ['name' => 'High Court of Tripura', 'seat' => 'Agartala', 'covers' => 'Tripura', 'url' => 'https://thc.nic.in/'],
        ['name' => 'High Court of Uttarakhand', 'seat' => 'Nainital', 'covers' => 'Uttarakhand', 'url' => 'https://highcourtofuttarakhand.gov.in/'],
    ];

    /**
     * One court room's own public page (judge, room, VC link and how to reach the court), so an advocate
     * searching Google for a judge or a room finds it. Old or wrong words in the address lead to the right one.
     */
    /**
     * A judge's photo, where the web server cannot hand it out itself (no public/storage link). An address with the
     * photo's version (?v=) never changes its contents, so browsers keep it for a year; an old address without one,
     * for an hour.
     */
    public function judgePhoto(Request $request, string $photo): StreamedResponse
    {
        $disk = Storage::disk('public');
        $path = 'judges/'.$photo;

        abort_unless($disk->exists($path), 404);

        return $disk->response($path, headers: ['Cache-Control' => $request->query('v') ? 'public, max-age=31536000, immutable' : 'public, max-age=3600']);
    }

    public function room(CourtBench $bench, ?string $slug = null): View|RedirectResponse
    {
        if ($slug !== $bench->pageSlug()) {
            return redirect()->to($bench->pageUrl(), 301);
        }

        $court = CourtBench::courts()[$bench->court_key] ?? ['court' => $bench->court_key, 'title' => 'Judges list'];
        $listUrl = match (true) {
            $bench->court_key === 'supreme-court' => route('courts.supreme-court.court-list'),
            $bench->court_key === 'delhi-high-court' => route('courts.delhi-high-court.judges'),
            default => route('courts.delhi-district-courts.judges', Str::after($bench->court_key, 'delhi-district-')),
        };

        return view('courts.room', [
            'bench' => $bench,
            'court' => $court,
            'listUrl' => $listUrl,
            'neighbours' => CourtBench::query()->where('court_key', $bench->court_key)->where('id', '!=', $bench->id)->orderBy('sort_order')->limit(8)->get(),
        ]);
    }

    public function highCourts(): View
    {
        return view('courts.high-courts', [
            'courts' => self::HIGH_COURTS,
            'caseStatus' => 'https://hcservices.ecourts.gov.in/',
            'efiling' => 'https://filing.ecourts.gov.in/',
        ]);
    }

    public function ecourtsServices(): View
    {
        return view('courts.links', [
            'title' => 'eCourts services',
            'heading' => 'eCourts services',
            'intro' => 'National eCourts services for High Courts and District Courts.',
            'metaDescription' => 'eCourts services in one place: District and High Court case status, judgment search, ePay, eFiling, NJDG and virtual courts.',
            'links' => self::ECOURTS_SERVICES,
            'source' => 'ecourts.gov.in',
            'showCnrSearch' => true,
        ]);
    }

    /**
     * The same pages on every Delhi district website, for the six buttons under each district.
     * Bail rosters have no fixed page on most sites, so their search opens the latest roster.
     *
     * @var array<string, string>
     */
    public const DELHI_DISTRICT_PAGES = [
        'Judges on leave' => 'judges-on-leave/',
        'Bail roster' => '?s=bail+roster',
        'Duty magistrate' => 'duty-magistrate/',
        'Case status / Order' => 'case-status-search-by-case-number/',
        'Cause list' => 'cause-list-%e2%81%84-daily-board/',
    ];

    public function delhiDistrictCourts(Request $request): View
    {
        $benches = CourtBench::query()->where('court_key', 'like', 'delhi-district-%')->get();
        $isVacant = fn (CourtBench $bench): bool => (bool) preg_match('/\bvacant\b/i', collect($bench->judgeList())->pluck('name')->implode(' '));

        // Courts and vacancies per district list, from the official judges & VC lists.
        $stats = $benches->groupBy('court_key')->map(fn ($rooms): array => [
            'courts' => $rooms->count(),
            'vacant' => $rooms->filter($isVacant)->count(),
        ]);

        $query = Str::of((string) $request->query('q'))->squish()->limit(60, '')->toString();
        $results = mb_strlen($query) < 2 ? collect() : $benches
            ->filter(function (CourtBench $bench) use ($query): bool {
                $haystack = collect($bench->judgeList())->flatMap(fn (array $judge): array => [$judge['name'] ?? '', $judge['designation'] ?? ''])
                    ->push((string) $bench->court_room)
                    ->implode(' ');

                return Str::contains(Str::lower($haystack), Str::lower($query));
            })
            ->take(40)
            ->values();

        return view('courts.delhi-district-courts', [
            'complexes' => self::DELHI_DISTRICT_COMPLEXES,
            'services' => self::DELHI_DISTRICT_SERVICES,
            'pages' => self::DELHI_DISTRICT_PAGES,
            'stats' => $stats,
            'totals' => ['courts' => $benches->count(), 'vacant' => $benches->filter($isVacant)->count(), 'districts' => 11, 'complexes' => count(self::DELHI_DISTRICT_COMPLEXES)],
            'query' => $query,
            'results' => $results,
            'districtNames' => collect(self::DELHI_DISTRICT_COMPLEXES)->flatten(1)->mapWithKeys(fn (array $district): array => ['delhi-district-'.Str::slug($district['district']) => $district['district']]),
        ]);
    }

    public function supremeCourtList(): View
    {
        return $this->courtRooms('supreme-court');
    }

    /**
     * A Supreme Court room's cause list on the court's site, for the date asked or, without one, the day the link
     * is opened: the court number and date are put into the court's own address when clicked.
     */
    public function supremeCourtCauseList(Request $request, string $court): RedirectResponse
    {
        $date = $request->query('date');
        $day = is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && strtotime($date) ? $date : today()->toDateString();

        return redirect()->away(SupremeCourtBenches::listUrl((int) $court, $day));
    }

    public function delhiHighCourtJudges(): View
    {
        return $this->courtRooms('delhi-high-court');
    }

    public function delhiDistrictJudges(string $district): View
    {
        return $this->courtRooms('delhi-district-'.$district);
    }

    /**
     * Court rooms with judges, VC link, meeting ID and email, searchable on one page.
     */
    private function courtRooms(string $courtKey): View
    {
        $court = CourtBench::courts()[$courtKey] ?? abort(404);
        $benches = CourtBench::query()
            ->where('court_key', $courtKey)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        // Sections (Division Bench, Single Bench — Criminal…) as one-tap filters: ?section=slug.
        $sections = $benches->whereNotNull('section')->countBy('section')->all();
        $activeSection = collect(array_keys($sections))
            ->first(fn (string $section) => Str::slug($section) === request()->query('section'));

        return view('courts.court-rooms', [
            'courtKey' => $courtKey,
            'court' => $court,
            'sections' => $sections,
            'activeSection' => $activeSection,
            'total' => $benches->count(),
            'groups' => ($activeSection ? $benches->where('section', $activeSection) : $benches)->groupBy(fn (CourtBench $bench) => $bench->section ?? ''),
            'causeList' => CourtBench::CAUSE_LISTS[$courtKey] ?? self::DELHI_DISTRICT_SERVICES['cause_list'],
            'source' => CourtBench::SOURCES[$courtKey] ?? null,
        ]);
    }

    public function displayBoards(): View
    {
        return view('courts.display-boards', ['courts' => self::DISPLAY_BOARDS]);
    }

    /**
     * Our own live board for a court, built from the data the court publishes for its public board.
     * It is a page of its own so the board viewer can show it inside VakilNiti (the courts' pages refuse that).
     * With ?room=12 that court is highlighted.
     */
    public function liveBoard(Request $request, string $court, LiveCourtBoard $boards): View
    {
        $myRooms = $request->user() ? $this->myRoomsToday($court) : [];
        $askedRoom = LiveCourtBoard::roomKey($court, $request->string('room')->toString());

        if ($askedRoom !== null && ! isset($myRooms[$askedRoom])) {
            $myRooms[$askedRoom] = [];
        }

        return view('courts.live-board', [
            'board' => $boards->board($court),
            'myRooms' => $myRooms,
        ]);
    }

    /**
     * The signed-in person's hearings today on this court's board, by board room: case title, number and time.
     * A room missing from the case is taken from the official court list, as on the hearing cards.
     *
     * @return array<string, list<array{title: string, case_number: ?string, time: ?string}>>
     */
    private function myRoomsToday(string $court): array
    {
        $matcher = app(CourtBenchMatcher::class);
        $rooms = [];

        $hearings = app(CalendarEventService::class)->between(today(), today(), ['groups' => ['hearing'], 'user_id' => auth()->user()->workspaceId()]);

        foreach ($hearings as $hearing) {
            if (LiveCourtBoard::boardFor($hearing['court'] ?? null) !== $court) {
                continue;
            }

            $room = $hearing['court_room'] ?? null;

            if (! trim((string) $room)) {
                $room = $matcher->find($hearing['court'] ?? null, $hearing['court_complex'] ?? null, $hearing['judge_name'] ?? null)?->court_room;
            }

            $key = LiveCourtBoard::roomKey($court, $room);

            if ($key !== null) {
                $rooms[$key][] = ['title' => $hearing['title'], 'case_number' => $hearing['case_number'] ?? null, 'time' => $hearing['time'] ?? null];
            }
        }

        return $rooms;
    }

    /**
     * The live board as JSON, for the board page and the "now running" chips beside today's hearings.
     */
    public function liveBoardData(string $court, LiveCourtBoard $boards): JsonResponse
    {
        return response()->json($boards->board($court))->header('Cache-Control', 'public, max-age=20');
    }
}
