<?php

namespace Tests\Unit;

use App\Services\MacAddressHasher;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class MacAddressHasherTest extends TestCase
{
    /**
     * Test that MAC addresses are hashed consistently.
     */
    public function test_hash_produces_consistent_results(): void
    {
        $mac = 'AA:BB:CC:DD:EE:FF';

        $hash1 = MacAddressHasher::hash($mac);
        $hash2 = MacAddressHasher::hash($mac);

        $this->assertEquals($hash1, $hash2);
        $this->assertEquals(64, strlen($hash1)); // SHA256 produces 64 char hex string
    }

    /**
     * Test that different MAC addresses produce different hashes.
     */
    public function test_different_macs_produce_different_hashes(): void
    {
        $mac1 = 'AA:BB:CC:DD:EE:FF';
        $mac2 = 'AA:BB:CC:DD:EE:FE';

        $hash1 = MacAddressHasher::hash($mac1);
        $hash2 = MacAddressHasher::hash($mac2);

        $this->assertNotEquals($hash1, $hash2);
    }

    /**
     * Test that MAC addresses in different formats produce the same hash.
     */
    public function test_different_formats_produce_same_hash(): void
    {
        $macColon = 'AA:BB:CC:DD:EE:FF';
        $macHyphen = 'AA-BB-CC-DD-EE-FF';
        $macDot = 'AABB.CCDD.EEFF';
        $macPlain = 'AABBCCDDEEFF';

        $hashColon = MacAddressHasher::hash($macColon);
        $hashHyphen = MacAddressHasher::hash($macHyphen);
        $hashDot = MacAddressHasher::hash($macDot);
        $hashPlain = MacAddressHasher::hash($macPlain);

        $this->assertEquals($hashColon, $hashHyphen);
        $this->assertEquals($hashColon, $hashDot);
        $this->assertEquals($hashColon, $hashPlain);
    }

    /**
     * Test MAC address normalization.
     */
    public function test_normalize_removes_separators(): void
    {
        $this->assertEquals('aabbccddeeff', MacAddressHasher::normalize('AA:BB:CC:DD:EE:FF'));
        $this->assertEquals('aabbccddeeff', MacAddressHasher::normalize('AA-BB-CC-DD-EE-FF'));
        $this->assertEquals('aabbccddeeff', MacAddressHasher::normalize('AABB.CCDD.EEFF'));
        $this->assertEquals('aabbccddeeff', MacAddressHasher::normalize('AABBCCDDEEFF'));
    }

    /**
     * Test MAC address normalization converts to lowercase.
     */
    public function test_normalize_converts_to_lowercase(): void
    {
        $this->assertEquals('aabbccddeeff', MacAddressHasher::normalize('AABBCCDDEEFF'));
        $this->assertEquals('aabbccddeeff', MacAddressHasher::normalize('AaBbCcDdEeFf'));
    }

    /**
     * Test valid MAC address validation.
     */
    public function test_is_valid_accepts_valid_mac_addresses(): void
    {
        $this->assertTrue(MacAddressHasher::isValid('AA:BB:CC:DD:EE:FF'));
        $this->assertTrue(MacAddressHasher::isValid('AA-BB-CC-DD-EE-FF'));
        $this->assertTrue(MacAddressHasher::isValid('AABB.CCDD.EEFF'));
        $this->assertTrue(MacAddressHasher::isValid('AABBCCDDEEFF'));
        $this->assertTrue(MacAddressHasher::isValid('00:00:00:00:00:00'));
        $this->assertTrue(MacAddressHasher::isValid('FF:FF:FF:FF:FF:FF'));
    }

    /**
     * Test invalid MAC address validation.
     */
    public function test_is_valid_rejects_invalid_mac_addresses(): void
    {
        $this->assertFalse(MacAddressHasher::isValid(''));
        $this->assertFalse(MacAddressHasher::isValid('invalid'));
        $this->assertFalse(MacAddressHasher::isValid('AA:BB:CC:DD:EE')); // Too short
        $this->assertFalse(MacAddressHasher::isValid('AA:BB:CC:DD:EE:FF:GG')); // Too long
        $this->assertFalse(MacAddressHasher::isValid('GG:HH:II:JJ:KK:LL')); // Invalid hex
        $this->assertFalse(MacAddressHasher::isValid('192.168.1.1')); // IP address
    }

    /**
     * Test that hashes are not reversible (one-way).
     */
    public function test_hash_is_not_reversible(): void
    {
        $mac = 'AA:BB:CC:DD:EE:FF';
        $hash = MacAddressHasher::hash($mac);

        // Hash should not contain the original MAC
        $this->assertStringNotContainsString('AA', $hash);
        $this->assertStringNotContainsString('BB', $hash);
        $this->assertStringNotContainsString('AABBCC', $hash);
    }

    /**
     * Test that hash uses application key as salt.
     */
    public function test_hash_uses_application_key(): void
    {
        // This test verifies that the same MAC with different app keys
        // produces different hashes (proving the app key is used as salt)

        $mac = 'AA:BB:CC:DD:EE:FF';

        // Set a specific app key
        Config::set('app.key', 'test-key-1');
        $hash1 = MacAddressHasher::hash($mac);

        // Change the app key
        Config::set('app.key', 'test-key-2');
        $hash2 = MacAddressHasher::hash($mac);

        // Hashes should be different when app key changes
        $this->assertNotEquals($hash1, $hash2);
    }

    /**
     * Test hash format is valid SHA256.
     */
    public function test_hash_format_is_valid_sha256(): void
    {
        $mac = 'AA:BB:CC:DD:EE:FF';
        $hash = MacAddressHasher::hash($mac);

        // SHA256 produces 64 character hex string
        $this->assertEquals(64, strlen($hash));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash);
    }

    /**
     * Test case insensitivity in MAC address handling.
     */
    public function test_case_insensitive_handling(): void
    {
        $macLower = 'aa:bb:cc:dd:ee:ff';
        $macUpper = 'AA:BB:CC:DD:EE:FF';
        $macMixed = 'Aa:Bb:Cc:Dd:Ee:Ff';

        $hashLower = MacAddressHasher::hash($macLower);
        $hashUpper = MacAddressHasher::hash($macUpper);
        $hashMixed = MacAddressHasher::hash($macMixed);

        $this->assertEquals($hashLower, $hashUpper);
        $this->assertEquals($hashLower, $hashMixed);
    }
}
