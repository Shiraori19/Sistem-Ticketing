<?php

namespace Tests\Feature\Security;

use App\Models\Bidang;
use App\Models\Category;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketBidangInvolvement;
use App\Models\TicketFile;
use App\Models\TicketFileAccessRequest;
use App\Models\User;
use App\Services\SecureUploadService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TlpRedVaultAndCrossBidangTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminPusat;
    protected User $adminPersandian;
    protected User $adminItik;
    protected Bidang $bidangPersandian;
    protected Bidang $bidangItik;
    protected Category $category;
    protected Ticket $ticket;
    protected TicketFile $ticketFile;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');
        Storage::fake('vault');
        Storage::fake('previews');

        $rolePusat = Role::firstOrCreate(['nama_role' => 'Admin Pusat']);
        $roleBidang = Role::firstOrCreate(['nama_role' => 'Admin Bidang']);

        $this->bidangPersandian = Bidang::firstOrCreate(
            ['kode' => 'PERSANDIAN'],
            ['nama_bidang' => 'Bidang Persandian', 'is_persandian' => true]
        );
        $this->bidangPersandian->update(['is_persandian' => true]);

        $this->bidangItik = Bidang::firstOrCreate(
            ['kode' => 'ITIK'],
            ['nama_bidang' => 'Bidang ITIK', 'is_persandian' => false]
        );
        $this->bidangItik->update(['is_persandian' => false]);

        $this->adminPusat = User::firstOrCreate(
            ['username' => 'pusat.test'],
            ['nama' => 'Admin Pusat Test', 'password' => bcrypt('password'), 'role_id' => $rolePusat->id, 'is_active' => true]
        );
        $this->adminPusat->update(['role_id' => $rolePusat->id, 'is_active' => true]);
        $this->adminPusat->load('role');

        $this->adminPersandian = User::firstOrCreate(
            ['username' => 'csirt.test'],
            ['nama' => 'CSIRT Agent', 'password' => bcrypt('password'), 'role_id' => $roleBidang->id, 'bidang_id' => $this->bidangPersandian->id, 'is_active' => true]
        );
        $this->adminPersandian->update([
            'role_id' => $roleBidang->id,
            'bidang_id' => $this->bidangPersandian->id,
            'is_active' => true,
        ]);
        $this->adminPersandian->load('role', 'bidang');

        $this->adminItik = User::firstOrCreate(
            ['username' => 'itik.test'],
            ['nama' => 'ITIK Agent', 'password' => bcrypt('password'), 'role_id' => $roleBidang->id, 'bidang_id' => $this->bidangItik->id, 'is_active' => true]
        );
        $this->adminItik->update([
            'role_id' => $roleBidang->id,
            'bidang_id' => $this->bidangItik->id,
            'is_active' => true,
        ]);
        $this->adminItik->load('role', 'bidang');

        $this->category = Category::firstOrCreate(
            ['nama_kategori' => 'Insiden Siber'],
            ['bidang_id' => $this->bidangPersandian->id]
        );

        $this->ticket = Ticket::create([
            'kode_tiket' => 'TIK-RED-9901',
            'nama_pelapor' => 'Sekretaris Daerah',
            'nip_pelapor' => '197001011990011001',
            'no_whatsapp' => '081299998888',
            'skpd' => 'Sekretariat Daerah',
            'judul' => 'Dugaan Kebocoran Kredensial Database',
            'deskripsi' => 'Terdeteksi upaya exfiltration data pada server database utama.',
            'status' => 'verified',
            'tlp_level' => 'RED',
            'bidang_id' => $this->bidangPersandian->id,
            'category_id' => $this->category->id,
            'pin_hash' => bcrypt('654321'),
        ]);

        // Upload berkas awal
        $fakeImg = UploadedFile::fake()->image('bukti_kritis.png', 100, 100);
        $this->ticketFile = app(SecureUploadService::class)->processUpload($fakeImg, 'bukti_resolusi', $this->ticket, [
            'classification' => 'RED',
            'is_encrypted' => true,
        ]);
    }

    /**
     * Uji Admin Bidang non-Persandian dilarang mengakses tiket TLP RED (403 Forbidden)
     */
    public function test_non_persandian_admin_cannot_access_tlp_red_ticket(): void
    {
        $response = $this->actingAs($this->adminItik)
            ->get(route('admin.tiket.show', $this->ticket->id));

        $response->assertStatus(403);
    }

    /**
     * Uji Admin Persandian diizinkan mengakses tiket TLP RED
     */
    public function test_persandian_admin_can_access_tlp_red_ticket(): void
    {
        $response = $this->actingAs($this->adminPersandian)
            ->get(route('admin.tiket.show', $this->ticket->id));

        $response->assertStatus(200);
    }

    /**
     * Uji Bidang ditembuskan dapat melihat rekam jejak tanpa 403
     */
    public function test_involved_bidang_can_view_ticket(): void
    {
        // Daftarkan bidang ITIK sebagai ditembuskan
        TicketBidangInvolvement::create([
            'ticket_id' => $this->ticket->id,
            'bidang_id' => $this->bidangItik->id,
            'peran' => 'ditembuskan',
            'notified_at' => now(),
            'ditetapkan_oleh' => $this->adminPusat->id,
        ]);

        $response = $this->actingAs($this->adminItik)
            ->get(route('admin.tiket.show', $this->ticket->id));

        $response->assertStatus(200);
    }

    /**
     * Uji Alur Pengajuan Akses Berkas Vault oleh Admin Pusat (BUTIR 5 & KEPUTUSAN 3)
     */
    public function test_admin_pusat_access_request_and_persandian_review_workflow(): void
    {
        // 1. Admin Pusat meminta akses berkas
        $response = $this->actingAs($this->adminPusat)
            ->post(route('admin.tiket.requestAccess', [
                'kode' => $this->ticket->kode_tiket,
                'fileId' => $this->ticketFile->id,
            ]), [
                'reason' => 'Diperlukan untuk koordinasi tindak lanjut dengan Tim CSIRT Pusat.',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('ticket_file_access_requests', [
            'ticket_file_id' => $this->ticketFile->id,
            'user_id' => $this->adminPusat->id,
            'status' => 'pending',
        ]);

        $accessRequest = TicketFileAccessRequest::where('ticket_file_id', $this->ticketFile->id)->first();

        // 2. Persandian menyetujui pengajuan akses
        $reviewResponse = $this->actingAs($this->adminPersandian)
            ->post(route('admin.tiket.reviewAccess', $accessRequest->id), [
                'decision' => 'approved',
                'review_notes' => 'Akses diberikan selama 24 jam untuk keperluan koordinasi kedinasan.',
                'duration_hours' => 24,
            ]);

        $reviewResponse->assertRedirect();
        $accessRequest->refresh();
        $this->assertEquals('approved', $accessRequest->status);
        $this->assertNotNull($accessRequest->expires_at);
        $this->assertTrue($accessRequest->isApprovedAndValid());
    }

    /**
     * Uji enkripsi Vault pada berkas TLP RED
     */
    public function test_vault_file_is_stored_and_encrypted(): void
    {
        $this->assertTrue($this->ticketFile->is_encrypted);
        $this->assertTrue($this->ticketFile->is_vault);
        $this->assertTrue(Storage::disk('vault')->exists($this->ticketFile->vault_path));

        // Verifikasi isi mentah di vault bukan plaintext gambar
        $rawVaultContent = Storage::disk('vault')->get($this->ticketFile->vault_path);
        $this->assertFalse(str_starts_with($rawVaultContent, "\x89PNG"));
    }
}
