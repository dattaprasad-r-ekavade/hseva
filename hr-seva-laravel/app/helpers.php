<?php

use App\Exceptions\LegacyApiResponseException;
use App\Support\HrHelpers;

if (! function_exists('bad')) {
    function bad(string $message): never
    {
        throw new LegacyApiResponseException(['detail' => $message], 400);
    }
}

if (! function_exists('j')) {
    function j(mixed $payload, int $status = 200): never
    {
        throw new LegacyApiResponseException($payload, $status);
    }
}

if (! function_exists('nf')) {
    function nf(string $message): never
    {
        j(['detail' => $message], 404);
    }
}

if (! function_exists('now_iso')) {
    function now_iso(): string
    {
        return HrHelpers::nowIso();
    }
}

if (! function_exists('s')) {
    function s(mixed $value, string $default = ''): string
    {
        return HrHelpers::s($value, $default);
    }
}

if (! function_exists('up')) {
    function up(mixed $value): string
    {
        return HrHelpers::up($value);
    }
}

if (! function_exists('f')) {
    function f(mixed $value, float $default = 0.0): float
    {
        return HrHelpers::f($value, $default);
    }
}

if (! function_exists('b')) {
    function b(mixed $value): bool
    {
        return HrHelpers::b($value);
    }
}
