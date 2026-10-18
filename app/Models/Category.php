<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $table = 'categories';

    protected $fillable = [
        'nama_kategori',
        'bidang_id',
        'sla_respon_jam',
        'sla_resolusi_jam',
    ];

    protected $attributes = [
        'sla_respon_jam' => 72.00,
        'sla_resolusi_jam' => 336,
    ];

    protected $casts = [
        'sla_respon_jam' => 'decimal:2',
        'sla_resolusi_jam' => 'integer',
    ];

    public function bidang(): BelongsTo
    {
        return $this->belongsTo(Bidang::class, 'bidang_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'category_id');
    }
}
