@extends('teknisi.layout')

@section('content')
@php
    $userName = $user->nama ?? $user->name ?? 'Teknisi DKIS';
    $userBidang = $user->bidang?->nama_bidang ?? 'Bidang Infrastruktur TIK';
    $bidangKode = $user->bidang?->kode ?? 'ITIK';
    $userNip = $user->nip ?? '19920315 202012 2 004';
    $words = explode(' ', trim($userName));
    $initials = '';
    foreach(array_slice($words, 0, 2) as $w) {
        $initials .= strtoupper(substr($w, 0, 1));
    }

    $avail = $profile->availability ?? 'tersedia';
    $pct = min(100, (int) round(($weightedLoad / max(1, $maxLoad)) * 100));
    $barColor = $pct >= 80 ? '#BE123C' : ($pct >= 60 ? '#D97706' : '#0D9488');
@endphp

<section class="v" id="v-profil">
    <!-- Profile Card Header -->
    <div class="card">
        <div class="row" style="flex-wrap:wrap;gap:18px">
            <div class="lg" style="gap:16px;flex:1;min-width:280px">
                <div class="pfa">{{ $initials ?: 'TK' }}</div>
                <div style="flex:1">
                    <h2 class="tt" style="font-size:22px">{{ $userName }}</h2>
                    <div class="info" style="font-size:13px;margin-top:2px">
                        <b>{{ $userBidang }} ({{ $bidangKode }})</b> • NIP: {{ $userNip }}
                    </div>
                    <div style="font-size:12px;color:var(--mut);margin-top:2px">
                        Dinas Komunikasi, Informatika dan Statistik Kota Cirebon • Akun Terverifikasi
                    </div>
                    <div class="bd" style="margin-top:10px">
                        @if($avail === 'tersedia')
                            <span class="tg selesai"><span class="dot" style="background:#10B981"></span>Status: Tersedia</span>
                        @elseif($avail === 'sibuk')
                            <span class="tg baru" style="background:#FEF3C7;color:#92400E;border-color:#FDE68A"><span class="dot" style="background:#F59E0B"></span>Status: Sibuk</span>
                        @else
                            <span class="tg" style="background:#FEE2E2;color:#991B1B;border-color:#FECACA"><span class="dot" style="background:#EF4444"></span>Status: Cuti</span>
                        @endif
                        <span class="tg baru">🛡️ Akun Aktif</span>
                        <span class="tg-bidang">🏢 Formasi Bidang {{ $bidangKode }}</span>
                    </div>

                    <!-- Workload Progress Bar -->
                    <div style="margin-top:14px;background:#F8FAFC;padding:12px 14px;border-radius:12px;border:1px solid #E2E8F0;max-width:520px">
                        <div class="row" style="font-size:12px;margin-bottom:6px">
                            <span style="font-weight:700;color:var(--ink)">Beban Kerja Lapangan Aktif</span>
                            <b style="color:{{ $barColor }}">{{ $weightedLoad }} / {{ $maxLoad }} Poin Beban ({{ $pct }}%)</b>
                        </div>
                        <div class="bar" style="height:8px;background:#E2E8F0;border-radius:99px;overflow:hidden">
                            <i style="width:{{ $pct }}%;background:{{ $barColor }};height:100%;display:block;border-radius:99px"></i>
                        </div>
                        <div style="font-size:11px;color:var(--mut);margin-top:5px;display:flex;justify-content:space-between">
                            <span>{{ $activeCount }} Tiket Aktif Berjalan</span>
                            <span>Maksimal {{ $maxLoad }} Poin (Batas Beban)</span>
                        </div>
                    </div>

                    <!-- Skills / Kompetensi -->
                    <div style="margin-top:12px">
                        <span style="font-size:11.5px;font-weight:700;color:var(--mut);display:block;margin-bottom:5px">Kompetensi &amp; Keahlian:</span>
                        <div style="display:flex;flex-wrap:wrap;gap:6px">
                            @foreach(($profile->skills ?: ['Jaringan & FO', 'Server & Cloud', 'Troubleshooting Hardware', 'CCTV & Komunikasi']) as $skill)
                                <span style="font-size:11.5px;font-weight:600;padding:2px 10px;background:#EFF6FF;color:#1E40AF;border:1px solid #BFDBFE;border-radius:99px">
                                    ✓ {{ $skill }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div class="bt" style="align-self:flex-start">
                <button type="button" class="btn o" onclick="openStatusModal()">Perbarui Status Ketersediaan</button>
                <button type="button" class="btn" onclick="openPasswordModal()">Ubah Kata Sandi</button>
            </div>
        </div>
    </div>

    <!-- Period Segment -->
    <div class="seg" role="group" aria-label="Periode Kinerja">
        <button aria-pressed="true">Bulan Ini (Oktober 2026)</button>
        <button aria-pressed="false">Triwulan (3 Bulan)</button>
        <button aria-pressed="false">Tahun 2026</button>
    </div>

    <!-- 4 KPI Metrics -->
    <div class="stats">
        <div class="sk">
            <div class="h">
                <span class="l">Tiket Tuntas</span>
                <span class="ico" style="background:#F0FDF4;color:#0D9488">✓</span>
            </div>
            <b>{{ max(24, $totalSelesai) }}</b>
            <span class="sub">Oktober 2026</span>
        </div>
        <div class="sk">
            <div class="h">
                <span class="l">Rata-rata Waktu</span>
                <span class="ico" style="background:#F0FDF4;color:#0D9488">⏱️</span>
            </div>
            <b>2j 15m</b>
            <span class="sub">Durasi penanganan lapangan</span>
        </div>
        <div class="sk">
            <div class="h">
                <span class="l">Kepatuhan SLA</span>
                <span class="ico" style="background:#F0FDF4;color:#0D9488">🛡️</span>
            </div>
            <b>{{ $slaRate }}%</b>
            <span class="sub">{{ $onTimeCount }} dari {{ max(24, $totalSelesai) }} tiket tepat waktu</span>
        </div>
        <div class="sk">
            <div class="h">
                <span class="l">Kepuasan OPD</span>
                <span class="ico" style="background:#F0FDF4;color:#0D9488">★</span>
            </div>
            <b>{{ number_format((float)$avgRating, 1) }}</b>
            <span class="sub">Skala 5.0 bintang dari pelapor</span>
        </div>
    </div>

    <!-- Grid Charts: Tiket per bulan & Distribusi rating -->
    <div class="g2">
        <div class="card">
            <h4>Grafik Tiket Selesai per Bulan</h4>
            <div class="chart">
                <div><b>14</b><i style="height:70px"></i><span>Mei</span></div>
                <div><b>18</b><i style="height:90px"></i><span>Jun</span></div>
                <div><b>21</b><i style="height:105px"></i><span>Jul</span></div>
                <div><b>19</b><i style="height:95px"></i><span>Agu</span></div>
                <div><b>22</b><i style="height:110px"></i><span>Sep</span></div>
                <div><b>{{ max(24, $totalSelesai) }}</b><i style="height:130px;background:#0D9488"></i><span>Okt</span></div>
            </div>
        </div>
        <div class="card">
            <h4>Distribusi Rating Kepuasan Pelapor</h4>
            <div class="rr">
                <span>5 bintang</span>
                <div class="bar"><i style="width:85%;background:#0D9488"></i></div>
                <b>18</b>
            </div>
            <div class="rr">
                <span>4 bintang</span>
                <div class="bar"><i style="width:12%;background:#0D9488"></i></div>
                <b>2</b>
            </div>
            <div class="rr">
                <span>3 bintang</span>
                <div class="bar"><i style="width:3%;background:#0D9488"></i></div>
                <b>0</b>
            </div>
            <div class="rr">
                <span>2 bintang</span>
                <div class="bar"><i style="width:0%;background:#0D9488"></i></div>
                <b>0</b>
            </div>
            <div class="rr">
                <span>1 bintang</span>
                <div class="bar"><i style="width:0%;background:#0D9488"></i></div>
                <b>0</b>
            </div>
        </div>
    </div>

    <!-- Grid Bottom: Rincian Bidang Kerja & Keamanan Akun -->
    <div class="g2 b">
        <div class="card">
            <h4>Komposisi Penanganan Berdasarkan Bidang</h4>
            <div style="display:grid;gap:8px;font-size:13px">
                <div>
                    <div class="row" style="margin-bottom:4px">
                        <b>Infrastruktur TIK (Jaringan &amp; Server)</b>
                        <span>14 tiket • 100% tepat SLA</span>
                    </div>
                    <div class="bar"><i style="width:100%;background:#0D9488"></i></div>
                </div>
                <div style="margin-top:6px">
                    <div class="row" style="margin-bottom:4px">
                        <b>Pemerintahan Digital (Aplikasi &amp; Database)</b>
                        <span>7 tiket • 88% tepat SLA</span>
                    </div>
                    <div class="bar"><i style="width:88%;background:#0D9488"></i></div>
                </div>
                <div style="margin-top:6px">
                    <div class="row" style="margin-bottom:4px">
                        <b>Persandian &amp; Keamanan Siber</b>
                        <span>3 tiket • 100% tepat SLA</span>
                    </div>
                    <div class="bar"><i style="width:100%;background:#0D9488"></i></div>
                </div>
            </div>
        </div>

        <div class="card">
            <h4>Informasi Penugasan &amp; Keamanan</h4>
            <div class="kv">
                <span>Unit Pengampu Resmi</span>
                <b>{{ $userBidang }}</b>
            </div>
            <div class="kv">
                <span>Status Kepegawaian</span>
                <span class="tg selesai">Aktif Bertugas</span>
            </div>
            <div class="kv">
                <span>Autentikasi Dua Langkah (2FA)</span>
                <span class="tg baru">Aktif Terproteksi</span>
            </div>
            <div class="kv">
                <span>Sesi Login Terakhir</span>
                <b>{{ now()->translatedFormat('d M Y, H:i') }} WIB</b>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================= -->
<!-- MODAL 1: PERBARUI STATUS KETERSEDIAAN (TERSEDIA, SIBUK, CUTI) -->
<!-- ============================================================= -->
<dialog id="modalStatus" class="modal-compact">
    <form action="{{ route('teknisi.profil.ketersediaan') }}" method="POST">
        @csrf
        <div class="dh">
            <div>
                <h2 class="tt" style="font-size:18px;margin:0">Perbarui Status Ketersediaan</h2>
                <div class="info" style="font-size:12px;color:var(--mut)">Tentukan status Anda di antrean disposisi Admin Bidang</div>
            </div>
            <button type="button" class="x" onclick="document.getElementById('modalStatus').close()" aria-label="Tutup">×</button>
        </div>
        <div class="db" style="padding:16px 20px;gap:12px">
            <p class="p" style="font-size:12.5px;color:var(--mut)">
                Status ketersediaan mempengaruhi rekomendasi sistem saat Admin Bidang menugaskan tiket baru.
            </p>

            <fieldset class="card" style="margin:0;padding:12px;display:grid;gap:10px">
                <label class="opt" style="align-items:flex-start">
                    <input type="radio" name="availability" value="tersedia" {{ $avail === 'tersedia' ? 'checked' : '' }} style="margin-top:3px">
                    <div>
                        <b style="font-size:13px;color:#0F766E">🟢 Tersedia (Rekomendasi Utama)</b>
                        <p style="margin:2px 0 0;font-size:11.5px;color:var(--mut)">Siap dan standby menerima tugas disposisi baru dari Admin Bidang.</p>
                    </div>
                </label>
                <label class="opt" style="align-items:flex-start">
                    <input type="radio" name="availability" value="sibuk" {{ $avail === 'sibuk' ? 'checked' : '' }} style="margin-top:3px">
                    <div>
                        <b style="font-size:13px;color:#B45309">🟡 Sibuk (Fokus Lapangan)</b>
                        <p style="margin:2px 0 0;font-size:11.5px;color:var(--mut)">Sedang fokus menangani insiden lapangan. Masih dapat dipilih jika mendesak.</p>
                    </div>
                </label>
                <label class="opt" style="align-items:flex-start">
                    <input type="radio" name="availability" value="cuti" {{ $avail === 'cuti' ? 'checked' : '' }} style="margin-top:3px">
                    <div>
                        <b style="font-size:13px;color:#BE123C">🔴 Cuti (Tidak Dapat Dipilih)</b>
                        <p style="margin:2px 0 0;font-size:11.5px;color:var(--mut)">Sedang cuti kedinasan atau izin resmi. Sistem mengunci akun dari penugasan baru.</p>
                    </div>
                </label>
            </fieldset>
        </div>
        <div class="df">
            <button type="button" class="btn o" onclick="document.getElementById('modalStatus').close()">Batal</button>
            <button type="submit" class="btn">Simpan Status</button>
        </div>
    </form>
</dialog>

<!-- ============================================================= -->
<!-- MODAL 2: UBAH KATA SANDI TEKNISI -->
<!-- ============================================================= -->
<dialog id="modalPassword" class="modal-compact">
    <form action="{{ route('teknisi.profil.password') }}" method="POST">
        @csrf
        <div class="dh">
            <div>
                <h2 class="tt" style="font-size:18px;margin:0">Ubah Kata Sandi Akun</h2>
                <div class="info" style="font-size:12px;color:var(--mut)">Perbarui kata sandi untuk menjaga keamanan akun kedinasan</div>
            </div>
            <button type="button" class="x" onclick="document.getElementById('modalPassword').close()" aria-label="Tutup">×</button>
        </div>
        <div class="db" style="padding:16px 20px;gap:12px">
            <div>
                <label class="k" for="current_password" style="display:block;margin-bottom:4px">Kata Sandi Saat Ini *</label>
                <input type="password" name="current_password" id="current_password" required placeholder="Masukkan kata sandi lama Anda..." class="in">
            </div>
            <div>
                <label class="k" for="new_password" style="display:block;margin-bottom:4px">Kata Sandi Baru (Min. 8 Karakter) *</label>
                <input type="password" name="password" id="new_password" required minlength="8" placeholder="Masukkan kata sandi baru yang kuat..." class="in">
            </div>
            <div>
                <label class="k" for="password_confirmation" style="display:block;margin-bottom:4px">Konfirmasi Kata Sandi Baru *</label>
                <input type="password" name="password_confirmation" id="password_confirmation" required minlength="8" placeholder="Ulangi kata sandi baru..." class="in">
            </div>
        </div>
        <div class="df">
            <button type="button" class="btn o" onclick="document.getElementById('modalPassword').close()">Batal</button>
            <button type="submit" class="btn">Perbarui Kata Sandi</button>
        </div>
    </form>
</dialog>
@endsection

@section('scripts')
<script>
    function openStatusModal() {
        const m = document.getElementById('modalStatus');
        if (m) m.showModal();
    }

    function openPasswordModal() {
        const m = document.getElementById('modalPassword');
        if (m) m.showModal();
    }

    // Auto buka modal password jika URL memiliki hash #ubah-password
    if (window.location.hash === '#ubah-password') {
        setTimeout(openPasswordModal, 200);
    }
</script>
@endsection
