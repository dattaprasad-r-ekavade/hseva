# Face Attendance Setup Guide

This project now includes a browser-based Employee Face Attendance module inside the existing HR Seva Portal.

## Files added

### Database / backend
- `backend/sql/face_attendance_schema.sql`
- `backend/api.php`

### Frontend pages
- `client/face-attendance-registration.php`
- `client/face-attendance-settings.php`
- `client/face-attendance-sheet.php`
- `client/monthly-attendance-report.php`
- `client/scan-attendance.php`
- `client/my-face-attendance.php`

### Shared assets
- `assets/js/face-attendance.js`
- `assets/css/face-attendance.css`

## Main database tables

The existing `employees` table is reused.

New tables added:
- `employee_faces`
- `attendance_settings`
- `attendance`
- `attendance_logs`

## Important working idea

- Face matching is done in the browser using `face-api.js`
- The browser sends the face descriptor to PHP
- PHP compares the scanned descriptor with the saved descriptor
- If the match passes the threshold, attendance is marked

## Before you start

1. Make sure PHP is installed and working
2. Make sure camera permission is allowed in the browser
3. Use:
   - `http://localhost/...`
   - or `https://...`
4. Internet is required the first time so the browser can load the face recognition library and model files from CDN

## XAMPP setup steps

### Option 1: Run with built-in PHP server

1. Open this project folder
2. Double-click `run-php.bat`
3. Open:
   - `http://127.0.0.1:8012/client/client-login.html`

### Option 2: Run from XAMPP Apache

1. Copy the project folder into `htdocs`
2. Example:
   - `C:\\xampp\\htdocs\\hr-seva-add-loan-mod`
3. Start Apache from XAMPP
4. Open:
   - `http://localhost/hr-seva-add-loan-mod/client/client-login.html`

## First-time admin setup

1. Login as admin / HR
2. Open `Employee Master`
3. Create employees if not already created
4. Open `Roles` and create employee portal users if needed
5. Open `Employee Face Registration`
6. Select employee
7. Allow camera permission
8. Click `Capture & Register Face`

## Configure scan rules

Open:
- `Scan Attendance Settings`

Set:
- IN allowed from
- IN allowed till
- Late mark after
- OUT allowed from
- OUT allowed till
- Grace time
- Face match threshold

Suggested starter values:
- IN allowed from: `08:00`
- IN allowed till: `11:00`
- Late mark after: `09:15`
- OUT allowed from: `17:00`
- OUT allowed till: `23:00`
- Grace time: `10`
- Threshold: `0.48`

## Employee usage flow

1. Login with employee portal account
2. Open `Scan Attendance`
3. Allow camera permission
4. Look straight at the camera
5. The system scans automatically
6. If no record exists today:
   - Attendance IN is marked
7. If IN exists and OUT is empty:
   - Attendance OUT is marked
8. If both IN and OUT exist:
   - System shows `Attendance already completed for today.`

## Admin pages

- `client/face-attendance-registration.php`
- `client/face-attendance-settings.php`
- `client/face-attendance-sheet.php`
- `client/monthly-attendance-report.php`

## Employee pages

- `client/scan-attendance.php`
- `client/my-face-attendance.php`

## Current behavior notes

- The monthly report exports to Excel from the browser
- Face attendance also updates the existing monthly attendance status map as `P`
- If OUT is marked before the configured OUT time, status becomes `Early Out`
- If IN is marked after late time + grace, status becomes `Late`
- If a previous record has IN but no OUT, the sheet can show `Missing OUT`

## Recommended testing order

1. Create one employee
2. Create one employee login
3. Register employee face
4. Login as employee
5. Scan once for IN
6. Scan again for OUT
7. Check:
   - `Face Attendance Sheet`
   - `Monthly Attendance Report`
   - existing `Attendance Sheet`

## If camera does not open

Check:
- browser camera permission
- another app is not using the camera
- you are opening with `localhost`, `127.0.0.1`, or `https`
- the page is not opened from raw `file://`

## If face verification fails often

Try:
- better lighting
- face centered in the guide box
- register the face again
- slightly increase threshold, for example from `0.48` to `0.52`

