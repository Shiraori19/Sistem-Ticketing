<?php

namespace Tests\Feature\Security;

use App\Models\Bidang;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UserMassAssignmentHardeningTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminPusat;
    protected User $adminBidang;
    protected Role $rolePusat;
    protected Role $roleBidang;
    protected Role $roleTeknisi;
    protected Bidang $bidang;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rolePusat = Role::firstOrCreate(['nama_role' => 'Admin Pusat']);
        $this->roleBidang = Role::firstOrCreate(['nama_role' => 'Admin Bidang']);
        $this->roleTeknisi = Role::firstOrCreate(['nama_role' => 'Teknisi']);

        $this->bidang = Bidang::firstOrCreate(
            ['kode' => 'PEMDI'],
            ['nama_bidang' => 'Pemerintahan Digital', 'is_persandian' => false]
        );

        $this->adminPusat = User::firstOrCreate(
            ['username' => 'admin.audit.pusat'],
            [
                'nama' => 'Admin Audit Pusat',
                'password' => bcrypt('Password123!'),
                'role_id' => $this->rolePusat->id,
                'bidang_id' => null,
                'is_active' => true,
            ]
        );

        $this->adminBidang = User::firstOrCreate(
            ['username' => 'admin.audit.bidang'],
            [
                'nama' => 'Admin Audit Bidang',
                'password' => bcrypt('Password123!'),
                'role_id' => $this->roleBidang->id,
                'bidang_id' => $this->bidang->id,
                'is_active' => true,
            ]
        );
    }

    /**
     * Memastikan role_id dan bidang_id tidak ada dalam fillable array Model User (Remediasi vuln-0001)
     */
    public function test_user_model_does_not_expose_role_id_and_bidang_id_in_fillable(): void
    {
        $user = new User();
        $fillable = $user->getFillable();

        $this->assertNotContains('role_id', $fillable, 'role_id must not be mass-assignable in User::$fillable');
        $this->assertNotContains('bidang_id', $fillable, 'bidang_id must not be mass-assignable in User::$fillable');
    }

    /**
     * Memastikan saat reguard aktif, User::create() mengabaikan role_id dan bidang_id
     */
    public function test_mass_assignment_ignores_role_and_bidang_when_reguarded(): void
    {
        Model::reguard();

        try {
            $user = new User([
                'nama' => 'Attacker Escalation Test',
                'username' => 'attacker.escalate',
                'role_id' => $this->rolePusat->id,
                'bidang_id' => $this->bidang->id,
            ]);

            $this->assertNull($user->role_id, 'role_id should not be set via mass assignment fill');
            $this->assertNull($user->bidang_id, 'bidang_id should not be set via mass assignment fill');
        } finally {
            Model::unguard();
        }
    }

    /**
     * Memastikan pengguna non-Admin Pusat ditolak (403) saat mencoba mengakses manajemen user
     */
    public function test_non_admin_pusat_cannot_access_user_endpoints(): void
    {
        $response = $this->actingAs($this->adminBidang)->post(route('admin.users.store'), [
            'nama' => 'Petugas Ilegal',
            'username' => 'petugas.ilegal',
            'password' => 'Password123!',
            'role_id' => $this->rolePusat->id,
        ]);

        $response->assertStatus(403);
    }

    /**
     * Memastikan Admin Pusat dapat membuat user baru dengan penetapan eksplisit
     */
    public function test_admin_pusat_can_create_user_with_explicit_assignment(): void
    {
        $username = 'petugas.uji.' . time();

        $response = $this->actingAs($this->adminPusat)->post(route('admin.users.store'), [
            'nama' => 'Petugas Uji Baru',
            'username' => $username,
            'password' => 'StandarKuat123!',
            'role_id' => $this->roleBidang->id,
            'bidang_id' => $this->bidang->id,
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        $created = User::where('username', $username)->first();
        $this->assertNotNull($created);
        $this->assertEquals($this->roleBidang->id, $created->role_id);
        $this->assertEquals($this->bidang->id, $created->bidang_id);
    }

    /**
     * Memastikan Admin Pusat tidak dapat menurunkan peran akunnya sendiri saat sedang login
     */
    public function test_admin_pusat_cannot_demote_themselves(): void
    {
        $response = $this->actingAs($this->adminPusat)->put(route('admin.users.update', $this->adminPusat->id), [
            'nama' => $this->adminPusat->nama,
            'username' => $this->adminPusat->username,
            'role_id' => $this->roleTeknisi->id,
            'bidang_id' => $this->bidang->id,
        ]);

        $response->assertSessionHas('error');
        $this->adminPusat->refresh();
        $this->assertEquals($this->rolePusat->id, $this->adminPusat->role_id);
    }
}
