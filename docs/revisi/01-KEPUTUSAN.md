# Keputusan Arsitektur dan Bisnis (Gate 0)
**Sistem Ticketing DKIS Kota Cirebon**  
**Status**: DISETUJUI & DIKONFIRMASI  
**Tanggal**: 21 Oktober 2026  
**Dasar Persetujuan**: Mandat Pengguna untuk Eksekusi Otonom Penuh

---

## Ringkasan Keputusan yang Diterapkan

Sesuai instruksi dan panduan standar pada `revisi.md`, seluruh KEPUTUSAN BAWAAN (Poin 1 s/d 6) diterapkan sebagai kebijakan baku implementasi sistem:

### 1. Format Dokumen Kantor (DOCX / DOC / ODT)
- **Keputusan**: **Ditolak** di semua titik unggah sistem helpdesk ticketing.
- **Rasional**: Format dokumen office (DOCX/DOC/ODT/RTF) memiliki permukaan serangan yang sangat luas (vektor makro jahat, XML External Entity/XXE, OLE embedding, polyglot ZIP).
- **Tindakan**: Hapus `doc,docx` dari allowlist form request. Tampilkan pesan kesalahan bahasa Indonesia yang ramah dan jelas: *"Format dokumen Office (DOCX/DOC/ODT) tidak diizinkan demi keamanan. Silakan konversi dokumen Anda menjadi format PDF sebelum diunggah."*

### 2. Berkas Video
- **Keputusan**: **Hanya format MP4**, divalidasi container dan stream dengan ffprobe / binary header check, batas ukuran 20 MB dan durasi maksimal 120 detik, tidak dikompresi di browser (tetap format asli ber-transcode aman bila perlu).
- **Rasional**: Video MP4 (H.264 / AAC) adalah standar universal web. Validasi mendalam mencegah berkas video disamarkan menjadi dokumen atau sebaliknya.

### 3. Akses Berkas Rahasia dan TLP: RED oleh Admin Pusat
- **Keputusan**: Admin Pusat **hanya melihat metadata** berkas (nama asli, jenis mime, ukuran, waktu unggah, identitas pengunggah, klasifikasi, hash SHA-256).
- **Rasional**: Mengikuti prinsip *Need-to-Know* dan *Least Privilege* sesuai standar CSIRT dan Persandian BSSN. Admin Pusat bertindak sebagai dispatcher/koordinator tiket publik, bukan analis insiden kripto/siber.
- **Mekanisme Permintaan Akses**: Admin Pusat dapat menekan tombol **"Minta Akses"** dengan mengisi alasan kedinasan tertulis. Permintaan dicatat di tabel `ticket_file_access_requests`, harus disetujui oleh Admin Bidang Persandian, berlaku sementara (misal 24 jam), dan seluruh aktivitas unduh/baca dicatat di audit log.

### 4. Kompresi Gambar Otomatis Sebelum Unggah
- **Keputusan**: Kompresi di sisi browser (client-side) secara bawaan untuk gambar (JPEG/PNG) menggunakan kanvas HTML5 ringan tanpa dependensi eksternal runtime.
- **Spesifikasi**: Sisi terpanjang maksimum 1920 piksel, kualitas adaptif dengan target ukuran <= 1 MB, pembersihan EXIF/metadata geolokasi secara alami.
- **Opsi Berkas Asli**: Disediakan checkbox *"Unggah berkas asli tanpa kompresi (Resolusi penuh)"* khusus untuk kategori Insiden Siber / Keamanan / TLP: RED agar integritas forensik bukti digital tetap terjaga. Server tetap memvalidasi penuh isi berkas.

### 5. Pratinjau Bukti Tanpa Unduh
- **Keputusan**: Seluruh berkas bukti dapat dipratinjau langsung di antarmuka (detail tiket publik, admin verifikasi, admin bidang, teknisi) **tanpa mengunduh berkas asli**.
- **Prinsip Keamanan Derivatif**: Server membuat salinan turunan (*derivative*) aman di disk privat `previews`:
  - Gambar: WebP/JPEG teroptimasi (max 1600px) + thumbnail kecil.
  - PDF: Rasterisasi halaman ke gambar (halaman 1 s/d 10) di server; PDF tidak pernah dieksekusi atau disajikan langsung ke browser.
  - Video: Cuplikan WebP poster dan video MP4 streaming dengan header HTTP Range.
  - Teks/Log: Teks ter-escape penuh (maksimal 200 KB) dalam blok kode aman.
- **Watermark Rahasia**: Untuk berkas berklasifikasi rahasia, pratinjau diberi watermark tipis (nama petugas yang melihat + timestamp + kode tiket).

### 6. Unduhan Berkas Asli & Kustodi Digital
- **Keputusan**: Unduhan berkas asli hanya tersedia bagi pihak berwenang via URL bertanda tangan berumur pendek (*short-lived signed URL*, maksimal 5 menit) atau rute berotorisasi sesi ketat.
- **Rasional & Kustodi**: Tidak ada tombol "unduh semua" massal. Setiap akses unduhan mencatat jejak kustodi digital: identitas pengguna, IP address, waktu, path, dan pencocokan hash SHA-256 yang tersimpan sejak unggah pertama kali.

### 7. Keputusan Lanjutan: Penentuan Bidang Persandian & Multi-Admin Pusat
- **Penentuan Bidang Persandian**: Menghapus seluruh kueri berbasis string `LIKE '%persandian%'`. Ditambahkan kolom boolean `is_persandian` pada tabel `bidang` (dengan migrasi baru aditif) dan fallback kode bidang `PERSANDIAN`.
- **Dua Admin Pusat Sekaligus**: Menerapkan mekanisme kunci lunak (*soft claim*) 10 menit pada antrean verifikasi, kunci optimistik (`row_version`), dan proteksi akun tidak boleh menghapus Admin Pusat terakhir atau diri sendiri.
