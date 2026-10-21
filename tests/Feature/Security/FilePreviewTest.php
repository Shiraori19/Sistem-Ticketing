<?php

namespace Tests\Feature\Security;

use App\Models\AuditLog;
use App\Models\Bidang;
use App\Models\Category;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketFile;
use App\Models\User;
use App\Services\FilePreviewService;
use App\Services\SecureUploadService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FilePreviewTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminPusat;
    protected User $adminPersandian;
    protected Ticket $ticket;
    protected TicketFile $ticketFile;
    protected FilePreviewService $previewService;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');
        Storage::fake('vault');
        Storage::fake('previews');

        $this->previewService = app(FilePreviewService::class);

        $rolePusat = Role::firstOrCreate(['nama_role' => 'Admin Pusat']);
        $roleBidang = Role::firstOrCreate(['nama_role' => 'Admin Bidang']);

        $bidang = Bidang::firstOrCreate(
            ['kode' => 'PERSANDIAN'],
            ['nama_bidang' => 'Bidang Persandian', 'is_persandian' => true]
        );

        $this->adminPusat = User::firstOrCreate(
            ['username' => 'preview.pusat'],
            ['nama' => 'Admin Preview', 'password' => bcrypt('password'), 'role_id' => $rolePusat->id, 'is_active' => true]
        );

        $this->adminPersandian = User::firstOrCreate(
            ['username' => 'preview.csirt'],
            ['nama' => 'CSIRT Preview', 'password' => bcrypt('password'), 'role_id' => $roleBidang->id, 'bidang_id' => $bidang->id, 'is_active' => true]
        );

        $category = Category::firstOrCreate(
            ['nama_kategori' => 'Keamanan Jaringan'],
            ['bidang_id' => $bidang->id]
        );

        $this->ticket = Ticket::create([
            'kode_tiket' => 'TIK-PRV-1001',
            'nama_pelapor' => 'Pegawai OPD',
            'nip_pelapor' => '198202022005011002',
            'no_whatsapp' => '081255556666',
            'skpd' => 'Dinas Pendidikan',
            'judul' => 'Tangkapan Layar Kendala Absensi',
            'deskripsi' => 'Rincian kendala pratinjau berkas.',
            'status' => 'verified',
            'tlp_level' => 'GREEN',
            'bidang_id' => $bidang->id,
            'category_id' => $category->id,
            'pin_hash' => bcrypt('555666'),
        ]);

        $fakeImg = UploadedFile::fake()->image('lampiran_kendala.png', 800, 600);
        $this->ticketFile = app(SecureUploadService::class)->processUpload($fakeImg, 'pelapor_lampiran', $this->ticket);
    }

    /**
     * Uji pembuatan berkas derivatif pratinjau pada disk previews
     */
    public function test_generates_preview_derivative_on_previews_disk(): void
    {
        $previewData = $this->previewService->getOrCreatePreview($this->ticketFile);

        $this->assertEquals('ready', $previewData['status'] ?? '');
        $this->assertTrue(Storage::disk('previews')->exists($previewData['preview_path']));
    }

    /**
     * Uji pembuatan thumbnail kecil untuk kartu lampiran
     */
    public function test_generates_thumbnail_derivative(): void
    {
        $previewData = $this->previewService->getOrCreatePreview($this->ticketFile);

        $this->assertNotEmpty($previewData['thumb_path'] ?? null);
        $this->assertTrue(Storage::disk('previews')->exists($previewData['thumb_path']));
    }

    /**
     * Uji endpoint previewAdmin mengembalikan respons gambar derivatif
     */
    public function test_preview_admin_endpoint_returns_file_response(): void
    {
        $response = $this->actingAs($this->adminPusat)
            ->get(route('admin.tiket.preview', [
                'kode' => $this->ticket->kode_tiket,
                'fileId' => $this->ticketFile->id,
            ]));

        $response->assertStatus(200);
    }

    /**
     * Uji pencatatan audit log PRATINJAU_BERKAS dan debounce dalam rentang waktu singkat
     */
    public function test_preview_logs_audit_with_debounce(): void
    {
        // Panggilan pertama
        $this->actingAs($this->adminPusat)
            ->get(route('admin.tiket.preview', [
                'kode' => $this->ticket->kode_tiket,
                'fileId' => $this->ticketFile->id,
            ]));

        $countInitial = AuditLog::where('aksi', 'PRATINJAU_BERKAS')
            ->where('target_kode', $this->ticket->kode_tiket)
            ->count();

        $this->assertEquals(1, $countInitial);

        // Panggilan kedua segera setelahnya (debounce 5 menit)
        $this->actingAs($this->adminPusat)
            ->get(route('admin.tiket.preview', [
                'kode' => $this->ticket->kode_tiket,
                'fileId' => $this->ticketFile->id,
            ]));

        $countAfterSecond = AuditLog::where('aksi', 'PRATINJAU_BERKAS')
            ->where('target_kode', $this->ticket->kode_tiket)
            ->count();

        // Jumlah log tidak bertambah karena ter-debounce
        $this->assertEquals(1, $countAfterSecond);
    }
}
