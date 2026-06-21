-- 2026-03-08 Shift / Roster Management migration (SQLite)
CREATE TABLE IF NOT EXISTS shift_master (
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
);

CREATE TABLE IF NOT EXISTS employee_shift_assignments (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  emp_id TEXT NOT NULL,
  default_shift_code TEXT NOT NULL,
  weekly_off_day TEXT NOT NULL DEFAULT 'Sunday',
  effective_from TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'Active',
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL,
  UNIQUE(emp_id)
);

CREATE TABLE IF NOT EXISTS shift_rosters (
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
);

CREATE TABLE IF NOT EXISTS shift_roster_weeks (
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
);
