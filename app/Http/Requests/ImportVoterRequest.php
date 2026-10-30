<?php

namespace App\Http\Requests;

use App\Models\Election;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportVoterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'election_id' => ['required', 'uuid', Rule::exists(Election::class, 'id')],
            'file' => ['required', 'file', 'mimes:xlsx', 'max:2048'],
        ];
    }
}
