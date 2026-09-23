<?php
declare(strict_types=1);

namespace App\Services;

/** 官網區塊登錄表：定義、樣式欄位與資料清理 */
final class Sections
{
    private static ?array $defs = null;

    public static function all(): array
    {
        if (self::$defs === null) {
            self::$defs = require AMF_ROOT . '/app/Data/sections.php';
        }
        return self::$defs;
    }

    public static function get(string $type): ?array
    {
        return self::all()[$type] ?? null;
    }

    public const ANIMATIONS = [
        'fade-up' => '由下往上浮現',
        'fade-in' => '淡入',
        'slide-left' => '從左側滑入',
        'slide-right' => '從右側滑入',
        'zoom-in' => '放大浮現',
        'blur-in' => '模糊到清晰',
        'flip-up' => '3D 翻轉',
        'none' => '無動畫',
    ];

    /** 所有區塊共用的樣式欄位；區塊定義中的 style 會覆寫預設值 */
    public static function styleFields(string $type = ''): array
    {
        $def = $type !== '' ? (self::get($type)['style'] ?? []) : [];
        $fields = [
            ['key' => 'bg', 'type' => 'select', 'label' => '背景', 'options' => [
                'default' => '預設背景', 'alt' => '淺色底', 'primary' => '主色', 'dark' => '深色', 'accent' => '強調色', 'custom' => '自訂顏色', 'image' => '背景圖片',
            ], 'default' => 'default'],
            ['key' => 'bgColor', 'type' => 'color', 'label' => '背景顏色', 'default' => '#ffffff', 'showIf' => ['bg', 'custom']],
            ['key' => 'bgImage', 'type' => 'image', 'label' => '背景圖片', 'showIf' => ['bg', 'image']],
            ['key' => 'bgOverlay', 'type' => 'range', 'label' => '圖片遮罩', 'min' => 0, 'max' => 90, 'step' => 5, 'default' => 50, 'unit' => '%', 'showIf' => ['bg', 'image']],
            ['key' => 'bgFixed', 'type' => 'toggle', 'label' => '背景固定（視差感）', 'default' => false, 'showIf' => ['bg', 'image']],
            ['key' => 'padding', 'type' => 'select', 'label' => '上下間距', 'options' => ['none' => '無', 'sm' => '小', 'md' => '中', 'lg' => '大', 'xl' => '特大'], 'default' => 'lg'],
            ['key' => 'width', 'type' => 'select', 'label' => '內容寬度', 'options' => ['narrow' => '窄', 'normal' => '標準', 'wide' => '寬', 'full' => '滿版'], 'default' => 'normal'],
            ['key' => 'align', 'type' => 'select', 'label' => '標題對齊', 'options' => ['center' => '置中', 'left' => '靠左'], 'default' => 'center'],
            ['key' => 'anim', 'type' => 'select', 'label' => '捲動進場動畫', 'options' => self::ANIMATIONS, 'default' => 'fade-up'],
            ['key' => 'anchor', 'type' => 'text', 'label' => '錨點 ID', 'help' => '設定後可以用「#錨點」連到這個區塊，例如 contact（英文、數字、-）'],
            ['key' => 'hideOn', 'type' => 'select', 'label' => '顯示裝置', 'options' => ['none' => '全部顯示', 'mobile' => '手機不顯示', 'desktop' => '電腦不顯示'], 'default' => 'none'],
        ];
        foreach ($fields as &$f) {
            if (array_key_exists($f['key'], $def)) {
                $f['default'] = $def[$f['key']];
            }
        }
        return $fields;
    }

    public static function newId(string $prefix = 's'): string
    {
        return $prefix . '_' . substr(bin2hex(random_bytes(6)), 0, 10);
    }

    public static function normalize(mixed $section): ?array
    {
        if (!is_array($section)) {
            return null;
        }
        $type = (string) ($section['type'] ?? '');
        $def = self::get($type);
        if (!$def) {
            return null;
        }
        $id = (string) ($section['id'] ?? '');
        $style = Fields::normalize(self::styleFields($type), $section['style'] ?? []);
        $style['anchor'] = strtolower((string) preg_replace('/[^A-Za-z0-9_-]/', '', (string) $style['anchor']));
        return [
            'id' => preg_match('/^[A-Za-z0-9_-]{3,40}$/', $id) ? $id : self::newId(),
            'type' => $type,
            'hidden' => !empty($section['hidden']),
            'data' => Fields::normalize($def['fields'], $section['data'] ?? []),
            'style' => $style,
        ];
    }

    /** 建立新區塊（使用範例內容） */
    public static function make(string $type, array $data = [], array $style = []): array
    {
        $def = self::get($type) ?? throw new \InvalidArgumentException("未知區塊：{$type}");
        return self::normalize([
            'id' => self::newId(),
            'type' => $type,
            'data' => array_replace($def['sample'] ?? [], $data),
            'style' => $style,
        ]);
    }

    /** 給後台編輯器的定義（含樣式欄位） */
    public static function forEditor(): array
    {
        $out = [];
        foreach (self::all() as $type => $def) {
            $out[$type] = [
                'label' => $def['label'],
                'icon' => $def['icon'],
                'desc' => $def['desc'],
                'fields' => $def['fields'],
                'styleFields' => self::styleFields($type),
                'sample' => Fields::normalize($def['fields'], $def['sample'] ?? []),
            ];
        }
        return $out;
    }
}
