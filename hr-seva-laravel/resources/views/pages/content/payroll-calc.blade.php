<div class="py-4 px-3 px-lg-4">
        <div class="row g-3">
          <div class="col-12">
            <div class="glass p-3" id="sheetListCard">
              <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-4">
                  <label class="form-label fw-semibold">Month</label>
                  <select class="form-select" id="monthSel">
                    <option value="" selected disabled>Choose month</option>
                    <option value="1">Jan</option>
                    <option value="2">Feb</option>
                    <option value="3">Mar</option>
                    <option value="4">Apr</option>
                    <option value="5">May</option>
                    <option value="6">Jun</option>
                    <option value="7">Jul</option>
                    <option value="8">Aug</option>
                    <option value="9">Sep</option>
                    <option value="10">Oct</option>
                    <option value="11">Nov</option>
                    <option value="12">Dec</option>
                  </select>
                </div>
                <div class="col-12 col-lg-4">
                  <label class="form-label fw-semibold">Year</label>
                  <select class="form-select" id="yearSel">
                    <option value="" selected disabled>Choose year</option>
                    <option>2026</option>
                    <option>2025</option>
                    <option>2024</option>
                  </select>
                </div>
                <div class="col-12 col-lg-4">
                  <label class="form-label fw-semibold">Create / Generate</label>
                  <div class="d-grid">
                    <button class="btn btn-primary" id="btnGenerateSheet" type="button">Create / Generate</button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="row g-3 mt-0">
          <div class="col-md-3"><div class="glass p-3 stat-card"><div class="text-muted small">Total Generated</div><div class="fs-4 fw-semibold" id="statTotal">0</div></div></div>
          <div class="col-md-3"><div class="glass p-3 stat-card"><div class="text-muted small">This Month</div><div class="fs-4 fw-semibold" id="statThisMonth">0</div></div></div>
          <div class="col-md-3"><div class="glass p-3 stat-card"><div class="text-muted small">Successful</div><div class="fs-4 fw-semibold" id="statSuccess">0</div></div></div>
          <div class="col-md-3"><div class="glass p-3 stat-card"><div class="text-muted small">Failed</div><div class="fs-4 fw-semibold" id="statFailed">0</div></div></div>
        </div>

        <div class="row g-3 mt-0">
          <div class="col-12">
            <div class="glass p-3">
              <div class="row g-3 align-items-end">
                <div class="col-md-4"><label class="form-label">Search</label><input class="form-control" id="searchInput" placeholder="Search by file name, month, year..."></div>
                <div class="col-md-2"><label class="form-label">Filter Month</label><select class="form-select" id="filterMonth"><option value="">All</option><option value="1">Jan</option><option value="2">Feb</option><option value="3">Mar</option><option value="4">Apr</option><option value="5">May</option><option value="6">Jun</option><option value="7">Jul</option><option value="8">Aug</option><option value="9">Sep</option><option value="10">Oct</option><option value="11">Nov</option><option value="12">Dec</option></select></div>
                <div class="col-md-2"><label class="form-label">Status</label><select class="form-select" id="filterStatus"><option value="">All</option><option value="success">Success</option><option value="failed">Failed</option><option value="processing">Processing</option></select></div>
                <div class="col-md-2 d-grid"><button class="btn btn-outline-secondary" type="button" id="clearFilters">Clear</button></div>
              </div>
            </div>
          </div>
        </div>

        <div class="row g-3 mt-0">
          <div class="col-12">
            <div class="glass p-3">
              <div class="section-head">
                <div>
                  <div class="fw-semibold">Salary Sheet Preview (Latest Generated)</div>
                  <div class="small text-muted-3" id="salaryPreviewNote">Generate salary sheet to preview.</div>
                </div>
                <div class="d-flex gap-2">
                  <span class="badge-soft" id="previewMonthBadge">Month: -</span>
                  <span class="badge-soft">Rows: <span id="previewRowsBadge">0</span></span>
                  <button class="btn btn-outline-secondary btn-sm" id="btnDownloadPreview" type="button" disabled>
                    <i class="bi bi-file-earmark-excel"></i> Download Preview
                  </button>
                </div>
              </div>
              <div class="table-responsive preview-scroll">
                <table class="table table-hover align-middle mb-0 salary-preview-table" data-no-datetime="true">
                  <thead>
                    <tr>
                      <th>Sr No</th>
                      <th>Name</th>
                      <th>Month</th>
                      <th>Year</th>
                      <th class="text-end">No. of Day</th>
                      <th class="text-end">Wages for</th>
                      <th class="text-end">Earned Wa</th>
                      <th class="text-end">Basic</th>
                      <th class="text-end">HRA</th>
                      <th class="text-end">Conv Allw</th>
                      <th class="text-end">Educationa</th>
                      <th class="text-end">Gross Sala</th>
                      <th class="text-end">PF</th>
                      <th class="text-end">ESI EE</th>
                      <th class="text-end">ESI ER</th>
                      <th class="text-end" id="thPt">PT</th>
                      <th class="text-end" id="thLwfEe">LWF EE</th>
                      <th class="text-end" id="thLwfEr">LWF ER</th>
                      <th class="text-end">Total Dedu</th>
                      <th class="text-end">Net Wages</th>
                    </tr>
                  </thead>
                  <tbody id="salaryPreviewBody"></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <div class="row g-3 mt-0">
          <div class="col-12">
            <div class="glass p-3">
              <div class="section-head">
                <div>
                  <div class="fw-semibold">Generated Salary Sheets (Stored)</div>
                  <div class="small text-muted-3">Each generate creates file stored in list below.</div>
                </div>
                <button class="btn btn-outline-secondary btn-sm" id="btnRefreshSheets" type="button"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
              </div>
              <div class="table-responsive">
                <table class="table align-middle mb-0" data-no-datetime="true">
                  <thead>
                    <tr>
                      <th>Sr. No</th>
                      <th>Month</th>
                      <th>Generated At</th>
                      <th>Year</th>
                      <th class="text-end">Rows</th>
                      <th class="text-end">Total PF Wage</th>
                      <th class="text-end">Total PF (EE)</th>
                      <th class="text-end">Total PF (ER)</th>
                      <th class="text-end">Action</th>
                    </tr>
                  </thead>
                  <tbody id="filesTbody"></tbody>
                </table>
              </div>
              <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="small text-muted-3"><span id="resultCount">0</span> saved</div>
                <button class="btn btn-outline-danger btn-sm btn-clear-history" id="btnClearSheetHistory" type="button"><i class="bi bi-trash3"></i> Clear Salary History</button>
              </div>
              <div class="text-center text-muted py-4 d-none" id="emptyState">No salary sheets generated yet. Click <span class="fw-semibold">Create / Generate</span>.</div>
            </div>
          </div>
        </div>

        <div class="text-center small text-muted-3 mt-4">&copy; <span id="yr"></span> HR Compliance Portal</div>
      </div>

@push('modals')
</div>

  </div><button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button></div>
    

  <div class="modal fade" id="empModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content u-modal-radius-16"><div class="modal-header"><h5 class="modal-title">Employee Salary Override</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body">
      <div class="glass-soft p-3 mb-3"><div class="row g-2 align-items-center"><div class="col-12 col-md-4"><div class="small text-muted-3">Employee</div><div class="fw-semibold" id="mEmpName">â€”</div><div class="small text-muted-3 mono" id="mEmpId">â€”</div></div><div class="col-12 col-md-8"><div class="small text-muted-3">Tip</div><div class="small text-muted-3">Enter Monthly Gross OR Monthly CTC. If both filled, Gross will be used.</div></div></div></div>
      <div class="row g-3"><div class="col-12 col-md-6"><label class="form-label fw-semibold">Monthly Gross (Rs)</label><input class="form-control" id="mGross" type="number" min="0" step="0.01"><div class="small text-muted-3 mt-1">Gross = Basic + HRA + Conveyance + Edu + Special</div></div><div class="col-12 col-md-6"><label class="form-label fw-semibold">Monthly CTC (Rs)</label><input class="form-control" id="mCTC" type="number" min="0" step="0.01"><div class="small text-muted-3 mt-1">If used, components are auto-distributed using control %</div></div>
      <div class="col-12"><div class="glass-soft p-3"><div class="fw-semibold mb-2">Applicability</div><div class="row g-2"><div class="col-6 col-lg-3"><div class="form-check"><input class="form-check-input" type="checkbox" id="mPfAppl" checked><label class="form-check-label" for="mPfAppl">PF applicable</label></div></div><div class="col-6 col-lg-3"><div class="form-check"><input class="form-check-input" type="checkbox" id="mEsiAppl" checked><label class="form-check-label" for="mEsiAppl">ESI applicable</label></div></div><div class="col-6 col-lg-3"><div class="form-check"><input class="form-check-input" type="checkbox" id="mPtAppl" checked><label class="form-check-label" for="mPtAppl">PT applicable</label></div></div><div class="col-6 col-lg-3"><div class="form-check"><input class="form-check-input" type="checkbox" id="mLwfAppl" checked><label class="form-check-label" for="mLwfAppl">LWF applicable</label></div></div></div></div></div></div>
      <div class="mt-3 d-flex justify-content-between"><button class="btn btn-outline-secondary" id="btnModalReset" type="button"><i class="bi bi-arrow-counterclockwise"></i> Reset Override</button><div class="d-flex gap-2"><button class="btn btn-outline-secondary" data-bs-dismiss="modal" type="button">Cancel</button><button class="btn btn-primary" id="btnModalSave" type="button"><i class="bi bi-check2-circle"></i> Save Override</button></div></div>
    </div></div></div>
  </div>
@endpush
