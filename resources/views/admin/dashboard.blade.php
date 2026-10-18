@extends('admin.layout', ['title' => 'Dashboard Console — DKIS Kota Cirebon'])

@section('content')
    <div class="space-y-6">
        <!-- Header Salam & Info Peran -->
        <div
            class="bg-white rounded-2xl shadow-xs border border-slate-200/80 p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div
                    class="inline-flex items-center gap-2 px-3 py-1 rounded-full {{ $user->isAdminPusat() ? 'bg-sky-50 text-sky-700 border border-sky-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }} text-xs font-semibold mb-2">
                    <span class="w-2 h-2 rounded-full {{ $user->isAdminPusat() ? 'bg-sky-500' : 'bg-emerald-500' }}"></span>
                    {{ $user->role->nama_role }} &bull;
                    {{ $user->bidang ? $user->bidang->nama_bidang : 'Pusat Tata Kelola DKIS' }}
                </div>
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Selamat Datang, {{ $user->nama }}
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    {{ $user->isAdminPusat() ? 'Monitoring menyeluruh tiket pengaduan seluruh OPD, verifikasi klasifikasi TLP, dan mitigasi eskalasi.' : 'Kelola antrean penugasan teknisi lapangan dan laporkan bukti penyelesaian kendala bidang.' }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                @if($user->isAdminPusat())
                    <a href="{{ route('admin.verifikasi.index') }}"
                        class="px-3.5 py-2 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold rounded-xl text-xs shadow-xs transition flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Antrean Verifikasi ({{ $stats['pending'] }})
                    </a>
                    <a href="{{ route('admin.eskalasi.index') }}"
                        class="px-3.5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl text-xs shadow-xs transition flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        Eskalasi Pusat ({{ $stats['eskalasi'] }})
                    </a>
                @else
                    <a href="{{ route('admin.bidang.tiket.index', ['filter' => 'antrean']) }}"
                        class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-xs transition flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8m-5 5h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293h3.172a1 1 0 00.707-.293l2.414-2.414a1 1 0 01.707-.293H20" />
                        </svg>
                        Antrean Bidang ({{ $bidangAntreanCount ?? 0 }})
                    </a>
                @endif
            </div>
        </div>

        <!-- Peringatan SLA Respon Terlambat untuk Admin Pusat -->
        @if($user->isAdminPusat() && ($stats['pending_respon_breach'] ?? 0) > 0)
            <div
                class="p-4 rounded-2xl bg-amber-50 border border-amber-300 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-amber-950 shadow-xs mb-4">
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 rounded-xl bg-amber-500 text-slate-950 flex items-center justify-center font-bold text-lg shadow-xs flex-shrink-0 animate-bounce">
                        ⏱️
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-amber-950">Peringatan SLA Respon Helpdesk: Terdapat
                            {{ $stats['pending_respon_breach'] }} Tiket Masuk Melebihi Batas Waktu Respon</h3>
                        <p class="text-xs text-amber-800 mt-0.5">Tiket di antrean verifikasi telah melewati batas target respon awal dan memerlukan
                            tindakan penelaahan serta disposisi segera oleh Admin Pusat.</p>
                    </div>
                </div>
                <a href="{{ route('admin.verifikasi.index') }}"
                    class="px-3.5 py-2 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold rounded-xl text-xs transition shadow-xs whitespace-nowrap text-center">
                    Tinjau Antrean Verifikasi &rarr;
                </a>
            </div>
        @endif

        <!-- Peringatan SLA Breach Resolusi Teknis (Jika Ada) -->
        @if(($stats['sla_breach'] ?? 0) > 0)
            <div
                class="p-4 rounded-2xl bg-rose-50 border border-rose-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-rose-900 shadow-xs">
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 rounded-xl bg-rose-600 text-white flex items-center justify-center font-bold text-lg shadow-xs flex-shrink-0 animate-pulse">
                        ⚠️
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-rose-900">Peringatan Kepatuhan SLA: Terdapat
                            {{ $stats['sla_breach'] }} Tiket Melebihi Batas Waktu</h3>
                        <p class="text-xs text-rose-700 mt-0.5">Tiket telah melewati batas target resolusi dan memerlukan
                            tindakan percepatan teknisi atau koordinasi eskalasi.</p>
                    </div>
                </div>
                @if($user->isAdminPusat())
                    <a href="{{ route('admin.tiket.index', ['sla_breach' => 1]) }}"
                        class="px-3.5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl text-xs transition shadow-xs whitespace-nowrap text-center">
                        Tinjau Tiket Breach &rarr;
                    </a>
                @else
                    <a href="{{ route('admin.bidang.tiket.index', ['filter' => 'pengerjaan']) }}"
                        class="px-3.5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl text-xs transition shadow-xs whitespace-nowrap text-center">
                        Periksa Pengerjaan Bidang &rarr;
                    </a>
                @endif
            </div>
        @endif

        <!-- 1. 4 STAT CARDS UTAMA -->
        <div>
            <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3 px-1">Ringkasan Beban Kerja</h2>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Stat 1: Total Tiket -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs hover:border-slate-300 transition">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Tiket</span>
                        <span
                            class="w-8 h-8 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-sm font-black">📁</span>
                    </div>
                    <div class="text-3xl font-black text-slate-900 mt-2">{{ $stats['total'] }}</div>
                    <div class="text-[11px] text-slate-400 mt-1 flex items-center gap-1">
                        <span>Semua tiket tercatat</span>
                    </div>
                </div>

                <!-- Stat 2: Menunggu Verifikasi / Antrean Bidang -->
                <div
                    class="bg-white p-5 rounded-2xl border border-amber-200/80 shadow-xs hover:border-amber-300 transition">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-amber-700 uppercase tracking-wider">{{ $user->isAdminBidang() ? 'Antrean Bidang' : 'Menunggu Verifikasi' }}</span>
                        <span
                            class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-sm font-black">{{ $user->isAdminBidang() ? '📥' : '⏳' }}</span>
                    </div>
                    <div class="text-3xl font-black text-amber-700 mt-2">{{ $stats['pending'] }}</div>
                    <div class="text-[11px] text-amber-600 mt-1 flex items-center gap-1">
                        <span>{{ $user->isAdminBidang() ? 'Siap penugasan teknisi' : 'Perlu verifikasi & klasifikasi' }}</span>
                    </div>
                </div>

                <!-- Stat 3: Sedang Dikerjakan -->
                <div
                    class="bg-white p-5 rounded-2xl border border-violet-200/80 shadow-xs hover:border-violet-300 transition">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-violet-700 uppercase tracking-wider">Sedang Dikerjakan</span>
                        <span
                            class="w-8 h-8 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center text-sm font-black">🔧</span>
                    </div>
                    <div class="text-3xl font-black text-violet-700 mt-2">{{ $stats['in_progress'] }}</div>
                    <div class="text-[11px] text-violet-600 mt-1 flex items-center gap-1">
                        <span>Penanganan teknisi aktif</span>
                    </div>
                </div>

                <!-- Stat 4: Tiket Selesai -->
                <div
                    class="bg-white p-5 rounded-2xl border border-emerald-200/80 shadow-xs hover:border-emerald-300 transition">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-emerald-700 uppercase tracking-wider">Tiket Selesai</span>
                        <span
                            class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm font-black">✅</span>
                    </div>
                    <div class="text-3xl font-black text-emerald-700 mt-2">{{ $stats['selesai'] }}</div>
                    <div class="text-[11px] text-emerald-600 mt-1 flex items-center gap-1">
                        <span>Resolved, closed & arsip</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. 4 TLP CARDS (RED, AMBER, GREEN, CLEAR) -->
        <div>
            <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3 px-1">Distribusi Klasifikasi
                Kerahasiaan (TLP Protocol)</h2>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- TLP RED Card -->
                <div
                    class="bg-gradient-to-br from-rose-900 to-rose-950 text-white p-5 rounded-2xl border border-rose-700/60 shadow-xs relative overflow-hidden">
                    <div class="absolute -right-3 -bottom-3 text-rose-800/30 text-6xl font-black">🔴</div>
                    <div class="flex items-center justify-between relative z-10">
                        <span class="text-xs font-extrabold tracking-wider text-rose-300 flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-400 animate-ping"></span>
                            TLP: RED
                        </span>
                        <span
                            class="text-[10px] bg-rose-500/30 border border-rose-400/40 text-rose-200 px-2 py-0.5 rounded-full uppercase font-bold">Kritis
                            CSIRT</span>
                    </div>
                    <div class="text-3xl font-black text-white mt-3 relative z-10">{{ $stats['tlp_red'] }}</div>
                    <p class="text-[11px] text-rose-200/80 mt-1 relative z-10">Insiden siber & bypass bidang terisolasi</p>
                </div>

                <!-- TLP AMBER Card -->
                <div
                    class="bg-gradient-to-br from-amber-900 to-amber-950 text-white p-5 rounded-2xl border border-amber-700/60 shadow-xs relative overflow-hidden">
                    <div class="absolute -right-3 -bottom-3 text-amber-800/30 text-6xl font-black">🟡</div>
                    <div class="flex items-center justify-between relative z-10">
                        <span class="text-xs font-extrabold tracking-wider text-amber-300 flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                            TLP: AMBER
                        </span>
                        <span
                            class="text-[10px] bg-amber-500/30 border border-amber-400/40 text-amber-200 px-2 py-0.5 rounded-full uppercase font-bold">Terbatas
                            OPD</span>
                    </div>
                    <div class="text-3xl font-black text-white mt-3 relative z-10">{{ $stats['tlp_amber'] }}</div>
                    <p class="text-[11px] text-amber-200/80 mt-1 relative z-10">Kerahasiaan spesifik instansi pelapor</p>
                </div>

                <!-- TLP GREEN Card -->
                <div
                    class="bg-gradient-to-br from-emerald-900 to-emerald-950 text-white p-5 rounded-2xl border border-emerald-700/60 shadow-xs relative overflow-hidden">
                    <div class="absolute -right-3 -bottom-3 text-emerald-800/30 text-6xl font-black">🟢</div>
                    <div class="flex items-center justify-between relative z-10">
                        <span class="text-xs font-extrabold tracking-wider text-emerald-300 flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                            TLP: GREEN
                        </span>
                        <span
                            class="text-[10px] bg-emerald-500/30 border border-emerald-400/40 text-emerald-200 px-2 py-0.5 rounded-full uppercase font-bold">Komunitas
                            ASN</span>
                    </div>
                    <div class="text-3xl font-black text-white mt-3 relative z-10">{{ $stats['tlp_green'] }}</div>
                    <p class="text-[11px] text-emerald-200/80 mt-1 relative z-10">Dapat dibagikan di jajaran Pemkot Cirebon
                    </p>
                </div>

                <!-- TLP CLEAR Card -->
                <div
                    class="bg-gradient-to-br from-slate-800 to-slate-900 text-white p-5 rounded-2xl border border-slate-700/60 shadow-xs relative overflow-hidden">
                    <div class="absolute -right-3 -bottom-3 text-slate-700/30 text-6xl font-black">⚪</div>
                    <div class="flex items-center justify-between relative z-10">
                        <span class="text-xs font-extrabold tracking-wider text-slate-300 flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-slate-300"></span>
                            TLP: CLEAR
                        </span>
                        <span
                            class="text-[10px] bg-slate-600/30 border border-slate-400/40 text-slate-300 px-2 py-0.5 rounded-full uppercase font-bold">Terbuka</span>
                    </div>
                    <div class="text-3xl font-black text-white mt-3 relative z-10">{{ $stats['tlp_clear'] }}</div>
                    <p class="text-[11px] text-slate-300/80 mt-1 relative z-10">Informasi publik tanpa restriksi</p>
                </div>
            </div>
        </div>

        <!-- 3. TABEL 10 TIKET TERBARU -->
        <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden"
            style="content-visibility: auto; contain-intrinsic-size: 0 450px;">
            <div
                class="p-5 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50">
                <div>
                    <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <span>10 Tiket Terbaru Masuk</span>
                        <span class="text-xs font-normal text-slate-500">({{ $recentTickets->count() }} dari
                            {{ $stats['total'] }} total tiket)</span>
                    </h3>
                </div>
                @if($user->isAdminPusat())
                    <a href="{{ route('admin.tiket.index') }}"
                        class="text-xs font-bold text-sky-600 hover:text-sky-800 hover:underline flex items-center gap-1">
                        Buka Arsip Semua Tiket &rarr;
                    </a>
                @else
                    <a href="{{ route('admin.bidang.tiket.index') }}"
                        class="text-xs font-bold text-emerald-600 hover:text-emerald-800 hover:underline flex items-center gap-1">
                        Buka Semua Tiket Bidang &rarr;
                    </a>
                @endif
            </div>

            <div class="overflow-x-auto scrollbar-thin">
                <table class="w-full text-left text-xs border-collapse">
                    <thead
                        class="bg-slate-50/90 text-slate-600 font-bold border-b border-slate-200 uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="py-3.5 px-4 whitespace-nowrap w-36">Kode Tiket</th>
                            <th class="py-3.5 px-4 min-w-[170px]">Pelapor & OPD</th>
                            <th class="py-3.5 px-4 min-w-[200px]">Masalah & Kategori</th>
                            <th class="py-3.5 px-4 min-w-[140px] whitespace-nowrap">Bidang</th>
                            <th class="py-3.5 px-4 text-center whitespace-nowrap w-16">TLP</th>
                            <th class="py-3.5 px-4 text-center whitespace-nowrap min-w-[170px]">Status</th>
                            <th class="py-3.5 px-4 text-center whitespace-nowrap min-w-[160px]">Indikator SLA</th>
                            <th class="py-3.5 px-4 text-right whitespace-nowrap w-24">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($recentTickets as $rt)
                            <tr class="hover:bg-slate-50/80 transition">
                                <!-- Kode Tiket -->
                                <td class="py-3 px-4 font-mono font-bold text-slate-900 whitespace-nowrap">
                                    {{ $rt->kode_tiket }}
                                </td>

                                <!-- Pelapor & OPD -->
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900">{{ $rt->nama_pelapor }}</div>
                                    <div class="text-slate-500 text-[11px] truncate max-w-[200px]" title="{{ $rt->skpd }}">
                                        {{ $rt->skpd }}</div>
                                </td>

                                <!-- Masalah & Kategori -->
                                <td class="py-3 px-4 max-w-xs">
                                    <div class="font-semibold text-slate-900 truncate" title="{{ $rt->judul }}">{{ $rt->judul }}
                                    </div>
                                    <div class="text-slate-500 text-[11px] truncate">{{ $rt->category?->nama_kategori }}</div>
                                </td>

                                <!-- Bidang -->
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span class="font-medium text-slate-700">
                                        {{ $rt->bidang?->nama_bidang ?? 'Bypass CSIRT (Umum)' }}
                                    </span>
                                </td>

                                <!-- TLP -->
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    @if($rt->tlp_level === 'RED')
                                        <span
                                            class="inline-block px-2 py-0.5 rounded text-[10px] font-mono font-black bg-rose-600 text-white shadow-xs">RED</span>
                                    @elseif($rt->tlp_level === 'AMBER')
                                        <span
                                            class="inline-block px-2 py-0.5 rounded text-[10px] font-mono font-black bg-amber-500 text-slate-950 shadow-xs">AMBER</span>
                                    @elseif($rt->tlp_level === 'GREEN')
                                        <span
                                            class="inline-block px-2 py-0.5 rounded text-[10px] font-mono font-black bg-emerald-600 text-white shadow-xs">GREEN</span>
                                    @elseif($rt->tlp_level === 'CLEAR')
                                        <span
                                            class="inline-block px-2 py-0.5 rounded text-[10px] font-mono font-black bg-slate-500 text-white shadow-xs">CLEAR</span>
                                    @else
                                        <span class="text-slate-400 text-[10px] font-mono">-</span>
                                    @endif
                                </td>

                                <!-- Status (Universal Component) -->
                                <td class="py-3 px-4 text-center whitespace-nowrap min-w-[170px]">
                                    <x-status-badge :status="$rt->status" />
                                </td>

                                <!-- SLA Indicator (Universal Component) -->
                                <td class="py-3 px-4 text-center whitespace-nowrap min-w-[160px]">
                                    <x-sla-indicator :ticket="$rt" />
                                </td>

                                <!-- Aksi Langsung di Dashboard -->
                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                    @if($user->isAdminPusat())
                                        @if($rt->status === 'pending_verification')
                                            <button type="button" onclick="openVerifyModalDashboard({{ $rt->id }})"
                                                class="px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold rounded-xl text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                                                <span>⚡ Verifikasi</span>
                                            </button>
                                        @else
                                            <button type="button" onclick="openDetailModalDashboard({{ $rt->id }})"
                                                class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 font-semibold rounded-xl text-xs transition border border-slate-300 inline-flex items-center gap-1.5 cursor-pointer">
                                                <span>👁️ Detail</span>
                                            </button>
                                        @endif
                                    @else
                                        @php
                                            // Arahkan ke tab yang tepat di sidebar bidang:
                                            $targetFilter = match ($rt->status) {
                                                'in_progress' => 'pengerjaan',
                                                'resolved', 'closed', 'force_closed' => 'arsip',
                                                default => 'antrean',
                                            };

                                            $btnText = match ($rt->status) {
                                                'verified', 'reopened' => 'Tangani &rarr;',
                                                'in_progress' => 'Tangani &rarr;',
                                                default => 'Detail &rarr;',
                                            };

                                            $btnStyle = match ($rt->status) {
                                                'verified', 'reopened' => 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs',
                                                'in_progress' => 'bg-sky-600 hover:bg-sky-700 text-white shadow-xs',
                                                default => 'bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300',
                                            };
                                        @endphp
                                        <a href="{{ route('admin.bidang.tiket.index', ['filter' => $targetFilter, 'q' => $rt->kode_tiket]) }}"
                                            class="px-2.5 py-1 rounded-lg text-xs font-bold transition {{ $btnStyle }}">
                                            {!! $btnText !!}
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center text-slate-400">
                                    Belum ada tiket pengaduan tercatat dalam sistem.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($user->isAdminPusat())
        <!-- ================================================================= -->
        <div id="verify-modal-dashboard"
            class="admin-verify-modal-wrapper hidden"
            style="position: fixed; inset: 0; z-index: 9999; background-color: rgba(15, 23, 42, 0.82); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; padding: 1rem; box-sizing: border-box;">
            <div
                class="admin-verify-modal-card"
                style="background: #ffffff; width: 95%; max-width: 1140px; max-height: calc(100vh - 2.5rem); border-radius: 1.5rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.45); border: 1px solid #cbd5e1; display: flex; flex-direction: column; overflow: hidden; margin: auto;">
                <!-- Header Modal -->
                <div class="bg-gradient-to-r from-[#0f274a] via-[#163a6f] to-[#0f274a] text-white px-5 py-4 flex items-start justify-between border-b border-blue-900/60" style="flex-shrink: 0;">
                    <div class="space-y-1 max-w-[85%]">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span id="dash-modal-kode"
                                class="font-mono text-sm font-black bg-sky-500/25 text-sky-200 px-2.5 py-0.5 rounded border border-sky-400/30">
                                TIK-XXXXXX-XXXX
                            </span>
                            <span id="dash-modal-status-badge" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-400/20 text-amber-200 border border-amber-300/30 uppercase tracking-wide">
                                Pending Verification
                            </span>
                            <span id="dash-modal-sla-badge" class="hidden text-[10px] font-mono px-2 py-0.5 rounded-full font-bold"></span>
                        </div>
                        <h3 id="dash-modal-judul" class="text-base sm:text-lg font-black text-white leading-snug line-clamp-1">
                            Judul Permasalahan
                        </h3>
                        <p id="dash-modal-pelapor-sub" class="text-xs text-sky-200/90 flex items-center gap-1.5 flex-wrap">
                            Diajukan pada: -
                        </p>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <a id="dash-modal-full-link" href="#" target="_blank"
                            class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-semibold border border-white/15 transition shadow-2xs"
                            title="Buka halaman penuh tiket di tab baru">
                            <span>Lihat Detail Penuh</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </a>
                        <button type="button" onclick="closeVerifyModalDashboard()"
                            class="text-slate-300 hover:text-white p-1.5 rounded-xl hover:bg-white/10 transition cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Body Modal Scrollable (2-Column Grid di Desktop) -->
                <div class="p-4 sm:p-5 overflow-y-auto scrollbar-thin text-xs" style="flex: 1 1 auto; max-height: calc(100vh - 8rem);">
                    <!-- Alert Catatan Kelengkapan Aktif Jika Ada -->
                    <div id="dash-modal-kelengkapan-alert"
                        class="hidden mb-4 p-3.5 bg-amber-50 border border-amber-300 rounded-2xl text-xs">
                        <div class="flex items-center gap-1.5 font-bold text-amber-900" id="dash-modal-kelengkapan-title">
                            <span>⚠️</span>
                            <span>Permintaan Kelengkapan Data Aktif:</span>
                        </div>
                        <p id="dash-modal-kelengkapan-notes" class="text-amber-800 mt-1 leading-relaxed"></p>
                    </div>

                    <div class="admin-verify-modal-grid" style="display: grid; grid-template-columns: 58% calc(42% - 1.25rem); gap: 1.25rem; align-items: start;">
                        <!-- ============================================== -->
                        <!-- KOLOM KIRI (58%): RINCIAN TIKET & LAMPIRAN     -->
                        <!-- ============================================== -->
                        <div class="space-y-4" style="min-width: 0;">
                            <!-- BAGIAN 1: RINCIAN INFORMASI TIKET & PELAPOR -->
                            <div class="bg-slate-50 border border-slate-200/90 rounded-2xl p-3.5 space-y-3">
                                <div class="flex items-center justify-between border-b border-slate-200/80 pb-2">
                                    <span class="text-[11px] font-bold text-slate-600 uppercase tracking-wider flex items-center gap-1.5">
                                        <span>📋</span> Informasi Pelapor &amp; Layanan
                                    </span>
                                    <span class="text-[10px] text-slate-500 font-medium" id="dash-modal-waktu-text">-</span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <!-- Identitas Pelapor -->
                                    <div class="space-y-0.5">
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Pelapor &amp; OPD</span>
                                        <div class="font-black text-slate-900 text-xs sm:text-sm line-clamp-1" id="dash-modal-pelapor-nama">-</div>
                                        <div class="text-[11px] text-slate-600 font-medium line-clamp-1" id="dash-modal-pelapor-skpd">-</div>
                                        <div class="font-mono text-[10px] text-slate-400" id="dash-modal-pelapor-nip">NIP: -</div>
                                    </div>

                                    <!-- Kontak WhatsApp -->
                                    <div class="space-y-0.5">
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Kontak WhatsApp</span>
                                        <div id="dash-modal-pelapor-wa" class="pt-0.5">-</div>
                                        <p class="text-[9px] text-slate-400 mt-0.5">Klik untuk konfirmasi via WA</p>
                                    </div>

                                    <!-- Kategori Layanan -->
                                    <div class="space-y-0.5">
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Kategori Layanan</span>
                                        <div class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-blue-50 text-blue-700 border border-blue-200 font-bold text-xs" id="dash-modal-kategori-text">
                                            -
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- BAGIAN 2: RINCIAN KELUHAN / KENDALA PELAPOR -->
                            <div>
                                <span class="text-[11px] font-bold text-slate-600 uppercase tracking-wider block mb-1 flex items-center gap-1.5">
                                    <span>📝</span> Rincian Keluhan / Kendala:
                                </span>
                                <div id="dash-modal-deskripsi"
                                    class="text-xs text-slate-800 bg-slate-50/80 p-3.5 rounded-2xl border border-slate-200 whitespace-pre-line leading-relaxed font-normal shadow-2xs max-h-44 overflow-y-auto scrollbar-thin">
                                    -
                                </div>
                            </div>

                            <!-- BAGIAN 3: BUKTI & LAMPIRAN PENGADUAN (INLINE PREVIEW LANGSUNG) -->
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-bold text-slate-600 uppercase tracking-wider flex items-center gap-1.5">
                                        <span>📎</span> Bukti &amp; Berkas Lampiran Pelapor:
                                    </span>
                                    <span class="text-[10px] text-slate-400 italic">
                                        👁️ Klik untuk pratinjau langsung
                                    </span>
                                </div>

                                <!-- Grid Kartu Preview Lampiran -->
                                <div id="dash-modal-lampiran-grid" class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                    <!-- Injected by JavaScript -->
                                </div>

                                <!-- State Jika Tidak Ada Lampiran -->
                                <div id="dash-modal-lampiran-empty" class="hidden p-3.5 rounded-2xl bg-slate-50 border border-dashed border-slate-300 text-center text-slate-400">
                                    <span class="text-sm block mb-0.5">ℹ️</span>
                                    <span class="text-[11px] font-medium text-slate-500">Pelapor tidak menyertakan berkas bukti fisik / tangkapan layar.</span>
                                </div>
                            </div>
                        </div>

                        <!-- ============================================== -->
                        <!-- KOLOM KANAN (42%): PANEL VERIFIKASI & AKSI    -->
                        <!-- ============================================== -->
                        <div class="bg-slate-50/90 border border-slate-200/90 rounded-2xl p-4 sm:p-5 shadow-2xs space-y-4" style="min-width: 0;">
                            <div class="border-b border-slate-200/80 pb-2 flex items-center justify-between">
                                <h4 class="font-bold text-xs uppercase text-slate-800 tracking-wider flex items-center gap-1.5">
                                    <span>⚡</span> Disposisi &amp; Verifikasi Tiket
                                </h4>
                                <span class="text-[10px] text-slate-400 font-semibold">Tindakan Admin</span>
                            </div>

                            <form id="dash-verify-form" method="POST" action="" class="space-y-3.5">
                                @csrf
                                <input type="hidden" name="catatan" id="dash-hidden-catatan" value="">

                                <!-- 1. RADIO TLP LEVEL -->
                                <div>
                                    <label class="block text-xs font-bold text-slate-800 mb-1.5">
                                        1. Klasifikasi TLP (Keamanan Data) *
                                    </label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <label
                                            class="relative flex items-start p-2 rounded-xl border border-slate-200 hover:border-rose-300 bg-white cursor-pointer transition select-none has-[:checked]:border-rose-500 has-[:checked]:bg-rose-50/50 has-[:checked]:ring-1 has-[:checked]:ring-rose-500">
                                            <input type="radio" name="tlp_level" value="RED" onchange="handleDashTlpSelect('RED')"
                                                class="mt-0.5 text-rose-600 focus:ring-rose-500">
                                            <div class="ml-2 text-xs">
                                                <span class="font-bold text-rose-700 block text-xs">🔴 RED</span>
                                                <span class="text-[10px] text-slate-500 leading-tight block">Sangat Rahasia</span>
                                            </div>
                                        </label>

                                        <label
                                            class="relative flex items-start p-2 rounded-xl border border-slate-200 hover:border-amber-300 bg-white cursor-pointer transition select-none has-[:checked]:border-amber-500 has-[:checked]:bg-amber-50/50 has-[:checked]:ring-1 has-[:checked]:ring-amber-500">
                                            <input type="radio" name="tlp_level" value="AMBER" onchange="handleDashTlpSelect('AMBER')"
                                                class="mt-0.5 text-amber-600 focus:ring-amber-500">
                                            <div class="ml-2 text-xs">
                                                <span class="font-bold text-amber-700 block text-xs">🟡 AMBER</span>
                                                <span class="text-[10px] text-slate-500 leading-tight block">Terbatas OPD</span>
                                            </div>
                                        </label>

                                        <label
                                            class="relative flex items-start p-2 rounded-xl border border-slate-200 hover:border-emerald-300 bg-white cursor-pointer transition select-none has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/50 has-[:checked]:ring-1 has-[:checked]:ring-emerald-500">
                                            <input type="radio" name="tlp_level" value="GREEN" checked
                                                onchange="handleDashTlpSelect('GREEN')"
                                                class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                                            <div class="ml-2 text-xs">
                                                <span class="font-bold text-emerald-700 block text-xs">🟢 GREEN</span>
                                                <span class="text-[10px] text-slate-500 leading-tight block">Komunitas ASN</span>
                                            </div>
                                        </label>

                                        <label
                                            class="relative flex items-start p-2 rounded-xl border border-slate-200 hover:border-slate-400 bg-white cursor-pointer transition select-none has-[:checked]:border-slate-500 has-[:checked]:bg-slate-100 has-[:checked]:ring-1 has-[:checked]:ring-slate-500">
                                            <input type="radio" name="tlp_level" value="CLEAR" onchange="handleDashTlpSelect('CLEAR')"
                                                class="mt-0.5 text-slate-600 focus:ring-slate-500">
                                            <div class="ml-2 text-xs">
                                                <span class="font-bold text-slate-700 block text-xs">⚪ CLEAR</span>
                                                <span class="text-[10px] text-slate-500 leading-tight block">Terbuka Umum</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <!-- 2. DROPDOWN BIDANG -->
                                <div>
                                    <label for="dash-modal-select-bidang" class="block text-xs font-bold text-slate-800 mb-1">
                                        2. Teruskan Disposisi ke Bidang *
                                    </label>
                                    <select name="bidang_id" id="dash-modal-select-bidang" required
                                        class="w-full text-xs rounded-xl border border-slate-300 p-2 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500 transition">
                                        <option value="">-- Pilih Bidang Penanggung Jawab --</option>
                                        @foreach($bidangList as $b)
                                            <option value="{{ $b->id }}">{{ $b->nama_lengkap ?? $b->nama_bidang }}</option>
                                        @endforeach
                                    </select>

                                    <div id="dash-red-bypass-warning"
                                        class="hidden mt-2 p-2.5 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-900 flex items-start gap-1.5">
                                        <svg class="w-4 h-4 text-rose-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        <div class="text-[11px] leading-tight text-rose-800">
                                            <strong class="font-bold">Bypass CSIRT:</strong> Tiket TLP RED otomatis ditugaskan ke Bidang Persandian.
                                        </div>
                                    </div>
                                </div>

                                <!-- 3. TEXTAREA CATATAN -->
                                <div>
                                    <label for="dash-modal-notes" class="block text-xs font-bold text-slate-800 mb-1">
                                        3. Catatan Disposisi / Arahan Teknis:
                                    </label>
                                    <textarea name="notes" id="dash-modal-notes" rows="3"
                                        placeholder="Tuliskan petunjuk penanganan atau arahan teknis untuk bidang..."
                                        class="w-full text-xs rounded-xl border border-slate-300 p-2.5 focus:outline-none focus:ring-2 focus:ring-sky-500 transition bg-white"></textarea>
                                </div>

                                <!-- 4. TOMBOL AKSI GANDA -->
                                <div class="pt-2 border-t border-slate-200 flex flex-col gap-2">
                                    <button type="button" id="dash-btn-submit-verify" onclick="submitDashVerifikasi()"
                                        class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs transition shadow-sm flex items-center justify-center gap-1.5 cursor-pointer">
                                        <span>✅ Verifikasi & Disposisi</span>
                                    </button>

                                    <button type="button" onclick="submitDashMintaKelengkapan()"
                                        class="w-full py-2 px-3 bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-300 font-bold rounded-xl text-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                                        <span>⚠️ Minta Kelengkapan Data</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================================================================= -->
        <!-- MODAL QUICK DETAIL TIKET LANGSUNG DI DASHBOARD -->
        <!-- ================================================================= -->
        <div id="detail-modal-dashboard"
            class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4">
            <div
                class="bg-white rounded-3xl max-w-2xl w-full shadow-2xl border border-slate-200 overflow-hidden transform transition-all my-8 animate-in fade-in zoom-in duration-150">
                <div class="bg-slate-900 text-white p-5 flex items-start justify-between border-b border-slate-800">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span id="detail-modal-kode"
                                class="font-mono text-sm font-black bg-white/10 text-white px-2.5 py-0.5 rounded border border-white/20">
                                TIK-XXXX
                            </span>
                            <span id="detail-modal-status-badge"></span>
                            <span id="detail-modal-tlp-badge"></span>
                        </div>
                        <h3 id="detail-modal-judul" class="text-base font-bold text-white mt-1 line-clamp-1">-</h3>
                        <p id="detail-modal-sub" class="text-xs text-slate-400 mt-0.5">-</p>
                    </div>
                    <button type="button" onclick="closeDetailModalDashboard()"
                        class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-white/10 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-5 sm:p-6 space-y-4 max-h-[70vh] overflow-y-auto scrollbar-thin text-xs">
                    <!-- Info Pelapor & Kontak -->
                    <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200 grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <span class="text-[10px] text-slate-400 font-bold uppercase block">Identitas Pelapor</span>
                            <span id="detail-modal-pelapor-nama" class="font-bold text-slate-800 text-xs block">-</span>
                            <span id="detail-modal-pelapor-skpd" class="text-slate-500 text-[11px] block">-</span>
                            <span id="detail-modal-pelapor-nip" class="font-mono text-slate-400 text-[10px] block">-</span>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-400 font-bold uppercase block">Kontak & Penanganan</span>
                            <div id="detail-modal-pelapor-wa" class="font-semibold text-blue-700 mt-0.5">-</div>
                            <div class="mt-1 text-slate-600">
                                <span>Bidang: <strong id="detail-modal-bidang" class="text-slate-800">-</strong></span> &bull;
                                <span>Teknisi: <strong id="detail-modal-teknisi" class="text-slate-800">-</strong></span>
                            </div>
                        </div>
                    </div>

                    <!-- Deskripsi Masalah -->
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Rincian
                            Keluhan:</span>
                        <div id="detail-modal-deskripsi"
                            class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 text-slate-700 leading-relaxed whitespace-pre-line">
                            -
                        </div>
                    </div>

                    <!-- Lampiran Berkas -->
                    <div id="detail-modal-lampiran-wrapper"
                        class="hidden p-3 bg-blue-50/60 rounded-xl border border-blue-200 text-blue-900 space-y-1">
                        <span class="font-bold text-[11px] block">📎 Berkas Pendukung:</span>
                        <div id="detail-modal-lampiran-links" class="flex flex-wrap gap-3"></div>
                    </div>

                    <!-- Catatan Resolusi (Jika Ada) -->
                    <div id="detail-modal-resolusi-wrapper"
                        class="hidden p-3.5 bg-emerald-50 rounded-xl border border-emerald-200 text-emerald-950 space-y-1">
                        <span class="font-bold text-[11px] block">✅ Catatan Hasil Perbaikan / Resolusi:</span>
                        <p id="detail-modal-resolusi-text" class="text-xs leading-relaxed"></p>
                        <div id="detail-modal-resolusi-link" class="pt-1"></div>
                    </div>

                    <!-- Timeline Status History -->
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-2">📜 Riwayat Status
                            & Jejak Audit:</span>
                        <div id="detail-modal-history-list" class="border-l-2 border-slate-300 ml-4 my-2 space-y-4">
                            <!-- Dynamic -->
                        </div>
                    </div>

                    <!-- Tombol Buka Halaman Lengkap -->
                    <div class="pt-3 border-t border-slate-200 flex items-center justify-between">
                        <span class="text-[11px] text-slate-400">Tindakan cepat tanpa berpindah halaman</span>
                        <a id="detail-modal-full-link" href="#"
                            class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl text-xs transition flex items-center gap-1.5">
                            <span>Buka Halaman Penuh</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @php
            $dashTicketsJsonMap = $recentTickets->keyBy('id')->map(function ($t) {
                $targetMins = max(1, (int) round(($t->sla_respon_jam ?: 72) * 60));
                $deadlineRespon = $t->sla_respon_deadline_at ? \Carbon\Carbon::parse($t->sla_respon_deadline_at) : ($t->created_at ? $t->created_at->copy()->addMinutes($targetMins) : null);
                $isOverdue = $deadlineRespon ? now()->gt($deadlineRespon) : false;
                $diffMins = $deadlineRespon ? abs(now()->diffInMinutes($deadlineRespon)) : 0;
                $diffText = $diffMins >= 60 ? floor($diffMins / 60) . 'j ' . ($diffMins % 60) . 'm' : $diffMins . 'm';
                $targetText = $targetMins >= 60 ? floor($targetMins / 60) . ' jam' : $targetMins . 'm';
                $slaText = $isOverdue ? "⚠️ Telat {$diffText} (Target: {$targetText})" : "⏱️ Sisa {$diffText} (Target: {$targetText})";

                return [
                    'id' => $t->id,
                    'kode_tiket' => $t->kode_tiket,
                    'nama_pelapor' => $t->nama_pelapor,
                    'nip_pelapor' => $t->nip_pelapor,
                    'skpd' => $t->skpd,
                    'no_whatsapp' => $t->no_whatsapp,
                    'judul' => $t->judul,
                    'deskripsi' => $t->deskripsi,
                    'kategori_nama' => $t->category?->nama_kategori ?? 'Umum',
                    'lampiran_pelapor' => $t->lampiran_pelapor,
                    'lampiran_tambahan' => $t->lampiran_tambahan,
                    'catatan_kelengkapan' => $t->catatan_kelengkapan,
                    'catatan_resolusi' => $t->catatan_resolusi,
                    'bukti_resolusi' => $t->bukti_resolusi,
                    'bidang_id' => $t->bidang_id,
                    'nama_bidang' => $t->bidang?->nama_bidang ?? 'Bypass CSIRT',
                    'teknisi_lapangan' => $t->teknisi_lapangan ?? 'Belum Ditugaskan',
                    'tlp_level' => $t->tlp_level,
                    'status' => $t->status,
                    'is_overdue' => $isOverdue,
                    'sla_respon_text' => $slaText,
                    'status_history' => $t->status_history ?? [],
                    'created_at_fmt' => $t->created_at ? $t->created_at->translatedFormat('d M Y, H:i') : '-',
                ];
            });
        @endphp

        <script>
            const dashTicketsData = @json($dashTicketsJsonMap);

            // ==========================================
            // 1. MODAL VERIFIKASI LANGSUNG DI DASHBOARD
            // ==========================================
            window.openVerifyModalDashboard = function (ticketId) {
                const ticket = dashTicketsData[ticketId];
                if (!ticket) return;

                // Header Info
                document.getElementById('dash-modal-kode').textContent = ticket.kode_tiket;
                document.getElementById('dash-modal-judul').textContent = ticket.judul;
                document.getElementById('dash-modal-pelapor-sub').textContent = `Diajukan pada: ${ticket.created_at_fmt} WIB \u2022 ${ticket.skpd}`;
                document.getElementById('dash-modal-deskripsi').textContent = ticket.deskripsi;

                // Full detail link
                const fullLink = document.getElementById('dash-modal-full-link');
                if (fullLink) fullLink.href = `/admin/tiket/${ticket.id}`;

                // Status Badge
                const statusBadge = document.getElementById('dash-modal-status-badge');
                if (statusBadge) {
                    statusBadge.textContent = (ticket.status || '').replace(/_/g, ' ').toUpperCase();
                }

                // SLA Badge
                const slaBadge = document.getElementById('dash-modal-sla-badge');
                if (slaBadge && ticket.sla_respon_text) {
                    slaBadge.className = ticket.is_overdue 
                        ? 'inline-flex items-center gap-1 text-[10px] font-mono px-2 py-0.5 rounded-full font-bold bg-rose-500/20 text-rose-300 border border-rose-400/30'
                        : 'inline-flex items-center gap-1 text-[10px] font-mono px-2 py-0.5 rounded-full font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30';
                    slaBadge.textContent = ticket.sla_respon_text;
                    slaBadge.classList.remove('hidden');
                } else if (slaBadge) {
                    slaBadge.classList.add('hidden');
                }

                // Metadata Card
                document.getElementById('dash-modal-pelapor-nama').textContent = ticket.nama_pelapor;
                document.getElementById('dash-modal-pelapor-skpd').textContent = ticket.skpd;
                document.getElementById('dash-modal-pelapor-nip').textContent = ticket.nip_pelapor ? `NIP: ${ticket.nip_pelapor}` : 'NIP: -';
                document.getElementById('dash-modal-kategori-text').textContent = ticket.kategori_nama || 'Umum';
                document.getElementById('dash-modal-waktu-text').textContent = `${ticket.created_at_fmt} WIB`;

                // WhatsApp Contact with direct link
                const waContainer = document.getElementById('dash-modal-pelapor-wa');
                if (ticket.no_whatsapp) {
                    const cleanWa = ticket.no_whatsapp.replace(/[^0-9]/g, '');
                    waContainer.innerHTML = `
                        <a href="https://wa.me/${cleanWa}" target="_blank"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 font-bold text-xs transition shadow-2xs">
                            <span>💬</span> <span>${ticket.no_whatsapp}</span>
                            <span class="text-[10px] text-emerald-600 font-normal">(Chat WA ↗)</span>
                        </a>
                    `;
                } else {
                    waContainer.textContent = '-';
                }

                // Catatan Kelengkapan Alert jika ada
                const alertKelengkapan = document.getElementById('dash-modal-kelengkapan-alert');
                const titleKelengkapan = document.getElementById('dash-modal-kelengkapan-title');
                const notesKelengkapan = document.getElementById('dash-modal-kelengkapan-notes');
                const btnVerify = document.getElementById('dash-btn-submit-verify');

                if (ticket.catatan_kelengkapan) {
                    notesKelengkapan.textContent = ticket.catatan_kelengkapan;
                    if (ticket.catatan_kelengkapan.includes('Dikembalikan oleh Bidang')) {
                        alertKelengkapan.className = 'p-3.5 bg-orange-50 border border-orange-300 rounded-2xl text-xs';
                        titleKelengkapan.className = 'flex items-center gap-1.5 font-extrabold text-orange-950';
                        titleKelengkapan.innerHTML = '<span>↩️</span><span>Catatan Pengembalian Disposisi dari Bidang:</span>';
                        notesKelengkapan.className = 'text-orange-900 mt-1 leading-relaxed font-semibold';
                        btnVerify.innerHTML = '<span>⚡ Verifikasi & Disposisi Ulang</span>';
                    } else {
                        alertKelengkapan.className = 'p-3.5 bg-amber-50 border border-amber-300 rounded-2xl text-xs';
                        titleKelengkapan.className = 'flex items-center gap-1.5 font-bold text-amber-900';
                        titleKelengkapan.innerHTML = '<span>⚠️</span><span>Permintaan Kelengkapan Data Aktif:</span>';
                        notesKelengkapan.className = 'text-amber-800 mt-1 leading-relaxed';
                        btnVerify.innerHTML = '<span>✅ Verifikasi & Disposisi</span>';
                    }
                    alertKelengkapan.classList.remove('hidden');
                } else {
                    alertKelengkapan.classList.add('hidden');
                    btnVerify.innerHTML = '<span>✅ Verifikasi & Disposisi</span>';
                }

                // Render Lampiran Cards with Direct Inline Thumbnail & In-App Preview
                const lampiranCardsContainer = document.getElementById('dash-modal-lampiran-grid');
                const lampiranEmptyState = document.getElementById('dash-modal-lampiran-empty');
                lampiranCardsContainer.innerHTML = '';

                let totalAttachments = 0;

                // 1. Lampiran Utama Pelapor
                if (ticket.lampiran_pelapor) {
                    let files = Array.isArray(ticket.lampiran_pelapor) 
                        ? ticket.lampiran_pelapor 
                        : (typeof ticket.lampiran_pelapor === 'string' && ticket.lampiran_pelapor.startsWith('[') ? JSON.parse(ticket.lampiran_pelapor) : [ticket.lampiran_pelapor]);

                    files = files.filter(Boolean);

                    files.forEach((filePath, idx) => {
                        totalAttachments++;
                        const ext = filePath.split('.').pop().toLowerCase();
                        const isImage = ['jpg', 'jpeg', 'png', 'webp', 'gif'].includes(ext);
                        const isPdf = ext === 'pdf';
                        const fileName = filePath.split('/').pop();
                        const previewUrl = `/admin/tiket/${ticket.kode_tiket}/berkas/lampiran?file=${encodeURIComponent(filePath)}`;
                        const downloadUrl = `${previewUrl}&download=1`;

                        const card = document.createElement('div');
                        card.className = 'group relative rounded-2xl border border-slate-200 bg-white hover:border-blue-400 hover:shadow-md transition-all overflow-hidden flex flex-col';

                        if (isImage) {
                            card.innerHTML = `
                                <div class="relative h-28 w-full bg-slate-900 cursor-pointer overflow-hidden" onclick="openInAppPreview('${previewUrl}', '${fileName}')">
                                    <img src="${previewUrl}" alt="${fileName}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200" loading="lazy">
                                    <div class="absolute inset-0 bg-slate-950/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-1.5 text-white font-bold text-xs">
                                        <span>👁️</span> <span>Pratinjau Penuh</span>
                                    </div>
                                    <span class="absolute top-2 left-2 px-2 py-0.5 rounded-md bg-blue-600/90 text-white font-bold text-[10px] uppercase backdrop-blur-xs">
                                        ${ext.toUpperCase()}
                                    </span>
                                </div>
                                <div class="p-2.5 flex-1 flex flex-col justify-between">
                                    <div class="truncate text-xs font-semibold text-slate-800 mb-2" title="${fileName}">${fileName}</div>
                                    <div class="flex items-center gap-1.5 pt-1 border-t border-slate-100">
                                        <button type="button" onclick="openInAppPreview('${previewUrl}', '${fileName}')" class="flex-1 py-1 px-2 rounded-lg bg-blue-50 hover:bg-blue-600 hover:text-white text-blue-700 font-bold text-[11px] transition text-center cursor-pointer">
                                            👁️ Pratinjau
                                        </button>
                                        <a href="${downloadUrl}" download class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition" title="Unduh Berkas Asli">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                        </a>
                                    </div>
                                </div>
                            `;
                        } else if (isPdf) {
                            card.innerHTML = `
                                <div class="h-28 w-full bg-gradient-to-br from-rose-50 to-rose-100/60 cursor-pointer flex flex-col items-center justify-center p-3 text-rose-600 border-b border-rose-100 group-hover:bg-rose-100/80 transition" onclick="openInAppPreview('${previewUrl}', '${fileName}')">
                                    <div class="w-10 h-10 rounded-xl bg-white text-rose-600 flex items-center justify-center text-xl shadow-xs mb-1">
                                        📄
                                    </div>
                                    <span class="text-[10px] font-bold text-rose-700 uppercase tracking-wider">Dokumen PDF</span>
                                    <span class="text-[10px] text-rose-500 font-medium">Klik untuk Pratinjau</span>
                                </div>
                                <div class="p-2.5 flex-1 flex flex-col justify-between">
                                    <div class="truncate text-xs font-semibold text-slate-800 mb-2" title="${fileName}">${fileName}</div>
                                    <div class="flex items-center gap-1.5 pt-1 border-t border-slate-100">
                                        <button type="button" onclick="openInAppPreview('${previewUrl}', '${fileName}')" class="flex-1 py-1 px-2 rounded-lg bg-rose-50 hover:bg-rose-600 hover:text-white text-rose-700 font-bold text-[11px] transition text-center cursor-pointer">
                                            👁️ Buka PDF
                                        </button>
                                        <a href="${downloadUrl}" download class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition" title="Unduh Berkas Asli">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                        </a>
                                    </div>
                                </div>
                            `;
                        } else {
                            card.innerHTML = `
                                <div class="h-28 w-full bg-slate-100 flex flex-col items-center justify-center p-3 text-slate-500 border-b border-slate-200">
                                    <div class="w-10 h-10 rounded-xl bg-white text-slate-600 flex items-center justify-center text-xl shadow-xs mb-1">
                                        📎
                                    </div>
                                    <span class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">${ext.toUpperCase()}</span>
                                </div>
                                <div class="p-2.5 flex-1 flex flex-col justify-between">
                                    <div class="truncate text-xs font-semibold text-slate-800 mb-2" title="${fileName}">${fileName}</div>
                                    <div class="flex items-center gap-1.5 pt-1 border-t border-slate-100">
                                        <button type="button" onclick="openInAppPreview('${previewUrl}', '${fileName}')" class="flex-1 py-1 px-2 rounded-lg bg-slate-100 hover:bg-slate-800 hover:text-white text-slate-700 font-bold text-[11px] transition text-center cursor-pointer">
                                            👁️ Pratinjau
                                        </button>
                                        <a href="${downloadUrl}" download class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition" title="Unduh Berkas Asli">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                        </a>
                                    </div>
                                </div>
                            `;
                        }

                        lampiranCardsContainer.appendChild(card);
                    });
                }

                // 2. Berkas Tambahan Pelapor (jika ada)
                if (ticket.lampiran_tambahan) {
                    totalAttachments++;
                    const filePath = ticket.lampiran_tambahan;
                    const ext = filePath.split('.').pop().toLowerCase();
                    const isImage = ['jpg', 'jpeg', 'png', 'webp', 'gif'].includes(ext);
                    const isPdf = ext === 'pdf';
                    const fileName = filePath.split('/').pop();
                    const previewUrl = `/admin/tiket/${ticket.kode_tiket}/berkas/lampiran_tambahan`;
                    const downloadUrl = `${previewUrl}?download=1`;

                    const card = document.createElement('div');
                    card.className = 'group relative rounded-2xl border-2 border-emerald-300 bg-emerald-50/40 hover:border-emerald-500 hover:shadow-md transition-all overflow-hidden flex flex-col';

                    if (isImage) {
                        card.innerHTML = `
                            <div class="relative h-28 w-full bg-slate-900 cursor-pointer overflow-hidden" onclick="openInAppPreview('${previewUrl}', 'Berkas Tambahan - ${fileName}')">
                                <img src="${previewUrl}" alt="${fileName}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200" loading="lazy">
                                <div class="absolute inset-0 bg-slate-950/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-1.5 text-white font-bold text-xs">
                                    <span>👁️</span> <span>Pratinjau Tambahan</span>
                                </div>
                                <span class="absolute top-2 left-2 px-2 py-0.5 rounded-md bg-emerald-600 text-white font-bold text-[10px] uppercase backdrop-blur-xs">
                                    Tambahan • ${ext.toUpperCase()}
                                </span>
                            </div>
                            <div class="p-2.5 flex-1 flex flex-col justify-between">
                                <div class="truncate text-xs font-semibold text-emerald-950 mb-2" title="${fileName}">⭐ Berkas Tambahan: ${fileName}</div>
                                <div class="flex items-center gap-1.5 pt-1 border-t border-emerald-100">
                                    <button type="button" onclick="openInAppPreview('${previewUrl}', 'Berkas Tambahan - ${fileName}')" class="flex-1 py-1 px-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] transition text-center cursor-pointer">
                                        👁️ Pratinjau
                                    </button>
                                    <a href="${downloadUrl}" download class="p-1 rounded-lg text-emerald-600 hover:text-emerald-900 hover:bg-emerald-100 transition" title="Unduh Berkas Asli">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    </a>
                                </div>
                            </div>
                        `;
                    } else {
                        card.innerHTML = `
                            <div class="h-28 w-full bg-gradient-to-br from-emerald-100 to-emerald-200/60 cursor-pointer flex flex-col items-center justify-center p-3 text-emerald-800 border-b border-emerald-200 group-hover:bg-emerald-200/80 transition" onclick="openInAppPreview('${previewUrl}', 'Berkas Tambahan - ${fileName}')">
                                <div class="w-10 h-10 rounded-xl bg-white text-emerald-600 flex items-center justify-center text-xl shadow-xs mb-1">
                                    ${isPdf ? '📄' : '📎'}
                                </div>
                                <span class="text-[10px] font-bold text-emerald-900 uppercase tracking-wider">Berkas Tambahan (${ext.toUpperCase()})</span>
                                <span class="text-[10px] text-emerald-700 font-medium">Klik untuk Pratinjau</span>
                            </div>
                            <div class="p-2.5 flex-1 flex flex-col justify-between">
                                <div class="truncate text-xs font-semibold text-emerald-950 mb-2" title="${fileName}">⭐ Berkas Tambahan: ${fileName}</div>
                                <div class="flex items-center gap-1.5 pt-1 border-t border-emerald-100">
                                    <button type="button" onclick="openInAppPreview('${previewUrl}', 'Berkas Tambahan - ${fileName}')" class="flex-1 py-1 px-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] transition text-center cursor-pointer">
                                        👁️ Buka Dokumen
                                    </button>
                                    <a href="${downloadUrl}" download class="p-1 rounded-lg text-emerald-600 hover:text-emerald-900 hover:bg-emerald-100 transition" title="Unduh Berkas Asli">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    </a>
                                </div>
                            </div>
                        `;
                    }

                    lampiranCardsContainer.appendChild(card);
                }

                if (totalAttachments > 0) {
                    lampiranCardsContainer.classList.remove('hidden');
                    lampiranEmptyState.classList.add('hidden');
                } else {
                    lampiranCardsContainer.classList.add('hidden');
                    lampiranEmptyState.classList.remove('hidden');
                }

                // Bidang default
                const selectBidang = document.getElementById('dash-modal-select-bidang');
                if (ticket.bidang_id) {
                    selectBidang.value = ticket.bidang_id;
                } else {
                    selectBidang.selectedIndex = 0;
                }

                // Reset radio ke GREEN atau existing
                const radios = document.querySelectorAll('input[name="tlp_level"]');
                radios.forEach(r => {
                    if (r.value === (ticket.tlp_level || 'GREEN')) {
                        r.checked = true;
                    }
                });

                handleDashTlpSelect(ticket.tlp_level || 'GREEN');

                document.getElementById('dash-verify-form').action = `/admin/tiket/${ticket.id}/verifikasi`;
                document.getElementById('dash-modal-notes').value = '';

                const modal = document.getElementById('verify-modal-dashboard');
                modal.classList.remove('hidden');
                modal.style.display = 'flex';
            };

            window.closeVerifyModalDashboard = function () {
                const modal = document.getElementById('verify-modal-dashboard');
                modal.classList.add('hidden');
                modal.style.display = 'none';
            };

            window.handleDashTlpSelect = function (level) {
                const warning = document.getElementById('dash-red-bypass-warning');
                const selectBidang = document.getElementById('dash-modal-select-bidang');

                if (level === 'RED') {
                    if (warning) warning.classList.remove('hidden');
                    if (selectBidang) {
                        for (let i = 0; i < selectBidang.options.length; i++) {
                            if (selectBidang.options[i].text.includes('Persandian')) {
                                selectBidang.selectedIndex = i;
                                break;
                            }
                        }
                    }
                } else {
                    if (warning) warning.classList.add('hidden');
                }
            };

            window.submitDashVerifikasi = function () {
                const selectBidang = document.getElementById('dash-modal-select-bidang');
                if (!selectBidang || !selectBidang.value) {
                    showAdminAlert({
                        title: 'Bidang Belum Dipilih',
                        message: 'Silakan pilih Bidang Penanggung Jawab terlebih dahulu sebelum meneruskan disposisi tiket.',
                        icon: '🏢',
                        iconBg: 'bg-rose-50 text-rose-700 border-rose-200'
                    });
                    if (selectBidang) selectBidang.focus();
                    return;
                }

                const notes = document.getElementById('dash-modal-notes').value;
                const bidangText = selectBidang.options[selectBidang.selectedIndex].text;

                showAdminConfirm({
                    title: 'Konfirmasi Verifikasi & Disposisi',
                    message: `Verifikasi tiket ini dan teruskan disposisi penanganan ke ${bidangText}?`,
                    icon: '✅',
                    iconBg: 'bg-emerald-50 text-emerald-700 border-emerald-200',
                    confirmText: 'Ya, Verifikasi & Disposisi',
                    confirmBtnClass: 'bg-emerald-600 hover:bg-emerald-700 text-white font-black',
                    onConfirm: function () {
                        const form = document.getElementById('dash-verify-form');
                        document.getElementById('dash-hidden-catatan').value = notes;
                        form.submit();
                    }
                });
            };

            window.submitDashMintaKelengkapan = function () {
                const notes = document.getElementById('dash-modal-notes').value.trim();
                if (notes.length < 5) {
                    showAdminAlert({
                        title: 'Catatan Diperlukan',
                        message: 'Silakan tuliskan data apa yang perlu dilengkapi oleh pelapor pada kolom catatan (minimal 5 karakter).',
                        icon: '📝',
                        iconBg: 'bg-amber-50 text-amber-800 border-amber-200'
                    });
                    document.getElementById('dash-modal-notes').focus();
                    return;
                }

                showAdminConfirm({
                    title: 'Permintaan Kelengkapan Data',
                    message: 'Kirimkan permintaan kelengkapan data kepada pelapor? Tiket tetap berstatus Pending Verification dan pelapor dapat mengunggah berkas perbaikan.',
                    icon: '⚠️',
                    iconBg: 'bg-amber-50 text-amber-700 border-amber-200',
                    confirmText: 'Ya, Kirimkan Permintaan',
                    confirmBtnClass: 'bg-amber-500 hover:bg-amber-600 text-slate-950 font-black',
                    onConfirm: function () {
                        const form = document.getElementById('dash-verify-form');
                        const actionUrl = form.action.replace('/verifikasi', '/minta-kelengkapan');
                        form.action = actionUrl;
                        document.getElementById('dash-hidden-catatan').value = notes;
                        form.submit();
                    }
                });
            };

            // ==========================================
            // 2. MODAL QUICK DETAIL TIKET DI DASHBOARD
            // ==========================================
            window.openDetailModalDashboard = function (ticketId) {
                const ticket = dashTicketsData[ticketId];
                if (!ticket) return;

                document.getElementById('detail-modal-kode').textContent = ticket.kode_tiket;
                document.getElementById('detail-modal-judul').textContent = ticket.judul;
                document.getElementById('detail-modal-sub').textContent = `Masuk: ${ticket.created_at_fmt} \u2022 Kategori Layanan`;

                document.getElementById('detail-modal-pelapor-nama').textContent = ticket.nama_pelapor;
                document.getElementById('detail-modal-pelapor-skpd').textContent = ticket.skpd;
                document.getElementById('detail-modal-pelapor-nip').textContent = `NIP: ${ticket.nip_pelapor || '-'}`;
                document.getElementById('detail-modal-pelapor-wa').innerHTML = ticket.no_whatsapp
                    ? `<a href="https://wa.me/${ticket.no_whatsapp.replace(/[^0-9]/g, '')}" target="_blank" class="hover:underline flex items-center gap-1"><span>💬</span> ${ticket.no_whatsapp}</a>`
                    : '-';

                document.getElementById('detail-modal-bidang').textContent = ticket.nama_bidang;
                document.getElementById('detail-modal-teknisi').textContent = ticket.teknisi_lapangan;
                document.getElementById('detail-modal-deskripsi').textContent = ticket.deskripsi;

                // Badge Status
                const badgeElem = document.getElementById('detail-modal-status-badge');
                badgeElem.innerHTML = `<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-900 border border-blue-200 capitalize">${ticket.status.replace(/_/g, ' ')}</span>`;

                // Badge TLP
                const tlpElem = document.getElementById('detail-modal-tlp-badge');
                if (ticket.tlp_level) {
                    let bgClass = 'bg-slate-700 text-white';
                    if (ticket.tlp_level === 'RED') bgClass = 'bg-rose-600 text-white';
                    else if (ticket.tlp_level === 'AMBER') bgClass = 'bg-amber-500 text-slate-950 font-bold';
                    else if (ticket.tlp_level === 'GREEN') bgClass = 'bg-emerald-600 text-white';
                    tlpElem.innerHTML = `<span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold ${bgClass}">TLP: ${ticket.tlp_level}</span>`;
                    tlpElem.classList.remove('hidden');
                } else {
                    tlpElem.innerHTML = '';
                }

                // Lampiran
                const lampiranWrapper = document.getElementById('detail-modal-lampiran-wrapper');
                const lampiranLinks = document.getElementById('detail-modal-lampiran-links');
                lampiranLinks.innerHTML = '';
                let hasLampiran = false;

                if (ticket.lampiran_pelapor) {
                    lampiranLinks.innerHTML += `<a href="/admin/tiket/${ticket.kode_tiket}/berkas/lampiran" target="_blank" class="font-bold underline text-blue-800 hover:text-blue-950">Berkas Lampiran Utama &rarr;</a>`;
                    hasLampiran = true;
                }
                if (ticket.lampiran_tambahan) {
                    lampiranLinks.innerHTML += `<a href="/admin/tiket/${ticket.kode_tiket}/berkas/lampiran_tambahan" target="_blank" class="font-bold underline text-emerald-800 hover:text-emerald-950">Berkas Tambahan Pelapor &rarr;</a>`;
                    hasLampiran = true;
                }
                if (hasLampiran) {
                    lampiranWrapper.classList.remove('hidden');
                } else {
                    lampiranWrapper.classList.add('hidden');
                }

                // Catatan Resolusi
                const resolusiWrapper = document.getElementById('detail-modal-resolusi-wrapper');
                const resolusiText = document.getElementById('detail-modal-resolusi-text');
                const resolusiLink = document.getElementById('detail-modal-resolusi-link');
                if (ticket.catatan_resolusi) {
                    resolusiText.textContent = ticket.catatan_resolusi;
                    if (ticket.bukti_resolusi) {
                        resolusiLink.innerHTML = `<a href="/admin/tiket/${ticket.kode_tiket}/berkas/bukti" target="_blank" class="text-xs font-bold text-emerald-800 underline hover:text-emerald-950">📎 Unduh Berkas Bukti Penyelesaian &rarr;</a>`;
                    } else {
                        resolusiLink.innerHTML = '';
                    }
                    resolusiWrapper.classList.remove('hidden');
                } else {
                    resolusiWrapper.classList.add('hidden');
                }

                // Timeline History List
                const historyList = document.getElementById('detail-modal-history-list');
                historyList.innerHTML = '';
                if (ticket.status_history && ticket.status_history.length > 0) {
                    const stageLabelsPusat = {
                        'pending_verification': 'Pending Verification (Baru)',
                        'verified': 'Verified (Disposisi ke Bidang)',
                        'in_progress': 'In Progress',
                        'resolved': 'Resolved',
                        'closed': 'Selesai',
                        'reopened': 'Reopened',
                        'eskalasi_pusat': 'Eskalasi Pusat',
                        'force_closed': 'Force Closed',
                        'cancelled': 'Cancelled'
                    };
                    ticket.status_history.forEach(h => {
                        const item = document.createElement('div');
                        item.className = 'relative pl-7';
                        item.style.position = 'relative';
                        item.style.paddingLeft = '1.75rem';
                        const statusLabel = stageLabelsPusat[h.status] || (h.status || '-').replace(/_/g, ' ');
                        item.innerHTML = `
                            <div class="absolute -left-[6px] top-1 w-3 h-3 rounded-full bg-sky-600 ring-4 ring-white shadow-xs" style="position: absolute; left: -6px; top: 4px; width: 12px; height: 12px; border-radius: 9999px;"></div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-bold text-slate-900">${statusLabel}</span>
                                <span class="text-slate-400 text-[10px]">&bull; ${h.timestamp ? new Date(h.timestamp).toLocaleString('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) : '-'} WIB</span>
                                <span class="text-[10px] text-sky-800 bg-sky-50 px-1.5 py-0.5 rounded border border-sky-200">Aktor: ${h.actor || '-'}</span>
                            </div>
                            ${h.note ? `<div class="mt-1 text-slate-700 bg-slate-50 p-2 rounded-lg border border-slate-200 text-[11px] leading-relaxed">${h.note}</div>` : ''}
                        `;
                        historyList.appendChild(item);
                    });
                } else {
                    historyList.innerHTML = '<p class="text-slate-400 italic">Belum ada riwayat tercatat.</p>';
                }

                // Full Link
                document.getElementById('detail-modal-full-link').href = `/admin/tiket/${ticket.id}`;

                document.getElementById('detail-modal-dashboard').classList.remove('hidden');
            };

            window.closeDetailModalDashboard = function () {
                document.getElementById('detail-modal-dashboard').classList.add('hidden');
            };
        </script>
    @endif
@endsection