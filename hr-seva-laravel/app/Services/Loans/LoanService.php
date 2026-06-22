<?php

namespace App\Services\Loans;

class LoanService
{
    public function index(): array
    {
        return ['rows' => loan_rows()];
    }

    public function store(array $payload): array
    {
        return ['row' => loan_create_or_update($payload)];
    }

    public function show(string $loanId): array
    {
        loan_view_ctx();
        $row = loan_fetch_one(db(), $loanId);
        if (! $row) {
            nf('Loan not found');
        }

        return ['row' => $row];
    }

    public function update(string $loanId, array $payload): array
    {
        $payload['id'] = $loanId;

        return ['row' => loan_create_or_update($payload)];
    }

    public function destroy(string $loanId): array
    {
        loan_delete($loanId);

        return ['status' => 'deleted'];
    }
}
