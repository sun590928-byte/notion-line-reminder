<?php
// 可選字體（Google Fonts 會依頁面實際用到的字自動分割下載，中文字體也不會太大）
// google：Google Fonts CSS2 參數；stack：CSS font-family
return [
    'Noto Sans TC' => [
        'label' => '思源黑體',
        'google' => ['Noto+Sans+TC:wght@400;500;700'],
        'stack' => "'Noto Sans TC', 'PingFang TC', 'Microsoft JhengHei', sans-serif",
    ],
    'Noto Serif TC' => [
        'label' => '思源宋體',
        'google' => ['Noto+Serif+TC:wght@500;700'],
        'stack' => "'Noto Serif TC', 'PingFang TC', 'Microsoft JhengHei', serif",
    ],
    'LXGW WenKai TC' => [
        'label' => '霞鶩文楷（手寫感）',
        'google' => ['LXGW+WenKai+TC:wght@400;700'],
        'stack' => "'LXGW WenKai TC', 'Noto Serif TC', serif",
    ],
    'Huninn' => [
        'label' => '粉圓體（圓潤可愛）',
        'google' => ['Huninn'],
        'stack' => "'Huninn', 'Noto Sans TC', sans-serif",
    ],
    'Playfair Display' => [
        'label' => 'Playfair Display＋宋體（優雅）',
        'google' => ['Playfair+Display:wght@500;700', 'Noto+Serif+TC:wght@500;700'],
        'stack' => "'Playfair Display', 'Noto Serif TC', serif",
    ],
    'Poppins' => [
        'label' => 'Poppins＋黑體（現代）',
        'google' => ['Poppins:wght@400;600', 'Noto+Sans+TC:wght@400;500;700'],
        'stack' => "'Poppins', 'Noto Sans TC', sans-serif",
    ],
    'system' => [
        'label' => '系統預設字體（載入最快）',
        'google' => [],
        'stack' => "-apple-system, BlinkMacSystemFont, 'Segoe UI', 'PingFang TC', 'Microsoft JhengHei', 'Noto Sans TC', sans-serif",
    ],
];
