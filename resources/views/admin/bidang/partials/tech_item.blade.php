@php
    $isCurLead = $activeLeadId === $tech['user_id'];
    $isCurMember = in_array($tech['user_id'], $activeMemberIds);
    $onLeave = $tech['is_on_leave'];
    $isOver = $tech['is_full'];
    $loadColorCls = $tech['load_color'] === 'red' ? 'bg-rose-500' : ($tech['load_color'] === 'amber' ? 'bg-amber-500' : 'bg-emerald-500');
    $loadTextCls = $tech['load_color'] === 'red' ? 'text-rose-700' : ($tech['load_color'] === 'amber' ? 'text-amber-700' : 'text-emerald-700');
@endphp

<div class="p-3 bg-white rounded-xl border {{ $isCurLead ? 'border-amber-400 bg-amber-50/20 ring-1 ring-amber-300' : 'border-slate-200' }} {{ $onLeave ? 'opacity-60 bg-slate-50' : '' }} transition hover:shadow-2xs text-xs">
    <!-- Top Row: Radio Lead & Checkbox Pendamping + Name -->
    <div class="flex items-start justify-between gap-2">
        <div class="flex items-start gap-2 flex-1">
            <!-- Radio Lead PIC -->
            <div class="flex items-center gap-1.5 pt-0.5" title="Pilih sebagai Koordinator Lapangan (Lead PIC)">
                <input type="radio" name="lead_user_id" id="lead-{{ $t->id }}-{{ $tech['user_id'] }}"
                    value="{{ $tech['user_id'] }}"
                    {{ $isCurLead ? 'checked' : '' }}
                    {{ $onLeave ? 'disabled' : '' }}
                    onchange="handleLeadChange({{ $t->id }}, {{ $tech['user_id'] }}, {{ $isOver ? 'true' : 'false' }})"
                    class="w-4 h-4 text-amber-600 focus:ring-amber-500 border-slate-300 cursor-pointer disabled:cursor-not-allowed">
                <label for="lead-{{ $t->id }}-{{ $tech['user_id'] }}" class="cursor-pointer font-bold text-slate-900 select-none flex items-center gap-1.5 flex-wrap">
                    <span>👑 {{ $tech['nama'] }}</span>
                    <span class="text-xs text-slate-500 font-mono font-normal">({{ $tech['username'] }})</span>
                    @if(!empty($tech['bidang_kode']))
                        <span class="text-xs font-bold px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 border border-slate-200">
                            {{ $tech['bidang_kode'] }}
                        </span>
                    @endif
                    @if($isCurLead)
                        <span class="text-xs font-black bg-amber-200 text-amber-900 px-1.5 py-0.2 rounded uppercase">Lead Aktif</span>
                    @endif
                </label>
            </div>
        </div>

        <!-- Checkbox / Status Pendamping -->
        <div class="flex items-center gap-1 pt-0.5" id="mem-wrapper-{{ $t->id }}-{{ $tech['user_id'] }}">
            <span id="mem-badge-{{ $t->id }}-{{ $tech['user_id'] }}" class="{{ $isCurLead ? 'inline-flex' : 'hidden' }} items-center gap-1 text-xs font-bold text-amber-800 bg-amber-100/90 border border-amber-300 px-2 py-0.5 rounded-md" title="Teknisi ini adalah Koordinator Utama (Lead PIC). Pilih teknisi lain sebagai pendamping jika diperlukan.">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Koordinator Utama
            </span>
            <div id="mem-input-box-{{ $t->id }}-{{ $tech['user_id'] }}" class="{{ $isCurLead ? 'hidden' : 'flex' }} items-center gap-1">
                <input type="checkbox" name="member_user_ids[]" id="mem-{{ $t->id }}-{{ $tech['user_id'] }}"
                    value="{{ $tech['user_id'] }}"
                    {{ $isCurMember ? 'checked' : '' }}
                    {{ $onLeave ? 'disabled' : '' }}
                    class="w-3.5 h-3.5 text-blue-600 focus:ring-blue-500 rounded border-slate-300 cursor-pointer disabled:cursor-not-allowed">
                <label for="mem-{{ $t->id }}-{{ $tech['user_id'] }}" class="text-xs font-semibold text-slate-600 select-none cursor-pointer">
                    + Pendamping
                </label>
            </div>
        </div>
    </div>

    <!-- Badges Row -->
    <div class="flex items-center flex-wrap gap-1.5 mt-2">
        <!-- Availability Badge -->
        @if($tech['availability'] === 'tersedia')
            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Tersedia
            </span>
        @elseif($tech['availability'] === 'sibuk')
            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200 flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Sibuk di Lapangan
            </span>
        @else
            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200 flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Cuti (Terkunci)
            </span>
        @endif

        <!-- Category Match Badge -->
        @if($tech['is_cat_match'])
            <span class="px-2 py-0.5 rounded-full text-xs font-black bg-indigo-50 text-indigo-700 border border-indigo-200">
                ✨ Cocok Kategori
            </span>
        @endif

        <!-- Urgent Tickets Warning -->
        @if($tech['urgent_count'] > 0)
            <span class="px-2 py-0.5 rounded-full text-xs font-black bg-rose-100 text-rose-800 border border-rose-300">
                ⚠️ {{ $tech['urgent_count'] }} Tiket Urgent (&lt;2j)
            </span>
        @endif
    </div>

    <!-- Workload Progress Bar -->
    <div class="mt-2.5">
        <div class="flex items-center justify-between text-xs mb-1">
            <span class="text-slate-600">Beban Kerja Aktif:</span>
            <span class="font-extrabold {{ $loadTextCls }}">
                {{ $tech['weighted_load'] }} / {{ $tech['max_load'] }} Poin ({{ $tech['load_percent'] }}%)
            </span>
        </div>
        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
            <div class="{{ $loadColorCls }} h-2 rounded-full transition-all duration-300" style="width: {{ $tech['load_percent'] }}%"></div>
        </div>
    </div>

    <!-- Expandable Active Tickets Accordion -->
    @if(!empty($tech['active_tickets']))
        <div class="mt-2 pt-2 border-t border-slate-100">
            <button type="button" onclick="document.getElementById('tech-tasks-{{ $t->id }}-{{ $tech['user_id'] }}').classList.toggle('hidden')"
                class="text-xs font-bold text-blue-700 hover:text-blue-900 flex items-center gap-1 cursor-pointer">
                <span>📋 Lihat {{ count($tech['active_tickets']) }} Tiket Aktif Sedang Dikerjakan</span> ▾
            </button>
            <div id="tech-tasks-{{ $t->id }}-{{ $tech['user_id'] }}" class="hidden mt-1.5 space-y-1 pl-2 border-l-2 border-blue-200">
                @foreach($tech['active_tickets'] as $at)
                    <div class="text-xs text-slate-600 flex items-center justify-between gap-1">
                        <div class="flex items-center gap-1 truncate max-w-[200px]">
                            <span class="font-mono font-bold">{{ $at['kode_tiket'] }}</span>
                            <span class="truncate">({{ $at['judul'] }})</span>
                        </div>
                        <span class="font-bold shrink-0 {{ $at['mins_left'] < 120 ? 'text-rose-600' : 'text-slate-500' }}">
                            ⏳ {{ $at['sisa_teks'] }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
