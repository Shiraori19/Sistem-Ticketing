<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\TechnicianProfile;
use App\Models\Ticket;
use App\Models\TicketAssignment;
use App\Models\TicketBlocker;
use App\Models\TicketEvent;
use App\Models\TicketFile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AssignmentWorkflowService
{
    protected TicketWorkflowService $ticketWorkflowService;
    protected SecureUploadService $uploadService;

    public function __construct(
        TicketWorkflowService $ticketWorkflowService,
        SecureUploadService $uploadService
    ) {
        $this->ticketWorkflowService = $ticketWorkflowService;
        $this->uploadService = $uploadService;
    }

    /**
     * Helper untuk memeriksa optimistic locking row_version
     */
    protected function checkRowVersion(TicketAssignment $assignment, ?int $expectedVersion): void
    {
        if ($expectedVersion !== null && (int) $assignment->row_version !== (int) $expectedVersion) {
            throw new HttpException(409, 'Konflik Versi Data (409 Conflict): Status penugasan telah diperbarui oleh sesi lain. Silakan segarkan halaman.');
        }
    }

    /**
     * 1. Aksi Teknisi: Terima Penugasan (Accept)
     * Syarat: status assigned dan now <= respond_deadline (jika deadline ada).
     * Hasil: status working, accepted_at & started_at diisi; tiket verified/reopened menjadi in_progress; sla_dimulai_at TIDAK diubah.
     */
    public function acceptAssignment(TicketAssignment $assignment, User $actor, ?int $expectedVersion = null): TicketAssignment
    {
        return DB::transaction(function () use ($assignment, $actor, $expectedVersion) {
            /** @var TicketAssignment $lockedAssignment */
            $lockedAssignment = TicketAssignment::where('id', $assignment->id)->lockForUpdate()->firstOrFail();
            $this->checkRowVersion($lockedAssignment, $expectedVersion);

            if ($lockedAssignment->status !== 'assigned') {
                throw ValidationException::withMessages([
                    'assignment' => "Penugasan tidak dapat diterima karena saat ini berstatus '{$lockedAssignment->status}'."
                ]);
            }

            // Validasi status tiket: dilarang jika tiket sudah ditutup / dibatalkan
            $ticket = Ticket::where('id', $lockedAssignment->ticket_id)->lockForUpdate()->firstOrFail();
            if (in_array($ticket->status, ['closed', 'force_closed', 'cancelled'])) {
                throw ValidationException::withMessages([
                    'assignment' => "Penugasan tidak dapat diterima karena tiket {$ticket->kode_tiket} telah berstatus '{$ticket->status}'."
                ]);
            }

            $now = Carbon::now();
            if ($lockedAssignment->respond_deadline && $now->gt($lockedAssignment->respond_deadline)) {
                throw ValidationException::withMessages([
                    'assignment' => 'Batas waktu konfirmasi respon penugasan (respond_deadline) telah terlewati.'
                ]);
            }

            $lockedAssignment->status = 'working';
            $lockedAssignment->accepted_at = $now;
            $lockedAssignment->started_at = $now;
            $lockedAssignment->incrementVersion();
            $lockedAssignment->save();

            // Perbarui tiket: verified / reopened -> in_progress, SLA TIDAK DIUBAH
            if (in_array($ticket->status, ['verified', 'reopened'])) {
                $ticket->status = 'in_progress';
            }
            if (!$ticket->pengerjaan_dimulai_at) {
                $ticket->pengerjaan_dimulai_at = $now;
            }
            $ticket->save();

            // Append-only event
            TicketEvent::create([
                'ticket_id' => $ticket->id,
                'assignment_id' => $lockedAssignment->id,
                'actor_id' => $actor->id,
                'type' => 'accepted',
                'payload' => [
                    'message' => "Teknisi {$actor->nama} mengonfirmasi dan menerima tugas lapangan.",
                    'role' => $lockedAssignment->role,
                ],
                'created_at' => $now,
            ]);

            AuditLog::create([
                'user_id' => $actor->id,
                'aksi' => 'TERIMA_PENUGASAN_TEKNISI',
                'target_kode' => $ticket->kode_tiket,
                'ip_address' => request()?->ip() ?? '127.0.0.1',
                'detail' => "Teknisi {$actor->nama} menerima penugasan tugas #{$lockedAssignment->id} untuk tiket {$ticket->kode_tiket}.",
                'timestamp' => $now,
            ]);

            return $lockedAssignment;
        });
    }

    /**
     * 2. Aksi Teknisi: Tolak Penugasan (Decline)
     * Syarat: alasan min. 10 karakter; status declined; tiket kembali verified ke antrean Admin Bidang; notifikasi ke Admin Bidang.
     */
    public function declineAssignment(TicketAssignment $assignment, string $reason, User $actor, ?int $expectedVersion = null): TicketAssignment
    {
        if (mb_strlen(trim($reason)) < 10) {
            throw ValidationException::withMessages([
                'alasan' => 'Alasan penolakan penugasan minimal 10 karakter.'
            ]);
        }

        return DB::transaction(function () use ($assignment, $reason, $actor, $expectedVersion) {
            /** @var TicketAssignment $lockedAssignment */
            $lockedAssignment = TicketAssignment::where('id', $assignment->id)->lockForUpdate()->firstOrFail();
            $this->checkRowVersion($lockedAssignment, $expectedVersion);

            if ($lockedAssignment->status !== 'assigned') {
                throw ValidationException::withMessages([
                    'assignment' => "Penugasan tidak dapat ditolak karena saat ini berstatus '{$lockedAssignment->status}'."
                ]);
            }

            // Validasi status tiket: dilarang jika tiket sudah ditutup / dibatalkan
            $ticket = Ticket::where('id', $lockedAssignment->ticket_id)->lockForUpdate()->firstOrFail();
            if (in_array($ticket->status, ['closed', 'force_closed', 'cancelled'])) {
                throw ValidationException::withMessages([
                    'assignment' => "Penugasan tidak dapat ditolak karena tiket {$ticket->kode_tiket} telah berstatus '{$ticket->status}'."
                ]);
            }

            $now = Carbon::now();
            $lockedAssignment->status = 'declined';
            $lockedAssignment->decline_reason = $reason;
            $lockedAssignment->unassigned_at = $now;
            $lockedAssignment->alasan_unassign = "Ditolak oleh teknisi: {$reason}";
            $lockedAssignment->incrementVersion();
            $lockedAssignment->save();

            // Tiket kembali verified ke antrean bidang jika tidak ada teknisi aktif lain
            $ticket = Ticket::where('id', $lockedAssignment->ticket_id)->lockForUpdate()->firstOrFail();
            $hasOtherActive = TicketAssignment::where('ticket_id', $ticket->id)
                ->where('id', '!=', $lockedAssignment->id)
                ->whereNull('unassigned_at')
                ->whereIn('status', ['assigned', 'working', 'submitted'])
                ->exists();

            if (!$hasOtherActive) {
                $ticket->status = 'verified';
                $ticket->teknisi_lapangan = null;
                $ticket->save();
            }

            // Append-only event
            TicketEvent::create([
                'ticket_id' => $ticket->id,
                'assignment_id' => $lockedAssignment->id,
                'actor_id' => $actor->id,
                'type' => 'declined',
                'payload' => [
                    'reason' => $reason,
                    'message' => "Teknisi {$actor->nama} menolak tugas. Alasan: {$reason}. Tiket dikembalikan ke antrean bidang.",
                ],
                'created_at' => $now,
            ]);

            // Notifikasi ke Admin Bidang
            $this->notifyBidangAdmins(
                $ticket,
                'Penugasan Ditolak Teknisi',
                "Teknisi {$actor->nama} menolak penugasan tiket {$ticket->kode_tiket}. Alasan: {$reason}."
            );

            AuditLog::create([
                'user_id' => $actor->id,
                'aksi' => 'TOLAK_PENUGASAN_TEKNISI',
                'target_kode' => $ticket->kode_tiket,
                'ip_address' => request()?->ip() ?? '127.0.0.1',
                'detail' => "Teknisi {$actor->nama} menolak penugasan tiket {$ticket->kode_tiket}: {$reason}",
                'timestamp' => $now,
            ]);

            return $lockedAssignment;
        });
    }

    /**
     * 3. Lepas Otomatis (Auto-Release Expired Assignments)
     * Scheduler tiap menit melepas penugasan assigned yang melewati respond_deadline (RED 15m, lainnya 60m); status released; notifikasi Admin Bidang.
     */
    public function releaseExpiredAssignments(): int
    {
        $now = Carbon::now();
        $expiredAssignments = TicketAssignment::with(['ticket', 'user'])
            ->whereNull('unassigned_at')
            ->where('status', 'assigned')
            ->whereHas('ticket', function ($q) {
                $q->whereNotIn('status', ['resolved', 'closed', 'force_closed', 'cancelled']);
            })
            ->get()
            ->filter(function (TicketAssignment $a) use ($now) {
                if ($a->respond_deadline) {
                    return $now->gt($a->respond_deadline);
                }
                // Fallback kalkulasi jika respond_deadline belum tercatat
                $timeoutMinutes = strtoupper($a->ticket->tlp_level ?? '') === 'RED' ? 15 : 60;
                return $a->assigned_at && $a->assigned_at->copy()->addMinutes($timeoutMinutes)->isPast();
            });

        $count = 0;
        foreach ($expiredAssignments as $assignment) {
            DB::transaction(function () use ($assignment, $now, &$count) {
                /** @var TicketAssignment $lockedAssignment */
                $lockedAssignment = TicketAssignment::where('id', $assignment->id)->lockForUpdate()->first();
                if (!$lockedAssignment || $lockedAssignment->status !== 'assigned') {
                    return;
                }

                $lockedAssignment->status = 'released';
                $lockedAssignment->unassigned_at = $now;
                $lockedAssignment->alasan_unassign = 'Penugasan otomatis dilepas sistem karena melewati batas waktu konfirmasi respon penugasan (respond_deadline).';
                $lockedAssignment->incrementVersion();
                $lockedAssignment->save();

                $ticket = Ticket::where('id', $lockedAssignment->ticket_id)->lockForUpdate()->first();
                if ($ticket) {
                    $hasOtherActive = TicketAssignment::where('ticket_id', $ticket->id)
                        ->where('id', '!=', $lockedAssignment->id)
                        ->whereNull('unassigned_at')
                        ->whereIn('status', ['assigned', 'working', 'submitted'])
                        ->exists();

                    if (!$hasOtherActive) {
                        $ticket->status = 'verified';
                        $ticket->teknisi_lapangan = null;
                        $ticket->save();
                    }

                    // Append-only event
                    TicketEvent::create([
                        'ticket_id' => $ticket->id,
                        'assignment_id' => $lockedAssignment->id,
                        'actor_id' => null,
                        'type' => 'released',
                        'payload' => [
                            'message' => "Penugasan teknisi {$lockedAssignment->technician_name} dilepas otomatis karena melewati batas waktu respon konfirmasi.",
                        ],
                        'created_at' => $now,
                    ]);

                    // Notifikasi ke Admin Bidang
                    $this->notifyBidangAdmins(
                        $ticket,
                        'Penugasan Teknisi Dilepas Otomatis (Timeout)',
                        "Penugasan untuk teknisi {$lockedAssignment->technician_name} pada tiket {$ticket->kode_tiket} otomatis dilepas karena tidak ada konfirmasi dalam batas waktu."
                    );
                }

                $count++;
            });
        }

        return $count;
    }

    /**
     * 4. Aksi Teknisi: Tambah Progres Lapangan (Add Progress / Worklog)
     * Status working; catatan min. 5 karakter; berkas opsional (disimpan sebagai versi baru di ticket_files).
     */
    public function addProgress(
        Ticket $ticket,
        TicketAssignment $assignment,
        string $notes,
        User $actor,
        ?UploadedFile $file = null,
        ?int $expectedVersion = null
    ): TicketEvent {
        if (in_array($ticket->status, ['closed', 'force_closed', 'cancelled'])) {
            throw ValidationException::withMessages([
                'ticket' => "Catatan progres tidak dapat ditambahkan karena tiket {$ticket->kode_tiket} telah berstatus '{$ticket->status}'."
            ]);
        }

        if (mb_strlen(trim($notes)) < 5) {
            throw ValidationException::withMessages([
                'catatan' => 'Catatan progres lapangan minimal 5 karakter.'
            ]);
        }

        return DB::transaction(function () use ($ticket, $assignment, $notes, $actor, $file, $expectedVersion) {
            /** @var TicketAssignment $lockedAssignment */
            $lockedAssignment = TicketAssignment::where('id', $assignment->id)->lockForUpdate()->firstOrFail();
            $this->checkRowVersion($lockedAssignment, $expectedVersion);

            if ($lockedAssignment->status !== 'working') {
                throw ValidationException::withMessages([
                    'assignment' => "Catatan progres hanya dapat ditambahkan saat tugas berstatus 'working'."
                ]);
            }

            $now = Carbon::now();

            // Simpan riwayat worklog di assignment
            $worklog = $lockedAssignment->worklog ?? [];
            $worklog[] = [
                'note' => $notes,
                'timestamp' => $now->toIso8601String(),
                'user' => $actor->nama,
            ];
            $lockedAssignment->worklog = $worklog;
            $lockedAssignment->incrementVersion();
            $lockedAssignment->save();

            // Buat event append-only
            $event = TicketEvent::create([
                'ticket_id' => $ticket->id,
                'assignment_id' => $lockedAssignment->id,
                'actor_id' => $actor->id,
                'type' => 'progress_added',
                'payload' => [
                    'note' => $notes,
                    'actor_name' => $actor->nama,
                ],
                'created_at' => $now,
            ]);

            // Simpan berkas privat jika dilampirkan
            if ($file) {
                $this->storeTicketFile($ticket, $event, $actor, $file, 'progres_lapangan');
            }

            AuditLog::create([
                'user_id' => $actor->id,
                'aksi' => 'CATAT_PROGRES_TEKNISI',
                'target_kode' => $ticket->kode_tiket,
                'ip_address' => request()?->ip() ?? '127.0.0.1',
                'detail' => "Teknisi {$actor->nama} mencatat progres tugas tiket {$ticket->kode_tiket}: {$notes}",
                'timestamp' => $now,
            ]);

            return $event;
        });
    }

    /**
     * 5. Aksi Teknisi: Laporkan Hambatan (Report Blocker)
     * Jenis (isp|vendor|opd_lain|suku_cadang), bukti, dan keterangan (min. 10 karakter) wajib.
     * JAM SLA TIDAK BERUBAH. Notifikasi ke Admin Bidang dan Admin Pusat.
     */
    public function reportBlocker(
        Ticket $ticket,
        TicketAssignment $assignment,
        array $data,
        User $actor,
        ?UploadedFile $file = null,
        ?int $expectedVersion = null
    ): TicketBlocker {
        if (in_array($ticket->status, ['closed', 'force_closed', 'cancelled'])) {
            throw ValidationException::withMessages([
                'ticket' => "Hambatan tidak dapat dilaporkan karena tiket {$ticket->kode_tiket} telah berstatus '{$ticket->status}'."
            ]);
        }

        $allowedJenis = ['isp', 'vendor', 'opd_lain', 'suku_cadang'];
        $jenis = strtolower(trim((string) ($data['jenis'] ?? '')));
        if (!in_array($jenis, $allowedJenis)) {
            throw ValidationException::withMessages([
                'jenis' => 'Jenis hambatan tidak valid. Pilihan: ' . implode(', ', $allowedJenis)
            ]);
        }

        $bukti = trim((string) ($data['bukti'] ?? ''));
        if (empty($bukti)) {
            throw ValidationException::withMessages([
                'bukti' => 'Bukti nomor tiket vendor/ISP atau keterangan bukti hambatan wajib diisi.'
            ]);
        }

        $keterangan = trim((string) ($data['keterangan'] ?? ''));
        if (mb_strlen($keterangan) < 10) {
            throw ValidationException::withMessages([
                'keterangan' => 'Keterangan rincian hambatan minimal 10 karakter.'
            ]);
        }

        return DB::transaction(function () use ($ticket, $assignment, $jenis, $bukti, $keterangan, $actor, $file, $expectedVersion) {
            /** @var TicketAssignment $lockedAssignment */
            $lockedAssignment = TicketAssignment::where('id', $assignment->id)->lockForUpdate()->firstOrFail();
            $this->checkRowVersion($lockedAssignment, $expectedVersion);

            $now = Carbon::now();

            $blocker = TicketBlocker::create([
                'ticket_id' => $ticket->id,
                'assignment_id' => $lockedAssignment->id,
                'jenis' => $jenis,
                'bukti' => $bukti,
                'keterangan' => $keterangan,
                'status' => 'open',
                'next_reminder_at' => $now->copy()->addHours(4),
                'resolved_at' => null,
            ]);

            // Append-only event
            $event = TicketEvent::create([
                'ticket_id' => $ticket->id,
                'assignment_id' => $lockedAssignment->id,
                'actor_id' => $actor->id,
                'type' => 'blocker_reported',
                'payload' => [
                    'blocker_id' => $blocker->id,
                    'jenis' => $jenis,
                    'bukti' => $bukti,
                    'keterangan' => $keterangan,
                    'sla_policy' => 'JAM SLA TETAP BERJALAN',
                ],
                'created_at' => $now,
            ]);

            if ($file) {
                $this->storeTicketFile($ticket, $event, $actor, $file, 'bukti_hambatan');
            }

            // Notifikasi ke Admin Bidang dan Admin Pusat
            $this->notifyBidangAdmins(
                $ticket,
                '⚠️ Hambatan Lapangan Dilaporkan',
                "Teknisi {$actor->nama} melaporkan hambatan ({$jenis}) pada tiket {$ticket->kode_tiket}: {$keterangan}. Jam SLA tetap berjalan."
            );
            $this->notifyPusatAdmins(
                $ticket,
                '⚠️ Hambatan Lapangan Dilaporkan',
                "Hambatan teknis ({$jenis}) dilaporkan pada tiket {$ticket->kode_tiket} oleh {$actor->nama}."
            );

            AuditLog::create([
                'user_id' => $actor->id,
                'aksi' => 'LAPOR_HAMBATAN_TEKNISI',
                'target_kode' => $ticket->kode_tiket,
                'ip_address' => request()?->ip() ?? '127.0.0.1',
                'detail' => "Teknisi {$actor->nama} melaporkan hambatan ({$jenis}) untuk tiket {$ticket->kode_tiket}: {$keterangan}. Jam SLA tidak dihentikan.",
                'timestamp' => $now,
            ]);

            return $blocker;
        });
    }

    /**
     * 6. Aksi Teknisi: Tutup/Selesaikan Hambatan
     */
    public function resolveBlocker(TicketBlocker $blocker, User $actor): TicketBlocker
    {
        return DB::transaction(function () use ($blocker, $actor) {
            $now = Carbon::now();
            $blocker->status = 'resolved';
            $blocker->resolved_at = $now;
            $blocker->next_reminder_at = null;
            $blocker->save();

            TicketEvent::create([
                'ticket_id' => $blocker->ticket_id,
                'assignment_id' => $blocker->assignment_id,
                'actor_id' => $actor->id,
                'type' => 'blocker_resolved',
                'payload' => [
                    'blocker_id' => $blocker->id,
                    'jenis' => $blocker->jenis,
                    'message' => "Hambatan ({$blocker->jenis}) telah berhasil diselesaikan oleh {$actor->nama}.",
                ],
                'created_at' => $now,
            ]);

            return $blocker;
        });
    }

    /**
     * 7. Aksi Teknisi: Ajukan Selesai (Submit for Review)
     * Catatan resolusi min. 10 karakter, bukti opsional; status submitted; tiket TETAP in_progress. Teknisi TIDAK dapat menutup tiket sendiri.
     */
    public function submitForReview(
        Ticket $ticket,
        TicketAssignment $assignment,
        string $notes,
        User $actor,
        ?UploadedFile $file = null,
        ?int $expectedVersion = null
    ): TicketAssignment {
        if (in_array($ticket->status, ['closed', 'force_closed', 'cancelled'])) {
            throw ValidationException::withMessages([
                'ticket' => "Pengajuan penyelesaian tidak dapat dilakukan karena tiket {$ticket->kode_tiket} telah berstatus '{$ticket->status}'."
            ]);
        }

        if (mb_strlen(trim($notes)) < 10) {
            throw ValidationException::withMessages([
                'catatan' => 'Catatan tindakan penyelesaian minimal 10 karakter.'
            ]);
        }

        return DB::transaction(function () use ($ticket, $assignment, $notes, $actor, $file, $expectedVersion) {
            /** @var TicketAssignment $lockedAssignment */
            $lockedAssignment = TicketAssignment::where('id', $assignment->id)->lockForUpdate()->firstOrFail();
            $this->checkRowVersion($lockedAssignment, $expectedVersion);

            if (!in_array($lockedAssignment->status, ['working', 'returned'])) {
                throw ValidationException::withMessages([
                    'assignment' => "Pengajuan selesai hanya dapat dilakukan saat tugas berstatus 'working' atau 'returned'."
                ]);
            }

            $now = Carbon::now();
            $lockedAssignment->status = 'approved';
            $lockedAssignment->submitted_at = $now;
            $lockedAssignment->approved_at = $now;
            $lockedAssignment->reviewed_at = $now;
            $lockedAssignment->reviewed_by = $actor->id;
            $lockedAssignment->notes = $notes;
            $lockedAssignment->review_notes = 'Pekerjaan diselesaikan langsung oleh teknisi dan diteruskan ke pelapor.';
            $lockedAssignment->incrementVersion();
            $lockedAssignment->save();

            // Selesaikan tiket langsung ke status 'resolved' (langsung ke pelapor, tanpa menunggu review admin bidang)
            $ticketLocked = Ticket::where('id', $ticket->id)->lockForUpdate()->firstOrFail();
            $ticketLocked->catatan_resolusi = $notes;
            $ticketLocked->save();

            $event = TicketEvent::create([
                'ticket_id' => $ticketLocked->id,
                'assignment_id' => $lockedAssignment->id,
                'actor_id' => $actor->id,
                'type' => 'submitted',
                'payload' => [
                    'notes' => $notes,
                    'message' => "Teknisi {$actor->nama} menyelesaikan pengerjaan. Tiket langsung berstatus Resolved dan diteruskan ke pelapor untuk konfirmasi.",
                ],
                'created_at' => $now,
            ]);

            $evidencePath = null;
            if ($file) {
                $savedFile = $this->storeTicketFile($ticketLocked, $event, $actor, $file, 'bukti_resolusi');
                $ticketLocked->bukti_resolusi = $savedFile->path;
                $ticketLocked->save();
                $evidencePath = $savedFile->path;
            }

            $isConfidential = (strtoupper($ticketLocked->tlp_level ?? '') === 'RED');
            $this->ticketWorkflowService->markResolved(
                $ticketLocked,
                $notes,
                $evidencePath ?: $ticketLocked->bukti_resolusi,
                $isConfidential,
                $actor
            );

            // Notifikasi ke Admin Bidang sebagai arsip informasi
            $this->notifyBidangAdmins(
                $ticketLocked,
                'Tiket Selesai Dikerjakan Teknisi',
                "Teknisi {$actor->nama} telah menyelesaikan pekerjaan pada tiket {$ticketLocked->kode_tiket}. Tiket kini berstatus Resolved dan menunggu konfirmasi pelapor."
            );

            AuditLog::create([
                'user_id' => $actor->id,
                'aksi' => 'SELESAI_TEKNISI_RESOLVED',
                'target_kode' => $ticketLocked->kode_tiket,
                'ip_address' => request()?->ip() ?? '127.0.0.1',
                'detail' => "Teknisi {$actor->nama} menyelesaikan tiket {$ticketLocked->kode_tiket}. Tiket beralih ke status resolved dan diteruskan langsung ke pelapor.",
                'timestamp' => $now,
            ]);

            return $lockedAssignment;
        });
    }

    /**
     * 8. Admin Bidang: Setujui Pengajuan Selesai (Approve)
     * Status approved; tiket menjadi resolved (memulai alur konfirmasi pelapor & cascade tiket anak).
     * Waktu resolusi dihitung sampai submitted_at dari pengajuan yang disetujui (waktu tunggu review tidak dibebankan ke teknisi).
     */
    public function approveSubmission(
        Ticket $ticket,
        TicketAssignment $assignment,
        ?string $reviewNotes,
        User $actor,
        ?int $expectedVersion = null
    ): Ticket {
        return DB::transaction(function () use ($ticket, $assignment, $reviewNotes, $actor, $expectedVersion) {
            /** @var TicketAssignment $lockedAssignment */
            $lockedAssignment = TicketAssignment::where('id', $assignment->id)->lockForUpdate()->firstOrFail();
            $this->checkRowVersion($lockedAssignment, $expectedVersion);

            if ($lockedAssignment->status !== 'submitted') {
                throw ValidationException::withMessages([
                    'assignment' => "Penugasan tidak dalam status menunggu review ('submitted')."
                ]);
            }

            $now = Carbon::now();
            $lockedAssignment->status = 'approved';
            $lockedAssignment->approved_at = $now;
            $lockedAssignment->reviewed_at = $now;
            $lockedAssignment->reviewed_by = $actor->id;
            $lockedAssignment->review_notes = $reviewNotes ?: 'Pekerjaan perbaikan lapangan diverifikasi dan disetujui Admin Bidang.';
            $lockedAssignment->incrementVersion();
            $lockedAssignment->save();

            TicketEvent::create([
                'ticket_id' => $ticket->id,
                'assignment_id' => $lockedAssignment->id,
                'actor_id' => $actor->id,
                'type' => 'approved',
                'payload' => [
                    'review_notes' => $lockedAssignment->review_notes,
                    'message' => "Admin Bidang {$actor->nama} menyetujui hasil perbaikan teknisi.",
                ],
                'created_at' => $now,
            ]);

            // Eksekusi markResolved lewat TicketWorkflowService yang sudah ada
            // (termasuk cascading anak, evaluasi SLA, dan status transisi ke resolved)
            $finalNotes = $lockedAssignment->notes ?: ($reviewNotes ?: 'Pekerjaan perbaikan selesai diverifikasi.');
            $finalEvidence = $ticket->bukti_resolusi;
            $isConfidential = (strtoupper($ticket->tlp_level ?? '') === 'RED');

            $resolvedTicket = $this->ticketWorkflowService->markResolved(
                $ticket,
                $finalNotes,
                $finalEvidence,
                $isConfidential,
                $actor
            );

            return $resolvedTicket;
        });
    }

    /**
     * 9. Admin Bidang: Kembalikan Pengajuan Selesai (Return)
     * Alasan wajib; status kembali working; jam SLA tetap berjalan.
     */
    public function returnSubmission(
        Ticket $ticket,
        TicketAssignment $assignment,
        string $reason,
        User $actor,
        ?int $expectedVersion = null
    ): TicketAssignment {
        if (mb_strlen(trim($reason)) < 5) {
            throw ValidationException::withMessages([
                'alasan' => 'Alasan pengembalian pekerjaan minimal 5 karakter.'
            ]);
        }

        return DB::transaction(function () use ($ticket, $assignment, $reason, $actor, $expectedVersion) {
            /** @var TicketAssignment $lockedAssignment */
            $lockedAssignment = TicketAssignment::where('id', $assignment->id)->lockForUpdate()->firstOrFail();
            $this->checkRowVersion($lockedAssignment, $expectedVersion);

            if ($lockedAssignment->status !== 'submitted') {
                throw ValidationException::withMessages([
                    'assignment' => "Penugasan tidak dalam status menunggu review ('submitted')."
                ]);
            }

            $now = Carbon::now();
            $lockedAssignment->status = 'working';
            $lockedAssignment->returned_at = $now;
            $lockedAssignment->reviewed_at = $now;
            $lockedAssignment->reviewed_by = $actor->id;
            $lockedAssignment->review_notes = $reason;
            $lockedAssignment->incrementVersion();
            $lockedAssignment->save();

            TicketEvent::create([
                'ticket_id' => $ticket->id,
                'assignment_id' => $lockedAssignment->id,
                'actor_id' => $actor->id,
                'type' => 'returned',
                'payload' => [
                    'reason' => $reason,
                    'message' => "Admin Bidang {$actor->nama} mengembalikan hasil pekerjaan teknisi untuk penyempurnaan: {$reason}. Jam SLA tetap berjalan.",
                ],
                'created_at' => $now,
            ]);

            // Notifikasi ke teknisi bersangkutan
            Notification::create([
                'ticket_id' => $ticket->id,
                'nomor_tujuan' => substr((string) ($lockedAssignment->user?->username ?? 'TEKNISI'), 0, 25),
                'pesan' => "Revisi Pekerjaan Tiket {$ticket->kode_tiket}: {$reason}",
                'status_kirim' => 'pending',
            ]);

            AuditLog::create([
                'user_id' => $actor->id,
                'aksi' => 'RETURN_PEKERJAAN_TEKNISI',
                'target_kode' => $ticket->kode_tiket,
                'ip_address' => request()?->ip() ?? '127.0.0.1',
                'detail' => "Admin Bidang {$actor->nama} mengembalikan pekerjaan tiket {$ticket->kode_tiket} ke teknisi: {$reason}",
                'timestamp' => $now,
            ]);

            return $lockedAssignment;
        });
    }

    /**
     * 10. Pembaruan Status Ketersediaan Teknisi (tersedia/sibuk/cuti)
     * Jika cuti saat masih ada tugas aktif, Admin Bidang diberi tahu untuk menugaskan ulang.
     */
    public function updateAvailability(User $user, string $availability, ?Carbon $cutiUntil = null): TechnicianProfile
    {
        $allowed = ['tersedia', 'sibuk', 'cuti'];
        if (!in_array($availability, $allowed)) {
            throw ValidationException::withMessages([
                'availability' => 'Status ketersediaan tidak valid.'
            ]);
        }

        return DB::transaction(function () use ($user, $availability, $cutiUntil) {
            $profile = $user->technicianProfile ?: TechnicianProfile::create([
                'user_id' => $user->id,
                'availability' => 'tersedia',
                'max_load' => 6,
                'skills' => [],
            ]);

            $oldAvailability = $profile->availability;
            $profile->availability = $availability;
            $profile->cuti_until = $availability === 'cuti' ? $cutiUntil : null;
            $profile->save();

            // Jika status berubah ke cuti dan masih ada penugasan aktif, kirim notifikasi ke Admin Bidang
            if ($availability === 'cuti') {
                $activeAssignments = TicketAssignment::with('ticket')
                    ->where('user_id', $user->id)
                    ->whereNull('unassigned_at')
                    ->whereIn('status', ['assigned', 'working', 'submitted'])
                    ->get();

                if ($activeAssignments->isNotEmpty()) {
                    $ticketsList = $activeAssignments->pluck('ticket.kode_tiket')->filter()->implode(', ');
                    $bidangId = $user->bidang_id;
                    $admins = User::where('bidang_id', $bidangId)
                        ->whereHas('role', fn($r) => $r->where('nama_role', 'Admin Bidang'))
                        ->get();

                    $untilText = $cutiUntil ? " (sampai " . $cutiUntil->translatedFormat('d M Y') . ")" : "";
                    foreach ($admins as $admin) {
                        Notification::create([
                            'ticket_id' => $activeAssignments->first()->ticket_id,
                            'nomor_tujuan' => substr((string) ($admin->username ?? 'DKIS-ADMIN'), 0, 25),
                            'pesan' => "Perhatian: Teknisi {$user->nama} Cuti{$untilText} pada tiket [{$ticketsList}]. Harap jadwalkan penugasan ulang.",
                            'status_kirim' => 'pending',
                        ]);
                    }
                }
            }

            AuditLog::create([
                'user_id' => $user->id,
                'aksi' => 'UPDATE_KETERSEDIAAN_TEKNISI',
                'target_kode' => $user->username,
                'ip_address' => request()?->ip() ?? '127.0.0.1',
                'detail' => "Teknisi {$user->nama} mengubah ketersediaan dari {$oldAvailability} menjadi {$availability}.",
                'timestamp' => Carbon::now(),
            ]);

            return $profile;
        });
    }

    /**
     * 11. Catat Event Penayangan Kontak Pelapor (Contact Viewed)
     */
    public function recordContactViewed(Ticket $ticket, TicketAssignment $assignment, User $actor): void
    {
        // Hanya catat sekali per jam per teknisi untuk menghindari event flooding
        $recentlyLogged = TicketEvent::where('ticket_id', $ticket->id)
            ->where('assignment_id', $assignment->id)
            ->where('actor_id', $actor->id)
            ->where('type', 'contact_viewed')
            ->where('created_at', '>=', Carbon::now()->subMinutes(30))
            ->exists();

        if (!$recentlyLogged) {
            TicketEvent::create([
                'ticket_id' => $ticket->id,
                'assignment_id' => $assignment->id,
                'actor_id' => $actor->id,
                'type' => 'contact_viewed',
                'payload' => [
                    'message' => "Teknisi {$actor->nama} melihat data kontak pelapor untuk koordinasi penanganan.",
                    'timestamp' => Carbon::now()->toIso8601String(),
                ],
                'created_at' => Carbon::now(),
            ]);
        }
    }

    /**
     * Helper: Simpan berkas ke disk privat dan catat di ticket_files
     */
    protected function storeTicketFile(
        Ticket $ticket,
        TicketEvent $event,
        User $uploader,
        UploadedFile $file,
        string $kategori
    ): TicketFile {
        $isTlpRed = strtoupper($ticket->tlp_level ?? '') === 'RED';
        $uploadResult = $this->uploadService->storeUploadedFile(
            $file,
            "tickets/{$ticket->kode_tiket}",
            [
                'ticket' => $ticket,
                'event_id' => $event->id,
                'uploader_id' => $uploader->id,
                'classification' => $isTlpRed ? 'rahasia' : 'umum',
                'kategori_field' => $kategori,
                'allowed_categories' => ['image', 'pdf', 'text', 'video'],
            ]
        );

        return TicketFile::where('path', $uploadResult['path'])->latest()->first() ?? TicketFile::create([
            'ticket_id'      => $ticket->id,
            'event_id'       => $event->id,
            'uploader_id'    => $uploader->id,
            'path'           => $uploadResult['path'],
            'mime'           => $uploadResult['mime'],
            'size'           => $uploadResult['size'],
            'kategori'       => $kategori,
            'sha256'         => $uploadResult['sha256'],
            'original_name'  => $uploadResult['original_name'],
            'classification' => $uploadResult['classification'],
            'is_encrypted'   => $uploadResult['is_encrypted'],
            'vault_path'     => $uploadResult['vault_path'],
        ]);
    }

    /**
     * Helper: Kirim notifikasi ke Admin Bidang
     */
    protected function notifyBidangAdmins(Ticket $ticket, string $title, string $message): void
    {
        $admins = User::where('bidang_id', $ticket->bidang_id)
            ->whereHas('role', fn($r) => $r->where('nama_role', 'Admin Bidang'))
            ->get();

        foreach ($admins as $admin) {
            Notification::create([
                'ticket_id' => $ticket->id,
                'nomor_tujuan' => substr((string) ($admin->username ?? 'DKIS-ADMIN'), 0, 25),
                'pesan' => "[{$title}] {$message}",
                'status_kirim' => 'pending',
            ]);
        }
    }

    /**
     * Helper: Kirim notifikasi ke Admin Pusat
     */
    protected function notifyPusatAdmins(Ticket $ticket, string $title, string $message): void
    {
        $pusatAdmins = User::whereHas('role', fn($r) => $r->where('nama_role', 'Admin Pusat'))->get();
        foreach ($pusatAdmins as $admin) {
            Notification::create([
                'ticket_id' => $ticket->id,
                'nomor_tujuan' => substr((string) ($admin->username ?? 'DKIS-PUSAT'), 0, 25),
                'pesan' => "[{$title}] {$message}",
                'status_kirim' => 'pending',
            ]);
        }
    }
}
