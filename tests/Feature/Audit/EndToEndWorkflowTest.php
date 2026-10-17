<?php

namespace Tests\Feature\Audit;

use App\Models\Bidang;
use App\Models\Category;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EndToEndWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminPusat;
    protected User $adminBidang;
    protected Bidang $bidang;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $rolePusat = Role::firstOrCreate(['nama_role' => 'Admin Pusat']);
        $roleBidang = Role::firstOrCreate(['nama_role' => 'Admin Bidang']);

        $this->bidang = Bidang::firstOrCreate(
            ['kode' => 'E2E_ITIK'],
            ['nama_bidang' => 'Bidang E2E Testing', 'icon' => 'server']
        );

        $this->category = Category::firstOrCreate(
            ['nama_kategori' => 'Kategori E2E'],
            [
                'bidang_id' => $this->bidang->id,
                'sla_respon_jam' => 1.0,
                'sla_resolusi_jam' => 24.0,
            ]
        );

        $this->adminPusat = User::firstOrCreate(
            ['username' => 'e2e.admin.pusat'],
            [
                'nama' => 'Admin Pusat E2E',
                'password' => Hash::make('password123'),
                'role_id' => $rolePusat->id,
            ]
        );

        $this->adminBidang = User::firstOrCreate(
            ['username' => 'e2e.admin.bidang'],
            [
                'nama' => 'Admin Bidang E2E',
                'password' => Hash::make('password123'),
                'role_id' => $roleBidang->id,
                'bidang_id' => $this->bidang->id,
            ]
        );
    }

    public function test_pelapor_e2e_create_track_provide_info_and_confirm(): void
    {
        // 1. Pelapor membuat tiket baru melalui portal publik
        $response = $this->post(route('public.ticket.store'), [
            'nip_pelapor' => '199001012015011001',
            'nama_pelapor' => 'Budi Santoso',
            'skpd' => 'Dinas Pendidikan',
            'no_whatsapp' => '081234567890',
            'category_id' => $this->category->id,
            'judul' => 'Kendala Jaringan Kantor E2E',
            'deskripsi' => 'Koneksi internet terputus sejak pagi.',
        ]);

        $response->assertStatus(302);
        $ticket = Ticket::where('nip_pelapor', '199001012015011001')->latest()->first();
        $this->assertNotNull($ticket);
        $this->assertEquals('pending_verification', $ticket->status);
        $this->assertNotEmpty($ticket->pin);

        // 2. Pelapor melacak tiket dari perangkat/sesi baru tanpa verifikasi PIN (deskripsi disamarkan)
        $this->flushSession();
        $trackResponse = $this->get(route('public.index', ['tab' => 'lacak', 'q' => $ticket->kode_tiket]));
        $trackResponse->assertStatus(200);
        $trackResponse->assertSee('Deskripsi lengkap disensor');
        $trackResponse->assertSee('Verifikasi Identitas Pelapor');

        // 3. Pelapor memverifikasi PIN
        $plainPin = '123456';
        $ticket->pin = Hash::make($plainPin);
        $ticket->save();

        $verifyPinResponse = $this->post(route('public.ticket.verify_pin', ['kode' => $ticket->kode_tiket]), [
            'pin' => $plainPin,
        ]);
        $verifyPinResponse->assertSessionHas('success');

        // 4. Setelah verified, detail dapat diakses
        $authorizedTrack = $this->get(route('public.index', ['tab' => 'lacak', 'q' => $ticket->kode_tiket]));
        $authorizedTrack->assertStatus(200);
        $authorizedTrack->assertSee('Kendala Jaringan Kantor E2E');

        // 5. Permintaan kelengkapan data oleh sistem / admin (status: pending_verification dengan catatan_kelengkapan)
        $ticket->catatan_kelengkapan = 'Mohon unggah foto kendala';
        $ticket->save();

        // 6. Pelapor melengkapi data
        $submitInfoResponse = $this->post(route('public.ticket.submit_completion', ['kode' => $ticket->kode_tiket]), [
            'pin' => $plainPin,
            'keterangan_tambahan' => 'Berikut informasi tambahan dari kantor kami mengenai kendala jaringan.',
        ]);
        $submitInfoResponse->assertStatus(302);
        $ticket->refresh();
        $this->assertEquals('pending_verification', $ticket->status);
        $this->assertNull($ticket->catatan_kelengkapan);

        // 7. Tiket diselesaikan lalu dikonfirmasi pelapor
        $ticket->status = 'resolved';
        $ticket->save();

        $confirmResponse = $this->post(route('public.ticket.confirm', ['kode' => $ticket->kode_tiket]), [
            'rating' => 5,
            'feedback' => 'Penanganan sangat memuaskan.',
        ]);
        $confirmResponse->assertStatus(302);
        $ticket->refresh();
        $this->assertEquals('closed', $ticket->status);
    }

    public function test_admin_pusat_verifikasi_disposisi_dan_eskalasi(): void
    {
        $ticket = Ticket::create([
            'kode_tiket' => 'TK-E2E-PUSAT-' . time(),
            'nip_pelapor' => '199001012015011001',
            'nama_pelapor' => 'Siti Aminah',
            'skpd' => 'Dinas Kesehatan',
            'no_whatsapp' => '082211223344',
            'category_id' => $this->category->id,
            'judul' => 'Sistem Antrean Puskesmas E2E',
            'deskripsi' => 'Server antrean lambat merespons.',
            'status' => 'pending_verification',
            'pin' => Hash::make('654321'),
        ]);

        // Verifikasi dan Disposisi oleh Admin Pusat
        $response = $this->actingAs($this->adminPusat)->post(route('admin.verifikasi.verify', ['id' => $ticket->id]), [
            'bidang_id' => $this->bidang->id,
            'tlp_level' => 'CLEAR',
            'notes' => 'Disposisi ke Bidang E2E',
        ]);

        $response->assertStatus(302);
        $ticket->refresh();
        $this->assertEquals('verified', $ticket->status);
        $this->assertEquals($this->bidang->id, $ticket->bidang_id);
        $this->assertNotNull($ticket->sla_dimulai_at);
        $this->assertNotNull($ticket->sla_deadline_at);

        // Eskalasi Tiket jika ada kendala pusat
        $ticket->status = 'eskalasi_pusat';
        $ticket->save();

        $forceCloseResponse = $this->actingAs($this->adminPusat)->post(route('admin.eskalasi.forceClose', ['id' => $ticket->id]), [
            'force_close_notes' => 'Ditutup paksa karena digantikan oleh kebijakan sistem terpusat nasional.',
        ]);
        $forceCloseResponse->assertStatus(302);
        $ticket->refresh();
        $this->assertEquals('force_closed', $ticket->status);
    }

    public function test_negative_akses_unauthenticated_dan_cross_role(): void
    {
        // 1. Unauthenticated mengakses dashboard admin -> redirect ke login
        $response = $this->get(route('admin.dashboard'));
        $response->assertRedirect(route('login'));

        // 2. Admin Bidang mencoba verifikasi tiket Admin Pusat -> 403 Forbidden
        $ticket = Ticket::create([
            'kode_tiket' => 'TK-E2E-SEC-' . time(),
            'nip_pelapor' => '199001012015011001',
            'nama_pelapor' => 'Agus',
            'skpd' => 'BPKAD',
            'no_whatsapp' => '081399887766',
            'category_id' => $this->category->id,
            'judul' => 'Security check',
            'deskripsi' => 'Testing cross role security',
            'status' => 'pending_verification',
            'pin' => Hash::make('123456'),
        ]);

        $forbiddenResponse = $this->actingAs($this->adminBidang)->post(route('admin.verifikasi.verify', ['id' => $ticket->id]), [
            'bidang_id' => $this->bidang->id,
            'tlp_level' => 'CLEAR',
            'notes' => 'Mencoba bypass',
        ]);
        $forbiddenResponse->assertStatus(403);
    }
}
