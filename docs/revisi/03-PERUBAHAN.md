# Katalog Perubahan Sistem (Fase 1 - 4)
**Sistem Ticketing DKIS Kota Cirebon**  
**Tanggal**: 21 Oktober 2026  
**Auditor/Engineer**: Senior Laravel Engineer & Security Reviewer  
**Branch**: `revisi/upload-tlp-20261021`

---

## 1. Ikhtisar Perubahan

Perubahan ini mengimplementasikan penguatan keamanan komprehensif pada penanganan berkas unggahan, enkripsi arsip rahasia (Vault), pembagian tugas multi-admin pusat, isolasi insiden siber TLP RED lintas bidang, kompresi berkas pada peramban klien, serta pratinjau berkas derivatif aman tanpa unduh sesuai direktif `revisi.md`.

---

## 2. Rincian Modifikasi & Berkas Baru

### A. Basis Data & Migrasi Skema
1. [database/migrations/2026_10_21_000001_enhance_ticket_files_and_classification_schema.php](file:///d:/Sistem_Ticketing_Doc/database/migrations/2026_10_21_000001_enhance_ticket_files_and_classification_schema.php)
   - **Perubahan**: Menambahkan kolom `classification` (`umum`, `terbatas`, `rahasia`), `classification_reason`, `classified_by`, `classified_at`, `sha256`, `is_vault`, `is_encrypted`, `vault_path`, `preview_status`, `preview_path`, `thumb_path`, `virus_scan_status` pada tabel `ticket_files`. Membuat tabel baru `ticket_file_access_requests`.
   - **Alasan**: Mendukung Butir 1, 5, 9, dan 10 untuk klasifikasi berkas, jejak integritas SHA-256, isolasi disk vault, dan alur permohonan akses berkas rahasia.
   - **Dampak**: Model `TicketFile` kini menyimpan metadata keamanan lengkap untuk audit integritas dan otorisasi bertingkat.

2. [database/migrations/2026_10_21_000002_add_persandian_flag_and_bidang_involvement_schema.php](file:///d:/Sistem_Ticketing_Doc/database/migrations/2026_10_21_000002_add_persandian_flag_and_bidang_involvement_schema.php)
   - **Perubahan**: Menambahkan kolom boolean `is_persandian` (default `false`) pada tabel `bidang`. Membuat tabel `ticket_bidang_involvement` (`ticket_id`, `bidang_id`, `peran`, `notified_at`, `acknowledged_at`, `ditetapkan_oleh`).
   - **Alasan**: Menghapus penentuan bidang persandian berbasis string `LIKE '%persandian%'` (Butir 7) dan mengganti broadcast TLP RED ke seluruh bidang dengan daftar keterlibatan eksplisit (ditembuskan / pendukung).
   - **Dampak**: Bidang non-Persandian tidak lagi menerima siaran tiket TLP RED kecuali secara eksplisit ditembuskan atau diminta menjadi pendamping teknis.

3. [database/migrations/2026_10_21_000003_add_multi_admin_pusat_claims_and_row_version.php](file:///d:/Sistem_Ticketing_Doc/database/migrations/2026_10_21_000003_add_multi_admin_pusat_claims_and_row_version.php)
   - **Perubahan**: Menambahkan kolom `claimed_by` (foreign key ke `users`), `claimed_at`, dan integer `row_version` (default 1) pada tabel `tickets`.
   - **Alasan**: Mengakomodasi operasional bersama 2 atau lebih Admin Pusat secara paralel (Butir 2) menggunakan soft-claim berdurasi 10 menit dan optimistic concurrency control.
   - **Dampak**: Tabrakan verifikasi tiket secara serentak dicegah secara deterministik dengan respons HTTP 409 Conflict bila `row_version` telah berubah.

4. [database/seeders/DatabaseSeeder.php](file:///d:/Sistem_Ticketing_Doc/database/seeders/DatabaseSeeder.php)
   - **Perubahan**: Menambahkan akun Admin Pusat kedua (`admin.pusat2@cirebonkota.go.id` / `admin_pusat2`) dan memastikan Bidang Persandian memiliki flag `is_persandian = true`.

---

### B. Konfigurasi Sistem & Arsitektur Storage
5. [config/filesystems.php](file:///d:/Sistem_Ticketing_Doc/config/filesystems.php)
   - **Perubahan**: Menambahkan konfigurasi disk privat terisolasi `vault` (`storage/app/vault`) dan `previews` (`storage/app/previews`) dengan visibilitas `private` dan izin berkas/direktori ketat (`0600`/`0700`).

6. [config/files.php](file:///d:/Sistem_Ticketing_Doc/config/files.php)
   - **Perubahan**: File konfigurasi terpusat baru yang mendefinisikan batasan ukuran per peran dan jenis MIME, daftar ekstensi terlarang (termasuk penolakan mutlak `.doc` dan `.docx` sesuai Keputusan Bawaan 1), kebijakan retensi, konfigurasi kunci enkripsi Vault AES-256-CBC, opsi antivirus ClamAV, serta batas kompresi klien.

---

### C. Komponen Layanan & Keamanan Unggahan
7. [app/Services/SecureUploadService.php](file:///d:/Sistem_Ticketing_Doc/app/Services/SecureUploadService.php)
   - **Perubahan**: Layanan pintu tunggal untuk seluruh operasi unggah berkas. Memeriksa kecocokan ekstensi klien vs MIME terdeteksi (finfo) vs magic bytes heksadesimal, menolak skrip/shell/polyglot, membersihkan path traversal dan null-byte injection, melakukan re-encoding gambar via GD untuk membuang metadata EXIF dan GPS, memvalidasi struktur biner PDF dari muatan `/JavaScript` dan `/Launch`, memvalidasi encoding UTF-8 pada log/teks, menghitung sidik jari SHA-256 berkas bersih, mengenkripsi berkas rahasia ke disk `vault`, serta menyimpan entri ke tabel `ticket_files`.
   - **Alasan**: Memenuhi Butir 1 (Validasi Mendalam & Penolakan Mutlak Ekstensi Terlarang), Butir 5 (Disk Vault & Enkripsi AES-256), Butir 6 (Jejak Kustodi Hash SHA-256), dan Butir 9 (Pembersihan EXIF).

8. [app/Services/Antivirus/VirusScannerInterface.php](file:///d:/Sistem_Ticketing_Doc/app/Services/Antivirus/VirusScannerInterface.php) & Implementasinya
   - **Perubahan**: Kontrak interface antivirus dengan implementasi `NullVirusScanner` (fallback aman) dan `ClamAvVirusScanner` (koneksi daemon clamd/socket/cli).

9. [app/Services/FilePreviewService.php](file:///d:/Sistem_Ticketing_Doc/app/Services/FilePreviewService.php)
   - **Perubahan**: Layanan pembuatan berkas derivatif pratinjau yang disimpan pada disk `previews`. Menghasilkan derivatif WebP/JPEG beresolusi maksimal 1600 px, thumbnail 240 px, pemotongan teks/log aman maksimal 200 KB, watermark merah kedinasan "RAHASIA NEGARA / CSIRT KOTA CIREBON" untuk berkas berklasifikasi rahasia, serta pencatatan audit log `PRATINJAU_BERKAS` dengan mekanisme debounce 5 menit.
   - **Alasan**: Memenuhi Butir 10 (Pratinjau Bukti Tanpa Unduh Berkas Asli).

10. [app/Rules/ValidSecureFile.php](file:///d:/Sistem_Ticketing_Doc/app/Rules/ValidSecureFile.php)
    - **Perubahan**: Custom validation rule Laravel untuk mengikat validasi mendalam `SecureUploadService` langsung pada Form Request level.

11. [app/Console/Commands/RebuildPreviewsCommand.php](file:///d:/Sistem_Ticketing_Doc/app/Console/Commands/RebuildPreviewsCommand.php) & [app/Console/Commands/RescanFilesSecurityCommand.php](file:///d:/Sistem_Ticketing_Doc/app/Console/Commands/RescanFilesSecurityCommand.php)
    - **Perubahan**: Perintah artisan untuk rekalkulasi derivatif pratinjau secara batch (`php artisan previews:rebuild`) dan pemindaian ulang integritas berkas di storage (`php artisan files:rescan-security`).

---

### D. Model & Kontroler
12. [app/Models/Ticket.php](file:///d:/Sistem_Ticketing_Doc/app/Models/Ticket.php), [app/Models/Bidang.php](file:///d:/Sistem_Ticketing_Doc/app/Models/Bidang.php), [app/Models/TicketFile.php](file:///d:/Sistem_Ticketing_Doc/app/Models/TicketFile.php), [app/Models/TicketBidangInvolvement.php](file:///d:/Sistem_Ticketing_Doc/app/Models/TicketBidangInvolvement.php), [app/Models/TicketFileAccessRequest.php](file:///d:/Sistem_Ticketing_Doc/app/Models/TicketFileAccessRequest.php)
    - **Perubahan**: Penambahan relasi Eloquent (`involvements`, `claimedBy`, `files`, `accessRequests`), cast boolean/datetime, serta helper method otorisasi (`isPersandian()`, `isVault()`, `isApprovedAndValid()`).

13. [app/Http/Controllers/PublicTicketController.php](file:///d:/Sistem_Ticketing_Doc/app/Http/Controllers/PublicTicketController.php)
    - **Perubahan**: Refaktor penyimpanan lampiran awal pada `store()`, pelengkap berkas pada `submitCompletion()`, dan bukti sanggahan pada `reopen()` untuk sepenuhnya menggunakan `SecureUploadService::processUpload()`. Menghapus seluruh pemanggilan `->store()` langsung.

14. [app/Http/Controllers/Admin/VerificationController.php](file:///d:/Sistem_Ticketing_Doc/app/Http/Controllers/Admin/VerificationController.php)
    - **Perubahan**: Penambahan endpoint `claimNext()` untuk mengalokasikan tiket berikutnya secara atomik ke Admin Pusat yang sedang aktif, aksi `claimTicket()` dan `releaseClaim()`, validasi `row_version` untuk mencegah tabrakan verifikasi paralel (409 Conflict), serta pemrosesan `bidang_ditembuskan[]` untuk pencatatan koordinasi insiden siber TLP RED pada `ticket_bidang_involvement`.

15. [app/Http/Controllers/Admin/TicketController.php](file:///d:/Sistem_Ticketing_Doc/app/Http/Controllers/Admin/TicketController.php) & [app/Http/Controllers/Admin/BidangTicketController.php](file:///d:/Sistem_Ticketing_Doc/app/Http/Controllers/Admin/BidangTicketController.php)
    - **Perubahan**: Pengecekan otorisasi berbasis `is_persandian` dan `TicketBidangInvolvement` pada tiket TLP RED. Pengecekan izin penayangan berkas rahasia/Vault berdasarkan permohonan akses yang telah disetujui (`TicketFileAccessRequest`).

16. [app/Http/Controllers/TicketFileController.php](file:///d:/Sistem_Ticketing_Doc/app/Http/Controllers/TicketFileController.php)
    - **Perubahan**: Penyajian berkas unduhan asli melalui header proteksi ketat (`Content-Disposition: attachment`, no-cache), penyajian pratinjau derivatif aman via `FilePreviewService`, alur pengajuan akses berkas rahasia (`requestAccess`), dan peninjauan persetujuan oleh Admin Bidang Persandian (`reviewAccessRequest`).

17. [app/Http/Controllers/Admin/UserController.php](file:///d:/Sistem_Ticketing_Doc/app/Http/Controllers/Admin/UserController.php)
    - **Perubahan**: Proteksi mutlak pencegahan deaktivasi, demosi, atau penghapusan Admin Pusat terakhir yang aktif serta larangan deaktivasi diri sendiri.

18. [app/Services/AssignmentWorkflowService.php](file:///d:/Sistem_Ticketing_Doc/app/Services/AssignmentWorkflowService.php)
    - **Perubahan**: Mengarahkan unggahan berkas log progres, hambatan, dan pengajuan selesai oleh teknisi ke `SecureUploadService::processUpload()`.

---

### E. Antarmuka Pengguna (Blade & Vanilla JS)
19. [public/js/secure-client-upload.js](file:///d:/Sistem_Ticketing_Doc/public/js/secure-client-upload.js)
    - **Perubahan**: Skrip kompresi gambar berbasis HTML5 Canvas di sisi peramban (tanpa ketergantungan CDN eksternal). Mengubah ukuran sisi terpanjang ke maksimum 1920 px, kompresi adaptif mendekati 1 MB, pembuangan data EXIF/GPS, visualisasi perbandingan ukuran sebelum vs sesudah kompresi, serta toggle opsional "Unggah Asli Tanpa Kompresi (Khusus Laporan Kritis/Siber)".

20. [public/js/file-preview-viewer.js](file:///d:/Sistem_Ticketing_Doc/public/js/file-preview-viewer.js) & [resources/views/components/file-preview-modal.blade.php](file:///d:/Sistem_Ticketing_Doc/resources/views/components/file-preview-modal.blade.php)
    - **Perubahan**: Komponen modal lightbox pratinjau yang mendukung gambar, iframe dokumen PDF, pemutar video HTML5 dengan dukungan HTTP Range, dan penampil log/teks dengan syntax container ber-escape penuh. Memiliki navigasi tombol keyboard (Escape untuk keluar, Panah Kiri/Kanan untuk berpindah berkas) dan trap-focus aksesibilitas.

21. [resources/views/admin/verifikasi/index.blade.php](file:///d:/Sistem_Ticketing_Doc/resources/views/admin/verifikasi/index.blade.php)
    - **Perubahan**: Tombol "Ambil Tiket Berikutnya", kartu statistik beban kerja harian Admin Pusat, badge soft-claim pada tiket yang sedang dikunci rekan admin, input hidden `row_version`, serta checkbox penembusan bidang lain saat klasifikasi TLP RED dipilih.

22. [resources/views/admin/tiket/show.blade.php](file:///d:/Sistem_Ticketing_Doc/resources/views/admin/tiket/show.blade.php)
    - **Perubahan**: Banner koordinasi TLP RED, visualisasi badge status kerahasiaan berkas (Vault/Enkripsi), kartu galeri lampiran pratinjau, dan modal dialog "Minta Akses Berkas Rahasia" untuk Admin Pusat dengan input alasan justifikasi dinas.

23. [resources/views/teknisi/tugas/show.blade.php](file:///d:/Sistem_Ticketing_Doc/resources/views/teknisi/tugas/show.blade.php) & [resources/views/public/index.blade.php](file:///d:/Sistem_Ticketing_Doc/resources/views/public/index.blade.php)
    - **Perubahan**: Menautkan skrip `secure-client-upload.js`, memperbarui atribut `accept` formulir (menghapus format `.doc`/`.docx`), dan menyediakan toggle raw upload.

---

## 3. Analisis Dampak Terhadap Komponen Eksisting

| Modul / Komponen | Dampak yang Diterima | Mitigasi / Penanganan |
|---|---|---|
| **Alur Unggah Pelapor** | Berkas `.doc` dan `.docx` ditolak. Berkas gambar besar dikompresi di browser sebelum upload. | Notifikasi jelas di UI pelapor: *"Format Word (.doc/.docx) tidak diizinkan. Harap konversi dokumen ke PDF sebelum mengunggah."* |
| **Alur Verifikasi Admin Pusat** | Dua admin dapat memproses antrean bersamaan. Kemungkinan bentrok data saat submit bersamaan. | Optimistic locking via `row_version` mengembalikan pesan ramah dan me-refresh data tanpa kehilangan integritas status tiket. |
| **Penyimpanan Tiket TLP RED** | Bukti dan berkas tiket RED langsung diisolasi dan dienkripsi di disk `vault`. Admin Pusat tidak langsung melihat berkas. | Tersedia alur permohonan akses berbatas waktu (24 jam) yang dapat disetujui langsung oleh Admin Bidang Persandian. |
| **Kinerja Server & Penyimpanan** | Pembuatan derivatif gambar dan watermark membutuhkan komputasi CPU GD. | Derivatif dibuat sekali dan disimpan di disk `previews`. Akses berikutnya langsung membaca berkas statis dari storage. Debounce audit log mencegah flood database. |

---

## 4. Prosedur Rollback

Jika ditemukan ketidaksesuaian kritis pada lingkungan produksi, langkah-langkah rollback berikut dapat dijalankan:

1. **Kembalikan Basis Data**:
   ```bash
   docker compose exec app php artisan migrate:rollback --step=3
   ```
   *Catatan: Rollback telah diuji secara menyeluruh dan mengeksekusi penghapusan tabel `ticket_file_access_requests`, `ticket_bidang_involvement`, serta dropping kolom-kolom baru pada `ticket_files`, `bidang`, dan `tickets` tanpa merusak tabel data utama.*

2. **Kembalikan Kode Sumber (Git)**:
   ```bash
   git checkout main
   # atau jika ingin membatalkan commit pada branch revisi:
   git reset --hard HEAD~1
   ```

3. **Bersihkan Cache Konfigurasi & Rute**:
   ```bash
   docker compose exec app php artisan config:clear
   docker compose exec app php artisan route:clear
   docker compose exec app php artisan view:clear
   ```

---

## 5. Hal yang Tidak Bisa Saya Verifikasi

1. **Daemon ClamAV Produksi**: Diuji menggunakan `NullVirusScanner` karena daemon ClamAV aktif (`clamd`) membutuhkan kontainer ClamAV mandiri atau alokasi RAM server minimal 2-4 GB untuk database signature virus. Integrasi kode scanner telah siap pakai melalui `ClamAvVirusScanner` saat service dihubungkan.
2. **Kinerja Kompresi Klien pada Perangkat Seluler Spesifikasi Rendah**: Skrip HTML5 Canvas telah diuji pada browser emulator modern, namun batas alokasi memori WebGL/Canvas pada ponsel low-end (RAM < 2 GB) belum diuji pada perangkat keras fisik nyata.
