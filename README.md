<h1 align="center">🎫 Sistem Helpdesk & Ticketing Layanan TIK</h1>
<h3 align="center">Dinas Komunikasi, Informatika, dan Statistik (DKIS) Kota Cirebon</h3>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-10.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" />
  <img src="https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white" />
  <img src="https://img.shields.io/badge/PostgreSQL-15-336791?style=for-the-badge&logo=postgresql&logoColor=white" />
  <img src="https://img.shields.io/badge/Docker-Compose-2496ED?style=for-the-badge&logo=docker&logoColor=white" />
  <img src="https://img.shields.io/badge/Nginx-Alpine-009639?style=for-the-badge&logo=nginx&logoColor=white" />
</p>

<p align="center">
  Sistem Informasi Manajemen Permohonan Layanan TIK, Pemeliharaan Infrastruktur Jaringan,<br/>
  dan Penanganan Insiden Keamanan Siber Terpadu berbasis standar SPBE Pemerintah Kota Cirebon.
</p>

---

## 📋 Daftar Isi

- [Gambaran Umum](#-gambaran-umum)
- [Fitur Utama](#-fitur-utama)
- [Arsitektur Sistem](#-arsitektur-sistem)
- [Peran Pengguna](#-peran-pengguna)
- [Alur Kerja Tiket](#-alur-kerja-tiket)
- [Prasyarat](#-prasyarat)
- [Instalasi & Menjalankan](#-instalasi--menjalankan)
- [Akun Default](#-akun-default-setelah-seeder)
- [Variabel Environment](#-variabel-environment-penting)
- [Perintah Berguna](#-perintah-berguna)
- [Menjalankan Test](#-menjalankan-test)
- [Deployment Produksi](#-deployment-produksi)

---

## 🏛️ Gambaran Umum

Sistem Ticketing DKIS adalah aplikasi web helpdesk terpusat untuk melayani kebutuhan bantuan Teknologi Informasi bagi seluruh ASN di OPD Pemerintah Kota Cirebon.

Sistem ini mengintegrasikan seluruh tahapan penanganan keluhan teknis — mulai dari permohonan awal oleh ASN, verifikasi oleh Admin Pusat, koordinasi oleh Admin Bidang, hingga pengerjaan langsung oleh Teknisi lapangan.

---

## ✨ Fitur Utama

| Fitur | Keterangan |
|-------|-----------|
| 🔐 **Zero-Friction Access** | ASN melapor hanya dengan NIP + No. WhatsApp, tanpa registrasi akun |
| ⏱️ **SLA Otomatis** | Hitung mundur target waktu respon & resolusi berdasarkan 13 kategori layanan |
| ⚖️ **Workload Balancing** | Pembatasan kuota beban kerja teknisi (maks. 6 poin aktif/teknisi) |
| 🚨 **Traffic Light Protocol** | Klasifikasi kerahasiaan tiket (RED/AMBER/GREEN/CLEAR) |
| 🔒 **Isolasi CSIRT** | Tiket TLP RED eksklusif untuk tim Persandian (HTTP 404 untuk non-CSIRT) |
| 📋 **Audit Trail Immutable** | Riwayat aktivitas dilindungi trigger PostgreSQL append-only |
| 🔁 **Anti-Deadlock Mechanism** | Eskalasi otomatis & Force Close oleh Admin Pusat |
| 🤖 **Auto-Release Penugasan** | Scheduler otomatis melepas penugasan yang tidak direspon teknisi |
| 📱 **Notifikasi WhatsApp** | Integrasi gateway Fonnte untuk notifikasi real-time |
| 🌟 **Rating Kepuasan** | ASN dapat memberi rating bintang 1–5 saat tiket selesai |

---

## 🏗️ Arsitektur Sistem

```
Internet
    │
    ▼
┌─────────────────────────────────────────────┐
│  Nginx Alpine (Port 80 / 443 / 8443)         │
│  ├── :443  → Portal Publik ASN               │
│  └── :8443 → Panel Admin Internal            │
└───────────────────┬─────────────────────────┘
                    │ FastCGI
                    ▼
┌───────────────────────────────┐
│   PHP-FPM 8.2 (Laravel 10)   │
│   ticketing_app               │
└──────┬────────────┬───────────┘
       │            │
       ▼            ▼
┌──────────┐  ┌───────────────────────────┐
│ PostgreSQL│  │  Background Workers       │
│    15     │  │  ├── ticketing_queue      │
│ ticketing │  │  └── ticketing_scheduler  │
│    _dkis  │  └───────────────────────────┘
└──────────┘
```

**Stack Teknologi:**
- **Backend:** PHP 8.2, Laravel 10.x
- **Database:** PostgreSQL 15 (dengan JSONB & Trigger)
- **Frontend:** Blade, Tailwind CSS, Alpine.js, Chart.js
- **Web Server:** Nginx Alpine (HTTP/2, SSL, Gzip)
- **Containerization:** Docker & Docker Compose

---

## 👥 Peran Pengguna

### 1. 🏢 Portal Pelapor (ASN Pemohon)
- Mengajukan tiket dengan NIP + No. WhatsApp
- Melacak status pengerjaan secara real-time
- Memberikan info susulan & rating kepuasan

### 2. 🛡️ Admin Pusat (Verifikator DKIS)
- Verifikasi & triage permohonan masuk
- Menetapkan klasifikasi TLP (RED/AMBER/GREEN/CLEAR)
- Mendisposisikan tiket ke bidang pengampu
- Hak veto Force Close untuk tiket deadlock

### 3. 📋 Admin Bidang (Koordinator Tim)
- Memantau antrean tiket bidangnya
- Menugaskan Lead PIC & anggota tim teknisi
- Mengawasi beban kerja & kepatuhan SLA

### 4. 🔧 Teknisi Lapangan (Pelaksana)
- Menerima/menolak tugas dengan alasan formal
- Mencatat worklog & progres penanganan
- Melaporkan blocker eksternal (ISP, vendor, dll.)
- Mengunggah bukti resolusi

---

## 🔄 Alur Kerja Tiket

```
[ASN Pelapor] → Submit Tiket
       │
       ▼
[Admin Pusat] → Verifikasi & Triage
       │
       ├──► [TLP: RED] → Khusus Tim CSIRT/Persandian
       │
       ▼
Disposisi ke Bidang (SLA Resmi Dimulai)
       │
       ▼
[Admin Bidang] → Penugasan Teknisi (cek kuota beban)
       │
       ▼
[Teknisi] → Accept → Worklog → Resolved
       │
       ▼
[ASN Pelapor] → Konfirmasi & Rating ★★★★★
       │
       ├──► Closed ✅
       └──► Reopen (maks. 2x) → Deadlock → Eskalasi → Force Close
```

---

## ⚙️ Prasyarat

Pastikan sudah terinstall di sistem kamu:

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (v24+)
- [Git](https://git-scm.com/)

---

## 🚀 Instalasi & Menjalankan

### 1. Clone Repositori

```bash
git clone https://github.com/Shiraori19/Sistem-Ticketing.git
cd Sistem-Ticketing
```

### 2. Konfigurasi Environment

```bash
# Salin file konfigurasi
cp .env.example .env

# Edit sesuai kebutuhan (minimal ubah DB_PASSWORD)
# nano .env   (Linux/Mac)
# notepad .env  (Windows)
```

### 3. Siapkan SSL Certificate

```bash
# Buat self-signed certificate untuk development
mkdir -p docker/nginx/ssl

openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout docker/nginx/ssl/key.pem \
  -out docker/nginx/ssl/cert.pem \
  -subj "/C=ID/ST=Jabar/L=Cirebon/O=DKIS/CN=localhost"
```

### 4. Buat Volume Database

```bash
docker volume create pgdata
```

### 5. Build & Jalankan Container

```bash
docker compose up -d
```

> Proses pertama kali membutuhkan waktu beberapa menit untuk build image.

### 6. Inisialisasi Database

```bash
# Migrasi & seed data awal
docker compose exec app php artisan migrate:fresh --seed
```

### 7. Setup Final

```bash
# Generate APP_KEY (jika belum di-generate)
docker compose exec app php artisan key:generate

# Buat symlink storage
docker compose exec app php artisan storage:link

# Optimasi cache
docker compose exec app php artisan optimize
```

### 8. Akses Sistem

| URL | Keterangan |
|-----|-----------|
| `https://localhost` | Portal Publik ASN (submit tiket) |
| `https://localhost:8443/login` | Panel Login Petugas (admin & teknisi) |

> ⚠️ Browser akan memperingatkan SSL tidak valid (self-signed) — klik **Advanced → Proceed** untuk melanjutkan.

---

## 🔑 Akun Default (Setelah Seeder)

> Password default: **`Dkis#Cirebon2026!`**  
> Dapat diubah via variabel `SEEDER_ADMIN_PASSWORD` di `.env` sebelum seeder dijalankan.

| Username | Role | Bidang |
|----------|------|--------|
| `admin.pusat@cirebonkota.go.id` | Admin Pusat | — |
| `admin.pusat2@cirebonkota.go.id` | Admin Pusat | — |
| `jaringan@cirebonkota.go.id` | Admin Bidang | Infrastruktur TIK |
| `aplikasi@cirebonkota.go.id` | Admin Bidang | Pemerintahan Digital |
| `persandian@cirebonkota.go.id` | Admin Bidang | Persandian & CSIRT |
| `teknisi.fazra@cirebonkota.go.id` | Teknisi | Infrastruktur TIK |
| `teknisi.andi@cirebonkota.go.id` | Teknisi | Pemerintahan Digital |

> ⚠️ **Wajib ubah semua password** sebelum deploy ke production!

---

## 🔧 Variabel Environment Penting

```ini
# Aplikasi
APP_ENV=production          # Ubah ke production saat deploy
APP_DEBUG=false             # WAJIB false di production
APP_URL=https://domain-kamu.go.id
SESSION_SECURE_COOKIE=true  # WAJIB true jika pakai HTTPS

# Database
DB_PASSWORD=GANTI_PASSWORD_KUAT  # WAJIB diubah!

# WhatsApp Gateway (Fonnte)
WA_GATEWAY_TOKEN=token_fonnte_kamu
WA_SENDER_NUMBER=6281xxxxxxxxx

# Google reCAPTCHA v2
RECAPTCHA_SITE_KEY=your_site_key
RECAPTCHA_SECRET_KEY=your_secret_key

# Password default seeder (opsional, ubah sebelum seed)
SEEDER_ADMIN_PASSWORD=PasswordKuat123!
```

---

## 🛠️ Perintah Berguna

```bash
# Lihat status semua container
docker compose ps

# Lihat log real-time
docker compose logs -f

# Log aplikasi Laravel saja
docker compose logs -f app

# Masuk ke shell container app
docker compose exec app sh

# Jalankan Artisan command
docker compose exec app php artisan <command>

# Restart semua service
docker compose restart

# Stop semua container
docker compose down

# Reset database (HATI-HATI: data hilang!)
docker compose exec app php artisan migrate:fresh --seed
```

---

## 🧪 Menjalankan Test

```bash
# Jalankan semua test
docker compose exec app php artisan test

# Jalankan test spesifik
docker compose exec app php artisan test --filter=TechnicianAssignmentWorkflowTest

# Jalankan dengan output detail
docker compose exec app php artisan test --verbose
```

---

## 🌐 Deployment Produksi

### Checklist Sebelum Go-Live

- [ ] `APP_DEBUG=false` di `.env`
- [ ] `APP_ENV=production` di `.env`
- [ ] `SESSION_SECURE_COOKIE=true` di `.env`
- [ ] Password database sudah diganti
- [ ] SSL certificate resmi sudah dipasang di `docker/nginx/ssl/`
- [ ] Domain DNS sudah diarahkan ke server
- [ ] Port 8443 (admin panel) dibatasi ke IP jaringan internal kantor
- [ ] `php artisan optimize` sudah dijalankan
- [ ] Backup database terjadwal aktif

### Update Deployment

```bash
git pull origin main
docker compose build app
docker compose up -d --no-deps app queue scheduler
docker compose exec app php artisan migrate --force
docker compose exec app php artisan optimize
```

---

## 📁 Struktur Direktori Penting

```
Sistem-Ticketing/
├── app/
│   ├── Console/          # Scheduled commands
│   ├── Http/
│   │   ├── Controllers/  # Admin, Teknisi, Publik
│   │   └── Middleware/   # Auth, TLP, Role checks
│   └── Models/           # Eloquent models
├── database/
│   ├── migrations/       # Skema database
│   └── seeders/          # Data awal
├── docker/
│   ├── nginx/            # Konfigurasi Nginx & SSL
│   └── php/              # Konfigurasi PHP & entrypoint
├── resources/views/      # Blade templates
├── routes/               # web.php & api.php
├── docker-compose.yml    # Orkestrasi container
└── Dockerfile            # Build image PHP
```

---

## 📄 Lisensi

Hak Cipta © 2026 **Dinas Komunikasi, Informatika, dan Statistik (DKIS) Pemerintah Kota Cirebon**.  
Dikembangkan untuk mendukung tata kelola TIK pemerintahan yang transparan, akuntabel, dan berorientasi pelayanan prima.
