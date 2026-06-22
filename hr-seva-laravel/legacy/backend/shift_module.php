<?php
declare(strict_types=1);

function init_shift_schema(PDO $d): void {
  $d->exec("CREATE TABLE IF NOT EXISTS shift_master (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    shift_code TEXT NOT NULL,
    shift_name TEXT NOT NULL,
    start_time TEXT,
    end_time TEXT,
    break_minutes INTEGER NOT NULL DEFAULT 0,
    total_hours REAL NOT NULL DEFAULT 0,
    shift_type TEXT NOT NULL DEFAULT 'Working',
    late_grace_minutes INTEGER NOT NULL DEFAULT 0,
    half_day_hours REAL NOT NULL DEFAULT 0,
    ot_eligible INTEGER NOT NULL DEFAULT 0,
    color_code TEXT NOT NULL DEFAULT '#0d6efd',
    status TEXT NOT NULL DEFAULT 'Active',
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    UNIQUE(shift_code)
  )");
  $d->exec("CREATE TABLE IF NOT EXISTS employee_shift_assignments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    emp_id TEXT NOT NULL,
    default_shift_code TEXT NOT NULL,
    weekly_off_day TEXT NOT NULL DEFAULT 'Sunday',
    effective_from TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'Active',
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    UNIQUE(emp_id)
  )");
  $d->exec("CREATE TABLE IF NOT EXISTS shift_rosters (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    emp_id TEXT NOT NULL,
    roster_date TEXT NOT NULL,
    shift_code TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'Draft',
    notes TEXT NOT NULL DEFAULT '',
    created_by TEXT NOT NULL DEFAULT '',
    updated_by TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    UNIQUE(emp_id, roster_date)
  )");
  $d->exec("CREATE TABLE IF NOT EXISTS shift_roster_weeks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    week_start_date TEXT NOT NULL,
    week_end_date TEXT NOT NULL,
    is_locked INTEGER NOT NULL DEFAULT 0,
    publish_status TEXT NOT NULL DEFAULT 'Draft',
    created_by TEXT NOT NULL DEFAULT '',
    updated_by TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    UNIQUE(week_start_date, week_end_date)
  )");

  $count = (int)($d->query("SELECT COUNT(*) AS c FROM shift_master")->fetch()['c'] ?? 0);
  if($count === 0){
    $ts = now_iso();
    $rows = [
      ['GS','General Shift','09:30','18:30',60,8.0,'Working',15,4.0,1,'#0d6efd','Active'],
      ['NS','Night Shift','21:00','06:00',45,8.25,'Working',10,4.0,1,'#6610f2','Active'],
      ['WO','Weekly Off',null,null,0,0.0,'Off',0,0.0,0,'#6c757d','Active'],
      ['LV','Leave',null,null,0,0.0,'Leave',0,0.0,0,'#ffc107','Active'],
      ['HD','Holiday',null,null,0,0.0,'Holiday',0,0.0,0,'#20c997','Active']
    ];
    $st = $d->prepare("INSERT INTO shift_master (shift_code,shift_name,start_time,end_time,break_minutes,total_hours,shift_type,late_grace_minutes,half_day_hours,ot_eligible,color_code,status,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    foreach($rows as $r){
      $st->execute([$r[0],$r[1],$r[2],$r[3],$r[4],$r[5],$r[6],$r[7],$r[8],$r[9],$r[10],$r[11],$ts,$ts]);
    }
  }
}

function shift_require_access(): void {
  $ctx = auth_ctx(false);
  if(!$ctx) j(['detail'=>'Unauthorized'],401);
  $role = strtolower((string)($ctx['role'] ?? ''));
  if(in_array($role, ['super_admin','agency_admin','client_admin','employee'], true)) return;
  if($role === 'client'){
    $perm = $ctx['permissions'] ?? [];
    if(is_array($perm) && array_key_exists('shiftRoster', $perm) && $perm['shiftRoster'] === false) j(['detail'=>'Forbidden'],403);
    return;
  }
}
function shift_actor_name(): string {
  $ctx = auth_ctx(false);
  return s($ctx['username'] ?? $ctx['name'] ?? $ctx['sub'] ?? 'system', 'system');
}
function shift_company_ids_scope(bool $allowAll): array {
  $ctx = auth_ctx(true);
  $role = strtolower((string)($ctx['role'] ?? ''));
  $qCompany = isset($_GET['companyId']) ? (int)$_GET['companyId'] : 0;
  if($role === 'super_admin'){
    if($qCompany > 0) return [$qCompany];
    if(!$allowAll){
      $hid = req_client_id();
      return $hid > 0 ? [$hid] : [];
    }
    $rows = central_db()->query("SELECT id FROM clients ORDER BY id ASC")->fetchAll();
    $ids = [];
    foreach($rows as $r){
      $id = (int)($r['id'] ?? 0);
      if($id > 0) $ids[] = $id;
    }
    return $ids;
  }
  $cid = (int)($ctx['clientId'] ?? 0);
  if($cid <= 0) $cid = req_client_id();
  return $cid > 0 ? [$cid] : [];
}
function shift_write_company_id(array $payload): int {
  $ctx = auth_ctx(true);
  $role = strtolower((string)($ctx['role'] ?? ''));
  $req = (int)($payload['companyId'] ?? 0);
  if($role === 'super_admin'){
    $fromHeader = req_client_id();
    $id = $req > 0 ? $req : ($fromHeader > 0 ? $fromHeader : 0);
    if($id <= 0) bad('companyId is required for super admin write');
    return $id;
  }
  $cid = (int)($ctx['clientId'] ?? 0);
  if($cid <= 0) $cid = req_client_id();
  if($cid <= 0) bad('Client scope is required');
  return $cid;
}
function shift_db_for_company(int $companyId): PDO { return db_open(db_path_for_client($companyId)); }
function shift_company_name(int $companyId): string {
  $q = central_db()->prepare("SELECT company_name FROM clients WHERE id=?");
  $q->execute([$companyId]);
  $row = $q->fetch();
  return s($row['company_name'] ?? '', 'Company '.$companyId);
}
function shift_parse_date(string $v, string $name): string {
  $x = s($v);
  if($x === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $x)) bad($name.' must be YYYY-MM-DD');
  return $x;
}
function shift_norm_time(?string $v): ?string {
  $x = trim((string)($v ?? ''));
  if($x === '') return null;
  if(!preg_match('/^\d{2}:\d{2}$/', $x)) bad('time must be HH:MM');
  return $x;
}
function shift_duration_minutes(string $start, string $end): int {
  [$sh,$sm] = array_map('intval', explode(':', $start));
  [$eh,$em] = array_map('intval', explode(':', $end));
  $s = $sh * 60 + $sm;
  $e = $eh * 60 + $em;
  if($e <= $s) $e += 1440;
  return $e - $s;
}
function shift_norm_master(array $raw): array {
  $code = up($raw['shiftCode'] ?? '');
  $name = s($raw['shiftName'] ?? '');
  $type = s($raw['shiftType'] ?? 'Working', 'Working');
  if($code === '') bad('shiftCode is required');
  if($name === '') bad('shiftName is required');
  if(!in_array($type, ['Working','Off','Leave','Holiday'], true)) bad('shiftType must be Working/Off/Leave/Holiday');
  $st = shift_norm_time(isset($raw['startTime']) ? (string)$raw['startTime'] : null);
  $et = shift_norm_time(isset($raw['endTime']) ? (string)$raw['endTime'] : null);
  if($type === 'Working' && ($st === null || $et === null)) bad('Working shift requires startTime and endTime');
  if($type !== 'Working'){ $st = null; $et = null; }
  $break = max(0, (int)($raw['breakMinutes'] ?? 0));
  $totalHours = max(0.0, f($raw['totalHours'] ?? 0));
  if($totalHours <= 0 && $st !== null && $et !== null){
    $mins = shift_duration_minutes($st, $et);
    $totalHours = round(max(0, $mins - $break) / 60, 2);
  }
  return [
    'id' => isset($raw['id']) ? (int)$raw['id'] : 0,
    'shiftCode' => $code,
    'shiftName' => $name,
    'startTime' => $st,
    'endTime' => $et,
    'breakMinutes' => $break,
    'totalHours' => $totalHours,
    'shiftType' => $type,
    'lateGraceMinutes' => max(0, (int)($raw['lateGraceMinutes'] ?? 0)),
    'halfDayHours' => max(0.0, f($raw['halfDayHours'] ?? 0)),
    'otEligible' => b($raw['otEligible'] ?? false) ? 1 : 0,
    'colorCode' => s($raw['colorCode'] ?? '#0d6efd', '#0d6efd'),
    'status' => s($raw['status'] ?? 'Active', 'Active') === 'Inactive' ? 'Inactive' : 'Active',
  ];
}
function shift_master_rows(PDO $d, int $companyId, bool $activeOnly=false): array {
  $sql = "SELECT * FROM shift_master".($activeOnly ? " WHERE status='Active'" : "")." ORDER BY shift_type='Working' DESC, shift_code ASC";
  $rows = $d->query($sql)->fetchAll();
  return array_map(function($r) use ($companyId){
    return [
      'id'=>(int)$r['id'],'companyId'=>$companyId,'shiftCode'=>(string)$r['shift_code'],'shiftName'=>(string)$r['shift_name'],
      'startTime'=>($r['start_time'] ?? null),'endTime'=>($r['end_time'] ?? null),'breakMinutes'=>(int)$r['break_minutes'],
      'totalHours'=>(float)$r['total_hours'],'shiftType'=>(string)$r['shift_type'],'lateGraceMinutes'=>(int)$r['late_grace_minutes'],
      'halfDayHours'=>(float)$r['half_day_hours'],'otEligible'=>((int)$r['ot_eligible'])===1,
      'colorCode'=>(string)$r['color_code'],'status'=>(string)$r['status'],'createdAt'=>(string)$r['created_at'],'updatedAt'=>(string)$r['updated_at']
    ];
  }, $rows);
}
function shift_master_upsert(PDO $d, int $companyId, array $raw, bool $mustExist): array {
  $n = shift_norm_master($raw);
  $id = (int)($raw['id'] ?? 0);
  $exists = false;
  if($id > 0){
    $q = $d->prepare("SELECT id FROM shift_master WHERE id=?");
    $q->execute([$id]);
    $exists = (bool)$q->fetch();
  }
  if($mustExist && !$exists) nf('Shift not found');
  $du = $d->prepare("SELECT id FROM shift_master WHERE shift_code=? AND id<>?");
  $du->execute([$n['shiftCode'], $id]);
  if($du->fetch()) bad('shiftCode already exists');

  $ts = now_iso();
  if($exists){
    $st = $d->prepare("UPDATE shift_master SET shift_code=?,shift_name=?,start_time=?,end_time=?,break_minutes=?,total_hours=?,shift_type=?,late_grace_minutes=?,half_day_hours=?,ot_eligible=?,color_code=?,status=?,updated_at=? WHERE id=?");
    $st->execute([$n['shiftCode'],$n['shiftName'],$n['startTime'],$n['endTime'],$n['breakMinutes'],$n['totalHours'],$n['shiftType'],$n['lateGraceMinutes'],$n['halfDayHours'],$n['otEligible'],$n['colorCode'],$n['status'],$ts,$id]);
  } else {
    $st = $d->prepare("INSERT INTO shift_master (shift_code,shift_name,start_time,end_time,break_minutes,total_hours,shift_type,late_grace_minutes,half_day_hours,ot_eligible,color_code,status,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $st->execute([$n['shiftCode'],$n['shiftName'],$n['startTime'],$n['endTime'],$n['breakMinutes'],$n['totalHours'],$n['shiftType'],$n['lateGraceMinutes'],$n['halfDayHours'],$n['otEligible'],$n['colorCode'],$n['status'],$ts,$ts]);
    $id = (int)$d->lastInsertId();
  }
  $rows = shift_master_rows($d, $companyId, false);
  foreach($rows as $r){ if((int)$r['id'] === $id) return $r; }
  return ['id'=>$id,'companyId'=>$companyId] + $n + ['createdAt'=>$ts,'updatedAt'=>$ts];
}
function shift_master_delete(PDO $d, int $id): void {
  $st = $d->prepare("DELETE FROM shift_master WHERE id=?");
  $st->execute([$id]);
  if($st->rowCount()===0) nf('Shift not found');
}
function shift_assignment_norm(array $raw): array {
  $empId = up($raw['empId'] ?? '');
  $defaultShiftCode = up($raw['defaultShiftCode'] ?? '');
  $weeklyOff = s($raw['weeklyOffDay'] ?? 'Sunday', 'Sunday');
  $effectiveFrom = shift_parse_date(s($raw['effectiveFrom'] ?? date('Y-m-d')), 'effectiveFrom');
  if($empId === '' || $defaultShiftCode === '') bad('empId and defaultShiftCode are required');
  $validDays = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
  if(!in_array($weeklyOff, $validDays, true)) bad('weeklyOffDay must be Monday..Sunday');
  return [
    'id'=>(int)($raw['id'] ?? 0),
    'empId'=>$empId,
    'defaultShiftCode'=>$defaultShiftCode,
    'weeklyOffDay'=>$weeklyOff,
    'effectiveFrom'=>$effectiveFrom,
    'status'=>s($raw['status'] ?? 'Active', 'Active') === 'Inactive' ? 'Inactive' : 'Active'
  ];
}
function shift_assignment_rows(PDO $d, int $companyId): array {
  $rows = $d->query("SELECT a.*, e.name AS employee_name, e.dept AS department, e.desig AS designation FROM employee_shift_assignments a LEFT JOIN employees e ON e.id=a.emp_id ORDER BY a.emp_id ASC")->fetchAll();
  return array_map(fn($r)=>[
    'id'=>(int)$r['id'],'companyId'=>$companyId,'empId'=>(string)$r['emp_id'],'employeeName'=>s($r['employee_name'] ?? '', (string)$r['emp_id']),
    'department'=>(string)($r['department'] ?? ''),'designation'=>(string)($r['designation'] ?? ''),'defaultShiftCode'=>(string)$r['default_shift_code'],
    'weeklyOffDay'=>(string)$r['weekly_off_day'],'effectiveFrom'=>(string)$r['effective_from'],'status'=>(string)$r['status'],
    'createdAt'=>(string)$r['created_at'],'updatedAt'=>(string)$r['updated_at']
  ], $rows);
}
function shift_assignment_upsert(PDO $d, int $companyId, array $raw, bool $mustExist): array {
  $n = shift_assignment_norm($raw);
  $id = $n['id'];
  $exists = false;
  if($id > 0){
    $q = $d->prepare("SELECT id FROM employee_shift_assignments WHERE id=?");
    $q->execute([$id]);
    $exists = (bool)$q->fetch();
  }
  if($mustExist && !$exists) nf('Shift assignment not found');

  $qe = $d->prepare("SELECT id FROM employees WHERE id=?");
  $qe->execute([$n['empId']]);
  if(!$qe->fetch()) bad('Employee not found');

  $qs = $d->prepare("SELECT id,status FROM shift_master WHERE shift_code=? LIMIT 1");
  $qs->execute([$n['defaultShiftCode']]);
  $srow = $qs->fetch();
  if(!$srow) bad('defaultShiftCode not found in shift master');
  if((string)($srow['status'] ?? '') !== 'Active') bad('defaultShiftCode must be active');

  $ts = now_iso();
  if($exists){
    $st = $d->prepare("UPDATE employee_shift_assignments SET emp_id=?,default_shift_code=?,weekly_off_day=?,effective_from=?,status=?,updated_at=? WHERE id=?");
    $st->execute([$n['empId'],$n['defaultShiftCode'],$n['weeklyOffDay'],$n['effectiveFrom'],$n['status'],$ts,$id]);
  } else {
    $st = $d->prepare("INSERT INTO employee_shift_assignments (emp_id,default_shift_code,weekly_off_day,effective_from,status,created_at,updated_at) VALUES (?,?,?,?,?,?,?) ON CONFLICT(emp_id) DO UPDATE SET default_shift_code=excluded.default_shift_code,weekly_off_day=excluded.weekly_off_day,effective_from=excluded.effective_from,status=excluded.status,updated_at=excluded.updated_at");
    $st->execute([$n['empId'],$n['defaultShiftCode'],$n['weeklyOffDay'],$n['effectiveFrom'],$n['status'],$ts,$ts]);
    $id = (int)$d->lastInsertId();
    if($id <= 0){
      $q2 = $d->prepare("SELECT id FROM employee_shift_assignments WHERE emp_id=?");
      $q2->execute([$n['empId']]);
      $id = (int)($q2->fetch()['id'] ?? 0);
    }
  }

  $rows = shift_assignment_rows($d, $companyId);
  foreach($rows as $r){ if((int)$r['id']===$id) return $r; }
  return ['id'=>$id,'companyId'=>$companyId] + $n + ['createdAt'=>$ts,'updatedAt'=>$ts];
}
function shift_assignment_delete(PDO $d, int $id): void {
  $st = $d->prepare("DELETE FROM employee_shift_assignments WHERE id=?");
  $st->execute([$id]);
  if($st->rowCount()===0) nf('Shift assignment not found');
}
function shift_roster_row_norm(array $r): array {
  $empId = up($r['empId'] ?? '');
  $date = shift_parse_date(s($r['rosterDate'] ?? ''), 'rosterDate');
  $shiftCode = up($r['shiftCode'] ?? '');
  if($empId === '' || $shiftCode === '') bad('empId and shiftCode are required');
  return [
    'id'=>(int)($r['id'] ?? 0), 'empId'=>$empId, 'rosterDate'=>$date, 'shiftCode'=>$shiftCode,
    'status'=>s($r['status'] ?? 'Draft', 'Draft') === 'Published' ? 'Published' : 'Draft',
    'notes'=>s($r['notes'] ?? ''),
  ];
}
function shift_roster_upsert_rows(PDO $d, array $rows, string $actor): array {
  $up = 0;
  $ts = now_iso();
  $st = $d->prepare("INSERT INTO shift_rosters (emp_id,roster_date,shift_code,status,notes,created_by,updated_by,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?) ON CONFLICT(emp_id,roster_date) DO UPDATE SET shift_code=excluded.shift_code,status=excluded.status,notes=excluded.notes,updated_by=excluded.updated_by,updated_at=excluded.updated_at");
  $qe = $d->prepare("SELECT id FROM employees WHERE id=?");
  $qs = $d->prepare("SELECT id,status,shift_type FROM shift_master WHERE shift_code=? LIMIT 1");
  $attMaps = [];
  $attChanged = [];
  $attSave = function(string $date, string $empId, bool $isWeeklyOff) use ($d, &$attMaps, &$attChanged): void {
    $m = (int)gmdate('n', strtotime($date.' 00:00:00 UTC'));
    $y = (int)gmdate('Y', strtotime($date.' 00:00:00 UTC'));
    $k = sprintf('attendance_daily_%04d-%02d', $y, $m);
    if(!array_key_exists($k, $attMaps)){
      $q = $d->prepare("SELECT value FROM app_kv WHERE key=?");
      $q->execute([$k]);
      $r = $q->fetch();
      $map = $r ? json_decode((string)$r['value'], true) : [];
      $attMaps[$k] = is_array($map) ? $map : [];
      $attChanged[$k] = false;
    }
    $key = up($empId).'|'.$date;
    if($isWeeklyOff){
      if(($attMaps[$k][$key] ?? '') !== 'WO'){
        $attMaps[$k][$key] = 'WO';
        $attChanged[$k] = true;
      }
      return;
    }
    if(($attMaps[$k][$key] ?? '') === 'WO'){
      unset($attMaps[$k][$key]);
      $attChanged[$k] = true;
    }
  };
  foreach($rows as $row){
    $n = shift_roster_row_norm((array)$row);
    $qe->execute([$n['empId']]);
    if(!$qe->fetch()) continue;
    $qs->execute([$n['shiftCode']]);
    $srow = $qs->fetch();
    if(!$srow || (string)($srow['status'] ?? '') !== 'Active') continue;
    $st->execute([$n['empId'],$n['rosterDate'],$n['shiftCode'],$n['status'],$n['notes'],$actor,$actor,$ts,$ts]);
    $isWeeklyOff = strtoupper((string)$n['shiftCode']) === 'WO' || strtolower((string)($srow['shift_type'] ?? '')) === 'off';
    $attSave((string)$n['rosterDate'], (string)$n['empId'], $isWeeklyOff);
    $up++;
  }
  foreach($attMaps as $k => $map){
    if(empty($attChanged[$k])) continue;
    $sv = $d->prepare("INSERT INTO app_kv (key,value,updated_at) VALUES (?,?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value,updated_at=excluded.updated_at");
    $sv->execute([$k, json_encode($map, JSON_UNESCAPED_UNICODE), now_iso()]);
  }
  return ['upserted'=>$up];
}
function shift_roster_delete_row(PDO $d, string $empId, string $rosterDate): array {
  $empId = up($empId);
  $rosterDate = shift_parse_date($rosterDate, 'rosterDate');
  if($empId === '') bad('empId is required');
  $st = $d->prepare("DELETE FROM shift_rosters WHERE emp_id=? AND roster_date=?");
  $st->execute([$empId, $rosterDate]);
  $deleted = $st->rowCount();

  $m = (int)gmdate('n', strtotime($rosterDate.' 00:00:00 UTC'));
  $y = (int)gmdate('Y', strtotime($rosterDate.' 00:00:00 UTC'));
  $k = sprintf('attendance_daily_%04d-%02d', $y, $m);
  $q = $d->prepare("SELECT value FROM app_kv WHERE key=?");
  $q->execute([$k]);
  $r = $q->fetch();
  $map = $r ? json_decode((string)$r['value'], true) : [];
  if(!is_array($map)) $map = [];
  $attKey = $empId.'|'.$rosterDate;
  if(($map[$attKey] ?? '') === 'WO'){
    unset($map[$attKey]);
    $sv = $d->prepare("INSERT INTO app_kv (key,value,updated_at) VALUES (?,?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value,updated_at=excluded.updated_at");
    $sv->execute([$k, json_encode($map, JSON_UNESCAPED_UNICODE), now_iso()]);
  }

  return ['deleted'=>$deleted];
}
function shift_roster_bulk_delete(PDO $d, array $rows): array {
  $deleted = 0;
  foreach($rows as $row){
    $empId = up((string)($row['empId'] ?? ''));
    $rosterDate = s($row['rosterDate'] ?? '');
    if($empId === '' || $rosterDate === '') continue;
    $res = shift_roster_delete_row($d, $empId, $rosterDate);
    $deleted += (int)($res['deleted'] ?? 0);
  }
  return ['deleted'=>$deleted];
}
function shift_week_days(string $start): array {
  $out = [];
  $t = strtotime($start.' 00:00:00 UTC');
  if($t === false) return [];
  for($i=0;$i<7;$i++) $out[] = gmdate('Y-m-d', $t + ($i * 86400));
  return $out;
}
function shift_week_status_get(PDO $d, string $weekStart, string $weekEnd): array {
  $q = $d->prepare("SELECT * FROM shift_roster_weeks WHERE week_start_date=? AND week_end_date=? LIMIT 1");
  $q->execute([$weekStart,$weekEnd]);
  $r = $q->fetch();
  if(!$r) return ['weekStartDate'=>$weekStart,'weekEndDate'=>$weekEnd,'isLocked'=>false,'publishStatus'=>'Draft'];
  return ['weekStartDate'=>$weekStart,'weekEndDate'=>$weekEnd,'isLocked'=>((int)$r['is_locked'])===1,'publishStatus'=>(string)$r['publish_status']];
}
function shift_week_status_set(PDO $d, string $weekStart, string $weekEnd, bool $isLocked, string $publishStatus, string $actor): array {
  $ts = now_iso();
  if($publishStatus !== 'Published') $publishStatus = 'Draft';
  $st = $d->prepare("INSERT INTO shift_roster_weeks (week_start_date,week_end_date,is_locked,publish_status,created_by,updated_by,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?) ON CONFLICT(week_start_date,week_end_date) DO UPDATE SET is_locked=excluded.is_locked,publish_status=excluded.publish_status,updated_by=excluded.updated_by,updated_at=excluded.updated_at");
  $st->execute([$weekStart,$weekEnd,$isLocked?1:0,$publishStatus,$actor,$actor,$ts,$ts]);
  $d->prepare("UPDATE shift_rosters SET status=?, updated_by=?, updated_at=? WHERE roster_date BETWEEN ? AND ?")->execute([$publishStatus,$actor,$ts,$weekStart,$weekEnd]);
  return shift_week_status_get($d, $weekStart, $weekEnd);
}
function shift_roster_list(PDO $d, int $companyId, string $start, string $end, array $filters=[]): array {
  $where = ["r.roster_date BETWEEN ? AND ?"]; $args = [$start, $end];
  $emp = up($filters['empId'] ?? '');
  if($emp !== ''){ $where[] = "r.emp_id=?"; $args[] = $emp; }
  $dep = s($filters['department'] ?? '');
  if($dep !== ''){ $where[] = "e.dept=?"; $args[] = $dep; }
  $des = s($filters['designation'] ?? '');
  if($des !== ''){ $where[] = "e.desig=?"; $args[] = $des; }
  $shiftCode = up($filters['shiftCode'] ?? '');
  if($shiftCode !== ''){ $where[] = "r.shift_code=?"; $args[] = $shiftCode; }

  $sql = "SELECT r.*, e.name AS employee_name, e.dept AS department, e.desig AS designation,
      s.shift_name, s.shift_type, s.start_time, s.end_time, s.color_code
    FROM shift_rosters r
    LEFT JOIN employees e ON e.id=r.emp_id
    LEFT JOIN shift_master s ON s.shift_code=r.shift_code
    WHERE ".implode(' AND ', $where)." ORDER BY e.name ASC, r.emp_id ASC, r.roster_date ASC";
  $q = $d->prepare($sql);
  $q->execute($args);
  $rows = $q->fetchAll();
  return array_map(function($r) use($companyId){
    return [
      'id'=>(int)$r['id'],'companyId'=>$companyId,'empId'=>(string)$r['emp_id'],'employeeName'=>s($r['employee_name'] ?? '', (string)$r['emp_id']),
      'department'=>(string)($r['department'] ?? ''),'designation'=>(string)($r['designation'] ?? ''),'rosterDate'=>(string)$r['roster_date'],
      'shiftCode'=>(string)$r['shift_code'],'shiftName'=>(string)($r['shift_name'] ?? ''),'shiftType'=>(string)($r['shift_type'] ?? ''),
      'startTime'=>($r['start_time'] ?? null),'endTime'=>($r['end_time'] ?? null),'colorCode'=>(string)($r['color_code'] ?? '#0d6efd'),
      'status'=>(string)$r['status'],'notes'=>(string)$r['notes'],'createdBy'=>(string)$r['created_by'],'updatedBy'=>(string)$r['updated_by'],
      'createdAt'=>(string)$r['created_at'],'updatedAt'=>(string)$r['updated_at']
    ];
  }, $rows);
}
function shift_roster_autofill_week(PDO $d, string $weekStart, string $weekEnd, string $actor, array $filters=[]): array {
  $days = shift_week_days($weekStart);
  if(count($days)!==7) bad('Invalid weekStartDate');
  $offShiftCode = 'WO';
  $qs = $d->query("SELECT shift_code FROM shift_master WHERE shift_type='Off' AND status='Active' ORDER BY id ASC LIMIT 1")->fetch();
  if($qs) $offShiftCode = (string)$qs['shift_code'];

  $sql = "SELECT e.id, e.dept, e.desig, a.default_shift_code, a.weekly_off_day
    FROM employees e
    LEFT JOIN employee_shift_assignments a ON a.emp_id=e.id
    WHERE lower(e.status) <> 'inactive'";
  $args = [];
  $dep = s($filters['department'] ?? '');
  if($dep !== ''){ $sql .= " AND e.dept=?"; $args[] = $dep; }
  $des = s($filters['designation'] ?? '');
  if($des !== ''){ $sql .= " AND e.desig=?"; $args[] = $des; }
  $emp = up($filters['empId'] ?? '');
  if($emp !== ''){ $sql .= " AND e.id=?"; $args[] = $emp; }
  $q = $d->prepare($sql);
  $q->execute($args);
  $emps = $q->fetchAll();

  $rows = [];
  foreach($emps as $e){
    $default = up($e['default_shift_code'] ?? '');
    if($default === '') $default = 'GS';
    $offDay = s($e['weekly_off_day'] ?? 'Sunday', 'Sunday');
    foreach($days as $date){
      $dayName = gmdate('l', strtotime($date.' 00:00:00 UTC'));
      $rows[] = [
        'empId'=>(string)$e['id'],
        'rosterDate'=>$date,
        'shiftCode'=>($dayName === $offDay ? $offShiftCode : $default),
        'status'=>'Draft'
      ];
    }
  }
  return shift_roster_upsert_rows($d, $rows, $actor) + ['generatedRows'=>count($rows)];
}
function shift_roster_copy_previous_week(PDO $d, string $weekStart, string $weekEnd, string $actor): array {
  $prevStart = gmdate('Y-m-d', strtotime($weekStart.' -7 day UTC'));
  $prevEnd = gmdate('Y-m-d', strtotime($weekEnd.' -7 day UTC'));
  $prev = shift_roster_list($d, 0, $prevStart, $prevEnd, []);
  $rows = [];
  foreach($prev as $r){
    $newDate = gmdate('Y-m-d', strtotime($r['rosterDate'].' +7 day UTC'));
    $rows[] = ['empId'=>$r['empId'],'rosterDate'=>$newDate,'shiftCode'=>$r['shiftCode'],'status'=>'Draft','notes'=>$r['notes'] ?? ''];
  }
  return shift_roster_upsert_rows($d, $rows, $actor) + ['copiedFromWeek'=>$prevStart.' to '.$prevEnd,'rows'=>count($rows)];
}
function shift_dt_range(string $date, ?string $start, ?string $end): array {
  if(!$start || !$end) return [null,null];
  $s = $date.'T'.$start.':00';
  $etDate = $date;
  if($end <= $start){
    $etDate = gmdate('Y-m-d', strtotime($date.' +1 day UTC'));
  }
  $e = $etDate.'T'.$end.':00';
  return [$s,$e];
}
function shift_calendar_events(PDO $d, int $companyId, string $from, string $to, array $filters=[]): array {
  $rows = shift_roster_list($d, $companyId, $from, $to, $filters);
  $events = [];
  $summary = [];
  foreach($rows as $r){
    [$start,$end] = shift_dt_range($r['rosterDate'], $r['startTime'], $r['endTime']);
    $day = $r['rosterDate'];
    if(!isset($summary[$day])) $summary[$day] = ['date'=>$day,'totalScheduled'=>0,'leaveCount'=>0,'offCount'=>0,'nightShiftCount'=>0];
    $summary[$day]['totalScheduled']++;
    if(strtolower($r['shiftType']) === 'leave') $summary[$day]['leaveCount']++;
    if(strtolower($r['shiftType']) === 'off') $summary[$day]['offCount']++;
    if(strtolower($r['shiftCode']) === 'ns' || (string)$r['startTime'] >= '20:00') $summary[$day]['nightShiftCount']++;

    $events[] = [
      'eventId'=>$r['id'],'id'=>$r['id'],'title'=>$r['employeeName'].' - '.$r['shiftCode'],'start'=>$start ?? ($r['rosterDate'].'T00:00:00'),
      'end'=>$end ?? ($r['rosterDate'].'T23:59:59'),'allDay'=>$start===null,
      'empId'=>$r['empId'],'employeeName'=>$r['employeeName'],'shiftCode'=>$r['shiftCode'],'shiftName'=>$r['shiftName'],'shiftType'=>$r['shiftType'],
      'colorCode'=>$r['colorCode'],'companyId'=>$companyId,'department'=>$r['department'],'notes'=>$r['notes'],
      'status'=>$r['status'],'rosterDate'=>$r['rosterDate']
    ];
  }
  ksort($summary);
  return ['events'=>$events,'daySummary'=>array_values($summary)];
}
function shift_attendance_status_on(PDO $d, string $date, string $empId): string {
  $m = (int)gmdate('n', strtotime($date.' 00:00:00 UTC'));
  $y = (int)gmdate('Y', strtotime($date.' 00:00:00 UTC'));
  $key = sprintf('attendance_daily_%04d-%02d', $y, $m);
  $q = $d->prepare("SELECT value FROM app_kv WHERE key=?");
  $q->execute([$key]);
  $r = $q->fetch();
  if(!$r) return '';
  $map = json_decode((string)$r['value'], true);
  if(!is_array($map)) return '';
  return strtoupper((string)($map[up($empId).'|'.$date] ?? ''));
}
function shift_leave_dates_map(PDO $d, string $from, string $to): array {
  $q = $d->prepare("SELECT emp_id, from_date, to_date FROM leaves WHERE status='Approved' AND to_date>=? AND from_date<=?");
  $q->execute([$from, $to]);
  $out = [];
  foreach($q->fetchAll() as $r){
    $eid = up($r['emp_id'] ?? '');
    $s1 = strtotime((string)$r['from_date'].' 00:00:00 UTC');
    $e1 = strtotime((string)$r['to_date'].' 00:00:00 UTC');
    if($eid === '' || $s1===false || $e1===false) continue;
    for($t=$s1; $t<=$e1; $t+=86400){
      $out[$eid.'|'.gmdate('Y-m-d',$t)] = true;
    }
  }
  return $out;
}
function shift_roster_attendance_report(PDO $d, int $companyId, string $from, string $to): array {
  $companyName = shift_company_name($companyId);
  $rows = shift_roster_list($d, $companyId, $from, $to, []);
  $leaveMap = shift_leave_dates_map($d, $from, $to);
  $out = [];
  foreach($rows as $r){
    $att = shift_attendance_status_on($d, $r['rosterDate'], $r['empId']);
    $scheduledIn = $r['startTime'];
    $scheduledOut = $r['endTime'];
    $actualIn = null;
    $actualOut = null;
    $workHours = 0.0;
    if($att === 'P' && $scheduledIn && $scheduledOut){
      $workHours = round(shift_duration_minutes((string)$scheduledIn, (string)$scheduledOut) / 60, 2);
    }
    $isLeave = !empty($leaveMap[up($r['empId']).'|'.$r['rosterDate']]);
    $status = $att !== '' ? $att : ($isLeave ? 'LEAVE' : 'NA');
    $shiftMismatch = false;
    if(strtoupper($r['shiftType']) === 'OFF' && $att === 'P') $shiftMismatch = true;
    if(strtoupper($r['shiftType']) === 'WORKING' && in_array($att, ['WO','CL','SL','EL','LOP'], true)) $shiftMismatch = true;

    $out[] = [
      'date'=>$r['rosterDate'],'company'=>$companyName,'companyId'=>$companyId,'empId'=>$r['empId'],'employeeName'=>$r['employeeName'],
      'shiftCode'=>$r['shiftCode'],'shiftName'=>$r['shiftName'],'scheduledIn'=>$scheduledIn,'scheduledOut'=>$scheduledOut,
      'actualIn'=>$actualIn,'actualOut'=>$actualOut,'workHours'=>$workHours,'status'=>$status,
      'lateMark'=>false,'earlyExit'=>false,'overtime'=>0,'shiftMismatch'=>$shiftMismatch
    ];
  }
  return $out;
}
function shift_dashboard_summary(array $companyIds): array {
  $today = gmdate('Y-m-d');
  $next7 = gmdate('Y-m-d', strtotime('+6 day UTC'));
  $totCompaniesUsing = 0;
  $activeShifts = 0;
  $assignedEmp = 0;
  $todayShifts = 0;
  $weeklyOffToday = 0;
  $leaveToday = 0;
  $nightShiftToday = 0;
  $missingRosters = 0;
  $withoutDefault = 0;
  $recentUpdates = [];
  $distByCompany = [];

  foreach($companyIds as $cid){
    $d = shift_db_for_company((int)$cid);
    init_shift_schema($d);
    $hasUsage = (int)($d->query("SELECT (SELECT COUNT(*) FROM shift_master)+(SELECT COUNT(*) FROM shift_rosters)+(SELECT COUNT(*) FROM employee_shift_assignments) AS c")->fetch()['c'] ?? 0) > 0;
    if($hasUsage) $totCompaniesUsing++;
    $activeShifts += (int)($d->query("SELECT COUNT(*) AS c FROM shift_master WHERE status='Active'")->fetch()['c'] ?? 0);
    $assignedEmp += (int)($d->query("SELECT COUNT(DISTINCT emp_id) AS c FROM shift_rosters")->fetch()['c'] ?? 0);
    $todayRows = shift_roster_list($d, (int)$cid, $today, $today, []);
    $todayShifts += count($todayRows);
    foreach($todayRows as $r){
      if(strtolower($r['shiftType']) === 'off') $weeklyOffToday++;
      if(strtolower($r['shiftType']) === 'leave') $leaveToday++;
      if(strtolower($r['shiftCode']) === 'ns' || (string)$r['startTime'] >= '20:00') $nightShiftToday++;
    }
    $emap = $d->query("SELECT id FROM employees WHERE lower(status) <> 'inactive'")->fetchAll();
    $assignedMap = [];
    $q = $d->query("SELECT DISTINCT emp_id FROM employee_shift_assignments WHERE status='Active'")->fetchAll();
    foreach($q as $a){ $assignedMap[up($a['emp_id'] ?? '')] = true; }
    foreach($emap as $e){ if(empty($assignedMap[up($e['id'] ?? '')])) $withoutDefault++; }
    $missing = $d->prepare("SELECT COUNT(*) AS c FROM employees e WHERE lower(e.status) <> 'inactive' AND NOT EXISTS (SELECT 1 FROM shift_rosters r WHERE r.emp_id=e.id AND r.roster_date BETWEEN ? AND ?)");
    $missing->execute([$today, $next7]);
    $missingRosters += (int)($missing->fetch()['c'] ?? 0);
    $recent = $d->query("SELECT emp_id, roster_date, shift_code, updated_at FROM shift_rosters ORDER BY updated_at DESC LIMIT 5")->fetchAll();
    $cName = shift_company_name((int)$cid);
    foreach($recent as $rr){
      $recentUpdates[] = ['companyId'=>(int)$cid,'companyName'=>$cName,'empId'=>(string)$rr['emp_id'],'rosterDate'=>(string)$rr['roster_date'],'shiftCode'=>(string)$rr['shift_code'],'updatedAt'=>(string)$rr['updated_at']];
    }
    $q2 = $d->prepare("SELECT COALESCE(e.dept,'Unmapped') AS department, COUNT(*) AS c FROM shift_rosters r LEFT JOIN employees e ON e.id=r.emp_id WHERE r.roster_date=? GROUP BY COALESCE(e.dept,'Unmapped') ORDER BY c DESC");
    $q2->execute([$today]);
    foreach($q2->fetchAll() as $dr){
      $distByCompany[] = ['companyId'=>(int)$cid,'companyName'=>$cName,'department'=>(string)$dr['department'],'count'=>(int)$dr['c']];
    }
  }
  usort($recentUpdates, fn($a,$b)=>strcmp((string)$b['updatedAt'], (string)$a['updatedAt']));
  $recentUpdates = array_slice($recentUpdates, 0, 20);
  return [
    'today'=>$today,
    'totals'=>['companiesUsingModule'=>$totCompaniesUsing,'activeShifts'=>$activeShifts,'employeesAssignedInRoster'=>$assignedEmp,'todayShifts'=>$todayShifts,'weeklyOffToday'=>$weeklyOffToday,'leaveToday'=>$leaveToday,'nightShiftToday'=>$nightShiftToday,'upcomingConflictsOrMissingRosters'=>$missingRosters,'employeesWithoutDefaultShift'=>$withoutDefault],
    'todayShiftSummary'=>['date'=>$today,'total'=>$todayShifts,'off'=>$weeklyOffToday,'leave'=>$leaveToday,'night'=>$nightShiftToday],
    'upcoming7DaysShiftSummary'=>['from'=>$today,'to'=>$next7,'missingRosters'=>$missingRosters],
    'recentRosterUpdates'=>$recentUpdates,
    'shiftDistributionByCompanyDepartment'=>$distByCompany
  ];
}
function shift_my_roster(PDO $d, int $companyId, string $empId, string $from, string $to): array {
  $rows = shift_roster_list($d, $companyId, $from, $to, ['empId'=>$empId]);
  $today = gmdate('Y-m-d');
  $todayRow = null;
  foreach($rows as $r){ if($r['rosterDate'] === $today){ $todayRow = $r; break; } }
  return ['empId'=>$empId,'from'=>$from,'to'=>$to,'todayShift'=>$todayRow,'rows'=>$rows];
}
function shift_csv(array $rows): void {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="roster-vs-attendance.csv"');
  $out = fopen('php://output', 'w');
  fputcsv($out, ['Date','Company','Emp ID','Employee Name','Shift Code','Shift Name','Scheduled In','Scheduled Out','Actual In','Actual Out','Work Hours','Status','Late Mark','Early Exit','Overtime','Shift Mismatch']);
  foreach($rows as $r){
    fputcsv($out, [$r['date'],$r['company'],$r['empId'],$r['employeeName'],$r['shiftCode'],$r['shiftName'],$r['scheduledIn'],$r['scheduledOut'],$r['actualIn'],$r['actualOut'],$r['workHours'],$r['status'],$r['lateMark']?'Yes':'No',$r['earlyExit']?'Yes':'No',$r['overtime'],$r['shiftMismatch']?'Yes':'No']);
  }
  fclose($out);
  exit;
}
function shift_route_handle(string $path, string $method): bool {
  if(!str_starts_with($path, '/api/shift') && !str_starts_with($path, '/api/roster') && !str_starts_with($path, '/api/my-shifts')) return false;
  shift_require_access();

  if($path === '/api/shift/dashboard'){
    meth('GET');
    $ids = shift_company_ids_scope(true);
    j(shift_dashboard_summary($ids));
  }

  if($path === '/api/shifts'){
    if($method === 'GET'){
      $ids = shift_company_ids_scope(isset($_GET['all']) && (string)$_GET['all']==='1');
      $activeOnly = isset($_GET['active']) && (string)$_GET['active']==='1';
      $all = [];
      foreach($ids as $cid){
        $d = shift_db_for_company((int)$cid);
        init_shift_schema($d);
        $all = array_merge($all, shift_master_rows($d, (int)$cid, $activeOnly));
      }
      j(['rows'=>$all,'count'=>count($all)]);
    }
    if($method === 'POST'){
      $b = body();
      $cid = shift_write_company_id($b);
      $d = shift_db_for_company($cid);
      init_shift_schema($d);
      j(['row'=>shift_master_upsert($d, $cid, $b, false)]);
    }
    j(['detail'=>'Method Not Allowed'],405);
  }
  if(preg_match('#^/api/shifts/(\d+)$#', $path, $mm)){
    $id = (int)$mm[1];
    $b = body();
    $cid = shift_write_company_id($b);
    $d = shift_db_for_company($cid);
    init_shift_schema($d);
    if($method === 'PUT') j(['row'=>shift_master_upsert($d, $cid, $b + ['id'=>$id], true)]);
    if($method === 'DELETE'){ shift_master_delete($d, $id); j(['status'=>'deleted']); }
    j(['detail'=>'Method Not Allowed'],405);
  }

  if($path === '/api/shift-assignments'){
    if($method === 'GET'){
      $ids = shift_company_ids_scope(isset($_GET['all']) && (string)$_GET['all']==='1');
      $all = [];
      foreach($ids as $cid){
        $d = shift_db_for_company((int)$cid);
        init_shift_schema($d);
        $all = array_merge($all, shift_assignment_rows($d, (int)$cid));
      }
      j(['rows'=>$all,'count'=>count($all)]);
    }
    if($method === 'POST'){
      $b = body();
      $cid = shift_write_company_id($b);
      $d = shift_db_for_company($cid);
      init_shift_schema($d);
      j(['row'=>shift_assignment_upsert($d, $cid, $b, false)]);
    }
    j(['detail'=>'Method Not Allowed'],405);
  }
  if(preg_match('#^/api/shift-assignments/(\d+)$#', $path, $mm)){
    $id = (int)$mm[1];
    $b = body();
    $cid = shift_write_company_id($b);
    $d = shift_db_for_company($cid);
    init_shift_schema($d);
    if($method === 'PUT') j(['row'=>shift_assignment_upsert($d, $cid, $b + ['id'=>$id], true)]);
    if($method === 'DELETE'){ shift_assignment_delete($d, $id); j(['status'=>'deleted']); }
    j(['detail'=>'Method Not Allowed'],405);
  }

  if($path === '/api/rosters'){
    meth('GET');
    $from = shift_parse_date(s($_GET['from'] ?? date('Y-m-d')), 'from');
    $to = shift_parse_date(s($_GET['to'] ?? $from), 'to');
    $ids = shift_company_ids_scope(isset($_GET['all']) && (string)$_GET['all']==='1');
    $filters = ['department'=>$_GET['department'] ?? '', 'designation'=>$_GET['designation'] ?? '', 'empId'=>$_GET['empId'] ?? '', 'shiftCode'=>$_GET['shiftCode'] ?? ''];
    $all = [];
    foreach($ids as $cid){
      $d = shift_db_for_company((int)$cid);
      init_shift_schema($d);
      $all = array_merge($all, shift_roster_list($d, (int)$cid, $from, $to, $filters));
    }
    j(['rows'=>$all,'count'=>count($all)]);
  }
  if($path === '/api/rosters/delete-cell'){
    meth('POST');
    $b = body();
    $cid = shift_write_company_id($b);
    $d = shift_db_for_company($cid);
    init_shift_schema($d);
    $empId = up($b['empId'] ?? '');
    $rosterDate = s($b['rosterDate'] ?? '');
    j(['status'=>'ok'] + shift_roster_delete_row($d, $empId, $rosterDate));
  }
  if($path === '/api/rosters/bulk-delete'){
    meth('POST');
    $b = body();
    $cid = shift_write_company_id($b);
    $d = shift_db_for_company($cid);
    init_shift_schema($d);
    $rows = isset($b['rows']) && is_array($b['rows']) ? $b['rows'] : [];
    j(['status'=>'ok'] + shift_roster_bulk_delete($d, $rows));
  }
  if($path === '/api/rosters/bulk-upsert'){
    meth('POST');
    $b = body();
    $cid = shift_write_company_id($b);
    $d = shift_db_for_company($cid);
    init_shift_schema($d);
    $res = shift_roster_upsert_rows($d, (array)($b['rows'] ?? []), shift_actor_name());
    j(['status'=>'ok'] + $res);
  }
  if($path === '/api/rosters/auto-fill-week'){
    meth('POST');
    $b = body();
    $cid = shift_write_company_id($b);
    $d = shift_db_for_company($cid);
    init_shift_schema($d);
    $start = shift_parse_date(s($b['weekStartDate'] ?? ''), 'weekStartDate');
    $end = shift_parse_date(s($b['weekEndDate'] ?? gmdate('Y-m-d', strtotime($start.' +6 day UTC'))), 'weekEndDate');
    j(shift_roster_autofill_week($d, $start, $end, shift_actor_name(), (array)$b));
  }
  if($path === '/api/rosters/copy-previous-week'){
    meth('POST');
    $b = body();
    $cid = shift_write_company_id($b);
    $d = shift_db_for_company($cid);
    init_shift_schema($d);
    $start = shift_parse_date(s($b['weekStartDate'] ?? ''), 'weekStartDate');
    $end = shift_parse_date(s($b['weekEndDate'] ?? gmdate('Y-m-d', strtotime($start.' +6 day UTC'))), 'weekEndDate');
    j(shift_roster_copy_previous_week($d, $start, $end, shift_actor_name()));
  }
  if($path === '/api/rosters/week-status'){
    if($method === 'GET'){
      $cid = shift_write_company_id([]);
      $d = shift_db_for_company($cid);
      init_shift_schema($d);
      $start = shift_parse_date(s($_GET['weekStartDate'] ?? ''), 'weekStartDate');
      $end = shift_parse_date(s($_GET['weekEndDate'] ?? gmdate('Y-m-d', strtotime($start.' +6 day UTC'))), 'weekEndDate');
      j(shift_week_status_get($d, $start, $end));
    }
    if($method === 'POST'){
      $b = body();
      $cid = shift_write_company_id($b);
      $d = shift_db_for_company($cid);
      init_shift_schema($d);
      $start = shift_parse_date(s($b['weekStartDate'] ?? ''), 'weekStartDate');
      $end = shift_parse_date(s($b['weekEndDate'] ?? gmdate('Y-m-d', strtotime($start.' +6 day UTC'))), 'weekEndDate');
      $locked = b($b['isLocked'] ?? false);
      $pub = s($b['publishStatus'] ?? 'Draft', 'Draft');
      j(shift_week_status_set($d, $start, $end, $locked, $pub, shift_actor_name()));
    }
    j(['detail'=>'Method Not Allowed'],405);
  }

  if($path === '/api/shift-calendar/events'){
    meth('GET');
    $from = shift_parse_date(s($_GET['from'] ?? date('Y-m-01')), 'from');
    $to = shift_parse_date(s($_GET['to'] ?? date('Y-m-t')), 'to');
    $ids = shift_company_ids_scope(isset($_GET['all']) && (string)$_GET['all']==='1');
    $filters = ['department'=>$_GET['department'] ?? '', 'empId'=>$_GET['empId'] ?? '', 'shiftCode'=>$_GET['shiftCode'] ?? ''];
    $events = [];
    $daySummaryMap = [];
    foreach($ids as $cid){
      $d = shift_db_for_company((int)$cid);
      init_shift_schema($d);
      $r = shift_calendar_events($d, (int)$cid, $from, $to, $filters);
      $events = array_merge($events, $r['events']);
      foreach($r['daySummary'] as $dr){
        $date = (string)$dr['date'];
        if(!isset($daySummaryMap[$date])) $daySummaryMap[$date] = ['date'=>$date,'totalScheduled'=>0,'leaveCount'=>0,'offCount'=>0,'nightShiftCount'=>0];
        $daySummaryMap[$date]['totalScheduled'] += (int)$dr['totalScheduled'];
        $daySummaryMap[$date]['leaveCount'] += (int)$dr['leaveCount'];
        $daySummaryMap[$date]['offCount'] += (int)$dr['offCount'];
        $daySummaryMap[$date]['nightShiftCount'] += (int)$dr['nightShiftCount'];
      }
    }
    ksort($daySummaryMap);
    j(['events'=>$events,'daySummary'=>array_values($daySummaryMap)]);
  }

  if($path === '/api/roster-attendance-report'){
    meth('GET');
    $from = shift_parse_date(s($_GET['from'] ?? date('Y-m-01')), 'from');
    $to = shift_parse_date(s($_GET['to'] ?? date('Y-m-t')), 'to');
    $ids = shift_company_ids_scope(isset($_GET['all']) && (string)$_GET['all']==='1');
    $all = [];
    foreach($ids as $cid){
      $d = shift_db_for_company((int)$cid);
      init_shift_schema($d);
      $all = array_merge($all, shift_roster_attendance_report($d, (int)$cid, $from, $to));
    }
    if((string)($_GET['format'] ?? '') === 'csv') shift_csv($all);
    j(['rows'=>$all,'count'=>count($all)]);
  }

  if($path === '/api/my-shifts'){
    meth('GET');
    $ctx = auth_ctx(true);
    $role = strtolower((string)($ctx['role'] ?? ''));
    $cid = (int)($ctx['clientId'] ?? 0);
    if($cid <= 0) $cid = req_client_id();
    if($cid <= 0) bad('Client scope is required');
    $empId = '';
    if($role === 'employee') $empId = up($ctx['empId'] ?? '');
    if($empId === '') $empId = up($_GET['empId'] ?? '');
    if($empId === '') bad('empId is required');
    $from = shift_parse_date(s($_GET['from'] ?? date('Y-m-d')), 'from');
    $to = shift_parse_date(s($_GET['to'] ?? gmdate('Y-m-d', strtotime($from.' +14 day UTC'))), 'to');
    $d = shift_db_for_company($cid);
    init_shift_schema($d);
    j(shift_my_roster($d, $cid, $empId, $from, $to));
  }

  return false;
}
