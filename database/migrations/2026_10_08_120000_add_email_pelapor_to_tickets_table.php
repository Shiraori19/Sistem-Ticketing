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
        Schema::table('tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('tickets', 'email_pelapor')) {
                $table->string('email_pelapor', 150)->nullable()->after('no_whatsapp')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'email_pelapor')) {
                $table->dropIndex(['email_pelapor']);
                $table->dropColumn('email_pelapor');
            }
        });
    }
};
