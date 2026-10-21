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
        // 1. Tambah metadata keamanan, hash SHA-256, klasifikasi, dan penanda vault pada ticket_files
        Schema::table('ticket_files', function (Blueprint $table) {
            if (!Schema::hasColumn('ticket_files', 'sha256')) {
                $table->string('sha256', 64)->nullable()->after('size');
            }
            if (!Schema::hasColumn('ticket_files', 'original_name')) {
                $table->string('original_name', 255)->nullable()->after('sha256');
            }
            if (!Schema::hasColumn('ticket_files', 'classification')) {
                $table->string('classification', 20)->default('umum')->after('original_name'); // 'umum', 'terbatas', 'rahasia'
            }
            if (!Schema::hasColumn('ticket_files', 'classification_reason')) {
                $table->text('classification_reason')->nullable()->after('classification');
            }
            if (!Schema::hasColumn('ticket_files', 'classified_by')) {
                $table->foreignId('classified_by')->nullable()->after('classification_reason')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('ticket_files', 'is_encrypted')) {
                $table->boolean('is_encrypted')->default(false)->after('classified_by');
            }
            if (!Schema::hasColumn('ticket_files', 'vault_path')) {
                $table->string('vault_path', 255)->nullable()->after('is_encrypted');
            }

            $table->index('classification');
            $table->index('sha256');
        });

        // 2. Buat tabel permohonan akses berkas rahasia oleh Admin Pusat
        if (!Schema::hasTable('ticket_file_access_requests')) {
            Schema::create('ticket_file_access_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ticket_file_id')->constrained('ticket_files')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->text('reason');
                $table->string('status', 20)->default('pending'); // 'pending', 'approved', 'rejected'
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();

                $table->index(['ticket_file_id', 'status']);
                $table->index(['user_id', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_file_access_requests');

        Schema::table('ticket_files', function (Blueprint $table) {
            $table->dropIndex(['classification']);
            $table->dropIndex(['sha256']);
            $table->dropConstrainedForeignId('classified_by');
            $table->dropColumn([
                'sha256',
                'original_name',
                'classification',
                'classification_reason',
                'is_encrypted',
                'vault_path',
            ]);
        });
    }
};
