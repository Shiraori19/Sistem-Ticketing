# 🏛️ Sistem Helpdesk & Ticketing Layanan TIK DKIS Kota Cirebon
> **Platform Manajemen Permohonan Layanan TIK, Pemeliharaan Infrastruktur, dan Penanganan Insiden Keamanan Siber Terpadu Berbasis Standar SPBE.**

[![Laravel](https://img.shields.io/badge/Laravel-10.x-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-15-4169E1?style=flat-square&logo=postgresql&logoColor=white)](https://www.postgresql.org/)
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?style=flat-square&logo=docker&logoColor=white)](https://www.docker.com/)
[![Tests](https://img.shields.io/badge/Tests-26_Passed_(100%25)-059669?style=flat-square)](#-pengujian-sistem)
[![Status](https://img.shields.io/badge/Status-Production_Ready-blue?style=flat-square)](#)

---

## 📌 Apa Itu Proyek Ini?

**Sistem Ticketing DKIS Kota Cirebon** adalah platform web helpdesk resmi yang dirancang untuk mengelola seluruh siklus hidup permohonan layanan teknologi informasi, perbaikan infrastruktur jaringan, serta penanganan insiden keamanan siber bagi **seluruh Aparatur Sipil Negara (ASN) di lingkungan Pemerintah Kota Cirebon**.

Sistem ini menghubungkan **Pelapor (ASN OPD)**, **Admin Pusat (Verifikator DKIS)**, **Admin Bidang (Koordinator Tim Teknis)**, dan **Teknisi Lapangan** dalam satu ekosistem kerja terpadu yang transparan, terukur, dan akuntabel.

---

## 💡 Mengapa Proyek Ini Dibuat?

Sebelum sistem ini dibangun, pengelolaan permohonan bantuan teknis di lingkungan pemerintah daerah menghadapi beberapa kendala nyata:

| Tantangan Sebelumnya | Solusi Melalui Sistem Ini |
| :--- | :--- |
| **Aduan Tercecer & Tidak Tercatat:** Permohonan via WhatsApp pribadi/telepon sering hilang dan tidak memiliki bukti riwayat. | **Form Pengaduan Terverifikasi:** Seluruh kendala tercatat resmi dengan kode tiket unik dan nomor tiket referensi. |
| **Birokrasi Registrasi yang Berbelit:** ASN enggan melapor karena malas membuat akun baru atau lupa kata sandi. | **Zero-Friction Access:** ASN cukup memasukkan **NIP** dan **No. WhatsApp** tanpa perlu registrasi akun manual. |
| **Ketidakpastian Waktu Pengerjaan (Tanpa SLA):** Pelapor tidak tahu kapan perbaikan akan selesai dikerjakan. | **SLA Otomatis (13 Kategori):** Target waktu respon & resolusi dihitung mundur secara *real-time* dengan visualisasi transparan. |
| **Penumpukan Beban Kerja Teknisi:** Penugasan manual sering membebani beberapa teknisi tertentu saja. | **Workload Balancing:** Sistem membatasi beban aktif maksimal 6 poin per teknisi dengan bobot sesuai tingkat keparahan kendala. |
| **Insiden Siber Bocor ke Publik:** Laporan kebocoran data atau defacement server berisiko tersebar luas. | **Isolasi Keamanan TLP: RED:** Tiket insiden kritis siber otomatis diisolasi eksklusif hanya untuk tim CSIRT/Persandian (akses teknisi lain diblokir 404). |
| **Sengketa Perbaikan (Deadlock):** Tidak ada mekanisme penengah jika hasil perbaikan teknisi dan pelapor tidak sepakat. | **Hak Veto Force Close:** Admin Pusat dapat menengahi dan menutup tiket secara permanen (*force closed*) berbasis audit teknis. |

---

## ✨ Fitur-Fitur Utama

* 📱 **Pelacakan Tiket Terlindungi PIN:** Pelapor melacak progres perbaikan menggunakan Kode Tiket dan PIN Keamanan 6 Digit (di-hash Bcrypt).
* ⏱️ **Countdown SLA & Indikator Kepatuhan:** Menghitung waktu respon dan resolusi secara presisi; jam pengerjaan tidak dikurangi durasi verifikasi berkas.
* 🛠️ **Portal Mandiri Teknisi Lapangan:** Antarmuka responsif bagi teknisi untuk menerima/menolak tugas, mencatat worklog progres, melaporkan hambatan (*blocker: ISP/vendor/suku cadang*), dan mengirim foto bukti selesai perbaikan.
* 🚦 **Klasifikasi Traffic Light Protocol (TLP):** Standardisasi penanganan informasi: **TLP: CLEAR**, **TLP: GREEN**, **TLP: AMBER**, dan **TLP: RED** (CSIRT).
* 🔒 **Keamanan & Integritas Audit Trail:** Tabel riwayat dilindungi **Trigger PostgreSQL append-only** (mencegah manipulasi/penghapusan riwayat) dan **Optimistic Locking** (`row_version` & HTTP 409 Conflict) untuk mencegah tabrakan data.

---

## 👥 Cara Menggunakan Sistem (Berdasarkan Peran)

### 1. Sebagai Pelapor (ASN Pemohon Layanan)
1. **Buka Portal Publik:** Akses halaman utama (tanpa perlu login).
2. **Ajukan Tiket:** Isi form dengan memilih Unit Kerja (OPD), Kategori Masalah, NIP, Nama, Nomor WhatsApp, dan deskripsi kendala beserta foto pendukung.
3. **Simpan PIN Keamanan:** Setelah tiket berhasil dibuat, Anda akan menerima **Kode Tiket** dan **PIN 6 Digit**.
4. **Lacak Progres:** Masukkan Kode Tiket & PIN pada tab *"Lacak Tiket"* untuk memantau status pengerjaan, membaca catatan teknisi, atau menambahkan info susulan.
5. **Konfirmasi Selesai & Beri Rating:** Saat perbaikan selesai, konfirmasi penyelesaian dan berikan penilaian kepuasan (bintang 1–5).

---

### 2. Sebagai Admin Pusat (Verifikator Utama DKIS)
1. **Login Petugas:** Masuk via `/login` menggunakan akun Admin Pusat.
2. **Triage & Verifikasi Tiket:** Buka menu *"Verifikasi Masuk"*, periksa kelayakan laporan, tentukan klasifikasi TLP (GREEN/AMBER/RED), dan tentukan kategori layanan.
3. **Disposisi Bidang:** Arahkan tiket ke bidang pengampu yang tepat (Infrastruktur TIK, Aplikasi Digital, atau Persandian/CSIRT).
4. **Mediasi & Force Close:** Jika terjadi tiket eskalasi yang berulang kali di-reopen pelapor, lakukan audit teknis dan gunakan hak veto penutupan paksa (*Force Close*).

---

### 3. Sebagai Admin Bidang (Koordinator Tim Teknis)
1. **Penerimaan Tiket:** Buka menu antrean bidang untuk melihat tiket yang telah diverifikasi oleh Admin Pusat.
2. **Penunjukan Teknisi:** Pilih teknisi koordinator (**Lead PIC**) dan tim anggota. Pantau indikator beban kerja agar tidak melebihi kuota beban.
3. **Monitoring Progres & Hambatan:** Pantau SLA berjalan dan tindak lanjuti laporan hambatan eksternal (*blocker*) yang dilaporkan teknisi di lapangan.

---

### 4. Sebagai Teknisi Lapangan (Pelaksana Tugas Lapangan)
1. **Buka Portal Tugas:** Masuk via portal teknisi untuk melihat daftar tugas yang dialokasikan.
2. **Respon Penugasan:** Klik **"Terima Penugasan"** (atau "Tolak Penugasan" dengan alasan formal) sebelum batas waktu respon kedaluwarsa.
3. **Komunikasi & Update Progres:** Gunakan tombol *1-Click WhatsApp* untuk menghubungi pelapor, dan catat progres tindakan teknis di lokasi.
4. **Selesaikan Tugas:** Lampirkan foto bukti perbaikan dan catatan solusi, lalu klik **"Selesaikan Pekerjaan"** untuk meneruskan hasil ke pelapor.

---

## 🚀 Panduan Menjalankan Sistem (Quick Start)

### Prasyarat
* [Docker Desktop](https://www.docker.com/products/docker-desktop/) telah terpasang di komputer Anda.
* Git.

### 1. Kloning Repositori
```bash
git clone https://github.com/Shiraori19/Sistem-Ticketing.git
cd Sistem-Ticketing
```

### 2. Siapkan File Environment
Salin template konfigurasi:
```bash
cp .env.example .env
```
*(File `.env.example` sudah disesuaikan secara default untuk environment Docker lokal).*

### 3. Jalankan Kontainer Docker
```bash
docker compose up -d
```
Sistem akan otomatis menjalankan 5 service:
* **`ticketing_web`** : Nginx Web Server (Port `80`, `443`, dan `8443` HTTPS).
* **`ticketing_app`** : PHP 8.2-FPM Backend Engine.
* **`ticketing_db`** : PostgreSQL 15 Database.
* **`ticketing_queue`** : Worker Pemroses Notifikasi Latar Belakang.
* **`ticketing_scheduler`** : Cron Otomasi Pelepasan Tugas & Pengecekan SLA.

### 4. Inisialisasi Database & Data Bawaan (Seeder)
```bash
docker exec -it ticketing_app php artisan migrate:fresh --seed
```

### 5. Buka di Browser
* 🌐 **Portal Layanan ASN (Publik):** [https://localhost:8443](https://localhost:8443) *(atau http://localhost)*
* 🔐 **Portal Masuk Petugas DKIS:** [https://localhost:8443/login](https://localhost:8443/login)

---

## 🔑 Akun Demo Pengujian

Anda dapat langsung menguji seluruh peran menggunakan akun bawaan berikut (seluruh kata sandi: `Password123!`):

| Peran Akun | Alamat Email / Akun | Bidang / Cakupan | Kata Sandi |
| :--- | :--- | :--- | :--- |
| **Admin Pusat** | `admin.pusat@cirebonkota.go.id` | Lintas Bidang (DKIS Pusat) | `Password123!` |
| **Admin Bidang ITIK** | `dewi.lestari@cirebonkota.go.id` | Infrastruktur Jaringan & TIK | `Password123!` |
| **Admin Bidang CSIRT** | `riko.pratama@cirebonkota.go.id` | Persandian & Keamanan Siber | `Password123!` |
| **Teknisi ITIK** | `fazra.fatimah@cirebonkota.go.id`| Teknisi Lapangan Jaringan | `Password123!` |
| **Teknisi CSIRT** | `rian.pratama@cirebonkota.go.id` | Teknisi Lapangan Siber | `Password123!` |

---

## 🧪 Pengujian Sistem (Automated Tests)

Proyek ini telah lulus **100% pada 26 skenario pengujian otomatis (135 assertions)**:

```bash
docker exec -it ticketing_app php artisan test
```

Cakupan pengujian meliputi:
- Siklus hidup tiket ujung-ke-ujung (E2E Multi-Role Workflow).
- Proteksi keamanan unduhan privat ber-PIN & masking data rahasia.
- Preservasi SLA pada redisposisi & pewarisan tiket induk-anak (*parent-child*).
- Isolasi keamanan insiden siber TLP: RED (blokir HTTP 404 bagi non-Persandian).
- Sinkronisasi penutupan paksa (*Force Close*) yang otomatis melepaskan penugasan aktif teknisi (*released*).
- Proteksi konkurensi data (*Optimistic Locking* & HTTP 409 Conflict).
- Integritas *append-only* riwayat event pada PostgreSQL trigger.

---

## 🛠️ Stack Teknologi

- **Backend:** PHP 8.2, Laravel 10.x
- **Basis Data:** PostgreSQL 15 (dengan JSONB Audit Ledger)
- **Web Server:** Nginx Alpine (HTTP/2, SSL TLS 1.2/1.3, FastCGI Keepalive)
- **Frontend:** Laravel Blade, CSS3 Modern, Tailwind CSS, Alpine.js, Chart.js
- **Orkestrasi:** Docker & Docker Compose
- **Integritas Sistem:** PostgreSQL Triggers, Eloquent State Machine, RBAC Policy Guard

---

## 🏛️ Lisensi & Hak Cipta

Hak Cipta © 2026 **Dinas Komunikasi, Informatika, dan Statistik (DKIS) Pemerintah Kota Cirebon**.  
Dikembangkan untuk mendukung transformasi digital pemerintahan, transparansi pelayanan publik, dan tata kelola SPBE yang andal.
