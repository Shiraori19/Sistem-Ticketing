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
        // 1. Tambah kolom eksplisit is_persandian pada tabel bidang
        if (!Schema::hasColumn('bidang', 'is_persandian')) {
            Schema::table('bidang', function (Blueprint $table) {
                $table->boolean('is_persandian')->default(false)->after('kode');
                $table->index('is_persandian');
            });

            // Set is_persandian = true untuk bidang Persandian yang ada
            DB::table('bidang')
                ->where('kode', 'PERSANDIAN')
                ->orWhere('nama_bidang', 'LIKE', '%Persandian%')
                ->update(['is_persandian' => true]);
        }

        // 2. Buat tabel ticket_bidang_involvement (Keterlibatan Lintas Bidang untuk Tiket TLP: RED & Dukungan Teknis)
        if (!Schema::hasTable('ticket_bidang_involvement')) {
            Schema::create('ticket_bidang_involvement', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ticket_id')->constrained('tickets')->onDelete('cascade');
                $table->foreignId('bidang_id')->constrained('bidang')->onDelete('cascade');
                $table->string('peran', 20)->default('ditembuskan'); // 'pelaksana', 'ditembuskan', 'pendukung'
                $table->timestamp('notified_at')->nullable();
                $table->timestamp('acknowledged_at')->nullable();
                $table->foreignId('ditetapkan_oleh')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['ticket_id', 'bidang_id', 'peran'], 'ticket_bidang_peran_unique');
                $table->index(['bidang_id', 'peran']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_bidang_involvement');

        if (Schema::hasColumn('bidang', 'is_persandian')) {
            Schema::table('bidang', function (Blueprint $table) {
                $table->dropIndex(['is_persandian']);
                $table->dropColumn('is_persandian');
            });
        }
    }
};
