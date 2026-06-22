<div class="container-fluid py-4 px-3 px-lg-4">

      <!-- Controls -->
      <div class="row g-3">
        <div class="col-12">
          <div class="glass p-3">
            <div class="row g-3 align-items-end">
              <div class="col-12 col-lg-4">
                <label class="form-label fw-semibold">Month</label>
                <select class="form-select" id="monthSel">
                  <option value="1">Jan</option><option value="2">Feb</option><option value="3">Mar</option>
                  <option value="4">Apr</option><option value="5">May</option><option value="6">Jun</option>
                  <option value="7">Jul</option><option value="8">Aug</option><option value="9">Sep</option>
                  <option value="10">Oct</option><option value="11">Nov</option><option value="12">Dec</option>
                </select>
              </div>

              <div class="col-12 col-lg-4">
                <label class="form-label fw-semibold">Year</label>
                <select class="form-select" id="yearSel">
                  <option>2026</option><option>2025</option><option>2024</option>
                </select>
              </div>

              <div class="col-12 col-lg-4">
                <label class="form-label fw-semibold">Action</label>
                <div class="d-flex gap-2">
                  <button class="btn btn-primary w-100" id="btnGenerate" type="button">
                    <i class="bi bi-magic"></i> Generate PF Sheet
                  </button>
                </div>
              </div>
            </div>

            <hr class="my-3">

            <!-- PF rule info -->
            <div class="row g-3">
              <div class="col-12 col-lg-6">
                <div class="glass-soft p-3">
                  <div class="d-flex justify-content-between">
                    <div class="small text-muted-3">PF % (EE / ER)</div>
                    <div class="fw-semibold"><span id="pfPctEE">-</span>% / <span id="pfPctER">-</span>%</div>
                  </div>
                  <div class="d-flex justify-content-between mt-2">
                    <div class="small text-muted-3">PF Wage Cap</div>
                    <div class="fw-semibold">
                      <span id="pfCapEnabled">-</span>  -  Rs  <span id="pfCapAmt">-</span>
                    </div>
                  </div>
                  <div class="d-flex justify-content-between mt-2">
                    <div class="small text-muted-3">PF Wage % When ESI Applicable</div>
                    <div class="fw-semibold"><span id="pfOnEsiPct">-</span>%</div>
                  </div>
                </div>
              </div>

              <div class="col-12 col-lg-6">
                <div class="glass-soft p-3">
                  <div class="fw-semibold">How it works</div>
                  <div class="small text-muted-3 mt-1">
                    If ESI applies, PF wages use the configured PF wage % on gross salary. Otherwise PF follows the normal PF base and wage-cap rule.
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>

      <!-- Preview Table -->
      <div class="row g-3 mt-0">
        <div class="col-12">
          <div class="glass p-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
              <div>
                <div class="fw-semibold">PF Sheet Preview (Latest Generated)</div>
                <div class="small text-muted-3" id="previewNote">Click Generate to preview.</div>
              </div>
              <div class="d-flex gap-2">
                <span class="badge-soft" id="badgeMonth">Month: -</span>
                <span class="badge-soft">Rows: <span id="previewCount">0</span></span>
                <button class="btn btn-outline-secondary btn-sm" id="btnDownloadPreview" type="button" disabled>
                  <i class="bi bi-file-earmark-excel"></i> Download Preview
                </button>
              </div>
            </div>

            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0" id="pfPreviewTable" data-no-datetime="true">
                <thead>
                  <tr>
                    <th>Sr. No</th>
                    <th>Month</th>
                    <th>Emp_ID</th>
                    <th>MEMBER_ NAME</th>
                    <th>UAN</th>
                    <th class="text-end">GROSS_WAGES</th>
                    <th class="text-end">EPF_WAGES</th>
                    <th class="text-end">EPS_WAGES</th>
                    <th class="text-end">EDLI_WAGES</th>
                    <th class="text-end">EPF_CONTRI_REMITTED</th>
                    <th class="text-end">EPS_CONTRI_REMITTED</th>
                    <th class="text-end">EPF_EPS_DIFF_REMITTED</th>
                    <th class="text-end">NCP_DAYS</th>
                    <th class="text-end">REFUND_OF_ADVANCES</th>
                  </tr>
                </thead>
                <tbody id="previewBody"></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      <!-- Saved PF Sheets List -->
      <div class="row g-3 mt-0">
        <div class="col-12">
          <div class="glass p-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
              <div>
                <div class="fw-semibold">Generated PF Sheets (Stored)</div>
                <div class="small text-muted-3">Each Generate creates an XLS file stored in list below.</div>
              </div>
              <button class="btn btn-outline-secondary btn-sm" id="btnRefreshList" type="button">
                <i class="bi bi-arrow-repeat"></i> Refresh
              </button>
            </div>

            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0" data-no-datetime="true">
                <thead>
                  <tr>
                    <th>Sr. No</th>
                    <th>Month</th>
                    <th>Generated At</th>
                    <th class="text-end">Rows</th>
                    <th class="text-end">Total PF Wage</th>
                    <th class="text-end">Total PF (EE)</th>
                    <th class="text-end">Total PF (ER)</th>
                    <th class="text-end">Action</th>
                  </tr>
                </thead>
                <tbody id="listBody"></tbody>
              </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3">
              <div class="small text-muted-3" id="listCount">-</div>
              <button class="btn btn-outline-danger btn-sm btn-clear-history" id="btnClearAll" type="button">
                <i class="bi bi-trash3"></i> Clear PF History
              </button>
            </div>

          </div>
        </div>
      </div>

      <div class="text-center small text-muted-3 mt-4">
        &copy; <span id="yr2"></span> HR Compliance Portal
      </div>
    </div>

@push('modals')
</div>
@endpush
