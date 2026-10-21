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
            $cleanedTempPath = $this->sanitizeImage($realPath, $detectedExt);
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
     */
    protected function inspectFileContent(string $path, int $size, array $options): array
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $realMime = finfo_file($finfo, $path);
        finfo_close($finfo);

        $allowedTypes = config('files.allowed_types', []);
        $matchedExt = null;
        $matchedCategory = null;

        // Baca magic bytes 16 byte pertama
        $handle = fopen($path, 'rb');
        $magicBytes = fread($handle, 16);
        fclose($handle);

        // Verifikasi Magic Bytes spesifik
        if (str_starts_with($magicBytes, "\xFF\xD8\xFF")) {
            // JPEG Image
            if ($realMime === 'image/jpeg') {
                $matchedExt = 'jpg';
                $matchedCategory = 'image';
            }
        } elseif (str_starts_with($magicBytes, "\x89PNG\r\n\x1a\n")) {
            // PNG Image
            if ($realMime === 'image/png') {
                $matchedExt = 'png';
                $matchedCategory = 'image';
            }
        } elseif (str_starts_with($magicBytes, "%PDF-")) {
            // PDF Document
            if ($realMime === 'application/pdf') {
                $matchedExt = 'pdf';
                $matchedCategory = 'pdf';
            }
        } elseif (str_contains($magicBytes, 'ftyp')) {
            // MP4 Video Container
            if ($realMime === 'video/mp4' || $realMime === 'application/mp4') {
                $matchedExt = 'mp4';
                $matchedCategory = 'video';
            }
        } elseif ($realMime === 'text/plain') {
            // Plain text or log
            $matchedExt = 'txt';
            $matchedCategory = 'text';
        }

        if (!$matchedExt || !$matchedCategory) {
            throw new InvalidArgumentException('Isi berkas tidak sesuai dengan jenisnya atau format tidak diizinkan.');
        }

        // Validasi kesesuaian ekstensi nama berkas dengan konten sebenarnya (Anti-Disguised File)
        $clientExt = strtolower($options['client_extension'] ?? pathinfo($options['original_name'] ?? '', PATHINFO_EXTENSION));
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

        // Validasi pembatasan kategori jika ditentukan konteks pemanggil
        $allowedCategories = $options['allowed_categories'] ?? null;
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
     * Sanitasi Gambar: Decode lalu Encode ulang dengan GD (Membersihkan EXIF, GPS, Muatan Tersembunyi)
     */
    protected function sanitizeImage(string $path, string $ext): string
    {
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

        $tempPath = tempnam(sys_get_temp_dir(), 'sec_img_');

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

    /**
     * Validasi Struktur Dokumen PDF (Tolak JavaScript, Launch Action, dan Lampiran Berbahaya)
     */
    protected function validatePdfStructure(string $path): void
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
