<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\DB;
use App\Core\Request;
use App\Core\Session;
use App\Core\Settings;
use App\Core\View;
use App\Services\Documents;
use App\Services\LineBot;
use App\Services\LinkPage;
use App\Services\Members;
use App\Services\OAuth;
use App\Services\SiteRenderer;

/** 會員登入頁與會員中心 */
final class MemberController
{
    public function login(): void
    {
        if (Auth::member()) {
            redirect(safe_return(Request::query('return'), '/member'));
        }
        $this->render('會員登入', 'member/login', [
            'providers' => OAuth::enabled(),
            'return' => safe_return(Request::query('return'), '/member'),
            'flashes' => Session::flashes(),
        ]);
    }

    public function center(): void
    {
        $member = Auth::member();
        if (!$member) {
            redirect('/login?return=/member');
        }
        $identities = Members::identities($member['id']);
        $lineUid = $identities['line']['provider_uid'] ?? null;
        $contact = $lineUid ? LineBot::contact($lineUid) : null;
        $this->render('會員中心', 'member/center', [
            'member' => $member,
            'identities' => $identities,
            'providers' => OAuth::enabled(),
            'contact' => $contact,
            'addFriendUrl' => LineBot::addFriendUrl(),
            'flashes' => Session::flashes(),
        ]);
    }

    public function profile(): void
    {
        verify_csrf();
        $member = Auth::member() ?? redirect('/login');
        $name = mb_substr(trim(Request::str('display_name')), 0, 50);
        $email = trim(Request::str('email'));
        if ($name === '') {
            flash('error', '請填寫顯示名稱。');
            redirect('/member');
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Email 格式不正確。');
            redirect('/member');
        }
        DB::update('members', [
            'display_name' => $name,
            'email' => $email !== '' ? mb_substr($email, 0, 191) : null,
            'notify_line' => Request::bool('notify_line') ? 1 : 0,
        ], '`id` = ?', [$member['id']]);
        flash('ok', '已儲存會員資料。');
        redirect('/member');
    }

    public function unlink(): void
    {
        verify_csrf();
        $member = Auth::member() ?? redirect('/login');
        $provider = Request::str('provider');
        if (!isset(OAuth::LABELS[$provider])) {
            abort(400);
        }
        if (Members::detachIdentity($member['id'], $provider)) {
            flash('ok', '已解除綁定 ' . OAuth::LABELS[$provider] . '。');
        } else {
            flash('error', '至少需要保留一種登入方式。');
        }
        redirect('/member');
    }

    public function logout(): void
    {
        verify_csrf();
        Auth::logoutMember();
        Session::regenerate();
        redirect('/');
    }

    /** 官網公開時套用官網外觀；否則套用連結頁外觀 */
    private function render(string $title, string $view, array $data): void
    {
        header('Cache-Control: no-store');
        $content = View::capture($view, $data);
        if (Settings::mode() === 'website' && ($doc = Documents::published('site'))) {
            echo (new SiteRenderer($doc))->wrap($title, '<div class="auth-wrap">' . $content . '</div>', 'member-page');
            return;
        }
        echo View::render('member/shell', [
            'title' => $title . '｜' . Settings::site()['name'],
            'content' => $content,
            'doc' => Documents::published('links') ?? LinkPage::defaults(),
        ]);
    }
}
