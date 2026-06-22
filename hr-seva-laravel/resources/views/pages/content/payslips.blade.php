<div class="container-fluid py-4 px-3 px-lg-4">
      <!-- Controls (PF-sheet style) -->
      <div class="row g-3 mb-3">
        <div class="col-12">
          <div class="glass p-3">
            <div class="row g-3 align-items-end">
              <div class="col-12 col-lg-2">
                <label class="form-label fw-semibold">Month</label>
                <select class="form-select" id="monthSel">
                  <option value="" selected disabled>Choose</option>
                  <option value="01">Jan</option><option value="02">Feb</option><option value="03">Mar</option>
                  <option value="04">Apr</option><option value="05">May</option><option value="06">Jun</option>
                  <option value="07">Jul</option><option value="08">Aug</option><option value="09">Sep</option>
                  <option value="10">Oct</option><option value="11">Nov</option><option value="12">Dec</option>
                </select>
              </div>
              <div class="col-12 col-lg-2">
                <label class="form-label fw-semibold">Year</label>
                <select class="form-select" id="yearSel">
                  <option value="" selected disabled>Choose</option>
                  <option value="2026">2026</option><option value="2025">2025</option><option value="2024">2024</option>
                </select>
              </div>
              <div class="col-12 col-lg-2">
                <label class="form-label fw-semibold">Name / EMP ID</label>
                <select id="genEmpId" placeholder="Search by employee ID or name..."></select>
              </div>
              <div class="col-12 col-lg-2">
                <label class="form-label fw-semibold">Format</label>
                <select class="form-select" id="genFormat">
                  <option value="pdf" selected>PDF</option>
                </select>
              </div>
              <div class="col-12 col-lg-4">
                <label class="form-label fw-semibold">Action</label>
                <div class="d-flex gap-2">
                  <button class="btn btn-primary w-100" id="btnGenerateTop" type="button"><i class="bi bi-magic"></i> Generate Payslip</button>
                  <button class="btn btn-outline-primary w-100" id="btnBulkTop" type="button"><i class="bi bi-collection"></i> Bulk Generate</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Stats -->
      <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><div class="glass p-3"><div class="small text-muted-3">Total Generated</div><div class="fs-4 fw-semibold" id="statTotal">0</div></div></div>
        <div class="col-6 col-lg-3"><div class="glass p-3"><div class="small text-muted-3">This Month</div><div class="fs-4 fw-semibold" id="statThisMonth">0</div></div></div>
        <div class="col-6 col-lg-3"><div class="glass p-3"><div class="small text-muted-3">Success</div><div class="fs-4 fw-semibold" id="statSuccess">0</div></div></div>
        <div class="col-6 col-lg-3"><div class="glass p-3"><div class="small text-muted-3">Failed</div><div class="fs-4 fw-semibold" id="statFailed">0</div></div></div>
      </div>

      <!-- Filters -->
      <div class="glass p-3">
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
              <option value="2025">2025</option>
              <option value="2026" selected>2026</option>
              <option value="2027">2027</option>
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

      <!-- Table -->
      <div class="glass p-3 mt-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
          <div>
            <div class="fw-semibold">Generated Payslips</div>
            <div class="small text-muted-3">Preview / Print / Download</div>
          </div>
          <div class="d-flex align-items-center gap-2">
            <div class="small text-muted-3" id="resultCount">-</div>
            <button class="btn btn-outline-danger btn-sm" id="btnClearPayslipHistory" type="button">
              <i class="bi bi-trash"></i> Clear Payslips
            </button>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" data-no-datetime="true">
            <thead>
              <tr>
                <th>Sr. No</th>
                <th>Payslip</th>
                <th>Month</th>
                <th>Employee ID</th>
                <th>Generated On</th>
                <th>Status</th>
                <th class="text-end">Action</th>
              </tr>
            </thead>
            <tbody id="filesTbody"></tbody>
          </table>
        </div>

        <div class="text-center text-muted-3 py-4 d-none" id="emptyState">
          No payslips generated yet. Click <span class="fw-semibold">Create / Generate</span>.
        </div>
      </div>

      <div class="text-center small text-muted-3 mt-4">
        &copy; <span id="yr"></span> HR Compliance Portal
      </div>
    </div>

@push('modals')
</div>

<!-- Mobile Sidebar -->

    </div>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  

<!-- Bulk Generate Modal -->
<div class="modal fade no-print" id="bulkGenerateModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content u-modal-radius-16">
      <div class="modal-header">
        <h5 class="modal-title">Bulk Payslip Generator</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form class="modal-body needs-validation" novalidate id="bulkGenerateForm">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label fw-semibold">Month</label>
            <select class="form-select" required id="bulkMonth">
              <option value="" selected disabled>Choose</option>
              <option value="01">Jan</option><option value="02" selected>Feb</option><option value="03">Mar</option>
              <option value="04">Apr</option><option value="05">May</option><option value="06">Jun</option>
              <option value="07">Jul</option><option value="08">Aug</option><option value="09">Sep</option>
              <option value="10">Oct</option><option value="11">Nov</option><option value="12">Dec</option>
            </select>
            <div class="invalid-feedback">Select month.</div>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Year</label>
            <select class="form-select" required id="bulkYear">
              <option value="" disabled>Choose</option>
              <option value="2025">2025</option>
              <option value="2026" selected>2026</option>
              <option value="2027">2027</option>
            </select>
            <div class="invalid-feedback">Select year.</div>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Format</label>
            <select class="form-select" id="bulkFormat">
              <option value="pdf" selected>PDF</option>
            </select>
          </div>
          <div class="col-12">
            <div class="alert alert-light border mb-0">
              Generates payslips for all employees in Employee Master for selected month/year.
            </div>
          </div>
          <div class="col-12">
            <div id="bulkStatus" class="small text-muted-3">Ready.</div>
          </div>
          <div class="col-12">
            <div class="d-flex justify-content-end gap-2">
              <button class="btn btn-outline-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
              <button class="btn btn-primary" type="submit" id="bulkGenerateBtn">
                <i class="bi bi-lightning-charge"></i> Generate Bulk Payslips
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content u-modal-radius-16">
      <div class="modal-header no-print">
        <h5 class="modal-title">Payslip Preview</h5>
        <div class="d-flex gap-2">
          <button class="btn btn-outline-secondary btn-sm" type="button" id="printFromPreviewBtn">
            <i class="bi bi-printer"></i> Print
          </button>
          <button class="btn btn-primary btn-sm" type="button" id="downloadFromPreviewBtn">
            <i class="bi bi-download"></i> Download
          </button>
          <button type="button" class="btn-close ms-1" data-bs-dismiss="modal"></button>
        </div>
      </div>
      <div class="modal-body" id="previewBody"></div>
    </div>
  </div>
</div>

<!-- Bootstrap bundle (must be before your JS) -->
@endpush
