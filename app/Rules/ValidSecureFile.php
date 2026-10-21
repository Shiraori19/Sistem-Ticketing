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

        /** @var SecureUploadService $uploadService */
        $uploadService = app(SecureUploadService::class);

        try {
            // 1. Validasi nama berkas
            $uploadService->validateFilename($value->getClientOriginalName());

            // 2. Validasi isi berkas
            $realPath = $value->getRealPath();
            if (!$realPath || !file_exists($realPath)) {
                $fail('Berkas fisik tidak ditemukan.');
                return;
            }

            $size = (int) $value->getSize();
            if ($size <= 0) {
                $fail('Ukuran berkas 0 byte tidak diizinkan.');
                return;
            }

            // Refleksi method protected inspectFileContent
            $reflection = new \ReflectionClass($uploadService);
            $method = $reflection->getMethod('inspectFileContent');
            $method->setAccessible(true);
            $info = $method->invoke($uploadService, $realPath, $size, [
                'allowed_categories' => $this->allowedCategories,
            ]);

            // Jika PDF, periksa struktur aktif
            if ($info['category'] === 'pdf') {
                $pdfMethod = $reflection->getMethod('validatePdfStructure');
                $pdfMethod->setAccessible(true);
                $pdfMethod->invoke($uploadService, $realPath);
            }
        } catch (\InvalidArgumentException $e) {
            $fail($e->getMessage());
        } catch (\Throwable $e) {
            $fail('Isi berkas tidak sesuai dengan jenisnya atau rusak.');
        }
    }
}
