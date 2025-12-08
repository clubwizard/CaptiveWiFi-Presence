<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccessPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'ap_name',
        'mac_address',
        'location_description',
        'floor',
        'zone',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    /**
     * Get the client sessions for this access point.
     */
    public function clientSessions(): HasMany
    {
        return $this->hasMany(ClientSession::class);
    }

    /**
     * Get active (connected) sessions for this access point.
     */
    public function activeSessions(): HasMany
    {
        return $this->hasMany(ClientSession::class)->whereNull('disconnected_at');
    }
}
