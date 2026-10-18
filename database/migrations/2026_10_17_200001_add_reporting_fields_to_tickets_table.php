<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('opd_id')->nullable()->after('skpd')->constrained('master_opd')->nullOnDelete();
            $table->timestamp('selesai_at')->nullable()->after('sla_breach');
            $table->string('jenis_penyelesaian', 50)->nullable()->after('selesai_at');
            $table->integer('total_jeda_menit')->default(0)->after('jenis_penyelesaian');

            // Index Performa untuk Agregat dan Filter Cepat Laporan Kinerja
            $table->index('selesai_at', 'tickets_selesai_at_index');
            $table->index('jenis_penyelesaian', 'tickets_jenis_penyelesaian_index');
            $table->index(['status', 'selesai_at'], 'tickets_status_selesai_at_index');
            $table->index('opd_id', 'tickets_opd_id_index');
        });

        // 1. Backfill mapping tickets.skpd ke master_opd.id
        $opdRecords = DB::table('master_opd')->get();
        foreach ($opdRecords as $opd) {
            // Cocokkan persis nama_opd atau bagian nama
            DB::table('tickets')
                ->whereNull('opd_id')
                ->where(function ($q) use ($opd) {
                    $q->where('skpd', $opd->nama_opd)
                      ->orWhere('skpd', 'ILIKE', "%{$opd->singkatan}%")
                      ->orWhere('skpd', 'ILIKE', "%{$opd->nama_opd}%");
                })
                ->update(['opd_id' => $opd->id]);
        }

        // 2. Backfill nilai selesai_at dan jenis_penyelesaian pada tiket selesai yang sudah ada
        DB::table('tickets')
            ->whereIn('status', ['resolved', 'closed', 'force_closed'])
            ->whereNull('selesai_at')
            ->update([
                'selesai_at' => DB::raw('updated_at'),
                'jenis_penyelesaian' => DB::raw("
                    CASE 
                        WHEN status = 'force_closed' THEN 'ditutup_paksa'
                        WHEN rating IS NOT NULL OR feedback IS NOT NULL THEN 'dikonfirmasi_pelapor'
                        ELSE 'manual_admin'
                    END
                "),
            ]);
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex('tickets_selesai_at_index');
            $table->dropIndex('tickets_jenis_penyelesaian_index');
            $table->dropIndex('tickets_status_selesai_at_index');
            $table->dropIndex('tickets_opd_id_index');

            $table->dropForeign(['opd_id']);
            $table->dropColumn(['opd_id', 'selesai_at', 'jenis_penyelesaian', 'total_jeda_menit']);
        });
    }
};
