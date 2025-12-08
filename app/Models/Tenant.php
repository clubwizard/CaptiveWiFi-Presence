<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Crypt;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains, HasFactory;

    protected $fillable = [
        'id',
        'name',
        'subdomain',
        'fortigate_credentials',
        'plan',
        'status',
        'trial_ends_at',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
    ];

    /**
     * Get the FortiGate credentials (decrypted).
     *
     * @return array
     */
    public function getFortigateCredentialsAttribute($value): ?array
    {
        if (!$value) {
            return null;
        }

        try {
            return json_decode(Crypt::decryptString($value), true);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set the FortiGate credentials (encrypted).
     *
     * @param array|null $value
     */
    public function setFortigateCredentialsAttribute($value): void
    {
        if (is_null($value)) {
            $this->attributes['fortigate_credentials'] = null;
            return;
        }

        $this->attributes['fortigate_credentials'] = Crypt::encryptString(json_encode($value));
    }

    /**
     * Check if the tenant is on trial.
     */
    public function isOnTrial(): bool
    {
        return $this->status === 'trial' &&
               $this->trial_ends_at &&
               $this->trial_ends_at->isFuture();
    }

    /**
     * Check if the tenant's trial has expired.
     */
    public function trialExpired(): bool
    {
        return $this->status === 'trial' &&
               $this->trial_ends_at &&
               $this->trial_ends_at->isPast();
    }

    /**
     * Check if the tenant is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active' || $this->isOnTrial();
    }
}
