<?php

namespace App\Policies;

use App\Models\TicketAssignment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TicketAssignmentPolicy
{
    use HandlesAuthorization;

    /**
     * Helper validasi kepemilikan dan proteksi TLP RED
     * (Wajib mengembalikan 404 jika ditolak agar ID penugasan/tiket rahasia tidak bocor)
     */
    protected function checkOwnershipAndTlp(User $user, TicketAssignment $assignment): bool
    {
        // 1. Kepemilikan tugas: hanya teknisi yang bersangkutan
        if ($assignment->user_id !== $user->id) {
            abort(404, 'Penugasan tidak ditemukan.');
        }

        // 2. Proteksi TLP RED: hanya teknisi Bidang Persandian & CSIRT
        $ticket = $assignment->relationLoaded('ticket') ? $assignment->ticket : $assignment->ticket()->first();
        if ($ticket && strtoupper($ticket->tlp_level ?? '') === 'RED') {
            $isPersandian = $user->bidang && (
                strtoupper($user->bidang->kode ?? '') === 'PERSANDIAN' || 
                str_contains(strtoupper($user->bidang->nama_bidang ?? ''), 'PERSANDIAN')
            );

            if (!$isPersandian) {
                abort(404, 'Penugasan tidak ditemukan.');
            }
        }

        return true;
    }

    /**
     * Helper validasi status tiket aktif (tidak terminal: closed, force_closed, cancelled)
     */
    protected function isTicketActive(TicketAssignment $assignment): bool
    {
        $ticket = $assignment->relationLoaded('ticket') ? $assignment->ticket : $assignment->ticket()->first();
        return $ticket && !in_array($ticket->status, ['closed', 'force_closed', 'cancelled']);
    }

    /**
     * Otorisasi melihat rincian penugasan
     */
    public function view(User $user, TicketAssignment $assignment): bool
    {
        return $this->checkOwnershipAndTlp($user, $assignment);
    }

    /**
     * Otorisasi menerima tugas
     */
    public function accept(User $user, TicketAssignment $assignment): bool
    {
        return $this->checkOwnershipAndTlp($user, $assignment) 
            && $this->isTicketActive($assignment) 
            && $assignment->status === 'assigned';
    }

    /**
     * Otorisasi menolak tugas
     */
    public function decline(User $user, TicketAssignment $assignment): bool
    {
        return $this->checkOwnershipAndTlp($user, $assignment) 
            && $this->isTicketActive($assignment) 
            && $assignment->status === 'assigned';
    }

    /**
     * Otorisasi mencatat progres
     */
    public function addProgress(User $user, TicketAssignment $assignment): bool
    {
        return $this->checkOwnershipAndTlp($user, $assignment) 
            && $this->isTicketActive($assignment) 
            && $assignment->status === 'working';
    }

    /**
     * Otorisasi melaporkan hambatan
     */
    public function reportBlocker(User $user, TicketAssignment $assignment): bool
    {
        return $this->checkOwnershipAndTlp($user, $assignment) 
            && $this->isTicketActive($assignment) 
            && in_array($assignment->status, ['working', 'assigned']);
    }

    /**
     * Otorisasi mengajukan selesai
     */
    public function submit(User $user, TicketAssignment $assignment): bool
    {
        return $this->checkOwnershipAndTlp($user, $assignment) 
            && $this->isTicketActive($assignment) 
            && in_array($assignment->status, ['working', 'returned']);
    }
}
