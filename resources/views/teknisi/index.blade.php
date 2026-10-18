@extends('teknisi.layout')

@section('content')
<section class="v" id="v-tugas">
    <!-- Stat Cards (Antrean, Mendesak, Dikerjakan, Selesai) -->
    <div class="stats" id="stats">
        <div class="sk">
            <div class="h">
                <span class="l">Antrean</span>
                <span class="ico" style="background:#EFF6FF;color:#1D4ED8;">📥</span>
            </div>
            <b style="color:#0F172A;">{{ $stats['antrean'] }}</b>
            <span class="sub">Tiket aktif ditugaskan</span>
        </div>
        <div class="sk {{ $stats['mendesak'] > 0 ? 'warn' : '' }}">
            <div class="h">
                <span class="l">Mendesak</span>
                <span class="ico" style="background:#FFF1F2;color:#BE123C;">⏱️</span>
            </div>
            <b style="color:#BE123C;">{{ $stats['mendesak'] }}</b>
            <span class="sub">Sisa waktu &lt; 2 jam</span>
        </div>
        <div class="sk">
            <div class="h">
                <span class="l">Dikerjakan</span>
                <span class="ico" style="background:#FFFBEB;color:#92400E;">🛠️</span>
            </div>
            <b style="color:#B45309;">{{ $stats['dikerjakan'] }}</b>
            <span class="sub">Proses tindakan lapangan</span>
        </div>
        <div class="sk">
            <div class="h">
                <span class="l">Selesai</span>
                <span class="ico" style="background:#ECFDF5;color:#065F46;">✅</span>
            </div>
            <b style="color:#065F46;">{{ $stats['selesai'] }}</b>
            <span class="sub">Disetujui Admin Bidang</span>
        </div>
    </div>

    <!-- Status Filter Pills -->
    <div class="pills" id="pills" role="group" aria-label="Filter status">
        <!-- Akan dirender dinamis -->
    </div>

    <!-- Tools: Search Box & Sort Dropdown -->
    <div class="tools">
        <input class="in" id="q" type="search" aria-label="Cari tiket" placeholder="Cari tiket..." value="{{ $q }}">
        <select id="sort" aria-label="Urutkan tiket">
            <option value="sla" {{ $sort === 'sla' ? 'selected' : '' }}>Urutkan: SLA</option>
            <option value="tlp" {{ $sort === 'tlp' ? 'selected' : '' }}>Urutkan: TLP</option>
            <option value="new" {{ $sort === 'new' ? 'selected' : '' }}>Urutkan: Terbaru</option>
        </select>
    </div>

    <!-- Dynamic Ticket List -->
    <div class="list" id="list">
        <!-- Dirender interaktif via JavaScript -->
    </div>
</section>

<!-- Detail Ticket Modal (dD) -->
<dialog id="dD" aria-labelledby="dT"></dialog>

<!-- Obstacle & Decline Modal (dF) -->
<dialog id="dF" class="modal-compact" aria-labelledby="fT"></dialog>
@endsection

@section('scripts')
<script>
    let T = @json($tickets);
    let filter = "{{ $filter }}";
    let sortVal = "{{ $sort }}";
    let cur = null;

    const ST = {
        baru: 'Baru',
        kerja: 'Dikerjakan',
        review: 'Menunggu Review',
        selesai: 'Selesai'
    };

    const TLPL = {
        RED: 'RED (Kritis)',
        AMBER: 'AMBER (Tinggi)',
        GREEN: 'GREEN (Sedang)',
        CLEAR: 'CLEAR'
    };

    const TLPC = {
        RED: '#E11D48',
        AMBER: '#D97706',
        GREEN: '#059669',
        CLEAR: '#64748B'
    };

    const left = t => t.left;
    const used = t => Math.max(0, Math.min(100, t.used));
    const live = t => t.st === 'baru' || t.st === 'kerja';

    const ft = m => {
        const a = Math.abs(m);
        return (m < 0 ? 'Lewat ' : '') + String(Math.floor(a / 60)).padStart(2, '0') + 'j ' + String(a % 60).padStart(2, '0') + 'm';
    };

    const col = t => {
        if (left(t) < 0) return '#BE123C';
        if (used(t) >= 80) return '#E11D48';
        if (used(t) >= 60) return '#D97706';
        return '#1F6E60';
    };

    const bar = (p, c) => `<div class="bar"><i style="width:${p}%;background:${c}"></i></div>`;
    const tag = (c, x, dot) => `<span class="tg ${c}">${dot ? `<span class="dot" style="background:${dot}"></span>` : ''}${x}</span>`;

    const waLink = t => {
        const phone = t.wa || '';
        const msg = encodeURIComponent(`Halo ${t.pel}, saya ${t.pic || 'Teknisi DKIS'} dari Tim Penanganan DKIS Kota Cirebon terkait tiket #${t.code} (${t.title}).`);
        return phone ? `https://wa.me/${phone}?text=${msg}` : '#';
    };

    function renderPills() {
        const d = {
            semua: '#6366F1',
            baru: '#3B82F6',
            kerja: '#F59E0B',
            review: '#A855F7',
            selesai: '#10B981'
        };
        const labels = {
            semua: 'Semua',
            baru: 'Baru',
            kerja: 'Kerja',
            review: 'Menunggu',
            selesai: 'Selesai'
        };
        const n = k => k === 'semua' ? T.length : T.filter(t => t.st === k).length;

        $('#pills').innerHTML = Object.keys(d).map(k => `
            <button class="pill" data-f="${k}" aria-pressed="${filter === k}">
                <span class="dot" style="background:${d[k]}"></span>
                ${labels[k]} <em>(${n(k)})</em>
            </button>
        `).join('');
    }

    function renderList() {
        const q = ($('#q').value || '').toLowerCase();
        const s = $('#sort').value;
        const o = { RED: 0, AMBER: 1, GREEN: 2, CLEAR: 3 };

        let a = T.filter(t => (filter === 'semua' || t.st === filter) &&
            ((t.code || '') + (t.title || '') + (t.opd || '') + (t.pel || '') + (t.bidang || '') + (t.cat || '')).toLowerCase().includes(q)
        );

        a.sort((x, y) => {
            if (s === 'tlp') return (o[x.tlp] ?? 4) - (o[y.tlp] ?? 4);
            if (s === 'new') return y.id - x.id;
            return (live(y) - live(x)) || (left(x) - left(y));
        });

        if (!a.length) {
            $('#list').innerHTML = `
                <div class="empty">
                    <div style="font-size:36px;margin-bottom:8px">📋</div>
                    <b style="font-size:15px;color:var(--ink)">Tidak Ada Tiket Ditemukan</b>
                    <p style="margin:4px 0 0;font-size:13px">Tidak ada tiket yang cocok dengan filter atau kata kunci pencarian Anda.</p>
                </div>
            `;
            return;
        }

        $('#list').innerHTML = a.map(t => {
            const lv = live(t);
            let slaText = '';
            if (lv) {
                if (left(t) < 0) {
                    slaText = `<span style="color:#BE123C;font-weight:700">Lewat SLA: ${ft(left(t))}</span>`;
                } else {
                    slaText = `<span style="color:#0F172A;font-weight:700">Sisa SLA: ${ft(left(t))}</span>`;
                }
            } else if (t.st === 'review') {
                slaText = `<span style="color:#7C3AED;font-weight:700">Menunggu Review Admin Bidang</span>`;
            } else {
                slaText = `<span style="color:#059669;font-weight:700">Selesai Tepat Waktu</span>`;
            }

            let b = '';
            if (t.st === 'baru') {
                b = `
                    <button class="btn" data-a="terima" data-id="${t.id}">Terima</button>
                    <button class="btn danger" data-a="tolak" data-id="${t.id}">Tolak</button>
                `;
            } else if (t.st === 'kerja') {
                b = `
                    <a class="btn wa" href="${waLink(t)}" target="_blank" rel="noopener">Chat WA</a>
                `;
            }

            return `
                <article class="tk">
                    <div style="display:grid;gap:6px">
                        <div class="bd">
                            <span class="code">${t.code}</span>
                            ${tag(t.tlp, 'TLP: ' + (TLPL[t.tlp] || t.tlp), TLPC[t.tlp] || '#64748B')}
                            ${tag(t.st, ST[t.st] || t.st)}
                            <span class="tg-cat">${esc(t.cat)}</span>
                        </div>
                        <h3 class="tt link" tabindex="0" role="button" data-a="det" data-id="${t.id}">
                            ${esc(t.title)}
                        </h3>
                        <div class="info">
                            <b>${esc(t.opd)}</b> • Pelapor: ${esc(t.pel)}<br>
                            Lokasi: ${esc(t.lok)} • PIC: ${esc(t.pic || 'Anda')}
                        </div>
                    </div>
                    <div style="display:grid;gap:6px">
                        <div class="row">
                            <span class="sl">${slaText}</span>
                        </div>
                        ${lv ? bar(used(t), col(t)) : bar(100, '#1F6E60')}
                        <div class="bt">
                            ${b}
                            <button class="btn o" data-a="det" data-id="${t.id}">Detail</button>
                        </div>
                    </div>
                </article>
            `;
        }).join('');
    }

    function openDetail(id) {
        cur = T.find(t => t.id == id);
        if (!cur) return;
        const t = cur;
        const lv = live(t);
        const k = t.st === 'kerja';

        let acts = '';
        if (t.st === 'baru') {
            acts = `
                <button class="btn danger" data-a="tolak" data-id="${t.id}">Tolak Tugas</button>
                <button class="btn" data-a="terima" data-id="${t.id}">Terima Penugasan</button>
            `;
        } else if (t.st === 'kerja') {
            acts = `
                <button class="btn o" data-a="hamb" data-id="${t.id}">Laporkan Hambatan</button>
                <button class="btn" data-a="selesai" data-id="${t.id}">Ajukan Selesai (Review)</button>
            `;
        } else if (t.st === 'review') {
            acts = `<span class="p" style="font-weight:600;color:#7C3AED">⏳ Pekerjaan sedang diverifikasi Admin Bidang.</span>`;
        } else {
            acts = `<span class="p" style="font-weight:600;color:#059669">✅ Tiket telah selesai dan disetujui.</span>`;
        }

        const filesHtml = (t.files && t.files.length) 
            ? t.files.map(f => `
                <div class="fl">
                    <span>📄 ${esc(typeof f === 'string' ? f.split('/').pop() : 'Berkas Lampiran')}</span>
                    <a href="${typeof f === 'string' && f.startsWith('http') ? f : '/storage/' + f}" target="_blank" style="color:var(--g);font-weight:700;text-decoration:none">Unduh / Buka</a>
                </div>
            `).join('')
            : '<p class="p" style="color:var(--mut)">Tidak ada lampiran berkas dari pelapor.</p>';

        const teamHtml = (t.team && t.team.length)
            ? t.team.map(m => `
                <div class="fl">
                    <span>👤 ${esc(m.nama)}</span>
                    ${tag(m.role === 'Lead PIC' ? 'selesai' : 'baru', esc(m.role))}
                </div>
            `).join('')
            : `<div class="fl"><span>👤 ${esc(t.pic || 'Teknisi')}</span>${tag('selesai', 'Lead PIC')}</div>`;

        const timelineHtml = (t.tl && t.tl.length)
            ? `<ol class="stp">${t.tl.map(s => {
                const action = Array.isArray(s) ? s[0] : (s.action || s.keterangan || s.note || JSON.stringify(s));
                const time = Array.isArray(s) ? s[1] : (s.waktu || s.created_at || s.timestamp || '');
                const actor = Array.isArray(s) ? s[2] : (s.aktor || s.actor || '');
                return `<li><b>${esc(action)}</b> <span style="color:var(--mut);font-size:12px">• ${esc(time)}</span><small>Oleh: ${esc(actor)}</small></li>`;
            }).join('')}</ol>`
            : '<p class="p" style="color:var(--mut)">Belum ada catatan aktivitas.</p>';

        $('#dD').innerHTML = `
            <div class="dh">
                <div style="display:grid;gap:8px">
                    <div class="bd">
                        <span class="code">${t.code}</span>
                        ${tag(t.tlp, 'TLP: ' + (TLPL[t.tlp] || t.tlp), TLPC[t.tlp] || '#64748B')}
                        ${tag(t.st, ST[t.st] || t.st)}
                        <span class="tg-bidang">🏢 ${esc(t.bidang || 'Bidang DKIS')}</span>
                        <span class="tg-cat">🏷️ ${esc(t.cat)}</span>
                    </div>
                    <h2 class="tt" id="dT" style="font-size:20px;margin:4px 0">${esc(t.title)}</h2>
                    <div class="info" style="font-size:13px;color:var(--mut)">Diajukan: ${esc(t.masuk)}</div>
                </div>
                <button class="x" data-a="close" aria-label="Tutup modal">×</button>
            </div>
            <div class="db">
                <div class="col">
                    <div class="card">
                        <h4>Deskripsi Masalah &amp; Lokasi</h4>
                        <p class="p" style="color:var(--ink);line-height:1.6">${esc(t.desk)}</p>
                        <div style="background:#F8FAFC;padding:12px 14px;border-radius:10px;border:1px solid #E2E8F0;font-size:13px;display:grid;gap:4px">
                            <div><b>Aset / Perangkat Terdampak:</b> ${esc(t.aset || 'Infrastruktur SKPD')}</div>
                            <div><b>Lokasi Fisik:</b> ${esc(t.lokf || t.lok)}</div>
                            <div><b>Bidang Penanggung Jawab:</b> ${esc(t.bidang || 'DKIS Kota Cirebon')}</div>
                        </div>
                    </div>
                    <div class="card">
                        <h4>Berkas Lampiran dari Pelapor</h4>
                        ${filesHtml}
                    </div>
                    <div class="card">
                        <h4>Timeline Penanganan Resmi</h4>
                        ${timelineHtml}
                    </div>
                    ${k ? `
                        <div class="card" style="border-color:#2DD4BF;background:#F0FDF4">
                            <h4 style="color:#0F766E">Catatan Tindakan Lapangan (Worklog)</h4>
                            <p class="p" style="font-size:12.5px;color:#0F766E">Tuliskan langkah teknis perbaikan yang sudah Anda kerjakan di lokasi secara bertahap.</p>
                            <textarea class="in" id="cn" placeholder="Contoh: Switch di-restart, konfigurasi port VLAN diperbarui, koneksi database diverifikasi normal..."></textarea>
                            <p class="err" id="ce"></p>
                            <button class="btn" data-a="prog" data-id="${t.id}" style="align-self:flex-start">Simpan Progres Lapangan</button>
                        </div>
                    ` : ''}
                </div>
                <div class="col r">
                    <div class="card">
                        <div class="row">
                            <h4>Status SLA Berjalan</h4>
                            ${lv ? tag(left(t) < 0 ? 'RED' : 'AMBER', used(t) + '% terpakai') : ''}
                        </div>
                        ${lv ? `
                            <div class="big" style="color:${col(t)}">${ft(left(t))}</div>
                            ${bar(used(t), col(t))}
                            <div class="note" style="margin-top:8px">
                                <b>Kebijakan SLA Ketat:</b> Jam SLA terus berjalan tanpa jeda pelaksana. Hanya berhenti otomatis saat status menunggu kelengkapan pelapor.
                                ${t.hamb ? `<br><b style="color:#BE123C">Hambatan tercatat:</b> ${esc(t.hamb)}. Jam SLA tidak dihentikan.` : ''}
                            </div>
                        ` : `
                            <p class="p" style="font-weight:600;color:${t.st === 'review' ? '#7C3AED' : '#059669'}">
                                ${t.st === 'review' ? 'Penanganan diajukan. Penilaian SLA menunggu verifikasi Admin Bidang.' : 'Pekerjaan tuntas tepat waktu.'}
                            </p>
                        `}
                    </div>
                    <div class="card">
                        <h4>Kontak OPD &amp; Pelapor</h4>
                        <div>
                            <b style="font-size:14px">${esc(t.pel)}</b>
                            <div class="info">${esc(t.jab || 'ASN Pelapor')} • ${esc(t.opd)}</div>
                        </div>
                        <div class="info">Nomor Kontak / WhatsApp: <b>${esc(t.hp || '-')}</b></div>
                        ${t.st === 'selesai' ? '' : `
                            <a class="btn wa" href="${waLink(t)}" target="_blank" rel="noopener" style="margin-top:4px">
                                Hubungi via WhatsApp
                            </a>
                        `}
                        <p class="p" style="font-size:11.5px;color:var(--mut)">Data kontak pelapor hanya aktif selama tiket berada dalam penugasan Anda.</p>
                    </div>
                    <div class="card">
                        <h4>Tim Teknisi yang Bertugas</h4>
                        ${teamHtml}
                    </div>
                </div>
            </div>
            <div class="df">
                ${acts}
                <button class="btn o" data-a="close" style="flex:none">Tutup</button>
            </div>
        `;

        const d = $('#dD');
        if (!d.open) d.showModal();
    }

    function openForm(mode) {
        const d = $('#dF');
        const t = cur;
        const h = mode === 'hamb';

        d.innerHTML = `
            <div class="dh">
                <div>
                    <h2 class="tt" id="fT" style="font-size:18px;margin:0">${h ? 'Laporkan Hambatan Lapangan' : 'Tolak Penugasan Tiket'}</h2>
                    <div class="info" style="font-size:12px;color:var(--mut)">${t.code} • ${esc(t.title)}</div>
                </div>
                <button type="button" class="x" data-a="fclose" aria-label="Tutup modal">×</button>
            </div>
            <div class="db" style="grid-template-columns:1fr;width:100%;padding:16px 20px">
                <form id="ff" class="col" novalidate onsubmit="return false">
                    ${h ? `
                        <div class="note">
                            <b>Pemberitahuan Kebijakan SLA Ketat:</b> Jam SLA tetap berjalan! Laporan ini mengekskalasi hambatan ke Admin Bidang dan Admin Pusat. Pengecualian SLA hanya dapat diputuskan oleh Admin Pusat.
                        </div>
                        <fieldset class="card" style="margin:0;padding:12px">
                            <legend class="k">Kategori Hambatan Lapangan</legend>
                            ${['Menunggu ISP', 'Menunggu vendor', 'Menunggu OPD lain', 'Menunggu suku cadang'].map((x, i) => `
                                <label class="opt">
                                    <input type="radio" name="j" value="${x}" ${i === 0 ? 'checked' : ''}> 
                                    <span><b>${x}</b></span>
                                </label>
                            `).join('')}
                        </fieldset>
                        <div class="card" style="padding:12px">
                            <label class="k" for="bk">Nomor Tiket Vendor / ISP / Nomor Bukti (Wajib)</label>
                            <input class="in" id="bk" placeholder="Contoh: TIK-ISP-9942 atau tautan berita acara">
                        </div>
                    ` : `
                        <div style="background:#FFF1F2;border:1px solid #FECDD3;padding:12px 14px;border-radius:12px;color:#9F1239;font-size:12.5px;line-height:1.4">
                            <b>Konfirmasi Penolakan:</b> Tiket akan ditolak dan dikembalikan ke antrean Admin Bidang untuk didisposisikan ke personil teknisi lain.
                        </div>
                    `}
                    <div class="card" style="padding:12px">
                        <label class="k" for="kt">${h ? 'Rincian Hambatan & Perkiraan Waktu' : 'Alasan Penolakan Formal (Min. 10 Karakter) *'}</label>
                        <textarea class="in" id="kt" rows="3" placeholder="${h ? 'Jelaskan kondisi di lapangan dan solusi alternatif...' : 'Tuliskan alasan penolakan secara jelas dan dapat dipertanggungjawabkan...'}"></textarea>
                        <p class="err" id="fe"></p>
                    </div>
                </form>
            </div>
            <div class="df">
                <button type="button" class="btn o" data-a="fclose" style="flex:none">Batal</button>
                <button type="button" class="btn ${h ? '' : 'danger'}" data-a="${h ? 'hsend' : 'tsend'}">${h ? 'Kirim Laporan Hambatan' : 'Konfirmasi Tolak Tugas'}</button>
            </div>
        `;

        d.showModal();
    }

    async function apiPost(url, data) {
        const token = $('meta[name="csrf-token"]').content;
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        });
        const resData = await res.json().catch(() => ({}));
        if (!res.ok) {
            const msg = resData.message || (resData.errors ? Object.values(resData.errors).flat().join(', ') : 'Terjadi kegagalan server.');
            throw new Error(msg);
        }
        return resData;
    }

    document.addEventListener('click', async e => {
        const b = e.target.closest('[data-a],[data-f]');
        if (!b) return;

        if (b.dataset.f) {
            filter = b.dataset.f;
            renderPills();
            renderList();
            return;
        }

        const a = b.dataset.a;
        const targetId = b.dataset.id;
        const t = targetId ? T.find(x => x.id == targetId) : cur;
        if (t) cur = t;

        if (a === 'det') {
            openDetail(t.id);
        } else if (a === 'close') {
            $('#dD').close();
        } else if (a === 'fclose') {
            $('#dF').close();
        } else if (a === 'terima') {
            try {
                const res = await apiPost(`/teknisi/tugas/${t.id}/terima`, {});
                if (res.success) {
                    t.st = 'kerja';
                    if (!t.tl) t.tl = [];
                    t.tl.push(['Diterima dan mulai dikerjakan', new Date().toLocaleTimeString('id-ID', {hour:'2-digit',minute:'2-digit'}).replace(':','.'), 'Anda']);
                    toast('Tugas diterima. Jam SLA resmi berjalan.');
                    renderList();
                    if ($('#dD').open) openDetail(t.id);
                }
            } catch (err) {
                toast(err.message || 'Gagal menerima tugas.');
            }
        } else if (a === 'tolak') {
            openForm('tolak');
        } else if (a === 'hamb') {
            openForm('hamb');
        } else if (a === 'prog') {
            const v = ($('#cn').value || '').trim();
            if (v.length < 5) {
                $('#ce').textContent = 'Tuliskan catatan minimal 5 karakter.';
                return;
            }
            try {
                const res = await apiPost(`/teknisi/tugas/${t.id}/progres`, { catatan: v });
                if (res.success) {
                    if (!t.tl) t.tl = [];
                    t.tl.push(['Progres: ' + v, new Date().toLocaleTimeString('id-ID', {hour:'2-digit',minute:'2-digit'}).replace(':','.'), 'Anda']);
                    toast('Progres lapangan berhasil disimpan.');
                    openDetail(t.id);
                }
            } catch (err) {
                toast(err.message || 'Gagal menyimpan progres.');
            }
        } else if (a === 'selesai') {
            const v = ($('#cn')?.value || '').trim();
            if (v.length < 10) {
                if ($('#ce')) $('#ce').textContent = 'Isi catatan tindakan teknis minimal 10 karakter sebelum mengajukan selesai.';
                $('#cn')?.focus();
                return;
            }
            try {
                const res = await apiPost(`/teknisi/tugas/${t.id}/selesai`, { catatan: v });
                if (res.success) {
                    t.st = 'review';
                    if (!t.tl) t.tl = [];
                    t.tl.push(['Diajukan selesai: ' + v + '. Menunggu review Admin Bidang.', new Date().toLocaleTimeString('id-ID', {hour:'2-digit',minute:'2-digit'}).replace(':','.'), 'Anda']);
                    toast('Diajukan ke Admin Bidang untuk direview.');
                    renderList();
                    if ($('#dD').open) openDetail(t.id);
                }
            } catch (err) {
                toast(err.message || 'Gagal mengajukan penyelesaian.');
            }
        } else if (a === 'hsend') {
            const bk = ($('#bk').value || '').trim();
            const kt = ($('#kt').value || '').trim();
            if (!bk || kt.length < 10) {
                $('#fe').textContent = 'Bukti / No. tiket wajib diisi dan keterangan minimal 10 karakter.';
                return;
            }
            const j = document.querySelector('input[name=j]:checked').value;
            try {
                const res = await apiPost(`/teknisi/tugas/${t.id}/hambatan`, {
                    obstacle_type: j,
                    evidence: bk,
                    description: kt
                });
                if (res.success) {
                    t.hamb = `${j} (${kt})`;
                    if (!t.tl) t.tl = [];
                    t.tl.push([`Hambatan dilaporkan: ${j} (bukti: ${bk}). SLA tetap berjalan`, new Date().toLocaleTimeString('id-ID', {hour:'2-digit',minute:'2-digit'}).replace(':','.'), 'Anda']);
                    $('#dF').close();
                    toast('Hambatan dilaporkan. Jam SLA tetap berjalan sesuai regulasi.');
                    renderList();
                    if ($('#dD').open) openDetail(t.id);
                }
            } catch (err) {
                toast(err.message || 'Gagal melaporkan hambatan.');
            }
        } else if (a === 'tsend') {
            const kt = ($('#kt').value || '').trim();
            if (kt.length < 10) {
                $('#fe').textContent = 'Alasan penolakan minimal 10 karakter.';
                return;
            }
            try {
                const res = await apiPost(`/teknisi/tugas/${t.id}/tolak`, { alasan: kt });
                if (res.success) {
                    T = T.filter(x => x.id !== t.id);
                    $('#dF').close();
                    if ($('#dD').open) $('#dD').close();
                    toast('Tiket ditolak dan dikembalikan ke antrean Admin Bidang.');
                    renderPills();
                    renderList();
                }
            } catch (err) {
                toast(err.message || 'Gagal menolak penugasan.');
            }
        }
    });

    $('#q').addEventListener('input', renderList);
    $('#sort').addEventListener('change', renderList);

    renderPills();
    renderList();
</script>
@endsection
