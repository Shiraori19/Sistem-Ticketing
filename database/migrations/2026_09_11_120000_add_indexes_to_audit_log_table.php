<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('audit_log', function (Blueprint $table) {
            $table->index('aksi', 'audit_log_aksi_index');
            $table->index('timestamp', 'audit_log_timestamp_index');
            $table->index('target_kode', 'audit_log_target_kode_index');
            $table->index(['aksi', 'timestamp'], 'audit_log_aksi_timestamp_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_log', function (Blueprint $table) {
            $table->dropIndex('audit_log_aksi_index');
            $table->dropIndex('audit_log_timestamp_index');
            $table->dropIndex('audit_log_target_kode_index');
            $table->dropIndex('audit_log_aksi_timestamp_index');
        });
    }
};
