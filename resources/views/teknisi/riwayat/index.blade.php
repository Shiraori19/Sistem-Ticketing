@extends('teknisi.layout', ['title' => 'Riwayat Pekerjaan Selesai — Helpdesk Teknisi'])

@section('content')
<section class="v" id="v-riwayat">
    <!-- Header Halaman -->
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div>
            <h2 style="margin:0;font-size:20px;font-weight:800;color:var(--ink)">Riwayat Penanganan Selesai</h2>
            <p style="margin:4px 0 0;font-size:13px;color:var(--mut)">Daftar tiket penanganan lapangan yang telah tuntas dan disetujui oleh Admin Bidang beserta ulasan pelapor.</p>
        </div>
        <div style="background:#fff;border:1px solid var(--line);padding:6px 14px;border-radius:12px;font-size:12px;font-weight:700;color:var(--g)">
            Total Selesai: {{ $completedAssignments->total() }} Tiket
        </div>
    </div>

    <!-- Rating Filter Pills -->
    @php
        $currentRating = request('rating', 'semua');
    @endphp
    <div class="pills" id="rf" role="group" aria-label="Filter rating kepuasan">
        <a href="{{ route('teknisi.riwayat') }}" class="pill {{ $currentRating === 'semua' || empty($currentRating) ? 'active' : '' }}" aria-pressed="{{ $currentRating === 'semua' || empty($currentRating) ? 'true' : 'false' }}">
            ⭐ Semua Ulasan ({{ $completedAssignments->total() }})
        </a>
        <a href="{{ route('teknisi.riwayat', ['rating' => '5']) }}" class="pill {{ $currentRating === '5' ? 'active' : '' }}" aria-pressed="{{ $currentRating === '5' ? 'true' : 'false' }}">
            ★★★★★ 5 Bintang
        </a>
        <a href="{{ route('teknisi.riwayat', ['rating' => '4']) }}" class="pill {{ $currentRating === '4' ? 'active' : '' }}" aria-pressed="{{ $currentRating === '4' ? 'true' : 'false' }}">
            ★★★★☆ 4 Bintang
        </a>
        <a href="{{ route('teknisi.riwayat', ['rating' => '3']) }}" class="pill {{ $currentRating === '3' ? 'active' : '' }}" aria-pressed="{{ $currentRating === '3' ? 'true' : 'false' }}">
            ★★★☆☆ 3 Bintang
        </a>
    </div>

    <!-- History List -->
    <div class="list" id="hist">
        @forelse($completedAssignments as $assignment)
            @php
                $ticket = $assignment->ticket;
                $tglSelesai = $assignment->approved_at 
                    ? $assignment->approved_at->translatedFormat('d M Y, H:i') 
                    : ($assignment->submitted_at ? $assignment->submitted_at->translatedFormat('d M Y, H:i') : '-');
                
                $durText = '-';
                if ($assignment->started_at && $assignment->submitted_at) {
                    $diffMins = $assignment->started_at->diffInMinutes($assignment->submitted_at);
                    $durText = $diffMins >= 60 ? floor($diffMins / 60) . ' jam ' . ($diffMins % 60) . ' menit' : max(1, $diffMins) . ' menit';
                } elseif ($assignment->assigned_at && $assignment->approved_at) {
                    $diffMins = $assignment->assigned_at->diffInMinutes($assignment->approved_at);
                    $durText = $diffMins >= 60 ? floor($diffMins / 60) . ' jam ' . ($diffMins % 60) . ' menit' : max(1, $diffMins) . ' menit';
                }

                $ratingVal = $ticket?->rating ? (float)$ticket->rating : null;
            @endphp
            <article class="tk" style="box-shadow:var(--shadow-sm);border-color:#E2E8F0;background:#fff;border-radius:16px;padding:16px;display:flex;flex-direction:column;gap:12px">
                <div class="bd" style="display:flex;align-items:center;flex-wrap:wrap;gap:8px;font-size:12px">
                    <span class="code">{{ $ticket?->kode_tiket ?? '-' }}</span>
                    <span class="tg selesai">✅ Selesai &amp; Disetujui</span>
                    <span style="font-size:12px;color:var(--mut)">📅 {{ $tglSelesai }} WIB &bull; ⏱️ Durasi Pengerjaan: <b>{{ $durText }}</b></span>
                    @if($ticket?->category)
                        <span class="tg tg-cat" style="background:#F1F5F9;border:1px solid #CBD5E1;padding:2px 8px;border-radius:6px;font-size:11px">
                            🏷️ {{ $ticket->category->nama_kategori }}
                        </span>
                    @endif
                </div>

                <div>
                    <h3 class="tt" style="font-size:17px;font-weight:700;color:#0F172A;margin:0 0 6px">
                        {{ $ticket?->judul ?? 'Tiket Penugasan' }}
                    </h3>
                    <div class="info" style="font-size:12.5px;color:var(--mut)">
                        🏛️ <b>{{ $ticket?->skpd ?? 'OPD Kota Cirebon' }}</b> &bull; Pelapor: <b>{{ $ticket?->nama_pelapor ?? '-' }}</b>
                    </div>
                </div>

                <!-- Solusi Teknis Perbaikan -->
                <div style="background:#F8FAFC;padding:12px 14px;border-radius:10px;border:1px solid #E2E8F0;font-size:13px">
                    <b style="color:#0F766E">Solusi Teknis Perbaikan:</b>
                    <p style="margin:4px 0 0;color:#1E293B;line-height:1.5">
                        {{ $assignment->notes ?: 'Pekerjaan teknis tuntas dilakukan sesuai arahan dan SOP perbaikan DKIS.' }}
                    </p>
                    @if($assignment->review_notes)
                        <div style="margin-top:8px;padding-top:8px;border-top:1px dashed #CBD5E1;font-size:12px;color:#334155">
                            <b>Catatan Verifikasi Admin:</b> {{ $assignment->review_notes }}
                        </div>
                    @endif
                </div>

                <!-- Ulasan & Rating Pelapor -->
                @if($ratingVal)
                    <div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:10px;padding:12px 14px;display:flex;flex-direction:column;gap:6px">
                        <div style="display:flex;align-items:center;gap:8px">
                            <span class="stars" style="color:#D97706;font-size:16px" aria-label="Rating {{ $ratingVal }} dari 5">
                                {{ str_repeat('★', (int)$ratingVal) }}{{ str_repeat('☆', max(0, 5 - (int)$ratingVal)) }}
                            </span>
                            <b style="font-size:14px;color:#92400E">{{ number_format($ratingVal, 1) }}</b>
                            <span style="font-size:11.5px;color:#78350F;font-weight:600">(Ulasan Resmi Pelapor)</span>
                        </div>
                        @if($ticket?->feedback)
                            <blockquote style="margin:0;font-style:italic;color:#451A03;font-size:12.5px;line-height:1.4">
                                &ldquo;{{ $ticket->feedback }}&rdquo;
                            </blockquote>
                        @else
                            <p style="margin:0;font-size:12px;color:#78350F;font-style:italic">
                                Pelapor memberikan bintang tanpa catatan tambahan.
                            </p>
                        @endif
                    </div>
                @else
                    <div style="font-size:12px;color:var(--mut);font-style:italic;display:flex;align-items:center;gap:6px">
                        <span>ℹ️</span> Belum ada ulasan rating dari pelapor untuk tiket ini.
                    </div>
                @endif

                <!-- Aksi Detail -->
                <div style="display:flex;justify-content:flex-end;margin-top:4px">
                    <a href="{{ route('teknisi.tugas.show', $assignment->id) }}" class="btn o" style="min-height:36px;font-size:12px;padding:0 14px">
                        Lihat Berkas &amp; Detail Tugas &rarr;
                    </a>
                </div>
            </article>
        @empty
            <div style="background:#fff;border:1px solid var(--line);border-radius:16px;padding:48px 24px;text-align:center">
                <div style="font-size:42px;margin-bottom:12px">⭐</div>
                <b style="font-size:16px;color:var(--ink);display:block;margin-bottom:6px">Belum Ada Riwayat Selesai</b>
                <p style="margin:0 auto;font-size:13px;color:var(--mut);max-width:440px">
                    @if($currentRating !== 'semua' && !empty($currentRating))
                        Tidak ada riwayat penanganan tuntas dengan filter rating {{ $currentRating }} bintang.
                    @else
                        Seluruh penugasan yang telah Anda rampungkan dan diverifikasi oleh Admin Bidang akan tercatat di sini lengkap beserta rating ulasan pelapor.
                    @endif
                </p>
                @if($currentRating !== 'semua' && !empty($currentRating))
                    <div style="margin-top:16px">
                        <a href="{{ route('teknisi.riwayat') }}" class="btn o" style="display:inline-flex;min-height:36px;font-size:12px">
                            Reset Filter Rating
                        </a>
                    </div>
                @endif
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($completedAssignments->hasPages())
        <div style="margin-top:16px;display:flex;justify-content:center">
            {{ $completedAssignments->links() }}
        </div>
    @endif
</section>
@endsection
