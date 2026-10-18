<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketAssignment extends Model
{
    use HasFactory;

    protected $table = 'ticket_assignments';

    protected $fillable = [
        'ticket_id',
        'user_id',
        'role', // 'lead' atau 'pendamping'
        'technician_name',
        'role_in_task', // 'lead_pic' atau 'member' (kompatibilitas historis)
        'status', // 'assigned', 'working', 'submitted', 'approved', 'declined', 'released', 'returned'
        'decline_reason',
        'assigned_at',
        'respond_deadline',
        'accepted_at',
        'started_at',
        'submitted_at',
        'approved_at',
        'returned_at',
        'unassigned_at',
        'alasan_unassign',
        'row_version',
        'reviewed_at',
        'reviewed_by',
        'review_notes',
        'worklog',
        'notes',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'respond_deadline' => 'datetime',
        'accepted_at' => 'datetime',
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'returned_at' => 'datetime',
        'unassigned_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'row_version' => 'integer',
        'worklog' => 'array',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function events(): HasMany
    {
        return $this->hasMany(TicketEvent::class, 'assignment_id');
    }

    public function blockers(): HasMany
    {
        return $this->hasMany(TicketBlocker::class, 'assignment_id');
    }

    public function isLead(): bool
    {
        return $this->role === 'lead' || $this->role_in_task === 'lead_pic';
    }

    public function isActive(): bool
    {
        return is_null($this->unassigned_at) && !in_array($this->status, ['declined', 'approved', 'released']);
    }

    public function isExpired(): bool
    {
        return $this->status === 'assigned' 
            && $this->respond_deadline 
            && $this->respond_deadline->isPast();
    }

    public function incrementVersion(): void
    {
        $this->row_version = ($this->row_version ?? 1) + 1;
    }

    /**
     * Scope untuk penugasan lead aktif pada tiket
     */
    public function scopeActiveLead(Builder $query): Builder
    {
        return $query->where('role', 'lead')->whereNull('unassigned_at');
    }

    /**
     * Scope untuk seluruh penugasan aktif (belum di-unassign)
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('unassigned_at');
    }
}
