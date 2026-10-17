# RINGKASAN EKSEKUTIF AUDIT DAN PENYEMPURNAAN SISTEM TICKETING DKIS

**Tanggal Laporan:** 17 Oktober 2026  
**Penyusun:** Senior Laravel Engineer & QA Security Reviewer  
**Target Pembaca:** Pimpinan, Kepala Bidang, Pengelola Sistem, dan Tim Teknis DKIS Kota Cirebon  
**Branch Kode:** `audit/perbaikan-20261017`  
**Dokumen Induk:** `audit sistem.md`

---

## 1. Ringkasan Eksekutif 5 Baris
1. Audit komprehensif terhadap arsitektur backend, logika bisnis, keamanan publik, modul penugasan teknisi, dan antarmuka pengguna telah selesai dilaksanakan secara tuntas.
2. Seluruh kerentanan keamanan berisiko kritis (kebocoran dokumen tanpa PIN, ketiadaan pembatasan laju akses, dan enkripsi PIN warisan) telah diperbaiki dan diverifikasi dengan 25 automated feature tests (100% Pass).
3. Tata kelola alur penanganan tiket dan SLA telah diperkuat: waktu SLA awal dipertahankan saat penugasan ulang, pembagian SLA laporan dibersihkan dari tiket batal, dan insiden keamanan siber (TLP RED) diisolasi ke kanal siaran khusus.
4. Permintaan khusus pemindahan menu navigasi teknisi pada perangkat HP telah diimplementasikan di bagian atas layar (sticky sub-header) dengan target sentuh ergonomis.
5. Sistem kini berada dalam kondisi stabil, aman, dan siap untuk ditinjau oleh pemangku kepentingan (*stakeholders*) sebelum penggabungan ke branch utama.

---

## 2. Perbandingan Kondisi Sistem: Sebelum vs Sesudah Audit

| Parameter Evaluasi | Kondisi Awal (Sebelum Audit) | Kondisi Terkini (Sesudah Perbaikan) |
| :--- | :--- | :--- |
| **Keamanan Unduh Berkas** | Tautan unduh lampiran mencetak token permanen (`auth_token`) di HTML publik sehingga berkas dapat diunduh tanpa memasukkan PIN. | Parameter token dihapus dari HTML; akses unduh berkas wajib lolos validasi sesi PIN pelapor sah (`TicketAuthService`). |
| **Privasi Lacak Tiket** | Siapapun yang mengetahui nomor tiket berurutan dapat membaca judul dan deskripsi lengkap keluhan pelapor tanpa pembatasan frekuensi akses. | Dilengkapi rate limiter `throttle:30,1`; isi keluhan disamarkan (*masked*) sampai pelapor memasukkan PIN keamanan resmi. |
| **Enkripsi PIN di Database** | Sebanyak 61 tiket lama (*legacy*) menyimpan PIN dalam format teks polos (*plaintext*) 6 karakter. | Seluruh 61 tiket telah dimigrasikan dan dienkripsi menjadi hash Bcrypt standar industri (`length(pin) >= 60`). |
| **Konsistensi Penghitungan SLA** | Jam awal SLA ter-reset kembali ke 0 saat tiket dikembalikan dari bidang ke pusat; pembagi laporan kepatuhan mendongkrak tiket pending/batal. | Waktu awal SLA dipertahankan (*retained*) saat disposisi ulang; rumus kepatuhan laporan hanya menghitung tiket relevan dan mendukung filter tanggal. |
| **Pemisahan Insiden Siber (TLP RED)** | Tiket TLP RED memenuhi antrean kerja Admin Bidang non-Persandian meskipun mereka dilarang mengambil tindakan teknis. | Disediakan tab terpisah **"Siaran Insiden Siber"** dengan badge notifikasi merah, menjaga antrean harian bidang lain tetap fokus dan bersih. |
| **Otomasi Scheduler di Docker** | Tidak ada layanan background scheduler yang berjalan otomatis pada file `docker-compose.yml`. | Ditambahkan container `ticketing_scheduler` yang secara berkala menjalankan pengecekan SLA breach dan pelepasan penugasan kedaluwarsa. |
| **Kanal Notifikasi Gateway** | Job pengiriman notifikasi hanya mencatat log database tanpa arsitektur antarmuka pengiriman HTTP client ke provider WA. | Dibangun arsitektur layanan `WhatsAppServiceInterface` yang siap beralih fleksibel antara mode log development dan HTTP live gateway. |
| **Tampilan Navigasi Teknisi (Mobile)** | Menu navigasi teknisi berada di floating bottom bar yang rentan menutupi tombol simpan dan navigasi ponsel. | Menu navigasi teknisi dipindahkan ke bagian atas (sub-header sticky bar di bawah topbar) dengan indikator aktif yang bersih dan nyaman. |

---

## 3. Matriks Status Temuan Audit

| Tingkat Keparahan | Total Temuan | Selesai-Terverifikasi | Selesai Sesuai Keputusan Gate 1 | Ditunda (Menunggu Kebijakan) |
| :--- | :---: | :---: | :---: | :---: |
| 🔴 **Kritis (Critical)** | **2** | 2 | 0 | 0 |
| 🟠 **Tinggi (High)** | **5** | 3 | 1 (FLOW-01) | 1 (SEC-03 - Superuser DB Prod) |
| 🟡 **Sedang (Medium)** | **7** | 7 | 0 | 0 |
| 🔵 **Rendah (Low)** | **3** | 2 | 0 | 1 (PERF-03 - Master OPD) |
| **TOTAL** | **17** | **14** | **1** | **2** |

---

## 4. Risiko yang Masih Tersisa
1. **Kredensial Superuser pada Server Produksi (SEC-03)**:
   - *Status Saat Ini:* Pada environment lokal/docker development, database masih menggunakan role `postgres`.
   - *Mitigasi:* Akses basis data dibatasi hanya di dalam internal docker network (tidak terpapar port publik). Pembuatan user non-superuser `ticketing_user` telah disiapkan pada dokumentasi rekomendasi untuk dieksekusi saat deployment server produksi.
2. **Standardisasi Penamaan SKPD / OPD (PERF-03)**:
   - *Status Saat Ini:* Nama SKPD masih disimpan dalam format teks bebas (contoh: "Dinas Kesehatan", "Dinkes").
   - *Mitigasi:* Telah diusulkan penyusunan tabel master OPD pada sprint berikutnya agar pelapor memilih instansi dari daftar baku terverifikasi.

---

## 5. Keputusan yang Menunggu Arahan Pimpinan
1. **Pengadaan Akun / Kuota Resmi WhatsApp Business Gateway**:
   - Untuk mengaktifkan pengiriman pesan real-time ke ponsel ASN, dibutuhkan pemilihan provider (misal Fonnte/Wablas) dan pengisian token API pada konfigurasi `.env`.
2. **Persetujuan Penggabungan Branch (Merge ke Main)**:
   - Seluruh pengujian otomatis telah lulus 100%. Pimpinan/tim teknis dapat melakukan *review* akhir terhadap perubahan pada branch `audit/perbaikan-20261017` sebelum di-merge ke branch utama.

---

## 6. Langkah Selanjutnya
1. Uji coba langsung (*user acceptance testing*) oleh perwakilan Admin Pusat, Admin Bidang, dan Teknisi Lapangan menggunakan akun demonstrasi yang tersedia.
2. Deployment perubahan ke server pementasan (*staging environment*).
3. Penggabungan branch `audit/perbaikan-20261017` ke branch utama (`main` / `master`).
4. Pelaksanaan rekomendasi Fase Cepat (Otomasi CI/CD Runner dan pengetatan user database produksi).

---

## 7. Hal yang Tidak Bisa Diverifikasi
1. **Koneksi Live WhatsApp Gateway Pihak Ketiga**: Lingkungan pengujian menggunakan driver simulasi (`WA_DRIVER=log`) karena belum ada token API komersial aktif.
2. **Infrastruktur Database Server Produksi**: Audit dan perbaikan difokuskan pada basis kode dan kontainer Docker lokal; penyesuaian hak akses server fisik produksi mengikuti jadwal pemeliharaan DevOps DKIS.
