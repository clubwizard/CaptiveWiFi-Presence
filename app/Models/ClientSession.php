<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'mac_address_hash',
        'access_point_id',
        'connected_at',
        'disconnected_at',
        'session_duration_seconds',
        'signal_strength',
        'is_returning_visitor',
        'first_seen_at',
    ];

    protected $casts = [
        'connected_at' => 'datetime',
        'disconnected_at' => 'datetime',
        'first_seen_at' => 'datetime',
        'is_returning_visitor' => 'boolean',
        'session_duration_seconds' => 'integer',
        'signal_strength' => 'integer',
    ];

    /**
     * Get the access point for this session.
     */
    public function accessPoint(): BelongsTo
    {
        return $this->belongsTo(AccessPoint::class);
    }

    /**
     * Scope to get active sessions (not yet disconnected).
     */
    public function scopeActive($query)
    {
        return $query->whereNull('disconnected_at');
    }

    /**
     * Scope to get sessions within a date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('connected_at', [$startDate, $endDate]);
    }

    /**
     * Calculate dwell time in minutes.
     */
    public function getDwellTimeMinutesAttribute(): ?float
    {
        if (!$this->session_duration_seconds) {
            return null;
        }

        return round($this->session_duration_seconds / 60, 2);
    }
}
