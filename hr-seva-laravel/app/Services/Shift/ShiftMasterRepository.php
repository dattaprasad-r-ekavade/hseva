<?php

namespace App\Services\Shift;

use PDO;

class ShiftMasterRepository
{
    public function __construct(private ShiftSupport $support) {}

    public function rows(PDO $db, int $companyId, bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM shift_master'.($activeOnly ? " WHERE status='Active'" : '')." ORDER BY shift_type='Working' DESC, shift_code ASC";
        $rows = $db->query($sql)->fetchAll();

        return array_map(function ($r) use ($companyId) {
            return [
                'id' => (int) $r['id'], 'companyId' => $companyId, 'shiftCode' => (string) $r['shift_code'], 'shiftName' => (string) $r['shift_name'],
                'startTime' => ($r['start_time'] ?? null), 'endTime' => ($r['end_time'] ?? null), 'breakMinutes' => (int) $r['break_minutes'],
                'totalHours' => (float) $r['total_hours'], 'shiftType' => (string) $r['shift_type'], 'lateGraceMinutes' => (int) $r['late_grace_minutes'],
                'halfDayHours' => (float) $r['half_day_hours'], 'otEligible' => ((int) $r['ot_eligible']) === 1,
                'colorCode' => (string) $r['color_code'], 'status' => (string) $r['status'], 'createdAt' => (string) $r['created_at'], 'updatedAt' => (string) $r['updated_at'],
            ];
        }, $rows);
    }

    public function upsert(PDO $db, int $companyId, array $raw, bool $mustExist): array
    {
        $n = $this->normalize($raw);
        $id = (int) ($raw['id'] ?? 0);
        $exists = false;
        if ($id > 0) {
            $q = $db->prepare('SELECT id FROM shift_master WHERE id=?');
            $q->execute([$id]);
            $exists = (bool) $q->fetch();
        }
        if ($mustExist && ! $exists) {
            nf('Shift not found');
        }
        $du = $db->prepare('SELECT id FROM shift_master WHERE shift_code=? AND id<>?');
        $du->execute([$n['shiftCode'], $id]);
        if ($du->fetch()) {
            bad('shiftCode already exists');
        }

        $ts = now_iso();
        if ($exists) {
            $st = $db->prepare('UPDATE shift_master SET shift_code=?,shift_name=?,start_time=?,end_time=?,break_minutes=?,total_hours=?,shift_type=?,late_grace_minutes=?,half_day_hours=?,ot_eligible=?,color_code=?,status=?,updated_at=? WHERE id=?');
            $st->execute([$n['shiftCode'], $n['shiftName'], $n['startTime'], $n['endTime'], $n['breakMinutes'], $n['totalHours'], $n['shiftType'], $n['lateGraceMinutes'], $n['halfDayHours'], $n['otEligible'], $n['colorCode'], $n['status'], $ts, $id]);
        } else {
            $st = $db->prepare('INSERT INTO shift_master (shift_code,shift_name,start_time,end_time,break_minutes,total_hours,shift_type,late_grace_minutes,half_day_hours,ot_eligible,color_code,status,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $st->execute([$n['shiftCode'], $n['shiftName'], $n['startTime'], $n['endTime'], $n['breakMinutes'], $n['totalHours'], $n['shiftType'], $n['lateGraceMinutes'], $n['halfDayHours'], $n['otEligible'], $n['colorCode'], $n['status'], $ts, $ts]);
            $id = (int) $db->lastInsertId();
        }
        foreach ($this->rows($db, $companyId, false) as $r) {
            if ((int) $r['id'] === $id) {
                return $r;
            }
        }

        return ['id' => $id, 'companyId' => $companyId] + $n + ['createdAt' => $ts, 'updatedAt' => $ts];
    }

    public function delete(PDO $db, int $id): void
    {
        $st = $db->prepare('DELETE FROM shift_master WHERE id=?');
        $st->execute([$id]);
        if ($st->rowCount() === 0) {
            nf('Shift not found');
        }
    }

    public function normalize(array $raw): array
    {
        $code = up($raw['shiftCode'] ?? '');
        $name = s($raw['shiftName'] ?? '');
        $type = s($raw['shiftType'] ?? 'Working', 'Working');
        if ($code === '') {
            bad('shiftCode is required');
        }
        if ($name === '') {
            bad('shiftName is required');
        }
        if (! in_array($type, ['Working', 'Off', 'Leave', 'Holiday'], true)) {
            bad('shiftType must be Working/Off/Leave/Holiday');
        }
        $st = $this->support->normTime(isset($raw['startTime']) ? (string) $raw['startTime'] : null);
        $et = $this->support->normTime(isset($raw['endTime']) ? (string) $raw['endTime'] : null);
        if ($type === 'Working' && ($st === null || $et === null)) {
            bad('Working shift requires startTime and endTime');
        }
        if ($type !== 'Working') {
            $st = null;
            $et = null;
        }
        $break = max(0, (int) ($raw['breakMinutes'] ?? 0));
        $totalHours = max(0.0, f($raw['totalHours'] ?? 0));
        if ($totalHours <= 0 && $st !== null && $et !== null) {
            $mins = $this->support->durationMinutes($st, $et);
            $totalHours = round(max(0, $mins - $break) / 60, 2);
        }

        return [
            'id' => isset($raw['id']) ? (int) $raw['id'] : 0,
            'shiftCode' => $code,
            'shiftName' => $name,
            'startTime' => $st,
            'endTime' => $et,
            'breakMinutes' => $break,
            'totalHours' => $totalHours,
            'shiftType' => $type,
            'lateGraceMinutes' => max(0, (int) ($raw['lateGraceMinutes'] ?? 0)),
            'halfDayHours' => max(0.0, f($raw['halfDayHours'] ?? 0)),
            'otEligible' => b($raw['otEligible'] ?? false) ? 1 : 0,
            'colorCode' => s($raw['colorCode'] ?? '#0d6efd', '#0d6efd'),
            'status' => s($raw['status'] ?? 'Active', 'Active') === 'Inactive' ? 'Inactive' : 'Active',
        ];
    }
}
