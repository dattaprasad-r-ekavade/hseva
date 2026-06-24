<?php

namespace App\Services\Shift;

class ShiftSupport
{
    public function parseDate(string $value, string $field): string
    {
        $x = s($value);
        if ($x === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $x)) {
            bad($field.' must be YYYY-MM-DD');
        }

        return $x;
    }

    public function normTime(?string $value): ?string
    {
        $x = trim((string) ($value ?? ''));
        if ($x === '') {
            return null;
        }
        if (! preg_match('/^\d{2}:\d{2}$/', $x)) {
            bad('time must be HH:MM');
        }

        return $x;
    }

    public function durationMinutes(string $start, string $end): int
    {
        [$sh, $sm] = array_map('intval', explode(':', $start));
        [$eh, $em] = array_map('intval', explode(':', $end));
        $s = $sh * 60 + $sm;
        $e = $eh * 60 + $em;
        if ($e <= $s) {
            $e += 1440;
        }

        return $e - $s;
    }

    public function weekDays(string $start): array
    {
        $out = [];
        $t = strtotime($start.' 00:00:00 UTC');
        if ($t === false) {
            return [];
        }
        for ($i = 0; $i < 7; $i++) {
            $out[] = gmdate('Y-m-d', $t + ($i * 86400));
        }

        return $out;
    }

    public function dateTimeRange(string $date, ?string $start, ?string $end): array
    {
        if (! $start || ! $end) {
            return [null, null];
        }
        $s = $date.'T'.$start.':00';
        $etDate = $date;
        if ($end <= $start) {
            $etDate = gmdate('Y-m-d', strtotime($date.' +1 day UTC'));
        }
        $e = $etDate.'T'.$end.':00';

        return [$s, $e];
    }
}
