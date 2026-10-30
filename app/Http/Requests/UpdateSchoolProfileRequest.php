<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSchoolProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'headmaster_name' => ['required', 'string', 'max:255'],
            'headmaster_nip' => ['nullable', 'digits_between:1,30'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama sekolah',
            'address' => 'alamat',
            'phone' => 'telepon',
            'email' => 'email',
            'website' => 'website',
            'headmaster_name' => 'nama kepala sekolah',
            'headmaster_nip' => 'NIP',
            'logo' => 'logo',
        ];
    }
}
