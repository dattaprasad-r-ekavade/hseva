<div class="container-fluid px-3 px-lg-4 py-4">
        <div class="mx-auto d-grid gap-4">
          <div id="advanceMsg" class="d-none"></div>

          <div class="row g-3">
            <div class="col-12 col-md-6 col-xl-3"><div class="glass p-4 advance-stat"><div class="label">Active Advances</div><div id="statActive" class="value">0</div></div></div>
            <div class="col-12 col-md-6 col-xl-3"><div class="glass p-4 advance-stat"><div class="label">Outstanding Balance</div><div id="statOutstanding" class="value warn">Rs 0.00</div></div></div>
            <div class="col-12 col-md-6 col-xl-3"><div class="glass p-4 advance-stat"><div class="label">Total Disbursed</div><div id="statDisbursed" class="value">Rs 0.00</div></div></div>
            <div class="col-12 col-md-6 col-xl-3"><div class="glass p-4 advance-stat"><div class="label">Recovered In Payroll</div><div id="statRecovered" class="value ok">Rs 0.00</div></div></div>
          </div>

          <div id="managePanel" class="row g-3">
            <div class="col-12 col-xl-7">
            <div class="glass p-4 p-lg-5 h-100">
              <div class="mb-5">
                <h2 class="advance-section-title">Generate Advance Entry</h2>
                <p class="advance-section-copy mt-1 mb-0">Select employee, read present attendance, calculate earned salary, then generate an advance lower than the eligible amount.</p>
              </div>
              <form id="advanceForm">
                <div class="row g-3">
                  <label class="col-12 col-md-6">
                    <span class="form-label fw-semibold">Employee</span>
                    <select id="employee" class="form-select"></select>
                  </label>
                  <label class="col-12 col-md-6">
                    <span class="form-label fw-semibold">Advance Date</span>
                    <input id="disbursedOn" type="date" class="form-control">
                  </label>
                  <label class="col-12 col-md-6">
                    <span class="form-label fw-semibold">Advance Amount</span>
                    <input id="amount" type="number" min="0" step="0.01" class="form-control" placeholder="5000">
                  </label>
                  <label class="col-12">
                    <span class="form-label fw-semibold">Notes</span>
                    <textarea id="notes" rows="3" class="form-control" placeholder="Optional note"></textarea>
                  </label>
                </div>
                <div class="mt-4">
                  <button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle me-2"></i>Create Advance</button>
                </div>
              </form>
            </div>
            </div>

            <div class="col-12 col-xl-5">
            <div class="glass p-4 p-lg-5 h-100">
              <div class="mb-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div>
                  <h2 class="advance-section-title">Attendance Salary Check</h2>
                  <p class="advance-section-copy mt-1 mb-0" id="eligibilityHint">Select employee and advance date to calculate attendance-based eligible salary.</p>
                </div>
                <div class="advance-emi-box text-md-end">
                  <div class="label">Remaining Eligible</div>
                  <div id="remainingEligibleValue" class="value">Rs 0.00</div>
                </div>
              </div>
              <div class="row g-3">
                <div class="col-12 col-md-6"><div class="glass p-3 h-100"><div class="small text-muted-3">Period</div><div id="periodValue" class="fw-semibold">-</div></div></div>
                <div class="col-12 col-md-6"><div class="glass p-3 h-100"><div class="small text-muted-3">Present Days</div><div id="presentDaysValue" class="fw-semibold">0</div></div></div>
                <div class="col-12 col-md-6"><div class="glass p-3 h-100"><div class="small text-muted-3">Monthly Gross</div><div id="monthlyGrossValue" class="fw-semibold">Rs 0.00</div></div></div>
                <div class="col-12 col-md-6"><div class="glass p-3 h-100"><div class="small text-muted-3">Per Day Salary</div><div id="perDaySalaryValue" class="fw-semibold">Rs 0.00</div></div></div>
                <div class="col-12 col-md-6"><div class="glass p-3 h-100"><div class="small text-muted-3">Calculated Salary</div><div id="eligibleSalaryValue" class="fw-semibold">Rs 0.00</div></div></div>
                <div class="col-12 col-md-6"><div class="glass p-3 h-100"><div class="small text-muted-3">Already Generated</div><div id="existingAdvanceValue" class="fw-semibold">Rs 0.00</div></div></div>
              </div>
            </div>
            </div>
          </div>

          <div class="glass p-4 p-lg-5">
            <div class="mb-4">
              <h2 class="advance-section-title">Generated Advance List</h2>
              <p class="advance-section-copy mt-1 mb-0">After generating an advance, it will appear in this list.</p>
            </div>
            <div class="overflow-x-auto">
              <table class="table align-middle mb-0" data-no-srno="true" data-no-datetime="true">
                <thead>
                  <tr>
                    <th>Emp ID</th>
                    <th>Employee</th>
                    <th>Period</th>
                    <th>Present Days</th>
                    <th>Calculated Salary</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody id="advanceTableBody"></tbody>
              </table>
            </div>
          </div>

        </div>
      </div>

@push('modals')
</div>

  
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
@endpush
