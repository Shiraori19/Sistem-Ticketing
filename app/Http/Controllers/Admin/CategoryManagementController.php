<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Bidang;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoryManagementController extends Controller
{
    /**
     * Tampilkan daftar seluruh Kategori Layanan DKIS
     */
    public function index(Request $request)
    {
        $bidangId = $request->input('bidang_id');
        $q = trim((string) $request->input('q', ''));

        $query = Category::with('bidang')->withCount('tickets');

        if ($bidangId) {
            $query->where('bidang_id', $bidangId);
        }

        if (!empty($q)) {
            $query->where('nama_kategori', 'ILIKE', "%{$q}%");
        }

        $categories = $query->orderBy('bidang_id')->orderBy('nama_kategori')->paginate(15)->withQueryString();
        $bidangList = Bidang::orderBy('id')->get();

        return view('admin.kategori.index', compact('categories', 'bidangList', 'bidangId', 'q'));
    }

    /**
     * Simpan kategori layanan baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_kategori' => ['required', 'string', 'max:150', 'unique:categories,nama_kategori'],
            'bidang_id' => ['required', 'exists:bidang,id'],
            'sla_respon_jam' => ['required', 'numeric', 'min:0.5', 'max:720'],
            'sla_resolusi_jam' => ['required', 'integer', 'min:1', 'max:1440'],
        ], [
            'nama_kategori.required' => 'Nama kategori layanan wajib diisi.',
            'nama_kategori.unique' => 'Nama kategori ini sudah terdaftar sebelumnya.',
            'bidang_id.required' => 'Bidang penanggung jawab wajib dipilih.',
            'sla_respon_jam.required' => 'Batas waktu SLA respons wajib ditentukan.',
            'sla_resolusi_jam.required' => 'Batas waktu SLA resolusi wajib ditentukan.',
        ]);

        $category = Category::create($validated);

        AuditLog::create([
            'user_id' => Auth::id(),
            'aksi' => 'TAMBAH_KATEGORI_LAYANAN',
            'target_kode' => (string) $category->id,
            'ip_address' => $request->ip(),
            'detail' => "Admin Pusat menambahkan kategori layanan baru: '{$category->nama_kategori}' (Bidang ID: {$category->bidang_id}, SLA Resolusi: {$category->sla_resolusi_jam} jam)",
            'timestamp' => now(),
        ]);

        return redirect()->route('admin.kategori.index')
            ->with('success', "Kategori layanan '{$category->nama_kategori}' berhasil ditambahkan dan otomatis aktif pada formulir pelapor.");
    }

    /**
     * Perbarui kategori layanan
     */
    public function update(Request $request, int $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'nama_kategori' => ['required', 'string', 'max:150', 'unique:categories,nama_kategori,' . $category->id],
            'bidang_id' => ['required', 'exists:bidang,id'],
            'sla_respon_jam' => ['required', 'numeric', 'min:0.5', 'max:720'],
            'sla_resolusi_jam' => ['required', 'integer', 'min:1', 'max:1440'],
        ], [
            'nama_kategori.required' => 'Nama kategori layanan wajib diisi.',
            'nama_kategori.unique' => 'Nama kategori ini sudah digunakan.',
            'bidang_id.required' => 'Bidang penanggung jawab wajib dipilih.',
        ]);

        $category->update($validated);

        AuditLog::create([
            'user_id' => Auth::id(),
            'aksi' => 'UBAH_KATEGORI_LAYANAN',
            'target_kode' => (string) $category->id,
            'ip_address' => $request->ip(),
            'detail' => "Admin Pusat memperbarui rincian kategori: '{$category->nama_kategori}'",
            'timestamp' => now(),
        ]);

        return redirect()->route('admin.kategori.index')
            ->with('success', "Kategori layanan '{$category->nama_kategori}' berhasil diperbarui.");
    }

    /**
     * Hapus kategori layanan jika belum memiliki tiket tertaut
     */
    public function destroy(int $id)
    {
        $category = Category::withCount('tickets')->findOrFail($id);

        if ($category->tickets_count > 0) {
            return back()->with('error', "Kategori '{$category->nama_kategori}' tidak dapat dihapus karena telah memiliki {$category->tickets_count} tiket yang tertaut.");
        }

        $nama = $category->nama_kategori;
        $category->delete();

        AuditLog::create([
            'user_id' => Auth::id(),
            'aksi' => 'HAPUS_KATEGORI_LAYANAN',
            'target_kode' => (string) $id,
            'ip_address' => request()->ip(),
            'detail' => "Admin Pusat menghapus kategori layanan: '{$nama}'",
            'timestamp' => now(),
        ]);

        return redirect()->route('admin.kategori.index')
            ->with('success', "Kategori '{$nama}' berhasil dihapus.");
    }
}
