<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Bidang;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            if (Auth::user()->isTeknisi()) {
                return redirect()->route('teknisi.tugas.index');
            }
            return redirect()->route('admin.dashboard');
        }
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $input = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'Email kedinasan atau username wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $loginIdentifier = Str::lower(trim($input['username']));
        $throttleKey = Str::transliterate($loginIdentifier . '|' . $request->ip());
        $ipThrottleKey = 'login_ip:' . $request->ip();

        // Proteksi Brute-force: Lockout 5 Menit (300 detik) jika salah 5 kali per-akun atau 15 kali per-IP (Anti Credential Stuffing)
        if (RateLimiter::tooManyAttempts($throttleKey, 5) || RateLimiter::tooManyAttempts($ipThrottleKey, 15)) {
            $seconds = max(RateLimiter::availableIn($throttleKey), RateLimiter::availableIn($ipThrottleKey));
            $minutes = ceil($seconds / 60);

            AuditLog::create([
                'timestamp' => now(),
                'user_id' => null,
                'aksi' => 'LOGIN_LOCKOUT',
                'target_kode' => $loginIdentifier,
                'ip_address' => $request->ip(),
                'detail' => "Penangguhan login (Lockout {$minutes} menit) untuk '{$loginIdentifier}' dari IP {$request->ip()} karena melebihi batas percobaan gagal.",
            ]);

            return back()->withErrors([
                'username' => "Terlalu banyak percobaan login yang gagal. Akun/IP Anda ditangguhkan selama {$minutes} menit ({$seconds} detik) demi keamanan sistem DKIS.",
            ])->onlyInput('username');
        }

        $remember = $request->boolean('remember');

        // Dukung pencarian username persis atau email kedinasan
        $credentials = [
            'username' => $loginIdentifier,
            'password' => $input['password'],
        ];

        // Jika user mengetik tanpa @cirebonkota.go.id, coba cocokkan
        if (!User::where('username', $loginIdentifier)->exists()) {
            $withDomain = $loginIdentifier . '@cirebonkota.go.id';
            if (User::where('username', $withDomain)->exists()) {
                $credentials['username'] = $withDomain;
            }
        }

        if (Auth::attempt($credentials, $remember)) {
            RateLimiter::clear($throttleKey);
            RateLimiter::clear($ipThrottleKey);
            $request->session()->regenerate();

            AuditLog::create([
                'timestamp' => now(),
                'user_id' => Auth::id(),
                'aksi' => 'LOGIN_SUKSES',
                'target_kode' => Auth::user()->username,
                'ip_address' => $request->ip(),
                'detail' => "Petugas " . Auth::user()->nama . " (" . (Auth::user()->role->nama_role ?? 'Petugas') . ") berhasil login ke sistem.",
            ]);

            if (Auth::user()->isTeknisi()) {
                return redirect()->intended(route('teknisi.tugas.index'))
                    ->with('success', 'Selamat bertugas, ' . Auth::user()->nama . '!');
            }

            return redirect()->intended(route('admin.dashboard'))
                ->with('success', 'Selamat datang, ' . Auth::user()->nama . '!');
        }

        // Catat kegagalan dengan masa penangguhan 300 detik (5 menit)
        RateLimiter::hit($throttleKey, 300);
        RateLimiter::hit($ipThrottleKey, 300);

        AuditLog::create([
            'timestamp' => now(),
            'user_id' => null,
            'aksi' => 'LOGIN_GAGAL',
            'target_kode' => $loginIdentifier,
            'ip_address' => $request->ip(),
            'detail' => "Percobaan login gagal untuk akun '{$loginIdentifier}' dari IP {$request->ip()}.",
        ]);

        $attemptsLeft = RateLimiter::remaining($throttleKey, 5);
        $warningMessage = $attemptsLeft > 0 
            ? "Email kedinasan atau kata sandi salah. Sisa kesempatan mencoba: {$attemptsLeft} kali sebelum akun dikunci 5 menit."
            : "Terlalu banyak percobaan login yang gagal. Akun Anda ditangguhkan selama 5 menit demi keamanan sistem DKIS.";

        return back()->withErrors([
            'username' => $warningMessage,
        ])->onlyInput('username');
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            AuditLog::create([
                'timestamp' => now(),
                'user_id' => Auth::id(),
                'aksi' => 'LOGOUT',
                'target_kode' => Auth::user()->username,
                'ip_address' => $request->ip(),
                'detail' => "Petugas " . Auth::user()->nama . " logout dari sistem.",
            ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar.');
    }


    public function dashboard()
    {
        $user = Auth::user();
        if ($user->isTeknisi()) {
            return redirect()->route('teknisi.tugas.index');
        }

        $isBidang = $user->isAdminBidang() && $user->bidang_id;
        $bidangId = $isBidang ? $user->bidang_id : 0;

        // 1. Cache stats selama 30 detik via 1 single SQL aggregation query (Read-Only)
        $cacheKey = "dashboard_stats_role_{$user->role_id}_bidang_{$bidangId}";
        $stats = Cache::remember($cacheKey, 30, function () use ($isBidang, $bidangId) {
            $raw = DB::table('tickets')
                ->when($isBidang, function ($q) use ($bidangId) {
                    $q->where(function ($sub) use ($bidangId) {
                        $sub->where('bidang_id', $bidangId)->orWhere('tlp_level', 'RED');
                    })
                    ->whereIn('status', ['verified', 'in_progress', 'resolved', 'reopened', 'closed', 'force_closed', 'eskalasi_pusat']);
                })
                ->selectRaw("
                    COUNT(*) as total,
                    COUNT(CASE WHEN " . ($isBidang ? "status IN ('verified', 'reopened')" : "status = 'pending_verification'") . " THEN 1 END) as pending,
                    COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress,
                    COUNT(CASE WHEN status IN ('resolved', 'closed', 'force_closed') THEN 1 END) as selesai,
                    COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved,
                    COUNT(CASE WHEN status = 'eskalasi_pusat' THEN 1 END) as eskalasi,
                    COUNT(CASE WHEN status = 'closed' THEN 1 END) as closed,
                    COUNT(CASE WHEN status = 'force_closed' THEN 1 END) as force_closed,
                    COUNT(CASE WHEN sla_breach = true OR (status IN ('verified', 'in_progress', 'reopened') AND sla_deadline_at IS NOT NULL AND sla_deadline_at <= NOW()) THEN 1 END) as sla_breach,
                    COUNT(CASE WHEN status IN ('pending_verification', 'pending_info') AND (sla_respon_breach = true OR (sla_respon_deadline_at IS NOT NULL AND sla_respon_deadline_at <= NOW())) THEN 1 END) as pending_respon_breach,
                    COUNT(CASE WHEN tlp_level = 'RED' THEN 1 END) as tlp_red,
                    COUNT(CASE WHEN tlp_level = 'AMBER' THEN 1 END) as tlp_amber,
                    COUNT(CASE WHEN tlp_level = 'GREEN' THEN 1 END) as tlp_green,
                    COUNT(CASE WHEN tlp_level = 'CLEAR' THEN 1 END) as tlp_clear
                ")->first();

            return [
                'total' => (int) ($raw->total ?? 0),
                'pending' => (int) ($raw->pending ?? 0),
                'in_progress' => (int) ($raw->in_progress ?? 0),
                'selesai' => (int) ($raw->selesai ?? 0),
                'resolved' => (int) ($raw->resolved ?? 0),
                'eskalasi' => (int) ($raw->eskalasi ?? 0),
                'closed' => (int) ($raw->closed ?? 0),
                'force_closed' => (int) ($raw->force_closed ?? 0),
                'sla_breach' => (int) ($raw->sla_breach ?? 0),
                'pending_respon_breach' => (int) ($raw->pending_respon_breach ?? 0),
                'tlp_red' => (int) ($raw->tlp_red ?? 0),
                'tlp_amber' => (int) ($raw->tlp_amber ?? 0),
                'tlp_green' => (int) ($raw->tlp_green ?? 0),
                'tlp_clear' => (int) ($raw->tlp_clear ?? 0),
            ];
        });

        // 2. 10 Tiket Terbaru (Lengkap dengan kolom untuk modal aksi langsung di dashboard)
        $recentTickets = Ticket::query()
            ->when($isBidang, function ($q) use ($bidangId) {
                $q->where(function ($sub) use ($bidangId) {
                    $sub->where('bidang_id', $bidangId)->orWhere('tlp_level', 'RED');
                })
                ->whereIn('status', ['verified', 'in_progress', 'resolved', 'reopened', 'closed', 'force_closed', 'eskalasi_pusat']);
            })
            ->select([
                'id', 'kode_tiket', 'nip_pelapor', 'nama_pelapor', 'skpd', 'no_whatsapp', 'judul', 'deskripsi',
                'lampiran_pelapor', 'lampiran_tambahan', 'catatan_kelengkapan', 'catatan_resolusi', 'bukti_resolusi',
                'category_id', 'bidang_id', 'tlp_level', 'status', 'teknisi_lapangan',
                'sla_breach', 'sla_respon_breach', 'sla_dimulai_at', 'sla_respon_jam', 'sla_resolusi_jam',
                'status_history', 'created_at'
            ])
            ->with(['category:id,nama_kategori', 'bidang:id,nama_bidang,kode'])
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        // 3. Rekap Bidang (Cache 60 detik)
        $bidangList = Cache::remember('dashboard_bidang_list', 60, function () {
            return Bidang::withCount('tickets')->get();
        });

        return view('admin.dashboard', compact('stats', 'recentTickets', 'bidangList', 'user'));
    }
}
