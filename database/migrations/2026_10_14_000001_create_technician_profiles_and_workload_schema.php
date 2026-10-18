<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tambah kolom is_active pada tabel users (Admin Pusat dapat menonaktifkan akun teknisi)
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('remember_token');
                    $table->index('is_active');
                }
            });
        }

        // 2. Buat tabel technician_profiles
        if (!Schema::hasTable('technician_profiles')) {
            Schema::create('technician_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
                $table->string('availability', 20)->default('tersedia'); // 'tersedia', 'sibuk', 'cuti'
                $table->integer('max_load')->default(6);
                $table->json('skills')->nullable(); // array kategori ID atau nama kategori keahlian
                $table->timestamps();

                $table->index(['availability', 'max_load']);
            });
        }

        // 3. Modifikasi tabel ticket_assignments untuk integrasi akun & timestamp siklus lengkap
        if (Schema::hasTable('ticket_assignments')) {
            Schema::table('ticket_assignments', function (Blueprint $table) {
                if (!Schema::hasColumn('ticket_assignments', 'role')) {
                    // role penugasan: 'lead' (penanggung jawab) atau 'pendamping'
                    $table->string('role', 20)->default('lead')->after('user_id');
                }
                if (!Schema::hasColumn('ticket_assignments', 'accepted_at')) {
                    $table->timestamp('accepted_at')->nullable()->after('assigned_at');
                }
                if (!Schema::hasColumn('ticket_assignments', 'started_at')) {
                    $table->timestamp('started_at')->nullable()->after('accepted_at');
                }
                if (!Schema::hasColumn('ticket_assignments', 'approved_at')) {
                    $table->timestamp('approved_at')->nullable()->after('submitted_at');
                }
                if (!Schema::hasColumn('ticket_assignments', 'unassigned_at')) {
                    $table->timestamp('unassigned_at')->nullable()->after('approved_at');
                    $table->text('alasan_unassign')->nullable()->after('unassigned_at');
                }
            });

            // 4. Normalisasi data lama sebelum pasang unique index:
            // - Jika role_in_task adalah 'member', set role = 'pendamping'
            DB::table('ticket_assignments')
                ->where('role_in_task', 'member')
                ->update(['role' => 'pendamping']);

            // - Jika ada beberapa lead pada tiket yang sama tanpa unassigned_at, simpan yang paling baru dan tutup yang lama
            $duplicateLeads = DB::table('ticket_assignments')
                ->select('ticket_id')
                ->where('role', 'lead')
                ->whereNull('unassigned_at')
                ->groupBy('ticket_id')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('ticket_id');

            foreach ($duplicateLeads as $tId) {
                $leadIds = DB::table('ticket_assignments')
                    ->where('ticket_id', $tId)
                    ->where('role', 'lead')
                    ->whereNull('unassigned_at')
                    ->orderBy('id', 'asc')
                    ->pluck('id')
                    ->toArray();

                // Biarkan yang terakhir aktif, tutup yang lebih lama
                $latestId = array_pop($leadIds);
                if (!empty($leadIds)) {
                    DB::table('ticket_assignments')
                        ->whereIn('id', $leadIds)
                        ->update([
                            'unassigned_at' => now(),
                            'alasan_unassign' => 'Penugasan lama ditutup otomatis saat migrasi sistem',
                        ]);
                }
            }

            // Index performa untuk agregasi beban kerja
            Schema::table('ticket_assignments', function (Blueprint $table) {
                $table->index(['user_id', 'status', 'unassigned_at'], 'idx_ta_user_workload');
                $table->index(['ticket_id', 'role', 'unassigned_at'], 'idx_ta_ticket_lead');
            });

            // 5. PostgreSQL Partial Unique Index: Tepat 1 lead aktif per tiket
            if (DB::getDriverName() === 'pgsql') {
                DB::statement("
                    CREATE UNIQUE INDEX IF NOT EXISTS uniq_active_lead_per_ticket 
                    ON ticket_assignments (ticket_id) 
                    WHERE role = 'lead' AND unassigned_at IS NULL;
                ");
            }
        }

        // 5. Migrasikan data profil awal bagi akun teknisi yang sudah ada di database
        $teknisiRole = DB::table('roles')->where('nama_role', 'Teknisi')->first();
        $teknisiUsers = $teknisiRole 
            ? DB::table('users')->where('role_id', $teknisiRole->id)->get() 
            : collect();

        foreach ($teknisiUsers as $user) {
            DB::table('technician_profiles')->insertOrIgnore([
                'user_id' => $user->id,
                'availability' => 'tersedia',
                'max_load' => 6,
                'skills' => json_encode(['Jaringan & Internet', 'Sistem Informasi', 'Keamanan Siber', 'Hardware & Server']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 6. Migrasikan data lama penugasan tiket: tautkan user_id jika nama cocok
        $assignmentsWithoutUser = DB::table('ticket_assignments')
            ->whereNull('user_id')
            ->whereNotNull('technician_name')
            ->get();

        foreach ($assignmentsWithoutUser as $assign) {
            $matchedUser = DB::table('users')
                ->where('nama', $assign->technician_name)
                ->orWhere('username', $assign->technician_name)
                ->first();

            if ($matchedUser) {
                DB::table('ticket_assignments')
                    ->where('id', $assign->id)
                    ->update([
                        'user_id' => $matchedUser->id,
                        'role' => $assign->role_in_task === 'member' ? 'pendamping' : 'lead',
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("DROP INDEX IF EXISTS uniq_active_lead_per_ticket;");
        }

        Schema::dropIfExists('technician_profiles');

        if (Schema::hasTable('ticket_assignments')) {
            Schema::table('ticket_assignments', function (Blueprint $table) {
                $table->dropIndex('idx_ta_user_workload');
                $table->dropIndex('idx_ta_ticket_lead');
                $table->dropColumn([
                    'role', 'accepted_at', 'started_at', 'approved_at', 'unassigned_at', 'alasan_unassign'
                ]);
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
};
