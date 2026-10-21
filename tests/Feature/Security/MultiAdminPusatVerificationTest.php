<?php

namespace Tests\Feature\Security;

use App\Models\Bidang;
use App\Models\Category;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MultiAdminPusatVerificationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin1;
    protected User $admin2;
    protected Bidang $bidang;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $rolePusat = Role::firstOrCreate(['nama_role' => 'Admin Pusat']);

        $this->admin1 = User::firstOrCreate(
            ['username' => 'admin.multi1'],
            ['nama' => 'Admin Pusat 1', 'password' => bcrypt('password'), 'role_id' => $rolePusat->id, 'is_active' => true]
        );

        $this->admin2 = User::firstOrCreate(
            ['username' => 'admin.multi2'],
            ['nama' => 'Admin Pusat 2', 'password' => bcrypt('password'), 'role_id' => $rolePusat->id, 'is_active' => true]
        );

        $this->bidang = Bidang::firstOrCreate(
            ['kode' => 'PEMDI'],
            ['nama_bidang' => 'Pemerintahan Digital', 'is_persandian' => false]
        );

        $this->category = Category::firstOrCreate(
            ['nama_kategori' => 'Aplikasi OPD'],
            ['bidang_id' => $this->bidang->id]
        );
    }

    /**
     * Uji alokasi tiket antrean atomik antar-Admin Pusat (claimNext)
     */
    public function test_claim_next_allocates_distinct_tickets_without_race_condition(): void
    {
        $ticket1 = Ticket::create([
            'kode_tiket' => 'TIK-CLM-001',
            'nama_pelapor' => 'Pelapor A',
            'nip_pelapor' => '198001012000011001',
            'no_whatsapp' => '081211111111',
            'skpd' => 'Dinas Kesehatan',
            'judul' => 'Kendala Aplikasi Antrean',
            'deskripsi' => 'Deskripsi kendala A',
            'status' => 'pending_verification',
            'category_id' => $this->category->id,
            'pin_hash' => bcrypt('111111'),
        ]);

        $ticket2 = Ticket::create([
            'kode_tiket' => 'TIK-CLM-002',
            'nama_pelapor' => 'Pelapor B',
            'nip_pelapor' => '198001012000011002',
            'no_whatsapp' => '081222222222',
            'skpd' => 'Dinas Pendidikan',
            'judul' => 'Kendala Jaringan Sekolah',
            'deskripsi' => 'Deskripsi kendala B',
            'status' => 'pending_verification',
            'category_id' => $this->category->id,
            'pin_hash' => bcrypt('222222'),
        ]);

        // Admin 1 klaim tiket pertama
        $this->actingAs($this->admin1)
            ->post(route('admin.verifikasi.claimNext'));

        // Admin 2 klaim berikutnya -> harus mendapatkan tiket berbeda
        $this->actingAs($this->admin2)
            ->post(route('admin.verifikasi.claimNext'));

        $claimed1 = Ticket::where('claimed_by', $this->admin1->id)->first();
        $claimed2 = Ticket::where('claimed_by', $this->admin2->id)->first();

        $this->assertNotNull($claimed1);
        $this->assertNotNull($claimed2);
        $this->assertNotEquals($claimed1->id, $claimed2->id);
    }

    /**
     * Uji kunci kedaluwarsa setelah 10 menit (Soft lock expiry)
     */
    public function test_expired_claim_can_be_claimed_by_another_admin(): void
    {
        $ticket = Ticket::create([
            'kode_tiket' => 'TIK-CLM-EXP',
            'nama_pelapor' => 'Pelapor C',
            'nip_pelapor' => '198001012000011003',
            'no_whatsapp' => '081233333333',
            'skpd' => 'Bapenda',
            'judul' => 'Kendala E-PBB',
            'deskripsi' => 'Deskripsi kendala C',
            'status' => 'pending_verification',
            'category_id' => $this->category->id,
            'pin_hash' => bcrypt('333333'),
            'claimed_by' => $this->admin1->id,
            'claimed_at' => now()->subMinutes(15), // Sudah 15 menit lalu
        ]);

        $this->assertTrue($ticket->isClaimExpired());
        $this->assertTrue($ticket->canBeClaimedBy($this->admin2->id));

        // Admin 2 mengklaim manual tiket tersebut
        $response = $this->actingAs($this->admin2)
            ->post(route('admin.verifikasi.claim', $ticket->id));

        $response->assertRedirect();
        $ticket->refresh();
        $this->assertEquals($this->admin2->id, $ticket->claimed_by);
    }

    /**
     * Uji Pencegahan Tabrakan Verifikasi melalui Kunci Optimistik (row_version 409 Conflict)
     */
    public function test_optimistic_locking_prevents_concurrent_verification_conflict(): void
    {
        $ticket = Ticket::create([
            'kode_tiket' => 'TIK-CLM-OPT',
            'nama_pelapor' => 'Pelapor D',
            'nip_pelapor' => '198001012000011004',
            'no_whatsapp' => '081244444444',
            'skpd' => 'BKPSDM',
            'judul' => 'Gangguan Server E-Kinerja',
            'deskripsi' => 'Deskripsi kendala D',
            'status' => 'pending_verification',
            'category_id' => $this->category->id,
            'pin_hash' => bcrypt('444444'),
            'row_version' => 1,
        ]);

        // Verifikasi pertama sukses menaikkan row_version
        $this->actingAs($this->admin1)
            ->post(route('admin.verifikasi.verify', $ticket->id), [
                'tlp_level' => 'GREEN',
                'bidang_id' => $this->bidang->id,
                'notes' => 'Diteruskan ke Pemdi',
                'row_version' => 1,
            ]);

        $ticket->refresh();
        $this->assertEquals('verified', $ticket->status);
        $this->assertGreaterThan(1, $ticket->row_version);

        // Verifikasi kedua dengan row_version usang menghasilkan 409 Conflict
        $conflictResponse = $this->actingAs($this->admin2)
            ->post(route('admin.verifikasi.verify', $ticket->id), [
                'tlp_level' => 'AMBER',
                'bidang_id' => $this->bidang->id,
                'notes' => 'Mencoba verifikasi tabrakan',
                'row_version' => 1, // Usang
            ]);

        $conflictResponse->assertStatus(409);
    }

    /**
     * Uji Proteksi Admin Pusat Terakhir dan Diri Sendiri (BUTIR 2)
     */
    public function test_cannot_deactivate_or_delete_last_active_admin_pusat(): void
    {
        // Nonaktifkan admin 2 sehingga admin 1 adalah satu-satunya Admin Pusat aktif
        $this->admin2->update(['is_active' => false]);

        // Admin 1 mencoba menonaktifkan dirinya sendiri
        $response = $this->actingAs($this->admin1)
            ->post(route('admin.users.toggleStatus', $this->admin1->id));

        $response->assertSessionHas('error');
        $this->admin1->refresh();
        $this->assertTrue($this->admin1->is_active);

        // Admin 1 mencoba menghapus akunnya sendiri
        $deleteResponse = $this->actingAs($this->admin1)
            ->delete(route('admin.users.destroy', $this->admin1->id));

        $deleteResponse->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $this->admin1->id]);
    }
}
