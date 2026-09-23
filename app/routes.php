<?php
declare(strict_types=1);

use App\Controllers\Admin;
use App\Controllers\ApiController;
use App\Controllers\AuthController;
use App\Controllers\LineWebhookController;
use App\Controllers\MemberController;
use App\Controllers\PreviewController;
use App\Controllers\PublicController;
use App\Core\Router;

return static function (Router $r): void {
    // ── 公開頁面 ─────────────────────────────
    $r->get('/', [PublicController::class, 'home']);
    $r->get('/links', [PublicController::class, 'links']);
    $r->get('/sitemap.xml', [PublicController::class, 'sitemap']);
    $r->get('/robots.txt', [PublicController::class, 'robots']);

    // ── 會員（LINE / Google 登入） ───────────
    $r->get('/login', [MemberController::class, 'login']);
    $r->get('/member', [MemberController::class, 'center']);
    $r->post('/member/profile', [MemberController::class, 'profile']);
    $r->post('/member/unlink', [MemberController::class, 'unlink']);
    $r->post('/logout', [MemberController::class, 'logout']);
    $r->get('/auth/{provider}', [AuthController::class, 'start']);
    $r->get('/auth/{provider}/callback', [AuthController::class, 'callback']);

    // ── 公開 API ─────────────────────────────
    $r->post('/api/click', [ApiController::class, 'click']);
    $r->post('/api/contact', [ApiController::class, 'contact']);
    $r->post('/api/line/webhook', [LineWebhookController::class, 'handle']);

    // ── 草稿預覽（僅管理員） ─────────────────
    $r->get('/preview/links', [PreviewController::class, 'links']);
    $r->get('/preview/site', [PreviewController::class, 'site']);
    $r->get('/preview/site/{slug}', [PreviewController::class, 'site']);

    $r->get('/install', static fn () => redirect('/admin'));

    // ── 後台 /admin ──────────────────────────
    $r->get('/admin', [Admin\DashboardController::class, 'index']);
    $r->get('/admin/login', [Admin\AuthController::class, 'form']);
    $r->post('/admin/login', [Admin\AuthController::class, 'login']);
    $r->post('/admin/logout', [Admin\AuthController::class, 'logout']);
    $r->get('/admin/links', [Admin\EditorController::class, 'links']);
    $r->get('/admin/site', [Admin\EditorController::class, 'site']);
    $r->get('/admin/media', [Admin\MediaController::class, 'index']);
    $r->get('/admin/members', [Admin\MembersController::class, 'index']);
    $r->get('/admin/members/{id}', [Admin\MembersController::class, 'show']);
    $r->get('/admin/forms', [Admin\FormsController::class, 'index']);
    $r->get('/admin/line', [Admin\LineController::class, 'index']);
    $r->get('/admin/settings', [Admin\SettingsController::class, 'index']);
    $r->get('/admin/account', [Admin\AccountController::class, 'index']);

    // 後台 JSON API（需登入＋CSRF）
    $r->post('/admin/api/doc/{name}/save', [Admin\EditorController::class, 'save']);
    $r->post('/admin/api/doc/{name}/publish', [Admin\EditorController::class, 'publish']);
    $r->post('/admin/api/doc/{name}/discard', [Admin\EditorController::class, 'discard']);
    $r->get('/admin/api/doc/{name}/revisions', [Admin\EditorController::class, 'revisions']);
    $r->post('/admin/api/doc/{name}/restore', [Admin\EditorController::class, 'restore']);
    $r->post('/admin/api/mode', [Admin\SettingsController::class, 'mode']);
    $r->post('/admin/api/settings', [Admin\SettingsController::class, 'save']);
    $r->get('/admin/api/media', [Admin\MediaController::class, 'list']);
    $r->post('/admin/api/media/upload', [Admin\MediaController::class, 'upload']);
    $r->post('/admin/api/media/{id}/update', [Admin\MediaController::class, 'update']);
    $r->post('/admin/api/media/{id}/delete', [Admin\MediaController::class, 'delete']);
    $r->post('/admin/api/members/{id}', [Admin\MembersController::class, 'update']);
    $r->post('/admin/api/members/{id}/delete', [Admin\MembersController::class, 'delete']);
    $r->post('/admin/api/members/{id}/message', [Admin\MembersController::class, 'message']);
    $r->post('/admin/api/forms/{id}/read', [Admin\FormsController::class, 'read']);
    $r->post('/admin/api/forms/{id}/delete', [Admin\FormsController::class, 'delete']);
    $r->post('/admin/api/line/settings', [Admin\LineController::class, 'saveSettings']);
    $r->post('/admin/api/line/test', [Admin\LineController::class, 'test']);
    $r->post('/admin/api/line/bind-code', [Admin\LineController::class, 'bindCode']);
    $r->post('/admin/api/line/receivers/{id}/remove', [Admin\LineController::class, 'removeReceiver']);
    $r->post('/admin/api/line/notify-test', [Admin\LineController::class, 'notifyTest']);
    $r->post('/admin/api/line/send', [Admin\LineController::class, 'send']);
    $r->post('/admin/api/account/password', [Admin\AccountController::class, 'password']);
    $r->post('/admin/api/account/admins', [Admin\AccountController::class, 'create']);
    $r->post('/admin/api/account/admins/{id}/delete', [Admin\AccountController::class, 'delete']);

    // ── 官網頁面（放在最後，避免覆蓋上方路由） ─
    $r->get('/{slug}', [PublicController::class, 'page']);
};
