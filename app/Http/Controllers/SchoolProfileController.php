<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSchoolProfileRequest;
use App\Models\SchoolProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SchoolProfileController extends Controller
{
    public function edit()
    {
        return Inertia::render('SchoolProfile/Edit', [
            'title' => 'Identitas Sekolah',
            'subtitle' => 'Informasi sekolah yang digunakan pada aplikasi',
            'profile' => SchoolProfile::first(),
        ]);
    }

    public function update(UpdateSchoolProfileRequest $request): RedirectResponse
    {
        $profile = SchoolProfile::firstOrCreate([], ['name' => '']);
        $data = $request->safe()->except('logo');
        if ($request->hasFile('logo')) {
            if ($profile->logo_path) {
                Storage::disk('public')->delete($profile->logo_path);
            }

            $logo = $request->file('logo');
            $filename = Str::random(40).'.'.$logo->extension();

            $data['logo_path'] = $logo->storeAs('school', $filename, 'public');
        }

        $profile->update($data);

        return back()->with('success', 'Identitas sekolah berhasil disimpan.');
    }
}
