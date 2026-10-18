@extends('admin.layout', ['title' => 'Kelola Petugas & Admin Bidang — DKIS Kota Cirebon'])

@section('content')
<div class="space-y-6">
    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-sky-50 text-sky-800 border border-sky-200 text-xs font-semibold mb-1">
                <span>👥</span> Manajemen Akun Petugas DKIS
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Kelola Akun Admin Bidang & Petugas</h1>
            <p class="text-xs text-slate-500 mt-1">
                Admin Pusat memiliki otoritas penuh untuk menambahkan, menugaskan, dan mengelola akses login seluruh Admin Bidang di lingkungan DKIS Kota Cirebon.
            </p>
        </div>

        <div>
            <button type="button" onclick="openAddModal()"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-700 text-white font-bold text-xs shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Tambah Petugas Baru</span>
            </button>
        </div>
    </div>

    <!-- Ringkasan Statistik Akun -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-lg">
                👥
            </div>
            <div>
                <div class="text-[11px] text-slate-500 font-medium">Total Akun Terdaftar</div>
                <div class="text-xl font-black text-slate-900">{{ $stats['total'] }} Petugas</div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center font-bold text-lg">
                🛡️
            </div>
            <div>
                <div class="text-[11px] text-slate-500 font-medium">Admin Pusat DKIS</div>
                <div class="text-xl font-black text-sky-800">{{ $stats['pusat'] }} Akun</div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-lg">
                🏢
            </div>
            <div>
                <div class="text-[11px] text-slate-500 font-medium">Admin Bidang Khusus</div>
                <div class="text-xl font-black text-emerald-800">{{ $stats['bidang'] }} Akun</div>
            </div>
        </div>
    </div>

    <!-- Filter & Pencarian -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200 p-4">
        <form action="{{ route('admin.users.index') }}" method="GET" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama atau username petugas..."
                    class="w-full text-xs rounded-xl border border-slate-300 p-2.5 focus:outline-none focus:ring-2 focus:ring-sky-500 transition">
            </div>

            <div class="w-full sm:w-64">
                <select name="bidang_id" onchange="this.form.submit()"
                    class="w-full text-xs rounded-xl border border-slate-300 p-2.5 focus:outline-none focus:ring-2 focus:ring-sky-500 transition">
                    <option value="">-- Semua Penugasan Bidang --</option>
                    @foreach($bidangs as $b)
                        <option value="{{ $b->id }}" {{ request('bidang_id') == $b->id ? 'selected' : '' }}>
                            Bidang {{ $b->nama_lengkap }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="px-4 py-2.5 bg-sky-600 hover:bg-sky-700 text-white font-bold rounded-xl text-xs shadow-xs transition">
                Terapkan
            </button>
            @if(request('q') || request('bidang_id'))
                <a href="{{ route('admin.users.index') }}" class="px-3 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition border border-slate-200 text-center">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- Tabel Daftar Pengguna -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                    <tr>
                        <th class="py-3.5 px-4">Nama & Username</th>
                        <th class="py-3.5 px-4">Peran (Role)</th>
                        <th class="py-3.5 px-4">Penugasan Bidang</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4">Terdaftar Sejak</th>
                        <th class="py-3.5 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($users as $u)
                        <tr class="hover:bg-slate-50/80 transition {{ !$u->is_active ? 'opacity-60 bg-slate-50/60' : '' }}">
                            <!-- Profil -->
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl flex items-center justify-center font-bold text-xs shadow-xs text-white
                                        @if($u->isAdminPusat()) bg-sky-600 @elseif($u->isTeknisi()) bg-blue-600 @else bg-emerald-600 @endif">
                                        {{ $u->avatar ?? 'DK' }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900 text-xs flex items-center gap-1.5">
                                            <span>{{ $u->nama }}</span>
                                            @if(Auth::id() === $u->id)
                                                <span class="text-[9px] px-1.5 py-0.2 bg-amber-100 text-amber-800 border border-amber-300 rounded font-bold">Akun Anda</span>
                                            @endif
                                        </div>
                                        <div class="font-mono text-[11px] text-slate-400">@<span>{{ $u->username }}</span></div>
                                    </div>
                                </div>
                            </td>

                            <!-- Peran -->
                            <td class="py-3 px-4 whitespace-nowrap">
                                @if($u->isAdminPusat())
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wide bg-sky-100 text-sky-800 border border-sky-200">
                                        🛡️ Admin Pusat
                                    </span>
                                @elseif($u->isTeknisi())
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wide bg-blue-100 text-blue-800 border border-blue-200">
                                        👷 Teknisi Lapangan
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wide bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        🏢 Admin Bidang
                                    </span>
                                @endif
                            </td>

                            <!-- Bidang -->
                            <td class="py-3 px-4">
                                @if($u->bidang)
                                    <div class="font-semibold text-slate-800">{{ $u->bidang->nama_bidang }}</div>
                                    <span class="text-[10px] font-mono text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200">
                                        {{ $u->bidang->kode }}
                                    </span>
                                @else
                                    <span class="text-slate-400 italic">Pusat Komando (Lintas Bidang)</span>
                                @endif
                            </td>

                            <!-- Status Aktif -->
                            <td class="py-3 px-4 whitespace-nowrap">
                                @if($u->is_active)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span> Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-300">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Nonaktif
                                    </span>
                                @endif
                            </td>

                            <!-- Tanggal Buat -->
                            <td class="py-3 px-4 text-slate-500 whitespace-nowrap font-mono text-[11px]">
                                {{ $u->created_at ? $u->created_at->translatedFormat('d M Y') : '-' }}
                            </td>

                            <!-- Aksi -->
                            <td class="py-3 px-4 text-right whitespace-nowrap space-x-1.5">
                                <button type="button"
                                    data-user="{{ json_encode($u) }}"
                                    onclick="openEditModal(JSON.parse(this.dataset.user))"
                                    class="px-2.5 py-1.5 bg-slate-100 hover:bg-sky-50 text-slate-700 hover:text-sky-700 font-bold rounded-lg border border-slate-200 transition text-[11px]">
                                    ✏️ Ubah
                                </button>

                                @if(Auth::id() !== $u->id)
                                    <form action="{{ route('admin.users.toggleStatus', $u->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="px-2 py-1.5 {{ $u->is_active ? 'bg-amber-50 hover:bg-amber-100 text-amber-800 border-amber-200' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border-emerald-200' }} font-bold rounded-lg border transition text-[11px]" title="{{ $u->is_active ? 'Nonaktifkan akun' : 'Aktifkan kembali akun' }}">
                                            {{ $u->is_active ? '⏸️ Nonaktifkan' : '▶️ Aktifkan' }}
                                        </button>
                                    </form>

                                    <form action="{{ route('admin.users.destroy', $u->id) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun {{ $u->nama }} ({{ $u->username }})?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-2 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold rounded-lg border border-rose-200 transition text-[11px]">
                                            🗑️ Hapus
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">
                                Tidak ada akun petugas yang cocok dengan kriteria pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL TAMBAH PETUGAS -->
<!-- ========================================================================= -->
<div id="addModal" class="fixed inset-0 bg-slate-950/60 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-200 animate-in fade-in zoom-in-95 duration-150">
        <div class="bg-[#0f274a] text-white p-5 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-sky-500/20 text-sky-400 flex items-center justify-center font-bold text-sm border border-sky-400/30">
                    ➕
                </div>
                <div>
                    <h3 class="font-bold text-sm">Tambah Akun Petugas / Admin Bidang</h3>
                    <p class="text-[11px] text-sky-200/70">Buat akses login baru untuk petugas teknis DKIS Kota Cirebon</p>
                </div>
            </div>
            <button type="button" onclick="closeAddModal()" class="text-slate-400 hover:text-white p-1">
                ✕
            </button>
        </div>

        <form action="{{ route('admin.users.store') }}" method="POST" class="p-6 space-y-4 text-xs">
            @csrf
            <div>
                <label class="block font-bold text-slate-700 mb-1">Nama Lengkap Petugas *</label>
                <input type="text" name="nama" required placeholder="Contoh: Rian Hendrawan, S.Kom"
                    class="w-full rounded-xl border border-slate-300 p-2.5 focus:ring-2 focus:ring-sky-500 focus:outline-none">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Username Login *</label>
                    <input type="text" name="username" required placeholder="rian.hendrawan"
                        class="w-full rounded-xl border border-slate-300 p-2.5 focus:ring-2 focus:ring-sky-500 focus:outline-none">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Kata Sandi (Password) *</label>
                    <input type="password" name="password" required minlength="6" placeholder="Minimal 6 karakter"
                        class="w-full rounded-xl border border-slate-300 p-2.5 focus:ring-2 focus:ring-sky-500 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Peran Akses (Role) *</label>
                <select name="role_id" id="add_role_id" onchange="toggleBidangField('add')" required
                    class="w-full rounded-xl border border-slate-300 p-2.5 focus:ring-2 focus:ring-sky-500 focus:outline-none">
                    @foreach($roles as $r)
                        <option value="{{ $r->id }}" {{ $r->nama_role === 'Admin Bidang' ? 'selected' : '' }}>
                            {{ $r->nama_role }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div id="add_bidang_container">
                <div class="flex items-center justify-between mb-1">
                    <label class="block font-bold text-slate-700">Penugasan Bidang DKIS *</label>
                    <a href="{{ route('admin.bidang-master.index') }}" target="_blank" class="text-[11px] text-sky-600 hover:text-sky-800 font-semibold hover:underline flex items-center gap-1">
                        <span>➕ Kelola / Tambah Bidang</span>
                    </a>
                </div>
                <select name="bidang_id" id="add_bidang_id"
                    class="w-full rounded-xl border border-slate-300 p-2.5 focus:ring-2 focus:ring-sky-500 focus:outline-none">
                    <option value="">-- Pilih Bidang Penugasan --</option>
                    @foreach($bidangs as $b)
                        <option value="{{ $b->id }}">{{ $b->nama_lengkap }}</option>
                    @endforeach
                </select>
                <p class="text-[10px] text-slate-500 mt-1">Admin Bidang hanya dapat menangani tiket yang sesuai dengan bidangnya.</p>
            </div>

            <div class="pt-4 border-t border-slate-200 flex items-center justify-end gap-2">
                <button type="button" onclick="closeAddModal()" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-sky-600 hover:bg-sky-700 text-white font-bold shadow-sm transition">
                    Simpan Akun
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL UBAH PETUGAS -->
<!-- ========================================================================= -->
<div id="editModal" class="fixed inset-0 bg-slate-950/60 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-200 animate-in fade-in zoom-in-95 duration-150">
        <div class="bg-[#0f274a] text-white p-5 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-sky-500/20 text-sky-400 flex items-center justify-center font-bold text-sm border border-sky-400/30">
                    ✏️
                </div>
                <div>
                    <h3 class="font-bold text-sm">Ubah Profil Petugas</h3>
                    <p class="text-[11px] text-sky-200/70">Perbarui data penugasan atau reset kata sandi</p>
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
                <label class="block font-bold text-slate-700 mb-1">Nama Lengkap Petugas *</label>
                <input type="text" name="nama" id="edit_nama" required
                    class="w-full rounded-xl border border-slate-300 p-2.5 focus:ring-2 focus:ring-sky-500 focus:outline-none">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Username Login *</label>
                    <input type="text" name="username" id="edit_username" required
                        class="w-full rounded-xl border border-slate-300 p-2.5 focus:ring-2 focus:ring-sky-500 focus:outline-none">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Kata Sandi Baru (Opsional)</label>
                    <input type="password" name="password" minlength="6" placeholder="Kosongkan jika tidak diubah"
                        class="w-full rounded-xl border border-slate-300 p-2.5 focus:ring-2 focus:ring-sky-500 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Peran Akses (Role) *</label>
                <select name="role_id" id="edit_role_id" onchange="toggleBidangField('edit')" required
                    class="w-full rounded-xl border border-slate-300 p-2.5 focus:ring-2 focus:ring-sky-500 focus:outline-none">
                    @foreach($roles as $r)
                        <option value="{{ $r->id }}">{{ $r->nama_role }}</option>
                    @endforeach
                </select>
            </div>

            <div id="edit_bidang_container">
                <div class="flex items-center justify-between mb-1">
                    <label class="block font-bold text-slate-700">Penugasan Bidang DKIS</label>
                    <a href="{{ route('admin.bidang-master.index') }}" target="_blank" class="text-[11px] text-sky-600 hover:text-sky-800 font-semibold hover:underline flex items-center gap-1">
                        <span>➕ Kelola / Tambah Bidang</span>
                    </a>
                </div>
                <select name="bidang_id" id="edit_bidang_id"
                    class="w-full rounded-xl border border-slate-300 p-2.5 focus:ring-2 focus:ring-sky-500 focus:outline-none">
                    <option value="">-- Pilih Bidang Penugasan --</option>
                    @foreach($bidangs as $b)
                        <option value="{{ $b->id }}">{{ $b->nama_lengkap }}</option>
                    @endforeach
                </select>
            </div>

            <div class="pt-4 border-t border-slate-200 flex items-center justify-end gap-2">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-sky-600 hover:bg-sky-700 text-white font-bold shadow-sm transition">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    window.openAddModal = function() {
        document.getElementById('addModal').classList.remove('hidden');
        toggleBidangField('add');
    };

    window.closeAddModal = function() {
        document.getElementById('addModal').classList.add('hidden');
    };

    window.openEditModal = function(user) {
        document.getElementById('editForm').action = `/admin/users/${user.id}`;
        document.getElementById('edit_nama').value = user.nama;
        document.getElementById('edit_username').value = user.username;
        document.getElementById('edit_role_id').value = user.role_id;
        document.getElementById('edit_bidang_id').value = user.bidang_id || '';
        toggleBidangField('edit');
        document.getElementById('editModal').classList.remove('hidden');
    };

    window.closeEditModal = function() {
        document.getElementById('editModal').classList.add('hidden');
    };

    window.toggleBidangField = function(prefix) {
        const roleSelect = document.getElementById(`${prefix}_role_id`);
        const container = document.getElementById(`${prefix}_bidang_container`);
        const bidangSelect = document.getElementById(`${prefix}_bidang_id`);
        
        // Cek nama role berdasarkan text option terpilih
        const selectedText = roleSelect.options[roleSelect.selectedIndex]?.text || '';
        if (selectedText.includes('Pusat')) {
            container.classList.add('opacity-40', 'pointer-events-none');
            bidangSelect.value = '';
        } else {
            container.classList.remove('opacity-40', 'pointer-events-none');
        }
    };
</script>
@endsection
