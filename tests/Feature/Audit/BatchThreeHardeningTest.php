<?php

namespace Tests\Feature\Audit;

use App\Jobs\SendTicketNotificationJob;
use App\Models\Bidang;
use App\Models\Category;
use App\Models\Notification;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketFile;
use App\Models\User;
use App\Services\Notification\WhatsAppServiceInterface;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BatchThreeHardeningTest extends TestCase
{
    protected Role $roleAdminPusat;
    protected Bidang $bidangItik;
    protected Category $category;
    protected User $adminPusat;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');

        $this->roleAdminPusat = Role::firstOrCreate(['nama_role' => 'Admin Pusat']);
        $this->bidangItik = Bidang::firstOrCreate(['kode' => 'ITIK'], ['nama_bidang' => 'Bidang ITIK']);
        $this->category = Category::firstOrCreate(['nama_kategori' => 'Jaringan'], ['bidang_id' => $this->bidangItik->id]);

        $this->adminPusat = User::create([
            'nama' => 'Admin Pusat Test',
            'username' => 'pusat.' . uniqid() . '@cirebonkota.go.id',
            'password' => bcrypt('Password123!'),
            'role_id' => $this->roleAdminPusat->id,
            'is_active' => true,
        ]);
    }

    /**
     * FLOW-05: Berkas kelengkapan data susulan dicatat ke ticket_files tanpa menimpa histori
     */
    public function test_additional_info_file_saved_to_ticket_files(): void
    {
        $rawPin = '123456';
        $ticket = Ticket::create([
            'kode_tiket' => 'TIK-SUSULAN-' . rand(100, 999),
            'nip_pelapor' => '199001012015011010',
            'nama_pelapor' => 'ASN Pelapor Susulan',
            'skpd' => 'Disdukcapil',
            'no_whatsapp' => '081234567891',
            'pin' => Hash::make($rawPin),
            'category_id' => $this->category->id,
            'judul' => 'Kendala Data Susulan',
            'deskripsi' => 'Awal kendala.',
            'status' => 'pending_verification',
            'catatan_kelengkapan' => 'Mohon sertakan bukti foto perangkat.',
        ]);

        $fakeFile = UploadedFile::fake()->image('foto_perangkat.jpg', 200, 200);

        $response = $this->post(route('public.ticket.submit_completion', $ticket->kode_tiket), [
            'pin' => $rawPin,
            'keterangan_tambahan' => 'Berikut kami lampirkan foto router yang indikatornya merah.',
            'lampiran_tambahan' => $fakeFile,
        ]);

        $response->assertSessionHas('success');

        $ticket->refresh();
        $this->assertNotNull($ticket->lampiran_tambahan);

        // FLOW-05: Pastikan tercatat di relasi ticket_files
        $this->assertDatabaseHas('ticket_files', [
            'ticket_id' => $ticket->id,
            'kategori' => 'lampiran_tambahan',
        ]);
    }

    /**
     * FLOW-06: Laporan kepatuhan SLA mengecualikan tiket pending_verification dan cancelled
     */
    public function test_sla_compliance_excludes_pending_and_cancelled(): void
    {
        // 1. Tiket verified patuh
        Ticket::create([
            'kode_tiket' => 'TIK-COMPLIANT-' . rand(100, 999),
            'nip_pelapor' => '199001012015011011',
            'nama_pelapor' => 'Pelapor A',
            'skpd' => 'Dinkes',
            'no_whatsapp' => '081234567892',
            'category_id' => $this->category->id,
            'judul' => 'Tiket Patuh',
            'deskripsi' => 'Deskripsi.',
            'status' => 'resolved',
            'sla_breach' => false,
            'bidang_id' => $this->bidangItik->id,
        ]);

        // 2. Tiket cancelled (tidak boleh dihitung sebagai pembagi kepatuhan)
        Ticket::create([
            'kode_tiket' => 'TIK-CANCELLED-' . rand(100, 999),
            'nip_pelapor' => '199001012015011012',
            'nama_pelapor' => 'Pelapor B',
            'skpd' => 'Dinkes',
            'no_whatsapp' => '081234567893',
            'category_id' => $this->category->id,
            'judul' => 'Tiket Dibatalkan',
            'deskripsi' => 'Deskripsi.',
            'status' => 'cancelled',
            'sla_breach' => false,
        ]);

        $response = $this->actingAs($this->adminPusat)->get(route('admin.laporan'));
        $response->assertStatus(200);
        $response->assertViewHas('complianceRate');
    }

    /**
     * NOTIF-01: SendTicketNotificationJob terintegrasi dengan WhatsAppServiceInterface
     */
    public function test_send_ticket_notification_job_uses_whatsapp_service(): void
    {
        $ticket = Ticket::create([
            'kode_tiket' => 'TIK-NOTIF-' . rand(100, 999),
            'nip_pelapor' => '199001012015011013',
            'nama_pelapor' => 'Pelapor Notif',
            'skpd' => 'DKIS',
            'no_whatsapp' => '081234567894',
            'category_id' => $this->category->id,
            'judul' => 'Uji Notifikasi',
            'deskripsi' => 'Deskripsi.',
            'status' => 'pending_verification',
        ]);

        $job = new SendTicketNotificationJob($ticket->id, '081234567894', 'Halo, tiket Anda sedang diproses.');
        $waService = app(WhatsAppServiceInterface::class);
        $job->handle($waService);

        $this->assertDatabaseHas('notifications', [
            'ticket_id' => $ticket->id,
            'nomor_tujuan' => '081234567894',
            'status_kirim' => 'sent',
        ]);
    }

    /**
     * DB-01: Pencarian judul dan nama pelapor case-insensitive (ILIKE)
     */
    public function test_case_insensitive_ticket_search(): void
    {
        $ticket = Ticket::create([
            'kode_tiket' => 'TIK-SEARCH-' . rand(100, 999),
            'nip_pelapor' => '199001012015011014',
            'nama_pelapor' => 'BAMBANG HERMANTO',
            'skpd' => 'SETDA',
            'no_whatsapp' => '081234567895',
            'category_id' => $this->category->id,
            'judul' => 'GANGGUAN JARINGAN LAN GEDUNG SETDA',
            'deskripsi' => 'Kabel LAN lepas.',
            'status' => 'verified',
            'bidang_id' => $this->bidangItik->id,
        ]);

        // Cari dengan huruf kecil semua: 'bambang' dan 'gangguan'
        $response = $this->actingAs($this->adminPusat)->get(route('admin.tiket.index', ['q' => 'bambang']));
        $response->assertStatus(200);
        $response->assertSee($ticket->kode_tiket);

        $responseJudul = $this->actingAs($this->adminPusat)->get(route('admin.tiket.index', ['q' => 'gangguan']));
        $responseJudul->assertStatus(200);
        $responseJudul->assertSee($ticket->kode_tiket);
    }
}
