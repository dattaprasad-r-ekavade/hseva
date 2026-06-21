<?php
declare(strict_types=1);

// Optional standalone seeder for one tenant DB.
// Usage: php backend/seeders/seed_shift_module.php C:/path/to/storage/clients/tenant_1/app.db

if($argc < 2){
  fwrite(STDERR, "Usage: php seed_shift_module.php <sqlite_db_path>\n");
  exit(1);
}

$dbPath = (string)$argv[1];
$pdo = new PDO('sqlite:'.$dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$ts = gmdate('Y-m-d\\TH:i:s\\Z');

$pdo->exec("CREATE TABLE IF NOT EXISTS shift_master (id INTEGER PRIMARY KEY AUTOINCREMENT,shift_code TEXT NOT NULL UNIQUE,shift_name TEXT NOT NULL,start_time TEXT,end_time TEXT,break_minutes INTEGER NOT NULL DEFAULT 0,total_hours REAL NOT NULL DEFAULT 0,shift_type TEXT NOT NULL DEFAULT 'Working',late_grace_minutes INTEGER NOT NULL DEFAULT 0,half_day_hours REAL NOT NULL DEFAULT 0,ot_eligible INTEGER NOT NULL DEFAULT 0,color_code TEXT NOT NULL DEFAULT '#0d6efd',status TEXT NOT NULL DEFAULT 'Active',created_at TEXT NOT NULL,updated_at TEXT NOT NULL)");

$rows = [
  ['GS','General Shift','09:30','18:30',60,8.0,'Working',15,4.0,1,'#0d6efd','Active'],
  ['NS','Night Shift','21:00','06:00',45,8.25,'Working',10,4.0,1,'#6610f2','Active'],
  ['WO','Weekly Off',null,null,0,0.0,'Off',0,0.0,0,'#6c757d','Active'],
  ['LV','Leave',null,null,0,0.0,'Leave',0,0.0,0,'#ffc107','Active'],
  ['HD','Holiday',null,null,0,0.0,'Holiday',0,0.0,0,'#20c997','Active'],
];

$st = $pdo->prepare("INSERT OR IGNORE INTO shift_master (shift_code,shift_name,start_time,end_time,break_minutes,total_hours,shift_type,late_grace_minutes,half_day_hours,ot_eligible,color_code,status,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
foreach($rows as $r){ $st->execute([$r[0],$r[1],$r[2],$r[3],$r[4],$r[5],$r[6],$r[7],$r[8],$r[9],$r[10],$r[11],$ts,$ts]); }

echo "Shift seed completed\n";
