<?php

namespace App\Console\Commands;

use App\Services\AssignmentWorkflowService;
use Illuminate\Console\Command;

class ReleaseExpiredAssignmentsCommand extends Command
{
    protected $signature = 'assignments:release-expired';

    protected $description = 'Melepas otomatis penugasan teknisi yang melewati batas waktu konfirmasi respons (respond_deadline)';

    public function handle(AssignmentWorkflowService $workflowService): int
    {
        $this->info('Memeriksa penugasan teknisi yang melewati batas waktu respond_deadline...');

        $count = $workflowService->releaseExpiredAssignments();

        if ($count > 0) {
            $this->warn("Sebanyak {$count} penugasan teknisi berhasil dilepaskan otomatis.");
        } else {
            $this->info('Tidak ada penugasan teknisi yang kadaluarsa.');
        }

        return Command::SUCCESS;
    }
}
