<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bidang;
use App\Models\Category;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    /**
     * Tampilkan seluruh tiket dengan multi-filter
     */
    public function index(Request $request)
    {
        $query = Ticket::query()
            ->select([
                'id', 'is_parent', 'parent_id', 'kode_tiket', 'nama_pelapor', 'skpd', 'judul',
                'category_id', 'bidang_id', 'tlp_level', 'status',
                'sla_breach', 'sla_dimulai_at', 'sla_resolusi_jam', 'created_at'
            ])
            ->with(['category:id,nama_kategori', 'bidang:id,nama_bidang', 'parent:id,kode_tiket']);

        // Filter Status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter Bidang
        if ($request->filled('bidang_id')) {
            $query->where('bidang_id', $request->input('bidang_id'));
        }

        // Filter TLP Level
        if ($request->filled('tlp_level')) {
            $query->where('tlp_level', $request->input('tlp_level'));
        }

        // Filter SLA Breach
        if ($request->filled('sla_breach')) {
            $query->where('sla_breach', $request->boolean('sla_breach'));
        }

        // Keyword Search (kode_tiket, nama_pelapor, nip, judul, skpd)
        if ($request->filled('q')) {
            $q = trim($request->input('q'));
            $query->where(function ($sub) use ($q) {
                $sub->where('kode_tiket', 'ILIKE', "%{$q}%")
                    ->orWhere('nama_pelapor', 'ILIKE', "%{$q}%")
                    ->orWhere('nip_pelapor', 'ILIKE', "%{$q}%")
                    ->orWhere('judul', 'ILIKE', "%{$q}%")
                    ->orWhere('skpd', 'ILIKE', "%{$q}%");
            });
        }

        $tickets = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        $bidangList = \Illuminate\Support\Facades\Cache::remember('dropdown_bidang_list', 60, function () {
            return Bidang::select(['id', 'nama_bidang', 'kode'])->get();
        });

        $categories = \Illuminate\Support\Facades\Cache::remember('dropdown_category_list', 60, function () {
            return Category::select(['id', 'nama_kategori'])->get();
        });

        return view('admin.tiket.index', compact('tickets', 'bidangList', 'categories'));
    }

    /**
     * Detail tiket lengkap beserta timeline status_history
     */
    public function show(int $id)
    {
        $ticket = Ticket::with([
            'category',
            'bidang',
            'verifier',
            'assigner',
            'parent:id,kode_tiket,judul',
            'children:id,parent_id,kode_tiket,judul,skpd,nama_pelapor,status',
            'assignments'
        ])->findOrFail($id);
        $user = Auth::user();

        // Otorisasi Hak Akses Tiket: Admin Bidang hanya berhak mengakses tiket yang telah didisposisikan ke bidangnya (kecuali TLP RED)
        if ($user->isAdminBidang() && strtoupper($ticket->tlp_level ?? '') !== 'RED') {
            if ($ticket->bidang_id !== $user->bidang_id || in_array($ticket->status, ['pending_verification', 'pending_info', 'cancelled'])) {
                abort(403, 'Akses Ditolak: Tiket ini belum didisposisikan ke bidang Anda.');
            }
        }

        // Cek kerahasiaan bukti (Confidential Evidence)
        $canViewConfidential = $user->isAdminPusat() || ($user->isAdminBidang() && $user->bidang_id === $ticket->bidang_id);

        return view('admin.tiket.show', compact('ticket', 'canViewConfidential'));
    }

    /**
     * Rekap Laporan Pengaduan & SLA
     */
    public function laporan(Request $request)
    {
        $bidangList = Bidang::withCount('tickets')->get();

        // FLOW-06: Dukungan filter rentang tanggal pengajuan laporan
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $baseQuery = Ticket::query();
        if ($startDate) {
            $baseQuery->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $baseQuery->whereDate('created_at', '<=', $endDate);
        }

        $totalTickets = (clone $baseQuery)->count();
        $closedTickets = (clone $baseQuery)->whereIn('status', ['closed', 'force_closed', 'resolved'])->count();

        // FLOW-06: Kepatuhan SLA hanya diukur pada tiket yang masuk fase operasional (bukan pending/cancelled)
        $slaEligibleQuery = (clone $baseQuery)->whereNotIn('status', ['pending_verification', 'pending_info', 'cancelled']);
        $totalSlaEligible = $slaEligibleQuery->count();
        $breachedTickets = (clone $slaEligibleQuery)->where('sla_breach', true)->count();
        $complianceRate = $totalSlaEligible > 0 ? round((($totalSlaEligible - $breachedTickets) / $totalSlaEligible) * 100, 1) : 100;

        // Rincian kinerja dan kepatuhan per bidang (1 single query agregasi menggantikan 40+ query)
        $metrics = (clone $baseQuery)->selectRaw("
            bidang_id,
            COUNT(*) as total,
            COUNT(CASE WHEN status IN ('resolved', 'closed', 'force_closed') THEN 1 END) as completed,
            COUNT(CASE WHEN status IN ('verified', 'in_progress', 'reopened') THEN 1 END) as active,
            COUNT(CASE WHEN sla_breach = true THEN 1 END) as breach
        ")->groupBy('bidang_id')->get()->keyBy('bidang_id');

        $bidangTable = Bidang::all()->map(function ($b) use ($metrics) {
            $m = $metrics->get($b->id);
            $total = (int) ($m?->total ?? 0);
            $completed = (int) ($m?->completed ?? 0);
            $active = (int) ($m?->active ?? 0);
            $breach = (int) ($m?->breach ?? 0);
            $eligible = $completed + $active;
            $rate = $eligible > 0 ? round((($eligible - $breach) / $eligible) * 100, 1) : 100;

            return (object) [
                'id' => $b->id,
                'kode' => $b->kode,
                'nama_bidang' => $b->nama_bidang,
                'nama_lengkap' => $b->nama_lengkap ?: $b->nama_bidang,
                'total' => $total,
                'completed' => $completed,
                'active' => $active,
                'breach' => $breach,
                'compliance_rate' => $rate,
            ];
        });

        // Distribusi per TLP (1 Single Aggregation Query)
        $rawTlp = Ticket::selectRaw('tlp_level, count(*) as total')
            ->whereNotNull('tlp_level')
            ->groupBy('tlp_level')
            ->pluck('total', 'tlp_level');

        $tlpStats = [
            'RED' => (int) ($rawTlp['RED'] ?? 0),
            'AMBER' => (int) ($rawTlp['AMBER'] ?? 0),
            'GREEN' => (int) ($rawTlp['GREEN'] ?? 0),
            'CLEAR' => (int) ($rawTlp['CLEAR'] ?? 0),
        ];

        // Top 5 OPD pelapor
        $topOpd = Ticket::selectRaw('skpd, count(*) as total')
            ->groupBy('skpd')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // Metrik Evaluasi Kepuasan Pelapor (Rating 1-5 Bintang - 1 Single Aggregation Query)
        $avgRating = round((float) (Ticket::whereNotNull('rating')->avg('rating') ?: 5.0), 1);
        $totalRated = Ticket::whereNotNull('rating')->count();

        $rawRating = Ticket::selectRaw('rating, count(*) as total')
            ->whereNotNull('rating')
            ->groupBy('rating')
            ->pluck('total', 'rating');

        $ratingDistribution = [
            5 => (int) ($rawRating[5] ?? 0),
            4 => (int) ($rawRating[4] ?? 0),
            3 => (int) ($rawRating[3] ?? 0),
            2 => (int) ($rawRating[2] ?? 0),
            1 => (int) ($rawRating[1] ?? 0),
        ];

        return view('admin.laporan.index', compact(
            'bidangList',
            'bidangTable',
            'totalTickets',
            'closedTickets',
            'breachedTickets',
            'complianceRate',
            'tlpStats',
            'topOpd',
            'avgRating',
            'totalRated',
            'ratingDistribution'
        ));
    }

    /**
     * Jejak Audit Log Sistem (Memanfaatkan Index audit_log_aksi_timestamp_index)
     */
    public function auditLog(Request $request)
    {
        $aksi = (string) $request->input('aksi', '');
        $q = trim((string) $request->input('q', ''));

        $query = \App\Models\AuditLog::with(['user.role'])->orderBy('timestamp', 'desc');

        if (!empty($aksi)) {
            $query->where('aksi', $aksi);
        }

        if (!empty($q)) {
            $query->where(function ($sub) use ($q) {
                $sub->where('aksi', 'LIKE', "%{$q}%")
                    ->orWhere('target_kode', 'LIKE', "%{$q}%")
                    ->orWhere('ip_address', 'LIKE', "%{$q}%")
                    ->orWhere('detail', 'LIKE', "%{$q}%");
            });
        }

        $logs = $query->paginate(20)->withQueryString();

        return view('admin.audit.index', compact('logs'));
    }

    /**
     * Pengecualian SLA Breach oleh Admin Pusat (Excused SLA)
     */
    public function excuseSla(Request $request, int $id)
    {
        $request->validate([
            'alasan' => ['required', 'string', 'min:10'],
            'bukti' => ['nullable', 'string'],
        ], [
            'alasan.required' => 'Alasan pengecualian SLA breach wajib diisi.',
            'alasan.min' => 'Alasan minimal 10 karakter.',
        ]);

        $ticket = Ticket::findOrFail($id);
        $user = Auth::user();

        app(\App\Services\TicketWorkflowService::class)->excuseSlaBreach(
            $ticket,
            $request->input('alasan'),
            $request->input('bukti'),
            $user
        );

        return back()->with('success', "Pengecualian SLA Breach untuk tiket #{$ticket->kode_tiket} berhasil ditetapkan oleh Admin Pusat.");
    }
}
