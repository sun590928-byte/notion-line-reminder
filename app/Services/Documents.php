<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\DB;

/**
 * 草稿／發布機制（類似 Wix：後台永遠編輯草稿，按「發布」才會更新公開版本）。
 * 文件：links（連結頁）、site（官方網站）
 */
final class Documents
{
    public const NAMES = ['links', 'site'];
    private const KEEP_REVISIONS = 30;

    private static array $cache = [];

    public static function normalize(string $name, mixed $doc): array
    {
        return $name === 'links' ? LinkPage::normalize($doc) : SiteDoc::normalize($doc);
    }

    public static function get(string $name): ?array
    {
        if (!array_key_exists($name, self::$cache)) {
            $row = DB::one('SELECT * FROM {documents} WHERE `name` = ?', [$name]);
            if ($row) {
                $row['draft'] = $row['draft'] !== null ? json_decode((string) $row['draft'], true) : null;
                $row['published'] = $row['published'] !== null ? json_decode((string) $row['published'], true) : null;
                $row['rev'] = (int) $row['rev'];
            }
            self::$cache[$name] = $row;
        }
        return self::$cache[$name];
    }

    public static function draft(string $name): array
    {
        $row = self::get($name);
        return is_array($row['draft'] ?? null) ? $row['draft'] : self::normalize($name, $name === 'links' ? LinkPage::defaults() : SiteDoc::defaults());
    }

    public static function published(string $name): ?array
    {
        $row = self::get($name);
        return is_array($row['published'] ?? null) ? $row['published'] : null;
    }

    /** 草稿與公開版本是否不同 */
    public static function hasChanges(string $name): bool
    {
        $row = self::get($name);
        if (!$row) {
            return false;
        }
        return self::encode($row['draft']) !== self::encode($row['published']);
    }

    private static function encode(mixed $doc): string
    {
        return (string) json_encode($doc, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /** 安裝時建立文件 */
    public static function init(string $name, array $doc, bool $publish): void
    {
        $doc = self::normalize($name, $doc);
        $json = self::encode($doc);
        DB::upsert('documents', ['name' => $name], [
            'draft' => $json,
            'published' => $publish ? $json : null,
            'rev' => 1,
            'updated_at' => DB::now(),
            'published_at' => $publish ? DB::now() : null,
            'published_by' => 0,
        ]);
        unset(self::$cache[$name]);
    }

    /**
     * 儲存草稿（樂觀鎖定：rev 不符代表其他視窗已修改過）
     * @return array{rev:int,doc:array}
     */
    public static function saveDraft(string $name, mixed $doc, int $baseRev, bool $force = false): array
    {
        $clean = self::normalize($name, $doc);
        $json = self::encode($clean);
        $row = self::get($name);
        if (!$row) {
            self::init($name, $clean, false);
            return ['rev' => 1, 'doc' => $clean];
        }
        if ($force) {
            DB::run('UPDATE {documents} SET `draft` = ?, `rev` = `rev` + 1, `updated_at` = ? WHERE `name` = ?', [$json, DB::now(), $name]);
        } else {
            $n = DB::run('UPDATE {documents} SET `draft` = ?, `rev` = `rev` + 1, `updated_at` = ? WHERE `name` = ? AND `rev` = ?', [$json, DB::now(), $name, $baseRev])->rowCount();
            if ($n === 0) {
                throw new DocumentConflict('內容已在其他視窗或裝置被修改。');
            }
        }
        unset(self::$cache[$name]);
        return ['rev' => (int) DB::value('SELECT `rev` FROM {documents} WHERE `name` = ?', [$name]), 'doc' => $clean];
    }

    public static function publish(string $name, int $adminId, string $note = ''): void
    {
        $row = self::get($name) ?? throw new \RuntimeException('文件不存在');
        $json = self::encode($row['draft']);
        DB::transaction(static function () use ($name, $json, $adminId, $note) {
            DB::update('documents', ['published' => $json, 'published_at' => DB::now(), 'published_by' => $adminId], '`name` = ?', [$name]);
            DB::insert('revisions', ['doc' => $name, 'data' => $json, 'note' => mb_substr($note, 0, 250), 'admin_id' => $adminId, 'created_at' => DB::now()]);
        });
        // 只保留最近的發布紀錄
        $keep = DB::all('SELECT `id` FROM {revisions} WHERE `doc` = ? ORDER BY `id` DESC LIMIT ' . self::KEEP_REVISIONS, [$name]);
        if (count($keep) === self::KEEP_REVISIONS) {
            DB::delete('revisions', '`doc` = ? AND `id` < ?', [$name, (int) end($keep)['id']]);
        }
        unset(self::$cache[$name]);
    }

    /** 捨棄草稿，回到目前公開版本 */
    public static function discard(string $name): void
    {
        $row = self::get($name);
        if (!$row || $row['published'] === null) {
            return;
        }
        DB::run('UPDATE {documents} SET `draft` = `published`, `rev` = `rev` + 1, `updated_at` = ? WHERE `name` = ?', [DB::now(), $name]);
        unset(self::$cache[$name]);
    }

    public static function revisions(string $name): array
    {
        return DB::all('SELECT r.`id`, r.`note`, r.`created_at`, a.`display_name` AS admin_name FROM {revisions} r LEFT JOIN {admins} a ON a.`id` = r.`admin_id` WHERE r.`doc` = ? ORDER BY r.`id` DESC LIMIT 30', [$name]);
    }

    /** 把某次發布的內容還原成草稿（不會直接公開） */
    public static function restore(string $name, int $revisionId): void
    {
        $data = DB::value('SELECT `data` FROM {revisions} WHERE `id` = ? AND `doc` = ?', [$revisionId, $name]);
        if (!is_string($data)) {
            throw new \RuntimeException('找不到這個版本');
        }
        $json = self::encode(self::normalize($name, json_decode($data, true)));
        DB::run('UPDATE {documents} SET `draft` = ?, `rev` = `rev` + 1, `updated_at` = ? WHERE `name` = ?', [$json, DB::now(), $name]);
        unset(self::$cache[$name]);
    }
}
