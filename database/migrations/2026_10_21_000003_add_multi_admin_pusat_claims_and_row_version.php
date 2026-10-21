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
            if (!Schema::hasColumn('tickets', 'claimed_by')) {
                $table->foreignId('claimed_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('tickets', 'claimed_at')) {
                $table->timestamp('claimed_at')->nullable()->after('claimed_by');
            }
            if (!Schema::hasColumn('tickets', 'row_version')) {
                $table->unsignedInteger('row_version')->default(1)->after('claimed_at');
            }

            $table->index(['claimed_by', 'claimed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['claimed_by', 'claimed_at']);
            $table->dropConstrainedForeignId('claimed_by');
            $table->dropColumn(['claimed_at', 'row_version']);
        });
    }
};
