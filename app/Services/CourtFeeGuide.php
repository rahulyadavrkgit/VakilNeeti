<?php

namespace App\Services;

use App\Models\State;

/**
 * Where court fee for a filing is governed and paid: Supreme Court, or a State's High Court
 * or District Courts. VakilNiti publishes no fee tables: each answer points to the
 * official law and the official payment and e-filing services.
 */
class CourtFeeGuide
{
    /**
     * @var array<string, string>
     */
    public const COURTS = [
        'supreme' => 'Supreme Court',
        'high' => 'High Court',
        'district' => 'District Court',
    ];

    /**
     * @return list<string>
     */
    public static function states(): array
    {
        $states = array_keys(State::indianStatesAndUnionTerritories());
        sort($states);

        return $states;
    }

    /**
     * The guide for a court level (and State for High and District Courts); null until a State is chosen.
     *
     * @return array{court: string, title: string, law: string, links: list<array{label: string, hint: string, url: string}>}|null
     */
    public function for(string $court, ?string $state): ?array
    {
        if ($court === 'supreme') {
            return [
                'court' => 'Supreme Court of India',
                'title' => 'Supreme Court of India',
                'law' => 'Supreme Court Rules, 2013 (court fee schedule)',
                'links' => [
                    ['label' => 'Supreme Court Rules', 'hint' => 'Fee schedule', 'url' => 'https://www.sci.gov.in/supreme-court-rules/'],
                    ['label' => 'SC e-filing', 'hint' => 'File and pay online', 'url' => 'https://efiling.sci.gov.in/'],
                ],
            ];
        }

        if ($state === null || ! in_array($state, self::states(), true)) {
            return null;
        }

        $highCourt = State::HIGH_COURTS[$state] ?? 'High Court';
        $lawSearch = 'https://indiacode.gov.in/search?query='.rawurlencode($state.' court fees');
        $payOnline = ['label' => 'Pay court fee', 'hint' => 'eCourts ePay', 'url' => 'https://pay.ecourts.gov.in/epay/'];
        $eStamp = ['label' => 'e-Court fee stamp', 'hint' => 'SHCIL, where the State uses it', 'url' => 'https://www.shcilestamp.com/'];

        return [
            'court' => $court === 'high' ? $highCourt : 'District Courts, '.$state,
            'title' => $court === 'high' ? $highCourt.' ('.$state.')' : 'District Courts of '.$state,
            'law' => $state.' court fees law (with State amendments)',
            'links' => [
                ['label' => $state.' court fees law', 'hint' => 'India Code', 'url' => $lawSearch],
                $payOnline,
                $eStamp,
                ['label' => 'eCourts e-filing', 'hint' => $court === 'high' ? 'High Court filing' : 'District filing', 'url' => 'https://efiling.ecourts.gov.in/'],
            ],
        ];
    }
}
