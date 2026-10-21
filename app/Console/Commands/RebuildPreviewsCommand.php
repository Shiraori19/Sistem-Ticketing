<?php

namespace App\Console\Commands;

use App\Models\TicketFile;
use App\Services\FilePreviewService;
use Illuminate\Console\Command;

class RebuildPreviewsCommand extends Command
{
    protected $signature = 'previews:rebuild {--ticket= : Kode tiket spesifik} {--force : Paksa buat ulang walau sudah ada}';
    protected $description = 'Buat ulang berkas turunan pratinjau aman untuk lampiran tiket';

    public function handle(FilePreviewService $previewService): int
    {
        $this->info('Memulai pembuatan ulang berkas turunan pratinjau...');

        $query = TicketFile::with('ticket');
        if ($ticketCode = $this->option('ticket')) {
            $query->whereHas('ticket', fn($q) => $q->where('kode_tiket', $ticketCode));
        }

        $files = $query->get();
        if ($files->isEmpty()) {
            $this->info('Tidak ada berkas yang perlu dibuatkan pratinjau.');
            return 0;
        }

        $bar = $this->output->createProgressBar($files->count());
        $bar->start();

        $success = 0;
        $failed = 0;

        foreach ($files as $file) {
            $result = $previewService->generatePreview($file);
            if (($result['status'] ?? '') === 'ready') {
                $success++;
            } else {
                $failed++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Selesai membuat pratinjau. Berhasil: {$success} | Gagal: {$failed}");

        return 0;
    }
}
