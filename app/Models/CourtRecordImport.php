<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CourtRecordImport extends Model
{
    use Concerns\BelongsToUser;

    protected $fillable = [
        'source_name',
        'source_url',
        'raw_content',
        'parsed_data',
        'import_status',
        'legal_case_id',
        'retrieved_at',
    ];

    protected function casts(): array
    {
        return [
            'parsed_data' => 'array',
            'retrieved_at' => 'datetime',
        ];
    }

    public function retryMethod(): ?string
    {
        return match ($this->source_name) {
            'eCourts app export' => 'app',
            'Calendar export' => 'calendar',
            'Pasted eCourts results' => 'website',
            default => null,
        };
    }

    public static function retryParent(?int $retry, string $method): ?self
    {
        if (! $retry) {
            return null;
        }

        $parent = self::query()->whereIn('import_status', ['failed', 'partial'])->find($retry);
        if ($parent?->retryMethod() !== $method) {
            throw ValidationException::withMessages(['retry' => 'That failed import is unavailable for this workspace or file type.']);
        }

        return $parent;
    }

    public function auditRetryOutcome(int $actorId): void
    {
        $parent = $this->parsed_data['retry_of'] ?? null;
        if ($parent && in_array($this->import_status, ['imported', 'partial', 'failed'], true)) {
            DB::table('admin_audit_entries')->insert([
                'actor_id' => $actorId, 'account_id' => $this->user_id,
                'action' => 'import retry '.$this->import_status, 'reason' => 'Original import #'.$parent.', retry #'.$this->id,
                'created_at' => now(),
            ]);
        }
    }

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class, 'legal_case_id');
    }
}
