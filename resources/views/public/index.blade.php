@extends('layouts.app', [
    'title' => 'Portal Layanan & Pengaduan TIK — DKIS Kota Cirebon',
    'hideNavbar' => true
])

@section('content')
<div class="bg-slate-50 min-h-screen">
    <!-- ══════════════════════════════════════════════════════════════════════
         A. HERO HEADER (Dark Navy Gradient)
         ══════════════════════════════════════════════════════════════════════ -->
    <header class="relative bg-gradient-to-br from-slate-950 via-[#0f274a] to-[#1e3a6a] text-white pt-8 pb-24 overflow-hidden">
        <!-- Ambient Radial Highlight Background -->
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute top-1/2 -left-40 w-96 h-96 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute inset-0 bg-[radial-gradient(#38bdf8_1px,transparent_1px)] [background-size:24px_24px] opacity-10 pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <!-- Top Navbar Bar inside Hero -->
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4 pb-10 border-b border-white/10">
                <!-- DKIS Logo -->
                <div class="flex items-center space-x-3.5">
                    <img src="{{ asset('images/logo-dkis.png') }}" alt="Logo DKIS" class="h-11 w-auto object-contain">
                    <div>
                        <div class="flex items-center">
                            <span class="text-2xl font-extrabold tracking-tight text-white">DKIS</span>
                            <span class="inline-block w-2.5 h-2.5 rounded-full bg-rose-500 ml-1 mb-0.5 animate-pulse"></span>
                        </div>
                        <p class="text-xs text-slate-300 font-medium tracking-wide">
                            Dinas Komunikasi, Informatika dan Statistik Kota Cirebon
                        </p>
                    </div>
                </div>

                <!-- Top Navbar Right Actions -->
                <div class="flex items-center gap-2 sm:gap-3 flex-wrap justify-end">
                    <!-- Tombol Lacak / Buat Pengaduan di Pojok Kanan Atas -->
                    <button type="button" id="nav-btn-toggle" onclick="togglePortalTab()"
                        class="inline-flex items-center gap-2 px-3.5 sm:px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold text-blue-200 bg-blue-950/60 hover:bg-blue-900/80 border border-blue-400/30 shadow-sm transition backdrop-blur-sm hover:border-blue-400">
                        <span id="nav-btn-icon">{{ ($activeTab ?? 'buat') === 'lacak' ? '✍️' : '🔍' }}</span>
                        <span id="nav-btn-label">{{ ($activeTab ?? 'buat') === 'lacak' ? 'Buat Pengaduan Baru' : 'Lacak Status Tiket' }}</span>
                    </button>
                </div>
            </div>

            <!-- Hero Headline & Description (Bersih tanpa tombol/tab di dekat form) -->
            <div class="text-center max-w-3xl mx-auto mt-10 sm:mt-12 space-y-4">
                <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight text-white leading-tight">
                    Layanan dan Laporan Pengaduan DKIS
                </h1>

                <p class="text-sm sm:text-base text-slate-300 leading-relaxed">
                    Sistem penanganan terpadu gangguan aplikasi pemerintahan, infrastruktur jaringan intra Pemkot, persandian, dan keamanan siber.
                </p>
            </div>
        </div>

        <!-- Wave SVG Separator (Smooth curve to white/slate content below) -->
        <div class="absolute bottom-0 left-0 right-0 leading-none overflow-hidden pointer-events-none">
            <svg class="relative block w-full h-12 sm:h-16 text-slate-50" viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path d="M0,0 C150,90 350,-40 500,45 C650,130 900,10 1200,40 L1200,120 L0,120 Z" fill="currentColor"></path>
            </svg>
        </div>
    </header>

    <!-- ══════════════════════════════════════════════════════════════════════
         MAIN CONTENT CONTAINER (Floating White Cards)
         ══════════════════════════════════════════════════════════════════════ -->
    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pb-16">

        <!-- Flash Session Messages -->
        @if(session('success'))
            <div id="flash-success-alert" class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 flex items-start justify-between gap-3 shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center flex-shrink-0 font-bold text-sm mt-0.5">✓</div>
                    <div class="text-sm font-medium leading-relaxed">{{ session('success') }}</div>
                </div>
                <button type="button" onclick="document.getElementById('flash-success-alert').remove()" class="text-emerald-700 hover:text-emerald-950 font-bold text-sm p-1 cursor-pointer" aria-label="Tutup notifikasi">✕</button>
            </div>
        @endif

        @if(session('error'))
            <div id="flash-error-alert" class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 flex items-start justify-between gap-3 shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="w-7 h-7 rounded-full bg-rose-100 text-rose-700 flex items-center justify-center flex-shrink-0 font-bold text-sm mt-0.5">✕</div>
                    <div class="text-sm font-medium leading-relaxed">{{ session('error') }}</div>
                </div>
                <button type="button" onclick="document.getElementById('flash-error-alert').remove()" class="text-rose-700 hover:text-rose-950 font-bold text-sm p-1 cursor-pointer" aria-label="Tutup notifikasi">✕</button>
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 p-5 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 shadow-sm">
                <div class="flex items-center gap-2 font-bold text-sm mb-2 text-amber-800">
                    <span>⚠️</span>
                    <span>Mohon lengkapi isian formulir dengan benar:</span>
                </div>
                <ul class="list-disc list-inside text-xs sm:text-sm space-y-1 text-amber-900">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- ══════════════════════════════════════════════════════════════════
             B. TAB "BUAT LAPORAN BARU" (Card Putih dengan Shadow)
             ══════════════════════════════════════════════════════════════════ -->
        <div id="section-buat-laporan" class="{{ ($activeTab ?? 'buat') === 'buat' ? '' : 'hidden' }}">
            <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/70 border border-slate-200/80 overflow-hidden">
                <!-- Form Header -->
                <div class="px-6 sm:px-10 py-6 bg-slate-50/70 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg sm:text-xl font-bold text-slate-900 flex items-center gap-2">
                            <span>📝</span>
                            <span>Formulir Permohonan & Pengaduan Layanan DKIS</span>
                        </h2>
                        <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                            Bidang bertanda bintang merah (<span class="text-rose-500 font-bold">*</span>) wajib diisi.
                        </p>
                    </div>
                </div>

                <!-- Form Body -->
                <form action="{{ route('public.ticket.store') }}" method="POST" enctype="multipart/form-data" class="p-6 sm:p-10 space-y-8" onsubmit="if (!validatePublicTicketForm(this)) return false; const btn = this.querySelector('button[type=submit]'); if (btn) { btn.disabled = true; btn.classList.add('opacity-75', 'cursor-not-allowed'); btn.innerHTML = '<span>⏳</span><span>Sedang Mengirim Tiket...</span>'; }">
                    @csrf

                    <!-- 1. Identitas ASN Pelapor -->
                    <div>
                        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-blue-600 text-white text-xs flex items-center justify-center font-bold">1</span>
                            Identitas Pegawai Pelapor (ASN)
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 sm:gap-6">
                            <!-- NIP / NIPK (Validasi Ketat 18 Digit BKN) -->
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label for="nip_pelapor" class="block text-xs sm:text-sm font-semibold text-slate-700">
                                        NIP / NIPK Pegawai <span class="text-rose-500 font-bold">*</span>
                                    </label>
                                    <span class="text-[11px] text-slate-400 font-medium">Wajib 18 Digit Angka</span>
                                </div>
                                <input type="text" id="nip_pelapor" name="nip_pelapor" value="{{ old('nip_pelapor') }}" 
                                    inputmode="numeric" minlength="18" maxlength="18" pattern="[0-9]{18}" 
                                    placeholder="Contoh: 198503122010011003 (18 Digit Angka)" required
                                    oninput="handleNipInput(this)"
                                    class="w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-600 focus:ring-blue-600 text-sm py-2.5 px-3.5 font-mono">
                            </div>

                            <!-- Nama Lengkap -->
                            <div>
                                <label for="nama_pelapor" class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">
                                    Nama Lengkap <span class="text-rose-500 font-bold">*</span>
                                </label>
                                <input type="text" id="nama_pelapor" name="nama_pelapor" value="{{ old('nama_pelapor') }}" placeholder="Contoh: Drs. Agus Prasetyo, M.Si." required
                                    class="w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-600 focus:ring-blue-600 text-sm py-2.5 px-3.5">
                            </div>

                            <!-- SKPD / Instansi (Searchable Combobox) -->
                            <div class="relative" id="skpd_search_container">
                                <div class="flex items-center justify-between mb-1.5">
                                    <label for="skpd_search_input" class="block text-xs sm:text-sm font-semibold text-slate-700">
                                        SKPD / Perangkat Daerah <span class="text-rose-500 font-bold">*</span>
                                    </label>
                                    <span class="text-[11px] text-blue-600 font-medium">Ketik atau klik untuk memilih</span>
                                </div>

                                <!-- Hidden input untuk form submit -->
                                <input type="hidden" id="skpd" name="skpd" value="{{ old('skpd') }}" required>

                                <!-- Kotak Input SKPD dengan Ikon Cari, Clear, & Chevron Dropdown -->
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </div>
                                    <input type="text" id="skpd_search_input" autocomplete="off"
                                        placeholder="Pilih dari daftar atau ketik nama instansi Anda..."
                                        value="{{ old('skpd') }}"
                                        class="w-full rounded-xl border border-slate-300 shadow-xs focus:border-blue-600 focus:ring-2 focus:ring-blue-100 text-sm py-2.5 pl-10 pr-16 bg-white transition cursor-pointer"
                                        onclick="openSkpdDropdown()"
                                        onfocus="openSkpdDropdown()"
                                        oninput="handleSkpdSearchInput(this.value)">
                                    
                                    <!-- Tombol Aksi Kanan: Clear & Chevron Toggle -->
                                    <div class="absolute inset-y-0 right-0 pr-2 flex items-center gap-0.5">
                                        <button type="button" id="skpd_clear_btn" onclick="clearSkpdSelection()"
                                            class="p-1.5 text-slate-400 hover:text-slate-600 cursor-pointer {{ old('skpd') ? '' : 'hidden' }}"
                                            title="Hapus pilihan">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                        <button type="button" id="skpd_chevron_btn" onclick="toggleSkpdDropdown()"
                                            class="p-1.5 text-slate-400 hover:text-blue-600 cursor-pointer transition transform"
                                            title="Buka / tutup daftar instansi">
                                            <svg id="skpd_chevron_icon" class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Panel Dropdown SKPD: Ringkas & Dapat Di-Scroll -->
                                <div id="skpd_dropdown_list" class="hidden absolute z-30 left-0 right-0 mt-1.5 bg-white border border-slate-200 rounded-2xl shadow-xl overflow-hidden text-xs sm:text-sm animate-in fade-in zoom-in-95 duration-100">
                                    <div class="px-3.5 py-1.5 bg-slate-50 border-b border-slate-100 flex items-center justify-between text-[11px] font-bold text-slate-400">
                                        <span>📜 PILIHAN TERSEDIA (GULIR KE BAWAH)</span>
                                        <span class="text-blue-600 font-medium">Bisa scroll & ketik</span>
                                    </div>
                                    @php
                                        $daftarOpd = (isset($masterOpdList) && $masterOpdList->isNotEmpty())
                                            ? $masterOpdList->pluck('nama_opd')->toArray()
                                            : [
                                                'Sekretariat Daerah Kota Cirebon',
                                                'Sekretariat DPRD Kota Cirebon',
                                                'Inspektorat Daerah Kota Cirebon',
                                                'Dinas Komunikasi, Informatika dan Statistik (DKIS)',
                                                'Badan Kepegawaian dan Pengembangan SDM (BKPSDM)',
                                                'Badan Pengelola Keuangan dan Pendapatan Daerah (BPKPD)',
                                                'Badan Perencanaan Pembangunan, Penelitian dan Pengembangan Daerah (Bappelitbangda)',
                                                'Dinas Pendidikan',
                                                'Dinas Kesehatan',
                                                'Dinas Pekerjaan Umum dan Tata Ruang (DPUTR)',
                                                'Dinas Perumahan Rakyat dan Kawasan Permukiman (DPRKP)',
                                                'Satuan Polisi Pamong Praja (Satpol PP)',
                                                'Dinas Sosial',
                                                'Dinas Tenaga Kerja',
                                                'Dinas Kependudukan dan Pencatatan Sipil (Disdukcapil)',
                                                'Dinas Perhubungan',
                                                'Dinas Koperasi, Usaha Kecil, Menengah, Perdagangan dan Perindustrian (DKUKMPP)',
                                                'Dinas Pemuda dan Olahraga (Dispora)',
                                                'Badan Penanggulangan Bencana Daerah (BPBD)',
                                                'RSUD Gunung Jati Kota Cirebon',
                                            ];
                                    @endphp
                                    <div class="p-1 space-y-0.5 max-h-48 sm:max-h-52 overflow-y-auto overscroll-contain" id="skpd_items_container" style="scrollbar-width: thin;">
                                        <!-- Opsi Custom Teks Ketikan (Muncul saat mengetik nama yang belum ada) -->
                                        <div id="skpd_custom_option" class="hidden px-3.5 py-2 rounded-xl bg-blue-50/90 hover:bg-blue-100 text-blue-900 font-semibold cursor-pointer border border-blue-200/80 transition" onclick="selectSkpdCustom()">
                                            <div class="flex items-center gap-2">
                                                <span>✍️</span>
                                                <span>Gunakan: <strong id="skpd_custom_label" class="underline"></strong></span>
                                            </div>
                                        </div>

                                        @foreach($daftarOpd as $opd)
                                            <div class="skpd-option px-3.5 py-2.5 rounded-xl hover:bg-blue-50 cursor-pointer flex items-center justify-between text-slate-700 hover:text-blue-900 transition {{ old('skpd') === $opd ? 'bg-blue-50 font-bold text-blue-900' : '' }}"
                                                data-value="{{ $opd }}" onclick="selectSkpd('{{ addslashes($opd) }}')">
                                                <span>{{ $opd }}</span>
                                                @if(old('skpd') === $opd)
                                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                    <div id="skpd_empty_state" class="hidden p-3.5 text-center text-xs text-slate-500 bg-slate-50 border-t border-slate-100">
                                        <span>Tidak menemukan nama instansi dalam daftar?</span>
                                        <button type="button" onclick="selectSkpdCustom()" class="block w-full mt-1 text-blue-600 hover:underline font-bold cursor-pointer">
                                            ✍️ Gunakan nama yang Anda ketik sebagai instansi baru
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Nomor Kontak / Telepon Pelapor -->
                            <div>
                                <label for="no_whatsapp" class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">
                                    Nomor Kontak / Telepon Pelapor <span class="text-rose-500 font-bold">*</span>
                                </label>
                                <input type="tel" id="no_whatsapp" name="no_whatsapp" value="{{ old('no_whatsapp') }}" placeholder="08xxxxxxxxxx" required
                                    class="w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-600 focus:ring-blue-600 text-sm py-2.5 px-3.5">
                            </div>
                        </div>
                    </div>

                    <!-- 2. Rincian Permasalahan & Layanan -->
                    <div>
                        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-blue-600 text-white text-xs flex items-center justify-center font-bold">2</span>
                            Rincian Permasalahan / Kebutuhan Layanan
                        </h3>

                        <div class="space-y-5">
                            <!-- Kategori Masalah / Layanan DKIS (Searchable Combobox Berkelompok) -->
                            <div class="relative" id="cat_search_container">
                                <div class="flex items-center justify-between mb-1.5">
                                    <label for="cat_search_input" class="block text-xs sm:text-sm font-semibold text-slate-700">
                                        Kategori Masalah / Layanan DKIS <span class="text-rose-500 font-bold">*</span>
                                    </label>
                                    <span class="text-[11px] text-blue-600 font-medium">Ketik atau klik untuk memilih</span>
                                </div>

                                <!-- Hidden input untuk form submit -->
                                <input type="hidden" id="category_id" name="category_id" value="{{ old('category_id') }}" required>
                                <!-- Hidden input untuk menyimpan nama kategori kustom jika mengetik sendiri -->
                                <input type="hidden" id="custom_category_name" name="custom_category_name" value="">

                                @php
                                    $selectedCatName = '';
                                    if (old('category_id')) {
                                        $matchedCat = $categories->firstWhere('id', old('category_id'));
                                        $selectedCatName = $matchedCat?->nama_kategori ?? '';
                                    }

                                    $fallbackCat = $categories->first(function($c) {
                                        $n = strtolower($c->nama_kategori);
                                        return str_contains($n, 'lainnya') || str_contains($n, 'belum tahu') || str_contains($n, 'konsultasi');
                                    }) ?? $categories->first();
                                    $fallbackCatId = $fallbackCat?->id ?? 1;
                                @endphp

                                <!-- Kotak Input Kategori dengan Ikon Cari, Clear, & Chevron Dropdown -->
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </div>
                                    <input type="text" id="cat_search_input" autocomplete="off"
                                        placeholder="Pilih dari daftar atau ketik jenis kendala Anda..."
                                        value="{{ $selectedCatName }}"
                                        class="w-full rounded-xl border border-slate-300 shadow-xs focus:border-blue-600 focus:ring-2 focus:ring-blue-100 text-sm py-2.5 pl-10 pr-16 bg-white transition cursor-pointer"
                                        onclick="openCatDropdown()"
                                        onfocus="openCatDropdown()"
                                        oninput="handleCatSearchInput(this.value)">
                                    
                                    <!-- Tombol Aksi Kanan: Clear & Chevron Toggle -->
                                    <div class="absolute inset-y-0 right-0 pr-2 flex items-center gap-0.5">
                                        <button type="button" id="cat_clear_btn" onclick="clearCatSelection()"
                                            class="p-1.5 text-slate-400 hover:text-slate-600 cursor-pointer {{ $selectedCatName ? '' : 'hidden' }}"
                                            title="Hapus pilihan">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                        <button type="button" id="cat_chevron_btn" onclick="toggleCatDropdown()"
                                            class="p-1.5 text-slate-400 hover:text-blue-600 cursor-pointer transition transform"
                                            title="Buka / tutup daftar kategori">
                                            <svg id="cat_chevron_icon" class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Panel Dropdown Kategori: Ringkas, Tanpa Judul Bidang & Dapat Di-Scroll -->
                                <div id="cat_dropdown_list" class="hidden absolute z-30 left-0 right-0 mt-1.5 bg-white border border-slate-200 rounded-2xl shadow-xl overflow-hidden text-xs sm:text-sm animate-in fade-in zoom-in-95 duration-100">
                                    <div class="px-3.5 py-1.5 bg-slate-50 border-b border-slate-100 flex items-center justify-between text-[11px] font-bold text-slate-400">
                                        <span>📜 DAFTAR KATEGORI (GULIR KE BAWAH)</span>
                                        <span class="text-blue-600 font-medium">Bisa scroll & ketik</span>
                                    </div>
                                    <div class="p-1 space-y-0.5 max-h-48 sm:max-h-52 overflow-y-auto overscroll-contain" id="cat_items_container" style="scrollbar-width: thin;">
                                        <!-- Opsi Kategori Kustom (Muncul saat mengetik kategori yang belum ada) -->
                                        <div id="cat_custom_option" class="hidden px-3.5 py-2 rounded-xl bg-blue-50/90 hover:bg-blue-100 text-blue-900 font-semibold cursor-pointer border border-blue-200/80 transition" onclick="selectCatCustom('{{ $fallbackCatId }}')">
                                            <div class="flex items-center gap-2">
                                                <span>✍️</span>
                                                <span>Gunakan: <strong id="cat_custom_label" class="underline"></strong> (Kategori Baru)</span>
                                            </div>
                                        </div>

                                        @foreach($categories as $cat)
                                            <div class="cat-option px-3.5 py-2.5 rounded-xl hover:bg-blue-50 cursor-pointer flex items-center justify-between text-slate-700 hover:text-blue-900 transition {{ old('category_id') == $cat->id ? 'bg-blue-50 font-bold text-blue-900' : '' }}"
                                                data-id="{{ $cat->id }}" data-name="{{ $cat->nama_kategori }}"
                                                onclick="selectCat('{{ $cat->id }}', '{{ addslashes($cat->nama_kategori) }}')">
                                                <span>{{ $cat->nama_kategori }}</span>
                                                @if(old('category_id') == $cat->id)
                                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                    <div id="cat_empty_state" class="hidden p-3.5 text-center text-xs text-slate-500 bg-slate-50 border-t border-slate-100">
                                        <span>Tidak menemukan kategori yang dicari?</span>
                                        <button type="button" onclick="selectCatCustom('{{ $fallbackCatId }}')" class="block w-full mt-1 text-blue-600 hover:underline font-bold cursor-pointer">
                                            ✍️ Gunakan nama yang Anda ketik sebagai kategori baru
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Judul / Pokok Masalah -->
                            <div>
                                <label for="judul" class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">
                                    Judul / Pokok Masalah <span class="text-rose-500 font-bold">*</span>
                                </label>
                                <input type="text" id="judul" name="judul" value="{{ old('judul') }}" placeholder="Tuliskan ringkasan kendala (misal: Gangguan Jaringan FO Ruang Rapat Bappelitbangda)" required
                                    class="w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-600 focus:ring-blue-600 text-sm py-2.5 px-3.5">
                            </div>

                            <!-- Deskripsi Lengkap -->
                            <div>
                                <label for="deskripsi" class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">
                                    Deskripsi Lengkap & Lokasi Fisik <span class="text-rose-500 font-bold">*</span>
                                </label>
                                <textarea id="deskripsi" name="deskripsi" rows="5" minlength="20" required
                                    placeholder="Jelaskan secara rinci kendala yang dialami, letak lantai/ruangan, waktu mulai terjadinya gangguan, serta nomor alternatif yang dapat dihubungi teknisi lapangan..."
                                    class="w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-600 focus:ring-blue-600 text-sm p-3.5">{{ old('deskripsi') }}</textarea>
                                <p class="text-[11px] text-slate-400 mt-1">Minimal 20 karakter agar teknisi memahami ruang lingkup penanganan.</p>
                            </div>

                            <!-- Upload Bukti (Multi-Upload, JPG/PNG/PDF, Max 5MB per file, Total Max 10MB, Max 5 file) -->
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label for="lampiran_pelapor" class="block text-xs sm:text-sm font-semibold text-slate-700">
                                        Upload Bukti / Berkas Pendukung (Opsional, Maks 5 File)
                                    </label>
                                    <span class="text-[11px] text-slate-400 font-medium">Maks 5 MB per berkas (Total Maks 10 MB)</span>
                                </div>
                                <div class="mt-1 flex flex-col items-center justify-center px-6 pt-5 pb-6 border-2 border-slate-300 border-dashed rounded-2xl hover:border-blue-500 transition cursor-pointer bg-slate-50/50" onclick="document.getElementById('lampiran_pelapor').click()">
                                    <div class="space-y-1 text-center">
                                        <svg class="mx-auto h-10 w-10 text-slate-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <div class="flex text-xs sm:text-sm text-slate-600 justify-center">
                                            <label for="lampiran_pelapor" class="relative cursor-pointer rounded-md font-bold text-blue-600 hover:text-blue-500 focus-within:outline-none" onclick="event.stopPropagation()">
                                                <span>Pilih Berkas Lampiran</span>
                                                <input id="lampiran_pelapor" name="lampiran_pelapor[]" type="file" multiple accept=".jpg,.jpeg,.png,.pdf" class="sr-only" onchange="handleMultipleFiles(this)">
                                            </label>
                                            <p class="pl-1 text-slate-500">atau klik area ini</p>
                                        </div>
                                        <p class="text-[11px] text-slate-400">
                                            Format diperbolehkan: JPG, PNG, atau PDF (Maksimal 5 MB per berkas, akumulasi total seluruh file maks 10 MB, hingga 5 berkas)
                                        </p>
                                    </div>
                                </div>

                                <!-- Pesan Error Validasi Klien -->
                                <div id="file-error-alert" class="hidden mt-2 p-3 bg-rose-50 border border-rose-300 rounded-xl text-xs text-rose-800 flex items-center justify-between shadow-xs">
                                    <div class="flex items-center gap-1.5 font-medium">
                                        <span>⚠️</span>
                                        <span id="file-error-message"></span>
                                    </div>
                                    <button type="button" onclick="document.getElementById('file-error-alert').classList.add('hidden')" class="font-bold text-rose-600 hover:text-rose-900 ml-2">✕</button>
                                </div>

                                <!-- Chips / Pills Berkas Terpilih -->
                                <div id="file-list-preview" class="mt-3 flex flex-wrap gap-2"></div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Verifikasi Keamanan Pelapor (Google reCAPTCHA) -->
                    <div class="pt-3 pb-1 flex flex-col items-center justify-center">
                        <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
                        @error('g-recaptcha-response')
                            <div class="text-xs font-bold text-rose-600 flex items-center gap-1 mt-2">
                                <span>⚠️</span>
                                <span>{{ $message }}</span>
                            </div>
                        @enderror
                    </div>

                    <!-- Tombol Kirim Full Width -->
                    <div class="pt-2">
                        <button type="submit" id="btn-submit-ticket"
                            class="w-full py-4 px-6 rounded-2xl bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-700 hover:from-blue-700 hover:to-indigo-800 text-white font-extrabold text-base sm:text-lg shadow-xl shadow-blue-600/25 hover:shadow-blue-600/35 transition transform active:scale-[0.99] flex items-center justify-center gap-3">
                            <span>🚀</span>
                            <span>Kirim Tiket Layanan Sekarang</span>
                        </button>
                        <p class="text-center text-xs text-slate-400 mt-3">
                            Tiket akan diverifikasi oleh Admin Pusat DKIS dalam hitungan jam sesuai standar SLA pelayanan.
                    </div>
                </form>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════════════════
             C. TAB "LACAK STATUS TIKET"
             ══════════════════════════════════════════════════════════════════ -->
        <div id="section-lacak-status" class="{{ ($activeTab ?? 'buat') === 'lacak' ? '' : 'hidden' }} space-y-8">
            <!-- Tracking Search Form Card (On-Page, Form Validasi Kode Tiket + Nomor Kontak) -->
            <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/70 border border-slate-200/80 p-6 sm:p-8">
                <div class="max-w-3xl mx-auto space-y-6">
                    <div class="text-center space-y-2">
                        <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900">Lacak Status Pengaduan</h2>
                        <p class="text-xs sm:text-sm text-slate-500 max-w-xl mx-auto leading-relaxed">
                            Masukkan Kode Tiket pengaduan Anda dan Nomor Kontak / Telepon yang terdaftar untuk memantau perkembangan penanganan secara langsung dari tim DKIS.
                        </p>
                    </div>

                    <!-- Form 1: Lacak Langsung jika punya Kode Tiket -->
                    <form action="{{ route('public.index') }}" method="GET" class="space-y-4">
                        <input type="hidden" name="tab" value="lacak">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Field 1: Kode Tiket -->
                            <div>
                                <label for="track_q" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                    Kode Tiket <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        🎫
                                    </span>
                                    <input type="text" id="track_q" name="q" value="{{ request('q') }}" required
                                        placeholder="Contoh: TIK-202609-0001"
                                        class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-300 text-slate-900 placeholder-slate-400 text-sm font-mono font-semibold focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition shadow-2xs uppercase">
                                </div>
                            </div>

                            <!-- Field 2: Nomor Kontak Terdaftar -->
                            <div>
                                <label for="track_wa" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                    Nomor Kontak / Telepon Terdaftar <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        📱
                                    </span>
                                    <input type="tel" id="track_wa" name="no_whatsapp" value="{{ request('no_whatsapp') }}" required
                                        placeholder="Contoh: 081234567890"
                                        class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-300 text-slate-900 placeholder-slate-400 text-sm font-semibold focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition shadow-2xs">
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2">
                            <div class="flex items-center gap-2 text-[11px] text-slate-500">
                                <span>🔒</span>
                                <span>Privasi terlindungi: Hanya pelapor sah yang dapat melihat rincian tiket.</span>
                            </div>
                            <div class="flex items-center gap-2 w-full sm:w-auto">
                                @if(!empty($query))
                                    <a href="{{ route('public.index', ['tab' => 'lacak']) }}"
                                        class="w-full sm:w-auto px-5 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs sm:text-sm transition flex items-center justify-center gap-2">
                                        <span>🔄</span>
                                        <span>Lacak Tiket Lain</span>
                                    </a>
                                @endif
                                <button type="submit"
                                    class="w-full sm:w-auto px-7 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm shadow-md shadow-blue-600/20 transition flex items-center justify-center gap-2">
                                    <span>🔍</span>
                                    <span>Lacak Status Tiket</span>
                                </button>
                            </div>
                        </div>

                    </form>
                </div>
            </div>

            <!-- Search Results Display -->
            @if(!empty($query))
                @if($tickets->isEmpty())
                    <!-- Not Found State -->
                    <div class="bg-white rounded-3xl border border-slate-200 p-12 text-center max-w-2xl mx-auto shadow-sm">
                        <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center text-2xl mb-3">
                            🔎
                        </div>
                        <h3 class="text-lg font-bold text-slate-800">Tiket Tidak Ditemukan</h3>
                        <p class="text-sm text-slate-500 mt-1 max-w-md mx-auto">
                            Tidak ditemukan tiket dengan kata kunci: <span class="font-mono font-bold text-slate-700">{{ $query }}</span>. Pastikan nomor tiket atau nomor kontak yang Anda masukkan sudah sesuai.
                        </p>
                        <div class="mt-6">
                            <button onclick="switchPortalTab('buat')" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 text-white font-bold text-xs hover:bg-blue-700 transition shadow-sm">
                                <span>✍️</span>
                                <span>Buat Laporan Tiket Baru</span>
                            </button>
                        </div>
                    </div>
                @else
                    <!-- Ticket Found Cards -->
                    <div class="space-y-8">
                        @if(isset($relatedTickets) && $relatedTickets->isNotEmpty())
                            <!-- Banner Tiket Aktif Lainnya Milik Pelapor -->
                            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200/90 rounded-2xl p-4 sm:p-5 shadow-xs">
                                <div class="flex items-center justify-between gap-3 mb-2.5">
                                    <div class="flex items-center gap-2">
                                        <span class="text-base">📋</span>
                                        <span class="text-xs sm:text-sm font-bold text-slate-900">
                                            Tiket Pengaduan Aktif Lainnya Milik Anda ({{ $relatedTickets->count() }})
                                        </span>
                                    </div>
                                    <span class="text-[11px] font-semibold text-blue-700 bg-blue-100 px-2.5 py-0.5 rounded-full">
                                        Nomor Kontak Terdaftar
                                    </span>
                                </div>
                                <p class="text-xs text-slate-600 mb-3">
                                    Sistem mendeteksi pengaduan lain yang sedang berjalan atas nomor Anda. Klik tiket di bawah untuk langsung berpindah pelacakan:
                                </p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                    @foreach($relatedTickets as $rel)
                                        <a href="{{ route('public.index', ['tab' => 'lacak', 'q' => $rel->kode_tiket, 'auth_token' => $rel->access_token]) }}"
                                           class="flex items-center justify-between p-3 rounded-xl bg-white border border-blue-100 hover:border-blue-400 hover:shadow-md transition group">
                                            <div class="min-w-0 pr-2">
                                                <div class="flex items-center gap-2">
                                                    <span class="font-mono text-xs font-bold text-blue-700 group-hover:text-blue-800">
                                                        {{ $rel->kode_tiket }}
                                                    </span>
                                                    <span class="text-[10px] px-2 py-0.5 rounded-full font-semibold bg-slate-100 text-slate-700">
                                                        {{ $rel->status_label }}
                                                    </span>
                                                </div>
                                                <div class="text-xs font-medium text-slate-800 truncate mt-1">
                                                    {{ $rel->judul }}
                                                </div>
                                            </div>
                                            <span class="text-blue-600 text-xs font-bold shrink-0 group-hover:translate-x-0.5 transition-transform">
                                                Lihat &rarr;
                                            </span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @foreach($tickets as $t)

                            <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/70 border border-slate-200/80 overflow-hidden">
                                <!-- 1. Header Card: Kode + TLP Badge + Status Badge -->
                                <div class="p-6 sm:p-8 bg-slate-50/80 border-b border-slate-200/80 flex flex-col md:flex-row md:items-center justify-between gap-4">
                                    <div>
                                        <div class="flex items-center gap-2.5 flex-wrap">
                                            <!-- Kode Tiket -->
                                            <span class="font-mono text-base font-extrabold text-slate-900 bg-white px-3 py-1.5 rounded-xl border border-slate-300 shadow-sm">
                                                {{ $t->kode_tiket }}
                                            </span>

                                            <!-- Status Badge -->
                                            <span class="text-xs px-3 py-1 rounded-full font-bold uppercase tracking-wider
                                                @if($t->status === 'resolved') bg-emerald-100 text-emerald-800 border border-emerald-200
                                                @elseif($t->status === 'closed') bg-slate-200 text-slate-800 border border-slate-300
                                                @elseif($t->status === 'in_progress') bg-blue-100 text-blue-800 border border-blue-200
                                                @elseif($t->status === 'verified') bg-sky-100 text-sky-800 border border-sky-200
                                                @elseif($t->status === 'reopened') bg-orange-100 text-orange-800 border border-orange-200
                                                @elseif($t->status === 'eskalasi_pusat') bg-purple-100 text-purple-800 border border-purple-200 animate-pulse
                                                @elseif($t->status === 'force_closed') bg-rose-100 text-rose-800 border border-rose-200
                                                @elseif($t->status === 'cancelled') bg-rose-100 text-rose-800 border border-rose-300
                                                @else bg-amber-100 text-amber-800 border border-amber-200 @endif">
                                                {{ str_replace('_', ' ', $t->status) }}
                                            </span>

                                            <!-- Badge Otorisasi Pelapor Sah -->
                                            @if(\App\Services\TicketAuthService::isAuthorized(request(), $t) || (request('auth_token') && hash_equals($t->access_token, (string) request('auth_token'))))
                                                <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 shadow-2xs">
                                                    <span>🛡️</span>
                                                    <span>Identitas Pelapor Sah</span>
                                                </span>
                                            @endif

                                            <!-- TLP Level Badge -->
                                            @if($t->tlp_level)
                                                <span class="text-xs px-2.5 py-1 rounded-lg font-mono font-extrabold tracking-wider shadow-sm
                                                    @if(strtoupper($t->tlp_level) === 'RED') bg-rose-600 text-white
                                                    @elseif(strtoupper($t->tlp_level) === 'AMBER') bg-amber-500 text-white
                                                    @elseif(strtoupper($t->tlp_level) === 'GREEN') bg-emerald-600 text-white
                                                    @else bg-slate-500 text-white @endif">
                                                    TLP: {{ strtoupper($t->tlp_level) }}
                                                </span>
                                            @else
                                                <span class="text-xs px-2.5 py-1 rounded-lg font-medium bg-slate-200 text-slate-600">
                                                    TLP Menunggu Verifikasi
                                                </span>
                                            @endif
                                        </div>

                                        <h3 class="text-xl sm:text-2xl font-extrabold text-slate-900 mt-3 leading-snug">
                                            {{ $t->judul }}
                                        </h3>

                                        <div class="mt-2 flex items-center gap-3 text-xs text-slate-500 flex-wrap">
                                            <span class="px-2.5 py-0.5 rounded-md bg-blue-50 text-blue-800 font-semibold border border-blue-100">
                                                {{ $t->category?->nama_kategori }}
                                            </span>
                                            <span>&bull;</span>
                                            <span>Bidang: <strong class="text-slate-700">{{ $t->bidang?->nama_bidang ?? 'Insiden Darurat / Pusat' }}</strong></span>
                                            <span>&bull;</span>
                                            <span>Waktu Pengajuan: <strong class="text-slate-700">{{ $t->created_at->translatedFormat('d F Y, H:i') }} WIB</strong></span>
                                        </div>
                                    </div>

                                    <!-- Status Teknisi Lapangan & Aksi Pelapor -->
                                    <div class="md:text-right text-xs text-slate-500 space-y-1.5">
                                        @if($t->status === 'cancelled')
                                            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-rose-50 text-rose-700 border border-rose-200 font-bold">
                                                <span>🚫</span>
                                                <span>Tiket Dibatalkan</span>
                                            </div>
                                        @elseif($t->teknisi_lapangan)
                                            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 font-semibold">
                                                <span>👷</span>
                                                <span>Teknisi: {{ $t->teknisi_lapangan }}</span>
                                            </div>
                                        @else
                                            <div class="text-slate-400 italic">Teknisi: Menunggu Penugasan</div>
                                        @endif

                                        @if($t->status === 'pending_verification')
                                            <div>
                                                <button type="button" onclick="openCancelTicketModal('{{ $t->kode_tiket }}')" 
                                                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold text-xs shadow-2xs hover:shadow-xs transition cursor-pointer">
                                                    <span>🚫 Batalkan Tiket</span>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- 2. HORIZONTAL STEPPER 5 TAHAP (Model SP4N LAPOR) -->
                                @php
                                    // Tahapan Stepper 5 Tahap:
                                    // 1: Tulis Laporan
                                    // 2: Proses Verifikasi (pending_verification)
                                    // 3: Tindak Lanjut (verified, in_progress)
                                    // 4: Beri Tanggapan (resolved, reopened, eskalasi_pusat)
                                    // 5: Selesai / Dibatalkan (closed, force_closed, cancelled)
                                    $activeStage = 1;
                                    $progressPercent = 0;

                                    if ($t->status === 'pending_verification') {
                                        $activeStage = 2;
                                        $progressPercent = 25;
                                    } elseif (in_array($t->status, ['verified', 'in_progress'])) {
                                        $activeStage = 3;
                                        $progressPercent = 50;
                                    } elseif (in_array($t->status, ['resolved', 'reopened', 'eskalasi_pusat'])) {
                                        $activeStage = 4;
                                        $progressPercent = 75;
                                    } elseif (in_array($t->status, ['closed', 'force_closed', 'cancelled'])) {
                                        $activeStage = 5;
                                        $progressPercent = 100;
                                    }

                                    $steps = [
                                        [
                                            'num' => 1,
                                            'title' => 'Tulis Laporan',
                                            'icon' => '✍️',
                                            'desc' => 'Tiket diajukan oleh ASN beserta identitas & berkas pendukung.',
                                        ],
                                        [
                                            'num' => 2,
                                            'title' => 'Verifikasi',
                                            'icon' => !empty($t->catatan_kelengkapan) ? '⚠️' : '🔍',
                                            'desc' => !empty($t->catatan_kelengkapan) 
                                                ? 'Perlu data tambahan dari pelapor.' 
                                                : 'Verifikasi kendala oleh Admin Pusat & penetapan level TLP keamanan.',
                                        ],
                                        [
                                            'num' => 3,
                                            'title' => 'Tindak Lanjut',
                                            'icon' => '⚙️',
                                            'desc' => $t->teknisi_lapangan ? 'Ditangani oleh teknisi: ' . $t->teknisi_lapangan : 'Disposisi ke Bidang terkait untuk penugasan teknisi.',
                                        ],
                                        [
                                            'num' => 4,
                                            'title' => 'Tanggapan',
                                            'icon' => $t->status === 'eskalasi_pusat' ? '⚖️' : ($t->reopen_count > 0 ? '🔄' : '💬'),
                                            'desc' => $t->status === 'eskalasi_pusat' 
                                                ? 'Deadlock: Reopen mencapai batas dan diaudit langsung oleh Admin Pusat.' 
                                                : ($t->status === 'resolved' 
                                                    ? 'Menunggu evaluasi pelapor: Konfirmasi selesai atau ajukan reopen.' 
                                                    : 'Umpan balik dan peninjauan hasil perbaikan.'),
                                        ],
                                        [
                                            'num' => 5,
                                            'title' => $t->status === 'force_closed' ? 'Force Closed' : ($t->status === 'cancelled' ? 'Dibatalkan' : 'Selesai'),
                                            'icon' => $t->status === 'force_closed' ? '⚖️' : ($t->status === 'cancelled' ? '🚫' : '✓'),
                                            'desc' => $t->status === 'force_closed' 
                                                ? 'Ditutup paksa secara resmi oleh Admin Pusat berdasarkan regulasi.' 
                                                : ($t->status === 'cancelled'
                                                    ? 'Tiket dibatalkan oleh pelapor pada tahap verifikasi awal.'
                                                    : ($t->status === 'closed' 
                                                        ? 'Penanganan tuntas diselesaikan dengan rating ' . ($t->rating ?? 5) . '/5 ⭐' 
                                                        : 'Tiket selesai dikerjakan dan dinyatakan tuntas.')),
                                        ],
                                    ];
                                @endphp

                                <div class="px-6 sm:px-8 py-7 bg-slate-900 text-white overflow-x-auto">
                                    <div class="flex items-center justify-between mb-5 text-xs text-slate-400 font-bold uppercase tracking-wider min-w-[620px]">
                                        <div class="flex items-center gap-2 text-blue-400">
                                            <span>📍</span>
                                            <span>Tahapan Alur Pelacakan Layanan (Tracking Milestones)</span>
                                        </div>
                                        <div>
                                            Tahap Aktif: <span class="text-white font-extrabold">{{ $activeStage }} dari 5</span>
                                        </div>
                                    </div>

                                    <!-- Stepper Visual Track -->
                                    <div class="relative flex justify-between items-start min-w-[620px] px-4">
                                        <!-- Background Gray Line -->
                                        <div class="absolute top-5 left-10 right-10 h-1 bg-slate-800 rounded-full z-0"></div>
                                        <!-- Active Color Fill Line -->
                                        <div class="absolute top-5 left-10 h-1 bg-gradient-to-r from-emerald-500 via-blue-500 to-indigo-500 rounded-full z-0 transition-all duration-700"
                                            style="width: calc({{ $progressPercent }}% - 20px);"></div>

                                        @foreach($steps as $s)
                                            @php
                                                $isCompleted = $s['num'] < $activeStage;
                                                $isActive = $s['num'] === $activeStage;
                                            @endphp
                                            <div class="flex flex-col items-center text-center relative z-10 w-28">
                                                <!-- Node Circle -->
                                                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm shadow-md transition-all duration-300
                                                    @if($isCompleted)
                                                        bg-emerald-500 text-white ring-4 ring-emerald-950
                                                    @elseif($isActive)
                                                        @if(!empty($t->catatan_kelengkapan))
                                                            bg-amber-500 text-slate-950 ring-4 ring-amber-400 animate-pulse
                                                        @elseif($t->status === 'eskalasi_pusat')
                                                            bg-purple-600 text-white ring-4 ring-purple-900 animate-pulse
                                                        @elseif($t->status === 'cancelled')
                                                            bg-rose-600 text-white ring-4 ring-rose-900
                                                        @else
                                                            bg-blue-600 text-white ring-4 ring-blue-900 animate-pulse
                                                        @endif
                                                    @else
                                                        bg-slate-800 text-slate-500 border border-slate-700
                                                    @endif">
                                                    @if($isCompleted)
                                                        ✓
                                                    @else
                                                        {{ $s['icon'] }}
                                                    @endif
                                                </div>

                                                <!-- Step Title -->
                                                <div class="mt-2 text-xs font-bold leading-tight
                                                    @if($isActive) text-white font-extrabold
                                                    @elseif($isCompleted) text-slate-200
                                                    @else text-slate-500 @endif">
                                                    {{ $s['title'] }}
                                                </div>

                                                <!-- Step Short Description -->
                                                <div class="mt-1 text-[11px] leading-tight line-clamp-2
                                                    @if($isActive) text-blue-300
                                                    @elseif($isCompleted) text-slate-400
                                                    @else text-slate-600 @endif">
                                                    {{ $s['desc'] }}
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- 3. Detail Section & Deskripsi Permohonan -->
                                <div class="p-6 sm:p-8 space-y-6">
                                    @php
                                        $isAuthorized = \App\Services\TicketAuthService::isAuthorized(request(), $t);
                                    @endphp

                                    <div class="bg-slate-50 rounded-2xl p-5 border border-slate-100 space-y-3">
                                        <div class="text-xs font-semibold uppercase text-slate-400 tracking-wider">
                                            Kategori: <strong class="text-slate-800">{{ $t->category?->nama_kategori }}</strong>
                                        </div>
                                        <div class="text-sm text-slate-700 whitespace-pre-line leading-relaxed">
                                            @if($isAuthorized)
                                                {{ $t->deskripsi }}
                                            @else
                                                {{ \Illuminate\Support\Str::limit($t->deskripsi, 60) }}
                                                <span class="text-xs text-amber-700 font-medium block mt-1">🔒 [Deskripsi lengkap disensor demi privasi laporan kedinasan. Masukkan PIN Keamanan di bawah untuk membuka.]</span>
                                            @endif
                                        </div>
                                        @if($t->lampiran_pelapor)
                                            @php
                                                $lampiranItems = is_array($t->lampiran_pelapor) ? $t->lampiran_pelapor : (is_string($t->lampiran_pelapor) && str_starts_with($t->lampiran_pelapor, '[') ? json_decode($t->lampiran_pelapor, true) : [$t->lampiran_pelapor]);
                                                $lampiranItems = array_filter((array) $lampiranItems);
                                            @endphp
                                            @if(count($lampiranItems) > 0)
                                                <div class="pt-2 border-t border-slate-200/60 space-y-1.5">
                                                    <span class="text-xs font-semibold text-slate-600 block">📎 Dokumen Lampiran Pelapor ({{ count($lampiranItems) }} Berkas):</span>
                                                    <div class="flex flex-wrap gap-2">
                                                        @foreach($lampiranItems as $idx => $lampiran)
                                                            @php
                                                                $ext = strtolower(pathinfo($lampiran, PATHINFO_EXTENSION));
                                                                $isPdf = $ext === 'pdf';
                                                                $filename = basename($lampiran);
                                                                $fileUrl = route('public.tiket.download', ['kode' => $t->kode_tiket, 'type' => 'lampiran', 'index' => $idx]);
                                                            @endphp
                                                            @if($isAuthorized)
                                                                <a href="{{ $fileUrl }}" target="_blank" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-blue-50 border border-blue-200 text-blue-800 text-xs font-medium hover:bg-blue-100 transition shadow-xs">
                                                                    <span>{{ $isPdf ? '📄' : '🖼️' }}</span>
                                                                    <span>Berkas {{ $idx + 1 }}: {{ \Illuminate\Support\Str::limit($filename, 22) }}</span>
                                                                    <span class="text-[10px] text-blue-600 font-bold uppercase">({{ $ext ?: 'FILE' }})</span>
                                                                </a>
                                                            @else
                                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 border border-slate-200 text-slate-500 text-xs font-medium cursor-not-allowed">
                                                                    <span>🔒</span>
                                                                    <span>Berkas {{ $idx + 1 }} (Terkunci - Butuh PIN)</span>
                                                                </span>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        @endif
                                        @if($t->lampiran_tambahan)
                                            <div class="pt-2 border-t border-slate-200/60 flex items-center gap-2 text-xs text-emerald-700 font-medium">
                                                <span>📎</span>
                                                @if($isAuthorized)
                                                    <a href="{{ route('public.tiket.download', ['kode' => $t->kode_tiket, 'type' => 'lampiran_tambahan']) }}" target="_blank" class="hover:underline">
                                                        Lampiran Tambahan Pelapor: Unduh Berkas
                                                    </a>
                                                @else
                                                    <span class="text-slate-500 italic">
                                                        🔒 Lampiran Tambahan (Terkunci - Butuh PIN)
                                                    </span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>

                                    @if(!$isAuthorized && $t->status !== 'resolved')
                                        <!-- PIN VERIFICATION BOX UNTUK TIKET AKTIF YANG BELUM TEROTORISASI -->
                                        <div class="p-5 rounded-2xl bg-amber-50/90 border border-amber-300 text-amber-950 space-y-3 shadow-xs">
                                            <div class="flex items-start gap-3">
                                                <div class="w-9 h-9 rounded-xl bg-amber-500 text-white flex items-center justify-center font-bold text-base flex-shrink-0 shadow-xs">
                                                    🔒
                                                </div>
                                                <div>
                                                    <h4 class="text-sm font-bold text-amber-950">Verifikasi Identitas Pelapor</h4>
                                                    <p class="text-xs text-amber-900/90 mt-0.5 leading-relaxed">
                                                        Masukkan 6 digit PIN Keamanan dari Struk Tanda Terima Digital untuk membuka deskripsi lengkap dan mengunduh berkas lampiran tiket:
                                                    </p>
                                                </div>
                                            </div>
                                            <form action="{{ route('public.ticket.verify_pin', $t->kode_tiket) }}" method="POST" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 max-w-md pt-1">
                                                @csrf
                                                <input type="text" name="pin" maxlength="6" minlength="6" placeholder="6 Digit PIN Keamanan" required pattern="[0-9]{6}" inputmode="numeric" autocomplete="off"
                                                    class="rounded-xl border-amber-300 text-center font-mono font-bold text-sm tracking-widest py-2 px-3 focus:ring-amber-500 focus:border-amber-500 bg-white shadow-xs">
                                                <button type="submit" class="px-5 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs shadow-xs transition flex items-center justify-center gap-1.5 whitespace-nowrap">
                                                    <span>🔓</span>
                                                    <span>Buka Akses Penuh</span>
                                                </button>
                                            </form>
                                            <p class="text-[11px] text-slate-500 italic">
                                                *Proteksi Keamanan: Batas maksimal 5 kali percobaan. Kesalahan berulang mengunci akses tiket 15 menit.
                                            </p>
                                        </div>
                                    @endif

                                    <!-- CASE KELENGKAPAN DATA: Admin Pusat Meminta Informasi Tambahan -->
                                    @if($t->status === 'pending_verification' && !empty($t->catatan_kelengkapan))
                                        <div class="p-6 rounded-2xl bg-amber-50/90 border-2 border-amber-300 space-y-4 shadow-xs animate-in fade-in">
                                            <div class="flex items-start gap-3">
                                                <div class="w-10 h-10 rounded-2xl bg-amber-500 text-slate-950 flex items-center justify-center font-bold text-xl flex-shrink-0 shadow-xs">
                                                    ⚠️
                                                </div>
                                                <div>
                                                    <h4 class="text-base font-bold text-amber-950">Perhatian: Admin Pusat Meminta Kelengkapan Data / Dokumen</h4>
                                                    <p class="text-xs sm:text-sm text-amber-900 mt-0.5">
                                                        Laporan pengaduan Anda sedang diverifikasi. Harap berikan keterangan atau berkas tambahan di bawah ini agar tiket dapat segera didisposisikan ke teknisi.
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Catatan Permintaan dari Admin Pusat -->
                                            <div class="bg-white p-4 rounded-xl border border-amber-200">
                                                <span class="text-[11px] font-bold text-amber-900 uppercase tracking-wider block mb-1">Catatan Kekurangan dari Admin Pusat DKIS:</span>
                                                <p class="text-xs sm:text-sm text-slate-800 font-semibold whitespace-pre-line leading-relaxed">
                                                    "{{ $t->catatan_kelengkapan }}"
                                                </p>
                                            </div>

                                            <!-- Form Kirim Kelengkapan Data -->
                                            <form action="{{ route('public.ticket.submit_completion', $t->kode_tiket) }}" method="POST" enctype="multipart/form-data" class="bg-white p-5 rounded-xl border border-amber-200 space-y-4 shadow-xs" onsubmit="handleBtnLoading(this, 'Mengirim Data...')">
                                                @csrf
                                                <div>
                                                    <label class="block text-xs font-bold text-slate-800 mb-1.5">
                                                        1. Penjelasan / Informasi Tambahan Pelapor *
                                                    </label>
                                                    <textarea name="keterangan_tambahan" rows="3" required placeholder="Tuliskan keterangan detail untuk melengkapi data yang diminta admin..."
                                                        class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:ring-amber-500 focus:border-amber-500 p-3"></textarea>
                                                </div>

                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                    <div>
                                                        <label class="block text-xs font-bold text-slate-800 mb-1.5">
                                                            2. Berkas / Foto Tambahan (Opsional)
                                                        </label>
                                                        <input type="file" name="lampiran_tambahan" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"
                                                            class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-amber-100 file:text-amber-900 hover:file:bg-amber-200 border border-slate-200 rounded-xl p-1">
                                                        <p class="text-[10px] text-slate-400 mt-1">Format: JPG, PNG, PDF (Maks. 5MB)</p>
                                                    </div>

                                                    <div>
                                                        <label class="block text-xs font-bold text-slate-800 mb-1.5">
                                                            3. PIN Keamanan Tiket *
                                                        </label>
                                                        <input type="text" name="pin" required maxlength="6" pattern="[0-9]{6}" inputmode="numeric" autocomplete="off"
                                                            placeholder="Masukkan 6 Digit PIN"
                                                            value="{{ old('pin', '') }}"
                                                            class="w-full rounded-xl border-slate-300 text-xs sm:text-sm font-mono tracking-wider focus:ring-amber-500 focus:border-amber-500 p-2.5 bg-slate-50">
                                                        <p class="text-[10px] text-slate-400 mt-1">PIN 6-digit tertera pada kotak emas Struk Tanda Terima Digital Anda.</p>
                                                    </div>
                                                </div>

                                                <div class="pt-2 flex justify-end">
                                                    <button type="submit" class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs sm:text-sm shadow-md transition flex items-center justify-center gap-2">
                                                        <span>📤</span>
                                                        <span>Kirim Kelengkapan Data ke Admin Pusat</span>
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    @endif

                                    <!-- CASE A: RESOLVED (Menunggu Konfirmasi Pelapor) -->
                                    @if($t->status === 'resolved')
                                        @if($isAuthorized)
                                            <!-- AREA DETAIL & AKSI PELAPOR (TERVERIFIKASI SAH) -->
                                            <div class="space-y-5">
                                                <!-- Banner Status Terverifikasi -->
                                                <div class="flex items-center justify-between p-3.5 bg-emerald-50 border border-emerald-200 rounded-2xl text-xs text-emerald-900 font-semibold shadow-xs">
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-base">🔓</span>
                                                        <span>Identitas Terverifikasi: Rincian hasil penanganan dan bukti lampiran terbuka untuk Anda.</span>
                                                    </div>
                                                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-200 text-emerald-800 text-[10px] font-bold uppercase tracking-wider">Pelapor Sah</span>
                                                </div>

                                                <!-- HASIL & BUKTI PENANGANAN PETUGAS (DI DALAM VERIFIKASI) -->
                                                @if($t->catatan_resolusi)
                                                    @if($t->is_confidential_evidence || strtoupper($t->tlp_level ?? '') === 'RED')
                                                        <!-- BUKTI RAHASIA (LINK FILE TIDAK TAMPIL) -->
                                                        <div class="p-5 rounded-2xl bg-amber-50/80 border border-amber-200 text-amber-950 space-y-2 shadow-xs">
                                                            <div class="flex items-center gap-2 font-bold text-sm text-amber-900">
                                                                <span>🔒</span>
                                                                <span>Catatan Penyelesaian Teknis (Klasifikasi Tertutup):</span>
                                                            </div>
                                                            <p class="text-xs sm:text-sm text-amber-900/90 leading-relaxed">
                                                                {{ $t->catatan_resolusi }}
                                                            </p>
                                                            <div class="pt-2 border-t border-amber-200/60 text-[11px] sm:text-xs text-amber-800 font-medium italic flex items-start gap-1.5">
                                                                <span>⚠️</span>
                                                                <span>Bukti log teknis berklasifikasi rahasia internal DKIS (TLP:AMBER / TLP:RED). Berkas tidak dipublikasikan ke publik demi keamanan infrastruktur siber Pemkot Cirebon.</span>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <!-- BUKTI TERBUKA (TAMPILKAN LINK FILE) -->
                                                        <div class="p-5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-950 space-y-3 shadow-xs">
                                                            <div class="flex items-center gap-2 font-bold text-sm text-emerald-900">
                                                                <span>✅</span>
                                                                <span>Hasil & Bukti Penanganan Petugas:</span>
                                                            </div>
                                                            <p class="text-xs sm:text-sm text-emerald-900 leading-relaxed bg-white/90 p-3.5 rounded-xl border border-emerald-100">
                                                                {{ $t->catatan_resolusi }}
                                                            </p>
                                                            @if($t->bukti_resolusi)
                                                                <div class="pt-1 flex items-center gap-2 text-xs font-semibold text-emerald-800">
                                                                    <a href="{{ route('public.tiket.download', ['kode' => $t->kode_tiket, 'type' => 'bukti']) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-100 hover:bg-emerald-200 text-emerald-900 transition underline">
                                                                        <span>📎</span>
                                                                        <span>Lihat Berkas Bukti Resolusi (Foto/Laporan)</span>
                                                                    </a>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @endif
                                                @endif

                                                <!-- FORM KONFIRMASI SELESAI & REOPEN -->
                                                <div class="p-6 rounded-2xl bg-gradient-to-br from-emerald-50 to-teal-50 border-2 border-emerald-300/80 space-y-5 shadow-xs">
                                                    <div class="flex items-start justify-between gap-3">
                                                        <div class="flex items-start gap-3">
                                                            <div class="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold text-lg flex-shrink-0">
                                                                💡
                                                            </div>
                                                            <div>
                                                                <h4 class="text-base font-bold text-emerald-950">Penanganan Telah Selesai — Mohon Konfirmasi & Berikan Rating</h4>
                                                                <p class="text-xs sm:text-sm text-emerald-800 mt-0.5">
                                                                    Tim teknis DKIS telah menyelesaikan kendala. Silakan evaluasi hasil perbaikan di atas dan berikan konfirmasi atau ajukan buka kembali jika belum tuntas.
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Form Konfirmasi Selesai & Berikan Rating Bintang -->
                                                    <form action="{{ route('public.ticket.confirm', $t->kode_tiket) }}" method="POST" class="bg-white p-5 rounded-xl border border-emerald-200 space-y-4 shadow-xs" onsubmit="return handleBtnLoading(this, 'Menyimpan Konfirmasi...')">
                                                        @csrf
                                                        <input type="hidden" name="auth_token" value="{{ request('auth_token') ?? $t->access_token }}">
                                                        <input type="hidden" id="selected-rating-val" name="rating" value="5" required>

                                                        <!-- Rating Bintang Interaktif -->
                                                        <div class="space-y-2 bg-amber-50/60 p-4 rounded-xl border border-amber-200/80">
                                                            <div class="flex items-center justify-between">
                                                                <label class="block text-xs font-bold text-slate-800">
                                                                    Tingkat Kepuasan Layanan Penanganan <span class="text-rose-500">*</span>
                                                                </label>
                                                                <span class="text-[11px] text-slate-500">Klik bintang 1 - 5</span>
                                                            </div>
                                                            <div class="flex flex-wrap items-center gap-2">
                                                                <div class="flex items-center gap-1" id="star-rating-container">
                                                                    @for($star = 1; $star <= 5; $star++)
                                                                        <button type="button" class="star-rating-btn text-2xl sm:text-3xl transition-transform hover:scale-125 focus:outline-hidden text-amber-400 select-none cursor-pointer"
                                                                            data-star="{{ $star }}"
                                                                            onclick="setPublicRating({{ $star }})"
                                                                            onmouseenter="hoverPublicRating({{ $star }})"
                                                                            onmouseleave="resetPublicRatingHover()"
                                                                            title="{{ $star }} Bintang">
                                                                            ★
                                                                        </button>
                                                                    @endfor
                                                                </div>
                                                                <span id="rating-desc-badge" class="text-xs font-bold text-emerald-800 bg-white px-3 py-1 rounded-full border border-emerald-300 shadow-2xs">
                                                                    ⭐⭐⭐⭐⭐ 5: Sangat Puas & Responsif
                                                                </span>
                                                            </div>
                                                        </div>

                                                        <div>
                                                            <label class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center justify-between">
                                                                <span>Ulasan / Catatan Konfirmasi Pelapor (Opsional)</span>
                                                                <span class="text-[11px] text-slate-400 font-normal">Maksimal 1000 karakter</span>
                                                            </label>
                                                            <textarea name="feedback" rows="2" placeholder="Tuliskan ulasan atau tanggapan Anda mengenai hasil pekerjaan penanganan teknisi DKIS..."
                                                                class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:ring-emerald-500 focus:border-emerald-500 p-3"></textarea>
                                                        </div>

                                                        <button type="submit" class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs sm:text-sm shadow-md transition flex items-center justify-center gap-2">
                                                            <span>✅</span>
                                                            <span>Konfirmasi Selesai & Tutup Tiket</span>
                                                        </button>
                                                    </form>

                                                    <!-- Expandable Form Reopen -->
                                                    <details class="group bg-white rounded-xl border border-rose-200 overflow-hidden">
                                                        <summary class="p-4 text-xs sm:text-sm font-bold text-rose-700 hover:text-rose-800 cursor-pointer flex items-center justify-between">
                                                            <span class="flex items-center gap-2">
                                                                <span>🔄</span>
                                                                <span>Kendala Belum Tuntas? Klik di Sini untuk Mengajukan Buka Kembali (Reopen)</span>
                                                            </span>
                                                            <span class="text-xs text-slate-400 group-open:rotate-180 transition">▼</span>
                                                        </summary>

                                                        <form action="{{ route('public.ticket.reopen', $t->kode_tiket) }}" method="POST" enctype="multipart/form-data" class="p-5 pt-2 border-t border-rose-100 space-y-4">
                                                            @csrf
                                                            <input type="hidden" name="auth_token" value="{{ request('auth_token') ?? $t->access_token }}">
                                                            <div class="text-xs text-slate-500">
                                                                Batas maksimal reopen adalah <strong>2 kali</strong>. Jika kendala masih berulang, tiket otomatis dialihkan ke status Eskalasi Pusat untuk audit pimpinan.
                                                                (Status saat ini: Reopen ke-{{ $t->reopen_count }} dari batas {{ $t->max_reopen }}x).
                                                            </div>

                                                            <div>
                                                                <label class="block text-xs font-bold text-slate-700 mb-1">
                                                                    Alasan Pembukaan Kembali Tiket <span class="text-rose-500">*</span>
                                                                </label>
                                                                <textarea name="alasan" rows="3" required placeholder="Jelaskan secara detail bagian kendala mana yang masih bermasalah..."
                                                                    class="w-full rounded-lg border-slate-300 text-xs sm:text-sm focus:ring-rose-500 focus:border-rose-500"></textarea>
                                                            </div>

                                                            <div>
                                                                <label class="block text-xs font-bold text-slate-700 mb-1">
                                                                    Upload Bukti Kendala Lanjutan (Wajib) <span class="text-rose-500">*</span>
                                                                </label>
                                                                <input type="file" name="bukti_reopen" accept=".jpg,.jpeg,.png,.pdf" required
                                                                    class="block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-rose-50 file:text-rose-700 hover:file:bg-rose-100 cursor-pointer">
                                                            </div>

                                                            <button type="submit" class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow transition">
                                                                Ajukan Pembukaan Kembali Tiket
                                                            </button>
                                                        </form>
                                                    </details>
                                                </div>
                                            </div>
                                        @else
                                            <!-- KOTAK PROTEKSI KEAMANAN: VERIFIKASI IDENTITAS PELAPOR -->
                                            <div class="p-6 rounded-2xl bg-amber-50/90 border-2 border-amber-300 text-amber-950 space-y-4 shadow-sm">
                                                <div class="flex items-start gap-3.5">
                                                    <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center font-bold text-lg flex-shrink-0 shadow-xs">
                                                        🔒
                                                    </div>
                                                    <div>
                                                        <h4 class="text-base font-bold text-amber-950">Verifikasi Identitas Pelapor Diperlukan</h4>
                                                        <p class="text-xs sm:text-sm text-amber-900/90 mt-1 leading-relaxed">
                                                            Tiket ini telah berstatus <strong>Menunggu Konfirmasi</strong>. Demi menjaga keamanan informasi teknis dan privasi laporan, <strong>rincian hasil penanganan petugas, dokumen bukti lampiran, serta aksi konfirmasi</strong> hanya dapat diakses setelah identitas pelapor diverifikasi.
                                                        </p>
                                                    </div>
                                                </div>

                                                <div class="bg-white/95 p-5 rounded-xl border border-amber-200 space-y-3">
                                                    <p class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                                        <span>🔑</span>
                                                        <span>Masukkan 6 digit PIN Keamanan yang tertera pada Struk Tanda Terima Digital untuk membuka detail & bukti tiket:</span>
                                                    </p>
                                                    <form action="{{ route('public.ticket.verify_pin', $t->kode_tiket) }}" method="POST" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 max-w-md">
                                                        @csrf
                                                        <input type="text" name="pin" maxlength="6" minlength="6" placeholder="6 Digit PIN Keamanan" required pattern="[0-9]{6}" inputmode="numeric" autocomplete="off"
                                                            class="rounded-xl border-amber-300 text-center font-mono font-bold text-sm tracking-widest py-2 px-3 focus:ring-amber-500 focus:border-amber-500 bg-white shadow-xs">
                                                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs shadow-xs transition flex items-center justify-center gap-1.5 whitespace-nowrap">
                                                            <span>🔓</span>
                                                            <span>Buka Detail & Bukti Tiket</span>
                                                        </button>
                                                    </form>
                                                    <p class="text-[11px] text-slate-500 italic">
                                                        *Proteksi Keamanan: Batas maksimal 5 kali percobaan. Kesalahan berulang akan mengunci akses tiket sementara selama 15 menit.
                                                    </p>
                                                </div>
                                            </div>
                                        @endif
                                    @endif

                                    <!-- JIKA TIKET SUDAH CLOSED: TAMPILKAN HASIL JIKA TERVERIFIKASI -->
                                    @if($t->status === 'closed')
                                        @if($isAuthorized)
                                            <div class="space-y-4">
                                                @if($t->catatan_resolusi)
                                                    <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200 text-slate-900 space-y-3">
                                                        <div class="flex items-center justify-between flex-wrap gap-2">
                                                            <div class="flex items-center gap-2 font-bold text-sm text-slate-800">
                                                                <span>✅</span>
                                                                <span>Hasil Penanganan Petugas:</span>
                                                            </div>
                                                            @php
                                                                $rVal = (int) ($t->rating ?? 5);
                                                                $starIcons = str_repeat('★', $rVal) . str_repeat('☆', 5 - $rVal);
                                                            @endphp
                                                            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-50 border border-amber-200 text-xs font-bold text-amber-800 shadow-2xs">
                                                                <span class="text-amber-500 tracking-wider text-sm">{{ $starIcons }}</span>
                                                                <span>{{ $rVal }}/5</span>
                                                            </div>
                                                        </div>
                                                        <p class="text-xs sm:text-sm text-slate-700 leading-relaxed bg-white p-3.5 rounded-xl border border-slate-200">
                                                            {{ $t->catatan_resolusi }}
                                                        </p>
                                                        @if($t->feedback)
                                                            <div class="text-xs text-slate-600 bg-emerald-50/70 p-3 rounded-xl border border-emerald-200">
                                                                <span class="font-bold text-emerald-900 block mb-0.5">💬 Ulasan & Evaluasi Pelapor:</span>
                                                                <p class="italic text-slate-700">"{{ $t->feedback }}"</p>
                                                            </div>
                                                        @endif
                                                        @if($t->bukti_resolusi && !$t->is_confidential_evidence && strtoupper($t->tlp_level ?? '') !== 'RED')
                                                            <div class="pt-1 text-xs font-semibold text-slate-700">
                                                                <a href="{{ route('public.tiket.download', ['kode' => $t->kode_tiket, 'type' => 'bukti']) }}" target="_blank" class="inline-flex items-center gap-1.5 underline hover:text-slate-950">
                                                                    <span>📎</span>
                                                                    <span>Lihat Berkas Bukti Resolusi (Foto/Laporan)</span>
                                                                </a>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    @endif

                                    <!-- CASE B: ESKALASI PUSAT (Deadlock Message Box) -->
                                    @if($t->status === 'eskalasi_pusat')
                                        <div class="p-6 rounded-2xl bg-purple-50 border-2 border-purple-300 text-purple-950 space-y-2 shadow-sm">
                                            <div class="flex items-center gap-2 font-bold text-base text-purple-900">
                                                <span>⚖️</span>
                                                <span>Status: Eskalasi Deadlock ke Admin Pusat DKIS</span>
                                            </div>
                                            <p class="text-xs sm:text-sm text-purple-900/90 leading-relaxed">
                                                Tiket ini telah diajukan buka kembali (reopen) sebanyak <strong>{{ $t->reopen_count }} kali</strong> (mencapai batas maksimum). Terjadi sengketa teknis yang belum terselesaikan pada level teknisi bidang.
                                            </p>
                                            <p class="text-xs sm:text-sm text-purple-800 font-medium">
                                                Saat ini penanganan tiket sedang diaudit dan ditinjau langsung oleh <strong>Admin Pusat DKIS Kota Cirebon</strong> bersama koordinator bidang untuk menetapkan putusan teknis akhir.
                                            </p>
                                        </div>
                                    @endif

                                    <!-- CASE C: FORCE CLOSED (Catatan Penutupan & Tombol Buat Baru) -->
                                    @if($t->status === 'force_closed')
                                        <div class="p-6 rounded-2xl bg-slate-100 border border-slate-300 text-slate-900 space-y-3">
                                            <div class="flex items-center gap-2 font-bold text-base text-slate-800">
                                                <span>⚖️</span>
                                                <span>Tiket Ditutup Paksa (Force Closed) oleh Admin Pusat</span>
                                            </div>
                                            <div class="p-4 rounded-xl bg-white border border-slate-200 text-xs sm:text-sm italic text-slate-700">
                                                "{{ $t->force_close_notes ?? 'Penutupan resmi berdasarkan audit dan batas demarkasi kewenangan teknis DKIS.' }}"
                                            </div>
                                            <p class="text-xs text-slate-500">
                                                Jika Anda memiliki kebutuhan layanan baru di luar ruang lingkup laporan ini, silakan ajukan tiket baru dengan menyertakan rincian terbaru.
                                            </p>
                                            <div>
                                                <button onclick="switchPortalTab('buat')" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow transition">
                                                    ✍️ Buat Tiket Layanan Baru
                                                </button>
                                            </div>
                                        </div>
                                    @endif

                                    <!-- CASE D: CLOSED (Konfirmasi & Ulasan Pelapor) -->
                                    @if($t->status === 'closed')
                                        <div class="p-5 rounded-2xl bg-emerald-50/70 border border-emerald-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs sm:text-sm">
                                            <div class="flex items-center gap-3 text-emerald-900">
                                                <span class="text-2xl">✅</span>
                                                <div>
                                                    <span class="font-bold">Tiket Telah Dikonfirmasi Selesai oleh Pelapor</span>
                                                    @if($t->feedback)
                                                        <div class="text-xs text-emerald-800 mt-1 bg-emerald-100/60 px-3 py-1.5 rounded-lg border border-emerald-200/60">
                                                            <span class="font-semibold">Ulasan Pelapor:</span> "{{ $t->feedback }}"
                                                        </div>
                                                    @else
                                                        <div class="text-xs text-emerald-700 mt-0.5">Dikonfirmasi selesai tanpa ulasan tambahan.</div>
                                                    @endif
                                                </div>
                                            </div>
                                            <span class="px-3 py-1 rounded-full bg-emerald-200 text-emerald-900 text-xs font-bold whitespace-nowrap">
                                                Selesai Sempurna
                                            </span>
                                        </div>
                                    @endif

                                    <!-- CASE E: CANCELLED (Tiket Dibatalkan oleh Pelapor) -->
                                    @if($t->status === 'cancelled')
                                        @php
                                            $cancelHistory = collect($t->status_history ?? [])->reverse()->firstWhere('status', 'cancelled');
                                        @endphp
                                        <div class="p-6 rounded-2xl bg-rose-50 border border-rose-200 text-rose-950 space-y-3">
                                            <div class="flex items-center gap-2 font-bold text-base text-rose-800">
                                                <span>🚫</span>
                                                <span>Tiket Telah Dibatalkan</span>
                                            </div>
                                            <div class="p-4 rounded-xl bg-white border border-rose-200 text-xs sm:text-sm text-slate-700">
                                                <strong class="text-rose-900 block mb-1">Keterangan Pembatalan:</strong>
                                                "{{ $cancelHistory['note'] ?? 'Tiket telah dibatalkan oleh pelapor pada tahap verifikasi awal.' }}"
                                            </div>
                                            <p class="text-xs text-slate-500">
                                                Tiket ini tidak lagi diproses oleh tim teknis DKIS. Jika Anda masih membutuhkan bantuan atau kendala berulang, silakan ajukan tiket baru.
                                            </p>
                                            <div>
                                                <button onclick="switchPortalTab('buat')" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow transition">
                                                    ✍️ Buat Tiket Layanan Baru
                                                </button>
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Alert Pemantauan Status Portal -->
                                    <div class="p-4 bg-blue-50/80 border border-blue-200 rounded-2xl flex items-start gap-3 text-xs text-blue-950">
                                        <svg class="w-4 h-4 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <p class="leading-relaxed">
                                            Tahapan penanganan di atas diperbarui secara langsung oleh tim teknis DKIS. Pantau halaman ini secara berkala untuk melihat perkembangan penyelesaian pengaduan Anda.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>
    </main>

    <!-- ══════════════════════════════════════════════════════════════════════
         MODAL POPUP SUKSES PEMBUATAN TIKET (Triggered by session new_ticket_code)
         ══════════════════════════════════════════════════════════════════════ -->
    @if(session('new_ticket_code'))
        @php
            $strukData = session('new_ticket_data') ?? [
                'kode_tiket' => session('new_ticket_code'),
                'waktu' => now()->translatedFormat('d F Y, H:i') . ' WIB',
                'nama' => 'Pelapor Terdaftar',
                'nip' => '-',
                'skpd' => '-',
                'kategori' => '-',
                'judul' => 'Pengaduan Layanan DKIS',
                'pin' => session('new_ticket_pin') ?? '882910',
            ];
        @endphp
        <div id="modal-success-ticket" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm overflow-y-auto">
            <div style="background-color: #0d1e3a; border-radius: 24px; max-width: 530px; width: 100%; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7); margin: 32px auto;" class="animate-scaleUp text-left">
                
                <!-- White Modal Header Bar -->
                <div style="background-color: #ffffff; padding: 14px 22px; display: flex; align-items: center; justify-content: space-between; border-top-left-radius: 24px; border-top-right-radius: 24px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 20px;">🎉</span>
                        <h3 style="font-size: 17px; font-weight: 900; color: #0f172a; margin: 0;">Tiket Berhasil Diajukan!</h3>
                    </div>
                    <button type="button" onclick="closeSuccessModal()" style="color: #64748b; font-size: 20px; font-weight: 700; background: none; border: none; cursor: pointer; padding: 4px;">✕</button>
                </div>

                <!-- Deep Navy Container Body -->
                <div style="padding: 16px;">
                    <!-- Cream Receipt Paper -->
                    <div style="background-color: #faf8f5; border-radius: 16px; padding: 22px; color: #0f172a; font-size: 13px; line-height: 1.6; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                        
                        <div style="text-align: center; font-weight: 800; font-size: 12.5px; text-transform: uppercase; color: #0f172a; line-height: 1.35; letter-spacing: 0.3px;">
                            <div>PEMERINTAH DAERAH KOTA CIREBON</div>
                            <div>DINAS KOMUNIKASI, INFORMATIKA, DAN STATISTIK</div>
                        </div>

                        <div style="border-top: 1px solid #cbd5e1; margin: 12px 0;"></div>

                        <div style="text-align: center; font-weight: 900; font-size: 13.5px; text-transform: uppercase; letter-spacing: 0.8px; color: #0f172a;">
                            BUKTI TANDA TERIMA PENGAJUAN TIKET
                        </div>

                        <div style="border-top: 1px solid #cbd5e1; margin: 12px 0 14px 0;"></div>

                        <!-- Ticket Code Row -->
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                            <span style="font-weight: 900; color: #0f172a; font-size: 13px; text-transform: uppercase;">KODE TIKET RESMI:</span>
                            <span style="background-color: #1e3a8a; color: #ffffff; font-weight: 900; font-family: ui-monospace, monospace; padding: 3px 12px; border-radius: 8px; font-size: 13px; letter-spacing: 0.5px;">{{ $strukData['kode_tiket'] }}</span>
                        </div>

                        <div style="margin-bottom: 12px; color: #1e293b; font-weight: 600; font-size: 12.5px;">
                            Waktu Pendaftaran: {{ $strukData['waktu'] }}
                        </div>

                        <!-- Identitas Pelapor -->
                        <div style="margin-bottom: 12px;">
                            <div style="font-weight: 900; color: #0f172a; font-size: 13px; margin-bottom: 4px;">Identitas Pelapor:</div>
                            <div style="padding-left: 6px; line-height: 1.55; color: #1e293b; font-size: 12.5px;">
                                <div>• Nama Pegawai: <strong>{{ $strukData['nama'] }}</strong></div>
                                <div>• NIP Pegawai: <strong>{{ $strukData['nip'] }}</strong></div>
                                <div>• Instansi / SKPD: <strong>{{ $strukData['skpd'] }}</strong></div>
                            </div>
                        </div>

                        <!-- Rincian Kendala -->
                        <div style="margin-bottom: 14px;">
                            <div style="font-weight: 900; color: #0f172a; font-size: 13px; margin-bottom: 4px;">Rincian Kendala:</div>
                            <div style="padding-left: 6px; line-height: 1.55; color: #1e293b; font-size: 12.5px;">
                                <div>• Kategori Masalah : <strong>{{ $strukData['kategori'] }}</strong></div>
                                <div>• Judul Kendala: <strong>{{ $strukData['judul'] }}</strong></div>
                            </div>
                        </div>

                        <div style="border-top: 1px solid #cbd5e1; margin: 14px 0 12px 0;"></div>

                        <!-- PIN Keamanan -->
                        <div>
                            <div style="font-size: 13.5px; font-weight: 900; color: #0f172a; display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                <span>🔒 PIN KEAMANAN TIKET ANDA:</span>
                                <span style="font-family: ui-monospace, monospace; font-size: 15px; font-weight: 900; letter-spacing: 4px; color: #0f172a; background-color: #ffffff; padding: 2px 10px; border-radius: 6px; border: 1px solid #cbd5e1;">[ {{ implode(' ', str_split($strukData['pin'])) }} ]</span>
                            </div>
                            <div style="font-size: 11px; color: #475569; margin-top: 6px; line-height: 1.45; font-style: italic;">
                                *Gunakan PIN di atas saat ingin Membatalkan Tiket, Melengkapi Data, atau Mengonfirmasi Selesai setelah perbaikan teknisi.
                            </div>
                        </div>
                    </div>

                    <!-- 3 Action Buttons on Deep Navy -->
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 14px; padding: 0 2px;">
                        <button type="button" onclick="copyTicketCode('{{ $strukData['kode_tiket'] }}')"
                            style="background-color: #e2e8f0; color: #0f172a; font-weight: 800; font-size: 12.5px; padding: 10px 12px; border-radius: 12px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; transition: all 0.2s;">
                            <span id="copy-btn-icon">📋</span>
                            <span id="copy-btn-text">Salin Kode</span>
                        </button>

                        <button type="button" 
                            id="btn-print-struk"
                            data-struk="{{ json_encode($strukData) }}"
                            onclick="triggerPrintStruk(JSON.parse(this.dataset.struk))"
                            style="background-color: #2563eb; color: #ffffff; font-weight: 800; font-size: 12.5px; padding: 10px 12px; border-radius: 12px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2); transition: all 0.2s;">
                            <span>🖨️ Cetak / PDF</span>
                        </button>

                        <button type="button" onclick="closeSuccessModal()"
                            style="background-color: #1d4ed8; color: #ffffff; font-weight: 800; font-size: 12.5px; padding: 10px 12px; border-radius: 12px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2); transition: all 0.2s;">
                            <span>🔍 Pantau Tiket</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- ══════════════════════════════════════════════════════════════════════
         MODAL POPUP PEMBATALAN TIKET OLEH PELAPOR (Otorisasi PIN Keamanan)
         ══════════════════════════════════════════════════════════════════════ -->
    <div id="modal-cancel-ticket" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm hidden overflow-y-auto">
        <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full p-6 text-left space-y-4 animate-scaleUp my-8 border border-slate-100">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <span class="w-9 h-9 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center text-lg font-bold">🚫</span>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">Batalkan Pengajuan Tiket</h3>
                        <p class="text-[11px] text-slate-500 font-medium">Kode Tiket: <span id="cancel-ticket-code-display" class="font-mono font-bold text-rose-600">-</span></p>
                    </div>
                </div>
                <button type="button" onclick="closeCancelTicketModal()" class="text-slate-400 hover:text-slate-600 p-1 text-sm font-bold cursor-pointer">✕</button>
            </div>

            <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-xs text-amber-900 leading-relaxed">
                <strong>Perhatian:</strong> Pembatalan tiket bersifat <strong>permanen</strong> dan hanya dapat dilakukan saat tiket masih dalam tahap verifikasi awal. Masukkan <strong>6 digit PIN Keamanan</strong> yang tertera pada Struk Tanda Terima Anda.
            </div>

            <form id="form-cancel-ticket" method="POST" action="" class="space-y-4" onsubmit="handleBtnLoading(this, 'Membatalkan Tiket...')">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        Alasan Pembatalan <span class="text-rose-500">*</span>
                    </label>
                    <textarea name="alasan_pembatalan" rows="3" required minlength="5" maxlength="500"
                        placeholder="Contoh: Kendala sudah teratasi sendiri / Salah memilih kategori layanan..."
                        class="w-full rounded-xl border border-slate-300 text-xs sm:text-sm p-3 focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition"></textarea>
                    <p class="text-[11px] text-slate-400 mt-1">Minimal 5 karakter, maksimal 500 karakter.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        PIN Keamanan 6 Digit <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="pin" required maxlength="6" pattern="[0-9]{6}" inputmode="numeric"
                        placeholder="6 Digit PIN pada Struk"
                        class="w-full rounded-xl border border-slate-300 text-center font-mono font-bold tracking-widest text-lg p-2.5 focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition text-slate-900 bg-slate-50">
                    <p class="text-[11px] text-slate-400 mt-1">
                        *PIN tertera di kotak emas Struk Tanda Terima Digital saat pengajuan tiket.
                    </p>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeCancelTicketModal()"
                        class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition cursor-pointer">
                        Kembali
                    </button>
                    <button type="submit"
                        class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-md shadow-rose-600/20 transition flex items-center gap-1.5 cursor-pointer">
                        <span>🚫</span>
                        <span>Ya, Batalkan Tiket</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════
     VANILLA JAVASCRIPT: TAB TOGGLE, DYNAMIC SLA, MODAL & COPY
     ══════════════════════════════════════════════════════════════════════ -->
<script>
    // 1. Tab Switching & Navbar Toggle
    let currentPortalTab = '{{ ($activeTab ?? "buat") }}';

    function togglePortalTab() {
        if (currentPortalTab === 'buat') {
            switchPortalTab('lacak');
        } else {
            switchPortalTab('buat');
        }
    }

    function switchPortalTab(tabName) {
        currentPortalTab = tabName;
        const buatSection = document.getElementById('section-buat-laporan');
        const lacakSection = document.getElementById('section-lacak-status');
        const navBtnIcon = document.getElementById('nav-btn-icon');
        const navBtnLabel = document.getElementById('nav-btn-label');
        const flashError = document.getElementById('flash-error-alert');

        // Sembunyikan notifikasi error yang tersisa saat berganti tab agar form bersih
        if (flashError) {
            flashError.style.display = 'none';
        }

        if (buatSection && lacakSection) {
            if (tabName === 'buat') {
                buatSection.classList.remove('hidden');
                lacakSection.classList.add('hidden');
                if (navBtnIcon) navBtnIcon.textContent = '🔍';
                if (navBtnLabel) navBtnLabel.textContent = 'Lacak Status Tiket';
                window.history.replaceState(null, '', '{{ route("public.index") }}');
            } else {
                buatSection.classList.add('hidden');
                lacakSection.classList.remove('hidden');
                if (navBtnIcon) navBtnIcon.textContent = '✍️';
                if (navBtnLabel) navBtnLabel.textContent = 'Buat Pengaduan Baru';
                window.history.replaceState(null, '', '{{ route("public.index") }}?tab=lacak');
            }
            window.scrollTo({ top: 120, behavior: 'smooth' });
        } else {
            window.location.href = '{{ route("public.index") }}' + (tabName === 'buat' ? '' : '?tab=' + tabName);
        }
    }

    function handleBtnLoading(form, loadingText = 'Memproses...') {
        const btn = form.querySelector('button[type="submit"]');
        if (btn) {
            btn.disabled = true;
            btn.classList.add('opacity-75', 'cursor-not-allowed');
            btn.innerHTML = `<span>⏳</span> <span>${loadingText}</span>`;
        }
        return true;
    }

    // ══════════════════════════════════════════════════════════════════════
    // 2. SEARCHABLE & SCROLLABLE COMBOBOX: SKPD / PERANGKAT DAERAH
    // ══════════════════════════════════════════════════════════════════════
    function openSkpdDropdown() {
        const list = document.getElementById('skpd_dropdown_list');
        const icon = document.getElementById('skpd_chevron_icon');
        if (list) {
            list.classList.remove('hidden');
            if (icon) icon.classList.add('rotate-180');
            const searchInput = document.getElementById('skpd_search_input');
            filterSkpdOptions(searchInput ? searchInput.value : '');
        }
    }

    function closeSkpdDropdown() {
        const list = document.getElementById('skpd_dropdown_list');
        const icon = document.getElementById('skpd_chevron_icon');
        if (list) list.classList.add('hidden');
        if (icon) icon.classList.remove('rotate-180');
    }

    function toggleSkpdDropdown() {
        const list = document.getElementById('skpd_dropdown_list');
        if (!list) return;
        if (list.classList.contains('hidden')) {
            openSkpdDropdown();
            document.getElementById('skpd_search_input')?.focus();
        } else {
            closeSkpdDropdown();
        }
    }

    function handleSkpdSearchInput(query) {
        const clearBtn = document.getElementById('skpd_clear_btn');
        const hiddenInput = document.getElementById('skpd');
        const q = (query || '').trim();

        if (clearBtn) {
            if (q.length > 0) clearBtn.classList.remove('hidden');
            else clearBtn.classList.add('hidden');
        }

        // Sinkronisasi langsung nilai teks yang diketik ke input form
        if (hiddenInput) {
            hiddenInput.value = q;
        }

        openSkpdDropdown();
        filterSkpdOptions(q);
    }

    function filterSkpdOptions(query) {
        const q = (query || '').toLowerCase().trim();
        const items = document.querySelectorAll('.skpd-option');
        const customOpt = document.getElementById('skpd_custom_option');
        const customLabel = document.getElementById('skpd_custom_label');
        let visibleCount = 0;
        let exactMatch = false;

        items.forEach(item => {
            const val = item.getAttribute('data-value') || '';
            const text = item.textContent.toLowerCase();
            if (!q || text.includes(q)) {
                item.classList.remove('hidden');
                visibleCount++;
                if (text === q || val.toLowerCase() === q) {
                    exactMatch = true;
                }
            } else {
                item.classList.add('hidden');
            }
        });

        // Tampilkan opsi ketik kustom langsung jika pengguna mengetik nama yang belum ada di daftar
        if (customOpt && customLabel) {
            if (q.length > 0 && !exactMatch) {
                customLabel.textContent = query.trim();
                customOpt.classList.remove('hidden');
            } else {
                customOpt.classList.add('hidden');
            }
        }

        const emptyState = document.getElementById('skpd_empty_state');
        if (emptyState) {
            if (visibleCount === 0 && q.length > 0) {
                emptyState.classList.remove('hidden');
            } else {
                emptyState.classList.add('hidden');
            }
        }
    }

    function selectSkpd(val) {
        const hiddenInput = document.getElementById('skpd');
        const searchInput = document.getElementById('skpd_search_input');
        const clearBtn = document.getElementById('skpd_clear_btn');

        if (hiddenInput) hiddenInput.value = val;
        if (searchInput) {
            searchInput.value = val;
            searchInput.classList.remove('border-rose-500', 'ring-2', 'ring-rose-200');
        }
        if (clearBtn) clearBtn.classList.remove('hidden');

        document.querySelectorAll('.skpd-option').forEach(item => {
            const itemVal = item.getAttribute('data-value');
            if (itemVal === val) {
                item.classList.add('bg-blue-50', 'font-bold', 'text-blue-900');
            } else {
                item.classList.remove('bg-blue-50', 'font-bold', 'text-blue-900');
            }
        });

        closeSkpdDropdown();
    }

    function selectSkpdCustom() {
        const searchInput = document.getElementById('skpd_search_input');
        const hiddenInput = document.getElementById('skpd');
        const clearBtn = document.getElementById('skpd_clear_btn');
        const val = (searchInput ? searchInput.value : '').trim();

        if (val) {
            if (hiddenInput) hiddenInput.value = val;
            if (clearBtn) clearBtn.classList.remove('hidden');
            if (searchInput) searchInput.classList.remove('border-rose-500', 'ring-2', 'ring-rose-200');
        }
        closeSkpdDropdown();
    }

    function clearSkpdSelection() {
        const hiddenInput = document.getElementById('skpd');
        const searchInput = document.getElementById('skpd_search_input');
        const clearBtn = document.getElementById('skpd_clear_btn');
        const customOpt = document.getElementById('skpd_custom_option');

        if (hiddenInput) hiddenInput.value = '';
        if (searchInput) {
            searchInput.value = '';
            searchInput.focus();
        }
        if (clearBtn) clearBtn.classList.add('hidden');
        if (customOpt) customOpt.classList.add('hidden');

        document.querySelectorAll('.skpd-option').forEach(item => {
            item.classList.remove('bg-blue-50', 'font-bold', 'text-blue-900');
            item.classList.remove('hidden');
        });
        const emptyState = document.getElementById('skpd_empty_state');
        if (emptyState) emptyState.classList.add('hidden');

        openSkpdDropdown();
    }

    // ══════════════════════════════════════════════════════════════════════
    // 3. SEARCHABLE & SCROLLABLE COMBOBOX: KATEGORI LAPORAN (FLAT DIRECT LIST)
    // ══════════════════════════════════════════════════════════════════════
    function openCatDropdown() {
        const list = document.getElementById('cat_dropdown_list');
        const icon = document.getElementById('cat_chevron_icon');
        if (list) {
            list.classList.remove('hidden');
            if (icon) icon.classList.add('rotate-180');
            const searchInput = document.getElementById('cat_search_input');
            filterCatOptions(searchInput ? searchInput.value : '');
        }
    }

    function closeCatDropdown() {
        const list = document.getElementById('cat_dropdown_list');
        const icon = document.getElementById('cat_chevron_icon');
        if (list) list.classList.add('hidden');
        if (icon) icon.classList.remove('rotate-180');
    }

    function toggleCatDropdown() {
        const list = document.getElementById('cat_dropdown_list');
        if (!list) return;
        if (list.classList.contains('hidden')) {
            openCatDropdown();
            document.getElementById('cat_search_input')?.focus();
        } else {
            closeCatDropdown();
        }
    }

    function handleCatSearchInput(query) {
        const clearBtn = document.getElementById('cat_clear_btn');
        const q = (query || '').trim();

        if (clearBtn) {
            if (q.length > 0) clearBtn.classList.remove('hidden');
            else clearBtn.classList.add('hidden');
        }

        openCatDropdown();
        filterCatOptions(q);
    }

    function filterCatOptions(query) {
        const q = (query || '').toLowerCase().trim();
        const options = document.querySelectorAll('.cat-option');
        const customOpt = document.getElementById('cat_custom_option');
        const customLabel = document.getElementById('cat_custom_label');
        let visibleCount = 0;
        let exactMatch = false;

        options.forEach(opt => {
            const name = (opt.getAttribute('data-name') || '').toLowerCase();
            if (!q || name.includes(q)) {
                opt.classList.remove('hidden');
                visibleCount++;
                if (name === q) {
                    exactMatch = true;
                }
            } else {
                opt.classList.add('hidden');
            }
        });

        // Tampilkan opsi ketik kustom langsung jika pengguna mengetik kategori baru
        if (customOpt && customLabel) {
            if (q.length > 0 && !exactMatch) {
                customLabel.textContent = query.trim();
                customOpt.classList.remove('hidden');
            } else {
                customOpt.classList.add('hidden');
            }
        }

        const emptyState = document.getElementById('cat_empty_state');
        if (emptyState) {
            if (visibleCount === 0 && q.length > 0) {
                emptyState.classList.remove('hidden');
            } else {
                emptyState.classList.add('hidden');
            }
        }
    }

    function selectCat(id, name) {
        const hiddenInput = document.getElementById('category_id');
        const customInput = document.getElementById('custom_category_name');
        const searchInput = document.getElementById('cat_search_input');
        const clearBtn = document.getElementById('cat_clear_btn');

        if (hiddenInput) hiddenInput.value = id;
        if (customInput) customInput.value = '';
        if (searchInput) {
            searchInput.value = name;
            searchInput.classList.remove('border-rose-500', 'ring-2', 'ring-rose-200');
        }
        if (clearBtn) clearBtn.classList.remove('hidden');

        document.querySelectorAll('.cat-option').forEach(item => {
            const itemId = item.getAttribute('data-id');
            if (itemId == id) {
                item.classList.add('bg-blue-50', 'font-bold', 'text-blue-900');
            } else {
                item.classList.remove('bg-blue-50', 'font-bold', 'text-blue-900');
            }
        });

        closeCatDropdown();
    }

    function selectCatCustom(fallbackId) {
        const searchInput = document.getElementById('cat_search_input');
        const hiddenInput = document.getElementById('category_id');
        const customInput = document.getElementById('custom_category_name');
        const clearBtn = document.getElementById('cat_clear_btn');
        const val = (searchInput ? searchInput.value : '').trim();

        if (val) {
            if (hiddenInput) hiddenInput.value = fallbackId;
            if (customInput) customInput.value = val;
            if (clearBtn) clearBtn.classList.remove('hidden');
            if (searchInput) searchInput.classList.remove('border-rose-500', 'ring-2', 'ring-rose-200');
        }
        closeCatDropdown();
    }

    function clearCatSelection() {
        const hiddenInput = document.getElementById('category_id');
        const customInput = document.getElementById('custom_category_name');
        const searchInput = document.getElementById('cat_search_input');
        const clearBtn = document.getElementById('cat_clear_btn');
        const customOpt = document.getElementById('cat_custom_option');

        if (hiddenInput) hiddenInput.value = '';
        if (customInput) customInput.value = '';
        if (searchInput) {
            searchInput.value = '';
            searchInput.focus();
        }
        if (clearBtn) clearBtn.classList.add('hidden');
        if (customOpt) customOpt.classList.add('hidden');

        document.querySelectorAll('.cat-option').forEach(item => {
            item.classList.remove('bg-blue-50', 'font-bold', 'text-blue-900');
            item.classList.remove('hidden');
        });
        const emptyState = document.getElementById('cat_empty_state');
        if (emptyState) emptyState.classList.add('hidden');

        openCatDropdown();
    }

    // Auto-tutup dropdown combobox saat klik di luar kontainer & rotasi panah chevron
    document.addEventListener('click', function(e) {
        const skpdContainer = document.getElementById('skpd_search_container');
        if (skpdContainer && !skpdContainer.contains(e.target)) {
            closeSkpdDropdown();
            const hiddenSkpd = document.getElementById('skpd');
            const searchSkpd = document.getElementById('skpd_search_input');
            if (hiddenSkpd && searchSkpd) {
                if (hiddenSkpd.value) {
                    searchSkpd.value = hiddenSkpd.value;
                } else if (searchSkpd.value.trim().length > 0) {
                    hiddenSkpd.value = searchSkpd.value.trim();
                } else {
                    searchSkpd.value = '';
                    const clearBtn = document.getElementById('skpd_clear_btn');
                    if (clearBtn) clearBtn.classList.add('hidden');
                }
            }
        }

        const catContainer = document.getElementById('cat_search_container');
        if (catContainer && !catContainer.contains(e.target)) {
            closeCatDropdown();
            const hiddenCat = document.getElementById('category_id');
            const searchCat = document.getElementById('cat_search_input');
            const customCat = document.getElementById('custom_category_name');
            if (hiddenCat && searchCat) {
                if (hiddenCat.value && !customCat?.value) {
                    const selectedOption = document.querySelector(`.cat-option[data-id="${hiddenCat.value}"]`);
                    if (selectedOption) {
                        searchCat.value = selectedOption.getAttribute('data-name') || '';
                    }
                } else if (customCat?.value) {
                    searchCat.value = customCat.value;
                } else if (searchCat.value.trim().length > 0) {
                    // Simpan ketikan bebas jika belum terpilih
                    hiddenCat.value = '{{ $fallbackCatId }}';
                    if (customCat) customCat.value = searchCat.value.trim();
                } else {
                    searchCat.value = '';
                    const clearBtn = document.getElementById('cat_clear_btn');
                    if (clearBtn) clearBtn.classList.add('hidden');
                }
            }
        }
    });

    // Keyboard support: Escape untuk menutup dropdown
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeSkpdDropdown();
            closeCatDropdown();
        }
    });

    // ══════════════════════════════════════════════════════════════════════
    // FORM VALIDATION & CONTROLS
    // ══════════════════════════════════════════════════════════════════════

    function validatePublicTicketForm(form) {
        // 1. Validasi NIP (Wajib Tepat 18 Digit)
        const nipElem = document.getElementById('nip_pelapor');
        if (nipElem && nipElem.value.trim().length !== 18) {
            nipElem.focus();
            nipElem.classList.add('border-rose-500', 'ring-2', 'ring-rose-200');
            alert('NIP / NIPK Pegawai wajib tepat 18 digit angka sesuai format resmi BKN.');
            return false;
        }

        // 2. Validasi SKPD / Perangkat Daerah (Mendukung input bebas langsung)
        const skpdHidden = document.getElementById('skpd');
        const skpdSearch = document.getElementById('skpd_search_input');
        if (skpdSearch && skpdSearch.value.trim().length > 0) {
            skpdHidden.value = skpdSearch.value.trim();
        }
        if (!skpdHidden || !skpdHidden.value.trim()) {
            if (skpdSearch) {
                skpdSearch.focus();
                skpdSearch.classList.add('border-rose-500', 'ring-2', 'ring-rose-200');
            }
            alert('Silakan pilih dari daftar atau ketikkan Perangkat Daerah (OPD) tempat Anda bertugas.');
            return false;
        }

        // 3. Validasi Kategori Layanan (Mendukung input bebas langsung)
        const catId = document.getElementById('category_id');
        const catSearch = document.getElementById('cat_search_input');
        const customCat = document.getElementById('custom_category_name');
        if (catSearch && catSearch.value.trim().length > 0 && (!catId.value || catId.value === '')) {
            catId.value = '{{ $fallbackCatId }}';
            if (customCat) customCat.value = catSearch.value.trim();
        }
        if (!catId || !catId.value) {
            if (catSearch) {
                catSearch.focus();
                catSearch.classList.add('border-rose-500', 'ring-2', 'ring-rose-200');
            }
            alert('Silakan pilih dari daftar atau ketikkan Kategori Masalah / Layanan DKIS terlebih dahulu.');
            return false;
        }

        // 4. Validasi Keamanan Google reCAPTCHA
        if (typeof grecaptcha !== 'undefined') {
            const recaptchaResp = grecaptcha.getResponse();
            if (!recaptchaResp || recaptchaResp.trim().length === 0) {
                alert('Harap centang verifikasi keamanan "Saya bukan robot" (reCAPTCHA) sebelum mengirim tiket.');
                const recaptchaBox = document.querySelector('.g-recaptcha');
                if (recaptchaBox) {
                    recaptchaBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return false;
            }
        }

        return true;
    }

    // 3. Filter NIP Tepat Angka & Maksimal 18 Digit
    function handleNipInput(inputElem) {
        if (!inputElem) return;
        inputElem.value = inputElem.value.replace(/[^0-9]/g, '').slice(0, 18);
    }

    // 4. Unggah Banyak Bukti (Multiple File Upload) dengan Chips & Validasi Klien
    let selectedFilesList = [];

    function handleMultipleFiles(inputElem) {
        const errorAlert = document.getElementById('file-error-alert');
        const errorMessage = document.getElementById('file-error-message');
        if (errorAlert) errorAlert.classList.add('hidden');

        const allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
        const maxSingleSizeBytes = 5 * 1024 * 1024; // 5MB per berkas
        const maxTotalSizeBytes = 10 * 1024 * 1024; // 10MB total akumulasi
        const maxTotalFiles = 5;

        const newFiles = Array.from(inputElem.files);
        let hasError = false;
        let errorText = '';

        for (let f of newFiles) {
            const ext = f.name.split('.').pop().toLowerCase();
            if (!allowedExtensions.includes(ext)) {
                hasError = true;
                errorText = `Format berkas "${f.name}" tidak diizinkan. Hanya menerima format JPG, PNG, atau PDF.`;
                break;
            }
            if (f.size > maxSingleSizeBytes) {
                hasError = true;
                errorText = `Ukuran berkas "${f.name}" (${(f.size / 1024 / 1024).toFixed(2)} MB) melebihi batas maksimal 5 MB per berkas.`;
                break;
            }
        }

        if (hasError) {
            if (errorMessage) errorMessage.textContent = errorText;
            if (errorAlert) errorAlert.classList.remove('hidden');
            syncInputFiles(inputElem);
            return;
        }

        // Simulasikan daftar gabungan untuk mengecek batas akumulasi total ukuran dan jumlah
        let simulatedList = [...selectedFilesList];
        for (let f of newFiles) {
            if (!simulatedList.some(item => item.name === f.name && item.size === f.size)) {
                if (simulatedList.length >= maxTotalFiles) {
                    hasError = true;
                    errorText = `Maksimal hanya dapat mengunggah ${maxTotalFiles} berkas lampiran.`;
                    break;
                }
                simulatedList.push(f);
            }
        }

        const totalSizeBytes = simulatedList.reduce((acc, curr) => acc + curr.size, 0);
        if (totalSizeBytes > maxTotalSizeBytes) {
            hasError = true;
            errorText = `Total ukuran seluruh berkas lampiran (${(totalSizeBytes / 1024 / 1024).toFixed(2)} MB) melebihi batas maksimal akumulasi 10 MB.`;
        }

        if (hasError) {
            if (errorMessage) errorMessage.textContent = errorText;
            if (errorAlert) errorAlert.classList.remove('hidden');
            syncInputFiles(inputElem);
            return;
        }

        selectedFilesList = simulatedList;
        syncInputFiles(inputElem);
        renderFileListChips();
    }

    function syncInputFiles(inputElem) {
        if (!inputElem) inputElem = document.getElementById('lampiran_pelapor');
        if (!inputElem) return;

        try {
            const dt = new DataTransfer();
            selectedFilesList.forEach(file => dt.items.add(file));
            inputElem.files = dt.files;
        } catch (e) {
            console.warn('DataTransfer not supported in this environment', e);
        }
    }

    function removeSelectedFile(index) {
        selectedFilesList.splice(index, 1);
        const inputElem = document.getElementById('lampiran_pelapor');
        syncInputFiles(inputElem);
        renderFileListChips();

        const errorAlert = document.getElementById('file-error-alert');
        if (errorAlert && selectedFilesList.length < 5) {
            errorAlert.classList.add('hidden');
        }
    }

    function renderFileListChips() {
        const container = document.getElementById('file-list-preview');
        if (!container) return;
        container.innerHTML = '';

        selectedFilesList.forEach((file, idx) => {
            const ext = file.name.split('.').pop().toLowerCase();
            const isPdf = ext === 'pdf';
            const sizeMb = (file.size / 1024 / 1024).toFixed(2);

            const chip = document.createElement('div');
            chip.className = 'inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-blue-50 border border-blue-200 text-blue-900 text-xs font-medium shadow-2xs animate-in fade-in';
            chip.innerHTML = `
                <span>${isPdf ? '📄' : '🖼️'}</span>
                <span class="max-w-[180px] truncate" title="${file.name}">${file.name}</span>
                <span class="text-[10px] text-blue-600 font-bold uppercase">(${sizeMb} MB)</span>
                <button type="button" onclick="removeSelectedFile(${idx})" class="w-4 h-4 rounded-full bg-blue-200/80 hover:bg-rose-500 hover:text-white flex items-center justify-center text-[10px] font-bold text-blue-800 transition cursor-pointer" title="Hapus berkas">✕</button>
            `;
            container.appendChild(chip);
        });

        if (selectedFilesList.length > 0) {
            const totalBytes = selectedFilesList.reduce((sum, f) => sum + f.size, 0);
            const totalMb = (totalBytes / 1024 / 1024).toFixed(2);
            const summary = document.createElement('div');
            summary.className = 'w-full text-[11px] font-semibold text-slate-500 pt-1 flex items-center justify-between border-t border-slate-100 mt-1';
            summary.innerHTML = `<span>Total: <strong>${selectedFilesList.length}</strong> / 5 berkas</span><span class="${totalBytes > 10 * 1024 * 1024 ? 'text-rose-600 font-bold' : 'text-slate-600'}">Total Ukuran: <strong>${totalMb} MB</strong> / maks 10 MB</span>`;
            container.appendChild(summary);
        }
    }

    // 5. Star Rating Selector Interaktif untuk Konfirmasi Pelapor
    const ratingDescriptions = {
        1: '⭐ 1: Sangat Kecewa',
        2: '⭐⭐ 2: Kurang Puas',
        3: '⭐⭐⭐ 3: Cukup / Standar',
        4: '⭐⭐⭐⭐ 4: Puas',
        5: '⭐⭐⭐⭐⭐ 5: Sangat Puas & Responsif'
    };
    let currentSelectedRating = 5;

    function setPublicRating(star) {
        currentSelectedRating = star;
        const input = document.getElementById('selected-rating-val');
        if (input) input.value = star;

        const badge = document.getElementById('rating-desc-badge');
        if (badge && ratingDescriptions[star]) {
            badge.textContent = ratingDescriptions[star];
        }
        updateStarUI(star);
    }

    function hoverPublicRating(star) {
        const badge = document.getElementById('rating-desc-badge');
        if (badge && ratingDescriptions[star]) {
            badge.textContent = ratingDescriptions[star];
        }
        updateStarUI(star);
    }

    function resetPublicRatingHover() {
        const badge = document.getElementById('rating-desc-badge');
        if (badge && ratingDescriptions[currentSelectedRating]) {
            badge.textContent = ratingDescriptions[currentSelectedRating];
        }
        updateStarUI(currentSelectedRating);
    }

    function updateStarUI(val) {
        const starBtns = document.querySelectorAll('.star-rating-btn');
        starBtns.forEach(btn => {
            const starNum = parseInt(btn.getAttribute('data-star') || '0', 10);
            if (starNum <= val) {
                btn.classList.add('text-amber-400');
                btn.classList.remove('text-slate-300');
            } else {
                btn.classList.remove('text-amber-400');
                btn.classList.add('text-slate-300');
            }
        });
    }

    // Inisialisasi status form saat halaman dimuat
    document.addEventListener('DOMContentLoaded', function() {
        const nipInput = document.getElementById('nip_pelapor');
        if (nipInput) {
            handleNipInput(nipInput);
        }
    });

    // 4. Modal Popup Controls & Clipboard Copy
    function closeSuccessModal() {
        const modal = document.getElementById('modal-success-ticket');
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    function openCancelTicketModal(kodeTiket) {
        const modal = document.getElementById('modal-cancel-ticket');
        const form = document.getElementById('form-cancel-ticket');
        const codeDisplay = document.getElementById('cancel-ticket-code-display');
        if (form) {
            form.action = '/tiket/' + encodeURIComponent(kodeTiket) + '/batal';
        }
        if (codeDisplay) {
            codeDisplay.textContent = kodeTiket;
        }
        if (modal) {
            modal.classList.remove('hidden');
        }
    }

    function closeCancelTicketModal() {
        const modal = document.getElementById('modal-cancel-ticket');
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    function copyTicketCode(code) {
        navigator.clipboard.writeText(code).then(() => {
            const btnText = document.getElementById('copy-btn-text');
            const btnIcon = document.getElementById('copy-btn-icon');
            btnText.textContent = 'Kode Disalin!';
            btnIcon.textContent = '✅';
            setTimeout(() => {
                btnText.textContent = 'Salin Kode';
                btnIcon.textContent = '📋';
            }, 3000);
        }).catch(err => {
            alert('Kode tiket: ' + code);
        });
    }

    // 5. Cetak Struk Tanda Terima Digital Resmi (Sesuai Desain Standar)
    function triggerPrintStruk(data) {
        const w = window.open('', '_blank', 'width=580,height=720');
        if (!w) {
            alert('Mohon izinkan popup di browser Anda untuk mencetak tanda terima.');
            return;
        }
        const pinFormatted = (data.pin || '882910').split('').join(' ');
        w.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Struk Tanda Terima - ${data.kode_tiket}</title>
                <style>
                    @page { size: auto; margin: 10mm; }
                    body {
                        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                        background-color: #ffffff;
                        color: #0f172a;
                        margin: 0;
                        padding: 24px;
                        font-size: 13px;
                        line-height: 1.5;
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                    }
                    .container {
                        max-width: 480px;
                        margin: 0 auto;
                        border: 2px dashed #94a3b8;
                        border-radius: 8px;
                        padding: 20px;
                        background-color: #ffffff;
                    }
                    .header { text-align: center; font-weight: 800; margin-bottom: 8px; text-transform: uppercase; font-size: 13px; color: #0f172a; line-height: 1.3; }
                    .divider { border-top: 1px solid #cbd5e1; margin: 10px 0; }
                    .title { text-align: center; font-weight: 800; font-size: 13px; letter-spacing: 0.5px; color: #0f172a; }
                    .row { display: flex; margin-bottom: 4px; }
                    .label { width: 150px; color: #475569; font-weight: 600; }
                    .sep { width: 14px; color: #64748b; }
                    .val { flex: 1; color: #0f172a; font-weight: 700; }
                    .sec-head { font-weight: 800; color: #0f172a; margin-top: 12px; margin-bottom: 4px; font-size: 12px; }
                    .pin-box { margin-top: 10px; padding: 10px 12px; border: 1.5px dashed #d97706; background-color: #fef3c7; color: #92400e; font-weight: 800; border-radius: 8px; }
                    .pin-digits { letter-spacing: 5px; color: #0f172a; font-weight: 900; font-size: 16px; background: #ffffff; padding: 2px 8px; border-radius: 4px; border: 1px solid #fcd34d; margin-left: 6px; }
                    .note { font-size: 10.5px; color: #78350f; margin-top: 8px; font-style: italic; line-height: 1.4; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <div>PEMERINTAH DAERAH KOTA CIREBON</div>
                        <div>DINAS KOMUNIKASI, INFORMATIKA, DAN STATISTIK</div>
                    </div>
                    <div class="divider"></div>
                    <div class="title">BUKTI TANDA TERIMA PENGAJUAN TIKET</div>
                    <div class="divider"></div>

                    <div class="row"><div class="label">KODE TIKET RESMI</div><div class="sep">:</div><div class="val" style="color:#1d4ed8; font-weight:900;">${data.kode_tiket}</div></div>
                    <div class="row"><div class="label">Waktu Pendaftaran</div><div class="sep">:</div><div class="val">${data.waktu}</div></div>

                    <div class="sec-head">IDENTITAS PELAPOR</div>
                    <div class="row"><div class="label">• Nama Pegawai</div><div class="sep">:</div><div class="val">${data.nama}</div></div>
                    <div class="row"><div class="label">• NIP Pegawai</div><div class="sep">:</div><div class="val">${data.nip}</div></div>
                    <div class="row"><div class="label">• Instansi / SKPD</div><div class="sep">:</div><div class="val">${data.skpd}</div></div>

                    <div class="sec-head">RINCIAN KENDALA</div>
                    <div class="row"><div class="label">• Kategori Masalah</div><div class="sep">:</div><div class="val">${data.kategori}</div></div>
                    <div class="row"><div class="label">• Judul Kendala</div><div class="sep">:</div><div class="val">${data.judul}</div></div>

                    <div class="divider"></div>
                    <div class="pin-box">
                        🔒 PIN KEAMANAN TIKET ANDA : <span class="pin-digits">[ ${pinFormatted} ]</span>
                    </div>
                    <div class="note">
                        *Gunakan PIN di atas saat ingin Membatalkan Tiket, Melengkapi Data, atau Mengonfirmasi Selesai setelah perbaikan teknisi.
                    </div>
                </div>
                <script>
                    window.onload = function() {
                        window.print();
                    };
                <\/script>
            </body>
            </html>
        `);
        w.document.close();
    }
</script>
@endsection

@push('scripts')
<script src="https://www.google.com/recaptcha/api.js?hl=id" async defer></script>
@endpush
