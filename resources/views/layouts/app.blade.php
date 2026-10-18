<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Sistem Ticketing DKIS Kota Cirebon' }}</title>
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="{{ ($isLoginPage ?? false) ? 'bg-slate-950' : 'bg-slate-50' }} text-slate-800 font-sans min-h-screen flex flex-col antialiased">
    <!-- Navbar -->
    @if(!isset($hideNavbar) || !$hideNavbar)
    <header class="bg-white border-b border-slate-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center space-x-3">
                    <img src="{{ asset('images/logo-dkis.png') }}" alt="Logo DKIS" class="h-10 w-auto object-contain">
                    <div>
                        @php
                            $isAuthPage = ($isLoginPage ?? false) || request()->is('login*') || request()->routeIs('login*');
                        @endphp
                        <a href="{{ url('/') }}" class="text-lg font-bold text-slate-900 leading-tight block hover:text-emerald-700">
                            {{ $isAuthPage ? 'Portal Tata Kelola & Layanan' : 'Sistem Ticketing Internal' }}
                        </a>
                        <span class="text-xs text-slate-500 font-medium block">Dinas Komunikasi, Informatika dan Statistik Kota Cirebon</span>
                    </div>
                </div>

                <nav class="flex items-center space-x-2 sm:space-x-4">
                    @if(!$isAuthPage)
                        <a href="{{ route('public.index') }}" class="px-3 py-2 rounded-md text-sm font-medium text-slate-700 hover:text-emerald-600 hover:bg-slate-100">
                            Buat Tiket
                        </a>
                        <a href="{{ route('public.index', ['tab' => 'lacak']) }}" class="px-3 py-2 rounded-md text-sm font-medium text-slate-700 hover:text-emerald-600 hover:bg-slate-100">
                            Lacak Tiket
                        </a>
                    @else
                        <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-slate-100 text-slate-700 border border-slate-200 text-xs font-semibold shadow-xs">
                            <span class="text-emerald-600 font-bold">🔒</span>
                            <span>Area Terbatas Petugas</span>
                        </div>
                    @endif

                    @auth
                        <div class="h-6 w-px bg-slate-200 mx-1"></div>
                        <a href="{{ route('admin.dashboard') }}" class="px-3 py-2 rounded-md text-sm font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100">
                            Dashboard Admin ({{ Auth::user()->avatar }})
                        </a>
                        <form action="{{ route('logout') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-3 py-2 rounded-md text-sm font-medium text-rose-600 hover:bg-rose-50">
                                Keluar
                            </button>
                        </form>
                    @endauth
                </nav>

            </div>
        </div>
    </header>
    @endif

    <!-- Main Content -->
    <main class="flex-grow">
        @if((!isset($hideNavbar) || !$hideNavbar) && (!isset($isLoginPage) || !$isLoginPage) && (session('success') || session('error') || $errors->any()))
        <!-- Flash Messages -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            @if(session('success'))
                <div class="p-4 mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-start space-x-3">
                    <svg class="w-5 h-5 text-emerald-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <div class="text-sm font-medium">{{ session('success') }}</div>
                </div>
            @endif

            @if(session('error'))
                <div class="p-4 mb-4 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 flex items-start space-x-3">
                    <svg class="w-5 h-5 text-rose-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <div class="text-sm font-medium">{{ session('error') }}</div>
                </div>
            @endif

            @if($errors->any())
                <div class="p-4 mb-4 rounded-lg bg-amber-50 border border-amber-200 text-amber-800">
                    <div class="font-semibold text-sm mb-1">Terdapat kesalahan pada isian form:</div>
                    <ul class="list-disc list-inside text-sm space-y-0.5">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 {{ ($isLoginPage ?? false) ? '' : 'mt-12' }} py-6 text-center text-xs text-slate-500">
        <p>&copy; {{ date('Y') }} Dinas Komunikasi, Informatika, dan Statistik Kota Cirebon. Hak Cipta Dilindungi.</p>
        <p class="mt-1">Pemerintah Daerah Kota Cirebon — Jawa Barat</p>
    </footer>
    @stack('scripts')
</body>
</html>
