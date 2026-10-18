<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'nama',
        'username',
        'password',
        'role_id',
        'bidang_id',
        'avatar',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function roleRel(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function bidang(): BelongsTo
    {
        return $this->belongsTo(Bidang::class, 'bidang_id');
    }

    public function technicianProfile(): HasOne
    {
        return $this->hasOne(TechnicianProfile::class, 'user_id');
    }

    public function technicianStats(): HasMany
    {
        return $this->hasMany(TechnicianStatsDaily::class, 'user_id');
    }

    public function verifiedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'verified_by');
    }

    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_by');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'user_id');
    }

    public function isAdminPusat(): bool
    {
        $roleAttr = strtolower((string) ($this->attributes['role'] ?? ''));
        if (in_array($roleAttr, ['admin_pusat', 'admin pusat', 'super_admin'])) {
            return true;
        }
        return $this->role?->nama_role === 'Admin Pusat';
    }

    public function isAdminBidang(): bool
    {
        $roleAttr = strtolower((string) ($this->attributes['role'] ?? ''));
        if (in_array($roleAttr, ['admin_bidang', 'admin bidang'])) {
            return true;
        }
        return $this->role?->nama_role === 'Admin Bidang';
    }

    public function isTeknisi(): bool
    {
        $roleAttr = strtolower((string) ($this->attributes['role'] ?? ''));
        if ($roleAttr === 'teknisi') {
            return true;
        }
        return $this->role?->nama_role === 'Teknisi';
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(TicketAssignment::class, 'user_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeTeknisi(Builder $query, ?int $bidangId = null): Builder
    {
        return $query->where(function ($sub) {
            $sub->whereHas('role', fn($r) => $r->where('nama_role', 'Teknisi'));
            if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'role')) {
                $sub->orWhere('role', 'teknisi');
            }
        })->when($bidangId, fn($q) => $q->where('bidang_id', $bidangId));
    }
}
