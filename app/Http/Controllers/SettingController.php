<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingRequest;
use App\Models\Election;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class SettingController extends Controller
{
    public function edit()
    {
        $election = Election::current();

        return Inertia::render('Settings/Edit', [
            'title' => 'Pengaturan',
            'subtitle' => 'Atur status dan akses voting pada pemilihan aktif',
            'election' => $election,
            'setting' => $election ? $this->settingFor($election) : null,
        ]);
    }

    public function update(UpdateSettingRequest $request): RedirectResponse
    {
        $election = Election::current();

        if (! $election) {
            return back()->with('error', 'Buat data pemilihan terlebih dahulu sebelum mengatur pengaturan.');
        }

        $setting = $this->settingFor($election);

        $data = $request->validated();

        if ($setting->isFinal() && $data['status'] === Setting::STATUS_FINAL) {
            return back()->with('error', 'Pemilihan sudah final dan dikunci. Ubah status untuk membuka kunci.');
        }

        $setting->update($data);

        return back()->with('success', 'Pengaturan pemilihan berhasil disimpan.');
    }

    private function settingFor(Election $election): Setting
    {
        return Setting::firstOrCreate(
            ['election_id' => $election->id],
            [
                'status' => Setting::STATUS_NOT_STARTED,
                'voting_enabled' => false,
            ],
        );
    }
}
