<?php

namespace App\Http\Controllers\Teknisi;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teknisi\AcceptAssignmentRequest;
use App\Http\Requests\Teknisi\AddWorklogRequest;
use App\Http\Requests\Teknisi\DeclineAssignmentRequest;
use App\Http\Requests\Teknisi\ReportBlockerRequest;
use App\Http\Requests\Teknisi\SubmitReviewRequest;
use App\Http\Requests\Teknisi\UpdateAvailabilityRequest;
use App\Models\AuditLog;
use App\Models\Ticket;
use App\Models\TicketAssignment;
use App\Models\TicketBlocker;
use App\Services\AssignmentWorkflowService;
use App\Services\TechnicianStatsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class TeknisiTicketController extends Controller
{
    protected AssignmentWorkflowService $workflowService;
    protected TechnicianStatsService $statsService;

    public function __construct(
        AssignmentWorkflowService $workflowService,
        TechnicianStatsService $statsService
    ) {
        $this->workflowService = $workflowService;
        $this->statsService = $statsService;
    }

    /**
     * Helper query dasar penugasan teknisi yang login
     * (Hanya penugasan milik sendiri, isolasi TLP RED untuk teknisi Persandian)
     */
    protected function getBaseAssignmentQuery()
    {
        $user = Auth::user();
        $isPersandian = $user->bidang && (
            strtoupper($user->bidang->kode ?? '') === 'PERSANDIAN' || 
            str_contains(strtoupper($user->bidang->nama_bidang ?? ''), 'PERSANDIAN')
        );

        return TicketAssignment::with([
            'ticket.category:id,nama_kategori',
            'ticket.bidang:id,nama_bidang,kode',
            'ticket.activeBlockers',
            'ticket.assignments.user:id,nama',
            'blockers' => fn($q) => $q->orderBy('created_at', 'desc'),
        ])
        ->where('user_id', $user->id)
        ->whereHas('ticket', function ($q) use ($isPersandian) {
            if (!$isPersandian) {
                $q->where(function ($sub) {
                    $sub->whereNull('tlp_level')->orWhere('tlp_level', '!=', 'RED');
                });
            }
        });
    }

    /**
     * Halaman Utama: Tugas Saya (Dashboard Kerja Teknisi)
     * Menampilkan 4 ringkasan angka, filter status, pencarian, dan daftar penugasan dengan sisa SLA.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            $user->loadMissing(['technicianProfile', 'bidang', 'role']);
        }

        $now = Carbon::now();
        $baseQuery = $this->getBaseAssignmentQuery();

        // 1. Hitung 4 Metrik Ringkas Utama
        $allMyAssignments = (clone $baseQuery)->get();
        $statAntrean = $allMyAssignments->where('status', 'assigned')
            ->filter(fn($a) => !in_array($a->ticket?->status, ['closed', 'force_closed', 'cancelled']))
            ->count();
        $statDikerjakan = $allMyAssignments->where('status', 'working')
            ->filter(fn($a) => !in_array($a->ticket?->status, ['closed', 'force_closed', 'cancelled']))
            ->count();
        $statSelesaiBulanIni = $allMyAssignments->where('status', 'approved')
            ->filter(fn($a) => $a->approved_at && Carbon::parse($a->approved_at)->isCurrentMonth())
            ->count();
        $statMendesak = $allMyAssignments->filter(function ($a) use ($now) {
            if (in_array($a->status, ['approved', 'declined', 'released'])) return false;
            if (in_array($a->ticket?->status, ['closed', 'force_closed', 'cancelled'])) return false;
            $dl = $a->ticket?->sla_deadline_at;
            if (!$dl) return false;
            $diff = $now->diffInMinutes(Carbon::parse($dl), false);
            return $diff >= 0 && $diff <= 120; // Sisa waktu < 2 jam
        })->count();

        $stats = [
            'antrean' => $statAntrean,
            'mendesak' => $statMendesak,
            'dikerjakan' => $statDikerjakan,
            'selesai' => $statSelesaiBulanIni,
        ];

        // 2. Filter Status Tab
        $filter = $request->input('filter', 'semua');
        $query = clone $baseQuery;

        if ($filter === 'antrean') {
            $query->where('status', 'assigned')
                ->whereHas('ticket', fn($t) => $t->whereNotIn('status', ['closed', 'force_closed', 'cancelled']));
        } elseif ($filter === 'dikerjakan') {
            $query->where('status', 'working')
                ->whereHas('ticket', fn($t) => $t->whereNotIn('status', ['closed', 'force_closed', 'cancelled']));
        } elseif ($filter === 'review') {
            $query->where('status', 'submitted')
                ->whereHas('ticket', fn($t) => $t->whereNotIn('status', ['closed', 'force_closed', 'cancelled']));
        } elseif ($filter === 'selesai') {
            $query->where('status', 'approved');
        } elseif ($filter === 'mendesak') {
            $query->whereNotIn('status', ['approved', 'declined', 'released'])
                ->whereHas('ticket', function ($q) use ($now) {
                    $q->whereNotIn('status', ['closed', 'force_closed', 'cancelled'])
                      ->whereNotNull('sla_deadline_at')
                      ->where('sla_deadline_at', '>=', $now)
                      ->where('sla_deadline_at', '<=', $now->copy()->addHours(2));
                });
        }

        // 3. Pencarian
        if ($request->filled('q')) {
            $q = trim($request->input('q'));
            $query->whereHas('ticket', function ($t) use ($q) {
                $t->where('kode_tiket', 'LIKE', "%{$q}%")
                  ->orWhere('judul', 'LIKE', "%{$q}%")
                  ->orWhere('skpd', 'LIKE', "%{$q}%")
                  ->orWhere('nama_pelapor', 'LIKE', "%{$q}%");
            });
        }

        // 4. Pengurutan (Default: Sisa SLA terdekat)
        $query->orderByRaw("
            CASE 
                WHEN status = 'assigned' THEN 1
                WHEN status = 'working' THEN 2
                WHEN status = 'submitted' THEN 3
                ELSE 4
            END ASC
        ");

        $assignments = $query->paginate(15)->withQueryString();

        return view('teknisi.tugas.index', compact('assignments', 'stats', 'filter', 'user'));
    }

    /**
     * Halaman Detail Tugas:
     * Menampilkan SLA berjalan, deskripsi, lampiran, timeline ticket_events, form progres, tim.
     * Kontak pelapor HANYA dikirim jika penugasan aktif (assigned, working), dan dicatat event contact_viewed.
     */
    public function show($id)
    {
        $user = Auth::user();
        /** @var TicketAssignment $assignment */
        $assignment = TicketAssignment::with([
            'ticket.category',
            'ticket.bidang',
            'ticket.files',
            'ticket.events.actor:id,nama,role_id',
            'ticket.events.files',
            'ticket.assignments.user:id,nama',
            'blockers' => fn($q) => $q->orderBy('created_at', 'desc'),
        ])->findOrFail($id);

        // Otorisasi via Policy (mengembalikan 404 jika bukan milik sendiri atau TLP RED non-Persandian)
        $this->authorize('view', $assignment);

        $ticket = $assignment->ticket;
        $canViewContact = in_array($assignment->status, ['assigned', 'working']) 
            && !in_array($ticket->status, ['closed', 'force_closed', 'cancelled']);

        // Catat audit event contact_viewed jika penugasan aktif
        if ($canViewContact) {
            $this->workflowService->recordContactViewed($ticket, $assignment, $user);
        }

        return view('teknisi.tugas.show', compact('assignment', 'ticket', 'canViewContact', 'user'));
    }

    /**
     * Aksi 1: Terima Tugas (Accept)
     */
    public function acceptTask(AcceptAssignmentRequest $request, $id)
    {
        $user = Auth::user();
        /** @var TicketAssignment $assignment */
        $assignment = TicketAssignment::findOrFail($id);
        $this->authorize('accept', $assignment);

        $this->workflowService->acceptAssignment($assignment, $user, $request->input('row_version'));

        return back()->with('success', 'Penugasan berhasil diterima. Silakan menuju lokasi dan mulai penanganan kendala.');
    }

    /**
     * Aksi 2: Tolak Tugas (Decline)
     */
    public function declineTask(DeclineAssignmentRequest $request, $id)
    {
        $user = Auth::user();
        /** @var TicketAssignment $assignment */
        $assignment = TicketAssignment::findOrFail($id);
        $this->authorize('decline', $assignment);

        $this->workflowService->declineAssignment(
            $assignment,
            $request->input('alasan'),
            $user,
            $request->input('row_version')
        );

        return redirect()->route('teknisi.tugas.index')
            ->with('success', 'Penugasan telah ditolak dan tiket dikembalikan ke antrean Admin Bidang.');
    }

    /**
     * Aksi 3: Tambah Catatan Progres Lapangan (Worklog)
     */
    public function addWorklog(AddWorklogRequest $request, $id)
    {
        $user = Auth::user();
        /** @var TicketAssignment $assignment */
        $assignment = TicketAssignment::with('ticket')->findOrFail($id);
        $this->authorize('addProgress', $assignment);

        $this->workflowService->addProgress(
            $assignment->ticket,
            $assignment,
            $request->input('catatan'),
            $user,
            $request->file('berkas'),
            $request->input('row_version')
        );

        return back()->with('success', 'Catatan progres lapangan berhasil ditambahkan ke timeline.');
    }

    /**
     * Aksi 4: Laporkan Hambatan (Blocker)
     * Kebijakan Ketat: Jam SLA Tetap Berjalan.
     */
    public function reportBlocker(ReportBlockerRequest $request, $id)
    {
        $user = Auth::user();
        /** @var TicketAssignment $assignment */
        $assignment = TicketAssignment::with('ticket')->findOrFail($id);
        $this->authorize('reportBlocker', $assignment);

        $this->workflowService->reportBlocker(
            $assignment->ticket,
            $assignment,
            $request->validated(),
            $user,
            $request->file('berkas_bukti'),
            $request->input('row_version')
        );

        return back()->with('success', 'Hambatan lapangan berhasil dilaporkan ke Admin Bidang & Pusat. Sesuai kebijakan SLA, jam target tetap berjalan.');
    }

    /**
     * Aksi 5: Selesaikan Hambatan (Resolve Blocker)
     */
    public function resolveBlocker(Request $request, $id, $blockerId)
    {
        $user = Auth::user();
        /** @var TicketAssignment $assignment */
        $assignment = TicketAssignment::findOrFail($id);
        $this->authorize('view', $assignment);

        /** @var TicketBlocker $blocker */
        $blocker = TicketBlocker::where('id', $blockerId)
            ->where('assignment_id', $assignment->id)
            ->firstOrFail();

        $this->workflowService->resolveBlocker($blocker, $user);

        return back()->with('success', 'Hambatan telah ditandai selesai (Resolved). Silakan lanjutkan pengerjaan teknis.');
    }

    /**
     * Aksi 6: Ajukan Selesai (Submit for Review)
     * Dilarang self-close; tiket tetap in_progress hingga disetujui Admin Bidang.
     */
    public function submitForReview(SubmitReviewRequest $request, $id)
    {
        $user = Auth::user();
        /** @var TicketAssignment $assignment */
        $assignment = TicketAssignment::with('ticket')->findOrFail($id);
        $this->authorize('submit', $assignment);

        $this->workflowService->submitForReview(
            $assignment->ticket,
            $assignment,
            $request->input('catatan'),
            $user,
            $request->file('bukti_foto'),
            $request->input('row_version')
        );

        return back()->with('success', 'Hasil pekerjaan berhasil diselesaikan! Tiket kini berstatus Selesai (Resolved) dan langsung diteruskan kepada pelapor untuk konfirmasi.');
    }

    /**
     * Halaman Riwayat: Penugasan Selesai & Ulasan/Rating Pelapor
     */
    public function riwayat(Request $request)
    {
        $user = Auth::user();
        $query = TicketAssignment::with(['ticket.category', 'ticket.bidang'])
            ->where('user_id', $user->id)
            ->where('status', 'approved');

        if ($request->filled('rating') && is_numeric($request->input('rating'))) {
            $r = (int) $request->input('rating');
            $query->whereHas('ticket', fn($t) => $t->where('rating', $r));
        }

        $completedAssignments = $query->orderBy('approved_at', 'desc')->paginate(10)->withQueryString();

        return view('teknisi.riwayat.index', compact('completedAssignments', 'user'));
    }

    /**
     * Halaman Profil, Ketersediaan & Statistik KPI Teknisi
     */
    public function profil()
    {
        $user = Auth::user();
        if ($user) {
            $user->loadMissing(['technicianProfile', 'bidang', 'role']);
        }

        $profile = $user->technicianProfile ?: $user->technicianProfile()->create([
            'availability' => 'tersedia',
            'max_load' => 6,
            'skills' => ['Jaringan & FO', 'Troubleshooting PC', 'Sistem Informasi'],
        ]);

        $stats = $this->statsService->calculateTechnicianStats($user);

        return view('teknisi.profil.index', compact('user', 'profile', 'stats'));
    }

    /**
     * Pembaruan Status Ketersediaan (Tersedia, Sibuk, Cuti)
     */
    public function updateAvailability(UpdateAvailabilityRequest $request)
    {
        $user = Auth::user();
        $cutiUntil = $request->filled('cuti_until') ? Carbon::parse($request->input('cuti_until')) : null;

        $this->workflowService->updateAvailability(
            $user,
            $request->input('availability'),
            $cutiUntil
        );

        return back()->with('success', 'Status ketersediaan Anda berhasil diperbarui.');
    }

    /**
     * Pembaruan Kata Sandi Mandiri Akun Teknisi
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => 'Kata sandi lama wajib diisi.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.min' => 'Kata sandi baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak sesuai.',
        ]);

        $user = Auth::user();
        if (!Hash::check($request->input('current_password'), $user->password)) {
            return back()->withErrors(['current_password' => 'Kata sandi saat ini tidak sesuai.']);
        }

        $user->password = Hash::make($request->input('password'));
        $user->save();

        AuditLog::create([
            'user_id' => $user->id,
            'aksi' => 'UBAH_PASSWORD_TEKNISI',
            'target_kode' => $user->username,
            'ip_address' => $request->ip() ?? '127.0.0.1',
            'detail' => "Teknisi {$user->nama} memperbarui kata sandi akun secara mandiri.",
            'timestamp' => Carbon::now(),
        ]);

        return back()->with('success', 'Kata sandi akun Anda berhasil diperbarui.');
    }
}
