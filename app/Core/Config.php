<?php
declare(strict_types=1);

namespace App\Core;

/** 讀取 config/config.php（由安裝程式產生） */
final class Config
{
    private static array $data = [];
    private static bool $fileExists = false;

    public static function path(): string
    {
        return AMF_ROOT . '/config/config.php';
    }

    public static function load(): void
    {
        $file = self::path();
        self::$fileExists = is_file($file);
        self::$data = self::$fileExists ? (array) require $file : [];
    }

    public static function installed(): bool
    {
        return self::$fileExists && !empty(self::$data['db']['driver']) && !empty(self::$data['app_key']);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::$data;
        foreach (explode('.', $key) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }
        return $value;
    }

    public static function set(string $key, mixed $value): void
    {
        $ref = &self::$data;
        foreach (explode('.', $key) as $part) {
            if (!isset($ref[$part]) || !is_array($ref[$part])) {
                $ref[$part] = [];
            }
            $ref = &$ref[$part];
        }
        $ref = $value;
    }

    /** 產生 config.php 內容 */
    public static function export(array $config): string
    {
        return "<?php\n// aftermoonF 設定檔（由安裝程式產生）。請勿公開或上傳到公開的 Git 儲存庫。\nreturn "
            . var_export($config, true) . ";\n";
    }
}
