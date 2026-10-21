<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketBidangInvolvement extends Model
{
    use HasFactory;

    protected $table = 'ticket_bidang_involvement';

    protected $fillable = [
        'ticket_id',
        'bidang_id',
        'peran', // 'pelaksana', 'ditembuskan', 'pendukung'
        'notified_at',
        'acknowledged_at',
        'ditetapkan_oleh',
    ];

    protected $casts = [
        'notified_at'     => 'datetime',
        'acknowledged_at' => 'datetime',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function bidang(): BelongsTo
    {
        return $this->belongsTo(Bidang::class, 'bidang_id');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditetapkan_oleh');
    }
}
