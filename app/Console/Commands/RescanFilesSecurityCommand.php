<?php

namespace App\Console\Commands;

use App\Models\TicketFile;
use App\Services\SecureUploadService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class RescanFilesSecurityCommand extends Command
{
    protected $signature = 'files:rescan-security {--ticket= : Filter kode tiket spesifik}';
    protected $description = 'Pindai ulang seluruh berkas lampiran yang tersimpan terhadap aturan keamanan baru (hanya lapor, tidak menghapus)';

    public function handle(SecureUploadService $uploadService): int
    {
        $this->info('Memulai pemindaian keamanan berkas sistem ticketing...');

        $query = TicketFile::with('ticket');
        if ($ticketCode = $this->option('ticket')) {
            $query->whereHas('ticket', fn($q) => $q->where('kode_tiket', $ticketCode));
        }

        $files = $query->get();
        if ($files->isEmpty()) {
            $this->info('Tidak ada berkas yang ditemukan untuk dipindai.');
            return 0;
        }

        $total = $files->count();
        $passed = 0;
        $flagged = 0;
        $missing = 0;

        $results = [];

        foreach ($files as $file) {
            $disk = $file->isVault() ? config('files.vault_disk', 'vault') : config('files.default_disk', 'private');
            $path = $file->vault_path ?: $file->path;

            if (!Storage::disk($disk)->exists($path)) {
                $missing++;
                $results[] = [
                    'ID'       => $file->id,
                    'Tiket'    => $file->ticket?->kode_tiket ?? 'N/A',
                    'Berkas'   => basename($path),
                    'Status'   => 'HILANG',
                    'Catatan'  => 'Berkas fisik tidak ditemukan di disk',
                ];
                continue;
            }

            $rawContent = Storage::disk($disk)->get($path);
            if ($file->is_encrypted) {
                $rawContent = $uploadService->decryptPayload($rawContent);
            }

            // Tulis sementara untuk finfo
            $tmp = tempnam(sys_get_temp_dir(), 'scan_');
            file_put_contents($tmp, $rawContent);

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $tmp);
            finfo_close($finfo);

            $hash = hash('sha256', $rawContent);
            $issues = [];

            // Cek hash
            if ($file->sha256 && $file->sha256 !== $hash) {
                $issues[] = 'Ketidakcocokan Hash SHA-256 (Peringatan Integritas)';
            }

            // Cek nama berkas
            try {
                $uploadService->validateFilename($file->original_name ?: basename($path));
            } catch (\Throwable $e) {
                $issues[] = 'Nama berkas: ' . $e->getMessage();
            }

            // Cek ekstensi biner
            if (str_starts_with($rawContent, '%PDF-')) {
                if (preg_match('/\/JavaScript|\/Launch|\/EmbeddedFiles/i', $rawContent)) {
                    $issues[] = 'PDF memuat muatan aktif berbahaya';
                }
            }

            @unlink($tmp);

            if (empty($issues)) {
                $passed++;
            } else {
                $flagged++;
                $results[] = [
                    'ID'       => $file->id,
                    'Tiket'    => $file->ticket?->kode_tiket ?? 'N/A',
                    'Berkas'   => basename($path),
                    'Status'   => 'PERINGATAN',
                    'Catatan'  => implode('; ', $issues),
                ];
            }
        }

        $this->table(['ID', 'Tiket', 'Berkas', 'Status', 'Catatan'], $results);
        $this->newLine();
        $this->info("Ringkasan Pemindaian: Total: {$total} | Sah/Bersih: {$passed} | Peringatan: {$flagged} | Hilang: {$missing}");

        return 0;
    }
}
