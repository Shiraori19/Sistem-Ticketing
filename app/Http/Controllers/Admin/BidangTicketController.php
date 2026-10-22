<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResolveTicketRequest;
use App\Models\Ticket;
use App\Models\User;
use App\Services\AssignmentWorkflowService;
use App\Services\TicketWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BidangTicketController extends Controller
{
    protected TicketWorkflowService $workflowService;
    protected AssignmentWorkflowService $assignmentWorkflowService;
    protected \App\Services\SecureUploadService $uploadService;

    public function __construct(
        TicketWorkflowService $workflowService,
        AssignmentWorkflowService $assignmentWorkflowService,
        \App\Services\SecureUploadService $uploadService
    ) {
        $this->workflowService = $workflowService;
        $this->assignmentWorkflowService = $assignmentWorkflowService;
        $this->uploadService = $uploadService;
    }

    /**
     * Tampilkan tiket khusus bidang yang ditangani Admin Bidang yang login
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $bidangId = $user->bidang_id;
        $isPersandian = $user->bidang?->isPersandian() ?? false;
        $filter = $request->input('filter');

        // Query antrean bidang
        $query = Ticket::with([
                'category:id,nama_kategori',
                'bidang:id,nama_bidang,kode',
                'parent:id,kode_tiket,judul,teknisi_lapangan',
                'children:id,parent_id,kode_tiket,judul,nama_pelapor,skpd,status,teknisi_lapangan',
                'assignments'
            ]);

        // FLOW-04: Pisahkan siaran TLP RED ke tab 'siaran_siber' untuk bidang non-Persandian
        if ($filter === 'siaran_siber') {
            $query->where('tlp_level', 'RED');
        } else {
            if ($isPersandian) {
                $query->where(function ($sub) use ($bidangId) {
                    $sub->where('bidang_id', $bidangId)
                        ->orWhere('tlp_level', 'RED');
                });
            } else {
                // Non-Persandian: Antrean operasional murni hanya tugas bidang bersangkutan
                $query->where('bidang_id', $bidangId);
            }
        }

        // Admin Bidang HANYA melihat tiket yang SUDAH resmi didisposisikan
        $query->whereIn('status', ['verified', 'in_progress', 'resolved', 'reopened', 'closed', 'force_closed', 'eskalasi_pusat']);

        // Filter tab status internal bidang
        if ($filter === 'antrean') {
            $query->whereIn('status', ['verified', 'reopened']);
        } elseif ($filter === 'pengerjaan') {
            $query->where('status', 'in_progress');
        } elseif ($filter === 'arsip') {
            $query->whereIn('status', ['resolved', 'closed', 'force_closed']);
        } elseif ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('q')) {
            $q = trim($request->input('q'));
            // DB-01: Gunakan ILIKE untuk pencarian case-insensitive pada PostgreSQL
            $query->where(function ($sub) use ($q) {
                $sub->where('kode_tiket', 'ILIKE', "%{$q}%")
                    ->orWhere('nama_pelapor', 'ILIKE', "%{$q}%")
                    ->orWhere('judul', 'ILIKE', "%{$q}%")
                    ->orWhere('teknisi_lapangan', 'ILIKE', "%{$q}%");
            });
        } else {
            // Smart Incident Grouping: Secara default lipat tiket anak ke dalam Induk Tiket
            // agar antrean bidang tidak dibanjiri puluhan tiket kendala yang sama.
            $showAll = $request->boolean('tampilkan_anak', false);
            if (!$showAll) {
                $query->whereNull('parent_id');
            }
        }

        $tickets = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();
        $staffUsers = User::where('bidang_id', $bidangId)->pluck('nama')->toArray();
        $showAll = $request->boolean('tampilkan_anak', false);

        // Hitung total tiket anak yang terlipat rapi di antrean bidang ini
        $foldedChildrenCount = Ticket::where(function ($sub) use ($bidangId, $isPersandian) {
                $sub->where('bidang_id', $bidangId);
                if ($isPersandian) {
                    $sub->orWhere('tlp_level', 'RED');
                }
            })
            ->whereNotNull('parent_id')
            ->whereIn('status', ['verified', 'in_progress'])
            ->count();

        $countSiaranSiber = 0;
        if (!$isPersandian) {
            $countSiaranSiber = Ticket::where('tlp_level', 'RED')
                ->where('bidang_id', '!=', $bidangId)
                ->whereIn('status', ['verified', 'in_progress', 'reopened'])
                ->count();
        }

        // Untuk Bidang Persandian: ambil seluruh teknisi lintas bidang agar dapat memilih teknisi bidang lain untuk tiket TLP RED
        $techniciansWithWorkload = $this->workflowService->getTechniciansWithWorkload($isPersandian ? null : $bidangId);

        return view('admin.bidang.index', compact('tickets', 'user', 'staffUsers', 'showAll', 'foldedChildrenCount', 'techniciansWithWorkload', 'isPersandian', 'countSiaranSiber'));
    }

    /**
     * Penunjukan / disposisi teknisi lapangan terhubung akun & beban kerja
     */
    public function assignTechnician(Request $request, int $id)
    {
        $ticket = Ticket::findOrFail($id);
        $user = Auth::user();

        $isPersandian = $user->bidang && (strtoupper($user->bidang->kode) === 'PERSANDIAN' || str_contains(strtoupper($user->bidang->nama_bidang), 'PERSANDIAN'));

        // Proteksi TLP RED: Hanya Bidang Persandian & CSIRT yang berhak menugaskan teknisi
        if (strtoupper($ticket->tlp_level ?? '') === 'RED' && !$isPersandian) {
            abort(403, 'Akses Ditolak: Tiket berklasifikasi TLP: RED disiarkan untuk koordinasi, penanganan teknis mutlak wewenang Bidang Persandian & CSIRT.');
        }

        // Validasi kepemilikan bidang untuk non-RED
        if (strtoupper($ticket->tlp_level ?? '') !== 'RED' && $ticket->bidang_id !== $user->bidang_id) {
            abort(403, 'Akses Ditolak: Tiket ini bukan kewenangan bidang Anda.');
        }

        // Ambil lead_user_id (wajib akun terdaftar)
        $leadId = $request->input('lead_user_id') ?: $request->input('teknisi_utama');
        if (empty($leadId) || $leadId === 'CUSTOM') {
            return back()->withErrors(['lead_user_id' => 'Koordinator Teknisi (Lead PIC) wajib dipilih dari daftar akun teknisi yang tersedia.'])->withInput();
        }

        // Anggota tim / pendamping (opsional)
        $rawMembers = $request->input('member_user_ids', $request->input('anggota_tim', []));
        if (!is_array($rawMembers)) {
            $rawMembers = !empty($rawMembers) ? [$rawMembers] : [];
        }
        $memberIds = array_values(array_unique(array_filter(array_map(function ($m) {
            return is_numeric($m) ? (int)$m : null;
        }, $rawMembers))));

        $overrideReason = trim((string) $request->input('override_reason', ''));

        try {
            $this->workflowService->assignTechnician(
                $ticket,
                is_numeric($leadId) ? (int) $leadId : $leadId,
                $user,
                is_numeric($leadId) ? (int) $leadId : null,
                'lead_pic',
                $memberIds,
                $overrideReason ?: null
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        $leadName = $ticket->lead_technician_name ?: 'Teknisi';
        $memberCount = count($memberIds);
        $msg = $memberCount > 0 
            ? "Tim teknisi ({$leadName} [Lead] & {$memberCount} pendamping) berhasil ditugaskan untuk tiket {$ticket->kode_tiket}."
            : "Teknisi lapangan {$leadName} berhasil ditugaskan untuk tiket {$ticket->kode_tiket}.";

        return back()->with('success', $msg);
    }

    /**
     * Tandai tiket selesai pengerjaan (Bukti resolusi bersifat opsional)
     */
    public function markResolved(ResolveTicketRequest $request, int $id)
    {
        $ticket = Ticket::findOrFail($id);
        $user = Auth::user();

        $isPersandian = $user->bidang && (strtoupper($user->bidang->kode) === 'PERSANDIAN' || str_contains(strtoupper($user->bidang->nama_bidang), 'PERSANDIAN'));

        // Proteksi TLP RED: Hanya Bidang Persandian & CSIRT yang berhak menyelesaikan tiket TLP RED
        if (strtoupper($ticket->tlp_level ?? '') === 'RED' && !$isPersandian) {
            abort(403, 'Akses Ditolak: Tiket berklasifikasi TLP: RED hanya dapat diselesaikan oleh Bidang Persandian & CSIRT.');
        }

        // Validasi kepemilikan bidang untuk non-RED
        if (strtoupper($ticket->tlp_level ?? '') !== 'RED' && $ticket->bidang_id !== $user->bidang_id) {
            abort(403, 'Akses Ditolak: Tiket ini bukan kewenangan bidang Anda.');
        }

        if (!in_array($ticket->status, ['verified', 'in_progress', 'reopened'])) {
            return back()->with('error', "Tiket dengan status '{$ticket->status}' tidak dapat ditandai selesai.");
        }

        // Simpan file bukti resolusi jika ada (opsional) melalui SecureUploadService
        $evidencePath = null;
        $isConfidential = $request->boolean('is_confidential_evidence') || (strtoupper($ticket->tlp_level ?? '') === 'RED');

        if ($request->hasFile('bukti_resolusi')) {
            $uploadResult = $this->uploadService->storeUploadedFile(
                $request->file('bukti_resolusi'),
                "tickets/{$ticket->kode_tiket}",
                [
                    'ticket' => $ticket,
                    'classification' => $isConfidential ? 'rahasia' : 'umum',
                    'kategori_field' => 'bukti_resolusi',
                    'allowed_categories' => ['image', 'pdf'],
                    'uploader_id' => $user->id,
                ]
            );
            $evidencePath = $uploadResult['path'];
        }

        $this->workflowService->markResolved(
            $ticket,
            $request->input('catatan_resolusi'),
            $evidencePath,
            $isConfidential,
            $user
        );

        return back()->with('success', "Tiket {$ticket->kode_tiket} berhasil ditandai selesai (Resolved) dan menunggu konfirmasi pelapor.");
    }

    /**
     * Pengembalian tiket salah disposisi ke Admin Pusat
     */
    public function returnToPusat(Request $request, int $id)
    {
        $request->validate([
            'alasan' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'alasan.required' => 'Alasan pengembalian / rekomendasi bidang tujuan wajib diisi.',
            'alasan.min' => 'Alasan pengembalian minimal 10 karakter.',
        ]);

        $ticket = Ticket::findOrFail($id);
        $user = Auth::user();

        $isPersandian = $user->bidang && (strtoupper($user->bidang->kode) === 'PERSANDIAN' || str_contains(strtoupper($user->bidang->nama_bidang), 'PERSANDIAN'));

        if (strtoupper($ticket->tlp_level ?? '') === 'RED') {
            if (!$isPersandian) {
                abort(403, 'Akses Ditolak: Tiket berklasifikasi TLP: RED hanya dapat dikelola oleh Bidang Persandian & CSIRT.');
            }
        } elseif ($ticket->bidang_id !== $user->bidang_id) {
            abort(403, 'Akses Ditolak: Tiket ini bukan kewenangan bidang Anda.');
        }

        if (!in_array($ticket->status, ['verified', 'in_progress', 'reopened'])) {
            return back()->with('error', "Tiket dengan status '{$ticket->status}' tidak dapat dikembalikan.");
        }

        $this->workflowService->returnToPusat($ticket, $request->input('alasan'), $user);

        return back()->with('success', "Tiket {$ticket->kode_tiket} berhasil dikembalikan ke Admin Pusat untuk disposisi ulang.");
    }

    /**
     * Lepaskan tiket anak dari Induk Tiket (Pecah/Unlink menjadi tiket mandiri)
     */
    public function unlinkParent(Request $request, int $id)
    {
        $request->validate([
            'alasan' => ['required', 'string', 'min:3', 'max:500'],
        ], [
            'alasan.required' => 'Alasan pelepasan tiket dari Induk Tiket wajib dicantumkan.',
            'alasan.min' => 'Alasan pelepasan minimal 3 karakter.',
        ]);

        $ticket = Ticket::findOrFail($id);
        if (!$ticket->parent_id) {
            return back()->with('error', "Tiket {$ticket->kode_tiket} bukan merupakan tiket anak.");
        }

        $user = Auth::user();
        $isPersandian = $user->bidang && (strtoupper($user->bidang->kode) === 'PERSANDIAN' || str_contains(strtoupper($user->bidang->nama_bidang ?? ''), 'PERSANDIAN'));

        if (strtoupper($ticket->tlp_level ?? '') === 'RED') {
            if (!$isPersandian) {
                abort(403, 'Akses Ditolak: Tiket berklasifikasi TLP: RED hanya dapat dikelola oleh Bidang Persandian & CSIRT.');
            }
        } elseif ($ticket->bidang_id !== $user->bidang_id) {
            abort(403, 'Akses Ditolak: Tiket ini bukan kewenangan bidang Anda.');
        }

        $this->workflowService->unlinkFromParent($ticket, $request->input('alasan'), $user);

        return back()->with('success', "Tiket {$ticket->kode_tiket} berhasil dipecah/dilepaskan dari Induk Tiket menjadi tiket mandiri.");
    }

    /**
     * Review pengajuan penyelesaian teknisi (Setujui atau Kembalikan)
     */
    public function reviewSubmission(Request $request, int $id)
    {
        $request->validate([
            'action' => ['required', 'in:approve,return'],
            'review_notes' => ['nullable', 'string'],
        ]);

        $ticket = Ticket::findOrFail($id);
        $user = Auth::user();

        // Validasi kepemilikan bidang
        if (strtoupper($ticket->tlp_level ?? '') !== 'RED' && $ticket->bidang_id !== $user->bidang_id) {
            abort(403, 'Akses Ditolak: Tiket ini bukan kewenangan bidang Anda.');
        }

        $assignment = $ticket->assignments()->where('status', 'submitted')->latest()->first()
            ?: $ticket->assignments()->latest()->first();

        if (!$assignment) {
            return back()->with('error', 'Tidak ada data penugasan teknisi yang menunggu review.');
        }

        $action = $request->input('action');
        $notes = $request->input('review_notes') ?: ($action === 'approve' ? 'Hasil perbaikan disetujui oleh Admin Bidang.' : 'Perbaikan perlu disempurnakan.');

        if ($action === 'approve') {
            $this->assignmentWorkflowService->approveSubmission($ticket, $assignment, $notes, $user);
            $msg = "Pekerjaan teknisi disetujui. Tiket #{$ticket->kode_tiket} resmi berstatus RESOLVED.";
        } else {
            $this->assignmentWorkflowService->returnSubmission($ticket, $assignment, $notes, $user);
            $msg = "Tiket #{$ticket->kode_tiket} dikembalikan ke teknisi dengan catatan revisi.";
        }

        return back()->with('success', $msg);
    }
}

