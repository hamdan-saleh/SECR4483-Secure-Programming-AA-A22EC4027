<?php

declare(strict_types=1);

namespace App;

use InvalidArgumentException;
use RuntimeException;

final class CryptoVault
{
    private const CIPHER = 'aes-256-gcm';
    private const IV_BYTES = 12;
    private const TAG_BYTES = 16;

    public function __construct(
        private readonly string $key
    ) {
        // AES-256 requires an exact 32-byte key.
        if (strlen($key) !== 32) {
            throw new InvalidArgumentException(
                'AES-256 key must be exactly 32 bytes.'
            );
        }
    }

    public function encrypt(string $plaintext): string
    {
        // Generate a fresh 12-byte IV for every message.
        $iv = random_bytes(self::IV_BYTES);
        $tag = '';

        // GCM returns ciphertext and a separate authentication tag.
        $ciphertext = openssl_encrypt(
            $plaintext,
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            self::TAG_BYTES
        );

        if (
            $ciphertext === false ||
            strlen($tag) !== self::TAG_BYTES
        ) {
            throw new RuntimeException('Encryption failed.');
        }

        // Serialization: IV || ciphertext || authentication tag.
        return base64_encode($iv . $ciphertext . $tag);
    }

    public function decrypt(string $payload): string
    {
        $packed = base64_decode($payload, true);

        if (
            $packed === false ||
            strlen($packed) < self::IV_BYTES + self::TAG_BYTES
        ) {
            throw new RuntimeException(
                'Invalid encrypted payload.'
            );
        }

        // Extract the first 12 bytes as the IV.
        $iv = substr($packed, 0, self::IV_BYTES);

        // Extract the final 16 bytes as the authentication tag.
        $tag = substr($packed, -self::TAG_BYTES);

        // Everything between the IV and tag is ciphertext.
        $ciphertext = substr(
            $packed,
            self::IV_BYTES,
            -self::TAG_BYTES
        );

        $plaintext = openssl_decrypt(
            $ciphertext,
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        // Convert authentication failure into a controlled exception.
        if ($plaintext === false) {
            throw new RuntimeException(
                'AEAD authentication failed.'
            );
        }

        return $plaintext;
    }
}