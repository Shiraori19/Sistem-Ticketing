<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TicketFileController extends Controller
{
    /**
     * Download / preview berkas privat untuk Admin (auth required)
     */
    public function downloadAdmin(Request $request, string $kode, string $type)
    {
        $ticket = Ticket::where('kode_tiket', $kode)->firstOrFail();
        $user = Auth::user();

        $isTlpRed = strtoupper($ticket->tlp_level ?? '') === 'RED';

        // Verifikasi hak akses bidang & peran
        if ($user->isAdminBidang()) {
            if (!$isTlpRed && (!$ticket->bidang_id || $user->bidang_id !== $ticket->bidang_id)) {
                abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk mengakses berkas tiket bidang lain atau tiket belum diverifikasi.');
            }
        } elseif ($user->isTeknisi()) {
            $isAssigned = $ticket->assignments()->where('user_id', $user->id)->whereNull('unassigned_at')->exists();
            $isSameBidang = $ticket->bidang_id && $user->bidang_id === $ticket->bidang_id;
            $isPersandian = $user->bidang && (strtoupper($user->bidang->kode) === 'PERSANDIAN' || str_contains(strtoupper($user->bidang->nama_bidang ?? ''), 'PERSANDIAN'));
            if (!$isAssigned && !($isTlpRed && $isPersandian) && !$isSameBidang) {
                abort(403, 'Akses Ditolak: Anda tidak memiliki akses ke berkas tiket ini.');
            }
        } elseif (!$user->isAdminPusat()) {
            abort(403, 'Akses Ditolak.');
        }

        $path = $this->resolveFilePath($ticket, $type, $request);

        if (!$path) {
            abort(404, 'Berkas yang diminta tidak ditemukan pada tiket ini.');
        }

        // Cek proteksi berkas rahasia
        if ($type === 'bukti' && $ticket->is_confidential_evidence) {
            $canView = $user->isAdminPusat() 
                || ($user->isAdminBidang() && $user->bidang_id === $ticket->bidang_id)
                || ($user->isTeknisi() && $ticket->assignments()->where('user_id', $user->id)->whereNull('unassigned_at')->exists());
            if (!$canView) {
                abort(403, 'Akses Ditolak: Berkas resolusi ini ditandai sebagai rahasia (confidential).');
            }
        }

        try {
            AuditLog::create([
                'user_id' => $user->id,
                'aksi' => 'UNDUH_BERKAS_ADMIN',
                'target_kode' => $ticket->kode_tiket,
                'ip_address' => $request->ip() ?: '127.0.0.1',
                'detail' => "Petugas {$user->nama} (" . ($user->role?->nama_role ?? 'Petugas') . ") mengakses berkas {$type} [TLP: " . ($ticket->tlp_level ?? 'BIASA') . "]: " . basename($path),
                'timestamp' => Carbon::now(),
            ]);
        } catch (\Throwable $e) {}

        return $this->serveFile($path, $request);
    }

    /**
     * Download / preview berkas publik oleh pelapor
     * Proteksi: is_confidential_evidence = true TIDAK DAPAT diakses pelapor
     * Proteksi Anti-IDOR: hanya pelapor terotorisasi yang dapat mengunduh berkas
     */
    public function downloadPublic(Request $request, string $kode, string $type)
    {
        $ticket = Ticket::where('kode_tiket', $kode)->firstOrFail();

        // 1. Proteksi mutlak: bukti rahasia tidak boleh diakses pelapor
        if ($type === 'bukti' && $ticket->is_confidential_evidence) {
            abort(403, 'Akses Ditolak: Berkas ini ditandai rahasia internal dan tidak dapat diakses oleh publik.');
        }

        // 2. Verifikasi Otorisasi Pelapor Sah untuk Mengunduh Berkas (Proteksi Anti-IDOR via PIN Auth Sesi)
        $isAuthorized = \App\Services\TicketAuthService::isAuthorized($request, $ticket);

        if (!$isAuthorized) {
            abort(403, 'Akses Ditolak: Anda wajib memverifikasi PIN Keamanan tiket terlebih dahulu untuk mengakses berkas ini.');
        }

        $path = $this->resolveFilePath($ticket, $type, $request);

        if (!$path) {
            abort(404, 'Berkas yang diminta tidak ditemukan.');
        }

        try {
            AuditLog::create([
                'user_id' => null,
                'aksi' => 'UNDUH_BERKAS_PUBLIK',
                'target_kode' => $ticket->kode_tiket,
                'ip_address' => $request->ip() ?: '127.0.0.1',
                'detail' => "Pelapor terverifikasi PIN sah mengunduh berkas {$type}: " . basename($path),
                'timestamp' => Carbon::now(),
            ]);
        } catch (\Throwable $e) {}

        return $this->serveFile($path, $request);
    }

    /**
     * Helper untuk menentukan path berkas sesuai tipe (Mendukung Multi-Upload Lampiran)
     */
    protected function resolveFilePath(Ticket $ticket, string $type, Request $request): ?string
    {
        if ($type === 'lampiran') {
            $lampiran = $ticket->lampiran_pelapor;
            if (is_array($lampiran)) {
                if ($request->filled('file')) {
                    $requestedFile = (string) $request->input('file');
                    if (in_array($requestedFile, $lampiran, true)) {
                        return $requestedFile;
                    }
                }
                if ($request->filled('index')) {
                    $idx = (int) $request->input('index');
                    return $lampiran[$idx] ?? null;
                }
                return $lampiran[0] ?? null;
            }
            return is_string($lampiran) ? $lampiran : null;
        }

        if ($type === 'ticket_file' && $request->filled('file_id')) {
            $tf = \App\Models\TicketFile::where('ticket_id', $ticket->id)->where('id', $request->input('file_id'))->first();
            return $tf?->path;
        }

        return match ($type) {
            'lampiran_tambahan' => $ticket->lampiran_tambahan,
            'bukti' => $ticket->bukti_resolusi,
            'reopen' => $ticket->bukti_reopen_tambahan,
            default => null,
        };
    }

    /**
     * Helper untuk mengembalikan file response secara INLINE untuk in-app preview
     */
    protected function serveFile(string $path, Request $request)
    {
        $disk = null;
        if (Storage::disk('private')->exists($path)) {
            $disk = 'private';
        } elseif (Storage::disk('local')->exists($path)) {
            $disk = 'local';
        } elseif (Storage::disk('public')->exists($path)) {
            $disk = 'public';
        }

        if (!$disk) {
            abort(404, 'Berkas fisik tidak ditemukan pada sistem penyimpanan.');
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $contentTypes = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'pdf'  => 'application/pdf',
        ];

        $mimeType = $contentTypes[$ext] ?? (Storage::disk($disk)->mimeType($path) ?: 'application/octet-stream');
        $filename = basename($path);
        $isDownload = $request->boolean('download');
        $disposition = $isDownload ? 'attachment' : 'inline';

        return Storage::disk($disk)->response($path, $filename, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => "{$disposition}; filename=\"{$filename}\"",
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
