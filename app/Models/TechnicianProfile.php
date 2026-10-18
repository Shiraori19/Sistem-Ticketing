<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechnicianProfile extends Model
{
    use HasFactory;

    protected $table = 'technician_profiles';

    protected $fillable = [
        'user_id',
        'availability',
        'cuti_until',
        'max_load',
        'skills',
    ];

    protected $casts = [
        'cuti_until' => 'datetime',
        'max_load' => 'integer',
        'skills' => 'array',
    ];

    protected $attributes = [
        'availability' => 'tersedia',
        'max_load' => 6,
        'skills' => '[]',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isAvailable(): bool
    {
        return $this->availability === 'tersedia';
    }

    public function isOnLeave(): bool
    {
        return $this->availability === 'cuti';
    }

    public function isBusy(): bool
    {
        return $this->availability === 'sibuk';
    }

    /**
     * Cek apakah kategori tiket cocok dengan keahlian teknisi
     */
    public function matchesCategory(string|int|null $category): bool
    {
        if (empty($category)) {
            return false;
        }

        $skills = is_array($this->skills) ? $this->skills : [];
        foreach ($skills as $skill) {
            if (is_numeric($skill) && is_numeric($category) && (int)$skill === (int)$category) {
                return true;
            }
            if (is_string($skill) && is_string($category) && (
                strcasecmp($skill, $category) === 0 || 
                str_contains(strtolower($category), strtolower($skill)) ||
                str_contains(strtolower($skill), strtolower($category))
            )) {
                return true;
            }
        }

        return false;
    }
}
