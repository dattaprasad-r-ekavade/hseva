<?php

namespace Database\Migrations\Hr;

use App\Services\Database\HrSql;
use PDO;

class CreateHrCoreSchema
{
    public function up(PDO $pdo): void
    {
        $sql = new HrSql($pdo);
        $real = $sql->realType();
        $bool = $sql->boolInt();
        $pk = $sql->autoIncrementPk();

        $sql->exec('CREATE TABLE IF NOT EXISTS app_kv (
            `key` TEXT PRIMARY KEY,
            value TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )');

        $sql->exec("CREATE TABLE IF NOT EXISTS clients (
            {$pk},
            company_name TEXT NOT NULL,
            company_address TEXT NOT NULL,
            company_reg_no TEXT NOT NULL,
            company_pan TEXT NOT NULL,
            company_tan TEXT NOT NULL,
            company_gstin TEXT NOT NULL,
            company_contact_no TEXT NOT NULL,
            company_email TEXT NOT NULL DEFAULT '',
            user_id TEXT NOT NULL DEFAULT '',
            user_password TEXT NOT NULL DEFAULT '',
            user_password_hash TEXT NOT NULL DEFAULT '',
            subscription_plan_id INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )");

        $sql->addColumnIfMissing('clients', 'company_email', "TEXT NOT NULL DEFAULT ''");
        $sql->addColumnIfMissing('clients', 'user_id', "TEXT NOT NULL DEFAULT ''");
        $sql->addColumnIfMissing('clients', 'user_password', "TEXT NOT NULL DEFAULT ''");
        $sql->addColumnIfMissing('clients', 'user_password_hash', "TEXT NOT NULL DEFAULT ''");
        $sql->addColumnIfMissing('clients', 'subscription_plan_id', 'INTEGER NOT NULL DEFAULT 0');

        $sql->exec('CREATE TABLE IF NOT EXISTS client_access (
            client_id INTEGER PRIMARY KEY,
            permissions TEXT NOT NULL,
            access_type TEXT NOT NULL DEFAULT \'custom\',
            updated_at TEXT NOT NULL
        )');
        $sql->addColumnIfMissing('client_access', 'access_type', "TEXT NOT NULL DEFAULT 'custom'");

        $sql->exec("CREATE TABLE IF NOT EXISTS access_types (
            code TEXT PRIMARY KEY,
            name TEXT NOT NULL UNIQUE,
            permissions TEXT NOT NULL,
            is_system {$bool} NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )");

        $sql->exec('CREATE TABLE IF NOT EXISTS staff_roles (
            client_id INTEGER NOT NULL,
            code TEXT NOT NULL,
            name TEXT NOT NULL,
            permissions TEXT NOT NULL,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            PRIMARY KEY (client_id, code)
        )');
        $sql->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_staff_roles_client_name ON staff_roles(client_id, name)');

        $sql->exec("CREATE TABLE IF NOT EXISTS staff_users (
            {$pk},
            client_id INTEGER NOT NULL,
            emp_id TEXT NOT NULL,
            username TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            role_code TEXT NOT NULL,
            status TEXT NOT NULL DEFAULT 'Active',
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )");
        $sql->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_staff_users_client_emp ON staff_users(client_id, emp_id)');

        $sql->exec("CREATE TABLE IF NOT EXISTS subscriptions (
            {$pk},
            client_id INTEGER NOT NULL,
            plan_name TEXT NOT NULL,
            start_date TEXT NOT NULL,
            end_date TEXT NOT NULL,
            renewal_date TEXT NOT NULL,
            status TEXT NOT NULL,
            amount {$real} NOT NULL DEFAULT 0,
            notes TEXT NOT NULL DEFAULT '',
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )");

        $sql->exec("CREATE TABLE IF NOT EXISTS subscription_plans (
            {$pk},
            plan_name TEXT NOT NULL UNIQUE,
            duration_months INTEGER NOT NULL DEFAULT 12,
            amount {$real} NOT NULL DEFAULT 0,
            status TEXT NOT NULL DEFAULT 'Active',
            features TEXT NOT NULL DEFAULT '',
            access_type_code TEXT NOT NULL DEFAULT 'full_access',
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )");
        $sql->addColumnIfMissing('subscription_plans', 'access_type_code', "TEXT NOT NULL DEFAULT 'full_access'");

        $sql->exec("CREATE TABLE IF NOT EXISTS employees (
            id TEXT PRIMARY KEY,
            name TEXT NOT NULL,
            status TEXT NOT NULL,
            dept TEXT NOT NULL,
            desig TEXT NOT NULL,
            type TEXT NOT NULL,
            mobile TEXT NOT NULL,
            email TEXT NOT NULL,
            doj TEXT NOT NULL,
            pf TEXT NOT NULL,
            uan TEXT NOT NULL,
            esi TEXT NOT NULL,
            esi_no TEXT NOT NULL,
            pf_no TEXT NOT NULL DEFAULT '',
            bank_name TEXT NOT NULL DEFAULT '',
            bank_ac TEXT NOT NULL DEFAULT '',
            ifsc TEXT NOT NULL DEFAULT '',
            aadhar_no TEXT NOT NULL DEFAULT '',
            pan_card TEXT NOT NULL DEFAULT '',
            address TEXT NOT NULL DEFAULT '',
            base_ctc {$real} NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )");
        foreach (['pf_no', 'bank_name', 'bank_ac', 'ifsc', 'aadhar_no', 'pan_card', 'address'] as $col) {
            $sql->addColumnIfMissing('employees', $col, "TEXT NOT NULL DEFAULT ''");
        }
        $sql->addColumnIfMissing('employees', 'base_ctc', "{$real} NOT NULL DEFAULT 0");

        $sql->exec("CREATE TABLE IF NOT EXISTS employee_faces (
            {$pk},
            employee_id TEXT NOT NULL,
            face_descriptor TEXT NOT NULL DEFAULT '[]',
            face_image TEXT NOT NULL DEFAULT '',
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL DEFAULT ''
        )");
        $sql->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_employee_faces_employee ON employee_faces(employee_id)');

        $sql->exec("CREATE TABLE IF NOT EXISTS attendance_settings (
            id INTEGER PRIMARY KEY,
            in_allowed_from TEXT NOT NULL DEFAULT '08:00',
            in_allowed_till TEXT NOT NULL DEFAULT '11:00',
            late_mark_after TEXT NOT NULL DEFAULT '09:15',
            out_allowed_from TEXT NOT NULL DEFAULT '17:00',
            out_allowed_till TEXT NOT NULL DEFAULT '23:00',
            grace_time INTEGER NOT NULL DEFAULT 10,
            face_match_threshold {$real} NOT NULL DEFAULT 0.48,
            timezone TEXT NOT NULL DEFAULT 'Asia/Kolkata',
            model_url TEXT NOT NULL DEFAULT '',
            auto_capture_seconds INTEGER NOT NULL DEFAULT 2,
            updated_at TEXT NOT NULL
        )");
        $sql->addColumnIfMissing('attendance_settings', 'scan_distance_cm', 'INTEGER NOT NULL DEFAULT 45');

        $sql->exec("CREATE TABLE IF NOT EXISTS attendance (
            {$pk},
            employee_id TEXT NOT NULL,
            attendance_date TEXT NOT NULL,
            in_time TEXT NOT NULL DEFAULT '',
            out_time TEXT NOT NULL DEFAULT '',
            total_working_hours {$real} NOT NULL DEFAULT 0,
            attendance_status TEXT NOT NULL DEFAULT '',
            in_status TEXT NOT NULL DEFAULT '',
            out_status TEXT NOT NULL DEFAULT '',
            remarks TEXT NOT NULL DEFAULT '',
            source TEXT NOT NULL DEFAULT 'face',
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            UNIQUE(employee_id, attendance_date)
        )");
        $sql->exec('CREATE INDEX IF NOT EXISTS idx_attendance_date_employee ON attendance(attendance_date, employee_id)');

        $sql->exec("CREATE TABLE IF NOT EXISTS attendance_logs (
            {$pk},
            employee_id TEXT NOT NULL DEFAULT '',
            attendance_date TEXT NOT NULL DEFAULT '',
            action_type TEXT NOT NULL DEFAULT '',
            scan_time TEXT NOT NULL DEFAULT '',
            verification_score {$real} NOT NULL DEFAULT 0,
            match_threshold {$real} NOT NULL DEFAULT 0,
            is_verified {$bool} NOT NULL DEFAULT 0,
            message TEXT NOT NULL DEFAULT '',
            payload_json TEXT NOT NULL DEFAULT '{}',
            created_at TEXT NOT NULL
        )");
        $sql->exec('CREATE INDEX IF NOT EXISTS idx_attendance_logs_emp_date ON attendance_logs(employee_id, attendance_date)');

        $sql->exec("CREATE TABLE IF NOT EXISTS leaves (
            {$pk},
            emp_id TEXT NOT NULL,
            emp_name TEXT NOT NULL,
            dept TEXT NOT NULL,
            desig TEXT NOT NULL,
            company TEXT NOT NULL,
            from_date TEXT NOT NULL,
            to_date TEXT NOT NULL,
            days {$real} NOT NULL,
            leave_type TEXT NOT NULL,
            reason TEXT NOT NULL,
            status TEXT NOT NULL,
            half_day TEXT NOT NULL,
            marked_by TEXT NOT NULL,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )");

        $sql->exec("CREATE TABLE IF NOT EXISTS salary_advances (
            id TEXT PRIMARY KEY,
            emp_id TEXT NOT NULL,
            employee_name TEXT NOT NULL,
            amount {$real} NOT NULL DEFAULT 0,
            repayment_type TEXT NOT NULL DEFAULT 'full',
            emi_months INTEGER NOT NULL DEFAULT 1,
            emi_amount {$real} NOT NULL DEFAULT 0,
            disbursed_on TEXT NOT NULL,
            start_year INTEGER NOT NULL DEFAULT 0,
            start_month INTEGER NOT NULL DEFAULT 0,
            attendance_year INTEGER NOT NULL DEFAULT 0,
            attendance_month INTEGER NOT NULL DEFAULT 0,
            attendance_through_date TEXT NOT NULL DEFAULT '',
            present_days {$real} NOT NULL DEFAULT 0,
            eligible_salary {$real} NOT NULL DEFAULT 0,
            monthly_gross {$real} NOT NULL DEFAULT 0,
            notes TEXT NOT NULL DEFAULT '',
            status TEXT NOT NULL DEFAULT 'Active',
            created_by TEXT NOT NULL DEFAULT '',
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )");
        foreach (['attendance_year', 'attendance_month'] as $col) {
            $sql->addColumnIfMissing('salary_advances', $col, 'INTEGER NOT NULL DEFAULT 0');
        }
        $sql->addColumnIfMissing('salary_advances', 'attendance_through_date', "TEXT NOT NULL DEFAULT ''");
        $sql->addColumnIfMissing('salary_advances', 'present_days', "{$real} NOT NULL DEFAULT 0");
        $sql->addColumnIfMissing('salary_advances', 'eligible_salary', "{$real} NOT NULL DEFAULT 0");
        $sql->addColumnIfMissing('salary_advances', 'monthly_gross', "{$real} NOT NULL DEFAULT 0");
        $sql->exec('CREATE INDEX IF NOT EXISTS idx_salary_advances_emp_status ON salary_advances(emp_id, status)');

        $sql->exec("CREATE TABLE IF NOT EXISTS incentives (
            id TEXT PRIMARY KEY,
            emp_id TEXT NOT NULL,
            employee_name TEXT NOT NULL DEFAULT '',
            incentive_date TEXT NOT NULL,
            amount {$real} NOT NULL DEFAULT 0,
            remarks TEXT NOT NULL DEFAULT '',
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )");
        $sql->exec('CREATE INDEX IF NOT EXISTS idx_incentives_emp_date ON incentives(emp_id, incentive_date)');

        $sql->exec("CREATE TABLE IF NOT EXISTS loans (
            id TEXT PRIMARY KEY,
            emp_id TEXT NOT NULL,
            employee_name TEXT NOT NULL DEFAULT '',
            dept TEXT NOT NULL DEFAULT '',
            designation TEXT NOT NULL DEFAULT '',
            property_branch TEXT NOT NULL DEFAULT '',
            loan_type TEXT NOT NULL DEFAULT '',
            requested_amount {$real} NOT NULL DEFAULT 0,
            reason TEXT NOT NULL DEFAULT '',
            request_date TEXT NOT NULL DEFAULT '',
            required_date TEXT NOT NULL DEFAULT '',
            repayment_type TEXT NOT NULL DEFAULT 'one_time',
            emi_start_year INTEGER NOT NULL DEFAULT 0,
            emi_start_month INTEGER NOT NULL DEFAULT 0,
            emi_amount {$real} NOT NULL DEFAULT 0,
            installment_count INTEGER NOT NULL DEFAULT 1,
            remarks TEXT NOT NULL DEFAULT '',
            status TEXT NOT NULL DEFAULT 'Active',
            created_by TEXT NOT NULL DEFAULT '',
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )");
        $sql->exec('CREATE INDEX IF NOT EXISTS idx_loans_emp_status ON loans(emp_id, status)');

        $sql->exec("CREATE TABLE IF NOT EXISTS loan_deductions (
            {$pk},
            loan_id TEXT NOT NULL,
            emp_id TEXT NOT NULL,
            deduction_year INTEGER NOT NULL,
            deduction_month INTEGER NOT NULL,
            scheduled_amount {$real} NOT NULL DEFAULT 0,
            deducted_amount {$real} NOT NULL DEFAULT 0,
            balance_after {$real} NOT NULL DEFAULT 0,
            payroll_period TEXT NOT NULL DEFAULT '',
            payroll_sheet_id TEXT NOT NULL DEFAULT '',
            status TEXT NOT NULL DEFAULT 'Scheduled',
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            UNIQUE(loan_id, deduction_year, deduction_month)
        )");
        $sql->exec('CREATE INDEX IF NOT EXISTS idx_loan_deductions_emp_period ON loan_deductions(emp_id, deduction_year, deduction_month)');

        $sql->exec("CREATE TABLE IF NOT EXISTS overtime_entries (
            id TEXT PRIMARY KEY,
            emp_id TEXT NOT NULL,
            employee_name TEXT NOT NULL,
            ot_date TEXT NOT NULL,
            start_time TEXT NOT NULL,
            end_time TEXT NOT NULL,
            total_hours {$real} NOT NULL DEFAULT 0,
            rate {$real} NOT NULL DEFAULT 0,
            amount {$real} NOT NULL DEFAULT 0,
            notes TEXT NOT NULL DEFAULT '',
            created_by TEXT NOT NULL DEFAULT '',
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )");
        $sql->exec('CREATE INDEX IF NOT EXISTS idx_overtime_entries_emp_date ON overtime_entries(emp_id, ot_date)');

        $sql->exec("CREATE TABLE IF NOT EXISTS advance_deductions (
            {$pk},
            advance_id TEXT NOT NULL,
            emp_id TEXT NOT NULL,
            deduction_year INTEGER NOT NULL,
            deduction_month INTEGER NOT NULL,
            scheduled_amount {$real} NOT NULL DEFAULT 0,
            deducted_amount {$real} NOT NULL DEFAULT 0,
            balance_after {$real} NOT NULL DEFAULT 0,
            payroll_period TEXT NOT NULL DEFAULT '',
            payroll_sheet_id TEXT NOT NULL DEFAULT '',
            status TEXT NOT NULL DEFAULT 'Scheduled',
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            UNIQUE(advance_id, deduction_year, deduction_month)
        )");
        $sql->exec('CREATE INDEX IF NOT EXISTS idx_advance_deductions_emp_period ON advance_deductions(emp_id, deduction_year, deduction_month)');

        $sql->exec("CREATE TABLE IF NOT EXISTS public_enquiries (
            {$pk},
            full_name TEXT NOT NULL DEFAULT '',
            company_name TEXT NOT NULL DEFAULT '',
            work_email TEXT NOT NULL DEFAULT '',
            phone_no TEXT NOT NULL DEFAULT '',
            team_size TEXT NOT NULL DEFAULT '',
            product_interest TEXT NOT NULL DEFAULT '',
            preferred_date TEXT NOT NULL DEFAULT '',
            preferred_time TEXT NOT NULL DEFAULT '',
            modules TEXT NOT NULL DEFAULT '[]',
            message TEXT NOT NULL DEFAULT '',
            source_page TEXT NOT NULL DEFAULT 'landing',
            status TEXT NOT NULL DEFAULT 'New',
            admin_note TEXT NOT NULL DEFAULT '',
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )");
        $sql->exec('CREATE INDEX IF NOT EXISTS idx_public_enquiries_status_created ON public_enquiries(status, created_at)');

        $sql->exec("CREATE TABLE IF NOT EXISTS email_logs (
            {$pk},
            module TEXT NOT NULL DEFAULT '',
            record_id TEXT NOT NULL DEFAULT '',
            client_id INTEGER NOT NULL DEFAULT 0,
            recipient TEXT NOT NULL DEFAULT '',
            subject TEXT NOT NULL DEFAULT '',
            direction TEXT NOT NULL DEFAULT 'outbound',
            status TEXT NOT NULL DEFAULT 'pending',
            error_message TEXT NOT NULL DEFAULT '',
            created_at TEXT NOT NULL
        )");
        $sql->exec('CREATE INDEX IF NOT EXISTS idx_email_logs_module_record ON email_logs(module, record_id)');
        $sql->exec('CREATE INDEX IF NOT EXISTS idx_email_logs_created_at ON email_logs(created_at)');

        $sql->exec("CREATE TABLE IF NOT EXISTS attendance_status_master (
            code TEXT PRIMARY KEY,
            short_label TEXT NOT NULL DEFAULT '',
            full_label TEXT NOT NULL DEFAULT '',
            button_class TEXT NOT NULL DEFAULT '',
            sort_order INTEGER NOT NULL DEFAULT 0,
            is_active {$bool} NOT NULL DEFAULT 1,
            note_required {$bool} NOT NULL DEFAULT 0,
            is_paid {$bool} NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )");
        foreach (['short_label', 'full_label', 'button_class'] as $col) {
            $sql->addColumnIfMissing('attendance_status_master', $col, "TEXT NOT NULL DEFAULT ''");
        }
        foreach (['sort_order'] as $col) {
            $sql->addColumnIfMissing('attendance_status_master', $col, 'INTEGER NOT NULL DEFAULT 0');
        }
        foreach (['is_active', 'note_required', 'is_paid'] as $col) {
            $sql->addColumnIfMissing('attendance_status_master', $col, "{$bool} NOT NULL DEFAULT 1");
        }
        $sql->addColumnIfMissing('attendance_status_master', 'created_at', "TEXT NOT NULL DEFAULT ''");
        $sql->addColumnIfMissing('attendance_status_master', 'updated_at', "TEXT NOT NULL DEFAULT ''");

        $sql->exec("CREATE TABLE IF NOT EXISTS employee_type_master (
            code TEXT PRIMARY KEY,
            label TEXT NOT NULL DEFAULT '',
            sort_order INTEGER NOT NULL DEFAULT 0,
            is_active {$bool} NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )");
        $sql->addColumnIfMissing('employee_type_master', 'label', "TEXT NOT NULL DEFAULT ''");
        $sql->addColumnIfMissing('employee_type_master', 'sort_order', 'INTEGER NOT NULL DEFAULT 0');
        $sql->addColumnIfMissing('employee_type_master', 'is_active', "{$bool} NOT NULL DEFAULT 1");
        $sql->addColumnIfMissing('employee_type_master', 'created_at', "TEXT NOT NULL DEFAULT ''");
        $sql->addColumnIfMissing('employee_type_master', 'updated_at', "TEXT NOT NULL DEFAULT ''");

        access_types_seed($pdo);
        access_enable_roles_visibility($pdo);
        attendance_status_seed($pdo);
        employee_type_seed($pdo);
    }
}
