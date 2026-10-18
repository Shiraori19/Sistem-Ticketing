@extends('teknisi.layout')

@section('content')
<section class="v" id="v-tugas">
    <!-- Stat Cards (Antrean, Mendesak, Dikerjakan, Selesai) -->
    <div class="stats" id="stats">
        <a href="{{ route('teknisi.tugas.index', ['filter' => 'antrean']) }}" class="sk" style="text-decoration: none; cursor: pointer;">
            <div class="h">
                <span class="l">Antrean</span>
                <span class="ico" style="background:#EFF6FF;color:#1D4ED8;">📥</span>
            </div>
            <b style="color:#0F172A;">{{ $stats['antrean'] }}</b>
            <span class="sub">Menunggu konfirmasi tugas</span>
        </a>
        <a href="{{ route('teknisi.tugas.index', ['filter' => 'mendesak']) }}" class="sk {{ $stats['mendesak'] > 0 ? 'warn' : '' }}" style="text-decoration: none; cursor: pointer;">
            <div class="h">
                <span class="l">Mendesak</span>
                <span class="ico" style="background:#FFF1F2;color:#BE123C;">⏱️</span>
            </div>
            <b style="color:#BE123C;">{{ $stats['mendesak'] }}</b>
            <span class="sub">Sisa waktu &lt; 2 jam</span>
        </a>
        <a href="{{ route('teknisi.tugas.index', ['filter' => 'dikerjakan']) }}" class="sk" style="text-decoration: none; cursor: pointer;">
            <div class="h">
                <span class="l">Dikerjakan</span>
                <span class="ico" style="background:#FFFBEB;color:#92400E;">🛠️</span>
            </div>
            <b style="color:#B45309;">{{ $stats['dikerjakan'] }}</b>
            <span class="sub">Proses tindakan lapangan</span>
        </a>
        <a href="{{ route('teknisi.tugas.index', ['filter' => 'selesai']) }}" class="sk" style="text-decoration: none; cursor: pointer;">
            <div class="h">
                <span class="l">Selesai</span>
                <span class="ico" style="background:#ECFDF5;color:#065F46;">✅</span>
            </div>
            <b style="color:#065F46;">{{ $stats['selesai'] }}</b>
            <span class="sub">Disetujui bulan ini</span>
        </a>
    </div>

    <!-- Status Filter Pills -->
    <div class="pills" role="group" aria-label="Filter status">
        <a href="{{ route('teknisi.tugas.index', ['filter' => 'semua', 'q' => request('q')]) }}" class="pill" aria-pressed="{{ $filter === 'semua' ? 'true' : 'false' }}">
            <span class="dot" style="background:#6366F1"></span>
            Semua
        </a>
        <a href="{{ route('teknisi.tugas.index', ['filter' => 'antrean', 'q' => request('q')]) }}" class="pill" aria-pressed="{{ $filter === 'antrean' ? 'true' : 'false' }}">
            <span class="dot" style="background:#3B82F6"></span>
            Antrean <em>({{ $stats['antrean'] }})</em>
        </a>
        <a href="{{ route('teknisi.tugas.index', ['filter' => 'dikerjakan', 'q' => request('q')]) }}" class="pill" aria-pressed="{{ $filter === 'dikerjakan' ? 'true' : 'false' }}">
            <span class="dot" style="background:#F59E0B"></span>
            Dikerjakan <em>({{ $stats['dikerjakan'] }})</em>
        </a>
        <a href="{{ route('teknisi.tugas.index', ['filter' => 'review', 'q' => request('q')]) }}" class="pill" aria-pressed="{{ $filter === 'review' ? 'true' : 'false' }}">
            <span class="dot" style="background:#A855F7"></span>
            Menunggu Review
        </a>
        <a href="{{ route('teknisi.tugas.index', ['filter' => 'selesai', 'q' => request('q')]) }}" class="pill" aria-pressed="{{ $filter === 'selesai' ? 'true' : 'false' }}">
            <span class="dot" style="background:#10B981"></span>
            Selesai
        </a>
    </div>

    <!-- Search Form -->
    <form action="{{ route('teknisi.tugas.index') }}" method="GET" class="tools" style="display: flex; gap: 8px;">
        <input type="hidden" name="filter" value="{{ $filter }}">
        <input class="in" name="q" type="search" aria-label="Cari tiket" placeholder="Cari kode tiket, judul, OPD, atau nama pelapor..." value="{{ request('q') }}" style="flex: 1;">
        <button type="submit" class="btn" style="padding: 0 16px;">Cari</button>
        @if(request('q'))
            <a href="{{ route('teknisi.tugas.index', ['filter' => $filter]) }}" class="btn o" style="display: inline-flex; align-items: center;">Reset</a>
        @endif
    </form>

    <!-- Task List -->
    <div class="list" style="display: grid; gap: 12px; margin-top: 14px;">
        @forelse($assignments as $assign)
            @php
                $ticket = $assign->ticket;
                $tlp = strtoupper($ticket->tlp_level ?? 'GREEN');
                $tlpColors = [
                    'RED' => '#E11D48',
                    'AMBER' => '#D97706',
                    'GREEN' => '#059669',
                    'CLEAR' => '#64748B',
                ];
                $tlpColor = $tlpColors[$tlp] ?? '#059669';

                // SLA Calculation
                $now = \Carbon\Carbon::now();
                $deadline = $ticket->sla_deadline_at ? \Carbon\Carbon::parse($ticket->sla_deadline_at) : ($ticket->sla_dimulai_at ? \Carbon\Carbon::parse($ticket->sla_dimulai_at)->addHours((float)($ticket->sla_resolusi_jam ?: 4)) : $now->copy()->addHours(4));
                $minsLeft = (int) $now->diffInMinutes($deadline, false);
                $totalMins = max(1, (int) round(((float)($ticket->sla_resolusi_jam ?: 4)) * 60));
                $usedPercent = max(0, min(100, (int) round((1 - ($minsLeft / $totalMins)) * 100)));
                $barColor = $minsLeft < 0 ? '#BE123C' : ($usedPercent >= 80 ? '#E11D48' : ($usedPercent >= 60 ? '#D97706' : '#1F6E60'));

                $statusTags = [
                    'assigned' => ['tg' => 'baru', 'label' => 'Antrean Baru'],
                    'working' => ['tg' => 'kerja', 'label' => 'Sedang Dikerjakan'],
                    'submitted' => ['tg' => 'review', 'label' => 'Menunggu Review'],
                    'approved' => ['tg' => 'selesai', 'label' => 'Selesai & Disetujui'],
                    'declined' => ['tg' => 'danger', 'label' => 'Ditolak'],
                    'released' => ['tg' => 'danger', 'label' => 'Dilepas Otomatis'],
                    'returned' => ['tg' => 'warn', 'label' => 'Perlu Revisi'],
                ];
                $tagInfo = $statusTags[$assign->status] ?? ['tg' => 'baru', 'label' => ucfirst($assign->status)];
            @endphp
            <article class="tk" style="border-radius: 14px; border: 1px solid var(--line); background: #fff; padding: 18px; box-shadow: var(--shadow-sm); display: flex; flex-direction: column; gap: 12px;">
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <span class="code" style="font-weight: 800;">{{ $ticket->kode_tiket }}</span>
                        <span style="font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 99px; color: #fff; background: {{ $tlpColor }};">
                            TLP: {{ $tlp }}
                        </span>
                        <span class="tg {{ $tagInfo['tg'] }}" style="font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 99px;">
                            {{ $tagInfo['label'] }}
                        </span>
                        <span style="font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 99px; background: #F1F5F9; color: var(--mut);">
                            {{ strtoupper($assign->role) }}
                        </span>
                    </div>

                    <!-- SLA Time Badge -->
                    <div>
                        @if(in_array($assign->status, ['assigned', 'working']))
                            @if($minsLeft < 0)
                                <span style="font-size: 12px; font-weight: 800; color: #BE123C; background: #FFF1F2; padding: 3px 10px; border-radius: 8px; border: 1px solid #FECACA;">
                                    ⚠️ Lewat SLA {{ abs(floor($minsLeft/60)) }}j {{ abs($minsLeft%60) }}m
                                </span>
                            @else
                                <span style="font-size: 12px; font-weight: 700; color: {{ $minsLeft < 120 ? '#B45309' : '#0F766E' }}; background: {{ $minsLeft < 120 ? '#FFFBEB' : '#ECFDF5' }}; padding: 3px 10px; border-radius: 8px; border: 1px solid {{ $minsLeft < 120 ? '#FDE68A' : '#A7F3D0' }};">
                                    ⏱️ Sisa {{ floor($minsLeft/60) }}j {{ $minsLeft%60 }}m
                                </span>
                            @endif
                        @elseif($assign->status === 'submitted')
                            <span style="font-size: 12px; font-weight: 700; color: #7C3AED; background: #FAF5FF; padding: 3px 10px; border-radius: 8px;">
                                ⏳ Diajukan {{ $assign->submitted_at?->diffForHumans() }}
                            </span>
                        @elseif($assign->status === 'approved')
                            <span style="font-size: 12px; font-weight: 700; color: #059669; background: #ECFDF5; padding: 3px 10px; border-radius: 8px;">
                                ✓ Tuntas Disetujui
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Title & OPD -->
                <div>
                    <h3 style="margin: 0 0 6px; font-size: 16px; font-weight: 800; color: var(--ink);">
                        <a href="{{ route('teknisi.tugas.show', $assign->id) }}" style="color: inherit; text-decoration: none;">
                            {{ $ticket->judul }}
                        </a>
                    </h3>
                    <div style="font-size: 12.5px; color: var(--mut); line-height: 1.5;">
                        <b>{{ $ticket->skpd }}</b> • Pelapor: {{ $ticket->nama_pelapor }} • Kategori: {{ $ticket->category?->nama_kategori ?: 'Layanan TI' }}
                    </div>
                </div>

                <!-- SLA Progress Bar (Jika aktif) -->
                @if(in_array($assign->status, ['assigned', 'working']))
                    <div class="bar" style="height: 6px; background: #E2E8F0; border-radius: 99px; overflow: hidden;">
                        <i style="width: {{ $usedPercent }}%; background: {{ $barColor }}; height: 100%; display: block; border-radius: 99px;"></i>
                    </div>
                @endif

                <!-- Footer Card & Action Buttons -->
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap; pt: 6px;">
                    <div style="font-size: 11.5px; color: var(--mut);">
                        Ditugaskan: {{ $assign->assigned_at ? $assign->assigned_at->translatedFormat('d M Y, H:i') : '-' }}
                    </div>
                    <div style="display: flex; gap: 8px;">
                        @if($assign->status === 'assigned')
                            <form action="{{ route('teknisi.tugas.terima', $assign->id) }}" method="POST" style="margin: 0;">
                                @csrf
                                <input type="hidden" name="row_version" value="{{ $assign->row_version }}">
                                <button type="submit" class="btn" style="padding: 6px 14px; font-size: 12px;">
                                    Terima Tugas
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('teknisi.tugas.show', $assign->id) }}" class="btn o" style="padding: 6px 14px; font-size: 12px; text-decoration: none;">
                            Buka Detail →
                        </a>
                    </div>
                </div>
            </article>
        @empty
            <div class="empty" style="text-align: center; padding: 48px 20px; background: #fff; border-radius: 14px; border: 1px dashed var(--line);">
                <div style="font-size: 40px; margin-bottom: 10px;">📋</div>
                <b style="font-size: 16px; color: var(--ink);">Tidak Ada Penugasan</b>
                <p style="margin: 6px 0 0; font-size: 13px; color: var(--mut);">
                    Tidak ada tiket penugasan yang sesuai dengan filter ini.
                </p>
            </div>
        @endforelse

        <!-- Pagination -->
        @if($assignments->hasPages())
            <div style="margin-top: 16px;">
                {{ $assignments->links() }}
            </div>
        @endif
    </div>
</section>
@endsection
