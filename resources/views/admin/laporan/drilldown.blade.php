@extends('admin.layout', ['title' => 'Drill-Down: ' . $title . ' — Laporan Kinerja Layanan'])

@section('content')
<div class="space-y-6">
    <!-- Top Bar & Breadcrumb -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 mb-1">
                <a href="{{ route('admin.laporan', $filters) }}" class="hover:text-sky-600 transition flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Kembali ke Laporan Kinerja</span>
                </a>
                <span>/</span>
                <span class="text-slate-800">Drill-Down Data</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                <span>{{ $title }}</span>
                <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-sky-100 text-sky-800 border border-sky-200">
                    {{ $tickets->total() }} Tiket
                </span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Menampilkan daftar tiket riil yang membentuk indikator metrik ini pada periode filter yang dipilih.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.laporan', $filters) }}" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-xs transition">
                &larr; Ubah Filter Laporan
            </a>
            <a href="{{ route('admin.laporan.exportExcel', $filters) }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-xs transition flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span>Unduh Excel / CSV</span>
            </a>
        </div>
    </div>

    <!-- Active Filter Chips -->
    <div class="flex flex-wrap items-center gap-2 px-1 text-xs text-slate-600">
        <span class="font-bold text-slate-700">Filter Aktif:</span>
        <span class="bg-slate-100 border border-slate-200 px-2.5 py-1 rounded-lg">
            Periode: <strong>{{ ucfirst(str_replace('_', ' ', $filters['periode'] ?? 'bulan ini')) }}</strong>
        </span>
        @if(!empty($filters['bidang_id']))
            <span class="bg-slate-100 border border-slate-200 px-2.5 py-1 rounded-lg">
                Bidang: <strong>{{ $filters['bidang_id'] === 'unassigned' ? 'Belum Didisposisi' : 'ID ' . $filters['bidang_id'] }}</strong>
            </span>
        @endif
        @if(!empty($filters['tlp_level']))
            <span class="bg-slate-100 border border-slate-200 px-2.5 py-1 rounded-lg">
                TLP: <strong>{{ strtoupper($filters['tlp_level']) }}</strong>
            </span>
        @endif
    </div>

    <!-- Tickets Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider">
                        <th class="py-3.5 px-4 w-12 text-center">No</th>
                        <th class="py-3.5 px-4">Kode & Judul Tiket</th>
                        <th class="py-3.5 px-4">Instansi Pelapor</th>
                        <th class="py-3.5 px-4">Bidang & Kategori</th>
                        <th class="py-3.5 px-4">Waktu Pengerjaan</th>
                        <th class="py-3.5 px-4">Status & SLA</th>
                        <th class="py-3.5 px-4 text-center w-24">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($tickets as $index => $t)
                        @php
                            $isOverdue = false;
                            if (in_array($t->status, ['resolved', 'closed', 'force_closed'])) {
                                $isOverdue = $t->sla_deadline_at && $t->selesai_at && $t->selesai_at > $t->sla_deadline_at && !$t->sla_excused;
                            } else {
                                $isOverdue = $t->sla_deadline_at && now() > $t->sla_deadline_at;
                            }
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3.5 px-4 text-center font-mono text-slate-400">
                                {{ $tickets->firstItem() + $index }}
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono font-bold text-slate-900">{{ $t->kode_tiket }}</span>
                                    @if($t->tlp_level)
                                        @php
                                            $tlpColor = match(strtoupper($t->tlp_level)) {
                                                'RED' => 'bg-rose-100 text-rose-800 border-rose-300',
                                                'AMBER' => 'bg-amber-100 text-amber-800 border-amber-300',
                                                'GREEN' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                                                default => 'bg-slate-100 text-slate-800 border-slate-300'
                                            };
                                        @endphp
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-mono font-extrabold border {{ $tlpColor }}">
                                            TLP:{{ strtoupper($t->tlp_level) }}
                                        </span>
                                    @endif
                                    @if($t->reopen_count > 0)
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                            Reopened ({{ $t->reopen_count }})
                                        </span>
                                    @endif
                                </div>
                                <div class="font-medium text-slate-800 text-sm mt-0.5 line-clamp-1 max-w-md" title="{{ $t->judul }}">
                                    {{ $t->judul }}
                                </div>
                                <div class="text-[11px] text-slate-400 mt-0.5">
                                    Dibuat: {{ $t->created_at ? $t->created_at->translatedFormat('d M Y, H:i') : '-' }}
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-semibold text-slate-900">
                                    {{ $t->opd->nama_opd ?? $t->skpd ?? 'Belum terpetakan' }}
                                </div>
                                <div class="text-[11px] text-slate-500">
                                    {{ $t->nama_pelapor ?? 'ASN' }}
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-medium text-slate-900">
                                    {{ $t->bidang->nama_bidang ?? 'Belum Didisposisi' }}
                                </div>
                                <div class="text-[11px] text-slate-500">
                                    {{ $t->category->nama_kategori ?? 'Umum' }}
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                <div>
                                    <span class="text-slate-500 text-[11px]">Selesai:</span>
                                    <span class="font-semibold text-slate-800">
                                        {{ $t->selesai_at ? \Carbon\Carbon::parse($t->selesai_at)->translatedFormat('d M Y, H:i') : '-' }}
                                    </span>
                                </div>
                                <div class="text-[11px] text-slate-500 mt-0.5">
                                    Tenggat SLA: {{ $t->sla_deadline_at ? \Carbon\Carbon::parse($t->sla_deadline_at)->translatedFormat('d M Y, H:i') : '-' }}
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex flex-col gap-1">
                                    @php
                                        $statusBadge = match($t->status) {
                                            'pending_verification' => 'bg-amber-50 text-amber-800 border-amber-200',
                                            'verified' => 'bg-blue-50 text-blue-800 border-blue-200',
                                            'in_progress' => 'bg-sky-50 text-sky-800 border-sky-200',
                                            'pending_technician' => 'bg-purple-50 text-purple-800 border-purple-200',
                                            'resolved' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                            'closed' => 'bg-emerald-100 text-emerald-900 border-emerald-300',
                                            'force_closed' => 'bg-slate-100 text-slate-700 border-slate-300',
                                            'reopened' => 'bg-amber-100 text-amber-900 border-amber-300',
                                            'rejected', 'cancelled' => 'bg-rose-50 text-rose-800 border-rose-200',
                                            default => 'bg-slate-50 text-slate-700 border-slate-200'
                                        };
                                    @endphp
                                    <span class="inline-block px-2 py-0.5 rounded text-[11px] font-bold border {{ $statusBadge }} w-fit">
                                        {{ ucfirst(str_replace('_', ' ', $t->status)) }}
                                    </span>

                                    @if($t->sla_excused)
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-sky-700">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Disetujui Pengecualian SLA
                                        </span>
                                    @elseif($isOverdue)
                                        <span class="inline-flex items-center gap-1 text-[10px] font-extrabold text-rose-600">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            SLA Breach (Lewat Batas)
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-600">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Sesuai SLA
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <a href="{{ route('admin.tiket.show', $t->id) }}" class="inline-flex items-center gap-1 px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-lg text-xs shadow-xs transition">
                                    <span>Detail</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="text-sm font-semibold text-slate-600">Tidak ada tiket yang ditemukan untuk metrik ini</p>
                                <p class="text-xs text-slate-400 mt-1">Coba sesuaikan filter waktu atau parameter lainnya.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($tickets->hasPages())
            <div class="p-4 border-t border-slate-200 bg-slate-50/50">
                {{ $tickets->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
