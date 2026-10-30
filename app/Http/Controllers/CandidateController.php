<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCandidateRequest;
use App\Http\Requests\UpdateCandidateRequest;
use App\Models\Candidate;
use App\Models\Election;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CandidateController extends Controller
{
    public function index()
    {
        $election = Election::current();

        return Inertia::render('Candidates/Index', [
            'title' => 'Data Paslon',
            'subtitle' => 'Kelola pasangan calon pada pemilihan',
            'election' => $election,
            'candidates' => $election
                ? $election->candidates()->withCount('votes')->orderBy('order_number')->get()
                : [],
        ]);
    }

    public function store(StoreCandidateRequest $request): RedirectResponse
    {
        $election = Election::current();

        if (! $election) {
            return back()->with('error', 'Buat data pemilihan terlebih dahulu sebelum menambahkan paslon.');
        }

        if ($election->isFinal()) {
            return $this->lockedResponse();
        }

        $data = $request->safe()->except('photo');
        $data['election_id'] = $election->id;

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $this->storePhoto($request->file('photo'));
        }

        Candidate::create($data);

        return back()->with('success', 'Data paslon berhasil ditambahkan.');
    }

    public function update(UpdateCandidateRequest $request, Candidate $candidate): RedirectResponse
    {
        $this->ensureBelongsToCurrentElection($candidate);

        if ($candidate->election->isFinal()) {
            return $this->lockedResponse();
        }

        $data = $request->safe()->except('photo');

        if ($request->hasFile('photo')) {
            if ($candidate->photo_path) {
                Storage::disk('public')->delete($candidate->photo_path);
            }

            $data['photo_path'] = $this->storePhoto($request->file('photo'));
        }

        $candidate->update($data);

        return back()->with('success', 'Data paslon berhasil diperbarui.');
    }

    public function destroy(Candidate $candidate): RedirectResponse
    {
        $this->ensureBelongsToCurrentElection($candidate);

        if ($candidate->election->isFinal()) {
            return $this->lockedResponse();
        }

        if ($candidate->photo_path) {
            Storage::disk('public')->delete($candidate->photo_path);
        }

        $candidate->delete();

        return back()->with('success', 'Data paslon berhasil dihapus.');
    }

    /**
     * Crops the upload to the 4:3 candidate photo size (1200x900) and stores it as JPEG.
     */
    private function storePhoto(UploadedFile $file): string
    {
        $width = 1200;
        $height = 900;
        $source = imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if ($source === false) {
            return $file->store('candidates', 'public');
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $scale = max($width / $sourceWidth, $height / $sourceHeight);
        $cropWidth = (int) round($width / $scale);
        $cropHeight = (int) round($height / $scale);

        $canvas = imagecreatetruecolor($width, $height);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled(
            $canvas,
            $source,
            0,
            0,
            intdiv($sourceWidth - $cropWidth, 2),
            intdiv($sourceHeight - $cropHeight, 2),
            $width,
            $height,
            $cropWidth,
            $cropHeight,
        );

        ob_start();
        imagejpeg($canvas, null, 85);
        $contents = (string) ob_get_clean();

        $path = 'candidates/'.Str::random(40).'.jpg';
        Storage::disk('public')->put($path, $contents);

        return $path;
    }

    private function lockedResponse(): RedirectResponse
    {
        return back()->with('error', 'Pemilihan sudah final dan dikunci. Data paslon tidak dapat diubah.');
    }

    private function ensureBelongsToCurrentElection(Candidate $candidate): void
    {
        abort_unless($candidate->election_id === Election::current()?->id, 404);
    }
}
