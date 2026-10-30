<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function edit()
    {
        return Inertia::render('Profile/Edit', [
            'title' => 'Profil',
            'subtitle' => 'Perbarui informasi akun Anda',
            'profile' => request()->user()->only(['id', 'name', 'email', 'role', 'created_at']),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $data = $request->safe()->only(['name', 'email']);

        if ($request->filled('password')) {
            $data['password'] = $request->validated('password');
        }

        $request->user()->update($data);

        return back()->with('success', 'Profil berhasil diperbarui.');
    }
}
