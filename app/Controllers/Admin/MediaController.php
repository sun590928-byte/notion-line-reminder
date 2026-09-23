<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\DB;
use App\Core\Request;
use App\Services\Media;

final class MediaController extends AdminBase
{
    public function index(): void
    {
        $this->page('media', '媒體庫', [
            'initial' => Media::list(1),
            'uploadLimit' => ini_get('upload_max_filesize'),
            'count' => (int) DB::value('SELECT COUNT(*) FROM {media}'),
        ], 'media');
    }

    public function list(): void
    {
        json_response(['ok' => true] + Media::list(max(1, (int) Request::query('page', '1'))));
    }

    public function upload(): void
    {
        $this->api();
        try {
            json_response(['ok' => true, 'item' => Media::upload($_FILES['file'] ?? null)]);
        } catch (\RuntimeException $e) {
            json_response(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }

    public function update(array $p): void
    {
        $this->api();
        DB::update('media', ['alt' => mb_substr(Request::str('alt'), 0, 250)], '`id` = ?', [(int) $p['id']]);
        json_response(['ok' => true]);
    }

    public function delete(array $p): void
    {
        $this->api();
        Media::delete((int) $p['id']);
        json_response(['ok' => true]);
    }
}
