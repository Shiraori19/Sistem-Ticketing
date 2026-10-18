@extends('teknisi.layout')

@section('content')
@php
    $userName = $user->nama ?? $user->name ?? 'Teknisi DKIS';
    $userBidang = $user->bidang?->nama_bidang ?? 'Bidang Persandian & CSIRT';
    $bidangKode = $user->bidang?->kode ?? 'PERSANDIAN';
    $userNip = $user->nip ?? '19920315 202012 2 004';
    $words = explode(' ', trim($userName));
    $initials = '';
    foreach(array_slice($words, 0, 2) as $w) {
        $initials .= strtoupper(substr($w, 0, 1));
    }

    $avail = $profile->availability ?? 'tersedia';
@endphp

<section class="v" id="v-profil" style="max-width: 1040px; margin: 0 auto; padding-bottom: 40px;">
    <!-- Profile Card Header -->
    <div class="card" style="padding: 24px; margin-bottom: 18px;">
        <div style="display: flex; flex-wrap: wrap; justify-content: space-between; gap: 20px;">
            <div style="display: flex; gap: 16px; flex: 1; min-width: 280px;">
                <div class="pfa" style="width: 64px; height: 64px; font-size: 24px;">{{ $initials ?: 'TK' }}</div>
                <div style="flex: 1;">
                    <h2 class="tt" style="font-size: 22px; margin: 0 0 4px;">{{ $userName }}</h2>
                    <div class="info" style="font-size: 13px; color: var(--mut);">
                        <b>{{ $userBidang }} ({{ $bidangKode }})</b> • NIP: {{ $userNip }}
                    </div>
                    <div style="font-size: 12px; color: var(--mut); margin-top: 2px;">
                        Dinas Komunikasi, Informatika dan Statistik Kota Cirebon • Akun Teknisi Lapangan
                    </div>

                    <!-- Availability Pill Badge -->
                    <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; align-items: center;">
                        @if($avail === 'tersedia')
                            <span class="tg selesai" style="font-size: 12px; padding: 3px 12px; border-radius: 99px;">
                                <span class="dot" style="background:#10B981"></span>Status: Tersedia (Siap Bertugas)
                            </span>
                        @elseif($avail === 'sibuk')
                            <span class="tg baru" style="font-size: 12px; padding: 3px 12px; border-radius: 99px; background:#FEF3C7; color:#92400E; border-color:#FDE68A;">
                                <span class="dot" style="background:#F59E0B"></span>Status: Sibuk (Fokus Lapangan)
                            </span>
                        @else
                            <span class="tg danger" style="font-size: 12px; padding: 3px 12px; border-radius: 99px; background:#FEE2E2; color:#991B1B; border-color:#FECACA;">
                                <span class="dot" style="background:#EF4444"></span>Status: Cuti {{ $profile->cuti_until ? '(s.d ' . $profile->cuti_until->translatedFormat('d M Y') . ')' : '' }}
                            </span>
                        @endif
                        <span style="font-size: 11.5px; padding: 3px 10px; border-radius: 99px; background: #EFF6FF; color: #1D4ED8; border: 1px solid #BFDBFE;">
                            Maks. Beban: {{ $profile->max_load ?: 6 }} Poin
                        </span>
                    </div>

                    <!-- Skills / Kompetensi -->
                    <div style="margin-top: 14px;">
                        <span style="font-size: 11.5px; font-weight: 700; color: var(--mut); display: block; margin-bottom: 6px;">Kompetensi Bidang:</span>
                        <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                            @foreach(($profile->skills ?: ['Jaringan & FO', 'Troubleshooting PC', 'Keamanan Siber', 'Server & Cloud']) as $skill)
                                <span style="font-size: 11.5px; font-weight: 600; padding: 2px 10px; background: #F8FAFC; color: var(--ink); border: 1px solid var(--line); border-radius: 99px;">
                                    ✓ {{ $skill }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 8px; align-self: flex-start;">
                <button type="button" class="btn o" style="font-size: 13px;" onclick="document.getElementById('modal-status').showModal()">
                    ⚙️ Perbarui Ketersediaan
                </button>
                <button type="button" class="btn" style="font-size: 13px;" onclick="document.getElementById('modal-password').showModal()">
                    🔒 Ubah Kata Sandi
                </button>
            </div>
        </div>
    </div>

    <!-- 4 Key Analytics KPI Cards from TechnicianStatsService -->
    <div style="margin-bottom: 20px;">
        <h3 style="margin: 0 0 12px; font-size: 16px; font-weight: 800; color: var(--ink);">
            📊 Kinerja & Statistik Layanan (Dihitung dari Rekam Jejak Ticket Events)
        </h3>
        <div class="stats">
            <div class="sk">
                <div class="h">
                    <span class="l">Tiket Selesai</span>
                    <span class="ico" style="background:#F0FDF4;color:#0D9488">✓</span>
                </div>
                <b>{{ $stats['total_completed'] }}</b>
                <span class="sub">Total penugasan disetujui</span>
            </div>
            <div class="sk">
                <div class="h">
                    <span class="l">Median Durasi</span>
                    <span class="ico" style="background:#EFF6FF;color:#2563EB">⏱️</span>
                </div>
                <b style="font-size: 20px;">{{ $stats['median_formatted'] }}</b>
                <span class="sub">Waktu respon hingga selesai</span>
            </div>
            <div class="sk">
                <div class="h">
                    <span class="l">SLA Mentah</span>
                    <span class="ico" style="background:#FFFBEB;color:#D97706">🛡️</span>
                </div>
                <b style="font-size: {{ $stats['has_enough_sample'] ? '24px' : '14px' }}; color: {{ $stats['has_enough_sample'] ? '#059669' : 'var(--mut)' }};">
                    {{ $stats['raw_percentage_label'] }}
                </b>
                <span class="sub">{{ $stats['raw_on_time_count'] }} tepat waktu (Syarat: $\ge$ 5 sampel)</span>
            </div>
            <div class="sk">
                <div class="h">
                    <span class="l">SLA Setelah Pengecualian</span>
                    <span class="ico" style="background:#F0FDF4;color:#059669">🎯</span>
                </div>
                <b style="font-size: {{ $stats['has_enough_sample'] ? '24px' : '14px' }}; color: {{ $stats['has_enough_sample'] ? '#059669' : 'var(--mut)' }};">
                    {{ $stats['excused_percentage_label'] }}
                </b>
                <span class="sub">{{ $stats['excused_on_time_count'] }} tepat waktu (Excused)</span>
            </div>
        </div>
    </div>

    <!-- Secondary Metrics: Rating, Reopen, and Category Breakdown -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 18px; margin-bottom: 20px;">
        <!-- Left: Rating & Reopen -->
        <div class="card" style="padding: 20px;">
            <h4 style="margin: 0 0 14px; font-size: 15px; font-weight: 700; color: var(--ink);">
                ⭐ Kepuasan Pelapor & Rekam Ulang
            </h4>
            <div style="display: grid; gap: 14px;">
                <div style="display: flex; align-items: center; justify-content: space-between; background: #F8FAFC; padding: 12px 14px; border-radius: 10px; border: 1px solid var(--line);">
                    <div>
                        <span style="font-size: 12px; color: var(--mut); display: block;">Rata-rata Rating Pelapor:</span>
                        <div style="display: flex; align-items: center; gap: 6px; margin-top: 2px;">
                            <span style="color: #F59E0B; font-size: 16px;">★★★★★</span>
                            <b style="font-size: 18px; color: var(--ink);">{{ $stats['avg_rating'] }}</b>
                            <span style="font-size: 11.5px; color: var(--mut);">({{ $stats['total_ratings'] }} ulasan)</span>
                        </div>
                    </div>
                    <span style="font-size: 28px;">🌟</span>
                </div>

                <div style="display: flex; align-items: center; justify-content: space-between; background: #F8FAFC; padding: 12px 14px; border-radius: 10px; border: 1px solid var(--line);">
                    <div>
                        <span style="font-size: 12px; color: var(--mut); display: block;">Frekuensi Tiket Dibuka Ulang (Reopen):</span>
                        <b style="font-size: 18px; color: {{ $stats['reopen_count'] > 0 ? '#BE123C' : '#059669' }};">
                            {{ $stats['reopen_count'] }} kali
                        </b>
                        <span style="font-size: 11.5px; color: var(--mut); display: block;">Semakin rendah semakin berkualitas</span>
                    </div>
                    <span style="font-size: 28px;">🔄</span>
                </div>
            </div>
        </div>

        <!-- Right: Breakdown Kategori Layanan -->
        <div class="card" style="padding: 20px;">
            <h4 style="margin: 0 0 14px; font-size: 15px; font-weight: 700; color: var(--ink);">
                📂 Distribusi Penanganan per Kategori Layanan
            </h4>
            @if(count($stats['category_breakdown']) > 0)
                <div style="display: grid; gap: 10px;">
                    @foreach($stats['category_breakdown'] as $catName => $count)
                        @php
                            $catPct = min(100, (int) round(($count / max(1, $stats['total_completed'])) * 100));
                        @endphp
                        <div>
                            <div style="display: flex; justify-content: space-between; font-size: 12.5px; margin-bottom: 4px;">
                                <b>{{ $catName }}</b>
                                <span>{{ $count }} tiket ({{ $catPct }}%)</span>
                            </div>
                            <div class="bar" style="height: 6px; background: #E2E8F0; border-radius: 99px; overflow: hidden;">
                                <i style="width: {{ $catPct }}%; background: var(--g); height: 100%; display: block; border-radius: 99px;"></i>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p style="margin: 0; font-size: 13px; color: var(--mut);">Belum ada riwayat penugasan selesai untuk memetakan kategori layanan.</p>
            @endif
        </div>
    </div>

    <!-- Security & Two-Factor Authentication Card -->
    <div class="card" style="padding: 20px;">
        <h4 style="margin: 0 0 12px; font-size: 15px; font-weight: 700; color: var(--ink); display: flex; align-items: center; gap: 8px;">
            <span>🛡️</span> Keamanan Akun & Otentikasi Dua Langkah (2FA)
        </h4>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; font-size: 13px;">
            <div style="background: #F8FAFC; padding: 14px; border-radius: 10px; border: 1px solid var(--line);">
                <span style="font-weight: 700; color: var(--ink); display: block; margin-bottom: 4px;">Two-Factor Authentication (2FA)</span>
                <p style="margin: 0 0 10px; font-size: 12px; color: var(--mut);">
                    Akun Anda terlindungi oleh lapisan otentikasi biometrik / TOTP saat login ke jaringan dinas.
                </p>
                <span style="display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 700; color: #065F46; background: #ECFDF5; padding: 3px 10px; border-radius: 99px;">
                    <span style="width: 6px; height: 6px; border-radius: 50%; background: #059669;"></span> 2FA Aktif & Terlindungi
                </span>
            </div>

            <div style="background: #F8FAFC; padding: 14px; border-radius: 10px; border: 1px solid var(--line);">
                <span style="font-weight: 700; color: var(--ink); display: block; margin-bottom: 4px;">Kata Sandi Akun</span>
                <p style="margin: 0 0 10px; font-size: 12px; color: var(--mut);">
                    Disarankan memperbarui kata sandi secara berkala minimal 3 bulan sekali demi standar audit keamanan DKIS.
                </p>
                <button type="button" class="btn o" style="font-size: 12px; padding: 4px 12px;" onclick="document.getElementById('modal-password').showModal()">
                    Ganti Kata Sandi Sekarang
                </button>
            </div>
        </div>
    </div>
</section>

<!-- Modal: Perbarui Ketersediaan -->
<dialog id="modal-status" style="border: 0; border-radius: 16px; padding: 0; width: 440px; max-width: 90vw; box-shadow: 0 20px 40px rgba(0,0,0,0.25);">
    <div style="padding: 18px 20px; background: var(--navy); color: #fff; display: flex; justify-content: space-between; align-items: center;">
        <b style="font-size: 16px;">Status Ketersediaan Teknisi</b>
        <button type="button" onclick="this.closest('dialog').close()" style="background: none; border: 0; color: #fff; font-size: 20px; cursor: pointer;">✕</button>
    </div>
    <form action="{{ route('teknisi.profil.ketersediaan') }}" method="POST" style="padding: 20px;">
        @csrf
        <div style="margin-bottom: 14px;">
            <label style="font-size: 12px; font-weight: 700; color: var(--ink); display: block; margin-bottom: 6px;">Pilih Status Ketersediaan:</label>
            <div style="display: grid; gap: 8px;">
                <label style="display: flex; align-items: center; gap: 10px; padding: 10px 14px; border-radius: 8px; border: 1px solid var(--line); cursor: pointer;">
                    <input type="radio" name="availability" value="tersedia" {{ $avail === 'tersedia' ? 'checked' : '' }} onchange="toggleCutiDate(false)">
                    <div>
                        <b style="font-size: 13px; color: #065F46;">🟢 Tersedia (Standby)</b>
                        <span style="font-size: 11.5px; color: var(--mut); display: block;">Siap menerima disposisi tugas baru dari Admin Bidang.</span>
                    </div>
                </label>
                <label style="display: flex; align-items: center; gap: 10px; padding: 10px 14px; border-radius: 8px; border: 1px solid var(--line); cursor: pointer;">
                    <input type="radio" name="availability" value="sibuk" {{ $avail === 'sibuk' ? 'checked' : '' }} onchange="toggleCutiDate(false)">
                    <div>
                        <b style="font-size: 13px; color: #B45309;">🟡 Sibuk (Di Lapangan)</b>
                        <span style="font-size: 11.5px; color: var(--mut); display: block;">Sedang fokus penanganan; tidak disarankan tugas mendesak.</span>
                    </div>
                </label>
                <label style="display: flex; align-items: center; gap: 10px; padding: 10px 14px; border-radius: 8px; border: 1px solid var(--line); cursor: pointer;">
                    <input type="radio" name="availability" value="cuti" {{ $avail === 'cuti' ? 'checked' : '' }} onchange="toggleCutiDate(true)">
                    <div>
                        <b style="font-size: 13px; color: #BE123C;">🔴 Cuti / Berhalangan</b>
                        <span style="font-size: 11.5px; color: var(--mut); display: block;">Admin Bidang akan otomatis diberitahu untuk penugasan ulang.</span>
                    </div>
                </label>
            </div>
        </div>

        <div id="cuti-date-wrapper" style="margin-bottom: 16px; display: {{ $avail === 'cuti' ? 'block' : 'none' }};">
            <label style="font-size: 12px; font-weight: 700; color: var(--ink); display: block; margin-bottom: 4px;">Cuti Berakhir Hingga Tanggal:</label>
            <input type="date" name="cuti_until" class="in" value="{{ $profile->cuti_until ? $profile->cuti_until->toDateString() : '' }}" style="width: 100%; font-size: 13px;">
        </div>

        <div style="display: flex; gap: 8px; justify-content: flex-end;">
            <button type="button" class="btn o" onclick="this.closest('dialog').close()">Batal</button>
            <button type="submit" class="btn">Simpan Ketersediaan</button>
        </div>
    </form>
</dialog>

<!-- Modal: Ganti Kata Sandi -->
<dialog id="modal-password" style="border: 0; border-radius: 16px; padding: 0; width: 440px; max-width: 90vw; box-shadow: 0 20px 40px rgba(0,0,0,0.25);">
    <div style="padding: 18px 20px; background: var(--navy); color: #fff; display: flex; justify-content: space-between; align-items: center;">
        <b style="font-size: 16px;">Ubah Kata Sandi Akun</b>
        <button type="button" onclick="this.closest('dialog').close()" style="background: none; border: 0; color: #fff; font-size: 20px; cursor: pointer;">✕</button>
    </div>
    <form action="{{ route('teknisi.profil.password') }}" method="POST" style="padding: 20px;">
        @csrf
        <div style="margin-bottom: 12px;">
            <label style="font-size: 12px; font-weight: 700; color: var(--ink); display: block; margin-bottom: 4px;">Kata Sandi Saat Ini:</label>
            <input type="password" name="current_password" class="in" required style="width: 100%; font-size: 13px;">
        </div>
        <div style="margin-bottom: 12px;">
            <label style="font-size: 12px; font-weight: 700; color: var(--ink); display: block; margin-bottom: 4px;">Kata Sandi Baru (Min. 8 Karakter):</label>
            <input type="password" name="password" class="in" required minlength="8" style="width: 100%; font-size: 13px;">
        </div>
        <div style="margin-bottom: 16px;">
            <label style="font-size: 12px; font-weight: 700; color: var(--ink); display: block; margin-bottom: 4px;">Konfirmasi Kata Sandi Baru:</label>
            <input type="password" name="password_confirmation" class="in" required minlength="8" style="width: 100%; font-size: 13px;">
        </div>
        <div style="display: flex; gap: 8px; justify-content: flex-end;">
            <button type="button" class="btn o" onclick="this.closest('dialog').close()">Batal</button>
            <button type="submit" class="btn">Simpan Kata Sandi</button>
        </div>
    </form>
</dialog>

<script>
    function toggleCutiDate(isCuti) {
        const el = document.getElementById('cuti-date-wrapper');
        if (el) el.style.display = isCuti ? 'block' : 'none';
    }
</script>
@endsection
