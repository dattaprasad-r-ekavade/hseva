# Backend (PHP + SQLite)

This backend supports all `/api/*` routes via PHP and stores data in SQLite (`backend/app.db`).

## Run

```powershell
php -S 127.0.0.1:8012 router.php
```

Open:
- `http://127.0.0.1:8012/`
- `http://127.0.0.1:8012/client-control.html`

## API

- `GET /api/health`
- `GET /api/dashboard/summary?month=MM&year=YYYY`
- `POST /api/auth/login`
- `POST /api/auth/forgot`
- `GET /api/compliance/tasks?month=MM&year=YYYY`
- `POST /api/compliance/tasks/upsert`
- `POST /api/compliance/tasks/reset?month=MM&year=YYYY`
- `GET /api/compliance/challans`
- `POST /api/compliance/challans`
- `DELETE /api/compliance/challans/{challan_id}`
- `POST /api/compliance/challans/clear`
- `GET /api/control`
- `PUT /api/control`
- `POST /api/control/reset`
- `GET /api/profile`
- `PUT /api/profile`
- `POST /api/profile/reset`
- `GET /api/clients`
- `POST /api/clients`
- `PUT /api/clients/{id}`
- `DELETE /api/clients/{id}`
- Client payload includes: `companyName`, `companyAddress`, `companyRegNo`, `companyPAN`, `companyTAN`, `companyGSTIN`, `companyContactNo`, `userId`, `userPassword`, `accessType`
- `GET /api/access-control/{clientId}`
- `PUT /api/access-control/{clientId}`
  - payload supports `accessType` (`full_access`, `payroll_ops`, `compliance_ops`, `read_only`, `custom`) and `permissions`
- `GET /api/access-types`
- `POST /api/access-types`
- `PUT /api/access-types/{code}`
- `DELETE /api/access-types/{code}`
- `GET /api/employees`
- `POST /api/employees`
- `PUT /api/employees/{emp_id}`
- `DELETE /api/employees/{emp_id}`
- `POST /api/employees/bulk-upsert`
- `GET /api/leaves`
- `POST /api/leaves`
- `PUT /api/leaves/{leave_id}`
- `DELETE /api/leaves/{leave_id}`
- `POST /api/leaves/bulk-upsert`
- `GET /api/leaves/summary?month=MM&year=YYYY`
- `GET /api/attendance/daily?month=MM&year=YYYY`
- `POST /api/attendance/daily/upsert`
- `POST /api/attendance/generate`
- `GET /api/attendance/sheets`
- `GET /api/attendance/sheets/{sheet_id}`
- `GET /api/payroll/overrides`
- `PUT /api/payroll/overrides/{emp_id}`
- `DELETE /api/payroll/overrides/{emp_id}`
- `POST /api/payroll/generate`
- `GET /api/payroll/sheets`
- `GET /api/payroll/sheets/{sheet_id}`
- `DELETE /api/payroll/sheets/{sheet_id}`
- `POST /api/payroll/clear`
- `POST /api/pf-sheet/generate`
- `GET /api/pf-sheet/sheets`
- `GET /api/pf-sheet/sheets/{sheet_id}`
- `DELETE /api/pf-sheet/sheets/{sheet_id}`
- `POST /api/pf-sheet/clear`
- `POST /api/pf-return/generate`
- `GET /api/pf-return/sheets`
- `GET /api/pf-return/sheets/{sheet_id}`
- `DELETE /api/pf-return/sheets/{sheet_id}`
- `POST /api/pf-return/clear`
- `GET /api/pf-return/challans`
- `POST /api/pf-return/challans`
- `DELETE /api/pf-return/challans/{challan_id}`
- `POST /api/pf-return/challans/clear`
- `POST /api/esic-sheet/generate`
- `GET /api/esic-sheet/sheets`
- `GET /api/esic-sheet/sheets/{sheet_id}`
- `DELETE /api/esic-sheet/sheets/{sheet_id}`
- `POST /api/esic-sheet/clear`
- `POST /api/ecr-sheet/generate`
- `GET /api/ecr-sheet/sheets`
- `GET /api/ecr-sheet/sheets/{sheet_id}`
- `DELETE /api/ecr-sheet/sheets/{sheet_id}`
- `POST /api/ecr-sheet/clear`
- `POST /api/esic-return/generate`
- `GET /api/esic-return/sheets`
- `GET /api/esic-return/sheets/{sheet_id}`
- `DELETE /api/esic-return/sheets/{sheet_id}`
- `POST /api/esic-return/clear`
- `GET /api/esic-return/challans`
- `POST /api/esic-return/challans`
- `DELETE /api/esic-return/challans/{challan_id}`
- `POST /api/esic-return/challans/clear`
- `POST /api/fnf/generate`
- `GET /api/fnf/sheets`
- `GET /api/fnf/sheets/{sheet_id}`
- `DELETE /api/fnf/sheets/{sheet_id}`
- `POST /api/fnf/clear`
- `POST /api/payslips/generate`
- `GET /api/payslips`
- `GET /api/payslips/{payslip_id}`
- `DELETE /api/payslips/{payslip_id}`
- `POST /api/payslips/clear`

## SMTP Email

Hostinger SMTP development support is configured through:
- `backend/mail.php`
- `backend/mail-config.php`

Current email flow:
- landing enquiry admin notification
- landing enquiry customer thank-you email

Before production, update `backend/mail-config.php` with your Hostinger mailbox details.

SQLite DB file:
- `backend/app.db`
