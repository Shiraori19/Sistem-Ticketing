<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Bidang;
use App\Models\Category;
use App\Models\TechnicianProfile;
use App\Models\Ticket;
use App\Models\TicketAssignment;
use App\Models\TicketObstacle;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class TicketWorkflowService
{
    /**
     * Generate format kode tiket unik: TIK-YYYYMM-XXXX
     */
    public function generateTicketCode(): string
    {
        $prefix = 'TIK-' . Carbon::now()->format('Ym') . '-';
        
        // Memanfaatkan B-Tree Unique Index pada kolom kode_tiket (O(log N)) tanpa full table scan casting
        $latestCode = Ticket::where('kode_tiket', 'LIKE', $prefix . '%')
            ->orderByDesc('kode_tiket')
            ->value('kode_tiket');

        $nextNumber = 1;
        if ($latestCode && str_starts_with($latestCode, $prefix)) {
            $lastSeq = (int) substr($latestCode, strlen($prefix));
            $nextNumber = $lastSeq + 1;
        }

        $candidateCode = $prefix . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);

        // Fail-safe pencegahan collision race condition
        while (Ticket::where('kode_tiket', $candidateCode)->exists()) {
            $nextNumber++;
            $candidateCode = $prefix . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
        }

        return $candidateCode;
    }

    /**
     * 1. Buat tiket baru dari form ASN
     */
    public function createTicket(array $data): Ticket
    {
        return retry(3, function () use ($data) {
            return DB::transaction(function () use ($data) {
                // PostgreSQL transaction-level advisory lock untuk serialisasi pembuatan kode tiket saat concurrent requests
                try {
                    DB::statement("SELECT pg_advisory_xact_lock(hashtext('ticket_code_generation'))");
                } catch (\Throwable $e) {
                    // Fallback jika database driver non-PostgreSQL
                }

                $category = Category::findOrFail($data['category_id']);
                
                // Validasi anti-duplicate: generate ulang jika kode sudah terpakai
                $kodeTiket = $data['kode_tiket'] ?? null;
                if (!$kodeTiket || Ticket::where('kode_tiket', $kodeTiket)->exists()) {
                    $kodeTiket = $this->generateTicketCode();
                }

                $rawPin = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
                $responJam = (float) ($category->sla_respon_jam ?: 72.00);
                $responDeadline = Carbon::now()->addMinutes((int) round($responJam * 60));

                $ticket = Ticket::create([
                    'kode_tiket' => $kodeTiket,
                    'nip_pelapor' => $data['nip_pelapor'],
                    'nama_pelapor' => $data['nama_pelapor'],
                    'skpd' => $data['skpd'],
                    'no_whatsapp' => $data['no_whatsapp'] ?? $data['nomor_kontak'] ?? '',
                    'pin' => Hash::make($rawPin), // Disimpan dengan Bcrypt hash aman ber-salt
                    'category_id' => $category->id,
                    'judul' => $data['judul'],
                    'deskripsi' => $data['deskripsi'],
                    'lampiran_pelapor' => $data['lampiran_pelapor'] ?? null,
                    'tlp_level' => $data['tlp_level'] ?? null,
                    'bidang_id' => $category->bidang_id,
                    'status' => 'pending_verification',
                    'sla_respon_jam' => $category->sla_respon_jam ?: 72.00,
                    'sla_resolusi_jam' => $category->sla_resolusi_jam ?: 336,
                    'sla_respon_deadline_at' => $responDeadline,
                    'sla_breach' => false,
                    'reopen_count' => 0,
                    'max_reopen' => $data['max_reopen'] ?? 3,
                    'status_history' => [],
                ]);

                $this->appendHistory(
                    $ticket,
                    'pending_verification',
                    $ticket->nama_pelapor . ' (Pelapor)',
                    'Tiket berhasil diajukan melalui portal layanan mandiri DKIS'
                );

                // Simpan PIN mentah sementara untuk struk tanda terima peramban saat pembuatan tiket
                $ticket->raw_pin_temporary = $rawPin;

                return $ticket;
            });
        }, 50);
    }

    /**
     * 2. Verifikasi & disposisi oleh Admin Pusat
     *    KHUSUS TLP RED: bidangId = null, langsung in_progress (bypass bidang)
     */
    public function verifyAndDispose(Ticket $ticket, string $tlp, ?int $bidangId, ?string $notes, User $actor): Ticket
    {
        return DB::transaction(function () use ($ticket, $tlp, $bidangId, $notes, $actor) {
            $waktuMasuk = $ticket->created_at ? Carbon::parse($ticket->created_at) : Carbon::now();
            $waktuVerifikasi = Carbon::now();
            $slaResponJam = (float) ($ticket->sla_respon_jam ?: 72);
            $targetResponMins = max(1, (int) round($slaResponJam * 60));
            $actualResponMins = max(0, (int) $waktuMasuk->diffInMinutes($waktuVerifikasi, false) * ($waktuVerifikasi->gte($waktuMasuk) ? 1 : -1));
            if ($actualResponMins === 0 && $waktuVerifikasi->gt($waktuMasuk)) {
                $actualResponMins = (int) $waktuMasuk->diffInMinutes($waktuVerifikasi);
            }
            $isResponBreached = $actualResponMins > $targetResponMins;

            $ticket->sla_respon_breach = $isResponBreached;
            $ticket->tlp_level = $tlp;
            $ticket->verified_by = $actor->id;

            // FLOW-03: Pertahankan sla_dimulai_at awal jika tiket merupakan re-disposisi (pengembalian dari bidang)
            $isReDisposition = !is_null($ticket->sla_dimulai_at);
            if (!$isReDisposition) {
                $ticket->sla_dimulai_at = $waktuVerifikasi;
                $resolusiJam = (float) ($ticket->sla_resolusi_jam ?: 336);
                $ticket->sla_deadline_at = $waktuVerifikasi->copy()->addMinutes((int) round($resolusiJam * 60)); // Standarisasi: 14 Hari Kalender (336 Jam)
            }

            if (strtoupper($tlp) === 'RED') {
                // TLP RED: Penanggung jawab utama otomatis Bidang Persandian & CSIRT
                $persandian = \App\Models\Bidang::where('kode', 'PERSANDIAN')
                    ->orWhere('nama_bidang', 'LIKE', '%Persandian%')
                    ->first();
                $ticket->bidang_id = $bidangId ?: ($persandian?->id ?? null);
                $ticket->status = 'in_progress';
                $baseNote = $notes ?: 'Tiket berkategori TLP RED diverifikasi dan diarahkan ke Bidang Persandian & CSIRT sebagai penanggung jawab utama (disiarkan ke seluruh bidang untuk koordinasi).';
            } else {
                $ticket->bidang_id = $bidangId;
                $ticket->status = 'verified';
                $baseNote = $notes ?: 'Tiket diverifikasi dan diteruskan ke Bidang terkait.';
            }

            // Catatan evaluasi SLA Respon Admin Pusat
            $waktuTeks = $actualResponMins >= 60 
                ? floor($actualResponMins / 60) . 'j ' . ($actualResponMins % 60) . 'm' 
                : $actualResponMins . 'm';
            $targetTeks = $targetResponMins >= 60 
                ? floor($targetResponMins / 60) . ' jam' 
                : $targetResponMins . ' menit';

            if ($isResponBreached) {
                $terlambatMins = $actualResponMins - $targetResponMins;
                $telatTeks = $terlambatMins >= 60 
                    ? floor($terlambatMins / 60) . 'j ' . ($terlambatMins % 60) . 'm' 
                    : $terlambatMins . 'm';
                $slaNote = " [⚠️ SLA Respon Terlampaui: Verifikasi butuh {$waktuTeks} dari target {$targetTeks} (telat {$telatTeks})]";
            } else {
                $slaNote = " [✓ SLA Respon Tepat Waktu: {$waktuTeks} (target: {$targetTeks})]";
            }

            $statusNote = $baseNote . $slaNote;

            $this->appendHistory($ticket, $ticket->status, $actor->nama . ' (Admin Pusat)', $statusNote);

            // Audit log
            AuditLog::create([
                'user_id' => $actor->id,
                'aksi' => 'VERIFIKASI_TIKET',
                'target_kode' => $ticket->kode_tiket,
                'ip_address' => request()?->ip() ?? '127.0.0.1',
                'detail' => "Verifikasi tiket {$ticket->kode_tiket} dengan TLP: {$tlp}, Status: {$ticket->status}." . ($isResponBreached ? " (SLA Respon MELEBIHI BATAS: {$waktuTeks} vs target {$targetTeks})" : " (SLA Respon Tepat Waktu: {$waktuTeks})"),
                'timestamp' => Carbon::now(),
            ]);

            return $ticket;
        });
    }

    /**
     * 2b. Minta kelengkapan data/berkas ke pelapor (Status: pending_info)
     *     Menghentikan sementara (pause) SLA Respon di Admin Pusat
     */
    public function requestPendingInfo(Ticket $ticket, string $notes, User $actor): Ticket
    {
        return DB::transaction(function () use ($ticket, $notes, $actor) {
            $ticket->status = 'pending_info';
            $ticket->catatan_kelengkapan = $notes;

            $this->appendHistory(
                $ticket,
                'pending_info',
                $actor->nama . ' (Admin Pusat)',
                'Permintaan kelengkapan data/berkas susulan ke pelapor: ' . $notes
            );

            AuditLog::create([
                'user_id' => $actor->id,
                'aksi' => 'MINTA_KELENGKAPAN_DATA',
                'target_kode' => $ticket->kode_tiket,
                'ip_address' => request()?->ip() ?? '127.0.0.1',
                'detail' => "Tiket {$ticket->kode_tiket} dialihkan ke pending_info: {$notes}",
                'timestamp' => Carbon::now(),
            ]);

            return $ticket;
        });
    }

    /**
     * 2c. Pelapor mengirim data kelengkapan susulan via portal mandiri PIN 6 digit
     *     Mengembalikan status tiket ke 'pending_verification' untuk ditelaah ulang
     */
    public function submitPendingInfo(Ticket $ticket, array $data): Ticket
    {
        return DB::transaction(function () use ($ticket, $data) {
            $ticket->status = 'pending_verification';
            if (!empty($data['lampiran_tambahan'])) {
                $ticket->lampiran_tambahan = $data['lampiran_tambahan'];
            }
            if (!empty($data['catatan_tambahan'])) {
                $ticket->deskripsi .= "\n\n[Susulan Pelapor - " . Carbon::now()->format('d/m/Y H:i') . "]: " . $data['catatan_tambahan'];
            }

            $this->appendHistory(
                $ticket,
                'pending_verification',
                $ticket->nama_pelapor . ' (Pelapor)',
                'Pelapor telah mengirimkan kelengkapan data susulan. Menunggu verifikasi lanjutan.'
            );

            return $ticket;
        });
    }

    /**
     * 2d. Tetapkan tiket sebagai Induk Tiket (Master Incident)
     */
    public function createParentTicket(Ticket $ticket, User $actor): Ticket
    {
        return DB::transaction(function () use ($ticket, $actor) {
            $ticket->is_parent = true;
            $ticket->save();

            $this->appendHistory(
                $ticket,
                $ticket->status,
                $actor->nama . ' (Admin Pusat)',
                'Tiket ditetapkan sebagai Induk Tiket (Master Incident) untuk pengelompokan penanganan insiden massal.'
            );

            return $ticket;
        });
    }

    /**
     * 2e. Tautkan tiket anak ke Induk Tiket (Safe Parent-Child Linking)
     */
    public function linkToParent(Ticket $child, Ticket $parent, User $actor): Ticket
    {
        if ($child->id === $parent->id) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'parent' => 'Tiket tidak dapat ditautkan ke dirinya sendiri sebagai tiket induk.'
            ]);
        }

        if (in_array($parent->status, ['closed', 'force_closed', 'cancelled'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'parent' => "Tidak dapat menautkan tiket ke Induk Tiket yang telah berstatus '{$parent->status}'."
            ]);
        }

        return DB::transaction(function () use ($child, $parent, $actor) {
            $parentLocked = Ticket::where('id', $parent->id)->lockForUpdate()->firstOrFail();
            $childLocked = Ticket::where('id', $child->id)->lockForUpdate()->firstOrFail();

            $parentLocked->is_parent = true;
            $parentLocked->save();

            $childLocked->parent_id = $parentLocked->id;

            // Jika parent sudah diverifikasi dan didisposisikan, wariskan bidang dan TLP ke anak
            if ($parentLocked->bidang_id && !$childLocked->bidang_id) {
                $childLocked->bidang_id = $parentLocked->bidang_id;
            }
            if ($parentLocked->tlp_level && !$childLocked->tlp_level) {
                $childLocked->tlp_level = $parentLocked->tlp_level;
            }

            if (in_array($parentLocked->status, ['verified', 'in_progress']) && in_array($childLocked->status, ['pending_verification', 'pending_info'])) {
                $childLocked->status = 'verified';
                $childLocked->verified_by = $parentLocked->verified_by ?: $actor->id;
                $childLocked->catatan_kelengkapan = null;
            }

            // Inisialisasi parameter SLA anak mewarisi Induk Tiket
            $childLocked->sla_dimulai_at = $parentLocked->sla_dimulai_at ?: Carbon::now();
            $childLocked->sla_deadline_at = $parentLocked->sla_deadline_at;
            $childLocked->sla_respon_deadline_at = $parentLocked->sla_respon_deadline_at;
            $childLocked->sla_respon_jam = $parentLocked->sla_respon_jam;
            $childLocked->sla_resolusi_jam = $parentLocked->sla_resolusi_jam;

            $childLocked->save();

            $this->appendHistory(
                $childLocked,
                $childLocked->status,
                $actor->nama . ' (Admin Pusat)',
                "Tiket ditautkan ke Induk Tiket #{$parentLocked->kode_tiket} untuk penanganan terkoordinasi (SLA diwariskan dari Induk)."
            );

            return $childLocked;
        });
    }

    /**
     * 2f. Lepaskan tiket anak dari Induk Tiket (Unlink / Detach to Standalone)
     *     Mengembalikan tiket anak menjadi tiket mandiri yang terpisah
     */
    public function unlinkFromParent(Ticket $child, string $reason, User $actor): Ticket
    {
        return DB::transaction(function () use ($child, $reason, $actor) {
            $isPersandian = $actor->bidang && (strtoupper($actor->bidang->kode) === 'PERSANDIAN' || str_contains(strtoupper($actor->bidang->nama_bidang), 'PERSANDIAN'));

            // Proteksi hak akses bidang & TLP RED
            if (strtoupper($child->tlp_level ?? '') === 'RED') {
                if (!$isPersandian && !$actor->isAdminPusat()) {
                    throw ValidationException::withMessages([
                        'bidang' => 'Akses Ditolak: Tiket berklasifikasi TLP: RED hanya dapat dikelola oleh Bidang Persandian & CSIRT.'
                    ]);
                }
            } elseif ($actor->isAdminBidang() && $child->bidang_id !== $actor->bidang_id) {
                throw ValidationException::withMessages([
                    'bidang' => 'Akses Ditolak: Tiket ini bukan kewenangan bidang Anda.'
                ]);
            }

            $parentId = $child->parent_id;
            $parent = $child->parent;
            $parentKode = $parent ? $parent->kode_tiket : "ID {$parentId}";

            $child->parent_id = null;
            $child->save();

            $actorName = $actor->nama . ' (' . ($actor->bidang->nama_bidang ?? 'Admin') . ')';
            $note = "Tiket dipecah/dilepaskan dari Induk Tiket #{$parentKode} menjadi tiket mandiri. Alasan: {$reason}";
            $this->appendHistory($child, $child->status, $actorName, $note);

            // Jika Induk tidak memiliki anak lagi, normalkan is_parent
            if ($parent && $parent->children()->count() === 0) {
                $parent->is_parent = false;
                $parent->save();

                $this->appendHistory(
                    $parent,
                    $parent->status,
                    $actorName,
                    "Seluruh tiket anak telah dilepaskan. Tiket dinormalkan kembali menjadi tiket mandiri standar."
                );
            }

            AuditLog::create([
                'user_id' => $actor->id,
                'aksi' => 'UNLINK_PARENT_CHILD',
                'target_kode' => $child->kode_tiket,
                'ip_address' => request()?->ip() ?? '127.0.0.1',
                'detail' => $note,
                'timestamp' => Carbon::now(),
            ]);

            return $child;
        });
    }

    /**
     * Mengambil daftar teknisi beserta skor beban kerja aktif secara teragregasi (O(1), Tanpa N+1)
     */
    public function getTechniciansWithWorkload(int|Bidang|null $bidang, ?Ticket $ticket = null): \Illuminate\Support\Collection
    {
        $bidangId = $bidang instanceof Bidang ? $bidang->id : $bidang;

        // Jika tiket berklasifikasi TLP RED: hanya teknisi Bidang Persandian
        $isTlpRed = $ticket && strtoupper($ticket->tlp_level ?? '') === 'RED';
        if ($isTlpRed) {
            $persandianBidang = Bidang::where('kode', 'PERSANDIAN')
                ->orWhere('nama_bidang', 'LIKE', '%PERSANDIAN%')
                ->first();
            $targetBidangId = $persandianBidang?->id;
        } else {
            $targetBidangId = $bidangId;
        }

        $query = User::with(['technicianProfile', 'bidang'])
            ->where('is_active', true)
            ->where(function ($sub) {
                $sub->whereHas('role', fn($r) => $r->where('nama_role', 'Teknisi'));
                if (Schema::hasColumn('users', 'role')) {
                    $sub->orWhere('role', 'teknisi');
                }
            });

        if ($targetBidangId) {
            $query->where('bidang_id', $targetBidangId);
        }

        $technicians = $query->get();

        if ($technicians->isEmpty()) {
            return collect();
        }

        $userIds = $technicians->pluck('id')->toArray();
        $weights = config('ticketing.workload_weights', ['RED' => 3, 'AMBER' => 2, 'GREEN' => 1, 'DEFAULT' => 1]);
        $wRed = $weights['RED'] ?? 3;
        $wAmber = $weights['AMBER'] ?? 2;
        $wGreen = $weights['GREEN'] ?? 1;
        $wDefault = $weights['DEFAULT'] ?? 1;

        $nowStr = Carbon::now()->toDateTimeString();
        $urgentThresholdStr = Carbon::now()->addHours(2)->toDateTimeString();

        // Agregasi beban kerja aktif dalam 1 query SQL efisien
        $activeStats = DB::table('ticket_assignments')
            ->join('tickets', 'ticket_assignments.ticket_id', '=', 'tickets.id')
            ->whereIn('ticket_assignments.user_id', $userIds)
            ->whereNull('ticket_assignments.unassigned_at')
            ->whereIn('ticket_assignments.status', ['assigned', 'accepted', 'working'])
            ->whereNotIn('tickets.status', ['resolved', 'closed', 'force_closed', 'cancelled'])
            ->select(
                'ticket_assignments.user_id',
                DB::raw('COUNT(ticket_assignments.id) as total_active_tickets'),
                DB::raw("SUM(CASE 
                    WHEN UPPER(COALESCE(tickets.tlp_level, '')) = 'RED' THEN {$wRed}
                    WHEN UPPER(COALESCE(tickets.tlp_level, '')) = 'AMBER' THEN {$wAmber}
                    WHEN UPPER(COALESCE(tickets.tlp_level, '')) = 'GREEN' THEN {$wGreen}
                    ELSE {$wDefault}
                END) as weighted_load"),
                DB::raw("SUM(CASE 
                    WHEN tickets.sla_deadline_at IS NOT NULL 
                         AND tickets.sla_deadline_at > '{$nowStr}' 
                         AND tickets.sla_deadline_at <= '{$urgentThresholdStr}' 
                    THEN 1 
                    ELSE 0 
                END) as urgent_tickets_count")
            )
            ->groupBy('ticket_assignments.user_id')
            ->get()
            ->keyBy('user_id');

        // Ambil tiket aktif per teknisi untuk drawer interaktif
        $activeTicketsByTech = DB::table('ticket_assignments')
            ->join('tickets', 'ticket_assignments.ticket_id', '=', 'tickets.id')
            ->whereIn('ticket_assignments.user_id', $userIds)
            ->whereNull('ticket_assignments.unassigned_at')
            ->whereIn('ticket_assignments.status', ['assigned', 'accepted', 'working'])
            ->whereNotIn('tickets.status', ['resolved', 'closed', 'force_closed', 'cancelled'])
            ->select(
                'ticket_assignments.user_id',
                'ticket_assignments.role as assignment_role',
                'tickets.id as ticket_id',
                'tickets.kode_tiket',
                'tickets.judul',
                'tickets.tlp_level',
                'tickets.sla_deadline_at'
            )
            ->get()
            ->groupBy('user_id');

        $ticketCatName = $ticket?->category?->nama_kategori ?? '';
        $defaultMaxLoad = (int) config('ticketing.default_max_load', 6);

        $mapped = $technicians->map(function ($tech) use ($activeStats, $activeTicketsByTech, $ticketCatName, $defaultMaxLoad) {
            $profile = $tech->technicianProfile ?: new TechnicianProfile([
                'user_id' => $tech->id,
                'availability' => 'tersedia',
                'max_load' => $defaultMaxLoad,
                'skills' => [],
            ]);

            $stat = $activeStats->get($tech->id);
            $totalActive = $stat ? (int) $stat->total_active_tickets : 0;
            $weightedLoad = $stat ? (int) $stat->weighted_load : 0;
            $urgentCount = $stat ? (int) $stat->urgent_tickets_count : 0;
            $maxLoad = max(1, (int) ($profile->max_load ?: $defaultMaxLoad));
            $percent = min(100, (int) round(($weightedLoad / $maxLoad) * 100));

            // Indikator warna bar beban: hijau (<60%), kuning (60-80%), merah (>80%)
            $loadColor = $percent >= 80 ? 'red' : ($percent >= 60 ? 'amber' : 'emerald');

            $isMatched = $profile->matchesCategory($ticketCatName);
            $avail = $profile->availability ?: 'tersedia';

            $techActiveTickets = ($activeTicketsByTech->get($tech->id) ?? collect())->map(function ($t) {
                $minsLeft = $t->sla_deadline_at ? Carbon::now()->diffInMinutes(Carbon::parse($t->sla_deadline_at), false) : 9999;
                return [
                    'kode_tiket' => $t->kode_tiket,
                    'judul' => $t->judul,
                    'tlp' => $t->tlp_level ?: 'GREEN',
                    'role' => $t->assignment_role ?: 'lead',
                    'mins_left' => $minsLeft,
                    'sisa_teks' => $minsLeft < 0 ? 'Breach (' . abs($minsLeft) . 'm)' : ($minsLeft < 120 ? $minsLeft . 'm' : round($minsLeft/60) . 'j'),
                ];
            })->toArray();

            return [
                'user_id' => $tech->id,
                'nama' => $tech->nama,
                'username' => $tech->username,
                'bidang_kode' => $tech->bidang?->kode ?: 'DKIS',
                'availability' => $avail,
                'is_on_leave' => $avail === 'cuti',
                'is_busy' => $avail === 'sibuk',
                'is_available' => $avail === 'tersedia',
                'skills' => $profile->skills ?: [],
                'is_skills_matched' => $isMatched,
                'total_active_tickets' => $totalActive,
                'weighted_load' => $weightedLoad,
                'max_load' => $maxLoad,
                'load_percent' => $percent,
                'load_color' => $loadColor,
                'is_full' => $weightedLoad >= $maxLoad,
                'urgent_count' => $urgentCount,
                'active_tickets' => $techActiveTickets,
            ];
        });

        // Urutan rekomendasi: tersedia > cocok kategori > beban terendah
        return $mapped->sort(function ($a, $b) {
            $scoreA_avail = $a['availability'] === 'tersedia' ? 0 : ($a['availability'] === 'sibuk' ? 1 : 2);
            $scoreB_avail = $b['availability'] === 'tersedia' ? 0 : ($b['availability'] === 'sibuk' ? 1 : 2);
            if ($scoreA_avail !== $scoreB_avail) {
                return $scoreA_avail <=> $scoreB_avail;
            }

            $scoreA_match = $a['is_skills_matched'] ? 0 : 1;
            $scoreB_match = $b['is_skills_matched'] ? 0 : 1;
            if ($scoreA_match !== $scoreB_match) {
                return $scoreA_match <=> $scoreB_match;
            }

            return $a['weighted_load'] <=> $b['weighted_load'];
        })->values();
    }

    /**
     * 3. Penunjukan teknisi lapangan oleh Admin Bidang terhubung akun & terkontrol kapasitas beban
     */
    public function assignTechnician(
        Ticket $ticket, 
        string|array|int $techNameOrId, 
        User $actor, 
        ?int $techUserId = null, 
        string $roleInTask = 'lead_pic',
        array $memberUserIds = [],
        ?string $overrideReason = null
    ): Ticket {
        return DB::transaction(function () use ($ticket, $techNameOrId, $actor, $techUserId, $roleInTask, $memberUserIds, $overrideReason) {
            // 1. Validasi status tiket
            if (!in_array($ticket->status, ['verified', 'in_progress', 'reopened'])) {
                throw ValidationException::withMessages([
                    'status' => "Tiket berstatus '{$ticket->status}' tidak dapat ditugaskan. Penugasan hanya berlaku untuk tiket berstatus verified, in_progress, atau reopened."
                ]);
            }

            // 2. Resolve Lead User
            $leadUser = null;
            if (is_int($techNameOrId)) {
                $leadUser = User::with('technicianProfile', 'bidang')->find($techNameOrId);
            } elseif ($techUserId) {
                $leadUser = User::with('technicianProfile', 'bidang')->find($techUserId);
            } elseif (is_string($techNameOrId)) {
                $leadUser = User::with('technicianProfile', 'bidang')
                    ->where('nama', trim($techNameOrId))
                    ->orWhere('username', trim($techNameOrId))
                    ->first();
            } elseif (is_array($techNameOrId) && !empty($techNameOrId)) {
                $first = $techNameOrId[0];
                if (is_numeric($first)) {
                    $leadUser = User::with('technicianProfile', 'bidang')->find((int)$first);
                } else {
                    $leadUser = User::with('technicianProfile', 'bidang')
                        ->where('nama', trim((string)$first))
                        ->orWhere('username', trim((string)$first))
                        ->first();
                }
            }

            if (!$leadUser) {
                throw ValidationException::withMessages([
                    'lead_user_id' => 'Akun teknisi koordinator lapangan (Lead PIC) wajib dipilih dari daftar akun teknisi yang terdaftar.'
                ]);
            }

            // 3. Resolve Member Users (Pendamping)
            $resolvedMembers = collect();
            if (is_array($techNameOrId) && count($techNameOrId) > 1) {
                $extra = array_slice($techNameOrId, 1);
                foreach ($extra as $m) {
                    $mUser = is_numeric($m) 
                        ? User::with('technicianProfile', 'bidang')->find((int)$m)
                        : User::with('technicianProfile', 'bidang')->where('nama', trim((string)$m))->orWhere('username', trim((string)$m))->first();
                    if ($mUser && $mUser->id !== $leadUser->id && !$resolvedMembers->contains('id', $mUser->id)) {
                        $resolvedMembers->push($mUser);
                    }
                }
            }
            foreach ($memberUserIds as $mId) {
                $mUser = User::with('technicianProfile', 'bidang')->find((int)$mId);
                if ($mUser && $mUser->id !== $leadUser->id && !$resolvedMembers->contains('id', $mUser->id)) {
                    $resolvedMembers->push($mUser);
                }
            }

            $allAssignedUsers = collect([$leadUser])->merge($resolvedMembers);

            // 4. Validasi Bidang dan Aturan TLP RED
            $isTlpRed = strtoupper($ticket->tlp_level ?? '') === 'RED';
            foreach ($allAssignedUsers as $u) {
                $uIsPersandian = $u->bidang && (strtoupper($u->bidang->kode) === 'PERSANDIAN' || str_contains(strtoupper($u->bidang->nama_bidang ?? ''), 'PERSANDIAN'));
                if ($isTlpRed) {
                    if (!$uIsPersandian) {
                        throw ValidationException::withMessages([
                            'technician' => "Akses Ditolak: Tiket berklasifikasi TLP: RED hanya boleh ditugaskan kepada teknisi Bidang Persandian. Teknisi {$u->nama} bukan anggota Bidang Persandian."
                        ]);
                    }
                } else {
                    if ($ticket->bidang_id && $u->bidang_id !== $ticket->bidang_id) {
                        throw ValidationException::withMessages([
                            'technician' => "Teknisi {$u->nama} berasal dari bidang yang berbeda dengan bidang pengampu tiket."
                        ]);
                    }
                }

                if (!$u->is_active) {
                    throw ValidationException::withMessages([
                        'technician' => "Akun teknisi {$u->nama} sedang dinonaktifkan dan tidak dapat menerima penugasan baru."
                    ]);
                }
            }

            // 5. DB lockForUpdate pada technician_profiles untuk mencegah race condition kapasitas
            $profileIds = [];
            foreach ($allAssignedUsers as $u) {
                $prof = TechnicianProfile::firstOrCreate(
                    ['user_id' => $u->id],
                    ['availability' => 'tersedia', 'max_load' => 6, 'skills' => []]
                );
                $profileIds[] = $prof->id;
            }
            $lockedProfiles = TechnicianProfile::whereIn('id', $profileIds)->lockForUpdate()->get()->keyBy('user_id');

            // 6. Cek Ketersediaan & Beban Kerja Lead
            $leadProfile = $lockedProfiles->get($leadUser->id);
            if ($leadProfile && $leadProfile->availability === 'cuti') {
                throw ValidationException::withMessages([
                    'technician' => "Teknisi koordinator {$leadUser->nama} sedang berstatus CUTI dan tidak dapat ditugaskan."
                ]);
            }

            // Bobot tiket berdasarkan TLP
            $weights = config('ticketing.workload_weights', ['RED' => 3, 'AMBER' => 2, 'GREEN' => 1, 'DEFAULT' => 1]);
            $ticketWeight = match (strtoupper($ticket->tlp_level ?? '')) {
                'RED' => $weights['RED'] ?? 3,
                'AMBER' => $weights['AMBER'] ?? 2,
                default => $weights['GREEN'] ?? 1,
            };

            // Hitung beban aktif lead saat ini
            $wRed = $weights['RED'] ?? 3;
            $wAmber = $weights['AMBER'] ?? 2;
            $wGreen = $weights['GREEN'] ?? 1;

            $currentLeadLoad = (int) DB::table('ticket_assignments')
                ->join('tickets', 'ticket_assignments.ticket_id', '=', 'tickets.id')
                ->where('ticket_assignments.user_id', $leadUser->id)
                ->whereNull('ticket_assignments.unassigned_at')
                ->whereIn('ticket_assignments.status', ['assigned', 'accepted', 'working'])
                ->whereNotIn('tickets.status', ['resolved', 'closed', 'force_closed', 'cancelled'])
                ->where('ticket_assignments.ticket_id', '!=', $ticket->id)
                ->sum(DB::raw("CASE 
                    WHEN UPPER(COALESCE(tickets.tlp_level, '')) = 'RED' THEN {$wRed}
                    WHEN UPPER(COALESCE(tickets.tlp_level, '')) = 'AMBER' THEN {$wAmber}
                    ELSE {$wGreen}
                END"));

            $maxLoad = max(1, (int) ($leadProfile?->max_load ?: config('ticketing.default_max_load', 6)));

            if (($currentLeadLoad + $ticketWeight) > $maxLoad) {
                if (empty(trim((string)$overrideReason))) {
                    throw ValidationException::withMessages([
                        'override_reason' => "Teknisi {$leadUser->nama} telah mencapai batas beban kerja ({$currentLeadLoad}/{$maxLoad} poin). Menugaskan tiket berbobot {$ticketWeight} poin ini memerlukan alasan override kapasitas resmi."
                    ]);
                }

                AuditLog::create([
                    'user_id' => $actor->id,
                    'aksi' => 'OVERRIDE_KAPASITAS_TEKNISI',
                    'target_kode' => $ticket->kode_tiket,
                    'ip_address' => request()?->ip() ?? '127.0.0.1',
                    'detail' => "Override kapasitas teknisi {$leadUser->nama} (Beban: {$currentLeadLoad}/{$maxLoad}, Tiket: +{$ticketWeight}). Alasan: {$overrideReason}",
                    'timestamp' => Carbon::now(),
                ]);
            }

            // 7. Mekanisme Reassign: Tutup penugasan lama secara aman (append-only history)
            $oldActiveAssignments = $ticket->assignments()->whereNull('unassigned_at')->get();
            if ($oldActiveAssignments->isNotEmpty()) {
                $now = Carbon::now();
                foreach ($oldActiveAssignments as $old) {
                    $old->unassigned_at = $now;
                    $old->alasan_unassign = $overrideReason ?: "Penugasan dialihkan kepada {$leadUser->nama} oleh {$actor->nama}";
                    $old->save();
                }

                AuditLog::create([
                    'user_id' => $actor->id,
                    'aksi' => 'REASSIGN_TEKNISI',
                    'target_kode' => $ticket->kode_tiket,
                    'ip_address' => request()?->ip() ?? '127.0.0.1',
                    'detail' => "Menutup " . $oldActiveAssignments->count() . " penugasan sebelumnya untuk tiket {$ticket->kode_tiket} dan mendisposisikan ulang ke {$leadUser->nama}.",
                    'timestamp' => Carbon::now(),
                ]);
            }

            // 8. Buat baris penugasan baru dengan batas respond_deadline (RED 15m, lainnya 60m)
            $timeoutMinutes = strtoupper($ticket->tlp_level ?? '') === 'RED' ? 15 : 60;
            $respondDeadline = Carbon::now()->addMinutes($timeoutMinutes);

            // Lead PIC
            $leadAssign = TicketAssignment::create([
                'ticket_id' => $ticket->id,
                'user_id' => $leadUser->id,
                'role' => 'lead',
                'role_in_task' => 'lead_pic',
                'technician_name' => $leadUser->nama,
                'status' => 'assigned',
                'assigned_at' => Carbon::now(),
                'respond_deadline' => $respondDeadline,
                'row_version' => 1,
            ]);

            \App\Models\TicketEvent::create([
                'ticket_id' => $ticket->id,
                'assignment_id' => $leadAssign->id,
                'actor_id' => $actor->id,
                'type' => 'assigned',
                'payload' => [
                    'technician_name' => $leadUser->nama,
                    'role' => 'lead',
                    'respond_deadline' => $respondDeadline->toIso8601String(),
                ],
                'created_at' => Carbon::now(),
            ]);

            // Pendamping (Members)
            foreach ($resolvedMembers as $mUser) {
                $memberAssign = TicketAssignment::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $mUser->id,
                    'role' => 'pendamping',
                    'role_in_task' => 'member',
                    'technician_name' => $mUser->nama,
                    'status' => 'assigned',
                    'assigned_at' => Carbon::now(),
                    'respond_deadline' => $respondDeadline,
                    'row_version' => 1,
                ]);

                \App\Models\TicketEvent::create([
                    'ticket_id' => $ticket->id,
                    'assignment_id' => $memberAssign->id,
                    'actor_id' => $actor->id,
                    'type' => 'assigned',
                    'payload' => [
                        'technician_name' => $mUser->nama,
                        'role' => 'pendamping',
                        'respond_deadline' => $respondDeadline->toIso8601String(),
                    ],
                    'created_at' => Carbon::now(),
                ]);
            }

            // 9. Sinkronisasi metadata tiket
            $allNames = $allAssignedUsers->pluck('nama')->toArray();
            $summaryNames = implode(', ', $allNames);
            $ticket->teknisi_lapangan = $summaryNames;
            $ticket->assigned_by = $actor->id;
            $ticket->status = 'in_progress';

            if (!$ticket->sla_dimulai_at) {
                $ticket->sla_dimulai_at = Carbon::now();
            }
            if (!$ticket->sla_deadline_at) {
                $resolusiJam = (float) ($ticket->sla_resolusi_jam ?: 336);
                $ticket->sla_deadline_at = $ticket->sla_dimulai_at->copy()->addMinutes((int) round($resolusiJam * 60));
            }
            $ticket->save();

            $teamInfo = $resolvedMembers->isNotEmpty() 
                ? "Tim Teknisi: {$leadUser->nama} (Lead) & " . $resolvedMembers->pluck('nama')->implode(', ')
                : "Teknisi: {$leadUser->nama} (Lead PIC)";
            $this->appendHistory($ticket, 'in_progress', $actor->nama . ' (Admin Bidang)', "Penunjukan {$teamInfo}");

            AuditLog::create([
                'user_id' => $actor->id,
                'aksi' => 'DISPOSISI_TEKNISI',
                'target_kode' => $ticket->kode_tiket,
                'ip_address' => request()?->ip() ?? '127.0.0.1',
                'detail' => "Menugaskan tim teknisi [{$summaryNames}] untuk tiket {$ticket->kode_tiket}",
                'timestamp' => Carbon::now(),
            ]);

            return $ticket;
        });
    }

    /**
     * Lepas penugasan otomatis jika teknisi tidak merespons (menerima/menolak) dalam batas SLA respons penugasan
     */
    public function checkResponseSlaTimeouts(): int
    {
        $now = Carbon::now();
        $timeoutRed = (int) config('ticketing.response_timeout_minutes.RED', 15);
        $timeoutDefault = (int) config('ticketing.response_timeout_minutes.DEFAULT', 60);

        $expiredAssignments = TicketAssignment::with('ticket', 'user')
            ->whereNull('unassigned_at')
            ->where('status', 'assigned')
            ->whereHas('ticket', function ($q) {
                $q->whereNotIn('status', ['resolved', 'closed', 'force_closed', 'cancelled']);
            })
            ->get()
            ->filter(function ($assignment) use ($now, $timeoutRed, $timeoutDefault) {
                $limit = strtoupper($assignment->ticket->tlp_level ?? '') === 'RED' ? $timeoutRed : $timeoutDefault;
                return $assignment->assigned_at && $assignment->assigned_at->copy()->addMinutes($limit)->isPast();
            });

        $count = 0;
        foreach ($expiredAssignments as $assignment) {
            $ticket = $assignment->ticket;
            $assignment->unassigned_at = $now;
            $assignment->alasan_unassign = 'Penugasan otomatis dilepas karena melewati batas waktu respons konfirmasi (SLA Response Timeout).';
            $assignment->save();

            $this->appendHistory(
                $ticket,
                $ticket->status,
                'Sistem SLA Otomatis',
                "Penugasan teknisi {$assignment->technician_name} dilepaskan otomatis karena melebihi batas waktu konfirmasi respons."
            );

            AuditLog::create([
                'user_id' => null,
                'aksi' => 'TIMEOUT_PENUGASAN_TEKNISI',
                'target_kode' => $ticket->kode_tiket,
                'ip_address' => '127.0.0.1',
                'detail' => "Penugasan teknisi {$assignment->technician_name} pada tiket {$ticket->kode_tiket} dilepas karena timeout respons.",
                'timestamp' => $now,
            ]);

            $count++;
        }

        return $count;
    }

    /**
     * 4. Tandai selesai oleh Admin Bidang / Teknisi (Bukti resolusi bersifat opsional)
     *    Jika tiket adalah Induk Tiket, otomatis selesaikan seluruh tiket anak (Safe Parent-Child Resolution)
     */
    public function markResolved(Ticket $ticket, string $notes, ?string $evidencePath = null, bool $isConfidential = false, ?User $actor = null): Ticket
    {
        return DB::transaction(function () use ($ticket, $notes, $evidencePath, $isConfidential, $actor) {
            $ticket->status = 'resolved';
            $ticket->catatan_resolusi = $notes;
            $ticket->bukti_resolusi = $evidencePath;
            $ticket->is_confidential_evidence = $isConfidential;

            // Evaluasi SLA Resolusi Teknis
            $isResolusiBreached = false;
            $waktuTeksResolusi = 'tanpa timer';
            if ($ticket->sla_dimulai_at) {
                $targetResolusiMins = max(1, (int) round(((float) ($ticket->sla_resolusi_jam ?: 336)) * 60));
                $actualResolusiMins = max(0, Carbon::parse($ticket->sla_dimulai_at)->diffInMinutes(Carbon::now()));
                $isResolusiBreached = $actualResolusiMins > $targetResolusiMins;

                $waktuTeksResolusi = $actualResolusiMins >= 60 
                    ? floor($actualResolusiMins / 60) . 'j ' . ($actualResolusiMins % 60) . 'm' 
                    : $actualResolusiMins . 'm';
                $targetTeksResolusi = $targetResolusiMins >= 60 
                    ? floor($targetResolusiMins / 60) . ' jam' 
                    : $targetResolusiMins . ' menit';

                $slaNote = $isResolusiBreached 
                    ? " [⚠️ SLA Resolusi Melebihi Batas: Pengerjaan {$waktuTeksResolusi} dari target {$targetTeksResolusi}]" 
                    : " [✓ SLA Resolusi Tepat Waktu: {$waktuTeksResolusi} (target: {$targetTeksResolusi})]";
            } else {
                $slaNote = "";
            }

            $ticket->sla_breach = $isResolusiBreached;
            $this->appendHistory($ticket, 'resolved', $actor->nama . ' (Admin Bidang)', $notes . $slaNote);

            // Cascading Resolution ke Tiket-Tiket Anak jika ini Induk Tiket
            if ($ticket->is_parent) {
                $childTickets = Ticket::where('parent_id', $ticket->id)
                    ->whereNotIn('status', ['resolved', 'closed', 'force_closed', 'cancelled'])
                    ->get();

                foreach ($childTickets as $child) {
                    $child->status = 'resolved';
                    $child->sla_breach = $isResolusiBreached;
                    $child->catatan_resolusi = $notes;
                    $child->bukti_resolusi = $evidencePath;
                    $child->is_confidential_evidence = $isConfidential;
                    $child->save();

                    $this->appendHistory(
                        $child,
                        'resolved',
                        $actor->nama . ' (Induk Tiket ' . $ticket->kode_tiket . ')',
                        'Diselesaikan otomatis mengikuti penyelesaian Induk Tiket: ' . $notes . $slaNote
                    );
                }
            }

            $evidenceDetail = $evidencePath ? "dengan bukti: {$evidencePath}" : "tanpa lampiran berkas";
            $slaAuditText = $isResolusiBreached ? " (SLA Resolusi MELEBIHI BATAS: {$waktuTeksResolusi})" : " (SLA Resolusi Tepat Waktu)";
            AuditLog::create([
                'user_id' => $actor->id,
                'aksi' => 'RESOLUSI_TIKET',
                'target_kode' => $ticket->kode_tiket,
                'ip_address' => request()?->ip() ?? '127.0.0.1',
                'detail' => "Penyelesaian tiket {$ticket->kode_tiket} {$evidenceDetail}{$slaAuditText}" . ($ticket->is_parent ? " (Beserta seluruh tiket anak)" : ""),
                'timestamp' => Carbon::now(),
            ]);

            return $ticket;
        });
    }

    /**
     * 4b. Pengembalian tiket salah disposisi ke Admin Pusat
     *     Status dikembalikan ke 'pending_verification' tanpa menambah enum status baru di database
     */
    public function returnToPusat(Ticket $ticket, string $alasan, User $actor): Ticket
    {
        return DB::transaction(function () use ($ticket, $alasan, $actor) {
            $namaBidang = $actor->bidang->nama_bidang ?? 'Bidang Terkait';
            $ticket->status = 'pending_verification';
            $ticket->bidang_id = null;
            $ticket->teknisi_lapangan = null;
            $ticket->catatan_kelengkapan = "Dikembalikan oleh Bidang {$namaBidang}: " . $alasan;

            $this->appendHistory(
                $ticket,
                'pending_verification',
                $actor->nama . " (Admin {$namaBidang})",
                'Disposisi dikembalikan: ' . $alasan
            );

            AuditLog::create([
                'user_id' => $actor->id,
                'aksi' => 'KEMBALIKAN_DISPOSISI',
                'target_kode' => $ticket->kode_tiket,
                'ip_address' => request()?->ip() ?? '127.0.0.1',
                'detail' => "Mengembalikan tiket {$ticket->kode_tiket} ke Admin Pusat. Alasan: {$alasan}",
                'timestamp' => Carbon::now(),
            ]);

            return $ticket;
        });
    }

    /**
     * 5. Konfirmasi selesai oleh pelapor ASN (tanpa login)
     */
    public function confirmClose(Ticket $ticket, ?int $rating = null, ?string $feedback = null): Ticket
    {
        return DB::transaction(function () use ($ticket, $rating, $feedback) {
            $ticket->status = 'closed';
            $ticket->rating = $rating !== null ? max(1, min(5, (int) $rating)) : 5;
            $ticket->feedback = $feedback;
            $ticket->save();

            $stars = str_repeat('⭐', (int) $ticket->rating);
            $note = "Pelapor mengonfirmasi penyelesaian tiket dengan penilaian {$ticket->rating}/5 {$stars}." . ($feedback ? " Ulasan: {$feedback}" : "");
            $this->appendHistory($ticket, 'closed', $ticket->nama_pelapor . ' (Pelapor)', $note);

            AuditLog::create([
                'user_id' => null,
                'aksi' => 'KONFIRMASI_SELESAI_PELAPOR',
                'target_kode' => $ticket->kode_tiket,
                'ip_address' => request()?->ip() ?? '127.0.0.1',
                'detail' => "Pelapor menutup tiket {$ticket->kode_tiket}. Rating: {$ticket->rating}/5 {$stars}" . ($feedback ? ", Ulasan: {$feedback}" : ""),
                'timestamp' => Carbon::now(),
            ]);

            return $ticket;
        });
    }

    /**
     * 6. Reopen oleh pelapor (WAJIB ada bukti file)
     *    Jika reopen_count >= max_reopen: otomatis ubah status ke eskalasi_pusat
     */
    public function reopen(Ticket $ticket, string $reason, string $evidencePath): Ticket
    {
        return DB::transaction(function () use ($ticket, $reason, $evidencePath) {
            $ticket->reopen_count = ($ticket->reopen_count ?? 0) + 1;
            $ticket->bukti_reopen_tambahan = $evidencePath;

            $maxReopen = (int) ($ticket->max_reopen ?: 3);
            if ($ticket->reopen_count >= $maxReopen) {
                // Otomatis ke eskalasi_pusat
                $ticket->status = 'eskalasi_pusat';
                $note = "Reopen ke-{$ticket->reopen_count} mencapai batas maksimum ({$maxReopen}x). Tiket otomatis dialihkan ke status Eskalasi Pusat untuk peninjauan khusus pimpinan. Alasan: {$reason}";
            } else {
                $ticket->status = 'reopened';
                $note = "Tiket dibuka kembali oleh pelapor (Reopen ke-{$ticket->reopen_count}). Alasan: {$reason}";
            }

            $this->appendHistory($ticket, $ticket->status, $ticket->nama_pelapor . ' (Pelapor)', $note);

            return $ticket;
        });
    }

    /**
     * 7. Tutup paksa oleh Admin Pusat (hanya untuk status eskalasi_pusat)
     */
    public function forceClose(Ticket $ticket, string $notes, User $actor): Ticket
    {
        if ($ticket->status !== 'eskalasi_pusat') {
            throw ValidationException::withMessages([
                'status' => "Force close hanya dapat dilakukan pada tiket berstatus 'eskalasi_pusat'. Status tiket saat ini: '{$ticket->status}'."
            ]);
        }

        return DB::transaction(function () use ($ticket, $notes, $actor) {
            $ticket->status = 'force_closed';
            $ticket->force_close_notes = $notes;

            $this->appendHistory($ticket, 'force_closed', $actor->nama . ' (Admin Pusat)', $notes);

            AuditLog::create([
                'user_id' => $actor->id,
                'aksi' => 'FORCE_CLOSE_TIKET',
                'target_kode' => $ticket->kode_tiket,
                'ip_address' => request()?->ip() ?? '127.0.0.1',
                'detail' => "Penutupan paksa tiket {$ticket->kode_tiket}: {$notes}",
                'timestamp' => Carbon::now(),
            ]);

            return $ticket;
        });
    }

    /**
     * 8. Batalkan / Tolak Tiket (status: cancelled)
     *    Bisa dilakukan oleh Pelapor (fase pending_verification) atau Admin Pusat (alasan penolakan baku)
     */
    public function cancelTicket(Ticket $ticket, string $reason, string $actor): Ticket
    {
        if (in_array($ticket->status, ['closed', 'force_closed', 'cancelled'])) {
            throw ValidationException::withMessages([
                'status' => "Tiket yang sudah selesai atau dibatalkan tidak dapat dibatalkan kembali."
            ]);
        }

        return DB::transaction(function () use ($ticket, $reason, $actor) {
            $ticket->status = 'cancelled';
            $this->appendHistory($ticket, 'cancelled', $actor, "Tiket dibatalkan/ditolak. Alasan: {$reason}");

            return $ticket;
        });
    }

    /**
     * 9. Aksi Teknisi: Terima Penugasan (accept)
     */
    public function acceptAssignment(TicketAssignment $assignment, User $actor): TicketAssignment
    {
        return DB::transaction(function () use ($assignment, $actor) {
            $assignment->status = 'accepted';
            $assignment->save();

            $ticket = $assignment->ticket;
            $this->appendHistory($ticket, $ticket->status, $actor->nama . ' (Teknisi)', 'Teknisi mengonfirmasi dan menerima tugas.');

            AuditLog::create([
                'user_id' => $actor->id,
                'aksi' => 'TERIMA_PENUGASAN',
                'target_kode' => $ticket->kode_tiket,
                'ip_address' => request()?->ip() ?? '127.0.0.1',
                'detail' => "Teknisi {$actor->nama} menerima penugasan tiket {$ticket->kode_tiket}",
                'timestamp' => Carbon::now(),
            ]);

            return $assignment;
        });
    }

    /**
     * 10. Aksi Teknisi: Tolak Penugasan (decline) dengan alasan formal wajib
     */
    public function declineAssignment(TicketAssignment $assignment, string $reason, User $actor): TicketAssignment
    {
        return DB::transaction(function () use ($assignment, $reason, $actor) {
            $assignment->status = 'declined';
            $assignment->decline_reason = $reason;
            $assignment->save();

            $ticket = $assignment->ticket;
            $this->appendHistory($ticket, $ticket->status, $actor->nama . ' (Teknisi)', "Teknisi menolak tugas. Alasan: {$reason}. Tiket dikembalikan ke antrean bidang.");

            AuditLog::create([
                'user_id' => $actor->id,
                'aksi' => 'TOLAK_PENUGASAN',
                'target_kode' => $ticket->kode_tiket,
                'ip_address' => request()?->ip() ?? '127.0.0.1',
                'detail' => "Teknisi {$actor->nama} menolak penugasan tiket {$ticket->kode_tiket}: {$reason}",
                'timestamp' => Carbon::now(),
            ]);

            return $assignment;
        });
    }

    /**
     * 11. Aksi Teknisi: Mulai Pengerjaan Lapangan (status: working, tiket: in_progress)
     */
    public function startProgress(Ticket $ticket, TicketAssignment $assignment, User $actor): Ticket
    {
        return DB::transaction(function () use ($ticket, $assignment, $actor) {
            $assignment->status = 'working';
            $assignment->save();

            $ticket->status = 'in_progress';
            if (!$ticket->pengerjaan_dimulai_at) {
                $ticket->pengerjaan_dimulai_at = Carbon::now();
            }
            if (!$ticket->sla_dimulai_at) {
                $ticket->sla_dimulai_at = Carbon::now();
            }
            $ticket->save();

            $this->appendHistory($ticket, 'in_progress', $actor->nama . ' (Teknisi)', 'Teknisi tiba di lokasi dan memulai pengerjaan fisik/sistem.');

            AuditLog::create([
                'user_id' => $actor->id,
                'aksi' => 'MULAI_PENGERJAAN',
                'target_kode' => $ticket->kode_tiket,
                'ip_address' => request()?->ip() ?? '127.0.0.1',
                'detail' => "Teknisi {$actor->nama} memulai pengerjaan lapangan untuk tiket {$ticket->kode_tiket}",
                'timestamp' => Carbon::now(),
            ]);

            return $ticket;
        });
    }

    /**
     * 12. Aksi Teknisi: Catat Progres Timeline (Append-Only Worklog)
     */
    public function addWorklog(Ticket $ticket, TicketAssignment $assignment, string $notes, User $actor): TicketAssignment
    {
        return DB::transaction(function () use ($ticket, $assignment, $notes, $actor) {
            $worklog = $assignment->worklog ?? [];
            $worklog[] = [
                'note' => $notes,
                'timestamp' => Carbon::now()->toIso8601String(),
                'user' => $actor->nama,
            ];
            $assignment->worklog = $worklog;
            $assignment->save();

            $this->appendHistory($ticket, $ticket->status, $actor->nama . ' (Teknisi)', 'Progres lapangan: ' . $notes);

            AuditLog::create([
                'user_id' => $actor->id,
                'aksi' => 'WORKLOG_TEKNISI',
                'target_kode' => $ticket->kode_tiket,
                'ip_address' => request()?->ip() ?? '127.0.0.1',
                'detail' => "Teknisi {$actor->nama} mencatat progres lapangan: {$notes}",
                'timestamp' => Carbon::now(),
            ]);

            return $assignment;
        });
    }

    /**
     * 13. Aksi Teknisi: Laporkan Hambatan Lapangan (KEBIJAKAN KETAT: Jam SLA TETAP BERJALAN)
     */
    public function reportObstacle(Ticket $ticket, TicketAssignment $assignment, array $data, User $actor): TicketObstacle
    {
        return DB::transaction(function () use ($ticket, $assignment, $data, $actor) {
            $obstacle = TicketObstacle::create([
                'ticket_id' => $ticket->id,
                'user_id' => $actor->id,
                'obstacle_type' => $data['obstacle_type'],
                'description' => $data['description'],
                'evidence_path' => $data['evidence_path'] ?? null,
                'reported_at' => Carbon::now(),
                'follow_up_deadline' => Carbon::now()->addHours(24),
            ]);

            // PENTING: Jam SLA TETAP BERJALAN! Tidak mempause SLA.
            $evidenceText = !empty($data['evidence_path']) ? " (Bukti: {$data['evidence_path']})" : "";
            $note = "Hambatan dilaporkan: {$data['obstacle_type']} - {$data['description']}{$evidenceText}. PERHATIAN: Sesuai kebijakan SLA ketat, jam SLA tetap berjalan.";
            $this->appendHistory($ticket, $ticket->status, $actor->nama . ' (Teknisi)', $note);

            AuditLog::create([
                'user_id' => $actor->id,
                'aksi' => 'LAPOR_HAMBATAN_SLA_BERJALAN',
                'target_kode' => $ticket->kode_tiket,
                'ip_address' => request()?->ip() ?? '127.0.0.1',
                'detail' => "Hambatan dilaporkan untuk tiket {$ticket->kode_tiket}: {$data['obstacle_type']} - {$data['description']}. Jam SLA tidak dihentikan.",
                'timestamp' => Carbon::now(),
            ]);

            return $obstacle;
        });
    }

    /**
     * 14. Aksi Teknisi: Ajukan Selesai (submitted ke Admin Bidang, DILARANG SELF-CLOSE)
     */
    public function submitForReview(Ticket $ticket, TicketAssignment $assignment, string $notes, ?string $evidencePath, User $actor): TicketAssignment
    {
        return DB::transaction(function () use ($ticket, $assignment, $notes, $evidencePath, $actor) {
            $assignment->status = 'submitted';
            $assignment->submitted_at = Carbon::now();
            $assignment->notes = $notes;
            $assignment->save();

            if ($evidencePath) {
                $ticket->bukti_resolusi = $evidencePath;
            }
            $ticket->catatan_resolusi = $notes;
            $ticket->save();

            $this->appendHistory($ticket, 'in_progress', $actor->nama . ' (Teknisi)', "Pengajuan selesai diajukan oleh teknisi: {$notes}. Menunggu review & verifikasi Admin Bidang.");

            AuditLog::create([
                'user_id' => $actor->id,
                'aksi' => 'AJUKAN_SELESAI_REVIEW',
                'target_kode' => $ticket->kode_tiket,
                'ip_address' => request()?->ip() ?? '127.0.0.1',
                'detail' => "Teknisi {$actor->nama} mengajukan penyelesaian untuk tiket {$ticket->kode_tiket}. Menunggu review Admin Bidang.",
                'timestamp' => Carbon::now(),
            ]);

            return $assignment;
        });
    }

    /**
     * 15. Review Admin Bidang atas Pengajuan Selesai Teknisi (Approve atau Return)
     */
    public function reviewSubmission(Ticket $ticket, TicketAssignment $assignment, string $action, ?string $notes, User $actor, ?string $evidencePath = null, ?bool $isConfidential = null): Ticket
    {
        return DB::transaction(function () use ($ticket, $assignment, $action, $notes, $actor, $evidencePath, $isConfidential) {
            if ($action === 'approve') {
                $assignment->status = 'approved';
                $assignment->reviewed_at = Carbon::now();
                $assignment->reviewed_by = $actor->id;
                $assignment->review_notes = $notes;
                $assignment->save();

                // Evaluasi kerahasiaan otomatis jika null
                $confidential = $isConfidential;
                if ($confidential === null) {
                    $confidential = (strtoupper($ticket->tlp_level ?? '') === 'RED');
                }

                $finalEvidence = $evidencePath ?: $ticket->bukti_resolusi;
                $finalNotes = $notes ?: ($assignment->notes ?: 'Pekerjaan perbaikan lapangan telah diverifikasi dan disetujui Admin Bidang.');

                return $this->markResolved($ticket, $finalNotes, $finalEvidence, $confidential, $actor);
            } else {
                // Return / Kembalikan ke teknisi untuk perbaikan ulang
                $assignment->status = 'returned';
                $assignment->reviewed_at = Carbon::now();
                $assignment->reviewed_by = $actor->id;
                $assignment->review_notes = $notes;
                $assignment->save();

                $this->appendHistory($ticket, 'in_progress', $actor->nama . ' (Admin Bidang)', "Hasil perbaikan dikembalikan oleh Admin Bidang untuk penyempurnaan: {$notes}");

                AuditLog::create([
                    'user_id' => $actor->id,
                    'aksi' => 'RETURN_HASIL_KERJA',
                    'target_kode' => $ticket->kode_tiket,
                    'ip_address' => request()?->ip() ?? '127.0.0.1',
                    'detail' => "Admin Bidang {$actor->nama} mengembalikan pengajuan tiket {$ticket->kode_tiket} ke teknisi: {$notes}",
                    'timestamp' => Carbon::now(),
                ]);

                return $ticket;
            }
        });
    }

    /**
     * 16. Pengecualian SLA Breach (Excused Breach) oleh Admin Pusat (Hak Veto Pusat)
     */
    public function excuseSlaBreach(Ticket $ticket, string $reason, ?string $evidencePath, User $actor): Ticket
    {
        if (!$actor->isAdminPusat()) {
            throw ValidationException::withMessages([
                'auth' => 'Pengecualian SLA hanya dapat diberikan oleh Admin Pusat.'
            ]);
        }

        return DB::transaction(function () use ($ticket, $reason, $evidencePath, $actor) {
            $ticket->sla_excused = true;
            $ticket->sla_excused_reason = $reason;
            $ticket->sla_excused_by = $actor->id;
            $ticket->sla_excused_at = Carbon::now();
            $ticket->sla_excused_evidence_path = $evidencePath;
            $ticket->save();

            $this->appendHistory($ticket, $ticket->status, $actor->nama . ' (Admin Pusat)', "Pengecualian SLA (Excused SLA Breach) disetujui: {$reason}");

            AuditLog::create([
                'user_id' => $actor->id,
                'aksi' => 'EXCUSE_SLA_BREACH',
                'target_kode' => $ticket->kode_tiket,
                'ip_address' => request()?->ip() ?? '127.0.0.1',
                'detail' => "Admin Pusat {$actor->nama} menyetujui pengecualian SLA untuk tiket {$ticket->kode_tiket}. Alasan: {$reason}",
                'timestamp' => Carbon::now(),
            ]);

            return $ticket;
        });
    }

    /**
     * 9. Private helper untuk append ke JSONB status_history
     */
    private function appendHistory(Ticket $ticket, string $status, string $actor, ?string $note): void
    {
        $history = $ticket->status_history ?? [];
        $history[] = [
            'status' => $status,
            'timestamp' => Carbon::now()->toIso8601String(),
            'actor' => $actor,
            'note' => $note ?? '',
        ];
        $ticket->status_history = $history;
        $ticket->save();
    }
}
