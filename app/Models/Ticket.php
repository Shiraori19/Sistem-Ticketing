<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use HasFactory;

    protected $table = 'tickets';

    /**
     * Properti sementara untuk struk tanda terima peramban saat pembuatan tiket
     */
    public ?string $raw_pin_temporary = null;

    protected $hidden = [
        'pin',
    ];

    protected $fillable = [
        'parent_id',
        'is_parent',
        'kode_tiket',
        'nip_pelapor',
        'nama_pelapor',
        'skpd',
        'no_whatsapp',
        'email_pelapor',
        'pin',
        'category_id',
        'judul',
        'deskripsi',
        'lampiran_pelapor',
        'lampiran_tambahan',
        'catatan_kelengkapan',
        'tlp_level',
        'bidang_id',
        'verified_by',
        'assigned_by',
        'status',
        'teknisi_lapangan',
        'sla_dimulai_at',
        'sla_deadline_at',
        'sla_respon_deadline_at',
        'sla_respon_jam',
        'sla_resolusi_jam',
        'sla_breach',
        'sla_respon_breach',
        'bukti_resolusi',
        'catatan_resolusi',
        'is_confidential_evidence',
        'reopen_count',
        'max_reopen',
        'bukti_reopen_tambahan',
        'force_close_notes',
        'rating',
        'feedback',
        'status_history',
        'sla_excused',
        'sla_excused_reason',
        'sla_excused_by',
        'sla_excused_at',
        'sla_excused_evidence_path',
        'pengerjaan_dimulai_at',
        'opd_id',
        'selesai_at',
        'jenis_penyelesaian',
        'total_jeda_menit',
        'claimed_by',
        'claimed_at',
        'row_version',
    ];

    protected $casts = [
        'is_parent' => 'boolean',
        'status_history' => 'array',
        'lampiran_pelapor' => 'array',
        'is_confidential_evidence' => 'boolean',
        'sla_breach' => 'boolean',
        'sla_respon_breach' => 'boolean',
        'sla_dimulai_at' => 'datetime',
        'sla_deadline_at' => 'datetime',
        'sla_respon_deadline_at' => 'datetime',
        'sla_respon_jam' => 'decimal:2',
        'sla_resolusi_jam' => 'integer',
        'reopen_count' => 'integer',
        'max_reopen' => 'integer',
        'rating' => 'integer',
        'sla_excused' => 'boolean',
        'sla_excused_at' => 'datetime',
        'pengerjaan_dimulai_at' => 'datetime',
        'selesai_at' => 'datetime',
        'total_jeda_menit' => 'integer',
        'claimed_at' => 'datetime',
        'row_version' => 'integer',
    ];

    protected $attributes = [
        'status' => 'pending_verification',
        'sla_respon_jam' => 72.00,
        'sla_resolusi_jam' => 336,
        'sla_breach' => false,
        'sla_respon_breach' => false,
        'reopen_count' => 0,
        'max_reopen' => 2,
    ];

    /**
     * Mutator no_whatsapp: normalisasi 08xxx -> 628xxx
     */
    protected function noWhatsapp(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value,
            set: function ($value) {
                if (empty($value)) {
                    return $value;
                }
                $cleaned = preg_replace('/[^0-9]/', '', (string)$value);
                if (str_starts_with($cleaned, '08')) {
                    return '628' . substr($cleaned, 2);
                }
                if (str_starts_with($cleaned, '8')) {
                    return '628' . substr($cleaned, 1);
                }
                return $cleaned;
            }
        );
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function opd(): BelongsTo
    {
        return $this->belongsTo(MasterOpd::class, 'opd_id');
    }

    public function slaExceptions(): HasMany
    {
        return $this->hasMany(SlaException::class, 'ticket_id');
    }

    public function bidang(): BelongsTo
    {
        return $this->belongsTo(Bidang::class, 'bidang_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'ticket_id');
    }


    public function parent(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Ticket::class, 'parent_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TicketAssignment::class, 'ticket_id');
    }

    public function activeLeadAssignment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(TicketAssignment::class, 'ticket_id')->where('role', 'lead')->whereNull('unassigned_at');
    }

    public function activeAssignments(): HasMany
    {
        return $this->hasMany(TicketAssignment::class, 'ticket_id')->whereNull('unassigned_at');
    }

    public function events(): HasMany
    {
        return $this->hasMany(TicketEvent::class, 'ticket_id')->orderBy('created_at', 'asc');
    }

    public function blockers(): HasMany
    {
        return $this->hasMany(TicketBlocker::class, 'ticket_id')->orderBy('created_at', 'desc');
    }

    public function activeBlockers(): HasMany
    {
        return $this->hasMany(TicketBlocker::class, 'ticket_id')->where('status', 'open');
    }

    public function files(): HasMany
    {
        return $this->hasMany(TicketFile::class, 'ticket_id')->orderBy('created_at', 'desc');
    }

    public function involvements(): HasMany
    {
        return $this->hasMany(TicketBidangInvolvement::class, 'ticket_id');
    }

    public function claimedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimed_by');
    }

    public function isClaimActive(): bool
    {
        return !empty($this->claimed_by) && $this->claimed_at && $this->claimed_at->copy()->addMinutes(10)->isFuture();
    }

    public function isClaimExpired(): bool
    {
        return empty($this->claimed_by) || !$this->claimed_at || $this->claimed_at->copy()->addMinutes(10)->isPast();
    }

    public function canBeClaimedBy(int $userId): bool
    {
        return $this->isClaimExpired() || $this->claimed_by === $userId;
    }

    public function getLeadTechnicianNameAttribute(): string
    {
        if ($this->relationLoaded('activeLeadAssignment') && $this->activeLeadAssignment) {
            return $this->activeLeadAssignment->user?->nama ?: ($this->activeLeadAssignment->technician_name ?: (string) $this->teknisi_lapangan);
        }

        $lead = $this->activeLeadAssignment()->with('user')->first();
        if ($lead) {
            return $lead->user?->nama ?: ($lead->technician_name ?: (string) $this->teknisi_lapangan);
        }

        return (string) ($this->teknisi_lapangan ?: 'Belum ditugaskan');
    }

    public function obstacles(): HasMany
    {
        return $this->hasMany(TicketObstacle::class, 'ticket_id');
    }

    /**
     * Token akses aman pelapor untuk konfirmasi & reopen via tautan WhatsApp (Magic Link)
     */
    public function getAccessTokenAttribute(): string
    {
        return substr(hash_hmac('sha256', $this->id . '|' . $this->kode_tiket . '|' . $this->no_whatsapp, config('app.key')), 0, 32);
    }

    /**
     * Label status kontekstual berdasarkan Role Pengguna (Pelapor, Admin Pusat, Admin Bidang)
     */
    public function getDisplayStatusAttribute(): string
    {
        $role = auth()->user()?->role?->name ?? (auth()->user()?->role ?? 'pelapor');

        return match ($this->status) {
            'pending_verification' => match ($role) {
                'admin_pusat' => 'pending verification (baru)',
                default => 'pending verification',
            },
            'pending_info' => match ($role) {
                'admin_pusat' => 'pending verification (pending info)',
                default => 'pending verification (perlu dilengkapi)',
            },
            'verified' => match ($role) {
                'admin_bidang' => 'baru',
                'admin_pusat' => 'verified (disposisi ke admin bidang)',
                default => 'verified',
            },
            'in_progress' => match ($role) {
                'admin_bidang' => 'in progress (disposisi ke teknisi)',
                default => 'in progress',
            },
            'resolved' => match ($role) {
                'admin_bidang' => 'resolved',
                default => 'selesai',
            },
            'closed' => match ($role) {
                'admin_bidang' => 'resolved',
                default => 'selesai',
            },
            'reopened' => match ($role) {
                'admin_bidang' => 'in progress (disposisi ke teknisi)',
                default => 'in progress',
            },
            'eskalasi_pusat' => match ($role) {
                'admin_bidang' => 'in progress (disposisi ke teknisi)',
                default => 'in progress',
            },
            'force_closed' => match ($role) {
                'admin_bidang' => 'resolved',
                default => 'selesai',
            },
            'cancelled' => 'cancelled',
            default => (string) $this->status,
        };
    }

    /**
     * Label status tiket dalam Bahasa Indonesia ramah pengguna
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending_verification' => 'Menunggu Verifikasi Admin Pusat',
            'pending_info' => 'Menunggu Kelengkapan Berkas (Pending Info)',
            'verified' => 'Terverifikasi (Disposisi ke Bidang)',
            'in_progress' => 'Sedang Ditangani Teknisi Lapangan',
            'resolved' => 'Selesai Dikerjakan (Menunggu Konfirmasi Anda)',
            'reopened' => 'Dibuka Kembali (Dalam Penanganan Ulang)',
            'eskalasi_pusat' => 'Eskalasi Penanganan Admin Pusat',
            'closed' => 'Selesai Resmi & Ditutup',
            'force_closed' => 'Ditutup oleh Pimpinan DKIS',
            'cancelled' => 'Dibatalkan',
            default => ucfirst(str_replace('_', ' ', (string) $this->status)),
        };
    }
}

