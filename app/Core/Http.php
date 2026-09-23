<?php
declare(strict_types=1);

namespace App\Core;

/** 對外 HTTP 請求（LINE / Google API） */
final class Http
{
    /** 本機測試用：設定後所有對外請求改由這個函式回應（正式環境不會設定） */
    public static ?\Closure $mock = null;

    /**
     * @param array{headers?:array,json?:mixed,form?:array,body?:string,timeout?:int} $opt
     * @return array{ok:bool,status:int,body:string,json:?array,error:?string}
     */
    public static function request(string $method, string $url, array $opt = []): array
    {
        if (self::$mock !== null) {
            return (self::$mock)($method, $url, $opt);
        }
        $headers = $opt['headers'] ?? [];
        $body = null;
        if (array_key_exists('json', $opt)) {
            $body = json_encode($opt['json'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $headers[] = 'Content-Type: application/json';
        } elseif (isset($opt['form'])) {
            $body = http_build_query($opt['form'], '', '&', PHP_QUERY_RFC3986);
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        } elseif (isset($opt['body'])) {
            $body = (string) $opt['body'];
        }
        $timeout = (int) ($opt['timeout'] ?? 15);
        $status = 0;
        $error = null;
        $resp = '';

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_USERAGENT => 'aftermoonF/' . AMF_VERSION,
            ]);
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }
            $result = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            if ($result === false) {
                $error = curl_error($ch) ?: '連線失敗';
            } else {
                $resp = (string) $result;
            }
        } else {
            $ctx = stream_context_create(['http' => [
                'method' => $method,
                'header' => implode("\r\n", array_merge($headers, ['User-Agent: aftermoonF/' . AMF_VERSION])),
                'content' => $body ?? '',
                'timeout' => $timeout,
                'ignore_errors' => true,
                'follow_location' => 0,
            ]]);
            $result = @file_get_contents($url, false, $ctx);
            if ($result === false) {
                $error = '連線失敗（主機未啟用 cURL）';
            } else {
                $resp = $result;
            }
            $statusLine = $http_response_header[0] ?? '';
            if (preg_match('#HTTP/\S+\s+(\d{3})#', $statusLine, $m)) {
                $status = (int) $m[1];
            }
        }

        $json = null;
        if ($resp !== '') {
            $decoded = json_decode($resp, true);
            $json = is_array($decoded) ? $decoded : null;
        }
        return ['ok' => $error === null && $status >= 200 && $status < 300, 'status' => $status, 'body' => $resp, 'json' => $json, 'error' => $error];
    }

    /** 從 API 回應中取出可讀的錯誤訊息 */
    public static function errorMessage(array $res): string
    {
        if ($res['error']) {
            return $res['error'];
        }
        $j = $res['json'] ?? [];
        $msg = $j['message'] ?? $j['error_description'] ?? $j['error'] ?? '';
        if (is_array($msg)) {
            $msg = json_encode($msg, JSON_UNESCAPED_UNICODE);
        }
        $details = '';
        if (!empty($j['details']) && is_array($j['details'])) {
            $details = '（' . implode('；', array_map(static fn ($d) => ($d['property'] ?? '') . ' ' . ($d['message'] ?? ''), $j['details'])) . '）';
        }
        return 'HTTP ' . $res['status'] . ($msg !== '' ? '：' . $msg : '') . $details;
    }
}
