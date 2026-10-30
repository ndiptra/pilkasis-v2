<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Election;
use Inertia\Inertia;

class ResultController extends Controller
{
    public function index()
    {
        $election = Election::current();

        $candidates = $election
            ? $election->candidates()->withCount('votes')->orderBy('order_number')->get()
            : collect();

        $totalVotes = $election?->votes()->count() ?? 0;
        $totalVoters = $election?->voters()->count() ?? 0;

        $results = $candidates->map(fn (Candidate $candidate) => [
            'id' => $candidate->id,
            'order_number' => $candidate->order_number,
            'chairman_name' => $candidate->chairman_name,
            'vice_chairman_name' => $candidate->vice_chairman_name,
            'photo_path' => $candidate->photo_path,
            'votes' => $candidate->votes_count,
            'percentage' => $totalVotes > 0
                ? round(($candidate->votes_count / $totalVotes) * 100, 1)
                : 0,
        ]);

        return Inertia::render('Results/Index', [
            'title' => 'Hasil',
            'subtitle' => 'Rekapitulasi perolehan suara',
            'election' => $election,
            'results' => $results,
            'summary' => [
                'total_votes' => $totalVotes,
                'total_voters' => $totalVoters,
                'participation' => $totalVoters > 0 ? round(($totalVotes / $totalVoters) * 100, 1) : 0,
            ],
            'chart' => [
                'labels' => $candidates->map(fn (Candidate $candidate) => 'Paslon '.$candidate->order_number)->values(),
                'data' => $candidates->pluck('votes_count')->values(),
            ],
        ]);
    }
}
