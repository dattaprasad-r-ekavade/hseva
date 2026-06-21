CREATE TABLE IF NOT EXISTS employee_faces (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  employee_id TEXT NOT NULL,
  face_descriptor TEXT NOT NULL DEFAULT '[]',
  face_image TEXT NOT NULL DEFAULT '',
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL DEFAULT ''
);

CREATE UNIQUE INDEX IF NOT EXISTS idx_employee_faces_employee
ON employee_faces(employee_id);

CREATE TABLE IF NOT EXISTS attendance_settings (
  id INTEGER PRIMARY KEY CHECK (id = 1),
  in_allowed_from TEXT NOT NULL DEFAULT '08:00',
  in_allowed_till TEXT NOT NULL DEFAULT '11:00',
  late_mark_after TEXT NOT NULL DEFAULT '09:15',
  out_allowed_from TEXT NOT NULL DEFAULT '17:00',
  out_allowed_till TEXT NOT NULL DEFAULT '23:00',
  grace_time INTEGER NOT NULL DEFAULT 10,
  face_match_threshold REAL NOT NULL DEFAULT 0.48,
  timezone TEXT NOT NULL DEFAULT 'Asia/Kolkata',
  model_url TEXT NOT NULL DEFAULT '',
  auto_capture_seconds INTEGER NOT NULL DEFAULT 2,
  updated_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS attendance (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  employee_id TEXT NOT NULL,
  attendance_date TEXT NOT NULL,
  in_time TEXT NOT NULL DEFAULT '',
  out_time TEXT NOT NULL DEFAULT '',
  total_working_hours REAL NOT NULL DEFAULT 0,
  attendance_status TEXT NOT NULL DEFAULT '',
  in_status TEXT NOT NULL DEFAULT '',
  out_status TEXT NOT NULL DEFAULT '',
  remarks TEXT NOT NULL DEFAULT '',
  source TEXT NOT NULL DEFAULT 'face',
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL,
  UNIQUE(employee_id, attendance_date)
);

CREATE INDEX IF NOT EXISTS idx_attendance_date_employee
ON attendance(attendance_date, employee_id);

CREATE TABLE IF NOT EXISTS attendance_logs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  employee_id TEXT NOT NULL DEFAULT '',
  attendance_date TEXT NOT NULL DEFAULT '',
  action_type TEXT NOT NULL DEFAULT '',
  scan_time TEXT NOT NULL DEFAULT '',
  verification_score REAL NOT NULL DEFAULT 0,
  match_threshold REAL NOT NULL DEFAULT 0,
  is_verified INTEGER NOT NULL DEFAULT 0,
  message TEXT NOT NULL DEFAULT '',
  payload_json TEXT NOT NULL DEFAULT '{}',
  created_at TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_attendance_logs_emp_date
ON attendance_logs(employee_id, attendance_date);
