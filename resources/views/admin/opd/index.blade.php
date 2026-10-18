@extends('admin.layout', ['title' => 'Master OPD & Rapikan Data — DKIS'])

@section('content')
<div class="space-y-6">

    <!-- Header & Aksi Tambah -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-emerald-600 mb-1">
                <span>🏛️ Master Data Instansi</span>
                <span>&bull;</span>
                <span>Standardisasi OPD</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Master OPD / Perangkat Daerah</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Standardisasi nama instansi pelapor Pemerintah Kota Cirebon untuk akurasi laporan dan integrasi layanan.
            </p>
        </div>
        <button type="button" onclick="document.getElementById('modalTambahOpd').classList.remove('hidden')"
            class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs sm:text-sm shadow-md shadow-emerald-600/20 transition flex items-center gap-2 cursor-pointer shrink-0">
            <span>➕</span>
            <span>Tambah Master OPD</span>
        </button>
    </div>

    <!-- PANEL RAPIKAN DATA SKPD (Jika ada tiket dengan teks SKPD belum baku) -->
    @if(isset($unmappedSkpd) && $unmappedSkpd->isNotEmpty())
        <div class="bg-amber-50 border border-amber-200 rounded-3xl p-6 shadow-xs">
            <div class="flex items-center justify-between gap-4 mb-3">
                <div class="flex items-center gap-2.5">
                    <span class="text-2xl">🧹</span>
                    <div>
                        <h3 class="text-sm font-extrabold text-amber-900">Perhatian Kualitas Data: Ditemukan Teks OPD Belum Baku</h3>
                        <p class="text-xs text-amber-700">Terdapat teks instansi bebas dari tiket yang belum terpetakan ke Master OPD resmi. Petakan ke master di bawah ini:</p>
                    </div>
                </div>
                <span class="px-3 py-1 bg-amber-200 text-amber-900 font-black rounded-full text-xs">
                    {{ $unmappedSkpd->count() }} Teks Berbeda
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-4">
                @foreach($unmappedSkpd as $raw)
                    <form action="{{ route('admin.opd.mergeUnmapped') }}" method="POST" class="bg-white p-3.5 rounded-2xl border border-amber-200 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 shadow-2xs">
                        @csrf
                        <input type="hidden" name="raw_skpd" value="{{ $raw->skpd }}">
                        <div class="min-w-0 flex-1">
                            <div class="font-bold text-xs text-slate-900 truncate">
                                "{{ $raw->skpd }}"
                            </div>
                            <div class="text-[11px] text-slate-400 font-semibold">
                                Tertaut pada <strong class="text-amber-700">{{ $raw->total }}</strong> tiket
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <select name="target_opd_id" required class="text-xs py-1.5 px-2.5 rounded-xl border border-slate-300 font-medium max-w-[200px]">
                                <option value="">-- Petakan Ke --</option>
                                @foreach($opdList as $opd)
                                    <option value="{{ $opd->id }}">{{ $opd->singkatan ?: $opd->nama_opd }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="px-3 py-1.5 bg-slate-900 text-white rounded-xl text-xs font-bold hover:bg-slate-800 transition shrink-0">
                                Petakan
                            </button>
                        </div>
                    </form>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Pencarian & Total -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-3">
        <form action="{{ route('admin.opd.index') }}" method="GET" class="flex items-center gap-3 w-full sm:w-auto flex-1">
            <div class="relative w-full sm:w-80">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">🔍</span>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama OPD atau singkatan..."
                    class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-300 focus:ring-2 focus:ring-emerald-500 font-medium">
            </div>
            <button type="submit" class="px-4 py-2 bg-slate-900 text-white rounded-xl text-xs font-bold hover:bg-slate-800 transition">
                Cari
            </button>
            @if(request('q'))
                <a href="{{ route('admin.opd.index') }}" class="text-xs text-slate-500 hover:text-slate-800 font-semibold underline">Reset</a>
            @endif
        </form>

        <div class="text-xs text-slate-500 font-semibold">
            Total Master OPD: <strong class="text-slate-900">{{ $opdList->total() }}</strong>
        </div>
    </div>

    <!-- Tabel Master OPD -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider text-[11px]">
                        <th class="py-3.5 px-4 w-12 text-center">Urutan</th>
                        <th class="py-3.5 px-4">Nama Lengkap OPD / Instansi</th>
                        <th class="py-3.5 px-4 text-center">Singkatan</th>
                        <th class="py-3.5 px-4">Alias / Penulisan Lain</th>
                        <th class="py-3.5 px-4 text-center">Jumlah Tiket</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                        <th class="py-3.5 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($opdList as $opd)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-3.5 px-4 text-center text-slate-400 font-mono font-bold">
                                {{ $opd->urutan }}
                            </td>
                            <td class="py-3.5 px-4 font-bold text-slate-900">
                                {{ $opd->nama_opd }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if($opd->singkatan)
                                    <span class="px-2.5 py-0.5 rounded-md font-mono font-bold bg-slate-100 text-slate-700 text-[11px]">
                                        {{ $opd->singkatan }}
                                    </span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-slate-500 text-[11px]">
                                @if(!empty($opd->alias) && is_array($opd->alias))
                                    <div class="flex flex-wrap gap-1">
                                        @foreach(array_slice($opd->alias, 0, 4) as $al)
                                            <span class="px-2 py-0.5 bg-slate-50 border border-slate-200 rounded text-slate-600 font-mono text-[10px]">{{ $al }}</span>
                                        @endforeach
                                        @if(count($opd->alias) > 4)
                                            <span class="text-slate-400 text-[10px]">+{{ count($opd->alias) - 4 }}</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-slate-400 italic">Belum ada alias</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-blue-50 text-blue-700 border border-blue-200">
                                    {{ $opd->tickets_count }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $opd->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500' }}">
                                    {{ $opd->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right space-x-1">
                                <button type="button"
                                    onclick="openEditOpdModal({{ json_encode($opd) }})"
                                    class="px-2.5 py-1.5 bg-slate-100 hover:bg-emerald-50 text-slate-700 hover:text-emerald-700 font-bold rounded-lg border border-slate-200 transition text-[11px]">
                                    ✏️ Ubah
                                </button>
                                @if($opd->tickets_count === 0)
                                    <form action="{{ route('admin.opd.destroy', $opd->id) }}" method="POST" class="inline"
                                        onsubmit="return confirm('Apakah Anda yakin ingin menghapus OPD {{ $opd->nama_opd }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-2.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold rounded-lg border border-rose-200 transition text-[11px]">
                                            🗑️ Hapus
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400 font-medium">
                                Tidak ada Master OPD yang cocok.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($opdList->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $opdList->links() }}
            </div>
        @endif
    </div>

</div>

<!-- MODAL TAMBAH MASTER OPD -->
<div id="modalTambahOpd" class="fixed inset-0 bg-slate-950/60 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-200">
        <div class="bg-[#0f274a] text-white p-5 flex items-center justify-between">
            <h3 class="font-extrabold text-base">Tambah Master OPD Baru</h3>
            <button type="button" onclick="document.getElementById('modalTambahOpd').classList.add('hidden')" class="text-slate-400 hover:text-white">✕</button>
        </div>

        <form action="{{ route('admin.opd.store') }}" method="POST" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap Instansi / OPD <span class="text-rose-500">*</span></label>
                <input type="text" name="nama_opd" required placeholder="Contoh: Dinas Lingkungan Hidup"
                    class="w-full text-xs rounded-xl border-slate-300 focus:ring-2 focus:ring-emerald-500 p-2.5 font-medium">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Singkatan / Akronim</label>
                    <input type="text" name="singkatan" placeholder="Contoh: DLH"
                        class="w-full text-xs rounded-xl border-slate-300 focus:ring-2 focus:ring-emerald-500 p-2.5 font-mono uppercase">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nomor Urutan</label>
                    <input type="number" name="urutan" value="21"
                        class="w-full text-xs rounded-xl border-slate-300 focus:ring-2 focus:ring-emerald-500 p-2.5 font-mono">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Alias / Variasi Penulisan Teks</label>
                <input type="text" name="alias" placeholder="Pisahkan dengan koma, contoh: DLH, Lingkungan Hidup"
                    class="w-full text-xs rounded-xl border-slate-300 focus:ring-2 focus:ring-emerald-500 p-2.5 font-medium">
                <p class="text-[10px] text-slate-400 mt-1">Digunakan untuk mencocokkan otomatis tiket yang ditulis manual oleh ASN.</p>
            </div>

            <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('modalTambahOpd').classList.add('hidden')"
                    class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-xs font-bold hover:bg-slate-200">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-md shadow-emerald-600/20">
                    Simpan Master OPD
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL UBAH MASTER OPD -->
<div id="modalEditOpd" class="fixed inset-0 bg-slate-950/60 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-200">
        <div class="bg-[#0f274a] text-white p-5 flex items-center justify-between">
            <h3 class="font-extrabold text-base">Ubah Master OPD</h3>
            <button type="button" onclick="document.getElementById('modalEditOpd').classList.add('hidden')" class="text-slate-400 hover:text-white">✕</button>
        </div>

        <form id="formEditOpd" method="POST" class="p-6 space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap Instansi / OPD <span class="text-rose-500">*</span></label>
                <input type="text" id="edit_nama_opd" name="nama_opd" required
                    class="w-full text-xs rounded-xl border-slate-300 focus:ring-2 focus:ring-emerald-500 p-2.5 font-medium">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Singkatan / Akronim</label>
                    <input type="text" id="edit_singkatan" name="singkatan"
                        class="w-full text-xs rounded-xl border-slate-300 focus:ring-2 focus:ring-emerald-500 p-2.5 font-mono uppercase">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nomor Urutan</label>
                    <input type="number" id="edit_urutan" name="urutan"
                        class="w-full text-xs rounded-xl border-slate-300 focus:ring-2 focus:ring-emerald-500 p-2.5 font-mono">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Alias / Variasi Penulisan Teks</label>
                <input type="text" id="edit_alias" name="alias"
                    class="w-full text-xs rounded-xl border-slate-300 focus:ring-2 focus:ring-emerald-500 p-2.5 font-medium">
            </div>

            <div class="flex items-center gap-2 pt-2">
                <input type="checkbox" id="edit_is_active" name="is_active" value="1" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                <label for="edit_is_active" class="text-xs font-bold text-slate-700 cursor-pointer">Status Aktif (Tampil di Formulir Pelapor)</label>
            </div>

            <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('modalEditOpd').classList.add('hidden')"
                    class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-xs font-bold hover:bg-slate-200">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-md shadow-emerald-600/20">
                    Perbarui Master OPD
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditOpdModal(opd) {
        document.getElementById('formEditOpd').action = '/admin/skpd/' + opd.id;
        document.getElementById('edit_nama_opd').value = opd.nama_opd;
        document.getElementById('edit_singkatan').value = opd.singkatan || '';
        document.getElementById('edit_urutan').value = opd.urutan || 99;
        document.getElementById('edit_alias').value = Array.isArray(opd.alias) ? opd.alias.join(', ') : '';
        document.getElementById('edit_is_active').checked = Boolean(opd.is_active);
        document.getElementById('modalEditOpd').classList.remove('hidden');
    }
</script>
@endsection
