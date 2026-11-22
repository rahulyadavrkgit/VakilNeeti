<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\Rule;

class AdvocateProfile extends Model
{
    /**
     * Practice specializations an advocate can choose from.
     *
     * @var list<string>
     */
    public const SPECIALIZATIONS = [
        'Civil',
        'Criminal',
        'Family Law',
        'Corporate Law',
        'Labour Law',
        'Intellectual Property Law',
        'Divorce Law',
        'Property Law',
        'Consumer Service Law',
        'Tax Law',
        'Real Estate',
    ];

    protected $fillable = [
        'gender',
        'photo_path',
        'years_experience',
        'graduation_year',
        'district',
        'state_code',
        'bar_registration_year',
        'languages_spoken',
        'specializations',
        'practice_areas',
        'bar_council_registration_number',
        'bar_state',
        'bar_state_code',
        'bar_number',
        'city',
        'region',
        'pincode',
        'court',
    ];

    protected function casts(): array
    {
        return [
            'languages_spoken' => 'array',
            'specializations' => 'array',
            'bar_registration_year' => 'integer',
        ];
    }

    /**
     * Validation rules for the Bar Council enrolment parts (State, State code, number, year).
     *
     * @return array<string, list<mixed>>
     */
    public static function barRegistrationRules(bool $required): array
    {
        $presence = $required ? 'required' : 'nullable';

        return [
            'bar_state' => [$presence, 'required_with:bar_number', 'string', Rule::in(array_keys(State::indianStatesAndUnionTerritories()))],
            'bar_state_code' => [$presence, 'required_with:bar_number', 'string', 'regex:/^[A-Z]{1,10}$/'],
            'bar_number' => [$presence, 'string', 'regex:/^[0-9A-Z]{1,20}$/'],
            'bar_registration_year' => [$presence, 'required_with:bar_number', 'integer', 'between:1950,'.now()->year],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function barRegistrationMessages(): array
    {
        return [
            'bar_state.required' => 'Choose the State of your Bar Council.',
            'bar_state.required_with' => 'Choose the State of your Bar Council.',
            'bar_state.in' => 'Choose a State or Union Territory from the list.',
            'bar_state_code.required' => 'Enter the State code from your enrolment number, e.g. UP.',
            'bar_state_code.required_with' => 'Enter the State code from your enrolment number, e.g. UP.',
            'bar_state_code.regex' => 'Use letters only for the State code, e.g. UP.',
            'bar_number.required' => 'Enter your enrolment number.',
            'bar_number.regex' => 'Use letters and digits only for the enrolment number, e.g. 1234.',
            'bar_registration_year.required' => 'Choose your enrolment year.',
            'bar_registration_year.required_with' => 'Choose your enrolment year.',
        ];
    }

    /**
     * Tidy typed values before validation: capital letters, no spaces, dots, dashes or slashes.
     *
     * @param  array<string, mixed>  $input
     * @return array{bar_state_code: ?string, bar_number: ?string}
     */
    public static function normaliseBarInput(array $input): array
    {
        $clean = function (mixed $value): ?string {
            $value = strtoupper((string) preg_replace('/[\s.\/\-]+/', '', (string) $value));

            return $value === '' ? null : $value;
        };

        return [
            'bar_state_code' => $clean($input['bar_state_code'] ?? null),
            'bar_number' => $clean($input['bar_number'] ?? null),
        ];
    }

    /**
     * The full enrolment number as written on the certificate and used by eCourts, e.g. "UP/1234/2017".
     */
    public static function composeBarRegistration(?string $stateCode, ?string $number, ?int $year): ?string
    {
        if (! $number) {
            return null;
        }

        return implode('/', array_filter([$stateCode, $number, $year]));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
