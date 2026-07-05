<?php

declare(strict_types=1);

namespace Tests;

use App\CryptoVault;
use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class SecurityTest extends TestCase
{
    private CryptoVault $vault;

    protected function setUp(): void
    {
        // Load the local secret configuration.
        Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

        $encodedKey = $_ENV['MEDVAULT_KEY_B64'] ?? '';
        $key = base64_decode($encodedKey, true);

        $this->assertIsString($key);
        $this->vault = new CryptoVault($key);
    }

    public function testUntamperedCryptographicLifecycle(): void
    {
        $original = 'Patient dosage: 10 mg';
        $encrypted = $this->vault->encrypt($original);
        $decrypted = $this->vault->decrypt($encrypted);

        $this->assertSame($original, $decrypted);
    }

    public function testTamperedCiphertextThrowsControlledAeadException(): void
    {
        $payload = $this->vault->encrypt(
            'Patient dosage: 10 mg'
        );

        $raw = base64_decode($payload, true);
        $this->assertIsString($raw);

        // Modify the first byte of the ciphertext.
        $raw[12] = chr(ord($raw[12]) ^ 1);
        $tamperedPayload = base64_encode($raw);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'AEAD authentication failed.'
        );

        $this->vault->decrypt($tamperedPayload);
    }

    public function testCredentialHashIntegrityMatches(): void
    {
        $credential = 'testkey123';

        $hash = password_hash(
            $credential,
            PASSWORD_ARGON2ID,
            [
                'memory_cost' => 65536,
                'time_cost'   => 3,
                'threads'     => 2
            ]
        );

        $this->assertIsString($hash);
        $this->assertTrue(
            password_verify($credential, $hash)
        );
        $this->assertFalse(
            password_verify('incorrect-key', $hash)
        );
    }
}