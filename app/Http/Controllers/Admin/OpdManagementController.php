<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\MasterOpd;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OpdManagementController extends Controller
{
    /**
     * Tampilkan daftar Master OPD Kota Cirebon & data yang belum terpetakan
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->input('q', ''));

        $query = MasterOpd::withCount('tickets');

        if (!empty($q)) {
            $query->where(function ($sub) use ($q) {
                $sub->where('nama_opd', 'ILIKE', "%{$q}%")
                    ->orWhere('singkatan', 'ILIKE', "%{$q}%");
            });
        }

        $opdList = $query->orderBy('urutan')->orderBy('nama_opd')->paginate(20)->withQueryString();

        // Cari teks skpd pada tiket yang belum terpetakan ke master_opd (opd_id IS NULL)
        $unmappedSkpd = Ticket::whereNull('opd_id')
            ->whereNotNull('skpd')
            ->selectRaw('skpd, count(*) as total')
            ->groupBy('skpd')
            ->orderByDesc('total')
            ->get();

        return view('admin.opd.index', compact('opdList', 'unmappedSkpd', 'q'));
    }

    /**
     * Simpan Master OPD baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_opd' => ['required', 'string', 'max:200', 'unique:master_opd,nama_opd'],
            'singkatan' => ['nullable', 'string', 'max:50'],
            'alias' => ['nullable', 'string'],
            'urutan' => ['nullable', 'integer'],
        ], [
            'nama_opd.required' => 'Nama OPD / Perangkat Daerah wajib diisi.',
            'nama_opd.unique' => 'Nama OPD ini sudah terdaftar.',
        ]);

        $aliasArray = [];
        if (!empty($validated['alias'])) {
            $aliasArray = array_values(array_filter(array_map('trim', explode(',', $validated['alias']))));
        }
        if (!empty($validated['singkatan'])) {
            $aliasArray[] = $validated['singkatan'];
        }

        $opd = MasterOpd::create([
            'nama_opd' => $validated['nama_opd'],
            'singkatan' => $validated['singkatan'] ?? null,
            'alias' => array_values(array_unique($aliasArray)),
            'urutan' => $validated['urutan'] ?? 99,
            'is_active' => true,
        ]);

        // Otomatis petakan tiket lama yang nama teks skpd-nya cocok
        DB::table('tickets')
            ->whereNull('opd_id')
            ->where(function ($q) use ($opd) {
                $q->where('skpd', $opd->nama_opd)
                  ->orWhere('skpd', 'ILIKE', "%{$opd->nama_opd}%");
            })
            ->update(['opd_id' => $opd->id]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'aksi' => 'TAMBAH_MASTER_OPD',
            'target_kode' => (string) $opd->id,
            'ip_address' => $request->ip(),
            'detail' => "Admin Pusat menambahkan Master OPD: '{$opd->nama_opd}' ({$opd->singkatan})",
            'timestamp' => now(),
        ]);

        return redirect()->route('admin.opd.index')
            ->with('success', "Master OPD '{$opd->nama_opd}' berhasil ditambahkan.");
    }

    /**
     * Perbarui Master OPD
     */
    public function update(Request $request, int $id)
    {
        $opd = MasterOpd::findOrFail($id);

        $validated = $request->validate([
            'nama_opd' => ['required', 'string', 'max:200', 'unique:master_opd,nama_opd,' . $opd->id],
            'singkatan' => ['nullable', 'string', 'max:50'],
            'alias' => ['nullable', 'string'],
            'urutan' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $aliasArray = [];
        if (!empty($validated['alias'])) {
            $aliasArray = array_values(array_filter(array_map('trim', explode(',', $validated['alias']))));
        }

        $opd->update([
            'nama_opd' => $validated['nama_opd'],
            'singkatan' => $validated['singkatan'] ?? null,
            'alias' => array_values(array_unique($aliasArray)),
            'urutan' => $validated['urutan'] ?? 99,
            'is_active' => $request->has('is_active'),
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'aksi' => 'UBAH_MASTER_OPD',
            'target_kode' => (string) $opd->id,
            'ip_address' => $request->ip(),
            'detail' => "Admin Pusat memperbarui Master OPD: '{$opd->nama_opd}'",
            'timestamp' => now(),
        ]);

        return redirect()->route('admin.opd.index')
            ->with('success', "Master OPD '{$opd->nama_opd}' berhasil diperbarui.");
    }

    /**
     * Hapus Master OPD jika belum memiliki tiket tertaut
     */
    public function destroy(int $id)
    {
        $opd = MasterOpd::withCount('tickets')->findOrFail($id);

        if ($opd->tickets_count > 0) {
            return back()->with('error', "Master OPD '{$opd->nama_opd}' tidak dapat dihapus karena telah terhubung ke {$opd->tickets_count} tiket.");
        }

        $nama = $opd->nama_opd;
        $opd->delete();

        AuditLog::create([
            'user_id' => Auth::id(),
            'aksi' => 'HAPUS_MASTER_OPD',
            'target_kode' => (string) $id,
            'ip_address' => request()->ip(),
            'detail' => "Admin Pusat menghapus Master OPD: '{$nama}'",
            'timestamp' => now(),
        ]);

        return redirect()->route('admin.opd.index')
            ->with('success', "Master OPD '{$nama}' berhasil dihapus.");
    }

    /**
     * Rapikan Data: Petakan teks SKPD lama yang belum baku ke Master OPD yang dipilih
     */
    public function mergeUnmapped(Request $request)
    {
        $validated = $request->validate([
            'raw_skpd' => ['required', 'string'],
            'target_opd_id' => ['required', 'exists:master_opd,id'],
        ], [
            'raw_skpd.required' => 'Pilih teks nama SKPD yang ingin dirapikan.',
            'target_opd_id.required' => 'Pilih Master OPD tujuan pemetaan.',
        ]);

        $targetOpd = MasterOpd::findOrFail($validated['target_opd_id']);

        // Update seluruh tiket yang memiliki skpd tersebut
        $affected = DB::table('tickets')
            ->where('skpd', $validated['raw_skpd'])
            ->update([
                'opd_id' => $targetOpd->id,
                'skpd' => $targetOpd->nama_opd, // Normalisasikan sekaligus teksnya
            ]);

        // Simpan raw_skpd sebagai alias baru pada Master OPD agar di masa depan otomatis cocok
        $aliases = $targetOpd->alias ?? [];
        if (!in_array($validated['raw_skpd'], $aliases, true)) {
            $aliases[] = $validated['raw_skpd'];
            $targetOpd->alias = array_values(array_unique($aliases));
            $targetOpd->save();
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'aksi' => 'RAPIKAN_DATA_OPD',
            'target_kode' => (string) $targetOpd->id,
            'ip_address' => $request->ip(),
            'detail' => "Admin Pusat memetakan {$affected} tiket dari teks bebas '{$validated['raw_skpd']}' ke Master OPD '{$targetOpd->nama_opd}'",
            'timestamp' => now(),
        ]);

        return redirect()->route('admin.opd.index')
            ->with('success', "Berhasil merapikan {$affected} tiket dari '{$validated['raw_skpd']}' ke '{$targetOpd->nama_opd}'.");
    }
}
