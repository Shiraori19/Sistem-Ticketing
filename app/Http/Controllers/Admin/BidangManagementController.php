<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Bidang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class BidangManagementController extends Controller
{
    /**
     * Tampilkan daftar master bidang pengampu teknis
     */
    public function index()
    {
        $bidangs = Bidang::withCount(['users', 'categories', 'tickets'])
            ->orderBy('id', 'asc')
            ->get();

        $stats = [
            'total_bidang' => $bidangs->count(),
            'total_petugas' => $bidangs->sum('users_count'),
            'total_kategori' => $bidangs->sum('categories_count'),
            'total_tiket' => $bidangs->sum('tickets_count'),
        ];

        return view('admin.bidang_master.index', compact('bidangs', 'stats'));
    }

    /**
     * Simpan bidang baru ke dalam sistem
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_bidang' => ['required', 'string', 'max:100'],
            'kode' => ['required', 'string', 'max:30', 'alpha_dash', 'unique:bidang,kode'],
            'icon' => ['nullable', 'string', 'max:50'],
        ], [
            'nama_bidang.required' => 'Nama bidang pengampu wajib diisi.',
            'kode.required' => 'Kode singkatan bidang wajib diisi.',
            'kode.unique' => 'Kode bidang ini sudah terdaftar dalam sistem.',
            'kode.alpha_dash' => 'Kode bidang hanya boleh berupa huruf, angka, dan garis bawah/strip.',
        ]);

        $kodeClean = strtoupper(trim($validated['kode']));
        // Bersihkan nama bidang jika pengguna secara tidak sengaja mengetikkan kurung kode di akhir
        $cleanNama = trim(preg_replace('/\s*\(' . preg_quote($kodeClean, '/') . '\)/i', '', $validated['nama_bidang']));

        $bidang = Bidang::create([
            'nama_bidang' => $cleanNama,
            'kode' => $kodeClean,
            'icon' => $validated['icon'] ?? 'building-office',
        ]);

        // Invalidate cache
        Cache::forget('bidang_list_dropdown');
        Cache::forget('bidangs_all');

        AuditLog::create([
            'timestamp' => now(),
            'user_id' => Auth::id(),
            'aksi' => 'TAMBAH_BIDANG',
            'target_kode' => $bidang->kode,
            'ip_address' => $request->ip(),
            'detail' => "Admin Pusat (" . Auth::user()->nama . ") menambahkan bidang baru: {$bidang->nama_bidang} ({$bidang->kode})",
        ]);

        return redirect()->route('admin.bidang-master.index')
            ->with('success', "Bidang baru '{$bidang->nama_lengkap}' berhasil ditambahkan ke dalam sistem.");
    }

    /**
     * Perbarui data bidang
     */
    public function update(Request $request, int $id)
    {
        $bidang = Bidang::findOrFail($id);

        $validated = $request->validate([
            'nama_bidang' => ['required', 'string', 'max:100'],
            'kode' => ['required', 'string', 'max:30', 'alpha_dash', "unique:bidang,kode,{$id}"],
            'icon' => ['nullable', 'string', 'max:50'],
        ], [
            'nama_bidang.required' => 'Nama bidang pengampu wajib diisi.',
            'kode.required' => 'Kode singkatan bidang wajib diisi.',
            'kode.unique' => 'Kode bidang ini sudah digunakan bidang lain.',
        ]);

        $kodeClean = strtoupper(trim($validated['kode']));
        $cleanNama = trim(preg_replace('/\s*\(' . preg_quote($kodeClean, '/') . '\)/i', '', $validated['nama_bidang']));

        $oldName = $bidang->nama_lengkap;

        $bidang->update([
            'nama_bidang' => $cleanNama,
            'kode' => $kodeClean,
            'icon' => $validated['icon'] ?? $bidang->icon,
        ]);

        Cache::forget('bidang_list_dropdown');
        Cache::forget('bidangs_all');

        AuditLog::create([
            'timestamp' => now(),
            'user_id' => Auth::id(),
            'aksi' => 'UBAH_BIDANG',
            'target_kode' => $bidang->kode,
            'ip_address' => $request->ip(),
            'detail' => "Admin Pusat (" . Auth::user()->nama . ") mengubah bidang '{$oldName}' menjadi '{$bidang->nama_lengkap}'",
        ]);

        return redirect()->route('admin.bidang-master.index')
            ->with('success', "Data bidang '{$bidang->nama_lengkap}' berhasil diperbarui.");
    }

    /**
     * Hapus bidang dengan validasi integritas relasi
     */
    public function destroy(Request $request, int $id)
    {
        $bidang = Bidang::findOrFail($id);

        if ($bidang->tickets()->exists()) {
            return back()->with('error', "Bidang '{$bidang->nama_lengkap}' tidak dapat dihapus karena memiliki riwayat {$bidang->tickets()->count()} tiket terkait.");
        }

        if ($bidang->users()->exists()) {
            return back()->with('error', "Bidang '{$bidang->nama_lengkap}' tidak dapat dihapus karena terdapat {$bidang->users()->count()} akun petugas yang terdaftar pada bidang ini.");
        }

        if ($bidang->categories()->exists()) {
            return back()->with('error', "Bidang '{$bidang->nama_lengkap}' tidak dapat dihapus karena memiliki {$bidang->categories()->count()} kategori layanan terkait.");
        }

        $namaHapus = $bidang->nama_lengkap;
        $kodeHapus = $bidang->kode;

        $bidang->delete();

        Cache::forget('bidang_list_dropdown');
        Cache::forget('bidangs_all');

        AuditLog::create([
            'timestamp' => now(),
            'user_id' => Auth::id(),
            'aksi' => 'HAPUS_BIDANG',
            'target_kode' => $kodeHapus,
            'ip_address' => $request->ip(),
            'detail' => "Admin Pusat (" . Auth::user()->nama . ") menghapus bidang {$namaHapus}",
        ]);

        return redirect()->route('admin.bidang-master.index')
            ->with('success', "Bidang '{$namaHapus}' berhasil dihapus dari sistem.");
    }
}
