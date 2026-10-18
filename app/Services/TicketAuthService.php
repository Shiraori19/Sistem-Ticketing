<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class TicketAuthService
{
    public const MAX_ATTEMPTS = 5;
    public const DECAY_SECONDS = 900; // 15 menit masa penangguhan (lockout)
    public const SESSION_TTL_HOURS = 2; // Masa berlaku otorisasi sesi 2 jam

    /**
     * Kunci Rate Limiter berbasis per Tiket + IP
     */
    protected static function getTicketRateLimitKey(Ticket $ticket, Request $request): string
    {
        return 'verify_pin_ticket:' . $ticket->id . ':' . $request->ip();
    }

    /**
     * Kunci Rate Limiter global berbasis IP (mencegah credential stuffing antar-tiket)
     */
    protected static function getIpRateLimitKey(Request $request): string
    {
        return 'verify_pin_ip:' . $request->ip();
    }

    /**
     * Verifikasi PIN Keamanan 6 Digit secara ketat (Anti Brute-Force, Constant-Time Compare, Audit Trail)
     *
     * @return array{success: bool, locked: bool, attempts_left?: int, seconds?: int, message: string}
     */
    public static function verifyPin(Request $request, Ticket $ticket, ?string $inputPin): array
    {
        $ticketKey = self::getTicketRateLimitKey($ticket, $request);
        $ipKey = self::getIpRateLimitKey($request);

        // 1. Cek Proteksi Brute-Force (Lockout 15 Menit jika melebihi 5 kali salah)
        if (RateLimiter::tooManyAttempts($ticketKey, self::MAX_ATTEMPTS) || RateLimiter::tooManyAttempts($ipKey, self::MAX_ATTEMPTS * 2)) {
            $seconds = max(RateLimiter::availableIn($ticketKey), RateLimiter::availableIn($ipKey));
            $minutes = max(1, (int) ceil($seconds / 60));

            try {
                AuditLog::create([
                    'user_id' => null,
                    'aksi' => 'PIN_LOCKOUT',
                    'target_kode' => $ticket->kode_tiket,
                    'ip_address' => $request->ip(),
                    'detail' => "Penangguhan akses PIN (Lockout {$minutes} menit) untuk tiket {$ticket->kode_tiket} dari IP {$request->ip()} karena melebihi " . self::MAX_ATTEMPTS . " kali percobaan gagal.",
                    'timestamp' => Carbon::now(),
                ]);
            } catch (\Throwable $e) {
                // Ignore log exception
            }

            return [
                'success' => false,
                'locked' => true,
                'seconds' => $seconds,
                'message' => "Terlalu banyak percobaan PIN yang salah. Akses verifikasi tiket {$ticket->kode_tiket} dikunci sementara selama {$minutes} menit ({$seconds} detik) demi keamanan informasi kedinasan.",
            ];
        }

        // 2. Sanitasi & Validasi Format PIN (Wajib tepat 6 digit angka numerik)
        $cleanPin = trim((string) $inputPin);
        if (!preg_match('/^[0-9]{6}$/', $cleanPin)) {
            return [
                'success' => false,
                'locked' => false,
                'message' => 'Format PIN Keamanan tidak valid. PIN wajib berupa 6 digit angka numerik sesuai Struk Tanda Terima Digital.',
            ];
        }

        // 3. Pastikan tiket memiliki PIN resmi yang tersimpan
        $storedPin = (string) $ticket->pin;
        if (empty($storedPin) || strlen($storedPin) < 6) {
            return [
                'success' => false,
                'locked' => false,
                'message' => 'Tiket ini tidak memiliki PIN keamanan yang valid. Silakan hubungi Admin Pusat DKIS.',
            ];
        }

        // 4. Verifikasi PIN: Mendukung hash ber-salt (Bcrypt) dan legacy plaintext dengan upgrade otomatis
        $isMatch = false;
        if (str_starts_with($storedPin, '$2y$') || str_starts_with($storedPin, '$2a$') || strlen($storedPin) >= 60) {
            $isMatch = Hash::check($cleanPin, $storedPin);
        } else {
            $isMatch = hash_equals($storedPin, $cleanPin);
            if ($isMatch) {
                // Otomatis upgrade PIN plaintext legacy menjadi hash ter-salt
                $ticket->pin = Hash::make($cleanPin);
                $ticket->saveQuietly();
            }
        }

        if (!$isMatch) {
            // Catat kegagalan ke RateLimiter dengan masa penangguhan 900 detik (15 menit)
            RateLimiter::hit($ticketKey, self::DECAY_SECONDS);
            RateLimiter::hit($ipKey, self::DECAY_SECONDS);

            $attemptsLeft = RateLimiter::remaining($ticketKey, self::MAX_ATTEMPTS);

            try {
                AuditLog::create([
                    'user_id' => null,
                    'aksi' => 'PIN_VERIFIKASI_GAGAL',
                    'target_kode' => $ticket->kode_tiket,
                    'ip_address' => $request->ip(),
                    'detail' => "Percobaan input PIN gagal untuk tiket {$ticket->kode_tiket} dari IP {$request->ip()}. Sisa kesempatan: {$attemptsLeft}.",
                    'timestamp' => Carbon::now(),
                ]);
            } catch (\Throwable $e) {}

            $msg = $attemptsLeft > 0
                ? "PIN Keamanan yang Anda masukkan salah. Sisa kesempatan mencoba: {$attemptsLeft} kali sebelum akses tiket dikunci 15 menit."
                : "PIN Keamanan salah. Batas maksimal percobaan terlampaui. Akses tiket dikunci selama 15 menit demi keamanan.";

            return [
                'success' => false,
                'locked' => $attemptsLeft <= 0,
                'attempts_left' => $attemptsLeft,
                'message' => $msg,
            ];
        }

        // 5. Verifikasi Sukses: Bersihkan Rate Limiter
        RateLimiter::clear($ticketKey);
        RateLimiter::clear($ipKey);

        // 6. Buat Otorisasi Sesi Aman Berbasis HMAC & IP Binding
        self::authorizeSession($request, $ticket);

        try {
            AuditLog::create([
                'user_id' => null,
                'aksi' => 'PIN_VERIFIKASI_SUKSES',
                'target_kode' => $ticket->kode_tiket,
                'ip_address' => $request->ip(),
                'detail' => "Verifikasi PIN 6-digit berhasil untuk tiket {$ticket->kode_tiket} dari IP {$request->ip()}. Otorisasi hak aksi pelapor diaktifkan.",
                'timestamp' => Carbon::now(),
            ]);
        } catch (\Throwable $e) {}

        return [
            'success' => true,
            'locked' => false,
            'message' => 'Identitas Anda sebagai pelapor sah berhasil diverifikasi. Seluruh hak akses dan bukti tiket kini aktif.',
        ];
    }

    /**
     * Daftarkan sesi terotorisasi yang diproteksi tanda tangan digital HMAC & IP address
     */
    public static function authorizeSession(Request $request, Ticket $ticket): void
    {
        $appKey = (string) config('app.key');
        $ip = (string) $request->ip();
        $storedPin = (string) $ticket->pin;

        // Generate HMAC Signature yang mengikat ID Tiket + PIN + IP Address + APP_KEY
        $signature = hash_hmac('sha256', "ticket_auth_{$ticket->id}|{$storedPin}|{$ip}|{$appKey}", $appKey);

        $payload = [
            'ticket_id' => $ticket->id,
            'token' => $signature,
            'ip' => $ip,
            'user_agent' => substr((string) $request->userAgent(), 0, 150),
            'authorized_at' => Carbon::now()->timestamp,
            'expires_at' => Carbon::now()->addHours(self::SESSION_TTL_HOURS)->timestamp,
        ];

        session([
            "ticket_auth_{$ticket->id}" => $payload,
            "ticket_authorized_{$ticket->id}" => true, // Flag kompatibilitas Blade
        ]);
    }

    /**
     * Periksa apakah sesi saat ini adalah pelapor sah yang telah lolos verifikasi PIN
     * (Memeriksa kedaluwarsa waktu, kecocokan IP, dan keaslian token kriptografis)
     */
    public static function isAuthorized(Request $request, Ticket $ticket): bool
    {
        $sessionData = session("ticket_auth_{$ticket->id}");

        if (!is_array($sessionData)) {
            return false;
        }

        // 1. Cek kedaluwarsa sesi (TTL 2 jam)
        $expiresAt = (int) ($sessionData['expires_at'] ?? 0);
        if (Carbon::now()->timestamp > $expiresAt) {
            self::revokeSession($ticket);
            return false;
        }

        // 2. Cek Anti Session-Hijacking: IP address wajib persis sama dengan saat input PIN
        $sessionIp = (string) ($sessionData['ip'] ?? '');
        if (empty($sessionIp) || !hash_equals($sessionIp, (string) $request->ip())) {
            self::revokeSession($ticket);
            return false;
        }

        // 3. Verifikasi Integritas Tanda Tangan HMAC
        $appKey = (string) config('app.key');
        $storedPin = (string) $ticket->pin;
        $expectedSignature = hash_hmac('sha256', "ticket_auth_{$ticket->id}|{$storedPin}|{$sessionIp}|{$appKey}", $appKey);
        $token = (string) ($sessionData['token'] ?? '');

        if (empty($token) || !hash_equals($expectedSignature, $token)) {
            self::revokeSession($ticket);
            return false;
        }

        return true;
    }

    /**
     * Cabut otorisasi sesi tiket (Logout pelapor / saat sesi kedaluwarsa)
     */
    public static function revokeSession(Ticket $ticket): void
    {
        session()->forget("ticket_auth_{$ticket->id}");
        session()->forget("ticket_authorized_{$ticket->id}");
    }
}
