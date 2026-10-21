<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Bidang;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Tampilkan daftar seluruh akun petugas (Admin Pusat & Admin Bidang)
     */
    public function index(Request $request)
    {
        $query = User::with(['role', 'bidang'])->orderBy('role_id', 'asc')->orderBy('nama', 'asc');

        if ($request->filled('bidang_id')) {
            $query->where('bidang_id', $request->input('bidang_id'));
        }

        if ($request->filled('q')) {
            $q = trim($request->input('q'));
            $query->where(function ($sub) use ($q) {
                $sub->where('nama', 'LIKE', "%{$q}%")
                    ->orWhere('username', 'LIKE', "%{$q}%");
            });
        }

        $users = $query->paginate(15)->withQueryString();
        $roles = Role::all();
        $bidangs = Bidang::all();

        $stats = [
            'total' => User::count(),
            'pusat' => User::whereHas('role', fn($r) => $r->where('nama_role', 'Admin Pusat'))->count(),
            'bidang' => User::whereHas('role', fn($r) => $r->where('nama_role', 'Admin Bidang'))->count(),
        ];

        return view('admin.users.index', compact('users', 'roles', 'bidangs', 'stats'));
    }

    /**
     * Simpan akun admin bidang / admin pusat baru
     */
    public function store(Request $request)
    {
        $roleBidang = Role::where('nama_role', 'Admin Bidang')->first();

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,username'],
            'password' => ['required', 'string', 'min:8'],
            'role_id' => ['required', 'exists:roles,id'],
            'bidang_id' => ['nullable', 'exists:bidang,id'],
        ], [
            'nama.required' => 'Nama lengkap petugas wajib diisi.',
            'username.required' => 'Username akun wajib diisi.',
            'username.unique' => 'Username ini sudah digunakan, silakan pilih username lain.',
            'username.alpha_dash' => 'Username hanya boleh berisi huruf, angka, tanda hubung (-) dan garis bawah (_).',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter demi standar keamanan kedinasan.',
            'role_id.required' => 'Peran akun wajib dipilih.',
        ]);


        $selectedRole = Role::find($validated['role_id']);
        $roleName = $selectedRole?->nama_role ?? '';
        $requiresBidang = in_array($roleName, ['Admin Bidang', 'Teknisi']);

        // Jika peran adalah Admin Bidang atau Teknisi, wajib memilih Bidang
        if ($requiresBidang && empty($validated['bidang_id'])) {
            return back()->withInput()->withErrors(['bidang_id' => "Bidang penugasan wajib dipilih untuk {$roleName}."]);
        }

        // Jika peran adalah Admin Pusat, bidang_id diset null
        if (!$requiresBidang) {
            $validated['bidang_id'] = null;
        }

        // Generate inisial nama untuk avatar
        $words = explode(' ', trim($validated['nama']));
        $initials = '';
        foreach (array_slice($words, 0, 2) as $w) {
            $initials .= strtoupper(substr($w, 0, 1));
        }
        if (empty($initials)) {
            $initials = 'AD';
        }

        $user = User::create([
            'nama' => $validated['nama'],
            'username' => strtolower($validated['username']),
            'password' => Hash::make($validated['password']),
            'role_id' => $validated['role_id'],
            'bidang_id' => $validated['bidang_id'],
            'avatar' => $initials,
            'is_active' => true,
        ]);

        if ($roleName === 'Teknisi') {
            $user->technicianProfile()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'availability' => 'tersedia',
                    'max_load' => (int) config('ticketing.default_max_load', 6),
                    'skills' => $request->input('skills', []),
                ]
            );
        }

        AuditLog::create([
            'timestamp' => now(),
            'user_id' => Auth::id(),
            'aksi' => 'TAMBAH_AKUN_PETUGAS',
            'target_kode' => $user->username,
            'ip_address' => $request->ip(),
            'detail' => "Admin Pusat (" . Auth::user()->nama . ") membuat akun baru {$user->nama} ({$user->username}) dengan peran " . ($user->role->nama_role ?? 'Petugas'),
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', "Akun petugas '{$user->nama}' ({$user->username}) berhasil ditambahkan.");
    }

    /**
     * Perbarui data akun petugas
     */
    public function update(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', "unique:users,username,{$id}"],
            'password' => ['nullable', 'string', 'min:8'],
            'role_id' => ['required', 'exists:roles,id'],
            'bidang_id' => ['nullable', 'exists:bidang,id'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'nama.required' => 'Nama lengkap petugas wajib diisi.',
            'username.required' => 'Username akun wajib diisi.',
            'username.unique' => 'Username ini sudah digunakan akun lain.',
            'password.min' => 'Kata sandi minimal 8 karakter jika diubah.',
        ]);

        $selectedRole = Role::find($validated['role_id']);
        $roleName = $selectedRole?->nama_role ?? '';
        $requiresBidang = in_array($roleName, ['Admin Bidang', 'Teknisi']);

        if ($requiresBidang && empty($validated['bidang_id'])) {
            return back()->withInput()->withErrors(['bidang_id' => "Bidang penugasan wajib dipilih untuk {$roleName}."]);
        }

        if (!$requiresBidang) {
            $validated['bidang_id'] = null;
        }

        // Pengaman: Cegah penurunan peran Admin Pusat terakhir
        if ($user->isAdminPusat() && (int) $validated['role_id'] !== (int) $user->role_id) {
            $otherActivePusat = User::whereHas('role', fn($r) => $r->where('nama_role', 'Admin Pusat'))
                ->where('is_active', true)
                ->where('id', '!=', $user->id)
                ->count();

            if ($otherActivePusat < 1) {
                return back()->withInput()->with('error', 'Aksi Ditolak: Anda tidak dapat menurunkan peran Admin Pusat terakhir. Sistem wajib memiliki minimal 1 Admin Pusat aktif.');
            }
        }

        $user->nama = $validated['nama'];
        $user->username = strtolower($validated['username']);
        $user->role_id = $validated['role_id'];
        $user->bidang_id = $validated['bidang_id'];

        if ($request->has('is_active')) {
            $user->is_active = $request->boolean('is_active');
        }

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        // Update inisial avatar
        $words = explode(' ', trim($validated['nama']));
        $initials = '';
        foreach (array_slice($words, 0, 2) as $w) {
            $initials .= strtoupper(substr($w, 0, 1));
        }
        $user->avatar = !empty($initials) ? $initials : 'AD';

        $user->save();

        if ($roleName === 'Teknisi') {
            $user->technicianProfile()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'availability' => 'tersedia',
                    'max_load' => (int) config('ticketing.default_max_load', 6),
                    'skills' => $request->input('skills', []),
                ]
            );
        }

        AuditLog::create([
            'timestamp' => now(),
            'user_id' => Auth::id(),
            'aksi' => 'UBAH_AKUN_PETUGAS',
            'target_kode' => $user->username,
            'ip_address' => $request->ip(),
            'detail' => "Admin Pusat (" . (Auth::user()?->nama ?? 'Admin Pusat') . ") memperbarui profil akun {$user->nama} ({$user->username})",
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', "Data akun petugas '{$user->nama}' berhasil diperbarui.");
    }

    /**
     * Nonaktifkan atau aktifkan kembali akun petugas (Admin Pusat)
     */
    public function toggleActive(int $id)
    {
        if (Auth::id() === $id) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri yang sedang aktif digunakan.');
        }

        $user = User::findOrFail($id);

        // Pengaman: Cegah penonaktifan Admin Pusat terakhir
        if ($user->isAdminPusat() && $user->is_active) {
            $otherActivePusat = User::whereHas('role', fn($r) => $r->where('nama_role', 'Admin Pusat'))
                ->where('is_active', true)
                ->where('id', '!=', $user->id)
                ->count();

            if ($otherActivePusat < 1) {
                return back()->with('error', 'Aksi Ditolak: Sistem wajib memiliki minimal 1 Admin Pusat aktif. Anda tidak dapat menonaktifkan Admin Pusat terakhir.');
            }
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $statusStr = $user->is_active ? 'diaktifkan kembali' : 'dinonaktifkan';

        AuditLog::create([
            'timestamp' => now(),
            'user_id' => Auth::id(),
            'aksi' => $user->is_active ? 'AKTIFKAN_AKUN_PETUGAS' : 'NONAKTIFKAN_AKUN_PETUGAS',
            'target_kode' => $user->username,
            'ip_address' => request()?->ip() ?? '127.0.0.1',
            'detail' => "Admin Pusat (" . (Auth::user()?->nama ?? 'Admin Pusat') . ") mengubah status akun {$user->nama} ({$user->username}) menjadi {$statusStr}",
        ]);

        return back()->with('success', "Akun '{$user->nama}' ({$user->username}) berhasil {$statusStr}.");
    }

    /**
     * Hapus akun petugas (Hanya jika belum memiliki relasi penting)
     */
    public function destroy(int $id)
    {
        if (Auth::id() === $id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif digunakan.');
        }

        $user = User::findOrFail($id);
        $nama = $user->nama;
        $username = $user->username;

        // Pengaman: Cegah penghapusan Admin Pusat terakhir
        if ($user->isAdminPusat()) {
            $otherActivePusat = User::whereHas('role', fn($r) => $r->where('nama_role', 'Admin Pusat'))
                ->where('is_active', true)
                ->where('id', '!=', $user->id)
                ->count();

            if ($otherActivePusat < 1) {
                return back()->with('error', 'Aksi Ditolak: Anda tidak dapat menghapus Admin Pusat terakhir. Sistem wajib memiliki minimal 1 Admin Pusat aktif.');
            }
        }

        // Jika user memiliki tugas aktif atau riwayat tiket, arahkan ke nonaktifkan (soft deactivate)
        $hasAssignments = $user->assignedTasks()->exists();
        if ($hasAssignments) {
            $user->is_active = false;
            $user->save();

            AuditLog::create([
                'timestamp' => now(),
                'user_id' => Auth::id(),
                'aksi' => 'NONAKTIFKAN_AKUN_PETUGAS',
                'target_kode' => $username,
                'ip_address' => request()->ip(),
                'detail' => "Admin Pusat (" . Auth::user()->nama . ") menonaktifkan akun {$nama} ({$username}) karena memiliki jejak penugasan tiket.",
            ]);

            return redirect()->route('admin.users.index')
                ->with('success', "Akun petugas '{$nama}' memiliki jejak penugasan tiket sehingga dinonaktifkan (bukan dihapus permanen).");
        }

        $user->delete();

        AuditLog::create([
            'timestamp' => now(),
            'user_id' => Auth::id(),
            'aksi' => 'HAPUS_AKUN_PETUGAS',
            'target_kode' => $username,
            'ip_address' => request()->ip(),
            'detail' => "Admin Pusat (" . Auth::user()->nama . ") menghapus akun {$nama} ({$username})",
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', "Akun petugas '{$nama}' ({$username}) berhasil dihapus dari sistem.");
    }
}
