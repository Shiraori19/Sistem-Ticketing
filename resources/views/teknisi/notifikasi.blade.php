@extends('teknisi.layout')

@section('content')
<section class="v" id="v-notifikasi">
    <div class="row">
        <div>
            <h2 style="margin:0;font-size:20px;font-weight:800;color:var(--ink)">Pemberitahuan &amp; Notifikasi Lapangan</h2>
            <p style="margin:4px 0 0;font-size:13px;color:var(--mut)">Peringatan tugas baru, tenggat waktu SLA ketat, dan catatan pengembalian dari Admin Bidang.</p>
        </div>
    </div>

    <div class="list" style="margin-top:16px">
        @forelse($items as $item)
            <div class="card" style="padding:16px;border-left:4px solid {{ ($item['badge_color'] ?? '') === 'rose' ? '#E11D48' : (($item['badge_color'] ?? '') === 'amber' ? '#D97706' : '#2563EB') }};margin-bottom:12px">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
                    <span class="tg {{ ($item['badge_color'] ?? '') === 'rose' ? 'tolak' : (($item['badge_color'] ?? '') === 'amber' ? 'hamb' : 'kerja') }}">
                        {{ $item['badge'] ?? 'Info' }}
                    </span>
                    <span style="font-size:12px;color:var(--mut)">{{ $item['time'] ?? 'Baru saja' }}</span>
                </div>
                <h4 style="margin:4px 0 6px;font-size:15px;color:var(--ink)">{{ $item['title'] ?? '-' }}</h4>
                <p style="margin:0 0 10px;font-size:13px;color:#475569">{{ $item['desc'] ?? '-' }}</p>
                @if(!empty($item['url']))
                    <a href="{{ $item['url'] }}" class="btn p" style="padding:6px 14px;font-size:12px;display:inline-block">
                        Buka Tugas &rarr;
                    </a>
                @endif
            </div>
        @empty
            <div class="empty">
                <div style="font-size:36px;margin-bottom:8px">🔔</div>
                <b style="font-size:15px;color:var(--ink)">Tidak Ada Notifikasi Baru</b>
                <p style="margin:4px 0 0;font-size:13px">Semua tugas dalam kondisi aman terkendali.</p>
            </div>
        @endforelse
    </div>
</section>
@endsection
