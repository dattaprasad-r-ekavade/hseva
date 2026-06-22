<?php

namespace App\Legacy;

use App\Exceptions\LegacyApiResponseException;
use Illuminate\Http\Request;

class LegacyApiKernel
{
    private static bool $booted = false;

    public static function handle(Request $request): never
    {
        self::boot();

        $GLOBALS['__hr_legacy_request_body'] = $request->getContent();

        $_SERVER['REQUEST_METHOD'] = $request->method();
        $_SERVER['HTTP_AUTHORIZATION'] = $request->header('Authorization', '');
        $_SERVER['HTTP_X_CLIENT_ID'] = $request->header('X-Client-Id', '');
        $_SERVER['REMOTE_ADDR'] = $request->ip() ?? '127.0.0.1';

        $path = '/'.ltrim($request->path(), '/');

        try {
            legacy_api_dispatch($path, $request->method());
        } catch (LegacyApiResponseException $e) {
            throw $e;
        }

        throw new LegacyApiResponseException(['detail' => 'Not Found'], 404);
    }

    private static function boot(): void
    {
        if (self::$booted) {
            return;
        }

        if (! defined('STORAGE_DIR')) {
            define('STORAGE_DIR', storage_path('app/clients'));
        }
        if (! defined('CENTRAL_DB_PATH')) {
            define('CENTRAL_DB_PATH', STORAGE_DIR.'/app.db');
        }
        if (! defined('LEGACY_DB_PATH')) {
            define('LEGACY_DB_PATH', base_path('legacy/backend/app.db'));
        }

        require_once base_path('legacy/backend/api.php');

        init_db();
        self::$booted = true;
    }
}
