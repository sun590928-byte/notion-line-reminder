<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Crypto;
use App\Core\Http;
use App\Core\Settings;

/**
 * 會員登入：LINE Login v2.1 與 Google（OpenID Connect）。
 * 使用授權碼流程＋state（防 CSRF）＋nonce（防重放）＋PKCE。
 */
final class OAuth
{
    public const LABELS = ['line' => 'LINE', 'google' => 'Google'];

    public static function config(): array
    {
        $c = Settings::get('auth', []);
        $c = is_array($c) ? $c : [];
        $c['line'] = array_replace(['enabled' => false, 'channelId' => '', 'channelSecret' => '', 'botPrompt' => 'aggressive', 'requestEmail' => false], (array) ($c['line'] ?? []));
        $c['google'] = array_replace(['enabled' => false, 'clientId' => '', 'clientSecret' => ''], (array) ($c['google'] ?? []));
        $c['allowRegistration'] = (bool) ($c['allowRegistration'] ?? true);
        return $c;
    }

    /** 已啟用且設定完整的登入方式 */
    public static function enabled(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $c = self::config();
        $out = [];
        if ($c['line']['enabled'] && $c['line']['channelId'] !== '' && $c['line']['channelSecret'] !== '') {
            $out[] = 'line';
        }
        if ($c['google']['enabled'] && $c['google']['clientId'] !== '' && $c['google']['clientSecret'] !== '') {
            $out[] = 'google';
        }
        return $cache = $out;
    }

    public static function redirectUri(string $provider): string
    {
        return absolute_url('/auth/' . $provider . '/callback');
    }

    public static function authorizeUrl(string $provider, string $state, string $nonce, string $verifier): string
    {
        $c = self::config()[$provider];
        $challenge = Crypto::base64url(hash('sha256', $verifier, true));
        if ($provider === 'line') {
            $params = [
                'response_type' => 'code',
                'client_id' => $c['channelId'],
                'redirect_uri' => self::redirectUri('line'),
                'state' => $state,
                'scope' => 'profile openid' . ($c['requestEmail'] ? ' email' : ''),
                'nonce' => $nonce,
                'code_challenge' => $challenge,
                'code_challenge_method' => 'S256',
            ];
            // 登入後引導加入 LINE 官方帳號好友（需在 LINE Developers 將官方帳號連結到 Login Channel）
            if (in_array($c['botPrompt'], ['normal', 'aggressive'], true)) {
                $params['bot_prompt'] = $c['botPrompt'];
            }
            return 'https://access.line.me/oauth2/v2.1/authorize?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        }
        $params = [
            'response_type' => 'code',
            'client_id' => $c['clientId'],
            'redirect_uri' => self::redirectUri('google'),
            'scope' => 'openid email profile',
            'state' => $state,
            'nonce' => $nonce,
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
            'prompt' => 'select_account',
        ];
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * 以授權碼換取使用者資料
     * @return array{uid:string,name:string,email:?string,avatar:?string,friend:?bool}
     */
    public static function exchange(string $provider, string $code, string $verifier, string $nonce): array
    {
        return $provider === 'line' ? self::line($code, $verifier, $nonce) : self::google($code, $verifier, $nonce);
    }

    private static function line(string $code, string $verifier, string $nonce): array
    {
        $c = self::config()['line'];
        $token = Http::request('POST', 'https://api.line.me/oauth2/v2.1/token', ['form' => [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => self::redirectUri('line'),
            'client_id' => $c['channelId'],
            'client_secret' => Crypto::decrypt($c['channelSecret']),
            'code_verifier' => $verifier,
        ]]);
        if (!$token['ok'] || empty($token['json']['id_token'])) {
            throw new \RuntimeException('LINE 取得權杖失敗：' . Http::errorMessage($token));
        }
        // 由 LINE 伺服器驗證 ID Token 的簽章、有效期限、aud 與 nonce
        $verify = Http::request('POST', 'https://api.line.me/oauth2/v2.1/verify', ['form' => [
            'id_token' => $token['json']['id_token'],
            'client_id' => $c['channelId'],
            'nonce' => $nonce,
        ]]);
        $claims = $verify['json'] ?? [];
        if (!$verify['ok'] || empty($claims['sub'])) {
            throw new \RuntimeException('LINE ID Token 驗證失敗：' . Http::errorMessage($verify));
        }
        $friend = null;
        if (!empty($token['json']['access_token'])) {
            $fs = Http::request('GET', 'https://api.line.me/friendship/v1/status', ['headers' => ['Authorization: Bearer ' . $token['json']['access_token']]]);
            if ($fs['ok'] && isset($fs['json']['friendFlag'])) {
                $friend = (bool) $fs['json']['friendFlag'];
            }
        }
        return [
            'uid' => (string) $claims['sub'],
            'name' => (string) ($claims['name'] ?? 'LINE 使用者'),
            'email' => isset($claims['email']) ? (string) $claims['email'] : null,
            'avatar' => isset($claims['picture']) ? (string) $claims['picture'] : null,
            'friend' => $friend,
        ];
    }

    private static function google(string $code, string $verifier, string $nonce): array
    {
        $c = self::config()['google'];
        $token = Http::request('POST', 'https://oauth2.googleapis.com/token', ['form' => [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => self::redirectUri('google'),
            'client_id' => $c['clientId'],
            'client_secret' => Crypto::decrypt($c['clientSecret']),
            'code_verifier' => $verifier,
        ]]);
        if (!$token['ok'] || empty($token['json']['id_token'])) {
            throw new \RuntimeException('Google 取得權杖失敗：' . Http::errorMessage($token));
        }
        // ID Token 是伺服器直接透過 HTTPS 向 Google 取得，依 Google 文件可只驗證內容欄位
        $claims = self::decodeJwt((string) $token['json']['id_token']);
        $aud = $claims['aud'] ?? '';
        $audOk = is_array($aud) ? in_array($c['clientId'], $aud, true) : $aud === $c['clientId'];
        if (!in_array($claims['iss'] ?? '', ['https://accounts.google.com', 'accounts.google.com'], true) || !$audOk) {
            throw new \RuntimeException('Google ID Token 來源不符');
        }
        if ((int) ($claims['exp'] ?? 0) < time() - 60) {
            throw new \RuntimeException('Google ID Token 已過期');
        }
        if (!hash_equals($nonce, (string) ($claims['nonce'] ?? ''))) {
            throw new \RuntimeException('Google nonce 不符');
        }
        $email = !empty($claims['email_verified']) && isset($claims['email']) ? (string) $claims['email'] : null;
        return [
            'uid' => (string) $claims['sub'],
            'name' => (string) ($claims['name'] ?? ($email ? strstr($email, '@', true) : 'Google 使用者')),
            'email' => $email,
            'avatar' => isset($claims['picture']) ? (string) $claims['picture'] : null,
            'friend' => null,
        ];
    }

    private static function decodeJwt(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new \RuntimeException('ID Token 格式錯誤');
        }
        $payload = json_decode(Crypto::base64urlDecode($parts[1]), true);
        if (!is_array($payload) || empty($payload['sub'])) {
            throw new \RuntimeException('ID Token 內容錯誤');
        }
        return $payload;
    }
}
