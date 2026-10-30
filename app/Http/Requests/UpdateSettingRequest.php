<?php

namespace App\Http\Requests;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(Setting::STATUSES)],
            'voting_enabled' => ['boolean'],
            'results_public' => ['boolean'],
            'announcement' => ['nullable', 'string'],
        ];
    }
}
