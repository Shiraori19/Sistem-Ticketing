<?php

namespace Tests\Feature\Audit;

use App\Models\AuditLog;
use App\Models\Bidang;
use App\Models\Category;
use App\Models\Ticket;
use App\Services\TicketAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicTicketSecurityTest extends TestCase
{
    protected Bidang $bidang;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
        Storage::fake('public');

        $this->bidang = Bidang::firstOrCreate(
            ['kode' => 'ITIK'],
            ['nama_bidang' => 'Bidang Infrastruktur TIK']
        );

        $this->category = Category::firstOrCreate(
            ['nama_kategori' => 'Jaringan & Internet'],
            ['bidang_id' => $this->bidang->id, 'sla_jam' => 72, 'sla_resolusi_jam' => 336]
        );
    }

    protected function createTicketWithAttachment(): Ticket
    {
        $rawPin = '123456';
        $storedPath = 'lampiran_pelapor/test_lampiran.pdf';
        Storage::disk('private')->put($storedPath, 'PDF dummy content');

        return Ticket::create([
            'kode_tiket' => 'TIK-202610-' . rand(1000, 9999),
            'nip_pelapor' => '198801012010121001',
            'nama_pelapor' => 'Budi ASN',
            'skpd' => 'Dinas Kesehatan',
            'no_whatsapp' => '081234567890',
            'pin' => Hash::make($rawPin),
            'category_id' => $this->category->id,
            'judul' => 'Gangguan Koneksi FO Gedung B',
            'deskripsi' => 'Koneksi jaringan internet pada lantai 2 mati total sejak pukul 08:00 WIB.',
            'lampiran_pelapor' => [$storedPath],
            'status' => 'in_progress',
            'bidang_id' => $this->bidang->id,
        ]);
    }

    /**
     * SEC-01: Verifikasi penolakan unduh berkas publik tanpa sesi PIN terverifikasi
     */
    public function test_public_download_rejected_without_pin_session(): void
    {
        $ticket = $this->createTicketWithAttachment();

        // 1. Request tanpa sesi PIN
        $response = $this->get(route('public.tiket.download', [
            'kode' => $ticket->kode_tiket,
            'type' => 'lampiran',
            'index' => 0,
        ]));
        $response->assertStatus(403);

        // 2. Request dengan query param auth_token yang mencoba membypass PIN
        $bypassResponse = $this->get(route('public.tiket.download', [
            'kode' => $ticket->kode_tiket,
            'type' => 'lampiran',
            'index' => 0,
            'auth_token' => $ticket->access_token,
        ]));
        $bypassResponse->assertStatus(403);
    }

    /**
     * SEC-01 & SEC-04: Berkas dapat diunduh jika lolos verifikasi PIN dan tercatat di audit_log
     */
    public function test_public_download_allowed_after_pin_verification_and_logged(): void
    {
        $ticket = $this->createTicketWithAttachment();

        // Pelapor memasukkan PIN yang benar
        $pinResponse = $this->post(route('public.ticket.verify_pin', $ticket->kode_tiket), [
            'pin' => '123456',
        ]);
        $pinResponse->assertSessionHas('success');

        // Pengunduhan berkas berhasil
        $downloadResponse = $this->get(route('public.tiket.download', [
            'kode' => $ticket->kode_tiket,
            'type' => 'lampiran',
            'index' => 0,
        ]));
        $downloadResponse->assertStatus(200);

        // SEC-04: Pastikan tercatat di audit_log
        $this->assertDatabaseHas('audit_log', [
            'target_kode' => $ticket->kode_tiket,
            'aksi' => 'UNDUH_BERKAS_PUBLIK',
        ]);
    }

    /**
     * SEC-02: Tracking tanpa PIN tidak membocorkan access_token di HTML dan menyensor deskripsi
     */
    public function test_public_tracking_masks_sensitive_data_and_omits_access_token(): void
    {
        $ticket = $this->createTicketWithAttachment();

        $response = $this->get(route('public.index', [
            'tab' => 'lacak',
            'q' => $ticket->kode_tiket,
        ]));

        $response->assertStatus(200);

        // Pastikan token otorisasi tidak pernah tercetak di markup HTML publik
        $response->assertDontSee($ticket->access_token);
        $response->assertDontSee('auth_token=' . $ticket->access_token);

        // Pastikan ada peringatan sensor privasi untuk pelapor yang belum input PIN
        $response->assertSee('Deskripsi lengkap disensor');
        $response->assertSee('Verifikasi Identitas Pelapor');
    }
}
