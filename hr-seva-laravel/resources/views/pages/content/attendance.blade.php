<div class="container-fluid py-4 px-3 px-lg-4">

        <!-- Controls -->
        <div class="row g-3">
          <div class="col-12">
            <div class="glass p-3">
              <div class="row g-3 align-items-end">
                <div class="col-12 col-md-3">
                  <label class="form-label fw-semibold">Month</label>
                  <select class="form-select" id="monthSel">
                    <option value="1">Jan</option><option value="2">Feb</option><option value="3">Mar</option>
                    <option value="4">Apr</option><option value="5">May</option><option value="6">Jun</option>
                    <option value="7">Jul</option><option value="8">Aug</option><option value="9">Sep</option>
                    <option value="10">Oct</option><option value="11">Nov</option><option value="12">Dec</option>
                  </select>
                </div>

                <div class="col-12 col-md-3">
                  <label class="form-label fw-semibold">Year</label>
                  <select class="form-select" id="yearSel">
                    <option>2026</option><option>2025</option><option>2024</option>
                  </select>
                </div>

                <div class="col-12 col-md-3">
                  <label class="form-label fw-semibold">Generate</label>
                  <div class="d-grid">
                    <button class="btn btn-primary" id="btnGeneratePeriod" type="button">
                      <i class="bi bi-magic"></i> Generate
                    </button>
                  </div>
                </div>

                <div class="col-12 col-md-3">
                  <label class="form-label fw-semibold">Sync</label>
                  <div class="d-grid">
                    <button class="btn btn-outline-secondary" id="btnSyncLeaves" type="button">
                      <i class="bi bi-arrow-repeat"></i> Sync from Leave Mgmt
                    </button>
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>

        <!-- Legend + Summary -->
        <div class="row g-3 mt-0">
          <div class="col-12">
            <div class="glass p-3">
              <div class="fw-semibold mb-2">Summary (Selected Employee)</div>
              <div class="row g-2" id="summaryCards"></div>
              <div class="small text-muted-3 mt-2">Counts are based on current month grid.</div>
            </div>
          </div>
        </div>

        <!-- Calendar grid -->
        <div class="row g-3 mt-0">
          <div class="col-12">
            <div class="glass p-3">
              <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-2">
                <div>
                  <div class="fw-semibold">Monthly Attendance Grid</div>
                  <div class="small text-muted-3" id="attendanceStatusHint">Click any cell to change status.</div>
                  <div class="d-flex flex-wrap gap-2 mt-2" id="attendanceLegend"></div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                  <span class="badge-soft" id="gridMonthBadge">Month: -</span>
                  <span class="badge-soft" id="gridYearBadge">Year: -</span>
                  <button class="btn btn-outline-secondary" id="btnImport" type="button">
                    <i class="bi bi-upload"></i> <span class="d-none d-md-inline">Import</span>
                  </button>
                  <button class="btn btn-outline-secondary" id="btnExport" type="button">
                    <i class="bi bi-download"></i> <span class="d-none d-md-inline">Export</span>
                  </button>
                  <input type="file" id="fileInput" accept=".csv" class="d-none" />
                </div>
              </div>
              <div class="cal-wrap">
                <table class="table table-bordered cal-table mb-0" id="calTable">
                  <thead id="calHead"></thead>
                  <tbody id="calBody"></tbody>
                </table>
              </div>
              <div class="small text-muted-3 mt-2">
                Import CSV columns: Employee ID, Date (YYYY-MM-DD), Status (P/A/WO/CL/SL/EL/LOP). Export uses the same format.
              </div>
              <div class="small text-muted-3 mt-1">
                Storage: <span id="storageMode" class="fw-semibold">Loading...</span>
              </div>
            </div>
          </div>
        </div>

        <div class="row g-3 mt-0">
          <div class="col-12">
            <div class="glass p-3">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                  <div class="fw-semibold">Generated Monthly Attendance Sheets</div>
                  <div class="small text-muted-3">Generated files list with view and XLSX download.</div>
                </div>
              </div>
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" data-no-datetime="true">
                  <thead>
                    <tr>
                      <th>Sr. No</th>
                      <th>Period</th>
                      <th>Rows</th>
                      <th>Generated At</th>
                      <th class="text-end">Action</th>
                    </tr>
                  </thead>
                  <tbody id="sheetListBody"></tbody>
                </table>
              </div>
              <div class="mt-3 text-end">
                <button class="btn btn-outline-danger btn-sm btn-clear-history" id="btnClearSheetHistory" type="button">
                  <i class="bi bi-trash3"></i> Clear Attendance Sheet
                </button>
              </div>
            </div>
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
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    

  <!-- Status Picker Modal -->
  <div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content u-modal-radius-16">
        <div class="modal-header">
          <h5 class="modal-title">Set Attendance Status</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-2" id="attendanceStatusButtons"></div>
          <div class="mt-3">
            <label class="form-label fw-semibold">Note (required only when selected status needs it)</label>
            <textarea class="form-control" id="leaveNote" rows="2" placeholder="Enter reason/note for leave"></textarea>
            <div class="small text-danger d-none" id="leaveNoteError">Note is mandatory for the selected attendance status.</div>
          </div>
          <div class="mt-2">
            <label class="form-label fw-semibold">Half Day?</label>
            <select class="form-select" id="leaveHalfDay">
              <option value="No" selected>No</option>
              <option value="Yes">Yes</option>
            </select>
          </div>
          <div class="mt-2 d-grid">
            <button class="btn btn-primary" id="btnSaveStatus" type="button">
              <i class="bi bi-check2-circle"></i> Save
            </button>
          </div>
          <hr>
          <div class="d-flex justify-content-between align-items-center">
            <div class="small text-muted-3">
              Tip: Leave Mgmt sync will auto-fill CL/SL/EL/LOP for leave dates.
            </div>
            <button class="btn btn-outline-secondary btn-sm" id="btnClearCell"><i class="bi bi-x-circle"></i> Clear</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="sheetViewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content u-modal-radius-16">
        <div class="modal-header">
          <h5 class="modal-title" id="sheetViewTitle">Attendance Sheet</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle mb-0" data-no-srno="true" data-no-datetime="true">
              <thead>
                <tr>
                  <th>Emp ID</th>
                  <th>Employee Name</th>
                  <th>P</th>
                  <th>A</th>
                  <th>WO Taken</th>
                  <th>CL</th>
                  <th>SL</th>
                  <th>EL</th>
                  <th>LOP</th>
                  <th>Payable</th>
                </tr>
              </thead>
              <tbody id="sheetViewBody"></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
@endpush
