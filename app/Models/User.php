<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'role', 'is_active', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function canModifyData(): bool
    {
        if ($this->isSuperadmin()) {
            return true;
        }

        $status = Election::current()?->setting?->status;

        return $status === null || in_array($status, [Setting::STATUS_NOT_STARTED, Setting::STATUS_PAUSED], true);
    }

    public function canViewResults(): bool
    {
        return $this->isSuperadmin() || (bool) Election::current()?->isFinal();
    }

    public function isSuperadmin(): bool
    {
        return $this->role === 'superadmin';
    }
}
