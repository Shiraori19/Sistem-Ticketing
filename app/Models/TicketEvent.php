<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketEvent extends Model
{
    use HasFactory;

    protected $table = 'ticket_events';

    public $timestamps = false;

    protected $fillable = [
        'ticket_id',
        'assignment_id',
        'actor_id',
        'type',
        'payload',
        'created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Fail-safe di level ORM: tolak operasi update & delete demi integritas audit siber
        static::updating(function () {
            throw new \RuntimeException('Tabel ticket_events bersifat append-only. Operasi UPDATE dilarang.');
        });

        static::deleting(function () {
            throw new \RuntimeException('Tabel ticket_events bersifat append-only. Operasi DELETE dilarang.');
        });
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(TicketAssignment::class, 'assignment_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(TicketFile::class, 'event_id');
    }
}
