# CATATAN PERUBAHAN SISTEM TICKETING DKIS (FASE 3)
**Tanggal:** 17 Oktober 2026  
**Branch:** `audit/perbaikan-20261017`  
**Dokumen Acuan:** `docs/audit/02-RENCANA.md`

---

## Log Perubahan Terlihat Pengguna (User-Facing Changes)

| ID Temuan | Komponen / Halaman | Apa yang Berubah | Mengapa Diubah | Dampak ke Pengguna | Cara Rollback |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **UI-01** | Antarmuka Teknisi (Mobile) | Bilah menu navigasi mobile dipindahkan dari bawah layar ke bagian atas (tepat di bawah header). | Permintaan pengguna untuk kenyamanan navigasi satu tangan dan konsistensi tata letak mobile modern. | Teknisi mengakses Tugas, Riwayat, Notifikasi, dan Profil di bilah atas layar. | Kembalikan `.nav` ke posisi fixed bottom di `resources/views/teknisi/layout.blade.php`. |
| **SEC-01** | Halaman Lacak Publik & Unduh Berkas | Tautan berkas lampiran tidak lagi menyertakan `auth_token` di HTML; unduh berkas mewajibkan sesi verifikasi PIN yang sah. | Mencegah pihak ketiga tanpa hak yang mengetahui nomor tiket mengunduh berkas privat pelapor. | Pelapor wajib memasukkan PIN 6-digit tiket sebelum dapat mengunduh dokumen/bukti. | Revert perubahan di `TicketFileController.php` dan `public/index.blade.php`. |
| **SEC-02** | Halaman Lacak Publik | Ditambahkan pembatas request (*Rate Limiting* 30 req/menit) dan penyensoran (*masking*) deskripsi laporan sebelum PIN dimasukkan. | Mencegah bot/scraper melakukan serangan enumerasi dan membaca keluhan kedinasan ASN Pemkot. | Pelapor melihat ringkasan tersensor dan form PIN untuk membuka rincian lengkap. | Hapus middleware throttle pada route dan sensor teks di Blade. |
| **SEC-04** | Modul Berkas & Audit Log | Setiap aksi pengunduhan berkas (admin maupun publik) dicatat ke `audit_log` beserta IP dan identitas pengunduh. | Memenuhi standar kepatuhan jejak rekam digital (*forensic digital audit trail*). | Transparan di sisi audit sistem internal tanpa mengganggu pengalaman unduh berkas. | Revert pencatatan `AuditLog::create()` di `TicketFileController.php`. |

---
