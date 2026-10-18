<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_opd', function (Blueprint $table) {
            $table->id();
            $table->string('nama_opd', 200)->unique();
            $table->string('singkatan', 50)->nullable();
            $table->jsonb('alias')->nullable(); // Alternatif penulisan teks lama
            $table->integer('urutan')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed 20 OPD resmi Pemerintah Kota Cirebon
        $opdList = [
            ['nama_opd' => 'Sekretariat Daerah Kota Cirebon', 'singkatan' => 'SETDA', 'urutan' => 1],
            ['nama_opd' => 'Sekretariat DPRD Kota Cirebon', 'singkatan' => 'SETWAN', 'urutan' => 2],
            ['nama_opd' => 'Inspektorat Daerah Kota Cirebon', 'singkatan' => 'INSPEKTORAT', 'urutan' => 3],
            ['nama_opd' => 'Dinas Komunikasi, Informatika dan Statistik (DKIS)', 'singkatan' => 'DKIS', 'urutan' => 4],
            ['nama_opd' => 'Badan Kepegawaian dan Pengembangan SDM (BKPSDM)', 'singkatan' => 'BKPSDM', 'urutan' => 5],
            ['nama_opd' => 'Badan Pengelola Keuangan dan Pendapatan Daerah (BPKPD)', 'singkatan' => 'BPKPD', 'urutan' => 6],
            ['nama_opd' => 'Badan Perencanaan Pembangunan, Penelitian dan Pengembangan Daerah (Bappelitbangda)', 'singkatan' => 'BAPPELITBANGDA', 'urutan' => 7],
            ['nama_opd' => 'Dinas Pendidikan', 'singkatan' => 'DISDIK', 'urutan' => 8],
            ['nama_opd' => 'Dinas Kesehatan', 'singkatan' => 'DINKES', 'urutan' => 9],
            ['nama_opd' => 'Dinas Pekerjaan Umum dan Tata Ruang (DPUTR)', 'singkatan' => 'DPUTR', 'urutan' => 10],
            ['nama_opd' => 'Dinas Perumahan Rakyat dan Kawasan Permukiman (DPRKP)', 'singkatan' => 'DPRKP', 'urutan' => 11],
            ['nama_opd' => 'Satuan Polisi Pamong Praja (Satpol PP)', 'singkatan' => 'SATPOL PP', 'urutan' => 12],
            ['nama_opd' => 'Dinas Sosial', 'singkatan' => 'DINSOS', 'urutan' => 13],
            ['nama_opd' => 'Dinas Tenaga Kerja', 'singkatan' => 'DISNAKER', 'urutan' => 14],
            ['nama_opd' => 'Dinas Kependudukan dan Pencatatan Sipil (Disdukcapil)', 'singkatan' => 'DISDUKCAPIL', 'urutan' => 15],
            ['nama_opd' => 'Dinas Perhubungan', 'singkatan' => 'DISHUB', 'urutan' => 16],
            ['nama_opd' => 'Dinas Koperasi, Usaha Kecil, Menengah, Perdagangan dan Perindustrian (DKUKMPP)', 'singkatan' => 'DKUKMPP', 'urutan' => 17],
            ['nama_opd' => 'Dinas Pemuda dan Olahraga (Dispora)', 'singkatan' => 'DISPORA', 'urutan' => 18],
            ['nama_opd' => 'Badan Penanggulangan Bencana Daerah (BPBD)', 'singkatan' => 'BPBD', 'urutan' => 19],
            ['nama_opd' => 'RSUD Gunung Jati Kota Cirebon', 'singkatan' => 'RSUD GUNUNG JATI', 'urutan' => 20],
        ];

        $now = now();
        foreach ($opdList as $item) {
            DB::table('master_opd')->insert([
                'nama_opd' => $item['nama_opd'],
                'singkatan' => $item['singkatan'],
                'alias' => json_encode([$item['singkatan'], strtolower($item['singkatan'])]),
                'urutan' => $item['urutan'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('master_opd');
    }
};
