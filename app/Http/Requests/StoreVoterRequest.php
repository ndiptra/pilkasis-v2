<?php

namespace App\Http\Requests;

use App\Models\Election;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVoterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [
            'nisn.regex' => 'NISN harus 10 digit, atau kode 4 digit untuk non-siswa.',
        ];
    }

    public function rules(): array
    {
        return [
            'election_id' => ['required', 'uuid', Rule::exists(Election::class, 'id')],
            'nisn' => [
                'required',
                'regex:/^(\d{4}|\d{10})$/',
                Rule::unique('voters', 'nisn')->where('election_id', $this->input('election_id')),
            ],
            'name' => ['required', 'string', 'max:255'],
            'grade' => ['required', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ];
    }
}
