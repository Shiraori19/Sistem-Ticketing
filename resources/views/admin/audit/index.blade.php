@extends('admin.layout', ['title' => 'Audit Log Sistem — DKIS Kota Cirebon'])

@section('content')
<div class="space-y-6">
    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-violet-50 text-violet-800 border border-violet-200 text-xs font-semibold mb-1">
                <span>🛡️</span> Keamanan & Akuntabilitas
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Audit Log & Jejak Aktivitas Sistem</h1>
            <p class="text-xs text-slate-500 mt-1 max-w-3xl leading-relaxed">
                Rekam jejak tidak dapat diubah (immutable audit trail) khusus untuk aksi penanganan tiket penting (Verifikasi, Disposisi, Resolusi, dan Force Close). Aktivitas rutin seperti login/logout sengaja <strong>tidak dicatat</strong> agar database tetap ringan, rapi, dan cepat.
            </p>
        </div>

        <div class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white border border-slate-200 shadow-xs text-xs font-bold text-slate-700">
            <span>Total Catatan Audit:</span>
            <span class="px-2.5 py-0.5 rounded-full bg-violet-600 text-white font-black">{{ $logs->total() }}</span>
        </div>
    </div>

    <!-- Filter Tab Kategori Aksi Cepat -->
    <div class="flex flex-wrap gap-2 text-xs font-semibold">
        @php
            $currentAksi = request('aksi', '');
        @endphp
        <a href="{{ route('admin.audit-log', ['q' => request('q')]) }}"
           class="px-3 py-1.5 rounded-xl border transition {{ empty($currentAksi) ? 'bg-violet-600 text-white border-violet-600 font-bold shadow-xs' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">
            Semua Aksi
        </a>
        <a href="{{ route('admin.audit-log', ['aksi' => 'VERIFIKASI_TIKET', 'q' => request('q')]) }}"
           class="px-3 py-1.5 rounded-xl border transition {{ $currentAksi === 'VERIFIKASI_TIKET' ? 'bg-emerald-600 text-white border-emerald-600 font-bold shadow-xs' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">
            Verifikasi & Disposisi
        </a>
        <a href="{{ route('admin.audit-log', ['aksi' => 'DISPOSISI_TEKNISI', 'q' => request('q')]) }}"
           class="px-3 py-1.5 rounded-xl border transition {{ $currentAksi === 'DISPOSISI_TEKNISI' ? 'bg-blue-600 text-white border-blue-600 font-bold shadow-xs' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">
            Penugasan Teknisi
        </a>
        <a href="{{ route('admin.audit-log', ['aksi' => 'RESOLUSI_TIKET', 'q' => request('q')]) }}"
           class="px-3 py-1.5 rounded-xl border transition {{ $currentAksi === 'RESOLUSI_TIKET' ? 'bg-teal-600 text-white border-teal-600 font-bold shadow-xs' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">
            Resolusi Tiket
        </a>
        <a href="{{ route('admin.audit-log', ['aksi' => 'FORCE_CLOSE_TIKET', 'q' => request('q')]) }}"
           class="px-3 py-1.5 rounded-xl border transition {{ $currentAksi === 'FORCE_CLOSE_TIKET' ? 'bg-rose-600 text-white border-rose-600 font-bold shadow-xs' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">
            Force Close Pusat
        </a>
        <a href="{{ route('admin.audit-log', ['aksi' => 'TAMBAH_AKUN_PETUGAS', 'q' => request('q')]) }}"
           class="px-3 py-1.5 rounded-xl border transition {{ $currentAksi === 'TAMBAH_AKUN_PETUGAS' ? 'bg-sky-600 text-white border-sky-600 font-bold shadow-xs' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">
            Kelola Petugas
        </a>
    </div>

    <!-- Filter Pencarian Log -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200 p-4">
        <form action="{{ route('admin.audit-log') }}" method="GET" class="flex gap-2">
            @if(request('aksi'))
                <input type="hidden" name="aksi" value="{{ request('aksi') }}">
            @endif
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari kode tiket, IP address, atau detail keterangan..."
                class="flex-1 text-xs rounded-xl border border-slate-300 p-2.5 focus:outline-none focus:ring-2 focus:ring-violet-500 transition">
            <button type="submit" class="px-4 py-2.5 bg-violet-600 hover:bg-violet-700 text-white font-bold rounded-xl text-xs shadow-xs transition">
                Cari Log
            </button>
            @if(request('q') || request('aksi'))
                <a href="{{ route('admin.audit-log') }}" class="px-3 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition border border-slate-200">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- Tabel Audit Log -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                    <tr>
                        <th class="py-3.5 px-4">Waktu (WIB)</th>
                        <th class="py-3.5 px-4">Aktor / Pengguna</th>
                        <th class="py-3.5 px-4">Aksi Dilakukan</th>
                        <th class="py-3.5 px-4">Target Tiket</th>
                        <th class="py-3.5 px-4">IP Address</th>
                        <th class="py-3.5 px-4">Keterangan / Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-3 px-4 text-slate-500 whitespace-nowrap font-mono">
                                {{ \Carbon\Carbon::parse($log->timestamp)->translatedFormat('d M Y H:i:s') }}
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="font-bold text-slate-900">{{ $log->user?->nama ?? 'Sistem / Pelapor' }}</span>
                                @if($log->user)
                                    <span class="text-[10px] text-slate-400 block">{{ $log->user->role->nama_role }}</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded font-mono font-bold text-[10px]
                                    @if(str_contains(strtolower($log->aksi), 'force')) bg-rose-100 text-rose-800
                                    @elseif(str_contains(strtolower($log->aksi), 'verify')) bg-emerald-100 text-emerald-800
                                    @elseif(str_contains(strtolower($log->aksi), 'assign')) bg-blue-100 text-blue-800
                                    @else bg-slate-100 text-slate-800 @endif">
                                    {{ $log->aksi }}
                                </span>
                            </td>
                            <td class="py-3 px-4 font-mono font-bold text-sky-700 whitespace-nowrap">
                                {{ $log->target_kode ?? '-' }}
                            </td>
                            <td class="py-3 px-4 font-mono text-slate-500 whitespace-nowrap">
                                {{ $log->ip_address ?? '127.0.0.1' }}
                            </td>
                            <td class="py-3 px-4 text-slate-700 max-w-md">
                                <div class="truncate" title="{{ $log->detail }}">{{ $log->detail ?? '-' }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">
                                Belum ada riwayat audit log tercatat dalam database.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-200">
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection
