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
                    <i class="bi bi-magic"></i> Generate ESIC Sheet
                  </button>
                </div>
              </div>
            </div>

            <hr class="my-3">

            <div class="row g-3 mb-3">
              <div class="col-12 col-lg-6">
                <div class="glass-soft p-3">
                  <div class="d-flex justify-content-between">
                    <div class="small text-muted-3">ESI % (EE / ER)</div>
                    <div class="fw-semibold"><span id="esiPctEE">0.75</span>% / <span id="esiPctER">3.25</span>%</div>
                  </div>
                  <div class="d-flex justify-content-between mt-2">
                    <div class="small text-muted-3">ESI Wage Limit</div>
                    <div class="fw-semibold">Rs <span id="esiWageLimitLabel">21,000</span></div>
                  </div>
                </div>
              </div>
              <div class="col-12 col-lg-6">
                <div class="glass-soft p-3">
                  <div class="fw-semibold">How it works</div>
                  <div class="small text-muted-3 mt-1">
                    ESI applies when gross is within ESI wage limit. ESI_EE and ESI_ER are calculated using Control %.
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Preview -->
      <div class="glass p-3 mt-3 mb-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
          <div>
            <div class="fw-semibold">ESIC Sheet Preview (Latest Generated)</div>
            <div class="small text-muted-3" id="previewNote">Click Generate to preview.</div>
          </div>
          <div class="d-flex align-items-center gap-2">
            <span class="badge-soft" id="previewMonth">Month: -</span>
            <span class="badge-soft" id="previewCount">Rows: 0</span>
            <button class="btn btn-outline-secondary btn-sm" id="btnDownloadPreview" type="button" disabled>
              <i class="bi bi-file-earmark-excel"></i> Download Preview
            </button>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" data-no-datetime="true">
            <thead>
              <tr>
                <th>Sr. No</th>
                <th>Month</th>
                <th>IP Number</th>
                <th>IP Name</th>
                <th class="text-end">No of Days for which wages paid/payable during the month</th>
                <th class="text-end">Total Monthly Wages</th>
                <th class="text-end">Reason Code for Zero workings days</th>
                <th>Last Working Day</th>
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
            <div class="fw-semibold">Generated ESIC Sheet List</div>
            <div class="small text-muted-3">View/Download saved sheet anytime.</div>
          </div>
          <button class="btn btn-outline-secondary btn-sm" id="btnRefresh" type="button">
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
                <th class="text-end">Total ESI Wage</th>
                <th class="text-end">Total ESI (EE)</th>
                <th class="text-end">Total ESI (ER)</th>
                <th class="text-end">Total ESI</th>
                <th class="text-end">Action</th>
              </tr>
            </thead>
            <tbody id="sheetListBody"></tbody>
          </table>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3">
          <div class="small text-muted-3" id="sheetListCount">-</div>
          <button class="btn btn-outline-danger btn-sm btn-clear-history" id="btnClearAll" type="button">
            <i class="bi bi-trash3"></i> Clear ESIC History
          </button>
        </div>
      </div>

      <div class="text-center small text-muted-3 mt-4">
        &copy; <span id="yr"></span> HR Compliance Portal
      </div>
    </div>

@push('modals')
</div>
@endpush
