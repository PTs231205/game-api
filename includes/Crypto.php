<?php

/**
 * Small helper for reversible encryption (admin-only secrets).
 * NOTE: For login verification we still use password_hash/password_verify.
 */
class Crypto
{
    private const CIPHER = 'AES-256-CBC';

    private static function keyFromSalt(string $salt): string
    {
        // 32-byte key for AES-256
        return hash('sha256', $salt, true);
    }

    public static function encryptString(string $plaintext, string $salt): string
    {
        if ($salt === '' || $plaintext === '') return '';

        $key = self::keyFromSalt($salt);
        $iv = random_bytes(16); // AES block size

        $ciphertext = openssl_encrypt($plaintext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);
        if ($ciphertext === false) return '';

        // Store iv + ciphertext together
        return base64_encode($iv . $ciphertext);
    }

    public static function decryptString(string $encoded, string $salt): ?string
    {
        if ($salt === '' || $encoded === '') return null;

        $raw = base64_decode($encoded, true);
        if ($raw === false || strlen($raw) < 17) return null;

        $iv = substr($raw, 0, 16);
        $ciphertext = substr($raw, 16);

        $key = self::keyFromSalt($salt);
        $plaintext = openssl_decrypt($ciphertext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);
        if ($plaintext === false) return null;

        return $plaintext;
    }
}

