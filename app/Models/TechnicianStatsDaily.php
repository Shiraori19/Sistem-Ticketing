<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechnicianStatsDaily extends Model
{
    use HasFactory;

    protected $table = 'technician_stats_daily';

    protected $fillable = [
        'user_id',
        'date',
        'tasks_completed',
        'median_resolution_minutes',
        'sla_on_time_raw',
        'sla_on_time_excused',
        'reopen_count',
        'avg_rating',
        'category_breakdown',
    ];

    protected $casts = [
        'date' => 'date',
        'tasks_completed' => 'integer',
        'median_resolution_minutes' => 'integer',
        'sla_on_time_raw' => 'integer',
        'sla_on_time_excused' => 'integer',
        'reopen_count' => 'integer',
        'avg_rating' => 'float',
        'category_breakdown' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
