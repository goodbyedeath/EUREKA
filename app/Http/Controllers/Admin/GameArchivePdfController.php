<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GameArchive;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * A finished session as a PDF: the report an operator hands to a client after the event.
 *
 * Built only from the archive snapshot, so it reads the same however long after the event it is
 * downloaded. Photos are opt-in (?photos=1): each is shrunk to a thumbnail before it is embedded, since
 * dompdf holds every image in memory and a 40-team event with full-size photos would not fit.
 */
class GameArchivePdfController extends Controller
{
    private const THUMB_PX = 320;

    public function __invoke(Request $request, GameArchive $archive)
    {
        $withPhotos = $request->boolean('photos');
        $s = $archive->snapshot ?? [];

        $images = [];
        if ($withPhotos) {
            $disk = Storage::disk('local');
            foreach (($s['photos'] ?? []) as $key => $path) {
                if (str_starts_with((string) $path, $archive->storage_dir.'/') && $disk->exists($path)) {
                    $images[$key] = $this->thumbnail($disk->get($path));
                }
            }
        }

        $pdf = Pdf::loadView('pdf.game-archive', [
            'archive' => $archive,
            's' => $s,
            'images' => array_filter($images),
            'withPhotos' => $withPhotos,
        ])->setPaper('a4', 'portrait')
            // Embed only the glyphs used. Without it the full DejaVu Sans goes in and a two-page
            // report weighs ~860 KB before a single photo.
            ->setOption('isFontSubsettingEnabled', true);

        $file = 'arsip-'.(Str::slug($archive->name) ?: 'sesi').'-'.$archive->archived_at?->format('Ymd').($withPhotos ? '-foto' : '').'.pdf';

        return $pdf->download($file);
    }

    /** A small JPEG data URI, or null if the bytes are not an image GD can read. */
    private function thumbnail(string $bytes): ?string
    {
        $src = @imagecreatefromstring($bytes);
        if (! $src) {
            return null;
        }

        [$w, $h] = [imagesx($src), imagesy($src)];
        $scale = min(1, self::THUMB_PX / max($w, $h));
        $tw = max(1, (int) round($w * $scale));
        $th = max(1, (int) round($h * $scale));

        $dst = imagecreatetruecolor($tw, $th);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));   // PNG transparency → white
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $tw, $th, $w, $h);

        ob_start();
        imagejpeg($dst, null, 72);
        $jpeg = ob_get_clean();
        imagedestroy($src);
        imagedestroy($dst);

        return 'data:image/jpeg;base64,'.base64_encode($jpeg);
    }
}
