<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Perlebar kolom pin dari varchar(10) menjadi varchar(255) agar mendukung bcrypt/argon hash
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE tickets ALTER COLUMN pin TYPE varchar(255);");
        } else {
            Schema::table('tickets', function (Blueprint $table) {
                $table->string('pin', 255)->nullable()->change();
            });
        }

        // 2. Tambah kolom sla_respon_deadline_at terindeks & composite index pada tickets
        Schema::table('tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('tickets', 'sla_respon_deadline_at')) {
                $table->timestamp('sla_respon_deadline_at')->nullable()->after('sla_dimulai_at')->index();
            }

            // Composite index untuk mengoptimalkan kueri riwayat terkait berbasis no_whatsapp & status
            $table->index(['no_whatsapp', 'status', 'id'], 'tickets_wa_status_id_idx');
        });

        // 3. Backfill nilai sla_respon_deadline_at untuk tiket yang sudah ada
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("
                UPDATE tickets 
                SET sla_respon_deadline_at = created_at + (COALESCE(sla_respon_jam, 72) || ' hours')::interval 
                WHERE sla_respon_deadline_at IS NULL AND created_at IS NOT NULL
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex('tickets_wa_status_id_idx');

            if (Schema::hasColumn('tickets', 'sla_respon_deadline_at')) {
                $table->dropIndex(['sla_respon_deadline_at']);
                $table->dropColumn('sla_respon_deadline_at');
            }
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE tickets ALTER COLUMN pin TYPE varchar(10);");
        } else {
            Schema::table('tickets', function (Blueprint $table) {
                $table->string('pin', 10)->nullable()->change();
            });
        }
    }
};
