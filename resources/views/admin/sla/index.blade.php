@extends('admin.layout', ['title' => 'Pengawasan & Monitoring SLA'])

@section('content')
<div class="space-y-5">
    <!-- Header Halaman Minimalis & Tenang -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white px-5 py-4 rounded-2xl border border-slate-200/80 shadow-2xs">
        <div>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                <h1 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight">Pengawasan Service Level Agreement (SLA)</h1>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">
                Standar Kebijakan: <strong>SLA Respon: 3x24 Jam (72 Jam)</strong> &bull; <strong>SLA Target Selesai (SLA Closed): 14 Hari (336 Jam)</strong> sejak tiket didisposisi ke Bidang.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}" class="px-3 py-1.5 bg-slate-50 hover:bg-slate-100 text-slate-600 font-semibold rounded-xl text-xs border border-slate-200 transition">
                &larr; Dashboard
            </a>
            @if($isPusat)
                <a href="{{ route('admin.tiket.index') }}" class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-xl text-xs transition">
                    Semua Tiket &rarr;
                </a>
            @else
                <a href="{{ route('admin.bidang.tiket.index') }}" class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-xl text-xs transition">
                    Tiket Bidang &rarr;
                </a>
            @endif
        </div>
    </div>

    <!-- 1. KPI STRIP MINIMALIS (Single Clean Bar) -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
        <div class="grid grid-cols-2 lg:grid-cols-4 divide-y lg:divide-y-0 divide-x-0 lg:divide-x divide-slate-100">
            <!-- Metrik 1: Kepatuhan Total -->
            <div class="p-4 sm:p-5">
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Kepatuhan SLA Total</div>
                <div class="flex items-baseline gap-2 mt-1.5">
                    <span class="text-2xl sm:text-3xl font-black {{ $stats['compliance_rate'] >= 90 ? 'text-emerald-600' : ($stats['compliance_rate'] >= 75 ? 'text-amber-600' : 'text-rose-600') }}">
                        {{ $stats['compliance_rate'] }}%
                    </span>
                    <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded {{ $stats['compliance_rate'] >= 90 ? 'bg-emerald-50 text-emerald-700' : ($stats['compliance_rate'] >= 75 ? 'bg-amber-50 text-amber-700' : 'bg-rose-50 text-rose-700') }}">
                        Target: 90%
                    </span>
                </div>
                <div class="text-[11px] text-slate-400 mt-1">
                    {{ $stats['resolved_on_time'] }} dari {{ $stats['total_evaluated'] }} tiket tepat waktu
                </div>
            </div>

            <!-- Metrik 2: Melebihi Batas (Breach) -->
            <div class="p-4 sm:p-5 {{ $stats['active_breach'] > 0 ? 'bg-rose-50/20' : '' }}">
                <div class="text-[11px] font-bold text-rose-700 uppercase tracking-wider">Terlampaui (Breach)</div>
                <div class="flex items-baseline gap-2 mt-1.5">
                    <span class="text-2xl sm:text-3xl font-black text-rose-600">
                        {{ $stats['active_breach'] }}
                    </span>
                    <span class="text-[11px] text-rose-500 font-medium">Tiket Aktif</span>
                </div>
                <div class="text-[11px] text-slate-400 mt-1">
                    Melewati batas waktu penyelesaian
                </div>
            </div>

            <!-- Metrik 3: Mendekati Kritis -->
            <div class="p-4 sm:p-5">
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Mendekati Kritis</div>
                <div class="flex items-baseline gap-2 mt-1.5">
                    <span class="text-2xl sm:text-3xl font-black text-amber-600">
                        {{ $stats['critical'] }}
                    </span>
                    <span class="text-[11px] text-slate-400">Sisa &lt;20%</span>
                </div>
                <div class="text-[11px] text-slate-400 mt-1">
                    Membutuhkan percepatan pengerjaan
                </div>
            </div>

            <!-- Metrik 4: Berjalan Aman -->
            <div class="p-4 sm:p-5">
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Berjalan Sesuai Target</div>
                <div class="flex items-baseline gap-2 mt-1.5">
                    <span class="text-2xl sm:text-3xl font-black text-emerald-600">
                        {{ $stats['safe'] }}
                    </span>
                    <span class="text-[11px] text-slate-400">Aman</span>
                </div>
                <div class="text-[11px] text-slate-400 mt-1">
                    Sisa waktu dalam batas standar
                </div>
            </div>
        </div>
    </div>

    <!-- 2. SEGMENTED TABS: PEMISAH KONTEKS AGAR TIDAK MENUMPUK RAME -->
    <div class="flex items-center justify-between border-b border-slate-200 pb-2">
        <div class="flex items-center gap-2">
            <button type="button" id="tab-btn-radar" onclick="switchSlaTab('radar')"
                class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 bg-slate-900 text-white shadow-2xs">
                <span>📡</span>
                <span>Radar Tiket Berjalan</span>
                <span id="badge-tab-radar" class="px-1.5 py-0.2 rounded-full text-[10px] bg-white/20 text-white">
                    {{ $radarTickets->total() }}
                </span>
            </button>

            @if($isPusat && !empty($bidangScorecards))
                <button type="button" id="tab-btn-matriks" onclick="switchSlaTab('matriks')"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 bg-white hover:bg-slate-100 text-slate-600 border border-slate-200">
                    <span>📊</span>
                    <span>Matriks Kepatuhan Bidang</span>
                    <span id="badge-tab-matriks" class="px-1.5 py-0.2 rounded-full text-[10px] bg-slate-100 text-slate-600">
                        {{ count($bidangScorecards) }} Bidang
                    </span>
                </button>
            @endif
        </div>

        <div class="hidden sm:block text-[11px] text-slate-400">
            Perubahan status SLA dihitung real-time
        </div>
    </div>

    <!-- TAB 1: RADAR TIKET BERJALAN (FOKUS OPERASIONAL) -->
    <div id="tab-content-radar" class="space-y-4">
        <div class="bg-white rounded-2xl shadow-2xs border border-slate-200/90 overflow-hidden">
            <!-- Filter Toolbar -->
            <div class="p-4 sm:p-5 border-b border-slate-200/80 bg-slate-50/60 space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="text-[11px] font-bold text-slate-400 mr-1">Status:</span>
                        <a href="{{ route('admin.sla.index', array_merge(request()->except(['status_sla', 'page']), ['status_sla' => 'semua', 'tab' => 'radar'])) }}"
                           class="px-2.5 py-1 rounded-lg text-xs font-bold transition {{ (request('status_sla', 'semua') === 'semua') ? 'bg-slate-900 text-white' : 'bg-white hover:bg-slate-100 text-slate-600 border border-slate-200' }}">
                            Semua ({{ $stats['total_active'] }})
                        </a>
                        <a href="{{ route('admin.sla.index', array_merge(request()->except(['status_sla', 'page']), ['status_sla' => 'breach', 'tab' => 'radar'])) }}"
                           class="px-2.5 py-1 rounded-lg text-xs font-bold transition flex items-center gap-1 {{ (request('status_sla') === 'breach') ? 'bg-rose-600 text-white' : 'bg-white hover:bg-rose-50 text-rose-700 border border-rose-200' }}">
                            <span>🔴</span> Terlampaui ({{ $stats['active_breach'] }})
                        </a>
                        <a href="{{ route('admin.sla.index', array_merge(request()->except(['status_sla', 'page']), ['status_sla' => 'aman', 'tab' => 'radar'])) }}"
                           class="px-2.5 py-1 rounded-lg text-xs font-bold transition flex items-center gap-1 {{ (request('status_sla') === 'aman') ? 'bg-emerald-600 text-white' : 'bg-white hover:bg-emerald-50 text-emerald-700 border border-emerald-200' }}">
                            <span>🟢</span> Berjalan Aman ({{ $stats['safe'] }})
                        </a>
                    </div>

                    @if(request()->anyFilled(['q', 'bidang_id', 'status_sla']))
                        <a href="{{ route('admin.sla.index', ['tab' => 'radar']) }}" class="text-[11px] font-semibold text-rose-600 hover:underline">
                            Reset Filter &times;
                        </a>
                    @endif
                </div>

                <!-- Form Filter Input -->
                <form action="{{ route('admin.sla.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5 text-xs">
                    <input type="hidden" name="tab" value="radar">
                    <div>
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari kode, pelapor, teknisi..."
                            class="w-full text-xs rounded-xl border border-slate-300 p-2 bg-white focus:outline-none focus:ring-2 focus:ring-sky-500">
                    </div>

                    @if($isPusat)
                        <div>
                            <select name="bidang_id" class="w-full text-xs rounded-xl border border-slate-300 p-2 bg-white focus:outline-none focus:ring-2 focus:ring-sky-500">
                                <option value="">-- Semua Bidang DKIS --</option>
                                @foreach($bidangList as $b)
                                    <option value="{{ $b->id }}" {{ request('bidang_id') == $b->id ? 'selected' : '' }}>
                                        {{ $b->nama_lengkap ?? $b->nama_bidang }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div>
                        <select name="status_sla" class="w-full text-xs rounded-xl border border-slate-300 p-2 bg-white focus:outline-none focus:ring-2 focus:ring-sky-500">
                            <option value="semua" {{ request('status_sla', 'semua') === 'semua' ? 'selected' : '' }}>-- Status Waktu --</option>
                            <option value="breach" {{ request('status_sla') === 'breach' ? 'selected' : '' }}>🔴 Lewat Batas (Breach)</option>
                            <option value="aman" {{ request('status_sla') === 'aman' ? 'selected' : '' }}>🟢 Dalam Batas Waktu</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <button type="submit" class="flex-1 py-2 px-3 bg-sky-600 hover:bg-sky-700 text-white font-bold rounded-xl text-xs transition">
                            Cari
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tabel Radar -->
            @if($radarTickets->isEmpty())
                <div class="p-12 text-center text-slate-500">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-2 font-black text-lg border border-emerald-100">
                        ✓
                    </div>
                    <h4 class="font-bold text-slate-800 text-xs">Tidak Ada Tiket Pada Filter Ini</h4>
                    <p class="text-[11px] text-slate-400 mt-0.5">Semua tiket berjalan aman atau belum ada tiket yang cocok.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs min-w-[960px]">
                        <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px] tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="py-3 px-4 w-36">Kode Tiket</th>
                                <th class="py-3 px-4 w-44">Pelapor & Instansi</th>
                                <th class="py-3 px-4">Pokok Masalah</th>
                                <th class="py-3 px-4 w-40">Bidang & Teknisi</th>
                                <th class="py-3 px-4 w-28 text-center">Batas SLA</th>
                                <th class="py-3 px-4 w-40 text-center">Status Waktu</th>
                                <th class="py-3 px-4 w-20 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($radarTickets as $t)
                                @php
                                    $slaStart = $t->sla_dimulai_at ? \Carbon\Carbon::parse($t->sla_dimulai_at) : null;
                                    $totalMins = max(1, ((float)($t->sla_resolusi_jam ?: 336)) * 60);
                                    $deadline = $t->sla_deadline_at ? \Carbon\Carbon::parse($t->sla_deadline_at) : ($slaStart ? $slaStart->copy()->addMinutes($totalMins) : null);
                                    $minsLeft = $deadline ? now()->diffInMinutes($deadline, false) : 0;
                                    $isBreach = $t->sla_breach || ($deadline && $minsLeft <= 0);
                                @endphp
                                <tr class="hover:bg-slate-50/70 transition {{ $isBreach ? 'bg-rose-50/20' : '' }}">
                                    <!-- Kode & Status -->
                                    <td class="py-3 px-4 font-mono font-bold text-slate-900">
                                        <a href="{{ $isPusat ? route('admin.tiket.show', $t->id) : route('admin.bidang.tiket.index', ['q' => $t->kode_tiket]) }}" class="text-sky-700 hover:underline">
                                            {{ $t->kode_tiket }}
                                        </a>
                                        <div class="mt-1">
                                            <x-status-badge :status="$t->status" />
                                        </div>
                                    </td>

                                    <!-- Pelapor & OPD -->
                                    <td class="py-3 px-4">
                                        <div class="font-bold text-slate-800 text-xs">{{ $t->nama_pelapor }}</div>
                                        <div class="text-[11px] text-slate-400 truncate mt-0.5">{{ $t->skpd }}</div>
                                    </td>

                                    <!-- Pokok Masalah -->
                                    <td class="py-3 px-4">
                                        <div class="font-bold text-slate-900 leading-snug line-clamp-1 text-xs" title="{{ $t->judul }}">{{ $t->judul }}</div>
                                        <div class="text-[10px] text-slate-400 mt-0.5">
                                            {{ $t->category?->nama_kategori ?? 'Umum' }}
                                        </div>
                                    </td>

                                    <!-- Bidang & Teknisi -->
                                    <td class="py-3 px-4">
                                        <div class="font-bold text-slate-800 text-xs">{{ $t->bidang?->nama_bidang ?? 'Bypass CSIRT' }}</div>
                                        <div class="text-[11px] text-slate-400 mt-0.5 truncate">
                                            {{ $t->teknisi_lapangan ?: 'Belum ada teknisi' }}
                                        </div>
                                    </td>

                                    <!-- Target SLA -->
                                    <td class="py-3 px-4 text-center">
                                        <span class="font-bold text-slate-700 text-xs font-mono">{{ round(((float)($t->sla_resolusi_jam ?? 336))/24) }} Hari ({{ (int)($t->sla_resolusi_jam ?? 336) }} Jam)</span>
                                        @if($deadline)
                                            <div class="text-[10px] text-slate-400">
                                                {{ $deadline->translatedFormat('d M, H:i') }}
                                            </div>
                                        @endif
                                    </td>

                                    <!-- Status & Countdown -->
                                    <td class="py-3 px-4 text-center">
                                        @if($deadline)
                                            @if($isBreach)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-rose-50 text-rose-700 border border-rose-200 text-[11px] font-bold font-mono">
                                                    <span>⚠️</span> Lewat {{ abs($minsLeft) >= 60 ? floor(abs($minsLeft) / 60) . 'j ' . (abs($minsLeft) % 60) . 'm' : abs($minsLeft) . 'm' }}
                                                </span>
                                            @elseif($minsLeft > 0)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded {{ $minsLeft < 60 ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-emerald-50 text-emerald-800 border border-emerald-200' }} text-[11px] font-bold font-mono">
                                                    <span>⏱️</span> Sisa {{ $minsLeft >= 60 ? floor($minsLeft / 60) . 'j ' . ($minsLeft % 60) . 'm' : $minsLeft . 'm' }}
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-[11px] text-slate-400 italic">Belum berjalan</span>
                                        @endif
                                    </td>

                                    <!-- Aksi -->
                                    <td class="py-3 px-4 text-right">
                                        @if($isPusat)
                                            <a href="{{ route('admin.tiket.show', $t->id) }}" class="px-2.5 py-1 bg-slate-100 hover:bg-sky-50 hover:text-sky-700 text-slate-700 font-bold rounded-lg text-xs transition border border-slate-200">
                                                Tinjau
                                            </a>
                                        @else
                                            <a href="{{ route('admin.bidang.tiket.index', ['q' => $t->kode_tiket]) }}" class="px-2.5 py-1 bg-slate-100 hover:bg-emerald-50 hover:text-emerald-700 text-slate-700 font-bold rounded-lg text-xs transition border border-slate-200">
                                                Kelola
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($radarTickets->hasPages())
                    <div class="p-3.5 border-t border-slate-100 bg-slate-50/50">
                        {{ $radarTickets->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>

    <!-- TAB 2: MATRIKS KEPATUHAN BIDANG (FOKUS EVALUASI SCORECARD) -->
    @if($isPusat && !empty($bidangScorecards))
        <div id="tab-content-matriks" class="hidden space-y-4">
            <div class="bg-white rounded-2xl shadow-2xs border border-slate-200/90 overflow-hidden">
                <div class="p-4 sm:p-5 border-b border-slate-200/80 bg-slate-50/60 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                        <h2 class="text-sm sm:text-base font-black text-slate-900 tracking-tight">
                            Matriks Kinerja & Kepatuhan Antar-Bidang
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Evaluasi pemenuhan target SLA untuk masing-masing bidang pengampu layanan TIK.
                        </p>
                    </div>
                    <div class="text-xs text-slate-500">
                        Standar Kepatuhan: <strong class="text-slate-800 font-mono">≥ 90.0%</strong>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs min-w-[750px]">
                        <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px] tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="py-3 px-5">Bidang Pengampu</th>
                                <th class="py-3 px-4 text-center">Total Tiket</th>
                                <th class="py-3 px-4 text-center">Aktif</th>
                                <th class="py-3 px-4 text-center">Tepat Waktu</th>
                                <th class="py-3 px-4 text-center">Breach</th>
                                <th class="py-3 px-5 text-center min-w-[160px]">Tingkat Kepatuhan</th>
                                <th class="py-3 px-4 text-center">Predikat</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($bidangScorecards as $sc)
                                @php
                                    $isRateHigh = $sc['compliance_rate'] >= 90;
                                    $isRateMed = $sc['compliance_rate'] >= 75 && $sc['compliance_rate'] < 90;
                                @endphp
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="py-3.5 px-5">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-center text-sm shrink-0">
                                                @if(str_contains(strtoupper($sc['kode']), 'SANDI') || str_contains(strtoupper($sc['nama_bidang']), 'SANDI'))
                                                    🛡️
                                                @elseif(str_contains(strtoupper($sc['kode']), 'PEMDI') || str_contains(strtoupper($sc['nama_bidang']), 'DIGITAL'))
                                                    🌐
                                                @else
                                                    💻
                                                @endif
                                            </div>
                                            <div>
                                                <div class="font-bold text-slate-900 text-xs">{{ $sc['nama_bidang'] }}</div>
                                                <div class="text-[10px] text-slate-400 font-mono">{{ $sc['kode'] }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="py-3.5 px-4 text-center font-mono font-bold text-slate-700">
                                        {{ $sc['total'] }}
                                    </td>

                                    <td class="py-3.5 px-4 text-center font-mono font-bold text-sky-700">
                                        {{ $sc['active'] }}
                                    </td>

                                    <td class="py-3.5 px-4 text-center font-mono font-bold text-emerald-700">
                                        {{ $sc['completed_on_time'] }}
                                    </td>

                                    <td class="py-3.5 px-4 text-center font-mono font-bold">
                                        @if($sc['breach'] > 0)
                                            <span class="text-rose-700 bg-rose-50 px-2 py-0.5 rounded border border-rose-200">
                                                {{ $sc['breach'] }}
                                            </span>
                                        @else
                                            <span class="text-slate-400">0</span>
                                        @endif
                                    </td>

                                    <td class="py-3.5 px-5">
                                        <div class="space-y-1 max-w-[160px] mx-auto">
                                            <div class="flex items-center justify-between text-[11px] font-mono font-bold">
                                                <span class="text-slate-400">Tingkat</span>
                                                <span class="{{ $isRateHigh ? 'text-emerald-600' : ($isRateMed ? 'text-amber-600' : 'text-rose-600') }}">
                                                    {{ $sc['compliance_rate'] }}%
                                                </span>
                                            </div>
                                            <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
                                                <div class="h-1.5 rounded-full {{ $isRateHigh ? 'bg-emerald-500' : ($isRateMed ? 'bg-amber-500' : 'bg-rose-500') }}"
                                                     style="width: {{ min(100, $sc['compliance_rate']) }}%"></div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="py-3.5 px-4 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $sc['predikatClass'] }}">
                                            {{ $sc['predikat'] }}
                                        </span>
                                    </td>

                                    <td class="py-3.5 px-4 text-right">
                                        <a href="{{ route('admin.sla.index', ['bidang_id' => $sc['id'], 'tab' => 'radar']) }}" 
                                           class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg text-[11px] transition">
                                            Lihat Radar &rarr;
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Tab Switching Script Minimalis -->
<script>
    function switchSlaTab(tabName) {
        const radarTabContent = document.getElementById('tab-content-radar');
        const matriksTabContent = document.getElementById('tab-content-matriks');
        const radarBtn = document.getElementById('tab-btn-radar');
        const matriksBtn = document.getElementById('tab-btn-matriks');

        if (!radarTabContent) return;

        if (tabName === 'matriks' && matriksTabContent) {
            radarTabContent.classList.add('hidden');
            matriksTabContent.classList.remove('hidden');

            if (radarBtn) {
                radarBtn.className = "px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 bg-white hover:bg-slate-100 text-slate-600 border border-slate-200";
            }
            if (matriksBtn) {
                matriksBtn.className = "px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 bg-slate-900 text-white shadow-2xs";
            }
        } else {
            radarTabContent.classList.remove('hidden');
            if (matriksTabContent) matriksTabContent.classList.add('hidden');

            if (radarBtn) {
                radarBtn.className = "px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 bg-slate-900 text-white shadow-2xs";
            }
            if (matriksBtn) {
                matriksBtn.className = "px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 bg-white hover:bg-slate-100 text-slate-600 border border-slate-200";
            }
        }
    }

    // Auto-select tab berdasarkan URL query jika ada
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');
        if (tab === 'matriks') {
            switchSlaTab('matriks');
        }
    });
</script>
@endsection
