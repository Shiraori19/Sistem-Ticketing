<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bidang;
use App\Models\Category;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    /**
     * Tampilkan seluruh tiket dengan multi-filter
     */
    public function index(Request $request)
    {
        $query = Ticket::query()
            ->select([
                'id', 'is_parent', 'parent_id', 'kode_tiket', 'nama_pelapor', 'skpd', 'judul',
                'category_id', 'bidang_id', 'tlp_level', 'status',
                'sla_breach', 'sla_dimulai_at', 'sla_resolusi_jam', 'created_at'
            ])
            ->with(['category:id,nama_kategori', 'bidang:id,nama_bidang', 'parent:id,kode_tiket']);

        // Filter Status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter Bidang
        if ($request->filled('bidang_id')) {
            $query->where('bidang_id', $request->input('bidang_id'));
        }

        // Filter TLP Level
        if ($request->filled('tlp_level')) {
            $query->where('tlp_level', $request->input('tlp_level'));
        }

        // Filter SLA Breach
        if ($request->filled('sla_breach')) {
            $query->where('sla_breach', $request->boolean('sla_breach'));
        }

        // Keyword Search (kode_tiket, nama_pelapor, nip, judul, skpd)
        if ($request->filled('q')) {
            $q = trim($request->input('q'));
            $query->where(function ($sub) use ($q) {
                $sub->where('kode_tiket', 'ILIKE', "%{$q}%")
                    ->orWhere('nama_pelapor', 'ILIKE', "%{$q}%")
                    ->orWhere('nip_pelapor', 'ILIKE', "%{$q}%")
                    ->orWhere('judul', 'ILIKE', "%{$q}%")
                    ->orWhere('skpd', 'ILIKE', "%{$q}%");
            });
        }

        $tickets = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        $bidangList = \Illuminate\Support\Facades\Cache::remember('dropdown_bidang_list', 60, function () {
            return Bidang::select(['id', 'nama_bidang', 'kode'])->get();
        });

        $categories = \Illuminate\Support\Facades\Cache::remember('dropdown_category_list', 60, function () {
            return Category::select(['id', 'nama_kategori'])->get();
        });

        return view('admin.tiket.index', compact('tickets', 'bidangList', 'categories'));
    }

    /**
     * Detail tiket lengkap beserta timeline status_history
     */
    public function show(int $id)
    {
        $ticket = Ticket::with([
            'category',
            'bidang',
            'verifier',
            'assigner',
            'parent:id,kode_tiket,judul',
            'children:id,parent_id,kode_tiket,judul,skpd,nama_pelapor,status',
            'assignments',
            'files.accessRequests',
            'involvements.bidang'
        ])->findOrFail($id);
        $user = Auth::user();

        $isRed = strtoupper($ticket->tlp_level ?? '') === 'RED';

        // Otorisasi Hak Akses Tiket TLP RED (BUTIR 7 & KEPUTUSAN 3)
        if ($isRed && $user->isAdminBidang()) {
            $isPersandian = (bool) $user->bidang?->isPersandian();
            $isInvolved = \App\Models\TicketBidangInvolvement::where('ticket_id', $ticket->id)
                ->where('bidang_id', $user->bidang_id)
                ->exists();

            if (!$isPersandian && !$isInvolved) {
                abort(403, 'Akses Ditolak: Tiket TLP RED (Siber Kritis) hanya dapat diakses oleh Tim Tanggap Insiden CSIRT / Bidang Persandian.');
            }
        } elseif (!$isRed && $user->isAdminBidang()) {
            if ($ticket->bidang_id !== $user->bidang_id || in_array($ticket->status, ['pending_verification', 'pending_info', 'cancelled'])) {
                abort(403, 'Akses Ditolak: Tiket ini belum didisposisikan ke bidang Anda.');
            }
        }

        // Cek kerahasiaan bukti & berkas (Confidential Evidence & Vault, KEPUTUSAN BAWAAN 3)
        $isPersandian = (bool) $user->bidang?->isPersandian();
        $fileIds = $ticket->files ? $ticket->files->pluck('id')->all() : [];
        $hasApprovedAccess = !empty($fileIds) && \App\Models\TicketFileAccessRequest::whereIn('ticket_file_id', $fileIds)
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->where('expires_at', '>', now())
            ->exists();

        $canViewConfidential = $isPersandian 
            || ($user->isAdminBidang() && $user->bidang_id === $ticket->bidang_id && !$isRed)
            || ($user->isAdminPusat() && (!$isRed || $hasApprovedAccess));

        $hasPendingAccessRequest = !empty($fileIds) && \App\Models\TicketFileAccessRequest::whereIn('ticket_file_id', $fileIds)
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->exists();

        return view('admin.tiket.show', compact('ticket', 'canViewConfidential', 'hasApprovedAccess', 'hasPendingAccessRequest'));
    }

    /**
     * Jejak Audit Log Sistem (Memanfaatkan Index audit_log_aksi_timestamp_index)
     */
    public function auditLog(Request $request)
    {
        $aksi = (string) $request->input('aksi', '');
        $q = trim((string) $request->input('q', ''));

        $query = \App\Models\AuditLog::with(['user.role'])->orderBy('timestamp', 'desc');

        if (!empty($aksi)) {
            $query->where('aksi', $aksi);
        }

        if (!empty($q)) {
            $query->where(function ($sub) use ($q) {
                $sub->where('aksi', 'LIKE', "%{$q}%")
                    ->orWhere('target_kode', 'LIKE', "%{$q}%")
                    ->orWhere('ip_address', 'LIKE', "%{$q}%")
                    ->orWhere('detail', 'LIKE', "%{$q}%");
            });
        }

        $logs = $query->paginate(20)->withQueryString();

        return view('admin.audit.index', compact('logs'));
    }

    /**
     * Pengecualian SLA Breach oleh Admin Pusat (Excused SLA)
     */
    public function excuseSla(Request $request, int $id)
    {
        $request->validate([
            'alasan' => ['required', 'string', 'min:10'],
            'bukti' => ['nullable', 'string'],
        ], [
            'alasan.required' => 'Alasan pengecualian SLA breach wajib diisi.',
            'alasan.min' => 'Alasan minimal 10 karakter.',
        ]);

        $ticket = Ticket::findOrFail($id);
        $user = Auth::user();

        app(\App\Services\TicketWorkflowService::class)->excuseSlaBreach(
            $ticket,
            $request->input('alasan'),
            $request->input('bukti'),
            $user
        );

        return back()->with('success', "Pengecualian SLA Breach untuk tiket #{$ticket->kode_tiket} berhasil ditetapkan oleh Admin Pusat.");
    }
}
