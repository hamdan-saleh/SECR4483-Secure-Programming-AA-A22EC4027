<?php

// Local test harness using the same vulnerable cipher and key.
$payload = str_repeat('A', 32);
$secretKey = 'MedVaultKey123!';

$ciphertext = openssl_encrypt(
    $payload,
    'aes-128-ecb',
    $secretKey,
    OPENSSL_RAW_DATA
);

if ($ciphertext === false) {
    die('Encryption failed.');
}

header('Content-Type: text/plain; charset=UTF-8');

echo "Plaintext length: " . strlen($payload) . " bytes\n";
echo "Plaintext: " . $payload . "\n\n";

$blocks = str_split($ciphertext, 16);

foreach ($blocks as $index => $block) {
    echo "Ciphertext block " . ($index + 1) . ": "
        . bin2hex($block)
        . "\n";
}