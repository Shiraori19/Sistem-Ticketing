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
        Schema::table('tickets', function (Blueprint $table) {
            $table->timestamp('sla_deadline_at')->nullable()->after('sla_dimulai_at')->index();
        });

        // Backfill kalkulasi batas waktu SLA untuk tiket yang sudah memiliki sla_dimulai_at
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("UPDATE tickets SET sla_deadline_at = sla_dimulai_at + (COALESCE(sla_resolusi_jam, 4) || ' hours')::interval WHERE sla_dimulai_at IS NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['sla_deadline_at']);
            $table->dropColumn('sla_deadline_at');
        });
    }
};
