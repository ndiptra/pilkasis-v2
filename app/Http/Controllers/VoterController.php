<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SortsQueries;
use App\Http\Requests\StoreVoterRequest;
use App\Http\Requests\UpdateVoterRequest;
use App\Models\Election;
use App\Models\Voter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VoterController extends Controller
{
    use SortsQueries;

    public function index(Request $request)
    {
        $elections = Election::query()
            ->orderByDesc('year')
            ->orderByDesc('starts_at')
            ->get(['id', 'name', 'year', 'is_active']);
        $election = $elections->firstWhere('id', $request->string('election')->toString())
            ?? $elections->firstWhere('is_active', true);
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();

        $voters = Voter::query()
            ->where('election_id', $election?->id ?? 0)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('nisn', 'like', "%{$search}%")
                        ->orWhere('grade', 'like', "%{$search}%");
                });
            })
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($status === 'voted', fn ($query) => $query->where('has_voted', true))
            ->when($status === 'not_voted', fn ($query) => $query->where('has_voted', false));
        $sorting = $this->applySort(
            $voters,
            $request,
            ['nisn', 'name', 'grade', 'password', 'is_active', 'has_voted'],
            'created_at',
        );

        $voters = $this->paginateQuery($voters, $request);

        return Inertia::render('Voters/Index', [
            'title' => 'Data Pemilih',
            'subtitle' => 'Kelola daftar pemilih pada pemilihan',
            'voters' => $voters,
            'elections' => $elections,
            'election' => $election,
            'filters' => [
                'election' => $election?->id,
                'search' => $search,
                'status' => $status,
                ...$sorting,
            ],
        ]);
    }

    public function store(StoreVoterRequest $request): RedirectResponse
    {
        if ($locked = $this->lockedResponse($request->validated('election_id'))) {
            return $locked;
        }

        Voter::create($request->validated());

        return back()->with('success', 'Data pemilih berhasil ditambahkan.');
    }

    public function update(UpdateVoterRequest $request, Voter $voter): RedirectResponse
    {
        if ($locked = $this->lockedResponse($voter->election_id)) {
            return $locked;
        }

        $voter->update($request->validated());

        return back()->with('success', 'Data pemilih berhasil diperbarui.');
    }

    public function destroy(Voter $voter): RedirectResponse
    {
        if ($locked = $this->lockedResponse($voter->election_id)) {
            return $locked;
        }

        $voter->delete();

        return back()->with('success', 'Data pemilih berhasil dihapus.');
    }

    private function lockedResponse(?string $electionId): ?RedirectResponse
    {
        if (Election::query()->find($electionId)?->isFinal()) {
            return back()->with('error', 'Pemilihan sudah final dan dikunci. Data pemilih tidak dapat diubah.');
        }

        return null;
    }
}
