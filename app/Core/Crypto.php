<?php
declare(strict_types=1);

namespace App\Core;

/** 加密金鑰儲存（AES-256-GCM）、簽章與隨機字串 */
final class Crypto
{
    private static function key(string $purpose): string
    {
        $appKey = (string) Config::get('app_key', '');
        if ($appKey === '') {
            throw new \RuntimeException('設定檔缺少 app_key');
        }
        return hash('sha256', $purpose . '|' . $appKey, true);
    }

    public static function encrypt(string $plain): string
    {
        if ($plain === '') {
            return '';
        }
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plain, 'aes-256-gcm', self::key('enc'), OPENSSL_RAW_DATA, $iv, $tag);
        if ($cipher === false) {
            throw new \RuntimeException('加密失敗');
        }
        return 'enc:' . base64_encode($iv . $tag . $cipher);
    }

    public static function decrypt(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        if (!str_starts_with($value, 'enc:')) {
            return $value;
        }
        $raw = base64_decode(substr($value, 4), true);
        if ($raw === false || strlen($raw) < 29) {
            return '';
        }
        $plain = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', self::key('enc'), OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));
        return $plain === false ? '' : $plain;
    }

    public static function sign(string $data): string
    {
        return hash_hmac('sha256', $data, self::key('sign'));
    }

    public static function token(int $bytes = 16): string
    {
        return bin2hex(random_bytes($bytes));
    }

    public static function base64url(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }

    public static function base64urlDecode(string $s): string
    {
        return (string) base64_decode(strtr($s, '-_', '+/') . str_repeat('=', (4 - strlen($s) % 4) % 4));
    }

    /** 金鑰只顯示末四碼 */
    public static function mask(string $secret): string
    {
        $len = strlen($secret);
        if ($len === 0) {
            return '';
        }
        return str_repeat('•', min(12, max(4, $len - 4))) . substr($secret, -4);
    }
}
