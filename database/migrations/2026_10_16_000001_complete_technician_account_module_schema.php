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
        // 1. Perbarui tabel technician_profiles (tambah cuti_until)
        if (Schema::hasTable('technician_profiles')) {
            Schema::table('technician_profiles', function (Blueprint $table) {
                if (!Schema::hasColumn('technician_profiles', 'cuti_until')) {
                    $table->timestamp('cuti_until')->nullable()->after('availability');
                }
            });
        }

        // 2. Perbarui tabel ticket_assignments
        if (Schema::hasTable('ticket_assignments')) {
            // Bersihkan atau isi fallback user_id jika ada record lama tanpa user_id sebelum diset NOT NULL
            $defaultTeknisi = DB::table('users')->where('role_id', function ($q) {
                $q->select('id')->from('roles')->where('nama_role', 'Teknisi')->limit(1);
            })->first();

            if ($defaultTeknisi) {
                DB::table('ticket_assignments')
                    ->whereNull('user_id')
                    ->update(['user_id' => $defaultTeknisi->id]);
            }

            Schema::table('ticket_assignments', function (Blueprint $table) {
                if (!Schema::hasColumn('ticket_assignments', 'respond_deadline')) {
                    $table->timestamp('respond_deadline')->nullable()->after('assigned_at');
                }
                if (!Schema::hasColumn('ticket_assignments', 'returned_at')) {
                    $table->timestamp('returned_at')->nullable()->after('approved_at');
                }
                if (!Schema::hasColumn('ticket_assignments', 'row_version')) {
                    $table->integer('row_version')->default(1)->after('alasan_unassign');
                }
            });

            // Set NOT NULL pada user_id jika driver postgres
            if (DB::getDriverName() === 'pgsql') {
                DB::statement("ALTER TABLE ticket_assignments ALTER COLUMN user_id SET NOT NULL;");
            }
        }

        // 3. Buat tabel ticket_events (Append-Only dengan Trigger Pencegah Update/Delete)
        if (!Schema::hasTable('ticket_events')) {
            Schema::create('ticket_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ticket_id')->constrained('tickets')->onDelete('cascade');
                $table->foreignId('assignment_id')->nullable()->constrained('ticket_assignments')->nullOnDelete();
                $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('type', 60); // assigned, accepted, declined, released, progress_added, blocker_reported, blocker_resolved, submitted, approved, returned, contact_viewed
                $table->jsonb('payload')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['ticket_id', 'created_at']);
                $table->index(['assignment_id']);
                $table->index(['actor_id', 'type']);
            });

            // PostgreSQL Trigger: Cabut hak UPDATE dan DELETE untuk menjamin append-only
            if (DB::getDriverName() === 'pgsql') {
                DB::statement("
                    CREATE OR REPLACE FUNCTION prevent_ticket_events_modification()
                    RETURNS TRIGGER AS $$
                    BEGIN
                        RAISE EXCEPTION 'ticket_events is append-only. UPDATE and DELETE operations are strictly prohibited.';
                    END;
                    $$ LANGUAGE plpgsql;
                ");

                DB::statement("DROP TRIGGER IF EXISTS trg_prevent_ticket_events_modification ON ticket_events;");

                DB::statement("
                    CREATE TRIGGER trg_prevent_ticket_events_modification
                    BEFORE UPDATE OR DELETE ON ticket_events
                    FOR EACH ROW
                    EXECUTE FUNCTION prevent_ticket_events_modification();
                ");
            }
        }

        // 4. Buat tabel ticket_blockers (Pelaporan hambatan teknisi, SLA TETAP JALAN)
        if (!Schema::hasTable('ticket_blockers')) {
            Schema::create('ticket_blockers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ticket_id')->constrained('tickets')->onDelete('cascade');
                $table->foreignId('assignment_id')->nullable()->constrained('ticket_assignments')->nullOnDelete();
                $table->string('jenis', 50); // 'isp', 'vendor', 'opd_lain', 'suku_cadang'
                $table->text('bukti');
                $table->text('keterangan');
                $table->string('status', 20)->default('open'); // 'open', 'resolved'
                $table->timestamp('next_reminder_at')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();

                $table->index(['ticket_id', 'status']);
                $table->index(['status', 'next_reminder_at']);
            });
        }

        // 5. Buat tabel ticket_files (Penyimpanan lampiran privat append-only)
        if (!Schema::hasTable('ticket_files')) {
            Schema::create('ticket_files', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ticket_id')->constrained('tickets')->onDelete('cascade');
                $table->foreignId('event_id')->nullable()->constrained('ticket_events')->nullOnDelete();
                $table->foreignId('uploader_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('path', 255);
                $table->string('mime', 100);
                $table->unsignedBigInteger('size');
                $table->string('kategori', 50); // 'progres_lapangan', 'bukti_hambatan', 'bukti_resolusi', dll.
                $table->timestamps();

                $table->index(['ticket_id', 'kategori']);
                $table->index(['uploader_id']);
            });
        }

        // 6. Buat tabel technician_stats_daily (Agregasi analitik KPI teknisi)
        if (!Schema::hasTable('technician_stats_daily')) {
            Schema::create('technician_stats_daily', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->date('date');
                $table->integer('tasks_completed')->default(0);
                $table->integer('median_resolution_minutes')->nullable();
                $table->integer('sla_on_time_raw')->default(0);
                $table->integer('sla_on_time_excused')->default(0);
                $table->integer('reopen_count')->default(0);
                $table->decimal('avg_rating', 3, 2)->nullable();
                $table->jsonb('category_breakdown')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'date']);
                $table->index('date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technician_stats_daily');
        Schema::dropIfExists('ticket_files');
        Schema::dropIfExists('ticket_blockers');

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("DROP TRIGGER IF EXISTS trg_prevent_ticket_events_modification ON ticket_events;");
            DB::statement("DROP FUNCTION IF EXISTS prevent_ticket_events_modification();");
        }
        Schema::dropIfExists('ticket_events');

        if (Schema::hasTable('ticket_assignments')) {
            Schema::table('ticket_assignments', function (Blueprint $table) {
                $table->dropColumn(['respond_deadline', 'returned_at', 'row_version']);
            });
            if (DB::getDriverName() === 'pgsql') {
                DB::statement("ALTER TABLE ticket_assignments ALTER COLUMN user_id DROP NOT NULL;");
            }
        }

        if (Schema::hasTable('technician_profiles')) {
            Schema::table('technician_profiles', function (Blueprint $table) {
                $table->dropColumn('cuti_until');
            });
        }
    }
};
