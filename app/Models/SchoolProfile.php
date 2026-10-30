<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SchoolProfile extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'website',
        'headmaster_name',
        'headmaster_nip',
        'logo_path',
    ];
}
