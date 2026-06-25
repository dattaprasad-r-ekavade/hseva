<?php

namespace App\Services\Enquiries;

class EnquiryRepository
{
    public function create(array $raw): array
    {
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

        if ($fullName === '') {
            bad('Full name is required');
        }
        if ($companyName === '') {
            bad('Company name is required');
        }
        if ($workEmail === '' && $phoneNo === '') {
            bad('Email or phone is required');
        }
        if ($productInterest === '') {
            bad('Please choose a product interest');
        }
        if ($preferredDate === '') {
            bad('Preferred date is required');
        }

        $ts = now_iso();
        $d = central_db();
        $st = $d->prepare(
            'INSERT INTO public_enquiries (full_name,company_name,work_email,phone_no,team_size,product_interest,preferred_date,preferred_time,modules,message,source_page,status,admin_note,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $st->execute([
            $fullName, $companyName, $workEmail, $phoneNo, $teamSize, $productInterest,
            $preferredDate, $preferredTime, json_encode($modules, JSON_UNESCAPED_UNICODE),
            $message, $sourcePage, 'New', '', $ts, $ts,
        ]);
        $id = (int) $d->lastInsertId();

        $q = $d->prepare('SELECT * FROM public_enquiries WHERE id=? LIMIT 1');
        $q->execute([$id]);
        $row = $q->fetch();
        $payload = enquiry_row_payload($row ?: []);
        enquiry_send_emails($payload);

        return $payload;
    }

    public function adminCreate(array $raw): array
    {
        require_super_admin();

        return $this->create($raw);
    }

    public function all(): array
    {
        require_super_admin();
        $rows = central_db()->query('SELECT * FROM public_enquiries ORDER BY id DESC')->fetchAll();

        return array_map('enquiry_row_payload', $rows ?: []);
    }

    public function update(int $id, array $raw): array
    {
        require_super_admin();
        if ($id <= 0) {
            bad('Invalid enquiry id');
        }

        $d = central_db();
        $q = $d->prepare('SELECT * FROM public_enquiries WHERE id=? LIMIT 1');
        $q->execute([$id]);
        $row = $q->fetch();
        if (! $row) {
            nf('Enquiry not found');
        }

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
            : enquiry_modules_norm(json_decode((string) ($row['modules'] ?? '[]'), true));
        $message = s($raw['message'] ?? ($row['message'] ?? ''));
        $sourcePage = s($raw['sourcePage'] ?? ($row['source_page'] ?? 'landing'), 'landing');
        $status = enquiry_status_norm((string) ($raw['status'] ?? ($row['status'] ?? 'New')));
        $adminNote = s($raw['adminNote'] ?? ($row['admin_note'] ?? ''));

        if ($fullName === '') {
            bad('Full name is required');
        }
        if ($companyName === '') {
            bad('Company name is required');
        }
        if ($workEmail === '' && $phoneNo === '') {
            bad('Email or phone is required');
        }
        if ($productInterest === '') {
            bad('Please choose a product interest');
        }
        if ($preferredDate === '') {
            bad('Preferred date is required');
        }

        $ts = now_iso();
        $upd = $d->prepare(
            'UPDATE public_enquiries SET full_name=?, company_name=?, work_email=?, phone_no=?, team_size=?, product_interest=?, preferred_date=?, preferred_time=?, modules=?, message=?, source_page=?, status=?, admin_note=?, updated_at=? WHERE id=?'
        );
        $upd->execute([
            $fullName, $companyName, $workEmail, $phoneNo, $teamSize, $productInterest,
            $preferredDate, $preferredTime, json_encode($modules, JSON_UNESCAPED_UNICODE),
            $message, $sourcePage, $status, $adminNote, $ts, $id,
        ]);
        $q->execute([$id]);
        $fresh = $q->fetch();

        return enquiry_row_payload($fresh ?: []);
    }

    public function delete(int $id): void
    {
        require_super_admin();
        if ($id <= 0) {
            bad('Invalid enquiry id');
        }

        $d = central_db();
        $q = $d->prepare('DELETE FROM public_enquiries WHERE id=?');
        $q->execute([$id]);
        if ($q->rowCount() <= 0) {
            nf('Enquiry not found');
        }
    }
}
