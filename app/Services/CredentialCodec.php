<?php

namespace App\Services;

/**
 * Turns a credential into the exact bytes that get hashed.
 *
 * Issuer and verifier must produce identical bytes, so keys are sorted and
 * every value is a string. Changing any character changes the SHA-256 hash.
 */
class CredentialCodec
{
    public static function canonical(array $credential): string
    {
        ksort($credential);

        return json_encode($credential, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /** SHA-256 fingerprint as a 0x-prefixed bytes32 hex string. */
    public static function hash(array $credential): string
    {
        return '0x'.hash('sha256', self::canonical($credential));
    }

    /** A random secret so nobody can guess a credential from its public hash. */
    public static function salt(): string
    {
        return bin2hex(random_bytes(16));
    }

    public static function toUrlToken(array $credential): string
    {
        return rtrim(strtr(base64_encode(self::canonical($credential)), '+/', '-_'), '=');
    }

    public static function fromUrlToken(string $token): ?array
    {
        $json = base64_decode(strtr($token, '-_', '+/'), true);

        return $json === false ? null : self::parse($json);
    }

    /**
     * Accepts either a downloaded .vericred.json file ({"credential": {...}})
     * or a bare credential object. Returns null when it is not a credential.
     */
    public static function parse(string $json): ?array
    {
        $data = json_decode($json, true);

        if (! is_array($data)) {
            return null;
        }

        $credential = $data['credential'] ?? $data;

        if (! is_array($credential) || ! isset($credential['salt'], $credential['issuer'])) {
            return null;
        }

        foreach ($credential as $value) {
            if (! is_string($value)) {
                return null;
            }
        }

        return $credential;
    }
}
