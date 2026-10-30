<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Voter extends Model
{
    use HasUuids;

    protected static function booted(): void
    {
        static::creating(function (Voter $voter) {
            $voter->password = static::generateUniquePassword($voter->election_id);
        });

        static::updating(function (Voter $voter) {
            $voter->password = $voter->getOriginal('password');
        });
    }

    public static function generateUniquePassword(?string $electionId): string
    {
        do {
            $password = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT).'*';
        } while (static::query()->where('election_id', $electionId)->where('password', $password)->exists());

        return $password;
    }

    protected $fillable = [
        'election_id',
        'nisn',
        'name',
        'grade',
        'is_active',
        'has_voted',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'has_voted' => 'boolean',
        ];
    }

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function vote(): HasOne
    {
        return $this->hasOne(Vote::class);
    }
}
