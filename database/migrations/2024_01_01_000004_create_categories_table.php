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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kategori', 150);
            $table->foreignId('bidang_id')->constrained('bidang')->onDelete('cascade');
            $table->decimal('sla_respon_jam', 5, 2); // misal 0.25 = 15 menit
            $table->integer('sla_resolusi_jam');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
