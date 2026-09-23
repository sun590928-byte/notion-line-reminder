<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Config;
use App\Core\DB;
use App\Core\Request;
use App\Core\Schema;
use App\Core\Settings;
use App\Core\View;
use App\Services\Documents;
use App\Services\LinkPage;
use App\Services\SiteDoc;

/** 首次安裝精靈：檢查環境 → 連接資料庫 → 建立資料表與預設內容 → 建立管理員 */
final class InstallController
{
    public const LOCK = '/storage/installed.lock';

    public function handle(): void
    {
        // 安裝過的網站若遺失設定檔，不允許任何人重新安裝（避免被接管）
        if (is_file(AMF_ROOT . self::LOCK)) {
            http_response_code(403);
            echo View::render('errors/error', [
                'code' => 403,
                'title' => '網站已安裝',
                'message' => '找不到 config/config.php。請從備份還原設定檔；若確定要重新安裝，請先刪除 storage/installed.lock。',
            ], 'bare');
            return;
        }
        $checks = $this->checks();
        $errors = [];
        $manualConfig = null;
        $input = [
            'db_driver' => extension_loaded('pdo_mysql') ? 'mysql' : 'sqlite',
            'db_host' => 'localhost',
            'db_port' => '3306',
            'db_name' => '',
            'db_user' => '',
            'db_prefix' => 'amf_',
            'site_name' => 'aftermoonF',
            'site_url' => Request::detectedBaseUrl(),
            'admin_user' => 'admin',
            'admin_name' => '管理員',
        ];

        if (Request::isPost()) {
            foreach (array_keys($input) as $k) {
                $input[$k] = trim((string) ($_POST[$k] ?? ''));
            }
            $password = (string) ($_POST['admin_pass'] ?? '');
            $errors = $this->validate($input, $password, (string) ($_POST['admin_pass2'] ?? ''));
            if (!$errors) {
                try {
                    $result = $this->install($input, $password);
                    if ($result !== true) {
                        $manualConfig = $result;
                    } else {
                        header('Location: ' . url('/admin?welcome=1'));
                        exit;
                    }
                } catch (\PDOException $e) {
                    $errors[] = '無法連接資料庫：' . $e->getMessage();
                } catch (\Throwable $e) {
                    $errors[] = '安裝失敗：' . $e->getMessage();
                }
            }
        }

        echo View::render('install/index', [
            'title' => '安裝 aftermoonF',
            'checks' => $checks,
            'errors' => $errors,
            'input' => $input,
            'manualConfig' => $manualConfig,
            'canInstall' => !in_array(false, array_column(array_filter($checks, static fn ($c) => $c['required']), 'ok'), true),
        ], 'bare');
    }

    private function checks(): array
    {
        $writable = static fn (string $dir): bool => is_dir(AMF_ROOT . '/' . $dir) ? is_writable(AMF_ROOT . '/' . $dir) : is_writable(dirname(AMF_ROOT . '/' . $dir));
        return [
            ['label' => 'PHP 8.1 以上（目前 ' . PHP_VERSION . '）', 'ok' => PHP_VERSION_ID >= 80100, 'required' => true],
            ['label' => 'PDO MySQL 或 PDO SQLite 擴充', 'ok' => extension_loaded('pdo_mysql') || extension_loaded('pdo_sqlite'), 'required' => true],
            ['label' => 'OpenSSL 擴充（金鑰加密）', 'ok' => extension_loaded('openssl'), 'required' => true],
            ['label' => 'mbstring 擴充（中文處理）', 'ok' => extension_loaded('mbstring'), 'required' => true],
            ['label' => 'cURL 擴充（LINE／Google 連線）', 'ok' => extension_loaded('curl'), 'required' => false],
            ['label' => 'fileinfo 擴充（圖片上傳檢查）', 'ok' => extension_loaded('fileinfo'), 'required' => false],
            ['label' => 'GD 擴充（圖片自動縮圖）', 'ok' => extension_loaded('gd'), 'required' => false],
            ['label' => 'DOM 擴充（文字編輯器安全過濾）', 'ok' => extension_loaded('dom'), 'required' => false],
            ['label' => 'config/ 資料夾可寫入', 'ok' => $writable('config'), 'required' => false],
            ['label' => 'storage/ 資料夾可寫入', 'ok' => $writable('storage'), 'required' => true],
            ['label' => 'public/uploads/ 資料夾可寫入', 'ok' => $writable('public/uploads'), 'required' => true],
        ];
    }

    private function validate(array $in, string $pass, string $pass2): array
    {
        $errors = [];
        if (!in_array($in['db_driver'], ['mysql', 'sqlite'], true)) {
            $errors[] = '請選擇資料庫類型';
        }
        if ($in['db_driver'] === 'mysql' && ($in['db_name'] === '' || $in['db_user'] === '')) {
            $errors[] = '請填寫資料庫名稱與使用者名稱（可在 Plesk「資料庫」頁面建立）';
        }
        if (!preg_match('/^[A-Za-z0-9_]{0,20}$/', $in['db_prefix'])) {
            $errors[] = '資料表前綴只能使用英文、數字與底線';
        }
        if ($in['site_name'] === '') {
            $errors[] = '請填寫網站名稱';
        }
        if (!preg_match('#^https?://[^\s/]+#i', $in['site_url'])) {
            $errors[] = '網站網址格式不正確';
        }
        if (!preg_match('/^[A-Za-z0-9_.@-]{3,50}$/', $in['admin_user'])) {
            $errors[] = '管理員帳號需為 3–50 個英文、數字或 _ . @ -';
        }
        if (strlen($pass) < 8) {
            $errors[] = '管理員密碼至少 8 個字元';
        } elseif ($pass !== $pass2) {
            $errors[] = '兩次輸入的密碼不一致';
        }
        return $errors;
    }

    /** @return true|string 成功回傳 true；設定檔無法寫入時回傳設定檔內容讓使用者手動建立 */
    private function install(array $in, string $password): bool|string
    {
        $db = $in['db_driver'] === 'sqlite'
            ? ['driver' => 'sqlite', 'path' => 'storage/database.sqlite', 'prefix' => $in['db_prefix']]
            : [
                'driver' => 'mysql',
                'host' => $in['db_host'] ?: 'localhost',
                'port' => (int) ($in['db_port'] ?: 3306),
                'database' => $in['db_name'],
                'username' => $in['db_user'],
                'password' => (string) ($_POST['db_pass'] ?? ''),
                'prefix' => $in['db_prefix'],
            ];
        $config = [
            'app_key' => bin2hex(random_bytes(32)),
            'db' => $db,
            'debug' => false,
            'timezone' => 'Asia/Taipei',
            'base_url' => '',
        ];
        Config::set('app_key', $config['app_key']);
        Config::set('db', $db);

        foreach (['storage/sessions', 'storage/logs', 'storage/cache', 'public/uploads'] as $dir) {
            if (!is_dir(AMF_ROOT . '/' . $dir)) {
                @mkdir(AMF_ROOT . '/' . $dir, 0775, true);
            }
        }

        DB::connect($db);
        $existing = false;
        try {
            $existing = (int) DB::value('SELECT COUNT(*) FROM {admins}') > 0;
        } catch (\Throwable) {
            $existing = false;
        }

        if (!$existing) {
            Schema::create();
            Settings::set('schema_version', Schema::VERSION);
            Settings::set('site', array_replace(Settings::site(), ['name' => $in['site_name'], 'url' => rtrim($in['site_url'], '/')]));
            Settings::set('mode', 'links');
            Settings::set('auth', [
                'allowRegistration' => true,
                'line' => ['enabled' => false, 'channelId' => '', 'channelSecret' => '', 'botPrompt' => 'aggressive', 'requestEmail' => false],
                'google' => ['enabled' => false, 'clientId' => '', 'clientSecret' => ''],
            ]);
            Settings::set('line_bot', [
                'channelSecret' => '', 'accessToken' => '', 'basicId' => '',
                'notifyNewMember' => true, 'notifyContact' => true, 'notifyNewFriend' => false,
            ]);

            $links = LinkPage::defaults();
            $links['profile']['name'] = $in['site_name'];
            Documents::init('links', $links, true);   // 連結頁：預設公開
            $site = SiteDoc::defaults();
            Documents::init('site', $site, false);    // 官方網站：未發布草稿（隱藏）

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $id = DB::insert('admins', [
                'username' => $in['admin_user'],
                'password_hash' => $hash,
                'display_name' => $in['admin_name'] ?: $in['admin_user'],
                'created_at' => DB::now(),
            ]);
            Auth::loginAdmin(['id' => $id, 'password_hash' => $hash], true);
        }

        $content = Config::export($config);
        @file_put_contents(AMF_ROOT . self::LOCK, date('c') . "\n");
        if (!@file_put_contents(Config::path(), $content, LOCK_EX)) {
            return $content;
        }
        @chmod(Config::path(), 0640);
        return true;
    }
}
