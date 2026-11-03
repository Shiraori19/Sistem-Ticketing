<?php

namespace Database\Seeders;

use App\Models\Bidang;
use App\Models\Category;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketAssignment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CleanOfficialDataSeeder extends Seeder
{
    public function run(): void
    {
        Model::unguard();

        $rolePusat = Role::firstOrCreate(['nama_role' => 'Admin Pusat']);
        $roleBidang = Role::firstOrCreate(['nama_role' => 'Admin Bidang']);
        $roleTeknisi = Role::firstOrCreate(['nama_role' => 'Teknisi']);

        $bidangItik = Bidang::firstOrCreate(['kode' => 'ITIK'], ['nama_bidang' => 'Infrastruktur TIK', 'is_persandian' => false]);
        $bidangSandi = Bidang::firstOrCreate(['kode' => 'PERSANDIAN'], ['nama_bidang' => 'Persandian & Keamanan Informasi', 'is_persandian' => true]);
        $bidangApli = Bidang::firstOrCreate(['kode' => 'APLIKASI'], ['nama_bidang' => 'Layanan Aplikasi & Informatika', 'is_persandian' => false]);

        $officialUsers = [
            // Admin Pusat (2 akun)
            [
                'nama' => 'Admin Pusat DKIS',
                'username' => 'admin.pusat@cirebonkota.go.id',
                'password' => Hash::make('password'),
                'role_id' => $rolePusat->id,
                'bidang_id' => null,
                'is_active' => true,
            ],
            [
                'nama' => 'Admin Pusat 2 DKIS',
                'username' => 'admin.pusat2@cirebonkota.go.id',
                'password' => Hash::make('password'),
                'role_id' => $rolePusat->id,
                'bidang_id' => null,
                'is_active' => true,
            ],
            // Admin Bidang (3 akun)
            [
                'nama' => 'Admin Bidang Jaringan & ITIK',
                'username' => 'jaringan@cirebonkota.go.id',
                'password' => Hash::make('password'),
                'role_id' => $roleBidang->id,
                'bidang_id' => $bidangItik->id,
                'is_active' => true,
            ],
            [
                'nama' => 'Admin Bidang Persandian & CSIRT',
                'username' => 'persandian@cirebonkota.go.id',
                'password' => Hash::make('password'),
                'role_id' => $roleBidang->id,
                'bidang_id' => $bidangSandi->id,
                'is_active' => true,
            ],
            [
                'nama' => 'Admin Bidang Aplikasi & Informatika',
                'username' => 'aplikasi@cirebonkota.go.id',
                'password' => Hash::make('password'),
                'role_id' => $roleBidang->id,
                'bidang_id' => $bidangApli->id,
                'is_active' => true,
            ],
            // Teknisi Lapangan (10 akun)
            [
                'nama' => 'Budi Santoso',
                'username' => 'teknisi@cirebonkota.go.id',
                'password' => Hash::make('password'),
                'role_id' => $roleTeknisi->id,
                'bidang_id' => $bidangItik->id,
                'is_active' => true,
            ],
            [
                'nama' => 'Andi Pratama',
                'username' => 'teknisi.andi@cirebonkota.go.id',
                'password' => Hash::make('password'),
                'role_id' => $roleTeknisi->id,
                'bidang_id' => $bidangItik->id,
                'is_active' => true,
            ],
            [
                'nama' => 'Bayu Saputra',
                'username' => 'teknisi.bayu@cirebonkota.go.id',
                'password' => Hash::make('password'),
                'role_id' => $roleTeknisi->id,
                'bidang_id' => $bidangItik->id,
                'is_active' => true,
            ],
            [
                'nama' => 'Citra Lestari',
                'username' => 'teknisi.citra@cirebonkota.go.id',
                'password' => Hash::make('password'),
                'role_id' => $roleTeknisi->id,
                'bidang_id' => $bidangApli->id,
                'is_active' => true,
            ],
            [
                'nama' => 'Dewi Anggraeni',
                'username' => 'teknisi.dewi@cirebonkota.go.id',
                'password' => Hash::make('password'),
                'role_id' => $roleTeknisi->id,
                'bidang_id' => $bidangApli->id,
                'is_active' => true,
            ],
            [
                'nama' => 'Dimas Setiawan',
                'username' => 'teknisi.dimas@cirebonkota.go.id',
                'password' => Hash::make('password'),
                'role_id' => $roleTeknisi->id,
                'bidang_id' => $bidangItik->id,
                'is_active' => true,
            ],
            [
                'nama' => 'Eka Kurniawan',
                'username' => 'teknisi.eka@cirebonkota.go.id',
                'password' => Hash::make('password'),
                'role_id' => $roleTeknisi->id,
                'bidang_id' => $bidangItik->id,
                'is_active' => true,
            ],
            [
                'nama' => 'Fazra Ramadhan',
                'username' => 'teknisi.fazra@cirebonkota.go.id',
                'password' => Hash::make('password'),
                'role_id' => $roleTeknisi->id,
                'bidang_id' => $bidangApli->id,
                'is_active' => true,
            ],
            [
                'nama' => 'Sandi Kusuma',
                'username' => 'teknisi.persandian@cirebonkota.go.id',
                'password' => Hash::make('password'),
                'role_id' => $roleTeknisi->id,
                'bidang_id' => $bidangSandi->id,
                'is_active' => true,
            ],
            [
                'nama' => 'Budi Santoso II',
                'username' => 'teknisi.budi@cirebonkota.go.id',
                'password' => Hash::make('password'),
                'role_id' => $roleTeknisi->id,
                'bidang_id' => $bidangItik->id,
                'is_active' => true,
            ],
        ];

        $keptUsernames = [];
        foreach ($officialUsers as $uData) {
            $user = User::where('username', $uData['username'])->first();
            if ($user) {
                $user->update([
                    'nama' => $uData['nama'],
                    'role_id' => $uData['role_id'],
                    'bidang_id' => $uData['bidang_id'],
                    'is_active' => true,
                ]);
            } else {
                User::create($uData);
            }
            $keptUsernames[] = $uData['username'];
        }

        // Matikan trigger postgres sementara untuk membersihkan tiket dummy
        try {
            DB::statement('ALTER TABLE ticket_events DISABLE TRIGGER ALL;');
        } catch (\Throwable $e) {}

        DB::table('ticket_assignments')->delete();
        DB::table('ticket_events')->delete();
        DB::table('tickets')->delete();

        try {
            DB::statement('ALTER TABLE ticket_events ENABLE TRIGGER ALL;');
        } catch (\Throwable $e) {}

        // Hapus akun selain 15 akun resmi
        User::whereNotIn('username', $keptUsernames)->delete();

        // Siapkan kategori
        $catJaringan = Category::firstOrCreate(['nama_kategori' => 'Jaringan & Internet'], ['bidang_id' => $bidangItik->id, 'sla_respon_jam' => 2, 'sla_resolusi_jam' => 24, 'is_active' => true]);
        $catSiber = Category::firstOrCreate(['nama_kategori' => 'Keamanan Siber & Insiden'], ['bidang_id' => $bidangSandi->id, 'sla_respon_jam' => 1, 'sla_resolusi_jam' => 12, 'is_active' => true]);
        $catAplikasi = Category::firstOrCreate(['nama_kategori' => 'Pengembangan Aplikasi OPD'], ['bidang_id' => $bidangApli->id, 'sla_respon_jam' => 4, 'sla_resolusi_jam' => 48, 'is_active' => true]);
        $catHardware = Category::firstOrCreate(['nama_kategori' => 'Perangkat Keras & Server'], ['bidang_id' => $bidangItik->id, 'sla_respon_jam' => 2, 'sla_resolusi_jam' => 24, 'is_active' => true]);

        $adminPusat = User::where('username', 'admin.pusat@cirebonkota.go.id')->first();
        $tekItik1 = User::where('username', 'teknisi@cirebonkota.go.id')->first();
        $tekItik2 = User::where('username', 'teknisi.andi@cirebonkota.go.id')->first();
        $tekSandi = User::where('username', 'teknisi.persandian@cirebonkota.go.id')->first();
        $tekApli = User::where('username', 'teknisi.citra@cirebonkota.go.id')->first();

        // Buat 10 Tiket Realistis Kota Cirebon:
        // 3 RED, 2 AMBER, 4 GREEN, 1 CLEAR
        $ticketDefs = [
            // 3 RED
            [
                'kode' => 'TIK-202611-0001',
                'nama' => 'Dr. H. Edi Siswoyo, M.Si.',
                'nip' => '197508122000031002',
                'wa' => '081222334455',
                'skpd' => 'Dinas Kesehatan Kota Cirebon',
                'judul' => 'Dugaan Ransomware & Ransom Note pada Server Database SIMPUS',
                'deskripsi' => 'Ditemukan file berekstensi .locked pada server database SIMPUS Puskesmas se-Kota Cirebon. Akses rekam medis terganggu total sejak pukul 06.00 WIB.',
                'tlp' => 'RED',
                'status' => 'in_progress',
                'category_id' => $catSiber->id,
                'bidang_id' => $bidangSandi->id,
                'hours_ago' => 3,
                'sla_hours' => 12,
                'teknisi_id' => $tekSandi?->id,
            ],
            [
                'kode' => 'TIK-202611-0002',
                'nama' => 'Ir. Agus Mulyadi, M.Si.',
                'nip' => '196805141994031005',
                'wa' => '081333445566',
                'skpd' => 'Badan Kepegawaian dan Pengembangan SDM',
                'judul' => 'Aktivitas Bruteforce Masif pada Portal Single Sign-On BKPSDM',
                'deskripsi' => 'Terdeteksi lebih dari 15.000 percobaan login ilegal per menit dari IP luar negeri yang menargetkan akun pejabat eselon II dan III Pemkot Cirebon.',
                'tlp' => 'RED',
                'status' => 'in_progress',
                'category_id' => $catSiber->id,
                'bidang_id' => $bidangSandi->id,
                'hours_ago' => 2,
                'sla_hours' => 12,
                'teknisi_id' => $tekSandi?->id,
            ],
            [
                'kode' => 'TIK-202611-0003',
                'nama' => 'Drs. Sutisna, M.Pd.',
                'nip' => '197103251998021003',
                'wa' => '081298765432',
                'skpd' => 'Dinas Pendidikan Kota Cirebon',
                'judul' => 'Web Defacement pada Subdomain Portal PPDB Online Kota Cirebon',
                'deskripsi' => 'Halaman utama portal PPDB menampilkan konten judi online dan deface script. Layanan pendaftaran siswa baru terpaksa dihentikan sementara.',
                'tlp' => 'RED',
                'status' => 'verified',
                'category_id' => $catSiber->id,
                'bidang_id' => $bidangSandi->id,
                'hours_ago' => 1,
                'sla_hours' => 12,
                'teknisi_id' => null,
            ],

            // 2 AMBER
            [
                'kode' => 'TIK-202611-0004',
                'nama' => 'Hj. Ratna Komala, S.Sos., M.Si.',
                'nip' => '197809182005012008',
                'wa' => '085211223344',
                'skpd' => 'Badan Pengelola Keuangan dan Pendapatan Daerah (BPKPD)',
                'judul' => 'Putusnya Kabel Fiber Optic Backbone Utama Menuju Balai Kota',
                'deskripsi' => 'Kabel FO di persimpangan Jl. Siliwangi terputus akibat perapihan pohon dinas terkait. Seluruh koneksi intranet BPKPD dan Bagian Umum terputus.',
                'tlp' => 'AMBER',
                'status' => 'in_progress',
                'category_id' => $catJaringan->id,
                'bidang_id' => $bidangItik->id,
                'hours_ago' => 4,
                'sla_hours' => 24,
                'teknisi_id' => $tekItik1?->id,
            ],
            [
                'kode' => 'TIK-202611-0005',
                'nama' => 'Rahmat Hidayat, S.STP.',
                'nip' => '198504102008011006',
                'wa' => '081744556677',
                'skpd' => 'Dinas Kependudukan dan Pencatatan Sipil',
                'judul' => 'Gangguan Replikasi Database Server KTP-el dengan Pusat Ditjen Dukcapil',
                'deskripsi' => 'Sinkronisasi data perekaman e-KTP dan cetak KIA mengalami time-out berulang. Antrean layanan adminduk terhambat di Mal Pelayanan Publik (MPP).',
                'tlp' => 'AMBER',
                'status' => 'in_progress',
                'category_id' => $catHardware->id,
                'bidang_id' => $bidangItik->id,
                'hours_ago' => 5,
                'sla_hours' => 24,
                'teknisi_id' => $tekItik2?->id,
            ],

            // 4 GREEN
            [
                'kode' => 'TIK-202611-0006',
                'nama' => 'Nurul Fadilah, S.Kom.',
                'nip' => '199201152019032014',
                'wa' => '087812345678',
                'skpd' => 'Dinas Komunikasi, Informatika dan Statistik',
                'judul' => 'Integrasi API Satu Data Cirebon dengan Modul Stunting Bappelitbangda',
                'deskripsi' => 'Permohonan integrasi webhook dan pembuatan token API endpoint Satu Data Kota Cirebon untuk modul pemantauan stunting tahun anggaran 2026.',
                'tlp' => 'GREEN',
                'status' => 'in_progress',
                'category_id' => $catAplikasi->id,
                'bidang_id' => $bidangApli->id,
                'hours_ago' => 8,
                'sla_hours' => 48,
                'teknisi_id' => $tekApli?->id,
            ],
            [
                'kode' => 'TIK-202611-0007',
                'nama' => 'Ahmad Fauzi, S.T.',
                'nip' => '198906202015021004',
                'wa' => '081987654321',
                'skpd' => 'Kecamatan Kesambi',
                'judul' => 'Pemasangan Access Point Wi-Fi Layanan Publik di Kantor Kelurahan Sunyaragi',
                'deskripsi' => 'Pengajuan instalasi access point tambahan untuk ruang tunggu pelayanan PATEN Kelurahan Sunyaragi agar warga dapat mengakses antrean online.',
                'tlp' => 'GREEN',
                'status' => 'verified',
                'category_id' => $catJaringan->id,
                'bidang_id' => $bidangItik->id,
                'hours_ago' => 6,
                'sla_hours' => 48,
                'teknisi_id' => null,
            ],
            [
                'kode' => 'TIK-202611-0008',
                'nama' => 'Endang Sri Wahyuni, S.AP.',
                'nip' => '198311052009022007',
                'wa' => '085399887766',
                'skpd' => 'Kecamatan Kejaksan',
                'judul' => 'Permohonan Akun Email Kedinasan @cirebonkota.go.id untuk Pejabat Baru',
                'deskripsi' => 'Permohonan penerbitan akun email resmi kedinasan dan konfigurasi tanda tangan elektronik (TTE BSrE) bagi Kepala Seksi Pemerintahan yang baru dilantik.',
                'tlp' => 'GREEN',
                'status' => 'pending_verification',
                'category_id' => $catAplikasi->id,
                'bidang_id' => null,
                'hours_ago' => 1,
                'sla_hours' => 72,
                'teknisi_id' => null,
            ],
            [
                'kode' => 'TIK-202611-0009',
                'nama' => 'Yudi Pratama, A.Md.',
                'nip' => '199503142022031008',
                'wa' => '082155667788',
                'skpd' => 'Dinas Perhubungan Kota Cirebon',
                'judul' => 'Perapihan Pengkabelan LAN dan Switch Hub Ruang ATCS Kejaksan',
                'deskripsi' => 'Penataan ulang rak server switch hub 24-port di ruang kontrol CCTV ATCS Dishub guna persiapan pantauan arus lalu lintas libur akhir tahun.',
                'tlp' => 'GREEN',
                'status' => 'verified',
                'category_id' => $catHardware->id,
                'bidang_id' => $bidangItik->id,
                'hours_ago' => 10,
                'sla_hours' => 48,
                'teknisi_id' => null,
            ],

            // 1 CLEAR
            [
                'kode' => 'TIK-202611-0010',
                'nama' => 'Hendra Setiawan, S.IP.',
                'nip' => '198707222010011009',
                'wa' => '081234567890',
                'skpd' => 'Bagian Protokol dan Komunikasi Pimpinan Setda',
                'judul' => 'Dukungan Teknis Sound System & Live Streaming Rapat Paripurna DPRD',
                'deskripsi' => 'Permohonan bantuan teknis audio visual dan live streaming YouTube Pemkot Cirebon untuk liputan Rapat Paripurna DPRD Kota Cirebon.',
                'tlp' => 'CLEAR',
                'status' => 'resolved',
                'category_id' => $catHardware->id,
                'bidang_id' => $bidangItik->id,
                'hours_ago' => 24,
                'sla_hours' => 48,
                'teknisi_id' => $tekItik1?->id,
            ],
        ];

        foreach ($ticketDefs as $tDef) {
            $createdAt = Carbon::now()->subHours($tDef['hours_ago']);
            $deadline = $createdAt->copy()->addHours($tDef['sla_hours']);

            $ticket = Ticket::create([
                'kode_tiket' => $tDef['kode'],
                'nama_pelapor' => $tDef['nama'],
                'nip_pelapor' => $tDef['nip'],
                'no_whatsapp' => $tDef['wa'],
                'skpd' => $tDef['skpd'],
                'judul' => $tDef['judul'],
                'deskripsi' => $tDef['deskripsi'],
                'tlp_level' => $tDef['tlp'],
                'status' => $tDef['status'],
                'category_id' => $tDef['category_id'],
                'bidang_id' => $tDef['bidang_id'],
                'sla_resolusi_jam' => $tDef['sla_hours'],
                'sla_dimulai_at' => $tDef['status'] !== 'pending_verification' ? $createdAt : null,
                'sla_deadline_at' => $tDef['status'] !== 'pending_verification' ? $deadline : null,
                'verified_by' => $tDef['status'] !== 'pending_verification' ? $adminPusat?->id : null,
                'pin' => Hash::make('123456'),
                'created_at' => $createdAt,
                'updated_at' => Carbon::now(),
            ]);

            if ($tDef['teknisi_id']) {
                $techUser = User::find($tDef['teknisi_id']);
                TicketAssignment::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $tDef['teknisi_id'],
                    'technician_name' => $techUser?->nama ?? 'Teknisi DKIS',
                    'role' => 'lead',
                    'status' => 'working',
                    'assigned_at' => $createdAt->copy()->addMinutes(15),
                    'accepted_at' => $createdAt->copy()->addMinutes(20),
                ]);
            }
        }
    }
}
