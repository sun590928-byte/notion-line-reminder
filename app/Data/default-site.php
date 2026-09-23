<?php
// 官網預設內容（安裝時建立為「未發布草稿」，官網預設隱藏）
use App\Services\Sections as S;

$ph = static fn (int $n): string => '/assets/img/placeholder/ph-' . $n . '.svg';

return [
    'theme' => ['preset' => 'moonlight'],
    'header' => [
        'showName' => true,
        'style' => 'auto',
        'showMember' => true,
    ],
    'footer' => [
        'about' => '在午後的光與夜晚的月色之間，收藏每一段值得被記住的時光。',
        'links' => [
            ['label' => '關於我們', 'url' => 'page:p_about'],
            ['label' => '服務項目', 'url' => 'page:p_services'],
            ['label' => '聯絡我們', 'url' => 'page:p_contact'],
        ],
        'showSocial' => true,
        'copyright' => '© {year} aftermoonF. All rights reserved.',
    ],
    'homeId' => 'p_home',
    'pages' => [
        [
            'id' => 'p_home',
            'slug' => 'home',
            'title' => '首頁',
            'showInNav' => true,
            'sections' => [
                S::make('hero', [
                    'layout' => 'center',
                    'eyebrow' => 'aftermoonF',
                    'title' => "讓每一段時光，\n都值得被好好收藏",
                    'subtitle' => '這是官網的主視覺。在後台點一下文字就能直接修改，換成你的品牌標語與介紹。',
                    'buttons' => [
                        ['label' => '認識我們', 'url' => 'page:p_about', 'style' => 'primary'],
                        ['label' => '聯絡我們', 'url' => 'page:p_contact', 'style' => 'outline'],
                    ],
                    'image' => '/assets/img/placeholder/hero.svg',
                    'height' => 'full',
                    'overlay' => 30,
                ], ['bg' => 'dark']),
                S::make('marquee', [
                    'items' => [['text' => 'aftermoonF'], ['text' => 'AFTERNOON'], ['text' => 'MOONLIGHT'], ['text' => '生活提案'], ['text' => '溫柔的日常']],
                ], ['bg' => 'accent']),
                S::make('imageText', [
                    'image' => $ph(2),
                    'imagePosition' => 'left',
                    'imageShape' => 'arch',
                    'eyebrow' => 'OUR STORY',
                    'heading' => '從一個午後開始的故事',
                    'body' => '<p>用一段文字介紹品牌的起源與理念：你為什麼開始、想帶給大家什麼樣的感受。</p><p>好的品牌故事不需要很長，但要真誠。</p>',
                    'buttons' => [['label' => '閱讀完整故事', 'url' => 'page:p_about', 'style' => 'outline']],
                ]),
                S::make('features', [
                    'eyebrow' => 'WHAT WE DO',
                    'heading' => '我們在意的三件事',
                    'intro' => '用三個重點，讓第一次來的訪客快速認識你。',
                    'items' => [
                        ['icon' => 'moon', 'title' => '用心', 'text' => '每一個細節都經過反覆琢磨，只為了剛剛好的體驗。'],
                        ['icon' => 'sparkles', 'title' => '質感', 'text' => '選擇好的材料與設計，讓日常多一點美好。'],
                        ['icon' => 'hand-heart', 'title' => '溫度', 'text' => '重視每一位朋友的回饋，陪你一起慢慢變好。'],
                    ],
                ], ['bg' => 'alt']),
                S::make('stats', [], ['bg' => 'dark']),
                S::make('cards', [
                    'eyebrow' => 'SERVICES',
                    'heading' => '服務項目',
                    'items' => [
                        ['image' => $ph(3), 'tag' => '服務', 'title' => '服務項目一', 'text' => '一句話說明這項服務能為顧客解決什麼。', 'url' => 'page:p_services'],
                        ['image' => $ph(4), 'tag' => '服務', 'title' => '服務項目二', 'text' => '一句話說明這項服務能為顧客解決什麼。', 'url' => 'page:p_services'],
                        ['image' => $ph(5), 'tag' => '服務', 'title' => '服務項目三', 'text' => '一句話說明這項服務能為顧客解決什麼。', 'url' => 'page:p_services'],
                    ],
                ]),
                S::make('gallery', ['eyebrow' => 'MOMENTS', 'heading' => '時光片刻']),
                S::make('testimonials', []),
                S::make('member', [], ['bg' => 'default']),
                S::make('cta', [
                    'heading' => '一起度過美好的時光',
                    'text' => '有任何想法或合作提案，都歡迎與我們聯繫。',
                    'buttons' => [
                        ['label' => '聯絡我們', 'url' => 'page:p_contact', 'style' => 'primary'],
                        ['label' => '加入 LINE 好友', 'url' => 'https://line.me/', 'style' => 'outline'],
                    ],
                ]),
            ],
        ],
        [
            'id' => 'p_about',
            'slug' => 'about',
            'title' => '關於我們',
            'showInNav' => true,
            'seoDescription' => '認識 aftermoonF 的品牌故事與理念。',
            'sections' => [
                S::make('hero', [
                    'eyebrow' => 'ABOUT',
                    'title' => '關於 aftermoonF',
                    'subtitle' => '在午後與月光之間，找到屬於自己的節奏。',
                    'buttons' => [],
                    'image' => $ph(1),
                    'height' => 'md',
                    'scrollHint' => false,
                ]),
                S::make('text', [
                    'eyebrow' => 'PHILOSOPHY',
                    'heading' => '品牌理念',
                    'body' => '<p>在這裡寫下品牌的理念與價值。可以分成幾個段落，說明你相信什麼、想為誰帶來什麼改變。</p><ul><li>我們相信的第一件事</li><li>我們相信的第二件事</li><li>我們相信的第三件事</li></ul>',
                ]),
                S::make('timeline', [], ['bg' => 'alt']),
                S::make('team', []),
                S::make('spacer', ['divider' => 'stars']),
                S::make('cta', [
                    'heading' => '想更認識我們嗎？',
                    'text' => '歡迎來信或加入 LINE 好友，第一時間收到最新消息。',
                    'buttons' => [['label' => '聯絡我們', 'url' => 'page:p_contact', 'style' => 'primary']],
                ]),
            ],
        ],
        [
            'id' => 'p_services',
            'slug' => 'services',
            'title' => '服務項目',
            'showInNav' => true,
            'seoDescription' => 'aftermoonF 提供的服務與方案。',
            'sections' => [
                S::make('hero', [
                    'eyebrow' => 'SERVICES',
                    'title' => '服務項目',
                    'subtitle' => '依照你的需求，選擇最適合的方案。',
                    'buttons' => [],
                    'image' => $ph(4),
                    'height' => 'md',
                    'scrollHint' => false,
                ]),
                S::make('cards', [
                    'eyebrow' => 'WHAT WE OFFER',
                    'heading' => '我們提供',
                    'hover' => 'tilt',
                    'items' => [
                        ['image' => $ph(3), 'tag' => '熱門', 'title' => '服務項目一', 'text' => '詳細說明這項服務的內容、適合對象與特色。'],
                        ['image' => $ph(5), 'tag' => '推薦', 'title' => '服務項目二', 'text' => '詳細說明這項服務的內容、適合對象與特色。'],
                        ['image' => $ph(6), 'tag' => '新推出', 'title' => '服務項目三', 'text' => '詳細說明這項服務的內容、適合對象與特色。'],
                    ],
                ]),
                S::make('pricing', [], ['bg' => 'alt']),
                S::make('faq', [
                    'eyebrow' => 'FAQ',
                    'heading' => '常見問題',
                    'items' => [
                        ['q' => '如何預約？', 'a' => '可以透過 LINE 官方帳號或聯絡表單預約，我們會盡快與你確認時間。'],
                        ['q' => '可以付款的方式有哪些？', 'a' => '在這裡說明付款方式，例如轉帳、信用卡或行動支付。'],
                        ['q' => '需要提前多久預約？', 'a' => '在這裡說明預約的注意事項。'],
                    ],
                ]),
                S::make('cta', [
                    'heading' => '找不到適合的方案？',
                    'text' => '告訴我們你的需求，我們會為你量身規劃。',
                    'buttons' => [['label' => '與我們聊聊', 'url' => 'page:p_contact', 'style' => 'primary']],
                ]),
            ],
        ],
        [
            'id' => 'p_contact',
            'slug' => 'contact',
            'title' => '聯絡我們',
            'showInNav' => true,
            'seoDescription' => '與 aftermoonF 聯繫、預約或合作洽談。',
            'sections' => [
                S::make('hero', [
                    'eyebrow' => 'CONTACT',
                    'title' => '聯絡我們',
                    'subtitle' => '留下訊息，我們會盡快回覆你。',
                    'buttons' => [],
                    'image' => $ph(6),
                    'height' => 'sm',
                    'scrollHint' => false,
                    'titleEffect' => 'fade',
                ]),
                S::make('contact', [
                    'heading' => '留言給我們',
                    'intro' => '合作提案、預約或任何問題都歡迎留言，送出後我們會收到 LINE 通知。',
                    'email' => 'hello@example.com',
                    'hours' => "週一至週五 10:00–18:00\n週末與國定假日休息",
                ]),
                S::make('links', ['heading' => '也可以在這裡找到我們'], ['bg' => 'alt']),
            ],
        ],
    ],
];
