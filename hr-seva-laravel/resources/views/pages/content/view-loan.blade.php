<div class="container-fluid py-4 px-3 px-lg-4">
      <div id="pageMsg" class="d-none"></div>
      <div class="glass p-4 p-lg-5 mb-3">
        <div class="d-flex flex-column flex-md-row justify-content-between gap-3 align-items-md-center">
          <div>
            <h2 class="h4 mb-1">View Loan Details</h2>
            <p class="text-muted-3 mb-0">Employee, loan, recovery summary, and EMI deduction history.</p>
          </div>
          <div class="d-flex flex-wrap gap-2">
            <span id="topStatus" class="badge text-bg-light border">-</span>
            <a class="btn btn-outline-secondary" href="client-loan.html">Back</a>
            <a id="btnEdit" class="btn btn-primary" href="client-loan.html">Edit Loan</a>
          </div>
        </div>
      </div>
      <div class="glass p-4 p-lg-5 mb-3">
        <h3 class="h5 mb-3">Employee Details</h3>
        <div id="employeeDetailGrid" class="row g-3"></div>
      </div>
      <div class="glass p-4 p-lg-5 mb-3">
        <h3 class="h5 mb-3">Loan Details</h3>
        <div id="loanDetailGrid" class="row g-3"></div>
      </div>
      <div class="glass p-4 p-lg-5 mb-3">
        <h3 class="h5 mb-3">Recovery Details</h3>
        <div id="recoveryDetailGrid" class="row g-3"></div>
      </div>
      <div class="glass p-4 p-lg-5">
        <h3 class="h5 mb-3">EMI Deduction History</h3>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" data-no-datetime="true" data-no-srno="true">
            <thead><tr><th>Month</th><th class="text-end">EMI Deducted</th><th class="text-end">Balance</th></tr></thead>
            <tbody id="deductionHistory"></tbody>
          </table>
        </div>
      </div>
    </div>

@push('modals')
</div>
</div><button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button></div>
@endpush
