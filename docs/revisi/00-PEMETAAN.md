# Dokumen Pemetaan Titik Unggah dan Konfigurasi Sistem (Fase 0)
**Sistem Ticketing DKIS Kota Cirebon**  
**Tanggal**: 21 Oktober 2026  
**Auditor/Engineer**: Senior Laravel Engineer & Security Reviewer  
**Branch**: `revisi/upload-tlp-20261021`

---

## 1. Pemetaan Titik Unggah Berkas (Upload Points)

Berdasarkan penelusuran statis kode sumber (`app/`, `resources/views/`, `routes/web.php`):

| No | Peran | Titik Unggah / Rute | Input Form | Kontroler / Service | Validasi Saat Ini | Disk / Lokasi Simpan | Cara Berkas Disajikan |
|---|---|---|---|---|---|---|---|
| 1 | **Pelapor Publik** | `POST /tiket` (`public.ticket.store`) | `lampiran_pelapor[]` (multi) | `PublicTicketController@store` | FormRequest: `file\|max:5120\|mimes:jpg,jpeg,png,pdf` | Disimpan bertahap di `tickets/staging/{uuid}` lalu dipindah ke `tickets/{kode_tiket}` (`private` disk) | Lewat `GET /tiket/{kode}/berkas/{type}` (`TicketFileController@downloadPublic`), dicek PIN sesi, header `inline`/`attachment` |
| 2 | **Pelapor Publik** | `POST /tiket/{kode}/lengkapi-data` (`public.ticket.submit_completion`) | `lampiran_tambahan` | `PublicTicketController@submitCompletion` | Validasi: `file\|max:5120\|mimes:jpg,jpeg,png,pdf,doc,docx` | `tickets/{kode_tiket}` (`private` disk) & entri `ticket_files` | Idem lewat `downloadPublic` |
| 3 | **Pelapor Publik** | `POST /tiket/{kode}/reopen` (`public.ticket.reopen`) | `bukti_reopen` | `PublicTicketController@reopen` | Validasi: `file\|max:5120\|mimes:jpg,jpeg,png,pdf` | `tickets/{kode_tiket}` (`private` disk) | Idem lewat `downloadPublic` |
| 4 | **Admin Bidang** | `POST /admin/tiket/{id}/resolved` (`admin.bidang.tiket.markResolved`) | `bukti_resolusi` | `BidangTicketController@markResolved` -> `TicketWorkflowService@markResolved` | FormRequest: `file\|max:10240\|mimes:jpg,jpeg,png,pdf` | `tickets/{kode_tiket}` (`private` disk) | `GET /admin/tiket/{kode}/berkas/{type}` (`TicketFileController@downloadAdmin`) |
| 5 | **Teknisi Lapangan**| `POST /teknisi/tugas/{id}/catatan` | `berkas` | `TeknisiTicketController@addWorklog` -> `AssignmentWorkflowService@addWorklog` | Validasi: `file\|max:10240\|mimes:jpg,jpeg,png,pdf,txt,log` | `tickets/{kode_tiket}` (`private` disk) & `ticket_files` | Lewat `downloadAdmin` |
| 6 | **Teknisi Lapangan**| `POST /teknisi/tugas/{id}/hambatan` | `berkas_bukti` | `TeknisiTicketController@reportBlocker` -> `AssignmentWorkflowService@reportBlocker` | Validasi: `file\|max:10240\|mimes:jpg,jpeg,png,pdf,txt,log` | `tickets/{kode_tiket}` (`private` disk) & `ticket_files` | Lewat `downloadAdmin` |
| 7 | **Teknisi Lapangan**| `POST /teknisi/tugas/{id}/ajukan-selesai` | `bukti_foto` | `TeknisiTicketController@submitForReview` -> `AssignmentWorkflowService@submitForReview` | Validasi: `file\|max:10240\|mimes:jpg,jpeg,png,pdf,txt,log` | `tickets/{kode_tiket}` (`private` disk) & `ticket_files` | Lewat `downloadAdmin` |
| 8 | **Impor Data** | Tidak ditemukan titik impor berkas (hanya ekspor CSV di `LaporanKinerjaController@exportExcel`). | - | - | - | - | - |

### Temuan Kelemahan di Titik Unggah:
1. **Validasi Hanya Ekstensi/MIME Klien**: Validasi `mimes:jpg,jpeg,png,pdf` pada Laravel bawaan memeriksa ekstensi berdasarkan tebakan client mime / finfo dasar tanpa verifikasi struktur biner mendalam (magic bytes + content structure parsing). Berkas berbahaya (seperti web shell, polyglot, atau video MP4 yang di-rename menjadi `.pdf`) berpotensi lolos.
2. **Tidak Ada Pembersihan EXIF/Metadata**: Berkas gambar diunggah mentah tanpa proses sanitasi GD/Imagick, sehingga metadata sensitif (geolokasi GPS pengunggah, jenis perangkat) atau muatan PHP di komentar EXIF tersimpan di server.
3. **Penyimpanan Tersebar Langsung Memanggil `->store()`**: Pemanggilan `->store()` dilakukan langsung di controller dan service tanpa satu pintu `SecureUploadService`.
4. **Ketiadaan Disk Vault untuk TLP RED / Rahasia**: Berkas rahasia atau tiket TLP RED masih disimpan di disk `private` biasa tanpa enkripsi *at-rest* dan tanpa partisi terisolasi.

---

## 2. Pemetaan Penayangan dan Penautan Lampiran

| Halaman | URL / Rute | Otorisasi Saat Ini | Keberadaan Token | Cara Penayangan |
|---|---|---|---|---|
| **Lacak Publik** | `GET /tiket/lacak` | Berbasis verifikasi PIN sesi (`TicketAuthService::isAuthorized`) | Tidak ada token permanen di URL, namun tautan unduhan langsung mengarah ke `/tiket/{kode}/berkas/{type}` | Menampilkan tautan unduh/inline biasa, belum ada modal pratinjau derivatif aman tanpa unduh |
| **Verifikasi Pusat** | `GET /admin/verifikasi` | Middleware `admin.pusat` | Sesi admin internal | Tautan berkas lampiran pelapor |
| **Detail Tiket Pusat** | `GET /admin/tiket/{id}` | Middleware `admin.pusat` | Sesi admin internal | Tautan berkas lampiran |
| **Antrean Bidang** | `GET /admin/bidang/tiket` | Middleware `admin.bidang` | Sesi admin bidang | Tautan berkas lampiran |
| **Detail Tugas Teknisi** | `GET /teknisi/tugas/{id}` | Middleware `teknisi` | Sesi teknisi terdaftar | Tautan berkas lampiran |

---

## 3. Pemeriksaan Konfigurasi Server & Kontainer

1. **Nginx (`docker/nginx/default.conf`)**:
   - `location ^~ /storage/ { deny all; return 403; }`: Akses langsung HTTP ke folder storage publik/privat diblokir secara benar.
   - Eksekusi skrip PHP di folder storage diisolasi karena hanya FastCGI handler pada webroot `public/index.php` yang aktif.
   - Header keamanan OWASP/Gov-CSIRT lengkap (`X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `HSTS`, CSP).
   - Batas unggahan Nginx: `client_max_body_size 25M`.
2. **PHP & Dockerfile (`Dockerfile`)**:
   - Base image: `php:8.2-fpm-alpine`.
   - Ekstensi terpasang: `pdo`, `pdo_pgsql`, `pgsql`, `mbstring`, `zip`, `gd` (freetype & jpeg), `bcmath`, `opcache`.
   - **Belum terpasang**: `ffmpeg`, `poppler-utils` (untuk pdftoppm), `clamav` scanner. Perlu ditambahkan paket-paket ini ke Dockerfile untuk mendukung rasterisasi pratinjau dan validasi video/antivirus.
3. **Queue Worker**:
   - `QUEUE_CONNECTION=database` di konfigurasi env.
   - Perlu memastikan queue worker aktif menjalankan background jobs pemrosesan pratinjau derivatif.

---

## 4. Pemeriksaan Admin Pusat Berganda (Multi-Admin Pusat)

1. **Kondisi Akun**:
   - `DatabaseSeeder.php` hanya membuat 1 akun Admin Pusat (`admin.pusat@cirebonkota.go.id` - Ahmad Fadli).
2. **Kondisi Antrean Verifikasi**:
   - `VerificationController@index` menampilkan seluruh tiket `pending_verification` secara terbuka tanpa status kunci lunak (*soft claim*).
   - Jika dua Admin Pusat membuka antrean bersamaan, keduanya dapat membuka dan menekan verifikasi pada tiket yang sama, berpotensi menimbulkan *race condition* dan tumpang tindih disposisi.
   - Belum ada kolom `claimed_by`, `claimed_at`, `row_version` (kunci optimistik), serta tombol "Ambil Tiket Berikutnya".
3. **Pengaman Akun**:
   - Belum ada proteksi penghapusan/penonaktifan pada akun Admin Pusat terakhir atau diri sendiri di `UserController`.
