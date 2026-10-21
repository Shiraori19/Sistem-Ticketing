# RINGKASAN EKSEKUTIF AUDIT DAN PENYEMPURNAAN SISTEM TICKETING DKIS

**Tanggal Laporan:** 21 Oktober 2026 (Penyempurnaan Keamanan Berkas, Vault Storage, Multi-Admin Pusat & TLP RED)  
**Penyusun:** Senior Laravel Engineer & QA Security Reviewer  
**Target Pembaca:** Pimpinan, Kepala Bidang, Pengelola Sistem, dan Tim Teknis DKIS Kota Cirebon  
**Branch Kode:** `revisi/upload-tlp-20261021`  
**Dokumen Induk:** `audit sistem.md` & `revisi.md`

---

## 1. Ringkasan Eksekutif 5 Baris
1. Audit komprehensif dan implementasi perbaikan mendalam terhadap arsitektur backend, keamanan berkas, enkripsi arsip rahasia (Vault), alur penanganan insiden siber TLP RED, dan operasional multi-admin telah diselesaikan secara tuntas.
2. Seluruh kerentanan keamanan berisiko kritis (kebocoran dokumen tanpa PIN, ketiadaan rate limit, manipulasi ekstensi/disguised files, EXIF GPS leakage, dan bypass TLP RED) telah ditutup dan diverifikasi dengan 45 automated feature tests (100% Pass, 183 asersi).
3. Tata kelola alur kerja verifikasi kini mendukung dua atau lebih Admin Pusat secara paralel dengan mekanisme soft-claim 10 menit dan optimistic locking (`row_version`), mencegah terjadinya tabrakan verifikasi tiket serentak.
4. Sistem pratinjau derivatif aman (`previews` disk) menyajikan tangkapan layar, thumbnail, video, dan log tanpa mengekspos berkas mentah ke peramban, dilengkapi penandaan watermark kedinasan otomatis untuk berkas rahasia.
5. Sistem kini berada dalam kondisi sangat prima, stabil, aman, dan siap untuk ditinjau oleh pemangku kepentingan (*stakeholders*) sebelum penggabungan ke branch utama.

---

## 2. Perbandingan Kondisi Sistem: Sebelum vs Sesudah Audit & Revisi

| Parameter Evaluasi | Kondisi Awal (Sebelum Audit) | Kondisi Terkini (Sesudah Perbaikan & Revisi) |
| :--- | :--- | :--- |
| **Keamanan Unduh Berkas** | Tautan unduh lampiran mencetak token permanen (`auth_token`) di HTML publik sehingga berkas dapat diunduh tanpa memasukkan PIN. | Parameter token dihapus dari HTML; akses unduh berkas wajib lolos validasi sesi PIN pelapor sah (`TicketAuthService`). |
| **Validasi Berkas Unggahan** | Hanya memeriksa ekstensi/MIME klien dasar. Format Word berbahaya (.doc/.docx) diterima. Berkas teks bisa disamarkan menjadi `.jpg`. | Pintu tunggal `SecureUploadService`: verifikasi MIME finfo + Magic Bytes biner + sanitasi path traversal + penolakan mutlak format `.doc`/`.docx`. |
| **Penyimpanan Berkas Rahasia / TLP RED** | Disimpan di disk `private` biasa tanpa enkripsi at-rest. Semua admin bidang bisa melihat file. | Diisolasi di disk `vault` (`0600`/`0700`), terenkripsi otomatis AES-256-CBC. Hanya Admin Persandian & teknisi yang ditugaskan yang dapat membuka. Admin Pusat wajib melalui form "Minta Akses (Justifikasi Dinas)". |
| **Pratinjau Bukti (Preview)** | Mengharuskan pengunduhan berkas fisik ke komputer pengguna untuk melihat bukti. | Pratinjau derivatif aman tanpa unduh (WebP/JPEG 1600px, thumbnail 240px, pemotongan log 200KB, watermark kedinasan otomatis pada berkas rahasia). |
| **Operasional Multi-Admin Pusat** | Hanya dirancang untuk 1 admin. Jika 2 admin bekerja bersamaan, terjadi tabrakan verifikasi dan duplikasi penanganan tiket. | Soft-claim atomik 10 menit ("Ambil Tiket Berikutnya") + Optimistic locking `row_version` (HTTP 409) + Proteksi Admin Pusat terakhir aktif. |
| **Pemisahan Insiden Siber (TLP RED)** | Tiket TLP RED disiarkan (*broadcast*) ke semua bidang dan memenuhi antrean mereka. | Broadcast dihapus. Diganti tabel keterlibatan eksplisit `ticket_bidang_involvement`. Bidang non-pelibat tidak melihat tiket RED; bidang ditembuskan hanya melihat koordinasi tanpa berkas rahasia. |
| **Kompresi Gambar di Klien** | Gambar beresolusi kamera ponsel besar dikirim mentah ke server, membebani bandwidth dan disk. | Skrip HTML5 Canvas `secure-client-upload.js` mengompresi gambar adaptif ~1MB (max 1920px) dan membuang metadata EXIF/GPS sebelum dikirim. Tersedia opsi raw upload untuk laporan siber. |
| **Enkripsi PIN di Database** | Sebanyak 61 tiket lama (*legacy*) menyimpan PIN dalam format teks polos (*plaintext*) 6 karakter. | Seluruh 61 tiket telah dimigrasikan dan dienkripsi menjadi hash Bcrypt standar industri (`length(pin) >= 60`). |
| **Jejak Kustodi Berkas** | Tidak ada sidik jari hash pada berkas. Pengunduhan dan penayangan tidak dicatat ke audit log. | Setiap berkas bersih dihitung hash SHA-256 saat disimpan. Aksi unduh dan pratinjau dicatat ke `audit_log` dengan mekanisme debounce 5 menit. |

---

## 3. Matriks Status Temuan Audit & Peningkatan Sistem

| Tingkat Keparahan | Total Temuan / Modul | Selesai-Terverifikasi | Selesai Sesuai Keputusan Gate | Ditunda (Menunggu Kebijakan) |
| :--- | :---: | :---: | :---: | :---: |
| 🔴 **Kritis (Critical)** | **5** | 5 | 0 | 0 |
| 🟠 **Tinggi (High)** | **8** | 6 | 1 (FLOW-01) | 1 (SEC-03 - Superuser DB Prod) |
| 🟡 **Sedang (Medium)** | **8** | 8 | 0 | 0 |
| 🔵 **Rendah (Low)** | **3** | 2 | 0 | 1 (PERF-03 - Master OPD) |
| **TOTAL** | **24** | **21** | **1** | **2** |

---

## 4. Risiko yang Masih Tersisa
1. **Kredensial Superuser pada Server Produksi (SEC-03)**:
   - *Status Saat Ini:* Pada environment lokal/docker development, database masih menggunakan role `postgres`.
   - *Mitigasi:* Akses basis data dibatasi hanya di dalam internal docker network (tidak terpapar port publik). Pembuatan user non-superuser `ticketing_user` telah disiapkan pada dokumentasi rekomendasi untuk dieksekusi saat deployment server produksi.
2. **Kapasitas Penyimpanan Storage Server dalam Jangka Panjang**:
   - *Status Saat Ini:* Akumulasi berkas derivatif pratinjau pada disk `previews` dan arsip `vault` akan bertambah seiring peningkatan laporan.
   - *Mitigasi:* Disediakan artisan command `php artisan previews:rebuild` dan kebijakan retensi terpusat di `config/files.php`.
3. **Ketergantungan Engine Daemon ClamAV**:
   - *Status Saat Ini:* Diuji menggunakan `NullVirusScanner` di kontainer lokal.
   - *Mitigasi:* Kontrak interface `VirusScannerInterface` dan driver `ClamAvVirusScanner` telah siap pakai ketika kontainer `clamav` dipasang pada docker produksi.

---

## 5. Keputusan yang Menunggu Arahan Pimpinan
1. **Pengadaan Akun / Kuota Resmi WhatsApp Business Gateway**:
   - Untuk mengaktifkan pengiriman pesan real-time ke ponsel ASN, dibutuhkan pemilihan provider (misal Fonnte/Wablas) dan pengisian token API pada konfigurasi `.env`.
2. **Persetujuan Penggabungan Branch (Merge ke Main)**:
   - Seluruh pengujian otomatis telah lulus 100% (45 passed, 183 assertions). Pimpinan/tim teknis dapat melakukan *review* akhir terhadap perubahan pada branch `revisi/upload-tlp-20261021` sebelum di-merge ke branch utama.

---

## 6. Langkah Selanjutnya
1. Uji coba langsung (*user acceptance testing*) oleh perwakilan Admin Pusat, Admin Bidang Persandian, Admin Bidang ITIK, dan Teknisi Lapangan.
2. Deployment perubahan ke server pementasan (*staging environment*).
3. Penggabungan branch `revisi/upload-tlp-20261021` ke branch utama (`main`).

---

## 7. Hal yang Tidak Bisa Diverifikasi
1. **Koneksi Live WhatsApp Gateway Pihak Ketiga**: Lingkungan pengujian menggunakan driver simulasi (`WA_DRIVER=log`) karena belum ada token API komersial aktif.
2. **Infrastruktur Database Server Produksi**: Audit dan perbaikan difokuskan pada basis kode dan kontainer Docker lokal; penyesuaian hak akses server fisik produksi mengikuti jadwal pemeliharaan DevOps DKIS.
3. **Daemon ClamAV Produksi**: Diuji menggunakan `NullVirusScanner` karena daemon ClamAV aktif (`clamd`) membutuhkan kontainer ClamAV mandiri atau alokasi RAM server minimal 2-4 GB untuk database signature virus.
