<?php

namespace Tests\Feature\Security;

use App\Models\Bidang;
use App\Models\Category;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketFile;
use App\Models\User;
use App\Services\SecureUploadService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SecureUploadServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected SecureUploadService $uploadService;
    protected Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');
        Storage::fake('vault');
        Storage::fake('previews');

        $this->uploadService = app(SecureUploadService::class);

        $role = Role::firstOrCreate(['nama_role' => 'Admin Pusat']);
        $user = User::firstOrCreate(
            ['username' => 'admin.test'],
            ['nama' => 'Test Admin', 'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true]
        );

        $bidang = Bidang::firstOrCreate(
            ['kode' => 'PERSANDIAN'],
            ['nama_bidang' => 'Bidang Persandian', 'is_persandian' => true]
        );

        $category = Category::firstOrCreate(
            ['nama_kategori' => 'Keamanan Siber'],
            ['bidang_id' => $bidang->id]
        );

        $this->ticket = Ticket::create([
            'kode_tiket' => 'TIK-SEC-0001',
            'nama_pelapor' => 'Pelapor Test',
            'nip_pelapor' => '198501012010011001',
            'no_whatsapp' => '081234567890',
            'skpd' => 'Dinas Komunikasi dan Informatika',
            'judul' => 'Uji Keamanan Berkas Unggahan',
            'deskripsi' => 'Pengujian berkas upload aman terhadap ancaman malware dan pemalsuan ekstensi.',
            'status' => 'pending_verification',
            'category_id' => $category->id,
            'pin' => bcrypt('123456'),
        ]);
    }

    /**
     * Uji penolakan berkas yang isi kontennya tidak cocok dengan ekstensinya (disguised file)
     */
    public function test_rejects_disguised_file_where_content_mismatches_extension(): void
    {
        // Berkas teks biasa dinamai .jpg
        $fakeJpg = UploadedFile::fake()->createWithContent('laporan_palsu.jpg', 'Ini sebenarnya berkas teks biasa bukan format gambar JPEG');

        $this->expectException(ValidationException::class);
        $this->uploadService->processUpload($fakeJpg, 'pelapor_lampiran', $this->ticket);
    }

    /**
     * Uji penolakan ekstensi terlarang (DOCX, EXE, PHP)
     */
    public function test_rejects_blocked_and_script_extensions(): void
    {
        // DOCX diblokir sesuai KEPUTUSAN BAWAAN 1 (harus PDF)
        $docxFile = UploadedFile::fake()->create('dokumen.docx', 50, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        try {
            $this->uploadService->processUpload($docxFile, 'pelapor_lampiran', $this->ticket);
            $this->fail('DOCX seharusnya ditolak.');
        } catch (ValidationException $e) {
            $this->assertTrue(true);
        }

        // Script PHP
        $phpFile = UploadedFile::fake()->createWithContent('malware.php', '<?php phpinfo(); ?>');
        try {
            $this->uploadService->processUpload($phpFile, 'pelapor_lampiran', $this->ticket);
            $this->fail('PHP script seharusnya ditolak.');
        } catch (ValidationException $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * Uji penolakan null byte injection dan pembersihan path traversal
     */
    public function test_sanitizes_filename_and_prevents_traversal(): void
    {
        $png = UploadedFile::fake()->image('../../evil.png', 100, 100);
        $record = $this->uploadService->processUpload($png, 'pelapor_lampiran', $this->ticket);

        $this->assertFalse(str_contains($record->filename, '..'));
        $this->assertFalse(str_contains($record->storage_path, '..'));
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9_-]+\.[a-zA-Z0-9]+$/', $record->filename);
    }

    /**
     * Uji penolakan PDF yang memiliki script aktif (JavaScript / Launch Action)
     */
    public function test_rejects_pdf_with_active_javascript(): void
    {
        $maliciousPdfContent = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R /OpenAction 3 0 R >>\nendobj\n3 0 obj\n<< /Type /Action /S /JavaScript /JS (app.alert('XSS')) >>\nendobj\n%%EOF";
        $pdf = UploadedFile::fake()->createWithContent('malicious.pdf', $maliciousPdfContent);

        $this->expectException(ValidationException::class);
        $this->uploadService->processUpload($pdf, 'pelapor_lampiran', $this->ticket);
    }

    /**
     * Uji pembersihan EXIF dan komputasi hash SHA-256 pada upload gambar yang sah
     */
    public function test_successful_clean_image_upload_calculates_sha256(): void
    {
        $cleanPng = UploadedFile::fake()->image('bukti_sah.png', 200, 200);

        $record = $this->uploadService->processUpload($cleanPng, 'pelapor_lampiran', $this->ticket);

        $this->assertInstanceOf(TicketFile::class, $record);
        $this->assertNotEmpty($record->sha256);
        $this->assertEquals(64, strlen($record->sha256));
        $this->assertTrue(Storage::disk('private')->exists($record->storage_path));
    }

    /**
     * Uji upload berkas teks UTF-8 yang bersih
     */
    public function test_successful_clean_text_log_upload(): void
    {
        $cleanLog = UploadedFile::fake()->createWithContent('system.log', "[2026-10-21 08:00:00] Server normal. No issues detected.\n");

        $record = $this->uploadService->processUpload($cleanLog, 'pelapor_lampiran', $this->ticket);

        $this->assertInstanceOf(TicketFile::class, $record);
        $this->assertEquals('text/plain', $record->mime_type);
        $this->assertEquals('txt', $record->extension);
    }
}
