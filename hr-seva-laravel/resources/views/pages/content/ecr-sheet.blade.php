<div class="container-fluid py-4 px-3 px-lg-4">

      <!-- Controls -->
      <div class="glass p-3 mb-3">
        <div class="row g-3 align-items-end">
          <div class="col-12 col-lg-4">
            <label class="form-label fw-semibold">Month</label>
            <div class="row g-2">
              <div class="col-6">
                <select class="form-select" id="monthSel">
                  <option value="1">Jan</option><option value="2">Feb</option><option value="3">Mar</option>
                  <option value="4">Apr</option><option value="5">May</option><option value="6">Jun</option>
                  <option value="7">Jul</option><option value="8">Aug</option><option value="9">Sep</option>
                  <option value="10">Oct</option><option value="11">Nov</option><option value="12">Dec</option>
                </select>
              </div>
              <div class="col-6">
                <select class="form-select" id="yearSel">
                  <option>2026</option><option>2025</option><option>2024</option>
                </select>
              </div>
            </div>
          </div>

          <div class="col-12 col-lg-4">
            <label class="form-label fw-semibold">Source</label>
            <div class="glass-soft p-3">
              <div class="d-flex justify-content-between align-items-center">
                <div class="small text-muted-3">From ECR Sheet API</div>
                <span class="badge-soft">/api/ecr-sheet/sheets</span>
              </div>
              <div class="small text-muted-3 mt-2">If PF sheet not found -> Dummy PF Sheet.</div>
            </div>
          </div>

          <div class="col-12 col-lg-4">
            <label class="form-label fw-semibold">Action</label>
            <div class="d-flex gap-2">
              <button class="btn btn-primary w-100" id="btnGenerate" type="button">
                <i class="bi bi-magic"></i> Generate
              </button>
              <button class="btn btn-outline-secondary w-100" id="btnDummy" type="button">
                <i class="bi bi-database"></i> Dummy PF Sheet
              </button>
            </div>
            <div class="small text-muted-3 mt-1">
              Storage: <span id="storageMode" class="fw-semibold">Loading...</span>
            </div>
          </div>
        </div>

        <hr class="my-3">

        <div class="row g-3 mb-3">
          <div class="col-12 col-lg-6">
            <div class="glass-soft p-3">
              <div class="d-flex justify-content-between">
                <div class="small text-muted-3">PF % (EE / ER)</div>
                <div class="fw-semibold"><span id="pfPctEE">12</span>% / <span id="pfPctER">12</span>%</div>
              </div>
              <div class="d-flex justify-content-between mt-2">
                <div class="small text-muted-3">PF Wage Cap</div>
                <div class="fw-semibold"><span id="pfCapEnabled">Yes</span> - Rs <span id="pfCapAmt">15,000</span></div>
              </div>
            </div>
          </div>
          <div class="col-12 col-lg-6">
            <div class="glass-soft p-3">
              <div class="fw-semibold">How it works</div>
              <div class="small text-muted-3 mt-1">
                PF_Wages = min(Basic + DA, PF_Wage_Cap) when cap enabled. PF_EE & PF_ER are % of PF_Wages.
              </div>
            </div>
          </div>
        </div>

        <div class="d-flex flex-wrap gap-2 align-items-center">
          <span class="badge-soft" id="tagMonth">Month: -</span>
          <span class="badge-soft">Saved: <span id="tagCount">0</span></span>
          <div class="ms-auto d-flex gap-2">
            <button class="btn btn-outline-secondary btn-sm" id="btnRefresh" type="button">
              <i class="bi bi-arrow-repeat"></i> Refresh List
            </button>
            <button class="btn btn-outline-danger btn-sm btn-clear-history" id="btnClearAll" type="button">
              <i class="bi bi-trash3"></i> Clear History
            </button>
          </div>
        </div>
      </div>

      <!-- Preview -->
      <div class="glass p-3 mb-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
          <div>
            <div class="fw-semibold">ECR Sheet Preview (Latest Generated)</div>
            <div class="small text-muted-3" id="previewNote">Click Generate to preview.</div>
          </div>
          <div class="d-flex gap-2">
            <span class="badge-soft" id="previewMonth">Month: -</span>
            <span class="badge-soft" id="previewCount">Rows: 0</span>
            <button class="btn btn-outline-secondary btn-sm" id="btnDownloadPreview" type="button" disabled>
              <i class="bi bi-file-earmark-excel"></i> Download Preview
            </button>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>UAN</th>
                <th>Member Name</th>
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

      <!-- List -->
      <div class="glass p-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
          <div>
            <div class="fw-semibold">Generated ECR Sheet List</div>
            <div class="small text-muted-3">Download saved ECR sheet anytime.</div>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Sr. No</th>
                <th>Month</th>
                <th>Generated At</th>
                <th class="text-end">Rows</th>
                <th class="text-end">Total PF Wage</th>
                <th class="text-end">Total EE</th>
                <th class="text-end">Total ER</th>
                <th class="text-end">Total PF</th>
                <th class="text-end">Action</th>
              </tr>
            </thead>
            <tbody id="listBody"></tbody>
          </table>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3">
          <div class="small text-muted-3" id="listCount">-</div>
        </div>
      </div>

      <div class="text-center small text-muted-3 mt-4">
        &copy; <span id="yr"></span> HR Compliance Portal
      </div>
    </div>

@push('modals')
</div>
@endpush
