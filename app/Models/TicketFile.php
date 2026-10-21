<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketFile extends Model
{
    use HasFactory;

    protected $table = 'ticket_files';

    protected $fillable = [
        'ticket_id',
        'event_id',
        'uploader_id',
        'path',
        'mime',
        'size',
        'kategori', // 'progres_lapangan', 'bukti_hambatan', 'bukti_resolusi', dll.
        'sha256',
        'original_name',
        'classification', // 'umum', 'terbatas', 'rahasia'
        'classification_reason',
        'classified_by',
        'is_encrypted',
        'vault_path',
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
        'size' => 'integer',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(TicketEvent::class, 'event_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    public function classifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'classified_by');
    }

    public function accessRequests(): HasMany
    {
        return $this->hasMany(TicketFileAccessRequest::class, 'ticket_file_id');
    }

    public function isConfidential(): bool
    {
        return in_array($this->classification, ['rahasia', 'terbatas'], true);
    }

    public function isVault(): bool
    {
        return $this->classification === 'rahasia' || !empty($this->vault_path);
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime, 'image/');
    }

    public function isPdf(): bool
    {
        return $this->mime === 'application/pdf';
    }

    public function getIsVaultAttribute(): bool
    {
        return $this->isVault();
    }

    public function getExtensionAttribute(): string
    {
        return strtolower(pathinfo($this->path, PATHINFO_EXTENSION));
    }

    public function getFilenameAttribute(): string
    {
        return basename($this->path);
    }

    public function getStoragePathAttribute(): string
    {
        return $this->path;
    }

    public function getMimeTypeAttribute(): string
    {
        return $this->mime;
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size;
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }
}
