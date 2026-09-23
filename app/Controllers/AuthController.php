<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Crypto;
use App\Core\ErrorHandler;
use App\Core\Request;
use App\Core\Session;
use App\Services\Members;
use App\Services\Notifier;
use App\Services\OAuth;

/** 會員 LINE / Google 登入流程 */
final class AuthController
{
    public function start(array $p): void
    {
        $provider = (string) $p['provider'];
        if (!in_array($provider, OAuth::enabled(), true)) {
            abort(404, '這個登入方式尚未開放');
        }
        // 先回到正式網域，確保回呼時 session cookie 一致
        $canonical = parse_url(base_url(), PHP_URL_HOST);
        if (is_string($canonical) && strcasecmp($canonical, (string) strtok(Request::host(), ':')) !== 0) {
            redirect(absolute_url('/auth/' . $provider) . (($_SERVER['QUERY_STRING'] ?? '') !== '' ? '?' . $_SERVER['QUERY_STRING'] : ''));
        }
        $link = Request::query('link') === '1';
        if ($link && !Auth::member()) {
            redirect('/login');
        }
        Session::start();
        $state = Crypto::token(16);
        $nonce = Crypto::token(16);
        $verifier = Crypto::base64url(random_bytes(48));
        Session::set('oauth', [
            'provider' => $provider,
            'state' => $state,
            'nonce' => $nonce,
            'verifier' => $verifier,
            'return' => safe_return(Request::query('return'), '/member'),
            'link' => $link,
            'ts' => time(),
        ]);
        redirect(OAuth::authorizeUrl($provider, $state, $nonce, $verifier));
    }

    public function callback(array $p): void
    {
        $provider = (string) $p['provider'];
        Session::startIfExists();
        $o = Session::pull('oauth');
        if (!is_array($o) || ($o['provider'] ?? '') !== $provider || time() - (int) ($o['ts'] ?? 0) > 900) {
            $this->fail('登入逾時或頁面已過期，請再試一次。');
        }
        if (Request::query('error') !== '') {
            $this->fail('已取消登入。');
        }
        if (!hash_equals((string) $o['state'], Request::query('state')) || Request::query('code') === '') {
            $this->fail('登入驗證失敗，請再試一次。');
        }

        try {
            $profile = OAuth::exchange($provider, Request::query('code'), (string) $o['verifier'], (string) $o['nonce']);
        } catch (\Throwable $e) {
            ErrorHandler::log($e);
            $this->fail('登入失敗，請稍後再試。');
        }

        $current = Auth::member();
        $existing = Members::findByIdentity($provider, $profile['uid']);
        $isNew = false;

        if (!empty($o['link']) && $current) {
            // 會員中心「綁定其他登入方式」
            if ($existing !== null && $existing !== $current['id']) {
                flash('error', '這個 ' . OAuth::LABELS[$provider] . ' 帳號已經綁定其他會員。');
                redirect('/member');
            }
            Members::attachIdentity($current['id'], $provider, $profile);
            $memberId = $current['id'];
            flash('ok', '已成功綁定 ' . OAuth::LABELS[$provider] . '。');
        } elseif ($existing !== null) {
            $member = Members::find($existing);
            if (!$member || $member['status'] !== 'active') {
                $this->fail('這個帳號目前無法登入，請聯絡網站管理員。');
            }
            Members::attachIdentity($existing, $provider, $profile);
            $memberId = $existing;
        } else {
            if (!OAuth::config()['allowRegistration']) {
                $this->fail('目前暫停開放新會員註冊。');
            }
            $memberId = Members::create($provider, $profile);
            $isNew = true;
        }

        Members::recordLogin($memberId);
        Auth::loginMember($memberId);
        if ($isNew) {
            flash('ok', '歡迎加入！已為你建立會員帳號。');
            Notifier::newMember($memberId, $provider);
        }
        redirect(safe_return($o['return'] ?? '/member', '/member'));
    }

    private function fail(string $message): never
    {
        flash('error', $message);
        redirect('/login');
    }
}
