<?php
declare(strict_types=1);

date_default_timezone_set('UTC');
if (!defined('STORAGE_DIR')) {
  define('STORAGE_DIR', dirname(__DIR__, 2) . '/storage/app/clients');
}
if (!defined('CENTRAL_DB_PATH')) {
  define('CENTRAL_DB_PATH', STORAGE_DIR . '/app.db');
}
if (!defined('LEGACY_DB_PATH')) {
  define('LEGACY_DB_PATH', __DIR__ . '/app.db');
}
const AUTH_TOKEN_TTL = 43200; // 12 hours

const DEFAULT_CONTROL = [
  "pfEmpPct"=>12.0,"pfErPct"=>13.0,"esiEmpPct"=>0.75,"esiErPct"=>3.25,"esiWageLimit"=>21000,
  "ptMonthly"=>200,"ptEnabled"=>"Yes","pfWageCapEnabled"=>"Yes","pfWageCapAmount"=>15000,"pfOnEsiPct"=>70.0,"daPctBasic"=>0,
  "lwfEnabled"=>"Yes","lwfEmpAmt"=>20,"lwfErAmt"=>40,"lwfMonth"=>0,
  "bonusEnabled"=>"Yes","bonusMinimumWage"=>0.0,"bonusMultiplierMonths"=>12.0,"bonusPercent"=>8.33,
  "gratuityMode"=>"after_5yr","gratuityMinYears"=>5.0,
  "ctcBasicPct"=>50.0,"ctcHraPct"=>10.0,"ctcConvPct"=>0.0,"ctcDaPct"=>30.0,"ctcEduPct"=>0.0,"ctcSpecialPct"=>0.0,
  "incomeTaxSlabs"=>[
    ["income"=>"Up to Rs 3L","taxPct"=>0],
    ["income"=>"Rs 3L - Rs 6L","taxPct"=>5],
    ["income"=>"Rs 6L - Rs 9L","taxPct"=>10],
    ["income"=>"Rs 9L - Rs 12L","taxPct"=>15],
    ["income"=>"Rs 12L - Rs 15L","taxPct"=>20],
    ["income"=>"Above Rs 15L","taxPct"=>30]
  ],
  "ctcAddonRows"=>[
    ["code"=>"pfEmployerPct","name"=>"PF Employer %","type"=>"percent","value"=>13.0],
    ["code"=>"esiEmployerPct","name"=>"ESI Employer %","type"=>"percent","value"=>3.25]
  ],
  "companyName"=>"","companyAddress"=>"",
  "companyRegNo"=>"","companyPAN"=>"","companyTAN"=>"","companyGSTIN"=>"","companyContact"=>""
];
const DEFAULT_PROFILE = [
  "companyName"=>"","companyAddress"=>"",
  "city"=>"","state"=>"","pincode"=>"","country"=>"","website"=>"",
  "regNo"=>"","pan"=>"","tan"=>"","gstin"=>"",
  "pfEstId"=>"","esicCode"=>"","contactName"=>"","contactNo"=>"","email"=>"","altContactNo"=>"","notes"=>""
];
const DEFAULT_AUTH_USERS = [
  ["username"=>"admin","password"=>"123456","name"=>"Admin","role"=>"super_admin"],
  ["username"=>"admin@hrseva.com","password"=>"123456","name"=>"Admin","role"=>"super_admin"]
];
const FACE_ATTENDANCE_DEFAULT_MODEL_URL = '/assets/vendor/face-api-models';
const FACE_ATTENDANCE_DEFAULT_TIMEZONE = 'Asia/Kolkata';
function now_iso(): string { return gmdate('Y-m-d\\TH:i:s\\Z'); }
function j($x,int $s=200): void {
  if (class_exists(\App\Exceptions\LegacyApiResponseException::class)) {
    throw new \App\Exceptions\LegacyApiResponseException($x, $s);
  }
  http_response_code($s);
  header('Content-Type: application/json');
  echo json_encode($x,JSON_UNESCAPED_UNICODE);
  exit;
}
function bad(string $m): void { j(["detail"=>$m],400); }
function nf(string $m): void { j(["detail"=>$m],404); }
function meth(string $m): void { if($_SERVER['REQUEST_METHOD']!==$m) j(["detail"=>"Method Not Allowed"],405); }
function s($v,$d=''){ $x=trim((string)$v); return $x===''?$d:$x; }
function up($v){ return strtoupper(trim((string)$v)); }
function f($v,$d=0.0){ return is_numeric($v)?(float)$v:(float)$d; }
function b($v){ if(is_bool($v)) return $v; $x=strtolower(trim((string)$v)); return in_array($x,['1','true','yes','y'],true); }
require_once __DIR__ . '/mail.php';
function app_secret(): string {
  $env = getenv('HR_APP_SECRET');
  if(is_string($env) && trim($env) !== '') return trim($env);
  return 'change-this-secret-before-production';
}
function b64url_enc(string $raw): string { return rtrim(strtr(base64_encode($raw), '+/', '-_'), '='); }
function b64url_dec(string $txt): string {
  $pad = 4 - (strlen($txt) % 4);
  if($pad < 4) $txt .= str_repeat('=', $pad);
  $x = base64_decode(strtr($txt, '-_', '+/'), true);
  return $x === false ? '' : $x;
}
function token_sign(array $payload): string {
  $h = ['alg'=>'HS256','typ'=>'JWT'];
  $h64 = b64url_enc((string)json_encode($h, JSON_UNESCAPED_UNICODE));
  $p64 = b64url_enc((string)json_encode($payload, JSON_UNESCAPED_UNICODE));
  $sig = hash_hmac('sha256', $h64.'.'.$p64, app_secret(), true);
  return $h64.'.'.$p64.'.'.b64url_enc($sig);
}
function token_verify(string $token): ?array {
  $parts = explode('.', trim($token));
  if(count($parts) !== 3) return null;
  [$h64,$p64,$s64] = $parts;
  $sig = b64url_dec($s64);
  if($sig === '') return null;
  $calc = hash_hmac('sha256', $h64.'.'.$p64, app_secret(), true);
  if(!hash_equals($calc, $sig)) return null;
  $payloadRaw = b64url_dec($p64);
  if($payloadRaw === '') return null;
  $p = json_decode($payloadRaw, true);
  if(!is_array($p)) return null;
  $exp = (int)($p['exp'] ?? 0);
  if($exp <= 0 || $exp < time()) return null;
  return $p;
}
function auth_header_token(): string {
  if (function_exists('request') && request()) {
    $h = (string) request()->header('Authorization', '');
    if (preg_match('/^\s*Bearer\s+(.+)\s*$/i', $h, $m)) {
      return trim((string) $m[1]);
    }
  }
  $h = (string)($_SERVER['HTTP_AUTHORIZATION'] ?? '');
  if($h === '') $h = (string)($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
  if(preg_match('/^\s*Bearer\s+(.+)\s*$/i', $h, $m)) return trim((string)$m[1]);
  return '';
}
function client_subscription_access_state(int $clientId): array {
  if($clientId <= 0) return ["active"=>false, "reason"=>"Invalid client", "endDate"=>null];
  $q = central_db()->prepare("SELECT id, status, end_date, renewal_date, updated_at FROM subscriptions WHERE client_id=? ORDER BY end_date DESC, renewal_date DESC, id DESC LIMIT 1");
  $q->execute([$clientId]);
  $row = $q->fetch();
  if(!$row){
    return ["active"=>false, "reason"=>"No active subscription found", "endDate"=>null];
  }

  $status = strtolower(trim((string)($row['status'] ?? '')));
  $blockedStatuses = ['expired','cancelled','canceled','inactive','terminated','disabled','closed'];
  if(in_array($status, $blockedStatuses, true)){
    return ["active"=>false, "reason"=>"Subscription status is ".($row['status'] ?? 'Expired'), "endDate"=>(string)($row['end_date'] ?? '')];
  }

  $endDateRaw = trim((string)($row['end_date'] ?? ''));
  if($endDateRaw === ''){
    $endDateRaw = trim((string)($row['renewal_date'] ?? ''));
  }
  if($endDateRaw === ''){
    return ["active"=>false, "reason"=>"Subscription end date is missing", "endDate"=>null];
  }
  $endTs = strtotime($endDateRaw . ' 23:59:59 UTC');
  if($endTs === false){
    return ["active"=>false, "reason"=>"Subscription end date is invalid", "endDate"=>$endDateRaw];
  }
  if(time() > $endTs){
    return ["active"=>false, "reason"=>"Subscription expired on ".$endDateRaw, "endDate"=>$endDateRaw];
  }
  return ["active"=>true, "reason"=>"", "endDate"=>$endDateRaw];
}
function auth_ctx(bool $required = true): ?array {
  static $cache = null;
  static $loaded = false;
  static $requestKey = null;
  $currentKey = (function_exists('request') && request()) ? spl_object_id(request()) : null;
  if ($currentKey !== null && request()->attributes->has('hr_token')) {
    if ($requestKey !== $currentKey) {
      $cache = request()->attributes->get('hr_token');
      $loaded = true;
      $requestKey = $currentKey;
    }
  } elseif (!$loaded || ($currentKey !== null && $requestKey !== $currentKey)) {
    $tok = auth_header_token();
    $cache = $tok !== '' ? token_verify($tok) : null;
    $loaded = true;
    $requestKey = $currentKey;
  }
  if($cache){
    $role = strtolower((string)($cache['role'] ?? ''));
    $clientId = (int)($cache['clientId'] ?? 0);
    if(($role === 'client' || $role === 'employee') && $clientId > 0){
      // Always resolve current access permissions from DB so Access Control updates
      // are effective immediately without requiring user relogin.
      $acc = access_get($clientId);
      $basePerm = $acc['permissions'] ?? access_default_permissions();
      if($role === 'employee'){
        $empId = up($cache['empId'] ?? '');
        $staff = staff_user_get_by_username((string)($cache['sub'] ?? ''));
        $rolePerm = access_default_permissions();
        if($staff && (int)($staff['clientId'] ?? 0) === $clientId && up($staff['empId'] ?? '') === $empId && strtolower((string)($staff['status'] ?? 'active')) === 'active'){
          $rolePerm = staff_role_permissions($clientId, (string)($staff['roleCode'] ?? ''));
        }
        $cache['permissions'] = perm_intersect($basePerm, $rolePerm);
      } else {
        $cache['permissions'] = $basePerm;
      }
      $sub = client_subscription_access_state($clientId);
      if(empty($sub['active'])){
        if($required) j(['detail'=>'Subscription expired. Access denied.', 'reason'=>$sub['reason'] ?? '', 'endDate'=>$sub['endDate'] ?? null],403);
        return null;
      }
    }
  }
  if($required && !$cache) j(['detail'=>'Unauthorized'],401);
  return $cache;
}
function auth_role(): string {
  $ctx = auth_ctx(false);
  return strtolower((string)($ctx['role'] ?? ''));
}
function auth_client_id(): int {
  $ctx = auth_ctx(false);
  return (int)($ctx['clientId'] ?? 0);
}
function require_super_admin(): void {
  $ctx = auth_ctx(true);
  if(strtolower((string)($ctx['role'] ?? '')) !== 'super_admin') j(['detail'=>'Forbidden'],403);
}
function login_attempts_init(PDO $d): void {
  $d->exec("CREATE TABLE IF NOT EXISTS auth_login_attempts (key TEXT PRIMARY KEY, fails INTEGER NOT NULL DEFAULT 0, first_at INTEGER NOT NULL DEFAULT 0, blocked_until INTEGER NOT NULL DEFAULT 0, updated_at INTEGER NOT NULL DEFAULT 0)");
}
function login_attempt_key(string $username): string {
  $ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
  return strtolower(trim($username)).'|'.$ip;
}
function login_rate_limit_check(string $username): void {
  $d = central_db();
  login_attempts_init($d);
  $k = login_attempt_key($username);
  $q = $d->prepare("SELECT fails, first_at, blocked_until FROM auth_login_attempts WHERE key=?");
  $q->execute([$k]);
  $r = $q->fetch();
  if(!$r) return;
  $now = time();
  if((int)($r['blocked_until'] ?? 0) > $now){
    j(['detail'=>'Too many login attempts. Try again later.'],429);
  }
}
function login_rate_limit_fail(string $username): void {
  $d = central_db();
  login_attempts_init($d);
  $k = login_attempt_key($username);
  $now = time();
  $window = 600; // 10 min
  $block = 900; // 15 min
  $q = $d->prepare("SELECT fails, first_at FROM auth_login_attempts WHERE key=?");
  $q->execute([$k]);
  $r = $q->fetch();
  $fails = 1;
  $first = $now;
  if($r){
    $oldFirst = (int)($r['first_at'] ?? 0);
    $oldFails = (int)($r['fails'] ?? 0);
    if($oldFirst > 0 && ($now - $oldFirst) <= $window){
      $fails = $oldFails + 1;
      $first = $oldFirst;
    }
  }
  $blockedUntil = $fails >= 5 ? ($now + $block) : 0;
  $st = $d->prepare("INSERT INTO auth_login_attempts (key,fails,first_at,blocked_until,updated_at) VALUES (?,?,?,?,?) ON CONFLICT(key) DO UPDATE SET fails=excluded.fails,first_at=excluded.first_at,blocked_until=excluded.blocked_until,updated_at=excluded.updated_at");
  $st->execute([$k,$fails,$first,$blockedUntil,$now]);
}
function login_rate_limit_success(string $username): void {
  $d = central_db();
  login_attempts_init($d);
  $k = login_attempt_key($username);
  $d->prepare("DELETE FROM auth_login_attempts WHERE key=?")->execute([$k]);
}
function req_client_id(): int {
  $ctx = auth_ctx(false);
  $x = isset($_SERVER['HTTP_X_CLIENT_ID']) ? trim((string)$_SERVER['HTTP_X_CLIENT_ID']) : '';
  $headerId = ctype_digit($x) ? (int)$x : 0;
  if($ctx){
    $role = strtolower((string)($ctx['role'] ?? ''));
    if(in_array($role, ['client','client_admin','agency_admin','employee'], true)){
      return (int)($ctx['clientId'] ?? 0);
    }
    if($role === 'super_admin'){
      return $headerId > 0 ? $headerId : 0;
    }
  }
  return $headerId;
}
function db_path_for_client(int $clientId): string {
  if($clientId > 0){
    return STORAGE_DIR . '/tenant_' . $clientId . '/app.db';
  }
  return CENTRAL_DB_PATH;
}
function &hr_db_connection_pool(): array {
  static $pool = [];
  return $pool;
}
function db_reset_pool(): void {
  $pool = &hr_db_connection_pool();
  $pool = [];
  if (function_exists('app') && app()->bound(\App\Services\Tenant\TenantManager::class)) {
    try {
      \Illuminate\Support\Facades\DB::purge('central');
      \Illuminate\Support\Facades\DB::purge('tenant');
    } catch (Throwable $e) {}
  }
}
function db_open(string $path): PDO {
  $pool = &hr_db_connection_pool();
  if(isset($pool[$path])) return $pool[$path];
  $dir = dirname($path);
  if(!is_dir($dir)){
    @mkdir($dir, 0777, true);
  }
  if($path === CENTRAL_DB_PATH && !file_exists($path) && file_exists(LEGACY_DB_PATH)){
    @copy(LEGACY_DB_PATH, $path);
  }
  if (function_exists('app') && app()->bound(\App\Services\Tenant\TenantManager::class)) {
    $mgr = app(\App\Services\Tenant\TenantManager::class);
    if ($path === CENTRAL_DB_PATH && ($mgr->centralDriver() === 'mysql' || file_exists($path))) {
      $pool[$path] = $mgr->central()->getPdo();
      return $pool[$path];
    }
    $clientId = (int) preg_replace('/.*tenant_(\d+)\/.*/', '$1', $path);
    if ($clientId > 0 && ($mgr->tenantDriver() === 'mysql' || file_exists($path))) {
      $mgr->setClientId($clientId);
      $pool[$path] = $mgr->tenant()->getPdo();
      return $pool[$path];
    }
  }
  $pdo = new PDO('sqlite:'.$path);
  $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
  $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
  $pool[$path] = $pdo;
  return $pdo;
}
function central_db(): PDO { return db_open(CENTRAL_DB_PATH); }

function db(): PDO {
  return db_open(db_path_for_client(req_client_id()));
}
require_once __DIR__ . '/shift_module.php';
function init_schema(PDO $d): void {
  if (class_exists(\App\Services\Database\HrSchemaInstaller::class)) {
    \App\Services\Database\HrSchemaInstaller::install($d);
    return;
  }
  $d->exec("CREATE TABLE IF NOT EXISTS app_kv (key TEXT PRIMARY KEY, value TEXT NOT NULL, updated_at TEXT NOT NULL)");
  $d->exec("CREATE TABLE IF NOT EXISTS clients (id INTEGER PRIMARY KEY AUTOINCREMENT,company_name TEXT NOT NULL,company_address TEXT NOT NULL,company_reg_no TEXT NOT NULL,company_pan TEXT NOT NULL,company_tan TEXT NOT NULL,company_gstin TEXT NOT NULL,company_contact_no TEXT NOT NULL,company_email TEXT NOT NULL DEFAULT '',user_id TEXT NOT NULL DEFAULT '',user_password TEXT NOT NULL DEFAULT '',user_password_hash TEXT NOT NULL DEFAULT '',subscription_plan_id INTEGER NOT NULL DEFAULT 0,created_at TEXT NOT NULL,updated_at TEXT NOT NULL)");
  try { $d->exec("ALTER TABLE clients ADD COLUMN company_email TEXT NOT NULL DEFAULT ''"); } catch (Throwable $e) {}
  $d->exec("CREATE TABLE IF NOT EXISTS client_access (client_id INTEGER PRIMARY KEY, permissions TEXT NOT NULL, access_type TEXT NOT NULL DEFAULT 'custom', updated_at TEXT NOT NULL)");
  $d->exec("CREATE TABLE IF NOT EXISTS access_types (code TEXT PRIMARY KEY, name TEXT NOT NULL UNIQUE, permissions TEXT NOT NULL, is_system INTEGER NOT NULL DEFAULT 0, created_at TEXT NOT NULL, updated_at TEXT NOT NULL)");
  $d->exec("CREATE TABLE IF NOT EXISTS staff_roles (client_id INTEGER NOT NULL, code TEXT NOT NULL, name TEXT NOT NULL, permissions TEXT NOT NULL, created_at TEXT NOT NULL, updated_at TEXT NOT NULL, PRIMARY KEY (client_id, code))");
  $d->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_staff_roles_client_name ON staff_roles(client_id, name)");
  $d->exec("CREATE TABLE IF NOT EXISTS staff_users (id INTEGER PRIMARY KEY AUTOINCREMENT, client_id INTEGER NOT NULL, emp_id TEXT NOT NULL, username TEXT NOT NULL UNIQUE, password_hash TEXT NOT NULL, role_code TEXT NOT NULL, status TEXT NOT NULL DEFAULT 'Active', created_at TEXT NOT NULL, updated_at TEXT NOT NULL)");
  $d->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_staff_users_client_emp ON staff_users(client_id, emp_id)");
  $d->exec("CREATE TABLE IF NOT EXISTS subscriptions (id INTEGER PRIMARY KEY AUTOINCREMENT, client_id INTEGER NOT NULL, plan_name TEXT NOT NULL, start_date TEXT NOT NULL, end_date TEXT NOT NULL, renewal_date TEXT NOT NULL, status TEXT NOT NULL, amount REAL NOT NULL DEFAULT 0, notes TEXT NOT NULL DEFAULT '', created_at TEXT NOT NULL, updated_at TEXT NOT NULL)");
  $d->exec("CREATE TABLE IF NOT EXISTS subscription_plans (id INTEGER PRIMARY KEY AUTOINCREMENT, plan_name TEXT NOT NULL UNIQUE, duration_months INTEGER NOT NULL DEFAULT 12, amount REAL NOT NULL DEFAULT 0, status TEXT NOT NULL DEFAULT 'Active', features TEXT NOT NULL DEFAULT '', access_type_code TEXT NOT NULL DEFAULT 'full_access', created_at TEXT NOT NULL, updated_at TEXT NOT NULL)");
  $planCols = $d->query("PRAGMA table_info(subscription_plans)")->fetchAll();
  $planColNames = array_map(fn($c)=>(string)($c['name'] ?? ''), $planCols);
  if(!in_array('access_type_code', $planColNames, true)){ $d->exec("ALTER TABLE subscription_plans ADD COLUMN access_type_code TEXT NOT NULL DEFAULT 'full_access'"); }
  $clientCols = $d->query("PRAGMA table_info(clients)")->fetchAll();
  $clientColNames = array_map(fn($c)=>(string)($c['name'] ?? ''), $clientCols);
  if(!in_array('user_id', $clientColNames, true)){ $d->exec("ALTER TABLE clients ADD COLUMN user_id TEXT NOT NULL DEFAULT ''"); }
  if(!in_array('user_password', $clientColNames, true)){ $d->exec("ALTER TABLE clients ADD COLUMN user_password TEXT NOT NULL DEFAULT ''"); }
  if(!in_array('user_password_hash', $clientColNames, true)){ $d->exec("ALTER TABLE clients ADD COLUMN user_password_hash TEXT NOT NULL DEFAULT ''"); }
  if(!in_array('subscription_plan_id', $clientColNames, true)){ $d->exec("ALTER TABLE clients ADD COLUMN subscription_plan_id INTEGER NOT NULL DEFAULT 0"); }
  $accessCols = $d->query("PRAGMA table_info(client_access)")->fetchAll();
  $accessColNames = array_map(fn($c)=>(string)($c['name'] ?? ''), $accessCols);
  if(!in_array('access_type', $accessColNames, true)){ $d->exec("ALTER TABLE client_access ADD COLUMN access_type TEXT NOT NULL DEFAULT 'custom'"); }
  $d->exec("CREATE TABLE IF NOT EXISTS employees (id TEXT PRIMARY KEY,name TEXT NOT NULL,status TEXT NOT NULL,dept TEXT NOT NULL,desig TEXT NOT NULL,type TEXT NOT NULL,mobile TEXT NOT NULL,email TEXT NOT NULL,doj TEXT NOT NULL,pf TEXT NOT NULL,uan TEXT NOT NULL,esi TEXT NOT NULL,esi_no TEXT NOT NULL,pf_no TEXT NOT NULL DEFAULT '',bank_name TEXT NOT NULL DEFAULT '',bank_ac TEXT NOT NULL DEFAULT '',ifsc TEXT NOT NULL DEFAULT '',aadhar_no TEXT NOT NULL DEFAULT '',pan_card TEXT NOT NULL DEFAULT '',address TEXT NOT NULL DEFAULT '',base_ctc REAL NOT NULL DEFAULT 0,created_at TEXT NOT NULL,updated_at TEXT NOT NULL)");
  $cols = $d->query("PRAGMA table_info(employees)")->fetchAll();
  $colNames = array_map(fn($c)=>(string)($c['name'] ?? ''), $cols);
  $ensureTextCol = function(string $name) use ($d, $colNames): void {
    if(!in_array($name, $colNames, true)){ $d->exec("ALTER TABLE employees ADD COLUMN $name TEXT NOT NULL DEFAULT ''"); }
  };
  $ensureTextCol('pf_no');
  $ensureTextCol('bank_name');
  $ensureTextCol('bank_ac');
  $ensureTextCol('ifsc');
  $ensureTextCol('aadhar_no');
  $ensureTextCol('pan_card');
  $ensureTextCol('address');
  if(!in_array('base_ctc', $colNames, true)){ $d->exec("ALTER TABLE employees ADD COLUMN base_ctc REAL NOT NULL DEFAULT 0"); }
  $d->exec("CREATE TABLE IF NOT EXISTS employee_faces (id INTEGER PRIMARY KEY AUTOINCREMENT, employee_id TEXT NOT NULL, face_descriptor TEXT NOT NULL DEFAULT '[]', face_image TEXT NOT NULL DEFAULT '', created_at TEXT NOT NULL, updated_at TEXT NOT NULL DEFAULT '')");
  $d->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_employee_faces_employee ON employee_faces(employee_id)");
  $d->exec("CREATE TABLE IF NOT EXISTS attendance_settings (id INTEGER PRIMARY KEY CHECK (id = 1), in_allowed_from TEXT NOT NULL DEFAULT '08:00', in_allowed_till TEXT NOT NULL DEFAULT '11:00', late_mark_after TEXT NOT NULL DEFAULT '09:15', out_allowed_from TEXT NOT NULL DEFAULT '17:00', out_allowed_till TEXT NOT NULL DEFAULT '23:00', grace_time INTEGER NOT NULL DEFAULT 10, face_match_threshold REAL NOT NULL DEFAULT 0.48, timezone TEXT NOT NULL DEFAULT 'Asia/Kolkata', model_url TEXT NOT NULL DEFAULT '', auto_capture_seconds INTEGER NOT NULL DEFAULT 2, updated_at TEXT NOT NULL)");
  try { $d->exec("ALTER TABLE attendance_settings ADD COLUMN scan_distance_cm INTEGER NOT NULL DEFAULT 45"); } catch (Throwable $e) {}
  $d->exec("CREATE TABLE IF NOT EXISTS attendance (id INTEGER PRIMARY KEY AUTOINCREMENT, employee_id TEXT NOT NULL, attendance_date TEXT NOT NULL, in_time TEXT NOT NULL DEFAULT '', out_time TEXT NOT NULL DEFAULT '', total_working_hours REAL NOT NULL DEFAULT 0, attendance_status TEXT NOT NULL DEFAULT '', in_status TEXT NOT NULL DEFAULT '', out_status TEXT NOT NULL DEFAULT '', remarks TEXT NOT NULL DEFAULT '', source TEXT NOT NULL DEFAULT 'face', created_at TEXT NOT NULL, updated_at TEXT NOT NULL, UNIQUE(employee_id, attendance_date))");
  $d->exec("CREATE INDEX IF NOT EXISTS idx_attendance_date_employee ON attendance(attendance_date, employee_id)");
  $d->exec("CREATE TABLE IF NOT EXISTS attendance_logs (id INTEGER PRIMARY KEY AUTOINCREMENT, employee_id TEXT NOT NULL DEFAULT '', attendance_date TEXT NOT NULL DEFAULT '', action_type TEXT NOT NULL DEFAULT '', scan_time TEXT NOT NULL DEFAULT '', verification_score REAL NOT NULL DEFAULT 0, match_threshold REAL NOT NULL DEFAULT 0, is_verified INTEGER NOT NULL DEFAULT 0, message TEXT NOT NULL DEFAULT '', payload_json TEXT NOT NULL DEFAULT '{}', created_at TEXT NOT NULL)");
  $d->exec("CREATE INDEX IF NOT EXISTS idx_attendance_logs_emp_date ON attendance_logs(employee_id, attendance_date)");
  $d->exec("CREATE TABLE IF NOT EXISTS leaves (id INTEGER PRIMARY KEY AUTOINCREMENT,emp_id TEXT NOT NULL,emp_name TEXT NOT NULL,dept TEXT NOT NULL,desig TEXT NOT NULL,company TEXT NOT NULL,from_date TEXT NOT NULL,to_date TEXT NOT NULL,days REAL NOT NULL,leave_type TEXT NOT NULL,reason TEXT NOT NULL,status TEXT NOT NULL,half_day TEXT NOT NULL,marked_by TEXT NOT NULL,created_at TEXT NOT NULL,updated_at TEXT NOT NULL)");
  $d->exec("CREATE TABLE IF NOT EXISTS salary_advances (id TEXT PRIMARY KEY, emp_id TEXT NOT NULL, employee_name TEXT NOT NULL, amount REAL NOT NULL DEFAULT 0, repayment_type TEXT NOT NULL DEFAULT 'full', emi_months INTEGER NOT NULL DEFAULT 1, emi_amount REAL NOT NULL DEFAULT 0, disbursed_on TEXT NOT NULL, start_year INTEGER NOT NULL DEFAULT 0, start_month INTEGER NOT NULL DEFAULT 0, attendance_year INTEGER NOT NULL DEFAULT 0, attendance_month INTEGER NOT NULL DEFAULT 0, attendance_through_date TEXT NOT NULL DEFAULT '', present_days REAL NOT NULL DEFAULT 0, eligible_salary REAL NOT NULL DEFAULT 0, monthly_gross REAL NOT NULL DEFAULT 0, notes TEXT NOT NULL DEFAULT '', status TEXT NOT NULL DEFAULT 'Active', created_by TEXT NOT NULL DEFAULT '', created_at TEXT NOT NULL, updated_at TEXT NOT NULL)");
  $advanceCols = $d->query("PRAGMA table_info(salary_advances)")->fetchAll();
  $advanceColNames = array_map(fn($c)=>(string)($c['name'] ?? ''), $advanceCols);
  if(!in_array('attendance_year', $advanceColNames, true)){ $d->exec("ALTER TABLE salary_advances ADD COLUMN attendance_year INTEGER NOT NULL DEFAULT 0"); }
  if(!in_array('attendance_month', $advanceColNames, true)){ $d->exec("ALTER TABLE salary_advances ADD COLUMN attendance_month INTEGER NOT NULL DEFAULT 0"); }
  if(!in_array('attendance_through_date', $advanceColNames, true)){ $d->exec("ALTER TABLE salary_advances ADD COLUMN attendance_through_date TEXT NOT NULL DEFAULT ''"); }
  if(!in_array('present_days', $advanceColNames, true)){ $d->exec("ALTER TABLE salary_advances ADD COLUMN present_days REAL NOT NULL DEFAULT 0"); }
  if(!in_array('eligible_salary', $advanceColNames, true)){ $d->exec("ALTER TABLE salary_advances ADD COLUMN eligible_salary REAL NOT NULL DEFAULT 0"); }
  if(!in_array('monthly_gross', $advanceColNames, true)){ $d->exec("ALTER TABLE salary_advances ADD COLUMN monthly_gross REAL NOT NULL DEFAULT 0"); }
  $d->exec("CREATE INDEX IF NOT EXISTS idx_salary_advances_emp_status ON salary_advances(emp_id, status)");
  $d->exec("CREATE TABLE IF NOT EXISTS incentives (id TEXT PRIMARY KEY, emp_id TEXT NOT NULL, employee_name TEXT NOT NULL DEFAULT '', incentive_date TEXT NOT NULL, amount REAL NOT NULL DEFAULT 0, remarks TEXT NOT NULL DEFAULT '', created_at TEXT NOT NULL, updated_at TEXT NOT NULL)");
  $d->exec("CREATE INDEX IF NOT EXISTS idx_incentives_emp_date ON incentives(emp_id, incentive_date)");
  $d->exec("CREATE TABLE IF NOT EXISTS loans (id TEXT PRIMARY KEY, emp_id TEXT NOT NULL, employee_name TEXT NOT NULL DEFAULT '', dept TEXT NOT NULL DEFAULT '', designation TEXT NOT NULL DEFAULT '', property_branch TEXT NOT NULL DEFAULT '', loan_type TEXT NOT NULL DEFAULT '', requested_amount REAL NOT NULL DEFAULT 0, reason TEXT NOT NULL DEFAULT '', request_date TEXT NOT NULL DEFAULT '', required_date TEXT NOT NULL DEFAULT '', repayment_type TEXT NOT NULL DEFAULT 'one_time', emi_start_year INTEGER NOT NULL DEFAULT 0, emi_start_month INTEGER NOT NULL DEFAULT 0, emi_amount REAL NOT NULL DEFAULT 0, installment_count INTEGER NOT NULL DEFAULT 1, remarks TEXT NOT NULL DEFAULT '', status TEXT NOT NULL DEFAULT 'Active', created_by TEXT NOT NULL DEFAULT '', created_at TEXT NOT NULL, updated_at TEXT NOT NULL)");
  $d->exec("CREATE INDEX IF NOT EXISTS idx_loans_emp_status ON loans(emp_id, status)");
  $d->exec("CREATE TABLE IF NOT EXISTS loan_deductions (id INTEGER PRIMARY KEY AUTOINCREMENT, loan_id TEXT NOT NULL, emp_id TEXT NOT NULL, deduction_year INTEGER NOT NULL, deduction_month INTEGER NOT NULL, scheduled_amount REAL NOT NULL DEFAULT 0, deducted_amount REAL NOT NULL DEFAULT 0, balance_after REAL NOT NULL DEFAULT 0, payroll_period TEXT NOT NULL DEFAULT '', payroll_sheet_id TEXT NOT NULL DEFAULT '', status TEXT NOT NULL DEFAULT 'Scheduled', created_at TEXT NOT NULL, updated_at TEXT NOT NULL, UNIQUE(loan_id, deduction_year, deduction_month))");
  $d->exec("CREATE INDEX IF NOT EXISTS idx_loan_deductions_emp_period ON loan_deductions(emp_id, deduction_year, deduction_month)");
  $d->exec("CREATE TABLE IF NOT EXISTS overtime_entries (id TEXT PRIMARY KEY, emp_id TEXT NOT NULL, employee_name TEXT NOT NULL, ot_date TEXT NOT NULL, start_time TEXT NOT NULL, end_time TEXT NOT NULL, total_hours REAL NOT NULL DEFAULT 0, rate REAL NOT NULL DEFAULT 0, amount REAL NOT NULL DEFAULT 0, notes TEXT NOT NULL DEFAULT '', created_by TEXT NOT NULL DEFAULT '', created_at TEXT NOT NULL, updated_at TEXT NOT NULL)");
  $d->exec("CREATE INDEX IF NOT EXISTS idx_overtime_entries_emp_date ON overtime_entries(emp_id, ot_date)");
  $d->exec("CREATE TABLE IF NOT EXISTS advance_deductions (id INTEGER PRIMARY KEY AUTOINCREMENT, advance_id TEXT NOT NULL, emp_id TEXT NOT NULL, deduction_year INTEGER NOT NULL, deduction_month INTEGER NOT NULL, scheduled_amount REAL NOT NULL DEFAULT 0, deducted_amount REAL NOT NULL DEFAULT 0, balance_after REAL NOT NULL DEFAULT 0, payroll_period TEXT NOT NULL DEFAULT '', payroll_sheet_id TEXT NOT NULL DEFAULT '', status TEXT NOT NULL DEFAULT 'Scheduled', created_at TEXT NOT NULL, updated_at TEXT NOT NULL, UNIQUE(advance_id, deduction_year, deduction_month))");
  $d->exec("CREATE INDEX IF NOT EXISTS idx_advance_deductions_emp_period ON advance_deductions(emp_id, deduction_year, deduction_month)");
  $d->exec("CREATE TABLE IF NOT EXISTS public_enquiries (id INTEGER PRIMARY KEY AUTOINCREMENT, full_name TEXT NOT NULL DEFAULT '', company_name TEXT NOT NULL DEFAULT '', work_email TEXT NOT NULL DEFAULT '', phone_no TEXT NOT NULL DEFAULT '', team_size TEXT NOT NULL DEFAULT '', product_interest TEXT NOT NULL DEFAULT '', preferred_date TEXT NOT NULL DEFAULT '', preferred_time TEXT NOT NULL DEFAULT '', modules TEXT NOT NULL DEFAULT '[]', message TEXT NOT NULL DEFAULT '', source_page TEXT NOT NULL DEFAULT 'landing', status TEXT NOT NULL DEFAULT 'New', admin_note TEXT NOT NULL DEFAULT '', created_at TEXT NOT NULL, updated_at TEXT NOT NULL)");
  $d->exec("CREATE INDEX IF NOT EXISTS idx_public_enquiries_status_created ON public_enquiries(status, created_at)");
  $d->exec("CREATE TABLE IF NOT EXISTS email_logs (id INTEGER PRIMARY KEY AUTOINCREMENT, module TEXT NOT NULL DEFAULT '', record_id TEXT NOT NULL DEFAULT '', client_id INTEGER NOT NULL DEFAULT 0, recipient TEXT NOT NULL DEFAULT '', subject TEXT NOT NULL DEFAULT '', direction TEXT NOT NULL DEFAULT 'outbound', status TEXT NOT NULL DEFAULT 'pending', error_message TEXT NOT NULL DEFAULT '', created_at TEXT NOT NULL)");
  $d->exec("CREATE INDEX IF NOT EXISTS idx_email_logs_module_record ON email_logs(module, record_id)");
  $d->exec("CREATE INDEX IF NOT EXISTS idx_email_logs_created_at ON email_logs(created_at)");
  $d->exec("CREATE TABLE IF NOT EXISTS attendance_status_master (code TEXT PRIMARY KEY, short_label TEXT NOT NULL DEFAULT '', full_label TEXT NOT NULL DEFAULT '', button_class TEXT NOT NULL DEFAULT '', sort_order INTEGER NOT NULL DEFAULT 0, is_active INTEGER NOT NULL DEFAULT 1, note_required INTEGER NOT NULL DEFAULT 0, is_paid INTEGER NOT NULL DEFAULT 1, created_at TEXT NOT NULL, updated_at TEXT NOT NULL)");
  $attendanceStatusCols = $d->query("PRAGMA table_info(attendance_status_master)")->fetchAll();
  $attendanceStatusColNames = array_map(fn($c)=>(string)($c['name'] ?? ''), $attendanceStatusCols);
  if(!in_array('short_label', $attendanceStatusColNames, true)){ $d->exec("ALTER TABLE attendance_status_master ADD COLUMN short_label TEXT NOT NULL DEFAULT ''"); }
  if(!in_array('full_label', $attendanceStatusColNames, true)){ $d->exec("ALTER TABLE attendance_status_master ADD COLUMN full_label TEXT NOT NULL DEFAULT ''"); }
  if(!in_array('button_class', $attendanceStatusColNames, true)){ $d->exec("ALTER TABLE attendance_status_master ADD COLUMN button_class TEXT NOT NULL DEFAULT ''"); }
  if(!in_array('sort_order', $attendanceStatusColNames, true)){ $d->exec("ALTER TABLE attendance_status_master ADD COLUMN sort_order INTEGER NOT NULL DEFAULT 0"); }
  if(!in_array('is_active', $attendanceStatusColNames, true)){ $d->exec("ALTER TABLE attendance_status_master ADD COLUMN is_active INTEGER NOT NULL DEFAULT 1"); }
  if(!in_array('note_required', $attendanceStatusColNames, true)){ $d->exec("ALTER TABLE attendance_status_master ADD COLUMN note_required INTEGER NOT NULL DEFAULT 0"); }
  if(!in_array('is_paid', $attendanceStatusColNames, true)){ $d->exec("ALTER TABLE attendance_status_master ADD COLUMN is_paid INTEGER NOT NULL DEFAULT 1"); }
  if(!in_array('created_at', $attendanceStatusColNames, true)){ $d->exec("ALTER TABLE attendance_status_master ADD COLUMN created_at TEXT NOT NULL DEFAULT ''"); }
  if(!in_array('updated_at', $attendanceStatusColNames, true)){ $d->exec("ALTER TABLE attendance_status_master ADD COLUMN updated_at TEXT NOT NULL DEFAULT ''"); }
  $d->exec("CREATE TABLE IF NOT EXISTS employee_type_master (code TEXT PRIMARY KEY, label TEXT NOT NULL DEFAULT '', sort_order INTEGER NOT NULL DEFAULT 0, is_active INTEGER NOT NULL DEFAULT 1, created_at TEXT NOT NULL, updated_at TEXT NOT NULL)");
  $employeeTypeCols = $d->query("PRAGMA table_info(employee_type_master)")->fetchAll();
  $employeeTypeColNames = array_map(fn($c)=>(string)($c['name'] ?? ''), $employeeTypeCols);
  if(!in_array('label', $employeeTypeColNames, true)){ $d->exec("ALTER TABLE employee_type_master ADD COLUMN label TEXT NOT NULL DEFAULT ''"); }
  if(!in_array('sort_order', $employeeTypeColNames, true)){ $d->exec("ALTER TABLE employee_type_master ADD COLUMN sort_order INTEGER NOT NULL DEFAULT 0"); }
  if(!in_array('is_active', $employeeTypeColNames, true)){ $d->exec("ALTER TABLE employee_type_master ADD COLUMN is_active INTEGER NOT NULL DEFAULT 1"); }
  if(!in_array('created_at', $employeeTypeColNames, true)){ $d->exec("ALTER TABLE employee_type_master ADD COLUMN created_at TEXT NOT NULL DEFAULT ''"); }
  if(!in_array('updated_at', $employeeTypeColNames, true)){ $d->exec("ALTER TABLE employee_type_master ADD COLUMN updated_at TEXT NOT NULL DEFAULT ''"); }
  init_shift_schema($d);
  access_types_seed($d);
  access_enable_roles_visibility($d);
  attendance_status_seed($d);
  employee_type_seed($d);
  hr_init_normalized_schema($d);
}
function hr_init_normalized_schema(PDO $d): void {
  $d->exec("CREATE TABLE IF NOT EXISTS tenant_settings (key TEXT PRIMARY KEY, value TEXT NOT NULL, updated_at TEXT NOT NULL)");
  $d->exec("CREATE TABLE IF NOT EXISTS attendance_daily (year INTEGER NOT NULL, month INTEGER NOT NULL, records TEXT NOT NULL, PRIMARY KEY (year, month))");
  $d->exec("CREATE TABLE IF NOT EXISTS payroll_overrides (emp_id TEXT PRIMARY KEY, data TEXT NOT NULL)");
  $d->exec("CREATE TABLE IF NOT EXISTS sheet_indexes (sheet_type TEXT PRIMARY KEY, entries TEXT NOT NULL)");
  $d->exec("CREATE TABLE IF NOT EXISTS sheets (sheet_type TEXT NOT NULL, sheet_id TEXT NOT NULL, month INTEGER NOT NULL, year INTEGER NOT NULL, period TEXT NOT NULL, data TEXT NOT NULL, meta TEXT NOT NULL, PRIMARY KEY (sheet_type, sheet_id))");
  $d->exec("CREATE TABLE IF NOT EXISTS challans (challan_type TEXT NOT NULL, challan_id TEXT NOT NULL, data TEXT NOT NULL, PRIMARY KEY (challan_type, challan_id))");
}
function access_type_builtin_rows(): array {
  return [
    ["code"=>"full_access","name"=>"Full Access","permissions"=>["dashboard"=>true,"clientModule"=>true,"employeeMaster"=>true,"employeeType"=>true,"salarySheet"=>true,"payslips"=>true,"compliance"=>true,"attendance"=>true,"attendanceStatus"=>true,"leaveManagement"=>true,"fnf"=>true,"gratuity"=>true,"bonus"=>true,"incentive"=>true,"loan"=>true,"pfSheet"=>true,"pfReturn"=>true,"esicSheet"=>true,"esicReturn"=>true,"ecrSheet"=>true,"controlPage"=>true,"companyProfile"=>true,"accessControl"=>false,"shiftRoster"=>true,"advanceSalary"=>true]],
    ["code"=>"payroll_ops","name"=>"Payroll Operations","permissions"=>["dashboard"=>true,"clientModule"=>false,"employeeMaster"=>true,"employeeType"=>false,"salarySheet"=>true,"payslips"=>true,"compliance"=>false,"attendance"=>true,"attendanceStatus"=>false,"leaveManagement"=>true,"fnf"=>true,"gratuity"=>true,"bonus"=>true,"incentive"=>true,"loan"=>true,"pfSheet"=>true,"pfReturn"=>false,"esicSheet"=>false,"esicReturn"=>false,"ecrSheet"=>true,"controlPage"=>false,"companyProfile"=>true,"accessControl"=>false,"shiftRoster"=>true,"advanceSalary"=>true]],
    ["code"=>"compliance_ops","name"=>"Compliance Operations","permissions"=>["dashboard"=>true,"clientModule"=>false,"employeeMaster"=>false,"employeeType"=>false,"salarySheet"=>false,"payslips"=>false,"compliance"=>true,"attendance"=>false,"attendanceStatus"=>false,"leaveManagement"=>false,"fnf"=>false,"gratuity"=>false,"bonus"=>false,"incentive"=>false,"loan"=>false,"pfSheet"=>true,"pfReturn"=>true,"esicSheet"=>true,"esicReturn"=>true,"ecrSheet"=>true,"controlPage"=>false,"companyProfile"=>true,"accessControl"=>false,"shiftRoster"=>false,"advanceSalary"=>false]],
    ["code"=>"read_only","name"=>"Read Only","permissions"=>["dashboard"=>true,"clientModule"=>false,"employeeMaster"=>false,"employeeType"=>false,"salarySheet"=>false,"payslips"=>false,"compliance"=>false,"attendance"=>false,"attendanceStatus"=>false,"leaveManagement"=>false,"fnf"=>false,"gratuity"=>false,"bonus"=>false,"incentive"=>false,"loan"=>false,"pfSheet"=>false,"pfReturn"=>false,"esicSheet"=>false,"esicReturn"=>false,"ecrSheet"=>false,"controlPage"=>false,"companyProfile"=>true,"accessControl"=>false,"shiftRoster"=>false,"advanceSalary"=>true]],
  ];
}
function access_types_seed(PDO $d): void {
  $ts = now_iso();
  $st = $d->prepare("INSERT OR IGNORE INTO access_types (code,name,permissions,is_system,created_at,updated_at) VALUES (?,?,?,?,?,?)");
  foreach(access_type_builtin_rows() as $r){
    $st->execute([(string)$r['code'],(string)$r['name'],json_encode(access_norm_permissions($r['permissions'] ?? []), JSON_UNESCAPED_UNICODE),1,$ts,$ts]);
  }
}
function access_enable_roles_visibility(PDO $d): void {
  $ts = now_iso();
  $defaults = access_default_permissions();
  $rows = $d->query("SELECT code, permissions FROM access_types")->fetchAll();
  $upType = $d->prepare("UPDATE access_types SET permissions=?, updated_at=? WHERE code=?");
  foreach($rows as $r){
    $perm = json_decode((string)($r['permissions'] ?? '[]'), true);
    $src = is_array($perm) ? $perm : [];
    $changed = false;
    foreach($defaults as $k => $v){
      if(!array_key_exists($k, $src)){
        $src[$k] = $v;
        $changed = true;
      }
    }
    if(!$changed) continue;
    $norm = access_norm_permissions($src);
    $upType->execute([json_encode($norm, JSON_UNESCAPED_UNICODE), $ts, (string)$r['code']]);
  }
  $rows2 = $d->query("SELECT client_id, permissions FROM client_access")->fetchAll();
  $upClient = $d->prepare("UPDATE client_access SET permissions=?, updated_at=? WHERE client_id=?");
  foreach($rows2 as $r){
    $perm = json_decode((string)($r['permissions'] ?? '[]'), true);
    $src = is_array($perm) ? $perm : [];
    $changed = false;
    foreach($defaults as $k => $v){
      if(!array_key_exists($k, $src)){
        $src[$k] = $v;
        $changed = true;
      }
    }
    if(!$changed) continue;
    $norm = access_norm_permissions($src);
    $upClient->execute([json_encode($norm, JSON_UNESCAPED_UNICODE), $ts, (int)$r['client_id']]);
  }
}
function attendance_status_builtin_rows(): array {
  return [
    ["code"=>"P","shortLabel"=>"P","fullLabel"=>"Present","buttonClass"=>"btn-outline-success","sortOrder"=>10,"isActive"=>1,"noteRequired"=>0,"isPaid"=>1],
    ["code"=>"A","shortLabel"=>"A","fullLabel"=>"Absent","buttonClass"=>"btn-outline-danger","sortOrder"=>15,"isActive"=>1,"noteRequired"=>0,"isPaid"=>0],
    ["code"=>"WO","shortLabel"=>"WO","fullLabel"=>"Weekly Off","buttonClass"=>"btn-outline-secondary","sortOrder"=>20,"isActive"=>1,"noteRequired"=>0,"isPaid"=>1],
    ["code"=>"CL","shortLabel"=>"CL","fullLabel"=>"Casual","buttonClass"=>"btn-outline-primary","sortOrder"=>30,"isActive"=>1,"noteRequired"=>1,"isPaid"=>1],
    ["code"=>"SL","shortLabel"=>"SL","fullLabel"=>"Sick","buttonClass"=>"btn-outline-info","sortOrder"=>40,"isActive"=>1,"noteRequired"=>1,"isPaid"=>1],
    ["code"=>"EL","shortLabel"=>"EL","fullLabel"=>"Earned","buttonClass"=>"btn-outline-dark","sortOrder"=>50,"isActive"=>1,"noteRequired"=>1,"isPaid"=>1],
    ["code"=>"LOP","shortLabel"=>"LOP","fullLabel"=>"Loss of Pay","buttonClass"=>"btn-outline-warning","sortOrder"=>60,"isActive"=>1,"noteRequired"=>1,"isPaid"=>0],
  ];
}
function attendance_status_seed(PDO $d): void {
  $ts = now_iso();
  $st = $d->prepare("INSERT OR IGNORE INTO attendance_status_master (code,short_label,full_label,button_class,sort_order,is_active,note_required,is_paid,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?)");
  foreach(attendance_status_builtin_rows() as $r){
    $st->execute([(string)$r['code'],(string)$r['shortLabel'],(string)$r['fullLabel'],(string)$r['buttonClass'],(int)$r['sortOrder'],(int)$r['isActive'],(int)$r['noteRequired'],(int)$r['isPaid'],$ts,$ts]);
  }
}
function employee_type_builtin_rows(): array {
  return [
    ["code"=>"FULL_TIME","label"=>"Full-time","sortOrder"=>10,"isActive"=>1],
    ["code"=>"PART_TIME","label"=>"Part-time","sortOrder"=>20,"isActive"=>1],
    ["code"=>"CONTRACT","label"=>"Contract","sortOrder"=>30,"isActive"=>1],
    ["code"=>"INTERN","label"=>"Intern","sortOrder"=>40,"isActive"=>1],
    ["code"=>"CONSULTANT","label"=>"Consultant","sortOrder"=>50,"isActive"=>1],
    ["code"=>"TEMPORARY","label"=>"Temporary","sortOrder"=>60,"isActive"=>1],
  ];
}
function employee_type_seed(PDO $d): void {
  $ts = now_iso();
  $st = $d->prepare("INSERT OR IGNORE INTO employee_type_master (code,label,sort_order,is_active,created_at,updated_at) VALUES (?,?,?,?,?,?)");
  foreach(employee_type_builtin_rows() as $r){
    $st->execute([(string)$r['code'],(string)$r['label'],(int)$r['sortOrder'],(int)$r['isActive'],$ts,$ts]);
  }
  $empRows = $d->query("SELECT DISTINCT type FROM employees WHERE TRIM(type)<>''")->fetchAll(PDO::FETCH_COLUMN);
  foreach($empRows as $label){
    $name = s((string)$label);
    if($name === '') continue;
    $code = up(preg_replace('/[^A-Z0-9]+/', '_', strtoupper($name)) ?? '');
    $code = trim($code, '_');
    if($code === '') $code = 'TYPE_' . substr(md5($name), 0, 8);
    $st->execute([$code,$name,100,1,$ts,$ts]);
  }
}
function attendance_status_norm(array $raw): array {
  $code = up($raw['code'] ?? '');
  if($code === '') bad('Code is required');
  if(!preg_match('/^[A-Z][A-Z0-9_\\-]{0,11}$/', $code)) bad('Code must start with a letter and use only A-Z, 0-9, dash or underscore (max 12 chars)');
  $short = strtoupper(trim((string)($raw['shortLabel'] ?? $raw['short_label'] ?? $code)));
  $full = trim((string)($raw['fullLabel'] ?? $raw['full_label'] ?? ''));
  if($short === '') $short = $code;
  if($full === '') bad('Full label is required');
  $button = trim((string)($raw['buttonClass'] ?? $raw['button_class'] ?? 'btn-outline-secondary'));
  if($button === '') $button = 'btn-outline-secondary';
  $sort = (int)($raw['sortOrder'] ?? $raw['sort_order'] ?? 0);
  $active = !array_key_exists('isActive', $raw) && !array_key_exists('is_active', $raw) ? 1 : (b($raw['isActive'] ?? $raw['is_active'] ?? true) ? 1 : 0);
  $note = !array_key_exists('noteRequired', $raw) && !array_key_exists('note_required', $raw) ? 0 : (b($raw['noteRequired'] ?? $raw['note_required'] ?? false) ? 1 : 0);
  $paid = !array_key_exists('isPaid', $raw) && !array_key_exists('is_paid', $raw) ? 1 : (b($raw['isPaid'] ?? $raw['is_paid'] ?? true) ? 1 : 0);
  return ['code'=>$code,'shortLabel'=>$short,'fullLabel'=>$full,'buttonClass'=>$button,'sortOrder'=>$sort,'isActive'=>$active,'noteRequired'=>$note,'isPaid'=>$paid];
}
function employee_type_norm(array $raw): array {
  $code = up(preg_replace('/[^A-Z0-9_-]+/', '_', (string)($raw['code'] ?? '')) ?? '');
  $code = trim($code, '_');
  $label = s($raw['label'] ?? '');
  if($code === '') bad('Employee type code is required');
  if($label === '') bad('Employee type label is required');
  if(strlen($code) > 24) bad('Employee type code must be 24 characters or fewer');
  return [
    'code' => $code,
    'label' => $label,
    'sortOrder' => max(0, (int)($raw['sortOrder'] ?? 0)),
    'isActive' => b($raw['isActive'] ?? true),
  ];
}
function employee_type_row_payload(array $r): array {
  return [
    'code' => (string)($r['code'] ?? ''),
    'label' => (string)($r['label'] ?? ''),
    'sortOrder' => (int)($r['sort_order'] ?? 0),
    'isActive' => ((int)($r['is_active'] ?? 1)) === 1,
    '__updatedAt' => (string)($r['updated_at'] ?? ''),
  ];
}
function employee_type_rows(bool $activeOnly = false): array {
  $sql = "SELECT code,label,sort_order,is_active,updated_at FROM employee_type_master";
  $args = [];
  if($activeOnly){
    $sql .= " WHERE is_active=1";
  }
  $sql .= " ORDER BY sort_order ASC, label ASC, code ASC";
  $st = db()->prepare($sql);
  $st->execute($args);
  return array_map('employee_type_row_payload', $st->fetchAll());
}
function employee_type_upsert(array $payload, bool $isUpdate): array {
  $n = employee_type_norm($payload);
  $d = db();
  $ts = now_iso();
  if($isUpdate){
    $q = $d->prepare("SELECT code, created_at FROM employee_type_master WHERE code=? LIMIT 1");
    $q->execute([$n['code']]);
    $existing = $q->fetch();
    if(!$existing) nf('Employee type not found');
    $createdAt = (string)($existing['created_at'] ?? $ts);
    $st = $d->prepare("UPDATE employee_type_master SET label=?, sort_order=?, is_active=?, updated_at=? WHERE code=?");
    $st->execute([$n['label'], $n['sortOrder'], $n['isActive'] ? 1 : 0, $ts, $n['code']]);
  } else {
    $createdAt = $ts;
    $st = $d->prepare("INSERT INTO employee_type_master (code,label,sort_order,is_active,created_at,updated_at) VALUES (?,?,?,?,?,?)");
    $st->execute([$n['code'], $n['label'], $n['sortOrder'], $n['isActive'] ? 1 : 0, $createdAt, $ts]);
  }
  $q = $d->prepare("SELECT code,label,sort_order,is_active,updated_at FROM employee_type_master WHERE code=? LIMIT 1");
  $q->execute([$n['code']]);
  $row = $q->fetch();
  return $row ? employee_type_row_payload($row) : ["code"=>$n['code'],"label"=>$n['label'],"sortOrder"=>$n['sortOrder'],"isActive"=>$n['isActive'],"__updatedAt"=>$ts];
}
function employee_type_delete(string $code): void {
  $code = up($code);
  if($code === '') bad('Employee type code is required');
  $inUse = db()->prepare("SELECT COUNT(*) FROM employees WHERE type = (SELECT label FROM employee_type_master WHERE code=? LIMIT 1)");
  $inUse->execute([$code]);
  if((int)$inUse->fetchColumn() > 0){
    j(['detail'=>'Employee type is in use by existing employees'], 409);
  }
  $st = db()->prepare("DELETE FROM employee_type_master WHERE code=?");
  $st->execute([$code]);
}
function attendance_status_row_payload(array $r): array {
  return [
    'code'=>(string)($r['code'] ?? ''),
    'shortLabel'=>(string)($r['short_label'] ?? ''),
    'fullLabel'=>(string)($r['full_label'] ?? ''),
    'buttonClass'=>(string)($r['button_class'] ?? 'btn-outline-secondary'),
    'sortOrder'=>(int)($r['sort_order'] ?? 0),
    'isActive'=>((int)($r['is_active'] ?? 0)) === 1,
    'noteRequired'=>((int)($r['note_required'] ?? 0)) === 1,
    'isPaid'=>((int)($r['is_paid'] ?? 0)) === 1
  ];
}
function attendance_status_rows(bool $activeOnly=false): array {
  $q = central_db()->query("SELECT code, short_label, full_label, button_class, sort_order, is_active, note_required, is_paid FROM attendance_status_master ORDER BY sort_order ASC, code ASC");
  $rows = array_map('attendance_status_row_payload', $q ? $q->fetchAll() : []);
  if($activeOnly) $rows = array_values(array_filter($rows, fn($r)=>!empty($r['isActive'])));
  return $rows;
}
function attendance_status_upsert(array $raw, bool $isUpdate=false): array {
  $n = attendance_status_norm($raw);
  $ts = now_iso();
  $d = central_db();
  if($isUpdate){
    $q = $d->prepare("UPDATE attendance_status_master SET short_label=?, full_label=?, button_class=?, sort_order=?, is_active=?, note_required=?, is_paid=?, updated_at=? WHERE code=?");
    $q->execute([$n['shortLabel'],$n['fullLabel'],$n['buttonClass'],$n['sortOrder'],$n['isActive'],$n['noteRequired'],$n['isPaid'],$ts,$n['code']]);
  } else {
    $q = $d->prepare("INSERT INTO attendance_status_master (code,short_label,full_label,button_class,sort_order,is_active,note_required,is_paid,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?)");
    $q->execute([$n['code'],$n['shortLabel'],$n['fullLabel'],$n['buttonClass'],$n['sortOrder'],$n['isActive'],$n['noteRequired'],$n['isPaid'],$ts,$ts]);
  }
  $s = $d->prepare("SELECT code, short_label, full_label, button_class, sort_order, is_active, note_required, is_paid FROM attendance_status_master WHERE code=? LIMIT 1");
  $s->execute([$n['code']]);
  return attendance_status_row_payload($s->fetch() ?: []);
}
function attendance_status_delete(string $code): void {
  $code = up($code);
  if($code === '') bad('Code is required');
  $q = central_db()->prepare("DELETE FROM attendance_status_master WHERE code=?");
  $q->execute([$code]);
}
function init_db(): void {
  init_schema(db());
  init_schema(central_db());
  face_attendance_settings_seed(db());
  face_attendance_settings_seed(central_db());
}
function face_attendance_default_settings(): array {
  return [
    'inAllowedFrom' => '08:00',
    'inAllowedTill' => '11:00',
    'lateMarkAfter' => '09:15',
    'outAllowedFrom' => '17:00',
    'outAllowedTill' => '23:00',
    'graceTime' => 10,
    'faceMatchThreshold' => 0.48,
    'timezone' => FACE_ATTENDANCE_DEFAULT_TIMEZONE,
    'modelUrl' => FACE_ATTENDANCE_DEFAULT_MODEL_URL,
    'autoCaptureSeconds' => 2,
    'scanDistanceCm' => 45,
    '__updatedAt' => now_iso()
  ];
}
function face_attendance_settings_seed(PDO $d): void {
  $defs = face_attendance_default_settings();
  $st = $d->prepare("INSERT OR IGNORE INTO attendance_settings (id, in_allowed_from, in_allowed_till, late_mark_after, out_allowed_from, out_allowed_till, grace_time, face_match_threshold, timezone, model_url, auto_capture_seconds, scan_distance_cm, updated_at) VALUES (1,?,?,?,?,?,?,?,?,?,?,?,?)");
  $st->execute([
    $defs['inAllowedFrom'], $defs['inAllowedTill'], $defs['lateMarkAfter'], $defs['outAllowedFrom'], $defs['outAllowedTill'],
    (int)$defs['graceTime'], f($defs['faceMatchThreshold']), (string)$defs['timezone'], (string)$defs['modelUrl'],
    (int)$defs['autoCaptureSeconds'], (int)$defs['scanDistanceCm'], (string)$defs['__updatedAt']
  ]);
}
function face_attendance_view_ctx(): array {
  $ctx = auth_ctx(true);
  $role = strtolower((string)($ctx['role'] ?? ''));
  if(!in_array($role, ['super_admin','client','client_admin','agency_admin','employee'], true)) j(['detail'=>'Forbidden'],403);
  return $ctx;
}
function face_attendance_manage_ctx(): array {
  $ctx = face_attendance_view_ctx();
  if(strtolower((string)($ctx['role'] ?? '')) === 'employee') j(['detail'=>'Only admin/HR can manage face attendance settings'],403);
  return $ctx;
}
function face_attendance_emp_scope(array $ctx): string {
  return strtolower((string)($ctx['role'] ?? '')) === 'employee' ? up($ctx['empId'] ?? '') : '';
}
function face_attendance_settings_get(): array {
  face_attendance_settings_seed(db());
  $r = db()->query("SELECT * FROM attendance_settings WHERE id=1 LIMIT 1")->fetch() ?: [];
  $defs = face_attendance_default_settings();
  return [
    'inAllowedFrom' => s($r['in_allowed_from'] ?? $defs['inAllowedFrom'], $defs['inAllowedFrom']),
    'inAllowedTill' => s($r['in_allowed_till'] ?? $defs['inAllowedTill'], $defs['inAllowedTill']),
    'lateMarkAfter' => s($r['late_mark_after'] ?? $defs['lateMarkAfter'], $defs['lateMarkAfter']),
    'outAllowedFrom' => s($r['out_allowed_from'] ?? $defs['outAllowedFrom'], $defs['outAllowedFrom']),
    'outAllowedTill' => s($r['out_allowed_till'] ?? $defs['outAllowedTill'], $defs['outAllowedTill']),
    'graceTime' => max(0, (int)($r['grace_time'] ?? $defs['graceTime'])),
    'faceMatchThreshold' => max(0.1, min(1.5, f($r['face_match_threshold'] ?? $defs['faceMatchThreshold']))),
    'timezone' => s($r['timezone'] ?? $defs['timezone'], $defs['timezone']),
    'modelUrl' => s($r['model_url'] ?? $defs['modelUrl'], $defs['modelUrl']),
    'autoCaptureSeconds' => max(1, (int)($r['auto_capture_seconds'] ?? $defs['autoCaptureSeconds'])),
    'scanDistanceCm' => max(20, min(150, (int)($r['scan_distance_cm'] ?? $defs['scanDistanceCm']))),
    '__updatedAt' => s($r['updated_at'] ?? $defs['__updatedAt'], $defs['__updatedAt'])
  ];
}
function face_attendance_settings_put(array $raw): array {
  $cur = face_attendance_settings_get();
  $row = [
    'inAllowedFrom' => s($raw['inAllowedFrom'] ?? $cur['inAllowedFrom'], $cur['inAllowedFrom']),
    'inAllowedTill' => s($raw['inAllowedTill'] ?? $cur['inAllowedTill'], $cur['inAllowedTill']),
    'lateMarkAfter' => s($raw['lateMarkAfter'] ?? $cur['lateMarkAfter'], $cur['lateMarkAfter']),
    'outAllowedFrom' => s($raw['outAllowedFrom'] ?? $cur['outAllowedFrom'], $cur['outAllowedFrom']),
    'outAllowedTill' => s($raw['outAllowedTill'] ?? $cur['outAllowedTill'], $cur['outAllowedTill']),
    'graceTime' => max(0, (int)($raw['graceTime'] ?? $cur['graceTime'])),
    'faceMatchThreshold' => max(0.1, min(1.5, f($raw['faceMatchThreshold'] ?? $cur['faceMatchThreshold']))),
    'timezone' => s($raw['timezone'] ?? $cur['timezone'], FACE_ATTENDANCE_DEFAULT_TIMEZONE),
    'modelUrl' => s($raw['modelUrl'] ?? $cur['modelUrl'], FACE_ATTENDANCE_DEFAULT_MODEL_URL),
    'autoCaptureSeconds' => max(1, (int)($raw['autoCaptureSeconds'] ?? $cur['autoCaptureSeconds'])),
    'scanDistanceCm' => max(20, min(150, (int)($raw['scanDistanceCm'] ?? $cur['scanDistanceCm']))),
    '__updatedAt' => now_iso()
  ];
  $st = db()->prepare("INSERT INTO attendance_settings (id, in_allowed_from, in_allowed_till, late_mark_after, out_allowed_from, out_allowed_till, grace_time, face_match_threshold, timezone, model_url, auto_capture_seconds, scan_distance_cm, updated_at) VALUES (1,?,?,?,?,?,?,?,?,?,?,?,?) ON CONFLICT(id) DO UPDATE SET in_allowed_from=excluded.in_allowed_from, in_allowed_till=excluded.in_allowed_till, late_mark_after=excluded.late_mark_after, out_allowed_from=excluded.out_allowed_from, out_allowed_till=excluded.out_allowed_till, grace_time=excluded.grace_time, face_match_threshold=excluded.face_match_threshold, timezone=excluded.timezone, model_url=excluded.model_url, auto_capture_seconds=excluded.auto_capture_seconds, scan_distance_cm=excluded.scan_distance_cm, updated_at=excluded.updated_at");
  $st->execute([
    $row['inAllowedFrom'], $row['inAllowedTill'], $row['lateMarkAfter'], $row['outAllowedFrom'], $row['outAllowedTill'],
    $row['graceTime'], $row['faceMatchThreshold'], $row['timezone'], $row['modelUrl'], $row['autoCaptureSeconds'], $row['scanDistanceCm'], $row['__updatedAt']
  ]);
  return $row;
}
function face_attendance_tz(array $settings): DateTimeZone {
  try { return new DateTimeZone((string)($settings['timezone'] ?? FACE_ATTENDANCE_DEFAULT_TIMEZONE)); } catch(Throwable $e) { return new DateTimeZone(FACE_ATTENDANCE_DEFAULT_TIMEZONE); }
}
function face_attendance_now(array $settings): DateTimeImmutable {
  return new DateTimeImmutable('now', face_attendance_tz($settings));
}
function face_attendance_time_to_minutes(string $time): int {
  if(!preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $time, $m)) bad('Time must be in HH:MM format');
  return ((int)$m[1] * 60) + (int)$m[2];
}
function face_attendance_descriptor_value($raw): array {
  $src = $raw;
  if(is_string($src)){
    $src = json_decode($src, true);
  }
  if(!is_array($src)) return [];
  $out = [];
  foreach($src as $v){
    if(!is_numeric($v)) continue;
    $out[] = round((float)$v, 8);
  }
  return $out;
}
function face_attendance_descriptor_distance(array $a, array $b): float {
  $n = min(count($a), count($b));
  if($n === 0) return 999.0;
  $sum = 0.0;
  for($i=0; $i<$n; $i++){
    $diff = (float)$a[$i] - (float)$b[$i];
    $sum += $diff * $diff;
  }
  return sqrt($sum);
}
function face_attendance_employee(array $ctx, array $payload = [], bool $allowAdminPick = true): array {
  $scope = face_attendance_emp_scope($ctx);
  $empId = $scope !== '' ? $scope : up(($allowAdminPick ? ($payload['employeeId'] ?? '') : ''));
  if($empId === '') bad('employeeId is required');
  $emp = employee_lookup($empId);
  if(!$emp) nf('Employee not found');
  if(strtolower((string)($emp['status'] ?? 'active')) === 'inactive') bad('Employee is inactive');
  return $emp;
}
function face_attendance_registration_payload(array $r): array {
  $emp = employee_lookup((string)($r['employee_id'] ?? ''));
  return [
    'id' => (int)($r['id'] ?? 0),
    'employeeId' => (string)($r['employee_id'] ?? ''),
    'employeeName' => (string)($emp['name'] ?? ($r['employee_id'] ?? '')),
    'department' => (string)($emp['dept'] ?? ''),
    'designation' => (string)($emp['desig'] ?? ''),
    'faceImage' => (string)($r['face_image'] ?? ''),
    '__updatedAt' => s($r['updated_at'] ?? $r['created_at'] ?? '', '')
  ];
}
function face_attendance_registration_rows(?string $employeeId = null): array {
  $sql = "SELECT id, employee_id, face_image, created_at, updated_at FROM employee_faces";
  $params = [];
  if($employeeId !== null && $employeeId !== ''){
    $sql .= " WHERE employee_id=?";
    $params[] = up($employeeId);
  }
  $sql .= " ORDER BY employee_id ASC";
  $st = db()->prepare($sql);
  $st->execute($params);
  return array_map('face_attendance_registration_payload', $st->fetchAll() ?: []);
}
function face_attendance_registered_match(array $scanDescriptor): ?array {
  $threshold = f(face_attendance_settings_get()['faceMatchThreshold'] ?? 0.48);
  $rows = db()->query("SELECT * FROM employee_faces ORDER BY employee_id ASC")->fetchAll() ?: [];
  $best = null;
  foreach($rows as $row){
    $employeeId = up($row['employee_id'] ?? '');
    if($employeeId === '') continue;
    $emp = employee_lookup($employeeId);
    if(!$emp) continue;
    if(strtolower((string)($emp['status'] ?? 'active')) === 'inactive') continue;
    $stored = face_attendance_descriptor_value($row['face_descriptor'] ?? '[]');
    if(count($stored) < 32) continue;
    $distance = face_attendance_descriptor_distance($scanDescriptor, $stored);
    if($best === null || $distance < f($best['score'] ?? 999.0)){
      $best = [
        'employee' => $emp,
        'row' => $row,
        'score' => $distance,
        'threshold' => $threshold
      ];
    }
  }
  if(!$best) return null;
  if(f($best['score'] ?? 999.0) > $threshold) return null;
  return $best;
}
function face_attendance_register(array $payload): array {
  $ctx = face_attendance_manage_ctx();
  $emp = face_attendance_employee($ctx, $payload, true);
  $descriptor = face_attendance_descriptor_value($payload['faceDescriptor'] ?? $payload['descriptor'] ?? []);
  if(count($descriptor) < 32) bad('Valid face descriptor is required');
  $image = s($payload['faceImage'] ?? '', '');
  $now = now_iso();
  $empId = up($emp['id'] ?? '');
  db()->prepare("DELETE FROM employee_faces WHERE employee_id=?")->execute([$empId]);
  $st = db()->prepare("INSERT INTO employee_faces (employee_id, face_descriptor, face_image, created_at, updated_at) VALUES (?,?,?,?,?)");
  $st->execute([$empId, json_encode($descriptor, JSON_UNESCAPED_UNICODE), $image, $now, $now]);
  return face_attendance_registration_rows($empId)[0] ?? ['employeeId'=>$empId,'employeeName'=>(string)($emp['name'] ?? $empId),'faceImage'=>$image,'__updatedAt'=>$now];
}
function face_attendance_delete_registration(string $employeeId): void {
  face_attendance_manage_ctx();
  $empId = up($employeeId);
  if($empId === '') bad('employeeId is required');
  $st = db()->prepare("DELETE FROM employee_faces WHERE employee_id=?");
  $st->execute([$empId]);
  if($st->rowCount() === 0) nf('Face registration not found');
}
function face_attendance_log(string $employeeId, string $date, string $actionType, float $score, float $threshold, bool $verified, string $message, array $payload = []): void {
  $st = db()->prepare("INSERT INTO attendance_logs (employee_id, attendance_date, action_type, scan_time, verification_score, match_threshold, is_verified, message, payload_json, created_at) VALUES (?,?,?,?,?,?,?,?,?,?)");
  $ts = now_iso();
  $st->execute([up($employeeId), $date, $actionType, $ts, round($score, 6), round($threshold, 6), $verified ? 1 : 0, $message, json_encode($payload, JSON_UNESCAPED_UNICODE), $ts]);
}
function face_attendance_row_payload(array $r): array {
  $emp = employee_lookup((string)($r['employee_id'] ?? ''));
  $status = s($r['attendance_status'] ?? '', '');
  if($status === '' && s($r['in_time'] ?? '', '') !== '' && s($r['out_time'] ?? '', '') === '') $status = 'Missing OUT';
  return [
    'id' => (int)($r['id'] ?? 0),
    'employeeId' => (string)($r['employee_id'] ?? ''),
    'employeeName' => (string)($emp['name'] ?? ($r['employee_id'] ?? '')),
    'department' => (string)($emp['dept'] ?? ''),
    'designation' => (string)($emp['desig'] ?? ''),
    'attendanceDate' => (string)($r['attendance_date'] ?? ''),
    'inTime' => (string)($r['in_time'] ?? ''),
    'outTime' => (string)($r['out_time'] ?? ''),
    'totalWorkingHours' => round(f($r['total_working_hours'] ?? 0), 2),
    'attendanceStatus' => $status,
    'inStatus' => (string)($r['in_status'] ?? ''),
    'outStatus' => (string)($r['out_status'] ?? ''),
    'remarks' => (string)($r['remarks'] ?? ''),
    'source' => (string)($r['source'] ?? 'face'),
    '__updatedAt' => s($r['updated_at'] ?? '', '')
  ];
}
function face_attendance_log_time_local(string $isoTs, array $settings): string {
  $raw = trim($isoTs);
  if($raw === '') return '';
  try {
    $dt = new DateTimeImmutable($raw, new DateTimeZone('UTC'));
    return $dt->setTimezone(face_attendance_tz($settings))->format('H:i:s');
  } catch (Throwable $e) {
    return '';
  }
}
function face_attendance_rebuild_from_logs(): void {
  $settings = face_attendance_settings_get();
  $logs = db()->query("SELECT employee_id, attendance_date, action_type, scan_time, is_verified FROM attendance_logs WHERE is_verified=1 AND action_type IN ('IN','OUT') ORDER BY id ASC")->fetchAll() ?: [];
  if(!$logs) return;
  $grouped = [];
  foreach($logs as $log){
    $empId = up($log['employee_id'] ?? '');
    $date = s($log['attendance_date'] ?? '', '');
    if($empId === '' || $date === '') continue;
    $key = $empId . '|' . $date;
    if(!isset($grouped[$key])){
      $grouped[$key] = ['employee_id'=>$empId, 'attendance_date'=>$date, 'in_time'=>'', 'out_time'=>''];
    }
    $localTime = face_attendance_log_time_local((string)($log['scan_time'] ?? ''), $settings);
    $action = strtoupper((string)($log['action_type'] ?? ''));
    if($action === 'IN' && $grouped[$key]['in_time'] === '') $grouped[$key]['in_time'] = $localTime;
    if($action === 'OUT') $grouped[$key]['out_time'] = $localTime;
  }
  if(!$grouped) return;
  $inFrom = face_attendance_time_to_minutes((string)$settings['inAllowedFrom']);
  $lateAfter = face_attendance_time_to_minutes((string)$settings['lateMarkAfter']) + max(0, (int)$settings['graceTime']);
  $outFrom = face_attendance_time_to_minutes((string)$settings['outAllowedFrom']);
  $st = db()->prepare("INSERT OR IGNORE INTO attendance (employee_id, attendance_date, in_time, out_time, total_working_hours, attendance_status, in_status, out_status, remarks, source, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
  foreach($grouped as $row){
    $inTime = s($row['in_time'] ?? '', '');
    $outTime = s($row['out_time'] ?? '', '');
    if($inTime === '') continue;
    $inStatus = '';
    if(preg_match('/^\d{2}:\d{2}:\d{2}$/', $inTime)){
      $mins = ((int)substr($inTime,0,2) * 60) + (int)substr($inTime,3,2);
      $inStatus = $mins > $lateAfter ? 'Late' : ($mins < $inFrom ? 'Before Time' : 'On Time');
    }
    $outStatus = '';
    if($outTime !== '' && preg_match('/^\d{2}:\d{2}:\d{2}$/', $outTime)){
      $mins = ((int)substr($outTime,0,2) * 60) + (int)substr($outTime,3,2);
      $outStatus = $mins < $outFrom ? 'Early Out' : 'On Time';
    }
    $totalHours = face_attendance_total_hours((string)$row['attendance_date'], $inTime, $outTime);
    $status = $outTime === '' ? 'Missing OUT' : face_attendance_mark_status($inStatus, $outStatus, $outTime);
    $remarks = $outTime === '' ? 'Recovered from face attendance logs (OUT missing)' : 'Recovered from face attendance logs';
    $ts = now_iso();
    $st->execute([(string)$row['employee_id'], (string)$row['attendance_date'], $inTime, $outTime, $totalHours, $status, $inStatus, $outStatus, $remarks, 'face', $ts, $ts]);
  }
}
function face_attendance_sheet_rows(array $query, array $ctx): array {
  face_attendance_rebuild_from_logs();
  $params = [];
  $where = [];
  $scope = face_attendance_emp_scope($ctx);
  $employeeId = $scope !== '' ? $scope : up($query['employeeId'] ?? '');
  if($employeeId !== ''){
    $where[] = "employee_id=?";
    $params[] = $employeeId;
  }
  $date = s($query['date'] ?? '', '');
  if($date !== ''){
    $where[] = "attendance_date=?";
    $params[] = $date;
  } else {
    $month = (int)($query['month'] ?? 0);
    $year = (int)($query['year'] ?? 0);
    if($month >= 1 && $month <= 12 && $year >= 2000){
      $where[] = "CAST(strftime('%m', attendance_date) AS INTEGER)=?";
      $where[] = "CAST(strftime('%Y', attendance_date) AS INTEGER)=?";
      $params[] = $month;
      $params[] = $year;
    }
  }
  $sql = "SELECT * FROM attendance";
  if($where) $sql .= " WHERE " . implode(' AND ', $where);
  $sql .= " ORDER BY attendance_date DESC, employee_id ASC";
  $st = db()->prepare($sql);
  $st->execute($params);
  $rows = array_map('face_attendance_row_payload', $st->fetchAll() ?: []);
  return array_values(array_map(function(array $row){
    if($row['outTime'] === '' && $row['attendanceDate'] < gmdate('Y-m-d') && strtoupper($row['attendanceStatus']) === 'PRESENT'){
      $row['attendanceStatus'] = 'Missing OUT';
    }
    return $row;
  }, $rows));
}
function face_attendance_fetch_one(int $id): ?array {
  if($id <= 0) return null;
  $st = db()->prepare("SELECT * FROM attendance WHERE id=? LIMIT 1");
  $st->execute([$id]);
  $row = $st->fetch();
  return $row ? face_attendance_row_payload($row) : null;
}
function face_attendance_total_hours(string $date, string $inTime, string $outTime): float {
  if($inTime === '' || $outTime === '') return 0.0;
  $inTs = strtotime($date.' '.$inTime);
  $outTs = strtotime($date.' '.$outTime);
  if($inTs === false || $outTs === false || $outTs < $inTs) return 0.0;
  return round(($outTs - $inTs) / 3600, 2);
}
function face_attendance_update_record(int $id, array $payload): array {
  face_attendance_manage_ctx();
  $st = db()->prepare("SELECT * FROM attendance WHERE id=? LIMIT 1");
  $st->execute([$id]);
  $existing = $st->fetch();
  if(!$existing) nf('Attendance record not found');
  $attendanceDate = s($payload['attendanceDate'] ?? $existing['attendance_date'] ?? '', s($existing['attendance_date'] ?? '', ''));
  $inTime = s($payload['inTime'] ?? $existing['in_time'] ?? '', s($existing['in_time'] ?? '', ''));
  $outTime = s($payload['outTime'] ?? $existing['out_time'] ?? '', s($existing['out_time'] ?? '', ''));
  foreach([['In time', $inTime], ['Out time', $outTime]] as $item){
    [$label, $value] = $item;
    if($value !== '' && !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value)) bad($label.' must be HH:MM or HH:MM:SS');
  }
  if($inTime !== '' && preg_match('/^\d{2}:\d{2}$/', $inTime)) $inTime .= ':00';
  if($outTime !== '' && preg_match('/^\d{2}:\d{2}$/', $outTime)) $outTime .= ':00';
  $inStatus = s($payload['inStatus'] ?? $existing['in_status'] ?? '', s($existing['in_status'] ?? '', ''));
  $outStatus = s($payload['outStatus'] ?? $existing['out_status'] ?? '', s($existing['out_status'] ?? '', ''));
  $remarks = s($payload['remarks'] ?? $existing['remarks'] ?? '', s($existing['remarks'] ?? '', ''));
  $totalHours = array_key_exists('totalWorkingHours', $payload) ? round(max(0, f($payload['totalWorkingHours'] ?? 0)), 2) : face_attendance_total_hours($attendanceDate, $inTime, $outTime);
  $attendanceStatus = s($payload['attendanceStatus'] ?? '', '');
  if($attendanceStatus === ''){
    if($inTime !== '' && $outTime === '') $attendanceStatus = 'Missing OUT';
    else $attendanceStatus = face_attendance_mark_status($inStatus, $outStatus, $outTime);
  }
  $upd = db()->prepare("UPDATE attendance SET attendance_date=?, in_time=?, out_time=?, total_working_hours=?, attendance_status=?, in_status=?, out_status=?, remarks=?, updated_at=? WHERE id=?");
  $upd->execute([$attendanceDate, $inTime, $outTime, $totalHours, $attendanceStatus, $inStatus, $outStatus, $remarks, now_iso(), $id]);
  $saved = face_attendance_fetch_one($id);
  if(!$saved) nf('Attendance record not found after update');
  return $saved;
}
function face_attendance_delete_record(int $id): void {
  face_attendance_manage_ctx();
  $st = db()->prepare("DELETE FROM attendance WHERE id=?");
  $st->execute([$id]);
  if($st->rowCount() === 0) nf('Attendance record not found');
}
function face_attendance_report_rows(array $query, array $ctx): array {
  $rows = face_attendance_sheet_rows($query, $ctx);
  $grouped = [];
  foreach($rows as $row){
    $eid = up($row['employeeId'] ?? '');
    if($eid === '') continue;
    if(!isset($grouped[$eid])){
      $grouped[$eid] = [
        'employeeId' => $row['employeeId'],
        'employeeName' => $row['employeeName'],
        'department' => $row['department'],
        'designation' => $row['designation'],
        'presentDays' => 0,
        'lateDays' => 0,
        'earlyOutDays' => 0,
        'missingOutDays' => 0,
        'totalWorkingHours' => 0.0
      ];
    }
    $grouped[$eid]['presentDays'] += 1;
    if(stripos((string)$row['inStatus'], 'late') !== false || stripos((string)$row['attendanceStatus'], 'late') !== false) $grouped[$eid]['lateDays'] += 1;
    if(stripos((string)$row['outStatus'], 'early') !== false || stripos((string)$row['attendanceStatus'], 'early') !== false) $grouped[$eid]['earlyOutDays'] += 1;
    if(stripos((string)$row['attendanceStatus'], 'missing out') !== false) $grouped[$eid]['missingOutDays'] += 1;
    $grouped[$eid]['totalWorkingHours'] += f($row['totalWorkingHours'] ?? 0);
  }
  return array_values(array_map(function(array $row){
    $row['totalWorkingHours'] = round($row['totalWorkingHours'], 2);
    return $row;
  }, $grouped));
}
function face_attendance_mark_status(string $inStatus, string $outStatus, string $outTime): string {
  $parts = [];
  if(stripos($inStatus, 'late') !== false) $parts[] = 'Late';
  if($outTime === '') $parts[] = 'Present';
  elseif(stripos($outStatus, 'early') !== false) $parts[] = 'Early Out';
  else $parts[] = 'Present';
  return implode(', ', array_values(array_unique($parts)));
}
function face_attendance_scan(array $payload): array {
  $ctx = face_attendance_view_ctx();
  $scanDescriptor = face_attendance_descriptor_value($payload['faceDescriptor'] ?? $payload['descriptor'] ?? []);
  if(count($scanDescriptor) < 32) bad('Face scan data is required');
  $scanMode = strtoupper(trim((string)($payload['scanMode'] ?? 'AUTO')));
  if(!in_array($scanMode, ['AUTO','IN','OUT'], true)) bad('scanMode must be AUTO, IN, or OUT');
  $settings = face_attendance_settings_get();
  $threshold = f($settings['faceMatchThreshold'] ?? 0.48);
  $now = face_attendance_now($settings);
  $date = $now->format('Y-m-d');
  $time = $now->format('H:i:s');
  $scope = face_attendance_emp_scope($ctx);
  $emp = null;
  $empId = '';
  $distance = 999.0;

  if($scope !== ''){
    $emp = face_attendance_employee($ctx, $payload, false);
    $empId = up($emp['id'] ?? '');
    $reg = db()->prepare("SELECT * FROM employee_faces WHERE employee_id=? LIMIT 1");
    $reg->execute([$empId]);
    $registered = $reg->fetch();
    if(!$registered){
      face_attendance_log($empId, $date, 'scan', 999.0, $threshold, false, 'Face not registered for employee', ['employeeId'=>$empId]);
      j(['detail'=>'Face not verified. Please try again or contact admin.'],422);
    }
    $storedDescriptor = face_attendance_descriptor_value($registered['face_descriptor'] ?? '[]');
    $distance = face_attendance_descriptor_distance($scanDescriptor, $storedDescriptor);
  } else {
    $requestedEmployeeId = up($payload['employeeId'] ?? '');
    if($requestedEmployeeId !== ''){
      $emp = face_attendance_employee($ctx, ['employeeId' => $requestedEmployeeId], true);
      $empId = up($emp['id'] ?? '');
      $reg = db()->prepare("SELECT * FROM employee_faces WHERE employee_id=? LIMIT 1");
      $reg->execute([$empId]);
      $registered = $reg->fetch();
      if(!$registered){
        face_attendance_log($empId, $date, 'scan', 999.0, $threshold, false, 'Face not registered for employee', ['employeeId'=>$empId]);
        j(['detail'=>'Face not verified. Please try again or contact admin.'],422);
      }
      $storedDescriptor = face_attendance_descriptor_value($registered['face_descriptor'] ?? '[]');
      $distance = face_attendance_descriptor_distance($scanDescriptor, $storedDescriptor);
    } else {
      $match = face_attendance_registered_match($scanDescriptor);
      if(!$match){
        face_attendance_log('', $date, 'scan', 999.0, $threshold, false, 'Face verification failed', ['mode'=>'auto-match']);
        j(['detail'=>'Face not verified. Please try again or contact admin.'],422);
      }
      $emp = $match['employee'];
      $empId = up($emp['id'] ?? '');
      $distance = f($match['score'] ?? 999.0);
    }
  }

  if($distance > $threshold){
    face_attendance_log($empId, $date, 'scan', $distance, $threshold, false, 'Face verification failed', ['employeeId'=>$empId]);
    j(['detail'=>'Face not verified. Please try again or contact admin.','score'=>round($distance,6)],422);
  }
  $curMin = face_attendance_time_to_minutes($now->format('H:i'));
  $inFrom = face_attendance_time_to_minutes((string)$settings['inAllowedFrom']);
  $lateAfter = face_attendance_time_to_minutes((string)$settings['lateMarkAfter']) + max(0, (int)$settings['graceTime']);
  $outFrom = face_attendance_time_to_minutes((string)$settings['outAllowedFrom']);
  $st = db()->prepare("SELECT * FROM attendance WHERE employee_id=? AND attendance_date=? LIMIT 1");
  $st->execute([$empId, $date]);
  $row = $st->fetch();
  $messageTitle = 'Face Verified Successfully';
  if($scanMode === 'IN' && $row && s($row['in_time'] ?? '', '') !== ''){
    face_attendance_log($empId, $date, 'scan', $distance, $threshold, true, 'Attendance IN already marked for today', ['employeeId'=>$empId, 'scanMode'=>$scanMode]);
    return [
      'verified' => true,
      'action' => 'IN_ALREADY',
      'score' => round($distance, 6),
      'messageTitle' => $messageTitle,
      'messageLine' => 'Attendance IN already marked for today.',
      'row' => face_attendance_row_payload($row)
    ];
  }
  if($scanMode === 'OUT' && (!$row || s($row['in_time'] ?? '', '') === '')){
    face_attendance_log($empId, $date, 'scan', $distance, $threshold, false, 'Attendance IN required before OUT scan', ['employeeId'=>$empId, 'scanMode'=>$scanMode]);
    j(['detail'=>'Attendance IN is required before OUT scan.'],422);
  }
  if(!$row){
    if($scanMode === 'OUT') j(['detail'=>'Attendance IN is required before OUT scan.'],422);
    if($curMin < $inFrom) j(['detail'=>'Attendance IN is not allowed yet. Please try within attendance time.'],422);
    $inStatus = $curMin > $lateAfter ? 'Late' : 'On Time';
    $status = face_attendance_mark_status($inStatus, '', '');
    $remarks = $inStatus === 'Late' ? 'Late attendance marked by face scan' : 'Attendance IN marked by face scan';
    $ins = db()->prepare("INSERT INTO attendance (employee_id, attendance_date, in_time, out_time, total_working_hours, attendance_status, in_status, out_status, remarks, source, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
    $ts = now_iso();
    $ins->execute([$empId, $date, $time, '', 0, $status, $inStatus, '', $remarks, 'face', $ts, $ts]);
    att_daily_upsert((int)$now->format('n'), (int)$now->format('Y'), [['empId'=>$empId,'date'=>$date,'status'=>'P']]);
    face_attendance_log($empId, $date, 'IN', $distance, $threshold, true, 'Attendance IN marked', ['employeeId'=>$empId]);
    $saved = db()->query("SELECT * FROM attendance WHERE id=".(int)db()->lastInsertId())->fetch() ?: [];
    return [
      'verified' => true,
      'action' => 'IN',
      'score' => round($distance, 6),
      'messageTitle' => $messageTitle,
      'messageLine' => 'Attendance IN marked at current time',
      'row' => face_attendance_row_payload($saved)
    ];
  }
  if(s($row['out_time'] ?? '', '') !== ''){
    face_attendance_log($empId, $date, 'scan', $distance, $threshold, true, 'Attendance already completed for today', ['employeeId'=>$empId]);
    return [
      'verified' => true,
      'action' => 'COMPLETED',
      'score' => round($distance, 6),
      'messageTitle' => $messageTitle,
      'messageLine' => 'Attendance already completed for today.',
      'row' => face_attendance_row_payload($row)
    ];
  }
  if($scanMode === 'IN'){
    face_attendance_log($empId, $date, 'scan', $distance, $threshold, true, 'Attendance IN already exists; use OUT scan', ['employeeId'=>$empId, 'scanMode'=>$scanMode]);
    return [
      'verified' => true,
      'action' => 'IN_ALREADY',
      'score' => round($distance, 6),
      'messageTitle' => $messageTitle,
      'messageLine' => 'Attendance IN already exists. Please use OUT scan.',
      'row' => face_attendance_row_payload($row)
    ];
  }
  $outStatus = $curMin < $outFrom ? 'Early Out' : 'On Time';
  $inTime = s($row['in_time'] ?? '', '');
  $totalHours = 0.0;
  if($inTime !== '' && preg_match('/^\d{2}:\d{2}:\d{2}$/', $inTime)){
    $inTs = strtotime($date.' '.$inTime);
    $outTs = strtotime($date.' '.$time);
    if($inTs !== false && $outTs !== false && $outTs >= $inTs){
      $totalHours = round(($outTs - $inTs) / 3600, 2);
    }
  }
  $status = face_attendance_mark_status((string)($row['in_status'] ?? ''), $outStatus, $time);
  $remarks = $outStatus === 'Early Out' ? 'Attendance OUT marked before allowed OUT time' : 'Attendance OUT marked by face scan';
  $upd = db()->prepare("UPDATE attendance SET out_time=?, total_working_hours=?, attendance_status=?, out_status=?, remarks=?, updated_at=? WHERE id=?");
  $upd->execute([$time, $totalHours, $status, $outStatus, $remarks, now_iso(), (int)$row['id']]);
  face_attendance_log($empId, $date, 'OUT', $distance, $threshold, true, 'Attendance OUT marked', ['employeeId'=>$empId]);
  $saved = db()->query("SELECT * FROM attendance WHERE id=".(int)$row['id'])->fetch() ?: [];
  return [
    'verified' => true,
    'action' => 'OUT',
    'score' => round($distance, 6),
    'messageTitle' => $messageTitle,
    'messageLine' => 'Attendance OUT marked at current time',
    'row' => face_attendance_row_payload($saved)
  ];
}
function auth_actor_name(): string {
  $ctx = auth_ctx(false);
  return s($ctx['username'] ?? $ctx['name'] ?? $ctx['sub'] ?? 'system', 'system');
}
function advance_permission_allowed(array $ctx): bool {
  $perm = $ctx['permissions'] ?? [];
  return !is_array($perm) || !array_key_exists('advanceSalary', $perm) || $perm['advanceSalary'] !== false;
}
function advance_view_ctx(): array {
  $ctx = auth_ctx(true);
  $role = strtolower((string)($ctx['role'] ?? ''));
  if(!in_array($role, ['super_admin','client','client_admin','agency_admin','employee'], true)) j(['detail'=>'Forbidden'],403);
  if(!advance_permission_allowed($ctx)) j(['detail'=>'Forbidden'],403);
  return $ctx;
}
function advance_manage_ctx(): array {
  $ctx = advance_view_ctx();
  $role = strtolower((string)($ctx['role'] ?? ''));
  if(in_array($role, ['employee'], true)) j(['detail'=>'Only admin/HR can create advances'],403);
  return $ctx;
}
function advance_emp_scope(array $ctx): string {
  $role = strtolower((string)($ctx['role'] ?? ''));
  if($role !== 'employee') return '';
  return up($ctx['empId'] ?? '');
}
function advance_month_period(int $year, int $month): string {
  return sprintf('%04d-%02d', $year, $month);
}
function advance_next_period(int $year, int $month, int $offset = 1): array {
  $base = strtotime(sprintf('%04d-%02d-01 UTC', $year, $month));
  if($base === false) return ['year'=>$year,'month'=>$month];
  $ts = strtotime(sprintf('+%d month', $offset), $base);
  return ['year'=>(int)gmdate('Y', $ts), 'month'=>(int)gmdate('n', $ts)];
}
function overtime_view_ctx(): array {
  $ctx = auth_ctx(true);
  $role = strtolower((string)($ctx['role'] ?? ''));
  if(!in_array($role, ['super_admin','client','client_admin','agency_admin','employee'], true)) j(['detail'=>'Forbidden'],403);
  return $ctx;
}
function overtime_manage_ctx(): array {
  $ctx = overtime_view_ctx();
  $role = strtolower((string)($ctx['role'] ?? ''));
  if($role === 'employee') j(['detail'=>'Only admin/HR can manage overtime'],403);
  return $ctx;
}
function overtime_emp_scope(array $ctx): string {
  $role = strtolower((string)($ctx['role'] ?? ''));
  return $role === 'employee' ? up($ctx['empId'] ?? '') : '';
}
function overtime_time_minutes(string $time): int {
  if(!preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $time, $m)) bad('Time must be HH:MM');
  return ((int)$m[1] * 60) + (int)$m[2];
}
function overtime_total_hours(string $startTime, string $endTime): float {
  $start = overtime_time_minutes($startTime);
  $end = overtime_time_minutes($endTime);
  if($end <= $start) $end += 24 * 60;
  return round(($end - $start) / 60, 2);
}
function overtime_row_payload(array $r): array {
  return [
    'id'=>(string)($r['id'] ?? ''),
    'empId'=>(string)($r['emp_id'] ?? ''),
    'employeeName'=>(string)($r['employee_name'] ?? ''),
    'otDate'=>(string)($r['ot_date'] ?? ''),
    'startTime'=>(string)($r['start_time'] ?? ''),
    'endTime'=>(string)($r['end_time'] ?? ''),
    'totalHours'=>round(f($r['total_hours'] ?? 0), 2),
    'rate'=>round(f($r['rate'] ?? 0), 2),
    'amount'=>round(f($r['amount'] ?? 0), 2),
    'notes'=>(string)($r['notes'] ?? ''),
    'createdBy'=>(string)($r['created_by'] ?? ''),
    'createdAt'=>(string)($r['created_at'] ?? ''),
    'updatedAt'=>(string)($r['updated_at'] ?? '')
  ];
}
function overtime_rows(array $ctx): array {
  $d = db();
  $empScope = overtime_emp_scope($ctx);
  $sql = "SELECT * FROM overtime_entries";
  $params = [];
  if($empScope !== ''){
    $sql .= " WHERE emp_id=?";
    $params[] = $empScope;
  }
  $sql .= " ORDER BY ot_date DESC, start_time DESC, created_at DESC";
  $q = $d->prepare($sql);
  $q->execute($params);
  return array_map('overtime_row_payload', $q->fetchAll());
}
function overtime_monthly_map(int $month, int $year): array {
  $start = sprintf('%04d-%02d-01', $year, $month);
  $endTs = strtotime($start . ' +1 month UTC');
  if($month < 1 || $month > 12 || $year < 2000 || $endTs === false) return [];
  $end = gmdate('Y-m-d', $endTs);
  $q = db()->prepare("SELECT emp_id, COALESCE(SUM(total_hours),0) AS total_hours, COALESCE(SUM(amount),0) AS amount, COUNT(*) AS entries FROM overtime_entries WHERE ot_date>=? AND ot_date<? GROUP BY emp_id");
  $q->execute([$start, $end]);
  $out = [];
  foreach($q->fetchAll() as $r){
    $eid = up($r['emp_id'] ?? '');
    if($eid === '') continue;
    $out[$eid] = [
      'hours'=>round(f($r['total_hours'] ?? 0), 2),
      'amount'=>round(f($r['amount'] ?? 0), 2),
      'entries'=>(int)($r['entries'] ?? 0)
    ];
  }
  return $out;
}
function overtime_stats(array $rows): array {
  $month = gmdate('Y-m');
  $monthRows = array_values(array_filter($rows, fn($r) => str_starts_with((string)($r['otDate'] ?? ''), $month)));
  $sum = fn($items, $key) => round(array_reduce($items, fn($c, $r) => $c + f($r[$key] ?? 0), 0.0), 2);
  return [
    'entries'=>count($rows),
    'totalHours'=>$sum($rows, 'totalHours'),
    'totalAmount'=>$sum($rows, 'amount'),
    'monthHours'=>$sum($monthRows, 'totalHours'),
    'monthAmount'=>$sum($monthRows, 'amount')
  ];
}
function overtime_create(array $payload): array {
  overtime_manage_ctx();
  $d = db();
  $empId = up($payload['empId'] ?? '');
  if($empId === '') bad('empId is required');
  $emp = null;
  foreach(employees_all() as $e){ if(up($e['id'] ?? '') === $empId){ $emp = $e; break; } }
  if(!$emp) nf('Employee not found');
  $otDate = s($payload['otDate'] ?? '');
  if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $otDate)) bad('otDate must be YYYY-MM-DD');
  $startTime = s($payload['startTime'] ?? '');
  $endTime = s($payload['endTime'] ?? '');
  $hours = overtime_total_hours($startTime, $endTime);
  if($hours <= 0) bad('Total hours must be greater than 0');
  $rate = round(f($payload['rate'] ?? 0), 2);
  if($rate < 0) bad('rate cannot be negative');
  $amount = round($hours * $rate, 2);
  $id = 'OT-'.preg_replace('/[^A-Z0-9]/', '', $empId).'-'.time();
  $now = now_iso();
  $notes = s($payload['notes'] ?? '');
  $st = $d->prepare("INSERT INTO overtime_entries (id,emp_id,employee_name,ot_date,start_time,end_time,total_hours,rate,amount,notes,created_by,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
  $st->execute([$id,$empId,s($emp['name'] ?? $empId),$otDate,$startTime,$endTime,$hours,$rate,$amount,$notes,auth_actor_name(),$now,$now]);
  invalidate_salary_dependent_sheets();
  $q = $d->prepare("SELECT * FROM overtime_entries WHERE id=? LIMIT 1");
  $q->execute([$id]);
  return overtime_row_payload($q->fetch() ?: ['id'=>$id]);
}
function overtime_delete(string $id): void {
  overtime_manage_ctx();
  $id = s($id);
  if($id === '') bad('Invalid overtime id');
  $q = db()->prepare("DELETE FROM overtime_entries WHERE id=?");
  $q->execute([$id]);
  if($q->rowCount() <= 0) nf('Overtime entry not found');
  invalidate_salary_dependent_sheets();
}
function advance_row_payload(array $r): array {
  return [
    'id'=>(string)$r['id'],
    'empId'=>(string)$r['emp_id'],
    'employeeName'=>(string)$r['employee_name'],
    'amount'=>round(f($r['amount'] ?? 0), 2),
    'repaymentType'=>strtolower((string)$r['repayment_type']) === 'emi' ? 'emi' : 'full',
    'emiMonths'=>(int)($r['emi_months'] ?? 1),
    'emiAmount'=>round(f($r['emi_amount'] ?? 0), 2),
    'disbursedOn'=>(string)$r['disbursed_on'],
    'startYear'=>(int)($r['start_year'] ?? 0),
    'startMonth'=>(int)($r['start_month'] ?? 0),
    'attendanceYear'=>(int)($r['attendance_year'] ?? 0),
    'attendanceMonth'=>(int)($r['attendance_month'] ?? 0),
    'attendanceThroughDate'=>(string)($r['attendance_through_date'] ?? ''),
    'presentDays'=>round(f($r['present_days'] ?? 0), 2),
    'eligibleSalary'=>round(f($r['eligible_salary'] ?? 0), 2),
    'monthlyGross'=>round(f($r['monthly_gross'] ?? 0), 2),
    'notes'=>(string)($r['notes'] ?? ''),
    'status'=>(string)($r['status'] ?? 'Active'),
    'createdBy'=>(string)($r['created_by'] ?? ''),
    'createdAt'=>(string)($r['created_at'] ?? ''),
    'updatedAt'=>(string)($r['updated_at'] ?? ''),
  ];
}
function advance_calc_summary(PDO $d, array $advance): array {
  $qid = (string)($advance['id'] ?? '');
  $amount = f($advance['amount'] ?? 0);
  $q = $d->prepare("SELECT COALESCE(SUM(deducted_amount),0) AS deducted, MIN(CASE WHEN status<>'Deducted' AND balance_after>0 THEN (deduction_year*100 + deduction_month) ELSE NULL END) AS next_code FROM advance_deductions WHERE advance_id=?");
  $q->execute([$qid]);
  $r = $q->fetch() ?: [];
  $deducted = round(f($r['deducted'] ?? 0), 2);
  $remaining = round(max(0.0, $amount - $deducted), 2);
  $nextCode = (int)($r['next_code'] ?? 0);
  $nextDue = '';
  if($nextCode > 0){
    $nextYear = (int)floor($nextCode / 100);
    $nextMonth = $nextCode % 100;
    $nextDue = advance_month_period($nextYear, $nextMonth);
  }
  return ['deductedAmount'=>$deducted,'remainingBalance'=>$remaining,'nextDuePeriod'=>$nextDue];
}
function advance_attendance_status_for_day(array $daily, string $empId, string $isoDate): string {
  return strtoupper((string)($daily[$empId.'|'.$isoDate] ?? ''));
}
function advance_effective_monthly_gross(array $emp): float {
  $eid = up($emp['id'] ?? '');
  $ctrl = control_get();
  $ov = ovr_all();
  $o = $ov[$eid] ?? [];
  $gross = (isset($o['gross']) && $o['gross'] !== null) ? f($o['gross']) : 0.0;
  $ctc = (isset($o['ctc']) && $o['ctc'] !== null) ? f($o['ctc']) : 0.0;
  $masterCtc = f($emp['baseCtc'] ?? 0);
  $base = $gross > 0 ? $gross : ($ctc > 0 ? $ctc : ($masterCtc > 0 ? $masterCtc : 25000));
  $parts = split_ctc($base, $ctrl);
  return round(f($parts['gross'] ?? 0), 2);
}
function advance_existing_month_total(PDO $d, string $empId, int $year, int $month): float {
  $q = $d->prepare("SELECT COALESCE(SUM(amount),0) AS total FROM salary_advances WHERE emp_id=? AND attendance_year=? AND attendance_month=?");
  $q->execute([$empId,$year,$month]);
  $row = $q->fetch() ?: [];
  return round(f($row['total'] ?? 0), 2);
}
function advance_eligibility(string $empId, string $asOfDate): array {
  $empId = up($empId);
  if($empId === '') bad('empId is required');
  if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $asOfDate)) bad('date must be YYYY-MM-DD');
  $ts = strtotime($asOfDate . ' 00:00:00 UTC');
  if($ts === false) bad('date is invalid');
  $emp = null;
  foreach(employees_all() as $e){
    if(up($e['id'] ?? '') === $empId){
      $emp = $e;
      break;
    }
  }
  if(!$emp) nf('Employee not found');
  $year = (int)gmdate('Y', $ts);
  $month = (int)gmdate('n', $ts);
  $day = (int)gmdate('j', $ts);
  $dim = (int)cal_days_in_month(CAL_GREGORIAN, $month, $year);
  $daily = kv_get(att_month_key($month,$year), []);
  if(!is_array($daily)) $daily = [];
  $presentDays = 0.0;
  for($d=1; $d<=$day; $d++){
    $iso = sprintf('%04d-%02d-%02d', $year, $month, $d);
    if(advance_attendance_status_for_day($daily, $empId, $iso) === 'P'){
      $presentDays += 1.0;
    }
  }
  $monthlyGross = advance_effective_monthly_gross($emp);
  $perDaySalary = $dim > 0 ? round($monthlyGross / $dim, 2) : 0.0;
  $eligibleSalary = round($perDaySalary * $presentDays, 2);
  $existingMonthAdvance = advance_existing_month_total(db(), $empId, $year, $month);
  $remainingEligible = round(max(0.0, $eligibleSalary - $existingMonthAdvance), 2);
  return [
    'empId'=>$empId,
    'employeeName'=>(string)($emp['name'] ?? $empId),
    'date'=>$asOfDate,
    'year'=>$year,
    'month'=>$month,
    'daysInMonth'=>$dim,
    'daysConsidered'=>$day,
    'presentDays'=>round($presentDays, 2),
    'monthlyGross'=>round($monthlyGross, 2),
    'perDaySalary'=>round($perDaySalary, 2),
    'eligibleSalary'=>round($eligibleSalary, 2),
    'existingMonthAdvance'=>round($existingMonthAdvance, 2),
    'remainingEligible'=>round($remainingEligible, 2),
    'attendanceRule'=>'present_only'
  ];
}

function enquiry_modules_norm($raw): array {
  $rows = is_array($raw) ? $raw : [];
  $seen = [];
  $out = [];
  foreach($rows as $x){
    $txt = s($x);
    if($txt === '') continue;
    $key = strtolower($txt);
    if(isset($seen[$key])) continue;
    $seen[$key] = true;
    $out[] = $txt;
  }
  return $out;
}
function enquiry_status_norm(string $status): string {
  $map = [
    'new' => 'New',
    'contacted' => 'Contacted',
    'demo scheduled' => 'Demo Scheduled',
    'demo_scheduled' => 'Demo Scheduled',
    'qualified' => 'Qualified',
    'closed' => 'Closed'
  ];
  $key = strtolower(trim($status));
  return $map[$key] ?? 'New';
}
function enquiry_row_payload(array $r): array {
  $mods = json_decode((string)($r['modules'] ?? '[]'), true);
  return [
    'id' => (int)($r['id'] ?? 0),
    'fullName' => (string)($r['full_name'] ?? ''),
    'companyName' => (string)($r['company_name'] ?? ''),
    'workEmail' => (string)($r['work_email'] ?? ''),
    'phoneNo' => (string)($r['phone_no'] ?? ''),
    'teamSize' => (string)($r['team_size'] ?? ''),
    'productInterest' => (string)($r['product_interest'] ?? ''),
    'preferredDate' => (string)($r['preferred_date'] ?? ''),
    'preferredTime' => (string)($r['preferred_time'] ?? ''),
    'modules' => is_array($mods) ? array_values($mods) : [],
    'message' => (string)($r['message'] ?? ''),
    'sourcePage' => (string)($r['source_page'] ?? 'landing'),
    'status' => (string)($r['status'] ?? 'New'),
    'adminNote' => (string)($r['admin_note'] ?? ''),
    'createdAt' => (string)($r['created_at'] ?? ''),
    'updatedAt' => (string)($r['updated_at'] ?? '')
  ];
}
function email_log_write(string $module, string $recordId, int $clientId, string $recipient, string $subject, bool $ok, string $error = ''): void {
  try {
    $d = central_db();
    $st = $d->prepare("INSERT INTO email_logs (module,record_id,client_id,recipient,subject,direction,status,error_message,created_at) VALUES (?,?,?,?,?,?,?,?,?)");
    $st->execute([$module,$recordId,$clientId,$recipient,$subject,'outbound',$ok ? 'sent' : 'failed',$error,now_iso()]);
  } catch (Throwable $e) {
    // Email logging must never block business actions.
  }
}
function enquiry_message_lines(string $message): array {
  $out = [];
  foreach (preg_split('/\r?\n/', $message) ?: [] as $line) {
    $txt = trim((string)$line);
    if ($txt !== '') $out[] = $txt;
  }
  return $out;
}
function enquiry_admin_email_html(array $row): string {
  $lines = enquiry_message_lines((string)($row['message'] ?? ''));
  $extra = '';
  foreach ($lines as $line) {
    $extra .= '<li style="margin:0 0 6px 0;">' . htmlspecialchars($line, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</li>';
  }
  return '<div style="font-family:Arial,sans-serif;color:#1f2937;line-height:1.6;">'
    . '<h2 style="margin:0 0 12px 0;color:#16404b;">New landing enquiry received</h2>'
    . '<p style="margin:0 0 12px 0;">A new enquiry has been submitted from the HR Seva landing page.</p>'
    . '<table cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;">'
    . '<tr><td style="padding:6px 0;font-weight:bold;">Full Name</td><td style="padding:6px 0;">' . htmlspecialchars((string)($row['fullName'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</td></tr>'
    . '<tr><td style="padding:6px 0;font-weight:bold;">Company</td><td style="padding:6px 0;">' . htmlspecialchars((string)($row['companyName'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</td></tr>'
    . '<tr><td style="padding:6px 0;font-weight:bold;">Business Email</td><td style="padding:6px 0;">' . htmlspecialchars((string)($row['workEmail'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</td></tr>'
    . '<tr><td style="padding:6px 0;font-weight:bold;">Phone</td><td style="padding:6px 0;">' . htmlspecialchars((string)($row['phoneNo'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</td></tr>'
    . '<tr><td style="padding:6px 0;font-weight:bold;">Company Size</td><td style="padding:6px 0;">' . htmlspecialchars((string)($row['teamSize'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</td></tr>'
    . '</table>'
    . ($extra !== '' ? '<h3 style="margin:18px 0 10px 0;color:#16404b;font-size:16px;">Additional details</h3><ul style="padding-left:18px;margin:0;">' . $extra . '</ul>' : '')
    . '</div>';
}
function enquiry_customer_email_html(array $row): string {
  return '<div style="font-family:Arial,sans-serif;color:#1f2937;line-height:1.6;">'
    . '<h2 style="margin:0 0 12px 0;color:#16404b;">Thank you for contacting HR Seva</h2>'
    . '<p style="margin:0 0 12px 0;">Hi ' . htmlspecialchars((string)($row['fullName'] ?? 'there'), ENT_QUOTES | ENT_HTML5, 'UTF-8') . ',</p>'
    . '<p style="margin:0 0 12px 0;">We have received your enquiry and our team will contact you shortly.</p>'
    . '<p style="margin:0 0 12px 0;">Company: <strong>' . htmlspecialchars((string)($row['companyName'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</strong></p>'
    . '<p style="margin:0;">Regards,<br><strong>HR Seva Team</strong></p>'
    . '</div>';
}
function enquiry_send_emails(array $row): void {
  $recordId = (string)($row['id'] ?? '');
  $admins = hr_mail_admin_list();
  if ($admins) {
    $subject = 'New Landing Enquiry | ' . s($row['companyName'] ?? 'HR Seva');
    $result = hr_mail_send($admins, $subject, enquiry_admin_email_html($row));
    email_log_write('landing_enquiry_admin', $recordId, 0, implode(', ', $admins), $subject, (bool)($result['ok'] ?? false), (string)($result['error'] ?? ''));
  }
  $workEmail = s($row['workEmail'] ?? '');
  if ($workEmail !== '' && hr_mail_is_valid_email($workEmail)) {
    $subject = 'Thank you for contacting HR Seva';
    $result = hr_mail_send([$workEmail], $subject, enquiry_customer_email_html($row));
    email_log_write('landing_enquiry_customer', $recordId, 0, $workEmail, $subject, (bool)($result['ok'] ?? false), (string)($result['error'] ?? ''));
  }
}
function mail_html_escape(string $value): string {
  return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
function mail_valid_email_or_blank(string $value): string {
  $email = trim($value);
  return hr_mail_is_valid_email($email) ? $email : '';
}
function mail_unique_recipients(array $emails): array {
  $seen = [];
  $out = [];
  foreach ($emails as $email) {
    $clean = mail_valid_email_or_blank((string)$email);
    if ($clean === '') continue;
    $key = strtolower($clean);
    if (isset($seen[$key])) continue;
    $seen[$key] = true;
    $out[] = $clean;
  }
  return $out;
}
function mail_facts_html(array $facts): string {
  if (!$facts) return '';
  $rows = '';
  foreach ($facts as $label => $value) {
    $txt = trim((string)$value);
    if ($txt === '') continue;
    $rows .= '<tr><td style="padding:6px 0;font-weight:bold;width:180px;">' . mail_html_escape((string)$label) . '</td><td style="padding:6px 0;">' . mail_html_escape($txt) . '</td></tr>';
  }
  return $rows === '' ? '' : '<table cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;">' . $rows . '</table>';
}
function mail_shell_html(string $title, string $intro, array $facts = [], string $closing = ''): string {
  $body = '<div style="font-family:Arial,sans-serif;color:#1f2937;line-height:1.6;">'
    . '<h2 style="margin:0 0 12px 0;color:#16404b;">' . mail_html_escape($title) . '</h2>'
    . '<p style="margin:0 0 12px 0;">' . mail_html_escape($intro) . '</p>';
  $factsHtml = mail_facts_html($facts);
  if ($factsHtml !== '') $body .= $factsHtml;
  if ($closing !== '') $body .= '<p style="margin:14px 0 0 0;">' . nl2br(mail_html_escape($closing)) . '</p>';
  return $body . '</div>';
}
function mail_send_logged(string $module, string $recordId, int $clientId, array $recipients, string $subject, string $html): void {
  $to = mail_unique_recipients($recipients);
  if (!$to) return;
  $res = hr_mail_send($to, $subject, $html);
  email_log_write($module, $recordId, $clientId, implode(', ', $to), $subject, (bool)($res['ok'] ?? false), (string)($res['error'] ?? ''));
}
function mail_send_admins(string $module, string $recordId, int $clientId, string $subject, string $title, string $intro, array $facts = []): void {
  $admins = mail_unique_recipients(hr_mail_admin_list());
  if (!$admins) return;
  mail_send_logged($module, $recordId, $clientId, $admins, $subject, mail_shell_html($title, $intro, $facts, 'HR Seva automated notification.'));
}
function client_row_get(int $clientId): ?array {
  if ($clientId <= 0) return null;
  $q = central_db()->prepare("SELECT * FROM clients WHERE id=? LIMIT 1");
  $q->execute([$clientId]);
  $row = $q->fetch();
  return $row ?: null;
}
function tenant_profile_lookup(int $clientId): array {
  if ($clientId <= 0) return [];
  try {
    $td = db_open(db_path_for_client($clientId));
    init_schema($td);
    $profile = kv_get_on($td, 'company_profile', []);
    return is_array($profile) ? $profile : [];
  } catch (Throwable $e) {
    return [];
  }
}
function client_contact_context(int $clientId): array {
  $client = client_row_get($clientId) ?: [];
  $profile = tenant_profile_lookup($clientId);
  $primaryEmail = mail_valid_email_or_blank((string)($client['company_email'] ?? ''));
  if ($primaryEmail === '') $primaryEmail = mail_valid_email_or_blank((string)($client['user_id'] ?? ''));
  if ($primaryEmail === '') $primaryEmail = mail_valid_email_or_blank((string)($profile['email'] ?? ''));
  return [
    'clientId' => $clientId,
    'companyName' => s($client['company_name'] ?? $profile['companyName'] ?? 'Client', 'Client'),
    'primaryEmail' => $primaryEmail,
    'emails' => mail_unique_recipients([$primaryEmail, (string)($profile['email'] ?? '')]),
    'contactName' => s($profile['contactName'] ?? '', ''),
    'contactNo' => s($client['company_contact_no'] ?? $profile['contactNo'] ?? '', '')
  ];
}
function employee_lookup(string $empId): array {
  $needle = up($empId);
  foreach (employees_all() as $emp) {
    if (up($emp['id'] ?? '') === $needle) return $emp;
  }
  return [];
}
function employee_email(string $empId): string {
  return mail_valid_email_or_blank((string)(employee_lookup($empId)['email'] ?? ''));
}
function mail_client_onboarding(string $recordId, array $clientRow, string $password, bool $isNew): void {
  $clientId = (int)($clientRow['id'] ?? 0);
  $ctx = client_contact_context($clientId);
  $subject = ($isNew ? 'Client access created' : 'Client access updated') . ' | ' . $ctx['companyName'];
  $facts = [
    'Company' => $ctx['companyName'],
    'User ID' => (string)($clientRow['userId'] ?? ''),
    'Contact No' => (string)($clientRow['companyContactNo'] ?? '')
  ];
  if ($password !== '') $facts['Password'] = $password;
  if ($ctx['emails']) {
    $intro = $isNew ? 'Your HR Seva client account is ready.' : 'Your HR Seva client account details were updated.';
    mail_send_logged('client_account_customer', $recordId, $clientId, $ctx['emails'], $subject, mail_shell_html('HR Seva Client Access', $intro, $facts, 'Please keep these credentials secure.'));
  }
  mail_send_admins('client_account_admin', $recordId, $clientId, $subject, 'Client account updated', 'A client account was created or updated from Super Admin.', $facts);
}
function mail_staff_access(int $clientId, string $recordId, array $staffRow, array $employee, string $password, bool $isNew): void {
  $email = mail_valid_email_or_blank((string)($staffRow['username'] ?? ''));
  $company = client_contact_context($clientId)['companyName'];
  $facts = [
    'Company' => $company,
    'Employee' => (string)($employee['name'] ?? $staffRow['empName'] ?? $staffRow['empId'] ?? ''),
    'Username' => (string)($staffRow['username'] ?? ''),
    'Role' => (string)($staffRow['roleCode'] ?? ''),
    'Status' => (string)($staffRow['status'] ?? '')
  ];
  if ($password !== '') $facts['Password'] = $password;
  $subject = ($isNew ? 'Staff portal access created' : 'Staff portal access updated') . ' | ' . $company;
  if ($email !== '') {
    $intro = $isNew ? 'Your HR Seva staff access is ready.' : 'Your HR Seva staff access has been updated.';
    mail_send_logged('staff_access_customer', $recordId, $clientId, [$email], $subject, mail_shell_html('Staff Access Details', $intro, $facts, 'Use these credentials to sign in to your portal.'));
  }
  mail_send_admins('staff_access_admin', $recordId, $clientId, $subject, 'Staff access updated', 'A staff access record was created or updated.', $facts);
}
function mail_employee_event(int $clientId, string $recordId, array $employee, bool $isNew): void {
  $email = mail_valid_email_or_blank((string)($employee['email'] ?? ''));
  $company = client_contact_context($clientId)['companyName'];
  $facts = [
    'Employee ID' => (string)($employee['id'] ?? ''),
    'Employee Name' => (string)($employee['name'] ?? ''),
    'Department' => (string)($employee['dept'] ?? ''),
    'Designation' => (string)($employee['desig'] ?? ''),
    'Company' => $company
  ];
  $subject = ($isNew ? 'Employee profile created' : 'Employee profile updated') . ' | ' . $company;
  if ($email !== '') {
    $intro = $isNew ? 'Your employee profile was created in HR Seva.' : 'Your employee profile was updated in HR Seva.';
    mail_send_logged('employee_profile_customer', $recordId, $clientId, [$email], $subject, mail_shell_html('Employee Profile Update', $intro, $facts, 'If any detail looks incorrect, contact your HR team.'));
  }
  mail_send_admins('employee_profile_admin', $recordId, $clientId, $subject, 'Employee profile updated', 'An employee profile was created or updated.', $facts);
}
function mail_leave_event(int $clientId, string $recordId, array $leaveRow, bool $isNew): void {
  $employee = employee_lookup((string)($leaveRow['empId'] ?? ''));
  $email = mail_valid_email_or_blank((string)($employee['email'] ?? ''));
  $company = client_contact_context($clientId)['companyName'];
  $facts = [
    'Employee ID' => (string)($leaveRow['empId'] ?? ''),
    'Employee Name' => (string)($leaveRow['empName'] ?? ''),
    'Leave Type' => (string)($leaveRow['leaveType'] ?? ''),
    'From Date' => (string)($leaveRow['fromDate'] ?? ''),
    'To Date' => (string)($leaveRow['toDate'] ?? ''),
    'Days' => (string)($leaveRow['days'] ?? ''),
    'Status' => (string)($leaveRow['status'] ?? ''),
    'Company' => $company
  ];
  $subject = ($isNew ? 'Leave request created' : 'Leave request updated') . ' | ' . $company;
  if ($email !== '') {
    $intro = $isNew ? 'Your leave request has been recorded in HR Seva.' : 'Your leave request has been updated in HR Seva.';
    mail_send_logged('leave_customer', $recordId, $clientId, [$email], $subject, mail_shell_html('Leave Request Update', $intro, $facts, 'You can contact HR if you need any changes.'));
  }
  mail_send_admins('leave_admin', $recordId, $clientId, $subject, 'Leave request updated', 'A leave entry was created or updated.', $facts);
}
function mail_subscription_event(string $recordId, array $subscriptionRow, bool $isNew): void {
  $clientId = (int)($subscriptionRow['clientId'] ?? 0);
  $ctx = client_contact_context($clientId);
  $facts = [
    'Company' => (string)($subscriptionRow['clientName'] ?? $ctx['companyName']),
    'Plan' => (string)($subscriptionRow['planName'] ?? ''),
    'Start Date' => (string)($subscriptionRow['startDate'] ?? ''),
    'End Date' => (string)($subscriptionRow['endDate'] ?? ''),
    'Renewal Date' => (string)($subscriptionRow['renewalDate'] ?? ''),
    'Status' => (string)($subscriptionRow['status'] ?? ''),
    'Amount' => 'Rs ' . number_format(f($subscriptionRow['amount'] ?? 0), 2)
  ];
  $subject = ($isNew ? 'Subscription created' : 'Subscription updated') . ' | ' . ($ctx['companyName'] ?: 'HR Seva');
  if ($ctx['emails']) {
    $intro = $isNew ? 'Your subscription has been created in HR Seva.' : 'Your subscription details have been updated in HR Seva.';
    mail_send_logged('subscription_customer', $recordId, $clientId, $ctx['emails'], $subject, mail_shell_html('Subscription Update', $intro, $facts, 'Please contact support if you need billing assistance.'));
  }
  mail_send_admins('subscription_admin', $recordId, $clientId, $subject, 'Subscription updated', 'A client subscription record was created or updated.', $facts);
}
function mail_advance_event(int $clientId, array $advanceRow): void {
  $email = employee_email((string)($advanceRow['empId'] ?? ''));
  $company = client_contact_context($clientId)['companyName'];
  $facts = [
    'Employee ID' => (string)($advanceRow['empId'] ?? ''),
    'Employee Name' => (string)($advanceRow['employeeName'] ?? ''),
    'Amount' => 'Rs ' . number_format(f($advanceRow['amount'] ?? 0), 2),
    'Repayment' => strtoupper((string)($advanceRow['repaymentType'] ?? 'full')),
    'Disbursed On' => (string)($advanceRow['disbursedOn'] ?? ''),
    'Company' => $company
  ];
  $subject = 'Advance salary created | ' . $company;
  if ($email !== '') {
    mail_send_logged('advance_salary_customer', (string)($advanceRow['id'] ?? ''), $clientId, [$email], $subject, mail_shell_html('Advance Salary Update', 'An advance salary entry has been created for you in HR Seva.', $facts, 'Please review the schedule with your HR team if needed.'));
  }
  mail_send_admins('advance_salary_admin', (string)($advanceRow['id'] ?? ''), $clientId, $subject, 'Advance salary created', 'A new advance salary entry was created.', $facts);
}
function mail_challan_event(string $moduleBase, int $clientId, array $row, string $label): void {
  $ctx = client_contact_context($clientId);
  $facts = [
    'Company' => $ctx['companyName'],
    'Period' => (string)($row['period'] ?? ''),
    'Challan No' => (string)($row['challanNo'] ?? $row['id'] ?? ''),
    'Paid Date' => (string)($row['paidDate'] ?? $row['dueDate'] ?? ''),
    'Amount' => 'Rs ' . number_format(f($row['amount'] ?? 0), 2),
    'Status' => (string)($row['status'] ?? 'Saved')
  ];
  $subject = $label . ' update | ' . $ctx['companyName'];
  if ($ctx['emails']) {
    mail_send_logged($moduleBase . '_customer', (string)($row['id'] ?? ''), $clientId, $ctx['emails'], $subject, mail_shell_html($label . ' Update', 'A compliance document was saved in HR Seva.', $facts, 'You can log in to review the latest record.'));
  }
  mail_send_admins($moduleBase . '_admin', (string)($row['id'] ?? ''), $clientId, $subject, $label . ' updated', 'A compliance document was created or updated.', $facts);
}
function mail_fnf_event(int $clientId, array $row): void {
  $email = employee_email((string)($row['empId'] ?? ''));
  $company = client_contact_context($clientId)['companyName'];
  $facts = [
    'Employee ID' => (string)($row['empId'] ?? ''),
    'Employee Name' => (string)($row['employeeName'] ?? ''),
    'Exit Date' => (string)($row['exitDate'] ?? ''),
    'Final Pay' => 'Rs ' . number_format(f($row['finalPay'] ?? 0), 2),
    'Company' => $company
  ];
  $subject = 'FNF generated | ' . $company;
  if ($email !== '') {
    mail_send_logged('fnf_customer', (string)($row['id'] ?? ''), $clientId, [$email], $subject, mail_shell_html('Full and Final Settlement', 'Your FNF statement has been generated in HR Seva.', $facts, 'Please connect with HR for payout timelines.'));
  }
  mail_send_admins('fnf_admin', (string)($row['id'] ?? ''), $clientId, $subject, 'FNF generated', 'A full and final settlement sheet was generated.', $facts);
}
function mail_payslip_event(int $clientId, array $sheet): void {
  $empId = (string)($sheet['empId'] ?? '');
  $email = employee_email($empId);
  $company = client_contact_context($clientId)['companyName'];
  $totals = is_array($sheet['data']['totals'] ?? null) ? $sheet['data']['totals'] : [];
  $facts = [
    'Employee ID' => $empId,
    'Employee Name' => (string)($sheet['employeeName'] ?? ''),
    'Period' => (string)($sheet['monthKey'] ?? ''),
    'Net Pay' => 'Rs ' . number_format(f($totals['netPay'] ?? 0), 2),
    'Company' => $company
  ];
  $subject = 'Payslip generated | ' . ((string)($sheet['monthKey'] ?? ''));
  if ($email !== '') {
    mail_send_logged('payslip_customer', (string)($sheet['id'] ?? ''), $clientId, [$email], $subject, mail_shell_html('Payslip Ready', 'Your payslip has been generated in HR Seva.', $facts, 'Please log in to view or download it securely.'));
  }
  mail_send_admins('payslip_admin', (string)($sheet['id'] ?? ''), $clientId, $subject, 'Payslip generated', 'A payslip was generated for an employee.', $facts);
}
function mail_sheet_event(string $moduleBase, int $clientId, array $sheet, string $label, array $extraFacts = []): void {
  $ctx = client_contact_context($clientId);
  $facts = [
    'Company' => $ctx['companyName'],
    'Period' => (string)($sheet['period'] ?? ''),
    'Rows' => (string)($sheet['rowCount'] ?? count((array)($sheet['rows'] ?? [])))
  ] + $extraFacts;
  $subject = $label . ' generated | ' . ($ctx['companyName'] ?: 'HR Seva');
  if ($ctx['emails']) {
    mail_send_logged($moduleBase . '_customer', (string)($sheet['id'] ?? $sheet['period'] ?? $label), $clientId, $ctx['emails'], $subject, mail_shell_html($label . ' Ready', 'A new sheet has been generated in HR Seva.', $facts, 'You can sign in to review or download it.'));
  }
  mail_send_admins($moduleBase . '_admin', (string)($sheet['id'] ?? $sheet['period'] ?? $label), $clientId, $subject, $label . ' generated', 'A generated sheet is ready in the system.', $facts);
}
function auth_forgot(array $raw): array {
  $email = mail_valid_email_or_blank((string)($raw['email'] ?? ''));
  if ($email === '') bad('email is required');
  $subject = 'HR Seva password assistance request';
  $matched = false;
  $clientCtx = null;
  $q = central_db()->prepare("SELECT id, company_name, user_id FROM clients WHERE lower(user_id)=lower(?) LIMIT 1");
  $q->execute([$email]);
  $client = $q->fetch();
  if ($client) {
    $matched = true;
    $clientCtx = client_contact_context((int)$client['id']);
    $facts = ['Company' => (string)($client['company_name'] ?? ''), 'Username' => (string)($client['user_id'] ?? '')];
    mail_send_logged('forgot_password_customer', 'client_' . (int)$client['id'], (int)$client['id'], [$email], $subject, mail_shell_html('Password Assistance Request', 'We received a password assistance request for your HR Seva client account.', $facts, 'For security, our team will help you with the next step.'));
  } else {
    $staff = staff_user_get_by_username($email);
    if ($staff) {
      $matched = true;
      $clientCtx = client_contact_context((int)($staff['clientId'] ?? 0));
      $facts = ['Username' => (string)($staff['username'] ?? ''), 'Employee ID' => (string)($staff['empId'] ?? ''), 'Company' => $clientCtx['companyName']];
      mail_send_logged('forgot_password_customer', 'staff_' . (int)($staff['id'] ?? 0), (int)($staff['clientId'] ?? 0), [$email], $subject, mail_shell_html('Password Assistance Request', 'We received a password assistance request for your HR Seva staff account.', $facts, 'For security, our team will help you with the next step.'));
    }
  }
  $facts = ['Requested Email' => $email, 'Matched Account' => $matched ? 'Yes' : 'No'];
  if ($clientCtx) $facts['Company'] = (string)($clientCtx['companyName'] ?? '');
  mail_send_admins('forgot_password_admin', $email, (int)($clientCtx['clientId'] ?? 0), 'Password assistance request | HR Seva', 'Password assistance requested', 'A password assistance request was submitted.', $facts);
  return ['ok' => true, 'message' => 'If this email is registered, reset instructions will be sent.'];
}
function public_enquiry_create(array $raw): array {
  $fullName = s($raw['fullName'] ?? '');
  $companyName = s($raw['companyName'] ?? '');
  $workEmail = s($raw['workEmail'] ?? '');
  $phoneNo = s($raw['phoneNo'] ?? '');
  $teamSize = s($raw['teamSize'] ?? '');
  $productInterest = s($raw['productInterest'] ?? '');
  $preferredDate = s($raw['preferredDate'] ?? '');
  $preferredTime = s($raw['preferredTime'] ?? '');
  $message = s($raw['message'] ?? '');
  $sourcePage = s($raw['sourcePage'] ?? 'landing', 'landing');
  $modules = enquiry_modules_norm($raw['modules'] ?? []);

  if($fullName === '') bad('Full name is required');
  if($companyName === '') bad('Company name is required');
  if($workEmail === '' && $phoneNo === '') bad('Email or phone is required');
  if($productInterest === '') bad('Please choose a product interest');
  if($preferredDate === '') bad('Preferred date is required');

  $ts = now_iso();
  $d = central_db();
  $st = $d->prepare("INSERT INTO public_enquiries (full_name,company_name,work_email,phone_no,team_size,product_interest,preferred_date,preferred_time,modules,message,source_page,status,admin_note,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
  $st->execute([$fullName,$companyName,$workEmail,$phoneNo,$teamSize,$productInterest,$preferredDate,$preferredTime,json_encode($modules, JSON_UNESCAPED_UNICODE),$message,$sourcePage,'New','',$ts,$ts]);
  $id = (int)$d->lastInsertId();
  $q = $d->prepare("SELECT * FROM public_enquiries WHERE id=? LIMIT 1");
  $q->execute([$id]);
  $row = $q->fetch();
  $payload = enquiry_row_payload($row ?: []);
  enquiry_send_emails($payload);
  return $payload;
}
function admin_enquiries_all(): array {
  require_super_admin();
  $rows = central_db()->query("SELECT * FROM public_enquiries ORDER BY id DESC")->fetchAll();
  return array_map('enquiry_row_payload', $rows ?: []);
}
function admin_enquiry_create(array $raw): array {
  require_super_admin();
  return public_enquiry_create($raw);
}
function admin_enquiry_update(int $id, array $raw): array {
  require_super_admin();
  if($id <= 0) bad('Invalid enquiry id');
  $d = central_db();
  $q = $d->prepare("SELECT * FROM public_enquiries WHERE id=? LIMIT 1");
  $q->execute([$id]);
  $row = $q->fetch();
  if(!$row) nf('Enquiry not found');

  $fullName = s($raw['fullName'] ?? ($row['full_name'] ?? ''));
  $companyName = s($raw['companyName'] ?? ($row['company_name'] ?? ''));
  $workEmail = s($raw['workEmail'] ?? ($row['work_email'] ?? ''));
  $phoneNo = s($raw['phoneNo'] ?? ($row['phone_no'] ?? ''));
  $teamSize = s($raw['teamSize'] ?? ($row['team_size'] ?? ''));
  $productInterest = s($raw['productInterest'] ?? ($row['product_interest'] ?? ''));
  $preferredDate = s($raw['preferredDate'] ?? ($row['preferred_date'] ?? ''));
  $preferredTime = s($raw['preferredTime'] ?? ($row['preferred_time'] ?? ''));
  $modules = array_key_exists('modules', $raw)
    ? enquiry_modules_norm($raw['modules'] ?? [])
    : enquiry_modules_norm(json_decode((string)($row['modules'] ?? '[]'), true));
  $message = s($raw['message'] ?? ($row['message'] ?? ''));
  $sourcePage = s($raw['sourcePage'] ?? ($row['source_page'] ?? 'landing'), 'landing');
  $status = enquiry_status_norm((string)($raw['status'] ?? ($row['status'] ?? 'New')));
  $adminNote = s($raw['adminNote'] ?? ($row['admin_note'] ?? ''));

  if($fullName === '') bad('Full name is required');
  if($companyName === '') bad('Company name is required');
  if($workEmail === '' && $phoneNo === '') bad('Email or phone is required');
  if($productInterest === '') bad('Please choose a product interest');
  if($preferredDate === '') bad('Preferred date is required');

  $ts = now_iso();
  $upd = $d->prepare("UPDATE public_enquiries SET full_name=?, company_name=?, work_email=?, phone_no=?, team_size=?, product_interest=?, preferred_date=?, preferred_time=?, modules=?, message=?, source_page=?, status=?, admin_note=?, updated_at=? WHERE id=?");
  $upd->execute([$fullName,$companyName,$workEmail,$phoneNo,$teamSize,$productInterest,$preferredDate,$preferredTime,json_encode($modules, JSON_UNESCAPED_UNICODE),$message,$sourcePage,$status,$adminNote,$ts,$id]);
  $q->execute([$id]);
  $fresh = $q->fetch();
  return enquiry_row_payload($fresh ?: []);
}
function admin_enquiry_delete(int $id): void {
  require_super_admin();
  if($id <= 0) bad('Invalid enquiry id');
  $d = central_db();
  $q = $d->prepare("DELETE FROM public_enquiries WHERE id=?");
  $q->execute([$id]);
  if($q->rowCount() <= 0) nf('Enquiry not found');
}
function advance_fetch_one(PDO $d, string $id): ?array {
  $q = $d->prepare("SELECT * FROM salary_advances WHERE id=? LIMIT 1");
  $q->execute([$id]);
  $r = $q->fetch();
  if(!$r) return null;
  $row = advance_row_payload($r);
  return $row + advance_calc_summary($d, $row);
}
function advance_schedule_rows(float $amount, string $repaymentType, int $emiMonths, int $startYear, int $startMonth): array {
  $rows = [];
  $periods = $repaymentType === 'emi' ? max(1, $emiMonths) : 1;
  $baseAmt = $periods > 0 ? round($amount / $periods, 2) : round($amount, 2);
  $acc = 0.0;
  for($i = 0; $i < $periods; $i++){
    $p = advance_next_period($startYear, $startMonth, $i);
    $scheduled = ($i === $periods - 1) ? round($amount - $acc, 2) : $baseAmt;
    $acc = round($acc + $scheduled, 2);
    $rows[] = ['year'=>$p['year'],'month'=>$p['month'],'scheduledAmount'=>$scheduled];
  }
  return $rows;
}
function advance_create(array $payload): array {
  $ctx = advance_manage_ctx();
  $clientId = req_client_id();
  $d = db();
  $empId = up($payload['empId'] ?? '');
  if($empId === '') bad('empId is required');
  $emp = null;
  foreach(employees_all() as $e){ if(up($e['id'] ?? '') === $empId){ $emp = $e; break; } }
  if(!$emp) nf('Employee not found');
  $amount = round(f($payload['amount'] ?? 0), 2);
  if($amount <= 0) bad('amount must be greater than 0');
  $disbursedOn = s($payload['disbursedOn'] ?? gmdate('Y-m-d'));
  if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $disbursedOn)) bad('disbursedOn must be YYYY-MM-DD');
  $ts = strtotime($disbursedOn.' 00:00:00 UTC');
  if($ts === false) bad('disbursedOn is invalid');
  $startYear = (int)gmdate('Y', $ts);
  $startMonth = (int)gmdate('n', $ts);
  $eligibility = advance_eligibility($empId, $disbursedOn);
  $remainingEligible = round(f($eligibility['remainingEligible'] ?? 0), 2);
  if($remainingEligible <= 0) bad('No eligible attendance-based advance is available for the selected employee and date');
  if($amount > $remainingEligible) bad('Advance amount cannot exceed the calculated salary on present attendance');
  $repaymentType = 'full';
  $emiMonths = 1;
  $id = 'ADV-'.preg_replace('/[^A-Z0-9]/', '', $empId).'-'.time();
  $emiAmount = round($amount, 2);
  $actor = auth_actor_name();
  $now = now_iso();
  $notes = s($payload['notes'] ?? '');
  $st = $d->prepare("INSERT INTO salary_advances (id,emp_id,employee_name,amount,repayment_type,emi_months,emi_amount,disbursed_on,start_year,start_month,attendance_year,attendance_month,attendance_through_date,present_days,eligible_salary,monthly_gross,notes,status,created_by,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
  $st->execute([$id,$empId,s($emp['name'] ?? $empId),$amount,$repaymentType,$emiMonths,$emiAmount,$disbursedOn,$startYear,$startMonth,(int)($eligibility['year'] ?? $startYear),(int)($eligibility['month'] ?? $startMonth),(string)($eligibility['date'] ?? $disbursedOn),round(f($eligibility['presentDays'] ?? 0),2),round(f($eligibility['eligibleSalary'] ?? 0),2),round(f($eligibility['monthlyGross'] ?? 0),2),$notes,'Active',$actor,$now,$now]);
  $sched = advance_schedule_rows($amount, $repaymentType, $emiMonths, $startYear, $startMonth);
  $sd = $d->prepare("INSERT INTO advance_deductions (advance_id,emp_id,deduction_year,deduction_month,scheduled_amount,deducted_amount,balance_after,payroll_period,payroll_sheet_id,status,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
  $balance = $amount;
  foreach($sched as $row){
    $balance = round(max(0.0, $balance - f($row['scheduledAmount'] ?? 0)), 2);
    $sd->execute([$id,$empId,(int)$row['year'],(int)$row['month'],round(f($row['scheduledAmount'] ?? 0),2),0.0,$amount,'','', 'Scheduled',$now,$now]);
  }
  invalidate_salary_dependent_sheets();
  $fresh = advance_fetch_one($d, $id) ?? ['id'=>$id];
  mail_advance_event($clientId, $fresh);
  return $fresh;
}
function advance_rows(array $ctx, bool $outstandingOnly = false): array {
  $d = db();
  $empScope = advance_emp_scope($ctx);
  $sql = "SELECT * FROM salary_advances";
  $params = [];
  if($empScope !== ''){
    $sql .= " WHERE emp_id=?";
    $params[] = $empScope;
  }
  $sql .= " ORDER BY disbursed_on DESC, created_at DESC";
  $q = $d->prepare($sql);
  $q->execute($params);
  $out = [];
  foreach($q->fetchAll() as $r){
    $row = advance_row_payload($r);
    $row += advance_calc_summary($d, $row);
    if($row['remainingBalance'] <= 0 && $row['status'] !== 'Closed'){
      $d->prepare("UPDATE salary_advances SET status='Closed', updated_at=? WHERE id=?")->execute([now_iso(), $row['id']]);
      $row['status'] = 'Closed';
    }
    if($outstandingOnly && $row['remainingBalance'] <= 0) continue;
    $out[] = $row;
  }
  return $out;
}
function advance_history_rows(array $ctx): array {
  $d = db();
  $empScope = advance_emp_scope($ctx);
  $sql = "SELECT d.*, a.employee_name, a.amount AS advance_amount, a.repayment_type FROM advance_deductions d JOIN salary_advances a ON a.id=d.advance_id";
  $params = [];
  if($empScope !== ''){
    $sql .= " WHERE d.emp_id=?";
    $params[] = $empScope;
  }
  $sql .= " ORDER BY d.deduction_year DESC, d.deduction_month DESC, d.id DESC";
  $q = $d->prepare($sql);
  $q->execute($params);
  $rows = [];
  foreach($q->fetchAll() as $r){
    $rows[] = [
      'id'=>(int)$r['id'],
      'advanceId'=>(string)$r['advance_id'],
      'empId'=>(string)$r['emp_id'],
      'employeeName'=>(string)$r['employee_name'],
      'period'=>advance_month_period((int)$r['deduction_year'], (int)$r['deduction_month']),
      'scheduledAmount'=>round(f($r['scheduled_amount'] ?? 0), 2),
      'deductedAmount'=>round(f($r['deducted_amount'] ?? 0), 2),
      'balanceAfter'=>round(f($r['balance_after'] ?? 0), 2),
      'status'=>(string)$r['status'],
      'payrollPeriod'=>(string)($r['payroll_period'] ?? ''),
      'advanceAmount'=>round(f($r['advance_amount'] ?? 0), 2),
      'repaymentType'=>(string)($r['repayment_type'] ?? 'full'),
      'updatedAt'=>(string)($r['updated_at'] ?? ''),
    ];
  }
  return $rows;
}
function advance_delete(string $id): void {
  advance_manage_ctx();
  $id = s($id);
  if($id === '') bad('Invalid advance id');
  $d = db();
  $row = advance_fetch_one($d, $id);
  if(!$row) nf('Advance not found');
  if(round(f($row['deductedAmount'] ?? 0), 2) > 0) bad('Advance cannot be deleted after payroll deduction has started');
  $d->prepare("DELETE FROM advance_deductions WHERE advance_id=?")->execute([$id]);
  $q = $d->prepare("DELETE FROM salary_advances WHERE id=?");
  $q->execute([$id]);
  if($q->rowCount() <= 0) nf('Advance not found');
  invalidate_salary_dependent_sheets();
}
function advance_payroll_apply(PDO $d, string $empId, int $month, int $year, float $maxAvailable, string $payrollSheetId = ''): array {
  $empId = up($empId);
  if($empId === '' || $maxAvailable <= 0) return ['amount'=>0.0,'items'=>[]];
  $q = $d->prepare("SELECT d.id, d.advance_id, d.scheduled_amount, a.amount AS advance_amount FROM advance_deductions d JOIN salary_advances a ON a.id=d.advance_id WHERE d.emp_id=? AND d.deduction_year=? AND d.deduction_month=? AND a.status IN ('Active','Closed') ORDER BY a.disbursed_on ASC, d.id ASC");
  $q->execute([$empId,$year,$month]);
  $rows = $q->fetchAll();
  if(!$rows) return ['amount'=>0.0,'items'=>[]];
  $sumPrev = $d->prepare("SELECT COALESCE(SUM(deducted_amount),0) AS deducted FROM advance_deductions WHERE advance_id=? AND ((deduction_year < ?) OR (deduction_year = ? AND deduction_month < ?))");
  $upd = $d->prepare("UPDATE advance_deductions SET deducted_amount=?, balance_after=?, payroll_period=?, payroll_sheet_id=?, status=?, updated_at=? WHERE id=?");
  $advanceUpd = $d->prepare("UPDATE salary_advances SET status=?, updated_at=? WHERE id=?");
  $left = round($maxAvailable, 2);
  $total = 0.0;
  $items = [];
  foreach($rows as $r){
    $advanceId = (string)$r['advance_id'];
    $sumPrev->execute([$advanceId,$year,$year,$month]);
    $prevDeducted = round(f(($sumPrev->fetch() ?: [])['deducted'] ?? 0), 2);
    $advanceAmount = round(f($r['advance_amount'] ?? 0), 2);
    $remainingBefore = round(max(0.0, $advanceAmount - $prevDeducted), 2);
    $scheduled = round(min($remainingBefore, f($r['scheduled_amount'] ?? 0)), 2);
    $deducted = round(min($left, $scheduled), 2);
    $balanceAfter = round(max(0.0, $remainingBefore - $deducted), 2);
    $status = $deducted <= 0 ? 'Scheduled' : ($balanceAfter <= 0 ? 'Deducted' : ($deducted < $scheduled ? 'Partial' : 'Deducted'));
    $upd->execute([$deducted,$balanceAfter,advance_month_period($year,$month),$payrollSheetId,$status,now_iso(),(int)$r['id']]);
    $advanceUpd->execute([$balanceAfter <= 0 ? 'Closed' : 'Active', now_iso(), $advanceId]);
    if($deducted > 0){
      $total = round($total + $deducted, 2);
      $left = round(max(0.0, $left - $deducted), 2);
      $items[] = ['advanceId'=>$advanceId,'amount'=>$deducted];
    }
  }
  return ['amount'=>$total,'items'=>$items];
}
function advance_outstanding_for_employee(PDO $d, string $empId, string $asOfDate = ''): array {
  $empId = up($empId);
  if($empId === '') return ['amount'=>0.0,'items'=>[]];
  $sql = "SELECT * FROM salary_advances WHERE emp_id=?";
  $params = [$empId];
  if($asOfDate !== ''){
    $sql .= " AND disbursed_on<=?";
    $params[] = $asOfDate;
  }
  $sql .= " ORDER BY disbursed_on ASC, created_at ASC";
  $q = $d->prepare($sql);
  $q->execute($params);
  $items = [];
  $total = 0.0;
  foreach($q->fetchAll() as $r){
    $row = advance_row_payload($r);
    $summary = advance_calc_summary($d, $row);
    $remaining = round(f($summary['remainingBalance'] ?? 0), 2);
    if($remaining <= 0) continue;
    $items[] = [
      'advanceId'=>(string)($row['id'] ?? ''),
      'disbursedOn'=>(string)($row['disbursedOn'] ?? ''),
      'remainingAmount'=>$remaining
    ];
    $total = round($total + $remaining, 2);
  }
  return ['amount'=>$total,'items'=>$items];
}
function incentive_employee_name(string $empId): string {
  $eid = up($empId);
  foreach(employees_all() as $emp){
    if(up($emp['id'] ?? '') === $eid) return s($emp['name'] ?? $eid, $eid);
  }
  return $eid;
}
function incentive_norm_row(array $r): array {
  return [
    'id'=>s($r['id'] ?? ''),
    'empId'=>up($r['emp_id'] ?? $r['empId'] ?? ''),
    'employeeName'=>s($r['employee_name'] ?? $r['employeeName'] ?? ''),
    'incentiveDate'=>s($r['incentive_date'] ?? $r['incentiveDate'] ?? $r['date'] ?? ''),
    'amount'=>round(max(0.0, f($r['amount'] ?? 0)), 2),
    'remarks'=>s($r['remarks'] ?? ''),
    'createdAt'=>s($r['created_at'] ?? $r['createdAt'] ?? ''),
    'updatedAt'=>s($r['updated_at'] ?? $r['updatedAt'] ?? '')
  ];
}
function incentive_fetch_one(PDO $d, string $id): ?array {
  $q = $d->prepare("SELECT * FROM incentives WHERE id=? LIMIT 1");
  $q->execute([$id]);
  $row = $q->fetch();
  return $row ? incentive_norm_row($row) : null;
}
function incentive_rows(array $query = []): array {
  $rows = db()->query("SELECT * FROM incentives ORDER BY incentive_date DESC, created_at DESC, id DESC")->fetchAll();
  $empId = up($query['empId'] ?? '');
  $month = (int)($query['month'] ?? 0);
  $year = (int)($query['year'] ?? 0);
  $from = s($query['from'] ?? '');
  $to = s($query['to'] ?? '');
  $out = [];
  foreach($rows as $row){
    $norm = incentive_norm_row($row);
    if($empId !== '' && $norm['empId'] !== $empId) continue;
    if($month > 0 || $year > 0){
      $ts = strtotime($norm['incentiveDate']);
      if($ts === false) continue;
      if($month > 0 && (int)gmdate('n', $ts) !== $month) continue;
      if($year > 0 && (int)gmdate('Y', $ts) !== $year) continue;
    }
    if($from !== '' && $norm['incentiveDate'] < $from) continue;
    if($to !== '' && $norm['incentiveDate'] > $to) continue;
    $out[] = $norm;
  }
  return $out;
}
function incentive_create(array $payload): array {
  $empId = up($payload['empId'] ?? '');
  if($empId === '') bad('empId is required');
  $dateRaw = s($payload['incentiveDate'] ?? ($payload['date'] ?? gmdate('Y-m-d')), gmdate('Y-m-d'));
  $ts = strtotime($dateRaw);
  if($ts === false) bad('Invalid incentive date');
  $date = gmdate('Y-m-d', $ts);
  $amount = round(max(0.0, f($payload['amount'] ?? 0)), 2);
  if($amount <= 0) bad('amount must be greater than 0');
  $remarks = s($payload['remarks'] ?? '');
  $id = 'INC-'.gmdate('YmdHis').'-'.substr(md5(uniqid((string)mt_rand(), true)), 0, 6);
  $name = incentive_employee_name($empId);
  $now = now_iso();
  $st = db()->prepare("INSERT INTO incentives (id,emp_id,employee_name,incentive_date,amount,remarks,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?)");
  $st->execute([$id,$empId,$name,$date,$amount,$remarks,$now,$now]);
  return incentive_fetch_one(db(), $id) ?? ['id'=>$id,'empId'=>$empId,'employeeName'=>$name,'incentiveDate'=>$date,'amount'=>$amount,'remarks'=>$remarks,'createdAt'=>$now,'updatedAt'=>$now];
}
function incentive_delete(string $id): void {
  $id = s($id);
  if($id === '') bad('Invalid incentive id');
  $q = db()->prepare("DELETE FROM incentives WHERE id=?");
  $q->execute([$id]);
  if($q->rowCount() === 0) nf('Incentive not found');
}
function incentive_clear(): void {
  db()->exec("DELETE FROM incentives");
}
function incentive_total_for_period(PDO $d, string $empId, int $month, int $year): float {
  $eid = up($empId);
  if($eid === '' || $month < 1 || $month > 12 || $year < 2000) return 0.0;
  $from = sprintf('%04d-%02d-01', $year, $month);
  $to = sprintf('%04d-%02d-%02d', $year, $month, (int)cal_days_in_month(CAL_GREGORIAN, $month, $year));
  $q = $d->prepare("SELECT COALESCE(SUM(amount),0) AS total FROM incentives WHERE emp_id=? AND incentive_date>=? AND incentive_date<=?");
  $q->execute([$eid,$from,$to]);
  $row = $q->fetch();
  return round(max(0.0, f($row['total'] ?? 0)), 2);
}
function incentive_total_till_date_for_month(PDO $d, string $empId, string $exitDate): float {
  $eid = up($empId);
  $exit = s($exitDate);
  if($eid === '' || $exit === '') return 0.0;
  $ts = strtotime($exit);
  if($ts === false) return 0.0;
  $from = gmdate('Y-m-01', $ts);
  $to = gmdate('Y-m-d', $ts);
  $q = $d->prepare("SELECT COALESCE(SUM(amount),0) AS total FROM incentives WHERE emp_id=? AND incentive_date>=? AND incentive_date<=?");
  $q->execute([$eid,$from,$to]);
  $row = $q->fetch();
  return round(max(0.0, f($row['total'] ?? 0)), 2);
}
function loan_permission_allowed(array $ctx): bool {
  $perm = $ctx['permissions'] ?? null;
  return !is_array($perm) || !array_key_exists('loan', $perm) || $perm['loan'] !== false;
}
function loan_view_ctx(): array {
  $ctx = auth_ctx(true) ?? [];
  $role = strtolower((string)($ctx['role'] ?? ''));
  if(in_array($role, ['employee'], true)) j(['detail'=>'Forbidden'],403);
  if(!loan_permission_allowed($ctx)) j(['detail'=>'Forbidden'],403);
  return $ctx;
}
function loan_manage_ctx(): array {
  $ctx = loan_view_ctx();
  $role = strtolower((string)($ctx['role'] ?? ''));
  if(in_array($role, ['employee'], true)) j(['detail'=>'Only admin/HR can manage loans'],403);
  return $ctx;
}
function loan_delete_ctx(): array {
  $ctx = loan_manage_ctx();
  $role = strtolower((string)($ctx['role'] ?? ''));
  if(!in_array($role, ['super_admin','client'], true)) j(['detail'=>'Only admin can delete loan entries'],403);
  return $ctx;
}
function loan_emp_snapshot(string $empId): array {
  $eid = up($empId);
  foreach(employees_all() as $emp){
    if(up($emp['id'] ?? '') !== $eid) continue;
    $propertyBranch = s($emp['address'] ?? '');
    return [
      'empId'=>$eid,
      'employeeName'=>s($emp['name'] ?? $eid, $eid),
      'dept'=>s($emp['dept'] ?? ''),
      'designation'=>s($emp['desig'] ?? ''),
      'propertyBranch'=>$propertyBranch
    ];
  }
  nf('Employee not found');
}
function loan_month_label(int $year, int $month): string {
  if($month < 1 || $month > 12 || $year < 2000) return '';
  return sprintf('%04d-%02d', $year, $month);
}
function loan_summary(PDO $d, string $loanId): array {
  $q = $d->prepare("SELECT COALESCE(SUM(deducted_amount),0) AS paid, MIN(CASE WHEN status<>'Deducted' AND balance_after>0 THEN (deduction_year*100 + deduction_month) ELSE NULL END) AS next_code FROM loan_deductions WHERE loan_id=?");
  $q->execute([$loanId]);
  $row = $q->fetch() ?: [];
  $paid = round(f($row['paid'] ?? 0), 2);
  $nextCode = (int)($row['next_code'] ?? 0);
  $nextMonth = '';
  if($nextCode > 0){
    $nextYear = intdiv($nextCode, 100);
    $nextMon = $nextCode % 100;
    $nextMonth = loan_month_label($nextYear, $nextMon);
  }
  return ['paidAmount'=>$paid, 'nextMonth'=>$nextMonth];
}
function loan_row_payload(array $r): array {
  $base = [
    'id'=>s($r['id'] ?? ''),
    'empId'=>up($r['emp_id'] ?? $r['empId'] ?? ''),
    'employeeName'=>s($r['employee_name'] ?? $r['employeeName'] ?? ''),
    'dept'=>s($r['dept'] ?? ''),
    'designation'=>s($r['designation'] ?? ''),
    'propertyBranch'=>s($r['property_branch'] ?? $r['propertyBranch'] ?? ''),
    'loanType'=>s($r['loan_type'] ?? $r['loanType'] ?? ''),
    'requestedAmount'=>round(f($r['requested_amount'] ?? $r['requestedAmount'] ?? 0), 2),
    'reason'=>s($r['reason'] ?? ''),
    'requestDate'=>s($r['request_date'] ?? $r['requestDate'] ?? ''),
    'requiredDate'=>s($r['required_date'] ?? $r['requiredDate'] ?? ''),
    'repaymentType'=>s($r['repayment_type'] ?? $r['repaymentType'] ?? 'one_time', 'one_time'),
    'emiStartYear'=>(int)($r['emi_start_year'] ?? $r['emiStartYear'] ?? 0),
    'emiStartMonth'=>(int)($r['emi_start_month'] ?? $r['emiStartMonth'] ?? 0),
    'emiStartPeriod'=>loan_month_label((int)($r['emi_start_year'] ?? $r['emiStartYear'] ?? 0), (int)($r['emi_start_month'] ?? $r['emiStartMonth'] ?? 0)),
    'emiAmount'=>round(f($r['emi_amount'] ?? $r['emiAmount'] ?? 0), 2),
    'installmentCount'=>(int)($r['installment_count'] ?? $r['installmentCount'] ?? 1),
    'remarks'=>s($r['remarks'] ?? ''),
    'status'=>s($r['status'] ?? 'Active', 'Active'),
    'createdBy'=>s($r['created_by'] ?? $r['createdBy'] ?? ''),
    'createdAt'=>s($r['created_at'] ?? $r['createdAt'] ?? ''),
    'updatedAt'=>s($r['updated_at'] ?? $r['updatedAt'] ?? '')
  ];
  $paidAmount = round(f($r['paid_amount'] ?? $r['paidAmount'] ?? 0), 2);
  $balanceAmount = round(max(0.0, $base['requestedAmount'] - $paidAmount), 2);
  return $base + [
    'paidAmount'=>$paidAmount,
    'balanceAmount'=>$balanceAmount
  ];
}
function loan_deduction_history_rows(PDO $d, string $loanId): array {
  $q = $d->prepare("SELECT * FROM loan_deductions WHERE loan_id=? ORDER BY deduction_year ASC, deduction_month ASC, id ASC");
  $q->execute([$loanId]);
  $rows = [];
  foreach($q->fetchAll() as $r){
    $rows[] = [
      'id'=>(int)($r['id'] ?? 0),
      'loanId'=>(string)($r['loan_id'] ?? ''),
      'empId'=>up($r['emp_id'] ?? ''),
      'year'=>(int)($r['deduction_year'] ?? 0),
      'month'=>(int)($r['deduction_month'] ?? 0),
      'period'=>loan_month_label((int)($r['deduction_year'] ?? 0), (int)($r['deduction_month'] ?? 0)),
      'scheduledAmount'=>round(f($r['scheduled_amount'] ?? 0), 2),
      'deductedAmount'=>round(f($r['deducted_amount'] ?? 0), 2),
      'balanceAfter'=>round(f($r['balance_after'] ?? 0), 2),
      'status'=>s($r['status'] ?? ''),
      'payrollPeriod'=>s($r['payroll_period'] ?? ''),
      'payrollSheetId'=>s($r['payroll_sheet_id'] ?? ''),
      'createdAt'=>s($r['created_at'] ?? ''),
      'updatedAt'=>s($r['updated_at'] ?? '')
    ];
  }
  return $rows;
}
function loan_fetch_one(PDO $d, string $id): ?array {
  $q = $d->prepare("SELECT * FROM loans WHERE id=? LIMIT 1");
  $q->execute([$id]);
  $row = $q->fetch();
  if(!$row) return null;
  $summary = loan_summary($d, (string)$row['id']);
  $payload = loan_row_payload($row + ['paid_amount'=>$summary['paidAmount']]);
  $history = loan_deduction_history_rows($d, (string)$row['id']);
  $status = $payload['balanceAmount'] <= 0 ? 'Closed' : ($summary['paidAmount'] > 0 ? 'Active' : $payload['status']);
  return $payload + ['status'=>$status, 'nextMonth'=>$summary['nextMonth'], 'historyRows'=>$history];
}
function loan_schedule_rows(float $amount, string $repaymentType, float $emiAmount, int $installments, int $startYear, int $startMonth): array {
  $type = strtolower(trim($repaymentType));
  if($type !== 'emi') $type = 'one_time';
  if($startYear < 2000 || $startMonth < 1 || $startMonth > 12) bad('Valid EMI start month is required');
  if($type === 'one_time'){
    return [['year'=>$startYear,'month'=>$startMonth,'scheduledAmount'=>round($amount, 2)]];
  }
  $installments = max(1, $installments);
  $emiAmount = round(max(0.0, $emiAmount), 2);
  if($emiAmount <= 0) bad('EMI amount must be greater than 0 for EMI repayment');
  $rows = [];
  $remaining = round($amount, 2);
  for($i = 0; $i < $installments && $remaining > 0; $i++){
    $p = advance_next_period($startYear, $startMonth, $i);
    $scheduled = round(min($emiAmount, $remaining), 2);
    $remaining = round(max(0.0, $remaining - $scheduled), 2);
    $rows[] = ['year'=>(int)$p['year'],'month'=>(int)$p['month'],'scheduledAmount'=>$scheduled];
  }
  while($remaining > 0){
    $i = count($rows);
    $p = advance_next_period($startYear, $startMonth, $i);
    $scheduled = round(min($emiAmount, $remaining), 2);
    $remaining = round(max(0.0, $remaining - $scheduled), 2);
    $rows[] = ['year'=>(int)$p['year'],'month'=>(int)$p['month'],'scheduledAmount'=>$scheduled];
  }
  return $rows;
}
function loan_create_or_update(array $payload, ?string $existingId = null): array {
  $ctx = loan_manage_ctx();
  $d = db();
  $isEdit = $existingId !== null && trim($existingId) !== '';
  $loanId = $isEdit ? s($existingId) : '';
  $existing = null;
  $lockedExisting = false;
  if($isEdit){
    $existing = loan_fetch_one($d, $loanId);
    if(!$existing) nf('Loan not found');
    $lockedExisting = array_reduce($existing['historyRows'] ?? [], fn($carry, $row) => $carry || f($row['deductedAmount'] ?? 0) > 0, false);
  }
  $empId = up($payload['empId'] ?? ($existing['empId'] ?? ''));
  if($empId === '') bad('empId is required');
  $snap = loan_emp_snapshot($empId);
  $loanType = s($payload['loanType'] ?? ($existing['loanType'] ?? ''));
  if($loanType === '') bad('loanType is required');
  $requestedAmount = round(f($payload['requestedAmount'] ?? ($existing['requestedAmount'] ?? 0)), 2);
  if($requestedAmount <= 0) bad('requestedAmount must be greater than 0');
  $reason = s($payload['reason'] ?? ($existing['reason'] ?? ''));
  if($reason === '') bad('reason is required');
  $requestDate = s($payload['requestDate'] ?? ($existing['requestDate'] ?? gmdate('Y-m-d')), gmdate('Y-m-d'));
  $requiredDate = s($payload['requiredDate'] ?? ($existing['requiredDate'] ?? ''), '');
  if($requiredDate === '') bad('requiredDate is required');
  $repaymentType = strtolower(s($payload['repaymentType'] ?? ($existing['repaymentType'] ?? 'one_time'), 'one_time'));
  if(!in_array($repaymentType, ['one_time','emi'], true)) bad('repaymentType must be one_time or emi');
  $emiStartPeriod = s($payload['emiStartPeriod'] ?? ($existing['emiStartPeriod'] ?? ''), '');
  $emiStartYear = (int)($payload['emiStartYear'] ?? ($existing['emiStartYear'] ?? 0));
  $emiStartMonth = (int)($payload['emiStartMonth'] ?? ($existing['emiStartMonth'] ?? 0));
  if($emiStartPeriod !== '' && preg_match('/^(\d{4})-(\d{2})$/', $emiStartPeriod, $m)){
    $emiStartYear = (int)$m[1];
    $emiStartMonth = (int)$m[2];
  }
  if($emiStartYear < 2000 || $emiStartMonth < 1 || $emiStartMonth > 12) bad('emi start month is required');
  $emiAmount = round(f($payload['emiAmount'] ?? ($existing['emiAmount'] ?? 0)), 2);
  $installmentCount = (int)($payload['installmentCount'] ?? ($existing['installmentCount'] ?? 1));
  if($repaymentType === 'one_time'){
    $installmentCount = 1;
    $emiAmount = $requestedAmount;
  } else {
    if($emiAmount <= 0) bad('emiAmount must be greater than 0');
    if($installmentCount <= 0){
      $installmentCount = (int)ceil($requestedAmount / $emiAmount);
    }
  }
  $remarks = s($payload['remarks'] ?? ($existing['remarks'] ?? ''));
  $status = s($payload['status'] ?? ($existing['status'] ?? 'Active'), 'Active');
  if($lockedExisting){
    if($requestedAmount !== round(f($existing['requestedAmount'] ?? 0), 2)) bad('Cannot change loan amount after deductions have started');
    if($repaymentType !== strtolower(s($existing['repaymentType'] ?? 'one_time'))) bad('Cannot change repayment type after deductions have started');
    if($emiAmount !== round(f($existing['emiAmount'] ?? 0), 2)) bad('Cannot change EMI amount after deductions have started');
    if($installmentCount !== (int)($existing['installmentCount'] ?? 1)) bad('Cannot change installment count after deductions have started');
    if($emiStartYear !== (int)($existing['emiStartYear'] ?? 0) || $emiStartMonth !== (int)($existing['emiStartMonth'] ?? 0)) bad('Cannot change EMI start month after deductions have started');
  }
  $id = $isEdit ? $loanId : 'LOAN-'.preg_replace('/[^A-Z0-9]/', '', $empId).'-'.time();
  $now = now_iso();
  $actor = auth_actor_name();
  if($isEdit){
    $st = $d->prepare("UPDATE loans SET emp_id=?, employee_name=?, dept=?, designation=?, property_branch=?, loan_type=?, requested_amount=?, reason=?, request_date=?, required_date=?, repayment_type=?, emi_start_year=?, emi_start_month=?, emi_amount=?, installment_count=?, remarks=?, status=?, updated_at=? WHERE id=?");
    $st->execute([$empId,$snap['employeeName'],$snap['dept'],$snap['designation'],$snap['propertyBranch'],$loanType,$requestedAmount,$reason,$requestDate,$requiredDate,$repaymentType,$emiStartYear,$emiStartMonth,$emiAmount,$installmentCount,$remarks,$status,$now,$id]);
    if(!$lockedExisting){
      $d->prepare("DELETE FROM loan_deductions WHERE loan_id=?")->execute([$id]);
    }
  } else {
    $st = $d->prepare("INSERT INTO loans (id,emp_id,employee_name,dept,designation,property_branch,loan_type,requested_amount,reason,request_date,required_date,repayment_type,emi_start_year,emi_start_month,emi_amount,installment_count,remarks,status,created_by,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $st->execute([$id,$empId,$snap['employeeName'],$snap['dept'],$snap['designation'],$snap['propertyBranch'],$loanType,$requestedAmount,$reason,$requestDate,$requiredDate,$repaymentType,$emiStartYear,$emiStartMonth,$emiAmount,$installmentCount,$remarks,$status,$actor,$now,$now]);
  }
  if(!$lockedExisting){
    $sched = loan_schedule_rows($requestedAmount, $repaymentType, $emiAmount, $installmentCount, $emiStartYear, $emiStartMonth);
    $sd = $d->prepare("INSERT INTO loan_deductions (loan_id,emp_id,deduction_year,deduction_month,scheduled_amount,deducted_amount,balance_after,payroll_period,payroll_sheet_id,status,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
    $remaining = $requestedAmount;
    foreach($sched as $row){
      $scheduled = round(f($row['scheduledAmount'] ?? 0), 2);
      $remaining = round(max(0.0, $remaining - $scheduled), 2);
      $sd->execute([$id,$empId,(int)$row['year'],(int)$row['month'],$scheduled,0.0,$requestedAmount,'','', 'Scheduled',$now,$now]);
    }
  }
  invalidate_salary_dependent_sheets();
  return loan_fetch_one($d, $id) ?? ['id'=>$id];
}
function loan_rows(): array {
  loan_view_ctx();
  $rows = db()->query("SELECT * FROM loans ORDER BY created_at DESC, id DESC")->fetchAll();
  $out = [];
  foreach($rows as $r){
    $summary = loan_summary(db(), (string)$r['id']);
    $payload = loan_row_payload($r + ['paid_amount'=>$summary['paidAmount']]);
    $payload['status'] = $payload['balanceAmount'] <= 0 ? 'Closed' : ($summary['paidAmount'] > 0 ? 'Active' : $payload['status']);
    $payload['nextMonth'] = $summary['nextMonth'];
    $out[] = $payload;
  }
  return $out;
}
function loan_delete(string $id): void {
  loan_delete_ctx();
  $d = db();
  $row = loan_fetch_one($d, $id);
  if(!$row) nf('Loan not found');
  foreach(($row['historyRows'] ?? []) as $hist){
    if(f($hist['deductedAmount'] ?? 0) > 0) bad('Cannot delete loan after deductions have started');
  }
  $d->prepare("DELETE FROM loan_deductions WHERE loan_id=?")->execute([$id]);
  $q = $d->prepare("DELETE FROM loans WHERE id=?");
  $q->execute([$id]);
  if($q->rowCount() <= 0) nf('Loan not found');
  invalidate_salary_dependent_sheets();
}
function loan_payroll_apply(PDO $d, string $empId, int $month, int $year, float $maxAvailable, string $payrollSheetId = ''): array {
  $empId = up($empId);
  if($empId === '' || $maxAvailable <= 0) return ['amount'=>0.0,'items'=>[]];
  $q = $d->prepare("SELECT d.id, d.loan_id, d.scheduled_amount, l.requested_amount FROM loan_deductions d JOIN loans l ON l.id=d.loan_id WHERE d.emp_id=? AND d.deduction_year=? AND d.deduction_month=? AND l.status IN ('Active','Closed') ORDER BY l.created_at ASC, d.id ASC");
  $q->execute([$empId,$year,$month]);
  $rows = $q->fetchAll();
  if(!$rows) return ['amount'=>0.0,'items'=>[]];
  $sumPrev = $d->prepare("SELECT COALESCE(SUM(deducted_amount),0) AS deducted FROM loan_deductions WHERE loan_id=? AND ((deduction_year < ?) OR (deduction_year = ? AND deduction_month < ?))");
  $upd = $d->prepare("UPDATE loan_deductions SET deducted_amount=?, balance_after=?, payroll_period=?, payroll_sheet_id=?, status=?, updated_at=? WHERE id=?");
  $loanUpd = $d->prepare("UPDATE loans SET status=?, updated_at=? WHERE id=?");
  $left = round($maxAvailable, 2);
  $total = 0.0;
  $items = [];
  foreach($rows as $r){
    $loanId = (string)$r['loan_id'];
    $sumPrev->execute([$loanId,$year,$year,$month]);
    $prevDeducted = round(f(($sumPrev->fetch() ?: [])['deducted'] ?? 0), 2);
    $loanAmount = round(f($r['requested_amount'] ?? 0), 2);
    $remainingBefore = round(max(0.0, $loanAmount - $prevDeducted), 2);
    $scheduled = round(min($remainingBefore, f($r['scheduled_amount'] ?? 0)), 2);
    $deducted = round(min($left, $scheduled), 2);
    $balanceAfter = round(max(0.0, $remainingBefore - $deducted), 2);
    $status = $deducted <= 0 ? 'Scheduled' : ($balanceAfter <= 0 ? 'Deducted' : ($deducted < $scheduled ? 'Partial' : 'Deducted'));
    $upd->execute([$deducted,$balanceAfter,loan_month_label($year,$month),$payrollSheetId,$status,now_iso(),(int)$r['id']]);
    $loanUpd->execute([$balanceAfter <= 0 ? 'Closed' : 'Active', now_iso(), $loanId]);
    if($deducted > 0){
      $total = round($total + $deducted, 2);
      $left = round(max(0.0, $left - $deducted), 2);
      $items[] = ['loanId'=>$loanId,'amount'=>$deducted];
    }
  }
  return ['amount'=>$total,'items'=>$items];
}
function loan_outstanding_for_employee(PDO $d, string $empId, string $asOfDate = ''): array {
  $empId = up($empId);
  if($empId === '') return ['amount'=>0.0,'items'=>[]];
  $sql = "SELECT * FROM loans WHERE emp_id=?";
  $params = [$empId];
  if($asOfDate !== ''){
    $sql .= " AND request_date<=?";
    $params[] = $asOfDate;
  }
  $sql .= " ORDER BY created_at ASC";
  $q = $d->prepare($sql);
  $q->execute($params);
  $items = [];
  $total = 0.0;
  foreach($q->fetchAll() as $r){
    $row = loan_fetch_one($d, (string)$r['id']);
    if(!$row) continue;
    $remaining = round(f($row['balanceAmount'] ?? 0), 2);
    if($remaining <= 0) continue;
    $items[] = ['loanId'=>(string)($row['id'] ?? ''), 'loanType'=>(string)($row['loanType'] ?? ''), 'remainingAmount'=>$remaining];
    $total = round($total + $remaining, 2);
  }
  return ['amount'=>$total,'items'=>$items];
}
function body(): array {
  $r = $GLOBALS['__hr_legacy_request_body'] ?? file_get_contents('php://input');
  if($r===false||trim((string)$r)==='') return [];
  $j=json_decode((string)$r,true);
  if(!is_array($j)) bad('Invalid JSON');
  return $j;
}
function kv_get(string $k,$d=null){
  if (function_exists('app') && app()->bound(\App\Services\Storage\SheetStorageService::class)) {
  $svc = app(\App\Services\Storage\SheetStorageService::class);
  if ($k === 'payroll_overrides') return $svc->payrollOverrides();
  if (preg_match('/^attendance_daily_(\d{4})-(\d{2})$/', $k, $m)) return $svc->attendanceDaily((int)$m[2], (int)$m[1]);
  if (str_ends_with($k, '_index')) {
    $prefix = str_replace('_index', '', $k);
    $sheetTypes = ['attendance_sheet','payroll_sheet','pf_sheet','pf_return_sheet','esic_sheet','esic_return_sheet','ecr_sheet','fnf_sheet','gratuity_sheet','bonus_sheet','payslip'];
    if (in_array($prefix, $sheetTypes, true)) {
      return $svc->index($prefix);
    }
  }
  if (preg_match('/^(attendance_sheet|payroll_sheet|pf_sheet|pf_return_sheet|esic_sheet|esic_return_sheet|ecr_sheet|fnf_sheet|gratuity_sheet|bonus_sheet|payslip)_(.+)$/', $k, $m)) {
    return $svc->get($m[1], $m[2]) ?? $d;
  }
  if (in_array($k, ['control_settings','company_profile'], true) && app()->bound(\App\Services\Storage\TenantSettingsService::class)) {
    return app(\App\Services\Storage\TenantSettingsService::class)->get($k, $d);
  }
  }
  $st=db()->prepare("SELECT value FROM app_kv WHERE key=?"); $st->execute([$k]); $r=$st->fetch(); if(!$r) return $d; $v=json_decode($r['value'],true); return ($v===null && $r['value']!=='null')?$d:$v;
}
function kv_set(string $k,$v): void {
  if (function_exists('app') && app()->bound(\App\Services\Storage\SheetStorageService::class)) {
  if ($k === 'payroll_overrides') { app(\App\Services\Storage\SheetStorageService::class)->setPayrollOverrides(is_array($v)?$v:[]); return; }
  if (preg_match('/^attendance_daily_(\d{4})-(\d{2})$/', $k, $m)) { app(\App\Services\Storage\SheetStorageService::class)->setAttendanceDaily((int)$m[2], (int)$m[1], is_array($v)?$v:[]); return; }
  if (in_array($k, ['control_settings','company_profile'], true) && app()->bound(\App\Services\Storage\TenantSettingsService::class)) {
    app(\App\Services\Storage\TenantSettingsService::class)->set($k, $v); return;
  }
  }
  $st=db()->prepare("INSERT INTO app_kv (key,value,updated_at) VALUES (?,?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value,updated_at=excluded.updated_at"); $st->execute([$k,json_encode($v,JSON_UNESCAPED_UNICODE),now_iso()]);
}
function kv_set_on(PDO $d, string $k, $v): void {
  $st=$d->prepare("INSERT INTO app_kv (key,value,updated_at) VALUES (?,?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value,updated_at=excluded.updated_at");
  $st->execute([$k,json_encode($v,JSON_UNESCAPED_UNICODE),now_iso()]);
}
function idx(string $k): array { $x=kv_get($k,[]); return is_array($x)?$x:[]; }
function period(int $m,int $y): string { return sprintf('%04d-%02d',$y,$m); }
function idkey(string $p,string $id): string { return $p.'_'.$id; }
function find_period(array $rows,int $m,int $y): ?array { $p=period($m,$y); foreach($rows as $r){ if(($r['period']??'')===$p) return $r; } return null; }
function get_sheet(string $k,string $msg): array {
  if (preg_match('/^(attendance_sheet|payroll_sheet|pf_sheet|pf_return_sheet|esic_sheet|esic_return_sheet|ecr_sheet|fnf_sheet|gratuity_sheet|bonus_sheet|payslip)_(.+)$/', $k, $m) && function_exists('app') && app()->bound(\App\Services\Storage\SheetStorageService::class)) {
    $x = app(\App\Services\Storage\SheetStorageService::class)->get($m[1], $m[2]);
    if(is_array($x)) return $x;
  }
  $x=kv_get($k,null); if(!is_array($x)) nf($msg); return $x;
}
function save_sheet(string $prefix,int $m,int $y,array $rows,array $extra=[]): array {
  if (function_exists('app') && app()->bound(\App\Services\Storage\SheetStorageService::class)) {
    return app(\App\Services\Storage\SheetStorageService::class)->save($prefix, $m, $y, $rows, $extra);
  }
  $id=period($m,$y).'-'.time(); $s=["id"=>$id,"month"=>$m,"year"=>$y,"period"=>period($m,$y),"generatedAt"=>now_iso(),"rowCount"=>count($rows),"rows"=>$rows]+$extra;
  kv_set(idkey($prefix,$id),$s); $ik=$prefix.'_index'; $ix=idx($ik); array_unshift($ix,["id"=>$id,"month"=>$m,"year"=>$y,"period"=>$s['period'],"generatedAt"=>$s['generatedAt'],"rowCount"=>count($rows)]+$extra); kv_set($ik,array_slice($ix,0,300)); return $s;
}
function del_sheet(string $prefix,string $id): void {
  if (function_exists('app') && app()->bound(\App\Services\Storage\SheetStorageService::class)) {
    app(\App\Services\Storage\SheetStorageService::class)->delete($prefix, $id); return;
  }
  db()->prepare("DELETE FROM app_kv WHERE key=?")->execute([idkey($prefix,$id)]); $ik=$prefix.'_index'; $x=array_values(array_filter(idx($ik),fn($r)=>((string)($r['id']??''))!==$id)); kv_set($ik,$x);
}
function clr_sheet(string $prefix): void {
  if (function_exists('app') && app()->bound(\App\Services\Storage\SheetStorageService::class)) {
    app(\App\Services\Storage\SheetStorageService::class)->clear($prefix); return;
  }
  $ik=$prefix.'_index'; foreach(idx($ik) as $r){ if(!empty($r['id'])) db()->prepare("DELETE FROM app_kv WHERE key=?")->execute([idkey($prefix,(string)$r['id'])]); } db()->prepare("DELETE FROM app_kv WHERE key=?")->execute([$ik]);
}
function esic_challan_idx_key(): string { return 'esic_return_challan_index'; }
function esic_challan_list(): array { return idx(esic_challan_idx_key()); }
function esic_challan_save_all(array $rows): void { kv_set(esic_challan_idx_key(), array_slice(array_values($rows), 0, 500)); }
function esic_challan_norm(array $raw): array {
  $month=(int)($raw['month']??0);
  $year=(int)($raw['year']??0);
  if($month<1||$month>12||$year<2000) bad('month/year required');
  $challanNo=s($raw['challanNo']??'');
  $paidDate=s($raw['paidDate']??'');
  $pdfDataUrl=s($raw['pdfDataUrl']??'');
  $amount=f($raw['amount']??0);
  if($challanNo===''||$paidDate===''||$amount<=0) bad('challanNo, paidDate and amount are required');
  if($pdfDataUrl==='' || stripos($pdfDataUrl, 'data:application/pdf')!==0) bad('Valid PDF data is required');
  return [
    'id'=>s($raw['id']??''),
    'month'=>$month,
    'year'=>$year,
    'period'=>period($month,$year),
    'challanNo'=>$challanNo,
    'paidDate'=>$paidDate,
    'amount'=>round($amount,2),
    'pdfDataUrl'=>$pdfDataUrl,
    'createdOn'=>s($raw['createdOn']??now_iso(), now_iso())
  ];
}
function esic_challan_create(array $raw): array {
  $n=esic_challan_norm($raw);
  $id = period($n['month'],$n['year']).'-'.time().'-'.substr(bin2hex(random_bytes(3)),0,6);
  $row = $n;
  $row['id'] = $id;
  $rows = esic_challan_list();
  array_unshift($rows, $row);
  esic_challan_save_all($rows);
  mail_challan_event('esic_challan', req_client_id(), $row, 'ESIC Challan');
  return $row;
}
function esic_challan_delete(string $id): void {
  $rows = esic_challan_list();
  $next = array_values(array_filter($rows, fn($r)=>((string)($r['id']??''))!==$id));
  if(count($next)===count($rows)) nf('ESIC challan not found');
  esic_challan_save_all($next);
}
function esic_challan_clear(): void { kv_set(esic_challan_idx_key(), []); }
function pf_challan_idx_key(): string { return 'pf_return_challan_index'; }
function pf_challan_list(): array { return idx(pf_challan_idx_key()); }
function pf_challan_save_all(array $rows): void { kv_set(pf_challan_idx_key(), array_slice(array_values($rows), 0, 500)); }
function pf_challan_norm(array $raw): array {
  $month=(int)($raw['month']??0);
  $year=(int)($raw['year']??0);
  if($month<1||$month>12||$year<2000) bad('month/year required');
  $challanNo=s($raw['challanNo']??'');
  $paidDate=s($raw['paidDate']??'');
  $pdfDataUrl=s($raw['pdfDataUrl']??'');
  $amount=f($raw['amount']??0);
  if($challanNo===''||$paidDate===''||$amount<=0) bad('challanNo, paidDate and amount are required');
  if($pdfDataUrl==='' || stripos($pdfDataUrl, 'data:application/pdf')!==0) bad('Valid PDF data is required');
  return [
    'id'=>s($raw['id']??''),
    'month'=>$month,
    'year'=>$year,
    'period'=>period($month,$year),
    'challanNo'=>$challanNo,
    'paidDate'=>$paidDate,
    'amount'=>round($amount,2),
    'pdfDataUrl'=>$pdfDataUrl,
    'createdOn'=>s($raw['createdOn']??now_iso(), now_iso())
  ];
}
function pf_challan_create(array $raw): array {
  $n=pf_challan_norm($raw);
  $id = period($n['month'],$n['year']).'-'.time().'-'.substr(bin2hex(random_bytes(3)),0,6);
  $row = $n;
  $row['id'] = $id;
  $rows = pf_challan_list();
  array_unshift($rows, $row);
  pf_challan_save_all($rows);
  mail_challan_event('pf_challan', req_client_id(), $row, 'PF Challan');
  return $row;
}
function pf_challan_delete(string $id): void {
  $rows = pf_challan_list();
  $next = array_values(array_filter($rows, fn($r)=>((string)($r['id']??''))!==$id));
  if(count($next)===count($rows)) nf('PF challan not found');
  pf_challan_save_all($next);
}
function pf_challan_clear(): void { kv_set(pf_challan_idx_key(), []); }
function compliance_challan_idx_key(): string { return 'compliance_challan_index'; }
function compliance_challan_list(): array { return idx(compliance_challan_idx_key()); }
function compliance_challan_save_all(array $rows): void { kv_set(compliance_challan_idx_key(), array_slice(array_values($rows), 0, 800)); }
function compliance_challan_norm(array $raw): array {
  $month=(int)($raw['month']??0);
  $year=(int)($raw['year']??0);
  if($month<1||$month>12||$year<2000) bad('month/year required');
  $type=s($raw['type']??'');
  $dueDate=s($raw['dueDate']??'');
  if($type===''||$dueDate==='') bad('type and dueDate are required');

  $status=s($raw['status']??'Pending', 'Pending');
  if(!in_array($status, ['Pending','In Progress','Completed'], true)) $status='Pending';

  $amount=round(f($raw['amount']??0),2);
  $notes=s($raw['notes']??'');
  $pdfDataUrl=s($raw['pdfDataUrl']??'');
  if($pdfDataUrl!=='' && stripos($pdfDataUrl, 'data:application/pdf')!==0) bad('Valid PDF data is required');

  $createdAt=s($raw['createdAt']??'', '');
  $updatedAt=s($raw['updatedAt']??'', '');
  if($createdAt==='') $createdAt=now_iso();
  if($updatedAt==='') $updatedAt=now_iso();

  return [
    'id'=>s($raw['id']??''),
    'month'=>$month,
    'year'=>$year,
    'period'=>period($month,$year),
    'type'=>$type,
    'dueDate'=>$dueDate,
    'status'=>$status,
    'amount'=>$amount,
    'notes'=>$notes,
    'pdfDataUrl'=>$pdfDataUrl,
    'createdAt'=>$createdAt,
    'updatedAt'=>$updatedAt
  ];
}
function compliance_challan_upsert(array $raw): array {
  $n=compliance_challan_norm($raw);
  $rows=compliance_challan_list();
  $id=s($n['id']??'');

  if($id===''){
    $id=period($n['month'],$n['year']).'-'.time().'-'.substr(bin2hex(random_bytes(3)),0,6);
  }

  $existing = null;
  foreach($rows as $r){
    if((string)($r['id']??'')===$id){
      $existing = $r;
      break;
    }
  }

  $row = $n;
  $row['id'] = $id;
  $row['createdAt'] = (string)($existing['createdAt'] ?? $row['createdAt'] ?? now_iso());
  $row['updatedAt'] = now_iso();

  $next = [];
  $updated = false;
  foreach($rows as $r){
    if((string)($r['id']??'') === $id){
      $next[] = $row;
      $updated = true;
      continue;
    }
    $next[] = $r;
  }
  if(!$updated){
    array_unshift($next, $row);
  }
  usort($next, fn($a,$b)=>strcmp((string)($b['updatedAt']??''),(string)($a['updatedAt']??'')));
  compliance_challan_save_all($next);
  mail_challan_event('compliance_challan', req_client_id(), $row, 'Compliance Challan');
  return $row;
}
function compliance_challan_delete(string $id): void {
  $rows = compliance_challan_list();
  $next = array_values(array_filter($rows, fn($r)=>((string)($r['id']??''))!==$id));
  if(count($next)===count($rows)) nf('Compliance challan not found');
  compliance_challan_save_all($next);
}
function compliance_challan_clear(): void { kv_set(compliance_challan_idx_key(), []); }
function invalidate_salary_dependent_sheets(): void {
  // Generated outputs that depend on employee salary/employee master data.
  foreach (['payroll_sheet','pf_sheet','esic_sheet','ecr_sheet','payslip'] as $prefix) {
    clr_sheet($prefix);
  }
  // Payroll overrides are also salary-sensitive.
  ovr_set([]);
}

function norm_emp(array $r): array { $id=up($r['id']??''); $name=s($r['name']??''); if($id===''||$name==='') bad('Employee id and name are required'); return ["id"=>$id,"name"=>$name,"status"=>s($r['status']??'Active','Active'),"dept"=>s($r['dept']??''),"desig"=>s($r['desig']??''),"type"=>s($r['type']??'Full-time','Full-time'),"mobile"=>s($r['mobile']??''),"email"=>s($r['email']??''),"doj"=>s($r['doj']??''),"pf"=>s($r['pf']??'Yes','Yes'),"uan"=>s($r['uan']??''),"esi"=>s($r['esi']??'Yes','Yes'),"esiNo"=>s($r['esiNo']??''),"pfNo"=>s($r['pfNo']??''),"bankName"=>s($r['bankName']??''),"bankAc"=>s($r['bankAc']??''),"ifsc"=>s($r['ifsc']??''),"aadharNo"=>s($r['aadharNo']??''),"panCard"=>s($r['panCard']??''),"address"=>s($r['address']??''),"baseCtc"=>f($r['baseCtc']??0)]; }
function norm_leave(array $r): array { $x=["empId"=>up($r['empId']??''),"empName"=>s($r['empName']??''),"fromDate"=>s($r['fromDate']??''),"toDate"=>s($r['toDate']??''),"leaveType"=>up($r['leaveType']??''),"reason"=>s($r['reason']??''),"days"=>f($r['days']??0),"dept"=>s($r['dept']??''),"desig"=>s($r['desig']??''),"company"=>s($r['company']??''),"status"=>s($r['status']??'Approved','Approved'),"halfDay"=>s($r['halfDay']??'No','No'),"markedBy"=>s($r['markedBy']??'Client HR','Client HR'),"id"=>$r['id']??null]; if($x['empId']===''||$x['empName']===''||$x['fromDate']===''||$x['toDate']===''||$x['reason']===''||$x['days']<=0) bad('Invalid leave data'); if(!in_array($x['leaveType'],['CL','SL','EL','LOP'],true)) bad('leaveType must be CL/SL/EL/LOP'); return $x; }
function employees_all(): array {
  $rows=db()->query("SELECT * FROM employees ORDER BY id ASC")->fetchAll();
  return array_map(fn($r)=>["id"=>$r['id'],"name"=>$r['name'],"status"=>$r['status'],"dept"=>$r['dept'],"desig"=>$r['desig'],"type"=>$r['type'],"mobile"=>$r['mobile'],"email"=>$r['email'],"doj"=>$r['doj'],"pf"=>$r['pf'],"uan"=>$r['uan'],"esi"=>$r['esi'],"esiNo"=>$r['esi_no'],"pfNo"=>$r['pf_no'],"bankName"=>$r['bank_name'],"bankAc"=>$r['bank_ac'],"ifsc"=>$r['ifsc'],"aadharNo"=>$r['aadhar_no'],"panCard"=>$r['pan_card'],"address"=>$r['address'],"baseCtc"=>(float)($r['base_ctc'] ?? 0),"__updatedAt"=>$r['updated_at']],$rows);
}
function employee_is_active(array $emp): bool {
  return strtolower(trim((string)($emp['status'] ?? 'active'))) !== 'inactive';
}
function employees_active_all(): array {
  return array_values(array_filter(employees_all(), 'employee_is_active'));
}
function emp_upsert(array $raw, ?bool $mustExist=null): array {
  $n=norm_emp($raw); $q=db()->prepare("SELECT id, base_ctc FROM employees WHERE id=?"); $q->execute([$n['id']]); $old=$q->fetch(); $exists=(bool)$old;
  if($mustExist===true && !$exists) nf('Employee not found');
  if($mustExist===false && $exists) j(['detail'=>'Employee id already exists'],409);
  $ts=now_iso();
  $st=db()->prepare("INSERT INTO employees (id,name,status,dept,desig,type,mobile,email,doj,pf,uan,esi,esi_no,pf_no,bank_name,bank_ac,ifsc,aadhar_no,pan_card,address,base_ctc,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON CONFLICT(id) DO UPDATE SET name=excluded.name,status=excluded.status,dept=excluded.dept,desig=excluded.desig,type=excluded.type,mobile=excluded.mobile,email=excluded.email,doj=excluded.doj,pf=excluded.pf,uan=excluded.uan,esi=excluded.esi,esi_no=excluded.esi_no,pf_no=excluded.pf_no,bank_name=excluded.bank_name,bank_ac=excluded.bank_ac,ifsc=excluded.ifsc,aadhar_no=excluded.aadhar_no,pan_card=excluded.pan_card,address=excluded.address,base_ctc=excluded.base_ctc,updated_at=excluded.updated_at");
  $st->execute([$n['id'],$n['name'],$n['status'],$n['dept'],$n['desig'],$n['type'],$n['mobile'],$n['email'],$n['doj'],$n['pf'],$n['uan'],$n['esi'],$n['esiNo'],$n['pfNo'],$n['bankName'],$n['bankAc'],$n['ifsc'],$n['aadharNo'],$n['panCard'],$n['address'],$n['baseCtc'],$ts,$ts]);
  $oldBase = $exists ? f($old['base_ctc'] ?? 0) : null;
  if(!$exists || $oldBase === null || abs($oldBase - f($n['baseCtc'])) > 0.0001){
    invalidate_salary_dependent_sheets();
  }
  $row = $n+["__updatedAt"=>$ts];
  mail_employee_event(req_client_id(), (string)$n['id'], $row, !$exists);
  return $row;
}
function emp_delete(string $id): void {
  $st=db()->prepare("DELETE FROM employees WHERE id=?");
  $st->execute([up($id)]);
  if($st->rowCount()===0) nf('Employee not found');
  invalidate_salary_dependent_sheets();
}

function leaves_list(?int $m=null, ?int $y=null, ?string $lt=null, ?string $stt=null): array {
  $sql="SELECT * FROM leaves WHERE 1=1"; $args=[];
  if($m!==null){$sql.=" AND CAST(strftime('%m', from_date) AS INTEGER)=?"; $args[]=$m;}
  if($y!==null){$sql.=" AND CAST(strftime('%Y', from_date) AS INTEGER)=?"; $args[]=$y;}
  if($lt!==null && $lt!==''){$sql.=" AND leave_type=?"; $args[]=up($lt);} if($stt!==null && $stt!==''){$sql.=" AND status=?"; $args[]=$stt;}
  $sql.=" ORDER BY from_date DESC,id DESC"; $q=db()->prepare($sql); $q->execute($args);
  return array_map(fn($r)=>["id"=>(int)$r['id'],"empId"=>$r['emp_id'],"empName"=>$r['emp_name'],"dept"=>$r['dept'],"desig"=>$r['desig'],"company"=>$r['company'],"fromDate"=>$r['from_date'],"toDate"=>$r['to_date'],"days"=>(float)$r['days'],"leaveType"=>$r['leave_type'],"reason"=>$r['reason'],"status"=>$r['status'],"halfDay"=>$r['half_day'],"markedBy"=>$r['marked_by'],"__updatedAt"=>$r['updated_at']],$q->fetchAll());
}
function leave_upsert(array $raw, ?bool $mustExist=null): array {
  $n=norm_leave($raw); $id=$n['id']!==null?(int)$n['id']:null; if($mustExist===true && $id===null) bad('leave id required');
  $isNew = $id === null;
  if($id!==null){$q=db()->prepare("SELECT id FROM leaves WHERE id=?");$q->execute([$id]); if($mustExist===true && !$q->fetch()) nf('Leave not found');}
  $ts=now_iso();
  if($id===null){
    $st=db()->prepare("INSERT INTO leaves (emp_id,emp_name,dept,desig,company,from_date,to_date,days,leave_type,reason,status,half_day,marked_by,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $st->execute([$n['empId'],$n['empName'],$n['dept'],$n['desig'],$n['company'],$n['fromDate'],$n['toDate'],$n['days'],$n['leaveType'],$n['reason'],$n['status'],$n['halfDay'],$n['markedBy'],$ts,$ts]); $id=(int)db()->lastInsertId();
  } else {
    $st=db()->prepare("UPDATE leaves SET emp_id=?,emp_name=?,dept=?,desig=?,company=?,from_date=?,to_date=?,days=?,leave_type=?,reason=?,status=?,half_day=?,marked_by=?,updated_at=? WHERE id=?");
    $st->execute([$n['empId'],$n['empName'],$n['dept'],$n['desig'],$n['company'],$n['fromDate'],$n['toDate'],$n['days'],$n['leaveType'],$n['reason'],$n['status'],$n['halfDay'],$n['markedBy'],$ts,$id]);
  }
  $n['id']=$id; $n['__updatedAt']=$ts;
  mail_leave_event(req_client_id(), (string)$id, $n, $isNew);
  return $n;
}
function attendance_unmark_leave(string $empId,string $fromDate,string $toDate,string $leaveType): void {
  $lt = up($leaveType);
  if(!in_array($lt, ['CL','SL','EL','LOP'], true)) return;
  $eid = up($empId);
  if($eid === '') return;
  $start = strtotime($fromDate);
  $end = strtotime($toDate);
  if($start === false || $end === false) return;
  if($end < $start){ $tmp = $start; $start = $end; $end = $tmp; }

  $maps = [];
  for($ts = $start; $ts <= $end; $ts += 86400){
    $y = (int)gmdate('Y', $ts);
    $m = (int)gmdate('n', $ts);
    $d = gmdate('Y-m-d', $ts);
    $monthKey = att_month_key($m, $y);
    if(!array_key_exists($monthKey, $maps)){
      $map = kv_get($monthKey, []);
      $maps[$monthKey] = is_array($map) ? $map : [];
    }
    $dayKey = $eid.'|'.$d;
    if(array_key_exists($dayKey, $maps[$monthKey])) unset($maps[$monthKey][$dayKey]);
  }
  foreach($maps as $k => $v) kv_set($k, $v);
}
function leave_delete(int $id): void {
  $q = db()->prepare("SELECT emp_id, from_date, to_date, leave_type FROM leaves WHERE id=?");
  $q->execute([$id]);
  $row = $q->fetch();
  if(!$row) nf('Leave not found');

  $st = db()->prepare("DELETE FROM leaves WHERE id=?");
  $st->execute([$id]);
  attendance_unmark_leave((string)$row['emp_id'], (string)$row['from_date'], (string)$row['to_date'], (string)$row['leave_type']);
}
function leaves_summary(int $m,int $y): array {
  $q=db()->prepare("SELECT emp_id AS empId, emp_name AS empName, SUM(CASE WHEN leave_type='CL' THEN days ELSE 0 END) AS clDays, SUM(CASE WHEN leave_type='SL' THEN days ELSE 0 END) AS slDays, SUM(CASE WHEN leave_type='EL' THEN days ELSE 0 END) AS elDays, SUM(CASE WHEN leave_type='LOP' THEN days ELSE 0 END) AS lopDays, SUM(days) AS totalDays FROM leaves WHERE CAST(strftime('%m', from_date) AS INTEGER)=? AND CAST(strftime('%Y', from_date) AS INTEGER)=? AND status='Approved' GROUP BY emp_id, emp_name ORDER BY emp_id ASC");
  $q->execute([$m,$y]); return $q->fetchAll();
}

function control_get(): array {
  $x = kv_get('control_settings', null);
  if(!is_array($x)) return ["__lastSaved"=>null, "__configured"=>false];
  $u = null;
  if (function_exists('app') && app()->bound(\App\Services\Storage\TenantSettingsService::class)) {
    $u = app(\App\Services\Storage\TenantSettingsService::class)->updatedAt('control_settings');
  }
  if(!$u){
    $st=db()->prepare("SELECT updated_at FROM tenant_settings WHERE key=?");
    $st->execute(['control_settings']);
    $u = $st->fetchColumn() ?: null;
  }
  return array_merge($x, ["__lastSaved"=>$u, "__configured"=>true]);
}
function control_put(array $p): array { kv_set('control_settings',$p); return array_merge(DEFAULT_CONTROL,$p,["__lastSaved"=>now_iso()]); }
function profile_get(): array {
  $x=kv_get('company_profile',null);
  if(!is_array($x)){
    $cid = req_client_id();
    if($cid > 0){
      $q = central_db()->prepare("SELECT company_name, company_address, company_reg_no, company_pan, company_tan, company_gstin, company_contact_no FROM clients WHERE id=?");
      $q->execute([$cid]);
      $c = $q->fetch();
      if($c){
        return array_merge(DEFAULT_PROFILE, [
          "companyName" => (string)$c['company_name'],
          "companyAddress" => (string)$c['company_address'],
          "regNo" => (string)$c['company_reg_no'],
          "pan" => (string)$c['company_pan'],
          "tan" => (string)$c['company_tan'],
          "gstin" => (string)$c['company_gstin'],
          "contactNo" => (string)$c['company_contact_no']
        ], ["__lastSaved"=>null]);
      }
    }
    return DEFAULT_PROFILE+["__lastSaved"=>null];
  }
  $u=db()->query("SELECT updated_at FROM app_kv WHERE key='company_profile'")->fetchColumn() ?: null;
  return array_merge(DEFAULT_PROFILE,$x,["__lastSaved"=>$u]);
}
function profile_put(array $p): array { kv_set('company_profile',$p); return array_merge(DEFAULT_PROFILE,$p,["__lastSaved"=>now_iso()]); }
function smtp_settings_default(): array {
  $cfg = hr_mail_config();
  return [
    'enabled' => (bool)($cfg['enabled'] ?? false),
    'host' => (string)($cfg['host'] ?? ''),
    'port' => (int)($cfg['port'] ?? 465),
    'encryption' => (string)($cfg['encryption'] ?? 'ssl'),
    'username' => (string)($cfg['username'] ?? ''),
    'password' => '',
    'fromEmail' => (string)($cfg['from_email'] ?? ''),
    'fromName' => (string)($cfg['from_name'] ?? 'HR Seva'),
    'replyTo' => (string)($cfg['reply_to'] ?? ''),
    'adminEmails' => (string)($cfg['admin_emails'] ?? ''),
    'hasPassword' => s((string)($cfg['password'] ?? '')) !== '',
    '__source' => 'effective',
    '__lastSaved' => null
  ];
}
function smtp_settings_get(): array {
  require_super_admin();
  $base = smtp_settings_default();
  $st = central_db()->prepare("SELECT value, updated_at FROM app_kv WHERE key=? LIMIT 1");
  $st->execute(['smtp_settings']);
  $row = $st->fetch();
  if(!$row) return $base;
  $val = json_decode((string)($row['value'] ?? ''), true);
  $stored = is_array($val) ? $val : [];
  return [
    'enabled' => b($stored['HR_SMTP_ENABLED'] ?? ($base['enabled'] ? 'true' : 'false')),
    'host' => s($stored['HR_SMTP_HOST'] ?? $base['host']),
    'port' => (int)($stored['HR_SMTP_PORT'] ?? $base['port']),
    'encryption' => s($stored['HR_SMTP_ENCRYPTION'] ?? $base['encryption'], 'ssl'),
    'username' => s($stored['HR_SMTP_USERNAME'] ?? $base['username']),
    'password' => '',
    'fromEmail' => s($stored['HR_SMTP_FROM_EMAIL'] ?? $base['fromEmail']),
    'fromName' => s($stored['HR_SMTP_FROM_NAME'] ?? $base['fromName'], 'HR Seva'),
    'replyTo' => s($stored['HR_SMTP_REPLY_TO'] ?? $base['replyTo']),
    'adminEmails' => s($stored['HR_SMTP_ADMIN_EMAILS'] ?? $base['adminEmails']),
    'hasPassword' => s($stored['HR_SMTP_PASSWORD'] ?? '') !== '' || (bool)$base['hasPassword'],
    '__source' => 'db',
    '__lastSaved' => (string)($row['updated_at'] ?? '')
  ];
}
function smtp_settings_put(array $raw): array {
  require_super_admin();
  $password = s($raw['password'] ?? '');
  $username = s($raw['username'] ?? '');
  $fromEmail = s($raw['fromEmail'] ?? '');
  if($username !== '' && $fromEmail !== '' && strtolower($username) !== strtolower($fromEmail)){
    $fromEmail = $username;
  } elseif($username !== '' && $fromEmail === ''){
    $fromEmail = $username;
  }
  $next = [
    'HR_SMTP_ENABLED' => b($raw['enabled'] ?? false) ? 'true' : 'false',
    'HR_SMTP_HOST' => s($raw['host'] ?? ''),
    'HR_SMTP_PORT' => (string)((int)($raw['port'] ?? 465)),
    'HR_SMTP_ENCRYPTION' => strtolower(s($raw['encryption'] ?? 'ssl', 'ssl')),
    'HR_SMTP_USERNAME' => $username,
    'HR_SMTP_PASSWORD' => $password !== '' ? $password : s(kv_get_on(central_db(), 'smtp_settings', [])['HR_SMTP_PASSWORD'] ?? ''),
    'HR_SMTP_FROM_EMAIL' => $fromEmail,
    'HR_SMTP_FROM_NAME' => s($raw['fromName'] ?? 'HR Seva', 'HR Seva'),
    'HR_SMTP_REPLY_TO' => s($raw['replyTo'] ?? ''),
    'HR_SMTP_ADMIN_EMAILS' => s($raw['adminEmails'] ?? '')
  ];
  if($next['HR_SMTP_ENABLED'] === 'true'){
    if($next['HR_SMTP_HOST'] === '') bad('SMTP host is required');
    if((int)$next['HR_SMTP_PORT'] <= 0) bad('SMTP port is required');
    if($next['HR_SMTP_USERNAME'] === '') bad('SMTP username is required');
    if($next['HR_SMTP_PASSWORD'] === '') bad('SMTP password is required');
    if($next['HR_SMTP_FROM_EMAIL'] === '') bad('From email is required');
  }
  kv_set_on(central_db(), 'smtp_settings', $next);
  return smtp_settings_get();
}
function kv_get_on(PDO $d, string $k, $default = null) {
  $st = $d->prepare("SELECT value FROM app_kv WHERE key=?");
  $st->execute([$k]);
  $r = $st->fetch();
  if(!$r) return $default;
  $v = json_decode((string)$r['value'], true);
  return ($v === null && (string)$r['value'] !== 'null') ? $default : $v;
}
function smtp_test_send(array $raw): array {
  require_super_admin();
  $to = s($raw['email'] ?? '');
  if($to === '') bad('Test email is required');
  if(!hr_mail_is_valid_email($to)) bad('Enter a valid email');
  $subject = 'HR Seva SMTP Test Email';
  $html = '<div style="font-family:Arial,sans-serif;color:#1f2937;line-height:1.6;">'
    . '<h2 style="margin:0 0 12px 0;color:#16404b;">SMTP Test Successful</h2>'
    . '<p style="margin:0 0 12px 0;">This test email was sent from the HR Seva SMTP Control module.</p>'
    . '<p style="margin:0;">If you received this email, your Hostinger SMTP configuration is working.</p>'
    . '</div>';
  $res = hr_mail_send([$to], $subject, $html);
  email_log_write('smtp_test', 'smtp_test', 0, $to, $subject, (bool)($res['ok'] ?? false), (string)($res['error'] ?? ''));
  if(empty($res['ok'])) bad((string)($res['error'] ?? 'Failed to send test email'));
  return ['ok' => true, 'message' => 'Test email sent successfully'];
}
function norm_client(array $r, bool $isUpdate=false): array {
  $name = s($r['companyName'] ?? '');
  if($name === '') bad('Company Name is required');
  $userId = strtolower(s($r['userId'] ?? ''));
  $userPassword = s($r['userPassword'] ?? '');
  if($userId === '') bad('User ID is required');
  if(!$isUpdate && $userPassword === '') bad('Password is required');
  return [
    "id" => isset($r['id']) ? (int)$r['id'] : null,
    "companyName" => $name,
    "companyAddress" => s($r['companyAddress'] ?? ''),
    "companyRegNo" => s($r['companyRegNo'] ?? ''),
    "companyPAN" => up($r['companyPAN'] ?? ''),
    "companyTAN" => up($r['companyTAN'] ?? ''),
    "companyGSTIN" => up($r['companyGSTIN'] ?? ''),
    "companyContactNo" => s($r['companyContactNo'] ?? ''),
    "companyEmail" => strtolower(s($r['companyEmail'] ?? '')),
    "userId" => $userId,
    "userPassword" => $userPassword,
    "accessType" => strtolower(s($r['accessType'] ?? '', '')),
    "subscriptionPlanId" => (int)($r['subscriptionPlanId'] ?? 0)
  ];
}
function clients_all(): array {
  $rows = central_db()->query("SELECT c.*, COALESCE(a.access_type, 'custom') AS access_type, COALESCE(p.plan_name, '') AS subscription_type_name FROM clients c LEFT JOIN client_access a ON a.client_id=c.id LEFT JOIN subscription_plans p ON p.id=c.subscription_plan_id ORDER BY c.id DESC")->fetchAll();
  return array_map(fn($r) => [
    "id" => (int)$r['id'],
    "companyName" => (string)$r['company_name'],
    "companyAddress" => (string)$r['company_address'],
    "companyRegNo" => (string)$r['company_reg_no'],
    "companyPAN" => (string)$r['company_pan'],
    "companyTAN" => (string)$r['company_tan'],
    "companyGSTIN" => (string)$r['company_gstin'],
    "companyContactNo" => (string)$r['company_contact_no'],
    "companyEmail" => (string)($r['company_email'] ?? ''),
    "userId" => (string)($r['user_id'] ?? ''),
    "accessType" => (string)($r['access_type'] ?? 'custom'),
    "subscriptionPlanId" => (int)($r['subscription_plan_id'] ?? 0),
    "subscriptionTypeName" => (string)($r['subscription_type_name'] ?? ''),
    "__updatedAt" => (string)$r['updated_at']
  ], $rows);
}
function client_upsert(array $raw, ?bool $mustExist=null): array {
  $n = norm_client($raw, $mustExist === true);
  $id = $n['id'];
  $d = central_db();
  $isNew = !($id && $id > 0);
  if($mustExist === true && (!$id || $id <= 0)) bad('Client id is required');
  if($id && $id > 0){
    $q = $d->prepare("SELECT id FROM clients WHERE id=?");
    $q->execute([$id]);
    $exists = (bool)$q->fetch();
    if($mustExist === true && !$exists) nf('Client not found');
  }
  $du = $d->prepare("SELECT id FROM clients WHERE lower(user_id)=? AND id<>?");
  $du->execute([$n['userId'], (int)($id ?? 0)]);
  if($du->fetch()) j(['detail'=>'User ID already exists'],409);
  $ts = now_iso();
  $pwdHash = $n['userPassword'] !== '' ? password_hash($n['userPassword'], PASSWORD_DEFAULT) : '';
  $effectiveAccessType = $n['accessType'];
  if($n['subscriptionPlanId'] > 0){
    $qp = $d->prepare("SELECT access_type_code FROM subscription_plans WHERE id=?");
    $qp->execute([$n['subscriptionPlanId']]);
    $pr = $qp->fetch();
    if(!$pr) bad('Invalid subscriptionPlanId');
    $effectiveAccessType = strtolower(s($pr['access_type_code'] ?? 'full_access', 'full_access'));
  }
  $syncTenant = function(int $clientId) use ($n): void {
    if($clientId <= 0) return;
    $td = db_open(db_path_for_client($clientId));
    init_schema($td);
    $existingProfile = null;
    $pst = $td->prepare("SELECT value FROM tenant_settings WHERE key='company_profile'");
    $pst->execute();
    $pr = $pst->fetch();
    if($pr && isset($pr['value'])){
      $decoded = json_decode((string)$pr['value'], true);
      if(is_array($decoded)) $existingProfile = $decoded;
    }
    $existingControl = null;
    $cst = $td->prepare("SELECT value FROM tenant_settings WHERE key='control_settings'");
    $cst->execute();
    $cr = $cst->fetch();
    if($cr && isset($cr['value'])){
      $decoded = json_decode((string)$cr['value'], true);
      if(is_array($decoded)) $existingControl = $decoded;
    }
    $profile = is_array($existingProfile) ? $existingProfile : [];
    $control = is_array($existingControl) ? $existingControl : [];
    $profile['companyName'] = $n['companyName'];
    $profile['companyAddress'] = $n['companyAddress'];
    $profile['regNo'] = $n['companyRegNo'];
    $profile['pan'] = $n['companyPAN'];
    $profile['tan'] = $n['companyTAN'];
    $profile['gstin'] = $n['companyGSTIN'];
    $profile['contactNo'] = $n['companyContactNo'];
    $profile['email'] = $n['companyEmail'];
    $control['companyName'] = $n['companyName'];
    $control['companyAddress'] = $n['companyAddress'];
    $control['companyRegNo'] = $n['companyRegNo'];
    $control['companyPAN'] = $n['companyPAN'];
    $control['companyTAN'] = $n['companyTAN'];
    $control['companyGSTIN'] = $n['companyGSTIN'];
    $control['companyContact'] = $n['companyContactNo'];
    $writeSetting = function(string $key, array $value) use ($td): void {
      $json = json_encode($value, JSON_UNESCAPED_UNICODE);
      $now = now_iso();
      $st = $td->prepare("INSERT INTO tenant_settings (key,value,updated_at) VALUES (?,?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value,updated_at=excluded.updated_at");
      $st->execute([$key, $json, $now]);
    };
    $writeSetting('company_profile', $profile);
    $writeSetting('control_settings', $control);
  };
  if($id && $id > 0){
    if($pwdHash !== ''){
      $st = $d->prepare("UPDATE clients SET company_name=?,company_address=?,company_reg_no=?,company_pan=?,company_tan=?,company_gstin=?,company_contact_no=?,company_email=?,user_id=?,user_password='',user_password_hash=?,subscription_plan_id=?,updated_at=? WHERE id=?");
      $st->execute([$n['companyName'],$n['companyAddress'],$n['companyRegNo'],$n['companyPAN'],$n['companyTAN'],$n['companyGSTIN'],$n['companyContactNo'],$n['companyEmail'],$n['userId'],$pwdHash,$n['subscriptionPlanId'],$ts,$id]);
    } else {
      $st = $d->prepare("UPDATE clients SET company_name=?,company_address=?,company_reg_no=?,company_pan=?,company_tan=?,company_gstin=?,company_contact_no=?,company_email=?,user_id=?,subscription_plan_id=?,updated_at=? WHERE id=?");
      $st->execute([$n['companyName'],$n['companyAddress'],$n['companyRegNo'],$n['companyPAN'],$n['companyTAN'],$n['companyGSTIN'],$n['companyContactNo'],$n['companyEmail'],$n['userId'],$n['subscriptionPlanId'],$ts,$id]);
    }
    if($effectiveAccessType !== ''){
      $ap = access_type_permissions($effectiveAccessType);
      access_put($id, ["accessType"=>$effectiveAccessType, "permissions"=>$ap]);
    }
    $syncTenant($id);
  } else {
    $st = $d->prepare("INSERT INTO clients (company_name,company_address,company_reg_no,company_pan,company_tan,company_gstin,company_contact_no,company_email,user_id,user_password,user_password_hash,subscription_plan_id,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $st->execute([$n['companyName'],$n['companyAddress'],$n['companyRegNo'],$n['companyPAN'],$n['companyTAN'],$n['companyGSTIN'],$n['companyContactNo'],$n['companyEmail'],$n['userId'],'',$pwdHash,$n['subscriptionPlanId'],$ts,$ts]);
    $id = (int)$d->lastInsertId();
    if($effectiveAccessType !== ''){
      $ap = access_type_permissions($effectiveAccessType);
      access_put($id, ["accessType"=>$effectiveAccessType, "permissions"=>$ap]);
    }
    $syncTenant($id);
  }
  $n['id'] = $id;
  $plainPassword = (string)($n['userPassword'] ?? '');
  unset($n['userPassword']);
  $row = $n + ["__updatedAt"=>$ts];
  mail_client_onboarding((string)$id, $row, $plainPassword, $isNew);
  return $row;
}
function client_delete(int $id): void {
  $d = central_db();
  $st = $d->prepare("DELETE FROM clients WHERE id=?");
  $st->execute([$id]);
  $d->prepare("DELETE FROM client_access WHERE client_id=?")->execute([$id]);
  $d->prepare("DELETE FROM staff_roles WHERE client_id=?")->execute([$id]);
  $d->prepare("DELETE FROM staff_users WHERE client_id=?")->execute([$id]);
  if($st->rowCount() === 0) nf('Client not found');
}
function client_exists(int $id): bool {
  $q = central_db()->prepare("SELECT id FROM clients WHERE id=?");
  $q->execute([$id]);
  return (bool)$q->fetch();
}
function access_default_permissions(): array {
  return [
    "dashboard"=>true,"clientModule"=>true,"employeeMaster"=>true,"employeeType"=>true,"salarySheet"=>true,"payslips"=>true,
    "compliance"=>true,"attendance"=>true,"attendanceStatus"=>true,"leaveManagement"=>true,"fnf"=>true,"gratuity"=>true,"bonus"=>true,"incentive"=>true,"loan"=>true,"pfSheet"=>true,
    "pfReturn"=>true,"esicSheet"=>true,"esicReturn"=>true,"ecrSheet"=>true,"controlPage"=>true,
    "companyProfile"=>true,"subscriptions"=>true,"billing"=>true,"invoices"=>true,"accessControl"=>false,"shiftRoster"=>true,"advanceSalary"=>true
  ];
}
function access_type_rows(): array {
  $rows = central_db()->query("SELECT code, name, permissions, is_system, updated_at FROM access_types ORDER BY is_system DESC, name ASC")->fetchAll();
  return array_map(function($r){
    $perm = json_decode((string)($r['permissions'] ?? '[]'), true);
    return [
      "code" => (string)$r['code'],
      "name" => (string)$r['name'],
      "isSystem" => ((int)($r['is_system'] ?? 0)) === 1,
      "permissions" => access_norm_permissions(is_array($perm) ? $perm : []),
      "__updatedAt" => (string)($r['updated_at'] ?? '')
    ];
  }, $rows);
}
function access_type_get(string $code): ?array {
  $st = central_db()->prepare("SELECT code, name, permissions, is_system, updated_at FROM access_types WHERE code=? LIMIT 1");
  $st->execute([strtolower(trim($code))]);
  $r = $st->fetch();
  if(!$r) return null;
  $perm = json_decode((string)($r['permissions'] ?? '[]'), true);
  return [
    "code" => (string)$r['code'],
    "name" => (string)$r['name'],
    "isSystem" => ((int)($r['is_system'] ?? 0)) === 1,
    "permissions" => access_norm_permissions(is_array($perm) ? $perm : []),
    "__updatedAt" => (string)($r['updated_at'] ?? '')
  ];
}
function access_type_permissions(string $code): array {
  $row = access_type_get($code);
  if($row) return access_norm_permissions($row['permissions'] ?? []);
  return access_default_permissions();
}
function access_type_code_from_name(string $name): string {
  $base = strtolower(trim($name));
  $base = preg_replace('/[^a-z0-9]+/', '_', $base ?? '') ?? '';
  $base = trim($base, '_');
  if($base === '') $base = 'type';
  $code = 'custom_'.$base;
  $i = 1;
  while(access_type_get($code)!==null){ $i++; $code = 'custom_'.$base.'_'.$i; }
  return $code;
}
function access_type_create(array $payload): array {
  $name = s($payload['name'] ?? '');
  if($name === '') bad('Access type name is required');
  $perm = access_norm_permissions($payload['permissions'] ?? []);
  $code = access_type_code_from_name($name);
  $ts = now_iso();
  $st = central_db()->prepare("INSERT INTO access_types (code,name,permissions,is_system,created_at,updated_at) VALUES (?,?,?,?,?,?)");
  $st->execute([$code,$name,json_encode($perm, JSON_UNESCAPED_UNICODE),0,$ts,$ts]);
  return access_type_get($code) ?? ["code"=>$code,"name"=>$name,"isSystem"=>false,"permissions"=>$perm,"__updatedAt"=>$ts];
}
function access_type_update(string $code, array $payload): array {
  $row = access_type_get($code);
  if(!$row) nf('Access type not found');
  if(!empty($row['isSystem'])) j(["detail"=>"System access type cannot be edited"],409);
  $name = s($payload['name'] ?? $row['name']);
  if($name === '') bad('Access type name is required');
  $perm = access_norm_permissions($payload['permissions'] ?? $row['permissions']);
  $ts = now_iso();
  $st = central_db()->prepare("UPDATE access_types SET name=?, permissions=?, updated_at=? WHERE code=?");
  $st->execute([$name, json_encode($perm, JSON_UNESCAPED_UNICODE), $ts, $row['code']]);
  return access_type_get($row['code']) ?? ["code"=>$row['code'],"name"=>$name,"isSystem"=>false,"permissions"=>$perm,"__updatedAt"=>$ts];
}
function access_type_delete(string $code): void {
  $row = access_type_get($code);
  if(!$row) nf('Access type not found');
  if(!empty($row['isSystem'])) j(["detail"=>"System access type cannot be deleted"],409);
  $st = central_db()->prepare("DELETE FROM access_types WHERE code=?");
  $st->execute([$row['code']]);
}
function subscription_norm(array $r): array {
  $clientId = (int)($r['clientId'] ?? 0);
  $planName = s($r['planName'] ?? '');
  $startDate = s($r['startDate'] ?? '');
  $endDate = s($r['endDate'] ?? '');
  $renewalDate = s($r['renewalDate'] ?? '');
  $status = s($r['status'] ?? 'Active', 'Active');
  $amount = f($r['amount'] ?? 0);
  $notes = s($r['notes'] ?? '');
  if($clientId <= 0) bad('clientId is required');
  if($planName === '') bad('planName is required');
  if($startDate === '' || $endDate === '' || $renewalDate === '') bad('startDate, endDate and renewalDate are required');
  if(!client_exists($clientId)) bad('Invalid clientId');
  return ["id" => isset($r['id']) ? (int)$r['id'] : null, "clientId"=>$clientId, "planName"=>$planName, "startDate"=>$startDate, "endDate"=>$endDate, "renewalDate"=>$renewalDate, "status"=>$status, "amount"=>$amount, "notes"=>$notes];
}
function subscriptions_all(): array {
  $rows = central_db()->query("SELECT s.*, c.company_name AS client_name, c.user_id AS user_id FROM subscriptions s LEFT JOIN clients c ON c.id=s.client_id ORDER BY s.updated_at DESC, s.id DESC")->fetchAll();
  return array_map(fn($r)=>[
    "id" => (int)$r['id'],
    "clientId" => (int)$r['client_id'],
    "clientName" => (string)($r['client_name'] ?? ''),
    "userId" => (string)($r['user_id'] ?? ''),
    "planName" => (string)$r['plan_name'],
    "startDate" => (string)$r['start_date'],
    "endDate" => (string)$r['end_date'],
    "renewalDate" => (string)$r['renewal_date'],
    "status" => (string)$r['status'],
    "amount" => (float)$r['amount'],
    "notes" => (string)$r['notes'],
    "__updatedAt" => (string)$r['updated_at']
  ], $rows);
}
function subscription_upsert(array $raw, ?bool $mustExist=null): array {
  $n = subscription_norm($raw);
  $id = $n['id'];
  $d = central_db();
  $isNew = !($id && $id>0);
  if($mustExist===true && (!$id || $id<=0)) bad('subscription id is required');
  if($id && $id>0){
    $q = $d->prepare("SELECT id FROM subscriptions WHERE id=?");
    $q->execute([$id]);
    if($mustExist===true && !$q->fetch()) nf('Subscription not found');
  }
  $ts = now_iso();
  if($id && $id>0){
    $st = $d->prepare("UPDATE subscriptions SET client_id=?, plan_name=?, start_date=?, end_date=?, renewal_date=?, status=?, amount=?, notes=?, updated_at=? WHERE id=?");
    $st->execute([$n['clientId'],$n['planName'],$n['startDate'],$n['endDate'],$n['renewalDate'],$n['status'],$n['amount'],$n['notes'],$ts,$id]);
  } else {
    $st = $d->prepare("INSERT INTO subscriptions (client_id, plan_name, start_date, end_date, renewal_date, status, amount, notes, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?)");
    $st->execute([$n['clientId'],$n['planName'],$n['startDate'],$n['endDate'],$n['renewalDate'],$n['status'],$n['amount'],$n['notes'],$ts,$ts]);
    $id = (int)$d->lastInsertId();
  }
  $n['id'] = $id;
  $q = $d->prepare("SELECT company_name, user_id FROM clients WHERE id=?");
  $q->execute([$n['clientId']]);
  $c = $q->fetch() ?: ["company_name"=>"","user_id"=>""];
  $row = $n + ["clientName"=>(string)$c['company_name'], "userId"=>(string)$c['user_id'], "__updatedAt"=>$ts];
  mail_subscription_event((string)$id, $row, $isNew);
  return $row;
}
function subscription_delete(int $id): void {
  $st = central_db()->prepare("DELETE FROM subscriptions WHERE id=?");
  $st->execute([$id]);
  if($st->rowCount()===0) nf('Subscription not found');
}
function plan_norm(array $r): array {
  $name = s($r['planName'] ?? '');
  if($name === '') bad('planName is required');
  $duration = (int)($r['durationMonths'] ?? 12);
  if($duration <= 0) bad('durationMonths must be > 0');
  $amount = f($r['amount'] ?? 0);
  $status = s($r['status'] ?? 'Active', 'Active');
  $features = s($r['features'] ?? '');
  $accessTypeCode = strtolower(s($r['accessTypeCode'] ?? 'full_access', 'full_access'));
  if(access_type_get($accessTypeCode) === null) bad('Invalid accessTypeCode');
  return ["id" => isset($r['id']) ? (int)$r['id'] : null, "planName"=>$name, "durationMonths"=>$duration, "amount"=>$amount, "status"=>$status, "features"=>$features, "accessTypeCode"=>$accessTypeCode];
}
function plans_all(): array {
  $rows = central_db()->query("SELECT p.*, a.name AS access_type_name FROM subscription_plans p LEFT JOIN access_types a ON a.code=p.access_type_code ORDER BY p.updated_at DESC, p.id DESC")->fetchAll();
  return array_map(fn($r)=>[
    "id" => (int)$r['id'],
    "planName" => (string)$r['plan_name'],
    "durationMonths" => (int)$r['duration_months'],
    "amount" => (float)$r['amount'],
    "status" => (string)$r['status'],
    "features" => (string)$r['features'],
    "accessTypeCode" => (string)($r['access_type_code'] ?? 'full_access'),
    "accessTypeName" => (string)($r['access_type_name'] ?? ($r['access_type_code'] ?? '')),
    "__updatedAt" => (string)$r['updated_at']
  ], $rows);
}
function plan_upsert(array $raw, ?bool $mustExist=null): array {
  $n = plan_norm($raw);
  $id = $n['id'];
  $d = central_db();
  if($mustExist===true && (!$id || $id<=0)) bad('plan id is required');
  if($id && $id>0){
    $q = $d->prepare("SELECT id FROM subscription_plans WHERE id=?");
    $q->execute([$id]);
    if($mustExist===true && !$q->fetch()) nf('Plan not found');
  }
  $dup = $d->prepare("SELECT id FROM subscription_plans WHERE lower(plan_name)=lower(?) AND id<>?");
  $dup->execute([$n['planName'], (int)($id ?? 0)]);
  if($dup->fetch()) j(["detail"=>"Plan name already exists"],409);
  $ts = now_iso();
  if($id && $id>0){
    $st = $d->prepare("UPDATE subscription_plans SET plan_name=?, duration_months=?, amount=?, status=?, features=?, access_type_code=?, updated_at=? WHERE id=?");
    $st->execute([$n['planName'],$n['durationMonths'],$n['amount'],$n['status'],$n['features'],$n['accessTypeCode'],$ts,$id]);
  } else {
    $st = $d->prepare("INSERT INTO subscription_plans (plan_name, duration_months, amount, status, features, access_type_code, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?)");
    $st->execute([$n['planName'],$n['durationMonths'],$n['amount'],$n['status'],$n['features'],$n['accessTypeCode'],$ts,$ts]);
    $id = (int)$d->lastInsertId();
  }
  $n['id'] = $id;
  $at = access_type_get($n['accessTypeCode']);
  $n['accessTypeName'] = $at['name'] ?? $n['accessTypeCode'];
  return $n + ["__updatedAt"=>$ts];
}
function plan_delete(int $id): void {
  $st = central_db()->prepare("DELETE FROM subscription_plans WHERE id=?");
  $st->execute([$id]);
  if($st->rowCount()===0) nf('Plan not found');
}
function subscription_info_get(): array {
  $cid = req_client_id();
  $plans = plans_all();
  if($cid <= 0){
    return ["clientId"=>0, "currentPlan"=>null, "plans"=>$plans];
  }
  $q = central_db()->prepare("SELECT c.id, c.subscription_plan_id, p.plan_name, p.duration_months, p.amount, p.status, p.features, p.access_type_code FROM clients c LEFT JOIN subscription_plans p ON p.id=c.subscription_plan_id WHERE c.id=? LIMIT 1");
  $q->execute([$cid]);
  $r = $q->fetch();
  if(!$r) nf('Client not found');
  $current = null;
  $curId = (int)($r['subscription_plan_id'] ?? 0);
  $startDate = '';
  $endDate = '';
  $renewalDate = '';
  $subStatus = '';
  $sq = central_db()->prepare("SELECT start_date, end_date, renewal_date, status FROM subscriptions WHERE client_id=? ORDER BY end_date DESC, id DESC LIMIT 1");
  $sq->execute([$cid]);
  $sr = $sq->fetch();
  if($sr){
    $startDate = (string)($sr['start_date'] ?? '');
    $endDate = (string)($sr['end_date'] ?? '');
    $renewalDate = (string)($sr['renewal_date'] ?? '');
    $subStatus = (string)($sr['status'] ?? '');
  }
  if($curId > 0){
    $at = access_type_get((string)($r['access_type_code'] ?? ''));
    $current = [
      "id" => $curId,
      "planName" => (string)($r['plan_name'] ?? ''),
      "durationMonths" => (int)($r['duration_months'] ?? 0),
      "amount" => (float)($r['amount'] ?? 0),
      "status" => $subStatus !== '' ? $subStatus : (string)($r['status'] ?? ''),
      "features" => (string)($r['features'] ?? ''),
      "accessTypeCode" => (string)($r['access_type_code'] ?? ''),
      "accessTypeName" => (string)($at['name'] ?? ($r['access_type_code'] ?? '')),
      "startDate" => $startDate,
      "endDate" => $endDate,
      "renewalDate" => $renewalDate
    ];
  }
  return ["clientId"=>$cid, "currentPlan"=>$current, "plans"=>$plans];
}
function client_access_template_get(): array {
  $cid = req_client_id();
  if($cid <= 0) bad('clientId is required');
  $q = central_db()->prepare("SELECT c.id, c.subscription_plan_id, p.access_type_code FROM clients c LEFT JOIN subscription_plans p ON p.id=c.subscription_plan_id WHERE c.id=? LIMIT 1");
  $q->execute([$cid]);
  $r = $q->fetch();
  if(!$r) nf('Client not found');
  $accessTypeCode = strtolower(s($r['access_type_code'] ?? '', ''));
  if($accessTypeCode !== ''){
    return [
      "clientId" => $cid,
      "source" => "subscription_plan",
      "accessTypeCode" => $accessTypeCode,
      "permissions" => access_type_permissions($accessTypeCode)
    ];
  }
  $acc = access_get($cid);
  return [
    "clientId" => $cid,
    "source" => "client_access",
    "accessTypeCode" => (string)($acc['accessType'] ?? 'custom'),
    "permissions" => access_norm_permissions($acc['permissions'] ?? [])
  ];
}
function billing_amount_by_access_type(string $accessType): float {
  $t = strtolower(trim($accessType));
  if(str_contains($t, 'full')) return 5000.0;
  if(str_contains($t, 'payroll')) return 3500.0;
  if(str_contains($t, 'compliance')) return 3000.0;
  if(str_contains($t, 'read')) return 2000.0;
  return 3200.0;
}
function client_billing_get(): array {
  $cid = req_client_id();
  if($cid <= 0) j(["detail"=>"Client session required"],401);
  $q = central_db()->prepare("SELECT c.id, c.company_name, p.plan_name, p.status FROM clients c LEFT JOIN subscription_plans p ON p.id=c.subscription_plan_id WHERE c.id=? LIMIT 1");
  $q->execute([$cid]);
  $row = $q->fetch();
  if(!$row) nf('Client not found');

  $planName = s($row['plan_name'] ?? '', '-');
  $planStatus = s($row['status'] ?? '', '-');
  $subQ = central_db()->prepare("SELECT id, plan_name, start_date, end_date, renewal_date, status, amount, updated_at FROM subscriptions WHERE client_id=? ORDER BY start_date DESC, id DESC");
  $subQ->execute([$cid]);
  $subs = $subQ->fetchAll();
  $rows = [];
  $sumSubtotal = 0.0;
  $sumGst = 0.0;
  $sumTotal = 0.0;
  $sumPaid = 0.0;
  $sumPending = 0.0;

  foreach($subs as $srow){
    $id = (int)($srow['id'] ?? 0);
    $issuedOn = s($srow['start_date'] ?? '', '');
    $dueDate = s($srow['renewal_date'] ?? '', '');
    $endDate = s($srow['end_date'] ?? '', '');
    $statusRaw = s($srow['status'] ?? '', 'Pending');
    $statusNorm = strtolower($statusRaw);
    $isPaid = in_array($statusNorm, ['paid','completed','settled','success'], true);
    $status = $isPaid ? 'Paid' : $statusRaw;
    $subtotal = round(f($srow['amount'] ?? 0), 2);
    $gst = round($subtotal * 0.18, 2);
    $total = round($subtotal + $gst, 2);
    $paidOn = $isPaid ? s($srow['updated_at'] ?? '', '') : null;
    $monthLabel = '-';
    if($issuedOn !== '' && strtotime($issuedOn) !== false){
      $monthLabel = gmdate('M Y', (int)strtotime($issuedOn));
    } elseif($dueDate !== '' && strtotime($dueDate) !== false){
      $monthLabel = gmdate('M Y', (int)strtotime($dueDate));
    }
    $invoiceNo = 'SUB-'.str_pad((string)$id, 6, '0', STR_PAD_LEFT);
    $rows[] = [
      "id" => (string)$id,
      "invoiceNo" => $invoiceNo,
      "billingMonth" => $monthLabel,
      "planName" => s($srow['plan_name'] ?? '', $planName),
      "planStatus" => $status,
      "amount" => $subtotal,
      "gst" => $gst,
      "total" => $total,
      "status" => $status,
      "issuedOn" => $issuedOn,
      "dueDate" => $dueDate,
      "endDate" => $endDate,
      "paidOn" => $paidOn
    ];
    $sumSubtotal += $subtotal;
    $sumGst += $gst;
    $sumTotal += $total;
    if($isPaid) $sumPaid += $total;
    else $sumPending += $total;
  }

  return [
    "clientId" => (int)$cid,
    "clientName" => (string)($row['company_name'] ?? ''),
    "currentPlan" => ["planName"=>$planName, "status"=>$planStatus],
    "summary" => [
      "subtotal" => round($sumSubtotal, 2),
      "gst" => round($sumGst, 2),
      "total" => round($sumTotal, 2),
      "paid" => round($sumPaid, 2),
      "pending" => round($sumPending, 2)
    ],
    "rows" => $rows
  ];
}
function client_invoices_get(): array {
  $bill = client_billing_get();
  $rows = [];
  foreach(($bill['rows'] ?? []) as $r){
    $rows[] = $r + [
      "invoiceTitle" => (string)($r['invoiceNo'] ?? ''),
      "downloadUrl" => ""
    ];
  }
  return [
    "clientId" => (int)($bill['clientId'] ?? 0),
    "clientName" => (string)($bill['clientName'] ?? ''),
    "currentPlan" => $bill['currentPlan'] ?? null,
    "summary" => $bill['summary'] ?? [],
    "rows" => $rows
  ];
}
function access_norm_permissions($raw): array {
  $base = access_default_permissions();
  $src = is_array($raw) ? $raw : [];
  foreach($base as $k => $v){
    $base[$k] = b($src[$k] ?? $v);
  }
  return $base;
}
function access_get(int $clientId): array {
  if($clientId <= 0 || !client_exists($clientId)) nf('Client not found');
  $q = central_db()->prepare("SELECT permissions, access_type, updated_at FROM client_access WHERE client_id=?");
  $q->execute([$clientId]);
  $row = $q->fetch();
  if(!$row){
    return ["clientId"=>$clientId,"accessType"=>"custom","permissions"=>access_default_permissions(),"__updatedAt"=>null];
  }
  $decoded = json_decode((string)$row['permissions'], true);
  $type = strtolower(s($row['access_type'] ?? 'custom','custom'));
  return ["clientId"=>$clientId,"accessType"=>$type,"permissions"=>access_norm_permissions($decoded),"__updatedAt"=>(string)$row['updated_at']];
}
function access_put(int $clientId, array $payload): array {
  if($clientId <= 0 || !client_exists($clientId)) nf('Client not found');
  $type = strtolower(s($payload['accessType'] ?? 'custom', 'custom'));
  $srcPerm = $payload['permissions'] ?? null;
  if(!is_array($srcPerm) || !$srcPerm){
    $srcPerm = access_type_permissions($type);
  }
  $perm = access_norm_permissions($srcPerm);
  if($type === '') $type = 'custom';
  $ts = now_iso();
  $st = central_db()->prepare("INSERT INTO client_access (client_id, permissions, access_type, updated_at) VALUES (?,?,?,?) ON CONFLICT(client_id) DO UPDATE SET permissions=excluded.permissions, access_type=excluded.access_type, updated_at=excluded.updated_at");
  $st->execute([$clientId, json_encode($perm, JSON_UNESCAPED_UNICODE), $type, $ts]);
  return ["clientId"=>$clientId,"accessType"=>$type,"permissions"=>$perm,"__updatedAt"=>$ts];
}
function perm_intersect(array $outer, array $inner): array {
  $base = access_default_permissions();
  foreach($base as $k => $v){
    $base[$k] = b($outer[$k] ?? $v) && b($inner[$k] ?? false);
  }
  return $base;
}
function staff_role_norm_permissions($raw): array {
  return access_norm_permissions(is_array($raw) ? $raw : []);
}
function staff_role_code_from_name(int $clientId, string $name): string {
  $base = strtolower(trim($name));
  $base = preg_replace('/[^a-z0-9]+/', '_', $base ?? '') ?? '';
  $base = trim($base, '_');
  if($base === '') $base = 'role';
  $code = 'role_'.$base;
  $i = 1;
  while(staff_role_get($clientId, $code) !== null){ $i++; $code = 'role_'.$base.'_'.$i; }
  return $code;
}
function staff_role_get(int $clientId, string $code): ?array {
  if($clientId <= 0) return null;
  $q = central_db()->prepare("SELECT client_id, code, name, permissions, created_at, updated_at FROM staff_roles WHERE client_id=? AND code=? LIMIT 1");
  $q->execute([$clientId, strtolower(trim($code))]);
  $r = $q->fetch();
  if(!$r) return null;
  $perm = json_decode((string)($r['permissions'] ?? '[]'), true);
  return [
    "clientId" => (int)$r['client_id'],
    "code" => (string)$r['code'],
    "name" => (string)$r['name'],
    "permissions" => staff_role_norm_permissions(is_array($perm) ? $perm : []),
    "__createdAt" => (string)($r['created_at'] ?? ''),
    "__updatedAt" => (string)($r['updated_at'] ?? '')
  ];
}
function staff_role_rows(int $clientId): array {
  if($clientId <= 0 || !client_exists($clientId)) return [];
  $q = central_db()->prepare("SELECT client_id, code, name, permissions, created_at, updated_at FROM staff_roles WHERE client_id=? ORDER BY name ASC");
  $q->execute([$clientId]);
  $rows = $q->fetchAll();
  return array_map(function($r){
    $perm = json_decode((string)($r['permissions'] ?? '[]'), true);
    return [
      "clientId" => (int)$r['client_id'],
      "code" => (string)$r['code'],
      "name" => (string)$r['name'],
      "permissions" => staff_role_norm_permissions(is_array($perm) ? $perm : []),
      "__createdAt" => (string)($r['created_at'] ?? ''),
      "__updatedAt" => (string)($r['updated_at'] ?? '')
    ];
  }, $rows);
}
function staff_role_create(int $clientId, array $payload): array {
  if($clientId <= 0 || !client_exists($clientId)) bad('clientId is required');
  $name = s($payload['name'] ?? '');
  if($name === '') bad('Role name is required');
  $perm = staff_role_norm_permissions($payload['permissions'] ?? []);
  $code = staff_role_code_from_name($clientId, $name);
  $ts = now_iso();
  $st = central_db()->prepare("INSERT INTO staff_roles (client_id, code, name, permissions, created_at, updated_at) VALUES (?,?,?,?,?,?)");
  $st->execute([$clientId, $code, $name, json_encode($perm, JSON_UNESCAPED_UNICODE), $ts, $ts]);
  return staff_role_get($clientId, $code) ?? ["clientId"=>$clientId,"code"=>$code,"name"=>$name,"permissions"=>$perm,"__updatedAt"=>$ts];
}
function staff_role_update(int $clientId, string $code, array $payload): array {
  $row = staff_role_get($clientId, $code);
  if(!$row) nf('Role not found');
  $name = s($payload['name'] ?? $row['name']);
  if($name === '') bad('Role name is required');
  $perm = staff_role_norm_permissions($payload['permissions'] ?? $row['permissions']);
  $ts = now_iso();
  $st = central_db()->prepare("UPDATE staff_roles SET name=?, permissions=?, updated_at=? WHERE client_id=? AND code=?");
  $st->execute([$name, json_encode($perm, JSON_UNESCAPED_UNICODE), $ts, $clientId, $row['code']]);
  return staff_role_get($clientId, $row['code']) ?? ["clientId"=>$clientId,"code"=>$row['code'],"name"=>$name,"permissions"=>$perm,"__updatedAt"=>$ts];
}
function staff_role_delete(int $clientId, string $code): void {
  $row = staff_role_get($clientId, $code);
  if(!$row) nf('Role not found');
  $q = central_db()->prepare("SELECT COUNT(*) AS cnt FROM staff_users WHERE client_id=? AND role_code=?");
  $q->execute([$clientId, $row['code']]);
  $cnt = (int)($q->fetchColumn() ?: 0);
  if($cnt > 0) j(["detail"=>"Role is assigned to staff users and cannot be deleted"],409);
  $st = central_db()->prepare("DELETE FROM staff_roles WHERE client_id=? AND code=?");
  $st->execute([$clientId, $row['code']]);
}
function staff_role_permissions(int $clientId, string $code): array {
  $row = staff_role_get($clientId, $code);
  if(!$row) return access_default_permissions();
  return staff_role_norm_permissions($row['permissions'] ?? []);
}
function staff_user_rows(int $clientId): array {
  if($clientId <= 0 || !client_exists($clientId)) return [];
  $emps = employees_all();
  $emap = [];
  foreach($emps as $e){
    $eid = up($e['id'] ?? '');
    if($eid === '') continue;
    $emap[$eid] = $e;
  }
  $q = central_db()->prepare("SELECT id, client_id, emp_id, username, role_code, status, created_at, updated_at FROM staff_users WHERE client_id=? ORDER BY emp_id ASC");
  $q->execute([$clientId]);
  $rows = [];
  foreach($q->fetchAll() as $r){
    $eid = up($r['emp_id'] ?? '');
    $e = $emap[$eid] ?? [];
    $rows[] = [
      "id" => (int)$r['id'],
      "clientId" => (int)$r['client_id'],
      "empId" => $eid,
      "empName" => (string)($e['name'] ?? ''),
      "dept" => (string)($e['dept'] ?? ''),
      "desig" => (string)($e['desig'] ?? ''),
      "username" => (string)$r['username'],
      "roleCode" => (string)$r['role_code'],
      "status" => (string)$r['status'],
      "__createdAt" => (string)($r['created_at'] ?? ''),
      "__updatedAt" => (string)($r['updated_at'] ?? '')
    ];
  }
  return $rows;
}
function staff_user_get_by_username(string $username): ?array {
  $u = strtolower(trim($username));
  if($u === '') return null;
  $q = central_db()->prepare("SELECT id, client_id, emp_id, username, password_hash, role_code, status, created_at, updated_at FROM staff_users WHERE lower(username)=? LIMIT 1");
  $q->execute([$u]);
  $r = $q->fetch();
  if(!$r) return null;
  return [
    "id" => (int)$r['id'],
    "clientId" => (int)$r['client_id'],
    "empId" => up($r['emp_id'] ?? ''),
    "username" => (string)$r['username'],
    "passwordHash" => (string)($r['password_hash'] ?? ''),
    "roleCode" => (string)($r['role_code'] ?? ''),
    "status" => (string)($r['status'] ?? 'Active'),
    "__createdAt" => (string)($r['created_at'] ?? ''),
    "__updatedAt" => (string)($r['updated_at'] ?? '')
  ];
}
function staff_user_upsert(int $clientId, string $empId, array $payload): array {
  if($clientId <= 0 || !client_exists($clientId)) bad('clientId is required');
  $empId = up($empId);
  if($empId === '') bad('empId is required');
  $existsEmp = false;
  foreach(employees_all() as $e){ if(up($e['id'] ?? '') === $empId){ $existsEmp = true; break; } }
  if(!$existsEmp) bad('Employee not found');
  $username = strtolower(s($payload['username'] ?? ''));
  if($username === '') bad('username is required');
  $roleCode = strtolower(s($payload['roleCode'] ?? ''));
  if($roleCode === '') bad('roleCode is required');
  if(!staff_role_get($clientId, $roleCode)) bad('Invalid roleCode');
  $status = s($payload['status'] ?? 'Active', 'Active');
  $status = strtolower($status) === 'inactive' ? 'Inactive' : 'Active';
  $password = s($payload['password'] ?? '');
  $ts = now_iso();
  $q = central_db()->prepare("SELECT id, password_hash FROM staff_users WHERE client_id=? AND emp_id=? LIMIT 1");
  $q->execute([$clientId, $empId]);
  $row = $q->fetch();
  $isNew = !$row;
  if($row){
    $id = (int)$row['id'];
    if($password !== ''){
      $hash = password_hash($password, PASSWORD_DEFAULT);
      $st = central_db()->prepare("UPDATE staff_users SET username=?, password_hash=?, role_code=?, status=?, updated_at=? WHERE id=?");
      $st->execute([$username, $hash, $roleCode, $status, $ts, $id]);
    } else {
      $st = central_db()->prepare("UPDATE staff_users SET username=?, role_code=?, status=?, updated_at=? WHERE id=?");
      $st->execute([$username, $roleCode, $status, $ts, $id]);
    }
  } else {
    if($password === '') bad('password is required for new staff user');
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $st = central_db()->prepare("INSERT INTO staff_users (client_id, emp_id, username, password_hash, role_code, status, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?)");
    $st->execute([$clientId, $empId, $username, $hash, $roleCode, $status, $ts, $ts]);
  }
  $list = staff_user_rows($clientId);
  foreach($list as $x){
    if(up($x['empId'] ?? '') === $empId){
      mail_staff_access($clientId, (string)($x['id'] ?? $empId), $x, employee_lookup($empId), $password, $isNew);
      return $x;
    }
  }
  $fallback = ["clientId"=>$clientId,"empId"=>$empId,"username"=>$username,"roleCode"=>$roleCode,"status"=>$status,"__updatedAt"=>$ts];
  mail_staff_access($clientId, $empId, $fallback, employee_lookup($empId), $password, $isNew);
  return $fallback;
}
function staff_user_delete(int $clientId, string $empId): void {
  $empId = up($empId);
  if($clientId <= 0 || $empId === '') bad('clientId and empId are required');
  $st = central_db()->prepare("DELETE FROM staff_users WHERE client_id=? AND emp_id=?");
  $st->execute([$clientId, $empId]);
  if($st->rowCount() === 0) nf('Staff user not found');
}

function auth_users(): array {
  $st = central_db()->prepare("SELECT value FROM app_kv WHERE key=?");
  $st->execute(['auth_users']);
  $r = $st->fetch();
  $x = $r ? json_decode((string)$r['value'], true) : DEFAULT_AUTH_USERS;
  $rows = is_array($x) ? $x : DEFAULT_AUTH_USERS;
  $hasAdmin = false;
  $hasAdminHrseva = false;
  foreach($rows as &$u){
    if(empty($u['role'])){
      $u['role'] = 'client';
    }
    if(strtolower((string)($u['username'] ?? '')) === 'admin'){
      $hasAdmin = true;
      if(empty($u['role'])) $u['role'] = 'super_admin';
    }
    if(strtolower((string)($u['username'] ?? '')) === 'admin@hrseva.com'){
      $hasAdminHrseva = true;
      if(empty($u['role'])) $u['role'] = 'super_admin';
    }
  }
  unset($u);
  if(!$hasAdmin){
    $rows[] = ["username"=>"admin","password"=>"123456","name"=>"Admin","role"=>"super_admin"];
  }
  if(!$hasAdminHrseva){
    $rows[] = ["username"=>"admin@hrseva.com","password"=>"123456","name"=>"Admin","role"=>"super_admin"];
  }
  return $rows;
}
function auth_users_save(array $rows): void {
  kv_set_on(central_db(), 'auth_users', array_values($rows));
}
function auth_user_verify(array $u, string $password): bool {
  $hash = (string)($u['passwordHash'] ?? '');
  if($hash !== '' && password_verify($password, $hash)) return true;
  $plain = (string)($u['password'] ?? '');
  return $plain !== '' && hash_equals($plain, $password);
}
function auth_login(string $u,string $p): array {
  $u=strtolower(trim($u)); $p=trim($p); if($u===''||$p==='') bad('username and password are required');
  login_rate_limit_check($u);
  $users = auth_users();
  $usersChanged = false;
  foreach($users as $idx => $x){
    if(strtolower((string)($x['username'] ?? ''))===$u && auth_user_verify($x, $p)){
      if((string)($x['passwordHash'] ?? '') === ''){
        $users[$idx]['passwordHash'] = password_hash($p, PASSWORD_DEFAULT);
        $users[$idx]['password'] = '';
        $usersChanged = true;
      }
      if($usersChanged) auth_users_save($users);
      login_rate_limit_success($u);
      $now = time();
      $role = (string)($x['role'] ?? 'super_admin');
      $tokenClientId = (int)($x['clientId'] ?? 0);
      $tokenEmpId = up($x['empId'] ?? '');
      $tok = token_sign([
        'sub' => $u,
        'name' => (string)($x['name'] ?? $u),
        'role' => $role,
        'clientId' => $tokenClientId,
        'empId' => $tokenEmpId,
        'iat' => $now,
        'exp' => $now + AUTH_TOKEN_TTL
      ]);
      return ["ok"=>true,"token"=>$tok,"user"=>["username"=>$u,"name"=>$x['name']??$u,"role"=>$role,"clientId"=>$tokenClientId,"empId"=>$tokenEmpId]];
    }
  }
  $q = central_db()->prepare("SELECT id, company_name, subscription_plan_id, user_password, user_password_hash FROM clients WHERE lower(user_id)=? LIMIT 1");
  $q->execute([$u]);
  $row = $q->fetch();
  if($row){
    $sub = client_subscription_access_state((int)$row['id']);
    if(empty($sub['active'])){
      j(['detail'=>'Subscription expired. Access denied.', 'reason'=>$sub['reason'] ?? '', 'endDate'=>$sub['endDate'] ?? null],403);
    }
    $ok = false;
    $hash = (string)($row['user_password_hash'] ?? '');
    if($hash !== '' && password_verify($p, $hash)){
      $ok = true;
    } else {
      $plain = (string)($row['user_password'] ?? '');
      if($plain !== '' && hash_equals($plain, $p)){
        $ok = true;
        $newHash = password_hash($p, PASSWORD_DEFAULT);
        $m = central_db()->prepare("UPDATE clients SET user_password='', user_password_hash=?, updated_at=? WHERE id=?");
        $m->execute([$newHash, now_iso(), (int)$row['id']]);
      }
    }
    if(!$ok){
      login_rate_limit_fail($u);
      j(["detail"=>"Invalid credentials"],401);
    }
    login_rate_limit_success($u);
    $cid = (int)$row['id'];
    // Keep client access in sync with the selected subscription plan access type.
    $planId = (int)($row['subscription_plan_id'] ?? 0);
    if($planId > 0){
      $sp = central_db()->prepare("SELECT access_type_code FROM subscription_plans WHERE id=? LIMIT 1");
      $sp->execute([$planId]);
      $pr = $sp->fetch();
      if($pr){
        $planAccessType = strtolower(s($pr['access_type_code'] ?? ''));
        if($planAccessType !== ''){
          access_put($cid, ["accessType"=>$planAccessType, "permissions"=>access_type_permissions($planAccessType)]);
        }
      }
    }
    $acc = access_get($cid);
    $now = time();
    $tok = token_sign([
      'sub' => $u,
      'name' => (string)$row['company_name'],
      'role' => 'client',
      'clientId' => $cid,
      'iat' => $now,
      'exp' => $now + AUTH_TOKEN_TTL
    ]);
    return ["ok"=>true,"token"=>$tok,"user"=>["username"=>$u,"name"=>(string)$row['company_name'],"role"=>"client","clientId"=>$cid,"permissions"=>$acc['permissions']]];
  }
  $staff = staff_user_get_by_username($u);
  if($staff){
    if(strtolower((string)($staff['status'] ?? 'active')) !== 'active'){
      login_rate_limit_fail($u);
      j(["detail"=>"Account is inactive"],403);
    }
    $hash = (string)($staff['passwordHash'] ?? '');
    if($hash === '' || !password_verify($p, $hash)){
      login_rate_limit_fail($u);
      j(["detail"=>"Invalid credentials"],401);
    }
    $cid = (int)($staff['clientId'] ?? 0);
    if($cid <= 0 || !client_exists($cid)){
      login_rate_limit_fail($u);
      j(["detail"=>"Invalid staff account"],403);
    }
    $sub = client_subscription_access_state($cid);
    if(empty($sub['active'])){
      j(['detail'=>'Subscription expired. Access denied.', 'reason'=>$sub['reason'] ?? '', 'endDate'=>$sub['endDate'] ?? null],403);
    }
    login_rate_limit_success($u);
    $companyAccess = access_get($cid);
    $rolePerm = staff_role_permissions($cid, (string)($staff['roleCode'] ?? ''));
    $effectivePerm = perm_intersect($companyAccess['permissions'] ?? access_default_permissions(), $rolePerm);
    $now = time();
    $empId = up($staff['empId'] ?? '');
    $tok = token_sign([
      'sub' => $u,
      'name' => (string)($staff['username'] ?? $u),
      'role' => 'employee',
      'clientId' => $cid,
      'empId' => $empId,
      'iat' => $now,
      'exp' => $now + AUTH_TOKEN_TTL
    ]);
    return ["ok"=>true,"token"=>$tok,"user"=>["username"=>$u,"name"=>(string)($staff['username'] ?? $u),"role"=>"employee","clientId"=>$cid,"empId"=>$empId,"permissions"=>$effectivePerm]];
  }
  login_rate_limit_fail($u);
  j(["detail"=>"Invalid credentials"],401);
}

function att_month_key(int $m,int $y): string { return sprintf('attendance_daily_%04d-%02d',$y,$m); }
function att_daily_list(int $m,int $y): array { $map=kv_get(att_month_key($m,$y),[]); if(!is_array($map)) $map=[]; $out=[]; foreach($map as $k=>$st){ $p=explode('|',(string)$k,2); if(count($p)===2) $out[]=["empId"=>$p[0],"date"=>$p[1],"status"=>strtoupper((string)$st)]; } usort($out,fn($a,$b)=>strcmp($a['empId'].$a['date'],$b['empId'].$b['date'])); return $out; }
function att_daily_upsert(int $m,int $y,array $records): array { $map=kv_get(att_month_key($m,$y),[]); if(!is_array($map)) $map=[]; $n=0; foreach($records as $r){ $e=up($r['empId']??''); $d=s($r['date']??''); if($e===''||$d==='') continue; $map[$e.'|'.$d]=strtoupper(s($r['status']??'P','P')); $n++; } kv_set(att_month_key($m,$y),$map); return ["upserted"=>$n]; }
function att_generate(int $m,int $y,bool $fill=true,bool $sunday=true): array {
  $clientId = req_client_id();
  $daily=kv_get(att_month_key($m,$y),[]); if(!is_array($daily)) $daily=[]; $emps=employees_active_all(); $dim=(int)cal_days_in_month(CAL_GREGORIAN,$m,$y); $rows=[];
  foreach($emps as $e){ $c=["P"=>0.0,"A"=>0.0,"WO"=>0.0,"CL"=>0.0,"SL"=>0.0,"EL"=>0.0,"LOP"=>0.0];
    for($d=1;$d<=$dim;$d++){ $date=sprintf('%04d-%02d-%02d',$y,$m,$d); $st=strtoupper((string)($daily[$e['id'].'|'.$date]??'')); if($st===''&&$fill){ $dow=(int)date('w',strtotime($date)); $st=($sunday&&$dow===0)?'WO':'P'; } if(!isset($c[$st])) $st='P'; $c[$st]+=1; }
    $rows[]=["month"=>period($m,$y),"empId"=>$e['id'],"empName"=>$e['name'],"dept"=>$e['dept'],"desig"=>$e['desig'],"daysInMonth"=>$dim,"P"=>$c['P'],"A"=>$c['A'],"WO"=>$c['WO'],"CL"=>$c['CL'],"SL"=>$c['SL'],"EL"=>$c['EL'],"LOP"=>$c['LOP']];
  }
  $sheet = save_sheet('attendance_sheet',$m,$y,$rows);
  mail_sheet_event('attendance_sheet', $clientId, $sheet, 'Attendance Sheet');
  return $sheet;
}

function split_ctc(float $base,array $c): array {
  if (function_exists('app') && app()->bound(\App\Services\Payroll\StatutoryCalculator::class)) {
    return app(\App\Services\Payroll\StatutoryCalculator::class)->splitCtc($base, $c);
  }
  $b=$base*f($c['ctcBasicPct']??50)/100; $h=$base*f($c['ctcHraPct']??10)/100; $v=$base*f($c['ctcConvPct']??0)/100; $d=$base*f($c['ctcDaPct']??30)/100; $e=$base*f($c['ctcEduPct']??0)/100; $s=$base*f($c['ctcSpecialPct']??0)/100;
  return ["basic"=>$b,"hra"=>$h,"convey"=>$v,"da"=>$d,"edu"=>$e,"special"=>$s,"gross"=>$b+$h+$v+$d+$e+$s];
}
function gratuity_mode_norm($mode): string {
  $x = strtolower(trim((string)$mode));
  return $x === 'monthly' ? 'monthly' : 'after_5yr';
}
function gratuity_mode_label(string $mode): string {
  return $mode === 'monthly' ? 'Monthly' : 'After completion of 5yr';
}
function gratuity_min_years(array $ctrl): float {
  return max(0.0, f($ctrl['gratuityMinYears'] ?? 5));
}
function gratuity_calc_row(array $emp, array $ctrl, float $years): array {
  $baseCtc = f($emp['baseCtc'] ?? 0);
  $parts = split_ctc(max(0.0, $baseCtc), $ctrl);
  $basic = round(f($parts['basic'] ?? 0), 2);
  $da = round(f($parts['da'] ?? 0), 2);
  $mode = gratuity_mode_norm($ctrl['gratuityMode'] ?? 'after_5yr');
  $amount = 0.0;
  $formula = '';
  if($mode === 'monthly'){
    $amount = $basic * 4.81 / 100.0;
    $formula = 'Basic x 4.81%';
  } else {
    $minYears = gratuity_min_years($ctrl);
    if($years <= $minYears) bad('Years of service must be more than ' . rtrim(rtrim(number_format($minYears, 2, '.', ''), '0'), '.') . ' for after completion gratuity mode');
    $amount = (($basic + $da) * 15.0 * $years) / 26.0;
    $formula = '((Basic + DA) x 15 x Years) / 26';
  }
  return [
    'mode'=>$mode,
    'modeLabel'=>gratuity_mode_label($mode),
    'years'=>round($years, 2),
    'baseCtc'=>round($baseCtc, 2),
    'basic'=>round($basic, 2),
    'da'=>round($da, 2),
    'gratuityAmount'=>round($amount, 2),
    'formula'=>$formula
  ];
}
function ovr_all(): array { $x=kv_get('payroll_overrides',[]); return is_array($x)?$x:[]; }
function ovr_set(array $x): void { kv_set('payroll_overrides',$x); }

function payroll_resolve(int $m,int $y): array {
  $it=find_period(idx('payroll_sheet_index'),$m,$y); if($it) return get_sheet(idkey('payroll_sheet',(string)$it['id']),'Payroll sheet not found');
  return payroll_generate($m,$y,'LOP');
}
function attendance_resolve(int $m,int $y): array { $it=find_period(idx('attendance_sheet_index'),$m,$y); if($it) return get_sheet(idkey('attendance_sheet',(string)$it['id']),'Attendance sheet not found'); return att_generate($m,$y,true,true); }
function fnf_paid_lop_till_exit(string $empId,string $exitDate): ?array {
  $eid = up($empId);
  $exit = trim($exitDate);
  if($eid === '' || $exit === '') return null;
  $ts = strtotime($exit);
  if($ts === false) return null;
  $y = (int)gmdate('Y', $ts);
  $m = (int)gmdate('n', $ts);
  $exitDay = (int)gmdate('j', $ts);
  if($y < 2000 || $m < 1 || $m > 12 || $exitDay < 1) return null;
  $dim = (int)cal_days_in_month(CAL_GREGORIAN, $m, $y);
  $to = min($dim, $exitDay);
  if($to < 1) return null;
  $daily = kv_get(att_month_key($m,$y), []);
  if(!is_array($daily)) $daily = [];
  $lop = 0.0;
  for($d=1; $d<=$to; $d++){
    $iso = sprintf('%04d-%02d-%02d', $y, $m, $d);
    $st = strtoupper((string)($daily[$eid.'|'.$iso] ?? ''));
    if($st === 'A' || $st === 'LOP') $lop += 1.0;
  }
  $span = (float)$to;
  $paid = max(0.0, $span - $lop);
  return [
    "year"=>$y,
    "month"=>$m,
    "monthDays"=>$dim,
    "spanDays"=>$span,
    "paidDays"=>$paid,
    "lopDays"=>$lop
  ];
}
function lop_leave_days_map(int $m,int $y): array {
  $q = db()->prepare("SELECT emp_id, SUM(days) AS lop_days FROM leaves WHERE CAST(strftime('%m', from_date) AS INTEGER)=? AND CAST(strftime('%Y', from_date) AS INTEGER)=? AND leave_type='LOP' AND status='Approved' GROUP BY emp_id");
  $q->execute([$m,$y]);
  $out = [];
  foreach($q->fetchAll() as $r){
    $eid = up($r['emp_id'] ?? '');
    if($eid === '') continue;
    $out[$eid] = f($r['lop_days'] ?? 0);
  }
  return $out;
}
function control_other_deduction_breakup(array $ctrl): array {
  if (function_exists('app') && app()->bound(\App\Services\Payroll\StatutoryCalculator::class)) {
    return app(\App\Services\Payroll\StatutoryCalculator::class)->controlOtherDeductionBreakup($ctrl);
  }
  $rows = $ctrl['otherDeductionRows'] ?? [];
  if(!is_array($rows)) return [];
  $items = [];
  foreach($rows as $r){
    if(!is_array($r)) continue;
    $name = s($r['name'] ?? ($r['label'] ?? ''));
    if($name === '') continue;
    $amt = f($r['amount'] ?? ($r['monthly'] ?? ($r['value'] ?? 0)));
    if($amt <= 0) continue;
    if(!isset($items[$name])) $items[$name] = 0.0;
    $items[$name] += $amt;
  }
  $out = [];
  foreach($items as $name=>$amt){
    $out[] = ["name"=>$name, "amount"=>round($amt,2)];
  }
  return $out;
}
function ctrl_num(array $ctrl, string $key): float {
  if (function_exists('app') && app()->bound(\App\Services\Payroll\StatutoryCalculator::class)) {
    return app(\App\Services\Payroll\StatutoryCalculator::class)->ctrlNum($ctrl, $key);
  }
  return f($ctrl[$key] ?? (DEFAULT_CONTROL[$key] ?? 0));
}
function ctrl_bool(array $ctrl, string $key): bool {
  if (function_exists('app') && app()->bound(\App\Services\Payroll\StatutoryCalculator::class)) {
    return app(\App\Services\Payroll\StatutoryCalculator::class)->ctrlBool($ctrl, $key);
  }
  return b($ctrl[$key] ?? (DEFAULT_CONTROL[$key] ?? false));
}
function esi_wage_limit(array $ctrl): float {
  if (function_exists('app') && app()->bound(\App\Services\Payroll\StatutoryCalculator::class)) {
    return app(\App\Services\Payroll\StatutoryCalculator::class)->esiWageLimit($ctrl);
  }
  return max(0.0, ctrl_num($ctrl, 'esiWageLimit'));
}
function payroll_statutory_calc(array $ctrl, float $gross, float $earned, bool $pfAp, bool $esiAp): array {
  if (function_exists('app') && app()->bound(\App\Services\Payroll\StatutoryCalculator::class)) {
    return app(\App\Services\Payroll\StatutoryCalculator::class)->payrollStatutoryCalc($ctrl, $gross, $earned, $pfAp, $esiAp);
  }
  $calcBase = max(0.0, $earned);
  $earnedParts = split_ctc($calcBase, $ctrl);
  $pfBase = f($earnedParts['basic'] ?? 0) + (f($earnedParts['basic'] ?? 0) * ctrl_num($ctrl, 'daPctBasic') / 100.0);
  $esiLimit = esi_wage_limit($ctrl);
  $esiEligibleByWage = $esiLimit > 0 ? ($calcBase <= $esiLimit) : false;
  $esiApplicable = $esiAp && $calcBase > 0 && $esiEligibleByWage;
  $pfCapEnabled = ctrl_bool($ctrl, 'pfWageCapEnabled');
  $pfOnEsiPct = max(0.0, ctrl_num($ctrl, 'pfOnEsiPct'));
  if($esiApplicable){
    $statutoryBase = $calcBase * $pfOnEsiPct / 100.0;
    $pfWages = $statutoryBase;
    $esiWages = $statutoryBase;
  } else {
    $pfThreshold = $esiLimit;
    $pfWages = ($pfCapEnabled && $pfThreshold > 0 && $calcBase > $pfThreshold) ? ctrl_num($ctrl, 'pfWageCapAmount') : $pfBase;
    $esiWages = $calcBase;
  }
  $pfEE = $pfAp ? ($pfWages * ctrl_num($ctrl, 'pfEmpPct') / 100.0) : 0.0;
  $pfER = $pfAp ? ($pfWages * ctrl_num($ctrl, 'pfErPct') / 100.0) : 0.0;
  $esiEE = $esiApplicable ? ($esiWages * ctrl_num($ctrl, 'esiEmpPct') / 100.0) : 0.0;
  $esiER = $esiApplicable ? ($esiWages * ctrl_num($ctrl, 'esiErPct') / 100.0) : 0.0;
  return [
    'calcBase' => round($calcBase, 2),
    'statutoryBase' => round($esiApplicable ? $esiWages : $calcBase, 2),
    'pfBase' => round($pfBase, 2),
    'pfWages' => round($pfWages, 2),
    'esiWages' => round($esiWages, 2),
    'pfEE' => round($pfEE, 2),
    'pfER' => round($pfER, 2),
    'esiEE' => round($esiEE, 2),
    'esiER' => round($esiER, 2),
    'esiLimit' => round($esiLimit, 2),
    'esiApplicable' => $esiApplicable,
  ];
}
function ecr_calc_from_pf_row(array $ctrl, array $pfRow): array {
  $gross = round(f($pfRow['Net_Pay'] ?? 0), 0);
  $pfWagesRaw = f($pfRow['PF_Wages'] ?? 0);
  $pfCapEnabled = ctrl_bool($ctrl, 'pfWageCapEnabled');
  $pfCapAmount = max(0.0, ctrl_num($ctrl, 'pfWageCapAmount'));
  $pfWages = $pfCapEnabled && $pfCapAmount > 0 ? min($pfWagesRaw, $pfCapAmount) : $pfWagesRaw;
  $pfWages = round($pfWages, 0);
  $eps = round($pfWages * 8.33 / 100, 0);
  if($pfCapEnabled && $pfCapAmount > 0){
    $epsCap = round($pfCapAmount * 8.33 / 100, 0);
    if($eps > $epsCap) $eps = $epsCap;
  }
  $employeePf = round(f($pfRow['PF_EE'] ?? 0), 0);
  $employerPf = round(f($pfRow['PF_ER'] ?? 0), 0);
  $epfEpsDiff = round($employerPf - $eps, 0);
  if($epfEpsDiff < 0) $epfEpsDiff = 0.0;
  return [
    'gross' => $gross,
    'pfWages' => $pfWages,
    'eps' => $eps,
    'employeePf' => $employeePf,
    'employerPf' => $employerPf,
    'epfEpsDiff' => $epfEpsDiff,
  ];
}

function payroll_generate(int $m,int $y,string $mode='LOP'): array {
  if (function_exists('app') && app()->bound(\App\Services\Payroll\PayrollGenerator::class)) {
    return app(\App\Services\Payroll\PayrollGenerator::class)->generate($m, $y, $mode);
  }
  $clientId = req_client_id();
  // Dependency rule:
  // Attendance sheet must be generated first, then salary sheet.
  $attIdx = find_period(idx('attendance_sheet_index'), $m, $y);
  if(!$attIdx) bad('Attendance sheet not found for selected month. Generate Attendance Sheet first.');
  // Use the existing generated attendance sheet for this period.
  // Salary generation must not create or overwrite attendance-sheet history.
  $att = get_sheet(idkey('attendance_sheet',(string)$attIdx['id']), 'Attendance sheet not found');
  $rows=$att['rows']??[]; $ctrl=control_get(); $ov=ovr_all(); $dim=(int)cal_days_in_month(CAL_GREGORIAN,$m,$y); $out=[]; $payrollSheetId='PAY-'.period($m,$y).'-'.time();
  $otherDedItems = control_other_deduction_breakup($ctrl);
  $otherDedFixed = 0.0;
  foreach($otherDedItems as $it){ $otherDedFixed += f($it['amount'] ?? 0); }
  $lopMap = lop_leave_days_map($m,$y);
  $otMap = overtime_monthly_map($m,$y);
  $emap=[]; foreach(employees_active_all() as $e){ $emap[up($e['id'] ?? '')] = $e; }
  foreach($rows as $a){
    $eid=up($a['empId']??''); $o=$ov[$eid]??[]; $emp=$emap[$eid] ?? [];
    if(!$emp) continue;
    $lopFromAtt = f($a['LOP']??0);
    $lopBase = array_key_exists($eid, $lopMap) ? f($lopMap[$eid]) : $lopFromAtt;
    $lop=$lopBase+((strtoupper($mode)==='LOP')?f($a['A']??0):0);
    $wo=f($a['WO']??0);
    // Weekly Off is paid: use full month days for payable and LOP ratio.
    $working=max(1.0,$dim);
    $paid=max(0.0,$working-$lop);
    $gross=(isset($o['gross']) && $o['gross']!==null)?f($o['gross']):0;
    $ctc=(isset($o['ctc']) && $o['ctc']!==null)?f($o['ctc']):0;
    $masterCtc=f($emp['baseCtc'] ?? 0);
    $base=$gross>0?$gross:($ctc>0?$ctc:($masterCtc>0?$masterCtc:25000));
    $parts=split_ctc($base,$ctrl); $gross=f($parts['gross']);
    $lopDed=$gross*($lop/$working);
    $earned=max(0.0,$gross-$lopDed);
    $pfAp=($o['pfAppl']??true)===true; $esiAp=($o['esiAppl']??true)===true; $ptAp=($o['ptAppl']??true)===true; $lwfAp=($o['lwfAppl']??true)===true;
    $stat = payroll_statutory_calc($ctrl, $gross, $earned, $pfAp, $esiAp);
    $pfW = f($stat['pfWages'] ?? 0);
    $pfEE = f($stat['pfEE'] ?? 0);
    $pfER = f($stat['pfER'] ?? 0);
    $esiEE = f($stat['esiEE'] ?? 0);
    $esiER = f($stat['esiER'] ?? 0);
    $ptEnabled = b($ctrl['ptEnabled'] ?? 'Yes');
    $pt=($ptAp&&$ptEnabled)?f($ctrl['ptMonthly']??200):0; $lm=(int)f($ctrl['lwfMonth']??0); $lwf=(b($ctrl['lwfEnabled']??'Yes')&&$lwfAp&&($lm===0||$lm===$m))?f($ctrl['lwfEmpAmt']??20):0;
    $otInfo = $otMap[$eid] ?? ['hours'=>0.0,'amount'=>0.0,'entries'=>0];
    $otHours = round(f($otInfo['hours'] ?? 0), 2);
    $otAmount = round(f($otInfo['amount'] ?? 0), 2);
    $incentiveAmount = incentive_total_for_period(db(), $eid, $m, $y);
    $totalEarnings = round($earned + $otAmount + $incentiveAmount, 2);
    $otherDed = $otherDedFixed;
    $dedWithoutAdvance=$pfEE+$esiEE+$pt+$lwf+$otherDed;
    $advanceDed = advance_payroll_apply(db(), $eid, $m, $y, max(0.0, $totalEarnings - $dedWithoutAdvance), $payrollSheetId);
    $advanceAmt = round(f($advanceDed['amount'] ?? 0), 2);
    $loanDed = loan_payroll_apply(db(), $eid, $m, $y, max(0.0, $totalEarnings - $dedWithoutAdvance - $advanceAmt), $payrollSheetId);
    $loanAmt = round(f($loanDed['amount'] ?? 0), 2);
    $ded=$dedWithoutAdvance+$advanceAmt+$loanAmt; $net=$totalEarnings-$ded;
    $workDays=max(0.0,$paid-$wo);
    $out[]=["month"=>period($m,$y),"empId"=>$eid,"empName"=>s($a['empName']??$eid),"dept"=>s($a['dept']??''),"desig"=>s($a['desig']??''),"daysInMonth"=>$dim,"paidDays"=>round($paid,2),"WO"=>round($wo,2),"workDays"=>round($workDays,2),"lopDays"=>round($lop,2),"CL"=>round(f($a['CL']??0),2),"SL"=>round(f($a['SL']??0),2),"EL"=>round(f($a['EL']??0),2),"gross"=>round($gross,2),"earnedGross"=>round($earned,2),"otHours"=>$otHours,"otAmount"=>$otAmount,"otEntries"=>(int)($otInfo['entries'] ?? 0),"incentiveAmount"=>round($incentiveAmount,2),"totalEarnings"=>$totalEarnings,"pfWages"=>round($pfW,2),"pfEE"=>round($pfEE,2),"pfER"=>round($pfER,2),"esiEE"=>round($esiEE,2),"esiER"=>round($esiER,2),"pt"=>round($pt,2),"lwf"=>round($lwf,2),"otherDeductions"=>round($otherDed,2),"otherDeductionItems"=>$otherDedItems,"advanceSalaryDeduction"=>$advanceAmt,"advanceDeductionItems"=>$advanceDed['items'] ?? [],"loanDeduction"=>$loanAmt,"loanDeductionItems"=>$loanDed['items'] ?? [],"totalDeductions"=>round($ded,2),"netPayable"=>round($net,2),"esiEligible"=>!empty($stat['esiApplicable'])];
  }
  $totWage = round(array_sum(array_map(fn($r)=>f($r['gross'] ?? 0), $out)), 2);
  $totPfEe = round(array_sum(array_map(fn($r)=>f($r['pfEE'] ?? 0), $out)), 2);
  $totPfEr = round(array_sum(array_map(fn($r)=>f($r['pfER'] ?? 0), $out)), 2);
  $totOtAmount = round(array_sum(array_map(fn($r)=>f($r['otAmount'] ?? 0), $out)), 2);
  $totOtHours = round(array_sum(array_map(fn($r)=>f($r['otHours'] ?? 0), $out)), 2);
  $sheet = save_sheet('payroll_sheet',$m,$y,$out,["id"=>$payrollSheetId,"totalPfWage"=>$totWage,"totalPfEe"=>$totPfEe,"totalPfEr"=>$totPfEr,"totalOtHours"=>$totOtHours,"totalOtAmount"=>$totOtAmount]);
  mail_sheet_event('payroll_sheet', $clientId, $sheet, 'Salary Sheet', [
    'Total PF Wage' => 'Rs ' . number_format($totWage, 2),
    'Total PF EE' => 'Rs ' . number_format($totPfEe, 2),
    'Total PF ER' => 'Rs ' . number_format($totPfEr, 2),
    'Total OT' => number_format($totOtHours, 2) . ' hrs / Rs ' . number_format($totOtAmount, 2)
  ]);
  return $sheet;
}

function pf_generate(int $m,int $y): array {
  $clientId = req_client_id();
  $p=payroll_resolve($m,$y); $a=attendance_resolve($m,$y); $c=control_get(); $rows=[]; $ov=ovr_all();
  $attMap = [];
  foreach(($a['rows'] ?? []) as $ar){
    $attMap[up($ar['empId'] ?? '')] = $ar;
  }
  foreach(($p['rows']??[]) as $r){
    $eid = up($r['empId'] ?? '');
    $o = $ov[$eid] ?? [];
    $attRow = $attMap[$eid] ?? [];
    $gross=f($r['gross'] ?? 0);
    $earned=f($r['earnedGross'] ?? $gross);
    $pfAp=($o['pfAppl']??true)===true;
    $esiAp=($o['esiAppl']??true)===true;
    $stat = payroll_statutory_calc($c, $gross, $earned, $pfAp, $esiAp);
    $w = f($stat['pfWages'] ?? 0);
    $ee = f($stat['pfEE'] ?? 0);
    $er = f($stat['pfER'] ?? 0);
    $eps = round($ee / 0.0833, 2);
    $basic=($earned * f($c['ctcBasicPct'] ?? 50)) / 100;
    $da=($earned * f($c['ctcDaPct'] ?? 30)) / 100;
    $rows[]=[
      "Month"=>period($m,$y),
      "Emp_ID"=>$r['empId'],
      "Employee_Name"=>$r['empName'],
      "GROSS_WAGES"=>round($earned,2),
      "Basic"=>round($basic,2),
      "DA"=>round($da,2),
      "PF_Wages"=>round($w,2),
      "PF_EE"=>round($ee,2),
      "EPF_CONTRI_REMITTED"=>round($ee,2),
      "EPS_CONTRI_REMITTED"=>$eps,
      "NCP_DAYS"=>round(f($attRow['LOP'] ?? 0),2),
      "PF_ER"=>round($er,2),
      "Net_Pay"=>round(f($r['netPayable']??0),2),
      "Key"=>$r['empId'].'|'.period($m,$y)
    ];
  }
  $sheet = save_sheet('pf_sheet',$m,$y,$rows,["totalWage"=>round(array_sum(array_column($rows,'PF_Wages')),2),"totalEE"=>round(array_sum(array_column($rows,'PF_EE')),2),"totalER"=>round(array_sum(array_column($rows,'PF_ER')),2)]);
  mail_sheet_event('pf_sheet', $clientId, $sheet, 'PF Sheet', ['Total Wage' => 'Rs ' . number_format(f($sheet['totalWage'] ?? 0), 2)]);
  return $sheet;
}
function pf_return_generate(int $m,int $y): array {
  $clientId = req_client_id();
  $it=find_period(idx('pf_sheet_index'),$m,$y); $pf=$it?get_sheet(idkey('pf_sheet',(string)$it['id']),'PF sheet not found'):pf_generate($m,$y); $rows=[];
  $emap=[]; foreach(employees_active_all() as $e){ $emap[up($e['id'] ?? '')] = $e; }
  foreach(($pf['rows']??[]) as $r){
    $eid = up($r['Emp_ID'] ?? '');
    $emp = $emap[$eid] ?? [];
    if(!$emp) continue;
    $rows[]=[
      "Month"=>$r['Month'],
      "Emp_ID"=>$r['Emp_ID'],
      "Employee_Name"=>$r['Employee_Name'],
      "UAN"=>s($emp['uan'] ?? ''),
      "PF_No"=>'PF-'.$eid,
      "PF_Wages"=>$r['PF_Wages'],
      "PF_EE"=>$r['PF_EE'],
      "PF_ER"=>$r['PF_ER'],
      "Total_PF"=>round(f($r['PF_EE'])+f($r['PF_ER']),2)
    ];
  }
  $sheet = save_sheet('pf_return_sheet',$m,$y,$rows,["totalWage"=>round(array_sum(array_column($rows,'PF_Wages')),2),"totalEE"=>round(array_sum(array_column($rows,'PF_EE')),2),"totalER"=>round(array_sum(array_column($rows,'PF_ER')),2),"totalPF"=>round(array_sum(array_column($rows,'Total_PF')),2)]);
  mail_sheet_event('pf_return_sheet', $clientId, $sheet, 'PF Return', ['Total PF' => 'Rs ' . number_format(f($sheet['totalPF'] ?? 0), 2)]);
  return $sheet;
}
function esic_generate(int $m,int $y): array {
  $clientId = req_client_id();
  $p=payroll_resolve($m,$y); $c=control_get(); $rows=[];
  $emap=[]; foreach(employees_active_all() as $e){ $emap[up($e['id'] ?? '')] = $e; }
  // Build latest FNF exit-date map per employee; Last Working Day comes only from FNF.
  $fnfExitByEmp = [];
  foreach(idx('fnf_sheet_index') as $fx){
    $empId = up($fx['empId'] ?? '');
    if($empId === '' || isset($fnfExitByEmp[$empId])) continue;
    $fid = s($fx['id'] ?? '');
    if($fid === '') continue;
    $sheet = kv_get(idkey('fnf_sheet', $fid), null);
    if(!is_array($sheet)) continue;
    $exitDate = s($sheet['exitDate'] ?? '');
    if($exitDate !== '') $fnfExitByEmp[$empId] = $exitDate;
  }
  $sr = 0;
  foreach(($p['rows']??[]) as $r){
    $sr++;
    $eid = up($r['empId'] ?? '');
    $emp = $emap[$eid] ?? [];
    if(!$emp) continue;
    $w=f($r['earnedGross']??0);
    $esiEligible = b($r['esiEligible'] ?? false);
    $esiApplicable = $w > 0 && $esiEligible && f($r['esiEE'] ?? 0) > 0;
    if(!$esiApplicable) continue;
    $gross = f($r['gross'] ?? 0);
    $stat = payroll_statutory_calc($c, $gross, $w, true, true);
    $ee=f($stat['esiEE'] ?? 0);
    $er=f($stat['esiER'] ?? 0);
    $ncp = f($r['lopDays'] ?? 0);
    $ipNoRaw = s($emp['esiNo'] ?? '');
    $ipNo = preg_replace('/\D+/', '', $ipNoRaw);
    if($ipNo === null) $ipNo = '';
    $ipNo = substr($ipNo, 0, 10);
    $ipName = preg_replace('/[^A-Za-z ]+/', '', s($r['empName'] ?? ''));
    if($ipName === null || trim($ipName)==='') $ipName = s($r['empName'] ?? '');
    $paidDays = f($r['paidDays'] ?? 0);
    $reasonCode = 0;
    $lastWorkingDay = '';
    $fnfExit = s($fnfExitByEmp[$eid] ?? '');
    if($fnfExit !== ''){
      $ts = strtotime($fnfExit);
      if($ts !== false){
        $lastWorkingDay = date('d/m/Y', $ts);
      } elseif(preg_match('/^\d{2}[-\/]\d{2}[-\/]\d{4}$/', $fnfExit)){
        // Already in DD/MM/YYYY or DD-MM-YYYY format.
        $lastWorkingDay = $fnfExit;
      }
    }
    $rows[]=[
      "Sr No"=>count($rows) + 1,
      "Month"=>period($m,$y),
      "IP Number"=>$ipNo,
      "IP Name"=>$ipName,
      "No of Days for which wages paid/payable during the month"=>round($paidDays,2),
      "Total Monthly Wages"=>round($w,2),
      "Reason Code for Zero workings days"=> (int)$reasonCode,
      "Last Working Day"=>$lastWorkingDay,
      // Keep legacy keys for existing UI compatibility
      "Emp_ID"=>$r['empId'],
      "Employee_Name"=>$r['empName'],
      "ESI_No"=>$ipNo,
      "IP_No"=>$ipNo,
      "IP_Name"=>$ipName,
      "No_of_Days_Paid"=>round($paidDays,2),
      "Total_Monthly_Wages"=>round($w,2),
      "Reason_Code_Zero_Working_Days"=>(int)$reasonCode,
      "Last_Working_Day"=>$lastWorkingDay,
      "Gross_Wages"=>round($gross,2),
      "ESI_Wages"=>round($w,2),
      "ESI_EE"=>round($ee,2),
      "ESI_ER"=>round($er,2),
      "EE_Contribution"=>round($ee,2),
      "ER_Contribution"=>round($er,2),
      "Total_ESI"=>round($ee+$er,2),
      "NCP_Days"=>round($ncp,2)
    ];
  }
  $sheet = save_sheet('esic_sheet',$m,$y,$rows,["totalWage"=>round(array_sum(array_column($rows,'ESI_Wages')),2),"totalEE"=>round(array_sum(array_column($rows,'ESI_EE')),2),"totalER"=>round(array_sum(array_column($rows,'ESI_ER')),2),"totalESI"=>round(array_sum(array_column($rows,'Total_ESI')),2)]);
  mail_sheet_event('esic_sheet', $clientId, $sheet, 'ESIC Sheet', ['Total ESI' => 'Rs ' . number_format(f($sheet['totalESI'] ?? 0), 2)]);
  return $sheet;
}
function ecr_generate(int $m,int $y): array {
  $clientId = req_client_id();
  $it=find_period(idx('pf_sheet_index'),$m,$y); $pf=$it?get_sheet(idkey('pf_sheet',(string)$it['id']),'PF sheet not found'):pf_generate($m,$y); $rows=[]; $ctrl = control_get();
  foreach(($pf['rows']??[]) as $r){
    $ecr = ecr_calc_from_pf_row($ctrl, $r);

    $rows[]=[
      "UAN"=>"",
      "MEMBER_NAME"=>$r['Employee_Name'],
      "GROSS_WAGES"=>$ecr['gross'],
      "EPF_WAGES"=>$ecr['pfWages'],
      "EPS_WAGES"=>$ecr['pfWages'],
      "EDLI_WAGES"=>$ecr['pfWages'],
      "EPF_CONTRI_REMITTED"=>$ecr['employeePf'],
      "EPS_CONTRI_REMITTED"=>$ecr['eps'],
      "EPF_EPS_DIFF_REMITTED"=>$ecr['epfEpsDiff'],
      "NCP_DAYS"=>0,
      "REFUND_OF_ADVANCES"=>0
    ];
  }
  $sheet = save_sheet('ecr_sheet',$m,$y,$rows,["totalGrossWages"=>round(array_sum(array_column($rows,'GROSS_WAGES')),2),"totalEPFWages"=>round(array_sum(array_column($rows,'EPF_WAGES')),2),"totalEPFContri"=>round(array_sum(array_column($rows,'EPF_CONTRI_REMITTED')),2),"totalEPSContri"=>round(array_sum(array_column($rows,'EPS_CONTRI_REMITTED')),2)]);
  mail_sheet_event('ecr_sheet', $clientId, $sheet, 'ECR Sheet', ['Total EPF Contribution' => 'Rs ' . number_format(f($sheet['totalEPFContri'] ?? 0), 2)]);
  return $sheet;
}
function esic_return_generate(int $m,int $y): array {
  $clientId = req_client_id();
  $it=find_period(idx('esic_sheet_index'),$m,$y); $es=$it?get_sheet(idkey('esic_sheet',(string)$it['id']),'ESIC sheet not found'):esic_generate($m,$y); $rows=$es['rows']??[];
  $sheet = save_sheet('esic_return_sheet',$m,$y,$rows,["totalWage"=>round(array_sum(array_column($rows,'ESI_Wages')),2),"totalEE"=>round(array_sum(array_column($rows,'ESI_EE')),2),"totalER"=>round(array_sum(array_column($rows,'ESI_ER')),2),"totalESI"=>round(array_sum(array_column($rows,'Total_ESI')),2)]);
  mail_sheet_event('esic_return_sheet', $clientId, $sheet, 'ESIC Return', ['Total ESI' => 'Rs ' . number_format(f($sheet['totalESI'] ?? 0), 2)]);
  return $sheet;
}

function fnf_generate(array $p): array {
  $clientId = req_client_id();
  $eid=up($p['empId']??''); $exit=s($p['exitDate']??''); if($eid===''||$exit==='') bad('empId and exitDate are required');
  $resignation=s($p['resignationDate']??($p['resignation_date']??$exit));
  $gross=f($p['gross']??0); $paid=f($p['paidDays']??0); $lop=f($p['lopDays']??0); $el=f($p['elDays']??0); $bonus=f($p['bonus']??0); $adv=f($p['advance']??0); $notice=f($p['notice']??0);
  if($gross <= 0) bad('Gross salary must be greater than 0');
  $exitTs = strtotime($exit);
  $monthDays = 30;
  if($exitTs !== false){
    $monthDays = (int)cal_days_in_month(CAL_GREGORIAN, (int)gmdate('n', $exitTs), (int)gmdate('Y', $exitTs));
  }
  $attCalc = fnf_paid_lop_till_exit($eid, $exit);
  if(is_array($attCalc)){
    $paid = f($attCalc['paidDays'] ?? $paid);
    $lop = f($attCalc['lopDays'] ?? $lop);
    $monthDays = (int)f($attCalc['monthDays'] ?? $monthDays);
  }
  if($monthDays <= 0) $monthDays = 30;
  $ctrl = control_get();
  $otherItems = control_other_deduction_breakup($ctrl);
  $otherTotal = 0.0;
  foreach($otherItems as $it){ $otherTotal += f($it['amount'] ?? 0); }
  $pd=$gross>0?$gross/(float)$monthDays:0.0; $earned=$pd*$paid; $lopDed=$pd*$lop; $enc=$pd*$el;
  $name=$eid; $emp=[];
  foreach(employees_all() as $e){ if($e['id']===$eid){$name=$e['name']; $emp=$e; break;} }
  $gratuityInfo = fnf_gratuity_fetch($eid, $exit);
  $gratuityAmount = f($gratuityInfo['amount'] ?? 0);
  $incentiveAmount = incentive_total_till_date_for_month(db(), $eid, $exit);
  if($bonus <= 0) $bonus = $incentiveAmount;
  $advanceOutstanding = advance_outstanding_for_employee(db(), $eid, $exit);
  $loanOutstanding = loan_outstanding_for_employee(db(), $eid, $exit);
  if($adv <= 0){
    $adv = f($advanceOutstanding['amount'] ?? 0) + f($loanOutstanding['amount'] ?? 0);
  }
  $pfAp = strtolower((string)($emp['pf'] ?? 'yes')) !== 'no';
  $esiAp = strtolower((string)($emp['esi'] ?? 'yes')) !== 'no';
  $ptAp = true;
  $lwfAp = true;
  $stat = payroll_statutory_calc($ctrl, $gross, $earned, $pfAp, $esiAp);
  $pfEE = f($stat['pfEE'] ?? 0);
  $esiEE = f($stat['esiEE'] ?? 0);
  $ptEnabled = b($ctrl['ptEnabled'] ?? 'Yes');
  $pt = ($ptAp && $ptEnabled) ? f($ctrl['ptMonthly'] ?? 200) : 0.0;
  $lwfMonth = (int)f($ctrl['lwfMonth'] ?? 0);
  $exitMonth = $exitTs !== false ? (int)gmdate('n', $exitTs) : 0;
  $lwf = (b($ctrl['lwfEnabled'] ?? 'Yes') && $lwfAp && ($lwfMonth === 0 || $lwfMonth === $exitMonth)) ? f($ctrl['lwfEmpAmt'] ?? 20) : 0.0;
  $noDeductionRuleApplied = $paid < 15.0;
  $advApplied = $adv;
  $noticeApplied = $notice;
  $lopDedApplied = $lopDed;
  $otherApplied = $otherTotal;
  if($noDeductionRuleApplied){
    $pfEE = 0.0; $esiEE = 0.0; $pt = 0.0; $lwf = 0.0;
    $advApplied = 0.0; $noticeApplied = 0.0; $lopDedApplied = 0.0; $otherApplied = 0.0;
  }
  $statutory = $pfEE + $esiEE + $pt + $lwf;
  $te=$earned+$enc+$bonus+$gratuityAmount; $td=$lopDedApplied+$advApplied+$noticeApplied+$otherApplied+$statutory; $final=$te-$td; $id=$eid.'-'.time();
  $row=["id"=>$id,"empId"=>$eid,"employeeName"=>$name,"resignationDate"=>$resignation,"exitDate"=>$exit,"gross"=>round($gross,2),"paidDays"=>round($paid,2),"lopDays"=>round($lop,2),"elDays"=>round($el,2),"bonus"=>round($bonus,2),"incentiveAmount"=>round($incentiveAmount,2),"gratuity"=>round($gratuityAmount,2),"gratuityYears"=>round(f($gratuityInfo['years'] ?? 0),2),"advance"=>round($advApplied,2),"advanceItems"=>$noDeductionRuleApplied?[]:($advanceOutstanding['items'] ?? []),"loanItems"=>$noDeductionRuleApplied?[]:($loanOutstanding['items'] ?? []),"notice"=>round($noticeApplied,2),"otherDeductions"=>round($otherApplied,2),"otherDeductionItems"=>$noDeductionRuleApplied?[]:$otherItems,"pfEE"=>round($pfEE,2),"esiEE"=>round($esiEE,2),"pt"=>round($pt,2),"lwf"=>round($lwf,2),"esiApplicable"=>(!empty($stat['esiApplicable'])&&(!$noDeductionRuleApplied)),"statutoryDeductions"=>round($statutory,2),"noDeductionsRuleApplied"=>$noDeductionRuleApplied,"monthDays"=>$monthDays,"perDay"=>round($pd,2),"earnedGross"=>round($earned,2),"earned"=>round($earned,2),"lopDeduction"=>round($lopDedApplied,2),"leaveEncashment"=>round($enc,2),"totalEarnings"=>round($te,2),"totalDeductions"=>round($td,2),"finalPay"=>round($final,2),"generatedAt"=>now_iso()];
  kv_set(idkey('fnf_sheet',$id),$row); $ix=idx('fnf_sheet_index'); array_unshift($ix,["id"=>$id,"empId"=>$eid,"employeeName"=>$name,"resignationDate"=>$resignation,"exitDate"=>$exit,"finalPay"=>$row['finalPay'],"generatedAt"=>$row['generatedAt']]); kv_set('fnf_sheet_index',array_slice($ix,0,500));
  mail_fnf_event($clientId, $row);
  return $row;
}
function gratuity_generate(array $p): array {
  $clientId = req_client_id();
  $ctrl = control_get();
  $mode = gratuity_mode_norm($ctrl['gratuityMode'] ?? 'after_5yr');
  if($mode === 'monthly'){
    $m = (int)($p['month'] ?? 0);
    $y = (int)($p['year'] ?? 0);
    if($m < 1 || $m > 12 || $y < 2000) bad('month and year are required');
    $rows = [];
    foreach(employees_active_all() as $emp){
      $calc = gratuity_calc_row($emp, $ctrl, 0.0);
      $rows[] = [
        'empId'=>(string)($emp['id'] ?? ''),
        'employeeName'=>(string)($emp['name'] ?? ''),
        'dept'=>(string)($emp['dept'] ?? ''),
        'desig'=>(string)($emp['desig'] ?? ''),
        'basic'=>$calc['basic'],
        'da'=>$calc['da'],
        'gratuityAmount'=>$calc['gratuityAmount']
      ];
    }
    $totalAmount = round(array_sum(array_map(fn($r)=>f($r['gratuityAmount'] ?? 0), $rows)), 2);
    $sheet = save_sheet('gratuity_sheet', $m, $y, $rows, [
      'mode'=>'monthly',
      'modeLabel'=>gratuity_mode_label('monthly'),
      'rowCount'=>count($rows),
      'totalAmount'=>$totalAmount
    ]);
    $sheet['mode'] = 'monthly';
    $sheet['modeLabel'] = gratuity_mode_label('monthly');
    $sheet['totalAmount'] = $totalAmount;
    mail_sheet_event('gratuity_sheet', $clientId, $sheet, 'Gratuity Sheet', ['Mode' => (string)($sheet['modeLabel'] ?? '')]);
    return $sheet;
  }
  $eid = up($p['empId'] ?? '');
  if($eid === '') bad('empId is required');
  $years = f($p['years'] ?? 0);
  $emp = null;
  foreach(employees_all() as $e){ if(($e['id'] ?? '') === $eid){ $emp = $e; break; } }
  if(!$emp) nf('Employee not found');
  $calc = gratuity_calc_row($emp, $ctrl, $years);
  $id = $eid.'-gratuity-'.time();
  $row = [
    'id'=>$id,
    'empId'=>$eid,
    'employeeName'=>(string)($emp['name'] ?? $eid),
    'dept'=>(string)($emp['dept'] ?? ''),
    'desig'=>(string)($emp['desig'] ?? ''),
    'generatedAt'=>now_iso()
  ] + $calc;
  kv_set(idkey('gratuity_sheet',$id), $row);
  $ix = idx('gratuity_sheet_index');
  array_unshift($ix, [
    'id'=>$id,
    'empId'=>$eid,
    'employeeName'=>$row['employeeName'],
    'mode'=>$row['mode'],
    'modeLabel'=>$row['modeLabel'],
    'years'=>$row['years'],
    'gratuityAmount'=>$row['gratuityAmount'],
    'generatedAt'=>$row['generatedAt']
  ]);
  kv_set('gratuity_sheet_index', array_slice($ix, 0, 500));
  mail_sheet_event('gratuity_sheet', $clientId, $row, 'Gratuity Sheet', [
    'Employee ID' => (string)$eid,
    'Employee Name' => (string)$row['employeeName'],
    'Amount' => 'Rs ' . number_format(f($row['gratuityAmount'] ?? 0), 2),
    'Mode' => (string)($row['modeLabel'] ?? '')
  ]);
  return $row;
}
function fnf_gratuity_fetch(string $empId, string $exitDate): array {
  $eid = up($empId);
  $exit = trim($exitDate);
  if($eid === '' || $exit === '') return ['amount'=>0.0,'years'=>0.0,'mode'=>'','sourceId'=>''];
  $ctrl = control_get();
  $mode = gratuity_mode_norm($ctrl['gratuityMode'] ?? 'after_5yr');
  if($mode === 'monthly'){
    $ts = strtotime($exit);
    if($ts === false) return ['amount'=>0.0,'years'=>0.0,'mode'=>'monthly','sourceId'=>''];
    $m = (int)gmdate('n', $ts);
    $y = (int)gmdate('Y', $ts);
    foreach(idx('gratuity_sheet_index') as $it){
      if((int)($it['month'] ?? 0) !== $m || (int)($it['year'] ?? 0) !== $y) continue;
      if(gratuity_mode_norm($it['mode'] ?? '') !== 'monthly') continue;
      $id = s($it['id'] ?? '');
      if($id === '') continue;
      $sheet = kv_get(idkey('gratuity_sheet', $id), null);
      if(!is_array($sheet)) continue;
      foreach(($sheet['rows'] ?? []) as $row){
        if(up($row['empId'] ?? '') !== $eid) continue;
        return ['amount'=>round(f($row['gratuityAmount'] ?? 0),2),'years'=>0.0,'mode'=>'monthly','sourceId'=>$id];
      }
    }
    return ['amount'=>0.0,'years'=>0.0,'mode'=>'monthly','sourceId'=>''];
  }
  foreach(idx('gratuity_sheet_index') as $it){
    if(up($it['empId'] ?? '') !== $eid) continue;
    if(gratuity_mode_norm($it['mode'] ?? '') !== 'after_5yr') continue;
    return ['amount'=>round(f($it['gratuityAmount'] ?? 0),2),'years'=>round(f($it['years'] ?? 0),2),'mode'=>'after_5yr','sourceId'=>s($it['id'] ?? '')];
  }
  return ['amount'=>0.0,'years'=>0.0,'mode'=>'after_5yr','sourceId'=>''];
}
function bonus_control_defaults(array $ctrl): array {
  $enabled = strtolower((string)($ctrl['bonusEnabled'] ?? 'Yes')) !== 'no';
  $minimumWage = round(max(0.0, f($ctrl['bonusMinimumWage'] ?? 0)), 2);
  $months = round(max(0.0, f($ctrl['bonusMultiplierMonths'] ?? 12)), 2);
  $percent = round(max(0.0, f($ctrl['bonusPercent'] ?? 0)), 2);
  return [
    'enabled'=>$enabled,
    'minimumWage'=>$minimumWage,
    'multiplierMonths'=>$months,
    'bonusPercent'=>$percent
  ];
}
function bonus_calc_amount(float $minimumWage, float $months, float $bonusPct): float {
  return round(max(0.0, $minimumWage) * max(0.0, $months) * max(0.0, $bonusPct) / 100.0, 2);
}
function bonus_generate_preview(int $m,int $y): array {
  if($m < 1 || $m > 12 || $y < 2000) bad('month and year are required');
  $ctrl = bonus_control_defaults(control_get());
  if(!$ctrl['enabled']) bad('Bonus module is disabled in control page');
  $rows = [];
  foreach(employees_active_all() as $emp){
    $rows[] = [
      'empId'=>(string)($emp['id'] ?? ''),
      'employeeName'=>(string)($emp['name'] ?? ''),
      'dept'=>(string)($emp['dept'] ?? ''),
      'desig'=>(string)($emp['desig'] ?? ''),
      'minimumWage'=>$ctrl['minimumWage'],
      'multiplierMonths'=>$ctrl['multiplierMonths'],
      'bonusPct'=>$ctrl['bonusPercent'],
      'bonusAmount'=>bonus_calc_amount($ctrl['minimumWage'], $ctrl['multiplierMonths'], $ctrl['bonusPercent'])
    ];
  }
  return ['month'=>$m,'year'=>$y,'period'=>period($m,$y),'defaults'=>$ctrl,'rows'=>$rows];
}
function bonus_rows_norm(array $rows): array {
  $out = [];
  foreach($rows as $r){
    $mw = round(max(0.0, f($r['minimumWage'] ?? 0)), 2);
    $months = round(max(0.0, f($r['multiplierMonths'] ?? 0)), 2);
    $pct = round(max(0.0, f($r['bonusPct'] ?? 0)), 2);
    $out[] = [
      'empId'=>up($r['empId'] ?? ''),
      'employeeName'=>s($r['employeeName'] ?? ''),
      'dept'=>s($r['dept'] ?? ''),
      'desig'=>s($r['desig'] ?? ''),
      'minimumWage'=>$mw,
      'multiplierMonths'=>$months,
      'bonusPct'=>$pct,
      'bonusAmount'=>bonus_calc_amount($mw, $months, $pct)
    ];
  }
  return $out;
}
function bonus_save_sheet(array $p): array {
  $clientId = req_client_id();
  $m = (int)($p['month'] ?? 0);
  $y = (int)($p['year'] ?? 0);
  if($m < 1 || $m > 12 || $y < 2000) bad('month and year are required');
  $rows = bonus_rows_norm(is_array($p['rows'] ?? null) ? $p['rows'] : []);
  $total = round(array_sum(array_map(fn($r)=>f($r['bonusAmount'] ?? 0), $rows)), 2);
  $sheet = save_sheet('bonus_sheet', $m, $y, $rows, ['rowCount'=>count($rows), 'totalBonus'=>$total]);
  mail_sheet_event('bonus_sheet', $clientId, $sheet, 'Bonus Sheet', ['Total Bonus' => 'Rs ' . number_format($total, 2)]);
  return $sheet;
}
function payslip_generate(int $m,int $y,string $eid,string $fmt='html'): array {
  $clientId = req_client_id();
  $eid=up($eid);
  if($eid==='') bad('empId is required');
  // Payslip must be generated only from an existing payroll/salary sheet.
  // Do not auto-generate or overwrite payroll when generating a payslip.
  $it=find_period(idx('payroll_sheet_index'),$m,$y);
  if(!$it) nf('Payroll sheet not found for selected month. Generate salary sheet first.');
  $p=get_sheet(idkey('payroll_sheet',(string)$it['id']), 'Payroll sheet not found');
  $row=null;
  foreach(($p['rows']??[]) as $r){ if(up($r['empId']??'')===$eid){$row=$r; break;} }
  if(!$row) nf('Employee not found in payroll sheet for selected month');
  $ctrl=control_get(); $emp=null; foreach(employees_all() as $e){ if($e['id']===$eid){$emp=$e; break;} } if(!$emp) nf('Employee not found');
  $dim=f($row['daysInMonth']??cal_days_in_month(CAL_GREGORIAN,$m,$y)); $paid=f($row['paidDays']??0); $lop=f($row['lopDays']??0); $gross=f($row['gross']??0); $earned=f($row['earnedGross']??0); $otHours=round(f($row['otHours']??0),2); $otAmount=round(f($row['otAmount']??0),2); $incentiveAmount=round(f($row['incentiveAmount']??0),2); $ratio=$dim>0?max(0.0,min(1.0,$paid/$dim)):0;
  $sp=split_ctc($gross,$ctrl); $lopDed=max(0.0,$gross-$earned); $adjustedGross=max(0.0,$earned);
  $earnRaw=[
    ["label"=>"Basic","amount"=>round($sp['basic']*$ratio,2)],
    ["label"=>"HRA","amount"=>round($sp['hra']*$ratio,2)],
    ["label"=>"Conveyance","amount"=>round($sp['convey']*$ratio,2)],
    ["label"=>"DA","amount"=>round(f($sp['da']??0)*$ratio,2)],
    ["label"=>"Educational Allowance","amount"=>round($sp['edu']*$ratio,2)],
    ["label"=>"Special Allowance","amount"=>round($sp['special']*$ratio,2)]
  ];
  $earn = array_values(array_filter($earnRaw, fn($x)=>f($x['amount'] ?? 0) > 0));
  if($otAmount > 0){
    $earn[] = ["label"=>"Overtime (".$otHours." hrs)","amount"=>$otAmount];
  }
  if($incentiveAmount > 0){
    $earn[] = ["label"=>"Incentive","amount"=>$incentiveAmount];
  }
  $dedRaw=[
    ["label"=>"PF (Employee)","amount"=>round(f($row['pfEE']??0),2)],
    ["label"=>"ESI (Employee)","amount"=>round(f($row['esiEE']??0),2)],
    ["label"=>"Professional Tax","amount"=>round(f($row['pt']??0),2)],
    ["label"=>"LWF","amount"=>round(f($row['lwf']??0),2)]
  ];
  $ded = array_values(array_filter($dedRaw, fn($x)=>f($x['amount'] ?? 0) > 0));
  $advanceDeduction = round(f($row['advanceSalaryDeduction'] ?? 0),2);
  if($advanceDeduction > 0){
    $ded[] = ["label"=>"Advance Salary","amount"=>$advanceDeduction];
  }
  $loanDeduction = round(f($row['loanDeduction'] ?? 0),2);
  if($loanDeduction > 0){
    $ded[] = ["label"=>"Loan EMI","amount"=>$loanDeduction];
  }
  foreach(($row['otherDeductionItems'] ?? []) as $it){
    if(!is_array($it)) continue;
    $nm = s($it['name'] ?? '');
    if($nm === '') continue;
    $amt = round(f($it['amount'] ?? 0),2);
    if($amt <= 0) continue;
    $ded[] = ["label"=>$nm,"amount"=>$amt];
  }
  $totE=round(array_sum(array_column($earn,'amount')),2); $totD=round(array_sum(array_column($ded,'amount')),2); $net=round(f($row['netPayable']??0),2); $mk=period($m,$y); $id=$mk.'-'.$eid.'-'.time();
  $sheet=["id"=>$id,"month"=>$m,"year"=>$y,"monthKey"=>$mk,"empId"=>$eid,"employeeName"=>$emp['name'],"status"=>"success","format"=>strtolower(trim($fmt))?:'html',"generatedOn"=>now_iso(),"data"=>["key"=>$mk.$eid,"grossSalary"=>round($gross,2),"lopDeduction"=>round($lopDed,2),"adjustedGrossSalary"=>round($adjustedGross,2),"otHours"=>$otHours,"otAmount"=>$otAmount,"incentiveAmount"=>$incentiveAmount,"company"=>["name"=>$ctrl['companyName'],"address"=>$ctrl['companyAddress'],"contact"=>$ctrl['companyContact'],"reg"=>$ctrl['companyRegNo'],"pan"=>$ctrl['companyPAN'],"tan"=>$ctrl['companyTAN'],"gstin"=>$ctrl['companyGSTIN']],"employee"=>["name"=>$emp['name'],"uan"=>$emp['uan'],"designation"=>$emp['desig'],"pfNo"=>s($emp['pfNo'] ?? '', 'PF-'.$eid),"department"=>$emp['dept'],"esiNo"=>$emp['esiNo'],"doj"=>$emp['doj'],"bankName"=>s($emp['bankName'] ?? '', ''),"bankAc"=>s($emp['bankAc'] ?? '', ''),"payableDays"=>round($paid,2),"lopDays"=>round($lop,2)],"earnings"=>$earn,"deductions"=>$ded,"totals"=>["earnings"=>$totE,"deductions"=>$totD,"netPay"=>$net]]];
  kv_set(idkey('payslip',$id),$sheet); $ix=idx('payslip_index'); array_unshift($ix,["id"=>$id,"month"=>$m,"year"=>$y,"monthKey"=>$mk,"empId"=>$eid,"employeeName"=>$emp['name'],"generatedOn"=>$sheet['generatedOn'],"status"=>'success',"format"=>$sheet['format'],"key"=>$mk.$eid,"netPay"=>$net]); kv_set('payslip_index',array_slice($ix,0,1000));
  mail_payslip_event($clientId, $sheet);
  return $sheet;
}

function compliance_defaults(int $m,int $y): array {
  $ld=(int)cal_days_in_month(CAL_GREGORIAN,$m,$y); $pf=find_period(idx('pf_return_sheet_index'),$m,$y)!==null; $es=find_period(idx('esic_return_sheet_index'),$m,$y)!==null; $py=find_period(idx('payroll_sheet_index'),$m,$y)!==null;
  return [["dueDate"=>sprintf('%04d-%02d-%02d',$y,$m,min(15,$ld)),"task"=>'ESI Return / Payment (Monthly)',"status"=>$es?'Completed':'Pending',"action"=>'View',"notes"=>''],["dueDate"=>sprintf('%04d-%02d-%02d',$y,$m,min(15,$ld)),"task"=>'PF ECR Preparation (Monthly)',"status"=>$pf?'Completed':($py?'In Progress':'Pending'),"action"=>'View',"notes"=>''],["dueDate"=>sprintf('%04d-%02d-%02d',$y,$m,min(20,$ld)),"task"=>'Professional Tax (if applicable)',"status"=>$py?'In Progress':'Pending',"action"=>'View',"notes"=>''],["dueDate"=>sprintf('%04d-%02d-%02d',$y,$m,$ld),"task"=>'LWF Deduction Review (if applicable)',"status"=>$py?'Completed':'Pending',"action"=>'View',"notes"=>'']];
}
function compliance_list(int $m,int $y): array { $x=kv_get('compliance_'.period($m,$y),null); return is_array($x)?$x:compliance_defaults($m,$y); }
function compliance_save(int $m,int $y,array $rows): array { $o=[]; foreach($rows as $r){ $o[]=["dueDate"=>s($r['dueDate']??''),"task"=>s($r['task']??''),"status"=>s($r['status']??'Pending','Pending'),"action"=>s($r['action']??'View','View'),"notes"=>s($r['notes']??'')]; } kv_set('compliance_'.period($m,$y),$o); return $o; }
function compliance_reset(int $m,int $y): array { $x=compliance_defaults($m,$y); kv_set('compliance_'.period($m,$y),$x); return $x; }

function dashboard_summary(int $m,int $y): array {
  $emps=employees_all(); $active=array_values(array_filter($emps,fn($e)=>strtolower((string)$e['status'])!=='inactive')); $pfc=count(array_filter($active,fn($e)=>strtolower((string)$e['pf'])==='yes')); $esic=count(array_filter($active,fn($e)=>strtolower((string)$e['esi'])==='yes'));
  $pit=find_period(idx('payroll_sheet_index'),$m,$y); $prows=[]; if($pit){ $sh=kv_get(idkey('payroll_sheet',(string)$pit['id']),null); if(is_array($sh)&&is_array($sh['rows']??null)) $prows=$sh['rows']; }
  $cnt=count($prows)?:count($active); $avg=count($prows)?round(array_sum(array_map(fn($r)=>f($r['paidDays']??0),$prows))/count($prows),1):0.0; $gross=round(array_sum(array_map(fn($r)=>f($r['earnedGross']??0),$prows)),2); $ded=round(array_sum(array_map(fn($r)=>f($r['totalDeductions']??0),$prows)),2); $net=round(array_sum(array_map(fn($r)=>f($r['netPayable']??0),$prows)),2);
  $ps=0; foreach(idx('payslip_index') as $p){ if((int)($p['month']??0)===$m && (int)($p['year']??0)===$y) $ps++; }
  $period=period($m,$y); $alerts=compliance_defaults($m,$y);
  $act=[]; foreach([['attendance_sheet_index','Attendance generated','Attendance sheet'],['payroll_sheet_index','Payroll generated','Payroll sheet'],['pf_sheet_index','PF Sheet generated','PF sheet'],['esic_sheet_index','ESIC Sheet generated','ESIC sheet']] as $a){ foreach(idx($a[0]) as $r){ if(($r['period']??'')===$period) $act[]=["title"=>$a[1],"detail"=>$a[2]." for ".$period,"at"=>$r['generatedAt']??now_iso()]; } }
  usort($act,fn($x,$y2)=>strcmp((string)$y2['at'],(string)$x['at'])); $act=array_slice($act,0,8);
  $pr=profile_get(); return ["period"=>$period,"companyName"=>$pr['companyName']??'Company',"employees"=>$cnt,"avgPaidDays"=>$avg,"gross"=>$gross,"deductions"=>$ded,"pfCount"=>$pfc,"esiCount"=>$esic,"netTotal"=>$net,"payslipCount"=>$ps,"alerts"=>$alerts,"activity"=>$act];
}

function legacy_api_dispatch(string $path, string $method): void {
$m = $method;

$publicApi = [
  '/api/health' => true,
  '/api/auth/login' => true,
  '/api/auth/forgot' => true,
  '/api/auth/session' => true,
  '/api/public-enquiries' => true
];
if(!isset($publicApi[$path])){
  auth_ctx(true);
  $adminPrefixes = [
    '/api/clients',
    '/api/access-control',
    '/api/access-types',
    '/api/subscriptions',
    '/api/subscription-plans'
  ];
  foreach($adminPrefixes as $pref){
    if(str_starts_with($path, $pref)){
      require_super_admin();
      break;
    }
  }
}

if($path==='/api/health') j(['status'=>'ok']);
if($path==='/api/dashboard/summary'){ meth('GET'); j(dashboard_summary((int)($_GET['month']??date('n')),(int)($_GET['year']??date('Y')))); }
if($path==='/api/auth/login'){ meth('POST'); $b=body(); j(auth_login((string)($b['username']??''),(string)($b['password']??''))); }
if($path==='/api/auth/forgot'){ meth('POST'); j(auth_forgot(body())); }
if($path==='/api/auth/session'){
  meth('GET');
  $ctx = auth_ctx(false);
  if(!$ctx) j(['valid'=>false]);
  j([
    'valid'=>true,
    'user'=>[
      'username'=>(string)($ctx['username'] ?? $ctx['sub'] ?? ''),
      'name'=>(string)($ctx['name'] ?? $ctx['username'] ?? ''),
      'role'=>(string)($ctx['role'] ?? ''),
      'clientId'=>(int)($ctx['clientId'] ?? 0),
      'empId'=>(string)($ctx['empId'] ?? '')
    ]
  ]);
}
if($path==='/api/public-enquiries'){ meth('POST'); j(['row'=>public_enquiry_create(body())],201); }
if($path==='/api/overtime'){
  if($m==='GET'){ $ctx=overtime_view_ctx(); $rows=overtime_rows($ctx); j(['rows'=>$rows,'stats'=>overtime_stats($rows)]); }
  if($m==='POST'){ j(['row'=>overtime_create(body())],201); }
  j(['detail'=>'Method Not Allowed'],405);
}
if($path==='/api/overtime/clear'){ overtime_manage_ctx(); meth('POST'); db()->exec("DELETE FROM overtime_entries"); invalidate_salary_dependent_sheets(); j(['status'=>'cleared']); }
if(preg_match('#^/api/overtime/([^/]+)$#',$path,$mm)){
  if($m==='DELETE'){ overtime_delete(urldecode((string)$mm[1])); j(['status'=>'deleted']); }
  j(['detail'=>'Method Not Allowed'],405);
}
if($path==='/api/admin-enquiries'){ if($m==='GET') j(['rows'=>admin_enquiries_all()]); if($m==='POST') j(['row'=>admin_enquiry_create(body())],201); j(['detail'=>'Method Not Allowed'],405); }
if(preg_match('#^/api/admin-enquiries/(\d+)$#',$path,$mm)){ $id=(int)$mm[1]; if($m==='PUT') j(['row'=>admin_enquiry_update($id, body())]); if($m==='DELETE'){ admin_enquiry_delete($id); j(['status'=>'deleted']); } j(['detail'=>'Method Not Allowed'],405); }
if($path==='/api/admin-smtp-settings'){ require_super_admin(); if($m==='GET') j(['row'=>smtp_settings_get()]); if($m==='PUT') j(['row'=>smtp_settings_put(body())]); j(['detail'=>'Method Not Allowed'],405); }
if($path==='/api/admin-smtp-settings/test'){ require_super_admin(); meth('POST'); j(smtp_test_send(body())); }
if($path==='/api/attendance-statuses'){
  if($m==='GET') j(['rows'=>attendance_status_rows(b($_GET['activeOnly'] ?? false))]);
  require_super_admin();
  if($m==='POST') j(['row'=>attendance_status_upsert(body(), false)]);
  j(['detail'=>'Method Not Allowed'],405);
}
if(preg_match('#^/api/attendance-statuses/([^/]+)$#',$path,$mm)){
  require_super_admin();
  $code=up(urldecode($mm[1]));
  if($m==='PUT'){ $b=body(); $b['code']=$code; j(['row'=>attendance_status_upsert($b, true)]);}
  if($m==='DELETE'){ attendance_status_delete($code); j(['status'=>'deleted']); }
  j(['detail'=>'Method Not Allowed'],405);
}
if($path==='/api/employee-types'){
  if($m==='GET') j(['rows'=>employee_type_rows(b($_GET['activeOnly'] ?? false))]);
  require_super_admin();
  if($m==='POST') j(['row'=>employee_type_upsert(body(), false)]);
  j(['detail'=>'Method Not Allowed'],405);
}
if(preg_match('#^/api/employee-types/([^/]+)$#',$path,$mm)){
  require_super_admin();
  $code=up(urldecode($mm[1]));
  if($m==='PUT'){ $b=body(); $b['code']=$code; j(['row'=>employee_type_upsert($b, true)]);}
  if($m==='DELETE'){ employee_type_delete($code); j(['status'=>'deleted']); }
  j(['detail'=>'Method Not Allowed'],405);
}

if($path==='/api/control'){ if($m==='GET') j(control_get()); if($m==='PUT') j(control_put(body())); j(['detail'=>'Method Not Allowed'],405); }
if($path==='/api/control/reset'){ meth('POST'); j(control_put(DEFAULT_CONTROL)); }
if($path==='/api/profile'){ if($m==='GET') j(profile_get()); if($m==='PUT') j(profile_put(body())); j(['detail'=>'Method Not Allowed'],405); }
if($path==='/api/profile/reset'){ meth('POST'); j(profile_put(DEFAULT_PROFILE)); }
if($path==='/api/clients'){ if($m==='GET') j(['rows'=>clients_all()]); if($m==='POST') j(['row'=>client_upsert(body(),false)]); j(['detail'=>'Method Not Allowed'],405); }
if($path==='/api/clients/clear'){ meth('POST'); central_db()->exec("DELETE FROM clients"); central_db()->exec("DELETE FROM client_access"); j(['status'=>'cleared']); }
if(preg_match('#^/api/clients/(\d+)$#',$path,$mm)){ $id=(int)$mm[1]; if($m==='PUT'){ $b=body(); $b['id']=$id; j(['row'=>client_upsert($b,true)]);} if($m==='DELETE'){ client_delete($id); j(['status'=>'deleted']); } j(['detail'=>'Method Not Allowed'],405); }
if(preg_match('#^/api/access-control/(\d+)$#',$path,$mm)){ $id=(int)$mm[1]; if($m==='GET') j(['row'=>access_get($id)]); if($m==='PUT') j(['row'=>access_put($id, body())]); j(['detail'=>'Method Not Allowed'],405); }
if($path==='/api/access-types'){ if($m==='GET') j(['rows'=>access_type_rows()]); if($m==='POST') j(['row'=>access_type_create(body())]); j(['detail'=>'Method Not Allowed'],405); }
if(preg_match('#^/api/access-types/([^/]+)$#',$path,$mm)){ $code=strtolower(urldecode($mm[1])); if($m==='PUT') j(['row'=>access_type_update($code, body())]); if($m==='DELETE'){ access_type_delete($code); j(['status'=>'deleted']); } j(['detail'=>'Method Not Allowed'],405); }
if($path==='/api/staff-roles'){
  $cid = req_client_id();
  if($cid <= 0) bad('clientId is required');
  if($m==='GET') j(['rows'=>staff_role_rows($cid)]);
  if($m==='POST') j(['row'=>staff_role_create($cid, body())]);
  j(['detail'=>'Method Not Allowed'],405);
}
if(preg_match('#^/api/staff-roles/([^/]+)$#',$path,$mm)){
  $cid = req_client_id();
  if($cid <= 0) bad('clientId is required');
  $code = strtolower(urldecode($mm[1]));
  if($m==='PUT') j(['row'=>staff_role_update($cid, $code, body())]);
  if($m==='DELETE'){ staff_role_delete($cid, $code); j(['status'=>'deleted']); }
  j(['detail'=>'Method Not Allowed'],405);
}
if($path==='/api/staff-users'){
  $cid = req_client_id();
  if($cid <= 0) bad('clientId is required');
  if($m==='GET') j(['rows'=>staff_user_rows($cid)]);
  j(['detail'=>'Method Not Allowed'],405);
}
if(preg_match('#^/api/staff-users/([^/]+)$#',$path,$mm)){
  $cid = req_client_id();
  if($cid <= 0) bad('clientId is required');
  $empId = urldecode($mm[1]);
  if($m==='PUT') j(['row'=>staff_user_upsert($cid, $empId, body())]);
  if($m==='DELETE'){ staff_user_delete($cid, $empId); j(['status'=>'deleted']); }
  j(['detail'=>'Method Not Allowed'],405);
}
if($path==='/api/subscriptions'){ if($m==='GET') j(['rows'=>subscriptions_all()]); if($m==='POST') j(['row'=>subscription_upsert(body(),false)]); j(['detail'=>'Method Not Allowed'],405); }
if(preg_match('#^/api/subscriptions/(\d+)$#',$path,$mm)){ $id=(int)$mm[1]; if($m==='PUT'){ $b=body(); $b['id']=$id; j(['row'=>subscription_upsert($b,true)]);} if($m==='DELETE'){ subscription_delete($id); j(['status'=>'deleted']); } j(['detail'=>'Method Not Allowed'],405); }
if($path==='/api/subscription-plans'){ if($m==='GET') j(['rows'=>plans_all()]); if($m==='POST') j(['row'=>plan_upsert(body(),false)]); j(['detail'=>'Method Not Allowed'],405); }
if(preg_match('#^/api/subscription-plans/(\d+)$#',$path,$mm)){ $id=(int)$mm[1]; if($m==='PUT'){ $b=body(); $b['id']=$id; j(['row'=>plan_upsert($b,true)]);} if($m==='DELETE'){ plan_delete($id); j(['status'=>'deleted']); } j(['detail'=>'Method Not Allowed'],405); }
if($path==='/api/subscription-info'){ meth('GET'); j(subscription_info_get()); }
if($path==='/api/client-access-template'){ meth('GET'); j(client_access_template_get()); }
if($path==='/api/client-billing'){ meth('GET'); j(client_billing_get()); }
if($path==='/api/client-invoices'){ meth('GET'); j(client_invoices_get()); }

if($path==='/api/employees'){ if($m==='GET') j(['rows'=>b($_GET['activeOnly'] ?? false) ? employees_active_all() : employees_all()]); if($m==='POST') j(['row'=>emp_upsert(body(),false)]); j(['detail'=>'Method Not Allowed'],405); }
if($path==='/api/employees/clear'){ meth('POST'); db()->exec("DELETE FROM employees"); invalidate_salary_dependent_sheets(); j(['status'=>'cleared']); }
if($path==='/api/employees/bulk-upsert'){ meth('POST'); $b=body(); $saved=[]; foreach(($b['rows']??[]) as $r){ $saved[]=emp_upsert((array)$r,null);} j(['rows'=>$saved,'count'=>count($saved)]); }
if(preg_match('#^/api/employees/([^/]+)$#',$path,$mm)){ $id=urldecode($mm[1]); if($m==='PUT'){ $b=body(); $b['id']=$id; j(['row'=>emp_upsert($b,true)]);} if($m==='DELETE'){ emp_delete($id); j(['status'=>'deleted']); } j(['detail'=>'Method Not Allowed'],405); }

if($path==='/api/leaves'){ if($m==='GET') j(['rows'=>leaves_list(isset($_GET['month'])?(int)$_GET['month']:null,isset($_GET['year'])?(int)$_GET['year']:null,$_GET['leaveType']??null,$_GET['status']??null)]); if($m==='POST') j(['row'=>leave_upsert(body(),false)]); j(['detail'=>'Method Not Allowed'],405); }
if($path==='/api/leaves/clear'){ meth('POST'); db()->exec("DELETE FROM leaves"); j(['status'=>'cleared']); }
if($path==='/api/leaves/bulk-upsert'){ meth('POST'); $b=body(); $saved=[]; foreach(($b['rows']??[]) as $r){ $saved[]=leave_upsert((array)$r,null);} j(['rows'=>$saved,'count'=>count($saved)]); }
if($path==='/api/leaves/summary'){ meth('GET'); $x=(int)($_GET['month']??0); $y=(int)($_GET['year']??0); if($x<1||$x>12||$y<2000) bad('month/year required'); j(['rows'=>leaves_summary($x,$y)]); }
if(preg_match('#^/api/leaves/(\d+)$#',$path,$mm)){ $id=(int)$mm[1]; if($m==='PUT'){ $b=body(); $b['id']=$id; j(['row'=>leave_upsert($b,true)]);} if($m==='DELETE'){ leave_delete($id); j(['status'=>'deleted']); } j(['detail'=>'Method Not Allowed'],405); }

if($path==='/api/advances'){
  if($m==='GET'){ $ctx=advance_view_ctx(); j(['rows'=>advance_rows($ctx, b($_GET['outstanding'] ?? false))]); }
  if($m==='POST'){ j(['row'=>advance_create(body())],201); }
  j(['detail'=>'Method Not Allowed'],405);
}
if($path==='/api/advances/eligibility'){
  meth('GET');
  advance_view_ctx();
  $empId = up($_GET['empId'] ?? '');
  $date = s($_GET['date'] ?? gmdate('Y-m-d'));
  j(['row'=>advance_eligibility($empId, $date)]);
}
if($path==='/api/advances/history'){ meth('GET'); j(['rows'=>advance_history_rows(advance_view_ctx())]); }
if(preg_match('#^/api/advances/([^/]+)$#',$path,$mm)){
  if($m==='GET'){
    $ctx=advance_view_ctx(); $row=advance_fetch_one(db(), urldecode((string)$mm[1])); if(!$row) nf('Advance not found');
    $scope=advance_emp_scope($ctx); if($scope!=='' && $scope!==up($row['empId'] ?? '')) j(['detail'=>'Forbidden'],403);
    j(['row'=>$row]);
  }
  if($m==='DELETE'){ advance_delete(urldecode((string)$mm[1])); j(['status'=>'deleted']); }
  j(['detail'=>'Method Not Allowed'],405);
}
if($path==='/api/loans'){
  if($m==='GET') j(['rows'=>loan_rows()]);
  if($m==='POST') j(['row'=>loan_create_or_update(body(), null)],201);
  j(['detail'=>'Method Not Allowed'],405);
}
if(preg_match('#^/api/loans/([^/]+)$#',$path,$mm)){
  $loanId = urldecode((string)$mm[1]);
  if($m==='GET'){
    loan_view_ctx();
    $row = loan_fetch_one(db(), $loanId);
    if(!$row) nf('Loan not found');
    j(['row'=>$row]);
  }
  if($m==='PUT'){
    j(['row'=>loan_create_or_update(body(), $loanId)]);
  }
  if($m==='DELETE'){
    loan_delete($loanId);
    j(['status'=>'deleted']);
  }
  j(['detail'=>'Method Not Allowed'],405);
}

if($path==='/api/attendance/daily'){ meth('GET'); $x=(int)($_GET['month']??0); $y=(int)($_GET['year']??0); if($x<1||$x>12||$y<2000) bad('month/year required'); j(['rows'=>att_daily_list($x,$y)]); }
if($path==='/api/attendance/daily/upsert'){ meth('POST'); $b=body(); $x=(int)($b['month']??0); $y=(int)($b['year']??0); if($x<1||$x>12||$y<2000) bad('month/year required'); j(['status'=>'ok']+att_daily_upsert($x,$y,$b['records']??[])); }
if($path==='/api/attendance/generate'){ meth('POST'); $b=body(); $x=(int)($b['month']??0); $y=(int)($b['year']??0); if($x<1||$x>12||$y<2000) bad('month/year required'); j(['sheet'=>att_generate($x,$y,(bool)($b['fillDefault']??true),(bool)($b['sundayWeeklyOff']??true))]); }
if($path==='/api/attendance/sheets'){ meth('GET'); j(['rows'=>idx('attendance_sheet_index')]); }
if(preg_match('#^/api/attendance/sheets/([^/]+)$#',$path,$mm) && strtolower((string)$mm[1])!=='clear'){ if($m==='GET') j(['sheet'=>get_sheet(idkey('attendance_sheet',$mm[1]),'Attendance sheet not found')]); if($m==='DELETE'){ del_sheet('attendance_sheet',$mm[1]); j(['status'=>'deleted']); } j(['detail'=>'Method Not Allowed'],405); }
if($path==='/api/attendance/sheets/clear'){ meth('POST'); clr_sheet('attendance_sheet'); j(['status'=>'cleared']); }
if($path==='/api/attendance/clear'){
  meth('POST');
  clr_sheet('attendance_sheet');
  db()->exec("DELETE FROM app_kv WHERE key LIKE 'attendance_daily_%'");
  j(['status'=>'cleared']);
}
if($path==='/api/face-attendance/settings'){
  if($m==='GET') j(['row'=>face_attendance_settings_get()]);
  face_attendance_manage_ctx();
  if($m==='PUT') j(['row'=>face_attendance_settings_put(body())]);
  j(['detail'=>'Method Not Allowed'],405);
}
if($path==='/api/face-attendance/registrations'){
  $ctx = face_attendance_view_ctx();
  $scope = face_attendance_emp_scope($ctx);
  $empId = $scope !== '' ? $scope : up($_GET['employeeId'] ?? '');
  j(['rows'=>face_attendance_registration_rows($empId !== '' ? $empId : null)]);
}
if(preg_match('#^/api/face-attendance/registrations/([^/]+)$#', $path, $mm)){
  if($m==='DELETE'){ face_attendance_delete_registration(urldecode((string)$mm[1])); j(['status'=>'deleted']); }
  j(['detail'=>'Method Not Allowed'],405);
}
if($path==='/api/face-attendance/register'){
  meth('POST');
  j(['row'=>face_attendance_register(body())], 201);
}
if($path==='/api/face-attendance/scan'){
  meth('POST');
  j(face_attendance_scan(body()));
}
if($path==='/api/face-attendance/sheet'){
  meth('GET');
  $ctx = face_attendance_view_ctx();
  j(['rows'=>face_attendance_sheet_rows($_GET, $ctx)]);
}
if($path==='/api/face-attendance/report'){
  meth('GET');
  $ctx = face_attendance_view_ctx();
  j(['rows'=>face_attendance_report_rows($_GET, $ctx)]);
}
if(preg_match('#^/api/face-attendance/attendance/(\d+)$#', $path, $mm)){
  $id = (int)$mm[1];
  if($m==='GET'){
    face_attendance_view_ctx();
    $row = face_attendance_fetch_one($id);
    if(!$row) nf('Attendance record not found');
    j(['row'=>$row]);
  }
  if($m==='PUT') j(['row'=>face_attendance_update_record($id, body())]);
  if($m==='DELETE'){ face_attendance_delete_record($id); j(['status'=>'deleted']); }
  j(['detail'=>'Method Not Allowed'],405);
}
if($path==='/api/face-attendance/my-attendance'){
  meth('GET');
  $ctx = face_attendance_view_ctx();
  if(face_attendance_emp_scope($ctx) === '') j(['detail'=>'Only employee login can use this endpoint'],403);
  j(['rows'=>face_attendance_sheet_rows($_GET, $ctx)]);
}

if($path==='/api/payroll/overrides'){ meth('GET'); j(['rows'=>ovr_all()]); }
if(preg_match('#^/api/payroll/overrides/([^/]+)$#',$path,$mm)){ $eid=up(urldecode($mm[1])); if($m==='PUT'){ $b=body(); $all=ovr_all(); $all[$eid]=["gross"=>array_key_exists('gross',$b)?($b['gross']===null?null:f($b['gross'])):null,"ctc"=>array_key_exists('ctc',$b)?($b['ctc']===null?null:f($b['ctc'])):null,"pfAppl"=>(bool)($b['pfAppl']??true),"esiAppl"=>(bool)($b['esiAppl']??true),"ptAppl"=>(bool)($b['ptAppl']??true),"lwfAppl"=>(bool)($b['lwfAppl']??true)]; ovr_set($all); j(['row'=>$all[$eid]]);} if($m==='DELETE'){ $all=ovr_all(); unset($all[$eid]); ovr_set($all); j(['status'=>'deleted']); } j(['detail'=>'Method Not Allowed'],405); }
if($path==='/api/payroll/generate'){ meth('POST'); $b=body(); j(['sheet'=>payroll_generate((int)$b['month'],(int)$b['year'],(string)($b['absentMode']??'LOP'))]); }
if($path==='/api/payroll/sheets'){ meth('GET'); j(['rows'=>idx('payroll_sheet_index')]); }
if(preg_match('#^/api/payroll/sheets/([^/]+)$#',$path,$mm)){
  if($m==='GET') j(['sheet'=>get_sheet(idkey('payroll_sheet',$mm[1]),'Payroll sheet not found')]);
  if($m==='DELETE'){ del_sheet('payroll_sheet',$mm[1]); j(['status'=>'deleted']); }
  j(['detail'=>'Method Not Allowed'],405);
}
if($path==='/api/payroll/clear'){ meth('POST'); clr_sheet('payroll_sheet'); ovr_set([]); j(['status'=>'cleared']); }

if($path==='/api/pf-sheet/generate'){ meth('POST'); $b=body(); j(['sheet'=>pf_generate((int)$b['month'],(int)$b['year'])]); }
if($path==='/api/pf-sheet/sheets'){ meth('GET'); j(['rows'=>idx('pf_sheet_index')]); }
if(preg_match('#^/api/pf-sheet/sheets/([^/]+)$#',$path,$mm)){ if($m==='GET') j(['sheet'=>get_sheet(idkey('pf_sheet',$mm[1]),'PF sheet not found')]); if($m==='DELETE'){ del_sheet('pf_sheet',$mm[1]); j(['status'=>'deleted']); } j(['detail'=>'Method Not Allowed'],405); }
if($path==='/api/pf-sheet/clear'){ meth('POST'); clr_sheet('pf_sheet'); j(['status'=>'cleared']); }

if($path==='/api/pf-return/generate'){ meth('POST'); $b=body(); j(['sheet'=>pf_return_generate((int)$b['month'],(int)$b['year'])]); }
if($path==='/api/pf-return/sheets'){ meth('GET'); j(['rows'=>idx('pf_return_sheet_index')]); }
if(preg_match('#^/api/pf-return/sheets/([^/]+)$#',$path,$mm)){ if($m==='GET') j(['sheet'=>get_sheet(idkey('pf_return_sheet',$mm[1]),'PF return sheet not found')]); if($m==='DELETE'){ del_sheet('pf_return_sheet',$mm[1]); j(['status'=>'deleted']); } j(['detail'=>'Method Not Allowed'],405); }
if($path==='/api/pf-return/clear'){ meth('POST'); clr_sheet('pf_return_sheet'); j(['status'=>'cleared']); }
if($path==='/api/pf-return/challans'){
  if($m==='GET') j(['rows'=>pf_challan_list()]);
  if($m==='POST'){ $b=body(); j(['row'=>pf_challan_create($b)],201); }
  j(['detail'=>'Method Not Allowed'],405);
}
if(preg_match('#^/api/pf-return/challans/([^/]+)$#',$path,$mm)){
  if($m==='DELETE'){ pf_challan_delete((string)$mm[1]); j(['status'=>'deleted']); }
  j(['detail'=>'Method Not Allowed'],405);
}
if($path==='/api/pf-return/challans/clear'){ meth('POST'); pf_challan_clear(); j(['status'=>'cleared']); }

if($path==='/api/esic-sheet/generate'){ meth('POST'); $b=body(); j(['sheet'=>esic_generate((int)$b['month'],(int)$b['year'])]); }
if($path==='/api/esic-sheet/sheets'){ meth('GET'); j(['rows'=>idx('esic_sheet_index')]); }
if(preg_match('#^/api/esic-sheet/sheets/([^/]+)$#',$path,$mm)){ if($m==='GET') j(['sheet'=>get_sheet(idkey('esic_sheet',$mm[1]),'ESIC sheet not found')]); if($m==='DELETE'){ del_sheet('esic_sheet',$mm[1]); j(['status'=>'deleted']); } j(['detail'=>'Method Not Allowed'],405); }
if($path==='/api/esic-sheet/clear'){ meth('POST'); clr_sheet('esic_sheet'); j(['status'=>'cleared']); }

if($path==='/api/ecr-sheet/generate'){ meth('POST'); $b=body(); j(['sheet'=>ecr_generate((int)$b['month'],(int)$b['year'])]); }
if($path==='/api/ecr-sheet/sheets'){ meth('GET'); j(['rows'=>idx('ecr_sheet_index')]); }
if(preg_match('#^/api/ecr-sheet/sheets/([^/]+)$#',$path,$mm)){ if($m==='GET') j(['sheet'=>get_sheet(idkey('ecr_sheet',$mm[1]),'ECR sheet not found')]); if($m==='DELETE'){ del_sheet('ecr_sheet',$mm[1]); j(['status'=>'deleted']); } j(['detail'=>'Method Not Allowed'],405); }
if($path==='/api/ecr-sheet/clear'){ meth('POST'); clr_sheet('ecr_sheet'); j(['status'=>'cleared']); }

if($path==='/api/esic-return/generate'){ meth('POST'); $b=body(); j(['sheet'=>esic_return_generate((int)$b['month'],(int)$b['year'])]); }
if($path==='/api/esic-return/sheets'){ meth('GET'); j(['rows'=>idx('esic_return_sheet_index')]); }
if(preg_match('#^/api/esic-return/sheets/([^/]+)$#',$path,$mm)){ if($m==='GET') j(['sheet'=>get_sheet(idkey('esic_return_sheet',$mm[1]),'ESIC return sheet not found')]); if($m==='DELETE'){ del_sheet('esic_return_sheet',$mm[1]); j(['status'=>'deleted']); } j(['detail'=>'Method Not Allowed'],405); }
if($path==='/api/esic-return/clear'){ meth('POST'); clr_sheet('esic_return_sheet'); j(['status'=>'cleared']); }
if($path==='/api/esic-return/challans'){ 
  if($m==='GET') j(['rows'=>esic_challan_list()]);
  if($m==='POST'){ $b=body(); j(['row'=>esic_challan_create($b)],201); }
  j(['detail'=>'Method Not Allowed'],405);
}
if(preg_match('#^/api/esic-return/challans/([^/]+)$#',$path,$mm)){
  if($m==='DELETE'){ esic_challan_delete((string)$mm[1]); j(['status'=>'deleted']); }
  j(['detail'=>'Method Not Allowed'],405);
}
if($path==='/api/esic-return/challans/clear'){ meth('POST'); esic_challan_clear(); j(['status'=>'cleared']); }

if($path==='/api/fnf/generate'){ meth('POST'); j(['sheet'=>fnf_generate(body())]); }
if($path==='/api/fnf/sheets'){ meth('GET'); j(['rows'=>idx('fnf_sheet_index')]); }
if(preg_match('#^/api/fnf/sheets/([^/]+)$#',$path,$mm)){ if($m==='GET') j(['sheet'=>get_sheet(idkey('fnf_sheet',$mm[1]),'FNF sheet not found')]); if($m==='DELETE'){ del_sheet('fnf_sheet',$mm[1]); j(['status'=>'deleted']); } j(['detail'=>'Method Not Allowed'],405); }
if($path==='/api/fnf/clear'){ meth('POST'); clr_sheet('fnf_sheet'); j(['status'=>'cleared']); }

if($path==='/api/gratuity/generate'){ meth('POST'); j(['sheet'=>gratuity_generate(body())]); }
if($path==='/api/gratuity/sheets'){ meth('GET'); j(['rows'=>idx('gratuity_sheet_index')]); }
if(preg_match('#^/api/gratuity/sheets/([^/]+)$#',$path,$mm)){ if($m==='GET') j(['sheet'=>get_sheet(idkey('gratuity_sheet',$mm[1]),'Gratuity sheet not found')]); if($m==='DELETE'){ del_sheet('gratuity_sheet',$mm[1]); j(['status'=>'deleted']); } j(['detail'=>'Method Not Allowed'],405); }
if($path==='/api/gratuity/clear'){ meth('POST'); clr_sheet('gratuity_sheet'); j(['status'=>'cleared']); }
if($path==='/api/bonus/generate'){ meth('POST'); $b=body(); j(['sheet'=>bonus_generate_preview((int)($b['month'] ?? 0),(int)($b['year'] ?? 0))]); }
if($path==='/api/bonus/sheets'){ if($m==='GET') j(['rows'=>idx('bonus_sheet_index')]); if($m==='POST') j(['sheet'=>bonus_save_sheet(body())]); j(['detail'=>'Method Not Allowed'],405); }
if(preg_match('#^/api/bonus/sheets/([^/]+)$#',$path,$mm)){ if($m==='GET') j(['sheet'=>get_sheet(idkey('bonus_sheet',$mm[1]),'Bonus sheet not found')]); if($m==='DELETE'){ del_sheet('bonus_sheet',$mm[1]); j(['status'=>'deleted']); } j(['detail'=>'Method Not Allowed'],405); }
if($path==='/api/bonus/clear'){ meth('POST'); clr_sheet('bonus_sheet'); j(['status'=>'cleared']); }
if($path==='/api/incentives'){
  if($m==='GET') j(['rows'=>incentive_rows($_GET)]);
  if($m==='POST') j(['row'=>incentive_create(body())],201);
  j(['detail'=>'Method Not Allowed'],405);
}
if($path==='/api/incentives/clear'){ meth('POST'); incentive_clear(); j(['status'=>'cleared']); }
if(preg_match('#^/api/incentives/([^/]+)$#',$path,$mm)){
  if($m==='GET'){ $row = incentive_fetch_one(db(), urldecode((string)$mm[1])); if(!$row) nf('Incentive not found'); j(['row'=>$row]); }
  if($m==='DELETE'){ incentive_delete(urldecode((string)$mm[1])); j(['status'=>'deleted']); }
  j(['detail'=>'Method Not Allowed'],405);
}

if($path==='/api/payslips/generate'){ meth('POST'); $b=body(); j(['sheet'=>payslip_generate((int)$b['month'],(int)$b['year'],(string)$b['empId'],(string)($b['format']??'html'))]); }
if($path==='/api/payslips'){ meth('GET'); j(['rows'=>idx('payslip_index')]); }
if(preg_match('#^/api/payslips/([^/]+)$#',$path,$mm) && strtolower((string)$mm[1])!=='clear'){ if($m==='GET') j(['sheet'=>get_sheet(idkey('payslip',$mm[1]),'Payslip not found')]); if($m==='DELETE'){ del_sheet('payslip',$mm[1]); j(['status'=>'deleted']); } j(['detail'=>'Method Not Allowed'],405); }
if($path==='/api/payslips/clear'){ meth('POST'); clr_sheet('payslip'); j(['status'=>'cleared']); }

if($path==='/api/compliance/tasks'){ meth('GET'); j(['rows'=>compliance_list((int)($_GET['month']??date('n')),(int)($_GET['year']??date('Y')))]); }
if($path==='/api/compliance/tasks/upsert'){ meth('POST'); $b=body(); $x=compliance_save((int)$b['month'],(int)$b['year'],$b['rows']??[]); j(['rows'=>$x,'count'=>count($x)]); }
if($path==='/api/compliance/tasks/reset'){ meth('POST'); j(['rows'=>compliance_reset((int)($_GET['month']??0),(int)($_GET['year']??0))]); }
if($path==='/api/compliance/tasks/clear'){ meth('POST'); db()->exec("DELETE FROM app_kv WHERE key LIKE 'compliance_%'"); j(['status'=>'cleared']); }
if($path==='/api/compliance/challans'){
  if($m==='GET') j(['rows'=>compliance_challan_list()]);
  if($m==='POST'){ $b=body(); j(['row'=>compliance_challan_upsert($b)],201); }
  j(['detail'=>'Method Not Allowed'],405);
}
if(preg_match('#^/api/compliance/challans/([^/]+)$#',$path,$mm)){
  if($m==='DELETE'){ compliance_challan_delete((string)$mm[1]); j(['status'=>'deleted']); }
  j(['detail'=>'Method Not Allowed'],405);
}
if($path==='/api/compliance/challans/clear'){ meth('POST'); compliance_challan_clear(); j(['status'=>'cleared']); }

if(shift_route_handle($path,$m)){ return; }

nf('Not Found');
}
