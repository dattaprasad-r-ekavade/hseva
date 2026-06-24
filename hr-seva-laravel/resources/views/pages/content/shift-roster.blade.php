<div class="container-fluid py-4 px-3 px-lg-4">
      <div id="shiftMsg" class="alert d-none"></div>
      <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabShift">Shift Master</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabAssign">Shift Assignment</button></li>
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabRoster">Weekly Roster</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabCalendar">Calendar</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabReport">Attendance Report</button></li>
      </ul>

      <div class="tab-content">
        <div class="tab-pane fade" id="tabShift">
          <div class="d-flex justify-content-end mb-2"><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#shiftModal">Add Shift</button></div>
          <div class="table-responsive glass p-3"><table class="table table-striped" data-no-datetime="true"><thead><tr><th>Code</th><th>Name</th><th>Type</th><th>Time</th><th>Color</th><th>Status</th><th>Action</th></tr></thead><tbody id="shiftMasterBody"></tbody></table></div>
        </div>

        <div class="tab-pane fade" id="tabAssign">
          <div class="glass p-3 mb-3"><div class="row g-2"><input type="hidden" id="assignId"><div class="col-md-3"><select id="assignEmp" class="form-select"></select></div><div class="col-md-2"><select id="assignDefaultShift" class="form-select"></select></div><div class="col-md-2"><select id="assignWeeklyOff" class="form-select"><option>Monday</option><option>Tuesday</option><option>Wednesday</option><option>Thursday</option><option>Friday</option><option>Saturday</option><option selected>Sunday</option></select></div><div class="col-md-2"><input id="assignEffective" type="date" class="form-control"></div><div class="col-md-1"><select id="assignStatus" class="form-select"><option>Active</option><option>Inactive</option></select></div><div class="col-md-2"><button id="btnSaveAssignment" class="btn btn-primary w-100">Save</button></div></div></div>
          <div class="table-responsive glass p-3"><table class="table table-striped" data-no-datetime="true"><thead><tr><th>Employee ID</th><th>Name</th><th>Default Shift</th><th>Weekly Off</th><th>Effective</th><th>Status</th><th>Action</th></tr></thead><tbody id="assignmentBody"></tbody></table></div>
        </div>

        <div class="tab-pane fade show active" id="tabRoster">
          <div class="glass p-3 mb-2">
            <div class="mb-2">
              <div class="fw-semibold">Weekly Roster Grid</div>
              <div class="small text-muted-3">Click any cell to assign shift code and save draft.</div>
            </div>
            <div class="row g-2 align-items-end">
              <div class="col-md-3"><label class="form-label">Month</label><select id="rosterMonth" class="form-select"></select></div>
              <div class="col-md-3"><label class="form-label">Year</label><select id="rosterYear" class="form-select"></select></div>
              <div class="col-md-6"><button id="btnRosterRefresh" class="btn btn-outline-secondary w-100" type="button">Refresh</button></div>
            </div>
            <div class="mt-2 d-flex gap-2 flex-wrap">
              <button id="btnRosterUpdate" class="btn btn-primary btn-sm" type="button">Update</button>
            </div>
          </div>
          <div class="glass p-3 roster-month-wrap">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-2">
              <div>
                <div class="fw-semibold">Weekly Roster Grid</div>
                <div class="small text-muted-3">Click any cell in the grid to assign/update shift.</div>
              </div>
              <div class="d-flex flex-wrap align-items-center justify-content-end gap-2">
                <span class="badge rounded-pill text-bg-light border" id="rosterMonthBadge">Month: -</span>
                <span class="badge rounded-pill text-bg-light border" id="rosterYearBadge">Year: -</span>
                <button id="btnRosterExportWeekly" class="btn btn-outline-secondary btn-sm" type="button"><i class="bi bi-download"></i> Weekly Export</button>
                <button id="btnRosterExport" class="btn btn-outline-secondary btn-sm" type="button"><i class="bi bi-download"></i> Export</button>
              </div>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-4">
              <div class="form-check me-1">
                <input class="form-check-input" type="checkbox" id="multiSelectMode">
                <label class="form-check-label small" for="multiSelectMode">Multi Select</label>
              </div>
              <select id="multiShiftCode" class="form-select form-select-sm" style="max-width:220px">
                <option value="">Select shift</option>
              </select>
              <input id="multiShiftNote" class="form-control form-control-sm" style="max-width:220px" placeholder="Note (optional)">
              <select id="multiShiftHalfDay" class="form-select form-select-sm" style="max-width:150px">
                <option value="No">No</option>
                <option value="First Half">First Half</option>
                <option value="Second Half">Second Half</option>
              </select>
              <button id="btnApplyMultiShift" class="btn btn-primary btn-sm" type="button">Apply Selected</button>
              <button id="btnClearMultiShift" class="btn btn-outline-danger btn-sm" type="button">Clear Shift</button>
              <button id="btnClearMultiSelection" class="btn btn-outline-secondary btn-sm" type="button">Clear Selection</button>
              <input id="rosterEmpSearch" class="form-control form-control-sm" style="max-width:240px" list="rosterEmpSearchList" placeholder="Employee Search (ID or name)">
              <datalist id="rosterEmpSearchList"></datalist>
              <span class="small text-muted-3" id="multiSelectedCount">0 selected</span>
            </div>
            <div class="shift-legend mb-2" id="legendRow"></div>
            <table class="table table-bordered mb-0 roster-month-table" id="rosterTable" data-no-datetime="true" data-srno-rendering="0" data-srno-auto-mode="true">
              <thead id="rosterHead"></thead>
              <tbody id="rosterBody"></tbody>
            </table>
          </div>
        </div>

        <div class="tab-pane fade" id="tabCalendar">
          <div class="glass p-3 mb-2"><div class="row g-2"><div class="col-md-3"><input id="calDepartment" class="form-control" placeholder="Department"></div><div class="col-md-3"><input id="calEmployee" class="form-control" placeholder="Employee ID"></div><div class="col-md-3"><input id="calShift" class="form-control" placeholder="Shift code"></div><div class="col-md-3"><button id="btnCalRefresh" class="btn btn-primary w-100">Refresh Calendar</button></div></div></div>
          <div class="row g-3"><div class="col-lg-9"><div class="glass p-3"><div id="shiftCalendar"></div></div></div><div class="col-lg-3"><div class="glass p-3"><div class="fw-semibold mb-2">Day Summary</div><div id="calendarSummary"></div></div></div></div>
        </div>

        <div class="tab-pane fade" id="tabReport">
          <div class="glass p-3 mb-2"><div class="row g-2 align-items-end"><div class="col-md-3"><label class="form-label">From</label><input id="reportFrom" type="date" class="form-control"></div><div class="col-md-3"><label class="form-label">To</label><input id="reportTo" type="date" class="form-control"></div><div class="col-md-2"><button id="btnReportLoad" class="btn btn-primary w-100">Load Report</button></div><div class="col-md-2"><button id="btnReportCsv" class="btn btn-outline-success w-100">Export CSV</button></div><div class="col-md-1"><button id="btnReportClear" class="btn btn-outline-secondary w-100" type="button">Clear</button></div><div class="col-md-1"><button id="btnReportDeleteAll" class="btn btn-outline-danger w-100" type="button">Delete All</button></div></div></div>
          <div class="table-responsive glass p-3"><table class="table table-sm table-striped" data-no-datetime="true"><thead><tr><th>Date</th><th>Company</th><th>Employee ID</th><th>Name</th><th>Shift</th><th>Scheduled In</th><th>Scheduled Out</th><th>Status</th><th>Mismatch</th></tr></thead><tbody id="reportBody"></tbody></table></div>
        </div>
      </div>
    </div>

@push('modals')
</div>


    </div>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  

<div class="modal fade" id="rosterCellModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Set Shift / Roster</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="small text-muted-3" id="cellEmpLabel"></div>
        <div class="small text-muted-3 mb-2" id="cellDateLabel"></div>
        <div id="cellShiftButtons" class="status-grid mb-3"></div>
        <label class="form-label fw-semibold">Note (required for CL / SL / EL / LOP)</label>
        <textarea id="cellNote" class="form-control mb-3" rows="2" placeholder="Enter reason/note for leave"></textarea>
        <label class="form-label fw-semibold">Half Day?</label>
        <select id="cellHalfDay" class="form-select">
          <option value="No">No</option>
          <option value="First Half">First Half</option>
          <option value="Second Half">Second Half</option>
        </select>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" id="btnCellClear" type="button">Clear</button>
        <button class="btn btn-primary" id="btnCellApply" type="button">Save</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="weeklyExportModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Weekly Export</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-2">
          <div class="col-md-6">
            <label class="form-label">From Date</label>
            <input id="weeklyExportFrom" type="date" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label">To Date</label>
            <input id="weeklyExportTo" type="date" class="form-control">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button id="btnGenerateWeeklyExport" type="button" class="btn btn-primary">Generate</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="shiftModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Shift Master</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="row g-2"><input type="hidden" id="shiftId"><div class="col-md-3"><input id="shiftCode" class="form-control" placeholder="Shift code"></div><div class="col-md-5"><input id="shiftName" class="form-control" placeholder="Shift name"></div><div class="col-md-4"><select id="shiftType" class="form-select"><option>Working</option><option>Off</option><option>Leave</option><option>Holiday</option></select></div><div class="col-md-3"><input id="shiftStart" type="time" class="form-control"></div><div class="col-md-3"><input id="shiftEnd" type="time" class="form-control"></div><div class="col-md-2"><input id="shiftBreak" type="number" class="form-control" placeholder="Break"></div><div class="col-md-2"><input id="shiftHours" type="number" step="0.25" class="form-control" placeholder="Hours"></div><div class="col-md-2"><input id="shiftGrace" type="number" class="form-control" placeholder="Grace"></div><div class="col-md-2"><input id="shiftHalfDay" type="number" step="0.25" class="form-control" placeholder="Half Day"></div><div class="col-md-2"><input id="shiftColor" type="color" class="form-control form-control-color" value="#0d6efd"></div><div class="col-md-2"><select id="shiftStatus" class="form-select"><option>Active</option><option>Inactive</option></select></div><div class="col-md-2"><div class="form-check mt-2"><input id="shiftOT" type="checkbox" class="form-check-input"><label class="form-check-label">OT Eligible</label></div></div></div></div><div class="modal-footer"><button class="btn btn-primary" id="btnSaveShift">Save Shift</button></div></div></div></div>
@endpush
