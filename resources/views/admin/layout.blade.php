<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Panel Admin' }} — DKIS Kota Cirebon</title>
    <!-- Performance: DNS Prefetch & Google Fonts Preconnect -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!-- Non-render-blocking font loading with swap -->
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap"></noscript>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            text-rendering: optimizeLegibility;
            -webkit-font-smoothing: antialiased;
        }
        .scrollbar-thin::-webkit-scrollbar { width: 6px; height: 6px; }
        .scrollbar-thin::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .contain-paint { contain: paint; }

        /* Unified Admin Verification Modal Styles */
        .admin-verify-modal-wrapper {
            position: fixed !important;
            inset: 0 !important;
            z-index: 9999 !important;
            background-color: rgba(15, 23, 42, 0.8) !important;
            backdrop-filter: blur(4px) !important;
            -webkit-backdrop-filter: blur(4px) !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 1rem !important;
            box-sizing: border-box !important;
        }
        #admin-confirm-modal {
            z-index: 99999 !important;
        }
        .admin-verify-modal-wrapper.hidden {
            display: none !important;
        }
        .admin-verify-modal-card {
            background: #ffffff !important;
            width: 95% !important;
            max-width: 1140px !important;
            max-height: calc(100vh - 2.5rem) !important;
            border-radius: 1.5rem !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4) !important;
            border: 1px solid #cbd5e1 !important;
            display: flex !important;
            flex-direction: column !important;
            overflow: hidden !important;
            margin: auto !important;
        }
        .admin-verify-modal-grid {
            display: grid !important;
            grid-template-columns: 58% calc(42% - 1.25rem) !important;
            gap: 1.25rem !important;
            align-items: start !important;
        }
        @media (max-width: 960px) {
            .admin-verify-modal-grid {
                grid-template-columns: 100% !important;
            }
            .admin-verify-modal-card {
                max-height: calc(100vh - 1rem) !important;
                border-radius: 1rem !important;
                width: 98% !important;
            }
        }

        @media print {
            html, body {
                overflow: visible !important;
                height: auto !important;
                min-height: 100% !important;
                background: #ffffff !important;
                color: #000000 !important;
            }
            .flex-1, .h-screen, .h-full, .overflow-hidden, .overflow-y-auto {
                height: auto !important;
                overflow: visible !important;
            }
            header, aside, #admin-top-loader, #mobile-sidebar-toggle, .no-print {
                display: none !important;
            }
            #main-container {
                overflow: visible !important;
                height: auto !important;
                padding: 0 !important;
                margin: 0 !important;
                background: #ffffff !important;
                max-width: 100% !important;
                width: 100% !important;
            }
            .print-break-inside-avoid {
                break-inside: avoid !important;
                page-break-inside: avoid !important;
            }
            @page {
                size: A4 portrait;
                margin: 1.5cm 1.5cm 1.5cm 1.5cm;
            }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 antialiased h-screen overflow-hidden flex flex-col">
    <!-- Top Progress Bar untuk Navigasi Instan -->
    <div id="admin-top-loader" class="fixed top-0 left-0 h-1 bg-gradient-to-r from-sky-400 via-blue-500 to-indigo-500 z-50 transition-all duration-200 pointer-events-none opacity-0" style="width: 0%;"></div>

    @php
        $user = $user ?? Auth::user();
        $isPusat = $user && $user->isAdminPusat();
        $sidebarBg = $isPusat ? '#08182f' : '#1e3a5f';
    @endphp

    <!-- ================================================================= -->
    <!-- TOPBAR (Background #0f274a) -->
    <!-- ================================================================= -->
    <header class="bg-[#0f274a] text-white px-4 sm:px-6 py-3.5 flex items-center justify-between border-b border-blue-900/60 flex-shrink-0 z-40 shadow-md">
        <div class="flex items-center gap-3 sm:gap-4">
            <!-- Mobile Hamburger Toggle -->
            <button type="button" id="mobile-sidebar-toggle" onclick="toggleMobileSidebar()" class="lg:hidden p-2 rounded-lg text-sky-200 hover:text-white hover:bg-white/10 focus:outline-none transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            <!-- Brand Logo -->
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/logo-dkis.png') }}" alt="Logo DKIS" class="h-9 w-auto object-contain">
                <div>
                    <h1 class="font-extrabold text-sm tracking-tight flex items-center gap-2">
                        <span>DKIS ADMIN CONSOLE</span>
                        @if(!$isPusat && $user?->bidang)
                            <span class="hidden md:inline-block text-[10px] font-mono bg-emerald-500/20 border border-emerald-400/30 text-emerald-300 px-2 py-0.5 rounded uppercase">
                                {{ $user->bidang->nama_bidang }}
                            </span>
                        @endif
                    </h1>
                    <p class="text-[11px] text-sky-200/70 hidden sm:block">
                        {{ $isPusat ? 'Pusat Tata Kelola Pengaduan ASN Kota Cirebon' : 'Pengelolaan Tiket Bidang ' . ($user?->bidang?->nama_bidang ?? '') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Topbar Right Profile & Actions -->
        <div class="flex items-center gap-2.5 sm:gap-3">
            @if($isPusat)
                <span class="text-[11px] font-bold bg-sky-900/70 border border-sky-400/30 text-sky-300 px-3 py-1 rounded-full uppercase tracking-wider hidden sm:inline-flex items-center gap-1.5 shadow-xs">
                    <span class="w-2 h-2 rounded-full bg-sky-400 animate-pulse"></span>
                    ADMIN PUSAT
                </span>
            @else
                <span class="text-[11px] font-bold bg-emerald-950/80 border border-emerald-400/40 text-emerald-300 px-3 py-1 rounded-full uppercase tracking-wider hidden sm:inline-flex items-center gap-1.5 shadow-xs">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    ADMIN BIDANG
                </span>
            @endif

            <!-- User Inisial Badge -->
            <div class="flex items-center gap-2 pl-2 sm:border-l sm:border-blue-900/60">
                <div class="w-8 h-8 rounded-lg bg-sky-500/30 border border-sky-300/40 text-white font-bold text-xs flex items-center justify-center shadow-sm">
                    {{ $user?->avatar ?? 'AD' }}
                </div>
                <div class="hidden md:block text-left">
                    <div class="text-xs font-bold text-white leading-tight">{{ $user?->nama ?? 'Admin' }}</div>
                    <div class="text-[10px] text-sky-300/70 truncate max-w-[120px]">{{ $user?->username ?? 'admin@cirebonkota.go.id' }}</div>
                </div>
            </div>

            <!-- Logout Button -->
            <form action="{{ route('logout') }}" method="POST" class="inline">
                @csrf
                <button type="submit" title="Keluar dari sistem" class="text-xs bg-slate-800/80 hover:bg-rose-600 text-slate-300 hover:text-white px-2.5 sm:px-3 py-1.5 rounded-lg border border-slate-700/80 transition flex items-center gap-1.5 shadow-xs">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span class="hidden sm:inline">Keluar</span>
                </button>
            </form>
        </div>
    </header>

    <div class="flex flex-1 h-[calc(100vh-61px)] overflow-hidden relative">
        <!-- ================================================================= -->
        <!-- MOBILE BACKDROP -->
        <!-- ================================================================= -->
        <div id="mobile-sidebar-backdrop" onclick="toggleMobileSidebar()" class="fixed inset-0 bg-slate-950/60 z-30 hidden lg:hidden transition-opacity"></div>

        <!-- ================================================================= -->
        <!-- SIDEBAR (Background #08182f untuk Pusat, #1e3a5f untuk Bidang) -->
        <!-- ================================================================= -->
        <aside id="sidebar" style="background-color: {{ $sidebarBg }};" class="fixed inset-y-0 left-0 top-[61px] z-30 w-64 text-slate-300 flex flex-col justify-between p-4 border-r border-slate-800/80 transform -translate-x-full lg:translate-x-0 lg:static lg:h-full lg:overflow-y-auto transition-transform duration-300 ease-in-out flex-shrink-0 shadow-xl lg:shadow-none scrollbar-thin">
            <div class="space-y-6">
                @if($isPusat)
                    <!-- ADMIN PUSAT MENU -->
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider px-3">Tata Kelola Pusat</span>
                        <nav class="mt-2 space-y-1 text-xs font-semibold">
                            <!-- 1. Dashboard -->
                            <a href="{{ route('admin.dashboard') }}"
                               class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition {{ request()->routeIs('admin.dashboard') ? 'bg-sky-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                                <span class="flex items-center gap-2.5">
                                    <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                    </svg>
                                    Dashboard
                                </span>
                            </a>

                            <!-- 2. Antrean Verifikasi (badge) -->
                            <a href="{{ route('admin.verifikasi.index') }}"
                               class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition {{ request()->routeIs('admin.verifikasi.*') ? 'bg-sky-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                                <span class="flex items-center gap-2.5">
                                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Antrean Verifikasi
                                </span>
                                @if(($pendingVerifikasiCount ?? 0) > 0)
                                    <span class="bg-amber-500 text-slate-950 font-extrabold text-[10px] px-2 py-0.5 rounded-full shadow-xs">
                                        {{ $pendingVerifikasiCount }}
                                    </span>
                                @endif
                            </a>

                            <!-- 3. Semua Tiket -->
                            <a href="{{ route('admin.tiket.index') }}"
                               class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition {{ request()->routeIs('admin.tiket.index') || request()->routeIs('admin.tiket.show') ? 'bg-sky-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                                <span class="flex items-center gap-2.5">
                                    <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                    Semua Tiket
                                </span>
                            </a>

                            <!-- 4. Pengawasan SLA (Watchtower) -->
                            <a href="{{ route('admin.sla.index') }}"
                               class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition {{ request()->routeIs('admin.sla.*') ? 'bg-sky-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                                <span class="flex items-center gap-2.5">
                                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Pengawasan SLA
                                </span>
                                @if(($slaKritisCount ?? 0) > 0)
                                    <span class="bg-rose-600 text-white font-extrabold text-[10px] px-2 py-0.5 rounded-full animate-pulse shadow-xs">
                                        {{ $slaKritisCount }}
                                    </span>
                                @endif
                            </a>

                            <!-- 5. Eskalasi (badge) -->
                            <a href="{{ route('admin.eskalasi.index') }}"
                               class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition {{ request()->routeIs('admin.eskalasi.*') ? 'bg-sky-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                                <span class="flex items-center gap-2.5">
                                    <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    Eskalasi
                                </span>
                                @if(($eskalasiCount ?? 0) > 0)
                                    <span class="bg-rose-600 text-white font-extrabold text-[10px] px-2 py-0.5 rounded-full animate-pulse shadow-xs">
                                        {{ $eskalasiCount }}
                                    </span>
                                @endif
                            </a>

                            <!-- 5. Laporan -->
                            <a href="{{ route('admin.laporan') }}"
                               class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition {{ request()->routeIs('admin.laporan*') ? 'bg-sky-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                                <span class="flex items-center gap-2.5">
                                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Laporan Kinerja
                                </span>
                            </a>

                            <!-- 6. Kelola Petugas / Admin Bidang -->
                            <a href="{{ route('admin.users.index') }}"
                               class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition {{ request()->routeIs('admin.users.*') ? 'bg-sky-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                                <span class="flex items-center gap-2.5">
                                    <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    Kelola Petugas
                                </span>
                            </a>

                            <!-- 7. Kelola Bidang -->
                            <a href="{{ route('admin.bidang-master.index') }}"
                               class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition {{ request()->routeIs('admin.bidang-master.*') ? 'bg-sky-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                                <span class="flex items-center gap-2.5">
                                    <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    Kelola Bidang
                                </span>
                            </a>

                            <!-- 8. Kelola Kategori Layanan -->
                            <a href="{{ route('admin.kategori.index') }}"
                               class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition {{ request()->routeIs('admin.kategori.*') ? 'bg-sky-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                                <span class="flex items-center gap-2.5">
                                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                    Kategori Layanan
                                </span>
                            </a>

                            <!-- 9. Kelola Master OPD -->
                            <a href="{{ route('admin.opd.index') }}"
                               class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition {{ request()->routeIs('admin.opd.*') ? 'bg-sky-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                                <span class="flex items-center gap-2.5">
                                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    Master OPD / SKPD
                                </span>
                            </a>
                        </nav>
                    </div>
                @else
                    <!-- ADMIN BIDANG MENU -->
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-300/80 tracking-wider px-3">
                            Menu Bidang ({{ $user->bidang->kode ?? 'BIDANG' }})
                        </span>
                        <nav class="mt-2 space-y-1 text-xs font-semibold">
                            <!-- 1. Dashboard -->
                            <a href="{{ route('admin.dashboard') }}"
                               class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition {{ request()->routeIs('admin.dashboard') ? 'bg-emerald-600 text-white shadow-sm font-bold' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                                <span class="flex items-center gap-2.5">
                                    <svg class="w-4 h-4 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                    </svg>
                                    Dashboard
                                </span>
                            </a>

                            <!-- 2. Antrean Bidang (badge) -->
                            <a href="{{ route('admin.bidang.tiket.index', ['filter' => 'antrean']) }}"
                               class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition {{ request()->routeIs('admin.bidang.tiket.*') && request('filter') === 'antrean' ? 'bg-emerald-600 text-white shadow-sm font-bold' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                                <span class="flex items-center gap-2.5">
                                    <svg class="w-4 h-4 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8m-5 5h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293h3.172a1 1 0 00.707-.293l2.414-2.414a1 1 0 01.707-.293H20" />
                                    </svg>
                                    Antrean Bidang
                                </span>
                                @if(($bidangAntreanCount ?? 0) > 0)
                                    <span class="bg-amber-400 text-slate-950 font-extrabold text-[10px] px-2 py-0.5 rounded-full shadow-xs">
                                        {{ $bidangAntreanCount }}
                                    </span>
                                @endif
                            </a>

                            <!-- 3. Dalam Pengerjaan -->
                            <a href="{{ route('admin.bidang.tiket.index', ['filter' => 'pengerjaan']) }}"
                               class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition {{ request()->routeIs('admin.bidang.tiket.*') && request('filter') === 'pengerjaan' ? 'bg-emerald-600 text-white shadow-sm font-bold' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                                <span class="flex items-center gap-2.5">
                                    <svg class="w-4 h-4 text-sky-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Dalam Pengerjaan
                                </span>
                                @if(($bidangPengerjaanCount ?? 0) > 0)
                                    <span class="bg-sky-500 text-white font-bold text-[10px] px-2 py-0.5 rounded-full shadow-xs">
                                        {{ $bidangPengerjaanCount }}
                                    </span>
                                @endif
                            </a>

                            <!-- 4. Pengawasan SLA Bidang -->
                            <a href="{{ route('admin.sla.index') }}"
                               class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition {{ request()->routeIs('admin.sla.*') ? 'bg-emerald-600 text-white shadow-sm font-bold' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                                <span class="flex items-center gap-2.5">
                                    <svg class="w-4 h-4 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Pengawasan SLA
                                </span>
                                @if(($slaKritisCount ?? 0) > 0)
                                    <span class="bg-rose-500 text-white font-bold text-[10px] px-2 py-0.5 rounded-full shadow-xs animate-pulse">
                                        {{ $slaKritisCount }}
                                    </span>
                                @endif
                            </a>

                            <!-- 5. Arsip -->
                            <a href="{{ route('admin.bidang.tiket.index', ['filter' => 'arsip']) }}"
                               class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition {{ request()->routeIs('admin.bidang.tiket.*') && request('filter') === 'arsip' ? 'bg-emerald-600 text-white shadow-sm font-bold' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                                <span class="flex items-center gap-2.5">
                                    <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                    </svg>
                                    Arsip Tiket
                                </span>
                            </a>

                            <!-- 5. Semua Tiket Bidang -->
                            <a href="{{ route('admin.bidang.tiket.index') }}"
                               class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition {{ request()->routeIs('admin.bidang.tiket.*') && !request('filter') ? 'bg-emerald-600 text-white shadow-sm font-bold' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                                <span class="flex items-center gap-2.5">
                                    <svg class="w-4 h-4 text-teal-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                    </svg>
                                    Semua Tiket
                                </span>
                            </a>
                            <!-- 6. Siaran Insiden Siber (TLP RED Lintas Bidang) -->
                            @if(isset($countSiaranSiber) || !str_contains(strtoupper(Auth::user()->bidang?->nama_bidang ?? ''), 'PERSANDIAN'))
                                <a href="{{ route('admin.bidang.tiket.index', ['filter' => 'siaran_siber']) }}"
                                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition {{ request()->routeIs('admin.bidang.tiket.*') && request('filter') === 'siaran_siber' ? 'bg-rose-700 text-white shadow-sm font-bold' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                                    <span class="flex items-center gap-2.5">
                                        <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        Siaran Insiden Siber
                                    </span>
                                    @if(($countSiaranSiber ?? 0) > 0)
                                        <span class="bg-rose-500 text-white font-extrabold text-[10px] px-2 py-0.5 rounded-full shadow-xs">
                                            {{ $countSiaranSiber }}
                                        </span>
                                    @endif
                                </a>
                            @endif
                        </nav>
                    </div>
                @endif
            </div>

            <!-- Footer Sidebar Identity -->
            <div class="pt-4 border-t border-slate-700/60 text-xs">
                <div class="p-3 bg-black/20 rounded-xl border border-white/5 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg {{ $isPusat ? 'bg-sky-600' : 'bg-emerald-600' }} text-white font-bold flex items-center justify-center text-xs shadow-inner">
                        {{ $user->avatar ?? 'DK' }}
                    </div>
                    <div class="overflow-hidden">
                        <div class="font-bold text-white text-xs truncate">{{ $user->nama }}</div>
                        <div class="text-[10px] {{ $isPusat ? 'text-sky-300' : 'text-emerald-300' }} truncate">
                            {{ $isPusat ? 'Pusat Komando DKIS' : ($user->bidang->nama_bidang ?? 'Bidang DKIS') }}
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- ================================================================= -->
        <!-- MAIN CONTENT CONTAINER (slate-100) -->
        <!-- ================================================================= -->
        <main id="main-container" class="flex-1 bg-slate-100 h-full p-4 sm:p-6 lg:p-8 overflow-y-auto scrollbar-thin">
            <!-- Flash Message Alerts -->
            @if(session('success'))
                <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 flex items-start gap-3 shadow-xs">
                    <svg class="w-5 h-5 text-emerald-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <div class="text-xs sm:text-sm font-semibold">{{ session('success') }}</div>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 flex items-start gap-3 shadow-xs">
                    <svg class="w-5 h-5 text-rose-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <div class="text-xs sm:text-sm font-semibold">{{ session('error') }}</div>
                </div>
            @endif

            @if(isset($errors) && $errors->any())
                <div class="mb-6 p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 shadow-xs">
                    <div class="font-bold text-xs sm:text-sm mb-1.5 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-amber-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        Terdapat kesalahan pada input Anda:
                    </div>
                    <ul class="list-disc list-inside text-xs space-y-0.5 text-amber-800 font-medium">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <!-- Mobile Sidebar JavaScript -->
    <script>
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('mobile-sidebar-backdrop');
            if (sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.remove('-translate-x-full');
                backdrop.classList.remove('hidden');
            } else {
                sidebar.classList.add('-translate-x-full');
                backdrop.classList.add('hidden');
            }
        }
    </script>

    <!-- Lightweight & Snappy Navigation Indicator (Native Browser Navigation) -->
    <script>
        (function() {
            const topLoader = document.getElementById('admin-top-loader');

            function showLoader() {
                if (!topLoader) return;
                topLoader.style.opacity = '1';
                topLoader.style.width = '75%';
            }

            function isEligibleLink(anchor) {
                if (!anchor || anchor.tagName !== 'A') return false;
                const href = anchor.getAttribute('href');
                if (!href || href.startsWith('#') || href.startsWith('javascript:')) return false;
                if (anchor.target === '_blank' || anchor.hasAttribute('download')) return false;
                if (href.includes('/logout') || href.includes('/berkas/')) return false;

                try {
                    const url = new URL(anchor.href, window.location.origin);
                    return url.origin === window.location.origin && url.pathname.startsWith('/admin');
                } catch (e) {
                    return false;
                }
            }

            // Berikan respons visual instan pada saat klik tanpa memblokir atau menahan session lock
            document.addEventListener('click', function(e) {
                if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
                const anchor = e.target.closest('a');
                if (!isEligibleLink(anchor)) return;

                showLoader();
            }, { passive: true });

            window.addEventListener('beforeunload', function() {
                showLoader();
            });
        })();
    </script>
    <x-file-preview-modal />

    <!-- ========================================================================= -->
    <!-- GLOBAL CUSTOM MODERN CONFIRMATION & ALERT MODAL (Replaces Browser Dialog) -->
    <!-- ========================================================================= -->
    <div id="admin-confirm-modal" class="fixed inset-0 z-[10000] hidden items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs transition-opacity duration-200" style="display: none; z-index: 99999 !important;">
        <div class="relative w-full max-w-md bg-white rounded-3xl shadow-2xl border border-slate-200/90 overflow-hidden transform transition-all p-6 text-center space-y-4 animate-in fade-in zoom-in-95 duration-150">
            <!-- Icon Wrapper -->
            <div id="admin-confirm-icon-wrapper" class="w-16 h-16 rounded-2xl mx-auto flex items-center justify-center text-3xl shadow-xs border bg-amber-50 text-amber-600 border-amber-200">
                <span id="admin-confirm-icon">⚠️</span>
            </div>
            
            <!-- Text Content -->
            <div class="space-y-1.5">
                <h3 id="admin-confirm-title" class="text-base sm:text-lg font-black text-slate-900 tracking-tight">
                    Konfirmasi Tindakan
                </h3>
                <p id="admin-confirm-message" class="text-xs sm:text-sm text-slate-500 leading-relaxed max-w-sm mx-auto">
                    Apakah Anda yakin ingin melanjutkan tindakan ini?
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="pt-2 flex items-center justify-center gap-3">
                <button type="button" id="admin-confirm-btn-cancel"
                    class="flex-1 py-2.5 px-4 rounded-xl border border-slate-300 hover:bg-slate-100 text-slate-700 font-bold text-xs sm:text-sm transition cursor-pointer">
                    Batal
                </button>
                <button type="button" id="admin-confirm-btn-ok"
                    class="flex-1 py-2.5 px-4 rounded-xl font-bold text-xs sm:text-sm transition shadow-sm cursor-pointer flex items-center justify-center gap-1.5 bg-amber-500 hover:bg-amber-600 text-slate-950">
                    <span>Oke</span>
                </button>
            </div>
        </div>
    </div>

    <script>
        window.showAdminConfirm = function({
            title = 'Konfirmasi Tindakan',
            message = 'Apakah Anda yakin ingin melanjutkan?',
            icon = '⚠️',
            iconBg = 'bg-amber-50 text-amber-600 border-amber-200',
            confirmText = 'Ya, Lanjutkan',
            confirmBtnClass = 'bg-amber-500 hover:bg-amber-600 text-slate-950 font-black',
            cancelText = 'Batal',
            onConfirm = null,
            onCancel = null
        }) {
            return new Promise((resolve) => {
                const modal = document.getElementById('admin-confirm-modal');
                const iconElem = document.getElementById('admin-confirm-icon');
                const iconWrap = document.getElementById('admin-confirm-icon-wrapper');
                const titleElem = document.getElementById('admin-confirm-title');
                const msgElem = document.getElementById('admin-confirm-message');
                const btnCancel = document.getElementById('admin-confirm-btn-cancel');
                const btnOk = document.getElementById('admin-confirm-btn-ok');

                if (!modal) {
                    const res = window.confirm(message);
                    if (res && onConfirm) onConfirm();
                    else if (!res && onCancel) onCancel();
                    resolve(res);
                    return;
                }

                iconElem.textContent = icon;
                iconWrap.className = `w-16 h-16 rounded-2xl mx-auto flex items-center justify-center text-3xl shadow-xs border ${iconBg}`;
                titleElem.textContent = title;
                msgElem.textContent = message;
                btnCancel.textContent = cancelText;
                btnCancel.style.display = 'block';

                btnOk.className = `flex-1 py-2.5 px-4 rounded-xl font-bold text-xs sm:text-sm transition shadow-sm cursor-pointer flex items-center justify-center gap-1.5 ${confirmBtnClass}`;
                btnOk.innerHTML = `<span>${confirmText}</span>`;

                const close = () => {
                    modal.classList.add('hidden');
                    modal.style.display = 'none';
                    btnOk.onclick = null;
                    btnCancel.onclick = null;
                };

                btnOk.onclick = () => {
                    close();
                    if (onConfirm) onConfirm();
                    resolve(true);
                };

                btnCancel.onclick = () => {
                    close();
                    if (onCancel) onCancel();
                    resolve(false);
                };

                modal.classList.remove('hidden');
                modal.style.display = 'flex';
            });
        };

        window.showAdminAlert = function({
            title = 'Pemberitahuan',
            message = '',
            icon = 'ℹ️',
            iconBg = 'bg-blue-50 text-blue-600 border-blue-200',
            buttonText = 'Mengerti',
            buttonClass = 'bg-slate-900 hover:bg-slate-800 text-white font-bold'
        }) {
            return new Promise((resolve) => {
                const modal = document.getElementById('admin-confirm-modal');
                const iconElem = document.getElementById('admin-confirm-icon');
                const iconWrap = document.getElementById('admin-confirm-icon-wrapper');
                const titleElem = document.getElementById('admin-confirm-title');
                const msgElem = document.getElementById('admin-confirm-message');
                const btnCancel = document.getElementById('admin-confirm-btn-cancel');
                const btnOk = document.getElementById('admin-confirm-btn-ok');

                if (!modal) {
                    window.alert(message);
                    resolve(true);
                    return;
                }

                iconElem.textContent = icon;
                iconWrap.className = `w-16 h-16 rounded-2xl mx-auto flex items-center justify-center text-3xl shadow-xs border ${iconBg}`;
                titleElem.textContent = title;
                msgElem.textContent = message;
                btnCancel.style.display = 'none';

                btnOk.className = `w-full py-2.5 px-4 rounded-xl font-bold text-xs sm:text-sm transition shadow-sm cursor-pointer flex items-center justify-center gap-1.5 ${buttonClass}`;
                btnOk.innerHTML = `<span>${buttonText}</span>`;

                btnOk.onclick = () => {
                    modal.classList.add('hidden');
                    modal.style.display = 'none';
                    btnCancel.style.display = 'block';
                    btnOk.onclick = null;
                    resolve(true);
                };

                modal.classList.remove('hidden');
                modal.style.display = 'flex';
            });
        };

        // Mencegah error '419 Page Expired': Refresh session / CSRF di latar belakang setiap 10 menit
        (function() {
            function keepAlive() {
                fetch('{{ route("admin.dashboard") }}', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                }).catch(() => {});
            }
            setInterval(keepAlive, 10 * 60 * 1000);
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) {
                    keepAlive();
                }
            });
        })();
    </script>
    @stack('scripts')
</body>
</html>
