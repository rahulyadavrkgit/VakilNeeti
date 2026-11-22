<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * Quick links to official court services: the Supreme Court dashboard and live display boards.
 * Every link opens the court's own website; nothing is fetched or copied into VakilNiti.
 */
class CourtLinksController extends Controller
{
    /**
     * @var list<array{title: string, description: string, url: string, icon: string}>
     */
    public const SUPREME_COURT = [
        [
            'title' => 'Court list',
            'description' => 'Daily and advance cause lists: which matters are listed, in which court.',
            'url' => 'https://www.sci.gov.in/cause-list/',
            'icon' => 'list',
        ],
        [
            'title' => 'Physical display board',
            'description' => 'Live item numbers running in every courtroom.',
            'url' => 'https://cdb.sci.gov.in/',
            'icon' => 'board',
        ],
        [
            'title' => 'Web display board',
            'description' => 'The original all-courts display, item by item.',
            'url' => 'https://wdb.sci.gov.in/display_original.php',
            'icon' => 'screen',
        ],
        [
            'title' => 'E-gate pass',
            'description' => 'SuSwagatam: apply for and download your entry e-pass.',
            'url' => 'https://suswagatam.sci.gov.in/',
            'icon' => 'pass',
        ],
        [
            'title' => 'Supreme Court calendar',
            'description' => 'Sittings, non-sitting days, special benches and court notices.',
            'url' => 'https://www.sci.gov.in/calendar/',
            'icon' => 'calendar',
        ],
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
                ['title' => 'Physical display board', 'url' => 'https://delhihighcourt.nic.in/app/physical-display-board'],
                ['title' => 'Display board page', 'url' => 'https://delhihighcourt.nic.in/web/display-board'],
            ],
        ],
        [
            'court' => 'Allahabad High Court',
            'level' => 'High Court',
            'boards' => [
                ['title' => 'Court View display board', 'url' => 'https://courtview2.allahabadhighcourt.in/courtview/CourtViewAllahabad.do'],
            ],
        ],
        [
            'court' => 'High Court for the State of Telangana',
            'level' => 'High Court',
            'boards' => [
                ['title' => 'Display board', 'url' => 'https://tshc.gov.in/Hcdbs/displayboard.jsp'],
            ],
        ],
    ];

    public function supremeCourt(): View
    {
        return view('court-directory.supreme-court', ['links' => self::SUPREME_COURT]);
    }

    public function displayBoards(): View
    {
        return view('court-directory.display-boards', ['courts' => self::DISPLAY_BOARDS]);
    }
}
