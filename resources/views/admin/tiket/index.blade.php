@extends('admin.layout', ['title' => 'Semua Tiket Layanan — DKIS Kota Cirebon'])

@section('content')
<div class="space-y-6">
    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-sky-50 text-sky-800 border border-sky-200 text-xs font-semibold mb-1">
                <span>📁</span> Arsip Terpusat
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Semua Tiket Layanan DKIS</h1>
            <p class="text-xs text-slate-500 mt-1">
                Seluruh arsip pengaduan lintas bidang dengan filter multi-kriteria dan indikator kepatuhan Service Level Agreement (SLA).
            </p>
        </div>

        <div class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white border border-slate-200 shadow-xs text-xs font-bold text-slate-700">
            <span>Ditemukan:</span>
            <span class="px-2.5 py-0.5 rounded-full bg-sky-600 text-white font-black">{{ $tickets->total() }} Tiket</span>
        </div>
    </div>

    <!-- FILTER BAR MULTI-KRITERIA -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200 p-5">
        <form action="{{ route('admin.tiket.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 text-xs">
            <!-- 1. Search Query -->
            <div>
                <label for="filter-q" class="block font-bold text-slate-700 mb-1">Kata Kunci</label>
                <input type="text" id="filter-q" name="q" value="{{ request('q') }}" placeholder="Kode / Pelapor / Judul / NIP..."
                    class="w-full text-xs rounded-xl border border-slate-300 p-2.5 focus:outline-none focus:ring-2 focus:ring-sky-500 transition">
            </div>

            <!-- 2. Filter Status -->
            <div>
                <label for="filter-status" class="block font-bold text-slate-700 mb-1">Status Tiket</label>
                <select id="filter-status" name="status" class="w-full text-xs rounded-xl border border-slate-300 p-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-sky-500 transition">
                    <option value="">-- Semua Status --</option>
                    @foreach([
                        'pending_verification' => '⏳ Menunggu Verifikasi',
                        'verified' => '✅ Terverifikasi',
                        'in_progress' => '🔧 Sedang Dikerjakan',
                        'resolved' => '🔔 Menunggu Konfirmasi',
                        'reopened' => '🔄 Dibuka Kembali',
                        'eskalasi_pusat' => '⚖️ Eskalasi Admin Pusat',
                        'force_closed' => '🔒 Ditutup Paksa',
                        'closed' => '✅ Selesai',
                        'cancelled' => '❌ Dibatalkan'
                    ] as $sKey => $sLabel)
                        <option value="{{ $sKey }}" {{ request('status') === $sKey ? 'selected' : '' }}>
                            {{ $sLabel }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- 3. Filter Bidang -->
            <div>
                <label for="filter-bidang" class="block font-bold text-slate-700 mb-1">Bidang DKIS</label>
                <select id="filter-bidang" name="bidang_id" class="w-full text-xs rounded-xl border border-slate-300 p-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-sky-500 transition">
                    <option value="">-- Semua Bidang --</option>
                    @foreach($bidangList as $b)
                        <option value="{{ $b->id }}" {{ request('bidang_id') == $b->id ? 'selected' : '' }}>
                            {{ $b->nama_lengkap }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- 4. Filter TLP Level -->
            <div>
                <label for="filter-tlp" class="block font-bold text-slate-700 mb-1">Klasifikasi TLP</label>
                <select id="filter-tlp" name="tlp_level" class="w-full text-xs rounded-xl border border-slate-300 p-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-sky-500 transition">
                    <option value="">-- Semua TLP --</option>
                    <option value="RED" {{ request('tlp_level') === 'RED' ? 'selected' : '' }}>🔴 RED (Kritis / Darurat)</option>
                    <option value="AMBER" {{ request('tlp_level') === 'AMBER' ? 'selected' : '' }}>🟡 AMBER (Terbatas OPD)</option>
                    <option value="GREEN" {{ request('tlp_level') === 'GREEN' ? 'selected' : '' }}>🟢 GREEN (Komunitas ASN)</option>
                    <option value="CLEAR" {{ request('tlp_level') === 'CLEAR' ? 'selected' : '' }}>⚪ CLEAR (Terbuka Umum)</option>
                </select>
            </div>

            <!-- 5. Filter SLA Breach -->
            <div>
                <label for="filter-sla" class="block font-bold text-slate-700 mb-1">Kepatuhan SLA</label>
                <select id="filter-sla" name="sla_breach" class="w-full text-xs rounded-xl border border-slate-300 p-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-sky-500 transition">
                    <option value="">-- Semua SLA --</option>
                    <option value="0" {{ request('sla_breach') === '0' ? 'selected' : '' }}>🟢 Sesuai Target (Aman)</option>
                    <option value="1" {{ request('sla_breach') === '1' ? 'selected' : '' }}>🔴 Melebihi Batas (Breach)</option>
                </select>
            </div>

            <!-- 6. Tombol Filter & Reset -->
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 py-2.5 px-3 bg-sky-600 hover:bg-sky-700 text-white font-bold rounded-xl text-xs shadow-xs transition flex items-center justify-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                    Filter
                </button>
                <a href="{{ route('admin.tiket.index') }}" class="py-2.5 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition border border-slate-200">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- TABEL SEMUA TIKET DENGAN INDIKATOR WARNA SLA -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
        <div class="overflow-x-auto scrollbar-thin">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50/90 text-slate-600 font-bold border-b border-slate-200 uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="py-3.5 px-4 whitespace-nowrap w-36">Kode Tiket</th>
                        <th class="py-3.5 px-4 min-w-[170px]">Pelapor & OPD</th>
                        <th class="py-3.5 px-4 min-w-[200px]">Pokok Masalah</th>
                        <th class="py-3.5 px-4 min-w-[140px] whitespace-nowrap">Bidang</th>
                        <th class="py-3.5 px-4 text-center whitespace-nowrap w-16">TLP</th>
                        <th class="py-3.5 px-4 text-center whitespace-nowrap min-w-[170px]">Status</th>
                        <th class="py-3.5 px-4 text-center whitespace-nowrap min-w-[160px]">Indikator SLA</th>
                        <th class="py-3.5 px-4 text-right whitespace-nowrap w-24">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($tickets as $t)
                        <tr class="hover:bg-slate-50/80 transition">
                            <!-- Kode Tiket & Badge Induk/Anak -->
                            <td class="py-3 px-4 whitespace-nowrap">
                                <div class="flex items-center gap-1.5 font-mono font-bold text-slate-900">
                                    <a href="{{ route('admin.tiket.show', $t->id) }}" class="hover:text-sky-600 hover:underline">
                                        {{ $t->kode_tiket }}
                                    </a>
                                    @if($t->is_parent)
                                        <span class="inline-flex items-center px-1.5 py-0.2 rounded bg-amber-400 text-slate-950 text-[10px] font-black uppercase" title="Induk Tiket (Master Incident)">
                                            👑 INDUK
                                        </span>
                                    @elseif($t->parent_id)
                                        <span class="inline-flex items-center px-1.5 py-0.2 rounded bg-indigo-100 text-indigo-800 text-[10px] font-bold border border-indigo-200" title="Tiket Anak dari {{ $t->parent?->kode_tiket }}">
                                            🔗 Anak
                                        </span>
                                    @endif
                                </div>
                                @if($t->parent_id && $t->parent)
                                    <div class="text-[10px] font-mono text-indigo-600 mt-0.5">
                                        ↳ {{ $t->parent->kode_tiket }}
                                    </div>
                                @endif
                            </td>

                            <!-- Pelapor & OPD -->
                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-900">{{ $t->nama_pelapor }}</div>
                                <div class="text-slate-500 text-[11px] truncate max-w-[190px]" title="{{ $t->skpd }}">{{ $t->skpd }}</div>
                            </td>

                            <!-- Pokok Masalah -->
                            <td class="py-3 px-4 max-w-xs">
                                <div class="font-semibold text-slate-900 truncate" title="{{ $t->judul }}">{{ $t->judul }}</div>
                                <div class="text-slate-500 text-[11px] truncate">{{ $t->category?->nama_kategori }}</div>
                            </td>

                            <!-- Bidang -->
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="font-medium text-slate-700">
                                    {{ $t->bidang?->nama_bidang ?? 'Bypass (CSIRT)' }}
                                </span>
                            </td>

                            <!-- TLP -->
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                @if($t->tlp_level === 'RED')
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-mono font-black bg-rose-600 text-white shadow-xs">RED</span>
                                @elseif($t->tlp_level === 'AMBER')
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-mono font-black bg-amber-500 text-slate-950 shadow-xs">AMBER</span>
                                @elseif($t->tlp_level === 'GREEN')
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-mono font-black bg-emerald-600 text-white shadow-xs">GREEN</span>
                                @elseif($t->tlp_level === 'CLEAR')
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-mono font-black bg-slate-500 text-white shadow-xs">CLEAR</span>
                                @else
                                    <span class="text-slate-400 text-[10px] font-mono">-</span>
                                @endif
                            </td>

                            <!-- Status Badge Konsisten -->
                            <td class="py-3 px-4 text-center whitespace-nowrap min-w-[170px]">
                                <x-status-badge :status="$t->status" />
                            </td>

                            <!-- SLA Indicator Warna (Hijau / Kuning / Merah) -->
                            <td class="py-3 px-4 text-center whitespace-nowrap min-w-[160px]">
                                <x-sla-indicator :ticket="$t" />
                            </td>

                            <!-- Aksi -->
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                <a href="{{ route('admin.tiket.show', $t->id) }}" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-lg text-xs font-semibold transition border border-slate-300">
                                    Detail &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-400">
                                Tidak ada tiket yang sesuai dengan kriteria filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-200">
            {{ $tickets->links() }}
        </div>
    </div>
</div>
@endsection
