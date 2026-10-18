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
        // 1. Tambah kolom parent-child linking pada tabel tickets
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('id')
                ->constrained('tickets')
                ->nullOnDelete();

            $table->boolean('is_parent')
                ->default(false)
                ->after('parent_id');
        });

        // 2. Modifikasi kolom status agar mendukung 'pending_info'
        // Jika PostgreSQL, sesuaikan check constraint
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE tickets DROP CONSTRAINT IF EXISTS tickets_status_check;");
            DB::statement("ALTER TABLE tickets ADD CONSTRAINT tickets_status_check CHECK (status::text = ANY (ARRAY[
                'pending_verification'::text,
                'pending_info'::text,
                'verified'::text,
                'in_progress'::text,
                'resolved'::text,
                'reopened'::text,
                'eskalasi_pusat'::text,
                'force_closed'::text,
                'closed'::text,
                'cancelled'::text
            ]));");
        }

        // 3. Tabel Relasi Penugasan Multi-Teknisi (Team Collaboration)
        Schema::create('ticket_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('technician_name', 150);
            $table->string('role_in_task', 50)->default('lead_pic'); // 'lead_pic', 'member', 'specialist'
            $table->timestamp('assigned_at')->useCurrent();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['ticket_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_assignments');

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'is_parent']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE tickets DROP CONSTRAINT IF EXISTS tickets_status_check;");
            DB::statement("ALTER TABLE tickets ADD CONSTRAINT tickets_status_check CHECK (status::text = ANY (ARRAY[
                'pending_verification'::text,
                'verified'::text,
                'in_progress'::text,
                'resolved'::text,
                'reopened'::text,
                'eskalasi_pusat'::text,
                'force_closed'::text,
                'closed'::text,
                'cancelled'::text
            ]));");
        }
    }
};
