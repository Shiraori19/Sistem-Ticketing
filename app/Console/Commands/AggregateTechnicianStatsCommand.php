<?php

namespace App\Console\Commands;

use App\Services\TechnicianStatsService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AggregateTechnicianStatsCommand extends Command
{
    protected $signature = 'stats:aggregate {--date= : Tanggal kalkulasi (format: YYYY-MM-DD)}';

    protected $description = 'Menghitung ulang dan mengagregasi metrik analitik harian teknisi ke technician_stats_daily';

    public function handle(TechnicianStatsService $statsService): int
    {
        $dateStr = $this->option('date');
        $targetDate = $dateStr ? Carbon::parse($dateStr) : Carbon::today();

        $this->info("Memulai kalkulasi agregasi statistik teknisi untuk tanggal {$targetDate->toDateString()}...");

        $count = $statsService->aggregateDaily($targetDate);

        $this->info("Agregasi selesai. Sebanyak {$count} profil akun teknisi berhasil dikalkulasi.");

        return Command::SUCCESS;
    }
}
