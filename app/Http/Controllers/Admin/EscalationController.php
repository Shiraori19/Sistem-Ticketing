<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Services\TicketWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EscalationController extends Controller
{
    protected TicketWorkflowService $workflowService;

    public function __construct(TicketWorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    /**
     * Tampilkan daftar tiket berstatus eskalasi_pusat
     */
    public function index()
    {
        $tickets = Ticket::with(['category:id,nama_kategori', 'bidang:id,nama_bidang'])
            ->where('status', 'eskalasi_pusat')
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('admin.eskalasi.index', compact('tickets'));
    }

    /**
     * Penutupan paksa tiket eskalasi oleh Admin Pusat
     */
    public function forceClose(Request $request, int $id)
    {
        $request->validate([
            'force_close_notes' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'force_close_notes.required' => 'Alasan / pertimbangan penutupan paksa wajib diisi.',
            'force_close_notes.min' => 'Alasan penutupan paksa minimal 10 karakter.',
        ]);

        $ticket = Ticket::findOrFail($id);

        if ($ticket->status !== 'eskalasi_pusat') {
            return back()->with('error', "Force close hanya dapat dilakukan untuk tiket yang berstatus 'eskalasi_pusat'.");
        }

        $this->workflowService->forceClose(
            $ticket,
            $request->input('force_close_notes'),
            Auth::user()
        );

        return redirect()->route('admin.eskalasi.index')
            ->with('success', "Tiket {$ticket->kode_tiket} berhasil ditutup paksa dengan catatan audit.");
    }
}
