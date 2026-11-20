<?php

namespace App\Services;

final class SsoTokenService
{
    public static function sign(array $claims, string $secretKey): string
    {
        $data = self::encode(json_encode(['alg' => 'EdDSA', 'typ' => 'JWT'])).'.'.self::encode(json_encode($claims));

        return $data.'.'.self::encode(sodium_crypto_sign_detached($data, base64_decode($secretKey, true)));
    }

    public static function verify(string $token, array $publicKeys, string $issuer, string $audience, int $skew = 30): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        [$header, $payload, $signature] = $parts;
        $signature = self::decode($signature);
        $head = json_decode(self::decode($header), true);

        if (!is_array($head) || ($head['alg'] ?? null) !== 'EdDSA' || strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES) {
            return null;
        }

        $signed = false;

        foreach ($publicKeys as $key) {
            $raw = base64_decode((string) $key, true);

            if ($raw !== false && strlen($raw) === SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES
                && sodium_crypto_sign_verify_detached($signature, $header.'.'.$payload, $raw)) {
                $signed = true;
                break;
            }
        }

        $claims = $signed ? json_decode(self::decode($payload), true) : null;

        if (!is_array($claims) || ($claims['iss'] ?? null) !== $issuer || ($claims['aud'] ?? null) !== $audience
            || !is_int($claims['iat'] ?? null) || !is_int($claims['exp'] ?? null)) {
            return null;
        }

        $now = time();

        if ($claims['iat'] > $now + $skew || $claims['exp'] < $now - $skew || $claims['exp'] < $claims['iat'] || $claims['exp'] - $claims['iat'] > 60) {
            return null;
        }

        return $claims;
    }

    private static function encode(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private static function decode(string $encoded): string
    {
        $padded = str_pad(strtr($encoded, '-_', '+/'), (int) ceil(strlen($encoded) / 4) * 4, '=');

        return base64_decode($padded, true) ?: '';
    }
}
