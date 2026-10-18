@extends('teknisi.layout')

@section('content')
@php
    $t = $ticket;
    $a = $assignment;
    $tlp = strtoupper($t->tlp_level ?? 'GREEN');
    $tlpColors = [
        'RED' => '#E11D48',
        'AMBER' => '#D97706',
        'GREEN' => '#059669',
        'CLEAR' => '#64748B',
    ];
    $tlpColor = $tlpColors[$tlp] ?? '#059669';

    // Kalkulasi SLA
    $isTerminalTicket = in_array($t->status, ['closed', 'force_closed', 'cancelled']);
    $now = \Carbon\Carbon::now();
    $deadline = $t->sla_deadline_at ? \Carbon\Carbon::parse($t->sla_deadline_at) : ($t->sla_dimulai_at ? \Carbon\Carbon::parse($t->sla_dimulai_at)->addHours((float)($t->sla_resolusi_jam ?: 4)) : $now->copy()->addHours(4));
    $minsLeft = (int) $now->diffInMinutes($deadline, false);
    $totalMins = max(1, (int) round(((float)($t->sla_resolusi_jam ?: 4)) * 60));
    $usedPercent = max(0, min(100, (int) round((1 - ($minsLeft / $totalMins)) * 100)));
    
    if ($t->status === 'force_closed') {
        $slaColor = '#991B1B';
        $slaText = '⚖️ Tiket Ditutup Paksa oleh Admin Pusat';
    } elseif ($t->status === 'cancelled') {
        $slaColor = '#9A3412';
        $slaText = '🚫 Tiket Dibatalkan / Ditolak';
    } elseif ($t->status === 'closed') {
        $slaColor = '#059669';
        $slaText = '✅ Tiket Selesai & Ditutup';
    } else {
        $slaColor = $minsLeft < 0 ? '#BE123C' : ($usedPercent >= 80 ? '#E11D48' : ($usedPercent >= 60 ? '#D97706' : '#1F6E60'));
        $slaText = $minsLeft < 0 
            ? '⚠️ Melewati SLA: ' . abs(floor($minsLeft / 60)) . 'j ' . abs($minsLeft % 60) . 'm' 
            : '⏱️ Sisa Waktu SLA: ' . floor($minsLeft / 60) . 'j ' . ($minsLeft % 60) . 'm';
    }

    $statusLabels = [
        'assigned' => 'Antrean Baru (Menunggu Konfirmasi)',
        'working' => 'Sedang Dikerjakan (Aktif)',
        'submitted' => 'Menunggu Review Admin Bidang',
        'approved' => 'Selesai & Disetujui',
        'declined' => 'Penugasan Ditolak',
        'released' => 'Dilepas Otomatis (Timeout)',
        'returned' => 'Dikembalikan (Perlu Revisi)',
    ];
@endphp

<style>
    .tugas-detail-container {
        max-width: 1140px;
        margin: 0 auto;
        padding-bottom: 40px;
        width: 100%;
    }
    .tugas-detail-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.22fr) minmax(0, 1fr);
        gap: 20px;
        align-items: start;
    }
    @media (max-width: 880px) {
        .tugas-detail-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="tugas-detail-container">
    <!-- Breadcrumb & Back Navigation -->
    <div style="margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
        <a href="{{ route('teknisi.tugas.index') }}" style="display: inline-flex; align-items: center; gap: 8px; font-weight: 700; font-size: 13px; color: var(--navy); text-decoration: none; background: #fff; padding: 8px 14px; border-radius: 10px; border: 1px solid var(--line); box-shadow: var(--shadow-sm);">
            <span>←</span> Kembali ke Tugas Saya
        </a>
        <div style="display: flex; align-items: center; gap: 8px; font-size: 12px; color: var(--mut);">
            <span>Kode Tiket:</span>
            <span class="code" style="font-size: 12px; padding: 3px 8px;">{{ $t->kode_tiket }}</span>
            <span style="padding: 2px 8px; border-radius: 99px; font-weight: 700; font-size: 11px; color: #fff; background: {{ $tlpColor }};">
                TLP: {{ $tlp }}
            </span>
        </div>
    </div>

    @if($t->status === 'force_closed')
        <div style="background: #FFF1F2; border: 1px solid #FECACA; border-radius: 12px; padding: 16px 20px; margin-bottom: 18px; display: flex; align-items: flex-start; gap: 14px; box-shadow: 0 2px 6px rgba(225, 29, 72, 0.08);">
            <span style="font-size: 24px; line-height: 1;">⚖️</span>
            <div>
                <b style="color: #991B1B; font-size: 15px; display: block;">Tiket Ditutup Paksa (Force Closed) oleh Admin Pusat</b>
                <p style="margin: 6px 0 0; color: #7F1D1D; font-size: 13px; line-height: 1.5;">
                    {{ $t->force_close_notes ?: 'Berdasarkan audit teknis/keputusan pimpinan pusat, kendala ini telah ditutup paksa. Seluruh tindakan dan penugasan teknisi dinonaktifkan.' }}
                </p>
            </div>
        </div>
    @elseif($t->status === 'cancelled')
        <div style="background: #FFF7ED; border: 1px solid #FED7AA; border-radius: 12px; padding: 16px 20px; margin-bottom: 18px; display: flex; align-items: flex-start; gap: 14px;">
            <span style="font-size: 24px; line-height: 1;">🚫</span>
            <div>
                <b style="color: #9A3412; font-size: 15px; display: block;">Tiket Dibatalkan / Ditolak</b>
                <p style="margin: 6px 0 0; color: #7C2D12; font-size: 13px; line-height: 1.5;">
                    Tiket ini telah dibatalkan atau ditolak. Tidak ada tindakan teknisi lanjutan yang diperlukan.
                </p>
            </div>
        </div>
    @elseif($t->status === 'closed')
        <div style="background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 12px; padding: 16px 20px; margin-bottom: 18px; display: flex; align-items: flex-start; gap: 14px;">
            <span style="font-size: 24px; line-height: 1;">✅</span>
            <div>
                <b style="color: #166534; font-size: 15px; display: block;">Tiket Telah Selesai & Ditutup</b>
                <p style="margin: 6px 0 0; color: #14532D; font-size: 13px; line-height: 1.5;">
                    Pelapor telah mengonfirmasi penyelesaian tiket ini.
                </p>
            </div>
        </div>
    @endif

    <!-- Main Grid: Left Details & Right Actions/SLA -->
    <div class="tugas-detail-grid">
        <!-- Left Column: Problem, Contact, Evidence, Team, Timeline -->
        <div style="display: flex; flex-direction: column; gap: 18px; min-width: 0;">
            <!-- Header Card -->
            <div class="card" style="padding: 20px;">
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 10px;">
                    <span class="code" style="font-size: 14px; font-weight: 800;">{{ $t->kode_tiket }}</span>
                    <span style="font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 99px; background: #EFF6FF; color: #1D4ED8; border: 1px solid #BFDBFE;">
                        Peran: {{ strtoupper($a->role) }}
                    </span>
                    @if($t->status === 'force_closed')
                        <span style="font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 99px; background: #FEE2E2; color: #991B1B; border: 1px solid #FECACA;">
                            Tiket Ditutup Paksa
                        </span>
                    @elseif($t->status === 'cancelled')
                        <span style="font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 99px; background: #FFEDD5; color: #9A3412; border: 1px solid #FED7AA;">
                            Tiket Dibatalkan
                        </span>
                    @else
                        <span style="font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 99px; background: #F8FAFC; color: var(--ink); border: 1px solid var(--line);">
                            {{ $statusLabels[$a->status] ?? ucfirst($a->status) }}
                        </span>
                    @endif
                </div>
                <h1 style="margin: 0 0 10px; font-size: 20px; font-weight: 800; color: var(--ink); line-height: 1.35;">
                    {{ $t->judul }}
                </h1>
                <div style="font-size: 13px; color: var(--mut); display: flex; flex-wrap: wrap; gap: 12px; padding-bottom: 14px; border-bottom: 1px solid var(--line);">
                    <span>🏢 OPD: <b>{{ $t->skpd }}</b></span>
                    <span>🏷️ Kategori: <b>{{ $t->category?->nama_kategori ?: 'Layanan TI' }}</b></span>
                    <span>📅 Masuk: <b>{{ $t->created_at ? $t->created_at->translatedFormat('d M Y, H:i') : '-' }}</b></span>
                </div>

                <!-- Deskripsi Kendala -->
                <div style="margin-top: 14px;">
                    <span style="font-size: 12px; font-weight: 700; color: var(--mut); text-transform: uppercase; letter-spacing: 0.5px;">Rincian Kendala Pelapor:</span>
                    <div style="margin-top: 6px; font-size: 13.5px; line-height: 1.6; color: var(--ink); background: #F8FAFC; padding: 14px; border-radius: 10px; border: 1px solid var(--line); white-space: pre-wrap;">{{ $t->deskripsi }}</div>
                </div>

                <!-- Lampiran Pelapor -->
                <div style="margin-top: 14px;">
                    <span style="font-size: 12px; font-weight: 700; color: var(--mut); text-transform: uppercase; letter-spacing: 0.5px;">Lampiran Berkas Awal:</span>
                    @php
                        $lampiranList = is_array($t->lampiran_pelapor) ? $t->lampiran_pelapor : ($t->lampiran_pelapor ? [$t->lampiran_pelapor] : []);
                    @endphp
                    @if(count($lampiranList) > 0)
                        <div style="margin-top: 6px; display: grid; gap: 6px;">
                            @foreach($lampiranList as $idx => $lamp)
                                <div class="fl" style="padding: 8px 12px; background: #fff; border: 1px solid var(--line); border-radius: 8px;">
                                    <span style="font-size: 12.5px;">📄 {{ basename($lamp) }}</span>
                                    <a href="{{ route('admin.tiket.download', ['kode' => $t->kode_tiket, 'type' => 'lampiran', 'index' => $idx]) }}" target="_blank" style="color: var(--g); font-weight: 700; font-size: 12px; text-decoration: none;">
                                        Buka Berkas ↗
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p style="margin: 4px 0 0; font-size: 12.5px; color: var(--mut);">Tidak ada lampiran dari pelapor.</p>
                    @endif
                </div>
            </div>

            <!-- Kontak Pelapor (Hanya untuk Penugasan Aktif) -->
            <div class="card" style="padding: 20px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                    <h3 style="margin: 0; font-size: 15px; font-weight: 700; color: var(--ink); display: flex; align-items: center; gap: 8px;">
                        <span>👤</span> Data Kontak Pelapor
                    </h3>
                    @if($canViewContact)
                        <span style="font-size: 11px; padding: 2px 8px; border-radius: 99px; background: #ECFDF5; color: #065F46; font-weight: 600;">
                            ✓ Penugasan Aktif
                        </span>
                    @else
                        <span style="font-size: 11px; padding: 2px 8px; border-radius: 99px; background: #F1F5F9; color: var(--mut); font-weight: 600;">
                            🔒 Kontak Diproteksi
                        </span>
                    @endif
                </div>

                @if($canViewContact)
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; font-size: 13px;">
                        <div>
                            <span style="color: var(--mut); display: block; font-size: 11.5px;">Nama Lengkap:</span>
                            <b style="color: var(--ink);">{{ $t->nama_pelapor }}</b>
                        </div>
                        <div>
                            <span style="color: var(--mut); display: block; font-size: 11.5px;">NIP / NIK:</span>
                            <b style="color: var(--ink);">{{ $t->nip_pelapor ?: '-' }}</b>
                        </div>
                        <div>
                            <span style="color: var(--mut); display: block; font-size: 11.5px;">Instansi / SKPD:</span>
                            <b style="color: var(--ink);">{{ $t->skpd }}</b>
                        </div>
                        <div>
                            <span style="color: var(--mut); display: block; font-size: 11.5px;">Nomor Telepon / WhatsApp:</span>
                            <b style="color: var(--ink);">{{ $t->no_whatsapp }}</b>
                        </div>
                    </div>

                    @if(!empty($t->no_whatsapp))
                        @php
                            $cleanPhone = preg_replace('/[^0-9]/', '', (string)$t->no_whatsapp);
                            if (str_starts_with($cleanPhone, '0')) {
                                $cleanPhone = '62' . substr($cleanPhone, 1);
                            }
                            $msgWa = rawurlencode("Halo {$t->nama_pelapor}, saya {$user->nama} dari Tim Penanganan DKIS Kota Cirebon terkait tiket #{$t->kode_tiket} ({$t->judul}).");
                        @endphp
                        <div style="margin-top: 14px;">
                            <a href="https://wa.me/{{ $cleanPhone }}?text={{ $msgWa }}" target="_blank" rel="noopener" class="btn wa" style="display: inline-flex; width: auto; padding: 8px 16px; font-size: 13px;">
                                💬 Hubungi Pelapor via WhatsApp
                            </a>
                        </div>
                    @endif
                @else
                    <div style="background: #F8FAFC; padding: 14px; border-radius: 10px; border: 1px dashed var(--line); font-size: 13px; color: var(--mut); display: flex; align-items: center; gap: 10px;">
                        <span style="font-size: 20px;">🔒</span>
                        <span>Nomor telepon dan kontak pelapor disembunyikan demi privasi karena penugasan Anda pada tiket ini telah selesai atau belum aktif.</span>
                    </div>
                @endif
            </div>

            <!-- Tim Teknisi yang Bertugas -->
            <div class="card" style="padding: 20px;">
                <h3 style="margin: 0 0 12px; font-size: 15px; font-weight: 700; color: var(--ink); display: flex; align-items: center; gap: 8px;">
                    <span>👥</span> Tim Teknisi yang Bertugas
                </h3>
                <div style="display: grid; gap: 8px;">
                    @forelse($t->assignments as $teamMember)
                        <div class="fl" style="padding: 10px 14px; background: #F8FAFC; border: 1px solid var(--line); border-radius: 8px; justify-content: space-between;">
                            <div>
                                <b style="font-size: 13px; color: var(--ink);">{{ $teamMember->user?->nama ?: $teamMember->technician_name }}</b>
                                <span style="font-size: 11px; color: var(--mut); display: block;">Ditugaskan: {{ $teamMember->assigned_at ? $teamMember->assigned_at->translatedFormat('d M, H:i') : '-' }}</span>
                            </div>
                            <div>
                                @if($teamMember->role === 'lead')
                                    <span style="font-size: 11px; font-weight: 800; padding: 3px 10px; border-radius: 99px; background: #ECFDF5; color: #065F46; border: 1px solid #A7F3D0;">
                                        👑 Lead PIC
                                    </span>
                                @else
                                    <span style="font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 99px; background: #EFF6FF; color: #1D4ED8; border: 1px solid #BFDBFE;">
                                        Pendamping
                                    </span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p style="margin: 0; font-size: 13px; color: var(--mut);">Tidak ada anggota tim lainnya.</p>
                    @endforelse
                </div>
            </div>

            <!-- Hambatan Lapangan (Blockers) -->
            <div class="card" style="padding: 20px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                    <h3 style="margin: 0; font-size: 15px; font-weight: 700; color: var(--ink); display: flex; align-items: center; gap: 8px;">
                        <span>⚠️</span> Hambatan Lapangan (Blockers)
                    </h3>
                    @if(in_array($a->status, ['assigned', 'working']) && !$isTerminalTicket)
                        <button type="button" class="btn o" style="font-size: 12px; padding: 5px 12px;" onclick="document.getElementById('modal-blocker').showModal()">
                            + Laporkan Hambatan
                        </button>
                    @endif
                </div>

                @if($a->blockers->isNotEmpty())
                    <div style="display: grid; gap: 10px;">
                        @foreach($a->blockers as $blocker)
                            <div style="padding: 12px 14px; border-radius: 10px; border: 1px solid {{ $blocker->isOpen() ? '#FECACA' : '#E2E8F0' }}; background: {{ $blocker->isOpen() ? '#FFF5F5' : '#F8FAFC' }};">
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                                    <span style="font-weight: 700; font-size: 12px; color: {{ $blocker->isOpen() ? '#991B1B' : '#0F766E' }};">
                                        {{ $blocker->jenis_label }}
                                    </span>
                                    @if($blocker->isOpen())
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <span style="font-size: 10.5px; font-weight: 800; padding: 2px 8px; border-radius: 99px; background: #FEE2E2; color: #991B1B;">
                                                🔴 TERBUKA
                                            </span>
                                            <form action="{{ route('teknisi.tugas.hambatan.resolve', ['id' => $a->id, 'blockerId' => $blocker->id]) }}" method="POST" style="margin: 0;">
                                                @csrf
                                                <button type="submit" style="font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 6px; background: #059669; color: #fff; border: 0; cursor: pointer;">
                                                    Tandai Selesai
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <span style="font-size: 10.5px; font-weight: 700; padding: 2px 8px; border-radius: 99px; background: #ECFDF5; color: #065F46;">
                                            ✓ SELESAI ({{ $blocker->resolved_at?->diffForHumans() }})
                                        </span>
                                    @endif
                                </div>
                                <div style="font-size: 13px; color: var(--ink); margin-top: 4px;">
                                    {{ $blocker->keterangan }}
                                </div>
                                <div style="font-size: 11.5px; color: var(--mut); margin-top: 6px;">
                                    Bukti / Tiket: <b>{{ $blocker->bukti }}</b> • Waktu: {{ $blocker->created_at->translatedFormat('d M Y, H:i') }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p style="margin: 0; font-size: 13px; color: var(--mut);">Belum ada hambatan lapangan yang dilaporkan. Pengerjaan berjalan lancar.</p>
                @endif
            </div>

            <!-- Timeline Aktivitas (Ticket Events) -->
            <div class="card" style="padding: 20px;">
                <h3 style="margin: 0 0 14px; font-size: 15px; font-weight: 700; color: var(--ink); display: flex; align-items: center; gap: 8px;">
                    <span>📜</span> Rekam Jejak Aktivitas (Audit Events)
                </h3>
                @if($t->events->isNotEmpty())
                    <ol class="stp" style="margin: 0; padding-left: 18px;">
                        @foreach($t->events as $event)
                            <li style="margin-bottom: 14px;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <b style="font-size: 13px; color: var(--ink);">
                                        {{ ucfirst(str_replace('_', ' ', $event->type)) }}
                                    </b>
                                    <span style="font-size: 11.5px; color: var(--mut);">
                                        • {{ $event->created_at ? $event->created_at->translatedFormat('d M Y, H:i') : '' }}
                                    </span>
                                </div>
                                <div style="font-size: 12.5px; color: var(--mut); margin-top: 2px;">
                                    Oleh: <b>{{ $event->actor?->nama ?: 'Sistem Otomatis' }}</b>
                                </div>
                                @if(!empty($event->payload['note']) || !empty($event->payload['message']) || !empty($event->payload['reason']))
                                    <div style="font-size: 12.5px; color: var(--ink); margin-top: 4px; background: #F8FAFC; padding: 6px 10px; border-radius: 6px; border-left: 3px solid var(--g);">
                                        {{ $event->payload['note'] ?? $event->payload['message'] ?? $event->payload['reason'] ?? '' }}
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                @else
                    <p style="margin: 0; font-size: 13px; color: var(--mut);">Belum ada rekam jejak event pada tiket ini.</p>
                @endif
            </div>
        </div>

        <!-- Right Column: Status Card, SLA Countdown, & Action Forms -->
        <div style="display: flex; flex-direction: column; gap: 18px; min-width: 0;">
            <!-- SLA Monitor Card -->
            <div class="card" style="padding: 20px; border-top: 4px solid {{ $slaColor }};">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                    <span style="font-size: 12px; font-weight: 800; color: var(--mut); text-transform: uppercase;">Monitoring SLA Resolusi</span>
                    <span style="font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 99px; background: {{ $slaColor }}15; color: {{ $slaColor }};">
                        {{ $isTerminalTicket ? strtoupper(str_replace('_', ' ', $t->status)) : ($minsLeft < 0 ? 'BREACH' : 'ON TRACK') }}
                    </span>
                </div>
                <div style="font-size: 18px; font-weight: 800; color: {{ $slaColor }}; margin-bottom: 6px;">
                    {{ $slaText }}
                </div>
                <div class="bar" style="height: 10px; border-radius: 99px; overflow: hidden; background: #E2E8F0; margin-bottom: 10px;">
                    <i style="width: {{ $usedPercent }}%; background: {{ $slaColor }}; height: 100%; display: block; border-radius: 99px;"></i>
                </div>
                <div style="font-size: 12px; color: var(--mut); display: grid; gap: 4px;">
                    <div style="display: flex; justify-content: space-between;">
                        <span>Target Resolusi:</span>
                        <b>{{ $t->sla_resolusi_jam ?: 4 }} Jam ({{ floor($totalMins/60) }}j {{ $totalMins%60 }}m)</b>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>Batas Waktu Akhir:</span>
                        <b>{{ $deadline->translatedFormat('d M Y, H:i') }}</b>
                    </div>
                    @if($a->respond_deadline && $a->status === 'assigned')
                        <div style="display: flex; justify-content: space-between; color: #BE123C; margin-top: 6px; padding-top: 6px; border-top: 1px dashed #E2E8F0;">
                            <span>Tenggat Konfirmasi Respon:</span>
                            <b>{{ $a->respond_deadline->translatedFormat('H:i') }} ({{ $a->respond_deadline->diffForHumans() }})</b>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Action Panel Based on Status -->
            <div class="card" style="padding: 20px;">
                <h3 style="margin: 0 0 14px; font-size: 15px; font-weight: 700; color: var(--ink);">
                    ⚡ Tindakan Penanganan Tugas
                </h3>

                @if($isTerminalTicket)
                    <div style="padding: 18px 16px; border-radius: 10px; background: #F8FAFC; border: 1px solid var(--line); text-align: center;">
                        <span style="font-size: 32px; display: block; margin-bottom: 8px;">
                            {{ $t->status === 'force_closed' ? '⚖️' : ($t->status === 'cancelled' ? '🚫' : '🔒') }}
                        </span>
                        <b style="font-size: 14px; color: var(--ink); display: block;">
                            {{ $t->status === 'force_closed' ? 'Tiket Ditutup Paksa oleh Admin Pusat' : ($t->status === 'cancelled' ? 'Tiket Telah Dibatalkan' : 'Tiket Sudah Selesai & Ditutup') }}
                        </b>
                        <p style="margin: 6px 0 0; font-size: 12.5px; color: var(--mut); line-height: 1.5;">
                            {{ $t->status === 'force_closed' ? 'Penutupan dilakukan secara permanen oleh Admin Pusat. Seluruh aksi dan formulir penugasan teknisi dinonaktifkan.' : 'Tidak ada tindakan teknisi lanjutan yang diperlukan pada tiket ini.' }}
                        </p>
                    </div>

                @elseif($a->status === 'assigned')
                    <!-- Konfirmasi Penerimaan Tugas -->
                    <div style="display: grid; gap: 10px;">
                        <form action="{{ route('teknisi.tugas.terima', $a->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="row_version" value="{{ $a->row_version }}">
                            <button type="submit" class="btn" style="width: 100%; padding: 12px; font-size: 14px; justify-content: center;">
                                ✓ Terima Penugasan Ini
                            </button>
                        </form>
                        <button type="button" class="btn danger" style="width: 100%; padding: 10px; font-size: 13px; justify-content: center;" onclick="document.getElementById('modal-decline').showModal()">
                            ✕ Tolak Penugasan
                        </button>
                    </div>

                @elseif($a->status === 'working' || $a->status === 'returned')
                    @if($a->status === 'returned')
                        <div style="margin-bottom: 14px; padding: 12px; border-radius: 8px; background: #FFF1F2; border: 1px solid #FECACA; color: #991B1B; font-size: 13px;">
                            <b>⚠️ Catatan Revisi Admin Bidang:</b>
                            <p style="margin: 4px 0 0;">{{ $a->review_notes }}</p>
                        </div>
                    @endif

                    <!-- Tambah Catatan Progres -->
                    <form action="{{ route('teknisi.tugas.catatan', $a->id) }}" method="POST" enctype="multipart/form-data" style="margin-bottom: 20px;">
                        @csrf
                        <input type="hidden" name="row_version" value="{{ $a->row_version }}">
                        <label style="font-size: 12px; font-weight: 700; color: var(--mut); display: block; margin-bottom: 4px;">
                            Catat Progres Tindakan Fisik/Sistem:
                        </label>
                        <textarea name="catatan" rows="3" class="in" placeholder="Tuliskan tindakan yang sedang dilakukan di lokasi..." required style="width: 100%; font-size: 13px; margin-bottom: 8px;"></textarea>
                        <div style="margin-bottom: 8px;">
                            <label style="font-size: 11.5px; color: var(--mut); display: block; margin-bottom: 2px;">Lampirkan Foto/File Progres (Opsional):</label>
                            <input type="file" name="berkas" accept=".jpg,.jpeg,.png,.pdf,.txt,.log" style="font-size: 12px; width: 100%;">
                        </div>
                        <button type="submit" class="btn o" style="width: 100%; font-size: 13px; justify-content: center;">
                            + Simpan Catatan Progres
                        </button>
                    </form>

                    <hr style="border: 0; border-top: 1px solid var(--line); margin: 16px 0;">

                    <!-- Tombol Ajukan Selesai -->
                    <button type="button" class="btn" style="width: 100%; padding: 12px; font-size: 14px; justify-content: center; background: #059669;" onclick="document.getElementById('modal-submit-review').showModal()">
                        🚀 Selesaikan Pekerjaan (Kirim ke Pelapor)
                    </button>

                @elseif($a->status === 'submitted')
                    <div style="padding: 16px; border-radius: 10px; background: #FAF5FF; border: 1px solid #E9D5FF; text-align: center;">
                        <span style="font-size: 28px; display: block; margin-bottom: 8px;">⏳</span>
                        <b style="font-size: 14px; color: #6B21A8; display: block;">Menunggu Konfirmasi Pelapor</b>
                        <p style="margin: 6px 0 0; font-size: 12.5px; color: #7E22CE; line-height: 1.5;">
                            Pekerjaan telah Anda selesaikan pada {{ $a->submitted_at ? $a->submitted_at->translatedFormat('d M, H:i') : '' }}. Tiket berstatus Resolved dan menunggu konfirmasi penyelesaian dari pelapor.
                        </p>
                    </div>

                @elseif($a->status === 'approved')
                    <div style="padding: 16px; border-radius: 10px; background: #ECFDF5; border: 1px solid #A7F3D0; text-align: center;">
                        <span style="font-size: 28px; display: block; margin-bottom: 8px;">✅</span>
                        <b style="font-size: 14px; color: #065F46; display: block;">Pekerjaan Selesai (Resolved)</b>
                        <p style="margin: 6px 0 0; font-size: 12.5px; color: #047857; line-height: 1.5;">
                            Hasil perbaikan lapangan telah selesai dan berstatus Resolved. Menunggu konfirmasi penyelesaian atau penutupan tiket oleh pelapor.
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal 1: Tolak Penugasan -->
<dialog id="modal-decline" style="border: 0; border-radius: 16px; padding: 0; width: 440px; max-width: 90vw; box-shadow: 0 20px 40px rgba(0,0,0,0.25);">
    <div style="padding: 18px 20px; background: var(--navy); color: #fff; display: flex; justify-content: space-between; align-items: center;">
        <b style="font-size: 16px;">Tolak Penugasan Tiket</b>
        <button type="button" onclick="this.closest('dialog').close()" style="background: none; border: 0; color: #fff; font-size: 20px; cursor: pointer;">✕</button>
    </div>
    <form action="{{ route('teknisi.tugas.tolak', $a->id) }}" method="POST" style="padding: 20px;">
        @csrf
        <input type="hidden" name="row_version" value="{{ $a->row_version }}">
        <p style="margin: 0 0 12px; font-size: 13px; color: var(--mut);">
            Harap berikan alasan formal penolakan penugasan ini agar Admin Bidang dapat menugaskan teknisi pengganti.
        </p>
        <label style="font-size: 12px; font-weight: 700; color: var(--ink); display: block; margin-bottom: 4px;">Alasan Penolakan (Min. 10 Karakter):</label>
        <textarea name="alasan" rows="4" class="in" placeholder="Contoh: Sedang menangani gangguan darurat di OPD lain..." required minlength="10" style="width: 100%; font-size: 13px; margin-bottom: 16px;"></textarea>
        <div style="display: flex; gap: 8px; justify-content: flex-end;">
            <button type="button" class="btn o" onclick="this.closest('dialog').close()">Batal</button>
            <button type="submit" class="btn danger">Konfirmasi Tolak</button>
        </div>
    </form>
</dialog>

<!-- Modal 2: Laporkan Hambatan Lapangan -->
<dialog id="modal-blocker" style="border: 0; border-radius: 16px; padding: 0; width: 480px; max-width: 90vw; box-shadow: 0 20px 40px rgba(0,0,0,0.25);">
    <div style="padding: 18px 20px; background: var(--navy); color: #fff; display: flex; justify-content: space-between; align-items: center;">
        <b style="font-size: 16px;">Laporkan Hambatan Lapangan (Blocker)</b>
        <button type="button" onclick="this.closest('dialog').close()" style="background: none; border: 0; color: #fff; font-size: 20px; cursor: pointer;">✕</button>
    </div>
    <form action="{{ route('teknisi.tugas.hambatan', $a->id) }}" method="POST" enctype="multipart/form-data" style="padding: 20px;">
        @csrf
        <input type="hidden" name="row_version" value="{{ $a->row_version }}">
        <div style="margin-bottom: 12px; padding: 10px; border-radius: 8px; background: #FFFBEB; border: 1px solid #FDE68A; font-size: 12px; color: #92400E;">
            ℹ️ <b>Kebijakan SLA:</b> Sesuai aturan kedinasan DKIS, pelaporan hambatan akan memberitahu Admin Bidang & Pusat, namun target jam SLA tetap berjalan.
        </div>
        <div style="margin-bottom: 12px;">
            <label style="font-size: 12px; font-weight: 700; color: var(--ink); display: block; margin-bottom: 4px;">Jenis Hambatan:</label>
            <select name="jenis" class="in" style="width: 100%; font-size: 13px;" required>
                <option value="isp">Pihak Ketiga: Internet Service Provider (ISP)</option>
                <option value="vendor">Pihak Ketiga: Vendor / Kontraktor Sistem</option>
                <option value="opd_lain">Koordinasi Eksternal: OPD Lain</option>
                <option value="suku_cadang">Logistik: Menunggu Pengadaan Suku Cadang</option>
            </select>
        </div>
        <div style="margin-bottom: 12px;">
            <label style="font-size: 12px; font-weight: 700; color: var(--ink); display: block; margin-bottom: 4px;">Nomor Tiket Vendor / Bukti Referensi:</label>
            <input type="text" name="bukti" class="in" placeholder="Contoh: Tiket Icon+ #98421 atau Surat Permohonan" required style="width: 100%; font-size: 13px;">
        </div>
        <div style="margin-bottom: 12px;">
            <label style="font-size: 12px; font-weight: 700; color: var(--ink); display: block; margin-bottom: 4px;">Rincian Keterangan Hambatan (Min. 10 Karakter):</label>
            <textarea name="keterangan" rows="3" class="in" placeholder="Jelaskan kendala lapangan secara detail..." required minlength="10" style="width: 100%; font-size: 13px;"></textarea>
        </div>
        <div style="margin-bottom: 16px;">
            <label style="font-size: 12px; font-weight: 700; color: var(--ink); display: block; margin-bottom: 4px;">Lampirkan Foto Bukti (Opsional):</label>
            <input type="file" name="berkas_bukti" accept=".jpg,.jpeg,.png,.pdf,.txt,.log" style="font-size: 12px; width: 100%;">
        </div>
        <div style="display: flex; gap: 8px; justify-content: flex-end;">
            <button type="button" class="btn o" onclick="this.closest('dialog').close()">Batal</button>
            <button type="submit" class="btn" style="background: #D97706;">Kirim Laporan Hambatan</button>
        </div>
    </form>
</dialog>

<!-- Modal 3: Selesaikan Pekerjaan (Kirim Langsung ke Pelapor) -->
<dialog id="modal-submit-review" style="border: 0; border-radius: 16px; padding: 0; width: 500px; max-width: 90vw; box-shadow: 0 20px 40px rgba(0,0,0,0.25);">
    <div style="padding: 18px 20px; background: #059669; color: #fff; display: flex; justify-content: space-between; align-items: center;">
        <b style="font-size: 16px;">Selesaikan Pekerjaan & Teruskan ke Pelapor</b>
        <button type="button" onclick="this.closest('dialog').close()" style="background: none; border: 0; color: #fff; font-size: 20px; cursor: pointer;">✕</button>
    </div>
    <form action="{{ route('teknisi.tugas.selesai', $a->id) }}" method="POST" enctype="multipart/form-data" style="padding: 20px;">
        @csrf
        <input type="hidden" name="row_version" value="{{ $a->row_version }}">
        <p style="margin: 0 0 12px; font-size: 13px; color: var(--mut);">
            Tuliskan tindakan teknis perbaikan yang telah Anda selesaikan. Tiket akan langsung beralih ke status Selesai (Resolved) dan pelapor dapat langsung mengonfirmasi penyelesaian kendala.
        </p>
        <div style="margin-bottom: 12px;">
            <label style="font-size: 12px; font-weight: 700; color: var(--ink); display: block; margin-bottom: 4px;">Catatan Solusi / Tindakan Perbaikan (Min. 10 Karakter):</label>
            <textarea name="catatan" rows="4" class="in" placeholder="Contoh: Splicing core FO putus di tiang #14 dan pengecekan sinyal optical power meter (-18 dBm)..." required minlength="10" style="width: 100%; font-size: 13px;"></textarea>
        </div>
        <div style="margin-bottom: 16px;">
            <label style="font-size: 12px; font-weight: 700; color: var(--ink); display: block; margin-bottom: 4px;">Foto Bukti Hasil Perbaikan (Opsional, Max 10MB):</label>
            <input type="file" name="bukti_foto" accept=".jpg,.jpeg,.png,.pdf,.txt,.log" style="font-size: 12px; width: 100%;">
        </div>
        <div style="display: flex; gap: 8px; justify-content: flex-end;">
            <button type="button" class="btn o" onclick="this.closest('dialog').close()">Batal</button>
            <button type="submit" class="btn" style="background: #059669;">Kirim & Selesaikan Tiket</button>
        </div>
    </form>
</dialog>
@endsection
