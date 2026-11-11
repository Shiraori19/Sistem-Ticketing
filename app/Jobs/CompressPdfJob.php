<?php

namespace App\Jobs;

use App\Models\AuditLog;
use App\Models\TicketFile;
use App\Services\SecureUploadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class CompressPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;
    protected int $ticketFileId;

    public function __construct(int $ticketFileId)
    {
        $this->ticketFileId = $ticketFileId;
        $this->onQueue('compressions');
    }

    public function handle(SecureUploadService $uploadService): void
    {
        $file = TicketFile::find($this->ticketFileId);
        if (!$file || $file->mime !== 'application/pdf') {
            return;
        }

        $disk = $file->isVault() ? 'vault' : 'private';
        $path = $file->vault_path ?: $file->path;

        if (!Storage::disk($disk)->exists($path)) {
            return;
        }

        $rawPdf = Storage::disk($disk)->get($path);
        if ($file->is_encrypted) {
            $rawPdf = $uploadService->decryptPayload($rawPdf);
        }

        $tempInput = tempnam(sys_get_temp_dir(), 'pdf_in_');
        $tempOutput = tempnam(sys_get_temp_dir(), 'pdf_out_');
        file_put_contents($tempInput, $rawPdf);

        // Eksekusi Ghostscript dengan opsi aman (-dSAFER) dan optimasi ebook (150 DPI)
        $process = new Process([
            'gs',
            '-sDEVICE=pdfwrite',
            '-dCompatibilityLevel=1.4',
            '-dPDFSETTINGS=/ebook',
            '-dNOPAUSE',
            '-dQUIET',
            '-dBATCH',
            '-dSAFER',
            "-sOutputFile={$tempOutput}",
            $tempInput,
        ]);

        $process->setTimeout(90);
        $process->run();

        if ($process->isSuccessful() && file_exists($tempOutput)) {
            $compressedSize = filesize($tempOutput);
            $originalSize = $file->size;

            // Hanya perbarui berkas jika hasil kompresi valid dan ukurannya lebih kecil
            if ($compressedSize > 0 && $compressedSize < $originalSize) {
                $newPayload = file_get_contents($tempOutput);
                $newHash = hash('sha256', $newPayload);

                if ($file->is_encrypted) {
                    $newPayload = $uploadService->encryptPayload($newPayload);
                }

                Storage::disk($disk)->put($path, $newPayload);

                $file->update([
                    'size'   => $compressedSize,
                    'sha256' => $newHash,
                ]);

                AuditLog::create([
                    'user_id'     => null,
                    'aksi'        => 'KOMPRESI_PDF_SUKSES',
                    'target_kode' => $file->ticket?->kode_tiket ?? 'N/A',
                    'ip_address'  => '127.0.0.1',
                    'detail'      => "Kompresi PDF {$file->original_name} berhasil via Ghostscript: {$originalSize} -> {$compressedSize} bytes.",
                    'timestamp'   => now(),
                ]);
            }
        } else {
            Log::info("Ghostscript compression skipped or not available for file ID: {$this->ticketFileId}");
        }

        @unlink($tempInput);
        @unlink($tempOutput);
    }
}

