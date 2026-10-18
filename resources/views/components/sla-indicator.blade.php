@props(['ticket'])

@php
    $isBreach = $ticket->sla_breach;
    $badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-300';
    $label = '🟢 Aman';

    if ($isBreach) {
        $badgeClass = 'bg-rose-50 text-rose-700 border-rose-300 font-bold';
        $label = '🔴 Breach';
    } elseif (in_array($ticket->status, ['resolved', 'closed', 'force_closed'])) {
        $badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-300 font-semibold';
        $label = '🟢 Selesai (Aman)';
    } elseif ($ticket->sla_dimulai_at) {
        $totalHours = (float) ($ticket->sla_resolusi_jam ?: 336);
        $totalMinutes = max(1, $totalHours * 60);
        $deadline = $ticket->sla_deadline_at ? \Carbon\Carbon::parse($ticket->sla_deadline_at) : \Carbon\Carbon::parse($ticket->sla_dimulai_at)->addMinutes($totalMinutes);
        $minutesLeft = now()->diffInMinutes($deadline, false);

        if ($minutesLeft <= 0) {
            $badgeClass = 'bg-rose-50 text-rose-700 border-rose-300 font-bold animate-pulse';
            $label = '🔴 Breach';
        } else {
            $percentageLeft = ($minutesLeft / $totalMinutes) * 100;
            if ($percentageLeft < 20) {
                $badgeClass = 'bg-amber-50 text-amber-800 border-amber-300 font-bold';
                $label = '🟡 <20% Sisa';
            } else {
                $badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-300 font-semibold';
                $label = '🟢 Aman';
            }
        }
    } else {
        $badgeClass = 'bg-slate-50 text-slate-600 border-slate-300';
        $label = '⚪ Antrean Verifikasi';
    }
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center justify-center gap-1.5 px-3 py-1 rounded-full text-xs border shadow-xs whitespace-nowrap shrink-0 transition ' . $badgeClass]) }}>
    {{ $label }}
</span>
