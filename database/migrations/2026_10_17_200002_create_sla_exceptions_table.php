<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sla_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->text('alasan');
            $table->foreignId('diputuskan_oleh')->constrained('users')->cascadeOnDelete();
            $table->string('lampiran_bukti')->nullable();
            $table->timestamps();

            $table->index('ticket_id', 'sla_exceptions_ticket_id_index');
            $table->index('diputuskan_oleh', 'sla_exceptions_diputuskan_oleh_index');
        });

        // Migrasikan tiket yang sebelumnya telah memiliki sla_excused
        $excusedTickets = DB::table('tickets')
            ->where('sla_excused', true)
            ->whereNotNull('sla_excused_by')
            ->get();

        foreach ($excusedTickets as $t) {
            DB::table('sla_exceptions')->insert([
                'ticket_id' => $t->id,
                'alasan' => $t->sla_excused_reason ?: 'Pengecualian SLA disetujui Admin Pusat',
                'diputuskan_oleh' => $t->sla_excused_by,
                'lampiran_bukti' => $t->sla_excused_evidence_path,
                'created_at' => $t->sla_excused_at ?: now(),
                'updated_at' => $t->sla_excused_at ?: now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sla_exceptions');
    }
};
