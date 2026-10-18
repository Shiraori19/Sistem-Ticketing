<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('periode_bulan', 7); // Format: YYYY-MM
            $table->string('filter_hash', 64)->default('default');
            $table->jsonb('payload_json');
            $table->timestamp('frozen_at');
            $table->foreignId('frozen_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['periode_bulan', 'filter_hash'], 'report_snapshots_periode_filter_unique');
            $table->index('periode_bulan', 'report_snapshots_periode_bulan_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_snapshots');
    }
};
