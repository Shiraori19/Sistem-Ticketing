<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckSlaBreachCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:check-sla';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Periksa dan sinkronkan tiket yang melewati batas waktu SLA Resolusi atau Respon';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = Carbon::now();

        // 1. Sinkronkan SLA Resolusi Breach
        $resolusiCount = Ticket::whereIn('status', ['verified', 'in_progress', 'reopened'])
            ->where('sla_breach', false)
            ->whereNotNull('sla_deadline_at')
            ->where('sla_deadline_at', '<=', $now)
            ->update(['sla_breach' => true]);

        // 2. Sinkronkan SLA Respon Breach
        $responCount = Ticket::whereIn('status', ['pending_verification', 'pending_info'])
            ->where('sla_respon_breach', false)
            ->whereNotNull('sla_respon_deadline_at')
            ->where('sla_respon_deadline_at', '<=', $now)
            ->update(['sla_respon_breach' => true]);

        $this->info("Sinkronisasi SLA selesai: {$resolusiCount} tiket SLA Resolusi breach, {$responCount} tiket SLA Respon breach.");

        return Command::SUCCESS;
    }
}
