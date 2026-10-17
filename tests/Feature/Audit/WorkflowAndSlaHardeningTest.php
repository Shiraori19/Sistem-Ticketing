<?php

namespace Tests\Feature\Audit;

use App\Models\Bidang;
use App\Models\Category;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketWorkflowService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WorkflowAndSlaHardeningTest extends TestCase
{
    protected Role $roleAdminPusat;
    protected Role $roleAdminBidang;
    protected Bidang $bidangItik;
    protected Bidang $bidangPersandian;
    protected Category $categoryItik;
    protected Category $categoryPersandian;
    protected User $adminPusat;
    protected User $adminItik;
    protected User $adminPersandian;
    protected TicketWorkflowService $workflowService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roleAdminPusat = Role::firstOrCreate(['nama_role' => 'Admin Pusat']);
        $this->roleAdminBidang = Role::firstOrCreate(['nama_role' => 'Admin Bidang']);

        $this->bidangItik = Bidang::firstOrCreate(
            ['kode' => 'ITIK'],
            ['nama_bidang' => 'Bidang Infrastruktur TIK']
        );
        $this->bidangPersandian = Bidang::firstOrCreate(
            ['kode' => 'PERSANDIAN'],
            ['nama_bidang' => 'Bidang Persandian dan Keamanan Informasi']
        );

        $this->categoryItik = Category::firstOrCreate(
            ['nama_kategori' => 'Jaringan & Switch'],
            ['bidang_id' => $this->bidangItik->id, 'sla_jam' => 72, 'sla_resolusi_jam' => 336]
        );
        $this->categoryPersandian = Category::firstOrCreate(
            ['nama_kategori' => 'Insiden Defacement Website'],
            ['bidang_id' => $this->bidangPersandian->id, 'sla_jam' => 72, 'sla_resolusi_jam' => 336]
        );

        $this->adminPusat = User::create([
            'nama' => 'Admin Pusat Audit Test',
            'username' => 'pusat.' . uniqid() . '@cirebonkota.go.id',
            'password' => bcrypt('Password123!'),
            'role_id' => $this->roleAdminPusat->id,
            'is_active' => true,
        ]);

        $this->adminItik = User::create([
            'nama' => 'Admin ITIK Audit Test',
            'username' => 'itik.' . uniqid() . '@cirebonkota.go.id',
            'password' => bcrypt('Password123!'),
            'role_id' => $this->roleAdminBidang->id,
            'bidang_id' => $this->bidangItik->id,
            'is_active' => true,
        ]);

        $this->adminPersandian = User::create([
            'nama' => 'Admin Persandian Audit Test',
            'username' => 'sandi.' . uniqid() . '@cirebonkota.go.id',
            'password' => bcrypt('Password123!'),
            'role_id' => $this->roleAdminBidang->id,
            'bidang_id' => $this->bidangPersandian->id,
            'is_active' => true,
        ]);

        $this->workflowService = app(TicketWorkflowService::class);
    }

    /**
     * SEC-05: Verifikasi bahwa tiket legacy dengan hash PIN Bcrypt dapat diverifikasi dengan PIN 6 digit
     */
    public function test_legacy_hashed_pin_verification_succeeds(): void
    {
        $rawPin = '882910';
        $ticket = Ticket::create([
            'kode_tiket' => 'TIK-202610-' . rand(1000, 9999),
            'nip_pelapor' => '199001012015011001',
            'nama_pelapor' => 'ASN Legacy Test',
            'skpd' => 'Bapenda',
            'no_whatsapp' => '081298765432',
            'pin' => Hash::make($rawPin), // Bcrypt hash dari pin lama
            'category_id' => $this->categoryItik->id,
            'judul' => 'Uji Tiket Legacy PIN',
            'deskripsi' => 'Deskripsi kendala pengujian legacy PIN.',
            'status' => 'resolved',
            'bidang_id' => $this->bidangItik->id,
        ]);

        // Verifikasi PIN via endpoint publik
        $response = $this->post(route('public.ticket.verify_pin', $ticket->kode_tiket), [
            'pin' => $rawPin,
        ]);

        $response->assertSessionHas('success');
        $this->assertTrue(\App\Services\TicketAuthService::isAuthorized(request(), $ticket));
    }

    /**
     * FLOW-02: linkToParent menolak penautan ke induk yang telah closed dan mewariskan SLA ke anak
     */
    public function test_link_to_parent_validates_status_and_inherits_sla(): void
    {
        $slaDimulai = Carbon::now()->subHours(5);
        $parent = Ticket::create([
            'kode_tiket' => 'TIK-PARENT-' . rand(100, 999),
            'nip_pelapor' => '199001012015011002',
            'nama_pelapor' => 'Pelapor Induk',
            'skpd' => 'Dinas Pendidikan',
            'no_whatsapp' => '081298765433',
            'pin' => Hash::make('123456'),
            'category_id' => $this->categoryItik->id,
            'judul' => 'Induk Insiden Internet Putus',
            'deskripsi' => 'Kabel FO utama putus.',
            'status' => 'verified',
            'bidang_id' => $this->bidangItik->id,
            'sla_dimulai_at' => $slaDimulai,
            'sla_deadline_at' => $slaDimulai->copy()->addHours(336),
            'sla_resolusi_jam' => 336,
        ]);

        $child = Ticket::create([
            'kode_tiket' => 'TIK-CHILD-' . rand(100, 999),
            'nip_pelapor' => '199001012015011003',
            'nama_pelapor' => 'Pelapor Anak',
            'skpd' => 'SMPN 1',
            'no_whatsapp' => '081298765434',
            'pin' => Hash::make('654321'),
            'category_id' => $this->categoryItik->id,
            'judul' => 'Internet SMPN 1 Mati',
            'deskripsi' => 'Koneksi internet mati.',
            'status' => 'pending_verification',
        ]);

        // 1. Tautkan anak ke induk: harus berhasil dan mewarisi SLA
        $linkedChild = $this->workflowService->linkToParent($child, $parent, $this->adminPusat);

        $this->assertEquals($parent->id, $linkedChild->parent_id);
        $this->assertEquals('verified', $linkedChild->status);
        $this->assertEquals($this->bidangItik->id, $linkedChild->bidang_id);
        $this->assertNotNull($linkedChild->sla_dimulai_at);
        $this->assertEquals($slaDimulai->toDateTimeString(), $linkedChild->sla_dimulai_at->toDateTimeString());

        // 2. Jika induk ditutup (closed), penautan tiket baru wajib ditolak
        $parent->update(['status' => 'closed']);
        $newChild = Ticket::create([
            'kode_tiket' => 'TIK-CHILD2-' . rand(100, 999),
            'nip_pelapor' => '199001012015011004',
            'nama_pelapor' => 'Pelapor Anak 2',
            'skpd' => 'SMPN 2',
            'no_whatsapp' => '081298765435',
            'pin' => Hash::make('112233'),
            'category_id' => $this->categoryItik->id,
            'judul' => 'Kendala Anak 2',
            'deskripsi' => 'Deskripsi.',
            'status' => 'pending_verification',
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->workflowService->linkToParent($newChild, $parent, $this->adminPusat);
    }

    /**
     * FLOW-03: Re-disposisi setelah tiket dikembalikan dari bidang mempertahankan jam SLA awal
     */
    public function test_redisposition_preserves_initial_sla_start(): void
    {
        $initialSla = Carbon::now()->subHours(24);
        $ticket = Ticket::create([
            'kode_tiket' => 'TIK-REDISPOSISI-' . rand(100, 999),
            'nip_pelapor' => '199001012015011005',
            'nama_pelapor' => 'Pelapor Salah Disposisi',
            'skpd' => 'Disdukcapil',
            'no_whatsapp' => '081298765436',
            'pin' => Hash::make('998877'),
            'category_id' => $this->categoryItik->id,
            'judul' => 'Salah Disposisi Tiket',
            'deskripsi' => 'Tiket dikembalikan ke pusat.',
            'status' => 'pending_verification', // Setelah di-returnToPusat
            'bidang_id' => null,
            'sla_dimulai_at' => $initialSla, // Timestamp verifikasi awal
            'sla_deadline_at' => $initialSla->copy()->addHours(336),
            'sla_resolusi_jam' => 336,
        ]);

        // Admin Pusat mendisposisikan ulang ke Bidang Persandian
        $redisposed = $this->workflowService->verifyAndDispose(
            $ticket,
            'GREEN',
            $this->bidangPersandian->id,
            'Disposisi ulang ke bidang yang benar.',
            $this->adminPusat
        );

        $this->assertEquals('verified', $redisposed->status);
        $this->assertEquals($this->bidangPersandian->id, $redisposed->bidang_id);
        // FLOW-03: Pastikan sla_dimulai_at awal TIDAK direset ke jam sekarang
        $this->assertEquals($initialSla->toDateTimeString(), $redisposed->sla_dimulai_at->toDateTimeString());
    }

    /**
     * FLOW-04: Antrean operasional bidang ITIK tidak menampilkan TLP RED, tetapi masuk tab siaran_siber
     */
    public function test_tlp_red_separated_from_operational_queue_for_non_persandian(): void
    {
        // 1. Tiket normal ITIK
        $ticketItik = Ticket::create([
            'kode_tiket' => 'TIK-ITIK-' . rand(100, 999),
            'nip_pelapor' => '199001012015011006',
            'nama_pelapor' => 'Pelapor ITIK',
            'skpd' => 'Dinkes',
            'no_whatsapp' => '081298765437',
            'pin' => Hash::make('111111'),
            'category_id' => $this->categoryItik->id,
            'judul' => 'Perbaikan Jaringan ITIK',
            'deskripsi' => 'Switch kantor rusak.',
            'status' => 'verified',
            'bidang_id' => $this->bidangItik->id,
            'tlp_level' => 'GREEN',
        ]);

        // 2. Tiket TLP RED Persandian
        $ticketRed = Ticket::create([
            'kode_tiket' => 'TIK-SIBER-' . rand(100, 999),
            'nip_pelapor' => '199001012015011007',
            'nama_pelapor' => 'Pelapor Siber',
            'skpd' => 'DKIS',
            'no_whatsapp' => '081298765438',
            'pin' => Hash::make('222222'),
            'category_id' => $this->categoryPersandian->id,
            'judul' => 'Serangan Malware Ransomware',
            'deskripsi' => 'Server terenkripsi malware.',
            'status' => 'in_progress',
            'bidang_id' => $this->bidangPersandian->id,
            'tlp_level' => 'RED',
        ]);

        // Request antrean operasional biasa oleh Admin ITIK: hanya melihat tiket ITIK
        $responseItik = $this->actingAs($this->adminItik)->get(route('admin.bidang.tiket.index'));
        $responseItik->assertStatus(200);
        $responseItik->assertSee($ticketItik->kode_tiket);
        $responseItik->assertDontSee($ticketRed->kode_tiket);

        // Request tab siaran_siber oleh Admin ITIK: melihat tiket TLP RED
        $responseSiber = $this->actingAs($this->adminItik)->get(route('admin.bidang.tiket.index', ['filter' => 'siaran_siber']));
        $responseSiber->assertStatus(200);
        $responseSiber->assertSee($ticketRed->kode_tiket);
    }
}
