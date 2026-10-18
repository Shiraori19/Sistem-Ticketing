<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bidang;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SlaMonitoringController extends Controller
{
    /**
     * Dashboard Pengawasan & Monitoring SLA Terpadu
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $isPusat = $user->isAdminPusat();
        $userBidangId = $user->isAdminBidang() ? $user->bidang_id : null;
        $selectedBidangId = $isPusat ? $request->input('bidang_id') : $userBidangId;

        // 1. Hitung Ringkasan Metrik SLA (Read-Only)
        $baseQuery = Ticket::query()
            ->when($selectedBidangId, fn($q) => $q->where('bidang_id', $selectedBidangId));

        $totalActive = (clone $baseQuery)
            ->whereIn('status', ['verified', 'in_progress', 'reopened'])
            ->whereNotNull('sla_dimulai_at')
            ->count();

        $activeBreachCount = (clone $baseQuery)
            ->whereIn('status', ['verified', 'in_progress', 'reopened'])
            ->where(function ($q) {
                $q->where('sla_breach', true)
                  ->orWhere(function ($sub) {
                      $sub->whereNotNull('sla_deadline_at')
                          ->where('sla_deadline_at', '<=', Carbon::now());
                  });
            })
            ->count();

        // Hitung Kritis (<20% sisa waktu namun belum breach) via query teroptimasi
        $isSqlite = DB::getDriverName() === 'sqlite';
        $now = Carbon::now();

        if ($isSqlite) {
            $activeTicketsForCalc = (clone $baseQuery)
                ->whereIn('status', ['verified', 'in_progress', 'reopened'])
                ->whereNotNull('sla_dimulai_at')
                ->where('sla_breach', false)
                ->get(['sla_dimulai_at', 'sla_resolusi_jam', 'sla_deadline_at']);

            $criticalCount = 0;
            $safeCount = 0;
            foreach ($activeTicketsForCalc as $t) {
                $totalMins = max(1, ((float)($t->sla_resolusi_jam ?: 336)) * 60);
                $deadline = $t->sla_deadline_at ? Carbon::parse($t->sla_deadline_at) : Carbon::parse($t->sla_dimulai_at)->addMinutes($totalMins);
                $minsLeft = $now->diffInMinutes($deadline, false);

                if ($minsLeft > 0) {
                    $pctLeft = ($minsLeft / $totalMins) * 100;
                    if ($pctLeft < 20) {
                        $criticalCount++;
                    } else {
                        $safeCount++;
                    }
                }
            }
        } else {
            // PostgreSQL: 1 Single Aggregation Query teroptimasi (O(1) Memory Overhead)
            $calc = (clone $baseQuery)
                ->whereIn('status', ['verified', 'in_progress', 'reopened'])
                ->where('sla_breach', false)
                ->whereNotNull('sla_dimulai_at')
                ->selectRaw("
                    COUNT(CASE 
                        WHEN (COALESCE(sla_deadline_at, sla_dimulai_at + (COALESCE(sla_resolusi_jam, 336) || ' hours')::interval)) > NOW() 
                         AND NOW() >= (sla_dimulai_at + ((COALESCE(sla_resolusi_jam, 336) * 0.8) || ' hours')::interval) 
                        THEN 1 END) as critical_count,
                    COUNT(CASE 
                        WHEN (COALESCE(sla_deadline_at, sla_dimulai_at + (COALESCE(sla_resolusi_jam, 336) || ' hours')::interval)) > NOW() 
                         AND NOW() < (sla_dimulai_at + ((COALESCE(sla_resolusi_jam, 336) * 0.8) || ' hours')::interval) 
                        THEN 1 END) as safe_count
                ")->first();

            $criticalCount = (int) ($calc->critical_count ?? 0);
            $safeCount = (int) ($calc->safe_count ?? 0);
        }

        // Kepatuhan Menyeluruh (Memperhitungkan tiket selesai dan tiket aktif)
        $totalResolved = (clone $baseQuery)
            ->whereIn('status', ['resolved', 'closed', 'force_closed'])
            ->count();

        $resolvedOnTime = (clone $baseQuery)
            ->whereIn('status', ['resolved', 'closed', 'force_closed'])
            ->where('sla_breach', false)
            ->count();

        $totalEvaluated = $totalResolved + $totalActive;
        $totalCompliant = $resolvedOnTime + $safeCount;

        $complianceRate = $totalEvaluated > 0 
            ? round(($totalCompliant / $totalEvaluated) * 100, 1) 
            : 100.0;

        $stats = [
            'total_active' => $totalActive,
            'active_breach' => $activeBreachCount,
            'critical' => $criticalCount,
            'safe' => $safeCount,
            'total_resolved' => $totalResolved,
            'resolved_on_time' => $resolvedOnTime,
            'total_evaluated' => $totalEvaluated,
            'compliance_rate' => $complianceRate,
        ];

        // 2. Scorecard Evaluasi Kepatuhan per Bidang (Untuk Admin Pusat)
        $bidangScorecards = [];
        $bidangList = Bidang::all();

        if ($isPusat) {
            $nowSql = $isSqlite ? 'CURRENT_TIMESTAMP' : 'NOW()';
            $metrics = Ticket::selectRaw("
                bidang_id,
                COUNT(*) as total,
                COUNT(CASE WHEN status IN ('verified', 'in_progress', 'reopened') THEN 1 END) as active,
                COUNT(CASE WHEN status IN ('verified', 'in_progress', 'reopened') AND (sla_breach = true OR (sla_deadline_at IS NOT NULL AND sla_deadline_at <= {$nowSql})) THEN 1 END) as active_breach,
                COUNT(CASE WHEN status IN ('resolved', 'closed', 'force_closed') THEN 1 END) as completed,
                COUNT(CASE WHEN status IN ('resolved', 'closed', 'force_closed') AND sla_breach = false THEN 1 END) as completed_on_time,
                COUNT(CASE WHEN status IN ('resolved', 'closed', 'force_closed') AND sla_breach = true THEN 1 END) as completed_breach
            ")->groupBy('bidang_id')->get()->keyBy('bidang_id');

            foreach ($bidangList as $b) {
                $m = $metrics->get($b->id);
                $bTotal = (int) ($m?->total ?? 0);
                $bActive = (int) ($m?->active ?? 0);
                $bActiveBreach = (int) ($m?->active_breach ?? 0);
                $bCompleted = (int) ($m?->completed ?? 0);
                $bCompletedOnTime = (int) ($m?->completed_on_time ?? 0);
                $bCompletedBreach = (int) ($m?->completed_breach ?? 0);
                $bBreach = $bActiveBreach + $bCompletedBreach;

                $bEvaluated = $bCompleted + $bActive;
                $bSafe = max(0, $bActive - $bActiveBreach);
                $bCompliant = $bCompletedOnTime + $bSafe;
                $bRate = $bEvaluated > 0 ? round(($bCompliant / $bEvaluated) * 100, 1) : 100.0;

                $predikat = '🟢 Sangat Baik';
                $predikatClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                if ($bRate < 75) {
                    $predikat = '🔴 Perlu Evaluasi';
                    $predikatClass = 'bg-rose-50 text-rose-700 border-rose-200';
                } elseif ($bRate < 90) {
                    $predikat = '🟡 Perlu Perhatian';
                    $predikatClass = 'bg-amber-50 text-amber-800 border-amber-200';
                }

                $bidangScorecards[] = [
                    'id' => $b->id,
                    'nama_bidang' => $b->nama_bidang,
                    'kode' => $b->kode,
                    'total' => $bTotal,
                    'active' => $bActive,
                    'breach' => $bBreach,
                    'completed' => $bCompleted,
                    'completed_on_time' => $bCompletedOnTime,
                    'compliance_rate' => $bRate,
                    'predikat' => $predikat,
                    'predikatClass' => $predikatClass,
                ];
            }
        }

        // 3. Daftar Radar Tiket Berjalan dengan Prioritas Urgensi
        $ticketQuery = Ticket::with(['category', 'bidang', 'verifier'])
            ->when($selectedBidangId, fn($q) => $q->where('bidang_id', $selectedBidangId))
            ->whereIn('status', ['verified', 'in_progress', 'reopened']);

        // Filter berdasarkan Status SLA
        $statusSlaFilter = $request->input('status_sla', 'semua');
        if ($statusSlaFilter === 'breach') {
            $ticketQuery->where(function ($q) {
                $q->where('sla_breach', true)
                  ->orWhereRaw("sla_dimulai_at IS NOT NULL AND (COALESCE(sla_deadline_at, sla_dimulai_at + (COALESCE(sla_resolusi_jam, 336) || ' hours')::interval)) <= NOW()");
            });
        } elseif ($statusSlaFilter === 'aman') {
            $ticketQuery->where('sla_breach', false)
                ->whereRaw("sla_dimulai_at IS NOT NULL AND (COALESCE(sla_deadline_at, sla_dimulai_at + (COALESCE(sla_resolusi_jam, 336) || ' hours')::interval)) > NOW()");
        }

        // Pencarian Kata Kunci
        if ($request->filled('q')) {
            $q = trim($request->input('q'));
            $ticketQuery->where(function ($sub) use ($q) {
                $sub->where('kode_tiket', 'LIKE', "%{$q}%")
                    ->orWhere('nama_pelapor', 'LIKE', "%{$q}%")
                    ->orWhere('judul', 'LIKE', "%{$q}%")
                    ->orWhere('teknisi_lapangan', 'LIKE', "%{$q}%");
            });
        }

        // Urutkan: Yang breach dan yang deadline paling dekat muncul di atas
        $radarTickets = $ticketQuery
            ->orderByRaw("
                CASE 
                    WHEN sla_breach = true THEN 1
                    WHEN sla_dimulai_at IS NOT NULL AND (COALESCE(sla_deadline_at, sla_dimulai_at + (COALESCE(sla_resolusi_jam, 336) || ' hours')::interval)) <= NOW() THEN 1
                    WHEN sla_dimulai_at IS NOT NULL THEN 2
                    ELSE 3
                END ASC
            ")
            ->orderByRaw("(COALESCE(sla_deadline_at, sla_dimulai_at + (COALESCE(sla_resolusi_jam, 336) || ' hours')::interval)) ASC")
            ->paginate(15)
            ->withQueryString();

        return view('admin.sla.index', compact(
            'stats',
            'bidangScorecards',
            'radarTickets',
            'bidangList',
            'user',
            'isPusat',
            'selectedBidangId',
            'statusSlaFilter'
        ));
    }
}
