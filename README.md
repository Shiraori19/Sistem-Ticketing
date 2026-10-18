# Sistem Helpdesk & Ticketing Layanan TIK DKIS Kota Cirebon

Sistem Informasi Manajemen Permohonan Layanan TIK, Pemeliharaan Infrastruktur Jaringan, dan Penanganan Insiden Keamanan Siber Terpadu di lingkungan Pemerintah Kota Cirebon berbasis standar Sistem Pemerintahan Berbasis Elektronik (SPBE).

---

## 1. Gambaran Umum Sistem (What is this project?)

Sistem Ticketing DKIS Kota Cirebon adalah aplikasi web helpdesk terpusat yang dibangun untuk melayani kebutuhan bantuan teknologi informasi bagi seluruh Aparatur Sipil Negara (ASN) di Organisasi Perangkat Daerah (OPD) Pemerintah Kota Cirebon.

Aplikasi ini mengintegrasikan seluruh tahapan penanganan keluhan teknis—mulai dari permohonan awal oleh ASN, verifikasi dan penentuan prioritas oleh Admin Pusat, koordinasi disposisi oleh Admin Bidang, hingga pengerjaan langsung di lapangan oleh Teknisi.

---

## 2. Latar Belakang & Tujuan (Why was it built?)

Pengelolaan layanan TIK di instansi pemerintahan sering kali menghadapi berbagai kendala operasional apabila dilakukan secara manual atau terfragmentasi. Sistem ini dibangun untuk menjawab tantangan-tantangan berikut:

1. **Sentralisasi Aduan Layanan:** Menggantikan permohonan manual via pesan singkat pribadi (WhatsApp/telepon) yang rentan tercecer, tidak terdokumentasi, dan sulit dipantau progresnya.
2. **Aksesibilitas Tanpa Beban Akun (Zero-Friction Access):** Memberikan kemudahan bagi ASN pemohon untuk mengajukan kendala cukup dengan memvalidasi **NIP ASN** dan **Nomor WhatsApp**, tanpa proses registrasi akun baru yang rumit.
3. **Kepastian Waktu Layanan (Service Level Agreement):** Menghadirkan penghitungan mundur target waktu respon dan resolusi secara otomatis berdasarkan 13 kategori standar layanan, sehingga pelayanan memiliki batas waktu penyelesaian yang terukur.
4. **Distribusi Beban Kerja Teknisi yang Adil (Workload Balancing):** Mengatur penugasan teknisi secara terkontrol dengan pembatasan kuota beban kerja aktif (maksimal 6 poin beban per teknisi) berdasarkan bobot tingkat kesulitan kendala.
5. **Perlindungan Insiden Keamanan Siber (CSIRT):** Memisahkan penanganan insiden siber kritis (TLP: RED) secara eksklusif kepada tim Persandian & CSIRT untuk menjaga kerahasiaan data pemerintah daerah.
6. **Kepastian Penyelesaian Tiket (Anti-Deadlock):** Menyediakan mekanisme eskalasi dan hak penutupan resmi (*Force Close*) oleh pimpinan pusat jika terjadi ketidaksepakatan atau kendala di luar kewenangan dinas.

---

## 3. Modul & Peran Pengguna

Sistem ini membagi hak akses ke dalam 4 modul peran (*Role-Based Access Control*):

### A. Portal Pelapor (ASN Pemohon Layanan)
* **Pengajuan Mandiri:** Mengisi form pengaduan terverifikasi dengan data NIP, OPD, kategori kendala, deskripsi, dan lampiran berkas pendukung.
* **Autentikasi PIN 6 Digit:** Menerima Kode Tiket dan PIN Keamanan mentah saat pengajuan, yang di-hash Bcrypt di database untuk menjamin privasi pelacakan status dan berkas tiket.
* **Pelacakan Transparan:** Memantau status pengerjaan, membaca worklog teknisi, menambahkan info susulan jika diminta, serta memberikan rating kepuasan (1–5 bintang) saat tiket selesai.

### B. Panel Admin Pusat (Verifikator Utama DKIS)
* **Verifikasi & Triage Masuk:** Memeriksa kelayakan setiap permohonan baru dari antrean `pending_verification`.
* **Klasifikasi Kerahasiaan (TLP):** Menetapkan tingkat kerahasiaan informasi (*Traffic Light Protocol*).
* **Disposisi Bidang:** Mendisposisikan tiket ke bidang pengampu teknis yang tepat (Infrastruktur TIK, Aplikasi Digital, atau Persandian/CSIRT), yang secara otomatis memulai penghitungan jam kerja resmi SLA.
* **Mediasi & Hak Veto Force Close:** Menyelesaikan tiket eskalasi dan menutup secara permanen tiket yang berstatus sengketa atau kendala perangkat non-dinas.

### C. Panel Admin Bidang (Koordinator Tim Teknis)
* **Manajemen Antrean Bidang:** Memantau seluruh tiket yang dialokasikan ke bidangnya.
* **Penugasan Teknisi:** Menunjuk teknisi koordinator (*Lead PIC*) dan tim pendamping (*Members*) dari daftar personil aktif.
* **Pengawasan Beban Kerja & Batas Waktu:** Memantau kuota beban kerja staf serta memastikan teknisi merespon penugasan sebelum batas waktu konfirmasi kedaluwarsa.
* **Tindak Lanjut Hambatan:** Menerima notifikasi dan memfasilitasi kendala lapangan (*blocker*) yang dihadapi teknisi.

### D. Portal Teknisi Lapangan (Pelaksana Teknis)
* **Respon Cepat Tugas:** Menerima (*Accept*) atau menolak (*Decline* dengan alasan formal) tugas yang diberikan.
* **Pencatatan Progres Lapangan (Worklog):** Mencatat setiap tindakan penanganan fisik/sistem dan mengunggah berkas progres.
* **Pelaporan Hambatan Lapangan (Blocker):** Melaporkan ketergantungan pihak eksternal (ISP, vendor aplikasi, pengadaan suku cadang, atau koordinasi antar-OPD) tanpa memanipulasi waktu SLA.
* **Penyelesaian Pekerjaan:** Mengunggah foto bukti fisik perbaikan dan catatan solusi, yang langsung mengalihkan tiket ke status *Resolved* untuk dikonfirmasi oleh pelapor.

---

## 4. Alur Kerja & Siklus Hidup Tiket (Workflow Lifecycle)

```
[ Pelapor ASN ] 
       │ 
       ▼
 1. Pengajuan Aduan (NIP, OPD, WhatsApp, Rincian, Lampiran)
       │
       ▼
 2. Generate Kode Tiket & PIN Keamanan 6 Digit
       │
       ▼
[ Admin Pusat ] ──(Verifikasi Berkas & Triage)──> [ Minta Info Susulan jika Kurang ]
       │
       ├─► Jika Kasus Kritis Siber (TLP: RED) ──> Khusus Tim CSIRT / Persandian
       │
       ▼
 3. Disposisi ke Bidang Pengampu (SLA Resmi Dimulai)
       │
       ▼
[ Admin Bidang ] ──(Pengecekan Kuota Beban Kerja)──> [ Penunjukan Lead PIC & Tim ]
       │
       ▼
[ Teknisi Lapangan ]
       │
       ├─► Terima Tugas (Status: in_progress)
       ├─► Catat Worklog & Lapor Hambatan Eksternal (Blocker)
       └─► Selesaikan Perbaikan & Lampirkan Bukti Foto (Status: resolved)
       │
       ▼
[ Pelapor ASN ]
       │
       ├─► Konfirmasi Selesai & Beri Rating Bintang 1-5 ──> [ Status: closed ]
       │
       └─► Jika Masalah Belum Tuntas (Reopen Maks. 2x) ──> [ Kembali ke Penanganan ]
                 │
                 └─► Reopen ke-3 (Deadlock) ──> [ Eskalasi ke Admin Pusat ]
                                                         │
                                                         ▼
                                            [ Audit & Veto Force Close ]
```

---

## 5. Kebijakan & Aturan Bisnis Sistem

1. **Target Service Level Agreement (SLA):**
   * Mengadopsi 13 kategori layanan resmi DKIS mencakup gangguan jaringan, aplikasi, server hosting, sertifikat elektronik (TTE BSrE), domain SSL, dan penanganan insiden siber.
   * Waktu awal perhitungan SLA (`sla_dimulai_at`) dipreservasi saat redisposisi antar-bidang untuk mencegah manipulasi kepatuhan.
2. **Traffic Light Protocol (TLP):**
   * **TLP: RED:** Informasi insiden siber sangat rahasia. Akses teknisi non-Persandian diblokir total dengan respon **HTTP 404 (Not Found)**.
   * **TLP: AMBER:** Informasi sensitif terbatas untuk pihak berkepentingan (pelapor dan tim pengampu).
   * **TLP: GREEN:** Informasi operasional normal antar-OPD.
   * **TLP: CLEAR:** Informasi permohonan umum non-sensitif.
3. **Integritas Audit Trail & Pencegahan Konflik:**
   * Riwayat aktivitas (`ticket_events` dan `audit_logs`) dilindungi oleh **Trigger PostgreSQL append-only** yang menolak operasi penghapusan atau pengubahan riwayat.
   * Menerapkan **Optimistic Locking** (`row_version`) untuk mencegah benturan data saat dua petugas mengakses tugas yang sama secara bersamaan (menghasilkan HTTP 409 Conflict).
4. **Pelepasan Otomatis Penugasan Kedaluwarsa:**
   * Command Scheduler secara otomatis melepaskan (*released*) penugasan jika teknisi tidak merespon konfirmasi tugas dalam batas waktu (15 menit untuk TLP RED, 60 menit untuk tiket reguler), sehingga tiket tidak terbengkalai.
5. **Sinkronisasi Force Close:**
   * Saat Admin Pusat menutup paksa (*Force Close*) tiket di status eskalasi, sistem secara otomatis melepaskan seluruh penugasan teknisi aktif dan menghentikan seluruh form aksi kerja.

---

## 6. Arsitektur & Teknologi

* **Bahasa & Framework:** PHP 8.2, Laravel 10.x
* **Basis Data:** PostgreSQL 15 (JSONB support untuk audit ledger)
* **Web Server:** Nginx Alpine (Reverse Proxy, HTTP/2, SSL Termination)
* **Frontend:** Laravel Blade, Vanilla CSS Modern, Tailwind CSS, Alpine.js, Chart.js
* **Asynchronous Queue & Scheduler:** Database Queue Worker (`ticketing_queue`) & Command Scheduler (`ticketing_scheduler`)
* **Kontainerisasi:** Docker & Docker Compose

---

## 7. Panduan Menjalankan Sistem

### Prasyarat
* Docker & Docker Compose terpasang pada sistem operasi Anda.
* Git.

### Langkah Menjalankan
1. **Kloning Repositori:**
   ```bash
   git clone https://github.com/Shiraori19/Sistem-Ticketing.git
   cd Sistem-Ticketing
   ```

2. **Salin Konfigurasi Environment:**
   ```bash
   cp .env.example .env
   ```

3. **Jalankan Kontainer Docker:**
   ```bash
   docker compose up -d
   ```
   Langkah ini akan menyalakan kontainer web server (Nginx), aplikasi (PHP-FPM), basis data (PostgreSQL), antrean antrean background (Queue Worker), dan penjadwal tugas (Scheduler).

4. **Inisialisasi Database:**
   ```bash
   docker exec -it ticketing_app php artisan migrate:fresh --seed
   ```

5. **Akses Sistem:**
   * Portal Publik (Pelapor): `https://localhost:8443` (atau `http://localhost`)
   * Panel Masuk Petugas: `https://localhost:8443/login`

6. **Menjalankan Pengujian Otomatis:**
   ```bash
   docker exec -it ticketing_app php artisan test
   ```

---

## 8. Lisensi & Hak Cipta

Hak Cipta © 2026 **Dinas Komunikasi, Informatika, dan Statistik (DKIS) Pemerintah Kota Cirebon**.  
Seluruh hak cipta dilindungi undang-undang. Dikembangkan untuk mendukung tata kelola teknologi informasi pemerintahan yang transparan, akuntabel, dan berorientasi pada pelayanan prima.
