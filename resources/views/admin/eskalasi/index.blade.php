@extends('admin.layout', ['title' => 'Eskalasi Pusat & Force Close — DKIS Kota Cirebon'])

@section('content')
<div class="space-y-6">
    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-rose-50 text-rose-800 border border-rose-200 text-xs font-semibold mb-1">
                <span>⚖️</span> Hak Khusus Admin Pusat
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Manajemen Eskalasi & Tutup Paksa</h1>
            <p class="text-xs text-slate-500 mt-1">
                Daftar tiket yang telah melampaui batas toleransi pembukaan kembali (reopen) atau memerlukan intervensi pimpinan Admin Pusat.
            </p>
        </div>

        <div class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white border border-slate-200 shadow-xs text-xs font-bold text-slate-700">
            <span>Tiket Ter-eskalasi:</span>
            <span class="px-2.5 py-0.5 rounded-full bg-rose-600 text-white font-black">{{ $tickets->total() }} Tiket</span>
        </div>
    </div>

    @if($tickets->isEmpty())
        <div class="bg-white rounded-3xl border border-slate-200 p-12 text-center shadow-xs">
            <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-4 border border-emerald-100 shadow-xs">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
            </div>
            <h3 class="text-lg font-bold text-slate-900">Tidak Ada Tiket dalam Eskalasi Pusat</h3>
            <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
                Seluruh tiket berjalan normal dalam batas penanganan bidang. Belum ada tiket yang melebihi batas reopen atau memerlukan tindakan Force Close.
            </p>
        </div>
    @else
        <div class="space-y-6">
            @foreach($tickets as $t)
                <div class="bg-white rounded-2xl shadow-xs border border-rose-200/80 overflow-hidden">
                    <!-- Header Card -->
                    <div class="p-5 sm:p-6 bg-rose-50/40 border-b border-rose-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5">
                            <span class="font-mono text-sm font-black text-rose-950 bg-white px-3 py-1 rounded-lg border border-rose-200 shadow-xs">
                                {{ $t->kode_tiket }}
                            </span>
                            <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-rose-100 text-rose-800 border border-rose-200">
                                🔄 Reopen: {{ $t->reopen_count }}/{{ $t->max_reopen }} Kali
                            </span>
                            <x-status-badge :status="$t->status" />
                        </div>

                        <div class="text-xs text-slate-600">
                            Pelapor: <strong>{{ $t->nama_pelapor }}</strong> ({{ $t->skpd }}) &bull; Kontak: {{ $t->no_whatsapp }}
                        </div>
                    </div>

                    <div class="p-5 sm:p-6 space-y-5">
                        <!-- Judul & Deskripsi Masalah -->
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Masalah Utama:</span>
                            <h2 class="text-base font-bold text-slate-900">{{ $t->judul }}</h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Kategori: <span class="font-semibold text-slate-700">{{ $t->category?->nama_kategori }}</span> &bull; 
                                Bidang: <span class="font-semibold text-slate-700">{{ $t->bidang?->nama_bidang ?? 'Bypass CSIRT' }}</span>
                            </p>
                            <div class="text-xs text-slate-700 bg-slate-50 p-3.5 rounded-xl border border-slate-200 mt-2 leading-relaxed whitespace-pre-line">
                                {{ $t->deskripsi }}
                            </div>
                        </div>

                        <!-- RIWAYAT REOPEN TAMPIL LENGKAP -->
                        <div class="pt-4 border-t border-slate-200">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                                    <span>📋</span> Riwayat Reopen Lengkap Pelapor
                                </h3>
                                <span class="text-[11px] text-rose-600 font-semibold">Telah mencapai batas maksimal reopen ({{ $t->reopen_count }}x)</span>
                            </div>

                            @php
                                $reopenEvents = collect($t->status_history ?? [])->filter(function($h) {
                                    return ($h['status'] ?? '') === 'reopened' || str_contains(strtolower($h['note'] ?? ''), 'reopen');
                                });
                            @endphp

                            @if($reopenEvents->isNotEmpty())
                                <div class="space-y-3">
                                    @foreach($reopenEvents as $rIdx => $rev)
                                        <div class="p-3.5 rounded-xl bg-amber-50/60 border border-amber-200/80 text-xs space-y-1.5">
                                            <div class="flex items-center justify-between">
                                                <span class="font-bold text-amber-900 flex items-center gap-1">
                                                    <span>🔄</span> Reopen #{{ $loop->iteration }} oleh: {{ $rev['actor'] ?? 'Pelapor ASN' }}
                                                </span>
                                                <span class="text-[11px] text-amber-700 font-mono">
                                                    {{ \Carbon\Carbon::parse($rev['timestamp'] ?? now())->translatedFormat('d M Y, H:i') }} WIB
                                                </span>
                                            </div>
                                            <div class="text-slate-800 bg-white/80 p-2.5 rounded-lg border border-amber-100 whitespace-pre-line">
                                                <strong>Alasan Pelapor:</strong> {{ $rev['note'] ?? 'Tidak mencantumkan rincian alasan.' }}
                                            </div>
                                            @if(!empty($rev['evidence_path']))
                                                <div class="pt-1 text-[11px]">
                                                    <a href="{{ route('admin.tiket.download', ['kode' => $t->kode_tiket, 'type' => 'reopen']) }}" target="_blank" class="text-sky-700 font-bold hover:underline inline-flex items-center gap-1">
                                                        <span>📎 Berkas Bukti Keluhan Pelapor</span>
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                                    </a>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-xs text-slate-400 italic">Riwayat catatan reopen otomatis disimpan pada status history.</p>
                            @endif
                        </div>

                        <!-- Tombol Aksi Buka Modal Force Close -->
                        <div class="pt-4 border-t border-slate-200 flex items-center justify-between">
                            <div class="text-[11px] text-slate-500">
                                Penutupan paksa memerlukan catatan resmi pimpinan dan akan dicatat secara permanen di audit log.
                            </div>
                            <button type="button"
                                data-id="{{ $t->id }}"
                                data-kode="{{ $t->kode_tiket }}"
                                data-judul="{{ $t->judul }}"
                                onclick="openForceCloseModalFromBtn(this)"
                                class="px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl text-xs shadow-md transition flex items-center gap-1.5 cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                <span>🔒 Tutup Paksa (Force Close)</span>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="p-4">
                {{ $tickets->links() }}
            </div>
        </div>
    @endif
</div>

<!-- ================================================================= -->
<!-- MODAL FORCE CLOSE RESMI PIMPINAN -->
<!-- ================================================================= -->
<div id="force-close-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full shadow-2xl border border-rose-200 overflow-hidden transform transition-all my-8 animate-in fade-in zoom-in duration-150">
        <!-- Header Modal -->
        <div class="bg-rose-900 text-white p-5 sm:p-6 flex items-start justify-between border-b border-rose-950">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span id="fc-modal-kode" class="font-mono text-sm font-black bg-rose-950/80 text-rose-200 px-2.5 py-0.5 rounded border border-rose-500/40">
                        TIK-XXXXXX-XXXX
                    </span>
                    <span class="text-xs text-rose-200 font-semibold">&bull; Force Close</span>
                </div>
                <h3 class="text-base font-bold text-white">Eksekusi Penutupan Paksa Tiket</h3>
                <p id="fc-modal-judul" class="text-xs text-rose-200/80 mt-0.5 line-clamp-1">Judul Tiket</p>
            </div>
            <button type="button" onclick="closeForceCloseModal()" class="text-rose-300 hover:text-white p-1 rounded-lg hover:bg-white/10 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <!-- Form Force Close -->
        <form id="fc-form" method="POST" action="" class="p-5 sm:p-6 space-y-4">
            @csrf
            <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-900 leading-relaxed">
                <strong>Peringatan Kebijakan:</strong> Penutupan paksa (Force Close) hanya dilakukan jika pengaduan berada di luar kewenangan teknis DKIS, bertentangan dengan regulasi, atau masalah telah terselesaikan namun pelapor terus melakukan reopen.
            </div>

            <div>
                <label for="force_close_notes" class="block text-xs font-bold text-slate-800 mb-1">
                    Catatan Resmi Pertimbangan Pimpinan (Wajib, Minimal 10 Karakter) *
                </label>
                <textarea name="force_close_notes" id="fc-notes" rows="4" required minlength="10" placeholder="Jelaskan dasar hukum, koordinasi kebijakan, atau alasan penutupan tiket secara permanen..."
                    class="w-full text-xs rounded-xl border border-slate-300 p-3 focus:outline-none focus:ring-2 focus:ring-rose-500 transition leading-relaxed"></textarea>
                <p class="text-[11px] text-slate-400 mt-1">Catatan ini akan tersimpan permanen pada jejak audit dan dapat dibaca pelapor.</p>
            </div>

            <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2.5">
                <button type="button" onclick="closeForceCloseModal()"
                    class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition">
                    Batal
                </button>
                <button type="submit" onclick="return confirm('Apakah Anda yakin ingin mengeksekusi Force Close untuk tiket ini? Tindakan ini permanen.')"
                    class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl text-xs shadow-md transition flex items-center gap-1.5">
                    <span>Eksekusi Force Close</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    window.openForceCloseModalFromBtn = function(btn) {
        if (!btn) return;
        const ticketId = btn.getAttribute('data-id');
        const kodeTiket = btn.getAttribute('data-kode');
        const judul = btn.getAttribute('data-judul');
        window.openForceCloseModal(ticketId, kodeTiket, judul);
    };

    window.openForceCloseModal = function(ticketId, kodeTiket, judul) {
        const kodeEl = document.getElementById('fc-modal-kode');
        const judulEl = document.getElementById('fc-modal-judul');
        const formEl = document.getElementById('fc-form');
        const notesEl = document.getElementById('fc-notes');
        const modalEl = document.getElementById('force-close-modal');

        if (kodeEl) kodeEl.textContent = kodeTiket;
        if (judulEl) judulEl.textContent = judul;
        if (formEl) formEl.action = `/admin/tiket/${ticketId}/force-close`;
        if (notesEl) notesEl.value = '';
        if (modalEl) modalEl.classList.remove('hidden');
    };

    window.closeForceCloseModal = function() {
        const modalEl = document.getElementById('force-close-modal');
        if (modalEl) modalEl.classList.add('hidden');
    };
</script>
@endsection
