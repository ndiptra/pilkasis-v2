<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportVoterRequest;
use App\Models\Election;
use App\Models\Voter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class VoterTransferController extends Controller
{
    public function template(): BinaryFileResponse
    {
        return response()->download(resource_path('templates/template_pemilih.xlsx'), 'template_pemilih.xlsx');
    }

    public function export(Request $request): StreamedResponse
    {
        $election = Election::query()->find($request->string('election')->toString())
            ?? Election::query()->where('is_active', true)->first();
        abort_unless($election, 404);

        $voters = Voter::query()
            ->where('election_id', $election->id)
            ->orderBy('grade')
            ->orderBy('name')
            ->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Pemilih');
        $sheet->fromArray(['#', 'NISN', 'Nama', 'Kelas', 'Kata Sandi', 'Status', 'Memilih'], null, 'A1');

        foreach ($voters as $index => $voter) {
            $row = $index + 2;
            $sheet->setCellValue("A{$row}", $index + 1);
            $sheet->setCellValueExplicit("B{$row}", $voter->nisn, 's');
            $sheet->setCellValue("C{$row}", $voter->name);
            $sheet->setCellValue("D{$row}", $voter->grade);
            $sheet->setCellValueExplicit("E{$row}", $voter->password, 's');
            $sheet->setCellValue("F{$row}", $voter->is_active ? 'Aktif' : 'Tidak aktif');
            $sheet->setCellValue("G{$row}", $voter->has_voted ? 'Sudah' : 'Belum');
        }

        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        $sheet->getStyle('A1:G1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        foreach (range('A', 'G') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = 'data_pemilih_'.str($election->name.' '.$election->year)->slug('_').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function import(ImportVoterRequest $request): RedirectResponse
    {
        if (Election::query()->find($request->validated('election_id'))?->isFinal()) {
            return back()->with('error', 'Pemilihan sudah final dan dikunci. Data pemilih tidak dapat diubah.');
        }

        try {
            $rows = IOFactory::load($request->file('file')->getRealPath())
                ->getActiveSheet()
                ->toArray(null, true, true, false);
        } catch (Throwable) {
            return back()->with('error', 'Berkas tidak dapat dibaca. Gunakan template impor yang tersedia.');
        }

        $header = array_map(fn ($value) => strtolower(trim((string) $value)), $rows[0] ?? []);

        if (array_slice($header, 1, 3) !== ['nisn', 'nama', 'kelas']) {
            return back()->with('error', 'Format berkas tidak sesuai. Kolom harus berurutan: No, NISN, Nama, Kelas.');
        }

        $electionId = $request->validated('election_id');
        $existing = Voter::query()->where('election_id', $electionId)->pluck('nisn')->flip();
        $seen = [];
        $valid = [];
        $errors = [];

        foreach (array_slice($rows, 1) as $index => $row) {
            $line = $index + 2;
            $nisn = trim((string) ($row[1] ?? ''));
            $name = trim((string) ($row[2] ?? ''));
            $grade = trim((string) ($row[3] ?? ''));

            if ($nisn === '' && $name === '' && $grade === '') {
                continue;
            }

            if (ctype_digit($nisn) && strlen($nisn) > 4 && strlen($nisn) < 10) {
                $nisn = str_pad($nisn, 10, '0', STR_PAD_LEFT);
            }

            $validator = Validator::make(
                ['nisn' => $nisn, 'name' => $name, 'grade' => $grade],
                ['nisn' => ['required', 'regex:/^(\d{4}|\d{10})$/'], 'name' => ['required', 'string', 'max:255'], 'grade' => ['required', 'string', 'max:50']],
            );

            if ($validator->fails()) {
                $errors[] = "Baris {$line}: ".$validator->errors()->first();

                continue;
            }

            if ($existing->has($nisn) || isset($seen[$nisn])) {
                $errors[] = "Baris {$line}: NISN {$nisn} sudah digunakan.";

                continue;
            }

            $seen[$nisn] = true;
            $valid[] = ['nisn' => $nisn, 'name' => $name, 'grade' => $grade];
        }

        if ($errors !== []) {
            $shown = implode(' ', array_slice($errors, 0, 5));
            $more = count($errors) > 5 ? ' (+'.(count($errors) - 5).' kesalahan lainnya)' : '';

            return back()->with('error', "Impor dibatalkan. {$shown}{$more}");
        }

        if ($valid === []) {
            return back()->with('error', 'Tidak ada data pemilih pada berkas.');
        }

        DB::transaction(function () use ($valid, $electionId) {
            foreach ($valid as $data) {
                Voter::create($data + ['election_id' => $electionId, 'is_active' => true]);
            }
        });

        return back()->with('success', count($valid).' data pemilih berhasil diimpor.');
    }
}
