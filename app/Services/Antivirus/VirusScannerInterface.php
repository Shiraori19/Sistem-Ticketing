<?php

namespace App\Services\Antivirus;

use Illuminate\Http\UploadedFile;

interface VirusScannerInterface
{
    /**
     * Pindai berkas apakah terinfeksi malware / virus.
     * Mengembalikan true jika bersih, false atau lempar exception jika terinfeksi.
     */
    public function scan(UploadedFile|string $file): bool;

    /**
     * Cek apakah scanner aktif dan siap digunakan.
     */
    public function isAvailable(): bool;
}
