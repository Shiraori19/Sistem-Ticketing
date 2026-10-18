@extends('admin.layout', ['title' => 'Kelola Master Bidang — DKIS Kota Cirebon'])

@section('content')
<div class="space-y-6">
    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-sky-50 text-sky-800 border border-sky-200 text-xs font-semibold mb-1">
                <span>🏢</span> Master Data Organisasi
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Kelola Bidang Pengampu Layanan</h1>
            <p class="text-xs text-slate-500 mt-1">
                Daftar bidang teknis kedinasan di lingkungan DKIS Kota Cirebon sebagai rujukan utama routing disposisi dan penugasan petugas.
            </p>
        </div>

        <button type="button" onclick="openAddModal()"
            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-sky-600 hover:bg-sky-700 text-white font-bold rounded-xl text-xs shadow-xs transition">
            <span>➕</span>
            <span>Tambah Bidang Baru</span>
        </button>
    </div>

    <!-- 4 Ringkasan Statistik -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Stat 1: Total Bidang -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs hover:border-slate-300 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Bidang</span>
                <span class="w-8 h-8 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-sm font-black">🏢</span>
            </div>
            <div class="text-3xl font-black text-slate-900 mt-2">{{ $stats['total_bidang'] }}</div>
            <div class="text-[11px] text-slate-400 mt-1">Unit bidang terdaftar</div>
        </div>

        <!-- Stat 2: Total Petugas Terkait -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs hover:border-slate-300 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Petugas Bidang</span>
                <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm font-black">👥</span>
            </div>
            <div class="text-3xl font-black text-emerald-700 mt-2">{{ $stats['total_petugas'] }}</div>
            <div class="text-[11px] text-emerald-600 mt-1">Akun Admin Bidang aktif</div>
        </div>

        <!-- Stat 3: Total Kategori Layanan -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs hover:border-slate-300 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Kategori Terikat</span>
                <span class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm font-black">📂</span>
            </div>
            <div class="text-3xl font-black text-indigo-700 mt-2">{{ $stats['total_kategori'] }}</div>
            <div class="text-[11px] text-indigo-600 mt-1">Jenis layanan spesifik</div>
        </div>

        <!-- Stat 4: Total Tiket Masuk -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs hover:border-slate-300 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-amber-600 uppercase tracking-wider">Tiket Terdisposisi</span>
                <span class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-sm font-black">🎫</span>
            </div>
            <div class="text-3xl font-black text-amber-700 mt-2">{{ $stats['total_tiket'] }}</div>
            <div class="text-[11px] text-amber-600 mt-1">Total beban kerja pengaduan</div>
        </div>
    </div>

    <!-- Flash Pesan Sukses / Error -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center gap-2">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold flex items-center gap-2">
            <span>⚠️</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if(isset($errors) && $errors->any())
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold space-y-1">
            <div class="font-bold flex items-center gap-1.5">
                <span>⚠️</span> Terdapat kendala pada isian formulir:
            </div>
            <ul class="list-disc list-inside pl-2">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Tabel Daftar Bidang -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
        <div class="p-4 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <span class="text-xs font-bold text-slate-700">Daftar Seluruh Bidang Pengampu DKIS</span>
            <span class="text-[11px] text-slate-400">Total: {{ $bidangs->count() }} Bidang</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                    <tr>
                        <th class="py-3.5 px-4 w-12 text-center">No</th>
                        <th class="py-3.5 px-4">Nama Bidang</th>
                        <th class="py-3.5 px-4 text-center">Kode Singkatan</th>
                        <th class="py-3.5 px-4 text-center">Akun Petugas</th>
                        <th class="py-3.5 px-4 text-center">Kategori Layanan</th>
                        <th class="py-3.5 px-4 text-center">Total Tiket</th>
                        <th class="py-3.5 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($bidangs as $index => $b)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-3.5 px-4 text-center font-mono text-slate-400">
                                {{ $index + 1 }}
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-900 text-xs flex items-center gap-2">
                                    <span class="w-7 h-7 rounded-lg bg-sky-100 text-sky-700 flex items-center justify-center font-bold text-xs border border-sky-200">
                                        🏢
                                    </span>
                                    <span>{{ $b->nama_bidang }}</span>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-lg font-mono font-extrabold text-[11px] bg-slate-100 text-slate-800 border border-slate-300">
                                    {{ $b->kode }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <span class="px-2.5 py-0.5 rounded-full font-bold text-[11px] bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    {{ $b->users_count }} Petugas
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <span class="px-2.5 py-0.5 rounded-full font-bold text-[11px] bg-indigo-50 text-indigo-700 border border-indigo-200">
                                    {{ $b->categories_count }} Kategori
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <span class="px-2.5 py-0.5 rounded-full font-bold text-[11px] bg-amber-50 text-amber-700 border border-amber-200">
                                    {{ $b->tickets_count }} Tiket
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right whitespace-nowrap space-x-2">
                                <button type="button"
                                    data-bidang="{{ json_encode($b) }}"
                                    onclick="openEditModal(JSON.parse(this.dataset.bidang))"
                                    class="px-2.5 py-1.5 bg-slate-100 hover:bg-sky-50 text-slate-700 hover:text-sky-700 font-bold rounded-lg border border-slate-200 transition text-[11px]">
                                    ✏️ Ubah
                                </button>

                                <form action="{{ route('admin.bidang-master.destroy', $b->id) }}" method="POST" class="inline"
                                    onsubmit="return confirm('Apakah Anda yakin ingin menghapus bidang {{ $b->nama_lengkap }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="px-2.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold rounded-lg border border-rose-200 transition text-[11px]">
                                        🗑️ Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                Belum ada data bidang yang terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL TAMBAH BIDANG BARU -->
<!-- ========================================================================= -->
<div id="addModal" class="fixed inset-0 bg-slate-950/60 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-200 animate-in fade-in zoom-in-95 duration-150">
        <div class="bg-[#0f274a] text-white p-5 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-sky-500/20 text-sky-400 flex items-center justify-center font-bold text-sm border border-sky-400/30">
                    ➕
                </div>
                <div>
                    <h3 class="font-bold text-sm">Tambah Bidang Pengampu Baru</h3>
                    <p class="text-[11px] text-sky-200/70">Daftarkan bidang baru di lingkungan DKIS Kota Cirebon</p>
                </div>
            </div>
            <button type="button" onclick="closeAddModal()" class="text-slate-400 hover:text-white p-1">
                ✕
            </button>
        </div>

        <form action="{{ route('admin.bidang-master.store') }}" method="POST" class="p-6 space-y-4 text-xs">
            @csrf
            <div>
                <label class="block font-bold text-slate-700 mb-1">Nama Lengkap Bidang *</label>
                <input type="text" name="nama_bidang" required placeholder="Contoh: Statistik & Layanan Informasi"
                    class="w-full rounded-xl border border-slate-300 p-2.5 focus:ring-2 focus:ring-sky-500 focus:outline-none">
                <p class="text-[10px] text-slate-400 mt-1">Cukup tuliskan nama bidang saja tanpa perlu tanda kurung singkatan.</p>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Kode Singkatan Bidang *</label>
                <input type="text" name="kode" required placeholder="Contoh: STATISTIK"
                    class="w-full rounded-xl border border-slate-300 p-2.5 uppercase font-mono focus:ring-2 focus:ring-sky-500 focus:outline-none">
                <p class="text-[10px] text-slate-400 mt-1">Kode identifikasi unik untuk penomoran dan routing internal (otomatis huruf kapital).</p>
            </div>

            <div class="pt-4 border-t border-slate-200 flex items-center justify-end gap-2">
                <button type="button" onclick="closeAddModal()" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-sky-600 hover:bg-sky-700 text-white font-bold shadow-sm transition">
                    Simpan Bidang
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL UBAH BIDANG -->
<!-- ========================================================================= -->
<div id="editModal" class="fixed inset-0 bg-slate-950/60 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-200 animate-in fade-in zoom-in-95 duration-150">
        <div class="bg-[#0f274a] text-white p-5 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-sky-500/20 text-sky-400 flex items-center justify-center font-bold text-sm border border-sky-400/30">
                    ✏️
                </div>
                <div>
                    <h3 class="font-bold text-sm">Ubah Data Bidang</h3>
                    <p class="text-[11px] text-sky-200/70">Perbarui nama atau kode identifikasi bidang</p>
                </div>
            </div>
            <button type="button" onclick="closeEditModal()" class="text-slate-400 hover:text-white p-1">
                ✕
            </button>
        </div>

        <form id="editForm" method="POST" class="p-6 space-y-4 text-xs">
            @csrf
            @method('PUT')
            <div>
                <label class="block font-bold text-slate-700 mb-1">Nama Lengkap Bidang *</label>
                <input type="text" name="nama_bidang" id="edit_nama_bidang" required
                    class="w-full rounded-xl border border-slate-300 p-2.5 focus:ring-2 focus:ring-sky-500 focus:outline-none">
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Kode Singkatan Bidang *</label>
                <input type="text" name="kode" id="edit_kode" required
                    class="w-full rounded-xl border border-slate-300 p-2.5 uppercase font-mono focus:ring-2 focus:ring-sky-500 focus:outline-none">
            </div>

            <div class="pt-4 border-t border-slate-200 flex items-center justify-end gap-2">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-sky-600 hover:bg-sky-700 text-white font-bold shadow-sm transition">
                    Perbarui Bidang
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddModal() {
        document.getElementById('addModal').classList.remove('hidden');
    }

    function closeAddModal() {
        document.getElementById('addModal').classList.add('hidden');
    }

    function openEditModal(bidang) {
        const form = document.getElementById('editForm');
        form.action = `/admin/kelola-bidang/${bidang.id}`;
        document.getElementById('edit_nama_bidang').value = bidang.nama_bidang;
        document.getElementById('edit_kode').value = bidang.kode;
        document.getElementById('editModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
    }
</script>
@endsection
