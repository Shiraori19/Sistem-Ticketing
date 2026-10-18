<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketBlocker extends Model
{
    use HasFactory;

    protected $table = 'ticket_blockers';

    protected $fillable = [
        'ticket_id',
        'assignment_id',
        'jenis', // 'isp', 'vendor', 'opd_lain', 'suku_cadang'
        'bukti',
        'keterangan',
        'status', // 'open', 'resolved'
        'next_reminder_at',
        'resolved_at',
    ];

    protected $casts = [
        'next_reminder_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(TicketAssignment::class, 'assignment_id');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }

    public function scopeResolved(Builder $query): Builder
    {
        return $query->where('status', 'resolved');
    }

    public function getJenisLabelAttribute(): string
    {
        return match (strtolower($this->jenis)) {
            'isp' => 'Pihak Ketiga: Internet Service Provider (ISP)',
            'vendor' => 'Pihak Ketiga: Vendor / Pihak Kontraktor',
            'opd_lain' => 'Koordinasi Eksternal: OPD Terkait Lainnya',
            'suku_cadang' => 'Logistik: Pengadaan Suku Cadang / Perangkat',
            default => ucfirst(str_replace('_', ' ', (string) $this->jenis)),
        };
    }
}
