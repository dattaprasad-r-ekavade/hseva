<div class="container-fluid py-4 px-3 px-lg-4">
        <div class="glass p-3">
          <div class="row g-3 align-items-end">
            <div class="col-12 col-md-4"><label class="form-label fw-semibold">Date</label><input class="form-control" id="attendanceDate" type="date"></div>
            <div class="col-12 col-md-4"><label class="form-label fw-semibold">Employee</label><select class="form-select" id="employeeIdFilter"><option value="">All employees</option></select></div>
            <div class="col-12 col-md-4"><button class="btn btn-primary w-100" id="btnLoadSheet" type="button"><i class="bi bi-search"></i> Load Sheet</button></div>
          </div>
          <div class="table-responsive mt-3">
            <table class="table table-hover align-middle mb-0">
              <thead><tr><th>Employee ID</th><th>Employee Name</th><th>Department</th><th>Designation</th><th>Date</th><th>IN</th><th>OUT</th><th>Hours</th><th>Status</th><th>IN Status</th><th>OUT Status</th><th>Remarks</th><th>Action</th></tr></thead>
              <tbody id="attendanceSheetBody"></tbody>
            </table>
          </div>
        </div>
      </div>

@push('modals')
</div>
  <div class="modal fade" id="attendanceEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Attendance</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="attendanceEditForm" class="row g-3">
            <input type="hidden" id="editAttendanceId">
            <div class="col-12"><label class="form-label fw-semibold">Date</label><input class="form-control" id="editAttendanceDate" type="date" required></div>
            <div class="col-6"><label class="form-label fw-semibold">IN Time</label><input class="form-control" id="editInTime" type="time" step="1"></div>
            <div class="col-6"><label class="form-label fw-semibold">OUT Time</label><input class="form-control" id="editOutTime" type="time" step="1"></div>
            <div class="col-6"><label class="form-label fw-semibold">Hours</label><input class="form-control" id="editHours" type="number" min="0" step="0.01"></div>
            <div class="col-6"><label class="form-label fw-semibold">Status</label><input class="form-control" id="editAttendanceStatus" type="text"></div>
            <div class="col-6"><label class="form-label fw-semibold">IN Status</label><input class="form-control" id="editInStatus" type="text"></div>
            <div class="col-6"><label class="form-label fw-semibold">OUT Status</label><input class="form-control" id="editOutStatus" type="text"></div>
            <div class="col-12"><label class="form-label fw-semibold">Remarks</label><textarea class="form-control" id="editRemarks" rows="3"></textarea></div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" id="btnSaveAttendanceEdit">Save Changes</button>
        </div>
      </div>
    </div>
  </div>
  </div><button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button></div>
@endpush
