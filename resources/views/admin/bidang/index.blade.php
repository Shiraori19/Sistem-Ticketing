@extends('admin.layout', ['title' => 'Tiket Bidang ' . ($user->bidang->nama_bidang ?? 'DKIS')])

@section('content')
@php
    $currentFilter = request('filter');
    $pageTitle = match($currentFilter) {
        'antrean' => 'Antrean Tiket Masuk Bidang',
        'pengerjaan' => 'Tiket Dalam Pengerjaan',
        'arsip' => 'Arsip Tiket Selesai',
        'siaran_siber' => 'Siaran Insiden Siber Kota Cirebon (TLP: RED)',
        default => 'Semua Tiket Bidang'
    };

    $pageSubtitle = match($currentFilter) {
        'antrean' => 'Daftar tiket hasil disposisi Admin Pusat yang siap ditugaskan ke teknisi spesialisasi bidang.',
        'pengerjaan' => 'Daftar tiket yang sedang ditangani oleh teknisi lapangan. Laporkan solusi dan unggah berkas bukti penyelesaian kendala.',
        'arsip' => 'Riwayat seluruh tiket bidang yang telah berhasil diselesaikan, dikonfirmasi pelapor, atau ditutup resmi.',
        'siaran_siber' => 'Siaran insiden siber kritis untuk koordinasi situasional seluruh OPD dan bidang Pemkot Cirebon (dikelola oleh CSIRT / Persandian).',
        default => 'Monitoring dan penelusuran seluruh tiket pengaduan yang terdaftar pada bidang Anda.'
    };

    $badgeLabel = match($currentFilter) {
        'antrean' => 'Antrean Menunggu Tindakan',
        'pengerjaan' => 'Sedang Dikerjakan',
        'arsip' => 'Tiket Diarsipkan',
        'siaran_siber' => 'Siaran Insiden Siber',
        default => 'Total Tiket Bidang'
    };

    $badgeColor = match($currentFilter) {
        'antrean' => 'bg-amber-500 text-slate-950',
        'pengerjaan' => 'bg-sky-600 text-white',
        'arsip' => 'bg-slate-800 text-white',
        'siaran_siber' => 'bg-rose-600 text-white',
        default => 'bg-emerald-600 text-white'
    };

    $iconHeader = match($currentFilter) {
        'antrean' => '📥',
        'pengerjaan' => '🔧',
        'arsip' => '📁',
        'siaran_siber' => '🛡️',
        default => '📋'
    };
@endphp

<div class="space-y-6">
    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 text-xs font-semibold mb-1">
                <span>{{ $iconHeader }}</span> Bidang: {{ $user->bidang->nama_lengkap ?? ($user->bidang->nama_bidang . ' (' . $user->bidang->kode . ')') }}
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">{{ $pageTitle }}</h1>
            <p class="text-xs text-slate-500 mt-1">
                {{ $pageSubtitle }}
            </p>
        </div>

        <div class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white border border-slate-200 shadow-xs text-xs font-bold text-slate-700">
            <span>{{ $badgeLabel }}:</span>
            <span class="px-2.5 py-0.5 rounded-full {{ $badgeColor }} font-black">{{ $tickets->total() }}</span>
        </div>
    </div>

    @if($currentFilter === 'siaran_siber')
        <div class="p-4 rounded-2xl bg-rose-50 border-2 border-rose-300 text-rose-950 flex items-start gap-3 shadow-xs">
            <span class="text-2xl">🛡️</span>
            <div>
                <h4 class="font-bold text-sm text-rose-900">Kanal Informasi Insiden Siber Lintas Sektor (TLP: RED)</h4>
                <p class="text-xs text-rose-800 mt-0.5 leading-relaxed">
                    Tiket pada kanal ini merupakan siaran insiden keamanan siber Kota Cirebon. Penugasan teknisi dan penanganan resolusi dilakukan secara terpusat oleh <strong>Bidang Persandian & CSIRT DKIS</strong>. Bidang lain memantau untuk kesiapan mitigasi infrastruktur terkait.
                </p>
            </div>
        </div>
    @endif

    <!-- Pencarian Sederhana Sesuai Menu Aktif -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200 p-4">
        <form action="{{ route('admin.bidang.tiket.index') }}" method="GET" class="flex flex-col sm:flex-row gap-3">
            @if($currentFilter)
                <input type="hidden" name="filter" value="{{ $currentFilter }}">
            @endif
            <div class="flex-1">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari kode tiket, nama pelapor, pokok kendala, atau teknisi..."
                    class="w-full text-xs rounded-xl border border-slate-300 p-2.5 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
            </div>
            <button type="submit" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-xs transition">
                Cari Tiket
            </button>
            @if(request('q'))
                <a href="{{ route('admin.bidang.tiket.index', $currentFilter ? ['filter' => $currentFilter] : []) }}"
                   class="px-3 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition border border-slate-200 text-center">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- BANNER SMART INCIDENT GROUPING (MINIMALIS & JELAS STATUSNYA) -->
    @if(isset($foldedChildrenCount) && $foldedChildrenCount > 0)
        @php
            $isUnfolded = (bool) request('tampilkan_anak');
            $firstGrouped = $tickets->first(fn($tk) => $tk->is_parent || $tk->parent_id);
            $targetAnchor = $firstGrouped ? '#tiket-' . $firstGrouped->kode_tiket : '';
        @endphp
        <div class="px-4 py-3.5 bg-white border {{ $isUnfolded ? 'border-indigo-300 bg-indigo-50/20' : 'border-amber-300/80 bg-amber-50/30' }} rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs shadow-xs">
            <div class="flex items-start sm:items-center gap-3">
                <span class="w-9 h-9 rounded-xl {{ $isUnfolded ? 'bg-indigo-100 text-indigo-700 border border-indigo-200' : 'bg-amber-400 text-slate-950 font-black' }} flex items-center justify-center text-base shrink-0 shadow-2xs">
                    {{ $isUnfolded ? '👁️' : '👑' }}
                </span>
                <div>
                    <div class="flex items-center flex-wrap gap-2">
                        <span class="font-extrabold text-sm {{ $isUnfolded ? 'text-indigo-950' : 'text-slate-900' }}">
                            {{ $isUnfolded ? 'Mode Tiket Lepas Aktif (Daftar Terurai / Terpecah)' : 'Smart Incident Grouping Aktif' }}
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full {{ $isUnfolded ? 'bg-indigo-100 text-indigo-800 border border-indigo-200' : 'bg-amber-200 text-amber-950 font-bold border border-amber-300' }} text-[10px]">
                            {{ $isUnfolded ? 'Tiket Anak Ditampilkan Sebagai Kartu Terpisah' : $foldedChildrenCount . ' Tiket Anak Terlipat Rapi' }}
                        </span>
                    </div>
                    <p class="text-slate-600 text-[11px] mt-1">
                        {{ $isUnfolded ? 'Tiket anak kini dipecah keluar dan tampil sebagai kartu tersendiri di antrean. Anda juga dapat melepaskan relasi secara permanen jika diperlukan.' : 'Pengaduan serupa lintas OPD disatukan di Kartu Induk. Menyelesaikan Induk otomatis menyelesaikan seluruh tiket anak.' }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                @if($isUnfolded)
                    <a href="{{ route('admin.bidang.tiket.index', array_merge(request()->except('tampilkan_anak'), ['tampilkan_anak' => 0])) }}"
                        class="px-3.5 py-2 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold rounded-xl text-xs transition shadow-xs flex items-center gap-1.5 cursor-pointer">
                        <span>👑 Lipat Kembali ke Induk Tiket</span>
                    </a>
                @else
                    <a href="{{ route('admin.bidang.tiket.index', array_merge(request()->except('tampilkan_anak'), ['tampilkan_anak' => 1])) }}{{ $targetAnchor }}"
                        class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs transition shadow-xs flex items-center gap-1.5 cursor-pointer">
                        <span>👁️ Urai / Pecah Tampilan ({{ $foldedChildrenCount }} Anak)</span>
                    </a>
                @endif
            </div>
        </div>
    @endif

    @if(request('q'))
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 p-3.5 bg-emerald-50 border border-emerald-200 rounded-2xl text-xs text-emerald-900 shadow-xs">
            <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-lg bg-emerald-600 text-white flex items-center justify-center font-bold text-xs">🎯</span>
                <span>Fokus Penanganan Tiket: <strong class="font-mono font-bold bg-white px-2 py-0.5 rounded border border-emerald-300">{{ request('q') }}</strong></span>
            </div>
            <a href="{{ route('admin.bidang.tiket.index', $currentFilter ? ['filter' => $currentFilter] : []) }}" 
               class="inline-flex items-center gap-1 font-bold text-emerald-800 hover:text-emerald-950 hover:underline">
                <span>Tampilkan Seluruh {{ $pageTitle }}</span> &rarr;
            </a>
        </div>
    @endif

    @if($tickets->isEmpty())
        <div class="bg-white rounded-3xl border border-slate-200 p-12 text-center shadow-xs">
            <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-4 border border-emerald-100 shadow-xs">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
            </div>
            <h3 class="text-lg font-bold text-slate-900">Tidak Ada Tiket</h3>
            <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
                @if($currentFilter === 'antrean')
                    Saat ini tidak ada antrean tiket baru yang menunggu penugasan teknisi di bidang Anda.
                @elseif($currentFilter === 'pengerjaan')
                    Saat ini tidak ada tiket yang sedang dalam proses penanganan aktif di lapangan.
                @elseif($currentFilter === 'arsip')
                    Belum ada tiket yang diselesaikan atau diarsipkan pada bidang Anda.
                @else
                    Belum ada data tiket yang cocok dengan kriteria pencarian ini.
                @endif
            </p>
        </div>
    @else
        <!-- DAFTAR TIKET BIDANG -->
        <div class="space-y-6">
            @php
                // Daftar personil teknisi lapangan spesifik per bidang
                $bidangKode = strtoupper($user->bidang->kode ?? '');
                $presetTechnicians = [
                    'PEMDI' => [
                        'Faisal Akbar, S.Kom. (Senior Web Developer)',
                        'Dian Pratama, A.Md. (Database & App Specialist)',
                        'Budi Raharjo, S.T. (System Analyst)',
                        'Siti Nurhaliza, S.Kom. (QA & Integrasi Sistem)',
                    ],
                    'TEKNIS' => [
                        'Faisal Akbar, S.Kom. (Senior Web Developer)',
                        'Dian Pratama, A.Md. (Database & App Specialist)',
                        'Budi Raharjo, S.T. (System Analyst)',
                        'Siti Nurhaliza, S.Kom. (QA & Integrasi Sistem)',
                    ],
                    'ITIK' => [
                        'Hendra Gunawan, S.Kom. (Network & FO Specialist)',
                        'Eko Wahyudi, A.Md. (Data Center & Hardware Tech)',
                        'Rian Hidayat, S.T. (CCTV & Bandwidth Specialist)',
                        'Dedi Mulyadi (Field Support Jaringan)',
                    ],
                    'JARINGAN' => [
                        'Hendra Gunawan, S.Kom. (Network & FO Specialist)',
                        'Eko Wahyudi, A.Md. (Data Center & Hardware Tech)',
                        'Rian Hidayat, S.T. (CCTV & Bandwidth Specialist)',
                        'Dedi Mulyadi (Field Support Jaringan)',
                    ],
                    'PERSANDIAN' => [
                        'Sandi Kusuma, M.Kom. (CSIRT Cyber Security Analyst)',
                        'Bripka Yoga Pratama, S.T. (TTE & BSrE Operator)',
                        'Arif Wicaksono, S.Tr.Kom. (Vulnerability Assessor)',
                        'Maya Kartika, S.Si. (Security Incident Responder)',
                    ],
                ][$bidangKode] ?? [
                    'Agus Setiawan (Teknisi Lapangan)',
                    'Bambang Irawan (Teknisi Jaringan)',
                    'Cahyo Wibowo (Teknisi Sistem)',
                ];

                // Gabungkan dengan akun petugas terdaftar di database
                $dbStaff = $staffUsers ?? [];
                $technicians = array_unique(array_merge($presetTechnicians, $dbStaff));
            @endphp

            @php
                $userIsPersandian = $user->bidang && (strtoupper($user->bidang->kode) === 'PERSANDIAN' || str_contains(strtoupper($user->bidang->nama_bidang), 'PERSANDIAN'));
            @endphp

            @foreach($tickets as $t)
                @php
                    $isTlpRed = strtoupper($t->tlp_level ?? '') === 'RED';
                    $isReadOnlyRed = $isTlpRed && !$userIsPersandian;
                @endphp
                <div id="tiket-{{ $t->kode_tiket }}" class="bg-white rounded-2xl shadow-xs border {{ $t->is_parent ? 'border-2 border-amber-300 ring-2 ring-amber-300/20 shadow-md' : (request('q') === $t->kode_tiket ? 'border-emerald-500 ring-2 ring-emerald-500/30 shadow-md' : 'border-slate-200/90') }} overflow-hidden transition">
                    <!-- Top Bar Card -->
                    <div class="p-4 sm:p-5 bg-slate-50/70 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center flex-wrap gap-2.5">
                            <span class="font-mono text-sm font-black text-slate-900 bg-white px-3 py-1 rounded-lg border border-slate-300 shadow-xs">
                                {{ $t->kode_tiket }}
                            </span>
                            @if($isReadOnlyRed)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-300 shadow-xs animate-pulse">
                                    🛡️ TLP: RED - Insiden Kritis (Read Only)
                                </span>
                            @else
                                <x-status-badge :status="$t->status" context="bidang" />
                            @endif
                            <x-sla-indicator :ticket="$t" />
                            @if($t->tlp_level)
                                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold
                                    @if($t->tlp_level === 'RED') bg-rose-600 text-white
                                    @elseif($t->tlp_level === 'AMBER') bg-amber-500 text-slate-950
                                    @elseif($t->tlp_level === 'GREEN') bg-emerald-600 text-white
                                    @else bg-slate-500 text-white @endif">
                                    TLP: {{ $t->tlp_level }}
                                </span>
                            @endif

                            @if($t->is_parent)
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-black bg-amber-400 text-slate-950 shadow-xs border border-amber-500">
                                    👑 INDUK TIKET
                                </span>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-900 border border-amber-300">
                                    🔗 {{ $t->children->count() }} Tiket Anak
                                </span>
                            @elseif($t->parent_id)
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-800 border border-indigo-200">
                                    🔗 Anak dari: <strong class="font-mono">{{ $t->parent?->kode_tiket }}</strong>
                                </span>
                            @endif
                        </div>

                        <div class="flex items-center flex-wrap gap-3">
                            <div class="text-xs text-slate-600">
                                Pelapor: <strong>{{ $t->nama_pelapor }}</strong> ({{ $t->skpd }}) &bull; Kontak: <a href="tel:{{ $t->no_whatsapp }}" class="text-blue-700 font-bold hover:underline">{{ $t->no_whatsapp }}</a>
                            </div>

                            @if(!$isReadOnlyRed && in_array($t->status, ['verified', 'in_progress', 'reopened']) && !$isTlpRed)
                                <button type="button" 
                                    data-id="{{ $t->id }}"
                                    data-kode="{{ $t->kode_tiket }}"
                                    data-judul="{{ $t->judul }}"
                                    onclick="openReturnModal(this.dataset.id, this.dataset.kode, this.dataset.judul)"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-300 text-xs font-bold transition shadow-xs cursor-pointer">
                                    <span>↩️ Kembalikan (Salah Kamar)</span>
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="p-5 sm:p-6 space-y-5">
                        <!-- Judul & Deskripsi -->
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Permasalahan:</span>
                            <h2 class="text-base font-bold text-slate-900">{{ $t->judul }}</h2>
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5 text-xs text-slate-500 mt-1.5">
                                <span>Kategori: <strong class="text-slate-700">{{ $t->category?->nama_kategori }}</strong></span>
                                <span>&bull;</span>
                                <span>Target SLA: <strong class="text-slate-700">{{ round(((float)($t->sla_resolusi_jam ?? 336))/24) }} Hari ({{ (int)($t->sla_resolusi_jam ?? 336) }} Jam)</strong></span>
                                @if($t->sla_dimulai_at)
                                    @php
                                        $slaStart = \Carbon\Carbon::parse($t->sla_dimulai_at);
                                        $totalMins = max(1, ((float)($t->sla_resolusi_jam ?: 336)) * 60);
                                        $deadline = $t->sla_deadline_at ? \Carbon\Carbon::parse($t->sla_deadline_at) : $slaStart->copy()->addMinutes($totalMins);
                                        $minsLeft = now()->diffInMinutes($deadline, false);
                                    @endphp
                                    <span>&bull;</span>
                                    <span>Mulai: <strong class="text-slate-700">{{ $slaStart->translatedFormat('d M, H:i') }}</strong></span>
                                    <span>&bull;</span>
                                    @if(in_array($t->status, ['resolved', 'closed', 'force_closed']))
                                        <span class="inline-flex items-center gap-1 font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                            <span>🎯</span> Selesai Ditangani
                                        </span>
                                    @elseif($minsLeft > 0)
                                        <span class="inline-flex items-center gap-1 text-slate-700 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                                            <span>⏰</span> Batas: <strong>{{ $deadline->translatedFormat('d M, H:i') }}</strong> 
                                            (<span class="{{ $minsLeft < 60 ? 'text-amber-700 font-bold' : 'text-emerald-700 font-semibold' }}">Sisa {{ $minsLeft >= 60 ? floor($minsLeft / 60) . ' jam ' . ($minsLeft % 60) . ' mnt' : $minsLeft . ' mnt' }}</span>)
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 font-bold text-rose-700 bg-rose-50 px-2 py-0.5 rounded border border-rose-200 animate-pulse">
                                            <span>⚠️</span> Breach (Lewat {{ abs($minsLeft) >= 60 ? floor(abs($minsLeft) / 60) . ' jam ' . (abs($minsLeft) % 60) . ' mnt' : abs($minsLeft) . ' mnt' }})
                                        </span>
                                    @endif
                                @else
                                    <span>&bull;</span>
                                    <span class="text-slate-400 italic">SLA aktif setelah verifikasi</span>
                                @endif
                            </div>
                            <div class="text-xs text-slate-700 bg-slate-50 p-3.5 rounded-xl border border-slate-200 mt-2.5 leading-relaxed whitespace-pre-line">
                                {{ $t->deskripsi }}
                            </div>

                            @if($t->lampiran_pelapor || $t->lampiran_tambahan)
                                @php
                                    $lamps = is_array($t->lampiran_pelapor) ? $t->lampiran_pelapor : (is_string($t->lampiran_pelapor) && str_starts_with($t->lampiran_pelapor, '[') ? json_decode($t->lampiran_pelapor, true) : [$t->lampiran_pelapor]);
                                    $lamps = array_filter((array) $lamps);
                                @endphp
                                <div class="mt-2.5 p-3 rounded-xl bg-blue-50/50 border border-blue-200/80 space-y-1.5">
                                    <span class="text-xs font-bold text-blue-900 block">📎 Dokumen Bukti Lampiran Pelapor:</span>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($lamps as $idx => $lamp)
                                            @php
                                                $ext = strtolower(pathinfo($lamp, PATHINFO_EXTENSION));
                                                $isPdf = $ext === 'pdf';
                                                $filename = basename($lamp);
                                                $previewUrl = route('admin.tiket.download', ['kode' => $t->kode_tiket, 'type' => 'lampiran', 'file' => $lamp]);
                                            @endphp
                                            <button type="button" 
                                                onclick="openInAppPreview(this.dataset.previewUrl, this.dataset.previewTitle)"
                                                data-preview-url="{{ $previewUrl }}"
                                                data-preview-title="Bukti #{{ $idx + 1 }} ({{ $filename }})"
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white border border-blue-300 text-blue-900 text-xs font-semibold hover:bg-blue-100 transition shadow-2xs cursor-pointer">
                                                <span>{{ $isPdf ? '📄' : '🖼️' }}</span>
                                                <span>Berkas {{ $idx + 1 }} ({{ strtoupper($ext) }})</span>
                                                <span class="text-blue-600 font-bold ml-0.5">👁️ Preview</span>
                                            </button>
                                        @endforeach
                                        @if($t->lampiran_tambahan)
                                            @php
                                                $tambahanUrl = route('admin.tiket.download', ['kode' => $t->kode_tiket, 'type' => 'lampiran_tambahan']);
                                            @endphp
                                            <button type="button" 
                                                onclick="openInAppPreview(this.dataset.previewUrl, this.dataset.previewTitle)"
                                                data-preview-url="{{ $tambahanUrl }}"
                                                data-preview-title="Berkas Tambahan ({{ $t->kode_tiket }})"
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 border border-emerald-300 text-emerald-900 text-xs font-semibold hover:bg-emerald-100 transition shadow-2xs cursor-pointer">
                                                <span>📄 Berkas Tambahan</span>
                                                <span class="text-emerald-700 font-bold ml-0.5">👁️ Preview</span>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <!-- DAFTAR TIKET ANAK TERHUBUNG (COMPACT & BERSIH) -->
                            @if($t->is_parent && $t->children->isNotEmpty())
                                @if(request('tampilkan_anak'))
                                    <!-- Tampilan Saat Mode Urai Aktif: Berikan banner informatif bahwa anak sudah terpecah keluar -->
                                    <div class="mt-3 p-3 bg-indigo-50/80 border border-indigo-200 rounded-xl text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-2 shadow-2xs">
                                        <div class="flex items-center gap-2 text-indigo-950 font-semibold">
                                            <span class="text-sm">👁️</span>
                                            <span>Mode Terurai Aktif: <strong>{{ $t->children->count() }} Tiket Anak</strong> telah dipecah keluar menjadi kartu tersendiri di antrean.</span>
                                        </div>
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span class="text-[11px] text-slate-500">Lihat kartu anak:</span>
                                            @foreach($t->children as $child)
                                                <a href="#tiket-{{ $child->kode_tiket }}" class="px-2 py-0.5 bg-white border border-indigo-300 rounded font-mono font-bold text-indigo-800 hover:bg-indigo-100 transition text-[11px] shadow-2xs">
                                                    #{{ $child->kode_tiket }} &darr;
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <!-- Tampilan Saat Mode Terlipat (Default): Accordion Rapi dengan tombol Lepas -->
                                    <div class="mt-3 border border-amber-200 rounded-xl overflow-hidden bg-amber-50/30 text-xs">
                                        <button type="button" onclick="document.getElementById('children-list-{{ $t->id }}').classList.toggle('hidden')"
                                            class="w-full px-3.5 py-2 flex items-center justify-between text-left hover:bg-amber-100/50 transition cursor-pointer">
                                            <div class="flex items-center gap-2">
                                                <span>👑</span>
                                                <span class="font-bold text-amber-950">Tiket Anak Terhubung ({{ $t->children->count() }} Pengaduan OPD)</span>
                                                <span class="text-[11px] text-slate-500 hidden sm:inline">&bull; Selesai otomatis saat Induk selesai</span>
                                            </div>
                                            <span class="text-amber-800 font-bold text-[11px] flex items-center gap-1">
                                                <span>Rincian Anak</span> ▾
                                            </span>
                                        </button>

                                        <div id="children-list-{{ $t->id }}" class="hidden p-3 pt-0 space-y-2 border-t border-amber-200/60 mt-1">
                                            @foreach($t->children as $child)
                                                <div class="flex flex-col sm:flex-row sm:items-center justify-between p-2.5 rounded-lg bg-white border border-slate-200 gap-2">
                                                    <div class="flex items-center gap-2 flex-wrap">
                                                        <span class="font-mono font-bold text-slate-800 bg-slate-100 px-2 py-0.5 rounded text-[11px]">{{ $child->kode_tiket }}</span>
                                                        <span class="font-bold text-slate-900 text-xs">{{ $child->skpd }}</span>
                                                        <span class="text-slate-500 text-[11px]">({{ $child->nama_pelapor }})</span>
                                                        @if($child->teknisi_lapangan)
                                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-800 border border-blue-200">
                                                                👷 {{ $child->teknisi_lapangan }}
                                                            </span>
                                                        @else
                                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] text-slate-500 bg-slate-50 border border-slate-200">
                                                                Ikut Teknisi Induk
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
                                                        <span class="text-slate-600 text-[11px] truncate max-w-xs">{{ Str::limit($child->judul, 25) }}</span>
                                                        <x-status-badge :status="$child->status" context="bidang" />
                                                        <a href="{{ route('admin.bidang.tiket.index', ['tampilkan_anak' => 1]) }}#tiket-{{ $child->kode_tiket }}"
                                                            class="px-2 py-0.5 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 rounded text-[10px] font-bold transition flex items-center gap-0.5"
                                                            title="Tunjuk teknisi khusus untuk tiket anak ini di mode terurai">
                                                            <span>👷 Tunjuk Khusus ↗</span>
                                                        </a>
                                                        <button type="button" onclick="openUnlinkModal({{ $child->id }}, '{{ $child->kode_tiket }}', '{{ $t->kode_tiket }}')"
                                                            class="px-2 py-0.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded text-[10px] font-bold transition cursor-pointer"
                                                            title="Lepaskan tiket ini dari induk agar berdiri sendiri">
                                                            ✂️ Lepas
                                                        </button>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @elseif($t->parent_id && $t->parent)
                                <div class="mt-2.5 px-3.5 py-2.5 rounded-xl bg-indigo-50/90 border border-indigo-200 text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-2 shadow-2xs">
                                    <div class="flex items-center gap-2 text-indigo-950">
                                        <span class="text-sm">🔗</span>
                                        <span>
                                            Tiket Anak Terhubung ke Induk:
                                            <a href="#tiket-{{ $t->parent->kode_tiket }}" class="font-mono font-bold bg-white px-2 py-0.5 rounded border border-indigo-300 text-indigo-950 hover:underline">
                                                #{{ $t->parent->kode_tiket }}
                                            </a>
                                            <span class="text-slate-600 font-medium">({{ Str::limit($t->parent->judul, 35) }})</span>
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <span class="text-[10px] font-bold text-indigo-700 bg-indigo-100 px-2 py-0.5 rounded border border-indigo-200">
                                            Auto-Sync Selesai
                                        </span>
                                        <button type="button" onclick="openUnlinkModal({{ $t->id }}, '{{ $t->kode_tiket }}', '{{ $t->parent->kode_tiket }}')"
                                            class="px-2.5 py-1 bg-white hover:bg-rose-50 text-rose-700 border border-rose-300 rounded-lg text-[11px] font-bold transition shadow-2xs cursor-pointer flex items-center gap-1"
                                            title="Lepaskan tiket ini dari Induk agar berdiri mandiri">
                                            <span>✂️ Lepas dari Induk (Pecah Jadi Mandiri)</span>
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Status Tim Teknisi Aktif (Multi-Personil) -->
                        @if($t->assignments && $t->assignments->isNotEmpty())
                            <div class="p-3.5 bg-gradient-to-r from-sky-50 to-blue-50/60 border border-sky-200 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between text-xs gap-2">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-bold text-sky-950 flex items-center gap-1.5">
                                        <span>👷</span> Tim Teknisi:
                                    </span>
                                    @foreach($t->assignments as $assign)
                                        @if($assign->role_in_task === 'lead_pic')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-amber-100 border border-amber-300 text-amber-950 font-black text-xs shadow-2xs">
                                                <span>👑</span>
                                                <span>{{ $assign->technician_name }}</span>
                                                <span class="text-[9px] bg-amber-200 text-amber-900 px-1 py-0.2 rounded font-extrabold uppercase">Lead</span>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-white border border-sky-200 text-sky-900 font-semibold text-xs shadow-2xs">
                                                <span>👤</span>
                                                <span>{{ $assign->technician_name }}</span>
                                            </span>
                                        @endif
                                    @endforeach
                                </div>
                                <span class="text-[11px] font-bold text-sky-800 bg-white px-2.5 py-0.5 rounded-full border border-sky-200 self-start sm:self-auto">
                                    ⚡ Sedang Dikerjakan
                                </span>
                            </div>
                        @elseif($t->teknisi_lapangan)
                            <div class="p-3 bg-sky-50/70 border border-sky-200 rounded-xl flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2 text-sky-900 font-semibold">
                                    <svg class="w-4 h-4 text-sky-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                                    <span>Teknisi Ditugaskan: <strong>{{ $t->teknisi_lapangan }}</strong></span>
                                </div>
                                <span class="text-[11px] text-sky-700 font-medium">Dalam Pengerjaan</span>
                            </div>
                        @endif

                        <!-- Accordion Riwayat & Jejak Penanganan Tiket (status_history) -->
                        <div class="border border-slate-200/90 rounded-2xl overflow-hidden bg-slate-50/60 transition">
                            <button type="button" onclick="toggleHistory('hist-{{ $t->id }}')" 
                                class="w-full flex items-center justify-between p-3.5 text-left hover:bg-slate-100/80 transition cursor-pointer select-none">
                                <span class="text-xs font-bold text-slate-700 flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center text-xs">📜</span>
                                    <span>Riwayat & Jejak Penanganan Tiket</span>
                                    <span class="px-2 py-0.5 rounded-full bg-slate-200 text-slate-700 text-[10px] font-bold">
                                        {{ count($t->status_history ?? []) }} Catatan
                                    </span>
                                </span>
                                <span id="icon-hist-{{ $t->id }}" class="text-xs text-emerald-700 font-bold flex items-center gap-1">
                                    <span>Lihat Kronologi</span>
                                    <svg class="w-3.5 h-3.5 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </span>
                            </button>
                            <div id="hist-{{ $t->id }}" class="hidden p-4 bg-white border-t border-slate-200 space-y-3">
                                @if(!empty($t->status_history))
                                    <div class="border-l-2 border-emerald-400 ml-4 my-2 space-y-5" style="border-left: 2px solid #34d399; margin-left: 1rem;">
                                        @php
                                            $stageLabelsBidang = [
                                                'pending_verification' => 'Pengajuan Pelapor',
                                                'verified'             => 'Baru (Disposisi Masuk)',
                                                'in_progress'          => 'In Progress (Disposisi ke Teknisi)',
                                                'resolved'             => 'Resolved',
                                                'closed'               => 'Selesai',
                                                'reopened'             => 'Reopened',
                                                'eskalasi_pusat'       => 'Dialihkan ke Pusat (Eskalasi)',
                                                'force_closed'         => 'Ditutup Paksa',
                                                'cancelled'            => 'Dibatalkan',
                                            ];
                                        @endphp
                                        @foreach($t->status_history as $h)
                                            <div class="relative pl-7" style="position: relative; padding-left: 1.75rem;">
                                                <div class="absolute -left-[7px] top-1 w-3.5 h-3.5 rounded-full bg-emerald-600 ring-4 ring-white shadow-xs" style="position: absolute; left: -7px; top: 4px; width: 14px; height: 14px; border-radius: 9999px;"></div>
                                                <div class="flex items-center gap-2 flex-wrap text-xs">
                                                    <span class="font-bold text-slate-900">{{ $stageLabelsBidang[$h['status'] ?? ''] ?? ucfirst(str_replace('_', ' ', $h['status'] ?? '-')) }}</span>
                                                    <span class="text-[11px] text-slate-400">&bull; {{ \Carbon\Carbon::parse($h['timestamp'] ?? now())->translatedFormat('d M Y, H:i') }} WIB</span>
                                                    <span class="text-[10px] font-semibold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                                                        Aktor: {{ $h['actor'] ?? '-' }}
                                                    </span>
                                                </div>
                                                @if(!empty($h['note']))
                                                    <div class="text-xs text-slate-700 mt-1.5 bg-slate-50 p-3 rounded-xl border border-slate-200/80 leading-relaxed whitespace-pre-line">
                                                        {{ $h['note'] }}
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-xs text-slate-400 italic pl-2">Belum ada riwayat perubahan status tercatat.</p>
                                @endif
                            </div>
                        </div>

                        <!-- AKSI BIDANG: GRID PENUGASAN TEKNISI & TANDAI SELESAI -->
                        @if($isReadOnlyRed)
                            <!-- Mode Pemantauan Koordinasi Lintas Bidang (Read-Only) untuk TLP RED -->
                            <div class="pt-4 border-t border-rose-200">
                                <div class="p-4 bg-rose-50 border border-rose-200 rounded-2xl flex items-start gap-3">
                                    <span class="text-2xl">🛡️</span>
                                    <div>
                                        <h4 class="text-xs font-bold text-rose-900">Mode Pemantauan Koordinasi Lintas Bidang (Read-Only)</h4>
                                        <p class="text-xs text-rose-700 mt-1 leading-relaxed">
                                            Tiket berklasifikasi <strong>TLP: RED</strong> disiarkan untuk pemantauan koordinasi lintas bidang. Penanganan teknis dan resolusi dilakukan oleh <strong>Bidang Persandian & CSIRT</strong>.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @elseif(in_array($t->status, ['verified', 'in_progress', 'reopened']))
                            <div class="pt-4 border-t border-slate-200 grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <!-- 1. Form Tunjuk Teknisi Lapangan Terhubung Akun & Beban Kerja -->
                                <form action="{{ route('admin.bidang.tiket.assignTechnician', $t->id) }}" method="POST" class="bg-blue-50/40 p-4 sm:p-5 rounded-2xl border border-blue-100/90 space-y-4">
                                    @csrf
                                    <div class="flex items-center justify-between border-b border-blue-200/60 pb-2.5">
                                        <div>
                                            <span class="text-xs font-bold text-blue-950 uppercase tracking-wider flex items-center gap-1.5">
                                                <span>👷</span> Penugasan Teknisi Lapangan
                                            </span>
                                            <p class="text-[11px] text-slate-600 mt-0.5 font-medium">Pilih <strong>1 Koordinator (Lead PIC)</strong>. Centang <strong>'Pendamping' pada teknisi LAIN</strong> jika butuh tim kerja.</p>
                                        </div>
                                        <span class="text-[10px] text-blue-800 bg-blue-100 px-2.5 py-1 rounded-full font-bold border border-blue-200">
                                            Terkontrol Beban Kerja
                                        </span>
                                    </div>

                                    @php
                                        $catName = strtolower(trim($t->category?->nama_kategori ?? ''));
                                        $activeLeadId = $t->activeLeadAssignment?->user_id;
                                        $activeMemberIds = $t->activeAssignments->where('role', 'pendamping')->pluck('user_id')->toArray();
                                        
                                        $ticketTechs = ($techniciansWithWorkload ?? collect())->map(function($tech) use ($catName) {
                                            $isMatch = false;
                                            if ($catName && !empty($tech['skills'])) {
                                                foreach ($tech['skills'] as $sk) {
                                                    $s = strtolower(trim($sk));
                                                    if ($s && (str_contains($catName, $s) || str_contains($s, $catName))) {
                                                        $isMatch = true;
                                                        break;
                                                    }
                                                }
                                            }
                                            $tech['is_cat_match'] = $isMatch;
                                            return $tech;
                                        })->sort(function($a, $b) {
                                            $scoreA = $a['availability'] === 'tersedia' ? 0 : ($a['availability'] === 'sibuk' ? 1 : 2);
                                            $scoreB = $b['availability'] === 'tersedia' ? 0 : ($b['availability'] === 'sibuk' ? 1 : 2);
                                            if ($scoreA !== $scoreB) return $scoreA <=> $scoreB;

                                            $mA = $a['is_cat_match'] ? 0 : 1;
                                            $mB = $b['is_cat_match'] ? 0 : 1;
                                            if ($mA !== $mB) return $mA <=> $mB;

                                            return $a['weighted_load'] <=> $b['weighted_load'];
                                        })->values();
                                    @endphp

                                    @if($ticketTechs->isEmpty())
                                        <div class="p-4 bg-white rounded-xl border border-slate-200 text-center text-xs text-slate-500">
                                            Belum ada akun teknisi aktif yang terdaftar di bidang ini. Hubungi Admin Pusat untuk menambahkan akun teknisi.
                                        </div>
                                    @else
                                        <div class="space-y-2 max-h-[360px] overflow-y-auto pr-1">
                                            @foreach($ticketTechs as $tech)
                                                @php
                                                    $isCurLead = $activeLeadId === $tech['user_id'];
                                                    $isCurMember = in_array($tech['user_id'], $activeMemberIds);
                                                    $onLeave = $tech['is_on_leave'];
                                                    $isOver = $tech['is_full'];
                                                    $loadColorCls = $tech['load_color'] === 'red' ? 'bg-rose-500' : ($tech['load_color'] === 'amber' ? 'bg-amber-500' : 'bg-emerald-500');
                                                    $loadTextCls = $tech['load_color'] === 'red' ? 'text-rose-700' : ($tech['load_color'] === 'amber' ? 'text-amber-700' : 'text-emerald-700');
                                                @endphp
                                                <div class="p-3 bg-white rounded-xl border {{ $isCurLead ? 'border-amber-400 bg-amber-50/20 ring-1 ring-amber-300' : 'border-slate-200' }} {{ $onLeave ? 'opacity-60 bg-slate-50' : '' }} transition hover:shadow-2xs text-xs">
                                                    <!-- Top Row: Radio Lead & Checkbox Pendamping + Name -->
                                                    <div class="flex items-start justify-between gap-2">
                                                        <div class="flex items-start gap-2 flex-1">
                                                            <!-- Radio Lead PIC -->
                                                            <div class="flex items-center gap-1.5 pt-0.5" title="Pilih sebagai Koordinator Lapangan (Lead PIC)">
                                                                <input type="radio" name="lead_user_id" id="lead-{{ $t->id }}-{{ $tech['user_id'] }}"
                                                                    value="{{ $tech['user_id'] }}"
                                                                    {{ $isCurLead ? 'checked' : '' }}
                                                                    {{ $onLeave ? 'disabled' : '' }}
                                                                    onchange="handleLeadChange({{ $t->id }}, {{ $tech['user_id'] }}, {{ $isOver ? 'true' : 'false' }})"
                                                                    class="w-4 h-4 text-amber-600 focus:ring-amber-500 border-slate-300 cursor-pointer disabled:cursor-not-allowed">
                                                                <label for="lead-{{ $t->id }}-{{ $tech['user_id'] }}" class="cursor-pointer font-bold text-slate-900 select-none flex items-center gap-1.5 flex-wrap">
                                                                    <span>👑 {{ $tech['nama'] }}</span>
                                                                    <span class="text-[10px] text-slate-500 font-mono font-normal">({{ $tech['username'] }})</span>
                                                                    @if($isCurLead)
                                                                        <span class="text-[9px] font-black bg-amber-200 text-amber-900 px-1.5 py-0.2 rounded uppercase">Lead Aktif</span>
                                                                    @endif
                                                                </label>
                                                            </div>
                                                        </div>

                                                        <!-- Checkbox / Status Pendamping -->
                                                        <div class="flex items-center gap-1 pt-0.5" id="mem-wrapper-{{ $t->id }}-{{ $tech['user_id'] }}">
                                                            <span id="mem-badge-{{ $t->id }}-{{ $tech['user_id'] }}" class="{{ $isCurLead ? 'inline-flex' : 'hidden' }} items-center gap-1 text-[10px] font-bold text-amber-800 bg-amber-100/90 border border-amber-300 px-2 py-0.5 rounded-md" title="Teknisi ini adalah Koordinator Utama (Lead PIC). Pilih teknisi lain sebagai pendamping jika diperlukan.">
                                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Koordinator Utama
                                                            </span>
                                                            <div id="mem-input-box-{{ $t->id }}-{{ $tech['user_id'] }}" class="{{ $isCurLead ? 'hidden' : 'flex' }} items-center gap-1">
                                                                <input type="checkbox" name="member_user_ids[]" id="mem-{{ $t->id }}-{{ $tech['user_id'] }}"
                                                                    value="{{ $tech['user_id'] }}"
                                                                    {{ $isCurMember ? 'checked' : '' }}
                                                                    {{ $onLeave ? 'disabled' : '' }}
                                                                    class="w-3.5 h-3.5 text-blue-600 focus:ring-blue-500 rounded border-slate-300 cursor-pointer disabled:cursor-not-allowed">
                                                                <label for="mem-{{ $t->id }}-{{ $tech['user_id'] }}" class="text-[10px] font-semibold text-slate-600 select-none cursor-pointer">
                                                                    + Pendamping
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Badges Row -->
                                                    <div class="flex items-center flex-wrap gap-1.5 mt-2">
                                                        <!-- Availability Badge -->
                                                        @if($tech['availability'] === 'tersedia')
                                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center gap-1">
                                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Tersedia
                                                            </span>
                                                        @elseif($tech['availability'] === 'sibuk')
                                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200 flex items-center gap-1">
                                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Sibuk di Lapangan
                                                            </span>
                                                        @else
                                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200 flex items-center gap-1">
                                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Cuti (Terkunci)
                                                            </span>
                                                        @endif

                                                        <!-- Category Match Badge -->
                                                        @if($tech['is_cat_match'])
                                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-indigo-50 text-indigo-700 border border-indigo-200">
                                                                ✨ Cocok Kategori
                                                            </span>
                                                        @endif

                                                        <!-- Urgent Tickets Warning -->
                                                        @if($tech['urgent_count'] > 0)
                                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-rose-800 border border-rose-300 animate-pulse">
                                                                ⚠️ {{ $tech['urgent_count'] }} Tiket Urgent (&lt;2j)
                                                            </span>
                                                        @endif
                                                    </div>

                                                    <!-- Workload Progress Bar -->
                                                    <div class="mt-2.5">
                                                        <div class="flex items-center justify-between text-[11px] mb-1">
                                                            <span class="text-slate-500">Beban Kerja Aktif:</span>
                                                            <span class="font-extrabold {{ $loadTextCls }}">
                                                                {{ $tech['weighted_load'] }} / {{ $tech['max_load'] }} Poin ({{ $tech['load_percent'] }}%)
                                                            </span>
                                                        </div>
                                                        <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                                            <div class="{{ $loadColorCls }} h-1.5 rounded-full transition-all duration-300" style="width: {{ $tech['load_percent'] }}%"></div>
                                                        </div>
                                                    </div>

                                                    <!-- Expandable Active Tickets Accordion -->
                                                    @if(!empty($tech['active_tickets']))
                                                        <div class="mt-2 pt-2 border-t border-slate-100">
                                                            <button type="button" onclick="document.getElementById('tech-tasks-{{ $t->id }}-{{ $tech['user_id'] }}').classList.toggle('hidden')"
                                                                class="text-[10px] font-bold text-blue-700 hover:text-blue-900 flex items-center gap-1 cursor-pointer">
                                                                <span>📋 Lihat {{ count($tech['active_tickets']) }} Tiket Aktif Sedang Dikerjakan</span> ▾
                                                            </button>
                                                            <div id="tech-tasks-{{ $t->id }}-{{ $tech['user_id'] }}" class="hidden mt-1.5 space-y-1 pl-2 border-l-2 border-blue-200">
                                                                @foreach($tech['active_tickets'] as $at)
                                                                    <div class="text-[10px] text-slate-600 flex items-center justify-between gap-1">
                                                                        <div class="flex items-center gap-1 truncate max-w-[200px]">
                                                                            <span class="font-mono font-bold">{{ $at['kode_tiket'] }}</span>
                                                                            <span class="truncate">({{ $at['judul'] }})</span>
                                                                        </div>
                                                                        <span class="font-bold shrink-0 {{ $at['mins_left'] < 120 ? 'text-rose-600' : 'text-slate-500' }}">
                                                                            ⏳ {{ $at['sisa_teks'] }}
                                                                        </span>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>

                                        <!-- Warning & Textarea Alasan Override jika Koordinator Overcapacity -->
                                        <div id="override-wrapper-{{ $t->id }}" class="hidden p-3.5 bg-amber-50 border border-amber-300 rounded-xl space-y-2 animate-in fade-in">
                                            <div class="flex items-start gap-2">
                                                <span class="text-lg">⚠️</span>
                                                <div>
                                                    <h5 class="text-xs font-bold text-amber-950">Konfirmasi Batas Maksimal Beban Kerja</h5>
                                                    <p class="text-[11px] text-amber-900 leading-relaxed">
                                                        Teknisi yang Anda pilih telah mencapai atau melebihi batas maksimal kapasitas beban kerja aktif. Penugasan tetap dapat dilakukan dengan mencantumkan alasan override kedinasan (tercatat di audit log).
                                                    </p>
                                                </div>
                                            </div>
                                            <div>
                                                <label for="override-input-{{ $t->id }}" class="block text-[11px] font-bold text-amber-950 mb-1">
                                                    Alasan Override Penugasan (Wajib diisi jika beban penuh) *
                                                </label>
                                                <textarea name="override_reason" id="override-input-{{ $t->id }}" rows="2"
                                                    placeholder="Contoh: Sangat mendesak, keahlian spesifik hanya dikuasai teknisi ini, instruksi pimpinan..."
                                                    class="w-full text-xs rounded-xl border border-amber-300 p-2 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-500"></textarea>
                                            </div>
                                        </div>
                                    @endif

                                    <button type="submit" class="w-full py-2.5 px-4 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                                        <span>💾 Simpan &amp; Disposisi Tim Teknisi</span>
                                    </button>
                                </form>

                                <!-- 2. Form Tandai Selesai (Mark Resolved) -->
                                <form action="{{ route('admin.bidang.tiket.markResolved', $t->id) }}" method="POST" enctype="multipart/form-data" class="bg-emerald-50/40 p-4 sm:p-5 rounded-2xl border border-emerald-100/90 space-y-3">
                                    @csrf
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-emerald-900 uppercase tracking-wider flex items-center gap-1.5">
                                            <span>✅</span> Penyelesaian Kendala (Resolved)
                                        </span>
                                    </div>

                                    <!-- Textarea Catatan Resolusi (Wajib) -->
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-700 mb-1">
                                            Catatan Hasil Perbaikan / Resolusi (Wajib) *
                                        </label>
                                        <textarea name="catatan_resolusi" rows="2" required placeholder="Jelaskan tindakan teknis yang telah diselesaikan untuk pelapor..."
                                            class="w-full text-xs rounded-xl border border-slate-300 p-2.5 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition leading-relaxed"></textarea>
                                    </div>

                                    <!-- Upload Bukti Resolusi (OPSIONAL) -->
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-700 mb-1">
                                            Upload Berkas Bukti Penyelesaian (Opsional, JPG/PNG/PDF Max 5MB)
                                        </label>
                                        <input type="file" name="bukti_resolusi" id="bukti-file-{{ $t->id }}"
                                            class="w-full text-xs rounded-xl border border-slate-300 p-2 bg-white file:mr-3 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-100 file:text-emerald-800 hover:file:bg-emerald-200 cursor-pointer">
                                    </div>

                                    <!-- Checkbox Tandai Rahasia -->
                                     <!-- Proteksi Kerahasiaan Berkas Bukti Berdasarkan TLP -->
                                     @if(strtoupper($t->tlp_level ?? '') === 'RED')
                                         <div class="flex items-start gap-2 pt-1 p-2.5 bg-rose-50 border border-rose-200 rounded-xl text-rose-900 text-xs">
                                             <span class="text-sm">🔒</span>
                                             <div>
                                                 <span class="font-bold block">Bukti Otomatis Terkunci (TLP: RED)</span>
                                                 <span class="text-[11px] text-rose-800/90 leading-tight block">Klasifikasi Persandian & CSIRT. Berkas bukti penyelesaian mutlak dirahasiakan dan tertutup dari publik.</span>
                                             </div>
                                             <input type="hidden" name="is_confidential_evidence" value="1">
                                         </div>
                                     @elseif(strtoupper($t->tlp_level ?? '') === 'AMBER')
                                         <div class="flex items-center gap-2 pt-1 p-2 bg-amber-50/70 border border-amber-200/80 rounded-xl">
                                             <input type="checkbox" name="is_confidential_evidence" id="confidential-{{ $t->id }}" value="1" checked
                                                 class="w-4 h-4 text-amber-600 rounded border-amber-300 focus:ring-amber-500 cursor-pointer">
                                             <label for="confidential-{{ $t->id }}" class="text-xs font-semibold text-amber-900 cursor-pointer select-none flex items-center gap-1">
                                                 <span>🔒 Tandai Rahasia (Direkomendasikan untuk TLP: AMBER)</span>
                                             </label>
                                         </div>
                                     @else
                                         <div class="flex items-center gap-2 pt-1">
                                             <input type="checkbox" name="is_confidential_evidence" id="confidential-{{ $t->id }}" value="1"
                                                 class="w-4 h-4 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500 cursor-pointer">
                                             <label for="confidential-{{ $t->id }}" class="text-xs font-semibold text-slate-700 cursor-pointer select-none flex items-center gap-1">
                                                 <span>🔒 Tandai Rahasia (Hanya dapat dilihat internal pengelola)</span>
                                             </label>
                                         </div>
                                     @endif

                                    <!-- Tombol Tandai Selesai (Langsung Aktif) -->
                                    <button type="submit" id="btn-resolve-{{ $t->id }}"
                                        class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                                        <span>✅ Tandai Selesai (Resolved)</span>
                                    </button>
                                </form>
                            </div>
                        @else
                            <!-- Tampilan Tiket Selesai / Arsip -->
                            <div class="pt-4 border-t border-slate-200 bg-slate-50/60 p-4 rounded-xl text-xs space-y-2">
                                <div class="font-bold text-slate-800 flex items-center gap-1.5">
                                    <span>🎉</span> Tiket telah diselesaikan / diarsipkan
                                </div>
                                @if($t->catatan_resolusi)
                                    <p class="text-slate-600">Catatan: {{ $t->catatan_resolusi }}</p>
                                @endif
                                @if($t->bukti_resolusi)
                                    <div>
                                        <a href="{{ route('admin.tiket.download', ['kode' => $t->kode_tiket, 'type' => 'bukti']) }}" target="_blank" class="text-emerald-700 font-bold hover:underline">
                                            📎 Unduh Berkas Bukti Penyelesaian &rarr;
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach

            <div class="p-4">
                {{ $tickets->links() }}
            </div>
        </div>
    @endif
</div>

<!-- ================================================================= -->
<!-- MODAL PENGEMBALIAN TIKET (SALAH DISPOSISI / SALAH KAMAR) -->
<!-- ================================================================= -->
<div id="return-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full shadow-2xl border border-slate-200 overflow-hidden transform transition-all my-8 animate-in fade-in zoom-in duration-150">
        <div class="bg-amber-500 text-slate-950 p-5 flex items-start justify-between border-b border-amber-600">
            <div>
                <span class="font-mono text-xs font-black bg-slate-950/15 text-slate-950 px-2.5 py-0.5 rounded">
                    ↩️ Pengembalian Disposisi Tiket
                </span>
                <h3 id="return-modal-kode" class="text-base font-extrabold text-slate-950 mt-1">TIK-XXXX</h3>
                <p id="return-modal-judul" class="text-xs text-slate-900/80 mt-0.5 line-clamp-1"></p>
            </div>
            <button type="button" onclick="closeReturnModal()" class="text-slate-800 hover:text-slate-950 p-1 rounded-lg hover:bg-black/10 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <form id="return-form" method="POST" action="" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1">
                    Alasan Pengembalian / Saran Bidang Tujuan (Wajib diisi, min 10 karakter) *
                </label>
                <textarea name="alasan" rows="4" required minlength="10" placeholder="Contoh: Permasalahan ini bukan terkait jaringan FO melainkan bug pada aplikasi SIMPEG. Mohon dialihkan ke Bidang Pemerintahan Digital (PEMDI)..."
                    class="w-full text-xs rounded-xl border border-slate-300 p-3 focus:outline-none focus:ring-2 focus:ring-amber-500 leading-relaxed"></textarea>
                <p class="text-[11px] text-slate-400 mt-1">Tiket akan dikembalikan ke antrean verifikasi Admin Pusat dengan status Pending Verification.</p>
            </div>
            <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeReturnModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 bg-amber-500 hover:bg-amber-600 text-slate-950 text-xs font-extrabold rounded-xl shadow-xs transition cursor-pointer">
                    Kirim Pengembalian ke Pusat
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ================================================================= -->
<!-- MODAL PELEPASAN TIKET ANAK (PECAH DARI INDUK MENJADI MANDIRI) -->
<!-- ================================================================= -->
<div id="unlink-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full shadow-2xl border border-slate-200 overflow-hidden transform transition-all my-8 animate-in fade-in zoom-in duration-150">
        <div class="bg-rose-600 text-white p-5 flex items-start justify-between border-b border-rose-700">
            <div>
                <span class="font-mono text-xs font-black bg-white/20 text-white px-2.5 py-0.5 rounded">
                    ✂️ Pecah / Lepas dari Induk Tiket
                </span>
                <h3 id="unlink-modal-kode" class="text-base font-extrabold text-white mt-1">TIK-XXXX</h3>
                <p id="unlink-modal-info" class="text-xs text-rose-100 mt-0.5"></p>
            </div>
            <button type="button" onclick="closeUnlinkModal()" class="text-white/80 hover:text-white p-1 rounded-lg hover:bg-black/10 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <form id="unlink-form" method="POST" action="" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1">
                    Alasan Pelepasan Tiket dari Induk (Wajib diisi) *
                </label>
                <textarea name="alasan" rows="3" required minlength="3" placeholder="Contoh: Kendala di OPD ini ternyata kasus berbeda (gangguan PC lokal, bukan server pusat). Dipisahkan agar penanganannya mandiri."
                    class="w-full text-xs rounded-xl border border-slate-300 p-3 focus:outline-none focus:ring-2 focus:ring-rose-500 leading-relaxed"></textarea>
                <p class="text-[11px] text-slate-400 mt-1">
                    Tiket ini akan berdiri sendiri sebagai tiket mandiri (tidak lagi otomatis selesai saat Induk diselesaikan).
                </p>
            </div>
            <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeUnlinkModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-extrabold rounded-xl shadow-xs transition cursor-pointer">
                    Ya, Lepaskan & Pecah Jadi Mandiri
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    window.openReturnModal = function(id, kode, judul) {
        document.getElementById('return-modal-kode').textContent = kode;
        document.getElementById('return-modal-judul').textContent = judul;
        document.getElementById('return-form').action = `/bidang/tiket/${id}/return`;
        document.getElementById('return-modal').classList.remove('hidden');
    };

    window.closeReturnModal = function() {
        document.getElementById('return-modal').classList.add('hidden');
    };

    window.openUnlinkModal = function(id, kode, parentKode) {
        document.getElementById('unlink-modal-kode').textContent = kode;
        document.getElementById('unlink-modal-info').textContent = 'Melepaskan tiket ini dari Induk Tiket #' + parentKode;
        document.getElementById('unlink-form').action = `/bidang/tiket/${id}/unlink`;
        document.getElementById('unlink-modal').classList.remove('hidden');
    };

    window.closeUnlinkModal = function() {
        document.getElementById('unlink-modal').classList.add('hidden');
    };

    window.handleLeadChange = function(ticketId, techUserId, isOver) {
        const overrideWrapper = document.getElementById('override-wrapper-' + ticketId);
        const overrideInput = document.getElementById('override-input-' + ticketId);

        // Update status visual: teknisi yang dipilih jadi Koordinator Utama, lainnya bisa dipilih jadi Pendamping
        const allWrappers = document.querySelectorAll(`[id^="mem-wrapper-${ticketId}-"]`);
        allWrappers.forEach(wrapper => {
            const userId = wrapper.id.replace(`mem-wrapper-${ticketId}-`, '');
            const badge = document.getElementById(`mem-badge-${ticketId}-${userId}`);
            const inputBox = document.getElementById(`mem-input-box-${ticketId}-${userId}`);
            const cb = document.getElementById(`mem-${ticketId}-${userId}`);

            if (userId == techUserId) {
                if (badge) {
                    badge.classList.remove('hidden');
                    badge.classList.add('inline-flex');
                }
                if (inputBox) {
                    inputBox.classList.add('hidden');
                    inputBox.classList.remove('flex');
                }
                if (cb) {
                    cb.checked = false;
                    cb.disabled = true;
                }
            } else {
                if (badge) {
                    badge.classList.add('hidden');
                    badge.classList.remove('inline-flex');
                }
                if (inputBox) {
                    inputBox.classList.remove('hidden');
                    inputBox.classList.add('flex');
                }
                if (cb) {
                    const parentCard = cb.closest('.p-3');
                    if (!parentCard || !parentCard.classList.contains('opacity-60')) {
                        cb.disabled = false;
                    }
                }
            }
        });

        // Update highlight border pada card teknisi
        const radioInputs = document.querySelectorAll(`input[name="lead_user_id"][id^="lead-${ticketId}-"]`);
        radioInputs.forEach(radio => {
            const card = radio.closest('.p-3');
            if (card) {
                if (radio.value == techUserId) {
                    card.classList.add('border-amber-400', 'bg-amber-50/20', 'ring-1', 'ring-amber-300');
                    card.classList.remove('border-slate-200');
                } else {
                    card.classList.remove('border-amber-400', 'bg-amber-50/20', 'ring-1', 'ring-amber-300');
                    card.classList.add('border-slate-200');
                }
            }
        });

        if (overrideWrapper && overrideInput) {
            if (isOver) {
                overrideWrapper.classList.remove('hidden');
                overrideInput.required = true;
                setTimeout(() => overrideInput.focus(), 100);
            } else {
                overrideWrapper.classList.add('hidden');
                overrideInput.required = false;
            }
        }
    };

    window.toggleHistory = function(id) {
        const el = document.getElementById(id);
        const icon = document.getElementById('icon-' + id);
        if (el) {
            if (el.classList.contains('hidden')) {
                el.classList.remove('hidden');
                if (icon) {
                    icon.innerHTML = '<span>Tutup Kronologi</span><svg class="w-3.5 h-3.5 transform rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>';
                }
            } else {
                el.classList.add('hidden');
                if (icon) {
                    icon.innerHTML = '<span>Lihat Kronologi</span><svg class="w-3.5 h-3.5 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>';
                }
            }
        }
    };

    window.addTeamMemberRow = function(ticketId, initialValue = '') {
        const container = document.getElementById('team-members-container-' + ticketId);
        if (!container) return;

        const row = document.createElement('div');
        row.className = 'team-member-row flex items-center gap-2 animate-in fade-in';
        row.innerHTML = `
            <div class="relative flex-1">
                <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-xs text-slate-400">👤</span>
                <input type="text" name="anggota_tim[]" value="${initialValue}" list="datalist-tech-${ticketId}" placeholder="Nama rekan teknisi / personil tim..."
                    class="w-full pl-8 pr-3 py-1.5 text-xs rounded-xl border border-slate-300 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="button" onclick="this.closest('.team-member-row').remove()" class="p-1 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition cursor-pointer" title="Hapus">✕</button>
        `;
        container.appendChild(row);
    };
</script>
@endsection
