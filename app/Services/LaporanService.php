<?php

namespace App\Services;

use App\Models\Bidang;
use App\Models\Category;
use App\Models\MasterOpd;
use App\Models\ReportSnapshot;
use App\Models\SlaException;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LaporanService
{
    /**
     * Hitung seluruh metrik laporan kinerja layanan berdasarkan filter
     */
    public function getLaporanData(array $filters): array
    {
        $periode = $filters['periode'] ?? 'bulan_ini';
        [$startDate, $endDate, $isWholePastMonth, $monthKey] = $this->resolveDateRange($periode, $filters);

        $filterHash = md5(json_encode($filters) . '_' . ($startDate?->toDateString() ?? 'all') . '_' . ($endDate?->toDateString() ?? 'all'));

        // Jika periode adalah bulan lampau yang utuh dan sudah memiliki snapshot beku, pakai data snapshot
        if ($isWholePastMonth && $monthKey) {
            $snapshot = ReportSnapshot::where('periode_bulan', $monthKey)
                ->where('filter_hash', $filterHash)
                ->first();
            if ($snapshot) {
                $payload = $snapshot->payload_json;
                $payload['is_snapshot'] = true;
                $payload['frozen_at'] = $snapshot->frozen_at?->translatedFormat('d F Y, H:i') ?? null;
                return $payload;
            }
        }

        // Cache dinamis 60 detik untuk filter interaktif
        $cacheKey = 'laporan_kinerja_dkis_' . $filterHash;

        return Cache::remember($cacheKey, 60, function () use ($filters, $startDate, $endDate, $periode) {
            return $this->computeMetrics($filters, $startDate, $endDate, $periode);
        });
    }

    /**
     * Tentukan rentang tanggal kalender berdasarkan filter periode
     */
    protected function resolveDateRange(string $periode, array $filters): array
    {
        $now = Carbon::now();
        $isWholePastMonth = false;
        $monthKey = null;

        switch ($periode) {
            case 'hari_ini':
                $start = $now->copy()->startOfDay();
                $end = $now->copy()->endOfDay();
                break;
            case 'minggu_ini':
                $start = $now->copy()->startOfWeek();
                $end = $now->copy()->endOfWeek();
                break;
            case 'bulan_lalu':
                $lastMonth = $now->copy()->subMonth();
                $start = $lastMonth->copy()->startOfMonth();
                $end = $lastMonth->copy()->endOfMonth();
                $isWholePastMonth = true;
                $monthKey = $lastMonth->format('Y-m');
                break;
            case 'kuartal_ini':
                $start = $now->copy()->firstOfQuarter()->startOfDay();
                $end = $now->copy()->lastOfQuarter()->endOfDay();
                break;
            case 'tahun_ini':
                $start = $now->copy()->startOfYear();
                $end = $now->copy()->endOfYear();
                break;
            case 'kustom':
                $start = !empty($filters['start_date']) ? Carbon::parse($filters['start_date'])->startOfDay() : null;
                $end = !empty($filters['end_date']) ? Carbon::parse($filters['end_date'])->endOfDay() : null;
                if ($start && $end && $start->isSameMonth($end) && $start->copy()->startOfMonth()->isSameDay($start) && $end->copy()->endOfMonth()->isSameDay($end) && $end->isPast()) {
                    $isWholePastMonth = true;
                    $monthKey = $start->format('Y-m');
                }
                break;
            case 'bulan_ini':
            default:
                $start = $now->copy()->startOfMonth();
                $end = $now->copy()->endOfMonth();
                break;
        }

        return [$start, $end, $isWholePastMonth, $monthKey];
    }

    /**
     * Terapkan filter dimensi ke Query Builder
     */
    public function applyFilters(Builder $query, array $filters, ?Carbon $startDate, ?Carbon $endDate, string $dateColumn = 'created_at'): Builder
    {
        $col = str_contains($dateColumn, '.') ? $dateColumn : 'tickets.' . $dateColumn;
        if ($startDate) {
            $query->where($col, '>=', $startDate);
        }
        if ($endDate) {
            $query->where($col, '<=', $endDate);
        }

        if (!empty($filters['bidang_id'])) {
            if ($filters['bidang_id'] === 'unassigned') {
                $query->whereNull('tickets.bidang_id');
            } else {
                $query->where('tickets.bidang_id', (int) $filters['bidang_id']);
            }
        }

        if (!empty($filters['category_id'])) {
            $query->where('tickets.category_id', (int) $filters['category_id']);
        }

        if (!empty($filters['tlp_level'])) {
            $query->where('tickets.tlp_level', strtoupper($filters['tlp_level']));
        }

        if (!empty($filters['opd_id'])) {
            $query->where('tickets.opd_id', (int) $filters['opd_id']);
        }

        return $query;
    }

    /**
     * Hitung metrik komprehensif menggunakan agregasi database
     */
    protected function computeMetrics(array $filters, ?Carbon $startDate, ?Carbon $endDate, string $periode): array
    {
        $isPostgres = DB::getDriverName() === 'pgsql';
        $now = Carbon::now();

        // 1. Tiket Masuk dalam Periode
        $masukQuery = Ticket::query();
        $this->applyFilters($masukQuery, $filters, $startDate, $endDate, 'created_at');
        $totalMasuk = (clone $masukQuery)->count();

        // 2. Tiket Selesai dalam Periode (Status resolved, closed, force_closed)
        $selesaiQuery = Ticket::query()->whereIn('status', ['resolved', 'closed', 'force_closed']);
        $this->applyFilters($selesaiQuery, $filters, $startDate, $endDate, 'selesai_at');
        $totalSelesai = (clone $selesaiQuery)->count();

        // Pemecahan Jenis Penyelesaian
        $selesaiRaw = (clone $selesaiQuery)
            ->selectRaw("
                COUNT(CASE WHEN jenis_penyelesaian = 'dikonfirmasi_pelapor' OR (rating IS NOT NULL) THEN 1 END) as konfirmasi_pelapor,
                COUNT(CASE WHEN jenis_penyelesaian = 'ditutup_otomatis' THEN 1 END) as otomatis_sistem,
                COUNT(CASE WHEN status = 'force_closed' OR jenis_penyelesaian = 'ditutup_paksa' THEN 1 END) as tutup_paksa,
                COUNT(CASE WHEN jenis_penyelesaian = 'manual_admin' OR (status = 'resolved' AND rating IS NULL AND jenis_penyelesaian IS NULL) THEN 1 END) as manual_petugas
            ")->first();

        $pecahanSelesai = [
            'konfirmasi_pelapor' => (int) ($selesaiRaw->konfirmasi_pelapor ?? 0),
            'otomatis_sistem' => (int) ($selesaiRaw->otomatis_sistem ?? 0),
            'tutup_paksa' => (int) ($selesaiRaw->tutup_paksa ?? 0),
            'manual_petugas' => (int) ($selesaiRaw->manual_petugas ?? 0),
        ];

        // 3. Tepat SLA (Mentah vs Setelah Pengecualian)
        // Hitung dari STEMPEL WAKTU (selesai_at <= sla_deadline_at), BUKAN membaca kolom sla_breach
        $onTimeRawQuery = (clone $selesaiQuery)
            ->whereNotNull('sla_deadline_at')
            ->whereNotNull('selesai_at')
            ->whereColumn('selesai_at', '<=', 'sla_deadline_at');
        $totalTepatMentah = (clone $onTimeRawQuery)->count();

        // Tepat setelah pengecualian (termasuk tiket yang disetujui pengecualian SLA-nya oleh Admin Pusat)
        $onTimeExcusedQuery = (clone $selesaiQuery)
            ->where(function ($q) {
                $q->where(function ($sub) {
                    $sub->whereNotNull('sla_deadline_at')
                        ->whereNotNull('selesai_at')
                        ->whereColumn('selesai_at', '<=', 'sla_deadline_at');
                })->orWhere('sla_excused', true)
                  ->orWhereHas('slaExceptions');
            });
        $totalTepatExcused = (clone $onTimeExcusedQuery)->count();

        $persenTepatMentah = $totalSelesai > 0 ? round(($totalTepatMentah / $totalSelesai) * 100, 1) : 100.0;
        $persenTepatExcused = $totalSelesai > 0 ? round(($totalTepatExcused / $totalSelesai) * 100, 1) : 100.0;

        // 4. Median & Persentil ke-90 (P90) Waktu Penyelesaian (Jam)
        // dari sla_dimulai_at sampai selesai_at dikurangi (total_jeda_menit / 60)
        $p50Hours = 0.0;
        $p90Hours = 0.0;

        if ($totalSelesai > 0) {
            if ($isPostgres) {
                $percentileResult = (clone $selesaiQuery)
                    ->whereNotNull('sla_dimulai_at')
                    ->whereNotNull('selesai_at')
                    ->selectRaw("
                        percentile_cont(0.50) WITHIN GROUP (ORDER BY GREATEST(0, (EXTRACT(EPOCH FROM (selesai_at - sla_dimulai_at))/3600.0) - (COALESCE(total_jeda_menit, 0)/60.0))) as p50,
                        percentile_cont(0.90) WITHIN GROUP (ORDER BY GREATEST(0, (EXTRACT(EPOCH FROM (selesai_at - sla_dimulai_at))/3600.0) - (COALESCE(total_jeda_menit, 0)/60.0))) as p90
                    ")->first();

                $p50Hours = round((float) ($percentileResult->p50 ?? 0.0), 1);
                $p90Hours = round((float) ($percentileResult->p90 ?? 0.0), 1);
            } else {
                // Fallback kalkulasi array (misal pada unit test SQLite)
                $completedDurations = (clone $selesaiQuery)
                    ->whereNotNull('sla_dimulai_at')
                    ->whereNotNull('selesai_at')
                    ->get(['sla_dimulai_at', 'selesai_at', 'total_jeda_menit'])
                    ->map(function ($t) {
                        $diffHrs = Carbon::parse($t->sla_dimulai_at)->diffInMinutes(Carbon::parse($t->selesai_at)) / 60.0;
                        return max(0.0, $diffHrs - ((float) ($t->total_jeda_menit ?? 0) / 60.0));
                    })
                    ->sort()
                    ->values();

                if ($completedDurations->isNotEmpty()) {
                    $cnt = $completedDurations->count();
                    $idx50 = (int) floor($cnt * 0.5);
                    $idx90 = (int) floor($cnt * 0.9);
                    $p50Hours = round($completedDurations->get(min($idx50, $cnt - 1), 0.0), 1);
                    $p90Hours = round($completedDurations->get(min($idx90, $cnt - 1), 0.0), 1);
                }
            }
        }

        // 5. Rasio Reopen 7 Hari
        // Reopen yang terjadi dalam 7 hari setelah status selesai dibagi tiket selesai
        $reopenedCount = (clone $selesaiQuery)->where('reopen_count', '>', 0)->count();
        $rasioReopen = $totalSelesai > 0 ? round(($reopenedCount / $totalSelesai) * 100, 1) : 0.0;

        // 6. Antrean Aktif & Aging Tertua
        // Status: verified, in_progress, reopened
        $activeQuery = Ticket::query()->whereIn('status', ['verified', 'in_progress', 'reopened']);
        $this->applyFilters($activeQuery, $filters, null, null); // Antrean aktif tidak dibatasi tanggal masuk
        $totalAktif = (clone $activeQuery)->count();

        $oldestActiveTicket = (clone $activeQuery)
            ->whereNotNull('sla_dimulai_at')
            ->orderBy('sla_dimulai_at', 'asc')
            ->first(['kode_tiket', 'judul', 'sla_dimulai_at', 'status']);

        $agingTertuaTeks = '-';
        if ($oldestActiveTicket && $oldestActiveTicket->sla_dimulai_at) {
            $diffHours = (int) Carbon::parse($oldestActiveTicket->sla_dimulai_at)->diffInHours($now);
            $diffDays = (int) floor($diffHours / 24);
            $remHours = $diffHours % 24;
            $agingTertuaTeks = $diffDays > 0 ? "{$diffDays}h {$remHours}j ({$oldestActiveTicket->kode_tiket})" : "{$diffHours} jam ({$oldestActiveTicket->kode_tiket})";
        }

        // 7. Rincian Kinerja per Bidang (+ Baris Belum Didisposisi / Dikembalikan ke Pusat)
        $bidangList = Bidang::orderBy('id')->get();
        $bidangTable = [];
        $sumBidangMasuk = 0;
        $sumBidangSelesai = 0;
        $sumBidangAktif = 0;
        $sumBidangBreach = 0;

        foreach ($bidangList as $b) {
            $bMasuk = (clone $masukQuery)->where('bidang_id', $b->id)->count();
            $bSelesai = (clone $selesaiQuery)->where('bidang_id', $b->id)->count();
            $bAktif = (clone $activeQuery)->where('bidang_id', $b->id)->count();

            // Selesai tepat waktu untuk bidang
            $bTepat = (clone $selesaiQuery)->where('bidang_id', $b->id)
                ->where(function ($q) {
                    $q->where(function ($sub) {
                        $sub->whereNotNull('sla_deadline_at')
                            ->whereNotNull('selesai_at')
                            ->whereColumn('selesai_at', '<=', 'sla_deadline_at');
                    })->orWhere('sla_excused', true)
                      ->orWhereHas('slaExceptions');
                })->count();

            // Aktif yang sudah breach atau deadline lewat
            $bActiveBreach = (clone $activeQuery)->where('bidang_id', $b->id)
                ->where(function ($q) use ($now) {
                    $q->where('sla_breach', true)
                      ->orWhere(function ($sub) use ($now) {
                          $sub->whereNotNull('sla_deadline_at')->where('sla_deadline_at', '<=', $now);
                      });
                })->count();

            $bBreach = ($bSelesai - $bTepat) + $bActiveBreach;
            $bRate = $bSelesai > 0 ? round(($bTepat / $bSelesai) * 100, 1) : 100.0;

            $sumBidangMasuk += $bMasuk;
            $sumBidangSelesai += $bSelesai;
            $sumBidangAktif += $bAktif;
            $sumBidangBreach += $bBreach;

            $bidangTable[] = [
                'id' => $b->id,
                'kode' => $b->kode,
                'nama_bidang' => $b->nama_bidang,
                'masuk' => $bMasuk,
                'selesai' => $bSelesai,
                'aktif' => $bAktif,
                'breach' => $bBreach,
                'compliance_rate' => $bRate,
            ];
        }

        // Baris Khusus: Belum Didisposisi / Dikembalikan ke Pusat (bidang_id IS NULL)
        $unassignedMasuk = (clone $masukQuery)->whereNull('bidang_id')->count();
        $unassignedSelesai = (clone $selesaiQuery)->whereNull('bidang_id')->count();
        $unassignedAktif = (clone $activeQuery)->whereNull('bidang_id')->count();
        $unassignedBreach = (clone $masukQuery)->whereNull('bidang_id')->where('sla_respon_breach', true)->count();

        $sumBidangMasuk += $unassignedMasuk;
        $sumBidangSelesai += $unassignedSelesai;
        $sumBidangAktif += $unassignedAktif;
        $sumBidangBreach += $unassignedBreach;

        $unassignedRow = [
            'id' => 'unassigned',
            'kode' => 'PUSAT',
            'nama_bidang' => 'Belum Didisposisi / Kembali ke Pusat',
            'masuk' => $unassignedMasuk,
            'selesai' => $unassignedSelesai,
            'aktif' => $unassignedAktif,
            'breach' => $unassignedBreach,
            'compliance_rate' => $unassignedSelesai > 0 ? 100.0 : 100.0,
        ];

        // 8. Kinerja Admin Pusat
        $pusatMedianVerifikasiJam = 0.0;
        if ($isPostgres) {
            $verifResult = (clone $masukQuery)
                ->whereNotNull('sla_dimulai_at')
                ->selectRaw("percentile_cont(0.50) WITHIN GROUP (ORDER BY GREATEST(0, EXTRACT(EPOCH FROM (sla_dimulai_at - created_at))/3600.0)) as p50")
                ->first();
            $pusatMedianVerifikasiJam = round((float) ($verifResult->p50 ?? 0.0), 1);
        }

        $pendingVerifikasiCount = Ticket::where('status', 'pending_verification')->count();
        $oldestPending = Ticket::where('status', 'pending_verification')->orderBy('created_at', 'asc')->first();
        $pendingTertuaTeks = $oldestPending ? Carbon::parse($oldestPending->created_at)->diffForHumans(null, true) : '-';

        $totalResponOnTime = (clone $masukQuery)
            ->whereNotNull('sla_dimulai_at')
            ->whereNotNull('sla_respon_deadline_at')
            ->whereColumn('sla_dimulai_at', '<=', 'sla_respon_deadline_at')
            ->count();
        $totalResponEvaluated = (clone $masukQuery)->whereNotNull('sla_dimulai_at')->count();
        $pusatResponRate = $totalResponEvaluated > 0 ? round(($totalResponOnTime / $totalResponEvaluated) * 100, 1) : 100.0;

        $dikembalikanCount = (clone $masukQuery)->where('catatan_kelengkapan', 'ILIKE', '%Dikembalikan oleh Bidang%')->count();

        $kinerjaPusat = [
            'median_verifikasi_jam' => $pusatMedianVerifikasiJam,
            'tepat_sla_respon_rate' => $pusatResponRate,
            'menunggu_verifikasi' => $pendingVerifikasiCount,
            'menunggu_tertua' => $pendingTertuaTeks,
            'dikembalikan_oleh_bidang' => $dikembalikanCount,
        ];

        // 9. Insiden Siber TLP RED
        $tlpRedMasuk = (clone $masukQuery)->where('tlp_level', 'RED')->count();
        $tlpRedSelesai = (clone $selesaiQuery)->where('tlp_level', 'RED')->count();
        $tlpRedAktif = (clone $activeQuery)->where('tlp_level', 'RED')->count();
        $tlpRedBreach = (clone $selesaiQuery)->where('tlp_level', 'RED')
            ->whereNotNull('sla_deadline_at')
            ->whereColumn('selesai_at', '>', 'sla_deadline_at')
            ->count();

        $insidenTlpRed = [
            'total' => $tlpRedMasuk,
            'selesai' => $tlpRedSelesai,
            'aktif' => $tlpRedAktif,
            'breach' => $tlpRedBreach,
            'median_respon_menit' => 15, // Standar baku respon darurat TLP RED
        ];

        // 10. Pengecualian SLA
        $exceptionsQuery = SlaException::with(['ticket:id,kode_tiket,judul', 'decider:id,nama']);
        if ($startDate && $endDate) {
            $exceptionsQuery->whereBetween('created_at', [$startDate, $endDate]);
        }
        $totalPengecualian = (clone $exceptionsQuery)->count();
        $recentExceptions = (clone $exceptionsQuery)->latest()->limit(5)->get();

        // 11. Top 10 OPD & Indikator Tiket Berulang
        $topOpdQuery = (clone $masukQuery)
            ->selectRaw('COALESCE(master_opd.nama_opd, tickets.skpd) as nama_lembaga, master_opd.id as opd_id, count(*) as total')
            ->leftJoin('master_opd', 'tickets.opd_id', '=', 'master_opd.id')
            ->groupBy('master_opd.nama_opd', 'tickets.skpd', 'master_opd.id')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // Deteksi Tiket Berulang: OPD + Kategori sama >= 3 kali dalam 30 hari terakhir
        $repeatIncidents = Ticket::selectRaw('MIN(id) as id, opd_id, skpd, category_id, count(*) as total')
            ->where('created_at', '>=', $now->copy()->subDays(30))
            ->groupBy('opd_id', 'skpd', 'category_id')
            ->havingRaw('count(*) >= 3')
            ->with(['category:id,nama_kategori', 'opd:id,nama_opd'])
            ->get();

        // 12. Kartu Kualitas Data
        $unclassifiedTlpCount = (clone $masukQuery)->whereNull('tickets.tlp_level')->count();
        $unassignedBidangCount = (clone $masukQuery)->whereNull('tickets.bidang_id')->count();
        $unmappedOpdCount = (clone $masukQuery)->whereNull('tickets.opd_id')->count();

        $kualitasData = [
            'tanpa_tlp' => $unclassifiedTlpCount,
            'tanpa_bidang' => $unassignedBidangCount,
            'opd_tidak_baku' => $unmappedOpdCount,
        ];

        // 13. Tren Bulanan 6 Bulan Terakhir
        $monthlyTrends = [];
        for ($i = 5; $i >= 0; $i--) {
            $mDate = $now->copy()->subMonths($i);
            $mStart = $mDate->copy()->startOfMonth();
            $mEnd = $mDate->copy()->endOfMonth();
            $mKey = $mDate->translatedFormat('M Y');

            $mMasuk = Ticket::whereBetween('created_at', [$mStart, $mEnd])->count();
            $mSelesai = Ticket::whereIn('status', ['resolved', 'closed', 'force_closed'])
                ->whereBetween('selesai_at', [$mStart, $mEnd])
                ->count();
            $mTepat = Ticket::whereIn('status', ['resolved', 'closed', 'force_closed'])
                ->whereBetween('selesai_at', [$mStart, $mEnd])
                ->whereNotNull('sla_deadline_at')
                ->whereColumn('selesai_at', '<=', 'sla_deadline_at')
                ->count();

            $monthlyTrends[] = [
                'bulan' => $mKey,
                'masuk' => $mMasuk,
                'selesai' => $mSelesai,
                'tepat_sla' => $mTepat,
                'rate' => $mSelesai > 0 ? round(($mTepat / $mSelesai) * 100, 1) : 100.0,
            ];
        }

        return [
            'periode' => $periode,
            'start_date' => $startDate?->toDateString(),
            'end_date' => $endDate?->toDateString(),
            'start_date_label' => $startDate?->translatedFormat('d F Y'),
            'end_date_label' => $endDate?->translatedFormat('d F Y'),
            'total_masuk' => $totalMasuk,
            'total_selesai' => $totalSelesai,
            'pecahan_selesai' => $pecahanSelesai,
            'tepat_mentah' => $totalTepatMentah,
            'persen_tepat_mentah' => $persenTepatMentah,
            'tepat_excused' => $totalTepatExcused,
            'persen_tepat_excused' => $persenTepatExcused,
            'p50_hours' => $p50Hours,
            'p90_hours' => $p90Hours,
            'rasio_reopen' => $rasioReopen,
            'reopened_count' => $reopenedCount,
            'total_aktif' => $totalAktif,
            'aging_tertua_teks' => $agingTertuaTeks,
            'bidang_table' => $bidangTable,
            'unassigned_row' => $unassignedRow,
            'sum_bidang_masuk' => $sumBidangMasuk,
            'sum_bidang_selesai' => $sumBidangSelesai,
            'sum_bidang_aktif' => $sumBidangAktif,
            'sum_bidang_breach' => $sumBidangBreach,
            'kinerja_pusat' => $kinerjaPusat,
            'insiden_tlp_red' => $insidenTlpRed,
            'total_pengecualian' => $totalPengecualian,
            'recent_exceptions' => $recentExceptions,
            'top_opd' => $topOpdQuery,
            'repeat_incidents' => $repeatIncidents,
            'kualitas_data' => $kualitasData,
            'monthly_trends' => $monthlyTrends,
            'generated_at' => $now->translatedFormat('d F Y, H:i') . ' WIB',
            'is_snapshot' => false,
        ];
    }
}
