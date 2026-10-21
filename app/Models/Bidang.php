<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bidang extends Model
{
    use HasFactory;

    protected $table = 'bidang';

    protected $fillable = [
        'nama_bidang',
        'kode',
        'icon',
        'is_persandian',
    ];

    protected $casts = [
        'is_persandian' => 'boolean',
    ];

    public function isPersandian(): bool
    {
        return (bool) $this->is_persandian || strtoupper($this->kode) === 'PERSANDIAN';
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'bidang_id');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class, 'bidang_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'bidang_id');
    }

    /**
     * Format nama bidang lengkap dan rapi tanpa duplikasi kode
     * Contoh: "Pemerintahan Digital (PEMDI)"
     */
    public function getNamaLengkapAttribute(): string
    {
        $cleanName = trim(preg_replace('/\s*\(' . preg_quote($this->kode, '/') . '\)/i', '', $this->nama_bidang));
        return "{$cleanName} ({$this->kode})";
    }

    /**
     * Invalidate cache secara otomatis saat data bidang berubah
     */
    protected static function booted(): void
    {
        static::saved(function () {
            \Illuminate\Support\Facades\Cache::forget('bidang_list_dropdown');
            \Illuminate\Support\Facades\Cache::forget('bidangs_all');
        });

        static::deleted(function () {
            \Illuminate\Support\Facades\Cache::forget('bidang_list_dropdown');
            \Illuminate\Support\Facades\Cache::forget('bidangs_all');
        });
    }
}
