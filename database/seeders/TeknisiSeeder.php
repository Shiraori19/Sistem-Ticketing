<?php

namespace Database\Seeders;

use App\Models\Bidang;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TeknisiSeeder extends Seeder
{
    public function run(): void
    {
        $roleTeknisi = Role::firstOrCreate([
            'nama_role' => 'Teknisi',
        ]);

        $bidangJaringan = Bidang::where('kode', 'ITIK')->first() ?: Bidang::first();
        $bidangTeknis = Bidang::where('kode', 'PEMDI')->first() ?: Bidang::first();
        $bidangPersandian = Bidang::where('kode', 'PERSANDIAN')->first() ?: Bidang::first();

        $defaultPassword = Hash::make(env('SEEDER_ADMIN_PASSWORD', 'Dkis#Cirebon2026!'));

        // =========================================================================
        // 1. BIDANG INFRASTRUKTUR TIK (ITIK) - 3 TEKNISI
        // =========================================================================
        $fazra = User::updateOrCreate(
            ['username' => 'teknisi.fazra@cirebonkota.go.id'],
            [
                'nama' => 'Fazra Fatimah',
                'password' => $defaultPassword,
                'role_id' => $roleTeknisi->id,
                'bidang_id' => $bidangJaringan?->id,
                'avatar' => 'FF',
                'is_active' => true,
            ]
        );
        \App\Models\TechnicianProfile::updateOrCreate(
            ['user_id' => $fazra->id],
            [
                'availability' => 'tersedia',
                'max_load' => 6,
                'skills' => ['Jaringan & Internet', 'Infrastruktur TIK', 'Fiber Optik', 'Server & Switch'],
            ]
        );

        $bayu = User::updateOrCreate(
            ['username' => 'teknisi.bayu@cirebonkota.go.id'],
            [
                'nama' => 'Bayu Pratama',
                'password' => $defaultPassword,
                'role_id' => $roleTeknisi->id,
                'bidang_id' => $bidangJaringan?->id,
                'avatar' => 'BP',
                'is_active' => true,
            ]
        );
        \App\Models\TechnicianProfile::updateOrCreate(
            ['user_id' => $bayu->id],
            [
                'availability' => 'tersedia',
                'max_load' => 6,
                'skills' => ['Jaringan & Internet', 'Routing & Mikrotik', 'CCTV & Komunikasi Data'],
            ]
        );

        $citra = User::updateOrCreate(
            ['username' => 'teknisi.citra@cirebonkota.go.id'],
            [
                'nama' => 'Citra Kirana',
                'password' => $defaultPassword,
                'role_id' => $roleTeknisi->id,
                'bidang_id' => $bidangJaringan?->id,
                'avatar' => 'CK',
                'is_active' => true,
            ]
        );
        \App\Models\TechnicianProfile::updateOrCreate(
            ['user_id' => $citra->id],
            [
                'availability' => 'tersedia',
                'max_load' => 6,
                'skills' => ['Infrastruktur TIK', 'Server Linux & Virtualisasi', 'Access Point & WiFi OPD'],
            ]
        );

        // =========================================================================
        // 2. BIDANG PEMERINTAHAN DIGITAL (PEMDI) - 3 TEKNISI
        // =========================================================================
        $andi = User::updateOrCreate(
            ['username' => 'teknisi.andi@cirebonkota.go.id'],
            [
                'nama' => 'Andi Pratama',
                'password' => $defaultPassword,
                'role_id' => $roleTeknisi->id,
                'bidang_id' => $bidangTeknis?->id,
                'avatar' => 'AP',
                'is_active' => true,
            ]
        );
        \App\Models\TechnicianProfile::updateOrCreate(
            ['user_id' => $andi->id],
            [
                'availability' => 'tersedia',
                'max_load' => 6,
                'skills' => ['Aplikasi Pemerintahan', 'Database & API', 'Website OPD'],
            ]
        );

        $budi = User::updateOrCreate(
            ['username' => 'teknisi.budi@cirebonkota.go.id'],
            [
                'nama' => 'Budi Santoso',
                'password' => $defaultPassword,
                'role_id' => $roleTeknisi->id,
                'bidang_id' => $bidangTeknis?->id,
                'avatar' => 'BS',
                'is_active' => true,
            ]
        );
        \App\Models\TechnicianProfile::updateOrCreate(
            ['user_id' => $budi->id],
            [
                'availability' => 'tersedia',
                'max_load' => 6,
                'skills' => ['Aplikasi Pemerintahan', 'Integrasi Sistem Layanan Publik', 'Bug Fixing PHP & Laravel'],
            ]
        );

        $dewiTech = User::updateOrCreate(
            ['username' => 'teknisi.dewi@cirebonkota.go.id'],
            [
                'nama' => 'Dewi Anggraini',
                'password' => $defaultPassword,
                'role_id' => $roleTeknisi->id,
                'bidang_id' => $bidangTeknis?->id,
                'avatar' => 'DA',
                'is_active' => true,
            ]
        );
        \App\Models\TechnicianProfile::updateOrCreate(
            ['user_id' => $dewiTech->id],
            [
                'availability' => 'tersedia',
                'max_load' => 6,
                'skills' => ['Website OPD', 'Portal Berita Pemda', 'Database MySQL & PostgreSQL'],
            ]
        );

        // =========================================================================
        // 3. BIDANG PERSANDIAN & KEAMANAN SIBER (PERSANDIAN) - 3 TEKNISI
        // =========================================================================
        $rian = User::updateOrCreate(
            ['username' => 'teknisi.persandian@cirebonkota.go.id'],
            [
                'nama' => 'Rian Pratama',
                'password' => $defaultPassword,
                'role_id' => $roleTeknisi->id,
                'bidang_id' => $bidangPersandian?->id,
                'avatar' => 'RP',
                'is_active' => true,
            ]
        );
        \App\Models\TechnicianProfile::updateOrCreate(
            ['user_id' => $rian->id],
            [
                'availability' => 'tersedia',
                'max_load' => 6,
                'skills' => ['Kriptografi & Sandi', 'Sertifikat Elektronik (TTE)', 'Keamanan Jaringan', 'Penanganan Insiden Siber'],
            ]
        );

        $dimas = User::updateOrCreate(
            ['username' => 'teknisi.dimas@cirebonkota.go.id'],
            [
                'nama' => 'Dimas Setiawan',
                'password' => $defaultPassword,
                'role_id' => $roleTeknisi->id,
                'bidang_id' => $bidangPersandian?->id,
                'avatar' => 'DS',
                'is_active' => true,
            ]
        );
        \App\Models\TechnicianProfile::updateOrCreate(
            ['user_id' => $dimas->id],
            [
                'availability' => 'tersedia',
                'max_load' => 6,
                'skills' => ['Sertifikat Elektronik (TTE)', 'Penerbitan BSrE', 'Audit Keamanan Informasi'],
            ]
        );

        $eka = User::updateOrCreate(
            ['username' => 'teknisi.eka@cirebonkota.go.id'],
            [
                'nama' => 'Eka Saputra',
                'password' => $defaultPassword,
                'role_id' => $roleTeknisi->id,
                'bidang_id' => $bidangPersandian?->id,
                'avatar' => 'ES',
                'is_active' => true,
            ]
        );
        \App\Models\TechnicianProfile::updateOrCreate(
            ['user_id' => $eka->id],
            [
                'availability' => 'tersedia',
                'max_load' => 6,
                'skills' => ['Penanganan Insiden Siber (CSIRT)', 'Analisis Kerentanan Web', 'Pengamanan Server & Log Audit'],
            ]
        );
    }
}
