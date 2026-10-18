<?php

namespace Database\Seeders;

use App\Models\Bidang;
use App\Models\Category;
use Illuminate\Database\Seeder;

class StandardSlaSeeder extends Seeder
{
    /**
     * Terapkan 12 Standar Kategori & SLA Resmi SPBE, BSSN, PermenPANRB, & ITIL v4
     */
    public function run(): void
    {
        $bidangTeknis = Bidang::where('kode', 'PEMDI')->first() ?? Bidang::create([
            'nama_bidang' => 'Pemerintahan Digital',
            'kode' => 'PEMDI',
            'icon' => 'code-bracket',
        ]);

        $bidangJaringan = Bidang::where('kode', 'ITIK')->first() ?? Bidang::create([
            'nama_bidang' => 'Infrastruktur TIK',
            'kode' => 'ITIK',
            'icon' => 'server-stack',
        ]);

        $bidangPersandian = Bidang::where('kode', 'PERSANDIAN')->first() ?? Bidang::create([
            'nama_bidang' => 'Persandian',
            'kode' => 'PERSANDIAN',
            'icon' => 'shield-check',
        ]);

        $standardCategories = [
            // =========================================================================
            // 1. BIDANG PEMERINTAHAN DIGITAL (PEMDI)
            // =========================================================================
            [
                'nama_kategori' => 'Perbaikan Aplikasi SIMPEG',
                'bidang_id' => $bidangTeknis->id,
                'sla_respon_jam' => 72.00, // 3x24 Jam
                'sla_resolusi_jam' => 336,  // 14 Hari Kalender
            ],
            [
                'nama_kategori' => 'Gangguan & Error Aplikasi',
                'bidang_id' => $bidangTeknis->id,
                'sla_respon_jam' => 72.00,
                'sla_resolusi_jam' => 336,
            ],
            [
                'nama_kategori' => 'Integrasi Data & API',
                'bidang_id' => $bidangTeknis->id,
                'sla_respon_jam' => 72.00,
                'sla_resolusi_jam' => 336,
            ],
            [
                'nama_kategori' => 'Pengembangan Aplikasi Baru',
                'bidang_id' => $bidangTeknis->id,
                'sla_respon_jam' => 72.00,
                'sla_resolusi_jam' => 336,
            ],

            // =========================================================================
            // 2. BIDANG INFRASTRUKTUR & TIK (ITIK)
            // =========================================================================
            [
                'nama_kategori' => 'Gangguan Jaringan & Internet',
                'bidang_id' => $bidangJaringan->id,
                'sla_respon_jam' => 72.00,
                'sla_resolusi_jam' => 336,
            ],
            [
                'nama_kategori' => 'Putus Jaringan Kabel FO',
                'bidang_id' => $bidangJaringan->id,
                'sla_respon_jam' => 72.00,
                'sla_resolusi_jam' => 336,
            ],
            [
                'nama_kategori' => 'Penyediaan Server & VPS',
                'bidang_id' => $bidangJaringan->id,
                'sla_respon_jam' => 72.00,
                'sla_resolusi_jam' => 336,
            ],
            [
                'nama_kategori' => 'Pemasangan Jaringan Baru',
                'bidang_id' => $bidangJaringan->id,
                'sla_respon_jam' => 72.00,
                'sla_resolusi_jam' => 336,
            ],

            // =========================================================================
            // 3. BIDANG PERSANDIAN & KEAMANAN INFORMASI (PERSANDIAN)
            // =========================================================================
            [
                'nama_kategori' => 'Insiden Keamanan Siber',
                'bidang_id' => $bidangPersandian->id,
                'sla_respon_jam' => 72.00,
                'sla_resolusi_jam' => 336,
            ],
            [
                'nama_kategori' => 'Penerbitan TTE BSrE',
                'bidang_id' => $bidangPersandian->id,
                'sla_respon_jam' => 72.00,
                'sla_resolusi_jam' => 336,
            ],
            [
                'nama_kategori' => 'Penerbitan SSL & Domain',
                'bidang_id' => $bidangPersandian->id,
                'sla_respon_jam' => 72.00,
                'sla_resolusi_jam' => 336,
            ],
            [
                'nama_kategori' => 'Uji Kerentanan Siber',
                'bidang_id' => $bidangPersandian->id,
                'sla_respon_jam' => 72.00,
                'sla_resolusi_jam' => 336,
            ],

            // =========================================================================
            // 4. KATEGORI KONSULTASI / UMUM (Untuk Pelapor Awam)
            // =========================================================================
            [
                'nama_kategori' => 'Lainnya / Belum Tahu Kategori',
                'bidang_id' => $bidangTeknis->id,
                'sla_respon_jam' => 72.00,
                'sla_resolusi_jam' => 336,
            ],
        ];

        foreach ($standardCategories as $catData) {
            Category::updateOrCreate(
                ['nama_kategori' => $catData['nama_kategori']],
                $catData
            );
        }
    }
}
