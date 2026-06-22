<div class="container-fluid py-4 px-3 px-lg-4">
      <div class="alert alert-warning d-none" id="clientScopeNotice"></div>

      <div class="glass p-3 mb-3">
        <div class="d-flex flex-column flex-lg-row gap-2 align-items-lg-center justify-content-between">
          <div>
            <div class="section-title">Active Gratuity Mode</div>
            <div class="small text-muted-3">Controlled from Control Page. Only one mode is active at a time.</div>
          </div>
          <div class="d-flex flex-wrap gap-2">
            <span class="pill"><i class="bi bi-award"></i> <span id="modeBadge">-</span></span>
            <span class="pill"><i class="bi bi-calculator"></i> <span id="modeFormula">-</span></span>
          </div>
        </div>
      </div>

      <form id="gratuityForm" class="glass p-3 mb-3">
        <div class="row g-3 align-items-end">
          <div class="col-md-6" id="employeeWrap">
            <label class="form-label fw-semibold">Employee</label>
            <select id="empSelect" class="form-select" required><option value="">Search employee...</option></select>
          </div>
          <div class="col-md-3" id="monthWrap" style="display:none">
            <label class="form-label fw-semibold">Month</label>
            <select id="monthInput" class="form-select">
              <option value="1">January</option><option value="2">February</option><option value="3">March</option><option value="4">April</option>
              <option value="5">May</option><option value="6">June</option><option value="7">July</option><option value="8">August</option>
              <option value="9">September</option><option value="10">October</option><option value="11">November</option><option value="12">December</option>
            </select>
          </div>
          <div class="col-md-3" id="yearWrap" style="display:none">
            <label class="form-label fw-semibold">Year</label>
            <input type="number" id="yearInput" class="form-control" min="2000" step="1">
          </div>
          <div class="col-md-4" id="yearsWrap">
            <label class="form-label fw-semibold">Years of Service</label>
            <input type="number" id="yearsInput" class="form-control" min="0" step="0.01" placeholder="Enter years">
            <div class="small text-muted-3 mt-1" id="yearsHint">Years must be more than 5.</div>
          </div>
          <div class="col-md-2">
            <label class="form-label fw-semibold">Action</label>
            <button class="btn btn-primary w-100" type="submit"><i class="bi bi-magic"></i> Generate</button>
          </div>
        </div>
      </form>

      <div class="glass p-3 mb-3 d-none" id="latestResult"></div>

      <div class="glass p-3 mb-3 d-none" id="previewWrap">
        <div class="section-head mb-2">
          <div><h6 class="fw-bold mb-0">Preview Table</h6><div class="text-muted-3" id="previewTitle">Generated gratuity preview</div></div>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" data-no-datetime="true">
            <thead>
              <tr>
                <th>Sr. No</th>
                <th>Employee</th>
                <th>Basic</th>
                <th>DA</th>
                <th>Gratuity</th>
              </tr>
            </thead>
            <tbody id="previewTable"></tbody>
          </table>
        </div>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="glass p-3 stat-card"><div class="text-muted">Total Generated</div><div class="value" id="statTotal">0</div></div></div>
        <div class="col-md-3"><div class="glass p-3 stat-card"><div class="text-muted">After 5yr</div><div class="value" id="statFiveYear">0</div></div></div>
        <div class="col-md-3"><div class="glass p-3 stat-card"><div class="text-muted">Monthly</div><div class="value" id="statMonthly">0</div></div></div>
        <div class="col-md-3"><div class="glass p-3 stat-card"><div class="text-muted">Total Amount</div><div class="value" id="statAmount">Rs 0.00</div></div></div>
      </div>

      <div class="glass p-3">
        <div class="section-head mb-2">
          <div><h6 class="fw-bold mb-0">Generated Gratuity Records</h6><div class="text-muted-3">Client-scoped saved records</div></div>
          <button class="btn btn-sm btn-outline-danger" id="btnClearAll" type="button"><i class="bi bi-trash"></i> Clear History</button>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" data-no-datetime="true">
            <thead>
              <tr>
                <th>Sr. No</th>
                <th>Reference</th>
                <th>Mode</th>
                <th>Period / Years</th>
                <th>Rows</th>
                <th>Total</th>
                <th>Generated On</th>
                <th class="text-end">Action</th>
              </tr>
            </thead>
            <tbody id="gratuityTable"></tbody>
          </table>
        </div>
      </div>
    </div>

@push('modals')
</div>


    </div>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
@endpush
