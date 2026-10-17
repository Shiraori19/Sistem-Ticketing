<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\VerifyTicketRequest;
use App\Models\AuditLog;
use App\Models\Bidang;
use App\Models\Ticket;
use App\Services\TicketWorkflowService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerificationController extends Controller
{
    protected TicketWorkflowService $workflowService;

    public function __construct(TicketWorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    /**
     * Tampilkan antrean tiket yang menunggu verifikasi
     */
    public function index()
    {
        $tickets = Ticket::select([
                'id', 'parent_id', 'is_parent', 'kode_tiket', 'created_at', 'nama_pelapor', 'skpd', 'nip_pelapor',
                'no_whatsapp', 'judul', 'deskripsi', 'lampiran_pelapor', 'lampiran_tambahan',
                'catatan_kelengkapan', 'category_id', 'bidang_id', 'tlp_level', 'status', 'status_history',
                'sla_respon_jam', 'sla_respon_breach', 'sla_respon_deadline_at'
            ])
            ->with([
                'category:id,nama_kategori',
                'bidang:id,nama_bidang,kode',
                'parent:id,kode_tiket,judul'
            ])
            ->whereIn('status', ['pending_verification', 'pending_info'])
            ->orderBy('id', 'asc')
            ->paginate(15);

        $bidangList = \Illuminate\Support\Facades\Cache::remember('bidang_list_dropdown', 60, function () {
            return Bidang::select(['id', 'nama_bidang', 'kode'])->get();
        });

        // Ambil daftar Induk Tiket yang sedang aktif untuk pilihan penautan (Cache 60 detik)
        $activeParents = \Illuminate\Support\Facades\Cache::remember('active_parents_dropdown', 60, function () {
            return Ticket::where('is_parent', true)
                ->whereNotIn('status', ['resolved', 'closed', 'force_closed', 'cancelled'])
                ->select(['id', 'kode_tiket', 'judul', 'skpd'])
                ->orderBy('id', 'desc')
                ->get();
        });

        // Deteksi klaster insiden massal (tiket dengan kategori sama yang pending verifikasi >= 2, Cache 30 detik)
        $clusters = \Illuminate\Support\Facades\Cache::remember('verification_clusters_cache', 30, function () {
            return Ticket::whereIn('status', ['pending_verification', 'pending_info'])
                ->selectRaw('category_id, count(*) as total')
                ->groupBy('category_id')
                ->havingRaw('count(*) >= 2')
                ->with('category:id,nama_kategori')
                ->get();
        });

        return view('admin.verifikasi.index', compact('tickets', 'bidangList', 'activeParents', 'clusters'));
    }

    /**
     * Proses verifikasi & penetapan TLP serta disposisi (Mendukung Safe Parent-Child Linking)
     */
    public function verify(VerifyTicketRequest $request, int $id)
    {
        $ticket = Ticket::findOrFail($id);

        if (!in_array($ticket->status, ['pending_verification', 'pending_info'])) {
            return back()->with('error', "Tiket ini sudah tidak berada dalam status pending verifikasi.");
        }

        $tlp = $request->input('tlp_level');
        $persandian = Bidang::where('kode', 'PERSANDIAN')->orWhere('nama_bidang', 'LIKE', '%Persandian%')->first();
        $bidangId = $tlp === 'RED' ? ($persandian?->id ?? $request->input('bidang_id')) : $request->input('bidang_id');
        $notes = $request->input('notes');

        // Evaluasi opsi Parent-Child Linking
        $isParent = $request->boolean('is_parent');
        $parentId = $request->input('parent_id');

        if ($parentId && !$isParent) {
            $parentTicket = Ticket::find($parentId);
            if ($parentTicket) {
                $ticket->parent_id = $parentTicket->id;
                // Jika parent sudah memiliki bidang, otomatis gunakan bidang parent
                if ($parentTicket->bidang_id && !$bidangId) {
                    $bidangId = $parentTicket->bidang_id;
                }
            }
        } elseif ($isParent) {
            $ticket->is_parent = true;
        }

        $updated = $this->workflowService->verifyAndDispose(
            $ticket,
            $tlp,
            $bidangId ? (int) $bidangId : null,
            $notes,
            Auth::user()
        );

        // Bersihkan catatan kelengkapan setelah berhasil diverifikasi
        $updated->catatan_kelengkapan = null;
        $updated->save();

        $parentMsg = $updated->is_parent ? " (Ditetapkan sebagai INDUK TIKET)" : ($updated->parent_id ? " (Ditautkan ke Induk #{$updated->parent?->kode_tiket})" : "");
        $msg = $tlp === 'RED'
            ? "Tiket {$updated->kode_tiket}{$parentMsg} (TLP RED) berhasil diverifikasi dan ditugaskan ke Bidang Persandian & CSIRT."
            : "Tiket {$updated->kode_tiket}{$parentMsg} berhasil diverifikasi dan diteruskan ke Bidang terkait.";

        return redirect()->route('admin.verifikasi.index')->with('success', $msg);
    }

    /**
     * Permintaan kelengkapan berkas kepada pelapor
     */
    public function requestCompletion(Request $request, int $id)
    {
        $request->validate([
            'catatan' => ['required', 'string', 'min:5'],
        ], [
            'catatan.required' => 'Catatan kekurangan berkas / data wajib dicantumkan.',
        ]);

        $ticket = Ticket::findOrFail($id);
        $user = Auth::user();
        $catatan = $request->input('catatan');

        $ticket->catatan_kelengkapan = $catatan;

        // Append ke history
        $history = $ticket->status_history ?? [];
        $history[] = [
            'status' => 'pending_verification',
            'timestamp' => Carbon::now()->toIso8601String(),
            'actor' => $user->nama . ' (Admin Pusat)',
            'note' => 'Permintaan kelengkapan data: ' . $catatan,
        ];
        $ticket->status_history = $history;
        $ticket->save();

        // Audit log
        AuditLog::create([
            'user_id' => $user->id,
            'aksi' => 'MINTA_KELENGKAPAN_BERKAS',
            'target_kode' => $ticket->kode_tiket,
            'ip_address' => $request->ip(),
            'detail' => $catatan,
            'timestamp' => Carbon::now(),
        ]);

        return back()->with('success', "Permintaan kelengkapan data untuk tiket {$ticket->kode_tiket} telah dicatat dan dikirimkan.");
    }

    /**
     * Verifikasi & Penautan Massal ke Induk Tiket (Batch Parent-Child Linking)
     */
    public function batchLink(Request $request)
    {
        $request->validate([
            'ticket_ids' => ['required', 'array', 'min:2'],
            'ticket_ids.*' => ['integer', 'exists:tickets,id'],
            'parent_action' => ['required', 'in:new,existing'],
            'parent_id' => ['nullable', 'required_if:parent_action,existing', 'exists:tickets,id'],
            'tlp_level' => ['required', 'in:RED,AMBER,GREEN,CLEAR'],
            'bidang_id' => ['required_unless:tlp_level,RED', 'nullable', 'exists:bidang,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'ticket_ids.required' => 'Pilih minimal 2 tiket untuk digabungkan ke dalam Induk Tiket.',
            'ticket_ids.min' => 'Pilih minimal 2 tiket untuk digabungkan.',
            'parent_id.required_if' => 'Pilih Induk Tiket tujuan jika memilih opsi tautkan ke induk yang ada.',
        ]);

        $user = Auth::user();
        $tlp = $request->input('tlp_level');
        $persandian = Bidang::where('kode', 'PERSANDIAN')->orWhere('nama_bidang', 'LIKE', '%Persandian%')->first();
        $bidangId = $tlp === 'RED' ? ($persandian?->id ?? $request->input('bidang_id')) : $request->input('bidang_id');
        $notes = $request->input('notes') ?: 'Insiden massal terkoordinasi (penautan otomatis).';
        $ticketIds = array_values(array_unique($request->input('ticket_ids')));

        return \Illuminate\Support\Facades\DB::transaction(function () use ($request, $user, $tlp, $bidangId, $notes, $ticketIds) {
            if ($request->input('parent_action') === 'new') {
                // Tiket pertama dijadikan Induk
                $parentId = array_shift($ticketIds);
                $parent = Ticket::findOrFail($parentId);
                $parent->is_parent = true;
                $parent->save();

                $parent = $this->workflowService->verifyAndDispose(
                    $parent,
                    $tlp,
                    $bidangId ? (int) $bidangId : null,
                    $notes . ' [Ditetapkan sebagai INDUK TIKET]',
                    $user
                );
            } else {
                $parent = Ticket::findOrFail($request->input('parent_id'));
                if (in_array($parent->status, ['closed', 'force_closed', 'cancelled'])) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'parent_id' => "Induk Tiket #{$parent->kode_tiket} sudah ditutup ({$parent->status}) dan tidak dapat menerima penautan tiket baru."
                    ]);
                }
                $parent->is_parent = true;
                $parent->save();
            }

            // Tautkan sisa tiket ke Induk ini sekaligus
            $linkedCount = 0;
            $childTickets = Ticket::whereIn('id', $ticketIds)->get();
            foreach ($childTickets as $child) {
                if (in_array($child->status, ['pending_verification', 'pending_info'])) {
                    $child->parent_id = $parent->id;
                    $child->bidang_id = $parent->bidang_id ?: $bidangId;
                    $child->tlp_level = $parent->tlp_level ?: $tlp;
                    $child->status = 'verified';
                    $child->verified_by = $user->id;
                    $child->catatan_kelengkapan = null;

                    // FLOW-02: Warisi SLA dari Induk Tiket
                    $child->sla_dimulai_at = $parent->sla_dimulai_at ?: Carbon::now();
                    $child->sla_deadline_at = $parent->sla_deadline_at;
                    $child->sla_respon_deadline_at = $parent->sla_respon_deadline_at;
                    $child->sla_respon_jam = $parent->sla_respon_jam;
                    $child->sla_resolusi_jam = $parent->sla_resolusi_jam;

                    $history = $child->status_history ?? [];
                    $history[] = [
                        'status' => 'verified',
                        'timestamp' => Carbon::now()->toIso8601String(),
                        'actor' => $user->nama . ' (Admin Pusat)',
                        'note' => "Penautan massal ke Induk Tiket #{$parent->kode_tiket} (Insiden Massal). {$notes}",
                    ];
                    $child->status_history = $history;
                    $child->save();
                    $linkedCount++;
                }
            }

            AuditLog::create([
                'user_id' => $user->id,
                'aksi' => 'BATCH_PARENT_CHILD_LINKING',
                'target_kode' => $parent->kode_tiket,
                'ip_address' => request()?->ip() ?? '127.0.0.1',
                'detail' => "Penautan massal {$linkedCount} tiket anak ke Induk Tiket {$parent->kode_tiket}.",
                'timestamp' => Carbon::now(),
            ]);

            return redirect()->route('admin.verifikasi.index')
                ->with('success', "⚡ Sukses! {$linkedCount} tiket berhasil diverifikasi & ditautkan secara massal ke Induk Tiket #{$parent->kode_tiket}.");
        });
    }
}
