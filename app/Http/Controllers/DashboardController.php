<?php

namespace App\Http\Controllers;

use App\Models\Election;
use App\Models\SchoolProfile;
use App\Models\Voter;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $election = Election::current();
        $showResults = $request->user()->canViewResults();

        $totalVoters = $election?->voters()->count() ?? 0;
        $totalVotes = $election?->votes()->count() ?? 0;
        $participation = $totalVoters > 0 ? round(($totalVotes / $totalVoters) * 100, 1) : 0;

        return Inertia::render('Dashboard', [
            'title' => 'Dasbor',
            'subtitle' => 'Ringkasan pelaksanaan pemilihan',
            'election' => $election,
            'stats' => [
                'total_voters' => $totalVoters,
                'active_voters' => $election?->voters()->where('is_active', true)->count() ?? 0,
                'total_candidates' => $election?->candidates()->count() ?? 0,
                'total_votes' => $showResults ? $totalVotes : null,
                'participation' => $showResults ? $participation : null,
            ],
            'setting' => $election?->setting,
            'school' => SchoolProfile::first(),
            'candidates' => $election
                ? ($showResults
                    ? $election->candidates()->withCount('votes')->orderBy('order_number')->get()
                    : $election->candidates()->orderBy('order_number')->get())
                : [],
            'recentVoters' => $election
                ? Voter::where('election_id', $election->id)
                    ->latest()
                    ->limit(5)
                    ->get($showResults
                        ? ['id', 'name', 'nisn', 'grade', 'has_voted', 'created_at']
                        : ['id', 'name', 'nisn', 'grade', 'created_at'])
                : [],
        ]);
    }
}
