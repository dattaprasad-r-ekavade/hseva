<div class="container-fluid py-4 px-3 px-lg-4">
      <div id="loanScopeNotice" class="alert alert-warning d-none"></div>
      <div class="row g-3 mb-3">
        <div class="col-12 col-md-6 col-xl-3"><div class="glass p-4"><div class="small text-muted-3">Active Loans</div><div id="statActive" class="fs-3 fw-semibold mt-2">0</div></div></div>
        <div class="col-12 col-md-6 col-xl-3"><div class="glass p-4"><div class="small text-muted-3">Total Loan Amount</div><div id="statDisbursed" class="fs-3 fw-semibold mt-2">Rs 0.00</div></div></div>
        <div class="col-12 col-md-6 col-xl-3"><div class="glass p-4"><div class="small text-muted-3">Recovered Amount</div><div id="statRecovered" class="fs-3 fw-semibold mt-2">Rs 0.00</div></div></div>
        <div class="col-12 col-md-6 col-xl-3"><div class="glass p-4"><div class="small text-muted-3">Outstanding Balance</div><div id="statBalance" class="fs-3 fw-semibold mt-2 text-warning-emphasis">Rs 0.00</div></div></div>
      </div>

      <div class="row g-3">
        <div class="col-12">
          <div class="glass p-4 p-lg-5">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
              <div>
                <h2 id="formTitle" class="h4 mb-1">Add Loan</h2>
                <p id="formCopy" class="text-muted-3 mb-0">Create employee loan requests with repayment setup and automatic payroll recovery.</p>
              </div>
            </div>
            <form id="loanForm">
              <input id="loanId" type="hidden">
              <div class="row g-3">
                <div class="col-12 col-md-6 col-xl-3"><label class="form-label fw-semibold">Employee Name</label><select id="empId" class="form-select"><option value="">Select employee</option></select></div>
                <div class="col-12 col-md-6 col-xl-3"><label class="form-label fw-semibold">Employee ID</label><input id="employeeCode" class="form-control" readonly></div>
                <div class="col-12 col-md-6 col-xl-3"><label class="form-label fw-semibold">Department</label><input id="department" class="form-control" readonly></div>
                <div class="col-12 col-md-6 col-xl-3"><label class="form-label fw-semibold">Property / Branch</label><input id="propertyBranch" class="form-control" readonly></div>
                <div class="col-12 col-md-6 col-xl-3 d-none"><label class="form-label fw-semibold">Designation</label><input id="designation" class="form-control" readonly></div>

                <div class="col-12 col-md-6 col-xl-3"><label class="form-label fw-semibold">Loan Type</label><select id="loanType" class="form-select"><option value="">Select loan type</option><option value="Salary Advance">Salary Advance</option><option value="Personal Loan">Personal Loan</option><option value="Emergency Loan">Emergency Loan</option></select></div>
                <div class="col-12 col-md-6 col-xl-3"><label class="form-label fw-semibold">Requested Amount</label><input id="requestedAmount" type="number" min="0" step="0.01" class="form-control text-end" placeholder="0.00"></div>
                <div class="col-12 col-md-6 col-xl-3"><label class="form-label fw-semibold">Request Date</label><input id="requestDate" type="date" class="form-control"></div>
                <div class="col-12 col-md-6 col-xl-3"><label class="form-label fw-semibold">Required Date</label><input id="requiredDate" type="date" class="form-control"></div>

                <div class="col-12"><label class="form-label fw-semibold">Reason</label><textarea id="reason" class="form-control" rows="2" placeholder="Medical, personal, family emergency, etc."></textarea></div>

                <div class="col-12 col-md-6 col-xl-3"><label class="form-label fw-semibold">Repayment Type</label><select id="repaymentType" class="form-select"><option value="emi">EMI</option><option value="one_time">One-time</option></select></div>
                <div class="col-12 col-md-6 col-xl-3"><label class="form-label fw-semibold">EMI Start Month</label><input id="emiStartMonth" type="month" class="form-control"></div>
                <div class="col-12 col-md-6 col-xl-3"><label class="form-label fw-semibold">EMI Amount</label><input id="emiAmount" type="number" min="0" step="0.01" class="form-control text-end" placeholder="0.00"></div>
                <div class="col-12 col-md-6 col-xl-3"><label class="form-label fw-semibold">Number of Installments</label><input id="installmentCount" type="number" min="1" step="1" class="form-control text-end" placeholder="1"></div>

                <div class="col-12"><label class="form-label fw-semibold">Remarks</label><textarea id="remarks" class="form-control" rows="2" placeholder="HR notes"></textarea></div>
              </div>
              <div class="d-flex flex-wrap gap-2 mt-4">
                <button id="btnSubmit" class="btn btn-primary" type="submit"><i class="bi bi-plus-circle me-2"></i>Submit Loan</button>
                <button id="btnReset" class="btn btn-outline-secondary" type="button">Reset Form</button>
              </div>
            </form>
          </div>
        </div>

        <div class="col-12">
          <div class="glass p-4 p-lg-5">
            <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-3">
              <div>
                <h2 class="h4 mb-1">Loan Records</h2>
                <p class="text-muted-3 mb-0"><span id="recordCount">0</span> records with loan balance and repayment status.</p>
              </div>
            </div>
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0" data-no-datetime="true" data-no-srno="true">
                <thead>
                  <tr>
                    <th>Employee</th>
                    <th>Loan Type</th>
                    <th class="text-end">Loan Amount</th>
                    <th class="text-end">Paid Amount</th>
                    <th class="text-end">Balance</th>
                    <th class="text-end">EMI</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                  </tr>
                </thead>
                <tbody id="loanTable"></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

@push('modals')
</div>

</div><button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button></div>
@endpush
