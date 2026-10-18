<?php

namespace Database\Seeders;

use App\Models\Bidang;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DummyTicketsSeeder extends Seeder
{
    public function run(): void
    {
        $adminPusat = User::where('username', 'admin.pusat@cirebonkota.go.id')->first();
        $adminJaringan = User::where('username', 'jaringan@cirebonkota.go.id')->first();
        $adminTeknis = User::where('username', 'aplikasi@cirebonkota.go.id')->first();
        $adminPersandian = User::where('username', 'persandian@cirebonkota.go.id')->first();

        $bidangTeknis = Bidang::where('kode', 'PEMDI')->orWhere('kode', 'TEKNIS')->first();
        $bidangJaringan = Bidang::where('kode', 'ITIK')->orWhere('kode', 'JARINGAN')->first();
        $bidangPersandian = Bidang::where('kode', 'PERSANDIAN')->first();

        $catApp = Category::where('bidang_id', $bidangTeknis?->id)->first() ?? Category::first();
        $catNet = Category::where('bidang_id', $bidangJaringan?->id)->first() ?? Category::first();
        $catSec = Category::where('bidang_id', $bidangPersandian?->id)->first() ?? Category::first();

        $now = Carbon::now();

        $dummyTickets = [
            // =========================================================================
            // 1. ANTREAN VERIFIKASI ADMIN PUSAT (pending_verification)
            // =========================================================================
            [
                'kode_tiket' => 'TIK-202609-0011',
                'nip_pelapor' => '198304122008011005',
                'nama_pelapor' => 'Drs. Supardi, M.Pd.',
                'skpd' => 'Dinas Pendidikan',
                'no_whatsapp' => '081324567811',
                'category_id' => $catApp->id,
                'judul' => 'Akses e-Kinerja Guru Lambat Menjelang Batas Unggah SKP',
                'deskripsi' => 'Banyak laporan dari kepala sekolah dan guru bahwa portal aplikasi e-Kinerja sering mengalami time out saat proses unggah bukti dukung laporan kinerja bulanan.',
                'tlp_level' => 'CLEAR',
                'bidang_id' => $bidangTeknis->id,
                'status' => 'pending_verification',
                'sla_respon_jam' => 0.5,
                'sla_resolusi_jam' => 4,
                'status_history' => [
                    ['status' => 'pending_verification', 'timestamp' => $now->copy()->subMinutes(35)->toIso8601String(), 'actor' => 'Drs. Supardi, M.Pd. (Pelapor)', 'note' => 'Pengajuan tiket antrean verifikasi OPD']
                ]
            ],
            [
                'kode_tiket' => 'TIK-202609-0012',
                'nip_pelapor' => '198907152014022003',
                'nama_pelapor' => 'dr. Tri Wahyuni, Sp.A.',
                'skpd' => 'Dinas Kesehatan',
                'no_whatsapp' => '082123456782',
                'category_id' => $catNet->id,
                'judul' => 'Permohonan Peningkatan Bandwidth Internet Puskesmas Nelayan',
                'deskripsi' => 'Puskesmas Nelayan membutuhkan alokasi bandwidth tambahan untuk mendukung kelancaran input data rekam medis elektronik (RME) dan bridging BPJS Kesehatan.',
                'tlp_level' => 'GREEN',
                'bidang_id' => $bidangJaringan->id,
                'status' => 'pending_verification',
                'sla_respon_jam' => 0.25,
                'sla_resolusi_jam' => 2,
                'status_history' => [
                    ['status' => 'pending_verification', 'timestamp' => $now->copy()->subMinutes(20)->toIso8601String(), 'actor' => 'dr. Tri Wahyuni, Sp.A. (Pelapor)', 'note' => 'Permohonan penambahan bandwidth puskesmas']
                ]
            ],
            [
                'kode_tiket' => 'TIK-202609-0013',
                'nip_pelapor' => '199211022019031004',
                'nama_pelapor' => 'Angga Kurniawan, S.STP.',
                'skpd' => 'Satuan Polisi Pamong Praja (Satpol PP)',
                'no_whatsapp' => '085712345673',
                'category_id' => $catNet->id,
                'judul' => 'Kamera CCTV Ruang Pantau Ketertiban Umum Tidak Menampilkan Gambar',
                'deskripsi' => 'Dua unit kamera pantau CCTV ruang command center Satpol PP terputus koneksinya (indikator no signal). Mohon pengecekan jalur kabel UTP dan switch PoE.',
                'tlp_level' => 'GREEN',
                'bidang_id' => $bidangJaringan->id,
                'status' => 'pending_verification',
                'sla_respon_jam' => 0.25,
                'sla_resolusi_jam' => 2,
                'status_history' => [
                    ['status' => 'pending_verification', 'timestamp' => $now->copy()->subMinutes(12)->toIso8601String(), 'actor' => 'Angga Kurniawan, S.STP. (Pelapor)', 'note' => 'Laporan kendala CCTV Satpol PP']
                ]
            ],
            [
                'kode_tiket' => 'TIK-202609-0014',
                'nip_pelapor' => '199405102020122008',
                'nama_pelapor' => 'Rina Marlina, S.IP.',
                'skpd' => 'Dinas Kependudukan dan Pencatatan Sipil (Disdukcapil)',
                'no_whatsapp' => '087812345674',
                'category_id' => $catApp->id,
                'judul' => 'Printer Cetak KTP Elektronik Tidak Terdeteksi pada PC Loket Pelayanan 2',
                'deskripsi' => 'Driver printer cetak KTP-el mengalami error device not recognized setelah pembaruan sistem operasi Windows pada PC loket pelayanan administrasi kependudukan.',
                'tlp_level' => 'CLEAR',
                'bidang_id' => $bidangTeknis->id,
                'status' => 'pending_verification',
                'sla_respon_jam' => 0.5,
                'sla_resolusi_jam' => 4,
                'status_history' => [
                    ['status' => 'pending_verification', 'timestamp' => $now->copy()->subMinutes(5)->toIso8601String(), 'actor' => 'Rina Marlina, S.IP. (Pelapor)', 'note' => 'Laporan kendala printer KTP loket']
                ]
            ],
            [
                'kode_tiket' => 'TIK-202609-0015',
                'nip_pelapor' => '198706182011011002',
                'nama_pelapor' => 'Bayu Wicaksono, M.Si.',
                'skpd' => 'Badan Perencanaan Pembangunan, Penelitian dan Pengembangan Daerah (Bappelitbangda)',
                'no_whatsapp' => '081298765415',
                'category_id' => $catApp->id,
                'judul' => 'Konsultasi Integrasi Data Statistik Sektoral pada Geoportal Cirebon',
                'deskripsi' => 'Bappelitbangda memerlukan fasilitasi integrasi data sebaran infrastruktur perkotaan dari WebGIS Bappelitbangda ke data center portal satu data DKIS.',
                'tlp_level' => 'CLEAR',
                'bidang_id' => $bidangTeknis->id,
                'status' => 'pending_verification',
                'sla_respon_jam' => 1.0,
                'sla_resolusi_jam' => 8,
                'status_history' => [
                    ['status' => 'pending_verification', 'timestamp' => $now->copy()->subHours(1)->toIso8601String(), 'actor' => 'Bayu Wicaksono, M.Si. (Pelapor)', 'note' => 'Konsultasi integrasi geoportal']
                ]
            ],

            // =========================================================================
            // 2. TIKET TERVERIFIKASI — MENUNGGU DISPOSISI TEKNISI (verified)
            // =========================================================================
            [
                'kode_tiket' => 'TIK-202609-0016',
                'nip_pelapor' => '199102282015031003',
                'nama_pelapor' => 'Rian Hidayat, S.Kom.',
                'skpd' => 'Dinas Pekerjaan Umum dan Tata Ruang (DPUTR)',
                'no_whatsapp' => '081398765416',
                'category_id' => $catNet->id,
                'judul' => 'Pemasangan Port Jaringan LAN Baru di Ruang Rapat Bidang Bina Marga',
                'deskripsi' => 'Kebutuhan penambahan 4 titik outlet kabel LAN Cat6 untuk kegiatan zoom meeting dan pemaparan proyek strategis daerah di ruang rapat.',
                'tlp_level' => 'GREEN',
                'bidang_id' => $bidangJaringan->id,
                'verified_by' => $adminPusat?->id,
                'status' => 'verified',
                'sla_dimulai_at' => $now->copy()->subMinutes(40),
                'sla_respon_jam' => 0.25,
                'sla_resolusi_jam' => 2,
                'status_history' => [
                    ['status' => 'pending_verification', 'timestamp' => $now->copy()->subHours(1)->toIso8601String(), 'actor' => 'Rian Hidayat, S.Kom.', 'note' => 'Laporan dibuat'],
                    ['status' => 'verified', 'timestamp' => $now->copy()->subMinutes(40)->toIso8601String(), 'actor' => 'Ahmad Fadli (Admin Pusat)', 'note' => 'Disposisi ke Bidang ITIK']
                ]
            ],
            [
                'kode_tiket' => 'TIK-202609-0017',
                'nip_pelapor' => '198610052010012007',
                'nama_pelapor' => 'Hj. Nurul Aini, S.Sos.',
                'skpd' => 'Dinas Koperasi, Usaha Kecil, Menengah, Perdagangan dan Perindustrian (DKUKMPP)',
                'no_whatsapp' => '082298765417',
                'category_id' => $catSec->id,
                'judul' => 'Permohonan Audit Kerentanan Keamanan Siber Aplikasi Marketplace UMKM',
                'deskripsi' => 'Sebelum launching portal belanja UMKM Kota Cirebon, dinas mengajukan vulnerability assessment (VA) dan penetrasi testing untuk memastikan tidak ada celah keamanan.',
                'tlp_level' => 'AMBER',
                'bidang_id' => $bidangPersandian->id,
                'verified_by' => $adminPusat?->id,
                'status' => 'verified',
                'sla_dimulai_at' => $now->copy()->subMinutes(25),
                'sla_respon_jam' => 1.0,
                'sla_resolusi_jam' => 24,
                'status_history' => [
                    ['status' => 'pending_verification', 'timestamp' => $now->copy()->subHours(2)->toIso8601String(), 'actor' => 'Hj. Nurul Aini, S.Sos.', 'note' => 'Permohonan audit VA UMKM'],
                    ['status' => 'verified', 'timestamp' => $now->copy()->subMinutes(25)->toIso8601String(), 'actor' => 'Ahmad Fadli (Admin Pusat)', 'note' => 'Disposisi ke Bidang Persandian']
                ]
            ],

            // =========================================================================
            // 3. SEDANG DIKERJAKAN TEKNISI (in_progress)
            // =========================================================================
            [
                'kode_tiket' => 'TIK-202609-0018',
                'nip_pelapor' => '198001012005011009',
                'nama_pelapor' => 'Dedi Suwandi, S.E.',
                'skpd' => 'Sekretariat Daerah Kota Cirebon',
                'no_whatsapp' => '081211223318',
                'category_id' => $catNet->id,
                'judul' => 'Pergantian Switch Hub Distribusi Lantai 3 Gedung Setda',
                'deskripsi' => 'Switch hub 24-port mengalami kerusakan power supply internal yang menyebabkan koneksi ruangan Bagian Hukum dan Organisasi terputus.',
                'tlp_level' => 'GREEN',
                'bidang_id' => $bidangJaringan->id,
                'verified_by' => $adminPusat?->id,
                'assigned_by' => $adminJaringan?->id,
                'teknisi_lapangan' => 'Hendra Gunawan, S.Kom. (Network & FO Specialist)',
                'status' => 'in_progress',
                'sla_dimulai_at' => $now->copy()->subHours(1),
                'sla_respon_jam' => 0.25,
                'sla_resolusi_jam' => 2,
                'status_history' => [
                    ['status' => 'pending_verification', 'timestamp' => $now->copy()->subHours(2)->toIso8601String(), 'actor' => 'Dedi Suwandi, S.E.', 'note' => 'Laporan masuk'],
                    ['status' => 'verified', 'timestamp' => $now->copy()->subHours(1)->subMinutes(30)->toIso8601String(), 'actor' => 'Ahmad Fadli (Admin Pusat)', 'note' => 'Diverifikasi'],
                    ['status' => 'in_progress', 'timestamp' => $now->copy()->subHours(1)->toIso8601String(), 'actor' => 'Dewi Lestari (Admin ITIK)', 'note' => 'Penunjukan teknisi: Hendra Gunawan, S.Kom.']
                ]
            ],
            [
                'kode_tiket' => 'TIK-202609-0019',
                'nip_pelapor' => '199304192019021005',
                'nama_pelapor' => 'Fajar Pratama, S.Kom.',
                'skpd' => 'Badan Pengelola Keuangan dan Pendapatan Daerah (BPKPD)',
                'no_whatsapp' => '085611223319',
                'category_id' => $catApp->id,
                'judul' => 'Optimasi Query dan Indeks Basis Data Modul Pajak PBB-P2',
                'deskripsi' => 'Proses generate laporan penerimaan pajak harian membutuhkan waktu lebih dari 5 menit pada jam kerja sibuk. Diperlukan penambahan indeks tabel transaksi.',
                'tlp_level' => 'CLEAR',
                'bidang_id' => $bidangTeknis->id,
                'verified_by' => $adminPusat?->id,
                'assigned_by' => $adminTeknis?->id,
                'teknisi_lapangan' => 'Faisal Akbar, S.Kom. (Senior Web Developer)',
                'status' => 'in_progress',
                'sla_dimulai_at' => $now->copy()->subHours(2),
                'sla_respon_jam' => 0.5,
                'sla_resolusi_jam' => 4,
                'status_history' => [
                    ['status' => 'pending_verification', 'timestamp' => $now->copy()->subHours(3)->toIso8601String(), 'actor' => 'Fajar Pratama, S.Kom.', 'note' => 'Laporan diajukan'],
                    ['status' => 'verified', 'timestamp' => $now->copy()->subHours(2)->subMinutes(30)->toIso8601String(), 'actor' => 'Ahmad Fadli', 'note' => 'Disposisi aplikasi'],
                    ['status' => 'in_progress', 'timestamp' => $now->copy()->subHours(2)->toIso8601String(), 'actor' => 'Andi Wijaya (Admin PEMDI)', 'note' => 'Penugasan teknisi: Faisal Akbar, S.Kom.']
                ]
            ],

            // =========================================================================
            // 4. SELESAI PENGERJAAN — MENUNGGU KONFIRMASI PELAPOR (resolved)
            // =========================================================================
            [
                'kode_tiket' => 'TIK-202609-0020',
                'nip_pelapor' => '198712252010011004',
                'nama_pelapor' => 'Asep Saepullah, S.T.',
                'skpd' => 'Badan Penanggulangan Bencana Daerah (BPBD)',
                'no_whatsapp' => '087711223320',
                'category_id' => $catNet->id,
                'judul' => 'Perbaikan Koneksi Radio Link Tower BPBD ke Data Center DKIS',
                'deskripsi' => 'Sinyal radio wireless antar tower mengalami penurunan kualitas throughput akibat pergeseran pointing antena pasca angin kencang.',
                'tlp_level' => 'GREEN',
                'bidang_id' => $bidangJaringan->id,
                'verified_by' => $adminPusat?->id,
                'assigned_by' => $adminJaringan?->id,
                'teknisi_lapangan' => 'Eko Wahyudi, A.Md. (Data Center & Hardware Tech)',
                'status' => 'resolved',
                'catatan_resolusi' => 'Telah dilakukan re-pointing antena parabolic dan pengencangan mounting bracket. Link throughput kembali stabil di 150 Mbps dengan latency < 2ms.',
                'bukti_resolusi' => 'resolusi/berita_acara_tower_bpbd.pdf',
                'sla_dimulai_at' => $now->copy()->subHours(3),
                'sla_respon_jam' => 0.25,
                'sla_resolusi_jam' => 2,
                'status_history' => [
                    ['status' => 'pending_verification', 'timestamp' => $now->copy()->subHours(4)->toIso8601String(), 'actor' => 'Asep Saepullah, S.T.', 'note' => 'Laporan masuk'],
                    ['status' => 'verified', 'timestamp' => $now->copy()->subHours(3)->subMinutes(30)->toIso8601String(), 'actor' => 'Ahmad Fadli', 'note' => 'Verifikasi selesai'],
                    ['status' => 'in_progress', 'timestamp' => $now->copy()->subHours(3)->toIso8601String(), 'actor' => 'Dewi Lestari', 'note' => 'Teknisi Eko Wahyudi berangkat ke lokasi'],
                    ['status' => 'resolved', 'timestamp' => $now->copy()->subMinutes(30)->toIso8601String(), 'actor' => 'Dewi Lestari (Admin ITIK)', 'note' => 'Pekerjaan re-pointing antena selesai tuntas']
                ]
            ],

            // =========================================================================
            // 5. SELESAI SEMPURNA & DITUTUP PELAPOR (closed)
            // =========================================================================
            [
                'kode_tiket' => 'TIK-202609-0021',
                'nip_pelapor' => '198402142008011003',
                'nama_pelapor' => 'Agus Hermanto, S.Sos.',
                'skpd' => 'Dinas Pemuda dan Olahraga (Dispora)',
                'no_whatsapp' => '081299887721',
                'category_id' => $catNet->id,
                'judul' => 'Pemasangan Access Point WiFi Gratis di Tribun Utama GOR Bima Cirebon',
                'deskripsi' => 'Persiapan fasilitas internet publik untuk penyelenggaraan kejuaraan atletik antar pelajar se-Jawa Barat.',
                'tlp_level' => 'CLEAR',
                'bidang_id' => $bidangJaringan->id,
                'verified_by' => $adminPusat?->id,
                'assigned_by' => $adminJaringan?->id,
                'teknisi_lapangan' => 'Rian Hidayat, S.T. (CCTV & Bandwidth Specialist)',
                'status' => 'closed',
                'catatan_resolusi' => 'Telah terpasang 2 unit outdoor AP Ruijie Reyee dengan kapasitas 200 concurrent user dan bandwidth terkelola 100 Mbps.',
                'bukti_resolusi' => 'resolusi/foto_ap_gor_bima.jpg',
                'feedback' => 'Pemasangan sangat cepat dan rapi. Pengujian sinyal WiFi lancar di seluruh area tribun penonton. Terima kasih banyak tim DKIS!',
                'sla_dimulai_at' => $now->copy()->subDays(1),
                'sla_respon_jam' => 0.25,
                'sla_resolusi_jam' => 2,
                'status_history' => [
                    ['status' => 'pending_verification', 'timestamp' => $now->copy()->subDays(1)->subHours(3)->toIso8601String(), 'actor' => 'Agus Hermanto, S.Sos.', 'note' => 'Pengajuan AP GOR'],
                    ['status' => 'verified', 'timestamp' => $now->copy()->subDays(1)->subHours(2)->toIso8601String(), 'actor' => 'Ahmad Fadli', 'note' => 'Disposisi ke Bidang ITIK'],
                    ['status' => 'in_progress', 'timestamp' => $now->copy()->subDays(1)->subHours(1)->toIso8601String(), 'actor' => 'Dewi Lestari', 'note' => 'Penugasan teknisi Rian Hidayat'],
                    ['status' => 'resolved', 'timestamp' => $now->copy()->subHours(5)->toIso8601String(), 'actor' => 'Dewi Lestari', 'note' => 'Instalasi dan uji coba speedtest selesai'],
                    ['status' => 'closed', 'timestamp' => $now->copy()->subHours(2)->toIso8601String(), 'actor' => 'Agus Hermanto, S.Sos. (Pelapor)', 'note' => 'Pelapor mengonfirmasi selesai dan memberikan ulasan']
                ]
            ],

            // =========================================================================
            // 6. ESKALASI KE ADMIN PUSAT KARENA 3X REOPEN (eskalasi_pusat)
            // =========================================================================
            [
                'kode_tiket' => 'TIK-202609-0022',
                'nip_pelapor' => '199003152014022002',
                'nama_pelapor' => 'Nurlita Sari, S.Tr.Sos.',
                'skpd' => 'Dinas Sosial',
                'no_whatsapp' => '085211223322',
                'category_id' => $catApp->id,
                'judul' => 'Aplikasi Bansos Sering Logout Otomatis Saat Input Data DTKS',
                'deskripsi' => 'Petugas verifikator bansos mengeluhkan sesi aplikasi sering tertutup sendiri (session timeout) setelah mengisi 10 menit form verifikasi keluarga miskin.',
                'tlp_level' => 'GREEN',
                'bidang_id' => $bidangTeknis->id,
                'verified_by' => $adminPusat?->id,
                'assigned_by' => $adminTeknis?->id,
                'teknisi_lapangan' => 'Dian Pratama, A.Md. (Database & App Specialist)',
                'status' => 'eskalasi_pusat',
                'reopen_count' => 3,
                'max_reopen' => 2,
                'sla_dimulai_at' => $now->copy()->subDays(2),
                'sla_respon_jam' => 0.5,
                'sla_resolusi_jam' => 4,
                'status_history' => [
                    ['status' => 'pending_verification', 'timestamp' => $now->copy()->subDays(2)->toIso8601String(), 'actor' => 'Nurlita Sari, S.Tr.Sos.', 'note' => 'Laporan awal'],
                    ['status' => 'in_progress', 'timestamp' => $now->copy()->subDays(2)->addHour()->toIso8601String(), 'actor' => 'Andi Wijaya', 'note' => 'Pengerjaan session timeout'],
                    ['status' => 'resolved', 'timestamp' => $now->copy()->subDays(1)->toIso8601String(), 'actor' => 'Andi Wijaya', 'note' => 'Perpanjangan session lifetime menjadi 120 menit'],
                    ['status' => 'reopened', 'timestamp' => $now->copy()->subDays(1)->addHours(2)->toIso8601String(), 'actor' => 'Nurlita Sari', 'note' => 'Reopen 1: Masih logout saat upload KTP'],
                    ['status' => 'resolved', 'timestamp' => $now->copy()->subHours(12)->toIso8601String(), 'actor' => 'Andi Wijaya', 'note' => 'Patching upload handler'],
                    ['status' => 'reopened', 'timestamp' => $now->copy()->subHours(6)->toIso8601String(), 'actor' => 'Nurlita Sari', 'note' => 'Reopen 2: Error 419 Page Expired tetap muncul'],
                    ['status' => 'eskalasi_pusat', 'timestamp' => $now->copy()->subHours(1)->toIso8601String(), 'actor' => 'Sistem Otomatis DKIS', 'note' => 'Tiket mencapai batas reopen maksimum (3x). Otomatis dialihkan ke status Eskalasi Pusat untuk audit pimpinan']
                ]
            ],

            // =========================================================================
            // 7. INSIDEN KRITIS TLP RED — TANGGAP DARURAT CSIRT (in_progress bypass)
            // =========================================================================
            [
                'kode_tiket' => 'TIK-202609-0023',
                'nip_pelapor' => '198105052006041011',
                'nama_pelapor' => 'Ir. Hendro Prasetyo, M.T.',
                'skpd' => 'Dinas Komunikasi, Informatika dan Statistik (DKIS)',
                'no_whatsapp' => '081122334423',
                'category_id' => $catSec->id,
                'judul' => 'Deteksi Upaya Brute Force dan Anomali Akses pada Database Simpeg',
                'deskripsi' => 'Sistem SIEM DKIS mendeteksi 15.000 percobaan otentikasi ilegal dari IP asing terhadap port manajemen basis data kepegawaian.',
                'tlp_level' => 'RED',
                'bidang_id' => null, // Bypass bidang: langsung dipegang CSIRT pusat
                'verified_by' => $adminPusat?->id,
                'assigned_by' => $adminPusat?->id,
                'teknisi_lapangan' => 'Sandi Kusuma, M.Kom. (CSIRT Cyber Security Analyst)',
                'status' => 'in_progress',
                'is_confidential_evidence' => true,
                'sla_dimulai_at' => $now->copy()->subMinutes(15),
                'sla_respon_jam' => 0.25,
                'sla_resolusi_jam' => 2,
                'status_history' => [
                    ['status' => 'pending_verification', 'timestamp' => $now->copy()->subMinutes(25)->toIso8601String(), 'actor' => 'Ir. Hendro Prasetyo, M.T.', 'note' => 'Laporan insiden keamanan siber'],
                    ['status' => 'in_progress', 'timestamp' => $now->copy()->subMinutes(15)->toIso8601String(), 'actor' => 'Ahmad Fadli (Admin Pusat)', 'note' => 'Klasifikasi TLP RED: Penanganan darurat bypass langsung oleh Tim Tanggap Insiden Siber CSIRT']
                ]
            ],
        ];

        foreach ($dummyTickets as $data) {
            $data['sla_respon_jam'] = 72.00;
            $data['sla_resolusi_jam'] = 336;
            Ticket::updateOrCreate(
                ['kode_tiket' => $data['kode_tiket']],
                $data
            );
        }
    }
}
