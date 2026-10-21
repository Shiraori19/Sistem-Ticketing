<?php

namespace App\Services\Antivirus;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class ClamAvVirusScanner implements VirusScannerInterface
{
    protected string $socket;

    public function __construct(?string $socket = null)
    {
        $this->socket = $socket ?: config('files.antivirus.clamav_socket', '/var/run/clamav/clamd.ctl');
    }

    public function scan(UploadedFile|string $file): bool
    {
        $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;

        if (!file_exists($path)) {
            return false;
        }

        if (!$this->isAvailable()) {
            Log::warning("ClamAV socket tidak tersedia di [{$this->socket}], melewatkan pemindaian antivirus.");
            return true;
        }

        try {
            $socket = @socket_create(AF_UNIX, SOCK_STREAM, 0);
            if (!$socket || !@socket_connect($socket, $this->socket)) {
                return true;
            }

            $command = "SCAN " . realpath($path) . "\n";
            socket_write($socket, $command, strlen($command));
            $response = socket_read($socket, 2048);
            socket_close($socket);

            if (str_contains((string)$response, 'FOUND')) {
                Log::alert("Malware terdeteksi pada berkas: {$path}. Respons ClamAV: {$response}");
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error("Error saat memindai dengan ClamAV: " . $e->getMessage());
            return true; // fail-open atau fail-safe sesuai kebijakan
        }
    }

    public function isAvailable(): bool
    {
        return file_exists($this->socket);
    }
}
