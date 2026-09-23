<?php
// 官網區塊定義（後台表單產生器與前台渲染共用同一份定義）
// label 名稱｜icon 圖示｜desc 說明｜fields 內容欄位｜style 樣式預設｜sample 新增時的範例內容

$button = [
    ['key' => 'label', 'type' => 'text', 'label' => '按鈕文字'],
    ['key' => 'url', 'type' => 'url', 'label' => '連結'],
    ['key' => 'style', 'type' => 'select', 'label' => '樣式', 'options' => ['primary' => '實心', 'outline' => '外框', 'ghost' => '文字連結'], 'default' => 'primary'],
];
$buttons = ['key' => 'buttons', 'type' => 'list', 'label' => '按鈕', 'max' => 2, 'itemLabel' => 'label', 'fields' => $button];
$eyebrow = ['key' => 'eyebrow', 'type' => 'text', 'label' => '小標', 'placeholder' => '例如 OUR STORY'];
$heading = ['key' => 'heading', 'type' => 'text', 'label' => '標題'];
$intro = ['key' => 'intro', 'type' => 'textarea', 'label' => '說明文字', 'rows' => 3];
$columns = ['key' => 'columns', 'type' => 'select', 'label' => '每列欄數', 'options' => ['2' => '2 欄', '3' => '3 欄', '4' => '4 欄'], 'default' => '3'];

$ph = static fn (int $n): string => '/assets/img/placeholder/ph-' . $n . '.svg';

return [
    'hero' => [
        'label' => '主視覺',
        'icon' => 'sparkles',
        'desc' => '大標題、背景圖與按鈕，適合放在頁面最上方。',
        'fields' => [
            ['key' => 'layout', 'type' => 'select', 'label' => '版型', 'options' => ['center' => '置中', 'left' => '靠左', 'split' => '左文右圖'], 'default' => 'center'],
            $eyebrow,
            ['key' => 'title', 'type' => 'textarea', 'label' => '主標題', 'rows' => 2],
            ['key' => 'subtitle', 'type' => 'textarea', 'label' => '副標題', 'rows' => 3],
            $buttons,
            ['key' => 'image', 'type' => 'image', 'label' => '背景圖片', 'help' => '版型為「左文右圖」時，這張圖會顯示在右側。'],
            ['key' => 'overlay', 'type' => 'range', 'label' => '背景遮罩', 'min' => 0, 'max' => 90, 'step' => 5, 'default' => 40, 'unit' => '%'],
            ['key' => 'height', 'type' => 'select', 'label' => '高度', 'options' => ['full' => '滿版', 'lg' => '大', 'md' => '中', 'sm' => '小'], 'default' => 'lg'],
            ['key' => 'parallax', 'type' => 'toggle', 'label' => '視差滾動效果', 'default' => true],
            ['key' => 'titleEffect', 'type' => 'select', 'label' => '標題動畫', 'options' => ['chars' => '逐字浮現', 'fade' => '淡入', 'none' => '無'], 'default' => 'chars'],
            ['key' => 'scrollHint', 'type' => 'toggle', 'label' => '顯示「向下捲動」提示', 'default' => true],
        ],
        'style' => ['bg' => 'dark', 'padding' => 'none', 'width' => 'wide'],
        'sample' => [
            'eyebrow' => 'NEW SECTION',
            'title' => "在這裡寫下\n吸引目光的大標題",
            'subtitle' => '用一兩句話說明你的品牌、服務或這一頁的重點。',
            'buttons' => [['label' => '了解更多', 'url' => '#', 'style' => 'primary']],
            'image' => $ph(1),
        ],
    ],

    'text' => [
        'label' => '文字',
        'icon' => 'type',
        'desc' => '標題與段落文字，可加粗、連結與條列。',
        'fields' => [
            $eyebrow,
            $heading,
            ['key' => 'body', 'type' => 'richtext', 'label' => '內文'],
            $buttons,
        ],
        'style' => ['width' => 'narrow'],
        'sample' => [
            'eyebrow' => 'ABOUT',
            'heading' => '段落標題',
            'body' => '<p>在這裡輸入內文。可以使用<strong>粗體</strong>、<em>斜體</em>、條列與連結，讓內容更好閱讀。</p>',
        ],
    ],

    'imageText' => [
        'label' => '圖文並排',
        'icon' => 'panels-top-left',
        'desc' => '一張圖片搭配標題、內文與按鈕。',
        'fields' => [
            ['key' => 'image', 'type' => 'image', 'label' => '圖片'],
            ['key' => 'imagePosition', 'type' => 'select', 'label' => '圖片位置', 'options' => ['left' => '左側', 'right' => '右側'], 'default' => 'left'],
            ['key' => 'imageShape', 'type' => 'select', 'label' => '圖片形狀', 'options' => ['rounded' => '圓角', 'arch' => '拱形', 'circle' => '圓形', 'square' => '直角'], 'default' => 'rounded'],
            ['key' => 'hover', 'type' => 'select', 'label' => '滑鼠懸停效果', 'options' => ['zoom' => '放大', 'tilt' => '3D 傾斜', 'lift' => '浮起', 'none' => '無'], 'default' => 'zoom'],
            $eyebrow,
            $heading,
            ['key' => 'body', 'type' => 'richtext', 'label' => '內文'],
            $buttons,
        ],
        'style' => ['align' => 'left'],
        'sample' => [
            'image' => $ph(2),
            'eyebrow' => 'STORY',
            'heading' => '圖文區塊標題',
            'body' => '<p>搭配一張有氛圍的照片，說一段品牌故事、介紹一項服務，或分享一個理念。</p>',
            'buttons' => [['label' => '閱讀更多', 'url' => '#', 'style' => 'outline']],
        ],
    ],

    'features' => [
        'label' => '特色圖示',
        'icon' => 'layers',
        'desc' => '以圖示呈現 3–4 個重點特色或服務。',
        'fields' => [
            $eyebrow, $heading, $intro, $columns,
            ['key' => 'cardStyle', 'type' => 'select', 'label' => '卡片樣式', 'options' => ['cards' => '卡片', 'outline' => '外框', 'plain' => '無框'], 'default' => 'cards'],
            ['key' => 'items', 'type' => 'list', 'label' => '項目', 'max' => 12, 'itemLabel' => 'title', 'fields' => [
                ['key' => 'icon', 'type' => 'icon', 'label' => '圖示', 'default' => 'sparkles'],
                ['key' => 'title', 'type' => 'text', 'label' => '標題'],
                ['key' => 'text', 'type' => 'textarea', 'label' => '說明', 'rows' => 3],
                ['key' => 'url', 'type' => 'url', 'label' => '連結（選填）'],
            ]],
        ],
        'sample' => [
            'eyebrow' => 'FEATURES',
            'heading' => '我們的特色',
            'items' => [
                ['icon' => 'sparkles', 'title' => '特色一', 'text' => '簡短描述這個特色帶給顧客的價值。'],
                ['icon' => 'heart', 'title' => '特色二', 'text' => '簡短描述這個特色帶給顧客的價值。'],
                ['icon' => 'star', 'title' => '特色三', 'text' => '簡短描述這個特色帶給顧客的價值。'],
            ],
        ],
    ],

    'cards' => [
        'label' => '圖片卡片',
        'icon' => 'square-stack',
        'desc' => '服務、商品或作品的圖片卡片列表。',
        'fields' => [
            $eyebrow, $heading, $intro, $columns,
            ['key' => 'hover', 'type' => 'select', 'label' => '滑鼠懸停效果', 'options' => ['zoom' => '圖片放大', 'lift' => '浮起', 'tilt' => '3D 傾斜', 'none' => '無'], 'default' => 'zoom'],
            ['key' => 'items', 'type' => 'list', 'label' => '卡片', 'max' => 24, 'itemLabel' => 'title', 'fields' => [
                ['key' => 'image', 'type' => 'image', 'label' => '圖片'],
                ['key' => 'tag', 'type' => 'text', 'label' => '標籤'],
                ['key' => 'title', 'type' => 'text', 'label' => '標題'],
                ['key' => 'text', 'type' => 'textarea', 'label' => '說明', 'rows' => 3],
                ['key' => 'url', 'type' => 'url', 'label' => '連結'],
                ['key' => 'buttonLabel', 'type' => 'text', 'label' => '連結文字', 'default' => '查看更多'],
            ]],
        ],
        'sample' => [
            'eyebrow' => 'SERVICES',
            'heading' => '服務項目',
            'items' => [
                ['image' => $ph(3), 'tag' => '分類', 'title' => '項目一', 'text' => '一句話介紹這個項目。'],
                ['image' => $ph(4), 'tag' => '分類', 'title' => '項目二', 'text' => '一句話介紹這個項目。'],
                ['image' => $ph(5), 'tag' => '分類', 'title' => '項目三', 'text' => '一句話介紹這個項目。'],
            ],
        ],
    ],

    'gallery' => [
        'label' => '相簿',
        'icon' => 'images',
        'desc' => '多張照片，點擊可放大瀏覽。',
        'fields' => [
            $eyebrow, $heading, $intro,
            ['key' => 'layout', 'type' => 'select', 'label' => '排列方式', 'options' => ['masonry' => '瀑布流', 'grid' => '整齊方格', 'carousel' => '橫向滑動'], 'default' => 'masonry'],
            $columns,
            ['key' => 'lightbox', 'type' => 'toggle', 'label' => '點擊放大瀏覽', 'default' => true],
            ['key' => 'items', 'type' => 'list', 'label' => '照片', 'max' => 60, 'itemLabel' => 'caption', 'fields' => [
                ['key' => 'image', 'type' => 'image', 'label' => '圖片'],
                ['key' => 'caption', 'type' => 'text', 'label' => '說明'],
            ]],
        ],
        'sample' => [
            'eyebrow' => 'GALLERY',
            'heading' => '相簿',
            'items' => [
                ['image' => $ph(1), 'caption' => ''], ['image' => $ph(4), 'caption' => ''], ['image' => $ph(6), 'caption' => ''],
                ['image' => $ph(2), 'caption' => ''], ['image' => $ph(5), 'caption' => ''], ['image' => $ph(3), 'caption' => ''],
            ],
        ],
    ],

    'stats' => [
        'label' => '數字亮點',
        'icon' => 'chart-column',
        'desc' => '捲動到畫面時數字會跳動計數。',
        'fields' => [
            $eyebrow, $heading,
            ['key' => 'countUp', 'type' => 'toggle', 'label' => '數字跳動動畫', 'default' => true],
            ['key' => 'items', 'type' => 'list', 'label' => '數字', 'max' => 8, 'itemLabel' => 'label', 'fields' => [
                ['key' => 'prefix', 'type' => 'text', 'label' => '前綴'],
                ['key' => 'value', 'type' => 'text', 'label' => '數字'],
                ['key' => 'suffix', 'type' => 'text', 'label' => '後綴', 'placeholder' => '+ 或 %'],
                ['key' => 'label', 'type' => 'text', 'label' => '說明'],
            ]],
        ],
        'style' => ['bg' => 'dark'],
        'sample' => [
            'items' => [
                ['value' => '1200', 'suffix' => '+', 'label' => '服務人次'],
                ['value' => '98', 'suffix' => '%', 'label' => '顧客滿意度'],
                ['value' => '36', 'suffix' => '', 'label' => '合作品牌'],
                ['value' => '5', 'suffix' => ' 年', 'label' => '品牌經營'],
            ],
        ],
    ],

    'testimonials' => [
        'label' => '顧客好評',
        'icon' => 'quote',
        'desc' => '顧客推薦與評價。',
        'fields' => [
            $eyebrow, $heading,
            ['key' => 'layout', 'type' => 'select', 'label' => '排列方式', 'options' => ['slider' => '橫向滑動', 'grid' => '方格'], 'default' => 'slider'],
            ['key' => 'items', 'type' => 'list', 'label' => '評價', 'max' => 20, 'itemLabel' => 'name', 'fields' => [
                ['key' => 'quote', 'type' => 'textarea', 'label' => '內容', 'rows' => 4],
                ['key' => 'name', 'type' => 'text', 'label' => '姓名'],
                ['key' => 'role', 'type' => 'text', 'label' => '身分／職稱'],
                ['key' => 'avatar', 'type' => 'image', 'label' => '頭像'],
                ['key' => 'rating', 'type' => 'select', 'label' => '星等', 'options' => ['5' => '★★★★★', '4' => '★★★★', '3' => '★★★', '0' => '不顯示'], 'default' => '5'],
            ]],
        ],
        'style' => ['bg' => 'alt'],
        'sample' => [
            'eyebrow' => 'VOICES',
            'heading' => '他們這樣說',
            'items' => [
                ['quote' => '在這裡放上顧客的真實回饋。', 'name' => '顧客 A', 'role' => '身分'],
                ['quote' => '在這裡放上顧客的真實回饋。', 'name' => '顧客 B', 'role' => '身分'],
                ['quote' => '在這裡放上顧客的真實回饋。', 'name' => '顧客 C', 'role' => '身分'],
            ],
        ],
    ],

    'faq' => [
        'label' => '常見問題',
        'icon' => 'circle-help',
        'desc' => '可展開收合的問答列表。',
        'fields' => [
            $eyebrow, $heading, $intro,
            ['key' => 'openFirst', 'type' => 'toggle', 'label' => '預設展開第一題', 'default' => true],
            ['key' => 'items', 'type' => 'list', 'label' => '問答', 'max' => 40, 'itemLabel' => 'q', 'fields' => [
                ['key' => 'q', 'type' => 'text', 'label' => '問題'],
                ['key' => 'a', 'type' => 'textarea', 'label' => '回答', 'rows' => 4],
            ]],
        ],
        'style' => ['width' => 'narrow'],
        'sample' => [
            'eyebrow' => 'FAQ',
            'heading' => '常見問題',
            'items' => [
                ['q' => '問題一？', 'a' => '在這裡輸入回答。'],
                ['q' => '問題二？', 'a' => '在這裡輸入回答。'],
            ],
        ],
    ],

    'cta' => [
        'label' => '行動呼籲',
        'icon' => 'megaphone',
        'desc' => '醒目的標語與按鈕，引導訪客採取行動。',
        'fields' => [
            $eyebrow, $heading,
            ['key' => 'text', 'type' => 'textarea', 'label' => '說明文字', 'rows' => 3],
            $buttons,
            ['key' => 'variant', 'type' => 'select', 'label' => '樣式', 'options' => ['banner' => '橫幅', 'card' => '浮動卡片', 'glow' => '光暈漸層'], 'default' => 'glow'],
        ],
        'style' => ['bg' => 'primary'],
        'sample' => [
            'heading' => '準備好開始了嗎？',
            'text' => '一句話鼓勵訪客行動，例如預約、加入會員或聯絡我們。',
            'buttons' => [['label' => '立即聯絡', 'url' => '#contact', 'style' => 'primary']],
        ],
    ],

    'contact' => [
        'label' => '聯絡表單',
        'icon' => 'mail',
        'desc' => '聯絡資訊、地圖與留言表單（留言會通知到後台與 LINE）。',
        'fields' => [
            $eyebrow, $heading, $intro,
            ['key' => 'showForm', 'type' => 'toggle', 'label' => '顯示留言表單', 'default' => true],
            ['key' => 'askPhone', 'type' => 'toggle', 'label' => '表單詢問電話', 'default' => true],
            ['key' => 'submitLabel', 'type' => 'text', 'label' => '送出按鈕文字', 'default' => '送出訊息'],
            ['key' => 'successMessage', 'type' => 'text', 'label' => '送出成功訊息', 'default' => '謝謝你的訊息！我們會盡快回覆。'],
            ['key' => 'address', 'type' => 'textarea', 'label' => '地址', 'rows' => 2],
            ['key' => 'phone', 'type' => 'text', 'label' => '電話'],
            ['key' => 'email', 'type' => 'text', 'label' => 'Email'],
            ['key' => 'hours', 'type' => 'textarea', 'label' => '營業時間', 'rows' => 3],
            ['key' => 'map', 'type' => 'text', 'label' => 'Google 地圖', 'help' => '輸入地址，或貼上 Google 地圖「嵌入地圖」的網址。留空則不顯示地圖。'],
            ['key' => 'social', 'type' => 'toggle', 'label' => '顯示社群圖示（取自連結頁）', 'default' => true],
        ],
        'style' => ['anchor' => 'contact', 'align' => 'left'],
        'sample' => [
            'eyebrow' => 'CONTACT',
            'heading' => '聯絡我們',
            'intro' => '有任何問題或合作想法，歡迎留言給我們。',
            'email' => 'hello@example.com',
        ],
    ],

    'video' => [
        'label' => '影片',
        'icon' => 'play',
        'desc' => '嵌入 YouTube 或 Vimeo 影片。',
        'fields' => [
            $eyebrow, $heading, $intro,
            ['key' => 'url', 'type' => 'url', 'label' => '影片網址', 'placeholder' => 'https://www.youtube.com/watch?v=...'],
            ['key' => 'aspect', 'type' => 'select', 'label' => '比例', 'options' => ['16-9' => '16:9 橫式', '4-3' => '4:3', '1-1' => '1:1 方形', '9-16' => '9:16 直式'], 'default' => '16-9'],
        ],
        'sample' => ['heading' => '影片標題', 'url' => ''],
    ],

    'timeline' => [
        'label' => '時間軸',
        'icon' => 'history',
        'desc' => '品牌歷程或流程步驟，隨捲動依序出現。',
        'fields' => [
            $eyebrow, $heading, $intro,
            ['key' => 'items', 'type' => 'list', 'label' => '事件', 'max' => 30, 'itemLabel' => 'title', 'fields' => [
                ['key' => 'date', 'type' => 'text', 'label' => '時間／步驟'],
                ['key' => 'title', 'type' => 'text', 'label' => '標題'],
                ['key' => 'text', 'type' => 'textarea', 'label' => '說明', 'rows' => 3],
            ]],
        ],
        'style' => ['width' => 'narrow'],
        'sample' => [
            'eyebrow' => 'JOURNEY',
            'heading' => '我們的旅程',
            'items' => [
                ['date' => '2024', 'title' => '起點', 'text' => '品牌誕生的故事。'],
                ['date' => '2025', 'title' => '成長', 'text' => '一個重要的里程碑。'],
                ['date' => '2026', 'title' => '現在', 'text' => '我們正在做的事。'],
            ],
        ],
    ],

    'marquee' => [
        'label' => '跑馬燈',
        'icon' => 'gallery-horizontal',
        'desc' => '無限循環滾動的文字，滑鼠移上去會暫停。',
        'fields' => [
            ['key' => 'items', 'type' => 'list', 'label' => '文字', 'max' => 20, 'itemLabel' => 'text', 'fields' => [
                ['key' => 'text', 'type' => 'text', 'label' => '文字'],
            ]],
            ['key' => 'separator', 'type' => 'text', 'label' => '分隔符號', 'default' => '✦'],
            ['key' => 'speed', 'type' => 'select', 'label' => '速度', 'options' => ['slow' => '慢', 'normal' => '中', 'fast' => '快'], 'default' => 'normal'],
            ['key' => 'size', 'type' => 'select', 'label' => '文字大小', 'options' => ['sm' => '小', 'md' => '中', 'lg' => '大'], 'default' => 'md'],
            ['key' => 'outline', 'type' => 'toggle', 'label' => '交錯使用鏤空字', 'default' => true],
            ['key' => 'reverse', 'type' => 'toggle', 'label' => '反方向滾動', 'default' => false],
        ],
        'style' => ['padding' => 'sm', 'width' => 'full', 'anim' => 'fade-in'],
        'sample' => [
            'items' => [['text' => 'aftermoonF'], ['text' => '關鍵字一'], ['text' => '關鍵字二'], ['text' => '關鍵字三']],
        ],
    ],

    'logos' => [
        'label' => '合作夥伴',
        'icon' => 'award',
        'desc' => '合作品牌、媒體報導或客戶 Logo。',
        'fields' => [
            $eyebrow, $heading,
            ['key' => 'grayscale', 'type' => 'toggle', 'label' => '灰階顯示（滑鼠移上變彩色）', 'default' => true],
            ['key' => 'scroll', 'type' => 'toggle', 'label' => '自動橫向滾動', 'default' => false],
            ['key' => 'items', 'type' => 'list', 'label' => 'Logo', 'max' => 40, 'itemLabel' => 'name', 'fields' => [
                ['key' => 'image', 'type' => 'image', 'label' => 'Logo 圖片'],
                ['key' => 'name', 'type' => 'text', 'label' => '名稱'],
                ['key' => 'url', 'type' => 'url', 'label' => '連結（選填）'],
            ]],
        ],
        'style' => ['padding' => 'md'],
        'sample' => ['eyebrow' => 'PARTNERS', 'heading' => '合作夥伴', 'items' => [['name' => '夥伴 A'], ['name' => '夥伴 B'], ['name' => '夥伴 C'], ['name' => '夥伴 D']]],
    ],

    'pricing' => [
        'label' => '方案價格',
        'icon' => 'badge-dollar-sign',
        'desc' => '服務方案與價格比較。',
        'fields' => [
            $eyebrow, $heading, $intro,
            ['key' => 'items', 'type' => 'list', 'label' => '方案', 'max' => 6, 'itemLabel' => 'name', 'fields' => [
                ['key' => 'name', 'type' => 'text', 'label' => '方案名稱'],
                ['key' => 'badge', 'type' => 'text', 'label' => '標章', 'placeholder' => '例如 最受歡迎'],
                ['key' => 'price', 'type' => 'text', 'label' => '價格'],
                ['key' => 'period', 'type' => 'text', 'label' => '單位', 'placeholder' => '/ 月'],
                ['key' => 'desc', 'type' => 'textarea', 'label' => '說明', 'rows' => 2],
                ['key' => 'features', 'type' => 'textarea', 'label' => '包含內容（一行一項）', 'rows' => 5],
                ['key' => 'highlight', 'type' => 'toggle', 'label' => '醒目顯示', 'default' => false],
                ['key' => 'buttonLabel', 'type' => 'text', 'label' => '按鈕文字', 'default' => '選擇方案'],
                ['key' => 'buttonUrl', 'type' => 'url', 'label' => '按鈕連結'],
            ]],
        ],
        'sample' => [
            'eyebrow' => 'PRICING',
            'heading' => '方案價格',
            'items' => [
                ['name' => '基本方案', 'price' => 'NT$ 990', 'period' => '/ 次', 'desc' => '適合第一次體驗。', 'features' => "內容一\n內容二"],
                ['name' => '進階方案', 'badge' => '最受歡迎', 'price' => 'NT$ 2,490', 'period' => '/ 月', 'desc' => '最多人選擇。', 'features' => "內容一\n內容二\n內容三", 'highlight' => true],
                ['name' => '尊榮方案', 'price' => 'NT$ 5,990', 'period' => '/ 季', 'desc' => '完整的專屬服務。', 'features' => "內容一\n內容二\n內容三\n內容四"],
            ],
        ],
    ],

    'team' => [
        'label' => '團隊成員',
        'icon' => 'users',
        'desc' => '成員照片、職稱與介紹。',
        'fields' => [
            $eyebrow, $heading, $intro, $columns,
            ['key' => 'items', 'type' => 'list', 'label' => '成員', 'max' => 40, 'itemLabel' => 'name', 'fields' => [
                ['key' => 'photo', 'type' => 'image', 'label' => '照片'],
                ['key' => 'name', 'type' => 'text', 'label' => '姓名'],
                ['key' => 'role', 'type' => 'text', 'label' => '職稱'],
                ['key' => 'bio', 'type' => 'textarea', 'label' => '介紹', 'rows' => 3],
                ['key' => 'url', 'type' => 'url', 'label' => '連結（選填）'],
            ]],
        ],
        'sample' => [
            'eyebrow' => 'TEAM',
            'heading' => '團隊成員',
            'items' => [
                ['photo' => $ph(6), 'name' => '成員姓名', 'role' => '職稱', 'bio' => '一句話介紹。'],
                ['photo' => $ph(2), 'name' => '成員姓名', 'role' => '職稱', 'bio' => '一句話介紹。'],
                ['photo' => $ph(5), 'name' => '成員姓名', 'role' => '職稱', 'bio' => '一句話介紹。'],
            ],
        ],
    ],

    'links' => [
        'label' => '連結按鈕',
        'icon' => 'link',
        'desc' => '顯示「連結頁」已發布的連結按鈕與社群圖示。',
        'fields' => [
            $eyebrow, $heading, $intro,
            ['key' => 'columns', 'type' => 'select', 'label' => '每列欄數', 'options' => ['1' => '1 欄', '2' => '2 欄', '3' => '3 欄'], 'default' => '2'],
            ['key' => 'showSocials', 'type' => 'toggle', 'label' => '顯示社群圖示', 'default' => true],
        ],
        'style' => ['width' => 'narrow'],
        'sample' => ['eyebrow' => 'LINKS', 'heading' => '找到我們'],
    ],

    'member' => [
        'label' => '會員專區',
        'icon' => 'user-plus',
        'desc' => 'LINE／Google 登入按鈕；會員登入後顯示歡迎訊息。',
        'fields' => [
            $eyebrow,
            ['key' => 'heading', 'type' => 'text', 'label' => '標題（未登入）', 'default' => '加入會員'],
            ['key' => 'text', 'type' => 'textarea', 'label' => '說明（未登入）', 'rows' => 3],
            ['key' => 'welcome', 'type' => 'text', 'label' => '標題（已登入）', 'default' => '歡迎回來，{name}', 'help' => '{name} 會替換成會員名稱。'],
            ['key' => 'welcomeText', 'type' => 'textarea', 'label' => '說明（已登入）', 'rows' => 3],
            ['key' => 'buttonLabel', 'type' => 'text', 'label' => '會員中心按鈕文字', 'default' => '前往會員中心'],
        ],
        'style' => ['bg' => 'alt', 'width' => 'narrow'],
        'sample' => [
            'eyebrow' => 'MEMBERSHIP',
            'heading' => '加入會員',
            'text' => '使用 LINE 或 Google 一鍵登入，即可收到最新消息與會員專屬優惠。',
            'welcomeText' => '感謝你成為我們的會員，最新消息會透過 LINE 通知你。',
        ],
    ],

    'html' => [
        'label' => '自訂 HTML',
        'icon' => 'code',
        'desc' => '嵌入第三方程式碼（預約系統、表單、社群貼文等）。',
        'fields' => [
            ['key' => 'code', 'type' => 'code', 'label' => 'HTML 程式碼', 'help' => '只有管理員可以編輯。請只貼上可信任來源的程式碼。'],
        ],
        'sample' => ['code' => '<div style="text-align:center;padding:24px;border:1px dashed currentColor;border-radius:12px">在這裡貼上嵌入程式碼</div>'],
    ],

    'spacer' => [
        'label' => '間隔線',
        'icon' => 'separator-horizontal',
        'desc' => '區塊之間的留白或裝飾分隔線。',
        'fields' => [
            ['key' => 'size', 'type' => 'select', 'label' => '高度', 'options' => ['sm' => '小', 'md' => '中', 'lg' => '大', 'xl' => '特大'], 'default' => 'md'],
            ['key' => 'divider', 'type' => 'select', 'label' => '裝飾', 'options' => ['none' => '無（留白）', 'line' => '細線', 'dots' => '圓點', 'stars' => '星月', 'wave' => '波浪'], 'default' => 'stars'],
        ],
        'style' => ['padding' => 'none', 'anim' => 'fade-in'],
        'sample' => [],
    ],
];
