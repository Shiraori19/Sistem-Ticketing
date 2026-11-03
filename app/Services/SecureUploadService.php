<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Ticket;
use App\Models\TicketFile;
use App\Services\Antivirus\ClamAvVirusScanner;
use App\Services\Antivirus\NullVirusScanner;
use App\Services\Antivirus\VirusScannerInterface;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SecureUploadService
{
    protected VirusScannerInterface $virusScanner;

    public function __construct(?VirusScannerInterface $virusScanner = null)
    {
        if ($virusScanner) {
            $this->virusScanner = $virusScanner;
        } elseif (config('files.antivirus.enabled', false)) {
            $this->virusScanner = new ClamAvVirusScanner();
        } else {
            $this->virusScanner = new NullVirusScanner();
        }
    }

    /**
     * Memproses unggahan dan mengembalikan entri model TicketFile
     * Mengonversi InvalidArgumentException ke ValidationException untuk kemudahan controller & test validation
     */
    public function processUpload(UploadedFile $file, string $kategoriField = 'lampiran_tiket', ?Ticket $ticket = null, array $extraOptions = []): TicketFile
    {
        $folder = $ticket ? "tickets/{$ticket->kode_tiket}" : 'uploads';
        $options = array_merge([
            'ticket' => $ticket,
            'kategori_field' => $kategoriField,
        ], $extraOptions);

        try {
            $stored = $this->storeUploadedFile($file, $folder, $options);
        } catch (\InvalidArgumentException $e) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'file' => [$e->getMessage()],
            ]);
        }

        $tf = null;
        if ($ticket) {
            $tf = TicketFile::where('path', $stored['path'])->first();
        }

        if (!$tf) {
            $tf = TicketFile::create([
                'ticket_id'      => $ticket?->id,
                'path'           => $stored['path'],
                'kategori'       => $kategoriField,
                'mime'           => $stored['mime'],
                'size'           => $stored['size'],
                'sha256'         => $stored['sha256'],
                'original_name'  => $stored['original_name'],
                'classification' => $stored['classification'],
                'is_encrypted'   => $stored['is_encrypted'],
                'vault_path'     => $stored['vault_path'],
            ]);
        }

        return $tf;
    }

    /**
     * Titik Masuk Utama Penyimpanan Berkas Aman di Seluruh Sistem
     *
     * @param UploadedFile $file
     * @param string $folder Direktori relatif penyimpanan (misal: 'tickets/TK-20261021-0001')
     * @param array $options Opsi konteks (ticket, classification, allowed_categories, is_raw, uploader_id, kategori_field)
     * @return array Metadata berkas hasil simpan terverifikasi
     * @throws InvalidArgumentException Jika berkas tidak valid / membahayakan
     */
    public function storeUploadedFile(UploadedFile $file, string $folder = 'tickets', array $options = []): array
    {
        // 1. Validasi Keabsahan dan Nama Berkas Asli
        $originalName = $file->getClientOriginalName();
        $this->validateFilename($originalName);
        $options['original_name'] = $originalName;
        $options['client_extension'] = strtolower($file->getClientOriginalExtension());

        $realPath = $file->getRealPath();
        if (!$realPath || !file_exists($realPath)) {
            throw new InvalidArgumentException('Berkas fisik sementara tidak ditemukan.');
        }

        $fileSize = (int) $file->getSize();
        if ($fileSize <= 0) {
            throw new InvalidArgumentException('Ukuran berkas 0 byte tidak diizinkan.');
        }

        // 2. Pemeriksaan Isi Asli (Finfo + Magic Bytes + Struktur)
        $detectedInfo = $this->inspectFileContent($realPath, $fileSize, $options);
        $detectedExt = $detectedInfo['extension'];
        $detectedMime = $detectedInfo['mime'];
        $category = $detectedInfo['category'];

        // 3. Batas Ukuran per Konteks
        $maxSizeKb = $options['max_size_kb'] ?? config("files.max_size_kb.{$category}", config('files.max_size_kb.default', 5120));
        if ($fileSize > ($maxSizeKb * 1024)) {
            $maxMb = round($maxSizeKb / 1024, 1);
            throw new InvalidArgumentException("Ukuran berkas melebihi batas maksimum ({$maxMb} MB).");
        }

        // 4. Pemindaian Antivirus
        if (!$this->virusScanner->scan($file)) {
            Log::alert("Penolakan unggahan: Berkas terinfeksi malware [{$originalName}] dari IP: " . request()->ip());
            throw new InvalidArgumentException('Berkas ditolak oleh sistem keamanan antivirus.');
        }

        // 5. Sanitasi dan Pembersihan Spesifik Format
        $cleanedTempPath = null;
        if ($category === 'image') {
            $this->validateImageStructure($realPath, $detectedExt);
            $cleanedTempPath = $this->sanitizeImage($realPath, $detectedExt, $options);
            $pathToStore = $cleanedTempPath;
            $finalSize = filesize($cleanedTempPath);
        } elseif ($category === 'pdf') {
            $this->validatePdfStructure($realPath);
            $pathToStore = $realPath;
            $finalSize = $fileSize;
        } elseif ($category === 'text') {
            $this->validateTextStructure($realPath);
            $pathToStore = $realPath;
            $finalSize = $fileSize;
        } elseif ($category === 'video') {
            $this->validateVideoStructure($realPath);
            $pathToStore = $realPath;
            $finalSize = $fileSize;
        } else {
            $pathToStore = $realPath;
            $finalSize = $fileSize;
        }

        // 6. Tentukan Klasifikasi dan Disk Penyimpanan (Vault vs Private)
        $ticket = $options['ticket'] ?? null;
        $isTlpRed = $ticket && strtoupper($ticket->tlp_level ?? '') === 'RED';
        $requestedClassification = strtolower($options['classification'] ?? 'umum');

        if ($isTlpRed) {
            $classification = 'rahasia';
        } elseif (in_array($requestedClassification, ['rahasia', 'terbatas'], true)) {
            $classification = $requestedClassification;
        } else {
            $classification = 'umum';
        }

        $isSecret = ($classification === 'rahasia' || $isTlpRed);
        $targetDisk = $isSecret ? config('files.vault_disk', 'vault') : config('files.default_disk', 'private');

        // 7. Hitung Hash SHA-256 Integritas Kustodi
        $contentToStore = file_get_contents($pathToStore);
        $sha256 = hash('sha256', $contentToStore);

        // Enkripsi at-rest jika masuk ke Vault
        $isEncrypted = false;
        if ($isSecret) {
            $contentToStore = $this->encryptPayload($contentToStore);
            $isEncrypted = true;
        }

        // 8. Tulis Berkas ke Disk dengan Nama Acak UUID
        $uuid = (string) Str::uuid();
        $storedFilename = "{$uuid}.{$detectedExt}";
        $cleanFolder = trim(str_replace(['\\', '..'], ['/', ''], $folder), '/');
        $storedRelativePath = "{$cleanFolder}/{$storedFilename}";

        Storage::disk($targetDisk)->put($storedRelativePath, $contentToStore);

        // Hapus file pembersihan sementara jika ada
        if ($cleanedTempPath && file_exists($cleanedTempPath)) {
            @unlink($cleanedTempPath);
        }

        $result = [
            'path'           => $storedRelativePath,
            'disk'           => $targetDisk,
            'original_name'  => $originalName,
            'mime'           => $detectedMime,
            'size'           => $finalSize,
            'sha256'         => $sha256,
            'classification' => $classification,
            'is_encrypted'   => $isEncrypted,
            'vault_path'     => $isSecret ? $storedRelativePath : null,
            'uuid'           => $uuid,
        ];

        // 9. Jika ada objek Ticket dan kategori field, langsung catat ke entri ticket_files
        if ($ticket instanceof Ticket) {
            $kategoriField = $options['kategori_field'] ?? 'lampiran_tiket';
            $uploaderId = $options['uploader_id'] ?? auth()->id();

            try {
                TicketFile::create([
                    'ticket_id'             => $ticket->id,
                    'event_id'              => $options['event_id'] ?? null,
                    'uploader_id'           => $uploaderId,
                    'path'                  => $storedRelativePath,
                    'mime'                  => $detectedMime,
                    'size'                  => $finalSize,
                    'kategori'              => $kategoriField,
                    'sha256'                => $sha256,
                    'original_name'         => $originalName,
                    'classification'        => $classification,
                    'classification_reason' => $options['classification_reason'] ?? ($isTlpRed ? 'Otomatis TLP RED' : null),
                    'classified_by'         => $uploaderId,
                    'is_encrypted'          => $isEncrypted,
                    'vault_path'            => $isSecret ? $storedRelativePath : null,
                ]);
            } catch (\Throwable $e) {
                Log::warning("Gagal menyimpan riwayat ticket_files: " . $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * Validasi Nama Berkas secara Ketat
     */
    public function validateFilename(string $filename): void
    {
        if (empty($filename) || strlen($filename) > 255) {
            throw new InvalidArgumentException('Nama berkas tidak valid atau melebihi 255 karakter.');
        }

        // Tolak byte null dan karakter kontrol terlarang
        if (preg_match('/[\x00-\x1F\x7F\x{202E}\x{200E}\x{200F}]/u', $filename)) {
            throw new InvalidArgumentException('Nama berkas memuat karakter kontrol atau byte null terlarang.');
        }

        // Tolak path traversal
        if (str_contains($filename, '..') || str_contains($filename, '/') || str_contains($filename, '\\')) {
            throw new InvalidArgumentException('Nama berkas memuat karakter path traversal.');
        }

        $parts = explode('.', $filename);
        if (count($parts) < 2) {
            throw new InvalidArgumentException('Berkas wajib memiliki ekstensi yang valid.');
        }

        // Tolak ekstensi ganda (misal: foto.jpg.mp4, laporan.pdf.php)
        if (count($parts) > 2) {
            throw new InvalidArgumentException('Format berkas dengan ekstensi ganda tidak diizinkan demi keamanan.');
        }

        $ext = strtolower(end($parts));
        $blocked = config('files.blocked_extensions', []);

        // Cek apakah ekstensi ada dalam daftar terlarang
        if (in_array($ext, $blocked, true)) {
            if (in_array($ext, ['doc', 'docx', 'odt'], true)) {
                throw new InvalidArgumentException('Format dokumen Office (DOCX/DOC/ODT) tidak diizinkan demi keamanan. Silakan konversi dokumen menjadi format PDF.');
            }
            throw new InvalidArgumentException('Ekstensi berkas berbahaya dan dilarang oleh sistem keamanan.');
        }
    }

    /**
     * Pemeriksaan Mendalam Isi Berkas (Content-Based)
     * Menggunakan Whitelist Ketat: Ekstensi, MIME Type, dan File Signature / Magic Bytes
     */
    public function inspectFileContent(string $path, int $size, array $options = []): array
    {
        $clientExt = strtolower($options['client_extension'] ?? pathinfo($options['original_name'] ?? '', PATHINFO_EXTENSION));
        $allowedCategories = $options['allowed_categories'] ?? null;

        // 1. Pemetaan Whitelist Ekstensi Resmi per Kategori
        $categoryExtMap = [
            'image' => ['jpg', 'jpeg', 'png'],
            'pdf'   => ['pdf'],
            'text'  => ['txt', 'log'],
            'video' => ['mp4'],
        ];

        // Validasi Whitelist Ekstensi Nama Berkas di Awal (jika kategori dibatasi)
        if (is_array($allowedCategories)) {
            $allowedExtensions = [];
            foreach ($allowedCategories as $cat) {
                if (isset($categoryExtMap[$cat])) {
                    $allowedExtensions = array_merge($allowedExtensions, $categoryExtMap[$cat]);
                }
            }
            $allowedExtensions = array_unique($allowedExtensions);

            if ($clientExt && !in_array($clientExt, $allowedExtensions, true)) {
                $allowedList = strtoupper(implode(', ', $allowedExtensions));
                throw new InvalidArgumentException("Format ekstensi berkas (.{$clientExt}) tidak diizinkan. Ekstensi yang diperbolehkan: {$allowedList}.");
            }
        }

        // 2. Deteksi MIME Asli dari Isi Berkas (finfo)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $realMime = finfo_file($finfo, $path);
        finfo_close($finfo);

        // 3. Baca File Signature / Magic Bytes (16 byte pertama)
        $handle = fopen($path, 'rb');
        $magicBytes = fread($handle, 16);
        fclose($handle);

        $matchedExt = null;
        $matchedCategory = null;

        // 4. Verifikasi Kecocokan Signature (Magic Bytes) + MIME Whitelist
        if (str_starts_with($magicBytes, "\xFF\xD8\xFF")) {
            // JPEG / JPG: Signature FF D8 FF
            if (in_array($realMime, ['image/jpeg', 'image/pjpeg'], true)) {
                $matchedExt = 'jpg';
                $matchedCategory = 'image';
            }
        } elseif (str_starts_with($magicBytes, "\x89PNG\r\n\x1a\n")) {
            // PNG: Signature 89 50 4E 47 0D 0A 1A 0A
            if ($realMime === 'image/png') {
                $matchedExt = 'png';
                $matchedCategory = 'image';
            }
        } elseif (str_starts_with($magicBytes, "%PDF-")) {
            // PDF: Signature 25 50 44 46 2D (%PDF-)
            if ($realMime === 'application/pdf') {
                $matchedExt = 'pdf';
                $matchedCategory = 'pdf';
            }
        } elseif (str_contains($magicBytes, 'ftyp')) {
            // MP4: Kontainer video 'ftyp'
            if ($realMime === 'video/mp4' || $realMime === 'application/mp4') {
                $matchedExt = 'mp4';
                $matchedCategory = 'video';
            }
        } elseif ($realMime === 'text/plain') {
            // Teks murni / log (hanya jika UTF-8 murni dan tanpa byte null)
            if (!str_contains($magicBytes, "\0")) {
                $matchedExt = 'txt';
                $matchedCategory = 'text';
            }
        }

        if (!$matchedExt || !$matchedCategory) {
            throw new InvalidArgumentException('Isi berkas tidak sesuai dengan jenisnya atau signature (magic bytes) dan MIME tidak cocok.');
        }

        // 5. Validasi Kesesuaian Ekstensi vs Konten Sebenarnya (Anti-Disguised / Anti-Spoofing)
        if ($clientExt) {
            $allowedForExt = match($matchedExt) {
                'jpg' => ['jpg', 'jpeg'],
                'png' => ['png'],
                'pdf' => ['pdf'],
                'mp4' => ['mp4'],
                'txt' => ['txt', 'log', 'csv', 'json'],
                default => [$matchedExt],
            };

            if (!in_array($clientExt, $allowedForExt, true)) {
                throw new InvalidArgumentException("Ekstensi berkas (.{$clientExt}) tidak cocok dengan isi konten aslinya (.{$matchedExt}). Pemalsuan format terdeteksi.");
            }
        }

        // 6. Validasi Pembatasan Kategori Pemanggil
        if (is_array($allowedCategories) && !in_array($matchedCategory, $allowedCategories, true)) {
            $allowedList = implode(', ', $allowedCategories);
            throw new InvalidArgumentException("Jenis berkas [{$matchedCategory}] tidak diizinkan untuk konteks ini. Diizinkan: {$allowedList}.");
        }

        return [
            'extension' => $matchedExt,
            'mime'      => $realMime,
            'category'  => $matchedCategory,
        ];
    }

    /**
     * Validasi Struktur Integritas Gambar Mendalam
     * Memastikan header, dimensi, MIME internal, dan parsing GD valid (bukan file palsu ber-magic-bytes)
     */
    public function validateImageStructure(string $path, string $ext): void
    {
        $imageInfo = @getimagesize($path);
        if ($imageInfo === false) {
            throw new InvalidArgumentException('Berkas gambar rusak, corrupt, atau bukan gambar valid.');
        }

        $detectedMime = $imageInfo['mime'] ?? '';
        $expectedMime = match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            default => null,
        };

        if ($expectedMime && $detectedMime !== $expectedMime) {
            throw new InvalidArgumentException("Format internal gambar ({$detectedMime}) tidak sesuai dengan ekstensi (.{$ext}).");
        }

        // Pastikan pustaka GD dapat mem-parsing data piksel gambar secara penuh jika ekstensi GD tersedia
        if (function_exists('imagecreatefromstring')) {
            $raw = file_get_contents($path);
            $gdImg = @imagecreatefromstring($raw);
            if (!$gdImg) {
                throw new InvalidArgumentException('Isi data gambar tidak dapat diproses atau mengalami kerusakan.');
            }
            imagedestroy($gdImg);
        }
    }

    /**
     * Sanitasi Gambar: Decode lalu Encode ulang dengan GD (Membersihkan EXIF, GPS, Muatan Tersembunyi)
     * Otomatis mengompresi dan menurunkan resolusi ke ukuran di bawah 1 MB (Maksimal 1920px),
     * kecuali opsi 'is_raw' bernilai true (atas permintaan forensik keamanan DKIS).
     */
    public function sanitizeImage(string $path, string $ext, array $options = []): string
    {
        if (!function_exists('imagecreatefromstring') || !function_exists('imagecreatetruecolor')) {
            $tempPath = tempnam(sys_get_temp_dir(), 'sec_img_');
            copy($path, $tempPath);
            return $tempPath;
        }

        $rawContent = file_get_contents($path);
        $image = @imagecreatefromstring($rawContent);

        if (!$image) {
            throw new InvalidArgumentException('Isi berkas gambar rusak atau tidak dapat diproses.');
        }

        $width = imagesx($image);
        $height = imagesy($image);

        $maxWidth = config('files.image.max_width', 6000);
        $maxHeight = config('files.image.max_height', 6000);

        if ($width > $maxWidth || $height > $maxHeight) {
            imagedestroy($image);
            throw new InvalidArgumentException("Dimensi gambar ({$width}x{$height} piksel) melebihi batas maksimum {$maxWidth}x{$maxHeight}.");
        }

        $isRaw = (bool) ($options['is_raw'] ?? false);
        $tempPath = tempnam(sys_get_temp_dir(), 'sec_img_');

        // Jika opsi "Kualitas Asli" aktif (misal investigasi insiden DKIS), pertahankan dimensi asli
        if ($isRaw) {
            if ($ext === 'png') {
                imagealphablending($image, false);
                imagesavealpha($image, true);
                imagepng($image, $tempPath, 8);
            } else {
                imagejpeg($image, $tempPath, 88);
            }
            imagedestroy($image);
            return $tempPath;
        }

        // Mode Standar (Otomatis Kompresi di bawah 1 MB)
        // 1. Resample gambar ke resolusi optimal (maksimal 1920px pada sisi terpanjang)
        $targetDim = 1920;
        $workImage = $image;

        if ($width > $targetDim || $height > $targetDim) {
            $ratio = min($targetDim / $width, $targetDim / $height);
            $newWidth = (int) max(1, round($width * $ratio));
            $newHeight = (int) max(1, round($height * $ratio));

            $resized = imagecreatetruecolor($newWidth, $newHeight);
            if ($ext === 'png') {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
                imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
            }
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            $workImage = $resized;
        }

        $maxTargetBytes = 1024 * 1024; // Batas Maksimal 1 MB (1.048.576 bytes)

        if ($ext === 'png') {
            imagealphablending($workImage, false);
            imagesavealpha($workImage, true);
            imagepng($workImage, $tempPath, 9); // Kompresi PNG maksimal

            // Jika berkas PNG masih melebihi 1 MB (misal screenshot sangat detail), downscale ke 1280px
            if (filesize($tempPath) > $maxTargetBytes) {
                $curW = imagesx($workImage);
                $curH = imagesy($workImage);
                if ($curW > 1280 || $curH > 1280) {
                    $ratio2 = min(1280 / $curW, 1280 / $curH);
                    $newW2 = (int) max(1, round($curW * $ratio2));
                    $newH2 = (int) max(1, round($curH * $ratio2));

                    $resized2 = imagecreatetruecolor($newW2, $newH2);
                    imagealphablending($resized2, false);
                    imagesavealpha($resized2, true);
                    $transparent2 = imagecolorallocatealpha($resized2, 0, 0, 0, 127);
                    imagefilledrectangle($resized2, 0, 0, $newW2, $newH2, $transparent2);
                    imagecopyresampled($resized2, $workImage, 0, 0, 0, 0, $newW2, $newH2, $curW, $curH);
                    imagepng($resized2, $tempPath, 9);
                    if ($workImage !== $image) {
                        imagedestroy($workImage);
                    }
                    $workImage = $resized2;
                }
            }
        } else {
            // Format JPEG: Simpan dengan kualitas adaptif agar ukuran di bawah 1 MB
            $quality = 80;
            imagejpeg($workImage, $tempPath, $quality);

            // Jika masih > 1MB, turunkan kualitas bertahap
            while (filesize($tempPath) > $maxTargetBytes && $quality > 50) {
                $quality -= 10;
                imagejpeg($workImage, $tempPath, $quality);
            }

            // Jika masih > 1MB pada quality 50, downscale dimensi ke 1280px
            if (filesize($tempPath) > $maxTargetBytes) {
                $curW = imagesx($workImage);
                $curH = imagesy($workImage);
                if ($curW > 1280 || $curH > 1280) {
                    $ratio2 = min(1280 / $curW, 1280 / $curH);
                    $newW2 = (int) max(1, round($curW * $ratio2));
                    $newH2 = (int) max(1, round($curH * $ratio2));

                    $resized2 = imagecreatetruecolor($newW2, $newH2);
                    imagecopyresampled($resized2, $workImage, 0, 0, 0, 0, $newW2, $newH2, $curW, $curH);
                    imagejpeg($resized2, $tempPath, 72);
                    if ($workImage !== $image) {
                        imagedestroy($workImage);
                    }
                    $workImage = $resized2;
                }
            }
        }

        if ($workImage !== $image) {
            imagedestroy($workImage);
        }
        imagedestroy($image);

        return $tempPath;
    }

    /**
     * Validasi Struktur Dokumen PDF (Tolak JavaScript, Launch Action, dan Lampiran Berbahaya)
     */
    public function validatePdfStructure(string $path): void
    {
        $content = file_get_contents($path);

        // Periksa keberadaan penanda awal dan akhir PDF
        if (!str_starts_with($content, '%PDF-')) {
            throw new InvalidArgumentException('Header dokumen PDF tidak valid.');
        }

        // Cek muatan aktif berbahaya
        $dangerousPatterns = [
            '/\/JavaScript/i'   => 'JavaScript tertanam',
            '/\/JS\b/i'         => 'Skrip JS tertanam',
            '/\/Launch\b/i'     => 'Aksi peluncuran eksekusi (Launch Action)',
            '/\/OpenAction/i'   => 'Aksi otomatis pembukaan (OpenAction)',
            '/\/EmbeddedFiles/i'=> 'Lampiran biner tertanam (EmbeddedFiles)',
        ];

        foreach ($dangerousPatterns as $pattern => $label) {
            if (preg_match($pattern, $content)) {
                Log::warning("PDF ditolak karena memuat {$label}: {$path}");
                throw new InvalidArgumentException("Dokumen PDF ditolak karena memuat elemen aktif terlarang ({$label}).");
            }
        }
    }

    /**
     * Validasi Berkas Teks / Log (Harus UTF-8 Murni tanpa Byte Biner)
     */
    protected function validateTextStructure(string $path): void
    {
        $content = file_get_contents($path);

        if (!mb_check_encoding($content, 'UTF-8')) {
            throw new InvalidArgumentException('Isi berkas teks/log harus berformat pengkodean UTF-8 murni.');
        }

        if (str_contains($content, "\0")) {
            throw new InvalidArgumentException('Berkas teks memuat karakter biner (null byte) yang dilarang.');
        }
    }

    /**
     * Validasi Berkas Video MP4
     */
    protected function validateVideoStructure(string $path): void
    {
        $header = file_get_contents($path, false, null, 0, 64);
        if (!str_contains($header, 'ftyp')) {
            throw new InvalidArgumentException('Kontainer video bukan format MP4 yang sah.');
        }
    }

    /**
     * Enkripsi Berkas Vault (AES-256-CBC dengan Kunci Khusus Vault)
     */
    public function encryptPayload(string $data): string
    {
        $cipher = 'aes-256-cbc';
        $key = hash('sha256', config('files.vault_encryption_key', config('app.key')), true);
        $ivLength = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivLength);

        $encrypted = openssl_encrypt($data, $cipher, $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . ':::' . $encrypted);
    }

    /**
     * Dekripsi Berkas Vault
     */
    public function decryptPayload(string $encryptedData): string
    {
        $cipher = 'aes-256-cbc';
        $key = hash('sha256', config('files.vault_encryption_key', config('app.key')), true);
        $decoded = base64_decode($encryptedData);

        if (!str_contains($decoded, ':::')) {
            return $encryptedData; // Fallback jika belum terenkripsi
        }

        [$iv, $encrypted] = explode(':::', $decoded, 2);
        return (string) openssl_decrypt($encrypted, $cipher, $key, OPENSSL_RAW_DATA, $iv);
    }

    /**
     * Pindahkan berkas yang ada ke Vault (Otomatis saat tiket menjadi TLP RED)
     */
    public function migrateTicketFilesToVault(Ticket $ticket): void
    {
        $files = TicketFile::where('ticket_id', $ticket->id)->get();

        foreach ($files as $file) {
            if ($file->is_encrypted && $file->vault_path) {
                continue;
            }

            $currentPath = $file->path;
            if (Storage::disk('private')->exists($currentPath)) {
                $content = Storage::disk('private')->get($currentPath);
                $encryptedContent = $this->encryptPayload($content);

                $vaultPath = "vault/{$file->ticket_id}/" . basename($currentPath);
                Storage::disk('vault')->put($vaultPath, $encryptedContent);
                Storage::disk('private')->delete($currentPath);

                $file->update([
                    'vault_path'            => $vaultPath,
                    'is_encrypted'          => true,
                    'classification'        => 'rahasia',
                    'classification_reason' => 'Dipindahkan otomatis ke vault karena eskalasi TLP RED',
                ]);
            }
        }
    }
}
