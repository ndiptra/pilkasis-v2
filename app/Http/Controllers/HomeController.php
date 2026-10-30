<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Election;
use App\Models\SchoolProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function __invoke(Request $request, DashboardController $dashboard): mixed
    {
        if ($request->user()) {
            return $dashboard->index($request);
        }

        $election = Election::current();

        if (! $election?->setting?->results_public) {
            return redirect()->route('voter.login');
        }

        $candidates = $election->candidates()
            ->withCount('votes')
            ->orderBy('order_number')
            ->get();

        $totalVotes = $election->votes()->count();
        $totalVoters = $election->voters()->count();

        return Inertia::render('PublicResults', [
            'school' => SchoolProfile::first()?->only(['name', 'address', 'logo_path']),
            'election' => $election->only(['name', 'year', 'starts_at', 'ends_at']),
            'status' => $election->setting->status,
            'summary' => [
                'total_votes' => $totalVotes,
                'total_voters' => $totalVoters,
                'participation' => $totalVoters > 0 ? round(($totalVotes / $totalVoters) * 100, 1) : 0,
            ],
            'results' => $candidates->map(fn (Candidate $candidate) => [
                'id' => $candidate->id,
                'order_number' => $candidate->order_number,
                'chairman_name' => $candidate->chairman_name,
                'vice_chairman_name' => $candidate->vice_chairman_name,
                'photo_path' => $candidate->photo_path,
                'votes' => $candidate->votes_count,
                'percentage' => $totalVotes > 0 ? round(($candidate->votes_count / $totalVotes) * 100, 1) : 0,
            ])->values(),
        ]);
    }
}
