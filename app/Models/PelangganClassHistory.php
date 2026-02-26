<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PelangganClassHistory extends Model
{
    protected $fillable = [
        'pelanggan_id',
        'previous_class',
        'new_class',
        'changed_at',
        'changed_by',
        'reason'
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    /**
     * Relasi ke pelanggan
     */
    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class);
    }

    /**
     * Relasi ke user yang melakukan perubahan
     */
    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Get badge color based on class
     */
    public function getBadgeColorAttribute(): string
    {
        return match($this->new_class) {
            'Prioritas' => 'danger',
            'Loyal' => 'success',
            'Potensial' => 'warning',
            default => 'secondary'
        };
    }

    /**
     * Get description of the change
     */
    public function getChangeDescriptionAttribute(): string
    {
        if ($this->previous_class) {
            return "Berubah dari {$this->previous_class} menjadi {$this->new_class}";
        }
        return "Ditetapkan sebagai {$this->new_class}";
    }
}
