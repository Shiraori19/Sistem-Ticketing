<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketFileAccessRequest extends Model
{
    use HasFactory;

    protected $table = 'ticket_file_access_requests';

    protected $fillable = [
        'ticket_file_id',
        'user_id',
        'reason',
        'status', // 'pending', 'approved', 'rejected'
        'reviewed_by',
        'reviewed_at',
        'expires_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'expires_at'  => 'datetime',
    ];

    public function ticketFile(): BelongsTo
    {
        return $this->belongsTo(TicketFile::class, 'ticket_file_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved' && (!$this->expires_at || $this->expires_at->isFuture());
    }

    public function isApprovedAndValid(): bool
    {
        return $this->isApproved();
    }
}
