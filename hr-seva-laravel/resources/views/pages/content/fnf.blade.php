<div class="container-fluid py-4 px-3 px-lg-4">
      <form id="fnfForm" class="glass p-3 mb-3">
        <div class="row g-3 align-items-end">
          <div class="col-md-4">
            <label class="form-label fw-semibold">Name / EMP ID</label>
            <select id="empSelect" class="form-select" required><option value="">Search employee...</option></select>
          </div>
          <div class="col-md-3">
            <label class="form-label fw-semibold">Resignation Date</label>
            <input type="date" id="resignationDate" class="form-control" required>
          </div>
          <div class="col-md-3">
            <label class="form-label fw-semibold">Exit Date</label>
            <input type="date" id="exitDate" class="form-control" required>
          </div>
          <div class="col-md-2">
            <label class="form-label fw-semibold">Action</label>
            <div class="d-flex gap-2">
              <button class="btn btn-primary flex-fill" type="submit"><i class="bi bi-magic"></i> Generate</button>
            </div>
          </div>
        </div>
        <div class="fnf-adv-grid mt-3">
          <div><label class="form-label">Format</label><select id="fnfFormat" class="form-select"><option value="pdf">PDF</option><option value="xlsx">XLSX</option></select></div>
          <div><label class="form-label">Gross Salary</label><input type="number" id="gross" class="form-control" min="0" step="0.01" required></div>
          <div><label class="form-label">Paid Days</label><input type="number" id="paidDays" class="form-control" min="0" step="0.01" required></div>
          <div><label class="form-label">LOP Days</label><input type="number" id="lopDays" class="form-control" min="0" step="0.01" required></div>
          <div><label class="form-label">EL Balance</label><input type="number" id="elDays" class="form-control" min="0" step="0.01" required></div>
          <div><label class="form-label">Bonus / Incentives</label><input type="number" id="bonus" class="form-control" min="0" step="0.01"></div>
          <div><label class="form-label">Gratuity</label><input type="number" id="gratuity" class="form-control" min="0" step="0.01" readonly></div>
          <div><label class="form-label">Advance / Loan</label><input type="number" id="advance" class="form-control" min="0" step="0.01"></div>
          <div><label class="form-label">Notice Recovery</label><input type="number" id="notice" class="form-control" min="0" step="0.01"></div>
        </div>
        <div class="mt-2 d-none"><label class="form-label">Payable Salary</label><input type="text" id="payableSalaryPreview" class="form-control fw-semibold" value="-" readonly></div>
      </form>

      <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="glass p-3 stat-card"><div class="text-muted">Total Generated</div><div class="value" id="statTotal">0</div></div></div>
        <div class="col-md-3"><div class="glass p-3 stat-card"><div class="text-muted">This Month</div><div class="value" id="statThisMonth">0</div></div></div>
        <div class="col-md-3"><div class="glass p-3 stat-card"><div class="text-muted">Success</div><div class="value" id="statSuccess">0</div></div></div>
        <div class="col-md-3"><div class="glass p-3 stat-card"><div class="text-muted">Failed</div><div class="value" id="statFailed">0</div></div></div>
      </div>

      <div class="glass p-3 mb-3">
        <div class="row g-3 align-items-end">
          <div class="col-lg-4">
            <label class="form-label fw-semibold">Search</label>
            <input class="form-control" id="searchInput" placeholder="Search by employee ID, name, month, or file...">
          </div>
          <div class="col-6 col-lg-2">
            <label class="form-label fw-semibold">Month</label>
            <select class="form-select" id="filterMonth">
              <option value="">All</option>
              <option value="01">Jan</option><option value="02">Feb</option><option value="03">Mar</option>
              <option value="04">Apr</option><option value="05">May</option><option value="06">Jun</option>
              <option value="07">Jul</option><option value="08">Aug</option><option value="09">Sep</option>
              <option value="10">Oct</option><option value="11">Nov</option><option value="12">Dec</option>
            </select>
          </div>
          <div class="col-6 col-lg-2">
            <label class="form-label fw-semibold">Year</label>
            <select class="form-select" id="filterYear">
              <option value="">All</option>
            </select>
          </div>
          <div class="col-6 col-lg-2">
            <label class="form-label fw-semibold">Status</label>
            <select class="form-select" id="filterStatus">
              <option value="">All</option>
              <option value="success">Success</option>
              <option value="failed">Failed</option>
              <option value="processing">Processing</option>
            </select>
          </div>
          <div class="col-6 col-lg-2 d-grid">
            <button class="btn btn-outline-secondary" id="clearFilters" type="button"><i class="bi bi-funnel"></i> Clear</button>
          </div>
        </div>
      </div>

      <div class="glass p-3">
        <div class="section-head mb-2">
          <div><h6 class="fw-bold mb-0">Generated FNF List</h6><div class="text-muted-3">Preview / Print / Download</div></div>
          <div class="d-flex align-items-center gap-2">
            <span class="text-muted-3"><span id="resultCount">0</span> record</span>
            <button class="btn btn-sm btn-outline-danger btn-clear-history" id="btnClearAll" type="button"><i class="bi bi-trash"></i> Clear History</button>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" data-no-datetime="true">
            <thead>
              <tr>
                <th>Sr. No</th>
                <th>FNF File</th>
                <th>Month</th>
                <th>Employee ID</th>
                <th>Generated On</th>
                <th>Status</th>
                <th class="text-end">Action</th>
              </tr>
            </thead>
            <tbody id="fnfTable"></tbody>
          </table>
        </div>
      </div>
      <div class="text-center small text-muted-3 mt-4">&copy; <span id="yr"></span> HR Compliance Portal</div>
    </div>

@push('modals')
</div>


    </div>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  

<div class="modal fade" id="fnfViewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">FNF Sheet Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="fnfViewBody"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" id="fnfPrintBtn"><i class="bi bi-printer"></i> Print</button>
        <button type="button" class="btn btn-outline-primary" id="fnfDownloadPdfBtn"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
        <button type="button" class="btn btn-outline-success" id="fnfDownloadXlsBtn"><i class="bi bi-file-earmark-excel"></i> XLS</button>
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
@endpush
