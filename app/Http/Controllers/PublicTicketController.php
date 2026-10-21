<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReopenRequest;
use App\Http\Requests\StoreTicketRequest;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Ticket;
use App\Services\TicketAuthService;
use App\Services\TicketWorkflowService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicTicketController extends Controller
{
    protected TicketWorkflowService $workflowService;
    protected \App\Services\SecureUploadService $uploadService;

    public function __construct(TicketWorkflowService $workflowService, \App\Services\SecureUploadService $uploadService)
    {
        $this->workflowService = $workflowService;
        $this->uploadService = $uploadService;
    }

    /**
     * Halaman portal publik ASN DKIS (form pengajuan & lacak tiket dalam satu halaman interaktif)
     */
    public function index(Request $request)
    {
        $categories = Category::with('bidang')->orderBy('bidang_id')->orderBy('id')->get();
        $masterOpdList = \App\Models\MasterOpd::active()->orderBy('urutan')->orderBy('nama_opd')->get();
        $activeTab = $request->input('tab', 'buat');
        $query = trim($request->input('q', ''));
        $inputWa = trim($request->input('no_whatsapp', ''));
        $authToken = (string) $request->input('auth_token', '');
        $tickets = collect();

        if (!empty($query)) {
            $activeTab = 'lacak';

            $ticketQuery = Ticket::with(['category', 'bidang'])
                ->where('kode_tiket', $query);

            // Filter nomor WhatsApp opsional jika dimasukkan
            if (!empty($inputWa)) {
                $cleanWa = preg_replace('/[^0-9]/', '', $inputWa);
                $cleanWaLtrim = ltrim($cleanWa, '0');
                if (str_starts_with($cleanWaLtrim, '62')) {
                    $cleanWaLtrim = substr($cleanWaLtrim, 2);
                }

                $ticketQuery->where(function ($w) use ($cleanWa, $cleanWaLtrim) {
                    $w->where('no_whatsapp', $cleanWa)
                      ->orWhere('no_whatsapp', '0' . $cleanWaLtrim)
                      ->orWhere('no_whatsapp', '62' . $cleanWaLtrim);
                });
            }

            $tickets = $ticketQuery->orderBy('id', 'desc')->get();

            // Otorisasi sesi HANYA jika memiliki token resmi yang sah (misal dari redirect pembuatan tiket baru)
            foreach ($tickets as $t) {
                if (!empty($authToken) && hash_equals($t->access_token, $authToken)) {
                    TicketAuthService::authorizeSession($request, $t);
                }
            }
        }

        $relatedTickets = collect();
        if ($tickets->isNotEmpty()) {
            $mainTicket = $tickets->first();
            $isMainAuthorized = TicketAuthService::isAuthorized($request, $mainTicket);

            // Hanya tampilkan daftar pengaduan terkait jika identitas pemohon telah lolos otorisasi PIN sah
            if ($isMainAuthorized) {
                $relatedTickets = Ticket::with(['category', 'bidang'])
                    ->where('no_whatsapp', $mainTicket->no_whatsapp)
                    ->where('id', '!=', $mainTicket->id)
                    ->whereNotIn('status', ['closed', 'force_closed'])
                    ->orderBy('id', 'desc')
                    ->limit(4)
                    ->get();
            }
        }

        return view('public.index', compact('categories', 'masterOpdList', 'activeTab', 'query', 'tickets', 'relatedTickets'));
    }

    /**
     * Simpan tiket baru dari formulir publik ASN (Rate limit 5 req/menit per IP)
     */
    public function store(StoreTicketRequest $request)
    {
        $data = $request->validated();

        // 1. createTicket mengunci advisory lock & mengalokasikan kode tiket pasti yang terbebas konflik
        $ticket = $this->workflowService->createTicket($data);

        // 2. Simpan seluruh lampiran pelapor melalui SecureUploadService
        if ($request->hasFile('lampiran_pelapor')) {
            $files = $request->file('lampiran_pelapor');
            $filesList = is_array($files) ? $files : [$files];
            $finalPaths = [];

            foreach ($filesList as $file) {
                if ($file) {
                    $uploadResult = $this->uploadService->storeUploadedFile(
                        $file,
                        "tickets/{$ticket->kode_tiket}",
                        [
                            'ticket' => $ticket,
                            'kategori_field' => 'lampiran_pelapor',
                            'allowed_categories' => ['image', 'pdf'],
                        ]
                    );
                    $finalPaths[] = $uploadResult['path'];
                }
            }

            if (!empty($finalPaths)) {
                $ticket->lampiran_pelapor = $finalPaths;
                $ticket->save();
            }
        }

        // 4. Kirim notifikasi asinkron ke antrean worker
        try {
            app(\App\Services\NotificationService::class)->notifyTicketCreated($ticket);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Gagal dispatch notifikasi tiket: " . $e->getMessage());
        }

        // Otorisasi sesi aman langsung untuk pembuat tiket baru pada perangkat yang sama
        TicketAuthService::authorizeSession($request, $ticket);

        $displayPin = $ticket->raw_pin_temporary ?? $ticket->pin;

        return redirect()->route('public.index', ['tab' => 'lacak', 'q' => $ticket->kode_tiket])
            ->with('new_ticket_code', $ticket->kode_tiket)
            ->with('new_ticket_pin', $displayPin)
            ->with('new_ticket_data', [
                'kode_tiket' => $ticket->kode_tiket,
                'waktu' => $ticket->created_at->translatedFormat('d F Y, H:i') . ' WIB',
                'nama' => $ticket->nama_pelapor,
                'nip' => $ticket->nip_pelapor,
                'skpd' => $ticket->skpd,
                'kategori' => $ticket->category?->nama_kategori ?? '-',
                'judul' => $ticket->judul,
                'pin' => $displayPin,
            ])
            ->with('success', "Tiket Anda berhasil diajukan dengan Kode: {$ticket->kode_tiket}. Simpan 6 digit PIN ini untuk membuka bukti penanganan atau membatalkan tiket.");
    }

    /**
     * Lacak status tiket (redirect ke index tab lacak)
     */
    public function track(Request $request)
    {
        $query = trim($request->input('q', ''));
        $token = trim($request->input('auth_token', ''));
        $params = ['tab' => 'lacak', 'q' => $query];
        if (!empty($token)) {
            $params['auth_token'] = $token;
        }
        return redirect()->route('public.index', $params);
    }

    /**
     * Verifikasi PIN pelapor untuk membuka aksi interaktif
     * Proteksi: Rate-Limiting Lockout 15 Menit + Timing-Attack Safe Compare + HMAC Session Binding
     */
    public function verifyPin(Request $request, string $kode)
    {
        $ticket = Ticket::where('kode_tiket', $kode)->firstOrFail();
        $inputPin = trim((string) $request->input('pin'));

        $authResult = TicketAuthService::verifyPin($request, $ticket, $inputPin);

        if (!$authResult['success']) {
            return back()->with('error', $authResult['message'])->withInput();
        }

        return back()->with('success', $authResult['message']);
    }

    /**
     * Kirim kelengkapan data/berkas tambahan oleh pelapor (Proteksi Otorisasi PIN)
     */
    public function submitCompletion(Request $request, string $kode)
    {
        $ticket = Ticket::where('kode_tiket', $kode)->firstOrFail();

        if ($ticket->status !== 'pending_verification') {
            return back()->with('error', 'Kelengkapan data hanya dapat dikirimkan saat tiket dalam tahap verifikasi.');
        }

        $request->validate([
            'pin' => ['required', 'string', 'regex:/^[0-9]{6}$/'],
            'keterangan_tambahan' => ['required', 'string', 'min:5', 'max:3000'],
            'lampiran_tambahan' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:5120'],
        ], [
            'pin.required' => 'PIN Keamanan 6 digit wajib diisi sesuai Struk Tanda Terima Digital.',
            'pin.regex' => 'Format PIN Keamanan harus berupa 6 digit angka numerik.',
            'keterangan_tambahan.required' => 'Penjelasan data tambahan wajib diisi.',
            'keterangan_tambahan.min' => 'Penjelasan minimal 5 karakter.',
            'lampiran_tambahan.max' => 'Ukuran berkas maksimal 5MB.',
        ]);

        // Verifikasi PIN ketat dengan RateLimiter
        $authResult = TicketAuthService::verifyPin($request, $ticket, $request->input('pin'));
        if (!$authResult['success']) {
            return back()->with('error', $authResult['message'])->withInput();
        }

        if ($request->hasFile('lampiran_tambahan')) {
            $uploaded = $request->file('lampiran_tambahan');
            $uploadResult = $this->uploadService->storeUploadedFile(
                $uploaded,
                "tickets/{$ticket->kode_tiket}",
                [
                    'ticket' => $ticket,
                    'kategori_field' => 'lampiran_tambahan',
                    'allowed_categories' => ['image', 'pdf'],
                ]
            );
            $ticket->lampiran_tambahan = $uploadResult['path'];
        }

        $nowStr = Carbon::now()->translatedFormat('d F Y, H:i');
        $ticket->deskripsi .= "\n\n--- [DATA TAMBAHAN DARI PELAPOR: {$nowStr} WIB] ---\n" . $request->input('keterangan_tambahan');

        $history = $ticket->status_history ?? [];
        $history[] = [
            'status' => 'pending_verification',
            'timestamp' => Carbon::now()->toIso8601String(),
            'actor' => $ticket->nama_pelapor . ' (Pelapor)',
            'note' => 'Pelapor telah melengkapi data tambahan: ' . Str::limit($request->input('keterangan_tambahan'), 80),
        ];
        $ticket->status_history = $history;
        $ticket->catatan_kelengkapan = null;
        $ticket->save();

        return back()->with('success', 'Data tambahan berhasil dikirimkan ke Admin Pusat DKIS. Laporan Anda segera diproses.');
    }

    /**
     * Batalkan tiket mandiri oleh pelapor saat status pending_verification (Proteksi Otorisasi PIN)
     */
    public function cancelTicket(Request $request, string $kode)
    {
        $ticket = Ticket::where('kode_tiket', $kode)->firstOrFail();

        if ($ticket->status !== 'pending_verification') {
            return back()->with('error', 'Pembatalan hanya dapat dilakukan saat tiket masih dalam tahap verifikasi awal (pending verification).');
        }

        $request->validate([
            'pin' => ['required', 'string', 'regex:/^[0-9]{6}$/'],
            'alasan_pembatalan' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'pin.required' => 'PIN Keamanan 6 digit wajib diisi sesuai Struk Tanda Terima Digital.',
            'pin.regex' => 'Format PIN Keamanan harus berupa 6 digit angka numerik.',
            'alasan_pembatalan.required' => 'Alasan pembatalan wajib dicantumkan.',
            'alasan_pembatalan.min' => 'Alasan pembatalan minimal 5 karakter.',
        ]);

        // Verifikasi PIN ketat dengan RateLimiter
        $authResult = TicketAuthService::verifyPin($request, $ticket, $request->input('pin'));
        if (!$authResult['success']) {
            return back()->with('error', $authResult['message'])->withInput();
        }

        $alasan = $request->input('alasan_pembatalan');
        $actor = $ticket->nama_pelapor . ' (Pelapor)';

        $this->workflowService->cancelTicket($ticket, $alasan, $actor);

        // Cabut otorisasi sesi setelah tiket resmi dibatalkan
        TicketAuthService::revokeSession($ticket);

        // Catat ke Audit Log
        AuditLog::create([
            'user_id' => null,
            'aksi' => 'TIKET_DIBATALKAN_PELAPOR',
            'target_kode' => $ticket->kode_tiket,
            'ip_address' => $request->ip(),
            'detail' => 'Dibatalkan oleh Pelapor dengan otorisasi PIN 6-digit. Alasan: ' . $alasan,
            'timestamp' => Carbon::now(),
        ]);

        return redirect()->route('public.index', ['tab' => 'lacak', 'q' => $ticket->kode_tiket])
            ->with('success', "Tiket {$ticket->kode_tiket} telah berhasil dibatalkan.");
    }

    /**
     * Konfirmasi selesai oleh pelapor (rating & feedback)
     */
    public function confirmClose(Request $request, string $kode)
    {
        $ticket = Ticket::where('kode_tiket', $kode)->firstOrFail();

        // Validasi Otorisasi Sesi Pelapor Sah
        if (!TicketAuthService::isAuthorized($request, $ticket)) {
            return back()->with('error', 'Akses Ditolak: Anda wajib memverifikasi PIN Keamanan 6 digit terlebih dahulu untuk melakukan konfirmasi penutupan tiket.');
        }

        $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'feedback' => ['nullable', 'string', 'max:1000'],
        ], [
            'rating.required' => 'Mohon berikan penilaian kepuasan layanan dengan memilih 1 sampai 5 bintang.',
            'rating.between' => 'Rating kepuasan harus bernilai antara 1 sampai 5 bintang.',
            'feedback.max' => 'Ulasan maksimal 1000 karakter.',
        ]);

        if ($ticket->status !== 'resolved') {
            return back()->with('error', "Hanya tiket yang berstatus 'resolved' yang dapat dikonfirmasi selesai.");
        }

        $this->workflowService->confirmClose(
            $ticket,
            (int) $request->input('rating'),
            $request->input('feedback')
        );

        return back()->with('success', 'Terima kasih atas konfirmasi penyelesaian dan ulasan yang Anda berikan.');
    }

    /**
     * Reopen oleh pelapor (WAJIB menyertakan bukti pendukung)
     */
    public function reopen(ReopenRequest $request, string $kode)
    {
        $ticket = Ticket::where('kode_tiket', $kode)->firstOrFail();

        // Validasi Otorisasi Sesi Pelapor Sah
        if (!TicketAuthService::isAuthorized($request, $ticket)) {
            return back()->with('error', 'Akses Ditolak: Anda wajib memverifikasi PIN Keamanan 6 digit terlebih dahulu untuk membuka kembali tiket.');
        }

        if (!in_array($ticket->status, ['resolved', 'closed'])) {
            return back()->with('error', "Tiket hanya dapat dibuka kembali jika sudah berstatus 'resolved' atau 'closed'.");
        }

        $uploadResult = $this->uploadService->storeUploadedFile(
            $request->file('bukti_reopen'),
            "tickets/{$ticket->kode_tiket}",
            [
                'ticket' => $ticket,
                'kategori_field' => 'bukti_reopen',
                'allowed_categories' => ['image', 'pdf'],
            ]
        );
        $path = $uploadResult['path'];

        $updatedTicket = $this->workflowService->reopen(
            $ticket,
            $request->input('alasan'),
            $path
        );

        $msg = $updatedTicket->status === 'eskalasi_pusat'
            ? "Tiket telah mencapai batas pembukaan kembali dan dialihkan ke status Eskalasi Pusat untuk ditinjau oleh Admin Pusat DKIS."
            : "Tiket berhasil dibuka kembali dan diteruskan ke tim teknis.";

        return back()->with('success', $msg);
    }
}
