# Prompt Revisi dan Penambahan Sistem Ticketing DKIS Bidang Persandian

Cara pakai: taruh file ini di root repo, lalu minta Claude Code (atau AI pembuat kode lain) membaca dan menjalankannya. Jalankan Fase 0 dulu dan berhenti di GATE 0. Jangan menghilangkan titik berhenti, karena di situlah kamu menjawab keputusan bisnis.

Versi ini sudah memuat: pemeriksaan jenis berkas dari isi aslinya di semua titik unggah (termasuk kasus berkas yang disamarkan), perlakuan berkas TLP RED dan rahasia, RED lintas bidang, dua Admin Pusat, kompresi sebelum unggah, dan pratinjau bukti tanpa unduh (butir 10). Butir 3 dan 8 sengaja tidak ada.

---

## PERAN

Kamu senior Laravel engineer dan reviewer keamanan. Kerjakan REVISI dan PENAMBAHAN pada sistem ticketing DKIS Bidang Persandian (Laravel + PostgreSQL + Blade + Docker). Pengguna: Pelapor (tanpa login, kode tiket + PIN), Admin Pusat, Admin Bidang, Teknisi.

## ATURAN KERJA (WAJIB)

1. Baca kode dulu. Setiap klaim wajib disertai bukti (file:baris atau keluaran perintah). Yang tidak bisa diverifikasi ditulis "tidak terverifikasi", jangan berpura-pura sudah dicek.
2. Buat branch baru (`revisi/upload-tlp-YYYYMMDD`). Jangan push atau merge. Jangan menyentuh `.env` asli, rahasia, atau data produksi.
3. Skema hanya lewat migrasi BARU yang aditif dan bisa di-rollback. Jangan ubah migrasi lama. Jangan ubah perilaku yang tidak terkait.
4. Satu perbaikan = satu commit kecil. Untuk setiap perilaku, tulis test dulu lalu implementasi. Logika di service, validasi di Form Request, otorisasi di Policy/Gate, perubahan status di DB transaction dengan `lockForUpdate`.
5. Keputusan bisnis yang ambigu: jangan menebak. Pakai KEPUTUSAN BAWAAN di bawah sebagai usulan, catat di `01-KEPUTUSAN.md` beserta opsi dan alasannya, lalu minta konfirmasiku di GATE 0.
6. Laporan berbahasa Indonesia di `docs/revisi/`. Fixture uji keamanan hanya memakai sampel INERT (string penanda), jangan membuat malware nyata.

## KEPUTUSAN BAWAAN (usulan; konfirmasi di GATE 0)

1. DOCX/DOC/ODT: ditolak di semua titik unggah. Pelapor diminta mengubahnya menjadi PDF.
2. Video: hanya MP4, divalidasi dengan ffprobe, batas ukuran dan durasi di konfigurasi, tidak dikompresi di browser.
3. Admin Pusat dan berkas rahasia/RED: hanya melihat metadata (nama, jenis, ukuran, waktu, pengunggah, klasifikasi, hash). Isi dan pratinjau hanya untuk Persandian yang berwenang. Admin Pusat dapat menekan "Minta akses" dengan alasan, disetujui Admin Bidang Persandian, berlaku sementara dan tercatat.
4. Kompresi gambar di browser secara bawaan; opsi "unggah asli" tersedia untuk kategori insiden keamanan dan tiket yang berpotensi RED.
5. Pratinjau PDF dibuat dengan rasterisasi di server (bukan menjalankan PDF di browser).
6. Unduhan berkas asli hanya untuk pihak berwenang, dan setiap unduhan dicatat.

---

## URUTAN KERJA

Fase 0 (pemetaan), lalu GATE 0. Lalu kerjakan butir berurutan: **1+9, 5+6, 4, 10, 7, 2**, dengan verifikasi di tiap butir. Terakhir laporan.

## FASE 0 (hanya-baca)

- Cari SEMUA titik unggah berkas: grep `->file(`, `->store(`, `Storage::`, `move(`, input `type=file` di Blade, serta impor (mis. CSV SKPD). Petakan per peran: pelapor (tiket baru, kelengkapan, reopen), Admin Pusat, Admin Bidang (bukti resolusi), Teknisi (progres, hambatan, bukti selesai). Catat validasi yang ada, disk penyimpanan, nama berkas, dan cara berkas disajikan.
- Cari SEMUA tempat lampiran ditampilkan atau ditautkan (halaman lacak pelapor, verifikasi Admin Pusat, antrean dan detail Admin Bidang, tampilan Teknisi). Catat apakah tautannya memuat token (mis. `access_token` / `auth_token`) dan bagaimana otorisasinya.
- Periksa konfigurasi nginx/PHP (eksekusi skrip di folder unggahan, batas ukuran, header) dan Dockerfile (ketersediaan ffmpeg, poppler/Imagick, ClamAV, queue worker).
- Periksa cara kerja Admin Pusat saat ini terhadap dua akun sekaligus.
- Keluaran: `docs/revisi/00-PEMETAAN.md`.

**GATE 0:** tampilkan pemetaan, KEPUTUSAN BAWAAN yang perlu kukonfirmasi, dan daftar BUTUH KEPUTUSAN lain. Tunggu jawabanku.

---

## BUTIR 1 + 9: PEMERIKSAAN JENIS DAN ISI BERKAS DI SEMUA TITIK UNGGAH

Masalah: validasi hanya memeriksa ekstensi. Unggahan adalah vektor eksploitasi (web shell, berkas yang menyamar, ekstensi ganda, polyglot). Contoh nyata: video yang namanya diganti menjadi `.pdf` atau `.docx` lolos karena sistem percaya pada nama atau keterangan dari browser.

- Satu `SecureUploadService` (+ aturan validasi) yang dipakai SEMUA titik unggah. Tidak boleh ada `->store()` langsung di controller. Tambahkan test arsitektur/grep yang gagal jika ada unggahan di luar service.
- Allowlist per konteks (usulkan, minta persetujuanku): gambar (jpg/png), pdf, teks/log bila perlu, dan video MP4 sesuai keputusan.
- Keputusan jenis berkas HANYA dari ISI berkas (finfo + magic bytes + struktur), tidak dari ekstensi, Content-Type klien, atau properti/metadata dokumen. Pemeriksaan berlapis:
  1. MIME hasil finfo dan magic bytes harus cocok dengan ekstensi yang diizinkan.
  2. Gambar: decode lalu simpan ulang dengan GD/Imagick (membuang EXIF dan muatan tersembunyi), validasi dimensi dan piksel maksimum.
  3. PDF: header dan struktur valid; tolak/sanitasi yang memuat JavaScript, aksi Launch/OpenAction, atau lampiran tertanam.
  4. Teks/log: UTF-8 murni, tanpa byte biner, disajikan sebagai `text/plain`.
  5. Video (jika diizinkan): MP4 saja, container dan codec divalidasi dengan ffprobe, batas ukuran dan durasi dari konfigurasi.
  6. Tolak: ekstensi ganda, ekstensi berbahaya di bagian mana pun nama (php, phtml, phar, js, html, svg, exe, sh, bat, dll.), byte null, karakter kontrol atau RTL override pada nama, nama lebih dari 255 karakter, ukuran 0 atau melebihi batas konteks.
  7. Antarmuka pemindai antivirus (ClamAV) di belakang feature flag; jika tidak tersedia, kerangka + dokumentasi, jangan dipalsukan.
- Penyimpanan: disk privat di luar webroot, nama acak (UUID), nama asli hanya di DB, simpan SHA-256, folder unggahan tidak boleh mengeksekusi skrip (konfigurasi server).
- Penyajian: hanya lewat route berotorisasi; header `X-Content-Type-Options: nosniff`, `Content-Disposition` yang aman, `Cache-Control: no-store` untuk berkas terbatas, dan CSP sandbox.
- Pesan error bahasa Indonesia yang jelas ke pengguna ("Isi berkas tidak sesuai dengan jenisnya."); detail teknis hanya di log. Catat setiap penolakan (pengguna/IP, alasan) dan beri rate limit pada endpoint unggah.
- **Test wajib per titik unggah** (pelapor, Admin Pusat, Admin Bidang, Teknisi, impor), memakai sampel INERT dan nyata:
  1. Video MP4 berdurasi 1 detik (buat dengan ffmpeg) yang namanya diganti menjadi `.pdf`, `.docx`, `.jpg`, dan `.png`.
  2. Berkas teks biasa berekstensi `.jpg` dan `.pdf`.
  3. Arsip ZIP berekstensi `.docx`.
  4. Ekstensi ganda `foto.jpg.mp4` dan `laporan.pdf.php`.
  5. Content-Type dipalsukan ke `image/jpeg` pada isi non-gambar.
  6. SVG, berkas kosong, terlalu besar, nama berisi `../` dan byte null.
  Semuanya HARUS ditolak; berkas sah HARUS lolos. Simpan sebagai fixture permanen, dan buat test yang gagal bila ada titik unggah baru yang tidak memakai `SecureUploadService`.
- Berkas lama yang sudah tersimpan: buat perintah artisan pemindai ulang (hanya-lapor, tidak menghapus) yang memeriksa semua berkas lama dengan aturan baru.

---

## BUTIR 5 + 6: PERLAKUAN BERKAS UNTUK TLP RED DAN BERKAS RAHASIA

- Tambah klasifikasi per berkas: `umum | terbatas | rahasia` (kolom `classification`, alasan, siapa yang menetapkan). Pelapor dan teknisi dapat menandai "berisi data rahasia" saat unggah, sehingga berkas langsung masuk vault tanpa dibuka pihak lain; Admin Pusat dapat menaikkan klasifikasi saat verifikasi. Penurunan klasifikasi hanya oleh Admin Pusat dengan alasan, tercatat.
- Berkas rahasia disimpan di disk terpisah ("vault"), terenkripsi saat disimpan (pendekatan yang teruji, dokumentasikan manajemen dan rotasi kunci, kunci terpisah dari `APP_KEY`), dan HANYA dapat diakses Admin Bidang Persandian dan teknisi Persandian yang ditugaskan. Konfigurasi di `config/files.php`.
- Saat sebuah tiket ditetapkan TLP RED (atau menjadi RED), SEMUA lampiran yang sudah ada dan yang baru otomatis minimal berklasifikasi terbatas dan dipindah ke vault. Akses: tim pelaksana Persandian, Admin Pusat sesuai KEPUTUSAN BAWAAN no. 3, dan pihak yang diberi akses eksplisit per tiket (dengan alasan, tercatat).
- Bidang lain yang hanya diberi notifikasi RED TIDAK melihat berkas, pratinjau, maupun nama berkas; hanya jumlah lampiran.
- Tanpa thumbnail di daftar untuk berkas terbatas/rahasia. Pratinjau (butir 10) hanya muncul setelah pihak berwenang membuka detail tiket.
- Unduhan berkas asli lewat URL bertanda tangan berumur pendek (5 menit atau kurang); tidak ada unduh-semua; setiap akses dicatat (siapa, kapan, IP, berkas, hash).
- Jejak kustodi: hash SHA-256 saat unggah, dapat diverifikasi saat unduh; semua pemindahan, perubahan klasifikasi, dan akses menjadi event yang tidak dapat diubah.
- Pelapor tetap dapat melihat berkas miliknya sendiri dengan PIN.
- BUTUH KEPUTUSAN (konfigurasi, bukan hardcode): retensi dan penghapusan berkas. Jangan hapus otomatis.

---

## BUTIR 4: KOMPRESI OTOMATIS SEBELUM UNGGAH

- Kompresi di browser SEBELUM dikirim ke server untuk gambar (jpg/png): ubah ukuran sisi terpanjang maksimum sekitar 1920 px, kualitas adaptif sampai ukuran sekitar 1 MB atau kurang, buang metadata EXIF (termasuk GPS). Tampilkan pratinjau, ukuran sebelum dan sesudah, dan progres unggah.
- PDF dan video tidak dikompresi di browser: tampilkan batas ukuran dan pesan jelas jika melebihi.
- Jangan percaya klien: server tetap menjalankan seluruh pemeriksaan butir 1+9 dan menolak berkas yang melewati batas.
- Bukti insiden: berkas asli bisa penting. Sediakan opsi "unggah asli tanpa kompresi" (batas lebih besar, mis. 10 MB) untuk kategori insiden keamanan dan tiket yang berpotensi RED; hash asli dicatat untuk integritas.
- Gunakan pustaka yang ringan (mis. browser-image-compression), di-host sendiri (tanpa CDN saat runtime), sesuai kebijakan CSP.

---

## BUTIR 10: PRATINJAU BUKTI TANPA UNDUH

Tujuan: semua bukti pada tiket bisa langsung dilihat di halaman tiket tanpa mengunduh. Berlaku di halaman lacak pelapor (setelah PIN), verifikasi Admin Pusat, antrean dan detail Admin Bidang, serta tampilan Teknisi.

**Prinsip keamanan:** pratinjau TIDAK PERNAH menyajikan berkas asli ke browser. Yang disajikan adalah turunan (derivative) yang dibuat server dari berkas yang sudah lolos pemeriksaan butir 1+9.

**Pembuatan turunan** (queue job setelah unggah lolos; tampilkan status `diproses | siap | gagal`, tampilan otomatis memperbarui saat siap):
- Gambar: versi tampil (sisi terpanjang maksimum 1600 px, WebP atau JPEG kualitas sekitar 80) dan thumbnail kecil.
- PDF: rasterisasi per halaman ke gambar di server (poppler `pdftoppm` atau Imagick), maksimum 10 halaman pertama dengan resolusi wajar dan batas ukuran total; PDF tidak pernah dijalankan di browser. Jika lebih dari 10 halaman, tampilkan "10 dari N halaman".
- Video (jika diizinkan): salinan pratinjau MP4/H.264 hasil ffmpeg (resolusi dibatasi) dan satu gambar poster; disajikan dengan dukungan HTTP Range agar bisa digeser, tanpa autoplay.
- Teks/log: tampil sebagai blok teks dengan escape penuh, maksimum 200 KB pertama, ditandai "dipotong" bila lebih panjang.
- Turunan disimpan di disk privat terpisah (`previews`), mengikuti klasifikasi berkas induknya (berkas rahasia menghasilkan turunan di vault dan terenkripsi). Jika berkas dihapus atau klasifikasinya berubah, turunan ikut dihapus atau dipindah.

**Otorisasi dan penyajian:**
- Route pratinjau memakai Policy yang SAMA dengan berkas induknya; tidak ada tautan pratinjau yang memuat token permanen. Gunakan URL bertanda tangan berumur pendek atau sesi + Policy, dan jangan mengembalikan token apa pun di HTML publik (ini sekaligus menutup temuan `access_token` pada tautan lampiran).
- Admin Pusat pada berkas rahasia/RED: tanpa pratinjau (hanya metadata) sesuai keputusan; muncul tombol "Minta akses".
- Bidang ditembuskan pada tiket RED: tanpa pratinjau.
- Header penyajian: `X-Content-Type-Options: nosniff`, `Content-Disposition: inline` hanya untuk turunan aman (image/webp, image/jpeg, video/mp4, text/plain), CSP `default-src 'none'`, `Cache-Control: no-store` untuk berkas terbatas/rahasia. Jangan pernah menyajikan HTML, SVG, atau berkas asli secara inline.
- Berkas rahasia: beri watermark tipis di pratinjau (nama pengguna dan waktu).
- Pencatatan: setiap penayangan masuk event `file_previewed` (pengguna, berkas, waktu), dengan pembatasan agar satu pengguna dan satu berkas tidak mencatat berulang dalam beberapa menit.

**Tampilan (UI):**
- Galeri lampiran di detail tiket: kartu per berkas dengan thumbnail (kecuali terbatas/rahasia), nama, jenis, ukuran, dan penanda klasifikasi.
- Klik untuk membuka penampil: gambar dengan zoom dan geser, PDF per halaman dengan navigasi, video dengan pemutar, teks dengan blok kode. Panah kiri/kanan berpindah antarberkas dan Esc menutup.
- Bisa dipakai di HP (pinch-zoom), pemuatan malas (lazy loading), dan keadaan jelas: memuat, diproses, gagal ("Pratinjau tidak tersedia"), dan tidak berwenang.
- Aksesibilitas: label dan teks alternatif, fokus terperangkap di dalam penampil lalu kembali ke pemicu saat ditutup, kontras memadai, dapat dipakai dengan keyboard.
- Tombol "Unduh asli" bersifat sekunder dan hanya tampil untuk pihak berwenang; setiap unduhan dicatat.

**Operasional:** perbarui Dockerfile/compose (poppler-utils atau Imagick, ffmpeg) dan pastikan queue worker berjalan; beri batas ukuran dan jumlah turunan, kuota penyimpanan, dan perintah artisan untuk membuat ulang turunan (`previews:rebuild`).

**Test:**
- Pengguna tidak berwenang (lintas bidang, bidang ditembuskan, Admin Pusat pada berkas rahasia, pelapor lain, tanpa PIN) menerima 403/404 pada pratinjau.
- Pratinjau tidak pernah mengembalikan berkas asli (bandingkan hash dan tipe respons); SVG/HTML tidak dirender; PDF berpenanda JavaScript tidak mengeksekusi apa pun.
- Token pratinjau kedaluwarsa ditolak; tidak ada token permanen di HTML halaman publik.
- Permintaan Range pada video berjalan; turunan dibuat untuk tiap jenis; keadaan `gagal` ditampilkan dengan benar; turunan ikut pindah atau terhapus saat klasifikasi berubah.
- Event `file_previewed` tercatat tanpa duplikasi berlebihan.

---

## BUTIR 7: TLP RED LINTAS BIDANG

- Ganti perilaku "RED disiarkan ke semua bidang" dengan daftar eksplisit. Tabel `ticket_bidang_involvement`: `ticket_id`, `bidang_id`, `peran` (pelaksana | ditembuskan | pendukung), `notified_at`, `acknowledged_at`, `ditetapkan_oleh`.
- Untuk tiket RED: pelaksana SELALU Bidang Persandian (otomatis, tidak dapat diubah). Admin Pusat memilih 1 sampai N bidang lain sebagai ditembuskan saat verifikasi (N dan bidang bawaan di konfigurasi). Semua bidang yang terlibat menerima notifikasi (database notification + antarmuka kanal yang ada), tanpa detail sensitif.
- Bidang ditembuskan hanya melihat ringkasan terbatas: kode, kategori, status, SLA, dan ringkasan yang disanitasi; bukan deskripsi rinci atau berkas kecuali diberi akses eksplisit.
- Persandian dapat meminta dukungan: teknisi dari bidang lain menjadi PENDAMPING (bukan lead) dengan persetujuan Admin Bidang pemilik teknisi. Lead dan penanggung jawab penyelesaian tetap Persandian; hasil disetujui Admin Bidang Persandian.
- Perbarui aturan beban kerja dan penugasan: lead RED hanya teknisi Persandian; pendamping boleh lintas bidang.
- Hapus penentuan "Persandian" lewat LIKE nama/kode; pakai penanda eksplisit pada bidang (mis. kolom `is_persandian` atau kode tetap di konfigurasi).
- Test: bidang non-pelibat tidak melihat tiket RED sama sekali; bidang ditembuskan menerima notifikasi tetapi tidak melihat berkas; lead non-Persandian ditolak; pendamping lintas bidang berjalan dengan persetujuan.

---

## BUTIR 2: DUA ADMIN PUSAT SEKALIGUS

- Pastikan sistem aman dengan dua atau lebih akun Admin Pusat aktif bersamaan.
- Antrean verifikasi: "klaim" tiket dengan kunci lunak (kedaluwarsa otomatis, mis. 10 menit) dan penanda "sedang diverifikasi oleh X"; kunci optimistik (`row_version`) agar dua admin tidak memverifikasi tiket yang sama; tombol "ambil tiket berikutnya".
- Pembagian beban: tampilkan jumlah tiket yang ditangani per admin; opsi pembagian otomatis (round-robin) di konfigurasi.
- Tiket RED: kedua admin menerima notifikasi.
- Pengaman akun: sistem harus selalu memiliki minimal 1 Admin Pusat aktif; tidak boleh menghapus atau menurunkan peran Admin Pusat terakhir, termasuk diri sendiri; nonaktifkan, jangan hapus permanen.
- Aksi sensitif (force close, pengecualian SLA, ubah klasifikasi berkas, kelola pengguna) mencatat pelaku. BUTUH KEPUTUSAN: apakah aksi tertentu memerlukan persetujuan Admin Pusat kedua (four-eyes). Rekomendasi: ya untuk force close dan pengecualian SLA.
- Test dua sesi bersamaan: klaim ganda, verifikasi ganda, serah-terima saat kunci kedaluwarsa.

---

## VERIFIKASI (bukti, bukan klaim)

- Jalankan seluruh test, analisis statis, formatter, `migrate:fresh --seed`, `migrate:rollback` lalu migrate ulang. Tidak boleh ada test yang dilewati diam-diam.
- Uji end-to-end:
  1. Pelapor mengunggah gambar besar, terkompresi, tiket RED, berkas masuk vault.
  2. Pratinjau tampil untuk Persandian yang berwenang tanpa mengunduh, dan tidak tampil untuk pihak lain.
  3. Admin Pusat A dan B memproses antrean bersamaan.
  4. Bidang ditembuskan menerima notifikasi tanpa akses berkas atau pratinjau.
  5. Teknisi Persandian mengakses berkas, tercatat.
  6. Semua berkas jahat dan berkas yang disamarkan ditolak di setiap titik unggah.
- Catat status akhir tiap butir: Selesai-terverifikasi, Selesai-tidak-terverifikasi, Ditunda, atau Butuh-keputusan, lengkap dengan nama test dan keluaran.

## KELUARAN (`docs/revisi/`)

- `00-PEMETAAN.md`
- `01-KEPUTUSAN.md` (opsi, rekomendasi, jawabanku)
- `02-RENCANA.md`
- `03-PERUBAHAN.md` (apa, mengapa, dampak, cara rollback)
- `04-VERIFIKASI.md`
- `05-RINGKASAN.md` (satu halaman non-teknis dan risiko tersisa)

Akhiri tiap laporan dengan "Hal yang tidak bisa saya verifikasi".

---

## MULAI SEKARANG

Kerjakan FASE 0, lalu berhenti di GATE 0.
