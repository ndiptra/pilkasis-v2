<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\SanitizesRichText;
use App\Models\Election;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCandidateRequest extends FormRequest
{
    use SanitizesRichText;

    protected function prepareForValidation(): void
    {
        $this->sanitizeRichTextFields(['vision', 'mission']);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $electionId = Election::current()?->id;

        return [
            'order_number' => [
                'required',
                'integer',
                'min:1',
                'max:255',
                Rule::unique('candidates', 'order_number')->where('election_id', $electionId),
            ],
            'chairman_name' => ['required', 'string', 'max:255'],
            'vice_chairman_name' => ['nullable', 'string', 'max:255'],
            'vision' => ['nullable', 'string'],
            'mission' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
