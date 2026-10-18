<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterOpd extends Model
{
    use HasFactory;

    protected $table = 'master_opd';

    protected $fillable = [
        'nama_opd',
        'singkatan',
        'alias',
        'urutan',
        'is_active',
    ];

    protected $casts = [
        'alias' => 'array',
        'urutan' => 'integer',
        'is_active' => 'boolean',
    ];

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'opd_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
