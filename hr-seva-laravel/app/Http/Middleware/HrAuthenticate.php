<?php

namespace App\Http\Middleware;

use App\Exceptions\LegacyApiResponseException;
use App\Services\Auth\JwtService;
use App\Services\Tenant\TenantManager;
use Closure;
use Illuminate\Http\Request;

class HrAuthenticate
{
    public function __construct(
        private JwtService $jwt,
        private TenantManager $tenants,
    ) {}

    public function handle(Request $request, Closure $next)
    {
        $token = $this->jwt->verify($this->bearer($request) ?? '');
        if (! $token) {
            throw new LegacyApiResponseException(['detail' => 'Unauthorized'], 401);
        }

        $request->attributes->set('hr_token', $token);
        $role = strtolower((string) ($token['role'] ?? ''));
        $clientId = (int) ($token['clientId'] ?? 0);

        if (in_array($role, ['client', 'employee'], true) && $clientId > 0) {
            $this->tenants->setClientId($clientId);
            $sub = client_subscription_access_state($clientId);
            if (empty($sub['active'])) {
                throw new LegacyApiResponseException([
                    'detail' => 'Subscription expired. Access denied.',
                    'reason' => $sub['reason'] ?? '',
                    'endDate' => $sub['endDate'] ?? null,
                ], 403);
            }
        } elseif ($role === 'super_admin') {
            $headerId = (int) $request->header('X-Client-Id', 0);
            if ($headerId > 0) {
                $this->tenants->setClientId($headerId);
            }
        }

        return $next($request);
    }

    private function bearer(Request $request): ?string
    {
        $h = (string) $request->header('Authorization', '');
        if (preg_match('/^\s*Bearer\s+(.+)\s*$/i', $h, $m)) {
            return trim($m[1]);
        }

        return null;
    }
}
