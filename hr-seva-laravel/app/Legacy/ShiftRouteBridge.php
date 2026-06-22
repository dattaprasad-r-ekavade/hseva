<?php

namespace App\Legacy;

use App\Exceptions\LegacyApiResponseException;
use Illuminate\Http\Request;

class ShiftRouteBridge
{
    private static bool $booted = false;

    public static function handle(Request $request, string $path, string $method): never
    {
        self::boot();
        $GLOBALS['__hr_legacy_request_body'] = $request->getContent();
        $_SERVER['REQUEST_METHOD'] = $method;
        foreach ($request->query() as $key => $value) {
            $_GET[$key] = $value;
        }

        if (! shift_route_handle($path, $method)) {
            throw new LegacyApiResponseException(['detail' => 'Not Found'], 404);
        }

        throw new LegacyApiResponseException(['detail' => 'Not Found'], 404);
    }

    private static function boot(): void
    {
        if (self::$booted) {
            return;
        }
        require_once base_path('legacy/backend/shift_module.php');
        self::$booted = true;
    }
}
