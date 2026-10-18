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
        // 1. Tambahkan Role 'Teknisi' jika tabel roles ada
        if (Schema::hasTable('roles')) {
            DB::table('roles')->insertOrIgnore([
                'nama_role' => 'Teknisi',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Modifikasi tabel ticket_assignments untuk siklus status penugasan & review
        if (Schema::hasTable('ticket_assignments')) {
            Schema::table('ticket_assignments', function (Blueprint $table) {
                if (!Schema::hasColumn('ticket_assignments', 'status')) {
                    $table->string('status', 30)->default('assigned')->after('role_in_task');
                }
                if (!Schema::hasColumn('ticket_assignments', 'decline_reason')) {
                    $table->text('decline_reason')->nullable()->after('status');
                }
                if (!Schema::hasColumn('ticket_assignments', 'submitted_at')) {
                    $table->timestamp('submitted_at')->nullable()->after('decline_reason');
                }
                if (!Schema::hasColumn('ticket_assignments', 'reviewed_at')) {
                    $table->timestamp('reviewed_at')->nullable()->after('submitted_at');
                    $table->unsignedBigInteger('reviewed_by')->nullable()->after('reviewed_at');
                    $table->text('review_notes')->nullable()->after('reviewed_by');
                }
                if (!Schema::hasColumn('ticket_assignments', 'worklog')) {
                    $table->json('worklog')->nullable()->after('review_notes');
                }
            });
        }

        // 3. Buat tabel ticket_obstacles untuk pelaporan hambatan teknisi (SLA TETAP JALAN)
        if (!Schema::hasTable('ticket_obstacles')) {
            Schema::create('ticket_obstacles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ticket_id')->constrained('tickets')->onDelete('cascade');
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('obstacle_type', 100); // 'Menunggu ISP', 'Menunggu vendor', 'Menunggu OPD lain', 'Menunggu suku cadang'
                $table->text('description');
                $table->string('evidence_path', 255)->nullable();
                $table->timestamp('reported_at')->useCurrent();
                $table->timestamp('follow_up_deadline')->nullable();
                $table->timestamps();

                $table->index(['ticket_id', 'obstacle_type']);
            });
        }

        // 4. Tambahkan kolom Pengecualian SLA (Excused Breach) dan Pengerjaan pada tickets
        if (Schema::hasTable('tickets')) {
            Schema::table('tickets', function (Blueprint $table) {
                if (!Schema::hasColumn('tickets', 'sla_excused')) {
                    $table->boolean('sla_excused')->default(false)->after('sla_breach');
                }
                if (!Schema::hasColumn('tickets', 'sla_excused_reason')) {
                    $table->text('sla_excused_reason')->nullable()->after('sla_excused');
                }
                if (!Schema::hasColumn('tickets', 'sla_excused_by')) {
                    $table->unsignedBigInteger('sla_excused_by')->nullable()->after('sla_excused_reason');
                }
                if (!Schema::hasColumn('tickets', 'sla_excused_at')) {
                    $table->timestamp('sla_excused_at')->nullable()->after('sla_excused_by');
                }
                if (!Schema::hasColumn('tickets', 'sla_excused_evidence_path')) {
                    $table->string('sla_excused_evidence_path', 255)->nullable()->after('sla_excused_at');
                }
                if (!Schema::hasColumn('tickets', 'pengerjaan_dimulai_at')) {
                    $table->timestamp('pengerjaan_dimulai_at')->nullable()->after('sla_dimulai_at');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_obstacles');

        if (Schema::hasTable('ticket_assignments')) {
            Schema::table('ticket_assignments', function (Blueprint $table) {
                $table->dropColumn([
                    'status', 'decline_reason', 'submitted_at', 'reviewed_at', 'reviewed_by', 'review_notes', 'worklog'
                ]);
            });
        }

        if (Schema::hasTable('tickets')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->dropColumn([
                    'sla_excused', 'sla_excused_reason', 'sla_excused_by', 'sla_excused_at', 'sla_excused_evidence_path', 'pengerjaan_dimulai_at'
                ]);
            });
        }
    }
};
