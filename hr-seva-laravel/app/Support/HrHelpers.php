<?php

namespace App\Support;

class HrHelpers
{
    public static function nowIso(): string
    {
        return gmdate('Y-m-d\TH:i:s\Z');
    }

    public static function s(mixed $v, string $default = ''): string
    {
        $x = trim((string) $v);

        return $x === '' ? $default : $x;
    }

    public static function up(mixed $v): string
    {
        return strtoupper(trim((string) $v));
    }

    public static function f(mixed $v, float $default = 0.0): float
    {
        return is_numeric($v) ? (float) $v : $default;
    }

    public static function b(mixed $v): bool
    {
        if (is_bool($v)) {
            return $v;
        }

        $x = strtolower(trim((string) $v));

        return in_array($x, ['1', 'true', 'yes', 'y'], true);
    }
}
