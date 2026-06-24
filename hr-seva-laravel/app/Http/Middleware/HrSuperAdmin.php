<?php

namespace App\Http\Middleware;

use App\Exceptions\LegacyApiResponseException;
use Closure;
use Illuminate\Http\Request;

class HrSuperAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->attributes->get('hr_token', []);
        if (strtolower((string) ($token['role'] ?? '')) !== 'super_admin') {
            throw new LegacyApiResponseException(['detail' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}
