<?php

namespace App\Console;

use App\Models\Ticket;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // 1 & 2. Sinkronkan pelanggaran SLA Resolusi & Respon via CheckSlaBreachCommand (tiap menit)
        $schedule->command('tickets:check-sla')->everyMinute()->withoutOverlapping();

        // 3. Lepas otomatis penugasan teknisi yang melewati respond_deadline (tiap menit)
        $schedule->command('assignments:release-expired')->everyMinute()->withoutOverlapping();

        // 4. Kirim pengingat hambatan terbuka (tiap 30 menit)
        $schedule->command('blockers:remind')->everyThirtyMinutes()->withoutOverlapping();

        // 5. Agregasi statistik harian teknisi (tiap jam)
        $schedule->command('stats:aggregate')->hourly()->withoutOverlapping();

        // 6. Bekukan snapshot laporan kinerja bulanan lampau (tiap tanggal 1 jam 01:00)
        $schedule->command('report:freeze-monthly')->monthlyOn(1, '01:00')->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
