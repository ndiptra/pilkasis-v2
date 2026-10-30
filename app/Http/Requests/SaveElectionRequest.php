<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveElectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'year' => ['required', 'digits:4', 'integer', 'between:1900,2100'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama pemilihan',
            'year' => 'tahun',
            'starts_at' => 'waktu mulai',
            'ends_at' => 'waktu selesai',
        ];
    }
}
