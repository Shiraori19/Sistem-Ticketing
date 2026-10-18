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
        Schema::table('tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('tickets', 'catatan_kelengkapan')) {
                $table->text('catatan_kelengkapan')->nullable()->after('lampiran_pelapor');
            }
            if (!Schema::hasColumn('tickets', 'lampiran_tambahan')) {
                $table->string('lampiran_tambahan')->nullable()->after('catatan_kelengkapan');
            }
            if (!Schema::hasColumn('tickets', 'pin')) {
                $table->string('pin', 10)->nullable()->after('no_whatsapp');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['catatan_kelengkapan', 'lampiran_tambahan', 'pin']);
        });
    }
};
