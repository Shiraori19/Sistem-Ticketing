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
        // 1. Ubah tipe kolom lampiran_pelapor menjadi JSONB (PostgreSQL) atau JSON agar mendukung multi-upload array
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE tickets ALTER COLUMN lampiran_pelapor TYPE jsonb USING CASE 
                WHEN lampiran_pelapor IS NULL THEN NULL 
                WHEN lampiran_pelapor LIKE '[%' THEN lampiran_pelapor::jsonb 
                ELSE jsonb_build_array(lampiran_pelapor) 
            END;");
        } else {
            Schema::table('tickets', function (Blueprint $table) {
                $table->json('lampiran_pelapor')->nullable()->change();
            });
        }

        // 2. Set default SLA Respon (72 jam / 3x24 jam) dan SLA Resolusi (168 jam / 7 hari)
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("UPDATE tickets SET sla_respon_jam = 72.00 WHERE sla_respon_jam IS NULL;");
            DB::statement("UPDATE tickets SET sla_resolusi_jam = 168 WHERE sla_resolusi_jam IS NULL;");
            DB::statement("UPDATE categories SET sla_respon_jam = 72.00 WHERE sla_respon_jam IS NULL;");
            DB::statement("UPDATE categories SET sla_resolusi_jam = 168 WHERE sla_resolusi_jam IS NULL;");
        }

        Schema::table('categories', function (Blueprint $table) {
            $table->decimal('sla_respon_jam', 5, 2)->nullable()->default(72.00)->change();
            $table->integer('sla_resolusi_jam')->nullable()->default(168)->change();
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->decimal('sla_respon_jam', 5, 2)->nullable()->default(72.00)->change();
            $table->integer('sla_resolusi_jam')->nullable()->default(168)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE tickets ALTER COLUMN lampiran_pelapor TYPE varchar(255) USING lampiran_pelapor::text;");
        } else {
            Schema::table('tickets', function (Blueprint $table) {
                $table->string('lampiran_pelapor', 255)->nullable()->change();
            });
        }

        Schema::table('categories', function (Blueprint $table) {
            $table->decimal('sla_respon_jam', 5, 2)->default(0.50)->change();
            $table->integer('sla_resolusi_jam')->default(4)->change();
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->decimal('sla_respon_jam', 5, 2)->nullable()->change();
            $table->integer('sla_resolusi_jam')->nullable()->change();
        });
    }
};
