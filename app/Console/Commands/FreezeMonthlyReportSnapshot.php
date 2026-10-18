<?php

namespace App\Console\Commands;

use App\Models\ReportSnapshot;
use App\Services\LaporanService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FreezeMonthlyReportSnapshot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'report:freeze-monthly {--month= : Format YYYY-MM (contoh: 2026-09)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bekukan metrik kinerja layanan bulanan lampau ke tabel report_snapshots untuk integritas audit.';

    /**
     * Execute the console command.
     */
    public function handle(LaporanService $laporanService): int
    {
        $monthInput = $this->option('month');
        $targetMonth = $monthInput ? Carbon::createFromFormat('Y-m', $monthInput) : Carbon::now()->subMonth();

        $monthKey = $targetMonth->format('Y-m');
        $startDate = $targetMonth->copy()->startOfMonth();
        $endDate = $targetMonth->copy()->endOfMonth();

        $this->info("Membekukan snapshot laporan kinerja untuk bulan: {$monthKey} ({$startDate->toDateString()} s/d {$endDate->toDateString()})");

        $filters = [
            'periode' => 'kustom',
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'bidang_id' => null,
            'category_id' => null,
            'tlp_level' => null,
            'opd_id' => null,
        ];

        // Hitung metrik
        $payload = $laporanService->getLaporanData($filters);

        $filterHash = md5(json_encode($filters) . '_' . $startDate->toDateString() . '_' . $endDate->toDateString());

        // Simpan / update ke report_snapshots
        ReportSnapshot::updateOrCreate(
            [
                'periode_bulan' => $monthKey,
                'filter_hash' => $filterHash,
            ],
            [
                'payload_json' => $payload,
                'frozen_at' => now(),
                'frozen_by' => null, // Scheduled system automated freeze
            ]
        );

        $this->info("Snapshot bulan {$monthKey} berhasil dibekukan!");
        $this->table(
            ['Metrik', 'Nilai'],
            [
                ['Tiket Masuk', $payload['total_masuk']],
                ['Tiket Selesai', $payload['total_selesai']],
                ['Kepatuhan SLA (Mentah)', $payload['persen_tepat_mentah'] . '%'],
                ['Kepatuhan SLA (Setelah Pengecualian)', $payload['persen_tepat_excused'] . '%'],
                ['P50 Jam Selesai', $payload['p50_hours'] . ' Jam'],
                ['P90 Jam Selesai', $payload['p90_hours'] . ' Jam'],
                ['Rasio Reopen', $payload['rasio_reopen'] . '%'],
            ]
        );

        return Command::SUCCESS;
    }
}
