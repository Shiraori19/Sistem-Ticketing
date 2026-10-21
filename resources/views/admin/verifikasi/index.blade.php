@extends('admin.layout', ['title' => 'Antrean Verifikasi Tiket — Admin Pusat'])

@section('content')
<div class="space-y-6">
    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-50 text-amber-800 border border-amber-200 text-xs font-semibold mb-1">
                <span>⏳</span> Antrean Verifikasi Admin Pusat
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Verifikasi & Disposisi Tiket Masuk</h1>
            <p class="text-xs text-slate-500 mt-1">
                Validasi laporan pengaduan dari ASN OPD, tetapkan klasifikasi keamanan TLP, dan tentukan bidang penanggung jawab.
            </p>
        </div>

        <div class="flex items-center gap-3 flex-wrap">
            <form method="POST" action="{{ route('admin.verifikasi.claimNext') }}">
                @csrf
                <button type="submit" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 font-black text-xs shadow-xs transition cursor-pointer">
                    <span>⚡ Ambil Tiket Berikutnya</span>
                </button>
            </form>

            <div class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white border border-slate-200 shadow-xs text-xs font-bold text-slate-700">
                <span>Menunggu Tindakan:</span>
                <span class="px-2 py-0.5 rounded-full bg-amber-500 text-slate-950 font-black">{{ $tickets->total() }}</span>
            </div>
        </div>
    </div>

    <!-- Statistik Beban Kerja Admin Pusat Hari Ini (BUTIR 2) -->
    @if(isset($adminPusatWorkloads) && $adminPusatWorkloads->isNotEmpty())
        <div class="flex items-center gap-2 text-xs flex-wrap p-2.5 bg-slate-100/80 rounded-xl border border-slate-200">
            <span class="font-bold text-slate-600 flex items-center gap-1 text-[11px] uppercase tracking-wider">
                <span>👥</span> Beban Verifikasi Hari Ini:
            </span>
            @foreach($adminPusatWorkloads as $ap)
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-slate-800 text-[11px] font-medium shadow-2xs">
                    <span class="w-2 h-2 rounded-full {{ $ap->id === auth()->id() ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                    <span>{{ $ap->nama }}</span>
                    <strong class="font-black text-slate-900 bg-slate-100 px-1.5 py-0.2 rounded">{{ $ap->verified_today_count }}</strong>
                </span>
            @endforeach
        </div>
    @endif

    @if($tickets->isEmpty())
        <div class="bg-white rounded-3xl border border-slate-200 p-12 text-center shadow-xs">
            <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-4 border border-emerald-100 shadow-xs">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <h3 class="text-lg font-bold text-slate-900">Semua Tiket Telah Diverifikasi</h3>
            <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
                Tidak ada tiket berstatus pending verifikasi saat ini. Seluruh tiket yang masuk telah didisposisikan atau ditangani.
            </p>
        </div>
    @else
        <!-- BANNER DETEKSI INSIDEN MASSAL (COMPACT & SLEEK) -->
        @if(isset($clusters) && $clusters->isNotEmpty())
            <div class="p-3.5 bg-gradient-to-r from-amber-500/10 via-amber-500/5 to-slate-50 rounded-2xl border border-amber-300/80 shadow-xs flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-amber-400 text-slate-950 font-black flex items-center justify-center text-sm shadow-xs shrink-0">
                        ⚡
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-black text-amber-950 uppercase tracking-wider">Deteksi Insiden Bersama</span>
                            <span class="px-2 py-0.5 rounded-full bg-amber-200/90 text-amber-950 text-[10px] font-bold">Smart Cluster</span>
                        </div>
                        <p class="text-[11px] text-slate-600 mt-0.5">
                            Kelompok pengaduan serupa terdeteksi masuk berdekatan. Gabungkan massal ke 1 Induk Tiket:
                        </p>
                    </div>
                </div>

                <div class="flex items-center flex-wrap gap-2 shrink-0">
                    @foreach($clusters as $cl)
                        <button type="button" onclick="selectClusterTickets({{ $cl->category_id }})"
                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white hover:bg-amber-100/80 text-slate-900 border border-amber-300 hover:border-amber-400 text-xs font-bold transition shadow-xs cursor-pointer group">
                            <span class="px-1.5 py-0.5 rounded-md bg-amber-400 text-slate-950 font-black text-[10px]">{{ $cl->total }} Tiket</span>
                            <span class="max-w-[200px] truncate text-slate-800 text-[11px]">{{ $cl->category?->nama_kategori }}</span>
                            <span class="text-amber-700 font-black group-hover:translate-x-0.5 transition-transform text-xs">&rarr;</span>
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- TABEL ANTREAN VERIFIKASI -->
        <div class="bg-white rounded-2xl shadow-xs border border-slate-200/90 overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-200 bg-slate-50/70 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div class="flex items-center gap-2.5">
                    <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">Antrean Tiket Belum Diverifikasi</span>
                    <span class="px-2 py-0.5 rounded-full bg-slate-200 text-slate-700 text-[11px] font-bold">{{ $tickets->total() }}</span>
                </div>
                <div class="flex items-center gap-1.5 text-[11px] text-slate-500">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-amber-50 text-amber-800 border border-amber-200/80 font-medium">
                        <span>💡</span> Centang kotak untuk menggabungkan banyak tiket ke <strong>Induk Tiket</strong>
                    </span>
                </div>
            </div>

            <div class="overflow-x-auto scrollbar-thin">
                <table class="w-full min-w-[900px] table-fixed text-left text-xs border-collapse">
                    <thead class="bg-slate-50/90 text-slate-600 font-bold border-b border-slate-200 text-[11px] uppercase tracking-wider">
                        <tr>
                            <th class="w-10 px-2 py-3 text-center">
                                <input type="checkbox" id="check-all" onchange="toggleCheckAll(this)" title="Pilih Semua Tiket" 
                                    class="w-4 h-4 rounded border-2 border-slate-300 text-sky-600 focus:ring-sky-500 cursor-pointer align-middle bg-white inline-block hover:border-sky-500 transition">
                            </th>
                            <th class="w-28 px-2.5 py-3">Kode Tiket</th>
                            <th class="w-28 px-2 py-3">Waktu Masuk</th>
                            <th class="w-40 px-2.5 py-3">Pelapor & OPD</th>
                            <th class="px-3 py-3">Pokok Masalah</th>
                            <th class="w-36 px-2.5 py-3">Kategori Layanan</th>
                            <th class="w-32 px-2 py-3 text-center">Status</th>
                            <th class="w-24 px-2.5 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach($tickets as $t)
                            <tr class="hover:bg-amber-50/30 transition cursor-pointer" onclick="openVerifyModal({{ $t->id }})">
                                <!-- Checkbox Seleksi Massal -->
                                <td class="w-10 px-2 py-2.5 text-center" onclick="event.stopPropagation()">
                                    <input type="checkbox" class="ticket-select-cb w-4 h-4 rounded border-2 border-slate-300 text-sky-600 focus:ring-sky-500 cursor-pointer align-middle bg-white inline-block hover:border-sky-500 transition" 
                                        value="{{ $t->id }}" 
                                        data-category-id="{{ $t->category_id }}"
                                        data-kode="{{ $t->kode_tiket }}"
                                        data-skpd="{{ $t->skpd }}"
                                        data-judul="{{ addslashes($t->judul) }}"
                                        onchange="handleRowCheckboxChange()">
                                </td>

                                <!-- Kode Tiket & Badge Induk/Anak -->
                                <td class="w-28 px-2.5 py-2.5 whitespace-nowrap overflow-hidden">
                                    <div class="flex items-center gap-1 font-mono font-bold text-slate-900 text-xs">
                                        <span>{{ $t->kode_tiket }}</span>
                                    </div>
                                    @if($t->is_parent)
                                        <div class="mt-0.5">
                                            <span class="inline-flex items-center gap-0.5 px-1.5 py-0.2 rounded bg-amber-400 text-slate-950 text-[9px] font-black tracking-wider uppercase shadow-xs border border-amber-500" title="Induk Tiket (Master Incident)">
                                                👑 INDUK
                                            </span>
                                        </div>
                                    @elseif($t->parent_id)
                                        <div class="mt-0.5">
                                            <span class="inline-flex items-center gap-0.5 px-1.5 py-0.2 rounded bg-indigo-100 text-indigo-800 text-[9px] font-bold border border-indigo-200" title="Tiket Anak dari {{ $t->parent?->kode_tiket }}">
                                                🔗 Anak
                                            </span>
                                        </div>
                                        @if($t->parent)
                                            <div class="text-[9px] font-mono text-indigo-600 font-medium truncate block" title="Induk: {{ $t->parent->kode_tiket }}">
                                                ↳ {{ $t->parent->kode_tiket }}
                                            </div>
                                        @endif
                                    @endif
                                </td>

                                <!-- Waktu Masuk & SLA Respon -->
                                <td class="w-28 px-2 py-2.5 text-slate-500 whitespace-nowrap overflow-hidden">
                                    <div class="font-semibold text-slate-700 text-[11px]">{{ $t->created_at->translatedFormat('d M, H:i') }}</div>
                                    @php
                                        $targetMins = max(1, (int) round(($t->sla_respon_jam ?: 72) * 60));
                                        $deadlineRespon = $t->sla_respon_deadline_at ? \Carbon\Carbon::parse($t->sla_respon_deadline_at) : $t->created_at->copy()->addMinutes($targetMins);
                                        $isOverdue = now()->gt($deadlineRespon);
                                        $diffMins = abs(now()->diffInMinutes($deadlineRespon));
                                    @endphp
                                    <div class="mt-0.5">
                                        @if($isOverdue)
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded bg-rose-50 text-rose-700 border border-rose-200 text-[9px] font-bold font-mono">
                                                <span>⚠️ Telat {{ $diffMins >= 60 ? floor($diffMins/60).'j '.($diffMins%60).'m' : $diffMins.'m' }}</span>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded bg-emerald-50 text-emerald-800 border border-emerald-200 text-[9px] font-semibold font-mono">
                                                <span>⏱️ Sisa {{ $diffMins >= 60 ? floor($diffMins/60).'j '.($diffMins%60).'m' : $diffMins.'m' }}</span>
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Pelapor & OPD -->
                                <td class="w-40 px-2.5 py-2.5 overflow-hidden">
                                    <div class="font-bold text-slate-900 leading-snug text-xs truncate block" title="{{ $t->nama_pelapor }}">{{ $t->nama_pelapor }}</div>
                                    <div class="text-slate-500 text-[11px] truncate block mt-0.5" title="{{ $t->skpd }}">{{ $t->skpd }}</div>
                                    <div class="text-[10px] font-mono text-slate-400 mt-0.5 truncate block">NIP: {{ $t->nip_pelapor }}</div>
                                </td>

                                <!-- Pokok Masalah -->
                                <td class="px-3 py-2.5 overflow-hidden">
                                    <div class="font-bold text-slate-900 text-xs truncate block" title="{{ $t->judul }}">{{ $t->judul }}</div>
                                    <div class="text-slate-500 text-[11px] truncate block mt-0.5" title="{{ $t->deskripsi }}">{{ $t->deskripsi }}</div>
                                    @if(str_contains($t->catatan_kelengkapan ?? '', 'Dikembalikan oleh Bidang'))
                                        <div class="mt-1">
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded bg-orange-100 text-orange-950 border border-orange-300 text-[9px] font-extrabold animate-pulse">
                                                <span>↩️ DISPOSISI DIKEMBALIKAN</span>
                                            </span>
                                        </div>
                                    @elseif($t->catatan_kelengkapan)
                                        <div class="mt-1">
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded bg-amber-100 text-amber-900 border border-amber-300 text-[9px] font-bold">
                                                <span>⚠️ Menunggu Data</span>
                                            </span>
                                        </div>
                                    @elseif(collect($t->status_history ?? [])->contains(fn($h) => str_contains($h['note'] ?? '', 'Pelapor telah melengkapi data')))
                                        <div class="mt-1">
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded bg-emerald-100 text-emerald-900 border border-emerald-300 text-[9px] font-bold">
                                                <span>✨ Data Dilengkapi</span>
                                            </span>
                                        </div>
                                    @endif
                                    @if($t->lampiran_pelapor)
                                        @php
                                            $lampList = is_array($t->lampiran_pelapor) ? $t->lampiran_pelapor : (is_string($t->lampiran_pelapor) && str_starts_with($t->lampiran_pelapor, '[') ? json_decode($t->lampiran_pelapor, true) : [$t->lampiran_pelapor]);
                                            $firstL = !empty($lampList) ? $lampList[0] : null;
                                            $lCount = count($lampList ?? []);
                                        @endphp
                                        @if($firstL)
                                            <div class="mt-1">
                                                <button type="button" 
                                                    onclick="event.stopPropagation(); openInAppPreview('/admin/tiket/{{ $t->kode_tiket }}/berkas/lampiran?file={{ urlencode($firstL) }}', 'Bukti Lampiran ({{ $t->kode_tiket }})')"
                                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 text-[10px] font-semibold transition cursor-pointer shadow-2xs">
                                                    <span>👁️ Preview Bukti ({{ $lCount }})</span>
                                                </button>
                                            </div>
                                        @endif
                                    @endif
                                </td>

                                <!-- Kategori Layanan -->
                                <td class="w-36 px-2.5 py-2.5 overflow-hidden">
                                    <span class="font-medium text-slate-800 text-xs truncate block" title="{{ $t->category?->nama_kategori }}">{{ $t->category?->nama_kategori ?? '-' }}</span>
                                    <span class="text-[10px] text-slate-400 mt-0.5 block">SLA: {{ round(((float)($t->sla_resolusi_jam ?? 336))/24) }} Hari ({{ (int)($t->sla_resolusi_jam ?? 336) }} Jam)</span>
                                </td>

                                <!-- Status Badge Universal & Soft-Claim Status -->
                                <td class="w-32 px-2 py-2.5 text-center whitespace-nowrap overflow-hidden">
                                    @if($t->isClaimActive())
                                        @if($t->claimed_by === auth()->id())
                                            <div class="space-y-0.5">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-blue-100 text-blue-900 border border-blue-300 text-[10px] font-bold shadow-xs whitespace-nowrap">
                                                    <span>🔒</span> Sedang Anda Tangani
                                                </span>
                                                <form method="POST" action="{{ route('admin.verifikasi.releaseClaim', $t->id) }}" onclick="event.stopPropagation()" class="block">
                                                    @csrf
                                                    <button type="submit" class="text-[9px] text-slate-400 hover:text-rose-600 underline cursor-pointer">Lepas Kunci</button>
                                                </form>
                                            </div>
                                        @else
                                            <div class="space-y-0.5">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 border border-slate-300 text-[10px] font-bold shadow-xs whitespace-nowrap" title="Kunci aktif sampai {{ $t->claimed_at->addMinutes(10)->format('H:i') }}">
                                                    <span>🔒</span> {{ \Illuminate\Support\Str::limit($t->claimedByUser?->nama ?? 'Admin Lain', 13) }}
                                                </span>
                                                <span class="text-[9px] text-amber-600 block font-mono font-medium">Sedang Ditinjau</span>
                                            </div>
                                        @endif
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-900 border border-amber-300 text-[10px] font-bold shadow-xs whitespace-nowrap">
                                            <span>⏳</span> Belum Verifikasi
                                        </span>
                                    @endif
                                </td>

                                <!-- Aksi -->
                                <td class="w-24 px-2.5 py-2.5 text-right whitespace-nowrap overflow-hidden" onclick="event.stopPropagation()">
                                    @if($t->isClaimActive() && $t->claimed_by !== auth()->id())
                                        <button type="button"
                                            onclick="openVerifyModal({{ $t->id }})"
                                            class="w-full py-1.5 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold rounded-lg text-xs shadow-xs transition inline-flex items-center justify-center gap-1 cursor-pointer">
                                            <span>👁️ Tinjau</span>
                                        </button>
                                    @else
                                        <button type="button"
                                            onclick="openVerifyModal({{ $t->id }})"
                                            class="w-full py-1.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold rounded-lg text-xs shadow-xs transition inline-flex items-center justify-center gap-1 cursor-pointer">
                                            <span>⚡ Verifikasi</span>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-200">
                {{ $tickets->links() }}
            </div>
        </div>
    @endif
</div>

<!-- ================================================================= -->
<!-- MODAL VERIFIKASI & DISPOSISI TIKET -->
<!-- ================================================================= -->
<div id="verify-modal"
    class="admin-verify-modal-wrapper hidden"
    style="position: fixed; inset: 0; z-index: 9999; background-color: rgba(15, 23, 42, 0.82); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; padding: 1rem; box-sizing: border-box;">
    <div
        class="admin-verify-modal-card"
        style="background: #ffffff; width: 95%; max-width: 1140px; max-height: calc(100vh - 2.5rem); border-radius: 1.5rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.45); border: 1px solid #cbd5e1; display: flex; flex-direction: column; overflow: hidden; margin: auto;">
        <!-- Header Modal -->
        <div class="bg-gradient-to-r from-[#0f274a] via-[#163a6f] to-[#0f274a] text-white px-5 py-4 flex items-start justify-between border-b border-blue-900/60" style="flex-shrink: 0;">
            <div class="space-y-1 max-w-[85%]">
                <div class="flex items-center gap-2 flex-wrap">
                    <span id="modal-kode" class="font-mono text-sm font-black bg-sky-500/25 text-sky-200 px-2.5 py-0.5 rounded border border-sky-400/30">
                        TIK-XXXXXX-XXXX
                    </span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-400/20 text-amber-200 border border-amber-300/30 uppercase tracking-wide">
                        Antrean Verifikasi Pusat
                    </span>
                    <span id="modal-sla-badge" class="hidden text-[10px] font-mono px-2 py-0.5 rounded-full font-bold"></span>
                </div>
                <h3 id="modal-judul" class="text-base sm:text-lg font-black text-white leading-snug line-clamp-1">Judul Permasalahan</h3>
                <p id="modal-pelapor-sub" class="text-xs text-sky-200/90 flex items-center gap-1.5 flex-wrap">
                    Diajukan pada: -
                </p>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <a id="modal-full-link" href="#" target="_blank"
                    class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-semibold border border-white/15 transition shadow-2xs"
                    title="Buka halaman penuh tiket di tab baru">
                    <span>Lihat Detail Penuh</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                </a>
                <button type="button" onclick="closeVerifyModal()" class="text-slate-300 hover:text-white p-1.5 rounded-xl hover:bg-white/10 transition cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
        </div>

        <!-- Body Modal Scrollable (2-Column Grid di Desktop) -->
        <div class="p-4 sm:p-5 overflow-y-auto scrollbar-thin text-xs" style="flex: 1 1 auto; max-height: calc(100vh - 8rem);">
            <!-- Alert Catatan Kelengkapan Aktif Jika Ada -->
            <div id="modal-kelengkapan-alert" class="hidden mb-4 p-3.5 bg-amber-50 border border-amber-300 rounded-2xl text-xs">
                <div class="flex items-center gap-1.5 font-bold text-amber-900" id="modal-kelengkapan-title">
                    <span>⚠️</span>
                    <span>Permintaan Kelengkapan Data Aktif:</span>
                </div>
                <p id="modal-kelengkapan-notes" class="text-amber-800 mt-1 leading-relaxed"></p>
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
                            <span class="text-[10px] text-slate-500 font-medium" id="modal-waktu-text">-</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <!-- Identitas Pelapor -->
                            <div class="space-y-0.5">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Pelapor &amp; Instansi</span>
                                <div class="font-black text-slate-900 text-xs sm:text-sm line-clamp-1" id="modal-pelapor-nama">-</div>
                                <div class="text-[11px] text-slate-600 font-medium line-clamp-1" id="modal-pelapor-skpd">-</div>
                                <div class="font-mono text-[10px] text-slate-400" id="modal-pelapor-nip">NIP: -</div>
                            </div>

                            <!-- Kontak WhatsApp -->
                            <div class="space-y-0.5">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Kontak WhatsApp</span>
                                <div id="modal-pelapor-wa" class="pt-0.5">-</div>
                                <p class="text-[9px] text-slate-400 mt-0.5">Klik untuk konfirmasi via WA</p>
                            </div>

                            <!-- Kategori Layanan -->
                            <div class="space-y-0.5">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Kategori Layanan</span>
                                <div class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-blue-50 text-blue-700 border border-blue-200 font-bold text-xs" id="modal-kategori-text">
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
                        <div id="modal-deskripsi"
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
                        <div id="modal-lampiran-grid" class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <!-- Injected by JavaScript -->
                        </div>

                        <!-- State Jika Tidak Ada Lampiran -->
                        <div id="modal-lampiran-empty" class="hidden p-3.5 rounded-2xl bg-slate-50 border border-dashed border-slate-300 text-center text-slate-400">
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

                    <form id="verify-form" method="POST" action="" class="space-y-3.5">
                        @csrf
                        <input type="hidden" name="row_version" id="modal-row-version" value="1">
                        <input type="hidden" name="catatan" id="hidden-catatan" value="">

                        <!-- Claim Locked Banner jika sedang dikunci Admin lain -->
                        <div id="modal-claim-locked-banner" class="hidden p-3 bg-amber-50 border border-amber-300 rounded-xl text-xs text-amber-900 flex items-start gap-2">
                            <span class="text-base leading-none">🔒</span>
                            <span id="modal-claim-locked-text">Tiket ini sedang diverifikasi oleh Admin Pusat lain. Mode baca-saja.</span>
                        </div>

                        <!-- 1. RADIO TLP LEVEL -->
                        <div>
                            <label class="block text-xs font-bold text-slate-800 mb-1.5">
                                1. Klasifikasi TLP (Keamanan Data) *
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                <label
                                    class="relative flex items-start p-2 rounded-xl border border-slate-200 hover:border-rose-300 bg-white cursor-pointer transition select-none has-[:checked]:border-rose-500 has-[:checked]:bg-rose-50/50 has-[:checked]:ring-1 has-[:checked]:ring-rose-500">
                                    <input type="radio" name="tlp_level" value="RED" onchange="handleTlpSelect('RED')"
                                        class="mt-0.5 text-rose-600 focus:ring-rose-500">
                                    <div class="ml-2 text-xs">
                                        <span class="font-bold text-rose-700 block text-xs">🔴 RED</span>
                                        <span class="text-[10px] text-slate-500 leading-tight block">Sangat Rahasia</span>
                                    </div>
                                </label>

                                <label
                                    class="relative flex items-start p-2 rounded-xl border border-slate-200 hover:border-amber-300 bg-white cursor-pointer transition select-none has-[:checked]:border-amber-500 has-[:checked]:bg-amber-50/50 has-[:checked]:ring-1 has-[:checked]:ring-amber-500">
                                    <input type="radio" name="tlp_level" value="AMBER" onchange="handleTlpSelect('AMBER')"
                                        class="mt-0.5 text-amber-600 focus:ring-amber-500">
                                    <div class="ml-2 text-xs">
                                        <span class="font-bold text-amber-700 block text-xs">🟡 AMBER</span>
                                        <span class="text-[10px] text-slate-500 leading-tight block">Terbatas OPD</span>
                                    </div>
                                </label>

                                <label
                                    class="relative flex items-start p-2 rounded-xl border border-slate-200 hover:border-emerald-300 bg-white cursor-pointer transition select-none has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/50 has-[:checked]:ring-1 has-[:checked]:ring-emerald-500">
                                    <input type="radio" name="tlp_level" value="GREEN" checked
                                        onchange="handleTlpSelect('GREEN')"
                                        class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                                    <div class="ml-2 text-xs">
                                        <span class="font-bold text-emerald-700 block text-xs">🟢 GREEN</span>
                                        <span class="text-[10px] text-slate-500 leading-tight block">Komunitas ASN</span>
                                    </div>
                                </label>

                                <label
                                    class="relative flex items-start p-2 rounded-xl border border-slate-200 hover:border-slate-400 bg-white cursor-pointer transition select-none has-[:checked]:border-slate-500 has-[:checked]:bg-slate-100 has-[:checked]:ring-1 has-[:checked]:ring-slate-500">
                                    <input type="radio" name="tlp_level" value="CLEAR" onchange="handleTlpSelect('CLEAR')"
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
                            <label for="modal-select-bidang" class="block text-xs font-bold text-slate-800 mb-1">
                                2. Teruskan Disposisi ke Bidang *
                            </label>
                            <select name="bidang_id" id="modal-select-bidang" required
                                class="w-full text-xs rounded-xl border border-slate-300 p-2 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500 transition">
                                <option value="">-- Pilih Bidang Penanggung Jawab --</option>
                                @foreach($bidangList as $b)
                                    <option value="{{ $b->id }}">{{ $b->nama_lengkap ?? $b->nama_bidang }}</option>
                                @endforeach
                            </select>

                            <div id="red-bypass-warning"
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

                            <!-- BUTIR 7: Opsi Bidang yang Ditembuskan untuk TLP RED -->
                            <div id="red-tembusan-container" class="hidden mt-2.5 p-2.5 bg-slate-100/90 border border-slate-200 rounded-xl text-xs">
                                <span class="block text-[11px] font-bold text-slate-700 mb-1.5">
                                    👥 Tembusan Disposisi ke Bidang Lain (Opsional):
                                </span>
                                <div class="grid grid-cols-1 gap-1 max-h-28 overflow-y-auto pr-1">
                                    @foreach($bidangList as $b)
                                        @if(!$b->is_persandian)
                                            <label class="flex items-center gap-2 p-1 rounded hover:bg-white text-slate-700 cursor-pointer text-[11px]">
                                                <input type="checkbox" name="bidang_ditembuskan[]" value="{{ $b->id }}" class="rounded text-sky-600 focus:ring-sky-500">
                                                <span>{{ $b->nama_lengkap ?? $b->nama_bidang }}</span>
                                            </label>
                                        @endif
                                    @endforeach
                                </div>
                                <p class="text-[10px] text-slate-500 mt-1">Bidang ditembuskan dapat melihat rekam jejak tiket tanpa membuka file rahasia.</p>
                            </div>
                        </div>

                        <!-- 3. SAFE PARENT-CHILD LINKING -->
                        <div class="p-3 rounded-xl bg-white border border-slate-200 space-y-2.5">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-bold text-slate-800">
                                    3. Hubungan Antar Tiket
                                </label>
                                <span class="text-[9px] font-semibold text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200">Insiden Massal</span>
                            </div>

                            <label class="relative flex items-start p-2 rounded-lg border border-slate-200 hover:border-amber-400 bg-slate-50/50 cursor-pointer transition select-none has-[:checked]:border-amber-500 has-[:checked]:bg-amber-50/60">
                                <input type="checkbox" name="is_parent" value="1" id="modal-is-parent" onchange="handleParentToggle()" class="mt-0.5 rounded text-amber-600 focus:ring-amber-500">
                                <div class="ml-2 text-xs">
                                    <span class="font-bold text-slate-900 block text-[11px]">👑 Jadikan sebagai INDUK TIKET</span>
                                    <span class="text-[10px] text-slate-500 leading-tight block">Sumber insiden bersama / gangguan terpusat</span>
                                </div>
                            </label>

                            <div id="parent-select-container">
                                <label for="modal-select-parent" class="block text-[10px] font-bold text-slate-600 mb-1">
                                    Atau Tautkan ke Induk Aktif:
                                </label>
                                <select name="parent_id" id="modal-select-parent" onchange="handleParentSelect()"
                                    class="w-full text-xs rounded-xl border border-slate-300 p-2 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500 transition">
                                    <option value="">-- Tiket Mandiri (Bukan Tiket Anak) --</option>
                                    @if(isset($activeParents))
                                        @foreach($activeParents as $p)
                                            <option value="{{ $p->id }}">[👑 {{ $p->kode_tiket }}] {{ Str::limit($p->judul, 35) }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>

                        <!-- 4. TEXTAREA CATATAN -->
                        <div>
                            <label for="modal-notes" class="block text-xs font-bold text-slate-800 mb-1">
                                4. Catatan Disposisi / Arahan Teknis:
                            </label>
                            <textarea name="notes" id="modal-notes" rows="2"
                                placeholder="Tuliskan petunjuk penanganan atau arahan teknis untuk bidang..."
                                class="w-full text-xs rounded-xl border border-slate-300 p-2.5 focus:outline-none focus:ring-2 focus:ring-sky-500 transition bg-white"></textarea>
                        </div>

                        <!-- 5. TOMBOL AKSI GANDA -->
                        <div class="pt-2 border-t border-slate-200 flex flex-col gap-2">
                            <button type="button" id="btn-submit-verify" onclick="submitVerifikasi()"
                                class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs transition shadow-sm flex items-center justify-center gap-1.5 cursor-pointer">
                                <span>✅ Verifikasi & Disposisi</span>
                            </button>

                            <button type="button" onclick="submitMintaKelengkapan()"
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
<!-- FLOATING ACTION BAR UNTUK PENAUTAN MASSAL (BATCH LINKING) -->
<!-- ================================================================= -->
<div id="batch-action-bar" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40 hidden bg-slate-900/95 backdrop-blur-md text-white px-5 py-3 rounded-2xl shadow-2xl border border-slate-700/80 flex items-center gap-4 transition-all duration-200 animate-in fade-in slide-in-from-bottom-4">
    <div class="flex items-center gap-2">
        <span class="w-7 h-7 rounded-xl bg-amber-400 text-slate-950 font-black text-xs flex items-center justify-center shadow-xs" id="batch-count-badge">0</span>
        <div class="text-xs font-bold leading-tight">
            <span class="text-white block"><span id="batch-count-text">0</span> Tiket Terpilih</span>
            <span class="text-[10px] text-slate-400 font-normal">Siap digabungkan massal</span>
        </div>
    </div>

    <div class="h-6 w-px bg-slate-700"></div>

    <button type="button" onclick="openBatchModal()"
        class="px-4 py-2 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 font-black text-xs rounded-xl shadow-md transition flex items-center gap-2 cursor-pointer">
        <span>⚡ Gabungkan ke Induk Tiket</span>
    </button>

    <button type="button" onclick="clearSelectedCheckboxes()"
        class="text-xs text-slate-400 hover:text-white transition px-2 py-1 rounded-lg hover:bg-white/10 cursor-pointer">
        Batal
    </button>
</div>

<!-- ================================================================= -->
<!-- MODAL PENAUTAN MASSAL (BATCH PARENT-CHILD LINKING) -->
<!-- ================================================================= -->
<div id="batch-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-2xl w-full shadow-2xl border border-slate-200 overflow-hidden transform transition-all my-8 animate-in fade-in zoom-in duration-150">
        <!-- Header Modal -->
        <div class="bg-gradient-to-r from-slate-900 via-blue-950 to-slate-900 text-white p-5 sm:p-6 flex items-start justify-between border-b border-blue-900">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="font-black text-[11px] bg-amber-400 text-slate-950 px-2.5 py-0.5 rounded-full uppercase tracking-wider">
                        ⚡ Tindakan Massal (Batch Linking)
                    </span>
                    <span class="text-xs text-sky-200" id="batch-modal-count-text">0 Tiket Terpilih</span>
                </div>
                <h3 class="text-base font-bold text-white">Gabungkan Tiket ke Induk Insiden Massal</h3>
                <p class="text-xs text-slate-300 mt-0.5">
                    Verifikasi dan disposisikan seluruh pengaduan ini sekaligus ke bidang teknis tanpa verifikasi manual satu per satu.
                </p>
            </div>
            <button type="button" onclick="closeBatchModal()" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-white/10 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <form method="POST" action="{{ route('admin.verifikasi.batchLink') }}" id="batch-link-form" class="p-5 sm:p-6 space-y-4 max-h-[75vh] overflow-y-auto">
            @csrf
            <!-- Container input hidden ticket_ids -->
            <div id="batch-hidden-inputs"></div>

            <!-- Ringkasan Tiket Terpilih -->
            <div>
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block mb-1.5">
                    Daftar Tiket yang Akan Digabungkan:
                </span>
                <div id="batch-tickets-preview" class="max-h-36 overflow-y-auto space-y-1.5 p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs">
                    <!-- Dynamic rendered by JS -->
                </div>
            </div>

            <!-- Pilihan Mekanisme Induk Tiket -->
            <div class="space-y-2.5 pt-2 border-t border-slate-200">
                <label class="block text-xs font-bold text-slate-800">
                    1. Tentukan Struktur Induk Tiket:
                </label>

                <!-- Opsi A: Buat Induk Baru dari Tiket Pertama -->
                <label class="flex items-start p-3 rounded-xl border border-slate-200 hover:border-amber-400 bg-white cursor-pointer transition select-none has-[:checked]:border-amber-500 has-[:checked]:bg-amber-50/50 has-[:checked]:ring-1 has-[:checked]:ring-amber-500">
                    <input type="radio" name="parent_action" value="new" checked onchange="toggleBatchParentAction('new')" class="mt-0.5 text-amber-600 focus:ring-amber-500">
                    <div class="ml-2.5 text-xs">
                        <span class="font-bold text-slate-900 flex items-center gap-1.5">
                            <span>👑</span> Jadikan Tiket Pertama sebagai INDUK TIKET BARU, dan sisanya sebagai Tiket Anak
                        </span>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-snug">
                            Sistem otomatis mempromosikan tiket pengaduan pertama menjadi Master Incident, lalu menautkan seluruh tiket lain ke bawahnya.
                        </p>
                    </div>
                </label>

                <!-- Opsi B: Tautkan ke Induk yang Sudah Ada -->
                <label class="flex items-start p-3 rounded-xl border border-slate-200 hover:border-indigo-400 bg-white cursor-pointer transition select-none has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50/50 has-[:checked]:ring-1 has-[:checked]:ring-indigo-500">
                    <input type="radio" name="parent_action" value="existing" onchange="toggleBatchParentAction('existing')" class="mt-0.5 text-indigo-600 focus:ring-indigo-500">
                    <div class="ml-2.5 text-xs flex-1">
                        <span class="font-bold text-slate-900 flex items-center gap-1.5">
                            <span>🔗</span> Tautkan Seluruh Tiket Terpilih ke INDUK TIKET yang Sedang Aktif
                        </span>
                        <div id="batch-existing-parent-container" class="hidden mt-2">
                            <select name="parent_id" id="batch-parent-id"
                                class="w-full text-xs rounded-xl border border-slate-300 p-2.5 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                                <option value="">-- Pilih Induk Tiket Aktif --</option>
                                @if(isset($activeParents))
                                    @foreach($activeParents as $p)
                                        <option value="{{ $p->id }}">[👑 {{ $p->kode_tiket }}] {{ Str::limit($p->judul, 45) }} ({{ $p->skpd }})</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                </label>
            </div>

            <!-- TLP Level & Bidang -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 border-t border-slate-200">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">
                        2. Klasifikasi TLP:
                    </label>
                    <select name="tlp_level" class="w-full text-xs rounded-xl border border-slate-300 p-2.5 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                        <option value="GREEN" selected>🟢 GREEN (Komunitas ASN)</option>
                        <option value="AMBER">🟡 AMBER (Terbatas OPD)</option>
                        <option value="CLEAR">⚪ CLEAR (Terbuka Umum)</option>
                        <option value="RED">🔴 RED (Siber Kritis - Bypass CSIRT)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">
                        3. Disposisi ke Bidang:
                    </label>
                    <select name="bidang_id" required class="w-full text-xs rounded-xl border border-slate-300 p-2.5 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                        <option value="">-- Pilih Bidang Penanggung Jawab --</option>
                        @foreach($bidangList as $b)
                            <option value="{{ $b->id }}">{{ $b->nama_lengkap }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Catatan Arahan -->
            <div>
                <label for="batch-notes" class="block text-xs font-bold text-slate-800 mb-1">
                    4. Catatan Arahan Penanganan Massal (Opsional):
                </label>
                <textarea name="notes" id="batch-notes" rows="2" placeholder="Contoh: Gangguan massal server/jaringan terpusat. Ditangani terpadu..."
                    class="w-full text-xs rounded-xl border border-slate-300 p-2.5 focus:outline-none focus:ring-2 focus:ring-sky-500"></textarea>
            </div>

            <!-- Tombol Aksi -->
            <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2.5">
                <button type="button" onclick="closeBatchModal()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 font-black rounded-xl text-xs shadow-md transition flex items-center gap-2 cursor-pointer">
                    <span>⚡ Eksekusi Penautan Massal</span>
                </button>
            </div>
        </form>
    </div>
</div>

@php
    $ticketsJsonMap = $tickets->getCollection()->keyBy('id')->map(function($t) {
        $targetMins = max(1, (int) round(($t->sla_respon_jam ?: 72) * 60));
        $deadlineRespon = $t->sla_respon_deadline_at ? \Carbon\Carbon::parse($t->sla_respon_deadline_at) : $t->created_at->copy()->addMinutes($targetMins);
        $isOverdue = now()->gt($deadlineRespon);
        $diffMins = abs(now()->diffInMinutes($deadlineRespon));
        $diffText = $diffMins >= 60 ? floor($diffMins / 60) . 'j ' . ($diffMins % 60) . 'm' : $diffMins . 'm';
        $targetText = $targetMins >= 60 ? floor($targetMins / 60) . ' jam' : $targetMins . 'm';
        $slaText = $isOverdue ? "⚠️ Telat {$diffText} (Target: {$targetText})" : "⏱️ Sisa {$diffText} (Target: {$targetText})";

        return [
            'id' => $t->id,
            'kode_tiket' => $t->kode_tiket,
            'is_parent' => (bool) $t->is_parent,
            'parent_id' => $t->parent_id,
            'judul' => $t->judul,
            'nama_pelapor' => $t->nama_pelapor,
            'nip_pelapor' => $t->nip_pelapor,
            'skpd' => $t->skpd,
            'no_whatsapp' => $t->no_whatsapp,
            'kategori_nama' => $t->category?->nama_kategori ?? 'Umum',
            'deskripsi' => $t->deskripsi,
            'lampiran_pelapor' => $t->lampiran_pelapor,
            'lampiran_tambahan' => $t->lampiran_tambahan,
            'catatan_kelengkapan' => $t->catatan_kelengkapan,
            'bidang_id' => $t->bidang_id,
            'tlp_level' => $t->tlp_level,
            'row_version' => $t->row_version ?? 1,
            'claimed_by' => $t->claimed_by,
            'claimed_user_name' => $t->claimedByUser?->nama,
            'is_claimed_active' => $t->isClaimActive(),
            'can_be_claimed' => $t->canBeClaimedBy(auth()->id()),
            'is_overdue' => $isOverdue,
            'sla_respon_text' => $slaText,
            'created_at_fmt' => $t->created_at ? $t->created_at->translatedFormat('d M Y, H:i') : '-',
        ];
    });
@endphp

<script>
    window.currentTicketId = null;
    window.ticketsMap = @json($ticketsJsonMap);
    
    window.openVerifyModal = function(ticketRef) {
        const ticket = typeof ticketRef === 'object' && ticketRef !== null ? ticketRef : window.ticketsMap[ticketRef];
        if (!ticket) return;

        currentTicketId = ticket.id;
        document.getElementById('modal-kode').textContent = ticket.kode_tiket;
        document.getElementById('modal-judul').textContent = ticket.judul;
        document.getElementById('modal-pelapor-sub').textContent = `Diajukan pada: ${ticket.created_at_fmt} WIB \u2022 ${ticket.skpd}`;
        document.getElementById('modal-deskripsi').textContent = ticket.deskripsi;

        // Full link
        const fullLink = document.getElementById('modal-full-link');
        if (fullLink) fullLink.href = `/admin/tiket/${ticket.id}`;

        // Metadata Card
        document.getElementById('modal-pelapor-nama').textContent = ticket.nama_pelapor;
        document.getElementById('modal-pelapor-skpd').textContent = ticket.skpd;
        document.getElementById('modal-pelapor-nip').textContent = ticket.nip_pelapor ? `NIP: ${ticket.nip_pelapor}` : 'NIP: -';
        document.getElementById('modal-kategori-text').textContent = ticket.kategori_nama || 'Umum';
        document.getElementById('modal-waktu-text').textContent = `${ticket.created_at_fmt} WIB`;

        // WhatsApp direct link
        const waContainer = document.getElementById('modal-pelapor-wa');
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

        // SLA Respon Badge di Header Modal
        const slaBadge = document.getElementById('modal-sla-badge');
        if (slaBadge && ticket.sla_respon_text) {
            slaBadge.className = ticket.is_overdue 
                ? 'inline-flex items-center gap-1 text-[10px] font-mono px-2 py-0.5 rounded-full font-bold bg-rose-500/20 text-rose-300 border border-rose-400/30'
                : 'inline-flex items-center gap-1 text-[10px] font-mono px-2 py-0.5 rounded-full font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30';
            slaBadge.textContent = ticket.sla_respon_text;
            slaBadge.classList.remove('hidden');
        } else if (slaBadge) {
            slaBadge.classList.add('hidden');
        }

        // Catatan Kelengkapan Alert jika ada
        const alertKelengkapan = document.getElementById('modal-kelengkapan-alert');
        const titleKelengkapan = document.getElementById('modal-kelengkapan-title');
        const notesKelengkapan = document.getElementById('modal-kelengkapan-notes');
        const btnVerify = document.getElementById('btn-submit-verify');

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
        const lampiranCardsContainer = document.getElementById('modal-lampiran-grid');
        const lampiranEmptyState = document.getElementById('modal-lampiran-empty');
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
        const selectBidang = document.getElementById('modal-select-bidang');
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

        // Set state bidang sesuai TLP
        handleTlpSelect(ticket.tlp_level || 'GREEN');

        // Hubungan Parent-Child Linking
        const isParentCheck = document.getElementById('modal-is-parent');
        const parentSelect = document.getElementById('modal-select-parent');
        if (isParentCheck && parentSelect) {
            isParentCheck.checked = !!ticket.is_parent;
            isParentCheck.disabled = false;
            parentSelect.disabled = false;
            parentSelect.value = ticket.parent_id || '';
            handleParentToggle();
            if (ticket.parent_id) {
                handleParentSelect();
            }
        }

        // Row version (Optimistic locking)
        const rowVersionInput = document.getElementById('modal-row-version');
        if (rowVersionInput) {
            rowVersionInput.value = ticket.row_version || 1;
        }

        // Handle Claim Lock Status
        const lockedBanner = document.getElementById('modal-claim-locked-banner');
        const lockedText = document.getElementById('modal-claim-locked-text');
        const btnVerify = document.getElementById('btn-submit-verify');

        if (ticket.is_claimed_active && !ticket.can_be_claimed) {
            if (lockedBanner) {
                lockedText.textContent = `Tiket ini sedang diverifikasi oleh ${ticket.claimed_user_name || 'Admin Pusat lain'}. Formulir ini dalam mode baca-saja untuk menghindari tabrakan.`;
                lockedBanner.classList.remove('hidden');
            }
            if (btnVerify) {
                btnVerify.disabled = true;
                btnVerify.classList.add('opacity-40', 'cursor-not-allowed');
            }
        } else {
            if (lockedBanner) lockedBanner.classList.add('hidden');
            if (btnVerify) {
                btnVerify.disabled = false;
                btnVerify.classList.remove('opacity-40', 'cursor-not-allowed');
            }
        }

        // Form action
        document.getElementById('verify-form').action = `/admin/tiket/${ticket.id}/verifikasi`;
        document.getElementById('modal-notes').value = '';

        // Tampilkan modal
        const modal = document.getElementById('verify-modal');
        modal.classList.remove('hidden');
        modal.style.display = 'flex';
    }

    function closeVerifyModal() {
        const modal = document.getElementById('verify-modal');
        modal.classList.add('hidden');
        modal.style.display = 'none';
        currentTicketId = null;
    }

    function handleParentToggle() {
        const isParentCheck = document.getElementById('modal-is-parent');
        const parentSelect = document.getElementById('modal-select-parent');
        const parentContainer = document.getElementById('parent-select-container');
        if (!isParentCheck || !parentSelect) return;

        if (isParentCheck.checked) {
            parentSelect.value = '';
            parentSelect.disabled = true;
            if (parentContainer) parentContainer.classList.add('opacity-40', 'pointer-events-none');
        } else {
            parentSelect.disabled = false;
            if (parentContainer) parentContainer.classList.remove('opacity-40', 'pointer-events-none');
        }
    }

    function handleParentSelect() {
        const isParentCheck = document.getElementById('modal-is-parent');
        const parentSelect = document.getElementById('modal-select-parent');
        if (!isParentCheck || !parentSelect) return;

        if (parentSelect.value) {
            isParentCheck.checked = false;
            isParentCheck.disabled = true;
        } else {
            isParentCheck.disabled = false;
        }
    }

    function handleTlpSelect(val) {
        const selectBidang = document.getElementById('modal-select-bidang');
        const warning = document.getElementById('red-bypass-warning');
        const tembusanContainer = document.getElementById('red-tembusan-container');

        if (val === 'RED') {
            selectBidang.disabled = true;
            selectBidang.classList.add('opacity-50', 'bg-slate-100', 'cursor-not-allowed');
            selectBidang.removeAttribute('required');
            warning.classList.remove('hidden');
            if (tembusanContainer) tembusanContainer.classList.remove('hidden');
        } else {
            selectBidang.disabled = false;
            selectBidang.classList.remove('opacity-50', 'bg-slate-100', 'cursor-not-allowed');
            selectBidang.setAttribute('required', 'required');
            warning.classList.add('hidden');
            if (tembusanContainer) tembusanContainer.classList.add('hidden');
        }
    }

    function submitVerifikasi() {
        const form = document.getElementById('verify-form');
        const selectedTlp = document.querySelector('input[name="tlp_level"]:checked')?.value;
        const selectBidang = document.getElementById('modal-select-bidang');

        if (selectedTlp !== 'RED' && !selectBidang.value) {
            showAdminAlert({
                title: 'Bidang Belum Dipilih',
                message: 'Silakan pilih bidang tujuan disposisi terlebih dahulu!',
                icon: '🏢',
                iconBg: 'bg-rose-50 text-rose-700 border-rose-200'
            });
            selectBidang.focus();
            return;
        }

        const bidangText = selectedTlp === 'RED' ? 'Bidang Persandian & CSIRT (Bypass RED)' : selectBidang.options[selectBidang.selectedIndex].text;

        showAdminConfirm({
            title: 'Konfirmasi Verifikasi & Disposisi',
            message: `Verifikasi tiket ini dan teruskan disposisi ke ${bidangText}?`,
            icon: '✅',
            iconBg: 'bg-emerald-50 text-emerald-700 border-emerald-200',
            confirmText: 'Ya, Verifikasi & Disposisi',
            confirmBtnClass: 'bg-emerald-600 hover:bg-emerald-700 text-white font-black',
            onConfirm: function () {
                form.action = `/admin/tiket/${currentTicketId}/verifikasi`;
                form.submit();
            }
        });
    }

    function submitMintaKelengkapan() {
        const notes = document.getElementById('modal-notes').value.trim();
        if (notes.length < 5) {
            showAdminAlert({
                title: 'Catatan Diperlukan',
                message: 'Harap tuliskan catatan rincian data/berkas yang masih kurang pada kolom catatan (minimal 5 karakter)!',
                icon: '📝',
                iconBg: 'bg-amber-50 text-amber-800 border-amber-200'
            });
            document.getElementById('modal-notes').focus();
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
                const form = document.getElementById('verify-form');
                document.getElementById('hidden-catatan').value = notes;
                form.action = `/admin/tiket/${currentTicketId}/minta-kelengkapan`;
                form.submit();
            }
        });
    }

    // =========================================================================
    // FITUR TINDAKAN MASSAL (BATCH LINKING & INCIDENT CLUSTERING)
    // =========================================================================
    function toggleCheckAll(master) {
        const checkboxes = document.querySelectorAll('.ticket-select-cb');
        checkboxes.forEach(cb => {
            cb.checked = master.checked;
        });
        updateBatchActionBar();
    }

    function handleRowCheckboxChange() {
        const checkboxes = document.querySelectorAll('.ticket-select-cb');
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        const checkAll = document.getElementById('check-all');
        if (checkAll) {
            checkAll.checked = checkboxes.length > 0 && allChecked;
        }
        updateBatchActionBar();
    }

    function updateBatchActionBar() {
        const selected = document.querySelectorAll('.ticket-select-cb:checked');
        const count = selected.length;
        const bar = document.getElementById('batch-action-bar');
        const countBadge = document.getElementById('batch-count-badge');
        const countText = document.getElementById('batch-count-text');

        if (count >= 1) {
            countBadge.textContent = count;
            countText.textContent = count;
            bar.classList.remove('hidden');
        } else {
            bar.classList.add('hidden');
        }
    }

    function clearSelectedCheckboxes() {
        document.querySelectorAll('.ticket-select-cb').forEach(cb => cb.checked = false);
        const checkAll = document.getElementById('check-all');
        if (checkAll) checkAll.checked = false;
        updateBatchActionBar();
    }

    function selectClusterTickets(categoryId) {
        document.querySelectorAll('.ticket-select-cb').forEach(cb => {
            if (parseInt(cb.dataset.categoryId) === parseInt(categoryId)) {
                cb.checked = true;
            } else {
                cb.checked = false;
            }
        });
        updateBatchActionBar();
        openBatchModal();
    }

    function openBatchModal() {
        const selectedCbs = Array.from(document.querySelectorAll('.ticket-select-cb:checked'));
        if (selectedCbs.length < 2) {
            alert('Silakan pilih minimal 2 tiket untuk digabungkan ke dalam Induk Tiket!');
            return;
        }

        const countText = document.getElementById('batch-modal-count-text');
        countText.textContent = `${selectedCbs.length} Tiket Terpilih`;

        const hiddenContainer = document.getElementById('batch-hidden-inputs');
        hiddenContainer.innerHTML = '';

        const previewContainer = document.getElementById('batch-tickets-preview');
        previewContainer.innerHTML = '';

        selectedCbs.forEach((cb, idx) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ticket_ids[]';
            input.value = cb.value;
            hiddenContainer.appendChild(input);

            const item = document.createElement('div');
            item.className = 'flex items-center justify-between p-2 rounded-lg bg-white border border-slate-200 text-xs';
            const isFirst = idx === 0;
            item.innerHTML = `
                <div class="flex items-center gap-2">
                    <span class="font-mono font-bold ${isFirst ? 'bg-amber-400 text-slate-950 font-black' : 'bg-slate-100 text-slate-800'} px-2 py-0.5 rounded text-[11px]">
                        ${isFirst ? '👑 Calon Induk: ' : '↳ Anak: '}${cb.dataset.kode}
                    </span>
                    <span class="font-bold text-slate-900">${cb.dataset.skpd}</span>
                </div>
                <span class="text-slate-500 truncate max-w-xs text-[11px]">${cb.dataset.judul}</span>
            `;
            previewContainer.appendChild(item);
        });

        document.getElementById('batch-modal').classList.remove('hidden');
    }

    function closeBatchModal() {
        document.getElementById('batch-modal').classList.add('hidden');
    }

    function toggleBatchParentAction(val) {
        const container = document.getElementById('batch-existing-parent-container');
        const selectParent = document.getElementById('batch-parent-id');
        if (val === 'existing') {
            container.classList.remove('hidden');
            selectParent.setAttribute('required', 'required');
        } else {
            container.classList.add('hidden');
            selectParent.removeAttribute('required');
        }
    }
</script>
@endsection
