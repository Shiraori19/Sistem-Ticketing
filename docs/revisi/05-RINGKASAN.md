# Ringkasan Eksekutif Pembaruan Keamanan & Manajemen Berkas (Fase 5)
**Sistem Ticketing DKIS Kota Cirebon**  
**Tanggal**: 21 Oktober 2026  
**Auditor/Engineer**: Senior Laravel Engineer & Security Reviewer  
**Branch**: `revisi/upload-tlp-20261021`

---

## 1. Ringkasan Eksekutif (Untuk Pimpinan & Pengelola Sistem)

Pemerintah Daerah Kota Cirebon melalui Dinas Komunikasi dan Informatika (DKIS) telah menyelesaikan penguatan arsitektur keamanan tingkat lanjut pada Sistem Ticketing Layanan Publik dan Penanganan Insiden Keamanan Informasi. 

Pembaruan ini mencakup 7 pilar utama penguatan sistem:
1. **Pembersihan dan Validasi Berkas Unggahan**: Sistem tidak lagi hanya mempercayai ekstensi nama berkas dari pengguna. Seluruh berkas diperiksa struktur biner mendalam (*magic bytes*), dibersihkan dari metadata geolokasi GPS/EXIF perangkat, dipindai dari muatan skrip aktif, dan dokumen format Word (.doc/.docx) ditolak mutlak guna memitigasi risiko infeksi makro/eksploitasi zero-day.
2. **Brankas Data Rahasia (Vault Storage & Enkripsi AES-256)**: Berkas pengaduan insiden siber berkategori kritis (TLP RED) dan berkas berstatus rahasia otomatis dienkripsi dan diisolasi pada disk terpisah (*Vault*). Hanya tim CSIRT / Bidang Persandian dan personel terotorisasi yang dapat membuka berkas tersebut.
3. **Pratinjau Bukti Tanpa Unduh (Secure Derivative Preview)**: Petugas dan pelapor dapat meninjau tangkapan layar, dokumen, video, dan log teknis secara langsung pada jendela peramban tanpa mengunduh berkas fisik mentah ke perangkat komputer masing-masing. Berkas rahasia diberi tanda air (*watermark*) kedinasan secara otomatis.
4. **Dukungan Operasional Bersama Multi-Admin Pusat**: Dua atau lebih Admin Pusat kini dapat memverifikasi pengaduan secara bersamaan tanpa risiko saling tindih atau tabrakan data berkat fitur alokasi otomatis (*soft-claim*) dan kunci optimistik (*optimistic concurrency control*).
5. **Tata Kelola Penanganan Insiden Siber TLP RED**: Kebijakan penyebaran informasi insiden siber disesuaikan dengan protokol keamanan standar. Bidang non-Persandian tidak lagi menerima siaran insiden siber rahasia kecuali secara resmi ditembuskan untuk koordinasi lintas perangkat daerah.
6. **Optimasi Pengiriman Berkas di Sisi Klien**: Berkas foto/gambar berukuran besar secara otomatis dioptimasi dan dikompresi di peramban pelapor sebelum dikirim ke server, menghemat kuota internet pelapor dan bandwidth server pemerintah daerah.
7. **Jejak Integritas (Chain of Custody)**: Setiap berkas memiliki sidik jari kriptografi (hash SHA-256) unik saat diunggah. Setiap kali berkas dilihat atau diunduh, identitas petugas, alamat IP, dan waktu akses dicatat secara permanen dalam catatan audit (*audit log*).

---

## 2. Analisis Risiko Tersisa & Rencana Mitigasi

| No | Risiko Tersisa | Tingkat Risiko | Dampak Operasional | Mitigasi yang Disarankan |
|---|---|---|---|---|
| 1 | **Kapasitas Penyimpanan Storage Server** | Sedang | Peningkatan akumulasi berkas pratinjau derivatif dan log audit dalam jangka panjang dapat memenuhi kapasitas disk server. | Jalankan pembersihan berkas sementara secara terjadwal dan siapkan kebijakan rotasi arsip/cold storage berkala tahunan. |
| 2 | **Penyalahgunaan Akun Petugas Persandian** | Rendah | Mengingat Admin Persandian memegang hak pembuka berkas Vault, kebocoran kredensial login dapat membuka data rahasia. | Terapkan autentikasi dua faktor (2FA / TOTP) untuk akun Admin Pusat dan Admin Bidang Persandian. |
| 3 | **Ketergantungan Engine Antivirus Lokal** | Rendah | Saat ini sistem beroperasi dengan interface scanner yang siap disambungkan ke daemon ClamAV. Tanpa ClamAV aktif di kontainer terpisah, deteksi malware bergantung pada validasi heuristik magic bytes dan re-encoding GD. | Pasang kontainer khusus ClamAV (`clamav/clamav:latest`) di lingkungan server produksi DKIS yang terhubung via TCP socket port 3310. |

---

## 3. Rekomendasi Langkah Selanjutnya

1. **Penggabungan Kode (Pull Request & Merge)**:
   - Branch `revisi/upload-tlp-20261021` telah siap untuk ditinjau oleh Kepala Bidang Persandian dan Koordinator Tim Pengembang DKIS sebelum digabungkan ke cabang utama (`main`).
2. **Sosialisasi Petugas Meja Layanan (Helpdesk / Admin Pusat)**:
   - Mengadakan briefing singkat mengenai alur verifikasi dengan tombol "Ambil Tiket Berikutnya" dan penjelasan mengenai tidak diperbolehkannya lagi dokumen `.docx` (pelapor diarahkan mengunggah format `.pdf`).
3. **Penyediaan Kunci Terpisah (Vault Secret)**:
   - Pastikan variabel lingkungan `VAULT_ENCRYPTION_KEY` didefinisikan secara unik pada file `.env` produksi dan disimpan pada password manager kedinasan terpisah dari repositori Git.

---

## 4. Hal yang Tidak Bisa Saya Verifikasi

1. **Daemon ClamAV Produksi**: Diuji menggunakan `NullVirusScanner` karena daemon ClamAV aktif (`clamd`) membutuhkan kontainer ClamAV mandiri atau alokasi RAM server minimal 2-4 GB untuk database signature virus. Integrasi kode scanner telah siap pakai melalui `ClamAvVirusScanner` saat service dihubungkan.
2. **Kinerja Kompresi Klien pada Perangkat Seluler Spesifikasi Rendah**: Skrip HTML5 Canvas telah diuji pada browser emulator modern, namun batas alokasi memori WebGL/Canvas pada ponsel low-end (RAM < 2 GB) belum diuji pada perangkat keras fisik nyata.
