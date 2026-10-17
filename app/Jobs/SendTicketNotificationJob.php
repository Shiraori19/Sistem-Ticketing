<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendTicketNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    protected int $ticketId;
    protected string $nomorTujuan;
    protected string $pesan;

    /**
     * Create a new job instance.
     */
    public function __construct(int $ticketId, string $nomorTujuan, string $pesan)
    {
        $this->ticketId = $ticketId;
        $this->nomorTujuan = $nomorTujuan;
        $this->pesan = $pesan;
    }

    /**
     * Execute the job.
     */
    public function handle(\App\Services\Notification\WhatsAppServiceInterface $waService): void
    {
        $ticket = Ticket::find($this->ticketId);

        if (!$ticket) {
            Log::warning("SendTicketNotificationJob: Tiket ID {$this->ticketId} tidak ditemukan.");
            return;
        }

        try {
            // NOTIF-01: Kirim notifikasi via WhatsApp Service (mock log / gateway HTTP)
            $sendResult = $waService->sendMessage($this->nomorTujuan, $this->pesan);
            $statusKirim = $sendResult['success'] ? 'sent' : 'failed';

            // Catat pengiriman notifikasi ke tabel notifications
            Notification::create([
                'ticket_id' => $ticket->id,
                'nomor_tujuan' => $this->nomorTujuan,
                'pesan' => $this->pesan,
                'status_kirim' => $statusKirim,
                'created_at' => Carbon::now(),
            ]);

            Log::info("SendTicketNotificationJob: Notifikasi [{$statusKirim}] ke {$this->nomorTujuan} untuk tiket #{$ticket->kode_tiket}.");
        } catch (\Throwable $e) {
            Log::error("SendTicketNotificationJob Gagal: " . $e->getMessage());
            throw $e;
        }
    }
}
