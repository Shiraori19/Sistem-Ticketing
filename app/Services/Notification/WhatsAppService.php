<?php

namespace App\Services\Notification;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService implements WhatsAppServiceInterface
{
    protected string $driver;
    protected ?string $endpoint;
    protected ?string $apiKey;

    public function __construct()
    {
        $this->driver = config('services.whatsapp.driver', env('WA_DRIVER', 'log'));
        $this->endpoint = config('services.whatsapp.endpoint', env('WA_GATEWAY_URL'));
        $this->apiKey = config('services.whatsapp.api_key', env('WA_API_KEY'));
    }

    /**
     * Standarisasi format nomor ponsel ke format internasional (628...)
     */
    public function formatPhoneNumber(string $phone): string
    {
        $clean = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($clean, '0')) {
            $clean = '62' . substr($clean, 1);
        }
        return $clean;
    }

    /**
     * Kirim pesan notifikasi
     */
    public function sendMessage(string $nomorTujuan, string $pesan): array
    {
        $formattedNumber = $this->formatPhoneNumber($nomorTujuan);

        // Driver: Log (Default Aman untuk Audit & Pengujian Lokal)
        if ($this->driver === 'log' || empty($this->endpoint)) {
            Log::channel('single')->info("[WA MOCK GATEWAY] Mengirim ke {$formattedNumber}:\n{$pesan}");
            return [
                'success' => true,
                'message' => 'Pesan tercatat pada log mock gateway lokal.',
                'response' => ['target' => $formattedNumber, 'driver' => 'log'],
            ];
        }

        // Driver: HTTP API Gateway Resmi (Fonnte/Wablas/Custom Webhook)
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => $this->apiKey,
                ])
                ->post($this->endpoint, [
                    'target' => $formattedNumber,
                    'message' => $pesan,
                ]);

            if ($response->successful()) {
                Log::info("[WA GATEWAY SUCCESS] Pesan terkirim ke {$formattedNumber}");
                return [
                    'success' => true,
                    'message' => 'Pesan sukses dikirimkan ke gateway WhatsApp.',
                    'response' => $response->json(),
                ];
            }

            Log::warning("[WA GATEWAY REJECTED] HTTP {$response->status()} untuk {$formattedNumber}: " . $response->body());
            return [
                'success' => false,
                'message' => 'Gateway menolak pengiriman pesan (HTTP ' . $response->status() . ').',
                'response' => $response->body(),
            ];
        } catch (\Throwable $e) {
            Log::error("[WA GATEWAY ERROR] Kegagalan koneksi: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Gagal menghubungi server gateway WhatsApp: ' . $e->getMessage(),
            ];
        }
    }
}
