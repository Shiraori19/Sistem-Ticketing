<?php

namespace App\Rules;

use App\Services\SecureUploadService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class ValidSecureFile implements ValidationRule
{
    protected array $allowedCategories;
    protected ?int $maxSizeKb;

    public function __construct(array $allowedCategories = ['image', 'pdf'], ?int $maxSizeKb = null)
    {
        $this->allowedCategories = $allowedCategories;
        $this->maxSizeKb = $maxSizeKb;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value instanceof UploadedFile) {
            return;
        }

        if (!$value->isValid()) {
            $fail('Berkas yang diunggah tidak valid atau proses pengunggahan terputus.');
            return;
        }

        /** @var SecureUploadService $uploadService */
        $uploadService = app(SecureUploadService::class);

        try {
            $originalName = $value->getClientOriginalName();
            $clientExt = strtolower($value->getClientOriginalExtension());

            // 1. Validasi nama berkas (ekstensi ganda, path traversal, karakter kontrol, blacklist)
            $uploadService->validateFilename($originalName);

            // 2. Validasi berkas fisik dan ukuran
            $realPath = $value->getRealPath();
            if (!$realPath || !file_exists($realPath)) {
                $fail('Berkas fisik sementara tidak ditemukan.');
                return;
            }

            $size = (int) $value->getSize();
            if ($size <= 0) {
                $fail('Ukuran berkas 0 byte tidak diizinkan.');
                return;
            }

            if ($this->maxSizeKb && $size > ($this->maxSizeKb * 1024)) {
                $maxMb = round($this->maxSizeKb / 1024, 1);
                $fail("Ukuran berkas melebihi batas maksimal ({$maxMb} MB).");
                return;
            }

            // 3. Pemeriksaan isi berkas secara ketat (Whitelist Ekstensi, MIME, dan Magic Bytes / Signature)
            $info = $uploadService->inspectFileContent($realPath, $size, [
                'allowed_categories' => $this->allowedCategories,
                'original_name'      => $originalName,
                'client_extension'   => $clientExt,
            ]);

            // 4. Validasi integritas dan struktur spesifik format (mencegah payload terselubung)
            if ($info['category'] === 'image') {
                $uploadService->validateImageStructure($realPath, $info['extension']);
            } elseif ($info['category'] === 'pdf') {
                $uploadService->validatePdfStructure($realPath);
            } elseif ($info['category'] === 'text') {
                $uploadService->validateTextStructure($realPath);
            } elseif ($info['category'] === 'video') {
                $uploadService->validateVideoStructure($realPath);
            }
        } catch (\InvalidArgumentException $e) {
            $fail($e->getMessage());
        } catch (\Throwable $e) {
            $fail('Isi berkas tidak sesuai dengan jenisnya atau mengalami kerusakan.');
        }
    }
}
