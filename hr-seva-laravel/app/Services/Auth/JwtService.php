<?php

namespace App\Services\Auth;

use App\Support\HrSevaDefaults;

class JwtService
{
    public function ttl(): int
    {
        return (int) config('hrseva.auth_token_ttl', 43200);
    }

    public function secret(): string
    {
        return (string) config('hrseva.app_secret');
    }

    public function sign(array $payload): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $h64 = $this->b64url(json_encode($header, JSON_UNESCAPED_UNICODE));
        $p64 = $this->b64url(json_encode($payload, JSON_UNESCAPED_UNICODE));
        $sig = hash_hmac('sha256', $h64.'.'.$p64, $this->secret(), true);

        return $h64.'.'.$p64.'.'.$this->b64url($sig);
    }

    public function verify(string $token): ?array
    {
        $parts = explode('.', trim($token));
        if (count($parts) !== 3) {
            return null;
        }
        [$h64, $p64, $s64] = $parts;
        $sig = $this->b64urlDecode($s64);
        if ($sig === '') {
            return null;
        }
        $calc = hash_hmac('sha256', $h64.'.'.$p64, $this->secret(), true);
        if (! hash_equals($calc, $sig)) {
            return null;
        }
        $payload = json_decode($this->b64urlDecode($p64), true);
        if (! is_array($payload)) {
            return null;
        }
        $exp = (int) ($payload['exp'] ?? 0);
        if ($exp <= 0 || $exp < time()) {
            return null;
        }

        return $payload;
    }

    public function issue(string $username, string $name, string $role, int $clientId = 0, string $empId = ''): string
    {
        $now = time();

        return $this->sign([
            'sub' => strtolower($username),
            'name' => $name,
            'role' => $role,
            'clientId' => $clientId,
            'empId' => strtoupper($empId),
            'iat' => $now,
            'exp' => $now + $this->ttl(),
        ]);
    }

    private function b64url(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private function b64urlDecode(string $txt): string
    {
        $pad = 4 - (strlen($txt) % 4);
        if ($pad < 4) {
            $txt .= str_repeat('=', $pad);
        }
        $x = base64_decode(strtr($txt, '-_', '+/'), true);

        return $x === false ? '' : $x;
    }
}
