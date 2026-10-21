<?php

namespace App\Services\Antivirus;

use Illuminate\Http\UploadedFile;

class NullVirusScanner implements VirusScannerInterface
{
    public function scan(UploadedFile|string $file): bool
    {
        // Dalam mode null/development, semua berkas lolos
        return true;
    }

    public function isAvailable(): bool
    {
        return true;
    }
}
