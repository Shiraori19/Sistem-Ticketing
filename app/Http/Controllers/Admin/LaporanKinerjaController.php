<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Bidang;
use App\Models\Category;
use App\Models\MasterOpd;
use App\Models\Ticket;
use App\Services\LaporanService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LaporanKinerjaController extends Controller
{
    protected LaporanService $laporanService;

    public function __construct(LaporanService $laporanService)
    {
        $this->laporanService = $laporanService;
    }

    /**
     * Tampilkan halaman utama Laporan Kinerja Layanan
     */
    public function index(Request $request)
    {
        $filters = [
            'periode' => $request->input('periode', 'bulan_ini'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'bidang_id' => $request->input('bidang_id'),
            'category_id' => $request->input('category_id'),
            'tlp_level' => $request->input('tlp_level'),
            'opd_id' => $request->input('opd_id'),
        ];

        $reportData = $this->laporanService->getLaporanData($filters);

        $bidangList = Bidang::orderBy('id')->get();
        $categoryList = Category::orderBy('bidang_id')->orderBy('nama_kategori')->get();
        $opdList = MasterOpd::active()->orderBy('urutan')->orderBy('nama_opd')->get();
        $complianceRate = $reportData['persen_tepat_excused'] ?? 100.0;

        return view('admin.laporan.index', compact('reportData', 'filters', 'bidangList', 'categoryList', 'opdList', 'complianceRate'));
    }

    /**
     * Drill-Down: Telusuri daftar tiket pembentuk angka metrik
     */
    public function drilldown(Request $request)
    {
        $metric = $request->input('metric', 'masuk');
        $filters = [
            'periode' => $request->input('periode', 'bulan_ini'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'bidang_id' => $request->input('bidang_id'),
            'category_id' => $request->input('category_id'),
            'tlp_level' => $request->input('tlp_level'),
            'opd_id' => $request->input('opd_id'),
        ];

        [$startDate, $endDate] = (function () use ($filters) {
            $now = Carbon::now();
            switch ($filters['periode']) {
                case 'hari_ini': return [$now->copy()->startOfDay(), $now->copy()->endOfDay()];
                case 'minggu_ini': return [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()];
                case 'bulan_lalu': return [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()];
                case 'kuartal_ini': return [$now->copy()->firstOfQuarter()->startOfDay(), $now->copy()->lastOfQuarter()->endOfDay()];
                case 'tahun_ini': return [$now->copy()->startOfYear(), $now->copy()->endOfYear()];
                case 'kustom':
                    $s = !empty($filters['start_date']) ? Carbon::parse($filters['start_date'])->startOfDay() : null;
                    $e = !empty($filters['end_date']) ? Carbon::parse($filters['end_date'])->endOfDay() : null;
                    return [$s, $e];
                case 'bulan_ini':
                default:
                    return [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()];
            }
        })();

        $query = Ticket::with(['category', 'bidang', 'opd']);

        switch ($metric) {
            case 'masuk':
                $this->laporanService->applyFilters($query, $filters, $startDate, $endDate, 'created_at');
                $title = 'Daftar Tiket Masuk';
                break;
            case 'selesai':
                $query->whereIn('status', ['resolved', 'closed', 'force_closed']);
                $this->laporanService->applyFilters($query, $filters, $startDate, $endDate, 'selesai_at');
                $title = 'Daftar Tiket Selesai';
                break;
            case 'tepat_sla':
                $query->whereIn('status', ['resolved', 'closed', 'force_closed'])
                    ->whereNotNull('sla_deadline_at')
                    ->whereNotNull('selesai_at')
                    ->whereColumn('selesai_at', '<=', 'sla_deadline_at');
                $this->laporanService->applyFilters($query, $filters, $startDate, $endDate, 'selesai_at');
                $title = 'Daftar Tiket Selesai Tepat SLA (Mentah)';
                break;
            case 'tepat_excused':
                $query->whereIn('status', ['resolved', 'closed', 'force_closed'])
                    ->where(function ($q) {
                        $q->where(function ($sub) {
                            $sub->whereNotNull('sla_deadline_at')
                                ->whereNotNull('selesai_at')
                                ->whereColumn('selesai_at', '<=', 'sla_deadline_at');
                        })->orWhere('sla_excused', true)
                          ->orWhereHas('slaExceptions');
                    });
                $this->laporanService->applyFilters($query, $filters, $startDate, $endDate, 'selesai_at');
                $title = 'Daftar Tiket Selesai Tepat SLA (Setelah Pengecualian)';
                break;
            case 'reopen':
                $query->whereIn('status', ['resolved', 'closed', 'force_closed'])
                    ->where('reopen_count', '>', 0);
                $this->laporanService->applyFilters($query, $filters, $startDate, $endDate, 'selesai_at');
                $title = 'Daftar Tiket Reopen';
                break;
            case 'aktif':
                $query->whereIn('status', ['verified', 'in_progress', 'reopened']);
                $this->laporanService->applyFilters($query, $filters, null, null);
                $title = 'Daftar Antrean Tiket Aktif';
                break;
            case 'tanpa_tlp':
                $query->whereNull('tlp_level');
                $this->laporanService->applyFilters($query, $filters, $startDate, $endDate, 'created_at');
                $title = 'Tiket Belum Diklasifikasi TLP';
                break;
            case 'tanpa_bidang':
                $query->whereNull('bidang_id');
                $this->laporanService->applyFilters($query, $filters, $startDate, $endDate, 'created_at');
                $title = 'Tiket Belum Didisposisi / Tanpa Bidang';
                break;
            case 'opd_tidak_baku':
                $query->whereNull('opd_id');
                $this->laporanService->applyFilters($query, $filters, $startDate, $endDate, 'created_at');
                $title = 'Tiket dengan Nama OPD Belum Terpetakan';
                break;
            case 'tlp_red':
                $query->where('tlp_level', 'RED');
                $this->laporanService->applyFilters($query, $filters, $startDate, $endDate, 'created_at');
                $title = 'Daftar Insiden Siber TLP: RED';
                break;
            default:
                $this->laporanService->applyFilters($query, $filters, $startDate, $endDate, 'created_at');
                $title = 'Daftar Tiket';
                break;
        }

        $tickets = $query->orderBy('id', 'desc')->paginate(20)->withQueryString();

        return view('admin.laporan.drilldown', compact('tickets', 'title', 'metric', 'filters'));
    }

    /**
     * Ekspor Laporan ke format CSV / Excel
     */
    public function exportExcel(Request $request)
    {
        $filters = [
            'periode' => $request->input('periode', 'bulan_ini'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'bidang_id' => $request->input('bidang_id'),
            'category_id' => $request->input('category_id'),
            'tlp_level' => $request->input('tlp_level'),
            'opd_id' => $request->input('opd_id'),
        ];

        $reportData = $this->laporanService->getLaporanData($filters);

        AuditLog::create([
            'user_id' => Auth::id(),
            'aksi' => 'EKSPOR_LAPORAN_EXCEL',
            'target_kode' => 'LAPORAN_KINERJA',
            'ip_address' => $request->ip(),
            'detail' => "Admin Pusat mengekspor laporan kinerja ke format Excel/CSV. Periode: {$filters['periode']}",
            'timestamp' => now(),
        ]);

        $fileName = 'laporan_kinerja_dkis_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($reportData, $filters) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM untuk Microsoft Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['LAPORAN KINERJA LAYANAN TIK & SLA - PEMERINTAH KOTA CIREBON']);
            fputcsv($handle, ['Dinas Komunikasi, Informatika dan Statistik Kota Cirebon']);
            fputcsv($handle, ['Periode Filter:', $filters['periode'] . ' (' . ($reportData['start_date_label'] ?? '') . ' s/d ' . ($reportData['end_date_label'] ?? '') . ')']);
            fputcsv($handle, ['Waktu Ekspor:', now()->translatedFormat('d F Y, H:i') . ' WIB']);
            fputcsv($handle, []);

            // 1. Ringkasan Eksekutif
            fputcsv($handle, ['1. RINGKASAN EKSEKUTIF METRIK']);
            fputcsv($handle, ['Metrik', 'Nilai', 'Keterangan']);
            fputcsv($handle, ['Total Tiket Masuk', $reportData['total_masuk'], 'Tiket dibuat pada periode']);
            fputcsv($handle, ['Total Tiket Selesai', $reportData['total_selesai'], 'Status resolved, closed, force_closed']);
            fputcsv($handle, ['- Dikonfirmasi Pelapor', $reportData['pecahan_selesai']['konfirmasi_pelapor']]);
            fputcsv($handle, ['- Ditutup Otomatis Sistem', $reportData['pecahan_selesai']['otomatis_sistem']]);
            fputcsv($handle, ['- Ditutup Paksa Pusat', $reportData['pecahan_selesai']['tutup_paksa']]);
            fputcsv($handle, ['- Diselesaikan Petugas', $reportData['pecahan_selesai']['manual_petugas']]);
            fputcsv($handle, ['Tepat SLA (Aktual)', $reportData['persen_tepat_mentah'] . '%', "{$reportData['tepat_mentah']} dari {$reportData['total_selesai']} tiket"]);
            fputcsv($handle, ['Tepat SLA (Setelah Pengecualian)', $reportData['persen_tepat_excused'] . '%', "{$reportData['tepat_excused']} dari {$reportData['total_selesai']} tiket"]);
            fputcsv($handle, ['Median Jam Penyelesaian (P50)', $reportData['p50_hours'] . ' Jam', 'Dihitung via percentile_cont PostgreSQL']);
            fputcsv($handle, ['Persentil ke-90 Jam Penyelesaian (P90)', $reportData['p90_hours'] . ' Jam']);
            fputcsv($handle, ['Rasio Reopen', $reportData['rasio_reopen'] . '%', "{$reportData['reopened_count']} tiket reopen"]);
            fputcsv($handle, ['Antrean Aktif', $reportData['total_aktif'], "Aging tertua: {$reportData['aging_tertua_teks']}"]);
            fputcsv($handle, []);

            // 2. Kinerja per Bidang
            fputcsv($handle, ['2. KINERJA PER BIDANG']);
            fputcsv($handle, ['No', 'Nama Bidang', 'Tiket Masuk', 'Tiket Selesai', 'Tiket Aktif', 'SLA Breach', 'Kepatuhan SLA (%)']);
            $no = 1;
            foreach ($reportData['bidang_table'] as $b) {
                fputcsv($handle, [
                    $no++,
                    $b['nama_bidang'],
                    $b['masuk'],
                    $b['selesai'],
                    $b['aktif'],
                    $b['breach'],
                    $b['compliance_rate'] . '%',
                ]);
            }
            fputcsv($handle, [
                '-',
                $reportData['unassigned_row']['nama_bidang'],
                $reportData['unassigned_row']['masuk'],
                $reportData['unassigned_row']['selesai'],
                $reportData['unassigned_row']['aktif'],
                $reportData['unassigned_row']['breach'],
                $reportData['unassigned_row']['compliance_rate'] . '%',
            ]);
            fputcsv($handle, [
                'TOTAL',
                'KESELURUHAN SISTEM',
                $reportData['sum_bidang_masuk'],
                $reportData['sum_bidang_selesai'],
                $reportData['sum_bidang_aktif'],
                $reportData['sum_bidang_breach'],
                $reportData['persen_tepat_excused'] . '%',
            ]);
            fputcsv($handle, []);

            // 3. Catatan Definisi Metrik
            fputcsv($handle, ['3. CATATAN DEFINISI METRIK']);
            fputcsv($handle, ['- Tiket Masuk: Dihitung dari tanggal pendaftaran tiket (created_at).']);
            fputcsv($handle, ['- Tiket Selesai: Tiket berstatus resolved, closed, atau force_closed pada periode evaluasi.']);
            fputcsv($handle, ['- Pembagi Kepatuhan: Tiket berstatus pending atau dibatalkan TIDAK masuk pembagi persentase kepatuhan.']);
            fputcsv($handle, ['- Tepat SLA: Waktu penyelesaian aktual <= batas SLA; angka setelah pengecualian memasukkan izin resmi Admin Pusat.']);
            fputcsv($handle, ['- Baris Belum Didisposisi: Tiket tanpa bidang / dikembalikan ke pusat agar jumlah per bidang selalu sama dengan total.']);

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
