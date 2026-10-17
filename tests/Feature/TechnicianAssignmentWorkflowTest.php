<?php

namespace Tests\Feature;

use App\Models\Bidang;
use App\Models\Category;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketAssignment;
use App\Models\TicketBlocker;
use App\Models\TicketEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TechnicianAssignmentWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    protected Role $roleTeknisi;
    protected Role $roleAdminBidang;
    protected Bidang $bidangItik;
    protected Bidang $bidangPemdi;
    protected Bidang $bidangPersandian;
    protected Category $categoryItik;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roleTeknisi = Role::firstOrCreate(['nama_role' => 'Teknisi']);
        $this->roleAdminBidang = Role::firstOrCreate(['nama_role' => 'Admin Bidang']);

        $this->bidangItik = Bidang::firstOrCreate(
            ['kode' => 'ITIK'],
            ['nama_bidang' => 'Infrastruktur TIK', 'icon' => 'server-stack']
        );

        $this->bidangPemdi = Bidang::firstOrCreate(
            ['kode' => 'PEMDI'],
            ['nama_bidang' => 'Pemerintahan Digital', 'icon' => 'code-bracket']
        );

        $this->bidangPersandian = Bidang::firstOrCreate(
            ['kode' => 'PERSANDIAN'],
            ['nama_bidang' => 'Persandian & Keamanan Informasi', 'icon' => 'shield-check']
        );

        $this->categoryItik = Category::firstOrCreate(
            ['nama_kategori' => 'Jaringan & Internet Testing'],
            [
                'bidang_id' => $this->bidangItik->id,
                'sla_respon_jam' => 1.0,
                'sla_resolusi_jam' => 24,
            ]
        );
    }

    /**
     * Helper untuk membuat akun teknisi
     */
    protected function createTechnician(Bidang $bidang, string $name = 'Teknisi Uji'): User
    {
        $unique = uniqid();
        $user = User::create([
            'nama' => $name,
            'username' => "teknisi.{$unique}@cirebonkota.go.id",
            'password' => bcrypt('Password123!'),
            'role_id' => $this->roleTeknisi->id,
            'bidang_id' => $bidang->id,
            'avatar' => 'TU',
            'is_active' => true,
        ]);

        $user->technicianProfile()->create([
            'availability' => 'tersedia',
            'max_load' => 5,
            'skills' => ['Jaringan', 'Troubleshooting'],
        ]);

        return $user;
    }

    /**
     * Helper untuk membuat tiket uji
     */
    protected function createTicket(Bidang $bidang, array $overrides = []): Ticket
    {
        $unique = strtoupper(substr(uniqid(), -4));
        return Ticket::create(array_merge([
            'kode_tiket' => 'TIK-TEST-' . $unique,
            'nip_pelapor' => '198501012010011001',
            'nama_pelapor' => 'Ahmad Pelapor ASN',
            'skpd' => 'Bapenda Kota Cirebon',
            'no_whatsapp' => '081234567890',
            'email_pelapor' => 'pelapor@cirebonkota.go.id',
            'pin' => bcrypt('123456'),
            'category_id' => $this->categoryItik->id,
            'judul' => 'Gangguan Koneksi Jaringan di Lantai 2',
            'deskripsi' => 'Koneksi internet terputus sejak pagi dan lampu indikator switch berkedip merah.',
            'tlp_level' => 'CLEAR',
            'bidang_id' => $bidang->id,
            'status' => 'verified',
            'sla_dimulai_at' => Carbon::now(),
            'sla_deadline_at' => Carbon::now()->addHours(24),
            'sla_respon_jam' => 1.0,
            'sla_resolusi_jam' => 24,
        ], $overrides));
    }

    /**
     * Helper untuk membuat penugasan teknisi
     */
    protected function createAssignment(Ticket $ticket, User $technician, array $overrides = []): TicketAssignment
    {
        return TicketAssignment::create(array_merge([
            'ticket_id' => $ticket->id,
            'user_id' => $technician->id,
            'role' => 'lead',
            'technician_name' => $technician->nama,
            'assigned_at' => Carbon::now(),
            'respond_deadline' => Carbon::now()->addMinutes(60),
            'status' => 'assigned',
            'row_version' => 1,
        ], $overrides));
    }

    /**
     * 1. Test: Teknisi menerima penugasan dalam batas waktu respon.
     * Status penugasan menjadi 'working', started_at terisi, status tiket menjadi 'in_progress', event tercatat.
     */
    public function test_teknisi_dapat_menerima_penugasan_dalam_deadline(): void
    {
        $teknisi = $this->createTechnician($this->bidangItik, 'Budi Santoso');
        $ticket = $this->createTicket($this->bidangItik, ['status' => 'verified']);
        $assignment = $this->createAssignment($ticket, $teknisi, [
            'status' => 'assigned',
            'respond_deadline' => Carbon::now()->addMinutes(30),
            'row_version' => 1,
        ]);

        $response = $this->actingAs($teknisi)->post(route('teknisi.tugas.terima', $assignment->id), [
            'row_version' => 1,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $assignment->refresh();
        $this->assertEquals('working', $assignment->status);
        $this->assertNotNull($assignment->accepted_at);
        $this->assertNotNull($assignment->started_at);
        $this->assertEquals(2, $assignment->row_version);

        $ticket->refresh();
        $this->assertEquals('in_progress', $ticket->status);
        $this->assertNotNull($ticket->pengerjaan_dimulai_at);

        $this->assertDatabaseHas('ticket_events', [
            'ticket_id' => $ticket->id,
            'assignment_id' => $assignment->id,
            'actor_id' => $teknisi->id,
            'type' => 'accepted',
        ]);
    }

    /**
     * 2. Test: Teknisi menolak penugasan dengan alasan minimal 10 karakter.
     * Status penugasan menjadi 'declined', tiket kembali 'verified' di antrean bidang, event tercatat.
     */
    public function test_teknisi_dapat_menolak_penugasan_dengan_alasan_valid(): void
    {
        $teknisi = $this->createTechnician($this->bidangItik, 'Joko Susilo');
        $ticket = $this->createTicket($this->bidangItik, ['status' => 'verified']);
        $assignment = $this->createAssignment($ticket, $teknisi, [
            'status' => 'assigned',
            'row_version' => 1,
        ]);

        // Uji validasi alasan < 10 karakter harus ditolak
        $invalidResponse = $this->actingAs($teknisi)->post(route('teknisi.tugas.tolak', $assignment->id), [
            'alasan' => 'Sibuk',
            'row_version' => 1,
        ]);
        $invalidResponse->assertSessionHasErrors('alasan');

        // Uji penolakan valid
        $reason = 'Sedang penanganan server kritis di Command Center.';
        $response = $this->actingAs($teknisi)->post(route('teknisi.tugas.tolak', $assignment->id), [
            'alasan' => $reason,
            'row_version' => 1,
        ]);

        $response->assertRedirect(route('teknisi.tugas.index'));
        $response->assertSessionHas('success');

        $assignment->refresh();
        $this->assertEquals('declined', $assignment->status);
        $this->assertEquals($reason, $assignment->decline_reason);
        $this->assertNotNull($assignment->unassigned_at);

        $ticket->refresh();
        $this->assertEquals('verified', $ticket->status);

        $this->assertDatabaseHas('ticket_events', [
            'ticket_id' => $ticket->id,
            'assignment_id' => $assignment->id,
            'actor_id' => $teknisi->id,
            'type' => 'declined',
        ]);
    }

    /**
     * 3. Test: Penugasan yang melewati batas konfirmasi (respond_deadline) otomatis dilepas oleh artisan command scheduler.
     * Status penugasan menjadi 'released', tiket kembali 'verified' jika tanpa teknisi lain, event tercatat.
     */
    public function test_penugasan_expired_otomatis_dilepas_oleh_command_scheduler(): void
    {
        $teknisi = $this->createTechnician($this->bidangItik, 'Hadi Prasetyo');
        $ticket = $this->createTicket($this->bidangItik, ['status' => 'verified']);
        $assignment = $this->createAssignment($ticket, $teknisi, [
            'status' => 'assigned',
            'assigned_at' => Carbon::now()->subMinutes(65),
            'respond_deadline' => Carbon::now()->subMinutes(5), // Telah kadaluarsa
            'row_version' => 1,
        ]);

        $exitCode = \Illuminate\Support\Facades\Artisan::call('assignments:release-expired');
        $this->assertEquals(0, $exitCode);

        $assignment->refresh();
        $this->assertEquals('released', $assignment->status);
        $this->assertNotNull($assignment->unassigned_at);

        $ticket->refresh();
        $this->assertEquals('verified', $ticket->status);

        $this->assertDatabaseHas('ticket_events', [
            'ticket_id' => $ticket->id,
            'assignment_id' => $assignment->id,
            'type' => 'released',
        ]);
    }

    /**
     * 4. Test: Pelaporan hambatan teknis (ISP/Vendor/OPD/Suku Cadang) TIDAK mengubah batas waktu atau menghentikan jam SLA.
     * Blocker tercatat open dengan reminder 4 jam, tiket SLA deadline tetap utuh.
     */
    public function test_pelaporan_hambatan_tidak_mengubah_atau_menghentikan_sla(): void
    {
        $teknisi = $this->createTechnician($this->bidangItik, 'Bambang Irawan');
        $originalDeadline = Carbon::now()->addHours(18);
        $ticket = $this->createTicket($this->bidangItik, [
            'status' => 'in_progress',
            'sla_deadline_at' => $originalDeadline,
        ]);
        $assignment = $this->createAssignment($ticket, $teknisi, [
            'status' => 'working',
            'row_version' => 1,
        ]);

        $response = $this->actingAs($teknisi)->post(route('teknisi.tugas.hambatan', $assignment->id), [
            'jenis' => 'isp',
            'bukti' => 'Nomor Tiket Telkom: IN18293041',
            'keterangan' => 'Kabel FO putus di jalan Cipto akibat pengerjaan drainase jalan.',
            'row_version' => 1,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        // Pastikan Blocker tersimpan
        $blocker = TicketBlocker::where('assignment_id', $assignment->id)->first();
        $this->assertNotNull($blocker);
        $this->assertEquals('isp', $blocker->jenis);
        $this->assertEquals('open', $blocker->status);
        $this->assertNotNull($blocker->next_reminder_at);

        // JAM SLA DILARANG BERUBAH
        $ticket->refresh();
        $this->assertEquals(
            $originalDeadline->format('Y-m-d H:i:s'),
            $ticket->sla_deadline_at->format('Y-m-d H:i:s'),
            'SLA deadline dilarang terpotong atau diperpanjang saat hambatan dilaporkan.'
        );

        $this->assertDatabaseHas('ticket_events', [
            'ticket_id' => $ticket->id,
            'assignment_id' => $assignment->id,
            'actor_id' => $teknisi->id,
            'type' => 'blocker_reported',
        ]);
    }

    /**
     * 5. Test: Pengajuan selesai oleh teknisi tidak menutup tiket secara sepihak.
     * Status penugasan menjadi 'submitted', tiket TETAP 'in_progress' menunggu verifikasi Admin Bidang.
     */
    public function test_pengajuan_selesai_oleh_teknisi_tidak_menutup_tiket_sendiri(): void
    {
        $teknisi = $this->createTechnician($this->bidangItik, 'Danang Wicaksono');
        $ticket = $this->createTicket($this->bidangItik, ['status' => 'in_progress']);
        $assignment = $this->createAssignment($ticket, $teknisi, [
            'status' => 'working',
            'row_version' => 1,
        ]);

        $note = 'Penggantian kabel patch cord selesai dan koneksi antar lantai telah normal.';
        $response = $this->actingAs($teknisi)->post(route('teknisi.tugas.submit', $assignment->id), [
            'catatan' => $note,
            'row_version' => 1,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $assignment->refresh();
        $this->assertEquals('approved', $assignment->status);
        $this->assertNotNull($assignment->submitted_at);
        $this->assertEquals($note, $assignment->notes);

        // Tiket langsung berstatus resolved diteruskan ke pelapor (bukan closed oleh pelapor)
        $ticket->refresh();
        $this->assertEquals('resolved', $ticket->status);
        $this->assertEquals($note, $ticket->catatan_resolusi);
        $this->assertNotNull($assignment->approved_at);

        $this->assertDatabaseHas('ticket_events', [
            'ticket_id' => $ticket->id,
            'assignment_id' => $assignment->id,
            'actor_id' => $teknisi->id,
            'type' => 'submitted',
        ]);
    }

    /**
     * 6. Test: Admin Bidang menyetujui atau mengembalikan hasil pekerjaan teknisi.
     * Saat dikembalikan: status penugasan kembali 'working' dengan alasan pengembalian.
     * Saat disetujui: status penugasan 'approved', tiket resmi 'resolved', dan waktu selesai terkunci di submitted_at.
     */
    public function test_admin_bidang_dapat_mengembalikan_dan_menyetujui_pengajuan_teknisi(): void
    {
        $adminBidang = User::create([
            'nama' => 'Admin Bidang ITIK Test',
            'username' => 'admin.itik.' . uniqid() . '@cirebonkota.go.id',
            'password' => bcrypt('Password123!'),
            'role_id' => $this->roleAdminBidang->id,
            'bidang_id' => $this->bidangItik->id,
            'avatar' => 'AB',
            'is_active' => true,
        ]);

        $teknisi = $this->createTechnician($this->bidangItik, 'Eko Wahyudi');
        $ticket = $this->createTicket($this->bidangItik, ['status' => 'in_progress']);
        $submittedAt = Carbon::now()->subMinutes(25);
        $assignment = $this->createAssignment($ticket, $teknisi, [
            'status' => 'submitted',
            'submitted_at' => $submittedAt,
            'notes' => 'Pekerjaan perbaikan konfigurasi switch lantai 2 telah selesai.',
            'row_version' => 1,
        ]);

        // A. Admin Bidang mengembalikan pekerjaan dengan catatan revisi
        $returnReason = 'Mohon sertakan hasil uji ping dan traceroute terbaru.';
        $returnResponse = $this->actingAs($adminBidang)->post(route('admin.bidang.tiket.reviewSubmission', $ticket->id), [
            'action' => 'return',
            'review_notes' => $returnReason,
        ]);

        $returnResponse->assertStatus(302);
        $returnResponse->assertSessionHas('success');

        $assignment->refresh();
        $this->assertEquals('working', $assignment->status);
        $this->assertNotNull($assignment->returned_at);
        $this->assertEquals($returnReason, $assignment->review_notes);

        $ticket->refresh();
        $this->assertEquals('in_progress', $ticket->status);

        $this->assertDatabaseHas('ticket_events', [
            'ticket_id' => $ticket->id,
            'assignment_id' => $assignment->id,
            'actor_id' => $adminBidang->id,
            'type' => 'returned',
        ]);

        // B. Teknisi mengajukan ulang dan Admin Bidang menyetujui
        $reSubmittedAt = Carbon::now()->subMinutes(10);
        $assignment->update([
            'status' => 'submitted',
            'submitted_at' => $reSubmittedAt,
            'notes' => 'Hasil uji ping 0% packet loss telah dilampirkan.',
        ]);

        $approveResponse = $this->actingAs($adminBidang)->post(route('admin.bidang.tiket.reviewSubmission', $ticket->id), [
            'action' => 'approve',
            'review_notes' => 'Hasil uji diverifikasi lengkap dan berfungsi normal.',
        ]);

        $approveResponse->assertStatus(302);
        $approveResponse->assertSessionHas('success');

        $assignment->refresh();
        $this->assertEquals('approved', $assignment->status);
        $this->assertNotNull($assignment->approved_at);

        $ticket->refresh();
        $this->assertEquals('resolved', $ticket->status);
        // SLA resolusi berhenti di submitted_at (waktu tunggu review admin tidak membebani teknisi)
        $this->assertEquals(
            $reSubmittedAt->format('Y-m-d H:i:s'),
            $assignment->submitted_at->format('Y-m-d H:i:s')
        );

        $this->assertDatabaseHas('ticket_events', [
            'ticket_id' => $ticket->id,
            'assignment_id' => $assignment->id,
            'actor_id' => $adminBidang->id,
            'type' => 'approved',
        ]);
    }

    /**
     * 7. Test: Proteksi Anti-Enumerasi (404 Not Found) jika teknisi mengakses penugasan orang lain atau TLP RED.
     */
    public function test_akses_ditolak_404_jika_mengakses_penugasan_orang_lain_atau_tlp_red(): void
    {
        $teknisiItik = $this->createTechnician($this->bidangItik, 'Faisal Akbar');
        $teknisiPemdi = $this->createTechnician($this->bidangPemdi, 'Gilang Ramadhan');
        $teknisiPersandian = $this->createTechnician($this->bidangPersandian, 'Haris Kurniawan');

        // Penugasan milik teknisi ITIK
        $ticketItik = $this->createTicket($this->bidangItik);
        $assignmentItik = $this->createAssignment($ticketItik, $teknisiItik);

        // Tiket TLP RED milik teknisi Persandian
        $ticketRed = $this->createTicket($this->bidangPersandian, ['tlp_level' => 'RED']);
        $assignmentRed = $this->createAssignment($ticketRed, $teknisiPersandian);

        // 1. Teknisi PEMDI mengakses penugasan milik Teknisi ITIK -> Wajib 404
        $resOther = $this->actingAs($teknisiPemdi)->get(route('teknisi.tugas.show', $assignmentItik->id));
        $resOther->assertStatus(404);

        // 2. Teknisi Non-Persandian (PEMDI) mencoba mengakses penugasan TLP RED -> Wajib 404
        $resRed = $this->actingAs($teknisiPemdi)->get(route('teknisi.tugas.show', $assignmentRed->id));
        $resRed->assertStatus(404);

        // 3. Teknisi pemilik yang sah (Persandian) dapat mengakses penugasan TLP RED miliknya
        $resOwnerRed = $this->actingAs($teknisiPersandian)->get(route('teknisi.tugas.show', $assignmentRed->id));
        $resOwnerRed->assertStatus(200);
    }

    /**
     * 8. Test: Race Condition Optimistic Locking (409 Conflict) saat row_version tidak cocok.
     */
    public function test_optimistic_locking_race_condition_menghasilkan_409_conflict(): void
    {
        $teknisi = $this->createTechnician($this->bidangItik, 'Indra Pratama');
        $ticket = $this->createTicket($this->bidangItik);
        $assignment = $this->createAssignment($ticket, $teknisi, [
            'status' => 'assigned',
            'row_version' => 3, // Versi saat ini di database adalah 3
        ]);

        // Request mengirim versi basi (row_version = 1)
        $response = $this->actingAs($teknisi)->post(route('teknisi.tugas.terima', $assignment->id), [
            'row_version' => 1,
        ]);

        $response->assertStatus(409);
    }

    /**
     * 9. Test: Tabel ticket_events bersifat strictly append-only.
     * Operasi UPDATE dan DELETE langsung dicegah baik oleh Trigger PostgreSQL maupun ORM guard.
     */
    public function test_ticket_events_append_only_mencegah_update_dan_delete(): void
    {
        $teknisi = $this->createTechnician($this->bidangItik, 'Kurnia Mega');
        $ticket = $this->createTicket($this->bidangItik);
        $assignment = $this->createAssignment($ticket, $teknisi);

        $event = TicketEvent::create([
            'ticket_id' => $ticket->id,
            'assignment_id' => $assignment->id,
            'actor_id' => $teknisi->id,
            'type' => 'progress_added',
            'payload' => ['note' => 'Catatan progres uji coba integrity'],
            'created_at' => Carbon::now(),
        ]);

        // A. Uji larangan UPDATE via Direct DB Query (PostgreSQL Trigger)
        $updateTriggerBlocked = false;
        try {
            DB::beginTransaction(); // Membuka savepoint di dalam database transaction
            DB::table('ticket_events')->where('id', $event->id)->update(['type' => 'tampered']);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $updateTriggerBlocked = str_contains($e->getMessage(), 'append-only');
        }
        $this->assertTrue($updateTriggerBlocked, 'PostgreSQL trigger harus menggagalkan UPDATE pada ticket_events.');

        // B. Uji larangan DELETE via Direct DB Query (PostgreSQL Trigger)
        $deleteTriggerBlocked = false;
        try {
            DB::beginTransaction(); // Membuka savepoint di dalam database transaction
            DB::table('ticket_events')->where('id', $event->id)->delete();
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $deleteTriggerBlocked = str_contains($e->getMessage(), 'append-only');
        }
        $this->assertTrue($deleteTriggerBlocked, 'PostgreSQL trigger harus menggagalkan DELETE pada ticket_events.');

        // C. Uji larangan UPDATE via Eloquent Model Guard
        $eloquentUpdateBlocked = false;
        try {
            $event->type = 'tampered';
            $event->save();
        } catch (\RuntimeException $e) {
            $eloquentUpdateBlocked = str_contains($e->getMessage(), 'append-only');
        }
        $this->assertTrue($eloquentUpdateBlocked, 'Eloquent Model guard harus menolak method save/update pada TicketEvent.');

        // D. Uji larangan DELETE via Eloquent Model Guard
        $eloquentDeleteBlocked = false;
        try {
            $event->delete();
        } catch (\RuntimeException $e) {
            $eloquentDeleteBlocked = str_contains($e->getMessage(), 'append-only');
        }
        $this->assertTrue($eloquentDeleteBlocked, 'Eloquent Model guard harus menolak method delete pada TicketEvent.');
    }
}
