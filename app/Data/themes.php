<?php
// 主題預設組合：site = 官方網站；links = 連結頁
return [
    'site' => [
        'moonlight' => [
            'label' => '月光',
            'primary' => '#2e2a5b', 'accent' => '#c9955b', 'bg' => '#faf7f2', 'surface' => '#f1ebe1',
            'text' => '#1f1b2d', 'muted' => '#6e6878', 'dark' => '#16142a',
            'headingFont' => 'Noto Serif TC', 'bodyFont' => 'Noto Sans TC', 'radius' => 14, 'buttonShape' => 'pill',
        ],
        'afternoon' => [
            'label' => '午後',
            'primary' => '#b4583a', 'accent' => '#e0a458', 'bg' => '#fbf5ec', 'surface' => '#f3e7d6',
            'text' => '#3a2a22', 'muted' => '#8a766a', 'dark' => '#2b1d17',
            'headingFont' => 'LXGW WenKai TC', 'bodyFont' => 'Noto Sans TC', 'radius' => 18, 'buttonShape' => 'pill',
        ],
        'minimal' => [
            'label' => '極簡',
            'primary' => '#111111', 'accent' => '#7c5cff', 'bg' => '#ffffff', 'surface' => '#f4f4f5',
            'text' => '#111111', 'muted' => '#6b7280', 'dark' => '#0b0b0f',
            'headingFont' => 'Noto Sans TC', 'bodyFont' => 'Noto Sans TC', 'radius' => 8, 'buttonShape' => 'rounded',
        ],
        'forest' => [
            'label' => '森林',
            'primary' => '#1f4d3a', 'accent' => '#c8a96a', 'bg' => '#f6f4ee', 'surface' => '#e9e6da',
            'text' => '#1b2a22', 'muted' => '#66756b', 'dark' => '#11231a',
            'headingFont' => 'Noto Serif TC', 'bodyFont' => 'Noto Sans TC', 'radius' => 12, 'buttonShape' => 'rounded',
        ],
        'ocean' => [
            'label' => '海洋',
            'primary' => '#0f4c75', 'accent' => '#3fb6c6', 'bg' => '#f5f9fc', 'surface' => '#e6f0f6',
            'text' => '#0e2233', 'muted' => '#5c7385', 'dark' => '#0a1a2a',
            'headingFont' => 'Poppins', 'bodyFont' => 'Noto Sans TC', 'radius' => 16, 'buttonShape' => 'pill',
        ],
        'sakura' => [
            'label' => '櫻花',
            'primary' => '#b0476e', 'accent' => '#e98aa0', 'bg' => '#fff8f9', 'surface' => '#fbe9ee',
            'text' => '#3d2530', 'muted' => '#8d6d78', 'dark' => '#2a1720',
            'headingFont' => 'Noto Serif TC', 'bodyFont' => 'Noto Sans TC', 'radius' => 20, 'buttonShape' => 'pill',
        ],
        'midnight' => [
            'label' => '午夜（深色）',
            'primary' => '#8b7bff', 'accent' => '#f4c979', 'bg' => '#0e0d18', 'surface' => '#17152a',
            'text' => '#eeeaf8', 'muted' => '#a39fbd', 'dark' => '#07060d',
            'headingFont' => 'Playfair Display', 'bodyFont' => 'Noto Sans TC', 'radius' => 14, 'buttonShape' => 'pill',
        ],
    ],
    'links' => [
        'twilight' => [
            'label' => '暮光',
            'bgType' => 'gradient', 'bgColor' => '#121331', 'bgColor2' => '#7a4a72', 'bgAngle' => 165, 'bgEffect' => 'stars',
            'textColor' => '#fff8ec', 'accent' => '#f4c979', 'font' => 'Noto Sans TC',
            'buttonStyle' => 'glass', 'buttonShape' => 'pill', 'buttonColor' => '#ffffff', 'buttonTextColor' => '#fff8ec', 'iconStyle' => 'mono',
        ],
        'afternoon' => [
            'label' => '午後',
            'bgType' => 'solid', 'bgColor' => '#f6efe4', 'bgColor2' => '#f3e0c8', 'bgAngle' => 180, 'bgEffect' => 'none',
            'textColor' => '#3b2f2a', 'accent' => '#b4583a', 'font' => 'LXGW WenKai TC',
            'buttonStyle' => 'fill', 'buttonShape' => 'rounded', 'buttonColor' => '#e9a87c', 'buttonTextColor' => '#2e211b', 'iconStyle' => 'mono',
        ],
        'minimal' => [
            'label' => '極簡白',
            'bgType' => 'solid', 'bgColor' => '#ffffff', 'bgColor2' => '#f4f4f5', 'bgAngle' => 180, 'bgEffect' => 'none',
            'textColor' => '#111111', 'accent' => '#7c5cff', 'font' => 'Noto Sans TC',
            'buttonStyle' => 'outline', 'buttonShape' => 'rounded', 'buttonColor' => '#111111', 'buttonTextColor' => '#111111', 'iconStyle' => 'brand',
        ],
        'midnight' => [
            'label' => '午夜極光',
            'bgType' => 'solid', 'bgColor' => '#0b0b12', 'bgColor2' => '#1b1433', 'bgAngle' => 180, 'bgEffect' => 'aurora',
            'textColor' => '#f5f5f7', 'accent' => '#8b7bff', 'font' => 'Poppins',
            'buttonStyle' => 'hard', 'buttonShape' => 'rounded', 'buttonColor' => '#8b7bff', 'buttonTextColor' => '#ffffff', 'iconStyle' => 'mono',
        ],
        'sakura' => [
            'label' => '櫻花',
            'bgType' => 'gradient', 'bgColor' => '#fde2e4', 'bgColor2' => '#e2ece9', 'bgAngle' => 180, 'bgEffect' => 'none',
            'textColor' => '#4a3f45', 'accent' => '#d27a93', 'font' => 'Noto Serif TC',
            'buttonStyle' => 'soft', 'buttonShape' => 'pill', 'buttonColor' => '#ffffff', 'buttonTextColor' => '#4a3f45', 'iconStyle' => 'brand',
        ],
        'forest' => [
            'label' => '森林',
            'bgType' => 'solid', 'bgColor' => '#1f3a2e', 'bgColor2' => '#2c5241', 'bgAngle' => 180, 'bgEffect' => 'none',
            'textColor' => '#f1efe7', 'accent' => '#e7d8b0', 'font' => 'Noto Serif TC',
            'buttonStyle' => 'fill', 'buttonShape' => 'rounded', 'buttonColor' => '#e7d8b0', 'buttonTextColor' => '#1f3a2e', 'iconStyle' => 'mono',
        ],
        'ocean' => [
            'label' => '海洋',
            'bgType' => 'gradient', 'bgColor' => '#0f4c75', 'bgColor2' => '#3fb6c6', 'bgAngle' => 160, 'bgEffect' => 'flow',
            'textColor' => '#ffffff', 'accent' => '#bdf3ff', 'font' => 'Noto Sans TC',
            'buttonStyle' => 'glass', 'buttonShape' => 'pill', 'buttonColor' => '#ffffff', 'buttonTextColor' => '#ffffff', 'iconStyle' => 'mono',
        ],
        'sunset' => [
            'label' => '夕陽',
            'bgType' => 'gradient', 'bgColor' => '#ff9a8b', 'bgColor2' => '#ff6a88', 'bgAngle' => 150, 'bgEffect' => 'flow',
            'textColor' => '#ffffff', 'accent' => '#ffe3a3', 'font' => 'Noto Sans TC',
            'buttonStyle' => 'soft', 'buttonShape' => 'pill', 'buttonColor' => '#ffffff', 'buttonTextColor' => '#7a2e3b', 'iconStyle' => 'brand',
        ],
    ],
];
