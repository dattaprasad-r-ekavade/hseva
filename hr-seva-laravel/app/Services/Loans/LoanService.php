<?php

namespace App\Services\Loans;

class LoanService
{
    public function __construct(private LoanRepository $repository) {}

    public function index(): array
    {
        return ['rows' => $this->repository->rows()];
    }

    public function store(array $payload): array
    {
        return ['row' => $this->repository->createOrUpdate($payload)];
    }

    public function show(string $loanId): array
    {
        loan_view_ctx();
        $row = $this->repository->fetchOne(db(), $loanId);
        if (! $row) {
            nf('Loan not found');
        }

        return ['row' => $row];
    }

    public function update(string $loanId, array $payload): array
    {
        return ['row' => $this->repository->createOrUpdate($payload, $loanId)];
    }

    public function destroy(string $loanId): array
    {
        $this->repository->delete($loanId);

        return ['status' => 'deleted'];
    }
}
