<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;

class MacAddressHasher
{
    /**
     * Hash a MAC address using SHA256 with application key as salt.
     * This ensures MAC addresses are never stored in plain text for GDPR compliance.
     *
     * @param string $macAddress
     * @return string SHA256 hash of the MAC address
     */
    public static function hash(string $macAddress): string
    {
        $macAddress = self::normalize($macAddress);
        $salt = Config::get('app.key');

        return hash('sha256', $macAddress . $salt);
    }

    /**
     * Normalize MAC address format (removes colons, hyphens, converts to lowercase).
     *
     * @param string $macAddress
     * @return string
     */
    public static function normalize(string $macAddress): string
    {
        return strtolower(str_replace([':', '-', '.'], '', $macAddress));
    }

    /**
     * Validate MAC address format.
     *
     * @param string $macAddress
     * @return bool
     */
    public static function isValid(string $macAddress): bool
    {
        $normalized = self::normalize($macAddress);
        return (bool) preg_match('/^[0-9a-f]{12}$/', $normalized);
    }
}
