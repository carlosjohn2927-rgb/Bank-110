<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Totp — RFC 6238 Time-based One-Time Password + RFC 4648 Base32.
 *
 * Pure PHP, no extensions or external services required. Used for
 * authenticator-app two-factor authentication (Google Authenticator,
 * Authy, 1Password, etc.).
 */
class Totp {

    const PERIOD = 30;
    const DIGITS = 6;
    const ALGO   = 'sha1';

    /** Generate a cryptographically-secure 20-byte secret, Base32-encoded. */
    public static function generate_secret($bytes = 20)
    {
        return self::base32_encode(random_bytes($bytes));
    }

    /** Current 6-digit code for a secret at a given Unix time. */
    public static function code($secret, $time = NULL, $period = self::PERIOD, $digits = self::DIGITS)
    {
        $key = self::base32_decode($secret);
        if ($key === '' || $key === FALSE) return NULL;
        $counter = floor(($time !== NULL ? (int)$time : time()) / $period);
        return self::hotp($key, $counter, $digits);
    }

    /**
     * Verify a code, allowing ±$window time steps to account for clock drift.
     * Uses a constant-time comparison.
     */
    public static function verify($code, $secret, $window = 1, $time = NULL, $period = self::PERIOD)
    {
        $code = preg_replace('/\s+/', '', (string)$code);
        if (!preg_match('/^\d{6}$/', $code)) return FALSE;
        $t = $time !== NULL ? (int)$time : time();
        for ($i = -$window; $i <= $window; $i++) {
            $expected = self::code($secret, $t + ($i * $period), $period);
            if ($expected !== NULL && hash_equals($expected, $code)) return TRUE;
        }
        return FALSE;
    }

    /** otpauth:// provisioning URI for QR codes / manual entry. */
    public static function provisioning_uri($secret, $account, $issuer = 'NorthWest')
    {
        $label = rawurlencode($issuer.':'.$account);
        $query = http_build_query(array(
            'secret'    => $secret,
            'issuer'    => $issuer,
            'algorithm' => strtoupper(self::ALGO),
            'digits'    => self::DIGITS,
            'period'    => self::PERIOD,
        ));
        return 'otpauth://totp/'.$label.'?'.$query;
    }

    /** Generate a set of one-time recovery codes (8 groups of 6 chars). */
    public static function generate_backup_codes($count = 8)
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no 0/O/1/I
        $codes = array();
        for ($i = 0; $i < $count; $i++) {
            $code = '';
            for ($j = 0; $j < 10; $j++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
                if ($j === 4) $code .= '-';
            }
            $codes[] = $code;
        }
        return $codes;
    }

    /** Hash backup codes for storage (one-way; codes shown only once). */
    public static function hash_backup_codes(array $codes)
    {
        return password_hash(implode(',', $codes), PASSWORD_DEFAULT);
    }

    /** Verify a supplied backup code against the stored hash. */
    public static function verify_backup_code($code, $hash)
    {
        if (!$hash) return FALSE;
        $code = strtoupper(trim((string)$code));
        return password_verify($code, $hash);
    }

    /* -------------------- HOTP (RFC 4226) -------------------- */

    private static function hotp($key, $counter, $digits = 6)
    {
        // 8-byte big-endian counter
        $bin = pack('N*', 0).pack('N*', $counter);
        $hash = hash_hmac(self::ALGO, $bin, $key, TRUE);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $part = substr($hash, $offset, 4);
        $value = unpack('N', $part)[1] & 0x7FFFFFFF;
        return str_pad((string)($value % pow(10, $digits)), $digits, '0', STR_PAD_LEFT);
    }

    /* -------------------- Base32 (RFC 4648, no padding) -------------------- */

    private static $b32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function base32_encode($data)
    {
        if ($data === '') return '';
        $out = '';
        $bits = 0; $value = 0;
        for ($i = 0, $n = strlen($data); $i < $n; $i++) {
            $value = ($value << 8) | ord($data[$i]);
            $bits += 8;
            while ($bits >= 5) {
                $bits -= 5;
                $out .= self::$b32[($value >> $bits) & 31];
            }
        }
        if ($bits > 0) {
            $out .= self::$b32[($value << (5 - $bits)) & 31];
        }
        return $out;
    }

    public static function base32_decode($data)
    {
        $data = strtoupper(preg_replace('/[^A-Z2-7]/i', '', (string)$data));
        if ($data === '') return '';
        $map = array_flip(str_split(self::$b32));
        $out = ''; $bits = 0; $value = 0;
        for ($i = 0, $n = strlen($data); $i < $n; $i++) {
            if (!isset($map[$data[$i]])) return FALSE;
            $value = ($value << 5) | $map[$data[$i]];
            $bits += 5;
            if ($bits >= 8) {
                $bits -= 8;
                $out .= chr(($value >> $bits) & 0xFF);
            }
        }
        return $out;
    }
}
