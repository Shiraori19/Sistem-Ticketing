<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Bidang;
use App\Models\Category;
use App\Models\Notification;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // =========================================================================
        // 1. SEED ROLES
        // =========================================================================
        $rolePusat = Role::create([
            'nama_role' => 'Admin Pusat',
        ]);

        $roleBidang = Role::create([
            'nama_role' => 'Admin Bidang',
        ]);

        $roleTeknisi = Role::create([
            'nama_role' => 'Teknisi',
        ]);

        // =========================================================================
        // 2. SEED BIDANG (3 Bidang Teknis di DKIS Kota Cirebon: PEMDI, ITIK, Persandian)
        // =========================================================================
        $bidangTeknis = Bidang::create([
            'nama_bidang' => 'Pemerintahan Digital',
            'kode' => 'PEMDI',
            'icon' => 'code-bracket',
        ]);

        $bidangJaringan = Bidang::create([
            'nama_bidang' => 'Infrastruktur TIK',
            'kode' => 'ITIK',
            'icon' => 'server-stack',
        ]);

        $bidangPersandian = Bidang::create([
            'nama_bidang' => 'Persandian',
            'kode' => 'PERSANDIAN',
            'icon' => 'shield-check',
            'is_persandian' => true,
        ]);

        // =========================================================================
        // 3. SEED USERS (5 Akun Admin: 2 Admin Pusat, 3 Admin Bidang)
        // =========================================================================
        $defaultAdminPassword = env('SEEDER_ADMIN_PASSWORD', 'Dkis#Cirebon2026!');

        $adminPusat = User::create([
            'nama' => 'Ahmad Fadli',
            'username' => 'admin.pusat@cirebonkota.go.id',
            'password' => Hash::make($defaultAdminPassword),
            'role_id' => $rolePusat->id,
            'bidang_id' => null, // Admin Pusat tidak terikat bidang
            'avatar' => 'AF',
        ]);

        $adminPusat2 = User::create([
            'nama' => 'Budi Santoso',
            'username' => 'admin.pusat2@cirebonkota.go.id',
            'password' => Hash::make($defaultAdminPassword),
            'role_id' => $rolePusat->id,
            'bidang_id' => null, // Admin Pusat kedua untuk uji multi-operator
            'avatar' => 'BS',
        ]);

        $adminJaringan = User::create([
            'nama' => 'Dewi Lestari',
            'username' => 'jaringan@cirebonkota.go.id',
            'password' => Hash::make($defaultAdminPassword),
            'role_id' => $roleBidang->id,
            'bidang_id' => $bidangJaringan->id,
            'avatar' => 'DL',
        ]);

        $adminPersandian = User::create([
            'nama' => 'Riko Pratama',
            'username' => 'persandian@cirebonkota.go.id',
            'password' => Hash::make($defaultAdminPassword),
            'role_id' => $roleBidang->id,
            'bidang_id' => $bidangPersandian->id,
            'avatar' => 'RP',
        ]);

        $adminTeknis = User::create([
            'nama' => 'Andi Wijaya',
            'username' => 'aplikasi@cirebonkota.go.id',
            'password' => Hash::make($defaultAdminPassword),
            'role_id' => $roleBidang->id,
            'bidang_id' => $bidangTeknis->id,
            'avatar' => 'AW',
        ]);

        // Akun Teknisi Lapangan
        $teknisiFazra = User::create([
            'nama' => 'Fazra Fatimah',
            'username' => 'teknisi.fazra@cirebonkota.go.id',
            'password' => Hash::make($defaultAdminPassword),
            'role_id' => $roleTeknisi->id,
            'role' => 'teknisi',
            'bidang_id' => $bidangJaringan->id,
            'avatar' => 'FF',
            'no_hp' => '081234567890',
        ]);

        $teknisiAndi = User::create([
            'nama' => 'Andi Pratama',
            'username' => 'teknisi.andi@cirebonkota.go.id',
            'password' => Hash::make($defaultAdminPassword),
            'role_id' => $roleTeknisi->id,
            'role' => 'teknisi',
            'bidang_id' => $bidangTeknis->id,
            'avatar' => 'AP',
            'no_hp' => '081234567891',
        ]);

        // =========================================================================
        // 4. SEED KATEGORI LAYANAN (6 Kategori dengan variasi SLA)
        // =========================================================================
        $katTeknis1 = Category::create([
            'nama_kategori' => 'Troubleshooting Aplikasi & SI',
            'bidang_id' => $bidangTeknis->id,
            'sla_respon_jam' => 72.00, // 3x24 Jam
            'sla_resolusi_jam' => 336,  // 14 Hari
        ]);

        $katTeknis2 = Category::create([
            'nama_kategori' => 'Pengembangan Fitur & Integrasi API',
            'bidang_id' => $bidangTeknis->id,
            'sla_respon_jam' => 72.00,
            'sla_resolusi_jam' => 336,
        ]);

        $katJaringan1 = Category::create([
            'nama_kategori' => 'Gangguan Koneksi Internet OPD',
            'bidang_id' => $bidangJaringan->id,
            'sla_respon_jam' => 72.00,
            'sla_resolusi_jam' => 336,
        ]);

        $katJaringan2 = Category::create([
            'nama_kategori' => 'Konfigurasi Router & CCTV Pemkot',
            'bidang_id' => $bidangJaringan->id,
            'sla_respon_jam' => 72.00,
            'sla_resolusi_jam' => 336,
        ]);

        $katPersandian1 = Category::create([
            'nama_kategori' => 'Penerbitan / Reset Akun TTE',
            'bidang_id' => $bidangPersandian->id,
            'sla_respon_jam' => 72.00,
            'sla_resolusi_jam' => 336,
        ]);

        $katPersandian2 = Category::create([
            'nama_kategori' => 'Insiden Keamanan Siber & Malware',
            'bidang_id' => $bidangPersandian->id,
            'sla_respon_jam' => 72.00,
            'sla_resolusi_jam' => 336,
        ]);

        $katLainnya = Category::create([
            'nama_kategori' => 'Lainnya / Belum Tahu Kategori',
            'bidang_id' => $bidangTeknis->id,
            'sla_respon_jam' => 72.00,
            'sla_resolusi_jam' => 336,
        ]);

        // =========================================================================
        // 5. SEED TIKET SAMPLE (6 Tiket Merepresentasikan Berbagai Status)
        // =========================================================================

        // Tiket 1: pending_verification
        $ticket1 = Ticket::create([
            'kode_tiket' => 'TIK-202609-0001',
            'nip_pelapor' => '198503122010011003',
            'nama_pelapor' => 'H. Bambang Sutrisno, S.Kom.',
            'skpd' => 'Badan Kepegawaian dan Pengembangan SDM',
            'no_whatsapp' => '081234567891', // Mutator akan mengubah jadi 6281234567891
            'category_id' => $katTeknis1->id,
            'judul' => 'Kendala Akses Modul Presensi Online pada Simpeg DKIS',
            'deskripsi' => 'Sejak pagi ini ASN di lingkungan BKPSDM tidak dapat melakukan submit presensi masuk. Muncul pesan galat 500 internal server error pada portal web.',
            'lampiran_pelapor' => null,
            'tlp_level' => 'CLEAR',
            'bidang_id' => $bidangTeknis->id,
            'verified_by' => null,
            'assigned_by' => null,
            'status' => 'pending_verification',
            'teknisi_lapangan' => null,
            'sla_dimulai_at' => null,
            'sla_respon_jam' => $katTeknis1->sla_respon_jam,
            'sla_resolusi_jam' => $katTeknis1->sla_resolusi_jam,
            'sla_breach' => false,
            'status_history' => [
                [
                    'status' => 'pending_verification',
                    'timestamp' => Carbon::now()->subMinutes(25)->toIso8601String(),
                    'actor' => 'H. Bambang Sutrisno, S.Kom. (Pelapor)',
                    'note' => 'Tiket dibuat melalui portal mandiri OPD',
                ],
            ],
        ]);

        // Tiket 2: verified
        $ticket2 = Ticket::create([
            'kode_tiket' => 'TIK-202609-0002',
            'nip_pelapor' => '199008242015032001',
            'nama_pelapor' => 'Siti Rahmawati, S.E.',
            'skpd' => 'Badan Pengelola Keuangan dan Pendapatan Daerah (BPKPD)',
            'no_whatsapp' => '085298765432', // Mutator -> 6285298765432
            'category_id' => $katPersandian1->id,
            'judul' => 'Permohonan Pembaruan Pasfrasa Sertifikat Elektronik TTE',
            'deskripsi' => 'Pasfrasa akun TTE Kepala BPKPD terblokir karena salah input 3 kali. Mohon bantuan reset pasfrasa BSrE sesuai SK terlampir.',
            'lampiran_pelapor' => 'lampiran/sk_kadis_bpkpd.pdf',
            'tlp_level' => 'AMBER',
            'bidang_id' => $bidangPersandian->id,
            'verified_by' => $adminPusat->id,
            'assigned_by' => null,
            'status' => 'verified',
            'teknisi_lapangan' => null,
            'sla_dimulai_at' => Carbon::now()->subMinutes(15),
            'sla_respon_jam' => $katPersandian1->sla_respon_jam,
            'sla_resolusi_jam' => $katPersandian1->sla_resolusi_jam,
            'sla_breach' => false,
            'status_history' => [
                [
                    'status' => 'pending_verification',
                    'timestamp' => Carbon::now()->subMinutes(30)->toIso8601String(),
                    'actor' => 'Siti Rahmawati, S.E. (Pelapor)',
                    'note' => 'Pengajuan tiket reset TTE BSrE',
                ],
                [
                    'status' => 'verified',
                    'timestamp' => Carbon::now()->subMinutes(15)->toIso8601String(),
                    'actor' => 'Ahmad Fadli (Admin Pusat)',
                    'note' => 'Dokumen pendukung SK dan identitas pejabat telah diverifikasi sah',
                ],
            ],
        ]);

        // Tiket 3: in_progress
        $ticket3 = Ticket::create([
            'kode_tiket' => 'TIK-202609-0003',
            'nip_pelapor' => '198811052014021004',
            'nama_pelapor' => 'Irvan Nurhadi, S.T.',
            'skpd' => 'Dinas Pekerjaan Umum dan Tata Ruang',
            'no_whatsapp' => '087811223344',
            'category_id' => $katJaringan1->id,
            'judul' => 'Koneksi FO Terputus di Kantor Bidang Bina Marga',
            'deskripsi' => 'Lampu indikator LOS pada modem fiber optik berkedip merah sejak pagi. Seluruh PC pada ruangan bidang Bina Marga offline.',
            'lampiran_pelapor' => 'lampiran/foto_modem_los.jpg',
            'tlp_level' => 'GREEN',
            'bidang_id' => $bidangJaringan->id,
            'verified_by' => $adminPusat->id,
            'assigned_by' => $adminJaringan->id,
            'status' => 'in_progress',
            'teknisi_lapangan' => 'Agus Setiawan (Teknisi Jaringan DKIS)',
            'sla_dimulai_at' => Carbon::now()->subHours(1),
            'sla_respon_jam' => $katJaringan1->sla_respon_jam,
            'sla_resolusi_jam' => $katJaringan1->sla_resolusi_jam,
            'sla_breach' => false,
            'status_history' => [
                [
                    'status' => 'pending_verification',
                    'timestamp' => Carbon::now()->subHours(2)->toIso8601String(),
                    'actor' => 'Irvan Nurhadi, S.T.',
                    'note' => 'Laporan awal gangguan FO',
                ],
                [
                    'status' => 'verified',
                    'timestamp' => Carbon::now()->subHours(1)->subMinutes(45)->toIso8601String(),
                    'actor' => 'Ahmad Fadli (Admin Pusat)',
                    'note' => 'Disetujui dan diarahkan ke Bidang Jaringan',
                ],
                [
                    'status' => 'in_progress',
                    'timestamp' => Carbon::now()->subHours(1)->toIso8601String(),
                    'actor' => 'Dewi Lestari (Admin Jaringan)',
                    'note' => 'Disposisi teknisi lapangan Agus Setiawan untuk pengecekan OTB dan kabel dropcore',
                ],
            ],
        ]);

        // Tiket 4: resolved
        $ticket4 = Ticket::create([
            'kode_tiket' => 'TIK-202609-0004',
            'nip_pelapor' => '198204182006042011',
            'nama_pelapor' => 'drg. Maya Indrawati',
            'skpd' => 'Dinas Kesehatan Kota Cirebon',
            'no_whatsapp' => '081377889900',
            'category_id' => $katJaringan2->id,
            'judul' => 'Penambahan Port VLAN Khusus Ruang Rapat Kadinkes',
            'deskripsi' => 'Kebutuhan koneksi jaringan terisolasi untuk rapat koordinasi daring kementerian kesehatan di ruang rapat utama.',
            'lampiran_pelapor' => null,
            'tlp_level' => 'GREEN',
            'bidang_id' => $bidangJaringan->id,
            'verified_by' => $adminPusat->id,
            'assigned_by' => $adminJaringan->id,
            'status' => 'resolved',
            'teknisi_lapangan' => 'Sopian Hadi',
            'sla_dimulai_at' => Carbon::now()->subHours(8),
            'sla_respon_jam' => $katJaringan2->sla_respon_jam,
            'sla_resolusi_jam' => $katJaringan2->sla_resolusi_jam,
            'sla_breach' => false,
            'bukti_resolusi' => 'uploads/resolusi/TIK-202609-0004_berita_acara.pdf',
            'catatan_resolusi' => 'Konfigurasi VLAN 205 pada switch Cisco Catalyst lantai 2 telah selesai diujicobakan dengan bandwidth 50 Mbps dedicated lancar.',
            'is_confidential_evidence' => false,
            'rating' => 5,
            'feedback' => 'Pelayanan sangat memuaskan, teknisi cepat tanggap dan ramah. Terima kasih DKIS!',
            'status_history' => [
                [
                    'status' => 'pending_verification',
                    'timestamp' => Carbon::now()->subHours(9)->toIso8601String(),
                    'actor' => 'drg. Maya Indrawati',
                    'note' => 'Pengajuan pembuatan port VLAN',
                ],
                [
                    'status' => 'verified',
                    'timestamp' => Carbon::now()->subHours(8)->subMinutes(40)->toIso8601String(),
                    'actor' => 'Ahmad Fadli',
                    'note' => 'Verifikasi selesai',
                ],
                [
                    'status' => 'in_progress',
                    'timestamp' => Carbon::now()->subHours(8)->toIso8601String(),
                    'actor' => 'Dewi Lestari',
                    'note' => 'Penugasan teknisi Sopian Hadi',
                ],
                [
                    'status' => 'resolved',
                    'timestamp' => Carbon::now()->subHours(3)->toIso8601String(),
                    'actor' => 'Sopian Hadi (Teknisi)',
                    'note' => 'Konfigurasi selesai dan speedtest sesuai SLA',
                ],
            ],
        ]);

        // Tiket 5: eskalasi_pusat
        $ticket5 = Ticket::create([
            'kode_tiket' => 'TIK-202609-0005',
            'nip_pelapor' => '199405022019031008',
            'nama_pelapor' => 'Rizky Ramadhani, S.Kom.',
            'skpd' => 'Dinas Kependudukan dan Pencatatan Sipil',
            'no_whatsapp' => '089612345678',
            'category_id' => $katPersandian2->id,
            'judul' => 'Deteksi Aktivitas Ransomware pada Server SIAK Lokal',
            'deskripsi' => 'Ditemukan ekstensi .locked pada direktori arsip SIAK lokal. Server fisik telah dicabut dari switch LAN untuk karantina.',
            'lampiran_pelapor' => 'lampiran/ransom_note_screenshot.png',
            'tlp_level' => 'RED',
            'bidang_id' => $bidangPersandian->id,
            'verified_by' => $adminPusat->id,
            'assigned_by' => $adminPersandian->id,
            'status' => 'eskalasi_pusat',
            'teknisi_lapangan' => 'Tim Tanggap Insiden CSIRT DKIS',
            'sla_dimulai_at' => Carbon::now()->subHours(2),
            'sla_respon_jam' => $katPersandian2->sla_respon_jam,
            'sla_resolusi_jam' => $katPersandian2->sla_resolusi_jam,
            'sla_breach' => false,
            'is_confidential_evidence' => true,
            'status_history' => [
                [
                    'status' => 'pending_verification',
                    'timestamp' => Carbon::now()->subHours(3)->toIso8601String(),
                    'actor' => 'Rizky Ramadhani, S.Kom.',
                    'note' => 'Laporan insiden keamanan RED TLP',
                ],
                [
                    'status' => 'verified',
                    'timestamp' => Carbon::now()->subHours(2)->subMinutes(50)->toIso8601String(),
                    'actor' => 'Ahmad Fadli (Admin Pusat)',
                    'note' => 'Validasi cepat dan langsung ditangani Tim Persandian',
                ],
                [
                    'status' => 'in_progress',
                    'timestamp' => Carbon::now()->subHours(2)->toIso8601String(),
                    'actor' => 'Riko Pratama (Admin Persandian)',
                    'note' => 'Isolasi jaringan, analisa hash biner malware',
                ],
                [
                    'status' => 'eskalasi_pusat',
                    'timestamp' => Carbon::now()->subMinutes(40)->toIso8601String(),
                    'actor' => 'Riko Pratama (Admin Persandian)',
                    'note' => 'Memerlukan bantuan koordinasi tingkat lanjut dengan BSSN dan arahan Kepala Dinas',
                ],
            ],
        ]);

        // Tiket 6: force_closed
        $ticket6 = Ticket::create([
            'kode_tiket' => 'TIK-202609-0006',
            'nip_pelapor' => '198701012011011002',
            'nama_pelapor' => 'Asep Saepudin',
            'skpd' => 'Kecamatan Harjamukti',
            'no_whatsapp' => '082122334455',
            'category_id' => $katTeknis2->id,
            'judul' => 'Permintaan Instalasi Software Bajakan di Laptop Pribadi',
            'deskripsi' => 'Mohon bantu instalasi software berbayar bajakan tanpa lisensi di perangkat laptop pribadi staf.',
            'lampiran_pelapor' => null,
            'tlp_level' => 'CLEAR',
            'bidang_id' => $bidangTeknis->id,
            'verified_by' => $adminPusat->id,
            'status' => 'force_closed',
            'force_close_notes' => 'Tiket ditutup paksa oleh Admin Pusat karena melanggar SOP Keamanan Informasi DKIS Kota Cirebon mengenai larangan perangkat lunak bajakan dan servis perangkat pribadi non-kedinasan.',
            'status_history' => [
                [
                    'status' => 'pending_verification',
                    'timestamp' => Carbon::now()->subDays(1)->toIso8601String(),
                    'actor' => 'Asep Saepudin',
                    'note' => 'Tiket dibuat',
                ],
                [
                    'status' => 'force_closed',
                    'timestamp' => Carbon::now()->subDays(1)->addMinutes(15)->toIso8601String(),
                    'actor' => 'Ahmad Fadli (Admin Pusat)',
                    'note' => 'Penutupan paksa: Permohonan melanggar regulasi DKIS',
                ],
            ],
        ]);

        // =========================================================================
        // 6. SEED NOTIFICATIONS (Sampel WhatsApp & Sistem)
        // =========================================================================
        Notification::create([
            'ticket_id' => $ticket1->id,
            'nomor_tujuan' => $ticket1->no_whatsapp,
            'pesan' => "Halo Bapak/Ibu {$ticket1->nama_pelapor}, tiket Anda dengan nomor {$ticket1->kode_tiket} berhasil diajukan dan sedang menunggu verifikasi Tim DKIS Kota Cirebon.",
            'status_kirim' => 'sent',
            'created_at' => Carbon::now()->subMinutes(24),
        ]);

        Notification::create([
            'ticket_id' => $ticket3->id,
            'nomor_tujuan' => $ticket3->no_whatsapp,
            'pesan' => "Pemberitahuan: Tiket {$ticket3->kode_tiket} sedang ditangani oleh teknisi: {$ticket3->teknisi_lapangan}.",
            'status_kirim' => 'sent',
            'created_at' => Carbon::now()->subHours(1),
        ]);

        Notification::create([
            'ticket_id' => $ticket4->id,
            'nomor_tujuan' => $ticket4->no_whatsapp,
            'pesan' => "Tiket {$ticket4->kode_tiket} telah SELESAI ditangani. Silakan berikan penilaian layanan pada portal DKIS.",
            'status_kirim' => 'sent',
            'created_at' => Carbon::now()->subHours(3),
        ]);

        // =========================================================================
        // 7. SEED AUDIT LOG (Pencatatan Jejak Audit Keamanan)
        // =========================================================================
        AuditLog::create([
            'user_id' => $adminPusat->id,
            'aksi' => 'VERIFIKASI_TIKET',
            'target_kode' => 'TIK-202609-0002',
            'ip_address' => '192.168.10.15',
            'detail' => 'Admin Pusat memvalidasi berkas SK TTE BPKPD dan meneruskan ke Bidang Persandian',
            'timestamp' => Carbon::now()->subMinutes(15),
        ]);

        AuditLog::create([
            'user_id' => $adminJaringan->id,
            'aksi' => 'DISPOSISI_TEKNISI',
            'target_kode' => 'TIK-202609-0003',
            'ip_address' => '192.168.10.22',
            'detail' => 'Admin Jaringan mendisposisikan Agus Setiawan untuk penanganan kabel FO',
            'timestamp' => Carbon::now()->subHours(1),
        ]);

        AuditLog::create([
            'user_id' => $adminPersandian->id,
            'aksi' => 'ESKALASI_KEAMANAN_SIBER',
            'target_kode' => 'TIK-202609-0005',
            'ip_address' => '192.168.10.30',
            'detail' => 'Admin Persandian menaikkan status insiden ransomware ke Eskalasi Pusat TLP RED',
            'timestamp' => Carbon::now()->subMinutes(40),
        ]);

        AuditLog::create([
            'user_id' => $adminPusat->id,
            'aksi' => 'FORCE_CLOSE_TIKET',
            'target_kode' => 'TIK-202609-0006',
            'ip_address' => '192.168.10.15',
            'detail' => 'Penutupan paksa tiket karena pelanggaran kebijakan software ilegal',
            'timestamp' => Carbon::now()->subDays(1)->addMinutes(15),
        ]);
    }
}
