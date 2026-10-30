<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    use HasUuids;

    public const STATUS_NOT_STARTED = 'not_started';

    public const STATUS_ONGOING = 'ongoing';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_FINAL = 'final';

    protected $fillable = [
        'election_id',
        'status',
        'voting_enabled',
        'results_public',
        'announcement',
    ];

    protected function casts(): array
    {
        return [
            'voting_enabled' => 'boolean',
            'results_public' => 'boolean',
        ];
    }

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    /**
     * @var array<int, string>
     */
    public const STATUSES = ['not_started', 'ongoing', 'paused', 'closed', 'final'];

    public function isFinal(): bool
    {
        return $this->status === self::STATUS_FINAL;
    }
}
