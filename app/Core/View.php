<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    private static array $shared = [];

    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    public static function render(string $template, array $data = [], ?string $layout = null): string
    {
        $content = self::capture($template, $data);
        if ($layout !== null) {
            $content = self::capture('layouts/' . $layout, ['content' => $content] + $data);
        }
        return $content;
    }

    public static function capture(string $template, array $data = []): string
    {
        $file = AMF_ROOT . '/app/Views/' . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("找不到版型：{$template}");
        }
        return (static function (string $__file, array $__data): string {
            extract($__data, EXTR_SKIP);
            ob_start();
            try {
                include $__file;
            } catch (\Throwable $e) {
                ob_end_clean();
                throw $e;
            }
            return (string) ob_get_clean();
        })($file, $data + self::$shared);
    }
}
