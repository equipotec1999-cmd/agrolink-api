<?php

namespace App\Support;

/**
 * TOTP (RFC 6238, SHA-1, 6 dígitos, 30 s) en PHP puro: compatible con Google Authenticator,
 * Microsoft Authenticator, Authy, etc. Sin dependencias ni acceso a la base.
 */
class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public const PERIOD = 30;

    public static function newSecret(): string
    {
        return self::base32Encode(random_bytes(20)); // 160 bits, 32 caracteres
    }

    public static function base32Encode(string $binary): string
    {
        $bits = '';
        foreach (str_split($binary) as $char) {
            $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            $out .= self::ALPHABET[bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
        }

        return $out;
    }

    public static function base32Decode(string $text): string
    {
        $bits = '';
        foreach (str_split(strtoupper(rtrim($text, '='))) as $char) {
            $pos = strpos(self::ALPHABET, $char);
            if ($pos === false) {
                continue;
            }
            $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $out .= chr(bindec($byte));
            }
        }

        return $out;
    }

    /** Código de 6 dígitos para un intervalo de 30 s (`$step` = unix_time / 30). */
    public static function code(string $secret, int $step): string
    {
        $hash = hash_hmac('sha1', pack('N2', $step >> 32, $step & 0xFFFFFFFF), self::base32Decode($secret), true);
        $offset = ord($hash[19]) & 0x0F;
        $value = ((ord($hash[$offset]) & 0x7F) << 24)
            | (ord($hash[$offset + 1]) << 16)
            | (ord($hash[$offset + 2]) << 8)
            | ord($hash[$offset + 3]);

        return str_pad((string) ($value % 1000000), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Verifica el código aceptando ±1 intervalo (desfase de reloj). Devuelve el intervalo que
     * coincidió, o null. `$notBeforeStep`: el último intervalo ya usado; uno igual o anterior
     * se rechaza para que un código no sirva dos veces.
     */
    public static function verify(string $secret, string $code, ?int $notBeforeStep = null, ?int $now = null): ?int
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';
        if (! preg_match('/^\d{6}$/', $code)) {
            return null;
        }

        $current = intdiv($now ?? time(), self::PERIOD);
        $found = null;
        for ($step = $current - 1; $step <= $current + 1; $step++) {
            // Sin cortar el ciclo al primer acierto: tiempo de respuesta constante.
            if (hash_equals(self::code($secret, $step), $code) && ($notBeforeStep === null || $step > $notBeforeStep)) {
                $found = $step;
            }
        }

        return $found;
    }

    public static function uri(string $account, string $secret, string $issuer = 'AgroLink'): string
    {
        return 'otpauth://totp/'.rawurlencode("$issuer:$account").'?'.http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => 6,
            'period' => self::PERIOD,
        ], '', '&', PHP_QUERY_RFC3986);
    }
}
