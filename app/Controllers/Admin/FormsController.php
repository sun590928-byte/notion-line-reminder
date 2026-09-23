<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\DB;
use App\Core\Request;

final class FormsController extends AdminBase
{
    private const PER_PAGE = 30;

    public function index(): void
    {
        $filter = Request::query('filter') === 'unread' ? 'unread' : 'all';
        $page = max(1, (int) Request::query('page', '1'));
        $where = $filter === 'unread' ? '`is_read` = 0' : '1 = 1';
        $total = (int) DB::value("SELECT COUNT(*) FROM {form_submissions} WHERE {$where}");
        $this->page('forms', '表單訊息', [
            'rows' => DB::all("SELECT * FROM {form_submissions} WHERE {$where} ORDER BY `id` DESC LIMIT " . self::PER_PAGE . ' OFFSET ' . (($page - 1) * self::PER_PAGE)),
            'filter' => $filter,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / self::PER_PAGE)),
            'total' => $total,
            'unread' => (int) DB::value('SELECT COUNT(*) FROM {form_submissions} WHERE `is_read` = 0'),
        ], 'forms');
    }

    public function read(array $p): void
    {
        $this->api();
        DB::update('form_submissions', ['is_read' => Request::bool('read') ? 1 : 0], '`id` = ?', [(int) $p['id']]);
        json_response(['ok' => true]);
    }

    public function delete(array $p): void
    {
        $this->api();
        DB::delete('form_submissions', '`id` = ?', [(int) $p['id']]);
        json_response(['ok' => true]);
    }
}
