<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

class State extends Model
{
    /**
     * Official States and Union Territories of India mapped to their internal state codes.
     *
     * The codes match the ones seeded by CourtCatalogueSeeder, so the list works even
     * before the states table has been seeded.
     *
     * @var array<string, string>
     */
    public const INDIAN_STATES = [
        'Andhra Pradesh' => 'IN-AP',
        'Arunachal Pradesh' => 'IN-AR',
        'Assam' => 'IN-AS',
        'Bihar' => 'IN-BR',
        'Chhattisgarh' => 'IN-CG',
        'Goa' => 'IN-GA',
        'Gujarat' => 'IN-GJ',
        'Haryana' => 'IN-HR',
        'Himachal Pradesh' => 'IN-HP',
        'Jharkhand' => 'IN-JH',
        'Karnataka' => 'IN-KA',
        'Kerala' => 'IN-KL',
        'Madhya Pradesh' => 'IN-MP',
        'Maharashtra' => 'IN-MH',
        'Manipur' => 'IN-MN',
        'Meghalaya' => 'IN-ML',
        'Mizoram' => 'IN-MZ',
        'Nagaland' => 'IN-NL',
        'Odisha' => 'IN-OD',
        'Punjab' => 'IN-PB',
        'Rajasthan' => 'IN-RJ',
        'Sikkim' => 'IN-SK',
        'Tamil Nadu' => 'IN-TN',
        'Telangana' => 'IN-TS',
        'Tripura' => 'IN-TR',
        'Uttar Pradesh' => 'IN-UP',
        'Uttarakhand' => 'IN-UK',
        'West Bengal' => 'IN-WB',
    ];

    /**
     * Union Territories of India mapped to their internal state codes.
     *
     * @var array<string, string>
     */
    public const INDIAN_UNION_TERRITORIES = [
        'Andaman and Nicobar Islands' => 'IN-AN',
        'Chandigarh' => 'IN-CH',
        'Dadra and Nagar Haveli and Daman and Diu' => 'IN-DH',
        'Delhi' => 'IN-DL',
        'Jammu and Kashmir' => 'IN-JK',
        'Ladakh' => 'IN-LA',
        'Lakshadweep' => 'IN-LD',
        'Puducherry' => 'IN-PY',
    ];

    /**
     * Official two-digit GST state codes, keyed by State / Union Territory name.
     *
     * @var array<string, string>
     */
    public const GST_STATE_CODES = [
        'Jammu and Kashmir' => '01',
        'Himachal Pradesh' => '02',
        'Punjab' => '03',
        'Chandigarh' => '04',
        'Uttarakhand' => '05',
        'Haryana' => '06',
        'Delhi' => '07',
        'Rajasthan' => '08',
        'Uttar Pradesh' => '09',
        'Bihar' => '10',
        'Sikkim' => '11',
        'Arunachal Pradesh' => '12',
        'Nagaland' => '13',
        'Manipur' => '14',
        'Mizoram' => '15',
        'Tripura' => '16',
        'Meghalaya' => '17',
        'Assam' => '18',
        'West Bengal' => '19',
        'Jharkhand' => '20',
        'Odisha' => '21',
        'Chhattisgarh' => '22',
        'Madhya Pradesh' => '23',
        'Gujarat' => '24',
        'Dadra and Nagar Haveli and Daman and Diu' => '26',
        'Maharashtra' => '27',
        'Karnataka' => '29',
        'Goa' => '30',
        'Lakshadweep' => '31',
        'Kerala' => '32',
        'Tamil Nadu' => '33',
        'Puducherry' => '34',
        'Andaman and Nicobar Islands' => '35',
        'Telangana' => '36',
        'Andhra Pradesh' => '37',
        'Ladakh' => '38',
    ];

    /**
     * Suggested Bar Council enrolment prefixes ("UP" in UP/1234/2017), keyed by the State where
     * the advocate enrolled. Places sharing a Bar Council share its prefix. Places whose prefix
     * is not certain are left out, and people can always change the suggestion.
     *
     * @var array<string, string>
     */
    public const BAR_COUNCIL_CODES = [
        'Andhra Pradesh' => 'AP',
        'Chhattisgarh' => 'CG',
        'Delhi' => 'D',
        'Gujarat' => 'G',
        'Himachal Pradesh' => 'HP',
        'Jammu and Kashmir' => 'JK',
        'Ladakh' => 'JK',
        'Jharkhand' => 'JH',
        'Karnataka' => 'KAR',
        'Kerala' => 'K',
        'Lakshadweep' => 'K',
        'Madhya Pradesh' => 'MP',
        'Maharashtra' => 'MAH',
        'Goa' => 'MAH',
        'Dadra and Nagar Haveli and Daman and Diu' => 'MAH',
        'Odisha' => 'OR',
        'Punjab' => 'PH',
        'Haryana' => 'PH',
        'Chandigarh' => 'PH',
        'Rajasthan' => 'R',
        'Tamil Nadu' => 'MS',
        'Puducherry' => 'MS',
        'Telangana' => 'TS',
        'Uttar Pradesh' => 'UP',
        'Uttarakhand' => 'UK',
        'West Bengal' => 'WB',
        'Andaman and Nicobar Islands' => 'WB',
    ];

    /**
     * The High Court with jurisdiction over each State / Union Territory (as in HighCourtSeeder).
     *
     * @var array<string, string>
     */
    public const HIGH_COURTS = [
        'Andhra Pradesh' => 'Andhra Pradesh High Court',
        'Arunachal Pradesh' => 'Gauhati High Court',
        'Assam' => 'Gauhati High Court',
        'Bihar' => 'Patna High Court',
        'Chhattisgarh' => 'Chhattisgarh High Court',
        'Goa' => 'Bombay High Court',
        'Gujarat' => 'Gujarat High Court',
        'Haryana' => 'Punjab and Haryana High Court',
        'Himachal Pradesh' => 'Himachal Pradesh High Court',
        'Jharkhand' => 'Jharkhand High Court',
        'Karnataka' => 'Karnataka High Court',
        'Kerala' => 'Kerala High Court',
        'Madhya Pradesh' => 'Madhya Pradesh High Court',
        'Maharashtra' => 'Bombay High Court',
        'Manipur' => 'Manipur High Court',
        'Meghalaya' => 'Meghalaya High Court',
        'Mizoram' => 'Gauhati High Court',
        'Nagaland' => 'Gauhati High Court',
        'Odisha' => 'Orissa High Court',
        'Punjab' => 'Punjab and Haryana High Court',
        'Rajasthan' => 'Rajasthan High Court',
        'Sikkim' => 'Sikkim High Court',
        'Tamil Nadu' => 'Madras High Court',
        'Telangana' => 'Telangana High Court',
        'Tripura' => 'Tripura High Court',
        'Uttar Pradesh' => 'Allahabad High Court',
        'Uttarakhand' => 'Uttarakhand High Court',
        'West Bengal' => 'Calcutta High Court',
        'Andaman and Nicobar Islands' => 'Calcutta High Court',
        'Chandigarh' => 'Punjab and Haryana High Court',
        'Dadra and Nagar Haveli and Daman and Diu' => 'Bombay High Court',
        'Delhi' => 'Delhi High Court',
        'Jammu and Kashmir' => 'High Court of Jammu and Kashmir and Ladakh',
        'Ladakh' => 'High Court of Jammu and Kashmir and Ladakh',
        'Lakshadweep' => 'Kerala High Court',
        'Puducherry' => 'Madras High Court',
    ];

    /**
     * All States followed by all Union Territories, keyed by name.
     *
     * @return array<string, string>
     */
    public static function indianStatesAndUnionTerritories(): array
    {
        return self::INDIAN_STATES + self::INDIAN_UNION_TERRITORIES;
    }

    /**
     * Validation rule: the value must be one of the official State / Union Territory names.
     */
    public static function nameRule(): In
    {
        return Rule::in(array_keys(self::indianStatesAndUnionTerritories()));
    }

    /**
     * Option data for State / Union Territory dropdowns, grouped for display.
     *
     * The database id is included when the states table is seeded, so the form can load
     * that state's judicial districts. Without it the district field falls back to free text.
     *
     * @return array<string, list<array{name: string, code: string, gst_code: string, high_court: string, id: int|null}>>
     */
    public static function dropdownGroups(): array
    {
        $databaseIds = self::query()->pluck('id', 'name');

        $toOptions = fn (array $states): array => collect($states)
            ->map(fn (string $code, string $name): array => [
                'name' => $name,
                'code' => $code,
                'gst_code' => self::GST_STATE_CODES[$name],
                'high_court' => self::HIGH_COURTS[$name],
                'id' => $databaseIds[$name] ?? null,
            ])
            ->values()
            ->all();

        return [
            'States' => $toOptions(self::INDIAN_STATES),
            'Union Territories' => $toOptions(self::INDIAN_UNION_TERRITORIES),
        ];
    }

    protected $fillable = [
        'name',
        'code',
        'country_code',
        'is_union_territory',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_union_territory' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function courts(): HasMany
    {
        return $this->hasMany(Court::class);
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    public function judicialDistricts(): HasMany
    {
        return $this->hasMany(JudicialDistrict::class);
    }
}
