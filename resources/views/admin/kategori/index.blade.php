@extends('admin.layout', ['title' => 'Kelola Kategori Layanan & SLA — DKIS'])

@section('content')
<div class="space-y-6">

    <!-- Header & Aksi Tambah -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-blue-600 mb-1">
                <span>📁 Master Data Layanan</span>
                <span>&bull;</span>
                <span>Standar Pelayanan</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Kategori Layanan Aduan & SLA</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Kelola kategori layanan yang tersedia pada portal publik ASN Kota Cirebon beserta batas waktu SLA.
            </p>
        </div>
        <button type="button" onclick="document.getElementById('modalTambahKategori').classList.remove('hidden')"
            class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs sm:text-sm shadow-md shadow-blue-600/20 transition flex items-center gap-2 cursor-pointer shrink-0">
            <span>➕</span>
            <span>Tambah Kategori Baru</span>
        </button>
    </div>

    <!-- Filter & Pencarian -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-3">
        <form action="{{ route('admin.kategori.index') }}" method="GET" class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto flex-1">
            <div class="relative w-full sm:w-72">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">🔍</span>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama kategori..."
                    class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500 font-medium">
            </div>

            <select name="bidang_id" onchange="this.form.submit()"
                class="w-full sm:w-60 py-2 px-3 text-xs rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500 font-semibold text-slate-700">
                <option value="">-- Seluruh Bidang Pengampu --</option>
                @foreach($bidangList as $b)
                    <option value="{{ $b->id }}" {{ (string) request('bidang_id') === (string) $b->id ? 'selected' : '' }}>
                        {{ $b->nama_bidang }} ({{ $b->kode }})
                    </option>
                @endforeach
            </select>

            <button type="submit" class="px-4 py-2 bg-slate-900 text-white rounded-xl text-xs font-bold hover:bg-slate-800 transition">
                Filter
            </button>
            @if(request()->hasAny(['q', 'bidang_id']))
                <a href="{{ route('admin.kategori.index') }}" class="text-xs text-slate-500 hover:text-slate-800 font-semibold underline">
                    Reset
                </a>
            @endif
        </form>

        <div class="text-xs text-slate-500 font-semibold">
            Total Kategori: <strong class="text-slate-900">{{ $categories->total() }}</strong>
        </div>
    </div>

    <!-- Tabel Kategori -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider text-[11px]">
                        <th class="py-3.5 px-4 w-12 text-center">No</th>
                        <th class="py-3.5 px-4">Nama Kategori Layanan</th>
                        <th class="py-3.5 px-4">Bidang Penanggung Jawab</th>
                        <th class="py-3.5 px-4 text-center">SLA Respons</th>
                        <th class="py-3.5 px-4 text-center">SLA Resolusi</th>
                        <th class="py-3.5 px-4 text-center">Jumlah Tiket</th>
                        <th class="py-3.5 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($categories as $idx => $cat)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-3.5 px-4 text-center text-slate-400 font-mono">
                                {{ $categories->firstItem() + $idx }}
                            </td>
                            <td class="py-3.5 px-4 font-bold text-slate-900">
                                {{ $cat->nama_kategori }}
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-bold
                                    @if(str_contains(strtoupper($cat->bidang?->kode ?? ''), 'PERSANDIAN')) bg-purple-50 text-purple-700 border border-purple-200
                                    @elseif(str_contains(strtoupper($cat->bidang?->kode ?? ''), 'ITIK')) bg-blue-50 text-blue-700 border border-blue-200
                                    @else bg-emerald-50 text-emerald-700 border border-emerald-200 @endif">
                                    <span>🏢</span>
                                    <span>{{ $cat->bidang?->nama_bidang ?? 'Belum Ditentukan' }}</span>
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center font-mono font-bold text-slate-700">
                                {{ (float)$cat->sla_respon_jam }} Jam
                            </td>
                            <td class="py-3.5 px-4 text-center font-mono font-bold text-slate-700">
                                {{ $cat->sla_resolusi_jam }} Jam <span class="text-slate-400 font-normal">({{ round($cat->sla_resolusi_jam / 24, 1) }} hari)</span>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-slate-100 text-slate-700">
                                    {{ $cat->tickets_count }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right space-x-1">
                                <button type="button"
                                    onclick="openEditKategoriModal({{ json_encode($cat) }})"
                                    class="px-2.5 py-1.5 bg-slate-100 hover:bg-blue-50 text-slate-700 hover:text-blue-700 font-bold rounded-lg border border-slate-200 transition text-[11px]">
                                    ✏️ Ubah
                                </button>
                                @if($cat->tickets_count === 0)
                                    <form action="{{ route('admin.kategori.destroy', $cat->id) }}" method="POST" class="inline"
                                        onsubmit="return confirm('Apakah Anda yakin ingin menghapus kategori {{ $cat->nama_kategori }}?')">
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
                                Tidak ada kategori layanan yang cocok dengan kriteria filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($categories->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $categories->links() }}
            </div>
        @endif
    </div>

</div>

<!-- MODAL TAMBAH KATEGORI -->
<div id="modalTambahKategori" class="fixed inset-0 bg-slate-950/60 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-200">
        <div class="bg-[#0f274a] text-white p-5 flex items-center justify-between">
            <h3 class="font-extrabold text-base">Tambah Kategori Layanan Baru</h3>
            <button type="button" onclick="document.getElementById('modalTambahKategori').classList.add('hidden')" class="text-slate-400 hover:text-white">✕</button>
        </div>

        <form action="{{ route('admin.kategori.store') }}" method="POST" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Kategori Layanan <span class="text-rose-500">*</span></label>
                <input type="text" name="nama_kategori" required placeholder="Contoh: Gangguan Jaringan Fiber Optik"
                    class="w-full text-xs rounded-xl border-slate-300 focus:ring-2 focus:ring-blue-500 p-2.5 font-medium">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Bidang Penanggung Jawab <span class="text-rose-500">*</span></label>
                <select name="bidang_id" required class="w-full text-xs rounded-xl border-slate-300 focus:ring-2 focus:ring-blue-500 p-2.5 font-semibold">
                    <option value="">-- Pilih Bidang --</option>
                    @foreach($bidangList as $b)
                        <option value="{{ $b->id }}">{{ $b->nama_bidang }} ({{ $b->kode }})</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">SLA Respons (Jam) <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.5" name="sla_respon_jam" value="72" required
                        class="w-full text-xs rounded-xl border-slate-300 focus:ring-2 focus:ring-blue-500 p-2.5 font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">SLA Resolusi (Jam) <span class="text-rose-500">*</span></label>
                    <input type="number" name="sla_resolusi_jam" value="336" required
                        class="w-full text-xs rounded-xl border-slate-300 focus:ring-2 focus:ring-blue-500 p-2.5 font-mono">
                    <p class="text-[10px] text-slate-400 mt-0.5">336 jam = 14 hari kalender</p>
                </div>
            </div>

            <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('modalTambahKategori').classList.add('hidden')"
                    class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-xs font-bold hover:bg-slate-200">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-600/20">
                    Simpan Kategori
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL UBAH KATEGORI -->
<div id="modalEditKategori" class="fixed inset-0 bg-slate-950/60 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-200">
        <div class="bg-[#0f274a] text-white p-5 flex items-center justify-between">
            <h3 class="font-extrabold text-base">Ubah Kategori Layanan</h3>
            <button type="button" onclick="document.getElementById('modalEditKategori').classList.add('hidden')" class="text-slate-400 hover:text-white">✕</button>
        </div>

        <form id="formEditKategori" method="POST" class="p-6 space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Kategori Layanan <span class="text-rose-500">*</span></label>
                <input type="text" id="edit_nama_kategori" name="nama_kategori" required
                    class="w-full text-xs rounded-xl border-slate-300 focus:ring-2 focus:ring-blue-500 p-2.5 font-medium">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Bidang Penanggung Jawab <span class="text-rose-500">*</span></label>
                <select id="edit_bidang_id" name="bidang_id" required class="w-full text-xs rounded-xl border-slate-300 focus:ring-2 focus:ring-blue-500 p-2.5 font-semibold">
                    @foreach($bidangList as $b)
                        <option value="{{ $b->id }}">{{ $b->nama_bidang }} ({{ $b->kode }})</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">SLA Respons (Jam) <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.5" id="edit_sla_respon_jam" name="sla_respon_jam" required
                        class="w-full text-xs rounded-xl border-slate-300 focus:ring-2 focus:ring-blue-500 p-2.5 font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">SLA Resolusi (Jam) <span class="text-rose-500">*</span></label>
                    <input type="number" id="edit_sla_resolusi_jam" name="sla_resolusi_jam" required
                        class="w-full text-xs rounded-xl border-slate-300 focus:ring-2 focus:ring-blue-500 p-2.5 font-mono">
                </div>
            </div>

            <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('modalEditKategori').classList.add('hidden')"
                    class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-xs font-bold hover:bg-slate-200">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-600/20">
                    Perbarui Kategori
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditKategoriModal(cat) {
        document.getElementById('formEditKategori').action = '/admin/kategori/' + cat.id;
        document.getElementById('edit_nama_kategori').value = cat.nama_kategori;
        document.getElementById('edit_bidang_id').value = cat.bidang_id;
        document.getElementById('edit_sla_respon_jam').value = cat.sla_respon_jam;
        document.getElementById('edit_sla_resolusi_jam').value = cat.sla_resolusi_jam;
        document.getElementById('modalEditKategori').classList.remove('hidden');
    }
</script>
@endsection
