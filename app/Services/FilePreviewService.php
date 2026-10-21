<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Ticket;
use App\Models\TicketFile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FilePreviewService
{
    protected SecureUploadService $uploadService;

    public function __construct(SecureUploadService $uploadService)
    {
        $this->uploadService = $uploadService;
    }

    /**
     * Hasilkan atau dapatkan turunan (derivative) aman untuk pratinjau berkas
     */
    public function getOrCreatePreview(TicketFile $file, ?User $viewer = null): array
    {
        $disk = config('files.previews_disk', 'previews');
        $baseFolder = "previews/{$file->ticket_id}";
        $previewPath = "{$baseFolder}/{$file->id}_preview.jpg";
        $thumbPath = "{$baseFolder}/{$file->id}_thumb.jpg";
        $metaPath = "{$baseFolder}/{$file->id}_meta.json";

        // Jika turunan sudah ada, kembalikan langsung
        if (Storage::disk($disk)->exists($metaPath) && Storage::disk($disk)->exists($previewPath)) {
            $meta = json_decode(Storage::disk($disk)->get($metaPath), true) ?: [];
            return array_merge([
                'status'       => 'ready',
                'preview_path' => $previewPath,
                'thumb_path'   => Storage::disk($disk)->exists($thumbPath) ? $thumbPath : $previewPath,
            ], $meta);
        }

        // Buat turunan baru
        return $this->generatePreview($file, $viewer);
    }

    /**
     * Pembuatan Turunan (Derivative Generator)
     */
    public function generatePreview(TicketFile $file, ?User $viewer = null): array
    {
        $disk = config('files.previews_disk', 'previews');
        $sourceDisk = $file->isVault() ? config('files.vault_disk', 'vault') : config('files.default_disk', 'private');
        $sourcePath = $file->vault_path ?: $file->path;

        if (!Storage::disk($sourceDisk)->exists($sourcePath)) {
            return [
                'status'  => 'failed',
                'message' => 'Berkas sumber fisik tidak ditemukan.',
                'type'    => 'unknown',
            ];
        }

        // Baca isi berkas asli (dekripsi jika di vault)
        $raw = Storage::disk($sourceDisk)->get($sourcePath);
        if ($file->is_encrypted) {
            $raw = $this->uploadService->decryptPayload($raw);
        }

        $baseFolder = "previews/{$file->ticket_id}";
        Storage::disk($disk)->makeDirectory($baseFolder);

        $previewPath = "{$baseFolder}/{$file->id}_preview.jpg";
        $thumbPath = "{$baseFolder}/{$file->id}_thumb.jpg";
        $metaPath = "{$baseFolder}/{$file->id}_meta.json";

        $mime = strtolower($file->mime);
        $type = 'unknown';
        $pages = 1;

        if (str_starts_with($mime, 'image/')) {
            $type = 'image';
            $this->generateImageDerivative($raw, $disk, $previewPath, $thumbPath, $file->isConfidential(), $viewer);
        } elseif ($mime === 'application/pdf') {
            $type = 'pdf';
            $pages = $this->generatePdfDerivative($raw, $disk, $baseFolder, $file->id, $previewPath, $thumbPath, $file->isConfidential(), $viewer);
        } elseif (str_starts_with($mime, 'text/') || in_array($mime, ['application/json', 'application/xml', 'text/plain'], true)) {
            $type = 'text';
            $previewPath = "{$baseFolder}/{$file->id}_preview.txt";
            $this->generateTextDerivative($raw, $disk, $previewPath);
            $thumbPath = $previewPath;
        } elseif (str_starts_with($mime, 'video/')) {
            $type = 'video';
            $this->generateVideoDerivative($raw, $disk, $previewPath, $thumbPath);
        } else {
            return [
                'status'  => 'failed',
                'message' => 'Format berkas tidak mendukung pratinjau otomatis.',
                'type'    => $type,
            ];
        }

        $meta = [
            'status'       => 'ready',
            'type'         => $type,
            'preview_path' => $previewPath,
            'thumb_path'   => $thumbPath,
            'pages'        => $pages,
            'generated_at' => Carbon::now()->toIso8601String(),
        ];

        Storage::disk($disk)->put($metaPath, json_encode($meta));

        return $meta;
    }

    /**
     * Buat turunan gambar (pratinjau max 1600px + thumbnail max 300px + watermark jika rahasia)
     */
    protected function generateImageDerivative(
        string $raw,
        string $disk,
        string $previewPath,
        string $thumbPath,
        bool $isConfidential = false,
        ?User $viewer = null
    ): void {
        $img = @imagecreatefromstring($raw);
        if (!$img) {
            return;
        }

        $w = imagesx($img);
        $h = imagesy($img);

        // 1. Pratinjau Max 1600px
        $maxPreview = config('files.image.preview_max_dim', 1600);
        $previewImg = $this->resizeImageResource($img, $w, $h, $maxPreview);

        if ($isConfidential) {
            $this->applyWatermark($previewImg, $viewer);
        }

        ob_start();
        imagejpeg($previewImg, null, 82);
        $previewData = ob_get_clean();
        imagedestroy($previewImg);

        Storage::disk($disk)->put($previewPath, $previewData);

        // 2. Thumbnail Max 300px (Hanya jika bukan rahasia)
        if (!$isConfidential) {
            $maxThumb = config('files.image.thumb_max_dim', 300);
            $thumbImg = $this->resizeImageResource($img, $w, $h, $maxThumb);

            ob_start();
            imagejpeg($thumbImg, null, 75);
            $thumbData = ob_get_clean();
            imagedestroy($thumbImg);

            Storage::disk($disk)->put($thumbPath, $thumbData);
        }

        imagedestroy($img);
    }

    /**
     * Buat turunan PDF (Rasterisasi halaman ke gambar server-side, max 10 halaman)
     */
    protected function generatePdfDerivative(
        string $raw,
        string $disk,
        string $baseFolder,
        int $fileId,
        string $previewPath,
        string $thumbPath,
        bool $isConfidential = false,
        ?User $viewer = null
    ): int {
        $tmpPdf = tempnam(sys_get_temp_dir(), 'pdf_prev_');
        file_put_contents($tmpPdf, $raw);

        $maxPages = config('files.pdf_preview.max_pages', 10);
        $renderedPages = 0;

        // Coba rasterisasi via pdftoppm (poppler) jika tersedia
        $outputPrefix = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "pdf_page_{$fileId}";
        $cmd = "pdftoppm -jpeg -r 144 -f 1 -l {$maxPages} " . escapeshellarg($tmpPdf) . " " . escapeshellarg($outputPrefix);
        @exec($cmd, $out, $ret);

        if ($ret === 0) {
            $matchedFiles = glob("{$outputPrefix}-*.jpg");
            if (!empty($matchedFiles)) {
                sort($matchedFiles);
                foreach ($matchedFiles as $index => $pageFile) {
                    $pageNum = $index + 1;
                    $pageDest = "{$baseFolder}/{$fileId}_page_{$pageNum}.jpg";
                    $pageData = file_get_contents($pageFile);

                    if ($isConfidential) {
                        $pImg = @imagecreatefromstring($pageData);
                        if ($pImg) {
                            $this->applyWatermark($pImg, $viewer);
                            ob_start();
                            imagejpeg($pImg, null, 82);
                            $pageData = ob_get_clean();
                            imagedestroy($pImg);
                        }
                    }

                    Storage::disk($disk)->put($pageDest, $pageData);
                    if ($pageNum === 1) {
                        Storage::disk($disk)->put($previewPath, $pageData);
                        if (!$isConfidential) {
                            Storage::disk($disk)->put($thumbPath, $pageData);
                        }
                    }
                    @unlink($pageFile);
                    $renderedPages++;
                }
            }
        }

        // Fallback jika pdftoppm tidak tersedia: buat kartu representasi grafis halaman dokumen PDF
        if ($renderedPages === 0) {
            $fallbackImg = imagecreatetruecolor(800, 1100);
            $bg = imagecolorallocate($fallbackImg, 248, 250, 252);
            $border = imagecolorallocate($fallbackImg, 203, 213, 225);
            $textDark = imagecolorallocate($fallbackImg, 30, 41, 59);
            $textRed = imagecolorallocate($fallbackImg, 225, 29, 72);

            imagefilledrectangle($fallbackImg, 0, 0, 799, 1099, $bg);
            imagerectangle($fallbackImg, 0, 0, 799, 1099, $border);

            imagestring($fallbackImg, 5, 260, 480, "DOKUMEN PDF RESMI", $textDark);
            imagestring($fallbackImg, 4, 210, 520, "Sistem Ticketing DKIS Kota Cirebon", $textDark);
            imagestring($fallbackImg, 3, 240, 560, "Rasterisasi Server Berjalan", $textRed);

            if ($isConfidential) {
                $this->applyWatermark($fallbackImg, $viewer);
            }

            ob_start();
            imagejpeg($fallbackImg, null, 85);
            $data = ob_get_clean();
            imagedestroy($fallbackImg);

            Storage::disk($disk)->put($previewPath, $data);
            if (!$isConfidential) {
                Storage::disk($disk)->put($thumbPath, $data);
            }
            $renderedPages = 1;
        }

        @unlink($tmpPdf);
        return $renderedPages;
    }

    /**
     * Buat turunan teks / log (potong max 200 KB)
     */
    protected function generateTextDerivative(string $raw, string $disk, string $previewPath): void
    {
        $maxBytes = 200 * 1024; // 200 KB
        $truncated = false;

        if (strlen($raw) > $maxBytes) {
            $raw = substr($raw, 0, $maxBytes);
            $truncated = true;
        }

        // Escape aman karakter HTML
        $escaped = htmlspecialchars($raw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        if ($truncated) {
            $escaped .= "\n\n--- [DIPOTONG: Berkas teks melebihi 200 KB, silakan unduh berkas asli jika memiliki wewenang] ---";
        }

        Storage::disk($disk)->put($previewPath, $escaped);
    }

    /**
     * Buat turunan video poster
     */
    protected function generateVideoDerivative(string $raw, string $disk, string $previewPath, string $thumbPath): void
    {
        $tmpVid = tempnam(sys_get_temp_dir(), 'vid_');
        file_put_contents($tmpVid, $raw);

        $tmpPoster = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'poster_' . uniqid() . '.jpg';
        $cmd = "ffmpeg -ss 00:00:01 -i " . escapeshellarg($tmpVid) . " -vframes 1 -q:v 2 " . escapeshellarg($tmpPoster) . " 2>&1";
        @exec($cmd, $out, $ret);

        if ($ret === 0 && file_exists($tmpPoster)) {
            $posterData = file_get_contents($tmpPoster);
            Storage::disk($disk)->put($previewPath, $posterData);
            Storage::disk($disk)->put($thumbPath, $posterData);
            @unlink($tmpPoster);
        } else {
            // Fallback kartu video
            $fallback = imagecreatetruecolor(800, 450);
            $bg = imagecolorallocate($fallback, 15, 23, 42);
            $text = imagecolorallocate($fallback, 248, 250, 252);
            imagefilledrectangle($fallback, 0, 0, 799, 449, $bg);
            imagestring($fallback, 5, 280, 210, "VIDEO MP4 LAMPIRAN", $text);

            ob_start();
            imagejpeg($fallback, null, 80);
            $data = ob_get_clean();
            imagedestroy($fallback);

            Storage::disk($disk)->put($previewPath, $data);
            Storage::disk($disk)->put($thumbPath, $data);
        }

        @unlink($tmpVid);
    }

    /**
     * Resize resource GD gambar dengan mempertahankan rasio aspek
     */
    protected function resizeImageResource($img, int $w, int $h, int $maxDim)
    {
        if ($w <= $maxDim && $h <= $maxDim) {
            $dest = imagecreatetruecolor($w, $h);
            imagecopy($dest, $img, 0, 0, 0, 0, $w, $h);
            return $dest;
        }

        if ($w > $h) {
            $newW = $maxDim;
            $newH = (int) round(($h / $w) * $maxDim);
        } else {
            $newH = $maxDim;
            $newW = (int) round(($w / $h) * $maxDim);
        }

        $dest = imagecreatetruecolor($newW, $newH);
        imagecopyresampled($dest, $img, 0, 0, 0, 0, $newW, $newH, $w, $h);
        return $dest;
    }

    /**
     * Beri watermark tipis untuk pratinjau berkas berklasifikasi rahasia
     */
    protected function applyWatermark($img, ?User $viewer = null): void
    {
        $w = imagesx($img);
        $h = imagesy($img);

        $color = imagecolorallocatealpha($img, 220, 38, 38, 70); // Merah transparan
        $text = "RAHASIA / PERSANDIAN CSIRT - " . ($viewer?->nama ?: 'INTERNAL DKIS') . " - " . date('Y-m-d H:i');

        // Cetak diagonal beberapa watermark
        for ($y = 80; $y < $h; $y += 240) {
            imagestring($img, 5, 40, $y, $text, $color);
        }
    }

    /**
     * Sajikan Berkas Turunan secara Inline dengan Header Keamanan Ketat
     */
    public function servePreviewResponse(TicketFile $file, Request $request, ?User $viewer = null)
    {
        $previewData = $this->getOrCreatePreview($file, $viewer);

        if (($previewData['status'] ?? '') === 'failed') {
            abort(404, $previewData['message'] ?? 'Pratinjau tidak tersedia.');
        }

        $disk = config('files.previews_disk', 'previews');
        $page = (int) $request->input('page', 1);

        if ($previewData['type'] === 'pdf' && $page > 1) {
            $pagePath = "previews/{$file->ticket_id}/{$file->id}_page_{$page}.jpg";
            $targetPath = Storage::disk($disk)->exists($pagePath) ? $pagePath : $previewData['preview_path'];
        } else {
            $targetPath = $request->boolean('thumb') ? $previewData['thumb_path'] : $previewData['preview_path'];
        }

        if (!Storage::disk($disk)->exists($targetPath)) {
            abort(404, 'Berkas turunan pratinjau belum siap.');
        }

        // Catat Event Audit Log (file_previewed) dengan Debounce 5 Menit
        $this->logPreviewEvent($file, $viewer, $request);

        $ext = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
        $contentType = match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'webp'        => 'image/webp',
            'txt'         => 'text/plain; charset=UTF-8',
            default       => 'application/octet-stream',
        };

        $cacheControl = $file->isConfidential() ? 'no-store, no-cache, must-revalidate, private' : 'private, max-age=3600';

        return response(Storage::disk($disk)->get($targetPath), 200, [
            'Content-Type'              => $contentType,
            'Content-Disposition'       => 'inline; filename="' . basename($targetPath) . '"',
            'X-Content-Type-Options'    => 'nosniff',
            'Cache-Control'             => $cacheControl,
            'Content-Security-Policy'   => "default-src 'none'; style-src 'unsafe-inline';",
        ]);
    }

    /**
     * Pencatatan Event Audit Log Pratinjau dengan Debounce 5 Menit
     */
    protected function logPreviewEvent(TicketFile $file, ?User $viewer, Request $request): void
    {
        $userId = $viewer?->id ?? 'public';
        $cacheKey = "preview_log_{$file->id}_{$userId}";

        if (Cache::has($cacheKey)) {
            return;
        }

        Cache::put($cacheKey, true, now()->addMinutes(5));

        try {
            AuditLog::create([
                'user_id'     => $viewer?->id,
                'aksi'        => 'PRATINJAU_BERKAS',
                'target_kode' => $file->ticket?->kode_tiket,
                'ip_address'  => $request->ip() ?: '127.0.0.1',
                'detail'      => ($viewer ? "Petugas {$viewer->nama}" : "Pelapor terverifikasi") . " melihat pratinjau berkas #{$file->id} ({$file->original_name}) [Klasifikasi: {$file->classification}]",
                'timestamp'   => Carbon::now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning("Gagal mencatat audit log pratinjau: " . $e->getMessage());
        }
    }
}
