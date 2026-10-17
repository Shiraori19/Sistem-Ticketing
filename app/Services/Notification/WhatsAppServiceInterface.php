<?php

namespace App\Services\Notification;

interface WhatsAppServiceInterface
{
    /**
     * Kirim pesan teks WhatsApp ke nomor tujuan
     *
     * @param string $nomorTujuan Nomor telepon/WhatsApp tujuan (format 08... atau 62...)
     * @param string $pesan Isi teks pesan notifikasi
     * @return array{success: bool, message: string, response?: mixed}
     */
    public function sendMessage(string $nomorTujuan, string $pesan): array;
}
