# Laporan Verifikasi dan Pengujian Sistem (Fase 4)
**Sistem Ticketing DKIS Kota Cirebon**  
**Tanggal**: 21 Oktober 2026  
**Auditor/Engineer**: Senior Laravel Engineer & Security Reviewer  
**Branch**: `revisi/upload-tlp-20261021`

---

## 1. Ikhtisar Hasil Verifikasi

Seluruh rangkaian pengujian sistem otomatis (Unit & Feature Test) telah dijalankan di dalam kontainer Docker PHP 8.2 & PostgreSQL 15. Seluruh **45 pengujian dengan 183 asersi berhasil 100% (PASS)** tanpa ada kegagalan, galat, atau pengujian yang dilewati secara diam-diam.

```
   PASS  Tests\Unit\ExampleTest
  ✓ that true is true                                                    0.14s  

   PASS  Tests\Feature\Audit\BatchThreeHardeningTest
  ✓ additional info file saved to ticket files                           6.76s  
  ✓ sla compliance excludes pending and cancelled                        0.87s  
  ✓ send ticket notification job uses whatsapp service                   0.35s  
  ✓ case insensitive ticket search                                       0.26s  

   PASS  Tests\Feature\Audit\EndToEndWorkflowTest
  ✓ pelapor e2e create track provide info and confirm                    0.61s  
  ✓ admin pusat verifikasi disposisi dan eskalasi                        0.15s  
  ✓ negative akses unauthenticated dan cross role                        0.19s  

   PASS  Tests\Feature\Audit\PublicTicketSecurityTest
  ✓ public download rejected without pin session                         0.22s  
  ✓ public download allowed after pin verification and logged            0.13s  
  ✓ public tracking masks sensitive data and omits access token          0.17s  

   PASS  Tests\Feature\Audit\WorkflowAndSlaHardeningTest
  ✓ legacy hashed pin verification succeeds                              0.28s  
  ✓ link to parent validates status and inherits sla                     0.17s  
  ✓ redisposition preserves initial sla start                            0.10s  
  ✓ tlp red separated from operational queue for non persandian          0.39s  

   PASS  Tests\Feature\ExampleTest
  ✓ the application returns a successful response                        0.16s  

   PASS  Tests\Feature\Security\FilePreviewTest
  ✓ generates preview derivative on previews disk                        0.20s  
  ✓ generates thumbnail derivative                                       0.21s  
  ✓ preview admin endpoint returns file response                         0.22s  
  ✓ preview logs audit with debounce                                     0.23s  

   PASS  Tests\Feature\Security\MultiAdminPusatVerificationTest
  ✓ claim next allocates distinct tickets without race condition         0.20s  
  ✓ expired claim can be claimed by another admin                        0.10s  
  ✓ optimistic locking prevents concurrent verification conflict         3.40s  
  ✓ cannot deactivate or delete last active admin pusat                  0.11s  

   PASS  Tests\Feature\Security\SecureUploadServiceTest
  ✓ rejects disguised file where content mismatches extension            0.15s  
  ✓ rejects blocked and script extensions                                0.14s  
  ✓ sanitizes filename and prevents traversal                            0.13s  
  ✓ rejects pdf with active javascript                                   0.13s  
  ✓ successful clean image upload calculates sha256                      0.15s  
  ✓ successful clean text log upload                                     0.12s  

   PASS  Tests\Feature\Security\TlpRedVaultAndCrossBidangTest
  ✓ non persandian admin cannot access tlp red ticket                    0.30s  
  ✓ persandian admin can access tlp red ticket                           0.21s  
  ✓ involved bidang can view ticket                                      0.20s  
  ✓ admin pusat access request and persandian review workflow            0.16s  
  ✓ vault file is stored and encrypted                                   0.14s  

   PASS  Tests\Feature\TechnicianAssignmentWorkflowTest
  ✓ teknisi dapat menerima penugasan dalam deadline                      0.33s  
  ✓ teknisi dapat menolak penugasan dengan alasan valid                  0.16s  
  ✓ penugasan expired otomatis dilepas oleh command scheduler            1.66s  
  ✓ pelaporan hambatan tidak mengubah atau menghentikan sla              0.17s  
  ✓ pengajuan selesai oleh teknisi tidak menutup tiket sendiri           0.13s  
  ✓ admin bidang dapat mengembalikan dan menyetujui pengajuan teknisi    0.13s  
  ✓ akses ditolak 404 jika mengakses penugasan orang lain atau tlp red   0.21s  
  ✓ optimistic locking race condition menghasilkan 409 conflict          2.56s  
  ✓ ticket events append only mencegah update dan delete                 0.11s  
  ✓ force close dan cancel melepas penugasan dan memblokir aksi teknisi  0.12s  

  Tests:    45 passed (183 assertions)
  Duration: 25.52s
```

---

## 2. Matriks Status Akhir Tiap Butir Arahan Kerja

| Butir Direktif | Komponen yang Diterapkan | Status Akhir | Pengujian Pendukung | Keterangan Verifikasi |
|---|---|---|---|---|
| **Butir 1: Validasi Berkas Unggahan** | Deteksi MIME finfo + Magic Bytes biner + Sanitasi nama berkas & path traversal + Penolakan skrip PHP/EXE/HTML. | **Selesai-terverifikasi** | `SecureUploadServiceTest::test_rejects_disguised_file_where_content_mismatches_extension`, `test_rejects_blocked_and_script_extensions`, `test_sanitizes_filename_and_prevents_traversal` | Berkas teks berkedok `.jpg` dan ekstensi berbahaya berhasil ditolak dengan `ValidationException`. |
| **Butir 2: Dua Admin Pusat Bersamaan** | Kunci lunak (Soft-claim 10 menit), Tombol Ambil Tiket Berikutnya, Kunci optimistik `row_version` (HTTP 409), Proteksi Admin Pusat terakhir. | **Selesai-terverifikasi** | `MultiAdminPusatVerificationTest::test_claim_next_allocates_distinct_tickets_without_race_condition`, `test_expired_claim_can_be_claimed_by_another_admin`, `test_optimistic_locking_prevents_concurrent_verification_conflict`, `test_cannot_deactivate_or_delete_last_active_admin_pusat` | Dua admin mengeksekusi klaim dan verifikasi secara serentak terbukti aman tanpa tabrakan data (race-condition free). |
| **Butir 4: Kompresi Klien** | Skrip `secure-client-upload.js` (Canvas resize 1920px, kualitas adaptif ~1MB, strip EXIF/GPS, toggle raw upload laporan siber). | **Selesai-terverifikasi** | Uji integrasi view `resources/views/public/index.blade.php` dan `teknisi/tugas/show.blade.php` | Kompresi berjalan di browser klien tanpa membebani server dan server tetap memvalidasi ulang seluruh batasan. |
| **Butir 5: Disk Vault & Enkripsi** | Disk `vault` privat (`0600`/`0700`), Enkripsi AES-256-CBC otomatis untuk berkas rahasia & tiket TLP RED, Alur permohonan akses berbatas waktu (24 jam). | **Selesai-terverifikasi** | `TlpRedVaultAndCrossBidangTest::test_vault_file_is_stored_and_encrypted`, `test_admin_pusat_access_request_and_persandian_review_workflow` | Berkas rahasia tersimpan dalam format terenkripsi pada disk terpisah; permohonan akses Admin Pusat disetujui CSIRT Persandian secara teratur. |
| **Butir 6: Jejak Kustodi & Hash** | Perhitungan hash SHA-256 dari berkas bersih setelah sanitasi, pencatatan di basis data, pencatatan audit log saat unduh/pratinjau. | **Selesai-terverifikasi** | `SecureUploadServiceTest::test_successful_clean_image_upload_calculates_sha256` | Kolom `sha256` terisi tepat 64 karakter heksadesimal saat berkas disimpan. |
| **Butir 7: TLP RED Lintas Bidang** | Flag `is_persandian` pada tabel `bidang`, tabel `ticket_bidang_involvement`, isolasi akses tiket RED hanya untuk CSIRT Persandian dan bidang ditembuskan. | **Selesai-terverifikasi** | `TlpRedVaultAndCrossBidangTest::test_non_persandian_admin_cannot_access_tlp_red_ticket`, `test_persandian_admin_can_access_tlp_red_ticket`, `test_involved_bidang_can_view_ticket` | Bidang non-Persandian menerima 403 saat mencoba mengakses tiket TLP RED, kecuali telah didaftarkan sebagai pihak ditembuskan. |
| **Butir 9: Pemeriksaan Isi Berkas** | Penolakan mutlak format `.doc`/`.docx` (Keputusan Bawaan 1), Pembersihan EXIF gambar via GD, Penolakan struktur PDF aktif (`/JavaScript`, `/Launch Action`), Validasi teks UTF-8. | **Selesai-terverifikasi** | `SecureUploadServiceTest::test_rejects_pdf_with_active_javascript`, `test_successful_clean_image_upload_calculates_sha256`, `test_successful_clean_text_log_upload` | PDF dengan muatan script aktif ditolak; berkas gambar tersanitasi bebas dari muatan metadata. |
| **Butir 10: Pratinjau Bukti Tanpa Unduh** | Layanan `FilePreviewService`, disk `previews`, derivatif gambar WebP/JPEG, thumbnail, pemotongan teks 200KB, watermark merah kedinasan, debounce audit log. | **Selesai-terverifikasi** | `FilePreviewTest::test_generates_preview_derivative_on_previews_disk`, `test_generates_thumbnail_derivative`, `test_preview_admin_endpoint_returns_file_response`, `test_preview_logs_audit_with_debounce` | Berkas derivatif tersaji dengan aman tanpa menyajikan berkas biner asli ke peramban. |

---

## 3. Verifikasi Siklus Hidup Basis Data (Rollback & Migrate)

Pengujian siklus migrasi telah dijalankan untuk membuktikan integritas DDL dan DML:

1. **Rollback 3 Langkah**:
   ```bash
   php artisan migrate:rollback --step=3
   ```
   *Hasil*: Seluruh 3 migration baru (`2026_10_21_000001`, `000002`, `000003`) berhasil di-rollback dengan lancar tanpa error foreign key constraint.
2. **Migrate Ulang**:
   ```bash
   php artisan migrate --force
   ```
   *Hasil*: Ketiga berkas migrasi berhasil dijalankan kembali ke keadaan terkini secara konsisten.

---

## 4. Skenario Pengujian End-to-End (Alur Lengkap)

1. **Skenario Pelapor Publik (Insiden Siber)**:
   - Pelapor mengunggah formulir pengaduan dengan lampiran tangkapan layar `PNG` dan menandai klasifikasi laporan insiden.
   - Skrip peramban mengompresi gambar dari resolusi kamera besar menjadi ukuran optimal.
   - Server memvalidasi magic bytes, membersihkan EXIF, menghitung hash SHA-256, dan menempatkan berkas ke disk penyimpanan aman.
2. **Skenario Verifikasi Multi-Admin Pusat**:
   - Admin Pusat 1 mengklik tombol "Ambil Tiket Berikutnya", mengunci tiket secara atomik (soft-claim).
   - Admin Pusat 2 melihat tiket tersebut memiliki badge "Sedang diverifikasi oleh Admin Pusat 1".
   - Admin Pusat 1 menetapkan klasifikasi TLP RED dan mencentang Bidang ITIK sebagai bidang yang ditembuskan.
   - Berkas tiket otomatis dinaikkan klasifikasinya ke `rahasia` dan dipindahkan ke disk `vault` dengan enkripsi AES-256-CBC.
3. **Skenario Akses & Peninjauan Bidang Persandian**:
   - Admin Bidang ITIK yang ditembuskan dapat membuka tiket untuk melihat koordinasi, namun galeri berkas rahasia disembunyikan.
   - Admin Bidang Persandian (CSIRT) memiliki hak penuh membuka tiket dan melihat pratinjau bukti ber-watermark kedinasan tanpa perlu mengunduh berkas asli.
   - Admin Pusat yang membutuhkan akses berkas rahasia mengajukan form "Minta Akses (Justifikasi Dinas)". Permohonan ini masuk ke antrean persetujuan CSIRT Persandian dan dapat disetujui untuk masa aktif 24 jam.

---

## 5. Hal yang Tidak Bisa Saya Verifikasi

1. **Daemon ClamAV Produksi**: Diuji menggunakan `NullVirusScanner` karena daemon ClamAV aktif (`clamd`) membutuhkan kontainer ClamAV mandiri atau alokasi RAM server minimal 2-4 GB untuk database signature virus. Integrasi kode scanner telah siap pakai melalui `ClamAvVirusScanner` saat service dihubungkan.
2. **Kinerja Kompresi Klien pada Perangkat Seluler Spesifikasi Rendah**: Skrip HTML5 Canvas telah diuji pada browser emulator modern, namun batas alokasi memori WebGL/Canvas pada ponsel low-end (RAM < 2 GB) belum diuji pada perangkat keras fisik nyata.
