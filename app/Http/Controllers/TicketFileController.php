<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Ticket;
use App\Models\TicketFile;
use App\Models\TicketFileAccessRequest;
use App\Services\FilePreviewService;
use App\Services\SecureUploadService;
use App\Services\TicketAuthService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TicketFileController extends Controller
{
    protected SecureUploadService $uploadService;
    protected FilePreviewService $previewService;

    public function __construct(SecureUploadService $uploadService, FilePreviewService $previewService)
    {
        $this->uploadService = $uploadService;
        $this->previewService = $previewService;
    }

    /**
     * Download berkas definitif untuk Admin (auth required, jejak kustodi digital)
     */
    public function downloadAdmin(Request $request, string $kode, string $type)
    {
        $ticket = Ticket::where('kode_tiket', $kode)->firstOrFail();
        $user = Auth::user();

        $isTlpRed = strtoupper($ticket->tlp_level ?? '') === 'RED';
        $isPersandian = (bool) $user->bidang?->isPersandian();

        // 1. Verifikasi Wewenang Akses Berdasarkan Peran & Bidang
        if ($user->isAdminBidang()) {
            if ($isTlpRed && !$isPersandian) {
                abort(403, 'Akses Ditolak: Berkas pada tiket TLP: RED mutlak dirahasiakan dan hanya dapat diakses oleh Bidang Persandian.');
            }
            if (!$isTlpRed && (!$ticket->bidang_id || $user->bidang_id !== $ticket->bidang_id)) {
                abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk mengakses berkas tiket bidang lain.');
            }
        } elseif ($user->isTeknisi()) {
            $isAssigned = $ticket->assignments()->where('user_id', $user->id)->whereNull('unassigned_at')->exists();
            $isSameBidang = $ticket->bidang_id && $user->bidang_id === $ticket->bidang_id;
            if (!$isAssigned && !($isTlpRed && $isPersandian) && !$isSameBidang) {
                abort(403, 'Akses Ditolak: Anda tidak memiliki penugasan atau wewenang pada berkas tiket ini.');
            }
        } elseif ($user->isAdminPusat()) {
            // BUTIR 5+6: Admin Pusat pada berkas rahasia / TLP RED wajib memiliki izin permohonan akses aktif
            if ($isTlpRed || $ticket->is_confidential_evidence) {
                $hasActiveAccess = TicketFileAccessRequest::where('user_id', $user->id)
                    ->whereHas('ticketFile', fn($q) => $q->where('ticket_id', $ticket->id))
                    ->where('status', 'approved')
                    ->where('expires_at', '>', now())
                    ->exists();

                if (!$hasActiveAccess) {
                    abort(403, 'Akses Ditolak: Berkas ini berklasifikasi RAHASIA / TLP RED. Admin Pusat wajib menekan "Minta Akses" dengan persetujuan Admin Bidang Persandian.');
                }
            }
        } else {
            abort(403, 'Akses Ditolak.');
        }

        $fileRecord = $this->resolveFileRecord($ticket, $type, $request);
        $path = $fileRecord?->vault_path ?: ($fileRecord?->path ?: $this->resolveFilePath($ticket, $type, $request));

        if (!$path) {
            abort(404, 'Berkas yang diminta tidak ditemukan pada tiket ini.');
        }

        // Cek proteksi bukti rahasia
        if ($type === 'bukti' && $ticket->is_confidential_evidence) {
            $canView = ($user->isAdminPusat() && isset($hasActiveAccess) && $hasActiveAccess)
                || ($user->isAdminBidang() && ($isPersandian || $user->bidang_id === $ticket->bidang_id))
                || ($user->isTeknisi() && $ticket->assignments()->where('user_id', $user->id)->whereNull('unassigned_at')->exists());
            if (!$canView) {
                abort(403, 'Akses Ditolak: Berkas resolusi ini ditandai sebagai rahasia internal.');
            }
        }

        // Catat Audit Log & Jejak Kustodi
        try {
            AuditLog::create([
                'user_id'     => $user->id,
                'aksi'        => 'UNDUH_BERKAS_ADMIN',
                'target_kode' => $ticket->kode_tiket,
                'ip_address'  => $request->ip() ?: '127.0.0.1',
                'detail'      => "Petugas {$user->nama} (" . ($user->role?->nama_role ?? 'Petugas') . ") mengunduh berkas {$type} [TLP: " . ($ticket->tlp_level ?? 'BIASA') . "] [Hash SHA-256: " . ($fileRecord?->sha256 ?: 'N/A') . "]: " . basename($path),
                'timestamp'   => Carbon::now(),
            ]);
        } catch (\Throwable $e) {}

        return $this->serveFile($path, $request, $fileRecord?->is_encrypted ?? false);
    }

    /**
     * Download berkas publik oleh pelapor (Proteksi Anti-IDOR via PIN Auth Sesi)
     */
    public function downloadPublic(Request $request, string $kode, string $type)
    {
        $ticket = Ticket::where('kode_tiket', $kode)->firstOrFail();

        // 1. Proteksi mutlak: bukti rahasia atau tiket TLP: RED tidak boleh diakses pelapor
        if ($type === 'bukti' && ($ticket->is_confidential_evidence || strtoupper($ticket->tlp_level ?? '') === 'RED')) {
            abort(403, 'Akses Ditolak: Berkas resolusi ini berklasifikasi rahasia (TLP: RED / Rahasia Internal) dan tidak dapat diakses oleh publik.');
        }

        // 2. Verifikasi Otorisasi Pelapor Sah untuk Mengunduh Berkas (Proteksi Anti-IDOR via PIN Auth Sesi)
        $isAuthorized = TicketAuthService::isAuthorized($request, $ticket);
        if (!$isAuthorized) {
            abort(403, 'Akses Ditolak: Anda wajib memverifikasi PIN Keamanan tiket terlebih dahulu untuk mengakses berkas ini.');
        }

        $fileRecord = $this->resolveFileRecord($ticket, $type, $request);
        $path = $fileRecord?->path ?: $this->resolveFilePath($ticket, $type, $request);

        if (!$path) {
            abort(404, 'Berkas yang diminta tidak ditemukan.');
        }

        try {
            AuditLog::create([
                'user_id'     => null,
                'aksi'        => 'UNDUH_BERKAS_PUBLIK',
                'target_kode' => $ticket->kode_tiket,
                'ip_address'  => $request->ip() ?: '127.0.0.1',
                'detail'      => "Pelapor terverifikasi PIN sah mengunduh berkas {$type} [Hash: " . ($fileRecord?->sha256 ?: 'N/A') . "]: " . basename($path),
                'timestamp'   => Carbon::now(),
            ]);
        } catch (\Throwable $e) {}

        return $this->serveFile($path, $request, false);
    }

    /**
     * Pratinjau Bukti Tanpa Unduh untuk Admin Internal
     */
    public function previewAdmin(Request $request, string $kode, int $fileId)
    {
        $ticket = Ticket::where('kode_tiket', $kode)->firstOrFail();
        $file = TicketFile::where('ticket_id', $ticket->id)->findOrFail($fileId);
        $user = Auth::user();

        $isTlpRed = strtoupper($ticket->tlp_level ?? '') === 'RED';
        $isPersandian = (bool) $user->bidang?->isPersandian();

        if ($user->isAdminBidang()) {
            if ($isTlpRed && !$isPersandian) {
                abort(403, 'Akses Ditolak: Pratinjau berkas TLP RED hanya untuk Persandian.');
            }
            if (!$isTlpRed && (!$ticket->bidang_id || $user->bidang_id !== $ticket->bidang_id)) {
                abort(403, 'Akses Ditolak: Berkas tiket bidang lain.');
            }
        } elseif ($user->isTeknisi()) {
            $isAssigned = $ticket->assignments()->where('user_id', $user->id)->whereNull('unassigned_at')->exists();
            if (!$isAssigned && !($isTlpRed && $isPersandian) && $ticket->bidang_id !== $user->bidang_id) {
                abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang pada berkas ini.');
            }
        } elseif ($user->isAdminPusat()) {
            if ($isTlpRed || $file->isConfidential()) {
                $hasActiveAccess = TicketFileAccessRequest::where('user_id', $user->id)
                    ->where('ticket_file_id', $file->id)
                    ->where('status', 'approved')
                    ->where('expires_at', '>', now())
                    ->exists();

                if (!$hasActiveAccess) {
                    abort(403, 'Akses Ditolak: Berkas rahasia/RED. Silakan ajukan "Minta Akses" untuk melihat pratinjau.');
                }
            }
        } else {
            abort(403, 'Akses Ditolak.');
        }

        return $this->previewService->servePreviewResponse($file, $request, $user);
    }

    /**
     * Pratinjau Bukti Tanpa Unduh untuk Pelapor Publik
     */
    public function previewPublic(Request $request, string $kode, int $fileId)
    {
        $ticket = Ticket::where('kode_tiket', $kode)->firstOrFail();
        $file = TicketFile::where('ticket_id', $ticket->id)->findOrFail($fileId);

        if ($file->isConfidential() || strtoupper($ticket->tlp_level ?? '') === 'RED') {
            abort(403, 'Akses Ditolak: Berkas rahasia.');
        }

        if (!TicketAuthService::isAuthorized($request, $ticket)) {
            abort(403, 'Akses Ditolak: Verifikasi PIN terlebih dahulu.');
        }

        return $this->previewService->servePreviewResponse($file, $request, null);
    }

    /**
     * Pengajuan Permohonan Akses Berkas Rahasia oleh Admin Pusat
     */
    public function requestAccess(Request $request, string $kode, int $fileId)
    {
        $ticket = Ticket::where('kode_tiket', $kode)->firstOrFail();
        $file = TicketFile::where('ticket_id', $ticket->id)->findOrFail($fileId);
        $user = Auth::user();

        if (!$user->isAdminPusat()) {
            abort(403, 'Hanya Admin Pusat yang dapat mengajukan permohonan akses.');
        }

        $request->validate([
            'reason' => 'required|string|min:5|max:500',
        ]);

        TicketFileAccessRequest::create([
            'ticket_file_id' => $file->id,
            'user_id'        => $user->id,
            'reason'         => $request->input('reason'),
            'status'         => 'pending',
        ]);

        return back()->with('success', 'Permohonan akses berkas rahasia berhasil diajukan ke Admin Bidang Persandian.');
    }

    /**
     * Peninjauan Permohonan Akses oleh Admin Bidang Persandian
     */
    public function reviewAccessRequest(Request $request, int $requestId)
    {
        $accessReq = TicketFileAccessRequest::findOrFail($requestId);
        $user = Auth::user();

        if (!$user->isAdminBidang() || !$user->bidang?->isPersandian()) {
            abort(403, 'Hanya Admin Bidang Persandian yang berwenang memproses permohonan akses berkas rahasia.');
        }

        $action = $request->input('action') ?? $request->input('decision'); // 'approve' | 'approved' | 'reject' | 'rejected'
        if (in_array(strtolower((string) $action), ['approve', 'approved'])) {
            $accessReq->update([
                'status'      => 'approved',
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
                'expires_at'  => now()->addHours(24),
            ]);
            $msg = 'Permohonan akses disetujui (berlaku 24 jam).';
        } else {
            $accessReq->update([
                'status'      => 'rejected',
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
            ]);
            $msg = 'Permohonan akses ditolak.';
        }

        return back()->with('success', $msg);
    }

    /**
     * Helper resolve entri TicketFile
     */
    protected function resolveFileRecord(Ticket $ticket, string $type, Request $request): ?TicketFile
    {
        if ($request->filled('file_id')) {
            return TicketFile::where('ticket_id', $ticket->id)->find($request->input('file_id'));
        }

        if ($type === 'bukti') {
            return TicketFile::where('ticket_id', $ticket->id)->where('kategori', 'bukti_resolusi')->latest()->first();
        }

        if ($type === 'lampiran_tambahan') {
            return TicketFile::where('ticket_id', $ticket->id)->where('kategori', 'lampiran_tambahan')->latest()->first();
        }

        if ($type === 'reopen') {
            return TicketFile::where('ticket_id', $ticket->id)->where('kategori', 'bukti_reopen')->latest()->first();
        }

        return TicketFile::where('ticket_id', $ticket->id)->latest()->first();
    }

    /**
     * Helper untuk menentukan path berkas legacy sesuai tipe
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
            $tf = TicketFile::where('ticket_id', $ticket->id)->where('id', $request->input('file_id'))->first();
            return $tf?->vault_path ?: $tf?->path;
        }

        return match ($type) {
            'lampiran_tambahan' => $ticket->lampiran_tambahan,
            'bukti'             => $ticket->bukti_resolusi,
            'reopen'            => $ticket->bukti_reopen_tambahan,
            default             => null,
        };
    }

    /**
     * Helper untuk mengembalikan file response secara aman
     */
    protected function serveFile(string $path, Request $request, bool $isEncrypted = false)
    {
        $disk = null;
        if (Storage::disk('vault')->exists($path)) {
            $disk = 'vault';
        } elseif (Storage::disk('private')->exists($path)) {
            $disk = 'private';
        } elseif (Storage::disk('local')->exists($path)) {
            $disk = 'local';
        } elseif (Storage::disk('public')->exists($path)) {
            $disk = 'public';
        }

        if (!$disk) {
            abort(404, 'Berkas fisik tidak ditemukan pada sistem penyimpanan.');
        }

        $filename = basename($path);
        $isDownload = $request->boolean('download');
        $disposition = $isDownload ? 'attachment' : 'inline';

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $contentTypes = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'pdf'  => 'application/pdf',
            'mp4'  => 'video/mp4',
            'txt'  => 'text/plain; charset=UTF-8',
        ];
        $mimeType = $contentTypes[$ext] ?? (Storage::disk($disk)->mimeType($path) ?: 'application/octet-stream');

        // Jika berkas terenkripsi di vault, baca lalu dekripsi terlebih dahulu
        if ($isEncrypted || $disk === 'vault') {
            $rawContent = Storage::disk($disk)->get($path);
            $decryptedContent = $this->uploadService->decryptPayload($rawContent);

            return response($decryptedContent, 200, [
                'Content-Type'              => $mimeType,
                'Content-Disposition'       => "{$disposition}; filename=\"{$filename}\"",
                'X-Content-Type-Options'    => 'nosniff',
                'Cache-Control'             => 'no-store, no-cache, must-revalidate, private',
                'Content-Security-Policy'   => "default-src 'none'",
            ]);
        }

        return Storage::disk($disk)->response($path, $filename, [
            'Content-Type'              => $mimeType,
            'Content-Disposition'       => "{$disposition}; filename=\"{$filename}\"",
            'X-Content-Type-Options'    => 'nosniff',
            'Cache-Control'             => 'no-store, no-cache, must-revalidate, private',
        ]);
    }
}
