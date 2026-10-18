<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Standarisasi SLA Respon: 3x24 Jam (72 Jam)
     * Standarisasi SLA Selesai / Closed (Resolusi): 14 Hari Kalender (336 Jam)
     */
    public function up(): void
    {
        // 1. Ubah default skema tabel categories dan tickets
        Schema::table('categories', function (Blueprint $table) {
            $table->decimal('sla_respon_jam', 5, 2)->nullable()->default(72.00)->change();
            $table->integer('sla_resolusi_jam')->nullable()->default(336)->change();
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->decimal('sla_respon_jam', 5, 2)->nullable()->default(72.00)->change();
            $table->integer('sla_resolusi_jam')->nullable()->default(336)->change();
        });

        // 2. Perbarui seluruh data kategori ke standar SLA 72 Jam Respon & 336 Jam Selesai
        DB::table('categories')->update([
            'sla_respon_jam' => 72.00,
            'sla_resolusi_jam' => 336,
        ]);

        // 3. Perbarui seluruh tiket dan hitung ulang batas deadline secara akurat
        DB::table('tickets')->update([
            'sla_respon_jam' => 72.00,
            'sla_resolusi_jam' => 336,
        ]);

        if (DB::getDriverName() === 'pgsql') {
            // Deadline SLA Respon: 72 jam (3x24 jam) sejak tiket dibuat
            DB::statement("UPDATE tickets 
                SET sla_respon_deadline_at = created_at + interval '72 hours' 
                WHERE created_at IS NOT NULL;");

            // Deadline SLA Resolusi: 14 hari (336 jam) sejak tiket mulai diverifikasi/dikerjakan
            DB::statement("UPDATE tickets 
                SET sla_deadline_at = sla_dimulai_at + interval '14 days' 
                WHERE sla_dimulai_at IS NOT NULL;");

            // Rekonsiliasi SLA Breach: tiket aktif yang belum melewati batas baru 14 hari dinormalkan kembali (tidak breach)
            DB::statement("UPDATE tickets 
                SET sla_breach = false 
                WHERE status IN ('verified', 'in_progress', 'reopened') 
                  AND sla_deadline_at IS NOT NULL 
                  AND sla_deadline_at > NOW();");

            // Rekonsiliasi SLA Respon Breach: tiket pending yang belum melewati 72 jam dinormalkan kembali (tidak breach)
            DB::statement("UPDATE tickets 
                SET sla_respon_breach = false 
                WHERE status IN ('pending_verification', 'pending_info') 
                  AND sla_respon_deadline_at IS NOT NULL 
                  AND sla_respon_deadline_at > NOW();");
        } else {
            // Driver SQLite / Non-PostgreSQL fallback
            $now = Carbon::now();
            $tickets = \App\Models\Ticket::all();
            foreach ($tickets as $ticket) {
                if ($ticket->created_at) {
                    $ticket->sla_respon_deadline_at = Carbon::parse($ticket->created_at)->addHours(72);
                }
                if ($ticket->sla_dimulai_at) {
                    $ticket->sla_deadline_at = Carbon::parse($ticket->sla_dimulai_at)->addDays(14);
                    if (in_array($ticket->status, ['verified', 'in_progress', 'reopened'])) {
                        $ticket->sla_breach = $now->gt($ticket->sla_deadline_at);
                    }
                }
                if (in_array($ticket->status, ['pending_verification', 'pending_info']) && $ticket->sla_respon_deadline_at) {
                    $ticket->sla_respon_breach = $now->gt($ticket->sla_respon_deadline_at);
                }
                $ticket->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->decimal('sla_respon_jam', 5, 2)->nullable()->default(72.00)->change();
            $table->integer('sla_resolusi_jam')->nullable()->default(168)->change();
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->decimal('sla_respon_jam', 5, 2)->nullable()->default(72.00)->change();
            $table->integer('sla_resolusi_jam')->nullable()->default(168)->change();
        });

        DB::table('categories')->update([
            'sla_resolusi_jam' => 168,
        ]);

        DB::table('tickets')->update([
            'sla_resolusi_jam' => 168,
        ]);
    }
};
