<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SlaException extends Model
{
    use HasFactory;

    protected $table = 'sla_exceptions';

    protected $fillable = [
        'ticket_id',
        'alasan',
        'diputuskan_oleh',
        'lampiran_bukti',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diputuskan_oleh');
    }
}
