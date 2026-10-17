# CATATAN PERUBAHAN SISTEM TICKETING DKIS (FASE 3)
**Tanggal:** 17 Oktober 2026  
**Branch:** `audit/perbaikan-20261017`  
**Dokumen Acuan:** `docs/audit/02-RENCANA.md`

---

## Log Perubahan Terlihat Pengguna (User-Facing Changes)

| ID Temuan | Komponen / Halaman | Apa yang Berubah | Mengapa Diubah | Dampak ke Pengguna | Cara Rollback |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **UI-01** | Antarmuka Teknisi (Mobile) | Bilah menu navigasi mobile dipindahkan dari bawah layar ke bagian atas (tepat di bawah header). | Permintaan pengguna untuk kenyamanan navigasi satu tangan dan konsistensi tata letak mobile modern. | Teknisi mengakses Tugas, Riwayat, Notifikasi, dan Profil di bilah atas layar. | Kembalikan `.nav` ke posisi fixed bottom di `resources/views/teknisi/layout.blade.php`. |

---
