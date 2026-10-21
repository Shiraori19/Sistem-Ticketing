@extends('admin.layout', ['title' => 'Detail Tiket ' . $ticket->kode_tiket])

@section('content')
<div class="space-y-6 max-w-5xl mx-auto">
    <!-- Top Bar Navigation -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ url()->previous() }}" class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 rounded-xl text-xs font-bold border border-slate-200 shadow-xs transition flex items-center gap-1.5">
                <span>&larr;</span> Kembali
            </a>
            <div>
                <h1 class="text-xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                    <span class="font-mono text-sky-700">{{ $ticket->kode_tiket }}</span>
                </h1>
                <span class="text-xs text-slate-500">Diajukan pada {{ $ticket->created_at->translatedFormat('d F Y, H:i') }} WIB</span>
            </div>
        </div>

        <div class="flex items-center flex-wrap gap-2">
            <!-- TLP Badge -->
            @if($ticket->tlp_level === 'RED')
                <span class="px-2.5 py-1 rounded-full text-xs font-mono font-black bg-rose-600 text-white shadow-xs">TLP: RED</span>
            @elseif($ticket->tlp_level === 'AMBER')
                <span class="px-2.5 py-1 rounded-full text-xs font-mono font-black bg-amber-500 text-slate-950 shadow-xs">TLP: AMBER</span>
            @elseif($ticket->tlp_level === 'GREEN')
                <span class="px-2.5 py-1 rounded-full text-xs font-mono font-black bg-emerald-600 text-white shadow-xs">TLP: GREEN</span>
            @elseif($ticket->tlp_level === 'CLEAR')
                <span class="px-2.5 py-1 rounded-full text-xs font-mono font-black bg-slate-500 text-white shadow-xs">TLP: CLEAR</span>
            @endif

            <!-- SLA Indicator Badge -->
            <x-sla-indicator :ticket="$ticket" />

            <!-- Parent-Child Badge -->
            @if($ticket->is_parent)
                <span class="px-2.5 py-1 rounded-full text-xs font-black bg-amber-400 text-slate-950 border border-amber-500 shadow-xs flex items-center gap-1">
                    👑 INDUK TIKET ({{ $ticket->children->count() }} Anak)
                </span>
            @elseif($ticket->parent_id)
                <a href="{{ route('admin.tiket.show', $ticket->parent_id) }}" class="px-2.5 py-1 rounded-full text-xs font-bold bg-indigo-100 text-indigo-900 border border-indigo-300 shadow-xs hover:bg-indigo-200 transition flex items-center gap-1">
                    🔗 Anak: {{ $ticket->parent?->kode_tiket }}
                </a>
            @endif

            <!-- Status Badge Konsisten -->
            <x-status-badge :status="$ticket->status" />
        </div>
    </div>

    <!-- Main Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Kolom Kiri: Detail Pengaduan & Resolusi (2 span) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Card Hubungan Safe Parent-Child Linking (Jika Ada) -->
            @if($ticket->is_parent)
                <div class="bg-gradient-to-br from-amber-500/10 via-white to-amber-500/5 rounded-2xl shadow-xs border border-amber-300 p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-amber-200 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">👑</span>
                            <div>
                                <h3 class="text-sm font-black text-amber-950">
                                    INDUK TIKET (Master Incident)
                                </h3>
                                <p class="text-[11px] text-amber-800">
                                    Pusat koordinasi insiden massal. Terdapat {{ $ticket->children->count() }} tiket anak lintas OPD yang terhubung.
                                </p>
                            </div>
                        </div>
                        <span class="px-2.5 py-1 rounded-full bg-amber-400 text-slate-950 font-black text-xs shadow-xs">
                            {{ $ticket->children->count() }} Tiket Anak
                        </span>
                    </div>

                    @if($ticket->children->isEmpty())
                        <p class="text-xs text-amber-800 italic">Belum ada tiket anak yang ditautkan ke Induk ini.</p>
                    @else
                        <div class="space-y-2">
                            <span class="text-[11px] font-bold text-amber-900 block">Daftar Tiket Terkelompok (Lintas OPD):</span>
                            <div class="grid grid-cols-1 gap-2">
                                @foreach($ticket->children as $child)
                                    <div class="p-3 bg-white rounded-xl border border-amber-200 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                        <div class="space-y-0.5">
                                            <div class="flex items-center gap-2">
                                                <a href="{{ route('admin.tiket.show', $child->id) }}" class="font-mono font-bold text-xs text-sky-700 hover:underline">
                                                    {{ $child->kode_tiket }}
                                                </a>
                                                <span class="text-xs font-bold text-slate-900">{{ $child->skpd }}</span>
                                            </div>
                                            <div class="text-[11px] text-slate-600 truncate max-w-md">
                                                {{ $child->judul }} &bull; Pelapor: {{ $child->nama_pelapor }}
                                            </div>
                                        </div>
                                        <div class="shrink-0 flex items-center gap-2">
                                            <x-status-badge :status="$child->status" />
                                            <a href="{{ route('admin.tiket.show', $child->id) }}" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-[11px] font-bold transition">
                                                Lihat &rarr;
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @elseif($ticket->parent_id && $ticket->parent)
                <div class="bg-indigo-50/80 rounded-2xl shadow-xs border border-indigo-200 p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <span class="text-2xl mt-0.5">🔗</span>
                        <div>
                            <div class="text-[11px] font-bold uppercase tracking-wider text-indigo-700">Terhubung ke Induk Tiket</div>
                            <h4 class="text-sm font-bold text-indigo-950 mt-0.5">
                                #{{ $ticket->parent->kode_tiket }} - {{ $ticket->parent->judul }}
                            </h4>
                            <p class="text-xs text-indigo-800/80 mt-1">
                                Tiket ini dikelompokkan ke dalam penanganan insiden massal. Ketika Induk Tiket diselesaikan teknisi, tiket ini akan otomatis terselesaikan.
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('admin.tiket.show', $ticket->parent->id) }}" class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-xs transition flex items-center gap-1.5 shrink-0 self-start sm:self-auto">
                        <span>Buka Induk Tiket</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            @endif

            <!-- Card Pokok Masalah -->
            <div class="bg-white rounded-2xl shadow-xs border border-slate-200 p-6 space-y-4">
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Judul Permasalahan:</span>
                    <h2 class="text-lg font-bold text-slate-900 leading-snug">{{ $ticket->judul }}</h2>
                </div>

                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1.5">Deskripsi Lengkap Kendala:</span>
                    <div class="text-xs sm:text-sm text-slate-700 bg-slate-50 p-4 rounded-xl border border-slate-200/80 whitespace-pre-line leading-relaxed">
                        {{ $ticket->deskripsi }}
                    </div>
                </div>

                @if($ticket->lampiran_pelapor)
                    @php
                        $lampiranList = is_array($ticket->lampiran_pelapor) ? $ticket->lampiran_pelapor : (is_string($ticket->lampiran_pelapor) && str_starts_with($ticket->lampiran_pelapor, '[') ? json_decode($ticket->lampiran_pelapor, true) : [$ticket->lampiran_pelapor]);
                        $lampiranList = array_filter((array) $lampiranList);
                    @endphp
                    @if(count($lampiranList) > 0)
                        <div class="pt-3 border-t border-slate-100 space-y-2">
                            <span class="text-xs font-bold text-slate-600 block">📎 Lampiran Dokumen Pelapor ({{ count($lampiranList) }} Berkas):</span>
                            <div class="flex flex-wrap gap-2">
                                @foreach($lampiranList as $idx => $lamp)
                                    @php
                                        $ext = strtolower(pathinfo($lamp, PATHINFO_EXTENSION));
                                        $isPdf = $ext === 'pdf';
                                        $filename = basename($lamp);
                                        $previewUrl = route('admin.tiket.download', ['kode' => $ticket->kode_tiket, 'type' => 'lampiran', 'file' => $lamp]);
                                    @endphp
                                    <button type="button" 
                                        onclick="openInAppPreview(this.dataset.previewUrl, this.dataset.previewTitle)"
                                        data-preview-url="{{ $previewUrl }}"
                                        data-preview-title="Bukti Lampiran #{{ $idx + 1 }} ({{ $filename }})"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-blue-50 border border-blue-200 text-blue-900 text-xs font-semibold hover:bg-blue-100 transition shadow-2xs cursor-pointer">
                                        <span>{{ $isPdf ? '📄' : '🖼️' }}</span>
                                        <span>Berkas {{ $idx + 1 }}: {{ \Illuminate\Support\Str::limit($filename, 20) }} ({{ strtoupper($ext) }})</span>
                                        <span class="text-blue-600 font-bold ml-1">👁️ Preview</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endif
            </div>

            <!-- Card Resolusi & Bukti Pengerjaan (Jika Ada) -->
            @if($ticket->catatan_resolusi || $ticket->bukti_resolusi)
                <div class="bg-white rounded-2xl shadow-xs border border-emerald-200 p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-emerald-100 pb-3">
                        <h3 class="text-sm font-bold text-emerald-900 flex items-center gap-1.5">
                            <span>🔧</span> Hasil & Catatan Resolusi Lapangan
                        </h3>
                        @if($ticket->resolved_at)
                            <span class="text-xs text-emerald-700 font-medium">Diselesaikan {{ $ticket->resolved_at->diffForHumans() }}</span>
                        @endif
                    </div>

                    @if($ticket->catatan_resolusi)
                        <div class="text-xs sm:text-sm text-slate-800 bg-emerald-50/50 p-4 rounded-xl border border-emerald-100/80 leading-relaxed whitespace-pre-line">
                            {{ $ticket->catatan_resolusi }}
                        </div>
                    @endif

                    <!-- Lampiran Bukti Resolusi -->
                    @if($ticket->bukti_resolusi)
                        <div class="pt-2 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                            <span class="text-slate-500 font-medium">Berkas Bukti Pekerjaan:</span>
                            @if($ticket->is_confidential_evidence && !$canViewConfidential)
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="px-2.5 py-1 bg-amber-50 text-amber-800 border border-amber-300 rounded-lg font-bold flex items-center gap-1">
                                        🔒 Bukti Rahasia Terlindungi (Khusus CSIRT / Persandian)
                                    </span>
                                    @if(auth()->user()->isAdminPusat())
                                        @if(!empty($hasPendingAccessRequest))
                                            <span class="px-2.5 py-1 bg-amber-100 text-amber-900 border border-amber-300 rounded-lg font-bold text-xs flex items-center gap-1">
                                                ⏳ Permintaan Akses Anda Sedang Ditinjau CSIRT
                                            </span>
                                        @else
                                            @php
                                                $firstFile = $ticket->files->where('type', 'bukti_resolusi')->first() ?? $ticket->files->first();
                                            @endphp
                                            @if($firstFile)
                                                <button type="button" onclick="openRequestAccessModal({{ $firstFile->id }})"
                                                    class="px-2.5 py-1 bg-sky-600 hover:bg-sky-700 text-white font-bold rounded-lg text-xs transition shadow-2xs cursor-pointer">
                                                    🔐 Ajukan Akses Dinas (Justifikasi Kebutuhan)
                                                </button>
                                            @endif
                                        @endif
                                    @endif
                                </div>
                            @else
                                <div class="flex items-center gap-2">
                                    @if($ticket->is_confidential_evidence)
                                        <span class="px-2 py-0.5 rounded bg-rose-100 text-rose-800 font-mono text-[10px] font-bold uppercase">🔒 Rahasia</span>
                                    @endif
                                    @php
                                        $buktiUrl = route('admin.tiket.download', ['kode' => $ticket->kode_tiket, 'type' => 'bukti']);
                                    @endphp
                                    <button type="button" 
                                        onclick="openInAppPreview(this.dataset.previewUrl, this.dataset.previewTitle)"
                                        data-preview-url="{{ $buktiUrl }}"
                                        data-preview-title="Bukti Resolusi ({{ $ticket->kode_tiket }})"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-100 hover:bg-emerald-200 text-emerald-900 font-bold transition shadow-2xs cursor-pointer">
                                        <span>👁️ Preview Bukti Resolusi</span>
                                    </button>
                                    <a href="{{ $buktiUrl }}?download=1" target="_blank" class="text-emerald-700 font-bold hover:underline inline-flex items-center gap-1 ml-1" title="Unduh file fisik">
                                        <span>⬇️ Unduh Asli</span>
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @endif

            <!-- Card Evaluasi & Rating Kepuasan Pelapor (Jika Closed / Ada Rating) -->
            @if($ticket->rating || $ticket->status === 'closed')
                @php
                    $rVal = (int) ($ticket->rating ?? 5);
                    $starIcons = str_repeat('★', $rVal) . str_repeat('☆', 5 - $rVal);
                    $ratingLabels = [
                        1 => 'Sangat Kecewa',
                        2 => 'Kurang Puas',
                        3 => 'Cukup / Standar',
                        4 => 'Puas',
                        5 => 'Sangat Puas & Responsif',
                    ];
                @endphp
                <div class="bg-gradient-to-br from-amber-500/10 via-white to-amber-500/5 rounded-2xl shadow-xs border border-amber-300 p-6 space-y-3">
                    <div class="flex items-center justify-between border-b border-amber-200 pb-2.5">
                        <h3 class="text-sm font-bold text-amber-950 flex items-center gap-1.5">
                            <span>⭐</span> Penilaian & Evaluasi Pelapor (ASN)
                        </h3>
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-100 border border-amber-300 text-amber-900 font-bold text-xs shadow-2xs">
                            <span class="text-amber-500 tracking-wider text-sm">{{ $starIcons }}</span>
                            <span>{{ $rVal }}/5 &bull; {{ $ratingLabels[$rVal] ?? 'Puas' }}</span>
                        </div>
                    </div>
                    @if($ticket->feedback)
                        <div class="text-xs sm:text-sm text-slate-700 bg-white p-3.5 rounded-xl border border-amber-200/80 leading-relaxed italic">
                            "{{ $ticket->feedback }}"
                        </div>
                    @else
                        <p class="text-xs text-slate-500 italic">Pelapor telah mengonfirmasi penyelesaian tanpa catatan ulasan tambahan.</p>
                    @endif
                </div>
            @endif

            <!-- Card Catatan Force Close (Jika Ada) -->
            @if($ticket->force_close_notes)
                <div class="bg-rose-50 rounded-2xl border border-rose-200 p-6 space-y-2">
                    <h3 class="text-sm font-bold text-rose-900 flex items-center gap-1.5">
                        <span>🔒</span> Catatan Resmi Tutup Paksa (Force Closed)
                    </h3>
                    <p class="text-xs sm:text-sm text-rose-800 bg-white/80 p-3.5 rounded-xl border border-rose-200/60 leading-relaxed whitespace-pre-line">
                        {{ $ticket->force_close_notes }}
                    </p>
                </div>
            @endif

            <!-- Riwayat Lengkap Jejak Audit Tiket -->
            <div class="bg-white rounded-2xl shadow-xs border border-slate-200 p-6 space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Jejak Status & Riwayat Audit (status_history)</h3>
                <div class="border-l-2 border-slate-200 ml-4 space-y-5" style="border-left: 2px solid #e2e8f0; margin-left: 1rem;">
                    @php
                        $stageLabelsShow = [
                            'pending_verification' => 'Pending Verification (Baru)',
                            'verified'             => 'Verified (Disposisi ke Bidang)',
                            'in_progress'          => 'In Progress',
                            'resolved'             => 'Resolved',
                            'closed'               => 'Selesai',
                            'reopened'             => 'Reopened',
                            'eskalasi_pusat'       => 'Eskalasi Pusat',
                            'force_closed'         => 'Force Closed',
                            'cancelled'            => 'Cancelled',
                        ];
                    @endphp
                    @forelse($ticket->status_history ?? [] as $hist)
                        <div class="relative pl-7" style="position: relative; padding-left: 1.75rem;">
                            <div class="absolute -left-[7px] top-1.5 w-3.5 h-3.5 rounded-full bg-sky-600 ring-4 ring-white" style="position: absolute; left: -7px; top: 6px; width: 14px; height: 14px; border-radius: 9999px;"></div>
                            <div class="text-xs font-bold text-slate-900">{{ $stageLabelsShow[$hist['status'] ?? ''] ?? ucfirst(str_replace('_', ' ', $hist['status'] ?? '-')) }}</div>
                            <div class="text-[11px] text-slate-500">
                                {{ \Carbon\Carbon::parse($hist['timestamp'] ?? now())->translatedFormat('d M Y, H:i') }} WIB &bull; Aktor: <strong>{{ $hist['actor'] ?? '-' }}</strong>
                            </div>
                            @if(!empty($hist['note']))
                                <div class="text-xs text-slate-700 mt-1.5 bg-slate-50 p-3 rounded-xl border border-slate-200 leading-relaxed">
                                    {{ $hist['note'] }}
                                </div>
                            @endif
                            @if(!empty($hist['evidence_path']))
                                <div class="mt-1 text-xs">
                                    <a href="{{ route('admin.tiket.download', ['kode' => $ticket->kode_tiket, 'type' => 'reopen']) }}" target="_blank" class="text-sky-600 font-bold hover:underline">
                                        📎 Berkas Lampiran Terkait &rarr;
                                    </a>
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 pl-4">Belum ada riwayat tercatat.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Meta Pelapor, SLA, & Petugas (1 span) -->
        <div class="space-y-6">
            <!-- Info Pelapor -->
            <div class="bg-white rounded-2xl shadow-xs border border-slate-200 p-5 space-y-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 border-b border-slate-100 pb-2">Identitas Pelapor</h3>
                <div class="space-y-2 text-xs">
                    <div>
                        <span class="text-slate-400 block text-[11px]">Nama Lengkap:</span>
                        <span class="font-bold text-slate-900">{{ $ticket->nama_pelapor }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px]">NIP:</span>
                        <span class="font-mono text-slate-700">{{ $ticket->nip_pelapor }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px]">SKPD / Instansi:</span>
                        <span class="font-semibold text-slate-900">{{ $ticket->skpd }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px]">Nomor Kontak / HP:</span>
                        <a href="tel:{{ $ticket->no_whatsapp }}" class="font-mono text-blue-600 font-bold hover:underline flex items-center gap-1">
                            <span>📞 {{ $ticket->no_whatsapp }}</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Monitoring SLA -->
            <div class="bg-white rounded-2xl shadow-xs border border-slate-200 p-5 space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Monitoring SLA Layanan</h3>
                    <x-sla-indicator :ticket="$ticket" />
                </div>
                <div class="space-y-2.5 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Target Respon Awal:</span>
                        <div class="flex items-center gap-1.5">
                            <span class="font-bold text-slate-800">3x24 Jam ({{ round($ticket->sla_respon_jam ?: 72) }} Jam)</span>
                            @if($ticket->sla_dimulai_at)
                                @if($ticket->sla_respon_breach)
                                    <span class="px-1.5 py-0.5 rounded bg-rose-100 text-rose-800 font-bold text-[10px] border border-rose-200">
                                        🔴 Lewat Batas
                                    </span>
                                @else
                                    <span class="px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold text-[10px] border border-emerald-200">
                                        🟢 Tepat Waktu
                                    </span>
                                @endif
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Target Penyelesaian (SLA Closed):</span>
                        <span class="font-bold text-slate-800">14 Hari ({{ (int)($ticket->sla_resolusi_jam ?: 336) }} Jam)</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Mulai Dihitung:</span>
                        <span class="font-semibold text-slate-800">
                            {{ $ticket->sla_dimulai_at ? \Carbon\Carbon::parse($ticket->sla_dimulai_at)->translatedFormat('d M Y, H:i') . ' WIB' : 'Menunggu Verifikasi' }}
                        </span>
                    </div>
                    @if($ticket->sla_dimulai_at)
                        @php
                            $slaStart = \Carbon\Carbon::parse($ticket->sla_dimulai_at);
                            $totalMins = max(1, ((float)($ticket->sla_resolusi_jam ?: 336)) * 60);
                            $deadline = $ticket->sla_deadline_at ? \Carbon\Carbon::parse($ticket->sla_deadline_at) : $slaStart->copy()->addMinutes($totalMins);
                            $minsLeft = now()->diffInMinutes($deadline, false);
                        @endphp
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Batas Deadline:</span>
                            <span class="font-bold text-slate-900">{{ $deadline->translatedFormat('d M Y, H:i') }} WIB</span>
                        </div>
                        <div class="pt-2 border-t border-slate-100">
                            @if(in_array($ticket->status, ['resolved', 'closed', 'force_closed']))
                                <div class="p-2.5 bg-emerald-50 rounded-xl border border-emerald-200 text-emerald-800 flex items-center gap-2">
                                    <span>🎯</span>
                                    <span>Tiket telah <strong>selesai ditangani</strong> dalam koridor waktu SLA.</span>
                                </div>
                            @elseif($minsLeft > 0)
                                <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between">
                                    <span class="text-slate-600">Sisa Waktu:</span>
                                    <span class="{{ $minsLeft < 60 ? 'text-amber-700 font-bold' : 'text-emerald-700 font-bold' }}">
                                        {{ $minsLeft >= 60 ? floor($minsLeft / 60) . ' jam ' . ($minsLeft % 60) . ' menit' : $minsLeft . ' menit' }}
                                    </span>
                                </div>
                            @else
                                <div class="p-2.5 bg-rose-50 rounded-xl border border-rose-200 text-rose-800 flex items-center gap-2 font-semibold text-[11px]">
                                    <span>⚠️</span>
                                    <span>Batas SLA <strong>terlampaui (Breach)</strong> sejak {{ abs($minsLeft) >= 60 ? floor(abs($minsLeft) / 60) . ' jam ' . (abs($minsLeft) % 60) . ' menit' : abs($minsLeft) . ' menit' }} lalu.</span>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Disposisi & Petugas -->
            <div class="bg-white rounded-2xl shadow-xs border border-slate-200 p-5 space-y-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 border-b border-slate-100 pb-2">Penanganan Bidang</h3>
                <div class="space-y-2.5 text-xs">
                    <div>
                        <span class="text-slate-400 block text-[11px]">Bidang Penanggung Jawab:</span>
                        <span class="font-bold text-slate-800">{{ $ticket->bidang?->nama_bidang ?? 'Bypass CSIRT DKIS' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px] mb-1">Tim Teknisi Lapangan:</span>
                        @if($ticket->assignments && $ticket->assignments->isNotEmpty())
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($ticket->assignments as $assign)
                                    @if($assign->role_in_task === 'lead_pic')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-amber-50 border border-amber-300 text-amber-950 font-bold text-xs shadow-2xs">
                                            <span>👑</span>
                                            <span>{{ $assign->technician_name }}</span>
                                            <span class="text-[10px] font-semibold text-amber-700 bg-amber-200/60 px-1 py-0.2 rounded ml-0.5">Lead</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 border border-slate-200 text-slate-800 font-semibold text-xs shadow-2xs">
                                            <span>👤</span>
                                            <span>{{ $assign->technician_name }}</span>
                                        </span>
                                    @endif
                                @endforeach
                            </div>
                        @elseif($ticket->teknisi_lapangan)
                            <span class="font-bold text-slate-800">{{ $ticket->teknisi_lapangan }}</span>
                        @else
                            <span class="text-slate-400 italic">Belum Ditugaskan</span>
                        @endif
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px]">Diverifikasi Oleh:</span>
                        <span class="text-slate-700">{{ $ticket->verifier?->nama ?? '-' }}</span>
                    </div>

                    @if($ticket->involvements && $ticket->involvements->isNotEmpty())
                        <div class="pt-2 border-t border-slate-100">
                            <span class="text-slate-400 block text-[11px] mb-1">Koordinasi Antar-Bidang (TLP RED):</span>
                            <div class="space-y-1">
                                @foreach($ticket->involvements as $inv)
                                    <div class="flex items-center justify-between text-[11px] p-1.5 rounded-lg bg-slate-50 border border-slate-200">
                                        <span class="font-bold text-slate-800">{{ $inv->bidang?->nama_bidang ?? 'Bidang' }}</span>
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold {{ $inv->peran === 'pelaksana' ? 'bg-rose-100 text-rose-800' : 'bg-blue-100 text-blue-800' }}">
                                            {{ ucfirst($inv->peran) }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Minta Akses Dokumen Rahasia (BUTIR 5 & KEPUTUSAN 3) -->
<dialog id="modal-request-access" class="rounded-2xl border border-slate-200 p-0 shadow-2xl backdrop:bg-slate-950/70 w-full max-w-md">
    <div class="bg-slate-900 text-white p-4 flex items-center justify-between border-b border-slate-800">
        <h3 class="font-bold text-sm flex items-center gap-1.5">
            <span>🔐</span> Pengajuan Akses Berkas Rahasia
        </h3>
        <button type="button" onclick="document.getElementById('modal-request-access').close()" class="text-slate-400 hover:text-white text-sm">✕</button>
    </div>
    <form id="form-request-access" method="POST" action="" class="p-5 space-y-4 text-xs">
        @csrf
        <p class="text-slate-600 leading-relaxed">
            Sesuai kebijakan pengamanan TLP RED / Vault DKIS, berkas bukti teknis ini dienkripsi dan memerlukan justifikasi kebutuhan kedinasan sebelum dapat ditinjau oleh Admin Pusat.
        </p>
        <div>
            <label class="font-bold text-slate-800 block mb-1">Alasan / Justifikasi Kebutuhan Dinas (Min. 10 Karakter):</label>
            <textarea name="reason" rows="3" required minlength="10" placeholder="Contoh: Diperlukan untuk koordinasi tindak lanjut insiden dengan Tim CSIRT..." class="w-full rounded-xl border border-slate-300 p-2.5 text-xs focus:ring-2 focus:ring-sky-500"></textarea>
        </div>
        <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
            <button type="button" onclick="document.getElementById('modal-request-access').close()" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold cursor-pointer">Batal</button>
            <button type="submit" class="px-4 py-1.5 rounded-lg bg-sky-600 hover:bg-sky-700 text-white font-bold cursor-pointer">Kirim Pengajuan</button>
        </div>
    </form>
</dialog>

<script>
    function openRequestAccessModal(fileId) {
        const dialog = document.getElementById('modal-request-access');
        const form = document.getElementById('form-request-access');
        form.action = `/admin/tiket/{{ $ticket->kode_tiket }}/request-access/${fileId}`;
        dialog.showModal();
    }
</script>
@endsection
