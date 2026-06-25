<?php

namespace App\Support;

use App\Services\Auth\JwtService;

class HrRequestContext
{
    public static function clientId(): int
    {
        $headerId = self::headerClientId();

        if (! function_exists('request') || ! request()) {
            return $headerId;
        }

        $request = request();
        $token = $request->attributes->get('hr_token');
        if (! is_array($token) && function_exists('app') && app()->bound(JwtService::class)) {
            $auth = (string) $request->header('Authorization', '');
            if (preg_match('/^\s*Bearer\s+(.+)\s*$/i', $auth, $matches)) {
                $token = app(JwtService::class)->verify(trim($matches[1]));
            }
        }

        if (! is_array($token)) {
            return $headerId;
        }

        $role = strtolower((string) ($token['role'] ?? ''));
        if (in_array($role, ['client', 'client_admin', 'agency_admin', 'employee'], true)) {
            return (int) ($token['clientId'] ?? 0);
        }

        if ($role === 'super_admin') {
            return $headerId > 0 ? $headerId : 0;
        }

        return $headerId;
    }

    private static function headerClientId(): int
    {
        if (function_exists('request') && request()) {
            $header = trim((string) request()->header('X-Client-Id', ''));

            return ctype_digit($header) ? (int) $header : 0;
        }

        $server = isset($_SERVER['HTTP_X_CLIENT_ID']) ? trim((string) $_SERVER['HTTP_X_CLIENT_ID']) : '';

        return ctype_digit($server) ? (int) $server : 0;
    }
}
