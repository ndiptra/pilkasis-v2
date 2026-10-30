<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidate extends Model
{
    use HasUuids;

    protected $fillable = [
        'election_id',
        'order_number',
        'chairman_name',
        'vice_chairman_name',
        'vision',
        'mission',
        'photo_path',
    ];

    protected function casts(): array
    {
        return [
            'order_number' => 'integer',
        ];
    }

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }
}
