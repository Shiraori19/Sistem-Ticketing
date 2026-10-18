<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\TicketBlocker;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RemindOpenBlockersCommand extends Command
{
    protected $signature = 'blockers:remind';

    protected $description = 'Mengirimkan notifikasi pengingat setiap 4 jam selama hambatan teknisi masih berstatus open';

    public function handle(): int
    {
        $now = Carbon::now();
        $this->info('Memeriksa hambatan lapangan (blockers) yang membutuhkan pengingat berkala...');

        $openBlockers = TicketBlocker::with(['ticket', 'assignment.user'])
            ->where('status', 'open')
            ->where(function ($q) use ($now) {
                $q->whereNull('next_reminder_at')
                  ->orWhere('next_reminder_at', '<=', $now);
            })
            ->get();

        $count = 0;
        foreach ($openBlockers as $blocker) {
            $ticket = $blocker->ticket;
            if (!$ticket) continue;

            $techName = $blocker->assignment?->user?->nama ?: 'Teknisi';
            $msg = "Pengingat Hambatan Terbuka: Tiket {$ticket->kode_tiket} masih terkendala {$blocker->jenis_label} (Keterangan: {$blocker->keterangan}). Dilaporkan oleh {$techName}. Jam SLA tetap berjalan.";

            // Kirim notifikasi ke Admin Bidang
            $admins = User::where('bidang_id', $ticket->bidang_id)
                ->whereHas('role', fn($r) => $r->where('nama_role', 'Admin Bidang'))
                ->get();

            foreach ($admins as $admin) {
                Notification::create([
                    'ticket_id' => $ticket->id,
                    'nomor_tujuan' => $admin->username ?? 'DKIS-ADMIN',
                    'pesan' => "⏱️ [Pengingat Hambatan {$ticket->kode_tiket}] " . $msg,
                    'status_kirim' => 'pending',
                ]);
            }

            // Perbarui next_reminder_at ke 4 jam mendatang
            $blocker->next_reminder_at = $now->copy()->addHours(4);
            $blocker->save();

            $count++;
        }

        $this->info("Pengingat berhasil dikirimkan untuk {$count} hambatan lapangan yang masih berstatus open.");

        return Command::SUCCESS;
    }
}
