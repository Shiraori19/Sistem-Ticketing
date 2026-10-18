@extends('admin.layout', ['title' => 'Laporan Kinerja Layanan & Evaluasi SLA — DKIS Kota Cirebon'])

@section('content')
<div class="space-y-6">

    <!-- ================================================================= -->
    <!-- TOOLBAR & HEADER (Hanya Tampil di Layar Monitor, Disembunyikan Saat Cetak) -->
    <!-- ================================================================= -->
    <div class="no-print bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-sky-100 text-sky-800 border border-sky-200">
                    <span class="w-2 h-2 rounded-full bg-sky-500 animate-pulse"></span>
                    Audit-Ready Service Metrics
                </span>
                @if($reportData['is_snapshot'] ?? false)
                    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                        Snapshot Terkunci ({{ $reportData['frozen_at'] ?? 'Bulan Lampau' }})
                    </span>
                @endif
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight mt-1.5">
                Laporan Kinerja Layanan & Kepatuhan SLA
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Data teragregasi periode: <strong class="text-slate-800">{{ $reportData['start_date_label'] ?? '-' }}</strong> s/d <strong class="text-slate-800">{{ $reportData['end_date_label'] ?? '-' }}</strong> &bull; Pembaruan Terakhir: {{ $reportData['generated_at'] ?? now()->translatedFormat('d M Y, H:i') }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Tombol Ekspor CSV/Excel -->
            <a href="{{ route('admin.laporan.exportExcel', $filters) }}" class="inline-flex items-center gap-2 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-xs transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span>Ekspor Excel</span>
            </a>

            <!-- Tombol Cetak / PDF Resmi -->
            <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl text-xs shadow-xs transition cursor-pointer">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span>Cetak / PDF Resmi</span>
            </button>
        </div>
    </div>

    <!-- ================================================================= -->
    <!-- FILTER BAR INTERAKTIF (No Print) -->
    <!-- ================================================================= -->
    <div class="no-print bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('admin.laporan') }}" id="filterForm" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">
                <!-- 1. Periode Waktu -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Periode Evaluasi</label>
                    <select name="periode" id="periodeSelect" onchange="toggleCustomDates(this.value)" class="w-full text-xs font-semibold bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-slate-800 focus:ring-2 focus:ring-sky-500 focus:bg-white transition">
                        <option value="bulan_ini" {{ ($filters['periode'] ?? '') === 'bulan_ini' ? 'selected' : '' }}>Bulan Ini (Default)</option>
                        <option value="bulan_lalu" {{ ($filters['periode'] ?? '') === 'bulan_lalu' ? 'selected' : '' }}>Bulan Lalu</option>
                        <option value="kuartal_ini" {{ ($filters['periode'] ?? '') === 'kuartal_ini' ? 'selected' : '' }}>Kuartal Ini (Triwulan)</option>
                        <option value="tahun_ini" {{ ($filters['periode'] ?? '') === 'tahun_ini' ? 'selected' : '' }}>Tahun Ini (YTD)</option>
                        <option value="minggu_ini" {{ ($filters['periode'] ?? '') === 'minggu_ini' ? 'selected' : '' }}>Minggu Ini</option>
                        <option value="hari_ini" {{ ($filters['periode'] ?? '') === 'hari_ini' ? 'selected' : '' }}>Hari Ini</option>
                        <option value="kustom" {{ ($filters['periode'] ?? '') === 'kustom' ? 'selected' : '' }}>Kustom Rentang Tanggal</option>
                    </select>
                </div>

                <!-- 2. Bidang Pengampu -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Bidang Layanan</label>
                    <select name="bidang_id" class="w-full text-xs font-semibold bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-slate-800 focus:ring-2 focus:ring-sky-500 focus:bg-white transition">
                        <option value="">Semua Bidang</option>
                        @foreach($bidangList as $b)
                            <option value="{{ $b->id }}" {{ ($filters['bidang_id'] ?? '') == $b->id ? 'selected' : '' }}>
                                {{ $b->nama_bidang }} ({{ $b->kode }})
                            </option>
                        @endforeach
                        <option value="unassigned" {{ ($filters['bidang_id'] ?? '') === 'unassigned' ? 'selected' : '' }}>Belum Didisposisi / Pusat</option>
                    </select>
                </div>

                <!-- 3. Kategori Layanan -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Kategori Permasalahan</label>
                    <select name="category_id" class="w-full text-xs font-semibold bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-slate-800 focus:ring-2 focus:ring-sky-500 focus:bg-white transition">
                        <option value="">Semua Kategori</option>
                        @foreach($categoryList as $cat)
                            <option value="{{ $cat->id }}" {{ ($filters['category_id'] ?? '') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->nama_kategori }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 4. Klasifikasi TLP -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Protokol Kerahasiaan (TLP)</label>
                    <select name="tlp_level" class="w-full text-xs font-semibold bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-slate-800 focus:ring-2 focus:ring-sky-500 focus:bg-white transition">
                        <option value="">Semua Level TLP</option>
                        <option value="RED" {{ ($filters['tlp_level'] ?? '') === 'RED' ? 'selected' : '' }}>TLP:RED (Insiden Kritis)</option>
                        <option value="AMBER" {{ ($filters['tlp_level'] ?? '') === 'AMBER' ? 'selected' : '' }}>TLP:AMBER (Terbatas)</option>
                        <option value="GREEN" {{ ($filters['tlp_level'] ?? '') === 'GREEN' ? 'selected' : '' }}>TLP:GREEN (Komunitas ASN)</option>
                        <option value="CLEAR" {{ ($filters['tlp_level'] ?? '') === 'CLEAR' ? 'selected' : '' }}>TLP:CLEAR (Publik)</option>
                    </select>
                </div>

                <!-- 5. Perangkat Daerah (OPD) -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Perangkat Daerah (OPD)</label>
                    <select name="opd_id" class="w-full text-xs font-semibold bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-slate-800 focus:ring-2 focus:ring-sky-500 focus:bg-white transition">
                        <option value="">Semua OPD Kota Cirebon</option>
                        @foreach($opdList as $opd)
                            <option value="{{ $opd->id }}" {{ ($filters['opd_id'] ?? '') == $opd->id ? 'selected' : '' }}>
                                {{ $opd->nama_opd }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Rentang Tanggal Kustom (Toggleable) -->
            <div id="customDateContainer" class="{{ ($filters['periode'] ?? '') === 'kustom' ? 'grid' : 'hidden' }} grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 pt-2 border-t border-slate-100">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Mulai Tanggal</label>
                    <input type="date" name="start_date" value="{{ $filters['start_date'] ?? '' }}" class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-slate-800 focus:ring-2 focus:ring-sky-500 focus:bg-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="{{ $filters['end_date'] ?? '' }}" class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-slate-800 focus:ring-2 focus:ring-sky-500 focus:bg-white">
                </div>
            </div>

            <!-- Tombol Submit & Reset -->
            <div class="flex items-center justify-between pt-1">
                <div class="text-[11px] text-slate-500">
                    Kepatuhan dihitung dari stempel waktu riil &bull; Tiket dibatalkan/pending tidak menggelembungkan persentase.
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.laporan') }}" class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold rounded-xl text-xs transition">
                        Reset Filter
                    </a>
                    <button type="submit" class="px-4 py-1.5 bg-sky-600 hover:bg-sky-700 text-white font-bold rounded-xl text-xs shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        <span>Terapkan Filter</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- ================================================================= -->
    <!-- DOKUMEN CETAK RESMI (Format A4 Standar Kedinasan DKIS) -->
    <!-- ================================================================= -->
    <div class="bg-white p-6 sm:p-10 rounded-2xl border border-slate-200 shadow-sm print:border-none print:shadow-none print:p-0 print:m-0 text-slate-900 font-sans space-y-6">

        <!-- KOP SURAT RESMI (Khusus Cetak / Pratinjau Formal) -->
        <div class="border-b-4 border-double border-black pb-4 text-center">
            <h2 class="text-xs sm:text-sm font-bold tracking-widest uppercase text-black">PEMERINTAH KOTA CIREBON</h2>
            <h1 class="text-base sm:text-lg font-black tracking-wider uppercase text-black mt-0.5">DINAS KOMUNIKASI, INFORMATIKA DAN STATISTIK</h1>
            <p class="text-[11px] text-black mt-0.5 font-medium">Jl. Sudarsono No. 40, Kesambi, Kota Cirebon, Jawa Barat 45134 &bull; Telp: (0231) 206123</p>
        </div>

        <!-- JUDUL LAPORAN CETAK -->
        <div class="text-center">
            <h3 class="text-sm font-black uppercase tracking-wider text-black">LAPORAN KINERJA PELAYANAN TIK DAN EVALUASI SLA</h3>
            <p class="text-xs text-black font-semibold mt-1">
                Periode Evaluasi: {{ $reportData['start_date_label'] ?? '-' }} s/d {{ $reportData['end_date_label'] ?? '-' }}
            </p>
        </div>

        <!-- ============================================================= -->
        <!-- 1. RINGKASAN EKSEKUTIF: 6 HEADLINE METRICS CARDS -->
        <!-- ============================================================= -->
        <div>
            <div class="flex items-center justify-between mb-3">
                <h4 class="text-xs font-black uppercase tracking-wider text-slate-800 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-sky-600"></span>
                    <span>1. Ringkasan Eksekutif Metrik Utama</span>
                </h4>
                <span class="text-[11px] text-slate-400 no-print">*Klik angka kartu untuk drill-down daftar tiket riil</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                
                <!-- Card 1: Tiket Masuk -->
                <div class="bg-gradient-to-br from-slate-50 to-white p-4 rounded-xl border border-slate-200 shadow-2xs hover:border-sky-300 transition group relative">
                    <div class="flex items-center justify-between text-slate-500 mb-1">
                        <span class="text-xs font-bold uppercase tracking-wider">Tiket Masuk</span>
                        <svg class="w-4 h-4 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </div>
                    <div class="flex items-baseline justify-between">
                        <a href="{{ route('admin.laporan.drilldown', array_merge($filters, ['metric' => 'masuk'])) }}" class="text-2xl sm:text-3xl font-black text-slate-900 hover:text-sky-600 transition" title="Lihat tiket masuk">
                            {{ number_format($reportData['total_masuk']) }}
                        </a>
                        <span class="text-[11px] font-semibold text-slate-400">Total Periode</span>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-2">
                        Dihitung berdasarkan tanggal pendaftaran tiket (created_at).
                    </p>
                </div>

                <!-- Card 2: Tiket Selesai + Breakdown -->
                <div class="bg-gradient-to-br from-slate-50 to-white p-4 rounded-xl border border-slate-200 shadow-2xs hover:border-emerald-300 transition relative">
                    <div class="flex items-center justify-between text-slate-500 mb-1">
                        <span class="text-xs font-bold uppercase tracking-wider">Tiket Selesai</span>
                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="flex items-baseline justify-between">
                        <a href="{{ route('admin.laporan.drilldown', array_merge($filters, ['metric' => 'selesai'])) }}" class="text-2xl sm:text-3xl font-black text-emerald-700 hover:text-emerald-800 transition" title="Lihat tiket selesai">
                            {{ number_format($reportData['total_selesai']) }}
                        </a>
                        <span class="text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                            {{ $reportData['total_masuk'] > 0 ? round(($reportData['total_selesai'] / $reportData['total_masuk']) * 100, 1) : 0 }}% Resolusi
                        </span>
                    </div>
                    <!-- Breakdown Chips -->
                    <div class="grid grid-cols-2 gap-1.5 mt-2.5 pt-2 border-t border-slate-100 text-[10px] text-slate-600">
                        <div title="Pelapor konfirmasi selesai">
                            Pelapor: <strong>{{ $reportData['pecahan_selesai']['konfirmasi_pelapor'] }}</strong>
                        </div>
                        <div title="Otomatis sistem 7 hari">
                            Otomatis: <strong>{{ $reportData['pecahan_selesai']['otomatis_sistem'] }}</strong>
                        </div>
                        <div title="Ditutup paksa Admin Pusat">
                            Tutup Paksa: <strong>{{ $reportData['pecahan_selesai']['tutup_paksa'] }}</strong>
                        </div>
                        <div title="Manual Petugas Lapangan">
                            Petugas: <strong>{{ $reportData['pecahan_selesai']['manual_petugas'] }}</strong>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Kepatuhan SLA (Mentah vs Setelah Pengecualian) -->
                <div class="bg-gradient-to-br from-slate-50 to-white p-4 rounded-xl border border-slate-200 shadow-2xs hover:border-indigo-300 transition relative">
                    <div class="flex items-center justify-between text-slate-500 mb-1">
                        <span class="text-xs font-bold uppercase tracking-wider">Kepatuhan SLA</span>
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <a href="{{ route('admin.laporan.drilldown', array_merge($filters, ['metric' => 'tepat_excused'])) }}" class="text-2xl sm:text-3xl font-black text-indigo-900 hover:text-indigo-700 transition" title="Lihat tiket sesuai SLA setelah dispensasi">
                            {{ $reportData['persen_tepat_excused'] }}%
                        </a>
                        <span class="text-xs text-slate-500 font-semibold">
                            (Mentah: <a href="{{ route('admin.laporan.drilldown', array_merge($filters, ['metric' => 'tepat_sla'])) }}" class="underline hover:text-indigo-600">{{ $reportData['persen_tepat_mentah'] }}%</a>)
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-2">
                        Tepat SLA: <strong>{{ $reportData['tepat_excused'] }}</strong> dari <strong>{{ $reportData['total_selesai'] }}</strong> tiket selesai.
                        <span class="text-[10px] text-slate-400 block mt-0.5">*Tiket pending/dibatalkan tidak masuk pembagi.</span>
                    </p>
                </div>

                <!-- Card 4: Median (P50) & P90 Jam Penyelesaian -->
                <div class="bg-gradient-to-br from-slate-50 to-white p-4 rounded-xl border border-slate-200 shadow-2xs hover:border-amber-300 transition relative">
                    <div class="flex items-center justify-between text-slate-500 mb-1">
                        <span class="text-xs font-bold uppercase tracking-wider">Durasi Penyelesaian</span>
                        <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="flex items-baseline gap-3">
                        <div>
                            <span class="text-2xl sm:text-3xl font-black text-slate-900">{{ $reportData['p50_hours'] }}</span>
                            <span class="text-xs font-bold text-slate-500">Jam (P50)</span>
                        </div>
                        <div class="border-l border-slate-200 pl-3">
                            <span class="text-lg font-black text-amber-800">{{ $reportData['p90_hours'] }}</span>
                            <span class="text-xs font-bold text-slate-500">Jam (P90)</span>
                        </div>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-2">
                        Median & Persentil ke-90 durasi aktif (dikurangi jeda kelengkapan data).
                    </p>
                </div>

                <!-- Card 5: Rasio Reopen 7 Hari -->
                <div class="bg-gradient-to-br from-slate-50 to-white p-4 rounded-xl border border-slate-200 shadow-2xs hover:border-rose-300 transition relative">
                    <div class="flex items-center justify-between text-slate-500 mb-1">
                        <span class="text-xs font-bold uppercase tracking-wider">Rasio Reopen</span>
                        <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </div>
                    <div class="flex items-baseline justify-between">
                        <a href="{{ route('admin.laporan.drilldown', array_merge($filters, ['metric' => 'reopen'])) }}" class="text-2xl sm:text-3xl font-black {{ $reportData['rasio_reopen'] > 5 ? 'text-rose-600' : 'text-slate-900' }} hover:underline" title="Lihat tiket reopen">
                            {{ $reportData['rasio_reopen'] }}%
                        </a>
                        <span class="text-xs font-semibold text-slate-500">{{ $reportData['reopened_count'] }} Tiket</span>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-2">
                        Tiket dibuka kembali oleh pelapor dalam jendela 7 hari pasca resolusi.
                    </p>
                </div>

                <!-- Card 6: Antrean Aktif & Aging Tertua -->
                <div class="bg-gradient-to-br from-slate-50 to-white p-4 rounded-xl border border-slate-200 shadow-2xs hover:border-purple-300 transition relative">
                    <div class="flex items-center justify-between text-slate-500 mb-1">
                        <span class="text-xs font-bold uppercase tracking-wider">Antrean Aktif</span>
                        <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <div class="flex items-baseline justify-between">
                        <a href="{{ route('admin.laporan.drilldown', array_merge($filters, ['metric' => 'aktif'])) }}" class="text-2xl sm:text-3xl font-black text-purple-900 hover:underline" title="Lihat antrean aktif">
                            {{ number_format($reportData['total_aktif']) }}
                        </a>
                        <span class="text-[11px] font-bold text-purple-800 bg-purple-50 px-2 py-0.5 rounded border border-purple-200">
                            Aging: {{ $reportData['aging_tertua_teks'] }}
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-2">
                        Status berjalan: verified, in_progress, reopened.
                    </p>
                </div>

            </div>
        </div>

        <!-- ============================================================= -->
        <!-- 2. KINERJA PER BIDANG (DENGAN BARIS BELUM DIDISPOSISI & EQUAL TOTAL) -->
        <!-- ============================================================= -->
        <div class="print-break-inside-avoid">
            <div class="flex items-center justify-between mb-2">
                <h4 class="text-xs font-black uppercase tracking-wider text-slate-800 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-sky-600"></span>
                    <span>2. Rincian Kinerja & Kepatuhan SLA per Bidang Pengampu Layanan</span>
                </h4>
                <span class="text-[11px] text-slate-500 font-medium no-print">Baris "Belum Didisposisi" menjaga konsistensi total</span>
            </div>

            <div class="overflow-x-auto border border-black/40 rounded-xl">
                <table class="w-full text-xs text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-100 border-b border-black text-slate-900 font-bold uppercase tracking-wider text-center">
                            <th class="p-2.5 border-r border-black/30 w-10">No</th>
                            <th class="p-2.5 border-r border-black/30 text-left">Bidang Layanan</th>
                            <th class="p-2.5 border-r border-black/30 w-24">Tiket Masuk</th>
                            <th class="p-2.5 border-r border-black/30 w-24">Tiket Selesai</th>
                            <th class="p-2.5 border-r border-black/30 w-24">Antrean Aktif</th>
                            <th class="p-2.5 border-r border-black/30 w-24">SLA Breach</th>
                            <th class="p-2.5 w-28">Kepatuhan SLA</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-black/20 text-slate-800">
                        @foreach($reportData['bidang_table'] as $index => $b)
                            <tr class="hover:bg-slate-50/80 transition text-center">
                                <td class="p-2.5 border-r border-black/20 font-mono text-slate-500">{{ $index + 1 }}</td>
                                <td class="p-2.5 border-r border-black/20 text-left font-semibold text-slate-900">
                                    <a href="{{ route('admin.laporan.drilldown', array_merge($filters, ['bidang_id' => $b['id'], 'metric' => 'masuk'])) }}" class="hover:text-sky-600">
                                        {{ $b['nama_bidang'] }} ({{ $b['kode'] }})
                                    </a>
                                </td>
                                <td class="p-2.5 border-r border-black/20 font-mono font-bold">{{ number_format($b['masuk']) }}</td>
                                <td class="p-2.5 border-r border-black/20 font-mono font-bold text-emerald-700">{{ number_format($b['selesai']) }}</td>
                                <td class="p-2.5 border-r border-black/20 font-mono font-bold text-purple-700">{{ number_format($b['aktif']) }}</td>
                                <td class="p-2.5 border-r border-black/20 font-mono font-bold {{ $b['breach'] > 0 ? 'text-rose-600' : 'text-slate-500' }}">
                                    {{ number_format($b['breach']) }}
                                </td>
                                <td class="p-2.5 font-bold font-mono {{ $b['compliance_rate'] >= 85 ? 'text-emerald-700' : ($b['compliance_rate'] >= 70 ? 'text-amber-700' : 'text-rose-700') }}">
                                    {{ $b['compliance_rate'] }}%
                                </td>
                            </tr>
                        @endforeach

                        <!-- Baris Wajib: Belum Didisposisi / Dikembalikan ke Pusat -->
                        <tr class="bg-amber-50/50 hover:bg-amber-50 transition text-center font-medium">
                            <td class="p-2.5 border-r border-black/20 font-mono text-amber-800">-</td>
                            <td class="p-2.5 border-r border-black/20 text-left font-semibold text-amber-900">
                                <a href="{{ route('admin.laporan.drilldown', array_merge($filters, ['bidang_id' => 'unassigned', 'metric' => 'masuk'])) }}" class="hover:underline flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                                    <span>{{ $reportData['unassigned_row']['nama_bidang'] }}</span>
                                </a>
                            </td>
                            <td class="p-2.5 border-r border-black/20 font-mono font-bold text-amber-900">{{ number_format($reportData['unassigned_row']['masuk']) }}</td>
                            <td class="p-2.5 border-r border-black/20 font-mono font-bold text-amber-900">{{ number_format($reportData['unassigned_row']['selesai']) }}</td>
                            <td class="p-2.5 border-r border-black/20 font-mono font-bold text-amber-900">{{ number_format($reportData['unassigned_row']['aktif']) }}</td>
                            <td class="p-2.5 border-r border-black/20 font-mono font-bold text-amber-900">{{ number_format($reportData['unassigned_row']['breach']) }}</td>
                            <td class="p-2.5 font-bold font-mono text-amber-900">-</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <!-- Baris TOTAL KESELURUHAN (Matematika Pasti Sama dengan Headline) -->
                        <tr class="bg-slate-200 border-t-2 border-black text-center font-black text-black">
                            <td colspan="2" class="p-3 border-r border-black text-left uppercase tracking-wider">
                                TOTAL KESELURUHAN SISTEM
                            </td>
                            <td class="p-3 border-r border-black font-mono text-sm">{{ number_format($reportData['sum_bidang_masuk']) }}</td>
                            <td class="p-3 border-r border-black font-mono text-sm text-emerald-800">{{ number_format($reportData['sum_bidang_selesai']) }}</td>
                            <td class="p-3 border-r border-black font-mono text-sm text-purple-800">{{ number_format($reportData['sum_bidang_aktif']) }}</td>
                            <td class="p-3 border-r border-black font-mono text-sm text-rose-800">{{ number_format($reportData['sum_bidang_breach']) }}</td>
                            <td class="p-3 font-mono text-sm text-indigo-900">{{ $reportData['persen_tepat_excused'] }}%</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <p class="text-[10px] text-slate-500 mt-1 italic">
                *Catatan: Jumlah Total Kolom Tiket Masuk ({{ $reportData['sum_bidang_masuk'] }}) persis sama dengan Total Tiket Masuk sistem ({{ $reportData['total_masuk'] }}), karena mencakup tiket yang belum didisposisi.
            </p>
        </div>

        <!-- ============================================================= -->
        <!-- 3. TREN BULANAN & ANALISIS OPERASIONAL -->
        <!-- ============================================================= -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 print-break-inside-avoid">
            
            <!-- Tren Bulanan 6 Bulan Terakhir -->
            <div class="bg-slate-50/70 p-4 rounded-xl border border-slate-200">
                <h4 class="text-xs font-black uppercase tracking-wider text-slate-800 mb-3 flex items-center justify-between">
                    <span>Tren Pelayanan 6 Bulan Terakhir</span>
                    <span class="text-[10px] text-slate-500 lowercase font-medium">historis bulanan</span>
                </h4>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-300 text-slate-600 font-bold uppercase text-center">
                                <th class="pb-2 text-left">Bulan</th>
                                <th class="pb-2">Masuk</th>
                                <th class="pb-2">Selesai</th>
                                <th class="pb-2">Tepat SLA</th>
                                <th class="pb-2 text-right">Skor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-slate-800">
                            @foreach($reportData['monthly_trends'] as $m)
                                <tr class="text-center font-medium">
                                    <td class="py-2 text-left font-semibold text-slate-900">{{ $m['bulan'] }}</td>
                                    <td class="py-2 font-mono">{{ $m['masuk'] }}</td>
                                    <td class="py-2 font-mono text-emerald-700">{{ $m['selesai'] }}</td>
                                    <td class="py-2 font-mono">{{ $m['tepat_sla'] }}</td>
                                    <td class="py-2 text-right font-mono font-bold {{ $m['rate'] >= 85 ? 'text-emerald-700' : 'text-amber-700' }}">
                                        {{ $m['rate'] }}%
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Kinerja Operasional Admin Pusat -->
            <div class="bg-slate-50/70 p-4 rounded-xl border border-slate-200 flex flex-col justify-between">
                <div>
                    <h4 class="text-xs font-black uppercase tracking-wider text-slate-800 mb-3 flex items-center justify-between">
                        <span>Kinerja Tata Kelola Admin Pusat</span>
                        <span class="text-[10px] text-slate-500 font-medium">sla verifikasi & disposisi</span>
                    </h4>
                    <div class="space-y-2.5 text-xs">
                        <div class="flex items-center justify-between p-2 rounded-lg bg-white border border-slate-200">
                            <span class="text-slate-600">Median Waktu Verifikasi Awal (P50)</span>
                            <span class="font-bold text-slate-900">{{ $reportData['kinerja_pusat']['median_verifikasi_jam'] }} Jam</span>
                        </div>
                        <div class="flex items-center justify-between p-2 rounded-lg bg-white border border-slate-200">
                            <span class="text-slate-600">Kepatuhan SLA Respon (Maks. 24 Jam)</span>
                            <span class="font-bold text-emerald-700">{{ $reportData['kinerja_pusat']['tepat_sla_respon_rate'] }}%</span>
                        </div>
                        <div class="flex items-center justify-between p-2 rounded-lg bg-white border border-slate-200">
                            <span class="text-slate-600">Antrean Menunggu Verifikasi Saat Ini</span>
                            <span class="font-bold {{ $reportData['kinerja_pusat']['menunggu_verifikasi'] > 5 ? 'text-amber-700' : 'text-slate-900' }}">
                                {{ $reportData['kinerja_pusat']['menunggu_verifikasi'] }} Tiket
                                <span class="text-[10px] text-slate-400 font-normal">({{ $reportData['kinerja_pusat']['menunggu_tertua'] }})</span>
                            </span>
                        </div>
                        <div class="flex items-center justify-between p-2 rounded-lg bg-white border border-slate-200">
                            <span class="text-slate-600">Tiket Dikembalikan oleh Bidang (Salah Kamar)</span>
                            <span class="font-bold text-rose-600">{{ $reportData['kinerja_pusat']['dikembalikan_oleh_bidang'] }} Tiket</span>
                        </div>
                    </div>
                </div>
                <p class="text-[10px] text-slate-500 mt-3 italic">
                    *Mencerminkan efisiensi screening awal tiket sebelum diteruskan ke teknisi spesialis bidang.
                </p>
            </div>

        </div>

        <!-- ============================================================= -->
        <!-- 4. TOP 10 OPD & DETEKSI TIKET BERULANG -->
        <!-- ============================================================= -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 print-break-inside-avoid">
            
            <!-- Top 10 OPD Pengirim Tiket -->
            <div class="bg-white p-4 rounded-xl border border-slate-200">
                <h4 class="text-xs font-black uppercase tracking-wider text-slate-800 mb-3 flex items-center justify-between">
                    <span>Top 10 Perangkat Daerah (OPD) Teraktif</span>
                    <span class="text-[10px] text-slate-400 font-normal">berdasarkan volume laporan</span>
                </h4>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 text-slate-500 font-bold uppercase">
                                <th class="pb-2 w-8 text-center">No</th>
                                <th class="pb-2">Nama Perangkat Daerah</th>
                                <th class="pb-2 text-right">Volume</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @forelse($reportData['top_opd'] as $idx => $opd)
                                <tr>
                                    <td class="py-2 text-center font-mono text-slate-400">{{ $idx + 1 }}</td>
                                    <td class="py-2 font-medium text-slate-900">
                                        @if($opd->opd_id)
                                            <a href="{{ route('admin.laporan.drilldown', array_merge($filters, ['opd_id' => $opd->opd_id, 'metric' => 'masuk'])) }}" class="hover:text-sky-600">
                                                {{ $opd->nama_lembaga }}
                                            </a>
                                        @else
                                            <span>{{ $opd->nama_lembaga }}</span>
                                            <span class="text-[9px] text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200">raw text</span>
                                        @endif
                                    </td>
                                    <td class="py-2 text-right font-mono font-bold text-slate-900">{{ $opd->total }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-4 text-center text-slate-400">Belum ada data OPD pada periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Deteksi Tiket Berulang (Repeat Incidents >= 3 dalam 30 hari) -->
            <div class="bg-white p-4 rounded-xl border border-slate-200 flex flex-col justify-between">
                <div>
                    <h4 class="text-xs font-black uppercase tracking-wider text-slate-800 mb-1 flex items-center justify-between">
                        <span class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                            <span>Indikator Masalah Berulang (Repeat Incidents)</span>
                        </span>
                        <span class="text-[10px] font-bold text-rose-700 bg-rose-50 px-2 py-0.5 rounded border border-rose-200">&ge; 3x / 30 Hari</span>
                    </h4>
                    <p class="text-[11px] text-slate-500 mb-3">
                        Kombinasi OPD dan kategori yang berulang kali dilaporkan mengindikasikan akar masalah sistemik / infrastruktur permanen.
                    </p>

                    <div class="space-y-2">
                        @forelse($reportData['repeat_incidents'] as $repeat)
                            <div class="p-2.5 rounded-lg bg-rose-50/60 border border-rose-200 flex items-center justify-between text-xs">
                                <div>
                                    <div class="font-bold text-slate-900">
                                        {{ $repeat->opd->nama_opd ?? $repeat->skpd ?? 'OPD Belum Terpetakan' }}
                                    </div>
                                    <div class="text-[11px] text-rose-800">
                                        Kategori: <strong>{{ $repeat->category->nama_kategori ?? 'Umum' }}</strong>
                                    </div>
                                </div>
                                <span class="px-2.5 py-1 bg-rose-600 text-white font-black text-xs rounded-lg shadow-2xs font-mono">
                                    {{ $repeat->total }}x
                                </span>
                            </div>
                        @empty
                            <div class="py-8 text-center text-slate-400">
                                <svg class="w-8 h-8 mx-auto text-emerald-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-xs font-semibold text-slate-600">Tidak ada anomali laporan berulang</p>
                                <p class="text-[10px] text-slate-400 mt-0.5">Tidak ada pasangan OPD + kategori yang melebihi ambang batas 3 kali.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

        </div>

        <!-- ============================================================= -->
        <!-- 5. TLP: RED INSIDEN SIBER, PENGECUALIAN SLA, & KARTU KUALITAS DATA -->
        <!-- ============================================================= -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 print-break-inside-avoid">
            
            <!-- Card Insiden Siber TLP RED -->
            <div class="bg-gradient-to-br from-rose-50/80 to-white p-4 rounded-xl border border-rose-200">
                <div class="flex items-center justify-between text-rose-800 mb-2">
                    <span class="text-xs font-black uppercase tracking-wider flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-rose-600 animate-ping"></span>
                        <span>Insiden Siber TLP: RED</span>
                    </span>
                    <a href="{{ route('admin.laporan.drilldown', array_merge($filters, ['metric' => 'tlp_red'])) }}" class="text-[11px] font-bold underline hover:text-rose-900 no-print">
                        Lihat Tiket
                    </a>
                </div>
                <div class="grid grid-cols-3 gap-2 text-center mt-3">
                    <div class="bg-white p-2 rounded-lg border border-rose-200">
                        <span class="text-[10px] text-slate-500 uppercase font-semibold">Total</span>
                        <div class="text-lg font-black text-slate-900 font-mono">{{ $reportData['insiden_tlp_red']['total'] }}</div>
                    </div>
                    <div class="bg-white p-2 rounded-lg border border-rose-200">
                        <span class="text-[10px] text-slate-500 uppercase font-semibold">Selesai</span>
                        <div class="text-lg font-black text-emerald-700 font-mono">{{ $reportData['insiden_tlp_red']['selesai'] }}</div>
                    </div>
                    <div class="bg-white p-2 rounded-lg border border-rose-200">
                        <span class="text-[10px] text-slate-500 uppercase font-semibold">Breach</span>
                        <div class="text-lg font-black text-rose-700 font-mono">{{ $reportData['insiden_tlp_red']['breach'] }}</div>
                    </div>
                </div>
                <p class="text-[10px] text-slate-500 mt-3">
                    Protokol darurat CSIRT DKIS Kota Cirebon: Target respon pertama &le; 15 menit.
                </p>
            </div>

            <!-- Card Pengecualian SLA -->
            <div class="bg-white p-4 rounded-xl border border-slate-200">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-black uppercase tracking-wider text-slate-800">Dispensasi SLA Resmi</span>
                    <span class="text-xs font-black font-mono bg-sky-100 text-sky-800 px-2 py-0.5 rounded">
                        {{ $reportData['total_pengecualian'] }} Tiket
                    </span>
                </div>
                <p class="text-[11px] text-slate-500 mb-2">
                    Disetujui Admin Pusat karena force majeure / vendor pihak ketiga.
                </p>
                <div class="space-y-1.5 text-xs">
                    @forelse($reportData['recent_exceptions'] as $exc)
                        <div class="p-1.5 rounded bg-slate-50 border border-slate-200 flex items-center justify-between text-[11px]">
                            <span class="font-mono font-bold text-slate-900">{{ $exc->ticket->kode_tiket ?? '-' }}</span>
                            <span class="text-slate-500 truncate max-w-[120px]" title="{{ $exc->alasan }}">{{ $exc->alasan }}</span>
                            <span class="text-[10px] text-slate-400">{{ $exc->created_at->format('d/m') }}</span>
                        </div>
                    @empty
                        <div class="py-4 text-center text-slate-400 text-[11px]">Tidak ada dispensasi SLA pada periode ini.</div>
                    @endforelse
                </div>
            </div>

            <!-- Card Kualitas Data (Data Quality Check) -->
            <div class="bg-white p-4 rounded-xl border border-slate-200">
                <h4 class="text-xs font-black uppercase tracking-wider text-slate-800 mb-1 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    <span>Pemeriksaan Kualitas Data (Audit)</span>
                </h4>
                <p class="text-[11px] text-slate-500 mb-3">
                    Tiket yang memerlukan perbaikan klasifikasi data oleh Admin Pusat:
                </p>
                <div class="space-y-2 text-xs">
                    <div class="flex items-center justify-between p-2 rounded-lg bg-slate-50 border border-slate-200">
                        <span class="text-slate-700">Belum Diklasifikasi TLP</span>
                        <a href="{{ route('admin.laporan.drilldown', array_merge($filters, ['metric' => 'tanpa_tlp'])) }}" class="font-bold {{ $reportData['kualitas_data']['tanpa_tlp'] > 0 ? 'text-rose-600 underline' : 'text-emerald-700' }}">
                            {{ $reportData['kualitas_data']['tanpa_tlp'] }} Tiket
                        </a>
                    </div>
                    <div class="flex items-center justify-between p-2 rounded-lg bg-slate-50 border border-slate-200">
                        <span class="text-slate-700">Belum Ditentukan Bidang</span>
                        <a href="{{ route('admin.laporan.drilldown', array_merge($filters, ['metric' => 'tanpa_bidang'])) }}" class="font-bold {{ $reportData['kualitas_data']['tanpa_bidang'] > 0 ? 'text-amber-700 underline' : 'text-emerald-700' }}">
                            {{ $reportData['kualitas_data']['tanpa_bidang'] }} Tiket
                        </a>
                    </div>
                    <div class="flex items-center justify-between p-2 rounded-lg bg-slate-50 border border-slate-200">
                        <span class="text-slate-700">Nama OPD Belum Terpetakan</span>
                        <a href="{{ route('admin.laporan.drilldown', array_merge($filters, ['metric' => 'opd_tidak_baku'])) }}" class="font-bold {{ $reportData['kualitas_data']['opd_tidak_baku'] > 0 ? 'text-amber-700 underline' : 'text-emerald-700' }}">
                            {{ $reportData['kualitas_data']['opd_tidak_baku'] }} Tiket
                        </a>
                    </div>
                </div>
            </div>

        </div>

        <!-- ============================================================= -->
        <!-- 6. CATATAN DEFINISI METRIK & PENGESAHAN DOKUMEN CETAK -->
        <!-- ============================================================= -->
        <div class="pt-4 border-t border-slate-200 text-xs space-y-4 print-break-inside-avoid">
            <div>
                <h5 class="font-bold uppercase tracking-wider text-slate-800 text-[11px] mb-1">Catatan Definisi Metrik (Audit Standard):</h5>
                <ul class="list-disc pl-5 space-y-0.5 text-[10px] text-slate-600">
                    <li><strong>Tiket Masuk:</strong> Seluruh tiket yang didaftarkan ke sistem dalam periode evaluasi berdasarkan <code>created_at</code>.</li>
                    <li><strong>Tiket Selesai:</strong> Tiket yang telah berstatus <code>resolved</code>, <code>closed</code>, atau <code>force_closed</code> dalam periode evaluasi.</li>
                    <li><strong>Penyebut Kepatuhan SLA:</strong> Dihitung murni dari tiket selesai pada periode berjalan. Tiket yang dibatalkan pelapor atau berstatus draft/pending kelengkapan data TIDAK dimasukkan sebagai penyebut agar tidak menggelembungkan rasio keberhasilan.</li>
                    <li><strong>Tepat SLA:</strong> Tiket selesai yang stempel waktu penyelesaiannya &le; tenggat SLA. Angka setelah pengecualian mengakomodasi dispensasi resmi yang disetujui Admin Pusat dengan riwayat audit.</li>
                    <li><strong>Baris Belum Didisposisi:</strong> Tiket baru atau tiket yang dikembalikan bidang ke Admin Pusat dicantumkan tersendiri agar jumlah penjumlahan seluruh bidang selalu konsisten dengan total keseluruhan.</li>
                </ul>
            </div>

            <!-- Tanda Tangan Pengesahan Pejabat (Tampil di Print Out & PDF) -->
            <div class="pt-6 grid grid-cols-2 gap-8 text-center text-xs">
                <div></div>
                <div>
                    <p class="text-slate-700">Cirebon, {{ now()->translatedFormat('d F Y') }}</p>
                    <p class="font-bold text-slate-900 mt-1 uppercase">Kepala Dinas Komunikasi, Informatika dan Statistik</p>
                    <div class="h-16"></div>
                    <p class="font-black text-slate-900 underline uppercase">MA'RUF NURYASA, AP, MM</p>
                    <p class="text-[11px] text-slate-600">Pembina Utama Muda &bull; NIP. 19750510 199311 1 001</p>
                </div>
            </div>
        </div>

    </div>

</div>

<!-- Script Filter Kustom Interaktif -->
<script>
    function toggleCustomDates(val) {
        const container = document.getElementById('customDateContainer');
        if (val === 'kustom') {
            container.classList.remove('hidden');
            container.classList.add('grid');
        } else {
            container.classList.add('hidden');
            container.classList.remove('grid');
        }
    }
</script>
@endsection
