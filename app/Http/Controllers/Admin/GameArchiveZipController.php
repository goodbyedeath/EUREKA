<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GameArchive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Every archived session in one ZIP — the whole event, end to end (operator, 26 Sep).
 *
 * A batch is archived at the end of each run, and after seven of them the operator had seven pages to
 * visit and no way to hand the client one file. This builds it: one folder per batch with the snapshot,
 * spreadsheets a client can open, the printable report, and the photographs; plus two sheets at the top
 * that put every batch side by side.
 *
 * Built from the snapshot, never from the live tables, so a download a month later reads exactly as the
 * event left it. Photos are copied without re-compressing (they are already JPEG) and nothing is held in
 * memory but the CSV text — a 76 MB archive set must not become a 76 MB allocation on a shared host.
 */
class GameArchiveZipController extends Controller
{
    public function __invoke(Request $request, ?GameArchive $archive = null)
    {
        // Ids only: a snapshot is a few hundred KB of JSON and seven of them at once is a quarter of the
        // web process's memory for nothing.
        $ids = $archive
            ? collect([$archive->id])
            : GameArchive::orderBy('archived_at')->pluck('id');

        if ($ids->isEmpty()) {
            return back()->with('error', 'Belum ada arsip untuk diunduh.');
        }

        $withPhotos = ! $request->boolean('no-photos');
        $disk = Storage::disk('local');

        // A real file, because ZipArchive cannot write to a stream. Deleted after it is sent.
        $tmpDir = storage_path('app/private/tmp');
        if (! is_dir($tmpDir)) {
            @mkdir($tmpDir, 0755, true);
        }
        $tmp = $tmpDir.'/arsip-'.Str::lower(Str::random(8)).'.zip';

        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Tidak bisa membuat file ZIP.');
        }

        // Deflating JPEGs wastes seconds and saves nothing; text compresses well.
        $store = fn (string $name) => $zip->setCompressionName($name, ZipArchive::CM_STORE);

        $all = [];
        $corrections = [];
        $lines = [];

        foreach ($ids->values() as $i => $id) {
            $a = GameArchive::find($id);
            if (! $a) {
                continue;
            }
            $s = $a->snapshot ?? [];
            $folder = sprintf('%02d-%s', $i + 1, Str::slug($a->name) ?: 'sesi-'.$a->id);

            $zip->addFromString("$folder/snapshot.json", json_encode($s, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            $zip->addFromString("$folder/klasemen.csv", $this->leaderboardCsv($s));
            $zip->addFromString("$folder/per-pos.csv", $this->stationsCsv($s));
            $zip->addFromString("$folder/anggota.csv", $this->membersCsv($s));

            // The same report the PDF button prints, as a self-contained page: no dompdf, so seven of
            // them cost milliseconds instead of seconds.
            $zip->addFromString("$folder/laporan.html", view('pdf.game-archive', [
                'archive' => $a,
                's' => $s,
                'images' => [],
                'withPhotos' => false,
                'num' => fn ($n) => number_format((float) $n),
                'signed' => fn ($n) => ($n > 0 ? '+' : '').number_format((float) $n),
            ])->render());

            if ($withPhotos) {
                foreach (($s['photos'] ?? []) as $key => $path) {
                    // Only inside this archive's own directory: a snapshot is data, and data does not
                    // get to name a path outside the folder the archive owns.
                    if (! str_starts_with((string) $path, $a->storage_dir.'/') || ! $disk->exists($path)) {
                        continue;
                    }
                    $name = "$folder/foto/".basename($path);
                    $zip->addFile($disk->path($path), $name);
                    $store($name);
                }
            }

            foreach (($s['leaderboard'] ?? []) as $row) {
                $all[] = [$a->name, $a->archived_at?->format('Y-m-d H:i'), $row['rank'] ?? '', $row['name'] ?? '',
                    $row['total'] ?? 0, $row['base_points'] ?? 0, $row['earned_points'] ?? 0,
                    $row['assessment_points'] ?? 0, $row['object_points'] ?? 0, $row['manual_points'] ?? 0,
                    ($row['stations_done'] ?? 0).'/'.($row['stations_total'] ?? 0),
                    $row['elapsed'] ?? '', ($row['race_finished'] ?? false) ? 'selesai' : 'belum'];
            }

            foreach (($s['manual_corrections'] ?? []) as $c) {
                $corrections[] = [$a->name, $c['at'] ?? '', $c['team'] ?? '', $c['points'] ?? 0, $c['reason'] ?? '', $c['by'] ?? ''];
            }

            $lines[] = sprintf('  %02d. %-34s %s — %d tim, %d akun, pemenang %s (%s)',
                $i + 1, $a->name, $a->archived_at?->format('d M Y H:i') ?? '-',
                $a->team_count, $a->account_count, $a->winner_name ?: '-', number_format((float) $a->winner_score));

            unset($s, $a);
        }

        $zip->addFromString('semua-batch.csv', $this->csv(
            ['Batch', 'Diarsipkan', 'Peringkat', 'Tim', 'Total', 'Awal', 'Kuis', 'Game', 'Objek', 'Koreksi', 'Pos selesai', 'Waktu', 'Status'],
            $all,
        ));
        $zip->addFromString('koreksi-manual.csv', $this->csv(
            ['Batch', 'Waktu', 'Tim', 'Poin', 'Alasan', 'Oleh'],
            $corrections,
        ));
        $zip->addFromString('README.txt', $this->readme($lines, $withPhotos));

        $zip->close();

        $file = $archive
            ? 'arsip-'.(Str::slug($archive->name) ?: 'sesi').'-'.$archive->archived_at?->format('Ymd').'.zip'
            : 'questerra-semua-arsip-'.now()->format('Ymd-Hi').'.zip';

        return response()->download($tmp, $file)->deleteFileAfterSend(true);
    }

    /** @param list<array<int, string|int|float>> $rows */
    private function csv(array $header, array $rows): string
    {
        // Excel on a Windows machine reads a BOM-less UTF-8 CSV as Latin-1 and turns every "—" into
        // mojibake; the client opens these in Excel.
        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $header);
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        rewind($out);

        return (string) stream_get_contents($out);
    }

    private function leaderboardCsv(array $s): string
    {
        $rows = [];
        foreach (($s['leaderboard'] ?? []) as $r) {
            $rows[] = [$r['rank'] ?? '', $r['name'] ?? '', $r['total'] ?? 0, $r['base_points'] ?? 0,
                $r['earned_points'] ?? 0, $r['assessment_points'] ?? 0, $r['object_points'] ?? 0,
                $r['manual_points'] ?? 0, ($r['stations_done'] ?? 0).'/'.($r['stations_total'] ?? 0),
                $r['elapsed'] ?? '', ($r['race_finished'] ?? false) ? 'selesai' : 'belum'];
        }

        return $this->csv(['Peringkat', 'Tim', 'Total', 'Awal', 'Kuis', 'Game', 'Objek', 'Koreksi', 'Pos selesai', 'Waktu', 'Status'], $rows);
    }

    private function stationsCsv(array $s): string
    {
        $rows = [];
        foreach (($s['teams'] ?? []) as $t) {
            foreach (($t['stations'] ?? []) as $st) {
                foreach (($st['attempts'] ?? [[]]) as $at) {
                    $rows[] = [
                        $t['name'] ?? '', $st['title'] ?? '', ($st['counted'] ?? false) ? 'dihitung' : 'bonus',
                        ($st['completed'] ?? false) ? 'selesai' : 'belum',
                        $at['started_at'] ?? '', $at['completed_at'] ?? '',
                        $at['total_time_seconds'] ?? '', $at['points'] ?? '',
                        $at['earned_points'] ?? '', $at['assessment_points'] ?? '',
                    ];
                }
            }
        }

        return $this->csv(['Tim', 'Pos', 'Jenis', 'Status', 'Mulai', 'Selesai', 'Durasi (detik)', 'Poin', 'Poin kuis', 'Poin game'], $rows);
    }

    private function membersCsv(array $s): string
    {
        $rows = [];
        foreach (($s['teams'] ?? []) as $t) {
            foreach (($t['members'] ?? []) as $m) {
                $rows[] = [$t['name'] ?? '', $m['name'] ?? '', $m['email'] ?? '', $m['phone'] ?? '',
                    $m['position'] ?? '', ($m['is_leader'] ?? false) ? 'ketua' : ''];
            }
        }

        return $this->csv(['Tim', 'Nama', 'E-mail', 'Telepon', 'Posisi', 'Peran'], $rows);
    }

    /** @param list<string> $batches one line per batch, built while the ZIP was filled */
    private function readme(array $batches, bool $withPhotos): string
    {
        $lines = [
            'QUESTERRA — arsip sesi permainan',
            'Dibuat '.now()->format('d M Y H:i').' (waktu server, UTC).',
            '',
            'Isi:',
            '  semua-batch.csv      setiap tim dari setiap batch dalam satu lembar, urut sesuai peringkat resminya.',
            '  koreksi-manual.csv   semua koreksi poin manual kru: kapan, tim mana, berapa, alasannya, oleh siapa.',
            '  NN-nama-batch/       satu folder per batch:',
            '      laporan.html     laporan lengkap batch itu, bisa dibuka di browser dan dicetak.',
            '      klasemen.csv     peringkat akhir dengan rincian poin dan waktu.',
            '      per-pos.csv      setiap percobaan di setiap pos: mulai, selesai, durasi, poin.',
            '      anggota.csv      anggota tiap tim.',
            '      snapshot.json    data mentah lengkap, apa adanya seperti saat diarsipkan.',
            $withPhotos
                ? '      foto/            foto verifikasi peserta dan foto penilaian fasilitator.'
                : '      (foto tidak disertakan pada unduhan ini)',
            '',
            'Aturan skor yang dipakai:',
            '  total = poin awal + poin kuis + poin game (nilai fasilitator − penalti) + poin objek 3D',
            '          + koreksi manual kru.',
            '  Kalau dua tim skornya sama, yang lebih cepat menyelesaikan semuanya menang; tim yang belum',
            '  selesai berada di bawah tim yang sudah, seberapa pun cepat jamnya.',
            '',
            'Batch di dalam file ini:',
        ];

        return implode("\n", array_merge($lines, $batches))."\n";
    }
}
