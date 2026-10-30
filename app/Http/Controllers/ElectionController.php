<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SortsQueries;
use App\Http\Requests\SaveElectionRequest;
use App\Models\Election;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ElectionController extends Controller
{
    use SortsQueries;

    public function index(Request $request)
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();

        $elections = Election::query()
            ->withCount(['voters', 'candidates', 'votes'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('year', 'like', "%{$search}%");
                });
            })
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false));

        $sorting = $this->applySort(
            $elections,
            $request,
            ['name', 'year', 'starts_at', 'ends_at', 'voters_count', 'is_active'],
            'year',
        );

        $elections = $this->paginateQuery($elections, $request);

        return Inertia::render('Elections/Index', [
            'title' => 'Data Pemilihan',
            'subtitle' => 'Kelola periode dan jadwal pelaksanaan pemilihan',
            'elections' => $elections,
            'filters' => [
                'search' => $search,
                'status' => $status,
                ...$sorting,
            ],
        ]);
    }

    public function store(SaveElectionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = ! Election::query()->where('is_active', true)->exists();

        Election::create($data);

        return back()->with('success', 'Data pemilihan berhasil ditambahkan.');
    }

    public function update(SaveElectionRequest $request, Election $election): RedirectResponse
    {
        $election->update($request->validated());

        return back()->with('success', 'Data pemilihan berhasil diperbarui.');
    }

    public function destroy(Election $election): RedirectResponse
    {
        if ($election->is_active) {
            return back()->with('error', 'Pemilihan yang sedang aktif tidak dapat dihapus. Nonaktifkan pemilihan terlebih dahulu.');
        }

        $election->delete();

        return back()->with('success', 'Data pemilihan berhasil dihapus.');
    }

    public function activate(Election $election): RedirectResponse
    {
        DB::transaction(function () use ($election) {
            Election::query()->whereKeyNot($election->id)->update(['is_active' => false]);
            $election->update(['is_active' => true]);
        });

        return back()->with('success', "Pemilihan {$election->name} berhasil diaktifkan.");
    }

    public function deactivate(Election $election): RedirectResponse
    {
        $election->update(['is_active' => false]);

        return back()->with('success', "Pemilihan {$election->name} berhasil dinonaktifkan.");
    }
}
