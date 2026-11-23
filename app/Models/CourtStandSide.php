<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Where the petitioner's counsel stands in one court, as the advocate recorded it.
 */
class CourtStandSide extends Model
{
    use Concerns\BelongsToUser;

    /**
     * Sides as seen when facing the judge.
     *
     * @var array<string, string>
     */
    public const SIDES = ['left' => 'Left', 'right' => 'Right'];

    protected $fillable = [
        'user_id',
        'court_name',
        'petitioner_side',
    ];

    /**
     * Where our counsel stands for a case, or null when the court's practice or our side is not recorded.
     *
     * @return array{side: string, label: string, petitioner_side: string}|null
     */
    public static function forCase(LegalCase $case): ?array
    {
        if (! $case->court_name || ! $case->appearing_for) {
            return null;
        }

        $petitionerSide = self::query()
            ->withoutGlobalScopes()
            ->where('user_id', $case->user_id)
            ->where('court_name', $case->court_name)
            ->value('petitioner_side');

        if (! isset(self::SIDES[$petitionerSide])) {
            return null;
        }

        $side = $case->appearsForMovingSide() || $case->appearing_for === 'other'
            ? $petitionerSide
            : ($petitionerSide === 'left' ? 'right' : 'left');

        return ['side' => $side, 'label' => self::SIDES[$side], 'petitioner_side' => $petitionerSide];
    }
}
