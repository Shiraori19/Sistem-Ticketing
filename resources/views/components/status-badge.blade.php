@props(['status', 'context' => null])

@php
    $configs = [
        'pending_verification' => [
            'label' => $context === 'bidang' ? '⏳ Menunggu Verifikasi' : '⏳ Pending Verification',
            'class' => 'bg-amber-50 text-amber-800 border-amber-300 ring-amber-500/20',
        ],
        'pending_info' => [
            'label' => $context === 'bidang' ? '⏳ Menunggu Info Pelapor' : '⚠️ Pending Info (Pelapor)',
            'class' => 'bg-amber-100 text-amber-950 border-amber-400 ring-amber-500/30 font-bold',
        ],
        'verified' => [
            'label' => $context === 'bidang' ? '📥 Baru' : '⚡ Verified (Disposisi)',
            'class' => $context === 'bidang' ? 'bg-blue-50 text-blue-800 border-blue-300 ring-blue-500/20 font-bold' : 'bg-sky-50 text-sky-800 border-sky-300 ring-sky-500/20',
        ],
        'in_progress' => [
            'label' => '🔧 In Progress',
            'class' => 'bg-violet-50 text-violet-800 border-violet-300 ring-violet-500/20',
        ],
        'resolved' => [
            'label' => $context === 'bidang' ? '✅ Resolved' : '🔔 Resolved',
            'class' => 'bg-emerald-50 text-emerald-800 border-emerald-300 ring-emerald-500/20',
        ],
        'reopened' => [
            'label' => '🔄 Dibuka Kembali',
            'class' => 'bg-orange-50 text-orange-800 border-orange-300 ring-orange-500/20',
        ],
        'eskalasi_pusat' => [
            'label' => '⚖️ Eskalasi Admin Pusat',
            'class' => 'bg-red-50 text-red-800 border-red-300 ring-red-500/20',
        ],
        'force_closed' => [
            'label' => '🔒 Ditutup Paksa',
            'class' => 'bg-gray-100 text-gray-800 border-gray-300 ring-gray-500/20',
        ],
        'closed' => [
            'label' => '✅ Selesai',
            'class' => 'bg-green-50 text-green-800 border-green-300 ring-green-500/20',
        ],
        'cancelled' => [
            'label' => '❌ Dibatalkan',
            'class' => 'bg-rose-50 text-rose-800 border-rose-300 ring-rose-500/20',
        ],
    ];

    $current = $configs[$status] ?? [
        'label' => ucfirst(str_replace('_', ' ', $status)),
        'class' => 'bg-slate-100 text-slate-700 border-slate-300 ring-slate-500/20',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center justify-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border shadow-xs whitespace-nowrap shrink-0 transition ' . $current['class']]) }}>
    {{ $current['label'] }}
</span>