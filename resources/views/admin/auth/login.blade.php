@extends('layouts.app', [
    'title' => 'Portal Masuk Petugas — DKIS Kota Cirebon',
    'hideNavbar' => true,
    'isLoginPage' => true
])

@section('content')
<div class="min-h-[calc(100vh-73px)] flex flex-col bg-gradient-to-br from-slate-950 via-[#0f1f3d] to-[#0a1222] text-slate-100 relative overflow-hidden px-4 sm:px-6 lg:px-8">
    <!-- Ambient Background Lighting -->
    <div class="absolute -top-32 -left-32 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute inset-0 bg-[radial-gradient(#38bdf8_1px,transparent_1px)] [background-size:28px_28px] opacity-10 pointer-events-none"></div>

    <!-- Top Government Identity Bar -->
    <header class="w-full max-w-5xl mx-auto flex items-center justify-between pt-8 sm:pt-12 pb-6 border-b border-white/10 relative z-10">
        <a href="{{ url('/') }}" class="flex items-center gap-3 group">
            <img src="{{ asset('images/logo-dkis.png') }}" alt="Logo DKIS" class="h-10 w-auto object-contain">
            <div>
                <div class="text-sm font-extrabold text-white tracking-wide group-hover:text-emerald-400 transition">
                    DKIS KOTA CIREBON
                </div>
                <div class="text-[11px] text-slate-400">
                    Sistem Tata Kelola Pelayanan Terpadu
                </div>
            </div>
        </a>

        <a href="{{ url('/') }}" class="text-xs text-slate-400 hover:text-emerald-400 transition font-medium flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-slate-900/60 border border-slate-800">
            <span>Kembali ke Portal ASN</span>
            <span>&rarr;</span>
        </a>
    </header>

    <!-- Center Card -->
    <main class="flex-1 flex items-center justify-center py-8 sm:py-12 relative z-10 w-full">
        <div class="w-full max-w-md mx-auto">
            <div class="bg-white rounded-3xl shadow-2xl shadow-slate-950/60 p-8 sm:p-9 text-slate-900 border border-slate-100/90 relative backdrop-blur-sm">
            
            <!-- Card Header -->
            <div class="text-center mb-6">
                <div style="width: 54px; height: 54px; margin: 0 auto 14px auto; background-color: #059669; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 26px; box-shadow: 0 10px 15px -3px rgba(5, 150, 105, 0.3); border: 2px solid rgba(52, 211, 153, 0.5);">
                    🛡️
                </div>
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                    Masuk Portal Petugas
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    Otentikasi Petugas: Admin Pusat, Admin Bidang, &amp; Teknisi Lapangan DKIS
                </p>
                <div class="mt-2 inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    🔒 Jalur Akses Terproteksi
                </div>
            </div>

            @if(session('error') || $errors->any())
                <div class="mb-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs space-y-1">
                    @if(session('error'))
                        <div class="font-medium">{{ session('error') }}</div>
                    @endif
                    @foreach($errors->all() as $err)
                        <div>{{ $err }}</div>
                    @endforeach
                </div>
            @endif

            <!-- Login Form -->
            <form action="{{ route('login.post') }}" method="POST" class="space-y-4" onsubmit="handleLoginSubmit(this)">
                @csrf

                <div>
                    <label for="username" class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center gap-1.5">
                        <span>✉️</span>
                        <span>Email Kedinasan / Username</span>
                    </label>
                    <input type="text" id="username" name="username" value="{{ old('username') }}" required autofocus
                        placeholder="nama.petugas@cirebonkota.go.id"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition shadow-2xs">
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center gap-1.5">
                        <span>🔒</span>
                        <span>Kata Sandi Kedinasan</span>
                    </label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required
                            placeholder="••••••••••••"
                            class="w-full px-3.5 py-2.5 pr-11 rounded-xl border border-slate-300 text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition shadow-2xs">
                        <button type="button" onclick="togglePasswordVisibility()" aria-label="Toggle password"
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer">
                            <span id="eye-icon" class="text-sm">👁️</span>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs pt-1">
                    <label class="flex items-center gap-2 text-slate-600 cursor-pointer select-none">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        <span>Ingat saya di perangkat ini</span>
                    </label>
                </div>

                <button type="submit" id="btn-login-submit"
                    class="w-full py-3 px-4 rounded-xl text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 active:scale-[0.99] shadow-lg shadow-emerald-600/25 transition duration-150 flex items-center justify-center gap-2 cursor-pointer mt-2">
                    <span id="btn-login-icon">🔐</span>
                    <span id="btn-login-text">Masuk ke Dashboard</span>
                </button>
            </form>

            <!-- Card Bottom Trust Note -->
            <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-center gap-2 text-[11px] text-slate-400">
                <span>🔒</span>
                <span>Akses diaudit & terenkripsi TLS v1.3</span>
            </div>
        </div>
    </div>
</main>
</div>

<script>
    function togglePasswordVisibility() {
        const pwd = document.getElementById('password');
        const icon = document.getElementById('eye-icon');
        if (pwd.type === 'password') {
            pwd.type = 'text';
            icon.textContent = '🙈';
        } else {
            pwd.type = 'password';
            icon.textContent = '👁️';
        }
    }

    function handleLoginSubmit(form) {
        const btn = document.getElementById('btn-login-submit');
        const text = document.getElementById('btn-login-text');
        const icon = document.getElementById('btn-login-icon');
        if (btn) {
            btn.classList.add('opacity-80', 'cursor-not-allowed');
            icon.textContent = '⏳';
            text.textContent = 'Memverifikasi Otentikasi...';
        }
    }
</script>
@endsection
