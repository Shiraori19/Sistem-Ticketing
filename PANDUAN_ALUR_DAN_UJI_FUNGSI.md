# PANDUAN LENGKAP ALUR KERJA SISTEM & PENGUJIAN FUNGSI (END-TO-END WORKFLOW & QA MANUAL)
## Sistem Helpdesk & Ticketing Layanan TIK — DKIS Kota Cirebon

---

## 1. PENDAHULUAN & ARSITEKTUR PERAN PENGGUNA

Sistem Ticketing DKIS Kota Cirebon dirancang untuk mengelola permohonan layanan teknologi informasi, perbaikan infrastruktur jaringan, pengembangan aplikasi, hingga penanganan insiden keamanan siber di lingkungan Pemerintah Kota Cirebon.

Sistem membagi hak operasional ke dalam **4 peran pengguna** dengan batas wewenang yang tegas (*Role-Based Access Control*):
1. **Pelapor (Aparatur Sipil Negara / ASN Pemkot Cirebon):** Mengajukan aduan secara mandiri tanpa pembuatan akun login rumit (*Zero-Friction Access*), memantau status transparan, melengkapi data susulan, serta memberikan konfirmasi dan rating ulasan.
2. **Admin Pusat (Verifikator Utama DKIS):** Melakukan triage seluruh tiket masuk, menetapkan tingkat kerahasiaan (*Traffic Light Protocol* / TLP), mendisposisikan tiket ke bidang pengampu, mengelola insiden massal (*Parent-Child Linking*), serta memiliki hak veto arbitrase (*Force Close*).
3. **Admin Bidang (Koordinator Tim Teknis Bidang):** Memantau antrean bidangnya, menganalisis ketersediaan dan beban kerja teknisi (maksimal 6 poin beban aktif), menunjuk teknisi koordinator (*Lead PIC*) dan pendamping (*Member*), serta memverifikasi hasil pekerjaan lapangan (*Review Submission*).
4. **Teknisi Lapangan (Pelaksana Teknis):** Menerima notifikasi penugasan, merespons tugas (Terima/Tolak), mencatat progres lapangan (*Worklog*), melaporkan hambatan eksternal (*Blocker*), dan mengajukan hasil perbaikan teknis (*Submit for Review*).

---

## 2. 9 STATUS BAKU SIKLUS HIDUP TIKET (TICKET STATE MACHINE)

Sistem mengadopsi **9 status baku** tanpa penambahan status kustom di database untuk menjaga integritas transaksi dan kepatuhan audit sistem:

| No | Kode Status | Label Status | Pihak yang Bertanggung Jawab | Deskripsi Operasional |
|:--:|:---|:---|:---|:---|
| 1 | `pending_verification` | Menunggu Verifikasi | Admin Pusat | Tiket baru diajukan oleh ASN atau dikembalikan oleh Admin Bidang karena salah disposisi. |
| 2 | `pending_info` | Menunggu Info Pelapor | Pelapor ASN | Data pengaduan belum lengkap. Timer SLA respon dijeda sementara menunggu kelengkapan susulan dari pelapor. |
| 3 | `verified` | Terverifikasi / Antrean Bidang | Admin Bidang | Tiket telah disetujui Admin Pusat, jam SLA resmi mulai berjalan, menunggu penunjukan teknisi oleh Admin Bidang. |
| 4 | `in_progress` | Dalam Pengerjaan | Teknisi Lapangan / Admin Bidang | Teknisi telah ditugaskan dan sedang melakukan diagnosis, penanganan fisik/sistem di lapangan. |
| 5 | `resolved` | Selesai Dikerjakan | Pelapor ASN | Pekerjaan teknisi telah disetujui Admin Bidang. Menunggu konfirmasi tuntas atau sanggahan dari pelapor. |
| 6 | `reopened` | Dibuka Kembali | Admin Bidang & Teknisi | Pelapor menyatakan masalah belum tuntas dan menyertakan bukti kendala baru (maksimal 2x reopen). |
| 7 | `closed` | Ditutup Selesai | Selesai Permanen | Pelapor mengonfirmasi penanganan tuntas serta memberikan rating bintang (1–5) dan ulasan kepuasan. |
| 8 | `eskalasi_pusat` | Eskalasi Admin Pusat | Admin Pusat (Pimpinan) | Tiket mengalami kebuntuan (*deadlock*), reopen ke-3, atau konflik wewenang lintas instansi/bidang. |
| 9 | `force_closed` / `cancelled` | Ditutup Paksa / Dibatalkan | Admin Pusat / Pelapor | Tiket ditutup paksa oleh Admin Pusat via hak veto arbitrase, atau dibatalkan oleh pelapor di fase awal. |

---

## 3. ALUR KERJA UTAMA (HAPPY PATH LIFECYCLE)

```
[ PELAPOR ASN ]
   │
   ├─► 1. Mengisi Form Pengaduan Mandiri (NIP BKN, No WA, SKPD, Kategori, Judul, Deskripsi, Berkas)
   └─► 2. Menerima Kode Tiket (TIK-YYYYMM-XXXX) & PIN Keamanan 6-Digit (Cetak Struk/PDF)
         │
         ▼
[ ADMIN PUSAT ]
   │
   ├─► 3. Membuka Antrean 'pending_verification' di Dashboard Pusat
   ├─► 4. Menetapkan Klasifikasi Keamanan TLP (RED, AMBER, GREEN, CLEAR)
   ├─► 5. Mendisposisikan ke Bidang Pengampu (SLA Resolusi Resmi Dimulai)
   └─► 6. (Opsi) Menautkan Tiket Anak ke Induk Tiket (Parent-Child) jika Insiden Massal
         │
         ▼
[ ADMIN BIDANG ]
   │
   ├─► 7. Membuka Antrean Bidang ('verified')
   ├─► 8. Memeriksa Dashboard Ketersediaan & Beban Kerja Teknisi (Kapasitas Maks. 6 Poin)
   └─► 9. Menugaskan Lead PIC (+ Pendamping Opsional) dengan SLA Respon (15 Menit / 60 Menit)
         │
         ▼
[ TEKNISI LAPANGAN ]
   │
   ├─► 10. Menerima Notifikasi di Portal Tugas (/teknisi/tugas)
   ├─► 11. Merespons Tugas: TERIMA (Accept) atau TOLAK (Decline dengan Alasan Formal)
   ├─► 12. Menuju Lokasi & Klik 'Mulai Pengerjaan' (Status: working)
   ├─► 13. Mencatat Progres Lapangan Bertahap (Worklog & Berkas)
   ├─► 14. Melaporkan Hambatan Eksternal jika ada (Jam SLA Tetap Berjalan)
   └─► 15. Klik 'Ajukan Selesai' (Submit for Review) beserta Bukti Foto & Solusi Teknis
         │
         ▼
[ ADMIN BIDANG ]
   │
   ├─► 16. Memeriksa Bukti Perbaikan & Solusi Lapangan
   ├─► 17. Opsi A: SETUJUI (Approve) ──► Tiket Berstatus 'resolved' (Jam Kerja Berhenti di Submitted_at)
   └─► 18. Opsi B: KEMBALIKAN (Return) ──► Teknisi Harus Memperbaiki Ulang dengan Catatan Revisi
         │
         ▼
[ PELAPOR ASN ]
   │
   ├─► 19. Memeriksa Bukti Selesai via Portal Lacak Tiket Mandiri (Kode Tiket + PIN)
   └─► 20. Klik 'Konfirmasi Selesai', Memberikan Rating (1–5 Bintang) & Testimoni ──► Status: 'closed'
```

### Penjelasan Rinci Setiap Tahap Utama:

#### Tahap 1: Pengajuan Tiket oleh Pelapor ASN
- Pelapor mengakses portal publik tanpa login di `https://localhost:8443` (atau `http://localhost`).
- Pelapor mengisi data identitas:
  - **NIP ASN:** Divalidasi menggunakan Custom Rule `ValidNipBkn` (wajib 18 digit angka terstruktur: format tanggal lahir YYYYMMDD, TMT CPNS YYYYMM, gender 1/2, dan nomor urut).
  - **Nama Pelapor, SKPD/OPD, dan Nomor WhatsApp aktif.**
  - **Kategori Layanan:** Dipilih dari 13 kategori standar DKIS (Jaringan Internet/Intranet, Troubleshooting PC, Aplikasi SPBE, Email Dinas, Hosting Web, TTE BSrE, Penanganan Insiden Siber, dll.).
  - **Judul dan Deskripsi Lengkap Masalah.**
  - **Lampiran Bukti:** Berkas foto/dokumen pendukung (maks. 5 MB per berkas). Berkas disimpan sementara ke direktori staging berbasis UUID unik (`tickets/staging/{uuid}/`) untuk mencegah *file collision* dan *race condition*.
- Sistem mengeksekusi transaksi database dengan **PostgreSQL Advisory Lock** (`pg_advisory_xact_lock`) untuk menjamin nomor urut kode tiket unik berformat `TIK-YYYYMM-XXXX`.
- Sistem menghasilkan **PIN Keamanan 6 Digit Acak**. PIN ini langsung di-hash menggunakan **Bcrypt** sebelum disimpan ke database (anti-kebocoran data). PIN mentah hanya ditampilkan satu kali pada lembar tanda terima pelapor.
- Pelapor dapat mengunduh atau mencetak struk bukti lapor berformat PDF/cetak peramban. Status awal tiket: `pending_verification`.

#### Tahap 2: Verifikasi & Disposisi oleh Admin Pusat
- Admin Pusat membuka menu **Antrean Verifikasi** (`/admin/verifikasi`).
- Admin Pusat memeriksa kelayakan pengaduan, kejelasan informasi, dan keabsahan berkas.
- Menetapkan **Traffic Light Protocol (TLP)**:
  - `RED`: Insiden keamanan siber sangat rahasia (otomatis diarahkan eksklusif ke Bidang Persandian & CSIRT, status langsung `in_progress`).
  - `AMBER`: Informasi sensitif terbatas untuk instansi pengadu dan tim teknis.
  - `GREEN`: Informasi operasional internal antar-OPD.
  - `CLEAR`: Informasi publik non-sensitif.
- Menentukan **Bidang Penanggung Jawab** (Bidang Infrastruktur TIK, Bidang Aplikasi Digital, atau Bidang Persandian & Keamanan Informasi).
- Menekan tombol **Verifikasi & Disposisikan**:
  - Kolom `verified_by` mencatat ID Admin Pusat.
  - Timestamp `sla_dimulai_at` diinisialisasi ke waktu saat ini.
  - Kolom `sla_deadline_at` dihitung otomatis: waktu verifikasi + durasi target resolusi kategori (standar: 14 hari kalender / 336 jam).
  - Status tiket beralih ke `verified` (atau `in_progress` untuk TLP RED).

#### Tahap 3: Penerimaan & Penugasan Teknisi oleh Admin Bidang
- Admin Bidang membuka menu **Antrean Bidang** (`/admin/bidang/tiket?filter=antrean`).
- Meninjau tiket berstatus `verified` atau `reopened`.
- Membuka modal **Penugasan Teknisi**:
  - Sistem menampilkan drawer analitik ketersediaan personil: status teknisi (`tersedia`, `sibuk`, atau `cuti`), kecocokan skill kategori kendala, serta penghitungan beban kerja aktif secara teragregasi.
  - Aturan Beban Kerja (*Workload Capacity*): Kuota maksimal default adalah **6 poin beban**. Tiket diberi bobot berdasarkan TLP (`RED` = 3 poin, `AMBER` = 2 poin, `GREEN/CLEAR` = 1 poin).
  - Jika seorang teknisi telah mencapai batas kapasitas (misal 6/6 poin), sistem menolak penugasan kecuali Admin Bidang menyertakan alasan dispensasi khusus (*Override Reason*) yang dicatat ke dalam audit log.
- Admin Bidang memilih **Lead PIC** (teknisi koordinator utama) dan dapat menambahkan beberapa **Member** (teknisi pendamping lapangan).
- Sistem membuat baris `TicketAssignment` baru dengan batas waktu konfirmasi respons (`respond_deadline`):
  - Tiket `TLP RED`: Batas waktu respon **15 menit**.
  - Tiket reguler (`AMBER/GREEN/CLEAR`): Batas waktu respon **60 menit**.
- Status tiket beralih ke `in_progress` dan nama tim teknisi tercatat di kolom `teknisi_lapangan`.

#### Tahap 4: Notifikasi & Respon Penugasan oleh Teknisi Lapangan
- Teknisi masuk ke portal mandiri di `/teknisi/tugas`.
- Penugasan baru tampil di tab antrean tugas teknisi dengan badge hitung mundur SLA respon konfirmasi.
- **Proteksi Akses Kontak:** Nomor kontak WhatsApp pelapor disembunyikan secara *default*. Kontak hanya dapat dibuka jika penugasan teknisi berstatus aktif (`assigned` atau `working`). Saat tombol lihat kontak diklik, sistem mencatat audit trail `contact_viewed`.
- Teknisi memiliki 2 pilihan aksi:
  1. **Terima Tugas (Accept Task):** Status penugasan berubah menjadi `accepted`. Teknisi siap meluncur ke lokasi penanganan.
  2. **Tolak Tugas (Decline Task):** Teknisi wajib mengisi alasan formal penolakan (contoh: sedang menangani downtime server kritis di Command Center). Penugasan ditandai `declined`, dan tiket dikembalikan ke antrean Admin Bidang untuk diredisposisikan kepada teknisi lain.
- **Fail-safe Timeout:** Jika teknisi tidak menekan Terima/Tolak hingga melewati batas `respond_deadline`, background command scheduler (`php artisan assignments:release-expired`) otomatis melepaskan penugasan (`released`) dan mengembalikan tiket ke antrean bidang tanpa menelantarkan pelapor.

#### Tahap 5: Pelaksanaan di Lapangan, Worklog & Pelaporan Blocker
- **Mulai Pengerjaan:** Saat tiba di lokasi penanganan fisik atau memulai konfigurasi sistem, teknisi menekan tombol **Mulai Pengerjaan** (`startProgress`). Status assignment berubah menjadi `working`, dan timestamp `pengerjaan_dimulai_at` tercatat permanen.
- **Pencatatan Progres (Worklog):** Teknisi dapat mencatat progres berkala dari lapangan (misal: "Kabel FO telah disambung menggunakan fusion splicer, sedang uji redaman dBm"). Catatan ini bersifat *append-only* di database dan dapat dibaca oleh Admin Bidang serta Pelapor.
- **Pelaporan Hambatan Lapangan (Blocker/Obstacle):**
  - Jika teknisi mengalami kendala di luar kendali teknis (misal: menunggu kedatangan perangkat keras dari vendor, listrik padam di lokasi OPD, atau ruangan server terkunci tanpa pemegang kunci), teknisi melaporkan formulir *Blocker*.
  - **Kebijakan SLA Ketat DKIS:** Sesuai standar tata kelola TIK, jam SLA resolusi **TETAP BERJALAN** dan tidak dihentikan/dimanipulasi. Pelaporan blocker berfungsi sebagai catatan mitigasi resmi bagi pimpinan untuk evaluasi dispensasi keterlambatan di kemudian hari.

#### Tahap 6: Penyelesaian Lapangan oleh Teknisi Langsung ke Pelapor
- **Strict No Self-Close Policy (Tidak Bisa Tutup Tiket Sendiri ke `closed`):**
  - Teknisi lapangan dilarang keras menutup tiket secara permanen (`closed`). Status `closed` mutlak merupakan hak prerogatif pelapor melalui pemberian rating/ulasan kepuasan layanan (atau *force close* pimpinan jika terjadi sengketa).
- **Alur Langsung ke Pelapor (*Agile Field Delivery*):**
  - Saat kendala teknis di lokasi telah teratasi, teknisi mengisi catatan tindakan perbaikan (`notes`) dan melampirkan berkas foto bukti fisik (`bukti_resolusi`), lalu menekan tombol **Selesaikan Pengerjaan** (`submit`).
  - Sistem secara otomatis:
    1. Mengubah status penugasan teknisi menjadi `approved` dan mencatat `submitted_at`.
    2. Mengubah status tiket menjadi **`resolved` (Selesai Penanganan)**.
    3. Langsung meneruskan tiket kepada **Pelapor** (via WhatsApp / portal lacak aduan) agar pelapor dapat menguji hasil perbaikan secara langsung di lokasi.
    4. Mengirimkan notifikasi arsip informasi kepada **Admin Bidang** bahwa pekerjaan teknis telah dituntaskan oleh teknisi pengampu.
- **Efisiensi Tanpa Bottleneck:** Alur ini mempercepat penanganan di lapangan tanpa menunggu Admin Bidang memencet tombol persetujuan manual, sehingga pelapor yang berada di ruangan kerja dapat langsung mencoba perangkat/jaringannya saat teknisi masih berada di lokasi. Jika pelapor belum puas, pelapor dapat langsung mengajukan **Reopen** (yang akan otomatis masuk ke antrean Admin Bidang).

#### Tahap 7: Konfirmasi Akhir Pelapor & Penutupan Tiket
- Pelapor memeriksa status tiket melalui portal publik lacak aduan menggunakan kombinasi Kode Tiket dan PIN 6-digit.
- Pelapor memeriksa foto bukti pengerjaan dan deskripsi solusi teknis.
- Jika kendala telah teratasi dengan baik, pelapor menekan tombol **Konfirmasi Selesai & Beri Ulasan**:
  - Pelapor memberikan rating kepuasan layanan berupa bintang 1 hingga 5.
  - Pelapor dapat menuliskan testimoni feedback.
  - Status tiket beralih menjadi `closed` secara permanen.
  - Rating teknisi otomatis teragregasi ke dalam statistik performa bulanan teknisi.

---

## 4. ALUR KHUSUS & SKENARIO PENANGANAN KENDALA (EDGE CASES)

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                       SKENARIO KHUSUS SISTEM TICKETING                      │
├───────────────────────────────┬─────────────────────────────────────────────┤
│ 1. Permintaan Kelengkapan Data│ pending_verification ──► pending_info       │
│    (Pending Info)             │ (Pelapor kirim data susulan ──► Verifikasi) │
├───────────────────────────────┼─────────────────────────────────────────────┤
│ 2. Tiket Salah Disposisi      │ verified / in_progress ──► pending_verif    │
│    (Salah Kamar)              │ (Admin Bidang kembalikan ──► Pusat redispo) │
├───────────────────────────────┼─────────────────────────────────────────────┤
│ 3. Pembukaan Kembali Tiket    │ resolved ──► reopened (Maksimal 2x)         │
│    (Reopen oleh Pelapor)      │ (Jika Reopen ke-3 ──► eskalasi_pusat)       │
├───────────────────────────────┼─────────────────────────────────────────────┤
│ 4. Arbitrase & Tutup Paksa    │ eskalasi_pusat ──► force_closed             │
│    (Eskalasi Pusat)           │ (Hak Veto Admin Pusat / Pimpinan DKIS)      │
├───────────────────────────────┼─────────────────────────────────────────────┤
│ 5. Insiden Massal Terpadu     │ Tiket-Tiket Serupa ──► Ditautkan ke 1 Induk │
│    (Parent-Child Linking)     │ (Induk Selesai ──► Seluruh Anak Auto-Close) │
├───────────────────────────────┼─────────────────────────────────────────────┤
│ 6. Isolasi Siber TLP: RED     │ Akses Non-Persandian Diblokir HTTP 404      │
│    (Gov-CSIRT Protection)     │ (Kerahasiaan Tertinggi Tanpa Bocor URL)     │
└───────────────────────────────┴─────────────────────────────────────────────┘
```

### A. Permintaan Kelengkapan Data (*Pending Info / Request Completion*)
1. **Kapan Terjadi:** Terjadi pada tahap verifikasi (Admin Pusat) atau tahap antrean (Admin Bidang) ketika laporan pengaduan pelapor ambigu, foto lampiran rusak/tidak terbaca, atau nomor kontak tidak dapat dihubungi.
2. **Eksekusi:** Admin menekan tombol **Minta Kelengkapan Data** dan menuliskan instruksi spesifik kepada pelapor (misal: "Mohon lampirkan foto pesan error pada layar monitor dan sebutkan nomor ruangan lantai kantor").
3. **Dampak Sistem:** Status tiket dialihkan ke `pending_info`. Penghitungan target SLA respon dijeda sementara agar tidak merugikan performa kepatuhan dinas.
4. **Respon Pelapor:**
   - Pelapor melihat status penangguhan dan membaca instruksi catatan kelengkapan di halaman pelacakan publik.
   - Pelapor menekan tombol **Kirim Kelengkapan Data** (`/tiket/{kode}/lengkapi-data`), mengunggah berkas tambahan dan menambahkan deskripsi pelengkap tanpa perlu membuat tiket baru dari awal.
   - Setelah data dikirim, sistem otomatis mengembalikan status tiket ke `pending_verification` dengan notifikasi kepada Admin Pusat bahwa kelengkapan telah dipenuhi.

### B. Redisposisi Tiket yang Salah Kirim (*Salah Kamar / Return to Pusat*)
1. **Kapan Terjadi:** Terjadi saat Admin Bidang menelaah tiket dan mendapati bahwa pokok masalah sebenarnya berada di luar wewenang bidangnya (contoh: tiket aduan koneksi internet drop didisposisikan ke Bidang Aplikasi, padahal ranah wewenangnya adalah Bidang Infrastruktur TIK/Jaringan).
2. **Eksekusi:** Admin Bidang menekan tombol **Kembalikan (Salah Kamar)** di panel antrean bidang, mengisi bidang yang semestinya, dan memberikan alasan teknis.
3. **Dampak Sistem & Preservasi SLA:**
   - Tiket dilepaskan dari bidang terkait dan statusnya kembali ke `pending_verification` di antrean Admin Pusat.
   - **Aturan Preservasi SLA:** Nilai `sla_dimulai_at` yang pertama kali dibuat **TETAP DIPERTAHANKAN** dan tidak di-reset ke nol. Hal ini mencegah celah manipulasi batas waktu layanan oleh petugas.
4. **Tindakan Admin Pusat:** Admin Pusat menerima kembali tiket dengan catatan riwayat pengembalian dari bidang sebelumnya, lalu mendisposisikan tiket tersebut ke bidang pengampu teknis yang tepat.

### C. Pembukaan Kembali Tiket (*Reopen oleh Pelapor*)
1. **Kapan Terjadi:** Pelapor mendapati bahwa kendala yang telah ditandai `resolved` oleh admin ternyata masih kambuh atau belum tuntas secara menyeluruh.
2. **Eksekusi:** Pelapor memasukkan Kode Tiket dan PIN di portal pelacakan, lalu menekan tombol **Ajukan Buka Kembali (Reopen)**.
3. **Aturan Ketat Reopen:**
   - Pelapor **WAJIB** mengunggah berkas bukti baru yang menunjukkan bahwa masalah belum tuntas.
   - Reopen hanya dapat dilakukan **maksimal 2 kali** (`reopen_count <= 2`).
   - Tiket berstatus `reopened` akan langsung masuk kembali ke antrean Admin Bidang dengan prioritas tinggi.

### D. Arbitrase & Hak Veto Tutup Paksa (*Eskalasi Pusat & Force Close*)
1. **Pemicu Eskalasi:**
   - Tiket diajukan reopen untuk yang ke-3 kalinya oleh pelapor (*Deadlock Loop*).
   - Terjadi perselisihan wewenang teknis yang alot antar-bidang yang saling melempar penanganan.
   - Kendala disebabkan oleh kerusakan fisik perangkat milik pribadi pegawai ASN yang tidak tercatat dalam aset resmi Pemkot Cirebon.
2. **Eksekusi Eskalasi:** Tiket otomatis beralih ke status `eskalasi_pusat` dan ditarik penuh ke wewenang pimpinan Admin Pusat.
3. **Arbitrase Admin Pusat:** Admin Pusat memanggil para pihak berkepentingan atau menerbitkan arahan penanganan terpadu lintas bidang.
4. **Hak Veto Tutup Paksa (*Force Close*):**
   - Jika permohonan memang tidak dapat dilanjutkan secara regulasi (misal: pengadaan software bajakan atau klaim kerusakan di luar aset dinas), Admin Pusat menggunakan hak veto **Force Close**.
   - Admin Pusat mengisi pertimbangan hukum/teknis resmi. Status tiket beralih ke `force_closed`.
   - Seluruh penugasan teknisi yang masih aktif untuk tiket tersebut otomatis dilepas dan dibatalkan (`released`), serta formulir aksi pengerjaan dikunci permanen.

### E. Pengelompokan Insiden Massal (*Parent-Child Linking*)
1. **Kapan Terjadi:** Terjadi gangguan massal skala kota atau kawasan (misal: kabel optik utama putus di jalur Pantura yang menyebabkan 15 OPD serentak melapor jaringan internet mati).
2. **Smart Cluster Detection:** Sistem secara cerdas mendeteksi lonjakan tiket dengan kategori serupa di antrean verifikasi dan memberikan notifikasi cluster insiden bersama kepada Admin Pusat.
3. **Eksekusi Penautan:**
   - Admin Pusat menetapkan 1 tiket pengaduan pertama sebagai **Induk Tiket (Master Incident)**.
   - Tiket-tiket pengaduan serupa lainnya ditautkan sebagai **Tiket Anak (Child Tickets)** ke tiket induk tersebut.
4. **Pewarisan Parameter:** Tiket anak mewarisi bidang pengampu, status pengerjaan, dan parameter SLA dari tiket induk.
5. **Cascading Resolution:** Saat tim teknisi menyelesaikan perbaikan pada tiket induk dan disetujui Admin Bidang, **seluruh tiket anak secara otomatis ikut berstatus `resolved`** dengan catatan solusi yang sama, menghemat waktu pelaporan secara drastis.

### F. Isolasi Siber Ketat TLP: RED (*Gov-CSIRT Security Isolation*)
1. **Kebijakan Kerahasiaan:** Tiket penanganan serangan siber (web defacement, ransomware, pencurian database, kebocoran data ASN) diberi klasifikasi `TLP: RED`.
2. **Proteksi Anti-Enumerasi (HTTP 404):**
   - Akses rute detail tugas teknisi (`/teknisi/tugas/{id}`) dilindungi oleh `TicketAssignmentPolicy`.
   - Jika teknisi atau admin dari bidang non-Persandian mencoba menebak atau membuka URL ID penugasan TLP RED, server tidak merespons dengan 403 Forbidden (yang membocorkan bahwa resource itu ada), melainkan merespons dengan **HTTP 404 Not Found**.
   - Dengan demikian, eksistensi insiden siber kritis sepenuhnya tersembunyi dari pihak yang tidak berhak.

---

## 5. PANDUAN PENGUJIAN FUNGSI SISTEM (QA & TESTING SUITE)

Sistem Ticketing DKIS Kota Cirebon dilengkapi dengan rangkaian pengujian otomatis (*Automated Test Suite*) berbasis PHPUnit dan skrip pengujian fungsional terintegrasi di dalam kontainer Docker.

### A. Rangkuman Test Suite Otomatis

| Berkas Pengujian | Jenis Tes | Jumlah Assertion | Cakupan Pengujian |
|:---|:---|:---:|:---|
| `tests/Feature/TechnicianAssignmentWorkflowTest.php` | Feature / Integration Test | 62 Assertions (9 Tes) | Alur lengkap penugasan teknisi, accept, decline, expired auto-release, blocker SLA non-pausing, workload capacity constraint, submit & review, TLP RED 404 isolation, optimistic locking. |
| `tests/test_audit_fixes.php` | Security & Regression Audit Test | 44 Assertions | Pengamanan XSS inline handler, validasi NIP BKN, otentikasi PIN Bcrypt, token HMAC anti-IDOR, staging UUID upload isolation, port binding docker, DB triggers. |
| `tests/benchmark_routes.php` | Performance Benchmark | Multi-Role Routes | Pengukuran latensi dan jumlah kueri SQL pada rute Admin Pusat, Admin Bidang, dan Teknisi. |

---

### B. Prosedur Menjalankan Uji Otomatis via Terminal Docker

Seluruh pengujian dijalankan langsung di dalam kontainer `ticketing_app` untuk menjamin konsistensi lingkungan PostgreSQL dan PHP 8.2:

```bash
# 1. Jalankan Seluruh Rangkaian Fitur Alur Penugasan Teknisi (9 Tes, 62 Assertions)
docker exec ticketing_app ./vendor/bin/phpunit tests/Feature/TechnicianAssignmentWorkflowTest.php

# 2. Jalankan Pengujian Kepatuhan Audit Keamanan & Kualitas Sistem (44 Tes)
docker exec ticketing_app php tests/test_audit_fixes.php

# 3. Jalankan Pengujian Kecepatan & Kueri Database (Benchmark Latensi Rute)
docker exec ticketing_app php tests/benchmark_routes.php
```

**Kriteria Keberhasilan (*Expected Output*):**
- PHPUnit: `OK (9 tests, 62 assertions)` tanpa ada failure atau error.
- Audit Fixes: `HASIL AKHIR PENGUJIAN AUDIT: 44 PASSED, 0 FAILED`.
- Benchmark: Rute admin dan teknisi merespons dengan `Status: 200` dan latensi hangat di bawah **100 ms**.

---

### C. Lembar Panduan Uji Manual Skenario demi Skenario (Manual QA Checklist)

Gunakan daftar periksa manual berikut saat melakukan pengujian antarmuka peramban (*User Acceptance Testing* / UAT):

#### Skenario Uji 1: Happy Path Pengajuan s/d Penutupan Tiket
1. **Langkah 1 (Pelapor):** Buka portal publik `https://localhost:8443`. Isi NIP ASN valid (`198503122010011003`), nama pelapor, SKPD, nomor WhatsApp, pilih kategori "Jaringan Internet", ketik judul dan deskripsi, lalu unggah foto lampiran. Klik **Kirim Laporan**.
   - *Verifikasi:* Halaman sukses memuat Kode Tiket (misal `TIK-202610-0001`) dan PIN 6-digit. Tombol unduh struk berfungsi.
2. **Langkah 2 (Admin Pusat):** Login di `/login` dengan akun Admin Pusat (`superadmin`). Buka menu **Antrean Verifikasi**. Tiket baru tampil di daftar. Klik tiket, pilih TLP: `GREEN`, pilih Bidang: `Bidang Infrastruktur TIK`. Klik **Verifikasi & Disposisikan**.
   - *Verifikasi:* Tiket hilang dari antrean verifikasi, status di database menjadi `verified`, kolom `sla_dimulai_at` terisi jam saat ini.
3. **Langkah 3 (Admin Bidang):** Buka menu **Antrean Bidang**. Klik tombol **Tugaskan Teknisi** pada tiket. Pilih teknisi yang berstatus "Tersedia" sebagai Lead PIC. Klik **Simpan Penugasan**.
   - *Verifikasi:* Status tiket menjadi `in_progress`, terbit baris penugasan di tabel `ticket_assignments` dengan batas respon 60 menit.
4. **Langkah 4 (Teknisi):** Buka portal teknisi di tab baru dengan akun teknisi terpilih. Klik tab **Tugas Baru**. Klik tombol **Terima Tugas**.
   - *Verifikasi:* Tombol "Mulai Pengerjaan" aktif. Kontak pelapor kini dapat dibuka dan audit log mencatat `contact_viewed`.
5. **Langkah 5 (Teknisi):** Klik **Mulai Pengerjaan**. Ketik catatan worklog "Pengecekan kabel patch cord". Unggah foto bukti perbaikan di formulir penyelesaian, lalu klik **Ajukan Selesai**.
   - *Verifikasi:* Status penugasan menjadi `submitted`. Tiket tidak langsung ditutup oleh teknisi.
6. **Langkah 6 (Admin Bidang):** Admin Bidang membuka detail tiket di panel bidang. Muncul banner pengajuan review teknisi. Klik **Setujui Hasil Kerja**.
   - *Verifikasi:* Status tiket beralih ke `resolved`. Waktu kerja teknisi terkunci di `submitted_at`.
7. **Langkah 7 (Pelapor):** Pelapor membuka halaman `/lacak`, memasukkan kode tiket dan PIN. Muncul tombol **Konfirmasi Selesai & Beri Nilai**. Pelapor memilih 5 bintang dan mengetik ulasan "Layanan sangat cepat dan ramah". Klik **Kirim**.
   - *Verifikasi:* Status tiket beralih ke `closed`. Riwayat ulasan tampil di halaman riwayat kerja teknisi (`/teknisi/riwayat`).

---

#### Skenario Uji 2: Permintaan Kelengkapan Data (*Pending Info*)
1. Buka tiket berstatus `pending_verification` di panel Admin Pusat.
2. Klik tombol **Minta Kelengkapan Data**. Isi catatan: *"Foto bukti error blur, mohon unggah ulang tangkapan layar yang jelas"*. Klik kirim.
   - *Verifikasi:* Status tiket menjadi `pending_info`. Timer respon terhenti.
3. Buka halaman pelacakan pelapor `/tiket/{kode}` dengan PIN yang sesuai.
   - *Verifikasi:* Tampil kotak peringatan kuning bertuliskan instruksi kekurangan dari Admin Pusat dan tombol **Lengkapi Data**.
4. Klik tombol **Lengkapi Data**, unggah file tangkapan layar baru, dan tulis catatan tambahan *"Foto tangkapan layar telah diperbarui"*. Klik kirim.
   - *Verifikasi:* Status tiket otomatis kembali ke `pending_verification`, dan Admin Pusat dapat memverifikasi ulang tiket tersebut.

---

#### Skenario Uji 3: Penolakan Disposisi (*Salah Kamar & Redisposisi*)
1. Admin Pusat mendisposisikan tiket masalah jaringan ke Bidang Aplikasi.
2. Admin Bidang Aplikasi membuka antrean bidangnya, mendapati bahwa kendala tersebut bukan ranah tupoksinya.
3. Admin Bidang mengklik tombol **Kembalikan (Salah Kamar)**, memilih rekomendasi "Bidang Infrastruktur TIK", dan mengisi alasan *"Kendala fisik kabel jaringan LAN di kantor OPD, mohon dialihkan ke Bidang TIK"*. Klik kirim.
   - *Verifikasi:* Tiket kembali ke antrean `pending_verification` Admin Pusat. Timestamp `sla_dimulai_at` awal tetap utuh dan tidak ter-reset.
4. Admin Pusat mendisposisikan ulang tiket ke Bidang Infrastruktur TIK.

---

#### Skenario Uji 4: Reopen & Arbitrase Eskalasi Pusat (*Force Close*)
1. Pada tiket yang berstatus `resolved`, pelapor membuka pelacakan mandiri dan menekan tombol **Belum Selesai (Reopen)**.
2. Pelapor mengisi alasan kendala kambuh dan melampirkan foto bukti.
   - *Verifikasi:* Status tiket menjadi `reopened`, tercatat reopen ke-1 di antrean Admin Bidang.
3. Ulangi proses hingga Reopen ke-3.
   - *Verifikasi:* Sistem secara otomatis mengubah status tiket menjadi `eskalasi_pusat` dan mengalihkannya ke dashboard pimpinan Admin Pusat.
4. Admin Pusat membuka menu **Eskalasi Pusat** (`/admin/eskalasi`). Admin Pusat menekan tombol **Tutup Paksa (Force Close)** dengan alasan *"Setelah mediasi teknis, kerusakan terjadi pada switch pribadi OPD yang tidak masuk dalam kontrak pemeliharaan DKIS"*.
   - *Verifikasi:* Status tiket menjadi `force_closed`. Seluruh penugasan teknisi aktif otomatis dilepas (`released`), dan form tindakan kerja dikunci permanen.

---

#### Skenario Uji 5: Proteksi Keamanan Siber TLP: RED
1. Buat tiket dengan klasifikasi `TLP: RED` (Insiden Siber Persandian).
2. Login sebagai teknisi dari Bidang Non-Persandian (misal Teknisi Bidang Aplikasi).
3. Salin URL tugas tiket TLP RED tersebut dan coba buka di peramban.
   - *Verifikasi:* Server menolak akses dengan status **HTTP 404 Not Found** (bukan 403 Forbidden). Seluruh informasi penanganan siber terlindungi dari pengintaian pihak luar.

---

## 6. KESIMPULAN

Sistem Ticketing Layanan TIK DKIS Kota Cirebon telah menerapkan tata kelola penanganan aduan yang tertib, aman, terukur, dan akuntabel sesuai standar SPBE dan Gov-CSIRT. Dengan pemisahan peran yang tegas, perlindungan transaksi race-safe, penghitungan jam kerja teknisi yang adil, serta pengawasan SLA yang ketat, sistem ini menjamin kepastian pelayanan publik prima bagi seluruh ASN Pemerintah Kota Cirebon.
