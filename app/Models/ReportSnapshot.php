<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportSnapshot extends Model
{
    use HasFactory;

    protected $table = 'report_snapshots';

    protected $fillable = [
        'periode_bulan',
        'filter_hash',
        'payload_json',
        'frozen_at',
        'frozen_by',
    ];

    protected $casts = [
        'payload_json' => 'array',
        'frozen_at' => 'datetime',
    ];

    public function freezer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'frozen_by');
    }
}
