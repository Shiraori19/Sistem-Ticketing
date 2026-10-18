<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketObstacle extends Model
{
    use HasFactory;

    protected $table = 'ticket_obstacles';

    protected $fillable = [
        'ticket_id',
        'user_id',
        'obstacle_type',
        'description',
        'evidence_path',
        'reported_at',
        'follow_up_deadline',
    ];

    protected $casts = [
        'reported_at' => 'datetime',
        'follow_up_deadline' => 'datetime',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
