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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('kode_tiket', 30)->unique(); // format: TIK-YYYYMM-XXXX
            
            // Data pelapor (BUKAN FK ke users)
            $table->string('nip_pelapor', 30);
            $table->string('nama_pelapor', 150);
            $table->string('skpd', 150);
            $table->string('no_whatsapp', 25);
            $table->string('pin', 10)->nullable();
            
            // Detail Tiket
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->string('judul', 255);
            $table->text('deskripsi');
            $table->string('lampiran_pelapor')->nullable();
            $table->string('lampiran_tambahan')->nullable();
            $table->text('catatan_kelengkapan')->nullable();
            $table->enum('tlp_level', ['RED', 'AMBER', 'GREEN', 'CLEAR'])->nullable();
            
            // Routing & Approval
            $table->foreignId('bidang_id')->nullable()->constrained('bidang')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            
            // Status Tiket
            $table->enum('status', [
                'pending_verification',
                'verified',
                'in_progress',
                'resolved',
                'reopened',
                'eskalasi_pusat',
                'force_closed',
                'closed',
                'cancelled',
            ])->default('pending_verification');
            
            // Penugasan & SLA
            $table->string('teknisi_lapangan')->nullable(); // varchar, teks bebas, bukan FK
            $table->timestamp('sla_dimulai_at')->nullable();
            $table->decimal('sla_respon_jam', 5, 2)->nullable();
            $table->integer('sla_resolusi_jam')->nullable();
            $table->boolean('sla_breach')->default(false);
            
            // Resolusi
            $table->string('bukti_resolusi')->nullable();
            $table->text('catatan_resolusi')->nullable();
            $table->boolean('is_confidential_evidence')->default(false);
            
            // Reopen & Force Close
            $table->integer('reopen_count')->default(0);
            $table->integer('max_reopen')->default(2);
            $table->string('bukti_reopen_tambahan')->nullable();
            $table->text('force_close_notes')->nullable();
            
            // Feedback Pelapor
            $table->tinyInteger('rating')->nullable(); // 1-5
            $table->text('feedback')->nullable();
            
            // Histori Status (PostgreSQL JSONB)
            $table->jsonb('status_history')->default('[]');
            
            $table->timestamps();
            
            // Index Wajib
            $table->index('status');
            $table->index('bidang_id');
            $table->index('tlp_level');
            $table->index('no_whatsapp');
        });

        // PostgreSQL GIN Index untuk status_history JSONB
        DB::statement('CREATE INDEX tickets_status_history_gin ON tickets USING gin (status_history);');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS tickets_status_history_gin;');
        Schema::dropIfExists('tickets');
    }
};
