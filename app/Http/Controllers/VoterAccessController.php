<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureVoterSession;
use App\Models\Election;
use App\Models\SchoolProfile;
use App\Models\Setting;
use App\Models\Vote;
use App\Models\Voter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Inertia\Response;

class VoterAccessController extends Controller
{
    private const MAX_ATTEMPTS = 10;

    private const MAX_ACCOUNT_ATTEMPTS = 5;

    public function create(Request $request): Response|RedirectResponse
    {
        if ($request->session()->has('voter_id')) {
            return redirect()->route('voter.ballot');
        }

        $election = Election::current();

        return Inertia::render('Voter/Login', [
            'school' => SchoolProfile::first()?->only(['name', 'logo_path']),
            'election' => $election?->only(['name', 'year']),
            'status' => $election?->setting?->status,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'nisn' => ['required', 'regex:/^(\d{4}|\d{10})$/'],
            'password' => ['required', 'string', 'max:50'],
        ], [
            'nisn.required' => 'NISN atau kode wajib diisi.',
            'nisn.regex' => 'NISN harus 10 digit, atau kode 4 digit untuk non-siswa.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $throttleKey = 'voter-login:'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors(['nisn' => "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik."]);
        }

        $accountKey = 'voter-login-account:'.$credentials['nisn'];

        if (RateLimiter::tooManyAttempts($accountKey, self::MAX_ACCOUNT_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($accountKey);

            return back()->withErrors(['nisn' => "Terlalu banyak percobaan untuk akun ini. Coba lagi dalam {$seconds} detik."]);
        }

        $election = Election::current();
        $voter = $election?->voters()->where('nisn', $credentials['nisn'])->first();

        if (! $voter || ! hash_equals((string) $voter->password, $credentials['password'])) {
            RateLimiter::hit($throttleKey, 60);
            RateLimiter::hit($accountKey, 300);

            return back()->withErrors(['nisn' => 'NISN/kode atau kata sandi tidak sesuai.'])->onlyInput('nisn');
        }

        if ($message = $this->blockedReason($voter, $election->setting?->status)) {
            return back()->withErrors(['nisn' => $message])->onlyInput('nisn');
        }

        RateLimiter::clear($throttleKey);
        RateLimiter::clear($accountKey);

        $request->session()->regenerate();
        $request->session()->put([
            'voter_id' => $voter->id,
            'voter_last_activity' => now()->timestamp,
        ]);

        return redirect()->route('voter.ballot');
    }

    public function ballot(Request $request): Response|RedirectResponse
    {
        $voter = Voter::find($request->session()->get('voter_id'));
        $election = $voter?->election;

        if (! $voter || ! $election?->is_active) {
            return $this->endSession($request, 'Sesi Anda tidak valid. Silakan masuk kembali.');
        }

        if ($message = $this->blockedReason($voter, $election->setting?->status)) {
            return $this->endSession($request, $message);
        }

        return Inertia::render('Voter/Ballot', [
            'voter' => $voter->only(['name', 'grade']),
            'election' => $election->only(['name', 'year']),
            'school' => SchoolProfile::first()?->only(['name', 'logo_path']),
            'idleSeconds' => EnsureVoterSession::IDLE_SECONDS,
            'candidates' => $election->candidates()
                ->orderBy('order_number')
                ->get(['id', 'order_number', 'chairman_name', 'vice_chairman_name', 'vision', 'mission', 'photo_path']),
        ]);
    }

    public function vote(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'candidate_id' => ['required', 'uuid'],
        ]);

        $voter = Voter::find($request->session()->get('voter_id'));
        $election = $voter?->election;

        if (! $voter || ! $election?->is_active) {
            return $this->endSession($request, 'Sesi Anda tidak valid. Silakan masuk kembali.');
        }

        $candidate = $election->candidates()->whereKey($data['candidate_id'])->first();

        if (! $candidate) {
            return back()->with('error', 'Pasangan calon tidak ditemukan.');
        }

        $message = DB::transaction(function () use ($voter, $election, $candidate) {
            $fresh = Voter::query()->lockForUpdate()->find($voter->id);

            if ($message = $this->blockedReason($fresh, $election->setting?->status)) {
                return $message;
            }

            Vote::create([
                'election_id' => $election->id,
                'voter_id' => $fresh->id,
                'candidate_id' => $candidate->id,
                'voted_at' => now(),
            ]);

            $fresh->forceFill(['has_voted' => true])->saveQuietly();

            return null;
        });

        if ($message) {
            return $this->endSession($request, $message);
        }

        $request->session()->forget(['voter_id', 'voter_last_activity']);
        $request->session()->put('voter_thanks', $voter->name);

        return redirect()->route('voter.thanks');
    }

    public function thanks(Request $request): Response|RedirectResponse
    {
        $name = $request->session()->pull('voter_thanks');

        if (! $name) {
            return redirect()->route('voter.login');
        }

        return Inertia::render('Voter/Thanks', [
            'name' => $name,
            'school' => SchoolProfile::first()?->only(['name', 'logo_path']),
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        return $this->endSession($request, $request->boolean('expired') ? 'Waktu habis. Silakan masuk kembali.' : null);
    }

    private function endSession(Request $request, ?string $message): RedirectResponse
    {
        $request->session()->forget(['voter_id', 'voter_last_activity']);

        $redirect = redirect()->route('voter.login');

        return $message ? $redirect->with('error', $message) : $redirect;
    }

    private function blockedReason(Voter $voter, ?string $status): ?string
    {
        if (! $voter->is_active) {
            return 'Akun pemilih tidak aktif. Hubungi panitia pemilihan.';
        }

        if ($voter->has_voted) {
            return 'Anda sudah menggunakan hak suara dan tidak dapat masuk kembali.';
        }

        if ($status === Setting::STATUS_ONGOING && ! $voter->election?->setting?->voting_enabled) {
            return 'Pemungutan suara sedang dinonaktifkan oleh panitia.';
        }

        return match ($status) {
            Setting::STATUS_ONGOING => null,
            Setting::STATUS_PAUSED => 'Pemilihan sedang dijeda. Silakan coba lagi nanti.',
            'closed', Setting::STATUS_FINAL => 'Pemilihan sudah ditutup.',
            default => 'Pemilihan belum dimulai.',
        };
    }
}
