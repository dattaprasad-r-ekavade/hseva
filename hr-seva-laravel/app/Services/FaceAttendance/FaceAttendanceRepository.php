<?php

namespace App\Services\FaceAttendance;

use App\Support\FaceAttendanceDefaults;
use DateTimeImmutable;
use DateTimeZone;
use PDO;
use Throwable;

class FaceAttendanceRepository
{
    public function defaultSettings(): array
    {
        return [
            'inAllowedFrom' => '08:00',
            'inAllowedTill' => '11:00',
            'lateMarkAfter' => '09:15',
            'outAllowedFrom' => '17:00',
            'outAllowedTill' => '23:00',
            'graceTime' => 10,
            'faceMatchThreshold' => 0.48,
            'timezone' => FaceAttendanceDefaults::TIMEZONE,
            'modelUrl' => FaceAttendanceDefaults::MODEL_URL,
            'autoCaptureSeconds' => 2,
            'scanDistanceCm' => 45,
            '__updatedAt' => now_iso(),
        ];
    }

    public function settingsSeed(PDO $d): void
    {
        $defs = $this->defaultSettings();
        $st = $d->prepare('INSERT OR IGNORE INTO attendance_settings (id, in_allowed_from, in_allowed_till, late_mark_after, out_allowed_from, out_allowed_till, grace_time, face_match_threshold, timezone, model_url, auto_capture_seconds, scan_distance_cm, updated_at) VALUES (1,?,?,?,?,?,?,?,?,?,?,?,?)');
        $st->execute([
            $defs['inAllowedFrom'], $defs['inAllowedTill'], $defs['lateMarkAfter'], $defs['outAllowedFrom'], $defs['outAllowedTill'],
            (int) $defs['graceTime'], f($defs['faceMatchThreshold']), (string) $defs['timezone'], (string) $defs['modelUrl'],
            (int) $defs['autoCaptureSeconds'], (int) $defs['scanDistanceCm'], (string) $defs['__updatedAt'],
        ]);
    }

    public function viewContext(): array
    {
        $ctx = auth_ctx(true);
        $role = strtolower((string) ($ctx['role'] ?? ''));
        if (! in_array($role, ['super_admin', 'client', 'client_admin', 'agency_admin', 'employee'], true)) {
            j(['detail' => 'Forbidden'], 403);
        }

        return $ctx;
    }

    public function manageContext(): array
    {
        $ctx = $this->viewContext();
        if (strtolower((string) ($ctx['role'] ?? '')) === 'employee') {
            j(['detail' => 'Only admin/HR can manage face attendance settings'], 403);
        }

        return $ctx;
    }

    public function employeeScope(array $ctx): string
    {
        return strtolower((string) ($ctx['role'] ?? '')) === 'employee' ? up($ctx['empId'] ?? '') : '';
    }

    public function settingsGet(): array
    {
        $this->settingsSeed(db());
        $r = db()->query('SELECT * FROM attendance_settings WHERE id=1 LIMIT 1')->fetch() ?: [];
        $defs = $this->defaultSettings();

        return [
            'inAllowedFrom' => s($r['in_allowed_from'] ?? $defs['inAllowedFrom'], $defs['inAllowedFrom']),
            'inAllowedTill' => s($r['in_allowed_till'] ?? $defs['inAllowedTill'], $defs['inAllowedTill']),
            'lateMarkAfter' => s($r['late_mark_after'] ?? $defs['lateMarkAfter'], $defs['lateMarkAfter']),
            'outAllowedFrom' => s($r['out_allowed_from'] ?? $defs['outAllowedFrom'], $defs['outAllowedFrom']),
            'outAllowedTill' => s($r['out_allowed_till'] ?? $defs['outAllowedTill'], $defs['outAllowedTill']),
            'graceTime' => max(0, (int) ($r['grace_time'] ?? $defs['graceTime'])),
            'faceMatchThreshold' => max(0.1, min(1.5, f($r['face_match_threshold'] ?? $defs['faceMatchThreshold']))),
            'timezone' => s($r['timezone'] ?? $defs['timezone'], $defs['timezone']),
            'modelUrl' => s($r['model_url'] ?? $defs['modelUrl'], $defs['modelUrl']),
            'autoCaptureSeconds' => max(1, (int) ($r['auto_capture_seconds'] ?? $defs['autoCaptureSeconds'])),
            'scanDistanceCm' => max(20, min(150, (int) ($r['scan_distance_cm'] ?? $defs['scanDistanceCm']))),
            '__updatedAt' => s($r['updated_at'] ?? $defs['__updatedAt'], $defs['__updatedAt']),
        ];
    }

    public function settingsPut(array $raw): array
    {
        $cur = $this->settingsGet();
        $row = [
            'inAllowedFrom' => s($raw['inAllowedFrom'] ?? $cur['inAllowedFrom'], $cur['inAllowedFrom']),
            'inAllowedTill' => s($raw['inAllowedTill'] ?? $cur['inAllowedTill'], $cur['inAllowedTill']),
            'lateMarkAfter' => s($raw['lateMarkAfter'] ?? $cur['lateMarkAfter'], $cur['lateMarkAfter']),
            'outAllowedFrom' => s($raw['outAllowedFrom'] ?? $cur['outAllowedFrom'], $cur['outAllowedFrom']),
            'outAllowedTill' => s($raw['outAllowedTill'] ?? $cur['outAllowedTill'], $cur['outAllowedTill']),
            'graceTime' => max(0, (int) ($raw['graceTime'] ?? $cur['graceTime'])),
            'faceMatchThreshold' => max(0.1, min(1.5, f($raw['faceMatchThreshold'] ?? $cur['faceMatchThreshold']))),
            'timezone' => s($raw['timezone'] ?? $cur['timezone'], FaceAttendanceDefaults::TIMEZONE),
            'modelUrl' => s($raw['modelUrl'] ?? $cur['modelUrl'], FaceAttendanceDefaults::MODEL_URL),
            'autoCaptureSeconds' => max(1, (int) ($raw['autoCaptureSeconds'] ?? $cur['autoCaptureSeconds'])),
            'scanDistanceCm' => max(20, min(150, (int) ($raw['scanDistanceCm'] ?? $cur['scanDistanceCm']))),
            '__updatedAt' => now_iso(),
        ];
        $st = db()->prepare('INSERT INTO attendance_settings (id, in_allowed_from, in_allowed_till, late_mark_after, out_allowed_from, out_allowed_till, grace_time, face_match_threshold, timezone, model_url, auto_capture_seconds, scan_distance_cm, updated_at) VALUES (1,?,?,?,?,?,?,?,?,?,?,?,?) ON CONFLICT(id) DO UPDATE SET in_allowed_from=excluded.in_allowed_from, in_allowed_till=excluded.in_allowed_till, late_mark_after=excluded.late_mark_after, out_allowed_from=excluded.out_allowed_from, out_allowed_till=excluded.out_allowed_till, grace_time=excluded.grace_time, face_match_threshold=excluded.face_match_threshold, timezone=excluded.timezone, model_url=excluded.model_url, auto_capture_seconds=excluded.auto_capture_seconds, scan_distance_cm=excluded.scan_distance_cm, updated_at=excluded.updated_at');
        $st->execute([
            $row['inAllowedFrom'], $row['inAllowedTill'], $row['lateMarkAfter'], $row['outAllowedFrom'], $row['outAllowedTill'],
            $row['graceTime'], $row['faceMatchThreshold'], $row['timezone'], $row['modelUrl'], $row['autoCaptureSeconds'], $row['scanDistanceCm'], $row['__updatedAt'],
        ]);

        return $row;
    }

    public function registrationRows(?string $employeeId = null): array
    {
        $sql = 'SELECT id, employee_id, face_image, created_at, updated_at FROM employee_faces';
        $params = [];
        if ($employeeId !== null && $employeeId !== '') {
            $sql .= ' WHERE employee_id=?';
            $params[] = up($employeeId);
        }
        $sql .= ' ORDER BY employee_id ASC';
        $st = db()->prepare($sql);
        $st->execute($params);

        return array_map(fn (array $r) => $this->registrationPayload($r), $st->fetchAll() ?: []);
    }

    public function register(array $payload): array
    {
        $ctx = $this->manageContext();
        $emp = $this->employee($ctx, $payload, true);
        $descriptor = $this->descriptorValue($payload['faceDescriptor'] ?? $payload['descriptor'] ?? []);
        if (count($descriptor) < 32) {
            bad('Valid face descriptor is required');
        }
        $image = s($payload['faceImage'] ?? '', '');
        $now = now_iso();
        $empId = up($emp['id'] ?? '');
        db()->prepare('DELETE FROM employee_faces WHERE employee_id=?')->execute([$empId]);
        $st = db()->prepare('INSERT INTO employee_faces (employee_id, face_descriptor, face_image, created_at, updated_at) VALUES (?,?,?,?,?)');
        $st->execute([$empId, json_encode($descriptor, JSON_UNESCAPED_UNICODE), $image, $now, $now]);

        return $this->registrationRows($empId)[0] ?? ['employeeId' => $empId, 'employeeName' => (string) ($emp['name'] ?? $empId), 'faceImage' => $image, '__updatedAt' => $now];
    }

    public function deleteRegistration(string $employeeId): void
    {
        $this->manageContext();
        $empId = up($employeeId);
        if ($empId === '') {
            bad('employeeId is required');
        }
        $st = db()->prepare('DELETE FROM employee_faces WHERE employee_id=?');
        $st->execute([$empId]);
        if ($st->rowCount() === 0) {
            nf('Face registration not found');
        }
    }

    public function sheetRows(array $query, array $ctx): array
    {
        $this->rebuildFromLogs();
        $params = [];
        $where = [];
        $scope = $this->employeeScope($ctx);
        $employeeId = $scope !== '' ? $scope : up($query['employeeId'] ?? '');
        if ($employeeId !== '') {
            $where[] = 'employee_id=?';
            $params[] = $employeeId;
        }
        $date = s($query['date'] ?? '', '');
        if ($date !== '') {
            $where[] = 'attendance_date=?';
            $params[] = $date;
        } else {
            $month = (int) ($query['month'] ?? 0);
            $year = (int) ($query['year'] ?? 0);
            if ($month >= 1 && $month <= 12 && $year >= 2000) {
                $where[] = "CAST(strftime('%m', attendance_date) AS INTEGER)=?";
                $where[] = "CAST(strftime('%Y', attendance_date) AS INTEGER)=?";
                $params[] = $month;
                $params[] = $year;
            }
        }
        $sql = 'SELECT * FROM attendance';
        if ($where) {
            $sql .= ' WHERE '.implode(' AND ', $where);
        }
        $sql .= ' ORDER BY attendance_date DESC, employee_id ASC';
        $st = db()->prepare($sql);
        $st->execute($params);
        $rows = array_map(fn (array $r) => $this->rowPayload($r), $st->fetchAll() ?: []);

        return array_values(array_map(function (array $row) {
            if ($row['outTime'] === '' && $row['attendanceDate'] < gmdate('Y-m-d') && strtoupper($row['attendanceStatus']) === 'PRESENT') {
                $row['attendanceStatus'] = 'Missing OUT';
            }

            return $row;
        }, $rows));
    }

    public function fetchOne(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $st = db()->prepare('SELECT * FROM attendance WHERE id=? LIMIT 1');
        $st->execute([$id]);
        $row = $st->fetch();

        return $row ? $this->rowPayload($row) : null;
    }

    public function updateRecord(int $id, array $payload): array
    {
        $this->manageContext();
        $st = db()->prepare('SELECT * FROM attendance WHERE id=? LIMIT 1');
        $st->execute([$id]);
        $existing = $st->fetch();
        if (! $existing) {
            nf('Attendance record not found');
        }
        $attendanceDate = s($payload['attendanceDate'] ?? $existing['attendance_date'] ?? '', s($existing['attendance_date'] ?? '', ''));
        $inTime = s($payload['inTime'] ?? $existing['in_time'] ?? '', s($existing['in_time'] ?? '', ''));
        $outTime = s($payload['outTime'] ?? $existing['out_time'] ?? '', s($existing['out_time'] ?? '', ''));
        foreach ([['In time', $inTime], ['Out time', $outTime]] as $item) {
            [$label, $value] = $item;
            if ($value !== '' && ! preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value)) {
                bad($label.' must be HH:MM or HH:MM:SS');
            }
        }
        if ($inTime !== '' && preg_match('/^\d{2}:\d{2}$/', $inTime)) {
            $inTime .= ':00';
        }
        if ($outTime !== '' && preg_match('/^\d{2}:\d{2}$/', $outTime)) {
            $outTime .= ':00';
        }
        $inStatus = s($payload['inStatus'] ?? $existing['in_status'] ?? '', s($existing['in_status'] ?? '', ''));
        $outStatus = s($payload['outStatus'] ?? $existing['out_status'] ?? '', s($existing['out_status'] ?? '', ''));
        $remarks = s($payload['remarks'] ?? $existing['remarks'] ?? '', s($existing['remarks'] ?? '', ''));
        $totalHours = array_key_exists('totalWorkingHours', $payload) ? round(max(0, f($payload['totalWorkingHours'] ?? 0)), 2) : $this->totalHours($attendanceDate, $inTime, $outTime);
        $attendanceStatus = s($payload['attendanceStatus'] ?? '', '');
        if ($attendanceStatus === '') {
            if ($inTime !== '' && $outTime === '') {
                $attendanceStatus = 'Missing OUT';
            } else {
                $attendanceStatus = $this->markStatus($inStatus, $outStatus, $outTime);
            }
        }
        $upd = db()->prepare('UPDATE attendance SET attendance_date=?, in_time=?, out_time=?, total_working_hours=?, attendance_status=?, in_status=?, out_status=?, remarks=?, updated_at=? WHERE id=?');
        $upd->execute([$attendanceDate, $inTime, $outTime, $totalHours, $attendanceStatus, $inStatus, $outStatus, $remarks, now_iso(), $id]);
        $saved = $this->fetchOne($id);
        if (! $saved) {
            nf('Attendance record not found after update');
        }

        return $saved;
    }

    public function deleteRecord(int $id): void
    {
        $this->manageContext();
        $st = db()->prepare('DELETE FROM attendance WHERE id=?');
        $st->execute([$id]);
        if ($st->rowCount() === 0) {
            nf('Attendance record not found');
        }
    }

    public function reportRows(array $query, array $ctx): array
    {
        $rows = $this->sheetRows($query, $ctx);
        $grouped = [];
        foreach ($rows as $row) {
            $eid = up($row['employeeId'] ?? '');
            if ($eid === '') {
                continue;
            }
            if (! isset($grouped[$eid])) {
                $grouped[$eid] = [
                    'employeeId' => $row['employeeId'],
                    'employeeName' => $row['employeeName'],
                    'department' => $row['department'],
                    'designation' => $row['designation'],
                    'presentDays' => 0,
                    'lateDays' => 0,
                    'earlyOutDays' => 0,
                    'missingOutDays' => 0,
                    'totalWorkingHours' => 0.0,
                ];
            }
            $grouped[$eid]['presentDays'] += 1;
            if (stripos((string) $row['inStatus'], 'late') !== false || stripos((string) $row['attendanceStatus'], 'late') !== false) {
                $grouped[$eid]['lateDays'] += 1;
            }
            if (stripos((string) $row['outStatus'], 'early') !== false || stripos((string) $row['attendanceStatus'], 'early') !== false) {
                $grouped[$eid]['earlyOutDays'] += 1;
            }
            if (stripos((string) $row['attendanceStatus'], 'missing out') !== false) {
                $grouped[$eid]['missingOutDays'] += 1;
            }
            $grouped[$eid]['totalWorkingHours'] += f($row['totalWorkingHours'] ?? 0);
        }

        return array_values(array_map(function (array $row) {
            $row['totalWorkingHours'] = round($row['totalWorkingHours'], 2);

            return $row;
        }, $grouped));
    }

    public function scan(array $payload): array
    {
        $ctx = $this->viewContext();
        $scanDescriptor = $this->descriptorValue($payload['faceDescriptor'] ?? $payload['descriptor'] ?? []);
        if (count($scanDescriptor) < 32) {
            bad('Face scan data is required');
        }
        $scanMode = strtoupper(trim((string) ($payload['scanMode'] ?? 'AUTO')));
        if (! in_array($scanMode, ['AUTO', 'IN', 'OUT'], true)) {
            bad('scanMode must be AUTO, IN, or OUT');
        }
        $settings = $this->settingsGet();
        $threshold = f($settings['faceMatchThreshold'] ?? 0.48);
        $now = $this->now($settings);
        $date = $now->format('Y-m-d');
        $time = $now->format('H:i:s');
        $scope = $this->employeeScope($ctx);
        $emp = null;
        $empId = '';
        $distance = 999.0;

        if ($scope !== '') {
            $emp = $this->employee($ctx, $payload, false);
            $empId = up($emp['id'] ?? '');
            $reg = db()->prepare('SELECT * FROM employee_faces WHERE employee_id=? LIMIT 1');
            $reg->execute([$empId]);
            $registered = $reg->fetch();
            if (! $registered) {
                $this->log($empId, $date, 'scan', 999.0, $threshold, false, 'Face not registered for employee', ['employeeId' => $empId]);
                j(['detail' => 'Face not verified. Please try again or contact admin.'], 422);
            }
            $storedDescriptor = $this->descriptorValue($registered['face_descriptor'] ?? '[]');
            $distance = $this->descriptorDistance($scanDescriptor, $storedDescriptor);
        } else {
            $requestedEmployeeId = up($payload['employeeId'] ?? '');
            if ($requestedEmployeeId !== '') {
                $emp = $this->employee($ctx, ['employeeId' => $requestedEmployeeId], true);
                $empId = up($emp['id'] ?? '');
                $reg = db()->prepare('SELECT * FROM employee_faces WHERE employee_id=? LIMIT 1');
                $reg->execute([$empId]);
                $registered = $reg->fetch();
                if (! $registered) {
                    $this->log($empId, $date, 'scan', 999.0, $threshold, false, 'Face not registered for employee', ['employeeId' => $empId]);
                    j(['detail' => 'Face not verified. Please try again or contact admin.'], 422);
                }
                $storedDescriptor = $this->descriptorValue($registered['face_descriptor'] ?? '[]');
                $distance = $this->descriptorDistance($scanDescriptor, $storedDescriptor);
            } else {
                $match = $this->registeredMatch($scanDescriptor);
                if (! $match) {
                    $this->log('', $date, 'scan', 999.0, $threshold, false, 'Face verification failed', ['mode' => 'auto-match']);
                    j(['detail' => 'Face not verified. Please try again or contact admin.'], 422);
                }
                $emp = $match['employee'];
                $empId = up($emp['id'] ?? '');
                $distance = f($match['score'] ?? 999.0);
            }
        }

        if ($distance > $threshold) {
            $this->log($empId, $date, 'scan', $distance, $threshold, false, 'Face verification failed', ['employeeId' => $empId]);
            j(['detail' => 'Face not verified. Please try again or contact admin.', 'score' => round($distance, 6)], 422);
        }
        $curMin = $this->timeToMinutes($now->format('H:i'));
        $inFrom = $this->timeToMinutes((string) $settings['inAllowedFrom']);
        $lateAfter = $this->timeToMinutes((string) $settings['lateMarkAfter']) + max(0, (int) $settings['graceTime']);
        $outFrom = $this->timeToMinutes((string) $settings['outAllowedFrom']);
        $st = db()->prepare('SELECT * FROM attendance WHERE employee_id=? AND attendance_date=? LIMIT 1');
        $st->execute([$empId, $date]);
        $row = $st->fetch();
        $messageTitle = 'Face Verified Successfully';
        if ($scanMode === 'IN' && $row && s($row['in_time'] ?? '', '') !== '') {
            $this->log($empId, $date, 'scan', $distance, $threshold, true, 'Attendance IN already marked for today', ['employeeId' => $empId, 'scanMode' => $scanMode]);

            return [
                'verified' => true,
                'action' => 'IN_ALREADY',
                'score' => round($distance, 6),
                'messageTitle' => $messageTitle,
                'messageLine' => 'Attendance IN already marked for today.',
                'row' => $this->rowPayload($row),
            ];
        }
        if ($scanMode === 'OUT' && (! $row || s($row['in_time'] ?? '', '') === '')) {
            $this->log($empId, $date, 'scan', $distance, $threshold, false, 'Attendance IN required before OUT scan', ['employeeId' => $empId, 'scanMode' => $scanMode]);
            j(['detail' => 'Attendance IN is required before OUT scan.'], 422);
        }
        if (! $row) {
            if ($scanMode === 'OUT') {
                j(['detail' => 'Attendance IN is required before OUT scan.'], 422);
            }
            if ($curMin < $inFrom) {
                j(['detail' => 'Attendance IN is not allowed yet. Please try within attendance time.'], 422);
            }
            $inStatus = $curMin > $lateAfter ? 'Late' : 'On Time';
            $status = $this->markStatus($inStatus, '', '');
            $remarks = $inStatus === 'Late' ? 'Late attendance marked by face scan' : 'Attendance IN marked by face scan';
            $ins = db()->prepare('INSERT INTO attendance (employee_id, attendance_date, in_time, out_time, total_working_hours, attendance_status, in_status, out_status, remarks, source, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
            $ts = now_iso();
            $ins->execute([$empId, $date, $time, '', 0, $status, $inStatus, '', $remarks, 'face', $ts, $ts]);
            att_daily_upsert((int) $now->format('n'), (int) $now->format('Y'), [['empId' => $empId, 'date' => $date, 'status' => 'P']]);
            $this->log($empId, $date, 'IN', $distance, $threshold, true, 'Attendance IN marked', ['employeeId' => $empId]);
            $saved = db()->query('SELECT * FROM attendance WHERE id='.(int) db()->lastInsertId())->fetch() ?: [];

            return [
                'verified' => true,
                'action' => 'IN',
                'score' => round($distance, 6),
                'messageTitle' => $messageTitle,
                'messageLine' => 'Attendance IN marked at current time',
                'row' => $this->rowPayload($saved),
            ];
        }
        if (s($row['out_time'] ?? '', '') !== '') {
            $this->log($empId, $date, 'scan', $distance, $threshold, true, 'Attendance already completed for today', ['employeeId' => $empId]);

            return [
                'verified' => true,
                'action' => 'COMPLETED',
                'score' => round($distance, 6),
                'messageTitle' => $messageTitle,
                'messageLine' => 'Attendance already completed for today.',
                'row' => $this->rowPayload($row),
            ];
        }
        if ($scanMode === 'IN') {
            $this->log($empId, $date, 'scan', $distance, $threshold, true, 'Attendance IN already exists; use OUT scan', ['employeeId' => $empId, 'scanMode' => $scanMode]);

            return [
                'verified' => true,
                'action' => 'IN_ALREADY',
                'score' => round($distance, 6),
                'messageTitle' => $messageTitle,
                'messageLine' => 'Attendance IN already exists. Please use OUT scan.',
                'row' => $this->rowPayload($row),
            ];
        }
        $outStatus = $curMin < $outFrom ? 'Early Out' : 'On Time';
        $inTime = s($row['in_time'] ?? '', '');
        $totalHours = 0.0;
        if ($inTime !== '' && preg_match('/^\d{2}:\d{2}:\d{2}$/', $inTime)) {
            $inTs = strtotime($date.' '.$inTime);
            $outTs = strtotime($date.' '.$time);
            if ($inTs !== false && $outTs !== false && $outTs >= $inTs) {
                $totalHours = round(($outTs - $inTs) / 3600, 2);
            }
        }
        $status = $this->markStatus((string) ($row['in_status'] ?? ''), $outStatus, $time);
        $remarks = $outStatus === 'Early Out' ? 'Attendance OUT marked before allowed OUT time' : 'Attendance OUT marked by face scan';
        $upd = db()->prepare('UPDATE attendance SET out_time=?, total_working_hours=?, attendance_status=?, out_status=?, remarks=?, updated_at=? WHERE id=?');
        $upd->execute([$time, $totalHours, $status, $outStatus, $remarks, now_iso(), (int) $row['id']]);
        $this->log($empId, $date, 'OUT', $distance, $threshold, true, 'Attendance OUT marked', ['employeeId' => $empId]);
        $saved = db()->query('SELECT * FROM attendance WHERE id='.(int) $row['id'])->fetch() ?: [];

        return [
            'verified' => true,
            'action' => 'OUT',
            'score' => round($distance, 6),
            'messageTitle' => $messageTitle,
            'messageLine' => 'Attendance OUT marked at current time',
            'row' => $this->rowPayload($saved),
        ];
    }

    private function timezone(array $settings): DateTimeZone
    {
        try {
            return new DateTimeZone((string) ($settings['timezone'] ?? FaceAttendanceDefaults::TIMEZONE));
        } catch (Throwable $e) {
            return new DateTimeZone(FaceAttendanceDefaults::TIMEZONE);
        }
    }

    private function now(array $settings): DateTimeImmutable
    {
        return new DateTimeImmutable('now', $this->timezone($settings));
    }

    private function timeToMinutes(string $time): int
    {
        if (! preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $time, $m)) {
            bad('Time must be in HH:MM format');
        }

        return ((int) $m[1] * 60) + (int) $m[2];
    }

    private function descriptorValue($raw): array
    {
        $src = $raw;
        if (is_string($src)) {
            $src = json_decode($src, true);
        }
        if (! is_array($src)) {
            return [];
        }
        $out = [];
        foreach ($src as $v) {
            if (! is_numeric($v)) {
                continue;
            }
            $out[] = round((float) $v, 8);
        }

        return $out;
    }

    private function descriptorDistance(array $a, array $b): float
    {
        $n = min(count($a), count($b));
        if ($n === 0) {
            return 999.0;
        }
        $sum = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $diff = (float) $a[$i] - (float) $b[$i];
            $sum += $diff * $diff;
        }

        return sqrt($sum);
    }

    private function employee(array $ctx, array $payload = [], bool $allowAdminPick = true): array
    {
        $scope = $this->employeeScope($ctx);
        $empId = $scope !== '' ? $scope : up(($allowAdminPick ? ($payload['employeeId'] ?? '') : ''));
        if ($empId === '') {
            bad('employeeId is required');
        }
        $emp = employee_lookup($empId);
        if (! $emp) {
            nf('Employee not found');
        }
        if (strtolower((string) ($emp['status'] ?? 'active')) === 'inactive') {
            bad('Employee is inactive');
        }

        return $emp;
    }

    private function registrationPayload(array $r): array
    {
        $emp = employee_lookup((string) ($r['employee_id'] ?? ''));

        return [
            'id' => (int) ($r['id'] ?? 0),
            'employeeId' => (string) ($r['employee_id'] ?? ''),
            'employeeName' => (string) ($emp['name'] ?? ($r['employee_id'] ?? '')),
            'department' => (string) ($emp['dept'] ?? ''),
            'designation' => (string) ($emp['desig'] ?? ''),
            'faceImage' => (string) ($r['face_image'] ?? ''),
            '__updatedAt' => s($r['updated_at'] ?? $r['created_at'] ?? '', ''),
        ];
    }

    private function registeredMatch(array $scanDescriptor): ?array
    {
        $threshold = f($this->settingsGet()['faceMatchThreshold'] ?? 0.48);
        $rows = db()->query('SELECT * FROM employee_faces ORDER BY employee_id ASC')->fetchAll() ?: [];
        $best = null;
        foreach ($rows as $row) {
            $employeeId = up($row['employee_id'] ?? '');
            if ($employeeId === '') {
                continue;
            }
            $emp = employee_lookup($employeeId);
            if (! $emp) {
                continue;
            }
            if (strtolower((string) ($emp['status'] ?? 'active')) === 'inactive') {
                continue;
            }
            $stored = $this->descriptorValue($row['face_descriptor'] ?? '[]');
            if (count($stored) < 32) {
                continue;
            }
            $distance = $this->descriptorDistance($scanDescriptor, $stored);
            if ($best === null || $distance < f($best['score'] ?? 999.0)) {
                $best = [
                    'employee' => $emp,
                    'row' => $row,
                    'score' => $distance,
                    'threshold' => $threshold,
                ];
            }
        }
        if (! $best) {
            return null;
        }
        if (f($best['score'] ?? 999.0) > $threshold) {
            return null;
        }

        return $best;
    }

    private function log(string $employeeId, string $date, string $actionType, float $score, float $threshold, bool $verified, string $message, array $payload = []): void
    {
        $st = db()->prepare('INSERT INTO attendance_logs (employee_id, attendance_date, action_type, scan_time, verification_score, match_threshold, is_verified, message, payload_json, created_at) VALUES (?,?,?,?,?,?,?,?,?,?)');
        $ts = now_iso();
        $st->execute([up($employeeId), $date, $actionType, $ts, round($score, 6), round($threshold, 6), $verified ? 1 : 0, $message, json_encode($payload, JSON_UNESCAPED_UNICODE), $ts]);
    }

    private function rowPayload(array $r): array
    {
        $emp = employee_lookup((string) ($r['employee_id'] ?? ''));
        $status = s($r['attendance_status'] ?? '', '');
        if ($status === '' && s($r['in_time'] ?? '', '') !== '' && s($r['out_time'] ?? '', '') === '') {
            $status = 'Missing OUT';
        }

        return [
            'id' => (int) ($r['id'] ?? 0),
            'employeeId' => (string) ($r['employee_id'] ?? ''),
            'employeeName' => (string) ($emp['name'] ?? ($r['employee_id'] ?? '')),
            'department' => (string) ($emp['dept'] ?? ''),
            'designation' => (string) ($emp['desig'] ?? ''),
            'attendanceDate' => (string) ($r['attendance_date'] ?? ''),
            'inTime' => (string) ($r['in_time'] ?? ''),
            'outTime' => (string) ($r['out_time'] ?? ''),
            'totalWorkingHours' => round(f($r['total_working_hours'] ?? 0), 2),
            'attendanceStatus' => $status,
            'inStatus' => (string) ($r['in_status'] ?? ''),
            'outStatus' => (string) ($r['out_status'] ?? ''),
            'remarks' => (string) ($r['remarks'] ?? ''),
            'source' => (string) ($r['source'] ?? 'face'),
            '__updatedAt' => s($r['updated_at'] ?? '', ''),
        ];
    }

    private function logTimeLocal(string $isoTs, array $settings): string
    {
        $raw = trim($isoTs);
        if ($raw === '') {
            return '';
        }
        try {
            $dt = new DateTimeImmutable($raw, new DateTimeZone('UTC'));

            return $dt->setTimezone($this->timezone($settings))->format('H:i:s');
        } catch (Throwable $e) {
            return '';
        }
    }

    private function rebuildFromLogs(): void
    {
        $settings = $this->settingsGet();
        $logs = db()->query("SELECT employee_id, attendance_date, action_type, scan_time, is_verified FROM attendance_logs WHERE is_verified=1 AND action_type IN ('IN','OUT') ORDER BY id ASC")->fetchAll() ?: [];
        if (! $logs) {
            return;
        }
        $grouped = [];
        foreach ($logs as $log) {
            $empId = up($log['employee_id'] ?? '');
            $date = s($log['attendance_date'] ?? '', '');
            if ($empId === '' || $date === '') {
                continue;
            }
            $key = $empId.'|'.$date;
            if (! isset($grouped[$key])) {
                $grouped[$key] = ['employee_id' => $empId, 'attendance_date' => $date, 'in_time' => '', 'out_time' => ''];
            }
            $localTime = $this->logTimeLocal((string) ($log['scan_time'] ?? ''), $settings);
            $action = strtoupper((string) ($log['action_type'] ?? ''));
            if ($action === 'IN' && $grouped[$key]['in_time'] === '') {
                $grouped[$key]['in_time'] = $localTime;
            }
            if ($action === 'OUT') {
                $grouped[$key]['out_time'] = $localTime;
            }
        }
        if (! $grouped) {
            return;
        }
        $inFrom = $this->timeToMinutes((string) $settings['inAllowedFrom']);
        $lateAfter = $this->timeToMinutes((string) $settings['lateMarkAfter']) + max(0, (int) $settings['graceTime']);
        $outFrom = $this->timeToMinutes((string) $settings['outAllowedFrom']);
        $st = db()->prepare('INSERT OR IGNORE INTO attendance (employee_id, attendance_date, in_time, out_time, total_working_hours, attendance_status, in_status, out_status, remarks, source, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
        foreach ($grouped as $row) {
            $inTime = s($row['in_time'] ?? '', '');
            $outTime = s($row['out_time'] ?? '', '');
            if ($inTime === '') {
                continue;
            }
            $inStatus = '';
            if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $inTime)) {
                $mins = ((int) substr($inTime, 0, 2) * 60) + (int) substr($inTime, 3, 2);
                $inStatus = $mins > $lateAfter ? 'Late' : ($mins < $inFrom ? 'Before Time' : 'On Time');
            }
            $outStatus = '';
            if ($outTime !== '' && preg_match('/^\d{2}:\d{2}:\d{2}$/', $outTime)) {
                $mins = ((int) substr($outTime, 0, 2) * 60) + (int) substr($outTime, 3, 2);
                $outStatus = $mins < $outFrom ? 'Early Out' : 'On Time';
            }
            $totalHours = $this->totalHours((string) $row['attendance_date'], $inTime, $outTime);
            $status = $outTime === '' ? 'Missing OUT' : $this->markStatus($inStatus, $outStatus, $outTime);
            $remarks = $outTime === '' ? 'Recovered from face attendance logs (OUT missing)' : 'Recovered from face attendance logs';
            $ts = now_iso();
            $st->execute([(string) $row['employee_id'], (string) $row['attendance_date'], $inTime, $outTime, $totalHours, $status, $inStatus, $outStatus, $remarks, 'face', $ts, $ts]);
        }
    }

    private function totalHours(string $date, string $inTime, string $outTime): float
    {
        if ($inTime === '' || $outTime === '') {
            return 0.0;
        }
        $inTs = strtotime($date.' '.$inTime);
        $outTs = strtotime($date.' '.$outTime);
        if ($inTs === false || $outTs === false || $outTs < $inTs) {
            return 0.0;
        }

        return round(($outTs - $inTs) / 3600, 2);
    }

    private function markStatus(string $inStatus, string $outStatus, string $outTime): string
    {
        $parts = [];
        if (stripos($inStatus, 'late') !== false) {
            $parts[] = 'Late';
        }
        if ($outTime === '') {
            $parts[] = 'Present';
        } elseif (stripos($outStatus, 'early') !== false) {
            $parts[] = 'Early Out';
        } else {
            $parts[] = 'Present';
        }

        return implode(', ', array_values(array_unique($parts)));
    }
}
