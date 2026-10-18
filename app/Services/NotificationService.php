<?php

namespace App\Services;

use App\Jobs\SendTicketNotificationJob;
use App\Models\Notification;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Kirim notifikasi tiket secara asinkron atau sinkron
     */
    public function sendTicketNotification(Ticket $ticket, string $nomorTujuan, string $pesan, bool $async = true): void
    {
        if ($async) {
            SendTicketNotificationJob::dispatch($ticket->id, $nomorTujuan, $pesan);
        } else {
            Notification::create([
                'ticket_id' => $ticket->id,
                'nomor_tujuan' => $nomorTujuan,
                'pesan' => $pesan,
                'status_kirim' => 'terkirim',
                'created_at' => Carbon::now(),
            ]);
            Log::info("NotificationService: Notifikasi langsung terkirim ke {$nomorTujuan} untuk tiket #{$ticket->kode_tiket}.");
        }
    }

    /**
     * Notifikasi pembuatan tiket baru ke pelapor
     */
    public function notifyTicketCreated(Ticket $ticket): void
    {
        if (empty($ticket->no_whatsapp)) {
            return;
        }

        $pesan = "Halo {$ticket->nama_pelapor}, tiket permohonan layanan TIK Anda telah diterima dengan Kode: {$ticket->kode_tiket}. Gunakan kode ini dan PIN rahasia Anda untuk memantau status penanganan di portal resmi DKIS Kota Cirebon.";
        $this->sendTicketNotification($ticket, $ticket->no_whatsapp, $pesan);
    }

    /**
     * Notifikasi pembaruan status tiket
     */
    public function notifyTicketStatusUpdated(Ticket $ticket, string $statusLabel): void
    {
        if (empty($ticket->no_whatsapp)) {
            return;
        }

        $pesan = "Pemberitahuan DKIS: Tiket #{$ticket->kode_tiket} mengenai '{$ticket->judul}' kini berstatus: {$statusLabel}. Kunjungi portal DKIS Kota Cirebon untuk informasi lebih lanjut.";
        $this->sendTicketNotification($ticket, $ticket->no_whatsapp, $pesan);
    }
}
