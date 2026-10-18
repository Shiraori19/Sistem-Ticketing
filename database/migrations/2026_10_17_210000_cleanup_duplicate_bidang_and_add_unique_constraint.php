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
        // 1. Bersihkan bidang duplikat yang tidak memiliki relasi
        $duplicates = DB::table('bidang as b1')
            ->join('bidang as b2', function ($join) {
                $join->on('b1.kode', '=', 'b2.kode')
                    ->whereColumn('b1.id', '>', 'b2.id');
            })
            ->select('b1.id')
            ->pluck('id');

        if ($duplicates->isNotEmpty()) {
            // Pastikan relasi jika ada dipindahkan ke ID primer terkecil
            foreach ($duplicates as $dupId) {
                $dupRow = DB::table('bidang')->where('id', $dupId)->first();
                if ($dupRow) {
                    $primaryRow = DB::table('bidang')->where('kode', $dupRow->kode)->where('id', '<', $dupId)->orderBy('id')->first();
                    if ($primaryRow) {
                        DB::table('users')->where('bidang_id', $dupId)->update(['bidang_id' => $primaryRow->id]);
                        DB::table('categories')->where('bidang_id', $dupId)->update(['bidang_id' => $primaryRow->id]);
                        DB::table('tickets')->where('bidang_id', $dupId)->update(['bidang_id' => $primaryRow->id]);
                    }
                    DB::table('bidang')->where('id', $dupId)->delete();
                }
            }
        }

        // 2. Tambahkan unique constraint pada kode bidang agar tidak ada duplikasi lagi
        Schema::table('bidang', function (Blueprint $table) {
            $table->unique('kode', 'bidang_kode_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bidang', function (Blueprint $table) {
            $table->dropUnique('bidang_kode_unique');
        });
    }
};
